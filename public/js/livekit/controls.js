/**
 * LiveKit Controls Module
 * Handles UI control interactions (mic, camera, screen share, etc.) using proper SDK methods
 * VERSION: 2025-11-16-FIX-v6 - Fixed clearAllRaisedHands room reference & added debugging
 */


/**
 * Controls manager for meeting UI interactions
 */
class LiveKitControls {
    /**
     * Create a new controls manager
     * @param {Object} config - Configuration object
     * @param {LiveKit.Room} config.room - LiveKit room instance
     * @param {LiveKit.LocalParticipant} config.localParticipant - Local participant instance
     * @param {Function} config.onControlStateChange - Callback when control state changes
     * @param {Function} config.onNotification - Callback for showing notifications
     * @param {Function} config.onLeaveRequest - Callback when leave is requested
     * @param {Object} config.meetingConfig - Meeting configuration for role-based features
     */
    constructor(config) {
        this.config = config;
        this.room = config.room;
        this.localParticipant = config.localParticipant;

        // Control states
        this.isAudioEnabled = true;
        this.isVideoEnabled = true;
        this.isScreenSharing = false;
        this.isHandRaised = false;
        this.isRecording = false;

        // UI state
        this.isChatOpen = false;
        this.isParticipantsListOpen = false;
        this.isSettingsOpen = false;
        this.currentSidebarType = null;

        // Meeting timer
        this.meetingStartTime = Date.now();
        this.timerInterval = null;

        // ===== HAND-RAISING & AUDIO MANAGEMENT STATE =====

        // Raised hands queue - maintains order of hand raises
        this.raisedHandsQueue = new Map(); // Map<participantSid, {identity, timestamp, granted}>

        // Audio permissions for students
        this.studentAudioPermissions = new Map(); // Map<participantSid, {canSpeak: boolean, isMuted: boolean}>

        // Global audio control state - CRITICAL FIX: ensure explicit initialization
        this.globalAudioControlsState = {
            allStudentsMuted: false,        // Whether teacher has muted all students (default: enabled)
            studentsCanSelfUnmute: true,    // Whether students can unmute themselves (default: true)
            teacherControlsAudio: false     // Whether teacher is controlling student audio (default: false)
        };

        // Flag to track if global state has been explicitly set by teacher
        this.globalStateExplicitlySet = false;

        // Hand-raise UI state
        this.handRaiseNotificationCount = 0; // Number of unacknowledged hand raises
        this.isHandRaiseQueueOpen = false;   // Whether raised hands sidebar is open

        // Role detection
        this.userRole = this.detectUserRole();

        console.log('MeetingControlsHandler initialized', {
            userRole: this.userRole,
            canRaiseHand: this.canRaiseHand(),
            canControlAudio: this.canControlStudentAudio()
        });

        // If teacher, request current hand raise status from all participants (with delay for room setup)
        if (this.canControlStudentAudio()) {
            setTimeout(() => {
                this.requestHandRaiseSync();
            }, 2000); // Wait 2 seconds for room to be fully connected
        }

        // Debug functions removed to prevent errors
        // Data channel handler removed to prevent errors

        this.initializeControls();

    }

    /**
     * Initialize control handlers
     */
    initializeControls() {
        this.setupControlButtons();
        this.setupKeyboardShortcuts();
        this.syncControlStates();

        // Initialize permission toggles from server (both teachers and students)
        if (this.canControlStudentAudio()) {
            // For teachers, fetch current permissions and initialize toggles
            this.initializeTeacherTogglesFromServer();
        } else {
            // For students, fetch room permissions from server
            this.fetchAndEnforceRoomPermissions();
        }

        this.updateControlButtons();
        this.startMeetingTimer();
    }

    /**
     * Sync control states with SDK
     */
    syncControlStates() {
        if (this.localParticipant) {
            this.isAudioEnabled = this.localParticipant.isMicrophoneEnabled;
            this.isVideoEnabled = this.localParticipant.isCameraEnabled;
        }
    }

    /**
     * Fetch room permissions from server and enforce them (students only)
     */
    async fetchAndEnforceRoomPermissions() {
        try {
            // Use actual LiveKit room name from connected room
            const roomName = this.room?.name || this.config?.meetingConfig?.roomName || `session-${window.sessionId}`;


            const response = await window.LiveKitAPI.get(`/livekit/rooms/permissions?room_name=${encodeURIComponent(roomName)}`);

            if (!response.ok) {
                throw new Error('Failed to fetch room permissions');
            }

            const result = await response.json();
            const permissions = result.permissions || {};

            // Store permissions locally
            this.roomPermissions = {
                microphoneAllowed: permissions.microphone_allowed !== false,
                cameraAllowed: permissions.camera_allowed !== false,
            };

            // Enforce permissions on UI
            this.enforcePermissionsOnUI();

            // Start polling for permission changes every 5 seconds
            this.startPermissionPolling();

        } catch (error) {
            // Default to allowing everything if fetch fails
            this.roomPermissions = {
                microphoneAllowed: true,
                cameraAllowed: true,
            };
        }
    }

    /**
     * Initialize teacher toggle switches from server permissions (teachers only)
     */
    async initializeTeacherTogglesFromServer() {
        try {
            const roomName = this.room?.name || this.config?.meetingConfig?.roomName || `session-${window.sessionId}`;

            const response = await window.LiveKitAPI.get(`/livekit/rooms/permissions?room_name=${encodeURIComponent(roomName)}`);

            if (!response.ok) {
                throw new Error('Failed to fetch room permissions');
            }

            const result = await response.json();
            const permissions = result.permissions || {};

            // Set toggle switches based on current permissions
            const micSwitch = document.getElementById('toggleAllStudentsMicSwitch');
            const cameraSwitch = document.getElementById('toggleAllStudentsCameraSwitch');

            if (micSwitch) {
                micSwitch.checked = permissions.microphone_allowed !== false;
            }

            if (cameraSwitch) {
                cameraSwitch.checked = permissions.camera_allowed !== false;
            }

            // Now sync the internal state from the correctly initialized toggles
            this.syncGlobalAudioStateFromToggle();
            this.updateGlobalAudioControlToggle();

        } catch (error) {
            // Default to allowing everything if fetch fails
            const micSwitch = document.getElementById('toggleAllStudentsMicSwitch');
            const cameraSwitch = document.getElementById('toggleAllStudentsCameraSwitch');

            if (micSwitch) micSwitch.checked = true;
            if (cameraSwitch) cameraSwitch.checked = true;

            this.syncGlobalAudioStateFromToggle();
            this.updateGlobalAudioControlToggle();
        }
    }

    /**
     * Enforce room permissions on UI buttons
     */
    enforcePermissionsOnUI() {
        if (!this.roomPermissions) return;

        const micButton = document.getElementById('toggleMic');
        const cameraButton = document.getElementById('toggleCamera');

        // Microphone permission
        if (!this.roomPermissions.microphoneAllowed) {

            if (micButton) {
                micButton.disabled = true;
                micButton.classList.add('opacity-50', 'cursor-not-allowed');
                micButton.title = t('permissions.mic_not_allowed_by_teacher');
            }

            // Ensure mic is muted if permission disabled
            if (this.isAudioEnabled && this.localParticipant) {
                this.toggleMicrophone(); // This will mute it
            }
        } else {
            if (micButton) {
                micButton.disabled = false;
                micButton.classList.remove('opacity-50', 'cursor-not-allowed');
                micButton.title = t('control_states.toggle_mic');
            }
        }

        // Camera permission
        if (!this.roomPermissions.cameraAllowed) {

            if (cameraButton) {
                cameraButton.disabled = true;
                cameraButton.classList.add('opacity-50', 'cursor-not-allowed');
                cameraButton.title = t('permissions.camera_not_allowed_by_teacher');
            }

            // Ensure camera is off if permission disabled
            if (this.isVideoEnabled && this.localParticipant) {
                this.toggleCamera(); // This will turn it off
            }
        } else {
            if (cameraButton) {
                cameraButton.disabled = false;
                cameraButton.classList.remove('opacity-50', 'cursor-not-allowed');
                cameraButton.title = t('control_states.toggle_camera');
            }
        }
    }

    /**
     * Poll for permission changes every 5 seconds
     */
    startPermissionPolling() {
        // Clear existing interval if any
        if (this.permissionPollingInterval) {
            clearInterval(this.permissionPollingInterval);
        }

        // Poll every 5 seconds
        this.permissionPollingInterval = setInterval(() => {
            this.fetchAndEnforceRoomPermissions();
        }, 5000);

    }

    /**
     * Set up control button event listeners
     */
    setupControlButtons() {

        // Microphone toggle
        const micButton = document.getElementById('toggleMic');
        if (micButton) {
            micButton.addEventListener('click', () => this.toggleMicrophone());
        }

        // Camera toggle
        const cameraButton = document.getElementById('toggleCamera');
        if (cameraButton) {
            cameraButton.addEventListener('click', () => this.toggleCamera());
        }

        // Screen share toggle
        const screenShareButton = document.getElementById('toggleScreenShare');
        if (screenShareButton) {
            screenShareButton.addEventListener('click', () => this.toggleScreenShare());
        }

        // Hand raise toggle
        const handRaiseButton = document.getElementById('toggleHandRaise');
        if (handRaiseButton) {
            handRaiseButton.addEventListener('click', () => {
                this.toggleHandRaise();
            });
        } else {
        }

        // Chat toggle
        const chatButton = document.getElementById('toggleChat');
        if (chatButton) {
            chatButton.addEventListener('click', () => this.toggleChat());
        }

        // Participants list toggle
        const participantsButton = document.getElementById('toggleParticipants');
        if (participantsButton) {
            participantsButton.addEventListener('click', () => this.toggleParticipantsList());
        }

        // Settings toggle
        const settingsButton = document.getElementById('toggleSettings');
        if (settingsButton) {
            settingsButton.addEventListener('click', () => this.toggleSettings());
        }

        // Close sidebar button
        const closeSidebarBtn = document.getElementById('closeSidebarBtn');
        if (closeSidebarBtn) {
            closeSidebarBtn.addEventListener('click', () => this.closeSidebar());
        }

        // Fullscreen button
        const fullscreenBtn = document.getElementById('fullscreenBtn');
        if (fullscreenBtn) {
            fullscreenBtn.addEventListener('click', () => this.toggleFullscreen());
        }

        // Leave meeting
        const leaveButton = document.getElementById('leaveMeeting');
        if (leaveButton) {
            leaveButton.addEventListener('click', () => this.showLeaveConfirmModal());
        } else {
        }

        // Recording toggle (teacher only)
        const recordButton = document.getElementById('toggleRecording');
        if (recordButton && this.isTeacher()) {
            recordButton.addEventListener('click', () => this.toggleRecording());
        }

        // Raised hands button (role-based)
        const raisedHandsButton = document.getElementById('toggleRaisedHands');
        if (raisedHandsButton && this.canControlStudentAudio()) {
            raisedHandsButton.addEventListener('click', () => this.toggleHandRaise());
        }

        // Global audio control toggle switch (teachers only)
        const toggleAllStudentsMicSwitch = document.getElementById('toggleAllStudentsMicSwitch');
        if (toggleAllStudentsMicSwitch && this.canControlStudentAudio()) {
            toggleAllStudentsMicSwitch.addEventListener('change', () => this.toggleAllStudentsMicrophones());
        }

        // Global camera control toggle switch (teachers only)
        const toggleAllStudentsCameraSwitch = document.getElementById('toggleAllStudentsCameraSwitch');
        if (toggleAllStudentsCameraSwitch && this.canControlStudentAudio()) {
            toggleAllStudentsCameraSwitch.addEventListener('change', () => this.toggleAllStudentsCamera());
        }

        
        // Debug: Check if hand raise button exists
        const handRaiseBtn = document.getElementById('toggleHandRaise');
        if (handRaiseBtn) {
        } else {
            // List all buttons to help debug
            const allButtons = document.querySelectorAll('button');
        }
    }

