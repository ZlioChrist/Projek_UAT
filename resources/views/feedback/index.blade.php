@extends('layouts.app')
@section('title', 'Kritik & Saran - WadahNgopi')
@section('content')
<main class="wn-customer-page wn-account-feedback-list-page">
    <a href="{{ route('account') }}" class="wn-customer-back"><i class="ph ph-arrow-left" aria-hidden="true"></i> Kembali ke akun</a>
    <header class="wn-customer-page-header">
        <span class="wn-customer-eyebrow"><i class="ph ph-chat-circle-text" aria-hidden="true"></i> Suara pelanggan</span>
        <h1>Kritik &amp; saran</h1>
        <p>Pilih pesanan yang sudah selesai untuk memberikan masukan kepada kafe atau roastery.</p>
    </header>

    <section class="wn-account-feedback-orders" aria-label="Pesanan yang bisa diberi masukan">
        @forelse($orders as $order)
            <article class="wn-account-feedback-order">
                <span class="wn-account-order-mark"><i class="ph ph-chat-circle-text" aria-hidden="true"></i></span>
                <span class="wn-account-feedback-order-copy">
                    <strong>{{ $order->shop_name }}</strong>
                    <small>{{ $order->public_code }} · {{ $order->completed_at?->format('d/m/Y H:i') ?? $order->created_at->format('d/m/Y H:i') }}</small>
                </span>
                @if($order->feedback)
                    <span class="wn-account-order-status is-completed">Terkirim</span>
                @else
                    <a href="{{ route('orders.feedback.create', $order->public_code) }}" class="wn-account-feedback-link">Beri masukan</a>
                @endif
            </article>
        @empty
            <div class="wn-account-empty">
                <i class="ph ph-chat-circle-text" aria-hidden="true"></i>
                <p>Pesanan yang sudah selesai akan muncul di sini. Setelah itu, kamu bisa mengirim kritik atau saran.</p>
            </div>
        @endforelse
    </section>
</main>
@endsection
