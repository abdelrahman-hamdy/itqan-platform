/**
 * FIXED: LiveKit Meeting Integration - Main Entry Point
 * CRITICAL FIXES: Eliminated race conditions, improved state synchronization, robust track management
 */

class LiveKitMeetingFixed {
    constructor(config) {
        this.config = config;
        this.isInitialized = false;
        this.isConnected = false;
        this.isDestroyed = false;

        // Module instances
        this.connection = null;
        this.participants = null;
        this.tracks = null;
        this.layout = null;
        this.controls = null;

        // CRITICAL FIX: Track synchronization state
        this.participantStates = new Map(); // participantId -> comprehensive state
        this.initializationQueue = new Map(); // participantId -> Promise
        this.syncInProgress = new Set(); // Track which participants are being synced

        console.log('🚀 LiveKitMeetingFixed initialized with robust sync');
    }

    async init() {
        if (this.isInitialized) {
            console.log('⚠️ Meeting already initialized');
            return;
        }

        try {
            console.log('🔧 Initializing LiveKit meeting modules (FIXED)...');

            // CRITICAL FIX: Initialize modules in strict order with proper error handling
            await this.initializeModulesSequentially();

            // Get token and connect
            const token = await this.connection.getLiveKitToken();
            const serverUrl = this.config.serverUrl || 'wss://test-rn3dlic1.livekit.cloud';
            await this.connection.connect(serverUrl, token);

            // CRITICAL FIX: Setup local media with proper state tracking
            await this.setupLocalMediaRobust();

            // Show interface and setup controls
            this.showMeetingInterface();

            this.isInitialized = true;
            this.isConnected = true;

            console.log('✅ LiveKit meeting initialized successfully (FIXED)');

        } catch (error) {
            console.error('❌ Failed to initialize meeting:', error);
            this.showError('فشل في الاتصال بالجلسة. يرجى المحاولة مرة أخرى.');
            throw error;
        }
    }

    /**
     * CRITICAL FIX: Initialize modules sequentially to prevent race conditions
     */
    async initializeModulesSequentially() {
        console.log('🔧 Setting up meeting modules sequentially...');

        // 1. Connection module first
        this.connection = new LiveKitConnection({
            serverUrl: this.config.serverUrl,
            csrfToken: this.config.csrfToken,
            roomName: this.config.roomName,
            participantName: this.config.participantName,
            role: this.config.role,
            onConnectionStateChange: (state) => this.handleConnectionStateChange(state),
            onParticipantConnected: (participant) => this.handleParticipantConnectedFixed(participant),
            onParticipantDisconnected: (participant) => this.handleParticipantDisconnectedFixed(participant),
            onTrackSubscribed: (track, publication, participant) => this.handleTrackSubscribedFixed(track, publication, participant),
            onTrackUnsubscribed: (track, publication, participant) => this.handleTrackUnsubscribedFixed(track, publication, participant),
            onTrackPublished: (publication, participant) => this.handleTrackPublishedFixed(publication, participant),
            onTrackUnpublished: (publication, participant) => this.handleTrackUnpublishedFixed(publication, participant),
            onTrackMuted: (publication, participant) => this.handleTrackMutedFixed(publication, participant),
            onTrackUnmuted: (publication, participant) => this.handleTrackUnmutedFixed(publication, participant),
            onActiveSpeakersChanged: (speakers) => this.handleActiveSpeakersChanged(speakers),
            onDataReceived: (payload, participant) => this.handleDataReceived(payload, participant)
        });

        // 2. Tracks module with fixed implementation
        this.tracks = new LiveKitTracksFixed({
            onVideoTrackAttached: (participantId, videoElement, track, publication) => {
                console.log(`📹 [FIXED] Video track attached for ${participantId}`);
                this.onVideoTrackAttached(participantId, videoElement, track, publication);
            },
            onVideoTrackDetached: (participantId, track, publication) => {
                console.log(`📹 [FIXED] Video track detached for ${participantId}`);
                this.onVideoTrackDetached(participantId, track, publication);
            },
            onCameraStateChanged: (participantId, hasVideo) => {
                this.handleCameraStateChangedFixed(participantId, hasVideo);
            },
            onMicrophoneStateChanged: (participantId, hasAudio) => {
                this.handleMicrophoneStateChangedFixed(participantId, hasAudio);
            }
        });

        // 3. Participants module
        this.participants = new LiveKitParticipants({
            meetingConfig: this.config,
            onParticipantAdded: (participant) => {
                console.log(`👤 [FIXED] Participant added: ${participant.identity}`);
                this.onParticipantAdded(participant);
            },
            onParticipantRemoved: (participant, participantId) => {
                console.log(`👤 [FIXED] Participant removed: ${participantId}`);
                this.onParticipantRemoved(participant, participantId);
            },
            onParticipantClick: (participantElement, participant) => {
                this.handleParticipantClick(participantElement, participant);
            }
        });

        // 4. Layout module
        this.layout = new LiveKitLayout({
            onLayoutChange: (layoutType) => {
                console.log(`🎨 Layout changed to: ${layoutType}`);
            },
            onFocusEnter: (participantId) => {
                console.log(`🎯 Entered focus mode for: ${participantId}`);
            },
            onFocusExit: (participantId) => {
                console.log(`🔙 Exited focus mode for: ${participantId}`);
            }
        });

        console.log('✅ All modules initialized sequentially');
    }

