/**
 * Meeting Data Channel Handler
 * Handles reliable message delivery, synchronization, and multi-channel communication
 */
class MeetingDataChannelHandler {
    constructor(config) {
        this.config = config;
        this.room = config.room;
        this.localParticipant = config.localParticipant;
        this.sessionId = config.sessionId;
        this.participantRole = config.participantRole || 'student';

        // Message handling
        this.messageHandlers = new Map();
        this.pendingAcknowledgments = new Map();
        this.messageHistory = new Map();
        this.reconnectAttempts = 0;
        this.maxReconnectAttempts = 5;

        // State synchronization
        this.lastSyncTimestamp = null;
        this.syncInterval = null;
        this.isOnline = navigator.onLine;

        // Multi-channel setup
        this.channels = {
            livekit: true,
            websocket: false,
            polling: false,
            sse: false
        };

        this.initializeHandlers();
        this.setupNetworkMonitoring();
        this.startStateSynchronization();

        console.log('📡 MeetingDataChannelHandler initialized', {
            sessionId: this.sessionId,
            role: this.participantRole,
            channels: this.channels
        });
    }

    /**
     * Initialize all message handlers and communication channels
     */
    initializeHandlers() {
        // Primary: LiveKit Data Channel
        this.setupLiveKitDataChannel();

        // Secondary: WebSocket (if available)
        this.setupWebSocketChannel();

        // Tertiary: Polling fallback
        this.setupPollingFallback();

        // Quaternary: Server-Sent Events
        this.setupServerSentEvents();

        // Register command handlers
        this.registerCommandHandlers();
    }

    /**
     * Setup LiveKit data channel handling
     */
    setupLiveKitDataChannel() {
        if (!this.room) {
            console.warn('⚠️ No room available for data channel setup');
            return;
        }

        this.room.on('dataReceived', (payload, participant, kind, topic) => {
            try {
                const data = JSON.parse(new TextDecoder().decode(payload));
                this.handleIncomingMessage(data, 'livekit', participant);
            } catch (error) {
                console.error('❌ Failed to parse LiveKit data:', error);
            }
        });

        this.channels.livekit = true;
        console.log('✅ LiveKit data channel setup complete');
    }

    /**
     * Setup WebSocket communication
     */
    setupWebSocketChannel() {
        if (typeof window.Echo !== 'undefined') {
            try {
                // Join meeting channel
                const meetingChannel = window.Echo.join(`meeting.${this.sessionId}`);

                meetingChannel.listen('.meeting.command', (event) => {
                    this.handleIncomingMessage(event.command_data, 'websocket');
                });

                meetingChannel.here((users) => {
                    console.log('👥 Current meeting participants:', users.length);
                });

                meetingChannel.joining((user) => {
                    console.log('🟢 Participant joined:', user.name);
                });

                meetingChannel.leaving((user) => {
                    console.log('🔴 Participant left:', user.name);
                });

                this.channels.websocket = true;
                console.log('✅ WebSocket channel setup complete');

            } catch (error) {
                console.warn('⚠️ WebSocket setup failed:', error);
                this.channels.websocket = false;
            }
        }
    }

    /**
     * Setup polling fallback for unreliable connections
     */
    setupPollingFallback() {
        // Only enable polling if primary channels fail
        this.pollingInterval = null;
        this.channels.polling = false;
    }

    /**
     * Setup Server-Sent Events
     */
    setupServerSentEvents() {
        try {
            this.eventSource = new EventSource(`/api/sessions/${this.sessionId}/events`);

            this.eventSource.onmessage = (event) => {
                try {
                    const data = JSON.parse(event.data);
                    this.handleIncomingMessage(data, 'sse');
                } catch (error) {
                    console.error('❌ SSE parse error:', error);
                }
            };

            this.eventSource.onerror = (error) => {
                console.warn('⚠️ SSE error:', error);
                if (this.eventSource.readyState === EventSource.CLOSED) {
                    this.reconnectSSE();
                }
            };

            this.channels.sse = true;
            console.log('✅ Server-Sent Events setup complete');

        } catch (error) {
            console.warn('⚠️ SSE setup failed:', error);
            this.channels.sse = false;
        }
    }

    /**
     * Handle incoming messages from any channel
     */
    handleIncomingMessage(data, channel, participant = null) {
        console.log(`📨 Received message via ${channel}:`, data);

        // Deduplicate messages
        if (this.messageHistory.has(data.message_id)) {
            console.log('🔄 Duplicate message ignored:', data.message_id);
            return;
        }

        // Store message
        this.messageHistory.set(data.message_id, {
            data,
            channel,
            receivedAt: Date.now(),
            participant
        });

        // Clean old messages (keep last 100)
        if (this.messageHistory.size > 100) {
            const oldestKey = this.messageHistory.keys().next().value;
            this.messageHistory.delete(oldestKey);
        }

        // Route to appropriate handler
        this.routeMessage(data);

        // Send acknowledgment if required
        if (data.requires_acknowledgment) {
            this.sendAcknowledgment(data.message_id, { received_via: channel });
        }
    }

