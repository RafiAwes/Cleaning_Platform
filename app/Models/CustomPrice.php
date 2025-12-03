<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class CustomPrice extends Model
{
    protected $fillable = [
        'price',
    ];
    public function customCategory()
    {
        return $this->belongsTo(CustomCategory::class);
    }
}
