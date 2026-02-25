{{--
    LiveKit Meeting Interface Component
    Unified meeting interface for both teachers and students
    Based on official LiveKit JavaScript SDK
--}}

@props([
'session',
'userType' => 'student'
])

@php
    // Detect session type
    $isAcademicSession = $session instanceof \App\Models\AcademicSession;
    $isInteractiveCourseSession = $session instanceof \App\Models\InteractiveCourseSession;
    $isQuranSession = $session instanceof \App\Models\QuranSession;

    // Session type string for API calls (to resolve correct session when IDs conflict across tables)
    $sessionTypeForApi = $isAcademicSession ? 'academic' : ($isInteractiveCourseSession ? 'interactive' : 'quran');

    // Get circle for Quran sessions (individual or group)
    $circle = null;
    if ($isQuranSession) {
        $circle = $session->session_type === 'individual'
            ? $session->individualCircle
            : $session->circle;
    }

    // Get academy for this session - all session types use academy settings
    if ($isInteractiveCourseSession) {
        $academy = $session->course?->academy;
    } else {
        $academy = $session->academy;
    }

    // Get meeting timing from academy settings (single source of truth)
    $academySettings = $academy?->settings;
    $preparationMinutes = $academySettings?->default_preparation_minutes ?? 10;
    $endingBufferMinutes = $academySettings?->default_buffer_minutes ?? 5;
    $graceMinutes = $academySettings?->default_late_tolerance_minutes ?? 15;

    // Check if session has a meeting room (based on meeting_room_name or meeting_link)
    $hasMeetingRoom = !empty($session->meeting_room_name) || !empty($session->meeting_link);

    // Anyone can join when session is READY or ONGOING (students and teachers)
    // Both can initiate the meeting if room doesn't exist
    $canJoinMeeting = in_array($session->status, [
        App\Enums\SessionStatus::READY,
        App\Enums\SessionStatus::ONGOING
    ]);

    // ADDITIONAL FIX: Allow students to join even if marked absent, as long as session is active
    if ($userType === 'student' && in_array($session->status, [
        App\Enums\SessionStatus::ABSENT,
        App\Enums\SessionStatus::SCHEDULED
    ]) && $hasMeetingRoom) {
        // Students can join during preparation time or if session hasn't ended
        // Use academy timezone for "now" to ensure accurate comparisons
        $now = nowInAcademyTimezone();
        $preparationStart = $session->scheduled_at?->copy()->subMinutes($preparationMinutes);
        $sessionEnd = $session->scheduled_at?->copy()->addMinutes(($session->duration_minutes ?? 30) + $endingBufferMinutes);

        if ($now->gte($preparationStart) && $now->lt($sessionEnd)) {
            $canJoinMeeting = true;
        }
    }
    
    // Get current user's avatar data for meeting
    $currentUser = auth()->user();
    $currentUserAvatarPath = $currentUser->avatar
        ?? $currentUser->studentProfile?->avatar
        ?? $currentUser->quranTeacherProfile?->avatar
        ?? $currentUser->academicTeacherProfile?->avatar
        ?? null;

    $currentUserGender = $currentUser->gender
        ?? $currentUser->studentProfile?->gender
        ?? $currentUser->quranTeacherProfile?->gender
        ?? $currentUser->academicTeacherProfile?->gender
        ?? 'male';

    $currentUserType = $currentUser->user_type ?? 'student';
    $genderPrefix = $currentUserGender === 'female' ? 'female' : 'male';

    // Build avatar URLs
    $currentUserAvatarUrl = $currentUserAvatarPath ? asset('storage/' . $currentUserAvatarPath) : null;
    $currentUserDefaultAvatarUrl = match($currentUserType) {
        'quran_teacher' => asset("app-design-assets/{$genderPrefix}-quran-teacher-avatar.png"),
        'academic_teacher' => asset("app-design-assets/{$genderPrefix}-academic-teacher-avatar.png"),
        'supervisor', 'admin', 'super_admin' => asset("app-design-assets/{$genderPrefix}-supervisor-avatar.png"),
        default => asset("app-design-assets/{$genderPrefix}-student-avatar.png"),
    };

    // Get status-specific messages
    $meetingMessage = '';
    $buttonText = '';
    $buttonClass = '';
    $buttonDisabled = false;
    
    switch($session->status) {
        case App\Enums\SessionStatus::READY:
            // Anyone can join/start the session
            $meetingMessage = __('meetings.status.session_ready');
            $buttonText = __('meetings.buttons.join_session');
            $buttonClass = 'bg-green-600 hover:bg-green-700';
            $buttonDisabled = false;
            break;

        case App\Enums\SessionStatus::ONGOING:
            // Anyone can join the ongoing session
            $meetingMessage = __('meetings.status.session_ongoing');
            $buttonText = __('meetings.buttons.join_ongoing');
            $buttonClass = 'bg-orange-600 hover:bg-orange-700 animate-pulse';
            $buttonDisabled = false;
            break;

        case App\Enums\SessionStatus::SCHEDULED:
            if ($canJoinMeeting) {
                $meetingMessage = __('meetings.status.preparing_meeting');
                $buttonText = __('meetings.buttons.join_session');
                $buttonClass = 'bg-blue-600 hover:bg-blue-700';
                $buttonDisabled = false;
            } else {
                if ($session->scheduled_at) {
                    $preparationTime = $session->scheduled_at->copy()->subMinutes($preparationMinutes);
                    $timeData = formatTimeRemaining($preparationTime);
                    if (!$timeData['is_past']) {
                        $meetingMessage = __('meetings.status.preparing_in_time', ['time' => $timeData['formatted'], 'minutes' => $preparationMinutes]);
                    } else {
                        $meetingMessage = __('meetings.status.preparing');
                    }
                } else {
                    $meetingMessage = __('meetings.status.scheduled_no_time');
                }
                $buttonText = __('meetings.status.waiting_preparation');
                $buttonClass = 'bg-gray-400 cursor-not-allowed';
                $buttonDisabled = true;
            }
            break;

        case App\Enums\SessionStatus::COMPLETED:
            $meetingMessage = __('meetings.status.session_completed');
            $buttonText = __('meetings.status.session_ended');
            $buttonClass = 'bg-gray-400 cursor-not-allowed';
            $buttonDisabled = true;
            break;

        case App\Enums\SessionStatus::CANCELLED:
            $meetingMessage = __('meetings.status.session_cancelled');
            $buttonText = __('meetings.status.session_cancelled_short');
            $buttonClass = 'bg-red-400 cursor-not-allowed';
            $buttonDisabled = true;
            break;

        case App\Enums\SessionStatus::ABSENT:
            if ($canJoinMeeting) {
                $meetingMessage = __('meetings.status.absent_can_join');
                $buttonText = __('meetings.buttons.join_as_absent');
                $buttonClass = 'bg-yellow-600 hover:bg-yellow-700';
                $buttonDisabled = false;
            } else {
                $meetingMessage = __('meetings.status.student_absent');
                $buttonText = __('meetings.status.student_absent_short');
                $buttonClass = 'bg-red-400 cursor-not-allowed';
                $buttonDisabled = true;
            }
            break;

        case App\Enums\SessionStatus::UNSCHEDULED:
            $meetingMessage = __('meetings.status.session_unscheduled');
            $buttonText = __('meetings.status.not_scheduled');
            $buttonClass = 'bg-gray-400 cursor-not-allowed';
            $buttonDisabled = true;
            break;

        default:
            // Handle case where status might be a string or enum
            $statusLabel = is_object($session->status) && method_exists($session->status, 'label')
                ? $session->status->label()
                : $session->status;
            $meetingMessage = __('meetings.status.session_status_prefix') . ' ' . $statusLabel;
            $buttonText = __('meetings.status.unavailable');
            $buttonClass = 'bg-gray-400 cursor-not-allowed';
            $buttonDisabled = true;
    }
@endphp

