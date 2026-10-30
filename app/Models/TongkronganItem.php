<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class TongkronganItem extends Model
{
    protected $fillable = [
        'tongkrongan_id',
        'cafe_id',
    ];

    public function tongkrongan(): BelongsTo
    {
        return $this->belongsTo(Tongkrongan::class);
    }

    public function cafe(): BelongsTo
    {
        return $this->belongsTo(Cafe::class);
    }

    public function votes(): HasMany
    {
        return $this->hasMany(TongkronganVote::class);
    }

    /**
     * Get vote count for this item.
     */
    public function getVoteCountAttribute(): int
    {
        return $this->votes()->count();
    }
}
