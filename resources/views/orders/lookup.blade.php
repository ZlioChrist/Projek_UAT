@extends('layouts.app')
@section('title', 'Lacak Pesanan - WadahNgopi')
@section('content')
<div class="mx-auto max-w-xl px-4 py-16">
    <div class="rounded-3xl border border-slate-200 bg-white p-7 shadow-sm">
        <p class="text-xs font-black uppercase tracking-widest text-amber-700">Tanpa akun pun bisa</p><h1 class="mb-2 text-3xl font-black text-[#2C1810]">Lacak pesanan</h1>
        <p class="mb-6 text-sm leading-relaxed text-slate-600">Masukkan kode dari nota digital. Kode ini juga digunakan kedai untuk mencocokkan pesanan saat pengambilan, jadi jangan bagikan ke orang lain.</p>
        @if($errors->any())<div class="mb-4 rounded-xl bg-rose-50 p-3 text-rose-800">{{ $errors->first() }}</div>@endif
        <form method="POST" action="{{ route('orders.lookup.submit') }}" class="space-y-4">@csrf
            <label class="block text-sm font-bold">Kode pesanan<input name="code" required minlength="8" maxlength="20" autocomplete="off" class="mt-1 w-full rounded-xl border border-slate-300 p-3 uppercase" placeholder="CONTOH: AB12CD34EF56"></label>
            <button class="w-full rounded-full bg-amber-600 px-5 py-3 font-black text-white">Lihat status pesanan</button>
        </form>
    </div>
</div>
@endsection
