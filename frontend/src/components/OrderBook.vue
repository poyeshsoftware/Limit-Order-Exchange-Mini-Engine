<script setup lang="ts">
import { computed } from "vue";

import { useExchangeStore } from "../stores/exchange";

const exchange = useExchangeStore();

const buy = computed(() => exchange.orderBook.buy);
const sell = computed(() => exchange.orderBook.sell);
</script>

<template>
  <section class="rounded-xl border border-slate-800 bg-slate-900/40 p-6">
    <h2 class="text-lg font-semibold">Order Book ({{ exchange.selectedSymbol }})</h2>

    <div class="mt-4 grid gap-4 lg:grid-cols-2">
      <div class="rounded-lg border border-slate-800 bg-slate-950">
        <div class="border-b border-slate-800 px-3 py-2 text-xs font-semibold uppercase tracking-wide text-slate-400">
          Buy
        </div>
        <div class="divide-y divide-slate-800">
          <div v-if="buy.length === 0" class="px-3 py-3 text-sm text-slate-400">No bids.</div>
          <div v-for="row in buy" :key="row.id" class="flex items-center justify-between px-3 py-2 text-sm">
            <div class="font-mono text-emerald-400">{{ row.price }}</div>
            <div class="font-mono text-slate-200">{{ row.amount }}</div>
          </div>
        </div>
      </div>

      <div class="rounded-lg border border-slate-800 bg-slate-950">
        <div class="border-b border-slate-800 px-3 py-2 text-xs font-semibold uppercase tracking-wide text-slate-400">
          Sell
        </div>
        <div class="divide-y divide-slate-800">
          <div v-if="sell.length === 0" class="px-3 py-3 text-sm text-slate-400">No asks.</div>
          <div v-for="row in sell" :key="row.id" class="flex items-center justify-between px-3 py-2 text-sm">
            <div class="font-mono text-rose-400">{{ row.price }}</div>
            <div class="font-mono text-slate-200">{{ row.amount }}</div>
          </div>
        </div>
      </div>
    </div>
  </section>
</template>

