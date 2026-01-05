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
        'lower_hand' => 'Lower hand',
        'toggle_chat' => 'Show/hide chat',
        'toggle_participants' => 'Show/hide participants',
        'manage_raised_hands' => 'Manage raised hands',
        'start_recording' => 'Start course recording',
        'stop_recording' => 'Stop course recording',
        'toggle_recording' => 'Start/stop course recording',
        'settings' => 'Settings',
        'leave_meeting' => 'Leave session',
        'start_mic' => 'Enable microphone',
        'stop_mic' => 'Disable microphone',
        'mic_disabled_by_teacher' => 'Microphone disabled by teacher',
        'start_camera' => 'Enable camera',
        'stop_camera' => 'Disable camera',
        'start_screen_share' => 'Share screen',
        'stop_screen_share' => 'Stop screen sharing',
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
        'title' => 'Recording',
        'started' => 'started',
        'stopped' => 'stopped',
        'error' => 'Recording error',
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

    // Participants & Roles
    'participants' => [
        'teacher' => 'Teacher',
        'student' => 'Student',
        'participant' => 'Participant',
        'you' => '(You)',
        'joined' => ':name joined the session',
        'left' => ':name left the session',
        'no_other_participants' => 'No other participants in the session',
    ],

    // Permission Messages
    'permissions' => [
        'mic_not_allowed_by_teacher' => 'Teacher has not allowed microphone usage',
        'camera_not_allowed_by_teacher' => 'Teacher has not allowed camera usage',
        'mic_disabled_by_teacher' => 'Microphone disabled by teacher',
        'cannot_unmute' => 'You are not allowed to unmute',
        'teacher_muted_all' => 'Teacher has muted all students - wait for permission',
        'teacher_controls_audio' => 'Teacher controls audio permissions - raise your hand for permission',
        'speaking_permission_granted' => 'You have been granted speaking permission - microphone is now enabled',
        'auto_unmute_error' => 'Error auto-enabling microphone',
        'cannot_manage_hands' => 'You are not allowed to manage raised hands',
        'cannot_raise_hand' => 'You are not allowed to raise hand',
        'cannot_manage_audio' => 'You are not allowed to manage audio permissions',
        'cannot_manage_camera' => 'You are not allowed to manage camera permissions',
        'cannot_record' => 'You are not allowed to record',
        'no_media_permissions' => 'No media permissions granted. You can only participate in chat.',
        'camera_control_not_allowed' => 'You are not allowed to manage camera permissions',
        'recording_not_allowed' => 'You are not allowed to record',
        'mic_permission_granted' => 'You have been granted microphone permission',
    ],

    // Control States
    'control_states' => [
        'mic_enabled' => 'enabled',
        'mic_disabled' => 'disabled',
        'camera_enabled' => 'enabled',
        'camera_disabled' => 'disabled',
        'microphone' => 'Microphone',
        'camera' => 'Camera',
        'screen_share' => 'Screen share',
        'hand' => 'Hand',
        'raised' => 'raised',
        'lowered' => 'lowered',
        'recording' => 'Recording',
        'started' => 'started',
        'stopped' => 'stopped',
        'toggle_mic' => 'Enable/Disable microphone',
        'toggle_camera' => 'Enable/Disable camera',
        'enable_mic' => 'Enable microphone',
        'disable_mic' => 'Disable microphone',
        'enable_camera' => 'Enable camera',
        'disable_camera' => 'Disable camera',
        'start_screen_share' => 'Share screen',
        'stop_screen_share' => 'Stop screen share',
        'raise_hand' => 'Raise hand',
        'lower_hand' => 'Lower hand',
        'start_recording' => 'Start recording',
        'stop_recording' => 'Stop recording',
        'hand_raised' => 'Hand raised',
    ],

    // Control Errors
    'control_errors' => [
        'not_connected' => 'Error: Not connected to session yet',
        'mic_error' => 'Error controlling microphone',
        'camera_error' => 'Error controlling camera',
        'screen_share_error' => 'Error with screen share',
        'screen_share_denied' => 'Screen share permission denied',
        'screen_share_not_supported' => 'Screen share not supported in this browser',
        'hand_raise_error' => 'Error raising hand',
        'recording_error' => 'Error with recording',
        'send_message_error' => 'Error sending message',
        'chat_data_error' => 'Error receiving chat data',
    ],

    // Hand Raise
    'hand_raise' => [
        'hand_raised_notification' => ':name raised their hand',
        'granted_permission' => 'Granted :name speaking permission',
        'grant_error' => 'Error granting speaking permission',
        'hand_raised_label' => 'Hand raised',
        'hand_raised' => 'Hand raised',
        'hide_hand' => 'Hide hand',
        'all_hands_cleared' => 'All raised hands cleared successfully',
        'clear_hands_error' => 'Error clearing raised hands',
        'minutes_ago' => ':minutes minutes ago',
        'seconds_ago' => ':seconds seconds ago',
        'teacher_dismissed_hand' => 'Teacher has hidden your raised hand',
        'all_hands_cleared_by_teacher' => 'All raised hands were cleared by teacher',
    ],

    // Student Control
    'student_control' => [
        'all_students_muted' => 'All students have been muted',
        'students_mic_allowed' => 'Students are allowed to use microphone',
        'students_can_use_mic' => 'Students are allowed to use microphone',
        'manage_students_mic_error' => 'Error managing student microphones',
        'mic_control_error' => 'Error managing student microphones',
        'all_students_camera_disabled' => 'All students\' cameras have been disabled',
        'all_cameras_disabled' => 'All students\' cameras have been disabled',
        'students_camera_allowed' => 'Students are allowed to use camera',
        'students_can_use_camera' => 'Students are allowed to use camera',
        'manage_students_camera_error' => 'Error managing student cameras',
        'camera_control_error' => 'Error managing student cameras',
        'mic_permission_granted_by' => 'Speaking permission granted by :name',
        'mic_revoked_by' => 'Microphone disabled by :name',
        'all_muted_by' => 'All students have been muted by :name',
        'mic_allowed_by' => 'You can now use the microphone - allowed by :name',
    ],

    // Data Channel Messages
    'data_channel' => [
        'all_students_muted' => 'All students have been muted',
        'mic_allowed' => 'Microphone usage is now allowed',
        'all_hands_cleared' => 'All raised hands have been cleared',
        'teacher_hid_hand' => 'Teacher has hidden your raised hand',
        'mic_permission_granted' => 'You have been granted microphone permission',
        'session_ended_by_teacher' => 'Session ended by teacher',
        'removed_from_session' => 'You have been removed from the session',
    ],

    // Screen Share
    'screen_share' => [
        'click_to_enlarge' => 'Click to enlarge',
        'your_shared_screen' => 'Your shared screen',
        'your_screen' => 'Your shared screen',
        'screen_of' => 'Screen of',
        'screen_share_paused' => 'Screen sharing has been paused',
        'screen_share_stopped' => 'Screen sharing stopped',
    ],

    // Sidebar & UI
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
        'participants' => 'Participants',
        'settings' => 'Settings',
        'fullscreen' => 'Fullscreen',
        'exit_fullscreen' => 'Exit fullscreen',
    ],

    // Leave Dialog
    'leave' => [
        'title' => 'Leave Session',
        'confirm_message' => 'Are you sure you want to leave the session?',
        'cancel' => 'Cancel',
        'leave' => 'Leave',
    ],

    // Connection Messages
    'connection' => [
        'failed' => 'Failed to connect to session. Please try again.',
        'setup_failed' => 'Failed to set up session. Please try again.',
        'joined_successfully' => 'Joined session successfully.',
        'joined_may_need_camera' => 'Joined session successfully. You may need to enable camera manually.',
        'mic_access_denied' => 'Cannot access microphone. Please allow access in your browser.',
        'camera_access_denied' => 'Cannot access camera. Please allow access in your browser.',
        'permission_denied' => 'Camera or microphone access was denied',
        'joined_teacher_mic_on' => 'Joined successfully. Microphone is enabled.',
        'joined_student_muted' => 'Joined successfully. Microphone and camera are off.',
        'connected' => 'Connected to session successfully',
        'disconnected' => 'Disconnected from session',
        'disconnected_reconnecting' => 'Connection lost... Reconnecting',
        'reconnecting' => 'Reconnecting...',
        'reconnected' => 'Reconnected successfully',
    ],

    // Chat
    'chat' => [
        'you' => 'You',
        'send_error' => 'Error sending message',
        'no_other_participants' => 'No other participants in the session',
    ],

    // Fullscreen
    'fullscreen' => [
        'enter' => 'Fullscreen',
        'exit' => 'Exit fullscreen',
    ],

    // Session
    'session' => [
        'ended_by_teacher' => 'Session ended by teacher',
        'kicked_from_session' => 'You have been removed from the session',
    ],
];