    /**
     * CRITICAL FIX: Robust local media setup with proper state tracking
     */
    async setupLocalMediaRobust() {
        console.log('🎤 [FIXED] Setting up local media with robust state tracking...');

        try {
            const localParticipant = this.connection.getLocalParticipant();
            if (!localParticipant) {
                throw new Error('Local participant not available');
            }

            // Set local participant reference
            this.participants.setLocalParticipant(localParticipant);

            // CRITICAL FIX: Add local participant to UI first and track state
            this.participants.addParticipant(localParticipant);
            this.initializeParticipantState(localParticipant.identity, true);

            // Request media permissions with better error handling
            await this.requestMediaPermissionsRobust(localParticipant);

            // CRITICAL FIX: Process local tracks only after participant is properly initialized
            await this.processLocalTracksRobust(localParticipant);

            // CRITICAL FIX: Load existing participants with proper state management
            await this.loadExistingParticipantsRobust();

            console.log('✅ Local media setup complete (FIXED)');

        } catch (error) {
            console.error('❌ Failed to setup local media:', error);
            
            if (error.name === 'NotAllowedError') {
                this.showNotification('تم رفض الوصول للكاميرا أو الميكروفون', 'error');
            } else if (error.message && (error.message.includes('room') || error.message.includes('connection'))) {
                this.showNotification('فشل في إعداد الجلسة. يرجى المحاولة مرة أخرى.', 'error');
            } else {
                console.warn('⚠️ Non-critical media setup error:', error.message);
                this.showNotification('تم الانضمام للجلسة بنجاح. قد تحتاج لتفعيل الكاميرا يدوياً.', 'info');
            }
        }
    }

    /**
     * CRITICAL FIX: Request media permissions with robust error handling
     */
    async requestMediaPermissionsRobust(localParticipant) {
        console.log('🎤 [FIXED] Requesting media permissions...');

        let mediaPermissionsGranted = false;
        
        // Request microphone permission
        try {
            console.log('🎤 Requesting microphone permission...');
            await navigator.mediaDevices.getUserMedia({ audio: true });
            await localParticipant.setMicrophoneEnabled(true);
            console.log('✅ Microphone enabled');
            mediaPermissionsGranted = true;
        } catch (audioError) {
            console.warn('⚠️ Microphone access denied:', audioError.message);
            if (audioError.name === 'NotAllowedError') {
                this.showNotification('لا يمكن الوصول إلى الميكروفون. يرجى السماح بالوصول في المتصفح.', 'warning');
            }
        }

        // Request camera permission
        try {
            console.log('📹 Requesting camera permission...');
            await navigator.mediaDevices.getUserMedia({ video: true });
            await localParticipant.setCameraEnabled(true);
            console.log('✅ Camera enabled');
            mediaPermissionsGranted = true;
        } catch (videoError) {
            console.warn('⚠️ Camera access denied:', videoError.message);
            if (videoError.name === 'NotAllowedError') {
                this.showNotification('لا يمكن الوصول إلى الكاميرا. يرجى السماح بالوصول في المتصفح.', 'warning');
            }
        }

        if (!mediaPermissionsGranted) {
            this.showNotification('لم يتم منح أي صلاحيات للوسائط. ستتمكن من المشاركة بالدردشة فقط.', 'info');
        }
    }

