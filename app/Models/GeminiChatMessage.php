<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class GeminiChatMessage extends Model
{
    protected $fillable = [
        'role',
        'content',
        'image_path',
        'image_mime_type',
    ];

    public function conversation(): BelongsTo
    {
        return $this->belongsTo(GeminiChatConversation::class, 'conversation_id');
    }
}