<!-- JavaScript Translations Object -->
<script>
    window.meetingTranslations = {
        status: {
            session_ready: @json(__('meetings.status.session_ready')),
            session_ongoing: @json(__('meetings.status.session_ongoing')),
            session_ended: @json(__('meetings.timer.session_ended')),
            preparation_time: @json(__('meetings.timer.preparation_time')),
            session_active: @json(__('meetings.timer.session_active')),
            overtime: @json(__('meetings.timer.overtime')),
            waiting_session: @json(__('meetings.timer.waiting_session')),
            time_until_start: @json(__('meetings.timer.time_until_start')),
            waiting_start: @json(__('meetings.timer.waiting_start')),
            session_active_since: @json(__('meetings.timer.session_active_since')),
            session_currently_active: @json(__('meetings.timer.session_currently_active')),
            overtime_since: @json(__('meetings.timer.overtime_since')),
            session_in_overtime: @json(__('meetings.timer.session_in_overtime')),
        },
        buttons: {
            connecting: @json(__('meetings.buttons.connecting')),
            connected: @json(__('meetings.buttons.connected')),
            retry: @json(__('meetings.buttons.retry')),
            livekit_unavailable: @json(__('meetings.buttons.livekit_unavailable')),
            init_error: @json(__('meetings.buttons.init_error')),
        },
        messages: {
            auto_terminated: @json(__('meetings.messages.auto_terminated')),
            auto_terminated_description: @json(__('meetings.messages.auto_terminated_description')),
            session_cancelled_success: @json(__('meetings.messages.session_cancelled_success')),
            cancel_failed: @json(__('meetings.messages.cancel_failed')),
            unknown_error: @json(__('meetings.messages.unknown_error')),
            cancel_error: @json(__('meetings.messages.cancel_error')),
            absent_marked_success: @json(__('meetings.messages.absent_marked_success')),
            absent_mark_failed: @json(__('meetings.messages.absent_mark_failed')),
            absent_mark_error: @json(__('meetings.messages.absent_mark_error')),
            session_ended_success: @json(__('meetings.messages.session_ended_success')),
            end_failed: @json(__('meetings.messages.end_failed')),
            end_error: @json(__('meetings.messages.end_error')),
            connection_failed: @json(__('meetings.messages.connection_failed')),
            unexpected_error: @json(__('meetings.messages.unexpected_error')),
            email_not_verified: @json(__('meetings.messages.email_not_verified')),
            session_not_found: @json(__('meetings.messages.session_not_found')),
            not_authorized: @json(__('meetings.messages.not_authorized')),
            session_not_started: @json(__('meetings.messages.session_not_started')),
            session_already_ended: @json(__('meetings.messages.session_already_ended')),
        },
        attendance: {
            present: @json(__('meetings.attendance.present')),
            late: @json(__('meetings.attendance.late')),
            left_early: @json(__('meetings.attendance.left_early')),
            absent: @json(__('meetings.attendance.absent')),
            attended_before: @json(__('meetings.attendance.attended_before')),
            not_joined_yet: @json(__('meetings.attendance.not_joined_yet')),
            in_session_now: @json(__('meetings.attendance.in_session_now')),
            duration_prefix: @json(__('meetings.attendance.duration_prefix')),
            not_started: @json(__('meetings.attendance.not_started')),
            waiting_start: @json(__('meetings.attendance.waiting_start')),
            did_not_attend: @json(__('meetings.attendance.did_not_attend')),
            session_ended: @json(__('meetings.attendance.session_ended')),
            attended_session: @json(__('meetings.attendance.attended_session')),
            attended_late: @json(__('meetings.attendance.attended_late')),
            in_session: @json(__('meetings.attendance.in_session')),
            session_ongoing: @json(__('meetings.attendance.session_ongoing')),
            attendance_tracked: @json(__('meetings.attendance.attendance_tracked')),
            attendance_failed: @json(__('meetings.attendance.attendance_failed')),
            join_failed: @json(__('meetings.attendance.join_failed')),
            leave_failed: @json(__('meetings.attendance.leave_failed')),
        },
        network: {
            offline: @json(__('meetings.network.offline')),
            reconnecting: @json(__('meetings.network.reconnecting')),
            reconnecting_session: @json(__('meetings.network.reconnecting_session')),
            reconnect_failed: @json(__('meetings.network.reconnect_failed')),
            reconnect_error: @json(__('meetings.network.reconnect_error')),
            connected: @json(__('meetings.network.connected')),
        },
        loading: {
            connecting_meeting: @json(__('meetings.loading.connecting_meeting')),
            please_wait: @json(__('meetings.loading.please_wait')),
        },
        recording: {
            start_recording: @json(__('meetings.recording.start_recording')),
            stop_recording: @json(__('meetings.recording.stop_recording')),
            recording_stopped: @json(__('meetings.recording.recording_stopped')),
            recording_started: @json(__('meetings.recording.recording_started')),
            recording_error: @json(__('meetings.recording.recording_error')),
            start_failed: @json(__('meetings.recording.start_failed')),
            no_active_recording: @json(__('meetings.recording.no_active_recording')),
            title: @json(__('meetings.recording.title')),
            started: @json(__('meetings.recording.started')),
            stopped: @json(__('meetings.recording.stopped')),
            error: @json(__('meetings.recording.error')),
        },
        confirm: {
            cancel_session: @json(__('meetings.confirm.cancel_session')),
            mark_absent: @json(__('meetings.confirm.mark_absent')),
            end_session: @json(__('meetings.confirm.end_session')),
        },
        info: {
            participant: @json(__('meetings.info.participant')),
            fullscreen: @json(__('meetings.info.fullscreen')),
            exit_fullscreen: @json(__('meetings.info.exit_fullscreen')),
            minute: @json(__('meetings.info.minute')),
        },
        system: {
            allowed: @json(__('meetings.system.allowed')),
            denied: @json(__('meetings.system.denied')),
            needs_permission: @json(__('meetings.system.needs_permission')),
            unknown: @json(__('meetings.system.unknown')),
            connected: @json(__('meetings.system.connected')),
            not_connected: @json(__('meetings.system.not_connected')),
            compatible: @json(__('meetings.system.compatible')),
            not_compatible: @json(__('meetings.system.not_compatible')),
        },
        timer: {
            time_until_start: @json(__('meetings.timer.time_until_start')),
            waiting_start: @json(__('meetings.timer.waiting_start')),
            session_active_since: @json(__('meetings.timer.session_active_since')),
            session_currently_active: @json(__('meetings.timer.session_currently_active')),
            overtime_since: @json(__('meetings.timer.overtime_since')),
            session_in_overtime: @json(__('meetings.timer.session_in_overtime')),
            session_ended: @json(__('meetings.timer.session_ended')),
            starting_soon: @json(__('meetings.timer.starting_soon')),
        },
        // Participants & Roles
        participants: {
            teacher: @json(__('meetings.participants.teacher')),
            student: @json(__('meetings.participants.student')),
            admin: @json(__('meetings.participants.admin')),
            participant: @json(__('meetings.participants.participant')),
            you: @json(__('meetings.participants.you')),
            joined: @json(__('meetings.participants.joined')),
            left: @json(__('meetings.participants.left')),
            no_other_participants: @json(__('meetings.participants.no_other_participants')),
        },
        // Permission Messages
        permissions: {
            mic_not_allowed_by_teacher: @json(__('meetings.permissions.mic_not_allowed_by_teacher')),
            camera_not_allowed_by_teacher: @json(__('meetings.permissions.camera_not_allowed_by_teacher')),
            mic_disabled_by_teacher: @json(__('meetings.permissions.mic_disabled_by_teacher')),
            cannot_unmute: @json(__('meetings.permissions.cannot_unmute')),
            teacher_muted_all: @json(__('meetings.permissions.teacher_muted_all')),
            teacher_controls_audio: @json(__('meetings.permissions.teacher_controls_audio')),
            speaking_permission_granted: @json(__('meetings.permissions.speaking_permission_granted')),
            auto_unmute_error: @json(__('meetings.permissions.auto_unmute_error')),
            cannot_manage_hands: @json(__('meetings.permissions.cannot_manage_hands')),
            cannot_raise_hand: @json(__('meetings.permissions.cannot_raise_hand')),
            cannot_manage_audio: @json(__('meetings.permissions.cannot_manage_audio')),
            cannot_manage_camera: @json(__('meetings.permissions.cannot_manage_camera')),
            cannot_record: @json(__('meetings.permissions.cannot_record')),
            no_media_permissions: @json(__('meetings.permissions.no_media_permissions')),
            camera_control_not_allowed: @json(__('meetings.permissions.camera_control_not_allowed')),
            recording_not_allowed: @json(__('meetings.permissions.recording_not_allowed')),
            mic_permission_granted: @json(__('meetings.permissions.mic_permission_granted')),
        },
        // Controls (button labels and tooltips)
        controls: {
            start_mic: @json(__('meetings.controls.start_mic')),
            stop_mic: @json(__('meetings.controls.stop_mic')),
            mic_disabled_by_teacher: @json(__('meetings.controls.mic_disabled_by_teacher')),
            start_camera: @json(__('meetings.controls.start_camera')),
            stop_camera: @json(__('meetings.controls.stop_camera')),
            start_screen_share: @json(__('meetings.controls.start_screen_share')),
            stop_screen_share: @json(__('meetings.controls.stop_screen_share')),
            raise_hand: @json(__('meetings.controls.raise_hand')),
            lower_hand: @json(__('meetings.controls.lower_hand')),
            start_recording: @json(__('meetings.controls.start_recording')),
            stop_recording: @json(__('meetings.controls.stop_recording')),
        },
        // Control States
        control_states: {
            mic_enabled: @json(__('meetings.control_states.mic_enabled')),
            mic_disabled: @json(__('meetings.control_states.mic_disabled')),
            camera_enabled: @json(__('meetings.control_states.camera_enabled')),
            camera_disabled: @json(__('meetings.control_states.camera_disabled')),
            microphone: @json(__('meetings.control_states.microphone')),
            camera: @json(__('meetings.control_states.camera')),
            screen_share: @json(__('meetings.control_states.screen_share')),
            hand: @json(__('meetings.control_states.hand')),
            raised: @json(__('meetings.control_states.raised')),
            lowered: @json(__('meetings.control_states.lowered')),
            recording: @json(__('meetings.control_states.recording')),
            started: @json(__('meetings.control_states.started')),
            stopped: @json(__('meetings.control_states.stopped')),
            toggle_mic: @json(__('meetings.control_states.toggle_mic')),
            toggle_camera: @json(__('meetings.control_states.toggle_camera')),
            enable_mic: @json(__('meetings.control_states.enable_mic')),
            disable_mic: @json(__('meetings.control_states.disable_mic')),
            enable_camera: @json(__('meetings.control_states.enable_camera')),
            disable_camera: @json(__('meetings.control_states.disable_camera')),
            start_screen_share: @json(__('meetings.control_states.start_screen_share')),
            stop_screen_share: @json(__('meetings.control_states.stop_screen_share')),
            raise_hand: @json(__('meetings.control_states.raise_hand')),
            lower_hand: @json(__('meetings.control_states.lower_hand')),
            start_recording: @json(__('meetings.control_states.start_recording')),
            stop_recording: @json(__('meetings.control_states.stop_recording')),
            hand_raised: @json(__('meetings.control_states.hand_raised')),
        },
        // Control Errors
        control_errors: {
            not_connected: @json(__('meetings.control_errors.not_connected')),
            mic_error: @json(__('meetings.control_errors.mic_error')),
            camera_error: @json(__('meetings.control_errors.camera_error')),
            screen_share_error: @json(__('meetings.control_errors.screen_share_error')),
            screen_share_denied: @json(__('meetings.control_errors.screen_share_denied')),
            screen_share_not_supported: @json(__('meetings.control_errors.screen_share_not_supported')),
            hand_raise_error: @json(__('meetings.control_errors.hand_raise_error')),
            recording_error: @json(__('meetings.control_errors.recording_error')),
            send_message_error: @json(__('meetings.control_errors.send_message_error')),
            chat_data_error: @json(__('meetings.control_errors.chat_data_error')),
        },
        // Hand Raise
        hand_raise: {
            hand_raised_notification: @json(__('meetings.hand_raise.hand_raised_notification')),
            granted_permission: @json(__('meetings.hand_raise.granted_permission')),
            grant_error: @json(__('meetings.hand_raise.grant_error')),
            hand_raised_label: @json(__('meetings.hand_raise.hand_raised_label')),
            hand_raised: @json(__('meetings.hand_raise.hand_raised')),
            hide_hand: @json(__('meetings.hand_raise.hide_hand')),
            all_hands_cleared: @json(__('meetings.hand_raise.all_hands_cleared')),
            clear_hands_error: @json(__('meetings.hand_raise.clear_hands_error')),
            minutes_ago: @json(__('meetings.hand_raise.minutes_ago')),
            seconds_ago: @json(__('meetings.hand_raise.seconds_ago')),
            teacher_dismissed_hand: @json(__('meetings.hand_raise.teacher_dismissed_hand')),
            all_hands_cleared_by_teacher: @json(__('meetings.hand_raise.all_hands_cleared_by_teacher')),
        },
        // Student Control
        student_control: {
            all_students_muted: @json(__('meetings.student_control.all_students_muted')),
            students_mic_allowed: @json(__('meetings.student_control.students_mic_allowed')),
            students_can_use_mic: @json(__('meetings.student_control.students_can_use_mic')),
            manage_students_mic_error: @json(__('meetings.student_control.manage_students_mic_error')),
            mic_control_error: @json(__('meetings.student_control.mic_control_error')),
            all_students_camera_disabled: @json(__('meetings.student_control.all_students_camera_disabled')),
            all_cameras_disabled: @json(__('meetings.student_control.all_cameras_disabled')),
            students_camera_allowed: @json(__('meetings.student_control.students_camera_allowed')),
            students_can_use_camera: @json(__('meetings.student_control.students_can_use_camera')),
            manage_students_camera_error: @json(__('meetings.student_control.manage_students_camera_error')),
            camera_control_error: @json(__('meetings.student_control.camera_control_error')),
            mic_permission_granted_by: @json(__('meetings.student_control.mic_permission_granted_by')),
            mic_revoked_by: @json(__('meetings.student_control.mic_revoked_by')),
            all_muted_by: @json(__('meetings.student_control.all_muted_by')),
            mic_allowed_by: @json(__('meetings.student_control.mic_allowed_by')),
        },
        // Data Channel Messages
        data_channel: {
            all_students_muted: @json(__('meetings.data_channel.all_students_muted')),
            mic_allowed: @json(__('meetings.data_channel.mic_allowed')),
            all_hands_cleared: @json(__('meetings.data_channel.all_hands_cleared')),
            teacher_hid_hand: @json(__('meetings.data_channel.teacher_hid_hand')),
            mic_permission_granted: @json(__('meetings.data_channel.mic_permission_granted')),
            session_ended_by_teacher: @json(__('meetings.data_channel.session_ended_by_teacher')),
            removed_from_session: @json(__('meetings.data_channel.removed_from_session')),
        },
        // Screen Share
        screen_share: {
            click_to_enlarge: @json(__('meetings.screen_share.click_to_enlarge')),
            your_shared_screen: @json(__('meetings.screen_share.your_shared_screen')),
            your_screen: @json(__('meetings.screen_share.your_screen')),
            screen_of: @json(__('meetings.screen_share.screen_of')),
            screen_share_paused: @json(__('meetings.screen_share.screen_share_paused')),
            screen_share_stopped: @json(__('meetings.screen_share.screen_share_stopped')),
        },
        // Sidebar & UI
        sidebar: {
            chat: @json(__('meetings.sidebar.chat')),
            participants: @json(__('meetings.sidebar.participants')),
            raised_hands: @json(__('meetings.sidebar.raised_hands')),
            settings: @json(__('meetings.sidebar.settings')),
            fullscreen: @json(__('meetings.sidebar.fullscreen')),
            exit_fullscreen: @json(__('meetings.sidebar.exit_fullscreen')),
        },
        // Leave Dialog
        leave: {
            title: @json(__('meetings.leave.title')),
            confirm_message: @json(__('meetings.leave.confirm_message')),
            cancel: @json(__('meetings.leave.cancel')),
            leave: @json(__('meetings.leave.leave')),
        },
        // Connection Messages
        connection: {
            failed: @json(__('meetings.connection.failed')),
            setup_failed: @json(__('meetings.connection.setup_failed')),
            joined_successfully: @json(__('meetings.connection.joined_successfully')),
            joined_may_need_camera: @json(__('meetings.connection.joined_may_need_camera')),
            mic_access_denied: @json(__('meetings.connection.mic_access_denied')),
            camera_access_denied: @json(__('meetings.connection.camera_access_denied')),
            permission_denied: @json(__('meetings.connection.permission_denied')),
            joined_teacher_mic_on: @json(__('meetings.connection.joined_teacher_mic_on')),
            joined_student_muted: @json(__('meetings.connection.joined_student_muted')),
            connected: @json(__('meetings.connection.connected')),
            disconnected: @json(__('meetings.connection.disconnected')),
            disconnected_reconnecting: @json(__('meetings.connection.disconnected_reconnecting')),
            reconnecting: @json(__('meetings.connection.reconnecting')),
            reconnected: @json(__('meetings.connection.reconnected')),
        },
        // Chat
        chat: {
            you: @json(__('meetings.chat.you')),
            send_error: @json(__('meetings.chat.send_error')),
            no_other_participants: @json(__('meetings.chat.no_other_participants')),
        },
        // Fullscreen
        fullscreen: {
            enter: @json(__('meetings.fullscreen.enter')),
            exit: @json(__('meetings.fullscreen.exit')),
        },
        // Session
        session: {
            ended_by_teacher: @json(__('meetings.session.ended_by_teacher')),
            kicked_from_session: @json(__('meetings.session.kicked_from_session')),
        },
    };

    /**
     * Translation helper function for JavaScript
     * @param {string} key - Dot notation key (e.g., 'connection.failed')
     * @param {Object} replacements - Key-value pairs for placeholder replacements (e.g., {name: 'John'})
     * @returns {string} Translated string or key if not found
     */
    window.t = function(key, replacements = {}) {
        const keys = key.split('.');
        let value = window.meetingTranslations;

        for (const k of keys) {
            if (value && typeof value === 'object' && k in value) {
                value = value[k];
            } else {
                console.warn(`Translation not found: ${key}`);
                return key;
            }
        }

        if (typeof value !== 'string') {
            console.warn(`Translation key ${key} is not a string`);
            return key;
        }

        // Handle placeholder replacements (e.g., :name)
        for (const [placeholder, replacement] of Object.entries(replacements)) {
            value = value.replace(new RegExp(`:${placeholder}`, 'g'), replacement);
        }

        return value;
    };
