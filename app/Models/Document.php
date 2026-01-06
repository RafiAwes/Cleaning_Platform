<?php

namespace App\Models;

use App\Models\User;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Model;

class Document extends Model
{
    protected $fillable = [
        'user_id',
        'nid', //national id
        'pob', //proof of business
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    protected function nid(): Attribute
    {
        return Attribute::make(
            get: fn (?string $value) => $value ? url($value) : null,
        );
    }

    protected function pob(): Attribute
    {
        return Attribute::make(
            get: fn (?string $value) => $value ? url($value) : null,
        );
    }

    protected function image(): Attribute
    {
        return Attribute::make(
            get: fn (?string $value) => $value ? url($value) : url('images/default/noImage.jpg'),
        );
    }
}
