import './bootstrap';
import React, { StrictMode } from 'react';
import { createRoot } from 'react-dom/client';
import DockNav from './components/DockNav';
import WfcScoreSection from './components/WfcScoreSection';
import VibeMeter from './components/VibeMeter';
import CheckInButton from './components/CheckInButton';
import BadgeShowcase from './components/BadgeShowcase';
import TongkronganCreate from './components/TongkronganCreate';
import TongkronganView from './components/TongkronganView';

const components = {
    'bottom-nav-react': DockNav,
    'wfc-score-react': WfcScoreSection,
    'vibe-meter-react': VibeMeter,
    'check-in-react': CheckInButton,
    'badge-showcase-react': BadgeShowcase,
    'tongkrongan-create-react': TongkronganCreate,
    'tongkrongan-view-react': TongkronganView,
};

// Root Registry buat nyimpen instance React yang aktif
// Biar browser user ga nangis karena memory bocor ngab
const reactRoots = new Map();

const mountReactComponents = () => {
    Object.entries(components).forEach(([id, Component]) => {
        const rootElement = document.getElementById(id);
        if (rootElement) {
            // Kumpulin data-* attribute jadi props
            const props = Object.keys(rootElement.dataset).reduce((acc, key) => {
                try {
                    acc[key] = JSON.parse(rootElement.dataset[key]);
                } catch (e) {
                    acc[key] = rootElement.dataset[key];
                }
                return acc;
            }, {});

            // Kalo sebelumnya udah ada root di elemen ini (meski jarang kejadian karena DOM di-replace), unmount dulu aja buat aman
            if (reactRoots.has(id)) {
                reactRoots.get(id).unmount();
                reactRoots.delete(id);
            }

            const root = createRoot(rootElement);
            root.render(
                <StrictMode>
                    <Component {...props} />
                </StrictMode>
            );
            
            // Simpen root-nya ke registry
            reactRoots.set(id, root);
        }
    });
};

// Mount pas pertama kali DOM kelar di-load
document.addEventListener('DOMContentLoaded', () => {
    mountReactComponents();
});

// Bersihin event listener & unmount komponen lama ngab
// Dipanggil TEPAT SEBELUM Livewire ganti halaman
document.addEventListener('livewire:navigating', () => {
    reactRoots.forEach((root, id) => {
        root.unmount();
    });
    // Kosongin registry
    reactRoots.clear();
});

// Re-initialize React setelah Livewire kelar navigasi & DOM baru udah ada
document.addEventListener('livewire:navigated', () => {
    mountReactComponents();
});
