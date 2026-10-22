import '../css/app.css';

import { createInertiaApp } from '@inertiajs/react';
import { resolvePageComponent } from 'laravel-vite-plugin/inertia-helpers';
import { createRoot } from 'react-dom/client';
import { route as routeFn } from 'ziggy-js';
import { initializeTheme } from './hooks/default/use-appearance';
import { ensureToken } from '@/api/client';

declare global {
    const route: typeof routeFn;
}

const appName = import.meta.env.VITE_APP_NAME || 'Laravel';

const PLAZAS = ['test00', 'merida', 'norte', 'cdmx'];

function detectPlazaFromUrl(): string | null {
    const parts = window.location.pathname.split('/').filter(Boolean);
    return parts.find((p) => PLAZAS.includes(p)) ?? null;
}

const plazaFromUrl = detectPlazaFromUrl();
if (plazaFromUrl) {
    const plazaAnterior = localStorage.getItem('plaza');
    if (plazaAnterior !== plazaFromUrl) {
        localStorage.setItem('plaza', plazaFromUrl);
        localStorage.removeItem('api_token');
    }
    
}

ensureToken().finally(() => {
    createInertiaApp({
        title: (title) => `${title} - ${appName}`,
        resolve: (name) =>
            resolvePageComponent(
                `./pages/${name}.tsx`,
                import.meta.glob('./pages/**/*.tsx')
            ),
        setup({ el, App, props }) {
            const root = createRoot(el);
            root.render(<App {...props} />);
        },
        progress: {
            color: '#4B5563',
        },
    });
});

initializeTheme();