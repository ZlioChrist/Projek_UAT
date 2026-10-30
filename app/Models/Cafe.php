<?php

namespace App\Models;

use App\Services\CafeSearchService;
use App\Traits\HasOperatingHours;
use Database\Factories\CafeFactory;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

class Cafe extends Model
{
    /** @use HasFactory<CafeFactory> */
    use HasFactory, HasOperatingHours, SoftDeletes;

    protected $fillable = [
        'name',
        'slug',
        'city_id',
        'description',
        'address',
        'google_maps_url',
        'whatsapp_number',
        'has_wifi',
        'image_path',
        'images',
        'latitude',
        'longitude',
        // 'opening_time', // DEPRECATED: Use operating_hours
        // 'closing_time', // DEPRECATED: Use operating_hours
        'is_24_hours',
        'operating_hours',
        'owner_id',
        'status',
        'social_links',
        'menu_images',
        'payment_qr_path',
        'payment_instructions',
        'wfc_avg_score',
        'wfc_review_count',
        'current_vibe',
        'vibe_updated_at',
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var array<int, string>
     */
    protected $hidden = [
        'location',
    ];

    /**
     * Exclude binary location from serialized output to prevent
     * issues with database cache drivers (e.g. MySQL utf8mb4 constraints).
     */

    /**
     * Get the city that the cafe belongs to.
     */
    public function city(): BelongsTo
    {
        return $this->belongsTo(City::class);
    }

    private static function sharedSchedule(array $hours, array $days): ?array
    {
        $schedules = array_map(fn (string $day) => $hours[$day] ?? null, $days);
        if (in_array(null, $schedules, true) || count(array_unique(array_map('serialize', $schedules))) !== 1) {
            return null;
        }

        return $schedules[0];
    }

    /**
     * Get the owner of the cafe.
     */
    public function owner(): BelongsTo
    {
        return $this->belongsTo(User::class, 'owner_id');
    }

    public function menuItems(): HasMany
    {
        return $this->hasMany(MenuItem::class);
    }

    public function orders(): HasMany
    {
        return $this->hasMany(Order::class);
    }

    public function feedback(): HasMany
    {
        return $this->hasMany(CustomerFeedback::class);
    }

    /**
     * The facilities that belong to the cafe.
     */
    public function facilities(): HasMany
    {
        return $this->hasMany(Facility::class);
    }

    /**
     * The WFC scores that belong to the cafe.
     */
    public function wfcScores(): HasMany
    {
        return $this->hasMany(WfcScore::class);
    }

    /**
     * The vibe votes that belong to the cafe.
     */
    public function vibeVotes(): HasMany
    {
        return $this->hasMany(VibeVote::class);
    }

    /**
     * The check-ins for this cafe.
     */
    public function checkIns(): HasMany
    {
        return $this->hasMany(CheckIn::class);
    }

    /**
     * Scope a query to only include open cafes.
     */
    public function scopeOpenNow($query): Builder
    {
        return app(CafeSearchService::class)->scopeOpenNow($query);
    }

    /**
     * Scope a query to only include cafes near a location.
     */
    public function scopeNearest($query, $lat, $lng): Builder
    {
        return app(CafeSearchService::class)->scopeNearest($query, $lat, $lng);
    }

    /**
     * Scope a query to search cafes by term.
     */
    public function scopeSearch($query, $term): void
    {
        app(CafeSearchService::class)->scopeSearch($query, $term);
    }

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'images' => 'array',
            'menu_images' => 'array',
            'social_links' => 'array',
            'operating_hours' => 'array',
            'has_wifi' => 'boolean',
            'is_24_hours' => 'boolean',
            'official_is_open' => 'boolean',
            'official_status_checked_at' => 'datetime',
            'latitude' => 'decimal:8',
            'longitude' => 'decimal:8',
            'weekday_open' => 'string',
            'weekday_close' => 'string',
            'weekend_open' => 'string',
            'weekend_close' => 'string',
        ];
    }

    /**
     * The "booted" method of the model.
     */
    protected static function booted(): void
    {
        static::creating(function ($cafe) {
            if (empty($cafe->slug)) {
                $cafe->slug = static::generateUniqueSlug($cafe->name);
            }
        });

        static::saving(function ($cafe) {
            // Validate Operating Hours JSON Structure to prevent "Invalid JSON" or logic errors
            if ($cafe->isDirty('operating_hours') && ! empty($cafe->operating_hours)) {
                $hours = $cafe->operating_hours;
                $days = [
                    'weekday',
                    'weekend',
                    'monday',
                    'tuesday',
                    'wednesday',
                    'thursday',
                    'friday',
                    'saturday',
                    'sunday',
                ];

                foreach ($days as $day) {
                    if (isset($hours[$day])) {
                        // Ensure open/close are present if one is set
                        $open = $hours[$day]['open'] ?? null;
                        $close = $hours[$day]['close'] ?? null;

                        if (($open && ! $close) || (! $open && $close)) {
                            // Recover: If incomplete, unset the day to avoid broken state
                            unset($hours[$day]);
                        }

                        // Normalize "24:00" to "00:00" if user inputs it manually (though frontend should handle it)
                        if ($open === '24:00') {
                            $hours[$day]['open'] = '00:00';
                        }
                        if ($close === '24:00') {
                            $hours[$day]['close'] = '00:00';
                        }
                    }
                }

                $cafe->operating_hours = $hours;
            }

            // Keep indexed TIME columns synchronized in application code. MariaDB
            // versions bundled with XAMPP do not reliably index JSON-generated TIME columns.
            $hours = $cafe->operating_hours ?? [];
            $weekdaySchedule = $hours['weekday'] ?? static::sharedSchedule(
                $hours,
                ['monday', 'tuesday', 'wednesday', 'thursday', 'friday'],
            );
            $weekendSchedule = $hours['weekend'] ?? static::sharedSchedule($hours, ['saturday', 'sunday']);
            $cafe->weekday_open = $weekdaySchedule['open'] ?? null;
            $cafe->weekday_close = $weekdaySchedule['close'] ?? null;
            $cafe->weekend_open = $weekendSchedule['open'] ?? null;
            $cafe->weekend_close = $weekendSchedule['close'] ?? null;

            // Sync Spatial Location (POINT) for optimized proximity search
            // Wrapped in try-catch: MariaDB / MySQL 5.7 may not support SRID 4326
            if (($cafe->isDirty(['latitude', 'longitude']) || ! $cafe->location)) {
                try {
                    $lat = (float) ($cafe->latitude ?: 0);
                    $lng = (float) ($cafe->longitude ?: 0);

                    // Try MySQL 8 syntax first (with SRID)
                    $cafe->location = DB::raw("ST_GeomFromText('POINT($lat $lng)', 4326)");
                } catch (\Throwable $e) {
                    // MariaDB/MySQL 5.7 fallback: skip spatial sync
                    // The Haversine fallback in CafeSearchService will use lat/lng columns directly
                }
            }
        });

        static::saved(function ($cafe) {
            // Invalidate cache when cafe is updated
            Cache::forget("cafe_{$cafe->slug}");
        });

        static::updating(function ($cafe) {
            if ($cafe->isDirty('name') && ! $cafe->isDirty('slug')) {
                $cafe->slug = static::generateUniqueSlug($cafe->name);
            }
        });
    }

    /**
     * Mutator: Automatically format WhatsApp number to 62xxx
     * Ensures wa.me links work correctly.
     */
    public function setWhatsappNumberAttribute($value): void
    {
        if (empty($value)) {
            $this->attributes['whatsapp_number'] = null;

            return;
        }

        // Remove non-numeric characters (spaces, dashes, etc.)
        $clean = preg_replace('/[^0-9]/', '', $value);

        // Normalize 08xxx -> 628xxx
        if (str_starts_with($clean, '0')) {
            $clean = '62'.substr($clean, 1);
        } elseif (! str_starts_with($clean, '62')) {
            $clean = '62'.$clean;
        }

        $this->attributes['whatsapp_number'] = $clean;
    }

    /**
     * Get the route key for the model.
     */
    public function getRouteKeyName(): string
    {
        return 'slug';
    }

    /**
     * Accessor: Clean and process ambience images.
     */
    public function getProcessedImagesAttribute(): array
    {
        return $this->processImagesList(collect([$this->image_path])->merge($this->images ?? [])->all());
    }

    /**
     * Accessor: Clean and process menu images.
     */
    public function getProcessedMenuImagesAttribute(): array
    {
        return $this->processImagesList($this->menu_images ?? []);
    }

    /**
     * Helper to clean image paths.
     */
    protected function processImagesList(array $list): array
    {
        return collect($list)
            ->filter()
            ->map(function ($img) {
                if (empty($img)) {
                    return null;
                }
                $cleanImg = preg_replace('/[\x00-\x1F\x7F\xA0\s]+/', '', $img);
                if (str_starts_with($cleanImg, 'http')) {
                    return $cleanImg;
                }

                return '/storage/'.$cleanImg;
            })
            ->filter()
            ->values()
            ->all() ?: ['https://images.unsplash.com/photo-1559056199-641a0ac8b55e?auto=format&fit=crop&q=80&w=1200'];
    }
}
