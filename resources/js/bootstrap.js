import axios from 'axios';
window.axios = axios;

window.axios.defaults.headers.common['X-Requested-With'] = 'XMLHttpRequest';

// nangkep error biar user ga emosi ngab
axios.interceptors.response.use(
    response => response,
    error => {
        if (error.response && (error.response.status === 429 || error.response.status === 422)) {
            const msg = error.response.data?.message || 'Terjadi kesalahan, coba lagi ngab.';
            window.dispatchEvent(new CustomEvent('toast', { detail: { message: msg, type: 'warning' } }));
        }
        return Promise.reject(error);
    }
);

// Wrapper buat native fetch API (karena banyak React component pake fetch)
const originalFetch = window.fetch;
window.fetch = async function(...args) {
    const response = await originalFetch.apply(this, args);
    if (response.status === 429 || response.status === 422) {
        try {
            const cloned = response.clone();
            const data = await cloned.json();
            const msg = data.message || 'Terjadi kesalahan, coba lagi ngab.';
            window.dispatchEvent(new CustomEvent('toast', { detail: { message: msg, type: 'warning' } }));
        } catch (e) {
            // Abaikan kalo bukan JSON
        }
    }
    return response;
};
