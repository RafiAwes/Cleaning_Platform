<?php

namespace App\Models;

use App\Models\Vendor;
use App\Models\Service;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Casts\Attribute;

class Category extends Model
{
    protected $fillable = ['name', 'image'];

    protected function image(): Attribute
    {
        return Attribute::make(
            get: fn (?string $value) => $value ? url($value) : url('images/default/noImage.jpg'),
        );
    }

    public function services()
    {
        return $this->hasMany(Service::class);
    }

    public function vendors()
    {
        return $this->belongsToMany(Vendor::class);
    }
}
