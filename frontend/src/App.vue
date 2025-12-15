<template>
  <div class="min-h-full bg-slate-950 text-slate-100">
    <ToastHost />
    <router-view />
  </div>
</template>

<script setup lang="ts">
import ToastHost from "./components/ToastHost.vue";
</script>
