{{-- Onboarding Flow — Shown once on first visit --}}
<div x-data="{
    show: false,
    step: 0,
    steps: [
        { title: 'Temukan Cafe Favoritmu', text: 'Jelajahi ratusan cafe dan roastery terbaik di Kalimantan. Filter berdasarkan lokasi, jam buka, dan lainnya.', icon: 'compass' },
        { title: 'Simpan & Akses Kapan Saja', text: 'Tandai cafe favoritmu agar mudah ditemukan kembali. Data tersimpan langsung di browsermu.', icon: 'bookmark-simple' },
        { title: 'Info Kopi Terbaru', text: 'Dapatkan berita, event, dan promo menarik seputar dunia kopi langsung di genggamanmu.', icon: 'newspaper' }
    ],
    init() {
        if (!localStorage.getItem('wadah-onboarded')) {
            this.show = true;
        }
    },
    next() {
        if (this.step < 2) {
            this.step++;
        } else {
            this.complete();
        }
    },
    complete() {
        localStorage.setItem('wadah-onboarded', '1');
        this.show = false;
    }
}" x-show="show" x-cloak x-transition:enter="transition ease-out duration-400" x-transition:enter-start="opacity-0"
    x-transition:enter-end="opacity-100" x-transition:leave="transition ease-in duration-200"
    x-transition:leave-start="opacity-100" x-transition:leave-end="opacity-0" class="onboarding-overlay">

    <div class="onboarding-card">
        {{-- Illustration --}}
        <div class="onboarding-illustration">
            {{-- Compass Icon --}}
            <template x-if="step === 0">
                <svg viewBox="0 0 120 120" fill="none" xmlns="http://www.w3.org/2000/svg">
                    <circle cx="60" cy="60" r="50" fill="#F5EFED" stroke="#E6E1DC" stroke-width="1" />
                    <circle cx="60" cy="60" r="38" fill="white" stroke="#2C1810" stroke-width="2" />
                    <polygon points="60,28 68,55 60,48 52,55" fill="#F59E0B" />
                    <polygon points="60,92 52,65 60,72 68,65" fill="#2C1810" />
                    <circle cx="60" cy="60" r="5" fill="#2C1810" />
                    <circle cx="60" cy="60" r="2" fill="#F59E0B" />
                </svg>
            </template>
            {{-- Bookmark Icon --}}
            <template x-if="step === 1">
                <svg viewBox="0 0 120 120" fill="none" xmlns="http://www.w3.org/2000/svg">
                    <rect x="30" y="20" width="60" height="80" rx="8" fill="#F5EFED" stroke="#E6E1DC"
                        stroke-width="1" />
                    <rect x="38" y="28" width="44" height="64" rx="4" fill="white" stroke="#2C1810" stroke-width="2" />
                    <path d="M45 28 L45 65 L60 55 L75 65 L75 28" fill="#F59E0B" stroke="#D97706" stroke-width="1" />
                    <circle cx="60" cy="82" r="4" fill="#2C1810" />
                </svg>
            </template>
            {{-- Newspaper Icon --}}
            <template x-if="step === 2">
                <svg viewBox="0 0 120 120" fill="none" xmlns="http://www.w3.org/2000/svg">
                    <rect x="20" y="25" width="80" height="70" rx="10" fill="#F5EFED" stroke="#E6E1DC"
                        stroke-width="1" />
                    <rect x="28" y="33" width="64" height="54" rx="6" fill="white" stroke="#2C1810" stroke-width="2" />
                    <rect x="36" y="41" width="28" height="18" rx="3" fill="#F59E0B" />
                    <line x1="36" y1="67" x2="84" y2="67" stroke="#E6E1DC" stroke-width="2" stroke-linecap="round" />
                    <line x1="36" y1="75" x2="70" y2="75" stroke="#E6E1DC" stroke-width="2" stroke-linecap="round" />
                    <line x1="70" y1="41" x2="84" y2="41" stroke="#E6E1DC" stroke-width="2" stroke-linecap="round" />
                    <line x1="70" y1="49" x2="84" y2="49" stroke="#E6E1DC" stroke-width="2" stroke-linecap="round" />
                    <line x1="70" y1="57" x2="80" y2="57" stroke="#E6E1DC" stroke-width="2" stroke-linecap="round" />
                </svg>
            </template>
        </div>

        {{-- Title --}}
        <h3 class="onboarding-title" x-text="steps[step].title"></h3>

        {{-- Text --}}
        <p class="onboarding-text" x-text="steps[step].text"></p>

        {{-- Progress Dots --}}
        <div class="onboarding-dots">
            <template x-for="(s, i) in steps" :key="i">
                <div class="onboarding-dot" :class="i === step ? 'active' : ''"></div>
            </template>
        </div>

        {{-- Actions --}}
        <div class="onboarding-actions">
            <button class="onboarding-btn-skip" @click="complete()" x-show="step < 2">
                Lewati
            </button>
            <button class="onboarding-btn-primary" @click="next()">
                <span x-text="step < 2 ? 'Lanjut' : 'Mulai Jelajahi!'"></span>
            </button>
        </div>
    </div>
</div>