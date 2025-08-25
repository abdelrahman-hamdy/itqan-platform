/**
 * LiveKit Meeting Integration - Main Entry Point
 * Modular, event-driven LiveKit meeting implementation
 * Replaces the monolithic ProfessionalLiveKitMeeting class
 */

import { LiveKitConnection } from './connection.js';
import { LiveKitParticipants } from './participants.js';
import { LiveKitTracks } from './tracks.js';
import { LiveKitLayout } from './layout.js';
import { LiveKitControls } from './controls.js';

/**
 * Main LiveKit Meeting class that coordinates all modules
 */
export class LiveKitMeeting {
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

        console.log('🚀 LiveKitMeeting initialized with config:', config);
    }

    /**
     * Initialize all modules and start the meeting
     * @returns {Promise<void>}
     */
    async init() {
        if (this.isInitialized) {
            console.log('⚠️ Meeting already initialized');
            return;
        }

        try {
            console.log('🔧 Initializing LiveKit meeting modules...');

            // Initialize modules in correct order
            await this.initializeModules();

            // Connect to the room
            await this.connection.connect();

            // Setup local media
            await this.setupLocalMedia();

            // Show meeting interface
            this.showMeetingInterface();

            this.isInitialized = true;
            this.isConnected = true;

            console.log('✅ LiveKit meeting initialized successfully');

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

        console.log('✅ Controls set up successfully');
        console.log('🔍 Debug functions available: window.debugChat(), window.debugMeeting()');
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

            // Enable microphone and camera by default - this will trigger track publishing
            console.log('🎤 Enabling microphone...');
            await localParticipant.setMicrophoneEnabled(true);

            console.log('📹 Enabling camera...');
            await localParticipant.setCameraEnabled(true);

            // Wait for tracks to be published and ensure they're properly handled
            const waitForTracks = async () => {
                let attempts = 0;
                const maxAttempts = 10;

                while (attempts < maxAttempts) {
                    console.log(`🔄 Checking for local tracks (attempt ${attempts + 1}/${maxAttempts})...`);

                    let videoTrackFound = false;
                    let audioTrackFound = false;

                    // Check for video tracks (with safety check) - don't require unmuted
                    if (localParticipant.videoTracks && localParticipant.videoTracks.size > 0) {
                        localParticipant.videoTracks.forEach((publication) => {
                            if (publication.track) {
                                console.log(`📹 Found local video track - muted: ${publication.isMuted}, track: ${publication.track.kind}`);
                                this.tracks.handleTrackSubscribed(publication.track, publication, localParticipant);
                                videoTrackFound = true;
                            }
                        });
                    } else {
                        console.log(`📹 No video tracks found yet - videoTracks size: ${localParticipant.videoTracks ? localParticipant.videoTracks.size : 'undefined'}`);
                    }

                    // Check for audio tracks (with safety check) - don't require unmuted
                    if (localParticipant.audioTracks && localParticipant.audioTracks.size > 0) {
                        localParticipant.audioTracks.forEach((publication) => {
                            if (publication.track) {
                                console.log(`🎤 Found local audio track - muted: ${publication.isMuted}, track: ${publication.track.kind}`);
                                this.tracks.handleTrackSubscribed(publication.track, publication, localParticipant);
                                audioTrackFound = true;
                            }
                        });
                    } else {
                        console.log(`🎤 No audio tracks found yet - audioTracks size: ${localParticipant.audioTracks ? localParticipant.audioTracks.size : 'undefined'}`);
                    }

                    if (videoTrackFound || attempts >= maxAttempts - 1) {
                        console.log(`✅ Track processing complete - Video: ${videoTrackFound}, Audio: ${audioTrackFound}`);
                        break;
                    }

                    attempts++;
                    await new Promise(resolve => setTimeout(resolve, 300));
                }

                // Always update control states to match actual device states
                if (this.controls) {
                    this.controls.isAudioEnabled = localParticipant.isMicrophoneEnabled;
                    this.controls.isVideoEnabled = localParticipant.isCameraEnabled;
                    this.controls.updateControlButtons();
                    console.log('🎮 Control states synced after track setup');
                }

                // Force update video display to ensure it shows if camera is enabled
                if (localParticipant.isCameraEnabled) {
                    console.log('📹 Forcing video display update for local participant');
                    this.tracks.updateVideoDisplay(localParticipant.identity, true);
                }
            };

            await waitForTracks();

            // Also load existing remote participants for late joiners
            this.loadExistingParticipants();

            // Start periodic track synchronization check for late joiners
            this.startTrackSyncCheck();

            console.log('✅ Local media setup complete');

        } catch (error) {
            console.error('❌ Failed to setup local media:', error);
            this.showNotification('فشل في الوصول للكاميرا أو الميكروفون', 'error');
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
     * Show the meeting interface
     */
    showMeetingInterface() {
        console.log('🎨 Showing meeting interface...');

        // Hide loading overlay
        const loadingOverlay = document.getElementById('loadingOverlay');
        if (loadingOverlay) {
            loadingOverlay.style.display = 'none';
        }

        // Show meeting interface
        const meetingInterface = document.getElementById('meetingInterface');
        if (meetingInterface) {
            meetingInterface.style.display = 'block';
        }

        // Setup controls after interface is shown
        this.setupControls();

        console.log('✅ Meeting interface shown');
    }

    /**
     * Handle connection state changes
     * @param {string} state - Connection state
     */
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

    /**
     * Handle participant connected
     * @param {LiveKit.Participant} participant - Connected participant
     */
    handleParticipantConnected(participant) {
        console.log(`👤 Participant connected: ${participant.identity}`);

        // Don't add local participant here - it's added in setupLocalMedia
        if (!participant.isLocal) {
            this.participants.addParticipant(participant);
            this.participants.updateParticipantsList();
            this.updateParticipantCount();
        }
    }

    /**
     * Handle participant disconnected
     * @param {LiveKit.Participant} participant - Disconnected participant
     */
    handleParticipantDisconnected(participant) {
        console.log(`👤 Participant disconnected: ${participant.identity}`);

        this.participants.removeParticipant(participant.identity);
        this.participants.updateParticipantsList();
        this.updateParticipantCount();
    }

    /**
     * Handle track subscribed
     * @param {LiveKit.Track} track - Subscribed track
     * @param {LiveKit.TrackPublication} publication - Track publication
     * @param {LiveKit.Participant} participant - Participant
     */
    handleTrackSubscribed(track, publication, participant) {
        console.log(`📹 Track subscribed: ${track.kind} from ${participant.identity} (local: ${participant.isLocal})`);
        this.tracks.handleTrackSubscribed(track, publication, participant);

        // Ensure all participants are properly synchronized
        this.ensureParticipantSync();
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
            // Stop track synchronization check
            this.stopTrackSyncCheck();

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
 * Initialize LiveKit meeting (global function for backward compatibility)
 * @param {Object} config - Meeting configuration
 * @returns {Promise<LiveKitMeeting>} Meeting instance
 */
export async function initializeLiveKitMeeting(config) {
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
export function getCurrentMeeting() {
    return globalMeetingInstance;
}

/**
 * Destroy current meeting instance
 * @returns {Promise<void>}
 */
export async function destroyCurrentMeeting() {
    if (globalMeetingInstance) {
        await globalMeetingInstance.destroy();
        globalMeetingInstance = null;
    }
}

// Export main class as default
export default LiveKitMeeting;
