<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PilotPricing extends Model
{
    protected $table = 'pilot_pricing';
    protected $fillable = [
        'pilot_id',
        'game',
        'range_name',
        'tier_start_id',
        'tier_end_id',
        'price_per_star',
        'major_rank_crossing_fee',
        'is_active',
        'display_order',
    ];

    protected $casts = [
        'price_per_star'          => 'decimal:2',
        'major_rank_crossing_fee' => 'decimal:2',
        'is_active'               => 'boolean',
    ];

    public function pilot()
    {
        return $this->belongsTo(User::class, 'pilot_id');
    }

    public function tierStart()
    {
        return $this->belongsTo(GameRankTier::class, 'tier_start_id');
    }

    public function tierEnd()
    {
        return $this->belongsTo(GameRankTier::class, 'tier_end_id');
    }

    public function auditLogs()
    {
        return $this->hasMany(PricingAuditLog::class, 'pricing_id');
    }
}