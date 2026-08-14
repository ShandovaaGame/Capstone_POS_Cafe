import { useState, useEffect, useCallback, useRef } from 'react';
import axios from 'axios';
import type { NetworkStatus } from '@/types/offline';

interface UseNetworkStatusReturn {
  isOnline: boolean;
  status: NetworkStatus;
  lastChecked: Date;
  wrapWithFallback: <T>(fn: () => Promise<T>, onError?: () => void) => Promise<T>;
}

function isBrowser(): boolean {
  return typeof window !== 'undefined';
}

/**
 * 3-tier composite network detection:
 *
 * Tier 1 — `window.addEventListener('online'/'offline')` — instant browser detection
 * Tier 2 — Proactive health check via HEAD /api/ping on browser 'online' event (lie-fi)
 * Tier 3 — `wrapWithFallback(fn)` — wrapper for any request, auto-set offline on network error
 *
 * Note: Periodic health check was removed — the data reload (router.reload) at 30s
 * naturally serves as connectivity verification. Success → online. Failure → already offline
 * (detected by Tier 1 or Tier 3).
 */
export function useNetworkStatus(): UseNetworkStatusReturn {
  // ─── Tier 1: Initial state from navigator.onLine ───
  const initialOnline = isBrowser() ? navigator.onLine : true;
  const [isOnline, setIsOnline] = useState(initialOnline);
  const [status, setStatus] = useState<NetworkStatus>(initialOnline ? 'online' : 'offline');
  const [lastChecked, setLastChecked] = useState(new Date());

  // Refs to avoid stale closures in events
  const onlineRef = useRef(initialOnline);

  // ─── Shared helpers ─────────────────────────────────────────
  const verifyHealth = useCallback(async (): Promise<boolean> => {
    try {
      await axios.head('/api/ping', { timeout: 5_000 });
      return true;
    } catch {
      return false;
    }
  }, []);

  const setOffline = useCallback(() => {
    onlineRef.current = false;
    setIsOnline(false);
    setStatus('offline');
    setLastChecked(new Date());
  }, []);

  const setOnline = useCallback(() => {
    onlineRef.current = true;
    setIsOnline(true);
    setStatus('online');
    setLastChecked(new Date());
  }, []);

  // ─── Tier 1: Browser online/offline events ───────────────────
  useEffect(() => {
    if (!isBrowser()) return;

    const handleOnline = async () => {
      // Verify with health check before declaring online (lie-fi protection)
      setStatus('checking');
      const ok = await verifyHealth();
      if (ok) {
        setOnline();
      } else {
        setOffline();
      }
    };

    const handleOffline = () => {
      setOffline();
    };

    window.addEventListener('online', handleOnline);
    window.addEventListener('offline', handleOffline);

    return () => {
      window.removeEventListener('online', handleOnline);
      window.removeEventListener('offline', handleOffline);
    };
  }, [verifyHealth, setOnline, setOffline]);

  // ─── Tier 3: Request wrapper that auto-detects offline ───────
  const wrapWithFallback = useCallback(
    async <T,>(fn: () => Promise<T>, onError?: () => void): Promise<T> => {
      try {
        return await fn();
      } catch (err) {
        // Network error (no response received) → we're offline
        if (axios.isAxiosError(err) && !err.response) {
          setOffline();
        }
        onError?.();
        throw err;
      }
    },
    [setOffline],
  );

  return { isOnline, status, lastChecked, wrapWithFallback };
}
