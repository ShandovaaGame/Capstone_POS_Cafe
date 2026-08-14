<?php

namespace App\Models;

use Filament\Models\Contracts\FilamentUser;
use Filament\Panel;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

class User extends Authenticatable implements FilamentUser
{
    use HasFactory, Notifiable;

    public function canAccessPanel(Panel $panel): bool
    {
        return $this->role === 'admin';
    }

    protected $fillable = [
        'name',
        'email',
        'password',
        'role',
        'phone',
    ];

    protected $hidden = [
        'password',
        'remember_token',
    ];

    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
        ];
    }

    public function orders()
    {
        return $this->hasMany(Order::class, 'customer_id');
    }

    public function cashierOrders()
    {
        return $this->hasMany(Order::class, 'cashier_id');
    }

    public function processedOrders()
    {
        return $this->hasMany(Order::class, 'processed_by');
    }

    public function reportedStockAdjustments()
    {
        return $this->hasMany(StockAdjustment::class, 'reported_by');
    }

    public function stockMovements()
    {
        return $this->hasMany(StockMovement::class, 'recorded_by');
    }
}
