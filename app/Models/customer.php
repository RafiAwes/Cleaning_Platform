<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class customer extends Model
{
    protected $fillable = [
        'user_id',
        'phone',
        'address',
        'image_path',
    ];

    public function user()
    {
        return $this->belongsTo(User::class, 'user_id');
    }
}