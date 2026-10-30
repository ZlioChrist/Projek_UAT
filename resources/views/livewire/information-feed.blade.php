<div class="block min-h-screen pb-32 bg-[#FAF9F6]">
    {{-- Premium Header (Matches Explore & Saved) --}}
    <header class="explore-hero-2026" x-data="{ isScrolled: false }" :class="{ 'is-compact': isScrolled }"
        @scroll.window="isScrolled = window.pageYOffset > 50">
        {{-- Premium Background Orbs --}}
        <div class="hero-orb hero-orb-1"></div>
        <div class="hero-orb hero-orb-2"></div>
        <div class="hero-orb hero-orb-3"></div>

        {{-- Top Bar Section --}}
        <div class="explore-branding-wrapper">
            <div class="explore-topbar">
                <div class="explore-logo-box">
                    <img src="{{ asset('wadahngopi.png') }}" alt="Logo">
                </div>
                <div class="flex flex-col">
                    <h1 class="explore-brand">Wadah<span>Ngopi</span></h1>
                    <p class="explore-tagline">INFO & EDUKASI KOPI</p>
                </div>
            </div>
        </div>

        {{-- Category Pills (Matches Explore Style) --}}
        <div class="filter-scroll-wrapper">
            <div class="explore-category-pills">
                <button class="category-pill disabled:opacity-50 disabled:cursor-not-allowed"
                    :class="$wire.activeCategory === 'Semua' ? 'active' : ''" wire:click="setCategory('Semua')"
                    wire:loading.attr="disabled" wire:target="setCategory('Semua')">
                    <i class="ph-fill ph-check-square" x-show="$wire.activeCategory === 'Semua'" wire:loading.remove
                        wire:target="setCategory('Semua')"></i>
                    <i class="ph-bold ph-spinner animate-spin" wire:loading wire:target="setCategory('Semua')"></i>
                    <span>Semua</span>
                </button>
                @foreach (['Berita', 'Edukasi', 'Lomba', 'Promo'] as $cat)
                    <button class="category-pill disabled:opacity-50 disabled:cursor-not-allowed"
                        :class="$wire.activeCategory === '{{ $cat }}' ? 'active' : ''"
                        wire:click="setCategory('{{ $cat }}')" wire:loading.attr="disabled"
                        wire:target="setCategory('{{ $cat }}')">
                        <span wire:loading.remove wire:target="setCategory('{{ $cat }}')">
                            @if($cat === 'Berita') <i class="ph-fill ph-newspaper"></i>
                            @elseif($cat === 'Edukasi') <i class="ph-fill ph-book-open"></i>
                            @elseif($cat === 'Lomba') <i class="ph-fill ph-trophy"></i>
                            @elseif($cat === 'Promo') <i class="ph-fill ph-tag"></i>
                            @endif
                        </span>
                        <i class="ph-bold ph-spinner animate-spin" wire:loading wire:target="setCategory('{{ $cat }}')"></i>
                        <span>{{ $cat }}</span>
                    </button>
                @endforeach
            </div>
        </div>
    </header>

    {{-- Premium Skeleton --}}
    <div wire:loading class="info-list-container">
        <template x-for="i in 3" :key="'skel-'+i">
            <x-skeleton.info-card />
        </template>
    </div>

    {{-- Content List --}}
    <div wire:loading.remove class="info-list-container"></div>

    {{-- Content Spacer --}}
    <div class="header-spacer-info"></div>

    {{-- Main Content Window --}}
    <div class="px-6 space-y-10">
        {{-- Populer Section --}}
        @if($activeCategory === 'Semua' && $popularInformations->isNotEmpty())
            <div class="overflow-hidden" wire:transition.fade>
                <div class="flex items-center justify-between mb-5">
                    <div class="flex items-center gap-2">
                        <div class="w-1 h-4 bg-[#F59E0B] rounded-full"></div>
                        <h2 class="text-xs font-black text-[#2C1810] uppercase tracking-widest">Populer Saat Ini</h2>
                    </div>
                </div>

                <div class="flex gap-5 overflow-x-auto no-scrollbar -mx-6 px-6 pb-6 snap-x snap-mandatory">
                    @foreach ($popularInformations as $info)
                        <a href="{{ route('information.show', $info) }}"
                            class="group flex-shrink-0 snap-center relative transition-transform active:scale-95">
                            {{-- Premium Vertical Card --}}
                            <div
                                class="w-[280px] aspect-[4/3] rounded-[32px] overflow-hidden shadow-[0_12px_40px_0_rgba(20,12,8,0.06)] relative isolate border border-white/60 bg-[#F5EFED] group-hover:shadow-[0_24px_60px_-12px_rgba(20,12,8,0.12)] transition-shadow duration-500">

                                {{-- Image --}}
                                @php
                                    $image = $info->image_path ? (str_starts_with($info->image_path, 'http') ? $info->image_path : Storage::url($info->image_path)) : null;
                                @endphp

                                @if ($image)
                                    {{-- Main Image (Full Bleed) --}}
                                    <img src="{{ $image }}"
                                        class="absolute inset-0 w-full h-full object-cover z-10 transition-transform duration-700 group-hover:scale-105"
                                        alt="{{ $info->title }}">
                                @else
                                    <div class="absolute inset-0 bg-[#F5EFED] flex items-center justify-center">
                                        <i class="ph-fill ph-newspaper text-5xl text-[#1a0f0a]/10"></i>
                                    </div>
                                @endif

                                {{-- Gradient Overlay --}}
                                <div
                                    class="absolute inset-0 bg-gradient-to-t from-[#1A0F0A] via-[#1A0F0A]/40 to-transparent opacity-90 z-20 pointer-events-none">
                                </div>

                                {{-- Floating View Count --}}
                                <div
                                    class="absolute top-4 right-4 px-3 py-1.5 bg-white/10 backdrop-blur-md border border-white/20 rounded-full flex items-center gap-1.5 text-white/90 text-[0.65rem] font-bold shadow-sm z-30">
                                    <i class="ph-fill ph-eye text-[#F59E0B]"></i>
                                    <span>{{ number_format($info->views) }}</span>
                                </div>

                                {{-- Content --}}
                                <div
                                    class="absolute bottom-0 left-0 right-0 p-6 pt-12 transform translate-y-2 group-hover:translate-y-0 transition-transform duration-300 z-30">
                                    <span
                                        class="inline-block px-3 py-1 mb-3 text-[0.6rem] font-black text-[#2C1810] uppercase bg-[#F59E0B] rounded-lg shadow-lg shadow-orange-500/20">
                                        {{ $info->category }}
                                    </span>
                                    <h3
                                        class="text-white text-lg font-bold leading-tight line-clamp-2 mb-2 group-hover:text-[#F59E0B] transition-colors">
                                        {{ $info->title }}
                                    </h3>
                                    <p class="text-white/70 text-xs font-medium line-clamp-1">
                                        {{ $info->short_content }}
                                    </p>
                                </div>
                            </div>
                        </a>
                    @endforeach
                </div>
            </div>
        @endif

        {{-- Terbaru Feed --}}
        <div wire:key="feed-{{ $activeCategory }}">
            <div class="flex items-center justify-between mb-5">
                <div class="flex items-center gap-2">
                    <div class="w-1 h-4 bg-[#2C1810] rounded-full"></div>
                    <h2 class="text-xs font-black text-[#2C1810] uppercase tracking-widest">
                        {{ $activeCategory === 'Semua' ? 'Terbaru' : 'Artikel ' . $activeCategory }}
                    </h2>
                </div>

                {{-- Loading Indicator --}}
                <div wire:loading class="text-[#F59E0B]">
                    <i class="ph ph-circle-notch animate-spin text-lg"></i>
                </div>
            </div>

            <div class="flex flex-col gap-4">
                @forelse($informations as $info)
                    <a href="{{ route('information.show', $info) }}"
                        class="group relative flex gap-4 p-3.5 bg-white hover:bg-white rounded-[28px] border border-black/5 shadow-[0_4px_12px_rgba(20,12,8,0.03)] hover:shadow-[0_16px_40px_-12px_rgba(20,12,8,0.08)] transition-all duration-400 active:scale-[0.98] overflow-hidden">

                        {{-- Hover Effect Glow --}}
                        <div
                            class="absolute inset-0 bg-gradient-to-r from-amber-400/0 via-amber-400/5 to-amber-400/0 translate-x-[-100%] group-hover:translate-x-[100%] transition-transform duration-1000 pointer-events-none">
                        </div>

                        {{-- Thumbnail --}}
                        <div
                            class="w-[110px] h-[110px] shrink-0 rounded-[20px] overflow-hidden bg-[#F5EFED] relative shadow-inner isolate">
                            @php
                                $thumb = $info->image_path ? (str_starts_with($info->image_path, 'http') ? $info->image_path : Storage::url($info->image_path)) : null;
                            @endphp
                            @if($thumb)
                                {{-- Main Image (Cover) --}}
                                <img src="{{ $thumb }}"
                                    class="w-full h-full object-cover transition-transform duration-500 group-hover:scale-110"
                                    alt="{{ $info->title }}" loading="lazy">
                            @else
                                <div class="w-full h-full flex items-center justify-center bg-[#F5EFED] text-[#D7CCC8]">
                                    <i class="ph-fill ph-image text-3xl"></i>
                                </div>
                            @endif
                        </div>

                        {{-- Text Content --}}
                        <div class="flex-1 min-w-0 py-1.5 flex flex-col justify-center">
                            <div class="flex items-center gap-2 mb-2">
                                <span
                                    class="text-[0.6rem] font-black text-[#F59E0B] uppercase tracking-wider px-2 py-0.5 bg-[#FFF8E1] rounded-md">
                                    {{ $info->category }}
                                </span>
                                <span class="text-[0.65rem] font-bold text-[#1a0f0a]/40 bg-gray-50 px-2 py-0.5 rounded-md">
                                    {{ $info->created_at->diffForHumans() }}
                                </span>
                            </div>

                            <h3
                                class="text-[1rem] font-bold text-[#2C1810] leading-snug line-clamp-2 mb-2 group-hover:text-[#F59E0B] transition-colors">
                                {{ $info->title }}
                            </h3>

                            <div class="flex items-center gap-3 mt-auto">
                                <div class="flex items-center gap-1.5 text-[0.7rem] font-bold text-[#8B7355]">
                                    <i class="ph-fill ph-eye text-[#F59E0B]"></i>
                                    {{ number_format($info->views) }} <span
                                        class="font-medium text-[#1a0f0a]/40">Views</span>
                                </div>
                            </div>
                        </div>

                        {{-- Arrow Indicator --}}
                        <div
                            class="absolute right-4 top-1/2 -translate-y-1/2 opacity-0 group-hover:opacity-100 transform translate-x-2 group-hover:translate-x-0 transition-all duration-300">
                            <i class="ph-bold ph-caret-right text-[#F59E0B]"></i>
                        </div>
                    </a>
                @empty
                    <div class="col-span-full empty-state-premium">
                        <div class="empty-state-illustration">
                            <svg viewBox="0 0 200 200" fill="none" xmlns="http://www.w3.org/2000/svg">
                                <rect x="40" y="40" width="120" height="120" rx="16" fill="#F5EFED" stroke="#E6E1DC"
                                    stroke-width="1" />
                                <rect x="50" y="50" width="100" height="100" rx="10" fill="white" stroke="#2C1810"
                                    stroke-width="2" />
                                <rect x="62" y="62" width="40" height="28" rx="4" fill="#F59E0B" />
                                <line x1="62" y1="100" x2="138" y2="100" stroke="#E6E1DC" stroke-width="3"
                                    stroke-linecap="round" />
                                <line x1="62" y1="115" x2="120" y2="115" stroke="#E6E1DC" stroke-width="3"
                                    stroke-linecap="round" />
                                <line x1="62" y1="130" x2="100" y2="130" stroke="#E6E1DC" stroke-width="3"
                                    stroke-linecap="round" />
                                <line x1="110" y1="65" x2="138" y2="65" stroke="#E6E1DC" stroke-width="2"
                                    stroke-linecap="round" />
                                <line x1="110" y1="75" x2="130" y2="75" stroke="#E6E1DC" stroke-width="2"
                                    stroke-linecap="round" />
                                <line x1="110" y1="85" x2="138" y2="85" stroke="#E6E1DC" stroke-width="2"
                                    stroke-linecap="round" />
                            </svg>
                        </div>
                        <h3>Belum Ada Konten</h3>
                        <p>Kategori <span class="font-bold text-[#F59E0B]">{{ $activeCategory }}</span> belum memiliki
                            artikel.</p>
                        <button class="empty-state-cta" wire:click="setCategory('Semua')">
                            <i class="ph-bold ph-arrow-counter-clockwise"></i>
                            Kembali ke Semua
                        </button>
                    </div>
                @endforelse
            </div>

            {{-- Load More Section (Infinite Scroll) --}}
            @php
                $hasMore = $informations->count() >= $perPage && $perPage < 50;
            @endphp

            @if($hasMore)
                <div x-intersect.margin.500px="$wire.loadMore()" class="w-full py-12 flex flex-col items-center gap-4">
                    <div class="flex flex-col items-center gap-3">
                        <div class="loading-more-spinner"></div>
                        <span class="text-[0.6rem] font-black text-[#8B7355]/60 uppercase tracking-widest animate-pulse">
                            Menyeduh artikel baru...
                        </span>
                    </div>
                </div>
            @else
                @if($informations->count() > 0)
                    <div class="scroll-sentinel py-10">
                        <div class="end-of-results bg-amber-50/50 border border-amber-100 flex flex-col gap-2 p-6 rounded-3xl">
                            <div class="flex items-center gap-2 justify-center">
                                <i class="ph-fill ph-check-circle text-amber-500 text-xl"></i>
                                <span class="inline-flex items-center gap-2 text-espresso font-black"><i class="ph-fill ph-check-circle text-amber-600" aria-hidden="true"></i>Kamu sudah mencapai batas akhir</span>
                            </div>
                        </div>
                    </div>
                @endif
            @endif
        </div>
    </div>
</div>