    /**
     * Set up keyboard shortcuts
     */
    setupKeyboardShortcuts() {
        document.addEventListener('keydown', (event) => {
            // Don't trigger shortcuts when typing in input fields
            if (event.target.tagName === 'INPUT' || event.target.tagName === 'TEXTAREA') {
                return;
            }

            switch (event.code) {
                case 'KeyM':
                    if (event.ctrlKey || event.metaKey) {
                        event.preventDefault();
                        this.toggleMicrophone();
                    }
                    break;
                case 'KeyV':
                    if (event.ctrlKey || event.metaKey) {
                        event.preventDefault();
                        this.toggleCamera();
                    }
                    break;
                case 'KeyS':
                    if (event.ctrlKey || event.metaKey) {
                        event.preventDefault();
                        this.toggleScreenShare();
                    }
                    break;
                case 'KeyH':
                    if (event.ctrlKey || event.metaKey) {
                        event.preventDefault();
                        this.toggleHandRaise();
                    }
                    break;
                case 'KeyC':
                    if (event.ctrlKey || event.metaKey) {
                        event.preventDefault();
                        this.toggleChat();
                    }
                    break;
                case 'Escape':
                    if (this.currentSidebarType) {
                        this.closeSidebar();
                    }
                    break;
            }
        });

    }

    /**
     * Toggle microphone on/off
     */
    async toggleMicrophone() {

        if (!this.localParticipant) {
            this.showNotification(t('control_errors.not_connected'), 'error');
            return;
        }

        try {
            // Get current state from SDK first
            const currentState = this.localParticipant.isMicrophoneEnabled;
            const newState = !currentState;


            // Check audio permissions for students (teachers have full control)
            if (this.userRole === 'student' && newState) {
                // Student trying to unmute - check permissions with enhanced validation
                if (!this.canStudentUnmute()) {
                    this.showPermissionDeniedNotification();
                    return;
                }

                // TRIPLE SAFETY CHECK: Prevent any bypass attempts
                if (this.globalAudioControlsState.allStudentsMuted === true) {
                    this.showPermissionDeniedNotification();
                    return;
                }

                if (this.globalAudioControlsState.studentsCanSelfUnmute === false) {
                    this.showPermissionDeniedNotification();
                    return;
                }
            }

            // Use SDK method to enable/disable microphone with audio optimization
            if (newState) {
                // Audio optimization settings (reduces bandwidth by 40-50%)
                const audioOptions = {
                    audioBitrate: 32000,        // 32 kbps (down from default 64 kbps)
                    dtx: true,                  // Discontinuous transmission (silence detection)
                    autoGainControl: true,      // Automatic gain control for consistent volume
                    echoCancellation: true,     // Echo cancellation
                    noiseSuppression: true,     // Noise suppression for clearer audio
                };
                await this.localParticipant.setMicrophoneEnabled(true, audioOptions);
            } else {
                await this.localParticipant.setMicrophoneEnabled(false);
            }

            // Update our internal state to match the SDK
            this.isAudioEnabled = this.localParticipant.isMicrophoneEnabled;

            // Update individual audio permission state for students
            if (this.userRole === 'student') {
                this.updateStudentAudioPermissionState();
            }

            // Update UI
            this.updateControlButtons();

            const status = this.isAudioEnabled ? t('control_states.mic_enabled') : t('control_states.mic_disabled');
            this.showNotification(`${t('control_states.microphone')}: ${status}`, 'success');

            // Notify state change
            this.notifyControlStateChange('microphone', this.isAudioEnabled);

        } catch (error) {
            this.showNotification(t('control_errors.mic_error'), 'error');
            // Reset state to match SDK
            this.isAudioEnabled = this.localParticipant.isMicrophoneEnabled;
            this.updateControlButtons();
        }
    }

    /**
     * Check if student can unmute their microphone
     * @returns {boolean} - True if student has permission to unmute
     */
    canStudentUnmute() {
        // Teachers always have permission
        if (this.userRole === 'teacher') {
            return true;
        }

        // Debug: Log current global audio control state
        console.log('Global audio control state', {
            allStudentsMuted: this.globalAudioControlsState.allStudentsMuted,
            studentsCanSelfUnmute: this.globalAudioControlsState.studentsCanSelfUnmute,
            teacherControlsAudio: this.globalAudioControlsState.teacherControlsAudio,
            globalStateExplicitlySet: this.globalStateExplicitlySet
        });

        // CRITICAL FIX: Always enforce teacher restrictions when they exist
        // Check if teacher has disabled microphones for all students
        if (this.globalAudioControlsState.allStudentsMuted === true) {
            return false;
        }

        // Check if teacher has disabled student self-unmute capability
        if (this.globalAudioControlsState.studentsCanSelfUnmute === false) {
            return false;
        }

        // Check individual permissions (only if participant is available)
        if (this.localParticipant) {
            const participantSid = this.localParticipant.sid;
            const individualPermission = this.studentAudioPermissions.get(participantSid);

            if (individualPermission && individualPermission.canSpeak === false) {
                return false;
            }
        }

        // IMPORTANT: Only allow unmute if no restrictions are in place
        return true;
    }

    /**
     * Show permission denied notification with context
     */
    showPermissionDeniedNotification() {
        let message = t('permissions.cannot_unmute');

        if (this.globalAudioControlsState.allStudentsMuted) {
            message = t('permissions.teacher_muted_all');
        } else if (!this.globalAudioControlsState.studentsCanSelfUnmute) {
            message = t('permissions.teacher_controls_audio');
        }

        this.showNotification(message, 'error');
    }

    /**
     * Update student audio permission state after microphone toggle
     */
    updateStudentAudioPermissionState() {
        const participantSid = this.localParticipant.sid;
        const currentPermission = this.studentAudioPermissions.get(participantSid) || {};

        // Update the muted state to reflect current microphone state
        this.studentAudioPermissions.set(participantSid, {
            ...currentPermission,
            isMuted: !this.isAudioEnabled,
            lastUpdated: Date.now()
        });

        console.log('Student audio permission state updated', {
            participantSid,
            isMuted: !this.isAudioEnabled,
            canSpeak: currentPermission.canSpeak
        });
    }

    /**
     * Automatically unmute student when granted permission
     * @param {string} participantSid - Student participant SID
     */
    async autoUnmuteStudentWithPermission(participantSid) {
        // Only auto-unmute if this is the local participant
        if (participantSid !== this.localParticipant.sid) {
            return;
        }

        if (this.userRole !== 'student') {
            return;
        }

        try {

            // Enable microphone automatically
            await this.localParticipant.setMicrophoneEnabled(true);

            // Update internal state
            this.isAudioEnabled = true;
            this.updateControlButtons();

            // Update permission state
            this.updateStudentAudioPermissionState();

            this.showNotification(t('permissions.speaking_permission_granted'), 'success');


        } catch (error) {
            this.showNotification(t('permissions.auto_unmute_error'), 'error');
        }
    }

    /**
     * Toggle camera on/off
     */
    async toggleCamera() {

        if (!this.localParticipant) {
            this.showNotification(t('control_errors.not_connected'), 'error');
            return;
        }

        try {
            // Get current state from SDK first
            const currentState = this.localParticipant.isCameraEnabled;
            const newState = !currentState;


            // If enabling camera, apply session-type-aware quality settings
            if (newState) {
                // Get session type and participant count for adaptive quality
                const sessionType = window.sessionType || 'individual';
                const participantCount = this.room ? this.room.numParticipants : 1;

                // Session-type-aware video quality profiles (optimized for bandwidth)
                let videoOptions;
                if (sessionType === 'individual' || participantCount <= 3) {
                    // 1-on-1 or small groups: Higher quality
                    videoOptions = {
                        resolution: window.LiveKit.VideoPresets.h720.resolution,  // 1280×720
                        frameRate: 30,
                        maxBitrate: 1500000  // 1.5 Mbps
                    };
                } else if (participantCount <= 10) {
                    // Medium groups: Balanced quality
                    videoOptions = {
                        resolution: window.LiveKit.VideoPresets.h540.resolution,  // 960×540
                        frameRate: 24,
                        maxBitrate: 800000  // 0.8 Mbps
                    };
                } else {
                    // Large groups: Optimize for bandwidth
                    videoOptions = {
                        resolution: window.LiveKit.VideoPresets.h360.resolution,  // 640×360
                        frameRate: 20,
                        maxBitrate: 500000  // 0.5 Mbps
                    };
                }

                // Enable camera with optimized quality settings
                await this.localParticipant.setCameraEnabled(true, videoOptions);
            } else {
                // Disable camera
                await this.localParticipant.setCameraEnabled(false);
            }

            // Update our internal state to match the SDK
            this.isVideoEnabled = this.localParticipant.isCameraEnabled;

            // Update UI
            this.updateControlButtons();

            const status = this.isVideoEnabled ? t('control_states.camera_enabled') : t('control_states.camera_disabled');
            this.showNotification(`${t('control_states.camera')}: ${status}`, 'success');

            // Notify state change
            this.notifyControlStateChange('camera', this.isVideoEnabled);

        } catch (error) {
            this.showNotification(t('control_errors.camera_error'), 'error');
            // Reset state to match SDK
            this.isVideoEnabled = this.localParticipant.isCameraEnabled;
            this.updateControlButtons();
        }
    }

    /**
     * Toggle screen sharing on/off
     */
    async toggleScreenShare() {

        if (!this.localParticipant) {
            this.showNotification(t('control_errors.not_connected'), 'error');
            return;
        }

        try {
            const currentState = this.isScreenSharing;
            const newState = !currentState;


            if (newState) {
                // Start screen sharing
                await this.startScreenShare();
            } else {
                // Stop screen sharing
                await this.stopScreenShare();
            }

            // Update internal state
            this.isScreenSharing = newState;

            // Update UI
            this.updateControlButtons();

            const status = this.isScreenSharing ? t('control_states.camera_enabled') : t('control_states.camera_disabled');
            this.showNotification(`${t('control_states.screen_share')}: ${status}`, 'success');

            // Notify state change
            this.notifyControlStateChange('screenShare', this.isScreenSharing);

        } catch (error) {
            this.showNotification(t('control_errors.screen_share_error'), 'error');

            // Handle specific error cases
            if (error.name === 'NotAllowedError') {
                this.showNotification(t('control_errors.screen_share_denied'), 'error');
            } else if (error.name === 'NotSupportedError') {
                this.showNotification(t('control_errors.screen_share_not_supported'), 'error');
            }
        }
    }

    /**
     * Start screen sharing
     */
    async startScreenShare() {

        try {
            // Check if screen sharing is supported
            if (!navigator.mediaDevices || !navigator.mediaDevices.getDisplayMedia) {
                throw new Error('Screen sharing not supported in this browser');
            }

            // Get screen share constraints (optimized for static content)
            const constraints = {
                video: {
                    mediaSource: 'screen',
                    // Optimized for documents/slides (70-80% bandwidth savings)
                    width: { ideal: 1920, max: 1920 },
                    height: { ideal: 1080, max: 1080 },
                    frameRate: { ideal: 5, max: 10 }  // Low FPS for static content (documents/slides)
                },
                audio: {
                    // Allow system audio sharing if supported
                    echoCancellation: false,
                    noiseSuppression: false,
                    autoGainControl: false
                }
            };

            // Request screen share permission
            const stream = await navigator.mediaDevices.getDisplayMedia(constraints);


            // Handle stream end event (when user stops sharing via browser UI)
            stream.getVideoTracks()[0].addEventListener('ended', () => {
                this.handleScreenShareEnded();
            });

            // Publish screen share tracks using LiveKit
            const videoTrack = stream.getVideoTracks()[0];
            const audioTracks = stream.getAudioTracks();

            // Publish video track with bitrate optimization
            await this.localParticipant.publishTrack(videoTrack, {
                name: 'screen_share',
                source: window.LiveKit.Track.Source.ScreenShare,
                // Screen share optimization: Low bitrate for static content
                videoEncoding: {
                    maxBitrate: 500000,  // 0.5 Mbps (sufficient for documents/slides)
                    maxFramerate: 5,     // 5 FPS for static content
                }
            });


            // Publish audio track if available
            if (audioTracks.length > 0) {
                await this.localParticipant.publishTrack(audioTracks[0], {
                    name: 'screen_share_audio',
                    source: window.LiveKit.Track.Source.ScreenShareAudio
                });
            }

        } catch (error) {
            throw error;
        }
    }

