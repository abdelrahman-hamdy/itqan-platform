<?php

namespace App\Enums\Api;

/**
 * Standardized API Error Codes
 *
 * All error codes returned by the API are defined here for consistency.
 * Use these codes instead of hardcoded strings in controllers.
 *
 * @see docs/api/API_ERRORS.md for documentation
 */
enum ErrorCode: string
{
    // ==========================================
    // HTTP Status-Based Errors (Standard)
    // ==========================================
    case BAD_REQUEST = 'BAD_REQUEST';
    case UNAUTHORIZED = 'UNAUTHORIZED';
    case UNAUTHENTICATED = 'UNAUTHENTICATED';
    case FORBIDDEN = 'FORBIDDEN';
    case NOT_FOUND = 'NOT_FOUND';
    case CONFLICT = 'CONFLICT';
    case VALIDATION_ERROR = 'VALIDATION_ERROR';
    case RATE_LIMIT_EXCEEDED = 'RATE_LIMIT_EXCEEDED';
    case INTERNAL_ERROR = 'INTERNAL_ERROR';
    case BAD_GATEWAY = 'BAD_GATEWAY';
    case SERVICE_UNAVAILABLE = 'SERVICE_UNAVAILABLE';
    case GATEWAY_TIMEOUT = 'GATEWAY_TIMEOUT';
    case UNKNOWN_ERROR = 'UNKNOWN_ERROR';

    // ==========================================
    // Authentication & Authorization Errors
    // ==========================================
    case INVALID_CREDENTIALS = 'INVALID_CREDENTIALS';
    case INVALID_PASSWORD = 'INVALID_PASSWORD';
    case INVALID_CURRENT_PASSWORD = 'INVALID_CURRENT_PASSWORD';
    case INVALID_RESET_TOKEN = 'INVALID_RESET_TOKEN';
    case INVALID_REGISTRATION_TOKEN = 'INVALID_REGISTRATION_TOKEN';
    case ACCOUNT_INACTIVE = 'ACCOUNT_INACTIVE';
    case TOKEN_EXPIRED = 'TOKEN_EXPIRED';
    case TOKEN_INVALID = 'TOKEN_INVALID';
    case SESSION_EXPIRED = 'SESSION_EXPIRED';

    // ==========================================
    // Resource Not Found Errors
    // ==========================================
    case RESOURCE_NOT_FOUND = 'RESOURCE_NOT_FOUND';
    case USER_NOT_FOUND = 'USER_NOT_FOUND';
    case STUDENT_CODE_NOT_FOUND = 'STUDENT_CODE_NOT_FOUND';
    case PARENT_PROFILE_NOT_FOUND = 'PARENT_PROFILE_NOT_FOUND';
    case PROFILE_NOT_FOUND = 'PROFILE_NOT_FOUND';
    case ACADEMY_NOT_FOUND = 'ACADEMY_NOT_FOUND';
    case CHILD_NOT_FOUND = 'CHILD_NOT_FOUND';
    case FILE_NOT_FOUND = 'FILE_NOT_FOUND';
    case SESSION_NOT_FOUND = 'SESSION_NOT_FOUND';
    case SUBSCRIPTION_NOT_FOUND = 'SUBSCRIPTION_NOT_FOUND';
    case HOMEWORK_NOT_FOUND = 'HOMEWORK_NOT_FOUND';
    case QUIZ_NOT_FOUND = 'QUIZ_NOT_FOUND';
    case PAYMENT_NOT_FOUND = 'PAYMENT_NOT_FOUND';

    // ==========================================
    // Session & Meeting Errors
    // ==========================================
    case FEEDBACK_ALREADY_SUBMITTED = 'FEEDBACK_ALREADY_SUBMITTED';
    case FEEDBACK_SUBMISSION_FAILED = 'FEEDBACK_SUBMISSION_FAILED';
    case ALREADY_COMPLETED = 'ALREADY_COMPLETED';
    case ALREADY_CANCELLED = 'ALREADY_CANCELLED';
    case COMPLETE_FAILED = 'COMPLETE_FAILED';
    case EVALUATION_FAILED = 'EVALUATION_FAILED';
    case SESSION_NOT_JOINABLE = 'SESSION_NOT_JOINABLE';
    case MEETING_CREATE_FAILED = 'MEETING_CREATE_FAILED';
    case TOKEN_GENERATE_FAILED = 'TOKEN_GENERATE_FAILED';
    case TOKEN_GENERATION_FAILED = 'TOKEN_GENERATION_FAILED';

