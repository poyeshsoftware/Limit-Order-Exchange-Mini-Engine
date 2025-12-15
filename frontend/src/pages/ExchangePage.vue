<script setup lang="ts">
import { onMounted } from "vue";

import { useExchangeStore } from "../stores/exchange";

const exchange = useExchangeStore();

onMounted(async () => {
  await exchange.refreshAll();
});
</script>

<template>
  <div class="mx-auto flex min-h-full w-full max-w-6xl flex-col gap-6 px-4 py-10">
    <div class="flex items-center justify-between">
      <h1 class="text-2xl font-semibold">Exchange</h1>
      <select v-model="exchange.selectedSymbol" class="rounded-lg bg-slate-900 px-3 py-2 text-sm">
        <option value="BTC">BTC</option>
        <option value="ETH">ETH</option>
      </select>
    </div>

    <div class="rounded-xl border border-slate-800 bg-slate-900/40 p-6">
      <div class="text-sm text-slate-400">
        UI components (order form, wallet, orderbook, orders list) are added in the next step.
      </div>
    </div>
  </div>
</template>

