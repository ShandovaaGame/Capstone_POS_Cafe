<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class StaffSession extends Model
{
    protected $fillable = [
        'user_id',
        'type',
        'session_id',
        'started_at',
        'ended_at',
        'last_activity_at',
        'is_active',
    ];

    protected $casts = [
        'started_at' => 'datetime',
        'ended_at' => 'datetime',
        'last_activity_at' => 'datetime',
        'is_active' => 'boolean',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    public function scopeForUser($query, $userId)
    {
        return $query->where('user_id', $userId);
    }

    public function scopeType($query, string $type)
    {
        return $query->where('type', $type);
    }

    public function scopeCashier($query)
    {
        return $query->where('type', 'cashier');
    }

    public function scopeKitchen($query)
    {
        return $query->where('type', 'kitchen');
    }
}
