<?php

namespace App\Http\Controllers\Api\Admin;

use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use App\Models\User;
use App\Models\Vendor;
use App\Models\Addon;

class AdminController extends Controller
{
    public function dashboard()
    {
        return "Admin Dashboard";
    }
    
    public function getPendingVendors()
    {
        $pendingVendors = Vendor::where('approval_status', 'pending')->with('user')->get();
        
        return response()->json([
            'message' => 'Pending vendors retrieved successfully',
            'vendors' => $pendingVendors
        ], 200);
    }
    
    public function approveVendor(Request $request, $vendorId)
    {
        $vendor = Vendor::find($vendorId);
        
        if (!$vendor) {
            return response()->json([
                'message' => 'Vendor not found'
            ], 404);
        }
        
        if ($vendor->approval_status !== 'pending') {
            return response()->json([
                'message' => 'Vendor is already ' . $vendor->approval_status
            ], 400);
        }
        
        $vendor->approval_status = 'approved';
        $vendor->save();
        
        return response()->json([
            'message' => 'Vendor approved successfully',
            'vendor' => $vendor
        ], 200);
    }
    
    public function rejectVendor(Request $request, $vendorId)
    {
        $vendor = Vendor::find($vendorId);
        
        if (!$vendor) {
            return response()->json([
                'message' => 'Vendor not found'
            ], 404);
        }
        
        if ($vendor->approval_status !== 'pending') {
            return response()->json([
                'message' => 'Vendor is already ' . $vendor->approval_status
            ], 400);
        }
        
        $vendor->approval_status = 'rejected';
        $vendor->save();
        
        return response()->json([
            'message' => 'Vendor rejected successfully',
            'vendor' => $vendor
        ], 200);
    }
}