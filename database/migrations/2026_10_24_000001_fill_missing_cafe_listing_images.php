<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    private const IMAGES = [
        'kopikumana-1' => 'https://images.unsplash.com/photo-1453614512568-c4024d13c247?auto=format&fit=crop&q=80&w=800',
        'pot-o-koffie-1' => 'https://images.unsplash.com/photo-1521017432531-fbd92d768814?auto=format&fit=crop&q=80&w=800',
    ];

    public function up(): void
    {
        DB::transaction(function (): void {
            foreach (self::IMAGES as $slug => $imagePath) {
                $updated = DB::table('cafes')
                    ->where('slug', $slug)
                    ->whereNull('image_path')
                    ->update(['image_path' => $imagePath]);

                if ($updated > 0) {
                    Cache::forget("cafe_{$slug}");
                }
            }
        });
    }

    public function down(): void
    {
        DB::transaction(function (): void {
            foreach (self::IMAGES as $slug => $imagePath) {
                $updated = DB::table('cafes')
                    ->where('slug', $slug)
                    ->where('image_path', $imagePath)
                    ->update(['image_path' => null]);

                if ($updated > 0) {
                    Cache::forget("cafe_{$slug}");
                }
            }
        });
    }
};
