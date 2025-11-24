<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Service extends Model
{
    protected $fillable = [
        'name',
        'vendor_id',
        'slug',
        'status',
        'image_path',
        'description',
        'price',
    ];

    public function bookings() {
        return $this->hasMany(Booking::class);
    }

    

    
    

}