</script>

<!-- Meeting interface CSS is loaded via resources/css/meeting-interface.css through Vite -->

<!-- LiveKit JavaScript SDK - SPECIFIC WORKING VERSION -->
<script>
    // Loading LiveKit SDK

    function loadLiveKitScript() {
        return new Promise((resolve, reject) => {
            // Use official latest version from CDN
            const script = document.createElement('script');
            script.src = 'https://cdn.jsdelivr.net/npm/livekit-client/dist/livekit-client.umd.min.js';
            script.crossOrigin = 'anonymous';

            script.onload = () => {
                // LiveKit script loaded
                // Check for various possible global names
                setTimeout(() => {
                    const possibleNames = ['LiveKit', 'LiveKitClient', 'LivekitClient', 'livekit'];
                    let livekitFound = null;

                    for (const name of possibleNames) {
                        if (typeof window[name] !== 'undefined') {
                            livekitFound = window[name];
                            window.LiveKit = livekitFound; // Normalize to LiveKit
                            // LiveKit found
                            break;
                        }
                    }

                    if (livekitFound) {
                        // LiveKit SDK available
                        resolve();
                    } else {
                        reject(new Error('LiveKit global not found'));
                    }
                }, 200);
            };

            script.onerror = (error) => {
                reject(new Error('Failed to load LiveKit script'));
            };

            document.head.appendChild(script);
        });
    }

    // Start loading LiveKit
    window.livekitLoadPromise = loadLiveKitScript();
</script>

