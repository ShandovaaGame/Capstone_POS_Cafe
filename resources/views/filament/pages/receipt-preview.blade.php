<div class="receipt-preview">
    <div class="mx-auto" style="max-width: 320px; font-family: 'Courier New', Courier, monospace; background: #fff; padding: 24px; border: 1px dashed #d1d5db;">
        {{-- Judul --}}
        <div style="text-align: center; font-size: 18px; font-weight: 700; letter-spacing: 1px; text-transform: uppercase; color: #1f2937;">
            {{ $data['receipt_title'] ?? 'W9 Cafe' }}
        </div>

        {{-- Header --}}
        @if (!empty($data['receipt_header']))
            <div style="text-align: center; font-size: 11px; color: #6b7280; margin-top: 4px; white-space: pre-line;">
                {{ $data['receipt_header'] }}
            </div>
        @endif

        {{-- Divider --}}
        <div style="border-top: 1px dashed #9ca3af; margin: 12px 0;"></div>

        {{-- Order info --}}
        <div style="font-size: 11px; color: #6b7280; margin-bottom: 8px;">
            <div style="display: flex; justify-content: space-between;">
                <span>#ORD-048</span>
                <span>22 Feb 2026, 10:25</span>
            </div>
        </div>

        <div style="border-top: 1px dashed #9ca3af; margin-bottom: 8px;"></div>

        {{-- Items --}}
        <table style="width: 100%; font-size: 11px; color: #374151; border-collapse: collapse;">
            <thead>
                <tr>
                    <th style="text-align: left; padding-bottom: 4px; border-bottom: 1px solid #d1d5db;">Menu</th>
                    <th style="text-align: center; padding-bottom: 4px; border-bottom: 1px solid #d1d5db;">Jumlah</th>
                    <th style="text-align: right; padding-bottom: 4px; border-bottom: 1px solid #d1d5db;">Harga</th>
                </tr>
            </thead>
            <tbody>
                <tr>
                    <td style="padding: 3px 0;">Kopi Robusta</td>
                    <td style="text-align: center; padding: 3px 0;">2</td>
                    <td style="text-align: right; padding: 3px 0;">Rp24.000</td>
                </tr>
                <tr>
                    <td style="padding: 3px 0;">Roti Bakar</td>
                    <td style="text-align: center; padding: 3px 0;">1</td>
                    <td style="text-align: right; padding: 3px 0;">Rp15.000</td>
                </tr>
                <tr>
                    <td style="padding: 3px 0;">Teh Manis</td>
                    <td style="text-align: center; padding: 3px 0;">1</td>
                    <td style="text-align: right; padding: 3px 0;">Rp5.000</td>
                </tr>
            </tbody>
        </table>

        <div style="border-top: 1px dashed #9ca3af; margin: 6px 0;"></div>

        {{-- Total --}}
        <div style="display: flex; justify-content: space-between; font-size: 13px; font-weight: 700; color: #1f2937; padding: 4px 0;">
            <span>TOTAL</span>
            <span>Rp44.000</span>
        </div>

        {{-- Footer --}}
        <div style="border-top: 1px dashed #9ca3af; margin: 10px 0 8px;"></div>
        <div style="text-align: center; font-size: 11px; color: #6b7280; white-space: pre-line;">
            {{ $data['receipt_footer'] ?? '' }}
        </div>
    </div>
</div>
