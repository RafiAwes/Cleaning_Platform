<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Booking extends Model
{
    protected $fillable = [
        'customer_id',
        'service_id',
        'cleaner_id',
        'booking_date',
        'status',
        'total_price',
    ] ;

    public function customer(){
        return $this->hasMany(Customer::class);
        return $this->belongsTo(Customer::class, 'customer_id');
    }

    public function service()
    {
        return $this->belongsTo(Service::class, 'service_id');
    }

    public function cleaner()
    {
        return $this->belongsTo(Cleaner::class, 'cleaner_id');
    }

    public function payment(){
        return $this->hasOne(Payment::class);
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
