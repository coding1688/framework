const prefix = 'backend:';
export function readStorage(key, fallback) {
    try { const value = localStorage.getItem(prefix + key); return value === null ? fallback : JSON.parse(value); }
    catch { return fallback; }
}
export function writeStorage(key, value) {
    try { localStorage.setItem(prefix + key, JSON.stringify(value)); } catch { /* Preferences remain usable in memory when storage is unavailable. */ }
}
