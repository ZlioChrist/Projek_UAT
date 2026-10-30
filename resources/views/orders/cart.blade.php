@extends('layouts.app')
@section('title', 'Keranjang Pesanan - WadahNgopi')
@section('content')
<div class="mx-auto max-w-4xl px-4 py-10">
    <div class="mb-6 flex items-center justify-between gap-3">
        <div><p class="text-xs font-black uppercase tracking-widest text-amber-700">Pesanan ambil di kedai</p><h1 class="text-3xl font-black text-[#2C1810]">Keranjang</h1></div>
        <a href="{{ route('orders.lookup') }}" class="text-sm font-bold text-amber-800">Lacak pesanan</a>
    </div>
    @if(session('status'))<div class="mb-4 rounded-xl bg-emerald-50 p-3 text-emerald-800">{{ session('status') }}</div>@endif
    @if($errors->any())<div class="mb-4 rounded-xl bg-rose-50 p-3 text-rose-800">{{ $errors->first() }}</div>@endif
    @if(!$cafe || $items->isEmpty())
        <div class="rounded-3xl border border-slate-200 bg-white p-8 text-center"><p class="font-bold text-slate-600">Keranjang masih kosong.</p><a class="mt-4 inline-block rounded-full bg-[#2C1810] px-5 py-3 font-bold text-white no-underline" href="{{ route('explore') }}">Cari kedai</a></div>
    @else
        <div class="mb-4 flex items-center justify-between rounded-2xl bg-amber-50 p-4"><strong>{{ $cafe->name }}</strong><form method="POST" action="{{ route('cart.clear') }}">@csrf @method('DELETE')<button class="text-sm font-bold text-rose-700">Kosongkan</button></form></div>
        <div class="space-y-3">
            @foreach($items as $item)
                <article class="flex flex-wrap items-center justify-between gap-4 rounded-2xl border border-slate-200 bg-white p-4">
                    <div><h2 class="font-extrabold text-[#2C1810]">{{ $item->name }}</h2><p class="text-sm text-slate-500">Rp{{ number_format((float)$item->price,0,',','.') }}</p></div>
                    <div class="flex items-center gap-4">
                        <form method="POST" action="{{ route('cart.items.update', $item->id) }}" class="flex items-center gap-2">@csrf @method('PATCH')<input aria-label="Jumlah" name="quantity" type="number" min="0" max="99" value="{{ $item->cart_quantity }}" class="w-20 rounded-lg border border-slate-300 p-2"><button class="rounded-lg bg-amber-100 px-3 py-2 text-sm font-bold">Perbarui</button></form>
                        <strong>Rp{{ number_format((float)$item->price * $item->cart_quantity,0,',','.') }}</strong>
                    </div>
                </article>
            @endforeach
        </div>
        <div class="mt-6 flex flex-wrap items-center justify-end gap-4 rounded-2xl bg-white p-5 shadow-sm"><a href="{{ $cafe instanceof \App\Models\Cafe ? route('checkout.show', $cafe->slug) : route('roastery.checkout.show', $cafe->slug) }}" class="rounded-full bg-amber-600 px-6 py-3 font-black text-white no-underline">Lanjut checkout</a></div>
    @endif
</div>
@endsection