<!-- Load LiveKit Classes in Correct Order -->
<script>
    // Track loading states
    let scriptsLoaded = {
        api: false,
        dataChannel: false,
        connection: false,
        tracks: false,
        participants: false,
        controls: false,
        layout: false,
        index: false
    };

    // Store interval IDs for cleanup (prevents memory leaks)
    let sessionStatusPollingInterval = null;

    function checkAllScriptsLoaded() {
        const allLoaded = Object.values(scriptsLoaded).every(loaded => loaded);
        if (allLoaded) {
            // Store session configuration
            window.sessionId = '{{ $session->id }}';
            window.sessionType = '{{ $isAcademicSession ? 'academic' : ($isInteractiveCourseSession ? 'interactive' : 'quran') }}';
            window.auth = @json(['user' => ['id' => auth()->id(), 'name' => auth()->user()->name]]);
        }
    }

    function loadScript(src, name) {
        return new Promise((resolve, reject) => {
            const script = document.createElement('script');
            script.src = src;
            script.onload = () => {
                scriptsLoaded[name] = true;
                checkAllScriptsLoaded();
                resolve();
            };
            script.onerror = reject;
            document.head.appendChild(script);
        });
    }

    // Load LiveKit scripts in order: API helper first, then session timer, then modules
    Promise.resolve()
        .then(() => loadScript('{{ asset("js/livekit/api.js") }}?v={{ time() }}', 'api'))
        .then(() => loadScript('{{ asset("js/session-timer.js") }}?v={{ time() }}', 'sessionTimer'))
        .then(() => loadScript('{{ asset("js/livekit/data-channel.js") }}?v={{ time() }}', 'dataChannel'))
        .then(() => loadScript('{{ asset("js/livekit/connection.js") }}?v={{ time() }}', 'connection'))
        .then(() => loadScript('{{ asset("js/livekit/tracks.js") }}?v={{ time() }}', 'tracks'))
        .then(() => loadScript('{{ asset("js/livekit/participants.js") }}?v={{ time() }}', 'participants'))
        .then(() => loadScript('{{ asset("js/livekit/controls.js") }}?v={{ time() }}', 'controls'))
        .then(() => loadScript('{{ asset("js/livekit/layout.js") }}?v={{ time() }}', 'layout'))
        .then(() => loadScript('{{ asset("js/livekit/index.js") }}?v={{ time() }}', 'index'))
        .catch(() => {
            // Silent fail - LiveKit scripts loading error
        });

    // CRITICAL FIX: Initialize Smart Session Timer with immediate loading and display
    @if($session->scheduled_at)
    function initializeSessionTimer() {
        const timerConfig = {
            sessionId: {{ $session->id }},
            scheduledAt: '{{ $session->scheduled_at->toISOString() }}',
            durationMinutes: {{ $session->duration_minutes ?? 30 }},
            preparationMinutes: {{ $preparationMinutes }},
            endingBufferMinutes: {{ $endingBufferMinutes }},
            timerElementId: 'session-timer',
            phaseElementId: 'timer-phase',
            displayElementId: 'time-display',
            meetingTimerElementId: 'meetingTimer',
            
            onPhaseChange: function(newPhase, oldPhase) {
                updateSessionPhaseUI(newPhase);

                // AUTO-TERMINATION: End meeting when time expires
                if (newPhase === 'ended' && oldPhase !== 'ended') {
                    autoTerminateMeeting();
                }
            },
            
            onTick: function(timing) {
                updateSessionProgress(timing);
            }
        };

        if (typeof SmartSessionTimer !== 'undefined') {
            window.sessionTimer = new SmartSessionTimer(timerConfig);
        } else {
            loadScript('{{ asset("js/session-timer.js") }}', 'sessionTimer').then(() => {
                window.sessionTimer = new SmartSessionTimer(timerConfig);
            }).catch(() => {
                // Silent fail - timer will not work
            });
        }
    }

    // Initialize timer immediately
    initializeSessionTimer();
    @endif

    /**
     * Auto-terminate meeting when time expires
     */
    function autoTerminateMeeting() {
        // Show notification to user
        if (typeof showNotification !== 'undefined') {
            showNotification(window.meetingTranslations.messages.auto_terminated, 'info');
        }

        // Disconnect from LiveKit room if connected
        if (window.room && window.room.state === 'connected') {
            try {
                window.room.disconnect();
            } catch {
                // Silent fail - room disconnect error
            }
        }

        // Record attendance leave if tracking
        if (window.attendanceTracker && window.attendanceTracker.isTracking) {
            window.attendanceTracker.recordLeave();
        }
        
        // Disable meeting controls
        const startMeetingBtn = document.getElementById('startMeeting');
        const joinMeetingBtn = document.getElementById('joinMeeting');
        const leaveMeetingBtn = document.getElementById('leaveMeeting');
        
        if (startMeetingBtn) {
            startMeetingBtn.disabled = true;
            startMeetingBtn.innerHTML = '<i class="ri-time-line text-xl"></i>';
            startMeetingBtn.title = window.meetingTranslations.status.session_ended;
        }

        if (joinMeetingBtn) {
            joinMeetingBtn.disabled = true;
            joinMeetingBtn.innerHTML = '<i class="ri-time-line text-xl"></i>';
            joinMeetingBtn.title = window.meetingTranslations.status.session_ended;
        }
        
        if (leaveMeetingBtn) {
            leaveMeetingBtn.style.display = 'none';
        }
        
        // Update UI to show session ended
        const connectionStatus = document.getElementById('connectionStatus');
        if (connectionStatus) {
            connectionStatus.innerHTML = '<div class="flex items-center justify-center gap-2"><i class="ri-time-line text-gray-500"></i><span class="text-gray-500">' + window.meetingTranslations.status.session_ended + '</span></div>';
        }
        
        // Hide video grid and show session ended message
        const videoGrid = document.getElementById('videoGrid');
        if (videoGrid) {
            videoGrid.innerHTML = `
                <div class="flex flex-col items-center justify-center h-64 text-center">
                    <i class="ri-time-line text-6xl text-gray-400 mb-4"></i>
                    <h3 class="text-xl font-semibold text-gray-600 mb-2">${window.meetingTranslations.status.session_ended}</h3>
                    <p class="text-gray-500">${window.meetingTranslations.messages.auto_terminated_description}</p>
                </div>
            `;
        }
        
    }

    // Initialize Attendance Status Tracking (only for students)
    // CRITICAL FIX: Don't start attendance tracking on page load - only when meeting actually starts
    @if($userType === 'student')
    // Attendance tracking will be initialized by AutoAttendanceTracker when meeting starts
    @endif
    
    // Initialize Real-time Session Status Polling
    initializeSessionStatusPolling();
    
    // Initialize Network Reconnection Handling
    initializeNetworkReconnection();

    // CRITICAL FIX: Check initial session status to handle completed sessions
    checkInitialSessionStatus();

    // Update session phase UI based on timer phase
    function updateSessionPhaseUI(phase) {
        const headerElement = document.querySelector('.session-status-header');
        const timerElement = document.getElementById('session-timer');
        const statusMessage = document.querySelector('.status-message p');
        
        if (!headerElement || !timerElement) return;
        
        // Update header background based on phase
        headerElement.className = 'session-status-header px-6 py-4 border-b border-gray-100 transition-colors duration-500';
        timerElement.setAttribute('data-phase', phase);
        
        switch(phase) {
            case 'not_started':
                headerElement.classList.add('bg-gradient-to-r', 'from-gray-50', 'to-gray-100');
                break;
            case 'preparation':
                headerElement.classList.add('bg-gradient-to-r', 'from-yellow-50', 'to-amber-50');
                if (statusMessage) statusMessage.textContent = window.meetingTranslations.status.preparation_time;
                break;
            case 'session':
                headerElement.classList.add('bg-gradient-to-r', 'from-green-50', 'to-emerald-50');
                if (statusMessage) statusMessage.textContent = window.meetingTranslations.status.session_active;
                break;
            case 'overtime':
                headerElement.classList.add('bg-gradient-to-r', 'from-red-50', 'to-rose-50');
                if (statusMessage) statusMessage.textContent = window.meetingTranslations.status.overtime;
                break;
            case 'ended':
                headerElement.classList.add('bg-gradient-to-r', 'from-gray-50', 'to-slate-50');
                if (statusMessage) statusMessage.textContent = window.meetingTranslations.status.session_ended;

                // Stop timer when session ends
                if (window.sessionTimer) {
                    window.sessionTimer.stop();

                    // Set timer display to 00:00
                    const timeDisplay = document.getElementById('time-display');
                    if (timeDisplay) {
                        timeDisplay.textContent = '00:00';
                    }
                }
                break;
        }
    }

    // Update session progress
    function updateSessionProgress(timing) {
        // Update any additional UI based on timing
        // This can be expanded for more detailed progress tracking
    }

    // Disabled - AutoAttendanceTracker handles all attendance tracking now
    function initializeAttendanceTracking() {
        // No automatic API calls on page load
    }

    // Initialize session status polling for real-time updates
    function initializeSessionStatusPolling() {
        // Check session status every 10 seconds for real-time button updates
        checkSessionStatus();
        // Store interval ID for cleanup on page unload (prevents memory leak)
        sessionStatusPollingInterval = setInterval(checkSessionStatus, 10000);
    }

    // Stop session status polling (for cleanup)
    function stopSessionStatusPolling() {
        if (sessionStatusPollingInterval) {
            clearInterval(sessionStatusPollingInterval);
            sessionStatusPollingInterval = null;
        }
    }

    // Check initial session status (for when page loads on a completed session)
    function checkInitialSessionStatus() {
        // Get server-side session status from PHP
        const sessionStatus = '{{ is_object($session->status) && method_exists($session->status, 'value') ? $session->status->value : (is_object($session->status) ? $session->status->name : $session->status) }}';
        
        if (sessionStatus === 'completed') {
            
            // Stop timer if it exists
            if (window.sessionTimer) {
                window.sessionTimer.stop();
            }
            
            // Set timer display to 00:00
            const timeDisplay = document.getElementById('time-display');
            if (timeDisplay) {
                timeDisplay.textContent = '00:00';
            }
            
            // Update phase to ended
            updateSessionPhaseUI('ended');
        }
    }

    // Check session status and update UI accordingly
    function checkSessionStatus() {
        fetchWithAuth(`/web-api/sessions/{{ $session->id }}/status?type={{ $sessionTypeForApi }}`)
            .then(response => response.json())
            .then(data => {
                updateSessionStatusUI(data);
            })
            .catch(error => {
            });
    }

    // Update session status UI based on server response
    function updateSessionStatusUI(statusData) {
        const meetingBtn = document.getElementById('startMeetingBtn');
        const meetingBtnText = document.getElementById('meetingBtnText');
        const statusMessage = document.querySelector('.status-message p');

        if (!meetingBtn || !meetingBtnText || !statusMessage) return;

        // API response wraps data in { success, message, data } structure
        // Extract the actual data from the response
        const responseData = statusData.data || statusData;
        const { status, can_join, message, button_text, button_class } = responseData;

        // Update button text and message
        meetingBtnText.textContent = button_text;
        statusMessage.textContent = message;

        // Update button classes and state
        meetingBtn.className = `join-button ${button_class} text-white px-8 py-4 rounded-xl font-semibold transition-all duration-300 flex items-center gap-3 mx-auto min-w-[240px] justify-center shadow-lg transform hover:scale-105`;
        
        // Enable/disable button based on can_join status
        if (can_join) {
            meetingBtn.disabled = false;
            meetingBtn.removeAttribute('disabled');
            meetingBtn.setAttribute('data-state', 'ready');
        } else {
            meetingBtn.disabled = true;
            meetingBtn.setAttribute('disabled', 'disabled');
            meetingBtn.setAttribute('data-state', 'waiting');
        }

        // Update icon based on status
        const iconElement = meetingBtn.querySelector('i');
        if (iconElement) {
            if (can_join) {
                iconElement.className = 'ri-video-on-line text-xl';
            } else {
                // Use status-specific icons
                iconElement.className = getStatusIcon(status) + ' text-xl';
            }
        }

        // Enum constants for JavaScript (matching PHP SessionStatus enum)
        const SessionStatus = {
            UNSCHEDULED: 'unscheduled',
            SCHEDULED: 'scheduled',
            READY: 'ready',
            ONGOING: 'ongoing',
            COMPLETED: 'completed',
            CANCELLED: 'cancelled',
            ABSENT: 'absent'
        };

        // CRITICAL FIX: Stop timer when session is completed
        if (status === SessionStatus.COMPLETED && window.sessionTimer) {
            window.sessionTimer.stop();
            
            // Mark timer as permanently stopped to prevent restart
            window.sessionTimer.isSessionCompleted = true;
            
            // Set timer display to 00:00 and prevent further updates
            const timeDisplay = document.getElementById('time-display');
            if (timeDisplay) {
                timeDisplay.textContent = '00:00';
                // Lock the display to prevent timer updates
                timeDisplay.dataset.locked = 'true';
            }
            
            // Update phase to ended
            updateSessionPhaseUI('ended');
        }
    }

    // Get icon for session status
    function getStatusIcon(status) {
        const icons = {
            'unscheduled': 'ri-draft-line',
            'scheduled': 'ri-calendar-line',
            'ready': 'ri-video-on-line',
            'ongoing': 'ri-live-line',
            'completed': 'ri-check-circle-line',
            'cancelled': 'ri-close-circle-line',
            'absent': 'ri-user-unfollow-line'
        };
        return icons[status] || 'ri-question-line';
    }

    // Enhanced fetch with authentication and error handling
    async function fetchWithAuth(url, options = {}) {
        const defaultHeaders = {
            'X-Requested-With': 'XMLHttpRequest',
            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.getAttribute('content'),
            'Accept': 'application/json',
            'Content-Type': 'application/json'
        };

        const config = {
            ...options,
            credentials: 'same-origin', // CRITICAL: Include session cookies for authentication
            headers: {
                ...defaultHeaders,
                ...options.headers
            }
        };

        try {
            const response = await fetch(url, config);
            
            // Handle authentication errors
            if (response.status === 401) {
                
                // Try to refresh CSRF token
                await refreshCSRFToken();
                
                // Retry with new token
                const newToken = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content');
                config.headers['X-CSRF-TOKEN'] = newToken;
                
                return await fetch(url, config);
            }
            
            if (!response.ok) {
                throw new Error(`HTTP ${response.status}: ${response.statusText}`);
            }
            
            return response;
        } catch (error) {
            throw error;
        }
    }

    // Refresh CSRF token
    async function refreshCSRFToken() {
        try {
            const response = await fetch('/csrf-token', {
                method: 'GET',
                credentials: 'same-origin', // Include session cookies
                headers: {
                    'X-Requested-With': 'XMLHttpRequest'
                }
            });
            
            if (response.ok) {
                const data = await response.json();
                document.querySelector('meta[name="csrf-token"]')?.setAttribute('content', data.token);
            }
        } catch (error) {
            // Fallback: reload page if token refresh fails repeatedly
            if (window.tokenRefreshAttempts > 2) {
                window.location.reload();
            }
            window.tokenRefreshAttempts = (window.tokenRefreshAttempts || 0) + 1;
        }
    }

    // CRITICAL FIX: Disable old attendance tracking function
    // This function was causing attendance tracking on page load
    function updateAttendanceStatus() {
        return; // Do nothing - AutoAttendanceTracker handles all attendance tracking
        
        /* OLD CODE DISABLED - was causing page load attendance tracking
        fetchWithAuth(`/web-api/sessions/{{ $session->id }}/attendance-status`)
        .then(response => response.json())
        .then(data => {
            const statusElement = document.getElementById('attendance-status');
            const textElement = statusElement?.querySelector('.attendance-text');
            const timeElement = statusElement?.querySelector('.attendance-time');
            const dotElement = statusElement?.querySelector('.attendance-dot');
            
            if (!statusElement || !textElement || !timeElement) return;
            
            // Update status text - using localized labels
            const statusLabels = @json(__('meetings.attendance.status_labels'));
            const attendanceTranslations = {
                present: @json(__('meetings.attendance.present')),
                attendedBefore: @json(__('meetings.attendance.attended_before')),
                notJoinedYet: @json(__('meetings.attendance.not_joined_yet')),
                inSessionNow: @json(__('meetings.attendance.in_session_now')),
                durationPrefix: @json(__('meetings.attendance.duration_prefix'))
            };

            const isInMeeting = data.is_currently_in_meeting;

            // Better status detection for active users
            let statusLabel;
            if (isInMeeting) {
                statusLabel = attendanceTranslations.present; // User is currently in meeting
            } else if (data.duration_minutes > 0) {
                statusLabel = statusLabels[data.attendance_status] || attendanceTranslations.attendedBefore;
            } else {
                statusLabel = statusLabels[data.attendance_status] || attendanceTranslations.notJoinedYet;
            }

            textElement.textContent = isInMeeting ?
                `${statusLabel} ${attendanceTranslations.inSessionNow}` :
                statusLabel;

            // Update time info
            if (data.duration_minutes > 0) {
                timeElement.textContent = `${attendanceTranslations.durationPrefix} ${data.duration_minutes}`;
            } else {
                timeElement.textContent = '--';
            }
            
            // Update dot color
            if (dotElement) {
                dotElement.className = 'attendance-dot w-3 h-3 rounded-full transition-all duration-300';

                const AttendanceStatus = {
                    ATTENDED: 'attended',
                    PRESENT: 'present',
                    LATE: 'late',
                    LEFT: 'left',
                    PARTIAL: 'partial',
                    ABSENT: 'absent'
                };

                if (isInMeeting) {
                    dotElement.classList.add('bg-green-500', 'animate-pulse');
                } else if (data.attendance_status === AttendanceStatus.ATTENDED || data.attendance_status === AttendanceStatus.PRESENT) {
                    dotElement.classList.add('bg-green-400');
                } else if (data.attendance_status === AttendanceStatus.LATE) {
                    dotElement.classList.add('bg-yellow-400');
                } else if (data.attendance_status === AttendanceStatus.LEFT || data.attendance_status === AttendanceStatus.PARTIAL) {
                    dotElement.classList.add('bg-orange-400');
                } else {
                    dotElement.classList.add('bg-gray-400');
                }
            }

        })
        .catch(error => {
        });
        */ // END OF DISABLED CODE
    }

    // Initialize network reconnection handling
    function initializeNetworkReconnection() {
        let isOnline = navigator.onLine;
        let reconnectAttempts = 0;
        const maxReconnectAttempts = 5;

        // Listen for online/offline events
        window.addEventListener('online', handleNetworkOnline);
        window.addEventListener('offline', handleNetworkOffline);

        function handleNetworkOffline() {
            isOnline = false;
            showNetworkStatus(window.meetingTranslations.network.offline, 'offline');
        }

        function handleNetworkOnline() {
            isOnline = true;
            showNetworkStatus(window.meetingTranslations.network.reconnecting, 'reconnecting');
            
            // Reset token refresh attempts
            window.tokenRefreshAttempts = 0;
            
            // Attempt to reconnect LiveKit and refresh data
            setTimeout(attemptReconnection, 1000);
        }

        async function attemptReconnection() {
            if (!isOnline || reconnectAttempts >= maxReconnectAttempts) {
                if (reconnectAttempts >= maxReconnectAttempts) {
                    showNetworkStatus(window.meetingTranslations.network.reconnect_failed, 'error');
                }
                return;
            }

            reconnectAttempts++;

            try {
                // Refresh CSRF token first
                await refreshCSRFToken();
                
                // Test API connectivity
                await fetchWithAuth('/api/server-time');
                
                // Update session status and attendance
                await Promise.all([
                    checkSessionStatus(),
                    updateAttendanceStatus()
                ]);

                // Try to reconnect LiveKit if room exists
                if (window.room && window.room.state === 'disconnected') {
                    
                    // Check if we have an active meeting and try to rejoin
                    const connectionStatus = document.getElementById('connectionStatus');
                    if (connectionStatus) {
                        connectionStatus.style.display = 'block';
                        const connectionText = document.getElementById('connectionText');
                        if (connectionText) {
                            connectionText.textContent = window.meetingTranslations.network.reconnecting_session;
                        }
                    }

                    // Trigger rejoin process
                    const startMeetingBtn = document.getElementById('startMeetingBtn');
                    if (startMeetingBtn && !startMeetingBtn.disabled) {
                        // Auto-rejoin if the meeting is still active
                        setTimeout(() => {
                            if (window.room && window.room.state === 'disconnected') {
                                startMeetingBtn.click();
                            }
                        }, 2000);
                    }
                }

                // CRITICAL FIX: Hide loading overlay after successful reconnection
                const loadingOverlay = document.getElementById('loadingOverlay');
                if (loadingOverlay && loadingOverlay.style.display !== 'none') {
                    loadingOverlay.classList.add('fade-out');
                    setTimeout(() => {
                        loadingOverlay.style.display = 'none';
                        loadingOverlay.classList.remove('fade-out');
                    }, 500);
                }

                showNetworkStatus(window.meetingTranslations.network.connected, 'online');
                reconnectAttempts = 0; // Reset on successful reconnection
                

            } catch (error) {
                
                if (reconnectAttempts < maxReconnectAttempts) {
                    // Exponential backoff
                    const delay = Math.min(1000 * Math.pow(2, reconnectAttempts), 10000);
                    setTimeout(attemptReconnection, delay);
                } else {
                    showNetworkStatus(window.meetingTranslations.network.reconnect_error, 'error');
                }
            }
        }

        function showNetworkStatus(message, status) {
            // Create or update network status indicator
            let networkIndicator = document.getElementById('networkIndicator');
            
            if (!networkIndicator) {
                networkIndicator = document.createElement('div');
                networkIndicator.id = 'networkIndicator';
                networkIndicator.className = 'fixed top-4 right-4 z-50 px-4 py-2 rounded-lg text-sm font-medium transition-all duration-300';
                document.body.appendChild(networkIndicator);
            }

            networkIndicator.textContent = message;
            
            // Update styling based on status
            networkIndicator.className = 'fixed top-4 right-4 z-50 px-4 py-2 rounded-lg text-sm font-medium transition-all duration-300';
            
            switch(status) {
                case 'online':
                    networkIndicator.classList.add('bg-green-500', 'text-white');
                    setTimeout(() => {
                        networkIndicator.style.opacity = '0';
                        setTimeout(() => networkIndicator.remove(), 300);
                    }, 3000);
                    break;
                case 'offline':
                    networkIndicator.classList.add('bg-red-500', 'text-white');
                    break;
                case 'reconnecting':
                    networkIndicator.classList.add('bg-yellow-500', 'text-white');
                    break;
                case 'error':
                    networkIndicator.classList.add('bg-red-600', 'text-white');
                    break;
            }
            
            networkIndicator.style.opacity = '1';
        }
    }

    /**
     * Parse connection error and return a user-friendly message
     * @param {string} errorMessage - The raw error message
     * @returns {string} User-friendly error message
     */
    window.parseConnectionError = function(errorMessage) {
        const translations = window.meetingTranslations?.messages || {};

        if (!errorMessage) {
            return translations.unexpected_error || 'An unexpected error occurred';
        }

        // Try to extract JSON message from HTTP error response
        // Format: "HTTP error! status: 403 - { \"message\": \"Your email address is not verified.\" }"
        const jsonMatch = errorMessage.match(/\{[\s\S]*"message"[\s\S]*\}/);
        if (jsonMatch) {
            try {
                const parsed = JSON.parse(jsonMatch[0]);
                const apiMessage = parsed.message?.toLowerCase() || '';

                // Map known API error messages to friendly translations
                if (apiMessage.includes('email') && apiMessage.includes('not verified')) {
                    return translations.email_not_verified || 'Please verify your email address before joining the session.';
                }
                if (apiMessage.includes('not found') || apiMessage.includes('session')) {
                    return translations.session_not_found || 'Session not found';
                }
                if (apiMessage.includes('unauthorized') || apiMessage.includes('not authorized')) {
                    return translations.not_authorized || 'You are not authorized to join this session';
                }
            } catch (e) {
                // JSON parsing failed, continue with other checks
            }
        }

        // Check for HTTP status codes
        if (errorMessage.includes('status: 403')) {
            return translations.not_authorized || 'You are not authorized to join this session';
        }
        if (errorMessage.includes('status: 404')) {
            return translations.session_not_found || 'Session not found';
        }
        if (errorMessage.includes('status: 401')) {
            return translations.not_authorized || 'You are not authorized to join this session';
        }

        // Check for common error patterns
        if (errorMessage.toLowerCase().includes('email') && errorMessage.toLowerCase().includes('verified')) {
            return translations.email_not_verified || 'Please verify your email address before joining the session.';
        }

        // Default: show generic connection failed message
        return translations.connection_failed || 'Failed to connect to session';
    }

    /**
     * Show notification using unified toast system
     */
    function showNotification(message, type = 'info', duration = 5000) {
        // Use unified toast system (toast-queue.js ensures window.toast is always available)
        if (window.toast) {
            window.toast.show({ type: type, message: message, duration: duration });
        }
    }
