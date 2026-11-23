<!doctype html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width,initial-scale=1">
    <title>{{ $order->payment_status === 'paid' ? 'Struk' : 'Tiket pesanan' }} {{ $order->display_code }}</title>
    <style>@include('orders.partials.receipt-styles')</style>
</head>
<body class="receipt-page">
@php
    $isDelivery = $order->fulfillment_method === 'delivery';
    $paymentStatusLabel = match($order->payment_status) {
        'paid' => 'Lunas / terverifikasi',
        'submitted' => 'Bukti menunggu verifikasi',
        'rejected' => 'Pembayaran perlu diperiksa kembali',
        'expired' => 'Batas waktu pembayaran berakhir',
        'cancelled' => 'Dibatalkan',
        default => 'Belum dibayar',
    };
@endphp
<main class="receipt-shell">
    <article class="receipt-paper">
        <header class="receipt-header">
            <span class="receipt-brand">WADAHNGOPI · {{ $order->payment_status === 'paid' ? 'STRUK' : 'TIKET PESANAN' }}</span>
            <h1 class="receipt-title">{{ $order->shop_name }}</h1>
            @if($order->shop?->address)<p class="receipt-address">{{ $order->shop->address }}</p>@endif
            <p class="receipt-type">{{ $isDelivery ? 'Pesanan diantar' : 'Pesanan diambil di kedai' }}</p>
            <span class="receipt-status{{ $order->payment_status === 'paid' ? '' : ' receipt-status--pending' }}">{{ $paymentStatusLabel }}</span>
        </header>

        <section class="receipt-meta" aria-label="Informasi pesanan">
            <div class="receipt-meta-item">
                <span class="receipt-meta-label">Waktu pesanan</span>
                <strong class="receipt-meta-value">{{ $order->created_at->format('d/m/Y H:i') }}</strong>
            </div>
            <div class="receipt-meta-item">
                <span class="receipt-meta-label">Status pesanan</span>
                <strong class="receipt-meta-value">{{ $order->status_label }}</strong>
            </div>
            <div class="receipt-meta-item">
                <span class="receipt-meta-label">Pemesan</span>
                <strong class="receipt-meta-value">{{ $order->customer_name }}</strong>
            </div>
            <div class="receipt-meta-item">
                <span class="receipt-meta-label">Kode</span>
                <strong class="receipt-meta-value">{{ $order->display_code }}</strong>
            </div>
        </section>

        @if($isDelivery)
            <section class="receipt-meta" aria-label="Informasi pengantaran">
                <div class="receipt-meta-item receipt-meta-item--wide">
                    <span class="receipt-meta-label">Alamat pengantaran</span>
                    <strong class="receipt-meta-value">{{ $order->delivery_address }}</strong>
                </div>
                @if($order->delivery_distance_km)
                    <div class="receipt-meta-item receipt-meta-item--wide">
                        <span class="receipt-meta-label">Jarak rute</span>
                        <strong class="receipt-meta-value">{{ number_format((float)$order->delivery_distance_km,2,',','.') }} km</strong>
                    </div>
                @endif
            </section>
        @endif

        <section class="receipt-items" aria-label="Rincian pesanan">
            <h2 class="receipt-section-title">Rincian pesanan</h2>
            @foreach($order->items as $item)
                <div class="receipt-item">
                    <div>
                        <span class="receipt-item-name">{{ $item->parent_order_item_id ? '+ ' : '' }}{{ $item->name_snapshot }}</span>
                        <span class="receipt-item-quantity">{{ $item->quantity }} × Rp{{ number_format((float)$item->unit_price,0,',','.') }}</span>
                    </div>
                    <strong class="receipt-item-price">Rp{{ number_format((float)$item->line_total,0,',','.') }}</strong>
                </div>
            @endforeach
        </section>

        <section class="receipt-totals" aria-label="Ringkasan pembayaran">
            <div class="receipt-total-row"><span>Subtotal</span><strong>Rp{{ number_format((float)$order->subtotal,0,',','.') }}</strong></div>
            @if($isDelivery)
                <div class="receipt-total-row"><span>Ongkir</span><strong>Rp{{ number_format((float)$order->delivery_fee,0,',','.') }}</strong></div>
            @endif
            <div class="receipt-total-row receipt-total-row--grand"><span>TOTAL</span><strong>Rp{{ number_format((float)$order->total,0,',','.') }}</strong></div>
        </section>

        <section class="receipt-code-box">
            <p class="receipt-code-label">KODE PESANAN</p>
            <strong class="receipt-code">{{ $order->display_code }}</strong>
            @if($order->payment_status === 'paid')
                <p class="receipt-note">{{ $isDelivery ? 'Pembayaran terverifikasi. Simpan kode ini sebagai referensi pesanan.' : 'Pembayaran terverifikasi. Tunjukkan kode pesanan ini kepada staf saat mengambil pesanan.' }}</p>
            @elseif($order->payment_status === 'submitted')
                <p class="receipt-note">Bukti pembayaran menunggu verifikasi kedai.</p>
            @elseif($order->payment_method === 'cash')
                <p class="receipt-note">Tunjukkan kode pesanan ini kepada staf untuk membayar tunai dan mengambil pesanan.</p>
            @else
                <p class="receipt-note">Simpan kode ini untuk konfirmasi pembayaran dan pengambilan pesanan.</p>
            @endif
        </section>
        <p class="receipt-thanks">Terima kasih sudah memesan ☕</p>
    </article>
</main>
<div class="receipt-shell receipt-actions">
    <button class="receipt-button" type="button" onclick="window.print()">Cetak / Simpan PDF</button>
    <a class="receipt-button receipt-button--secondary" href="{{ route('orders.show', $order->public_code) }}">Kembali ke pesanan</a>
</div>
</body>
</html>
