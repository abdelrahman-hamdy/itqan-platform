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

        console.log('✅ Controls set up successfully');
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

            // Add local participant to UI
            this.participants.addParticipant(localParticipant);

            // Request media permissions and enable devices
            const stream = await navigator.mediaDevices.getUserMedia({
                audio: true,
                video: true
            });

            console.log('✅ Media permissions granted');

            // Enable microphone and camera by default
            await localParticipant.setMicrophoneEnabled(true);
            await localParticipant.setCameraEnabled(true);

            console.log('✅ Local media setup complete');

        } catch (error) {
            console.error('❌ Failed to setup local media:', error);
            this.showNotification('فشل في الوصول للكاميرا أو الميكروفون', 'error');
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
        const meetingInterface = document.getElementById('livekitMeetingInterface');
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
    }

    /**
     * Handle track subscribed
     * @param {LiveKit.Track} track - Subscribed track
     * @param {LiveKit.TrackPublication} publication - Track publication
     * @param {LiveKit.Participant} participant - Participant
     */
    handleTrackSubscribed(track, publication, participant) {
        console.log(`📹 Track subscribed: ${track.kind} from ${participant.identity}`);
        this.tracks.handleTrackSubscribed(track, publication, participant);
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
        console.log(`🔊 Track unmuted: ${publication.kind} from ${participant.identity}`);
        this.tracks.handleTrackUnmuted(publication, participant);
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
            const data = JSON.parse(new TextDecoder().decode(payload));
            console.log(`📦 Data received from ${participant?.identity}:`, data);

            if (this.controls) {
                this.controls.handleDataReceived(data, participant);
            }
        } catch (error) {
            console.error('❌ Failed to parse received data:', error);
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
            // Redirect to appropriate page based on role
            const redirectUrl = this.config.role === 'teacher' ? '/teacher/sessions' : '/student/sessions';
            window.location.href = redirectUrl;
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
            // Destroy modules in reverse order
            if (this.controls) {
                this.controls.destroy();
                this.controls = null;
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
