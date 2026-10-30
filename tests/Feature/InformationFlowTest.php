<?php

use App\Models\Information;
use Illuminate\Support\Facades\Cache;

it('increments view count in cache on first visit', function () {
    $info = Information::factory()->create(['is_published' => true, 'views' => 0]);

    $this->get(route('information.show', $info));

    // View count is now batched via cache, not direct DB write
    expect((int) Cache::get("info_views:{$info->id}", 0))->toBe(1);
});

it('does not increment view count on repeat visit in same session', function () {
    $info = Information::factory()->create(['is_published' => true, 'views' => 0]);

    $this->get(route('information.show', $info));
    $this->get(route('information.show', $info));

    // Should still be 1 (session-based deduplication)
    expect((int) Cache::get("info_views:{$info->id}", 0))->toBe(1);
});

it('increments view count for different articles independently', function () {
    $info1 = Information::factory()->create(['is_published' => true, 'views' => 0]);
    $info2 = Information::factory()->create(['is_published' => true, 'views' => 0]);

    $this->get(route('information.show', $info1));
    $this->get(route('information.show', $info2));

    expect((int) Cache::get("info_views:{$info1->id}", 0))->toBe(1);
    expect((int) Cache::get("info_views:{$info2->id}", 0))->toBe(1);
});
