<?php

namespace App\Models;

use App\Models\Package;
use Illuminate\Database\Eloquent\Model;

class Addon extends Model
{
    protected $fillable = [
        'title',
    ];

    public function packages()
    {
        return $this->belongsToMany(Package::class, 'package_addons')->withPivot('price', 'id', 'package_id', 'addon_id')->withTimestamps();
    }
}
