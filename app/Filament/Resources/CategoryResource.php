<?php

namespace App\Filament\Resources;

use App\Filament\Helpers\TextInputHelper;
use App\Filament\Resources\CategoryResource\Pages\ListCategories;
use App\Models\Category;
use Filament\Actions\DeleteAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\TextInput;
use Filament\Notifications\Notification;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class CategoryResource extends Resource
{
    protected static ?string $model = Category::class;

    protected static string|\BackedEnum|null $navigationIcon = 'heroicon-o-tag';

    protected static string|\UnitEnum|null $navigationGroup = 'Menu';

    protected static ?string $navigationLabel = 'Kategori Menu';

    protected static ?int $navigationSort = 2;

    protected static bool $shouldRegisterNavigation = true;

    protected static ?string $slug = 'kategori';

    protected static ?string $breadcrumb = 'Kategori Menu';

    protected static ?string $pluralLabel = 'Kategori Menu';

    protected static ?string $label = 'Kategori Menu';

    public static function form(Schema $schema): Schema
    {
        return $schema->components([
            TextInput::make('name')
                ->label('Kategori Menu')
                ->required()
                ->unique(ignoreRecord: true)
                ->maxLength(100)
                ->extraInputAttributes(TextInputHelper::string(100)),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->searchPlaceholder('Cari Nama Kategori')
            ->columns([
                TextColumn::make('name')
                    ->label('Kategori Menu')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('menus_count')
                    ->label('Jumlah Menu')
                    ->counts('menus')
                    ->sortable(),
            ])
            ->recordActions([
                EditAction::make()->modal(),
                DeleteAction::make()
                    ->before(function (DeleteAction $action, Category $record) {
                        if ($record->menus()->exists()) {
                            Notification::make()
                                ->danger()
                                ->title('Kategori tidak dapat dihapus')
                                ->body("Kategori '{$record->name}' masih memiliki {$record->menus()->count()} menu. Pindahkan atau hapus menu terlebih dahulu.")
                                ->send();

                            $action->cancel();
                        }
                    }),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => ListCategories::route('/'),
        ];
    }
}
