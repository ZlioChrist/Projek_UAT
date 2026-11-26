@php($registerMode = request()->query('mode') === 'register')
<div class="fi-wn-login-page" data-auth-mode="{{ $registerMode ? 'register' : 'login' }}">

    {{--  NUCLEAR CSS INJECTION (God Tier Priority)  --}}
    <style>
        /* Force Reset Filament Layout */
        .fi-simple-header,
        .fi-simple-main-inner::before {
            display: none !important;
        }

        body,
        .fi-simple-layout {
            background: #060606 !important;
            padding: 0 !important;
            margin: 0 !important;
            min-height: 100vh !important;
            display: flex !important;
            align-items: center !important;
            justify-content: center !important;
        }

        .fi-simple-main {
            max-width: 100% !important;
            width: auto !important;
            margin: 0 !important;
            background: transparent !important;
            box-shadow: none !important;
            border: none !important;
        }

        .fi-simple-main-inner {
            background: transparent !important;
            box-shadow: none !important;
            border: none !important;
            padding: 0 !important;
        }

        /* Apex Container */
        .fi-wn-login-page {
            position: relative !important;
            min-height: 100vh !important;
            width: 100% !important;
            display: flex !important;
            align-items: center !important;
            justify-content: center !important;
            background: #060606 !important;
            z-index: 10 !important;
            overflow-x: hidden !important;
            font-family: 'Outfit', sans-serif !important;
            padding: 2rem 1rem !important;
        }

        .fi-wn-login-page::before {
            content: '';
            position: absolute;
            top: -30%;
            right: -20%;
            width: 100vw;
            height: 100vw;
            background: radial-gradient(circle, rgba(245, 158, 11, 0.12) 0%, transparent 70%);
            filter: blur(120px);
            z-index: -1;
            pointer-events: none;
            animation: apexFloat 20s ease-in-out infinite alternate;
        }

        @keyframes apexFloat {
            from {
                transform: translate(0, 0) scale(1);
            }

            to {
                transform: translate(-5%, 5%) scale(1.1);
            }
        }

        .fi-wn-login-container {
            width: 100%;
            max-width: 420px;
            padding: 0;
            position: relative;
            z-index: 10;
            animation: apexFadeUp 0.8s ease-out;
        }

        @keyframes apexFadeUp {
            from {
                opacity: 0;
                transform: translateY(30px);
            }

            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        /* THE LOGO FIX (Hard Bound) */
        .fi-wn-login-logo-box {
            width: 80px !important;
            height: 80px !important;
            margin: 0 auto 2.5rem !important;
            background: rgba(255, 255, 255, 0.03) !important;
            border: 1px solid rgba(255, 255, 255, 0.1) !important;
            border-radius: 22px !important;
            padding: 18px !important;
            backdrop-filter: blur(12px) !important;
            display: flex !important;
            align-items: center !important;
            justify-content: center !important;
            box-shadow: 0 15px 35px -10px rgba(0, 0, 0, 0.5) !important;
            overflow: hidden !important;
        }

        .fi-wn-login-logo-box img {
            max-width: 100% !important;
            max-height: 100% !important;
            object-fit: contain !important;
            filter: brightness(1.2) drop-shadow(0 0 10px rgba(245, 158, 11, 0.2)) !important;
        }

        /* Premium Card */
        .fi-wn-login-card {
            background: rgba(20, 20, 20, 0.6) !important;
            backdrop-filter: blur(40px) saturate(200%) !important;
            -webkit-backdrop-filter: blur(40px) saturate(200%) !important;
            border: 1px solid rgba(255, 255, 255, 0.05) !important;
            border-radius: 32px !important;
            padding: 2.5rem !important;
            box-shadow: 0 40px 100px -20px rgba(0, 0, 0, 0.9), inset 0 1px 0 rgba(255, 255, 255, 0.05) !important;
        }

        /* Input Overrides (Nuklir) */
        .fi-wn-login-page .fi-fo-field-wrp-label {
            font-size: 0.7rem !important;
            font-weight: 800 !important;
            color: rgba(255, 255, 255, 0.4) !important;
            text-transform: uppercase !important;
            letter-spacing: 0.12em !important;
            margin-bottom: 0.6rem !important;
        }

        .fi-wn-login-page .fi-input-wrapper {
            background: rgba(0, 0, 0, 0.4) !important;
            border: 1px solid rgba(255, 255, 255, 0.08) !important;
            border-radius: 14px !important;
            box-shadow: none !important;
        }

        .fi-wn-login-page .fi-input-wrapper:focus-within {
            border-color: #f59e0b !important;
            background: #000 !important;
            box-shadow: 0 0 0 1px #f59e0b !important;
        }

        .fi-wn-login-page input {
            color: #fff !important;
            font-size: 0.95rem !important;
            font-weight: 500 !important;
        }

        /* Button Overrides (High Priority) */
        .fi-wn-login-page button[type="submit"] {
            background: #ffffff !important;
            color: #000000 !important;
            border-radius: 14px !important;
            font-weight: 950 !important;
            font-size: 0.85rem !important;
            text-transform: uppercase !important;
            letter-spacing: 0.15em !important;
            padding: 1.1rem !important;
            width: 100% !important;
            border: none !important;
            transition: all 0.3s cubic-bezier(0.25, 1, 0.5, 1) !important;
            margin-top: 1.5rem !important;
            box-shadow: 0 8px 20px rgba(255, 255, 255, 0.1) !important;
        }

        .fi-wn-login-page button[type="submit"]:hover {
            background: #f59e0b !important;
            transform: translateY(-2px) !important;
            box-shadow: 0 12px 24px -5px rgba(245, 158, 11, 0.4) !important;
        }

        .fi-wn-login-title {
            font-size: 1.6rem;
            font-weight: 900;
            color: #fff;
            text-align: center;
            letter-spacing: -0.02em;
            margin-bottom: 0.25rem;
        }

        .fi-wn-login-subtitle {
            font-size: 0.75rem;
            color: rgba(255, 255, 255, 0.3);
            text-align: center;
            font-weight: 700;
            letter-spacing: 0.2em;
            margin-bottom: 2.5rem;
            text-transform: uppercase;
        }

        @media (max-width: 480px) {
            .fi-wn-login-page {
                padding: 1.5rem 0.75rem !important;
            }

            .fi-wn-login-title {
                font-size: 1.35rem;
            }

            .fi-wn-login-subtitle {
                font-size: 0.65rem;
                margin-bottom: 1.5rem;
            }

            .fi-wn-login-logo-box {
                width: 64px !important;
                height: 64px !important;
                padding: 14px !important;
                margin-bottom: 1.5rem !important;
            }

            .fi-wn-login-card {
                padding: 1.75rem 1.25rem !important;
                border-radius: 24px !important;
            }

            .fi-wn-login-page .fi-fo-field-wrp-label {
                font-size: 0.6rem !important;
            }

            .fi-wn-login-page button[type="submit"] {
                padding: 0.9rem !important;
                font-size: 0.75rem !important;
            }
        }

        @media (max-width: 360px) {
            .fi-wn-login-title {
                font-size: 1.15rem;
            }
        }

        body,
        .fi-simple-layout,
        .fi-wn-login-page {
            background: #17110e !important;
        }

        .fi-wn-login-page {
            align-items: flex-start !important;
            color: #f3e8dc;
        }

        .fi-wn-login-page::before {
            background: radial-gradient(circle, rgba(166, 112, 67, 0.16) 0%, transparent 70%);
        }

        .fi-wn-login-logo-box {
            background: #2b2119 !important;
            border-color: #49372a !important;
            box-shadow: 0 12px 28px rgba(0, 0, 0, 0.25) !important;
        }

        .fi-wn-login-card {
            background: #241c16 !important;
            border: 1px solid #433226 !important;
            box-shadow: 0 14px 36px rgba(0, 0, 0, 0.22) !important;
        }

        .fi-wn-login-page .fi-input-wrapper,
        .fi-wn-login-page .fi-input-wrp {
            background: #1b1511 !important;
            border-color: #594635 !important;
            color: #f3e8dc !important;
        }

        .fi-wn-login-page .fi-fo-field-wrp-label {
            color: #e5d4c2 !important;
            font-size: 0.85rem !important;
            font-weight: 600 !important;
            letter-spacing: 0.02em !important;
            text-transform: none !important;
        }

        .fi-wn-login-page .fi-input-wrapper:focus-within,
        .fi-wn-login-page .fi-input-wrp:focus-within {
            background: #1b1511 !important;
            border-color: #c08a5b !important;
            box-shadow: 0 0 0 3px rgba(192, 138, 91, 0.18) !important;
        }

        .fi-wn-login-page input {
            color: #f3e8dc !important;
        }

        .fi-wn-login-page button[type="submit"] {
            background: #9c6d47 !important;
            color: #fffaf4 !important;
            box-shadow: 0 8px 20px rgba(0, 0, 0, 0.24) !important;
        }

        .fi-wn-login-page button[type="submit"]:hover {
            background: #b07d52 !important;
        }

        .fi-wn-login-title {
            color: #f3e8dc !important;
        }

        .fi-wn-login-subtitle {
            color: #c0aa95 !important;
            opacity: 1;
        }

        .fi-wn-login-error {
            margin-bottom: 1rem;
            border: 1px solid #7f4338;
            border-radius: 0.75rem;
            background: #3a211d;
            padding: 0.75rem 1rem;
            color: #ffd9cf;
            font-size: 0.875rem;
        }

        .fi-wn-login-success {
            margin-bottom: 1rem;
            border: 1px solid #365a42;
            border-radius: 0.75rem;
            background: #1b3325;
            padding: 0.75rem 1rem;
            color: #c9f2d5;
            font-size: 0.875rem;
        }

        .fi-wn-login-register {
            margin-top: 1.25rem;
            text-align: center;
            color: #c0aa95;
            font-size: 0.875rem;
        }

        .fi-wn-login-register button {
            margin-left: .25rem;
            border: 0;
            background: transparent;
            color: #e9b65b;
            cursor: pointer;
            font: inherit;
            font-weight: 700;
            text-decoration: underline;
            text-underline-offset: 3px;
        }
    </style>

    @include('components.auth-transition')

    <div class="fi-wn-login-container">
        {{-- Branding --}}
        <header>
            <div class="fi-wn-login-logo-box">
                <img src="{{ asset('wadahngopi.png') }}" alt="WadahNgopi Logo">
            </div>

            <h1 class="fi-wn-login-title">
                Wadah<span class="text-amber-500 italic">Ngopi</span>
            </h1>
            <p class="fi-wn-login-subtitle">
                <span data-auth-heading="login" @if($registerMode) hidden @endif>Panel Pengelolaan</span>
                <span data-auth-heading="register" @unless($registerMode) hidden @endunless>Daftar</span>
            </p>
        </header>

        <div class="fi-wn-auth-panels">
            <section class="fi-wn-login-card fi-wn-auth-panel" data-auth-panel="login" @if($registerMode) hidden inert @endif>
                @if(!$registerMode && $errors->any())
                    <div class="fi-wn-login-error" role="alert">{{ $errors->first() }}</div>
                @endif

                <x-filament-panels::form wire:submit="authenticate">
                    {{ $this->form }}

                    <div class="pt-2">
                        <x-filament-panels::form.actions :actions="$this->getCachedFormActions()"
                            :full-width="$this->hasFullWidthFormActions()" />
                    </div>
                </x-filament-panels::form>

                <p class="fi-wn-login-register">
                    Belum punya akun?
                    <button type="button" data-auth-toggle="register">Daftar</button>
                </p>
            </section>

            <section class="fi-wn-login-card fi-wn-auth-panel" data-auth-panel="register" @unless($registerMode) hidden inert @endunless>
                @if(session('status'))
                    <div class="fi-wn-login-success" role="status">{{ session('status') }}</div>
                @endif

                @if($registerMode && $errors->any())
                    <div class="fi-wn-login-error" role="alert">{{ $errors->first() }}</div>
                @endif

                <form method="POST" action="{{ route('merchant-registration.store') }}" class="fi-wn-auth-fieldset">
                    @csrf
                    <h2 class="fi-wn-auth-section-title">Akun</h2>
                    <label class="fi-wn-auth-field"><span>Nama<span class="fi-wn-auth-required-mark" aria-hidden="true">*</span></span>
                        <input name="name" value="{{ old('name') }}" required minlength="2" maxlength="120" autocomplete="name" @if($registerMode) autofocus @endif>
                    </label>
                    <label class="fi-wn-auth-field"><span>Email<span class="fi-wn-auth-required-mark" aria-hidden="true">*</span></span>
                        <input name="email" type="email" value="{{ old('email') }}" required maxlength="255" autocomplete="email">
                    </label>
                    <label class="fi-wn-auth-field"><span>Kata sandi<span class="fi-wn-auth-required-mark" aria-hidden="true">*</span></span>
                        <input name="password" type="password" required minlength="8" autocomplete="new-password">
                    </label>
                    <label class="fi-wn-auth-field"><span>Ulangi kata sandi<span class="fi-wn-auth-required-mark" aria-hidden="true">*</span></span>
                        <input name="password_confirmation" type="password" required minlength="8" autocomplete="new-password">
                    </label>

                    <h2 class="fi-wn-auth-section-title">Usaha</h2>
                    <label class="fi-wn-auth-field"><span>Jenis akun<span class="fi-wn-auth-required-mark" aria-hidden="true">*</span></span>
                        <select name="business_type" required>
                            <option value="">Pilih jenis</option>
                            <option value="cafe" @selected(old('business_type') === 'cafe')>Admin Kafe</option>
                            <option value="roastery" @selected(old('business_type') === 'roastery')>Admin Roastery</option>
                        </select>
                    </label>
                    <label class="fi-wn-auth-field"><span>Nama usaha<span class="fi-wn-auth-required-mark" aria-hidden="true">*</span></span>
                        <input name="business_name" value="{{ old('business_name') }}" required minlength="2" maxlength="180">
                    </label>
                    <label class="fi-wn-auth-field"><span>Kota<span class="fi-wn-auth-required-mark" aria-hidden="true">*</span></span>
                        <select name="city_id" id="merchant-city-select" required>
                            <option value="">Pilih kota</option>
                            @foreach($merchantCities as $city)
                                <option value="{{ $city->id }}" @selected((string) old('city_id') === (string) $city->id)>{{ $city->name }}</option>
                            @endforeach
                            <option value="other" @selected(old('city_id') === 'other')>Kota lainnya</option>
                        </select>
                    </label>
                    <label class="fi-wn-auth-field" id="merchant-city-name-field" @if(old('city_id') !== 'other') hidden @endif><span>Nama kota<span class="fi-wn-auth-required-mark" aria-hidden="true">*</span></span>
                        <input name="city_name" id="merchant-city-name" value="{{ old('city_name') }}" maxlength="120" @if(old('city_id') === 'other') required @endif>
                    </label>
                    <label class="fi-wn-auth-field"><span>Alamat<span class="fi-wn-auth-required-mark" aria-hidden="true">*</span></span>
                        <textarea name="address" required minlength="8" maxlength="2000" rows="3">{{ old('address') }}</textarea>
                    </label>
                    <label class="fi-wn-auth-field"><span>WhatsApp<span class="fi-wn-auth-required-mark" aria-hidden="true">*</span></span>
                        <input name="whatsapp_number" type="tel" value="{{ old('whatsapp_number') }}" required minlength="8" maxlength="32" placeholder="08xxxxxxxxxx">
                    </label>
                    <label class="fi-wn-auth-field"><span>Tentang usaha (opsional)</span>
                        <textarea name="description" maxlength="2000" rows="3">{{ old('description') }}</textarea>
                    </label>
                    <button type="submit">Daftar</button>
                </form>

                <p class="fi-wn-login-register">
                    Sudah punya akun?
                    <button type="button" data-auth-toggle="login">Masuk</button>
                </p>
            </section>
        </div>
    </div>
</div>