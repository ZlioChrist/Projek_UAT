<?php

namespace App\Services;

use App\Models\Cafe;
use App\Models\VibeVote;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

class VibeService
{
    /**
     * Submit a vibe vote for a cafe.
     */
    public function submitVibe(Cafe $cafe, string $level, ?float $lat, ?float $lng, ?string $fingerprint, ?int $userId = null): VibeVote
    {
        $isVerified = false;

        if ($lat && $lng && $cafe->latitude && $cafe->longitude) {
            $distance = app(WfcScoreService::class)->calculateDistance($lat, $lng, $cafe->latitude, $cafe->longitude);
            $isVerified = $distance <= 100;
        }

        $vote = DB::transaction(function () use ($cafe, $level, $isVerified, $lat, $lng, $fingerprint, $userId) {
            $vote = VibeVote::create([
                'cafe_id' => $cafe->id,
                'user_id' => $userId,
                'level' => $level,
                'is_verified' => $isVerified,
                'user_lat' => $lat,
                'user_lng' => $lng,
                'fingerprint' => $fingerprint,
            ]);

            $this->updateCafeVibe($cafe);

            return $vote;
        });

        // Invalidate cache
        Cache::forget("vibe_aggregate_{$cafe->id}");

        return $vote;
    }

    /**
     * Get aggregated vibe data for a cafe (cached 2 minutes).
     */
    public function getAggregatedVibe(Cafe $cafe): array
    {
        return Cache::remember("vibe_aggregate_{$cafe->id}", 120, function () use ($cafe) {
            $votes = VibeVote::where('cafe_id', $cafe->id)
                ->recent(4)
                ->select('level', DB::raw('COUNT(*) as count'))
                ->groupBy('level')
                ->pluck('count', 'level')
                ->toArray();

            $total = array_sum($votes);
            $lastVote = VibeVote::where('cafe_id', $cafe->id)
                ->recent(4)
                ->latest()
                ->first();

            return [
                'distribution' => [
                    'sepi' => $votes['sepi'] ?? 0,
                    'lumayan' => $votes['lumayan'] ?? 0,
                    'rame' => $votes['rame'] ?? 0,
                    'penuh' => $votes['penuh'] ?? 0,
                ],
                'total' => $total,
                'dominant' => $total > 0 ? array_search(max($votes), $votes) : null,
                'last_updated' => $lastVote?->created_at?->diffForHumans(),
                'last_updated_iso' => $lastVote?->created_at?->toISOString(),
            ];
        });
    }

    /**
     * Update the cafe's cached vibe level.
     */
    public function updateCafeVibe(Cafe $cafe): void
    {
        $votes = VibeVote::where('cafe_id', $cafe->id)
            ->recent(4)
            ->select('level', DB::raw('COUNT(*) as count'))
            ->groupBy('level')
            ->pluck('count', 'level')
            ->toArray();

        $dominant = !empty($votes) ? array_search(max($votes), $votes) : null;

        $cafe->update([
            'current_vibe' => $dominant,
            'vibe_updated_at' => now(),
        ]);
    }

    /**
     * Check if a fingerprint has already voted for this cafe recently.
     */
    public function hasRecentVote(int $cafeId, string $fingerprint): bool
    {
        return VibeVote::where('cafe_id', $cafeId)
            ->where('fingerprint', $fingerprint)
            ->recent(4)
            ->exists();
    }
}
