<script setup lang="ts">
import { computed } from "vue";

import { useToastStore } from "../stores/toast";

const toast = useToastStore();

const toasts = computed(() => toast.toasts);

function toastClass(type: string): string {
  if (type === "success") return "border-emerald-900 bg-emerald-950/40 text-emerald-100";
  if (type === "error") return "border-rose-900 bg-rose-950/40 text-rose-100";
  return "border-slate-800 bg-slate-950/60 text-slate-100";
}
</script>

<template>
  <div class="pointer-events-none fixed right-4 top-4 z-50 flex w-full max-w-sm flex-col gap-2">
    <div
      v-for="item in toasts"
      :key="item.id"
      class="pointer-events-auto rounded-xl border px-4 py-3 text-sm shadow-xl backdrop-blur"
      :class="toastClass(item.type)"
    >
      <div class="flex items-start justify-between gap-3">
        <div class="leading-snug">{{ item.message }}</div>
        <button
          type="button"
          class="rounded-lg bg-slate-900/40 px-2 py-1 text-xs text-slate-200 hover:bg-slate-900/60"
          @click="toast.remove(item.id)"
        >
          Close
        </button>
      </div>
    </div>
  </div>
</template>

