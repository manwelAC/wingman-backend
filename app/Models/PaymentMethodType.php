<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class PaymentMethodType extends Model
{
    protected $table = 'payment_method_types';

    protected $fillable = [
        'code',
        'name',
        'category',
        'icon_name',
        'logo_path',
        'description',
        'is_active',
    ];

    protected $casts = [
        'is_active' => 'boolean',
    ];

    /**
     * Get all user payment method configurations using this type
     */
    public function userPaymentMethods(): HasMany
    {
        return $this->hasMany(UserPaymentMethod::class);
    }

    /**
     * Scope active methods
     */
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    /**
     * Get methods by category
     */
    public function scopeByCategory($query, $category)
    {
        return $query->where('category', $category);
    }
}