    /**
     * Stop screen sharing
     */
    async stopScreenShare() {

        try {
            // Unpublish screen share tracks
            const publications = this.localParticipant.trackPublications;

            for (const [trackSid, publication] of publications) {
                if (publication.source === window.LiveKit.Track.Source.ScreenShare ||
                    publication.source === window.LiveKit.Track.Source.ScreenShareAudio) {

                    await this.localParticipant.unpublishTrack(publication.track);
                }
            }

        } catch (error) {
            throw error;
        }
    }

    /**
     * Handle screen share ended (by user via browser controls)
     */
    handleScreenShareEnded() {

        // Update internal state
        this.isScreenSharing = false;

        // Update UI
        this.updateControlButtons();

        // Show notification
        this.showNotification(t('screen_share.screen_share_stopped'), 'info');

        // Notify state change
        this.notifyControlStateChange('screenShare', false);
    }

    /**
     * Toggle hand raise (role-based behavior)
     */
    async toggleHandRaise() {

        // Role-based behavior
        if (this.userRole === 'teacher') {
            // Teachers open/close the raised hands sidebar
            this.toggleRaisedHandsSidebar();
            return;
        }

        if (this.userRole === 'student') {
            // Students toggle their hand raise state
            await this.toggleStudentHandRaise();
            return;
        }

    }

    /**
     * Toggle raised hands sidebar (teachers only)
     */
    toggleRaisedHandsSidebar() {
        if (!this.canControlStudentAudio()) {
            this.showNotification(t('permissions.cannot_manage_hands'), 'error');
            return;
        }


        if (this.currentSidebarType === 'raisedHands') {
            this.closeSidebar();
        } else {
            this.openSidebar('raisedHands');
        }

        // Mark all hand raises as acknowledged when opening sidebar
        if (this.currentSidebarType === 'raisedHands') {
            this.handRaiseNotificationCount = 0;
            this.updateRaisedHandsNotificationBadge();
        }
    }

    /**
     * Toggle student hand raise state
     */
    async toggleStudentHandRaise() {
        if (!this.canRaiseHand()) {
            this.showNotification(t('permissions.cannot_raise_hand'), 'error');
            return;
        }

        try {

            this.isHandRaised = !this.isHandRaised;


            // Send hand raise state via data channel with enhanced data
            const data = {
                type: 'handRaise',
                isRaised: this.isHandRaised,
                participantId: this.localParticipant.identity,
                participantSid: this.localParticipant.sid,
                timestamp: new Date().toISOString(),
                timeRaised: this.isHandRaised ? Date.now() : null
            };


            // Use the same reliable broadcasting as chat
            const encoder = new TextEncoder();
            const encodedData = encoder.encode(JSON.stringify(data));
            const dataKind = window.LiveKit.DataPacket_Kind?.RELIABLE || 1;

            await this.room.localParticipant.publishData(
                encodedData,
                dataKind,
                {
                    reliable: true,
                    destinationSids: [] // Broadcast to all participants
                }
            );

            // ✅ IMMEDIATE: Show hand raise indicator for current user - SIMPLE DIRECT APPROACH
            this.createHandRaiseIndicatorDirect(this.localParticipant.identity, this.isHandRaised);

            // Update local UI
            this.updateControlButtons();

            const status = this.isHandRaised ? t('control_states.raised') : t('control_states.lowered');
            this.showNotification(`${t('control_states.hand')}: ${status}`, 'success');

            // Notify state change
            this.notifyControlStateChange('handRaise', this.isHandRaised);


        } catch (error) {
            this.showNotification(t('control_errors.hand_raise_error'), 'error');
            // Revert state on error
            this.isHandRaised = !this.isHandRaised;
        }
    }

    // ===== RAISED HANDS MANAGEMENT METHODS =====

    /**
     * Add a participant to the raised hands queue
     * @param {Object} handRaiseData - Hand raise event data
     * @param {LiveKit.Participant} participant - Participant who raised hand
     */
    addToRaisedHandsQueue(handRaiseData, participant) {
        if (!this.canControlStudentAudio()) {
            return;
        }

        const participantSid = participant.sid;
        const participantIdentity = participant.identity;


        // Add to queue with timestamp
        this.raisedHandsQueue.set(participantSid, {
            identity: participantIdentity,
            sid: participantSid,
            timestamp: handRaiseData.timeRaised || Date.now(),
            granted: false,
            participant: participant
        });

        // Increment notification count if sidebar is not open
        if (this.currentSidebarType !== 'raisedHands') {
            this.handRaiseNotificationCount++;
        }

        // ✅ IMMEDIATE: Show hand raise indicator for this student - SIMPLE DIRECT APPROACH
        this.createHandRaiseIndicatorDirect(participantIdentity, true);

        // Update UI
        this.updateRaisedHandsUI();
        this.updateRaisedHandsNotificationBadge();

        // Show floating notification for teacher
        this.showHandRaiseNotification(participantIdentity);

        // Show notification for teacher
        this.showNotification(t('hand_raise.hand_raised_notification', { name: participantIdentity }), 'info');
    }

    /**
     * Remove a participant from the raised hands queue
     * @param {string} participantSid - Participant SID
     */
    async removeFromRaisedHandsQueue(participantSid) {
        if (!this.canControlStudentAudio()) {
            return;
        }

        const handRaise = this.raisedHandsQueue.get(participantSid);
        if (handRaise) {

            // ✅ IMMEDIATE: Hide hand raise indicator for this student - SIMPLE DIRECT APPROACH
            this.createHandRaiseIndicatorDirect(handRaise.identity, false);

            // Send message to student to lower their hand
            try {
                const data = {
                    type: 'lower_hand',
                    targetParticipantSid: participantSid,
                    targetParticipantId: handRaise.identity,
                    timestamp: Date.now(),
                    teacherId: this.localParticipant.identity
                };

                const encoder = new TextEncoder();
                const encodedData = encoder.encode(JSON.stringify(data));
                const dataKind = window.LiveKit.DataPacket_Kind?.RELIABLE || 1;

                await this.room.localParticipant.publishData(
                    encodedData,
                    dataKind,
                    { reliable: true }
                );

            } catch (error) {
            }

            this.raisedHandsQueue.delete(participantSid);

            // Update UI
            this.updateRaisedHandsUI();
            this.updateRaisedHandsNotificationBadge();
        }
    }

    /**
     * Grant audio permission to a student with raised hand
     * @param {string} participantSid - Participant SID
     */
    async grantAudioPermission(participantSid) {
        if (!this.canControlStudentAudio()) {
            this.showNotification(t('permissions.cannot_manage_audio'), 'error');
            return;
        }

        const handRaise = this.raisedHandsQueue.get(participantSid);
        if (!handRaise) {
            return;
        }

        try {

            // Update local permissions
            this.setParticipantAudioPermission(participantSid, true, false);

            // Mark as granted in queue
            handRaise.granted = true;
            handRaise.grantedAt = Date.now();

            // Send audio permission via data channel
            const data = {
                type: 'audioPermission',
                action: 'grant',
                targetParticipantSid: participantSid,
                targetParticipantId: handRaise.identity,
                grantedBy: this.localParticipant.identity,
                timestamp: new Date().toISOString()
            };


            const encoder = new TextEncoder();
            const encodedData = encoder.encode(JSON.stringify(data));
            const dataKind = window.LiveKit.DataPacket_Kind?.RELIABLE || 1;

            await this.room.localParticipant.publishData(
                encodedData,
                dataKind,
                {
                    reliable: true,
                    destinationSids: [] // Broadcast to all
                }
            );

            // Update UI
            this.updateRaisedHandsUI();

            // Trigger auto-unmute for the student (they will receive this via data channel)
            // The student's client will handle auto-unmuting when it receives the audioPermission event

            // Show success notification
            this.showNotification(t('hand_raise.granted_permission', { name: handRaise.identity }), 'success');

            // Show visual effect on participant video
            this.showPermissionGrantedEffect(participantSid);

            // Remove from queue after a delay (hand automatically lowered)
            setTimeout(() => {
                this.removeFromRaisedHandsQueue(participantSid);
            }, 1000);


        } catch (error) {
            this.showNotification(t('hand_raise.grant_error'), 'error');
        }
    }

    /**
     * Update raised hands UI elements
     */
    updateRaisedHandsUI() {
        if (!this.canControlStudentAudio()) {
            return;
        }

        const raisedHandsList = document.getElementById('raisedHandsList');
        const raisedHandsCount = document.getElementById('raisedHandsCount');
        const noRaisedHandsMessage = document.getElementById('noRaisedHandsMessage');

        if (!raisedHandsList || !raisedHandsCount) {
            return;
        }

        const raisedHands = Array.from(this.raisedHandsQueue.values());

        // Update count
        raisedHandsCount.textContent = raisedHands.length.toString();

        // Show/hide empty state message
        if (noRaisedHandsMessage) {
            noRaisedHandsMessage.style.display = raisedHands.length === 0 ? 'block' : 'none';
        }

        // Clear existing items
        const existingItems = raisedHandsList.querySelectorAll('.raised-hand-item');
        existingItems.forEach(item => item.remove());

        // Add items for each raised hand (sorted by timestamp)
        raisedHands
            .sort((a, b) => a.timestamp - b.timestamp)
            .forEach(handRaise => {
                const item = this.createRaisedHandItem(handRaise);
                raisedHandsList.appendChild(item);
            });

        // Update count
        if (raisedHandsCount) {
            raisedHandsCount.textContent = raisedHands.length.toString();
        }

        // Show/hide "clear all" button
        const clearAllBtn = document.getElementById('clearAllRaisedHandsBtn');
        if (clearAllBtn) {
            if (raisedHands.length > 0) {
                clearAllBtn.classList.remove('hidden');
            } else {
                clearAllBtn.classList.add('hidden');
            }
        }

    }

    /**
     * Create a raised hand item element (simplified without individual mic controls)
     * @param {Object} handRaise - Hand raise data
     * @returns {HTMLElement} - DOM element for the raised hand
     */
    createRaisedHandItem(handRaise) {
        const timeAgo = this.getTimeAgo(handRaise.timestamp);

        const item = document.createElement('div');
        item.className = 'raised-hand-item bg-gray-700 rounded-lg p-3 transition-all duration-200';
        item.dataset.participantSid = handRaise.sid;

        item.innerHTML = `
            <div class="flex items-center justify-between mb-2">
                <div class="flex items-center gap-2">
                    <div class="w-8 h-8 bg-orange-500 rounded-full flex items-center justify-center">
                        <i class="fa-solid fa-hand text-white text-sm"></i>
                    </div>
                    <div>
                        <p class="text-white font-medium text-sm">${handRaise.identity}</p>
                        <p class="text-gray-400 text-xs">${timeAgo}</p>
                    </div>
                </div>
                <span class="text-orange-400 text-xs">${t('hand_raise.hand_raised_label')}</span>
            </div>
            <div class="flex gap-2">
                <button onclick="window.meeting?.controls?.removeFromRaisedHandsQueue('${handRaise.sid}')" 
                        class="flex-1 bg-gray-600 hover:bg-gray-700 text-white px-3 py-1 rounded text-xs transition-colors">
                    ${t('hand_raise.hide_hand')}
                </button>
            </div>
        `;

        return item;
    }

    /**
     * Update raised hands notification badge
     */
    updateRaisedHandsNotificationBadge() {
        const badge = document.getElementById('raisedHandsNotificationBadge');
        const badgeCount = document.getElementById('raisedHandsBadgeCount');

        if (badge && badgeCount) {
            if (this.handRaiseNotificationCount > 0) {
                badge.classList.remove('hidden');
                badgeCount.textContent = this.handRaiseNotificationCount.toString();
            } else {
                badge.classList.add('hidden');
            }
        }
    }

