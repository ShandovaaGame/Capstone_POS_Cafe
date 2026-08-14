<?php

namespace App\Filament\Helpers;

class NumberInputHelper
{
    public static function decimal(): array
    {
        return ['inputmode' => 'decimal'];
    }

    public static function integer(): array
    {
        return ['inputmode' => 'numeric'];
    }
}
