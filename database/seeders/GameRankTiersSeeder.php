<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class GameRankTiersSeeder extends Seeder
{
    public function run(): void
    {
        $tiers = [];

        // CODM
        $codmRanks = [
            'Veteran'     => ['I', 'II', 'III', 'IV', 'V'],
            'Elite'       => ['I', 'II', 'III', 'IV', 'V'],
            'Pro'         => ['I', 'II', 'III', 'IV', 'V'],
            'Master'      => ['I', 'II', 'III', 'IV', 'V'],
            'GrandMaster' => ['I', 'II', 'III', 'IV', 'V'],
        ];

        $order = 1;
        foreach ($codmRanks as $group => $numbers) {
            foreach ($numbers as $number) {
                $tiers[] = [
                    'game'           => 'CODM',
                    'tier_name'      => "$group $number",
                    'tier_order'     => $order++,
                    'rank_group'     => $group,
                    'tier_number'    => $number,
                    'stars_per_tier' => 1,
                    'is_active'      => true,
                    'created_at'     => now(),
                    'updated_at'     => now(),
                ];
            }
        }
        // CODM peak
        $tiers[] = [
            'game'           => 'CODM',
            'tier_name'      => 'Legendary',
            'tier_order'     => $order++,
            'rank_group'     => 'Legendary',
            'tier_number'    => null,
            'stars_per_tier' => 1,
            'is_active'      => true,
            'created_at'     => now(),
            'updated_at'     => now(),
        ];

        // Valorant
        $valorantRanks = [
            'Iron'      => ['I', 'II', 'III'],
            'Bronze'    => ['I', 'II', 'III'],
            'Silver'    => ['I', 'II', 'III'],
            'Gold'      => ['I', 'II', 'III'],
            'Platinum'  => ['I', 'II', 'III'],
            'Diamond'   => ['I', 'II', 'III'],
            'Ascendant' => ['I', 'II', 'III'],
            'Immortal'  => ['I', 'II', 'III'],
        ];

        $order = 1;
        foreach ($valorantRanks as $group => $numbers) {
            foreach ($numbers as $number) {
                $tiers[] = [
                    'game'           => 'Valorant',
                    'tier_name'      => "$group $number",
                    'tier_order'     => $order++,
                    'rank_group'     => $group,
                    'tier_number'    => $number,
                    'stars_per_tier' => 1,
                    'is_active'      => true,
                    'created_at'     => now(),
                    'updated_at'     => now(),
                ];
            }
        }
        // Valorant peak
        $tiers[] = [
            'game'           => 'Valorant',
            'tier_name'      => 'Radiant',
            'tier_order'     => $order++,
            'rank_group'     => 'Radiant',
            'tier_number'    => null,
            'stars_per_tier' => 1,
            'is_active'      => true,
            'created_at'     => now(),
            'updated_at'     => now(),
        ];

        // MLBB
        // Note: Within each group, numbers count DOWN (III→I or V→I)
        // but tier_order always goes UP so the price walk stays consistent
        $mlbbRanks = [
            'Warrior'     => ['III', 'II', 'I'],
            'Elite'       => ['IV', 'III', 'II', 'I'],
            'Master'      => ['IV', 'III', 'II', 'I'],
            'Grandmaster' => ['V', 'IV', 'III', 'II', 'I'],
            'Epic'        => ['V', 'IV', 'III', 'II', 'I'],
            'Legend'      => ['V', 'IV', 'III', 'II', 'I'],
        ];

        $order = 1;
        foreach ($mlbbRanks as $group => $numbers) {
            // Determine stars per tier based on rank group
            $starsPerTier = match($group) {
                'Warrior'     => 3,
                'Elite'       => 4,
                'Master'      => 4,
                'Grandmaster' => 5,
                'Epic'        => 5,
                'Legend'      => 5,
                default       => 1,
            };

            foreach ($numbers as $number) {
                $tiers[] = [
                    'game'           => 'MLBB',
                    'tier_name'      => "$group $number",
                    'tier_order'     => $order++,
                    'rank_group'     => $group,
                    'tier_number'    => $number,
                    'stars_per_tier' => $starsPerTier,
                    'is_active'      => true,
                    'created_at'     => now(),
                    'updated_at'     => now(),
                ];
            }
        }
        // MLBB peak
        $tiers[] = [
            'game'           => 'MLBB',
            'tier_name'      => 'Mythic',
            'tier_order'     => $order++,
            'rank_group'     => 'Mythic',
            'tier_number'    => null,
            'stars_per_tier' => 1,
            'is_active'      => true,
            'created_at'     => now(),
            'updated_at'     => now(),
        ];

        DB::table('game_rank_tiers')->insert($tiers);
    }
}