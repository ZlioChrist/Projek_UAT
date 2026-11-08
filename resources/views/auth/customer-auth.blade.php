@extends('layouts.app')

@php
    $isRegister = $mode === 'register';
@endphp

@section('title', ($isRegister ? 'Daftar' : 'Masuk').' sebagai Pelanggan - WadahNgopi')

@section('content')
<main class="wn-auth-page{{ $isRegister ? ' is-register' : '' }}" data-auth-page data-mode="{{ $mode }}">
    <aside class="wn-auth-story">
        <a href="{{ route('home') }}" class="wn-auth-brand">
            <img src="{{ asset('wadahngopi.png') }}" alt="">
            <span>WadahNgopi</span>
        </a>
        <div class="wn-auth-story-copy">
            <p class="wn-auth-eyebrow">Temukan cerita di setiap cangkir</p>
            <h1 class="wn-auth-story-title">Kopi enak,<br><span>lebih dekat.</span></h1>
            <p class="wn-auth-story-text">Pesan kopi favorit, simpan tempat singgah, dan ikuti perjalanan pesananmu dalam satu ruang.</p>
        </div>
        <div class="wn-auth-story-foot">
            <i class="ph ph-coffee" aria-hidden="true"></i>
            <span>Tempat terbaik selalu dimulai dari secangkir kopi.</span>
        </div>
    </aside>

    <section class="wn-auth-card-wrap" aria-label="Akses akun pelanggan">
        <div class="wn-auth-slider" data-auth-slider>
            <div class="wn-auth-track">
                <section class="wn-auth-panel" data-auth-panel="login" aria-labelledby="login-title" @if($isRegister) aria-hidden="true" inert @endif>
                    <div class="wn-auth-card">
                        <p class="wn-auth-eyebrow">Senang bertemu lagi</p>
                        <h2 class="wn-auth-heading" id="login-title" tabindex="-1">Masuk ke akunmu</h2>
                        <p class="wn-auth-description">Masuk untuk checkout dan mengelola aktivitas akun Anda.</p>

                        @if(!$isRegister && $errors->any())
                            <div class="wn-auth-alert" role="alert">
                                <i class="ph ph-warning-circle" aria-hidden="true"></i>
                                <span>{{ $errors->first() }}</span>
                            </div>
                        @endif

                        <form method="POST" action="{{ route('login.store') }}" class="wn-auth-form">
                            @csrf
                            <label class="wn-auth-field" for="login-email">
                                Email
                                <input id="login-email" type="email" name="email" value="{{ old('email') }}" placeholder="nama@email.com" required autocomplete="email" @if(!$isRegister) autofocus @endif>
                            </label>
                            <label class="wn-auth-field" for="login-password">
                                Kata sandi
                                <input id="login-password" type="password" name="password" placeholder="Masukkan kata sandi" required autocomplete="current-password">
                            </label>
                            <label class="wn-auth-remember">
                                <input type="checkbox" name="remember" value="1">
                                Ingat saya di perangkat ini
                            </label>
                            <button type="submit" class="wn-auth-submit">Masuk sebagai pelanggan <i class="ph ph-arrow-right" aria-hidden="true"></i></button>
                        </form>

                        <p class="wn-auth-switch">Belum punya akun? <a href="{{ route('register') }}" data-auth-toggle="register">Daftar pelanggan</a></p>
                    </div>
                </section>

                <section class="wn-auth-panel" data-auth-panel="register" aria-labelledby="register-title" @unless($isRegister) aria-hidden="true" inert @endunless>
                    <div class="wn-auth-card">
                        <p class="wn-auth-eyebrow">Ruang pelangganmu</p>
                        <h2 class="wn-auth-heading" id="register-title" tabindex="-1">Buat akun baru</h2>
                        <p class="wn-auth-description">Daftar untuk menyimpan tempat favorit, checkout, dan melacak pesanan.</p>

                        @if($isRegister && $errors->any())
                            <div class="wn-auth-alert" role="alert">
                                <i class="ph ph-warning-circle" aria-hidden="true"></i>
                                <span>{{ $errors->first() }}</span>
                            </div>
                        @endif

                        <form method="POST" action="{{ route('register.store') }}" class="wn-auth-form">
                            @csrf
                            <label class="wn-auth-field" for="register-name">
                                Nama lengkap
                                <input id="register-name" name="name" value="{{ old('name') }}" placeholder="Nama yang ingin kami sapa" required minlength="2" maxlength="120" autocomplete="name" @if($isRegister) autofocus @endif>
                            </label>
                            <label class="wn-auth-field" for="register-email">
                                Email
                                <input id="register-email" type="email" name="email" value="{{ old('email') }}" placeholder="nama@email.com" required autocomplete="email">
                            </label>
                            <label class="wn-auth-field" for="register-password">
                                Kata sandi
                                <input id="register-password" type="password" name="password" placeholder="Buat kata sandi yang kuat" required minlength="8" autocomplete="new-password">
                                <span class="wn-auth-password-hint">Gunakan minimal 8 karakter.</span>
                            </label>
                            <label class="wn-auth-field" for="register-password-confirmation">
                                Ulangi kata sandi
                                <input id="register-password-confirmation" type="password" name="password_confirmation" placeholder="Ketik ulang kata sandi" required autocomplete="new-password">
                            </label>
                            <button type="submit" class="wn-auth-submit">Daftar sebagai pelanggan <i class="ph ph-arrow-right" aria-hidden="true"></i></button>
                        </form>
                        <p class="wn-auth-switch">Sudah punya akun? <a href="{{ route('login') }}" data-auth-toggle="login">Masuk</a></p>
                    </div>
                </section>
            </div>
        </div>
    </section>
</main>
@endsection

@push('scripts')
<script>
    (() => {
        const page = document.querySelector('[data-auth-page]');
        if (!page) return;

        const panels = Array.from(page.querySelectorAll('[data-auth-panel]'));
        const modeUrls = {
            login: @json(route('login')),
            register: @json(route('register')),
        };
        const normalizePath = (path) => path.replace(/\/+$/, '') || '/';

        const setMode = (mode, updateHistory = false) => {
            if (!modeUrls[mode]) return;

            const isRegister = mode === 'register';
            page.classList.toggle('is-register', isRegister);
            page.dataset.mode = mode;

            panels.forEach((panel) => {
                const active = panel.dataset.authPanel === mode;
                panel.inert = !active;
                panel.setAttribute('aria-hidden', String(!active));
            });

            if (updateHistory) {
                const target = new URL(modeUrls[mode], window.location.origin);
                target.search = window.location.search;
                target.hash = window.location.hash;
                window.history.pushState({ authMode: mode }, '', target);
            }
        };

        page.querySelectorAll('[data-auth-toggle]').forEach((toggle) => {
            toggle.addEventListener('click', (event) => {
                event.preventDefault();
                setMode(toggle.dataset.authToggle, true);
                const heading = page.querySelector(`[data-auth-panel="${toggle.dataset.authToggle}"] .wn-auth-heading`);
                heading?.focus();
            });
        });

        window.addEventListener('popstate', () => {
            const registerPath = normalizePath(new URL(modeUrls.register, window.location.origin).pathname);
            const currentPath = normalizePath(window.location.pathname);
            const mode = currentPath === registerPath ? 'register' : 'login';
            setMode(mode);
            page.querySelector(`[data-auth-panel="${mode}"] .wn-auth-heading`)?.focus();
        });

        setMode(page.dataset.mode);
    })();
</script>
@endpush
