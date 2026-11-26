@extends('layouts.app')
@section('title', 'Notifikasi - WadahNgopi')
@section('content')
<style>
    .wn-notifications-page {
        --notification-ink: #292620;
        --notification-muted: #746d63;
        --notification-gold: #925a10;
        --notification-border: #e9e1d5;
    }

    .wn-notifications-list {
        display: grid;
        gap: 14px;
        margin-top: 20px;
    }

    .wn-notification-card {
        min-width: 0;
        padding: 18px;
        border: 1px solid var(--notification-border);
        border-radius: 22px;
        background: #fff;
        color: var(--notification-ink);
        box-shadow: 0 8px 24px rgba(48, 37, 22, 0.045);
        transition: border-color 180ms ease, transform 180ms ease, box-shadow 180ms ease;
    }

    .wn-notification-card:hover {
        transform: translateY(-1px);
        border-color: #e7c982;
        box-shadow: 0 12px 26px rgba(48, 37, 22, 0.08);
    }

    .wn-notification-card.is-unread {
        border-color: #e7c982;
        background: #fffdf8;
        box-shadow: inset 3px 0 0 #d9a333, 0 8px 24px rgba(48, 37, 22, 0.045);
    }

    .wn-notification-card__layout {
        display: grid;
        grid-template-columns: minmax(0, 1fr) auto;
        align-items: center;
        gap: 18px;
    }

    .wn-notification-card__content {
        min-width: 0;
    }

    .wn-notification-card__title {
        margin: 0;
        color: var(--notification-ink);
        font-size: 0.98rem;
        font-weight: 850;
        line-height: 1.35;
    }

    .wn-notification-card__badge {
        padding: 4px 9px;
        border: 1px solid #f0dfb8;
        border-radius: 999px;
        background: #fff3d8;
        color: #795821;
        font-size: 0.65rem;
        font-weight: 800;
        letter-spacing: 0.04em;
    }

    .wn-notification-card__message {
        margin: 10px 0 0;
        color: var(--notification-muted);
        font-size: 0.86rem;
        line-height: 1.65;
    }

    .wn-notification-card__business {
        margin: 10px 0 0;
        color: #8c5c1e;
        font-size: 0.75rem;
        font-weight: 750;
    }

    .wn-notification-card__time {
        display: block;
        margin-top: 12px;
        color: #898176;
        font-size: 0.7rem;
        font-weight: 600;
    }

    .wn-notification-card__open {
        display: inline-flex;
        min-height: 40px;
        align-items: center;
        justify-content: center;
        gap: 8px;
        padding: 9px 14px;
        border: 1px solid var(--notification-ink);
        border-radius: 999px;
        background: var(--notification-ink);
        color: #fff;
        font: inherit;
        font-size: 0.72rem;
        font-weight: 800;
        cursor: pointer;
        transition: transform 160ms ease, box-shadow 160ms ease;
        max-width: 100%;
        white-space: nowrap;
    }

    .wn-notification-card__open:hover {
        transform: translateY(-1px);
        background: #514a3f;
        box-shadow: 0 7px 16px rgba(48, 37, 22, 0.14);
    }

    .wn-notification-card__open:focus-visible {
        outline: 3px solid rgba(224, 168, 70, 0.5);
        outline-offset: 3px;
    }

    .wn-notifications-pagination {
        margin-top: 20px;
    }

    @media (max-width: 640px) {
        .wn-notification-card__layout {
            grid-template-columns: minmax(0, 1fr);
            gap: 14px;
        }

        .wn-notification-card__open {
            width: 100%;
            white-space: normal;
        }
    }

    @media (max-width: 480px) {
        .wn-notification-card {
            padding: 14px;
        }
    }
</style>

<main class="wn-customer-page wn-notifications-page">
    <a href="{{ route('account') }}" class="wn-customer-back"><i class="ph ph-arrow-left" aria-hidden="true"></i> Kembali ke akun</a>
    <header class="wn-customer-page-header">
        <span class="wn-customer-eyebrow"><i class="ph ph-bell" aria-hidden="true"></i> Pembaruan favoritmu</span>
        <h1>Notifikasi</h1>
        <p>Info perubahan kafe atau roastery yang kamu simpan, serta kabar pesananmu.</p>
    </header>

    <section aria-label="Daftar notifikasi" class="wn-notifications-list">
        @forelse($notifications as $notification)
            @php($isOrderNotification = in_array($notification->data['kind'] ?? '', ['order_ready', 'order_payment_verified', 'payment_qr_changed'], true))
            <article class="wn-notification-card {{ $notification->read_at ? 'is-read' : 'is-unread' }}">
                <div class="wn-notification-card__layout">
                    <div class="wn-notification-card__content">
                        <div class="flex flex-wrap items-center gap-2">
                            <h2 class="wn-notification-card__title">{{ $notification->data['title'] ?? 'Pembaruan WadahNgopi' }}</h2>
                            @unless($notification->read_at)
                                <span class="wn-notification-card__badge">Baru</span>
                            @endunless
                        </div>
                        <p class="wn-notification-card__message">{{ $notification->data['message'] ?? '' }}</p>
                        @if(! empty($notification->data['business_name']))
                            <p class="wn-notification-card__business">{{ $notification->data['business_name'] }}</p>
                        @endif
                        <time class="wn-notification-card__time" datetime="{{ $notification->created_at->toIso8601String() }}">
                            {{ $notification->created_at->format('d M Y, H:i') }}
                        </time>
                    </div>
                    @if(! empty($notification->data['url']))
                        <form method="POST" action="{{ route('notifications.open', $notification->id) }}">
                            @csrf
                            <button type="submit" class="wn-notification-card__open">
                                {{ $notification->read_at ? ($isOrderNotification ? 'Buka pesanan' : 'Buka') : ($isOrderNotification ? 'Tandai dibaca & buka pesanan' : 'Tandai dibaca & buka') }}
                                <i class="ph ph-arrow-up-right" aria-hidden="true"></i>
                            </button>
                        </form>
                    @endif
                </div>
            </article>
        @empty
            <div class="wn-customer-empty wn-notifications-empty">
                <span aria-hidden="true">
                    <i class="ph ph-bell-slash"></i>
                </span>
                <strong>Belum ada notifikasi</strong>
                <p>Pembaruan kedai favorit dan status pesanan akan muncul di sini.</p>
                <a href="{{ route('explore') }}">Jelajahi kafe</a>
            </div>
        @endforelse
    </section>

    @if($notifications->hasPages())
        <div class="wn-notifications-pagination">
            {{ $notifications->links('pagination.customer') }}
        </div>
    @endif
</main>
@endsection
