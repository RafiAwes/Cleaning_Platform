<?php

namespace App\Models;

use App\Models\addon;
use App\Models\service;
use App\Models\Booking;
use Illuminate\Database\Eloquent\Model;

class package extends Model
{
    protected $fillable = [
        'name', 'description', 'price', 'status', 'rating', 'image'
    ];

    public function bookings() {
        return $this->hasMany(booking::class, 'package_id');
    }
    public function addons() {
        return $this->belongsToMany(addon::class, 'package_addons')->withPivot('price', 'id', 'package_id', 'addon_id')->withTimestamps();
    }

    public function services() {
        return $this->belongsToMany(service::class, 'service_package')->withPivot('price', 'id', 'service_id', 'package_id')->withTimestamps();
    }
}
