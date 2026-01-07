<?php

namespace App\Models;

use App\Models\Package;
use Illuminate\Database\Eloquent\Model;

class Addon extends Model
{
    protected $fillable = [
        'title',
    ];


    public function packageaddons()
    {
        return $this->hasMany(PackageAddon::class, 'addon_id');
    }
}
