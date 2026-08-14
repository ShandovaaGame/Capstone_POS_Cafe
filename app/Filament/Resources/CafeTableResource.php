<?php

namespace App\Filament\Resources;

use App\Filament\Resources\CafeTableResource\Pages\ListCafeTables;
use App\Models\CafeTable;
use Filament\Actions\Action;
use Filament\Actions\DeleteAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\TextInput;
use Filament\Infolists\Components\ImageEntry;
use Filament\Infolists\Components\TextEntry;
use Filament\Notifications\Notification;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class CafeTableResource extends Resource
{
    protected static ?string $model = CafeTable::class;

    protected static string|\BackedEnum|null $navigationIcon = 'heroicon-o-qr-code';

    protected static string|\UnitEnum|null $navigationGroup = 'Transaksi';

    protected static ?string $navigationLabel = 'QR Code Meja';

    protected static ?string $pluralLabel = 'QR Code Meja';

    protected static ?string $label = 'QR Code Meja';

    protected static ?int $navigationSort = 4;

    public static function form(Schema $schema): Schema
    {
        return $schema->components([
            TextInput::make('table_number')
                ->label('Nomor Meja')
                ->required()
                ->numeric()
                ->minValue(1)
                ->maxValue(99)
                ->unique(ignoreRecord: true)
                ->extraAttributes(['inputmode' => 'numeric']),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->searchPlaceholder('Cari Nomor Meja')
            ->columns([
                TextColumn::make('table_number')
                    ->label('Nomor Meja')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('qr_code_svg')
                    ->label('QR Code')
                    ->html()
                    ->width(120)
                    ->alignCenter()
                    ->formatStateUsing(function (CafeTable $record): string {
                        $dataUri = $record->qr_code_svg_data_uri;
                        return sprintf(
                            '<a href="%s" target="_blank" title="Buka URL">
                                <img src="%s" width="80" height="80"
                                     style="border-radius:4px;border:1px solid #e5e7eb;padding:4px;"
                                     alt="QR Meja %d" />
                            </a>',
                            e($record->qr_code_url),
                            $dataUri,
                            $record->table_number
                        );
                    }),
                TextColumn::make('qr_code_url')
                    ->label('URL Scan')
                    ->copyable()
                    ->copyMessage('Tersalin!')
                    ->limit(40)
                    ->tooltip(fn (CafeTable $record) => $record->qr_code_url),
            ])
            ->recordActions([
                EditAction::make()
                    ->modalWidth('md'),
                Action::make('view_qr')
                    ->label('Lihat QR')
                    ->icon('heroicon-o-qr-code')
                    ->modalHeading(fn (CafeTable $record) => 'QR Code — Meja '.$record->table_number)
                    ->modalWidth('md')
                    ->infolist(fn (CafeTable $record) => [
                        ImageEntry::make('qr_image')
                            ->hiddenLabel()
                            ->state(fn () => $record->qr_code_svg_data_uri)
                            ->width(200)
                            ->height(200)
                            ->extraImgAttributes([
                                'style' => 'border-radius:8px;border:1px solid #e5e7eb;padding:8px;margin:0 auto;display:block;',
                            ]),
                        TextEntry::make('qr_url')
                            ->hiddenLabel()
                            ->state(fn () => $record->qr_code_url)
                            ->color('primary')
                            ->copyable()
                            ->copyMessage('Tersalin!')
                            ->copyMessageDuration(1500)
                            ->alignCenter(),
                    ])
                    ->modalSubmitAction(false)
                    ->modalCancelActionLabel('Tutup')
                    ->modalFooterActions([
                        Action::make('download_qr_png')
                            ->label('Download PNG')
                            ->icon('heroicon-o-arrow-down-tray')
                            ->action(function (CafeTable $record) {
                                return response()->streamDownload(
                                    fn () => print($record->generatePngDownload()),
                                    sprintf('qr-meja-%d.png', $record->table_number),
                                    ['Content-Type' => 'image/png'],
                                );
                            }),
                    ]),
                DeleteAction::make()
                    ->requiresConfirmation()
                    ->modalHeading(fn (CafeTable $record) => 'Hapus Meja '.$record->table_number)
                    ->before(function (DeleteAction $action, CafeTable $record) {
                        if ($record->orders()->whereIn('status', ['pending', 'diproses'])->exists()) {
                            Notification::make()
                                ->danger()
                                ->title('Meja tidak dapat dihapus')
                                ->body('Meja ini masih memiliki pesanan aktif. Silakan tunggu pesanan selesai terlebih dahulu.')
                                ->send();
                            $action->cancel();
                        }
                    })
                    ->modalWidth('md'),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => ListCafeTables::route('/'),
        ];
    }
}
