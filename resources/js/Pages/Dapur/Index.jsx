import { useState, useEffect, useCallback } from 'react';
import { router, Head } from '@inertiajs/react';
import axios from 'axios';
import KitchenLayout from '@/Layouts/KitchenLayout';
import KanbanColumn from '@/Components/Kitchen/KanbanColumn';
import OrderKanbanCard from '@/Components/Kitchen/OrderKanbanCard';

export default function KitchenIndex({ orders: initialOrders }) {
    const [orders, setOrders] = useState(initialOrders ?? []);
    const [bumpingIds, setBumpingIds] = useState(new Set());

    // ── Polling: reload orders every 5s, pause when tab is hidden ──
    useEffect(() => {
        const reload = () => {
            if (document.visibilityState === 'hidden') return;
            router.reload({
                only: ['orders'],
                preserveState: true,
                preserveScroll: true,
                onSuccess: (page) => {
                    setOrders(page.props.orders ?? []);
                },
            });
        };

        const id = setInterval(reload, 5000);
        const onVisible = () => {
            if (document.visibilityState === 'visible') reload();
        };
        document.addEventListener('visibilitychange', onVisible);

        return () => {
            clearInterval(id);
            document.removeEventListener('visibilitychange', onVisible);
        };
    }, []);

    // ── Bump: optimistic update with error recovery ──
    const handleBump = useCallback(async (order) => {
        if (bumpingIds.has(order.id)) return;
        if (order.status === 'selesai') return;

        setBumpingIds(prev => new Set(prev).add(order.id));

        try {
            await axios.patch(route('dapur.proses', {order: order.id}));
            const nextStatus = order.status === 'pending' ? 'diproses' : 'selesai';
            setOrders(prev =>
                nextStatus === 'selesai'
                    ? prev.filter(o => o.id !== order.id)
                    : prev.map(o => o.id === order.id ? { ...o, status: nextStatus } : o)
            );
        } catch {
            router.reload({ only: ['orders'], preserveState: true, preserveScroll: true });
        } finally {
            setBumpingIds(prev => {
                const next = new Set(prev);
                next.delete(order.id);
                return next;
            });
        }
    }, [bumpingIds]);

    const pendingOrders = orders.filter(o => o.status === 'pending');
    const processingOrders = orders.filter(o => o.status === 'diproses');

    const halfPending = Math.ceil(pendingOrders.length / 2);
    const halfProcessing = Math.ceil(processingOrders.length / 2);

    const pendingCol1 = pendingOrders.slice(0, halfPending);
    const pendingCol2 = pendingOrders.slice(halfPending);
    const processingCol1 = processingOrders.slice(0, halfProcessing);
    const processingCol2 = processingOrders.slice(halfProcessing);

    const renderCards = (items) => items.map(order => (
        <OrderKanbanCard
            key={order.id}
            order={order}
            onBump={handleBump}
            isBumping={bumpingIds.has(order.id)}
        />
    ));

    return (
        <>
            <Head title="Dapur | W9 Cafe" />
            <KitchenLayout>
                <div className="flex flex-1 min-h-0">
                    <div className="flex flex-col flex-1 min-w-0">
                        <div className="flex items-center justify-center gap-2 mt-2 pt-2 mb-2 pb-2 mx-3 border-b-2 border-b-primary">
                            <span className="text-base font-bold text-foreground">Menunggu</span>
                            <span className="rounded-full px-2.5 py-0.5 text-xs font-bold bg-primary text-primary-foreground">
                                {pendingOrders.length}
                            </span>
                        </div>

                        <div className="flex flex-1 min-h-0 gap-3 px-3">
                            <KanbanColumn hideHeader>
                                {renderCards(pendingCol1)}
                            </KanbanColumn>
                            <KanbanColumn hideHeader>
                                {renderCards(pendingCol2)}
                            </KanbanColumn>
                        </div>
                    </div>

                    <div className="border-l border-border" />

                    <div className="flex flex-col flex-1 min-w-0">
                        <div className="flex items-center justify-center gap-2 mt-2 pt-2 mb-2 pb-2 mx-3 border-b-2 border-b-amber-500">
                            <span className="text-base font-bold text-foreground">Diproses</span>
                            <span className="rounded-full px-2.5 py-0.5 text-xs font-bold bg-amber-500 text-white">
                                {processingOrders.length}
                            </span>
                        </div>

                        <div className="flex flex-1 min-h-0 gap-3 px-3">
                            <KanbanColumn hideHeader>
                                {renderCards(processingCol1)}
                            </KanbanColumn>
                            <KanbanColumn hideHeader>
                                {renderCards(processingCol2)}
                            </KanbanColumn>
                        </div>
                    </div>
                </div>
            </KitchenLayout>
        </>
    );
}