    /**
     * Clear all raised hands with one click
     */
    async clearAllRaisedHands() {
        if (!this.room) {
            return;
        }

        try {
            const raisedHandsArray = Array.from(this.raisedHandsQueue.values());

            if (raisedHandsArray.length === 0) {
                return;
            }


            // Hide all hand raise indicators immediately (teacher side)
            raisedHandsArray.forEach(handRaise => {
                this.createHandRaiseIndicatorDirect(handRaise.identity, false);
            });

            // Send clear all command via data channel
            const data = {
                type: 'clear_all_raised_hands',
                timestamp: Date.now(),
                teacherId: this.localParticipant.identity
            };

            const encoder = new TextEncoder();
            const encodedData = encoder.encode(JSON.stringify(data));
            const dataKind = window.LiveKit.DataPacket_Kind?.RELIABLE || 1;

            await this.room.localParticipant.publishData(
                encodedData,
                dataKind,
                { reliable: true }
            );

            // Clear local state
            this.raisedHandsQueue.clear();
            this.raisedHands = {};
            this.handRaiseNotificationCount = 0;

            // Update UI
            this.updateRaisedHandsUI();
            this.updateRaisedHandsNotificationBadge();

            // Show success notification
            this.showNotification(t('hand_raise.all_hands_cleared'), 'success');

        } catch (error) {
            this.showNotification(t('hand_raise.clear_hands_error'), 'error');
        }
    }

    /**
     * Handle clear all hand raises from teacher (via data channel)
     * @param {Object} data - Message data
     */
    handleClearAllHandRaises(data) {

        // If student, lower their hand
        if (this.userRole === 'student' && this.isHandRaised) {
            this.isHandRaised = false;

            // Hide local hand raise indicator
            if (this.localParticipant) {
                this.createHandRaiseIndicatorDirect(this.localParticipant.identity, false);
            }

            // Update control buttons
            this.updateControlButtons();
        }

        // Clear local state (for both teacher and student)
        this.raisedHandsQueue.clear();
        this.raisedHands = {};
        this.handRaiseNotificationCount = 0;

        // Update UI
        this.updateRaisedHandsUI();
        this.updateRaisedHandsNotificationBadge();

    }

    /**
     * Get time ago string for timestamp
     * @param {number} timestamp - Timestamp
     * @returns {string} - Time ago string in Arabic
     */
    getTimeAgo(timestamp) {
        const now = Date.now();
        const diff = now - timestamp;
        const minutes = Math.floor(diff / 60000);
        const seconds = Math.floor((diff % 60000) / 1000);

        if (minutes > 0) {
            return t('hand_raise.minutes_ago', { minutes: minutes });
        } else {
            return t('hand_raise.seconds_ago', { seconds: seconds });
        }
    }

    // ===== GLOBAL AUDIO CONTROL METHODS =====

    /**
     * Toggle all students' microphones on/off using server-side API (teacher only)
     */
    async toggleAllStudentsMicrophones() {
        if (!this.canControlStudentAudio()) {
            this.showNotification(t('permissions.cannot_manage_audio'), 'error');
            return;
        }

        try {
            // Get the switch state
            const toggleSwitch = document.getElementById('toggleAllStudentsMicSwitch');
            const newAllowedState = toggleSwitch ? toggleSwitch.checked : false;
            const newMutedState = !newAllowedState; // Inverted logic: checked = allowed, unchecked = muted


            // Update global state
            this.globalAudioControlsState.allStudentsMuted = newMutedState;
            this.globalAudioControlsState.teacherControlsAudio = newMutedState;
            this.globalAudioControlsState.studentsCanSelfUnmute = newAllowedState;
            this.globalStateExplicitlySet = true;

            // Use actual LiveKit room name from connected room
            const roomName = this.room?.name || this.config?.meetingConfig?.roomName || `session-${window.sessionId}`;

            // Debug logging
            console.log('Toggle all students microphones', {
                hasRoom: !!this.room,
                roomName: roomName,
                roomObject: this.room?.name,
                configName: this.config?.meetingConfig?.roomName,
                fallback: `session-${window.sessionId}`,
                muted: newMutedState
            });

            // Call server-side API to mute all students
            const response = await window.LiveKitAPI.post('/livekit/participants/mute-all-students', {
                room_name: roomName,
                muted: newMutedState
            });

            if (!response.ok) {
                const errorData = await response.json();
                throw new Error(errorData.error || 'Server request failed');
            }

            const result = await response.json();
            
            // Update UI toggle
            this.updateGlobalAudioControlToggle();

            const status = newMutedState ? t('student_control.all_students_muted') : t('student_control.students_can_use_mic');
            this.showNotification(`✅ ${status}`, 'success');


            // Update all participant mic status icons immediately
            this.updateAllParticipantMicIcons(newMutedState);

            // Notify UI of state change
            this.notifyControlStateChange('globalAudioControl', {
                muted: newMutedState,
                allowed: newAllowedState,
                affectedParticipants: result.affected_participants
            });

        } catch (error) {
            this.showNotification(t('student_control.mic_control_error') + ': ' + error.message, 'error');

            // Reset toggle switch on error
            const toggleSwitch = document.getElementById('toggleAllStudentsMicSwitch');
            if (toggleSwitch) {
                toggleSwitch.checked = !toggleSwitch.checked; // Revert
            }
        }
    }

    /**
     * Toggle all students' cameras on/off using server-side API (teacher only)
     */
    async toggleAllStudentsCamera() {
        if (!this.canControlStudentAudio()) {
            this.showNotification(t('permissions.camera_control_not_allowed'), 'error');
            return;
        }

        try {
            // Get the switch state
            const toggleSwitch = document.getElementById('toggleAllStudentsCameraSwitch');
            const newAllowedState = toggleSwitch ? toggleSwitch.checked : false;
            const newDisabledState = !newAllowedState; // Inverted logic: checked = allowed, unchecked = disabled


            // Use actual LiveKit room name from connected room
            const roomName = this.room?.name || this.config?.meetingConfig?.roomName || `session-${window.sessionId}`;

            // Debug logging
            console.log('Toggle all students cameras', {
                hasRoom: !!this.room,
                roomName: roomName,
                roomObject: this.room?.name,
                configName: this.config?.meetingConfig?.roomName,
                fallback: `session-${window.sessionId}`,
                disabled: !newAllowedState
            });

            // Call server-side API to disable/enable all students cameras
            const response = await window.LiveKitAPI.post('/livekit/participants/disable-all-students-camera', {
                room_name: roomName,
                disabled: newDisabledState
            });

            if (!response.ok) {
                const errorData = await response.json();
                throw new Error(errorData.error || 'Server request failed');
            }

            const result = await response.json();

            const status = newDisabledState ? t('student_control.all_cameras_disabled') : t('student_control.students_can_use_camera');
            this.showNotification(`✅ ${status}`, 'success');


            // Update all participant camera status icons immediately
            this.updateAllParticipantCameraIcons(newDisabledState);

            // Notify UI of state change
            this.notifyControlStateChange('globalCameraControl', {
                disabled: newDisabledState,
                allowed: newAllowedState,
                affectedParticipants: result.affected_participants
            });

        } catch (error) {
            this.showNotification(t('student_control.camera_control_error') + ': ' + error.message, 'error');

            // Reset toggle switch on error
            const toggleSwitch = document.getElementById('toggleAllStudentsCameraSwitch');
            if (toggleSwitch) {
                toggleSwitch.checked = !toggleSwitch.checked; // Revert
            }
        }
    }

    /**
     * Get API token for authenticated requests
     */
    async getApiToken() {
        // For Laravel Sanctum, we use CSRF token in headers
        // If using API tokens, implement token retrieval here
        return '';
    }

    /**
     * Sync global audio state from toggle switch position
     */
    syncGlobalAudioStateFromToggle() {
        const toggleSwitch = document.getElementById('toggleAllStudentsMicSwitch');

        if (toggleSwitch) {
            // Read current toggle state and update global state accordingly
            const isAllowed = toggleSwitch.checked;
            this.globalAudioControlsState.allStudentsMuted = !isAllowed;
            this.globalAudioControlsState.studentsCanSelfUnmute = isAllowed;
            this.globalAudioControlsState.teacherControlsAudio = !isAllowed;

        }
    }

    /**
     * Update global audio control switch state
     */
    updateGlobalAudioControlToggle() {
        const toggleSwitch = document.getElementById('toggleAllStudentsMicSwitch');

        if (toggleSwitch) {
            // Set switch state: checked = students allowed, unchecked = students muted
            const shouldBeChecked = !this.globalAudioControlsState.allStudentsMuted;
            toggleSwitch.checked = shouldBeChecked;
        } else {
        }
    }

    /**
     * Toggle chat sidebar
     */
    toggleChat() {
        this.toggleSidebar('chat');
    }

    /**
     * Toggle participants list sidebar
     */
    toggleParticipantsList() {
        this.toggleSidebar('participants');
    }

    /**
     * Toggle settings sidebar
     */
    toggleSettings() {
        this.toggleSidebar('settings');
    }

    /**
     * Toggle recording (teacher only)
     */
    async toggleRecording() {
        if (!this.isTeacher()) {
            this.showNotification(t('permissions.recording_not_allowed'), 'error');
            return;
        }


        try {
            this.isRecording = !this.isRecording;

            if (this.isRecording) {
                await this.startRecording();
            } else {
                await this.stopRecording();
            }

            // Update UI
            this.updateControlButtons();

            const status = this.isRecording ? t('recording.started') : t('recording.stopped');
            this.showNotification(`${t('recording.title')}: ${status}`, 'success');

            // Notify state change
            this.notifyControlStateChange('recording', this.isRecording);

        } catch (error) {
            this.showNotification(t('recording.error'), 'error');
            // Revert state on error
            this.isRecording = !this.isRecording;
        }
    }

    /**
     * Start recording
     */
    async startRecording() {
        // Implementation would depend on your recording setup
        // This could involve calling a server endpoint to start recording
    }

    /**
     * Stop recording
     */
    async stopRecording() {
        // Implementation would depend on your recording setup
        // This could involve calling a server endpoint to stop recording
    }

    /**
     * Toggle sidebar
     * @param {string} type - Sidebar type ('chat', 'participants', 'settings')
     */
    toggleSidebar(type) {
        if (this.currentSidebarType === type) {
            this.closeSidebar();
        } else {
            this.openSidebar(type);
        }
    }

    /**
     * Open sidebar
     * @param {string} type - Sidebar type
     */
    openSidebar(type) {

        const sidebar = document.getElementById('meetingSidebar');
        if (!sidebar) {
            return;
        }

        // Hide all sidebar content
        const sidebarContents = sidebar.querySelectorAll('[id$="Content"]');
        sidebarContents.forEach(content => {
            content.classList.add('hidden');
            content.classList.remove('flex', 'flex-col');
        });

        // Show specific content
        const targetContent = document.getElementById(`${type}Content`);
        if (targetContent) {
            targetContent.classList.remove('hidden');
            targetContent.classList.add('flex', 'flex-col');
        }

        // Show sidebar by sliding in from right
        sidebar.classList.remove('translate-x-full');
        sidebar.classList.add('-translate-x-0');

        this.currentSidebarType = type;
        this.updateSidebarButtonStates(type);

        // Update specific sidebar state
        switch (type) {
            case 'chat':
                this.isChatOpen = true;
                // Update sidebar title
                const chatSidebarTitle = document.getElementById('sidebarTitle');
                if (chatSidebarTitle) {
                    chatSidebarTitle.textContent = t('sidebar.chat');
                }
                this.markChatAsRead();
                break;
            case 'participants':
                this.isParticipantsListOpen = true;
                // Update sidebar title
                const participantsSidebarTitle = document.getElementById('sidebarTitle');
                if (participantsSidebarTitle) {
                    participantsSidebarTitle.textContent = t('sidebar.participants');
                }
                // Update participants list when opening the sidebar
                if (this.config.onParticipantsListOpened) {
                    this.config.onParticipantsListOpened();
                }
                break;
            case 'raisedHands':
                this.isHandRaiseQueueOpen = true;
                // Update sidebar title
                const sidebarTitle = document.getElementById('sidebarTitle');
                if (sidebarTitle) {
                    sidebarTitle.textContent = t('sidebar.raised_hands');
                }
                // Reset notification count when opening
                this.handRaiseNotificationCount = 0;
                this.updateRaisedHandsNotificationBadge();
                // Update the raised hands UI
                this.updateRaisedHandsUI();
                break;
            case 'settings':
                this.isSettingsOpen = true;
                // Update sidebar title
                const settingsSidebarTitle = document.getElementById('sidebarTitle');
                if (settingsSidebarTitle) {
                    settingsSidebarTitle.textContent = t('sidebar.settings');
                }
                this.updateSettingsPanel();
                break;
        }

    }

