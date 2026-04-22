# Pilot Management System — Master Plan

## What This App Is

A **mobile backend** for Pilots (boosters) to manage their own boosting business.
Built with **Laravel** as the API backend, consumed by a mobile frontend.

There are no customer-facing accounts. Customers are just **pilot-owned contact references**
for logging grinds quickly without re-entering details every time.

---

## What a Pilot Can Do

- Log and track **Grinds** (boosting jobs) with progress updates
- Manage a personal **Customer list** (reusable references)
- Set and manage their **Pricing Tiers** per game
- View their **Pricing Audit History** (every change they made, with reasons)
- Auto-calculate **expected earnings** when logging a grind based on their pricing

---

## User Types

| Type | Access |
|------|--------|
| `admin` | Full system access, can verify pilots, manage master data |
| `pilot` | Own profile, own grinds, own customers, own pricing |

---

## Games Supported (v1)

| Game | Short Code |
|------|-----------|
| Call of Duty: Mobile | `CODM` |
| Mobile Legends: Bang Bang | `MLBB` |
| Valorant | `Valorant` |

---

## Rank Distributions (Master Data — Seeded)

### CODM
```
Veteran   I → V   (5 tiers)
Elite     I → V   (5 tiers)
Pro       I → V   (5 tiers)
Master    I → V   (5 tiers)
GrandMaster I → V (5 tiers)
Legendary         (1 — PEAK)
```
Total: **26 tier steps**

### Valorant
```
Iron        I → III  (3 tiers)
Bronze      I → III  (3 tiers)
Silver      I → III  (3 tiers)
Gold        I → III  (3 tiers)
Platinum    I → III  (3 tiers)
Diamond     I → III  (3 tiers)
Ascendant   I → III  (3 tiers)
Immortal    I → III  (3 tiers)
Radiant              (1 — PEAK)
```
Total: **25 tier steps**

### MLBB
> Note: MLBB counts DOWN within a group (III → I), so tier_order in DB must still go ascending (low to high) to keep the walk logic consistent.
```
Warrior     III → I  (3 tiers)
Elite       IV → I   (4 tiers)
Master      IV → I   (4 tiers)
Grandmaster V → I    (5 tiers)
Epic        V → I    (5 tiers)
Legend      V → I    (5 tiers)
Mythic               (1 — PEAK)
```
Total: **27 tier steps**

---

## Database Tables

### `users`
Single table for both auth and pilot profile data.

| Column | Type | Notes |
|--------|------|-------|
| id | bigint PK | |
| user_type | enum | `admin`, `pilot` |
| display_name | varchar | |
| email | varchar unique | |
| password | varchar | hashed |
| bio | text nullable | |
| profile_image_url | varchar nullable | |
| games_expertise | JSON nullable | e.g. `["CODM","MLBB"]` |
| is_verified | boolean | default false |
| verification_date | timestamp nullable | |
| is_active | boolean | default true |
| timestamps | | created_at, updated_at |

---

### `customers`
Pilot-owned contact list. No app login — purely a reference label.

| Column | Type | Notes |
|--------|------|-------|
| id | bigint PK | |
| pilot_id | FK → users.id | owner of this customer record |
| display_name | varchar | |
| email | varchar nullable | |
| phone | varchar nullable | |
| notes | text nullable | any extra info pilot wants to store |
| timestamps | | |

---

### `game_rank_tiers`
Master data. Seeded once, not modified by pilots.

| Column | Type | Notes |
|--------|------|-------|
| id | bigint PK | |
| game | enum | `CODM`, `MLBB`, `Valorant` |
| tier_name | varchar | e.g. `Master III`, `Legendary` |
| tier_order | int | sequential 1,2,3... per game — used for price walk |
| rank_group | varchar | e.g. `Master`, `GrandMaster` |
| tier_number | varchar nullable | `I`, `II`, `III`, `IV`, `V` — null for peaks |
| is_active | boolean | default true |

---

### `pilot_pricing`
Pilot sets price ranges per game. Multiple ranges per game are allowed and expected.

| Column | Type | Notes |
|--------|------|-------|
| id | bigint PK | |
| pilot_id | FK → users.id | |
| game | enum | |
| range_name | varchar | display label e.g. `Pro to Master` |
| tier_start_id | FK → game_rank_tiers.id | inclusive start of range |
| tier_end_id | FK → game_rank_tiers.id | inclusive end of range |
| price_per_tier | decimal(10,2) | cost per single tier step in this range |
| major_rank_crossing_fee | decimal(10,2) | flat bonus when crossing into a new rank group |
| is_active | boolean | default true |
| display_order | int | for UI ordering |
| timestamps | | |

---

### `pricing_audit_log`
Every pricing create/update/deactivate is logged here automatically.

