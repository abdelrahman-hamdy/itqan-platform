/**
 * LiveKit Connection Module
 * Handles Room creation, connection, reconnection, and connection state management
 */

/**
 * Connection manager for LiveKit Room
 */
class LiveKitConnection {
    /**
     * Create a new LiveKit connection manager
     * @param {Object} config - Configuration object
     * @param {string} config.serverUrl - LiveKit server URL
     * @param {string} config.csrfToken - CSRF token for API calls
     * @param {string} config.roomName - Room name to join
     * @param {string} config.participantName - Participant name
     * @param {string} config.role - Participant role (teacher/student)
     * @param {Function} config.onConnectionStateChange - Callback for connection state changes
     * @param {Function} config.onParticipantConnected - Callback for participant connected
     * @param {Function} config.onParticipantDisconnected - Callback for participant disconnected
     * @param {Function} config.onTrackSubscribed - Callback for track subscribed
     * @param {Function} config.onTrackUnsubscribed - Callback for track unsubscribed
     * @param {Function} config.onTrackMuted - Callback for track muted
     * @param {Function} config.onTrackUnmuted - Callback for track unmuted
     * @param {Function} config.onActiveSpeakersChanged - Callback for active speakers changed
     * @param {Function} config.onDataReceived - Callback for data received
     */
    constructor(config) {
        this.config = config;
        this.room = null;
        this.localParticipant = null;
        this.isConnected = false;
        this.isConnecting = false;
        this.reconnectAttempts = 0;
        this.maxReconnectAttempts = 5;

        console.log('🔗 LiveKitConnection initialized');
    }

    /**
     * Create and configure a new LiveKit Room instance
     * @returns {Promise<LiveKit.Room>} Configured room instance
     */
    async createRoom() {
        console.log('🏠 Creating LiveKit room...');

        if (!window.LiveKit) {
            throw new Error('LiveKit SDK not loaded');
        }

        // Create room with TURN servers to bypass network restrictions
        this.room = new window.LiveKit.Room({
            webRtcConfig: {
                iceServers: [
                    { urls: 'stun:stun.l.google.com:19302' },
                    { urls: 'stun:stun1.l.google.com:19302' },
                    // Public TURN servers to bypass network restrictions
                    {
                        urls: 'turn:openrelay.metered.ca:80',
                        username: 'openrelayproject',
                        credential: 'openrelayproject'
                    },
                    {
                        urls: 'turn:openrelay.metered.ca:443',
                        username: 'openrelayproject', 
                        credential: 'openrelayproject'
                    },
                    {
                        urls: 'turn:openrelay.metered.ca:443?transport=tcp',
                        username: 'openrelayproject',
                        credential: 'openrelayproject'
                    }
                ],
                iceTransportPolicy: 'relay'
            }
        });

        this.setupRoomEventListeners();

        console.log('✅ Room created successfully');
        return this.room;
    }

