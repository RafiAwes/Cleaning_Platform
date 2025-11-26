<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Transaction extends Model
{
    protected $fillable = [
        'booking_id', 
        'vendor_id', 
        'payment_intent_id', 
        'charge_id', 
        'transfer_id', 
        'total_amount', 
        'platform_fee', 
        'vendor_amount', 
        'status'
    ];

    public function booking()
    {
        return $this->belongsTo(Booking::class);
    }

    public function vendor()
    {
        return $this->belongsTo(Vendor::class);
    }
    
}