    /**
     * Close sidebar
     */
    closeSidebar() {

        const sidebar = document.getElementById('meetingSidebar');
        if (!sidebar) {
            return;
        }

        // Hide sidebar by sliding out to right
        sidebar.classList.remove('-translate-x-0');
        sidebar.classList.add('translate-x-full');

        const previousType = this.currentSidebarType;
        this.currentSidebarType = null;
        this.updateSidebarButtonStates(null);

        // Update specific sidebar state
        switch (previousType) {
            case 'chat':
                this.isChatOpen = false;
                break;
            case 'participants':
                this.isParticipantsListOpen = false;
                break;
            case 'raisedHands':
                this.isHandRaiseQueueOpen = false;
                // Reset sidebar title
                const sidebarTitle = document.getElementById('sidebarTitle');
                if (sidebarTitle) {
                    sidebarTitle.textContent = t('sidebar.chat'); // Reset to default "Chat"
                }
                break;
            case 'settings':
                this.isSettingsOpen = false;
                break;
        }

    }

    /**
     * Toggle fullscreen mode
     */
    toggleFullscreen() {

        const meetingInterface = document.getElementById('livekitMeetingInterface');
        if (!meetingInterface) {
            return;
        }

        if (meetingInterface.classList.contains('meeting-fullscreen')) {
            // Exit fullscreen
            meetingInterface.classList.remove('meeting-fullscreen');
            this.updateFullscreenButton(false);
        } else {
            // Enter fullscreen
            meetingInterface.classList.add('meeting-fullscreen');
            this.updateFullscreenButton(true);
        }
    }

    /**
     * Update fullscreen button state
     * @param {boolean} isFullscreen - Whether in fullscreen mode
     */
    updateFullscreenButton(isFullscreen) {
        const fullscreenText = document.getElementById('fullscreenText');
        const fullscreenIcon = document.getElementById('fullscreenIcon');

        if (fullscreenText) {
            fullscreenText.textContent = isFullscreen ? t('fullscreen.exit') : t('fullscreen.enter');
        }

        if (fullscreenIcon) {
            if (isFullscreen) {
                fullscreenIcon.className = 'ri-fullscreen-exit-line text-lg text-white';
            } else {
                fullscreenIcon.className = 'ri-fullscreen-line text-lg text-white';
            }
        }
    }

    /**
     * Update sidebar button states
     * @param {string|null} activeType - Currently active sidebar type
     */
    updateSidebarButtonStates(activeType) {
        const buttons = {
            chat: document.getElementById('toggleChat'),
            participants: document.getElementById('toggleParticipants'),
            raisedHands: document.getElementById('toggleRaisedHands'),
            settings: document.getElementById('toggleSettings')
        };

        Object.entries(buttons).forEach(([type, button]) => {
            if (!button) return;

            if (type === activeType) {
                // Active state styling
                if (type === 'raisedHands') {
                    button.classList.add('bg-orange-600', 'text-white');
                    button.classList.remove('bg-gray-600', 'text-gray-300');
                } else {
                    button.classList.add('bg-blue-600', 'text-white');
                    button.classList.remove('bg-gray-600', 'text-gray-300');
                }
            } else {
                // Inactive state styling
                button.classList.remove('bg-blue-600', 'bg-orange-600', 'text-white');
                button.classList.add('bg-gray-600', 'text-gray-300');
            }
        });
    }

    /**
     * Send chat message
     */
    async sendChatMessage() {
        const messageInput = document.getElementById('chatMessageInput');
        if (!messageInput || !messageInput.value.trim()) {
            return;
        }

        const message = messageInput.value.trim();

        try {
            // Enhanced debugging for chat sending

            // Verify room is properly connected before sending
            if (this.room?.state !== 'connected') {
                throw new Error(`Room not connected. Current state: ${this.room?.state}`);
            }

            // Log all participants for debugging
            this.room.remoteParticipants.forEach((participant, sid) => {
            });

            if (!this.room.remoteParticipants || this.room.remoteParticipants.size === 0) {
                this.showNotification(t('chat.no_other_participants'), 'warning');
            }

            // Create comprehensive message data
            const data = {
                type: 'chat',
                message: message,
                sender: this.localParticipant.identity,
                senderSid: this.localParticipant.sid,
                timestamp: new Date().toISOString(),
                messageId: `msg_${Date.now()}_${Math.random().toString(36).substr(2, 9)}`
            };


            // CRITICAL FIX: Use proper data encoding
            const encoder = new TextEncoder();
            const encodedData = encoder.encode(JSON.stringify(data));


            // Use reliable data packet kind for guaranteed delivery
            const LiveKitSDK = window.LiveKit;
            let dataKind = 1; // Safe fallback

            if (LiveKitSDK && LiveKitSDK.DataPacket_Kind) {
                dataKind = LiveKitSDK.DataPacket_Kind.RELIABLE || 1;
            }


            // CRITICAL FIX: Explicit destination scoping to broadcast to ALL participants
            const publishOptions = {
                reliable: true,
                destinationSids: [] // Empty array = broadcast to ALL participants in room
            };


            // Publish the data
            await this.room.localParticipant.publishData(
                encodedData,
                dataKind,
                publishOptions
            );


            // Verification logging

            // Add message to local chat UI immediately
            this.addChatMessage(message, this.localParticipant.identity, true);

            // Clear input
            messageInput.value = '';


        } catch (error) {
            console.error('Chat message send error', {
                name: error.name,
                message: error.message,
                stack: error.stack,
                roomState: this.room?.state,
                localParticipant: this.localParticipant?.identity,
                participantCount: this.room?.numParticipants
            });

            this.showNotification(t('chat.send_error') + ': ' + error.message, 'error');
        }
    }

    /**
     * Add chat message to UI
     * @param {string} message - Message text
     * @param {string} sender - Sender name
     * @param {boolean} isOwn - Whether this is the user's own message
     */
    addChatMessage(message, sender, isOwn = false) {
        const messagesContainer = document.getElementById('chatMessages');
        if (!messagesContainer) return;

        const messageElement = document.createElement('div');
        messageElement.className = `flex ${isOwn ? 'justify-end' : 'justify-start'} mb-3`;

        const timestamp = new Date().toLocaleTimeString('ar-SA', {
            hour: '2-digit',
            minute: '2-digit'
        });

        messageElement.innerHTML = `
            <div class="${isOwn ? 'bg-blue-600' : 'bg-white'} rounded-lg px-3 py-2 max-w-xs border border-gray-200">
                <p class="text-xs ${isOwn ? 'text-blue-100' : 'text-gray-500'} mb-1">${isOwn ? t('chat.you') : sender}</p>
                <p class="${isOwn ? 'text-white' : 'text-gray-800'} text-sm">${message}</p>
                <p class="text-xs ${isOwn ? 'text-blue-200' : 'text-gray-400'} mt-1">${timestamp}</p>
            </div>
        `;

        messagesContainer.appendChild(messageElement);
        messagesContainer.scrollTop = messagesContainer.scrollHeight;

        // Show notification if chat is not open
        if (!this.isChatOpen) {
            this.showChatNotification();
        }
    }

    /**
     * Show chat notification
     */
    showChatNotification() {
        const chatButton = document.getElementById('toggleChat');
        if (!chatButton) return;

        // Add notification badge
        let badge = chatButton.querySelector('.notification-badge');
        if (!badge) {
            badge = document.createElement('div');
            badge.className = 'notification-badge absolute -top-1 -right-1 w-3 h-3 bg-red-500 rounded-full';
            chatButton.style.position = 'relative';
            chatButton.appendChild(badge);
        }
    }

    /**
     * Mark chat as read
     */
    markChatAsRead() {
        const chatButton = document.getElementById('toggleChat');
        if (!chatButton) return;

        const badge = chatButton.querySelector('.notification-badge');
        if (badge) {
            badge.remove();
        }
    }

    /**
     * Update settings panel
     */
    async updateSettingsPanel() {
        // Implementation for updating device settings
    }

