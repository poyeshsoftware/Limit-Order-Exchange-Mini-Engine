import { defineStore } from "pinia";

export type ToastType = "success" | "error" | "info";

export type Toast = {
  id: string;
  type: ToastType;
  message: string;
};

function makeId(): string {
  if (typeof crypto !== "undefined" && "randomUUID" in crypto) {
    return crypto.randomUUID();
  }

  return `${Date.now()}-${Math.random().toString(16).slice(2)}`;
}

export const useToastStore = defineStore("toast", {
  state: () => ({
    toasts: [] as Toast[],
  }),
  actions: {
    push(message: string, type: ToastType = "info", timeoutMs = 3500): void {
      const id = makeId();
      this.toasts = [...this.toasts, { id, type, message }].slice(-5);

      window.setTimeout(() => {
        this.remove(id);
      }, timeoutMs);
    },

    success(message: string): void {
      this.push(message, "success");
    },

    error(message: string): void {
      this.push(message, "error");
    },

    info(message: string): void {
      this.push(message, "info");
    },

    remove(id: string): void {
      this.toasts = this.toasts.filter((t) => t.id !== id);
    },
  },
});

