{{-- Skeleton Saved Card — pixel-perfect match with saved-items.blade.php lines 27-51 --}}
<div class="saved-card group skeleton-card" aria-hidden="true" style="pointer-events:none;">
    {{-- Left: Fixed Image (w-20 h-20 shrink-0 rounded-[18px]) --}}
    <div class="saved-card__image-container bg-gray-100 skeleton-shimmer">
        {{-- Status Dot --}}
        <div class="absolute top-1.5 right-1.5 w-3 h-3 bg-white/90 backdrop-blur-sm rounded-full flex items-center justify-center shadow-sm z-20">
            <div class="w-1.5 h-1.5 rounded-full bg-gray-200 skeleton-shimmer"></div>
        </div>
    </div>

    {{-- Middle: Content Area --}}
    <div class="flex-1 min-w-0 flex flex-col justify-center gap-1.5">
        {{-- Title --}}
        <div class="h-4 w-4/5 bg-gray-200 rounded-md skeleton-shimmer"></div>

        {{-- Address --}}
        <div class="flex items-center gap-1">
            <div class="h-3 w-3 shrink-0 bg-[#F59E0B]/20 rounded-full skeleton-shimmer"></div>
            <div class="h-3 w-3/5 bg-gray-200 rounded-md skeleton-shimmer"></div>
        </div>

        {{-- Tags --}}
        <div class="flex items-center gap-2 mt-1">
            <div class="px-2 py-0.5 w-12 h-[18px] bg-gray-200/50 rounded-md skeleton-shimmer"></div>
            <div class="px-2 py-0.5 w-14 h-[18px] bg-gray-200/50 rounded-md skeleton-shimmer"></div>
        </div>
    </div>

    {{-- Right: Actions (pl-2 border-l) --}}
    <div class="pl-2 border-l border-[#1a0f0a]/5 flex flex-col justify-center">
        <div class="flex items-center justify-center w-9 h-9 bg-rose-50/50 rounded-xl">
            <div class="w-5 h-5 bg-[#EF4444]/15 rounded skeleton-shimmer"></div>
        </div>
    </div>
</div>