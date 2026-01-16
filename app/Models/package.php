<?php

namespace App\Models;

use App\Models\{Addon, PackageAddon, Service};
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Package extends Model
{
    use HasFactory;
    protected $fillable = [
        'title', 'description', 'price', 'status', 'rating', 'image', 'vendor_id',
    ];

    public function bookings()
    {
        return $this->hasMany(booking::class, 'package_id');
    }

    public function packageaddons()
    {
        return $this->hasMany(PackageAddon::class, 'package_id');
    }

    public function services()
    {
        return $this->hasMany(Service::class);
    }

    public function addons()
    {
        return $this->belongsToMany(Addon::class, 'package_addons')->withPivot('price')->withTimestamps();
    }

    protected function image(): Attribute
    {
        return Attribute::make(
             get: fn (?string $value) => $value ? url($value) : url('images/default/noImage.jpg'),
             set: fn ($value) => $value,
        );
       
    }
}


