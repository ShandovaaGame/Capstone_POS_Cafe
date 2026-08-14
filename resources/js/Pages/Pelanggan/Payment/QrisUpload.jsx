import { useState } from 'react';
import { router } from '@inertiajs/react';
import axios from 'axios';
import { ChevronLeft, Camera, Send, CheckCircle } from 'lucide-react';
import PelangganLayout from '@/Layouts/PelangganLayout';
import { formatRupiah } from '@/helpers';
import useCart from '@/Hooks/useCart';
import { cn } from '@/lib/utils';

export default function QrisUpload({ order, qrisImage, qrisName, totalAmount, rejectedMessage, resubmitCount = 0 }) {
    const [file,      setFile]      = useState(null);
    const [preview,   setPreview]   = useState(null);
    const [uploading, setUploading] = useState(false);
    const [error,     setError]     = useState(rejectedMessage ?? '');
    const [uploaded,  setUploaded]  = useState(false);
    const { clearCart } = useCart();

    function handleFileChange(e) {
        const f = e.target.files[0];
        if (!f) return;
        if (!['image/jpeg', 'image/png', 'image/jpg'].includes(f.type)) {
            setError('File harus berupa gambar (JPG atau PNG)');
            return;
        }
        if (f.size > 5 * 1024 * 1024) {
            setError('Ukuran file maksimal 5MB');
            return;
        }
        setError('');
        setFile(f);
        const reader = new FileReader();
        reader.onload = ev => setPreview(ev.target.result);
        reader.readAsDataURL(f);
    }

    async function handleUpload() {
        if (!file || uploading) return;
        setUploading(true);
        setError('');
        const formData = new FormData();
        formData.append('proof', file);
        try {
            await axios.post(`/api/order/${order.id}/qris-proof`, formData, {
                headers: { 'Content-Type': 'multipart/form-data' },
            });
            clearCart();
            setUploaded(true);
        } catch (err) {
            setError(err.response?.data?.message ?? 'Unggah gagal. Coba lagi.');
        } finally {
            setUploading(false);
        }
    }

    return (
        <PelangganLayout activeTab="cart">
            <div className="bg-card border-b border-border px-6 pb-4 pt-[22px]">
                <div className="flex items-center gap-[14px]">
                    <button
                        onClick={() => router.visit(`/pelanggan/bayar/${order.order_code}/pilih`)}
                        className="w-9 h-9 rounded-[12px] bg-muted border-none cursor-pointer flex items-center justify-center shrink-0"
                    >
                        <ChevronLeft size={20} className="text-foreground" />
                    </button>
                    <div>
                        <div className="text-xl font-bold text-foreground">
                            Pembayaran QRIS
                        </div>
                        <div className="text-xs text-muted-foreground">
                            #{order.order_code}
                        </div>
                    </div>
                </div>
            </div>

            <div className="px-6 pb-5 flex flex-col gap-[14px]">
                {rejectedMessage && !uploaded && (
                    <div className="bg-destructive/10 border border-destructive/30 rounded-[14px] p-[14px] mt-[14px]">
                        <div className="text-[13px] font-bold text-destructive mb-1">
                            Bukti Ditolak Kasir
                        </div>
                        <div className="text-[13px] text-muted-foreground">{rejectedMessage}</div>
                        <div className="text-xs text-muted-foreground/70 mt-1.5">
                            Silakan unggah ulang bukti pembayaran yang valid.
                        </div>
                        <div className="text-xs font-semibold text-primary mt-1">
                            Sisa kesempatan: {Math.max(0, 3 - resubmitCount)}x
                        </div>
                    </div>
                )}

                {!uploaded ? (
                    <>
                        <div className="bg-card rounded-[20px] border border-border px-5 pb-[14px] pt-[18px] flex flex-col items-center gap-[10px] shadow-[0_3px_14px_rgba(45,32,22,0.05)]" style={{ marginTop: rejectedMessage ? 0 : 14 }}>
                            <div className="w-[170px] h-[170px] rounded-[14px] overflow-hidden bg-muted flex items-center justify-center shadow-[0_2px_8px_rgba(45,32,22,0.10)]">
                                <img
                                    src={qrisImage}
                                    alt="QRIS W9 Cafe"
                                    className="w-full h-full object-contain"
                                    onError={e => { e.target.src = '/images/logo.jpg'; }}
                                />
                            </div>

                            <span className="text-sm font-medium text-muted-foreground">
                                {qrisName || 'W9 Cafe STIE Totalwin'}
                            </span>

                            <span className="text-2xl font-bold text-primary tracking-tight">
                                {formatRupiah(totalAmount)}
                            </span>

                            <span className="text-[11px] text-muted-foreground/50 text-center max-w-[260px]">
                                Pindai QR menggunakan aplikasi dompet digital Anda
                            </span>

                            <div className="w-full h-px bg-border" />

                            <div className="w-full flex flex-col items-center gap-2">
                                <div className="w-full flex items-center gap-1">
                                    <span className="text-[13px] font-semibold text-foreground">
                                        Unggah Bukti Pembayaran
                                    </span>
                                    <span className="text-[13px] font-semibold text-primary">*</span>
                                </div>

                                <label htmlFor="proof-upload" className="w-full cursor-pointer">
                                    <div
                                        className={cn(
                                            'w-full bg-muted/30 rounded-[14px] flex flex-col items-center justify-center gap-1.5 overflow-hidden transition-colors',
                                            file ? 'border border-primary' : 'border border-muted-foreground/20',
                                        )}
                                        style={{ height: preview ? 'auto' : 80, padding: preview ? 0 : undefined }}
                                    >
                                        {preview ? (
                                            <img
                                                src={preview}
                                                alt="preview"
                                                className="w-full object-contain"
                                                style={{ maxHeight: 200 }}
                                            />
                                        ) : (
                                            <>
                                                <Camera size={24} className="text-muted-foreground/40" />
                                                <span className="text-[13px] font-semibold text-foreground">
                                                    Pilih atau Foto Bukti Bayar
                                                </span>
                                                <span className="text-[11px] text-muted-foreground/50">
                                                    JPG, PNG, max 5MB
                                                </span>
                                            </>
                                        )}
                                    </div>
                                    <input
                                        id="proof-upload" type="file"
                                        accept="image/jpeg,image/png,image/jpg"
                                        className="hidden"
                                        onChange={handleFileChange}
                                        capture="environment"
                                    />
                                </label>

                                {error && (
                                    <p className="text-destructive text-xs mt-0.5 self-start" style={{ margin: '2px 0 0' }}>
                                        {error}
                                    </p>
                                )}
                            </div>
                        </div>

                        <div className="flex-1" />

                        <button
                            onClick={handleUpload}
                            disabled={!file || uploading}
                            className={cn(
                                'w-full h-[54px] border-none rounded-[18px] text-base font-bold flex items-center justify-center gap-2',
                                !file ? 'bg-muted text-muted-foreground/50 cursor-default' : 'bg-primary text-primary-foreground cursor-pointer shadow-[0_4px_16px_rgba(232,118,58,0.30)]',
                            )}
                        >
                            {uploading ? (
                                <svg className="animate-spin" width="18" height="18" viewBox="0 0 24 24" fill="none">
                                    <circle cx="12" cy="12" r="10" stroke="currentColor" strokeWidth="3" opacity="0.25" />
                                    <path d="M12 2a10 10 0 0 1 10 10" stroke="currentColor" strokeWidth="3" strokeLinecap="round" />
                                </svg>
                            ) : <Send size={18} />}
                            {uploading ? 'Mengupload...' : 'Kirim Bukti Pembayaran'}
                        </button>
                    </>
                ) : (
                    <div className="text-center py-10 flex flex-col items-center gap-4">
                        <CheckCircle size={72} className="text-green-500" />
                        <h2 className="text-[22px] font-bold text-foreground m-0">
                            Bukti Dikirim!
                        </h2>
                        <p className="text-sm text-muted-foreground m-0 leading-relaxed">
                            Pembayaran Anda sedang diverifikasi kasir.
                        </p>
                        <button
                            onClick={() => router.visit(`/pelanggan/bayar/${order.order_code}/qris-status`)}
                            className="w-full h-[52px] bg-primary text-primary-foreground border-none rounded-[18px] text-[15px] font-bold cursor-pointer flex items-center justify-center gap-2 shadow-[0_4px_16px_rgba(232,118,58,0.30)]"
                        >
                            Cek Status Verifikasi
                        </button>
                        <button
                            onClick={() => router.visit(route('pelanggan.pesanan.status', {code: order.order_code}))}
                            className="w-full h-[46px] bg-transparent border border-muted-foreground/30 rounded-[18px] text-[14px] font-semibold cursor-pointer text-muted-foreground"
                        >
                            Cek Status Pesanan
                        </button>
                    </div>
                )}
            </div>
        </PelangganLayout>
    );
}
