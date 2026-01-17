<?php

namespace App\Models;

use App\Models\Package;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Addon extends Model
{
    use HasFactory;

    protected $fillable = [
        'title',
    ];

    protected $appends = ['name'];

    /**
     * Get the name attribute (alias for title)
     */
    protected function name(): Attribute
    {
        return Attribute::make(
            get: fn () => $this->title,
        );
    }

    public function packageaddons()
    {
        return $this->hasMany(PackageAddon::class, 'addon_id');
    }
}
