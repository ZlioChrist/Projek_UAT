<?php

namespace App\Http\Controllers;

use Illuminate\Http\RedirectResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class NotificationController extends Controller
{
    private const ORDER_NOTIFICATION_KINDS = [
        'order_ready',
        'order_payment_verified',
        'payment_qr_changed',
    ];

    public function index(Request $request): View
    {
        $notifications = $request->user()
            ->notifications()
            ->latest()
            ->paginate(20);

        return view('notifications.index', compact('notifications'));
    }

    public function unreadCount(Request $request): JsonResponse
    {
        return response()
            ->json(['count' => $request->user()->unreadNotifications()->count()])
            ->header('Cache-Control', 'private, no-store');
    }

    public function open(Request $request, string $notificationId): RedirectResponse
    {
        $notification = $request->user()
            ->notifications()
            ->whereKey($notificationId)
            ->firstOrFail();

        $notification->markAsRead();
        $data = $notification->data;
        $isOrderNotification = in_array($data['kind'] ?? null, self::ORDER_NOTIFICATION_KINDS, true);
        $orderCode = $data['order_code'] ?? null;

        if ($isOrderNotification && is_string($orderCode) && preg_match('/^[A-Za-z0-9_-]{1,20}$/D', $orderCode) === 1) {
            return redirect()->to(route('orders.show', $orderCode, false));
        }

        $url = $data['url'] ?? null;
        if (is_string($url)) {
            $destination = $this->localNotificationPath($url, $request, $isOrderNotification);
            if ($destination !== null) {
                return redirect()->to($destination);
            }
        }

        return redirect()->route('notifications.index');
    }

    private function localNotificationPath(string $url, Request $request, bool $isOrderNotification): ?string
    {
        $parts = parse_url($url);
        if ($parts === false) {
            return null;
        }

        $path = $parts['path'] ?? null;
        if (! is_string($path) || ! str_starts_with($path, '/') || str_starts_with($path, '//')) {
            return null;
        }

        if (! isset($parts['host'])) {
            return $url;
        }

        $scheme = strtolower((string) ($parts['scheme'] ?? ''));
        if (! in_array($scheme, ['http', 'https'], true)) {
            return null;
        }

        $configuredHost = parse_url((string) config('app.url'), PHP_URL_HOST);
        $trustedHosts = array_filter([$request->getHost(), $configuredHost], 'is_string');
        $isKnownOrderPath = $isOrderNotification && preg_match('~^/orders/[^/]+$~D', $path) === 1;
        if (
            ! in_array(strtolower($parts['host']), array_map('strtolower', $trustedHosts), true)
            && ! $isKnownOrderPath
        ) {
            return null;
        }

        return $path.(isset($parts['query']) ? '?'.$parts['query'] : '');
    }
}
