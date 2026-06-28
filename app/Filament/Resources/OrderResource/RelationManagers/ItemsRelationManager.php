<?php

namespace App\Filament\Resources\OrderResource\RelationManagers;

use Filament\Schemas\Schema;
use Filament\Tables\Columns\TextColumn;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables\Table;

class ItemsRelationManager extends RelationManager
{
    protected static string $relationship = 'items';

    protected static ?string $title = 'Item Pesanan';

    public function form(Schema $schema): Schema
    {
        return $schema->components([]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('menu.name')
                    ->label('Menu'),
                TextColumn::make('quantity')
                    ->label('Qty'),
                TextColumn::make('unit_price')
                    ->label('Harga Jual')
                    ->money('IDR'),
                TextColumn::make('menu.harga_modal')
                    ->label('Harga Modal')
                    ->money('IDR'),
                TextColumn::make('total_harga_modal')
                    ->label('Total Harga Modal')
                    ->getStateUsing(fn ($record): float => ($record->menu?->harga_modal ?? 0) * $record->quantity)
                    ->money('IDR'),
                TextColumn::make('subtotal')
                    ->label('Subtotal')
                    ->money('IDR'),
                TextColumn::make('keuntungan')
                    ->label('Keuntungan')
                    ->getStateUsing(fn ($record): float => $record->subtotal - (($record->menu?->harga_modal ?? 0) * $record->quantity))
                    ->money('IDR')
                    ->color('success'),
            ])
            ->paginated(false);
    }
}
