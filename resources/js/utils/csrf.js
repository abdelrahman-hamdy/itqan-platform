/**
 * CSRF Token Utility
 * Centralized CSRF token management for all AJAX requests
 *
 * Usage:
 *   import { getCsrfToken, getCsrfHeaders, refreshCsrfToken } from './utils/csrf';
 *
 *   // Get token
 *   const token = getCsrfToken();
 *
 *   // Get headers for fetch
 *   const headers = getCsrfHeaders();
 *   fetch('/api/endpoint', { headers });
 *
 *   // Refresh token after session timeout
 *   await refreshCsrfToken();
 */

import { Logger } from './logger';

const CsrfLogger = Logger.create('CSRF');

/**
 * Cache for CSRF token to avoid repeated DOM queries
 */
let cachedToken = null;

/**
 * Get CSRF token from meta tag
 * Uses caching to avoid repeated DOM lookups
 *
 * @returns {string|null} CSRF token or null if not found
 */
export function getCsrfToken() {
    // Return cached token if available
    if (cachedToken) {
        return cachedToken;
    }

    // Try to get from meta tag (Laravel's standard approach)
    const metaTag = document.querySelector('meta[name="csrf-token"]');
    if (metaTag) {
        cachedToken = metaTag.getAttribute('content');
        return cachedToken;
    }

    // Fallback: Try to get from cookie (for SPA setups)
    const cookies = document.cookie.split(';');
    for (const cookie of cookies) {
        const [name, value] = cookie.trim().split('=');
        if (name === 'XSRF-TOKEN') {
            cachedToken = decodeURIComponent(value);
            return cachedToken;
        }
    }

    CsrfLogger.warn('CSRF token not found');
    return null;
}

/**
 * Get headers object with CSRF token for fetch requests
 *
 * @param {Object} additionalHeaders - Additional headers to merge
 * @returns {Object} Headers object with CSRF token
 */
export function getCsrfHeaders(additionalHeaders = {}) {
    const token = getCsrfToken();

    const headers = {
        'Content-Type': 'application/json',
        'Accept': 'application/json',
        'X-Requested-With': 'XMLHttpRequest',
        ...additionalHeaders,
    };

    if (token) {
        headers['X-CSRF-TOKEN'] = token;
    }

    return headers;
}

/**
 * Get headers object for FormData requests (multipart/form-data)
 * Does NOT set Content-Type - browser will set it with boundary
 *
 * @param {Object} additionalHeaders - Additional headers to merge
 * @returns {Object} Headers object with CSRF token
 */
export function getCsrfFormDataHeaders(additionalHeaders = {}) {
    const token = getCsrfToken();

    const headers = {
        'Accept': 'application/json',
        'X-Requested-With': 'XMLHttpRequest',
        ...additionalHeaders,
    };

    if (token) {
        headers['X-CSRF-TOKEN'] = token;
    }

    return headers;
}

/**
 * Clear cached token (useful when token might have changed)
 */
export function clearCachedToken() {
    cachedToken = null;
}

/**
 * Update the cached token with a new value
 * Also updates the meta tag if present
 *
 * @param {string} newToken - New CSRF token
 */
export function updateToken(newToken) {
    if (!newToken || typeof newToken !== 'string') {
        CsrfLogger.warn('Invalid token provided to updateToken');
        return;
    }

    cachedToken = newToken;

    // Update meta tag as well
    const metaTag = document.querySelector('meta[name="csrf-token"]');
    if (metaTag) {
        metaTag.setAttribute('content', newToken);
    }

    CsrfLogger.debug('CSRF token updated');
}

/**
 * Refresh CSRF token from server
 * Useful when token might have expired (419 error)
 *
 * @returns {Promise<string|null>} New token or null on failure
 */
export async function refreshCsrfToken() {
    try {
        // Clear cache first
        clearCachedToken();

        // Fetch new token from Laravel's sanctum/csrf-cookie or a custom endpoint
        const response = await fetch('/sanctum/csrf-cookie', {
            method: 'GET',
            credentials: 'same-origin',
        });

        if (response.ok) {
            // Token should now be in the cookie, try to get it
            const newToken = getCsrfToken();
            CsrfLogger.debug('CSRF token refreshed');
            return newToken;
        }

        CsrfLogger.error('Failed to refresh CSRF token', { status: response.status });
        return null;
    } catch (error) {
        CsrfLogger.error('Error refreshing CSRF token', error);
        return null;
    }
}

/**
 * Wrapper for fetch that automatically includes CSRF token
 * and handles 419 (CSRF token mismatch) errors
 *
 * @param {string} url - URL to fetch
 * @param {Object} options - Fetch options
 * @returns {Promise<Response>} Fetch response
 */
export async function csrfFetch(url, options = {}) {
    const isFormData = options.body instanceof FormData;

    const headers = isFormData
        ? getCsrfFormDataHeaders(options.headers)
        : getCsrfHeaders(options.headers);

    const fetchOptions = {
        ...options,
        headers,
        credentials: 'same-origin',
    };

    let response = await fetch(url, fetchOptions);

    // Handle CSRF token mismatch (419 error)
    if (response.status === 419) {
        CsrfLogger.warn('CSRF token expired, refreshing...');

        // Try to refresh token and retry once
        await refreshCsrfToken();

        const retryHeaders = isFormData
            ? getCsrfFormDataHeaders(options.headers)
            : getCsrfHeaders(options.headers);

        response = await fetch(url, {
            ...options,
            headers: retryHeaders,
            credentials: 'same-origin',
        });
    }

    return response;
}

/**
 * Add CSRF token to a FormData object
 *
 * @param {FormData} formData - FormData object to modify
 * @returns {FormData} Modified FormData with _token field
 */
export function addTokenToFormData(formData) {
    const token = getCsrfToken();
    if (token) {
        formData.append('_token', token);
    }
    return formData;
}

/**
 * Check if a 419 error is a CSRF token issue
 *
 * @param {Response|Error} error - Error to check
 * @returns {boolean} True if error is CSRF related
 */
export function isCsrfError(error) {
    if (error instanceof Response) {
        return error.status === 419;
    }
    if (error?.response?.status) {
        return error.response.status === 419;
    }
    return false;
}

export default {
    getCsrfToken,
    getCsrfHeaders,
    getCsrfFormDataHeaders,
    clearCachedToken,
    updateToken,
    refreshCsrfToken,
    csrfFetch,
    addTokenToFormData,
    isCsrfError,
};
