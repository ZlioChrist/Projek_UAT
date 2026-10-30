<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Cafe;
use App\Services\BadgeService;
use Illuminate\Http\Request;

class CheckInController extends Controller
{
    public function __construct(protected BadgeService $badgeService)
    {
    }

    /**
     * Check in at a cafe.
     */
    public function store(Request $request, Cafe $cafe)
    {
        $validated = $request->validate([
            'user_lat' => 'nullable|numeric',
            'user_lng' => 'nullable|numeric',
        ]);

        $checkIn = $this->badgeService->checkIn(
            $request->user(),
            $cafe,
            $validated['user_lat'] ?? null,
            $validated['user_lng'] ?? null
        );

        // Check for newly earned badges
        $newBadges = $this->badgeService->evaluateBadges($request->user());

        return response()->json([
            'message' => $checkIn->wasRecentlyCreated ? 'Check-in berhasil!' : 'Udah check-in hari ini!',
            'check_in' => $checkIn,
            'is_verified' => $checkIn->is_verified,
            'verification_reason' => $checkIn->verification_reason ?? 'no_gps',
            'new_badges' => $newBadges,
            'is_new' => $checkIn->wasRecentlyCreated,
        ]);
    }

    /**
     * Get user's badge collection.
     */
    public function badges(Request $request)
    {
        $collection = $this->badgeService->getUserBadgeCollection($request->user());

        return response()->json([
            'badges' => $collection,
            'total_check_ins' => $request->user()->checkIns()->count(),
            'unique_cafes' => $request->user()->unique_cafe_visits,
        ]);
    }
}
