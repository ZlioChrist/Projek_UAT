@extends('layouts.app')
@section('title', 'Checkout - '.$cafe->name)
@section('content')
<div class="mx-auto grid max-w-5xl gap-6 px-4 py-10 lg:grid-cols-[1.2fr_.8fr]">
    <section class="rounded-3xl border border-slate-200 bg-white p-6">
        <p class="text-xs font-black uppercase tracking-widest text-amber-700">Ambil di kedai</p><h1 class="mb-5 text-3xl font-black text-[#2C1810]">Checkout</h1>
        @if($errors->any())<div class="mb-4 rounded-xl bg-rose-50 p-3 text-sm text-rose-800">{{ $errors->first() }}</div>@endif
        <form method="POST" action="{{ $cafe instanceof \App\Models\Cafe ? route('checkout.store', $cafe->slug) : route('roastery.checkout.store', $cafe->slug) }}" class="space-y-4">
            @csrf
            <input type="hidden" name="checkout_key" value="{{ $checkoutKey }}">
            <label class="block text-sm font-bold">Nama pemesan<input name="customer_name" required minlength="2" maxlength="120" value="{{ old('customer_name', auth()->user()?->name) }}" class="mt-1 w-full rounded-xl border border-slate-300 p-3"></label>
            <label class="block text-sm font-bold">Nomor WhatsApp @guest<span class="text-rose-600">(wajib)</span>@endguest<input name="customer_whatsapp" type="tel" minlength="8" maxlength="32" value="{{ old('customer_whatsapp') }}" placeholder="08xxxxxxxxxx" class="mt-1 w-full rounded-xl border border-slate-300 p-3"></label>
            <label class="block text-sm font-bold">Catatan untuk kedai <span class="font-normal text-slate-500">(opsional)</span><textarea name="customer_note" maxlength="500" rows="3" class="mt-1 w-full rounded-xl border border-slate-300 p-3">{{ old('customer_note') }}</textarea></label>
            <fieldset class="space-y-2"><legend class="text-sm font-bold">Metode pembayaran</legend>
                @if($cafe->payment_qr_path)
                    <label class="flex items-start gap-3 rounded-xl border border-slate-200 p-3"><input type="radio" name="payment_method" value="qris" @checked(old('payment_method', 'qris') === 'qris') required><span><strong>QRIS</strong><span class="block text-xs font-normal text-slate-500">Gunakan QR statis yang disediakan kedai; pembayaran diperiksa manual oleh kedai.</span></span></label>
                @endif
                <label class="flex items-start gap-3 rounded-xl border border-slate-200 p-3"><input type="radio" name="payment_method" value="transfer" @checked(old('payment_method', $cafe->payment_qr_path ? 'qris' : 'transfer') === 'transfer') required><span><strong>Transfer manual</strong><span class="block text-xs font-normal text-slate-500">Ikuti instruksi kedai, lalu kirim bukti. Sistem tidak terhubung ke bank.</span></span></label>
                <label class="flex items-start gap-3 rounded-xl border border-slate-200 p-3"><input type="radio" name="payment_method" value="cash" @checked(old('payment_method') === 'cash') required><span><strong>Tunai saat di kedai</strong><span class="block text-xs font-normal text-slate-500">Bayar langsung dan tunjukkan kode pesanan kepada staf.</span></span></label>
            </fieldset>
            <div class="rounded-xl bg-amber-50 p-3 text-sm leading-relaxed text-amber-950">Simpan kode pesanan untuk pembayaran dan pengambilan. Status pembayaran diperbarui setelah kedai mencatat atau memverifikasinya.</div>
            <button class="w-full rounded-full bg-[#2C1810] px-6 py-3 font-black text-white">Buat pesanan</button>
        </form>
    </section>
    <aside class="h-fit rounded-3xl bg-slate-50 p-6"><h2 class="mb-4 text-xl font-black">{{ $cafe->name }}</h2>
        @php($total = 0)
        @foreach($items as $item)
            @php($qty = (int)($cart['items'][$item->id] ?? 0))
            @php($line = (float)$item->price * $qty)
            @php($total += $line)
            <div class="flex justify-between gap-3 border-b border-slate-200 py-3 text-sm"><span>{{ $item->name }} × {{ $qty }}</span><strong>Rp{{ number_format($line,0,',','.') }}</strong></div>
        @endforeach
        <div class="mt-4 flex justify-between text-lg font-black"><span>Total</span><span>Rp{{ number_format($total,0,',','.') }}</span></div>
    </aside>
</div>
@endsection
