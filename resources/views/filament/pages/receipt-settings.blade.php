<x-filament-panels::page>
    <div class="grid grid-cols-1 gap-8 lg:grid-cols-2">
        <div>
            {{ $this->form }}
        </div>

        <div>
            <div class="flex items-center justify-between mb-4">
                <h3 class="text-lg font-medium text-gray-900 dark:text-gray-100">
                    Pratinjau Struk
                </h3>

                <x-filament::button
                    wire:click="refreshPreview"
                    color="gray"
                    size="sm"
                >
                    ↻ Refresh Pratinjau
                </x-filament::button>
            </div>

            <div class="overflow-hidden bg-white border border-gray-200 rounded-lg shadow-sm dark:border-gray-700 dark:bg-gray-800">
                @include('filament.pages.receipt-preview', ['data' => $this->previewData ?? []])
            </div>

            @if (!empty($this->previewData['receipt_whatsapp_template']))
                <div style="height: 30px;"></div>
                <div class="mt-6">
                    <h3 class="text-lg font-medium text-gray-900 dark:text-gray-100 mb-4">
                        Pratinjau WhatsApp
                    </h3>

                    <div style="background-color: #efeae2; padding: 16px; border-radius: 8px; border: 1px solid #e5e7eb;" class="dark:border-gray-700 dark:bg-gray-900 shadow-sm">

                        <div style="display: flex; align-items: flex-start; gap: 8px;">

                            <div style="width: 32px; height: 32px; border-radius: 50%; background-color: #22c55e; color: white; display: flex; align-items: center; justify-content: center; font-size: 14px; font-weight: bold; flex-shrink: 0; margin-top: 0px;">
                                K
                            </div>

                            <div style="position: relative; max-width: 85%; min-width: 120px; padding: 6px 8px 6px 12px; border-radius: 8px; border-top-left-radius: 0; background-color: white;" class="shadow-sm dark:bg-gray-800">

                                <div style="font-size: 13px; font-weight: 600; color: #16a34a; margin-bottom: 0px;">
                                    Kasir
                                </div>

                                <div style="font-size: 14px; line-height: 1.4; white-space: pre-line; word-break: break-word; overflow-wrap: break-word; color: #1f2937;" class="dark:text-gray-200">{!! trim(str_replace('(link)', 'https://w9cafe.com/struk/a1b2c3d4-e5f6-7890-abcd-ef1234567890', ($this->previewData['receipt_whatsapp_template'] ?? ''))) !!}<span style="display: inline-block; width: 38px;"></span></div>

                                <div style="position: absolute; bottom: 4px; right: 8px; font-size: 10px; color: #6b7280;">
                                    {{ now()->format('H.i') }}
                                </div>

                            </div>
                        </div>

                    </div>
                </div>
            @endif
        </div>
    </div>
</x-filament-panels::page>
