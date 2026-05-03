<?php

namespace App\Http\Controllers;

use App\Services\PaymentMethodService;
use Illuminate\Http\Request;

class PaymentMethodController extends Controller
{
    private $paymentMethodService;

    public function __construct(PaymentMethodService $paymentMethodService)
    {
        $this->paymentMethodService = $paymentMethodService;
    }

    /**
     * GET /api/payment-methods/available
     * Get all available payment methods (for initial setup)
     */
    public function getAvailableMethods()
    {
        $methods = $this->paymentMethodService->getAllPaymentMethods();

        return response()->json([
            'success' => true,
            'data' => $methods,
        ]);
    }

    /**
     * GET /api/payment-methods
     * Get user's configured payment methods
     */
    public function getUserMethods(Request $request)
    {
        $methods = $this->paymentMethodService->getUserPaymentMethods($request->user());

        return response()->json([
            'success' => true,
            'data' => $methods,
        ]);
    }

    /**
     * POST /api/payment-methods
     * Add a new payment method for user
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'payment_method_type_id' => 'required|exists:payment_method_types,id',
            'account_identifier' => 'nullable|string|max:255',
            'account_holder_name' => 'nullable|string|max:255',
        ]);

        $method = $this->paymentMethodService->addPaymentMethod(
            $request->user(),
            $validated['payment_method_type_id'],
            $validated['account_identifier'] ?? null,
            $validated['account_holder_name'] ?? null
        );

        return response()->json([
            'success' => true,
            'message' => 'Payment method added successfully',
            'data' => $method->load('paymentMethodType'),
        ], 201);
    }

    /**
     * DELETE /api/payment-methods/{id}
     * Remove a payment method
     */
    public function destroy(Request $request, int $id)
    {
        $deleted = $this->paymentMethodService->removePaymentMethod($request->user(), $id);

        if (!$deleted) {
            return response()->json([
                'success' => false,
                'message' => 'Payment method not found',
            ], 404);
        }

        return response()->json([
            'success' => true,
            'message' => 'Payment method removed',
        ]);
    }

    /**
     * PATCH /api/payment-methods/{id}/toggle
     * Toggle payment method active status
     */
    public function toggle(Request $request, int $id)
    {
        $validated = $request->validate([
            'is_active' => 'required|boolean',
        ]);

        $updated = $this->paymentMethodService->togglePaymentMethod(
            $request->user(),
            $id,
            $validated['is_active']
        );

        if (!$updated) {
            return response()->json([
                'success' => false,
                'message' => 'Payment method not found',
            ], 404);
        }

        return response()->json([
            'success' => true,
            'message' => 'Payment method status updated',
        ]);
    }

    /**
     * PATCH /api/payment-methods/{id}/set-preferred
     * Set as preferred payment method
     */
    public function setPreferred(Request $request, int $id)
    {
        $updated = $this->paymentMethodService->setPreferredPaymentMethod($request->user(), $id);

        if (!$updated) {
            return response()->json([
                'success' => false,
                'message' => 'Payment method not found',
            ], 404);
        }

        return response()->json([
            'success' => true,
            'message' => 'Preferred payment method updated',
        ]);
    }

    /**
     * PATCH /api/payment-methods/{id}
     * Update payment method details
     */
    public function update(Request $request, int $id)
    {
        $validated = $request->validate([
            'account_identifier' => 'nullable|string|max:255',
            'account_holder_name' => 'nullable|string|max:255',
        ]);

        $method = $this->paymentMethodService->updatePaymentMethod(
            $request->user(),
            $id,
            $validated['account_identifier'] ?? null,
            $validated['account_holder_name'] ?? null
        );

        if (!$method) {
            return response()->json([
                'success' => false,
                'message' => 'Payment method not found',
            ], 404);
        }

        return response()->json([
            'success' => true,
            'message' => 'Payment method updated',
            'data' => $method->load('paymentMethodType'),
        ]);
    }

    /**
     * GET /api/payment-methods/for-grind
     * Get formatted payment methods for grind logging
     */
    public function getForGrindLogging(Request $request)
    {
        $methods = $this->paymentMethodService->getPaymentMethodsForGrindLogging($request->user());

        return response()->json([
            'success' => true,
            'data' => $methods,
        ]);
    }
}
