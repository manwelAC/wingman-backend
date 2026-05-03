<?php

namespace App\Services;

use App\Models\PaymentMethodType;
use App\Models\UserPaymentMethod;
use App\Models\User;
use Illuminate\Database\Eloquent\Collection;

class PaymentMethodService
{
    /**
     * Get all available payment methods grouped by category
     */
    public function getAllPaymentMethods(): array
    {
        $methods = PaymentMethodType::active()->get();

        return [
            'e_wallet' => $methods->where('category', 'e_wallet')->values(),
            'bank_transfer' => $methods->where('category', 'bank_transfer')->values(),
            'credit_card' => $methods->where('category', 'credit_card')->values(),
        ];
    }

    /**
     * Get user's active payment methods
     */
    public function getUserPaymentMethods(User $user): array
    {
        $userMethods = $user->activePaymentMethods()
            ->with('paymentMethodType')
            ->get()
            ->groupBy(fn($m) => $m->paymentMethodType->category);

        return [
            'e_wallet' => $userMethods->get('e_wallet', []),
            'bank_transfer' => $userMethods->get('bank_transfer', []),
            'credit_card' => $userMethods->get('credit_card', []),
        ];
    }

    /**
     * Add a payment method for user
     */
    public function addPaymentMethod(
        User $user,
        int $paymentMethodTypeId,
        string $accountIdentifier = null,
        string $accountHolderName = null
    ): UserPaymentMethod {
        return UserPaymentMethod::create([
            'user_id' => $user->id,
            'payment_method_type_id' => $paymentMethodTypeId,
            'account_identifier' => $accountIdentifier,
            'account_holder_name' => $accountHolderName,
            'is_preferred' => false,
            'is_active' => true,
        ]);
    }

    /**
     * Remove a payment method for user
     */
    public function removePaymentMethod(User $user, int $userPaymentMethodId): bool
    {
        $method = UserPaymentMethod::where('user_id', $user->id)
            ->where('id', $userPaymentMethodId)
            ->first();

        if (!$method) {
            return false;
        }

        return $method->delete();
    }

    /**
     * Toggle payment method active status
     */
    public function togglePaymentMethod(User $user, int $userPaymentMethodId, bool $isActive): bool
    {
        $method = UserPaymentMethod::where('user_id', $user->id)
            ->where('id', $userPaymentMethodId)
            ->first();

        if (!$method) {
            return false;
        }

        $method->update(['is_active' => $isActive]);
        return true;
    }

    /**
     * Set preferred payment method
     */
    public function setPreferredPaymentMethod(User $user, int $userPaymentMethodId): bool
    {
        // Reset all to non-preferred
        UserPaymentMethod::where('user_id', $user->id)
            ->update(['is_preferred' => false]);

        // Set the new preferred
        $method = UserPaymentMethod::where('user_id', $user->id)
            ->where('id', $userPaymentMethodId)
            ->first();

        if (!$method) {
            return false;
        }

        return $method->update(['is_preferred' => true]);
    }

    /**
     * Update payment method details
     */
    public function updatePaymentMethod(
        User $user,
        int $userPaymentMethodId,
        string $accountIdentifier = null,
        string $accountHolderName = null
    ): ?UserPaymentMethod {
        $method = UserPaymentMethod::where('user_id', $user->id)
            ->where('id', $userPaymentMethodId)
            ->first();

        if (!$method) {
            return null;
        }

        $method->update([
            'account_identifier' => $accountIdentifier ?? $method->account_identifier,
            'account_holder_name' => $accountHolderName ?? $method->account_holder_name,
        ]);

        return $method->fresh();
    }

    /**
     * Get formatted payment methods for grind logging
     * Returns only user's active methods
     */
    public function getPaymentMethodsForGrindLogging(User $user): array
    {
        return $user->activePaymentMethods()
            ->with('paymentMethodType')
            ->orderByDesc('is_preferred')
            ->orderByDesc('created_at')
            ->get()
            ->map(fn($m) => [
                'id' => $m->id,
                'type_id' => $m->payment_method_type_id,
                'name' => $m->paymentMethodType->name,
                'code' => $m->paymentMethodType->code,
                'category' => $m->paymentMethodType->category,
                'icon' => $m->paymentMethodType->icon_name,
                'account_holder' => $m->account_holder_name,
                'is_preferred' => $m->is_preferred,
            ])
            ->groupBy('category')
            ->toArray();
    }
}
