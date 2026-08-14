<?php

namespace App\Filament\Resources\IngredientResource\Pages;

use App\Filament\Resources\IngredientResource;
use App\Filament\Resources\OrderResource;
use App\Filament\Resources\StockAdjustmentResource;
use App\Models\Ingredient;
use App\Models\StockAdjustment;
use App\Models\StockMovement;
use Filament\Actions\Action;
use Filament\Infolists\Components\TextEntry;
use Filament\Resources\Pages\ListRecords;
use Filament\Schemas\Components\Section;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class ViewStockHistory extends ListRecords
{
    protected static string $resource = IngredientResource::class;

    protected static ?string $breadcrumb = 'Riwayat Stok';

    public ?Ingredient $ingredient = null;

    public function mount(): void
    {
        $this->ingredient = Ingredient::findOrFail(request()->route('record'));
        parent::mount();
    }

    public function getTitle(): string
    {
        return 'Riwayat Stok: '.($this->ingredient?->name ?? '');
    }

    protected function getTableQuery(): \Illuminate\Database\Eloquent\Builder
    {
        return StockMovement::with(['order', 'stockAdjustment', 'ingredientBatch'])
            ->where('ingredient_id', $this->ingredient->id);
    }

    public function table(Table $table): Table
    {
        return $table
            ->query(fn () => $this->getTableQuery())
            ->searchPlaceholder('Cari...')
            ->filters([])
            ->recordAction(null)
            ->columns([
                TextColumn::make('created_at')
                    ->label('Waktu')
                    ->dateTime('d M Y, H:i:s')
                    ->sortable(),

                TextColumn::make('movement_type')
                    ->label('Jenis Pemakaian')
                    ->badge()
                    ->color(fn (StockMovement $record): string => match (true) {
                        $record->source_type === 'stock_adjustment_reversal'                   => 'gray',
                        $record->movement_type === 'sale'                                      => 'primary',
                        $record->movement_type === 'purchase'                                  => 'success',
                        $record->stockAdjustment?->category === StockAdjustment::CAT_EXPIRED  => 'danger',
                        $record->movement_type === 'waste'                                     => 'danger',
                        in_array($record->movement_type, ['adjustment_increase', 'adjustment_decrease']) => 'warning',
                        default => 'gray',
                    })
                    ->formatStateUsing(fn (StockMovement $record): string => match (true) {
                        $record->source_type === 'stock_adjustment_reversal'
                            => 'Pembatalan Penyesuaian',
                        $record->stockAdjustment?->category === StockAdjustment::CAT_EXPIRED
                            => 'Kedaluwarsa',
                        $record->movement_type === 'sale'    => 'Penjualan',
                        $record->movement_type === 'purchase' => 'Pembelian',
                        $record->movement_type === 'waste'   => 'Penyesuaian',
                        in_array($record->movement_type, ['adjustment_increase', 'adjustment_decrease'])
                            => 'Penyesuaian',
                        default => $record->movement_type,
                    })
                    ->tooltip(fn (StockMovement $record): string => match (true) {
                        $record->movement_type === 'sale'
                            => 'Stok berkurang karena ada pesanan penjualan',
                        $record->movement_type === 'purchase'
                            => 'Stok bertambah karena ada pembelian',
                        $record->source_type === 'stock_adjustment_reversal'
                            => 'Kembalikan stok akibat penyesuaian dibatalkan',
                        $record->stockAdjustment?->category === StockAdjustment::CAT_EXPIRED
                            => 'Stok berkurang karena batch sudah kedaluwarsa',
                        $record->movement_type === 'waste'
                            => 'Stok berkurang karena bahan kedaluwarsa/rusak/tumpah',
                        in_array($record->movement_type, ['adjustment_increase', 'adjustment_decrease'])
                            => 'Stok disesuaikan secara manual',
                        default => '',
                    }),

                TextColumn::make('reference')
                    ->label('Referensi')
                    ->state(fn (StockMovement $record): string => match (true) {
                        $record->movement_type === 'sale' && $record->order
                            => $record->order->order_code,
                        $record->movement_type === 'purchase' && $record->ingredientBatch
                            => ($record->ingredientBatch->batch_code ?? '#'.$record->ingredientBatch->id),
                        (bool) $record->stock_adjustment_id
                            => StockAdjustment::find($record->stock_adjustment_id)?->code
                                ?? ('ADJ-'.str_pad($record->stock_adjustment_id, 3, '0', STR_PAD_LEFT)),
                        default => '-',
                    }),

                TextColumn::make('ingredient_batch_id')
                    ->label('Batch')
                    ->formatStateUsing(fn (StockMovement $record): string =>
                        $record->ingredientBatch?->batch_code
                        ?? '#'.($record->ingredient_batch_id ?? '-')
                    ),

                TextColumn::make('quantity_change')
                    ->label('Perubahan')
                    ->formatStateUsing(fn ($state) =>
                        number_format((float) $state, (float) $state != (int) $state ? 2 : 0, ',', '.')
                        .' '.($this->ingredient?->unit ?? '')
                    )
                    ->color(fn (StockMovement $record): string => $record->quantity_change < 0 ? 'danger' : 'success')
                    ->sortable(),

                TextColumn::make('quantity_before')
                    ->label('Sebelum')
                    ->formatStateUsing(fn ($state) =>
                        number_format((float) $state, (float) $state != (int) $state ? 2 : 0, ',', '.')
                        .' '.($this->ingredient?->unit ?? '')
                    )
                    ->sortable(),

                TextColumn::make('quantity_after')
                    ->label('Sesudah')
                    ->formatStateUsing(fn ($state) =>
                        number_format((float) $state, (float) $state != (int) $state ? 2 : 0, ',', '.')
                        .' '.($this->ingredient?->unit ?? '')
                    )
                    ->sortable(),
            ])
            ->recordActions([
                Action::make('view_adjustment')
                    ->label('Detail')
                    ->icon('heroicon-o-eye')
                    ->visible(fn (StockMovement $record): bool =>
                        (bool) $record->stock_adjustment_id)
                    ->infolist(function (StockMovement $record): array {
                        $record->loadMissing('stockAdjustment.ingredient', 'stockAdjustment.menu', 'stockAdjustment.reportedBy');

                        return StockAdjustmentResource::getInfolistComponents(prefix: 'stockAdjustment.');
                    })
                    ->modalAutofocus(false)
                    ->modalSubmitAction(false)
                    ->modalCancelActionLabel('Tutup'),

                Action::make('view_order')
                    ->label('Detail')
                    ->icon('heroicon-o-eye')
                    ->visible(fn (StockMovement $record): bool =>
                        $record->movement_type === 'sale' && (bool) $record->order_id)
                    ->infolist(function (StockMovement $record): array {
                        $record->loadMissing('order.items.menu', 'order.cashier');

                        return OrderResource::getInfolistComponents(prefix: 'order.');
                    })
                    ->modalAutofocus(false)
                    ->modalSubmitAction(false)
                    ->modalCancelActionLabel('Tutup'),

                Action::make('view_purchase')
                    ->label('Detail')
                    ->icon('heroicon-o-eye')
                    ->modalHeading('Detail Pembelian Batch')
                    ->modalSubmitAction(false)
                    ->modalCancelActionLabel('Tutup')
                    ->modalAutofocus(false)
                    ->visible(fn ($record) => $record->movement_type === 'purchase')
                    ->infolist(fn ($record) => [
                        Section::make('Informasi Batch')
                            ->schema([
                                TextEntry::make('ingredientBatch.batch_code')->label('Kode Batch')->copyable(),
                                TextEntry::make('ingredientBatch.received_at')->label('Waktu Diterima')->dateTime('d M Y, H:i:s'),
                                TextEntry::make('ingredientBatch.expiry_date')->label('Tanggal Kedaluwarsa')->date('d M Y')->default('-'),
                                TextEntry::make('ingredientBatch.quantity')->label('Quantity Awal')->formatStateUsing(fn ($state) => number_format((float) $state, 2)),
                                TextEntry::make('ingredientBatch.cost_per_unit')->label('Harga per Unit')->money('IDR')->default('-'),
                                TextEntry::make('ingredientBatch.status')->label('Status')->badge()
                                    ->formatStateUsing(fn ($state) => $state === 'active' ? 'Aktif' : 'Nonaktif'),
                                TextEntry::make('ingredientBatch.allow_expired_usage')->label('Bisa Kedaluwarsa')->boolean(),
                            ])->columns(3),
                        Section::make('Statistik Pemakaian')
                            ->schema([
                                TextEntry::make('quantity_before')->label('Quantity Awal')->formatStateUsing(fn ($state) => number_format((float) $state, 2)),
                                TextEntry::make('quantity_change')->label('Perubahan')->formatStateUsing(fn ($state) => number_format((float) $state, 2)),
                                TextEntry::make('quantity_after')->label('Sisa')->formatStateUsing(fn ($state) => number_format((float) $state, 2)),
                            ])->columns(3),
                    ]),
            ])
            ->defaultSort('created_at', 'desc');
    }

    protected function getHeaderActions(): array
    {
        return [
            Action::make('back')
                ->label('Kembali')
                ->color('gray')
                ->icon('heroicon-o-arrow-left')
                ->url(static::$resource::getUrl('index')),
        ];
    }
}
