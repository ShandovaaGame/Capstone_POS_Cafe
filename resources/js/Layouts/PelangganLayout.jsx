import { useEffect, useState } from 'react';
import { usePage } from '@inertiajs/react';
import BottomNav from '@/Components/Pelanggan/BottomNav';

export default function PelangganLayout({ children, activeTab = 'menu', showBottomNav = true }) {
    const { flash } = usePage().props;
    const [info, setInfo] = useState(null);

    /* ── Flash info toast ── */
    useEffect(() => {
        if (flash?.info) {
            setInfo(flash.info);
            const t = setTimeout(() => setInfo(null), 5000);
            return () => clearTimeout(t);
        }
    }, [flash]);

    /* ── Load Midtrans Snap (once) ── */
    useEffect(() => {
        if (typeof window === 'undefined' || window.snap) return;
        const snapUrl = import.meta.env.VITE_MIDTRANS_SNAP_URL;
        const clientKey = import.meta.env.VITE_MIDTRANS_CLIENT_KEY;
        if (!snapUrl || !clientKey) return;
        const s = document.createElement('script');
        s.src = snapUrl;
        s.setAttribute('data-client-key', clientKey);
        document.head.appendChild(s);
    }, []);

    return (
        <div style={{
            maxWidth: 430,
            margin: '0 auto',
            minHeight: '100vh',
            background: 'transparent',
            position: 'relative',
            paddingBottom: showBottomNav ? 80 : 0,
        }}>
            {children}
            {showBottomNav && <BottomNav activeTab={activeTab} />}

            {/* ── Info toast (verifikasi pending) ── */}
            {info && (
                <div style={{
                    position: 'fixed',
                    bottom: 80,
                    left: '50%',
                    transform: 'translateX(-50%)',
                    width: 'calc(100% - 32px)',
                    maxWidth: 398,
                    background: '#FEF3EC',
                    border: '1px solid #F0C4A0',
                    borderRadius: 14,
                    padding: '12px 16px',
                    fontSize: 13,
                    color: '#C05A1A',
                    fontFamily: 'Outfit, system-ui, sans-serif',
                    boxShadow: '0 4px 16px rgba(45,32,22,0.12)',
                    zIndex: 9999,
                    textAlign: 'center',
                    lineHeight: 1.5,
                }}>
                    ⏳ {info}
                </div>
            )}
        </div>
    );
}
