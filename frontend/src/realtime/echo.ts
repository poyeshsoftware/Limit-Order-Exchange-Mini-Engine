import Echo from "laravel-echo";
import Pusher from "pusher-js";

declare global {
  interface Window {
    Pusher: any;
  }
}

let echo: Echo<"pusher"> | null = null;

export function connectEcho(token: string): Echo<"pusher"> {
  if (echo) return echo;

  window.Pusher = Pusher;

  echo = new Echo({
    broadcaster: "pusher",
    key: import.meta.env.VITE_PUSHER_KEY,
    cluster: import.meta.env.VITE_PUSHER_CLUSTER,
    forceTLS: true,
    authEndpoint: `${import.meta.env.VITE_API_BASE_URL}/broadcasting/auth`,
    auth: {
      headers: {
        Authorization: `Bearer ${token}`,
      },
    },
  });

  return echo;
}

export function disconnectEcho(): void {
  if (!echo) return;
  echo.disconnect();
  echo = null;
}

