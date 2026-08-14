import { formatRupiah } from '@/helpers';

export default function KeranjangItem({ item, onIncrement, onDecrement }) {
    return (
        <div style={{ display: 'flex', alignItems: 'center', padding: '14px 0', borderBottom: '1px solid #F3F4F6', gap: 12 }}>
            {/* Name + unit price */}
            <div style={{ flex: 1, minWidth: 0 }}>
                <div style={{ fontSize: 14, fontWeight: 600, color: '#111827', overflow: 'hidden', textOverflow: 'ellipsis', whiteSpace: 'nowrap' }}>
                    {item.name}
                </div>
                <div style={{ fontSize: 12, color: '#9CA3AF', marginTop: 2 }}>
                    {formatRupiah(item.price)}
                </div>
            </div>

            {/* [-] qty [+] */}
            <div style={{ display: 'flex', alignItems: 'center', gap: 8, flexShrink: 0 }}>
                <button
                    onClick={() => onDecrement(item.menuId)}
                    style={{ width: 30, height: 30, background: '#FFFFFF', color: '#6B7280', border: '1px solid #E5E7EB', borderRadius: 6, fontSize: 18, fontWeight: 700, cursor: 'pointer', display: 'flex', alignItems: 'center', justifyContent: 'center' }}
                >
                    −
                </button>

                <span style={{ fontSize: 14, fontWeight: 600, color: '#111827', minWidth: 20, textAlign: 'center' }}>
                    {item.quantity}
                </span>

                <button
                    onClick={() => onIncrement(item.menuId)}
                    style={{ width: 30, height: 30, background: '#3B6FD4', color: '#FFFFFF', border: 'none', borderRadius: 6, fontSize: 18, fontWeight: 700, cursor: 'pointer', display: 'flex', alignItems: 'center', justifyContent: 'center' }}
                >
                    +
                </button>

                {/* Item total */}
                <span style={{ fontSize: 14, fontWeight: 700, color: '#111827', minWidth: 76, textAlign: 'right', flexShrink: 0 }}>
                    {formatRupiah(item.price * item.quantity)}
                </span>
            </div>
        </div>
    );
}
