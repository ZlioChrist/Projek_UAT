<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Str;

class Tongkrongan extends Model
{
    protected $fillable = [
        'uuid',
        'title',
        'creator_fingerprint',
        'creator_user_id',
        'expires_at',
    ];

    protected function casts(): array
    {
        return [
            'expires_at' => 'datetime',
        ];
    }

    protected static function booted(): void
    {
        static::creating(function ($tongkrongan) {
            if (empty($tongkrongan->uuid)) {
                $tongkrongan->uuid = Str::uuid()->toString();
            }
            if (empty($tongkrongan->expires_at)) {
                $tongkrongan->expires_at = now()->addHours(24);
            }
        });
    }

    public function items(): HasMany
    {
        return $this->hasMany(TongkronganItem::class);
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'creator_user_id');
    }

    /**
     * Scope: only active (not expired) tongkrongans.
     */
    public function scopeActive($query)
    {
        return $query->where('expires_at', '>', now());
    }

    /**
     * Check if this tongkrongan has expired.
     */
    public function getIsExpiredAttribute(): bool
    {
        return $this->expires_at->isPast();
    }

    /**
     * Get the shareable URL.
     */
    public function getShareUrlAttribute(): string
    {
        return url("/tongkrongan/{$this->uuid}");
    }

    /**
     * Get the route key for the model.
     */
    public function getRouteKeyName(): string
    {
        return 'uuid';
    }
}
