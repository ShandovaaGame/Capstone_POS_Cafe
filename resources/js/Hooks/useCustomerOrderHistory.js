import { useState, useCallback } from 'react';

const STORAGE_KEY = 'order_history';
const META_KEY = 'order_history_meta';
const SCHEMA_VERSION = 1;
const MAX_ENTRIES = 200;
const PURGE_COUNT = 50;

const REQUIRED_FIELDS = ['uuid', 'order_code', 'date'];

function readMeta() {
  try {
    const raw = localStorage.getItem(META_KEY);
    if (!raw) return null;
    return JSON.parse(raw);
  } catch {
    return null;
  }
}

function readStorage() {
  const meta = readMeta();
  if (meta && meta.version !== SCHEMA_VERSION) {
    localStorage.removeItem(STORAGE_KEY);
    localStorage.removeItem(META_KEY);
    return [];
  }

  try {
    const raw = localStorage.getItem(STORAGE_KEY);
    if (!raw) return [];
    const parsed = JSON.parse(raw);
    if (!Array.isArray(parsed)) throw new Error('Invalid format');
    return parsed.filter(
      (entry) =>
        entry &&
        typeof entry === 'object' &&
        !Array.isArray(entry) &&
        REQUIRED_FIELDS.every((f) => entry[f]),
    );
  } catch {
    localStorage.removeItem(STORAGE_KEY);
    localStorage.removeItem(META_KEY);
    return [];
  }
}

function writeStorage(orders) {
  try {
    localStorage.setItem(STORAGE_KEY, JSON.stringify(orders));
    localStorage.setItem(
      META_KEY,
      JSON.stringify({ version: SCHEMA_VERSION, updatedAt: new Date().toISOString() }),
    );
  } catch (e) {
    if (e.name === 'QuotaExceededError' || e.code === 22) {
      const clamped = orders.slice(0, MAX_ENTRIES - PURGE_COUNT);
      localStorage.setItem(STORAGE_KEY, JSON.stringify(clamped));
      localStorage.setItem(
        META_KEY,
        JSON.stringify({ version: SCHEMA_VERSION, updatedAt: new Date().toISOString() }),
      );
    }
  }
}

function validateOrder(order) {
  for (const field of REQUIRED_FIELDS) {
    if (!order[field]) {
      console.warn(`[useCustomerOrderHistory] Missing required field: ${field}`);
      return false;
    }
  }
  return true;
}

export const __TEST_EXPORT_STORAGE_KEY = STORAGE_KEY;
export const __TEST_EXPORT_META_KEY = META_KEY;
export const __TEST_EXPORT_SCHEMA_VERSION = SCHEMA_VERSION;
export const __TEST_EXPORT_MAX_ENTRIES = MAX_ENTRIES;

export default function useCustomerOrderHistory() {
  const [orders, setOrders] = useState(() => {
    const list = readStorage();
    list.sort((a, b) => new Date(b.date) - new Date(a.date));
    return list;
  });

  const getHistory = useCallback(() => {
    const list = readStorage();
    list.sort((a, b) => new Date(b.date) - new Date(a.date));
    setOrders(list);
    return list;
  }, []);

  const addOrder = useCallback((order) => {
    if (!validateOrder(order)) return;

    const current = readStorage();
    const updated = [order, ...current];

    if (updated.length > MAX_ENTRIES) {
      updated.length = MAX_ENTRIES;
    }

    writeStorage(updated);
    setOrders(updated);
  }, []);

  const clearHistory = useCallback(() => {
    localStorage.removeItem(STORAGE_KEY);
    localStorage.removeItem(META_KEY);
    setOrders([]);
  }, []);

  return { orders, getHistory, addOrder, clearHistory };
}
