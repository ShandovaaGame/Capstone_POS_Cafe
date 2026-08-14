<?php

namespace App\Filament\Resources;

use App\Filament\Helpers\NumberInputHelper;
use App\Filament\Helpers\TextInputHelper;
use App\Filament\Resources\MenuResource\Pages\ListMenus;
use App\Filament\Resources\MenuResource\RelationManagers\IngredientsRelationManager;
use App\Models\Ingredient;
use App\Models\Menu;
use App\Services\MenuImageService;
use Illuminate\Database\Eloquent\Builder;
use Filament\Actions\Action;
use Filament\Actions\DeleteAction;
use Filament\Actions\EditAction;
use Filament\Notifications\Notification;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Filters\TernaryFilter;
use Filament\Tables\Table;

class MenuResource extends Resource
{
    protected static ?string $model = Menu::class;

    protected static string|\BackedEnum|null $navigationIcon = 'heroicon-o-document-text';

    protected static string|\UnitEnum|null $navigationGroup = 'Menu';

    protected static ?string $navigationLabel = 'Menu';

    protected static ?int $navigationSort = 1;

    protected static bool $shouldRegisterNavigation = true;

    protected static ?string $slug = 'menu';

    protected static ?string $breadcrumb = 'Menu';

    protected static ?string $pluralLabel = 'Menu';

