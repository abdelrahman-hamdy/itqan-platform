/**
 * LiveKit Controls Module
 * Handles UI control interactions (mic, camera, screen share, etc.) using proper SDK methods
 * VERSION: 2025-11-16-FIX-v6 - Fixed clearAllRaisedHands room reference & added debugging
 */

console.log('🔧 CONTROLS.JS VERSION: 2025-11-16-FIX-v6 - CLEAR ALL FIX & DEBUG - Loading...');

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

        console.log('👋 Hand-raising system initialized:', {
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

        console.log('🎮 LiveKitControls initialized');
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
            console.log('🔄 Syncing control states with SDK...');
            this.isAudioEnabled = this.localParticipant.isMicrophoneEnabled;
            this.isVideoEnabled = this.localParticipant.isCameraEnabled;
            console.log(`📊 Control states synced - Audio: ${this.isAudioEnabled}, Video: ${this.isVideoEnabled}`);
        }
    }

    /**
     * Fetch room permissions from server and enforce them (students only)
     */
    async fetchAndEnforceRoomPermissions() {
        try {
            // Use actual LiveKit room name from connected room
            const roomName = this.room?.name || this.config?.meetingConfig?.roomName || `session-${window.sessionId}`;

            console.log('🔐 Fetching room permissions for students...', { roomName });

            const response = await fetch(`/livekit/rooms/permissions?room_name=${encodeURIComponent(roomName)}`, {
                method: 'GET',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '',
                    'Accept': 'application/json'
                },
                credentials: 'same-origin',
            });

            if (!response.ok) {
                throw new Error('Failed to fetch room permissions');
            }

            const result = await response.json();
            const permissions = result.permissions || {};

            console.log('✅ Room permissions received:', permissions);

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
            console.error('❌ Failed to fetch room permissions:', error);
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

            console.log('🔐 Fetching room permissions for teacher initialization...', { roomName });

            const response = await fetch(`/livekit/rooms/permissions?room_name=${encodeURIComponent(roomName)}`, {
                method: 'GET',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '',
                    'Accept': 'application/json'
                },
                credentials: 'same-origin',
            });

            if (!response.ok) {
                throw new Error('Failed to fetch room permissions');
            }

            const result = await response.json();
            const permissions = result.permissions || {};

            console.log('✅ Teacher permissions received:', permissions);

            // Set toggle switches based on current permissions
            const micSwitch = document.getElementById('toggleAllStudentsMicSwitch');
            const cameraSwitch = document.getElementById('toggleAllStudentsCameraSwitch');

            if (micSwitch) {
                micSwitch.checked = permissions.microphone_allowed !== false;
                console.log('🎤 Mic toggle initialized:', micSwitch.checked ? 'ALLOWED' : 'MUTED');
            }

            if (cameraSwitch) {
                cameraSwitch.checked = permissions.camera_allowed !== false;
                console.log('📹 Camera toggle initialized:', cameraSwitch.checked ? 'ALLOWED' : 'DISABLED');
            }

            // Now sync the internal state from the correctly initialized toggles
            this.syncGlobalAudioStateFromToggle();
            this.updateGlobalAudioControlToggle();

        } catch (error) {
            console.error('❌ Failed to fetch teacher permissions:', error);
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
            console.log('🚫 Microphone permission disabled by teacher');

            if (micButton) {
                micButton.disabled = true;
                micButton.classList.add('opacity-50', 'cursor-not-allowed');
                micButton.title = 'المعلم لم يسمح بإستخدام الميكروفون';
            }

            // Ensure mic is muted if permission disabled
            if (this.isAudioEnabled && this.localParticipant) {
                this.toggleMicrophone(); // This will mute it
            }
        } else {
            if (micButton) {
                micButton.disabled = false;
                micButton.classList.remove('opacity-50', 'cursor-not-allowed');
                micButton.title = 'تشغيل/إيقاف الميكروفون';
            }
        }

        // Camera permission
        if (!this.roomPermissions.cameraAllowed) {
            console.log('🚫 Camera permission disabled by teacher');

            if (cameraButton) {
                cameraButton.disabled = true;
                cameraButton.classList.add('opacity-50', 'cursor-not-allowed');
                cameraButton.title = 'المعلم لم يسمح بإستخدام الكاميرا';
            }

            // Ensure camera is off if permission disabled
            if (this.isVideoEnabled && this.localParticipant) {
                this.toggleCamera(); // This will turn it off
            }
        } else {
            if (cameraButton) {
                cameraButton.disabled = false;
                cameraButton.classList.remove('opacity-50', 'cursor-not-allowed');
                cameraButton.title = 'تشغيل/إيقاف الكاميرا';
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

        console.log('🔄 Permission polling started (every 5 seconds)');
    }

    /**
     * Set up control button event listeners
     */
    setupControlButtons() {
        console.log('🎮 Setting up control buttons...');

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
                console.log('✋ Hand raise button clicked!');
                this.toggleHandRaise();
            });
        } else {
            console.warn('⚠️ Hand raise button not found');
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
            console.warn('⚠️ Leave meeting button not found');
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

        console.log('✅ Control buttons set up successfully');
        
        // Debug: Check if hand raise button exists
        const handRaiseBtn = document.getElementById('toggleHandRaise');
        if (handRaiseBtn) {
            console.log('✅ Hand raise button found:', handRaiseBtn);
            console.log('   - Text content:', handRaiseBtn.textContent);
            console.log('   - Classes:', handRaiseBtn.className);
            console.log('   - Visible:', handRaiseBtn.offsetParent !== null);
        } else {
            console.error('❌ Hand raise button NOT found in DOM');
            // List all buttons to help debug
            const allButtons = document.querySelectorAll('button');
            console.log('🔍 All buttons found:', allButtons);
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

        console.log('⌨️ Keyboard shortcuts set up');
    }

    /**
     * Toggle microphone on/off
     */
    async toggleMicrophone() {
        console.log('🎤 Toggling microphone...');

        if (!this.localParticipant) {
            console.warn('⚠️ No local participant available');
            this.showNotification('خطأ: لم يتم الاتصال بالجلسة بعد', 'error');
            return;
        }

        try {
            // Get current state from SDK first
            const currentState = this.localParticipant.isMicrophoneEnabled;
            const newState = !currentState;

            console.log(`🎤 Microphone: ${currentState} -> ${newState}`);

            // Check audio permissions for students (teachers have full control)
            if (this.userRole === 'student' && newState) {
                // Student trying to unmute - check permissions with enhanced validation
                if (!this.canStudentUnmute()) {
                    this.showPermissionDeniedNotification();
                    console.log('❌ Student microphone unmute blocked by teacher restrictions');
                    return;
                }

                // TRIPLE SAFETY CHECK: Prevent any bypass attempts
                if (this.globalAudioControlsState.allStudentsMuted === true) {
                    this.showPermissionDeniedNotification();
                    console.log('❌ SAFETY: Student unmute blocked - all students muted');
                    return;
                }

                if (this.globalAudioControlsState.studentsCanSelfUnmute === false) {
                    this.showPermissionDeniedNotification();
                    console.log('❌ SAFETY: Student unmute blocked - self unmute disabled');
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
                console.log('✅ Microphone enabled with audio optimization (32kbps, DTX)');
            } else {
                await this.localParticipant.setMicrophoneEnabled(false);
                console.log('✅ Microphone disabled');
            }

            // Update our internal state to match the SDK
            this.isAudioEnabled = this.localParticipant.isMicrophoneEnabled;

            // Update individual audio permission state for students
            if (this.userRole === 'student') {
                this.updateStudentAudioPermissionState();
            }

            // Update UI
            this.updateControlButtons();

            const status = this.isAudioEnabled ? 'مفعل' : 'معطل';
            this.showNotification(`الميكروفون: ${status}`, 'success');

            // Notify state change
            this.notifyControlStateChange('microphone', this.isAudioEnabled);

            console.log('✅ Microphone toggled to:', this.isAudioEnabled);
        } catch (error) {
            console.error('❌ Failed to toggle microphone:', error);
            this.showNotification('خطأ في التحكم بالميكروفون', 'error');
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
        console.log('🔍 Checking student unmute permission:', {
            allStudentsMuted: this.globalAudioControlsState.allStudentsMuted,
            studentsCanSelfUnmute: this.globalAudioControlsState.studentsCanSelfUnmute,
            teacherControlsAudio: this.globalAudioControlsState.teacherControlsAudio,
            globalStateExplicitlySet: this.globalStateExplicitlySet
        });

        // CRITICAL FIX: Always enforce teacher restrictions when they exist
        // Check if teacher has disabled microphones for all students
        if (this.globalAudioControlsState.allStudentsMuted === true) {
            console.log('🔇 Student cannot unmute: teacher has muted all students');
            return false;
        }

        // Check if teacher has disabled student self-unmute capability
        if (this.globalAudioControlsState.studentsCanSelfUnmute === false) {
            console.log('🔇 Student cannot unmute: teacher controls audio');
            return false;
        }

        // Check individual permissions (only if participant is available)
        if (this.localParticipant) {
            const participantSid = this.localParticipant.sid;
            const individualPermission = this.studentAudioPermissions.get(participantSid);

            if (individualPermission && individualPermission.canSpeak === false) {
                console.log('🔇 Student cannot unmute: individual permission denied');
                return false;
            }
        }

        // IMPORTANT: Only allow unmute if no restrictions are in place
        console.log('✅ Student can unmute: no active restrictions');
        return true;
    }

    /**
     * Show permission denied notification with context
     */
    showPermissionDeniedNotification() {
        let message = 'غير مسموح لك بتفعيل الميكروفون';

        if (this.globalAudioControlsState.allStudentsMuted) {
            message = 'المعلم قام بكتم جميع الطلاب - انتظر الإذن';
        } else if (!this.globalAudioControlsState.studentsCanSelfUnmute) {
            message = 'المعلم يتحكم في صلاحيات الصوت - ارفع يدك للحصول على الإذن';
        }

        this.showNotification(message, 'error');
        console.log('🚫 Audio permission denied for student');
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

        console.log('🎤 Updated student audio state:', {
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
            console.log('🎤 Auto-unmuting student with granted permission');

            // Enable microphone automatically
            await this.localParticipant.setMicrophoneEnabled(true);

            // Update internal state
            this.isAudioEnabled = true;
            this.updateControlButtons();

            // Update permission state
            this.updateStudentAudioPermissionState();

            this.showNotification('✅ تم منحك إذن التحدث - الميكروفون مفعل الآن', 'success');

            console.log('✅ Student auto-unmuted successfully');

        } catch (error) {
            console.error('❌ Failed to auto-unmute student:', error);
            this.showNotification('خطأ في تفعيل الميكروفون تلقائياً', 'error');
        }
    }

    /**
     * Toggle camera on/off
     */
    async toggleCamera() {
        console.log('📹 Toggling camera...');

        if (!this.localParticipant) {
            console.warn('⚠️ No local participant available');
            this.showNotification('خطأ: لم يتم الاتصال بالجلسة بعد', 'error');
            return;
        }

        try {
            // Get current state from SDK first
            const currentState = this.localParticipant.isCameraEnabled;
            const newState = !currentState;

            console.log(`📹 Camera: ${currentState} -> ${newState}`);

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
                    console.log('📹 Using high quality profile (720p@30fps) for small session');
                } else if (participantCount <= 10) {
                    // Medium groups: Balanced quality
                    videoOptions = {
                        resolution: window.LiveKit.VideoPresets.h540.resolution,  // 960×540
                        frameRate: 24,
                        maxBitrate: 800000  // 0.8 Mbps
                    };
                    console.log('📹 Using medium quality profile (540p@24fps) for medium group');
                } else {
                    // Large groups: Optimize for bandwidth
                    videoOptions = {
                        resolution: window.LiveKit.VideoPresets.h360.resolution,  // 640×360
                        frameRate: 20,
                        maxBitrate: 500000  // 0.5 Mbps
                    };
                    console.log('📹 Using low quality profile (360p@20fps) for large group');
                }

                // Enable camera with optimized quality settings
                await this.localParticipant.setCameraEnabled(true, videoOptions);
                console.log('✅ Camera enabled with optimized quality settings:', videoOptions);
            } else {
                // Disable camera
                await this.localParticipant.setCameraEnabled(false);
                console.log('✅ Camera disabled');
            }

            // Update our internal state to match the SDK
            this.isVideoEnabled = this.localParticipant.isCameraEnabled;

            // Update UI
            this.updateControlButtons();

            const status = this.isVideoEnabled ? 'مفعلة' : 'معطلة';
            this.showNotification(`الكاميرا: ${status}`, 'success');

            // Notify state change
            this.notifyControlStateChange('camera', this.isVideoEnabled);

            console.log('✅ Camera toggled to:', this.isVideoEnabled);
        } catch (error) {
            console.error('❌ Failed to toggle camera:', error);
            this.showNotification('خطأ في التحكم بالكاميرا', 'error');
            // Reset state to match SDK
            this.isVideoEnabled = this.localParticipant.isCameraEnabled;
            this.updateControlButtons();
        }
    }

    /**
     * Toggle screen sharing on/off
     */
    async toggleScreenShare() {
        console.log('🖥️ Toggling screen share...');

        if (!this.localParticipant) {
            console.warn('⚠️ No local participant available');
            this.showNotification('خطأ: لم يتم الاتصال بالجلسة بعد', 'error');
            return;
        }

        try {
            const currentState = this.isScreenSharing;
            const newState = !currentState;

            console.log(`🖥️ Screen share: ${currentState} -> ${newState}`);

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

            const status = this.isScreenSharing ? 'مفعلة' : 'معطلة';
            this.showNotification(`مشاركة الشاشة: ${status}`, 'success');

            // Notify state change
            this.notifyControlStateChange('screenShare', this.isScreenSharing);

            console.log('✅ Screen share toggled:', this.isScreenSharing);
        } catch (error) {
            console.error('❌ Failed to toggle screen share:', error);
            this.showNotification('خطأ في مشاركة الشاشة', 'error');

            // Handle specific error cases
            if (error.name === 'NotAllowedError') {
                this.showNotification('تم رفض إذن مشاركة الشاشة', 'error');
            } else if (error.name === 'NotSupportedError') {
                this.showNotification('مشاركة الشاشة غير مدعومة في هذا المتصفح', 'error');
            }
        }
    }

    /**
     * Start screen sharing
     */
    async startScreenShare() {
        console.log('🖥️ Starting screen share...');

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

            console.log('🖥️ Screen share stream acquired:', stream.getTracks());

            // Handle stream end event (when user stops sharing via browser UI)
            stream.getVideoTracks()[0].addEventListener('ended', () => {
                console.log('🖥️ Screen share ended by user via browser');
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

            console.log('✅ Screen share video track published with optimization (500kbps, 5fps)');

            // Publish audio track if available
            if (audioTracks.length > 0) {
                await this.localParticipant.publishTrack(audioTracks[0], {
                    name: 'screen_share_audio',
                    source: window.LiveKit.Track.Source.ScreenShareAudio
                });
                console.log('✅ Screen share audio track published');
            }

        } catch (error) {
            console.error('❌ Failed to start screen share:', error);
            throw error;
        }
    }

    /**
     * Stop screen sharing
     */
    async stopScreenShare() {
        console.log('🖥️ Stopping screen share...');

        try {
            // Unpublish screen share tracks
            const publications = this.localParticipant.trackPublications;

            for (const [trackSid, publication] of publications) {
                if (publication.source === window.LiveKit.Track.Source.ScreenShare ||
                    publication.source === window.LiveKit.Track.Source.ScreenShareAudio) {

                    console.log(`🖥️ Unpublishing screen share track: ${publication.trackName}`);
                    await this.localParticipant.unpublishTrack(publication.track);
                }
            }

            console.log('✅ Screen share tracks unpublished');
        } catch (error) {
            console.error('❌ Failed to stop screen share:', error);
            throw error;
        }
    }

    /**
     * Handle screen share ended (by user via browser controls)
     */
    handleScreenShareEnded() {
        console.log('🖥️ Handling screen share ended');

        // Update internal state
        this.isScreenSharing = false;

        // Update UI
        this.updateControlButtons();

        // Show notification
        this.showNotification('تم إيقاف مشاركة الشاشة', 'info');

        // Notify state change
        this.notifyControlStateChange('screenShare', false);
    }

    /**
     * Toggle hand raise (role-based behavior)
     */
    async toggleHandRaise() {
        console.log('✋ Toggling hand raise...');

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

        console.warn('⚠️ Unknown user role for hand raise:', this.userRole);
    }

    /**
     * Toggle raised hands sidebar (teachers only)
     */
    toggleRaisedHandsSidebar() {
        if (!this.canControlStudentAudio()) {
            this.showNotification('غير مسموح لك بإدارة الأيدي المرفوعة', 'error');
            return;
        }

        console.log('👋 Teacher toggling raised hands sidebar...');

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
            this.showNotification('غير مسموح لك برفع اليد', 'error');
            return;
        }

        try {
            console.log('👋 Student toggling hand raise state...');
            console.log(`👋 Current state BEFORE toggle: ${this.isHandRaised}`);

            this.isHandRaised = !this.isHandRaised;

            console.log(`👋 New state AFTER toggle: ${this.isHandRaised}`);

            // Send hand raise state via data channel with enhanced data
            const data = {
                type: 'handRaise',
                isRaised: this.isHandRaised,
                participantId: this.localParticipant.identity,
                participantSid: this.localParticipant.sid,
                timestamp: new Date().toISOString(),
                timeRaised: this.isHandRaised ? Date.now() : null
            };

            console.log('👋 Publishing hand raise data:', data);

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
            console.log(`✋ IMMEDIATE: Creating hand raise indicator for current user: ${this.isHandRaised}`);
            this.createHandRaiseIndicatorDirect(this.localParticipant.identity, this.isHandRaised);

            // Update local UI
            this.updateControlButtons();

            const status = this.isHandRaised ? 'مرفوعة' : 'مخفضة';
            this.showNotification(`اليد: ${status}`, 'success');

            // Notify state change
            this.notifyControlStateChange('handRaise', this.isHandRaised);

            console.log('✅ Hand raise toggled successfully:', this.isHandRaised);

        } catch (error) {
            console.error('❌ Failed to toggle hand raise:', error);
            this.showNotification('خطأ في رفع اليد', 'error');
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
            console.log('👋 Not a teacher, ignoring raised hand queue update');
            return;
        }

        const participantSid = participant.sid;
        const participantIdentity = participant.identity;

        console.log(`👋 Adding ${participantIdentity} to raised hands queue`);

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
        console.log(`✋ IMMEDIATE: Creating hand raise indicator for student ${participantIdentity}`);
        this.createHandRaiseIndicatorDirect(participantIdentity, true);

        // Update UI
        this.updateRaisedHandsUI();
        this.updateRaisedHandsNotificationBadge();

        // Show floating notification for teacher
        this.showHandRaiseNotification(participantIdentity);

        // Show notification for teacher
        this.showNotification(`👋 ${participantIdentity} رفع يده`, 'info');
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
            console.log(`👋 Removing ${handRaise.identity} from raised hands queue`);

            // ✅ IMMEDIATE: Hide hand raise indicator for this student - SIMPLE DIRECT APPROACH
            console.log(`✋ IMMEDIATE: Removing hand raise indicator for student ${handRaise.identity}`);
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

                console.log(`✅ Sent lower hand message to ${handRaise.identity}`);
            } catch (error) {
                console.error('❌ Failed to send lower hand message:', error);
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
            this.showNotification('غير مسموح لك بإدارة صلاحيات الصوت', 'error');
            return;
        }

        const handRaise = this.raisedHandsQueue.get(participantSid);
        if (!handRaise) {
            console.warn('👋 Participant not found in raised hands queue:', participantSid);
            return;
        }

        try {
            console.log(`🎤 Granting audio permission to ${handRaise.identity}`);

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

            console.log('🎤 Publishing audio permission data:', data);

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
            this.showNotification(`✅ تم منح ${handRaise.identity} إذن التحدث`, 'success');

            // Show visual effect on participant video
            this.showPermissionGrantedEffect(participantSid);

            // Remove from queue after a delay (hand automatically lowered)
            setTimeout(() => {
                this.removeFromRaisedHandsQueue(participantSid);
            }, 1000);

            console.log('✅ Audio permission granted successfully');

        } catch (error) {
            console.error('❌ Failed to grant audio permission:', error);
            this.showNotification('خطأ في منح إذن التحدث', 'error');
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
            console.warn('👋 Raised hands UI elements not found');
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

        console.log(`👋 Updated raised hands UI: ${raisedHands.length} hands`);
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
                <span class="text-orange-400 text-xs">✋ يد مرفوعة</span>
            </div>
            <div class="flex gap-2">
                <button onclick="window.meeting?.controls?.removeFromRaisedHandsQueue('${handRaise.sid}')" 
                        class="flex-1 bg-gray-600 hover:bg-gray-700 text-white px-3 py-1 rounded text-xs transition-colors">
                    ✓ إخفاء اليد
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
            console.warn('⚠️ Room not available for clearing raised hands');
            return;
        }

        try {
            const raisedHandsArray = Array.from(this.raisedHandsQueue.values());

            if (raisedHandsArray.length === 0) {
                console.log('ℹ️ No raised hands to clear');
                return;
            }

            console.log(`🧹 Clearing ${raisedHandsArray.length} raised hands`);

            // Hide all hand raise indicators immediately (teacher side)
            raisedHandsArray.forEach(handRaise => {
                console.log(`✋ Hiding hand raise indicator for ${handRaise.identity}`);
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
            this.showNotification('تم إخفاء جميع الأيدي المرفوعة بنجاح', 'success');

            console.log('✅ All raised hands cleared successfully');
        } catch (error) {
            console.error('❌ Error clearing all raised hands:', error);
            this.showNotification('حدث خطأ أثناء إخفاء الأيدي المرفوعة', 'error');
        }
    }

    /**
     * Handle clear all hand raises from teacher (via data channel)
     * @param {Object} data - Message data
     */
    handleClearAllHandRaises(data) {
        console.log('✋ Handling clear all hand raises from teacher:', data);

        // If student, lower their hand
        if (this.userRole === 'student' && this.isHandRaised) {
            console.log('✋ Lowering my hand (student)');
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

        console.log('✅ All raised hands cleared by teacher');
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
            return `قبل ${minutes} دقيقة`;
        } else {
            return `قبل ${seconds} ثانية`;
        }
    }

    // ===== GLOBAL AUDIO CONTROL METHODS =====

    /**
     * Toggle all students' microphones on/off using server-side API (teacher only)
     */
    async toggleAllStudentsMicrophones() {
        if (!this.canControlStudentAudio()) {
            this.showNotification('غير مسموح لك بإدارة صلاحيات الصوت', 'error');
            return;
        }

        try {
            // Get the switch state
            const toggleSwitch = document.getElementById('toggleAllStudentsMicSwitch');
            const newAllowedState = toggleSwitch ? toggleSwitch.checked : false;
            const newMutedState = !newAllowedState; // Inverted logic: checked = allowed, unchecked = muted

            console.log(`🎤 Teacher toggling all students microphones: ${newAllowedState ? 'ALLOWED' : 'MUTED'}`);

            // Update global state
            this.globalAudioControlsState.allStudentsMuted = newMutedState;
            this.globalAudioControlsState.teacherControlsAudio = newMutedState;
            this.globalAudioControlsState.studentsCanSelfUnmute = newAllowedState;
            this.globalStateExplicitlySet = true;

            // Use actual LiveKit room name from connected room
            const roomName = this.room?.name || this.config?.meetingConfig?.roomName || `session-${window.sessionId}`;

            // Debug logging
            console.log('🔍 Mic Toggle Debug:', {
                hasRoom: !!this.room,
                roomName: roomName,
                roomObject: this.room?.name,
                configName: this.config?.meetingConfig?.roomName,
                fallback: `session-${window.sessionId}`,
                muted: newMutedState
            });

            // Call server-side API to mute all students
            const response = await fetch('/livekit/participants/mute-all-students', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '',
                    'Accept': 'application/json'
                },
                credentials: 'same-origin', // Include session cookies for authentication
                body: JSON.stringify({
                    room_name: roomName,
                    muted: newMutedState
                })
            });

            if (!response.ok) {
                const errorData = await response.json();
                throw new Error(errorData.error || 'Server request failed');
            }

            const result = await response.json();
            
            // Update UI toggle
            this.updateGlobalAudioControlToggle();

            const status = newMutedState ? 'تم كتم جميع الطلاب' : 'تم السماح للطلاب بإستخدام الميكروفون';
            this.showNotification(`✅ ${status}`, 'success');

            console.log(`✅ All students microphones toggled successfully via API:`, result);

            // Update all participant mic status icons immediately
            this.updateAllParticipantMicIcons(newMutedState);

            // Notify UI of state change
            this.notifyControlStateChange('globalAudioControl', {
                muted: newMutedState,
                allowed: newAllowedState,
                affectedParticipants: result.affected_participants
            });

        } catch (error) {
            console.error('❌ Failed to toggle students microphones:', error);
            this.showNotification('خطأ في إدارة ميكروفونات الطلاب: ' + error.message, 'error');

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
            this.showNotification('غير مسموح لك بإدارة صلاحيات الكاميرا', 'error');
            return;
        }

        try {
            // Get the switch state
            const toggleSwitch = document.getElementById('toggleAllStudentsCameraSwitch');
            const newAllowedState = toggleSwitch ? toggleSwitch.checked : false;
            const newDisabledState = !newAllowedState; // Inverted logic: checked = allowed, unchecked = disabled

            console.log(`📹 Teacher toggling all students cameras: ${newAllowedState ? 'ALLOWED' : 'DISABLED'}`);

            // Use actual LiveKit room name from connected room
            const roomName = this.room?.name || this.config?.meetingConfig?.roomName || `session-${window.sessionId}`;

            // Debug logging
            console.log('🔍 Camera Toggle Debug:', {
                hasRoom: !!this.room,
                roomName: roomName,
                roomObject: this.room?.name,
                configName: this.config?.meetingConfig?.roomName,
                fallback: `session-${window.sessionId}`,
                disabled: !newAllowedState
            });

            // Call server-side API to disable/enable all students cameras
            const response = await fetch('/livekit/participants/disable-all-students-camera', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '',
                    'Accept': 'application/json'
                },
                credentials: 'same-origin', // Include session cookies for authentication
                body: JSON.stringify({
                    room_name: roomName,
                    disabled: newDisabledState
                })
            });

            if (!response.ok) {
                const errorData = await response.json();
                throw new Error(errorData.error || 'Server request failed');
            }

            const result = await response.json();

            const status = newDisabledState ? 'تم تعطيل كاميرات جميع الطلاب' : 'تم السماح للطلاب بإستخدام الكاميرا';
            this.showNotification(`✅ ${status}`, 'success');

            console.log(`✅ All students cameras toggled successfully via API:`, result);

            // Update all participant camera status icons immediately
            this.updateAllParticipantCameraIcons(newDisabledState);

            // Notify UI of state change
            this.notifyControlStateChange('globalCameraControl', {
                disabled: newDisabledState,
                allowed: newAllowedState,
                affectedParticipants: result.affected_participants
            });

        } catch (error) {
            console.error('❌ Failed to toggle students cameras:', error);
            this.showNotification('خطأ في إدارة كاميرات الطلاب: ' + error.message, 'error');

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

            console.log(`🎛️ Synced global audio state from toggle: ${isAllowed ? 'ALLOWED' : 'MUTED'}`, this.globalAudioControlsState);
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
            console.log(`🎛️ Updated toggle switch: ${shouldBeChecked ? 'ALLOWED' : 'MUTED'} (allStudentsMuted: ${this.globalAudioControlsState.allStudentsMuted})`);
        } else {
            console.log('🎛️ Toggle switch not found (likely student or not in raised hands panel)');
        }
    }

    /**
     * Toggle chat sidebar
     */
    toggleChat() {
        console.log('💬 Toggling chat...');
        this.toggleSidebar('chat');
    }

    /**
     * Toggle participants list sidebar
     */
    toggleParticipantsList() {
        console.log('👥 Toggling participants list...');
        this.toggleSidebar('participants');
    }

    /**
     * Toggle settings sidebar
     */
    toggleSettings() {
        console.log('⚙️ Toggling settings...');
        this.toggleSidebar('settings');
    }

    /**
     * Toggle recording (teacher only)
     */
    async toggleRecording() {
        if (!this.isTeacher()) {
            this.showNotification('غير مسموح لك بالتسجيل', 'error');
            return;
        }

        console.log('📹 Toggling recording...');

        try {
            this.isRecording = !this.isRecording;

            if (this.isRecording) {
                await this.startRecording();
            } else {
                await this.stopRecording();
            }

            // Update UI
            this.updateControlButtons();

            const status = this.isRecording ? 'بدأ' : 'توقف';
            this.showNotification(`التسجيل: ${status}`, 'success');

            // Notify state change
            this.notifyControlStateChange('recording', this.isRecording);

            console.log('✅ Recording toggled:', this.isRecording);
        } catch (error) {
            console.error('❌ Failed to toggle recording:', error);
            this.showNotification('خطأ في التسجيل', 'error');
            // Revert state on error
            this.isRecording = !this.isRecording;
        }
    }

    /**
     * Start recording
     */
    async startRecording() {
        console.log('📹 Starting recording...');
        // Implementation would depend on your recording setup
        // This could involve calling a server endpoint to start recording
    }

    /**
     * Stop recording
     */
    async stopRecording() {
        console.log('⏹️ Stopping recording...');
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
        console.log(`📋 Opening ${type} sidebar`);

        const sidebar = document.getElementById('meetingSidebar');
        if (!sidebar) {
            console.error('❌ Sidebar element not found');
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
                    chatSidebarTitle.textContent = 'الدردشة';
                }
                this.markChatAsRead();
                break;
            case 'participants':
                this.isParticipantsListOpen = true;
                // Update sidebar title
                const participantsSidebarTitle = document.getElementById('sidebarTitle');
                if (participantsSidebarTitle) {
                    participantsSidebarTitle.textContent = 'المشاركين';
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
                    sidebarTitle.textContent = 'الأيدي المرفوعة';
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
                    settingsSidebarTitle.textContent = 'الإعدادات';
                }
                this.updateSettingsPanel();
                break;
        }

        console.log(`✅ ${type} sidebar opened`);
    }

    /**
     * Close sidebar
     */
    closeSidebar() {
        console.log('📋 Closing sidebar');

        const sidebar = document.getElementById('meetingSidebar');
        if (!sidebar) {
            console.error('❌ Sidebar element not found');
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
                    sidebarTitle.textContent = 'الدردشة'; // Reset to default "Chat"
                }
                break;
            case 'settings':
                this.isSettingsOpen = false;
                break;
        }

        console.log('✅ Sidebar closed');
    }

    /**
     * Toggle fullscreen mode
     */
    toggleFullscreen() {
        console.log('🖥️ Toggling fullscreen...');

        const meetingInterface = document.getElementById('livekitMeetingInterface');
        if (!meetingInterface) {
            console.error('❌ Meeting interface not found');
            return;
        }

        if (meetingInterface.classList.contains('meeting-fullscreen')) {
            // Exit fullscreen
            meetingInterface.classList.remove('meeting-fullscreen');
            this.updateFullscreenButton(false);
            console.log('✅ Exited fullscreen mode');
        } else {
            // Enter fullscreen
            meetingInterface.classList.add('meeting-fullscreen');
            this.updateFullscreenButton(true);
            console.log('✅ Entered fullscreen mode');
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
            fullscreenText.textContent = isFullscreen ? 'إغلاق ملء الشاشة' : 'ملء الشاشة';
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
            console.log('💬 No message to send (empty input)');
            return;
        }

        const message = messageInput.value.trim();

        try {
            // Enhanced debugging for chat sending
            console.log('📤 ATTEMPTING TO SEND CHAT MESSAGE:');
            console.log(`  - Message: "${message}"`);
            console.log(`  - Local participant: ${this.localParticipant?.identity}`);
            console.log(`  - Local participant SID: ${this.localParticipant?.sid}`);
            console.log(`  - Room state: ${this.room?.state}`);
            console.log(`  - Room participants count: ${this.room?.numParticipants}`);

            // Verify room is properly connected before sending
            if (this.room?.state !== 'connected') {
                throw new Error(`Room not connected. Current state: ${this.room?.state}`);
            }

            // Log all participants for debugging
            console.log('📋 CURRENT ROOM PARTICIPANTS:');
            console.log(`  - Local: ${this.localParticipant?.identity} (SID: ${this.localParticipant?.sid})`);
            this.room.remoteParticipants.forEach((participant, sid) => {
                console.log(`  - Remote: ${participant.identity} (SID: ${sid})`);
            });

            if (!this.room.remoteParticipants || this.room.remoteParticipants.size === 0) {
                console.warn('⚠️ No remote participants to send message to');
                this.showNotification('لا يوجد مشاركين آخرين في الجلسة', 'warning');
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

            console.log('📦 PREPARED DATA PACKET:', data);

            // CRITICAL FIX: Use proper data encoding
            const encoder = new TextEncoder();
            const encodedData = encoder.encode(JSON.stringify(data));

            console.log('🔄 PUBLISHING DATA WITH ENHANCED BROADCASTING:');
            console.log(`  - Encoded data size: ${encodedData.length} bytes`);
            console.log(`  - Target participants: ALL (destinationSids: [])`);

            // Use reliable data packet kind for guaranteed delivery
            const LiveKitSDK = window.LiveKit;
            let dataKind = 1; // Safe fallback

            if (LiveKitSDK && LiveKitSDK.DataPacket_Kind) {
                dataKind = LiveKitSDK.DataPacket_Kind.RELIABLE || 1;
            }

            console.log(`  - Using data packet kind: ${dataKind}`);

            // CRITICAL FIX: Explicit destination scoping to broadcast to ALL participants
            const publishOptions = {
                reliable: true,
                destinationSids: [] // Empty array = broadcast to ALL participants in room
            };

            console.log('🎯 PUBLISHING WITH OPTIONS:', publishOptions);

            // Publish the data
            await this.room.localParticipant.publishData(
                encodedData,
                dataKind,
                publishOptions
            );

            console.log('✅ DATA PUBLISHED SUCCESSFULLY TO ALL PARTICIPANTS');

            // Verification logging
            console.log('🔍 POST-PUBLISH VERIFICATION:');
            console.log(`  - Room has ${this.room.remoteParticipants.size} remote participants`);
            console.log(`  - Each remote participant should receive this message`);

            // Add message to local chat UI immediately
            this.addChatMessage(message, this.localParticipant.identity, true);

            // Clear input
            messageInput.value = '';

            console.log('✅ CHAT MESSAGE SENT AND LOCAL UI UPDATED');

        } catch (error) {
            console.error('❌ FAILED TO SEND CHAT MESSAGE:', error);
            console.error('📍 DETAILED ERROR INFORMATION:', {
                name: error.name,
                message: error.message,
                stack: error.stack,
                roomState: this.room?.state,
                localParticipant: this.localParticipant?.identity,
                participantCount: this.room?.numParticipants
            });

            this.showNotification('خطأ في إرسال الرسالة: ' + error.message, 'error');
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
                <p class="text-xs ${isOwn ? 'text-blue-100' : 'text-gray-500'} mb-1">${isOwn ? 'أنت' : sender}</p>
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
        console.log('⚙️ Updating settings panel');
    }

    /**
     * Update control button states
     */
    updateControlButtons() {
        console.log('🎮 Updating control button states', {
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
                    micButton.title = 'إيقاف الميكروفون';
                    if (tooltip) tooltip.textContent = 'إيقاف الميكروفون';
                    if (svg) {
                        svg.innerHTML = '<path fill-rule="evenodd" d="M7 4a3 3 0 016 0v4a3 3 0 11-6 0V4zm4 10.93A7.001 7.001 0 0017 8a1 1 0 10-2 0A5 5 0 715 8a1 1 0 00-2 0 7.001 7.001 0 006 6.93V17H6a1 1 0 100 2h8a1 1 0 100-2h-3v-2.07z" clip-rule="evenodd"/>';
                    }
                } else {
                    micButton.classList.add('bg-red-600');
                    micButton.classList.remove('bg-gray-600', 'bg-gray-700');
                    micButton.title = 'تشغيل الميكروفون';
                    if (tooltip) tooltip.textContent = 'تشغيل الميكروفون';
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
                    micButton.title = 'إيقاف الميكروفون';
                    if (tooltip) tooltip.textContent = 'إيقاف الميكروفون';
                    if (svg) {
                        svg.innerHTML = '<path fill-rule="evenodd" d="M7 4a3 3 0 0 1 6 0v4a3 3 0 1 1-6 0V4zm4 10.93A7.001 7.001 0 0 0 17 8a1 1 0 1 0-2 0A5 5 0 0 1 5 8a1 1 0 0 0-2 0 7.001 7.001 0 0 0 6 6.93V17H6a1 1 0 1 0 0 2h8a1 1 0 1 0 0-2h-3v-2.07z" clip-rule="evenodd"/>';
                    }
                } else {
                    // Student mic is disabled
                    if (canUnmute) {
                        // Can unmute - show normal disabled state
                        micButton.classList.remove('bg-green-600', 'bg-gray-500');
                        micButton.classList.add('bg-red-600');
                        micButton.title = 'تشغيل الميكروفون';
                        if (tooltip) tooltip.textContent = 'تشغيل الميكروفون';
                    } else {
                        // Cannot unmute - show restricted state
                        micButton.classList.remove('bg-green-600', 'bg-red-600');
                        micButton.classList.add('bg-gray-500');
                        micButton.title = 'الميكروفون معطل من قبل المعلم';
                        if (tooltip) tooltip.textContent = 'الميكروفون معطل من قبل المعلم';
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
                cameraButton.title = 'إيقاف الكاميرا';
                if (tooltip) tooltip.textContent = 'إيقاف الكاميرا';
                if (svg) {
                    svg.innerHTML = '<path fill-rule="evenodd" d="M4 3a2 2 0 00-2 2v10a2 2 0 002 2h12a2 2 0 002-2V5a2 2 0 00-2-2H4zm12 12H4l4-8v3l2 2 2-2v-3l4 8z" clip-rule="evenodd"/>';
                }
            } else {
                cameraButton.classList.add('bg-red-600');
                cameraButton.classList.remove('bg-gray-600', 'bg-gray-700');
                cameraButton.title = 'تشغيل الكاميرا';
                if (tooltip) tooltip.textContent = 'تشغيل الكاميرا';
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
                screenShareButton.title = 'إيقاف مشاركة الشاشة';
                if (tooltip) tooltip.textContent = 'إيقاف مشاركة الشاشة';
                if (svg) {
                    svg.innerHTML = '<path d="M3 4a1 1 0 011-1h12a1 1 0 011 1v2a1 1 0 01-1 1H4a1 1 0 01-1-1V4zM3 10a1 1 0 011-1h6a1 1 0 011 1v6a1 1 0 01-1 1H4a1 1 0 01-1-1v-6zM14 9a1 1 0 00-1 1v6a1 1 0 001 1h2a1 1 0 001-1v-6a1 1 0 00-1-1h-2z"/><path d="M6 16h4v2H6z"/>';
                }
            } else {
                screenShareButton.classList.remove('bg-blue-600');
                screenShareButton.classList.add('bg-gray-600');
                screenShareButton.title = 'مشاركة الشاشة';
                if (tooltip) tooltip.textContent = 'مشاركة الشاشة';
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
                handRaiseButton.title = 'خفض اليد';
                if (tooltip) tooltip.textContent = 'خفض اليد';
                if (icon) icon.className = 'fa-solid fa-hand text-white text-xl';
            } else {
                handRaiseButton.classList.remove('bg-yellow-600');
                handRaiseButton.classList.add('bg-gray-600');
                handRaiseButton.title = 'رفع اليد';
                if (tooltip) tooltip.textContent = 'رفع اليد';
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
                recordButton.title = 'إيقاف التسجيل';
                if (svg) {
                    svg.innerHTML = '<path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zM8 7a1 1 0 011-1h2a1 1 0 011 1v6a1 1 0 01-1 1H9a1 1 0 01-1-1V7z" clip-rule="evenodd"/>';
                }
            } else {
                recordButton.classList.remove('bg-red-600');
                recordButton.classList.add('bg-gray-600');
                recordButton.title = 'بدء التسجيل';
                if (svg) {
                    svg.innerHTML = '<path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zM9.555 7.168A1 1 0 008 8v4a1 1 0 001.555.832l3-2a1 1 0 000-1.664l-3-2z" clip-rule="evenodd"/>';
                }
            }
        }

        console.log('✅ Control buttons updated with proper visual states');
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
                <h3 class="text-xl font-bold text-white mb-4">مغادرة الجلسة</h3>
                <p class="text-gray-300 mb-6">هل أنت متأكد من أنك تريد مغادرة الجلسة؟</p>
                <div class="flex justify-end space-x-3 space-x-reverse">
                    <button id="cancelLeave" class="px-4 py-2 bg-gray-600 text-white rounded hover:bg-gray-700 transition-colors">
                        إلغاء
                    </button>
                    <button id="confirmLeave" class="px-4 py-2 bg-red-600 text-white rounded hover:bg-red-700 transition-colors">
                        مغادرة
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
        console.log('🚪 Leaving meeting...');

        // CRITICAL FIX: Record leave attendance BEFORE leaving
        this.recordLeaveAttendance();

        if (this.config.onLeaveRequest) {
            this.config.onLeaveRequest();
        } else {
            // Fallback behavior - simply reload the current page
            console.log('🔄 Reloading current page after leaving meeting');
            window.location.reload();
        }
    }

    /**
     * Record leave attendance when user clicks leave button
     */
    async recordLeaveAttendance() {
        try {
            console.log('📝 Recording leave attendance via leave button...');
            
            // Get session ID and type from window object (set in Blade template)
            const sessionId = window.sessionId;
            const sessionType = window.sessionType || 'quran';
            
            if (!sessionId) {
                console.warn('⚠️ Session ID not available for leave attendance recording');
                return;
            }

            const response = await fetch('/api/sessions/meeting/leave', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.getAttribute('content')
                },
                credentials: 'same-origin',
                body: JSON.stringify({
                    session_type: sessionType,
                    session_id: sessionId
                })
            });

            if (response.ok) {
                const data = await response.json();
                console.log('✅ Leave attendance recorded via leave button:', data);
            } else {
                const error = await response.text();
                console.warn('⚠️ Failed to record leave attendance:', error);
            }
        } catch (error) {
            console.error('❌ Error recording leave attendance via leave button:', error);
        }
    }

    /**
     * Start meeting timer
     */
    startMeetingTimer() {
        console.log('⏱️ Starting meeting timer');

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

        console.log(`🎤 Updated audio permission for ${participantSid}: canSpeak=${canSpeak}, isMuted=${isMuted}`);
    }

    /**
     * Show notification
     * @param {string} message - Notification message
     * @param {string} type - Notification type ('success', 'error', 'info')
     */
    showNotification(message, type = 'info') {
        if (this.config.onNotification) {
            this.config.onNotification(message, type);
        } else {
            console.log(`📢 Notification (${type}):`, message);
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
                console.log('📥 Data received:', {
                    type: data.type,
                    from: participant?.identity,
                    data: data
                });
            }

            console.log(`📦 Controls handling data type: ${data.type} from ${participant?.identity}`);

            // Don't process messages from ourselves unless it's a test message
            if (participant?.sid === this.localParticipant?.sid && data.type !== 'testMessage') {
                if (window.debugMode) {
                    console.log('🔄 Skipping self-message:', data.type);
                }
                return;
            }

            // Validate required data fields
            if (!data.type) {
                console.error('❌ Invalid data received - missing type field:', data);
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
                    console.log('🧪 ✅ Test message received from', participant?.identity, ':', data.message);
                    break;

                default:
                    if (window.debugMode) {
                        console.log('❓ Unknown message type:', data.type);
                    }
                    break;
            }
        } catch (error) {
            console.error('❌ Failed to handle received data:', error, {
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
        console.log(`💬 Processing chat message: "${data.message}" from ${data.sender}`);
        console.log(`💬 Local participant identity: ${this.localParticipant?.identity}`);
        console.log(`💬 Sender identity from participant: ${participant?.identity}`);
        console.log(`💬 Sender identity from data: ${data.sender}`);
        console.log(`💬 Identity comparison: ${participant?.identity} !== ${this.localParticipant?.identity} = ${participant?.identity !== this.localParticipant?.identity}`);

        // Don't show messages from self (they're already shown when sent)
        if (participant?.identity !== this.localParticipant?.identity) {
            console.log(`💬 ✅ Adding message from other participant: ${data.sender}`);
            this.addChatMessage(data.message, data.sender, false);
        } else {
            console.log(`💬 ⏭️ Ignoring message from self (already shown)`);
        }
    }

    /**
     * Handle hand raise event
     * @param {Object} data - Hand raise data
     * @param {LiveKit.Participant} participant - Sender participant
     */
    handleHandRaiseEvent(data, participant) {
        console.log('🔧🔧🔧 VERSION 2025-11-16-FIX-v6 - handleHandRaiseEvent RUNNING 🔧🔧🔧');
        console.log(`✋ Hand raise update from ${participant.identity}: ${data.isRaised}`);
        console.log(`🔧 Participant SID: ${participant.sid}, Identity: ${participant.identity}`);

        // Don't process our own hand raise events
        if (participant?.identity === this.localParticipant?.identity) {
            console.log('✋ Ignoring own hand raise event');
            return;
        }

        if (data.isRaised) {
            console.log(`✋ ${participant.identity} raised their hand`);

            // Only teachers handle hand raise queue
            if (this.canControlStudentAudio()) {
                this.addToRaisedHandsQueue(data, participant);
            }

            // Update participant visual indicator
            console.log(`🔧 About to call updateParticipantHandRaiseIndicator(${participant.identity}, true)`);
            this.updateParticipantHandRaiseIndicator(participant.identity, true);

        } else {
            console.log(`✋ ${participant.identity} lowered their hand`);

            // Remove from queue
            if (this.canControlStudentAudio()) {
                this.removeFromRaisedHandsQueue(participant.sid);
            }

            // Update participant visual indicator
            console.log(`🔧 About to call updateParticipantHandRaiseIndicator(${participant.identity}, false)`);
            this.updateParticipantHandRaiseIndicator(participant.identity, false);
        }
    }

    /**
     * Handle lower hand command from teacher
     * @param {Object} data - Lower hand command data
     * @param {LiveKit.Participant} participant - Sender participant (teacher)
     */
    handleLowerHandCommand(data, participant) {
        console.log('✋ Received lower hand command from teacher:', data);

        // Check if this message is for me
        const myParticipantId = this.localParticipant?.identity;
        const myParticipantSid = this.localParticipant?.sid;

        if (data.targetParticipantId === myParticipantId || data.targetParticipantSid === myParticipantSid) {
            console.log('✋ This lower hand command is for me, lowering my hand');

            // Lower the hand
            this.isHandRaised = false;

            // Hide hand raise indicator
            this.createHandRaiseIndicatorDirect(myParticipantId, false);

            // Update control buttons
            this.updateControlButtons();

            // Show notification
            this.showNotification('قام المعلم بإخفاء يدك المرفوعة', 'info');

            console.log('✅ Hand lowered successfully');
        } else {
            console.log('✋ Lower hand command is for someone else, ignoring');
        }
    }

    /**
     * Handle clear all raised hands command from teacher
     * @param {Object} data - Clear all command data
     * @param {LiveKit.Participant} participant - Sender participant (teacher)
     */
    handleClearAllRaisedHandsCommand(data, participant) {
        console.log('✋ Received clear all raised hands command from teacher:', data);

        // If I'm a student and my hand is raised, lower it
        if (!this.canControlStudentAudio() && this.isHandRaised) {
            console.log('✋ Lowering my hand (student)');

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
            this.showNotification('تم إخفاء جميع الأيدي المرفوعة من قبل المعلم', 'info');

            console.log('✅ All raised hands cleared by teacher');
        }
    }

    /**
     * Handle audio permission event
     * @param {Object} data - Audio permission data
     * @param {LiveKit.Participant} participant - Sender participant
     */
    handleAudioPermissionEvent(data, participant) {
        console.log(`🎤 Audio permission event from ${participant?.identity}:`, data);

        // Only process if this is meant for us or if we're a teacher observing
        const isForUs = data.targetParticipantSid === this.localParticipant?.sid;
        const isFromTeacher = this.getParticipantRole(participant?.identity) === 'teacher';

        if (isForUs && isFromTeacher) {
            console.log(`🎤 Processing audio permission: ${data.action}`);

            if (data.action === 'grant') {
                // Student received permission to speak
                this.handleAudioPermissionGranted(data);
            } else if (data.action === 'revoke') {
                // Student's permission was revoked
                this.handleAudioPermissionRevoked(data);
            }
        } else if (this.canControlStudentAudio()) {
            // Teacher observing audio permission changes
            console.log(`🎤 Teacher observing audio permission change for ${data.targetParticipantId}`);
        }
    }

    /**
     * Handle global audio control event
     * @param {Object} data - Global audio control data
     * @param {LiveKit.Participant} participant - Sender participant
     */
    handleGlobalAudioControlEvent(data, participant) {
        console.log(`🔊 Global audio control from ${participant?.identity}:`, {
            action: data.action,
            settings: data.settings,
            controlledBy: data.controlledBy
        });

        // Only process if from a teacher (or self for immediate UI update)
        const isFromTeacher = this.getParticipantRole(participant?.identity) === 'teacher';
        const isFromSelf = participant?.sid === this.localParticipant?.sid;

        if (!isFromTeacher && !isFromSelf) {
            console.warn('🔊 ⚠️ Ignoring global audio control from non-teacher:', participant?.identity);
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

        console.log(`🔊 Updated global audio control state:`, {
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
            console.log('🔊 Post-update state check:', {
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
        console.log(`🎤 ✅ Audio permission granted by ${data.grantedBy}`);

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

            this.showNotification(`🎤 تم منحك إذن التحدث من قبل ${data.grantedBy}`, 'success');

        } catch (error) {
            console.error('❌ Failed to unmute after permission granted:', error);
        }
    }

    /**
     * Handle audio permission revoked for this student
     * @param {Object} data - Permission data
     */
    async handleAudioPermissionRevoked(data) {
        console.log(`🎤 ❌ Audio permission revoked by ${data.revokedBy}`);

        try {
            // Automatically mute microphone
            if (this.isAudioEnabled) {
                await this.localParticipant.setMicrophoneEnabled(false);
                this.isAudioEnabled = false;
                this.updateControlButtons();
            }

            this.showNotification(`🔇 تم إيقاف الميكروفون من قبل ${data.revokedBy}`, 'warning');

        } catch (error) {
            console.error('❌ Failed to mute after permission revoked:', error);
        }
    }

    /**
     * Handle global mute all command
     * @param {Object} data - Global control data
     */
    async handleGlobalMuteAll(data) {
        if (this.userRole === 'student') {
            console.log(`🔇 Global mute all by ${data.controlledBy}`);

            try {
                // Force mute the student's microphone via LiveKit SDK
                if (this.isAudioEnabled) {
                    await this.localParticipant.setMicrophoneEnabled(false);
                    this.isAudioEnabled = false;
                    this.updateControlButtons();
                    console.log('🔇 Student microphone force-disabled by teacher');
                }

                // CRITICAL FIX: Mark that global state has been explicitly set
                this.globalStateExplicitlySet = true;

                // Update global state
                this.globalAudioControlsState.allStudentsMuted = true;
                this.globalAudioControlsState.studentsCanSelfUnmute = false;
                this.globalAudioControlsState.teacherControlsAudio = true;

                console.log('🔇 Student global state updated for mute all:', this.globalAudioControlsState);

                this.showNotification(`🔇 تم كتم جميع الطلاب من قبل ${data.controlledBy}`, 'info');

            } catch (error) {
                console.error('❌ Failed to mute for global mute all:', error);
            }
        }
    }

    /**
     * Handle global allow all command
     * @param {Object} data - Global control data
     */
    async handleGlobalAllowAll(data) {
        if (this.userRole === 'student') {
            console.log(`🔊 Global allow all by ${data.controlledBy}`);

            try {
                // CRITICAL FIX: Mark that global state has been explicitly set  
                this.globalStateExplicitlySet = true;

                // Update global state first so student permissions are correct
                this.globalAudioControlsState.allStudentsMuted = false;
                this.globalAudioControlsState.studentsCanSelfUnmute = true;
                this.globalAudioControlsState.teacherControlsAudio = false;

                console.log('🔊 Student global state updated for allow all:', this.globalAudioControlsState);

                // Optionally enable microphone for students when allowed (commented out to give choice)
                // if (!this.isAudioEnabled) {
                //     await this.localParticipant.setMicrophoneEnabled(true);
                //     this.isAudioEnabled = true;
                //     this.updateControlButtons();
                // }

                // Update button UI to reflect new permissions
                this.updateControlButtons();

                this.showNotification(`🔊 يمكنك الآن استخدام الميكروفون - تم السماح للجميع من قبل ${data.controlledBy}`, 'success');

            } catch (error) {
                console.error('❌ Failed to unmute for global allow all:', error);
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
        console.log('🔧🔧🔧 VERSION 2025-11-16-FIX-v6 - createHandRaiseIndicatorDirect RUNNING 🔧🔧🔧');
        console.log(`✋ Direct hand raise indicator for ${participantIdentity}: ${isRaised ? 'SHOW' : 'HIDE'}`);

        // Find participant element by identity
        const elementId = `participant-${participantIdentity}`;
        console.log(`🔧 Looking for element with ID: ${elementId}`);

        const participantElement = document.getElementById(elementId);
        console.log(`🔧 Element found:`, participantElement ? 'YES' : 'NO');

        if (!participantElement) {
            console.warn(`✋ Participant element not found: ${elementId}`);
            console.warn(`🔧 Listing all participant elements in DOM:`);
            const allParticipants = document.querySelectorAll('[id^="participant-"]');
            allParticipants.forEach(el => console.log(`  - ${el.id}`));
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
                handRaiseIndicator.title = 'يد مرفوعة';
                
                participantElement.appendChild(handRaiseIndicator);
                
                console.log(`✅ Created hand raise indicator for ${participantIdentity}`);
            } else {
                // Show existing indicator
                handRaiseIndicator.style.display = 'flex';
                handRaiseIndicator.style.opacity = '1';
                handRaiseIndicator.style.transform = 'scale(1)';
                handRaiseIndicator.style.visibility = 'visible';
                console.log(`✅ Showed existing hand raise indicator for ${participantIdentity}`);
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
                console.log(`✅ Hidden hand raise indicator for ${participantIdentity}`);
            }
        }
    }

    /**
     * Update participant hand raise visual indicator
     * @param {string} participantId - Participant identity
     * @param {boolean} isRaised - Whether hand is raised
     */
    updateParticipantHandRaiseIndicator(participantId, isRaised) {
        console.log('🔧🔧🔧 VERSION 2025-11-16-FIX-v6 - updateParticipantHandRaiseIndicator RUNNING 🔧🔧🔧');
        console.log(`✋ Updating hand raise indicator for ${participantId}: ${isRaised}`);

        // Use direct hand raise indicator method (works reliably)
        console.log(`🔧 Calling createHandRaiseIndicatorDirect(${participantId}, ${isRaised})`);
        this.createHandRaiseIndicatorDirect(participantId, isRaised);
        console.log(`✋ ✅ Updated hand raise indicator for ${participantId}`);
    }

    /**
     * Request hand raise sync from all participants (teacher joins late)
     */
    async requestHandRaiseSync() {
        if (!this.canControlStudentAudio()) {
            return;
        }

        console.log('👋 Teacher requesting hand raise sync from all participants...');

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

            console.log('👋 Hand raise sync request sent to all participants');

        } catch (error) {
            console.error('❌ Failed to request hand raise sync:', error);
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

        console.log(`👋 Student responding to hand raise sync request from ${participant.identity}`);

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

            console.log(`👋 Sent hand raise status to teacher: ${this.isHandRaised}`);

        } catch (error) {
            console.error('❌ Failed to respond to hand raise sync:', error);
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

        console.log(`👋 Received hand raise sync from ${data.participantId}: ${data.isRaised}`);

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
        console.log('✋ Updating all participant hand raise indicators...');

        // Update raised hands from queue
        this.raisedHandsQueue.forEach((handRaise, participantSid) => {
            this.updateParticipantHandRaiseIndicator(participantSid, true);
        });

        // Also check for local participant if hand is raised
        if (this.isHandRaised && this.localParticipant) {
            this.updateParticipantHandRaiseIndicator(this.localParticipant.sid, true);
        }

        console.log(`✋ Updated indicators for ${this.raisedHandsQueue.size} raised hands`);
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

        console.log(`✋ Showing hand raise notification for ${studentName}`);

        // Create floating notification element
        const notification = document.createElement('div');
        notification.className = 'hand-raise-notification fixed top-4 left-1/2 transform -translate-x-1/2 bg-orange-500 text-white px-6 py-3 rounded-lg shadow-lg z-50 transition-all duration-300';
        notification.innerHTML = `
            <div class="flex items-center gap-3">
                <div class="w-6 h-6 bg-white bg-opacity-20 rounded-full flex items-center justify-center">
                    <i class="fa-solid fa-hand text-sm"></i>
                </div>
                <span class="font-medium">${studentName} رفع يده</span>
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

            console.log('✋ 🔊 Played hand raise notification sound');
        } catch (error) {
            console.warn('✋ Could not play notification sound:', error);
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

        console.log(`✋ ✅ Showed permission granted effect for ${participantSid}`);
    }

    /**
     * Update all participant microphone status icons
     * @param {boolean} muted - Whether microphones are muted
     */
    updateAllParticipantMicIcons(muted) {
        if (!this.room) {
            console.warn('⚠️ No room available to update participant icons');
            return;
        }

        console.log(`🎤 Updating all participant mic icons: ${muted ? 'MUTED' : 'UNMUTED'}`);

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
                console.log(`🎤 Updated mic icon for ${participantId}: ${muted ? 'MUTED' : 'CHECK TRACK'}`);
            }
        });
    }

    /**
     * Update all participant camera status icons
     * @param {boolean} disabled - Whether cameras are disabled
     */
    updateAllParticipantCameraIcons(disabled) {
        if (!this.room) {
            console.warn('⚠️ No room available to update participant icons');
            return;
        }

        console.log(`📹 Updating all participant camera icons: ${disabled ? 'DISABLED' : 'ENABLED'}`);

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
                console.log(`📹 Updated camera icon for ${participantId}: ${disabled ? 'DISABLED' : 'CHECK TRACK'}`);
            }
        });
    }

    /**
     * Destroy controls manager and clean up
     */
    destroy() {
        console.log('🧹 Destroying controls manager...');

        // Stop timer
        this.stopMeetingTimer();

        // Close any open sidebars
        this.closeSidebar();

        // Remove event listeners would be handled by the parent

        // Clear references
        this.room = null;
        this.localParticipant = null;

        console.log('🎮 Controls destroyed');
    }
}

// Make class globally available
window.LiveKitControls = LiveKitControls;
