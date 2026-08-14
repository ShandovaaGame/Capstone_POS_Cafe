// This file intentionally left empty — offline sync removed
export function useSyncService() {
  return { isOnline: true, queueCount: 0, syncNow: () => {}, startPolling: () => {}, stopPolling: () => {} };
}
