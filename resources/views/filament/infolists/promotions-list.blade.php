<div class="space-y-3">
    @forelse ($promotions as $promotion)
        <div class="flex items-center justify-between rounded-lg border border-gray-200 px-4 py-3 dark:border-gray-700">
            <div class="flex items-center gap-3">
                <div class="flex h-8 w-8 items-center justify-center rounded-full bg-success-50 text-success-600 dark:bg-success-500/10 dark:text-success-400">
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                        <path d="M20.59 13.41l-7.17 7.17a2 2 0 0 1-2.83 0L2 12V2h10l8.59 8.59a2 2 0 0 1 0 2.82z"/>
                        <line x1="7" y1="7" x2="7.01" y2="7"/>
                    </svg>
                </div>
                <div>
                    <p class="text-sm font-semibold text-gray-950 dark:text-white">
                        {{ $promotion->promotion?->name ?? 'Promosi' }}
                    </p>
                    <p class="text-xs text-gray-500 dark:text-gray-400">
                        @if ($promotion->discount_type === 'percentage')
                            Diskon {{ number_format((float) $promotion->discount_value, 0) }}%
                        @elseif ($promotion->discount_type === 'fixed_amount')
                            Potongan Rp {{ number_format((float) $promotion->discount_value, 0, ',', '.') }}
                        @else
                            {{ ucfirst($promotion->discount_type) }} — Nilai: {{ number_format((float) $promotion->discount_value, 0, ',', '.') }}
                        @endif
                    </p>
                </div>
            </div>
            <div class="text-end">
                <p class="text-sm font-bold text-danger-600 dark:text-danger-400">
                    - Rp {{ number_format((float) $promotion->discount_amount, 0, ',', '.') }}
                </p>
            </div>
        </div>
    @empty
        <p class="text-sm text-gray-500 dark:text-gray-400 px-4 py-3">Tidak ada promosi yang diterapkan.</p>
    @endforelse

    @if ($promotions->isNotEmpty())
        <div class="flex items-center justify-between rounded-lg bg-success-50 px-4 py-3 dark:bg-success-500/10">
            <span class="text-sm font-semibold text-success-700 dark:text-success-300">Total Diskon</span>
            <span class="text-sm font-bold text-success-700 dark:text-success-300">
                - Rp {{ number_format((float) $totalDiscount, 0, ',', '.') }}
            </span>
        </div>
    @endif
</div>
