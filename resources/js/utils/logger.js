/**
 * Secure Logger Utility
 * Provides environment-aware logging that:
 * - Only logs in development mode
 * - Sanitizes sensitive data
 * - Provides consistent formatting
 *
 * Usage:
 *   import { Logger } from './utils/logger';
 *   Logger.log('Message');
 *   Logger.warn('Warning');
 *   Logger.error('Error');
 *   Logger.debug('Debug info', { data: 'value' });
 */

// Determine if we're in development mode
const isDevelopment = () => {
    // Check Vite dev mode
    if (typeof import.meta !== 'undefined' && import.meta.env) {
        return import.meta.env.DEV || import.meta.env.MODE === 'development';
    }
    // Fallback to hostname check
    return window.location.hostname === 'localhost' ||
           window.location.hostname === '127.0.0.1' ||
           window.location.hostname.endsWith('.test') ||
           window.location.hostname.endsWith('.local');
};

// Sensitive keys that should be redacted
const SENSITIVE_KEYS = [
    'token', 'password', 'secret', 'key', 'auth', 'credential',
    'csrf', 'bearer', 'authorization', 'cookie', 'session',
    'api_key', 'apikey', 'private', 'access_token', 'refresh_token'
];

/**
 * Check if a key might contain sensitive data
 */
const isSensitiveKey = (key) => {
    const lowerKey = String(key).toLowerCase();
    return SENSITIVE_KEYS.some(sensitive => lowerKey.includes(sensitive));
};

/**
 * Recursively sanitize an object, redacting sensitive values
 */
const sanitizeObject = (obj, depth = 0) => {
    // Prevent infinite recursion
    if (depth > 10) return '[MAX_DEPTH]';

    if (obj === null || obj === undefined) return obj;

    // Handle primitive types
    if (typeof obj !== 'object') return obj;

    // Handle arrays
    if (Array.isArray(obj)) {
        return obj.map(item => sanitizeObject(item, depth + 1));
    }

    // Handle objects
    const sanitized = {};
    for (const [key, value] of Object.entries(obj)) {
        if (isSensitiveKey(key)) {
            sanitized[key] = '[REDACTED]';
        } else if (typeof value === 'object' && value !== null) {
            sanitized[key] = sanitizeObject(value, depth + 1);
        } else {
            sanitized[key] = value;
        }
    }
    return sanitized;
};

/**
 * Format a log message with timestamp and context
 */
const formatMessage = (level, context, message) => {
    const timestamp = new Date().toISOString().split('T')[1].split('.')[0];
    const prefix = context ? `[${context}]` : '';
    return `${timestamp} ${level} ${prefix} ${message}`;
};

/**
 * Logger class with environment-aware logging
 */
export const Logger = {
    _isDev: null,

    /**
     * Check if development mode (cached)
     */
    get isDev() {
        if (this._isDev === null) {
            this._isDev = isDevelopment();
        }
        return this._isDev;
    },

    /**
     * Force set development mode (for testing)
     */
    setDev(isDev) {
        this._isDev = isDev;
    },

    /**
     * Log info message (dev only)
     */
    log(message, data = null, context = '') {
        if (!this.isDev) return;

        const formatted = formatMessage('INFO', context, message);
        if (data) {
            console.log(formatted, sanitizeObject(data));
        } else {
            console.log(formatted);
        }
    },

    /**
     * Log debug message (dev only)
     */
    debug(message, data = null, context = '') {
        if (!this.isDev) return;

        const formatted = formatMessage('DEBUG', context, message);
        if (data) {
            console.debug(formatted, sanitizeObject(data));
        } else {
            console.debug(formatted);
        }
    },

    /**
     * Log warning message (always shown)
     */
    warn(message, data = null, context = '') {
        const formatted = formatMessage('WARN', context, message);
        if (data) {
            console.warn(formatted, sanitizeObject(data));
        } else {
            console.warn(formatted);
        }
    },

    /**
     * Log error message (always shown)
     */
    error(message, error = null, context = '') {
        const formatted = formatMessage('ERROR', context, message);
        if (error) {
            // For Error objects, extract useful info
            if (error instanceof Error) {
                console.error(formatted, {
                    message: error.message,
                    name: error.name,
                    stack: this.isDev ? error.stack : '[Stack hidden in production]'
                });
            } else {
                console.error(formatted, sanitizeObject(error));
            }
        } else {
            console.error(formatted);
        }
    },

    /**
     * Log a group of related messages (dev only)
     */
    group(label, fn, context = '') {
        if (!this.isDev) return;

        console.group(formatMessage('GROUP', context, label));
        try {
            fn();
        } finally {
            console.groupEnd();
        }
    },

    /**
     * Time an operation (dev only)
     */
    time(label) {
        if (!this.isDev) return;
        console.time(label);
    },

    timeEnd(label) {
        if (!this.isDev) return;
        console.timeEnd(label);
    },

    /**
     * Create a namespaced logger
     */
    create(namespace) {
        return {
            log: (msg, data) => this.log(msg, data, namespace),
            debug: (msg, data) => this.debug(msg, data, namespace),
            warn: (msg, data) => this.warn(msg, data, namespace),
            error: (msg, err) => this.error(msg, err, namespace),
            group: (label, fn) => this.group(label, fn, namespace),
            time: (label) => this.time(`${namespace}:${label}`),
            timeEnd: (label) => this.timeEnd(`${namespace}:${label}`)
        };
    }
};

// Create pre-configured loggers for common modules
export const ChatLogger = Logger.create('Chat');
export const LiveKitLogger = Logger.create('LiveKit');
export const SessionLogger = Logger.create('Session');
export const AuthLogger = Logger.create('Auth');

// Make available globally for inline scripts
if (typeof window !== 'undefined') {
    window.Logger = Logger;
}

export default Logger;
