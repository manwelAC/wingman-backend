<?php

namespace App\Services;

use App\Models\Grind;
use App\Models\PaymentMethodType;
use App\Models\User;
use App\Models\Wallet;
use App\Models\WalletTransaction;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Pagination\Paginator;

class WalletService
{
    /**
     * Get or create wallet for user
     */
    public function getOrCreateWallet(User $user): Wallet
    {
        return $user->wallet()->firstOrCreate(
            ['user_id' => $user->id],
            [
                'balance' => 0,
                'total_earned' => 0,
                'total_withdrawn' => 0,
                'pending_amount' => 0,
            ]
        );
    }

    /**
     * Get wallet summary for user
     */
    public function getWalletSummary(User $user): array
    {
        $wallet = $this->getOrCreateWallet($user);

        return [
            'wallet_id' => $wallet->id,
            'balance' => (float) $wallet->balance,
            'total_earned' => (float) $wallet->total_earned,
            'total_withdrawn' => (float) $wallet->total_withdrawn,
            'pending_amount' => (float) $wallet->pending_amount,
            'last_transaction_at' => $wallet->transactions()
                ->latest('created_at')
                ->value('created_at'),
        ];
    }

    /**
     * Get paginated transaction history
     */
    public function getTransactionHistory(
        User $user,
        int $page = 1,
        int $limit = 20,
        ?string $type = null,
        ?string $fromDate = null,
        ?string $toDate = null
    ): array {
        $wallet = $this->getOrCreateWallet($user);

        $query = $wallet->transactions()
            ->with(['grind', 'paymentMethodType']);

        if ($type) {
            $query->where('type', $type);
        }

        if ($fromDate) {
            $query->whereDate('created_at', '>=', $fromDate);
        }

        if ($toDate) {
            $query->whereDate('created_at', '<=', $toDate);
        }

        $transactions = $query
            ->orderByDesc('created_at')
            ->paginate($limit, ['*'], 'page', $page);

        return [
            'transactions' => $this->formatTransactions($transactions->items()),
            'pagination' => [
                'total' => $transactions->total(),
                'per_page' => $transactions->perPage(),
                'current_page' => $transactions->currentPage(),
                'last_page' => $transactions->lastPage(),
            ],
        ];
    }

    /**
     * Get earnings summary grouped by payment method
     */
    public function getEarningsByPaymentMethod(User $user): array
    {
        $wallet = $this->getOrCreateWallet($user);

        // Get all payment method types
        $paymentMethodTypes = PaymentMethodType::get();

        // Fetch raw earnings data efficiently
        $earningsData = WalletTransaction::where('wallet_id', $wallet->id)
            ->where('type', 'earning')
            ->selectRaw('payment_method_type_id')
            ->selectRaw('SUM(amount) as total_earned')
            ->selectRaw('COUNT(*) as grind_count')
            ->selectRaw('MAX(created_at) as last_earned_at')
            ->groupBy('payment_method_type_id')
            ->get()
            ->keyBy('payment_method_type_id');

        // Group by category
        $grouped = [
            'e_wallet' => [],
            'bank_transfer' => [],
            'credit_card' => [],
        ];

        foreach ($paymentMethodTypes as $methodType) {
            $earnings = $earningsData->get($methodType->id);

            $methodData = [
                'id' => $methodType->id,
                'type_id' => $methodType->id,
                'code' => $methodType->code,
                'name' => $methodType->name,
                'icon' => $methodType->icon_name,
                'logo_path' => $methodType->logo_path,
                'category' => $methodType->category,
                'total_earned' => $earnings ? (float) $earnings->total_earned : 0,
                'grind_count' => $earnings ? (int) $earnings->grind_count : 0,
                'last_earned_at' => $earnings && $earnings->last_earned_at ? \Carbon\Carbon::parse($earnings->last_earned_at)->toIso8601String() : null,
            ];

            $grouped[$methodType->category][] = $methodData;
        }

        return [
            'total_balance' => (float) $wallet->balance,
            'total_earned' => (float) $wallet->total_earned,
            'earnings_by_method' => $grouped,
        ];
    }

    /**
     * Get earnings timeline for specific payment method
     */
    public function getEarningsTimeline(
        User $user,
        int $paymentMethodTypeId,
        int $page = 1,
        int $limit = 20
    ): array {
        $wallet = $this->getOrCreateWallet($user);

        $paymentMethodType = PaymentMethodType::findOrFail($paymentMethodTypeId);

        // Get earnings summary
        $summary = WalletTransaction::where('wallet_id', $wallet->id)
            ->where('type', 'earning')
            ->where('payment_method_type_id', $paymentMethodTypeId)
            ->selectRaw('SUM(amount) as total_earned')
            ->selectRaw('COUNT(*) as grind_count')
            ->selectRaw('MAX(created_at) as last_earned_at')
            ->first();

        // Get paginated timeline
        $timeline = WalletTransaction::where('wallet_id', $wallet->id)
            ->where('type', 'earning')
            ->where('payment_method_type_id', $paymentMethodTypeId)
            ->with('grind')
            ->orderByDesc('created_at')
            ->paginate($limit, ['*'], 'page', $page);

        return [
            'payment_method' => [
                'id' => $paymentMethodType->id,
                'code' => $paymentMethodType->code,
                'name' => $paymentMethodType->name,
                'category' => $paymentMethodType->category,
                'icon' => $paymentMethodType->icon_name,
            ],
            'summary' => [
                'total_earned' => (float) ($summary?->total_earned ?? 0),
                'grind_count' => (int) ($summary?->grind_count ?? 0),
                'last_earned_at' => $summary && $summary->last_earned_at ? \Carbon\Carbon::parse($summary->last_earned_at)->toIso8601String() : null,
            ],
            'timeline' => $this->formatGrindsForTimeline($timeline->items()),
            'pagination' => [
                'total' => $timeline->total(),
                'per_page' => $timeline->perPage(),
                'current_page' => $timeline->currentPage(),
                'last_page' => $timeline->lastPage(),
            ],
        ];
    }

