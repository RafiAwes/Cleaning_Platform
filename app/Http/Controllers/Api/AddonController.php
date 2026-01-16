<?php

namespace App\Http\Controllers\Api;

use App\Models\Addon;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Auth;

class AddonController extends Controller
{
    public function createAddon(Request $request)
    {
        // Check if user is authenticated
        if (!Auth::check()) {
            return response()->json([
                'message' => 'Unauthenticated.'
            ], 401);
        }

        /** @var \App\Models\User $user */
        $user = Auth::user();
        
        if ($user->role !== 'admin') {
            return response()->json([
                'message' => 'Access denied. You do not have permission to create an addon.'
            ], 403);
        }
        
        $request->validate([
            'name' => 'required|string',
        ]);

        $addon = Addon::create([
            'title' => $request->name,
        ]);

        return response()->json([
            'message' => 'Addon created successfully',
            'addon' => $addon
        ], 201);
    }

    public function getAddons()
    {
        $addons = Addon::all();
        return response()->json([
            'message' => 'Addons retrieved successfully',
            'addons' => $addons
        ], 200);
    }

    public function updateAddon(Request $request, Addon $addon)
    {
        // Check if user is authenticated
        if (!Auth::check()) {
            return response()->json([
                'message' => 'Unauthenticated.'
            ], 401);
        }

        $request->validate([
            'name' => 'required|string',
        ]);

        Addon::where('id', '=', $addon->id, 'and')->update([
            'title' => $request->name,
        ]);

        return response()->json([
            'message' => 'Addon updated successfully',
            'addon' => $addon
        ], 200);
    }

    public function deleteAddon(Addon $addon)
    {
        // Check if user is authenticated
        if (!Auth::check()) {
            return response()->json([
                'message' => 'Unauthenticated.'
            ], 401);
        }

        Addon::where('id', '=', $addon->id, 'and')->delete();
        return response()->json([
            'message' => 'Addon deleted successfully'
        ], 200);
    }
}