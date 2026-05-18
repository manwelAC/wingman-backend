<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Grind;
use App\Models\GrindPaymentMethod;
use App\Services\PriceCalculatorService;
use App\Services\WalletService;
use Illuminate\Http\Request;

class GrindController extends Controller
{
    protected PriceCalculatorService $calculator;
    protected WalletService $walletService;

    public function __construct(PriceCalculatorService $calculator, WalletService $walletService)
    {
        $this->calculator = $calculator;
        $this->walletService = $walletService;
    }

    public function index(Request $request)
    {
        $query = Grind::where('pilot_id', $request->user()->id)
            ->with(['customer', 'startingTier', 'targetTier', 'paymentMethod.paymentMethodType']);

        if ($request->has('status')) {
            $query->where('status', $request->status);
        }

        if ($request->has('game')) {
            $query->where('game', $request->game);
        }

        $grinds = $query->orderBy('created_at', 'desc')->get();

        return response()->json($grinds);
    }

    public function store(Request $request)
    {
        $request->validate([
            'customer_id'          => 'nullable|exists:customers,id',
            'game'                 => 'required|in:CODM,MLBB,Valorant',
            'service_type'         => 'required|in:rank_boost,win_count',
            'starting_tier_id'     => 'required_if:service_type,rank_boost|exists:game_rank_tiers,id',
            'target_tier_id'       => 'required_if:service_type,rank_boost|exists:game_rank_tiers,id',
            'target_wins'          => 'required_if:service_type,win_count|integer|min:1',
            'price_per_win'        => 'required_if:service_type,win_count|numeric|min:0',
            'account_username'     => 'nullable|string|max:255',
            'special_instructions' => 'nullable|string',
            'payment_method_type_id' => 'required|exists:payment_method_types,id',
            'due_date'             => 'nullable|date|after:now',
        ]);

        $pilotId = $request->user()->id;

        // Calculate price
        $basePrice   = 0;
        $totalTiers  = 0;
        $priceBreakdown = null;

        if ($request->service_type === 'rank_boost') {
            $result      = $this->calculator->calculateRankBoost(
                $pilotId,
                $request->game,
                $request->starting_tier_id,
                $request->target_tier_id
            );
            $basePrice      = $result['base_price'];
            $totalTiers     = $result['total_tiers'];
            $priceBreakdown = $result['breakdown'];

        } elseif ($request->service_type === 'win_count') {
            $basePrice = $request->target_wins * $request->price_per_win;
        }

        // Generate grind number per pilot
        $lastGrind = Grind::where('pilot_id', $pilotId)
            ->orderBy('id', 'desc')
            ->first();

        $nextNumber  = $lastGrind
            ? (int) substr($lastGrind->grind_number, 4) + 1
            : 1;

        $grindNumber = 'GRD-' . str_pad($nextNumber, 4, '0', STR_PAD_LEFT);

        $grind = Grind::create([
            'grind_number'         => $grindNumber,
            'pilot_id'             => $pilotId,
            'customer_id'          => $request->customer_id,
            'game'                 => $request->game,
            'service_type'         => $request->service_type,
            'starting_tier_id'     => $request->starting_tier_id,
            'target_tier_id'       => $request->target_tier_id,
            'total_tiers'          => $totalTiers,
            'target_wins'          => $request->target_wins,
            'price_per_win'        => $request->price_per_win,
            'base_price'           => $basePrice,
            'final_price'          => $basePrice,
            'status'               => 'not_started',
            'progress_percentage'  => 0,
            'account_username'     => $request->account_username,
            'special_instructions' => $request->special_instructions,
            'due_date'             => $request->due_date,
        ]);

        // Create GrindPaymentMethod record
        GrindPaymentMethod::create([
            'grind_id' => $grind->id,
            'payment_method_type_id' => $request->payment_method_type_id,
        ]);

        $grind->load(['customer', 'startingTier', 'targetTier', 'paymentMethod.paymentMethodType']);

        return response()->json([
            'grind'     => $grind,
            'breakdown' => $priceBreakdown,
        ], 201);
    }

