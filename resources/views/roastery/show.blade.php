@extends('layouts.app')

@section('title', $roastery->name . ' - WadahNgopi.Com')
@section('meta_description', Str::limit(strip_tags($roastery->description), 155) ?: 'Temukan ' . $roastery->name . ' di ' . ($roastery->city?->name ?? 'Kalimantan') . '. Roastery terbaik pilihan WadahNgopi.')
@section('og_title', $roastery->name . ' - WadahNgopi')
@section('og_description', Str::limit(strip_tags($roastery->description), 100) ?: 'Roastery terbaik di ' . ($roastery->city?->name ?? 'Kalimantan'))
@section('og_image', $roastery->image_path ? (str_starts_with($roastery->image_path, 'http') ? $roastery->image_path : Storage::url($roastery->image_path)) : asset('wadahngopi.png'))

@section('content')
    @php
        $galleryImages = $roastery->processed_images;

        // Handle Menu Images (Roastery has simple list vs Cafe's tagged list)
        // We convert roastery's simple URL list to object structure for current view logic
        $menuRaw = $roastery->processed_menu_images ?? [];
        $menuImages = collect($menuRaw)->map(fn($url) => ['url' => $url, 'tag' => null])->values()->all();
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

        .roastery-detail-page {
            color: #2c1810;
        }

        .roastery-detail-page .detail-hero-luxury {
            height: min(55vh, 520px);
            min-height: 360px;
            border-radius: 0 0 32px 32px;
        }

        .roastery-detail-page .detail-content-luxury {
            margin-top: -26px;
            padding: 30px 20px 40px;
            border: 1px solid rgba(73, 48, 31, .07);
            border-bottom: 0;
            border-radius: 30px 30px 0 0;
            background: #fcfaf7;
            box-shadow: 0 -12px 32px rgba(39, 25, 16, .06);
        }

        .roastery-detail-page .roastery-heading {
            margin-bottom: 18px;
        }

        .roastery-detail-page .roastery-heading h1 {
            margin-bottom: 10px;
            color: #2c1810;
            font-size: clamp(1.55rem, 7vw, 1.9rem);
            letter-spacing: -.04em;
            overflow-wrap: anywhere;
        }

        .roastery-detail-page .roastery-address {
            color: #81766c;
            line-height: 1.5;
        }

        .roastery-detail-page .roastery-description {
            margin-bottom: 28px;
            color: #766b61;
            font-size: .94rem;
            line-height: 1.75;
        }

        .roastery-detail-page .roastery-description p {
            margin: 0 0 1em;
        }

        .roastery-detail-page .roastery-description p:last-child {
            margin-bottom: 0;
        }

        .roastery-detail-page .roastery-contact-section {
            margin-bottom: 30px;
            padding: 18px;
            border: 1px solid rgba(73, 48, 31, .08);
            border-radius: 22px;
            background: rgba(255, 255, 255, .82);
            box-shadow: 0 8px 24px rgba(39, 25, 16, .04);
        }

        .roastery-detail-page .roastery-contact-section h3 {
            color: #2c1810;
        }

        .roastery-detail-page .roastery-menu-section {
            margin-bottom: 2rem;
        }

        .roastery-detail-page .roastery-menu-header {
            margin-bottom: 18px;
            padding: 0;
        }

        .roastery-detail-page .roastery-menu-header h2 {
            color: #2c1810;
            font-size: 1.45rem;
            letter-spacing: -.035em;
        }

        .roastery-detail-page .roastery-menu-header p {
            color: #81766c;
        }

        .roastery-detail-page .roastery-menu-card {
            border-radius: 18px;
            box-shadow: 0 6px 18px rgba(39, 25, 16, .08);
        }

        .roastery-detail-page .roastery-empty-menu {
            padding: 42px 20px;
            border: 1px dashed rgba(111, 78, 55, .2);
            border-radius: 24px;
            background: linear-gradient(145deg, #fff, #f8f4ed);
        }

        .roastery-detail-page a:focus-visible,
        .roastery-detail-page button:focus-visible {
            outline: 3px solid rgba(210, 135, 15, .55);
            outline-offset: 3px;
        }

        @media (max-width: 380px) {
            .roastery-detail-page .detail-content-luxury {
                padding: 26px 16px 36px;
            }

            .roastery-detail-page .roastery-contact-section {
                padding: 15px;
            }
        }
    </style>

    <div class="detail-wrapper roastery-detail-page"
        x-data="roasteryDetailComponent({
                                                                                                                                                id: {{ $roastery->id }},
                                                                                                                                                images: {{ json_encode($galleryImages) }},
                                                                                                                                                menuImages: {{ json_encode($menuImages) }}
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
                        @click="shareRoastery">
                        <i class="ph ph-share-network text-xl"></i>
                    </button>
                </div>
            </nav>

            {{-- Slider Images --}}
            <template x-for="(img, idx) in images" :key="idx">
                <div x-show="currentSlide === idx" class="absolute inset-0 z-0 cursor-zoom-in">
                    <img :src="img" class="detail-gallery-img w-full h-full object-cover" alt="Roastery">
                    <div class="absolute inset-0 bg-gradient-to-t from-black/60 via-transparent to-black/20"></div>
                </div>
            </template>
            <div x-show="images.length === 0" class="absolute inset-0 z-0 bg-slate-200 flex items-center justify-center">
                <img src="https://placehold.co/1200x800?text={{ urlencode($roastery->name) }}"
                    class="w-full h-full object-cover">
                <div class="absolute inset-0 bg-black/20"></div>
            </div>

            {{-- Slider Info Overlay --}}
            <div class="absolute bottom-16 left-6 right-6 z-20" @click.stop>
                <livewire:roastery-detail :roastery-id="$roastery->id" />
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
            <div class="roastery-heading">
                <h1 class="text-3xl font-black leading-tight">{{ $roastery->name }}</h1>
                <div class="roastery-address flex items-start gap-2 font-medium text-sm">
                    <i class="ph-fill ph-map-pin text-amber-600 text-lg mt-0.5"></i>
                    <span>{{ $roastery->address ?: ($roastery->city?->name ?? 'Kalimantan') }}</span>
                </div>
            </div>
            <div class="roastery-description text-slate-600 font-medium">
                {!! clean($roastery->description) !!}
            </div>

            {{-- Kontak & Sosmed Section --}}
            @php
                // Filter active social links - handle various boolean representations
                $activeSocialLinks = collect($roastery->social_links ?? [])->filter(function ($link) {
                    $isVisible = isset($link['show']) && filter_var($link['show'], FILTER_VALIDATE_BOOLEAN);
                    $hasUrl = !empty($link['url']);
                    return $isVisible && $hasUrl;
                });
                $hasGoogleMaps = !empty($roastery->google_maps_url);
                $hasWhatsApp = !empty($roastery->whatsapp_number);
            @endphp

            @if($hasGoogleMaps || $hasWhatsApp || $activeSocialLinks->isNotEmpty())
                <section class="roastery-contact-section mb-8">
                    {{-- Section Title --}}
                    <div class="flex items-center justify-between mb-4">
                        <div class="flex items-center gap-2">
                            <i class="ph-bold ph-chats-circle text-amber-600 text-lg"></i>
                            <h3 class="text-espresso text-sm font-bold uppercase tracking-wider">Hubungi & Follow</h3>
                        </div>
                    </div>

                    {{-- Action Buttons --}}
                    @if($hasGoogleMaps || $hasWhatsApp)
                        <div class="flex items-center gap-3 mb-5">
                            @if($hasGoogleMaps)
                                <a href="{{ e($roastery->google_maps_url) }}" target="_blank" rel="noopener noreferrer"
                                    class="flex-1 group/btn relative overflow-hidden p-[1px] rounded-2xl transition-all active:scale-[0.98] no-underline shadow-soft hover:shadow-lg hover:shadow-amber-500/20">
                                    <div
                                        class="absolute inset-0 bg-gradient-to-br from-amber-200 via-amber-100 to-transparent opacity-0 group-hover/btn:opacity-100 transition-opacity duration-300">
                                    </div>
                                    <div
                                        class="relative bg-white rounded-2xl p-3 flex items-center gap-3 h-full border border-transparant">
                                        <div
                                            class="w-10 h-10 rounded-xl flex items-center justify-center shrink-0 bg-gradient-to-br from-amber-50 to-orange-50 text-amber-600 group-hover/btn:scale-110 transition-transform duration-300">
                                            <i class="ph-fill ph-map-pin text-lg"></i>
                                        </div>
                                        <div class="min-w-0">
                                            <span
                                                class="text-espresso text-[0.85rem] font-bold block leading-none mb-1 group-hover/btn:text-amber-700 transition-colors">Lokasi</span>
                                            <span
                                                class="text-slate-400 text-[10px] font-semibold group-hover/btn:text-amber-600/70 transition-colors">Google
                                                Maps</span>
                                        </div>
                                    </div>
                                </a>
                            @endif

                            @if($hasWhatsApp)
                                @php
                                    $rawWa = preg_replace('/[^0-9]/', '', $roastery->whatsapp_number);
                                    if (str_starts_with($rawWa, '08')) {
                                        $rawWa = '62' . substr($rawWa, 1);
                                    }
                                @endphp
                                <a href="https://wa.me/{{ $rawWa }}" target="_blank" rel="noopener noreferrer"
                                    class="flex-1 group/btn relative overflow-hidden p-[1px] rounded-2xl transition-all active:scale-[0.98] no-underline shadow-soft hover:shadow-lg hover:shadow-emerald-500/20">
                                    <div
                                        class="absolute inset-0 bg-gradient-to-br from-emerald-200 via-emerald-100 to-transparent opacity-0 group-hover/btn:opacity-100 transition-opacity duration-300">
                                    </div>
                                    <div
                                        class="relative bg-white rounded-2xl p-3 flex items-center gap-3 h-full border border-transparant">
                                        <div
                                            class="w-10 h-10 rounded-xl flex items-center justify-center shrink-0 bg-gradient-to-br from-emerald-50 to-green-50 text-emerald-600 group-hover/btn:scale-110 transition-transform duration-300">
                                            <i class="ph-fill ph-whatsapp-logo text-lg"></i>
                                        </div>
                                        <div class="min-w-0">
                                            <span
                                                class="text-espresso text-[0.85rem] font-bold block leading-none mb-1 group-hover/btn:text-emerald-700 transition-colors">Chat</span>
                                            <span
                                                class="text-slate-400 text-[10px] font-semibold group-hover/btn:text-emerald-600/70 transition-colors">WhatsApp</span>
                                        </div>
                                    </div>
                                </a>
                            @endif
                        </div>
                    @endif

                    {{-- Social Media Links --}}
                    @if($activeSocialLinks->isNotEmpty())
                        <div class="flex flex-wrap gap-2.5">
                            @foreach($activeSocialLinks as $link)
                                @php
                                    $platform = strtolower($link['platform'] ?? '');
                                    $iconClass = match ($platform) {
                                        'instagram' => 'ph-fill ph-instagram-logo',
                                        'tiktok' => 'ph-fill ph-tiktok-logo',
                                        'facebook' => 'ph-fill ph-facebook-logo',
                                        'x', 'twitter' => 'ph-fill ph-x-logo',
                                        'youtube' => 'ph-fill ph-youtube-logo',
                                        'whatsapp' => 'ph-fill ph-whatsapp-logo',
                                        default => 'ph-fill ph-globe'
                                    };
                                    $label = match ($platform) {
                                        'instagram' => 'Instagram',
                                        'tiktok' => 'TikTok',
                                        'facebook' => 'Facebook',
                                        'x', 'twitter' => 'X',
                                        'youtube' => 'YouTube',
                                        'whatsapp' => 'WhatsApp',
                                        default => 'Web'
                                    };
                                @endphp
                                <a href="{{ e($link['url']) }}" target="_blank" rel="noopener noreferrer"
                                    class="inline-flex items-center gap-2 px-4 py-2.5 rounded-xl border border-espresso/5 bg-white shadow-soft text-espresso active:scale-95 transition-all no-underline hover:bg-espresso hover:text-white group/social">
                                    <i
                                        class="{{ $iconClass }} text-sm text-amber-600 group-hover/social:text-white transition-colors"></i>
                                    <span class="text-[0.7rem] font-bold uppercase tracking-wider">{{ $label }}</span>
                                </a>
                            @endforeach
                        </div>
                    @endif
                </section>
            @endif

            {{-- Features Section (Empty for Roastery) --}}
            {{-- Removed "Fasilitas Tersedia" as per plan --}}

            @if($menuItems->isNotEmpty())
                <section class="mb-12 rounded-3xl border border-amber-100 bg-white p-5 shadow-sm sm:p-7">
                    <div class="mb-5 flex flex-wrap items-end justify-between gap-3">
                        <div><p class="text-xs font-black uppercase tracking-widest text-amber-700">Pesan stok tersedia</p><h2 class="mt-1 text-2xl font-black text-[#2C1810]">Katalog Produk Roastery</h2></div>
                        <a href="{{ route('cart.show') }}" class="rounded-full border border-amber-200 px-4 py-2 text-sm font-bold text-amber-900 no-underline">Lihat keranjang</a>
                    </div>
                    <div class="grid gap-4 sm:grid-cols-2">
                        @foreach($menuItems as $item)
                            <article class="flex gap-4 rounded-2xl border border-slate-100 p-4">
                                @if($item->image_path)<img src="{{ Storage::disk('public')->url($item->image_path) }}" alt="{{ $item->name }}" class="h-20 w-20 rounded-xl object-cover">@endif
                                <div class="min-w-0 flex-1"><p class="text-xs font-bold uppercase text-amber-700">{{ $item->category ?: 'Produk' }}</p><h3 class="font-black text-[#2C1810]">{{ $item->name }}</h3>@if($item->description)<p class="mt-1 text-xs text-slate-600">{{ $item->description }}</p>@endif
                                    <div class="mt-3 flex items-center justify-between gap-3"><strong>Rp{{ number_format((float)$item->price,0,',','.') }}</strong><form method="POST" action="{{ route('roastery.cart.items.add', [$roastery->slug, $item->id]) }}">@csrf<button class="rounded-full bg-[#2C1810] px-4 py-2 text-sm font-bold text-white">Tambah</button></form></div>
                                </div>
                            </article>
                        @endforeach
                    </div>
                    @if($errors->has('cart'))<p role="alert" class="mt-4 rounded-xl bg-rose-50 p-3 text-sm text-rose-800">{{ $errors->first('cart') }}</p>@endif
                </section>
            @endif

            {{-- Premium Menu Section --}}
            <section class="roastery-menu-section relative section-premium-fade mb-16">

                {{-- Section Header (Polished) --}}
                <div class="roastery-menu-header flex items-center justify-between mb-8 px-2">
                    <div>
                        <h2 class="text-2xl font-extrabold text-[#2C1810] tracking-tight leading-tight">Daftar Menu</h2>
                        <p class="text-xs font-medium text-slate-500 mt-1 tracking-wide">
                            {{ count($menuImages) }} Pilihan Menu Tersedia
                        </p>
                    </div>

                    {{-- Animated Badge --}}
                    <div
                        class="group relative overflow-hidden bg-white border border-amber-100 px-4 py-2 rounded-2xl shadow-sm hover:shadow-md transition-all duration-300">
                        <div
                            class="absolute inset-0 bg-gradient-to-r from-amber-50 to-orange-50 opacity-50 group-hover:opacity-100 transition-opacity duration-300">
                        </div>
                        <div class="relative flex items-center gap-2">
                            <i
                                class="ph-fill ph-camera text-amber-500 text-lg group-hover:scale-110 transition-transform duration-300"></i>
                            <span
                                class="text-[0.75rem] font-bold text-amber-950 uppercase tracking-widest group-hover:tracking-[0.15em] transition-all duration-300">
                                {{ count($menuImages) }} FOTO
                            </span>
                        </div>
                    </div>
                </div>

                @if(count($menuImages) > 0)
                    {{-- Premium Uniform Grid (Aspect 4:5 + Interaction) --}}
                    <div class="grid grid-cols-2 md:grid-cols-4 gap-4 px-1">
                        @foreach($menuImages as $index => $img)
                            <div class="relative group/menu cursor-pointer transform transition-all duration-500 hover:-translate-y-2"
                                @click="openLightbox('menu', {{ $index }})"
                                style="animation: fadeInUp 0.5s cubic-bezier(0.34, 1.56, 0.64, 1) {{ $index * 0.05 }}s both;">

                                {{-- Card Container (Fixed Aspect Ratio 4:5 + Deep Shadow) --}}
                                <div
                                    class="roastery-menu-card relative aspect-[4/5] w-full rounded-2xl overflow-hidden bg-white shadow-[0_8px_20px_-6px_rgba(0,0,0,0.08)] group-hover/menu:shadow-[0_20px_40px_-12px_rgba(0,0,0,0.15)] transition-all duration-500 ring-1 ring-black/5">

                                    {{-- Image (Smart Crop + Zoom Effect) --}}
                                    <img src="{{ $img['url'] }}"
                                        class="w-full h-full object-cover object-center transition-transform duration-700 ease-[cubic-bezier(0.25,1,0.5,1)] group-hover/menu:scale-105"
                                        alt="Menu Roastery" loading="lazy">

                                    {{-- Full Gradient Overlay (Cinematic) --}}
                                    <div
                                        class="absolute inset-0 bg-gradient-to-t from-black/70 via-black/10 to-transparent opacity-0 group-hover/menu:opacity-100 transition-opacity duration-500">
                                    </div>

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
                @else
                    {{-- Empty State --}}
                    <div
                        class="roastery-empty-menu py-20 text-center bg-gradient-to-br from-slate-50 to-slate-100/50 rounded-3xl border-2 border-dashed border-slate-200">
                        <div
                            class="w-20 h-20 bg-gradient-to-br from-amber-100 to-orange-100 rounded-full flex items-center justify-center mx-auto mb-5 shadow-inner">
                            <i class="ph-fill ph-image-square text-4xl text-amber-400"></i>
                        </div>
                        <p class="text-slate-400 font-bold text-sm">Belum ada menu yang diunggah</p>
                        <p class="text-slate-300 text-xs mt-1">Menu akan tampil di sini setelah ditambahkan</p>
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
                            alt="Menu Roastery" @click.stop="toggleZoom()"
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
            Alpine.data('roasteryDetailComponent', (props) => ({
                currentSlide: 0,
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
                    this.images = props.images || [];
                    this.menuImages = props.menuImages || [];

                    try {
                        let vid = localStorage.getItem('wadah-visitor-id');
                        if (!vid) {
                            vid = 'visitor-' + Math.random().toString(36).substr(2, 9) + Date.now();
                            localStorage.setItem('wadah-visitor-id', vid);
                        }
                        this.visitorId = vid;
                        const saved = JSON.parse(localStorage.getItem('wadah-bookmarks') || '[]');
                        this.isBookmarked = saved.includes(props.id);
                    } catch (e) { console.error('[Roastery Detail] LocalStorage error:', e); }


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
                toggleBookmark() {
                    try {
                        let b = JSON.parse(localStorage.getItem('wadah-bookmarks') || '[]');
                        this.isBookmarked ? b = b.filter(id => id !== props.id) : b.push(props.id);
                        localStorage.setItem('wadah-bookmarks', JSON.stringify(b));
                        this.isBookmarked = !this.isBookmarked;
                        window.dispatchEvent(new CustomEvent('toast', { detail: { message: this.isBookmarked ? 'Disimpan ke favorit' : 'Dihapus dari favorit', type: 'success' } }));
                    } catch (e) { console.error(e); }
                },
                shareRoastery() {
                    const shareData = {
                        title: '{{ $roastery->name }} - WadahNgopi',
                        text: 'Cek roastery estetik ini di WadahNgopi: {{ $roastery->name }}',
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