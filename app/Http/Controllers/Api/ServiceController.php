<?php

namespace App\Http\Controllers\Api;

use App\Models\CustomPrice;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Auth;

class ServiceController extends Controller
{
    public function createCustomPrice(Request $request){
        $data = $request->validate([
            'user_id'=>'required|exists:users,id',
            'custom_category_id' =>'required|exists:custom_categories,id',
            'price'=>'required|numeric'
        ]);
        $user = Auth::user();
        if($user->type() == 'vendor'){
             $custom_price = CustomPrice::create([
            'custom_category_id'=>$data['custom_category_id'],
            'price'=>$data['price']
        ]);

        return response()->json([
            'message' => 'Custom price created successfully',
            'custom_price' => $custom_price
        ], 201);
        }

        return response()->json([
            'message' => 'Unauthorized'
        ], 401);
    }
}
    