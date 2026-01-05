/**
 * LiveKit Meeting Integration - Main Entry Point - ENHANCED WITH SYNCHRONIZATION FIXES
 * Modular, event-driven LiveKit meeting implementation
 * CRITICAL FIXES: Eliminated race conditions, improved state synchronization, robust track management
 */

// Note: Classes are loaded via separate script tags, no imports needed

/**
 * Main LiveKit Meeting class that coordinates all modules
 */
class LiveKitMeeting {
    /**
     * Initialize the meeting with configuration
     * @param {Object} config - Meeting configuration
     * @param {string} config.serverUrl - LiveKit server URL
     * @param {string} config.csrfToken - CSRF token for API calls
     * @param {string} config.roomName - Room name to join
     * @param {string} config.participantName - Participant name
     * @param {string} config.role - Participant role (teacher/student)
     * @param {Object} config.uiSelectors - DOM selector configuration
     */
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

        // CRITICAL FIX: Enhanced synchronization management
        this.participantStates = new Map(); // participantId -> comprehensive state
        this.initializationQueue = new Map(); // participantId -> Promise
        this.syncInProgress = new Set(); // Track which participants are being synced
        this.lastStateCheck = new Map(); // participantId -> timestamp
        
        // Track synchronization interval
        this.trackSyncInterval = null;

        
        // CRITICAL FIX: Initialize loading overlay state properly
        // Use setTimeout to ensure DOM is ready
        setTimeout(() => {
            this.initializeLoadingOverlay();
        }, 100);
    }

    /**
     * Initialize all modules and start the meeting - ENHANCED WITH SYNCHRONIZATION
     * @returns {Promise<void>}
     */
    async init() {
        if (this.isInitialized) {
            return;
        }

        try {

            // Initialize modules in correct order
            await this.initializeModules();

            // Get token and connect to the room
            const token = await this.connection.getLiveKitToken();
            const serverUrl = this.config.serverUrl || 'wss://test-rn3dlic1.livekit.cloud';
            await this.connection.connect(serverUrl, token);

            // CRITICAL FIX: Setup local media with enhanced synchronization
            await this.setupLocalMediaEnhanced();

            // Show meeting interface
            this.showMeetingInterface();

            // CRITICAL FIX: Start continuous synchronization check
            this.startContinuousSync();

            this.isInitialized = true;
            this.isConnected = true;


        } catch (error) {
            this.showError(t('connection.failed'));
            throw error;
        }
    }

    /**
     * Initialize all modules with cross-module communication
     */
    async initializeModules() {

        // 1. Initialize connection module
        this.connection = new LiveKitConnection({
            serverUrl: this.config.serverUrl,
            csrfToken: this.config.csrfToken,
            roomName: this.config.roomName,
            participantName: this.config.participantName,
            role: this.config.role,
            onConnectionStateChange: (state) => this.handleConnectionStateChange(state),
            onParticipantConnected: (participant) => this.handleParticipantConnected(participant),
            onParticipantDisconnected: (participant) => this.handleParticipantDisconnected(participant),
            onTrackSubscribed: (track, publication, participant) => this.handleTrackSubscribed(track, publication, participant),
            onTrackUnsubscribed: (track, publication, participant) => this.handleTrackUnsubscribed(track, publication, participant),
            onTrackPublished: (publication, participant) => this.handleTrackPublished(publication, participant),
            onTrackUnpublished: (publication, participant) => this.handleTrackUnpublished(publication, participant),
            onTrackMuted: (publication, participant) => this.handleTrackMuted(publication, participant),
            onTrackUnmuted: (publication, participant) => this.handleTrackUnmuted(publication, participant),
            onActiveSpeakersChanged: (speakers) => this.handleActiveSpeakersChanged(speakers),
            onDataReceived: (payload, participant) => this.handleDataReceived(payload, participant)
        });

        // 2. Initialize tracks module
        this.tracks = new LiveKitTracks({
            onVideoTrackAttached: (participantId, videoElement, track, publication) => {
            },
            onVideoTrackDetached: (participantId, track, publication) => {
            },
            onCameraStateChanged: (participantId, hasVideo) => {
                this.handleCameraStateChanged(participantId, hasVideo);
            },
            onMicrophoneStateChanged: (participantId, hasAudio) => {
                this.handleMicrophoneStateChanged(participantId, hasAudio);
            }
        });

        // 3. Initialize participants module
        this.participants = new LiveKitParticipants({
            meetingConfig: this.config,
            onParticipantAdded: (participant) => {
                this.layout.applyGrid(this.participants.getParticipantCount());
            },
            onParticipantRemoved: (participant, participantId) => {
                this.tracks.removeParticipantTracks(participantId);
                this.layout.applyGrid(this.participants.getParticipantCount());
            },
            onParticipantClick: (participantElement, participant) => {
                this.handleParticipantClick(participantElement, participant);
            }
        });

        // 4. Initialize layout module
        this.layout = new LiveKitLayout({
            onLayoutChange: (layoutType) => {
            },
            onFocusEnter: (participantId) => {
            },
            onFocusExit: (participantId) => {
            }
        });

        // 5. Initialize controls module (will be set up after connection)
        // This is done in setupControls() after connection is established

    }

    /**
     * Setup controls after connection is established
     */
    setupControls() {

        this.controls = new LiveKitControls({
            room: this.connection.getRoom(),
            localParticipant: this.connection.getLocalParticipant(),
            meetingConfig: this.config,
            onControlStateChange: (control, enabled) => {
            },
            onNotification: (message, type) => this.showNotification(message, type),
            onLeaveRequest: () => this.handleLeaveRequest(),
            onParticipantsListOpened: () => {
                this.participants.updateParticipantsList();
            }
        });

        // Set global reference for screen share controls
        window.livekitControls = this.controls;

        // Add global debug functions
        window.debugChat = () => {
            if (this.controls) {
                this.controls.debugTestChat();
            } else {
            }
        };

        window.debugMeeting = () => {
            return this.getMeetingState();
        };

        window.debugVideos = () => {
            const videoElements = document.querySelectorAll('video');
            
            videoElements.forEach((video, index) => {
            });

            const participants = document.querySelectorAll('[id^="participant-"]');
            
            participants.forEach((participant, index) => {
                const id = participant.id.replace('participant-', '');
                const hasVideo = !!participant.querySelector('video');
                const placeholder = participant.querySelector('.absolute.inset-0.flex.flex-col');
            });

            return {
                totalVideos: videoElements.length,
                totalParticipants: participants.length,
                videoElements: Array.from(videoElements).map(v => ({
                    id: v.id,
                    visible: v.style.opacity !== '0' && v.style.display !== 'none',
                    hasSource: !!v.srcObject
                })),
                participants: Array.from(participants).map(p => {
                    const id = p.id.replace('participant-', '');
                    const placeholder = p.querySelector('.absolute.inset-0.flex.flex-col');
                    return {
                        id,
                        hasPlaceholder: !!placeholder,
                        placeholderVisible: placeholder?.style.opacity !== '0'
                    };
                })
            };
        };

        window.debugPlaceholders = () => {
            const participants = document.querySelectorAll('[id^="participant-"]');
            
            participants.forEach((participant, index) => {
                const id = participant.id.replace('participant-', '');
                const placeholder = participant.querySelector('.absolute.inset-0.flex.flex-col');
                const video = participant.querySelector('video');
                
                if (placeholder) {
                    
                    const avatar = placeholder.querySelector('.rounded-full');
                    const nameElements = placeholder.querySelectorAll('p');
                    const statusContainer = placeholder.querySelector('.mt-2.flex.items-center.justify-center.gap-3');
                    
                } else {
                }
                
                if (video) {
                } else {
                }
                
                // Check for name overlays
                const overlay = document.getElementById(`name-overlay-${id}`);
                if (overlay) {
                } else {
                }
            });
        };

        // Add test function to manually show overlays
        window.testOverlay = (participantId) => {
            const overlay = document.getElementById(`name-overlay-${participantId}`);
            if (overlay) {
                overlay.style.display = 'block';
                overlay.style.opacity = '1';
            } else {
            }
        };

        // Force show overlays for all participants (for testing)
        window.forceShowOverlays = () => {
            const participants = document.querySelectorAll('[id^="participant-"]');
            participants.forEach(participant => {
                const id = participant.id.replace('participant-', '');
                const overlay = document.getElementById(`name-overlay-${id}`);
                if (overlay) {
                    overlay.style.display = 'block';
                    overlay.style.opacity = '1';
                }
            });
        };

        // Force update video display for all participants (for testing)
        window.forceUpdateVideoDisplay = () => {
            const participants = document.querySelectorAll('[id^="participant-"]');
            participants.forEach(participant => {
                const id = participant.id.replace('participant-', '');
                if (this.tracks && this.tracks.updateVideoDisplay) {
                    // Force video ON state for testing
                    this.tracks.updateVideoDisplay(id, true);
                }
            });
        };

        // Check if HTML overlays exist in DOM
        window.checkOverlays = () => {
            const participants = document.querySelectorAll('[id^="participant-"]');
            participants.forEach(participant => {
                const id = participant.id.replace('participant-', '');
                const overlay = document.getElementById(`name-overlay-${id}`);
                if (overlay) {
                } else {
                }
            });
        };

        // Test name cleaning function
        window.testNameCleaning = () => {
            if (this.participants && this.participants.cleanParticipantIdentity) {
                const testNames = [
                    '17_أحمد_العلي',
                    '25_فاطمة_محمد_teacher',
                    '12_علي_حسن_student',
                    'أحمد_العلي',
                    'normal_name'
                ];
                
                testNames.forEach(name => {
                    const cleanName = this.participants.cleanParticipantIdentity(name);
                });
            } else {
            }
        };

        // Test hand raise functionality
        window.testHandRaise = (participantId, isRaised = true) => {
            if (this.participants && this.participants.updateHandRaiseStatus) {
                this.participants.updateHandRaiseStatus(participantId, isRaised);
            } else {
            }
        };

        // Test hand raise indicators for all participants
        window.testHandRaiseIndicators = () => {
            if (this.participants) {
                const participants = document.querySelectorAll('[id^="participant-"]');
                participants.forEach(participant => {
                    const id = participant.id.replace('participant-', '');
                    // Test show/hide cycle
                    this.participants.showHandRaise(id);
                    setTimeout(() => {
                        this.participants.hideHandRaise(id);
                    }, 2000);
                });
            } else {
            }
        };

        // Test hand raise for specific participant
        window.testHandRaiseForParticipant = (participantId) => {
            if (this.participants && this.participants.updateHandRaiseStatus) {
                // Show hand raise
                this.participants.showHandRaise(participantId);
                
                // Hide after 3 seconds
                setTimeout(() => {
                    this.participants.hideHandRaise(participantId);
                }, 3000);
                
            } else {
            }
        };

        // Test hand raise directly (bypasses LiveKit flow)
        window.testHandRaiseDirectly = (participantId) => {
            if (this.participants && this.participants.testHandRaiseDirectly) {
                this.participants.testHandRaiseDirectly(participantId);
            } else {
            }
        };

        // Force create hand raise indicator (for debugging)
        window.forceCreateHandRaiseIndicator = (participantId) => {
            
            const participantElement = document.getElementById(`participant-${participantId}`);
            if (!participantElement) {
                
                // List all available participant elements
                const allParticipants = document.querySelectorAll('[id^="participant-"]');
                return;
            }
            
            // Remove existing indicator if any
            const existingIndicator = document.getElementById(`hand-raise-${participantId}`);
            if (existingIndicator) {
                existingIndicator.remove();
            }
            
            // Create new indicator
            const handRaiseIndicator = document.createElement('div');
            handRaiseIndicator.id = `hand-raise-${participantId}`;
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
                z-index: 9999;
                box-shadow: 0 4px 12px rgba(0, 0, 0, 0.3);
                border: 2px solid white;
                opacity: 1;
                transform: scale(1);
            `;
            handRaiseIndicator.innerHTML = '<i class="fas fa-hand" style="font-size: 14px;"></i>';
            
            participantElement.appendChild(handRaiseIndicator);
            
            
            // Remove after 5 seconds
            setTimeout(() => {
                if (handRaiseIndicator.parentNode) {
                    handRaiseIndicator.remove();
                }
            }, 5000);
        };

        // Test direct controls hand raise function
        window.testControlsHandRaise = (participantId = 'local', isRaised = true) => {
            if (this.controls && this.controls.createHandRaiseIndicatorDirect) {
                this.controls.createHandRaiseIndicatorDirect(participantId, isRaised);
            } else {
            }
        };

    }

    /**
     * Setup local media (camera and microphone)
     */
    async setupLocalMedia() {

        try {
            const localParticipant = this.connection.getLocalParticipant();

            // Set local participant reference in participants module
            this.participants.setLocalParticipant(localParticipant);

            // Add local participant to UI first
            this.participants.addParticipant(localParticipant);
            
            // Update participants list immediately to show local participant
            this.participants.updateParticipantsList();

            // Use the centralized setupMediaPermissions method with role-based defaults
            await this.setupMediaPermissions(localParticipant);

            // Process existing tracks after a short delay to ensure they're initialized
            
            // Wait a moment for tracks to be fully initialized
            setTimeout(() => {
                this.processLocalTracks(localParticipant);
            }, 500);

                    // Also load existing remote participants for late joiners
        this.loadExistingParticipants();

        // Force subscribe to all available tracks after a short delay
        setTimeout(() => {
            this.forceSubscribeToAllTracks();
        }, 1000);

        // Test hand raise functionality after meeting is fully initialized
        setTimeout(() => {
            if (this.participants && this.localParticipant) {
                const localId = this.localParticipant.identity;
                
                // Test direct hand raise
                if (this.participants.testHandRaiseDirectly) {
                    this.participants.testHandRaiseDirectly(localId);
                }
            }
        }, 3000);

            // Start periodic track synchronization check for late joiners
            this.startTrackSyncCheck();


        } catch (error) {
            
            // Only show user error for critical failures, not track processing issues
            if (error.name === 'NotAllowedError') {
                this.showNotification(t('connection.permission_denied'), 'error');
            } else if (error.message && error.message.includes('room') || error.message.includes('connection')) {
                this.showNotification(t('connection.setup_failed'), 'error');
            } else {
                // For other errors, just log them - don't overwhelm user with technical messages
                this.showNotification(t('connection.joined_may_need_camera'), 'info');
            }
        }
    }

    /**
     * CRITICAL FIX: Enhanced local media setup with synchronization
     */
    async setupLocalMediaEnhanced() {

        try {
            const localParticipant = this.connection.getLocalParticipant();
            
            // Set local participant reference
            this.participants.setLocalParticipant(localParticipant);
            
            // CRITICAL FIX: Initialize participant state tracking
            this.initializeParticipantState(localParticipant.identity, true);

            // Add local participant to UI
            await this.addParticipantWithSync(localParticipant);

            // Setup media with better error handling
            await this.setupMediaPermissions(localParticipant);

            // CRITICAL FIX: Process tracks with synchronization
            await this.processParticipantTracksSync(localParticipant);

            // Load existing participants with synchronization
            await this.loadExistingParticipantsSync();


        } catch (error) {
            this.handleMediaSetupError(error);
        }
    }

    /**
     * CRITICAL FIX: Initialize participant state tracking
     */
    initializeParticipantState(participantId, isLocal) {
        if (!this.participantStates.has(participantId)) {
            const state = {
                id: participantId,
                isLocal: isLocal,
                connected: true,
                hasVideo: false,
                hasAudio: false,
                videoMuted: true,
                audioMuted: true,
                lastSeen: Date.now(),
                tracksSynced: false,
                uiSynced: false
            };
            
            this.participantStates.set(participantId, state);
        }
    }

    /**
     * CRITICAL FIX: Add participant with synchronization
     */
    async addParticipantWithSync(participant) {
        const participantId = participant.identity;
        
        // Prevent duplicate processing
        if (this.syncInProgress.has(participantId)) {
            return;
        }
        
        this.syncInProgress.add(participantId);
        
        try {
            // Add to UI
            await this.participants.addParticipant(participant);
            
            // Update participant count
            this.updateParticipantCount();
            
            // Apply layout
            this.layout.applyGrid(this.participants.getParticipantCount());
            
            
        } finally {
            this.syncInProgress.delete(participantId);
        }
    }

    /**
     * CRITICAL FIX: Setup media permissions with role-based defaults
     * - Teachers: Mic ON, Camera OFF
     * - Students: Mic OFF, Camera OFF
     */
    async setupMediaPermissions(localParticipant) {
        // Determine user role from config
        const isTeacher = this.config.role === 'teacher';


        let mediaPermissionsGranted = false;

        // MICROPHONE: ON for teachers, OFF for students
        try {
            await navigator.mediaDevices.getUserMedia({ audio: true });

            if (isTeacher) {
                await localParticipant.setMicrophoneEnabled(true);
                mediaPermissionsGranted = true;
            } else {
                // Request permission but keep it OFF for students
                await localParticipant.setMicrophoneEnabled(false);
                mediaPermissionsGranted = true;
            }
        } catch (audioError) {
            if (audioError.name === 'NotAllowedError') {
                this.showNotification(t('connection.mic_access_denied'), 'warning');
            }
        }

        // CAMERA: OFF for everyone by default
        try {
            await navigator.mediaDevices.getUserMedia({ video: true });
            await localParticipant.setCameraEnabled(false);
            mediaPermissionsGranted = true;
        } catch (videoError) {
            if (videoError.name === 'NotAllowedError') {
                this.showNotification(t('connection.camera_access_denied'), 'warning');
            }
        }

        if (!mediaPermissionsGranted) {
            this.showNotification(t('permissions.no_media_permissions'), 'info');
        } else {
            const statusMsg = isTeacher
                ? t('connection.joined_teacher_mic_on')
                : t('connection.joined_student_muted');
            this.showNotification(statusMsg, 'success');
        }
    }

    /**
     * CRITICAL FIX: Process participant tracks with synchronization
     */
    async processParticipantTracksSync(participant) {
        const participantId = participant.identity;

        // Update participant state
        this.updateParticipantStateFromTracks(participant);

        // Process video tracks
        if (participant.videoTracks && participant.videoTracks.size > 0) {
            for (const publication of participant.videoTracks.values()) {
                if (publication && publication.track) {
                    await this.tracks.handleTrackSubscribed(publication.track, publication, participant);
                }
            }
        }

        // Process audio tracks
        if (participant.audioTracks && participant.audioTracks.size > 0) {
            for (const publication of participant.audioTracks.values()) {
                if (publication && publication.track) {
                    await this.tracks.handleTrackSubscribed(publication.track, publication, participant);
                }
            }
        }

        // Mark as synced
        const state = this.participantStates.get(participantId);
        if (state) {
            state.tracksSynced = true;
            state.lastSeen = Date.now();
        }

    }

    /**
     * CRITICAL FIX: Update participant state from actual tracks
     */
    updateParticipantStateFromTracks(participant) {
        const participantId = participant.identity;
        const state = this.participantStates.get(participantId);
        if (!state) return;

        // Check video state
        state.hasVideo = false;
        state.videoMuted = true;
        if (participant.videoTracks && participant.videoTracks.size > 0) {
            for (const publication of participant.videoTracks.values()) {
                if (publication.track) {
                    state.hasVideo = true;
                    state.videoMuted = publication.isMuted;
                    break;
                }
            }
        }

        // Check audio state
        state.hasAudio = false;
        state.audioMuted = true;
        if (participant.audioTracks && participant.audioTracks.size > 0) {
            for (const publication of participant.audioTracks.values()) {
                if (publication.track) {
                    state.hasAudio = true;
                    state.audioMuted = publication.isMuted;
                    break;
                }
            }
        }

        state.lastSeen = Date.now();
    }

    /**
     * CRITICAL FIX: Load existing participants with synchronization
     */
    async loadExistingParticipantsSync() {

        const room = this.connection.getRoom();
        if (!room) {
            return;
        }

        for (const [identity, participant] of room.remoteParticipants) {
            
            // Initialize state
            this.initializeParticipantState(identity, false);
            
            // Add participant with sync
            await this.addParticipantWithSync(participant);
            
            // Process their tracks
            await this.processParticipantTracksSync(participant);
        }

    }

    /**
     * CRITICAL FIX: Handle media setup errors gracefully
     */
    handleMediaSetupError(error) {
        
        if (error.name === 'NotAllowedError') {
            this.showNotification(t('connection.permission_denied'), 'error');
        } else if (error.message && (error.message.includes('room') || error.message.includes('connection'))) {
            this.showNotification(t('connection.setup_failed'), 'error');
        } else {
            this.showNotification(t('connection.joined_may_need_camera'), 'info');
        }
    }

    /**
     * CRITICAL FIX: Start continuous synchronization monitoring
     */
    startContinuousSync() {
        
        // Check every 3 seconds for synchronization issues
        this.trackSyncInterval = setInterval(() => {
            this.performSyncCheck();
        }, 3000);
        
    }

    /**
     * CRITICAL FIX: Perform comprehensive sync check
     */
    async performSyncCheck() {
        const room = this.connection.getRoom();
        if (!room) return;

        for (const [identity, participant] of room.remoteParticipants) {
            const state = this.participantStates.get(identity);
            
            if (!state || !state.tracksSynced) {
                await this.processParticipantTracksSync(participant);
            }
            
            // Check for missing video elements
            if (participant.videoTracks && participant.videoTracks.size > 0) {
                for (const publication of participant.videoTracks.values()) {
                    if (publication.track && !publication.isMuted) {
                        const videoElement = document.getElementById(`video-${identity}`);
                        if (!videoElement) {
                            await this.tracks.handleTrackSubscribed(publication.track, publication, participant);
                        }
                    }
                }
            }
        }
    }

    /**
     * Process local participant tracks with proper error handling
     * @param {LiveKit.LocalParticipant} localParticipant - Local participant instance
     */
    processLocalTracks(localParticipant) {

        try {
            // Handle video tracks with null checks
            if (localParticipant.videoTracks && localParticipant.videoTracks.size > 0) {
                localParticipant.videoTracks.forEach((publication) => {
                    if (publication && publication.track) {
                        this.tracks.handleTrackSubscribed(publication.track, publication, localParticipant);
                    } else {
                    }
                });
            } else {
            }

            // Handle audio tracks with null checks
            if (localParticipant.audioTracks && localParticipant.audioTracks.size > 0) {
                localParticipant.audioTracks.forEach((publication) => {
                    if (publication && publication.track) {
                        this.tracks.handleTrackSubscribed(publication.track, publication, localParticipant);
                    } else {
                    }
                });
            } else {
            }

            // Force update video display if camera is enabled and track exists
            // But only after meeting is fully initialized to prevent flickering
            if (localParticipant.isCameraEnabled && localParticipant.videoTracks?.size > 0 && this.isInitialized) {
                setTimeout(() => {
                    this.tracks.updateVideoDisplay(localParticipant.identity, true);
                }, 200); // Small delay to ensure UI is stable
            }


        } catch (error) {
            // Don't show user error for track processing issues, as this is internal
            
            // Retry after a longer delay if tracks processing failed
            setTimeout(() => {
                this.processLocalTracks(localParticipant);
            }, 2000);
        }

        // Also retry if no tracks were found (may be a timing issue)
        const hasVideoTracks = localParticipant.videoTracks?.size > 0;
        const hasAudioTracks = localParticipant.audioTracks?.size > 0;
        
        if (!hasVideoTracks && !hasAudioTracks && localParticipant.isCameraEnabled) {
            setTimeout(() => {
                this.processLocalTracks(localParticipant);
            }, 2000);
        }
    }

    /**
     * Load existing participants for late joiners
     */
    loadExistingParticipants() {

        const room = this.connection.getRoom();
        if (!room) {
            return;
        }

        // Add all existing remote participants
        for (const [identity, participant] of room.remoteParticipants) {

            // Add participant to UI
            this.participants.addParticipant(participant);

            // Handle their existing tracks (with safety checks and forced subscription)
            if (participant.videoTracks && participant.videoTracks.size > 0) {
                participant.videoTracks.forEach((publication) => {

                    if (publication.track) {
                        this.tracks.handleTrackSubscribed(publication.track, publication, participant);
                    } else if (!publication.isSubscribed && !publication.isMuted) {
                        // Force subscription for unmuted tracks without attached track
                        this.forceTrackSubscription(participant, publication);
                    }
                });
            }

            if (participant.audioTracks && participant.audioTracks.size > 0) {
                participant.audioTracks.forEach((publication) => {

                    if (publication.track) {
                        this.tracks.handleTrackSubscribed(publication.track, publication, participant);
                    } else if (!publication.isSubscribed && !publication.isMuted) {
                        // Force subscription for unmuted tracks without attached track
                        this.forceTrackSubscription(participant, publication);
                    }
                });
            }
        }

        // Update participant count and layout
        this.updateParticipantCount();
        this.participants.updateParticipantsList();
        this.layout.applyGrid(this.participants.getParticipantCount());


        // Ensure all tracks are properly subscribed
        this.ensureAllTracksSubscribed();
    }

    /**
     * Update participant count display
     */
    updateParticipantCount() {
        const participantCountElement = document.getElementById('participantCount');
        if (participantCountElement) {
            const count = this.participants.getParticipantCount();
            participantCountElement.textContent = count.toString();
        }
    }

    /**
     * Show the meeting interface - ENHANCED WITH SMOOTH TRANSITIONS
     */
    showMeetingInterface() {

        // CRITICAL FIX: Add a small delay to ensure everything is truly ready
        setTimeout(() => {
            this.performSmoothTransition();
        }, 200);
    }

    /**
     * CRITICAL FIX: Perform smooth transition from loading to meeting interface
     */
    performSmoothTransition() {
        const loadingOverlay = document.getElementById('loadingOverlay');
        const meetingInterface = document.getElementById('meetingInterface');

        if (!loadingOverlay || !meetingInterface) {
            return;
        }


        // Step 1: Start fading out the loading overlay
        loadingOverlay.classList.add('fade-out');

        // Step 2: Setup controls immediately since meeting interface is already visible
        setTimeout(() => {
            // Meeting interface is already visible, just setup controls
            this.setupControls();
            
        }, 100);

        // Step 3: Completely remove loading overlay after transition completes
        setTimeout(() => {
            if (loadingOverlay.classList.contains('fade-out')) {
                loadingOverlay.style.display = 'none';
            }
        }, 600); // 500ms transition + 100ms buffer

    }

    /**
     * CRITICAL FIX: Show loading overlay smoothly (for reconnection, etc.)
     */
    showLoadingOverlay(message = null) {
        // Use translation if no message provided
        message = message || t('loading.connecting_meeting');
        
        const loadingOverlay = document.getElementById('loadingOverlay');
        
        if (!loadingOverlay) {
            return;
        }

        // Update loading message if provided
        const messageElement = loadingOverlay.querySelector('p.text-xl');
        if (messageElement && message) {
            messageElement.textContent = message;
        }

        // Reset overlay state and show smoothly
        loadingOverlay.classList.remove('fade-out');
        loadingOverlay.style.display = 'flex';
        
        // CRITICAL FIX: Don't hide meeting interface - overlay is semi-transparent
        // Meeting interface should remain visible behind the overlay
        
    }

    /**
     * CRITICAL FIX: Ensure loading overlay is properly initialized
     */
    initializeLoadingOverlay() {
        const loadingOverlay = document.getElementById('loadingOverlay');
        const meetingInterface = document.getElementById('meetingInterface');
        
        if (loadingOverlay) {
            // Ensure loading overlay starts visible and in correct state
            loadingOverlay.classList.remove('fade-out');
            loadingOverlay.style.display = 'flex';
            // Don't override opacity - let Tailwind bg-opacity-75 work
        }
        
        if (meetingInterface) {
            // CRITICAL FIX: Don't hide meeting interface initially
            // Let it be visible by default so when loading overlay fades out, meeting shows
            meetingInterface.classList.remove('fade-in');
            // Don't set opacity to 0 - let it be visible
        }
        
    }

    /**
     * Handle connection state changes - ENHANCED WITH LOADING OVERLAY
     * @param {string} state - Connection state
     */
    handleConnectionStateChange(state) {

        switch (state) {
            case 'connected':
                this.isConnected = true;
                this.showNotification(t('connection.connected'), 'success');
                break;
            case 'disconnected':
                this.isConnected = false;
                this.showNotification(t('connection.disconnected'), 'error');
                // CRITICAL FIX: Show loading overlay during disconnection
                this.showLoadingOverlay(t('connection.disconnected_reconnecting'));
                break;
            case 'reconnecting':
                this.showNotification(t('connection.reconnecting'), 'info');
                // CRITICAL FIX: Show loading overlay during reconnection
                this.showLoadingOverlay(t('connection.reconnecting'));
                break;
            case 'reconnected':
                this.showNotification(t('connection.reconnected'), 'success');
                // Hide loading overlay and show meeting interface again
                this.performSmoothTransition();
                break;
        }
    }

    /**
     * Handle participant connected - ENHANCED WITH SYNCHRONIZATION
     * @param {LiveKit.Participant} participant - Connected participant
     */
    async handleParticipantConnected(participant) {
        const participantId = participant.identity;

        // Don't add local participant here - it's added in setupLocalMedia
        if (!participant.isLocal) {
            try {
                // CRITICAL FIX: Initialize state and add with synchronization
                this.initializeParticipantState(participantId, false);
                await this.addParticipantWithSync(participant);

                // Update participants list
                this.participants.updateParticipantsList();

                // CRITICAL FIX: Process any existing tracks immediately
                await this.processParticipantTracksSync(participant);

                // Show notification
                const displayName = participant.name || participant.identity;
                this.showNotification(t('participants.joined', { name: displayName }), 'info');


            } catch (error) {
            }
        }
    }

    /**
     * Handle participant disconnected - ENHANCED WITH CLEANUP
     * @param {LiveKit.Participant} participant - Disconnected participant
     */
    handleParticipantDisconnected(participant) {
        const participantId = participant.identity;

        // CRITICAL FIX: Clean up all synchronization tracking
        this.syncInProgress.delete(participantId);
        this.participantStates.delete(participantId);
        this.lastStateCheck.delete(participantId);
        
        // Clear any pending initialization
        if (this.initializationQueue.has(participantId)) {
            this.initializationQueue.delete(participantId);
        }

        // Remove participant from UI
        this.participants.removeParticipant(participantId);
        
        // Clean up tracks
        this.tracks.removeParticipantTracks(participantId);
        
        // Update participant count and layout
        this.participants.updateParticipantsList();
        this.updateParticipantCount();
        this.layout.applyGrid(this.participants.getParticipantCount());

        // Show notification
        const displayName = participant.name || participant.identity;
        this.showNotification(t('participants.left', { name: displayName }), 'info');

    }

    /**
     * Handle track subscribed - ENHANCED WITH SYNCHRONIZATION
     * @param {LiveKit.Track} track - Subscribed track
     * @param {LiveKit.TrackPublication} publication - Track publication
     * @param {LiveKit.Participant} participant - Participant
     */
    async handleTrackSubscribed(track, publication, participant) {
        const participantId = participant.identity;
        
        // CRITICAL FIX: Update participant state immediately
        this.updateParticipantStateFromTracks(participant);
        
        // Handle track subscription with enhanced error handling
        await this.tracks.handleTrackSubscribed(track, publication, participant);

        // CRITICAL FIX: Ensure participant sync without race conditions
        await this.ensureParticipantSyncSafe(participantId);
    }

    /**
     * CRITICAL FIX: Safe participant sync without race conditions
     */
    async ensureParticipantSyncSafe(participantId) {
        // Prevent overlapping sync operations
        if (this.syncInProgress.has(participantId)) {
            return;
        }
        
        this.syncInProgress.add(participantId);
        
        try {
            const room = this.connection.getRoom();
            if (!room) return;
            
            // Check if participant exists
            const participant = room.remoteParticipants.get(participantId) || 
                              (room.localParticipant?.identity === participantId ? room.localParticipant : null);
            
            if (participant) {
                // Check if participant element exists
                const participantElement = document.getElementById(`participant-${participantId}`);
                if (!participantElement) {
                    await this.addParticipantWithSync(participant);
                }
                
                // Check tracks
                await this.processParticipantTracksSync(participant);
            }
            
        } finally {
            this.syncInProgress.delete(participantId);
        }
    }

    /**
     * Handle track unsubscribed
     * @param {LiveKit.Track} track - Unsubscribed track
     * @param {LiveKit.TrackPublication} publication - Track publication
     * @param {LiveKit.Participant} participant - Participant
     */
    handleTrackUnsubscribed(track, publication, participant) {
        this.tracks.handleTrackUnsubscribed(track, publication, participant);
    }

    /**
     * Handle track published (for local participant mainly)
     * @param {LiveKit.TrackPublication} publication - Track publication
     * @param {LiveKit.Participant} participant - Participant
     */
    handleTrackPublished(publication, participant) {

        // For local participant, create the track handling as if it was subscribed
        if (participant.isLocal && publication.track) {
            this.tracks.handleTrackSubscribed(publication.track, publication, participant);
        }
    }

    /**
     * Handle track unpublished (for local participant mainly)
     * @param {LiveKit.TrackPublication} publication - Track publication
     * @param {LiveKit.Participant} participant - Participant
     */
    handleTrackUnpublished(publication, participant) {

        // For local participant, handle as if it was unsubscribed
        if (participant.isLocal && publication.track) {
            this.tracks.handleTrackUnsubscribed(publication.track, publication, participant);
        }
    }

    /**
     * Handle track muted
     * @param {LiveKit.TrackPublication} publication - Track publication
     * @param {LiveKit.Participant} participant - Participant
     */
    handleTrackMuted(publication, participant) {
        this.tracks.handleTrackMuted(publication, participant);
    }

    /**
     * Handle track unmuted
     * @param {LiveKit.TrackPublication} publication - Track publication
     * @param {LiveKit.Participant} participant - Participant
     */
    handleTrackUnmuted(publication, participant) {
        this.tracks.handleTrackUnmuted(publication, participant);

        // CRITICAL: For remote participants, ensure all users can see the track when it's unmuted
        if (!participant.isLocal) {

            // Check if we have the track, if not, force subscription
            if (!publication.track && !publication.isSubscribed) {
                this.forceTrackSubscription(participant, publication);
            } else if (publication.track) {
                // Ensure the track is properly processed even if we already have it
                this.tracks.handleTrackSubscribed(publication.track, publication, participant);
            }

            // Trigger participant sync to ensure UI is consistent
            this.ensureParticipantSync();
        }
    }

    /**
     * Handle active speakers changed
     * @param {LiveKit.Participant[]} speakers - Array of active speakers
     */
    handleActiveSpeakersChanged(speakers) {
        const speakerIds = speakers.map(speaker => speaker.identity);
        this.participants.highlightActiveSpeakers(speakerIds);
    }

    /**
     * Handle data received
     * @param {Uint8Array} payload - Data payload
     * @param {LiveKit.Participant} participant - Sender participant
     */
    handleDataReceived(payload, participant) {
        try {

            // Enhanced local participant context
            const localParticipant = this.connection?.getLocalParticipant();

            // List all participants for debugging
            if (localParticipant) {
            }
            this.connection?.getRoom()?.remoteParticipants?.forEach((participant, sid) => {
            });

            // Comprehensive payload validation
            if (!payload) {
                return;
            }

            if (payload.length === 0) {
                return;
            }

            // Enhanced participant validation
            if (!participant) {
                return;
            }

            // Check if this is from ourselves (should not happen with proper broadcasting)
            if (participant.isLocal) {
                // Don't return - process it for testing purposes
            }


            // Decode the payload with enhanced error handling
            let decodedString;
            try {
                decodedString = new TextDecoder().decode(payload);
            } catch (decodeError) {
                return;
            }

            // Parse JSON with enhanced error handling
            let data;
            try {
                data = JSON.parse(decodedString);
            } catch (parseError) {
                return;
            }

            // Enhanced data structure validation

            if (!data.type) {
                return;
            }

            // Check for sender mismatch (debugging)
            if (data.sender && participant.identity && data.sender !== participant.identity) {
            }

            if (data.senderSid && participant.sid && data.senderSid !== participant.sid) {
            }

            // Forward to controls with comprehensive validation
            if (this.controls) {

                this.controls.handleDataReceived(data, participant);

            } else {

                // Try to provide helpful debugging info
            }


        } catch (error) {
            console.error('Track subscription error', {
                name: error.name,
                message: error.message,
                stack: error.stack,
                participant: participant ? {
                    identity: participant.identity,
                    sid: participant.sid,
                    isLocal: participant.isLocal
                } : 'null'
            });

            if (payload) {

                try {
                    const asString = new TextDecoder().decode(payload);
                } catch (e) {
                }
            }

            // Try to show a user-friendly error if controls are available
            if (this.controls && typeof this.controls.showNotification === 'function') {
                this.controls.showNotification(t('control_errors.chat_data_error'), 'error');
            }
        }
    }

    /**
     * Handle camera state changed
     * @param {string} participantId - Participant ID
     * @param {boolean} hasVideo - Whether participant has active video
     */
    handleCameraStateChanged(participantId, hasVideo) {

        // Update participant list status
        this.participants.updateParticipantListStatus(participantId, 'cam', hasVideo);
    }

    /**
     * Handle microphone state changed
     * @param {string} participantId - Participant ID
     * @param {boolean} hasAudio - Whether participant has active audio
     */
    handleMicrophoneStateChanged(participantId, hasAudio) {

        // Update participant list status
        this.participants.updateParticipantListStatus(participantId, 'mic', hasAudio);
    }

    /**
     * Handle participant click for focus mode
     * @param {HTMLElement} participantElement - Participant DOM element
     * @param {LiveKit.Participant} participant - Participant object
     */
    handleParticipantClick(participantElement, participant) {

        const layoutState = this.layout.getLayoutState();

        if (layoutState.isFocusModeActive && layoutState.focusedParticipant === participant.identity) {
            // Exit focus mode if clicking on already focused participant
            this.layout.exitFocusMode();
        } else {
            // Enter focus mode for clicked participant
            this.layout.applyFocusMode(participant.identity, participantElement);
        }
    }

    /**
     * Handle leave request
     */
    handleLeaveRequest() {

        this.destroy().then(() => {
            // Simply reload the current page instead of redirecting
            window.location.reload();
        }).catch(error => {
            // Still reload even if cleanup fails
            window.location.reload();
        });
    }

    /**
     * Show notification to user using unified toast system
     * @param {string} message - Notification message
     * @param {string} type - Notification type ('success', 'error', 'info', 'warning')
     */
    showNotification(message, type = 'info') {
        if (!window.toast) return;

        // Use meeting type for participant join/leave events
        // Check for both English and Arabic patterns
        const joinedPattern = t('participants.joined').replace(':name', '').trim();
        const leftPattern = t('participants.left').replace(':name', '').trim();
        const isParticipantEvent = message.includes(joinedPattern) || message.includes(leftPattern);

        if (isParticipantEvent) {
            window.toast.meeting(message);
        } else {
            const toastMethod = window.toast[type] || window.toast.info;
            toastMethod(message);
        }
    }

    /**
     * Show error message
     * @param {string} message - Error message
     */
    showError(message) {
        // Use toast notification if available, silent fail otherwise
        if (window.toast?.error) {
            window.toast.error(message);
        }
    }

    /**
     * Get current meeting state
     * @returns {Object} Meeting state
     */
    getMeetingState() {
        return {
            isInitialized: this.isInitialized,
            isConnected: this.isConnected,
            participantCount: this.participants?.getParticipantCount() || 0,
            layoutState: this.layout?.getLayoutState() || null,
            controlStates: this.controls?.getControlStates() || null
        };
    }

    /**
     * Cleanup and destroy the meeting
     * @returns {Promise<void>}
     */
    async destroy() {
        if (this.isDestroyed) {
            return;
        }


        try {
            // CRITICAL FIX: Stop track synchronization check
            this.stopTrackSyncCheck();
            
            // Clear all synchronization tracking
            this.syncInProgress.clear();
            this.participantStates.clear();
            this.lastStateCheck.clear();
            this.initializationQueue.clear();

            // Destroy modules in reverse order
            if (this.controls) {
                this.controls.destroy();
                this.controls = null;

                // Clean up global reference
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


        } catch (error) {
        }
    }

    /**
     * Force track subscription for a specific participant and publication
     * @param {LiveKit.Participant} participant - The participant
     * @param {LiveKit.TrackPublication} publication - The track publication
     */
    async forceTrackSubscription(participant, publication) {

        try {
            // Use LiveKit SDK to manually subscribe to the track
            await participant.subscribeToTrack(publication);

            // Wait a bit for track to be available
            setTimeout(() => {
                if (publication.track) {
                    this.tracks.handleTrackSubscribed(publication.track, publication, participant);
                } else {
                }
            }, 500);

        } catch (error) {
        }
    }

    /**
     * Ensure all tracks are properly subscribed for late joiners
     */
    async ensureAllTracksSubscribed() {

        const room = this.connection.getRoom();
        if (!room) {
            return;
        }

        for (const [identity, participant] of room.remoteParticipants) {

            // Check video tracks
            if (participant.videoTracks && participant.videoTracks.size > 0) {
                participant.videoTracks.forEach(async (publication) => {
                    if (!publication.isSubscribed && !publication.isMuted) {
                        await this.forceTrackSubscription(participant, publication);
                    }
                });
            }

            // Check audio tracks
            if (participant.audioTracks && participant.audioTracks.size > 0) {
                participant.audioTracks.forEach(async (publication) => {
                    if (!publication.isSubscribed && !publication.isMuted) {
                        await this.forceTrackSubscription(participant, publication);
                    }
                });
            }
        }

    }

    /**
     * Ensure participant synchronization across all users
     */
    ensureParticipantSync() {

        const room = this.connection.getRoom();
        if (!room) {
            return;
        }

        // Check if any participants are missing from UI
        for (const [identity, participant] of room.remoteParticipants) {
            const participantElement = document.getElementById(`participant-${identity}`);
            if (!participantElement) {
                this.participants.addParticipant(participant);
            }

            // Check if their tracks are properly handled
            if (participant.videoTracks && participant.videoTracks.size > 0) {
                participant.videoTracks.forEach((publication) => {
                    if (publication.track && !publication.isMuted) {
                        const videoElement = document.getElementById(`video-${identity}`);
                        if (!videoElement) {
                            this.tracks.handleTrackSubscribed(publication.track, publication, participant);
                        }
                    }
                });
            }
        }

        // Update UI elements
        this.participants.updateParticipantsList();
        this.layout.applyGrid(this.participants.getParticipantCount());

    }

    /**
     * Start periodic track synchronization check
     */
    startTrackSyncCheck() {

        // Check every 5 seconds for missing tracks
        this.trackSyncInterval = setInterval(() => {
            this.checkAndFixMissingTracks();
        }, 5000);

    }

    /**
     * Stop periodic track synchronization check
     */
    stopTrackSyncCheck() {
        if (this.trackSyncInterval) {
            clearInterval(this.trackSyncInterval);
            this.trackSyncInterval = null;
        }
    }

    /**
     * Force subscribe to all available tracks for better reliability
     */
    async forceSubscribeToAllTracks() {

        const room = this.connection.getRoom();
        if (!room) {
            return;
        }

        for (const [identity, participant] of room.remoteParticipants) {

            // Force subscribe to video tracks
            if (participant.videoTracks && participant.videoTracks.size > 0) {
                for (const publication of participant.videoTracks.values()) {
                    if (!publication.isSubscribed && !publication.isMuted) {
                        try {
                            await publication.setSubscribed(true);
                        } catch (error) {
                        }
                    }
                }
            }

            // Force subscribe to audio tracks
            if (participant.audioTracks && participant.audioTracks.size > 0) {
                for (const publication of participant.audioTracks.values()) {
                    if (!publication.isSubscribed && !publication.isMuted) {
                        try {
                            await publication.setSubscribed(true);
                        } catch (error) {
                        }
                    }
                }
            }
        }

    }

    /**
     * Check and fix missing tracks for participants
     */
    async checkAndFixMissingTracks() {
        const room = this.connection.getRoom();
        if (!room) return;

        for (const [identity, participant] of room.remoteParticipants) {
            // Check video tracks
            if (participant.videoTracks && participant.videoTracks.size > 0) {
                participant.videoTracks.forEach(async (publication) => {
                    // If track is unmuted but we don't have video element, fix it
                    if (!publication.isMuted && !document.getElementById(`video-${identity}`)) {

                        if (!publication.track && !publication.isSubscribed) {
                            await this.forceTrackSubscription(participant, publication);
                        } else if (publication.track) {
                            this.tracks.handleTrackSubscribed(publication.track, publication, participant);
                        }
                    }
                });
            }
        }
    }
}

/**
 * Global meeting instance for cross-component access
 */
let globalMeetingInstance = null;

/**
 * Initialize a new LiveKit meeting instance
 * @param {Object} config - Meeting configuration
 * @returns {Promise<LiveKitMeeting>} Meeting instance
 */
async function initializeLiveKitMeeting(config) {
    if (globalMeetingInstance) {
        await globalMeetingInstance.destroy();
    }

    globalMeetingInstance = new LiveKitMeeting(config);
    await globalMeetingInstance.init();

    return globalMeetingInstance;
}

/**
 * Get current meeting instance
 * @returns {LiveKitMeeting|null} Current meeting instance
 */
function getCurrentMeeting() {
    return globalMeetingInstance;
}

/**
 * Destroy current meeting instance
 * @returns {Promise<void>}
 */
async function destroyCurrentMeeting() {
    if (globalMeetingInstance) {
        await globalMeetingInstance.destroy();
        globalMeetingInstance = null;
    }
}

// Make functions available globally
window.initializeLiveKitMeeting = initializeLiveKitMeeting;
window.getCurrentMeeting = getCurrentMeeting;
window.destroyCurrentMeeting = destroyCurrentMeeting;
window.LiveKitMeeting = LiveKitMeeting;
