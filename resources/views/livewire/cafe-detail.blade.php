<div wire:poll.60s="refreshStatus">
    @if($hasCafe)
        {{-- Status badge with Premium Glassmorphism --}}
        <div class="realtime-status" x-data="{ isOpen: @js($isOpen) }">
            @if($isOpen)
                <div
                    class="flex items-center gap-2 px-4 py-2 rounded-full bg-emerald-500/20 backdrop-blur-md border border-emerald-400/30 shadow-lg shadow-emerald-900/10">
                    <span class="relative flex h-2.5 w-2.5">
                        <span
                            class="animate-ping absolute inline-flex h-full w-full rounded-full bg-emerald-400 opacity-75"></span>
                        <span class="relative inline-flex rounded-full h-2.5 w-2.5 bg-emerald-400"></span>
                    </span>
                    <span class="text-xs font-black text-white tracking-widest uppercase"
                        style="text-shadow: 0 1px 2px rgba(0,0,0,0.1);">Sedang Buka</span>
                </div>
            @else
                <div
                    class="flex items-center gap-2 px-4 py-2 rounded-full bg-rose-500/20 backdrop-blur-md border border-rose-400/30 shadow-lg shadow-rose-900/10">
                    <span class="h-2.5 w-2.5 bg-rose-400 rounded-full"></span>
                    <span class="text-xs font-black text-white tracking-widest uppercase"
                        style="text-shadow: 0 1px 2px rgba(0,0,0,0.1);">Tutup</span>
                </div>
            @endif
        </div>
    @endif
</div>