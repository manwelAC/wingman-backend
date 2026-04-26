# Star-Based Pricing Implementation

## Overview

Implemented **star-based pricing system** to support Mobile Legends' unique per-star pricing model while maintaining compatibility with CODM and Valorant:

- **Mobile Legends**: 3-5 stars per tier (custom ratio per rank group) ⭐ **PRIMARY REASON FOR CHANGE**
- **CODM**: 1 star per tier (standard progression)
- **Valorant**: 1 star per tier (same as CODM - no special handling needed)

We moved from `price_per_tier` to `price_per_star` specifically to accommodate Mobile Legends' flexible star distribution across different rank groups. CODM and Valorant simply use 1 star per tier in this system.

---

## Database Schema Changes

### 1. Added `stars_per_tier` to `game_rank_tiers`

```sql
ALTER TABLE game_rank_tiers ADD COLUMN stars_per_tier INTEGER DEFAULT 1;
```

Migration: `2026_04_26_add_stars_per_tier_to_game_rank_tiers.php`

**Example Data:**
```
CODM
├── Veteran I → V: 1 star each
├── Elite I → V: 1 star each
└── Legendary: 1 star

Mobile Legends
├── Warrior I → III: 3 stars each
├── Elite I → IV: 4 stars each
├── Master I → IV: 4 stars each
├── Grandmaster I → V: 5 stars each
├── Epic I → V: 5 stars each
├── Legend I → V: 5 stars each
└── Mythic: 1 star
```

### 2. Renamed Column: `price_per_tier` → `price_per_star`

**In `pilot_pricing` table:**
```sql
ALTER TABLE pilot_pricing RENAME COLUMN price_per_tier TO price_per_star;
```

Migration: `2026_04_26_rename_price_per_tier_to_price_per_star.php`

**In `pricing_audit_log` table:**
```sql
ALTER TABLE pricing_audit_log 
  RENAME COLUMN old_price_per_tier TO old_price_per_star,
  RENAME COLUMN new_price_per_tier TO new_price_per_star;
```

Migration: `2026_04_26_rename_pricing_audit_log_columns.php`

---

## Model Updates

### `GameRankTier.php`
```php
protected $fillable = [
    'game',
    'tier_name',
    'tier_order',
    'rank_group',
    'tier_number',
    'stars_per_tier',  // NEW
    'is_active',
];
```

### `PilotPricing.php`
```php
protected $fillable = [
    'pilot_id',
    'game',
    'range_name',
    'tier_start_id',
    'tier_end_id',
    'price_per_star',  // CHANGED from price_per_tier
    'major_rank_crossing_fee',
    'is_active',
    'display_order',
];

protected $casts = [
    'price_per_star' => 'decimal:2',  // CHANGED
    'major_rank_crossing_fee' => 'decimal:2',
    'is_active' => 'boolean',
];
```

### `PricingAuditLog.php`
```php
protected $fillable = [
    'pilot_id',
    'pricing_id',
    'action',
    'old_price_per_star',  // CHANGED
    'new_price_per_star',  // CHANGED
    'old_crossing_fee',
    'new_crossing_fee',
    'reason',
    'notes',
    'created_at',
];

protected $casts = [
    'old_price_per_star' => 'decimal:2',  // CHANGED
    'new_price_per_star' => 'decimal:2',  // CHANGED
    'old_crossing_fee' => 'decimal:2',
    'new_crossing_fee' => 'decimal:2',
    'created_at' => 'datetime',
];
```

---

## API Changes

### Store Pricing (POST `/api/pricing`)

**Request:**
```json
{
  "game": "MLBB",
  "range_name": "Warrior to Elite",
  "tier_start_id": 1,
  "tier_end_id": 7,
  "price_per_star": 50,
  "major_rank_crossing_fee": 100,
  "display_order": 1,
  "reason": "Initial pricing setup"
}
```

**Changed from:** `price_per_tier` → `price_per_star`

### Update Pricing (PUT `/api/pricing/{id}`)

**Request:**
```json
{
  "price_per_star": 55,
  "reason": "Adjusted based on demand"
}
```

### Validation

```php
$request->validate([
    'price_per_star' => 'required|numeric|min:0',  // CHANGED
    ...
]);
```

---

## Price Calculation

### `PriceCalculatorService.php`

**Old Logic:**
```php
$segmentPrice = $tiersInRange * $range->price_per_tier;
// Cost = number of tiers × price per tier
```

**New Logic:**
```php
$totalStarsInRange = 0;
for ($order = $currentOrder; $order < $rangeEndOrder; $order++) {
    $tier = GameRankTier::where('tier_order', $order)
        ->where('game', $game)
        ->first();
    if ($tier) {
        $totalStarsInRange += $tier->stars_per_tier;
    }
}

$segmentPrice = $totalStarsInRange * $range->price_per_star;
// Cost = total stars × price per star
```

