<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Booking extends Model
{
    protected $fillable = [
        'customer_id',
        'vendor_id',
        'package_id',
        'cleaner_id',
        'booking_date_time',
        'status',
        'customer_status',
        'payment_status',
        'total_price',
        'ratings',
        'ratingS',
        'notes',
        'address',
        'is_custom',
    ];

    public function customer(){
        return $this->belongsTo(User::class, 'customer_id');
    }

    public function package()
    {
        return $this->belongsTo(Package::class);
    }

    public function cleaner()
    {
        return $this->belongsTo(Cleaner::class, 'cleaner_id');
    }

    public function transaction(){
        return $this->hasOne(Transaction::class);
    }

    public function review(){
        return $this->hasOne(Review::class);
    }

    public function scopeActive($query)
    {
        return $query->where('status', 'active');
    }

    public function scopeByDate($query, $date)
    {
        return $query->whereDate('booking_date', $date);
    }

    
}
