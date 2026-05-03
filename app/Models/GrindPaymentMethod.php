<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class GrindPaymentMethod extends Model
{
    protected $table = 'grind_payment_methods';

    const UPDATED_AT = null;

    protected $fillable = [
        'grind_id',
        'payment_method_type_id',
        'customer_reference',
    ];

    protected $casts = [];

    /**
     * Get the grind
     */
    public function grind(): BelongsTo
    {
        return $this->belongsTo(Grind::class);
    }

    /**
     * Get the payment method type
     */
    public function paymentMethodType(): BelongsTo
    {
        return $this->belongsTo(PaymentMethodType::class);
    }
}