| Column | Type | Notes |
|--------|------|-------|
| id | bigint PK | |
| pilot_id | FK → users.id | |
| pricing_id | FK → pilot_pricing.id | |
| action | enum | `created`, `updated`, `deactivated`, `reactivated` |
| old_price_per_tier | decimal nullable | |
| new_price_per_tier | decimal nullable | |
| old_crossing_fee | decimal nullable | |
| new_crossing_fee | decimal nullable | |
| reason | text nullable | pilot's reason for change |
| notes | text nullable | extra notes |
| created_at | timestamp | |

---

### `grinds`
Core logging table. Each grind = one boosting job.

| Column | Type | Notes |
|--------|------|-------|
| id | bigint PK | |
| grind_number | varchar unique | auto-generated e.g. `GRD-0001` |
| pilot_id | FK → users.id | |
| customer_id | FK → customers.id nullable | optional — pilot may log without a customer |
| game | enum | |
| service_type | enum | `rank_boost`, `win_count` |
| starting_tier_id | FK → game_rank_tiers.id nullable | for rank_boost |
| target_tier_id | FK → game_rank_tiers.id nullable | for rank_boost |
| total_tiers | int default 0 | computed on log |
| target_wins | int nullable | for win_count |
| price_per_win | decimal nullable | for win_count |
| base_price | decimal(10,2) | calculated by system |
| final_price | decimal(10,2) | base_price (no urgency multiplier for now) |
| status | enum | `pending`, `in_progress`, `completed`, `cancelled` |
| progress_percentage | int default 0 | |
| current_tier | varchar nullable | where the grind is at right now |
| account_username | varchar nullable | customer's game account |
| special_instructions | text nullable | |
| started_at | timestamp nullable | |
| completed_at | timestamp nullable | |
| timestamps | | |

---

## Pricing — How It Actually Works

When a pilot logs a grind (rank_boost), the system:

1. Gets all the pilot's **active pricing ranges** for that game
2. Looks up the **tier_order** of start and target tiers
3. **Walks step by step** from start → target tier_order
4. At each step, finds which pricing range covers that step
5. Adds `price_per_tier` for that step
6. If the step **crosses into a new rank_group**, adds `major_rank_crossing_fee` once
7. Sums everything = `base_price`

### Example (CODM, pilot's pricing as set):
```
Veteran–Elite range:  ₱5/tier
Elite–Pro range:      ₱5/tier
Pro–Master range:     ₱10/tier
Master–GrandMaster:   ₱20/tier
GrandMaster–Legendary:₱40/tier
```

**Grind: Master I → Legendary**
```
Master I   → Master II   = ₱20
Master II  → Master III  = ₱20
Master III → Master IV   = ₱20
Master IV  → Master V    = ₱20
Master V   → GM I        = ₱20  (+ crossing fee if set)
GM I       → GM II       = ₱40
GM II      → GM III      = ₱40
GM III     → GM IV       = ₱40
GM IV      → GM V        = ₱40
GM V       → Legendary   = ₱40  (+ crossing fee if set)
─────────────────────────────
Total = ₱300
```

---

## API Structure (High Level)

### Auth
```
POST /api/auth/login
POST /api/auth/logout
GET  /api/auth/me
```

### Profile
```
GET  /api/profile
PUT  /api/profile
```

### Customers
```
GET    /api/customers
POST   /api/customers
GET    /api/customers/{id}
PUT    /api/customers/{id}
DELETE /api/customers/{id}
```

### Grinds
```
GET    /api/grinds
POST   /api/grinds
GET    /api/grinds/{id}
PUT    /api/grinds/{id}/progress
POST   /api/grinds/{id}/complete
DELETE /api/grinds/{id}
```

### Pricing
```
GET    /api/pricing
POST   /api/pricing
PUT    /api/pricing/{id}
DELETE /api/pricing/{id}
```

### Pricing Audit
```
GET  /api/pricing/audit
```

### Reference Data
```
GET  /api/games/ranks/{game}
```

### Price Calculator
```
POST /api/calculator/rank-boost
Body: { game, starting_tier_id, target_tier_id }
Response: { total_tiers, base_price, breakdown[] }
```

---

## Build Order

| Phase | What |
|-------|------|
| 1 | Laravel project setup + Sanctum install |
| 2 | Migrations (all tables) |
| 3 | Seeders (game_rank_tiers for CODM, MLBB, Valorant) |
| 4 | Models + Relationships |
| 5 | Auth (login, logout, me) |
| 6 | Profile endpoints |
| 7 | Customers CRUD |
| 8 | Pricing CRUD + audit log auto-write |
| 9 | Price Calculator service class |
| 10 | Grinds CRUD (uses calculator on create) |
| 11 | Reference data endpoint |

---

## Notes & Decisions

- `urgency_multiplier` removed for now — pricing is straightforward
- Customers have no app login — pilot-owned data only
- `grind_number` auto-generated on create (GRD-0001 format)
- Pricing audit log is written automatically by the system on every pricing change — pilot doesn't manually trigger it
- MLBB ranks count down within a group (V → I = higher rank) but `tier_order` in DB always goes ascending so the walk logic stays consistent across all games