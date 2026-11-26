<style>
    .fi-wn-auth-panels {
        display: grid;
        overflow: hidden;
        transition: height .36s cubic-bezier(.22, .72, .18, 1);
    }

    .fi-wn-auth-panel {
        grid-area: 1 / 1;
        min-width: 0;
        opacity: 1;
        transform: translateX(0);
        transition: transform .36s cubic-bezier(.22, .72, .18, 1), opacity .24s ease;
    }

    .fi-wn-auth-panel[hidden] { display: none !important; }

    .fi-wn-auth-panel.is-from-right,
    .fi-wn-auth-panel.is-going-right { opacity: 0; transform: translateX(36px); }

    .fi-wn-auth-panel.is-from-left,
    .fi-wn-auth-panel.is-going-left { opacity: 0; transform: translateX(-36px); }

    .fi-wn-auth-fieldset {
        display: grid;
        gap: .85rem;
    }

    .fi-wn-auth-section-title {
        margin: .25rem 0 0;
        color: #e9b65b;
        font-size: .75rem;
        font-weight: 800;
        letter-spacing: .12em;
        text-transform: uppercase;
    }

    .fi-wn-auth-required-mark {
        margin-left: .1rem;
        color: #f27b68;
        font-size: .8em;
        font-weight: 800;
        vertical-align: super;
    }

    .fi-wn-auth-field {
        display: grid;
        gap: .4rem;
        color: #e5d4c2;
        font-size: .85rem;
        font-weight: 600;
    }

    .fi-wn-auth-field[hidden] { display: none !important; }

    .fi-wn-auth-field input,
    .fi-wn-auth-field select,
    .fi-wn-auth-field textarea {
        width: 100%;
        border: 1px solid #594635;
        border-radius: 14px;
        outline: none;
        background: #1b1511;
        padding: .8rem .9rem;
        color: #f3e8dc;
        font: inherit;
    }

    .fi-wn-auth-field input:focus,
    .fi-wn-auth-field select:focus,
    .fi-wn-auth-field textarea:focus {
        border-color: #c08a5b;
        box-shadow: 0 0 0 3px rgba(192, 138, 91, .18);
    }

    .fi-wn-auth-field option { background: #1b1511; color: #f3e8dc; }

    [data-auth-heading][hidden] { display: none; }

    @media (prefers-reduced-motion: reduce) {
        .fi-wn-auth-panels,
        .fi-wn-auth-panel {
            transition: none;
            transform: none !important;
        }
    }
</style>
<script>
    document.addEventListener('DOMContentLoaded', () => {
        const page = document.querySelector('.fi-wn-login-page');
        const panelStack = page?.querySelector('.fi-wn-auth-panels');

        if (!page || !panelStack) return;

        const panels = {
            login: panelStack.querySelector('[data-auth-panel="login"]'),
            register: panelStack.querySelector('[data-auth-panel="register"]'),
        };
        const headings = {
            login: page.querySelector('[data-auth-heading="login"]'),
            register: page.querySelector('[data-auth-heading="register"]'),
        };
        const citySelect = page.querySelector('#merchant-city-select');
        const cityNameField = page.querySelector('#merchant-city-name-field');
        const cityNameInput = page.querySelector('#merchant-city-name');
        let activeMode = page.dataset.authMode;
        let isTransitioning = false;

        const updateTitle = mode => {
            document.title = `${mode === 'register' ? 'Daftar' : 'Masuk'} - WadahNgopi`;
        };

        updateTitle(activeMode);

        const updateCityInput = () => {
            if (!citySelect || !cityNameField || !cityNameInput) return;

            const needsNewCity = citySelect.value === 'other';
            cityNameField.hidden = !needsNewCity;
            cityNameInput.required = needsNewCity;
        };

        citySelect?.addEventListener('change', updateCityInput);
        updateCityInput();

        page.querySelectorAll('[data-auth-toggle]').forEach(button => {
            button.addEventListener('click', () => {
                const nextMode = button.dataset.authToggle;
                if (isTransitioning || nextMode === activeMode || !panels[nextMode]) return;

                const currentPanel = panels[activeMode];
                const nextPanel = panels[nextMode];
                const currentHeight = panelStack.getBoundingClientRect().height;
                const nextEntryClass = nextMode === 'register' ? 'is-from-right' : 'is-from-left';
                const currentExitClass = nextMode === 'register' ? 'is-going-left' : 'is-going-right';

                isTransitioning = true;
                panelStack.style.height = `${currentHeight}px`;
                nextPanel.hidden = false;
                nextPanel.inert = false;
                currentPanel.inert = true;
                nextPanel.style.zIndex = '2';
                currentPanel.style.zIndex = '1';
                nextPanel.classList.add(nextEntryClass);
                currentPanel.classList.add(currentExitClass);
                page.dataset.authMode = nextMode;
                updateTitle(nextMode);
                headings.login.hidden = nextMode === 'register';
                headings.register.hidden = nextMode === 'login';

                const nextHeight = nextPanel.scrollHeight;
                nextPanel.offsetWidth;
                requestAnimationFrame(() => {
                    panelStack.style.height = `${nextHeight}px`;
                    nextPanel.classList.remove(nextEntryClass);
                });

                window.setTimeout(() => {
                    currentPanel.hidden = true;
                    currentPanel.classList.remove(currentExitClass);
                    currentPanel.style.zIndex = '';
                    nextPanel.style.zIndex = '';
                    panelStack.style.height = 'auto';
                    activeMode = nextMode;
                    isTransitioning = false;
                    nextPanel.querySelector('input, select, textarea')?.focus({ preventScroll: true });
                }, window.matchMedia('(prefers-reduced-motion: reduce)').matches ? 0 : 360);
            });
        });
    });
</script>
