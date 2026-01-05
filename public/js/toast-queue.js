/**
 * Toast Queue Bootstrap Script
 *
 * This script provides an early-loading window.toast API that queues notifications
 * until the main Alpine.js toast system initializes.
 *
 * Load this script in <head> BEFORE any other JavaScript that might call window.toast.
 *
 * When the main toast-container component loads, it will:
 * 1. Check for window.__notificationQueue
 * 2. Display any queued notifications
 * 3. Replace these stub methods with the real implementation
 */
(function() {
    'use strict';

    // Only create queue if toast system is not already initialized
    if (window.toast && window.toast._initialized) {
        return;
    }

    // Initialize notification queue
    window.__notificationQueue = window.__notificationQueue || [];

    /**
     * Queue a notification for later display
     * @param {string} type - Notification type (success, error, warning, info, meeting)
     * @param {string} message - Notification message
     * @param {Object} options - Additional options
     */
    function queueNotification(type, message, options) {
        if (!message) return;

        window.__notificationQueue.push({
            type: type,
            message: message,
            duration: options?.duration,
            description: options?.description,
            timestamp: Date.now(),
            ...options
        });
    }

    /**
     * Stub toast API that queues notifications
     * This will be replaced by the real implementation when toast-container initializes
     */
    window.toast = {
        _initialized: false,

        /**
         * Show a toast with custom options
         * @param {Object} options - Toast options {type, message, duration, description}
         */
        show: function(options) {
            if (!options) return;
            queueNotification(
                options.type || 'info',
                options.message,
                options
            );
        },

        /**
         * Show a success toast
         * @param {string} message - Success message
         * @param {Object} options - Additional options
         */
        success: function(message, options) {
            queueNotification('success', message, options);
        },

        /**
         * Show an error toast
         * @param {string} message - Error message
         * @param {Object} options - Additional options
         */
        error: function(message, options) {
            queueNotification('error', message, options);
        },

        /**
         * Show a warning toast
         * @param {string} message - Warning message
         * @param {Object} options - Additional options
         */
        warning: function(message, options) {
            queueNotification('warning', message, options);
        },

        /**
         * Show an info toast
         * @param {string} message - Info message
         * @param {Object} options - Additional options
         */
        info: function(message, options) {
            queueNotification('info', message, options);
        },

        /**
         * Show a meeting-related toast (for participant events)
         * @param {string} message - Meeting notification message
         * @param {Object} options - Additional options
         */
        meeting: function(message, options) {
            queueNotification('meeting', message, { duration: 3000, ...options });
        },

        /**
         * Clear all queued notifications
         */
        clear: function() {
            window.__notificationQueue = [];
        }
    };
})();
