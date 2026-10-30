<?php

namespace App\Http\Controllers;

use App\Models\Cafe;
use Illuminate\Support\Facades\Cache;
use Illuminate\View\View;

// Note: Cafe + Cache still used in show() method

class CafeController extends Controller
{
    /**
     * Display the home landing page.
     * Note: Home page is pure branding/CTA — no cafe data needed.
     */
    public function index(): View
    {
        return view('home');
    }

    /**
     * Display the specified cafe.
     * Cached per-cafe for 10 minutes.
     */
    public function show(Cafe $cafe): View
    {
        abort_if($cafe->status !== 'published', 404);

        // Cache individual cafe with relationships
        // CRITICAL: Strip binary `location` (POINT) field before caching.
        // File/database cache drivers use PHP serialize() which corrupts binary data,
        // causing "Malformed UTF-8" JsonException when Blade renders json_encode().
        $cafe = Cache::remember("cafe_{$cafe->slug}", now()->addMinutes(10), function () use ($cafe) {
            $cafe->load(['facilities', 'city', 'wfcScores' => fn($q) => $q->with('user')->whereNotNull('comment')->latest()]);
            unset($cafe->location);

            return $cafe;
        });

        $hasRated = auth()->check()
            ? $cafe->wfcScores->contains('user_id', auth()->id())
            : false;

        $hasCheckedInToday = auth()->check()
            ? \App\Models\CheckIn::where('user_id', auth()->id())
                ->where('cafe_id', $cafe->id)
                ->whereDate('created_at', today())
                ->exists()
            : false;

        // Public menu cards only contain active items that can currently be ordered.
        $menuItems = $cafe->menuItems()
            ->where('is_active', true)
            ->whereColumn('stock', '>', 'reserved_stock')
            ->orderBy('category')
            ->orderBy('name')
            ->get();
        $menuItemsByCategory = $menuItems->groupBy(fn ($item) => filled($item->category) ? $item->category : 'Lainnya');

        return view('cafes.show', compact('cafe', 'hasRated', 'hasCheckedInToday', 'menuItems', 'menuItemsByCategory'));
    }
}
