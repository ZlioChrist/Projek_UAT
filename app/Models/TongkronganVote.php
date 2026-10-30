<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class TongkronganVote extends Model
{
    protected $fillable = [
        'tongkrongan_item_id',
        'voter_fingerprint',
        'voter_user_id',
    ];

    public function item(): BelongsTo
    {
        return $this->belongsTo(TongkronganItem::class, 'tongkrongan_item_id');
    }

    public function voter(): BelongsTo
    {
        return $this->belongsTo(User::class, 'voter_user_id');
    }
}
