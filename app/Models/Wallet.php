<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Wallet extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'balance',
        'total_earned',
        'total_withdrawn',
        'pending_amount',
    ];

    protected $casts = [
        'balance' => 'decimal:2',
        'total_earned' => 'decimal:2',
        'total_withdrawn' => 'decimal:2',
        'pending_amount' => 'decimal:2',
    ];

    /**
     * Get the user that owns the wallet
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Get all transactions for this wallet
     */
    public function transactions(): HasMany
    {
        return $this->hasMany(WalletTransaction::class);
    }

    /**
     * Get earnings transactions grouped by payment method type
     */
    public function earningsByPaymentMethod()
    {
        return $this->transactions()
            ->where('type', 'earning')
            ->selectRaw('payment_method_type_id, SUM(amount) as total_earned, COUNT(*) as grind_count, MAX(created_at) as last_earned_at')
            ->groupBy('payment_method_type_id')
            ->orderByDesc('total_earned');
    }

    /**
     * Create an earning transaction when grind is completed
     */
    public function recordEarning(Grind $grind, int $paymentMethodTypeId): WalletTransaction
    {
        $amount = (float) $grind->final_price;
        $newBalance = (float) $this->balance + $amount;

        $transaction = $this->transactions()->create([
            'type' => 'earning',
            'amount' => $amount,
            'grind_id' => $grind->id,
            'payment_method_type_id' => $paymentMethodTypeId,
            'balance_after' => $newBalance,
            'description' => "Earnings from grind completion",
        ]);

        // Update wallet totals
        $this->update([
            'balance' => $newBalance,
            'total_earned' => (float) $this->total_earned + $amount,
        ]);

        return $transaction;
    }

    /**
     * Create a withdrawal transaction
     */
    public function recordWithdrawal(float $amount, string $referenceId = null): WalletTransaction
    {
        if ((float) $this->balance < $amount) {
            throw new \Exception('Insufficient wallet balance');
        }

        $newBalance = (float) $this->balance - $amount;

        $transaction = $this->transactions()->create([
            'type' => 'withdrawal',
            'amount' => $amount,
            'reference_id' => $referenceId,
            'balance_after' => $newBalance,
            'description' => "Withdrawal to payment method",
        ]);

        // Update wallet totals
        $this->update([
            'balance' => $newBalance,
            'total_withdrawn' => (float) $this->total_withdrawn + $amount,
        ]);

        return $transaction;
    }

    /**
     * Create a refund transaction
     */
    public function recordRefund(Grind $grind, float $amount, string $reason = null): WalletTransaction
    {
        $newBalance = (float) $this->balance - $amount;

        $transaction = $this->transactions()->create([
            'type' => 'refund',
            'amount' => $amount,
            'grind_id' => $grind->id,
            'balance_after' => $newBalance,
            'description' => $reason ?? "Refund from grind cancellation",
        ]);

        // Update wallet totals
        $this->update([
            'balance' => $newBalance,
            'total_earned' => (float) $this->total_earned - $amount,
        ]);

        return $transaction;
    }

    /**
     * Create a fee transaction
     */
    public function recordFee(float $amount, string $description = null): WalletTransaction
    {
        if ((float) $this->balance < $amount) {
            throw new \Exception('Insufficient wallet balance for fee');
        }

        $newBalance = (float) $this->balance - $amount;

        $transaction = $this->transactions()->create([
            'type' => 'fee',
            'amount' => $amount,
            'balance_after' => $newBalance,
            'description' => $description ?? "Platform fee",
        ]);

        // Update wallet totals
        $this->update([
            'balance' => $newBalance,
        ]);

        return $transaction;
    }
}
