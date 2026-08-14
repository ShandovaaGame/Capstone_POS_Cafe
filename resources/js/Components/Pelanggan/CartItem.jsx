import { formatRupiah } from '@/helpers';

export default function CartItem({ item, onIncrement, onDecrement, showDivider = true }) {
    return (
        <div style={{
            display: 'flex', alignItems: 'center', gap: 14,
            padding: '16px 18px',
            borderBottom: showDivider ? '1px solid #F5F0EB' : 'none',
        }}>
            {/* Item info */}
            <div style={{ flex: 1, display: 'flex', flexDirection: 'column', gap: 2 }}>
                <span style={{
                    fontSize: 15, fontWeight: 600, color: '#2D2016',
                    fontFamily: 'Outfit, system-ui, sans-serif',
                }}>
                    {item.name}
                </span>
                <span style={{
                    fontSize: 13, color: '#8C7B6B',
                    fontFamily: 'Outfit, system-ui, sans-serif',
                }}>
                    {formatRupiah(item.price)}
                </span>
            </div>

            {/* Qty controls */}
            <div style={{ display: 'flex', alignItems: 'center', gap: 12 }}>
                {/* Minus */}
                <button
                    onClick={() => onDecrement(item.menuId)}
                    style={{
                        width: 32, height: 32, borderRadius: 12,
                        background: '#F5F0EB', border: 'none',
                        display: 'flex', alignItems: 'center', justifyContent: 'center',
                        fontSize: 18, fontWeight: 600, color: '#2D2016',
                        cursor: 'pointer',
                    }}
                >
                    −
                </button>

                {/* Quantity */}
                <span style={{
                    fontSize: 16, fontWeight: 700, color: '#2D2016',
                    minWidth: 24, textAlign: 'center',
                    fontFamily: 'Outfit, system-ui, sans-serif',
                }}>
                    {item.quantity}
                </span>

                {/* Plus */}
                <button
                    onClick={() => onIncrement(item.menuId)}
                    style={{
                        width: 32, height: 32, borderRadius: 12,
                        background: '#E8763A', border: 'none',
                        display: 'flex', alignItems: 'center', justifyContent: 'center',
                        fontSize: 18, fontWeight: 600, color: '#FFFFFF',
                        cursor: 'pointer',
                        boxShadow: '0 2px 6px rgba(232,118,58,0.25)',
                    }}
                >
                    +
                </button>
            </div>
        </div>
    );
}
