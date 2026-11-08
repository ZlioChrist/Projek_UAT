<?php

namespace App\Services;

use App\Models\Bookmark;
use App\Models\Cafe;
use App\Models\Roastery;
use App\Models\User;
use App\Notifications\BusinessUpdateNotification;

class BusinessUpdateNotifier
{
    public function notify(Cafe|Roastery $business, string $title, string $message): void
    {
        $type = $business instanceof Cafe ? 'cafe' : 'roastery';
        $url = $business instanceof Cafe
            ? route('cafes.show', $business->slug)
            : route('roastery.show', $business->slug);

        User::query()
            ->where('role', 'user')
            ->whereIn('id', Bookmark::query()
                ->where('bookmarkable_type', $type)
                ->where('bookmarkable_id', $business->id)
                ->select('user_id'))
            ->chunkById(100, function ($users) use ($business, $title, $message, $url): void {
                foreach ($users as $user) {
                    $user->notify(new BusinessUpdateNotification(
                        $title,
                        $message,
                        $business->name,
                        $url,
                    ));
                }
            });
    }
}
