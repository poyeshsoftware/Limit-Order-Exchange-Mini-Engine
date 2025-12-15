import { createApp } from "vue";
import "./style.css";
import App from "./App.vue";
import { pinia } from "./pinia";
import router from "./router";

createApp(App).use(pinia).use(router).mount("#app");
