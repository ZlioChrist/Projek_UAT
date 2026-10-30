<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class VibeVote extends Model
{
    protected $fillable = [
        'cafe_id',
        'user_id',
        'level',
        'is_verified',
        'user_lat',
        'user_lng',
        'fingerprint',
    ];

    protected function casts(): array
    {
        return [
            'is_verified' => 'boolean',
            'user_lat' => 'decimal:8',
            'user_lng' => 'decimal:8',
        ];
    }

    public function cafe(): BelongsTo
    {
        return $this->belongsTo(Cafe::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Scope: only votes from the last N hours (data decay).
     */
    public function scopeRecent($query, int $hours = 4)
    {
        return $query->where('created_at', '>=', now()->subHours($hours));
    }
}
