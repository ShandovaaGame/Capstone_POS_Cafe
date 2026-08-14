import './bootstrap';
import { createInertiaApp } from '@inertiajs/react';
import { createRoot } from 'react-dom/client';
import { route } from 'ziggy-js';
import { Ziggy } from './ziggy';
import '../css/app.css';

// Make route() available globally (used in components)
window.route = (name, params, absolute) => route(name, params, absolute, Ziggy);

const pages = import.meta.glob('./Pages/**/*.jsx');

createInertiaApp({
    resolve: name => pages[`./Pages/${name}.jsx`](),
    setup({ el, App, props }) {
        createRoot(el).render(<App {...props} />);
    },
    progress: { color: '#E8692A' },
});

if ('serviceWorker' in navigator) {
    window.addEventListener('load', () => navigator.serviceWorker.register('/sw.js'));
}
