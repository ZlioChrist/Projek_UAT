@extends('layouts.app')
@section('title', 'Akun Saya - WadahNgopi')
@section('content')
<main class="wn-account-page">
    <header class="wn-account-hero">
        <div class="wn-account-profile-heading">
            <div class="wn-account-topline">
                <span>Profil</span>
                <a class="wn-account-notification-button" href="{{ route('notifications.index') }}" aria-label="Notifikasi, {{ $unreadNotificationCount }} belum dibaca">
                    <i class="ph ph-bell" aria-hidden="true"></i>
                    @if($unreadNotificationCount > 0)<span>{{ $unreadNotificationCount }}</span>@endif
                </a>
            </div>
            <div class="wn-account-identity">
                @if($profilePhotoUrl)
                    <img class="wn-account-avatar wn-account-avatar-photo" src="{{ $profilePhotoUrl }}" alt="Foto profil {{ \App\Support\AuthenticatedUser::require()->name }}">
                @else
                    <div class="wn-account-avatar" aria-hidden="true">{{ mb_strtoupper(mb_substr(\App\Support\AuthenticatedUser::require()->name, 0, 1)) }}</div>
                @endif
            </div>
            <div class="wn-account-profile-name">
                <p class="wn-account-eyebrow">Ruang pelanggan</p>
                <h1 class="wn-account-title">{{ \App\Support\AuthenticatedUser::require()->name }}</h1>
                <p class="wn-account-email">{{ \App\Support\AuthenticatedUser::require()->email }}</p>
                <span class="wn-account-member-badge"><i class="ph-fill ph-seal-check" aria-hidden="true"></i> Member WadahNgopi</span>
                <a href="{{ route('account.profile.edit') }}" class="wn-account-profile-edit-link">
                    <i class="ph ph-pencil-simple" aria-hidden="true"></i> Atur profil
                </a>
                <a href="{{ route('account.whatsapp.edit') }}" class="wn-account-profile-edit-link">
                    <i class="ph ph-shield-check" aria-hidden="true"></i> Login WhatsApp
                </a>
            </div>
        </div>
        <div class="wn-account-stats" role="group" aria-label="Ringkasan akun">
            <div class="wn-account-stat">
                <span class="wn-account-stat-value">{{ $orderCount }}</span>
                <span class="wn-account-stat-label">Pesanan</span>
            </div>
            <div class="wn-account-stat">
                <span class="wn-account-stat-value">{{ $savedCount }}</span>
                <span class="wn-account-stat-label">Tersimpan</span>
            </div>
            <a class="wn-account-stat wn-account-stat-action" href="{{ route('orders.history', ['filter' => 'active']) }}" aria-label="Lihat {{ $activeOrderCount }} pesanan berjalan">
                <span class="wn-account-stat-value">{{ $activeOrderCount }}</span>
                <span class="wn-account-stat-label">Berjalan <i class="ph ph-arrow-up-right" aria-hidden="true"></i></span>
            </a>
        </div>
    </header>

    <section aria-labelledby="account-menu-title">
        <div class="wn-account-section-heading">
            <div>
                <h2 id="account-menu-title">Aktivitas kamu</h2>
                <p>Akses cepat ke pesanan dan tempat favorit.</p>
            </div>
        </div>
        @if(session('status'))
            <p role="status" class="mb-4 rounded-xl bg-emerald-50 p-3 text-sm font-semibold text-emerald-800">{{ session('status') }}</p>
        @endif
        @if($errors->any())
            <p role="alert" class="mb-4 rounded-xl bg-rose-50 p-3 text-sm font-semibold text-rose-800">{{ $errors->first() }}</p>
        @endif
        <div class="wn-account-menu-panels">
            <div class="wn-account-menu-panel">
                <p class="wn-account-menu-label">Pesanan</p>
                <nav class="wn-account-shortcuts" aria-label="Menu pesanan">
                    <a href="{{ route('orders.history') }}" class="wn-account-shortcut">
                        <span class="wn-account-shortcut-icon"><i class="ph ph-receipt" aria-hidden="true"></i></span>
                        <span class="wn-account-shortcut-copy"><strong>Riwayat pesanan</strong><span class="wn-account-shortcut-description">Lihat pesanan sebelumnya dan statusnya.</span></span>
                        <span class="wn-account-shortcut-meta">{{ $orderCount }}</span>
                        <span class="wn-account-shortcut-arrow" aria-hidden="true"><i class="ph ph-caret-right"></i></span>
                    </a>
                    <a href="{{ route('cart.show') }}" class="wn-account-shortcut">
                        <span class="wn-account-shortcut-icon"><i class="ph ph-shopping-bag-open" aria-hidden="true"></i></span>
                        <span class="wn-account-shortcut-copy"><strong>Keranjang pesanan</strong><span class="wn-account-shortcut-description">Lanjutkan pilihan kopi favoritmu.</span></span>
                        <span class="wn-account-shortcut-meta">{{ $cartCount }} item</span>
                        <span class="wn-account-shortcut-arrow" aria-hidden="true"><i class="ph ph-caret-right"></i></span>
                    </a>
                </nav>
            </div>
            <div class="wn-account-menu-panel">
                <p class="wn-account-menu-label">Favorit &amp; bantuan</p>
                <nav class="wn-account-shortcuts" aria-label="Menu favorit dan bantuan">
                    <a href="{{ route('saved') }}" class="wn-account-shortcut">
                        <span class="wn-account-shortcut-icon"><i class="ph ph-heart" aria-hidden="true"></i></span>
                        <span class="wn-account-shortcut-copy"><strong>Favorit</strong><span class="wn-account-shortcut-description">Kafe dan roastery pilihanmu.</span></span>
                        <span class="wn-account-shortcut-meta">{{ $savedCount }}</span>
                        <span class="wn-account-shortcut-arrow" aria-hidden="true"><i class="ph ph-caret-right"></i></span>
                    </a>
                    <a href="{{ route('account.feedback.index') }}" class="wn-account-shortcut">
                        <span class="wn-account-shortcut-icon"><i class="ph ph-chat-circle-text" aria-hidden="true"></i></span>
                        <span class="wn-account-shortcut-copy"><strong>Kritik &amp; saran</strong><span class="wn-account-shortcut-description">Bagikan pengalaman dari pesanan selesai.</span></span>
                        <span class="wn-account-shortcut-meta">Masukan</span>
                        <span class="wn-account-shortcut-arrow" aria-hidden="true"><i class="ph ph-caret-right"></i></span>
                    </a>
                    <a href="{{ route('account.profile.edit') }}" class="wn-account-shortcut">
                        <span class="wn-account-shortcut-icon"><i class="ph ph-user-circle" aria-hidden="true"></i></span>
                        <span class="wn-account-shortcut-copy"><strong>Profil akun</strong><span class="wn-account-shortcut-description">Atur nama, email, dan foto profil.</span></span>
                        <span class="wn-account-shortcut-meta">Atur</span>
                        <span class="wn-account-shortcut-arrow" aria-hidden="true"><i class="ph ph-caret-right"></i></span>
                    </a>
                </nav>
            </div>
            <div class="wn-account-menu-panel">
                <p class="wn-account-menu-label">Untuk pemilik usaha</p>
                <nav class="wn-account-shortcuts" aria-label="Pengajuan usaha">
                    <a href="{{ route('merchant-applications.create') }}" class="wn-account-shortcut">
                        <span class="wn-account-shortcut-icon"><i class="ph ph-storefront" aria-hidden="true"></i></span>
                        <span class="wn-account-shortcut-copy"><strong>Ajukan kafe atau roastery</strong><span class="wn-account-shortcut-description">Kirim informasi usaha untuk ditinjau tim WadahNgopi.</span></span>
                        <span class="wn-account-shortcut-meta">Ajukan</span>
                        <span class="wn-account-shortcut-arrow" aria-hidden="true"><i class="ph ph-caret-right"></i></span>
                    </a>
                </nav>
            </div>
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
