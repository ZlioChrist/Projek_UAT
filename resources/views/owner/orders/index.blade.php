@extends('layouts.app')
@section('title', 'Pesanan Kedai - WadahNgopi')
@section('content')
<div class="mx-auto max-w-6xl px-4 py-10">
    <div class="mb-6 flex flex-wrap items-end justify-between gap-4"><div><p class="text-xs font-black uppercase tracking-widest text-amber-700">Dashboard kedai</p><h1 class="text-3xl font-black text-[#2C1810]">Daftar Pesanan</h1></div><div class="flex flex-wrap gap-3"><a href="{{ \App\Filament\Resources\OrderReportResource::getUrl() }}" class="rounded-full bg-emerald-700 px-4 py-2 text-sm font-bold text-white no-underline">Laporan Pesanan</a><a href="{{ url('/admin') }}" class="rounded-full border border-slate-300 px-4 py-2 text-sm font-bold no-underline">Panel kedai</a></div></div>
    @if(session('status'))<div class="mb-4 rounded-xl bg-emerald-50 p-3 font-semibold text-emerald-800">{{ session('status') }}</div>@endif
    @if($errors->any())<div class="mb-4 rounded-xl bg-rose-50 p-3 text-rose-800">{{ $errors->first() }}</div>@endif
    <div class="overflow-x-auto rounded-2xl border border-slate-200 bg-white">
        <table class="w-full min-w-[720px] text-left text-sm"><thead class="bg-slate-50 text-xs uppercase tracking-wider text-slate-500"><tr><th class="p-4">Kode / kedai</th><th class="p-4">Pemesan</th><th class="p-4">Status</th><th class="p-4">Total</th><th class="p-4">Waktu</th><th class="p-4"></th></tr></thead><tbody>
        @forelse($orders as $order)
            <tr class="border-t border-slate-100"><td class="p-4"><strong class="font-mono">{{ $order->display_code }}</strong><p class="text-xs text-slate-500">{{ $order->shop_name }}</p></td><td class="p-4">{{ $order->customer_name }}</td><td class="p-4"><span class="font-bold">{{ $order->status_label }}</span></td><td class="p-4">Rp{{ number_format((float)$order->total,0,',','.') }}</td><td class="p-4">{{ $order->created_at->format('d/m H:i') }}</td><td class="p-4"><a class="font-bold text-amber-800" href="{{ route('owner.orders.show', $order->public_code) }}">Detail / nota</a></td></tr>
        @empty
            <tr><td colspan="6" class="p-8 text-center text-slate-500">Belum ada pesanan.</td></tr>
        @endforelse
        </tbody></table>
    </div>
    <div class="mt-5">{{ $orders->links() }}</div>
</div>
@endsection
