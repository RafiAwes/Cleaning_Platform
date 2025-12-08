<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Casts\Attribute;



class CustomBooking extends Model
{
    protected $fillable = [
        'customer_id',
        'vendor_id',
        'services_id',
    ];

    protected $casts = [
        'services' => 'array',
    ];

    protected function customServiceData(): Attribute
    {
        return Attribute::make(
            get: fn ($value) => json_decode($value, true) ?? [],
            set: fn ($value) => json_encode($value),
        );
    }
}
