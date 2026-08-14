<div class="fi-ta-ctn divide-y divide-gray-200 dark:divide-white/5">
    <table class="fi-ta-table w-full table-auto divide-y divide-gray-200 text-start dark:divide-white/5">
        <thead>
            <tr class="bg-gray-50 dark:bg-white/5">
                <th class="fi-ta-header-cell px-3 py-3.5 text-sm font-semibold text-gray-950 dark:text-white text-start">Menu</th>
                <th class="fi-ta-header-cell px-3 py-3.5 text-sm font-semibold text-gray-950 dark:text-white text-center">Jumlah</th>
                <th class="fi-ta-header-cell px-3 py-3.5 text-sm font-semibold text-gray-950 dark:text-white text-end">Harga</th>
                <th class="fi-ta-header-cell px-3 py-3.5 text-sm font-semibold text-gray-950 dark:text-white text-end">Subtotal</th>
            </tr>
        </thead>
        <tbody class="divide-y divide-gray-200 dark:divide-white/5">
            @foreach ($items as $item)
                <tr class="fi-ta-row transition hover:bg-gray-50 dark:hover:bg-white/5">
                    <td class="fi-ta-col px-3 py-2 text-sm text-gray-950 dark:text-white">{{ $item->menu?->name ?? 'Menu Tidak Dikenal' }}</td>
                    <td class="fi-ta-col px-3 py-2 text-sm text-gray-950 dark:text-white text-center">{{ $item->quantity }}</td>
                    <td class="fi-ta-col px-3 py-2 text-sm text-gray-950 dark:text-white text-end">Rp {{ number_format($item->unit_price, 0, ',', '.') }}</td>
                    <td class="fi-ta-col px-3 py-2 text-sm font-semibold text-gray-950 dark:text-white text-end">Rp {{ number_format($item->subtotal, 0, ',', '.') }}</td>
                </tr>
            @endforeach
            @if ($items->isEmpty())
                <tr>
                    <td colspan="4" class="fi-ta-col px-3 py-4 text-sm text-gray-500 dark:text-gray-400 text-center">Tidak ada item pesanan.</td>
                </tr>
            @endif
        </tbody>
        <tfoot>
            <tr class="bg-gray-50 dark:bg-white/5">
                <td colspan="3" class="fi-ta-col px-3 py-2 text-sm font-semibold text-gray-950 dark:text-white text-end">Total</td>
                <td class="fi-ta-col px-3 py-2 text-sm font-bold text-gray-950 dark:text-white text-end">
                    Rp {{ number_format($totalAmount, 0, ',', '.') }}
                </td>
            </tr>
        </tfoot>
    </table>
</div>
