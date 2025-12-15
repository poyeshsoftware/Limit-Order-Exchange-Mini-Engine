import axios from "axios";

import router from "../router";
import { clearAuth, getToken } from "./authStorage";

export const http = axios.create({
  baseURL: import.meta.env.VITE_API_BASE_URL,
});

http.interceptors.request.use((config) => {
  const token = getToken();
  if (token) config.headers.Authorization = `Bearer ${token}`;
  return config;
});

http.interceptors.response.use(
  (r) => r,
  async (err) => {
    if (err?.response?.status === 401) {
      clearAuth();
      await router.push("/login");
    }
    return Promise.reject(err);
  }
);

