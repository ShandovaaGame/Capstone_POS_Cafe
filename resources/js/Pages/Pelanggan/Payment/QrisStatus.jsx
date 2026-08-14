import { useEffect } from 'react';
import { router } from '@inertiajs/react';
import PelangganLayout from '@/Layouts/PelangganLayout';
import { formatRupiah } from '@/helpers';

export default function QrisStatus({ order }) {
    const isRejected = order.status === 'pending' && !!order.rejection_note;
    const isDone     = order.status === 'selesai';

    useEffect(() => {
        if (isDone || isRejected) return;
        const id = setInterval(() => router.reload({ only: ['order'] }), 5000);
        return () => clearInterval(id);
    }, [order.status, order.rejection_note]);

    const isWaiting   = order.status === 'pending' && !order.rejection_note;
    const isConfirmed = order.status === 'diproses';

    return (
        <PelangganLayout>
            <div style={{
                padding: 24, maxWidth: 430, margin: '0 auto',
                fontFamily: "'Outfit', system-ui, sans-serif",
                minHeight: '100vh', background: '#FAF6F1',
                display: 'flex', flexDirection: 'column', alignItems: 'center',
                justifyContent: 'center',
            }}>

                {isWaiting && (
                    <>
                        <div style={{
                            width: 80, height: 80, borderRadius: '50%', background: '#FEF3EC',
                            display: 'flex', alignItems: 'center', justifyContent: 'center',
                            fontSize: 36, marginBottom: 20,
                        }}>⏳</div>
                        <h1 style={{ fontSize: 22, fontWeight: 700, color: '#2D2016', marginBottom: 8, textAlign: 'center' }}>
                            Menunggu Verifikasi
                        </h1>
                        <p style={{ fontSize: 14, color: '#8C7B6B', textAlign: 'center', lineHeight: 1.6, marginBottom: 24 }}>
                            Bukti pembayaran QRIS sedang diverifikasi kasir.<br/>
                            Mohon tunggu beberapa saat.
                        </p>
                        <p style={{ fontSize: 12, color: '#B5A898' }}>Halaman ini otomatis update setiap 5 detik</p>
                    </>
                )}

                {isConfirmed && (
                    <>
                        <div style={{ fontSize: 72, marginBottom: 16 }}>✅</div>
                        <h1 style={{ fontSize: 22, fontWeight: 700, color: '#2D2016', marginBottom: 8, textAlign: 'center' }}>
                            Pembayaran Dikonfirmasi!
                        </h1>
                        <p style={{ fontSize: 14, color: '#8C7B6B', textAlign: 'center', marginBottom: 24 }}>
                            #{order.order_code} · {formatRupiah(order.total_amount)}
                        </p>
                        <div style={{
                            background: '#FFFFFF', borderRadius: 16, border: '1px solid #EDE8E2',
                            padding: 18, width: '100%',
                        }}>
                            {[
                                { label: 'Pembayaran Dikonfirmasi', done: true },
                                { label: 'Pesanan Sedang Diproses', done: true },
                            ].map((s, i) => (
                                <div key={i} style={{ display: 'flex', alignItems: 'center', gap: 12, marginBottom: 12 }}>
                                    <div style={{
                                        width: 26, height: 26, borderRadius: '50%',
                                        background: s.done ? '#28A745' : '#EDE8E2',
                                        display: 'flex', alignItems: 'center', justifyContent: 'center',
                                        color: 'white', fontSize: 13, fontWeight: 700, flexShrink: 0,
                                    }}>
                                        {s.done ? '✓' : ''}
                                    </div>
                                    <span style={{ fontSize: 13, color: s.done ? '#28A745' : '#9AA3AF', fontWeight: s.done ? 600 : 400 }}>
                                        {s.label}
                                    </span>
                                </div>
                            ))}
                        </div>
                    </>
                )}

                {isDone && (
                    <>
                        <div style={{ fontSize: 72, marginBottom: 16 }}>🎉</div>
                        <h1 style={{ fontSize: 22, fontWeight: 700, color: '#2D2016', marginBottom: 8, textAlign: 'center' }}>
                            Pesanan Selesai!
                        </h1>
                        <p style={{ fontSize: 14, color: '#8C7B6B', textAlign: 'center', marginBottom: 28 }}>
                            Silakan ambil pesanan Anda di kasir.
                        </p>
                        <button
                            onClick={() => router.visit('/pelanggan/menu')}
                            style={{
                                width: '100%', height: 50, background: '#E8763A', color: 'white',
                                border: 'none', borderRadius: 16, fontSize: 15, fontWeight: 700, cursor: 'pointer',
                            }}
                        >
                            Pesan Lagi
                        </button>
                    </>
                )}

                {isRejected && (
                    <>
                        <div style={{ fontSize: 72, marginBottom: 16 }}>✗</div>
                        <h1 style={{ fontSize: 22, fontWeight: 700, color: '#DC2626', marginBottom: 8, textAlign: 'center' }}>
                            Bukti Pembayaran Ditolak
                        </h1>
                        {order.rejection_note && (
                            <div style={{
                                background: '#FEF2F2', border: '1px solid #FECACA',
                                borderRadius: 12, padding: 14, width: '100%', marginBottom: 20,
                            }}>
                                <div style={{ fontSize: 13, color: '#DC2626', fontWeight: 600, marginBottom: 4 }}>
                                    Alasan:
                                </div>
                                <div style={{ fontSize: 13, color: '#5C4A3A' }}>{order.rejection_note}</div>
                            </div>
                        )}
                        <p style={{ fontSize: 14, color: '#8C7B6B', textAlign: 'center', marginBottom: 24 }}>
                            Silakan upload ulang bukti pembayaran yang valid.
                        </p>
                        <button
                            onClick={() => router.visit(`/pelanggan/bayar/${order.order_code}/qris`)}
                            style={{
                                width: '100%', height: 50, background: '#E8763A', color: 'white',
                                border: 'none', borderRadius: 16, fontSize: 15, fontWeight: 700, cursor: 'pointer',
                            }}
                        >
                            Upload Ulang Bukti
                        </button>
                    </>
                )}
            </div>
        </PelangganLayout>
    );
}
