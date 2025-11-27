<?php

namespace App\Models;

use App\Models\User;
use Illuminate\Database\Eloquent\Model;

class Inventory extends Model
{
    protected $fillable = [
        'product_name',
        'quantity',
        'image',
    ];

    public function users() {
        return $this->belongsTo(User::class);
    }
}
