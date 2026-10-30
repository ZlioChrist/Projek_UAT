import React from 'react';

/**
 * Reusable Card Component buat UI yang konsisten ngab!
 * variant: 'bento' | 'glass' | 'premium'
 */
const Card = ({ children, variant = 'bento', className = '', ...props }) => {
    // Mapping variant ke class CSS yang udah distandarisasi
    const variantClasses = {
        bento: 'glass-bento p-[var(--spacing-card-p)]',
        glass: 'glass-morphism p-[var(--spacing-card-p)] rounded-[var(--radius-bento)]',
        premium: 'hero-tile p-6',
        plain: 'bg-white rounded-[var(--radius-bento)] shadow-sm border border-slate-100 p-[var(--spacing-card-p)]'
    };

    return (
        <div 
            className={`${variantClasses[variant]} ${className}`}
            {...props}
        >
            {children}
        </div>
    );
};

export default Card;
