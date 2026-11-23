@extends('layouts.app')
@section('title', 'Checkout - '.$cafe->name)
@section('content')
@php
    $selectedPaymentMethod = old('payment_method', $paymentQrAvailable ? 'qris' : 'transfer');
    $selectedFulfillmentMethod = old('fulfillment_method', 'pickup');
    if (! $deliveryAvailable) {
        $selectedFulfillmentMethod = 'pickup';
    }
    $total = $items->sum(fn ($item) => (float) $item->price * (int) ($cart['items'][$item->id] ?? 0))
        + $addonsByParent->flatten(1)->sum(fn ($item) => (float) $item->price * $item->cart_quantity);
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
                        Nomor WhatsApp <span class="text-rose-600">(wajib)</span>
                        <input name="customer_whatsapp" type="tel" required minlength="8" maxlength="32"
                            value="{{ old('customer_whatsapp', \App\Support\AuthenticatedUser::get()?->phone_number) }}" placeholder="08xxxxxxxxxx"
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

                @if($cafe instanceof \App\Models\Cafe)
                    <section class="checkout-card" data-fulfillment-selector>
                        <div class="checkout-section-heading">
                            <span class="checkout-section-number">02</span>
                            <div>
                                <h2>Cara menerima pesanan</h2>
                                <p>Pilih pengantaran atau ambil sendiri di kafe.</p>
                            </div>
                        </div>
                        <fieldset class="checkout-payment-options">
                            <legend class="sr-only">Cara menerima pesanan</legend>
                            <label class="checkout-payment-option{{ $selectedFulfillmentMethod === 'pickup' ? ' is-selected' : '' }}">
                                <input type="radio" name="fulfillment_method" value="pickup" @checked($selectedFulfillmentMethod === 'pickup') required>
                                <span class="checkout-payment-icon"><i class="ph ph-storefront" aria-hidden="true"></i></span>
                                <span class="checkout-payment-copy">
                                    <span class="checkout-payment-title">Ambil sendiri di kafe</span>
                                    <span class="checkout-payment-description">Tunjukkan kode pesanan saat mengambil.</span>
                                </span>
                                <i class="ph-fill ph-check-circle checkout-payment-check" aria-hidden="true"></i>
                            </label>
                            @if($deliveryAvailable)
                                <label class="checkout-payment-option{{ $selectedFulfillmentMethod === 'delivery' ? ' is-selected' : '' }}">
                                    <input type="radio" name="fulfillment_method" value="delivery" @checked($selectedFulfillmentMethod === 'delivery') required>
                                    <span class="checkout-payment-icon"><i class="ph ph-moped-front" aria-hidden="true"></i></span>
                                    <span class="checkout-payment-copy">
                                        <span class="checkout-payment-title">Diantar</span>
                                        <span class="checkout-payment-description">Ongkir dihitung dari jarak rute dan tarif kafe.</span>
                                    </span>
                                    <i class="ph-fill ph-check-circle checkout-payment-check" aria-hidden="true"></i>
                                </label>
                            @else
                                <p class="rounded-xl bg-slate-50 p-3 text-sm text-slate-700">Pengantaran belum tersedia karena kafe belum mengatur tarif ongkirnya.</p>
                            @endif
                        </fieldset>
                        <label class="checkout-field" data-delivery-address-field>
                            Alamat pengantaran
                            <textarea name="delivery_address" data-delivery-address rows="3" maxlength="1000"
                                @if($selectedFulfillmentMethod === 'delivery') required @endif
                                placeholder="Alamat lengkap, termasuk patokan bila perlu"
                                class="checkout-input checkout-textarea">{{ old('delivery_address') }}</textarea>
                        </label>
                        @if($deliveryAvailable)
                            <p class="mt-3 rounded-xl bg-amber-50 p-3 text-sm text-amber-900" data-delivery-fee-note>
                                Mitra akan mengukur jarak rute di Google Maps secara manual. Ongkir dihitung dari biaya dasar + (jarak km dibulatkan ke atas × tarif per km), lalu ditambahkan ke total sebelum pembayaran.
                            </p>
                        @endif
                    </section>
                @endif

                <section class="checkout-card" data-payment-selector>
                    <div class="checkout-section-heading">
                        <span class="checkout-section-number">{{ $cafe instanceof \App\Models\Cafe ? '03' : '02' }}</span>
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
                            <span class="checkout-payment-description">Nomor rekening mitra tampil setelah pesanan dibuat</span>
                        </span>
                        <i class="ph-fill ph-check-circle checkout-payment-check" aria-hidden="true"></i>
                    </label>

                    <label data-payment-cash-option class="checkout-payment-option{{ $selectedPaymentMethod === 'cash' ? ' is-selected' : '' }}">
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
                    <div data-payment-panel="qris" @if($selectedPaymentMethod !== 'qris') hidden @endif class="checkout-payment-notice" role="status">
                        Kode QRIS akan ditampilkan di halaman menunggu pembayaran setelah pesanan dibuat. Bayar sesuai total di halaman tersebut, lalu unggah bukti pembayaran.
                    </div>
                @endif

                <div data-payment-panel="transfer" @if($selectedPaymentMethod !== 'transfer') hidden @endif class="checkout-transfer-panel">
                    <h3><i class="ph ph-bank" aria-hidden="true"></i> Instruksi transfer</h3>
                    <p>Setelah pesanan dibuat, halaman menunggu pembayaran menampilkan nama mitra, bank, dan nomor rekening tujuan. Transfer sesuai total yang tercantum di halaman tersebut, lalu unggah bukti pembayaran.</p>
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
                <div class="checkout-pickup-note" data-fulfillment-summary>
                    <i class="ph-fill {{ $selectedFulfillmentMethod === 'delivery' ? 'ph-moped-front' : 'ph-storefront' }}" aria-hidden="true"></i>
                    {{ $selectedFulfillmentMethod === 'delivery' ? 'Diantar ke alamat yang kamu berikan' : 'Diambil langsung di kedai' }}
                </div>

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
                    @foreach($addonsByParent->get($item->id, collect()) as $addon)
                        @php $addonLineTotal = (float) $addon->price * $addon->cart_quantity; @endphp
                        <div class="checkout-order-item pl-7">
                            <span class="checkout-item-quantity">{{ $addon->cart_quantity }}×</span>
                            <div class="checkout-item-copy">
                                <p>+ {{ $addon->addon_name }}</p>
                                <span>Tambahan untuk {{ $item->name }} · Rp{{ number_format((float) $addon->price, 0, ',', '.') }} / item</span>
                            </div>
                            <strong>Rp{{ number_format($addonLineTotal, 0, ',', '.') }}</strong>
                        </div>
                    @endforeach
                @endforeach
                </div>

                <div class="checkout-totals">
                    <div><span>Subtotal</span><span>Rp{{ number_format($total, 0, ',', '.') }}</span></div>
                    <div><span>Biaya pengiriman</span><span data-checkout-delivery-fee>{{ $selectedFulfillmentMethod === 'delivery' ? 'Dikonfirmasi kafe' : 'Rp0' }}</span></div>
                    <div class="checkout-grand-total"><strong>Total pembayaran</strong><strong>Rp{{ number_format($total, 0, ',', '.') }}</strong></div>
                </div>

                <p class="checkout-summary-help"><i class="ph-fill ph-info" aria-hidden="true"></i> @if($selectedFulfillmentMethod === 'delivery')Total akhir dan ongkir akan dikonfirmasi kafe sebelum pembayaran.@else Status pembayaran diperbarui setelah kedai memverifikasinya.@endif</p>
                <button type="submit" class="checkout-submit"><span>Buat pesanan</span><i class="ph-bold ph-arrow-right" aria-hidden="true"></i></button>
                <p class="checkout-secure-note"><i class="ph-fill ph-lock-key" aria-hidden="true"></i> Informasi pembayaran ditangani langsung oleh kedai.</p>
            </aside>
        </div>
    </div>
</form>
@endsection
