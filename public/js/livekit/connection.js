/**
 * LiveKit Connection Module
 * Handles Room creation, connection, reconnection, and connection state management
 */

/**
 * Computes encoder bitrate targets from LiveKit connection-quality events
 * using hysteresis, so transient flips don't renegotiate the RTP encoding
 * (each renegotiation is an audible micro-glitch).
 */
class AdaptiveBitrateController {
    // Require 3 consecutive "poor" quality events (~30 s) to downshift and
    // 6 consecutive "excellent" (~60 s) to upshift. "good" holds position.
    // Numbers chosen from telemetry: the adaptive handler was flipping
    // 12 times per 5-hour session; these streak thresholds bring it to ~1–2.
    static DOWNSHIFT_STREAK = 3;
    static UPSHIFT_STREAK = 6;
    static BITRATES = Object.freeze({ excellent: 64_000, good: 48_000, poor: 32_000 });

    constructor() {
        this.reset();
    }

    reset() {
        this._streak = { q: null, count: 0 };
        this._lastApplied = null;
    }

    // Returns `{ quality, bitrate, streak }` when a change should be applied,
    // or `null` when the quality event is a no-op (streak not reached, "good",
    // or repeat of the already-applied bitrate).
    update(quality) {
        const q = quality === window.LiveKit.ConnectionQuality.Excellent ? 'excellent'
                : quality === window.LiveKit.ConnectionQuality.Good ? 'good' : 'poor';
        this._streak.count = this._streak.q === q ? this._streak.count + 1 : 1;
        this._streak.q = q;

        const target = q === 'poor' && this._streak.count >= AdaptiveBitrateController.DOWNSHIFT_STREAK
                ? AdaptiveBitrateController.BITRATES.poor
            : q === 'excellent' && this._streak.count >= AdaptiveBitrateController.UPSHIFT_STREAK
                ? AdaptiveBitrateController.BITRATES.excellent
            : null;
        if (target === null || target === this._lastApplied) return null;

        this._lastApplied = target;
        return { quality: q, bitrate: target, streak: this._streak.count };
    }
}

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
        this.intentionalDisconnect = false;
        this._bitrateController = new AdaptiveBitrateController();

        // Debounce state for ActiveSpeakersChanged — SDK fires up to 10/s
        // during speech; coalescing avoids a DOM rebuild storm.
        this._activeSpeakerIds = new Set();
        this._activeSpeakerTimer = null;

        // Housekeeping: wipe the legacy RNNoise opt-in flag on any browser
        // that still has it set. The ML denoiser was removed entirely after
        // the Insertable-Streams pipeline progressively paralyzed Chrome tabs.
        try { if (typeof localStorage !== 'undefined') localStorage.removeItem('enhanced_nr_v2'); } catch (_) {}
        this._wakeLock = null;
        this._keepAliveAudio = null;
        this._visibilityHandler = null;
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
            disconnectOnPageLeave: true,
            stopLocalTrackOnUnpublish: false,
            audioCaptureDefaults: {
                echoCancellation: true,
                noiseSuppression: true,
                autoGainControl: true,
                voiceIsolation: false,
                channelCount: 1,
            },
            publishDefaults: {
                // Fix #5: use the SDK's speech preset constant. The previous
                // raw `{ maxBitrate: 48_000 }` object didn't match the
                // AudioPreset type and may have been silently ignored,
                // falling back to the music preset (worse for voice).
                audioPreset: window.LiveKit.AudioPresets.speech,
                dtx: true,
                red: true,
                // Fix #6: do NOT force videoCodec. Let the SDK negotiate so
                // Safari gets H.264 (VP9 simulcast broken there) and Chrome
                // gets VP9 where available. H.264 simulcast was unreliable
                // on some browsers, silently disabling bandwidth adaptation
                // and pulling the whole room down to the worst peer.
                simulcast: true,
                videoSimulcastLayers: [
                    window.LiveKit.VideoPresets.h180,
                    window.LiveKit.VideoPresets.h360,
                    window.LiveKit.VideoPresets.h720,
                ],
            },
            // Fix #4: widen SDK reconnect window so mobile WiFi↔cellular
            // handovers (10–15 s common) stay within ceiling. The custom
            // handleDisconnection/reconnect layer below has been deleted
            // (fix #3), so this is now the sole reconnect authority.
            reconnectPolicy: {
                nextRetryDelayInMs: (context) => {
                    if (context.retryCount > 6) return null;
                    return Math.min(500 * Math.pow(2, context.retryCount), 15_000);
                },
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
            // Fix #3: removed maxRetries:5 (was a third retry layer stacking
            // on top of SDK reconnectPolicy and the now-deleted custom
            // handleDisconnection path).
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

        // Active speakers changed — debounced with set-equality guard
        // (fix #7). Skip when the membership hasn't actually changed, and
        // coalesce bursts into a single 250 ms callback. Mirrors the mobile
        // pattern in itqan-mobile/lib/services/livekit_service.dart.
        this.room.on(window.LiveKit.RoomEvent.ActiveSpeakersChanged, (speakers) => {
            // Cheap membership check first so we avoid allocating a Set on
            // every SDK tick (~10/sec during speech). Only materialize the
            // new set when the membership has actually changed.
            const prev = this._activeSpeakerIds;
            if (speakers.length === prev.size && speakers.every((s) => prev.has(s.identity))) return;
            this._activeSpeakerIds = new Set(speakers.map((s) => s.identity));
            if (this._activeSpeakerTimer) return; // already coalescing
            this._activeSpeakerTimer = setTimeout(() => {
                this._activeSpeakerTimer = null;
                const currentSpeakers = speakers.filter((s) => this._activeSpeakerIds.has(s.identity));
                if (this.config.onActiveSpeakersChanged) {
                    this.config.onActiveSpeakersChanged(currentSpeakers);
                }
                // Broadcast whether the local participant is currently speaking
                // so the soft-ducker (audio-ducking.js) can attenuate remote
                // audio playback during local speech and reduce the echo the
                // local mic picks back up.
                const localSid = this.room?.localParticipant?.sid;
                const speaking = !!(localSid && currentSpeakers.some((s) => s.sid === localSid));
                window.dispatchEvent(new CustomEvent('livekit-local-speaking', {
                    detail: { speaking },
                }));
            }, 250);
        });

        // Data received
        this.room.on(window.LiveKit.RoomEvent.DataReceived, (payload, participant) => {
            if (this.config.onDataReceived) {
                this.config.onDataReceived(payload, participant);
            }
        });

        this.room.on(window.LiveKit.RoomEvent.ConnectionQualityChanged, (quality, participant) => {
            if (!participant.isLocal) return;
            const change = this._bitrateController.update(quality);
            if (!change) return;

            const pub = this.room.localParticipant.getTrackPublication(window.LiveKit.Track.Source.Microphone);
            if (pub?.track?.sender) {
                try {
                    const params = pub.track.sender.getParameters();
                    if (params.encodings && params.encodings.length > 0) {
                        params.encodings[0].maxBitrate = change.bitrate;
                        pub.track.sender.setParameters(params).catch(() => {});
                    }
                } catch (_) { /* sender may not support getParameters on all browsers */ }
            }
            if (window.MT) window.MT.event('connection', 'quality_adaptive_bitrate', change);
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
            // Keep meeting alive when browser goes to background
            this._acquireWakeLock();
            this._startKeepAliveAudio();
            this._setupVisibilityHandler();
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
            reason: this.lastDisconnectReason || null,
        });

        if (state === 'connected') {
            this.isConnecting = false;
            // The encoder restarts at publishDefaults.audioPreset on every
            // (re)connect, so a cached target from the prior session would
            // falsely suppress the first real quality-driven change.
            this._bitrateController.reset();
            this.recordAttendanceJoin();
        } else if (state === 'disconnected') {
            this.isConnecting = false;
            if (window.MT) window.MT.stopStatsPolling();
            this.recordAttendanceLeave();
            // Fix #3: deleted the handleDisconnection() call. SDK's built-in
            // reconnectPolicy (widened in fix #4 to 6 retries / 15s cap)
            // handles recovery. The UI layer owns any "rejoin" CTA.
        } else if (state === 'reconnecting') {
            this.isConnecting = true;
        }

        if (this.config.onConnectionStateChange) {
            this.config.onConnectionStateChange(state, {
                intentional: this.intentionalDisconnect,
                reason: this.lastDisconnectReason || null,
            });
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
            // Only dismiss if audio is actually playing now
            if (this.room.canPlaybackAudio) {
                prompt.remove();
            } else {
                const btn = document.getElementById('audioPlaybackBtn');
                if (btn) btn.textContent = window.meetingTranslations?.buttons?.retry || 'Try again';
                if (window.MT) window.MT.warn('audio', 'autoplay_unlock_failed_keep_prompt', {});
            }
        });

        if (window.MT) window.MT.event('audio', 'autoplay_blocked_prompt_shown', {});
    }

    /**
     * Acquire screen wake lock to prevent screen dimming during meetings.
     * Released automatically by the browser when tab is backgrounded,
     * re-acquired when tab becomes visible again.
     */
    async _acquireWakeLock() {
        try {
            if (!('wakeLock' in navigator)) return;
            this._wakeLock = await navigator.wakeLock.request('screen');
        } catch (e) { /* Low battery or not supported */ }
    }

    _releaseWakeLock() {
        if (this._wakeLock) {
            this._wakeLock.release().catch(() => {});
            this._wakeLock = null;
        }
    }

    /**
     * Play a silent audio loop to prevent the OS from killing the tab.
     * Uses a hidden <audio> element (not AudioContext) to avoid
     * interfering with the noise suppression pipeline.
     */
    _startKeepAliveAudio() {
        if (this._keepAliveAudio) return;
        // Zero-length silent WAV (header only). Looping keeps the tab alive in background.
        const silentWav = 'data:audio/wav;base64,UklGRiQAAABXQVZFZm10IBAAAAABAAEARKwAABCxAgABAAgAZGF0YQAAAAA=';
        this._keepAliveAudio = new Audio(silentWav);
        this._keepAliveAudio.loop = true;
        this._keepAliveAudio.volume = 0.01;
        this._keepAliveAudio.play().catch(() => {});
    }

    _stopKeepAliveAudio() {
        if (this._keepAliveAudio) {
            this._keepAliveAudio.pause();
            this._keepAliveAudio.src = '';
            this._keepAliveAudio = null;
        }
    }

    /**
     * Handle visibility changes — re-acquire wake lock when tab becomes
     * visible, and trigger immediate reconnection if connection was lost
     * while backgrounded.
     */
    _setupVisibilityHandler() {
        if (this._visibilityHandler) return;
        this._visibilityHandler = () => {
            if (document.visibilityState !== 'visible') return;
            // Re-acquire wake lock (browser releases it on background)
            this._acquireWakeLock();
            // If disconnected while backgrounded, trigger reconnection immediately
            // Fix #3: removed the manual handleDisconnection() reentry. The
            // SDK's reconnectPolicy already tries to recover when the tab
            // comes back to foreground — kicking off a second parallel
            // reconnect here was part of the race that caused ghost
            // connections.
        };
        document.addEventListener('visibilitychange', this._visibilityHandler);
    }

    _teardownVisibilityHandler() {
        if (this._visibilityHandler) {
            document.removeEventListener('visibilitychange', this._visibilityHandler);
            this._visibilityHandler = null;
        }
    }

    /**
     * Disconnect from the room
     */
    async disconnect() {
        if (window.MT) window.MT.stopStatsPolling();
        this._releaseWakeLock();
        this._stopKeepAliveAudio();
        this._teardownVisibilityHandler();
        if (this._activeSpeakerTimer) {
            clearTimeout(this._activeSpeakerTimer);
            this._activeSpeakerTimer = null;
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

        if (this._activeSpeakerTimer) {
            clearTimeout(this._activeSpeakerTimer);
            this._activeSpeakerTimer = null;
        }

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
    }

    /**
     * Synchronous teardown for page-unload paths. The async `disconnect()`
     * relies on `await`, which browsers do not honour during `beforeunload` —
     * so local MediaStreamTracks linger past document teardown. That hangs
     * the compositor and produces a black tab until the user reloads again.
     * This method releases those resources immediately.
     */
    destroySync() {
        this.intentionalDisconnect = true;

        if (this._activeSpeakerTimer) {
            clearTimeout(this._activeSpeakerTimer);
            this._activeSpeakerTimer = null;
        }

        this._releaseWakeLock();
        this._stopKeepAliveAudio();
        this._teardownVisibilityHandler();

        // Stop every local MediaStreamTrack so the browser releases mic/camera
        // hardware and GPU-backed Insertable Streams buffers before the new
        // document starts loading.
        try {
            const lp = this.room?.localParticipant;
            if (lp) {
                const pubs = lp.trackPublications?.values?.() ?? [];
                for (const pub of pubs) {
                    try { pub.track?.mediaStreamTrack?.stop(); } catch (_) {}
                }
            }
        } catch (_) {}

        if (this.room) {
            try { this.room.removeAllListeners(); } catch (_) {}
            try { this.room.disconnect(); } catch (_) {}
            this.room = null;
        }

        this.localParticipant = null;
        this.isConnected = false;
        this.isConnecting = false;
    }
}

// Make class globally available
window.LiveKitConnection = LiveKitConnection;
