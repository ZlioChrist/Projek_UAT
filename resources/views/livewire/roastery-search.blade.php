{{-- Livewire Roastery Search Component --}}
{{-- Premium Redesign 2026 - Ultra Modern & Responsive (Identical to Explore) --}}
<div x-data="roasteryLogic()" x-init="initComponent()" class="roastery-page block min-h-screen">
    {{-- Livewire data is handled in Alpine x-data script --}}

    @if(config('app.debug'))
        <div class="hidden" id="roastery-debug-count">{{ $roasteries->count() }}</div>
    @endif

    {{-- Ultra Premium Hero Section --}}
    <header class="explore-hero-2026" :class="{ 'is-compact': isScrolled }"
        @scroll.window="isScrolled = window.pageYOffset > 50">
        {{-- Top Bar Section --}}
        <div class="explore-branding-wrapper">
            <div class="explore-topbar">
                <div class="explore-logo-box">
                    <img src="{{ asset('wadahngopi.png') }}" alt="Logo">
                </div>
                <div class="flex flex-col">
                    <h1 class="explore-brand">Wadah<span>Ngopi</span></h1>
                    <p class="explore-tagline">CARI BIJI KOPI FAVORITMU</p>
                </div>
            </div>
        </div>

        {{-- Premium Search Box --}}
        <div class="explore-search-2026" @click.away="showSortMenu = false">
            <div class="search-icon-pulse">
                <i class="ph-bold ph-magnifying-glass" wire:loading.remove wire:target="search"></i>
                <i class="ph-bold ph-spinner animate-spin text-amber-500" wire:loading wire:target="search"></i>
            </div>
            <input type="text" id="roastery-search" name="search" wire:model.live.debounce.500ms="search" placeholder="Cari roastery atau beans..."
                class="explore-search-input">
            <button class="explore-sort-btn" @click="showSortMenu = !showSortMenu"
                :class="showSortMenu ? 'active' : ''">
                <i class="ph-bold ph-sliders-horizontal"></i>
            </button>

            {{-- Sort Dropdown Premium --}}
            <div class="explore-sort-dropdown" x-show="showSortMenu" x-cloak
                x-transition:enter="transition ease-out duration-300"
                x-transition:enter-start="opacity-0 translate-y-4 scale-95"
                x-transition:enter-end="opacity-100 translate-y-0 scale-100"
                x-transition:leave="transition ease-in duration-200"
                x-transition:leave-start="opacity-100 translate-y-0 scale-100"
                x-transition:leave-end="opacity-0 translate-y-4 scale-95">
                <div class="sort-dropdown-header">
                    <i class="ph-fill ph-funnel text-amber"></i>
                    <span>Urutkan</span>
                    <i class="ph-bold ph-spinner animate-spin ml-auto text-amber-500" wire:loading wire:target="setSort, setLetter, resetAllFilters"></i>
                </div>
                <div class="sort-dropdown-body custom-scrollbar relative">
                    <button class="sort-dropdown-item"
                        wire:click="setSort('name_az')"
                        :class="$wire.sort === 'name_az' && !$wire.activeLetter ? 'active' : ''"
                        @click="showSortMenu = false">
                        <i class="ph-fill ph-sort-ascending"></i>
                        <span> (A-Z)</span>
                    </button>
                    <button class="sort-dropdown-item"
                        wire:click="setSort('name_za')"
                        :class="$wire.sort === 'name_za' && !$wire.activeLetter ? 'active' : ''"
                        @click="showSortMenu = false">
                        <i class="ph-fill ph-sort-descending"></i>
                        <span> (Z-A)</span>
                    </button>

                    {{-- Unified Alphabet List items --}}
                     @foreach (range('A', 'Z') as $char)
                        <button class="sort-dropdown-item"
                            wire:click="setLetter('{{ $char }}')"
                            :class="$wire.activeLetter === '{{ $char }}' ? 'active' : ''"
                            @click="showSortMenu = false">
                            <i class="ph-bold ph-text-aa"></i>
                            <span> ({{ $char }})</span>
                        </button>
                    @endforeach

                    @if($activeLetter || $search || $filter !== 'semua' || $cityId || $sort !== 'relevance')
                        <div class="sticky bottom-0 left-0 right-0 p-2 bg-white/95 backdrop-blur-sm border-t border-[#F5EFED]">
                            <button wire:click="resetAllFilters" @click="showSortMenu = false"
                                wire:loading.attr="disabled"
                                class="w-full py-2 flex items-center justify-center gap-2 text-xs font-bold text-red-500 bg-red-50 hover:bg-red-100 rounded-lg transition-colors disabled:opacity-50">
                                <i class="ph-bold ph-x" wire:loading.remove wire:target="resetAllFilters"></i>
                                <i class="ph-bold ph-spinner animate-spin" wire:loading wire:target="resetAllFilters"></i>
                                Reset Filter
                            </button>
                        </div>
                    @endif
                </div>
            </div>
        </div>

        {{-- Category Filter Pills --}}
        <div class="filter-scroll-wrapper">
            <div class="explore-category-pills">
                <button class="category-pill disabled:opacity-50" :class="$wire.filter === 'semua' ? 'active' : ''"
                    wire:click="$set('filter', 'semua')" wire:loading.attr="disabled">
                    <i class="ph-fill ph-coffee-bean" wire:loading.remove wire:target="$set('filter', 'semua')"></i>
                    <i class="ph-bold ph-spinner animate-spin" wire:loading wire:target="$set('filter', 'semua')"></i>
                    <span>Semua</span>
                </button>
                <button class="category-pill" :class="$wire.filter === 'terdekat' ? 'active' : ''"
                    @click="getLocation()">
                    <i class="ph-fill ph-map-pin" x-show="!isLocating" wire:loading.remove wire:target="setUserLocation"></i>
                    <i class="ph ph-circle-notch animate-spin" x-show="isLocating" wire:loading wire:target="setUserLocation"></i>
                    <span>Terdekat</span>
                </button>
                <button class="category-pill pill-open disabled:opacity-50" :class="$wire.filter === 'buka' ? 'active' : ''"
                    wire:click="$set('filter', 'buka')" wire:loading.attr="disabled">
                    <span class="pulse-dot" wire:loading.remove wire:target="$set('filter', 'buka')"></span>
                    <i class="ph-bold ph-spinner animate-spin" wire:loading wire:target="$set('filter', 'buka')"></i>
                    <span>Sedang Buka</span>
                </button>
            </div>
        </div>

        {{-- City Filter Pills --}}
        <div class="filter-scroll-wrapper">
            <div class="explore-city-pills">
                <button wire:click="$set('cityId', '')" wire:loading.attr="disabled"
                    class="city-pill {{ $cityId === '' || $cityId === null ? 'active' : '' }} disabled:opacity-50">
                    <span wire:loading.remove wire:target="$set('cityId', '')">Semua Kota</span>
                    <span wire:loading wire:target="$set('cityId', '')"><i class="ph-bold ph-spinner animate-spin"></i></span>
                </button>
                @foreach($cities as $city)
                    <button wire:click="$set('cityId', '{{ $city['id'] }}')" wire:loading.attr="disabled"
                        class="city-pill {{ $cityId == $city['id'] ? 'active' : '' }} disabled:opacity-50">
                        <span wire:loading.remove wire:target="$set('cityId', '{{ $city['id'] }}')">{{ $city['name'] }}</span>
                        <span wire:loading wire:target="$set('cityId', '{{ $city['id'] }}')"><i class="ph-bold ph-spinner animate-spin"></i></span>
                    </button>
                @endforeach
            </div>
        </div>
    </header>

    {{-- Content Spacer for Fixed Header --}}
    <div class="header-spacer-2026"></div>

    {{-- Premium Skeleton Shimmer (Shown while loading) --}}
    <div class="explore-cafe-grid" wire:loading>
        @for($i = 1; $i <= 6; $i++)
            <x-skeleton.roastery-card />
        @endfor
    </div>

    {{-- Roastery Grid Premium --}}
    <main class="explore-cafe-grid" wire:loading.remove.delay.shorter>
        @forelse($roasteries as $roastery)
            <a href="{{ route('roastery.show', $roastery) }}" class="cafe-card-2026 group card-stagger" wire:key="roastery-{{ $roastery->id }}">
                    {{-- Fixed Height Image Container --}}
                    @php
                        $image = $roastery->image_path ? (str_starts_with($roastery->image_path, 'http') ? $roastery->image_path : Storage::url($roastery->image_path)) : 'https://images.unsplash.com/photo-1559056199-641a0ac8b55e?auto=format&fit=crop&q=80&w=800';
                    @endphp
                    <div class="cafe-card-image">
                        @php
                            $images = $roastery->processed_images;
                            $firstImage = count($images) > 0 ? $images[0] : 'https://images.unsplash.com/photo-1559056199-641a0ac8b55e?auto=format&fit=crop&q=80&w=800';
                        @endphp
                         <img src="{{ $firstImage }}"
                              alt="{{ $roastery->name }}" loading="lazy"
                              class="cafe-card-img object-cover">

                        {{-- Gradient Overlay --}}
                        <div class="cafe-card-overlay"></div>

                        {{-- Smart Status Badge --}}
                        @php
                            $isOpen = $roastery->is_open;
                            $todayHours = $roastery->today_hours;
                            $timeText = trim((string) ($roastery->official_status_is_fresh ? $roastery->official_status_text : ''));

                            if ($timeText === '') {
                                if ($roastery->is_24_hours && $isOpen) {
                                    $timeText = '24 Jam';
                                } elseif ($isOpen && isset($todayHours['close'])) {
                                    $timeText = 'Sampai ' . \Carbon\Carbon::parse($todayHours['close'])->format('H:i');
                                } elseif (! $isOpen && isset($todayHours['open'])) {
                                    $timeText = 'Buka ' . \Carbon\Carbon::parse($todayHours['open'])->format('H:i');
                                }
                            }
                        @endphp
                        <div class="cafe-status-badge-smart {{ $isOpen ? 'open' : 'closed' }}">
                            <div class="flex items-center gap-1">
                                <span class="status-indicator">
                                    <span class="status-ping"></span>
                                    <span class="status-dot"></span>
                                </span>
                                <span class="font-black tracking-wider uppercase">
                                    {{ $isOpen ? 'Buka' : 'Tutup' }}
                                </span>
                            </div>
                            @if($timeText)
                                <span class="text-[0.5rem] sm:text-[0.55rem] font-bold opacity-90 ml-1 border-l border-white/20 pl-1 leading-none whitespace-nowrap">
                                    {{ $timeText }}
                                </span>
                            @endif
                        </div>

                        {{-- Distance Badge --}}
                        @if(isset($roastery->distance))
                            <div class="cafe-distance-badge">
                                <i class="ph-fill ph-navigation-arrow text-amber-400"></i>
                                <span>{{ number_format($roastery->distance, 1) }} km</span>
                            </div>
                        @endif

                        {{-- Hover Quick Actions --}}
                        <div class="cafe-card-actions">
                            @php
                                $socials = collect($roastery->social_links ?? [])
                                    ->filter(fn($s) => filter_var($s['show'] ?? false, FILTER_VALIDATE_BOOLEAN) && !empty($s['url']));
                                $visibleSocials = $socials->take(3);
                                $moreSocialsCount = $socials->count() - 3;
                            @endphp

                            @foreach ($visibleSocials as $social)
                                <button onclick="event.preventDefault(); window.open('{{ $social['url'] }}', '_blank')" class="quick-action-btn" title="{{ ucfirst($social['platform']) }}">
                                    <i class="ph-bold ph-@if($social['platform'] === 'twitter')x-logo @else{{ $social['platform'] }}-logo @endif"></i>
                                </button>
                            @endforeach

                            @if($moreSocialsCount > 0)
                                <div class="quick-action-more">+{{ $moreSocialsCount }}</div>
                            @endif
                        </div>
                     </div>

                {{-- Card Content --}}
                <div class="cafe-card-content">
                    <h3 class="cafe-card-title">{{ $roastery->name }}</h3>
                    <p class="cafe-card-address">
                        <i class="ph-fill ph-map-pin"></i>
                        <span class="drop-shadow-sm">{{ $roastery->address ?: ($roastery->city?->name ?? 'Kalimantan') }}</span>
                    </p>

                    {{-- Spacer (No Facilities for Roastery) --}}
                    <div class="mt-auto"></div>
                </div>

                {{-- Hover Shine Effect --}}
                <div class="card-shine"></div>
            </a>
        @empty
            {{-- Premium Empty State with SVG --}}
            <div class="col-span-full empty-state-premium">
                <div class="empty-state-illustration">
                    <svg viewBox="0 0 200 200" fill="none" xmlns="http://www.w3.org/2000/svg">
                        <circle cx="100" cy="150" rx="50" ry="10" fill="#F5EFED"/>
                        <ellipse cx="100" cy="100" rx="35" ry="50" fill="#3E2723" stroke="#2C1810" stroke-width="2"/>
                        <ellipse cx="100" cy="55" rx="35" ry="12" fill="#5D4037" stroke="#2C1810" stroke-width="1"/>
                        <path d="M80 60 C85 70, 80 80, 85 90" stroke="#1A0F0A" stroke-width="1" opacity="0.2" fill="none"/>
                        <path d="M100 58 C105 70, 100 82, 105 95" stroke="#1A0F0A" stroke-width="1" opacity="0.15" fill="none"/>
                        <path d="M120 60 C115 72, 118 82, 115 92" stroke="#1A0F0A" stroke-width="1" opacity="0.2" fill="none"/>
                        <path d="M85 30 C85 20, 90 12, 88 5" stroke="#B08968" stroke-width="2" stroke-linecap="round" opacity="0.4">
                            <animate attributeName="d" values="M85 30 C85 20, 90 12, 88 5;M85 30 C83 18, 88 10, 92 2;M85 30 C85 20, 90 12, 88 5" dur="2.5s" repeatCount="indefinite"/>
                        </path>
                        <path d="M100 28 C100 16, 103 8, 100 0" stroke="#B08968" stroke-width="2" stroke-linecap="round" opacity="0.3">
                            <animate attributeName="d" values="M100 28 C100 16, 103 8, 100 0;M100 28 C98 14, 101 6, 104 -2;M100 28 C100 16, 103 8, 100 0" dur="3s" repeatCount="indefinite"/>
                        </path>
                        <path d="M115 30 C115 22, 112 15, 115 8" stroke="#B08968" stroke-width="2" stroke-linecap="round" opacity="0.3">
                            <animate attributeName="d" values="M115 30 C115 22, 112 15, 115 8;M115 30 C113 20, 110 12, 113 5;M115 30 C115 22, 112 15, 115 8" dur="2s" repeatCount="indefinite"/>
                        </path>
                    </svg>
                </div>
                @if($filter === 'terdekat')
                    <h3>{{ $hasGeolocatedRoasteries ? 'Roastery Terdekat Tidak Ditemukan' : 'Lokasi Roastery Belum Tersedia' }}</h3>
                    <p>
                        {{ $hasGeolocatedRoasteries
                            ? 'Belum ada roastery berkoordinat dalam radius 20 km dari lokasi Anda.'
                            : 'Belum ada roastery dengan koordinat. Admin perlu mengisi latitude dan longitude roastery agar jarak dapat dihitung.' }}
                    </p>
                @else
                    <h3>Roastery Tidak Ditemukan</h3>
                    <p>Hmm, belum ada roastery yang cocok. Coba ubah filter atau kata kunci pencarianmu.</p>
                @endif
                <button class="empty-state-cta" wire:click="resetAllFilters">
                    <i class="ph-bold ph-arrow-counter-clockwise"></i>
                    Reset Pencarian
                </button>
            </div>
        @endforelse
    </main>

    {{-- Load More Section --}}
    @if($roasteries->hasMorePages())
        <div class="w-full py-8 flex flex-col items-center gap-4" wire:key="load-more-{{ $perPage }}">
            <div wire:loading wire:target="loadMore" class="flex flex-col items-center gap-3">
                <div class="loading-more-spinner"></div>
                <span class="text-[0.6rem] font-black text-[#8B7355]/60 uppercase tracking-widest animate-pulse">Menyiapkan Roastery Lainnya...</span>
            </div>
            <button wire:click="loadMore" wire:loading.remove wire:target="loadMore"
                class="px-6 py-2.5 bg-espresso/10 hover:bg-espresso/20 text-espresso font-bold text-xs rounded-full transition-all active:scale-95 flex items-center gap-2"
                aria-label="Muat lebih banyak roastery">
                <i class="ph-bold ph-arrow-down"></i>
                Muat Lagi ({{ $roasteries->total() - $perPage > 0 ? number_format($roasteries->total() - $perPage) : 0 }} tersisa)
            </button>
        </div>
    @else
        @if($roasteries->count() > 0)
            <div class="scroll-sentinel py-16">
                <div class="end-of-results bg-amber-50/50 border border-amber-100 flex flex-col gap-2 p-6 rounded-3xl">
                    <div class="flex items-center gap-2 justify-center">
                        <i class="ph-fill ph-check-circle text-amber-500 text-xl"></i>
                        <span class="text-espresso font-black">Selesai Menjelajah</span>
                    </div>
                    <p class="text-[0.7rem] text-center text-[#8B7355] font-medium max-w-[200px]">
                        Semua {{ number_format($roasteries->total()) }} roastery sudah ditampilkan.
                    </p>
                </div>
            </div>
        @endif
    @endif

    {{-- Styles moved to resources/css/app.css --}}
