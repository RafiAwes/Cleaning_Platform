<?php

namespace App\Http\Controllers\Api;

use App\Models\Category;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use App\Traits\ApiResponseTrait;

class categoryController extends Controller
{
    public function createCategory(Request $request){
        $category = new Category();
        $category->name = $request->name;
        $category->save();
        return response()->json($category);
    }

    public function editCategory(Request $request, Category $category){
        $data = $request->validate([
            'name' => 'required|string|max:255',
        ]);

        $category->update($data);
        
        return response()->json($category);
    }

    public function deleteCategory($category_id){
        $category = Category::find($category_id);
        $category->delete();
        return response()->json($category);
    }

    public function categoryList(){
        $category = Category::all();
        return response()->json($category);
    }
}
