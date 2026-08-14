import { Coffee } from 'lucide-react';
import { formatRupiah } from '@/helpers';

const F = '"Inter", system-ui, sans-serif';

/* stone-bg dari Stitch */
const BG = '#F7F5F2';

const STATUS_MAP = {
    pending:  { label: 'Pending',  dot: '#FBBF24', bg: 'rgba(251,191,36,0.10)',  border: 'rgba(251,191,36,0.20)'  },
    diproses: { label: 'Diproses', dot: '#60A5FA', bg: 'rgba(96,165,250,0.10)',  border: 'rgba(96,165,250,0.20)'  },
    selesai:  { label: 'Selesai',  dot: '#4ADE80', bg: 'rgba(74,222,128,0.10)', border: 'rgba(74,222,128,0.20)' },
};

function getPaymentLabel(method) {
    if (method === 'cash') return 'Tunai';
    if (method === 'qris') return 'QRIS';
    return '';
}

export default function RiwayatCard({ order, onDetail }) {
    const s         = STATUS_MAP[order.status] ?? { label: order.status, dot: '#A8A29E', bg: 'rgba(168,162,158,0.10)', border: 'rgba(168,162,158,0.20)' };
    const payLabel  = getPaymentLabel(order.payment_method);
    const itemCount = order.items?.length ?? 0;
    const subtitle  = [payLabel, itemCount ? `${itemCount} item` : ''].filter(Boolean).join(' • ');
    const hasItems  = Array.isArray(order.items) && order.items.length > 0;

    return (
        <article style={{
            background: 'rgba(255,255,255,0.90)',
            backdropFilter: 'blur(6px)',
            borderRadius: 12,
            border: '1px solid #E2DED8',
            padding: 20,
            marginBottom: 14,
            boxShadow: '0 2px 8px -2px rgba(0,0,0,0.05)',
        }}>
            {/* ── Top row: icon + info + status badge ── */}
            <div style={{ display: 'flex', justifyContent: 'space-between', alignItems: 'flex-start', marginBottom: 14 }}>
                <div style={{ display: 'flex', alignItems: 'center', gap: 12 }}>
                    {/* Coffee icon circle */}
                    <div style={{
                        width: 40, height: 40, borderRadius: '50%',
                        background: BG,
                        display: 'flex', alignItems: 'center', justifyContent: 'center',
                        flexShrink: 0,
                    }}>
                        <Coffee size={18} color="#44403C" strokeWidth={1.75} />
                    </div>
                    <div>
                        <h3 style={{
                            fontSize: 14, fontWeight: 700, color: '#1C1917',
                            fontFamily: F, margin: 0, letterSpacing: '-0.01em',
                        }}>
                            {order.order_code}
                        </h3>
                        <p style={{ fontSize: 12, color: 'rgba(68,64,60,0.60)', fontFamily: F, marginTop: 2 }}>
                            {subtitle}
                        </p>
                    </div>
                </div>

                {/* Status badge */}
                <div style={{
                    background: s.bg,
                    border: `1px solid ${s.border}`,
                    borderRadius: 999,
                    padding: '4px 10px',
                    display: 'flex', alignItems: 'center', gap: 5,
                    flexShrink: 0,
                }}>
                    <span style={{ width: 6, height: 6, borderRadius: '50%', background: s.dot, flexShrink: 0 }} />
                    <span style={{
                        fontSize: 10, fontWeight: 700, color: '#1C1917',
                        fontFamily: F, textTransform: 'uppercase', letterSpacing: '0.04em',
                    }}>
                        {s.label}
                    </span>
                </div>
            </div>

            {/* ── Item list ── */}
            <div style={{
                borderTop: `1px solid ${BG}`,
                borderBottom: `1px solid ${BG}`,
                padding: '12px 0',
                marginBottom: 14,
                display: 'flex', flexDirection: 'column', gap: 8,
            }}>
                {hasItems ? order.items.map((item, i) => (
                    <div key={i} style={{ display: 'flex', justifyContent: 'space-between', fontSize: 13 }}>
                        <span style={{ color: '#44403C', fontFamily: F }}>
                            <span style={{ fontWeight: 600 }}>{item.quantity}×</span> {item.name}
                        </span>
                        <span style={{ color: '#1C1917', fontWeight: 500, fontFamily: F }}>
                            {formatRupiah(item.subtotal)}
                        </span>
                    </div>
                )) : (
                    <span style={{ fontSize: 13, color: '#78716C', fontFamily: F }}>
                        {order.items_summary}
                    </span>
                )}
            </div>

            {/* ── Footer: total + detail button ── */}
            <div style={{ display: 'flex', justifyContent: 'space-between', alignItems: 'flex-end' }}>
                <div>
                    <p style={{
                        fontSize: 10, fontWeight: 700, color: 'rgba(68,64,60,0.50)',
                        textTransform: 'uppercase', letterSpacing: '0.06em',
                        fontFamily: F, marginBottom: 3,
                    }}>
                        TOTAL
                    </p>
                    <p style={{ fontSize: 20, fontWeight: 700, color: '#1C1917', fontFamily: F, letterSpacing: '-0.02em' }}>
                        {formatRupiah(order.total_amount)}
                    </p>
                </div>
                <button
                    onClick={() => onDetail(order)}
                    style={{
                        padding: '8px 22px',
                        border: '1px solid #E2DED8',
                        borderRadius: 8,
                        background: 'transparent',
                        fontSize: 13, fontWeight: 700,
                        color: '#44403C', fontFamily: F,
                        cursor: 'pointer',
                        transition: 'background 0.15s',
                    }}
                >
                    Detail
                </button>
            </div>
        </article>
    );
}
