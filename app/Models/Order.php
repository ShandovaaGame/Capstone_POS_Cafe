<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Order extends Model
{
    use HasFactory;

    const STATUS_PENDING = 'pending';

    const STATUS_DIPROSES = 'diproses';

    const STATUS_SELESAI = 'selesai';

    const STATUS_DIBATALKAN = 'dibatalkan';

    protected static function boot(): void
    {
        parent::boot();

        static::creating(function ($order) {
            $order->order_code ??= 'ORD-'.date('dmy').'-'.
                (Order::whereDate('created_at', today())->count() + 1);
        });
    }

    protected static function booted(): void
    {
        static::created(function (self $order) {
            if ($order->payment_method === 'bayar_nanti') {
                if (! $order->receivable()->exists()) {
                    Receivable::create([
                        'customer_name' => $order->customer_name ?? 'Event Customer',
                        'amount' => $order->total_amount ?? 0,
                        'invoice_date' => $order->created_at,
                        'due_date' => $order->created_at->copy()->addDays(30),
                        'status' => Receivable::STATUS_PENDING,
                        'paid_amount' => 0,
                        'order_id' => $order->id,
                        'notes' => "Auto-generated from Order #{$order->order_code}",
                    ]);
                }
            }
        });
    }

    protected $fillable = [
        'order_code',
        'customer_name',
        'customer_phone',
        'table_id',
        'cashier_id',
        'status',
        'order_type',
        'payment_method',
        'payment_proof',
        'rejection_note',
        'total_amount',
        'notes',
        'processed_by',
        'processed_at',
        'completed_at',
        'cancelled_at',
        'uuid',
        'resubmit_count',
        'qris_status',
        'whatsapp_phone',
    ];

    protected function casts(): array
    {
        return [
            'total_amount' => 'integer',
            'resubmit_count' => 'integer',
            'qris_status' => 'string',
            'processed_at' => 'datetime',
            'completed_at' => 'datetime',
            'cancelled_at' => 'datetime',
        ];
    }

    public function cafeTable()
    {
        return $this->belongsTo(CafeTable::class, 'table_id');
    }

    public function cashier()
    {
        return $this->belongsTo(User::class, 'cashier_id');
    }

    public function items()
    {
        return $this->hasMany(OrderItem::class);
    }

    public function appliedPromotions()
    {
        return $this->hasMany(AppliedPromotion::class);
    }

    public function receivable()
    {
        return $this->hasOne(Receivable::class);
    }

    public function processedBy()
    {
        return $this->belongsTo(User::class, 'processed_by');
    }

    public function isCashPending(): bool
    {
        return $this->status === self::STATUS_PENDING && $this->payment_method === 'cash';
    }

    public function isQrisPending(): bool
    {
        return $this->status === self::STATUS_PENDING && $this->payment_method === 'qris';
    }

    public function isActive(): bool
    {
        return $this->status !== self::STATUS_SELESAI && $this->status !== self::STATUS_DIBATALKAN;
    }

    /**
     * Jumlah pesanan pending yang perlu ditangani kasir.
     * Satu sumber kebenaran — dipakai badge sidebar, broadcast, & endpoint count.
     */
    public static function generateCode(): string
    {
        return 'ORD-'.date('dmy').'-'.(static::whereDate('created_at', today())->count() + 1);
    }

    public static function cashierPendingCount(): int
    {
        return static::where('status', self::STATUS_PENDING)
            ->where(fn ($q) => $q->where('order_type', 'cashier')
                ->orWhere(fn ($q2) => $q2->where('order_type', 'qr')
                    ->where(fn ($q3) => $q3->where('payment_method', 'cash')
                        ->orWhere(fn ($q4) => $q4->where('payment_method', 'qris')->whereNotNull('payment_proof'))
                    )
                )
            )->count();
    }

    public function scopeByUuid(Builder $query, string $uuid): Builder
    {
        return $query->where('uuid', $uuid);
    }

    public function isQrisResubmitable(): bool
    {
        return $this->resubmit_count < 3 && $this->qris_status === 'resubmit_requested';
    }

    public function getReceiptUrlAttribute(): string
    {
        return url('/struk-pesanan/' . $this->uuid);
    }
}
