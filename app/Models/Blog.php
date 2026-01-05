<?php

namespace App\Models;

use Illuminate\Http\UploadedFile;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Casts\Attribute;

class Blog extends Model
{
    protected $fillable = ['title', 'image', 'description'];


    protected function image(): Attribute
    {
       return Attribute::make( 
            get: fn (?string $value) => $value ?? 'noImage.jpg',
            set: fn ($value) => empty($value) ? 'noImage.jpg' : $value
       );
    }

    // protected function imageUrl(): Attribute
    // {
    //     return Attribute::make(
    //         get: fn () => asset('images/blog/' . $this->image),
    //     );
    // }

    public function getImageAttribute($value)
    {
        return $value ? url($value) : url('images/default/noImage.jpg');
    }
}
