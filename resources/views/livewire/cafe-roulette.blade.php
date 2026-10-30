{{-- Cafe Roulette — "Bingung? Putar Aja!" --}}
<div x-data="cafeRoulette(@this)" x-init="init()">

    {{-- Draggable Floating Action Button --}}
    <button class="roulette-fab" id="roulette-fab-btn" x-ref="fab" x-show="!$wire.isOpen"
        :class="{ 'is-dragging': fabDragging }" :style="fabStyle" @mousedown.prevent="fabDragStart($event)"
        @touchstart.passive="fabDragStart($event)" x-transition:enter="transition ease-out duration-300"
        x-transition:enter-start="opacity-0 translate-y-4 scale-90"
        x-transition:enter-end="opacity-100 translate-y-0 scale-100">
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
            <div class="roulette-modal" x-show="$wire.isOpen" x-transition:enter="transition ease-out duration-400"
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
                    <p class="roulette-subtitle">Putar dan temukan cafe yang pas buat kamu!</p>
                </div>

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
        </div>
    </template>
</div>

@script
<script>
    Alpine.data('cafeRoulette', (wire) => ({
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

        // Draggable FAB state
        fabDragging: false,
        fabX: null,
        fabY: null,
        fabStartX: 0,
        fabStartY: 0,
        fabTouchStartX: 0,
        fabTouchStartY: 0,
        fabMoved: false,
        fabStyle: '',

        init() {
            // Restore saved FAB position from localStorage
            const saved = localStorage.getItem('roulette-fab-pos');
            if (saved) {
                try {
                    const pos = JSON.parse(saved);
                    const bounds = this.getContainerBounds();
                    this.fabX = Math.min(Math.max(bounds.left + 8, pos.x), bounds.right - 60);
                    this.fabY = Math.min(Math.max(8, pos.y), window.innerHeight - 60);
                    this.applyFabPosition();
                } catch (e) { /* ignore corrupt data */ }
            }

            // Global move/end listeners (bound once)
            this._fabMove = (e) => this.fabDragMove(e);
            this._fabEnd = (e) => this.fabDragEnd(e);
        },

        // Get the app container bounds (respects max-width: 480px centered layout)
        getContainerBounds() {
            const container = document.querySelector('.main-container');
            if (container) {
                const rect = container.getBoundingClientRect();
                return { left: rect.left, right: rect.right, width: rect.width };
            }
            return { left: 0, right: window.innerWidth, width: window.innerWidth };
        },

        // --- Draggable FAB Methods ---
        fabDragStart(e) {
            const touch = e.touches ? e.touches[0] : e;
            this.fabTouchStartX = touch.clientX;
            this.fabTouchStartY = touch.clientY;
            this.fabMoved = false;

            const fab = this.$refs.fab;
            const rect = fab.getBoundingClientRect();
            this.fabStartX = touch.clientX - rect.left;
            this.fabStartY = touch.clientY - rect.top;

            // Attach global listeners
            document.addEventListener('mousemove', this._fabMove, { passive: false });
            document.addEventListener('mouseup', this._fabEnd);
            document.addEventListener('touchmove', this._fabMove, { passive: false });
            document.addEventListener('touchend', this._fabEnd);
        },

        fabDragMove(e) {
            e.preventDefault();
            const touch = e.touches ? e.touches[0] : e;

            const dx = Math.abs(touch.clientX - this.fabTouchStartX);
            const dy = Math.abs(touch.clientY - this.fabTouchStartY);

            // Only start dragging after 8px movement (distinguish tap vs drag)
            if (!this.fabMoved && (dx + dy) < 8) return;
            this.fabMoved = true;
            this.fabDragging = true;

            // Calculate new position (clamped to container bounds)
            const fab = this.$refs.fab;
            const w = fab.offsetWidth;
            const h = fab.offsetHeight;
            const bounds = this.getContainerBounds();
            const minX = bounds.left + 8;
            const maxX = bounds.right - w - 8;
            const maxY = window.innerHeight - h - 8;

            this.fabX = Math.min(Math.max(minX, touch.clientX - this.fabStartX), maxX);
            this.fabY = Math.min(Math.max(8, touch.clientY - this.fabStartY), maxY);
            this.applyFabPosition();
        },

        fabDragEnd() {
            // Remove global listeners
            document.removeEventListener('mousemove', this._fabMove);
            document.removeEventListener('mouseup', this._fabEnd);
            document.removeEventListener('touchmove', this._fabMove);
            document.removeEventListener('touchend', this._fabEnd);

            if (!this.fabMoved) {
                // It was a tap, not a drag — open modal
                this.fabDragging = false;
                wire.openModal();
                return;
            }

            // Snap to nearest edge within container bounds
            const fab = this.$refs.fab;
            const w = fab.offsetWidth;
            const bounds = this.getContainerBounds();
            const midX = bounds.left + bounds.width / 2;

            if (this.fabX + w / 2 < midX) {
                this.fabX = bounds.left + 12; // Snap to container left
            } else {
                this.fabX = bounds.right - w - 12; // Snap to container right
            }

            this.applyFabPosition();

            // Save position to localStorage
            localStorage.setItem('roulette-fab-pos', JSON.stringify({
                x: this.fabX,
                y: this.fabY,
            }));

            // Small delay before removing dragging class (for smooth snap transition)
            setTimeout(() => { this.fabDragging = false; }, 300);
        },

        applyFabPosition() {
            if (this.fabX !== null && this.fabY !== null) {
                this.fabStyle = `position:fixed; left:${this.fabX}px; top:${this.fabY}px; right:auto; bottom:auto;`;
            }
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