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
        this.updateControlButtons();
        this.startMeetingTimer();
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

        // Leave meeting
        const leaveButton = document.getElementById('leaveMeeting');
        if (leaveButton) {
            leaveButton.addEventListener('click', () => this.showLeaveConfirmModal());
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
            this.isAudioEnabled = !this.isAudioEnabled;

            // Use SDK method to enable/disable microphone
            await this.localParticipant.setMicrophoneEnabled(this.isAudioEnabled);

            // Update UI
            this.updateControlButtons();

            const status = this.isAudioEnabled ? 'مفعل' : 'معطل';
            this.showNotification(`الميكروفون: ${status}`, 'success');

            // Notify state change
            this.notifyControlStateChange('microphone', this.isAudioEnabled);

            console.log('✅ Microphone toggled:', this.isAudioEnabled);
        } catch (error) {
            console.error('❌ Failed to toggle microphone:', error);
            this.showNotification('خطأ في التحكم بالميكروفون', 'error');
            // Revert state on error
            this.isAudioEnabled = !this.isAudioEnabled;
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
            this.isVideoEnabled = !this.isVideoEnabled;

            // Use SDK method to enable/disable camera
            await this.localParticipant.setCameraEnabled(this.isVideoEnabled);

            // Update UI
            this.updateControlButtons();

            const status = this.isVideoEnabled ? 'مفعلة' : 'معطلة';
            this.showNotification(`الكاميرا: ${status}`, 'success');

            // Notify state change
            this.notifyControlStateChange('camera', this.isVideoEnabled);

            console.log('✅ Camera toggled:', this.isVideoEnabled);
        } catch (error) {
            console.error('❌ Failed to toggle camera:', error);
            this.showNotification('خطأ في التحكم بالكاميرا', 'error');
            // Revert state on error
            this.isVideoEnabled = !this.isVideoEnabled;
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
            this.isScreenSharing = !this.isScreenSharing;

            // Use SDK method to enable/disable screen sharing
            await this.localParticipant.setScreenShareEnabled(this.isScreenSharing);

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
            // Revert state on error
            this.isScreenSharing = !this.isScreenSharing;
        }
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

            await this.room.localParticipant.publishData(
                JSON.stringify(data),
                window.LiveKit.DataPacket_Kind.RELIABLE
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
        if (!sidebar) return;

        // Hide all sidebar content
        const sidebarContents = sidebar.querySelectorAll('[id$="Content"]');
        sidebarContents.forEach(content => {
            content.style.display = 'none';
        });

        // Show specific content
        const targetContent = document.getElementById(`${type}Content`);
        if (targetContent) {
            targetContent.style.display = 'block';
        }

        // Show sidebar
        sidebar.classList.remove('translate-x-full');

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
    }

    /**
     * Close sidebar
     */
    closeSidebar() {
        console.log('📋 Closing sidebar');

        const sidebar = document.getElementById('meetingSidebar');
        if (!sidebar) return;

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
        if (!messageInput || !messageInput.value.trim()) return;

        const message = messageInput.value.trim();
        console.log('💬 Sending chat message:', message);

        try {
            // Send message via data channel
            const data = {
                type: 'chat',
                message: message,
                sender: this.localParticipant.identity,
                timestamp: Date.now()
            };

            await this.room.localParticipant.publishData(
                JSON.stringify(data),
                window.LiveKit.DataPacket_Kind.RELIABLE
            );

            // Add message to chat UI
            this.addChatMessage(message, this.localParticipant.identity, true);

            // Clear input
            messageInput.value = '';

            console.log('✅ Chat message sent');
        } catch (error) {
            console.error('❌ Failed to send chat message:', error);
            this.showNotification('خطأ في إرسال الرسالة', 'error');
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
        console.log('🎮 Updating control button states');

        // Microphone button
        const micButton = document.getElementById('toggleMic');
        if (micButton) {
            const icon = micButton.querySelector('i');
            if (this.isAudioEnabled) {
                micButton.classList.remove('bg-red-600');
                micButton.classList.add('bg-gray-700');
                if (icon) icon.className = 'fas fa-microphone';
            } else {
                micButton.classList.add('bg-red-600');
                micButton.classList.remove('bg-gray-700');
                if (icon) icon.className = 'fas fa-microphone-slash';
            }
        }

        // Camera button
        const cameraButton = document.getElementById('toggleCamera');
        if (cameraButton) {
            const icon = cameraButton.querySelector('i');
            if (this.isVideoEnabled) {
                cameraButton.classList.remove('bg-red-600');
                cameraButton.classList.add('bg-gray-700');
                if (icon) icon.className = 'fas fa-video';
            } else {
                cameraButton.classList.add('bg-red-600');
                cameraButton.classList.remove('bg-gray-700');
                if (icon) icon.className = 'fas fa-video-slash';
            }
        }

        // Screen share button
        const screenShareButton = document.getElementById('toggleScreenShare');
        if (screenShareButton) {
            const icon = screenShareButton.querySelector('i');
            if (this.isScreenSharing) {
                screenShareButton.classList.add('bg-blue-600');
                screenShareButton.classList.remove('bg-gray-700');
                if (icon) icon.className = 'fas fa-stop-circle';
            } else {
                screenShareButton.classList.remove('bg-blue-600');
                screenShareButton.classList.add('bg-gray-700');
                if (icon) icon.className = 'fas fa-desktop';
            }
        }

        // Hand raise button
        const handRaiseButton = document.getElementById('toggleHandRaise');
        if (handRaiseButton) {
            const icon = handRaiseButton.querySelector('i');
            if (this.isHandRaised) {
                handRaiseButton.classList.add('bg-yellow-600');
                handRaiseButton.classList.remove('bg-gray-700');
                if (icon) icon.className = 'fas fa-hand-paper';
            } else {
                handRaiseButton.classList.remove('bg-yellow-600');
                handRaiseButton.classList.add('bg-gray-700');
                if (icon) icon.className = 'far fa-hand-paper';
            }
        }

        // Recording button (teacher only)
        const recordButton = document.getElementById('toggleRecording');
        if (recordButton && this.isTeacher()) {
            const icon = recordButton.querySelector('i');
            if (this.isRecording) {
                recordButton.classList.add('bg-red-600');
                recordButton.classList.remove('bg-gray-700');
                if (icon) icon.className = 'fas fa-stop';
            } else {
                recordButton.classList.remove('bg-red-600');
                recordButton.classList.add('bg-gray-700');
                if (icon) icon.className = 'fas fa-record-vinyl';
            }
        }

        console.log('✅ Control buttons updated');
    }

    /**
     * Show leave confirmation modal
     */
    showLeaveConfirmModal() {
        const modal = document.createElement('div');
        modal.id = 'leaveConfirmModal';
        modal.className = 'fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-50';

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
            // Fallback behavior
            window.location.href = '/';
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
        if (data.type === 'chat') {
            this.addChatMessage(data.message, data.sender, false);
        } else if (data.type === 'handRaise') {
            // Handle hand raise updates
            console.log(`✋ Hand raise update from ${participant.identity}: ${data.isRaised}`);
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
}
