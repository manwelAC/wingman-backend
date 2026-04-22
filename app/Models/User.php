<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Laravel\Sanctum\HasApiTokens;

class User extends Authenticatable
{
    use HasFactory, HasApiTokens;

    protected $fillable = [
        'user_type',
        'display_name',
        'email',
        'password',
        'bio',
        'profile_image_url',
        'games_expertise',
        'is_verified',
        'verification_date',
        'is_active',
    ];

    protected $hidden = [
        'password',
        'remember_token',
    ];

    protected $casts = [
        'games_expertise'   => 'array',
        'is_verified'       => 'boolean',
        'is_active'         => 'boolean',
        'verification_date' => 'datetime',
    ];

    public function customers()
    {
        return $this->hasMany(Customer::class, 'pilot_id');
    }

    public function grinds()
    {
        return $this->hasMany(Grind::class, 'pilot_id');
    }

    public function pricing()
    {
        return $this->hasMany(PilotPricing::class, 'pilot_id');
    }

    public function pricingAuditLogs()
    {
        return $this->hasMany(PricingAuditLog::class, 'pilot_id');
    }

    public function isPilot(): bool
    {
        return $this->user_type === 'pilot';
    }

    public function isAdmin(): bool
    {
        return $this->user_type === 'admin';
    }
}