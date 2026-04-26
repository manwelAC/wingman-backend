<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class GameRankTier extends Model
{
    protected $fillable = [
        'game',
        'tier_name',
        'tier_order',
        'rank_group',
        'tier_number',
        'stars_per_tier',
        'is_active',
    ];

    protected $casts = [
        'is_active' => 'boolean',
    ];

    public function pricingStarts()
    {
        return $this->hasMany(PilotPricing::class, 'tier_start_id');
    }

    public function pricingEnds()
    {
        return $this->hasMany(PilotPricing::class, 'tier_end_id');
    }
}