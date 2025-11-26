<?php

namespace App\Http\Controllers\Api\Vendor;

use App\Models\User;
use App\Models\Addon;
use App\Models\Vendor;
use App\Models\Package;
use App\Models\Service;
use App\Models\Booking;
use App\Models\Cleaner;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use App\Http\Controllers\Controller;

class VendorController extends Controller
{
    public function dashboard()
    {
        return "Vendor Dashboard";
    }

    public function update(Request $request)
    {
        /** @var \App\Models\User $currentUser */
        $currentUser = Auth::user();
        $userId = $currentUser->id;
        
        $validated = $request->validate([
            "name" => "sometimes|string",
            "email" => "sometimes|email|unique:users,email," . $userId,
            "phone" => "sometimes|string",
            "about" => "sometimes|string",
            "address" => "sometimes|string",
            "profile_image" => "nullable|file",
            'start_time' => 'sometimes|string',
            'end_time' => 'sometimes|string',
        ]);

        /** @var \App\Models\User $user */
        $user = Auth::user();

        // Upload profile image
        if (!empty($validated["profile_image"])) {
            $validated["profile_image"] =
                $validated["profile_image"]->store("vendors", "public");
        }

        $user->update($validated);

        return response()->json([
            "message" => "Vendor profile updated successfully",
            "vendor" => $user
        ]);
    }


    public function packages()
    {
        // Check if user is authenticated
        if (!Auth::check()) {
            return response()->json([
                'message' => 'Unauthenticated. Please login to continue.'
            ], 401);
        }
        
        // Check if authenticated user has vendor role
        /** @var \App\Models\User $user */
        $user = Auth::user();
        
        if ($user->role !== 'vendor') {
            return response()->json([
                'message' => 'Access denied. Only vendors can view packages.'
            ], 403);
        }
        
        // Get packages for this vendor
        $packages = Package::where('vendor_id', $user->id)->with(['services', 'addons'])->get();
        
        return response()->json([
            'message' => 'Packages retrieved successfully',
            'packages' => $packages
        ], 200);
    }

