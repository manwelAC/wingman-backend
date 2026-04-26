<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\PilotPricing;
use App\Models\PricingAuditLog;
use App\Models\GameRankTier;
use Illuminate\Http\Request;

class PricingController extends Controller
{
    public function index(Request $request)
    {
        $pricing = PilotPricing::where('pilot_id', $request->user()->id)
            ->with(['tierStart', 'tierEnd'])
            ->orderBy('game')
            ->orderBy('display_order')
            ->get()
            ->groupBy('game');

        return response()->json($pricing);
    }

    public function store(Request $request)
    {
        $request->validate([
            'game'                    => 'required|in:CODM,MLBB,Valorant',
            'range_name'              => 'required|string|max:100',
            'tier_start_id'           => 'required|exists:game_rank_tiers,id',
            'tier_end_id'             => 'required|exists:game_rank_tiers,id',
            'price_per_star'          => 'required|numeric|min:0',
            'major_rank_crossing_fee' => 'nullable|numeric|min:0',
            'display_order'           => 'nullable|integer',
            'reason'                  => 'nullable|string',
        ]);

        // Validate tier_start is lower than tier_end
        $tierStart = GameRankTier::findOrFail($request->tier_start_id);
        $tierEnd   = GameRankTier::findOrFail($request->tier_end_id);

        if ($tierStart->tier_order >= $tierEnd->tier_order) {
            return response()->json([
                'message' => 'Start tier must be lower than end tier.',
            ], 422);
        }

        if ($tierStart->game !== $request->game || $tierEnd->game !== $request->game) {
            return response()->json([
                'message' => 'Tiers must belong to the selected game.',
            ], 422);
        }

        $pricing = PilotPricing::create([
            'pilot_id'                => $request->user()->id,
            'game'                    => $request->game,
            'range_name'              => $request->range_name,
            'tier_start_id'           => $request->tier_start_id,
            'tier_end_id'             => $request->tier_end_id,
            'price_per_star'          => $request->price_per_star,
            'major_rank_crossing_fee' => $request->major_rank_crossing_fee ?? 0,
            'display_order'           => $request->display_order ?? 0,
            'is_active'               => true,
        ]);

        // Auto-write audit log
        PricingAuditLog::create([
            'pilot_id'          => $request->user()->id,
            'pricing_id'        => $pricing->id,
            'action'            => 'created',
            'old_price_per_star'=> null,
            'new_price_per_star'=> $pricing->price_per_star,
            'old_crossing_fee'  => null,
            'new_crossing_fee'  => $pricing->major_rank_crossing_fee,
            'reason'            => $request->reason,
            'created_at'        => now(),
        ]);

        $pricing->load(['tierStart', 'tierEnd']);

        return response()->json($pricing, 201);
    }

    public function update(Request $request, $id)
    {
        $pricing = PilotPricing::where('pilot_id', $request->user()->id)
            ->findOrFail($id);

        $request->validate([
            'range_name'              => 'sometimes|string|max:100',
            'price_per_star'          => 'sometimes|numeric|min:0',
            'major_rank_crossing_fee' => 'nullable|numeric|min:0',
            'display_order'           => 'nullable|integer',
            'reason'                  => 'nullable|string',
        ]);

        // Snapshot old values for audit
        $oldPrice       = $pricing->price_per_star;
        $oldCrossingFee = $pricing->major_rank_crossing_fee;

        $pricing->update($request->only([
            'range_name',
            'price_per_star',
            'major_rank_crossing_fee',
            'display_order',
        ]));

        // Auto-write audit log
        PricingAuditLog::create([
            'pilot_id'           => $request->user()->id,
            'pricing_id'         => $pricing->id,
            'action'             => 'updated',
            'old_price_per_star' => $oldPrice,
            'new_price_per_star' => $pricing->price_per_star,
            'old_crossing_fee'   => $oldCrossingFee,
            'new_crossing_fee'   => $pricing->major_rank_crossing_fee,
            'reason'             => $request->reason,
            'created_at'         => now(),
        ]);

        $pricing->load(['tierStart', 'tierEnd']);

        return response()->json($pricing);
    }

    public function destroy(Request $request, $id)
    {
        $pricing = PilotPricing::where('pilot_id', $request->user()->id)
            ->findOrFail($id);

        // Auto-write audit log before deactivating
        PricingAuditLog::create([
            'pilot_id'           => $request->user()->id,
            'pricing_id'         => $pricing->id,
            'action'             => 'deactivated',
            'old_price_per_star' => $pricing->price_per_star,
            'new_price_per_star' => null,
            'old_crossing_fee'   => $pricing->major_rank_crossing_fee,
            'new_crossing_fee'   => null,
            'reason'             => $request->reason,
            'created_at'         => now(),
        ]);

        $pricing->update(['is_active' => false]);

        return response()->json([
            'message' => 'Pricing tier deactivated.',
        ]);
    }

    public function audit(Request $request)
    {
        $logs = PricingAuditLog::where('pilot_id', $request->user()->id)
            ->with('pricing')
            ->orderBy('created_at', 'desc')
            ->get();

        return response()->json($logs);
    }
}