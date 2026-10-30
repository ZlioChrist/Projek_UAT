<div class="fi-wn-login-page">

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
            max-width: 480px;
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

        /* THE LOGO FIX */
        .fi-wn-login-logo-box {
            width: 60px !important;
            height: 60px !important;
            margin: 0 auto 1.5rem !important;
            background: rgba(255, 255, 255, 0.03) !important;
            border: 1px solid rgba(255, 255, 255, 0.1) !important;
            border-radius: 18px !important;
            padding: 12px !important;
            backdrop-filter: blur(12px) !important;
            display: flex !important;
            align-items: center !important;
            justify-content: center !important;
            box-shadow: 0 10px 25px -5px rgba(0, 0, 0, 0.5) !important;
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
            padding: 2.25rem !important;
            box-shadow: 0 40px 100px -20px rgba(0, 0, 0, 0.9), inset 0 1px 0 rgba(255, 255, 255, 0.05) !important;
            overflow-y: visible !important;
            max-height: none !important;
        }

        /* Input Overrides */
        .fi-wn-login-page .fi-fo-field-wrp-label {
            font-size: 0.68rem !important;
            font-weight: 800 !important;
            color: rgba(255, 255, 255, 0.4) !important;
            text-transform: uppercase !important;
            letter-spacing: 0.12em !important;
            margin-bottom: 0.45rem !important;
        }

        .fi-wn-login-page .fi-input-wrapper {
            background: rgba(0, 0, 0, 0.4) !important;
            border: 1px solid rgba(255, 255, 255, 0.08) !important;
            border-radius: 12px !important;
        }

        .fi-wn-login-page .fi-input-wrapper:focus-within {
            border-color: #f59e0b !important;
            background: #000 !important;
            box-shadow: 0 0 0 1px #f59e0b !important;
        }

        .fi-wn-login-page input,
        .fi-wn-login-page select {
            color: #fff !important;
            font-size: 0.9rem !important;
            font-weight: 500 !important;
        }

        /* Button Overrides */
        .fi-wn-login-page button[type="submit"] {
            background: #ffffff !important;
            color: #000000 !important;
            border-radius: 12px !important;
            font-weight: 950 !important;
            font-size: 0.8rem !important;
            text-transform: uppercase !important;
            letter-spacing: 0.15em !important;
            padding: 1rem !important;
            width: 100% !important;
            border: none !important;
            transition: all 0.3s cubic-bezier(0.25, 1, 0.5, 1) !important;
            margin-top: 1rem !important;
        }

        .fi-wn-login-page button[type="submit"]:hover {
            background: #f59e0b !important;
            transform: translateY(-2px) !important;
            box-shadow: 0 12px 24px -5px rgba(245, 158, 11, 0.4) !important;
        }

        .fi-wn-login-title {
            font-size: 1.4rem;
            font-weight: 900;
            color: #fff;
            text-align: center;
            letter-spacing: -0.02em;
            margin-bottom: 0.2rem;
        }

        .fi-wn-login-subtitle {
            font-size: 0.7rem;
            color: rgba(255, 255, 255, 0.3);
            text-align: center;
            font-weight: 700;
            letter-spacing: 0.2em;
            margin-bottom: 2rem;
            text-transform: uppercase;
        }

        @media (max-width: 480px) {
            .fi-wn-login-page {
                padding: 1.5rem 0.75rem !important;
            }

            .fi-wn-login-title {
                font-size: 1.25rem;
            }

            .fi-wn-login-subtitle {
                font-size: 0.6rem;
                margin-bottom: 1.5rem;
            }

            .fi-wn-login-logo-box {
                width: 52px !important;
                height: 52px !important;
                padding: 10px !important;
                margin-bottom: 1.25rem !important;
                border-radius: 14px !important;
            }

            .fi-wn-login-card {
                padding: 1.5rem 1rem !important;
                border-radius: 24px !important;
            }

            .fi-wn-login-page .fi-fo-field-wrp-label {
                font-size: 0.58rem !important;
                margin-bottom: 0.3rem !important;
            }

            .fi-wn-login-page button[type="submit"] {
                padding: 0.85rem !important;
                font-size: 0.7rem !important;
            }
        }
    </style>

    <div class="fi-wn-login-container">
        <header>
            <div class="fi-wn-login-logo-box">
                <img src="{{ asset('wadahngopi.png') }}" alt="WadahNgopi Logo">
            </div>

            <h1 class="fi-wn-login-title">
                Gabung <span class="text-amber-500 italic">WadahNgopi</span>
            </h1>
            <p class="fi-wn-login-subtitle">
                PENDAFTARAN AKUN
            </p>
        </header>

        <main class="fi-wn-login-card">
            <x-filament-panels::form wire:submit="register">
                {{ $this->form }}

                <div class="pt-2">
                    <x-filament-panels::form.actions :actions="$this->getCachedFormActions()"
                        :full-width="$this->hasFullWidthFormActions()" />
                </div>
            </x-filament-panels::form>
        </main>

        <footer class="mt-8 text-center">
            <a href="{{ filament()->getLoginUrl() }}" class="group inline-flex items-center gap-2 text-white/30 hover:text-white
                       transition-all text-[10px] font-bold uppercase tracking-[0.2em] no-underline">
                <i class="ph ph-arrow-left text-xs group-hover:-translate-x-1 transition-transform"></i>
                Sudah Punya Akun? Masuk Di Sini
            </a>
        </footer>
    </div>

    {{-- Icon Assets --}}
    <link rel="stylesheet" href="https://unpkg.com/@phosphor-icons/web@2.1.1/src/regular/style.css">
</div>