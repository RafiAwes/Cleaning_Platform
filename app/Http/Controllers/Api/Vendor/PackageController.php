<?php

namespace App\Http\Controllers\Api\Vendor;

use App\Http\Controllers\Controller;
use App\Models\{Package, Service};
use Illuminate\Http\Request;
use Illuminate\Support\Facades\{Auth, File};
use Illuminate\Support\Str;

class PackageController extends Controller
{
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
}
