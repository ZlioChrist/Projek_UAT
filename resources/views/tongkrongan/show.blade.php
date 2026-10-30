@extends('layouts.app')

@section('title', $tongkrongan->title . ' — Tongkrongan WadahNgopi')
@section('og_title', $tongkrongan->title . ' — Vote Cafe Bareng!')
@section('og_description', 'Vote cafe favorit lo buat tongkrongan bareng di WadahNgopi!')

@section('content')
<div class="bg-noise flex flex-col min-h-screen relative overflow-hidden"
     style="background: 
         radial-gradient(ellipse 80% 60% at 18% -8%, rgba(234,179,8,0.1) 0%, transparent 58%),
         radial-gradient(ellipse 100% 100% at 50% 50%, #FFFBF0 0%, #FEF3C7 100%);">
    
    <header class="sticky top-0 z-50 bg-white/80 backdrop-blur-xl border-b border-slate-100 px-6 py-4">
        <div class="flex items-center gap-3">
            <a href="{{ route('explore') }}" class="w-10 h-10 rounded-full bg-slate-100 flex items-center justify-center text-slate-600 hover:bg-slate-200 transition-all active:scale-90 no-underline">
                <i class="ph-bold ph-arrow-left text-lg"></i>
            </a>
            <div>
                <h1 class="text-lg font-black text-[#2C1810]">Tongkrongan</h1>
                <p class="text-[0.65rem] font-bold text-slate-400 uppercase tracking-widest">Vote cafe favorit lo</p>
            </div>
        </div>
    </header>

    <main class="flex-1 w-full max-w-[430px] mx-auto relative z-10 px-6 pt-6 pb-32">
        <div id="tongkrongan-view-react" data-tongkrongan="{{ json_encode($tongkrongan) }}"></div>
    </main>
</div>
@endsection
