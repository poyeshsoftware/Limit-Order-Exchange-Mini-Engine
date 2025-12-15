<script setup lang="ts">
import { ref } from "vue";

import { useAuthStore } from "../stores/auth";

const auth = useAuthStore();

const email = ref("");
const password = ref("");
</script>

<template>
  <div class="mx-auto flex min-h-full w-full max-w-md flex-col justify-center gap-6 px-4 py-10">
    <div>
      <h1 class="text-2xl font-semibold">Login</h1>
      <p class="mt-1 text-sm text-slate-400">Use your backend user credentials.</p>
    </div>

    <form
      class="space-y-4 rounded-xl border border-slate-800 bg-slate-900/40 p-6"
      @submit.prevent="auth.login(email, password)"
    >
      <div class="space-y-1">
        <label class="text-sm text-slate-300">Email</label>
        <input v-model="email" class="w-full rounded-lg bg-slate-950 px-3 py-2 text-slate-100" type="email" />
      </div>

      <div class="space-y-1">
        <label class="text-sm text-slate-300">Password</label>
        <input
          v-model="password"
          class="w-full rounded-lg bg-slate-950 px-3 py-2 text-slate-100"
          type="password"
        />
      </div>

      <button
        class="w-full rounded-lg bg-indigo-600 px-3 py-2 text-sm font-semibold text-white hover:bg-indigo-500 disabled:opacity-60"
        type="submit"
        :disabled="auth.isLoggingIn"
      >
        {{ auth.isLoggingIn ? "Signing in..." : "Sign in" }}
      </button>

      <p v-if="auth.error" class="text-sm text-red-400">{{ auth.error }}</p>
    </form>
  </div>
</template>

