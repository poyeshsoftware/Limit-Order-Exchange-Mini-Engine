# Setup Guide — Limit-Order Exchange Mini Engine (Laravel + Vue)

This document is the **single source of truth** for setting up and implementing the assessment project end-to-end.

**Scope focus:** Backend (Laravel API) with strict **atomicity**, **race safety**, **correct commission**, and **real-time broadcasts**.

---

## 0) Non‑negotiable requirements from the assessment

### Tech stack
- Backend: **Laravel (latest stable)**
- Frontend: **Vue.js (latest stable, Composition API)**
- Database: **MySQL or PostgreSQL**
- Real-time: **Pusher via Laravel Broadcasting**
- Matching: **Job-based matching recommended**
- Data integrity: **Concurrency-safe / atomic**

### Database tables (keep these names + columns)
> Do **not** rename columns; follow the prompt’s naming.

- `users` — default Laravel columns + `balance` (decimal, USD funds)
- `assets` — `user_id`, `symbol`, `amount`, `locked_amount`
- `orders` — `user_id`, `symbol`, `side`, `price`, `amount`, `status` (open=1, filled=2, cancelled=3), timestamps
- `trades` — optional (bonus)

### Mandatory API endpoints
- `GET  /api/profile` — returns authenticated user USD + assets
- `GET  /api/orders?symbol=BTC` — returns **open** orders for orderbook
- `POST /api/orders` — creates a limit order
- `POST /api/orders/{id}/cancel` — cancels an open order and releases locked funds/assets
- Matching trigger: **internal**, preferably job-based (e.g., `MatchOrderJob`)

### Matching rules (Full match only)
- New BUY matches first SELL where `sell.price <= buy.price`
- New SELL matches first BUY where `buy.price >= sell.price`
- **Full match only** (no partial fills required)

### Commission (must stay)
- `fee = matched_usd_volume * 0.015` (1.5%)
- Pick one consistent fee policy. Recommended:
  - **Buyer pays fee in USD** (deduct from buyer balance at settlement)

---

## 1) Prerequisites

### System tools
- Docker + Docker Compose v2
- PHP 8.2+ (8.3 recommended)
- Composer
- Node.js 18+ (20+ recommended)

### Accounts / keys
- Pusher account + app keys (APP_ID, KEY, SECRET, CLUSTER)

---

## 2) Infrastructure: Docker (DB + Redis)

### 2.1 Add `docker-compose-dev.yaml`
Use the provided file (from our chat). Ports in that file:
- MySQL dev: `21150 -> 3306`
- phpMyAdmin dev: `21151 -> 80`
- Redis: `21152 -> 6379`
- MySQL test: `21153 -> 3306`
- phpMyAdmin test: `21154 -> 80`

### 2.2 Create expected folders (optional but recommended)
```bash
mkdir -p docker-compose/mysql/{dump,conf,mysql}
```

### 2.3 Start containers
```bash
docker compose -f docker-compose-dev.yaml up -d
docker compose -f docker-compose-dev.yaml ps
```

### 2.4 Verify quickly
- Dev DB: http://localhost:21151
- Test DB: http://localhost:21154
- Redis: `localhost:21152`

---

## 3) Laravel project setup

### 3.1 Install dependencies
```bash
composer install
cp .env.example .env
php artisan key:generate
```

### 3.2 Configure `.env` (dev)
```env
APP_NAME="Exchange Mini Engine"
APP_ENV=local
APP_DEBUG=true
APP_URL=http://localhost:8000

DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=21150
DB_DATABASE=exchange
DB_USERNAME=exchange_user
DB_PASSWORD=exchange_pass

QUEUE_CONNECTION=redis
REDIS_HOST=127.0.0.1
REDIS_PORT=21152

BROADCAST_CONNECTION=pusher
PUSHER_APP_ID=xxxx
PUSHER_APP_KEY=xxxx
PUSHER_APP_SECRET=xxxx
PUSHER_APP_CLUSTER=eu
```

---

## 4) Install required Laravel packages

### 4.1 Sanctum (auth)
```bash
composer require laravel/sanctum
php artisan sanctum:install
```

### 4.2 Horizon (queue monitoring)
```bash
composer require laravel/horizon
php artisan horizon:install
```

### 4.3 Pusher server SDK
```bash
composer require pusher/pusher-php-server
```

### 4.4 Quality tooling (recommended)
```bash
composer require --dev laravel/pint nunomaduro/larastan
composer require --dev pestphp/pest pestphp/pest-plugin-laravel
php artisan pest:install
```

---

## 5) Database migrations (schema)

> **Core rule:** use DECIMAL for all money/amount/price. Avoid floats.