    /**
     * Update control button states
     */
    updateControlButtons() {
        console.log('Updating control buttons', {
            audio: this.isAudioEnabled,
            video: this.isVideoEnabled,
            screenShare: this.isScreenSharing,
            handRaise: this.isHandRaised
        });

        // Microphone button - show proper status for all users
        const micButton = document.getElementById('toggleMic');
        if (micButton) {
            const svg = micButton.querySelector('svg');
            const tooltip = micButton.querySelector('.control-tooltip');

            if (this.userRole === 'teacher') {
                // Teachers see full mic status
                if (this.isAudioEnabled) {
                    micButton.classList.remove('bg-red-600', 'bg-red-500');
                    micButton.classList.add('bg-gray-600');
                    micButton.title = t('controls.stop_mic');
                    if (tooltip) tooltip.textContent = t('controls.stop_mic');
                    if (svg) {
                        svg.innerHTML = '<path fill-rule="evenodd" d="M7 4a3 3 0 016 0v4a3 3 0 11-6 0V4zm4 10.93A7.001 7.001 0 0017 8a1 1 0 10-2 0A5 5 0 715 8a1 1 0 00-2 0 7.001 7.001 0 006 6.93V17H6a1 1 0 100 2h8a1 1 0 100-2h-3v-2.07z" clip-rule="evenodd"/>';
                    }
                } else {
                    micButton.classList.add('bg-red-600');
                    micButton.classList.remove('bg-gray-600', 'bg-gray-700');
                    micButton.title = t('controls.start_mic');
                    if (tooltip) tooltip.textContent = t('controls.start_mic');
                    if (svg) {
                        svg.innerHTML = '<path d="M2.5 8.5a6 6 0 0 1 12 0v2a1 1 0 0 0 2 0v-2a8 8 0 0 0-16 0v2a1 1 0 0 0 2 0v-2z"/><path d="M10 8a2 2 0 1 1-4 0V6a2 2 0 1 1 4 0v2zM8 13a1 1 0 0 1 1-1h4a1 1 0 1 1 0 2H9a1 1 0 0 1-1-1z"/><path d="m2.5 1.5 15 15a1 1 0 0 1-1.414 1.414l-15-15A1 1 0 0 1 2.5 1.5z"/>';
                    }
                }
            } else {
                // Students see their actual mic status but with visual feedback about permissions
                const canUnmute = this.canStudentUnmute();

                if (this.isAudioEnabled) {
                    // Student mic is enabled
                    micButton.classList.remove('bg-red-600', 'bg-red-500', 'bg-gray-500');
                    micButton.classList.add('bg-green-600');
                    micButton.title = t('controls.stop_mic');
                    if (tooltip) tooltip.textContent = t('controls.stop_mic');
                    if (svg) {
                        svg.innerHTML = '<path fill-rule="evenodd" d="M7 4a3 3 0 0 1 6 0v4a3 3 0 1 1-6 0V4zm4 10.93A7.001 7.001 0 0 0 17 8a1 1 0 1 0-2 0A5 5 0 0 1 5 8a1 1 0 0 0-2 0 7.001 7.001 0 0 0 6 6.93V17H6a1 1 0 1 0 0 2h8a1 1 0 1 0 0-2h-3v-2.07z" clip-rule="evenodd"/>';
                    }
                } else {
                    // Student mic is disabled
                    if (canUnmute) {
                        // Can unmute - show normal disabled state
                        micButton.classList.remove('bg-green-600', 'bg-gray-500');
                        micButton.classList.add('bg-red-600');
                        micButton.title = t('controls.start_mic');
                        if (tooltip) tooltip.textContent = t('controls.start_mic');
                    } else {
                        // Cannot unmute - show restricted state
                        micButton.classList.remove('bg-green-600', 'bg-red-600');
                        micButton.classList.add('bg-gray-500');
                        micButton.title = t('controls.mic_disabled_by_teacher');
                        if (tooltip) tooltip.textContent = t('controls.mic_disabled_by_teacher');
                    }
                    if (svg) {
                        svg.innerHTML = '<path d="M2.5 8.5a6 6 0 0 1 12 0v2a1 1 0 0 0 2 0v-2a8 8 0 0 0-16 0v2a1 1 0 0 0 2 0v-2z"/><path d="M10 8a2 2 0 1 1-4 0V6a2 2 0 1 1 4 0v2zM8 13a1 1 0 0 1 1-1h4a1 1 0 1 1 0 2H9a1 1 0 0 1-1-1z"/><path d="m2.5 1.5 15 15a1 1 0 0 1-1.414 1.414l-15-15A1 1 0 0 1 2.5 1.5z"/>';
                    }
                }
            }
        }

        // Camera button
        const cameraButton = document.getElementById('toggleCamera');
        if (cameraButton) {
            const svg = cameraButton.querySelector('svg');
            const tooltip = cameraButton.querySelector('.control-tooltip');

            if (this.isVideoEnabled) {
                cameraButton.classList.remove('bg-red-600', 'bg-red-500');
                cameraButton.classList.add('bg-gray-600');
                cameraButton.title = t('controls.stop_camera');
                if (tooltip) tooltip.textContent = t('controls.stop_camera');
                if (svg) {
                    svg.innerHTML = '<path fill-rule="evenodd" d="M4 3a2 2 0 00-2 2v10a2 2 0 002 2h12a2 2 0 002-2V5a2 2 0 00-2-2H4zm12 12H4l4-8v3l2 2 2-2v-3l4 8z" clip-rule="evenodd"/>';
                }
            } else {
                cameraButton.classList.add('bg-red-600');
                cameraButton.classList.remove('bg-gray-600', 'bg-gray-700');
                cameraButton.title = t('controls.start_camera');
                if (tooltip) tooltip.textContent = t('controls.start_camera');
                if (svg) {
                    svg.innerHTML = '<path d="M4 3a2 2 0 0 0-2 2v10a2 2 0 0 0 2 2h8a2 2 0 0 0 2-2V5a2 2 0 00-2-2H4z"/><path d="m16 7 2-2v10l-2-2V7z"/><path d="m2.5 1.5 15 15a1 1 0 0 1-1.414 1.414l-15-15A1 1 0 0 1 2.5 1.5z"/>';
                }
            }
        }

        // Screen share button
        const screenShareButton = document.getElementById('toggleScreenShare');
        if (screenShareButton) {
            const svg = screenShareButton.querySelector('svg');
            const tooltip = screenShareButton.querySelector('.control-tooltip');

            if (this.isScreenSharing) {
                screenShareButton.classList.add('bg-blue-600');
                screenShareButton.classList.remove('bg-gray-600', 'bg-gray-700');
                screenShareButton.title = t('controls.stop_screen_share');
                if (tooltip) tooltip.textContent = t('controls.stop_screen_share');
                if (svg) {
                    svg.innerHTML = '<path d="M3 4a1 1 0 011-1h12a1 1 0 011 1v2a1 1 0 01-1 1H4a1 1 0 01-1-1V4zM3 10a1 1 0 011-1h6a1 1 0 011 1v6a1 1 0 01-1 1H4a1 1 0 01-1-1v-6zM14 9a1 1 0 00-1 1v6a1 1 0 001 1h2a1 1 0 001-1v-6a1 1 0 00-1-1h-2z"/><path d="M6 16h4v2H6z"/>';
                }
            } else {
                screenShareButton.classList.remove('bg-blue-600');
                screenShareButton.classList.add('bg-gray-600');
                screenShareButton.title = t('controls.start_screen_share');
                if (tooltip) tooltip.textContent = t('controls.start_screen_share');
                if (svg) {
                    svg.innerHTML = '<path d="M3 4a1 1 0 011-1h12a1 1 0 011 1v2a1 1 0 01-1 1H4a1 1 0 01-1-1V4zM3 10a1 1 0 011-1h6a1 1 0 011 1v6a1 1 0 01-1 1H4a1 1 0 01-1-1v-6zM14 9a1 1 0 00-1 1v6a1 1 0 001 1h2a1 1 0 001-1v-6a1 1 0 00-1-1h-2z"/>';
                }
            }
        }

        // Hand raise button
        const handRaiseButton = document.getElementById('toggleHandRaise');
        if (handRaiseButton) {
            const icon = handRaiseButton.querySelector('i');
            const tooltip = handRaiseButton.querySelector('.control-tooltip');

            if (this.isHandRaised) {
                handRaiseButton.classList.add('bg-yellow-600');
                handRaiseButton.classList.remove('bg-gray-600', 'bg-gray-700');
                handRaiseButton.title = t('controls.lower_hand');
                if (tooltip) tooltip.textContent = t('controls.lower_hand');
                if (icon) icon.className = 'fa-solid fa-hand text-white text-xl';
            } else {
                handRaiseButton.classList.remove('bg-yellow-600');
                handRaiseButton.classList.add('bg-gray-600');
                handRaiseButton.title = t('controls.raise_hand');
                if (tooltip) tooltip.textContent = t('controls.raise_hand');
                if (icon) icon.className = 'fa-regular fa-hand text-white text-xl';
            }
        }

        // Recording button (teacher only)
        const recordButton = document.getElementById('toggleRecording');
        if (recordButton && this.isTeacher()) {
            const svg = recordButton.querySelector('svg');
            if (this.isRecording) {
                recordButton.classList.add('bg-red-600');
                recordButton.classList.remove('bg-gray-600', 'bg-gray-700');
                recordButton.title = t('controls.stop_recording');
                if (svg) {
                    svg.innerHTML = '<path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zM8 7a1 1 0 011-1h2a1 1 0 011 1v6a1 1 0 01-1 1H9a1 1 0 01-1-1V7z" clip-rule="evenodd"/>';
                }
            } else {
                recordButton.classList.remove('bg-red-600');
                recordButton.classList.add('bg-gray-600');
                recordButton.title = t('controls.start_recording');
                if (svg) {
                    svg.innerHTML = '<path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zM9.555 7.168A1 1 0 008 8v4a1 1 0 001.555.832l3-2a1 1 0 000-1.664l-3-2z" clip-rule="evenodd"/>';
                }
            }
        }

    }

    /**
     * Show leave confirmation modal
     */
    showLeaveConfirmModal() {
        const modal = document.createElement('div');
        modal.id = 'leaveConfirmModal';
        modal.className = 'fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-[99999]';

        modal.innerHTML = `
            <div class="bg-gray-800 rounded-lg p-6 max-w-md mx-4">
                <h3 class="text-xl font-bold text-white mb-4">${t('leave.title')}</h3>
                <p class="text-gray-300 mb-6">${t('leave.confirm_message')}</p>
                <div class="flex justify-end space-x-3 space-x-reverse">
                    <button id="cancelLeave" class="px-4 py-2 bg-gray-600 text-white rounded hover:bg-gray-700 transition-colors">
                        ${t('leave.cancel')}
                    </button>
                    <button id="confirmLeave" class="px-4 py-2 bg-red-600 text-white rounded hover:bg-red-700 transition-colors">
                        ${t('leave.leave')}
                    </button>
                </div>
            </div>
        `;

        document.body.appendChild(modal);

        // Handle cancel
        const cancelButton = modal.querySelector('#cancelLeave');
        const closeModal = () => {
            modal.remove();
        };

        cancelButton.addEventListener('click', closeModal);
        modal.addEventListener('click', (e) => {
            if (e.target === modal) closeModal();
        });

        // Handle confirm
        const confirmButton = modal.querySelector('#confirmLeave');
        confirmButton.addEventListener('click', () => {
            modal.remove();
            this.leaveMeeting();
        });
    }

    /**
     * Leave the meeting
     */
    leaveMeeting() {

        // CRITICAL FIX: Record leave attendance BEFORE leaving
        this.recordLeaveAttendance();

        if (this.config.onLeaveRequest) {
            this.config.onLeaveRequest();
        } else {
            // Fallback behavior - simply reload the current page
            window.location.reload();
        }
    }

    /**
     * Record leave attendance when user clicks leave button
     */
    async recordLeaveAttendance() {
        try {
            
            // Get session ID and type from window object (set in Blade template)
            const sessionId = window.sessionId;
            const sessionType = window.sessionType || 'quran';
            
            if (!sessionId) {
                return;
            }

            const response = await window.LiveKitAPI.post('/api/sessions/meeting/leave', {
                session_type: sessionType,
                session_id: sessionId
            });

            if (response.ok) {
                const data = await response.json();
            } else {
                const error = await response.text();
            }
        } catch (error) {
        }
    }

    /**
     * Start meeting timer
     */
    startMeetingTimer() {

        this.meetingStartTime = Date.now();
        this.timerInterval = setInterval(() => {
            this.updateMeetingTimer();
        }, 1000);
    }

    /**
     * Stop meeting timer
     */
    stopMeetingTimer() {
        if (this.timerInterval) {
            clearInterval(this.timerInterval);
            this.timerInterval = null;
        }
    }

    /**
     * Update meeting timer display
     */
    updateMeetingTimer() {
        const timerElement = document.getElementById('meetingTimer');
        if (!timerElement) return;

        const elapsed = Date.now() - this.meetingStartTime;
        const minutes = Math.floor(elapsed / 60000);
        const seconds = Math.floor((elapsed % 60000) / 1000);

        timerElement.textContent = `${minutes}:${seconds.toString().padStart(2, '0')}`;
    }

    /**
     * Check if current user is a teacher
     * @returns {boolean}
     */
    isTeacher() {
        return this.config.meetingConfig?.role === 'teacher';
    }

    // ===== HAND-RAISING & AUDIO MANAGEMENT METHODS =====

    /**
     * Detect user role from meeting configuration
     * @returns {string} User role ('teacher', 'student', 'unknown')
     */
    detectUserRole() {
        const role = this.config.meetingConfig?.role;
        if (role === 'teacher' || role === 'quran_teacher') {
            return 'teacher';
        } else if (role === 'student') {
            return 'student';
        }
        return 'unknown';
    }

    /**
     * Check if current user can raise hand
     * @returns {boolean}
     */
    canRaiseHand() {
        return this.userRole === 'student';
    }

    /**
     * Check if current user can control student audio
     * @returns {boolean}
     */
    canControlStudentAudio() {
        return this.userRole === 'teacher';
    }

    /**
     * Check if current user is a student
     * @returns {boolean}
     */
    isStudent() {
        return this.userRole === 'student';
    }

    /**
     * Get participant role by identity or SID
     * @param {string} participantIdentifier - Participant identity or SID
     * @returns {string} Role ('teacher', 'student', 'unknown')
     */
    getParticipantRole(participantIdentifier) {
        // For now, assume anyone who can control audio is a teacher
        // In production, this would come from the meeting configuration
        if (participantIdentifier === this.localParticipant?.identity) {
            return this.userRole;
        }

        // Default to student for remote participants
        // This could be enhanced to get role from participant metadata
        return 'student';
    }

    /**
     * Check if a participant can currently speak
     * @param {string} participantSid - Participant SID
     * @returns {boolean}
     */
    canParticipantSpeak(participantSid) {
        const permissions = this.studentAudioPermissions.get(participantSid);
        if (!permissions) {
            // Default: students can speak unless teacher has restricted it
            return !this.globalAudioControlsState.allStudentsMuted;
        }
        return permissions.canSpeak && !permissions.isMuted;
    }

