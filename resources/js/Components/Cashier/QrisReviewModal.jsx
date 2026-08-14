import { useState } from 'react';
import axios from 'axios';
import { QrCode, Check, X, RotateCcw, ExternalLink, AlertTriangle } from 'lucide-react';
import Modal from '@/Components/Shared/Modal';
import { Button } from '@/components/ui/button';
import { formatRupiah, formatDate, formatTime } from '@/helpers';

export default function QrisReviewModal({ isOpen, onClose, order }) {
    const [action,    setAction]    = useState(null);
    const [reason,    setReason]    = useState('');
    const [loading,   setLoading]   = useState(false);
    const [error,     setError]     = useState('');

    function resetState() {
        setAction(null);
        setReason('');
        setLoading(false);
        setError('');
    }

    function handleClose() {
        resetState();
        onClose();
    }

    function handleReasonChange(e) {
        setReason(e.target.value);
        if (error) setError('');
    }

    async function handleAccept() {
        setLoading(true);
        setError('');
        try {
            await axios.post(route('kasir.pesanan.qris.accept', { order: order.id }));
            handleClose();
            window.location.reload();
        } catch (err) {
            setError(err.response?.data?.message || 'Gagal menerima bukti QRIS.');
        } finally {
            setLoading(false);
        }
    }

    async function handleActionWithReason(selectedAction) {
        if (!reason.trim()) {
            setError('Alasan wajib diisi.');
            return;
        }
        setLoading(true);
        setError('');
        try {
            const endpoint = selectedAction === 'reject'
                ? route('kasir.pesanan.qris.reject', { order: order.id })
                : route('kasir.pesanan.qris.resubmit', { order: order.id });

            await axios.post(endpoint, { reason: reason.trim() });
            handleClose();
            window.location.reload();
        } catch (err) {
            setError(err.response?.data?.message || 'Gagal memproses bukti QRIS.');
        } finally {
            setLoading(false);
        }
    }

    const needsReason = action === 'reject' || action === 'resubmit';

    return (
        <Modal isOpen={isOpen} onClose={handleClose} title="Review Pembayaran QRIS" size="lg">
            <div className="px-6 pb-6 space-y-4">
                <div className="flex items-center justify-between bg-muted/40 rounded-xl px-4 py-3 border border-border">
                    <div className="flex flex-col gap-0.5">
                        <span className="text-xs text-muted-foreground">Pesanan</span>
                        <span className="text-sm font-bold text-foreground">#{order.order_code}</span>
                    </div>
                    <div className="flex items-center gap-1 text-xs text-muted-foreground">
                        <QrCode size={14} className="text-primary" />
                        <span>QRIS</span>
                    </div>
                </div>

                <div>
                    <label className="text-xs font-semibold text-muted-foreground mb-1.5 block tracking-wide">
                        BUKTI TRANSFER
                    </label>
                    <div className="relative bg-muted/30 rounded-xl border border-border overflow-hidden">
                        <img
                            src={order.payment_proof}
                            alt="Bukti QRIS"
                            className="w-full h-52 object-contain cursor-pointer bg-white"
                            onClick={() => window.open(order.payment_proof, '_blank')}
                        />
                        <button
                            onClick={() => window.open(order.payment_proof, '_blank')}
                            className="absolute top-2 right-2 w-7 h-7 rounded-lg bg-black/40 border-none cursor-pointer flex items-center justify-center hover:bg-black/60 transition-colors"
                            type="button"
                            aria-label="Perbesar"
                        >
                            <ExternalLink size={14} className="text-white" />
                        </button>
                    </div>
                    <p className="text-xs text-muted-foreground mt-1">Klik gambar untuk memperbesar</p>
                </div>

                <div className="grid grid-cols-2 gap-3 rounded-xl bg-muted/40 border border-border px-4 py-3">
                    <div>
                        <span className="text-xs text-muted-foreground">Tanggal</span>
                        <p className="text-sm font-medium text-foreground">{formatDate(order.created_at)}</p>
                    </div>
                    <div>
                        <span className="text-xs text-muted-foreground">Waktu</span>
                        <p className="text-sm font-medium text-foreground">{formatTime(order.created_at)}</p>
                    </div>
                    <div className="col-span-2">
                        <span className="text-xs text-muted-foreground">Total Pembayaran</span>
                        <p className="text-base font-bold text-primary">{formatRupiah(order.total_amount)}</p>
                    </div>
                </div>

                {!action && (
                    <div className="flex flex-col gap-2.5">
                        <Button
                            onClick={() => setAction('accept')}
                            disabled={loading}
                            className="w-full h-10 gap-2"
                        >
                            <Check size={16} />
                            Terima Pembayaran
                        </Button>
                        <Button
                            variant="outline"
                            onClick={() => setAction('resubmit')}
                            disabled={loading}
                            className="w-full h-10 gap-2"
                        >
                            <RotateCcw size={16} />
                            Minta Kirim Ulang
                        </Button>
                        <Button
                            variant="destructive"
                            onClick={() => setAction('reject')}
                            disabled={loading}
                            className="w-full h-10 gap-2"
                        >
                            <X size={16} />
                            Tolak
                        </Button>
                    </div>
                )}

                {needsReason && (
                    <div className="space-y-3">
                        <div>
                            <label className="text-sm font-medium text-foreground mb-1.5 block">
                                {action === 'reject' ? 'Alasan Penolakan' : 'Alasan Minta Kirim Ulang'}
                                <span className="text-destructive ml-0.5">*</span>
                            </label>
                            <textarea
                                value={reason}
                                onChange={handleReasonChange}
                                placeholder={
                                    action === 'reject'
                                        ? 'Contoh: Bukti tidak jelas, nominal tidak sesuai...'
                                        : 'Contoh: Foto bukti terpotong, mohon upload ulang...'
                                }
                                rows={3}
                                className="w-full rounded-lg border border-border bg-transparent px-3 py-2 text-sm text-foreground placeholder:text-muted-foreground focus-visible:border-primary focus-visible:ring-1 focus-visible:ring-primary outline-none resize-none"
                                disabled={loading}
                            />
                            {error && (
                                <p className="mt-1.5 text-xs text-destructive flex items-center gap-1">
                                    <AlertTriangle size={12} />
                                    {error}
                                </p>
                            )}
                        </div>

                        <div className="flex gap-2.5">
                            <Button
                                variant="outline"
                                onClick={() => { setAction(null); setReason(''); setError(''); }}
                                disabled={loading}
                                className="flex-1 h-10"
                            >
                                Batal
                            </Button>
                            <Button
                                onClick={() => handleActionWithReason(action)}
                                disabled={loading || !reason.trim()}
                                className="flex-[2] h-10 gap-2"
                            >
                                {loading ? 'Memproses...' : (
                                    action === 'reject' ? 'Tolak Bukti' : 'Minta Kirim Ulang'
                                )}
                            </Button>
                        </div>
                    </div>
                )}

                {action === 'accept' && !loading && (
                    <div className="bg-green-50 border border-green-200 rounded-xl px-4 py-3 flex items-start gap-2.5">
                        <Check size={16} className="text-green-600 shrink-0 mt-0.5" />
                        <div>
                            <p className="text-sm font-semibold text-green-800">Konfirmasi Penerimaan</p>
                            <p className="text-xs text-green-700 mt-0.5">
                                Pesanan akan dilanjutkan ke status Diproses setelah dikonfirmasi.
                            </p>
                        </div>
                    </div>
                )}
            </div>
        </Modal>
    );
}
