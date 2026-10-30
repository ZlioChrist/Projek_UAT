@extends('layouts.app')
@section('title', 'Kelola '.$order->public_code.' - WadahNgopi')
@section('content')
<div class="mx-auto max-w-4xl px-4 py-10">
    <a href="{{ route('owner.orders.index') }}" class="text-sm font-bold text-amber-800">← Kembali ke pesanan</a>
    @if(session('status'))<div class="mt-4 rounded-xl bg-emerald-50 p-3 font-semibold text-emerald-800">{{ session('status') }}</div>@endif
    @if($errors->any())<div class="mt-4 rounded-xl bg-rose-50 p-3 text-rose-800">{{ $errors->first() }}</div>@endif
    <div class="mt-5 rounded-3xl border border-slate-200 bg-white p-6 sm:p-8">
        <div class="flex flex-wrap justify-between gap-4"><div><p class="text-xs font-black uppercase tracking-widest text-amber-700">{{ $order->shop_name }}</p><h1 class="font-mono text-3xl font-black text-[#2C1810]">{{ $order->public_code }}</h1><p class="mt-1 text-sm text-slate-500">{{ $order->customer_name }} · {{ $order->customer_whatsapp ?: 'Tanpa nomor WA' }} · {{ $order->created_at->format('d/m/Y H:i') }}</p><p class="mt-1 text-xs text-slate-500">Pembayaran: {{ strtoupper($order->payment_method) }}</p></div><div class="h-fit rounded-xl bg-slate-100 px-4 py-3 font-black">{{ $order->status_label }}</div></div>
        <div class="my-6 space-y-2 border-y border-slate-200 py-5">
            @foreach($order->items as $item)<div class="flex justify-between gap-4 text-sm"><span>{{ $item->name_snapshot }} × {{ $item->quantity }}</span><strong>Rp{{ number_format((float)$item->line_total,0,',','.') }}</strong></div>@endforeach
            <div class="flex justify-between pt-2 text-lg font-black"><span>Total</span><span>Rp{{ number_format((float)$order->total,0,',','.') }}</span></div>
        </div>
        @if($order->customer_note)<p class="mb-5 rounded-xl bg-amber-50 p-3 text-sm">Catatan pembeli: {{ $order->customer_note }}</p>@endif

        @if($order->status === \App\Models\Order::STATUS_PENDING_VERIFICATION)
            <section class="mb-6 rounded-2xl border border-sky-200 bg-sky-50 p-5"><h2 class="mb-3 font-black">Periksa bukti pembayaran</h2>
                @foreach($order->payments->where('status','submitted') as $payment)
                    <div class="mb-3 rounded-xl bg-white p-3 text-sm"><p>Referensi: <strong>{{ $payment->reference }}</strong> · Nominal: <strong>Rp{{ number_format((float)$payment->amount,0,',','.') }}</strong></p><a class="font-bold text-amber-800" href="{{ route('owner.orders.payment-proof', [$order->public_code, $payment->id]) }}">Buka bukti pembayaran</a></div>
                @endforeach
                <div class="flex flex-wrap gap-3"><form method="POST" action="{{ route('owner.orders.confirm-payment',$order->public_code) }}">@csrf<button class="rounded-full bg-emerald-700 px-5 py-3 font-black text-white">Konfirmasi dana masuk</button></form>
                    <form method="POST" action="{{ route('owner.orders.reject-payment',$order->public_code) }}" class="flex flex-wrap gap-2">@csrf<input name="reason" required minlength="3" maxlength="500" placeholder="Alasan penolakan" class="rounded-full border border-slate-300 px-4 py-2"><button class="rounded-full bg-rose-700 px-5 py-3 font-black text-white">Tolak bukti</button></form></div>
            </section>
        @endif
        @if($order->status === \App\Models\Order::STATUS_AWAITING_PAYMENT && $order->payment_method === 'cash')
            <section class="mb-6 rounded-2xl border border-amber-200 bg-amber-50 p-5"><h2 class="mb-2 font-black">Pembayaran tunai</h2><p class="mb-3 text-sm">Konfirmasi setelah menerima Rp{{ number_format((float)$order->total,0,',','.') }} dan mencocokkan kode {{ $order->public_code }}.</p><form method="POST" action="{{ route('owner.orders.confirm-cash-payment',$order->public_code) }}">@csrf<button class="rounded-full bg-emerald-700 px-5 py-3 font-black text-white">Tunai diterima</button></form></section>
        @endif

        @if($order->status === \App\Models\Order::STATUS_PAID)
            @if($order->printJobs->contains(fn ($job) => $job->state === 'printed'))
                <form method="POST" action="{{ route('owner.orders.status',$order->public_code) }}">@csrf<input type="hidden" name="status" value="preparing"><button class="rounded-full bg-amber-700 px-5 py-3 font-black text-white">Mulai siapkan</button></form>
            @else
                <div class="rounded-xl border border-amber-200 bg-amber-50 p-4 text-sm font-semibold text-amber-900">Pembayaran lunas. Cetak nota fisik dan catat hasil cetaknya sebelum pesanan dapat disiapkan.</div>
            @endif
        @elseif($order->status === \App\Models\Order::STATUS_PREPARING)
            <form method="POST" action="{{ route('owner.orders.status',$order->public_code) }}">@csrf<input type="hidden" name="status" value="ready"><button class="rounded-full bg-sky-700 px-5 py-3 font-black text-white">Tandai siap diambil</button></form>
        @elseif($order->status === \App\Models\Order::STATUS_READY)
            <div class="rounded-2xl bg-emerald-50 p-4 text-sm font-semibold text-emerald-900">Minta pembeli menunjukkan kode <span class="font-mono font-black">{{ $order->public_code }}</span>, lalu cocokkan melalui form serah-terima di dashboard pesanan.</div>
        @endif

        @if($order->canBeCancelled())<form method="POST" action="{{ route('owner.orders.cancel',$order->public_code) }}" class="mt-5">@csrf<input type="hidden" name="reason" value="Dibatalkan oleh kedai."><button class="text-sm font-bold text-rose-700">Batalkan pesanan dan lepas stok reservasi</button></form>@endif
        @if($order->paid_at)<div class="mt-6 flex flex-wrap items-center gap-3 border-t border-slate-200 pt-5"><a href="{{ route('owner.orders.print',$order->public_code) }}" class="rounded-full bg-[#2C1810] px-5 py-3 font-bold text-white no-underline">Lihat / cetak nota</a><form method="POST" action="{{ route('owner.orders.reprint',$order->public_code) }}">@csrf<button class="rounded-full border border-slate-300 px-5 py-3 font-bold">Cetak ulang</button></form></div>@endif
        <section class="mt-7"><h2 class="mb-3 font-black">Riwayat status</h2><ol class="space-y-2">@foreach($order->statusHistories as $history)<li class="rounded-xl bg-slate-50 p-3 text-sm"><strong>{{ $history->to_status }}</strong> · {{ $history->created_at->format('d/m/Y H:i:s') }} @if($history->note)<p class="text-xs text-slate-500">{{ $history->note }}</p>@endif</li>@endforeach</ol></section>
    </div>
</div>
@endsection