### 5.1 `users` table change
Add column:
- `balance` — `decimal` (USD funds), default `0`

Recommended: `decimal(20, 8)` (safe for fees and fractional USD).

Example migration snippet:
```php
Schema::table('users', function (Blueprint $table) {
    $table->decimal('balance', 20, 8)->default(0);
});
```

### 5.2 `assets` table (required)
Columns:
- `user_id` (FK users.id)
- `symbol` (string, e.g., BTC/ETH)
- `amount` decimal
- `locked_amount` decimal

Constraints:
- unique index `(user_id, symbol)`

Example:
```php
Schema::create('assets', function (Blueprint $table) {
    $table->id();
    $table->foreignId('user_id')->constrained()->cascadeOnDelete();
    $table->string('symbol', 10);
    $table->decimal('amount', 20, 8)->default(0);
    $table->decimal('locked_amount', 20, 8)->default(0);
    $table->timestamps();

    $table->unique(['user_id', 'symbol']);
});
```

### 5.3 `orders` table (required)
Columns:
- `user_id`
- `symbol`
- `side` (buy/sell)
- `price` decimal
- `amount` decimal
- `status` tinyint (1/2/3)
- timestamps

Indexes (recommended):
- `(symbol, status, side, price, created_at)` — supports “best price + FIFO” queries

Example:
```php
Schema::create('orders', function (Blueprint $table) {
    $table->id();
    $table->foreignId('user_id')->constrained()->cascadeOnDelete();
    $table->string('symbol', 10);
    $table->string('side', 4); // "buy" or "sell"
    $table->decimal('price', 20, 8);
    $table->decimal('amount', 20, 8);
    $table->unsignedTinyInteger('status')->default(1); // 1 open, 2 filled, 3 cancelled
    $table->timestamps();

    $table->index(['symbol', 'status', 'side', 'price', 'created_at']);
});
```

### 5.4 `trades` table (optional bonus)
Keep it small and auditable:
- `buy_order_id`, `sell_order_id`
- `symbol`, `price`, `amount`
- `usd_volume`, `fee_usd`
- timestamps

---

## 6) Models (relationships + casts)

### 6.1 Models to create
- `Asset`
- `Order`
- `Trade` (optional)

### 6.2 Relationships
- `User hasMany Asset`
- `User hasMany Order`
- `Asset belongsTo User`
- `Order belongsTo User`

### 6.3 Decimal casts (recommended)
Ensure decimals serialize consistently (strings) to avoid JS float rounding surprises:
```php
protected $casts = [
  'balance' => 'decimal:8',
];
```
Similarly for `amount`, `locked_amount`, `price`.

---

## 7) API layer (routes + controllers + requests)

### 7.1 Routes (`routes/api.php`)
All core routes should be protected by Sanctum:
```php
Route::middleware('auth:sanctum')->group(function () {
    Route::get('/profile', ProfileController::class);
    Route::get('/orders', [OrderController::class, 'index']);
    Route::post('/orders', [OrderController::class, 'store']);
    Route::post('/orders/{order}/cancel', [OrderController::class, 'cancel']);
});
```

### 7.2 Requests (validation)
Create `StoreOrderRequest`:
- `symbol`: in `BTC,ETH`
- `side`: `buy,sell`
- `price`: numeric > 0
- `amount`: numeric > 0

### 7.3 Responses (stable JSON shapes)
Recommended shapes:
- `GET /api/profile`
```json
{
  "usd_balance": "1000.00000000",
  "assets": [
    {"symbol":"BTC","amount":"0.01000000","locked_amount":"0.00000000"}
  ]
}
```

- `GET /api/orders?symbol=BTC` (open only)
```json
{
  "buy":  [ ... ],
  "sell": [ ... ]
}
```

---

## 8) Core business logic (the heart of the assessment)

### 8.1 Architecture (clean separation)
Keep controllers thin. Put business logic in:
- `app/Actions/Orders/CreateOrderAction.php`
- `app/Actions/Orders/CancelOrderAction.php`
- `app/Actions/Matching/MatchOrderAction.php`
- `app/Jobs/MatchOrderJob.php`

(Services/Actions are both acceptable; “actions” are usually cleaner for assessments.)

### 8.2 Order placement rules (atomic reservation)

#### BUY order placement
Inside a single DB transaction:
1. Lock the `users` row: `lockForUpdate()`
2. `cost = amount * price`
3. Ensure `users.balance >= cost`
4. Deduct `users.balance -= cost`
5. Create order `status=OPEN (1)`
6. Commit
7. Dispatch `MatchOrderJob(order_id)` **after commit**

