Here’s a **step-by-step frontend setup plan** (Vue 3 + Vite + Tailwind + Vue Router + Pinia + Axios + Laravel Echo/Pusher) designed to integrate cleanly with your Laravel backend.

---

# Frontend Setup Guide (Vue 3 + Vite + Router + Pinia + Tailwind + Realtime)

## 1) Create the project

### Option A (recommended): Vue 3 + TypeScript

```bash
npm create vite@latest exchange-frontend -- --template vue-ts
cd exchange-frontend
npm install
```

### Add dependencies

```bash
npm i vue-router pinia axios laravel-echo pusher-js
npm i -D tailwindcss postcss autoprefixer
npx tailwindcss init -p
```

---

## 2) Tailwind setup

### `tailwind.config.js`

```js
export default {
  content: ["./index.html", "./src/**/*.{vue,js,ts}"],
  theme: { extend: {} },
  plugins: [],
};
```

### `src/style.css`

```css
@tailwind base;
@tailwind components;
@tailwind utilities;
```

### `src/main.ts`

```ts
import { createApp } from "vue";
import "./style.css";
import App from "./App.vue";
import router from "./router";
import { createPinia } from "pinia";

createApp(App).use(createPinia()).use(router).mount("#app");
```

---

## 3) Routing (Vue Router)

### Create `src/router/index.ts`

Routes you need:

* `/login`
* `/exchange` (main screen: order form + wallet/orders/orderbook)

```ts
import { createRouter, createWebHistory } from "vue-router";
import LoginPage from "../pages/LoginPage.vue";
import ExchangePage from "../pages/ExchangePage.vue";

const router = createRouter({
  history: createWebHistory(),
  routes: [
    { path: "/", redirect: "/exchange" },
    { path: "/login", component: LoginPage },
    { path: "/exchange", component: ExchangePage },
  ],
});

export default router;
```

---

## 4) App structure (clean + minimal)

Recommended folders:

```
src/
  api/
    http.ts
  realtime/
    echo.ts
  stores/
    auth.ts
    exchange.ts
  pages/
    LoginPage.vue
    ExchangePage.vue
  components/
    OrderForm.vue
    WalletOverview.vue
    OrdersList.vue
    OrderBook.vue
```

---

## 5) Environment variables (Vite)

Create `.env`:

```env
VITE_API_BASE_URL=http://localhost:8000
VITE_PUSHER_KEY=xxxx
VITE_PUSHER_CLUSTER=eu
```

---

## 6) Axios setup (single HTTP client)

Create `src/api/http.ts`

* Sets base URL
* Adds token header
* Handles 401 redirect to login

```ts
import axios from "axios";
import router from "../router";
import { useAuthStore } from "../stores/auth";

export const http = axios.create({
  baseURL: import.meta.env.VITE_API_BASE_URL,
});

http.interceptors.request.use((config) => {
  const auth = useAuthStore();
  if (auth.token) config.headers.Authorization = `Bearer ${auth.token}`;
  return config;
});

http.interceptors.response.use(
  (r) => r,
  async (err) => {
    if (err?.response?.status === 401) {
      const auth = useAuthStore();
      auth.logout();
      router.push("/login");
    }
    return Promise.reject(err);
  }
);
```

---

## 7) Pinia stores (Auth + Exchange)

### 7.1 Auth store (`src/stores/auth.ts`)

Responsibilities:

* login/logout
* store token + userId (needed for `private-user.{id}`)
* persist token in localStorage

Plan:

* Login endpoint returns `{ token, user: { id } }` (make backend do this)

State:

* `token: string | null`
* `userId: number | null`

Actions:

* `login(email, password)`
* `logout()`

### 7.2 Exchange store (`src/stores/exchange.ts`)

Responsibilities:

* `profile` (usd_balance + assets)
* `orders` (all past orders: open/filled/cancelled)
* `orderbook` for selected symbol (open orders)
* actions to load/refresh these
* handle event updates

State:

* `selectedSymbol: "BTC" | "ETH"`
* `profile`
* `allOrders`
* `orderbook` (buy/sell arrays)

Actions:

* `fetchProfile() -> GET /api/profile`
* `fetchOrderbook(symbol) -> GET /api/orders?symbol=BTC`
* `placeOrder(payload) -> POST /api/orders`
* `cancelOrder(id) -> POST /api/orders/{id}/cancel`
* `refreshAfterMatch(symbol)` (either patch or re-fetch)

**Recommendation for reliability:**
On `OrderMatched`, simply:

* `await fetchProfile()`
* `await fetchOrderbook(selectedSymbol)`
* `await fetchMyOrders()` (if you have an endpoint for “all my orders”, otherwise keep a local list from responses)

If you don’t have `/api/my-orders`, show orders from the store responses + include them in match payload.

---

## 8) Realtime setup (Laravel Echo + Pusher)

Create `src/realtime/echo.ts`

Responsibilities:

* create Echo instance using pusher-js
* subscribe to private channel after login
* listen for `OrderMatched` event

Important: Laravel private channels are like:

* backend: `private-user.{id}`
* frontend Echo: `.private(`user.${id}`)` **or** `.private(`user.${id}`)` depending on your channel naming.
  If backend channel is `private-user.{id}`, usually Echo uses `.private(`user.${id}`)` only when the channel is defined as `user.{id}`.
  So align backend channel naming to `user.{id}` in `routes/channels.php` and broadcast to `private-user.{id}` automatically.

**Simplest alignment:**

* In Laravel broadcast event: `new PrivateChannel("user.$id")`
* Then in Vue: `echo.private(`user.${id}`)`

---

## 9) Pages + Components

### 9.1 `LoginPage.vue`

* email/password inputs
* login button
* on success -> `/exchange`

### 9.2 `ExchangePage.vue`

Layout:

* Top: symbol selector (BTC/ETH)
* Left: `OrderForm`
* Right: `WalletOverview`
* Below: `OrdersList` + `OrderBook`

### 9.3 Components responsibilities

* `OrderForm.vue`
  emits `{symbol, side, price, amount}` -> calls store.placeOrder
* `WalletOverview.vue`
  shows `usd_balance`, assets (amount + locked_amount)
* `OrdersList.vue`
  shows “all past orders” (open/filled/cancelled), includes Cancel button for open
* `OrderBook.vue`
  shows open buy/sell orders for selected symbol

---

## 10) Startup sequence (important)

When the user enters `/exchange`:

1. Ensure authenticated (token exists)
2. Load initial data in parallel:

    * `fetchProfile()`
    * `fetchOrderbook(selectedSymbol)`
    * `fetchMyOrders()` (if available)
3. Connect Echo:

    * subscribe to `private user.{id}`
    * on `OrderMatched`:

        * refresh data (profile + orderbook + orders list)

---

## 11) UX “bonus” features (low effort, good impact)

* Toast notifications on:

    * order placed
    * order cancelled
    * match executed
* Form preview:

    * `cost = price * amount`
    * shows fee estimate (1.5% of USD volume)
* Filtering in OrdersList: by symbol/status/side

---

## 12) Final run commands

```bash
npm run dev
```

---

## 13) Integration checklist (avoid common breaks)

* ✅ Keep all money fields as **strings** (don’t use float math)
* ✅ Always refresh state after `OrderMatched` (safe path)
* ✅ Store token in localStorage and load it on startup
* ✅ Make sure backend CORS allows your Vite dev origin
* ✅ Ensure Echo auth endpoint works:

    * For Sanctum bearer token, you’ll likely use `broadcasting/auth` with Authorization header

---
