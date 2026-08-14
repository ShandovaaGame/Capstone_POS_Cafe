<?php

namespace App\Filament\Resources\MenuResource\RelationManagers;

use Filament\Actions\CreateAction;
use Filament\Actions\DeleteAction;
use Filament\Actions\EditAction;
use Filament\Notifications\Notification;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Get;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class IngredientsRelationManager extends RelationManager
{
    protected static string $relationship = 'menuIngredients';

    protected static ?string $title = 'Resep (Bahan)';

    public function form(Schema $schema): Schema
    {
        return $schema->components([
            Select::make('ingredient_id')
                ->label('Bahan')
                ->relationship('ingredient', 'name')
                ->required()
                ->searchable()
                ->preload()
                ->live()
                ->getOptionLabelFromRecordUsing(fn ($record) => $record->name.' ('.$record->unit.')'),
            TextInput::make('quantity_used')
                ->label('Jumlah per Porsi')
                ->required()
                ->numeric()
                ->minValue(0.01)
                ->step(0.01),
            Select::make('unit_id')
                ->label('Satuan')
                ->options(fn (Get $get): array => self::getCompatibleUnitOptions($get('ingredient_id')))
                ->searchable()
                ->preload(),
        ]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('ingredient.name')
                    ->label('Bahan')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('quantity_used')
                    ->label('Jumlah/Porsi')
                    ->sortable()
                    ->formatStateUsing(fn ($state, $record) => $state.' '.($record->unit?->abbreviation ?? '')),
                TextColumn::make('ingredient.total_stock')
                    ->label('Stok Tersedia')
                    ->getStateUsing(fn ($record) => number_format($record->ingredient?->getTotalStock() ?? 0, 2))
                    ->suffix(fn ($record) => ' '.($record->ingredient->unit ?? ''))
                    ->badge()
                    ->color(fn ($record) => ($record->ingredient?->getTotalStock() ?? 0) < (float) ($record->ingredient->low_stock_threshold ?? 0) ? 'danger' : 'success'),
            ])
            ->headerActions([
                CreateAction::make(),
            ])
            ->recordActions([
                EditAction::make(),
                DeleteAction::make()
                    ->before(function (DeleteAction $action) {
                        /** @var \App\Models\Menu $menu */
                        $menu = $this->getOwnerRecord();
                        if ($menu->menuIngredients()->count() <= 1) {
                            Notification::make()
                                ->danger()
                                ->title('Bahan terakhir tidak dapat dihapus')
                                ->body('Setiap menu minimal harus memiliki satu bahan baku.')
                                ->send();
                            $action->cancel();
                        }
                    }),
            ])
            ->toolbarActions([]);
    }

    private static function getCompatibleUnitOptions(?int $ingredientId): array
    {
        return \App\Models\Unit::pluck('name', 'id')->toArray();
    }
}
