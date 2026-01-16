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
        $packages = Package::where('vendor_id', '=', $user->id, 'and')->with(['services', 'addons'])->get();

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

        // Normalize JSON strings coming from FormData (services, addons)
        $payload = $request->all();
        if (isset($payload['services']) && is_string($payload['services'])) {
            $decoded = json_decode($payload['services'], true);
            $payload['services'] = is_array($decoded) ? $decoded : [];
        }
        if (isset($payload['addons']) && is_string($payload['addons'])) {
            $decoded = json_decode($payload['addons'], true);
            $payload['addons'] = is_array($decoded) ? $decoded : [];
        }
        $request->replace($payload);

        $validated = $request->validate([
            'title' => 'required|string|max:255',
            'description' => 'required|string',
            'price' => 'required|numeric',
            'image' => 'nullable|image|max:4096',

            // Nested Array Validation - services now only require title
            'services' => 'required|array|min:1',
            'services.*.title' => 'required|string|max:255',

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

        // Normalize JSON strings coming from FormData (services, addons)
        $payload = $request->all();
        if (isset($payload['services']) && is_string($payload['services'])) {
            $decoded = json_decode($payload['services'], true);
            $payload['services'] = is_array($decoded) ? $decoded : [];
        }
        if (isset($payload['addons']) && is_string($payload['addons'])) {
            $decoded = json_decode($payload['addons'], true);
            $payload['addons'] = is_array($decoded) ? $decoded : [];
        }
        $request->replace($payload);

        $validated = $request->validate([
            'title' => 'nullable|string|max:255',
            'description' => 'nullable|string',
            'price' => 'nullable|numeric',
            'image' => 'nullable|image|max:4096',
            'status' => 'nullable|string|in:active,inactive',

            // Nested Array Validation - services now only require title
            'services' => 'nullable|array',
            'services.*.title' => 'required|string|max:255',

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
}