### Response Format

**Old:**
```json
{
  "range_name": "Elite to Master",
  "tiers": 5,
  "price_per_tier": 50,
  "subtotal": 250
}
```

**New:**
```json
{
  "range_name": "Elite to Master",
  "tiers": 8,
  "total_stars": 32,
  "price_per_star": 50,
  "subtotal": 1600
}
```

---

## Example Pricing Scenarios

### CODM Boost (1 star per tier - Standard/Original Model)
```
Start: Veteran I (tier 1)
Target: Elite I (tier 6)
Tiers: 5
Stars: 5 × 1 = 5 stars
Price per star: $30
Total: 5 × $30 = $150

Note: CODM uses 1 star per tier (standard progression model)
```

### Valorant Boost (1 star per tier - Same as CODM)
```
Start: Iron I (tier 1)
Target: Immortal I (tier 22)
Tiers: 21
Stars: 21 × 1 = 21 stars
Price per star: $40
Total: 21 × $40 = $840

Note: Valorant uses the same 1:1 tier-to-star ratio as CODM
```

### Mobile Legends Boost (3-5 stars per tier - Requires Star System)
```
Start: Warrior III (tier 1, 3 stars each)
Target: Elite I (tier 7, 4 stars each)

Breakdown:
├── Warrior III → I: 3 tiers × 3 stars = 9 stars
├── Elite IV → I: 4 tiers × 4 stars = 16 stars
└── Total: 25 stars

Price per star: $50
Total: 25 × $50 = $1,250

Note: MLBB is why we implemented star-based pricing
Each rank group has different star values per tier
```

---

## Audit Trail

All pricing changes are automatically logged with **old and new values**:

```json
{
  "pilot_id": 5,
  "pricing_id": 12,
  "action": "updated",
  "old_price_per_star": 50,
  "new_price_per_star": 55,
  "old_crossing_fee": 100,
  "new_crossing_fee": 100,
  "reason": "Adjusted based on market demand",
  "created_at": "2026-04-26 09:36:51"
}
```

---

## Game-Specific Star Ratios

| Game | Rank Group | Stars Per Tier | Tiers | Total Stars | Notes |
|------|-----------|----------------|-------|-------------|-------|
| **CODM** | All | 1 | 26 | 26 | Standard model - original pricing |
| **Valorant** | All | 1 | 25 | 25 | Same as CODM - standard tier-to-star |
| **MLBB** | Warrior | 3 | 3 | 9 | ⭐ Requires star system - custom ratio |
| **MLBB** | Elite | 4 | 4 | 16 | ⭐ Requires star system - custom ratio |
| **MLBB** | Master | 4 | 4 | 16 | ⭐ Requires star system - custom ratio |
| **MLBB** | Grandmaster | 5 | 5 | 25 | ⭐ Requires star system - custom ratio |
| **MLBB** | Epic | 5 | 5 | 25 | ⭐ Requires star system - custom ratio |
| **MLBB** | Legend | 5 | 5 | 25 | ⭐ Requires star system - custom ratio |

---

## Migrations Applied

1. `2026_04_26_add_stars_per_tier_to_game_rank_tiers.php`
2. `2026_04_26_rename_price_per_tier_to_price_per_star.php`
3. `2026_04_26_rename_pricing_audit_log_columns.php`

Run with:
```bash
php artisan migrate
```

Or reset database with:
```bash
php artisan migrate:refresh --seed
```

---

## Benefits

✅ **Mobile Legends Support**: Star-based pricing accommodates MLBB's variable star distribution per rank group  
✅ **CODM Compatible**: Works seamlessly with CODM's standard 1:1 tier-to-star ratio  
✅ **Valorant Compatible**: Valorant uses the same standard model as CODM  
✅ **Flexible Progression**: Can adjust stars per tier without code changes  
✅ **Accurate Billing**: Charges based on actual difficulty (stars), not just tier count  
✅ **Audit Trail**: Full history of pricing changes  
✅ **Scalable**: Easy to add new games with custom star distributions  

---

## Frontend Integration

**Pricing Create/Update Request:**
```javascript
const pricing = {
  game: 'MLBB',
  range_name: 'Warrior to Elite',
  tier_start_id: 1,
  tier_end_id: 7,
  price_per_star: 50,  // CHANGED from price_per_tier
  major_rank_crossing_fee: 100,
  display_order: 1
};

fetch('/api/pricing', {
  method: 'POST',
  headers: { 'Authorization': `Bearer ${token}` },
  body: JSON.stringify(pricing)
});
```

**Calculating Cost on Frontend:**
```javascript
// Get stars for a range
const totalStars = tierDataList.reduce((sum, tier) => {
  return sum + tier.stars_per_tier;
}, 0);

const totalCost = totalStars * pricing.price_per_star;
```