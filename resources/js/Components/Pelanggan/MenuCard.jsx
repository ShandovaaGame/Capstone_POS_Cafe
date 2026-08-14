import { useState } from 'react';
import { Coffee, Plus } from 'lucide-react';
import { formatRupiah } from '@/helpers';

export default function MenuCard({ menu, onAdd }) {
    const [pressing, setPressing] = useState(false);

    const displayPrice = menu.is_student_discount && menu.student_price
        ? Number(menu.student_price)
        : Number(menu.price);

    return (
        <div style={{
            background: '#FFFFFF',
            borderRadius: 18,
            border: '1px solid #EDE8E2',
            overflow: 'hidden',
            boxShadow: '0 4px 14px rgba(45,32,22,0.06)',
        }}>
            {/* Image placeholder */}
            <div style={{
                background: '#F5F0EB',
                height: 110,
                borderRadius: '12px 12px 0 0',
                display: 'flex', alignItems: 'center', justifyContent: 'center',
                overflow: 'hidden',
            }}>
                {menu.image
                    ? <img
                        src={menu.image}
                        alt={menu.name}
                        style={{ width: '100%', height: '100%', objectFit: 'cover' }}
                      />
                    : <Coffee size={32} color="#B5A898" />
                }
            </div>

            {/* Content */}
            <div style={{
                padding: '10px 12px 14px',
                display: 'flex', flexDirection: 'column', gap: 6,
            }}>
                <div style={{
                    fontSize: 14, fontWeight: 700, color: '#2D2016',
                    fontFamily: '"DM Sans", system-ui, sans-serif',
                    lineHeight: 1.3,
                }}>
                    {menu.name}
                </div>

                {menu.is_student_discount && menu.student_price && (
                    <div style={{ fontSize: 11, color: '#8C7B6B', textDecoration: 'line-through' }}>
                        {formatRupiah(menu.price)}
                    </div>
                )}

                <div style={{ fontSize: 13, fontWeight: 600, color: '#E8763A' }}>
                    {formatRupiah(displayPrice)}
                </div>

                {/* Add button */}
                <button
                    onClick={() => onAdd(menu)}
                    onMouseDown={() => setPressing(true)}
                    onMouseUp={() => setPressing(false)}
                    onMouseLeave={() => setPressing(false)}
                    style={{
                        width: '100%', height: 34,
                        background: pressing ? '#D0682F' : '#E8763A',
                        color: '#FFFFFF', border: 'none', borderRadius: 14,
                        display: 'flex', alignItems: 'center', justifyContent: 'center',
                        gap: 6, fontSize: 12, fontWeight: 700, cursor: 'pointer',
                        boxShadow: '0 2px 8px rgba(232,118,58,0.22)',
                        transition: 'background 0.12s',
                    }}
                >
                    <Plus size={14} />
                    Tambah
                </button>
            </div>
        </div>
    );
}
