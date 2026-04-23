<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\GameRankTier;
use Illuminate\Http\Request;

class GameRankController extends Controller
{
    public function index($game)
    {
        $validGames = ['CODM', 'MLBB', 'Valorant'];

        if (!in_array($game, $validGames)) {
            return response()->json([
                'message' => 'Invalid game. Valid options are: CODM, MLBB, Valorant.',
            ], 422);
        }

        $tiers = GameRankTier::where('game', $game)
            ->where('is_active', true)
            ->orderBy('tier_order')
            ->get();

        return response()->json([
            'game'  => $game,
            'tiers' => $tiers,
        ]);
    }

    public function games()
    {
        return response()->json([
            'games' => ['CODM', 'MLBB', 'Valorant'],
        ]);
    }
}