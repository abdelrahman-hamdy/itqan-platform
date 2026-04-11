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
        this.intentionalDisconnect = false;
        this.reconnectTimeoutId = null;
        // Stability timer: only reset reconnectAttempts after the connection
        // has been stable for this long. Prevents infinite loops where a
        // client connects (reaching 'connected'), immediately disconnects,
        // reconnects again, and resets the counter each cycle — bypassing
        // maxReconnectAttempts entirely.
        this.stabilityTimerId = null;
        this.stabilityWindowMs = 30000;
    }

    /**
     * Build LiveKit RoomOptions — passed to `new Room(options)`.
     * Audio pipeline: speech-preset Opus (voice-optimized) with AEC/NS/AGC on.
     * Video config is intentionally left on SDK defaults (stable VP8, auto simulcast).
     */
    getRoomOptions() {
        return {
            adaptiveStream: true,
            dynacast: true,
            audioCaptureDefaults: {
                echoCancellation: true,
                noiseSuppression: true,
                autoGainControl: true,
            },
            publishDefaults: {
                audioPreset: window.LiveKit.AudioPresets.speech, // ~32 kbps, voice-optimized
                dtx: true,
                red: true,
            },
        };
    }

    /**
     * Build LiveKit RoomConnectOptions — passed to `room.connect(url, token, options)`.
     * Only fields defined in RoomConnectOptions belong here (autoSubscribe, rtcConfig, etc.).
     * Fields belonging to RoomOptions are silently ignored by connect() — see getRoomOptions().
     */
    getConnectOptions() {
        return {
            autoSubscribe: true,
        };
    }

    /**
     * Create and configure a new LiveKit Room instance
     * @returns {Promise<LiveKit.Room>} Configured room instance
     */
    async createRoom() {

        if (!window.LiveKit) {
            throw new Error('LiveKit SDK not loaded');
        }

        // Let LiveKit server provide TURN credentials automatically via signaling.
        // No client-side ICE override needed (matches mobile app behavior).
        // RoomOptions (audioCaptureDefaults, publishDefaults, adaptiveStream, dynacast)
        // MUST be passed to the Room constructor — they are silently ignored if passed to connect().
        this.room = new window.LiveKit.Room(this.getRoomOptions());

        this.setupRoomEventListeners();

        if (window.MT) window.MT.event('connection', 'room_created', { sdkVersion: (window.LiveKit && window.LiveKit.version) || null });
        return this.room;
    }

    /**
     * Set up event listeners for the room
     */
    setupRoomEventListeners() {
        if (!this.room) {
            return;
        }


        // Connection state changes
        this.room.on(window.LiveKit.RoomEvent.ConnectionStateChanged, (state) => {
            this.handleConnectionStateChange(state);
        });

        // Disconnected event — fires with a reason when room is left
        this.room.on(window.LiveKit.RoomEvent.Disconnected, (reason) => {
            // reason can be: CLIENT_INITIATED, DUPLICATE_IDENTITY, SERVER_SHUTDOWN,
            // PARTICIPANT_REMOVED, ROOM_DELETED, STATE_MISMATCH, JOIN_FAILURE, etc.
            this.lastDisconnectReason = reason;
        });

        // Participant events
        this.room.on(window.LiveKit.RoomEvent.ParticipantConnected, (participant) => {
            if (this.config.onParticipantConnected) {
                this.config.onParticipantConnected(participant);
            }
        });

        this.room.on(window.LiveKit.RoomEvent.ParticipantDisconnected, (participant) => {
            if (this.config.onParticipantDisconnected) {
                this.config.onParticipantDisconnected(participant);
            }
        });

        // Track events - these will be handled by the tracks module
        this.room.on(window.LiveKit.RoomEvent.TrackSubscribed, (track, publication, participant) => {
            if (this.config.onTrackSubscribed) {
                this.config.onTrackSubscribed(track, publication, participant);
            }
        });

        this.room.on(window.LiveKit.RoomEvent.TrackUnsubscribed, (track, publication, participant) => {
            if (this.config.onTrackUnsubscribed) {
                this.config.onTrackUnsubscribed(track, publication, participant);
            }
        });

        // Local track events - important for local participant
        this.room.on(window.LiveKit.RoomEvent.LocalTrackPublished, (publication, participant) => {
            if (this.config.onTrackPublished) {
                this.config.onTrackPublished(publication, participant);
            }
        });

        this.room.on(window.LiveKit.RoomEvent.LocalTrackUnpublished, (publication, participant) => {
            if (this.config.onTrackUnpublished) {
                this.config.onTrackUnpublished(publication, participant);
            }
        });

        this.room.on(window.LiveKit.RoomEvent.TrackMuted, (publication, participant) => {
            if (this.config.onTrackMuted) {
                this.config.onTrackMuted(publication, participant);
            }
        });

        this.room.on(window.LiveKit.RoomEvent.TrackUnmuted, (publication, participant) => {
            if (this.config.onTrackUnmuted) {
                this.config.onTrackUnmuted(publication, participant);
            }
        });

        // Active speakers changed
        this.room.on(window.LiveKit.RoomEvent.ActiveSpeakersChanged, (speakers) => {
            if (this.config.onActiveSpeakersChanged) {
                this.config.onActiveSpeakersChanged(speakers);
            }
        });

        // Data received
        this.room.on(window.LiveKit.RoomEvent.DataReceived, (payload, participant) => {
            if (this.config.onDataReceived) {
                this.config.onDataReceived(payload, participant);
            }
        });

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
            return;
        }

        this.isConnecting = true;

        if (window.MT) window.MT.event('connection', 'connect_started', { server: serverUrl, hasToken: !!token });
        const t0 = Date.now();
        try {
            await this.room.connect(serverUrl, token, this.getConnectOptions());
            this.localParticipant = this.room.localParticipant;
            if (window.MT) window.MT.event('connection', 'connect_succeeded', { ms: Date.now() - t0 });
            // Begin sampling WebRTC stats now that we have a live room
            if (window.MT) window.MT.startStatsPolling(this.room);
        } catch (error) {
            this.isConnecting = false;
            if (window.MT) window.MT.error('connection', 'connect_failed', error, { ms: Date.now() - t0 });
            throw error;
        }
    }

    /**
     * Handle connection state changes
     * @param {string} state - Connection state
     */
    handleConnectionStateChange(state) {
        this.isConnected = state === 'connected';

        if (window.MT) window.MT.event('connection', 'state_change', {
            state,
            attempts: this.reconnectAttempts,
            max_attempts: this.maxReconnectAttempts,
            reason: this.lastDisconnectReason || null,
        });

        if (state === 'connected') {
            this.isConnecting = false;

            // Defer the reconnectAttempts reset until the connection has been
            // stable for stabilityWindowMs. A rapid connect → disconnect cycle
            // will cancel the timer via the 'disconnected' branch below, so the
            // counter keeps climbing and maxReconnectAttempts eventually kicks in.
            if (this.stabilityTimerId) clearTimeout(this.stabilityTimerId);
            const attemptsAtConnect = this.reconnectAttempts;
            this.stabilityTimerId = setTimeout(() => {
                if (window.MT) window.MT.event('connection', 'stability_reached', {
                    window_ms: this.stabilityWindowMs,
                    attempts_cleared: attemptsAtConnect,
                });
                this.reconnectAttempts = 0;
                this.stabilityTimerId = null;
            }, this.stabilityWindowMs);

            // CRITICAL FIX: Record attendance when successfully connected
            this.recordAttendanceJoin();
        } else if (state === 'disconnected') {
            this.isConnecting = false;

            // Cancel any pending stability timer — the connection didn't stay
            // up long enough to count as successful.
            if (this.stabilityTimerId) {
                clearTimeout(this.stabilityTimerId);
                this.stabilityTimerId = null;
                if (window.MT) window.MT.event('connection', 'stability_cancelled', { window_ms: this.stabilityWindowMs });
            }

            // CRITICAL FIX: Record attendance when disconnected
            this.recordAttendanceLeave();
            this.handleDisconnection();
        } else if (state === 'reconnecting') {
            this.isConnecting = true;
        }

        if (this.config.onConnectionStateChange) {
            // Pass extra context so the UI can distinguish intentional vs unexpected disconnect
            this.config.onConnectionStateChange(state, {
                intentional: this.intentionalDisconnect,
                reason: this.lastDisconnectReason || null,
            });
        }
    }

    /**
     * Handle disconnection and attempt reconnection if needed.
     * This fires AFTER the LiveKit SDK's internal reconnection retries are exhausted.
     * It gets a fresh token and creates a new connection with exponential backoff.
     */
    handleDisconnection() {
        if (this.intentionalDisconnect) {
            if (window.MT) window.MT.event('connection', 'disconnect_intentional', {});
            return;
        }
        if (this.reconnectAttempts >= this.maxReconnectAttempts) {
            if (window.MT) window.MT.warn('connection', 'reconnect_exhausted', {
                attempts: this.reconnectAttempts,
                max: this.maxReconnectAttempts,
                reason: this.lastDisconnectReason || null,
            });
            return;
        }

        this.reconnectAttempts++;
        const delay = Math.min(1000 * Math.pow(2, this.reconnectAttempts - 1), 16000);

        if (window.MT) window.MT.event('connection', 'reconnect_scheduled', {
            attempt: this.reconnectAttempts,
            max: this.maxReconnectAttempts,
            delay_ms: delay,
            reason: this.lastDisconnectReason || null,
        });

        if (this.reconnectTimeoutId) clearTimeout(this.reconnectTimeoutId);
        this.reconnectTimeoutId = setTimeout(async () => {
            this.reconnectTimeoutId = null;
            if (this.intentionalDisconnect || !this.room) return;
            try {
                await this.reconnect();
            } catch (error) {
                // Will re-enter handleDisconnection via state change if still disconnected
            }
        }, delay);
    }

    /**
     * Attempt to reconnect to the room
     */
    async reconnect() {
        if (this.isConnecting || this.isConnected) {
            return;
        }

        try {
            this.isConnecting = true;

            // Get a fresh token
            const token = await this.getLiveKitToken();
            if (!token) {
                throw new Error('Failed to get fresh token for reconnection');
            }

            const serverUrl = this.config.serverUrl;
            if (!serverUrl) {
                throw new Error('LiveKit server URL not configured');
            }
            await this.room.connect(serverUrl, token, this.getConnectOptions());

        } catch (error) {
            this.isConnecting = false;
            throw error;
        }
    }

    /**
     * Get LiveKit token from the unified session API
     * @returns {Promise<string>} LiveKit token
     */
    async getLiveKitToken() {
        const t0 = Date.now();
        if (window.MT) window.MT.event('connection', 'token_fetch_started', {});

        try {
            // Get session ID from window object (set in Blade template)
            const sessionId = window.sessionId;
            if (!sessionId) {
                throw new Error('Session ID not found. Please refresh the page.');
            }


            // Get session type from window object (set in Blade template)
            const sessionType = window.sessionType || 'quran';

            // Use unified API endpoint for getting participant token
            const response = await window.LiveKitAPI.post('/api/sessions/meeting/token', {
                session_type: sessionType,
                session_id: sessionId
            });

            if (!response.ok) {
                const errorText = await response.text();
                throw new Error(`HTTP error! status: ${response.status} - ${errorText}`);
            }

            const data = await response.json();

            if (!data.success || !data.data?.access_token) {
                throw new Error('Invalid token response: ' + (data.message || data.error || 'Unknown error'));
            }

            if (window.MT) window.MT.event('connection', 'token_fetch_succeeded', { ms: Date.now() - t0 });
            return data.data.access_token;

        } catch (error) {
            if (window.MT) window.MT.error('connection', 'token_fetch_failed', error, { ms: Date.now() - t0 });
            throw error;
        }
    }


    /**
     * Disconnect from the room
     */
    async disconnect() {
        if (this.room && this.isConnected) {
            this.intentionalDisconnect = true;
            await this.room.disconnect();
            this.isConnected = false;
            this.isConnecting = false;
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
     * Check if all reconnection attempts have been exhausted
     * @returns {boolean}
     */
    isReconnectExhausted() {
        return this.reconnectAttempts >= this.maxReconnectAttempts;
    }

    /**
     * Record attendance join via unified API (fallback for webhook issues)
     */
    async recordAttendanceJoin() {
        try {
            
            // Get session ID and type from window object (set in Blade template)
            const sessionId = window.sessionId;
            const sessionType = window.sessionType || 'quran';
            
            if (!sessionId) {
                return;
            }

            // This is handled automatically by the unified API when generating token
            // But we can call the leave endpoint just to be sure

        } catch (error) {
        }
    }

    /**
     * Record attendance leave via unified API (fallback for webhook issues)
     */
    async recordAttendanceLeave() {
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
        if (window.MT) {
            window.MT.event('connection', 'destroy_called', {});
            window.MT.stopStatsPolling();
        }

        // Clear pending reconnect timer
        if (this.reconnectTimeoutId) {
            clearTimeout(this.reconnectTimeoutId);
            this.reconnectTimeoutId = null;
        }

        // Clear pending stability timer
        if (this.stabilityTimerId) {
            clearTimeout(this.stabilityTimerId);
            this.stabilityTimerId = null;
        }

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

    }
}

// Make class globally available
window.LiveKitConnection = LiveKitConnection;
