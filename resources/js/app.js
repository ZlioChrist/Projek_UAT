import './bootstrap';

/**
 * WADAHNGOPI 2026 — CORE INTERACTIVE ENGINE
 * Powered by Antigravity AI
 */

document.addEventListener('DOMContentLoaded', () => {
    initStaggeredEntrance();
    initMagneticElements();
    initToastNotifications();
    initUnreadNotificationPolling();
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
        toast.classList.toggle('is-notification-toast', detail.kind === 'notification');

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

function initUnreadNotificationPolling() {
    const toast = document.getElementById('app-toast');
    if (!toast) return;

    const { unreadCountUrl, notificationsUrl, notificationUserKey, isNotificationsPage } = toast.dataset;
    if (!unreadCountUrl || !notificationsUrl || !notificationUserKey) return;

    const storageKey = `wadahngopi.unread-notifications.${notificationUserKey}`;
    let previousCount = null;
    let storageAvailable = true;

    try {
        const storedCount = window.sessionStorage.getItem(storageKey);
        if (storedCount !== null) {
            const parsedCount = Number(storedCount);
            if (Number.isInteger(parsedCount) && parsedCount >= 0) {
                previousCount = parsedCount;
            } else {
                window.sessionStorage.removeItem(storageKey);
            }
        }
    } catch (error) {
        storageAvailable = false;
        console.warn('Pengingat notifikasi tidak dapat disimpan di sesi browser.', error);
    }

    const rememberCount = (count) => {
        if (!storageAvailable) return;

        try {
            window.sessionStorage.setItem(storageKey, String(count));
        } catch (error) {
            storageAvailable = false;
            console.warn('Pengingat notifikasi tidak dapat disimpan di sesi browser.', error);
        }
    };

    let requestInProgress = false;
    const checkUnreadNotifications = async () => {
        if (requestInProgress) return;
        requestInProgress = true;

        try {
            const response = await fetch(unreadCountUrl, {
                headers: { Accept: 'application/json' },
                cache: 'no-store',
                credentials: 'same-origin',
            });
            if (!response.ok) {
                throw new Error(`Pemeriksaan notifikasi gagal (${response.status}).`);
            }

            const result = await response.json();
            if (!Number.isInteger(result.count) || result.count < 0) {
                throw new Error('Jumlah notifikasi yang diterima tidak valid.');
            }

            const newCount = result.count;
            if (previousCount === null) {
                if (newCount > 0 && isNotificationsPage !== 'true') {
                    window.dispatchEvent(new CustomEvent('toast', {
                        detail: {
                            kind: 'notification',
                            type: 'warning',
                            message: `Kamu punya ${newCount} notifikasi yang belum dibaca.`,
                            duration: 6500,
                            action: { label: 'Lihat notifikasi', url: notificationsUrl },
                        },
                    }));
                }
            } else if (newCount > previousCount) {
                const unreadDelta = newCount - previousCount;
                window.dispatchEvent(new CustomEvent('toast', {
                    detail: {
                        kind: 'notification',
                        type: 'warning',
                        message: unreadDelta === 1 ? 'Ada notifikasi baru.' : `Ada ${unreadDelta} notifikasi baru.`,
                        duration: 6500,
                        action: { label: 'Lihat notifikasi', url: notificationsUrl },
                    },
                }));
            }

            previousCount = newCount;
            rememberCount(newCount);
        } catch (error) {
            console.error('Tidak dapat memeriksa notifikasi baru.', error);
        } finally {
            requestInProgress = false;
        }
    };

    checkUnreadNotifications();
    window.setInterval(() => {
        if (!document.hidden) checkUnreadNotifications();
    }, 30000);
    document.addEventListener('visibilitychange', () => {
        if (!document.hidden) checkUnreadNotifications();
    });
}

const updateTransferBankInputs = (selector, paymentMethod) => {
    const isTransfer = paymentMethod === 'transfer';
    const transferPanel = selector.querySelector('[data-payment-panel="transfer"]');

    if (transferPanel) transferPanel.hidden = !isTransfer;

    selector.querySelectorAll('input[name="sender_bank"]').forEach((input) => {
        input.disabled = !isTransfer;
        input.required = isTransfer;
    });
};

document.querySelectorAll('[data-payment-selector]').forEach((selector) => {
    const selectedMethod = selector.querySelector('input[name="payment_method"]:checked')?.value;
    updateTransferBankInputs(selector, selectedMethod);
});

const syncFulfillmentOptions = (selector) => {
    const form = selector.closest('form');
    const fulfillment = selector.querySelector('input[name="fulfillment_method"]:checked')?.value ?? 'pickup';
    const delivery = fulfillment === 'delivery';
    const addressField = form?.querySelector('[data-delivery-address-field]');
    const address = form?.querySelector('[data-delivery-address]');
    const feeNote = form?.querySelector('[data-delivery-fee-note]');
    const feeSummary = form?.querySelector('[data-checkout-delivery-fee]');
    const fulfillmentSummary = form?.querySelector('[data-fulfillment-summary]');
    const paymentSelector = form?.querySelector('[data-payment-selector]');
    const cashOption = paymentSelector?.querySelector('[data-payment-cash-option]');
    const cashInput = cashOption?.querySelector('input[name="payment_method"]');

    if (addressField) addressField.hidden = !delivery;
    if (address) {
        address.disabled = !delivery;
        address.required = delivery;
    }
    if (feeNote) feeNote.hidden = !delivery;
    if (feeSummary) feeSummary.textContent = delivery ? 'Dikonfirmasi kafe' : 'Rp0';
    if (fulfillmentSummary) {
        fulfillmentSummary.innerHTML = delivery
            ? '<i class="ph-fill ph-moped-front" aria-hidden="true"></i> Diantar ke alamat yang kamu berikan'
            : '<i class="ph-fill ph-storefront" aria-hidden="true"></i> Diambil langsung di kedai';
    }
    if (cashOption) cashOption.hidden = delivery;
    if (cashInput) {
        cashInput.disabled = delivery;
        if (delivery && cashInput.checked) {
            const replacement = paymentSelector.querySelector('input[name="payment_method"]:not([value="cash"])');
            if (replacement) {
                replacement.checked = true;
                replacement.dispatchEvent(new Event('change', {bubbles: true}));
            }
        }
    }
};

document.querySelectorAll('[data-fulfillment-selector]').forEach((selector) => {
    syncFulfillmentOptions(selector);
    selector.addEventListener('change', (event) => {
        if (event.target instanceof HTMLInputElement && event.target.name === 'fulfillment_method') {
            selector.querySelectorAll('.checkout-payment-option').forEach((option) => {
                const radio = option.querySelector('input[name="fulfillment_method"]');
                option.classList.toggle('is-selected', radio?.checked ?? false);
            });
            syncFulfillmentOptions(selector);
        }
    });
});

document.querySelectorAll('[data-payment-proof-form]').forEach((form) => {
    const proofInput = form.querySelector('input[name="proof"]');
    const submitButton = form.querySelector('button[type="submit"], button:not([type])');
    if (!(proofInput instanceof HTMLInputElement) || !(submitButton instanceof HTMLButtonElement)) return;

    const syncProofSubmissionState = () => {
        submitButton.disabled = proofInput.files?.length !== 1;
    };

    proofInput.addEventListener('change', syncProofSubmissionState);
    syncProofSubmissionState();
});

document.addEventListener('change', (event) => {
    const input = event.target;
    if (!(input instanceof HTMLInputElement)) return;

    const selector = input.closest('[data-payment-selector]');
    if (!selector) return;

    if (input.name === 'payment_method') {
        selector.querySelectorAll('.checkout-payment-option').forEach((option) => {
            const radio = option.querySelector('input[name="payment_method"]');
            option.classList.toggle('is-selected', radio?.checked ?? false);
        });

        selector.querySelectorAll('[data-payment-panel]').forEach((panel) => {
            panel.hidden = panel.dataset.paymentPanel !== input.value;
        });
        updateTransferBankInputs(selector, input.value);
    } else if (input.name === 'sender_bank') {
        selector.querySelectorAll('.checkout-sender-bank-option').forEach((option) => {
            const radio = option.querySelector('input[name="sender_bank"]');
            option.classList.toggle('is-selected', radio?.checked ?? false);
        });
    }
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
