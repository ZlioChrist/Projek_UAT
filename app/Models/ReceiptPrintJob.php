<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ReceiptPrintJob extends Model
{
    protected $fillable = [
        'order_id', 'kind', 'state', 'dedupe_key', 'payload', 'attempts',
        'last_error', 'requested_by', 'printed_at', 'reported_by', 'reported_at',
    ];

    protected function casts(): array
    {
        return [
            'payload' => 'array',
            'attempts' => 'integer',
            'printed_at' => 'datetime',
            'reported_at' => 'datetime',
        ];
    }

    public function order(): BelongsTo
    {
        return $this->belongsTo(Order::class);
    }

    public function requester(): BelongsTo
    {
        return $this->belongsTo(User::class, 'requested_by');
    }
}