    public static function form(Schema $schema): Schema
    {
        return $schema->components([
            TextInput::make('name')
                ->label('Nama Menu')
                ->required()
                ->maxLength(255)
                ->live(onBlur: true)
                ->extraInputAttributes(TextInputHelper::string()),
            Select::make('category_id')
                ->label('Kategori Menu')
                ->relationship('category', 'name')
                ->required()
                ->searchable()
                ->preload()
                ->placeholder('Pilih Kategori Menu')
                ->createOptionForm([
                    TextInput::make('name')
                        ->label('Kategori Menu')
                        ->required(),
                ])
                ->createOptionAction(fn (Action $action) => $action->label('+ Kategori Baru')),
            FileUpload::make('image')
                ->label('Gambar Menu')
                ->directory('menus/')
                ->disk('public')
                ->imagePreviewHeight('200')
                ->placeholder('Pilih gambar...')
                ->acceptedFileTypes(['image/jpeg', 'image/png', 'image/webp'])
                ->maxSize(5120)
                ->nullable()
                ->saveUploadedFileUsing(function ($file) {
                    return app(MenuImageService::class)->convertAndStore($file);
                }),
            TextInput::make('price')
                ->label('Harga')
                ->required()
                ->type('text')
                ->minValue(0.01)
                ->stripCharacters('.')
                ->extraInputAttributes(NumberInputHelper::integer())
                ->prefix('Rp'),
            Toggle::make('is_available')
                ->label('Tersedia')
                ->default(true)
                ->inline(false),
            TextInput::make('student_price')
                ->label('Diskon Mahasiswa')
                ->type('text')
                ->minValue(0)
                ->rules([
                    fn (Get $get): \Closure => function (string $attribute, $value, \Closure $fail) use ($get) {
                        $price = (int) str_replace('.', '', $get('price') ?? '0');
                        $studentPrice = (int) str_replace('.', '', $value ?? '0');
                        if ($studentPrice > $price) {
                            $fail('Diskon mahasiswa tidak boleh lebih besar dari harga menu (Rp '.number_format($price, 0, ',', '.').').');
                        }
                    },
                ])
                ->stripCharacters('.')
                ->extraInputAttributes(NumberInputHelper::integer())
                ->prefix('Rp')
                ->placeholder('Kosongkan jika tidak ada'),
            Repeater::make('menuIngredients')
                ->relationship('menuIngredients')
                ->label('Resep Menu')
                ->addActionLabel('+ Tambah Bahan')
                ->columnSpanFull()
                ->minItems(1)
                ->required()
                ->schema([
                    Select::make('ingredient_id')
                        ->label('Bahan Baku')
                        ->relationship('ingredient', 'name')
                        ->required()
                        ->searchable()
                        ->preload()
                        ->live()
                        ->placeholder('Pilih bahan baku...')
                        ->getOptionLabelFromRecordUsing(fn ($record) => $record->name.' ('.$record->unit.')'),
                    TextInput::make('quantity_used')
                        ->label('Jumlah per Porsi')
                        ->required()
                        ->numeric()
                        ->minValue(0.01)
                        ->step(0.01)
                        ->suffix(fn (Get $get): ?string => $get('ingredient_id')
                            ? ' '.(Ingredient::find($get('ingredient_id'))?->unit ?? '')
                            : null),
                ]),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->searchPlaceholder('Cari Nama Menu')
            ->columns([
                TextColumn::make('name')
                    ->label('Nama Menu')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('category.name')
                    ->label('Kategori Menu')
                    ->sortable()
                    ->searchable(),
                TextColumn::make('price')
                    ->label('Harga')
                    ->formatStateUsing(fn ($state) => 'Rp'.number_format($state, 0, ',', '.'))
                    ->sortable(),
                TextColumn::make('student_price')
                    ->label('Diskon Mahasiswa')
                    ->formatStateUsing(fn ($state) => $state ? 'Rp'.number_format($state, 0, ',', '.') : '-')
                    ->sortable(),
                TextColumn::make('stock')
                    ->label('Sisa Jual')
                    ->headerTooltip('Maksimal porsi dihitung dari stok bahan baku. Nilai dapat berubah jika bahan baku dipakai bersama menu lain.')
                    ->formatStateUsing(fn ($state) => $state === null ? '-' : number_format($state, 0, ',', '.'))
                    ->color(fn ($state) => match (true) {
                        $state === null || $state > 10 => 'success',
                        $state > 0 => 'warning',
                        default => 'danger',
                    })
                    ->badge()
                    ->sortable(),
                TextColumn::make('is_available')
                    ->label('Tersedia')
                    ->badge()
                    ->formatStateUsing(fn ($state) => $state ? 'Tersedia' : 'Tidak')
                    ->color(fn ($state) => $state ? 'success' : 'danger'),
            ])
            ->filters([
                SelectFilter::make('category')
                    ->relationship('category', 'name')
                    ->label('Kategori Menu')
                    ->placeholder('Semua'),
                TernaryFilter::make('is_available')
                    ->label('Tersedia')
                    ->placeholder('Semua')
                    ->trueLabel('Tersedia')
                    ->falseLabel('Tidak Tersedia'),
                SelectFilter::make('ingredient')
                    ->label('Bahan Baku')
                    ->placeholder('Semua')
                    ->options(Ingredient::pluck('name', 'id'))
                    ->searchable()
                    ->query(fn (Builder $query, array $data) =>
                        $query->when($data['value'] ?? null, fn ($q, $id) =>
                            $q->whereHas('menuIngredients', fn ($q) =>
                                $q->where('ingredient_id', $id)
                            )
                        )
                    ),
            ])
            ->recordActions([
                EditAction::make()->modal()
                    ->before(function (EditAction $action, Menu $record) {
                        if (! $record->menuIngredients()->exists()) {
                            Notification::make()
                                ->warning()
                                ->title('Belum ada bahan baku')
                                ->body("Menu \"{$record->name}\" belum memiliki bahan baku. Tambahkan bahan baku terlebih dahulu agar stok dapat terdeduksi saat menu terjual.")
                                ->send();
                        }
                    }),
                DeleteAction::make()
                    ->modalDescription('Apakah Anda yakin ingin melakukan ini? Seluruh data pesanan menu ini tetap aman dan tidak berubah.'),
            ]);
    }

    public static function getRelations(): array
    {
        return [
            IngredientsRelationManager::class,
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => ListMenus::route('/'),
        ];
    }

}
