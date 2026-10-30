<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CustomerFeedback extends Model
{
    protected $table = 'customer_feedback';

    protected $fillable = [
        'cafe_id',
        'roastery_id',
        'category',
        'customer_name',
        'customer_contact',
        'message',
        'status',
        'admin_note',
    ];

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
}
