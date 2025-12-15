import { defineStore } from "pinia";

import { http } from "../api/http";
import { clearAuth, getToken, getUserId, setToken, setUserId } from "../api/authStorage";

type LoginResponse = {
  token: string;
  user: { id: number };
};

export const useAuthStore = defineStore("auth", {
  state: () => ({
    token: getToken() as string | null,
    userId: getUserId() as number | null,
    isLoggingIn: false as boolean,
    error: null as string | null,
  }),
  getters: {
    isAuthenticated: (state) => Boolean(state.token),
  },
  actions: {
    async login(email: string, password: string): Promise<void> {
      this.isLoggingIn = true;
      this.error = null;

      try {
        const res = await http.post<LoginResponse>("/api/login", {
          email,
          password,
          device_name: "frontend",
        });

        this.token = res.data.token;
        this.userId = res.data.user.id;
        setToken(this.token);
        setUserId(this.userId);
      } catch (e: any) {
        this.error = e?.response?.data?.message ?? "Login failed.";
        throw e;
      } finally {
        this.isLoggingIn = false;
      }
    },

    logout(): void {
      clearAuth();
      this.token = null;
      this.userId = null;
    },
  },
});

