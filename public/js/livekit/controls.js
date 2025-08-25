/**
 * LiveKit Controls Module
 * Handles UI control interactions (mic, camera, screen share, etc.) using proper SDK methods
 */

/**
 * Controls manager for meeting UI interactions
 */
export class LiveKitControls {
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
            handRaiseButton.addEventListener('click', () => this.toggleHandRaise());
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

        console.log('✅ Control buttons set up successfully');
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

            // Use SDK method to enable/disable microphone
            await this.localParticipant.setMicrophoneEnabled(newState);

            // Update our internal state to match the SDK
            this.isAudioEnabled = this.localParticipant.isMicrophoneEnabled;

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

            // Use SDK method to enable/disable camera
            await this.localParticipant.setCameraEnabled(newState);

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

            // Get screen share constraints
            const constraints = {
                video: {
                    mediaSource: 'screen',
                    // Optional: Set constraints for better quality
                    width: { ideal: 1920, max: 1920 },
                    height: { ideal: 1080, max: 1080 },
                    frameRate: { ideal: 15, max: 30 }
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

            // Publish video track
            await this.localParticipant.publishTrack(videoTrack, {
                name: 'screen_share',
                source: window.LiveKit.Track.Source.ScreenShare
            });

            console.log('✅ Screen share video track published');

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
     * Toggle hand raise
     */
    async toggleHandRaise() {
        console.log('✋ Toggling hand raise...');

        try {
            this.isHandRaised = !this.isHandRaised;

            // Send hand raise state via data channel
            const data = {
                type: 'handRaise',
                isRaised: this.isHandRaised,
                participantId: this.localParticipant.identity,
                timestamp: Date.now()
            };

            // Ensure we have the right data packet kind
            const dataKind = window.LiveKit.DataPacket_Kind?.RELIABLE || 1;

            await this.room.localParticipant.publishData(
                JSON.stringify(data),
                dataKind
            );

            // Update UI
            this.updateControlButtons();

            const status = this.isHandRaised ? 'مرفوعة' : 'مخفضة';
            this.showNotification(`اليد: ${status}`, 'success');

            // Notify state change
            this.notifyControlStateChange('handRaise', this.isHandRaised);

            console.log('✅ Hand raise toggled:', this.isHandRaised);
        } catch (error) {
            console.error('❌ Failed to toggle hand raise:', error);
            this.showNotification('خطأ في رفع اليد', 'error');
            // Revert state on error
            this.isHandRaised = !this.isHandRaised;
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
                this.markChatAsRead();
                break;
            case 'participants':
                this.isParticipantsListOpen = true;
                break;
            case 'settings':
                this.isSettingsOpen = true;
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
                fullscreenIcon.innerHTML = '<path fill-rule="evenodd" d="M5 9V5h4V3H3v6h2zm0 2v4h4v-2H5v-2zm6-8h4v4h-2V5h-2V3zm4 8v2h-2v2h4v-4h-2z" clip-rule="evenodd"/>';
            } else {
                fullscreenIcon.innerHTML = '<path fill-rule="evenodd" d="M3 4a1 1 0 011-1h4a1 1 0 010 2H6.414l2.293 2.293a1 1 0 01-1.414 1.414L5 6.414V8a1 1 0 01-2 0V4zm9 1a1 1 0 010-2h4a1 1 0 011 1v4a1 1 0 01-2 0V6.414l-2.293 2.293a1 1 0 11-1.414-1.414L13.586 5H12zm-9 7a1 1 0 012 0v1.586l2.293-2.293a1 1 0 111.414 1.414L6.414 15H8a1 1 0 010 2H4a1 1 0 01-1-1v-4zm13-1a1 1 0 011 1v4a1 1 0 01-1 1h-4a1 1 0 010-2h1.586l-2.293-2.293a1 1 0 111.414-1.414L15 13.586V12a1 1 0 011-1z" clip-rule="evenodd"/>';
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
            settings: document.getElementById('toggleSettings')
        };

        Object.entries(buttons).forEach(([type, button]) => {
            if (!button) return;

            if (type === activeType) {
                button.classList.add('bg-blue-600', 'text-white');
                button.classList.remove('bg-gray-700', 'text-gray-300');
            } else {
                button.classList.remove('bg-blue-600', 'text-white');
                button.classList.add('bg-gray-700', 'text-gray-300');
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
            <div class="${isOwn ? 'bg-blue-600' : 'bg-gray-700'} rounded-lg px-3 py-2 max-w-xs">
                <p class="text-xs text-gray-300 mb-1">${isOwn ? 'أنت' : sender}</p>
                <p class="text-white text-sm">${message}</p>
                <p class="text-xs text-gray-400 mt-1">${timestamp}</p>
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

        // Microphone button
        const micButton = document.getElementById('toggleMic');
        if (micButton) {
            const svg = micButton.querySelector('svg');
            if (this.isAudioEnabled) {
                micButton.classList.remove('bg-red-600', 'bg-red-500');
                micButton.classList.add('bg-gray-600');
                micButton.title = 'إيقاف الميكروفون';
                if (svg) {
                    svg.innerHTML = '<path fill-rule="evenodd" d="M7 4a3 3 0 016 0v4a3 3 0 11-6 0V4zm4 10.93A7.001 7.001 0 0017 8a1 1 0 10-2 0A5 5 0 015 8a1 1 0 00-2 0 7.001 7.001 0 006 6.93V17H6a1 1 0 100 2h8a1 1 0 100-2h-3v-2.07z" clip-rule="evenodd"/>';
                }
            } else {
                micButton.classList.add('bg-red-600');
                micButton.classList.remove('bg-gray-600', 'bg-gray-700');
                micButton.title = 'تشغيل الميكروفون';
                if (svg) {
                    svg.innerHTML = '<path d="M2.5 8.5a6 6 0 0 1 12 0v2a1 1 0 0 0 2 0v-2a8 8 0 0 0-16 0v2a1 1 0 0 0 2 0v-2z"/><path d="M10 8a2 2 0 1 1-4 0V6a2 2 0 1 1 4 0v2zM8 13a1 1 0 0 1 1-1h4a1 1 0 1 1 0 2H9a1 1 0 0 1-1-1z"/><path d="m2.5 1.5 15 15a1 1 0 0 1-1.414 1.414l-15-15A1 1 0 0 1 2.5 1.5z"/>';
                }
            }
        }

        // Camera button
        const cameraButton = document.getElementById('toggleCamera');
        if (cameraButton) {
            const svg = cameraButton.querySelector('svg');
            if (this.isVideoEnabled) {
                cameraButton.classList.remove('bg-red-600', 'bg-red-500');
                cameraButton.classList.add('bg-gray-600');
                cameraButton.title = 'إيقاف الكاميرا';
                if (svg) {
                    svg.innerHTML = '<path fill-rule="evenodd" d="M4 3a2 2 0 00-2 2v10a2 2 0 002 2h12a2 2 0 002-2V5a2 2 0 00-2-2H4zm12 12H4l4-8v3l2 2 2-2v-3l4 8z" clip-rule="evenodd"/>';
                }
            } else {
                cameraButton.classList.add('bg-red-600');
                cameraButton.classList.remove('bg-gray-600', 'bg-gray-700');
                cameraButton.title = 'تشغيل الكاميرا';
                if (svg) {
                    svg.innerHTML = '<path d="M4 3a2 2 0 0 0-2 2v10a2 2 0 0 0 2 2h8a2 2 0 0 0 2-2V5a2 2 0 0 0-2-2H4z"/><path d="m16 7 2-2v10l-2-2V7z"/><path d="m2.5 1.5 15 15a1 1 0 0 1-1.414 1.414l-15-15A1 1 0 0 1 2.5 1.5z"/>';
                }
            }
        }

        // Screen share button
        const screenShareButton = document.getElementById('toggleScreenShare');
        if (screenShareButton) {
            const svg = screenShareButton.querySelector('svg');
            if (this.isScreenSharing) {
                screenShareButton.classList.add('bg-blue-600');
                screenShareButton.classList.remove('bg-gray-600', 'bg-gray-700');
                screenShareButton.title = 'إيقاف مشاركة الشاشة';
                if (svg) {
                    svg.innerHTML = '<path d="M3 4a1 1 0 011-1h12a1 1 0 011 1v2a1 1 0 01-1 1H4a1 1 0 01-1-1V4zM3 10a1 1 0 011-1h6a1 1 0 011 1v6a1 1 0 01-1 1H4a1 1 0 01-1-1v-6zM14 9a1 1 0 00-1 1v6a1 1 0 001 1h2a1 1 0 001-1v-6a1 1 0 00-1-1h-2z"/><path d="M6 16h4v2H6z"/>';
                }
            } else {
                screenShareButton.classList.remove('bg-blue-600');
                screenShareButton.classList.add('bg-gray-600');
                screenShareButton.title = 'مشاركة الشاشة';
                if (svg) {
                    svg.innerHTML = '<path d="M3 4a1 1 0 011-1h12a1 1 0 011 1v2a1 1 0 01-1 1H4a1 1 0 01-1-1V4zM3 10a1 1 0 011-1h6a1 1 0 011 1v6a1 1 0 01-1 1H4a1 1 0 01-1-1v-6zM14 9a1 1 0 00-1 1v6a1 1 0 001 1h2a1 1 0 001-1v-6a1 1 0 00-1-1h-2z"/>';
                }
            }
        }

        // Hand raise button
        const handRaiseButton = document.getElementById('toggleHandRaise');
        if (handRaiseButton) {
            const icon = handRaiseButton.querySelector('i');
            if (this.isHandRaised) {
                handRaiseButton.classList.add('bg-yellow-600');
                handRaiseButton.classList.remove('bg-gray-600', 'bg-gray-700');
                handRaiseButton.title = 'خفض اليد';
                if (icon) icon.className = 'fa-solid fa-hand text-white text-xl';
            } else {
                handRaiseButton.classList.remove('bg-yellow-600');
                handRaiseButton.classList.add('bg-gray-600');
                handRaiseButton.title = 'رفع اليد';
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

        if (this.config.onLeaveRequest) {
            this.config.onLeaveRequest();
        } else {
            // Fallback behavior - simply reload the current page
            console.log('🔄 Reloading current page after leaving meeting');
            window.location.reload();
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
     * Handle data received (for chat and hand raise)
     * @param {Object} data - Received data
     * @param {LiveKit.Participant} participant - Sender participant
     */
    handleDataReceived(data, participant) {
        console.log(`📦 Controls handling data type: ${data.type} from ${participant?.identity}`);
        console.log(`📦 Full data object:`, data);
        console.log(`📦 Participant object:`, participant);
        console.log(`📦 Local participant object:`, this.localParticipant);

        if (data.type === 'chat') {
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
        } else if (data.type === 'handRaise') {
            console.log(`✋ Hand raise update from ${participant.identity}: ${data.isRaised}`);
            // Handle hand raise updates
        } else {
            console.warn(`⚠️ Unknown data type received: ${data.type}`);
        }
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

        console.log('✅ Controls manager destroyed');
    }

    /**
     * Debug function to test chat manually
     */
    debugTestChat() {
        console.log('🔍 Testing chat functionality...');
        console.log('🔍 Room state:', this.room?.state);
        console.log('🔍 Local participant:', this.localParticipant?.identity);
        console.log('🔍 Remote participants:', Array.from(this.room?.remoteParticipants?.keys() || []));
        console.log('🔍 Room participants count:', this.room?.numParticipants);
        console.log('🔍 DataPacket_Kind available:', window.LiveKit?.DataPacket_Kind);

        // Send a test message
        const testMessage = 'Test message ' + Date.now();
        const messageInput = document.getElementById('chatMessageInput');
        if (messageInput) {
            messageInput.value = testMessage;
            this.sendChatMessage();
        }
    }

    /**
     * Debug function to test data channel connectivity
     */
    async debugDataChannel() {
        console.log('🔍 Testing data channel connectivity...');
        
        if (!this.room || this.room.state !== 'connected') {
            console.error('❌ Room not connected');
            return;
        }

        const testData = {
            type: 'test',
            message: 'Data channel test ' + Date.now(),
            sender: this.localParticipant?.identity,
            timestamp: Date.now()
        };

        try {
            console.log('🔍 Sending test data:', testData);
            
            const encoder = new TextEncoder();
            const encodedData = encoder.encode(JSON.stringify(testData));
            
            await this.room.localParticipant.publishData(
                encodedData,
                window.LiveKit.DataPacket_Kind?.RELIABLE || 1,
                {
                    reliable: true,
                    destinationSids: [] // Send to all
                }
            );
            
            console.log('✅ Test data sent successfully');
            
        } catch (error) {
            console.error('❌ Failed to send test data:', error);
        }
    }

    /**
     * Debug function to get current state
     */
    debugGetState() {
        return {
            room: {
                state: this.room?.state,
                numParticipants: this.room?.numParticipants,
                localParticipant: {
                    identity: this.room?.localParticipant?.identity,
                    sid: this.room?.localParticipant?.sid
                },
                remoteParticipants: Array.from(this.room?.remoteParticipants?.values() || []).map(p => ({
                    identity: p.identity,
                    sid: p.sid
                }))
            },
            controls: {
                isAudioEnabled: this.isAudioEnabled,
                isVideoEnabled: this.isVideoEnabled,
                isChatOpen: this.isChatOpen
            }
        };
    }

    /**
     * Comprehensive data channel diagnostics
     */
    async debugDataChannelDiagnostics() {
        console.log('🔍 ==== DATA CHANNEL DIAGNOSTICS ====');
        
        // 1. Basic connectivity checks
        console.log('🔍 1. BASIC CONNECTIVITY:');
        console.log(`  - Room exists: ${!!this.room}`);
        console.log(`  - Room state: ${this.room?.state}`);
        console.log(`  - Local participant: ${this.localParticipant?.identity}`);
        console.log(`  - Local participant SID: ${this.localParticipant?.sid}`);
        console.log(`  - Remote participants count: ${this.room?.remoteParticipants?.size || 0}`);
        
        // 2. List all participants
        console.log('🔍 2. ALL PARTICIPANTS:');
        if (this.localParticipant) {
            console.log(`  - LOCAL: ${this.localParticipant.identity} (SID: ${this.localParticipant.sid})`);
        }
        this.room?.remoteParticipants?.forEach((participant, sid) => {
            console.log(`  - REMOTE: ${participant.identity} (SID: ${sid})`);
        });
        
        // 3. LiveKit SDK checks
        console.log('🔍 3. LIVEKIT SDK:');
        console.log(`  - LiveKit global: ${!!window.LiveKit}`);
        console.log(`  - DataPacket_Kind: ${JSON.stringify(window.LiveKit?.DataPacket_Kind)}`);
        
        // 4. Data channel capability test
        console.log('🔍 4. DATA CHANNEL TEST:');
        if (this.room?.state === 'connected' && this.room.remoteParticipants.size > 0) {
            try {
                const testData = {
                    type: 'diagnostic_test',
                    message: 'Diagnostic test message',
                    sender: this.localParticipant?.identity,
                    senderSid: this.localParticipant?.sid,
                    timestamp: new Date().toISOString(),
                    testId: `test_${Date.now()}`
                };
                
                console.log(`  - Sending diagnostic test:`, testData);
                
                const encoder = new TextEncoder();
                const encodedData = encoder.encode(JSON.stringify(testData));
                
                await this.room.localParticipant.publishData(
                    encodedData,
                    window.LiveKit.DataPacket_Kind?.RELIABLE || 1,
                    {
                        reliable: true,
                        destinationSids: [] // Broadcast to all
                    }
                );
                
                console.log(`  - ✅ Diagnostic test sent successfully`);
                console.log(`  - Watch for incoming data in other participants' consoles`);
                
            } catch (error) {
                console.error(`  - ❌ Diagnostic test failed:`, error);
            }
        } else {
            console.log(`  - ❌ Cannot test: Room not connected or no remote participants`);
        }
        
        console.log('🔍 ==== END DIAGNOSTICS ====');
        
        return this.debugGetState();
    }

    /**
     * Test chat message sending with enhanced debugging
     */
    async debugSendTestMessage(message = null) {
        const testMessage = message || `Debug test message ${Date.now()}`;
        
        console.log('🐛 ==== DEBUG CHAT MESSAGE TEST ====');
        console.log(`🐛 Sending test message: "${testMessage}"`);
        
        // Temporarily set the input and trigger send
        const messageInput = document.getElementById('chatMessageInput');
        if (messageInput) {
            const originalValue = messageInput.value;
            messageInput.value = testMessage;
            
            try {
                await this.sendChatMessage();
                console.log('🐛 ✅ Test message sent via normal flow');
            } catch (error) {
                console.error('🐛 ❌ Test message failed:', error);
            } finally {
                messageInput.value = originalValue;
            }
        } else {
            console.error('🐛 ❌ Chat input not found');
        }
        
        console.log('🐛 ==== END CHAT TEST ====');
    }
}

// ====== GLOBAL DEBUG FUNCTIONS ======
// These functions are available in the browser console for testing

/**
 * Global function to test chat functionality
 * Usage: window.debugLiveKitChat()
 */
window.debugLiveKitChat = function(message) {
    console.log('🌐 Global chat debug function called');
    
    if (window.meeting?.controls) {
        if (message) {
            window.meeting.controls.debugSendTestMessage(message);
        } else {
            window.meeting.controls.debugTestChat();
        }
    } else {
        console.error('❌ No meeting controls available');
        console.log('Available globals:', Object.keys(window).filter(k => k.includes('meeting') || k.includes('livekit')));
    }
};

/**
 * Global function to run comprehensive diagnostics
 * Usage: window.debugLiveKitDiagnostics()
 */
window.debugLiveKitDiagnostics = async function() {
    console.log('🌐 Global diagnostics function called');
    
    if (window.meeting?.controls) {
        return await window.meeting.controls.debugDataChannelDiagnostics();
    } else {
        console.error('❌ No meeting controls available');
        return null;
    }
};

/**
 * Global function to get current meeting state
 * Usage: window.debugLiveKitState()
 */
window.debugLiveKitState = function() {
    console.log('🌐 Global state function called');
    
    if (window.meeting?.controls) {
        const state = window.meeting.controls.debugGetState();
        console.log('📊 Current LiveKit state:', state);
        return state;
    } else {
        console.error('❌ No meeting controls available');
        return null;
    }
};

/**
 * Global function to send a specific message for testing
 * Usage: window.debugLiveKitSend('Hello from participant 1')
 */
window.debugLiveKitSend = function(message) {
    console.log('🌐 Global send function called with:', message);
    
    if (window.meeting?.controls) {
        window.meeting.controls.debugSendTestMessage(message);
    } else {
        console.error('❌ No meeting controls available');
    }
};

/**
 * Global function to test data channel broadcasting
 * Usage: window.debugLiveKitBroadcast()
 */
window.debugLiveKitBroadcast = async function() {
    console.log('🌐 Global broadcast test function called');
    
    if (window.meeting?.controls) {
        const room = window.meeting.controls.room;
        if (!room || room.state !== 'connected') {
            console.error('❌ Room not connected');
            return;
        }
        
        const testData = {
            type: 'broadcast_test',
            message: `Broadcast test from ${room.localParticipant?.identity}`,
            sender: room.localParticipant?.identity,
            senderSid: room.localParticipant?.sid,
            timestamp: new Date().toISOString(),
            testId: `broadcast_${Date.now()}`
        };
        
        try {
            console.log('📤 Broadcasting test data:', testData);
            
            const encoder = new TextEncoder();
            const encodedData = encoder.encode(JSON.stringify(testData));
            
            await room.localParticipant.publishData(
                encodedData,
                window.LiveKit.DataPacket_Kind?.RELIABLE || 1,
                {
                    reliable: true,
                    destinationSids: [] // Broadcast to ALL participants
                }
            );
            
            console.log('✅ Broadcast test data sent successfully');
            console.log('🔍 Check other participants\' consoles for reception');
            
        } catch (error) {
            console.error('❌ Broadcast test failed:', error);
        }
    } else {
        console.error('❌ No meeting controls available');
    }
};

console.log('🌐 LiveKit debug functions registered:');
console.log('  - window.debugLiveKitChat(message?) - Test chat functionality');
console.log('  - window.debugLiveKitDiagnostics() - Run comprehensive diagnostics');
console.log('  - window.debugLiveKitState() - Get current state');
console.log('  - window.debugLiveKitSend(message) - Send specific test message');
console.log('  - window.debugLiveKitBroadcast() - Test data channel broadcasting');

