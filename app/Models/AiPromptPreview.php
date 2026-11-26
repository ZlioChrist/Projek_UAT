<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AiPromptPreview extends Model
{
    protected $fillable = [
        'user_id',
        'prompt_key',
        'image_path',
        'image_mime_type',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
