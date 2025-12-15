const TOKEN_KEY = "auth.token";
const USER_ID_KEY = "auth.userId";

export function getToken(): string | null {
  return localStorage.getItem(TOKEN_KEY);
}

export function setToken(token: string): void {
  localStorage.setItem(TOKEN_KEY, token);
}

export function getUserId(): number | null {
  const raw = localStorage.getItem(USER_ID_KEY);
  if (!raw) return null;

  const parsed = Number.parseInt(raw, 10);
  return Number.isFinite(parsed) ? parsed : null;
}

export function setUserId(userId: number): void {
  localStorage.setItem(USER_ID_KEY, String(userId));
}

export function clearAuth(): void {
  localStorage.removeItem(TOKEN_KEY);
  localStorage.removeItem(USER_ID_KEY);
}

