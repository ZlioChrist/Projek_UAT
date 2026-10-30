<!doctype html>
<html lang="id"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1"><title>Nota {{ $order->public_code }}</title>
<style>
body{font:14px/1.45 Arial,sans-serif;color:#222;margin:0;background:#f2f2f2}.paper{width:min(100%,380px);margin:24px auto;background:white;padding:24px;box-sizing:border-box}.center{text-align:center}.row{display:flex;justify-content:space-between;gap:12px;padding:5px 0}.code{font:700 20px monospace;letter-spacing:2px}.muted{color:#666;font-size:12px}.actions{width:min(100%,380px);margin:0 auto 32px;display:grid;gap:8px}.actions form{margin:0}.actions button,.actions a{display:block;width:100%;box-sizing:border-box;padding:11px;text-align:center;border:0;border-radius:8px;background:#654321;color:white;font-weight:700;text-decoration:none}.actions .secondary{background:#555}.actions .danger{background:#9f2732}@media print{body{background:white}.paper{margin:0 auto;padding:12px}.actions{display:none}}
</style></head><body>
@php($payload = $job->payload)
<main class="paper"><header class="center"><h2>{{ $payload['cafe_name'] }}</h2><p class="muted">{{ $payload['cafe_address'] }}</p><p class="muted">Nota pesanan ambil di kedai</p></header><hr>
<p class="muted">Waktu: {{ $payload['paid_at'] ? \Illuminate\Support\Carbon::parse($payload['paid_at'])->format('d/m/Y H:i') : $order->created_at->format('d/m/Y H:i') }}<br>Pemesan: {{ $payload['customer_name'] }}<br>Order: {{ $payload['order_code'] }}</p><hr>
@foreach($payload['items'] as $item)<div class="row"><span>{{ $item['name'] }} × {{ $item['quantity'] }}</span><strong>Rp{{ number_format((float)$item['line_total'],0,',','.') }}</strong></div>@endforeach
<hr><div class="row"><strong>TOTAL</strong><strong>Rp{{ number_format((float)$payload['total'],0,',','.') }}</strong></div><hr><p class="center muted">KODE PENGAMBILAN</p><p class="center code">{{ $payload['pickup_code'] }}</p><p class="center muted">Simpan kode ini dan tunjukkan kepada kedai saat mengambil pesanan.</p><p class="center muted">Terima kasih telah memesan.</p></main>
<div class="actions"><button type="button" onclick="window.print()">Buka dialog cetak</button>
<p class="muted">Tandai berhasil hanya setelah nota fisik benar-benar keluar dari printer thermal.</p><form method="POST" action="{{ route('owner.print-jobs.status',$job->id) }}">@csrf<input type="hidden" name="state" value="printed"><button type="submit">Tandai cetak berhasil</button></form>
<form method="POST" action="{{ route('owner.print-jobs.status',$job->id) }}">@csrf<input type="hidden" name="state" value="failed"><input type="hidden" name="last_error" value="Printer offline / kertas habis (dilaporkan staf)"><button class="danger" type="submit">Cetak gagal / perlu dicoba lagi</button></form>
<a class="secondary" href="{{ route('owner.orders.show',$order->public_code) }}">Kembali ke detail pesanan</a></div>
<script>window.addEventListener('load',()=>setTimeout(()=>window.print(),600));</script>
</body></html>
