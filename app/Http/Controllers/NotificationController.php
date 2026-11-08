<?php

namespace App\Http\Controllers;

use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class NotificationController extends Controller
{
    public function index(Request $request): View
    {
        $notifications = $request->user()
            ->notifications()
            ->latest()
            ->paginate(20);

        return view('notifications.index', compact('notifications'));
    }

    public function open(Request $request, string $notificationId): RedirectResponse
    {
        $notification = $request->user()
            ->notifications()
            ->whereKey($notificationId)
            ->firstOrFail();

        $notification->markAsRead();
        $url = $notification->data['url'] ?? null;

        if (is_string($url)) {
            $host = parse_url($url, PHP_URL_HOST);
            $isLocalPath = str_starts_with($url, '/') && ! str_starts_with($url, '//');
            $isSameHost = $host !== null && strcasecmp($host, $request->getHost()) === 0;

            if ($isLocalPath || $isSameHost) {
                return redirect()->to($url);
            }
        }

        return redirect()->route('notifications.index');
    }
}
