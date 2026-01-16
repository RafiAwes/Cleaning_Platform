<?php

namespace App\Models;

use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Casts\Attribute;

class Inventory extends Model
{
    protected $fillable = [
        'vendor_id',
        'product_name',
        'quantity',
        'image',
        'image_path',
        'stock_status',
    ];

    public function users() {
        return $this->belongsTo(User::class);
    }

    protected function imagePath(): Attribute
    {
        return Attribute::make(
            get: fn (?string $value) => $value ? url($value) : url('images/default/noImage.jpg'),
        );
    }
}
