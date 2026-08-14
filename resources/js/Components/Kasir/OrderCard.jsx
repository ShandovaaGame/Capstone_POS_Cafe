import { useState, useRef, useEffect } from 'react';
import { MoreVertical, Banknote, QrCode, Check, Clock, XCircle } from 'lucide-react';
import { Button } from '@/components/ui/button';
import { Card, CardContent } from '@/components/ui/card';
import { cn } from '@/lib/utils';

const METHOD_META = {
    cash:        { label: 'Tunai',       bg: '#FEF9EC', color: '#B45309', Icon: Banknote },
    qris:        { label: 'QRIS',        bg: '#EEF2FF', color: '#3B6FD4', Icon: QrCode   },
    bayar_nanti: { label: 'Bayar Nanti', bg: '#EEF2FF', color: '#3B6FD4', Icon: Clock    },
};

import StatusBadge from '@/Components/Common/StatusBadge';
import { formatRupiah, formatDate, formatTime } from '@/helpers';
import QrisReviewModal from '@/Components/Cashier/QrisReviewModal';


const STATUS_META = {
    pending:  { dot: '#D08068', label: 'Menunggu'  },
    diproses: { dot: '#D4A64A', label: 'Diproses' },
    selesai:  { dot: '#4D9B6A', label: 'Selesai'  },
};

