import { create } from 'zustand';

const useCartStore = create((set, get) => ({
    items: [],
    tableId: null,

    // ── Customer cart ──────────────────────────────────────────────────

    setTable: (tableId) => set({ tableId }),

    addItem: (menu) => set(state => {
        const existing = state.items.find(i => i.menuId === menu.id);
        if (existing) return {
            items: state.items.map(i =>
                i.menuId === menu.id ? { ...i, quantity: i.quantity + 1 } : i
            ),
        };
        return {
            items: [...state.items, {
                menuId:   menu.id,
                name:     menu.name,
                price:    Number(menu.price),
                cashback: Number(menu.cashback ?? 0),
                quantity: 1,
                image:    menu.image ?? null,
            }],
        };
    }),

    removeItem: (menuId) => set(state => ({
        items: state.items.filter(i => i.menuId !== menuId),
    })),

    updateQty: (menuId, qty) => set(state => ({
        items: qty <= 0
            ? state.items.filter(i => i.menuId !== menuId)
            : state.items.map(i => i.menuId === menuId ? { ...i, quantity: qty } : i),
    })),

    clearCart: () => set({ items: [], tableId: null }),

    total:    () => get().items.reduce((s, i) => s + i.price * i.quantity, 0),
    count:    () => get().items.reduce((s, i) => s + i.quantity, 0),
    totalQty: () => get().items.reduce((s, i) => s + i.quantity, 0),

    // ── Cashier cart ──────────────────────────────────────────────────

    cashierItems: [],

    cashierAddItem: (menu) => set(state => {
        const existing = state.cashierItems.find(i => i.menuId === menu.id);
        if (existing) return {
            cashierItems: state.cashierItems.map(i =>
                i.menuId === menu.id ? { ...i, quantity: i.quantity + 1 } : i
            ),
        };
        return {
            cashierItems: [...state.cashierItems, {
                menuId:   menu.id,
                name:     menu.name,
                price:    Number(menu.price),
                cashback: Number(menu.cashback ?? 0),
                quantity: 1,
                image:    menu.image ?? null,
            }],
        };
    }),

    cashierRemoveItem: (menuId) => set(state => ({
        cashierItems: state.cashierItems.filter(i => i.menuId !== menuId),
    })),

    cashierUpdateQty: (menuId, qty) => set(state => ({
        cashierItems: qty <= 0
            ? state.cashierItems.filter(i => i.menuId !== menuId)
            : state.cashierItems.map(i => i.menuId === menuId ? { ...i, quantity: qty } : i),
    })),

    cashierClearCart: () => set({ cashierItems: [] }),

    cashierIncrement: (menuId) => set(state => ({
        cashierItems: state.cashierItems.map(i =>
            i.menuId === menuId ? { ...i, quantity: i.quantity + 1 } : i
        ),
    })),

    cashierDecrement: (menuId) => set(state => ({
        cashierItems: state.cashierItems
            .map(i => i.menuId === menuId ? { ...i, quantity: i.quantity - 1 } : i)
            .filter(i => i.quantity > 0),
    })),

    cashierTotal:    () => get().cashierItems.reduce((s, i) => s + i.price * i.quantity, 0),
    cashierCount:    () => get().cashierItems.length,
    cashierTotalQty: () => get().cashierItems.reduce((s, i) => s + i.quantity, 0),
}));

export default useCartStore;