    /**
     * CRITICAL FIX: Process local tracks with proper error handling and state management
     */
    async processLocalTracksRobust(localParticipant) {
        console.log('🔄 [FIXED] Processing local participant tracks robustly...');

        const participantId = localParticipant.identity;

        try {
            // Wait for tracks to be available
            await this.waitForLocalTracksReady(localParticipant);

            // Process video tracks
            if (localParticipant.videoTracks && localParticipant.videoTracks.size > 0) {
                console.log(`📹 Found ${localParticipant.videoTracks.size} local video track(s)`);
                for (const publication of localParticipant.videoTracks.values()) {
                    if (publication && publication.track) {
                        console.log('📹 Processing local video track');
                        await this.tracks.handleTrackSubscribed(publication.track, publication, localParticipant);
                    }
                }
            }

            // Process audio tracks
            if (localParticipant.audioTracks && localParticipant.audioTracks.size > 0) {
                console.log(`🎤 Found ${localParticipant.audioTracks.size} local audio track(s)`);
                for (const publication of localParticipant.audioTracks.values()) {
                    if (publication && publication.track) {
                        console.log('🎤 Processing local audio track');
                        await this.tracks.handleTrackSubscribed(publication.track, publication, localParticipant);
                    }
                }
            }

            // Update participant state
            this.updateParticipantState(participantId, {
                hasVideoTracks: localParticipant.videoTracks?.size > 0,
                hasAudioTracks: localParticipant.audioTracks?.size > 0,
                isLocal: true,
                tracksProcessed: true
            });

            console.log('✅ Local tracks processed successfully (FIXED)');

        } catch (error) {
            console.error('❌ Error processing local tracks:', error);
            
            // Mark processing attempt for retry logic
            this.updateParticipantState(participantId, {
                tracksProcessingFailed: true,
                lastError: error.message
            });
        }
    }

    /**
     * CRITICAL FIX: Wait for local tracks to be ready
     */
    async waitForLocalTracksReady(localParticipant, timeout = 5000) {
        console.log('⏳ [FIXED] Waiting for local tracks to be ready...');

        return new Promise((resolve) => {
            const checkTracks = () => {
                const hasVideo = localParticipant.videoTracks?.size > 0;
                const hasAudio = localParticipant.audioTracks?.size > 0;
                
                if (hasVideo || hasAudio) {
                    console.log('✅ Local tracks are ready');
                    resolve(true);
                    return true;
                }
                return false;
            };

            // Check immediately
            if (checkTracks()) return;

            // Poll for tracks
            const pollInterval = setInterval(() => {
                if (checkTracks()) {
                    clearInterval(pollInterval);
                }
            }, 100);

            // Timeout fallback
            setTimeout(() => {
                clearInterval(pollInterval);
                console.log('⏰ Local tracks wait timeout, proceeding anyway');
                resolve(false);
            }, timeout);
        });
    }

    /**
     * CRITICAL FIX: Load existing participants with robust state management
     */
    async loadExistingParticipantsRobust() {
        console.log('👥 [FIXED] Loading existing participants robustly...');

        const room = this.connection.getRoom();
        if (!room) {
            console.warn('⚠️ Room not available for loading participants');
            return;
        }

        const existingParticipants = Array.from(room.remoteParticipants.values());
        console.log(`👥 Found ${existingParticipants.length} existing remote participants`);

        // Process each participant sequentially to avoid race conditions
        for (const participant of existingParticipants) {
            await this.processExistingParticipant(participant);
        }

        // Update UI elements
        this.participants.updateParticipantsList();
        this.layout.applyGrid(this.participants.getParticipantCount());

        console.log(`✅ Loaded ${existingParticipants.length} existing participants (FIXED)`);
    }

    /**
     * CRITICAL FIX: Process existing participant with proper state management
     */
    async processExistingParticipant(participant) {
        const participantId = participant.identity;
        console.log(`👤 [FIXED] Processing existing participant: ${participantId}`);

        try {
            // Initialize participant state
            this.initializeParticipantState(participantId, false);

            // Add participant to UI
            this.participants.addParticipant(participant);

            // Process existing tracks with proper subscription
            await this.processExistingParticipantTracks(participant);

            // Mark as processed
            this.updateParticipantState(participantId, {
                processed: true,
                addedToUI: true
            });

        } catch (error) {
            console.error(`❌ Error processing existing participant ${participantId}:`, error);
            this.updateParticipantState(participantId, {
                processingFailed: true,
                lastError: error.message
            });
        }
    }

