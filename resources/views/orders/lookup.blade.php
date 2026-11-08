@extends('layouts.app')
@section('title', 'Lacak Pesanan - WadahNgopi')
@section('content')
<main class="wn-customer-page wn-lookup-page">
    <a href="{{ route('account') }}" class="wn-customer-back"><i class="ph ph-arrow-left" aria-hidden="true"></i> Kembali ke profil</a>
    <section class="wn-lookup-card">
        <span class="wn-lookup-illustration"><i class="ph ph-package" aria-hidden="true"></i></span>
        <span class="wn-customer-eyebrow">Pesanan kamu</span>
        <h1>Lacak pesanan</h1>
        <p>Masukkan kode pada nota digital untuk melihat status terbaru pesananmu. Jangan bagikan kode ini kepada orang lain.</p>
        @if($errors->any())<div role="alert" class="wn-customer-alert">{{ $errors->first() }}</div>@endif
        <form method="POST" action="{{ route('orders.lookup.submit') }}" class="wn-lookup-form">@csrf
            <label for="order-code">Kode pesanan</label>
            <input id="order-code" name="code" required minlength="8" maxlength="20" autocomplete="off" value="{{ old('code') }}" class="wn-lookup-input" placeholder="Contoh: AB12CD34EF56">
            <button type="submit"><i class="ph ph-magnifying-glass" aria-hidden="true"></i> Lihat status pesanan</button>
        </form>
        <span class="wn-lookup-hint"><i class="ph ph-lock-key" aria-hidden="true"></i> Kode pesanan menjaga informasi tetap aman.</span>
    </section>
</main>
@endsection
