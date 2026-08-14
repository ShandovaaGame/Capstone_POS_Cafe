<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ReceivablePayment extends Model
{
    protected $fillable = ['receivable_id', 'amount', 'payment_date', 'payment_method', 'notes', 'recorded_by'];

    protected function casts(): array
    {
        return [
            'payment_date' => 'datetime',
        ];
    }

    public function receivable(): BelongsTo
    {
        return $this->belongsTo(Receivable::class);
    }

    public function recordedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'recorded_by');
    }
}