    // ==========================================
    // Homework Errors
    // ==========================================
    case INVALID_TYPE = 'INVALID_TYPE';
    case ALREADY_SUBMITTED = 'ALREADY_SUBMITTED';
    case HOMEWORK_OVERDUE = 'HOMEWORK_OVERDUE';
    case INVALID_SUBMISSION = 'INVALID_SUBMISSION';
    case SUBMISSION_NOT_FOUND = 'SUBMISSION_NOT_FOUND';

    // ==========================================
    // Quiz Errors
    // ==========================================
    case QUIZ_OVERDUE = 'QUIZ_OVERDUE';
    case MAX_ATTEMPTS_REACHED = 'MAX_ATTEMPTS_REACHED';
    case NO_ACTIVE_ATTEMPT = 'NO_ACTIVE_ATTEMPT';
    case TIME_LIMIT_EXCEEDED = 'TIME_LIMIT_EXCEEDED';
    case QUIZ_NOT_PUBLISHED = 'QUIZ_NOT_PUBLISHED';

    // ==========================================
    // Payment Errors
    // ==========================================
    case ALREADY_PAID = 'ALREADY_PAID';
    case PAYMENT_FAILED = 'PAYMENT_FAILED';
    case PAYMENT_PROCESSING = 'PAYMENT_PROCESSING';
    case REFUND_FAILED = 'REFUND_FAILED';
    case INVALID_PAYMENT_METHOD = 'INVALID_PAYMENT_METHOD';

    // ==========================================
    // Subscription Errors
    // ==========================================
    case SUBSCRIPTION_EXPIRED = 'SUBSCRIPTION_EXPIRED';
    case SUBSCRIPTION_INACTIVE = 'SUBSCRIPTION_INACTIVE';
    case SESSIONS_EXHAUSTED = 'SESSIONS_EXHAUSTED';

    // ==========================================
    // Relationship Errors
    // ==========================================
    case CHILD_ALREADY_LINKED = 'CHILD_ALREADY_LINKED';
    case RELATIONSHIP_EXISTS = 'RELATIONSHIP_EXISTS';
    case RELATIONSHIP_NOT_FOUND = 'RELATIONSHIP_NOT_FOUND';

    // ==========================================
    // Validation Errors
    // ==========================================
    case INVALID_DATE = 'INVALID_DATE';
    case INVALID_PARAMETERS = 'INVALID_PARAMETERS';
    case INVALID_FORMAT = 'INVALID_FORMAT';
    case MISSING_REQUIRED_FIELD = 'MISSING_REQUIRED_FIELD';

    // ==========================================
    // File Upload Errors
    // ==========================================
    case FILE_TOO_LARGE = 'FILE_TOO_LARGE';
    case INVALID_FILE_TYPE = 'INVALID_FILE_TYPE';
    case UPLOAD_FAILED = 'UPLOAD_FAILED';

