<?php

namespace App\Filament\Resources\IngredientResource\Pages;

use App\Filament\Helpers\NumberInputHelper;
use App\Filament\Resources\IngredientResource;
use App\Models\Ingredient;
use App\Models\IngredientBatch;
use App\Models\StockAdjustment;
use App\Models\StockMovement;
use App\Services\StockReconciliationService;
use Filament\Actions\Action;
use Filament\Actions\CreateAction;
use Filament\Actions\DeleteAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\Page;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Concerns\InteractsWithTable;
use Filament\Tables\Contracts\HasTable;
use Filament\Tables\Table;
use Illuminate\Contracts\Support\Htmlable;
use Illuminate\Support\Facades\Auth;

class ManageBatches extends Page implements HasTable
{
    use InteractsWithTable;

    protected static string $resource = IngredientResource::class;

    protected string $view = 'filament.pages.manage-batches';

    public Ingredient $record;

    public bool $showDepleted = false;

    public function mount(Ingredient $record): void
    {
        $this->record = $record;
    }

    public function getTitle(): string|Htmlable
    {
        return "Batch Stok - {$this->record->name}";
    }

    public function table(Table $table): Table
    {
        $unit = $this->record->unit;

        return $table
            ->query(fn () => IngredientBatch::where('ingredient_id', $this->record->id)
                ->when(! $this->showDepleted, fn ($q) => $q->where('quantity', '>', 0)))
            ->columns([
                TextColumn::make('batch_code')
                    ->label('Kode Batch')
                    ->default('-'),
                TextColumn::make('id')
                    ->label('ID Batch')
                    ->sortable(),
                TextColumn::make('quantity')
                    ->label('Jumlah')
                    ->formatStateUsing(fn ($state) => number_format((float) $state, (float) $state != (int) $state ? 2 : 0, ',', '.').' '.$unit)
                    ->sortable(),
                TextColumn::make('expiry_date')
                    ->label('Tanggal Kedaluwarsa')
                    ->date('d M Y')
                    ->sortable()
                    ->color(fn ($record) => $record->expiry_date && $record->expiry_date->isPast() ? 'danger' : null),
                TextColumn::make('received_at')
                    ->label('Waktu Diterima')
                    ->dateTime('d M Y, H:i:s')
                    ->sortable(),
                TextColumn::make('cost_per_unit')
                    ->label('Harga/Unit')
                    ->formatStateUsing(fn ($state) => 'Rp'.number_format($state, 0, ',', '.'))
                    ->sortable(),
                TextColumn::make('allow_expired_usage')
                    ->label('')
                    ->default('')
                    ->state(fn ($record) => $record->allow_expired_usage && $record->expiry_date && $record->expiry_date->isPast() ? '⚠️ Abaikan Kedaluwarsa' : '')
                    ->color('warning'),
            ])
            ->headerActions([
                Action::make('toggle_depleted')
                    ->label(fn () => $this->showDepleted ? 'Sembunyikan Batch Habis' : 'Tampilkan Batch Habis')
                    ->color(fn () => $this->showDepleted ? 'gray' : 'info')
                    ->action(fn () => $this->showDepleted = ! $this->showDepleted),
                CreateAction::make()
                    ->model(IngredientBatch::class)
                    ->form([
                        TextInput::make('quantity')
                            ->label('Jumlah')
                            ->required()
                            ->minValue(0)
                            ->step(0.1)
                            ->type('text')
                            ->stripCharacters('.')
                            ->dehydrateStateUsing(fn ($state) => is_string($state) ? (float) str_replace(',', '.', $state) : $state)
                            ->extraInputAttributes(NumberInputHelper::decimal())
                            ->suffix(fn () => ' '.$this->record->unit),
                        DatePicker::make('expiry_date')
                            ->label('Tanggal Kedaluwarsa')
                            ->native(false)
                            ->required(fn () => $this->record->batch_mode === Ingredient::BATCH_MODE_FEFO)
                            ->helperText(fn () => $this->record->batch_mode === Ingredient::BATCH_MODE_FEFO
                                ? 'Wajib untuk mode FEFO'
                                : null),
                        DateTimePicker::make('received_at')
                            ->label('Waktu Diterima')
                            ->required()
                            ->default(now())
                            ->native(false),
                        TextInput::make('cost_per_unit')
                            ->label('Harga per Unit')
                            ->required()
                            ->numeric()
                            ->minValue(0)
                            ->type('text')
                            ->stripCharacters('.')
                            ->extraInputAttributes(NumberInputHelper::integer())
                            ->prefix('Rp'),
                        Toggle::make('allow_expired_usage')
                            ->label('Bisa dipakai meskipun kedaluwarsa')
                            ->helperText('Batch ini tetap bisa dipakai FEFO walau sudah kedaluwarsa')
                            ->visible(fn () => $this->record->batch_mode === Ingredient::BATCH_MODE_FEFO)
                            ->default(false),
                    ])
                    ->using(function (array $data): IngredientBatch {
                        $batch = $this->record->batches()->create($data);

                        StockMovement::create([
                            'ingredient_id'       => $batch->ingredient_id,
                            'ingredient_batch_id' => $batch->id,
                            'movement_type'       => 'purchase',
                            'source_type'         => 'batch_addition',
                            'quantity_before'     => 0,
                            'quantity_change'     => $batch->quantity,
                            'quantity_after'      => $batch->quantity,
                            'reference'           => $batch->batch_code,
                            'recorded_by'         => Auth::id(),
                            'notes'               => 'Pembelian batch '.($batch->batch_code ?? $batch->id),
                        ]);

                        return $batch;
                    }),
            ])
            ->recordActions([
                EditAction::make()
                    ->form([
                        TextInput::make('quantity')
                            ->label('Jumlah')
                            ->required()
                            ->minValue(0)
                            ->step(0.1)
                            ->type('text')
                            ->stripCharacters('.')
                            ->dehydrateStateUsing(fn ($state) => is_string($state) ? (float) str_replace(',', '.', $state) : $state)
                            ->extraInputAttributes(NumberInputHelper::decimal())
                            ->suffix(fn () => ' '.$this->record->unit),
                        DatePicker::make('expiry_date')
                            ->label('Tanggal Kedaluwarsa')
                            ->native(false)
                            ->required(fn () => $this->record->batch_mode === Ingredient::BATCH_MODE_FEFO)
                            ->helperText(fn () => $this->record->batch_mode === Ingredient::BATCH_MODE_FEFO
                                ? 'Wajib untuk mode FEFO'
                                : null),
                        DateTimePicker::make('received_at')
                            ->label('Waktu Diterima')
                            ->required()
                            ->default(now())
                            ->native(false),
                        TextInput::make('cost_per_unit')
                            ->label('Harga per Unit')
                            ->required()
                            ->numeric()
                            ->minValue(0)
                            ->type('text')
                            ->stripCharacters('.')
                            ->extraInputAttributes(NumberInputHelper::integer())
                            ->prefix('Rp'),
                        Toggle::make('allow_expired_usage')
                            ->label('Bisa dipakai meskipun kedaluwarsa')
                            ->helperText('Batch ini tetap bisa dipakai FEFO walau sudah kedaluwarsa'),
                    ])
                    ->before(function (array $data, IngredientBatch $record) {
                        $rawQty = $data['quantity'] ?? null;
                        if ($rawQty === null) {
                            return;
                        }

                        $newQty = (float) str_replace(',', '.', $rawQty);
                        $oldQty = (float) $record->quantity;
                        if (abs($oldQty - $newQty) < 0.001) {
                            return;
                        }

                        $diff      = $newQty - $oldQty;
                        $unitLabel = $record->ingredient?->unit ?? '';
                        $batchCode = $record->batch_code ?? '#'.$record->id;
                        $adjType   = $diff > 0 ? StockAdjustment::TYPE_INCREASE : StockAdjustment::TYPE_DECREASE;
                        $note      = "Batch {$batchCode}: qty {$oldQty} → {$newQty} {$unitLabel}";

                        StockAdjustment::create([
                            'code'            => StockReconciliationService::generateAdjustmentCode(),
                            'adjustable_type' => StockAdjustment::ADJUSTABLE_TYPE_INGREDIENT,
                            'ingredient_id'   => $record->ingredient_id,
                            'adjustment_type' => $adjType,
                            'category'        => StockAdjustment::CAT_CORRECTION,
                            'quantity'        => abs($diff),
                            'quantity_before' => $oldQty,
                            'quantity_after'  => $newQty,
                            'reason'          => $note,
                            'recorded_by'     => Auth::id(),
                            'adjusted_at'     => now(),
                            'status'          => StockAdjustment::STATUS_ACTIVE,
                        ]);

                        Notification::make()
                            ->success()
                            ->title('Penyesuaian stok otomatis tercatat')
                            ->body($note)
                            ->send();
                    }),
                DeleteAction::make()
                    ->before(function (DeleteAction $action, IngredientBatch $record) {
                        if ($record->stockMovements()->exists()) {
                            Notification::make()
                                ->warning()
                                ->title('Batch tidak dapat dihapus')
                                ->body('Batch ini memiliki riwayat pemakaian. Batch telah dinonaktifkan.')
                                ->send();

                            $record->update(['quantity' => 0, 'status' => IngredientBatch::STATUS_INACTIVE]);
                            $action->cancel();

                            return;
                        }

                        if ((float) $record->quantity > 0) {
                            $unitLabel = $record->ingredient?->unit ?? '';
                            $batchCode = $record->batch_code ?? '#'.$record->id;
                            $note      = "Hapus batch {$batchCode}: sisa {$record->quantity} {$unitLabel}";

                            StockAdjustment::create([
                                'code'            => StockReconciliationService::generateAdjustmentCode(),
                                'adjustable_type' => StockAdjustment::ADJUSTABLE_TYPE_INGREDIENT,
                                'ingredient_id'   => $record->ingredient_id,
                                'adjustment_type' => StockAdjustment::TYPE_DECREASE,
                                'category'        => StockAdjustment::CAT_CORRECTION,
                                'quantity'        => (float) $record->quantity,
                                'quantity_before' => (float) $record->quantity,
                                'quantity_after'  => 0,
                                'reason'          => $note,
                                'recorded_by'     => Auth::id(),
                                'adjusted_at'     => now(),
                                'status'          => StockAdjustment::STATUS_ACTIVE,
                            ]);
                        }
                    }),
                Action::make('mark_expired')
                    ->label('Tandai Kedaluwarsa')
                    ->icon('heroicon-o-x-circle')
                    ->color('danger')
                    ->visible(fn (IngredientBatch $record): bool =>
                        $record->expiry_date
                        && $record->expiry_date->isPast()
                        && (float) $record->quantity > 0
                    )
                    ->requiresConfirmation()
                    ->modalHeading('Tandai Batch Kedaluwarsa')
                    ->modalDescription(fn (IngredientBatch $record): string =>
                        "Batch {$record->batch_code} sudah kedaluwarsa sejak "
                        .$record->expiry_date->format('d M Y')
                        .". Stok sisa {$record->quantity} ".($record->ingredient?->unit ?? '')
                        .' akan dihapus dan dicatat sebagai waste.'
                    )
                    ->modalSubmitActionLabel('Ya, Tandai')
                    ->action(function (IngredientBatch $record) {
                        $unitLabel = $record->ingredient?->unit ?? '';
                        $batchCode = $record->batch_code ?? '#'.$record->id;
                        $qty       = (float) $record->quantity;

                        $adjustment = StockAdjustment::create([
                            'code'            => StockReconciliationService::generateAdjustmentCode(),
                            'adjustable_type' => StockAdjustment::ADJUSTABLE_TYPE_INGREDIENT,
                            'ingredient_id'   => $record->ingredient_id,
                            'adjustment_type' => StockAdjustment::TYPE_DECREASE,
                            'category'        => StockAdjustment::CAT_EXPIRED,
                            'quantity'        => $qty,
                            'quantity_before' => $qty,
                            'quantity_after'  => 0,
                            'reason'          => "Batch {$batchCode} kedaluwarsa: {$qty} {$unitLabel}",
                            'recorded_by'     => Auth::id(),
                            'adjusted_at'     => now(),
                            'status'          => StockAdjustment::STATUS_ACTIVE,
                        ]);

                        StockMovement::create([
                            'ingredient_id'       => $record->ingredient_id,
                            'ingredient_batch_id' => $record->id,
                            'stock_adjustment_id' => $adjustment->id,
                            'movement_type'       => 'waste',
                            'source_type'         => 'stock_adjustment',
                            'source_id'           => (string) $adjustment->id,
                            'quantity_before'     => $qty,
                            'quantity_change'     => -$qty,
                            'quantity_after'      => 0,
                            'unit_cost'           => $record->cost_per_unit,
                            'notes'               => "Batch {$batchCode} kedaluwarsa",
                            'recorded_by'         => Auth::id(),
                        ]);

                        $record->update(['quantity' => 0, 'status' => IngredientBatch::STATUS_INACTIVE]);

                        Notification::make()
                            ->success()
                            ->title('Batch ditandai kedaluwarsa')
                            ->body("Stok {$batchCode} telah dihapus dan dicatat di Penyesuaian Stok.")
                            ->send();
                    }),
            ])
            ->toolbarActions([])
            ->defaultSort('expiry_date', 'asc');
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
