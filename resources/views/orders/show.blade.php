@extends('layouts.app')
@section('title', 'Pesanan '.$order->public_code.' - WadahNgopi')
@section('content')
<div class="mx-auto max-w-3xl px-4 py-10">
    @if(session('status'))<div class="mb-4 rounded-xl bg-emerald-50 p-3 font-semibold text-emerald-800">{{ session('status') }}</div>@endif
    <div class="rounded-3xl border border-slate-200 bg-white p-6 shadow-sm sm:p-8">
        <div class="flex flex-wrap items-start justify-between gap-4 border-b border-dashed border-slate-300 pb-5">
            <div><p class="text-xs font-black uppercase tracking-widest text-amber-700">Nota digital · {{ $order->shop_name }}</p><h1 class="mt-1 text-3xl font-black text-[#2C1810]">Pesanan Anda</h1><p class="mt-1 text-sm text-slate-500">{{ $order->created_at->format('d/m/Y H:i') }} · {{ $order->customer_name }}</p></div>
            <div class="rounded-2xl bg-amber-50 px-4 py-3 text-center"><p class="text-[10px] font-black uppercase tracking-widest text-amber-800">Kode pesanan / pickup</p><strong class="font-mono text-xl tracking-widest text-[#2C1810]">{{ $order->public_code }}</strong></div>
        </div>
        <div class="my-5 rounded-2xl bg-slate-50 p-4" aria-live="polite"><p class="text-xs font-bold uppercase tracking-widest text-slate-500">Status terbaru</p><p id="order-status" data-status="{{ $order->status }}" class="mt-1 text-xl font-black text-[#2C1810]">{{ $order->status_label }}</p><p class="mt-1 text-xs text-slate-500">Halaman ini memperbarui status otomatis.</p></div>
        <div class="space-y-3">
            @foreach($order->items as $item)
                <div class="flex justify-between gap-4 text-sm"><span>{{ $item->name_snapshot }} × {{ $item->quantity }}</span><strong>Rp{{ number_format((float)$item->line_total,0,',','.') }}</strong></div>
            @endforeach
        </div>
        <div class="mt-5 flex justify-between border-t border-slate-200 pt-4 text-lg font-black"><span>Total</span><span>Rp{{ number_format((float)$order->total,0,',','.') }}</span></div>
        @if($order->customer_note)<p class="mt-3 text-sm text-slate-500">Catatan: {{ $order->customer_note }}</p>@endif

        @if($order->status === \App\Models\Order::STATUS_AWAITING_PAYMENT && $order->payment_method === 'cash')
            <div class="mt-7 rounded-2xl border border-amber-200 bg-amber-50 p-5"><h2 class="text-lg font-black text-[#2C1810]">Bayar tunai di {{ $order->shop_name }}</h2><p class="mt-2 text-sm">Bayar Rp{{ number_format((float)$order->total,0,',','.') }} langsung kepada staf. Tunjukkan kode pesanan di atas. Status berubah setelah staf mencatat pembayaran.</p></div>
        @elseif($order->status === \App\Models\Order::STATUS_AWAITING_PAYMENT)
            <div class="mt-7 rounded-2xl border border-amber-200 bg-amber-50 p-5">
                <h2 class="text-lg font-black text-[#2C1810]">Pembayaran {{ $order->payment_method === 'qris' ? 'QRIS' : 'transfer manual' }}</h2>
                @if($order->payment_method === 'qris' && $order->shop?->payment_qr_path)
                    <img src="{{ Storage::disk('public')->url($order->shop->payment_qr_path) }}" alt="QRIS {{ $order->shop_name }}" class="my-4 max-h-72 rounded-xl bg-white p-2">
                @else
                    <p class="mt-3 rounded-xl bg-white p-3 text-sm {{ $order->payment_method === 'qris' ? 'text-rose-700' : '' }}">{{ $order->payment_method === 'qris' ? 'Kedai belum mengunggah QRIS.' : 'Minta instruksi transfer langsung dari kedai.' }}</p>
                @endif
                @if($order->shop?->payment_instructions)<p class="mb-4 text-sm leading-relaxed">{{ $order->shop->payment_instructions }}</p>@endif
                @if($order->payment_method === 'transfer' || $order->shop?->payment_qr_path)
                    <form method="POST" action="{{ route('orders.payment.submit', $order->public_code) }}" enctype="multipart/form-data" class="space-y-3">
                        @csrf
                        @if($errors->any())<div class="rounded-xl bg-rose-100 p-3 text-sm text-rose-800">{{ $errors->first() }}</div>@endif
                        <label class="block text-sm font-bold">Nomor referensi / keterangan pembayaran<input name="reference" required minlength="4" maxlength="120" value="{{ old('reference') }}" class="mt-1 w-full rounded-xl border border-slate-300 p-3"></label>
                        <label class="block text-sm font-bold">Nominal dibayar<input name="amount" type="number" min="1" step="0.01" required value="{{ old('amount', $order->total) }}" class="mt-1 w-full rounded-xl border border-slate-300 p-3"></label>
                        <label class="block text-sm font-bold">Bukti pembayaran (JPG/PNG/WEBP/PDF, maks 5 MB)<input name="proof" type="file" accept="image/jpeg,image/png,image/webp,application/pdf" required class="mt-1 block w-full rounded-xl border border-slate-300 bg-white p-3"></label>
                        <button class="rounded-full bg-amber-700 px-5 py-3 font-black text-white">Kirim untuk diverifikasi</button>
                    </form>
                @endif
            </div>
        @elseif($order->status === \App\Models\Order::STATUS_PENDING_VERIFICATION)
            <div class="mt-6 rounded-2xl bg-sky-50 p-4 text-sm font-semibold text-sky-900">Bukti pembayaran sudah diterima. Pesanan mulai disiapkan setelah kedai mengonfirmasi dana masuk.</div>
        @elseif($order->payment_status === 'paid')
            <div class="mt-6 rounded-2xl bg-emerald-50 p-4 text-sm font-semibold text-emerald-900">Pembayaran tercatat/terverifikasi. Tunjukkan kode di atas saat pesanan siap diambil.</div>
        @endif
        <div class="mt-6 flex flex-wrap gap-3"><a href="{{ route('orders.receipt', $order->public_code) }}" class="rounded-full bg-[#2C1810] px-4 py-2 text-sm font-bold text-white no-underline">Cetak / Simpan struk PDF</a><a href="{{ route('orders.lookup') }}" class="rounded-full border border-slate-300 px-4 py-2 text-sm font-bold text-slate-700 no-underline">Lacak pesanan lain</a><a href="{{ $order->cafe ? route('cafes.show', $order->cafe->slug) : route('roastery.show', $order->roastery->slug) }}" class="rounded-full border border-slate-300 px-4 py-2 text-sm font-bold text-slate-700 no-underline">Kembali ke kedai</a></div>
    </div>
</div>
@endsection
@push('scripts')
<script>
(() => {
    const node = document.getElementById('order-status');
    if (!node) return;
    const endpoint = @json(route('orders.status', $order->public_code));
    let previous = node.dataset.status;
    const poll = async () => {
        try {
            const response = await fetch(endpoint, {headers: {'Accept': 'application/json'}, cache: 'no-store'});
            if (!response.ok) return;
            const data = await response.json();
            node.textContent = data.label;
            node.dataset.status = data.status;
            if (data.status !== previous) window.location.reload();
            previous = data.status;
        } catch (_) { /* retry on the next interval */ }
    };
    const timer = window.setInterval(poll, 3000);
    window.addEventListener('pagehide', () => window.clearInterval(timer), {once: true});
})();
</script>
@endpush
