<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Cleaner extends Model
{
    protected $fillable = [
        'name',
        'phone',
        'image',
        'status',
        'ratings',
    ];

    public function vendor() {
        return $this->belongsTo(User::class, 'vendor_id');
    }

    public function bookings() {
        return $this->hasMany(Booking::class);
    }

}
