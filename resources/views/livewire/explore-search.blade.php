{{-- Livewire Explore Search Component --}}
{{-- Premium Redesign 2026 - Ultra Modern & Responsive --}}
<div x-data="exploreLogic()" x-init="initComponent()" class="explore-page block min-h-screen">
    {{-- Livewire data is handled in Alpine x-data script --}}

    @if(config('app.debug'))
        <div class="hidden" id="cafe-debug-count">{{ $cafes->count() }}</div>
    @endif

    {{-- Ultra Premium Hero Section --}}
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
                    <h1 class="explore-brand font-heading tracking-premium">Wadah<span class="text-shimmer">Ngopi</span></h1>
                    <p class="explore-tagline text-premium-label">Jelajahi Kopi Favoritmu</p>
                </div>

            </div>
        </div>

        {{-- Premium Search Box --}}
        <div class="explore-search-2026" x-data="{ hasSearch: @js((bool) $search) }" @click.away="showSortMenu = false">
            <div class="search-icon-pulse">
                <i class="ph-bold ph-magnifying-glass" wire:loading.remove wire:target="search, filter, cityId, sort, activeLetter"></i>
                <i class="ph-bold ph-spinner animate-spin text-amber-500" wire:loading wire:target="search, filter, cityId, sort, activeLetter"></i>
            </div>
            <input type="text" id="cafe-search" name="search" wire:model.live.debounce.150ms="search" placeholder="Cari cafe favoritmu..."
                class="explore-search-input" autocomplete="off" @input="hasSearch = $event.target.value.length > 0">
            <button type="button" class="explore-clear-search-btn" x-show="hasSearch" x-cloak
                wire:click="clearSearch" @click="hasSearch = false" aria-label="Hapus pencarian" title="Hapus pencarian">
                <i class="ph-bold ph-x"></i>
            </button>
            <button class="explore-sort-btn disabled:opacity-50" @click="showSortMenu = !showSortMenu"
                :class="showSortMenu ? 'active' : ''" wire:loading.attr="disabled" wire:target="search, filter, cityId, sort, activeLetter">
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
            <div class="explore-category-pills" role="group" aria-label="Filter kedai">
                <button type="button" class="category-pill disabled:opacity-50" :class="$wire.filter === 'semua' ? 'active' : ''"
                    :aria-pressed="$wire.filter === 'semua'"
                    wire:click="$set('filter', 'semua')" wire:loading.attr="disabled">
                    <i class="ph-fill ph-coffee" wire:loading.remove wire:target="$set('filter', 'semua')"></i>
                    <i class="ph-bold ph-spinner animate-spin" wire:loading wire:target="$set('filter', 'semua')"></i>
                    <span>Semua</span>
                </button>
                <button type="button" class="category-pill disabled:opacity-70 disabled:cursor-not-allowed"
                    :class="$wire.filter === 'terdekat' ? 'active' : ''"
                    :aria-pressed="$wire.filter === 'terdekat'"
                    @click="getLocation()"
                    wire:loading.attr="disabled"
                    wire:target="setUserLocation, filter">
                    <i class="ph-fill ph-map-pin" x-show="!isLocating" wire:loading.remove wire:target="setUserLocation, filter"></i>
                    <i class="ph ph-circle-notch animate-spin text-amber-500" x-show="isLocating" wire:loading wire:target="setUserLocation, filter"></i>
                    <span>Terdekat</span>
                </button>
                <button type="button" class="category-pill pill-open disabled:opacity-70 disabled:cursor-not-allowed"
                    :class="$wire.filter === 'buka' ? 'active' : ''"
                    :aria-pressed="$wire.filter === 'buka'"
                    wire:click="$set('filter', 'buka')" 
                    wire:loading.attr="disabled"
                    wire:target="filter">
                    <span class="pulse-dot" wire:loading.remove wire:target="filter"></span>
                    <i class="ph-bold ph-spinner animate-spin text-emerald-500" wire:loading wire:target="filter"></i>
                    <span>Sedang Buka</span>
                </button>
            </div>
        </div>

        {{-- City Filter Pills --}}
        <div class="filter-scroll-wrapper">
            <div class="explore-city-pills" role="group" aria-label="Filter kota">
                <button type="button" wire:click="$set('cityId', '')" wire:loading.attr="disabled"
                    aria-pressed="{{ $cityId === '' || $cityId === null ? 'true' : 'false' }}"
                    class="city-pill {{ $cityId === '' || $cityId === null ? 'active' : '' }} disabled:opacity-50">
                    <span wire:loading.remove wire:target="$set('cityId', '')">Semua Kota</span>
                    <span wire:loading wire:target="$set('cityId', '')"><i class="ph-bold ph-spinner animate-spin"></i></span>
                </button>
                @foreach($cities as $city)
                    <button type="button" wire:key="explore-city-{{ $city['id'] }}"
                        wire:click="$set('cityId', '{{ $city['id'] }}')" wire:loading.attr="disabled"
                        aria-pressed="{{ (string) $cityId === (string) $city['id'] ? 'true' : 'false' }}"
                        class="city-pill {{ (string)$cityId === (string)$city['id'] ? 'active' : '' }} disabled:opacity-50">
                        <span wire:loading.remove wire:target="$set('cityId', '{{ $city['id'] }}')">{{ $city['name'] }}</span>
                        <span wire:loading wire:target="$set('cityId', '{{ $city['id'] }}')"><i class="ph-bold ph-spinner animate-spin"></i></span>
                    </button>
                @endforeach

            </div>
        </div>

        {{-- Sengaja Gw Hide Dulu Jangan Di Rubah Jangan Di Aktifkan Kalau Gw Gak nyuruh --}}
        <!-- <div class="explore-result-counter-wrapper">
            <div class="explore-result-counter">
                <span class="counter-number">{{ $cafes->total() }}</span>
                <span class="counter-text">cafe ditemukan</span>
            </div>
            <div wire:loading.delay.shorter class="explore-loading-state">
                <i class="ph ph-circle-notch animate-spin"></i>
                <span>Mencari...</span>
            </div>
        </div> -->
    </header>

    {{-- Content Spacer for Fixed Header (Static height to prevent jumping) --}}
    <div class="header-spacer-2026"></div>

    {{-- Premium Skeleton Shimmer (Shown while loading) --}}
    <div class="explore-cafe-grid" wire:loading>
        @for($i = 1; $i <= 6; $i++)
            <x-skeleton.cafe-card />
        @endfor
    </div>

    @if($filter === 'terdekat' && $userLat !== null && $userLng !== null)
        @php
            $formattedCoordinates = number_format($userLat, 6, '.', '').', '.number_format($userLng, 6, '.', '');
            $gpsMapsUrl = 'https://www.google.com/maps/search/?api=1&query='.rawurlencode(str_replace(', ', ',', $formattedCoordinates));
            $nearestLocationMapsUrl = $nearestLocationPointIsClosest
                ? ($nearestLocationPoint->google_maps_url ?: 'https://www.google.com/maps/search/?api=1&query='.rawurlencode(
                    number_format($nearestLocationPoint->latitude, 6, '.', '').','.number_format($nearestLocationPoint->longitude, 6, '.', '')
                ))
                : null;
        @endphp
        <section class="mx-3 mt-4 rounded-2xl border border-amber-200 bg-amber-50/90 p-4 text-espresso sm:mx-4"
            role="status" aria-live="polite">
            <div class="flex items-start gap-3">
                <i class="ph-fill ph-map-pin mt-0.5 text-xl text-amber-600" aria-hidden="true"></i>
                <div class="min-w-0">
                    <p class="font-bold">Titik lokasi perangkat Anda</p>
                    <p class="mt-1 break-words text-sm text-espresso/80">
                        Koordinat: {{ $formattedCoordinates }}
                        @if($userAccuracy !== null)
                            <span class="font-semibold">(perkiraan akurasi &plusmn;{{ number_format($userAccuracy, 0) }} m)</span>
                        @else
                            <span class="font-semibold">(akurasi lokasi tidak tersedia)</span>
                        @endif
                    </p>
                    <a class="mt-1 inline-flex text-sm font-semibold text-amber-800 underline decoration-amber-400 underline-offset-2"
                        href="{{ $gpsMapsUrl }}" target="_blank" rel="noopener noreferrer">
                        Buka titik GPS di Google Maps
                    </a>

                    @if($userAccuracy === null)
                        <p class="mt-2 text-sm text-espresso/70">Akurasi lokasi tidak diterima, jadi aplikasi belum bisa memastikan Anda berada di titik kafe tertentu.</p>
                    @elseif($userAccuracy > 25)
                        <p class="mt-2 text-sm text-espresso/70">
                            Lokasi perangkat belum cukup presisi untuk memastikan kafe tempat Anda berada.
                            @if($nearestPlace)
                                Titik terdaftar terdekat:
                                @if($nearestLocationPointIsClosest)
                                    <a class="font-semibold text-amber-800 underline decoration-amber-400 underline-offset-2"
                                        href="{{ $nearestLocationMapsUrl }}" target="_blank" rel="noopener noreferrer">{{ $nearestPlace->name }}</a>
                                @else
                                    <a class="font-semibold text-amber-800 underline decoration-amber-400 underline-offset-2"
                                        href="{{ route('cafes.show', $nearestCafe) }}">{{ $nearestPlace->name }}</a>
                                @endif
                                (sekitar {{ $nearestPlaceDistance < 1
                                    ? round($nearestPlaceDistance * 1000).' m'
                                    : number_format($nearestPlaceDistance, 1).' km' }} dari koordinat perangkat).
                            @else
                                Belum ada titik terdaftar dalam radius 20 km.
                            @endif
                            Jarak tersebut hanya perkiraan karena akurasi perangkat &plusmn;{{ number_format($userAccuracy, 0) }} m.
                            Coba aktifkan lokasi presisi/GPS lalu ambil lokasi kembali.
                        </p>
                    @elseif($isAtCafe && $nearestPlace)
                        <p class="mt-2 font-semibold">Lokasi Anda terdeteksi di sekitar
                            @if($nearestLocationPointIsClosest)
                                <a class="text-amber-800 underline decoration-amber-400 underline-offset-2"
                                    href="{{ $nearestLocationMapsUrl }}" target="_blank" rel="noopener noreferrer">{{ $nearestPlace->name }}</a>.
                            @else
                                <a class="text-amber-800 underline decoration-amber-400 underline-offset-2"
                                    href="{{ route('cafes.show', $nearestCafe) }}">{{ $nearestPlace->name }}</a>.
                            @endif
                            berdasarkan titik GPS perangkat.
                        </p>
                    @elseif($nearestPlace)
                        <p class="mt-2 text-sm text-espresso/70">Titik kafe terdekat dari lokasi Anda adalah
                            @if($nearestLocationPointIsClosest)
                                <a class="font-semibold text-amber-800 underline decoration-amber-400 underline-offset-2"
                                    href="{{ $nearestLocationMapsUrl }}" target="_blank" rel="noopener noreferrer">{{ $nearestPlace->name }}</a>
                            @else
                                <a class="font-semibold text-amber-800 underline decoration-amber-400 underline-offset-2"
                                    href="{{ route('cafes.show', $nearestCafe) }}">{{ $nearestPlace->name }}</a>
                            @endif
                            ({{ $nearestPlaceDistance < 1
                                ? round($nearestPlaceDistance * 1000).' m'
                                : number_format($nearestPlaceDistance, 1).' km' }}). Ini bukan kepastian bahwa Anda sedang berada di sana.
                        </p>
                    @else
                        <p class="mt-2 text-sm text-espresso/70">Belum ada titik kafe berkoordinat dalam radius 20 km dari lokasi GPS ini.</p>
                    @endif
                    <p class="mt-2 text-sm text-espresso/70">
                        @if($nearestLocationPointIsClosest)
                            Titik terdekat ditampilkan pertama; kafe berikutnya diurutkan berdasarkan jarak dari lokasi perangkat.
                        @else
                            Kafe di bawah diurutkan berdasarkan jarak dari lokasi perangkat.
                        @endif
                    </p>
                </div>
            </div>
        </section>
    @endif

    {{-- Cafe Grid Premium - Hidden while loading more to prevent jump --}}
    <main class="explore-cafe-grid" 
        wire:loading.class="opacity-50 pointer-events-none" 
        wire:target="loadMore, filter, cityId, search, activeLetter, sort, setSort, setLetter">
        @if($filter === 'terdekat' && $nearestLocationPointIsClosest && $nearestLocationPoint)
            <a data-location-point="{{ $nearestLocationPoint->slug }}"
                href="{{ $nearestLocationMapsUrl }}" target="_blank" rel="noopener noreferrer"
                class="group flex min-h-48 flex-col justify-between rounded-3xl border-2 border-amber-300 bg-amber-50 p-5 text-espresso shadow-sm transition hover:-translate-y-1 hover:shadow-md"
                aria-label="Buka arah ke {{ $nearestLocationPoint->name }}">
                <div class="flex items-start justify-between gap-3">
                    <span class="inline-flex items-center gap-1 rounded-full bg-amber-200 px-3 py-1 text-xs font-bold text-amber-950">
                        <i class="ph-fill ph-map-pin" aria-hidden="true"></i>
                        Pin lokasi terdaftar
                    </span>
                    <i class="ph-bold ph-arrow-up-right text-amber-700" aria-hidden="true"></i>
                </div>
                <div class="mt-5">
                    <h2 class="font-heading text-xl font-black">{{ $nearestLocationPoint->name }}</h2>
                    @if($nearestLocationPoint->address)
                        <p class="mt-1 text-sm font-medium leading-5 text-espresso/75">
                            <i class="ph-fill ph-map-pin text-amber-600" aria-hidden="true"></i>
                            {{ $nearestLocationPoint->address }}
                        </p>
                    @endif
                    <p class="mt-1 text-xs font-semibold text-espresso/65">
                        Koordinat pin: {{ number_format($nearestLocationPoint->latitude, 7, '.', '') }}, {{ number_format($nearestLocationPoint->longitude, 7, '.', '') }}
                    </p>
                    <p class="mt-1 text-sm font-semibold text-espresso/75">
                        Estimasi dari perangkat:
                        {{ $nearestPlaceDistance < 1
                            ? round($nearestPlaceDistance * 1000).' m'
                            : number_format($nearestPlaceDistance, 1).' km' }}
                    </p>
                    <p class="mt-2 text-xs text-espresso/65">
                        Pin usaha memakai koordinat terdaftar. Akurasi lokasi perangkat &plusmn;{{ $userAccuracy === null ? 'tidak tersedia' : number_format($userAccuracy, 0).' m' }}.
                    </p>
                </div>
            </a>
        @endif

        @forelse($cafes as $cafe)
            <a href="{{ route('cafes.show', $cafe) }}" 
               class="cafe-card-2026 group card-stagger" 
               wire:key="cafe-{{ $cafe->id }}">
                    {{-- Fixed Height Image Container --}}
                    @php
                        $image = $cafe->image_path ? (str_starts_with($cafe->image_path, 'http') ? $cafe->image_path : Storage::url($cafe->image_path)) : 'https://images.unsplash.com/photo-1501339847302-ac426a4a7cbb?auto=format&fit=crop&q=80&w=800';
                    @endphp
                    <div class="cafe-card-image">
                        @php
                            $images = $cafe->processed_images;
                            $firstImage = count($images) > 0 ? $images[0] : 'https://images.unsplash.com/photo-1501339847302-ac426a4a7cbb?auto=format&fit=crop&q=80&w=800';
                        @endphp
                         <img src="{{ $firstImage }}"
                              alt="{{ $cafe->name }}" loading="lazy" 
                              class="cafe-card-img object-cover">
                              
                        {{-- Gradient Overlay --}}
                        <div class="cafe-card-overlay"></div>

                        {{-- Smart Status Badge --}}
                            @php
                                $isOpen = $cafe->is_open;
                                $todayHours = $cafe->today_hours;
                                $timeText = trim((string) ($cafe->official_status_is_fresh ? $cafe->official_status_text : ''));

                                if ($timeText === '') {
                                    if ($cafe->is_24_hours && $isOpen) {
                                        $timeText = '24 Jam';
                                    } elseif ($isOpen && isset($todayHours['close'])) {
                                        $timeText = 'Sampai ' . \Carbon\Carbon::parse($todayHours['close'])->format('H:i');
                                    } elseif (! $isOpen && isset($todayHours['open'])) {
                                        $timeText = 'Buka ' . \Carbon\Carbon::parse($todayHours['open'])->format('H:i');
                                    } elseif (! $isOpen && ! $cafe->is_24_hours && isset($todayHours['close'])) {
                                        $timeText = 'Tutup pukul ' . \Carbon\Carbon::parse($todayHours['close'])->format('H:i');
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
                        @if(isset($cafe->distance))
                            <div class="cafe-distance-badge">
                                <i class="ph-fill ph-navigation-arrow text-amber-400"></i>
                                <span>{{ $cafe->distance < 1
                                    ? round($cafe->distance * 1000).' m'
                                    : number_format($cafe->distance, 1).' km' }}</span>
                            </div>
                        @endif

                        {{-- Hover Quick Actions --}}
                        <div class="cafe-card-actions">
                            @php
                                $socials = collect($cafe->social_links ?? [])
                                    ->filter(fn($s) => filter_var($s['show'] ?? false, FILTER_VALIDATE_BOOLEAN) && !empty($s['url']));
                                $visibleSocials = $socials->take(3);
                                $moreSocialsCount = $socials->count() - 3;
                            @endphp
                            
                            @foreach ($visibleSocials as $social)
                                <button onclick="event.stopPropagation(); window.open('{{ $social['url'] }}', '_blank')" class="quick-action-btn" title="{{ ucfirst($social['platform']) }}">
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
                    <h3 class="cafe-card-title font-heading tracking-premium">{{ $cafe->name }}</h3>
                    @if($filter === 'terdekat' && $loop->first)
                        <span class="mb-2 inline-flex w-fit items-center gap-1 rounded-full bg-amber-100 px-2.5 py-1 text-[0.65rem] font-bold text-amber-900">
                            <i class="ph-fill {{ $isAtCafe && ! $nearestLocationPointIsClosest ? 'ph-map-pin' : 'ph-navigation-arrow' }}" aria-hidden="true"></i>
                            @if($isAtCafe && ! $nearestLocationPointIsClosest)
                                Lokasi Anda
                            @elseif($nearestLocationPointIsClosest)
                                Kafe berikutnya
                            @else
                                Kafe terdekat
                            @endif
                        </span>
                    @endif
                    <p class="cafe-card-address font-medium">
                        <i class="ph-fill ph-map-pin text-amber-500"></i>
                        <span class="drop-shadow-sm">{{ $cafe->address }}</span>    
                    </p>
                    
                    @if($cafe->facilities->isNotEmpty())
                        <div class="cafe-card-tags">
                            @foreach($cafe->facilities->take(3) as $facility)
                                <span class="cafe-tag text-[0.6rem] font-black uppercase tracking-widest bg-cream-dark/50">
                                    {{ Str::limit($facility->name, 12) }}
                                </span>
                            @endforeach
                            @if($cafe->facilities->count() > 3)
                                <span class="cafe-tag-more">+{{ $cafe->facilities->count() - 3 }}</span>
                            @endif
                        </div>
                    @else 
                        <div class="mt-auto"></div> 
                    @endif
                </div>


                {{-- Hover Shine Effect --}}
                <div class="card-shine"></div>
            </a>
        @empty
            {{-- Premium Empty State with SVG --}}
            <div class="col-span-full empty-state-premium explore-empty-state" role="status" aria-live="polite">
                <div class="empty-state-illustration explore-empty-state-illustration" aria-hidden="true">
                    <svg viewBox="0 0 200 200" fill="none" xmlns="http://www.w3.org/2000/svg">
                        <circle cx="100" cy="130" rx="60" ry="12" fill="#F5EFED"/>
                        <path d="M60 70 C60 50, 80 35, 100 35 C120 35, 140 50, 140 70 L140 110 C140 120, 130 130, 120 130 L80 130 C70 130, 60 120, 60 110 Z" fill="#2C1810" stroke="#1A0F0A" stroke-width="2"/>
                        <path d="M140 80 C155 80, 165 90, 165 100 C165 110, 155 120, 140 115" stroke="#2C1810" stroke-width="4" fill="none" stroke-linecap="round"/>
                        <ellipse cx="100" cy="60" rx="25" ry="6" fill="#3E2723" opacity="0.3"/>
                        <path d="M85 30 C85 20, 90 15, 90 10" stroke="#B08968" stroke-width="2" stroke-linecap="round" opacity="0.5">
                            <animate attributeName="d" values="M85 30 C85 20, 90 15, 90 10;M85 30 C83 18, 88 12, 92 5;M85 30 C85 20, 90 15, 90 10" dur="2.5s" repeatCount="indefinite"/>
                        </path>
                        <path d="M100 28 C100 16, 105 10, 105 2" stroke="#B08968" stroke-width="2" stroke-linecap="round" opacity="0.4">
                            <animate attributeName="d" values="M100 28 C100 16, 105 10, 105 2;M100 28 C98 14, 103 8, 107 0;M100 28 C100 16, 105 10, 105 2" dur="3s" repeatCount="indefinite"/>
                        </path>
                        <path d="M115 30 C115 22, 118 18, 118 12" stroke="#B08968" stroke-width="2" stroke-linecap="round" opacity="0.3">
                            <animate attributeName="d" values="M115 30 C115 22, 118 18, 118 12;M115 30 C113 20, 116 15, 120 8;M115 30 C115 22, 118 18, 118 12" dur="2s" repeatCount="indefinite"/>
                        </path>
                    </svg>
                </div>
                @if($filter === 'terdekat')
                    <span class="explore-empty-state-eyebrow">Pencarian Lokasi</span>
                    <h3 class="explore-empty-state-title">{{ $hasGeolocatedCafes ? 'Belum Ada Cafe di Sekitar' : 'Lokasi Cafe Belum Tersedia' }}</h3>
                    <p class="explore-empty-state-description">
                        {{ $hasGeolocatedCafes
                            ? 'Belum ada cafe berkoordinat dalam radius 20 km dari lokasi Anda.'
                            : 'Belum ada cafe dengan koordinat. Admin perlu mengisi latitude dan longitude cafe agar jarak dapat dihitung.' }}
                    </p>
                @else
                    <span class="explore-empty-state-eyebrow">Jelajahi Cafe</span>
                    <h3 class="explore-empty-state-title">{{ $search ? 'Cafe Belum Ditemukan' : 'Belum Ada Cafe yang Cocok' }}</h3>
                    @if($search)
                        <div class="explore-empty-state-query">
                            <i class="ph-bold ph-magnifying-glass" aria-hidden="true"></i>
                            <span>{{ $search }}</span>
                        </div>
                    @endif
                    <p class="explore-empty-state-description">Coba periksa ejaan atau gunakan kata kunci lain. Kamu juga bisa mengubah filter pencarian.</p>
                @endif
                <button class="empty-state-cta" wire:click="resetAllFilters">
                    <i class="ph-bold ph-arrow-counter-clockwise"></i>
                    Atur Ulang Pencarian
                </button>
            </div>
        @endforelse
    </main>

    {{-- Load More Section --}}
    @if($cafes->hasMorePages())
        <div class="w-full py-8 flex flex-col items-center gap-4" wire:key="load-more-{{ $perPage }}">

            {{-- Loading State --}}
            <div wire:loading wire:target="loadMore" class="flex flex-col items-center gap-3">
                <div class="loading-more-spinner"></div>
                <span class="text-[0.6rem] font-black text-[#8B7355]/60 uppercase tracking-widest animate-pulse">Menyiapkan Kafe Lainnya...</span>
            </div>

            {{-- Manual Load More Button --}}
            <button wire:click="loadMore" wire:loading.remove wire:target="loadMore"
                class="px-6 py-2.5 bg-espresso/10 hover:bg-espresso/20 text-espresso font-bold text-xs rounded-full transition-all active:scale-95 flex items-center gap-2">
                <i class="ph-bold ph-arrow-down"></i>
                Muat Lagi ({{ $cafes->total() - $perPage > 0 ? number_format($cafes->total() - $perPage) : 0 }} tersisa)
            </button>
        </div>
    @else
        <div class="scroll-sentinel py-16">
            <div class="end-of-results bg-amber-50/50 border border-amber-100 flex flex-col gap-2 p-6 rounded-3xl">
                <div class="flex items-center gap-2 justify-center">
                    <i class="ph-fill ph-check-circle text-amber-500 text-xl"></i>
                    <span class="text-espresso font-black">Selesai Menjelajah</span>
                </div>
                <p class="text-[0.7rem] text-center text-[#8B7355] font-medium max-w-[200px]">
                    Semua {{ number_format($cafes->total()) }} cafe sudah ditampilkan.
                </p>
                <button wire:click="resetAllFilters" class="mt-2 text-xs font-bold text-amber-600 hover:text-amber-700">
                    Kembali ke Atas ↑
                </button>
            </div>
        </div>
    @endif

    {{-- Styles moved to resources/css/app.css --}}

    {{-- Cafe Roulette — "Bingung? Putar Aja!" --}}
    <livewire:cafe-roulette />
</div>

@script
<script>
    Alpine.data('exploreLogic', () => ({
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

            if (this.isLocating) {
                return;
            }

            this.isLocating = true;
            let watchId = null;
            let timeoutId = null;
            let bestPosition = null;
            let finished = false;

            const stopWatching = () => {
                if (watchId !== null) {
                    navigator.geolocation.clearWatch(watchId);
                }
                if (timeoutId !== null) {
                    window.clearTimeout(timeoutId);
                }
            };

            const usePosition = (position) => {
                if (finished) return;
                finished = true;
                stopWatching();
                this.$wire.setUserLocation(
                    position.coords.latitude,
                    position.coords.longitude,
                    position.coords.accuracy
                );
                this.isLocating = false;
            };

            const showLocationError = (error) => {
                if (finished) return;
                if (bestPosition) {
                    usePosition(bestPosition);
                    return;
                }

                finished = true;
                stopWatching();
                console.error('Geolocation Error:', error);
                this.isLocating = false;

                if (error.code === 1) {
                    this.$dispatch('toast', {
                        message: !window.isSecureContext
                            ? 'Fitur Terdekat butuh HTTPS/Secure connection'
                            : 'Izin lokasi ditolak. Cek pengaturan browser.',
                        type: 'error'
                    });
                } else if (error.code === 2) {
                    this.$dispatch('toast', { message: 'Lokasi tidak ditemukan. Periksa layanan lokasi perangkat.', type: 'error' });
                } else if (error.code === 3) {
                    this.$dispatch('toast', { message: 'Lokasi tidak ditemukan dalam 15 detik. Coba lagi.', type: 'error' });
                } else {
                    this.$dispatch('toast', { message: 'Gagal mendapatkan lokasi.', type: 'error' });
                }
            };

            watchId = navigator.geolocation.watchPosition(
                (position) => {
                    if (!bestPosition || position.coords.accuracy < bestPosition.coords.accuracy) {
                        bestPosition = position;
                    }

                    if (position.coords.accuracy <= 5) {
                        usePosition(position);
                    }
                },
                showLocationError,
                { enableHighAccuracy: true, timeout: 20000, maximumAge: 0 }
            );

            timeoutId = window.setTimeout(() => {
                if (bestPosition) {
                    usePosition(bestPosition);
                } else {
                    showLocationError({ code: 3 });
                }
            }, 15000);
        }
    }));
</script>
@endscript