    /**
     * Set up event listeners for the room
     */
    setupRoomEventListeners() {
        if (!this.room) {
            console.error('❌ Room not available for event listeners');
            return;
        }

        console.log('🎧 Setting up room event listeners...');

        // Connection state changes
        this.room.on(window.LiveKit.RoomEvent.ConnectionStateChanged, (state) => {
            console.log('🔗 Connection state changed:', state);
            this.handleConnectionStateChange(state);
        });

        // Participant events
        this.room.on(window.LiveKit.RoomEvent.ParticipantConnected, (participant) => {
            console.log('👤 Participant connected:', participant.identity);
            if (this.config.onParticipantConnected) {
                this.config.onParticipantConnected(participant);
            }
        });

        this.room.on(window.LiveKit.RoomEvent.ParticipantDisconnected, (participant) => {
            console.log('👤 Participant disconnected:', participant.identity);
            if (this.config.onParticipantDisconnected) {
                this.config.onParticipantDisconnected(participant);
            }
        });

        // Track events - these will be handled by the tracks module
        this.room.on(window.LiveKit.RoomEvent.TrackSubscribed, (track, publication, participant) => {
            console.log('📹 Track subscribed:', track.kind, 'from', participant.identity, 'isLocal:', participant.isLocal);
            if (this.config.onTrackSubscribed) {
                this.config.onTrackSubscribed(track, publication, participant);
            }
        });

        this.room.on(window.LiveKit.RoomEvent.TrackUnsubscribed, (track, publication, participant) => {
            console.log('📹 Track unsubscribed:', track.kind, 'from', participant.identity, 'isLocal:', participant.isLocal);
            if (this.config.onTrackUnsubscribed) {
                this.config.onTrackUnsubscribed(track, publication, participant);
            }
        });

        // Local track events - important for local participant
        this.room.on(window.LiveKit.RoomEvent.LocalTrackPublished, (publication, participant) => {
            console.log('📹 Local track published:', publication.kind, 'from', participant.identity);
            if (this.config.onTrackPublished) {
                this.config.onTrackPublished(publication, participant);
            }
        });

        this.room.on(window.LiveKit.RoomEvent.LocalTrackUnpublished, (publication, participant) => {
            console.log('📹 Local track unpublished:', publication.kind, 'from', participant.identity);
            if (this.config.onTrackUnpublished) {
                this.config.onTrackUnpublished(publication, participant);
            }
        });

        this.room.on(window.LiveKit.RoomEvent.TrackMuted, (publication, participant) => {
            console.log('🔇 Track muted:', publication.kind, 'from', participant.identity);
            if (this.config.onTrackMuted) {
                this.config.onTrackMuted(publication, participant);
            }
        });

        this.room.on(window.LiveKit.RoomEvent.TrackUnmuted, (publication, participant) => {
            console.log('🔊 Track unmuted:', publication.kind, 'from', participant.identity);
            if (this.config.onTrackUnmuted) {
                this.config.onTrackUnmuted(publication, participant);
            }
        });

        // Active speakers changed
        this.room.on(window.LiveKit.RoomEvent.ActiveSpeakersChanged, (speakers) => {
            console.log('🗣️ Active speakers changed:', speakers.map(s => s.identity));
            if (this.config.onActiveSpeakersChanged) {
                this.config.onActiveSpeakersChanged(speakers);
            }
        });

        // Data received
        this.room.on(window.LiveKit.RoomEvent.DataReceived, (payload, participant) => {
            console.log('📦 Raw data received from:', participant?.identity);
            console.log('📦 Payload length:', payload?.length);
            console.log('📦 Participant is local:', participant?.isLocal);
            console.log('📦 Participant SID:', participant?.sid);
            console.log('📦 Local participant SID:', this.room?.localParticipant?.sid);
            console.log('📦 Current participants in room:', Array.from(this.room.remoteParticipants.keys()));
            console.log('📦 All participants (including local):', [
                this.room?.localParticipant?.identity,
                ...Array.from(this.room.remoteParticipants.values()).map(p => p.identity)
            ]);

            // Try to decode the payload for debugging
            try {
                const decodedData = JSON.parse(new TextDecoder().decode(payload));
                console.log('📦 Decoded payload:', decodedData);
            } catch (e) {
                console.log('📦 Could not decode payload as JSON:', e.message);
            }

            if (this.config.onDataReceived) {
                console.log('📦 Calling onDataReceived callback');
                this.config.onDataReceived(payload, participant);
            } else {
                console.warn('⚠️ No onDataReceived callback configured');
            }
        });

        console.log('✅ Room event listeners set up successfully');
    }

    /**
     * Connect to LiveKit room with token
     * @param {string} serverUrl - LiveKit server URL
     * @param {string} token - Authentication token
     * @returns {Promise<void>}
     */
    async connect(serverUrl, token) {
        if (!this.room) {
            await this.createRoom();
        }

        if (this.isConnected || this.isConnecting) {
            console.warn('⚠️ Already connected or connecting');
            return;
        }

        this.isConnecting = true;
        console.log('🔌 Connecting to LiveKit room...');

        try {
            await this.room.connect(serverUrl, token);
            console.log('✅ Successfully connected to room');
            this.localParticipant = this.room.localParticipant;
        } catch (error) {
            this.isConnecting = false;
            console.error('❌ Connection failed:', error);
            throw error;
        }
    }

    /**
     * Handle connection state changes
     * @param {string} state - Connection state
     */
    handleConnectionStateChange(state) {
        this.isConnected = state === 'connected';

        if (state === 'connected') {
            this.isConnecting = false;
            this.reconnectAttempts = 0;
            console.log('✅ Connected to room successfully');
            
            // CRITICAL FIX: Record attendance when successfully connected
            this.recordAttendanceJoin();
        } else if (state === 'disconnected') {
            this.isConnecting = false;
            console.log('❌ Disconnected from room');
            
            // CRITICAL FIX: Record attendance when disconnected
            this.recordAttendanceLeave();
            this.handleDisconnection();
        } else if (state === 'reconnecting') {
            this.isConnecting = true;
            console.log('🔄 Reconnecting to room...');
        }

        if (this.config.onConnectionStateChange) {
            this.config.onConnectionStateChange(state);
        }
    }

    /**
     * Handle disconnection and attempt reconnection if needed
     */
    handleDisconnection() {
        console.log('❌ Connection lost - disabling auto-reconnect to prevent spam');
        // Temporarily disable auto-reconnection to stop notification spam
        // User can manually click meeting button to reconnect
    }

    /**
     * Attempt to reconnect to the room
     */
    async reconnect() {
        if (this.isConnecting || this.isConnected) {
            return;
        }

        try {
            console.log('🔄 Attempting to reconnect...');
            this.isConnecting = true;

            // Get a fresh token
            const token = await this.getLiveKitToken();
            if (!token) {
                throw new Error('Failed to get fresh token for reconnection');
            }

            // Reconnect with fresh token
            const serverUrl = this.config.serverUrl || 'wss://test-rn3dlic1.livekit.cloud';
            await this.room.connect(serverUrl, token);
            
            console.log('✅ Reconnected to room successfully');

        } catch (error) {
            console.error('❌ Failed to connect to room:', error);
            this.isConnecting = false;
            throw error;
        }
    }

