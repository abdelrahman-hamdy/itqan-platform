/**
 * Standardized Error Handler Utility
 * Provides consistent error handling, categorization, and user-friendly messages
 *
 * Usage:
 *   import { ErrorHandler, handleApiError } from './utils/error-handler';
 *
 *   try {
 *       await fetch('/api/endpoint');
 *   } catch (error) {
 *       handleApiError(error); // Auto-shows toast with appropriate message
 *   }
 */

import { Logger } from './logger';
import { LABELS_AR, TIMEOUTS } from './constants';

const ErrorLogger = Logger.create('ErrorHandler');

/**
 * Error categories for classification
 */
export const ERROR_CATEGORY = {
    NETWORK: 'network',
    VALIDATION: 'validation',
    AUTHENTICATION: 'authentication',
    AUTHORIZATION: 'authorization',
    NOT_FOUND: 'not_found',
    SERVER: 'server',
    TIMEOUT: 'timeout',
    UNKNOWN: 'unknown',
};

/**
 * HTTP status code to error category mapping
 */
const STATUS_CATEGORY_MAP = {
    400: ERROR_CATEGORY.VALIDATION,
    401: ERROR_CATEGORY.AUTHENTICATION,
    403: ERROR_CATEGORY.AUTHORIZATION,
    404: ERROR_CATEGORY.NOT_FOUND,
    408: ERROR_CATEGORY.TIMEOUT,
    422: ERROR_CATEGORY.VALIDATION,
    429: ERROR_CATEGORY.SERVER, // Rate limiting
    500: ERROR_CATEGORY.SERVER,
    502: ERROR_CATEGORY.SERVER,
    503: ERROR_CATEGORY.SERVER,
    504: ERROR_CATEGORY.TIMEOUT,
};

/**
 * Get error category from HTTP status code
 */
const getCategoryFromStatus = (status) => {
    return STATUS_CATEGORY_MAP[status] || ERROR_CATEGORY.UNKNOWN;
};

/**
 * Get user-friendly message based on error category
 */
const getMessageForCategory = (category) => {
    const messages = {
        [ERROR_CATEGORY.NETWORK]: LABELS_AR.ERRORS.NETWORK,
        [ERROR_CATEGORY.VALIDATION]: LABELS_AR.ERRORS.VALIDATION,
        [ERROR_CATEGORY.AUTHENTICATION]: LABELS_AR.ERRORS.SESSION_EXPIRED,
        [ERROR_CATEGORY.AUTHORIZATION]: LABELS_AR.ERRORS.PERMISSION,
        [ERROR_CATEGORY.NOT_FOUND]: LABELS_AR.ERRORS.NOT_FOUND,
        [ERROR_CATEGORY.SERVER]: LABELS_AR.ERRORS.SERVER,
        [ERROR_CATEGORY.TIMEOUT]: LABELS_AR.ERRORS.TIMEOUT,
        [ERROR_CATEGORY.UNKNOWN]: LABELS_AR.ERRORS.UNKNOWN,
    };
    return messages[category] || LABELS_AR.ERRORS.UNKNOWN;
};

/**
 * Parse error response from various sources
 */
const parseErrorResponse = async (error) => {
    // Network errors (no response)
    if (!error.response && error.message) {
        if (error.message.includes('NetworkError') || error.message.includes('Failed to fetch')) {
            return {
                category: ERROR_CATEGORY.NETWORK,
                message: LABELS_AR.ERRORS.NETWORK,
                details: null,
            };
        }
        if (error.message.includes('timeout') || error.name === 'AbortError') {
            return {
                category: ERROR_CATEGORY.TIMEOUT,
                message: LABELS_AR.ERRORS.TIMEOUT,
                details: null,
            };
        }
    }

    // Fetch Response object
    if (error instanceof Response) {
        const category = getCategoryFromStatus(error.status);
        let details = null;

        try {
            const contentType = error.headers.get('content-type');
            if (contentType && contentType.includes('application/json')) {
                details = await error.json();
            }
        } catch (e) {
            // Ignore JSON parsing errors
        }

        return {
            category,
            message: details?.message || getMessageForCategory(category),
            details,
            status: error.status,
        };
    }

    // Error with response property (axios-style)
    if (error.response) {
        const status = error.response.status;
        const category = getCategoryFromStatus(status);
        const details = error.response.data;

        return {
            category,
            message: details?.message || getMessageForCategory(category),
            details,
            status,
        };
    }

    // Standard Error object
    if (error instanceof Error) {
        return {
            category: ERROR_CATEGORY.UNKNOWN,
            message: error.message || LABELS_AR.ERRORS.UNKNOWN,
            details: null,
        };
    }

    // Unknown error type
    return {
        category: ERROR_CATEGORY.UNKNOWN,
        message: LABELS_AR.ERRORS.UNKNOWN,
        details: null,
    };
};