    /**
     * CRITICAL FIX: Process existing participant tracks with proper subscription
     */
    async processExistingParticipantTracks(participant) {
        const participantId = participant.identity;

        // Process video tracks
        if (participant.videoTracks && participant.videoTracks.size > 0) {
            for (const publication of participant.videoTracks.values()) {
                console.log(`📹 Processing existing video track from ${participantId} - subscribed: ${publication.isSubscribed}, muted: ${publication.isMuted}`);

                if (publication.track) {
                    console.log(`📹 Loading existing video track from ${participantId}`);
                    await this.tracks.handleTrackSubscribed(publication.track, publication, participant);
                } else if (!publication.isSubscribed && !publication.isMuted) {
                    console.log(`📹 Force subscribing to video track from ${participantId}`);
                    await this.forceTrackSubscription(participant, publication);
                }
            }
        }

        // Process audio tracks
        if (participant.audioTracks && participant.audioTracks.size > 0) {
            for (const publication of participant.audioTracks.values()) {
                console.log(`🎤 Processing existing audio track from ${participantId} - subscribed: ${publication.isSubscribed}, muted: ${publication.isMuted}`);

                if (publication.track) {
                    console.log(`🎤 Loading existing audio track from ${participantId}`);
                    await this.tracks.handleTrackSubscribed(publication.track, publication, participant);
                } else if (!publication.isSubscribed && !publication.isMuted) {
                    console.log(`🎤 Force subscribing to audio track from ${participantId}`);
                    await this.forceTrackSubscription(participant, publication);
                }
            }
        }
    }

    /**
     * CRITICAL FIX: Initialize participant state
     */
    initializeParticipantState(participantId, isLocal) {
        if (!this.participantStates.has(participantId)) {
            const state = {
                participantId,
                isLocal,
                addedToUI: false,
                processed: false,
                tracksProcessed: false,
                hasVideoTracks: false,
                hasAudioTracks: false,
                lastUpdate: Date.now(),
                processingFailed: false,
                tracksProcessingFailed: false,
                lastError: null
            };
            
            this.participantStates.set(participantId, state);
            console.log(`📊 [FIXED] Initialized state for ${participantId}:`, state);
        }
    }

    /**
     * CRITICAL FIX: Update participant state
     */
    updateParticipantState(participantId, updates) {
        const state = this.participantStates.get(participantId);
        if (state) {
            Object.assign(state, updates, { lastUpdate: Date.now() });
            console.log(`📊 [FIXED] Updated state for ${participantId}:`, updates);
        }
    }

    /**
     * CRITICAL FIX: Handle participant connected with state management
     */
    async handleParticipantConnectedFixed(participant) {
        const participantId = participant.identity;
        console.log(`👤 [FIXED] Participant connected: ${participantId}`);

        if (!participant.isLocal) {
            try {
                // Check if already being processed
                if (this.syncInProgress.has(participantId)) {
                    console.log(`⏭️ Participant ${participantId} already being processed`);
                    return;
                }

                this.syncInProgress.add(participantId);

                // Initialize state
                this.initializeParticipantState(participantId, false);

                // Add to UI
                this.participants.addParticipant(participant);
                this.updateParticipantState(participantId, { addedToUI: true });

                // Process any existing tracks
                await this.processExistingParticipantTracks(participant);

                // Update UI
                this.participants.updateParticipantsList();
                this.updateParticipantCount();
                
                this.updateParticipantState(participantId, { processed: true });

            } finally {
                this.syncInProgress.delete(participantId);
            }
        }
    }

    /**
     * CRITICAL FIX: Handle participant disconnected with cleanup
     */
    handleParticipantDisconnectedFixed(participant) {
        const participantId = participant.identity;
        console.log(`👤 [FIXED] Participant disconnected: ${participantId}`);

        // Clean up sync state
        this.syncInProgress.delete(participantId);

        // Remove participant
        this.participants.removeParticipant(participantId);
        this.participants.updateParticipantsList();
        this.updateParticipantCount();

        // Clean up state
        this.participantStates.delete(participantId);
    }