</script>



<!-- Enhanced Smart Meeting Interface -->
<div class="session-join-container bg-white rounded-xl shadow-sm border border-gray-200 overflow-hidden">
    <!-- Session Status Header -->
    <div class="session-status-header bg-gradient-to-r from-blue-50 to-indigo-50 px-6 py-4 border-b border-gray-100" data-phase="waiting">
        <div class="flex items-center justify-between">
            <div class="flex items-center gap-3">
                <div class="status-indicator flex items-center gap-2">
                    <h2 class="text-lg font-bold text-gray-900 flex items-center gap-2">
                        <i class="ri-video-line text-blue-600"></i>
                        @if($userType === 'quran_teacher')
                        {{ __('meetings.headers.meeting_management') }}
                        @else
                        {{ __('meetings.headers.join_live_session') }}
                        @endif
                    </h2>
                </div>
            </div>
            
            <!-- Session Timer -->
            @if($session->scheduled_at)
            <div class="session-timer text-start" id="session-timer" data-phase="not_started">
                <div class="flex items-center gap-2 text-sm">
                    <span id="timer-phase" class="phase-label font-medium">{{ __('meetings.timer.waiting_session') }}</span>
                    <span class="text-gray-400">|</span>
                    <span id="time-display" class="time-display font-mono font-bold text-lg">--:--</span>
                </div>
                <div class="w-full bg-gray-200 rounded-full h-1.5 mt-2">
                    <div id="timer-progress" class="h-1.5 rounded-full transition-all duration-1000" style="width: 0%"></div>
                </div>
            </div>
            @endif
        </div>
    </div>

    <!-- Main Content Area -->
    <div class="p-6">
        <div class="flex flex-col lg:flex-row gap-6">
            <!-- Left Column: Status & Info -->
            <div class="flex-1 space-y-4">
                <!-- Main Action Area -->
                <div class="join-action-area text-center py-6">
                    <!-- Join Button -->
                    <button
                        id="startMeetingBtn"
                        class="join-button {{ $buttonClass }} text-white px-8 py-4 rounded-xl font-semibold transition-all duration-300 flex items-center gap-3 mx-auto min-w-[240px] justify-center shadow-lg transform hover:scale-105"
                        data-state="{{ $canJoinMeeting ? 'ready' : 'waiting' }}"
                        {{ $buttonDisabled ? 'disabled' : '' }}>
                        
                        @if($canJoinMeeting)
                            <i class="ri-video-on-line text-xl"></i>
                        @else
                            <i class="{{ is_object($session->status) && method_exists($session->status, 'icon') ? $session->status->icon() : 'ri-question-line' }} text-xl"></i>
                        @endif
                        <span id="meetingBtnText" class="text-lg">{{ $buttonText }}</span>
                    </button>

                    <!-- Status Message -->
                    <div class="status-message mt-4 bg-gray-50 rounded-lg p-3">
                        <p class="text-gray-700 text-sm font-medium">{{ $meetingMessage }}</p>
                    </div>
                </div>

                <!-- Session Info Grid -->
                <div class="session-info bg-gray-50 rounded-lg p-4">
                    <h3 class="text-sm font-semibold text-gray-900 mb-3 flex items-center gap-2">
                        <i class="ri-information-line text-blue-600"></i>
                        {{ __('meetings.info.session_info') }}
                    </h3>
                    <div class="grid grid-cols-2 gap-4 text-sm">
                        <div class="info-item flex justify-between">
                            <span class="label text-gray-600">{{ __('meetings.info.session_time') }}</span>
                            <span class="value font-medium text-gray-900">{{ $session->scheduled_at ? formatTimeArabic($session->scheduled_at) : __('meetings.info.not_specified') }}</span>
                        </div>
                        <div class="info-item flex justify-between">
                            <span class="label text-gray-600">{{ __('meetings.info.duration') }}</span>
                            <span class="value font-medium text-gray-900">{{ $session->duration_minutes ?? 30 }} {{ __('meetings.info.minute') }}</span>
                        </div>
                        @if($circle)
                        <div class="info-item flex justify-between">
                            <span class="label text-gray-600">{{ __('meetings.info.preparation_period') }}</span>
                            <span class="value font-medium text-gray-900">{{ $preparationMinutes }} {{ __('meetings.info.minute') }}</span>
                        </div>
                        <div class="info-item flex justify-between">
                            <span class="label text-gray-600">{{ __('meetings.info.buffer_time') }}</span>
                            <span class="value font-medium text-gray-900">{{ $endingBufferMinutes }} {{ __('meetings.info.minute') }}</span>
                        </div>
                        @endif
                    </div>

                    @if($session->meeting_room_name)
                    <div class="mt-3 pt-3 border-t border-gray-200">
                        <div class="flex justify-between items-center text-sm">
                            <span class="text-gray-600">{{ __('meetings.info.room_number') }}</span>
                            <code class="bg-white px-2 py-1 rounded text-xs font-mono border">{{ $session->meeting_room_name }}</code>
                        </div>
                    </div>
                    @endif
                </div>
            </div>

            <!-- Right Column: Controls & Status -->
            <div class="lg:w-80 space-y-4">
                <!-- Attendance Status (Only for students) -->
                @if($userType === 'student')
                @livewire('student.attendance-status', [
                    'sessionId' => $session->id,
                    'sessionType' => $sessionTypeForApi,
                ])
                @endif

                <!-- System Status -->
                <x-meetings.system-status :userType="$userType" />

            </div>
        </div>
    </div>
