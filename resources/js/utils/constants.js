/**
 * Application Constants
 * Centralized configuration values to eliminate magic numbers/strings
 */

/**
 * Timeout durations in milliseconds
 */
export const TIMEOUTS = {
    // Reconnection
    RECONNECT_BASE: 1000,           // Base delay for exponential backoff
    RECONNECT_MAX: 30000,           // Maximum reconnect delay
    RECONNECT_ATTEMPTS: 5,          // Maximum reconnect attempts

    // UI Interactions
    DEBOUNCE_DEFAULT: 300,          // Default debounce delay
    TYPING_DEBOUNCE: 1500,          // Typing indicator debounce
    SEARCH_DEBOUNCE: 400,           // Search input debounce

    // Polling intervals
    STATUS_POLL_INTERVAL: 10000,    // Session status polling
    SAVE_STATE_INTERVAL: 5000,      // State save interval
    PERMISSION_POLL_INTERVAL: 5000, // Permission polling (to be replaced with WS)

    // Toast durations
    TOAST_SUCCESS: 4000,
    TOAST_ERROR: 6000,
    TOAST_WARNING: 5000,
    TOAST_INFO: 4000,

    // Animations
    ANIMATION_FAST: 150,
    ANIMATION_NORMAL: 300,
    ANIMATION_SLOW: 500,

    // Network
    FETCH_TIMEOUT: 30000,           // API request timeout
    SCRIPT_LOAD_TIMEOUT: 10000,     // Script loading timeout
};

/**
 * Limits and constraints
 */
export const LIMITS = {
    // Chat
    MAX_MESSAGE_LENGTH: 5000,
    MAX_ATTACHMENTS: 10,
    MAX_FILE_SIZE_MB: 25,
    MESSAGE_BATCH_SIZE: 20,

    // UI
    MAX_TOASTS: 5,
    MAX_NOTIFICATIONS: 50,
    PAGINATION_SIZE: 20,

    // Session
    PREPARATION_MINUTES: 15,
    ENDING_BUFFER_MINUTES: 5,
    DEFAULT_SESSION_DURATION: 30,

    // Video
    MAX_PARTICIPANTS: 50,
    VIDEO_QUALITY_DEFAULT: 720,
};

/**
 * Status values
 */
export const STATUS = {
    // Connection status
    CONNECTION: {
        CONNECTED: 'connected',
        DISCONNECTED: 'disconnected',
        CONNECTING: 'connecting',
        ERROR: 'error',
    },

    // Message status
    MESSAGE: {
        SENDING: 'sending',
        SENT: 'sent',
        DELIVERED: 'delivered',
        READ: 'read',
        FAILED: 'failed',
    },

    // Session status
    SESSION: {
        SCHEDULED: 'scheduled',
        LIVE: 'live',
        COMPLETED: 'completed',
        CANCELLED: 'cancelled',
    },

    // Attendance status (matches backend AttendanceStatus enum)
    ATTENDANCE: {
        ATTENDED: 'attended',
        LATE: 'late',
        LEFT: 'left',
        ABSENT: 'absent',
    },

    // Session timer phases
    TIMER_PHASE: {
        NOT_STARTED: 'not_started',
        PREPARATION: 'preparation',
        SESSION: 'session',
        OVERTIME: 'overtime',
        ENDED: 'ended',
    },
};

/**
 * CSS class names for consistent styling
 */
export const CSS_CLASSES = {
    // Toast types
    TOAST: {
        SUCCESS: 'bg-green-100 border-green-200',
        ERROR: 'bg-red-100 border-red-200',
        WARNING: 'bg-amber-100 border-amber-200',
        INFO: 'bg-blue-100 border-blue-200',
    },

    // Attendance badges
    ATTENDANCE: {
        ATTENDED: 'bg-green-100 text-green-800',
        LATE: 'bg-yellow-100 text-yellow-800',
        LEFT: 'bg-orange-100 text-orange-800',
        ABSENT: 'bg-red-100 text-red-800',
    },

    // Session status
    SESSION: {
        SCHEDULED: 'bg-blue-100 text-blue-800',
        LIVE: 'bg-green-100 text-green-800',
        COMPLETED: 'bg-gray-100 text-gray-800',
        CANCELLED: 'bg-red-100 text-red-800',
    },
};

