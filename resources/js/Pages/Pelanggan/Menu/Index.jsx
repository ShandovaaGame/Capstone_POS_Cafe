import { useState, useEffect, useMemo } from 'react';
import { router, Head } from '@inertiajs/react';
import { Search, ShoppingBag, Coffee } from 'lucide-react';
import PelangganLayout from '@/Layouts/PelangganLayout';
import useCart from '@/Hooks/useCart';
import { formatRupiah } from '@/helpers';

const F = '"Inter", system-ui, sans-serif';

const C = {
    bg:          '#F7F5F2',
    surface:     '#FFFFFF',
    surfaceAlt:  '#EFEDE9',
    border:      '#E2DED8',
    accent:      '#44403C',
    accentHover: '#1C1917',
    textPrimary: '#1C1917',
    textSecond:  '#78716C',
    textMuted:   '#A8A29E',
    success:     '#16A34A',
    warning:     '#FBBF24',
    shadow:      '0 4px 20px -2px rgba(0,0,0,0.05)',
    shadowLift:  '0 8px 24px -2px rgba(0,0,0,0.10)',
};

function MenuItemCard({ menu, cartItem, onAdd, onIncrement, onDecrement, priority = false, isMahasiswa = false }) {
    const cashback = Number(menu.cashback ?? 0);
    const soldOut  = menu.is_available === false;

    return (
        <article className="w9-card" style={{
            background:    'rgba(255,255,255,0.90)',
            backdropFilter: 'blur(6px)',
            borderRadius:  12,
            overflow:      'hidden',
            border:        `1px solid rgba(226,222,216,0.60)`,
            boxShadow:     C.shadow,
            display:       'flex',
            flexDirection: 'column',
            opacity:       soldOut ? 0.75 : 1,
        }}>
            <div style={{
                position:       'relative',
                width:          '100%',
                aspectRatio:    '1 / 1',
                background:     'rgba(193,154,107,0.15)',
                display:        'flex',
                alignItems:     'center',
                justifyContent: 'center',
                overflow:       'hidden',
            }}>
                {menu.image_url
                    ? <img src={menu.image_url} alt={menu.name}
                        loading={priority ? 'eager' : 'lazy'}
                        decoding={priority ? 'sync' : 'async'}
                        style={{ width: '100%', height: '100%', objectFit: 'cover' }}
                      />
                    : <Coffee size={28} color="rgba(193,154,107,0.70)" strokeWidth={1.5} />
                }
                {soldOut && (
                    <div style={{
                        position:       'absolute',
                        inset:          0,
                        display:        'flex',
                        alignItems:     'center',
                        justifyContent: 'center',
                        background:     'rgba(0,0,0,0.20)',
                        backdropFilter: 'blur(1px)',
                    }}>
                        <span style={{
                            background:    'rgba(68,64,60,0.90)',
                            color:         '#FFFFFF',
                            fontSize:      10,
                            fontWeight:    700,
                            fontFamily:    F,
                            letterSpacing: '0.05em',
                            textTransform: 'uppercase',
                            padding:       '4px 12px',
                            borderRadius:  999,
                        }}>
                            Stok Habis
                        </span>
                    </div>
                )}
            </div>

            <div style={{
                padding:       '14px 14px 14px',
                flex:          1,
                display:       'flex',
                flexDirection: 'column',
                gap:           4,
            }}>
                <h3 style={{
                    fontSize:      13,
                    fontWeight:    700,
                    color:         C.textPrimary,
                    fontFamily:    F,
                    lineHeight:    1.3,
                    letterSpacing: '-0.01em',
                    margin:        0,
                }}>
                    {menu.name}
                </h3>

                <p style={{
                    fontSize:   13,
                    fontWeight: 600,
                    color:      C.accent,
                    fontFamily: F,
                    margin:     0,
                }}>
                    {formatRupiah(Number(menu.price))}
                </p>

                {isMahasiswa && cashback > 0 && (
                    <p style={{
                        fontSize:   10,
                        fontWeight: 500,
                        color:      C.success,
                        fontFamily: F,
                        display:    'flex',
                        alignItems: 'center',
                        gap:        4,
                        margin:     0,
                    }}>
                        <span style={{ width: 5, height: 5, borderRadius: '50%', background: C.success, flexShrink: 0 }} />
                        Cashback {formatRupiah(cashback)}
                    </p>
                )}

                <div style={{ marginTop: 'auto', paddingTop: 10 }}>
                    {soldOut ? (
                        <button disabled style={{
                            width:        '100%',
                            background:   '#E5E1DB',
                            color:        C.textMuted,
                            border:       'none',
                            borderRadius: 8,
                            padding:      '8px 0',
                            fontSize:     12,
                            fontWeight:   700,
                            fontFamily:   F,
                            cursor:       'not-allowed',
                        }}>
                            Stok Habis
                        </button>
                    ) : cartItem ? (
                        <div style={{
                            display:        'flex',
                            alignItems:     'center',
                            justifyContent: 'space-between',
                            background:     'rgba(247,245,242,0.70)',
                            borderRadius:   8,
                            padding:        '3px',
                        }}>
                            <button onClick={onDecrement} style={{
                                width:          28, height: 28,
                                borderRadius:   6,
                                background:     C.surface,
                                border:         'none',
                                cursor:         'pointer',
                                fontSize:       16, fontWeight: 500,
                                color:          C.accent,
                                display:        'flex',
                                alignItems:     'center',
                                justifyContent: 'center',
                                boxShadow:      '0 1px 3px rgba(0,0,0,0.08)',
                            }}>−</button>

                            <span style={{
                                fontSize:   12, fontWeight: 700,
                                color:      C.textPrimary,
                                minWidth:   20,
                                textAlign:  'center',
                                fontFamily: F,
                                padding:    '0 6px',
                            }}>
                                {cartItem.quantity}
                            </span>

                            <button onClick={onIncrement} style={{
                                width:          28, height: 28,
                                borderRadius:   6,
                                background:     C.accent,
                                border:         'none',
                                cursor:         'pointer',
                                fontSize:       16, fontWeight: 600,
                                color:          C.surface,
                                display:        'flex',
                                alignItems:     'center',
                                justifyContent: 'center',
                                boxShadow:      '0 1px 3px rgba(0,0,0,0.12)',
                            }}>+</button>
                        </div>
                    ) : (
                        <button onClick={onAdd} className="w9-add-btn" style={{
                            width:         '100%',
                            background:    C.accent,
                            color:         C.surface,
                            border:        'none',
                            borderRadius:  8,
                            padding:       '8px 0',
                            fontSize:      12,
                            fontWeight:    700,
                            fontFamily:    F,
                            cursor:        'pointer',
                            letterSpacing: '0.01em',
                        }}>
                            + Tambah
                        </button>
                    )}
                </div>
            </div>
        </article>
    );
}

