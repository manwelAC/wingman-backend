<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PricingAuditLog extends Model
{
    public $timestamps = false;

    protected $fillable = [
        'pilot_id',
        'pricing_id',
        'action',
        'old_price_per_tier',
        'new_price_per_tier',
        'old_crossing_fee',
        'new_crossing_fee',
        'reason',
        'notes',
        'created_at',
    ];

    protected $casts = [
        'old_price_per_tier' => 'decimal:2',
        'new_price_per_tier' => 'decimal:2',
        'old_crossing_fee'   => 'decimal:2',
        'new_crossing_fee'   => 'decimal:2',
        'created_at'         => 'datetime',
    ];

    public function pilot()
    {
        return $this->belongsTo(User::class, 'pilot_id');
    }

    public function pricing()
    {
        return $this->belongsTo(PilotPricing::class, 'pricing_id');
    }
}