    /**
     * Record earning when grind is completed
     */
    public function recordGrindEarning(Grind $grind, int $paymentMethodTypeId): WalletTransaction
    {
        $pilot = $grind->pilot;
        $wallet = $this->getOrCreateWallet($pilot);

        return $wallet->recordEarning($grind, $paymentMethodTypeId);
    }

    /**
     * Record refund when grind is cancelled
     */
    public function recordGrindRefund(Grind $grind, float $amount, string $reason = null): WalletTransaction
    {
        $pilot = $grind->pilot;
        $wallet = $this->getOrCreateWallet($pilot);

        return $wallet->recordRefund($grind, $amount, $reason);
    }

    /**
     * Record withdrawal request
     */
    public function recordWithdrawal(User $user, float $amount, string $referenceId = null): WalletTransaction
    {
        $wallet = $this->getOrCreateWallet($user);

        return $wallet->recordWithdrawal($amount, $referenceId);
    }

    /**
     * Record fee
     */
    public function recordFee(User $user, float $amount, string $description = null): WalletTransaction
    {
        $wallet = $this->getOrCreateWallet($user);

        return $wallet->recordFee($amount, $description);
    }

    /**
     * Sync/recalculate wallet from grinds
     */
    public function syncWallet(User $user): array
    {
        $wallet = $this->getOrCreateWallet($user);

        // Clear existing transactions
        $wallet->transactions()->delete();

        // Recalculate from all completed grinds
        $completedGrinds = $user->grinds()
            ->where('status', 'completed')
            ->with('paymentMethod')
            ->get();

        $totalEarned = 0;
        $transactions = [];

        foreach ($completedGrinds as $grind) {
            $amount = (float) $grind->final_price;
            $totalEarned += $amount;

            $paymentMethodTypeId = $grind->paymentMethod?->payment_method_type_id;

            $transaction = [
                'wallet_id' => $wallet->id,
                'type' => 'earning',
                'amount' => $amount,
                'grind_id' => $grind->id,
                'payment_method_type_id' => $paymentMethodTypeId,
                'balance_after' => $totalEarned,
                'description' => "Earnings from grind completion",
                'created_at' => $grind->completed_at ?? now(),
            ];

            $transactions[] = $transaction;
        }

        // Bulk insert transactions
        if (!empty($transactions)) {
            WalletTransaction::insert($transactions);
        }

        // Update wallet totals
        $wallet->update([
            'balance' => $totalEarned,
            'total_earned' => $totalEarned,
        ]);

        return [
            'wallet_id' => $wallet->id,
            'balance' => (float) $wallet->balance,
            'total_earned' => (float) $wallet->total_earned,
            'transactions_processed' => count($transactions),
            'message' => 'Wallet synced successfully',
        ];
    }

    /**
     * Format transaction for API response
     */
    private function formatTransactions(array $transactions): array
    {
        return array_map(function (WalletTransaction $transaction) {
            return [
                'id' => $transaction->id,
                'type' => $transaction->type,
                'amount' => (float) $transaction->amount,
                'balance_after' => (float) $transaction->balance_after,
                'grind_id' => $transaction->grind_id,
                'grind_number' => $transaction->grind?->grind_number,
                'payment_method_type_id' => $transaction->payment_method_type_id,
                'payment_method_name' => $transaction->paymentMethodType?->name,
                'reference_id' => $transaction->reference_id,
                'description' => $transaction->description,
                'created_at' => $transaction->created_at->toIso8601String(),
            ];
        }, $transactions);
    }

    /**
     * Format grinds for timeline response
     */
    private function formatGrindsForTimeline(array $transactions): array
    {
        return array_map(function (WalletTransaction $transaction) {
            $grind = $transaction->grind;

            return [
                'id' => $grind->id,
                'grind_number' => $grind->grind_number,
                'game' => $grind->game,
                'service_type' => $grind->service_type,
                'starting_tier' => $grind->starting_tier,
                'target_tier' => $grind->target_tier,
                'target_stars' => $grind->target_stars,
                'final_price' => (float) $grind->final_price,
                'completed_at' => $grind->completed_at?->toIso8601String(),
            ];
        }, $transactions);
    }
}
