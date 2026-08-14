<?php

namespace App\Filament\Resources;

use App\Filament\Resources\CashierSessionResource\Pages\CreateCashierSession;
use App\Filament\Resources\CashierSessionResource\Pages\EditCashierSession;
use App\Filament\Resources\CashierSessionResource\Pages\ListCashierSessions;
use App\Models\CashierSession;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\Select;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\Filter;
use Filament\Tables\Table;

class CashierSessionResource extends Resource
{
    protected static ?string $model = CashierSession::class;

    protected static string | \BackedEnum | null $navigationIcon = 'heroicon-o-clock';

    protected static string | \UnitEnum | null $navigationGroup = 'Transaksi';

    protected static ?string $navigationLabel = 'Sesi Kasir';

    protected static ?int $navigationSort = 2;

    public static function form(Schema $schema): Schema
    {
        return $schema->components([
            Select::make('user_id')
                ->label('Kasir')
                ->relationship('user', 'name')
                ->required()
                ->searchable()
                ->preload(),
            DateTimePicker::make('started_at')
                ->label('Waktu Mulai')
                ->required()
                ->default(now())
                ->native(false),
            DateTimePicker::make('ended_at')
                ->label('Waktu Selesai')
                ->nullable()
                ->native(false),
        ])->columns(2);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('user.name')
                    ->label('Kasir')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('started_at')
                    ->label('Mulai')
                    ->dateTime('d M Y, H:i')
                    ->sortable(),
                TextColumn::make('ended_at')
                    ->label('Selesai')
                    ->dateTime('d M Y, H:i')
                    ->default('-')
                    ->sortable(),
                TextColumn::make('is_active')
                    ->label('Status')
                    ->badge()
                    ->getStateUsing(fn (CashierSession $record) => $record->is_active ? 'Aktif' : 'Selesai')
                    ->color(fn (string $state): string => $state === 'Aktif' ? 'success' : 'gray'),
                TextColumn::make('duration')
                    ->label('Durasi (jam)')
                    ->getStateUsing(fn (CashierSession $record) => $record->duration !== null ? number_format($record->duration, 2) : '-')
                    ->toggleable(),
            ])
            ->filters([
                Filter::make('active')
                    ->label('Sesi Aktif')
                    ->query(fn ($query) => $query->active()),
                Filter::make('today')
                    ->label('Hari Ini')
                    ->query(fn ($query) => $query->today()),
            ])
            ->recordActions([
                EditAction::make(),
                DeleteAction::make(),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                ]),
            ])
            ->defaultSort('started_at', 'desc');
    }

    public static function getPages(): array
    {
        return [
            'index'  => ListCashierSessions::route('/'),
            'create' => CreateCashierSession::route('/create'),
            'edit'   => EditCashierSession::route('/{record}/edit'),
        ];
    }
}
