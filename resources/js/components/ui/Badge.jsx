import React from 'react';

/**
 * Reusable Badge Component biar ga berantakan stylingnya ngab!
 * variant: 'solid' | 'glass' | 'outline'
 * color: 'emerald' | 'amber' | 'rose' | 'slate' | 'default'
 */
const Badge = ({ children, variant = 'solid', color = 'default', icon, className = '', ...props }) => {
    
    // props styling buat badge nih
    const baseClass = "inline-flex items-center gap-1.5 px-[var(--spacing-badge-x)] py-[var(--spacing-badge-y)] text-[0.65rem] font-bold uppercase tracking-widest rounded-[var(--radius-pill)] transition-all";
    
    const colorStyles = {
        solid: {
            emerald: 'bg-emerald-500 text-white shadow-sm',
            amber: 'bg-amber-500 text-white shadow-sm',
            rose: 'bg-rose-500 text-white shadow-sm',
            slate: 'bg-slate-500 text-white shadow-sm',
            default: 'bg-slate-100 text-slate-600',
        },
        glass: {
            emerald: 'bg-emerald-500/10 text-emerald-600 border border-emerald-500/20 backdrop-blur-md',
            amber: 'bg-amber-500/10 text-amber-600 border border-amber-500/20 backdrop-blur-md',
            rose: 'bg-rose-500/10 text-rose-600 border border-rose-500/20 backdrop-blur-md',
            slate: 'bg-slate-500/10 text-slate-600 border border-slate-500/20 backdrop-blur-md',
            default: 'glass-badge', // pakai class bawaan dr badges.css
        }
    };

    const finalClass = `${baseClass} ${colorStyles[variant]?.[color] || colorStyles.solid.default} ${className}`;

    return (
        <span className={finalClass} {...props}>
            {icon && <span className="text-sm">{icon}</span>}
            {children}
        </span>
    );
};

export default Badge;
