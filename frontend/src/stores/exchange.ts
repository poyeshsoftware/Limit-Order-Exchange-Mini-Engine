import { defineStore } from "pinia";

import { http } from "../api/http";

export type Symbol = "BTC" | "ETH";

export type Asset = {
  symbol: string;
  amount: string;
  locked_amount: string;
};

export type Profile = {
  usd_balance: string;
  assets: Asset[];
};

export type OrderBookOrder = {
  id: number;
  price: string;
  amount: string;
  created_at: string;
};

export type OrderBook = {
  buy: OrderBookOrder[];
  sell: OrderBookOrder[];
};

export type MyOrder = {
  id: number;
  symbol: Symbol;
  side: "buy" | "sell";
  price: string;
  amount: string;
  status: number;
  created_at: string;
};

export const useExchangeStore = defineStore("exchange", {
  state: () => ({
    selectedSymbol: "BTC" as Symbol,
    profile: null as Profile | null,
    orderBook: { buy: [], sell: [] } as OrderBook,
    myOrders: [] as MyOrder[],
    isLoading: false as boolean,
  }),
  actions: {
    async fetchProfile(): Promise<void> {
      const res = await http.get<Profile>("/api/profile");
      this.profile = res.data;
    },

    async fetchOrderBook(symbol: Symbol): Promise<void> {
      const res = await http.get<OrderBook>("/api/orders", { params: { symbol } });
      this.orderBook = res.data;
    },

    async fetchMyOrders(symbol?: Symbol): Promise<void> {
      const res = await http.get<{ orders: MyOrder[] }>("/api/my-orders", {
        params: symbol ? { symbol } : undefined,
      });
      this.myOrders = res.data.orders;
    },

    async placeOrder(payload: { symbol: Symbol; side: "buy" | "sell"; price: string; amount: string }): Promise<void> {
      await http.post("/api/orders", payload);
      await this.refreshAll();
    },

    async cancelOrder(orderId: number): Promise<void> {
      await http.post(`/api/orders/${orderId}/cancel`);
      await this.refreshAll();
    },

    async refreshAll(): Promise<void> {
      await Promise.all([
        this.fetchProfile(),
        this.fetchOrderBook(this.selectedSymbol),
        this.fetchMyOrders(),
      ]);
    },
  },
});

