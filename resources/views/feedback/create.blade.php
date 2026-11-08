@extends('layouts.app')
@section('title', 'Masukan Pelanggan - WadahNgopi')
@section('content')
<main class="wn-customer-page wn-feedback-page">
    <a href="{{ route('account') }}#account-feedback" class="wn-customer-back"><i class="ph ph-arrow-left" aria-hidden="true"></i> Kembali ke akun</a>
    <div class="wn-feedback-card">
        <span class="wn-feedback-illustration"><i class="ph ph-chat-circle-dots" aria-hidden="true"></i></span>
        <p class="wn-customer-eyebrow">Suara pelanggan</p>
        <h1>Keluhan, kritik, dan saran</h1>
        <p class="wn-feedback-intro">Ceritakan pengalamanmu agar kafe dan roastery bisa terus berkembang. Masukan hanya dikelola pihak kedai dan admin WadahNgopi.</p>
        @if(session('status'))<div role="status" class="wn-customer-alert is-success">{{ session('status') }}</div>@endif
        @if($errors->any())<div role="alert" class="wn-customer-alert">{{ $errors->first() }}</div>@endif
        <form method="POST" action="{{ $order ? route('orders.feedback.store', $order->public_code) : route('feedback.store') }}" class="wn-feedback-form">
            @csrf
            @if($order)
                <div class="wn-feedback-order">
                    <span><i class="ph ph-storefront" aria-hidden="true"></i></span>
                    <div><small>Pesanan selesai</small><strong>{{ $order->shop_name }}</strong><small>Kode: {{ $order->public_code }}</small></div>
                </div>
            @else
                <label>Kafe atau roastery
                    <select name="shop" required>
                        <option value="">Pilih tempat</option>
                        @foreach($shops as $shop)<option value="{{ $shop['value'] }}" @selected(old('shop') === $shop['value'])>{{ $shop['name'] }}</option>@endforeach
                    </select>
                </label>
            @endif
            <label>Jenis masukan
                <select name="category" required>
                    @foreach(['complaint' => 'Keluhan', 'criticism' => 'Kritik', 'suggestion' => 'Saran', 'praise' => 'Apresiasi', 'other' => 'Lainnya'] as $value => $label)
                        <option value="{{ $value }}" @selected(old('category', 'suggestion') === $value)>{{ $label }}</option>
                    @endforeach
                </select>
            </label>
            <label>Nama<input name="customer_name" required minlength="2" maxlength="120" value="{{ old('customer_name', $order?->customer_name ?? \App\Support\AuthenticatedUser::get()?->name) }}"></label>
            <label>Kontak yang bisa dihubungi<input name="customer_contact" required minlength="5" maxlength="120" value="{{ old('customer_contact', $order?->customer_whatsapp ?? '') }}" placeholder="Nomor WhatsApp atau email"></label>
            <label>Pesan<textarea name="message" required minlength="10" maxlength="3000" rows="6" placeholder="Ceritakan pengalaman atau saranmu...">{{ old('message') }}</textarea></label>
            <button type="submit"><i class="ph ph-paper-plane-tilt" aria-hidden="true"></i> Kirim masukan</button>
        </form>
    </div>
</main>
@endsection
