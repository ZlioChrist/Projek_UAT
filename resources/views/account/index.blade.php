@extends('layouts.app')
@section('title', 'Akun Saya - WadahNgopi')
@section('content')
<main class="wn-account-page">
    <header class="wn-account-hero">
        <div class="wn-account-welcome">
            <div class="wn-account-identity">
                <div class="wn-account-avatar" aria-hidden="true">{{ mb_strtoupper(mb_substr(auth()->user()->name, 0, 1)) }}</div>
                <div>
                    <p class="wn-account-eyebrow">Ruang pelanggan</p>
                    <h1 class="wn-account-title">Halo, {{ auth()->user()->name }}</h1>
                    <p class="wn-account-email">{{ auth()->user()->email }}</p>
                </div>
            </div>
            <div class="wn-account-hero-note">
                <span class="wn-account-note-icon"><i class="ph ph-coffee" aria-hidden="true"></i></span>
                <span>Sudah siap untuk kopi favorit berikutnya?</span>
            </div>
        </div>
        <div class="wn-account-stats" aria-label="Ringkasan akun">
            <div class="wn-account-stat">
                <span class="wn-account-stat-icon"><i class="ph ph-receipt" aria-hidden="true"></i></span>
                <span class="wn-account-stat-value">{{ $orderCount }}</span>
                <span class="wn-account-stat-label">Total pesanan</span>
            </div>
            <div class="wn-account-stat">
                <span class="wn-account-stat-icon"><i class="ph ph-clock-countdown" aria-hidden="true"></i></span>
                <span class="wn-account-stat-value">{{ $activeOrderCount }}</span>
                <span class="wn-account-stat-label">Sedang diproses</span>
            </div>
            <div class="wn-account-stat">
                <span class="wn-account-stat-icon"><i class="ph ph-shopping-bag-open" aria-hidden="true"></i></span>
                <span class="wn-account-stat-value">{{ $cartCount }}</span>
                <span class="wn-account-stat-label">Di keranjang</span>
            </div>
            <div class="wn-account-stat">
                <span class="wn-account-stat-icon"><i class="ph ph-heart" aria-hidden="true"></i></span>
                <span class="wn-account-stat-value">{{ $savedCount }}</span>
                <span class="wn-account-stat-label">Tersimpan</span>
            </div>
        </div>
    </header>

    <section aria-labelledby="account-menu-title">
        <div class="wn-account-section-heading">
            <div>
                <h2 id="account-menu-title">Semua yang kamu butuhkan</h2>
                <p>Kelola pesanan dan tempat favoritmu dengan mudah.</p>
            </div>
        </div>
        <div class="wn-account-shortcuts">
            <a href="{{ route('orders.history') }}" class="wn-account-shortcut">
                <span class="wn-account-shortcut-icon"><i class="ph ph-receipt" aria-hidden="true"></i></span>
                <strong>Riwayat pesanan</strong>
                <span class="wn-account-shortcut-description">Lihat pesanan sebelumnya dan statusnya.</span>
                <span class="wn-account-shortcut-arrow" aria-hidden="true"><i class="ph ph-arrow-up-right"></i></span>
            </a>
            <a href="{{ route('cart.show') }}" class="wn-account-shortcut">
                <span class="wn-account-shortcut-icon"><i class="ph ph-shopping-bag-open" aria-hidden="true"></i></span>
                <strong>Keranjang pesanan</strong>
                <span class="wn-account-shortcut-description">{{ $cartCount }} item menunggu di keranjang.</span>
                <span class="wn-account-shortcut-arrow" aria-hidden="true"><i class="ph ph-arrow-up-right"></i></span>
            </a>
            <a href="{{ route('orders.lookup') }}" class="wn-account-shortcut">
                <span class="wn-account-shortcut-icon"><i class="ph ph-map-pin-line" aria-hidden="true"></i></span>
                <strong>Lacak pesanan</strong>
                <span class="wn-account-shortcut-description">Pantau progres pesananmu.</span>
                <span class="wn-account-shortcut-arrow" aria-hidden="true"><i class="ph ph-arrow-up-right"></i></span>
            </a>
            <a href="{{ route('saved') }}" class="wn-account-shortcut">
                <span class="wn-account-shortcut-icon"><i class="ph ph-heart" aria-hidden="true"></i></span>
                <strong>Tempat tersimpan</strong>
                <span class="wn-account-shortcut-description">{{ $savedCount }} tempat favorit tersimpan.</span>
                <span class="wn-account-shortcut-arrow" aria-hidden="true"><i class="ph ph-arrow-up-right"></i></span>
            </a>
        </div>
    </section>

    <section aria-labelledby="recent-orders-title">
        <div class="wn-account-section-heading">
            <div>
                <h2 id="recent-orders-title">Pesanan terbaru</h2>
                <p>Ringkasan aktivitas terakhirmu di WadahNgopi.</p>
            </div>
            <a href="{{ route('orders.history') }}" class="text-sm font-bold text-amber-900 no-underline hover:underline">Lihat semua</a>
        </div>
        <div class="wn-account-orders">
            @forelse($orders as $order)
                <a href="{{ route('orders.show', $order->public_code) }}" class="wn-account-order">
                    <span class="wn-account-order-shop">
                        <span class="wn-account-order-mark"><i class="ph ph-coffee" aria-hidden="true"></i></span>
                        <span>
                            <span class="wn-account-order-name">{{ $order->shop_name }}</span>
                            <span class="wn-account-order-meta">{{ $order->public_code }} · {{ $order->created_at->format('d/m/Y H:i') }}</span>
                        </span>
                    </span>
                    <span class="wn-account-order-total">
                        Rp{{ number_format((float) $order->total, 0, ',', '.') }}
                        <span @class([
                            'wn-account-order-status',
                            'is-completed' => $order->status === \App\Models\Order::STATUS_COMPLETED,
                            'is-cancelled' => $order->status === \App\Models\Order::STATUS_CANCELLED,
                        ])>{{ $order->status_label }}</span>
                    </span>
                </a>
            @empty
                <div class="wn-account-empty">
                    <i class="ph ph-coffee" aria-hidden="true"></i>
                    <p>Belum ada pesanan. Jelajahi kafe atau roastery untuk menemukan kopi pertamamu.</p>
                </div>
            @endforelse
        </div>
    </section>

    <footer class="wn-account-footer">
        <form method="POST" action="{{ route('logout') }}">
            @csrf
            <button type="submit" class="wn-account-logout"><i class="ph ph-sign-out" aria-hidden="true"></i> Keluar dari akun</button>
        </form>
    </footer>
</main>
@endsection