/**
 * Icons (Remix Icons)
 */
export const ICONS = {
    ATTENDANCE: {
        ATTENDED: 'ri-check-line',
        LATE: 'ri-time-line',
        LEFT: 'ri-logout-box-line',
        ABSENT: 'ri-close-line',
    },

    STATUS: {
        SUCCESS: 'ri-checkbox-circle-fill',
        ERROR: 'ri-error-warning-fill',
        WARNING: 'ri-alert-fill',
        INFO: 'ri-information-fill',
    },

    MESSAGE: {
        SENDING: 'ri-loader-2-line',
        SENT: 'ri-check-line',
        DELIVERED: 'ri-check-double-line',
        READ: 'ri-check-double-fill',
        FAILED: 'ri-close-line',
    },
};

/**
 * Arabic labels
 */
export const LABELS_AR = {
    ATTENDANCE: {
        ATTENDED: 'حاضر',
        LATE: 'متأخر',
        LEFT: 'غادر مبكراً',
        ABSENT: 'غائب',
    },

    SESSION: {
        SCHEDULED: 'مجدولة',
        LIVE: 'جارية',
        COMPLETED: 'مكتملة',
        CANCELLED: 'ملغاة',
    },

    TIMER_PHASE: {
        NOT_STARTED: 'في انتظار الجلسة',
        PREPARATION: 'وقت التحضير',
        SESSION: 'الجلسة المباشرة',
        OVERTIME: 'وقت إضافي',
        ENDED: 'انتهت الجلسة',
    },

    ERRORS: {
        NETWORK: 'حدث خطأ في الاتصال. يرجى التحقق من الإنترنت.',
        TIMEOUT: 'انتهت مهلة الطلب. يرجى المحاولة مرة أخرى.',
        PERMISSION: 'ليس لديك صلاحية للقيام بهذا الإجراء.',
        NOT_FOUND: 'لم يتم العثور على المورد المطلوب.',
        SERVER: 'حدث خطأ في الخادم. يرجى المحاولة لاحقاً.',
        UNKNOWN: 'حدث خطأ غير متوقع.',
    },

    SUCCESS: {
        SAVED: 'تم الحفظ بنجاح',
        DELETED: 'تم الحذف بنجاح',
        UPDATED: 'تم التحديث بنجاح',
        SENT: 'تم الإرسال بنجاح',
    },
};

/**
 * API endpoints (relative paths)
 */
export const API = {
    CHAT: {
        SEND_MESSAGE: '/chat/sendMessage',
        TYPING: '/chat/typing',
        MARK_READ: '/chat/messages/:id/read',
        MARK_DELIVERED: '/chat/messages/:id/delivered',
        FETCH_MESSAGES: '/chat/fetchMessages',
        SET_ACTIVE: '/chat/setActiveStatus',
        MAKE_SEEN: '/chat/makeSeen',
    },

    LIVEKIT: {
        TOKEN: '/livekit/token',
        PERMISSIONS: '/livekit/rooms/permissions',
        ATTENDANCE_JOIN: '/api/meetings/attendance/join',
        ATTENDANCE_LEAVE: '/api/meetings/attendance/leave',
    },

    SESSION: {
        STATUS: '/api/sessions/:id/status',
        COMPLETE: '/api/sessions/:id/complete',
        CANCEL: '/api/sessions/:id/cancel',
    },
};

/**
 * Storage keys for localStorage/sessionStorage
 */
export const STORAGE_KEYS = {
    SIDEBAR_COLLAPSED: 'sidebar_collapsed',
    ACTIVE_TAB: 'tabs:',
    CHAT_PREFERENCES: 'chat_preferences',
    OFFLINE_MESSAGES: 'offline_messages',
    TIMER_STATE: 'session_timer_state',
    THEME: 'theme_preference',
};

// Export all as default for convenience
export default {
    TIMEOUTS,
    LIMITS,
    STATUS,
    CSS_CLASSES,
    ICONS,
    LABELS_AR,
    API,
    STORAGE_KEYS,
};
