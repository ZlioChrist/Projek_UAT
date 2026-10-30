@props(['isFeatured' => false])
<div class="cafe-card-2026 skeleton-card {{ $isFeatured ? 'card-featured' : '' }}" aria-hidden="true">
    {{-- Image Area --}}
    <div class="cafe-card-image skeleton-shimmer relative">
        {{-- Status Badge Placeholder --}}
        <div class="absolute top-3 left-3 z-10">
            <div class="h-5 w-16 bg-white/30 rounded-full skeleton-shimmer"></div>
        </div>
    </div>

    {{-- Content Area --}}
    <div class="cafe-card-content">
        {{-- Title --}}
        <div class="space-y-2">
            <div class="h-[18px] w-3/4 bg-gray-200 rounded-md skeleton-shimmer"></div>
            {{-- Address with icon placeholder --}}
            <div class="flex items-center gap-1.5">
                <div class="h-3 w-3 bg-[#F59E0B]/20 rounded-full skeleton-shimmer"></div>
                <div class="h-3 w-2/3 bg-gray-200 rounded-md skeleton-shimmer"></div>
            </div>
        </div>

        {{-- Facility Tags --}}
        <div class="cafe-card-tags pt-2">
            <div class="h-5 w-14 bg-gray-200 rounded-full skeleton-shimmer"></div>
            <div class="h-5 w-12 bg-gray-200 rounded-full skeleton-shimmer"></div>
            <div class="h-5 w-10 bg-gray-200 rounded-full skeleton-shimmer"></div>
        </div>
    </div>
</div>