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
                audioPreset: { maxBitrate: 64_000 }, // 64kbps — higher quality for Quran recitation (was 32kbps speech preset)
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
            // Apply noise suppression to outbound audio (RNNoise WASM)
            if (publication.source === window.LiveKit.Track.Source.Microphone && publication.track) {
                this.applyNoiseSuppression(publication.track);
            }
            if (this.config.onTrackPublished) {
                this.config.onTrackPublished(publication, participant);
            }
        });

        // iOS Safari blocks audio autoplay without a user gesture.
        // Show a tap-to-hear prompt when playback is blocked, then call startAudio().
        this.room.on(window.LiveKit.RoomEvent.AudioPlaybackStatusChanged, () => {
            if (!this.room.canPlaybackAudio) {
                this.showAudioPlaybackPrompt();
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
     * Apply RNNoise-based noise suppression to the local audio track.
     * Uses @shiguredo/noise-suppression (WASM, runs client-side, ~2ms latency).
     * Fails silently on unsupported browsers — audio works without suppression.
     */
    async applyNoiseSuppression(localAudioTrack) {
        try {
            // Skip on low-end devices — WASM AudioWorklet causes jitter on <4GB RAM
            const mem = navigator.deviceMemory || 8; // defaults to 8 if unsupported (desktop)
            const cores = navigator.hardwareConcurrency || 4;
            if (mem < 4 || cores < 4) {
                if (window.MT) window.MT.event('audio', 'noise_suppression_skipped_low_end', { mem, cores });
                return;
            }

            if (this.noiseProcessor) {
                this.noiseProcessor.stopProcessing();
                this.noiseProcessor = null;
            }
            if (this._noiseGainCtx) {
                this._noiseGainCtx.close();
                this._noiseGainCtx = null;
            }
            const { NoiseSuppressionProcessor } = await import('/js/livekit/noise-suppression/noise_suppression.js');
            if (!NoiseSuppressionProcessor.isSupported()) {
                if (window.MT) window.MT.event('audio', 'noise_suppression_unsupported', {});
                return;
            }
            this.noiseProcessor = new NoiseSuppressionProcessor();
            const mediaTrack = localAudioTrack.mediaStreamTrack;
            const denoisedTrack = await this.noiseProcessor.startProcessing(mediaTrack);

            // RNNoise reduces volume as a side-effect of per-band gain attenuation.
            // Apply a 1.5x gain boost to compensate without clipping.
            const ctx = new AudioContext({ sampleRate: 48000 });
            const source = ctx.createMediaStreamSource(new MediaStream([denoisedTrack]));
            const gain = ctx.createGain();
            gain.gain.value = 1.5;
            const dest = ctx.createMediaStreamDestination();
            source.connect(gain).connect(dest);
            this._noiseGainCtx = ctx;

            await localAudioTrack.replaceTrack(dest.stream.getAudioTracks()[0]);
            if (window.MT) window.MT.event('audio', 'noise_suppression_enabled', { mem, cores, gain: 1.5 });
        } catch (e) {
            console.warn('Noise suppression not available:', e.message);
        }
    }

    /**
     * Show a prompt for iOS Safari users when audio autoplay is blocked.
     * Calls room.startAudio() on tap to unlock playback.
     */
    showAudioPlaybackPrompt() {
        if (document.getElementById('audioPlaybackPrompt')) return;

        const prompt = document.createElement('div');
        prompt.id = 'audioPlaybackPrompt';
        prompt.style.cssText = 'position:fixed;top:0;left:0;right:0;bottom:0;z-index:9999;background:rgba(0,0,0,0.7);display:flex;align-items:center;justify-content:center;';
        prompt.innerHTML = `
            <div style="background:white;border-radius:16px;padding:32px 24px;text-align:center;max-width:320px;margin:16px;">
                <div style="font-size:48px;margin-bottom:16px;">🔊</div>
                <p style="font-size:18px;font-weight:600;margin-bottom:8px;color:#1e293b;">تفعيل الصوت</p>
                <p style="font-size:14px;color:#64748b;margin-bottom:20px;">اضغط الزر لسماع صوت المشاركين</p>
                <button id="audioPlaybackBtn" style="background:#2563eb;color:white;border:none;padding:14px 32px;border-radius:10px;font-size:16px;font-weight:600;cursor:pointer;width:100%;">
                    تفعيل الصوت
                </button>
            </div>
        `;

        document.body.appendChild(prompt);

        document.getElementById('audioPlaybackBtn').addEventListener('click', async () => {
            try {
                await this.room.startAudio();
                if (window.MT) window.MT.event('audio', 'autoplay_unlocked', {});
            } catch (e) {
                console.warn('startAudio failed:', e.message);
            }
            prompt.remove();
        });

        if (window.MT) window.MT.event('audio', 'autoplay_blocked_prompt_shown', {});
    }

    /**
     * Disconnect from the room
     */
    async disconnect() {
        if (this.noiseProcessor) {
            this.noiseProcessor.stopProcessing();
            this.noiseProcessor = null;
        }
        if (this._noiseGainCtx) {
            this._noiseGainCtx.close();
            this._noiseGainCtx = null;
        }
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
