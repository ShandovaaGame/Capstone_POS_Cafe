import { useEffect } from 'react';
import { router } from '@inertiajs/react';
import PelangganLayout from '@/Layouts/PelangganLayout';
import { formatRupiah } from '@/helpers';

const STEPS = [
    { label: 'Pesanan Diterima',          statuses: ['menunggu_bayar_cash','dikonfirmasi','diproses','siap','selesai'] },
    { label: 'Menunggu Pembayaran Cash',  statuses: ['menunggu_bayar_cash'], active: true },
    { label: 'Pembayaran Dikonfirmasi',   statuses: ['dikonfirmasi','diproses','siap','selesai'] },
    { label: 'Pesanan Diproses',          statuses: ['diproses','siap','selesai'] },
    { label: 'Pesanan Siap Diambil',      statuses: ['siap','selesai'] },
];

export default function CashStatus({ order }) {
    useEffect(() => {
        if (['siap', 'selesai'].includes(order.status)) return;
        const id = setInterval(() => router.reload({ only: ['order'] }), 5000);
        return () => clearInterval(id);
    }, [order.status]);

    return (
        <PelangganLayout>
            <div className="px-6 py-6 mx-auto w-full max-w-[430px] min-h-screen bg-background flex flex-col items-center justify-center">
                <div className="w-20 h-20 rounded-full bg-secondary flex items-center justify-center text-4xl mb-5">
                    💵
                </div>

                <h1 className="text-[22px] font-bold text-foreground mb-1.5 text-center">
                    {order.status === 'selesai' ? 'Pesanan Selesai!' : 'Pesanan Dikonfirmasi!'}
                </h1>
                <p className="text-sm text-muted-foreground mb-1 text-center">
                    #{order.order_code}
                </p>
                <p className="text-[15px] text-foreground mb-6 text-center">
                    Total: <strong className="text-primary">{formatRupiah(order.total_amount)}</strong>
                </p>

                {order.status === 'menunggu_bayar_cash' && (
                    <div className="bg-secondary rounded-[16px] p-[18px] mb-5 w-full border border-primary/20">
                        <div className="text-sm font-bold text-primary mb-[10px]">
                            💡 Cara Bayar ke Kasir
                        </div>
                        <div className="text-[13px] text-muted-foreground leading-relaxed">
                            1. Tunjukkan kode <strong>#{order.order_code}</strong> ke kasir<br/>
                            2. Bayar sesuai total: <strong>{formatRupiah(order.total_amount)}</strong><br/>
                            3. Tunggu kasir mengkonfirmasi
                        </div>
                    </div>
                )}

                <div className="bg-card rounded-[16px] border border-border p-[18px] w-full mb-5">
                    <div className="text-[13px] font-bold text-foreground mb-[14px]">
                        Status Pesanan
                    </div>
                    {STEPS.map((step, i) => {
                        const done   = step.statuses.includes(order.status) && !step.active;
                        const active = step.active && order.status === 'menunggu_bayar_cash';
                        return (
                            <div key={i} className="flex items-center gap-3 mb-3">
                                <div className={`w-[26px] h-[26px] rounded-full shrink-0 flex items-center justify-center text-[13px] font-bold text-white ${done ? 'bg-green-500' : active ? 'bg-primary' : 'bg-stone-100'}`}>
                                    {done ? '✓' : active ? '◐' : ''}
                                </div>
                                <span className={`text-[13px] ${done ? 'text-green-600 font-semibold' : active ? 'text-primary font-semibold' : 'text-gray-400 font-normal'}`}>
                                    {step.label}
                                </span>
                            </div>
                        );
                    })}
                </div>

                {order.status !== 'selesai' && (
                    <p className="text-xs text-muted-foreground/50 text-center">
                        Halaman ini otomatis update setiap 5 detik
                    </p>
                )}

                {order.status === 'selesai' && (
                    <button
                        onClick={() => router.visit('/pelanggan/menu')}
                        className="w-full h-[50px] bg-primary text-primary-foreground border-none rounded-[16px] text-[15px] font-bold cursor-pointer"
                    >
                        Pesan Lagi
                    </button>
                )}
            </div>
        </PelangganLayout>
    );
}
