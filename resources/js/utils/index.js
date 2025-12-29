/**
 * Utilities Index
 * Central export point for all utility modules
 *
 * Usage:
 *   import { Logger, ErrorHandler, getCsrfToken, TIMEOUTS } from './utils';
 */

// Logger
export { Logger, ChatLogger, LiveKitLogger, SessionLogger, AuthLogger } from './logger';

// Constants
export {
    TIMEOUTS,
    LIMITS,
    STATUS,
    CSS_CLASSES,
    ICONS,
    LABELS_AR,
    API,
    STORAGE_KEYS,
} from './constants';

// Error Handler
export {
    ErrorHandler,
    ErrorCategory,
    handleApiError,
    handleValidationError,
    safeFetch,
} from './error-handler';

// CSRF
export {
    getCsrfToken,
    getCsrfHeaders,
    getCsrfFormDataHeaders,
    clearCachedToken,
    updateToken,
    refreshCsrfToken,
    csrfFetch,
    addTokenToFormData,
    isCsrfError,
} from './csrf';

// Default export with all utilities grouped
import { Logger } from './logger';
import Constants from './constants';
import ErrorHandler from './error-handler';
import Csrf from './csrf';

export default {
    Logger,
    Constants,
    ErrorHandler,
    Csrf,
};
