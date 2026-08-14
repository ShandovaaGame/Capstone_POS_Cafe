import { describe, it, expect, vi, afterEach } from 'vitest';
import { render, screen, act } from '@testing-library/react';
import { OfflineBanner } from '../OfflineBanner';

describe('OfflineBanner', () => {
  afterEach(() => {
    vi.useRealTimers();
  });

  it('offline banner shows correct text', () => {
    render(<OfflineBanner status="offline" pendingCount={3} isSyncing={false} />);

    expect(screen.getByText(/Mode Luring/)).toBeTruthy();
    expect(screen.getByText(/3 pesanan menunggu sinkronisasi/)).toBeTruthy();
    // Badge shows pending count
    expect(screen.getByText('3')).toBeTruthy();
  });

  it('sync banner shows progress', () => {
    const { container } = render(
      <OfflineBanner
        status="online"
        pendingCount={0}
        isSyncing={true}
        syncProgress={{ current: 2, total: 5 }}
      />,
    );

    expect(screen.getByText(/Menyinkronkan 2\/5 pesanan/)).toBeTruthy();

    // Progress bar exists with correct width
    const progressBar = container.querySelector('.bg-blue-600');
    expect(progressBar).toBeTruthy();
    expect(progressBar.style.width).toBe('40%');
  });

  it('online banner auto-disappears after 3s', () => {
    vi.useFakeTimers();
    render(<OfflineBanner status="online" pendingCount={0} isSyncing={false} />);

    // Initially visible
    expect(screen.getByText(/Kembali Online/)).toBeTruthy();

    // Advance past 3s display + 300ms leave animation
    act(() => {
      vi.advanceTimersByTime(3300);
    });

    // Banner should be gone
    expect(screen.queryByText(/Kembali Online/)).toBeNull();
  });

  it('renders nothing when checking status with no sync', () => {
    const { container } = render(
      <OfflineBanner status="checking" pendingCount={0} isSyncing={false} />,
    );

    expect(container.innerHTML).toBe('');
  });
});
