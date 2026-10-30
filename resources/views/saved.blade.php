@extends('layouts.app')

@section('title', 'Tersimpan - WadahNgopi.Com')

@section('content')
    <div class="bg-noise flex flex-col min-h-screen relative overflow-hidden"
         style="background: 
            radial-gradient(ellipse 80% 60% at 18% -8%, rgba(234,179,8,0.1) 0%, transparent 58%),
            radial-gradient(ellipse 55% 45% at 82% 105%, rgba(202,138,4,0.06) 0%, transparent 52%),
            radial-gradient(ellipse 100% 100% at 50% 50%, #FFFBF0 0%, #FEF3C7 100%);">
        
        {{-- Ambient Floating Orbs --}}
        <div class="absolute -top-16 -left-12 w-72 h-72 rounded-full pointer-events-none"
             style="background: radial-gradient(circle, rgba(251,191,36,0.2) 0%, transparent 70%); animation: ambient-drift 14s ease-in-out infinite;"></div>
        <div class="absolute bottom-24 -right-16 w-80 h-80 rounded-full pointer-events-none"
             style="background: radial-gradient(circle, rgba(255,240,200,0.3) 0%, transparent 70%); animation: ambient-drift 22s ease-in-out infinite reverse;"></div>

        <header class="explore-hero-2026 relative z-20" x-data="{ isScrolled: false }" :class="{ 'is-compact': isScrolled }"
            @scroll.window="isScrolled = window.pageYOffset > 50">
            
            {{-- Premium Background Orbs (Matches Live Design) --}}
            <div class="hero-orb hero-orb-1"></div>
            <div class="hero-orb hero-orb-2"></div>
            <div class="hero-orb hero-orb-3"></div>

            {{-- Branding Section --}}
            <div class="explore-branding-wrapper">
                <div class="explore-topbar">
                    <div class="explore-logo-box">
                        <img src="{{ asset('wadahngopi.png') }}" alt="Logo">
                    </div>
                    <div class="flex flex-col">
                        <h1 class="explore-brand">Wadah<span class="text-shimmer">Ngopi</span></h1>
                        <p class="explore-tagline">SIMPAN WADAH FAVORITMU</p>
                    </div>
                </div>
            </div>
        </header>

        <div class="header-spacer-saved"></div>

        <main class="flex-1 w-full max-w-[430px] mx-auto relative z-10 pt-4">
            <livewire:saved-items />
        </main>
    </div>
@endsection