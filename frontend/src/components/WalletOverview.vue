<script setup lang="ts">
import { computed } from "vue";

import { useExchangeStore } from "../stores/exchange";

const exchange = useExchangeStore();

const profile = computed(() => exchange.profile);
</script>

<template>
  <section class="rounded-xl border border-slate-800 bg-slate-900/40 p-6">
    <h2 class="text-lg font-semibold">Wallet</h2>

    <div v-if="!profile" class="mt-4 text-sm text-slate-400">Loading…</div>

    <div v-else class="mt-4 space-y-4">
      <div class="rounded-lg bg-slate-950 px-3 py-2">
        <div class="text-xs uppercase tracking-wide text-slate-400">USD Balance</div>
        <div class="mt-1 font-mono text-lg">{{ profile.usd_balance }}</div>
      </div>

      <div>
        <div class="text-xs uppercase tracking-wide text-slate-400">Assets</div>
        <div class="mt-2 space-y-2">
          <div
            v-for="asset in profile.assets"
            :key="asset.symbol"
            class="flex items-center justify-between rounded-lg bg-slate-950 px-3 py-2 text-sm"
          >
            <div class="font-semibold">{{ asset.symbol }}</div>
            <div class="text-right font-mono">
              <div>Amt: {{ asset.amount }}</div>
              <div class="text-slate-400">Locked: {{ asset.locked_amount }}</div>
            </div>
          </div>
          <div v-if="profile.assets.length === 0" class="text-sm text-slate-400">No assets yet.</div>
        </div>
      </div>
    </div>
  </section>
</template>