export default function OrderCard({ order, onDetail, onOpenQrisModal, onMarkDone, onConfirmPayment, onCancel }) {
    const [menuOpen,      setMenuOpen]      = useState(false);
    const [payPopover,    setPayPopover]    = useState(false);
    const [showQrisReview, setShowQrisReview] = useState(false);
    const menuRef    = useRef(null);
    const payRef     = useRef(null);

    useEffect(() => {
        if (!menuOpen) return;
        function handler(e) {
            if (menuRef.current && !menuRef.current.contains(e.target)) setMenuOpen(false);
        }
        document.addEventListener('mousedown', handler);
        return () => document.removeEventListener('mousedown', handler);
    }, [menuOpen]);

    useEffect(() => {
        if (!payPopover) return;
        function handler(e) {
            if (payRef.current && !payRef.current.contains(e.target)) setPayPopover(false);
        }
        document.addEventListener('mousedown', handler);
        return () => document.removeEventListener('mousedown', handler);
    }, [payPopover]);

    const isQrisPending  = order.status === 'pending' && order.payment_method === 'qris';
    const hasProof       = !!order.payment_proof;
    const belumBayar     = order.payment_method === 'bayar_nanti';
    const ALL_STATUSES   = ['pending', 'diproses', 'selesai'];
    const statusIndex    = ALL_STATUSES.indexOf(order.status);

    return (
        <Card
            size="sm"
            className={cn(
                'relative min-w-0 transition-all duration-200 overflow-visible',
                belumBayar && 'border-destructive shadow-[0_4px_14px_rgba(239,68,68,0.10)]',
            )}
        >
            <CardContent className="flex flex-col gap-2.5">
                {/* ── Top row: order code + payment badge | status + ellipsis ── */}
                <div className="flex justify-between items-center gap-2">
                    {/* Left */}
                    <div className="flex items-center gap-2 min-w-0 overflow-hidden">
                        <span className="text-base font-semibold text-foreground truncate">
                            #{order.order_code}
                        </span>
                        {order.payment_method && order.payment_method !== 'bayar_nanti' && (() => {
                            const m = METHOD_META[order.payment_method] ?? METHOD_META['cash'];
                            const isQris = order.payment_method === 'qris';
                            return (
                                <span
                                    className="inline-flex items-center gap-0.5 rounded-md text-xs font-semibold shrink-0 px-1.5 py-0.5"
                                    style={{ background: m.bg, color: m.color }}
                                >
                                    {isQris
                                        ? <img src="/images/logo-qris.png" alt="QRIS" className="h-3.5 object-contain" />
                                        : <><m.Icon size={11} />{m.label}</>
                                    }
                                </span>
                            );
                        })()}

                    </div>

                    {/* Right: status badge + ellipsis */}
                    <div className="flex items-center gap-1.5 shrink-0">
                        <StatusBadge status={order.status} />

                        {order.status !== 'selesai' && (
                            <div ref={menuRef} className="relative">
                                <button
                                    onClick={() => setMenuOpen(v => !v)}
                                    aria-label="Opsi pesanan"
                                    className={cn(
                                        'w-7 h-7 rounded-lg border-none cursor-pointer flex items-center justify-center transition-colors duration-100',
                                        menuOpen ? 'bg-muted' : 'bg-transparent',
                                    )}
                                    style={{ color: '#475569' }}
                                >
                                    <MoreVertical size={16} />
                                </button>

                                {menuOpen && (
                                    <div
                                        className="absolute right-0 top-8 z-100 w-50 bg-card rounded-xl flex flex-col p-1.5"
                                        style={{
                                            border: '1px solid #E5E4E1',
                                            boxShadow: '0 6px 20px rgba(15,23,42,0.094), 0 1px 3px rgba(15,23,42,0.031)',
                                        }}
                                    >
                                        <div className="px-2.5 py-1 text-xs font-semibold tracking-wide" style={{ color: '#9C9B99' }}>
                                            Ubah Status
                                        </div>

                                        {ALL_STATUSES.map((s, i) => {
                                            const meta       = STATUS_META[s];
                                            const isCurrent  = s === order.status;
                                            const isNext     = i === statusIndex + 1;
                                            const isDisabled = !isCurrent && !isNext;
                                            const isBlocked  = s === 'selesai' && belumBayar;

                                            return (
                                                <button
                                                    key={s}
                                                    disabled={isDisabled || isCurrent || isBlocked}
                                                    onClick={() => {
                                                        if (!isNext || isBlocked) return;
                                                        setMenuOpen(false);
                                                        onMarkDone(order.id, s);
                                                    }}
                                                    className={cn(
                                                        'flex items-center justify-between w-full rounded-lg border-none text-left px-2.5 py-2 gap-2.5 transition-colors duration-100',
                                                        isCurrent && 'bg-muted',
                                                        (isNext && !isBlocked) && 'cursor-pointer hover:bg-muted',
                                                    )}
                                                    style={{
                                                        cursor: (isNext && !isBlocked) ? 'pointer' : 'default',
                                                        opacity: (isDisabled || isBlocked) ? 0.4 : 1,
                                                    }}
                                                >
                                                    <span className="flex items-center gap-2.5">
                                                        <span
                                                            className="w-2 h-2 rounded-full shrink-0 inline-block"
                                                            style={{ background: meta.dot }}
                                                        />
                                                        <span className="text-sm" style={{ fontWeight: isCurrent ? 600 : 500, color: '#1A1918' }}>
                                                            {meta.label}
                                                        </span>
                                                    </span>
                                                    {isCurrent && (
                                                        <Check size={14} color="#3D8A5A" strokeWidth={2.5} />
                                                    )}
                                                </button>
                                            );
                                        })}

                                        {/* Divider + Batalkan */}
                                        {onCancel && (
                                            <>
                                                <div style={{ height: 1, background: '#E5E4E1', margin: '4px 0' }} />
                                                <button
                                                    onClick={() => { setMenuOpen(false); onCancel(order); }}
                                                    className="flex items-center gap-2.5 w-full rounded-lg border-none text-left px-2.5 py-2 cursor-pointer transition-colors duration-100 hover:bg-red-50"
                                                    style={{ color: '#DC2626' }}
                                                >
                                                    <XCircle size={14} strokeWidth={2} />
                                                    <span className="text-sm font-medium">Batalkan Pesanan</span>
                                                </button>
                                            </>
                                        )}
                                    </div>
                                )}
                            </div>
                        )}
                    </div>
                </div>

                {/* ── Time ── */}
                <div className="flex items-center gap-2 text-xs text-muted-foreground">
                    <span>{formatTime(order.created_at)} · {formatDate(order.created_at)}</span>
                    {order.table_number && (
                        <span>· Meja {order.table_number}</span>
                    )}
                    {belumBayar && (
                        <span className="inline-flex items-center gap-0.5 rounded-md px-1.5 text-xs font-bold bg-destructive/10 text-destructive">
                            Belum Bayar
                        </span>
                    )}
                </div>

                {/* ── Items summary ── */}
                <div className="text-sm truncate text-muted-foreground">
                    {order.items_summary || '-'}
                </div>

                {/* ── Bottom: total + buttons ── */}
                <div className="flex justify-between items-center mt-0.5 gap-2">
                    <span className="text-base font-bold text-foreground">
                        {formatRupiah(order.total_amount)}
                    </span>

                    <div className="flex gap-1.5">
                        {order.qris_status === 'proof_submitted' && (
                            <Button
                                size="sm"
                                variant="default"
                                onClick={() => setShowQrisReview(true)}
                            >
                                Review Bukti QRIS
                            </Button>
                        )}

                        {isQrisPending && hasProof && (
                            <Button
                                size="sm"
                                onClick={() => onOpenQrisModal(order)}
                            >
                                Lihat Bukti
                            </Button>
                        )}

                        {/* Tombol Konfirmasi Lunas + Popover */}
                        {belumBayar && order.status === 'diproses' && (
                            <div ref={payRef} className="relative">
                                <button
                                    onClick={() => setPayPopover(v => !v)}
                                    className="h-8 px-3 border-none rounded-lg text-xs font-semibold cursor-pointer text-white"
                                    style={{
                                        background: '#EF4444',
                                        boxShadow: '0 2px 6px rgba(239,68,68,0.25)',
                                    }}
                                >
                                    Konfirmasi Lunas
                                </button>

                                {payPopover && (
                                    <div
                                        className="absolute bottom-9 right-0 z-200 bg-card rounded-xl flex flex-col gap-1 min-w-[150px] p-2"
                                        style={{
                                            border: '1px solid #E2E8F0',
                                            boxShadow: '0 6px 20px rgba(15,23,42,0.12)',
                                        }}
                                    >
                                        <span
                                            className="text-xs font-semibold px-2 py-0.5 text-muted-foreground"
                                            style={{ letterSpacing: 0.4 }}
                                        >
                                            Metode Pembayaran
                                        </span>
                                        {[
                                            { method: 'cash', label: 'Tunai',  Icon: Banknote },
                                            { method: 'qris', label: 'QRIS',   Icon: QrCode  },
                                        ].map(({ method, label, Icon }) => (
                                            <button
                                                key={method}
                                                onClick={() => {
                                                    setPayPopover(false);
                                                    onConfirmPayment(order.id, method);
                                                }}
                                                className="flex items-center gap-2 px-2.5 py-2 rounded-lg border-none bg-transparent cursor-pointer text-left text-sm font-medium text-foreground hover:bg-muted transition-colors duration-100"
                                            >
                                                <Icon size={15} className="text-muted-foreground" />
                                                {label}
                                            </button>
                                        ))}
                                    </div>
                                )}
                            </div>
                        )}

                        <Button
                            variant="outline"
                            size="sm"
                            onClick={() => onDetail(order.id)}
                            className="bg-white border-[#E2E8F0] text-[#374151] rounded-lg text-[13px] h-auto px-3.5 py-1 font-normal hover:bg-gray-50"
                        >
                            Detail
                        </Button>
                    </div>
                </div>
            </CardContent>

            {showQrisReview && (
                <QrisReviewModal
                    isOpen={showQrisReview}
                    onClose={() => setShowQrisReview(false)}
                    order={order}
                />
            )}
        </Card>
    );
}
