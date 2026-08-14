import { useState, useEffect } from 'react';
import { Link, usePage, router } from '@inertiajs/react';
import {
    LayoutDashboard,
    ShoppingCart,
    ClipboardList,
    History,
    User,
    LogOut,
    CheckCircle,
    XCircle,
    PanelLeftClose,
    PanelLeftOpen,
} from 'lucide-react';

const navItems = [
    { label: 'Dashboard',       href: '/kasir/dashboard',     icon: LayoutDashboard },
    { label: 'Pesanan Baru',    href: '/kasir/pesanan-baru',  icon: ShoppingCart },
    { label: 'Pesanan Aktif',   href: '/kasir/pesanan-aktif', icon: ClipboardList },
    { label: 'Riwayat Pesanan', href: '/kasir/riwayat-pesanan', icon: History },
    { label: 'Profil',          href: '/kasir/profil',        icon: User },
];

export default function CashierLayout({ children, title = 'Dashboard', fullscreen = false }) {
    const { flash, pendingOrderCount: initialCount } = usePage().props;
    const [pendingCount, setPendingCount] = useState(initialCount ?? 0);
    const [toast, setToast] = useState(null);
    const [isSidebarCollapsed, setIsSidebarCollapsed] = useState(() => {
        if (typeof window === 'undefined') return false;
        return window.localStorage.getItem('cashier-sidebar-collapsed') === 'true';
    });
    const sidebarWidth = isSidebarCollapsed ? 84 : 260;

    useEffect(() => {
        if (flash?.success) {
            setToast({ type: 'success', message: flash.success });
            const t = setTimeout(() => setToast(null), 3000);
            return () => clearTimeout(t);
        }
        if (flash?.error) {
            setToast({ type: 'error', message: flash.error });
            const t = setTimeout(() => setToast(null), 3000);
            return () => clearTimeout(t);
        }
    }, [flash]);

    useEffect(() => {
        setPendingCount(initialCount ?? 0);
    }, [initialCount]);

    useEffect(() => {
        // WebSocket via Laravel Reverb — zero polling, push-based update
        if (!window.Echo) return;

        const channel = window.Echo.channel('orders');

        channel.listen('.OrderStatusUpdated', (e) => {
            setPendingCount(e.pendingCount);
        });

        return () => {
            window.Echo.leaveChannel('orders');
        };
    }, []);

    // Ambil pending count fresh setiap halaman dibuka — hindari angka stale
    // dari cache prefetch Inertia saat berpindah menu
    useEffect(() => {
        let cancelled = false;
        window.axios?.get('/kasir/pesanan-menunggu')
            .then(res => { if (!cancelled) setPendingCount(res.data.count); })
            .catch(() => {});
        return () => { cancelled = true; };
    }, []);

    useEffect(() => {
        if (typeof window === 'undefined') return;
        window.localStorage.setItem('cashier-sidebar-collapsed', String(isSidebarCollapsed));

        window.dispatchEvent(new CustomEvent('cashier-sidebar-toggle', {
            detail: {
                collapsed: isSidebarCollapsed,
                width: sidebarWidth,
            },
        }));
    }, [isSidebarCollapsed]);

    return (
        <div
            style={{
                display: 'flex',
                minHeight: '100vh',
                fontFamily: "'Inter', system-ui, sans-serif",
                '--cashier-sidebar-width': `${sidebarWidth}px`,
            }}
        >

            {/* ── SIDEBAR ── */}
            <aside style={{
                width: sidebarWidth,
                minHeight: '100vh',
                background: '#0F172A',
                display: 'flex',
                flexDirection: 'column',
                flexShrink: 0,
                transition: 'width 0.2s ease',
            }}>

                {/* Brand / Logo */}
                <div style={{
                    padding: '24px 20px',
                    borderBottom: '1px solid rgba(255,255,255,0.06)',
                    display: 'flex',
                    alignItems: 'center',
                    gap: 12,
                }}>
                    {isSidebarCollapsed ? (
                        <button
                            type="button"
                            onClick={() => setIsSidebarCollapsed(prev => !prev)}
                            title="Expand sidebar"
                            aria-label="Expand sidebar"
                            style={{
                                width: 40,
                                height: 40,
                                borderRadius: 10,
                                border: '1px solid rgba(255,255,255,0.14)',
                                background: '#1E293B',
                                color: '#E2E8F0',
                                display: 'inline-flex',
                                alignItems: 'center',
                                justifyContent: 'center',
                                cursor: 'pointer',
                                flexShrink: 0,
                                boxShadow: '0 2px 10px rgba(0,0,0,0.20)',
                            }}
                        >
                            <PanelLeftOpen size={18} />
                        </button>
                    ) : (
                        <img
                            src="/images/logo.jpg"
                            alt="W9 Cafe"
                            width={40}
                            height={40}
                            style={{
                                width: 40,
                                height: 40,
                                borderRadius: 10,
                                objectFit: 'cover',
                                flexShrink: 0,
                                boxShadow: '0 2px 10px rgba(0,0,0,0.20)',
                            }}
                        />
                    )}
                    {!isSidebarCollapsed && (
                        <span style={{ color: 'white', fontWeight: 700, fontSize: 16, whiteSpace: 'nowrap' }}>W9 Cafe</span>
                    )}
                    {!isSidebarCollapsed && (
                        <button
                            type="button"
                            onClick={() => setIsSidebarCollapsed(prev => !prev)}
                            title="Collapse sidebar"
                            aria-label="Collapse sidebar"
                            style={{
                                marginLeft: 'auto',
                                width: 32,
                                height: 32,
                                borderRadius: 8,
                                border: '1px solid rgba(255,255,255,0.14)',
                                background: '#1E293B',
                                color: '#E2E8F0',
                                display: 'inline-flex',
                                alignItems: 'center',
                                justifyContent: 'center',
                                cursor: 'pointer',
                                flexShrink: 0,
                            }}
                        >
                            <PanelLeftClose size={16} />
                        </button>
                    )}
                </div>

                {/* Nav */}
                <nav style={{ flex: 1, padding: '20px 20px 0', display: 'flex', flexDirection: 'column', gap: 4, overflowY: 'auto' }}>
                    {navItems.map(({ label, href, icon: Icon }) => {
                        const active = window.location.pathname === href;
                        const showBadge = label === 'Pesanan Aktif' && pendingCount > 0;
                        return (
                            <Link
                                key={href}
                                href={href}
                                prefetch
                                cacheFor="1m"
                                style={{
                                    display: 'flex',
                                    alignItems: 'center',
                                    gap: isSidebarCollapsed ? 0 : 12,
                                    height: 44,
                                    padding: isSidebarCollapsed ? '0 12px' : '0 16px',
                                    borderRadius: 8,
                                    textDecoration: 'none',
                                    fontSize: 14,
                                    fontWeight: active ? 600 : 500,
                                    color: active ? '#FFFFFF' : '#94A3B8',
                                    background: active ? '#3B6FD4' : 'transparent',
                                    transition: 'background 0.15s, color 0.15s',
                                    justifyContent: isSidebarCollapsed ? 'center' : 'flex-start',
                                }}
                                onMouseEnter={e => { if (!active) e.currentTarget.style.background = '#1E293B'; }}
                                onMouseLeave={e => { if (!active) e.currentTarget.style.background = 'transparent'; }}
                            >
                                <span style={{ position: 'relative', display: 'inline-flex' }}>
                                    <Icon size={20} />
                                    {isSidebarCollapsed && showBadge && (
                                        <span style={{
                                            background: '#EF4444',
                                            color: '#FFFFFF',
                                            borderRadius: '50%',
                                            minWidth: 16,
                                            height: 16,
                                            position: 'absolute',
                                            top: -8,
                                            right: -10,
                                            display: 'flex',
                                            alignItems: 'center',
                                            justifyContent: 'center',
                                            fontSize: 9,
                                            fontWeight: 700,
                                            lineHeight: 1,
                                            padding: pendingCount > 9 ? '0 4px' : 0,
                                        }}>
                                            {pendingCount > 99 ? '99+' : pendingCount}
                                        </span>
                                    )}
                                </span>
                                {!isSidebarCollapsed && <span style={{ flex: 1 }}>{label}</span>}
                                {showBadge && (
                                    !isSidebarCollapsed && (
                                        <span style={{
                                            background: '#EF4444',
                                            color: '#FFFFFF',
                                            borderRadius: pendingCount > 9 ? 10 : '50%',
                                            minWidth: 20,
                                            height: 20,
                                            display: 'flex',
                                            alignItems: 'center',
                                            justifyContent: 'center',
                                            fontSize: 11,
                                            fontWeight: 700,
                                            lineHeight: 1,
                                            padding: pendingCount > 9 ? '0 5px' : 0,
                                            flexShrink: 0,
                                        }}>
                                            {pendingCount > 99 ? '99+' : pendingCount}
                                        </span>
                                    )
                                )}
                            </Link>
                        );
                    })}
                </nav>

                {/* Logout */}
                <div style={{ padding: '12px 20px 24px', borderTop: '1px solid rgba(255,255,255,0.06)' }}>
                    <button
                        onClick={() => router.post('/logout')}
                        style={{
                            display: 'flex',
                            alignItems: 'center',
                            gap: isSidebarCollapsed ? 0 : 12,
                            height: 44,
                            padding: isSidebarCollapsed ? '0 12px' : '0 16px',
                            borderRadius: 8,
                            width: '100%',
                            background: 'transparent',
                            border: 'none',
                            color: '#DC2626',
                            fontSize: 14,
                            fontWeight: 500,
                            cursor: 'pointer',
                            transition: 'background 0.15s',
                            justifyContent: isSidebarCollapsed ? 'center' : 'flex-start',
                        }}
                        onMouseEnter={e => e.currentTarget.style.background = 'rgba(220,38,38,0.08)'}
                        onMouseLeave={e => e.currentTarget.style.background = 'transparent'}
                    >
                        <LogOut size={20} />
                        {!isSidebarCollapsed && 'Keluar'}
                    </button>
                </div>
            </aside>

            {/* ── MAIN CONTENT ── */}
            {fullscreen ? (
                <main style={{ flex: 1, display: 'flex', flexDirection: 'column', overflow: 'hidden', height: '100vh' }}>
                    {children}
                </main>
            ) : (
                <main style={{ flex: 1, background: '#F8FAFC', padding: 32, minHeight: '100vh' }}>
                    <div style={{
                        background: 'white',
                        borderRadius: 12,
                        padding: 24,
                        minHeight: 'calc(100vh - 64px)',
                        border: '1px solid #E2E8F0',
                        boxShadow: '0 2px 8px rgba(15,23,42,0.03)',
                    }}>
                        {children}
                    </div>
                </main>
            )}

            {/* ── TOAST ── */}
            {toast && (
                <div style={{
                    position: 'fixed',
                    top: 24,
                    right: 24,
                    zIndex: 9999,
                    background: toast.type === 'success' ? '#F0FDF4' : '#FEF2F2',
                    border: `1px solid ${toast.type === 'success' ? '#86EFAC' : '#FCA5A5'}`,
                    borderRadius: 10,
                    padding: '12px 16px',
                    display: 'flex',
                    alignItems: 'center',
                    gap: 10,
                    fontSize: 14,
                    color: toast.type === 'success' ? '#15803D' : '#DC2626',
                    boxShadow: '0 4px 16px rgba(0,0,0,0.10)',
                    minWidth: 280,
                    maxWidth: 380,
                }}>
                    {toast.type === 'success'
                        ? <CheckCircle size={18} style={{ flexShrink: 0 }} />
                        : <XCircle size={18} style={{ flexShrink: 0 }} />}
                    {toast.message}
                </div>
            )}
        </div>
    );
}
