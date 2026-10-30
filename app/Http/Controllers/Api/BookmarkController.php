<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Bookmark;
use Illuminate\Http\Request;

class BookmarkController extends Controller
{
    /**
     * Get all bookmarks for authenticated user.
     */
    public function index(Request $request)
    {
        $bookmarks = Bookmark::where('user_id', $request->user()->id)
            ->get()
            ->map(fn($b) => [
                'id' => $b->bookmarkable_id,
                'type' => $b->bookmarkable_type,
            ]);

        return response()->json([
            'cafes' => $bookmarks->where('type', 'cafe')->pluck('id')->values(),
            'roasteries' => $bookmarks->where('type', 'roastery')->pluck('id')->values(),
        ]);
    }

    /**
     * Toggle a bookmark (add or remove).
     */
    public function toggle(Request $request)
    {
        $validated = $request->validate([
            'bookmarkable_id' => 'required|integer',
            'bookmarkable_type' => 'required|in:cafe,roastery',
        ]);

        $existing = Bookmark::where('user_id', $request->user()->id)
            ->where('bookmarkable_id', $validated['bookmarkable_id'])
            ->where('bookmarkable_type', $validated['bookmarkable_type'])
            ->first();

        if ($existing) {
            $existing->delete();
            return response()->json(['action' => 'removed', 'message' => 'Dihapus dari favorit']);
        }

        // Limit bookmarks to 50 per user
        $count = Bookmark::where('user_id', $request->user()->id)->count();
        if ($count >= 50) {
            return response()->json(['message' => 'Maksimal 50 simpanan.'], 422);
        }

        Bookmark::create([
            'user_id' => $request->user()->id,
            'bookmarkable_id' => $validated['bookmarkable_id'],
            'bookmarkable_type' => $validated['bookmarkable_type'],
        ]);

        return response()->json(['action' => 'added', 'message' => 'Disimpan ke favorit']);
    }

    /**
     * Sync bookmarks from localStorage (one-time migration).
     */
    public function sync(Request $request)
    {
        $validated = $request->validate([
            'cafes' => 'nullable|array|max:50',
            'cafes.*' => 'integer',
            'roasteries' => 'nullable|array|max:50',
            'roasteries.*' => 'integer',
        ]);

        $userId = $request->user()->id;
        $synced = 0;

        foreach ($validated['cafes'] ?? [] as $cafeId) {
            Bookmark::firstOrCreate([
                'user_id' => $userId,
                'bookmarkable_id' => $cafeId,
                'bookmarkable_type' => 'cafe',
            ]);
            $synced++;
        }

        foreach ($validated['roasteries'] ?? [] as $roasteryId) {
            Bookmark::firstOrCreate([
                'user_id' => $userId,
                'bookmarkable_id' => $roasteryId,
                'bookmarkable_type' => 'roastery',
            ]);
            $synced++;
        }

        return response()->json(['message' => "Synced {$synced} bookmarks.", 'synced' => $synced]);
    }
}
