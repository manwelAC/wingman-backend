<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Laravel\Sanctum\HasApiTokens;

class User extends Authenticatable
{
    use HasFactory, HasApiTokens;

    protected $fillable = [
        'username',
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
        'email_verified_at',
        'verification_code',
        'verification_code_expires_at',
        'verification_code_sent_at',
        'fingerprint_enrolled',
        'last_login_ip',
        'last_login_city',
        'last_login_country',
        'last_login_at',
        'trusted_locations',
    ];

    protected $hidden = [
        'password',
        'remember_token',
        'verification_code',
    ];

    protected $casts = [
        'games_expertise'   => 'array',
        'is_verified'       => 'boolean',
        'is_active'         => 'boolean',
        'verification_date' => 'datetime',
        'email_verified_at' => 'datetime',
        'verification_code_expires_at' => 'datetime',
        'verification_code_sent_at'    => 'datetime',
        'last_login_at'     => 'datetime',
        'trusted_locations' => 'array',
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

    public function paymentMethods()
    {
        return $this->hasMany(UserPaymentMethod::class);
    }

    public function activePaymentMethods()
    {
        return $this->paymentMethods()->active();
    }

    public function preferredPaymentMethod()
    {
        return $this->hasOne(UserPaymentMethod::class)->where('is_preferred', true)->active();
    }

    public function isPilot(): bool
    {
        return $this->user_type === 'pilot';
    }

    public function isAdmin(): bool
    {
        return $this->user_type === 'admin';
    }

    public function isEmailVerified(): bool
    {
        return $this->email_verified_at !== null;
    }
}