<?php

namespace App\Services;

use App\Models\Badge;
use App\Models\Cafe;
use App\Models\CheckIn;
use App\Models\User;
use App\Models\UserBadge;
use Illuminate\Support\Facades\DB;

class BadgeService
{
    /**
     * Process a check-in at a cafe.
     */
    public function checkIn(User $user, Cafe $cafe, ?float $lat, ?float $lng): CheckIn
    {
        $isVerified = false;
        $reason = 'no_gps'; // fallback klo gps user nyasar ke isekai

        if ($lat && $lng && $cafe->latitude && $cafe->longitude) {
            $distance = app(WfcScoreService::class)->calculateDistance($lat, $lng, $cafe->latitude, $cafe->longitude);
            if ($distance <= 100) {
                $isVerified = true;
                $reason = 'verified';
            } else {
                $reason = 'too_far';
            }
        }

        // Prevent duplicate check-ins at the same cafe on the same day
        $existing = CheckIn::where('user_id', $user->id)
            ->where('cafe_id', $cafe->id)
            ->whereDate('created_at', today())
            ->first();

        if ($existing) {
            return $existing;
        }

        $checkIn = CheckIn::create([
            'user_id' => $user->id,
            'cafe_id' => $cafe->id,
            'is_verified' => $isVerified,
            'user_lat' => $lat,
            'user_lng' => $lng,
        ]);

        $checkIn->verification_reason = $reason;

        // Evaluate badges after check-in
        $this->evaluateBadges($user);

        return $checkIn;
    }

    /**
     * Evaluate and award new badges to a user.
     *
     * @return array List of newly awarded badge slugs
     */
    public function evaluateBadges(User $user): array
    {
        $newBadges = [];
        $badges = Badge::all();
        $existingBadgeSlugs = $user->userBadges()->with('badge')->get()->pluck('badge.slug')->toArray();

        foreach ($badges as $badge) {
            if (in_array($badge->slug, $existingBadgeSlugs)) {
                continue; // Already earned
            }

            $earned = match ($badge->requirement_type) {
                'cafe_count' => $this->checkCafeCount($user, $badge->requirement_value),
                'weekend_count' => $this->checkWeekendCount($user, $badge->requirement_value),
                'night_count' => $this->checkNightCount($user, $badge->requirement_value),
                default => false,
            };

            if ($earned) {
                UserBadge::create([
                    'user_id' => $user->id,
                    'badge_id' => $badge->id,
                    'earned_at' => now(),
                ]);
                $newBadges[] = $badge->slug;
            }
        }

        return $newBadges;
    }

    /**
     * Check if user visited N unique cafes.
     */
    private function checkCafeCount(User $user, int $required): bool
    {
        return CheckIn::where('user_id', $user->id)
            ->distinct('cafe_id')
            ->count('cafe_id') >= $required;
    }

    /**
     * Check if user has N weekend check-ins.
     */
    private function checkWeekendCount(User $user, int $required): bool
    {
        return CheckIn::where('user_id', $user->id)
            ->whereIn(DB::raw('DAYOFWEEK(created_at)'), [1, 7]) // Sunday=1, Saturday=7
            ->count() >= $required;
    }

    /**
     * Check if user has N night check-ins (after 20:00).
     */
    private function checkNightCount(User $user, int $required): bool
    {
        return CheckIn::where('user_id', $user->id)
            ->where(DB::raw('HOUR(created_at)'), '>=', 20)
            ->count() >= $required;
    }

    /**
     * Get user's badge collection with progress.
     */
    public function getUserBadgeCollection(User $user): array
    {
        $allBadges = Badge::all();
        $earnedBadgeIds = $user->userBadges()->pluck('badge_id')->toArray();
        $uniqueCafes = CheckIn::where('user_id', $user->id)->distinct('cafe_id')->count('cafe_id');
        $weekendCount = CheckIn::where('user_id', $user->id)->whereIn(DB::raw('DAYOFWEEK(created_at)'), [1, 7])->count();
        $nightCount = CheckIn::where('user_id', $user->id)->where(DB::raw('HOUR(created_at)'), '>=', 20)->count();

        return $allBadges->map(function ($badge) use ($earnedBadgeIds, $uniqueCafes, $weekendCount, $nightCount) {
            $current = match ($badge->requirement_type) {
                'cafe_count' => $uniqueCafes,
                'weekend_count' => $weekendCount,
                'night_count' => $nightCount,
                default => 0,
            };

            return [
                'id' => $badge->id,
                'slug' => $badge->slug,
                'name' => $badge->name,
                'description' => $badge->description,
                'icon' => $badge->icon,
                'earned' => in_array($badge->id, $earnedBadgeIds),
                'progress' => min($current, $badge->requirement_value),
                'target' => $badge->requirement_value,
            ];
        })->toArray();
    }
}