    /**
     * Get LiveKit token from the server
     * @returns {Promise<string>} LiveKit token
     */
    async getLiveKitToken() {
        console.log('🔑 Getting LiveKit token...');

        try {
            const requestData = {
                room_name: this.config.roomName,
                participant_name: this.config.participantName,
                user_type: this.config.role === 'teacher' ? 'quran_teacher' : 'student'
            };

            console.log('🔑 Sending token request with data:', requestData);

            const response = await fetch('/api/meetings/livekit/token', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': this.config.csrfToken
                },
                credentials: 'same-origin',
                body: JSON.stringify(requestData)
            });

            if (!response.ok) {
                const errorText = await response.text();
                console.error('❌ Server response:', errorText);
                throw new Error(`HTTP error! status: ${response.status} - ${errorText}`);
            }

            const data = await response.json();

            if (!data.token) {
                throw new Error('Invalid token response: ' + (data.error || 'Unknown error'));
            }

            console.log('✅ Token received successfully');
            return data.token;

        } catch (error) {
            console.error('❌ Failed to get LiveKit token:', error);
            throw error;
        }
    }


    /**
     * Disconnect from the room
     */
    async disconnect() {
        if (this.room && this.isConnected) {
            console.log('🔌 Disconnecting from room...');
            
            await this.room.disconnect();
            this.isConnected = false;
            this.isConnecting = false;
            console.log('✅ Disconnected from room');
        }
    }

    /**
     * Get the current room instance
     * @returns {LiveKit.Room|null}
     */
    getRoom() {
        return this.room;
    }

    /**
     * Get the local participant
     * @returns {LiveKit.LocalParticipant|null}
     */
    getLocalParticipant() {
        return this.localParticipant;
    }

    /**
     * Check if currently connected
     * @returns {boolean}
     */
    isRoomConnected() {
        return this.isConnected;
    }

    /**
     * Check if currently connecting
     * @returns {boolean}
     */
    isRoomConnecting() {
        return this.isConnecting;
    }

    /**
     * Record attendance join via API (fallback for webhook issues)
     */
    async recordAttendanceJoin() {
        try {
            console.log('📝 Recording attendance join...');
            
            // Extract session ID from room name (format: academy-session-type-session-id)
            const sessionId = this.extractSessionIdFromRoomName(this.config.roomName);
            if (!sessionId) {
                console.warn('⚠️ Could not extract session ID from room name:', this.config.roomName);
                return;
            }

            const response = await fetch('/api/meetings/attendance/join', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': this.config.csrfToken
                },
                body: JSON.stringify({
                    session_id: sessionId,
                    room_name: this.config.roomName
                })
            });

            if (response.ok) {
                const data = await response.json();
                console.log('✅ Attendance join recorded:', data);
            } else {
                const error = await response.text();
                console.warn('⚠️ Failed to record attendance join:', error);
            }
        } catch (error) {
            console.error('❌ Error recording attendance join:', error);
        }
    }

    /**
     * Record attendance leave via API (fallback for webhook issues)
     */
    async recordAttendanceLeave() {
        try {
            console.log('📝 Recording attendance leave...');
            
            const sessionId = this.extractSessionIdFromRoomName(this.config.roomName);
            if (!sessionId) {
                console.warn('⚠️ Could not extract session ID from room name:', this.config.roomName);
                return;
            }

            const response = await fetch('/api/meetings/attendance/leave', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': this.config.csrfToken
                },
                body: JSON.stringify({
                    session_id: sessionId,
                    room_name: this.config.roomName
                })
            });

            if (response.ok) {
                const data = await response.json();
                console.log('✅ Attendance leave recorded:', data);
            } else {
                const error = await response.text();
                console.warn('⚠️ Failed to record attendance leave:', error);
            }
        } catch (error) {
            console.error('❌ Error recording attendance leave:', error);
        }
    }

    /**
     * Extract session ID from room name
     * Expected format: academy-session-type-session-id (e.g., "itqan-academy-individual-session-29")
     */
    extractSessionIdFromRoomName(roomName) {
        if (!roomName) return null;
        
        const parts = roomName.split('-');
        if (parts.length >= 4 && parts[parts.length - 2] === 'session') {
            const sessionId = parseInt(parts[parts.length - 1]);
            return isNaN(sessionId) ? null : sessionId;
        }
        
        return null;
    }

    /**
     * Destroy the connection and clean up
     */
    destroy() {
        console.log('🧹 Destroying LiveKit connection...');

        // Record leave when destroying connection
        if (this.isConnected) {
            this.recordAttendanceLeave();
        }

        if (this.room) {
            this.room.removeAllListeners();
            if (this.isConnected) {
                this.room.disconnect();
            }
            this.room = null;
        }

        this.localParticipant = null;
        this.isConnected = false;
        this.isConnecting = false;
        this.reconnectAttempts = 0;

        console.log('✅ Connection destroyed');
    }
}

// Make class globally available
window.LiveKitConnection = LiveKitConnection;
