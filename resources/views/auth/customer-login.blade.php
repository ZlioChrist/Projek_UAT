@extends('layouts.app')
@section('title', 'Masuk sebagai Pelanggan - WadahNgopi')
@section('content')
<main class="wn-auth-page">
    <aside class="wn-auth-story">
        <a href="{{ route('home') }}" class="wn-auth-brand">
            <img src="{{ asset('wadahngopi.png') }}" alt="">
            <span>WadahNgopi</span>
        </a>
        <div class="wn-auth-story-copy">
            <p class="wn-auth-eyebrow">Temukan cerita di setiap cangkir</p>
            <h1 class="wn-auth-story-title">Kopi enak,<br><span>lebih dekat.</span></h1>
            <p class="wn-auth-story-text">Masuk untuk memesan kopi favorit, menyimpan tempat singgah, dan mengikuti setiap pesananmu.</p>
        </div>
        <div class="wn-auth-story-foot">
            <i class="ph ph-coffee" aria-hidden="true"></i>
            <span>Tempat terbaik selalu dimulai dari secangkir kopi.</span>
        </div>
    </aside>

    <section class="wn-auth-card-wrap" aria-labelledby="login-title">
        <div class="wn-auth-card">
            <p class="wn-auth-eyebrow">Senang bertemu lagi</p>
            <h2 class="wn-auth-heading" id="login-title">Masuk ke akunmu</h2>
            <p class="wn-auth-description">Masuk untuk checkout dan mengelola aktivitas akun Anda.</p>

            @if($errors->any())
                <div class="wn-auth-alert" role="alert">
                    <i class="ph ph-warning-circle" aria-hidden="true"></i>
                    <span>{{ $errors->first() }}</span>
                </div>
            @endif

            <form method="POST" action="{{ route('login.store') }}" class="wn-auth-form">
                @csrf
                <label class="wn-auth-field" for="email">
                    Email
                    <input id="email" type="email" name="email" value="{{ old('email') }}" placeholder="nama@email.com" required autocomplete="email" autofocus>
                </label>
                <label class="wn-auth-field" for="password">
                    Kata sandi
                    <input id="password" type="password" name="password" placeholder="Masukkan kata sandi" required autocomplete="current-password">
                </label>
                <label class="wn-auth-remember">
                    <input type="checkbox" name="remember" value="1">
                    Ingat saya di perangkat ini
                </label>
                <button type="submit" class="wn-auth-submit">Masuk sebagai pelanggan <i class="ph ph-arrow-right" aria-hidden="true"></i></button>
            </form>

            <p class="wn-auth-switch">Belum punya akun? <a href="{{ route('register') }}">Daftar pelanggan</a></p>
            <p class="wn-auth-owner-link">Pemilik kafe atau roastery? <a href="{{ url('/admin/login') }}">Masuk panel pengelola</a></p>
        </div>
    </section>
</main>
@endsection
