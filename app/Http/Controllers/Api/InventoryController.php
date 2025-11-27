<?php

namespace App\Http\Controllers\Api;

use App\Models\User;
use App\Models\Inventory;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use App\Http\Controllers\Controller;

class InventoryController extends Controller
{
    const PRODUCT_IMAGE_PATH = 'image/products';
    const DEFAULT_IMAGE_PATH = self::PRODUCT_IMAGE_PATH . '/defaultimage.png';
    
    private function storeProductImage($image)
    {
        // Create directory if it doesn't exist
        $directory = public_path(self::PRODUCT_IMAGE_PATH);
        if (!file_exists($directory)) {
            mkdir($directory, 0755, true);
        }

        // Generate unique filename
        $imageName = time() . '_' . uniqid() . '.' . $image->getClientOriginalExtension();
        
        // Move image to public/images/product folder
        $image->move($directory, $imageName);
        
        return self::PRODUCT_IMAGE_PATH . $imageName;
    }

    private function getStockStatus($quantity)
    {
        return match(true) {
            $quantity === 0 => "Out of stock",
            $quantity <= 19 => "Very low",
            $quantity <= 100 => "Medium",
            default => "Huge quantity"
        };
    }


    public function addProduct(Request $request)
    {
        $request->validate([
            'product_name' => 'required|string',
            'quantity' => 'required|integer',
            'image' => 'required|image|mimes:jpeg,png,jpg,gif,svg|max:2048',
        ]);


        $user = Auth::user();
        $image_path = $request->hasFile('image') ? $this->storeProductImage($request->image) : self::DEFAULT_IMAGE_PATH;
        if ($user->role == 'vendor') {
            $inventory = Inventory::create([
            'vendor_id' => Auth::id(),
            'product_name' => $request->product_name,
            'stock_status' => $this->getStockStatus($request->quantity),
            'image_path' => $image_path,
            'quantity' => $request->quantity,
            ]);
            return response()->json(["inventory" => $inventory], 201);
        } else {
            return response()->json(["message" => "You are not authorized to add products"], 403);
        }

    }

    public function productDetails($id)
    {
        $inventory = Inventory::findOrFail($id);
        return response()->json(["inventory" => $inventory], 200);
    }

    public function updateProduct(Request $request, $id)
    {
        $request->validate([
            'product_name' => 'required|string',
            'quantity' => 'required|integer',
            'image' => 'image|mimes:jpeg,png,jpg,gif,svg|max:2048',
        ]);

        $stock_status = $this->getStockStatus($request->quantity);
        
        $inventory = Inventory::findOrFail($id);
        $inventory->update([
            'product_name' => $request->product_name,
            'stock_status' => $stock_status,
            'image_path' => $request->hasFile('image') ? $this->storeProductImage($request->image) : $inventory->image_path,
            'stock_status' => $stock_status,
            'quantity' => $request->quantity,
        ]);
        return response()->json(["inventory" => $inventory], 200);
    }

    public function deleteProduct($id)
    {
        $inventory = Inventory::findOrFail($id);
        $inventory->delete();
        return response()->json(["message" => "Product deleted successfully"], 200);
    }

    public function getProductsByVendor()
    {
        $user = Auth::user();
        if ($user->role == 'vendor') {
            $inventory = Inventory::where('vendor_id', $user->id)->paginate(10);
            return response()->json(["inventory" => $inventory], 200);
        } else {
            return response()->json(["message" => "You are not authorized to view products"], 403);
        }

    }
    
}
