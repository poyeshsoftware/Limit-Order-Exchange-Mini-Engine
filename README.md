# Limit-Order Exchange Mini Engine (Laravel + Vue)

Monorepo with:
- `backend/` — Laravel API (matching engine + queues + broadcasting)
- `frontend/` — Vue 3 (Vite) UI

## Prerequisites
- Docker + Docker Compose v2
- PHP 8.2+ and Composer
- Node.js 18+ (20+ recommended)

## 1) Start infrastructure (DB + Redis)
From repo root:
```bash
docker compose -f docker-compose-dev.yaml up -d
docker compose -f docker-compose-dev.yaml ps
```

Ports:
- MySQL (dev): `21150`
- phpMyAdmin (dev): `21151` (http://localhost:21151)
- Redis: `21152`
- MySQL (test): `21153`
- phpMyAdmin (test): `21154` (http://localhost:21154)

## 2) Backend (Laravel)
```bash
cd backend
composer install
cp .env.example .env
php artisan key:generate
php artisan migrate:fresh --seed
php artisan serve --port=8000
```

Run the queue worker (required for matching):
```bash
cd backend
php artisan queue:work
```

Demo users (password: `password`) are seeded in `local` env:
- `buyer@example.com`
- `seller@example.com`
- `maker.buy@example.com`
- `maker.sell@example.com`

Notes:
- The backend uses `REDIS_CLIENT=predis` by default (works without the PHP `redis` extension).
- API base URL: `http://localhost:8000`

## 3) Frontend (Vue)
```bash
cd frontend
npm install
cp .env.example .env
npm run dev
```

Open: `http://localhost:5173`

If you want realtime updates, set `VITE_PUSHER_KEY` and `VITE_PUSHER_CLUSTER` in `frontend/.env` and restart Vite.

## 4) Realtime (Pusher)
To enable realtime order-matched broadcasts you need a Pusher Channels app:
1. Create an app on https://pusher.com/channels
2. Copy credentials into:
   - `backend/.env`: `PUSHER_APP_ID`, `PUSHER_APP_KEY`, `PUSHER_APP_SECRET`, `PUSHER_APP_CLUSTER`
   - `frontend/.env`: `VITE_PUSHER_KEY`, `VITE_PUSHER_CLUSTER`
3. Restart backend queue worker and frontend dev server.

If you don’t want realtime for local dev:
- Set `backend/.env` `BROADCAST_CONNECTION=log`
- Keep `frontend/.env` `VITE_PUSHER_KEY=xxxx` to disable Echo (the UI will poll for updates instead).

## 5) Tests
The test DB container runs on port `21153` and `backend/.env.testing` is already configured.
```bash
cd backend
php artisan test
```

## 6) Useful API endpoints
All endpoints are under `/api` and protected by Sanctum (except login).
- `POST /api/login`
- `POST /api/logout`
- `GET /api/profile`
- `GET /api/my-orders`
- `GET /api/orders?symbol=BTC`
- `POST /api/orders`
- `POST /api/orders/{id}/cancel`
