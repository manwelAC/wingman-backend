<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class UserPaymentMethod extends Model
{
    protected $table = 'user_payment_methods';

    protected $fillable = [
        'user_id',
        'payment_method_type_id',
        'account_identifier',
        'account_holder_name',
        'is_preferred',
        'is_active',
    ];

    protected $casts = [
        'is_preferred' => 'boolean',
        'is_active' => 'boolean',
    ];

    /**
     * Get the user that owns this payment method
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Get the payment method type
     */
    public function paymentMethodType(): BelongsTo
    {
        return $this->belongsTo(PaymentMethodType::class);
    }

    /**
     * Scope active methods
     */
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    /**
     * Scope preferred method
     */
    public function scopePreferred($query)
    {
        return $query->where('is_preferred', true);
    }
}