    /**
     * CRITICAL FIX: Handle track subscribed with state management
     */
    async handleTrackSubscribedFixed(track, publication, participant) {
        const participantId = participant.identity;
        console.log(`📹 [FIXED] Track subscribed: ${track.kind} from ${participantId} (local: ${participant.isLocal})`);

        try {
            // Ensure participant state exists
            this.initializeParticipantState(participantId, participant.isLocal);

            // Process track through fixed tracks module
            await this.tracks.handleTrackSubscribed(track, publication, participant);

            // Update participant state
            this.updateParticipantState(participantId, {
                [`has${track.kind.charAt(0).toUpperCase() + track.kind.slice(1)}Tracks`]: true,
                tracksProcessed: true
            });

        } catch (error) {
            console.error(`❌ Error handling track subscribed for ${participantId}:`, error);
            this.updateParticipantState(participantId, {
                tracksProcessingFailed: true,
                lastError: error.message
            });
        }
    }

    /**
     * CRITICAL FIX: Handle track unsubscribed
     */
    handleTrackUnsubscribedFixed(track, publication, participant) {
        console.log(`📹 [FIXED] Track unsubscribed: ${track.kind} from ${participant.identity}`);
        this.tracks.handleTrackUnsubscribed(track, publication, participant);
    }

    /**
     * CRITICAL FIX: Handle track published
     */
    handleTrackPublishedFixed(publication, participant) {
        console.log(`📹 [FIXED] Track published: ${publication.kind} from ${participant.identity} (local: ${participant.isLocal})`);

        if (participant.isLocal && publication.track) {
            console.log(`📹 Processing local published track: ${publication.kind}`);
            this.tracks.handleTrackSubscribed(publication.track, publication, participant);
        }
    }

    /**
     * CRITICAL FIX: Handle track unpublished
     */
    handleTrackUnpublishedFixed(publication, participant) {
        console.log(`📹 [FIXED] Track unpublished: ${publication.kind} from ${participant.identity} (local: ${participant.isLocal})`);

        if (participant.isLocal && publication.track) {
            console.log(`📹 Processing local unpublished track: ${publication.kind}`);
            this.tracks.handleTrackUnsubscribed(publication.track, publication, participant);
        }
    }

    /**
     * CRITICAL FIX: Handle track muted
     */
    handleTrackMutedFixed(publication, participant) {
        console.log(`🔇 [FIXED] Track muted: ${publication.kind} from ${participant.identity}`);
        this.tracks.handleTrackMuted(publication, participant);
    }

    /**
     * CRITICAL FIX: Handle track unmuted with enhanced subscription check
     */
    handleTrackUnmutedFixed(publication, participant) {
        const participantId = participant.identity;
        console.log(`🔊 [FIXED] Track unmuted: ${publication.kind} from ${participantId} (local: ${participant.isLocal})`);

        this.tracks.handleTrackUnmuted(publication, participant);

        // CRITICAL FIX: For remote participants, ensure track is available
        if (!participant.isLocal) {
            console.log(`📹 Remote participant ${participantId} unmuted ${publication.kind}, ensuring track availability`);

            if (!publication.track && !publication.isSubscribed) {
                console.log(`📹 No track available for unmuted ${publication.kind} from ${participantId}, force subscribing...`);
                this.forceTrackSubscription(participant, publication);
            }
        }
    }

    /**
     * CRITICAL FIX: Camera state changed
     */
    handleCameraStateChangedFixed(participantId, hasVideo) {
        console.log(`📹 [FIXED] Camera state changed for ${participantId}: ${hasVideo ? 'ON' : 'OFF'}`);
        this.participants.updateParticipantListStatus(participantId, 'cam', hasVideo);
    }

    /**
     * CRITICAL FIX: Microphone state changed
     */
    handleMicrophoneStateChangedFixed(participantId, hasAudio) {
        console.log(`🎤 [FIXED] Microphone state changed for ${participantId}: ${hasAudio ? 'ON' : 'OFF'}`);
        this.participants.updateParticipantListStatus(participantId, 'mic', hasAudio);
    }

