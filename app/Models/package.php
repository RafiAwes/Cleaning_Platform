<?php

namespace App\Models;

use App\Models\addon;
use App\Models\service;
use Illuminate\Database\Eloquent\Model;

class package extends Model
{
    public function addons() {
        return $this->belongsToMany(addon::class, 'package_addons')->withPivot('price', 'id', 'package_id', 'addon_id')->withTimestamps();
    }

    public function services() {
        return $this->belongsToMany(service::class, 'service_package')->withPivot('price', 'id', 'service_id', 'package_id')->withTimestamps();
    }
}