    public function CreatePackage(Request $request)
    {
        // Check if user is authenticated
        if (!Auth::check()) {
            return response()->json([
                'message' => 'Unauthenticated. Please login to continue.'
            ], 401);
        }
        
        // Check if authenticated user has vendor role
        /** @var \App\Models\User $user */
        $user = Auth::user();
        
        if ($user->role !== 'vendor') {
            return response()->json([
                'message' => 'Access denied. Only vendors can create packages.'
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
        
        $package = new Package();
        $package->vendor_id = $user->id;
        $package->title = $data['title'];
        $package->description = $data['description'];
        $package->price = $data['price'];
        $package->save();

        // Handle image upload
        if ($request->hasFile('image')) {
            $imagePath = $request->file('image')->store('packages', 'public');
            $package->image = $imagePath;
            $package->save();
        }

        // Process services
        foreach ($data['services'] as $serviceData) {
           $service = new Service();
           $service->package_id = $package->id;
           $service->title = $serviceData['title'];
           $service->save();

        }

        // Process addons
        if(!empty($data['addons']))
        {
            $pivotData = [];

            foreach ($data['addons'] as $addon)
            {
                $pivotData[$addon['addon_id']] = ['price' => $addon['price']];
            }

            $package->addons()->sync($pivotData);
        }

        return response()->json([
            'message' => 'Package created successfully',
            'package' => $package
        ], 201);
    }

    public function updatePackage(Request $request, Package $package)
    {
        // Check if user is authenticated
        if (!Auth::check()) {
            return response()->json([
                'message' => 'Unauthenticated. Please login to continue.'
            ], 401);
        }
        
        // Check if authenticated user has vendor role
        /** @var \App\Models\User $user */
        $user = Auth::user();
        
        if ($user->role !== 'vendor') {
            return response()->json([
                'message' => 'Access denied. Only vendors can update packages.'
            ], 403);
        }
        
        if ($package->vendor_id !== $user->id) {
            return response()->json([
                'message' => 'Access denied. You do not have permission to update this package.'
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
        
        if (!empty($data['services'])) {
            foreach ($data['services'] as $serviceData) {
                if (!empty($serviceData['id'])) {
                    $service = Service::find($serviceData['id']);
                    // Handle image upload for service if provided
                    if (!empty($serviceData['image'])) {
                        $imagePath = $request->file('image')->store('services', 'public');
                        $service->image = $imagePath;
                        $service->save();
                    }
                } else {
                    // Create new service
                    $service = new Service();
                    $service->package_id = $package->id;
                    $service->title = $serviceData['title'];
                    // Handle image upload for new service if provided
                    if (!empty($serviceData['image'])) {
                        $imagePath = $request->file('image')->store('services', 'public');
                        $service->image = $imagePath;
                    }
                    $service->save();
                }
            }
        }
        
        // Process addons
        if (!empty($data['addons'])) {
            $pivotData = [];
            
            foreach ($data['addons'] as $addon) {
                $pivotData[$addon['addon_id']] = ['price' => $addon['price']];
            }
            
            $package->addons()->sync($pivotData);
        }
        
        return response()->json([
            'message' => 'Package updated successfully',
            'package' => $package
        ], 200);
    }

    public function deletePackage(Package $package)
    {
        /** @var \App\Models\User $user */
        $user = Auth::user();

        if ($package->vendor_id !== $user->id) {
            return response()->json([
                'message' => 'Access denied. You do not have permission to delete this package.'
            ], 403);
        }
        $package->delete();
        return response()->json([
            'message' => 'Package deleted successfully'
        ], 200);
        
    }

    public function addCleaner(Request $request)
    {
        // Check if user is authenticated
        if (!Auth::check()) {
            return response()->json([
                'message' => 'Unauthenticated. Please login to continue.'
            ], 401);
        }
        
        // Check if authenticated user has vendor role
        /** @var \App\Models\User $user */
        $user = Auth::user();
        
        if ($user->role !== 'vendor') {
            return response()->json([
                'message' => 'Access denied. Only vendors can add cleaners.'
            ], 403);
        }
        
        $data = $request->validate([
            'name' => 'required|string',
            'phone' => 'required|string',
            'image' => 'nullable|image',
            'status' => 'required|in:active,assigned,completed'
        ]);

        $cleaner = new Cleaner();
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
            'cleaner' => $cleaner
        ], 201);
    }

    public function getCleaners()
    {
        $cleaners = Cleaner::where('vendor_id', Auth::id())->get();
        return response()->json([
            'message' => 'Cleaners retrieved successfully',
            'cleaners' => $cleaners
        ]);
    }

    public function bookingTarget(Request $request)
    {
        $vendor = Vendor::where('user_id', Auth::id())->firstorFail();
        if (!$vendor) {
            $bookingTarget = new Vendor();
            $bookingTarget->user_id = Auth::id();
            $bookingTarget->bookings_target = $request->bookings_target;
            $bookingTarget->save();
            return response()->json([
                'message' => 'Booking target set successfully',
                'vendor' => $bookingTarget
            ], 201);
        } else {
            $vendor->update([
                'bookings_target' => $request->bookings_target
            ]);
            return response()->json([
                'message' => 'Bookings target updated successfully',
                'vendor' => $vendor->bookings_target
            ], 200);
        }   
    }

    public function revenueTarget(Request $request)
    {
        $vendor = Vendor::where('user_id', Auth::id())->firstorFail();
        if (!$vendor) {
            $revenueTarget = new Vendor();
            $revenueTarget->user_id = Auth::id();
            $revenueTarget->revenue_target = $request->revenue_target;
            $revenueTarget->save();
            return response()->json([
                'message' => 'Revenue target set successfully',
                'vendor' => $revenueTarget
            ], 201);
        } else {
            $vendor->update([
                'revenue_target' => $request->revenue_target
            ]);
            return response()->json([
                'message' => 'Revenue target updated successfully',
                'vendor' => $vendor->revenue_target
            ], 200);
        }   
    }
}