/**
 * LiveKit API Helper
 * Provides unified fetch utilities for all LiveKit modules
 * Uses global csrfFetch when available, with fallback to manual implementation
 */

const LiveKitAPI = {
    /**
     * Make an authenticated API request
     * @param {string} url - API endpoint
     * @param {Object} options - Fetch options
     * @returns {Promise<Response>}
     */
    async fetch(url, options = {}) {
        // Use global csrfFetch if available (from app.js bundle)
        if (window.csrfFetch) {
            return window.csrfFetch(url, options);
        }

        // Fallback implementation
        const csrfToken = window.getCsrfToken?.() ||
            document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '';

        const defaultHeaders = {
            'Content-Type': 'application/json',
            'Accept': 'application/json',
            'X-Requested-With': 'XMLHttpRequest',
            'X-CSRF-TOKEN': csrfToken
        };

        const config = {
            ...options,
            headers: {
                ...defaultHeaders,
                ...options.headers
            },
            credentials: 'same-origin'
        };

        return fetch(url, config);
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