    /**
     * Event handlers for participants module
     */
    onParticipantAdded(participant) {
        this.layout.applyGrid(this.participants.getParticipantCount());
        this.updateParticipantCount();
    }

    onParticipantRemoved(participant, participantId) {
        this.tracks.removeParticipantTracks(participantId);
        this.layout.applyGrid(this.participants.getParticipantCount());
        this.updateParticipantCount();
    }

    onVideoTrackAttached(participantId, videoElement, track, publication) {
        console.log(`📹 Video track attached for ${participantId}`);
    }

    onVideoTrackDetached(participantId, track, publication) {
        console.log(`📹 Video track detached for ${participantId}`);
    }

    /**
     * Force track subscription helper
     */
    async forceTrackSubscription(participant, publication) {
        console.log(`🔄 Force subscribing to ${publication.kind} track from ${participant.identity}`);

        try {
            await participant.subscribeToTrack(publication);
            console.log(`✅ Successfully force subscribed to ${publication.kind} track from ${participant.identity}`);

            setTimeout(() => {
                if (publication.track) {
                    console.log(`📹 Force subscription resulted in track, processing...`);
                    this.tracks.handleTrackSubscribed(publication.track, publication, participant);
                }
            }, 500);

        } catch (error) {
            console.error(`❌ Failed to force subscribe to track from ${participant.identity}:`, error);
        }
    }

    /**
     * Standard meeting methods (unchanged)
     */
    showMeetingInterface() {
        console.log('🎨 Showing meeting interface...');

        const loadingOverlay = document.getElementById('loadingOverlay');
        if (loadingOverlay) {
            loadingOverlay.style.display = 'none';
        }

        const meetingInterface = document.getElementById('meetingInterface');
        if (meetingInterface) {
            meetingInterface.style.display = 'block';
        }

        this.setupControls();
        console.log('✅ Meeting interface shown');
    }

    setupControls() {
        console.log('🎮 Setting up controls...');

        this.controls = new LiveKitControls({
            room: this.connection.getRoom(),
            localParticipant: this.connection.getLocalParticipant(),
            meetingConfig: this.config,
            onControlStateChange: (control, enabled) => {
                console.log(`🎮 Control state changed - ${control}: ${enabled}`);
            },
            onNotification: (message, type) => this.showNotification(message, type),
            onLeaveRequest: () => this.handleLeaveRequest()
        });

        window.livekitControls = this.controls;
        console.log('✅ Controls set up successfully');
    }

    updateParticipantCount() {
        const participantCountElement = document.getElementById('participantCount');
        if (participantCountElement) {
            const count = this.participants.getParticipantCount();
            participantCountElement.textContent = count.toString();
            console.log(`📊 Updated participant count to: ${count}`);
        }
    }

    handleConnectionStateChange(state) {
        console.log(`🔗 Connection state: ${state}`);

        switch (state) {
            case 'connected':
                this.isConnected = true;
                this.showNotification('تم الاتصال بالجلسة بنجاح', 'success');
                break;
            case 'disconnected':
                this.isConnected = false;
                this.showNotification('تم قطع الاتصال بالجلسة', 'error');
                break;
            case 'reconnecting':
                this.showNotification('جاري إعادة الاتصال...', 'info');
                break;
        }
    }

    handleActiveSpeakersChanged(speakers) {
        console.log(`🗣️ Active speakers changed:`, speakers.map(s => s.identity));
        const speakerIds = speakers.map(speaker => speaker.identity);
        this.participants.highlightActiveSpeakers(speakerIds);
    }

    handleDataReceived(payload, participant) {
        try {
            console.log(`📦 Data received from ${participant?.identity}`);

            if (!payload || !participant) {
                console.error('❌ Invalid data received');
                return;
            }

            const decodedString = new TextDecoder().decode(payload);
            const data = JSON.parse(decodedString);

            if (this.controls) {
                this.controls.handleDataReceived(data, participant);
            } else {
                console.error('❌ Controls module not available to handle data');
            }

        } catch (error) {
            console.error('❌ Error in handleDataReceived:', error);
        }
    }

    handleParticipantClick(participantElement, participant) {
        console.log(`👆 Participant clicked: ${participant.identity}`);

        const layoutState = this.layout.getLayoutState();

        if (layoutState.isFocusModeActive && layoutState.focusedParticipant === participant.identity) {
            this.layout.exitFocusMode();
        } else {
            this.layout.applyFocusMode(participant.identity, participantElement);
        }
    }

