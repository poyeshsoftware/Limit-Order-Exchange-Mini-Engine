<script setup lang="ts">
import { computed } from "vue";

import { useExchangeStore } from "../stores/exchange";

const exchange = useExchangeStore();

const trades = computed(() => exchange.trades);

function formatTime(iso: string): string {
  const date = new Date(iso);
  if (Number.isNaN(date.getTime())) return iso;
  return date.toLocaleString();
}
</script>

<template>
  <section class="rounded-xl border border-slate-800 bg-slate-900/40 p-6">
    <div class="flex items-center justify-between">
      <h2 class="text-lg font-semibold">Trade History ({{ exchange.selectedSymbol }})</h2>
      <div class="text-xs text-slate-400">Latest {{ trades.length }}</div>
    </div>

    <div v-if="trades.length === 0" class="mt-4 text-sm text-slate-400">No trades yet.</div>

    <div v-else class="mt-4 overflow-auto rounded-lg border border-slate-800">
      <table class="w-full text-left text-sm">
        <thead class="bg-slate-950 text-xs uppercase tracking-wide text-slate-400">
          <tr>
            <th class="px-3 py-2">Time</th>
            <th class="px-3 py-2">Price</th>
            <th class="px-3 py-2">Amount</th>
            <th class="px-3 py-2">USD</th>
            <th class="px-3 py-2">Fee</th>
          </tr>
        </thead>
        <tbody class="divide-y divide-slate-800">
          <tr v-for="trade in trades" :key="trade.id">
            <td class="px-3 py-2 whitespace-nowrap text-xs text-slate-400">{{ formatTime(trade.created_at) }}</td>
            <td class="px-3 py-2 font-mono">{{ trade.price }}</td>
            <td class="px-3 py-2 font-mono">{{ trade.amount }}</td>
            <td class="px-3 py-2 font-mono">{{ trade.usd_volume }}</td>
            <td class="px-3 py-2 font-mono">{{ trade.fee_usd }}</td>
          </tr>
        </tbody>
      </table>
    </div>
  </section>
</template>

