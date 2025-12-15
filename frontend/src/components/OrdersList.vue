<script setup lang="ts">
import { computed, ref } from "vue";

import { useExchangeStore } from "../stores/exchange";

const exchange = useExchangeStore();

const showOnlySelectedSymbol = ref(true);

const orders = computed(() => {
  const all = exchange.myOrders;
  if (!showOnlySelectedSymbol.value) return all;
  return all.filter((o) => o.symbol === exchange.selectedSymbol);
});

function statusLabel(status: number): string {
  if (status === 1) return "Open";
  if (status === 2) return "Filled";
  if (status === 3) return "Cancelled";
  return String(status);
}
</script>

<template>
  <section class="rounded-xl border border-slate-800 bg-slate-900/40 p-6">
    <div class="flex items-center justify-between">
      <h2 class="text-lg font-semibold">My Orders</h2>
      <label class="flex items-center gap-2 text-sm text-slate-300">
        <input v-model="showOnlySelectedSymbol" type="checkbox" class="accent-indigo-600" />
        Only {{ exchange.selectedSymbol }}
      </label>
    </div>

    <div v-if="orders.length === 0" class="mt-4 text-sm text-slate-400">No orders.</div>

    <div v-else class="mt-4 overflow-auto rounded-lg border border-slate-800">
      <table class="w-full text-left text-sm">
        <thead class="bg-slate-950 text-xs uppercase tracking-wide text-slate-400">
          <tr>
            <th class="px-3 py-2">ID</th>
            <th class="px-3 py-2">Symbol</th>
            <th class="px-3 py-2">Side</th>
            <th class="px-3 py-2">Price</th>
            <th class="px-3 py-2">Amount</th>
            <th class="px-3 py-2">Status</th>
            <th class="px-3 py-2">Action</th>
          </tr>
        </thead>
        <tbody class="divide-y divide-slate-800">
          <tr v-for="order in orders" :key="order.id">
            <td class="px-3 py-2 font-mono">{{ order.id }}</td>
            <td class="px-3 py-2">{{ order.symbol }}</td>
            <td class="px-3 py-2">
              <span :class="order.side === 'buy' ? 'text-emerald-400' : 'text-rose-400'">{{ order.side }}</span>
            </td>
            <td class="px-3 py-2 font-mono">{{ order.price }}</td>
            <td class="px-3 py-2 font-mono">{{ order.amount }}</td>
            <td class="px-3 py-2">{{ statusLabel(order.status) }}</td>
            <td class="px-3 py-2">
              <button
                v-if="order.status === 1"
                class="rounded-lg bg-slate-800 px-2 py-1 text-xs font-semibold text-slate-100 hover:bg-slate-700"
                type="button"
                @click="exchange.cancelOrder(order.id)"
              >
                Cancel
              </button>
              <span v-else class="text-xs text-slate-500">—</span>
            </td>
          </tr>
        </tbody>
      </table>
    </div>
  </section>
</template>

