<?php

namespace App\Filament\Resources;

use Filament\Schemas\Schema;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\Filter;
use Filament\Forms\Components\DatePicker;
use Filament\Tables\Filters\SelectFilter;
use App\Filament\Resources\DailyIngredientUsageResource\Pages\ListDailyIngredientUsages;
use App\Filament\Resources\DailyIngredientUsageResource\Pages;
use App\Models\DailyIngredientUsage;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;

class DailyIngredientUsageResource extends Resource
{
    protected static ?string $model = DailyIngredientUsage::class;

    protected static string | \BackedEnum | null $navigationIcon = 'heroicon-o-chart-bar-square';

    protected static string | \UnitEnum | null $navigationGroup = 'Inventori';

    protected static ?string $navigationLabel = 'Pemakaian Bahan Harian';

    protected static ?int $navigationSort = 4;

    public static function form(Schema $schema): Schema
    {
        return $schema->components([]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('usage_date')
                    ->label('Tanggal')
                    ->date('d M Y')
                    ->sortable(),
                TextColumn::make('ingredient_name')
                    ->label('Bahan')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('unit')
                    ->label('Satuan')
                    ->badge()
                    ->sortable(),
                TextColumn::make('jumlah_digunakan')
                    ->label('Jumlah Digunakan')
                    ->numeric(decimalPlaces: 2)
                    ->sortable(),
                TextColumn::make('created_at')
                    ->label('Dicatat')
                    ->dateTime('d M Y H:i')
                    ->toggleable(isToggledHiddenByDefault: true)
                    ->sortable(),
            ])
            ->filters([
                Filter::make('usage_date_range')
                    ->label('Rentang Tanggal')
                    ->schema([
                        DatePicker::make('from')->label('Dari'),
                        DatePicker::make('until')->label('Sampai'),
                    ])
                    ->query(function ($query, array $data) {
                        return $query
                            ->when($data['from'] ?? null, fn ($q, $date) => $q->whereDate('usage_date', '>=', $date))
                            ->when($data['until'] ?? null, fn ($q, $date) => $q->whereDate('usage_date', '<=', $date));
                    }),
                SelectFilter::make('ingredient_id')
                    ->label('Bahan')
                    ->relationship('ingredient', 'name')
                    ->searchable()
                    ->preload(),
            ])
            ->recordActions([])
            ->toolbarActions([])
            ->defaultSort('usage_date', 'desc');
    }

    public static function canCreate(): bool
    {
        return false;
    }

    public static function canEdit($record): bool
    {
        return false;
    }

    public static function canDelete($record): bool
    {
        return false;
    }

    public static function getPages(): array
    {
        return [
            'index' => ListDailyIngredientUsages::route('/'),
        ];
    }
}
