{{-- Skeleton Info Card — matches actual information-feed card structure --}}
<div class="group relative flex gap-4 p-3.5 bg-white rounded-[28px] border border-black/5 shadow-[0_4px_12px_rgba(20,12,8,0.03)] overflow-hidden skeleton-card"
    aria-hidden="true">
    {{-- Thumbnail --}}
    <div class="w-[110px] h-[110px] shrink-0 rounded-[20px] bg-[#F5EFED] skeleton-shimmer overflow-hidden isolate shadow-inner"></div>

    {{-- Text Content --}}
    <div class="flex-1 min-w-0 py-1.5 flex flex-col justify-center">
        {{-- Category & Date --}}
        <div class="flex items-center gap-2 mb-2">
            <div class="h-[18px] w-12 bg-gray-100 skeleton-shimmer rounded-md"></div>
            <div class="h-[18px] w-16 bg-gray-100 skeleton-shimmer rounded-md"></div>
        </div>

        {{-- Title (2 lines) --}}
        <div class="h-4 w-full bg-gray-100 skeleton-shimmer rounded-md mb-2"></div>
        <div class="h-4 w-3/4 bg-gray-100 skeleton-shimmer rounded-md mb-2"></div>

        {{-- Views --}}
        <div class="mt-auto flex items-center gap-1.5">
            <div class="h-3.5 w-24 bg-gray-100 skeleton-shimmer rounded-md"></div>
        </div>
    </div>
</div>