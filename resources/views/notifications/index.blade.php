@extends('layouts.app')
@section('title', 'Notifikasi - WadahNgopi')
@section('content')
<style>
    .wn-notifications-page {
        --notification-espresso: #321b12;
        --notification-cream: #fff8ed;
        --notification-muted: #d1b99f;
        --notification-gold: #f59e0b;
    }

    .wn-notifications-list {
        display: grid;
        gap: 10px;
        margin-top: 8px;
    }

    .wn-notification-card {
        padding: 16px;
        border: 1px solid rgba(255, 255, 255, 0.1);
        border-radius: 20px;
        background: linear-gradient(145deg, #49291a, #321b12);
        color: var(--notification-cream);
        box-shadow: 0 12px 26px rgba(61, 34, 20, 0.13);
        transition: border-color 160ms ease, transform 160ms ease, box-shadow 160ms ease;
    }

    .wn-notification-card:hover {
        transform: translateY(-2px);
        border-color: rgba(245, 158, 11, 0.34);
        box-shadow: 0 16px 30px rgba(61, 34, 20, 0.18);
    }

    .wn-notification-card.is-unread {
        border-color: rgba(245, 158, 11, 0.48);
        box-shadow: inset 3px 0 0 var(--notification-gold), 0 12px 26px rgba(61, 34, 20, 0.13);
    }

    .wn-notification-card__title {
        margin: 0;
        color: var(--notification-cream);
        font-size: 0.98rem;
        font-weight: 850;
        line-height: 1.35;
    }

    .wn-notification-card__badge {
        padding: 4px 9px;
        border: 1px solid rgba(245, 158, 11, 0.3);
        border-radius: 999px;
        background: rgba(245, 158, 11, 0.16);
        color: #ffdc9d;
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
        color: #f5d399;
        font-size: 0.75rem;
        font-weight: 750;
    }

    .wn-notification-card__time {
        display: block;
        margin-top: 12px;
        color: #bca58d;
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
        border: 1px solid var(--notification-gold);
        border-radius: 999px;
        background: var(--notification-gold);
        color: var(--notification-espresso);
        font: inherit;
        font-size: 0.72rem;
        font-weight: 800;
        cursor: pointer;
        transition: transform 160ms ease, box-shadow 160ms ease;
    }

    .wn-notification-card__open:hover {
        transform: translateY(-1px);
        background: #ffbd3b;
        box-shadow: 0 7px 16px rgba(50, 27, 18, 0.18);
    }

    .wn-notification-card__open:focus-visible {
        outline: 3px solid rgba(224, 168, 70, 0.5);
        outline-offset: 3px;
    }

    .wn-notifications-pagination {
        margin-top: 20px;
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
            <article class="wn-notification-card {{ $notification->read_at ? 'is-read' : 'is-unread' }}">
                <div class="flex flex-col gap-4 sm:flex-row sm:items-start sm:justify-between">
                    <div class="min-w-0">
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
                                {{ $notification->read_at ? 'Buka' : 'Tandai dibaca & buka' }}
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
            {{ $notifications->links() }}
        </div>
    @endif
</main>
@endsection
