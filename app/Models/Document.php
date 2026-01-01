<?php

namespace App\Models;

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
}
