// Chunked sync configuration for offline order processing
// Values derived from Phase 1 benchmark: 50 orders = 5,593 ms → ~111 ms/order
// Vercel Hobby PHP max execution: 60 s (conservative budget: 55 s)
// Pure config — no runtime logic, no imports.

export const SYNC_CONFIG = {
  /** Max wall-clock time allowed for a single sync chunk (ms) */
  vercelTimeoutMs: 55_000,
  /** Estimated PHP cold-start overhead (ms) */
  phpColdStartMs: 250,
  /** Safety margin subtracted from timeout to leave headroom (ms) */
  safetyMarginMs: 5_000,
  /** Observed average processing time per order (ms) — 50 orders ÷ 5 593 ms */
  timePerOrderMs: 111,
  /** Minimum number of orders in a sync chunk */
  minChunkSize: 10,
  /** Max number of orders in a sync chunk (safety cap) */
  maxChunkSize: 100,
  /** Max retry attempts for a failed chunk */
  maxRetries: 3,
} as const;
