@extends('layouts.app')
@section('title', 'Pesanan '.$order->display_code.' - WadahNgopi')
@section('content')
<div class="mx-auto max-w-3xl px-4 py-10">
    @if(session('status'))<div class="mb-4 rounded-xl bg-emerald-50 p-3 font-semibold text-emerald-800">{{ session('status') }}</div>@endif
    @if($errors->any())<div role="alert" class="mb-4 rounded-xl bg-rose-50 p-3 text-sm font-semibold text-rose-800">{{ $errors->first() }} @if($whatsappUrl)<a href="{{ $whatsappUrl }}" target="_blank" rel="noopener noreferrer" class="underline">Hubungi kedai via WhatsApp</a>@endif</div>@endif
    <div class="rounded-3xl border border-slate-200 bg-white p-6 shadow-sm sm:p-8">
        <div class="flex flex-wrap items-start justify-between gap-4 border-b border-dashed border-slate-300 pb-5">
            <div><p class="text-xs font-black uppercase tracking-widest text-amber-700">Nota digital · {{ $order->shop_name }}</p><h1 class="mt-1 text-3xl font-black text-[#2C1810]">Pesanan Anda</h1><p class="mt-1 text-sm text-slate-500">{{ $order->created_at->format('d/m/Y H:i') }} · {{ $order->customer_name }}</p></div>
            <div class="rounded-2xl bg-amber-50 px-4 py-3 text-center"><p class="text-[10px] font-black uppercase tracking-widest text-amber-800">{{ $order->fulfillment_method === 'delivery' ? 'Kode pesanan' : 'Kode pesanan / pickup' }}</p><strong class="font-mono text-xl tracking-widest text-[#2C1810]">{{ $order->display_code }}</strong></div>
        </div>
        <div class="my-5 rounded-2xl bg-slate-50 p-4" aria-live="polite"><p class="text-xs font-bold uppercase tracking-widest text-slate-500">Status terbaru</p><p id="order-status" data-status="{{ $order->status }}" class="mt-1 text-xl font-black text-[#2C1810]">{{ $order->status_label }}</p><p class="mt-1 text-xs text-slate-500">Halaman ini memperbarui status otomatis.</p></div>
        <div class="space-y-3">
            @foreach($order->items as $item)
                <div class="flex justify-between gap-4 text-sm{{ $item->parent_order_item_id ? ' pl-6 text-slate-600' : '' }}"><span>{{ $item->parent_order_item_id ? '+ ' : '' }}{{ $item->name_snapshot }} × {{ $item->quantity }}</span><strong>Rp{{ number_format((float)$item->line_total,0,',','.') }}</strong></div>
            @endforeach
        </div>
        <div class="mt-4 space-y-1 border-t border-slate-200 pt-3 text-sm">
            <div class="flex justify-between"><span>Subtotal</span><span>Rp{{ number_format((float)$order->subtotal,0,',','.') }}</span></div>
            @if($order->fulfillment_method === 'delivery')
                <div class="flex justify-between"><span>Ongkir</span><span>{{ $order->delivery_fee_confirmed_at ? 'Rp'.number_format((float)$order->delivery_fee,0,',','.') : 'Menunggu konfirmasi kafe' }}</span></div>
                @if($order->delivery_distance_km)<p class="text-xs text-slate-500">Jarak rute: {{ number_format((float) $order->delivery_distance_km, 2, ',', '.') }} km (dibulatkan ke {{ (int) ceil((float) $order->delivery_distance_km) }} km).</p>@endif
            @endif
        </div>
        <div class="mt-5 flex justify-between border-t border-slate-200 pt-4 text-lg font-black"><span>Total</span><span>Rp{{ number_format((float)$order->total,0,',','.') }}</span></div>
        <div class="mt-3 rounded-xl bg-slate-50 p-3 text-sm">
            <strong>{{ $order->fulfillment_method === 'delivery' ? 'Diantar ke:' : 'Diambil sendiri di kafe' }}</strong>
            @if($order->fulfillment_method === 'delivery')<p class="mt-1">{{ $order->delivery_address }}</p>@endif
        </div>
        @if($order->customer_note)<p class="mt-3 text-sm text-slate-500">Catatan: {{ $order->customer_note }}</p>@endif

        @if($order->status === \App\Models\Order::STATUS_AWAITING_PAYMENT && $order->payment_method === 'cash')
            <div class="mt-7 rounded-2xl border border-amber-200 bg-amber-50 p-5">
                <h2 class="text-lg font-black text-[#2C1810]">Bayar tunai di {{ $order->shop_name }}</h2>
                <p class="mt-2 text-sm">Bayar Rp{{ number_format((float)$order->total,0,',','.') }} langsung kepada staf. Tunjukkan kode pesanan di atas. Status berubah setelah staf mencatat pembayaran.</p>
                @if($order->payment_expires_at)
                    <p class="mt-3 text-sm font-bold text-rose-800">Reservasi pesanan berlaku sampai {{ $order->payment_expires_at->format('d/m/Y H:i') }}.</p>
                    <p id="payment-countdown" data-expires="{{ $order->payment_expires_at->toIso8601String() }}" class="mt-1 text-sm font-black text-rose-800" aria-live="polite"></p>
                @endif
            </div>
        @elseif($order->status === \App\Models\Order::STATUS_AWAITING_PAYMENT && $order->fulfillment_method === 'delivery' && !$order->delivery_fee_confirmed_at)
            <div class="mt-7 rounded-2xl border border-amber-200 bg-amber-50 p-5">
                <h2 class="text-lg font-black text-[#2C1810]">Menunggu kesepakatan ongkir</h2>
                <p class="mt-2 text-sm">Kafe akan mengisi ongkir dan memperbarui total pesanan. Kamu bisa membayar dan mengirim bukti setelah total akhir tampil di halaman ini.</p>
                @if($order->payment_expires_at)
                    <p class="mt-3 text-sm font-bold text-rose-800">Selesaikan pembayaran sebelum {{ $order->payment_expires_at->format('d/m/Y H:i') }} setelah ongkir dikonfirmasi.</p>
                @endif
            </div>
        @elseif($order->status === \App\Models\Order::STATUS_AWAITING_PAYMENT)
            <div class="mt-7 rounded-2xl border border-amber-200 bg-amber-50 p-5">
                <h2 class="text-lg font-black text-[#2C1810]">Pembayaran {{ $order->payment_method === 'qris' ? 'QRIS' : 'transfer' }}</h2>
                @if($order->payment_expires_at)
                    <p class="mt-2 text-sm font-bold text-rose-800">Selesaikan pembayaran dan kirim bukti sebelum {{ $order->payment_expires_at->format('d/m/Y H:i') }}.</p>
                    <p id="payment-countdown" data-expires="{{ $order->payment_expires_at->toIso8601String() }}" class="mt-1 text-sm font-black text-rose-800" aria-live="polite"></p>
                @endif
                @if($order->payment_method === 'qris' && $paymentQrAvailable)
                    <img src="{{ Storage::disk('public')->url($order->shop->payment_qr_path) }}" alt="QRIS {{ $order->shop_name }}" class="my-4 max-h-72 rounded-xl bg-white p-2">
                    <p class="rounded-xl bg-white p-3 text-sm">Bayar tepat <strong>Rp{{ number_format((float)$order->total,0,',','.') }}</strong>. QRIS ini dikelola kedai dan pembayaran tidak diverifikasi otomatis. Unggah bukti setelah membayar; kedai akan mencocokkan dana sebelum mengonfirmasi pesanan.</p>
                    <p class="mt-2 rounded-xl bg-rose-50 p-3 text-xs font-semibold text-rose-800">QRIS kedai dapat berupa kode statis. Jangan lanjutkan pembayaran setelah batas waktu; bila terlanjur membayar saat pesanan kedaluwarsa, segera hubungi kedai untuk penanganan manual.</p>
                @else
                    <p class="mt-3 rounded-xl bg-white p-3 text-sm {{ $order->payment_method === 'qris' ? 'text-rose-700' : '' }}">{{ $order->payment_method === 'qris' ? 'QRIS kedai belum tersedia atau tidak dapat dibuka. Jangan membayar melalui QRIS lama; hubungi kedai untuk memastikan kode terbaru.' : 'Minta instruksi transfer langsung dari kedai.' }}</p>
                @endif
                @if($order->payment_method === 'transfer')
                    <div class="mb-4 rounded-xl bg-white p-3 text-sm leading-relaxed">
                        <strong>Transfer ke rekening mitra</strong>
                        <p class="my-2">Nominal: <strong>Rp{{ number_format((float)$order->total,0,',','.') }}</strong></p>
                        <p class="my-2 text-xs text-slate-600">Transfer sesuai rekening mitra berikut. Jika ada biaya admin bank, biaya tersebut tidak termasuk total pesanan.</p>
                        @include('orders.partials.bank-transfer-details', ['shop' => $order->shop])
                    </div>
                @endif
                @if($order->payment_method === 'transfer' || $paymentQrAvailable)
                    <form method="POST" action="{{ route('orders.payment.submit', $order->public_code) }}" enctype="multipart/form-data" class="space-y-3" data-payment-proof-form>
                        @csrf
                        @if($errors->any())<div class="rounded-xl bg-rose-100 p-3 text-sm text-rose-800">{{ $errors->first() }}</div>@endif
                        <p class="rounded-xl bg-sky-50 p-3 text-sm text-sky-900">Cukup unggah bukti pembayaran. Nominal yang diperiksa mengikuti total pesanan di sistem. Bukti membantu kafe mencocokkan jumlah, waktu, dan penerima; nomor referensi tidak diminta karena formatnya berbeda-beda antarbank dan bisa salah ketik.</p>
                        <div class="rounded-xl border border-amber-200 bg-amber-50 p-4 text-sm text-amber-950">
                            <h3 class="font-black">Bukti pembayaran seperti apa yang perlu diunggah?</h3>
                            <ol class="mt-2 list-inside list-decimal space-y-1">
                                <li>Setelah pembayaran berhasil, buka riwayat transaksi di aplikasi pembayaran yang kamu gunakan.</li>
                                <li>Ambil screenshot yang memperlihatkan status <strong>berhasil</strong>, nominal, tanggal/waktu, dan penerima.</li>
                                <li>Pastikan nominal sesuai total pesanan dan nama/tujuan mitra terbaca, lalu unggah screenshot tersebut.</li>
                            </ol>
                            <p class="mt-2 text-xs">Jangan unggah bukti berstatus pending/gagal, QR pembayaran, atau screenshot yang tidak terkait. Jangan sertakan PIN, OTP, kata sandi, maupun informasi login. Saldo rekening boleh ditutup.</p>
                        </div>
                        <label class="block text-sm font-bold">Bukti pembayaran (JPG/PNG/WEBP/PDF, maks 5 MB)<input name="proof" type="file" accept="image/jpeg,image/png,image/webp,application/pdf" required class="mt-1 block w-full rounded-xl border border-slate-300 bg-white p-3"></label>
                        <p class="text-xs text-slate-600" data-payment-proof-hint>Pilih dan unggah bukti pembayaran untuk mengaktifkan tombol kirim.</p>
                        <button disabled class="rounded-full bg-amber-700 px-5 py-3 font-black text-white disabled:cursor-not-allowed disabled:opacity-50">Kirim untuk diverifikasi</button>
                    </form>
                @endif
                @if($order->payment_method === 'qris' && !$paymentQrAvailable && $whatsappUrl)
                    <a href="{{ $whatsappUrl }}" target="_blank" rel="noopener noreferrer" class="mt-4 inline-flex rounded-full bg-emerald-700 px-5 py-3 font-bold text-white no-underline">Tanya QRIS terbaru via WhatsApp</a>
                @endif
            </div>
        @elseif($order->status === \App\Models\Order::STATUS_PENDING_VERIFICATION)
            <div class="mt-6 rounded-2xl bg-sky-50 p-4 text-sm font-semibold text-sky-900">Bukti pembayaran sudah diterima dan sedang diperiksa kedai.</div>
        @elseif($order->status === \App\Models\Order::STATUS_READY && $order->fulfillment_method === 'pickup')
            <div class="mt-6 rounded-2xl bg-sky-50 p-4 text-sm font-semibold text-sky-900">
                Pesanan siap diambil. Tunjukkan kode di atas kepada staf. Batas 24 jam dihitung sejak pesanan ditandai siap: {{ $order->receipt_confirmation_deadline_started_at?->copy()->addHours(24)->format('d/m/Y H:i') }}.
                @if($orderIssueWhatsappUrl)<a href="{{ $orderIssueWhatsappUrl }}" target="_blank" rel="noopener noreferrer" class="ml-1 underline">Laporkan jika ada kendala</a>@endif
            </div>
        @elseif($order->status === \App\Models\Order::STATUS_READY && $order->fulfillment_method === 'delivery')
            <div class="mt-6 rounded-2xl bg-sky-50 p-4 text-sm font-semibold text-sky-900">Pesanan siap untuk mulai diantar. Kami akan memberi tahu saat pengantaran dimulai.</div>
        @elseif($order->status === \App\Models\Order::STATUS_DELIVERING)
            <div class="mt-6 rounded-2xl bg-sky-50 p-4 text-sm font-semibold text-sky-900">
                Pesanan sedang dalam pengantaran. Jika belum diterima, laporkan ke kafe sebelum {{ $order->receipt_confirmation_deadline_started_at?->copy()->addHours(24)->format('d/m/Y H:i') }}; jika tidak ada laporan, sistem akan menganggap pesanan diterima dan menyelesaikannya otomatis.
                @if($orderIssueWhatsappUrl)<a href="{{ $orderIssueWhatsappUrl }}" target="_blank" rel="noopener noreferrer" class="ml-1 underline">Laporkan pesanan belum diterima</a>@endif
            </div>
        @elseif($order->status === \App\Models\Order::STATUS_AWAITING_RECEIPT_CONFIRMATION)
            <div class="mt-6 rounded-2xl border border-emerald-200 bg-emerald-50 p-5">
                <h2 class="font-black text-emerald-950">Pesanan sudah diserahkan</h2>
                <p class="mt-2 text-sm text-emerald-900">Konfirmasikan penerimaan jika pesanan sudah sampai. Jika belum diterima atau ada kendala, gunakan menu komplain untuk menghubungi admin kedai.</p>
                <p class="mt-2 text-xs text-emerald-900">Batas konfirmasi: {{ $order->receipt_confirmation_deadline_started_at?->copy()->addHours(24)->format('d/m/Y H:i') }}.</p>
                @if($canCustomerConfirmReceipt)
                    <dialog id="receipt-confirmation-dialog" class="m-auto w-[calc(100%-2rem)] max-w-lg rounded-3xl border-0 p-0 shadow-2xl backdrop:bg-black/50" aria-labelledby="receipt-confirmation-title" aria-describedby="receipt-confirmation-description">
                        <div class="p-6 sm:p-8">
                            <p class="text-xs font-black uppercase tracking-widest text-emerald-700">{{ $order->fulfillment_method === 'delivery' ? 'Konfirmasi pengantaran' : 'Konfirmasi pengambilan' }}</p>
                            <h2 id="receipt-confirmation-title" class="mt-2 text-2xl font-black text-[#2C1810]">Pesanan sudah diterima?</h2>
                            <p id="receipt-confirmation-description" class="mt-2 text-sm text-slate-600">Pilih “Ya” jika pesanan sudah kamu terima. Jika belum atau ada masalah, pilih menu komplain untuk menghubungi admin kedai.</p>
                            <form method="POST" action="{{ route('orders.confirm-received', $order->public_code) }}" class="mt-6">
                                @csrf
                                <button class="w-full rounded-full bg-emerald-700 px-5 py-3 font-black text-white">Ya, pesanan sudah diterima</button>
                            </form>
                            <a href="{{ $orderIssueContactUrl }}" target="_blank" rel="noopener noreferrer" class="mt-3 inline-flex w-full justify-center rounded-full border border-rose-300 bg-rose-50 px-5 py-3 text-center font-bold text-rose-800 no-underline">
                                Tidak, ada kendala — komplain / hubungi kedai
                            </a>
                        </div>
                    </dialog>
                @else
                    <p class="mt-3 text-sm text-emerald-900">Masuk dengan akun pelanggan pemesan untuk mengonfirmasi penerimaan.</p>
                    <a href="{{ $orderIssueContactUrl }}" target="_blank" rel="noopener noreferrer" class="mt-3 inline-flex rounded-full border border-rose-300 bg-rose-50 px-5 py-3 font-bold text-rose-800 no-underline">Komplain / hubungi kedai</a>
                @endif
            </div>
        @elseif($order->status === \App\Models\Order::STATUS_COMPLETED)
            <div class="mt-6 rounded-2xl bg-emerald-50 p-4 text-sm font-semibold text-emerald-900">Pesanan selesai. Terima kasih sudah memesan di {{ $order->shop_name }}.</div>
        @elseif($order->status === \App\Models\Order::STATUS_CANCELLED)
            <div class="mt-6 rounded-2xl border border-rose-200 bg-rose-50 p-4 text-sm font-semibold text-rose-900">
                @if($order->payment_status === 'expired')
                    Batas waktu pembayaran berakhir. Pesanan dibatalkan dan reservasi stok dilepas; pembayaran tidak diterima lagi untuk pesanan ini.
                @else
                    Pesanan telah dibatalkan dan tidak akan diproses.
                @endif
                @if($whatsappUrl)<a href="{{ $whatsappUrl }}" target="_blank" rel="noopener noreferrer" class="ml-1 underline">Hubungi kedai jika Anda sudah terlanjur membayar.</a>@endif
            </div>
        @elseif($order->payment_status === 'paid')
            <div class="mt-6 rounded-2xl bg-emerald-50 p-4 text-sm font-semibold text-emerald-900">Pembayaran terverifikasi dan pesanan sudah masuk daftar kedai untuk diproses. Nota tetap tersedia untuk memeriksa rincian pembayaran.</div>
        @endif
        @if($canCustomerCancel)
            <form method="POST" action="{{ route('orders.cancel', $order->public_code) }}" class="mt-5">
                @csrf
                <p class="mb-2 text-sm text-slate-600">Kamu masih dapat membatalkan pesanan sebelum membayar atau mengirim bukti pembayaran.</p>
                <button type="submit" class="rounded-full border border-rose-200 px-4 py-2 text-sm font-bold text-rose-700 transition hover:bg-rose-50">
                    Batalkan pesanan
                </button>
            </form>
        @endif
        <div class="mt-6 flex flex-wrap gap-3">
            @if(($order->payment_status === 'paid' && $order->paid_at) || ($order->payment_method === 'cash' && $order->status === \App\Models\Order::STATUS_AWAITING_PAYMENT && $order->payment_expires_at?->isFuture()))
                <a href="{{ route('orders.receipt', $order->public_code) }}" class="rounded-full bg-[#2C1810] px-4 py-2 text-sm font-bold text-white no-underline">
                    {{ $order->payment_status === 'paid' ? 'Cetak / Simpan struk PDF' : 'Cetak / Simpan tiket pesanan' }}
                </a>
            @endif
            <a href="{{ route('orders.history') }}" class="rounded-full border border-slate-300 px-4 py-2 text-sm font-bold text-slate-700 no-underline">Riwayat pesanan</a>
            <a href="{{ $order->cafe ? route('cafes.show', $order->cafe->slug) : route('roastery.show', $order->roastery->slug) }}" class="rounded-full border border-slate-300 px-4 py-2 text-sm font-bold text-slate-700 no-underline">Kembali ke kedai</a>
        </div>
    </div>
