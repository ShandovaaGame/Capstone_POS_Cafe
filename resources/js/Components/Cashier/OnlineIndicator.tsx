import type { NetworkStatus } from '@/types/offline';

interface OnlineIndicatorProps {
  status: NetworkStatus;
  isSyncing: boolean;
  pendingCount: number;
  syncProgress?: { current: number; total: number };
}

export function OnlineIndicator({
  status,
  isSyncing,
  pendingCount,
  syncProgress,
}: OnlineIndicatorProps) {
  if (isSyncing) {
    return (
      <span className="inline-flex items-center gap-1.5 text-xs font-medium px-3 py-1 rounded-full bg-blue-50 text-blue-700 border border-blue-200">
        <span className="w-2 h-2 rounded-full bg-blue-500 animate-pulse" />
        Menyinkronkan {syncProgress?.current ?? '...'}/{syncProgress?.total ?? '...'}
      </span>
    );
  }

  if (status === 'offline' || status === 'checking') {
    return (
      <span className="inline-flex items-center gap-1.5 text-xs font-medium px-3 py-1 rounded-full bg-yellow-50 text-yellow-700 border border-yellow-200">
        <span className="w-2 h-2 rounded-full bg-yellow-400" />
        {pendingCount > 0
          ? `Offline (${pendingCount} pesanan dicatat offline)`
          : 'Offline'}
      </span>
    );
  }

  return (
    <span className="inline-flex items-center gap-1.5 text-xs font-medium px-3 py-1 rounded-full bg-green-50 text-green-700 border border-green-200">
      <span className="w-2 h-2 rounded-full bg-green-500" />
      Online
    </span>
  );
}
