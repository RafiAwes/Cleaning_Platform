<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\BlogResource;
use App\Models\Blog;
use App\Services\BlogService;
use Illuminate\Http\Request;

class BlogController extends Controller
{
    private BlogService $blogService;

    public function __construct(BlogService $blogService)
    {
        $this->blogService = $blogService;
    }

    public function index(Request $request)
    {
        $perPage = $request->query('per_page', 15);
        $blogs = $this->blogService->getBlogs($perPage);

        return response()->json([
            'success' => true,
            'message' => 'Blogs retrieved successfully!',
            'data' => BlogResource::collection(collect($blogs)),
        ]);
    }

    public function createBlog(Request $request)
    {
        try {
            $validated = $request->validate([
                'title' => 'required|max:255',
                'image' => 'required|image|mimes:jpeg,png,jpg,gif,svg|max:2048',
                'description' => 'required',
            ]);

            $blog = $this->blogService->createBlog($validated, $request->file('image'));

            return response()->json([
                'success' => true,
                'message' => 'Blog created successfully!',
                'data' => new BlogResource($blog),
            ]);
        } catch (\RuntimeException $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 400);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error creating blog: '.$e->getMessage(),
                'exception' => get_class($e),
                'line' => $e->getLine(),
                'file' => $e->getFile(),
            ], 500);
        }
    }

    public function updateBlog(Request $request, $id)
    {
        try {
            $blog = Blog::findOrFail($id);

            // 1. Validation
            // 'sometimes' means: if the field is present, validate it.
            $validated = $request->validate([
                'title' => 'sometimes|nullable|string|max:255',
                'description' => 'sometimes|nullable|string',
                'image' => 'sometimes|nullable|image|mimes:jpeg,png,jpg,gif,svg|max:2048',
            ]);

            // 2. Call Service
            // We pass the validated array and the file (if it exists)
            $updatedBlog = $this->blogService->updateBlog(
                $blog,
                $validated,
                $request->file('image')
            );

            return response()->json([
                'success' => true,
                'message' => 'Blog updated successfully!',
                'data' => new BlogResource($updatedBlog),
            ]);

        } catch (\Exception $e) {
            return response()->json(['message' => $e->getMessage()], 400);
        }
    }

    public function deleteBlog($id)
    {
        $blog = Blog::find($id);

        if (! $blog) {
            return response()->json([
                'success' => false,
                'message' => 'Blog not found',
            ], 404);
        }

        $this->blogService->deleteBlog($blog);

        return response()->json([
            'success' => true,
            'message' => 'Blog deleted successfully',
        ]);
    }
}
