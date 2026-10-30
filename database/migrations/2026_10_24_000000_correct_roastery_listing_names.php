<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    private const NAMES = [
        'floorforfloor' => 'FloorForFloor',
        'konus-roastery' => 'Konus Roastery',
        'nature-coffee-roastery' => 'Nature Coffee & Roastery',
        'triton-coffee-roaster' => 'Triton Coffee Roaster',
        'kopi-samarinda' => 'Kopi Samarinda',
        'angata-roastery' => 'An.gata Roastery',
    ];

    public function up(): void
    {
        DB::transaction(function (): void {
            DB::table('roasteries')
                ->whereIn('slug', array_keys(self::NAMES))
                ->get(['slug', 'name'])
                ->each(function (object $roastery): void {
                    $decodedName = json_decode($roastery->name, true);

                    if (is_array($decodedName)) {
                        DB::table('roasteries')
                            ->where('slug', $roastery->slug)
                            ->update(['name' => self::NAMES[$roastery->slug]]);
                        Cache::forget("roastery_{$roastery->slug}");
                    }
                });
        });
    }

    public function down(): void
    {
        DB::transaction(function (): void {
            DB::table('roasteries')
                ->whereIn('slug', array_keys(self::NAMES))
                ->get(['slug', 'name', 'social_links'])
                ->each(function (object $roastery): void {
                    if (
                        $roastery->name === self::NAMES[$roastery->slug]
                        && $roastery->social_links !== null
                    ) {
                        DB::table('roasteries')
                            ->where('slug', $roastery->slug)
                            ->update(['name' => $roastery->social_links]);
                        Cache::forget("roastery_{$roastery->slug}");
                    }
                });
        });
    }
};
