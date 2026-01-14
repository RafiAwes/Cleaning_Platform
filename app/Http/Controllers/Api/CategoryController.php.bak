<?php

namespace App\Http\Controllers\Api;

use Illuminate\Http\{JsonResponse, Request};
use App\Http\Controllers\Controller;
use App\Models\Category;
use App\Services\CategoryService;
use App\Traits\ApiResponseTrait;

class categoryController extends Controller
{
    use ApiResponseTrait;

    private CategoryService $categoryService;

    public function __construct(CategoryService $categoryService)
    {
        $this->categoryService = $categoryService;
    }

    public function createCategory(Request $request)
    {
        try {
            $validated = $request->validate([
                'name' => 'required|string|max:255',
                'image' => 'sometimes|image|mimes:jpeg,png,jpg,gif,svg|max:2048',
            ]);

            $category = $this->categoryService->createCategory($validated, $request->file('image'));

            return $this->successResponse($category, 'Category created successfully!', 201);
        } catch (\RuntimeException $e) {
            return $this->errorResponse($e->getMessage(), 400);
        } catch (\Exception $e) {
            return $this->errorResponse('Error creating category: '.$e->getMessage(), 500);
        }
    }

    public function editCategory(Request $request, $id)
    {
        try {
            $validated = $request->validate([
                'name' => 'sometimes|nullable|string|max:255',
                'image' => 'sometimes|nullable|image|mimes:jpeg,png,jpg,gif,svg|max:2048',
            ]);

            $category = Category::find($id);

            if (! $category) {
                return $this->errorResponse('Category not found', 404);
            }

            $category = $this->categoryService->updateCategory($category, $validated, $request->file('image'));

            return $this->successResponse($category, 'Category updated successfully!');
        } catch (\RuntimeException $e) {
            return $this->errorResponse($e->getMessage(), 400);
        } catch (\Exception $e) {
            return $this->errorResponse('Error updating category: '.$e->getMessage(), 500);
        }
    }

    public function deleteCategory($id)
    {
        try {
            $category = Category::find($id);

            if (! $category) {
                return $this->errorResponse('Category not found', 404);
            }

            $this->categoryService->deleteCategory($category);

            return $this->successResponse(null, 'Category deleted successfully!');
        } catch (\Exception $e) {
            return $this->errorResponse('Error deleting category: '.$e->getMessage(), 500);
        }
    }

    /**
     * Get all categories
     *
     * @return JsonResponse
     */
    public function categoryList(Request $request)
    {
        try {
            // 1. Get Query Parameters
            // Defaults: search is null, per_page is 10
            $search = $request->query('search');
            $perPage = $request->query('per_page', 10);

            // 2. Call Service
            $categories = $this->categoryService->categoryList($perPage, $search);

            // 3. Return Clean Paginated Response
            return $this->successResponse($categories, 'Categories retrieved successfully!');

        } catch (\Exception $e) {
            return $this->errorResponse('Error retrieving categories: '.$e->getMessage(), 500);
        }
    }
}
