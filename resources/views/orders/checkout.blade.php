@extends('layouts.app')
@section('title', 'Checkout - '.$cafe->name)
@section('content')
@php
    $selectedPaymentMethod = old('payment_method', $paymentQrAvailable ? 'qris' : 'transfer');
    $total = $items->sum(fn ($item) => (float) $item->price * (int) ($cart['items'][$item->id] ?? 0));
@endphp
<form id="checkout-form" class="checkout-page" method="POST" action="{{ $cafe instanceof \App\Models\Cafe ? route('checkout.store', $cafe->slug) : route('roastery.checkout.store', $cafe->slug) }}">
    @csrf
    <input type="hidden" name="checkout_key" value="{{ $checkoutKey }}">

    <div class="checkout-shell">
        <header class="checkout-heading">
            <div class="checkout-heading-copy">
                <span class="checkout-eyebrow"><i class="ph-fill ph-coffee" aria-hidden="true"></i> Pesanan di {{ $cafe->name }}</span>
                <h1>Selesaikan pesananmu</h1>
                <p>Isi detail pesanan dan pilih cara pembayaran yang paling nyaman.</p>
            </div>
            <div class="checkout-steps" aria-label="Tahapan pesanan">
                <span class="checkout-step checkout-step-done"><i class="ph-bold ph-check" aria-hidden="true"></i><span>Keranjang</span></span>
                <span class="checkout-step-line" aria-hidden="true"></span>
                <span class="checkout-step checkout-step-active"><span class="checkout-step-number">2</span><span>Pembayaran</span></span>
                <span class="checkout-step-line" aria-hidden="true"></span>
                <span class="checkout-step"><span class="checkout-step-number">3</span><span>Selesai</span></span>
            </div>
        </header>

        <div class="checkout-layout">
            <div class="checkout-main">
                <section class="checkout-card">
                    <div class="checkout-section-heading">
                        <span class="checkout-section-number">01</span>
                        <div>
                            <h2>Informasi pemesan</h2>
                            <p>Pastikan kontak yang diberikan bisa dihubungi.</p>
                        </div>
                    </div>
                @if($errors->any())
                    <div role="alert" class="checkout-error">
                        {{ $errors->first() }}
                    </div>
                @endif

                    <div class="checkout-fields">
                    <label class="checkout-field">
                        Nama pemesan
                        <input name="customer_name" required minlength="2" maxlength="120"
                            value="{{ old('customer_name', \App\Support\AuthenticatedUser::get()?->name) }}"
                            autocomplete="name"
                            placeholder="Nama lengkap"
                            class="checkout-input">
                    </label>
                    <label class="checkout-field">
                        Nomor WhatsApp @guest<span class="text-rose-600">(wajib)</span>@endguest
                        <input name="customer_whatsapp" type="tel" minlength="8" maxlength="32"
                            value="{{ old('customer_whatsapp') }}" placeholder="08xxxxxxxxxx"
                            autocomplete="tel"
                            class="checkout-input">
                    </label>
                    <label class="checkout-field checkout-field-wide">
                        Catatan untuk kedai <span class="font-normal text-slate-500">(opsional)</span>
                        <textarea name="customer_note" maxlength="500" rows="3"
                            placeholder="Contoh: permintaan khusus untuk pesanan"
                            class="checkout-input checkout-textarea">{{ old('customer_note') }}</textarea>
                    </label>
                    </div>
                </section>

                <section class="checkout-card" data-payment-selector>
                    <div class="checkout-section-heading">
                        <span class="checkout-section-number">02</span>
                        <div>
                            <h2>Pilih pembayaran</h2>
                            <p>Pilih salah satu metode di bawah ini.</p>
                        </div>
                    </div>

                    <fieldset class="checkout-payment-options">
                        <legend class="sr-only">Metode pembayaran</legend>
                    @if($paymentQrAvailable)
                        <label class="checkout-payment-option{{ $selectedPaymentMethod === 'qris' ? ' is-selected' : '' }}">
                            <input type="radio" name="payment_method" value="qris"
                                @checked($selectedPaymentMethod === 'qris') required>
                            <span class="checkout-payment-icon"><i class="ph ph-qr-code" aria-hidden="true"></i></span>
                            <span class="checkout-payment-copy">
                                <span class="checkout-payment-title">QRIS</span>
                                <span class="checkout-payment-description">Pindai kode QR kedai</span>
                            </span>
                            <i class="ph-fill ph-check-circle checkout-payment-check" aria-hidden="true"></i>
                        </label>
                    @endif

                    <label class="checkout-payment-option{{ $selectedPaymentMethod === 'transfer' ? ' is-selected' : '' }}">
                        <input type="radio" name="payment_method" value="transfer"
                            @checked($selectedPaymentMethod === 'transfer') required>
                        <span class="checkout-payment-icon"><i class="ph ph-bank" aria-hidden="true"></i></span>
                        <span class="checkout-payment-copy">
                            <span class="checkout-payment-title">Transfer</span>
                            <span class="checkout-payment-description">Transfer sesuai instruksi kedai</span>
                        </span>
                        <i class="ph-fill ph-check-circle checkout-payment-check" aria-hidden="true"></i>
                    </label>

                    <label class="checkout-payment-option{{ $selectedPaymentMethod === 'cash' ? ' is-selected' : '' }}">
                        <input type="radio" name="payment_method" value="cash"
                            @checked($selectedPaymentMethod === 'cash') required>
                        <span class="checkout-payment-icon"><i class="ph ph-money" aria-hidden="true"></i></span>
                        <span class="checkout-payment-copy">
                            <span class="checkout-payment-title">Tunai di kedai</span>
                            <span class="checkout-payment-description">Bayar saat mengambil pesanan</span>
                        </span>
                        <i class="ph-fill ph-check-circle checkout-payment-check" aria-hidden="true"></i>
                    </label>
                    </fieldset>

                @if($paymentQrAvailable)
                    <div data-payment-panel="qris" @if($selectedPaymentMethod !== 'qris') hidden @endif class="checkout-qris-panel">
                        <div class="checkout-qris-image">
                                <img src="{{ Storage::disk('public')->url($cafe->payment_qr_path) }}"
                                    alt="QRIS {{ $cafe->name }}"
                                    class="checkout-qris-img"
                                    loading="eager">
                        </div>
                        <div class="checkout-qris-copy">
                            <span class="checkout-qris-tag"><i class="ph-fill ph-shield-check" aria-hidden="true"></i> Pembayaran aman</span>
                            <h3>Pindai QRIS untuk membayar</h3>
                            <p>Pastikan nama penerima sesuai dengan kedai. Setelah membayar, buat pesanan dan kirim bukti pembayaran agar dapat diverifikasi.</p>
                            <p class="checkout-qris-note">QRIS statis perlu dikonfirmasi manual oleh kedai.</p>
                        </div>
                    </div>
                @endif

                <div data-payment-panel="transfer" @if($selectedPaymentMethod !== 'transfer') hidden @endif class="checkout-transfer-panel">
                    <h3><i class="ph ph-bank" aria-hidden="true"></i> Instruksi transfer</h3>
                    @include('orders.partials.bank-transfer-details', ['shop' => $cafe])
                </div>

                @if(!$paymentQrAvailable)
                    <div role="status" class="checkout-payment-notice">
                        QRIS kedai saat ini belum tersedia. Silakan pilih transfer bank atau tunai.
                        @if($whatsappUrl)
                            <a href="{{ $whatsappUrl }}" target="_blank" rel="noopener noreferrer">Hubungi kedai via WhatsApp</a>
                        @endif
                    </div>
                @endif
                </section>
            </div>

            <aside class="checkout-summary">
                <div class="checkout-summary-heading">
                    <span class="checkout-summary-icon"><i class="ph-fill ph-receipt" aria-hidden="true"></i></span>
                    <div>
                        <p>Ringkasan pesanan</p>
                        <h2>{{ $cafe->name }}</h2>
                    </div>
                </div>
                <div class="checkout-pickup-note"><i class="ph-fill ph-storefront" aria-hidden="true"></i> Diambil langsung di kedai</div>

                <div class="checkout-order-items">
                @foreach($items as $item)
                    @php
                        $quantity = (int) ($cart['items'][$item->id] ?? 0);
                        $lineTotal = (float) $item->price * $quantity;
                    @endphp
                    <div class="checkout-order-item">
                        <span class="checkout-item-quantity">{{ $quantity }}×</span>
                        <div class="checkout-item-copy">
                            <p>{{ $item->name }}</p>
                            <span>Rp{{ number_format((float) $item->price, 0, ',', '.') }} / item</span>
                        </div>
                        <strong>Rp{{ number_format($lineTotal, 0, ',', '.') }}</strong>
                    </div>
                @endforeach
                </div>

                <div class="checkout-totals">
                    <div><span>Subtotal</span><span>Rp{{ number_format($total, 0, ',', '.') }}</span></div>
                    <div><span>Biaya pengiriman</span><span>Rp0</span></div>
                    <div class="checkout-grand-total"><strong>Total pembayaran</strong><strong>Rp{{ number_format($total, 0, ',', '.') }}</strong></div>
                </div>

                <p class="checkout-summary-help"><i class="ph-fill ph-info" aria-hidden="true"></i> Status pembayaran diperbarui setelah kedai memverifikasinya.</p>
                <button type="submit" class="checkout-submit"><span>Buat pesanan</span><i class="ph-bold ph-arrow-right" aria-hidden="true"></i></button>
                <p class="checkout-secure-note"><i class="ph-fill ph-lock-key" aria-hidden="true"></i> Informasi pembayaran ditangani langsung oleh kedai.</p>
            </aside>
        </div>
    </div>
</form>
@endsection
