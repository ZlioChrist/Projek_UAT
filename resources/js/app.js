import './bootstrap';

/**
 * WADAHNGOPI 2026 — CORE INTERACTIVE ENGINE
 * Powered by Antigravity AI
 */

document.addEventListener('DOMContentLoaded', () => {
    initStaggeredEntrance();
    initMagneticElements();
    initToastNotifications();
});

function initToastNotifications() {
    const toast = document.getElementById('app-toast');
    const icon = document.getElementById('app-toast-icon');
    const message = document.getElementById('app-toast-message');
    const action = document.getElementById('app-toast-action');
    const dismiss = document.getElementById('app-toast-dismiss');
    if (!toast || !icon || !message || !action || !dismiss) return;

    let dismissTimer;
    const hideToast = () => {
        window.clearTimeout(dismissTimer);
        toast.classList.remove('is-visible');
        dismissTimer = window.setTimeout(() => {
            toast.hidden = true;
        }, 180);
    };

    window.addEventListener('toast', (event) => {
        const detail = event.detail || {};
        const type = ['success', 'warning', 'error'].includes(detail.type) ? detail.type : 'success';
        message.textContent = typeof detail.message === 'string' ? detail.message : '';
        icon.className = `ph-fill ${type === 'warning' ? 'ph-warning-circle' : type === 'error' ? 'ph-x-circle' : 'ph-check-circle'} app-toast-icon`;
        toast.dataset.type = type;

        if (detail.action && typeof detail.action.label === 'string' && typeof detail.action.url === 'string') {
            const actionUrl = new URL(detail.action.url, window.location.href);
            if (actionUrl.origin === window.location.origin) {
                action.href = actionUrl.href;
                action.textContent = detail.action.label;
                action.hidden = false;
            } else {
                action.hidden = true;
                action.removeAttribute('href');
                action.textContent = '';
            }
        } else {
            action.hidden = true;
            action.removeAttribute('href');
            action.textContent = '';
        }

        window.clearTimeout(dismissTimer);
        toast.hidden = false;
        window.requestAnimationFrame(() => toast.classList.add('is-visible'));
        const duration = Number.isFinite(detail.duration) ? Math.min(Math.max(detail.duration, 1500), 10000) : 3200;
        dismissTimer = window.setTimeout(hideToast, duration);
    });

    dismiss.addEventListener('click', hideToast);
}

document.addEventListener('change', (event) => {
    const input = event.target;
    if (!(input instanceof HTMLInputElement) || input.name !== 'payment_method') return;

    const selector = input.closest('[data-payment-selector]');
    if (!selector) return;

    selector.querySelectorAll('.checkout-payment-option').forEach((option) => {
        const radio = option.querySelector('input[name="payment_method"]');
        option.classList.toggle('is-selected', radio?.checked ?? false);
    });

    selector.querySelectorAll('[data-payment-panel]').forEach((panel) => {
        panel.hidden = panel.dataset.paymentPanel !== input.value;
    });
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
