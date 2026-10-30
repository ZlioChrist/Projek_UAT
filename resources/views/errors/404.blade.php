@extends('layouts.app')

@section('title', '404 - Cafe Nya Lagi Di Perbaiki!')

@section('content')
    <div class="flex flex-col items-center justify-center min-h-[80vh] px-6 text-center animate-up">
        <div class="relative mb-8">
            {{-- Glassmorphism Icon --}}
            <div
                class="w-32 h-32 rounded-[2.5rem] bg-white/40 backdrop-blur-2xl border border-white/50 shadow-2xl flex items-center justify-center">
                <i class="ph ph-coffee-bean text-[5rem] text-[--color-coffee-dark] opacity-20"></i>
            </div>
            <div
                class="absolute -top-2 -right-2 w-12 h-12 rounded-2xl bg-[--color-amber] flex items-center justify-center text-white shadow-lg animate-bounce">
                <span class="font-black text-lg">?</span>
            </div>
        </div>

        <h1 class="text-[5rem] font-black text-[--color-espresso] leading-none mb-4 opacity-10">404</h1>

        <h2 class="text-2xl font-black text-[--color-espresso] mb-3">Halaman tidak ditemukan</h2>

        <p class="text-slate-400 font-bold text-[0.95rem] mb-10 max-w-[280px] leading-relaxed">
            Halamannya ilang kayak janji manis doi. Balik ke Beranda aja yuk biar nggak bengong!
        </p>

        <a href="{{ route('home') }}"
            class="btn btn-primary px-10 py-4 h-16 shadow-xl shadow-[--color-coffee-light]/30 active:scale-95 transition-all">
            <i class="ph-fill ph-house-line text-xl"></i> Kembali ke Beranda
        </a>
    </div>

    <style>
        /* Ensure the error page feels distinct yet integrated */
        .main-container {
            background: radial-gradient(circle at top right, rgba(111, 78, 55, 0.05), transparent),
                radial-gradient(circle at bottom left, rgba(214, 185, 143, 0.05), transparent);
        }
    </style>
@endsection
