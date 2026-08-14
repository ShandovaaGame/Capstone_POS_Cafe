import { describe, it, expect, beforeEach, afterEach, vi } from 'vitest';
import { renderHook, act } from '@testing-library/react';
import axios from 'axios';
import { useNetworkStatus } from '../useNetworkStatus';

vi.mock('axios', () => ({
  default: {
    head: vi.fn(),
    isAxiosError: vi.fn((err) => {
      if (err && typeof err === 'object' && 'isAxiosError' in err) {
        return err.isAxiosError === true;
      }
      return false;
    }),
  },
}));

const mockHead = axios.head;
const mockIsAxiosError = axios.isAxiosError;

describe('useNetworkStatus', () => {
  beforeEach(() => {
    vi.useFakeTimers({ shouldAdvanceTime: true });
    Object.defineProperty(navigator, 'onLine', {
      value: true, writable: true, configurable: true,
    });
    mockHead.mockReset();
  });

  afterEach(() => {
    vi.useRealTimers();
    vi.restoreAllMocks();
  });

  // 1. Returns initial online status based on navigator.onLine
  it('returns online status when navigator.onLine is true', () => {
    Object.defineProperty(navigator, 'onLine', { value: true, writable: true, configurable: true });
    mockHead.mockResolvedValue({ status: 200, headers: { 'x-health': 'ok' } });

    const { result } = renderHook(() => useNetworkStatus());

    expect(result.current.isOnline).toBe(true);
    expect(result.current.status).toBe('online');
  });

  // 2. Returns initial offline status when navigator.onLine is false
  it('returns offline status when navigator.onLine is false', () => {
    Object.defineProperty(navigator, 'onLine', { value: false, writable: true, configurable: true });

    const { result } = renderHook(() => useNetworkStatus());

    expect(result.current.isOnline).toBe(false);
    expect(result.current.status).toBe('offline');
  });

  // 3. Browser 'offline' event sets status to offline immediately
  it('sets offline status on browser offline event', async () => {
    Object.defineProperty(navigator, 'onLine', { value: true, writable: true, configurable: true });
    mockHead.mockResolvedValue({ status: 200 });

    const { result } = renderHook(() => useNetworkStatus());

    // Wait for initial health check to resolve
    await act(async () => { await vi.advanceTimersByTimeAsync(0); });
    expect(result.current.status).toBe('online');

    act(() => { window.dispatchEvent(new Event('offline')); });

    expect(result.current.isOnline).toBe(false);
    expect(result.current.status).toBe('offline');
  });

  // 4. Browser 'online' event triggers health check before declaring online
  it('verifies with health check on browser online event before declaring online', async () => {
    Object.defineProperty(navigator, 'onLine', { value: false, writable: true, configurable: true });
    mockHead.mockResolvedValue({ status: 200 });

    const { result } = renderHook(() => useNetworkStatus());
    expect(result.current.status).toBe('offline');

    act(() => { window.dispatchEvent(new Event('online')); });
    expect(result.current.status).toBe('checking');

    await act(async () => { await vi.advanceTimersByTimeAsync(0); });

    expect(result.current.isOnline).toBe(true);
    expect(result.current.status).toBe('online');
  });

  // 5. Tier 2: Periodic health check succeeds → online
  it('sets online after periodic health check succeeds', async () => {
    Object.defineProperty(navigator, 'onLine', { value: true, writable: true, configurable: true });
    mockHead.mockRejectedValueOnce(new Error('timeout')).mockResolvedValue({ status: 200 });

    const { result } = renderHook(() => useNetworkStatus());

    // Initial health check: mocked reject → offline
    await act(async () => { await vi.advanceTimersByTimeAsync(0); });
    expect(result.current.status).toBe('offline');

    // Advance 15s for next health check (this one succeeds)
    await act(async () => { vi.advanceTimersByTime(15_000); });
    await act(async () => { await vi.advanceTimersByTimeAsync(0); });

    expect(result.current.isOnline).toBe(true);
    expect(result.current.status).toBe('online');
  });

  // 6. Tier 2: Periodic health check fails → offline
  it('sets offline after periodic health check fails', async () => {
    Object.defineProperty(navigator, 'onLine', { value: true, writable: true, configurable: true });
    mockHead.mockResolvedValueOnce({ status: 200 }).mockRejectedValue(new Error('network error'));

    const { result } = renderHook(() => useNetworkStatus());

    // Initial health check: succeed → online
    await act(async () => { await vi.advanceTimersByTimeAsync(0); });
    expect(result.current.status).toBe('online');

    // Advance 15s for next health check (this one fails)
    await act(async () => { vi.advanceTimersByTime(15_000); });
    await act(async () => { await vi.advanceTimersByTimeAsync(0); });

    expect(result.current.isOnline).toBe(false);
    expect(result.current.status).toBe('offline');
  });

  // 7. Tier 3: wrapWithFallback sets offline on network error and calls onError
  it('wrapWithFallback sets offline on network error and calls onError', async () => {
    Object.defineProperty(navigator, 'onLine', { value: true, writable: true, configurable: true });
    mockHead.mockResolvedValue({ status: 200 });

    const { result } = renderHook(() => useNetworkStatus());
    await act(async () => { await vi.advanceTimersByTimeAsync(0); });
    expect(result.current.status).toBe('online');

    const networkErr = new Error('ECONNREFUSED');
    networkErr.isAxiosError = true;
    networkErr.response = undefined;
    const onError = vi.fn();

    let caught = null;
    await act(async () => {
      try {
        await result.current.wrapWithFallback(() => Promise.reject(networkErr), onError);
      } catch (e) {
        caught = e;
      }
    });

    expect(caught).toBe(networkErr);
    expect(onError).toHaveBeenCalledOnce();
    expect(result.current.isOnline).toBe(false);
    expect(result.current.status).toBe('offline');
    expect(result.current.lastChecked).toBeInstanceOf(Date);
  });
});
