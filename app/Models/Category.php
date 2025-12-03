<?php

namespace App\Models;

use App\Models\Vendor;
use App\Models\Service;
use Illuminate\Database\Eloquent\Model;

class Category extends Model
{
    protected $fillable = ['name'];

    public function services()
    {
        return $this->hasMany(Service::class);
    }

    public function vendors()
    {
        return $this->belongsToMany(Vendor::class);
    }
}
