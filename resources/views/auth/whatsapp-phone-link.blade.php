@extends('layouts.app')
@php
    $isAdmin = $audience === 'admin';
    $pending = session('auth.whatsapp.link');
    $requestRoute = $isAdmin ? 'owner.account.whatsapp.request' : 'account.whatsapp.request';
    $verifyRoute = $isAdmin ? 'owner.account.whatsapp.verify' : 'account.whatsapp.verify';
    $returnUrl = $isAdmin ? url('/admin') : route('account');
    $whatsappEnabled = filled(config('services.meta_whatsapp.access_token'))
        && filled(config('services.meta_whatsapp.phone_number_id'))
        && filled(config('services.meta_whatsapp.otp_template'));
@endphp
@section('title', 'Keamanan WhatsApp - WadahNgopi')
@section('content')
<main class="wn-customer-page" style="max-width:680px">
    <a href="{{ $returnUrl }}" class="wn-customer-back"><i class="ph ph-arrow-left" aria-hidden="true"></i> Kembali</a>
    <header class="wn-customer-page-header">
        <span class="wn-customer-eyebrow"><i class="ph ph-shield-check" aria-hidden="true"></i> Keamanan akun</span>
        <h1>Login dengan WhatsApp</h1>
        <p>Verifikasi nomor WhatsApp dengan OTP. Kode berlaku lima menit dan tidak pernah ditampilkan di halaman.</p>
    </header>

    @if(session('status'))
        <p role="status" class="mb-4 rounded-xl bg-emerald-50 p-3 text-sm font-semibold text-emerald-800">{{ session('status') }}</p>
    @endif
    @if($errors->any())
        <p role="alert" class="mb-4 rounded-xl bg-rose-50 p-3 text-sm font-semibold text-rose-800">{{ $errors->first() }}</p>
    @endif
    @if($phoneNumber && $verifiedAt)
        <p class="mb-4 rounded-xl bg-emerald-50 p-3 text-sm font-semibold text-emerald-800">
            Nomor terverifikasi: {{ $phoneNumber }} ({{ $verifiedAt->format('d/m/Y H:i') }})
        </p>
    @else
        <p class="mb-4 rounded-xl bg-amber-50 p-3 text-sm font-semibold text-amber-900">
            Nomor WhatsApp belum ditautkan dan diverifikasi. Login OTP baru dapat digunakan setelah langkah ini.
        </p>
    @endif

    @if($whatsappEnabled)
        <form method="POST" action="{{ route($requestRoute) }}" class="wn-auth-form">
            @csrf
            <label class="wn-auth-field" for="link-whatsapp-number">
                Nomor WhatsApp
                <input id="link-whatsapp-number" type="tel" name="phone_number" value="{{ old('phone_number', $pending['phone'] ?? $phoneNumber) }}" placeholder="08xxxxxxxxxx" required autocomplete="tel">
            </label>
            <button type="submit" class="wn-auth-submit">Kirim kode verifikasi</button>
        </form>

        @if(session('whatsapp_link_sent') && is_array($pending))
            <form method="POST" action="{{ route($verifyRoute) }}" class="wn-auth-form">
                @csrf
                <input type="hidden" name="phone_number" value="{{ $pending['phone'] }}">
                <label class="wn-auth-field" for="link-whatsapp-code">
                    Kode OTP
                    <input id="link-whatsapp-code" name="code" inputmode="numeric" pattern="[0-9]{6}" minlength="6" maxlength="6" placeholder="6 digit kode" required autocomplete="one-time-code">
                </label>
                <button type="submit" class="wn-auth-submit">Verifikasi nomor</button>
            </form>
        @endif
    @else
        <p role="status" class="mb-4 rounded-xl bg-amber-50 p-3 text-sm font-semibold text-amber-900">
            Verifikasi WhatsApp belum aktif. Hubungi pengelola untuk melengkapi konfigurasi Meta Cloud API.
        </p>
    @endif
</main>
@endsection
