<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Unit extends Model
{
    protected $fillable = ["name", "abbreviation", "unit_type", "base_unit_id", "conversion_factor"];

    public function baseUnit(): BelongsTo
    {
        return $this->belongsTo(self::class, "base_unit_id");
    }

    public function subUnits(): HasMany
    {
        return $this->hasMany(self::class, "base_unit_id");
    }
}
