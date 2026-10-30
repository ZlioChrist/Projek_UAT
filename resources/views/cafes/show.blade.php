@extends('layouts.app')

@section('title', $cafe->name . ' - WadahNgopi.Com')
@section('meta_description', Str::limit(strip_tags($cafe->description), 155) ?: 'Temukan ' . $cafe->name . ' di ' . ($cafe->address ?? 'Kalimantan') . '. Cafe dengan fasilitas lengkap.')
@section('og_title', $cafe->name . ' - WadahNgopi')
@section('og_description', Str::limit(strip_tags($cafe->description), 100) ?: 'Cafe nyaman di ' . ($cafe->address ?? 'Kalimantan'))
@section('og_image', $cafe->image_path ? Storage::url($cafe->image_path) : asset('wadahngopi.png'))

@section('content')
    @php
        $galleryImages = $cafe->processed_images;

        // PRECOMPUTE CATEGORIES
        // menu_images may hold plain path strings (seeder/scraper) or objects {image, tag, is_active}
        $menuRaw = $cafe->menu_images ?? [];
        $activeGalleryImages = collect($menuRaw)->map(function ($img) {
            if (is_string($img)) {
                return ['image' => $img, 'tag' => null, 'is_active' => true];
            }
            return $img;
        })->filter(fn($img) => is_array($img) && ($img['is_active'] ?? true) === true);
        $allCats = $activeGalleryImages->pluck('tag')->unique()->filter()->values()->all();
        $defaultTab = !empty($allCats) ? $allCats[0] : '';
    @endphp

    <style>
        /* MODERN CLEAN DESIGN SYSTEM */
        .menu-section-v4 {
            margin-bottom: 5rem;
            padding-top: 2rem;
        }

        .section-header-v5 {
            margin-bottom: 2.5rem;
            position: relative;
        }

        .section-title-v5 {
            font-size: 2.25rem;
            font-weight: 900;
            color: #2C1810;
            letter-spacing: -0.02em;
            line-height: 1.1;
        }

        /* Utils */
        .scrollbar-hide::-webkit-scrollbar {
            display: none;
        }

        .scrollbar-hide {
            -ms-overflow-style: none;
            scrollbar-width: none;
        }
    </style>

    <div class="detail-wrapper"
        x-data="cafeDetailComponent({
                                                                                                                                                id: {{ $cafe->id }},
                                                                                                                                                images: {{ json_encode($galleryImages) }},
                                                                                                                                                allCategories: {{ json_encode($allCats) }},
                                                                                                                                                defaultTab: {{ json_encode($defaultTab) }},
                                                                                                                                                menuImages: {{ json_encode($activeGalleryImages->map(fn($img) => ['url' => str_starts_with($img['image'], 'http') ? $img['image'] : '/storage/' . $img['image'], 'tag' => $img['tag']])->values()) }}
                                                                                                                                            })">

        {{-- Hero Skeleton (Shown while image loading) --}}
        <div x-show="!imagesLoaded" class="absolute inset-0 z-50 bg-white">
            <x-skeleton.detail-header />
        </div>

        {{-- Hero Slider Section --}}
        <div class="detail-hero-luxury relative w-full h-[55vh] min-h-[450px] overflow-hidden bg-slate-900"
            @touchstart="touchStart($event)" @touchend="touchEnd($event)" @click="openLightbox('hero', currentSlide)">

            {{-- Nav Overlay --}}
            <nav class="detail-nav-overlay group" @click.stop>
                <a href="javascript:history.back()"
                    class="w-11 h-11 rounded-full bg-black/20 backdrop-blur-xl border border-white/10 flex items-center justify-center text-white shadow-2xl transition-all active:scale-90 no-underline hover:bg-black/40 shrink-0">
                    <i class="ph ph-arrow-left text-xl"></i>
                </a>

                <div class="flex items-center gap-2.5">
                    <button
                        class="w-11 h-11 rounded-full bg-black/20 backdrop-blur-xl border border-white/10 flex items-center justify-center text-white shadow-2xl transition-all active:scale-90 hover:bg-black/40 shrink-0"
                        @click="toggleBookmark">
                        <i :class="isBookmarked ? 'ph-fill ph-bookmark-simple' : 'ph ph-bookmark-simple'"
                            :style="isBookmarked ? 'color: #F59E0B' : ''" class="text-xl"></i>
                    </button>
                    <button
                        class="w-11 h-11 rounded-full bg-black/20 backdrop-blur-xl border border-white/10 flex items-center justify-center text-white shadow-2xl transition-all active:scale-90 hover:bg-black/40 shrink-0"
                        @click="shareCafe">
                        <i class="ph ph-share-network text-xl"></i>
                    </button>
                </div>
            </nav>

            {{-- Slider Images --}}
            <template x-for="(img, idx) in images" :key="idx">
                <div x-show="currentSlide === idx" class="absolute inset-0 z-0 cursor-zoom-in">
                    <img :src="img" class="detail-gallery-img w-full h-full object-cover" alt="Cafe">
                    <div class="absolute inset-0 bg-gradient-to-t from-black/60 via-transparent to-black/20"></div>
                </div>
            </template>
            <div x-show="images.length === 0" class="absolute inset-0 z-0 bg-slate-200 flex items-center justify-center">
                <img src="https://placehold.co/1200x800?text={{ urlencode($cafe->name) }}"
                    class="w-full h-full object-cover">
                <div class="absolute inset-0 bg-black/20"></div>
            </div>

            {{-- Slider Info Overlay --}}
            <div class="absolute bottom-16 left-6 right-6 z-20" @click.stop>
                <livewire:cafe-detail :cafe-id="$cafe->id" />
            </div>

            {{-- Dot Indicators --}}
            <div class="absolute bottom-10 left-6 flex gap-1.5 z-30" @click.stop>
                <template x-for="(_, idx) in images" :key="idx">
                    <div class="h-1 rounded-full transition-all duration-500"
                        :class="currentSlide === idx ? 'w-8 bg-white' : 'w-2 bg-white/40'" @click="currentSlide = idx">
                    </div>
                </template>
            </div>
        </div>

        {{-- Main Detail Content --}}
        <div class="detail-content-luxury animate-up">
            <div class="mb-6">
                <h1 class="text-3xl font-black text-[#2C1810] leading-tight mb-2">{{ $cafe->name }}</h1>
                <div class="flex items-start gap-2 text-slate-500 font-medium text-sm">
                    <i class="ph-fill ph-map-pin text-amber-600 text-lg mt-0.5"></i>
                    <span>{{ $cafe->address }}</span>
                </div>
            </div>
            <div class="text-slate-600 leading-[1.8] text-[1rem] font-medium mb-10 opacity-90">
                {!! clean($cafe->description) !!}
            </div>

            {{-- Kontak & Sosmed Section --}}
            @php
                // Filter active social links - handle various boolean representations
                $activeSocialLinks = collect($cafe->social_links ?? [])->filter(function ($link) {
                    $isVisible = isset($link['show']) && filter_var($link['show'], FILTER_VALIDATE_BOOLEAN);
                    $hasUrl = !empty($link['url']);
                    return $isVisible && $hasUrl;
                });
                $hasGoogleMaps = !empty($cafe->google_maps_url);
                $hasWhatsApp = !empty($cafe->whatsapp_number);
            @endphp

            {{-- Kontak & Sosmed — Magazine Bento Grid --}}
            <section class="mb-10">
                <div class="detail-bento-grid">
                    {{-- Row 1: Lokasi + Chat --}}
                    <div class="detail-bento-row">
                        @if($hasGoogleMaps)
                            <a href="{{ e($cafe->google_maps_url) }}" target="_blank" class="detail-tile detail-tile--lokasi">
                                <div class="detail-tile__icon"><i class="ph-fill ph-map-pin"></i></div>
                                <div>
                                    <div class="detail-tile__label">Lokasi</div>
                                    <div class="detail-tile__sublabel">Google Maps</div>
                                </div>
                            </a>
                        @endif

                        @if($hasWhatsApp)
                            @php
                                $rawWa = preg_replace('/[^0-9]/', '', $cafe->whatsapp_number);
                                if (str_starts_with($rawWa, '08')) $rawWa = '62' . substr($rawWa, 1);
                            @endphp
                            <a href="https://wa.me/{{ $rawWa }}" target="_blank" class="detail-tile detail-tile--chat">
                                <div class="detail-tile__icon"><i class="ph-fill ph-whatsapp-logo"></i></div>
                                <div>
                                    <div class="detail-tile__label">Chat</div>
                                    <div class="detail-tile__sublabel">WhatsApp</div>
                                </div>
                            </a>
                        @endif
                    </div>

                    {{-- Row 2: Jam Buka — Full Width --}}
                    <div class="detail-tile detail-tile--fullwidth detail-tile--hours">
                        <div class="detail-tile__icon"><i class="ph-fill ph-clock"></i></div>
                        <div class="flex-1">
                            <div class="detail-tile__label">Jam Operasional</div>
                            <div class="text-[0.75rem] font-bold text-slate-500 flex items-center gap-1.5 mt-0.5">
                                <livewire:cafe-detail :cafe-id="$cafe->id" />
                            </div>
                        </div>
                    </div>
                </div>

                {{-- Social Media Links (Horizontal Scroll) --}}
                @if($activeSocialLinks->isNotEmpty())
                    <div class="flex gap-3 overflow-x-auto no-scrollbar px-4 mt-6 pb-2">
                        @foreach($activeSocialLinks as $link)
                            @php
                                $platform = strtolower($link['platform'] ?? '');
                                $icon = match ($platform) {
                                    'instagram' => 'ph-fill ph-instagram-logo',
                                    'tiktok' => 'ph-fill ph-tiktok-logo',
                                    'facebook' => 'ph-fill ph-facebook-logo',
                                    'x', 'twitter' => 'ph-fill ph-x-logo',
                                    'youtube' => 'ph-fill ph-youtube-logo',
                                    'whatsapp' => 'ph-fill ph-whatsapp-logo',
                                    default => 'ph-fill ph-globe'
                                };
                            @endphp
                            <a href="{{ e($link['url']) }}" target="_blank"
                               class="flex-shrink-0 flex items-center gap-2 px-4 py-3 rounded-2xl bg-white border border-slate-100 shadow-soft active:scale-95 transition-all no-underline">
                                <i class="{{ $icon }} text-amber-600 text-lg"></i>
                                <span class="text-[0.7rem] font-black uppercase tracking-widest text-espresso">{{ $platform }}</span>
                            </a>
                        @endforeach
                    </div>
                @endif
            </section>

            </section>

            {{-- Tongkrongan CTA --}}
            <section class="mb-8">
                <a href="{{ route('tongkrongan.create') }}" class="w-full flex items-center justify-between bg-amber-50 border border-amber-200 rounded-2xl p-4 active:scale-[0.98] transition-transform no-underline">
                    <div class="flex items-center gap-3">
                        <div class="w-10 h-10 bg-amber-500 rounded-full flex items-center justify-center text-white shadow-sm">
                            <i class="ph-fill ph-users-three text-lg"></i>
                        </div>
                        <div>
                            <h3 class="text-sm font-black text-[#2C1810]">Masukin ke Tongkrongan</h3>
                            <p class="text-[0.65rem] font-bold text-amber-700">Vote bareng temen buat ke sini!</p>
                        </div>
                    </div>
                    <i class="ph-bold ph-caret-right text-amber-500"></i>
                </a>
            </section>

            {{-- Check-In Button (Gamification) --}}
            <div id="check-in-react"
                 data-cafe-id="{{ $cafe->id }}"
                 data-cafe-name="{{ $cafe->name }}"
                 data-cafe-lat="{{ $cafe->latitude }}"
                 data-cafe-lng="{{ $cafe->longitude }}"
                 data-is-authenticated="{{ auth()->check() ? 'true' : 'false' }}">
            </div>

            {{-- WFC Compatibility Score (React Hybrid) --}}
            <div id="wfc-score-react"
                 data-cafe-id="{{ $cafe->id }}"
                 data-cafe-name="{{ $cafe->name }}"
                 data-initial-score="{{ number_format($cafe->wfc_avg_score, 1) }}"
                 data-initial-count="{{ $cafe->wfc_review_count }}"
                 data-cafe-lat="{{ $cafe->latitude }}"
                 data-cafe-lng="{{ $cafe->longitude }}"
                 data-has-rated="{{ $hasRated ? 'true' : 'false' }}">
            </div>

            {{-- Live Vibe Meter (React Hybrid) --}}
            <div id="vibe-meter-react"
                 data-cafe-id="{{ $cafe->id }}"
                 data-cafe-lat="{{ $cafe->latitude }}"
                 data-cafe-lng="{{ $cafe->longitude }}">
            </div>

            {{-- Catatan Komunitas (WFC Comments) --}}
            @if($cafe->wfcScores->isNotEmpty())
                <section class="mb-12 animate-up">
                    <h2 class="text-sm font-bold text-espresso uppercase tracking-widest mb-5 flex items-center gap-2.5">
                        <i class="ph-fill ph-chat-centered-text text-amber-600 text-lg"></i>
                        Suara Warga
                    </h2>
                    <div class="space-y-4">
                        @foreach($cafe->wfcScores->take(5) as $score)
                            <div class="bg-white/40 backdrop-blur-md border border-slate-100 p-5 rounded-[24px] shadow-sm">
                                <div class="flex justify-between items-start mb-3">
                                    <div class="flex items-center gap-3">
                                        <div class="w-8 h-8 rounded-full bg-amber-100 flex items-center justify-center text-amber-600 font-bold text-[0.7rem]">
                                            {{ strtoupper(substr($score->user->name, 0, 1)) }}
                                        </div>
                                        <div>
                                            <div class="text-[0.75rem] font-black text-espresso">{{ $score->user->name }}</div>
                                            <div class="text-[0.6rem] text-slate-400 font-bold">{{ $score->created_at->diffForHumans() }}</div>
                                        </div>
                                    </div>
                                    @if($score->is_verified)
                                        <span class="text-[0.55rem] font-black text-green-600 bg-green-50 px-2 py-1 rounded-lg flex items-center gap-1">
                                            <i class="ph-fill ph-check-circle"></i> VERIFIED
                                        </span>
                                    @endif
                                </div>
                                <div class="flex gap-4 mb-3">
                                    <div class="flex flex-col">
                                        <span class="text-[0.5rem] font-bold text-slate-400 uppercase tracking-tighter">WiFi</span>
                                        <div class="flex gap-0.5">
                                            @for($i=1; $i<=5; $i++)
                                                <i class="ph-fill ph-star text-[0.6rem] {{ $i <= $score->wifi_rating ? 'text-amber-400' : 'text-slate-200' }}"></i>
                                            @endfor
                                        </div>
                                    </div>
                                    <div class="flex flex-col">
                                        <span class="text-[0.5rem] font-bold text-slate-400 uppercase tracking-tighter">Listrik</span>
                                        <div class="flex gap-0.5">
                                            @for($i=1; $i<=5; $i++)
                                                <i class="ph-fill ph-star text-[0.6rem] {{ $i <= $score->outlet_rating ? 'text-amber-400' : 'text-slate-200' }}"></i>
                                            @endfor
                                        </div>
                                    </div>
                                    <div class="flex flex-col">
                                        <span class="text-[0.5rem] font-bold text-slate-400 uppercase tracking-tighter">Vibe</span>
                                        <div class="flex gap-0.5">
                                            @for($i=1; $i<=5; $i++)
                                                <i class="ph-fill ph-star text-[0.6rem] {{ $i <= $score->comfort_rating ? 'text-amber-400' : 'text-slate-200' }}"></i>
                                            @endfor
                                        </div>
                                    </div>
                                </div>
                                <p class="text-[0.8rem] text-slate-600 leading-relaxed font-medium">
                                    "{{ $score->comment }}"
                                </p>
                            </div>
                        @endforeach
                    </div>
                </section>
            @endif

            {{-- Features Section --}}
            <section class="mb-12">
                <h2 class="text-sm font-bold text-espresso uppercase tracking-widest mb-5 flex items-center gap-2.5">
                    <i class="ph-fill ph-armchair text-amber-600 text-lg"></i>
                    Fasilitas Penunjang
                </h2>
                <div class="flex flex-wrap gap-2.5">
                    @forelse($cafe->facilities as $f)
                        <div
                            class="bg-slate-50 border border-slate-100 px-4 py-2.5 rounded-2xl flex items-center gap-2.5 transition-all shadow-sm hover:shadow-md hover:bg-amber-50/50 hover:border-amber-200/50 group/fac cursor-default">
                            <i
                                class="{{ $f->icon ?? 'ph ph-check-circle' }} text-slate-400 text-lg group-hover/fac:text-amber-500 group-hover/fac:scale-110 transition-all duration-300"></i>
                            <span
                                class="font-bold text-slate-700 text-[0.8rem] group-hover/fac:text-amber-800 transition-colors">{{ $f->name }}</span>
                        </div>
                    @empty
                        <span class="text-slate-300 italic text-sm">Informasi fasilitas belum tersedia.</span>
                    @endforelse
                </div>
            </section>

            {{-- Premium Menu Section --}}
            <section id="cafe-menu" class="cafe-menu-section relative section-premium-fade mb-16">

                @if(($menuItems ?? collect())->isNotEmpty())
                    <div class="cafe-menu-heading mb-6">
                        <div class="cafe-menu-heading-copy">
                            <h2 class="text-2xl font-extrabold text-[#2C1810] tracking-tight leading-tight">Daftar Menu</h2>
                            <p class="text-xs font-medium text-slate-500 mt-1 tracking-wide">
                                {{ $menuItems->count() }} item menu · {{ $activeGalleryImages->count() }} foto menu
                            </p>
                        </div>
                        @if($activeGalleryImages->count() > 0)
                            <span class="cafe-menu-photo-count inline-flex items-center gap-2 rounded-2xl border border-amber-100 bg-white px-4 py-2 text-[0.75rem] font-bold uppercase tracking-widest text-amber-950 shadow-sm">
                                <i class="ph-fill ph-camera text-amber-500 text-lg"></i>
                                {{ $activeGalleryImages->count() }} FOTO
                            </span>
                        @endif
                    </div>
                @endif

                @if(isset($menuItems) && $menuItems->isNotEmpty())
                    <div class="cafe-menu-panel mb-8">
                        <div class="cafe-menu-panel-heading">
                            <div class="cafe-menu-availability">
                                <span class="cafe-menu-availability-label">Katalog menu</span>
                                <span class="cafe-menu-product-count">{{ $menuItems->count() }} produk</span>
                            </div>
                            @if($menuItems->contains(fn ($item) => $item->is_active && $item->available_stock > 0))
                                <a href="{{ route('cart.show') }}" class="cafe-menu-cart-link">
                                    <i class="ph-bold ph-shopping-cart" aria-hidden="true"></i>
                                    <span>Lihat keranjang</span>
                                </a>
                            @endif
                        </div>

                        @if($errors->has('cart'))
                            <div class="mb-4 rounded-xl bg-rose-50 p-3 text-sm font-semibold text-rose-700" role="alert">{{ $errors->first('cart') }}</div>
                        @endif

                        @foreach($menuItemsByCategory as $category => $categoryItems)
                            @php $categoryHeadingId = 'menu-category-' . Str::slug($category) . '-' . $loop->index; @endphp
                            <section class="cafe-menu-category" aria-labelledby="{{ $categoryHeadingId }}">
                                <div class="cafe-menu-category-heading">
                                    <h3 id="{{ $categoryHeadingId }}">{{ $category }}</h3>
                                    <span>{{ $categoryItems->count() }} menu</span>
                                </div>
                                <div class="cafe-menu-grid">
                                    @foreach($categoryItems as $item)
                                        @php
                                            $itemImage = $item->image_path
                                                ? (Str::startsWith($item->image_path, ['http://', 'https://']) ? $item->image_path : Storage::disk('public')->url($item->image_path))
                                                : null;
                                        @endphp
                                        <article class="cafe-menu-item">
                                            @if($itemImage)
                                                <img src="{{ $itemImage }}" alt="{{ $item->name }}" class="cafe-menu-item-image" loading="lazy">
                                            @else
                                                <div class="cafe-menu-item-placeholder" aria-hidden="true"><i class="ph ph-coffee"></i></div>
                                            @endif
                                            <div class="cafe-menu-item-content">
                                                <div class="cafe-menu-item-copy">
                                                    <h4 class="cafe-menu-item-name">{{ $item->name }}</h4>
                                                    @if($item->description)<p class="cafe-menu-item-description">{{ $item->description }}</p>@endif
                                                </div>
                                                <div class="cafe-menu-item-footer">
                                                    <p class="cafe-menu-item-price">Rp{{ number_format((float) $item->price, 0, ',', '.') }}</p>
                                                    @if($item->is_active && $item->available_stock > 0)
                                                        <form method="POST" action="{{ route('cart.items.add', [$cafe->slug, $item->id]) }}">
                                                            @csrf
                                                            <button type="submit" class="cafe-menu-add-button">
                                                                <i class="ph-bold ph-plus" aria-hidden="true"></i>
                                                                <span>Tambah</span>
                                                            </button>
                                                        </form>
                                                    @endif
                                                </div>
                                            </div>
                                        </article>
                                    @endforeach
                                </div>
                            </section>
                        @endforeach
                        <p class="cafe-menu-order-note">Pilih QRIS, transfer manual, atau tunai di kedai saat checkout.</p>
                    </div>
                @endif

                @if($activeGalleryImages->count() > 0)
                    <div class="mb-4 flex items-center gap-2 px-1 text-xs font-black uppercase tracking-[0.15em] text-slate-500">
                        <i class="ph-fill ph-images text-amber-600 text-base"></i> Foto menu
                    </div>
                    {{-- Premium Uniform Grid (Aspect 4:5 + Interaction) --}}
                    <div class="grid grid-cols-2 md:grid-cols-4 gap-4 px-1">
                        @foreach($activeGalleryImages as $index => $img)
                            <div class="relative group/menu cursor-pointer transform transition-all duration-500 hover:-translate-y-2"
                                @click="openLightbox('menu', {{ $index }})"
                                style="animation: fadeInUp 0.5s cubic-bezier(0.34, 1.56, 0.64, 1) {{ $index * 0.05 }}s both;">

                                {{-- Card Container (Fixed Aspect Ratio 4:5 + Deep Shadow) --}}
                                <div
                                    class="relative aspect-[4/5] w-full rounded-2xl overflow-hidden bg-white shadow-[0_8px_20px_-6px_rgba(0,0,0,0.08)] group-hover/menu:shadow-[0_20px_40px_-12px_rgba(0,0,0,0.15)] transition-all duration-500 ring-1 ring-black/5">

                                    {{-- Image (Smart Crop + Zoom Effect) --}}
                                    <img src="{{ str_starts_with($img['image'], 'http') ? $img['image'] : '/storage/' . $img['image'] }}"
                                        class="w-full h-full object-cover object-center transition-transform duration-700 ease-[cubic-bezier(0.25,1,0.5,1)] group-hover/menu:scale-105"
                                        alt="{{ $img['tag'] }}" loading="lazy">

                                    {{-- Full Gradient Overlay (Cinematic) --}}
                                    <div
                                        class="absolute inset-0 bg-gradient-to-t from-black/70 via-black/10 to-transparent opacity-0 group-hover/menu:opacity-100 transition-opacity duration-500">
                                    </div>

                                    {{-- Tag Badge (Floating) --}}
                                    @if(!empty($img['tag']))
                                        <div
                                            class="absolute bottom-3 left-3 right-3 opacity-0 group-hover/menu:opacity-100 transform translate-y-4 group-hover/menu:translate-y-0 transition-all duration-500 delay-75">
                                            <span
                                                class="inline-flex items-center gap-1.5 bg-white/95 backdrop-blur-md px-2.5 py-1.5 rounded-lg shadow-lg ring-1 ring-black/5">
                                                <i class="ph-fill ph-tag text-amber-600 text-[0.65rem]"></i>
                                                <span
                                                    class="text-[0.65rem] font-bold text-slate-800 uppercase tracking-wider truncate max-w-full">
                                                    {{ $img['tag'] }}
                                                </span>
                                            </span>
                                        </div>
                                    @endif

                                    {{-- Zoom Icon (Center Pulse) --}}
                                    <div
                                        class="absolute inset-0 flex items-center justify-center opacity-0 group-hover/menu:opacity-100 transition-opacity duration-500">
                                        <div
                                            class="w-12 h-12 bg-white/20 backdrop-blur-md rounded-full flex items-center justify-center text-white border border-white/30 transform scale-50 opacity-0 group-hover/menu:scale-100 group-hover/menu:opacity-100 transition-all duration-500 delay-100 shadow-xl">
                                            <i class="ph-bold ph-arrows-out-simple text-lg"></i>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        @endforeach
                    </div>


                @endif
            </section>

            <style>
                @keyframes fadeInUp {
                    from {
                        opacity: 0;
                        transform: translateY(20px);
                    }

                    to {
                        opacity: 1;
                        transform: translateY(0);
                    }
                }
            </style>

            <div class="h-24"></div>
        </div>

        {{-- Menu Lightbox (Unified Premium) --}}
        <div x-show="activeLightbox === 'menu'" x-transition.opacity
            class="fixed inset-0 z-[20000] bg-black/98 backdrop-blur-xl flex flex-col items-center justify-center p-0"
            @keydown.escape.window="closeLightbox()" @keydown.left.window="prevImage()" @keydown.right.window="nextImage()"
            @wheel.prevent="handleWheel($event)" @touchstart="handleTouchStart($event)" @touchmove="handleTouchMove($event)"
            @touchend="handleTouchEnd($event)" @click="closeLightbox()" x-cloak>

            <button @click="closeLightbox()"
                class="absolute top-6 right-6 z-[100000] w-10 h-10 bg-white/10 hover:bg-white/20 rounded-full flex items-center justify-center text-white border border-white/10 transition-all active:scale-90 backdrop-blur-md cursor-pointer">
                <i class="ph-bold ph-x text-lg"></i>
            </button>

            {{-- Image Container --}}
            <div class="w-full h-full flex items-center justify-center relative overflow-hidden pointer-events-none p-0 md:p-8"
                @click.self="closeLightbox()">
                <template x-for="(m, i) in menuImages" :key="i">
                    <div x-show="lightboxIdx === i" x-transition:enter="transition duration-300 ease-out"
                        x-transition:enter-start="opacity-0 scale-95" x-transition:enter-end="opacity-100 scale-100"
                        x-transition:leave="transition duration-200 ease-in"
                        x-transition:leave-start="opacity-100 scale-100" x-transition:leave-end="opacity-0 scale-95"
                        class="absolute inset-0 flex items-center justify-center pointer-events-auto w-full h-full">
                        <img :src="m.url" class="max-w-full max-h-[85vh] object-contain shadow-2xl mx-auto cursor-zoom-in"
                            :alt="m.tag" @click.stop="toggleZoom()"
                            :style="{ transform: `scale(${zoomLevel}) translate(${zoomLevel > 1 ? panX : swipeX}px, ${panY}px)` }">
                    </div>
                </template>
            </div>

            {{-- Floating Controls --}}
            <div class="absolute bottom-10 left-0 right-0 z-[100000] flex justify-center pointer-events-none">
                <div
                    class="flex items-center gap-4 bg-white/10 backdrop-blur-md px-5 py-2.5 rounded-full border border-white/10 shadow-lg pointer-events-auto">
                    <button @click.stop="prevImage()"
                        class="w-10 h-10 rounded-full flex items-center justify-center text-white/80 hover:text-white hover:bg-white/10 transition-all active:scale-90">
                        <i class="ph-bold ph-caret-left text-2xl"></i>
                    </button>
                    <span class="text-white font-bold text-xs tracking-widest w-12 text-center">
                        <span x-text="lightboxIdx + 1"></span>/<span x-text="totalLightboxImages"></span>
                    </span>
                    <button @click.stop="nextImage()"
                        class="w-10 h-10 rounded-full flex items-center justify-center text-white/80 hover:text-white hover:bg-white/10 transition-all active:scale-90">
                        <i class="ph-bold ph-caret-right text-2xl"></i>
                    </button>
                </div>
            </div>
        </div>

        {{-- Hero Gallery Lightbox (Premium Slider) --}}
        <div x-show="activeLightbox === 'hero'" x-transition.opacity
            class="fixed inset-0 z-[20000] bg-black/98 backdrop-blur-xl flex flex-col items-center justify-center p-0"
            @keydown.escape.window="closeLightbox()" @keydown.left.window="prevImage()" @keydown.right.window="nextImage()"
            @wheel.prevent="handleWheel($event)" @touchstart="handleTouchStart($event)" @touchmove="handleTouchMove($event)"
            @touchend="handleTouchEnd($event)" @click="closeLightbox()" x-cloak>

            {{-- Close Button --}}
            <button @click.stop="closeLightbox()"
                class="absolute top-6 right-6 z-[20001] w-12 h-12 bg-white/10 hover:bg-white/20 rounded-full flex items-center justify-center text-white border border-white/10 transition-all active:scale-90 cursor-pointer">
                <i class="ph-bold ph-x text-xl"></i>
            </button>

            {{-- Nav Arrows --}}
            <button x-show="totalLightboxImages > 1" @click.stop="prevImage()"
                class="hidden sm:flex absolute left-8 z-[20001] w-14 h-14 bg-white/10 hover:bg-white/20 rounded-full items-center justify-center text-white transition-all active:scale-90 border border-white/10 cursor-pointer">
                <i class="ph-bold ph-caret-left text-3xl"></i>
            </button>
            <button x-show="totalLightboxImages > 1" @click.stop="nextImage()"
                class="hidden sm:flex absolute right-8 z-[20001] w-14 h-14 bg-white/10 hover:bg-white/20 rounded-full items-center justify-center text-white transition-all active:scale-90 border border-white/10 cursor-pointer">
                <i class="ph-bold ph-caret-right text-3xl"></i>
            </button>

            {{-- Slides Container --}}
            <div class="w-full h-full flex items-center justify-center relative overflow-hidden pointer-events-none">
                <template x-for="(img, i) in images" :key="i">
                    <div x-show="lightboxIdx === i" x-transition:enter="transition duration-500 ease-out"
                        x-transition:enter-start="opacity-0 scale-90" x-transition:enter-end="opacity-100 scale-100"
                        class="px-4 text-center max-w-[95%] pointer-events-auto">
                        <img :src="img"
                            class="max-w-full max-h-[85vh] rounded-[32px] shadow-3xl object-contain mx-auto border-4 border-white/10 cursor-default"
                            @click.stop="toggleZoom()"
                            :style="{ transform: `scale(${zoomLevel}) translate(${zoomLevel > 1 ? panX : swipeX}px, ${panY}px)` }">
                    </div>
                </template>
            </div>

            {{-- Counter Indicator --}}
            <div class="absolute bottom-10 bg-white/10 backdrop-blur-md px-5 py-2 rounded-full border border-white/10"
                @click.stop>
                <span class="text-white font-black text-xs tracking-[0.2em]">
                    <span x-text="lightboxIdx + 1"></span> / <span x-text="totalLightboxImages"></span>
                </span>
            </div>
        </div>
    </div>

    <script>
        document.addEventListener('alpine:init', () => {
            Alpine.data('cafeDetailComponent', (props) => ({
                currentSlide: 0,
                currentTab: '',
                allCategories: [],
                images: [],
                menuImages: [],
                isBookmarked: false,
                visitorId: '',

                // LIGHTBOX STATE
                activeLightbox: null, // 'hero' or 'menu'
                lightboxIdx: 0,

                imagesLoaded: false,

                // ZOOM & PAN STATE
                zoomLevel: 1,
                panX: 0,
                panY: 0,
                isDragging: false,
                startX: 0,
                startY: 0,
                lastPinchDist: 0,

                // SWIPE STATE
                swipeX: 0,
                isSwiping: false,

                init() {
                    this.allCategories = props.allCategories || [];
                    this.images = props.images || [];
                    this.menuImages = props.menuImages || [];
                    this.currentTab = props.defaultTab || (this.allCategories.length > 0 ? this.allCategories[0] : '');

                    try {
                        let vid = localStorage.getItem('wadah-visitor-id');
                        if (!vid) {
                            vid = 'visitor-' + Math.random().toString(36).substr(2, 9) + Date.now();
                            localStorage.setItem('wadah-visitor-id', vid);
                        }
                        this.visitorId = vid;
                        const saved = JSON.parse(localStorage.getItem('wadah-bookmarks') || '[]');
                        this.isBookmarked = saved.includes(props.id);
                    } catch (e) { console.error('[Cafe Detail] LocalStorage error:', e); }


                    // Auto Slide Hero
                    setInterval(() => {
                        if (!this.activeLightbox) this.nextSlide();
                    }, 5000);

                    // Reset zoom when slide changes
                    this.$watch('lightboxIdx', () => this.resetZoom());

                    setTimeout(() => { this.imagesLoaded = true; }, 500);
                },

                // ------------------ HERO SLIDER (Inline) ------------------
                nextSlide() {
                    if (this.images.length > 0) this.currentSlide = (this.currentSlide + 1) % this.images.length;
                },

                // ------------------ UNIFIED LIGHTBOX ------------------
                openLightbox(type, idx) {
                    this.activeLightbox = type;
                    this.lightboxIdx = idx;
                    this.resetZoom();
                    document.body.style.overflow = 'hidden'; // Lock scroll
                },

                closeLightbox() {
                    this.activeLightbox = null;
                    this.resetZoom();
                    document.body.style.overflow = ''; // Unlock scroll
                },

                get currentLightboxImage() {
                    if (this.activeLightbox === 'hero') return this.images[this.lightboxIdx];
                    if (this.activeLightbox === 'menu') return this.menuImages[this.lightboxIdx]?.url;
                    return '';
                },

                get totalLightboxImages() {
                    if (this.activeLightbox === 'hero') return this.images.length;
                    if (this.activeLightbox === 'menu') return this.menuImages.length;
                    return 0;
                },

                nextImage() {
                    const total = this.totalLightboxImages;
                    if (total > 0) this.lightboxIdx = (this.lightboxIdx + 1) % total;
                },

                prevImage() {
                    const total = this.totalLightboxImages;
                    if (total > 0) this.lightboxIdx = (this.lightboxIdx - 1 + total) % total;
                },

                // ------------------ TOUCH & ZOOM LOGIC ------------------
                // Used for both Lightbox and main slider touches if needed

                handleTouchStart(e) {
                    if (e.touches.length === 1) {
                        this.isDragging = true;
                        this.startX = e.touches[0].clientX;
                        this.startY = e.touches[0].clientY;

                        // If zoomed, we pan. If not, we might swipe.
                        if (this.zoomLevel <= 1) {
                            this.isSwiping = true;
                            this.swipeX = 0;
                        }
                    } else if (e.touches.length === 2) {
                        // Pinch start
                        this.isDragging = false;
                        this.isSwiping = false;
                        this.lastPinchDist = this.getPinchDist(e);
                    }
                },

                handleTouchMove(e) {
                    if (e.touches.length === 1 && this.isDragging) {
                        const clientX = e.touches[0].clientX;
                        const clientY = e.touches[0].clientY;

                        if (this.zoomLevel > 1) {
                            // Pan Logic
                            e.preventDefault(); // Stop page scroll when panning zoomed image
                            this.panX += clientX - this.startX;
                            this.panY += clientY - this.startY;
                            this.startX = clientX;
                            this.startY = clientY;
                        } else if (this.isSwiping) {
                            // Swipe Logic (Visual feedback)
                            const diffX = clientX - this.startX;
                            const diffY = clientY - this.startY;

                            // Only treat as horizontal swipe if X movement is dominant
                            if (Math.abs(diffX) > Math.abs(diffY)) {
                                e.preventDefault();
                                this.swipeX = diffX;
                            }
                        }
                    } else if (e.touches.length === 2) {
                        // Pinch Zoom Logic
                        e.preventDefault();
                        const dist = this.getPinchDist(e);
                        const delta = dist - this.lastPinchDist;
                        this.zoomLevel = Math.min(Math.max(1, this.zoomLevel + (delta * 0.01)), 4);
                        this.lastPinchDist = dist;
                    }
                },

                handleTouchEnd(e) {
                    this.isDragging = false;

                    if (this.zoomLevel > 1) {
                        // Dampening or bounds check could go here
                        return;
                    }

                    if (this.isSwiping) {
                        if (this.swipeX < -50) this.nextImage();
                        else if (this.swipeX > 50) this.prevImage();
                    }

                    // Reset swipe
                    this.isSwiping = false;
                    this.swipeX = 0;
                },

                getPinchDist(e) {
                    return Math.hypot(
                        e.touches[0].clientX - e.touches[1].clientX,
                        e.touches[0].clientY - e.touches[1].clientY
                    );
                },

                // Mouse Wheel Zoom
                handleWheel(e) {
                    if (e.ctrlKey) {
                        e.preventDefault();
                        const delta = e.deltaY * -0.01;
                        this.zoomLevel = Math.min(Math.max(1, this.zoomLevel + delta), 4);
                    }
                },

                // Reset
                resetZoom() {
                    this.zoomLevel = 1;
                    this.panX = 0;
                    this.panY = 0;
                    this.swipeX = 0;
                },

                // ------------------ UTILS ------------------
                async toggleBookmark() {
                    try {
                        let b = JSON.parse(localStorage.getItem('wadah-bookmarks') || '[]');
                        this.isBookmarked ? b = b.filter(id => id !== props.id) : b.push(props.id);
                        localStorage.setItem('wadah-bookmarks', JSON.stringify(b));
                        this.isBookmarked = !this.isBookmarked;

                        // Hybrid DB Sync for authenticated users
                        if ({{ auth()->check() ? 'true' : 'false' }}) {
                            await fetch('/api/bookmarks/toggle', {
                                method: 'POST',
                                headers: {
                                    'Content-Type': 'application/json',
                                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.content,
                                    'Accept': 'application/json',
                                },
                                body: JSON.stringify({ bookmarkable_id: props.id, bookmarkable_type: 'cafe' })
                            });
                        }

                        window.dispatchEvent(new CustomEvent('toast', { detail: { message: this.isBookmarked ? 'Disimpan ke favorit' : 'Dihapus dari favorit', type: 'success' } }));
                    } catch (e) { console.error(e); }
                },
                shareCafe() {
                    const shareData = {
                        title: '{{ $cafe->name }} - WadahNgopi',
                        text: 'Cek cafe estetik ini di WadahNgopi: {{ $cafe->name }}',
                        url: window.location.href
                    };
                    if (navigator.share) {
                        navigator.share(shareData).catch(err => console.log('Error sharing:', err));
                    } else {
                        navigator.clipboard.writeText(shareData.url).then(() => {
                            window.dispatchEvent(new CustomEvent('toast', { detail: { message: 'Link disalin ke clipboard!', type: 'success' } }));
                        });
                    }
                }
            }));
        });
    </script>
@endsection
