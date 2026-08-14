import { formatRupiah } from '@/helpers';

export default function MenuGridItem({ menu, onAdd }) {
    return (
        <div
            onClick={() => onAdd(menu)}
            style={{
                background: '#FFFFFF',
                border: '1.5px solid #D1D5DB',
                borderRadius: 8,
                padding: '20px 16px',
                cursor: 'pointer',
                boxShadow: 'none',
                transition: 'box-shadow 0.15s, transform 0.1s',
                display: 'flex',
                flexDirection: 'column',
                gap: 6,
                textAlign: 'center',
                fontFamily: "'Inter', system-ui, sans-serif",
            }}
            onMouseEnter={e => {
                e.currentTarget.style.boxShadow = '0 4px 12px rgba(0,0,0,0.08)';
                e.currentTarget.style.transform = 'translateY(-1px)';
            }}
            onMouseLeave={e => {
                e.currentTarget.style.boxShadow = 'none';
                e.currentTarget.style.transform = 'translateY(0)';
            }}
        >
            <div style={{ fontSize: 11, textTransform: 'uppercase', color: '#6B7280', letterSpacing: '0.6px', fontWeight: 600 }}>
                {menu.category?.name}
            </div>
            <div style={{ fontSize: 15, fontWeight: 700, color: '#111827', lineHeight: 1.3 }}>
                {menu.name}
            </div>
            <div style={{ fontSize: 14, fontWeight: 700, color: '#3B6FD4', fontFamily: "'DM Sans', 'Inter', system-ui" }}>
                {formatRupiah(menu.price)}
            </div>
        </div>
    );
}