    /**
     * Route messages to appropriate handlers
     */
    routeMessage(data) {
        const handler = this.messageHandlers.get(data.command);

        if (handler) {
            try {
                handler(data);
            } catch (error) {
                console.error(`❌ Handler error for ${data.command}:`, error);
            }
        } else {
            console.warn(`⚠️ No handler for command: ${data.command}`);
            this.handleUnknownCommand(data);
        }
    }

    /**
     * Register command handlers
     */
    registerCommandHandlers() {
        // Teacher control commands
        this.messageHandlers.set('mute_all_students', (data) => {
            this.handleMuteAllStudents(data);
        });

        this.messageHandlers.set('allow_student_microphones', (data) => {
            this.handleAllowStudentMicrophones(data);
        });

        this.messageHandlers.set('clear_all_hand_raises', (data) => {
            this.handleClearAllHandRaises(data);
        });

        this.messageHandlers.set('clear_all_raised_hands', (data) => {
            this.handleClearAllHandRaises(data);
        });

        this.messageHandlers.set('lower_hand', (data) => {
            this.handleLowerHand(data);
        });

        this.messageHandlers.set('grant_microphone_permission', (data) => {
            this.handleGrantMicrophonePermission(data);
        });

        this.messageHandlers.set('end_session', (data) => {
            this.handleEndSession(data);
        });

        this.messageHandlers.set('kick_participant', (data) => {
            this.handleKickParticipant(data);
        });

        // System commands
        this.messageHandlers.set('session_announcement', (data) => {
            this.handleSessionAnnouncement(data);
        });

        this.messageHandlers.set('sync_state', (data) => {
            this.handleStateSynchronization(data);
        });
    }

    /**
     * Command handler implementations
     */
    handleMuteAllStudents(data) {
        if (this.participantRole === 'student') {
            console.log('🔇 Teacher muted all students');

            // Create the data structure expected by handleGlobalAudioControlEvent
            const controlEventData = {
                type: 'globalAudioControl',
                action: 'muteAll',
                controlledBy: data.teacher_identity || 'Teacher',
                timestamp: data.timestamp,
                settings: {
                    allStudentsMuted: true,
                    studentsCanSelfUnmute: false,
                    teacherControlsAudio: true
                }
            };

            // Update global state in LiveKit controls
            if (window.meeting?.controls) {
                // Create a mock participant object
                const mockParticipant = {
                    identity: data.teacher_identity || 'Teacher',
                    sid: 'teacher_sid'
                };
                window.meeting.controls.handleGlobalAudioControlEvent(controlEventData, mockParticipant);
            }

            // Show notification
            this.showNotification(data.data.message || 'تم كتم جميع الطلاب', 'warning');
        }
    }

    handleAllowStudentMicrophones(data) {
        if (this.participantRole === 'student') {
            console.log('🎤 Teacher allowed student microphones');

            // Create the data structure expected by handleGlobalAudioControlEvent
            const controlEventData = {
                type: 'globalAudioControl',
                action: 'allowAll',
                controlledBy: data.teacher_identity || 'Teacher',
                timestamp: data.timestamp,
                settings: {
                    allStudentsMuted: false,
                    studentsCanSelfUnmute: true,
                    teacherControlsAudio: false
                }
            };

            // Update global state
            if (window.meeting?.controls) {
                // Create a mock participant object
                const mockParticipant = {
                    identity: data.teacher_identity || 'Teacher',
                    sid: 'teacher_sid'
                };
                window.meeting.controls.handleGlobalAudioControlEvent(controlEventData, mockParticipant);
            }

            this.showNotification(data.data.message || 'تم السماح باستخدام الميكروفون', 'success');
        }
    }

    handleClearAllHandRaises(data) {
        console.log('✋ Clear all hand raises');

        if (window.meeting?.controls) {
            window.meeting.controls.handleClearAllHandRaises(data);
        }

        this.showNotification(data.data.message || 'تم مسح جميع الأيدي المرفوعة', 'info');
    }

