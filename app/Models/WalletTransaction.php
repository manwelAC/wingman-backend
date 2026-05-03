<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class WalletTransaction extends Model
{
    use HasFactory;

    public $timestamps = false;

    protected $fillable = [
        'wallet_id',
        'type',
        'amount',
        'grind_id',
        'payment_method_type_id',
        'reference_id',
        'balance_after',
        'description',
    ];

    protected $casts = [
        'amount' => 'decimal:2',
        'balance_after' => 'decimal:2',
        'created_at' => 'datetime',
    ];

    /**
     * Get the wallet that owns this transaction
     */
    public function wallet(): BelongsTo
    {
        return $this->belongsTo(Wallet::class);
    }

    /**
     * Get the grind associated with this transaction (if any)
     */
    public function grind(): BelongsTo
    {
        return $this->belongsTo(Grind::class)->withDefault();
    }

    /**
     * Get the payment method type associated with this transaction (if any)
     */
    public function paymentMethodType(): BelongsTo
    {
        return $this->belongsTo(PaymentMethodType::class)->withDefault();
    }
}
