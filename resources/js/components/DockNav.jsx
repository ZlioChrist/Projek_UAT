import React from 'react';

const DockItem = ({ icon, label, url, active, onClick }) => {
    const handleClick = (event) => {
        if (onClick) {
            onClick(event);
            return;
        }

        if (window.Livewire) {
            event.preventDefault();
            window.hapticFeedback?.('light');
            window.Livewire.navigate(url);
        }
    };

    const currentIcon = active ? icon.replace('ph ', 'ph-fill ') : icon;

    return (
        <a
            href={url}
            onClick={handleClick}
            aria-label={label}
            aria-current={active ? 'page' : undefined}
            className={`nav-item ${active ? 'active' : ''}`}
        >
            <i className={currentIcon} aria-hidden="true"></i>
            <span className="nav-label">{label}</span>
        </a>
    );
};

const DockNav = ({ currentRoute, routes, isAuthenticated }) => {
    const items = [
        { id: 'home', label: 'Beranda', icon: 'ph ph-house', url: routes.home },
        { id: 'roastery', label: 'Roastery', icon: 'ph ph-coffee-bean', url: routes.roastery },
        { id: 'explore', label: 'Jelajahi', icon: 'ph ph-compass', url: routes.explore },
        { id: 'information', label: 'Info', icon: 'ph ph-newspaper', url: routes.information },
    ];

    const authenticated = isAuthenticated === true || isAuthenticated === 'true';
    items.push(authenticated
        ? { id: 'account', label: 'Akun', icon: 'ph ph-user-circle', url: routes.account }
        : { id: 'login', label: 'Masuk', icon: 'ph ph-user-circle', url: routes.login });

    const navClass = 'bottom-nav dock-nav-home';

    return (
        <div className="contents">
            <nav
                className={navClass}
                aria-label="Navigasi utama"
            >
                {items.map((item) => (
                    <DockItem
                        key={item.id}
                        {...item}
                        active={
                            currentRoute === item.id
                            || (item.id === 'explore' && currentRoute.startsWith('cafes.'))
                            || (item.id === 'roastery' && currentRoute.startsWith('roastery.'))
                            || (item.id === 'information' && currentRoute.startsWith('information'))
                            || (item.id === 'account' && currentRoute === 'account')
                            || (item.id === 'login' && ['login', 'register'].includes(currentRoute))
                        }
                    />
                ))}
            </nav>
        </div>
    );
};

export default DockNav;
