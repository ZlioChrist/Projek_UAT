@extends('layouts.app')
@section('title', 'Kelola '.$order->display_code.' - WadahNgopi')
@section('content')
@php
    $mapsOrigin = $order->cafe
        ? (filled($order->cafe->latitude) && filled($order->cafe->longitude)
            ? $order->cafe->latitude.','.$order->cafe->longitude
            : ($order->cafe->google_maps_url ?: $order->cafe->address))
        : null;
    $mapsDirectionsUrl = $mapsOrigin && $order->delivery_address
        ? 'https://www.google.com/maps/dir/?'.http_build_query([
            'api' => 1,
            'origin' => $mapsOrigin,
            'destination' => $order->delivery_address,
            'travelmode' => 'driving',
        ])
        : null;
@endphp
<div class="owner-order-page mx-auto max-w-4xl px-4 py-10">
    <a href="{{ route('owner.orders.index') }}" class="text-sm font-bold text-amber-800">← Kembali ke pesanan</a>
    @if(session('status'))<div class="mt-4 rounded-xl bg-emerald-50 p-3 font-semibold text-emerald-800">{{ session('status') }}</div>@endif
    @if($errors->any())<div class="mt-4 rounded-xl bg-rose-50 p-3 text-rose-800">{{ $errors->first() }}</div>@endif
    <div class="mt-5 rounded-3xl border border-slate-200 bg-white p-6 sm:p-8">
        <div class="flex flex-wrap justify-between gap-4"><div><p class="text-xs font-black uppercase tracking-widest text-amber-700">{{ $order->shop_name }}</p><h1 class="font-mono text-3xl font-black text-[#2C1810]">{{ $order->display_code }}</h1><p class="mt-1 text-sm text-slate-500">{{ $order->customer_name }} · {{ $order->customer_whatsapp ?: 'Tanpa nomor WA' }} · {{ $order->created_at->format('d/m/Y H:i') }}</p><p class="mt-1 text-xs text-slate-500">Pembayaran: {{ strtoupper($order->payment_method) }}@if($order->sender_bank) · Bank pengirim: {{ config('payment.transfer_banks.'.$order->sender_bank, $order->sender_bank) }}@endif</p>@if($order->payment_expires_at)<p class="mt-1 text-xs font-bold text-rose-700">Batas pembayaran: {{ $order->payment_expires_at->format('d/m/Y H:i') }}</p>@endif</div><div class="h-fit rounded-xl bg-slate-100 px-4 py-3 font-black">{{ $order->status_label }}</div></div>
        <div class="my-6 space-y-2 border-y border-slate-200 py-5">
            @foreach($order->items as $item)<div class="flex justify-between gap-4 text-sm{{ $item->parent_order_item_id ? ' pl-6 text-slate-600' : '' }}"><span>{{ $item->parent_order_item_id ? '+ ' : '' }}{{ $item->name_snapshot }} × {{ $item->quantity }}</span><strong>Rp{{ number_format((float)$item->line_total,0,',','.') }}</strong></div>@endforeach
            <div class="flex justify-between pt-2 text-sm"><span>Subtotal</span><span>Rp{{ number_format((float)$order->subtotal,0,',','.') }}</span></div>
            @if($order->fulfillment_method === 'delivery')
                <div class="flex justify-between text-sm"><span>Ongkir</span><span>{{ $order->delivery_fee_confirmed_at ? 'Rp'.number_format((float)$order->delivery_fee,0,',','.') : 'Belum ditentukan' }}</span></div>
            @endif
            <div class="flex justify-between pt-2 text-lg font-black"><span>Total</span><span>Rp{{ number_format((float)$order->total,0,',','.') }}</span></div>
        </div>
        <div class="mb-5 rounded-xl bg-slate-50 p-4 text-sm">
            <strong>Cara menerima pesanan:</strong> {{ $order->fulfillment_method === 'delivery' ? 'Diantar' : 'Ambil sendiri di kafe' }}
            @if($order->fulfillment_method === 'delivery')
                <p class="mt-2"><strong>Alamat:</strong> {{ $order->delivery_address }}</p>
                @if($mapsDirectionsUrl)<a class="mt-2 inline-flex font-bold text-amber-800 underline" href="{{ $mapsDirectionsUrl }}" target="_blank" rel="noopener noreferrer">Ukur rute di Google Maps</a>@endif
                @if($order->delivery_distance_km)
                    <p class="mt-2">Jarak yang dipakai: {{ number_format((float) $order->delivery_distance_km, 2, ',', '.') }} km (dibulatkan menjadi {{ (int) ceil((float) $order->delivery_distance_km) }} km).</p>
                @endif
            @endif
        </div>
        @if($order->customer_note)<p class="mb-5 rounded-xl bg-amber-50 p-3 text-sm">Catatan pembeli: {{ $order->customer_note }}</p>@endif

        @if($order->fulfillment_method === 'delivery' && $order->status === \App\Models\Order::STATUS_AWAITING_PAYMENT && !$order->delivery_fee_confirmed_at)
            <section class="mb-6 rounded-2xl border border-amber-200 bg-amber-50 p-5">
                <h2 class="mb-2 font-black">Hitung ongkir sebelum pembayaran</h2>
                @if($order->cafe?->delivery_base_fee !== null && $order->cafe?->delivery_fee_per_km !== null)
                    <p class="mb-3 text-sm">Masukkan jarak berkendara yang ditampilkan Google Maps. Rumus: biaya dasar Rp{{ number_format((int) $order->cafe->delivery_base_fee, 0, ',', '.') }} + (jarak dibulatkan ke atas × Rp{{ number_format((int) $order->cafe->delivery_fee_per_km, 0, ',', '.') }}/km).</p>
                    <form method="POST" action="{{ route('owner.orders.delivery-fee', $order->public_code) }}" class="flex flex-wrap items-end gap-3">
                        @csrf
                        <label class="min-w-[220px] flex-1 text-sm font-bold">Jarak rute dari Google Maps (km)
                            <input name="distance_km" type="number" min="0.01" max="999999.99" step="0.01" required value="{{ old('distance_km') }}" class="mt-1 w-full rounded-xl border border-slate-300 bg-white p-3" placeholder="Contoh: 4.2">
                        </label>
                        <button class="rounded-full bg-amber-700 px-5 py-3 font-black text-white">Hitung ongkir dan perbarui total</button>
                    </form>
                    <p class="mt-2 text-xs text-slate-600">Contoh: jarak 4,2 km dibulatkan menjadi 5 km. Sistem menghitung ongkir dari tarif kafe; tidak ada API berbayar.</p>
                @else
                    <p class="text-sm">Tarif pengantaran belum diatur. Atur biaya dasar dan tarif per km di menu pengaturan kafe sebelum memproses pesanan antar.</p>
                @endif
            </section>
        @endif

        @if($order->status === \App\Models\Order::STATUS_PENDING_VERIFICATION)
            <section class="owner-payment-review mb-6" aria-labelledby="payment-review-title">
                <div class="owner-payment-review-heading">
                    <span class="owner-payment-review-icon" aria-hidden="true"><i class="ph ph-shield-check"></i></span>
                    <div>
                        <h2 id="payment-review-title">Periksa bukti pembayaran</h2>
                        <p>Pastikan dana benar-benar masuk sebelum mengonfirmasi pesanan.</p>
                    </div>
                </div>
                <div class="owner-payment-proof-list">
                    @forelse($order->payments->where('status', 'submitted') as $payment)
                        <article class="owner-payment-proof">
                            <div>
                                <span class="owner-payment-proof-label">Nominal sesuai total pesanan</span>
                                <strong>Rp{{ number_format((float)$payment->amount,0,',','.') }}</strong>
                            </div>
                            <a class="owner-payment-proof-link" href="{{ route('owner.orders.payment-proof', [$order->public_code, $payment->id]) }}">
                                <i class="ph ph-receipt" aria-hidden="true"></i>
                                Buka bukti pembayaran
                            </a>
                        </article>
                    @empty
                        <p class="owner-payment-proof-empty">Belum ada bukti pembayaran yang menunggu pemeriksaan.</p>
                    @endforelse
                </div>
                <div class="owner-payment-review-actions">
                    <form method="POST" action="{{ route('owner.orders.confirm-payment',$order->public_code) }}">
                        @csrf
                        <button class="owner-order-action owner-order-action--confirm" type="submit">
                            <i class="ph ph-check-circle" aria-hidden="true"></i>
                            Konfirmasi dana masuk
                        </button>
                    </form>
                    <form method="POST" action="{{ route('owner.orders.reject-payment',$order->public_code) }}" class="owner-payment-reject-form">
                        @csrf
                        <label class="sr-only" for="payment-rejection-reason">Alasan penolakan</label>
                        <input id="payment-rejection-reason" name="reason" required minlength="3" maxlength="500" placeholder="Tuliskan alasan penolakan">
                        <button class="owner-order-action owner-order-action--cancel" type="submit">
                            <i class="ph ph-x-circle" aria-hidden="true"></i>
                            {{ $order->payment_method === 'qris' ? 'Tolak & batalkan pesanan' : 'Tolak bukti' }}
                        </button>
                    </form>
                </div>
            </section>
        @endif
        @if($order->status === \App\Models\Order::STATUS_AWAITING_PAYMENT && $order->payment_method === 'cash')
            <section class="mb-6 rounded-2xl border border-amber-200 bg-amber-50 p-5"><h2 class="mb-2 font-black">Pembayaran tunai</h2><p class="mb-3 text-sm">Konfirmasi setelah menerima Rp{{ number_format((float)$order->total,0,',','.') }} dan mencocokkan kode {{ $order->display_code }}.</p><form method="POST" action="{{ route('owner.orders.confirm-cash-payment',$order->public_code) }}">@csrf<button class="rounded-full bg-emerald-700 px-5 py-3 font-black text-white">Tunai diterima</button></form></section>
        @endif

        @if($order->status === \App\Models\Order::STATUS_PAID)
            @if($order->printJobs->contains(fn ($job) => $job->state === 'printed'))
                <div class="rounded-xl border border-emerald-200 bg-emerald-50 p-4 text-sm font-semibold text-emerald-900">
                    Pembayaran terverifikasi dan nota sudah tercetak. Atur kesiapan pesanan melalui menu <strong>Atur Pesanan → Laporan Pesanan</strong>.
                    <a href="{{ \App\Filament\Resources\OrderReportResource::getUrl() }}" class="ml-1 font-black underline">Buka laporan</a>
                </div>
            @else
                <div class="rounded-xl border border-amber-200 bg-amber-50 p-4 text-sm font-semibold text-amber-900">Pembayaran lunas. Cetak nota fisik dan catat hasil cetaknya sebelum pesanan dapat disiapkan.</div>
            @endif
        @elseif($order->status === \App\Models\Order::STATUS_PREPARING)
            <div class="rounded-xl border border-amber-200 bg-amber-50 p-4 text-sm font-semibold text-amber-900">Atur status lanjutan pesanan ini melalui menu <strong>Atur Pesanan → Laporan Pesanan</strong>.</div>
        @elseif($order->status === \App\Models\Order::STATUS_READY)
            @if($order->fulfillment_method === 'delivery')
                <div class="rounded-xl bg-sky-50 p-4 text-sm font-semibold text-sky-900">Lanjutkan proses pengiriman dari menu <strong>Atur Pesanan → Laporan Pesanan</strong>.</div>
            @else
                <div class="rounded-2xl bg-emerald-50 p-4 text-sm font-semibold text-emerald-900">Pesanan siap diambil. Cocokkan kode <span class="font-mono font-black">{{ $order->display_code }}</span> melalui menu <strong>Atur Pesanan → Laporan Pesanan</strong> saat pelanggan mengambilnya.</div>
            @endif
        @elseif($order->status === \App\Models\Order::STATUS_DELIVERING)
            <div class="rounded-xl bg-sky-50 p-4 text-sm font-semibold text-sky-900">Setelah pesanan diterima pelanggan, catat serah-terima dari menu <strong>Atur Pesanan → Laporan Pesanan</strong>. Pelanggan tetap diminta mengonfirmasi penerimaan.</div>
        @elseif($order->status === \App\Models\Order::STATUS_AWAITING_RECEIPT_CONFIRMATION)
            <div class="rounded-2xl bg-sky-50 p-4 text-sm font-semibold text-sky-900">Menunggu pelanggan mengonfirmasi pesanan diterima. Sistem akan menyelesaikan otomatis 24 jam setelah pesanan ditandai siap diambil atau mulai diantar jika tidak ada laporan kendala.</div>
        @endif
        @if(in_array($order->status, [\App\Models\Order::STATUS_READY, \App\Models\Order::STATUS_DELIVERING, \App\Models\Order::STATUS_AWAITING_RECEIPT_CONFIRMATION, \App\Models\Order::STATUS_COMPLETED], true))
            <section class="mt-5 rounded-2xl border border-emerald-200 bg-emerald-50 p-4">
                <h2 class="font-black text-emerald-950">Kirim notifikasi WhatsApp ke pelanggan</h2>
                @if($order->customer_whatsapp)
                    @php
                        $customerPhone = preg_replace('/\D+/', '', $order->customer_whatsapp);
                        $notice = match ($order->status) {
                            \App\Models\Order::STATUS_COMPLETED => "Halo {$order->customer_name}, pesanan {$order->display_code} dari {$order->shop_name} telah selesai. Terima kasih sudah memesan!",
                            \App\Models\Order::STATUS_DELIVERING => "Halo {$order->customer_name}, pesanan {$order->display_code} dari {$order->shop_name} sedang diantarkan.",
                            \App\Models\Order::STATUS_AWAITING_RECEIPT_CONFIRMATION => "Halo {$order->customer_name}, pesanan {$order->display_code} dari {$order->shop_name} sudah diserahkan. Mohon konfirmasi penerimaan di halaman pesanan.",
                            default => "Halo {$order->customer_name}, pesanan {$order->display_code} dari {$order->shop_name} sudah siap".($order->fulfillment_method === 'delivery' ? ' untuk diantar.' : ' diambil. Silakan tunjukkan kode pesanan saat mengambil.'),
                        };
                    @endphp
                    <a href="https://wa.me/{{ $customerPhone }}?text={{ rawurlencode($notice) }}" target="_blank" rel="noopener noreferrer" class="mt-3 inline-flex rounded-full bg-emerald-700 px-5 py-3 font-black text-white no-underline">Buka WhatsApp dan kirim pesan</a>
                    <p class="mt-2 text-xs text-emerald-900">WhatsApp akan terbuka dengan pesan terisi. Tekan Kirim di WhatsApp untuk mengirimkannya.</p>
                @else
                    <p class="mt-2 text-sm text-emerald-900">Pelanggan tidak mencantumkan nomor WhatsApp.</p>
                @endif
            </section>
        @endif

        @if($order->canBeCancelled())<form method="POST" action="{{ route('owner.orders.cancel',$order->public_code) }}" class="mt-5">@csrf<input type="hidden" name="reason" value="Dibatalkan oleh kedai."><button class="text-sm font-bold text-rose-700">Batalkan pesanan dan lepas stok reservasi</button></form>@endif
        @if($order->paid_at)<div class="mt-6 flex flex-wrap items-center gap-3 border-t border-slate-200 pt-5"><a href="{{ route('owner.orders.print',$order->public_code) }}" class="rounded-full bg-[#2C1810] px-5 py-3 font-bold text-white no-underline">Lihat / cetak nota</a><form method="POST" action="{{ route('owner.orders.reprint',$order->public_code) }}">@csrf<button class="rounded-full border border-slate-300 px-5 py-3 font-bold">Cetak ulang</button></form></div>@endif
        <section class="mt-7"><h2 class="mb-3 font-black">Riwayat status</h2><ol class="space-y-2">@foreach($order->statusHistories as $history)<li class="rounded-xl bg-slate-50 p-3 text-sm"><strong>{{ $history->to_status }}</strong> · {{ $history->created_at->format('d/m/Y H:i:s') }} @if($history->note)<p class="text-xs text-slate-500">{{ $history->note }}</p>@endif</li>@endforeach</ol></section>
    </div>
</div>
@endsection
