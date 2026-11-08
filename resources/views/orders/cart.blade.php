@extends('layouts.app')
@section('title', 'Keranjang Pesanan - WadahNgopi')
@section('content')
<main class="cart-page mx-auto max-w-4xl px-4 py-10">
    <div class="cart-header mb-6 flex items-center justify-between gap-3">
        <div><p class="text-xs font-black uppercase tracking-widest text-amber-700">Pesanan ambil di kedai</p><h1 class="text-3xl font-black text-[#2C1810]">Keranjang</h1></div>
        <a href="{{ route('orders.lookup') }}" class="cart-track-link text-sm font-bold text-amber-800">Lacak pesanan</a>
    </div>
    @if(session('status'))<div class="cart-notice cart-notice-success mb-4 rounded-xl p-3">{{ session('status') }}</div>@endif
    @if($errors->any())<div class="cart-notice cart-notice-error mb-4 rounded-xl p-3">{{ $errors->first() }}</div>@endif
    @if(!$cafe || $items->isEmpty())
        <div class="cart-empty rounded-3xl border border-slate-200 bg-white p-8 text-center"><span class="cart-empty-icon" aria-hidden="true"><i class="ph ph-basket"></i></span><p class="font-bold text-slate-600">Keranjang masih kosong.</p><a class="mt-4 inline-block rounded-full bg-[#2C1810] px-5 py-3 font-bold text-white no-underline" href="{{ route('explore') }}">Cari kedai</a></div>
    @else
        <div class="cart-shop-bar mb-4 flex items-center justify-between rounded-2xl p-4"><strong>{{ $cafe->name }}</strong><form method="POST" action="{{ route('cart.clear') }}">@csrf @method('DELETE')<button class="cart-clear-button text-sm font-bold">Kosongkan</button></form></div>
        <div id="cart-items-list" data-cart-items data-update-url-template="{{ route('cart.items.update', ['menuItem' => '__MENU_ITEM_ID__']) }}" class="space-y-3">
            @foreach($items as $item)
                <article data-cart-item="{{ $item->id }}" class="cart-item">
                    <div class="cart-item-details"><h2 data-cart-item-name>{{ $item->name }}</h2><p data-cart-item-price>Rp{{ number_format((float)$item->price,0,',','.') }}</p></div>
                    <div class="cart-item-controls">
                        <form method="POST" action="{{ route('cart.items.update', $item->id) }}" class="cart-quantity-form">@csrf @method('PATCH')<input aria-label="Jumlah" name="quantity" type="number" min="0" max="99" value="{{ $item->cart_quantity }}" class="cart-quantity-input"><button class="cart-update-button">Perbarui</button></form>
                        <strong data-cart-item-subtotal class="cart-item-subtotal">Rp{{ number_format((float)$item->price * $item->cart_quantity,0,',','.') }}</strong>
                    </div>
                </article>
            @endforeach
        </div>
        <div class="cart-total mt-4 flex justify-end rounded-2xl p-4 text-lg font-black">
            <span>Total</span><strong id="cart-grand-total">Rp{{ number_format($items->sum(fn ($item) => (float) $item->price * $item->cart_quantity), 0, ',', '.') }}</strong>
        </div>
        @if($additionalItems->isNotEmpty())
            <section class="cart-recommendations mt-8 rounded-3xl border border-amber-100 bg-white p-5 sm:p-6">
                <div class="mb-4">
                    <p class="text-xs font-black uppercase tracking-widest text-amber-700">Belanja di kedai yang sama</p>
                    <h2 class="mt-1 text-xl font-black text-[#2C1810]">Tambah produk lain</h2>
                    <p class="mt-1 text-sm text-slate-600">Produk akan langsung ditambahkan tanpa meninggalkan keranjang.</p>
                </div>
                <div class="grid gap-3 sm:grid-cols-2">
                    @foreach($additionalItems as $item)
                        <article data-additional-item="{{ $item->id }}" class="cart-recommendation-item flex items-center justify-between gap-3 rounded-2xl border border-slate-200 p-4">
                            <div class="min-w-0">
                                @if($item->category)<p class="text-xs font-bold uppercase text-amber-700">{{ $item->category }}</p>@endif
                                <h3 class="truncate font-bold text-[#2C1810]">{{ $item->name }}</h3>
                                <p class="text-sm text-slate-600">Rp{{ number_format((float) $item->price, 0, ',', '.') }}</p>
                            </div>
                            <form method="POST" action="{{ $cafe instanceof \App\Models\Cafe ? route('cart.items.add', [$cafe->slug, $item->id]) : route('roastery.cart.items.add', [$cafe->slug, $item->id]) }}" data-cart-add-form data-customer-authenticated="{{ auth()->check() ? 'true' : 'false' }}">
                                @csrf
                                <button type="submit" class="cart-add-button rounded-full bg-[#2C1810] px-4 py-2 text-sm font-bold text-white">Tambah</button>
                            </form>
                        </article>
                    @endforeach
                </div>
            </section>
        @endif
        <div class="cart-actions mt-6 flex flex-wrap items-center justify-between gap-4 rounded-2xl p-5">
            <a href="{{ $cafe instanceof \App\Models\Cafe ? route('cafes.show', $cafe->slug) : route('roastery.show', $cafe->slug) }}" class="cart-back-link font-bold no-underline">Kembali ke katalog {{ $cafe instanceof \App\Models\Cafe ? 'kafe' : 'roastery' }}</a>
            <a href="{{ $cafe instanceof \App\Models\Cafe ? route('checkout.show', $cafe->slug) : route('roastery.checkout.show', $cafe->slug) }}" class="cart-checkout-button rounded-full px-6 py-3 font-black text-white no-underline">Lanjut checkout</a>
        </div>
    @endif
</main>
@if($cafe && $items->isNotEmpty())
    @include('orders.partials.cart-add-modal')
@endif
@endsection
