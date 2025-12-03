<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

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
}
