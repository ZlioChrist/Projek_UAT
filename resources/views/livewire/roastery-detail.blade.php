<div wire:poll.60s="refreshStatus" class="inline-block" x-data="{ isOpen: @js($isOpen) }">
    @if($hasRoastery)
        @if($isOpen)
            <div
                class="inline-flex items-center gap-1.5 px-3 py-1 bg-emerald-500/90 backdrop-blur-md rounded-lg shadow-soft border border-emerald-400/30 transition-all duration-500">
                <span class="w-1.5 h-1.5 rounded-full bg-white animate-pulse shadow-[0_0_8px_rgba(255,255,255,0.8)]"></span>
                <span class="text-white text-[0.65rem] font-black uppercase tracking-widest">Buka Sekarang</span>
            </div>
        @else
            <div
                class="inline-flex items-center gap-1.5 px-3 py-1 bg-rose-500/90 backdrop-blur-md rounded-lg shadow-soft border border-rose-400/30 transition-all duration-500">
                <span class="w-1.5 h-1.5 rounded-full bg-white/80"></span>
                <span class="text-white text-[0.65rem] font-black uppercase tracking-widest">Tutup</span>
            </div>
        @endif
    @endif
</div>