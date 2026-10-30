<?php

namespace App\Http\Controllers;

use App\Models\Roastery;
use Illuminate\Support\Facades\Cache;
use Illuminate\View\View;

class RoasteryController extends Controller
{
    public function index(): View
    {
        return view('roastery.index');
    }

    public function show(Roastery $roastery): View
    {
        abort_if($roastery->status !== 'published', 404);

        // Cache per-roastery with slug-based key for 10 minutes
        // CRITICAL: Strip binary `location` (POINT) field before caching.
        // File/database cache drivers corrupt binary data during serialize().
        $roastery = Cache::remember("roastery_{$roastery->slug}", now()->addMinutes(10), function () use ($roastery) {
            $roastery->load('city');
            unset($roastery->location);

            return $roastery;
        });

        // Re-check status after cache load (handles unpublished-but-cached edge case)
        abort_if($roastery->status !== 'published', 404);

        $menuItems = $roastery->menuItems()
            ->where('is_active', true)
            ->whereColumn('stock', '>', 'reserved_stock')
            ->orderBy('category')
            ->orderBy('name')
            ->get();

        return view('roastery.show', compact('roastery', 'menuItems'));
    }
}
