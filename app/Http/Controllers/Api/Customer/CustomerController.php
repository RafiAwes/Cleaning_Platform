<?php

namespace App\Http\Controllers\Api\Customer;

use App\Models\User;
use App\Models\Customer;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use App\Http\Controllers\Controller;

class CustomerController extends Controller
{
    const CUSTOMER_IMAGE_PATH = 'images/customers';
    const DEFAULT_IMAGE_PATH = self::CUSTOMER_IMAGE_PATH . '/default.png';
    
    private function storeCustomerImage($image)
    {
        // Create directory if it doesn't exist
        $directory = public_path(self::CUSTOMER_IMAGE_PATH);
        if (!file_exists($directory)) {
            mkdir($directory, 0755, true);
        }

        // Generate unique filename
        $imageName = time() . '_' . uniqid() . '.' . $image->getClientOriginalExtension();
        
        // Move image to public/images/customers folder
        $image->move($directory, $imageName);
        
        return self::CUSTOMER_IMAGE_PATH . '/' . $imageName;
    }

    public function dashboard()
    {
        return "Customer Dashboard";
    }
    
    public function updateProfile(Request $request)
    {
        /** @var \App\Models\User $currentUser */
        $currentUser = Auth::user();
        
        // Check if the user is a customer
        if ($currentUser->role !== 'customer') {
            return response()->json([
                'message' => 'Access denied. Only customers can update their profile.'
            ], 403);
        }
        
        $validated = $request->validate([
            "name" => "nullable|string|max:255",
            "email" => "nullable|email|unique:users,email," . $currentUser->id,
            "phone" => "nullable|string|max:20",
            "address" => "nullable|string",
            "profile_picture" => "nullable|image|mimes:jpeg,png,jpg,gif|max:2048",
        ]);
        
        // Check if customer profile exists, create if not
        $customer = Customer::where('user_id', $currentUser->id)->first();
        if (!$customer) {
            $customer = new Customer();
            $customer->user_id = $currentUser->id;
        }
        
        // Update user information
        $userData = [];
        if (!empty($validated["name"])) {
            $userData["name"] = $validated["name"];
        }
        if (!empty($validated["email"])) {
            $userData["email"] = $validated["email"];
        }
        if (!empty($validated["phone"])) {
            $userData["phone"] = $validated["phone"];
        }
        
        if (!empty($userData)) {
            $currentUser->update($userData);
        }
        
        // Update customer information
        $customerData = [];
        if (!empty($validated["address"])) {
            $customerData["address"] = $validated["address"];
        }
        
        // Handle profile picture upload
        if ($request->hasFile('profile_picture')) {
            $imagePath = $this->storeCustomerImage($request->file('profile_picture'));
            $customerData["image_path"] = $imagePath;
        }
        
        if (!empty($customerData)) {
            $customer->fill($customerData);
            $customer->save();
        }
        
        // Load the updated customer relationship
        $currentUser->load('customer');
        
        return response()->json([
            "message" => "Customer profile updated successfully",
            "user" => $currentUser
        ]);
    }
}