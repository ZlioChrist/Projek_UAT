<?php

namespace App\Models;

use App\Services\CafeSearchService;
use App\Traits\HasOperatingHours;
use Database\Factories\RoasteryFactory;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

class Roastery extends Model
{
    /** @use HasFactory<RoasteryFactory> */
    use HasFactory, HasOperatingHours, SoftDeletes;

    protected $fillable = [
        'name',
        'slug',
        'city_id',
        'description',
        'address',
        'google_maps_url',
        'whatsapp_number',
        'image_path',
        'images',
        'menu_images',
        'latitude',
        'longitude',
        'social_links',
        'owner_id',
        'status',
        'is_24_hours',
        'operating_hours',
        'weekday_open',
        'weekday_close',
        'weekend_open',
        'weekend_close',
        'official_is_open',
        'official_status_text',
        'official_status_checked_at',
        'payment_qr_path',
        'payment_instructions',
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
     * Get the city that the roastery belongs to.
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
     * Get the owner of the roastery.
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
     * Scope query to find roasteries that are currently open.
     */
    public function scopeOpenNow($query): Builder
    {
        return app(CafeSearchService::class)->scopeOpenNow($query);
    }

    /**
     * Scope a query to only include roasteries near a location.
     */
    public function scopeNearest($query, $lat, $lng): Builder
    {
        return app(CafeSearchService::class)->scopeNearest($query, $lat, $lng);
    }

    /**
     * Scope a query to search roasteries by term.
     */
    public function scopeSearch($query, $term): void
    {
        app(CafeSearchService::class)->scopeSearch($query, $term);
    }

    /**
     * Set the whatsapp_number attribute.
     */
    public function setWhatsappNumberAttribute($value): void
    {
        if (empty($value)) {
            $this->attributes['whatsapp_number'] = null;

            return;
        }

        // Remove non-numeric characters
        $value = preg_replace('/[^0-9]/', '', $value);

        // Normalize Indonesian numbers
        if (str_starts_with($value, '0')) {
            $value = '62'.substr($value, 1);
        } elseif (! str_starts_with($value, '62')) {
            $value = '62'.$value;
        }

        $this->attributes['whatsapp_number'] = $value;
    }

    /**
     * Get the attributes that should be cast.
     */
    protected function casts(): array
    {
        return [
            'images' => 'array',
            'menu_images' => 'array',
            'social_links' => 'array',
            'latitude' => 'decimal:8',
            'longitude' => 'decimal:8',
            'operating_hours' => 'array',
            'is_24_hours' => 'boolean',
            'weekday_open' => 'string',
            'weekday_close' => 'string',
            'weekend_open' => 'string',
            'weekend_close' => 'string',
            'official_is_open' => 'boolean',
            'official_status_checked_at' => 'datetime',
        ];
    }

    /**
     * The "booted" method of the model.
     */
    protected static function booted(): void
    {
        static::creating(function ($roastery) {
            if (empty($roastery->slug)) {
                $roastery->slug = static::generateUniqueSlug($roastery->name);
            }
        });

        static::saving(function ($roastery) {
            // Sync operating_hours JSON to dedicated columns for performance query scope
            $hours = $roastery->operating_hours ?? [];
            $weekdaySchedule = $hours['weekday'] ?? static::sharedSchedule(
                $hours,
                ['monday', 'tuesday', 'wednesday', 'thursday', 'friday'],
            );
            $weekendSchedule = $hours['weekend'] ?? static::sharedSchedule($hours, ['saturday', 'sunday']);
            $roastery->weekday_open = $weekdaySchedule['open'] ?? null;
            $roastery->weekday_close = $weekdaySchedule['close'] ?? null;
            $roastery->weekend_open = $weekendSchedule['open'] ?? null;
            $roastery->weekend_close = $weekendSchedule['close'] ?? null;

            // Sync Spatial Location (POINT) for optimized proximity search
            // Wrapped in try-catch: MariaDB / MySQL 5.7 may not support SRID 4326
            if (($roastery->isDirty(['latitude', 'longitude']) || ! $roastery->location)) {
                try {
                    $lat = (float) ($roastery->latitude ?: 0);
                    $lng = (float) ($roastery->longitude ?: 0);

                    // Try MySQL 8 syntax first (with SRID)
                    $roastery->location = DB::raw("ST_GeomFromText('POINT($lat $lng)', 4326)");
                } catch (\Throwable $e) {
                    // MariaDB/MySQL 5.7 fallback: skip spatial sync
                    // The Haversine fallback in CafeSearchService will use lat/lng columns directly
                }
            }
        });

        static::saved(function ($roastery) {
            // Invalidate cache
            Cache::forget("roastery_{$roastery->slug}");
        });

        static::updating(function ($roastery) {
            if ($roastery->isDirty('name') && ! $roastery->isDirty('slug')) {
                $roastery->slug = static::generateUniqueSlug($roastery->name);
            }
        });
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
