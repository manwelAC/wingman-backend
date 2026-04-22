<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Services\PriceCalculatorService;
use Illuminate\Http\Request;

class CalculatorController extends Controller
{
    protected PriceCalculatorService $calculator;

    public function __construct(PriceCalculatorService $calculator)
    {
        $this->calculator = $calculator;
    }

    public function rankBoost(Request $request)
    {
        $request->validate([
            'game'            => 'required|in:CODM,MLBB,Valorant',
            'starting_tier_id'=> 'required|exists:game_rank_tiers,id',
            'target_tier_id'  => 'required|exists:game_rank_tiers,id',
        ]);

        try {
            $result = $this->calculator->calculateRankBoost(
                $request->user()->id,
                $request->game,
                $request->starting_tier_id,
                $request->target_tier_id
            );

            return response()->json($result);

        } catch (\Exception $e) {
            return response()->json([
                'message' => $e->getMessage(),
            ], 422);
        }
    }
}