#### SELL order placement
Inside a single DB transaction:
1. Lock the `assets` row for `(user_id, symbol)` with `lockForUpdate()`
2. Ensure `assets.amount >= amount`
3. Move funds:
   - `assets.amount -= amount`
   - `assets.locked_amount += amount`
4. Create order `OPEN`
5. Commit
6. Dispatch match job after commit

> Important: If user has no `assets` row for that symbol, treat as 0 available and reject sells.

### 8.3 Matching rules (full match only)
A single match attempt is enough (per prompt “Matches new orders with the first valid counter order”).

For incoming BUY:
- Find first SELL:
  - `symbol = incoming.symbol`
  - `status = OPEN`
  - `sell.price <= buy.price`
  - order by: `price ASC`, then `created_at ASC`

For incoming SELL:
- Find first BUY:
  - `buy.price >= sell.price`
  - order by: `price DESC`, then `created_at ASC`

**Full match only:**
- Only proceed if `counter.amount == incoming.amount`
- Otherwise: no match

### 8.4 Settlement (atomic + locked)
Inside one DB transaction:
1. Lock incoming order row (FOR UPDATE)
2. Lock counter order row (FOR UPDATE)
3. Re-check both are still OPEN
4. Define execution price (recommended: counter order price)
5. Compute:
   - `usd_volume = amount * trade_price`
   - `fee_usd = usd_volume * 0.015`
6. Apply transfers (fee charged to buyer in USD):
   - Buyer receives asset: `buyer_asset.amount += amount`
   - Buyer pays fee: `buyer.balance -= fee_usd` (lock buyer user row)
   - Seller releases asset: `seller_asset.locked_amount -= amount`
   - Seller receives USD: `seller.balance += usd_volume` (lock seller user row)
7. Mark both orders `FILLED (2)`
8. Insert `trades` record (optional)
9. Commit

---

## 9) Broadcasting (Pusher) — OrderMatched event

### 9.1 Create event: `OrderMatched`
- Implements `ShouldBroadcast`
- Broadcast to **both**:
  - `private-user.{buyer_id}`
  - `private-user.{seller_id}`

### 9.2 Channel authorization (`routes/channels.php`)
```php
Broadcast::channel('user.{id}', function ($user, $id) {
    return (int)$user->id === (int)$id;
});
```

### 9.3 Payload strategy (recommended)
Simplest + safest for frontend consistency:
- Broadcast minimal payload:
  - `symbol`, `order_ids`, `type=matched`
- Frontend then refetches:
  - `/api/profile`
  - `/api/orders?symbol=...`

This avoids mistakes in delta math on the client.

---

## 10) Running workers (queues + Horizon)

### 10.1 Run the API server
```bash
php artisan serve
```

### 10.2 Run Horizon
```bash
php artisan horizon
```

---

## 11) Testing strategy (TDD + integrity)

### 11.1 `.env.testing`
Configure DB test port:
```env
DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=21153
DB_DATABASE=exchange_test
DB_USERNAME=exchange_user_test
DB_PASSWORD=exchange_pass_test

QUEUE_CONNECTION=sync
BROADCAST_CONNECTION=log
```

### 11.2 Must-have tests (high impact)
- Buy order reserves USD (balance decreases)
- Sell order locks asset (amount decreases, locked_amount increases)
- Cancel buy refunds USD
- Cancel sell releases locked asset
- Matching executes:
  - orders become filled
  - balances + assets update correctly
  - fee equals exactly 1.5% of USD volume
- Authorization:
  - users cannot cancel others’ orders
- Concurrency safety (bonus signal):
  - two simultaneous orders can’t overspend balance (lockForUpdate correctness)

Run:
```bash
php artisan test
```

---

## 12) Implementation order (recommended timeline)

1. Docker compose up (db + redis)
2. Laravel base config (.env, key)
3. Sanctum + login/logout endpoints
4. Migrations + models + factories
5. `/api/profile`
6. Order placement (store) with atomic reservation
7. Cancel endpoint
8. Matching job + action (with locks + transactions)
9. Broadcasting OrderMatched event
10. Tests (keep adding as you go)
11. README + final cleanup (pint, larastan)

---

## 13) Common pitfalls (avoid these)
- ❌ Using float/double for money → use DECIMAL + careful arithmetic
- ❌ Matching inside controller synchronously → queue job
- ❌ No locking → race conditions in balances/assets
- ❌ Overcomplicating (partial fills / extra tables) → follow prompt exactly
- ❌ Broadcasting before commit → send events after DB commit

---

## 14) Quick “How to run” summary

```bash
# infra
docker compose -f docker-compose-dev.yaml up -d

# backend
composer install
cp .env.example .env
php artisan key:generate
php artisan migrate
php artisan serve

# queue
php artisan horizon

# tests
php artisan test
```

--- 
