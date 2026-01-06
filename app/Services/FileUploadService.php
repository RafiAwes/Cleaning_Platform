<?php
namespace App\Services;

use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\File;

class FileUploadService
{
    public function uploadFile(UploadedFile $file, string $path): string
    {
       if (!$file->isValid()) 
        {
            throw new \RuntimeException('Uploaded file is not valid: '.$file->getErrorMessage());
        }

        $destinationPath = public_path($path);
        if (!File::exists($destinationPath)) {
            File::makeDirectory($destinationPath, 0755, true);
        }
        $extension = $file->getClientOriginalExtension();
        $fileName = time().'_'.uniqid().'.'.$extension;
        $file->move($destinationPath, $fileName);
        return $path.'/'.$fileName;
    }

    public function deleteFile(?string $path): void
    {
        if (!$path) 
        {
            return;
        }

        $fullPath = public_path($path);
        if (File::exists($fullPath)) 
        {
            @File::delete($fullPath);
        }
    }
}