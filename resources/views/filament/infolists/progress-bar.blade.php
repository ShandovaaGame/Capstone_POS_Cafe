<div class="space-y-3">
    <div class="flex items-center justify-between">
        <span class="text-sm font-medium text-gray-500 dark:text-gray-400">Dibayar</span>
        <span class="text-sm font-semibold text-gray-950 dark:text-white">
            Rp {{ number_format($paidAmount, 0, ',', '.') }} / Rp {{ number_format($totalAmount, 0, ',', '.') }}
        </span>
    </div>

    <div class="relative h-3 w-full overflow-hidden rounded-full bg-gray-200 dark:bg-gray-700">
        <div class="h-full rounded-full transition-all duration-500 ease-in-out {{ $percentage >= 100 ? 'bg-success-500' : 'bg-primary-500' }}"
             style="width: {{ min($percentage, 100) }}%">
        </div>
    </div>

    <div class="flex items-center justify-between">
        <span class="text-xs text-gray-500 dark:text-gray-400">
            {{ number_format($percentage, 1) }}% lunas
        </span>
        <span class="text-xs font-medium {{ $remainingAmount > 0 ? 'text-danger-500' : 'text-success-500' }}">
            @if ($remainingAmount > 0)
                Sisa: Rp {{ number_format($remainingAmount, 0, ',', '.') }}
            @else
                Lunas ✓
            @endif
        </span>
    </div>
</div>
