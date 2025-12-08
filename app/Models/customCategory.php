<?php

namespace App\Models;

use App\Models\Booking;
use App\Models\customPrice;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;


class customCategory extends Model
{
    protected $fillable = [
        'name',
        'option'
    ];

    public function prices()
    {
        return $this->hasMany(customPrice::class);
    }

    public function bookings(): BelongsToMany
    {
        return $this->belongsToMany(Booking::class);
    }
}
