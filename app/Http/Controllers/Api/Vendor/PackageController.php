<?php

namespace App\Http\Controllers\Api\Vendor;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\{Auth, File};
use Illuminate\Support\Str;
use App\Http\Controllers\Controller;
use App\Models\{Package, Service};
use App\Services\PackageService;
use App\Traits\ApiResponseTrait;

class PackageController extends Controller
{
    use ApiResponseTrait;
    protected $packageService;

    public function __construct(PackageService $packageService)
    {
        $this->packageService = $packageService;
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
        if (! Auth::check()) {
            return response()->json([
                'message' => 'Unauthenticated. Please login to continue.',
            ], 401);
        }

        $validated = $request->validate([
            'title' => 'required|string|max:255',
            'description' => 'required|string',
            'price' => 'required|numeric',
            'image' => 'nullable|image|max:4096',

            // Nested Array Validation
            'services' => 'required|array|min:1',
            'services.*.title' => 'required|string',
            'services.*.description' => 'nullable|string',
            'services.*.price' => 'nullable|numeric',

            'addons' => 'nullable|array',
            'addons.*.addon_id' => 'required|exists:addons,id',
            'addons.*.price' => 'required|numeric',
        ]);

        try {
            $package = $this->packageService->createPackage(Auth::user(), $validated, $request->file('image'));

            return $this->successResponse($package, 'Package created successfully!', 201);
        } catch (\RuntimeException $e) {
            return $this->errorResponse($e->getMessage(), 400);
        } catch (\Exception $e) {
            return $this->errorResponse('Error creating package: '.$e->getMessage(), 500);
        }
    }

    public function updatePackage(Request $request, Package $package)
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
                'message' => 'Access denied. Only vendors can update packages.',
            ], 403);
        }

        if ($package->vendor_id !== $user->id) {
            return response()->json([
                'message' => 'Access denied. You do not have permission to update this package.',
            ], 403);
        }

        $validated = $request->validate([
            'title' => 'nullable|string|max:255',
            'description' => 'nullable|string',
            'price' => 'nullable|numeric',
            'image' => 'nullable|image|max:4096',
            'status' => 'nullable|string|in:active,inactive',

            // Nested Array Validation
            'services' => 'nullable|array',
            'services.*.title' => 'required|string',
            'services.*.description' => 'nullable|string',
            'services.*.price' => 'nullable|numeric',

            'addons' => 'nullable|array',
            'addons.*.addon_id' => 'required|exists:addons,id',
            'addons.*.price' => 'required|numeric',
        ]);

        try {
            $package = $this->packageService->updatePackage($package, $validated, $request->file('image'));

            return $this->successResponse($package, 'Package updated successfully!', 200);
        } catch (\RuntimeException $e) {
            return $this->errorResponse($e->getMessage(), 400);
        } catch (\Exception $e) {
            return $this->errorResponse('Error updating package: '.$e->getMessage(), 500);
        }
    }

    public function deletePackage(Package $package)
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
                'message' => 'Access denied. Only vendors can delete packages.',
            ], 403);
        }

        if ($package->vendor_id !== $user->id) {
            return response()->json([
                'message' => 'Access denied. You do not have permission to delete this package.',
            ], 403);
        }

        try {
            $this->packageService->deletePackage($package);

            return $this->successResponse(null, 'Package deleted successfully!', 200);
        } catch (\Exception $e) {
            return $this->errorResponse('Error deleting package: '.$e->getMessage(), 500);
        }
    }

    public function getPackagePublic($id)
    {
        try {
            $package = Package::with(['services', 'addons', 'vendor'])
                ->findOrFail($id);

            return $this->successResponse($package, 'Package retrieved successfully', 200);
        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            return $this->errorResponse('Package not found', 404);
        } catch (\Exception $e) {
            return $this->errorResponse('Error retrieving package: '.$e->getMessage(), 500);
        }
    }

    public function getAllPackagesPublic(Request $request)
    {
        try {
            $query = Package::with(['services', 'addons', 'vendor'])
                ->latest('id');

            // Unified search across package title, service title, and vendor name
            if ($request->filled('search')) {
                $search = $request->input('search');
                $query->where(function($q) use ($search) {
                    $q->where('packages.title', 'like', "%{$search}%")
                      ->orWhere('packages.description', 'like', "%{$search}%")
                      ->orWhereHas('services', function($subQ) use ($search) {
                          $subQ->where('title', 'like', "%{$search}%");
                      })
                      ->orWhereHas('vendor', function($subQ) use ($search) {
                          $subQ->where('name', 'like', "%{$search}%");
                      });
                });
            }

            // Sorting options
            if ($request->filled('sort_by')) {
                $sortBy = $request->input('sort_by');
                switch ($sortBy) {
                    case 'highest_price':
                        $query->orderBy('price', 'desc');
                        break;
                    case 'lowest_price':
                        $query->orderBy('price', 'asc');
                        break;
                    case 'highest_rating':
                        $query->orderBy('rating', 'desc');
                        break;
                    case 'lowest_rating':
                        $query->orderBy('rating', 'asc');
                        break;
                    default:
                        $query->latest('id');
                }
            }

            // Pagination
            $perPage = $request->input('per_page', 12);
            $packages = $query->paginate($perPage);

            return $this->successResponse($packages, 'Packages retrieved successfully', 200);
        } catch (\Exception $e) {
            return $this->errorResponse('Error retrieving packages: '.$e->getMessage(), 500);
        }
    }

    /**
     * Get other packages from a specific vendor (excluding a package)
     */
    public function getVendorPackages($vendorId, Request $request)
    {
        try {
            $excludeId = $request->input('exclude_id');

            $query = Package::where('vendor_id', $vendorId)
                ->with(['services', 'addons', 'vendor']);

            if ($excludeId) {
                $query->where('id', '!=', $excludeId);
            }

            $perPage = $request->input('per_page', 4);
            $packages = $query->paginate($perPage);

            return $this->successResponse($packages, 'Vendor packages retrieved successfully', 200);
        } catch (\Exception $e) {
            return $this->errorResponse('Error retrieving vendor packages: '.$e->getMessage(), 500);
        }
    }

    /**
     * Get random packages for recommendations
     */
    public function getRandomPackages(Request $request)
    {
        try {
            $limit = $request->input('limit', 5);
            $excludeId = $request->input('exclude_id');

            $query = Package::with(['services', 'addons', 'vendor']);

            if ($excludeId) {
                $query->where('id', '!=', $excludeId);
            }

            $packages = $query->inRandomOrder()->limit($limit)->get();

            return $this->successResponse([
                'data' => $packages,
                'count' => count($packages)
            ], 'Random packages retrieved successfully', 200);
        } catch (\Exception $e) {
            return $this->errorResponse('Error retrieving random packages: '.$e->getMessage(), 500);
        }
    }
}

