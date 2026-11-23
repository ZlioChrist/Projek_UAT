<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

class Order extends Model
{
    public const STATUS_AWAITING_PAYMENT = 'awaiting_payment';

    public const STATUS_PENDING_VERIFICATION = 'pending_verification';

    public const STATUS_PAID = 'paid';

    public const STATUS_PREPARING = 'preparing';

    public const STATUS_READY = 'ready';

    public const STATUS_DELIVERING = 'delivering';

    public const STATUS_AWAITING_RECEIPT_CONFIRMATION = 'awaiting_receipt_confirmation';

    public const STATUS_COMPLETED = 'completed';

    public const STATUS_CANCELLED = 'cancelled';

    public const STATUSES = [
        self::STATUS_AWAITING_PAYMENT,
        self::STATUS_PENDING_VERIFICATION,
        self::STATUS_PAID,
        self::STATUS_PREPARING,
        self::STATUS_READY,
        self::STATUS_DELIVERING,
        self::STATUS_AWAITING_RECEIPT_CONFIRMATION,
        self::STATUS_COMPLETED,
        self::STATUS_CANCELLED,
    ];

    protected $fillable = [
        'cafe_id', 'user_id', 'public_code', 'display_code', 'idempotency_key', 'status', 'payment_status',
        'roastery_id', 'payment_method', 'sender_bank', 'fulfillment_method',
        'delivery_address', 'delivery_distance_km', 'delivery_fee', 'delivery_fee_confirmed_at', 'delivery_started_at',
        'receipt_confirmation_deadline_started_at',
        'customer_name', 'customer_whatsapp', 'customer_note', 'subtotal', 'total',
        'payment_expires_at', 'paid_at', 'preparing_at', 'ready_at', 'completed_at', 'cancelled_at',
    ];

    protected function casts(): array
    {
        return [
            'subtotal' => 'decimal:2',
            'total' => 'decimal:2',
            'payment_expires_at' => 'datetime',
            'paid_at' => 'datetime',
            'preparing_at' => 'datetime',
            'ready_at' => 'datetime',
            'delivery_fee' => 'decimal:2',
            'delivery_distance_km' => 'decimal:2',
            'delivery_fee_confirmed_at' => 'datetime',
            'delivery_started_at' => 'datetime',
            'receipt_confirmation_deadline_started_at' => 'datetime',
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

    public function feedback(): HasOne
    {
        return $this->hasOne(CustomerFeedback::class);
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
            self::STATUS_READY => $this->fulfillment_method === 'delivery' ? 'Siap Diantar' : 'Siap Diambil',
            self::STATUS_DELIVERING => 'Dalam Pengantaran',
            self::STATUS_AWAITING_RECEIPT_CONFIRMATION => 'Menunggu Konfirmasi Diterima',
            self::STATUS_COMPLETED => 'Selesai',
            self::STATUS_CANCELLED => 'Dibatalkan',
            default => 'Status Tidak Dikenal',
        };
    }

    public function canBeCancelled(): bool
    {
        return in_array($this->status, [self::STATUS_AWAITING_PAYMENT, self::STATUS_PENDING_VERIFICATION], true);
    }

    public function canBeCancelledByCustomer(): bool
    {
        return $this->status === self::STATUS_AWAITING_PAYMENT
            && in_array($this->payment_status, ['unpaid', 'rejected'], true);
    }
}
