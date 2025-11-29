<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use App\Models\Notification;
use Laravel\Sanctum\HasApiTokens;
use Spatie\Permission\Traits\HasRoles;
use Illuminate\Notifications\Notifiable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;

class User extends Authenticatable
{
    /** @use HasFactory<\Database\Factories\UserFactory> */
    use HasApiTokens, HasFactory, Notifiable, HasRoles;

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'name',
        'email',
        'password',
        'google_id',
        'role',
        'phone',
        'address',        
        'profile_picture',
        'status',
        'created_at',
        'updated_at',
        'verification_code',
        'verification_expires_at',
        'reset_token',
        'reset_token_expires_at',
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var list<string>
     */
    protected $hidden = [
        'password',
        'remember_token',
        'verification_code',
        'reset_token',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
            'verification_expires_at' => 'datetime',
            'reset_token_expires_at' => 'datetime',
        ];
    }

    public function bookings(){
        return $this->hasMany(Booking::class, 'customer_id');
    }
    public function cleaners(){
        return $this->hasMany(Cleaner::class,'vendor_id');
    }
    public function reviews(){
        return $this->hasMany(Review::class);
    }

    public function notifications(){
        return $this->morphMany(Notification::class, 'notifiable')->orderBy('created_at', 'desc');
    }

}