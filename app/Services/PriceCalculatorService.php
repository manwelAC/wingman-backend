<?php

namespace App\Services;

use App\Models\GameRankTier;
use App\Models\PilotPricing;

class PriceCalculatorService
{
    public function calculateRankBoost(
        int $pilotId,
        string $game,
        int $startTierId,
        int $targetTierId
    ): array {
        $startTier  = GameRankTier::findOrFail($startTierId);
        $targetTier = GameRankTier::findOrFail($targetTierId);

        // Validate same game
        if ($startTier->game !== $game || $targetTier->game !== $game) {
            throw new \Exception('Tiers must belong to the selected game.');
        }

        // Validate direction
        if ($startTier->tier_order >= $targetTier->tier_order) {
            throw new \Exception('Target tier must be higher than starting tier.');
        }

        // Get pilot's active pricing ranges for this game
        $pilotPricing = PilotPricing::where('pilot_id', $pilotId)
            ->where('game', $game)
            ->where('is_active', true)
            ->with(['tierStart', 'tierEnd'])
            ->orderBy('display_order')
            ->get();

        if ($pilotPricing->isEmpty()) {
            throw new \Exception('No active pricing found for this game.');
        }

        $breakdown          = [];
        $totalPrice         = 0;
        $currentOrder       = $startTier->tier_order;
        $targetOrder        = $targetTier->tier_order;

        while ($currentOrder < $targetOrder) {
            // Find pricing range that covers the current position
            $range = $pilotPricing->first(function ($pricing) use ($currentOrder) {
                return $currentOrder >= $pricing->tierStart->tier_order
                    && $currentOrder < $pricing->tierEnd->tier_order;
            });

            if (!$range) {
                throw new \Exception("No pricing range covers tier order: {$currentOrder}. Please set up complete pricing.");
            }

            // How far this range goes
            $rangeEndOrder  = min($range->tierEnd->tier_order, $targetOrder);
            $tiersInRange   = $rangeEndOrder - $currentOrder;

            // Base cost for this range segment
            $segmentPrice   = $tiersInRange * $range->price_per_tier;

            // Check for major rank group crossings within this segment
            $crossingFee    = 0;
            $crossings      = $this->getMajorRankCrossings(
                $currentOrder,
                $rangeEndOrder,
                $game
            );

            if (!empty($crossings) && $range->major_rank_crossing_fee > 0) {
                $crossingFee = count($crossings) * $range->major_rank_crossing_fee;
            }

            $segmentTotal = $segmentPrice + $crossingFee;

            $breakdown[] = [
                'range_name'              => $range->range_name,
                'tiers'                   => $tiersInRange,
                'price_per_tier'          => (float) $range->price_per_tier,
                'subtotal'                => (float) $segmentPrice,
                'crossings'               => $crossings,
                'major_rank_crossing_fee' => (float) $crossingFee,
                'total'                   => (float) $segmentTotal,
            ];

            $totalPrice   += $segmentTotal;
            $currentOrder  = $rangeEndOrder;
        }

        return [
            'game'         => $game,
            'start_tier'   => $startTier->tier_name,
            'target_tier'  => $targetTier->tier_name,
            'total_tiers'  => $targetTier->tier_order - $startTier->tier_order,
            'base_price'   => (float) $totalPrice,
            'breakdown'    => $breakdown,
        ];
    }

    private function getMajorRankCrossings(
        int $startOrder,
        int $endOrder,
        string $game
    ): array {
        $tiers = GameRankTier::where('game', $game)
            ->whereBetween('tier_order', [$startOrder + 1, $endOrder])
            ->orderBy('tier_order')
            ->get();

        $crossings      = [];
        $previousGroup  = GameRankTier::where('game', $game)
            ->where('tier_order', $startOrder)
            ->value('rank_group');

        foreach ($tiers as $tier) {
            if ($tier->rank_group !== $previousGroup) {
                $crossings[]   = $tier->rank_group;
                $previousGroup = $tier->rank_group;
            }
        }

        return $crossings;
    }
}