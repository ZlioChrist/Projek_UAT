<x-filament-panels::page>
    @php
        $categories = [
            'FOTO PRODUK & VISUAL' => 'Foto & visual',
            'PROMO & DISKON' => 'Promo',
            'LOGO & BRANDING' => 'Branding',
            'KONTEN VIDEO' => 'Video',
            'COPYWRITING & CAPTION' => 'Caption',
            'STRATEGI BISNIS' => 'Strategi',
        ];
    @endphp

    <div class="ai-studio space-y-6">
        <section class="ai-hero">
            <div class="ai-hero-copy">
                <span class="ai-eyebrow">
                    <x-heroicon-o-sparkles class="h-4 w-4" />
                    Ruang kreatif WadahNgopi
                </span>
                <h1>Ide kedai, siap dikembangkan dengan AI.</h1>
                <p>
                    Temukan inspirasi untuk foto produk, promosi, konten, dan strategi usaha.
                    Pilih prompt, sesuaikan gaya, lalu gunakan di alat AI pilihan Anda.
                </p>
            </div>
            <div class="ai-hero-mark" aria-hidden="true">
                <span class="ai-orbit ai-orbit-one"></span>
                <span class="ai-orbit ai-orbit-two"></span>
                <x-heroicon-o-sparkles class="h-14 w-14" />
            </div>
            <div class="ai-hero-stats" aria-label="Ringkasan koleksi prompt">
                <div>
                    <strong>{{ count($prompts) }}</strong>
                    <span>ide prompt</span>
                </div>
                <div>
                    <strong>{{ count($categories) }}</strong>
                    <span>kategori</span>
                </div>
                <div>
                    <strong>4</strong>
                    <span>gaya visual</span>
                </div>
            </div>
        </section>

        <section class="ai-guide" aria-labelledby="ai-guide-title">
            <div class="ai-section-heading">
                <div>
                    <span class="ai-kicker">MULAI DARI SINI</span>
                    <h2 id="ai-guide-title">Cara menggunakan</h2>
                </div>
                <p>Prompt adalah inspirasi yang dapat Anda ubah sesuai nama, menu, harga, dan karakter kedai.</p>
            </div>
            <div class="ai-guide-grid">
                <article class="ai-guide-step">
                    <span class="ai-step-number">01</span>
                    <x-heroicon-o-photo class="ai-guide-icon" />
                    <div>
                        <h3>Pilih ide</h3>
                        <p>Cari prompt yang sesuai dengan kebutuhan usaha Anda.</p>
                    </div>
                </article>
                <article class="ai-guide-step">
                    <span class="ai-step-number">02</span>
                    <x-heroicon-o-clipboard-document class="ai-guide-icon" />
                    <div>
                        <h3>Salin dan sesuaikan</h3>
                        <p>Salin prompt, lalu lengkapi detail usaha atau unggah foto produk.</p>
                    </div>
                </article>
                <article class="ai-guide-step">
                    <span class="ai-step-number">03</span>
                    <x-heroicon-o-arrow-top-right-on-square class="ai-guide-icon" />
                    <div>
                        <h3>Buka alat AI</h3>
                        <p>Tempel prompt dan unggah foto secara manual di alat AI pilihan.</p>
                    </div>
                </article>
            </div>
            <div class="ai-note">
                <x-heroicon-o-information-circle class="h-5 w-5 shrink-0" />
                <p>Halaman ini menyediakan inspirasi prompt. Hasil AI dibuat di layanan lain; foto dan prompt tidak dikirim otomatis oleh WadahNgopi.</p>
            </div>
        </section>

        <section class="ai-controls" aria-label="Pencarian dan filter prompt">
            <div class="ai-controls-top">
                <div>
                    <span class="ai-kicker">PERPUSTAKAAN IDE</span>
                    <h2>Temukan inspirasi berikutnya</h2>
                </div>
                <label class="ai-search">
                    <x-heroicon-o-magnifying-glass class="h-5 w-5 shrink-0" />
                    <span class="sr-only">Cari prompt</span>
                    <input id="ai-search-input" type="search" placeholder="Cari foto, promo, caption..." autocomplete="off">
                    <kbd>/</kbd>
                </label>
            </div>

            <div class="ai-filter-list" role="group" aria-label="Filter kategori prompt">
                <button type="button" class="ai-filter is-active" data-filter="all" aria-pressed="true">Semua ide</button>
                @foreach ($categories as $category => $label)
                    <button
                        type="button"
                        class="ai-filter"
                        data-filter="{{ \Illuminate\Support\Str::slug($category) }}"
                        aria-pressed="false"
                    >{{ $label }}</button>
                @endforeach
            </div>

            <div class="ai-vibe-row">
                <div class="ai-vibe-copy">
                    <x-heroicon-o-swatch class="h-5 w-5 shrink-0" />
                    <div>
                        <strong>Gaya visual</strong>
                        <span>Pilih suasana untuk ditambahkan ke prompt.</span>
                    </div>
                </div>
                <div class="ai-vibe-options" role="group" aria-label="Pilih gaya visual">
                    <button type="button" class="ai-vibe is-active" data-vibe="wooden" aria-pressed="true">
                        <x-heroicon-o-home-modern class="h-4 w-4" /> Hangat kayu
                    </button>
                    <button type="button" class="ai-vibe" data-vibe="industrial" aria-pressed="false">
                        <x-heroicon-o-building-office-2 class="h-4 w-4" /> Industrial
                    </button>
                    <button type="button" class="ai-vibe" data-vibe="minimalis" aria-pressed="false">
                        <x-heroicon-o-squares-2x2 class="h-4 w-4" /> Minimalis
                    </button>
                    <button type="button" class="ai-vibe" data-vibe="neon" aria-pressed="false">
                        <x-heroicon-o-moon class="h-4 w-4" /> Malam modern
                    </button>
                </div>
            </div>
        </section>

        <div class="ai-results-line" aria-live="polite">
            <span id="ai-results-count">{{ count($prompts) }} ide</span>
            <span>Pratinjau foto hanya tersimpan di perangkat ini.</span>
        </div>

        <section class="ai-prompt-grid" id="ai-prompt-grid" aria-label="Koleksi prompt AI">
            @foreach ($prompts as $index => $prompt)
                @php
                    $categorySlug = \Illuminate\Support\Str::slug($prompt['category']);
                    $icon = match ($prompt['category']) {
                        'FOTO PRODUK & VISUAL' => 'photo',
                        'PROMO & DISKON' => 'ticket',
                        'LOGO & BRANDING' => 'fingerprint',
                        'KONTEN VIDEO' => 'video-camera',
                        'COPYWRITING & CAPTION' => 'chat-bubble-left-right',
                        default => 'chart-bar',
                    };
                @endphp
                <article
                    class="ai-prompt-card"
                    data-category="{{ $categorySlug }}"
                    data-search="{{ \Illuminate\Support\Str::lower($prompt['category'].' '.$prompt['title'].' '.$prompt['prompt_text']) }}"
                >
                    <div class="ai-card-image-wrap">
                        @if ($prompt['image_url'])
                            <img
                                class="ai-card-image"
                                src="{{ $prompt['image_url'] }}"
                                alt="Inspirasi {{ $prompt['title'] }}"
                                loading="lazy"
                                onerror="this.hidden=true; this.nextElementSibling.hidden=false"
                            >
                        @endif
                        <div class="ai-card-image-fallback" @if ($prompt['image_url']) hidden @endif>
                            @switch($icon)
                                @case('photo') <x-heroicon-o-photo class="h-10 w-10" /> @break
                                @case('ticket') <x-heroicon-o-ticket class="h-10 w-10" /> @break
                                @case('fingerprint') <x-heroicon-o-finger-print class="h-10 w-10" /> @break
                                @case('video-camera') <x-heroicon-o-video-camera class="h-10 w-10" /> @break
                                @case('chat-bubble-left-right') <x-heroicon-o-chat-bubble-left-right class="h-10 w-10" /> @break
                                @default <x-heroicon-o-chart-bar class="h-10 w-10" />
                            @endswitch
                            <span>Tambahkan foto referensi</span>
                        </div>
                        <label class="ai-upload-action">
                            <x-heroicon-o-arrow-up-tray class="h-4 w-4" />
                            <span>Pilih foto</span>
                            <input
                                class="ai-image-input"
                                type="file"
                                accept="image/jpeg,image/png,image/webp"
                                data-preview-target="ai-preview-{{ $index }}"
                                aria-label="Pilih foto referensi untuk {{ $prompt['title'] }}"
                            >
                        </label>
                        <span class="ai-category-badge">{{ $prompt['category'] }}</span>
                    </div>

                    <div class="ai-card-content">
                        <h3>{{ $prompt['title'] }}</h3>
                        <div
                            class="ai-prompt-text"
                            data-original="{{ $prompt['prompt_text'] }}"
                            tabindex="0"
                            aria-label="Teks prompt: {{ $prompt['title'] }}"
                        >{{ $prompt['prompt_text'] }}</div>
                    </div>

                    <div class="ai-card-actions">
                        <button type="button" class="ai-copy-button">
                            <x-heroicon-o-clipboard-document class="h-4 w-4" />
                            <span>Salin prompt</span>
                        </button>
                        <a
                            class="ai-open-button"
                            href="https://gemini.google.com/"
                            target="_blank"
                            rel="noopener noreferrer"
                        >
                            <span>Salin &amp; buka Gemini</span>
                            <x-heroicon-o-arrow-top-right-on-square class="h-4 w-4" />
                        </a>
                    </div>
                    <p class="ai-card-status" aria-live="polite"></p>
                </article>
            @endforeach
        </section>

        <div class="ai-empty-state" id="ai-empty-state" hidden>
            <x-heroicon-o-magnifying-glass class="h-8 w-8" />
            <h3>Ide belum ditemukan</h3>
            <p>Coba kata kunci atau kategori lain.</p>
        </div>
    </div>

    @once
        <style>
            .ai-studio {
                --ai-espresso: #1a0f0a;
                --ai-brown: #6f4e37;
                --ai-amber: #eab308;
                --ai-cream: #fcfaf7;
                --ai-muted: #78716c;
                color: #29211c;
            }
            .dark .ai-studio { color: #f5eee7; }
            .ai-hero {
                position: relative; overflow: hidden; display: grid; grid-template-columns: minmax(0, 1fr) auto;
                gap: 1.5rem; align-items: center; padding: clamp(1.35rem, 3vw, 2.5rem);
                border: 1px solid rgba(234, 179, 8, .2); border-radius: 1.35rem;
                background: radial-gradient(circle at 90% 10%, rgba(234,179,8,.18), transparent 28%),
                    linear-gradient(125deg, #1a0f0a, #3b2419 70%, #60402f);
                color: #fffaf3; box-shadow: 0 18px 45px rgba(26,15,10,.16);
            }
            .ai-hero-copy { position: relative; z-index: 1; max-width: 48rem; }
            .ai-eyebrow, .ai-kicker { display: inline-flex; align-items: center; gap: .5rem; font-size: .72rem; font-weight: 800; letter-spacing: .12em; text-transform: uppercase; }
            .ai-eyebrow { color: #f6d36a; }
            .ai-hero h1 { margin: .85rem 0 .65rem; max-width: 45rem; color: #fffaf3; font-size: clamp(1.8rem, 4vw, 3rem); font-weight: 800; letter-spacing: -.045em; line-height: 1.08; }
            .ai-hero-copy p { margin: 0; max-width: 42rem; color: rgba(255,250,243,.78); font-size: clamp(.9rem, 1.4vw, 1.05rem); line-height: 1.7; }
            .ai-hero-mark { position: relative; display: grid; width: clamp(6.5rem, 13vw, 10rem); aspect-ratio: 1; place-items: center; color: #f6d36a; }
            .ai-hero-mark > svg { z-index: 1; width: 3.5rem; height: 3.5rem; }
            .ai-orbit { position: absolute; inset: 9%; border: 1px solid rgba(246,211,106,.35); border-radius: 40% 60% 55% 45%; transform: rotate(25deg); }
            .ai-orbit-two { inset: 0; transform: rotate(-35deg); border-color: rgba(246,211,106,.22); }
            .ai-hero-stats { grid-column: 1 / -1; display: flex; flex-wrap: wrap; gap: .65rem; }
            .ai-hero-stats > div { display: flex; align-items: baseline; gap: .45rem; padding: .55rem .85rem; border: 1px solid rgba(255,255,255,.15); border-radius: 999px; background: rgba(255,255,255,.07); }
            .ai-hero-stats strong { color: #f6d36a; font-size: 1rem; }
            .ai-hero-stats span { color: rgba(255,250,243,.78); font-size: .78rem; }
            .ai-guide, .ai-controls { padding: clamp(1rem, 2.4vw, 1.6rem); border: 1px solid #e8dfd7; border-radius: 1.1rem; background: #fff; }
            .dark .ai-guide, .dark .ai-controls { border-color: #3d332b; background: #1c1917; }
            .ai-section-heading, .ai-controls-top { display: flex; justify-content: space-between; align-items: end; gap: 1rem; }
            .ai-kicker { color: #8b5e3c; }
            .dark .ai-kicker { color: #f6d36a; }
            .ai-section-heading h2, .ai-controls-top h2 { margin: .3rem 0 0; color: inherit; font-size: clamp(1.15rem, 2vw, 1.5rem); font-weight: 800; letter-spacing: -.025em; }
            .ai-section-heading > p { max-width: 36rem; margin: 0; color: var(--ai-muted); font-size: .85rem; line-height: 1.55; }
            .ai-guide-grid { display: grid; grid-template-columns: repeat(3, minmax(0, 1fr)); gap: .8rem; margin-top: 1rem; }
            .ai-guide-step { display: grid; grid-template-columns: auto auto 1fr; gap: .7rem; align-items: start; padding: .95rem; border-radius: .8rem; background: #faf7f3; }
            .dark .ai-guide-step { background: #29231e; }
            .ai-step-number { color: #a68a78; font-size: .68rem; font-weight: 800; letter-spacing: .1em; }
            .ai-guide-icon { width: 1.2rem; height: 1.2rem; color: #8b5e3c; }
            .dark .ai-guide-icon { color: #f6d36a; }
            .ai-guide-step h3 { margin: 0; font-size: .88rem; font-weight: 750; }
            .ai-guide-step p { margin: .25rem 0 0; color: var(--ai-muted); font-size: .76rem; line-height: 1.5; }
            .ai-note { display: flex; gap: .6rem; align-items: flex-start; margin-top: .9rem; padding: .75rem .9rem; border: 1px solid rgba(234,179,8,.28); border-radius: .75rem; background: #fffaf0; color: #684b12; }
            .dark .ai-note { background: #302813; color: #f6d36a; }
            .ai-note svg { flex: 0 0 auto; margin-top: .05rem; }
            .ai-note p { margin: 0; font-size: .78rem; line-height: 1.5; }
            .ai-controls-top { align-items: center; }
            .ai-search { display: flex; width: min(100%, 22rem); min-height: 2.8rem; align-items: center; gap: .55rem; padding: 0 .75rem; border: 1px solid #ded5cc; border-radius: .75rem; color: #8b5e3c; background: #fff; }
            .dark .ai-search { border-color: #51463d; background: #151311; color: #f6d36a; }
            .ai-search input { width: 100%; min-width: 0; border: 0; outline: 0; color: #29211c; background: transparent; font-size: .85rem; }
            .dark .ai-search input { color: #f5eee7; }
            .ai-search kbd { display: grid; width: 1.4rem; height: 1.4rem; flex: 0 0 auto; place-items: center; border: 1px solid #e8dfd7; border-radius: .35rem; color: #8a8179; font-size: .72rem; }
            .ai-filter-list { display: flex; gap: .5rem; overflow-x: auto; padding: .9rem 0 .35rem; scrollbar-width: thin; }
            .ai-filter, .ai-vibe { display: inline-flex; min-height: 2.35rem; flex: 0 0 auto; align-items: center; justify-content: center; gap: .4rem; padding: .48rem .8rem; border: 1px solid #e3d8ce; border-radius: 999px; background: #fff; color: #55453a; font-size: .76rem; font-weight: 700; transition: .18s ease; }
            .dark .ai-filter, .dark .ai-vibe { border-color: #51463d; background: #211d19; color: #e7ddd4; }
            .ai-filter:hover, .ai-vibe:hover { border-color: #b9905e; }
            .ai-filter.is-active, .ai-vibe.is-active { border-color: #6f4e37; background: #6f4e37; color: #fff; }
            .dark .ai-filter.is-active, .dark .ai-vibe.is-active { border-color: #c89145; background: #9a6a32; color: #fffaf3; }
            .ai-vibe-row { display: flex; justify-content: space-between; align-items: center; gap: 1rem; margin-top: .8rem; padding-top: .9rem; border-top: 1px solid #eee7e1; }
            .dark .ai-vibe-row { border-color: #3d332b; }
            .ai-vibe-copy { display: flex; align-items: center; gap: .65rem; color: #8b5e3c; }
            .dark .ai-vibe-copy { color: #f6d36a; }
            .ai-vibe-copy div { display: grid; gap: .12rem; }
            .ai-vibe-copy strong { color: #36281e; font-size: .82rem; }
            .dark .ai-vibe-copy strong { color: #f5eee7; }
            .ai-vibe-copy span { color: var(--ai-muted); font-size: .72rem; }
            .ai-vibe-options { display: flex; flex-wrap: wrap; justify-content: end; gap: .4rem; }
            .ai-results-line { display: flex; justify-content: space-between; gap: .8rem; color: var(--ai-muted); font-size: .76rem; }
            .ai-results-line span:first-child { color: #6f4e37; font-weight: 800; }
            .dark .ai-results-line span:first-child { color: #f6d36a; }
            .ai-prompt-grid { display: grid; grid-template-columns: repeat(3, minmax(0, 1fr)); gap: 1rem; align-items: stretch; }
            .ai-prompt-card { display: flex; min-width: 0; flex-direction: column; overflow: hidden; border: 1px solid #e9e1d9; border-radius: 1rem; background: #fff; box-shadow: 0 4px 14px rgba(48,34,22,.045); transition: transform .18s ease, box-shadow .18s ease, border-color .18s ease; }
            .dark .ai-prompt-card { border-color: #3d332b; background: #1c1917; }
            .ai-prompt-card:hover { transform: translateY(-2px); border-color: #d3b88a; box-shadow: 0 12px 25px rgba(48,34,22,.1); }
            .ai-prompt-card[hidden] { display: none; }
            .ai-card-image-wrap { position: relative; display: grid; min-height: 10rem; overflow: hidden; place-items: center; background: linear-gradient(135deg, #f3e7d8, #e6d3bc); }
            .dark .ai-card-image-wrap { background: linear-gradient(135deg, #39271e, #57402e); }
            .ai-card-image { position: absolute; inset: 0; width: 100%; height: 100%; object-fit: cover; }
            .ai-card-image-fallback { display: flex; width: 100%; min-height: 10rem; flex-direction: column; align-items: center; justify-content: center; gap: .5rem; color: #8b5e3c; }
            .ai-card-image-fallback[hidden] { display: none; }
            .ai-card-image-fallback span { font-size: .75rem; font-weight: 700; }
            .ai-upload-action { position: absolute; z-index: 2; top: .7rem; right: .7rem; display: inline-flex; min-height: 2.1rem; align-items: center; gap: .35rem; padding: .4rem .65rem; border: 1px solid rgba(255,255,255,.7); border-radius: 999px; background: rgba(26,15,10,.72); color: #fff; cursor: pointer; font-size: .7rem; font-weight: 700; backdrop-filter: blur(8px); }
            .ai-upload-action:hover { background: #1a0f0a; }
            .ai-image-input { position: absolute; width: 1px; height: 1px; overflow: hidden; clip: rect(0,0,0,0); white-space: nowrap; clip-path: inset(50%); }
            .ai-upload-action:focus-within { outline: 2px solid #eab308; outline-offset: 3px; }
            .ai-category-badge { position: absolute; bottom: .7rem; left: .7rem; max-width: calc(100% - 1.4rem); overflow: hidden; padding: .36rem .55rem; border: 1px solid rgba(255,255,255,.2); border-radius: .45rem; background: rgba(26,15,10,.78); color: #fff; font-size: .61rem; font-weight: 800; letter-spacing: .04em; text-overflow: ellipsis; white-space: nowrap; }
            .ai-card-content { display: flex; flex: 1; flex-direction: column; gap: .7rem; padding: 1rem; }
            .ai-card-content h3 { margin: 0; color: #2c2119; font-size: .98rem; font-weight: 800; line-height: 1.35; }
            .dark .ai-card-content h3 { color: #fff7ee; }
            .ai-prompt-text { max-height: 7.5rem; overflow: auto; padding: .75rem; border: 1px solid #eee7e1; border-radius: .65rem; background: #faf8f5; color: #5e554d; font-size: .76rem; line-height: 1.55; overflow-wrap: anywhere; }
            .dark .ai-prompt-text { border-color: #3d332b; background: #151311; color: #d0c4b8; }
            .ai-prompt-text:focus { outline: 2px solid #d5a545; outline-offset: 2px; }
            .ai-card-actions { display: grid; grid-template-columns: 1fr 1fr; gap: .55rem; padding: 0 1rem 1rem; }
            .ai-copy-button, .ai-open-button { display: inline-flex; min-width: 0; min-height: 2.65rem; align-items: center; justify-content: center; gap: .4rem; padding: .55rem .65rem; border: 1px solid #e5ddd5; border-radius: .65rem; color: #514238; background: #fff; font-size: .74rem; font-weight: 750; text-align: center; text-decoration: none; transition: .18s ease; }
            .dark .ai-copy-button { border-color: #51463d; background: #211d19; color: #eee5dc; }
            .ai-copy-button:hover { border-color: #a68a78; background: #faf7f3; }
            .dark .ai-copy-button:hover { background: #302923; }
            .ai-open-button { border-color: #6f4e37; background: #6f4e37; color: #fff; }
            .ai-open-button:hover { border-color: #513925; background: #513925; color: #fff; }
            .ai-card-status { min-height: .95rem; margin: -.55rem 1rem .7rem; color: #35734d; font-size: .7rem; }
            .dark .ai-card-status { color: #81d6a0; }
            .ai-empty-state { padding: 3rem 1rem; border: 1px dashed #d4c6b8; border-radius: 1rem; color: #8b5e3c; text-align: center; }
            .ai-empty-state[hidden] { display: none; }
            .ai-empty-state svg { margin: 0 auto .5rem; }
            .ai-empty-state h3 { margin: 0; color: inherit; font-size: 1rem; font-weight: 800; }
            .ai-empty-state p { margin: .3rem 0 0; color: var(--ai-muted); font-size: .82rem; }
            @media (max-width: 1024px) {
                .ai-prompt-grid { grid-template-columns: repeat(2, minmax(0, 1fr)); }
                .ai-guide-grid { grid-template-columns: 1fr; }
                .ai-guide-step { grid-template-columns: auto auto 1fr; }
            }
            @media (max-width: 640px) {
                .ai-hero { grid-template-columns: 1fr; gap: .25rem; }
                .ai-hero-mark { position: absolute; top: .8rem; right: .7rem; width: 4.5rem; opacity: .38; }
                .ai-hero-mark > svg { width: 2rem; height: 2rem; }
                .ai-hero-copy { padding-right: 1.8rem; }
                .ai-hero-stats { gap: .4rem; }
                .ai-hero-stats > div { gap: .3rem; padding: .45rem .65rem; }
                .ai-hero-stats span { font-size: .68rem; }
                .ai-section-heading, .ai-controls-top { align-items: stretch; flex-direction: column; }
                .ai-section-heading > p { max-width: none; }
                .ai-search { width: 100%; }
                .ai-vibe-row { align-items: stretch; flex-direction: column; }
                .ai-vibe-options { justify-content: flex-start; }
                .ai-results-line { align-items: flex-start; flex-direction: column; gap: .2rem; }
                .ai-prompt-grid { grid-template-columns: 1fr; }
                .ai-card-image-wrap, .ai-card-image-fallback { min-height: 12rem; }
            }
            @media (prefers-reduced-motion: reduce) {
                .ai-prompt-card, .ai-filter, .ai-vibe, .ai-copy-button, .ai-open-button { transition: none; }
                .ai-prompt-card:hover { transform: none; }
            }
        </style>
    @endonce

    @script
        (() => {
            const root = document.querySelector('.ai-studio');
            if (!root || root.dataset.ready === 'true') return;
            root.dataset.ready = 'true';

            const modifiers = {
                wooden: ' [Visual style: warm natural wood, soft amber cafe lighting, tactile grain, inviting local coffee-shop atmosphere.]',
                industrial: ' [Visual style: raw concrete, dark brushed metal, exposed brick, and moody tungsten cafe lighting.]',
                minimalis: ' [Visual style: clean light surfaces, restrained composition, bright natural daylight, and modern minimal cafe aesthetic.]',
                neon: ' [Visual style: dark evening cafe, matte black surfaces, subtle amber and soft neon accents, modern nocturnal atmosphere.]',
            };
            let activeVibe = 'wooden';
            let activeFilter = 'all';
            const search = root.querySelector('#ai-search-input');
            const cards = [...root.querySelectorAll('.ai-prompt-card')];
            const counter = root.querySelector('#ai-results-count');
            const emptyState = root.querySelector('#ai-empty-state');

            if (!document.documentElement.dataset.aiPromptShortcut) {
                document.documentElement.dataset.aiPromptShortcut = 'true';
                document.addEventListener('keydown', (event) => {
                    if (event.key !== '/' || event.ctrlKey || event.metaKey || event.altKey
                        || ['INPUT', 'TEXTAREA'].includes(document.activeElement.tagName)) return;
                    const activeSearch = document.querySelector('.ai-studio #ai-search-input');
                    if (!activeSearch) return;
                    event.preventDefault();
                    activeSearch.focus();
                });
            }

            const filterCards = () => {
                const term = (search.value || '').trim().toLocaleLowerCase('id');
                let visible = 0;
                cards.forEach((card) => {
                    const categoryMatches = activeFilter === 'all' || card.dataset.category === activeFilter;
                    const searchMatches = !term || card.dataset.search.includes(term);
                    const show = categoryMatches && searchMatches;
                    card.hidden = !show;
                    if (show) visible += 1;
                });
                counter.textContent = `${visible} ide`;
                emptyState.hidden = visible !== 0;
            };

            root.querySelectorAll('.ai-filter').forEach((button) => {
                button.addEventListener('click', () => {
                    activeFilter = button.dataset.filter;
                    root.querySelectorAll('.ai-filter').forEach((item) => {
                        const active = item === button;
                        item.classList.toggle('is-active', active);
                        item.setAttribute('aria-pressed', String(active));
                    });
                    filterCards();
                });
            });
            search.addEventListener('input', filterCards);

            root.querySelectorAll('.ai-vibe').forEach((button) => {
                button.addEventListener('click', () => {
                    activeVibe = button.dataset.vibe;
                    root.querySelectorAll('.ai-vibe').forEach((item) => {
                        const active = item === button;
                        item.classList.toggle('is-active', active);
                        item.setAttribute('aria-pressed', String(active));
                    });
                    root.querySelectorAll('.ai-prompt-text').forEach((prompt) => {
                        prompt.textContent = prompt.dataset.original + (modifiers[activeVibe] || '');
                    });
                });
            });

            root.querySelectorAll('.ai-copy-button').forEach((button) => {
                button.addEventListener('click', async () => {
                    const card = button.closest('.ai-prompt-card');
                    const prompt = card.querySelector('.ai-prompt-text').textContent.trim();
                    const status = card.querySelector('.ai-card-status');
                    try {
                        await navigator.clipboard.writeText(prompt);
                        status.textContent = 'Prompt berhasil disalin.';
                    } catch (error) {
                        const range = document.createRange();
                        range.selectNodeContents(card.querySelector('.ai-prompt-text'));
                        const selection = window.getSelection();
                        selection.removeAllRanges();
                        selection.addRange(range);
                        status.textContent = 'Clipboard tidak tersedia. Teks prompt dipilih; salin secara manual.';
                    }
                    window.setTimeout(() => { status.textContent = ''; }, 4500);
                });
            });

            root.querySelectorAll('.ai-open-button').forEach((button) => {
                button.addEventListener('click', async (event) => {
                    event.preventDefault();
                    const card = button.closest('.ai-prompt-card');
                    const prompt = card.querySelector('.ai-prompt-text').textContent.trim();
                    const status = card.querySelector('.ai-card-status');
                    const geminiUrl = `https://gemini.google.com/app?hl=id&q=${encodeURIComponent(prompt)}`;

                    window.open(geminiUrl, '_blank', 'noopener,noreferrer');

                    try {
                        await navigator.clipboard.writeText(prompt);
                        status.textContent = 'Prompt berhasil disalin dan dibuka di Gemini.';
                    } catch (error) {
                        status.textContent = 'Prompt dibuka di Gemini. Jika prompt belum terisi, salin teks secara manual.';
                    }
                    window.setTimeout(() => { status.textContent = ''; }, 4500);
                });
            });

            root.querySelectorAll('.ai-image-input').forEach((input) => {
                input.addEventListener('change', () => {
                    const file = input.files && input.files[0];
                    const card = input.closest('.ai-prompt-card');
                    const wrap = card.querySelector('.ai-card-image-wrap');
                    const fallback = wrap.querySelector('.ai-card-image-fallback');
                    let preview = wrap.querySelector('.ai-uploaded-preview');
                    const status = card.querySelector('.ai-card-status');
                    if (!file) return;
                    if (!['image/jpeg', 'image/png', 'image/webp'].includes(file.type) || file.size > 5 * 1024 * 1024) {
                        input.value = '';
                        status.textContent = 'Pilih JPG, PNG, atau WebP dengan ukuran maksimal 5 MB.';
                        window.setTimeout(() => { status.textContent = ''; }, 4500);
                        return;
                    }
                    const reader = new FileReader();
                    reader.addEventListener('load', () => {
                        if (!preview) {
                            preview = document.createElement('img');
                            preview.className = 'ai-card-image ai-uploaded-preview';
                            preview.alt = 'Pratinjau foto pilihan Anda';
                            wrap.prepend(preview);
                        }
                        const referenceImage = [...wrap.querySelectorAll('.ai-card-image')]
                            .find((image) => !image.classList.contains('ai-uploaded-preview'));
                        if (referenceImage) referenceImage.remove();
                        preview.src = reader.result;
                        if (fallback) fallback.hidden = true;
                        status.textContent = 'Foto hanya dipratinjau di halaman ini. Unggah manual di alat AI.';
                        window.setTimeout(() => { status.textContent = ''; }, 5000);
                    });
                    reader.addEventListener('error', () => {
                        status.textContent = 'Foto tidak dapat dibaca. Coba pilih berkas lain.';
                    });
                    reader.readAsDataURL(file);
                });
            });
        })();
    @endscript
</x-filament-panels::page>
