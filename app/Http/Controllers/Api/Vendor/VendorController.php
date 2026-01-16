<?php

namespace App\Http\Controllers\Api\Vendor;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\{Auth, DB, File};
use Illuminate\Support\Str;
use Carbon\Carbon;
use App\Http\Controllers\Controller;
use App\Models\{Booking, Cleaner, Document, Package, Service, Transaction, User, Vendor};
use App\Services\FileUploadService;
use App\Traits\ApiResponseTrait;

class VendorController extends Controller
{
    use ApiResponseTrait;
    const VENDOR_IMAGE_PATH = 'images/vendors';

    const DEFAULT_IMAGE_PATH = self::VENDOR_IMAGE_PATH.'/default.png';

    protected $fileUploadService;

    public function __construct(FileUploadService $fileUploadService)
    {
        $this->fileUploadService = $fileUploadService;
    }

    private function storeVendorImage($image)
    {
        // Create directory if it doesn't exist
        $directory = public_path(self::VENDOR_IMAGE_PATH);
        if (! file_exists($directory)) {
            mkdir($directory, 0755, true);
        }

        // Generate unique filename
        $imageName = time().'_'.uniqid().'.'.$image->getClientOriginalExtension();

        // Move image to public/images/vendors folder
        $image->move($directory, $imageName);

        return self::VENDOR_IMAGE_PATH.'/'.$imageName;
    }

    public function dashboard(Request $request)
    {
        if (! Auth::check()) {
            return response()->json([
                'message' => 'Unauthenticated. Please login to continue.',
            ], 401);
        }

        /** @var \App\Models\User $user */
        $user = Auth::user();

        if ($user->role !== 'vendor') {
            return response()->json([
                'message' => 'Access denied. Only vendors can view the dashboard.',
            ], 403);
        }

        $vendorProfile = Vendor::where('user_id', '=', $user->id, 'and')->first();
        $packagesCount = Package::where('vendor_id', '=', $user->id, 'and')->count();

        $bookingQuery = Booking::with([
            'customer:id,name,email,profile_picture',
            'package:id,title,price,vendor_id',
            'cleaner:id,name,phone,image',
        ])->where(function ($query) use ($user) {
            $query->where('vendor_id', '=', $user->id, 'and')
                ->orWhereHas('package', function ($packageQuery) use ($user) {
                    $packageQuery->where('vendor_id', '=', $user->id, 'and');
                });
        });

        $totalBookings = (clone $bookingQuery)->count();

        $statusCounts = [
            'new' => (clone $bookingQuery)->where('status', '=', 'new', 'and')->count(),
            'pending' => (clone $bookingQuery)->whereIn('status', ['pending', 'accepted'], 'and')->count(),
            'completed' => (clone $bookingQuery)->where('status', '=', 'completed', 'and')->count(),
        ];

        $bookingsTarget = $vendorProfile->bookings_target ?? 0;
        $revenueTarget = $vendorProfile->revenue_target ?? 0;

        $totalEarnings = Transaction::where('vendor_id', '=', $user->id, 'and')
            ->whereIn('status', ['paid', 'released'], 'and')
            ->sum('vendor_amount');

        $summary = [
            'total_packages' => $packagesCount,
            'total_bookings' => $totalBookings,
            'target_bookings' => $bookingsTarget,
            'target_bookings_progress' => $bookingsTarget > 0
                ? min(100, (int) round(($totalBookings / $bookingsTarget) * 100))
                : 0,
            'total_earnings' => (float) $totalEarnings,
            'revenue_target' => (float) $revenueTarget,
            'revenue_target_progress' => $revenueTarget > 0
                ? min(100, (int) round(($totalEarnings / $revenueTarget) * 100))
                : 0,
        ];

        $recentBookings = (clone $bookingQuery)
            ->orderBy('created_at', 'desc')
            ->limit(10)
            ->get()
            ->map(function ($booking) {
                return [
                    'id' => $booking->id,
                    'title' => optional($booking->package)->title ?? 'Custom booking',
                    'total_price' => $booking->total_price,
                    'booking_date_time' => $booking->booking_date_time,
                    'status' => $booking->status,
                    'customer' => [
                        'name' => optional($booking->customer)->name,
                        'email' => optional($booking->customer)->email,
                        'profile_picture' => optional($booking->customer)->profile_picture,
                    ],
                ];
            });

        $startDate = Carbon::now()->subDays(6)->startOfDay();
        $dailyCounts = (clone $bookingQuery)
            ->whereDate('booking_date_time', '>=', $startDate->toDateString())
            ->select(DB::raw('DATE(booking_date_time) as date'), DB::raw('COUNT(*) as total'))
            ->groupBy('date')
            ->pluck('total', 'date');

        $weeklyBookings = [];
        for ($i = 6; $i >= 0; $i--) {
            $date = Carbon::now()->subDays($i);
            $weeklyBookings[] = [
                'day' => $date->format('D'),
                'value' => (int) ($dailyCounts[$date->toDateString()] ?? 0),
            ];
        }

        return response()->json([
            'message' => 'Vendor dashboard data loaded successfully',
            'data' => [
                'summary' => $summary,
                'bookings_by_status' => $statusCounts,
                'recent_bookings' => $recentBookings,
                'weekly_bookings' => $weeklyBookings,
            ],
        ]);
    }

