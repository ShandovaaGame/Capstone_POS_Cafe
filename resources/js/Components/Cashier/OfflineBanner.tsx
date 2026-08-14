import { useState, useEffect } from 'react';
import type { NetworkStatus } from '@/types/offline';

interface OfflineBannerProps {
  status: NetworkStatus;
  pendingCount: number;
  isSyncing: boolean;
  syncProgress?: { current: number; total: number };
}

export function OfflineBanner({
  status,
  pendingCount,
  isSyncing,
  syncProgress,
}: OfflineBannerProps) {
  const [visible, setVisible] = useState(false);
  const [variant, setVariant] = useState<'offline' | 'syncing' | 'online'>('offline');
  const [animEnter, setAnimEnter] = useState(false);

  // Determine if banner should show and which variant
  useEffect(() => {
    if (status === 'offline') {
      setVariant('offline');
      setVisible(true);
    } else if (isSyncing || (status === 'online' && pendingCount > 0)) {
      setVariant('syncing');
      setVisible(true);
    } else if (status === 'online' && pendingCount === 0) {
      // Online, no pending — show success banner briefly
      setVariant('online');
      setVisible(true);
      const timer = setTimeout(() => {
        setAnimEnter(false);
        setTimeout(() => setVisible(false), 300);
      }, 3000);
      return () => clearTimeout(timer);
    }
  }, [status, isSyncing, pendingCount]);

  // Slide-in animation trigger
  useEffect(() => {
    if (visible) {
      const raf = requestAnimationFrame(() => setAnimEnter(true));
      return () => cancelAnimationFrame(raf);
    }
  }, [visible]);

  if (!visible) return null;

  const bannerConfig = {
    offline: {
      bg: 'bg-yellow-50 border-yellow-400',
      text: 'text-yellow-800',
      icon: '\u26A0\uFE0F',
      msg: `Mode Luring \u2014 ${pendingCount} pesanan menunggu sinkronisasi`,
    },
    syncing: {
      bg: 'bg-blue-50 border-blue-400',
      text: 'text-blue-800',
      icon: '\u27F3',
      msg: `Menyinkronkan ${syncProgress?.current ?? '...'}/${syncProgress?.total ?? '...'} pesanan`,
    },
    online: {
      bg: 'bg-green-50 border-green-400',
      text: 'text-green-800',
      icon: '\u2713',
      msg: 'Kembali Online',
    },
  };

  const config = bannerConfig[variant];
  const progressPercent =
    variant === 'syncing' && syncProgress
      ? Math.round((syncProgress.current / syncProgress.total) * 100)
      : 0;

  return (
    <div
      className={`fixed top-0 left-0 right-0 z-50 transition-all duration-300 ease-in-out ${
        animEnter ? 'translate-y-0 opacity-100' : '-translate-y-full opacity-0'
      }`}
      role="alert"
      aria-live="polite"
    >
      <div className={`${config.bg} ${config.text} border-b px-4 py-3`}>
        <div className="flex items-center justify-between">
          <div className="flex items-center gap-2">
            <span className="text-base">{config.icon}</span>
            <span className="text-sm font-medium">{config.msg}</span>
          </div>
          {variant === 'offline' && pendingCount > 0 && (
            <span className="inline-flex items-center justify-center bg-yellow-200 text-yellow-800 text-xs font-bold rounded-full px-2 py-0.5">
              {pendingCount}
            </span>
          )}
        </div>
        {variant === 'syncing' && syncProgress && (
          <div className="mt-2 w-full bg-blue-200 rounded-full h-1.5">
            <div
              className="bg-blue-600 h-1.5 rounded-full transition-all duration-500 ease-in-out"
              style={{ width: `${progressPercent}%` }}
            />
          </div>
        )}
      </div>
    </div>
  );
}
