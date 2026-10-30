@extends('layouts.app')
@section('title', 'Riwayat Pesanan - WadahNgopi')
@section('content')
<div class="mx-auto max-w-4xl px-4 py-10"><h1 class="mb-6 text-3xl font-black text-[#2C1810]">Riwayat Pesanan</h1>
    <div class="space-y-3">
        @forelse($orders as $order)
            <a href="{{ route('orders.show', $order->public_code) }}" class="flex flex-wrap items-center justify-between gap-3 rounded-2xl border border-slate-200 bg-white p-4 no-underline">
                <div><strong class="text-[#2C1810]">{{ $order->shop_name }}</strong><p class="text-xs text-slate-500">{{ $order->created_at->format('d/m/Y H:i') }} · {{ $order->public_code }}</p><p class="mt-1 text-xs text-slate-600">{{ $order->items->map(fn ($item) => $item->name_snapshot.' × '.$item->quantity)->implode(' · ') }}</p></div>
                <div class="text-right"><strong class="text-amber-800">Rp{{ number_format((float)$order->total,0,',','.') }}</strong><p class="text-xs text-slate-500">{{ $order->status_label }}</p></div>
            </a>
        @empty
            <div class="rounded-2xl bg-white p-8 text-center text-slate-500">Belum ada pesanan di akun ini.</div>
        @endforelse
    </div>
    <div class="mt-5">{{ $orders->links() }}</div>
</div>
@endsection