</div>
@endsection
@push('scripts')
<script>
(() => {
    const node = document.getElementById('order-status');
    if (!node) return;
    const receiptDialog = document.getElementById('receipt-confirmation-dialog');
    if (receiptDialog && typeof receiptDialog.showModal === 'function') receiptDialog.showModal();
    const endpoint = @json(route('orders.status', $order->public_code));
    const countdown = document.getElementById('payment-countdown');
    let countdownTimer;
    if (countdown) {
        const expiresAt = new Date(countdown.dataset.expires).getTime();
        const updateCountdown = () => {
            const seconds = Math.max(0, Math.floor((expiresAt - Date.now()) / 1000));
            if (seconds === 0) {
                countdown.textContent = 'Waktu pembayaran berakhir. Status pesanan sedang diperiksa...';
                return;
            }
            const minutes = Math.floor(seconds / 60);
            countdown.textContent = `Sisa waktu pembayaran: ${minutes}:${String(seconds % 60).padStart(2, '0')}`;
        };
        updateCountdown();
        countdownTimer = window.setInterval(updateCountdown, 1000);
    }
    let previous = node.dataset.status;
    let previousUpdatedAt = @json($order->updated_at?->toIso8601String());
    let previousTotal = @json($order->total);
    const poll = async () => {
        try {
            const response = await fetch(endpoint, {headers: {'Accept': 'application/json'}, cache: 'no-store'});
            if (!response.ok) return;
            const data = await response.json();
            node.textContent = data.label;
            node.dataset.status = data.status;
            if (
                data.status !== previous
                || (previousUpdatedAt && data.updated_at !== previousUpdatedAt)
                || (data.total && data.total !== previousTotal)
            ) window.location.reload();
            previous = data.status;
            previousUpdatedAt = data.updated_at;
            previousTotal = data.total;
        } catch (_) { /* retry on the next interval */ }
    };
    const timer = window.setInterval(poll, 3000);
    window.addEventListener('pagehide', () => {
        window.clearInterval(timer);
        if (countdownTimer) window.clearInterval(countdownTimer);
    }, {once: true});
})();
</script>
@endpush
