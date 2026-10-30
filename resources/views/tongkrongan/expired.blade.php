@extends('layouts.app')

@section('title', 'Tongkrongan Expired - WadahNgopi')

@section('content')
<div class="flex flex-col items-center justify-center min-h-screen px-6 text-center"
     style="background: radial-gradient(ellipse 100% 100% at 50% 50%, #FFFBF0 0%, #FEF3C7 100%);">
    <span class="text-6xl mb-4">⏰</span>
    <h2 class="text-2xl font-black text-[#2C1810] mb-2">Tongkrongan Expired</h2>
    <p class="text-slate-500 text-sm mb-6">List "{{ $tongkrongan->title }}" udah lewat 24 jam.</p>
    <a href="{{ route('tongkrongan.create') }}" 
       class="inline-flex items-center gap-2 bg-amber-500 text-white px-6 py-3 rounded-2xl font-bold text-sm no-underline hover:bg-amber-600 transition-all">
        <i class="ph-fill ph-plus-circle"></i>
        Bikin Tongkrongan Baru
    </a>
</div>
@endsection
