@extends('layouts.app')
@section('title', 'Daftar Pelanggan - WadahNgopi')
@section('content')
<main class="wn-auth-page">
    <aside class="wn-auth-story">
        <a href="{{ route('home') }}" class="wn-auth-brand">
            <img src="{{ asset('wadahngopi.png') }}" alt="">
            <span>WadahNgopi</span>
        </a>
        <div class="wn-auth-story-copy">
            <p class="wn-auth-eyebrow">Temukan cerita di setiap cangkir</p>
            <h1 class="wn-auth-story-title">Satu akun,<br><span>banyak rasa.</span></h1>
            <p class="wn-auth-story-text">Simpan tempat favorit, checkout dengan mudah, dan ikuti perjalanan pesananmu dalam satu ruang.</p>
        </div>
        <div class="wn-auth-story-foot">
            <i class="ph ph-heart" aria-hidden="true"></i>
            <span>Perjalanan kopi yang lebih personal dimulai di sini.</span>
        </div>
    </aside>

    <section class="wn-auth-card-wrap" aria-labelledby="register-title">
        <div class="wn-auth-card">
            <p class="wn-auth-eyebrow">Ruang pelangganmu</p>
            <h2 class="wn-auth-heading" id="register-title">Buat akun baru</h2>
            <p class="wn-auth-description">Daftar untuk menyimpan tempat favorit, checkout, dan melacak pesanan.</p>

            @if($errors->any())
                <div class="wn-auth-alert" role="alert">
                    <i class="ph ph-warning-circle" aria-hidden="true"></i>
                    <span>{{ $errors->first() }}</span>
                </div>
            @endif

            <form method="POST" action="{{ route('register.store') }}" class="wn-auth-form">
                @csrf
                <label class="wn-auth-field" for="name">
                    Nama lengkap
                    <input id="name" name="name" value="{{ old('name') }}" placeholder="Nama yang ingin kami sapa" required minlength="2" maxlength="120" autocomplete="name" autofocus>
                </label>
                <label class="wn-auth-field" for="email">
                    Email
                    <input id="email" type="email" name="email" value="{{ old('email') }}" placeholder="nama@email.com" required autocomplete="email">
                </label>
                <label class="wn-auth-field" for="password">
                    Kata sandi
                    <input id="password" type="password" name="password" placeholder="Buat kata sandi yang kuat" required minlength="8" autocomplete="new-password">
                    <span class="wn-auth-password-hint">Gunakan minimal 8 karakter.</span>
                </label>
                <label class="wn-auth-field" for="password_confirmation">
                    Ulangi kata sandi
                    <input id="password_confirmation" type="password" name="password_confirmation" placeholder="Ketik ulang kata sandi" required autocomplete="new-password">
                </label>
                <button type="submit" class="wn-auth-submit">Daftar sebagai pelanggan <i class="ph ph-arrow-right" aria-hidden="true"></i></button>
            </form>

            <p class="wn-auth-switch">Sudah punya akun? <a href="{{ route('login') }}">Masuk</a></p>
        </div>
    </section>
</main>
@endsection