    public function updateOrCreate(Request $request)
    {
        /** @var \App\Models\User $currentUser */
        $currentUser = Auth::user();
        $userId = $currentUser->id;
        $vendor = Vendor::where('user_id', '=', $userId, 'and')->first();

        if (! $vendor) {
            $vendor = new Vendor;
        }
        $validated = $request->validate([
            'name' => 'nullable|string',
            'email' => 'nullable|email|unique:users,email,'.$userId,
            'phone' => 'nullable|string',
            'about' => 'nullable|string',
            'address' => 'nullable|string',
            'profile_image' => 'nullable|image|mimes:jpeg,png,jpg,gif|max:2048',
            'start_time' => 'sometimes|string',
            'end_time' => 'sometimes|string',
        ]);

        /** @var \App\Models\User $user */
        $user = Auth::user();

        // Upload profile image
        if ($request->hasFile('profile_image')) {
            $imagePath = $this->storeVendorImage($request->file('profile_image'));
            $user->profile_picture = $imagePath;
        }

        $user->fill($validated);
        $user->save();

        return response()->json([
            'message' => 'Vendor profile updated successfully',
            'vendor' => $user,
        ]);
    }

    public function updateAddress(Request $request)
    {
        /** @var \App\Models\User $currentUser */
        $currentUser = Auth::user();

        // Check if the user is a vendor
        if ($currentUser->role !== 'vendor') {
            return response()->json([
                'message' => 'Access denied. Only vendors can update their address.',
            ], 403);
        }

        $vendor = Vendor::where('user_id', '=', $currentUser->id, 'and')->first();

        if (! $vendor) {
            // Create vendor profile if it doesn't exist
            $vendor = new Vendor;
            $vendor->user_id = $currentUser->id;
            $vendor->approval_status = 'pending';
        }

        $validated = $request->validate([
            'address' => 'required|string',
        ]);

        $vendor->address = $validated['address'];
        $vendor->save();

        return response()->json([
            'message' => 'Vendor address updated successfully',
            'vendor' => $vendor,
        ]);
    }

   

    public function addCleaner(Request $request)
    {
        // Check if user is authenticated
        if (! Auth::check()) {
            return response()->json([
                'message' => 'Unauthenticated. Please login to continue.',
            ], 401);
        }

        // Check if authenticated user has vendor role
        /** @var \App\Models\User $user */
        $user = Auth::user();

        if ($user->role !== 'vendor') {
            return response()->json([
                'message' => 'Access denied. Only vendors can add cleaners.',
            ], 403);
        }

        $data = $request->validate([
            'name' => 'required|string',
            'phone' => 'required|string',
            'address' => 'nullable|string',
            'image' => 'nullable|image',
            'status' => 'nullable|in:active,assigned,completed',
        ]);

        $vendorProfile = Vendor::where('user_id', '=', $user->id, 'and')->first();
        if (! $vendorProfile) {
            return response()->json([
                'message' => 'Vendor profile not found for the current user.',
            ], 404);
        }

        $cleaner = new Cleaner;
        $cleaner->vendor_id = $vendorProfile->id;
        $cleaner->name = $data['name'];
        $cleaner->phone = $data['phone'];
        $cleaner->address = $data['address'] ?? null;
        $cleaner->status = $data['status'] ?? 'active';

        if ($request->hasFile('image')) {
            $cleaner->image = $request->file('image')->store('cleaners', 'public');
        }

        $cleaner->save();

        return response()->json([
            'message' => 'Cleaner added successfully',
            'cleaner' => $cleaner,
        ], 201);
    }