</div>

@if($userType === 'quran_teacher')
<!-- Session Status Management Section -->
<div class="mt-6 pt-6 border-t border-gray-200">
    <h3 class="text-lg font-semibold text-gray-900 mb-4">{{ __('meetings.management.session_management') }}</h3>
    
    <div class="flex flex-wrap gap-3">
        @switch($session->status instanceof \BackedEnum ? $session->status->value : $session->status)
            @case('scheduled')
            @case('ready')
            @case('ongoing')
                @if($session->session_type === 'group')
                    <!-- Group Session: Mark as Canceled -->
                    <button id="cancelSessionBtn"
                            class="bg-red-600 hover:bg-red-700 text-white px-4 py-2 rounded-lg font-medium transition-colors duration-200 flex items-center gap-2"
                            onclick="cancelSession('{{ $session->id }}')">
                        <i class="ri-close-circle-line"></i>
                        {{ __('meetings.management.cancel_session_teacher') }}
                    </button>
                @elseif($session->session_type === 'individual')
                    <!-- Individual Session: Multiple options -->
                    <button id="cancelSessionBtn"
                            class="bg-red-600 hover:bg-red-700 text-white px-4 py-2 rounded-lg font-medium transition-colors duration-200 flex items-center gap-2"
                            onclick="cancelSession('{{ $session->id }}')">
                        <i class="ri-close-circle-line"></i>
                        {{ __('meetings.management.cancel_session') }}
                    </button>

                @endif

                <!-- Complete Session Button (for both types if session is ongoing) -->
                @if((is_object($session->status) && method_exists($session->status, 'value') ? $session->status->value : $session->status) === 'ongoing')
                <button id="completeSessionBtn"
                        class="bg-green-600 hover:bg-green-700 text-white px-4 py-2 rounded-lg font-medium transition-colors duration-200 flex items-center gap-2"
                        onclick="completeSession('{{ $session->id }}')">
                    <i class="ri-check-circle-line"></i>
                    {{ __('meetings.management.end_session') }}
                </button>
                @endif
                @break

            @case('completed')
                <!-- No actions needed for completed sessions -->
                <div class="text-green-600 flex items-center gap-2">
                    <i class="ri-check-circle-fill text-lg"></i>
                    <span class="font-medium">{{ __('meetings.management.session_ended_success') }}</span>
                </div>
                @break

            @case('cancelled')
                <!-- No actions needed for cancelled sessions -->
                <div class="text-red-600 flex items-center gap-2">
                    <i class="ri-close-circle-fill text-lg"></i>
                    <span class="font-medium">{{ __('meetings.management.session_cancelled') }}</span>
                </div>
                @break

            @case('absent')
                <!-- No actions needed for absent sessions -->
                <div class="text-gray-600 flex items-center gap-2">
                    <i class="ri-user-unfollow-fill text-lg"></i>
                    <span class="font-medium">{{ __('meetings.management.student_marked_absent') }}</span>
                </div>
                @break

            @default
                <!-- Unknown status -->
                <div class="text-gray-500 flex items-center gap-2">
                    <i class="ri-question-line text-lg"></i>
                    <span class="font-medium">{{ __('meetings.status.unknown_status') }} {{ is_object($session->status) && method_exists($session->status, 'label') ? $session->status->label() : $session->status }}</span>
                </div>
        @endswitch
    </div>
</div>

<script>
// Session status management functions
function cancelSession(sessionId) {
    if (!confirm(window.meetingTranslations.confirm.cancel_session)) {
        return;
    }

    fetch(`/teacher/sessions/${sessionId}/cancel`, {
        method: 'PUT',
        headers: {
            'Content-Type': 'application/json',
            'X-CSRF-TOKEN': '{{ csrf_token() }}'
        }
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            showNotification(window.meetingTranslations.messages.session_cancelled_success, 'success');
            setTimeout(() => window.location.reload(), 2000);
        } else {
            showNotification(window.meetingTranslations.messages.cancel_failed + ' ' + (data.message || window.meetingTranslations.messages.unknown_error), 'error');
        }
    })
    .catch(error => {
        showNotification(window.meetingTranslations.messages.cancel_error, 'error');
    });
}


function completeSession(sessionId) {
    if (!confirm(window.meetingTranslations.confirm.end_session)) {
        return;
    }

    fetch(`/teacher/sessions/${sessionId}/complete`, {
        method: 'PUT',
        headers: {
            'Content-Type': 'application/json',
            'X-CSRF-TOKEN': '{{ csrf_token() }}'
        }
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            showNotification(window.meetingTranslations.messages.session_ended_success, 'success');
            setTimeout(() => window.location.reload(), 2000);
        } else {
            showNotification(window.meetingTranslations.messages.end_failed + ' ' + (data.message || window.meetingTranslations.messages.unknown_error), 'error');
        }
    })
    .catch(error => {
        showNotification(window.meetingTranslations.messages.end_error, 'error');
    });
}
</script>
@endif

<!-- Meeting Container -->
<div id="meetingContainer" class="bg-white rounded-lg shadow-md overflow-hidden mt-8" style="display: none;">
    <!-- LiveKit Meeting Interface - Dynamic Height -->
    <div id="livekitMeetingInterface" class="bg-gray-900 relative" style="min-height: 400px;">
        <!-- Loading Overlay - ENHANCED WITH SMOOTH TRANSITIONS -->
        <div id="loadingOverlay" class="absolute inset-0 bg-black bg-opacity-75 flex items-center justify-center z-22">
            <div class="text-center text-white">
                <div class="animate-spin rounded-full h-16 w-16 border-b-2 border-blue-500 mx-auto mb-4"></div>
                <p class="text-xl font-medium">{{ __('meetings.loading.connecting_meeting') }}</p>
                <p class="text-sm text-gray-300 mt-2">{{ __('meetings.loading.please_wait') }}</p>
            </div>
        </div>

        <!-- Meeting Interface - ENHANCED WITH SMOOTH FADE-IN -->
        <div id="meetingInterface" class="h-full flex flex-col bg-gray-900 text-white" style="min-height: 700px;">
            <!-- Meeting Header - With fullscreen button -->
            <div class="bg-gradient-to-r from-blue-500 via-blue-600 to-blue-700 text-white px-4 py-3 flex items-center justify-between text-sm font-medium shadow-lg">
                <!-- Left side - Meeting info -->
                <div class="flex items-center gap-4 sm:gap-8">
                    <!-- Participant Count -->
                    <div class="flex items-center gap-2 text-white">
                        <i class="ri-group-line text-lg text-white"></i>
                        <span id="participantCount" class="text-white font-semibold">0</span>
                        <span class="text-white">{{ __('meetings.info.participant') }}</span>
                    </div>

                    <!-- Meeting Timer -->
                    <div class="flex items-center gap-2 text-white font-mono">
                        <div id="meetingTimerDot" class="w-2 h-2 bg-orange-400 rounded-full animate-pulse"></div>
                        <span id="meetingTimer" class="text-white font-bold">00:00</span>
                    </div>
                </div>

                <!-- Right side - Fullscreen button -->
                <button id="fullscreenBtn" aria-label="{{ __('meetings.info.fullscreen') }}" class="bg-black bg-opacity-20 hover:bg-opacity-30 text-white px-3 py-2 rounded-lg transition-all duration-200 flex items-center gap-2 text-sm font-medium hover:scale-105 focus:outline-none focus:ring-2 focus:ring-white focus:ring-opacity-50 z-1 relative">
                    <i id="fullscreenIcon" class="ri-fullscreen-line text-lg text-white" aria-hidden="true"></i>
                    <span id="fullscreenText" class="hidden sm:inline">{{ __('meetings.info.fullscreen') }}</span>
                </button>
            </div>

            <!-- Main Content Area with Sidebar -->
            <div class="flex-1 grid grid-cols-1 min-h-0 overflow-hidden relative" style="overflow: hidden;">
                <!-- Video Area -->
                <div id="videoArea" class="video-area bg-gray-900 relative">

                    <!-- Video Grid -->
                    <div id="videoGrid" class="video-grid grid-1">
                        <!-- Participants will be added here dynamically -->
                    </div>

                    <!-- Focus Mode Overlay -->
                    <div id="focusOverlay" class="focus-overlay hidden">                        
                        <!-- Focused Video Container -->
                        <div id="focusedVideoContainer" class="focused-video-container">
                            <!-- Focused video will be moved here -->
                        </div>
                    </div>
                </div>
            </div>

            <!-- Sidebar moved outside overflow-hidden container to prevent clipping -->
            <x-meetings.sidebar-panels :userType="$userType" />

            @php
                // Only show recording for Interactive Course sessions (Academic teachers only)
                $isInteractiveCourse = ($session->session_type === 'interactive_course' ||
                                      (isset($session->interactiveCourseSession) && $session->interactiveCourseSession) ||
                                      (method_exists($session, 'session_type') && $session->session_type === 'interactive_course'));
                $showRecording = $userType === 'academic_teacher' && $isInteractiveCourse;
            @endphp
            <x-meetings.control-bar :userType="$userType" :showRecording="$showRecording" />
        </div>
    </div>
</div>

