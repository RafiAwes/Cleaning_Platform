<?php

namespace App\Services;

use App\Models\Category;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\File;

class CategoryService
{
    public function createCategory(array $data, ?UploadedFile $image = null): Category
    {
        if ($image) {
            $imagePath = $this->storeImage($image);
            $data['image'] = $imagePath;
        }

        $category = Category::create($data);

        return $category->fresh();
    }

    public function updateCategory(Category $category, array $data, ?UploadedFile $image = null): Category
    {
        // Filter out empty values to preserve existing data
        $updateData = array_filter($data, function ($value) {
            return !is_null($value) && $value !== '';
        });

        if ($image) {
            $imagePath = $this->storeImage($image);
            $this->removeImage($category->getRawOriginal('image'));
            $updateData['image'] = $imagePath;
        }

        $category->update($updateData);

        return $category->fresh();
    }

    public function deleteCategory(Category $category): void
    {
        $this->removeImage($category->getRawOriginal('image'));
        $category->delete();
    }

    private function storeImage(UploadedFile $file): string
    {
        if (!$file->isValid()) {
            throw new \RuntimeException('Uploaded file is not valid: ' . $file->getErrorMessage());
        }

        $destinationPath = public_path('images/category');
        if (!File::exists($destinationPath)) {
            File::makeDirectory($destinationPath, 0777, true);
        }

        $extension = $file->getClientOriginalExtension();
        $imageName = time() . '_' . uniqid() . '.' . $extension;

        $file->move($destinationPath, $imageName);

        return 'images/category/' . $imageName;
    }

    private function removeImage(?string $path): void
    {
        if (!$path) {
            return;
        }

        $fullPath = public_path($path);
        if (File::exists($fullPath)) {
            @File::delete($fullPath);
        }
    }

    public function categoryList($perPage, ?string $search = null)
    {
        $query = Category::query();
        $query->when($search, function ($q) use ($search) {
            $q->where('name', 'like', '%' . $search . '%');
        });
        return $query->latest()->paginate($perPage);
    }
}
