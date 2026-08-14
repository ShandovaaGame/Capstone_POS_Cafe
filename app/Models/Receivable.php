<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Facades\Auth;

class Receivable extends Model
{
    public const STATUS_PENDING = 'pending';

    public const STATUS_PARTIAL = 'partial';

    public const STATUS_PAID = 'paid';

    public const STATUS_OVERDUE = 'overdue';

    public const STATUS_CANCELLED = 'cancelled';

    public const STATUSES = [
        self::STATUS_PENDING,
        self::STATUS_PARTIAL,
        self::STATUS_PAID,
        self::STATUS_OVERDUE,
        self::STATUS_CANCELLED,
    ];

    protected $fillable = [
        'customer_name',
        'amount',
        'invoice_date',
        'due_date',
        'status',
        'paid_amount',
        'notes',
        'order_id',
    ];

    protected function casts(): array
    {
        return [
            'invoice_date' => 'date',
            'due_date' => 'date',
            'amount' => 'integer',
            'paid_amount' => 'integer',
        ];
    }

    public function getRemainingAmountAttribute(): float
    {
        return max(0, (float) $this->amount - (float) $this->paid_amount);
    }

    public function isOverdue(): bool
    {
        return $this->status !== self::STATUS_PAID
            && $this->due_date !== null
            && $this->due_date->lessThan(now());
    }

    public function recordPayment(float $amount, ?string $method = null, ?int $recordedBy = null, ?string $notes = null): void
    {
        if ($this->status === 'paid') {
            throw new \RuntimeException('Receivable sudah lunas');
        }
        $remaining = $this->amount - $this->paid_amount;
        if ($amount <= 0 || $amount > $remaining) {
            throw new \InvalidArgumentException('Jumlah pembayaran tidak valid');
        }
        $this->payments()->create([
            'amount' => $amount,
            'payment_date' => now(),
            'payment_method' => $method,
            'notes' => $notes,
            'recorded_by' => $recordedBy ?? Auth::id(),
        ]);
        $this->increment('paid_amount', $amount);
        if ((float)$this->paid_amount >= (float)$this->amount) {
            $this->status = 'paid';
        } elseif ($this->paid_amount > 0) {
            $this->status = 'partial';
        }
        $this->save();
    }

    /**
     * Scope to filter overdue receivables.
     */
    public function scopeOverdue(Builder $query): void
    {
        $query->where('status', '!=', self::STATUS_PAID)
            ->whereNotNull('due_date')
            ->where('due_date', '<', now());
    }

    /**
     * Scope to filter pending receivables.
     */
    public function scopePending(Builder $query): void
    {
        $query->where('status', self::STATUS_PENDING);
    }

    /**
     * Scope to filter paid receivables.
     */
    public function scopePaid(Builder $query): void
    {
        $query->where('status', self::STATUS_PAID);
    }

    public function order()
    {
        return $this->belongsTo(Order::class);
    }

    public function payments(): HasMany
    {
        return $this->hasMany(ReceivablePayment::class);
    }

    public function cancel(?string $reason = null): void
    {
        if (in_array($this->status, ['paid', 'cancelled'])) {
            throw new \RuntimeException('Receivable tidak dapat dibatalkan');
        }
        $this->status = self::STATUS_CANCELLED;
        $this->notes = $reason ? trim($this->notes."\n[Dibatalkan: {$reason}]") : $this->notes;
        $this->save();
    }
}