<!-- Meeting Initialization Script -->
<script>

    // Initialize modular meeting system
    async function initializeMeeting() {

        try {
            // Wait for LiveKit SDK to load
            if (window.livekitLoadPromise) {
                await window.livekitLoadPromise;
            }

            // Double-check LiveKit is available
            if (typeof LiveKit === 'undefined' && typeof window.LiveKit === 'undefined') {
                throw new Error('LiveKit SDK not available after loading');
            }

            // Meeting configuration for modular system
            const meetingConfig = {
                serverUrl: '{{ config("livekit.server_url") }}',
                csrfToken: '{{ csrf_token() }}',
                roomName: {!! json_encode($session->meeting_room_name ?? 'session-' . $session->id) !!},
                participantName: {!! json_encode(trim(auth()->user()->first_name . ' ' . auth()->user()->last_name)) !!},
                role: '{{ $userType === "quran_teacher" ? "teacher" : "student" }}',
                // Avatar data for local participant
                avatarUrl: {!! json_encode($currentUserAvatarUrl) !!},
                defaultAvatarUrl: '{{ $currentUserDefaultAvatarUrl }}',
                userType: '{{ $currentUserType }}',
                gender: '{{ $currentUserGender }}'
            };


            // Set up start button handler
            const startBtn = document.getElementById('startMeetingBtn');
            if (startBtn) {

                // Add click handler for modular system
                startBtn.addEventListener('click', async () => {

                    // CRITICAL FIX: Check if user is already in the meeting
                    if (window.meeting || startBtn.disabled) {
                        return;
                    }

                    // CRITICAL FIX: Check if already tracking attendance (user is in meeting)
                    if (attendanceTracker && attendanceTracker.isTracking) {
                        return;
                    }

                    try {
                        // Show loading state
                        startBtn.disabled = true;
                        const btnText = document.getElementById('meetingBtnText');
                        const originalText = btnText?.textContent;
                        
                        if (btnText) {
                            btnText.textContent = window.meetingTranslations.buttons.connecting;
                        }

                        // Show meeting container and scroll to it
                        const meetingContainer = document.getElementById('meetingContainer');
                        if (meetingContainer) {
                            meetingContainer.style.display = 'block';
                            meetingContainer.scrollIntoView({ behavior: 'smooth', block: 'start' });
                        } else {
                        }

                        // Initialize meeting with new modular system
                        window.meeting = await initializeLiveKitMeeting(meetingConfig);


                        // CRITICAL FIX: Immediately record join when meeting starts
                        if (attendanceTracker) {
                            setTimeout(() => {
                                attendanceTracker.recordJoin();
                            }, 1000);
                        }

                        // Update button text
                        if (btnText) btnText.textContent = window.meetingTranslations.buttons.connected;

                    } catch (error) {

                        // Reset button state
                        startBtn.disabled = false;
                        const btnText = document.getElementById('meetingBtnText');
                        if (btnText) {
                            btnText.textContent = window.meetingTranslations.buttons.retry;
                        }

                        // Hide meeting container on error
                        const meetingContainer = document.getElementById('meetingContainer');
                        if (meetingContainer) {
                            meetingContainer.style.display = 'none';
                        }

                        // Show user-friendly error
                        const friendlyError = window.parseConnectionError(error?.message);
                        window.toast?.error(friendlyError);
                    }
                });

            } else {
            }


        } catch (error) {
            const btn = document.getElementById('startMeetingBtn');
            const btnText = document.getElementById('meetingBtnText');
            if (btn) btn.disabled = true;

            const errorMessage = error?.message || error?.toString() || 'Unknown error';
            if (btnText) {
                btnText.textContent = errorMessage.toLowerCase().includes('livekit') ? window.meetingTranslations.buttons.livekit_unavailable : window.meetingTranslations.buttons.init_error;
            }
        }
    }

    // Wait for window load, then initialize
    window.addEventListener('load', function() {
        initializeMeeting();
    });

    // Fallback initialization on DOM ready
    document.addEventListener('DOMContentLoaded', function() {

        // Ensure initializeLiveKitMeeting is available
        if (typeof window.initializeLiveKitMeeting !== 'function') {
            return;
        }

    });

    // Cleanup on page unload
    window.addEventListener('beforeunload', async () => {
        // Stop session status polling (prevents memory leak)
        stopSessionStatusPolling();

        if (window.meeting && typeof window.meeting.destroy === 'function') {
            try {
                await window.meeting.destroy();
            } catch (error) {
            }
        } else if (window.destroyCurrentMeeting) {
            // Fallback cleanup
            try {
                await window.destroyCurrentMeeting();
            } catch (error) {
            }
        }
    });





</script>

<!-- Auto-join functionality removed - meetings now require manual start -->

<!-- Meeting Timer System -->
<script>
document.addEventListener('DOMContentLoaded', function() {
    // =====================================================
    // Session Starting Soon Notification (Uses Unified Toast)
    // Shows toast notification when session is starting soon
    // =====================================================
    @if($session->scheduled_at && $session->scheduled_at->isFuture() && $session->scheduled_at->diffInMinutes(now()) <= 15)
        @php
            $timeData = formatTimeRemaining($session->scheduled_at);
        @endphp
        @if(!$timeData['is_past'])
            // Use unified toast system for session starting notification
            if (window.toast) {
                window.toast.info(window.meetingTranslations.timer.starting_soon.replace(':time', '{{ $timeData['formatted'] }}'), { duration: 8000 });
            }
        @endif
    @endif

    // Meeting header timer is now synced from SmartSessionTimer (no duplicate timer class needed)
});
</script>

<!-- Auto-Attendance Tracking System -->
<script>
    // Auto-Attendance Tracking System
    class AutoAttendanceTracker {
        constructor() {
            this.sessionId = {{ $session->id }};
            this.roomName = {!! json_encode($session->meeting_room_name ?? 'session-' . $session->id) !!};
            this.csrfToken = '{{ csrf_token() }}';
            this.isTracking = false;
            this.attendanceStatus = null;
        }
        
        /**
         * Load current attendance status
         * DISABLED: Attendance now handled by Livewire component via webhooks
         */
        async loadCurrentStatus() {
            return; // DISABLED - Livewire component handles this now
        }
        
        /**
         * Record user joining the meeting
         */
        async recordJoin() {
            if (this.isTracking) {
                return;
            }
            
            try {
                // DISABLED: Client-side attendance tracking - Now handled by LiveKit webhooks

                // Simulate successful response for UI update
                const data = {
                    success: true,
                    message: window.meetingTranslations.attendance.attendance_tracked,
                    attendance_status: {}
                };
                
                if (data.success) {
                    this.isTracking = true;
                    
                    if (data.attendance_status) {
                        this.updateAttendanceUI(data.attendance_status);
                    }
                    
                    this.showNotification('✅ ' + data.message, 'success');
                    
                    // CRITICAL FIX: Start periodic updates only when meeting join is successful
                    if (!this.updateInterval) {
                        this.startPeriodicUpdates();
                    }
                    
                    // Immediately refresh attendance status
                    setTimeout(() => {
                        this.loadCurrentStatus();
                    }, 500);
                    
                } else {
                    this.showNotification('⚠️ ' + (data.message || window.meetingTranslations.attendance.attendance_failed), 'warning');
                }

            } catch (error) {
                this.showNotification('❌ ' + window.meetingTranslations.attendance.join_failed, 'error');
            }
        }
        
        /**
         * Record user leaving the meeting
         */
        async recordLeave() {
            if (!this.isTracking) return; // Only record leave if we recorded join
            
            try {
                
                const response = await fetch('/api/meetings/attendance/leave', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': this.csrfToken,
                        'X-Requested-With': 'XMLHttpRequest',
                    },
                    credentials: 'same-origin',
                    body: JSON.stringify({
                        session_id: this.sessionId,
                        session_type: window.sessionType || 'quran',
                        room_name: this.roomName,
                    }),
                });
                
                const data = await response.json();
                
                if (data.success) {
                    this.isTracking = false;
                    this.updateAttendanceUI(data.attendance_status);
                    this.showNotification('✅ ' + data.message, 'success');
                    
                    // CRITICAL FIX: Stop periodic updates when user leaves
                    this.stopPeriodicUpdates();
                    
                } else {
                    this.showNotification('⚠️ ' + data.message, 'warning');
                }
                
            } catch (error) {
                this.showNotification('❌ ' + window.meetingTranslations.attendance.leave_failed, 'error');
            }
        }
        
        /**
         * Update attendance UI - delegates to Livewire component
         */
        updateAttendanceUI(statusData) {
            // Dispatch event to Livewire component to refresh its state
            if (window.Livewire) {
                Livewire.dispatch('attendance-updated');
            }
        }
        
        /**
         * Start periodic updates for real-time attendance tracking
         */
        startPeriodicUpdates() {
            // Update every 30 seconds for real-time tracking
            this.updateInterval = setInterval(() => {
                this.loadCurrentStatus();
            }, 30000);
        }
        
        /**
         * Stop periodic updates
         */
        stopPeriodicUpdates() {
            if (this.updateInterval) {
                clearInterval(this.updateInterval);
                this.updateInterval = null;
            }
        }
        
        /**
         * Show notification to user using unified toast system
         */
        showNotification(message, type = 'info') {
            if (window.toast) {
                window.toast.show({ type: type, message: message });
            } else {
            }
        }
        
        /**
         * Hook into meeting events
         */
        hookIntoMeetingEvents(meeting) {
            
            if (!meeting) {
                return;
            }
            
            // Try to get room from different possible paths
            let room = null;
            if (meeting.connection && typeof meeting.connection.getRoom === 'function') {
                room = meeting.connection.getRoom();
            } else if (meeting.room) {
                room = meeting.room;
            } else if (meeting.connection && meeting.connection.room) {
                room = meeting.connection.room;
            }
            
            if (!room) {
                // Fallback: try to record join immediately since user clicked to join
                setTimeout(() => {
                    this.recordJoin();
                }, 2000);
                return;
            }
            
            
            // Check if already connected
            if (room.state === 'connected') {
                this.recordJoin();
            }
            
            // Listen for local participant connection
            room.on('connected', () => {
                this.recordJoin();
            });
            
            // Listen for local participant disconnection
            room.on('disconnected', () => {
                this.recordLeave();
            });
            
            // Listen for connection state changes
            room.on('connectionStateChanged', (state) => {
                
                if (state === 'connected') {
                    this.recordJoin();
                } else if (state === 'disconnected' || state === 'failed') {
                    this.recordLeave();
                }
            });
            
        }
    }
    
    // Recording functionality for Interactive Courses only
    let recordingState = {
        isRecording: false,
        recordingId: null,
        startTime: null,
        sessionId: {{ $session->id ?? 'null' }}
    };
    
    function initializeRecordingControls() {
        
        const recordingBtn = document.getElementById('toggleRecording');
        const recordingIcon = document.getElementById('recordingIcon');
        const recordingIndicator = document.getElementById('recordingIndicator');
        
        if (recordingBtn) {
            recordingBtn.addEventListener('click', toggleRecording);
        }
    }
    
    async function toggleRecording() {
        const recordingBtn = document.getElementById('toggleRecording');
        const recordingIcon = document.getElementById('recordingIcon');
        const recordingIndicator = document.getElementById('recordingIndicator');

        // Safety check - return if elements don't exist
        if (!recordingBtn || !recordingIcon || !recordingIndicator) {
            return;
        }

        try {
            if (recordingState.isRecording) {
                // Stop recording
                await stopRecording();

                // Update UI
                recordingIcon.className = 'ri-record-circle-line text-xl';
                recordingIndicator.classList.add('hidden');
                recordingBtn.classList.remove('bg-red-600');
                recordingBtn.classList.add('bg-gray-600');
                recordingBtn.title = window.meetingTranslations.recording.start_recording;

                showRecordingNotification('✅ ' + window.meetingTranslations.recording.recording_stopped, 'success');

            } else {
                // Start recording
                await startRecording();

                // Update UI
                recordingIcon.className = 'ri-stop-circle-line text-xl';
                recordingIndicator.classList.remove('hidden');
                recordingBtn.classList.remove('bg-gray-600');
                recordingBtn.classList.add('bg-red-600');
                recordingBtn.title = window.meetingTranslations.recording.stop_recording;

                showRecordingNotification('🎥 ' + window.meetingTranslations.recording.recording_started, 'success');
            }
        } catch (error) {
            showRecordingNotification('❌ ' + window.meetingTranslations.recording.recording_error + ' ' + error.message, 'error');
        }
    }
    
    async function startRecording() {
        const response = await fetch('/api/interactive-courses/recording/start', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.getAttribute('content')
            },
            body: JSON.stringify({
                session_id: recordingState.sessionId,
                meeting_room: window.meeting?.roomName || 'unknown_room'
            })
        });
        
        if (!response.ok) {
            throw new Error(window.meetingTranslations.recording.start_failed);
        }
        
        const data = await response.json();
        recordingState.isRecording = true;
        recordingState.recordingId = data.recording_id;
        recordingState.startTime = new Date();
        
    }
    
    async function stopRecording() {
        if (!recordingState.recordingId) {
            throw new Error(window.meetingTranslations.recording.no_active_recording);
        }
        
        const response = await fetch('/api/interactive-courses/recording/stop', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.getAttribute('content')
            },
            body: JSON.stringify({
                recording_id: recordingState.recordingId,
                session_id: recordingState.sessionId
            })
        });
        
        if (!response.ok) {
            throw new Error(window.meetingTranslations.recording.stop_failed);
        }
        
        const data = await response.json();
        recordingState.isRecording = false;
        recordingState.recordingId = null;
        recordingState.startTime = null;
        
    }
    
    function showRecordingNotification(message, type = 'info') {
        // Use unified toast system
        if (window.toast) {
            window.toast.show({ type: type, message: message, duration: 4000 });
        } else {
        }
    }
    
    // Initialize attendance tracker
    let attendanceTracker = null;
    document.addEventListener('DOMContentLoaded', () => {
        attendanceTracker = new AutoAttendanceTracker();
        // Make globally accessible for debugging
        window.attendanceTracker = attendanceTracker;
        
        // Initialize recording functionality (Interactive Courses only)
        @if($showRecording ?? false)
        initializeRecordingControls();
        @endif
        
        // CRITICAL FIX: Load initial status for students (especially for completed sessions)
        @if($userType === 'student')
            // Wait a moment for DOM to be fully ready, then load status
            setTimeout(() => {
                if (attendanceTracker) {
                    attendanceTracker.loadCurrentStatus();
                }
            }, 500);
        @endif
        
        // Hook into meeting events when meeting starts
        const originalButton = document.getElementById('startMeetingBtn');
        if (originalButton) {
            const originalOnClick = originalButton.onclick;
            originalButton.addEventListener('click', async function(e) {
                // Wait a bit for the meeting to initialize
                setTimeout(() => {
                    if (window.meeting && attendanceTracker) {
                        attendanceTracker.hookIntoMeetingEvents(window.meeting);
                    }
                }, 3000);
            });
        }
    });
    
    // Cleanup attendance tracking on page unload
    window.addEventListener('beforeunload', () => {
        if (attendanceTracker) {
            // Stop periodic updates
            attendanceTracker.stopPeriodicUpdates();
            
            if (attendanceTracker.isTracking) {
                // Send leave event synchronously (best effort)
                navigator.sendBeacon('/api/meetings/attendance/leave', JSON.stringify({
                    session_id: attendanceTracker.sessionId,
                    room_name: attendanceTracker.roomName,
                }));
            }
        }
    });
