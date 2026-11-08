@extends('layouts.app')
@section('title', 'Riwayat Pesanan - WadahNgopi')
@section('content')
<main class="wn-customer-page wn-history-page">
    <a href="{{ route('account') }}" class="wn-customer-back"><i class="ph ph-arrow-left" aria-hidden="true"></i> Kembali ke profil</a>
    <header class="wn-customer-page-header">
        <span class="wn-customer-eyebrow"><i class="ph ph-receipt" aria-hidden="true"></i> Aktivitas akun</span>
        <h1>Riwayat pesanan</h1>
        <p>Semua pesanan yang pernah kamu buat, lengkap dengan status dan detailnya.</p>
    </header>
    <div class="wn-history-list">
        @forelse($orders as $order)
            <a href="{{ route('orders.show', $order->public_code) }}" class="wn-history-card">
                <span class="wn-history-icon"><i class="ph ph-coffee" aria-hidden="true"></i></span>
                <span class="wn-history-content">
                    <strong>{{ $order->shop_name }}</strong>
                    <span class="wn-history-meta">{{ $order->created_at->format('d/m/Y H:i') }} <span aria-hidden="true">·</span> {{ $order->public_code }}</span>
                    <span class="wn-history-items">{{ $order->items->map(fn ($item) => $item->name_snapshot.' × '.$item->quantity)->implode(' · ') }}</span>
                </span>
                <span class="wn-history-total">
                    <strong>Rp{{ number_format((float)$order->total,0,',','.') }}</strong>
                    <span class="wn-history-status">{{ $order->status_label }}</span>
                </span>
                <i class="ph ph-caret-right wn-history-arrow" aria-hidden="true"></i>
            </a>
        @empty
            <div class="wn-customer-empty">
                <span><i class="ph ph-receipt" aria-hidden="true"></i></span>
                <strong>Belum ada pesanan</strong>
                <p>Pesanan yang kamu buat akan muncul di sini.</p>
                <a href="{{ route('explore') }}">Jelajahi kafe</a>
            </div>
        @endforelse
    </div>
    @if($orders->hasPages())<div class="wn-history-pagination">{{ $orders->links() }}</div>@endif
</main>
@endsection
