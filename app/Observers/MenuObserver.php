<?php

namespace App\Observers;

use App\Models\Menu;
use Illuminate\Support\Facades\Cache;

class MenuObserver
{
    public function saved(Menu $menu): void
    {
        Cache::forget('customer_menu_v1');
        Cache::forget('menu_categories_cashier');
    }

    public function deleting(Menu $menu): void {}
    public function restored(Menu $menu): void {}
}
