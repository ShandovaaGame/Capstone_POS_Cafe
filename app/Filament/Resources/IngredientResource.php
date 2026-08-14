<?php

namespace App\Filament\Resources;

use App\Filament\Helpers\NumberInputHelper;
use App\Filament\Helpers\TextInputHelper;
use App\Filament\Resources\IngredientResource\Pages\ListIngredients;
use App\Filament\Resources\IngredientResource\Pages\ManageBatches;
use App\Filament\Resources\IngredientResource\Pages\ViewStockHistory;
use App\Models\Ingredient;
use Filament\Actions\Action;
use Filament\Actions\DeleteAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Notifications\Notification;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\Filter;
use Filament\Tables\Table;

class IngredientResource extends Resource
{
    protected static ?string $model = Ingredient::class;

    protected static string|\BackedEnum|null $navigationIcon = 'heroicon-o-cube';

    protected static bool $shouldRegisterNavigation = true;

    protected static string|\UnitEnum|null $navigationGroup = 'Inventori';

    protected static ?string $navigationLabel = 'Bahan Baku';

    protected static ?string $pluralLabel = 'Bahan Baku';

    protected static ?string $label = 'Bahan Baku';

    protected static ?string $slug = 'stok';

    protected static ?int $navigationSort = 1;

    public static function form(Schema $schema): Schema
    {
        return $schema->components([
            TextInput::make('name')
                ->label('Nama Bahan')
                ->required()
                ->maxLength(255)
                ->unique(ignoreRecord: true)
                ->extraInputAttributes(TextInputHelper::string()),
            Select::make('unit')
                ->label('Unit')
                ->options(Ingredient::UNITS)
                ->required()
                ->searchable()
                ->native(false)
                ->live(),
            TextInput::make('low_stock_threshold')
                ->label('Peringatan Stok Rendah')
                ->type('text')
                ->extraInputAttributes(NumberInputHelper::decimal())
                ->formatStateUsing(fn ($state) => $state !== null && $state !== '' ? number_format((float) $state, 2, ',', '.') : '')
                ->stripCharacters('.')
                ->dehydrateStateUsing(fn ($state) => is_string($state) ? (float) str_replace(',', '.', $state) : $state)
                ->suffix(fn ($get) => $get('unit') ? ' '.$get('unit') : ''),
            Select::make('batch_mode')
                ->label('Prioritas Batch')
                ->options(array_slice(Ingredient::batchModes(), 0, 2))
                ->default(Ingredient::BATCH_MODE_FEFO)
                ->required()
                ->native(false),
            Repeater::make('batches')
                ->relationship('batches')
                ->label('Stok Awal (Batch)')
                ->addActionLabel('+ Tambah Batch')
                ->hiddenOn('edit')
                ->columnSpanFull()
                ->columns(1)
                ->schema([
                    TextInput::make('quantity')
                        ->label('Jumlah')
                        ->required()
                        ->minValue(0)
                        ->step(0.1)
                        ->type('text')
                        ->stripCharacters('.')
                        ->dehydrateStateUsing(fn ($state) => is_string($state) ? (float) str_replace(',', '.', $state) : $state)
                        ->extraInputAttributes(NumberInputHelper::decimal())
                        ->suffix(fn ($get) => $get('../../unit') ? ' '.$get('../../unit') : ''),
                    DatePicker::make('expiry_date')
                        ->label('Tanggal Kadaluarsa')
                        ->nullable()
                        ->native(false)
                        ->required(fn ($get) => $get('../../batch_mode') === Ingredient::BATCH_MODE_FEFO)
                        ->helperText(fn ($get) => $get('../../batch_mode') === Ingredient::BATCH_MODE_FEFO
                            ? 'Wajib diisi untuk mode FEFO'
                            : null),
                    DateTimePicker::make('received_at')
                        ->label('Diterima Tanggal')
                        ->nullable()
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
                        ->prefix(fn ($get) => $get('../../unit') ? 'Rp/'.$get('../../unit') : 'Rp'),
                ])
                ->defaultItems(0)
                ->collapsible(),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->searchPlaceholder('Cari Nama Bahan')
            ->columns([
                TextColumn::make('name')
                    ->label('Nama Bahan')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('unit')
                    ->label('Unit')
                    ->sortable(),
                TextColumn::make('nearest_expiry')
                    ->label('Kedaluwarsa Terdekat')
                    ->getStateUsing(fn (Ingredient $record) =>
                        $record->batches()
                            ->where('quantity', '>', 0)
                            ->whereNotNull('expiry_date')
                            ->min('expiry_date')
                    )
                    ->date('d M Y')
                    ->color(fn ($state) => $state && \Carbon\Carbon::parse($state)->isPast() ? 'danger' : null)
                    ->sortable(query: fn ($query, string $direction) =>
                        $query->orderByRaw('(SELECT MIN(expiry_date) FROM ingredient_batches WHERE ingredient_id = ingredients.id AND quantity > 0 AND expiry_date IS NOT NULL) '.$direction)
                    ),
                TextColumn::make('low_stock_threshold')
                    ->label('Peringatan Stok Rendah')
                    ->formatStateUsing(fn ($state) =>
                        $state === null || $state === ''
                            ? ''
                            : number_format((float) $state, (float) $state != (int) $state ? 2 : 0, ',', '.')
                    )
                    ->suffix(fn (Ingredient $record) => ' '.$record->unit)
                    ->sortable(),
                TextColumn::make('total_stock')
                    ->label('Total Stok')
                    ->getStateUsing(fn (Ingredient $record) => $record->getTotalStock() + 0)
                    ->suffix(fn (Ingredient $record) => ' '.$record->unit)
                    ->badge()
                    ->color(fn (Ingredient $record) => $record->getTotalStock() < (float) $record->low_stock_threshold ? 'danger' : 'success')
                    ->sortable(query: function ($query, string $direction): void {
                        $query->orderByRaw('(SELECT COALESCE(SUM(quantity), 0) FROM ingredient_batches WHERE ingredient_batches.ingredient_id = ingredients.id) '.$direction);
                    }),
                TextColumn::make('batch_mode')
                    ->label('Prioritas Batch')
                    ->badge()
                    ->color(fn ($state) => $state === 'fifo' ? 'info' : 'warning')
                    ->formatStateUsing(fn ($state) => strtoupper((string) $state)),
            ])
            ->filters([
                Filter::make('low_stock')
                    ->label('Stok Rendah')
                    ->query(fn ($query) => $query->whereRaw('(SELECT COALESCE(SUM(quantity), 0) FROM ingredient_batches WHERE ingredient_batches.ingredient_id = ingredients.id) < low_stock_threshold')),
            ])
            ->recordActions([
                Action::make('batches')
                    ->label('Stok Bahan')
                    ->icon('heroicon-o-cube')
                    ->url(fn ($record) => static::getUrl('batches', ['record' => $record])),
                Action::make('history')
                    ->label('Riwayat Pemakaian')
                    ->icon('heroicon-o-clock')
                    ->url(fn ($record) => static::getUrl('history', ['record' => $record])),
                EditAction::make()->modal(),
                DeleteAction::make()
                    ->before(function (DeleteAction $action, Ingredient $record) {
                        $activeCount = $record->menuIngredients()
                            ->whereHas('menu', fn ($q) => $q->whereNull('deleted_at'))
                            ->count();

                        if ($activeCount > 0) {
                            Notification::make()
                                ->danger()
                                ->title("Bahan baku '{$record->name}' tidak dapat dihapus")
                                ->body("Masih digunakan oleh {$activeCount} menu. Gunakan filter Bahan Baku di halaman Menu untuk melihat daftarnya.")
                                ->send();

                            $action->cancel();
                        }
                    }),
            ])
            ->defaultSort('nearest_expiry', 'asc');
    }

    public static function getRelations(): array
    {
        return [];
    }

    public static function getPages(): array
    {
        return [
            'index'   => ListIngredients::route('/'),
            'batches' => ManageBatches::route('/{record}/batches'),
            'history' => ViewStockHistory::route('/{record}/history'),
        ];
    }
}
