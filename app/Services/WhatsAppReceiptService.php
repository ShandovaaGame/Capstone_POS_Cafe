<?php

namespace App\Services;

use App\Models\Order;
use App\Models\Setting;
use Illuminate\Support\Collection;

class WhatsAppReceiptService
{
    /**
     * Default WhatsApp receipt template. Available placeholders:
     * {{cafe_name}}, {{order_code}}, {{total}}, {{date}}, {{receipt_url}}
     */
    const DEFAULT_TEMPLATE = "Struk Belanja di {{cafe_name}} total {{total}}. Lihat detail & beri saran di {{receipt_url}} [ABAIKAN BILA TIDAK MEMBELI]";

    /**
     * Format an amount as Indonesian Rupiah string.
     */
    public function formatRupiah(int $amount): string
    {
        return 'Rp ' . number_format($amount, 0, ',', '.');
    }

    /**
     * Normalize an Indonesian phone number to international format (62-prefix).
     *
     * Rules:
     *  - Strip all non-digit characters
     *  - Leading "0" → replace with "62"
     *  - Leading "8" (without "62" prefix) → prepend "62"
     *  - Already starts with "62" → keep as-is
     */
    public function normalizePhone(string $raw): string
    {
        $digits = preg_replace('/[^0-9]/', '', $raw);

        if (str_starts_with($digits, '0')) {
            $digits = '62' . substr($digits, 1);
        } elseif (str_starts_with($digits, '8') && ! str_starts_with($digits, '62')) {
            $digits = '62' . $digits;
        }

        return $digits;
    }

    /**
     * Validate a normalized phone number: must be 10-15 digits, all numeric.
     */
    public function validatePhone(string $phone): bool
    {
        return (bool) preg_match('/^[0-9]{10,15}$/', $phone);
    }

    /**
     * Generate a WhatsApp message for the given order.
     *
     * Reads template from Setting::get('receipt_whatsapp_template', self::DEFAULT_TEMPLATE),
     * replaces placeholders, and prepends an item summary (max 2 items).
     */
    public function generateMessage(Order $order): string
    {
        $template = Setting::get('receipt_whatsapp_template', self::DEFAULT_TEMPLATE);

        $cafeName = Setting::get('cafe_name', config('app.name', 'W9 Cafe'));
        $date = $order->created_at->format('d M Y');

        $placeholders = [
            '{{cafe_name}}'   => $cafeName,
            '{{order_code}}'  => $order->order_code,
            '{{total}}'       => $this->formatRupiah($order->total_amount),
            '{{date}}'        => $date,
            '{{receipt_url}}' => $order->receipt_url,
        ];

        $message = str_replace(array_keys($placeholders), array_values($placeholders), $template);

        // Prepend item summary if items exist
        $itemsSummary = $this->summarizeItems($order->items);
        if ($itemsSummary) {
            $message = "Pesanan: {$itemsSummary}\n\n{$message}";
        }

        return $message;
    }

    /**
     * Build a wa.me click-to-chat link for the given order and phone number.
     *
     * Normalizes the phone, generates the message, URL-encodes it,
     * and returns the full wa.me link.
     */
    public function buildWaMeLink(Order $order, string $phone): string
    {
        $normalizedPhone = $this->normalizePhone($phone);

        if (! $this->validatePhone($normalizedPhone)) {
            throw new \InvalidArgumentException("Invalid phone number: {$phone}");
        }

        $message = $this->generateMessage($order);
        $encoded = urlencode($message);

        return "https://wa.me/{$normalizedPhone}?text={$encoded}";
    }

    /**
     * Summarize order items for inclusion in the WhatsApp message.
     *
     * Takes up to 2 items, formats as "2x Kopi Robusta, 1x Roti Bakar".
     * If more than 2 items exist, appends " dan N item lainnya".
     */
    protected function summarizeItems(Collection $items): string
    {
        if ($items->isEmpty()) {
            return '';
        }

        $firstTwo = $items->take(2);
        $parts = $firstTwo->map(function ($item) {
            $menuName = $item->menu ? $item->menu->name : 'Menu #' . $item->menu_id;
            return "{$item->quantity}x {$menuName}";
        });

        $summary = $parts->join(', ');

        if ($items->count() > 2) {
            $remaining = $items->count() - 2;
            $summary .= " dan {$remaining} item lainnya";
        }

        return $summary;
    }
}
