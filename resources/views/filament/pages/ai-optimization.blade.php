<x-filament-panels::page>
    @php
        $categories = [
            'FOTO PRODUK & VISUAL' => 'Foto & visual',
            'PROMO & DISKON' => 'Promo',
            'LOGO & BRANDING' => 'Branding',
            'KONTEN VIDEO' => 'Video',
            'VIDEO EDUKASI' => 'Video edukasi',
            'COPYWRITING & CAPTION' => 'Caption',
            'STRATEGI BISNIS' => 'Strategi',
            'STRATEGI PEMASARAN' => 'Pemasaran',
            'STRATEGI HARGA' => 'Harga',
            'PENYUSUNAN MENU' => 'Menu',
        ];
    @endphp

    <div class="ai-studio space-y-6">
        <section class="ai-hero">
            <div class="ai-hero-copy">
                <span class="ai-eyebrow">
                    <x-heroicon-o-sparkles class="h-4 w-4" />
                    Asisten pintar WadahNgopi
                </span>
                <h1>NEAHGO</h1>
                <p class="ai-hero-subtitle">YOUR SMART COFFEE COMPANION</p>
                <p class="ai-hero-tagline">Discover. Order. Promote. Grow.</p>
                <p class="ai-hero-description">Teman pintar WadahNgopi untuk membantu usaha kopi Anda berkembang.</p>
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

        <section class="ai-workspace" aria-labelledby="ai-workspace-title">
            <div class="ai-section-heading">
                <div>
                    <span class="ai-kicker">NEAHGO CHAT</span>
                    <h2 id="ai-workspace-title">Asisten pintar WadahNgopi</h2>
                </div>
                <p>Riwayat percakapan dan foto disimpan di akun Anda sampai percakapan dihapus.</p>
            </div>
            <div class="ai-chat-layout">
                <aside class="ai-chat-history" aria-label="Riwayat percakapan">
                    <div class="ai-history-heading">
                        <h3>Percakapan</h3>
                        <button type="button" class="ai-new-chat" wire:click="clearChat">
                            <x-heroicon-o-plus class="h-4 w-4" />
                            <span>Chat baru</span>
                        </button>
                    </div>
                    @forelse ($conversations as $conversation)
                        <article class="ai-history-item {{ $activeConversationId === $conversation->id ? 'is-active' : '' }}">
                            <button type="button" wire:click="openConversation({{ $conversation->id }})" class="ai-history-open">
                                <span>{{ $conversation->title }}</span>
                                <small>{{ $conversation->updated_at->format('d/m/Y H:i') }}</small>
                            </button>
                            <button
                                type="button"
                                wire:click="deleteConversation({{ $conversation->id }})"
                                wire:confirm="Hapus percakapan beserta foto lampirannya?"
                                class="ai-history-delete"
                                aria-label="Hapus {{ $conversation->title }}"
                            >Hapus</button>
                        </article>
                    @empty
                        <p class="ai-history-empty">Belum ada riwayat.</p>
                    @endforelse
                </aside>

                <div class="ai-chat-main">
                    <header class="ai-chat-topbar">
                        <div class="ai-chat-identity">
                            <span class="ai-chat-avatar"><x-heroicon-o-sparkles class="h-5 w-5" /></span>
                            <span class="ai-chat-identity-copy">
                                <strong>NEAHGO</strong>
                                <small>Asisten pintar WadahNgopi</small>
                            </span>
                        </div>
                        <span class="ai-chat-topbar-note">Discover. Order. Promote. Grow.</span>
                    </header>
                    <div class="ai-chat-tools-panel">
                        <div class="ai-tool-heading">
                            <strong>Mulai dari topik</strong>
                            <span>Pilih salah satu, lalu sesuaikan pertanyaan.</span>
                        </div>
                        <div class="ai-quick-tools" role="group" aria-label="Pilih bantuan AI">
                            @foreach ($quickTools as $tool)
                                <button type="button" class="ai-quick-tool" data-ai-prompt="{{ $tool['prompt'] }}">
                                    <x-heroicon-o-sparkles class="h-4 w-4" />
                                    <span>{{ $tool['title'] }}</span>
                                </button>
                            @endforeach
                        </div>
                    </div>
                    <div class="ai-chat-transcript" aria-live="polite">
                        @forelse ($chatMessages as $message)
                            <article class="ai-chat-message ai-chat-{{ $message['role'] }}">
                                <span class="ai-message-avatar" aria-hidden="true">
                                    @if ($message['role'] === 'assistant')
                                        <x-heroicon-o-sparkles class="h-4 w-4" />
                                    @elseif (auth()->user()?->getFilamentAvatarUrl())
                                        <img src="{{ auth()->user()->getFilamentAvatarUrl() }}" alt="" loading="lazy">
                                    @else
                                        <x-heroicon-o-user class="h-4 w-4" />
                                    @endif
                                </span>
                                <div class="ai-message-bubble">
                                    <strong>{{ $message['role'] === 'assistant' ? 'NEAHGO' : 'Anda' }}</strong>
                                    <p>{{ $message['text'] }}</p>
                                    @if ($message['image_url'])
                                        <img class="ai-chat-message-image" src="{{ $message['image_url'] }}" alt="{{ $message['role'] === 'assistant' ? 'Gambar hasil AI' : 'Foto lampiran' }}" loading="lazy">
                                    @endif
                                </div>
                            </article>
                        @empty
                            @php
                                $greetingName = trim(auth()->user()?->name ?? '');
                            @endphp
                            <div class="ai-chat-welcome">
                                <span class="ai-chat-welcome-icon"><x-heroicon-o-sparkles class="h-6 w-6" /></span>
                                <span class="ai-chat-welcome-label">YOUR SMART COFFEE COMPANION</span>
                                <h3>{{ $greetingName !== '' ? 'Halo, '.$greetingName.'. Apa yang ingin Anda kembangkan?' : 'Halo, apa yang ingin Anda kembangkan?' }}</h3>
                                <p>Tanya NEAHGO tentang pemasaran, caption, harga, menu, atau konten usaha kopi Anda.</p>
                            </div>
                        @endforelse
                        @if ($isChatThinking)
                            <article class="ai-chat-message ai-chat-assistant ai-chat-thinking" aria-live="polite">
                                <span class="ai-message-avatar" aria-hidden="true"><x-heroicon-o-sparkles class="h-4 w-4" /></span>
                                <div class="ai-message-bubble">
                                    <strong>NEAHGO</strong>
                                    <p><span class="ai-thinking-dot"></span> Sedang menyusun jawaban yang dapat diterapkan...</p>
                                </div>
                            </article>
                        @endif
                    </div>

                    @if ($uploadedPhoto)
                        <div class="ai-chat-upload-preview">
                            <img src="{{ $uploadedPhoto->temporaryUrl() }}" alt="Foto yang akan dikirim ke AI">
                            <span>{{ $uploadedPhoto->getClientOriginalName() }}</span>
                            <button type="button" wire:click="$set('uploadedPhoto', null)" aria-label="Hapus foto">Hapus</button>
                        </div>
                    @endif

                    <form class="ai-chat-form" wire:submit="sendChatMessage">
                        <textarea
                            id="ai-chat-input"
                            wire:model="chatDraft"
                            rows="3"
                            maxlength="4000"
                            placeholder="Tulis pertanyaan tentang pemasaran, caption, harga, menu, atau konten..."
                            aria-label="Pesan untuk AI"
                        ></textarea>
                        <p class="ai-chat-hint">Enter untuk mengirim, Shift+Enter untuk baris baru.</p>
                        <div class="ai-chat-actions">
                            <label class="ai-chat-upload">
                                <x-heroicon-o-paper-clip class="h-4 w-4" />
                                <span>Lampirkan foto</span>
                                <input type="file" wire:model="uploadedPhoto" accept="image/jpeg,image/png,image/webp">
                            </label>
                            <span wire:loading wire:target="uploadedPhoto">Mengunggah foto...</span>
                            <button type="submit" class="ai-chat-send" wire:loading.attr="disabled" wire:target="sendChatMessage,uploadedPhoto">
                                <x-heroicon-o-paper-airplane class="h-4 w-4" />
                                <span wire:loading.remove wire:target="sendChatMessage">Kirim</span>
                                <span wire:loading wire:target="sendChatMessage">Mengirim...</span>
                            </button>
                        </div>
                        @error('uploadedPhoto') <p class="ai-response-error">{{ $message }}</p> @enderror
                        @if ($chatError)
                            <p class="ai-response-error">{{ $chatError }}</p>
                        @endif
                        @if ($imageError)
                            <p class="ai-response-error">{{ $imageError }}</p>
                        @endif
                    </form>
                </div>
            </div>
        </section>

        <section class="ai-guide" aria-labelledby="ai-guide-title">
            <div class="ai-section-heading">
                <div>
                    <span class="ai-kicker">MULAI DARI SINI</span>
                    <h2 id="ai-guide-title">Cara menggunakan</h2>
                </div>
                <p>Pilih bantuan AI atau ubah contoh prompt sesuai usaha dan kebutuhan Anda.</p>
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
                        <h3>Coba dan sesuaikan</h3>
                        <p>Isi informasi usaha yang relevan, lalu kirim agar AI memberi saran yang lebih sesuai.</p>
                    </div>
                </article>
                <article class="ai-guide-step">
                    <span class="ai-step-number">03</span>
                    <x-heroicon-o-sparkles class="ai-guide-icon" />
                    <div>
                        <h3>Lanjutkan percakapan</h3>
                        <p>Lanjutkan pertanyaan, minta caption atau naskah video, atau lampirkan foto untuk dianalisis.</p>
                    </div>
                </article>
            </div>
            <div class="ai-note">
                <x-heroicon-o-information-circle class="h-5 w-5 shrink-0" />
                <p>Chat, analisis foto, dan gambar menggunakan Cloudflare Workers AI. Paket gratis menyediakan 10.000 Neurons per hari untuk semua penggunaan AI; jika habis, permintaan berhenti sampai kuota direset. Hindari mengunggah data sensitif.</p>
            </div>
            <div class="ai-note">
                <x-heroicon-o-information-circle class="h-5 w-5 shrink-0" />
                <p>AI dapat menyusun naskah video edukasi. Untuk membuat videonya, buka Gemini dari kartu video; fitur pembuatan video mengikuti ketersediaan akun Gemini Anda.</p>
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
            <span>Ganti pratinjau dengan JPG, PNG, atau WebP hingga 5 MB. Perubahan hanya berlaku untuk akun Anda.</span>
        </div>

        <section class="ai-prompt-grid" id="ai-prompt-grid" aria-label="Koleksi prompt AI">
            @foreach ($prompts as $index => $prompt)
                @php
                    $categorySlug = \Illuminate\Support\Str::slug($prompt['category']);
                    $icon = match ($prompt['category']) {
                        'FOTO PRODUK & VISUAL' => 'photo',
                        'PROMO & DISKON' => 'ticket',
                        'LOGO & BRANDING' => 'fingerprint',
                        'KONTEN VIDEO', 'VIDEO EDUKASI' => 'video-camera',
                        'COPYWRITING & CAPTION' => 'chat-bubble-left-right',
                        default => 'chart-bar',
                    };
                    $isVideoPrompt = in_array($prompt['category'], ['KONTEN VIDEO', 'VIDEO EDUKASI'], true);
                    $canGenerateImage = in_array($prompt['category'], ['FOTO PRODUK & VISUAL', 'PROMO & DISKON', 'LOGO & BRANDING'], true);
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
                            <span>Pratinjau visual belum tersedia</span>
                        </div>
                        <label class="ai-preview-upload ai-preview-upload-overlay">
                            <x-heroicon-o-arrow-up-tray class="h-4 w-4" />
                            <span>Ganti pratinjau</span>
                            <input
                                type="file"
                                accept="image/jpeg,image/png,image/webp"
                                data-preview-prompt-key="{{ $prompt['key'] }}"
                                aria-label="Ganti foto pratinjau: {{ $prompt['title'] }}"
                            >
                        </label>
                        <span class="ai-category-badge">{{ $prompt['category'] }}</span>
                    </div>

                    @if ($prompt['has_custom_image'])
                        <div class="ai-preview-tools">
                            <button
                                type="button"
                                class="ai-preview-reset"
                                data-preview-prompt-key="{{ $prompt['key'] }}"
                            >Pakai gambar bawaan</button>
                        </div>
                    @endif
                    @if ($previewError && $previewErrorForKey === $prompt['key'])
                        <p class="ai-response-error ai-preview-error">{{ $previewError }}</p>
                    @endif
                    @error('previewPhoto')
                        @if ($previewErrorForKey === $prompt['key'])
                            <p class="ai-response-error ai-preview-error">{{ $message }}</p>
                        @endif
                    @enderror

                    <div class="ai-card-content">
                        <h3>{{ $prompt['title'] }}</h3>
                        <div
                            class="ai-prompt-text"
                            data-original="{{ $prompt['prompt_text'] }}"
                            tabindex="0"
                            contenteditable="true"
                            aria-label="Teks prompt: {{ $prompt['title'] }}"
                        >{{ $prompt['prompt_text'] }}</div>
                    </div>

                    <div class="ai-card-actions">
                        <button type="button" class="ai-copy-button">
                            <x-heroicon-o-clipboard-document class="h-4 w-4" />
                            <span>Salin prompt</span>
                        </button>
                        <div class="ai-card-primary-actions {{ ! $isVideoPrompt && ! $canGenerateImage ? 'is-single' : '' }}">
                            <button type="button" class="ai-card-chat-button">
                                <x-heroicon-o-chat-bubble-left-right class="h-4 w-4" />
                                <span>Gunakan di chat</span>
                            </button>
                            @if ($isVideoPrompt)
                                <button type="button" class="ai-gemini-video-button">
                                    <x-heroicon-o-video-camera class="h-4 w-4" />
                                    <span>Buat video di Gemini</span>
                                </button>
                            @elseif ($canGenerateImage)
                                <button type="button" class="ai-image-button">
                                    <x-heroicon-o-photo class="h-4 w-4" />
                                    <span>Buat gambar</span>
                                </button>
                            @endif
                        </div>
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
                font-family: var(--font-family-sans, 'Plus Jakarta Sans', 'Outfit', sans-serif);
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
            .ai-hero h1 { margin: .7rem 0 .35rem; max-width: 45rem; color: #fffaf3; font-size: clamp(2.4rem, 5vw, 3.8rem); font-weight: 900; letter-spacing: .035em; line-height: 1; }
            .ai-hero-copy p { margin: 0; max-width: 42rem; color: rgba(255,250,243,.78); font-size: clamp(.9rem, 1.4vw, 1.05rem); line-height: 1.7; }
            .ai-hero-copy .ai-hero-subtitle { color: #f6d36a; font-size: .73rem; font-weight: 800; letter-spacing: .2em; line-height: 1.4; }
            .ai-hero-copy .ai-hero-tagline { margin-top: .65rem; color: #fffaf3; font-size: clamp(1rem, 1.7vw, 1.2rem); font-weight: 750; letter-spacing: .015em; }
            .ai-hero-copy .ai-hero-description { margin-top: .25rem; font-size: .9rem; }
            .ai-hero-mark { position: relative; display: grid; width: clamp(6.5rem, 13vw, 10rem); aspect-ratio: 1; place-items: center; color: #f6d36a; }
            .ai-hero-mark > svg { z-index: 1; width: 3.5rem; height: 3.5rem; }
            .ai-orbit { position: absolute; inset: 9%; border: 1px solid rgba(246,211,106,.35); border-radius: 40% 60% 55% 45%; transform: rotate(25deg); }
            .ai-orbit-two { inset: 0; transform: rotate(-35deg); border-color: rgba(246,211,106,.22); }
            .ai-hero-stats { grid-column: 1 / -1; display: flex; flex-wrap: wrap; gap: .65rem; }
            .ai-hero-stats > div { display: flex; align-items: baseline; gap: .45rem; padding: .55rem .85rem; border: 1px solid rgba(255,255,255,.15); border-radius: 999px; background: rgba(255,255,255,.07); }
            .ai-hero-stats strong { color: #f6d36a; font-size: 1rem; }
            .ai-hero-stats span { color: rgba(255,250,243,.78); font-size: .78rem; }
            .ai-guide, .ai-controls, .ai-workspace { padding: clamp(1rem, 2.4vw, 1.6rem); border: 1px solid #e8dfd7; border-radius: 1.1rem; background: #fff; }
            .dark .ai-guide, .dark .ai-controls, .dark .ai-workspace { border-color: #3d332b; background: #1c1917; }
            .ai-workspace { padding: clamp(1rem, 2.2vw, 1.5rem); box-shadow: 0 12px 30px rgba(48,34,22,.055); }
            .dark .ai-workspace { box-shadow: 0 14px 32px rgba(0,0,0,.18); }
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
            .ai-preview-tools { display: flex; flex-wrap: wrap; align-items: center; gap: .45rem; padding: .6rem .8rem 0; }
            .ai-preview-upload, .ai-preview-reset { display: inline-flex; min-height: 2rem; align-items: center; justify-content: center; gap: .35rem; padding: .4rem .6rem; border: 1px solid #e3d8ce; border-radius: .55rem; background: #fff; color: #55453a; font-size: .68rem; font-weight: 700; cursor: pointer; }
            .dark .ai-preview-upload, .dark .ai-preview-reset { border-color: #51463d; background: #211d19; color: #eee5dc; }
            .ai-preview-upload input { position: absolute; width: 1px; height: 1px; overflow: hidden; clip: rect(0,0,0,0); }
            .ai-preview-upload:focus-within, .ai-preview-reset:focus-visible { outline: 2px solid #d5a545; outline-offset: 2px; }
            .ai-preview-upload-overlay { position: absolute; top: .7rem; right: .7rem; z-index: 2; border-color: rgba(255,255,255,.25); background: rgba(26,15,10,.84); color: #fffaf3; }
            .ai-preview-upload-overlay:hover { background: rgba(26,15,10,.96); }
            .ai-preview-error { padding: .35rem .8rem 0; }
            .ai-category-badge { position: absolute; bottom: .7rem; left: .7rem; z-index: 2; max-width: calc(100% - 1.4rem); overflow: hidden; padding: .36rem .55rem; border: 1px solid rgba(255,255,255,.2); border-radius: .45rem; background: rgba(26,15,10,.78); color: #fff; font-size: .61rem; font-weight: 800; letter-spacing: .04em; text-overflow: ellipsis; white-space: nowrap; }
            .ai-card-content { display: flex; flex: 1; flex-direction: column; gap: .7rem; padding: 1rem; }
            .ai-card-content h3 { margin: 0; color: #2c2119; font-size: .98rem; font-weight: 800; line-height: 1.35; }
            .dark .ai-card-content h3 { color: #fff7ee; }
            .ai-prompt-text { max-height: 7.5rem; overflow: auto; padding: .75rem; border: 1px solid #eee7e1; border-radius: .65rem; background: #faf8f5; color: #5e554d; font-size: .76rem; line-height: 1.55; overflow-wrap: anywhere; }
            .dark .ai-prompt-text { border-color: #3d332b; background: #151311; color: #d0c4b8; }
            .ai-prompt-text:focus { outline: 2px solid #d5a545; outline-offset: 2px; }
            .ai-card-actions { display: grid; gap: .55rem; padding: 0 1rem 1rem; }
            .ai-copy-button { display: inline-flex; min-width: 0; min-height: 2.65rem; align-items: center; justify-content: center; gap: .4rem; padding: .55rem .65rem; border: 1px solid #e5ddd5; border-radius: .65rem; color: #514238; background: #fff; font-size: .74rem; font-weight: 750; text-align: center; text-decoration: none; transition: .18s ease; }
            .dark .ai-copy-button { border-color: #51463d; background: #211d19; color: #eee5dc; }
            .ai-copy-button:hover { border-color: #a68a78; background: #faf7f3; }
            .dark .ai-copy-button:hover { background: #302923; }
            .ai-card-primary-actions { display: grid; grid-template-columns: repeat(2, minmax(0, 1fr)); gap: .45rem; }
            .ai-card-primary-actions.is-single { grid-template-columns: 1fr; }
            .ai-card-chat-button, .ai-image-button, .ai-gemini-video-button { display: inline-flex; min-width: 0; min-height: 2.65rem; align-items: center; justify-content: center; gap: .4rem; padding: .55rem .65rem; border: 1px solid #d5a545; border-radius: .65rem; color: #4a321d; background: #fff8e6; font-size: .74rem; font-weight: 750; text-align: center; cursor: pointer; transition: .18s ease; }
            .dark .ai-card-chat-button, .dark .ai-image-button, .dark .ai-gemini-video-button { border-color: #8c6b31; color: #ffe7a3; background: #302813; }
            .ai-card-chat-button:hover, .ai-image-button:hover, .ai-gemini-video-button:hover { border-color: #a77719; background: #fff0c2; }
            .ai-card-chat-button:disabled, .ai-image-button:disabled, .ai-gemini-video-button:disabled { cursor: wait; opacity: .65; }
            .ai-card-status { min-height: .95rem; margin: -.55rem 1rem .7rem; color: #35734d; font-size: .7rem; }
            .dark .ai-card-status { color: #81d6a0; }
            .ai-response-error { margin: 0; color: #b42318; font-size: .9rem; }
            .ai-tool-heading { display: flex; flex-wrap: wrap; align-items: baseline; gap: .35rem .5rem; }
            .ai-tool-heading strong { color: #36281e; font-size: .82rem; font-weight: 800; }
            .dark .ai-tool-heading strong { color: #f5eee7; }
            .ai-tool-heading span { color: var(--ai-muted); font-size: .72rem; }
            .ai-chat-layout { display: grid; grid-template-columns: minmax(13rem, 15rem) minmax(0, 1fr); gap: .9rem; margin-top: 1rem; align-items: stretch; }
            .ai-chat-history { display: flex; min-width: 0; max-height: 40rem; flex-direction: column; gap: .4rem; overflow: auto; padding: .85rem; border: 1px solid #eee7e1; border-radius: .9rem; background: #faf8f5; }
            .dark .ai-chat-history { border-color: #3d332b; background: #171411; }
            .ai-history-heading { display: flex; align-items: center; justify-content: space-between; gap: .5rem; margin-bottom: .35rem; }
            .ai-chat-history h3 { margin: 0; color: #514238; font-size: .74rem; font-weight: 800; letter-spacing: .07em; text-transform: uppercase; }
            .dark .ai-chat-history h3 { color: #c0aa95; }
            .ai-new-chat { display: inline-flex; min-height: 2.25rem; align-items: center; justify-content: center; gap: .3rem; padding: .45rem .6rem; border: 1px solid #6f4e37; border-radius: .6rem; background: #6f4e37; color: #fff; font-size: .7rem; font-weight: 750; white-space: nowrap; transition: .18s ease; }
            .ai-new-chat:hover { border-color: #8b6343; background: #8b6343; }
            .ai-chat-main { display: flex; min-width: 0; flex-direction: column; overflow: hidden; padding: .8rem; border: 1px solid #e8dfd7; border-radius: 1rem; background: linear-gradient(180deg, #fffdfa, #fff); }
            .dark .ai-chat-main { border-color: #3d332b; background: linear-gradient(180deg, #211d19, #1c1917); }
            .ai-chat-topbar { display: flex; min-width: 0; align-items: center; justify-content: space-between; gap: .75rem; padding: .2rem .25rem .75rem; border-bottom: 1px solid #eee7e1; }
            .dark .ai-chat-topbar { border-color: #3d332b; }
            .ai-chat-identity { display: flex; min-width: 0; align-items: center; gap: .65rem; }
            .ai-chat-avatar { display: grid; width: 2.5rem; height: 2.5rem; flex: 0 0 auto; place-items: center; border: 1px solid #ead8b8; border-radius: .8rem; background: #fff5dd; color: #8b5e3c; }
            .dark .ai-chat-avatar { border-color: #594635; background: #302813; color: #e9b65b; }
            .ai-chat-identity-copy { display: grid; min-width: 0; gap: .1rem; }
            .ai-chat-identity-copy strong { color: #36281e; font-size: .84rem; font-weight: 900; letter-spacing: .06em; }
            .dark .ai-chat-identity-copy strong { color: #fff7ee; }
            .ai-chat-identity-copy small { color: var(--ai-muted); font-size: .68rem; }
            .ai-chat-topbar-note { color: #8b5e3c; font-size: .7rem; font-weight: 750; text-align: right; }
            .dark .ai-chat-topbar-note { color: #e9b65b; }
            .ai-chat-tools-panel { margin: .7rem 0; padding: .65rem .7rem .7rem; border: 1px solid #eee7e1; border-radius: .8rem; background: #faf8f5; }
            .dark .ai-chat-tools-panel { border-color: #3d332b; background: #171411; }
            .ai-quick-tools { display: flex; gap: .45rem; overflow-x: auto; margin-top: .5rem; padding: 0 0 .15rem; scrollbar-width: thin; }
            .ai-quick-tool { display: inline-flex; min-width: max-content; min-height: 2.35rem; align-items: center; justify-content: center; gap: .35rem; padding: .45rem .6rem; border: 1px solid #e3d8ce; border-radius: 999px; background: #fff; color: #5c4028; font-size: .69rem; font-weight: 750; white-space: nowrap; transition: .18s ease; }
            .ai-quick-tool svg { flex: 0 0 auto; color: #a77719; }
            .dark .ai-quick-tool { border-color: #594635; background: #211d19; color: #ffe7a3; }
            .dark .ai-quick-tool svg { color: #e9b65b; }
            .ai-quick-tool:hover { border-color: #b9905e; background: #fff0c2; transform: translateY(-1px); }
            .dark .ai-quick-tool:hover { background: #3d321a; }
            .ai-quick-tool:focus-visible, .ai-new-chat:focus-visible, .ai-chat-history button:focus-visible, .ai-chat-send:focus-visible, .ai-chat-upload:focus-within { outline: 2px solid #d5a545; outline-offset: 2px; }
            .ai-history-item { display: grid; grid-template-columns: minmax(0, 1fr) auto; align-items: center; gap: .3rem; padding: .35rem; border: 1px solid transparent; border-radius: .65rem; transition: .16s ease; }
            .ai-history-item:hover { border-color: #e8dfd7; background: #fff; }
            .ai-history-item.is-active { border-color: #ead9bd; background: #fff8e6; }
            .dark .ai-history-item:hover { border-color: #44372c; background: #211d19; }
            .dark .ai-history-item.is-active { border-color: #594635; background: #302813; }
            .ai-history-open { display: grid; min-width: 0; gap: .2rem; padding: .25rem; text-align: left; }
            .ai-history-open span { overflow: hidden; color: #514238; font-size: .74rem; font-weight: 700; text-overflow: ellipsis; white-space: nowrap; }
            .dark .ai-history-open span { color: #eee5dc; }
            .ai-history-open small { color: var(--ai-muted); font-size: .65rem; }
            .ai-history-delete { padding: .3rem; border-radius: .4rem; color: #b42318; font-size: .66rem; }
            .ai-history-delete:hover { background: #fff0ed; }
            .ai-history-empty { margin: .2rem; color: var(--ai-muted); font-size: .74rem; }
            .ai-chat-transcript { display: grid; min-height: 21rem; max-height: 32rem; align-content: start; gap: 1rem; overflow: auto; margin: 0 0 .8rem; padding: 1.15rem; border: 1px solid #eee7e1; border-radius: 1rem; background: linear-gradient(145deg, #fcfaf7, #f8f4ef); scrollbar-color: #c9b7a5 transparent; }
            .dark .ai-chat-transcript { border-color: #3d332b; background: #171411; scrollbar-color: #594635 transparent; }
            .ai-chat-message { display: grid; width: min(100%, 54rem); max-width: 100%; grid-template-columns: 2.15rem minmax(0, 1fr); align-items: end; gap: .55rem; justify-self: start; }
            .ai-message-avatar { display: grid; width: 2.05rem; height: 2.05rem; flex: 0 0 auto; place-items: center; border: 1px solid #ead8b8; border-radius: 999px; background: #fff5dd; color: #8b5e3c; box-shadow: 0 2px 7px rgba(48,34,22,.08); }
            .ai-message-avatar img { width: 100%; height: 100%; border-radius: inherit; object-fit: cover; }
            .dark .ai-message-avatar { border-color: #594635; background: #302813; color: #e9b65b; }
            .ai-message-bubble { width: fit-content; max-width: min(100%, 44rem); padding: .7rem .9rem; border: 1px solid #eee7e1; border-radius: 1rem 1rem 1rem .35rem; background: #fff; color: #29211c; box-shadow: 0 3px 12px rgba(48,34,22,.055); }
            .dark .ai-message-bubble { border-color: #44372c; background: #211d19; color: #f5eee7; }
            .ai-message-bubble strong { display: block; margin-bottom: .25rem; color: #8b5e3c; font-size: .68rem; font-weight: 800; letter-spacing: .04em; }
            .dark .ai-message-bubble strong { color: #e9b65b; }
            .ai-message-bubble p { margin: 0; white-space: pre-wrap; overflow-wrap: anywhere; font-size: .84rem; line-height: 1.65; }
            .ai-chat-message-image { display: block; max-width: min(100%, 28rem); max-height: 28rem; margin-top: .6rem; border-radius: .65rem; object-fit: contain; }
            .ai-chat-message-video { display: block; width: min(100%, 36rem); max-height: 28rem; margin-top: .6rem; border-radius: .65rem; background: #000; }
            .ai-chat-user { grid-template-columns: minmax(0, 1fr) 2.15rem; justify-self: end; }
            .ai-chat-user .ai-message-avatar { grid-column: 2; grid-row: 1; border-color: #8b6343; background: #6f4e37; color: #fffaf3; }
            .dark .ai-chat-user .ai-message-avatar { border-color: #a77719; background: #6f4e37; color: #fffaf3; }
            .ai-chat-user .ai-message-bubble { grid-column: 1; grid-row: 1; justify-self: end; border-color: #6f4e37; border-radius: 1rem 1rem .35rem 1rem; background: linear-gradient(135deg, #6f4e37, #8b6343); color: #fffaf3; }
            .ai-chat-user .ai-message-bubble strong { color: #f6d36a; }
            .ai-chat-assistant .ai-message-bubble { grid-column: 2; grid-row: 1; }
            .ai-chat-thinking .ai-message-bubble { background: #fffaf0; }
            .dark .ai-chat-thinking .ai-message-bubble { background: #302813; }
            .ai-chat-thinking p { display: flex; align-items: center; gap: .5rem; color: var(--ai-muted); }
            .ai-thinking-dot { width: .55rem; height: .55rem; border-radius: 999px; background: #d5a545; animation: ai-pulse 1.1s ease-in-out infinite; }
            @keyframes ai-pulse { 0%, 100% { opacity: .35; transform: scale(.8); } 50% { opacity: 1; transform: scale(1); } }
            .ai-chat-welcome { display: grid; align-self: center; justify-items: center; max-width: 34rem; margin: auto; padding: 1.4rem; text-align: center; }
            .ai-chat-welcome-icon { display: grid; width: 3.2rem; height: 3.2rem; place-items: center; margin-bottom: .75rem; border: 1px solid #ead8b8; border-radius: 1rem; background: #fff5dd; color: #8b5e3c; }
            .dark .ai-chat-welcome-icon { border-color: #594635; background: #302813; color: #e9b65b; }
            .ai-chat-welcome-label { color: #9a6a32; font-size: .62rem; font-weight: 850; letter-spacing: .16em; }
            .dark .ai-chat-welcome-label { color: #e9b65b; }
            .ai-chat-welcome h3 { margin: .45rem 0 .3rem; color: #36281e; font-size: clamp(1.05rem, 2vw, 1.35rem); font-weight: 850; letter-spacing: -.02em; }
            .dark .ai-chat-welcome h3 { color: #fff7ee; }
            .ai-chat-welcome p { max-width: 29rem; margin: 0; color: var(--ai-muted); font-size: .8rem; line-height: 1.6; }
            .ai-chat-form { display: grid; gap: .65rem; padding: .75rem; border: 1px solid #eee7e1; border-radius: .8rem; background: #fff; }
            .dark .ai-chat-form { border-color: #3d332b; background: #1c1917; }
            .ai-chat-form textarea { width: 100%; min-height: 5.2rem; padding: .8rem .85rem; border: 1px solid #ded5cc; border-radius: .65rem; background: #faf8f5; color: #29211c; font-size: .85rem; line-height: 1.55; resize: vertical; transition: border-color .16s ease, box-shadow .16s ease; }
            .ai-chat-form textarea:focus { border-color: #b9905e; outline: 0; box-shadow: 0 0 0 3px rgba(185,144,94,.15); }
            .dark .ai-chat-form textarea { border-color: #51463d; background: #151311; color: #f5eee7; }
            .dark .ai-chat-form textarea:focus { border-color: #b9905e; }
            .ai-chat-hint { margin: -.35rem 0 0 .1rem; color: var(--ai-muted); font-size: .67rem; }
            .ai-chat-actions { display: flex; flex-wrap: wrap; align-items: center; justify-content: space-between; gap: .5rem; }
            .ai-chat-upload, .ai-chat-send { display: inline-flex; min-height: 2.5rem; align-items: center; justify-content: center; gap: .4rem; padding: .5rem .75rem; border: 1px solid #e3d8ce; border-radius: .65rem; color: #514238; background: #fff; font-size: .76rem; font-weight: 750; cursor: pointer; text-decoration: none; }
            .dark .ai-chat-upload { border-color: #51463d; background: #211d19; color: #eee5dc; }
            .ai-chat-upload input { position: absolute; width: 1px; height: 1px; overflow: hidden; clip: rect(0,0,0,0); }
            .ai-chat-send { border-color: #6f4e37; background: #6f4e37; color: #fff; transition: .18s ease; }
            .ai-chat-send:hover { border-color: #8b6343; background: #8b6343; transform: translateY(-1px); }
            .ai-chat-send:disabled { cursor: wait; opacity: .65; }
            .ai-chat-upload-preview { display: flex; min-width: 0; align-items: center; gap: .65rem; margin: 0 0 .65rem; padding: .55rem; border: 1px solid #eee7e1; border-radius: .7rem; background: #faf8f5; }
            .dark .ai-chat-upload-preview { border-color: #3d332b; background: #171411; }
            .ai-chat-upload-preview img { width: 3rem; height: 3rem; border-radius: .5rem; object-fit: cover; }
            .ai-chat-upload-preview span { min-width: 0; overflow: hidden; color: var(--ai-muted); font-size: .75rem; text-overflow: ellipsis; white-space: nowrap; }
            .ai-chat-upload-preview button { color: #b42318; font-size: .75rem; }
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
                .ai-chat-layout { grid-template-columns: 1fr; }
                .ai-chat-history { max-height: 12rem; overflow: auto; }
                .ai-chat-main { padding: .6rem; }
                .ai-chat-transcript { min-height: 18rem; max-height: 26rem; padding: .7rem; }
                .ai-chat-transcript { gap: .8rem; padding: .75rem; }
                .ai-chat-message { width: 100%; grid-template-columns: 1.9rem minmax(0, 1fr); gap: .4rem; }
                .ai-chat-user { grid-template-columns: minmax(0, 1fr) 1.9rem; }
                .ai-message-avatar { width: 1.85rem; height: 1.85rem; }
                .ai-message-bubble { max-width: 100%; padding: .65rem .75rem; }
                .ai-chat-topbar-note { display: none; }
                .ai-chat-actions { align-items: stretch; }
                .ai-chat-send { margin-left: auto; }
                .ai-vibe-row { align-items: stretch; flex-direction: column; }
                .ai-vibe-options { justify-content: flex-start; }
                .ai-results-line { align-items: flex-start; flex-direction: column; gap: .2rem; }
                .ai-prompt-grid { grid-template-columns: 1fr; }
                .ai-card-image-wrap, .ai-card-image-fallback { min-height: 12rem; }
            }
            @media (prefers-reduced-motion: reduce) {
                .ai-prompt-card, .ai-filter, .ai-vibe, .ai-copy-button, .ai-card-chat-button, .ai-image-button, .ai-gemini-video-button, .ai-quick-tool { transition: none; }
                .ai-prompt-card:hover { transform: none; }
            }
        </style>
    @endonce

    @script
        <script>
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
            const chatInput = root.querySelector('#ai-chat-input');

            chatInput?.addEventListener('keydown', (event) => {
                if (event.key !== 'Enter' || event.shiftKey || event.isComposing || event.repeat) return;

                event.preventDefault();
                const form = chatInput.closest('form');
                const submitButton = form?.querySelector('button[type="submit"]');

                if (form && !submitButton?.disabled) {
                    form.requestSubmit();
                }
            });

            const updateSearchIndex = (card) => {
                card.dataset.search = [
                    card.dataset.category,
                    card.querySelector('.ai-card-content h3').textContent,
                    card.querySelector('.ai-prompt-text').textContent,
                ].join(' ').toLocaleLowerCase('id');
            };
            const stripVibe = (text) => Object.values(modifiers).reduce(
                (value, modifier) => value.endsWith(modifier) ? value.slice(0, -modifier.length).trimEnd() : value,
                text.trim(),
            );

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

            cards.forEach((card) => {
                const prompt = card.querySelector('.ai-prompt-text');
                prompt.addEventListener('input', () => {
                    prompt.dataset.original = stripVibe(prompt.textContent);
                    updateSearchIndex(card);
                    filterCards();
                });
            });

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

            root.querySelectorAll('.ai-quick-tool').forEach((button) => {
                button.addEventListener('click', async () => {
                    await $wire.set('chatDraft', button.dataset.aiPrompt);
                    root.querySelector('#ai-chat-input').focus();
                    root.querySelector('.ai-workspace').scrollIntoView({ behavior: 'smooth', block: 'start' });
                });
            });

            root.querySelectorAll('.ai-vibe').forEach((button) => {
                button.addEventListener('click', () => {
                    activeVibe = button.dataset.vibe;
                    root.querySelectorAll('.ai-vibe').forEach((item) => {
                        const active = item === button;
                        item.classList.toggle('is-active', active);
                        item.setAttribute('aria-pressed', String(active));
                    });
                    root.querySelectorAll('.ai-prompt-text').forEach((prompt) => {
                        prompt.dataset.original = stripVibe(prompt.textContent);
                        prompt.textContent = prompt.dataset.original + (modifiers[activeVibe] || '');
                        updateSearchIndex(prompt.closest('.ai-prompt-card'));
                    });
                    filterCards();
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

            root.querySelectorAll('.ai-card-chat-button').forEach((button) => {
                button.addEventListener('click', async () => {
                    const card = button.closest('.ai-prompt-card');
                    const prompt = card.querySelector('.ai-prompt-text').textContent.trim();
                    const status = card.querySelector('.ai-card-status');
                    await $wire.set('chatDraft', prompt);
                    status.textContent = 'Prompt ditambahkan ke chat. Edit jika perlu, lalu tekan Kirim ke AI.';
                    root.querySelector('#ai-chat-input').focus();
                    root.querySelector('.ai-workspace').scrollIntoView({ behavior: 'smooth', block: 'start' });
                    window.setTimeout(() => { status.textContent = ''; }, 5000);
                });
            });

            root.addEventListener('change', (event) => {
                const input = event.target;
                if (!input.matches || !input.matches('.ai-preview-upload input')) return;

                const file = input.files[0];
                if (!file) return;

                const card = input.closest('.ai-prompt-card');
                const status = card.querySelector('.ai-card-status');
                const promptKey = input.dataset.previewPromptKey;
                status.textContent = 'Mengunggah foto...';

                $wire.upload('previewPhoto', file, () => {
                    $wire.savePromptPreview(promptKey)
                        .then((saved) => {
                            status.textContent = saved
                                ? 'Foto pratinjau berhasil diganti.'
                                : 'Foto pratinjau gagal disimpan. Lihat pesan pada kartu.';
                        })
                        .catch(() => {
                            status.textContent = 'Foto pratinjau gagal disimpan. Coba lagi.';
                        })
                        .finally(() => { input.value = ''; });
                }, () => {
                    status.textContent = 'Foto gagal diunggah. Gunakan JPG, PNG, atau WebP maksimal 5 MB.';
                    input.value = '';
                });
            });

            root.addEventListener('click', (event) => {
                const button = event.target.closest('.ai-preview-reset');
                if (!button) return;

                const card = button.closest('.ai-prompt-card');
                const status = card.querySelector('.ai-card-status');
                status.textContent = 'Mengembalikan gambar bawaan...';

                $wire.resetPromptPreview(button.dataset.previewPromptKey)
                    .then((reset) => {
                        status.textContent = reset
                            ? 'Gambar bawaan berhasil dipulihkan.'
                            : 'Gambar bawaan dipulihkan, tetapi foto lama belum terhapus.';
                    })
                    .catch(() => {
                        status.textContent = 'Gambar bawaan gagal dipulihkan. Coba lagi.';
                    });
            });

            root.querySelectorAll('.ai-image-button').forEach((button) => {
                button.addEventListener('click', async () => {
                    const card = button.closest('.ai-prompt-card');
                    const prompt = card.querySelector('.ai-prompt-text').textContent.trim();
                    const status = card.querySelector('.ai-card-status');
                    const label = button.querySelector('span');

                    button.disabled = true;
                    label.textContent = 'Membuat gambar...';
                    status.textContent = 'AI sedang membuat visual. Proses ini dapat memerlukan waktu.';

                    try {
                        await $wire.generateImage(prompt);
                        root.querySelector('.ai-chat-transcript').scrollIntoView({ behavior: 'smooth', block: 'center' });
                    } catch (error) {
                        status.textContent = 'Pembuatan gambar gagal. Coba lagi.';
                    } finally {
                        button.disabled = false;
                        label.textContent = 'Buat gambar';
                    }
                });
            });

            root.querySelectorAll('.ai-gemini-video-button').forEach((button) => {
                button.addEventListener('click', async () => {
                    const card = button.closest('.ai-prompt-card');
                    const prompt = card.querySelector('.ai-prompt-text').textContent.trim();
                    const status = card.querySelector('.ai-card-status');

                    window.open('https://gemini.google.com/app', '_blank', 'noopener,noreferrer');

                    try {
                        await navigator.clipboard.writeText(prompt);
                        status.textContent = 'Prompt disalin. Tempelkan di Gemini untuk membuat video.';
                    } catch (error) {
                        const range = document.createRange();
                        range.selectNodeContents(card.querySelector('.ai-prompt-text'));
                        const selection = window.getSelection();
                        selection.removeAllRanges();
                        selection.addRange(range);
                        status.textContent = 'Gemini sudah dibuka. Clipboard tidak tersedia; salin prompt yang dipilih secara manual.';
                    }

                    window.setTimeout(() => { status.textContent = ''; }, 6000);
                });
            });

        })();
        </script>
    @endscript
</x-filament-panels::page>
