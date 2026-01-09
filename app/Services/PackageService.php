<?php

namespace App\Services;

use App\Models\{Package, User};
use App\Services\FileUploadService;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\{DB, File};

class PackageService
{
    private FileUploadService $fileUploadService;

    public function __construct(FileUploadService $fileUploadService)
    {
        $this->fileUploadService = $fileUploadService;
    }

    public function createPackage(User $vendor, array $data, ?UploadedFile $image = null): Package
    {
        return DB::transaction(function () use ($vendor, $data, $image) {
            $image = $this->fileUploadService->uploadFile($image, 'images/packages');
            $package = new Package();
            $package->vendor_id = $vendor->id;
            $package->title = $data['title'];
            $package->description = $data['description'];
            $package->image = $image;
            $package->price = $data['price'];
            $package->status = $data['status'] ?? 'active';            
            $package->save();

            // Process services
            if(!empty($data['services'])){
                $serviceData = array_map(function ($service) {
                    return [
                        'title' => $service['title'],
                        'description' => $service['description'] ?? null,
                        'price' => $service['price'] ?? 0,
                        'status' => $service['status'] ?? 'active'
                    ];
                }, $data['services']);

                $package->services()->createMany($serviceData);
            }

            if(!empty($data['addons'])){
                $pivotData = [];

                foreach ($data['addons'] as $addon) {
                    $pivotData[$addon['addon_id']] = ['price' => $addon['price']];
                }

                $package->addons()->sync($pivotData);
            }

            return $package->load(['services', 'addons']);
        });
    }

    public function updatePackage(Package $package, array $data, ?UploadedFile $image = null): Package
    {
        return DB::transaction(function () use ($package, $data, $image) {
            // Upload new image if provided
            if ($image) {
                // Delete old image if exists
                if ($package->image) {
                    $this->fileUploadService->deleteFile($package->image);
                }
                $package->image = $this->fileUploadService->uploadFile($image, 'images/packages');
            }

            // Update package details only if fields are provided
            if (isset($data['title']) && !empty($data['title'])) {
                $package->title = $data['title'];
            }
            if (isset($data['description']) && !empty($data['description'])) {
                $package->description = $data['description'];
            }
            if (isset($data['price']) && !empty($data['price'])) {
                $package->price = $data['price'];
            }
            if (isset($data['status']) && !empty($data['status'])) {
                $package->status = $data['status'];
            }
            $package->save();

            // Process services if provided
            if (isset($data['services'])) {
                // Delete existing services
                $package->services()->delete();

                // Create new services
                $serviceData = array_map(function ($service) {
                    return [
                        'title' => $service['title'],
                        'description' => $service['description'] ?? null,
                        'price' => $service['price'] ?? 0,
                        'status' => $service['status'] ?? 'active'
                    ];
                }, $data['services']);

                $package->services()->createMany($serviceData);
            }

            // Process addons if provided
            if (isset($data['addons'])) {
                $pivotData = [];

                foreach ($data['addons'] as $addon) {
                    $pivotData[$addon['addon_id']] = ['price' => $addon['price']];
                }

                $package->addons()->sync($pivotData);
            }

            return $package->load(['services', 'addons']);
        });
    }

    public function deletePackage(Package $package): bool
    {
        return DB::transaction(function () use ($package) {
            // Delete package image if exists
            if ($package->image) {
                $this->fileUploadService->deleteFile($package->image);
            }

            // Delete associated services
            $package->services()->delete();

            // Detach addons
            $package->addons()->detach();

            // Delete the package
            return $package->delete();
        });
    }
}