<!doctype html>
<html lang="id"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1"><title>Nota {{ $order->display_code }}</title>
<style>@include('orders.partials.receipt-styles')</style></head><body class="receipt-page">
@php($payload = $job->payload)
@php($isDelivery = ($payload['fulfillment_method'] ?? 'pickup') === 'delivery')
@php($paymentStatus = $order->payment_status === 'paid' ? 'Lunas' : 'Belum lunas')
<main class="receipt-shell">
    <article class="receipt-paper">
        <header class="receipt-header">
            <span class="receipt-brand">WADAHNGOPI · NOTA PESANAN</span>
            <h1 class="receipt-title">{{ $payload['cafe_name'] }}</h1>
            @if($payload['cafe_address'])<p class="receipt-address">{{ $payload['cafe_address'] }}</p>@endif
            <p class="receipt-type">{{ $isDelivery ? 'Pesanan diantar' : 'Pesanan diambil di kedai' }}</p>
            <span class="receipt-status{{ $order->payment_status === 'paid' ? '' : ' receipt-status--unpaid' }}">{{ $paymentStatus }}</span>
        </header>

        <section class="receipt-meta" aria-label="Informasi pesanan">
            <div class="receipt-meta-item">
                <span class="receipt-meta-label">Waktu</span>
                <strong class="receipt-meta-value">{{ $payload['paid_at'] ? \Illuminate\Support\Carbon::parse($payload['paid_at'])->format('d/m/Y H:i') : $order->created_at->format('d/m/Y H:i') }}</strong>
            </div>
            <div class="receipt-meta-item">
                <span class="receipt-meta-label">Pemesan</span>
                <strong class="receipt-meta-value">{{ $payload['customer_name'] }}</strong>
            </div>
            <div class="receipt-meta-item receipt-meta-item--wide">
                <span class="receipt-meta-label">Kode pesanan</span>
                <strong class="receipt-meta-value">{{ $order->display_code }}</strong>
            </div>
        </section>

        @if($isDelivery)
            <section class="receipt-meta" aria-label="Informasi pengantaran">
                <div class="receipt-meta-item receipt-meta-item--wide">
                    <span class="receipt-meta-label">Alamat pengantaran</span>
                    <strong class="receipt-meta-value">{{ $payload['delivery_address'] }}</strong>
                </div>
                @if(!empty($payload['delivery_distance_km']))
                    <div class="receipt-meta-item receipt-meta-item--wide">
                        <span class="receipt-meta-label">Jarak rute</span>
                        <strong class="receipt-meta-value">{{ number_format((float)$payload['delivery_distance_km'],2,',','.') }} km</strong>
                    </div>
                @endif
            </section>
        @endif

        <section class="receipt-items" aria-label="Rincian pesanan">
            <h2 class="receipt-section-title">Rincian pesanan</h2>
            @foreach($payload['items'] as $item)
                <div class="receipt-item">
                    <div>
                        <span class="receipt-item-name">{{ $item['name'] }}</span>
                        <span class="receipt-item-quantity">{{ $item['quantity'] }} × Rp{{ number_format((float)$item['unit_price'],0,',','.') }}</span>
                    </div>
                    <strong class="receipt-item-price">Rp{{ number_format((float)$item['line_total'],0,',','.') }}</strong>
                </div>
            @endforeach
        </section>

        <section class="receipt-totals" aria-label="Ringkasan pembayaran">
            <div class="receipt-total-row"><span>Subtotal</span><strong>Rp{{ number_format((float)$payload['subtotal'],0,',','.') }}</strong></div>
            @if($isDelivery)
                <div class="receipt-total-row"><span>Ongkir</span><strong>Rp{{ number_format((float)$payload['delivery_fee'],0,',','.') }}</strong></div>
            @endif
            <div class="receipt-total-row receipt-total-row--grand"><span>TOTAL</span><strong>Rp{{ number_format((float)$payload['total'],0,',','.') }}</strong></div>
        </section>

        <section class="receipt-code-box">
            <p class="receipt-code-label">KODE PESANAN</p>
            <strong class="receipt-code">{{ $order->display_code }}</strong>
            <p class="receipt-note">{{ $isDelivery ? 'Simpan kode ini sebagai referensi pesanan.' : 'Tunjukkan kode ini kepada kedai saat mengambil pesanan.' }}</p>
        </section>
        <p class="receipt-thanks">Terima kasih sudah memesan ☕</p>
    </article>

    <div class="receipt-actions">
        <button class="receipt-button" type="button" onclick="window.print()">Buka dialog cetak</button>
        <p class="receipt-actions-note">Tandai berhasil hanya setelah nota fisik benar-benar keluar dari printer.</p>
        <form method="POST" action="{{ route('owner.print-jobs.status',$job->id) }}">@csrf<input type="hidden" name="state" value="printed"><button class="receipt-button receipt-button--confirm" type="submit">Tandai cetak berhasil</button></form>
        <form method="POST" action="{{ route('owner.print-jobs.status',$job->id) }}">@csrf<input type="hidden" name="state" value="failed"><input type="hidden" name="last_error" value="Printer offline / kertas habis (dilaporkan staf)"><button class="receipt-button receipt-button--danger" type="submit">Cetak gagal / coba lagi</button></form>
        <a class="receipt-button receipt-button--secondary" href="{{ route('owner.orders.show',$order->public_code) }}">Kembali ke detail pesanan</a>
    </div>
</main>
</body></html>
