<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class MenuItem extends Model
{
    use HasFactory;

    public const DEFAULT_INITIAL_STOCK = 100;

    protected $table = 'products';

    protected $fillable = [
        'cafe_id', 'roastery_id', 'name', 'category', 'description', 'image_path', 'price', 'stock', 'reserved_stock', 'is_active',
    ];

    protected function casts(): array
    {
        return [
            'price' => 'decimal:2',
            'stock' => 'integer',
            'reserved_stock' => 'integer',
            'is_active' => 'boolean',
        ];
    }

    protected static function booted(): void
    {
        static::creating(function (MenuItem $menuItem): void {
            if ($menuItem->stock === null) {
                $menuItem->stock = self::DEFAULT_INITIAL_STOCK;
            }
        });

        static::saving(function (MenuItem $menuItem): void {
            if ((int) $menuItem->reserved_stock < 0) {
                throw ValidationException::withMessages([
                    'reserved_stock' => 'Jumlah stok yang direservasi tidak boleh negatif.',
                ]);
            }

            if ((int) $menuItem->stock < (int) $menuItem->reserved_stock) {
                throw ValidationException::withMessages([
                    'stock' => 'Stok fisik tidak boleh lebih sedikit daripada stok yang sedang dipesan.',
                ]);
            }

            $stockIncreased = (int) $menuItem->stock > (int) $menuItem->getOriginal('stock');

            if ($menuItem->exists && $menuItem->isDirty('stock') && $stockIncreased && $menuItem->available_stock > 0) {
                $menuItem->is_active = true;
            }
        });
    }

    public function cafe(): BelongsTo
    {
        return $this->belongsTo(Cafe::class);
    }

    public function roastery(): BelongsTo
    {
        return $this->belongsTo(Roastery::class);
    }

    public function orderItems(): HasMany
    {
        return $this->hasMany(OrderItem::class, 'product_id');
    }

    public function addons(): BelongsToMany
    {
        return $this->belongsToMany(self::class, 'product_addons', 'parent_product_id', 'addon_product_id')
            ->withTimestamps();
    }

    public function addonFor(): BelongsToMany
    {
        return $this->belongsToMany(self::class, 'product_addons', 'addon_product_id', 'parent_product_id')
            ->withTimestamps();
    }

    public function getAddonNameAttribute(): string
    {
        $name = preg_replace('/^croffle\s+add[- ]on:\s*/i', '', $this->name);
        $name = preg_replace('/^add\s+on\s*:\s*/i', '', $name);
        $name = preg_replace('/^(?:add|additional|tambah(?:an)?)\s+/i', '', $name);

        return Str::title(trim($name));
    }

    public function getAvailableStockAttribute(): int
    {
        return max(0, (int) $this->stock - (int) $this->reserved_stock);
    }
}
