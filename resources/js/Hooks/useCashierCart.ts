import useCartStore from '@/Store/cartStore';

// ── Hook ────────────────────────────────────────────────────────────

export function useCashierCart() {
  const cashierItems = useCartStore(s => s.cashierItems);
  const cashierTotal = useCartStore(s => s.cashierTotal());
  const cashierCount = useCartStore(s => s.cashierCount());

  const cashierAddItem = useCartStore(s => s.cashierAddItem);
  const cashierRemoveItem = useCartStore(s => s.cashierRemoveItem);
  const cashierUpdateQty = useCartStore(s => s.cashierUpdateQty);
  const cashierClearCart = useCartStore(s => s.cashierClearCart);
  const cashierIncrement = useCartStore(s => s.cashierIncrement);
  const cashierDecrement = useCartStore(s => s.cashierDecrement);

  return {
    cashierItems,
    cashierTotal,
    cashierCount,
    cashierAddItem,
    cashierRemoveItem,
    cashierUpdateQty,
    cashierClearCart,
    cashierIncrement,
    cashierDecrement,
  };
}

export default useCashierCart;
