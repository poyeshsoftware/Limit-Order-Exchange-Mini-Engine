import { createRouter, createWebHistory } from "vue-router";

import LoginPage from "../pages/LoginPage.vue";
import ExchangePage from "../pages/ExchangePage.vue";

const router = createRouter({
  history: createWebHistory(),
  routes: [
    { path: "/", redirect: "/exchange" },
    { path: "/login", component: LoginPage },
    { path: "/exchange", component: ExchangePage },
  ],
});

router.beforeEach((to) => {
  const token = localStorage.getItem("auth.token");

  if (!token && to.path !== "/login") {
    return "/login";
  }

  if (token && to.path === "/login") {
    return "/exchange";
  }

  return true;
});

export default router;