</script>

<!-- System Status Checker -->
<script>
document.addEventListener('DOMContentLoaded', function() {
    // System Status Checker Class
    class SystemStatusChecker {
        constructor() {
            this.init();
        }

        init() {
            this.checkCameraPermission();
            this.checkMicrophonePermission();
            this.checkNetworkStatus();
            this.checkBrowserCompatibility();
            this.setupEventListeners();
        }

        async checkCameraPermission() {
            try {
                const result = await navigator.permissions.query({ name: 'camera' });
                this.updatePermissionStatus('camera', result.state);
                
                result.addEventListener('change', () => {
                    this.updatePermissionStatus('camera', result.state);
                });
            } catch (error) {
                // Fallback: try to access camera directly
                try {
                    const stream = await navigator.mediaDevices.getUserMedia({ video: true });
                    this.updatePermissionStatus('camera', 'granted');
                    stream.getTracks().forEach(track => track.stop());
                } catch (err) {
                    this.updatePermissionStatus('camera', 'denied');
                }
            }
        }

        async checkMicrophonePermission() {
            try {
                const result = await navigator.permissions.query({ name: 'microphone' });
                this.updatePermissionStatus('mic', result.state);
                
                result.addEventListener('change', () => {
                    this.updatePermissionStatus('mic', result.state);
                });
            } catch (error) {
                // Fallback: try to access microphone directly
                try {
                    const stream = await navigator.mediaDevices.getUserMedia({ audio: true });
                    this.updatePermissionStatus('mic', 'granted');
                    stream.getTracks().forEach(track => track.stop());
                } catch (err) {
                    this.updatePermissionStatus('mic', 'denied');
                }
            }
        }

        updatePermissionStatus(type, state) {
            const icon = document.getElementById(`${type}-status-icon`);
            const text = document.getElementById(`${type}-status-text`);
            const button = document.getElementById(`${type}-permission-btn`);

            if (!icon || !text) return;

            // Remove existing classes
            icon.className = 'w-8 h-8 rounded-full flex items-center justify-center';
            text.className = 'text-xs';

            switch (state) {
                case 'granted':
                    icon.classList.add('bg-green-100');
                    icon.innerHTML = '<i class="ri-check-line text-green-600"></i>';
                    text.classList.add('text-green-600');
                    text.textContent = window.meetingTranslations.system.allowed;
                    if (button) button.classList.add('hidden');
                    break;
                case 'denied':
                    icon.classList.add('bg-red-100');
                    icon.innerHTML = '<i class="ri-close-line text-red-600"></i>';
                    text.classList.add('text-red-600');
                    text.textContent = window.meetingTranslations.system.denied;
                    if (button) button.classList.remove('hidden');
                    break;
                case 'prompt':
                    icon.classList.add('bg-yellow-100');
                    icon.innerHTML = '<i class="ri-question-line text-yellow-600"></i>';
                    text.classList.add('text-yellow-600');
                    text.textContent = window.meetingTranslations.system.needs_permission;
                    if (button) button.classList.remove('hidden');
                    break;
                default:
                    icon.classList.add('bg-gray-100');
                    icon.innerHTML = `<i class="ri-${type === 'camera' ? 'camera' : 'mic'}-line text-gray-400"></i>`;
                    text.classList.add('text-gray-600');
                    text.textContent = window.meetingTranslations.system.unknown;
                    if (button) button.classList.add('hidden');
            }
        }

        checkNetworkStatus() {
            const icon = document.getElementById('network-status-icon');
            const text = document.getElementById('network-status-text');
            const speed = document.getElementById('network-speed');

            if (!icon || !text) return;

            const updateNetworkStatus = () => {
                if (navigator.onLine) {
                    icon.className = 'w-8 h-8 rounded-full flex items-center justify-center bg-green-100';
                    icon.innerHTML = '<i class="ri-wifi-line text-green-600"></i>';
                    text.className = 'text-xs text-green-600';
                    text.textContent = window.meetingTranslations.system.connected;

                    // Check connection speed if available
                    if (navigator.connection) {
                        const connection = navigator.connection;
                        const speedText = connection.effectiveType || connection.type || window.meetingTranslations.system.unknown;
                        if (speed) speed.textContent = speedText;
                    }
                } else {
                    icon.className = 'w-8 h-8 rounded-full flex items-center justify-center bg-red-100';
                    icon.innerHTML = '<i class="ri-wifi-off-line text-red-600"></i>';
                    text.className = 'text-xs text-red-600';
                    text.textContent = window.meetingTranslations.system.not_connected;
                    if (speed) speed.textContent = '';
                }
            };

            // Initial check
            updateNetworkStatus();

            // Listen for network changes
            window.addEventListener('online', updateNetworkStatus);
            window.addEventListener('offline', updateNetworkStatus);

            // Check connection speed changes
            if (navigator.connection) {
                navigator.connection.addEventListener('change', updateNetworkStatus);
            }
        }

        checkBrowserCompatibility() {
            const icon = document.getElementById('browser-status-icon');
            const text = document.getElementById('browser-status-text');

            if (!icon || !text) return;

            // Check for required APIs
            const hasMediaDevices = !!navigator.mediaDevices;
            const hasGetUserMedia = hasMediaDevices && !!navigator.mediaDevices.getUserMedia;
            const hasWebRTC = !!(window.RTCPeerConnection || window.webkitRTCPeerConnection);
            const hasPermissions = !!navigator.permissions;

            const isCompatible = hasMediaDevices && hasGetUserMedia && hasWebRTC;

            if (isCompatible) {
                icon.className = 'w-8 h-8 rounded-full flex items-center justify-center bg-green-100';
                icon.innerHTML = '<i class="ri-check-line text-green-600"></i>';
                text.className = 'text-xs text-green-600';
                text.textContent = window.meetingTranslations.system.compatible;
            } else {
                icon.className = 'w-8 h-8 rounded-full flex items-center justify-center bg-red-100';
                icon.innerHTML = '<i class="ri-error-warning-line text-red-600"></i>';
                text.className = 'text-xs text-red-600';
                text.textContent = window.meetingTranslations.system.not_compatible;
            }
        }

        setupEventListeners() {
            // Camera permission button
            const cameraBtn = document.getElementById('camera-permission-btn');
            if (cameraBtn) {
                cameraBtn.addEventListener('click', async () => {
                    try {
                        const stream = await navigator.mediaDevices.getUserMedia({ video: true });
                        this.updatePermissionStatus('camera', 'granted');
                        stream.getTracks().forEach(track => track.stop());
                    } catch (error) {
                        this.updatePermissionStatus('camera', 'denied');
                    }
                });
            }

            // Microphone permission button
            const micBtn = document.getElementById('mic-permission-btn');
            if (micBtn) {
                micBtn.addEventListener('click', async () => {
                    try {
                        const stream = await navigator.mediaDevices.getUserMedia({ audio: true });
                        this.updatePermissionStatus('mic', 'granted');
                        stream.getTracks().forEach(track => track.stop());
                    } catch (error) {
                        this.updatePermissionStatus('mic', 'denied');
                    }
                });
            }
        }
    }

    // Initialize system status checker
    const systemStatusChecker = new SystemStatusChecker();
    window.systemStatusChecker = systemStatusChecker; // Make globally accessible
});
</script>