    handleLowerHand(data) {
        console.log('✋ Received lower hand command from teacher:', data);

        // Check if this message is for me
        const myParticipantId = window.room?.localParticipant?.identity;
        const myParticipantSid = window.room?.localParticipant?.sid;

        if (data.targetParticipantId === myParticipantId || data.targetParticipantSid === myParticipantSid) {
            console.log('✋ This lower hand command is for me, lowering my hand');

            if (window.meeting?.controls) {
                // Lower the hand
                window.meeting.controls.isHandRaised = false;

                // Hide hand raise indicator
                window.meeting.controls.createHandRaiseIndicatorDirect(myParticipantId, false);

                // Update control buttons
                window.meeting.controls.updateControlButtons();

                console.log('✅ Hand lowered successfully');
            }

            this.showNotification('قام المعلم بإخفاء يدك المرفوعة', 'info');
        }
    }

    handleGrantMicrophonePermission(data) {
        const studentId = data.data.student_id;
        const currentUserId = this.getCurrentUserId();

        if (studentId && currentUserId && studentId.toString() === currentUserId.toString()) {
            console.log('🎤 Granted microphone permission');

            if (window.meeting?.controls) {
                window.meeting.controls.handleAudioPermissionGranted(data);
            }

            this.showNotification(data.data.message || 'تم منحك إذن استخدام الميكروفون', 'success');
        }
    }

    handleEndSession(data) {
        console.log('🛑 Session ended by teacher');

        this.showNotification('تم إنهاء الجلسة من قبل المعلم', 'warning');

        // Give user time to see the message before redirect
        setTimeout(() => {
            if (data.data.redirect_url) {
                window.location.href = data.data.redirect_url;
            } else {
                window.location.reload();
            }
        }, 3000);
    }

    handleKickParticipant(data) {
        const targetId = data.data.participant_id;
        const currentUserId = this.getCurrentUserId();

        if (targetId && currentUserId && targetId.toString() === currentUserId.toString()) {
            console.log('👢 Kicked from session');

            this.showNotification('تم إخراجك من الجلسة', 'error');

            setTimeout(() => {
                this.room?.disconnect();
                window.location.href = data.data.redirect_url || '/';
            }, 2000);
        }
    }

    handleSessionAnnouncement(data) {
        console.log('📢 Session announcement:', data.data.message);
        this.showNotification(data.data.message, 'info');
    }

    handleStateSynchronization(data) {
        console.log('🔄 State synchronization received');

        if (window.meeting?.controls) {
            window.meeting.controls.syncWithServerState(data.data);
        }

        this.lastSyncTimestamp = Date.now();
    }

    handleUnknownCommand(data) {
        console.warn('❓ Unknown command received:', data);

        // Still show notification if there's a message
        if (data.data?.message) {
            this.showNotification(data.data.message, 'info');
        }
    }

    /**
     * Send acknowledgment for received message
     */
    async sendAcknowledgment(messageId, responseData = {}) {
        try {
            const ackData = {
                message_id: messageId,
                participant_id: this.getCurrentUserId(),
                acknowledged_at: new Date().toISOString(),
                response_data: responseData
            };

            await fetch(`/api/sessions/${this.sessionId}/acknowledge`, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.content
                },
                body: JSON.stringify(ackData)
            });

