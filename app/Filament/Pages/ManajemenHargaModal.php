<?php

namespace App\Filament\Pages;

use App\Models\Menu;
use Filament\Actions\Action;
use Filament\Forms\Components\TextInput;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Concerns\InteractsWithTable;
use Filament\Tables\Contracts\HasTable;
use Filament\Tables\Table;

class ManajemenHargaModal extends Page implements HasTable
{
    use InteractsWithTable;

    protected static string|\BackedEnum|null $navigationIcon = 'heroicon-o-calculator';

    protected static string|\UnitEnum|null $navigationGroup = 'Data Master';

    protected static ?string $navigationLabel = 'Harga Modal';

    protected static ?string $title = 'Manajemen Harga Modal';

    protected static ?int $navigationSort = 3;

    public function getView(): string
    {
        return 'filament.pages.manajemen-harga-modal';
    }

    public function table(Table $table): Table
    {
        return $table
            ->query(fn () => Menu::query()->with('category')->orderBy('category_id')->orderBy('name'))
            ->columns([
                TextColumn::make('name')
                    ->label('Nama Menu')
                    ->searchable()
                    ->sortable()
                    ->weight('bold'),
                TextColumn::make('category.name')
                    ->label('Kategori')
                    ->sortable()
                    ->badge(),
                TextColumn::make('price')
                    ->label('Harga Jual')
                    ->money('IDR')
                    ->sortable(),
                TextColumn::make('harga_modal')
                    ->label('Harga Modal')
                    ->money('IDR')
                    ->sortable()
                    ->color(fn ($state) => $state > 0 ? 'success' : 'danger'),
                TextColumn::make('margin')
                    ->label('Margin')
                    ->getStateUsing(fn (Menu $record): string => $record->price > 0
                        ? number_format((($record->price - $record->harga_modal) / $record->price) * 100, 1) . '%'
                        : '-'
                    )
                    ->color('info'),
            ])
            ->recordActions([
                Action::make('edit_harga_modal')
                    ->label('Edit')
                    ->icon('heroicon-o-pencil')
                    ->color('warning')
                    ->schema([
                        TextInput::make('harga_modal')
                            ->label('Harga Modal (Rp)')
                            ->numeric()
                            ->minValue(0)
                            ->prefix('Rp')
                            ->required(),
                    ])
                    ->fillForm(fn (Menu $record): array => [
                        'harga_modal' => $record->harga_modal,
                    ])
                    ->action(function (Menu $record, array $data): void {
                        $record->update(['harga_modal' => $data['harga_modal']]);

                        Notification::make()
                            ->title('Harga modal diperbarui')
                            ->body("Harga modal {$record->name} berhasil disimpan.")
                            ->success()
                            ->send();
                    }),
            ])
            ->striped()
            ->defaultSort('category_id');
    }
}
