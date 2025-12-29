import Echo from 'laravel-echo';

import Pusher from 'pusher-js';
window.Pusher = Pusher;

window.Echo = new Echo({
    broadcaster: 'reverb',
    key: import.meta.env.VITE_REVERB_APP_KEY,
    wsHost: import.meta.env.VITE_REVERB_HOST ?? window.location.hostname,
    wsPort: Number(import.meta.env.VITE_REVERB_PORT ?? 8085),
    wssPort: Number(import.meta.env.VITE_REVERB_PORT ?? 8085),
    forceTLS: false,
    scheme: import.meta.env.VITE_REVERB_SCHEME ?? (window.location.protocol === 'https:' ? 'https' : 'http'),
    enabledTransports: ['ws', 'wss'],
    authEndpoint: '/broadcasting/auth',
    auth: {
        headers: {
            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.getAttribute('content'),
        },
    },
});

/**
 * Ensure Echo is registered with Livewire for real-time events
 * (Merged from livewire-echo.js)
 */
document.addEventListener('DOMContentLoaded', () => {
    // Livewire v3 should auto-detect Echo, but ensure it's set
    const checkLivewire = setInterval(() => {
        if (typeof window.Livewire !== 'undefined') {
            clearInterval(checkLivewire);
            if (!window.Livewire.echo) {
                window.Livewire.echo = window.Echo;
            }
        }
    }, 100);
});
