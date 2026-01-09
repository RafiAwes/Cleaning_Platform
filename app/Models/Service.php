<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Service extends Model
{
    protected $fillable = [
        'title',
        'package',
        'slug',
        'status',
        'description',
        'price',
        // 'image_path',
    ];

    
    public function user() {
        return $this->belongsTo(User::class);
    }
    
    public function bookings() {
        return $this->hasMany(Booking::class);
    }

    public function package() {
        return $this->belongsTo(Package::class);
    }
    
    public function category() {
        return $this->belongsTo(Category::class);
    }
}
