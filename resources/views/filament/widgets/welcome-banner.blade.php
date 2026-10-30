<div class="welcome-banner isolate" x-data="{ 
    visitorId: localStorage.getItem('wadah-visitor-id') || 'Friend'
}">
    {{-- Animated Micro-interactions --}}
    <div class="absolute inset-0 z-0 pointer-events-none">
        <div class="absolute -top-20 -left-20 w-80 h-80 bg-white/5 rounded-full blur-[100px] animate-pulse"></div>
        <div class="absolute -bottom-20 -right-20 w-80 h-80 bg-amber-500/10 rounded-full blur-[100px] animate-pulse"
            style="animation-duration: 4s"></div>
    </div>

    <div class="relative z-10 flex flex-col lg:flex-row items-center justify-between gap-12">
        <div class="flex-1 text-center lg:text-left">
            {{-- Badge --}}
            <div
                class="inline-flex items-center gap-2 px-4 py-1.5 rounded-full bg-white/10 backdrop-blur-xl border border-white/10 mb-6 group cursor-default">
                <span class="relative flex h-2 w-2">
                    <span
                        class="animate-ping absolute inline-flex h-full w-full rounded-full bg-amber-400 opacity-75"></span>
                    <span class="relative inline-flex rounded-full h-2 w-2 bg-amber-500"></span>
                </span>
                <span
                    class="text-[10px] font-black text-slate-500 dark:text-white/60 uppercase tracking-[0.2em] group-hover:text-amber-500 transition-colors">
                    Dashboard Active
                </span>
            </div>

            {{-- Greeting & Name --}}
            <h1
                class="text-4xl lg:text-6xl font-[900] text-slate-900 dark:text-white tracking-tight leading-[1.1] mb-6">
                {{ $greeting }}, <br class="hidden lg:block">
                <span class="bg-gradient-to-r from-amber-400 to-amber-600 bg-clip-text text-transparent">
                    {{ $user->name }}
                </span> 
            </h1>

            <p class="text-slate-500 dark:text-white/50 text-base lg:text-lg max-w-xl leading-relaxed font-medium">
                Gimana hari ini? Siap pantau performa <span
                    class="text-slate-900 dark:text-white font-bold decoration-amber-500 decoration-2 underline-offset-4 underline">WadahNgopi</span>
                dan scale up bisnis kita?
            </p>
        </div>

        {{-- Action Buttons (Semi-Mobile Style) --}}
        <div class="flex flex-col sm:flex-row lg:flex-col xl:flex-row items-stretch gap-4 shrink-0">
            <a href="{{ route('home') }}" target="_blank"
                class="group flex items-center justify-between gap-6 px-8 py-5 rounded-[2rem] bg-amber-500 text-espresso-950 hover:bg-amber-400 hover:-translate-y-1 active:scale-95 transition-all shadow-[0_20px_40px_-10px_rgba(245,158,11,0.4)] no-underline">
                <div class="flex flex-col items-start">
                    <span class="text-[0.7rem] font-black uppercase tracking-widest opacity-60">Web Store</span>
                    <span class="text-sm font-bold">Lihat Website</span>
                </div>
                <div
                    class="w-10 h-10 rounded-full bg-espresso-950/10 flex items-center justify-center group-hover:translate-x-1 transition-transform">
                    <x-heroicon-m-arrow-top-right-on-square class="w-5 h-5" />
                </div>
            </a>

            @if(auth()->user()->role === 'developer' || auth()->user()->role === 'admin')
                <a href="{{ \App\Filament\Resources\CafeResource::getUrl('index') }}"
                    class="group flex items-center justify-between gap-6 px-8 py-5 rounded-[2rem] bg-slate-100 dark:bg-white/10 backdrop-blur-2xl border border-slate-200 dark:border-white/10 text-slate-900 dark:text-white hover:bg-slate-200 dark:hover:bg-white/20 hover:-translate-y-1 active:scale-95 transition-all no-underline">
                    <div class="flex flex-col items-start">
                        <span
                            class="text-[0.7rem] font-black uppercase tracking-widest text-slate-500 dark:text-white/40">Management</span>
                        <span class="text-sm font-bold">Kelola Cafe</span>
                    </div>
                    <div
                        class="w-10 h-10 rounded-full bg-slate-200 dark:bg-white/10 flex items-center justify-center group-hover:rotate-12 transition-transform">
                        <x-heroicon-m-building-storefront class="w-5 h-5 text-amber-500" />
                    </div>
                </a>
            @endif
        </div>
    </div>
</div>