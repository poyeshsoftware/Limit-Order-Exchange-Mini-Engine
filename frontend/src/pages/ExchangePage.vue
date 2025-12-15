<script setup lang="ts">
import { onBeforeUnmount, onMounted, ref, watch } from "vue";
import { useRouter } from "vue-router";

import OrderBook from "../components/OrderBook.vue";
import OrderForm from "../components/OrderForm.vue";
import OrdersList from "../components/OrdersList.vue";
import WalletOverview from "../components/WalletOverview.vue";
import { disconnectEcho, connectEcho } from "../realtime/echo";
import { useAuthStore } from "../stores/auth";
import { useExchangeStore } from "../stores/exchange";

const router = useRouter();
const auth = useAuthStore();
const exchange = useExchangeStore();
const lastMatchMessage = ref<string | null>(null);
let pollInterval: number | null = null;

function startPolling(): void {
  if (pollInterval !== null) return;

  pollInterval = window.setInterval(() => {
    if (exchange.isLoading) return;
    exchange.refreshAll().catch(() => {});
  }, 2000);
}

function stopPolling(): void {
  if (pollInterval === null) return;
  window.clearInterval(pollInterval);
  pollInterval = null;
}

onMounted(async () => {
  await exchange.refreshAll();

  if (auth.token && auth.userId) {
    const echo = connectEcho(auth.token);

    if (echo) {
      echo.private(`user.${auth.userId}`).listen("OrderMatched", async (payload: any) => {
        lastMatchMessage.value = `Matched ${payload?.symbol ?? ""}`.trim();
        await exchange.refreshAll();
      });
    } else {
      startPolling();
    }
  }
});

watch(
  () => exchange.selectedSymbol,
  async (symbol) => {
    await exchange.fetchOrderBook(symbol);
  }
);

onBeforeUnmount(() => {
  stopPolling();
  disconnectEcho();
});

function logout(): void {
  auth.logout();
  stopPolling();
  disconnectEcho();
  router.push("/login");
}
</script>

<template>
  <div class="mx-auto flex min-h-full w-full max-w-6xl flex-col gap-6 px-4 py-10">
    <div class="flex flex-col gap-4 md:flex-row md:items-center md:justify-between">
      <div>
        <h1 class="text-2xl font-semibold">Exchange</h1>
        <p class="mt-1 text-sm text-slate-400">Full-match only. Fee: 1.5% (buyer pays in USD).</p>
      </div>

      <div class="flex items-center gap-3">
        <select v-model="exchange.selectedSymbol" class="rounded-lg bg-slate-900 px-3 py-2 text-sm">
          <option value="BTC">BTC</option>
          <option value="ETH">ETH</option>
        </select>

        <button
          class="rounded-lg bg-slate-800 px-3 py-2 text-sm font-semibold text-slate-100 hover:bg-slate-700"
          type="button"
          @click="logout"
        >
          Logout
        </button>
      </div>
    </div>

    <div v-if="lastMatchMessage" class="rounded-lg border border-emerald-900 bg-emerald-950/30 px-4 py-3 text-sm">
      {{ lastMatchMessage }}
    </div>

    <div class="grid gap-6 lg:grid-cols-2">
      <OrderForm />
      <WalletOverview />
    </div>

    <div class="grid gap-6 lg:grid-cols-2">
      <OrdersList />
      <OrderBook />
    </div>
  </div>
</template>
