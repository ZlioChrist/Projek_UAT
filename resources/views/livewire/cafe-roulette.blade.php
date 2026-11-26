{{-- Cafe Roulette — "Bingung? Putar Aja!" --}}
<div x-data="cafeRoulette(@this)">

    {{-- Floating Action Button --}}
    <button class="roulette-fab" id="roulette-fab-btn" type="button" aria-label="Bingung memilih kafe? Buka pencarian suara atau pemilih kafe acak"
        title="Bantu pilihkan kafe" x-show="!$wire.isOpen" wire:click="openModal" @click="activeMode = 'random'"
        :class="{ 'is-dragging': fabDragging }" :style="fabStyle"
        @pointerdown="startFabDrag($event)" @pointermove="moveFab($event)"
        @pointerup="endFabDrag($event)" @pointercancel="endFabDrag($event)"
        @click.capture="if (suppressFabClick) { $event.preventDefault(); $event.stopImmediatePropagation(); suppressFabClick = false; }"
        x-transition:enter="transition ease-out duration-600"
        x-transition:enter-start="opacity-0 scale-95"
        x-transition:enter-end="opacity-100 scale-100">
        <i class="ph-bold ph-shuffle"></i>
        <span>Bingung?</span>
    </button>

    {{-- Modal Overlay --}}
    <template x-teleport="body">
        <div class="roulette-overlay" x-show="$wire.isOpen" x-cloak
            x-transition:enter="transition ease-out duration-300" x-transition:enter-start="opacity-0"
            x-transition:enter-end="opacity-100" x-transition:leave="transition ease-in duration-200"
            x-transition:leave-start="opacity-100" x-transition:leave-end="opacity-0" @click.self="closeModal()">

            {{-- Modal Card --}}
            <div class="roulette-modal" :class="{ 'roulette-modal--voice': activeMode === 'voice' }" x-show="$wire.isOpen" x-transition:enter="transition ease-out duration-400"
                x-transition:enter-start="opacity-0 scale-90 translate-y-8"
                x-transition:enter-end="opacity-100 scale-100 translate-y-0"
                x-transition:leave="transition ease-in duration-200" x-transition:leave-start="opacity-100 scale-100"
                x-transition:leave-end="opacity-0 scale-95" @click.stop>

                {{-- Close --}}
                <button class="roulette-close" @click="closeModal()">
                    <i class="ph-bold ph-x"></i>
                </button>

                {{-- Header --}}
                <div class="roulette-header">
                    <div class="roulette-emoji"
                        style="width:52px;height:52px;border-radius:16px;background:linear-gradient(135deg,#F59E0B,#D97706);display:flex;align-items:center;justify-content:center;margin:0 auto;">
                        <i class="ph-fill ph-coffee text-2xl text-white"></i>
                    </div>
                    <h2 class="roulette-title">Bingung Mau Ngopi Dimana?</h2>
                <p class="roulette-subtitle">Cari dengan suara sesuai fasilitas atau biarkan kami memilih secara acak.</p>
                </div>

            <div class="roulette-mode-tabs" role="tablist" aria-label="Cara memilih kafe">
                <button type="button" role="tab" :aria-selected="activeMode === 'random'" :class="{ 'is-active': activeMode === 'random' }" @click="activeMode = 'random'">
                    <i class="ph-bold ph-shuffle"></i> Pilih acak
                </button>
                <button type="button" role="tab" :aria-selected="activeMode === 'voice'" :class="{ 'is-active': activeMode === 'voice' }" @click="activeMode = 'voice'">
                    <i class="ph-bold ph-microphone"></i> Cari dengan suara
                </button>
            </div>

            <div x-show="activeMode === 'random'">
                {{-- Stage --}}
                <div class="roulette-stage" id="roulette-stage">
                    {{-- Cards will be injected by Alpine --}}
                    <template x-if="!spinning && !winnerData && candidates.length === 0">
                        <div class="roulette-empty">
                            <span class="roulette-empty-icon"></span>
                            <p class="roulette-empty-text">Tap tombol di bawah untuk mulai spin!</p>
                        </div>
                    </template>

                    {{-- Spinning cards (Alpine renders) --}}
                    <template x-for="(cafe, index) in displayCards" :key="'rc-'+index">
                        <div class="roulette-card" :class="{ 'is-winner': cafe.isWinner && showWinner }"
                            :style="cafe.style">
                            <img :src="cafe.image" :alt="cafe.name" class="roulette-card-img" loading="eager">
                            <div class="roulette-card-info">
                                <div class="roulette-card-name" x-text="cafe.name"></div>
                                <div class="roulette-card-city">
                                    <i class="ph-fill ph-map-pin" style="color:#F59E0B"></i>
                                    <span x-text="cafe.city"></span>
                                </div>
                                </div>
                            </div>
                    </template>

                    {{-- Confetti --}}
                    <div class="confetti-container" x-show="showConfetti" id="confetti-box"></div>
                </div>

                {{-- No open cafe state --}}
                <template x-if="noOpenCafe">
                    <div class="roulette-empty" style="margin-top:-10px">
                        <span class="roulette-empty-icon"></span>
                        <p class="roulette-empty-text">Sayang banget, nggak ada cafe yang buka sekarang.</p>
                        <p class="text-xs text-red-400 mt-2" x-show="errorMessage" x-text="errorMessage"></p>
                    </div>
                </template>

                {{-- Spin Button --}}
                <template x-if="!winnerData">
                    <button class="roulette-spin-btn" @click="doSpin()" :disabled="spinning || cooldown"
                        x-text="spinning ? '  Memutar...' : (cooldown ? ' Tunggu...' : '  PUTAR SEKARANG!')">
                    </button>
                </template>

                {{-- Winner CTA --}}
                <template x-if="winnerData && showWinner">
                    <div class="roulette-cta-group">
                        <a :href="winnerData.url" class="roulette-cta roulette-cta-primary" @click="closeModal()">
                            <i class="ph-bold ph-arrow-right"></i>
                            Lihat Cafe
                        </a>
                        <button class="roulette-cta roulette-cta-secondary" @click="resetAndSpin()">
                            <i class="ph-bold ph-arrows-clockwise"></i>
                            Putar Lagi
                        </button>
                    </div>
                </template>
                </div>

            <div x-show="activeMode === 'voice'" x-cloak class="roulette-voice-search">
                <form class="roulette-voice-form" @submit.prevent="searchByVoice()">
                    <label class="roulette-voice-field">
                        <span>Ucapkan kebutuhanmu</span>
                        <textarea
                            wire:model="voiceCommand"
                            rows="3"
                            maxlength="500"
                            placeholder="Contoh: Saya di Jalan Juanda, cari kafe dengan mushola dan Wi-Fi, harga terjangkau."
                            aria-label="Perintah pencarian kafe"
                        ></textarea>
                    </label>
                    <div class="roulette-voice-actions">
                        <button type="button" class="roulette-voice-button" @click="startVoiceRecognition()" :disabled="isListening">
                            <i class="ph-bold ph-microphone"></i>
                            <span x-text="isListening ? 'Mendengarkan...' : 'Mulai bicara'"></span>
                        </button>
                        <span class="roulette-voice-status" role="status" x-text="voiceStatus"></span>
                    </div>

                    <label class="roulette-voice-field">
                        <span>Lokasi awal</span>
                        <input
                            type="text"
                            wire:model="voiceLocationAddress"
                            maxlength="200"
                            autocomplete="street-address"
                            placeholder="Contoh: Jalan Juanda, Samarinda"
                        >
                    </label>
                    <button type="button" class="roulette-voice-location" @click="useCurrentLocation()" :disabled="isGettingLocation">
                        <i class="ph-bold ph-crosshair"></i>
                        <span x-text="isGettingLocation ? 'Mengambil lokasi...' : 'Gunakan lokasi GPS saya'"></span>
                    </button>
                    <p class="roulette-voice-privacy">Alamat yang diketik akan dikirim ke Google Maps untuk mencari koordinat. Pengenalan suara memerlukan izin mikrofon dan bergantung pada dukungan browser. Jika menggunakan GPS, browser akan meminta izin lokasi.</p>

                    <label class="roulette-voice-field">
                        <span>Batas harga per menu (Rp)</span>
                        <input type="number" wire:model="maxMenuPrice" min="0" max="1000000" step="1000" inputmode="numeric">
                        <small>Awalnya Rp5.000. Ubah bila batas terjangkau kamu berbeda. Isi 0 untuk tanpa batas.</small>
                    </label>

                    <button type="submit" class="roulette-voice-submit" wire:loading.attr="disabled" wire:target="searchNearbyByVoice">
                        <i class="ph-bold ph-magnifying-glass"></i>
                        <span wire:loading.remove wire:target="searchNearbyByVoice">Cari kafe terdekat</span>
                        <span wire:loading wire:target="searchNearbyByVoice">Mencari...</span>
                    </button>
                </form>

                @if ($voiceSearchError)
                    <p class="roulette-voice-error" role="alert">{{ $voiceSearchError }}</p>
                @endif
                @if ($voiceSearchSummary)
                    <p class="roulette-voice-summary" role="status">{{ $voiceSearchSummary }}</p>
                @endif
                @if ($voiceSearchLocationLabel)
                    <p class="roulette-voice-origin">Titik pencarian: {{ $voiceSearchLocationLabel }}</p>
                @endif

                @foreach ($voiceSearchGroups as $group)
                    <section class="roulette-voice-group" aria-labelledby="voice-group-{{ $group['key'] }}">
                        <header>
                            <h3 id="voice-group-{{ $group['key'] }}">{{ $group['title'] }}</h3>
                            <span>{{ $group['count'] }}</span>
                        </header>
                        <p>{{ $group['description'] }}</p>
                        @forelse ($group['cafes'] as $cafe)
                            <article class="roulette-voice-result">
                                <img src="{{ $cafe['image'] }}" alt="" loading="lazy">
                                <div class="roulette-voice-result-copy">
                                    <h4>{{ $cafe['name'] }}</h4>
                                    <span>{{ $cafe['city'] }} · {{ number_format($cafe['distance_km'], 1, ',', '.') }} km</span>
                                    <span>
                                        @if ($cafe['minimum_menu_price'] !== null)
                                            Menu mulai Rp{{ number_format($cafe['minimum_menu_price'], 0, ',', '.') }}
                                        @else
                                            Harga menu belum tercatat
                                        @endif
                                    </span>
                                    <small>Memiliki: {{ implode(', ', $cafe['matched_facilities']) ?: 'Belum ada yang tercatat' }}</small>
                                    @if ($cafe['missing_facilities'] !== [])
                                        <small class="is-missing">Belum tercatat: {{ implode(', ', $cafe['missing_facilities']) }}</small>
                                    @endif
                                    <a href="{{ $cafe['url'] }}">Lihat kafe</a>
                                </div>
                            </article>
                        @empty
                            <p class="roulette-voice-no-results">Belum ada kafe dalam kelompok ini.</p>
                        @endforelse
                    </section>
                @endforeach
            </div>
        </div>
    </template>
