import defaultTheme from 'tailwindcss/defaultTheme';
import forms from '@tailwindcss/forms';

const token = (name) => `rgb(var(--${name}) / <alpha-value>)`;

/** @type {import('tailwindcss').Config} */
export default {
    darkMode: 'class',
    safelist: [{ pattern: /^bg-(red|gold|sand)-(50|100|200|300|400|500|600|700|800|900)$/ }],
    content: [
        './vendor/laravel/framework/src/Illuminate/Pagination/resources/views/*.blade.php',
        './storage/framework/views/*.php',
        './resources/views/**/*.blade.php',
        './resources/js/**/*.{vue,js}',
    ],

    theme: {
        extend: {
            colors: {
                canvas: token('canvas'),
                surface: token('surface'),
                sunken: token('sunken'),
                line: { DEFAULT: token('line'), strong: token('line-strong') },
                ink: { DEFAULT: token('ink'), muted: token('ink-muted'), subtle: token('ink-subtle') },
                primary: {
                    DEFAULT: token('primary'),
                    hover: token('primary-hover'),
                    ink: token('primary-ink'),
                    soft: token('primary-soft'),
                },
                'on-primary': token('on-primary'),
                accent: { DEFAULT: token('accent'), soft: token('accent-soft'), ink: token('accent-ink') },
                'on-accent': token('on-accent'),
                success: { DEFAULT: token('success'), soft: token('success-soft') },
                warning: { DEFAULT: token('warning'), soft: token('warning-soft') },
                danger: { DEFAULT: token('danger'), soft: token('danger-soft') },
                info: { DEFAULT: token('info'), soft: token('info-soft') },
                focus: token('focus'),
                red: {
                    50: '#FDF3F2', 100: '#FBE4E2', 200: '#F6C6C2', 300: '#EE9C95', 400: '#E2665E', 500: '#CC3A33',
                    600: '#AA151B', 700: '#8E1117', 800: '#741016', 900: '#5E1115', 950: '#340608',
                },
                gold: {
                    50: '#FFFBEA', 100: '#FFF3C4', 200: '#FFE585', 300: '#FFD43D', 400: '#F1BF00', 500: '#D4A200',
                    600: '#A87C00', 700: '#7E5B03', 800: '#5E450A', 900: '#4A370D', 950: '#2A1E04',
                },
                sand: {
                    50: '#FBF6EC', 100: '#F4ECDD', 200: '#E8DDCB', 300: '#D3C5AE', 400: '#AE9F88', 500: '#8A7B68',
                    600: '#6A5D4E', 700: '#4F443A', 800: '#352D27', 900: '#241E1A', 950: '#1A1412',
                },
            },
            fontFamily: {
                sans: ['Geologica', ...defaultTheme.fontFamily.sans],
                display: ['"Source Serif 4"', 'Georgia', ...defaultTheme.fontFamily.serif],
            },
            fontSize: {
                display: ['clamp(2.5rem, 1.6rem + 4.5vw, 5.25rem)', { lineHeight: '1', letterSpacing: '-0.025em' }],
                'title-1': ['clamp(2rem, 1.55rem + 2.2vw, 3.25rem)', { lineHeight: '1.05', letterSpacing: '-0.02em' }],
                'title-2': ['clamp(1.5rem, 1.3rem + 1vw, 2.125rem)', { lineHeight: '1.15', letterSpacing: '-0.015em' }],
                'title-3': ['clamp(1.25rem, 1.18rem + 0.35vw, 1.5rem)', { lineHeight: '1.25', letterSpacing: '-0.01em' }],
            },
            borderRadius: {
                none: '0',
                sm: '2px',
                DEFAULT: '4px',
                md: '4px',
                lg: '10px',
                xl: '14px',
                full: '9999px',
            },
            boxShadow: {
                hairline: '0 1px 0 rgb(var(--shadow) / 0.05), 0 0 0 1px rgb(var(--line))',
                pop: '0 12px 32px -8px rgb(var(--shadow) / 0.28), 0 0 0 1px rgb(var(--line))',
            },
            transitionTimingFunction: {
                out: 'cubic-bezier(0.2, 0, 0, 1)',
                in: 'cubic-bezier(0.4, 0, 1, 1)',
            },
            keyframes: {
                'fade-in': { from: { opacity: '0' }, to: { opacity: '1' } },
                'pop-in': {
                    from: { opacity: '0', transform: 'translateY(4px) scale(0.98)' },
                    to: { opacity: '1', transform: 'none' },
                },
                'sheet-in': { from: { transform: 'translateX(-100%)' }, to: { transform: 'none' } },
                shimmer: { '100%': { transform: 'translateX(100%)' } },
            },
            animation: {
                'fade-in': 'fade-in 200ms cubic-bezier(0.2, 0, 0, 1) both',
                'pop-in': 'pop-in 200ms cubic-bezier(0.2, 0, 0, 1) both',
                'sheet-in': 'sheet-in 280ms cubic-bezier(0.2, 0, 0, 1) both',
                shimmer: 'shimmer 1.4s infinite',
            },
        },
    },

    plugins: [forms],
};