    /**
     * Update audio permissions for a participant
     * @param {string} participantSid - Participant SID
     * @param {boolean} canSpeak - Whether participant can speak
     * @param {boolean} isMuted - Whether participant is currently muted
     */
    setParticipantAudioPermission(participantSid, canSpeak, isMuted = false) {
        this.studentAudioPermissions.set(participantSid, {
            canSpeak,
            isMuted,
            updatedAt: Date.now()
        });

    }

    /**
     * Show notification using callback or unified toast system
     * @param {string} message - Notification message
     * @param {string} type - Notification type ('success', 'error', 'info', 'warning')
     */
    showNotification(message, type = 'info') {
        if (this.config.onNotification) {
            this.config.onNotification(message, type);
        } else if (window.toast) {
            // Fallback to unified toast system
            const toastMethod = window.toast[type] || window.toast.info;
            toastMethod(message);
        }
    }

    /**
     * Notify control state change
     * @param {string} control - Control name
     * @param {boolean} enabled - Whether control is enabled
     */
    notifyControlStateChange(control, enabled) {
        if (this.config.onControlStateChange) {
            this.config.onControlStateChange(control, enabled);
        }
    }

    /**
     * Get current control states
     * @returns {Object} Current control states
     */
    getControlStates() {
        return {
            audio: this.isAudioEnabled,
            video: this.isVideoEnabled,
            screenShare: this.isScreenSharing,
            handRaise: this.isHandRaised,
            recording: this.isRecording,
            chat: this.isChatOpen,
            participants: this.isParticipantsListOpen,
            settings: this.isSettingsOpen
        };
    }

    /**
     * Handle data received (for chat, hand raise, and audio controls)
     * @param {Object} data - Received data
     * @param {LiveKit.Participant} participant - Sender participant
     */
    handleDataReceived(data, participant) {
        try {
            // Log all received data for debugging
            if (window.debugMode) {
                console.log('Data received', {
                    type: data.type,
                    from: participant?.identity,
                    data: data
                });
            }


            // Don't process messages from ourselves unless it's a test message
            if (participant?.sid === this.localParticipant?.sid && data.type !== 'testMessage') {
                if (window.debugMode) {
                }
                return;
            }

            // Validate required data fields
            if (!data.type) {
                return;
            }

            // Handle different data types
            switch (data.type) {
                case 'chat':
                    this.handleChatMessage(data, participant);
                    break;

                case 'handRaise':
                    this.handleHandRaiseEvent(data, participant);
                    break;

                case 'handRaiseSync':
                    if (data.action === 'request') {
                        this.handleHandRaiseSyncRequest(data, participant);
                    } else if (data.action === 'response') {
                        this.handleHandRaiseSyncResponse(data, participant);
                    }
                    break;

                case 'lower_hand':
                    this.handleLowerHandCommand(data, participant);
                    break;

                case 'clear_all_raised_hands':
                    this.handleClearAllRaisedHandsCommand(data, participant);
                    break;

                case 'audioPermission':
                    this.handleAudioPermissionEvent(data, participant);
                    break;

                case 'globalAudioControl':
                    this.handleGlobalAudioControlEvent(data, participant);
                    break;

                case 'testMessage':
                    break;

                default:
                    if (window.debugMode) {
                    }
                    break;
            }
        } catch (error) {
            console.error('Error handling data received', {
                data: data,
                participant: participant?.identity,
                participantSid: participant?.sid
            });
        }
    }

    /**
     * Handle chat message
     * @param {Object} data - Chat message data
     * @param {LiveKit.Participant} participant - Sender participant
     */
    handleChatMessage(data, participant) {

        // Don't show messages from self (they're already shown when sent)
        if (participant?.identity !== this.localParticipant?.identity) {
            this.addChatMessage(data.message, data.sender, false);
        } else {
        }
    }

    /**
     * Handle hand raise event
     * @param {Object} data - Hand raise data
     * @param {LiveKit.Participant} participant - Sender participant
     */
    handleHandRaiseEvent(data, participant) {

        // Don't process our own hand raise events
        if (participant?.identity === this.localParticipant?.identity) {
            return;
        }

        if (data.isRaised) {

            // Only teachers handle hand raise queue
            if (this.canControlStudentAudio()) {
                this.addToRaisedHandsQueue(data, participant);
            }

            // Update participant visual indicator
            this.updateParticipantHandRaiseIndicator(participant.identity, true);

        } else {

            // Remove from queue
            if (this.canControlStudentAudio()) {
                this.removeFromRaisedHandsQueue(participant.sid);
            }

            // Update participant visual indicator
            this.updateParticipantHandRaiseIndicator(participant.identity, false);
        }
    }

    /**
     * Handle lower hand command from teacher
     * @param {Object} data - Lower hand command data
     * @param {LiveKit.Participant} participant - Sender participant (teacher)
     */
    handleLowerHandCommand(data, participant) {

        // Check if this message is for me
        const myParticipantId = this.localParticipant?.identity;
        const myParticipantSid = this.localParticipant?.sid;

        if (data.targetParticipantId === myParticipantId || data.targetParticipantSid === myParticipantSid) {

            // Lower the hand
            this.isHandRaised = false;

            // Hide hand raise indicator
            this.createHandRaiseIndicatorDirect(myParticipantId, false);

            // Update control buttons
            this.updateControlButtons();

            // Show notification
            this.showNotification(t('hand_raise.teacher_dismissed_hand'), 'info');

        } else {
        }
    }

    /**
     * Handle clear all raised hands command from teacher
     * @param {Object} data - Clear all command data
     * @param {LiveKit.Participant} participant - Sender participant (teacher)
     */
    handleClearAllRaisedHandsCommand(data, participant) {

        // If I'm a student and my hand is raised, lower it
        if (!this.canControlStudentAudio() && this.isHandRaised) {

            // Lower the hand
            this.isHandRaised = false;

            // Hide hand raise indicator
            const myParticipantId = this.localParticipant?.identity;
            this.createHandRaiseIndicatorDirect(myParticipantId, false);

            // Clear local queue if it exists
            if (this.raisedHandsQueue) {
                this.raisedHandsQueue.clear();
            }

            // Update control buttons
            this.updateControlButtons();

            // Show notification
            this.showNotification(t('hand_raise.all_hands_cleared_by_teacher'), 'info');

        }
    }

    /**
     * Handle audio permission event
     * @param {Object} data - Audio permission data
     * @param {LiveKit.Participant} participant - Sender participant
     */
    handleAudioPermissionEvent(data, participant) {

        // Only process if this is meant for us or if we're a teacher observing
        const isForUs = data.targetParticipantSid === this.localParticipant?.sid;
        const isFromTeacher = this.getParticipantRole(participant?.identity) === 'teacher';

        if (isForUs && isFromTeacher) {

            if (data.action === 'grant') {
                // Student received permission to speak
                this.handleAudioPermissionGranted(data);
            } else if (data.action === 'revoke') {
                // Student's permission was revoked
                this.handleAudioPermissionRevoked(data);
            }
        } else if (this.canControlStudentAudio()) {
            // Teacher observing audio permission changes
        }
    }

    /**
     * Handle global audio control event
     * @param {Object} data - Global audio control data
     * @param {LiveKit.Participant} participant - Sender participant
     */
    handleGlobalAudioControlEvent(data, participant) {
        console.log('Global audio control event received', {
            action: data.action,
            settings: data.settings,
            controlledBy: data.controlledBy
        });

        // Only process if from a teacher (or self for immediate UI update)
        const isFromTeacher = this.getParticipantRole(participant?.identity) === 'teacher';
        const isFromSelf = participant?.sid === this.localParticipant?.sid;

        if (!isFromTeacher && !isFromSelf) {
            return;
        }

        // Store previous state for comparison
        const previousState = { ...this.globalAudioControlsState };

        // CRITICAL FIX: Mark that global state has been explicitly set
        this.globalStateExplicitlySet = true;

        // Update global audio control state
        this.globalAudioControlsState = {
            ...this.globalAudioControlsState,
            ...data.settings
        };

        console.log('Global audio control state updated', {
            previous: previousState,
            current: this.globalAudioControlsState,
            action: data.action
        });

        if (data.action === 'muteAll') {
            this.handleGlobalMuteAll(data);
        } else if (data.action === 'allowAll') {
            this.handleGlobalAllowAll(data);
        }

        // Update UI for all roles
        this.updateControlButtons();

        // Update teacher's toggle switch if we're a teacher
        if (this.canControlStudentAudio()) {
            this.updateGlobalAudioControlToggle();
        }

        // Debug logging
        if (window.debugMode) {
            console.log('Student unmute check', {
                canStudentUnmute: this.canStudentUnmute(),
                isAudioEnabled: this.isAudioEnabled,
                sdkAudioEnabled: this.localParticipant?.isMicrophoneEnabled
            });
        }
    }

    /**
     * Handle audio permission granted for this student
     * @param {Object} data - Permission data
     */
    async handleAudioPermissionGranted(data) {

        try {
            // Automatically unmute microphone
            if (!this.isAudioEnabled) {
                await this.localParticipant.setMicrophoneEnabled(true);
                this.isAudioEnabled = true;
                this.updateControlButtons();
            }

            // Lower hand automatically
            if (this.isHandRaised) {
                this.isHandRaised = false;
                this.updateControlButtons();
            }

            this.showNotification(`🎤 ${t('student_control.mic_permission_granted_by', {name: data.grantedBy})}`, 'success');

        } catch (error) {
        }
    }

    /**
     * Handle audio permission revoked for this student
     * @param {Object} data - Permission data
     */
    async handleAudioPermissionRevoked(data) {

        try {
            // Automatically mute microphone
            if (this.isAudioEnabled) {
                await this.localParticipant.setMicrophoneEnabled(false);
                this.isAudioEnabled = false;
                this.updateControlButtons();
            }

            this.showNotification(`🔇 ${t('student_control.mic_revoked_by', {name: data.revokedBy})}`, 'warning');

        } catch (error) {
        }
    }

    /**
     * Handle global mute all command
     * @param {Object} data - Global control data
     */
    async handleGlobalMuteAll(data) {
        if (this.userRole === 'student') {

            try {
                // Force mute the student's microphone via LiveKit SDK
                if (this.isAudioEnabled) {
                    await this.localParticipant.setMicrophoneEnabled(false);
                    this.isAudioEnabled = false;
                    this.updateControlButtons();
                }

                // CRITICAL FIX: Mark that global state has been explicitly set
                this.globalStateExplicitlySet = true;

                // Update global state
                this.globalAudioControlsState.allStudentsMuted = true;
                this.globalAudioControlsState.studentsCanSelfUnmute = false;
                this.globalAudioControlsState.teacherControlsAudio = true;


                this.showNotification(`🔇 ${t('student_control.all_muted_by', {name: data.controlledBy})}`, 'info');

            } catch (error) {
            }
        }
    }

    /**
     * Handle global allow all command
     * @param {Object} data - Global control data
     */
    async handleGlobalAllowAll(data) {
        if (this.userRole === 'student') {

            try {
                // CRITICAL FIX: Mark that global state has been explicitly set  
                this.globalStateExplicitlySet = true;

                // Update global state first so student permissions are correct
                this.globalAudioControlsState.allStudentsMuted = false;
                this.globalAudioControlsState.studentsCanSelfUnmute = true;
                this.globalAudioControlsState.teacherControlsAudio = false;


                // Optionally enable microphone for students when allowed (commented out to give choice)
                // if (!this.isAudioEnabled) {
                //     await this.localParticipant.setMicrophoneEnabled(true);
                //     this.isAudioEnabled = true;
                //     this.updateControlButtons();
                // }

                // Update button UI to reflect new permissions
                this.updateControlButtons();

                this.showNotification(`🔊 ${t('student_control.mic_allowed_by', {name: data.controlledBy})}`, 'success');

            } catch (error) {
            }
        }
    }

    // ===== VISUAL INDICATOR METHODS =====

