<?php

namespace App\Filament\Resources\StockAdjustmentResource\RelationManagers;

use App\Models\StockMovement;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class MovementsRelationManager extends RelationManager
{
    protected static string $relationship = 'stockMovements';

    protected static ?string $title = 'Detail Bahan Baku';

    public function form(Schema $schema): Schema
    {
        return $schema->components([]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->modifyQueryUsing(fn ($query) => $query->with(['ingredient', 'ingredientBatch']))
            ->columns([
                TextColumn::make('ingredient.name')
                    ->label('Bahan Baku')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('ingredient.unit')
                    ->label('Unit')
                    ->sortable(),
                TextColumn::make('quantity_change')
                    ->label('Perubahan')
                    ->formatStateUsing(fn ($state, StockMovement $record) =>
                        ($state >= 0 ? '+ ' : '- ')
                        . number_format(abs((float) $state), 0, ',', '.')
                        . ' ' . ($record->ingredient?->unit ?? '')
                    )
                    ->sortable(),
                TextColumn::make('quantity_before')
                    ->label('Sebelum')
                    ->formatStateUsing(fn ($state, StockMovement $record) =>
                        number_format((float) $state, 0, ',', '.')
                        . ' ' . ($record->ingredient?->unit ?? '')
                    )
                    ->sortable(),
                TextColumn::make('quantity_after')
                    ->label('Sesudah')
                    ->formatStateUsing(fn ($state, StockMovement $record) =>
                        number_format((float) $state, 0, ',', '.')
                        . ' ' . ($record->ingredient?->unit ?? '')
                    )
                    ->sortable(),
                TextColumn::make('ingredient_batch_id')
                    ->label('Batch')
                    ->default('-')
                    ->sortable(),
            ])
            ->headerActions([])
            ->recordActions([])
            ->toolbarActions([])
            ->defaultSort('id', 'desc');
    }
}
