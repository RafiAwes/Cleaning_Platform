<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class vendor extends Model
{
    protected $fillable = [
        'user_id',
        'about',
        'badge',
        'ratings',
        'from_time',
        'to_time',
        'bookings_target',
        'revenue_target',
    ];

    public function user()
    {
        return $this->belongsTo(User::class, 'user_id', 'id');
    }

    public function packages()
    {
        return $this->hasMany(Package::class, 'vendor_id', 'id');
    }

}