/**
 * Show toast notification for error
 */
const showErrorToast = (message, options = {}) => {
    if (typeof window !== 'undefined' && window.toast) {
        window.toast.error(message, {
            duration: TIMEOUTS.TOAST_ERROR,
            ...options,
        });
    } else {
        // Fallback to console
        ErrorLogger.error('Toast not available, error:', message);
    }
};

/**
 * Main error handler class
 */
export const ErrorHandler = {
    /**
     * Handle an API error with automatic categorization and toast display
     * @param {Error|Response} error - The error to handle
     * @param {Object} options - Options for handling
     * @param {boolean} options.showToast - Whether to show toast (default: true)
     * @param {string} options.context - Context for logging
     * @param {Function} options.onAuthError - Callback for authentication errors
     * @param {Function} options.onValidationError - Callback for validation errors
     * @returns {Promise<Object>} Parsed error information
     */
    async handle(error, options = {}) {
        const {
            showToast = true,
            context = '',
            onAuthError = null,
            onValidationError = null,
        } = options;

        const parsed = await parseErrorResponse(error);

        // Log the error
        ErrorLogger.error(`Error in ${context || 'unknown context'}`, {
            category: parsed.category,
            message: parsed.message,
            status: parsed.status,
        });

        // Handle specific error types
        if (parsed.category === ERROR_CATEGORY.AUTHENTICATION && onAuthError) {
            onAuthError(parsed);
        } else if (parsed.category === ERROR_CATEGORY.VALIDATION && onValidationError) {
            onValidationError(parsed);
        }

        // Show toast notification
        if (showToast) {
            showErrorToast(parsed.message);
        }

        return parsed;
    },

    /**
     * Handle validation errors with field-specific messages
     * @param {Object} errors - Validation errors object from Laravel
     * @param {Function} setFieldError - Optional callback to set field errors
     */
    handleValidation(errors, setFieldError = null) {
        if (!errors || typeof errors !== 'object') {
            showErrorToast(LABELS_AR.ERRORS.UNKNOWN);
            return;
        }

        const messages = [];

        for (const [field, fieldErrors] of Object.entries(errors)) {
            const errorList = Array.isArray(fieldErrors) ? fieldErrors : [fieldErrors];

            if (setFieldError) {
                setFieldError(field, errorList[0]);
            }

            messages.push(...errorList);
        }

        // Show first error as toast
        if (messages.length > 0) {
            showErrorToast(messages[0]);
        }

        return messages;
    },

    /**
     * Create an error boundary wrapper for async functions
     * @param {Function} fn - Async function to wrap
     * @param {Object} options - Error handling options
     * @returns {Function} Wrapped function
     */
    wrapAsync(fn, options = {}) {
        return async (...args) => {
            try {
                return await fn(...args);
            } catch (error) {
                await this.handle(error, options);
                throw error; // Re-throw for caller handling
            }
        };
    },

    /**
     * Check if error is of a specific category
     */
    isCategory(error, category) {
        return error?.category === category;
    },

    /**
     * Check if error is recoverable (should retry)
     */
    isRecoverable(error) {
        const recoverableCategories = [
            ERROR_CATEGORY.NETWORK,
            ERROR_CATEGORY.TIMEOUT,
        ];
        return recoverableCategories.includes(error?.category);
    },
};

/**
 * Convenience function for handling API errors
 * @param {Error|Response} error - The error to handle
 * @param {string} context - Context for logging
 * @returns {Promise<Object>} Parsed error information
 */
export async function handleApiError(error, context = '') {
    return ErrorHandler.handle(error, { context });
}

/**
 * Convenience function for handling form validation errors
 * @param {Object} errors - Validation errors from Laravel
 * @param {Function} setFieldError - Optional callback for field errors
 */
export function handleValidationError(errors, setFieldError = null) {
    return ErrorHandler.handleValidation(errors, setFieldError);
}

/**
 * Fetch wrapper with automatic error handling
 * @param {string} url - URL to fetch
 * @param {Object} options - Fetch options
 * @returns {Promise<Response>} Fetch response
 */
export async function safeFetch(url, options = {}) {
    const controller = new AbortController();
    const timeout = options.timeout || TIMEOUTS.FETCH_TIMEOUT;

    const timeoutId = setTimeout(() => controller.abort(), timeout);

    try {
        const response = await fetch(url, {
            ...options,
            signal: controller.signal,
        });

        clearTimeout(timeoutId);

        if (!response.ok) {
            throw response;
        }

        return response;
    } catch (error) {
        clearTimeout(timeoutId);
        throw error;
    }
}

// Export error categories for external use
export { ERROR_CATEGORY as ErrorCategory };

export default ErrorHandler;
