<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Grind extends Model
{
    protected $fillable = [
        'grind_number',
        'pilot_id',
        'customer_id',
        'game',
        'service_type',
        'starting_tier_id',
        'target_tier_id',
        'total_tiers',
        'target_wins',
        'price_per_win',
        'base_price',
        'final_price',
        'status',
        'progress_percentage',
        'current_tier',
        'account_username',
        'special_instructions',
        'started_at',
        'completed_at',
    ];

    protected $casts = [
        'base_price'          => 'decimal:2',
        'final_price'         => 'decimal:2',
        'price_per_win'       => 'decimal:2',
        'started_at'          => 'datetime',
        'completed_at'        => 'datetime',
    ];

    public function pilot()
    {
        return $this->belongsTo(User::class, 'pilot_id');
    }

    public function customer()
    {
        return $this->belongsTo(Customer::class, 'customer_id');
    }

    public function startingTier()
    {
        return $this->belongsTo(GameRankTier::class, 'starting_tier_id');
    }

    public function targetTier()
    {
        return $this->belongsTo(GameRankTier::class, 'target_tier_id');
    }
}