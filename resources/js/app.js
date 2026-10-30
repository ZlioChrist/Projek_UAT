import './bootstrap';

/**
 * WADAHNGOPI 2026 — CORE INTERACTIVE ENGINE
 * Powered by Antigravity AI
 */

document.addEventListener('DOMContentLoaded', () => {
    initStaggeredEntrance();
    initMagneticElements();
});

// 1. STAGGERED ENTRANCE (Intersection Observer)
// Membuat card muncul secara bergantian saat di-scroll
function initStaggeredEntrance() {
    const observerOptions = {
        root: null,
        rootMargin: '0px 0px -50px 0px',
        threshold: 0.15
    };

    const observer = new IntersectionObserver((entries) => {
        entries.forEach((entry) => {
            if (entry.isIntersecting) {
                // Tambahkan class visible untuk trigger CSS animation
                entry.target.classList.add('visible');
                
                // Jika card punya stagger delay via CSS nth-child, dia bakal otomatis delay
                // Stop observing setelah muncul biar gak re-animate (performance)
                observer.unobserve(entry.target);
            }
        });
    }, observerOptions);

    // Targetkan semua elemen dengan class card-stagger atau bento-tile
    const animateElements = document.querySelectorAll('.card-stagger, .bento-tile, .animate-up');
    animateElements.forEach(el => observer.observe(el));

    // Handle Livewire Load More (RE-OBSERVE)
    window.addEventListener('livewire:load', () => {
        Livewire.hook('message.processed', (message, component) => {
            const newElements = document.querySelectorAll('.card-stagger:not(.visible), .animate-up:not(.visible)');
            newElements.forEach(el => observer.observe(el));
        });
    });
}

// 2. MAGNETIC ELEMENTS (Subtle 3D Tilt)
// Efek parallax saat mouse mendekati tombol utama
function initMagneticElements() {
    const magneticElements = document.querySelectorAll('.magnetic-btn');
    
    magneticElements.forEach(btn => {
        btn.addEventListener('mousemove', (e) => {
            const rect = btn.getBoundingClientRect();
            const x = e.clientX - rect.left - rect.width / 2;
            const y = e.clientY - rect.top - rect.height / 2;
            
            btn.style.transform = `translate(${x * 0.3}px, ${y * 0.3}px) scale(1.05)`;
        });
        
        btn.addEventListener('mouseleave', () => {
            btn.style.transform = '';
        });
    });
}

// 3. PWA HAPTIC FEEDBACK (Mobile Only)
window.hapticFeedback = function(type = 'light') {
    if (window.navigator.vibrate) {
        if (type === 'light') window.navigator.vibrate(15);
        else if (type === 'medium') window.navigator.vibrate(30);
        else if (type === 'error') window.navigator.vibrate([30, 50, 30]);
        return;
    }
    
    // Fallback buat user ipon yang ga support getar cuy
    document.body.classList.add('haptic-fallback');
    setTimeout(() => {
        document.body.classList.remove('haptic-fallback');
    }, 150);
};