    public function show(Request $request, $id)
    {
        $grind = Grind::where('pilot_id', $request->user()->id)
            ->with(['customer', 'startingTier', 'targetTier', 'paymentMethod.paymentMethodType'])
            ->findOrFail($id);

        return response()->json($grind);
    }

    public function update(Request $request, $id)
    {
        $grind = Grind::where('pilot_id', $request->user()->id)
            ->findOrFail($id);

        $request->validate([
            'customer_id'          => 'sometimes|nullable|exists:customers,id',
            'account_username'     => 'sometimes|nullable|string|max:255',
            'special_instructions' => 'sometimes|nullable|string',
            'due_date'             => 'sometimes|nullable|date|after:now',
        ]);

        $updateData = $request->only([
            'customer_id',
            'account_username',
            'special_instructions',
            'due_date',
        ]);

        $grind->update($updateData);
        $grind->load(['customer', 'startingTier', 'targetTier', 'paymentMethod.paymentMethodType']);

        return response()->json($grind);
    }

    public function updateProgress(Request $request, $id)
    {
        $grind = Grind::where('pilot_id', $request->user()->id)
            ->findOrFail($id);

        if (in_array($grind->status, ['completed', 'cancelled'])) {
            return response()->json([
                'message' => 'Cannot update a completed or cancelled grind.',
            ], 422);
        }

        $request->validate([
            'status'              => 'sometimes|in:not_started,in_progress,cancelled',
            'progress_percentage' => 'sometimes|integer|min:0|max:100',
            'current_tier'        => 'sometimes|nullable|string',
        ]);

        $oldStatus = $grind->status;
        $newStatus = $request->status ?? $grind->status;

        // Handle status transitions with timestamps
        $updateData = $request->only([
            'status',
            'progress_percentage',
            'current_tier',
        ]);

        // Transition: not_started -> in_progress
        if ($oldStatus === 'not_started' && $newStatus === 'in_progress') {
            $updateData['started_at'] = now();
        }

        // Transition: in_progress -> completed
        if ($oldStatus === 'in_progress' && $newStatus === 'completed') {
            $updateData['completed_at'] = now();
            $updateData['progress_percentage'] = 100;
        }

        // Transition: any -> cancelled
        if ($newStatus === 'cancelled') {
            $updateData['cancelled_at'] = now();
        }

        $grind->update($updateData);

        return response()->json($grind);
    }

    public function complete(Request $request, $id)
    {
        $grind = Grind::where('pilot_id', $request->user()->id)
            ->with('paymentMethod')
            ->findOrFail($id);

        if ($grind->status === 'completed') {
            return response()->json([
                'message' => 'Grind is already completed.',
            ], 422);
        }

        if ($grind->status === 'cancelled') {
            return response()->json([
                'message' => 'Cannot complete a cancelled grind.',
            ], 422);
        }

        $grind->update([
            'status'              => 'completed',
            'progress_percentage' => 100,
            'completed_at'        => now(),
        ]);

        // Record earning in wallet
        if ($grind->paymentMethod) {
            $this->walletService->recordGrindEarning(
                $grind,
                $grind->paymentMethod->payment_method_type_id
            );
        }

        return response()->json([
            'message'      => 'Grind completed successfully.',
            'grind_number' => $grind->grind_number,
            'final_price'  => $grind->final_price,
            'completed_at' => $grind->completed_at,
        ]);
    }

    public function destroy(Request $request, $id)
    {
        $grind = Grind::where('pilot_id', $request->user()->id)
            ->findOrFail($id);

        if ($grind->status === 'in_progress') {
            return response()->json([
                'message' => 'Cannot delete an in-progress grind. Cancel it first.',
            ], 422);
        }

        $grind->delete();

        return response()->json([
            'message' => 'Grind deleted successfully.',
        ]);
    }
}