</div>

@script
<script>
    Alpine.data('cafeRoulette', (wire) => ({
        activeMode: 'random',
        spinning: false,
        candidates: [],
        displayCards: [],
        winnerData: null,
        showWinner: false,
        showConfetti: false,
        cooldown: false,
        noOpenCafe: false,
        errorMessage: '',
        audioCtx: null,
        spinTimeout: null,
        fabDragging: false,
        fabMoved: false,
        suppressFabClick: false,
        fabPointerId: null,
        fabStartClientX: 0,
        fabStartClientY: 0,
        fabStartLeft: 0,
        fabStartTop: 0,
        fabStyle: '',
        isListening: false,
        isGettingLocation: false,
        voiceStatus: '',

        startVoiceRecognition() {
            const SpeechRecognition = window.SpeechRecognition || window.webkitSpeechRecognition;
            if (!SpeechRecognition) {
                this.voiceStatus = 'Pengenalan suara belum didukung browser ini. Ketik kebutuhanmu atau coba Chrome/Edge.';
                return;
            }

            const recognition = new SpeechRecognition();
            recognition.lang = 'id-ID';
            recognition.interimResults = false;
            recognition.maxAlternatives = 1;
            this.isListening = true;
            this.voiceStatus = 'Silakan ucapkan kebutuhan dan lokasi kafe.';

            recognition.onresult = async (event) => {
                const transcript = event.results?.[0]?.[0]?.transcript?.trim() || '';
                if (!transcript) return;

                await wire.set('voiceCommand', transcript);
                const address = this.extractStreetAddress(transcript);
                if (address) {
                    await wire.set('voiceLocationAddress', address);
                    await wire.set('voiceLatitude', null);
                    await wire.set('voiceLongitude', null);
                    this.voiceStatus = `Lokasi terdeteksi: ${address}. Periksa sebelum mencari.`;
                } else {
                    this.voiceStatus = 'Teks suara siap. Isi lokasi awal atau gunakan GPS.';
                }
            };
            recognition.onerror = (event) => {
                const messages = {
                    'not-allowed': 'Izin mikrofon ditolak. Izinkan mikrofon di browser atau ketik kebutuhanmu.',
                    'service-not-allowed': 'Layanan pengenalan suara tidak tersedia. Ketik kebutuhanmu sebagai gantinya.',
                    'no-speech': 'Suara belum terdengar. Coba bicara lagi atau ketik kebutuhanmu.',
                    network: 'Layanan suara mengalami gangguan jaringan. Kamu bisa mengetik kebutuhanmu.',
                };
                this.voiceStatus = messages[event.error] || 'Suara tidak dapat dikenali. Coba lagi atau ketik kebutuhanmu.';
            };
            recognition.onend = () => {
                this.isListening = false;
                if (this.voiceStatus === 'Silakan ucapkan kebutuhan dan lokasi kafe.') {
                    this.voiceStatus = 'Belum ada suara yang dikenali. Coba lagi atau ketik kebutuhanmu.';
                }
            };

            try {
                recognition.start();
            } catch {
                this.isListening = false;
                this.voiceStatus = 'Mikrofon belum siap. Coba lagi atau ketik kebutuhanmu.';
            }
        },

        extractStreetAddress(transcript) {
            const match = transcript.match(/\b(?:di|dari|sekitar|dekat)\s+(?:jalan|jl\.?|jln\.?)\s+([^,.;!?]+)/i);
            if (!match) return '';

            const street = match[1]
                .split(/\s+(?=(?:mau|ingin|cari|tolong|dengan|yang|ada|harga|fasilitas)\b)/i)[0]
                .trim();

            return street ? `Jalan ${street}` : '';
        },

        useCurrentLocation() {
            if (!navigator.geolocation) {
                this.voiceStatus = 'Browser ini tidak mendukung lokasi GPS. Isi alamat awal secara manual.';
                return;
            }
            if (!window.isSecureContext && location.hostname !== 'localhost') {
                this.voiceStatus = 'Lokasi GPS memerlukan HTTPS. Gunakan alamat awal sebagai gantinya.';
                return;
            }

            this.isGettingLocation = true;
            this.voiceStatus = 'Meminta izin lokasi perangkat...';
            navigator.geolocation.getCurrentPosition(async (position) => {
                try {
                    await wire.setVoiceLocation(position.coords.latitude, position.coords.longitude);
                    this.voiceStatus = 'Lokasi GPS siap digunakan.';
                } catch {
                    this.voiceStatus = 'Lokasi GPS gagal disimpan. Coba lagi.';
                } finally {
                    this.isGettingLocation = false;
                }
            }, (error) => {
                this.isGettingLocation = false;
                const messages = {
                    1: 'Izin lokasi ditolak. Izinkan lokasi di browser atau isi alamat awal.',
                    2: 'Lokasi perangkat tidak ditemukan. Coba lagi atau isi alamat awal.',
                    3: 'Permintaan lokasi terlalu lama. Coba lagi atau isi alamat awal.',
                };
                this.voiceStatus = messages[error.code] || 'Lokasi GPS gagal didapatkan. Isi alamat awal.';
            }, { enableHighAccuracy: true, timeout: 15000, maximumAge: 60000 });
        },

        async searchByVoice() {
            this.voiceStatus = 'Mencari kafe dalam radius 20 km...';
            try {
                await wire.searchNearbyByVoice();
                this.voiceStatus = '';
            } catch {
                this.voiceStatus = 'Pencarian gagal. Periksa perintah, lokasi, dan batas harga.';
            }
        },

        getFabBounds() {
            const container = document.querySelector('.main-container');
            const rect = container?.getBoundingClientRect();

            return rect
                ? { left: rect.left, right: rect.right }
                : { left: 0, right: window.innerWidth };
        },

        startFabDrag(event) {
            if (event.button !== 0) return;

            const rect = event.currentTarget.getBoundingClientRect();
            this.fabPointerId = event.pointerId;
            this.fabMoved = false;
            this.fabStartClientX = event.clientX;
            this.fabStartClientY = event.clientY;
            this.fabStartLeft = rect.left;
            this.fabStartTop = rect.top;
            event.currentTarget.setPointerCapture(event.pointerId);
        },

        moveFab(event) {
            if (event.pointerId !== this.fabPointerId) return;

            const deltaX = event.clientX - this.fabStartClientX;
            const deltaY = event.clientY - this.fabStartClientY;
            if (!this.fabMoved && Math.hypot(deltaX, deltaY) < 7) return;

            event.preventDefault();
            this.fabMoved = true;
            this.fabDragging = true;

            const fab = event.currentTarget;
            const bounds = this.getFabBounds();
            const minLeft = Math.max(8, bounds.left + 8);
            const maxLeft = Math.max(minLeft, Math.min(window.innerWidth, bounds.right) - fab.offsetWidth - 8);
            const maxTop = Math.max(8, window.innerHeight - fab.offsetHeight - 8);
            const left = Math.min(Math.max(minLeft, this.fabStartLeft + deltaX), maxLeft);
            const top = Math.min(Math.max(8, this.fabStartTop + deltaY), maxTop);

            this.fabStyle = `left:${left}px;top:${top}px;right:auto;bottom:auto;`;
        },

        endFabDrag(event) {
            if (event.pointerId !== this.fabPointerId) return;
            this.fabPointerId = null;
            if (!this.fabMoved) return;

            const fab = event.currentTarget;
            const rect = fab.getBoundingClientRect();
            const bounds = this.getFabBounds();
            const minLeft = Math.max(8, bounds.left + 8);
            const maxLeft = Math.max(minLeft, Math.min(window.innerWidth, bounds.right) - fab.offsetWidth - 8);
            const left = Math.min(Math.max(rect.left, minLeft), maxLeft);
            const top = Math.min(Math.max(8, rect.top), Math.max(8, window.innerHeight - fab.offsetHeight - 8));

            this.fabStyle = `left:${left}px;top:${top}px;right:auto;bottom:auto;`;
            this.fabDragging = false;
            this.suppressFabClick = true;
            window.setTimeout(() => { this.suppressFabClick = false; }, 250);
        },

        async doSpin() {
            if (this.spinning || this.cooldown) return;
            this.spinning = true;
            this.showWinner = false;
            this.showConfetti = false;
            this.winnerData = null;
            this.displayCards = [];
            this.noOpenCafe = false;

            // Safety timeout: if spin takes longer than 10s, reset
            this.spinTimeout = setTimeout(() => {
                if (this.spinning) {
                    this.spinning = false;
                    this.noOpenCafe = true;
                }
            }, 10000);

            try {
                // Call Livewire method and wait for response
                await wire.spin();

                clearTimeout(this.spinTimeout);

                // Read data directly from Livewire properties
                // NOTE: Use wire.get if accessing public properties directly to ensure latest state
                const candidates = await wire.get('candidates');
                const winner = await wire.get('winner');

                if (!candidates || candidates.length === 0) {
                    this.spinning = false;
                    this.noOpenCafe = true;
                    return;
                }

                this.candidates = candidates;
                this.winnerData = winner;
                this.startAnimation();
            } catch (e) {
                clearTimeout(this.spinTimeout);
                this.spinning = false;
                this.noOpenCafe = true;
                this.errorMessage = e.message || 'Terjadi kesalahan teknis (' + e + ')';
                console.error('Roulette spin error:', e);
            }
        },

        resetAndSpin() {
            // Cancel any active cooldown so doSpin() won't be blocked
            this.cooldown = false;
            this.spinning = false;
            this.winnerData = null;
            this.showWinner = false;
            this.showConfetti = false;
            this.displayCards = [];
            this.noOpenCafe = false;
            this.$nextTick(() => this.doSpin());
        },

        startAnimation() {
            const total = this.candidates.length;
            if (total === 0) {
                this.spinning = false;
                return;
            }

            // Shuffle helper (Fisher-Yates)
            const shuffle = (arr) => {
                const a = [...arr];
                for (let i = a.length - 1; i > 0; i--) {
                    const j = Math.floor(Math.random() * (i + 1));
                    [a[i], a[j]] = [a[j], a[i]];
                }
                return a;
            };

            // - Random number of cycles (2-4) to keep it responsive
            // - Candidates shuffled differently each cycle
            let sequence = [];
            // Reduced cycles: 2-3 times through the list is enough for a "quick" feel
            const cycles = 2 + Math.floor(Math.random() * 2);

            for (let c = 0; c < cycles; c++) {
                const shuffled = shuffle(this.candidates);
                for (let i = 0; i < shuffled.length; i++) {
                    sequence.push({ ...shuffled[i], isWinner: false });
                }
            }

            // Add 2-4 extra random cards near the end for suspense
            const extraCards = 2 + Math.floor(Math.random() * 3);
            const nonWinners = this.candidates.filter(c => c.id !== this.winnerData.id);
            for (let i = 0; i < extraCards && nonWinners.length > 0; i++) {
                const pick = nonWinners[Math.floor(Math.random() * nonWinners.length)];
                sequence.push({ ...pick, isWinner: false });
            }

            // Final winner at the end
            sequence.push({ ...this.winnerData, isWinner: true });

            let step = 0;
            const totalSteps = sequence.length;

            const showNext = () => {
                if (step >= totalSteps) {
                    this.revealWinner();
                    return;
                }

                const card = sequence[step];
                
                // Calculate progress (0.0 to 1.0)
                const progress = step / totalSteps;
                const remaining = totalSteps - step;

                // --- NEW LOGIC: Physics-based "Friction" ---
                // 1. Base speed is fast (40-50ms)
                // 2. Friction kicks in only at the end (last ~25% or last 8 cards)
                // 3. Random "hiccups" to make it unpredictable

                let delay = 40; // Base fast speed

                if (remaining <= 8) {
                    // Final deceleration: Exponential slowdown
                    // The closer to 0 remaining, the higher the delay.
                    // f(x) = base + (factor / x)
                    const decelerationFactor = 300 + Math.random() * 200; 
                    delay = 40 + (decelerationFactor / Math.max(0.5, remaining * 0.5));
                } else if (Math.random() < 0.05 && progress > 0.3 && progress < 0.7) {
                    // Random "Hiccup" (5% chance in the middle): Unexpected brief pause
                    // Makes it feel like the wheel is "slipping" or "thinking"
                    delay = 150 + Math.random() * 100;
                } else {
                    // Normal cruising speed with slight jitter
                    delay = 40 + Math.random() * 10;
                }

                // Hard cap to prevent it from being too slow/boring
                delay = Math.min(delay, 700);

                this.displayCards = [{
                    ...card,
                    style: 'opacity: 1; transform: translateY(0) scale(1);',
                }];

                step++;
                
                // Transition out logic
                setTimeout(() => {
                    if (step < totalSteps) {
                        // Move current card up and fade out
                        this.displayCards = [{
                            ...card,
                            style: 'opacity: 0; transform: translateY(-40px) scale(0.9); transition: all 0.1s ease-in;',
                        }];
                    }
                    // Schedule next frame
                    // Subtracting a bit from delay for the timeout to make the loop tighter
                    setTimeout(showNext, delay * 0.8); 
                }, delay);
            };

            showNext();
        },

        revealWinner() {
            this.showWinner = true;
            this.displayCards = [{
                ...this.winnerData,
                isWinner: true,
                style: '',
            }];

            this.playDing();
            this.spawnConfetti();

            this.spinning = false;
            this.cooldown = true;
            setTimeout(() => { this.cooldown = false; }, 2000);
        },

        playDing() {
            try {
                if (!this.audioCtx) {
                    this.audioCtx = new (window.AudioContext || window.webkitAudioContext)();
                }
                const ctx = this.audioCtx;
                const t = ctx.currentTime;

                // "Correct Answer" — ascending triple tone (C5 → E5 → G5)
                const notes = [
                    { freq: 523, start: 0, dur: 0.12 }, // C5
                    { freq: 659, start: 0.12, dur: 0.12 }, // E5
                    { freq: 784, start: 0.24, dur: 0.35 }, // G5 (held longer)
                ];

                notes.forEach(note => {
                    const osc = ctx.createOscillator();
                    const harm = ctx.createOscillator();
                    const gain = ctx.createGain();

                    osc.type = 'sine';
                    osc.frequency.setValueAtTime(note.freq, t + note.start);

                    // Soft harmonic overtone for richness
                    harm.type = 'triangle';
                    harm.frequency.setValueAtTime(note.freq * 2, t + note.start);

                    // Volume envelope
                    gain.gain.setValueAtTime(0, t + note.start);
                    gain.gain.linearRampToValueAtTime(0.18, t + note.start + 0.02);
                    gain.gain.exponentialRampToValueAtTime(0.001, t + note.start + note.dur + 0.15);

                    osc.connect(gain);
                    harm.connect(gain);
                    gain.connect(ctx.destination);

                    osc.start(t + note.start);
                    harm.start(t + note.start);
                    osc.stop(t + note.start + note.dur + 0.2);
                    harm.stop(t + note.start + note.dur + 0.2);
                });
            } catch (e) {
                // AudioContext not supported — silent fail
            }
        },

        spawnConfetti() {
            this.showConfetti = true;
            const container = document.getElementById('confetti-box');
            if (!container) return;

            container.innerHTML = '';
            const colors = ['#F59E0B', '#D97706', '#EF4444', '#10B981', '#3B82F6', '#8B5CF6', '#EC4899'];

            for (let i = 0; i < 30; i++) {
                const particle = document.createElement('div');
                particle.className = 'confetti-particle';
                particle.style.left = Math.random() * 100 + '%';
                particle.style.backgroundColor = colors[Math.floor(Math.random() * colors.length)];
                particle.style.animationDelay = (Math.random() * 0.5) + 's';
                particle.style.animationDuration = (1 + Math.random() * 1) + 's';
                particle.style.width = (5 + Math.random() * 6) + 'px';
                particle.style.height = (5 + Math.random() * 6) + 'px';
                particle.style.borderRadius = Math.random() > 0.5 ? '50%' : '2px';
                container.appendChild(particle);
            }

            setTimeout(() => {
                this.showConfetti = false;
                if (container) container.innerHTML = '';
            }, 2500);
        },

        closeModal() {
            // Always allow close — force reset everything
            clearTimeout(this.spinTimeout);
            this.spinning = false;
            this.cooldown = false;
            wire.closeModal();
            this.displayCards = [];
            this.winnerData = null;
            this.showWinner = false;
            this.showConfetti = false;
            this.noOpenCafe = false;
            this.candidates = [];
        },
    }));
</script>
@endscript