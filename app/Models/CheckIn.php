<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CheckIn extends Model
{
    protected $fillable = [
        'user_id',
        'cafe_id',
        'is_verified',
        'user_lat',
        'user_lng',
    ];

    protected function casts(): array
    {
        return [
            'is_verified' => 'boolean',
            'user_lat' => 'decimal:8',
            'user_lng' => 'decimal:8',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function cafe(): BelongsTo
    {
        return $this->belongsTo(Cafe::class);
    }
}
