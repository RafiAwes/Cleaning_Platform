<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use App\Models\{Booking, addon, service};

class Package extends Model
{
    protected $fillable = [
        'name', 'description', 'price', 'status', 'rating', 'image'
    ];

    public function bookings() {
        return $this->hasMany(booking::class, 'package_id');
    }
    public function packageaddons(){
        return $this->hasMany(PackageAddon::class, 'package_id');
    }

    public function services() {
        return $this->belongsToMany(service::class, 'service_package')->withPivot('price', 'id', 'service_id', 'package_id')->withTimestamps();
    }
}
