<?php

namespace App\Observers;

use App\Models\MenuIngredient;

class MenuIngredientObserver
{
    public function created(MenuIngredient $menuIngredient): void {}
    public function updated(MenuIngredient $menuIngredient): void {}
    public function deleted(MenuIngredient $menuIngredient): void {}
}
