/**
 * LiveKit API Helper
 * Provides unified fetch utilities for all LiveKit modules
 * Uses global csrfFetch when available, with fallback to manual implementation
 */

const LiveKitAPI = {
    /**
     * Make an authenticated API request.
     * Delegates to the Vite-bundled csrfFetch when available (has built-in
     * 419 retry). Fallback reads the meta tag token and retries once on 419.
     * @param {string} url - API endpoint
     * @param {Object} options - Fetch options
     * @returns {Promise<Response>}
     */
    async fetch(url, options = {}) {
        // Use global csrfFetch if available (from app.js bundle) — has 419 retry
        if (window.csrfFetch) {
            return window.csrfFetch(url, options);
        }

        // Fallback: read fresh token from DOM on every call
        const buildConfig = () => {
            const csrfToken = window.getCsrfToken?.() ||
                document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '';
            return {
                ...options,
                headers: {
                    'Content-Type': 'application/json',
                    'Accept': 'application/json',
                    'X-Requested-With': 'XMLHttpRequest',
                    'X-CSRF-TOKEN': csrfToken,
                    ...options.headers
                },
                credentials: 'same-origin'
            };
        };

        let response = await fetch(url, buildConfig());

        // Retry once on 419 (expired CSRF token) — refresh token first
        if (response.status === 419) {
            try {
                await fetch('/sanctum/csrf-cookie', { method: 'GET', credentials: 'same-origin' });
                const match = document.cookie.match('(?:^|; )XSRF-TOKEN=([^;]*)');
                if (match) {
                    const token = decodeURIComponent(match[1]);
                    document.querySelector('meta[name="csrf-token"]')?.setAttribute('content', token);
                }
            } catch (_) { /* best effort */ }
            response = await fetch(url, buildConfig());
        }

        return response;
    },

    /**
     * Make a POST request with JSON body
     * @param {string} url - API endpoint
     * @param {Object} data - Request body data
     * @returns {Promise<Response>}
     */
    async post(url, data = {}) {
        return this.fetch(url, {
            method: 'POST',
            body: JSON.stringify(data)
        });
    },

    /**
     * Make a GET request
     * @param {string} url - API endpoint
     * @returns {Promise<Response>}
     */
    async get(url) {
        return this.fetch(url, {
            method: 'GET'
        });
    },

    /**
     * Get session context from window globals
     * @returns {Object} Session context
     */
    getSessionContext() {
        return {
            sessionId: window.sessionId,
            sessionType: window.sessionType || 'quran',
            roomName: window.meetingRoomName
        };
    }
};

// Expose globally for all LiveKit modules
window.LiveKitAPI = LiveKitAPI;
