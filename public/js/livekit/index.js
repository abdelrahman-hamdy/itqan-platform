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

        console.log('🚀 LiveKitMeeting initialized with enhanced synchronization:', config);
        
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
            console.log('⚠️ Meeting already initialized');
            return;
        }

        try {
            console.log('🔧 [FIXED] Initializing LiveKit meeting modules...');

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

            console.log('✅ LiveKit meeting initialized successfully (FIXED)');

        } catch (error) {
            console.error('❌ Failed to initialize meeting:', error);
            this.showError('فشل في الاتصال بالجلسة. يرجى المحاولة مرة أخرى.');
            throw error;
        }
    }

    /**
     * Initialize all modules with cross-module communication
     */
    async initializeModules() {
        console.log('🔧 Setting up meeting modules...');

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
                console.log(`📹 Video track attached for ${participantId}`);
            },
            onVideoTrackDetached: (participantId, track, publication) => {
                console.log(`📹 Video track detached for ${participantId}`);
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
                console.log(`👤 Participant added: ${participant.identity}`);
                this.layout.applyGrid(this.participants.getParticipantCount());
            },
            onParticipantRemoved: (participant, participantId) => {
                console.log(`👤 Participant removed: ${participantId}`);
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
                console.log(`🎨 Layout changed to: ${layoutType}`);
            },
            onFocusEnter: (participantId) => {
                console.log(`🎯 Entered focus mode for: ${participantId}`);
            },
            onFocusExit: (participantId) => {
                console.log(`🔙 Exited focus mode for: ${participantId}`);
            }
        });

        // 5. Initialize controls module (will be set up after connection)
        // This is done in setupControls() after connection is established

        console.log('✅ All modules initialized');
    }

    /**
     * Setup controls after connection is established
     */
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

        // Set global reference for screen share controls
        window.livekitControls = this.controls;

        // Add global debug functions
        window.debugChat = () => {
            if (this.controls) {
                this.controls.debugTestChat();
            } else {
                console.log('❌ No controls available');
            }
        };

        window.debugMeeting = () => {
            console.log('🔍 Meeting debug info:');
            console.log('  - Initialized:', this.isInitialized);
            console.log('  - Connected:', this.isConnected);
            console.log('  - Room state:', this.connection?.getRoom()?.state);
            console.log('  - Local participant:', this.connection?.getLocalParticipant()?.identity);
            console.log('  - Remote participants:', Array.from(this.connection?.getRoom()?.remoteParticipants?.keys() || []));
            console.log('  - Controls available:', !!this.controls);
            return this.getMeetingState();
        };

        window.debugVideos = () => {
            console.log('📹 Video debug info:');
            const videoElements = document.querySelectorAll('video');
            console.log(`  - Total video elements: ${videoElements.length}`);
            
            videoElements.forEach((video, index) => {
                console.log(`  Video ${index + 1}:`);
                console.log(`    - ID: ${video.id}`);
                console.log(`    - Display: ${video.style.display}`);
                console.log(`    - Opacity: ${video.style.opacity}`);
                console.log(`    - Visibility: ${video.style.visibility}`);
                console.log(`    - Source object: ${!!video.srcObject}`);
                console.log(`    - Parent: ${video.parentElement?.id}`);
                console.log(`    - Muted: ${video.muted}`);
                console.log(`    - Autoplay: ${video.autoplay}`);
            });

            const participants = document.querySelectorAll('[id^="participant-"]');
            console.log(`  - Total participant elements: ${participants.length}`);
            
            participants.forEach((participant, index) => {
                const id = participant.id.replace('participant-', '');
                const hasVideo = !!participant.querySelector('video');
                const placeholder = participant.querySelector('.absolute.inset-0.flex.flex-col');
                console.log(`  Participant ${index + 1} (${id}):`);
                console.log(`    - Has video element: ${hasVideo}`);
                console.log(`    - Has placeholder: ${!!placeholder}`);
                console.log(`    - Placeholder opacity: ${placeholder?.style.opacity || 'default'}`);
                console.log(`    - Placeholder background: ${placeholder?.style.backgroundColor || 'default'}`);
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
            console.log('👤 Placeholder debug info:');
            const participants = document.querySelectorAll('[id^="participant-"]');
            
            participants.forEach((participant, index) => {
                const id = participant.id.replace('participant-', '');
                const placeholder = participant.querySelector('.absolute.inset-0.flex.flex-col');
                const video = participant.querySelector('video');
                
                console.log(`  Participant ${index + 1} (${id}):`);
                if (placeholder) {
                    console.log(`    - Placeholder opacity: ${placeholder.style.opacity || 'default'}`);
                    console.log(`    - Placeholder z-index: ${placeholder.style.zIndex || 'default'}`);
                    console.log(`    - Placeholder background: ${placeholder.style.backgroundColor || 'default'}`);
                    
                    const avatar = placeholder.querySelector('.rounded-full');
                    const nameElements = placeholder.querySelectorAll('p');
                    const statusContainer = placeholder.querySelector('.mt-2.flex.items-center.justify-center.gap-3');
                    
                    console.log(`    - Avatar opacity: ${avatar?.style.opacity || 'default'}`);
                    console.log(`    - Name elements: ${nameElements.length}, opacity: ${nameElements[0]?.style.opacity || 'default'}`);
                    console.log(`    - Status container position: ${statusContainer?.style.position || 'default'}`);
                } else {
                    console.log(`    - No placeholder found!`);
                }
                
                if (video) {
                    console.log(`    - Video opacity: ${video.style.opacity || 'default'}`);
                    console.log(`    - Video display: ${video.style.display || 'default'}`);
                    console.log(`    - Video has source: ${!!video.srcObject}`);
                } else {
                    console.log(`    - No video element found!`);
                }
                
                // Check for name overlays
                const overlay = document.getElementById(`name-overlay-${id}`);
                if (overlay) {
                    console.log(`    - Name overlay: FOUND`);
                    console.log(`      - Position: ${overlay.style.position}`);
                    console.log(`      - Bottom: ${overlay.style.bottom}`);
                    console.log(`      - Left: ${overlay.style.left}`);
                    console.log(`      - Z-index: ${overlay.style.zIndex}`);
                    console.log(`      - Parent: ${overlay.parentElement?.id}`);
                } else {
                    console.log(`    - Name overlay: NOT FOUND`);
                }
            });
        };

        // Add test function to manually show overlays
        window.testOverlay = (participantId) => {
            console.log(`🧪 Testing overlay visibility for ${participantId}`);
            const overlay = document.getElementById(`name-overlay-${participantId}`);
            if (overlay) {
                overlay.style.display = 'block';
                overlay.style.opacity = '1';
                console.log(`✅ Test overlay shown for ${participantId}`);
            } else {
                console.error(`❌ Overlay not found for ${participantId}`);
            }
        };

        // Force show overlays for all participants (for testing)
        window.forceShowOverlays = () => {
            console.log(`🧪 Force showing overlays for all participants`);
            const participants = document.querySelectorAll('[id^="participant-"]');
            participants.forEach(participant => {
                const id = participant.id.replace('participant-', '');
                console.log(`🧪 Showing overlay for ${id}`);
                const overlay = document.getElementById(`name-overlay-${id}`);
                if (overlay) {
                    overlay.style.display = 'block';
                    overlay.style.opacity = '1';
                }
            });
        };

        // Force update video display for all participants (for testing)
        window.forceUpdateVideoDisplay = () => {
            console.log(`🧪 Force updating video display for all participants`);
            const participants = document.querySelectorAll('[id^="participant-"]');
            participants.forEach(participant => {
                const id = participant.id.replace('participant-', '');
                console.log(`🧪 Updating video display for ${id}`);
                if (this.tracks && this.tracks.updateVideoDisplay) {
                    // Force video ON state for testing
                    this.tracks.updateVideoDisplay(id, true);
                }
            });
        };

        // Check if HTML overlays exist in DOM
        window.checkOverlays = () => {
            console.log(`🔍 Checking HTML overlays in DOM`);
            const participants = document.querySelectorAll('[id^="participant-"]');
            participants.forEach(participant => {
                const id = participant.id.replace('participant-', '');
                const overlay = document.getElementById(`name-overlay-${id}`);
                if (overlay) {
                    console.log(`✅ Overlay found for ${id}:`, overlay);
                    console.log(`   - Display: ${overlay.style.display}`);
                    console.log(`   - Opacity: ${overlay.style.opacity}`);
                    console.log(`   - Classes: ${overlay.className}`);
                } else {
                    console.log(`❌ No overlay found for ${id}`);
                }
            });
        };

        // Test name cleaning function
        window.testNameCleaning = () => {
            console.log(`🧪 Testing name cleaning function`);
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
                    console.log(`   "${name}" → "${cleanName}"`);
                });
            } else {
                console.error(`❌ Participants module not available`);
            }
        };

        // Test hand raise functionality
        window.testHandRaise = (participantId, isRaised = true) => {
            console.log(`🧪 Testing hand raise for ${participantId}: ${isRaised ? 'RAISE' : 'LOWER'}`);
            if (this.participants && this.participants.updateHandRaiseStatus) {
                this.participants.updateHandRaiseStatus(participantId, isRaised);
            } else {
                console.error(`❌ Participants module not available`);
            }
        };

        // Test hand raise indicators for all participants
        window.testHandRaiseIndicators = () => {
            console.log(`🧪 Testing hand raise indicators`);
            if (this.participants) {
                const participants = document.querySelectorAll('[id^="participant-"]');
                participants.forEach(participant => {
                    const id = participant.id.replace('participant-', '');
                    console.log(`🧪 Testing hand raise for participant ${id}`);
                    // Test show/hide cycle
                    this.participants.showHandRaise(id);
                    setTimeout(() => {
                        this.participants.hideHandRaise(id);
                    }, 2000);
                });
            } else {
                console.error(`❌ Participants module not available`);
            }
        };

        // Test hand raise for specific participant
        window.testHandRaiseForParticipant = (participantId) => {
            console.log(`🧪 Testing hand raise for specific participant: ${participantId}`);
            if (this.participants && this.participants.updateHandRaiseStatus) {
                // Show hand raise
                this.participants.showHandRaise(participantId);
                
                // Hide after 3 seconds
                setTimeout(() => {
                    this.participants.hideHandRaise(participantId);
                }, 3000);
                
                console.log(`✅ Hand raise test completed for ${participantId}`);
            } else {
                console.error(`❌ Participants module not available`);
            }
        };

        // Test hand raise directly (bypasses LiveKit flow)
        window.testHandRaiseDirectly = (participantId) => {
            console.log(`🧪 Testing hand raise directly for ${participantId}`);
            if (this.participants && this.participants.testHandRaiseDirectly) {
                this.participants.testHandRaiseDirectly(participantId);
            } else {
                console.error(`❌ Participants module not available`);
            }
        };

        // Force create hand raise indicator (for debugging)
        window.forceCreateHandRaiseIndicator = (participantId) => {
            console.log(`🧪 Force creating hand raise indicator for ${participantId}`);
            
            const participantElement = document.getElementById(`participant-${participantId}`);
            if (!participantElement) {
                console.error(`❌ Participant element not found: participant-${participantId}`);
                
                // List all available participant elements
                const allParticipants = document.querySelectorAll('[id^="participant-"]');
                console.log(`🔍 Available participants:`, Array.from(allParticipants).map(p => p.id));
                return;
            }
            
            // Remove existing indicator if any
            const existingIndicator = document.getElementById(`hand-raise-${participantId}`);
            if (existingIndicator) {
                existingIndicator.remove();
                console.log(`🗑️ Removed existing indicator`);
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
            
            console.log(`✅ Force created hand raise indicator for ${participantId}:`, handRaiseIndicator);
            
            // Remove after 5 seconds
            setTimeout(() => {
                if (handRaiseIndicator.parentNode) {
                    handRaiseIndicator.remove();
                    console.log(`🗑️ Auto-removed force created indicator`);
                }
            }, 5000);
        };

        // Test direct controls hand raise function
        window.testControlsHandRaise = (participantId = 'local', isRaised = true) => {
            console.log(`🧪 Testing controls hand raise for ${participantId}: ${isRaised ? 'RAISE' : 'LOWER'}`);
            if (this.controls && this.controls.createHandRaiseIndicatorDirect) {
                this.controls.createHandRaiseIndicatorDirect(participantId, isRaised);
            } else {
                console.error(`❌ Controls module not available`);
            }
        };

        console.log('✅ Controls set up successfully');
        console.log('🔍 Debug functions available: window.debugChat(), window.debugMeeting(), window.debugVideos(), window.debugPlaceholders(), window.testOverlay(), window.forceShowOverlays(), window.forceUpdateVideoDisplay(), window.checkOverlays(), window.testNameCleaning(), window.testHandRaise(), window.testHandRaiseIndicators(), window.testHandRaiseForParticipant(), window.testHandRaiseDirectly(), window.forceCreateHandRaiseIndicator(), window.testControlsHandRaise()');
    }

    /**
     * Setup local media (camera and microphone)
     */
    async setupLocalMedia() {
        console.log('🎤 Setting up local media...');

        try {
            const localParticipant = this.connection.getLocalParticipant();

            // Set local participant reference in participants module
            this.participants.setLocalParticipant(localParticipant);

            // Add local participant to UI first
            this.participants.addParticipant(localParticipant);
            
            // Update participants list immediately to show local participant
            this.participants.updateParticipantsList();

            // Request media permissions with better error handling
            let mediaPermissionsGranted = false;
            
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

            // Show a more general message only if no permissions were granted
            if (!mediaPermissionsGranted) {
                this.showNotification('لم يتم منح أي صلاحيات للوسائط. ستتمكن من المشاركة بالدردشة فقط.', 'info');
            }

            // Process existing tracks after a short delay to ensure they're initialized
            console.log('🔄 Processing local tracks...');
            
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
                console.log('🧪 Testing hand raise functionality after initialization...');
                const localId = this.localParticipant.identity;
                console.log(`🧪 Local participant ID: ${localId}`);
                
                // Test direct hand raise
                if (this.participants.testHandRaiseDirectly) {
                    this.participants.testHandRaiseDirectly(localId);
                }
            }
        }, 3000);

            // Start periodic track synchronization check for late joiners
            this.startTrackSyncCheck();

            console.log('✅ Local media setup complete');

        } catch (error) {
            console.error('❌ Failed to setup local media:', error);
            
            // Only show user error for critical failures, not track processing issues
            if (error.name === 'NotAllowedError') {
                this.showNotification('تم رفض الوصول للكاميرا أو الميكروفون', 'error');
            } else if (error.message && error.message.includes('room') || error.message.includes('connection')) {
                this.showNotification('فشل في إعداد الجلسة. يرجى المحاولة مرة أخرى.', 'error');
            } else {
                // For other errors, just log them - don't overwhelm user with technical messages
                console.warn('⚠️ Non-critical media setup error:', error.message);
                this.showNotification('تم الانضمام للجلسة بنجاح. قد تحتاج لتفعيل الكاميرا يدوياً.', 'info');
            }
        }
    }

    /**
     * CRITICAL FIX: Enhanced local media setup with synchronization
     */
    async setupLocalMediaEnhanced() {
        console.log('🎤 [FIXED] Setting up local media with enhanced synchronization...');

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

            console.log('✅ Enhanced local media setup complete');

        } catch (error) {
            console.error('❌ Enhanced media setup failed:', error);
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
            console.log(`📊 [FIXED] Initialized state for ${participantId}:`, state);
        }
    }

    /**
     * CRITICAL FIX: Add participant with synchronization
     */
    async addParticipantWithSync(participant) {
        const participantId = participant.identity;
        
        // Prevent duplicate processing
        if (this.syncInProgress.has(participantId)) {
            console.log(`⏭️ Already syncing ${participantId}, skipping`);
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
            
            console.log(`✅ Participant ${participantId} added with sync`);
            
        } finally {
            this.syncInProgress.delete(participantId);
        }
    }

    /**
     * CRITICAL FIX: Setup media permissions with better error handling
     */
    async setupMediaPermissions(localParticipant) {
        let mediaPermissionsGranted = false;
        
        // Try microphone
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

        // Try camera
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
     * CRITICAL FIX: Process participant tracks with synchronization
     */
    async processParticipantTracksSync(participant) {
        const participantId = participant.identity;
        console.log(`🔄 [FIXED] Processing tracks for ${participantId}...`);

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

        console.log(`✅ Tracks processed for ${participantId}`);
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
        console.log(`📊 [FIXED] Updated state for ${participantId}:`, state);
    }

    /**
     * CRITICAL FIX: Load existing participants with synchronization
     */
    async loadExistingParticipantsSync() {
        console.log('👥 [FIXED] Loading existing participants with sync...');

        const room = this.connection.getRoom();
        if (!room) {
            console.warn('⚠️ Room not available for loading participants');
            return;
        }

        for (const [identity, participant] of room.remoteParticipants) {
            console.log(`👤 [FIXED] Processing existing participant: ${identity}`);
            
            // Initialize state
            this.initializeParticipantState(identity, false);
            
            // Add participant with sync
            await this.addParticipantWithSync(participant);
            
            // Process their tracks
            await this.processParticipantTracksSync(participant);
        }

                    console.log(`✅ Loaded ${room.remoteParticipants.size} existing participants with sync`);
    }

    /**
     * CRITICAL FIX: Handle media setup errors gracefully
     */
    handleMediaSetupError(error) {
        console.error('❌ Media setup error:', error);
        
        if (error.name === 'NotAllowedError') {
            this.showNotification('تم رفض الوصول للكاميرا أو الميكروفون', 'error');
        } else if (error.message && (error.message.includes('room') || error.message.includes('connection'))) {
            this.showNotification('فشل في إعداد الجلسة. يرجى المحاولة مرة أخرى.', 'error');
        } else {
            console.warn('⚠️ Non-critical media setup error:', error.message);
            this.showNotification('تم الانضمام للجلسة بنجاح. قد تحتاج لتفعيل الكاميرا يدوياً.', 'info');
        }
    }

    /**
     * CRITICAL FIX: Start continuous synchronization monitoring
     */
    startContinuousSync() {
        console.log('⏰ [FIXED] Starting continuous synchronization monitoring...');
        
        // Check every 3 seconds for synchronization issues
        this.trackSyncInterval = setInterval(() => {
            this.performSyncCheck();
        }, 3000);
        
        console.log('✅ Continuous sync monitoring started');
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
                console.log(`🔧 [SYNC] Re-syncing participant ${identity}`);
                await this.processParticipantTracksSync(participant);
            }
            
            // Check for missing video elements
            if (participant.videoTracks && participant.videoTracks.size > 0) {
                for (const publication of participant.videoTracks.values()) {
                    if (publication.track && !publication.isMuted) {
                        const videoElement = document.getElementById(`video-${identity}`);
                        if (!videoElement) {
                            console.log(`🔧 [SYNC] Fixing missing video for ${identity}`);
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
        console.log('🔄 Processing local participant tracks...');

        try {
            // Handle video tracks with null checks
            if (localParticipant.videoTracks && localParticipant.videoTracks.size > 0) {
                console.log(`📹 Found ${localParticipant.videoTracks.size} local video track(s)`);
                localParticipant.videoTracks.forEach((publication) => {
                    if (publication && publication.track) {
                        console.log('📹 Processing local video track');
                        this.tracks.handleTrackSubscribed(publication.track, publication, localParticipant);
                    } else {
                        console.log('📹 Local video track not yet available');
                    }
                });
            } else {
                console.log('📹 No local video tracks found yet');
            }

            // Handle audio tracks with null checks
            if (localParticipant.audioTracks && localParticipant.audioTracks.size > 0) {
                console.log(`🎤 Found ${localParticipant.audioTracks.size} local audio track(s)`);
                localParticipant.audioTracks.forEach((publication) => {
                    if (publication && publication.track) {
                        console.log('🎤 Processing local audio track');
                        this.tracks.handleTrackSubscribed(publication.track, publication, localParticipant);
                    } else {
                        console.log('🎤 Local audio track not yet available');
                    }
                });
            } else {
                console.log('🎤 No local audio tracks found yet');
            }

            // Force update video display if camera is enabled and track exists
            // But only after meeting is fully initialized to prevent flickering
            if (localParticipant.isCameraEnabled && localParticipant.videoTracks?.size > 0 && this.isInitialized) {
                console.log('📹 Forcing video display update for local participant');
                setTimeout(() => {
                    this.tracks.updateVideoDisplay(localParticipant.identity, true);
                }, 200); // Small delay to ensure UI is stable
            }

            console.log('✅ Local tracks processed successfully');

        } catch (error) {
            console.error('❌ Error processing local tracks:', error);
            // Don't show user error for track processing issues, as this is internal
            
            // Retry after a longer delay if tracks processing failed
            setTimeout(() => {
                console.log('🔄 Retrying local tracks processing...');
                this.processLocalTracks(localParticipant);
            }, 2000);
        }

        // Also retry if no tracks were found (may be a timing issue)
        const hasVideoTracks = localParticipant.videoTracks?.size > 0;
        const hasAudioTracks = localParticipant.audioTracks?.size > 0;
        
        if (!hasVideoTracks && !hasAudioTracks && localParticipant.isCameraEnabled) {
            console.log('🔄 No tracks found but camera is enabled, will retry in 2 seconds...');
            setTimeout(() => {
                this.processLocalTracks(localParticipant);
            }, 2000);
        }
    }

    /**
     * Load existing participants for late joiners
     */
    loadExistingParticipants() {
        console.log('👥 Loading existing participants...');

        const room = this.connection.getRoom();
        if (!room) {
            console.warn('⚠️ Room not available for loading participants');
            return;
        }

        // Add all existing remote participants
        for (const [identity, participant] of room.remoteParticipants) {
            console.log(`👤 Found existing participant: ${identity}`);

            // Add participant to UI
            this.participants.addParticipant(participant);

            // Handle their existing tracks (with safety checks and forced subscription)
            if (participant.videoTracks && participant.videoTracks.size > 0) {
                participant.videoTracks.forEach((publication) => {
                    console.log(`📹 Processing existing video track from ${identity} - subscribed: ${publication.isSubscribed}, muted: ${publication.isMuted}, hasTrack: ${!!publication.track}`);

                    if (publication.track) {
                        console.log(`📹 Loading existing video track from ${identity}`);
                        this.tracks.handleTrackSubscribed(publication.track, publication, participant);
                    } else if (!publication.isSubscribed && !publication.isMuted) {
                        // Force subscription for unmuted tracks without attached track
                        console.log(`📹 Force subscribing to video track from ${identity}`);
                        this.forceTrackSubscription(participant, publication);
                    }
                });
            }

            if (participant.audioTracks && participant.audioTracks.size > 0) {
                participant.audioTracks.forEach((publication) => {
                    console.log(`🎤 Processing existing audio track from ${identity} - subscribed: ${publication.isSubscribed}, muted: ${publication.isMuted}, hasTrack: ${!!publication.track}`);

                    if (publication.track) {
                        console.log(`🎤 Loading existing audio track from ${identity}`);
                        this.tracks.handleTrackSubscribed(publication.track, publication, participant);
                    } else if (!publication.isSubscribed && !publication.isMuted) {
                        // Force subscription for unmuted tracks without attached track
                        console.log(`🎤 Force subscribing to audio track from ${identity}`);
                        this.forceTrackSubscription(participant, publication);
                    }
                });
            }
        }

        // Update participant count and layout
        this.updateParticipantCount();
        this.participants.updateParticipantsList();
        this.layout.applyGrid(this.participants.getParticipantCount());

        console.log(`✅ Loaded ${room.remoteParticipants.size} existing participants`);

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
            console.log(`📊 Updated participant count to: ${count}`);
        }
    }

    /**
     * Show the meeting interface - ENHANCED WITH SMOOTH TRANSITIONS
     */
    showMeetingInterface() {
        console.log('🎨 [FIXED] Showing meeting interface with smooth transitions...');

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
            console.warn('⚠️ Loading overlay or meeting interface not found');
            return;
        }

        console.log('🎨 Starting smooth transition...');

        // Step 1: Start fading out the loading overlay
        loadingOverlay.classList.add('fade-out');

        // Step 2: Setup controls immediately since meeting interface is already visible
        setTimeout(() => {
            // Meeting interface is already visible, just setup controls
            this.setupControls();
            
            console.log('✅ Meeting interface transition initiated');
        }, 100);

        // Step 3: Completely remove loading overlay after transition completes
        setTimeout(() => {
            if (loadingOverlay.classList.contains('fade-out')) {
                loadingOverlay.style.display = 'none';
                console.log('✅ Loading overlay completely hidden');
            }
        }, 600); // 500ms transition + 100ms buffer

        console.log('✅ Meeting interface shown with smooth transitions');
    }

    /**
     * CRITICAL FIX: Show loading overlay smoothly (for reconnection, etc.)
     */
    showLoadingOverlay(message = 'جاري الاتصال بالاجتماع...') {
        console.log('🔄 [FIXED] Showing loading overlay smoothly...');
        
        const loadingOverlay = document.getElementById('loadingOverlay');
        
        if (!loadingOverlay) {
            console.warn('⚠️ Loading overlay not found');
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
        
        console.log('✅ Loading overlay shown smoothly');
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
        
        console.log('🎨 Loading overlay initialized properly');
    }

    /**
     * Handle connection state changes - ENHANCED WITH LOADING OVERLAY
     * @param {string} state - Connection state
     */
    handleConnectionStateChange(state) {
        console.log(`🔗 [FIXED] Connection state: ${state}`);

        switch (state) {
            case 'connected':
                this.isConnected = true;
                this.showNotification('تم الاتصال بالجلسة بنجاح', 'success');
                break;
            case 'disconnected':
                this.isConnected = false;
                this.showNotification('تم قطع الاتصال بالجلسة', 'error');
                // CRITICAL FIX: Show loading overlay during disconnection
                this.showLoadingOverlay('انقطع الاتصال... جاري المحاولة مرة أخرى');
                break;
            case 'reconnecting':
                this.showNotification('جاري إعادة الاتصال...', 'info');
                // CRITICAL FIX: Show loading overlay during reconnection
                this.showLoadingOverlay('جاري إعادة الاتصال...');
                break;
            case 'reconnected':
                this.showNotification('تم إعادة الاتصال بنجاح', 'success');
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
        console.log(`👤 [FIXED] Participant connected: ${participantId}`);

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
                this.showNotification(`انضم ${displayName} إلى الجلسة`, 'info');

                console.log(`✅ Participant ${participantId} connected and synced`);

            } catch (error) {
                console.error(`❌ Error handling participant connection for ${participantId}:`, error);
            }
        }
    }

    /**
     * Handle participant disconnected - ENHANCED WITH CLEANUP
     * @param {LiveKit.Participant} participant - Disconnected participant
     */
    handleParticipantDisconnected(participant) {
        const participantId = participant.identity;
        console.log(`👤 [FIXED] Participant disconnected: ${participantId}`);

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
        this.showNotification(`غادر ${displayName} الجلسة`, 'info');

        console.log(`✅ Participant ${participantId} disconnected and cleaned up`);
    }

    /**
     * Handle track subscribed - ENHANCED WITH SYNCHRONIZATION
     * @param {LiveKit.Track} track - Subscribed track
     * @param {LiveKit.TrackPublication} publication - Track publication
     * @param {LiveKit.Participant} participant - Participant
     */
    async handleTrackSubscribed(track, publication, participant) {
        const participantId = participant.identity;
        console.log(`📹 [FIXED] Track subscribed: ${track.kind} from ${participantId} (local: ${participant.isLocal})`);
        
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
            console.log(`⏭️ Sync already in progress for ${participantId}, skipping`);
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
                    console.log(`🔧 [SYNC] Adding missing participant ${participantId}`);
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
        console.log(`📹 Track unsubscribed: ${track.kind} from ${participant.identity}`);
        this.tracks.handleTrackUnsubscribed(track, publication, participant);
    }

    /**
     * Handle track published (for local participant mainly)
     * @param {LiveKit.TrackPublication} publication - Track publication
     * @param {LiveKit.Participant} participant - Participant
     */
    handleTrackPublished(publication, participant) {
        console.log(`📹 Track published: ${publication.kind} from ${participant.identity} (local: ${participant.isLocal})`);

        // For local participant, create the track handling as if it was subscribed
        if (participant.isLocal && publication.track) {
            console.log(`📹 Processing local published track: ${publication.kind}`);
            this.tracks.handleTrackSubscribed(publication.track, publication, participant);
        }
    }

    /**
     * Handle track unpublished (for local participant mainly)
     * @param {LiveKit.TrackPublication} publication - Track publication
     * @param {LiveKit.Participant} participant - Participant
     */
    handleTrackUnpublished(publication, participant) {
        console.log(`📹 Track unpublished: ${publication.kind} from ${participant.identity} (local: ${participant.isLocal})`);

        // For local participant, handle as if it was unsubscribed
        if (participant.isLocal && publication.track) {
            console.log(`📹 Processing local unpublished track: ${publication.kind}`);
            this.tracks.handleTrackUnsubscribed(publication.track, publication, participant);
        }
    }

    /**
     * Handle track muted
     * @param {LiveKit.TrackPublication} publication - Track publication
     * @param {LiveKit.Participant} participant - Participant
     */
    handleTrackMuted(publication, participant) {
        console.log(`🔇 Track muted: ${publication.kind} from ${participant.identity}`);
        this.tracks.handleTrackMuted(publication, participant);
    }

    /**
     * Handle track unmuted
     * @param {LiveKit.TrackPublication} publication - Track publication
     * @param {LiveKit.Participant} participant - Participant
     */
    handleTrackUnmuted(publication, participant) {
        console.log(`🔊 Track unmuted: ${publication.kind} from ${participant.identity} (local: ${participant.isLocal})`);
        this.tracks.handleTrackUnmuted(publication, participant);

        // CRITICAL: For remote participants, ensure all users can see the track when it's unmuted
        if (!participant.isLocal) {
            console.log(`📹 Remote participant ${participant.identity} unmuted ${publication.kind}, ensuring all users can see it`);

            // Check if we have the track, if not, force subscription
            if (!publication.track && !publication.isSubscribed) {
                console.log(`📹 No track available for unmuted ${publication.kind} from ${participant.identity}, force subscribing...`);
                this.forceTrackSubscription(participant, publication);
            } else if (publication.track) {
                // Ensure the track is properly processed even if we already have it
                console.log(`📹 Re-processing existing track for ${participant.identity} after unmute`);
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
        console.log(`🗣️ Active speakers changed:`, speakers.map(s => s.identity));

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
            console.log(`📦 ==== DATA RECEIVED EVENT ====`);
            console.log(`📦 Raw data received:`);
            console.log(`  - From participant: ${participant?.identity}`);
            console.log(`  - Participant SID: ${participant?.sid}`);
            console.log(`  - Is local: ${participant?.isLocal}`);
            console.log(`  - Payload size: ${payload?.length} bytes`);
            console.log(`  - Payload type: ${payload?.constructor?.name}`);

            // Enhanced local participant context
            const localParticipant = this.connection?.getLocalParticipant();
            console.log(`📋 Current session context:`);
            console.log(`  - Local participant: ${localParticipant?.identity}`);
            console.log(`  - Local SID: ${localParticipant?.sid}`);
            console.log(`  - Room state: ${this.connection?.getRoom()?.state}`);
            console.log(`  - Total participants: ${this.connection?.getRoom()?.numParticipants}`);

            // List all participants for debugging
            console.log(`📋 All participants in room:`);
            if (localParticipant) {
                console.log(`  - LOCAL: ${localParticipant.identity} (SID: ${localParticipant.sid})`);
            }
            this.connection?.getRoom()?.remoteParticipants?.forEach((participant, sid) => {
                console.log(`  - REMOTE: ${participant.identity} (SID: ${sid})`);
            });

            // Comprehensive payload validation
            if (!payload) {
                console.error('❌ NULL payload received');
                return;
            }

            if (payload.length === 0) {
                console.error('❌ Empty payload received (0 bytes)');
                return;
            }

            // Enhanced participant validation
            if (!participant) {
                console.error('❌ No participant information received');
                return;
            }

            // Check if this is from ourselves (should not happen with proper broadcasting)
            if (participant.isLocal) {
                console.log('💬 Received data from local participant (echo) - this is normal for testing');
                // Don't return - process it for testing purposes
            }

            console.log(`🔄 Attempting to decode payload...`);

            // Decode the payload with enhanced error handling
            let decodedString;
            try {
                decodedString = new TextDecoder().decode(payload);
                console.log(`🔄 Decoded string: "${decodedString}"`);
            } catch (decodeError) {
                console.error('❌ Failed to decode payload as UTF-8:', decodeError);
                console.error('❌ Raw payload bytes:', Array.from(payload).slice(0, 50)); // Show first 50 bytes
                return;
            }

            // Parse JSON with enhanced error handling
            let data;
            try {
                data = JSON.parse(decodedString);
                console.log(`📦 Successfully parsed JSON data:`, data);
            } catch (parseError) {
                console.error('❌ Failed to parse decoded string as JSON:', parseError);
                console.error('❌ Decoded string was:', decodedString);
                return;
            }

            // Enhanced data structure validation
            console.log(`🔍 Validating data structure:`);
            console.log(`  - Type: ${data.type}`);
            console.log(`  - Sender: ${data.sender}`);
            console.log(`  - Sender SID: ${data.senderSid}`);
            console.log(`  - Message ID: ${data.messageId || 'no-id'}`);
            console.log(`  - Timestamp: ${data.timestamp}`);

            if (!data.type) {
                console.error('❌ Data missing required "type" field:', data);
                return;
            }

            // Check for sender mismatch (debugging)
            if (data.sender && participant.identity && data.sender !== participant.identity) {
                console.warn(`⚠️ Sender mismatch - data.sender: "${data.sender}", participant.identity: "${participant.identity}"`);
            }

            if (data.senderSid && participant.sid && data.senderSid !== participant.sid) {
                console.warn(`⚠️ Sender SID mismatch - data.senderSid: "${data.senderSid}", participant.sid: "${participant.sid}"`);
            }

            // Forward to controls with comprehensive validation
            if (this.controls) {
                console.log(`📦 ✅ FORWARDING DATA TO CONTROLS for processing`);
                console.log(`📦 Controls module is available and ready`);

                this.controls.handleDataReceived(data, participant);

                console.log(`📦 ✅ DATA SUCCESSFULLY FORWARDED TO CONTROLS`);
            } else {
                console.error('❌ CRITICAL: Controls module not available to handle data');
                console.error('❌ This means chat messages cannot be processed!');

                // Try to provide helpful debugging info
                console.error('❌ Debug info:');
                console.error(`  - this.controls exists: ${!!this.controls}`);
                console.error(`  - this.controls type: ${typeof this.controls}`);
                console.error(`  - Meeting initialized: ${this.isInitialized}`);
                console.error(`  - Meeting connected: ${this.isConnected}`);
            }

            console.log(`📦 ==== END DATA RECEIVED EVENT ====`);

        } catch (error) {
            console.error('❌ CRITICAL ERROR in handleDataReceived:', error);
            console.error('❌ Error details:', {
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
                console.error('❌ Payload debugging:');
                console.error(`  - Length: ${payload.length}`);
                console.error(`  - Type: ${payload.constructor?.name}`);
                console.error(`  - First 20 bytes:`, Array.from(payload.slice(0, 20)));

                try {
                    const asString = new TextDecoder().decode(payload);
                    console.error(`  - As string: "${asString}"`);
                } catch (e) {
                    console.error(`  - Cannot decode as string: ${e.message}`);
                }
            }

            // Try to show a user-friendly error if controls are available
            if (this.controls && typeof this.controls.showNotification === 'function') {
                this.controls.showNotification('خطأ في تلقي بيانات الدردشة', 'error');
            }
        }
    }

    /**
     * Handle camera state changed
     * @param {string} participantId - Participant ID
     * @param {boolean} hasVideo - Whether participant has active video
     */
    handleCameraStateChanged(participantId, hasVideo) {
        console.log(`📹 Camera state changed for ${participantId}: ${hasVideo ? 'ON' : 'OFF'}`);

        // Update participant list status
        this.participants.updateParticipantListStatus(participantId, 'cam', hasVideo);
    }

    /**
     * Handle microphone state changed
     * @param {string} participantId - Participant ID
     * @param {boolean} hasAudio - Whether participant has active audio
     */
    handleMicrophoneStateChanged(participantId, hasAudio) {
        console.log(`🎤 Microphone state changed for ${participantId}: ${hasAudio ? 'ON' : 'OFF'}`);

        // Update participant list status
        this.participants.updateParticipantListStatus(participantId, 'mic', hasAudio);
    }

    /**
     * Handle participant click for focus mode
     * @param {HTMLElement} participantElement - Participant DOM element
     * @param {LiveKit.Participant} participant - Participant object
     */
    handleParticipantClick(participantElement, participant) {
        console.log(`👆 Participant clicked: ${participant.identity}`);

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
        console.log('🚪 Handling leave request...');

        this.destroy().then(() => {
            // Simply reload the current page instead of redirecting
            console.log('🔄 Reloading current page after meeting cleanup');
            window.location.reload();
        }).catch(error => {
            console.error('❌ Error during meeting cleanup:', error);
            // Still reload even if cleanup fails
            console.log('🔄 Reloading current page despite cleanup error');
            window.location.reload();
        });
    }

    /**
     * Show notification to user
     * @param {string} message - Notification message
     * @param {string} type - Notification type ('success', 'error', 'info')
     */
    showNotification(message, type = 'info') {
        console.log(`📢 Notification (${type}): ${message}`);

        // Create notification element
        const notification = document.createElement('div');
        notification.className = `fixed top-4 right-4 z-50 p-4 rounded-lg shadow-lg transition-all duration-300 transform translate-x-full`;

        // Set colors based on type
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

        // Animate in
        setTimeout(() => {
            notification.classList.remove('translate-x-full');
        }, 100);

        // Auto remove after 5 seconds
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

    /**
     * Show error message
     * @param {string} message - Error message
     */
    showError(message) {
        console.error('❌ Meeting error:', message);
        alert(message); // Fallback to alert, could be replaced with better UI
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
            console.log('⚠️ Meeting already destroyed');
            return;
        }

        console.log('🧹 Destroying LiveKit meeting...');

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

            console.log('✅ Meeting destroyed successfully');

        } catch (error) {
            console.error('❌ Error during meeting destruction:', error);
        }
    }

    /**
     * Force track subscription for a specific participant and publication
     * @param {LiveKit.Participant} participant - The participant
     * @param {LiveKit.TrackPublication} publication - The track publication
     */
    async forceTrackSubscription(participant, publication) {
        console.log(`🔄 Force subscribing to ${publication.kind} track from ${participant.identity}`);

        try {
            // Use LiveKit SDK to manually subscribe to the track
            await participant.subscribeToTrack(publication);
            console.log(`✅ Successfully force subscribed to ${publication.kind} track from ${participant.identity}`);

            // Wait a bit for track to be available
            setTimeout(() => {
                if (publication.track) {
                    console.log(`📹 Force subscription resulted in track, processing...`);
                    this.tracks.handleTrackSubscribed(publication.track, publication, participant);
                } else {
                    console.warn(`⚠️ Force subscription completed but no track available for ${participant.identity}`);
                }
            }, 500);

        } catch (error) {
            console.error(`❌ Failed to force subscribe to track from ${participant.identity}:`, error);
        }
    }

    /**
     * Ensure all tracks are properly subscribed for late joiners
     */
    async ensureAllTracksSubscribed() {
        console.log('🔄 Ensuring all tracks are subscribed...');

        const room = this.connection.getRoom();
        if (!room) {
            console.warn('⚠️ Room not available for track subscription check');
            return;
        }

        for (const [identity, participant] of room.remoteParticipants) {
            console.log(`📊 Checking track subscriptions for ${identity}`);

            // Check video tracks
            if (participant.videoTracks && participant.videoTracks.size > 0) {
                participant.videoTracks.forEach(async (publication) => {
                    if (!publication.isSubscribed && !publication.isMuted) {
                        console.log(`📹 Found unsubscribed video track for ${identity}, force subscribing...`);
                        await this.forceTrackSubscription(participant, publication);
                    }
                });
            }

            // Check audio tracks
            if (participant.audioTracks && participant.audioTracks.size > 0) {
                participant.audioTracks.forEach(async (publication) => {
                    if (!publication.isSubscribed && !publication.isMuted) {
                        console.log(`🎤 Found unsubscribed audio track for ${identity}, force subscribing...`);
                        await this.forceTrackSubscription(participant, publication);
                    }
                });
            }
        }

        console.log('✅ Track subscription check completed');
    }

    /**
     * Ensure participant synchronization across all users
     */
    ensureParticipantSync() {
        console.log('🔄 Ensuring participant synchronization...');

        const room = this.connection.getRoom();
        if (!room) {
            console.warn('⚠️ Room not available for participant sync');
            return;
        }

        // Check if any participants are missing from UI
        for (const [identity, participant] of room.remoteParticipants) {
            const participantElement = document.getElementById(`participant-${identity}`);
            if (!participantElement) {
                console.log(`👤 Missing participant ${identity} from UI, adding...`);
                this.participants.addParticipant(participant);
            }

            // Check if their tracks are properly handled
            if (participant.videoTracks && participant.videoTracks.size > 0) {
                participant.videoTracks.forEach((publication) => {
                    if (publication.track && !publication.isMuted) {
                        const videoElement = document.getElementById(`video-${identity}`);
                        if (!videoElement) {
                            console.log(`📹 Missing video element for ${identity}, creating...`);
                            this.tracks.handleTrackSubscribed(publication.track, publication, participant);
                        }
                    }
                });
            }
        }

        // Update UI elements
        this.participants.updateParticipantsList();
        this.layout.applyGrid(this.participants.getParticipantCount());

        console.log('✅ Participant synchronization completed');
    }

    /**
     * Start periodic track synchronization check
     */
    startTrackSyncCheck() {
        console.log('⏰ Starting periodic track synchronization check...');

        // Check every 5 seconds for missing tracks
        this.trackSyncInterval = setInterval(() => {
            this.checkAndFixMissingTracks();
        }, 5000);

        console.log('✅ Track sync check started');
    }

    /**
     * Stop periodic track synchronization check
     */
    stopTrackSyncCheck() {
        if (this.trackSyncInterval) {
            clearInterval(this.trackSyncInterval);
            this.trackSyncInterval = null;
            console.log('⏹️ Track sync check stopped');
        }
    }

    /**
     * Force subscribe to all available tracks for better reliability
     */
    async forceSubscribeToAllTracks() {
        console.log('🔄 Force subscribing to all available tracks...');

        const room = this.connection.getRoom();
        if (!room) {
            console.warn('⚠️ Room not available for force subscription');
            return;
        }

        for (const [identity, participant] of room.remoteParticipants) {
            console.log(`🔄 Checking tracks for ${identity}...`);

            // Force subscribe to video tracks
            if (participant.videoTracks && participant.videoTracks.size > 0) {
                for (const publication of participant.videoTracks.values()) {
                    if (!publication.isSubscribed && !publication.isMuted) {
                        console.log(`📹 Force subscribing to video track from ${identity}`);
                        try {
                            await publication.setSubscribed(true);
                        } catch (error) {
                            console.warn(`⚠️ Could not force subscribe to video track from ${identity}:`, error);
                        }
                    }
                }
            }

            // Force subscribe to audio tracks
            if (participant.audioTracks && participant.audioTracks.size > 0) {
                for (const publication of participant.audioTracks.values()) {
                    if (!publication.isSubscribed && !publication.isMuted) {
                        console.log(`🎤 Force subscribing to audio track from ${identity}`);
                        try {
                            await publication.setSubscribed(true);
                        } catch (error) {
                            console.warn(`⚠️ Could not force subscribe to audio track from ${identity}:`, error);
                        }
                    }
                }
            }
        }

        console.log('✅ Force subscription completed');
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
                        console.log(`🔧 Fixing missing video for ${identity}`);

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
 * Global meeting instance for backward compatibility
 */
let globalMeetingInstance = null;

/**
 * Initialize a new LiveKit meeting instance
 * @param {Object} config - Meeting configuration
 * @returns {Promise<LiveKitMeeting>} Meeting instance
 */
async function initializeLiveKitMeeting(config) {
    if (globalMeetingInstance) {
        console.log('⚠️ Meeting already exists, destroying previous instance');
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
