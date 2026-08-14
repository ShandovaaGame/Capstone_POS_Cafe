<?php

namespace App\Filament\Resources\MenuResource\Pages;

use App\Filament\Resources\MenuResource;
use Filament\Resources\Pages\CreateRecord;
use Illuminate\Support\Facades\Cache;

class CreateMenu extends CreateRecord
{
    protected static string $resource = MenuResource::class;

    protected function afterCreate(): void
    {
        Cache::forget('customer_menu_v1');
        Cache::forget('menu_categories_active');
    }
}
