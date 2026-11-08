<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}"
    class="{{ request()->routeIs('explore', 'roastery', 'information', 'information.*') ? 'page-canvas-white' : (request()->routeIs('roastery.show', 'cafes.show') ? 'page-canvas-detail' : '') }}"
    style="{{ request()->routeIs('explore', 'roastery', 'information', 'information.*') ? 'min-height:100%;background:#fff;background-image:none;' : (request()->routeIs('roastery.show', 'cafes.show') ? 'min-height:100%;background:#fcfaf7;background-image:none;' : '') }}">

<head>
    <script>document.documentElement.classList.add('js-active')</script>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="theme-color" content="#6F4E37">
    <meta name="csrf-token" content="{{ csrf_token() }}">

    <title>@yield('title', 'WadahNgopi')</title>

    <link rel="icon" type="image/png" sizes="32x32" href="{{ asset('favicon-32x32.png') }}">
    <link rel="icon" type="image/png" sizes="192x192" href="{{ asset('favicon-192x192.png') }}">
    <link rel="apple-touch-icon" href="{{ asset('favicon-192x192.png') }}">

    <link rel="manifest" href="/manifest.json">

    {{-- Original SEO & Fonts --}}
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Outfit:wght@300;400;500;600;700;800;900&family=Inter:wght@300;400;500;600;700;800;900&family=Plus+Jakarta+Sans:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    <script src="https://unpkg.com/@phosphor-icons/web" defer></script>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">

    @viteReactRefresh
    @vite(['resources/css/app.css', 'resources/js/app.js', 'resources/js/app.jsx'])
</head>

<body class="{{ request()->routeIs('explore', 'roastery', 'information', 'information.*') ? 'page-canvas-white' : (request()->routeIs('roastery.show', 'cafes.show') ? 'page-canvas-detail' : '') }}"
    style="{{ request()->routeIs('explore', 'roastery', 'information', 'information.*') ? 'min-height:100vh;background:#fff;background-image:none;' : (request()->routeIs('roastery.show', 'cafes.show') ? 'min-height:100vh;background:#fcfaf7;background-image:none;' : '') }}">

    {{-- Splash Screen Manual --}}
    <div id="splash-screen" class="splash-screen"
        style="position:fixed; inset:0; z-index:99999; background:#1A0F0A; display:flex; align-items:center; justify-content:center;">
        <script>
            // Anti-Flicker: Langsung sembunyikan kalau sudah pernah tampil di sesi ini
            if (sessionStorage.getItem('splash_shown')) {
                document.getElementById('splash-screen').style.display = 'none';
            }
        </script>
        <div class="splash-logo" style="display:flex; flex-direction:column; align-items:center;">
            <div class="splash-icon"
                style="width:56px; height:56px; border-radius:16px; overflow:hidden; display:flex; align-items:center; justify-content:center; background:#F59E0B;">
                <img src="{{ asset('wadahngopi.png') }}" alt="WadahNgopi" style="width:36px; height:36px; object-fit:contain;">
            </div>
            <div class="splash-brand" style="margin-top:16px;">
                <div class="splash-brand-name" style="color:white; font-weight:900; font-size:24px;">Wadah<span style="color:#F59E0B;">Ngopi</span></div>
            </div>
        </div>
    </div>

    <script>
        (function () {
            const s = document.getElementById('splash-screen');
            if (!s) return;

            // Jika sudah pernah tampil, hapus dari DOM secara instan
            if (sessionStorage.getItem('splash_shown')) {
                s.remove();
                return;
            }

            const hideSplash = () => {
                s.style.transition = 'opacity 0.6s cubic-bezier(0.4, 0, 0.2, 1)';
                s.style.opacity = '0';
                setTimeout(() => {
                    s.remove();
                    // Tandai bahwa splash sudah tampil di sesi ini
                    sessionStorage.setItem('splash_shown', 'true');
                }, 600);
            };

            // Tunggu load atau fallback setelah 1.5 detik
            if (document.readyState === 'complete') {
                hideSplash();
            } else {
                window.addEventListener('load', hideSplash);
                setTimeout(hideSplash, 1500);
            }
        })();
    </script>

    <div id="app-toast" class="app-toast" role="status" aria-live="polite" aria-atomic="true" hidden>
        <i id="app-toast-icon" class="ph-fill ph-check-circle app-toast-icon" aria-hidden="true"></i>
        <span id="app-toast-message" class="app-toast-message"></span>
        <a id="app-toast-action" class="app-toast-action" hidden></a>
        <button id="app-toast-dismiss" class="app-toast-dismiss" type="button" aria-label="Tutup notifikasi">
            <i class="ph-bold ph-x" aria-hidden="true"></i>
        </button>
    </div>

    <x-onboarding />

    <div class="main-container {{ request()->routeIs('explore', 'roastery', 'information', 'information.*') ? 'page-canvas-white' : (request()->routeIs('roastery.show', 'cafes.show') ? 'page-canvas-detail' : '') }}" id="main-container"
        style="{{ request()->routeIs('explore', 'roastery', 'information', 'information.*') ? 'min-height:100vh;background:#fff;' : (request()->routeIs('roastery.show', 'cafes.show') ? 'min-height:100vh;background:#fcfaf7;' : '') }}">
        @auth
            @if(in_array(\App\Support\AuthenticatedUser::require()->role, ['developer', 'admin', 'roastery'], true))
                <nav class="relative z-40 flex justify-end gap-2 px-4 py-2 text-xs font-bold" aria-label="Navigasi pengelola">
                    <a href="{{ route('owner.dashboard') }}" class="rounded-full border border-amber-300 bg-amber-50 px-3 py-2 text-amber-900 no-underline">Dashboard kedai</a>
                    <a href="{{ route('owner.orders.index') }}" class="rounded-full border border-slate-200 bg-white/90 px-3 py-2 text-slate-700 no-underline">Notifikasi pesanan</a>
                    <a href="{{ url('/admin') }}" class="rounded-full border border-slate-200 bg-white/90 px-3 py-2 text-slate-700 no-underline">Panel pengelola</a>
                </nav>
            @endif
        @endauth
        @yield('content')

        {{-- REACT BOTTOM NAVIGATION (HYBRID) --}}
        <div id="bottom-nav-react"
             data-current-route="{{ optional(request()->route())->getName() ?? '' }}"
             data-is-authenticated="{{ auth()->check() ? 'true' : 'false' }}"
             data-current-user-name="{{ auth()->user()?->name ?? '' }}"
             data-current-user-photo-url="{{ auth()->user()?->profile_photo_path ? \Illuminate\Support\Facades\Storage::disk('public')->url(auth()->user()->profile_photo_path) : '' }}"
             data-routes="{{ json_encode([
                 'home' => route('home'),
                 'roastery' => route('roastery'),
                 'explore' => route('explore'),
                 'information' => route('information'),
                 'account' => route('account'),
                 'login' => route('login'),
             ]) }}">
        </div>

        {{-- OLD NAV (LEGACY BACKUP) --}}
        {{--
        <nav class="bottom-nav">
            ...
        </nav>
        --}}
    </div>

    @stack('scripts')
</body>
</html>
