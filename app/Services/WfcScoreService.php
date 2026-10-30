<?php

namespace App\Services;

use App\Models\Cafe;
use App\Models\WfcScore;
use Illuminate\Support\Facades\DB;

class WfcScoreService
{
    /**
     * Calculate the distance between two points in meters using Haversine formula.
     */
    public function calculateDistance($lat1, $lng1, $lat2, $lng2): float
    {
        $earthRadius = 6371000; // Earth radius in meters

        $dLat = deg2rad($lat2 - $lat1);
        $dLng = deg2rad($lng2 - $lng1);

        $a = sin($dLat / 2) * sin($dLat / 2) +
            cos(deg2rad($lat1)) * cos(deg2rad($lat2)) *
            sin($dLng / 2) * sin($dLng / 2);

        $c = 2 * atan2(sqrt($a), sqrt(1 - $a));

        return $earthRadius * $c;
    }

    /**
     * Submit a new WFC score and update the cafe's aggregate score.
     */
    public function submitScore(Cafe $cafe, array $data): WfcScore
    {
        $userLat = $data['user_lat'] ?? null;
        $userLng = $data['user_lng'] ?? null;
        $isVerified = false;
        $reason = 'no_gps';

        if ($userLat && $userLng && $cafe->latitude && $cafe->longitude) {
            $distance = $this->calculateDistance($userLat, $userLng, $cafe->latitude, $cafe->longitude);
            if ($distance <= 100) { // Increased to 100m for better GPS tolerance
                $isVerified = true;
                $reason = 'verified';
            } else {
                $reason = 'too_far';
            }
        }

        return DB::transaction(function () use ($cafe, $data, $isVerified, $reason) {
            $score = WfcScore::create([
                'cafe_id' => $cafe->id,
                'user_id' => auth()->id(),
                'wifi_rating' => $data['wifi_rating'],
                'outlet_rating' => $data['outlet_rating'],
                'comfort_rating' => $data['comfort_rating'],
                'is_verified' => $isVerified,
                'user_lat' => $data['user_lat'] ?? null,
                'user_lng' => $data['user_lng'] ?? null,
                'comment' => $data['comment'] ?? null,
            ]);

            $score->verification_reason = $reason;

            $this->updateCafeAggregate($cafe);

            return $score;
        });
    }

    /**
     * Update the cafe's aggregated WFC score.
     */
    public function updateCafeAggregate(Cafe $cafe): void
    {
        // We now aggregate ALL scores for the count and average, 
        // but individual reviews still show 'Verified' badge for trust.
        $stats = WfcScore::where('cafe_id', $cafe->id)
            ->selectRaw('COUNT(*) as count, AVG((wifi_rating + outlet_rating + comfort_rating) / 3) as avg_score')
            ->first();

        $cafe->update([
            'wfc_avg_score' => $stats->avg_score ?: 0,
            'wfc_review_count' => $stats->count ?: 0,
        ]);
    }
}
