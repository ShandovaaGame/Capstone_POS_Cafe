import { describe, it, expect, beforeEach, vi } from 'vitest';
import { renderHook, act } from '@testing-library/react';
import useCustomerOrderHistory, {
    __TEST_EXPORT_STORAGE_KEY,
    __TEST_EXPORT_META_KEY,
    __TEST_EXPORT_SCHEMA_VERSION,
    __TEST_EXPORT_MAX_ENTRIES,
} from '../useCustomerOrderHistory';

const makeOrder = (overrides = {}) => ({
    uuid: crypto.randomUUID(),
    order_code: 'ORD-20260518-0001',
    date: new Date().toISOString(),
    total_amount: 45000,
    status: 'completed',
    ...overrides,
});

describe('useCustomerOrderHistory', () => {
    beforeEach(() => {
        localStorage.clear();
        vi.restoreAllMocks();
    });

    // ──────────────────────────────────────────────────
    // Basic CRUD
    // ──────────────────────────────────────────────────

    it('returns empty orders array initially', () => {
        const { result } = renderHook(() => useCustomerOrderHistory());
        expect(result.current.orders).toEqual([]);
    });

    it('adds and retrieves a valid order', () => {
        const { result } = renderHook(() => useCustomerOrderHistory());
        const order = makeOrder();

        act(() => {
            result.current.addOrder(order);
        });

        expect(result.current.orders).toHaveLength(1);
        expect(result.current.orders[0].uuid).toBe(order.uuid);
        expect(result.current.orders[0].order_code).toBe(order.order_code);
    });

    it('stores order in newest-first order', () => {
        const { result } = renderHook(() => useCustomerOrderHistory());

        const older = makeOrder({ date: '2026-05-17T10:00:00.000Z', order_code: 'ORD-001' });
        const newer = makeOrder({ date: '2026-05-18T10:00:00.000Z', order_code: 'ORD-002' });

        act(() => {
            result.current.addOrder(older);
        });
        act(() => {
            result.current.addOrder(newer);
        });

        expect(result.current.orders).toHaveLength(2);
        expect(result.current.orders[0].order_code).toBe('ORD-002');
        expect(result.current.orders[1].order_code).toBe('ORD-001');
    });

    it('clears all history', () => {
        const { result } = renderHook(() => useCustomerOrderHistory());

        act(() => {
            result.current.addOrder(makeOrder());
        });
        expect(result.current.orders).toHaveLength(1);

        act(() => {
            result.current.clearHistory();
        });

        expect(result.current.orders).toEqual([]);
        expect(localStorage.getItem(__TEST_EXPORT_STORAGE_KEY)).toBeNull();
        expect(localStorage.getItem(__TEST_EXPORT_META_KEY)).toBeNull();
    });

    // ──────────────────────────────────────────────────
    // Corrupt JSON recovery
    // ──────────────────────────────────────────────────

    it('recovers from corrupt JSON in localStorage', () => {
        localStorage.setItem(__TEST_EXPORT_STORAGE_KEY, '{broken json!!!');

        const { result } = renderHook(() => useCustomerOrderHistory());

        expect(result.current.orders).toEqual([]);
        expect(localStorage.getItem(__TEST_EXPORT_STORAGE_KEY)).toBeNull();
    });

    it('recovers from non-array JSON in localStorage', () => {
        localStorage.setItem(__TEST_EXPORT_STORAGE_KEY, JSON.stringify({ not: 'an-array' }));

        const { result } = renderHook(() => useCustomerOrderHistory());

        expect(result.current.orders).toEqual([]);
        expect(localStorage.getItem(__TEST_EXPORT_STORAGE_KEY)).toBeNull();
    });

    it('recovers from string value in localStorage', () => {
        localStorage.setItem(__TEST_EXPORT_STORAGE_KEY, '"just a string, not an array"');

        const { result } = renderHook(() => useCustomerOrderHistory());

        expect(result.current.orders).toEqual([]);
        expect(localStorage.getItem(__TEST_EXPORT_STORAGE_KEY)).toBeNull();
    });

    it('recovers from null JSON in localStorage', () => {
        localStorage.setItem(__TEST_EXPORT_STORAGE_KEY, 'null');

        const { result } = renderHook(() => useCustomerOrderHistory());

        expect(result.current.orders).toEqual([]);
        expect(localStorage.getItem(__TEST_EXPORT_STORAGE_KEY)).toBeNull();
    });

    it('recovers from array with corrupt entries and keeps valid ones', () => {
        const validOrder = makeOrder();
        const validEntry = JSON.stringify([
            validOrder,
            'not_an_object',
            null,
            { missing_fields: true },
        ]);
        localStorage.setItem(__TEST_EXPORT_STORAGE_KEY, validEntry);

        const { result } = renderHook(() => useCustomerOrderHistory());

        expect(result.current.orders).toHaveLength(1);
        expect(result.current.orders[0].uuid).toBe(validOrder.uuid);
        expect(result.current.orders[0].order_code).toBe(validOrder.order_code);
    });

    it('survives empty localStorage entry', () => {
        localStorage.setItem(__TEST_EXPORT_STORAGE_KEY, '');

        const { result } = renderHook(() => useCustomerOrderHistory());

        expect(result.current.orders).toEqual([]);
        // Empty string is falsy, readStorage returns [] without cleanup
        expect(localStorage.getItem(__TEST_EXPORT_STORAGE_KEY)).toBe('');
    });

    // ──────────────────────────────────────────────────
    // QuotaExceededError handling
    // ──────────────────────────────────────────────────

    it('handles QuotaExceededError during write', () => {
        const { result } = renderHook(() => useCustomerOrderHistory());

        // Fill with 200 entries first
        const orders = Array.from({ length: 200 }, (_, i) =>
            makeOrder({
                order_code: `ORD-${String(i).padStart(4, '0')}`,
                date: new Date(2026, 4, 18, 10, 0, i).toISOString(),
            }),
        );

        // Seed localStorage directly
        localStorage.setItem(__TEST_EXPORT_STORAGE_KEY, JSON.stringify(orders));

        // Simulate QuotaExceededError on the next write
        const setItemSpy = vi.spyOn(Storage.prototype, 'setItem');
        let callCount = 0;
        setItemSpy.mockImplementation((key, value) => {
            callCount++;
            if (callCount === 1) {
                const err = new DOMException('Quota exceeded', 'QuotaExceededError');
                err.code = 22;
                throw err;
            }
            // Second call (recovery write) should succeed
            Storage.prototype.setItem.call(localStorage, key, value);
        });

        act(() => {
            result.current.addOrder(makeOrder({ order_code: 'ORD-NEW' }));
        });

        setItemSpy.mockRestore();

        // After recovery, orders should be clamped
        expect(result.current.orders.length).toBeLessThanOrEqual(200);
    });

    it('handles QuotaExceededError with error code 22', () => {
        const setItemSpy = vi.spyOn(Storage.prototype, 'setItem');
        let callCount = 0;
        setItemSpy.mockImplementation((key, value) => {
            callCount++;
            if (callCount === 1) {
                const err = new DOMException('Quota', 'UnknownError');
                err.code = 22;
                throw err;
            }
            Storage.prototype.setItem.call(localStorage, key, value);
        });

        const { result } = renderHook(() => useCustomerOrderHistory());

        act(() => {
            result.current.addOrder(makeOrder());
        });

        setItemSpy.mockRestore();

        expect(result.current.orders.length).toBeGreaterThanOrEqual(1);
    });

    it('survives write with generic Error (not QuotaExceeded)', () => {
        const setItemSpy = vi.spyOn(Storage.prototype, 'setItem');
        let callCount = 0;
        setItemSpy.mockImplementation(() => {
            callCount++;
            if (callCount === 1) {
                throw new Error('Some random storage error');
            }
        });

        const { result } = renderHook(() => useCustomerOrderHistory());

        // Should not crash — the error is caught by writeStorage's outer try/catch
        act(() => {
            result.current.addOrder(makeOrder());
        });

        setItemSpy.mockRestore();
        // Hook state may be stale since writeStorage failed silently
        // but the hook itself must not throw
        expect(result.current.orders).toBeDefined();
    });

    // ──────────────────────────────────────────────────
    // Max 200 FIFO eviction
    // ──────────────────────────────────────────────────

    it('trims entries to MAX_ENTRIES (200)', () => {
        const { result } = renderHook(() => useCustomerOrderHistory());

        // Add 250 orders
        for (let i = 0; i < 250; i++) {
            act(() => {
                result.current.addOrder(
                    makeOrder({
                        order_code: `ORD-${String(i).padStart(4, '0')}`,
                        date: new Date(2026, 4, 18, 10, 0, i).toISOString(),
                    }),
                );
            });
        }

        expect(result.current.orders.length).toBeLessThanOrEqual(200);
    });

    it('evicts oldest entries first (FIFO)', () => {
        const { result } = renderHook(() => useCustomerOrderHistory());

        // Add 201 orders — first one should be evicted
        const oldest = makeOrder({
            order_code: 'ORD-OLDEST',
            date: '2026-05-01T00:00:00.000Z',
        });

        act(() => {
            result.current.addOrder(oldest);
        });

        for (let i = 0; i < 200; i++) {
            act(() => {
                result.current.addOrder(
                    makeOrder({
                        order_code: `ORD-${String(i).padStart(4, '0')}`,
                        date: new Date(2026, 5, 18, 10, 0, i).toISOString(),
                    }),
                );
            });
        }

        expect(result.current.orders.length).toBeLessThanOrEqual(200);
        const codes = result.current.orders.map((o) => o.order_code);
        expect(codes).not.toContain('ORD-OLDEST');
    });

    it('maintains exact MAX_ENTRIES when at limit', () => {
        const { result } = renderHook(() => useCustomerOrderHistory());

        for (let i = 0; i < __TEST_EXPORT_MAX_ENTRIES; i++) {
            act(() => {
                result.current.addOrder(
                    makeOrder({
                        order_code: `ORD-${String(i).padStart(4, '0')}`,
                        date: new Date(2026, 4, 18, 10, 0, i).toISOString(),
                    }),
                );
            });
        }

        expect(result.current.orders.length).toBe(__TEST_EXPORT_MAX_ENTRIES);
    });

    // ──────────────────────────────────────────────────
    // Schema migration
    // ──────────────────────────────────────────────────

    it('detects schema version mismatch and resets', () => {
        // Write data with an old schema version
        localStorage.setItem(
            __TEST_EXPORT_META_KEY,
            JSON.stringify({ version: 0, updatedAt: new Date().toISOString() }),
        );
        localStorage.setItem(
            __TEST_EXPORT_STORAGE_KEY,
            JSON.stringify([makeOrder({ order_code: 'ORD-OLD-SCHEMA' })]),
        );

        const { result } = renderHook(() => useCustomerOrderHistory());

        // Schema version mismatch triggers reset — orders and meta are removed
        expect(result.current.orders).toEqual([]);
        expect(localStorage.getItem(__TEST_EXPORT_STORAGE_KEY)).toBeNull();
        expect(localStorage.getItem(__TEST_EXPORT_META_KEY)).toBeNull();
    });

    it('writes correct schema version in meta', () => {
        const { result } = renderHook(() => useCustomerOrderHistory());

        act(() => {
            result.current.addOrder(makeOrder());
        });

        const metaRaw = localStorage.getItem(__TEST_EXPORT_META_KEY);
        expect(metaRaw).not.toBeNull();

        const meta = JSON.parse(metaRaw);
        expect(meta.version).toBe(__TEST_EXPORT_SCHEMA_VERSION);
        expect(meta.updatedAt).toBeDefined();
    });

    it('survives corrupt meta JSON in localStorage', () => {
        localStorage.setItem(__TEST_EXPORT_META_KEY, 'not-json{{');
        localStorage.setItem(
            __TEST_EXPORT_STORAGE_KEY,
            JSON.stringify([makeOrder({ order_code: 'ORD-WITH-CORRUPT-META' })]),
        );

        const { result } = renderHook(() => useCustomerOrderHistory());

        // readStorage doesn't read meta, so corrupt meta shouldn't affect orders
        expect(result.current.orders.length).toBeGreaterThanOrEqual(1);
    });

    // ──────────────────────────────────────────────────
    // Validation edge cases
    // ──────────────────────────────────────────────────

    it('rejects order missing required uuid field', () => {
        const { result } = renderHook(() => useCustomerOrderHistory());
        const warnSpy = vi.spyOn(console, 'warn').mockImplementation(() => {});

        act(() => {
            result.current.addOrder({ order_code: 'ORD-001', date: new Date().toISOString() });
        });

        expect(result.current.orders).toEqual([]);
        expect(warnSpy).toHaveBeenCalledWith(
            expect.stringContaining('Missing required field: uuid'),
        );

        warnSpy.mockRestore();
    });

    it('rejects order missing order_code', () => {
        const { result } = renderHook(() => useCustomerOrderHistory());
        const warnSpy = vi.spyOn(console, 'warn').mockImplementation(() => {});

        act(() => {
            result.current.addOrder({ uuid: crypto.randomUUID(), date: new Date().toISOString() });
        });

        expect(result.current.orders).toEqual([]);
        expect(warnSpy).toHaveBeenCalledWith(
            expect.stringContaining('Missing required field: order_code'),
        );

        warnSpy.mockRestore();
    });

    it('rejects order missing date field', () => {
        const { result } = renderHook(() => useCustomerOrderHistory());
        const warnSpy = vi.spyOn(console, 'warn').mockImplementation(() => {});

        act(() => {
            result.current.addOrder({ uuid: crypto.randomUUID(), order_code: 'ORD-001' });
        });

        expect(result.current.orders).toEqual([]);
        expect(warnSpy).toHaveBeenCalledWith(
            expect.stringContaining('Missing required field: date'),
        );

        warnSpy.mockRestore();
    });

    it('rejects falsy uuid (empty string)', () => {
        const { result } = renderHook(() => useCustomerOrderHistory());
        const warnSpy = vi.spyOn(console, 'warn').mockImplementation(() => {});

        act(() => {
            result.current.addOrder({
                uuid: '',
                order_code: 'ORD-001',
                date: new Date().toISOString(),
            });
        });

        expect(result.current.orders).toEqual([]);

        warnSpy.mockRestore();
    });

    // ──────────────────────────────────────────────────
    // getHistory refresh
    // ──────────────────────────────────────────────────

    it('getHistory re-reads from localStorage', () => {
        const { result } = renderHook(() => useCustomerOrderHistory());

        // Write directly to localStorage (bypass hook)
        const externalOrder = makeOrder({ order_code: 'ORD-EXTERNAL' });
        localStorage.setItem(__TEST_EXPORT_STORAGE_KEY, JSON.stringify([externalOrder]));

        act(() => {
            result.current.getHistory();
        });

        expect(result.current.orders).toHaveLength(1);
        expect(result.current.orders[0].order_code).toBe('ORD-EXTERNAL');
    });

    it('getHistory sorts by date descending', () => {
        const older = makeOrder({ order_code: 'ORD-OLDER', date: '2026-05-01T00:00:00.000Z' });
        const newer = makeOrder({ order_code: 'ORD-NEWER', date: '2026-05-18T00:00:00.000Z' });

        localStorage.setItem(__TEST_EXPORT_STORAGE_KEY, JSON.stringify([older, newer]));

        const { result } = renderHook(() => useCustomerOrderHistory());

        act(() => {
            result.current.getHistory();
        });

        expect(result.current.orders[0].order_code).toBe('ORD-NEWER');
        expect(result.current.orders[1].order_code).toBe('ORD-OLDER');
    });

    // ──────────────────────────────────────────────────
    // Persistence across remounts
    // ──────────────────────────────────────────────────

    it('persists orders across hook remounts', () => {
        const order = makeOrder();

        const { result: first, unmount } = renderHook(() => useCustomerOrderHistory());

        act(() => {
            first.current.addOrder(order);
        });

        unmount();

        const { result: second } = renderHook(() => useCustomerOrderHistory());

        expect(second.current.orders).toHaveLength(1);
        expect(second.current.orders[0].uuid).toBe(order.uuid);
    });
});
