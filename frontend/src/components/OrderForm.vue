<script setup lang="ts">
import { computed, ref } from "vue";

import { useExchangeStore } from "../stores/exchange";
import { useToastStore } from "../stores/toast";
import { mulDecimalStringByRatio, mulDecimalStrings } from "../utils/decimal";

const exchange = useExchangeStore();
const toast = useToastStore();

const side = ref<"buy" | "sell">("buy");
const price = ref("");
const amount = ref("");

const isSubmitting = ref(false);
const error = ref<string | null>(null);

const costUsd = computed(() => mulDecimalStrings(price.value, amount.value));
const feeUsd = computed(() => (costUsd.value ? mulDecimalStringByRatio(costUsd.value, 15n, 1000n) : null));

async function submit(): Promise<void> {
  isSubmitting.value = true;
  error.value = null;

  try {
    await exchange.placeOrder({
      symbol: exchange.selectedSymbol,
      side: side.value,
      price: price.value,
      amount: amount.value,
    });

    price.value = "";
    amount.value = "";
    toast.success(`Order submitted: ${side.value.toUpperCase()} ${exchange.selectedSymbol}`);
  } catch (e: any) {
    const message = e?.response?.data?.message ?? "Failed to place order.";
    error.value = message;
    toast.error(message);
  } finally {
    isSubmitting.value = false;
  }
}
</script>

<template>
  <section class="rounded-xl border border-slate-800 bg-slate-900/40 p-6">
    <div class="flex items-center justify-between">
      <h2 class="text-lg font-semibold">Place Order</h2>
      <div class="text-sm text-slate-400">Symbol: {{ exchange.selectedSymbol }}</div>
    </div>

    <form class="mt-4 space-y-4" @submit.prevent="submit">
      <div class="grid grid-cols-2 gap-3">
        <button
          type="button"
          class="rounded-lg px-3 py-2 text-sm font-semibold"
          :class="side === 'buy' ? 'bg-emerald-600 text-white' : 'bg-slate-950 text-slate-200'"
          @click="side = 'buy'"
        >
          Buy
        </button>
        <button
          type="button"
          class="rounded-lg px-3 py-2 text-sm font-semibold"
          :class="side === 'sell' ? 'bg-rose-600 text-white' : 'bg-slate-950 text-slate-200'"
          @click="side = 'sell'"
        >
          Sell
        </button>
      </div>

      <div class="space-y-1">
        <label class="text-sm text-slate-300">Price (USD)</label>
        <input v-model="price" class="w-full rounded-lg bg-slate-950 px-3 py-2 text-slate-100" inputmode="decimal" />
      </div>

      <div class="space-y-1">
        <label class="text-sm text-slate-300">Amount</label>
        <input v-model="amount" class="w-full rounded-lg bg-slate-950 px-3 py-2 text-slate-100" inputmode="decimal" />
      </div>

      <div class="rounded-lg bg-slate-950 px-3 py-2 text-sm text-slate-300">
        <div class="flex items-center justify-between">
          <span>Estimated cost</span>
          <span class="font-mono">{{ costUsd ?? "—" }}</span>
        </div>
        <div class="mt-1 flex items-center justify-between">
          <span>Estimated fee (1.5%)</span>
          <span class="font-mono">{{ feeUsd ?? "—" }}</span>
        </div>
      </div>

      <button
        class="w-full rounded-lg bg-indigo-600 px-3 py-2 text-sm font-semibold text-white hover:bg-indigo-500 disabled:opacity-60"
        type="submit"
        :disabled="isSubmitting"
      >
        {{ isSubmitting ? "Submitting..." : "Submit order" }}
      </button>

      <p v-if="error" class="text-sm text-red-400">{{ error }}</p>
    </form>
  </section>
</template>
