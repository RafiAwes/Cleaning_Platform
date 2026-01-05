<?php

namespace App\Services;

use App\Models\Blog;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\File;

class BlogService
{
    public function createBlog(array $data, UploadedFile $image): Blog
    {
        $imagePath = $this->storeImage($image);

        $blog = new Blog([
            'title' => $data['title'],
            'description' => $data['description'],
            'image' => $imagePath,
        ]);

        $blog->save();

        return $blog->fresh();
    }

    public function updateBlog(Blog $blog, array $data, ?UploadedFile $image = null): Blog
    {
        // 1. Filter out null or empty strings so we don't wipe existing data
        // This ensures if 'description' is sent as "", we keep the OLD description.
        $data = array_filter($data, function ($value) {
            return ! is_null($value) && $value !== '';
        });

        // 2. Handle Image
        if ($image) {
            $imagePath = $this->storeImage($image);
            $this->removeImage($blog->getRawOriginal('image'));
            $data['image'] = $imagePath;
        }

        // 3. Update & Save
        // fill() updates the model instance but doesn't save yet
        $blog->fill($data);
        $blog->save();

        return $blog->fresh();
    }

    public function deleteBlog(Blog $blog): void
    {
        $this->removeImage($blog->getRawOriginal('image'));
        $blog->delete();
    }

    public function getBlogs($perPage): array
    {
        return Blog::latest()->paginate($perPage)->items();
    }

    private function storeImage(UploadedFile $file): string
    {
        if (! $file->isValid()) {
            throw new \RuntimeException('Uploaded file is not valid: '.$file->getErrorMessage());
        }

        $destinationPath = public_path('images/blog');
        if (! File::exists($destinationPath)) {
            File::makeDirectory($destinationPath, 0777, true);
        }

        $extension = $file->getClientOriginalExtension();
        $imageName = time().'_'.uniqid().'.'.$extension;

        $file->move($destinationPath, $imageName);

        return 'images/blog/'.$imageName;
    }

    private function removeImage(?string $path): void
    {
        if (! $path || $path === 'noImage.jpg') {
            return;
        }

        $fullPath = public_path($path);
        if (File::exists($fullPath)) {
            @File::delete($fullPath);
        }
    }
}
