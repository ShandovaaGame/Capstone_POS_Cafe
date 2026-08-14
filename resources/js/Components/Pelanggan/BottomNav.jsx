import { Link } from '@inertiajs/react';
import { Home, ShoppingCart, Clock } from 'lucide-react';
import useCart from '@/Hooks/useCart';

const F = '"Inter", system-ui, sans-serif';

function getRiwayatHref() {
    try {
        const saved = sessionStorage.getItem('w9_customer');
        if (saved) {
            const data = JSON.parse(saved);
            if (data?.phone) return `/customer/riwayat?phone=${encodeURIComponent(data.phone)}`;
        }
    } catch (_) {}
    return '/customer/riwayat';
}

export default function BottomNav({ activeTab }) {
    const { count } = useCart();

    const TABS = [
        { key: 'menu',    label: 'Menu',      Icon: Home,         href: '/pelanggan/menu' },
        { key: 'cart',    label: 'Keranjang', Icon: ShoppingCart, href: '/pelanggan/keranjang' },
        { key: 'riwayat', label: 'Riwayat',   Icon: Clock,        href: getRiwayatHref() },
    ];

    return (
        <div style={{
            position:        'fixed',
            bottom:          0,
            left:            '50%',
            transform:       'translateX(-50%)',
            width:           '100%',
            maxWidth:        430,
            background:      '#FFFFFF',
            borderRadius:    '20px 20px 0 0',
            boxShadow:       '0 -2px 16px rgba(28,25,23,0.08)',
            zIndex:          100,
            paddingBottom:   'env(safe-area-inset-bottom, 0px)',
        }}>
            <div style={{
                display:        'flex',
                alignItems:     'center',
                height:         64,
                padding:        '0 8px',
            }}>
                {TABS.map(({ key, label, Icon, href }) => {
                    const active    = activeTab === key;
                    const showBadge = key === 'cart' && count > 0;

                    return (
                        <Link
                            key={key}
                            href={href}
                            style={{
                                flex:           1,
                                display:        'flex',
                                flexDirection:  'column',
                                alignItems:     'center',
                                justifyContent: 'center',
                                gap:            4,
                                textDecoration: 'none',
                                fontFamily:     F,
                                position:       'relative',
                            }}
                        >
                            <div style={{
                                width:           active ? 44 : 32,
                                height:          active ? 32 : 32,
                                borderRadius:    10,
                                background:      active ? '#2C2A27' : 'transparent',
                                display:         'flex',
                                alignItems:      'center',
                                justifyContent:  'center',
                                transition:      'background 0.2s ease, width 0.2s ease',
                                position:        'relative',
                            }}>
                                <Icon
                                    size={18}
                                    color={active ? '#FFFFFF' : '#A8A29E'}
                                    strokeWidth={active ? 2.2 : 1.75}
                                />
                                {showBadge && (
                                    <span style={{
                                        position:       'absolute',
                                        top:            -4,
                                        right:          -4,
                                        background:     '#D97706',
                                        color:          '#FFFFFF',
                                        borderRadius:   '50%',
                                        width:          15,
                                        height:         15,
                                        fontSize:       8,
                                        fontWeight:     700,
                                        fontFamily:     F,
                                        display:        'flex',
                                        alignItems:     'center',
                                        justifyContent: 'center',
                                        border:         '1.5px solid #FFFFFF',
                                    }}>
                                        {count > 9 ? '9+' : count}
                                    </span>
                                )}
                            </div>

                            <span style={{
                                fontSize:      10,
                                fontWeight:    active ? 600 : 400,
                                color:         active ? '#2C2A27' : '#A8A29E',
                                letterSpacing: '0.01em',
                                lineHeight:    1,
                            }}>
                                {label}
                            </span>
                        </Link>
                    );
                })}
            </div>
        </div>
    );
}
