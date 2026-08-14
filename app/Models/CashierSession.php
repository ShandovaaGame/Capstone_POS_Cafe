<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

class CashierSession extends Model
{
    protected $table = 'staff_sessions';

    protected $fillable = [
        'user_id',
        'type',
        'session_id',
        'started_at',
        'ended_at',
        'last_activity_at',
        'is_active',
    ];

    protected function casts(): array
    {
        return [
            'started_at'       => 'datetime',
            'ended_at'         => 'datetime',
            'last_activity_at' => 'datetime',
            'is_active'        => 'boolean',
        ];
    }

    protected static function booted(): void
    {
        static::addGlobalScope('cashier', fn (Builder $q) => $q->where('type', 'cashier'));
        static::creating(fn ($m) => $m->type = 'cashier');
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function isActive(): bool
    {
        return $this->is_active;
    }

    public function getDurationAttribute(): ?float
    {
        if (! $this->started_at || ! $this->ended_at) {
            return null;
        }

        return round($this->started_at->diffInMinutes($this->ended_at) / 60, 2);
    }

    public function scopeActive(Builder $query): Builder
    {
        return $query->where('is_active', true);
    }

    public function scopeToday(Builder $query): Builder
    {
        return $query->whereDate('started_at', today());
    }
}
