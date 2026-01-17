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

    // ... existing methods ...

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
}
