import { useEffect, useState } from 'react';
import { usePage } from '@inertiajs/react';
import BottomNav from '@/Components/Pelanggan/BottomNav';

export default function CustomerLayout({ children, activeTab = 'menu', showBottomNav = true }) {
    const { flash } = usePage().props;
    const [info, setInfo] = useState(null);
    const [error, setError] = useState(null);

    useEffect(() => {
        if (flash?.info) {
            setInfo(flash.info);
            const t = setTimeout(() => setInfo(null), 5000);
            return () => clearTimeout(t);
        }
        if (flash?.error) {
            setError(flash.error);
            const t = setTimeout(() => setError(null), 5000);
            return () => clearTimeout(t);
        }
    }, [flash]);

    return (
        <div
            data-interface="customer"
            className="min-h-screen bg-background text-foreground antialiased"
        >
            <header className="sticky top-0 z-40 flex items-center justify-between px-4 py-2 bg-background/80 backdrop-blur-sm border-b border-border">
                <div className="text-sm font-semibold text-primary tracking-wide">
                    W9 Cafe
                </div>
            </header>

            {showBottomNav && <BottomNav activeTab={activeTab} />}

            <main className="pb-16 sm:pb-0">
                {children}
            </main>

            {info && (
                <div className="fixed bottom-20 sm:bottom-6 left-1/2 -translate-x-1/2 z-[9999] w-[calc(100%-2rem)] max-w-md rounded-xl border border-primary/20 bg-accent px-4 py-3 text-sm text-accent-foreground text-center leading-relaxed shadow-floating">
                    <span className="mr-1.5 opacity-70">⏳</span>
                    {info}
                </div>
            )}

            {error && (
                <div className="fixed bottom-20 sm:bottom-6 left-1/2 -translate-x-1/2 z-[9999] w-[calc(100%-2rem)] max-w-md rounded-xl border border-red-300 bg-red-50 px-4 py-3 text-sm text-red-700 text-center leading-relaxed shadow-floating">
                    <span className="mr-1.5">&#x2716;</span>
                    {error}
                </div>
            )}
        </div>
    );
}
