import { useState, useEffect } from 'react';
import { usePage } from '@inertiajs/react';
import { ClipboardList, History } from 'lucide-react';
import HeaderBar from '@/Components/Shared/HeaderBar';
import FlashToast from '@/Components/Shared/FlashToast';

const kitchenTabs = [
    { label: 'Pesanan Aktif', href: route('dapur.beranda'), icon: ClipboardList },
    { label: 'Riwayat Pesanan', href: route('dapur.riwayat-pesanan'), icon: History },
];

export default function KitchenLayout({ children }) {
    const { flash, auth } = usePage().props;
    const [toast, setToast] = useState(null);

    // ── Flash toast ──
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

    return (
        <div data-interface="kitchen" className="min-h-screen flex flex-col bg-muted">
            <HeaderBar tabs={kitchenTabs} user={auth?.user} />

            {/* ── Main Content ── */}
            <main className="flex-1 overflow-hidden min-h-0 p-4 lg:p-6">
                <div className="bg-card rounded-xl border border-border shadow-sm h-full flex flex-col min-h-0">
                    {children}
                </div>
            </main>

            {/* ── Toast ── */}
            <FlashToast toast={toast} onDismiss={() => setToast(null)} />
        </div>
    );
}
