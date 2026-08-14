import { useState } from 'react';
import { router, Link } from '@inertiajs/react';
import axios from 'axios';
import { ArrowLeft, MessageSquare } from 'lucide-react';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import {
    Table,
    TableHeader,
    TableBody,
    TableRow,
    TableHead,
    TableCell,
} from '@/components/ui/table';
import CashierLayout from '@/Layouts/CashierLayout';
import StatusBadge from '@/Components/Common/StatusBadge';
import FlashToast from '@/Components/Shared/FlashToast';
import WhatsAppShareModal from '@/Components/Cashier/WhatsAppShareModal';
import { formatRupiah, formatDate, formatTime } from '@/helpers';

const paymentLabel = { cash: 'Tunai (Cash)', qris: 'QRIS' };

export default function OrderShow({ order }) {
    const [processing, setProcessing] = useState(false);
    const [showRejectModal, setShowRejectModal] = useState(false);
    const [rejectNote, setRejectNote] = useState('');
    const [showWhatsAppModal, setShowWhatsAppModal] = useState(false);
    const [waToast, setWaToast] = useState(null);

    const isCashPending = order.status === 'pending' && order.payment_method === 'cash';
    const isQrisPending = order.status === 'pending' && order.payment_method === 'qris' && !!order.payment_proof;
    const canAdvance = order.status === 'diproses';

    async function handleAction(url, body = {}) {
        if (processing) return;
        setProcessing(true);
        try {
            await axios.patch(url, body);
            router.reload();
        } finally {
            setProcessing(false);
        }
    }

    function handleAdvance() { handleAction(route('kasir.pesanan.status', {order: order.id}), { status: 'selesai' }); }
    function handleConfirmCash() { handleAction(route('kasir.pesanan.konfirmasi-tunai', {order: order.id})); }
    function handleConfirmQris() { handleAction(route('kasir.pesanan.konfirmasi-qris', {order: order.id})); setShowRejectModal(false); }
    function handleRejectQris() { handleAction(route('kasir.pesanan.tolak-qris', {order: order.id}), { note: rejectNote }); setShowRejectModal(false); setRejectNote(''); }

    return (
        <CashierLayout title={`Detail Pesanan ${order.order_code}`} fullscreen>
            <div className="flex-1 overflow-y-auto p-8 bg-muted">
                <Card className="shadow-sm">
                    <CardContent className="p-6">

                        {/* ── Header ── */}
                        <div className="flex items-center justify-between mb-7">
                            <div className="flex items-center gap-3.5">
                                <Link
                                    href={route('kasir.pesanan-aktif')}
                                    className="w-9 h-9 rounded-lg bg-card border border-border shrink-0 flex items-center justify-center no-underline shadow-sm text-foreground"
                                >
                                    <ArrowLeft size={18} />
                                </Link>
                                <div className="flex flex-col gap-1">
                                    <h1 className="text-2xl font-bold m-0 tracking-tight text-foreground">
                                        Detail Pesanan {order.order_code}
                                    </h1>
                                    <p className="text-sm m-0 text-muted-foreground">
                                        {formatDate(order.created_at)}, {formatTime(order.created_at)} WIB
                                    </p>
                                </div>
                            </div>

                            <div className="flex items-center gap-2.5">
                                {canAdvance && (
                                    <Button onClick={handleAdvance} disabled={processing}>
                                        {processing ? 'Memproses...' : 'Tandai Selesai'}
                                    </Button>
                                )}
                                <Button
                                    variant="outline"
                                    size="sm"
                                    onClick={() => setShowWhatsAppModal(true)}
                                    className="flex items-center gap-1.5"
                                >
                                    <MessageSquare size={15} />
                                    Bagikan Struk
                                </Button>
                                <StatusBadge status={order.status} />
                            </div>
                        </div>

                        {/* ── Cash pending banner ── */}
                        {isCashPending && (
                            <div className="flex justify-between items-center px-5 py-4 mb-5 rounded-xl bg-amber-50 border border-amber-300">
                                <div className="flex flex-col gap-0.5">
                                    <span className="text-sm font-bold text-amber-700">
                                        Pelanggan Akan Bayar Tunai
                                    </span>
                                    <span className="text-sm text-muted-foreground">
                                        Total: <strong className="text-foreground">{formatRupiah(order.total_amount)}</strong> — Konfirmasi setelah uang diterima
                                    </span>
                                </div>
                                <Button onClick={handleConfirmCash} disabled={processing} className="shrink-0">
                                    ✓ Konfirmasi Pembayaran
                                </Button>
                            </div>
                        )}

                        {/* ── QRIS pending banner ── */}
                        {isQrisPending && (
                            <div className="flex justify-between items-center px-5 py-4 mb-5 rounded-xl bg-blue-50 border border-blue-300">
                                <div className="flex flex-col gap-0.5">
                                    <span className="text-sm font-bold text-blue-700">
                                        Bukti Pembayaran QRIS Diterima
                                    </span>
                                    <span className="text-sm text-muted-foreground">
                                        Verifikasi bukti transfer pelanggan sebelum memproses pesanan
                                    </span>
                                </div>
                                <div className="flex gap-2 shrink-0">
                                    <Button
                                        variant="outline"
                                        onClick={() => setShowRejectModal(true)}
                                        className="text-destructive border-destructive/40 hover:bg-destructive/10"
                                    >
                                        Tolak
                                    </Button>
                                    <Button onClick={handleConfirmQris} disabled={processing}>
                                        ✓ Konfirmasi
                                    </Button>
                                </div>
                            </div>
                        )}

                        {/* ── 2-column grid ── */}
                        <div className="grid grid-cols-1 lg:grid-cols-3 gap-6">
                            {/* Left: Items table */}
                            <div className="lg:col-span-2">
                                <Card className="shadow-md overflow-hidden">
                                    <CardHeader className="bg-muted border-b border-border">
                                        <CardTitle>Daftar Item Pesanan</CardTitle>
                                    </CardHeader>
                                    <Table>
                                        <TableHeader>
                                            <TableRow>
                                                <TableHead>Nama Item</TableHead>
                                                <TableHead>Harga</TableHead>
                                                <TableHead>Jumlah</TableHead>
                                                <TableHead className="text-right">Subtotal</TableHead>
                                            </TableRow>
                                        </TableHeader>
                                        <TableBody>
                                            {order.items.map(item => (
                                                <TableRow key={item.id}>
                                                    <TableCell className="font-medium">{item.name}</TableCell>
                                                    <TableCell className="text-muted-foreground">{formatRupiah(item.unit_price)}</TableCell>
                                                    <TableCell>{item.quantity}</TableCell>
                                                    <TableCell className="text-right font-semibold">{formatRupiah(item.subtotal)}</TableCell>
                                                </TableRow>
                                            ))}
                                        </TableBody>
                                    </Table>
                                    <div className="flex justify-between items-center px-4 py-4 bg-muted border-t border-border">
                                        <span className="text-base font-bold text-foreground">
                                            Total Pembayaran
                                        </span>
                                        <span className="text-xl font-bold text-primary">
                                            {formatRupiah(order.total_amount)}
                                        </span>
                                    </div>
                                </Card>
                            </div>

                            {/* Right: Info card */}
                            <div className="lg:col-span-1">
                                <Card className="shadow-md overflow-hidden">
                                    <CardHeader className="bg-muted border-b border-border">
                                        <CardTitle>Informasi Pesanan</CardTitle>
                                    </CardHeader>
                                    <CardContent className="p-0">
                                        <div className="divide-y divide-border">
                                            <InfoRow label="ID Pesanan" value={order.order_code} bold />
                                            <InfoRow label="Nama Pelanggan" value={order.customer_name} bold />
                                            <InfoRow label="No. Telepon" value={order.customer_phone} />
                                            <InfoRow label="Meja" value={order.table_number ? `No. ${order.table_number}` : '—'} />
                                            <InfoRow label="Tanggal" value={formatDate(order.created_at)} />
                                            <InfoRow label="Waktu" value={`${formatTime(order.created_at)} WIB`} />
                                            <InfoRow label="Metode Pembayaran" value={paymentLabel[order.payment_method] ?? '—'} bold />
                                            <InfoRow label="Kasir" value={order.cashier_name ?? '—'} />
                                            <div className="flex justify-between items-center px-5 py-3">
                                                <span className="text-sm font-medium text-muted-foreground">
                                                    Status
                                                </span>
                                                <StatusBadge status={order.status} />
                                            </div>
                                        </div>
                                    </CardContent>
                                </Card>
                            </div>
                        </div>

                        {/* ── WhatsApp share modal ── */}
                        <WhatsAppShareModal
                            isOpen={showWhatsAppModal}
                            onClose={() => setShowWhatsAppModal(false)}
                            order={{
                                id: order.id,
                                order_code: order.order_code,
                                total_amount: order.total_amount,
                                created_at: order.created_at,
                                items: order.items.map(i => ({
                                    quantity: i.quantity,
                                    name: i.name,
                                })),
                            }}
                            onSkip={() => {
                                setShowWhatsAppModal(false);
                                setWaToast({ type: 'error', message: 'Struk tidak terkirim karena nomor WhatsApp tidak diisi' });
                                setTimeout(() => setWaToast(null), 4000);
                            }}
                        />

                        {/* ── Reject QRIS modal ── */}
                        {showRejectModal && (
                            <div className="fixed inset-0 flex items-center justify-center z-50 bg-black/40">
                                <div className="bg-card rounded-2xl p-7 w-[420px] shadow-xl">
                                    <h3 className="text-lg font-bold mb-2 text-foreground">
                                        Tolak Bukti QRIS
                                    </h3>
                                    <p className="text-sm mb-4 text-muted-foreground">
                                        Isi alasan penolakan (opsional). Pelanggan dapat upload ulang.
                                    </p>
                                    <textarea
                                        value={rejectNote}
                                        onChange={e => setRejectNote(e.target.value)}
                                        placeholder="Contoh: Nominal tidak sesuai / Bukti tidak jelas"
                                        rows={3}
                                        className="w-full border border-border rounded-xl p-3 text-sm resize-vertical outline-none bg-muted text-foreground"
                                    />
                                    <div className="flex gap-3 mt-4">
                                        <Button
                                            variant="outline"
                                            onClick={() => setShowRejectModal(false)}
                                            className="flex-1"
                                        >
                                            Batal
                                        </Button>
                                        <Button
                                            onClick={handleRejectQris}
                                            className="flex-1"
                                            variant="destructive"
                                        >
                                            Konfirmasi Tolak
                                        </Button>
                                    </div>
                                </div>
                            </div>
                        )}
                    </CardContent>
                </Card>
            </div>

            <FlashToast toast={waToast} onDismiss={() => setWaToast(null)} />
        </CashierLayout>
    );
}

function InfoRow({ label, value, bold }) {
    return (
        <div className="flex justify-between items-center px-5 py-3">
            <span className="text-sm font-medium text-muted-foreground">
                {label}
            </span>
            <span className={`text-sm ${bold ? 'font-semibold' : 'font-normal'} text-foreground`}>
                {value}
            </span>
        </div>
    );
}