    /**
     * Get the error label (localized)
     */
    public function label(): string
    {
        return match ($this) {
            // HTTP Status-Based
            self::BAD_REQUEST => __('Bad request'),
            self::UNAUTHORIZED => __('Unauthorized'),
            self::UNAUTHENTICATED => __('Unauthenticated'),
            self::FORBIDDEN => __('Forbidden'),
            self::NOT_FOUND => __('Not found'),
            self::CONFLICT => __('Conflict'),
            self::VALIDATION_ERROR => __('Validation failed'),
            self::RATE_LIMIT_EXCEEDED => __('Rate limit exceeded'),
            self::INTERNAL_ERROR => __('Internal server error'),
            self::BAD_GATEWAY => __('Bad gateway'),
            self::SERVICE_UNAVAILABLE => __('Service unavailable'),
            self::GATEWAY_TIMEOUT => __('Gateway timeout'),
            self::UNKNOWN_ERROR => __('Unknown error'),

            // Authentication
            self::INVALID_CREDENTIALS => __('Invalid credentials'),
            self::INVALID_PASSWORD => __('Invalid password'),
            self::INVALID_CURRENT_PASSWORD => __('Invalid current password'),
            self::INVALID_RESET_TOKEN => __('Invalid or expired reset token'),
            self::INVALID_REGISTRATION_TOKEN => __('Invalid registration token'),
            self::ACCOUNT_INACTIVE => __('Account is inactive'),
            self::TOKEN_EXPIRED => __('Token has expired'),
            self::TOKEN_INVALID => __('Token is invalid'),
            self::SESSION_EXPIRED => __('Session has expired'),

            // Resources Not Found
            self::RESOURCE_NOT_FOUND => __('Resource not found'),
            self::USER_NOT_FOUND => __('User not found'),
            self::STUDENT_CODE_NOT_FOUND => __('Student code not found'),
            self::PARENT_PROFILE_NOT_FOUND => __('Parent profile not found'),
            self::PROFILE_NOT_FOUND => __('Profile not found'),
            self::ACADEMY_NOT_FOUND => __('Academy not found'),
            self::CHILD_NOT_FOUND => __('Child not found'),
            self::FILE_NOT_FOUND => __('File not found'),
            self::SESSION_NOT_FOUND => __('Session not found'),
            self::SUBSCRIPTION_NOT_FOUND => __('Subscription not found'),
            self::HOMEWORK_NOT_FOUND => __('Homework not found'),
            self::QUIZ_NOT_FOUND => __('Quiz not found'),
            self::PAYMENT_NOT_FOUND => __('Payment not found'),

            // Sessions & Meetings
            self::FEEDBACK_ALREADY_SUBMITTED => __('Feedback already submitted'),
            self::FEEDBACK_SUBMISSION_FAILED => __('Failed to submit feedback'),
            self::ALREADY_COMPLETED => __('Already completed'),
            self::ALREADY_CANCELLED => __('Already cancelled'),
            self::COMPLETE_FAILED => __('Failed to complete'),
            self::EVALUATION_FAILED => __('Failed to submit evaluation'),
            self::SESSION_NOT_JOINABLE => __('Session cannot be joined'),
            // SESSION_EXPIRED handled above with auth errors
            self::MEETING_CREATE_FAILED => __('Failed to create meeting'),
            self::TOKEN_GENERATE_FAILED => __('Failed to generate token'),
            self::TOKEN_GENERATION_FAILED => __('Token generation failed'),

            // Homework
            self::INVALID_TYPE => __('Invalid type'),
            self::ALREADY_SUBMITTED => __('Already submitted'),
            self::HOMEWORK_OVERDUE => __('Homework is overdue'),
            self::INVALID_SUBMISSION => __('Invalid submission'),
            self::SUBMISSION_NOT_FOUND => __('Submission not found'),

            // Quiz
            self::QUIZ_OVERDUE => __('Quiz due date has passed'),
            self::MAX_ATTEMPTS_REACHED => __('Maximum attempts reached'),
            self::NO_ACTIVE_ATTEMPT => __('No active attempt found'),
            self::TIME_LIMIT_EXCEEDED => __('Time limit exceeded'),
            self::QUIZ_NOT_PUBLISHED => __('Quiz is not published'),

            // Payment
            self::ALREADY_PAID => __('Already paid'),
            self::PAYMENT_FAILED => __('Payment failed'),
            self::PAYMENT_PROCESSING => __('Payment is processing'),
            self::REFUND_FAILED => __('Refund failed'),
            self::INVALID_PAYMENT_METHOD => __('Invalid payment method'),

            // Subscription
            self::SUBSCRIPTION_EXPIRED => __('Subscription has expired'),
            self::SUBSCRIPTION_INACTIVE => __('Subscription is inactive'),
            self::SESSIONS_EXHAUSTED => __('All sessions have been used'),

            // Relationships
            self::CHILD_ALREADY_LINKED => __('Child already linked'),
            self::RELATIONSHIP_EXISTS => __('Relationship already exists'),
            self::RELATIONSHIP_NOT_FOUND => __('Relationship not found'),

            // Validation
            self::INVALID_DATE => __('Invalid date format'),
            self::INVALID_PARAMETERS => __('Invalid parameters'),
            self::INVALID_FORMAT => __('Invalid format'),
            self::MISSING_REQUIRED_FIELD => __('Required field is missing'),

            // File Upload
            self::FILE_TOO_LARGE => __('File is too large'),
            self::INVALID_FILE_TYPE => __('Invalid file type'),
            self::UPLOAD_FAILED => __('Upload failed'),
        };
    }

