import React from 'react';
import { motion } from 'motion/react';

/**
 * Reusable Button Component dengan micro-interactions ngab!
 * variant: 'primary' | 'premium' | 'ghost' | 'glass'
 */
const Button = ({ 
    children, 
    variant = 'primary', 
    icon, 
    onClick, 
    disabled = false, 
    className = '', 
    ...props 
}) => {
    
    // Animasi klik muter tipis biar ga bosen
    const animationProps = {
        whileTap: disabled ? {} : { scale: 0.95 },
        whileHover: disabled ? {} : { y: -2 }
    };

    const baseClass = "relative inline-flex items-center justify-center gap-2 font-bold text-sm transition-all disabled:opacity-50 disabled:cursor-not-allowed";
    
    const variantStyles = {
        primary: "bg-amber-500 hover:bg-amber-600 text-white px-6 py-3 rounded-[var(--radius-button)] shadow-sm hover:shadow-md",
        premium: "bg-gradient-to-r from-amber-500 to-orange-500 text-white px-6 py-3 rounded-[var(--radius-button)] shadow-lg hover:shadow-xl",
        ghost: "bg-transparent hover:bg-slate-100 text-slate-700 px-4 py-2 rounded-[var(--radius-button)]",
        glass: "bg-white/10 hover:bg-white/20 border border-white/20 text-white px-6 py-3 rounded-[var(--radius-button)] backdrop-blur-md"
    };

    const finalClass = `${baseClass} ${variantStyles[variant]} ${className}`;

    return (
        <motion.button
            onClick={onClick}
            disabled={disabled}
            className={finalClass}
            {...animationProps}
            {...props}
        >
            {icon && <i className={`${icon} text-lg`}></i>}
            {children}
        </motion.button>
    );
};

export default Button;