    handleLeaveRequest() {
        console.log('🚪 Handling leave request...');

        this.destroy().then(() => {
            console.log('🔄 Reloading current page after meeting cleanup');
            window.location.reload();
        }).catch(error => {
            console.error('❌ Error during meeting cleanup:', error);
            console.log('🔄 Reloading current page despite cleanup error');
            window.location.reload();
        });
    }

    showNotification(message, type = 'info') {
        console.log(`📢 Notification (${type}): ${message}`);

        const notification = document.createElement('div');
        notification.className = `fixed top-4 right-4 z-50 p-4 rounded-lg shadow-lg transition-all duration-300 transform translate-x-full`;

        const colors = {
            success: 'bg-green-600 text-white',
            error: 'bg-red-600 text-white',
            info: 'bg-blue-600 text-white'
        };

        notification.className += ` ${colors[type] || colors.info}`;
        notification.innerHTML = `
            <div class="flex items-center justify-between">
                <span>${message}</span>
                <button class="ml-4 text-white hover:text-gray-200" onclick="this.parentElement.parentElement.remove()">
                    <i class="fas fa-times"></i>
                </button>
            </div>
        `;

        document.body.appendChild(notification);

        setTimeout(() => {
            notification.classList.remove('translate-x-full');
        }, 100);

        setTimeout(() => {
            if (notification.parentNode) {
                notification.classList.add('translate-x-full');
                setTimeout(() => {
                    if (notification.parentNode) {
                        notification.remove();
                    }
                }, 300);
            }
        }, 5000);
    }

    showError(message) {
        console.error('❌ Meeting error:', message);
        alert(message);
    }

    getMeetingState() {
        return {
            isInitialized: this.isInitialized,
            isConnected: this.isConnected,
            participantCount: this.participants?.getParticipantCount() || 0,
            layoutState: this.layout?.getLayoutState() || null,
            controlStates: this.controls?.getControlStates() || null,
            participantStates: Object.fromEntries(this.participantStates)
        };
    }

    async destroy() {
        if (this.isDestroyed) {
            console.log('⚠️ Meeting already destroyed');
            return;
        }

        console.log('🧹 Destroying LiveKit meeting (FIXED)...');

        try {
            // Clean up state
            this.participantStates.clear();
            this.syncInProgress.clear();

            // Destroy modules
            if (this.controls) {
                this.controls.destroy();
                this.controls = null;
                window.livekitControls = null;
            }

            if (this.layout) {
                this.layout.destroy();
                this.layout = null;
            }

            if (this.tracks) {
                this.tracks.destroy();
                this.tracks = null;
            }

            if (this.participants) {
                this.participants.destroy();
                this.participants = null;
            }

            if (this.connection) {
                await this.connection.disconnect();
                this.connection.destroy();
                this.connection = null;
            }

            this.isInitialized = false;
            this.isConnected = false;
            this.isDestroyed = true;

            console.log('✅ Meeting destroyed successfully (FIXED)');

        } catch (error) {
            console.error('❌ Error during meeting destruction:', error);
        }
    }
}

// Global functions for compatibility
let globalMeetingInstanceFixed = null;

async function initializeLiveKitMeetingFixed(config) {
    if (globalMeetingInstanceFixed) {
        console.log('⚠️ Meeting already exists, destroying previous instance');
        await globalMeetingInstanceFixed.destroy();
    }

    globalMeetingInstanceFixed = new LiveKitMeetingFixed(config);
    await globalMeetingInstanceFixed.init();

    return globalMeetingInstanceFixed;
}

function getCurrentMeetingFixed() {
    return globalMeetingInstanceFixed;
}

async function destroyCurrentMeetingFixed() {
    if (globalMeetingInstanceFixed) {
        await globalMeetingInstanceFixed.destroy();
        globalMeetingInstanceFixed = null;
    }
}

// Make available globally
window.initializeLiveKitMeetingFixed = initializeLiveKitMeetingFixed;
window.getCurrentMeetingFixed = getCurrentMeetingFixed;
window.destroyCurrentMeetingFixed = destroyCurrentMeetingFixed;
window.LiveKitMeetingFixed = LiveKitMeetingFixed;