    /**
     * Get the suggested HTTP status code for this error
     */
    public function httpStatus(): int
    {
        return match ($this) {
            // 400 Bad Request
            self::BAD_REQUEST,
            self::INVALID_TYPE,
            self::ALREADY_SUBMITTED,
            self::HOMEWORK_OVERDUE,
            self::INVALID_SUBMISSION,
            self::QUIZ_OVERDUE,
            self::MAX_ATTEMPTS_REACHED,
            self::NO_ACTIVE_ATTEMPT,
            self::TIME_LIMIT_EXCEEDED,
            self::ALREADY_COMPLETED,
            self::ALREADY_CANCELLED,
            self::ALREADY_PAID,
            self::INVALID_DATE,
            self::INVALID_PARAMETERS,
            self::INVALID_FORMAT,
            self::MISSING_REQUIRED_FIELD,
            self::FILE_TOO_LARGE,
            self::INVALID_FILE_TYPE,
            self::INVALID_PAYMENT_METHOD,
            self::CHILD_ALREADY_LINKED,
            self::RELATIONSHIP_EXISTS => 400,

            // 401 Unauthorized
            self::UNAUTHORIZED,
            self::UNAUTHENTICATED,
            self::INVALID_CREDENTIALS,
            self::INVALID_PASSWORD,
            self::INVALID_CURRENT_PASSWORD,
            self::INVALID_RESET_TOKEN,
            self::INVALID_REGISTRATION_TOKEN,
            self::TOKEN_EXPIRED,
            self::TOKEN_INVALID,
            self::SESSION_EXPIRED => 401,

            // 403 Forbidden
            self::FORBIDDEN,
            self::ACCOUNT_INACTIVE,
            self::SUBSCRIPTION_EXPIRED,
            self::SUBSCRIPTION_INACTIVE,
            self::SESSIONS_EXHAUSTED,
            self::SESSION_NOT_JOINABLE => 403,

            // 404 Not Found
            self::NOT_FOUND,
            self::RESOURCE_NOT_FOUND,
            self::USER_NOT_FOUND,
            self::STUDENT_CODE_NOT_FOUND,
            self::PARENT_PROFILE_NOT_FOUND,
            self::PROFILE_NOT_FOUND,
            self::ACADEMY_NOT_FOUND,
            self::CHILD_NOT_FOUND,
            self::FILE_NOT_FOUND,
            self::SESSION_NOT_FOUND,
            self::SUBSCRIPTION_NOT_FOUND,
            self::HOMEWORK_NOT_FOUND,
            self::QUIZ_NOT_FOUND,
            self::PAYMENT_NOT_FOUND,
            self::SUBMISSION_NOT_FOUND,
            self::RELATIONSHIP_NOT_FOUND => 404,

            // 409 Conflict
            self::CONFLICT => 409,

            // 422 Validation
            self::VALIDATION_ERROR => 422,

            // 429 Rate Limit
            self::RATE_LIMIT_EXCEEDED => 429,

            // 500 Server Errors
            self::INTERNAL_ERROR,
            self::UNKNOWN_ERROR,
            self::COMPLETE_FAILED,
            self::EVALUATION_FAILED,
            self::FEEDBACK_SUBMISSION_FAILED,
            self::MEETING_CREATE_FAILED,
            self::TOKEN_GENERATE_FAILED,
            self::TOKEN_GENERATION_FAILED,
            self::PAYMENT_FAILED,
            self::REFUND_FAILED,
            self::UPLOAD_FAILED,
            self::QUIZ_NOT_PUBLISHED,
            self::PAYMENT_PROCESSING => 500,

            // 502 Bad Gateway
            self::BAD_GATEWAY => 502,

            // 503 Service Unavailable
            self::SERVICE_UNAVAILABLE => 503,

            // 504 Gateway Timeout
            self::GATEWAY_TIMEOUT => 504,
        };
    }

    /**
     * Create from HTTP status code (fallback mapping)
     */
    public static function fromHttpStatus(int $status): self
    {
        return match ($status) {
            400 => self::BAD_REQUEST,
            401 => self::UNAUTHORIZED,
            403 => self::FORBIDDEN,
            404 => self::NOT_FOUND,
            409 => self::CONFLICT,
            422 => self::VALIDATION_ERROR,
            429 => self::RATE_LIMIT_EXCEEDED,
            500 => self::INTERNAL_ERROR,
            502 => self::BAD_GATEWAY,
            503 => self::SERVICE_UNAVAILABLE,
            504 => self::GATEWAY_TIMEOUT,
            default => self::UNKNOWN_ERROR,
        };
    }

    /**
     * Check if this is a client error (4xx)
     */
    public function isClientError(): bool
    {
        $status = $this->httpStatus();
        return $status >= 400 && $status < 500;
    }

    /**
     * Check if this is a server error (5xx)
     */
    public function isServerError(): bool
    {
        return $this->httpStatus() >= 500;
    }
}
