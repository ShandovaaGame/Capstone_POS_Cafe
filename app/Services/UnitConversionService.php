<?php

namespace App\Services;

use App\Models\Unit;
use Illuminate\Support\Collection;
use InvalidArgumentException;

class UnitConversionService
{
    public function convert(float $amount, Unit $from, Unit $to): float
    {
        if ($from->id === $to->id) return $amount;
        if ($from->unit_type !== $to->unit_type) {
            throw new InvalidArgumentException("Cannot convert {$from->unit_type} to {$to->unit_type}");
        }
        return round($amount * $from->conversion_factor / $to->conversion_factor, 6);
    }

    public function getCompatibleUnits(Unit $unit): Collection
    {
        return Unit::where('unit_type', $unit->unit_type)->get();
    }
}
