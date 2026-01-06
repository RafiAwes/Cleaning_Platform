<?php

namespace App\Http\Controllers\Api\Vendor;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\{Auth, File};
use Illuminate\Support\Str;
use App\Http\Controllers\Controller;
use App\Models\{Cleaner, Document, Package, Service, Transaction, User, Vendor};
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

    public function dashboard()
    {
        return 'Vendor Dashboard';
    }

    public function updateOrCreate(Request $request)
    {
        /** @var \App\Models\User $currentUser */
        $currentUser = Auth::user();
        $userId = $currentUser->id;
        $vendor = Vendor::where('user_id', $userId)->first();

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

        $user->update($validated);

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

        $vendor = Vendor::where('user_id', $currentUser->id)->first();

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

    public function packages()
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
                'message' => 'Access denied. Only vendors can view packages.',
            ], 403);
        }

        // Get packages for this vendor
        $packages = Package::where('vendor_id', $user->id)->with(['services', 'addons'])->get();

        return response()->json([
            'message' => 'Packages retrieved successfully',
            'packages' => $packages,
        ], 200);
    }

    public function CreatePackage(Request $request)
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
                'message' => 'Access denied. Only vendors can create packages.',
            ], 403);
        }

        $data = $request->validate([
            'title' => 'required|string',
            'description' => 'required|string',
            'price' => 'required|numeric',
            'image' => 'image',
            'services' => 'required|array',
            'services.*.title' => 'required|string',
            // 'services.*.price' => 'required|numeric',
            'addons' => 'array',
            'addons.*.addon_id' => 'exists:addons,id',
            'addons.*.price' => 'numeric',
        ]);

        // create image direcatory if not exists
        if (! File::exists(public_path('images/packages'))) {
            File::makeDirectory(public_path('images/packages'), 0755, true);
        }
        // Handle image upload
        $imageName = null;
        if ($request->hasFile('image')) {
            $imageName = time().'_'.Str::random(10).'.'.$request->image->getClientOriginalExtension();
            $request->image->move(public_path('images/packages'), $imageName);
        }

        $package = new Package;
        $package->vendor_id = $user->id;
        if ($imageName) {
            $package->image = $imageName;
        }
        $package->title = $data['title'];
        $package->description = $data['description'];
        $package->price = $data['price'];
        $package->save();

        // Process services
        foreach ($data['services'] as $serviceData) {
            $service = new Service;
            $service->package_id = $package->id;
            $service->title = $serviceData['title'];
            $service->save();

        }

        // Process addons
        if (! empty($data['addons'])) {
            $pivotData = [];

            foreach ($data['addons'] as $addon) {
                $pivotData[$addon['addon_id']] = ['price' => $addon['price']];
            }

            $package->addons()->sync($pivotData);
        }

        return response()->json([
            'message' => 'Package created successfully',
            'package' => $package,
        ], 201);
    }

    public function updatePackage(Request $request, Package $package)
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
                'message' => 'Access denied. Only vendors can update packages.',
            ], 403);
        }

        if ($package->vendor_id !== $user->id) {
            return response()->json([
                'message' => 'Access denied. You do not have permission to update this package.',
            ], 403);
        }

        $data = $request->validate([
            'title' => 'string',
            'description' => 'string',
            'price' => 'numeric',
            'image' => 'image',
            'services' => 'array',
            'services.*.id' => 'nullable|exists:services,id',
            'services.*.title' => 'string',
            'addons' => 'array',
            'addons.*.addon_id' => 'nullable|exists:addons,id',
            'addons.*.price' => 'numeric',
        ]);

        $package->update($data);

        if (! empty($data['services'])) {
            foreach ($data['services'] as $serviceData) {
                if (! empty($serviceData['id'])) {
                    $service = Service::find($serviceData['id']);
                    // Handle image upload for service if provided
                    if (! empty($serviceData['image'])) {
                        $imagePath = $request->file('image')->store('services', 'public');
                        $service->image = $imagePath;
                        $service->save();
                    }
                } else {
                    // Create new service
                    $service = new Service;
                    $service->package_id = $package->id;
                    $service->title = $serviceData['title'];
                    // Handle image upload for new service if provided
                    if (! empty($serviceData['image'])) {
                        $imagePath = $request->file('image')->store('services', 'public');
                        $service->image = $imagePath;
                    }
                    $service->save();
                }
            }
        }

        // Process addons
        if (! empty($data['addons'])) {
            $pivotData = [];

            foreach ($data['addons'] as $addon) {
                $pivotData[$addon['addon_id']] = ['price' => $addon['price']];
            }

            $package->addons()->sync($pivotData);
        }

        return response()->json([
            'message' => 'Package updated successfully',
            'package' => $package,
        ], 200);
    }

    public function deletePackage(Package $package)
    {
        /** @var \App\Models\User $user */
        $user = Auth::user();

        if ($package->vendor_id !== $user->id) {
            return response()->json([
                'message' => 'Access denied. You do not have permission to delete this package.',
            ], 403);
        }
        $package->delete();

        return response()->json([
            'message' => 'Package deleted successfully',
        ], 200);

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
            'image' => 'nullable|image',
            'status' => 'required|in:active,assigned,completed',
        ]);

        $cleaner = new Cleaner;
        $cleaner->vendor_id = $user->id;
        $cleaner->name = $data['name'];
        $cleaner->phone = $data['phone'];
        $cleaner->status = $data['status'];

        // Handle image upload
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
        $cleaners = Cleaner::where('vendor_id', Auth::id())->get();

        return response()->json([
            'message' => 'Cleaners retrieved successfully',
            'cleaners' => $cleaners,
        ]);
    }

    public function bookingTarget(Request $request)
    {
        $vendor = Vendor::where('user_id', Auth::id())->first();
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
            $vendor->update([
                'bookings_target' => $request->bookings_target,
            ]);

            return response()->json([
                'message' => 'Bookings target updated successfully',
                'vendor' => $vendor->bookings_target,
            ], 200);
        }
    }

    public function revenueTarget(Request $request)
    {
        $vendor = Vendor::where('user_id', Auth::id())->first();
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
            $vendor->update([
                'revenue_target' => $request->revenue_target,
            ]);

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
        $vendor_profile = Vendor::where('user_id', $vendor->id)->first();
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
        $total_amount = Transaction::where('vendor_id', $vendor->id)->where('status', 'paid')->sum('vendor_amount');

        return response()->json([
            'success' => true,
            'total_amount' => $total_amount,
        ]);
    }

    public function transactionHistory()
    {
        $vendor = Auth::user();
        $trasactions = Transaction::where('vendor_id', $vendor->id)->orderBy('created_at', 'desc')->paginate(10);

        return response()->json([
            'success' => true,
            'transactions' => $trasactions,
        ]);

    }

    public function uploadBusinessDocuments(Request $request)
    {
        $data = $request->validate([
            // 'user_id' => 'required|exists:users,id',
            'nid' => 'required', // National ID for vendors
            'pob' => 'required', // Proof of Business for vendors
        ]);

        $nid = $this->fileUploadService->uploadFile($request->file('nid'), 'documents/nid');
        $pob = $this->fileUploadService->uploadFile($request->file('pob'), 'documents/pob');

        $document = new Document;
        $document->user_id = Auth::id();
        $document->nid = $nid;
        $document->pob = $pob;
        $document->save();

        return $this->successResponse($document, 'Business documents uploaded successfully', 201);
    }
}
