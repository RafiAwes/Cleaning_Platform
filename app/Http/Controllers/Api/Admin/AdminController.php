<?php

namespace App\Http\Controllers\Api\Admin;

use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use App\Models\User;
use App\Models\Vendor;
use App\Models\Document;
use App\Models\Addon;

class AdminController extends Controller
{
    public function dashboard()
    {
        return "Admin Dashboard";
    }
    
    public function getPendingVendors()
    {
        $pendingVendors = Vendor::where('approval_status', 'pending')
            ->with(['user', 'categories', 'documents'])
            ->get();
        
        // Enhance each vendor with document information
        foreach ($pendingVendors as $vendor) {
            if ($vendor->documents) {
                $vendor->documents_info = [
                    'nid_url' => $vendor->documents->nid,
                    'pob_url' => $vendor->documents->pob,
                    'uploaded_at' => $vendor->documents->created_at
                ];
            } else {
                $vendor->documents_info = null;
            }
        }
        
        return response()->json([
            'message' => 'Pending vendors retrieved successfully',
            'vendors' => $pendingVendors
        ], 200);
    }
    
    public function getAllVendors()
    {
        $vendors = Vendor::with(['user', 'categories', 'documents'])->get();
        
        // Enhance each vendor with document information
        foreach ($vendors as $vendor) {
            if ($vendor->documents) {
                $vendor->documents_info = [
                    'nid_url' => $vendor->documents->nid,
                    'pob_url' => $vendor->documents->pob,
                    'uploaded_at' => $vendor->documents->created_at
                ];
            } else {
                $vendor->documents_info = null;
            }
        }
        
        return response()->json([
            'message' => 'Vendors retrieved successfully',
            'vendors' => $vendors
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