    public function getCleaners()
    {
        $vendorProfile = Vendor::where('user_id', '=', Auth::id(), 'and')->first();
        if (! $vendorProfile) {
            return response()->json([
                'message' => 'Vendor profile not found for the current user.',
                'cleaners' => [],
            ], 404);
        }

        $cleaners = Cleaner::where('vendor_id', '=', $vendorProfile->id, 'and')
            ->orderByDesc('id')
            ->paginate(10);

        return response()->json([
            'message' => 'Cleaners retrieved successfully',
            'cleaners' => $cleaners,
        ]);
    }

    public function getCleaner(Cleaner $cleaner)
    {
        $vendorProfile = Vendor::where('user_id', '=', Auth::id(), 'and')->first();
        if (! $vendorProfile || $cleaner->vendor_id !== $vendorProfile->id) {
            return response()->json([
                'message' => 'Unauthorized to view this cleaner.',
            ], 403);
        }

        // Load cleaner with bookings
        $cleaner->load(['bookings' => function($query) {
            $query->select('id', 'cleaner_id', 'status', 'booking_date_time', 'total_price', 'customer_id', 'package_id')
                  ->with(['customer:id,name,email,image', 'package:id,name']);
        }]);

        $bookingsCount = $cleaner->bookings()->count();
        $activeBookings = $cleaner->bookings()->where('status', '=', 'pending', 'and')->count();
        $completedBookings = $cleaner->bookings()->where('status', '=', 'completed', 'and')->count();

        return response()->json([
            'message' => 'Cleaner retrieved successfully',
            'cleaner' => array_merge($cleaner->toArray(), [
                'bookings_count' => $bookingsCount,
                'active_bookings' => $activeBookings,
                'completed_bookings' => $completedBookings,
            ]),
        ]);
    }

    public function updateCleaner(Request $request, Cleaner $cleaner)
    {
        $vendorProfile = Vendor::where('user_id', '=', Auth::id(), 'and')->first();
        if (! $vendorProfile || $cleaner->vendor_id !== $vendorProfile->id) {
            return response()->json([
                'message' => 'Unauthorized to update this cleaner.',
            ], 403);
        }

        $data = $request->validate([
            'name' => 'required|string',
            'phone' => 'required|string',
            'address' => 'nullable|string',
            'image' => 'nullable|image',
            'status' => 'required|in:active,assigned,completed',
        ]);

        if ($request->hasFile('image')) {
            $cleaner->image = $request->file('image')->store('cleaners', 'public');
        }

        $cleaner->name = $data['name'];
        $cleaner->phone = $data['phone'];
        $cleaner->address = $data['address'] ?? null;
        $cleaner->status = $data['status'];
        $cleaner->save();

        return response()->json([
            'message' => 'Cleaner updated successfully',
            'cleaner' => $cleaner,
        ]);
    }

    public function deleteCleaner(Cleaner $cleaner)
    {
        $vendorProfile = Vendor::where('user_id', '=', Auth::id(), 'and')->first();
        if (! $vendorProfile || $cleaner->vendor_id !== $vendorProfile->id) {
            return response()->json([
                'message' => 'Unauthorized to delete this cleaner.',
            ], 403);
        }

        Cleaner::where('id', '=', $cleaner->id, 'and')->delete();

        return response()->json([
            'message' => 'Cleaner deleted successfully',
        ]);
    }

