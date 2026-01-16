<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\CustomPrice;
use App\Models\Service;
use Illuminate\Support\Facades\DB;
use App\Traits\ApiResponseTrait;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class ServiceController extends Controller
{
    use ApiResponseTrait;
    public function createCustomPrice(Request $request)
    {
        $data = $request->validate([
            '*.custom_category_id' => 'required|exists:custom_categories,id',
            '*.price' => 'required|numeric',
        ]);
        $user = Auth::user();

        if ($user->role !== 'vendor') 
        {
            return $this->errorResponse('Unauthorized - Only vendors can create custom services', 401);
        }

        $dataToInsert = [];
        $now = now();

        foreach ($data as $item)
        {
            $dataToInsert[] = [
                'vendor_id' => $user->id,
                'custom_category_id' => $item['custom_category_id'],
                'price' => $item['price'],
                'created_at' => $now,
                'updated_at' => $now,
            ];
        }

        DB::table('custom_prices')->insert($dataToInsert);
       
        return $this->successResponse($dataToInsert, 'Custom services created successfully', 201);
    }

    public function customPriceList()
    {
        $user = Auth::user();

        if ($user->role !== 'vendor') 
        {
            return $this->errorResponse('Unauthorized - Only vendors can view custom services', 401);
        }

        $customPrices = CustomPrice::where('vendor_id', '=', $user->id, 'and')->get();

        return $this->successResponse($customPrices, 'Custom services retrieved successfully', 200);
    }
}