export default function CustomerMenu({ categories, table }) {
    const [activeCategory, setActiveCategory] = useState('all');
    const [search,         setSearch]         = useState('');
    const [customer,       setCustomer]       = useState(null);
    const [ready,          setReady]          = useState(false);

    const { items, addItem, updateQty, setTable, total, count } = useCart();

    useEffect(() => {
        try {
            const saved = sessionStorage.getItem('w9_customer');
            if (!saved) {
                const fb = table?.id ?? '';
                router.visit(fb ? `/order?table=${fb}` : '/order');
                return;
            }
            const data = JSON.parse(saved);
            if (!data.name || !data.phone) {
                sessionStorage.removeItem('w9_customer');
                const fb = table?.id ?? '';
                router.visit(fb ? `/order?table=${fb}` : '/order');
                return;
            }
            if (table?.id && data.tableId !== table.id) {
                sessionStorage.removeItem('w9_customer');
                router.visit(`/order?table=${table.id}`);
                return;
            }
            setCustomer(data);
            setTable(table?.id ?? data.tableId ?? null);
            setReady(true);
        } catch (_) {
            router.visit('/order');
        }
    }, [table?.id]);

    const cartMap = useMemo(() => {
        const m = {};
        items.forEach(i => { m[i.menuId] = i; });
        return m;
    }, [items]);

    const allMenus = useMemo(
        () => categories.flatMap(c => c.menus.map(m => ({ ...m, categoryName: c.name }))),
        [categories]
    );

    const filtered = useMemo(() => {
        let list = activeCategory === 'all'
            ? allMenus
            : allMenus.filter(m => m.categoryName === activeCategory);
        if (search.trim()) {
            const q = search.toLowerCase();
            list = list.filter(m => m.name.toLowerCase().includes(q));
        }
        return list;
    }, [allMenus, activeCategory, search]);

    const grouped = useMemo(() => {
        if (activeCategory !== 'all') return [{ label: activeCategory, menus: filtered }];
        const map = {};
        filtered.forEach(m => {
            if (!map[m.categoryName]) map[m.categoryName] = [];
            map[m.categoryName].push(m);
        });
        return Object.entries(map).map(([label, menus]) => ({ label, menus }));
    }, [filtered, activeCategory]);

    if (!ready) return null;

    return (
        <PelangganLayout activeTab="menu">
            <Head>
                <title>Menu — W9 Cafe</title>
                <link rel="preconnect" href="https://fonts.googleapis.com" />
                <link rel="preconnect" href="https://fonts.gstatic.com" crossOrigin="anonymous" />
                <link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" />
                <style>{`
                    html, body { background: #F7F5F2; }
                    .w9-card { transition: box-shadow 0.2s ease, transform 0.2s ease; }
                    .w9-card:hover { box-shadow: ${C.shadowLift} !important; transform: translateY(-2px); }
                    .w9-add-btn { transition: background 0.15s ease; }
                    .w9-add-btn:hover { background: ${C.accentHover} !important; }
                    .w9-chip { transition: background 0.15s, color 0.15s; }
                    .w9-search:focus { outline: none; box-shadow: 0 0 0 2px rgba(68,64,60,0.20) !important; }
                    .w9-chips::-webkit-scrollbar { display: none; }
                    .w9-scroll::-webkit-scrollbar { display: none; }
                    .w9-wallpaper {
                        position: fixed; top: 0; left: 50%; transform: translateX(-50%);
                        width: 100%; max-width: 430px; height: 100vh;
                        z-index: 0; pointer-events: none; overflow: hidden; background: #F2EFE9;
                    }
                `}</style>
            </Head>

            <div className="w9-wallpaper" aria-hidden="true">
                <img src="/images/wallpaper-menu.jpg" alt=""
                    fetchPriority="high" loading="eager"
                    style={{ width: '100%', height: '100%', objectFit: 'cover', objectPosition: 'center top', display: 'block' }}
                />
            </div>

            <div style={{
                position: 'fixed', top: 0, left: '50%', transform: 'translateX(-50%)',
                width: '100%', maxWidth: 430, height: '100vh',
                display: 'flex', flexDirection: 'column', zIndex: 1,
            }}>

                <header style={{ padding: '24px 20px 14px', flexShrink: 0, background: 'transparent' }}>

                    <div style={{ display: 'flex', alignItems: 'center', gap: 14, marginBottom: 14 }}>

                        <div style={{
                            width: 48, height: 48, borderRadius: 14,
                            background: C.accent,
                            overflow: 'hidden', flexShrink: 0,
                            display: 'flex', alignItems: 'center', justifyContent: 'center',
                            boxShadow: '0 2px 8px rgba(68,64,60,0.25)',
                        }}>
                            <img
                                src="/images/logo.jpg" alt="W9 Cafe"
                                fetchPriority="high" loading="eager"
                                style={{ width: '100%', height: '100%', objectFit: 'cover', opacity: 0.90 }}
                                onError={e => {
                                    e.target.style.display = 'none';
                                    e.target.parentElement.innerHTML = '<span style="color:#fff;font-size:16px;font-style:italic;font-weight:700">w9</span>';
                                }}
                            />
                        </div>

                        <div style={{ flex: 1 }}>
                            <p style={{
                                fontSize: 10, fontWeight: 500, color: C.textSecond,
                                fontFamily: F, textTransform: 'uppercase',
                                letterSpacing: '0.10em', margin: '0 0 3px',
                            }}>
                                Selamat datang,
                            </p>
                            <h1 style={{
                                fontSize: 19, fontWeight: 700, color: C.textPrimary,
                                fontFamily: F, letterSpacing: '-0.02em',
                                margin: 0, lineHeight: 1.2,
                            }}>
                                {customer?.name ?? 'Pelanggan'}
                                <span style={{
                                    fontSize: 13, fontWeight: 400, color: C.textMuted,
                                    marginLeft: 6, letterSpacing: 0,
                                }}>
                                    • Meja {table?.table_number ?? customer?.tableNumber ?? '—'}
                                </span>
                            </h1>
                        </div>

                    </div>

                    <div style={{ position: 'relative' }}>
                        <Search size={18} color={C.textMuted} strokeWidth={2}
                            style={{
                                position: 'absolute', left: 14,
                                top: '50%', transform: 'translateY(-50%)',
                                pointerEvents: 'none',
                            }}
                        />
                        <input
                            type="text"
                            value={search}
                            onChange={e => setSearch(e.target.value)}
                            placeholder="Cari menu favoritmu..."
                            className="w9-search"
                            style={{
                                width: '100%', height: 46,
                                background: 'rgba(255,255,255,0.90)',
                                backdropFilter: 'blur(6px)',
                                border: 'none',
                                borderRadius: 12,
                                padding: '0 16px 0 42px',
                                fontSize: 14, fontWeight: 400,
                                color: C.textPrimary, fontFamily: F,
                                outline: 'none', boxSizing: 'border-box',
                                boxShadow: C.shadow,
                            }}
                        />
                    </div>
                </header>

                <div style={{ flexShrink: 0 }}>
                    <div className="w9-chips" style={{
                        display: 'flex', gap: 8, overflowX: 'auto',
                        scrollbarWidth: 'none', padding: '4px 20px 14px',
                    }}>
                        {[{ id: 'all', name: 'Semua' }, ...categories].map(c => {
                            const active = activeCategory === (c.id === 'all' ? 'all' : c.name);
                            return (
                                <button
                                    key={c.id}
                                    onClick={() => setActiveCategory(c.id === 'all' ? 'all' : c.name)}
                                    className="w9-chip"
                                    style={{
                                        flexShrink:     0,
                                        background:     active ? C.accent : 'rgba(255,255,255,0.90)',
                                        backdropFilter: active ? 'none' : 'blur(6px)',
                                        borderRadius:   12,
                                        border:         active ? 'none' : '1px solid rgba(255,255,255,0.20)',
                                        padding:        '8px 20px',
                                        fontSize:       13,
                                        fontWeight:     active ? 600 : 500,
                                        color:          active ? C.surface : C.textSecond,
                                        fontFamily:     F,
                                        cursor:         'pointer',
                                        whiteSpace:     'nowrap',
                                        boxShadow:      active ? '0 2px 8px rgba(68,64,60,0.25)' : C.shadow,
                                    }}
                                >
                                    {c.name}
                                </button>
                            );
                        })}
                    </div>
                </div>

                <div className="w9-scroll" style={{
                    flex: 1, overflowY: 'auto',
                    padding: '4px 16px',
                    paddingBottom: count > 0 ? 140 : 80,
                    scrollbarWidth: 'none',
                    WebkitOverflowScrolling: 'touch',
                }}>
                    {filtered.length === 0 ? (
                        <div style={{
                            textAlign: 'center', color: C.textMuted,
                            padding: '64px 0', fontSize: 14,
                            fontFamily: F, lineHeight: 1.6,
                        }}>
                            Tidak ada menu ditemukan
                        </div>

                    ) : activeCategory === 'all' ? (
                        <div style={{ display: 'grid', gridTemplateColumns: '1fr 1fr', gap: 12 }}>
                            {filtered.map((menu, idx) => (
                                <MenuItemCard
                                    key={menu.id} menu={menu} priority={idx < 4}
                                    cartItem={cartMap[menu.id]}
                                    isMahasiswa={!!customer?.isMahasiswa}
                                    onAdd={() => addItem(menu)}
                                    onIncrement={() => updateQty(menu.id, (cartMap[menu.id]?.quantity ?? 0) + 1)}
                                    onDecrement={() => updateQty(menu.id, (cartMap[menu.id]?.quantity ?? 0) - 1)}
                                />
                            ))}
                        </div>

                    ) : (
                        grouped.map(group => (
                            <div key={group.label} style={{ marginBottom: 24 }}>
                                <div style={{
                                    display: 'flex', justifyContent: 'space-between',
                                    alignItems: 'center', marginBottom: 12,
                                }}>
                                    <span style={{
                                        fontSize: 11, fontWeight: 600, color: C.textMuted,
                                        fontFamily: F, textTransform: 'uppercase', letterSpacing: '0.08em',
                                    }}>
                                        {group.label}
                                    </span>
                                    <span style={{ fontSize: 11, color: C.textMuted, fontFamily: F }}>
                                        {group.menus.length} menu
                                    </span>
                                </div>
                                <div style={{ display: 'grid', gridTemplateColumns: '1fr 1fr', gap: 12 }}>
                                    {group.menus.map(menu => (
                                        <MenuItemCard
                                            key={menu.id} menu={menu}
                                            cartItem={cartMap[menu.id]}
                                            isMahasiswa={!!customer?.isMahasiswa}
                                            onAdd={() => addItem(menu)}
                                            onIncrement={() => updateQty(menu.id, (cartMap[menu.id]?.quantity ?? 0) + 1)}
                                            onDecrement={() => updateQty(menu.id, (cartMap[menu.id]?.quantity ?? 0) - 1)}
                                        />
                                    ))}
                                </div>
                            </div>
                        ))
                    )}
                </div>

            </div>

            {count > 0 && (
                <div style={{
                    position: 'fixed', bottom: 64, left: '50%', transform: 'translateX(-50%)',
                    width: '100%', maxWidth: 430,
                    background: C.textPrimary,
                    padding: '12px 16px',
                    display: 'flex', alignItems: 'center', justifyContent: 'space-between',
                    zIndex: 100,
                }}>
                    <div style={{ display: 'flex', alignItems: 'center', gap: 12 }}>
                        <div style={{ position: 'relative', flexShrink: 0 }}>
                            <ShoppingBag size={22} color={C.surface} />
                            <span style={{
                                position: 'absolute', top: -6, right: -6,
                                background: C.warning, color: C.textPrimary,
                                borderRadius: '50%', width: 18, height: 18,
                                fontSize: 10, fontWeight: 700, fontFamily: F,
                                display: 'flex', alignItems: 'center', justifyContent: 'center',
                            }}>
                                {count}
                            </span>
                        </div>
                        <div style={{ display: 'flex', flexDirection: 'column', gap: 1 }}>
                            <span style={{ fontSize: 11, color: C.textSecond, fontFamily: F }}>
                                {count} item di keranjang
                            </span>
                            <span style={{ fontSize: 15, fontWeight: 700, color: C.surface, fontFamily: F, letterSpacing: '-0.02em' }}>
                                {formatRupiah(total)}
                            </span>
                        </div>
                    </div>
                    <button
                        onClick={() => router.visit('/pelanggan/keranjang')}
                        style={{
                            background: C.surface, color: C.textPrimary,
                            border: 'none', borderRadius: 8,
                            padding: '9px 16px', fontSize: 13, fontWeight: 600,
                            fontFamily: F, cursor: 'pointer', whiteSpace: 'nowrap',
                            letterSpacing: '-0.01em',
                        }}
                    >
                        Lihat Keranjang →
                    </button>
                </div>
            )}

        </PelangganLayout>
    );
}
