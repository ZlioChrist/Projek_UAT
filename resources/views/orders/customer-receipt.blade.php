<!doctype html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width,initial-scale=1">
    <title>Struk {{ $order->public_code }}</title>
    <style>
        body{font:14px/1.5 Arial,sans-serif;color:#222;margin:0;background:#f3f4f6}.paper{width:min(100%,420px);margin:24px auto;background:#fff;padding:24px;box-sizing:border-box}.center{text-align:center}.row{display:flex;justify-content:space-between;gap:12px;padding:5px 0}.muted{color:#666;font-size:12px}.code{font:700 22px monospace;letter-spacing:2px}.actions{width:min(100%,420px);margin:0 auto 30px;display:flex;gap:8px}.actions button,.actions a{flex:1;padding:12px;text-align:center;border:0;border-radius:8px;background:#39251c;color:white;font-weight:700;text-decoration:none}
        @media print{body{background:#fff}.paper{margin:0 auto;padding:12px}.actions{display:none}@page{margin:12mm}}
    </style>
</head>
<body>
<main class="paper">
    <header class="center"><h2>{{ $order->shop_name }}</h2><p class="muted">{{ $order->shop?->address }}</p><p class="muted">Struk pesanan ambil di kedai</p></header><hr>
    <p class="muted">Waktu: {{ $order->created_at->format('d/m/Y H:i') }}<br>Pemesan: {{ $order->customer_name }}<br>Status: {{ $order->status_label }}<br>Metode bayar: {{ strtoupper($order->payment_method) }}</p><hr>
    @foreach($order->items as $item)<div class="row"><span>{{ $item->name_snapshot }} × {{ $item->quantity }}</span><strong>Rp{{ number_format((float)$item->line_total,0,',','.') }}</strong></div>@endforeach
    <hr><div class="row"><strong>TOTAL</strong><strong>Rp{{ number_format((float)$order->total,0,',','.') }}</strong></div><hr>
    <p class="center muted">KODE PESANAN / PENGAMBILAN</p><p class="center code">{{ $order->public_code }}</p><p class="center muted">Tunjukkan kode ini kepada staf saat membayar atau mengambil pesanan.</p>
</main>
<div class="actions"><button type="button" onclick="window.print()">Cetak / Simpan PDF</button><a href="{{ route('orders.show', $order->public_code) }}">Kembali</a></div>
</body>
</html>
