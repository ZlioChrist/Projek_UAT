@extends('layouts.app')
@section('title', 'Keranjang Pesanan - WadahNgopi')
@section('content')
@php
    $cartTotal = $items->sum(fn ($item) => (float) $item->price * $item->cart_quantity)
        + $addonsByParent->flatten(1)->sum(fn ($item) => (float) $item->price * $item->cart_quantity);
@endphp
<main class="wn-customer-page cart-page">
    <a href="{{ auth()->check() ? route('account') : route('explore') }}" class="wn-customer-back">
        <i class="ph ph-arrow-left" aria-hidden="true"></i>
        {{ auth()->check() ? 'Kembali ke akun' : 'Jelajahi kafe' }}
    </a>

    <header class="wn-customer-page-header cart-header">
        <span class="wn-customer-eyebrow"><i class="ph ph-shopping-bag-open" aria-hidden="true"></i> Pesanan di kafe</span>
        <h1>Keranjang</h1>
        <p>Periksa pesananmu. Cara menerima pesanan dapat dipilih saat checkout.</p>
    </header>

    @if(session('status'))<div class="cart-notice cart-notice-success">{{ session('status') }}</div>@endif
    @if($errors->any())<div class="cart-notice cart-notice-error">{{ $errors->first() }}</div>@endif
    @if(!$cafe || $items->isEmpty())
        <div class="wn-customer-empty cart-empty">
            <span aria-hidden="true"><i class="ph ph-basket"></i></span>
            <strong>Keranjang masih kosong</strong>
            <p>Pilih minuman atau produk favoritmu, lalu lanjutkan pesanan di sini.</p>
            <a href="{{ route('explore') }}">Jelajahi kafe</a>
        </div>
    @else
        <div class="cart-shop-bar">
            <strong><i class="ph ph-storefront" aria-hidden="true"></i> {{ $cafe->name }}</strong>
            <form method="POST" action="{{ route('cart.clear') }}">@csrf @method('DELETE')<button class="cart-clear-button">Kosongkan</button></form>
        </div>
        <div id="cart-items-list" data-cart-items data-update-url-template="{{ route('cart.items.update', ['menuItem' => '__MENU_ITEM_ID__']) }}" class="cart-items-list">
            @foreach($items as $item)
                <article data-cart-item="{{ $item->id }}" class="cart-item">
                    <div class="cart-item-details">
                        <h2 data-cart-item-name>{{ $item->name }}</h2>
                        <p data-cart-item-price>Rp{{ number_format((float)$item->price,0,',','.') }}</p>
                    </div>
                    <div class="cart-item-controls">
                        <form method="POST" action="{{ route('cart.items.update', $item->id) }}" class="cart-quantity-form">
                            @csrf @method('PATCH')
                            <input aria-label="Jumlah" name="quantity" type="number" min="0" max="99" value="{{ $item->cart_quantity }}" class="cart-quantity-input">
                            <button class="cart-update-button">Perbarui</button>
                        </form>
                        <strong data-cart-item-subtotal class="cart-item-subtotal">Rp{{ number_format((float)$item->price * $item->cart_quantity,0,',','.') }}</strong>
                    </div>
                </article>
                @foreach($addonsByParent->get($item->id, collect()) as $addon)
                    <article class="cart-item ml-4 border-l-2 border-amber-200 pl-4">
                        <div class="cart-item-details">
                            <h2>+ {{ $addon->addon_name }} <span class="text-sm font-medium text-slate-500">({{ $item->name }})</span></h2>
                            <p>Rp{{ number_format((float) $addon->price, 0, ',', '.') }} / topping</p>
                        </div>
                        <div class="cart-item-controls">
                            <form method="POST" action="{{ route('cart.addons.update', [$item->id, $addon->id]) }}" class="cart-quantity-form">
                                @csrf @method('PATCH')
                                <input aria-label="Jumlah {{ $addon->addon_name }}" name="quantity" type="number" min="0" max="99" value="{{ $addon->cart_quantity }}" class="cart-quantity-input">
                                <button class="cart-update-button">Perbarui</button>
                            </form>
                            <strong class="cart-item-subtotal">Rp{{ number_format((float) $addon->price * $addon->cart_quantity, 0, ',', '.') }}</strong>
                        </div>
                    </article>
                @endforeach
            @endforeach
        </div>
        <div class="cart-total">
            <span>Total</span><strong id="cart-grand-total">Rp{{ number_format($cartTotal, 0, ',', '.') }}</strong>
        </div>
        @if($additionalItems->isNotEmpty())
            <section class="cart-recommendations">
                <div class="cart-recommendations-heading">
                    <p class="cart-recommendations-eyebrow">Belanja di kedai yang sama</p>
                    <h2>Tambah produk lain</h2>
                    <p>Produk akan langsung ditambahkan tanpa meninggalkan keranjang.</p>
                </div>
                <div class="cart-recommendations-list">
                    @foreach($additionalItems as $item)
                        <article data-additional-item="{{ $item->id }}" class="cart-recommendation-item">
                            <div class="min-w-0">
                                @if($item->category)<p class="cart-recommendation-category">{{ $item->category }}</p>@endif
                                <h3>{{ $item->name }}</h3>
                                <p>Rp{{ number_format((float) $item->price, 0, ',', '.') }}</p>
                            </div>
                            <form method="POST" action="{{ $cafe instanceof \App\Models\Cafe ? route('cart.items.add', [$cafe->slug, $item->id]) : route('roastery.cart.items.add', [$cafe->slug, $item->id]) }}" data-cart-add-form data-customer-authenticated="{{ auth()->check() ? 'true' : 'false' }}">
                                @csrf
                                <button type="submit" class="cart-add-button">Tambah</button>
                            </form>
                        </article>
                    @endforeach
                </div>
            </section>
        @endif
        <div class="cart-actions">
            <a href="{{ $cafe instanceof \App\Models\Cafe ? route('cafes.show', $cafe->slug) : route('roastery.show', $cafe->slug) }}" class="cart-back-link">Kembali ke katalog {{ $cafe instanceof \App\Models\Cafe ? 'kafe' : 'roastery' }}</a>
            <a href="{{ $cafe instanceof \App\Models\Cafe ? route('checkout.show', $cafe->slug) : route('roastery.checkout.show', $cafe->slug) }}" class="cart-checkout-button">Lanjut checkout</a>
        </div>
    @endif
</main>
@if($cafe && $items->isNotEmpty())
    @include('orders.partials.cart-add-modal')
@endif
@endsection
