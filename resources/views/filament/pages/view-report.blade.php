<x-filament-panels::page>
    <div class="space-y-6 financial-report-wrapper">
        {{-- Back Button & Header --}}
        <div class="flex items-center justify-between">
            <div class="flex items-center gap-4">
                <a href="{{ \App\Filament\Resources\GeneratedReportResource::getUrl('index') }}"
                   class="inline-flex items-center gap-2 text-sm text-gray-600 dark:text-gray-400 hover:text-primary-600 dark:hover:text-primary-400 transition-colors">
                    <x-heroicon-o-arrow-left class="w-4 h-4"/>
                    <span>Kembali ke Daftar Laporan</span>
                </a>
            </div>
        </div>

        {{-- Report Title --}}
        <div>
            <h1 class="text-2xl font-bold text-gray-900 dark:text-gray-100">{{ $this->report->name }}</h1>
            <p class="text-sm text-gray-500 dark:text-gray-400 mt-1">
                Dibuat oleh {{ $this->report->user?->name ?? 'Unknown' }}
                · {{ $this->report->created_at->format('d F Y, H:i') }} WIB
            </p>
        </div>

        {{-- Metadata Cards --}}
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-4">
            <div class="rounded-xl border border-gray-200 dark:border-gray-700 bg-white dark:bg-gray-800 p-4">
                <p class="text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">Tipe Laporan</p>
                <p class="text-sm font-semibold text-gray-900 dark:text-gray-100 mt-1">{{ $this->getTypeLabel() }}</p>
            </div>
            <div class="rounded-xl border border-gray-200 dark:border-gray-700 bg-white dark:bg-gray-800 p-4">
                <p class="text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">Periode</p>
                <p class="text-sm font-semibold text-gray-900 dark:text-gray-100 mt-1">
                    {{ \Carbon\Carbon::parse($this->report->date_start)->format('d M Y') }}
                    &rarr;
                    {{ \Carbon\Carbon::parse($this->report->date_end)->format('d M Y') }}
                </p>
            </div>
            <div class="rounded-xl border border-gray-200 dark:border-gray-700 bg-white dark:bg-gray-800 p-4">
                <p class="text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">Agregasi</p>
                <p class="text-sm font-semibold text-gray-900 dark:text-gray-100 mt-1">{{ $this->getAggregationLabel() }}</p>
            </div>
            <div class="rounded-xl border border-gray-200 dark:border-gray-700 bg-white dark:bg-gray-800 p-4">
                <p class="text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">Status</p>
                <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-green-50 text-green-700 dark:bg-green-900/30 dark:text-green-300 mt-1">
                    ● Generated
                </span>
            </div>
        </div>

        {{-- Summary Section --}}
        @php $reportData = $this->getReportData(); @endphp
        @if(!empty($reportData->summary))
            <x-filament::section>
                <x-slot name="heading">Ringkasan Keuangan</x-slot>
                <div class="grid grid-cols-2 md:grid-cols-4 gap-4">
                    @foreach($reportData->summary as $item)
                        <div class="p-4 border rounded-lg">
                            <p class="text-xs text-gray-500">{{ $item->label }}</p>
                            <p class="text-sm font-semibold {{ $item->isHighlighted ? 'text-primary-600' : '' }}">
                                {{ $item->formattedValue }}
                            </p>
                        </div>
                    @endforeach
                </div>
            </x-filament::section>
        @endif

        {{-- AG Grid React (interactive table) --}}
        <div id="ag-grid-report-container"
             data-report-props='@json($this->getReportData()->toArray())'
             class="ag-grid-react-wrapper rounded-xl border border-gray-200 dark:border-gray-700 overflow-hidden"
        ></div>

        {{-- Raw JSON (collapsible) --}}
        <div x-data="{ open: false }" class="rounded-xl border border-gray-200 dark:border-gray-700 bg-white dark:bg-gray-800">
            <button @click="open = !open"
                    class="w-full flex items-center justify-between px-6 py-3 text-sm text-gray-500 dark:text-gray-400 hover:bg-gray-50 dark:hover:bg-gray-700/30 rounded-t-xl transition-colors">
                <span class="flex items-center gap-2">
                    <x-heroicon-o-code-bracket class="w-4 h-4"/>
                    Raw Report Data
                </span>
                <x-heroicon-o-chevron-down x-show="!open" class="w-4 h-4"/>
                <x-heroicon-o-chevron-up x-show="open" class="w-4 h-4"/>
            </button>
            <div x-show="open" x-cloak class="px-6 pb-4">
                <pre class="text-xs text-gray-600 dark:text-gray-400 bg-gray-50 dark:bg-gray-900 rounded-lg p-4 overflow-x-auto max-h-96">{{ json_encode($this->report->result, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE) }}</pre>
            </div>
        </div>
    </div>

    @vite(['resources/js/report-grid.jsx'])
</x-filament-panels::page>