    public function bookingTarget(Request $request)
    {
        $vendor = Vendor::where('user_id', '=', Auth::id(), 'and')->first();
        if (! $vendor) {
            $bookingTarget = new Vendor;
            $bookingTarget->user_id = Auth::id();
            $bookingTarget->bookings_target = $request->bookings_target;
            $bookingTarget->save();

            return response()->json([
                'message' => 'Booking target set successfully',
                'vendor' => $bookingTarget,
            ], 201);
        } else {
            $vendor->bookings_target = $request->bookings_target;
            $vendor->save();

            return response()->json([
                'message' => 'Bookings target updated successfully',
                'vendor' => $vendor->bookings_target,
            ], 200);
        }
    }

    public function revenueTarget(Request $request)
    {
        $vendor = Vendor::where('user_id', '=', Auth::id(), 'and')->first();
        if (! $vendor) {
            $revenueTarget = new Vendor;
            $revenueTarget->user_id = Auth::id();
            $revenueTarget->revenue_target = $request->revenue_target;
            $revenueTarget->save();

            return response()->json([
                'message' => 'Revenue target set successfully',
                'vendor' => $revenueTarget,
            ], 201);
        } else {
            $vendor->revenue_target = $request->revenue_target;
            $vendor->save();

            return response()->json([
                'message' => 'Revenue target updated successfully',
                'vendor' => $vendor->revenue_target,
            ], 200);
        }
    }

    public function getTargets()
    {
        /** @var \App\Models\User $vendor */
        $vendor = Auth::user();
        $vendor_profile = Vendor::where('user_id', '=', $vendor->id, 'and')->first();
        $bookings_target = $vendor_profile->bookings_target ?? 0;
        $revenue_target = $vendor_profile->revenue_target ?? 0;

        return response()->json([
            'success' => true,
            'bookings_target' => $bookings_target,
            'revenue_target' => $revenue_target,
        ]);
    }

    public function totalEarnings()
    {
        $vendor = Auth::user();
        $total_amount = Transaction::where('vendor_id', '=', $vendor->id, 'and')->where('status', '=', 'paid', 'and')->sum('vendor_amount');

        return response()->json([
            'success' => true,
            'total_amount' => $total_amount,
        ]);
    }

    public function transactionHistory()
    {
        $vendor = Auth::user();
        $trasactions = Transaction::where('vendor_id', '=', $vendor->id, 'and')->orderBy('created_at', 'desc')->paginate(10);

        return response()->json([
            'success' => true,
            'transactions' => $trasactions,
        ]);

    }
    
    public function getDocumentStatus()
    {
        $user = Auth::user();
        
        // Check if the user is a vendor
        if ($user->role !== 'vendor') {
            return response()->json([
                'message' => 'Access denied. Only vendors can check document status.',
            ], 403);
        }
        
        // Find the vendor's documents
        $document = Document::where('user_id', '=', $user->id, 'and')->first();
        
        if ($document) {
            return response()->json([
                'has_documents' => true,
                'document' => $document,
                'message' => 'Documents found',
            ]);
        } else {
            return response()->json([
                'has_documents' => false,
                'message' => 'No documents uploaded',
            ]);
        }
    }

    public function uploadBusinessDocuments(Request $request)
    {
        try {
            $data = $request->validate([
                // 'user_id' => 'required|exists:users,id',
                'nid' => 'required|file|mimes:jpeg,png,jpg,pdf|max:10240', // National ID for vendors
                'pob' => 'required|file|mimes:jpeg,png,jpg,pdf|max:10240', // Proof of Business for vendors
            ]);
    
            $nid = $this->fileUploadService->uploadFile($request->file('nid'), 'documents/nid');
            $pob = $this->fileUploadService->uploadFile($request->file('pob'), 'documents/pob');
    
            $document = new Document;
            $document->fill([
                'user_id' => Auth::id(),
                'nid' => $nid,
                'pob' => $pob,
            ]);
            $document->save();
    
            return $this->successResponse($document, 'Business documents uploaded successfully', 201);
        } catch (\Illuminate\Validation\ValidationException $e) {
            return $this->errorResponse('Validation failed: ' . $e->getMessage(), 422, $e->errors());
        } catch (\Exception $e) {
            return $this->errorResponse('Upload failed: ' . $e->getMessage(), 500);
        }
    }
}
