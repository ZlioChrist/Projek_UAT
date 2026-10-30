<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Order extends Model
{
    public const STATUS_AWAITING_PAYMENT = 'awaiting_payment';

    public const STATUS_PENDING_VERIFICATION = 'pending_verification';

    public const STATUS_PAID = 'paid';

    public const STATUS_PREPARING = 'preparing';

    public const STATUS_READY = 'ready';

    public const STATUS_COMPLETED = 'completed';

    public const STATUS_CANCELLED = 'cancelled';

    public const STATUSES = [
        self::STATUS_AWAITING_PAYMENT,
        self::STATUS_PENDING_VERIFICATION,
        self::STATUS_PAID,
        self::STATUS_PREPARING,
        self::STATUS_READY,
        self::STATUS_COMPLETED,
        self::STATUS_CANCELLED,
    ];

    protected $fillable = [
        'cafe_id', 'user_id', 'public_code', 'idempotency_key', 'status', 'payment_status',
        'roastery_id', 'payment_method',
        'customer_name', 'customer_whatsapp', 'customer_note', 'subtotal', 'total',
        'paid_at', 'preparing_at', 'ready_at', 'completed_at', 'cancelled_at',
    ];

    protected function casts(): array
    {
        return [
            'subtotal' => 'decimal:2',
            'total' => 'decimal:2',
            'paid_at' => 'datetime',
            'preparing_at' => 'datetime',
            'ready_at' => 'datetime',
            'completed_at' => 'datetime',
            'cancelled_at' => 'datetime',
        ];
    }

    public function cafe(): BelongsTo
    {
        return $this->belongsTo(Cafe::class);
    }

    public function roastery(): BelongsTo
    {
        return $this->belongsTo(Roastery::class);
    }

    public function getShopAttribute(): Cafe|Roastery|null
    {
        return $this->cafe ?? $this->roastery;
    }

    public function getShopNameAttribute(): string
    {
        return $this->shop?->name ?? 'Kedai';
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function items(): HasMany
    {
        return $this->hasMany(OrderItem::class);
    }

    public function payments(): HasMany
    {
        return $this->hasMany(OrderPayment::class);
    }

    public function statusHistories(): HasMany
    {
        return $this->hasMany(OrderStatusHistory::class)->latest();
    }

    public function printJobs(): HasMany
    {
        return $this->hasMany(ReceiptPrintJob::class)->latest();
    }

    public function getStatusLabelAttribute(): string
    {
        return match ($this->status) {
            self::STATUS_AWAITING_PAYMENT => 'Menunggu Pembayaran',
            self::STATUS_PENDING_VERIFICATION => 'Menunggu Konfirmasi Kedai',
            self::STATUS_PAID => 'Lunas',
            self::STATUS_PREPARING => 'Sedang Disiapkan',
            self::STATUS_READY => 'Siap Diambil',
            self::STATUS_COMPLETED => 'Selesai',
            self::STATUS_CANCELLED => 'Dibatalkan',
            default => 'Status Tidak Dikenal',
        };
    }

    public function canBeCancelled(): bool
    {
        return in_array($this->status, [self::STATUS_AWAITING_PAYMENT, self::STATUS_PENDING_VERIFICATION], true);
    }
}
