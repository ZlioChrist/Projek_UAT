@extends('layouts.app')

@section('title', 'WadahNgopi — Jelajahi Kopi Favoritmu')

@section('content')
    <main class="wn-home-shell">
        <div class="wn-home-frame">
            <header class="wn-brand-header">
                <div class="wn-brand-mark">
                    <img src="{{ asset('wadahngopi.png') }}" alt="Logo WadahNgopi">
                </div>
                <span class="wn-brand-credit">BY : AK KREATIF</span>
                <h1 class="wn-brand-name">Wadah<span>Ngopi</span></h1>
                <p class="wn-brand-tagline">
                    Untuk merajut silaturahmi dan mendekatkan para pelaku usaha kedai kopi dengan para penikmat kopi di Samarinda.
                </p>
            </header>

            <section class="wn-about-card" aria-labelledby="wn-about-title">
                <div class="wn-about-heading">
                    <span class="wn-about-accent" aria-hidden="true"></span>
                    <div class="wn-about-heading-copy">
                        <span class="wn-about-kicker">TENTANG KAMI</span>
                        <h2 id="wn-about-title">Lebih dari<br>Sekadar Kopi</h2>
                    </div>
                </div>

                <p class="wn-about-copy">
                    Di WadahNgopi, para pelaku usaha kedai kopi dapat memperkenalkan produk kedainya dengan lebih mudah.
                </p>

                <div class="wn-card-divider wn-card-divider-top" aria-hidden="true"></div>

                <nav class="wn-social-links" aria-label="Media sosial WadahNgopi">
                    <a href="https://www.instagram.com/wadahngopi/" target="_blank" rel="noopener noreferrer" aria-label="Instagram WadahNgopi">
                        <span class="wn-social-icon wn-instagram"><i class="ph-fill ph-instagram-logo" aria-hidden="true"></i></span>
                        <span>INSTAGRAM</span>
                    </a>
                    <a href="https://www.tiktok.com/@wadah.ngopi" target="_blank" rel="noopener noreferrer" aria-label="TikTok WadahNgopi">
                        <span class="wn-social-icon wn-tiktok"><i class="ph-fill ph-tiktok-logo" aria-hidden="true"></i></span>
                        <span>TIKTOK</span>
                    </a>
                    <a href="https://youtube.com/@wadahngopi" target="_blank" rel="noopener noreferrer" aria-label="YouTube WadahNgopi">
                        <span class="wn-social-icon wn-youtube"><i class="ph-fill ph-youtube-logo" aria-hidden="true"></i></span>
                        <span>YOUTUBE</span>
                    </a>
                </nav>

                <div class="wn-card-divider" aria-hidden="true"></div>

                <nav class="wn-home-actions" aria-label="Jelajahi WadahNgopi">
                    <a class="wn-action-link wn-action-primary" href="{{ route('explore') }}">
                        <span class="wn-action-icon"><i class="ph-bold ph-compass" aria-hidden="true"></i></span>
                        <span class="wn-action-copy">
                            <strong>JELAJAHI CAFE</strong>
                            <small>Ayo cari cafe favoritmu</small>
                        </span>
                        <span class="wn-action-arrow"><i class="ph-bold ph-caret-right" aria-hidden="true"></i></span>
                    </a>

                    <a class="wn-action-link wn-action-muted" href="{{ route('information') }}">
                        <span class="wn-action-icon"><i class="ph-bold ph-newspaper" aria-hidden="true"></i></span>
                        <span class="wn-action-copy">
                            <strong>INFORMASI</strong>
                            <small>Berita &amp; event terbaru</small>
                        </span>
                        <span class="wn-action-arrow"><i class="ph-bold ph-caret-right" aria-hidden="true"></i></span>
                    </a>

                    <a class="wn-action-link wn-action-roastery" href="{{ route('roastery') }}">
                        <span class="wn-action-icon"><i class="ph-bold ph-coffee-bean" aria-hidden="true"></i></span>
                        <span class="wn-action-copy">
                            <strong>ROASTERY</strong>
                            <small>Cari beans kopi favoritmu</small>
                        </span>
                        <span class="wn-action-arrow"><i class="ph-bold ph-caret-right" aria-hidden="true"></i></span>
                    </a>
                </nav>

                <div class="wn-account-entry">
                    @auth
                        <a href="{{ route('account') }}">Akun {{ auth()->user()->name }}</a>
                    @else
                        <span>Sudah punya akun?</span>
                        <a href="{{ route('login') }}">Masuk</a>
                        <a href="{{ route('register') }}">Daftar pelanggan</a>
                    @endauth
                </div>

                <footer class="wn-home-footer">
                    <nav aria-label="Tautan pesanan">
                        <a href="{{ route('feedback.create') }}">Kritik &amp; saran</a>
                    </nav>
                    <span>WADAHNGOPI.COM <span aria-hidden="true">©</span> 2026</span>
                </footer>
            </section>
        </div>
    </main>

    <style>
        .wn-home-shell {
            min-height: 100vh;
            min-height: 100dvh;
            padding: 0 16px;
            color: #2c1810;
            font-family: 'Outfit', 'Plus Jakarta Sans', sans-serif;
        }

        .wn-home-frame {
            width: 100%;
            max-width: 480px;
            min-height: 100vh;
            min-height: 100dvh;
            margin: 0 auto;
            padding: 16px 18px 122px;
            display: flex;
            flex-direction: column;
            gap: 10px;
        }

        .wn-brand-header {
            display: flex;
            flex-direction: column;
            align-items: center;
            padding: 0 8px 2px;
            text-align: center;
        }

        .wn-brand-mark {
            display: grid;
            width: 56px;
            height: 56px;
            place-items: center;
            margin-bottom: 11px;
            border: 1px solid rgba(55, 37, 25, .08);
            border-radius: 17px;
            background: #fff;
            box-shadow: 0 8px 22px rgba(45, 29, 20, .09);
        }

        .wn-brand-mark img {
            width: 34px;
            height: 34px;
            object-fit: contain;
        }

        .wn-brand-credit {
            display: inline-flex;
            min-height: 20px;
            align-items: center;
            padding: 3px 11px;
            border: 1px solid rgba(40, 26, 19, .08);
            border-radius: 999px;
            background: #f7f3ed;
            color: #76675a;
            font-size: 8px;
            font-weight: 900;
            letter-spacing: .14em;
        }

        .wn-brand-name {
            margin: 7px 0 3px;
            color: #2c1810;
            font-size: clamp(28px, 8vw, 34px);
            font-weight: 900;
            line-height: 1;
            letter-spacing: -.065em;
        }

        .wn-brand-name span { color: #e8990b; }

        .wn-brand-tagline {
            max-width: 320px;
            margin: 5px 0 0;
            color: #81766c;
            font-size: 12px;
            font-weight: 500;
            line-height: 1.55;
        }

        .wn-about-card {
            position: relative;
            overflow: hidden;
            padding: 14px 18px 10px;
            border: 1px solid rgba(255, 255, 255, .1);
            border-radius: 30px;
            background:
                radial-gradient(ellipse 100% 55% at 100% 0%, rgba(174, 103, 27, .16), transparent 65%),
                linear-gradient(150deg, #301b13 0%, #402518 58%, #392219 100%);
            box-shadow: 0 18px 38px rgba(48, 28, 18, .2), inset 0 1px 0 rgba(255, 255, 255, .06);
            color: #fff;
        }

        .wn-about-heading {
            display: grid;
            grid-template-columns: 4px 1fr;
            gap: 12px;
            align-items: center;
            margin-bottom: 10px;
        }

        .wn-about-accent {
            width: 4px;
            min-height: 40px;
            border-radius: 999px;
            background: linear-gradient(180deg, #f5a500, #d17a00);
            box-shadow: 0 0 14px rgba(239, 153, 4, .28);
        }

        .wn-about-heading-copy {
            display: flex;
            flex-direction: row;
            align-items: flex-end;
            gap: 8px;
        }

        .wn-about-kicker {
            padding: 4px 8px;
            flex: 0 0 auto;
            margin-bottom: 2px;
            border: 1px solid rgba(255, 255, 255, .14);
            border-radius: 999px;
            background: rgba(255, 255, 255, .1);
            color: #f3e8dc;
            font-size: 8px;
            font-weight: 900;
            letter-spacing: .11em;
            white-space: nowrap;
        }

        .wn-about-heading h2 {
            margin: 0;
            color: #fff;
            font-size: clamp(21px, 5.3vw, 24px);
            font-weight: 900;
            line-height: 1.04;
            letter-spacing: -.045em;
        }

        .wn-about-copy {
            margin: 0 0 10px;
            color: rgba(255, 248, 239, .82);
            font-size: 13px;
            font-weight: 500;
            line-height: 1.65;
        }

        .wn-social-links {
            display: flex;
            justify-content: center;
            gap: clamp(24px, 8vw, 38px);
            padding: 10px 0 11px;
        }

        .wn-social-links a {
            display: flex;
            min-width: 48px;
            flex-direction: column;
            align-items: center;
            gap: 6px;
            color: rgba(255, 246, 237, .62);
            font-size: 8px;
            font-weight: 800;
            letter-spacing: .04em;
            text-decoration: none;
            transition: transform .18s ease, color .18s ease;
        }

        .wn-social-links a:hover {
            transform: translateY(-2px);
            color: #fff;
        }

        .wn-social-icon {
            display: grid;
            width: 40px;
            height: 40px;
            place-items: center;
            border: 1px solid rgba(255, 255, 255, .08);
            border-radius: 14px;
            background: rgba(255, 255, 255, .08);
            box-shadow: 0 5px 12px rgba(0, 0, 0, .12);
            font-size: 19px;
        }

        .wn-instagram { color: #d94678; }
        .wn-tiktok { color: #fff; }
        .wn-youtube { color: #e52222; }

        .wn-card-divider {
            height: 1px;
            margin: 0 0 12px;
            background: rgba(255, 255, 255, .1);
        }

        .wn-card-divider-top {
            margin-bottom: 0;
        }

        .wn-home-actions {
            display: flex;
            flex-direction: column;
            gap: 6px;
        }

        .wn-action-link {
            display: flex;
            min-height: 56px;
            align-items: center;
            gap: 11px;
            padding: 7px 11px;
            border: 1px solid rgba(255, 255, 255, .1);
            border-radius: 20px;
            background: rgba(255, 255, 255, .08);
            color: #fff;
            text-decoration: none;
            transition: transform .18s ease, border-color .18s ease, background .18s ease, box-shadow .18s ease;
        }

        .wn-action-link:hover {
            transform: translateY(-2px);
            border-color: rgba(246, 172, 29, .5);
            box-shadow: 0 8px 18px rgba(0, 0, 0, .13);
        }

        .wn-action-primary {
            border-color: rgba(238, 163, 20, .42);
            background: linear-gradient(105deg, rgba(179, 100, 0, .37), rgba(113, 63, 23, .33));
        }

        .wn-action-muted { background: rgba(255, 255, 255, .09); }
        .wn-action-roastery {
            border-color: rgba(230, 150, 22, .36);
            background: linear-gradient(105deg, rgba(164, 83, 0, .31), rgba(102, 54, 23, .32));
        }

        .wn-action-icon {
            display: grid;
            width: 40px;
            height: 40px;
            flex: 0 0 40px;
            place-items: center;
            border: 1px solid rgba(255, 255, 255, .14);
            border-radius: 15px;
            background: rgba(255, 255, 255, .11);
            color: #fff;
            font-size: 19px;
        }

        .wn-action-primary .wn-action-icon,
        .wn-action-roastery .wn-action-icon {
            border-color: rgba(255, 193, 75, .46);
            background: linear-gradient(145deg, #ffb21c, #da7900);
            box-shadow: 0 5px 12px rgba(225, 128, 0, .18);
            color: #fff;
        }

        .wn-action-copy {
            display: flex;
            min-width: 0;
            flex: 1;
            flex-direction: column;
            gap: 3px;
        }

        .wn-action-copy strong {
            color: #fff;
            font-size: 13px;
            font-weight: 900;
            line-height: 1.1;
        }

        .wn-action-copy small {
            color: rgba(255, 246, 237, .64);
            font-size: 10px;
            font-weight: 600;
            line-height: 1.2;
        }

        .wn-action-arrow {
            display: grid;
            width: 29px;
            height: 29px;
            flex: 0 0 29px;
            place-items: center;
            border-radius: 50%;
            background: rgba(255, 255, 255, .09);
            color: rgba(255, 255, 255, .62);
            font-size: 15px;
        }

        .wn-account-entry {
            display: flex;
            justify-content: center;
            align-items: center;
            flex-wrap: wrap;
            gap: 7px;
            margin-top: 10px;
            padding-top: 8px;
            border-top: 1px solid rgba(255, 255, 255, .1);
            color: rgba(255, 246, 237, .66);
            font-size: 10px;
            font-weight: 600;
        }

        .wn-account-entry a,
        .wn-account-entry button {
            padding: 5px 10px;
            border: 1px solid rgba(248, 182, 66, .28);
            border-radius: 999px;
            background: rgba(248, 182, 66, .1);
            color: #f8b642;
            cursor: pointer;
            font: inherit;
            font-weight: 800;
            text-decoration: none;
            transition: background .18s ease, border-color .18s ease;
        }

        .wn-account-entry a:hover,
        .wn-account-entry button:hover {
            border-color: rgba(248, 182, 66, .55);
            background: rgba(248, 182, 66, .18);
        }

        .wn-account-entry form { margin: 0; }
        .wn-home-footer {
            display: flex;
            flex-direction: column;
            align-items: center;
            gap: 4px;
            margin-top: 6px;
            padding-top: 6px;
            border-top: 1px solid rgba(255, 255, 255, .1);
            color: rgba(255, 246, 237, .36);
            font-size: 8px;
            font-weight: 800;
            letter-spacing: .18em;
        }

        .wn-home-footer nav {
            display: flex;
            align-items: center;
            gap: 8px;
            color: rgba(255, 246, 237, .64);
            font-size: 10px;
            font-weight: 600;
            letter-spacing: 0;
        }

        .wn-home-footer a {
            color: inherit;
            text-decoration: none;
        }

        .wn-home-footer a:hover { color: #f8b642; }

        .wn-home-shell a:focus-visible,
        .wn-home-shell button:focus-visible {
            outline: 3px solid rgba(245, 165, 0, .7);
            outline-offset: 3px;
        }

        @media (max-width: 380px) {
            .wn-home-shell { padding: 0 10px; }
            .wn-home-frame { padding-right: 12px; padding-left: 12px; }
            .wn-about-card { padding-right: 15px; padding-left: 15px; }
            .wn-about-heading { gap: 9px; }
            .wn-about-heading-copy { gap: 6px; }
            .wn-about-kicker { padding-right: 6px; padding-left: 6px; font-size: 7px; }
            .wn-about-heading h2 { font-size: 19px; }
        }

        @media (prefers-reduced-motion: reduce) {
            .wn-social-links a,
            .wn-action-link { transition: none; }
        }
    </style>
@endsection