            console.log('✅ Acknowledgment sent for:', messageId);

        } catch (error) {
            console.error('❌ Failed to send acknowledgment:', error);
        }
    }

    /**
     * Setup network monitoring
     */
    setupNetworkMonitoring() {
        window.addEventListener('online', () => {
            console.log('🟢 Network connection restored');
            this.isOnline = true;
            this.handleNetworkReconnect();
        });

        window.addEventListener('offline', () => {
            console.log('🔴 Network connection lost');
            this.isOnline = false;
            this.handleNetworkDisconnect();
        });
    }

    /**
     * Handle network reconnection
     */
    handleNetworkReconnect() {
        // Restart failed channels
        if (!this.channels.websocket) {
            this.setupWebSocketChannel();
        }

        if (!this.channels.sse) {
            this.setupServerSentEvents();
        }

        // Stop polling if primary channels are working
        if (this.channels.livekit || this.channels.websocket) {
            this.stopPolling();
        }

        // Sync state with server
        this.syncStateWithServer();
    }

    /**
     * Handle network disconnection
     */
    handleNetworkDisconnect() {
        // Start polling as fallback
        this.startPolling();
    }

    /**
     * Start state synchronization
     */
    startStateSynchronization() {
        this.syncInterval = setInterval(() => {
            if (this.isOnline) {
                this.syncStateWithServer();
            }
        }, 30000); // Sync every 30 seconds
    }

    /**
     * Sync state with server
     */
    async syncStateWithServer() {
        try {
            const response = await fetch(`/api/sessions/${this.sessionId}/state`, {
                headers: {
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.content
                }
            });

            if (response.ok) {
                const state = await response.json();
                this.handleStateSynchronization({ data: state });
            }
        } catch (error) {
            console.warn('⚠️ State sync failed:', error);
        }
    }

    /**
     * Start polling fallback
     */
    startPolling() {
        if (this.pollingInterval) return;

        console.log('🔄 Starting polling fallback');
        this.channels.polling = true;

        this.pollingInterval = setInterval(async () => {
            try {
                const response = await fetch(`/api/sessions/${this.sessionId}/commands`, {
                    headers: {
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.content
                    }
                });

                if (response.ok) {
                    const commands = await response.json();
                    commands.forEach(command => {
                        this.handleIncomingMessage(command, 'polling');
                    });
                }
            } catch (error) {
                console.warn('⚠️ Polling failed:', error);
            }
        }, 5000); // Poll every 5 seconds
    }

    /**
     * Stop polling
     */
    stopPolling() {
        if (this.pollingInterval) {
            clearInterval(this.pollingInterval);
            this.pollingInterval = null;
            this.channels.polling = false;
            console.log('⏹️ Polling stopped');
        }
    }

    /**
     * Reconnect Server-Sent Events
     */
    reconnectSSE() {
        if (this.reconnectAttempts >= this.maxReconnectAttempts) {
            console.warn('⚠️ Max SSE reconnect attempts reached');
            return;
        }

        this.reconnectAttempts++;
        const delay = Math.pow(2, this.reconnectAttempts) * 1000; // Exponential backoff

        setTimeout(() => {
            console.log(`🔄 Reconnecting SSE (attempt ${this.reconnectAttempts})`);
            this.setupServerSentEvents();
        }, delay);
    }

    /**
     * Show notification to user
     */
    showNotification(message, type = 'info') {
        // Integrate with your existing notification system
        if (window.meeting?.controls?.showNotification) {
            window.meeting.controls.showNotification(message, type);
        } else {
            console.log(`📢 ${type.toUpperCase()}: ${message}`);
        }
    }

    /**
     * Get current user ID
     */
    getCurrentUserId() {
        // Get from your authentication system
        return window.auth?.user?.id || this.config.userId;
    }

    /**
     * Cleanup resources
     */
    destroy() {
        if (this.syncInterval) {
            clearInterval(this.syncInterval);
        }

        this.stopPolling();

        if (this.eventSource) {
            this.eventSource.close();
        }

        this.messageHandlers.clear();
        this.messageHistory.clear();
        this.pendingAcknowledgments.clear();

        console.log('🧹 MeetingDataChannelHandler destroyed');
    }
}

// Global debugging functions
window.debugDataChannel = function () {
    if (window.meeting?.dataChannelHandler) {
        const handler = window.meeting.dataChannelHandler;
        console.log('📡 Data Channel Debug Info:', {
            channels: handler.channels,
            messageHistory: Array.from(handler.messageHistory.entries()),
            isOnline: handler.isOnline,
            lastSync: handler.lastSyncTimestamp,
            sessionId: handler.sessionId,
            role: handler.participantRole
        });
    } else {
        console.log('❌ No data channel handler available');
    }
};

window.testDataChannelDelivery = function () {
    console.log('🧪 Testing data channel delivery...');

    if (window.meeting?.dataChannelHandler) {
        const testMessage = {
            message_id: 'test_' + Date.now(),
            type: 'test',
            command: 'session_announcement',
            data: {
                message: 'This is a test message from console'
            },
            timestamp: new Date().toISOString()
        };

        window.meeting.dataChannelHandler.handleIncomingMessage(testMessage, 'console');
    }
};

// Test teacher controls integration
window.testTeacherMuteAll = async function () {
    console.log('🧪 Testing teacher mute all students...');

    if (window.meeting?.controls && window.meeting.controls.userRole === 'teacher') {
        try {
            await window.meeting.controls.toggleAllStudentsMicrophones();
            console.log('✅ Teacher mute toggle command sent');
        } catch (error) {
            console.error('❌ Teacher mute toggle failed:', error);
        }
    } else {
        console.log('❌ No teacher controls available or user is not a teacher');
    }
};

// Test backend service directly
window.testBackendMuteAll = async function () {
    console.log('🧪 Testing backend mute all service...');

    const sessionId = window.sessionId || prompt('Enter session ID:');
    if (!sessionId) {
        console.log('❌ No session ID provided');
        return;
    }

    try {
        const response = await fetch(`/api/sessions/${sessionId}/commands/mute-all`, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.content
            }
        });

        if (response.ok) {
            const result = await response.json();
            console.log('✅ Backend mute all command sent:', result);
        } else {
            console.error('❌ Backend request failed:', response.status);
        }
    } catch (error) {
        console.error('❌ Backend service error:', error);
    }
};