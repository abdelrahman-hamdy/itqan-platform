<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Meeting Interface Language Lines (English)
    |--------------------------------------------------------------------------
    |
    | The following language lines are used throughout the LiveKit meeting
    | interface, including session status, controls, attendance tracking,
    | and all related functionality.
    |
    */

    // Session Status Messages
    'status' => [
        'session_ready' => 'Session is ready - you can join now',
        'session_ongoing' => 'Session is currently ongoing - join to participate',
        'preparing_meeting' => 'Preparing meeting - you can join now',
        'preparing_in_time' => 'Meeting will be prepared in :time (:minutes minutes before start)',
        'preparing' => 'Preparing meeting...',
        'scheduled_no_time' => 'Session is scheduled but time is not set yet',
        'waiting_preparation' => 'Waiting for meeting preparation',
        'session_completed' => 'Session completed successfully',
        'session_ended' => 'Session ended',
        'session_cancelled' => 'Session has been cancelled',
        'session_cancelled_short' => 'Session cancelled',
        'absent_can_join' => 'You were marked absent but you can join now',
        'student_absent' => 'Student absence has been recorded',
        'student_absent_short' => 'Student absent',
        'session_status_prefix' => 'Session status:',
        'unavailable' => 'Unavailable',
        'unknown_status' => 'Unknown status:',
        'session_unscheduled' => 'Session is not scheduled yet',
        'not_scheduled' => 'Not Scheduled',
    ],

    // Button Labels
    'buttons' => [
        'join_session' => 'Join Session',
        'join_ongoing' => 'Join Ongoing Session',
        'join_as_absent' => 'Join Session (Absent)',
        'leave_session' => 'Leave Session',
        'connecting' => 'Connecting...',
        'connected' => 'Connected',
        'retry' => 'Retry',
        'livekit_unavailable' => 'LiveKit unavailable',
        'init_error' => 'Initialization error',
    ],

    // Timer and Phase Labels
    'timer' => [
        'waiting_session' => 'Waiting for session',
        'preparation_time' => 'Preparation time - get ready',
        'session_active' => 'Session is active now',
        'overtime' => 'Overtime - wrap up soon',
        'session_ended' => 'Session ended',
        'time_until_start' => 'Session starts in',
        'waiting_start' => 'Waiting to start',
        'session_active_since' => 'Session active for',
        'session_currently_active' => 'Session is currently active',
        'overtime_since' => 'Overtime for',
        'session_in_overtime' => 'Session is in overtime',
        'starting_soon' => 'Session starts in :time',
    ],

    // Session Info Labels
    'info' => [
        'session_info' => 'Session Information',
        'session_time' => 'Session time:',
        'duration' => 'Duration:',
        'minute' => 'minute',
        'minutes' => 'minutes',
        'preparation_period' => 'Preparation period:',
        'buffer_time' => 'Buffer time:',
        'room_number' => 'Room number:',
        'not_specified' => 'Not specified',
        'participant' => 'participant',
        'participants' => 'participants',
        'fullscreen' => 'Fullscreen',
        'exit_fullscreen' => 'Exit fullscreen',
    ],

    // Session Management (Teacher)
    'management' => [
        'session_management' => 'Session Status Management',
        'cancel_session_teacher' => 'Cancel Session (Teacher Absence)',
        'cancel_session' => 'Cancel Session',
        'mark_student_absent' => 'Mark Student Absent',
        'end_session' => 'End Session',
        'session_ended_success' => 'Session ended successfully',
        'session_cancelled' => 'Session cancelled',
        'student_marked_absent' => 'Student marked as absent',
    ],

    // Confirmation Dialogs
    'confirm' => [
        'cancel_session' => 'Are you sure you want to cancel this session? This session will not count towards the subscription.',
        'mark_absent' => 'Are you sure you want to mark the student as absent?',
        'end_session' => 'Are you sure you want to end this session?',
    ],

    // Success/Error Messages
    'messages' => [
        'session_cancelled_success' => 'Session cancelled successfully',
        'cancel_failed' => 'Failed to cancel session:',
        'unknown_error' => 'Unknown error',
        'cancel_error' => 'An error occurred while cancelling the session',
        'absent_marked_success' => 'Student absence recorded successfully',
        'absent_mark_failed' => 'Failed to record student absence:',
        'absent_mark_error' => 'An error occurred while recording student absence',
        'session_ended_success' => 'Session ended successfully',
        'end_failed' => 'Failed to end session:',
        'end_error' => 'An error occurred while ending the session',
        'connection_failed' => 'Failed to connect to session:',
        'unexpected_error' => 'An unexpected error occurred',
        'auto_terminated' => 'Session time has expired and was automatically terminated',
        'auto_terminated_description' => 'Session was automatically ended as the scheduled time elapsed',
    ],

    // Attendance Status
    'attendance' => [
        'present' => 'Present',
        'late' => 'Late',
        'left_early' => 'Left early',
        'absent' => 'Absent',
        'attended_before' => 'Attended previously',
        'not_joined_yet' => 'Not joined yet',
        'in_session_now' => '(In session now)',
        'duration_prefix' => 'Attendance duration:',
        'not_started' => 'Session has not started yet',
        'starting_in' => 'Starting in :minutes minutes',
        'waiting_start' => 'Waiting to start',
        'did_not_attend' => 'Did not attend session',
        'session_ended' => 'Session ended',
        'attended_session' => 'Attended session',
        'attended_late' => 'Attended late',
        'in_session' => 'In session now',
        'session_ongoing' => 'Session is ongoing now',
        'attendance_tracked' => 'Attendance is being tracked automatically',
        'attendance_failed' => 'Failed to record attendance',
        'join_failed' => 'Failed to record your session entry',
        'leave_failed' => 'Failed to record your session exit',
        'attended_minutes' => 'Attended :minutes minutes',
        'joined_times' => ':minutes minutes - joined :count times',
        'not_attended_label' => 'Did not attend',
    ],

    // Network Status
    'network' => [
        'offline' => 'Not connected to network',
        'reconnecting' => 'Reconnecting...',
        'reconnecting_session' => 'Reconnecting to session...',
        'reconnect_failed' => 'Reconnection failed - please reload the page',
        'reconnect_error' => 'Reconnection failed',
        'connected' => 'Connected',
    ],

    // Loading States
    'loading' => [
        'connecting_meeting' => 'Connecting to meeting...',
        'please_wait' => 'Please wait...',
        'loading_devices' => 'Loading...',
    ],

    // Control Bar (Tooltips)
    'controls' => [
        'toggle_mic' => 'Toggle microphone',
        'toggle_camera' => 'Toggle camera',
        'share_screen' => 'Share screen',
        'raise_hand' => 'Raise hand',
        'toggle_chat' => 'Show/hide chat',
        'toggle_participants' => 'Show/hide participants',
        'manage_raised_hands' => 'Manage raised hands',
        'start_recording' => 'Start course recording',
        'stop_recording' => 'Stop course recording',
        'toggle_recording' => 'Start/stop course recording',
        'settings' => 'Settings',
        'leave_meeting' => 'Leave session',
    ],

    // Sidebar Panels
    'sidebar' => [
        'chat' => 'Chat',
        'close_sidebar' => 'Close sidebar',
        'type_message' => 'Type a message...',
        'raised_hands' => 'Raised Hands',
        'hide_all' => 'Hide all',
        'no_raised_hands' => 'No students have raised their hands',
        'student_controls' => 'Student Controls',
        'allow_microphone' => 'Allow microphone',
        'allow_mic_description' => 'Allow students to use microphone',
        'allow_camera' => 'Allow camera',
        'allow_camera_description' => 'Allow students to use camera',
        'camera_settings' => 'Camera Settings',
        'camera_label' => 'Camera',
        'quality_label' => 'Quality',
        'quality_low' => 'Low (480p)',
        'quality_medium' => 'Medium (720p)',
        'quality_high' => 'High (1080p)',
        'mic_settings' => 'Microphone Settings',
        'microphone_label' => 'Microphone',
        'mute_on_join' => 'Mute on join',
    ],

    // Recording
    'recording' => [
        'start_recording' => 'Start course recording',
        'stop_recording' => 'Stop course recording',
        'recording_stopped' => 'Recording stopped and saved successfully',
        'recording_started' => 'Interactive course recording started',
        'recording_error' => 'Recording error:',
        'start_failed' => 'Failed to start recording',
        'no_active_recording' => 'No active recording',
        'stop_failed' => 'Failed to stop recording',
    ],

    // Headers
    'headers' => [
        'meeting_management' => 'Live Meeting Management',
        'join_live_session' => 'Join Live Session',
    ],

    // System Status (for device/browser checks)
    'system' => [
        'allowed' => 'Allowed',
        'denied' => 'Denied',
        'needs_permission' => 'Needs permission',
        'unknown' => 'Unknown',
        'connected' => 'Connected',
        'not_connected' => 'Not connected',
        'compatible' => 'Compatible',
        'not_compatible' => 'Not compatible',
    ],
];
