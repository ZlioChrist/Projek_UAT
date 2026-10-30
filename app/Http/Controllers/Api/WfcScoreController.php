<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Cafe;
use App\Services\WfcScoreService;
use Illuminate\Http\Request;

class WfcScoreController extends Controller
{
    protected $wfcService;

    public function __construct(WfcScoreService $wfcService)
    {
        $this->wfcService = $wfcService;
    }

    public function store(Request $request, Cafe $cafe)
    {
        $validated = $request->validate([
            'wifi_rating' => 'required|integer|min:1|max:5',
            'outlet_rating' => 'required|integer|min:1|max:5',
            'comfort_rating' => 'required|integer|min:1|max:5',
            'user_lat' => 'nullable|numeric',
            'user_lng' => 'nullable|numeric',
            'comment' => 'nullable|string|max:500',
        ]);

        // Prevent multiple ratings from the same user for the same cafe
        $existing = \App\Models\WfcScore::where('cafe_id', $cafe->id)
            ->where('user_id', auth()->id())
            ->first();

        if ($existing) {
            return response()->json([
                'message' => 'Lu udah kasih rating buat cafe ini sebelumnya.',
            ], 422);
        }

        $score = $this->wfcService->submitScore($cafe, $validated);

        return response()->json([
            'message' => 'Score submitted successfully!',
            'score' => $score,
            'is_verified' => $score->is_verified,
            'verification_reason' => $score->verification_reason ?? 'no_gps',
            'new_aggregate' => [
                'score' => number_format($cafe->fresh()->wfc_avg_score, 1),
                'count' => $cafe->fresh()->wfc_review_count,
            ],
        ]);
    }
}
