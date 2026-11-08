@extends('layouts.app')

@section('title', 'Tersimpan - WadahNgopi.Com')

@section('content')
    <main class="wn-customer-page wn-saved-page">
        <a href="{{ auth()->check() ? route('account') : route('explore') }}" class="wn-customer-back">
            <i class="ph ph-arrow-left" aria-hidden="true"></i> {{ auth()->check() ? 'Kembali ke profil' : 'Jelajahi kafe' }}
        </a>
        <header class="wn-customer-page-header">
            <span class="wn-customer-eyebrow"><i class="ph ph-heart" aria-hidden="true"></i> Favorit kamu</span>
            <h1>Tempat tersimpan</h1>
            <p>Kafe dan roastery yang kamu simpan, siap dikunjungi kapan saja.</p>
        </header>
        <section class="wn-saved-list" aria-label="Daftar tempat tersimpan">
            <livewire:saved-items />
        </section>
    </main>
@endsection