</div>

@script
<script>
    Alpine.data('roasteryLogic', () => ({
        isScrolled: false,
        showSortMenu: false,
        isLocating: false,

        initComponent() {
            // Keep header scroll effect
            this.isScrolled = window.pageYOffset > 50;

            // Auto-request GPS if page loaded/refreshed with filter=terdekat but coordinates lost
            if (this.$wire.filter === 'terdekat' && (this.$wire.userLat === null || this.$wire.userLng === null)) {
                this.getLocation();
            }
        },

        getLocation() {
            if (!navigator.geolocation) {
                this.$dispatch('toast', { message: 'Browser tidak mendukung Geolocation', type: 'error' });
                return;
            }
            this.isLocating = true;
            navigator.geolocation.getCurrentPosition(
                (pos) => {
                    // Send to backend
                    this.$wire.setUserLocation(pos.coords.latitude, pos.coords.longitude);
                    this.isLocating = false;
                },
                (err) => {
                    console.error('Geolocation Error:', err);
                    this.isLocating = false;

                    if (err.code === 1) { // PERMISSION_DENIED
                        if (!window.isSecureContext) {
                            this.$dispatch('toast', { message: 'Fitur Terdekat butuh HTTPS/Secure connection', type: 'error' });
                        } else {
                            this.$dispatch('toast', { message: 'Izin lokasi ditolak. Cek pengaturan browser.', type: 'error' });
                        }
                    } else if (err.code === 2) { // POSITION_UNAVAILABLE
                        this.$dispatch('toast', { message: 'Lokasi tidak ditemukan. Periksa layanan lokasi perangkat.', type: 'error' });
                    } else if (err.code === 3) { // TIMEOUT
                        this.$dispatch('toast', { message: 'Permintaan lokasi terlalu lama. Coba lagi.', type: 'error' });
                    } else {
                        this.$dispatch('toast', { message: 'Gagal mendapatkan lokasi.', type: 'error' });
                    }
                },
                { enableHighAccuracy: false, timeout: 10000, maximumAge: 60000 }
            );
        }
    }));
</script>
@endscript
