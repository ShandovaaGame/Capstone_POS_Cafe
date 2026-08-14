import { useState, useEffect, useRef } from 'react';
import { router, Head } from '@inertiajs/react';
import axios from 'axios';
import { X, QrCode } from 'lucide-react';
import { Button } from '@/components/ui/button';
import { Card, CardContent } from '@/components/ui/card';
import { Tabs, TabsList, TabsTrigger } from '@/components/ui/tabs';
import CashierLayout from '@/Layouts/CashierLayout';
import OrderCard from '@/Components/Kasir/OrderCard';
import StatusBadge from '@/Components/Common/StatusBadge';
import { formatRupiah, formatDate, formatTime } from '@/helpers';

export default function PesananAktif({ orders: initialOrders, counts }) {
    const [activeTab,    setActiveTab]    = useState('all');
    const [qrisOrder,    setQrisOrder]    = useState(null);
    const [rejectNote,   setRejectNote]   = useState('');
    const [processing,   setProcessing]   = useState(false);
    const [localOrders,  setLocalOrders]  = useState(initialOrders ?? []);
    const pendingRemoveRef = useRef(new Set());
    const pendingStatusRef = useRef(new Map());

    useEffect(() => {
        setLocalOrders(
            (initialOrders ?? [])
                .filter(o => !pendingRemoveRef.current.has(o.id))
                .map(o => pendingStatusRef.current.has(o.id)
                    ? { ...o, status: pendingStatusRef.current.get(o.id) }
                    : o
                )
        );
    }, [initialOrders]);

    useEffect(() => {
        const reload = () => {
            if (document.visibilityState === 'hidden') return;
            router.reload({ only: ['orders', 'counts'] });
        };

        if (window.Echo) {
            window.Echo.channel('orders').listen('.OrderStatusUpdated', reload);
        }

        const id = setInterval(reload, 5_000);
        const onVisible = () => { if (document.visibilityState === 'visible') reload(); };
        document.addEventListener('visibilitychange', onVisible);

        return () => {
            if (window.Echo) window.Echo.leaveChannel('orders');
            clearInterval(id);
            document.removeEventListener('visibilitychange', onVisible);
        };
    }, []);

    const tabs = [
        { key: 'all',         label: `Semua (${counts.all})` },
        { key: 'pending',     label: `Pending (${counts.pending})` },
        { key: 'diproses',    label: `Diproses (${counts.diproses})` },
        { key: 'belum_bayar', label: `Belum Bayar (${counts.belum_bayar ?? 0})` },
    ];

    const filteredOrders = (() => {
        switch (activeTab) {
            case 'pending':     return localOrders.filter(o => o.status === 'pending');
            case 'diproses':    return localOrders.filter(o => o.status === 'diproses');
            case 'belum_bayar': return localOrders.filter(o => o.payment_method === 'bayar_nanti');
            default:            return localOrders;
        }
    })();

    async function handleConfirmQris() {
        if (processing || !qrisOrder) return;
        const orderId = qrisOrder.id;
        setProcessing(true);

        pendingStatusRef.current.set(orderId, 'diproses');
        setLocalOrders(prev => prev.map(o =>
            o.id === orderId ? { ...o, status: 'diproses', payment_proof: null } : o
        ));
        setQrisOrder(null);

        try {
            await axios.patch(route('kasir.pesanan.konfirmasi-qris', {order: orderId}));
            router.reload({
                only: ['orders', 'counts'],
                onFinish: () => pendingStatusRef.current.delete(orderId),
            });
        } catch (_) {
            pendingStatusRef.current.delete(orderId);
            router.reload({ only: ['orders', 'counts'] });
        } finally {
            setProcessing(false);
        }
    }

    async function handleRejectQris() {
        if (processing || !qrisOrder) return;
        setProcessing(true);
        try {
            await axios.patch(route('kasir.pesanan.tolak-qris', {order: qrisOrder.id}), { note: rejectNote });
            setQrisOrder(null);
            setRejectNote('');
            router.reload({ only: ['orders', 'counts'] });
        } finally {
            setProcessing(false);
        }
    }

    async function handleMarkDone(orderId, targetStatus) {
        if (processing) return;
        setProcessing(true);

        if (targetStatus === 'selesai') {
            pendingRemoveRef.current.add(orderId);
            setLocalOrders(prev => prev.filter(o => o.id !== orderId));
        } else {
            pendingStatusRef.current.set(orderId, targetStatus);
            setLocalOrders(prev => prev.map(o => o.id === orderId ? { ...o, status: targetStatus } : o));
        }

        try {
            await axios.patch(route('kasir.pesanan.status', {order: orderId}), { status: targetStatus });
            router.reload({
                only: ['orders', 'counts'],
                onFinish: () => {
                    pendingRemoveRef.current.delete(orderId);
                    pendingStatusRef.current.delete(orderId);
                },
            });
        } catch (_) {
            pendingRemoveRef.current.delete(orderId);
            pendingStatusRef.current.delete(orderId);
            router.reload({ only: ['orders', 'counts'] });
        } finally {
            setProcessing(false);
        }
    }

    async function handleConfirmPayment(orderId, paymentMethod) {
        if (processing) return;
        setProcessing(true);
        try {
            await axios.patch(route('kasir.pesanan.konfirmasi-bayar', {order: orderId}), { payment_method: paymentMethod });
            router.reload({ only: ['orders', 'counts'] });
        } finally {
            setProcessing(false);
        }
    }

    async function handleCancel(order) {
        if (!confirm('Batalkan pesanan ' + order.order_code + '?')) return;
        setProcessing(true);
        try {
            await axios.patch(route('kasir.pesanan.cancel', {order: order.id}));
            router.reload({ only: ['orders', 'counts'] });
        } finally {
            setProcessing(false);
        }
    }

    return (
        <><Head title="Pesanan Aktif | W9 Cafe" /><CashierLayout title="Pesanan Aktif" fullscreen>
            <div className="flex-1 overflow-y-auto overflow-x-hidden p-8 bg-muted min-w-0">
            <Card className="shadow-sm">
            <CardContent className="p-6">

            <div className="mb-5">
                <h1 className="text-3xl font-bold text-foreground m-0 mb-1 tracking-tight">
                    Pesanan Aktif
                </h1>
                <p className="text-sm text-muted-foreground m-0">
                    Kelola semua pesanan yang sedang diproses
                </p>
            </div>

            {/* ── Shadcn Tabs — pill variant ── */}
            <Tabs value={activeTab} onValueChange={setActiveTab} className="mb-6">
                <TabsList className="h-auto rounded-full p-1 bg-muted">
                    {tabs.map(tab => (
                        <TabsTrigger
                            key={tab.key}
                            value={tab.key}
                            className="rounded-full px-4 py-1.5 text-sm font-semibold
                                       data-active:bg-background data-active:text-foreground
                                       data-active:shadow-sm"
                        >
                            {tab.label}
                        </TabsTrigger>
                    ))}
                </TabsList>
            </Tabs>

            {filteredOrders.length === 0 ? (
                <div className="text-center pt-16 text-sm text-muted-foreground">
                    Tidak ada pesanan aktif
                </div>
            ) : (
                <div className="grid grid-cols-1 md:grid-cols-2 xl:grid-cols-3 gap-4 items-start">
                    {filteredOrders.map(order => (
                        <OrderCard
                            key={order.id}
                            order={order}
                            onDetail={id => router.visit(route('kasir.pesanan.detail', {order: id}))}
                            onOpenQrisModal={o => { setQrisOrder(o); setRejectNote(''); }}
                            onMarkDone={handleMarkDone}
                            onConfirmPayment={handleConfirmPayment}
                            onCancel={handleCancel}
                        />
                    ))}
                </div>
            )}

            {/* ── QRIS Modal ── */}
            {qrisOrder && (
                <div className="fixed inset-0 z-50 flex items-center justify-center p-4 bg-black/40">
                    <div className="bg-card rounded-2xl w-full max-w-2xl flex flex-col overflow-hidden shadow-modal max-h-[calc(100vh-32px)]">
                        <div className="flex justify-between items-center px-5 py-3.5 border-b border-border shrink-0">
                            <div className="flex flex-col gap-0.5">
                                <span className="text-base font-bold text-foreground">
                                    Konfirmasi Pembayaran QRIS
                                </span>
                                <span className="text-xs text-muted-foreground">
                                    #{qrisOrder.order_code}{qrisOrder.table_number ? ` · Meja ${qrisOrder.table_number}` : ''}
                                </span>
                            </div>
                            <button
                                onClick={() => setQrisOrder(null)}
                                aria-label="Tutup modal"
                                className="w-8 h-8 rounded-lg shrink-0 flex items-center justify-center border-none cursor-pointer bg-muted text-muted-foreground"
                            >
                                <X size={16} />
                            </button>
                        </div>

                        <div className="px-5 py-3.5 flex gap-3.5 overflow-y-auto flex-1">
                            <div className="flex-[0_0_200px] flex flex-col gap-2">
                                <span className="text-xs font-semibold tracking-wide text-muted-foreground">
                                    BUKTI TRANSFER
                                </span>
                                <div className="flex flex-col gap-1 rounded-xl p-1.5 bg-muted border border-border">
                                    <img
                                        src={qrisOrder.payment_proof}
                                        alt="Bukti QRIS"
                                        className="w-full h-[150px] object-contain rounded-md cursor-pointer block"
                                        onClick={() => window.open(qrisOrder.payment_proof, '_blank')}
                                    />
                                    <span className="text-xs text-center text-muted-foreground">
                                        Klik untuk perbesar
                                    </span>
                                </div>
                            </div>

                            <div className="flex-1 flex flex-col gap-2.5 min-w-0">
                                <div className="flex flex-col gap-1.5 rounded-xl px-3.5 py-2.5 bg-muted/50 border border-border">
                                    <div className="flex justify-between items-center">
                                        <span className="text-xs text-muted-foreground">Metode</span>
                                        <span className="flex items-center gap-1 text-xs font-semibold text-foreground">
                                            <QrCode size={12} className="text-primary" />
                                            QRIS
                                        </span>
                                    </div>
                                    <div className="flex justify-between items-center">
                                        <span className="text-xs text-muted-foreground">Waktu Bayar</span>
                                        <span className="text-xs font-medium text-foreground">
                                            {formatTime(qrisOrder.created_at)} · {formatDate(qrisOrder.created_at)}
                                        </span>
                                    </div>
                                    <div className="flex justify-between items-center">
                                        <span className="text-xs text-muted-foreground">Status</span>
                                        <StatusBadge status={qrisOrder.status} />
                                    </div>
                                    <div className="h-px bg-border" />
                                    <div className="flex justify-between items-center">
                                        <span className="text-sm font-bold text-foreground">Total</span>
                                        <span className="text-base font-bold text-primary">
                                            {formatRupiah(qrisOrder.total_amount)}
                                        </span>
                                    </div>
                                </div>

                                <div className="flex flex-col gap-1.5 rounded-xl px-3.5 py-2.5 bg-muted/50 border border-border">
                                    <span className="text-xs font-semibold tracking-wide text-muted-foreground">
                                        DETAIL PESANAN
                                    </span>
                                    {qrisOrder.items?.map((item, i) => (
                                        <div key={i} className="flex justify-between items-center">
                                            <span className="flex items-center gap-1">
                                                <span className="text-xs font-semibold text-primary">
                                                    {item.quantity}x
                                                </span>
                                                <span className="text-xs font-medium text-foreground">
                                                    {item.name}
                                                </span>
                                            </span>
                                            <span className="text-xs text-muted-foreground">
                                                {formatRupiah(item.subtotal)}
                                            </span>
                                        </div>
                                    ))}
                                </div>
                            </div>
                        </div>

                        <div className="flex gap-2.5 px-5 py-3 border-t border-border shrink-0">
                            <Button
                                variant="destructive"
                                onClick={handleRejectQris}
                                disabled={processing}
                                className="flex-1"
                            >
                                Tolak
                            </Button>
                            <Button
                                onClick={handleConfirmQris}
                                disabled={processing}
                                className="flex-[2]"
                            >
                                Konfirmasi Pembayaran
                            </Button>
                        </div>
                    </div>
                </div>
            )}
            </CardContent>
            </Card>
            </div>
        </CashierLayout></>
    );
}
