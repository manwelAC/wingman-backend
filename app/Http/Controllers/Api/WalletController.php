<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Services\WalletService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class WalletController extends Controller
{
    public function __construct(private WalletService $walletService) {}

    /**
     * Get wallet summary (balance, earnings, withdrawals)
     * GET /api/wallet
     */
    public function show(): JsonResponse
    {
        $user = auth()->user();
        $summary = $this->walletService->getWalletSummary($user);

        return response()->json([
            'success' => true,
            'data' => $summary,
        ]);
    }

    /**
     * Get transaction history with filters
     * GET /api/wallet/transactions
     */
    public function transactions(Request $request): JsonResponse
    {
        $user = auth()->user();

        $validated = $request->validate([
            'page' => 'nullable|integer|min:1',
            'limit' => 'nullable|integer|min:1|max:100',
            'type' => 'nullable|in:earning,deduction,refund,fee,withdrawal',
            'from_date' => 'nullable|date_format:Y-m-d',
            'to_date' => 'nullable|date_format:Y-m-d',
        ]);

        $page = $validated['page'] ?? 1;
        $limit = $validated['limit'] ?? 20;
        $type = $validated['type'] ?? null;
        $fromDate = $validated['from_date'] ?? null;
        $toDate = $validated['to_date'] ?? null;

        $transactions = $this->walletService->getTransactionHistory(
            $user,
            $page,
            $limit,
            $type,
            $fromDate,
            $toDate
        );

        return response()->json([
            'success' => true,
            'data' => $transactions,
        ]);
    }

    /**
     * Get earnings summary grouped by payment method
     * GET /api/wallet/earnings-by-payment-method
     */
    public function earningsByPaymentMethod(): JsonResponse
    {
        $user = auth()->user();
        $earnings = $this->walletService->getEarningsByPaymentMethod($user);

        return response()->json([
            'success' => true,
            'data' => $earnings,
        ]);
    }

    /**
     * Get earnings timeline for specific payment method
     * GET /api/wallet/earnings-by-payment-method/{payment_method_type_id}
     */
    public function earningsTimeline(Request $request, int $paymentMethodTypeId): JsonResponse
    {
        $user = auth()->user();

        $validated = $request->validate([
            'page' => 'nullable|integer|min:1',
            'limit' => 'nullable|integer|min:1|max:100',
        ]);

        $page = $validated['page'] ?? 1;
        $limit = $validated['limit'] ?? 20;

        $timeline = $this->walletService->getEarningsTimeline(
            $user,
            $paymentMethodTypeId,
            $page,
            $limit
        );

        return response()->json([
            'success' => true,
            'data' => $timeline,
        ]);
    }

    /**
     * Sync/recalculate wallet from grinds (maintenance endpoint)
     * POST /api/wallet/sync
     */
    public function sync(): JsonResponse
    {
        $user = auth()->user();
        $result = $this->walletService->syncWallet($user);

        return response()->json([
            'success' => true,
            'data' => $result,
        ]);
    }
}