    /**
     * Create/remove hand raise indicator directly (SIMPLE APPROACH)
     * @param {string} participantIdentity - Participant identity/ID
     * @param {boolean} isRaised - Whether hand is raised
     */
    createHandRaiseIndicatorDirect(participantIdentity, isRaised) {

        // Find participant element by identity
        const elementId = `participant-${participantIdentity}`;

        const participantElement = document.getElementById(elementId);

        if (!participantElement) {
            const allParticipants = document.querySelectorAll('[id^="participant-"]');
            return;
        }
        
        // Find or create hand raise indicator
        let handRaiseIndicator = document.getElementById(`hand-raise-${participantIdentity}`);
        
        if (isRaised) {
            // Show hand raise indicator
            if (!handRaiseIndicator) {
                // Create new indicator
                handRaiseIndicator = document.createElement('div');
                handRaiseIndicator.id = `hand-raise-${participantIdentity}`;
                handRaiseIndicator.style.cssText = `
                    position: absolute;
                    top: 8px;
                    right: 8px;
                    width: 32px;
                    height: 32px;
                    background: linear-gradient(135deg, #f59e0b, #d97706);
                    color: white;
                    border-radius: 50%;
                    display: flex;
                    align-items: center;
                    justify-content: center;
                    z-index: 50;
                    box-shadow: 0 4px 12px rgba(0, 0, 0, 0.4);
                    border: 2px solid white;
                    animation: handRaisePulse 2s ease-in-out infinite;
                    cursor: pointer;
                `;
                handRaiseIndicator.innerHTML = '<i class="fas fa-hand" style="font-size: 14px; filter: drop-shadow(0 1px 2px rgba(0, 0, 0, 0.3));"></i>';
                handRaiseIndicator.title = t('hand_raise.hand_raised');
                
                participantElement.appendChild(handRaiseIndicator);
                
            } else {
                // Show existing indicator
                handRaiseIndicator.style.display = 'flex';
                handRaiseIndicator.style.opacity = '1';
                handRaiseIndicator.style.transform = 'scale(1)';
                handRaiseIndicator.style.visibility = 'visible';
            }
        } else {
            // Hide hand raise indicator
            if (handRaiseIndicator) {
                handRaiseIndicator.style.opacity = '0';
                setTimeout(() => {
                    if (handRaiseIndicator && handRaiseIndicator.parentNode) {
                        handRaiseIndicator.remove();
                    }
                }, 300);
            }
        }
    }

    /**
     * Update participant hand raise visual indicator
     * @param {string} participantId - Participant identity
     * @param {boolean} isRaised - Whether hand is raised
     */
    updateParticipantHandRaiseIndicator(participantId, isRaised) {

        // Use direct hand raise indicator method (works reliably)
        this.createHandRaiseIndicatorDirect(participantId, isRaised);
    }

    /**
     * Request hand raise sync from all participants (teacher joins late)
     */
    async requestHandRaiseSync() {
        if (!this.canControlStudentAudio()) {
            return;
        }


        try {
            // Send sync request to all participants
            const data = {
                type: 'handRaiseSync',
                action: 'request',
                from: this.localParticipant.identity,
                timestamp: new Date().toISOString()
            };

            const encoder = new TextEncoder();
            const encodedData = encoder.encode(JSON.stringify(data));
            const dataKind = window.LiveKit.DataPacket_Kind?.RELIABLE || 1;

            await this.room.localParticipant.publishData(
                encodedData,
                dataKind,
                {
                    reliable: true,
                    destinationSids: [] // Broadcast to all participants
                }
            );


        } catch (error) {
        }
    }

    /**
     * Handle hand raise sync request (student responds with their status)
     * @param {Object} data - Sync request data
     * @param {LiveKit.Participant} participant - Teacher who requested sync
     */
    async handleHandRaiseSyncRequest(data, participant) {
        // Only students respond to sync requests
        if (this.userRole !== 'student') {
            return;
        }


        try {
            // Send current hand raise status to teacher
            const responseData = {
                type: 'handRaiseSync',
                action: 'response',
                isRaised: this.isHandRaised,
                participantId: this.localParticipant.identity,
                participantSid: this.localParticipant.sid,
                timestamp: new Date().toISOString(),
                timeRaised: this.isHandRaised ? Date.now() : null
            };

            const encoder = new TextEncoder();
            const encodedData = encoder.encode(JSON.stringify(responseData));
            const dataKind = window.LiveKit.DataPacket_Kind?.RELIABLE || 1;

            await this.room.localParticipant.publishData(
                encodedData,
                dataKind,
                {
                    reliable: true,
                    destinationSids: [participant.sid] // Send only to teacher
                }
            );


        } catch (error) {
        }
    }

    /**
     * Handle hand raise sync response (teacher receives student status)
     * @param {Object} data - Sync response data
     * @param {LiveKit.Participant} participant - Student who responded
     */
    handleHandRaiseSyncResponse(data, participant) {
        // Only teachers process sync responses
        if (!this.canControlStudentAudio()) {
            return;
        }


        if (data.isRaised) {
            // Add student to raised hands queue
            this.addToRaisedHandsQueue(data, participant);
            
            // Show hand raise indicator immediately
            this.createHandRaiseIndicatorDirect(data.participantId, true);
        }
    }

    /**
     * Update all participant hand raise indicators (useful for initialization)
     */
    updateAllParticipantHandRaiseIndicators() {

        // Update raised hands from queue
        this.raisedHandsQueue.forEach((handRaise, participantSid) => {
            this.updateParticipantHandRaiseIndicator(participantSid, true);
        });

        // Also check for local participant if hand is raised
        if (this.isHandRaised && this.localParticipant) {
            this.updateParticipantHandRaiseIndicator(this.localParticipant.sid, true);
        }

    }

    /**
     * Show floating notification for new hand raise
     * @param {string} studentName - Name of student who raised hand
     */
    showHandRaiseNotification(studentName) {
        // Only show for teachers
        if (!this.canControlStudentAudio()) {
            return;
        }


        // Create floating notification element
        const notification = document.createElement('div');
        notification.className = 'hand-raise-notification fixed top-4 left-1/2 transform -translate-x-1/2 bg-orange-500 text-white px-6 py-3 rounded-lg shadow-lg z-50 transition-all duration-300';
        notification.innerHTML = `
            <div class="flex items-center gap-3">
                <div class="w-6 h-6 bg-white bg-opacity-20 rounded-full flex items-center justify-center">
                    <i class="fa-solid fa-hand text-sm"></i>
                </div>
                <span class="font-medium">${t('hand_raise.hand_raised_notification', {name: studentName})}</span>
                <button onclick="this.remove()" class="text-white hover:text-gray-200 ml-2">
                    <i class="fa-solid fa-times"></i>
                </button>
            </div>
        `;

        // Add to page
        document.body.appendChild(notification);

        // Auto-remove after 5 seconds
        setTimeout(() => {
            if (notification.parentNode) {
                notification.style.opacity = '0';
                notification.style.transform = 'translateX(-50%) translateY(-20px)';
                setTimeout(() => {
                    notification.remove();
                }, 300);
            }
        }, 5000);

        // Play sound if available
        this.playHandRaiseSound();
    }

    /**
     * Play hand raise notification sound
     */
    playHandRaiseSound() {
        // Only for teachers
        if (!this.canControlStudentAudio()) {
            return;
        }

        try {
            // Create a gentle notification sound
            const audioContext = new (window.AudioContext || window.webkitAudioContext)();
            const oscillator = audioContext.createOscillator();
            const gainNode = audioContext.createGain();

            oscillator.connect(gainNode);
            gainNode.connect(audioContext.destination);

            oscillator.type = 'sine';
            oscillator.frequency.setValueAtTime(800, audioContext.currentTime);
            oscillator.frequency.setValueAtTime(1000, audioContext.currentTime + 0.1);

            gainNode.gain.setValueAtTime(0, audioContext.currentTime);
            gainNode.gain.linearRampToValueAtTime(0.1, audioContext.currentTime + 0.05);
            gainNode.gain.linearRampToValueAtTime(0, audioContext.currentTime + 0.2);

            oscillator.start(audioContext.currentTime);
            oscillator.stop(audioContext.currentTime + 0.2);

        } catch (error) {
        }
    }

    /**
     * Add visual effect for successful permission grant
     * @param {string} participantSid - Participant SID
     */
    showPermissionGrantedEffect(participantSid) {
        const participantVideo = document.querySelector(`[data-participant-sid="${participantSid}"]`);
        if (!participantVideo) {
            return;
        }

        // Create success effect overlay
        const effect = document.createElement('div');
        effect.className = 'permission-granted-effect absolute inset-0 bg-green-500 bg-opacity-20 flex items-center justify-center z-20 animate-pulse';
        effect.innerHTML = `
            <div class="bg-green-500 text-white rounded-full w-16 h-16 flex items-center justify-center shadow-lg">
                <i class="fa-solid fa-check text-2xl"></i>
            </div>
        `;

        const videoContainer = participantVideo.closest('.participant-video-container') || participantVideo;
        videoContainer.appendChild(effect);

        // Remove effect after animation
        setTimeout(() => {
            effect.style.opacity = '0';
            setTimeout(() => {
                effect.remove();
            }, 300);
        }, 2000);

    }

    /**
     * Update all participant microphone status icons
     * @param {boolean} muted - Whether microphones are muted
     */
    updateAllParticipantMicIcons(muted) {
        if (!this.room) {
            return;
        }


        // Get all remote participants
        const participants = Array.from(this.room.remoteParticipants.values());

        participants.forEach(participant => {
            const participantId = participant.identity;
            const micStatus = document.getElementById(`mic-status-${participantId}`);

            if (micStatus) {
                const icon = micStatus.querySelector('i');
                if (muted) {
                    // Show as muted/disabled
                    micStatus.className = 'text-red-500';
                    if (icon) icon.className = 'ri-mic-off-line text-sm';
                } else {
                    // Check actual track state
                    const audioPublication = participant.getTrackPublication(window.LiveKit.Track.Source.Microphone);
                    const hasActiveAudio = audioPublication && !audioPublication.isMuted && audioPublication.track;

                    if (hasActiveAudio) {
                        micStatus.className = 'text-green-500';
                        if (icon) icon.className = 'ri-mic-line text-sm';
                    } else {
                        micStatus.className = 'text-red-500';
                        if (icon) icon.className = 'ri-mic-off-line text-sm';
                    }
                }
            }
        });
    }

    /**
     * Update all participant camera status icons
     * @param {boolean} disabled - Whether cameras are disabled
     */
    updateAllParticipantCameraIcons(disabled) {
        if (!this.room) {
            return;
        }


        // Get all remote participants
        const participants = Array.from(this.room.remoteParticipants.values());

        participants.forEach(participant => {
            const participantId = participant.identity;
            const cameraStatus = document.getElementById(`camera-status-${participantId}`);

            if (cameraStatus) {
                const icon = cameraStatus.querySelector('i');
                if (disabled) {
                    // Show as disabled
                    cameraStatus.className = 'text-red-500';
                    if (icon) icon.className = 'ri-video-off-line text-sm';
                } else {
                    // Check actual track state
                    const videoPublication = participant.getTrackPublication(window.LiveKit.Track.Source.Camera);
                    const hasActiveVideo = videoPublication && !videoPublication.isMuted && videoPublication.track;

                    if (hasActiveVideo) {
                        cameraStatus.className = 'text-green-500';
                        if (icon) icon.className = 'ri-video-line text-sm';
                    } else {
                        cameraStatus.className = 'text-red-500';
                        if (icon) icon.className = 'ri-video-off-line text-sm';
                    }
                }
            }
        });
    }

    /**
     * Stop permission polling (for cleanup)
     */
    stopPermissionPolling() {
        if (this.permissionPollingInterval) {
            clearInterval(this.permissionPollingInterval);
            this.permissionPollingInterval = null;
        }
    }

    /**
     * Destroy controls manager and clean up
     */
    destroy() {

        // Stop timer
        this.stopMeetingTimer();

        // Stop permission polling (prevents memory leak)
        this.stopPermissionPolling();

        // Close any open sidebars
        this.closeSidebar();

        // Remove event listeners would be handled by the parent

        // Clear references
        this.room = null;
        this.localParticipant = null;

    }
}

// Make class globally available
window.LiveKitControls = LiveKitControls;
