<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Information extends Model
{
    /** @use HasFactory<\Database\Factories\InformationFactory> */
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'title',
        'slug',
        'summary',
        'content',
        'category',
        'source_name',
        'image_path',
        'source_url',
        'is_published',
        'published_at',
        'views',
    ];

    protected $casts = [
        'is_published' => 'boolean',
        'published_at' => 'datetime',
    ];

    /**
     * Sanitize content automatically before saving.
     */
    protected function content(): Attribute
    {
        return Attribute::make(
            set: fn (string $value) => clean($value),
        );
    }

    /**
     * Get a clean short summary for preview.
     */
    protected function shortContent(): Attribute
    {
        return Attribute::make(
            get: function () {
                $text = strip_tags($this->content);
                $text = preg_replace('/\s+/', ' ', $text); // Remove multiple spaces/newlines
                return \Illuminate\Support\Str::limit(trim($text), 100);
            }
        );
    }
}
