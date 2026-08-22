import defaultTheme from 'tailwindcss/defaultTheme';
import forms from '@tailwindcss/forms';

/** @type {import('tailwindcss').Config} */
export default {
    content: [
        './vendor/laravel/framework/src/Illuminate/Pagination/resources/views/*.blade.php',
        './storage/framework/views/*.php',
        './resources/views/**/*.blade.php',
        './resources/js/**/*.js',
    ],

    theme: {
        extend: {
            colors: {
                background: 'var(--background)',
                surface: 'var(--surface)',
                'surface-muted': 'var(--surface-muted)',
                'surface-high': 'var(--surface-high)',
                'border-strong': 'var(--border-strong)',
                'on-surface': 'var(--on-surface)',
                'on-surface-variant': 'var(--on-surface-variant)',
                primary: 'var(--primary)',
                'primary-text': 'var(--primary-text)',
                'on-primary': 'var(--on-primary)',
                success: 'var(--success)',
                error: 'var(--error)',
                'error-solid': 'var(--error-solid)',
            },
            fontFamily: {
                sans: ['Inter', ...defaultTheme.fontFamily.sans],
                inter: ['Inter', ...defaultTheme.fontFamily.sans],
            },
            fontSize: {
                'display-price': ['48px', { lineHeight: '56px', fontWeight: '700' }],
                'headline-lg': ['32px', { lineHeight: '40px', fontWeight: '600' }],
                'headline-md': ['24px', { lineHeight: '32px', fontWeight: '600' }],
                'body-lg': ['18px', { lineHeight: '28px', fontWeight: '400' }],
                'body-md': ['16px', { lineHeight: '24px', fontWeight: '400' }],
                'label-caps': ['12px', { lineHeight: '16px', fontWeight: '700', letterSpacing: '0.1em' }],
                'label-sm': ['11px', { lineHeight: '14px', fontWeight: '500' }],
            },
            spacing: {
                'touch-target-min': '56px',
                'panel-padding': '24px',
                'safe-margin': '16px',
                'stack-gap': '8px',
                'grid-gutter': '1px',
            },
            minHeight: {
                'touch-target-min': '56px',
            },
            minWidth: {
                'touch-target-min': '56px',
            },
            borderRadius: {
                sm: '4px',
                DEFAULT: '8px',
                md: '8px',
                lg: '8px',
                full: '9999px',
            },
            boxShadow: {
                none: 'none',
            },
        },
    },

    plugins: [forms],
};
