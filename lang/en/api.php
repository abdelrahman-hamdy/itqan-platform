<?php

/**
 * English translations for API responses.
 *
 * These translations are used by the mobile API (V1) and any API endpoints
 * that return localized messages to clients.
 *
 * @see App\Http\Traits\Api\ApiResponses
 * @see App\Http\Middleware\Api\SetApiLocale
 */

return [
    /*
    |--------------------------------------------------------------------------
    | Common Response Messages
    |--------------------------------------------------------------------------
    */
    'success' => 'Success',
    'created' => 'Created successfully',
    'updated' => 'Updated successfully',
    'deleted' => 'Deleted successfully',

    /*
    |--------------------------------------------------------------------------
    | Error Messages
    |--------------------------------------------------------------------------
    */
    'unauthorized' => 'Unauthorized',
    'unauthenticated' => 'Unauthenticated',
    'forbidden' => 'Forbidden',
    'not_found' => 'Resource not found',
    'validation_failed' => 'Validation failed',
    'server_error' => 'Internal server error',

    /*
    |--------------------------------------------------------------------------
    | Avatar Fallback Names
    |--------------------------------------------------------------------------
    |
    | Used when generating placeholder avatars for users without profile images.
    |
    */
    'avatar' => [
        'student' => 'Student',
        'teacher' => 'Teacher',
        'parent' => 'Parent',
        'user' => 'User',
    ],

    /*
    |--------------------------------------------------------------------------
    | Meeting Messages
    |--------------------------------------------------------------------------
    |
    | Messages related to video meeting/LiveKit functionality.
    |
    */
    'meeting' => [
        'command_sent' => 'Command sent successfully',
        'ack_recorded' => 'Acknowledgment recorded',
        'students_muted' => 'All students muted successfully',
        'mics_allowed' => 'Student microphones allowed successfully',
        'hands_cleared' => 'All hand raises cleared successfully',
        'mic_granted' => 'Microphone permission granted successfully',
        'command_not_found' => 'Command not found',
        'room_created' => 'Meeting room created successfully',
        'room_ended' => 'Meeting ended successfully',
        'token_generated' => 'Meeting token generated successfully',
    ],

    /*
    |--------------------------------------------------------------------------
    | Session Messages
    |--------------------------------------------------------------------------
    */
    'session' => [
        'completed' => 'Session completed successfully',
        'cancelled' => 'Session cancelled successfully',
        'rescheduled' => 'Session rescheduled successfully',
        'feedback_submitted' => 'Feedback submitted successfully',
        'notes_updated' => 'Notes updated successfully',
        'evaluation_saved' => 'Evaluation saved successfully',
    ],

    /*
    |--------------------------------------------------------------------------
    | Homework Messages
    |--------------------------------------------------------------------------
    */
    'homework' => [
        'assigned' => 'Homework assigned successfully',
        'submitted' => 'Homework submitted successfully',
        'graded' => 'Homework graded successfully',
        'revision_requested' => 'Revision requested successfully',
        'draft_saved' => 'Draft saved successfully',
    ],

    /*
    |--------------------------------------------------------------------------
    | Authentication Messages
    |--------------------------------------------------------------------------
    */
    'auth' => [
        'login_success' => 'Login successful',
        'logout_success' => 'Logged out successfully',
        'token_refreshed' => 'Token refreshed successfully',
        'token_revoked' => 'Token revoked successfully',
        'password_changed' => 'Password changed successfully',
        'profile_updated' => 'Profile updated successfully',
    ],

    /*
    |--------------------------------------------------------------------------
    | Subscription Messages
    |--------------------------------------------------------------------------
    */
    'subscription' => [
        'activated' => 'Subscription activated successfully',
        'renewed' => 'Subscription renewed successfully',
        'cancelled' => 'Subscription cancelled successfully',
        'paused' => 'Subscription paused successfully',
        'resumed' => 'Subscription resumed successfully',
    ],

    /*
    |--------------------------------------------------------------------------
    | Payment Messages
    |--------------------------------------------------------------------------
    */
    'payment' => [
        'initiated' => 'Payment initiated successfully',
        'completed' => 'Payment completed successfully',
        'failed' => 'Payment failed',
        'refunded' => 'Payment refunded successfully',
    ],

    /*
    |--------------------------------------------------------------------------
    | Certificate Messages
    |--------------------------------------------------------------------------
    */
    'certificate' => [
        'issued' => 'Certificate issued successfully',
        'requested' => 'Certificate request submitted successfully',
    ],

    /*
    |--------------------------------------------------------------------------
    | Notification Messages
    |--------------------------------------------------------------------------
    */
    'notification' => [
        'marked_read' => 'Notification marked as read',
        'all_marked_read' => 'All notifications marked as read',
        'deleted' => 'Notification deleted successfully',
        'all_deleted' => 'All notifications deleted successfully',
    ],

    /*
    |--------------------------------------------------------------------------
    | Chat Messages
    |--------------------------------------------------------------------------
    */
    'chat' => [
        'conversation_created' => 'Conversation created successfully',
        'message_sent' => 'Message sent successfully',
        'marked_read' => 'Conversation marked as read',
    ],

    /*
    |--------------------------------------------------------------------------
    | Data Retrieval Messages
    |--------------------------------------------------------------------------
    */
    'data' => [
        'retrieved' => 'Data retrieved successfully',
        'list_retrieved' => 'List retrieved successfully',
        'details_retrieved' => 'Details retrieved successfully',
    ],
];
