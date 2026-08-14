import { useState } from 'react';
import axios from 'axios';
import { Send } from 'lucide-react';
import Modal from '@/Components/Shared/Modal';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { formatRupiah } from '@/helpers';

export default function WhatsAppShareModal({ isOpen, onClose, order, onSkip }) {
    const [phone, setPhone] = useState('');
    const [sending, setSending] = useState(false);
    const [error, setError] = useState('');

    function handlePhoneChange(e) {
        // Auto-strip non-digits
        const value = e.target.value.replace(/\D/g, '');
        setPhone(value);
        if (error) setError('');
    }

    async function handleSend() {
        if (!phone.trim()) {
            setError('Nomor WhatsApp tidak boleh kosong');
            return;
        }
        setSending(true);
        setError('');
        try {
            const { data } = await axios.post(
                route('kasir.pesanan.whatsapp-link', { order: order.id }),
                { phone },
            );
            window.open(data.wa_link, '_blank');
            onClose();
        } catch (err) {
            setError(
                err.response?.data?.message || 'Gagal membuat tautan WhatsApp',
            );
        } finally {
            setSending(false);
        }
    }

    function handleSkip() {
        onSkip();
    }

    // Build simple preview message from order data
    const itemSummary =
        order?.items
            ?.slice(0, 2)
            .map((i) => `${i.quantity}x ${i.name}`)
            .join(', ') ?? '';
    const previewMessage = itemSummary
        ? `Pesanan: ${itemSummary}${order.items.length > 2 ? ` dan ${order.items.length - 2} item lainnya` : ''}\n\nStruk Belanja di W9 Cafe total ${formatRupiah(order?.total_amount || 0)}.`
        : `Struk Belanja di W9 Cafe total ${formatRupiah(order?.total_amount || 0)}.`;

    return (
        <Modal isOpen={isOpen} onClose={onClose} title="Kirim Struk via WhatsApp" size="sm">
            <div className="px-6 pb-6">
                {/* Phone input with +62 prefix */}
                <div className="mb-4">
                    <label className="text-sm font-medium text-foreground mb-1.5 block">
                        Nomor WhatsApp Pelanggan
                    </label>
                    <div className="flex">
                        <span className="inline-flex items-center px-3.5 border border-r-0 border-border rounded-l-lg bg-muted text-muted-foreground text-sm shrink-0">
                            +62
                        </span>
                        <Input
                            type="tel"
                            value={phone}
                            onChange={handlePhoneChange}
                            placeholder="81234567890"
                            className="rounded-l-none"
                        />
                    </div>
                    {error && (
                        <p className="mt-1.5 text-xs text-destructive flex items-center gap-1">
                            <span>⚠</span> {error}
                        </p>
                    )}
                </div>

                {/* Non-editable message preview */}
                <div className="mb-5">
                    <label className="text-sm font-medium text-foreground mb-1.5 block">
                        Pesan yang akan dikirim
                    </label>
                    <div className="bg-muted border border-border rounded-lg p-3.5 text-sm text-muted-foreground whitespace-pre-wrap leading-relaxed select-none">
                        {previewMessage}
                    </div>
                </div>

                {/* Action buttons */}
                <div className="flex flex-col gap-2.5">
                    <Button
                        onClick={handleSend}
                        disabled={sending}
                        className="w-full h-11 gap-2"
                    >
                        <Send size={16} />
                        {sending ? 'Memproses...' : 'Kirim via WhatsApp'}
                    </Button>
                    <Button
                        variant="outline"
                        onClick={handleSkip}
                        disabled={sending}
                        className="w-full h-11"
                    >
                        Lewati
                    </Button>
                </div>
            </div>
        </Modal>
    );
}
