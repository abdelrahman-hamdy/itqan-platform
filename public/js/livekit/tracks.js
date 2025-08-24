/**
 * LiveKit Tracks Module
 * Handles track subscription, attachment/detachment, and media element management
 * Replaces manual camera detection with proper SDK events
 */

/**
 * Track manager for LiveKit media tracks
 */
export class LiveKitTracks {
    /**
     * Create a new tracks manager
     * @param {Object} config - Configuration object
     * @param {Function} config.onVideoTrackAttached - Callback when video track is attached
     * @param {Function} config.onVideoTrackDetached - Callback when video track is detached
     * @param {Function} config.onAudioTrackAttached - Callback when audio track is attached
     * @param {Function} config.onAudioTrackDetached - Callback when audio track is detached
     * @param {Function} config.onCameraStateChanged - Callback when camera state changes
     * @param {Function} config.onMicrophoneStateChanged - Callback when microphone state changes
     */
    constructor(config = {}) {
        this.config = config;
        this.participantTracks = new Map(); // participantId -> { video: HTMLVideoElement, audio: HTMLAudioElement }
        this.trackStates = new Map(); // participantId -> { hasVideo: boolean, hasAudio: boolean, videoMuted: boolean, audioMuted: boolean }

        console.log('🎬 LiveKitTracks initialized');
    }

    /**
     * Handle track subscribed event
     * @param {LiveKit.Track} track - The subscribed track
     * @param {LiveKit.TrackPublication} publication - The track publication
     * @param {LiveKit.Participant} participant - The participant
     */
    handleTrackSubscribed(track, publication, participant) {
        console.log(`📹 Track subscribed: ${track.kind} from ${participant.identity}`);

        const participantId = participant.identity;
        const isLocal = participant.isLocal;

        // Initialize participant state if not exists
        if (!this.trackStates.has(participantId)) {
            this.trackStates.set(participantId, {
                hasVideo: false,
                hasAudio: false,
                videoMuted: true,
                audioMuted: true
            });
        }

        if (track.kind === 'video') {
            this.handleVideoTrackSubscribed(track, publication, participant);
        } else if (track.kind === 'audio') {
            this.handleAudioTrackSubscribed(track, publication, participant);
        }

        // Update participant state
        this.updateParticipantTrackState(participantId, publication);
    }

    /**
     * Handle video track subscribed
     * @param {LiveKit.VideoTrack} track - The video track
     * @param {LiveKit.TrackPublication} publication - The track publication
     * @param {LiveKit.Participant} participant - The participant
     */
    handleVideoTrackSubscribed(track, publication, participant) {
        const participantId = participant.identity;
        const isLocal = participant.isLocal;

        console.log(`📹 Video track subscribed for ${participantId}`);

        // Get or create video element
        const videoElement = this.getOrCreateVideoElement(participantId, isLocal);

        // Attach track to video element
        track.attach(videoElement);

        // Update video visibility and overlay state
        this.updateVideoDisplay(participantId, !publication.isMuted);

        // Store track reference
        if (!this.participantTracks.has(participantId)) {
            this.participantTracks.set(participantId, {});
        }
        this.participantTracks.get(participantId).video = videoElement;

        // Notify callback
        if (this.config.onVideoTrackAttached) {
            this.config.onVideoTrackAttached(participantId, videoElement, track, publication);
        }

        console.log(`✅ Video track attached for ${participantId}`);
    }

    /**
     * Handle audio track subscribed
     * @param {LiveKit.AudioTrack} track - The audio track
     * @param {LiveKit.TrackPublication} publication - The track publication
     * @param {LiveKit.Participant} participant - The participant
     */
    handleAudioTrackSubscribed(track, publication, participant) {
        const participantId = participant.identity;
        const isLocal = participant.isLocal;

        console.log(`🎤 Audio track subscribed for ${participantId}`);

        // For local participant, don't attach audio to prevent feedback
        if (!isLocal) {
            // Get or create audio element
            const audioElement = this.getOrCreateAudioElement(participantId);

            // Attach track to audio element
            track.attach(audioElement);

            // Store track reference
            if (!this.participantTracks.has(participantId)) {
                this.participantTracks.set(participantId, {});
            }
            this.participantTracks.get(participantId).audio = audioElement;

            // Notify callback
            if (this.config.onAudioTrackAttached) {
                this.config.onAudioTrackAttached(participantId, audioElement, track, publication);
            }
        }

        console.log(`✅ Audio track handled for ${participantId}`);
    }

    /**
     * Handle track unsubscribed event
     * @param {LiveKit.Track} track - The unsubscribed track
     * @param {LiveKit.TrackPublication} publication - The track publication
     * @param {LiveKit.Participant} participant - The participant
     */
    handleTrackUnsubscribed(track, publication, participant) {
        console.log(`📹 Track unsubscribed: ${track.kind} from ${participant.identity}`);

        const participantId = participant.identity;

        if (track.kind === 'video') {
            this.handleVideoTrackUnsubscribed(track, publication, participant);
        } else if (track.kind === 'audio') {
            this.handleAudioTrackUnsubscribed(track, publication, participant);
        }

        // Update participant state
        this.updateParticipantTrackState(participantId, publication);
    }

    /**
     * Handle video track unsubscribed
     * @param {LiveKit.VideoTrack} track - The video track
     * @param {LiveKit.TrackPublication} publication - The track publication
     * @param {LiveKit.Participant} participant - The participant
     */
    handleVideoTrackUnsubscribed(track, publication, participant) {
        const participantId = participant.identity;

        console.log(`📹 Video track unsubscribed for ${participantId}`);

        // Detach track
        track.detach();

        // Update video display - hide video, show camera off overlay
        this.updateVideoDisplay(participantId, false);

        // Remove track reference
        if (this.participantTracks.has(participantId)) {
            delete this.participantTracks.get(participantId).video;
        }

        // Notify callback
        if (this.config.onVideoTrackDetached) {
            this.config.onVideoTrackDetached(participantId, track, publication);
        }

        console.log(`✅ Video track detached for ${participantId}`);
    }

    /**
     * Handle audio track unsubscribed
     * @param {LiveKit.AudioTrack} track - The audio track
     * @param {LiveKit.TrackPublication} publication - The track publication
     * @param {LiveKit.Participant} participant - The participant
     */
    handleAudioTrackUnsubscribed(track, publication, participant) {
        const participantId = participant.identity;

        console.log(`🎤 Audio track unsubscribed for ${participantId}`);

        // Detach track
        track.detach();

        // Remove track reference
        if (this.participantTracks.has(participantId)) {
            delete this.participantTracks.get(participantId).audio;
        }

        // Notify callback
        if (this.config.onAudioTrackDetached) {
            this.config.onAudioTrackDetached(participantId, track, publication);
        }

        console.log(`✅ Audio track detached for ${participantId}`);
    }

    /**
     * Handle track muted event
     * @param {LiveKit.TrackPublication} publication - The track publication
     * @param {LiveKit.Participant} participant - The participant
     */
    handleTrackMuted(publication, participant) {
        const participantId = participant.identity;

        console.log(`🔇 Track muted: ${publication.kind} from ${participantId}`);

        if (publication.kind === 'video') {
            // Hide video, show camera off overlay
            this.updateVideoDisplay(participantId, false);

            // Notify camera state change
            if (this.config.onCameraStateChanged) {
                this.config.onCameraStateChanged(participantId, false);
            }
        } else if (publication.kind === 'audio') {
            // Notify microphone state change
            if (this.config.onMicrophoneStateChanged) {
                this.config.onMicrophoneStateChanged(participantId, false);
            }
        }

        // Update participant state
        this.updateParticipantTrackState(participantId, publication);
    }

    /**
     * Handle track unmuted event
     * @param {LiveKit.TrackPublication} publication - The track publication
     * @param {LiveKit.Participant} participant - The participant
     */
    handleTrackUnmuted(publication, participant) {
        const participantId = participant.identity;

        console.log(`🔊 Track unmuted: ${publication.kind} from ${participantId}`);

        if (publication.kind === 'video') {
            // Show video, hide camera off overlay
            this.updateVideoDisplay(participantId, true);

            // Notify camera state change
            if (this.config.onCameraStateChanged) {
                this.config.onCameraStateChanged(participantId, true);
            }
        } else if (publication.kind === 'audio') {
            // Notify microphone state change
            if (this.config.onMicrophoneStateChanged) {
                this.config.onMicrophoneStateChanged(participantId, true);
            }
        }

        // Update participant state
        this.updateParticipantTrackState(participantId, publication);
    }

    /**
     * Get or create video element for participant
     * @param {string} participantId - Participant ID
     * @param {boolean} isLocal - Whether this is the local participant
     * @returns {HTMLVideoElement} Video element
     */
    getOrCreateVideoElement(participantId, isLocal = false) {
        const participantElement = document.getElementById(`participant-${participantId}`);
        if (!participantElement) {
            console.error(`❌ Participant element not found for ${participantId}`);
            return null;
        }

        let videoElement = participantElement.querySelector('video');

        if (!videoElement) {
            videoElement = document.createElement('video');
            videoElement.className = 'absolute inset-0 w-full h-full object-cover opacity-0 transition-opacity duration-300';
            videoElement.autoplay = true;
            videoElement.playsInline = true;
            videoElement.muted = isLocal; // Mute local video to avoid feedback
            videoElement.style.aspectRatio = '16/9';

            // Insert video element at the beginning of participant element
            participantElement.insertBefore(videoElement, participantElement.firstChild);
        }

        return videoElement;
    }

    /**
     * Get or create audio element for participant
     * @param {string} participantId - Participant ID
     * @returns {HTMLAudioElement} Audio element
     */
    getOrCreateAudioElement(participantId) {
        let audioElement = document.getElementById(`audio-${participantId}`);

        if (!audioElement) {
            audioElement = document.createElement('audio');
            audioElement.id = `audio-${participantId}`;
            audioElement.autoplay = true;
            audioElement.style.display = 'none';

            // Append to document body
            document.body.appendChild(audioElement);
        }

        return audioElement;
    }

    /**
     * Update video display and camera overlay state
     * @param {string} participantId - Participant ID
     * @param {boolean} hasVideo - Whether participant has active video
     */
    updateVideoDisplay(participantId, hasVideo) {
        console.log(`📹 Updating video display for ${participantId}: ${hasVideo ? 'ON' : 'OFF'}`);

        const participantElement = document.getElementById(`participant-${participantId}`);
        if (!participantElement) {
            console.error(`❌ Participant element not found for ${participantId}`);
            return;
        }

        const videoElement = participantElement.querySelector('video');
        const placeholder = participantElement.querySelector('.absolute.inset-0.flex.flex-col');

        if (hasVideo && videoElement) {
            // Show video
            videoElement.style.opacity = '1';
            if (placeholder) {
                placeholder.style.opacity = '0';
            }
            this.hideCameraOffOverlay(participantId);
        } else {
            // Hide video, show placeholder and camera off overlay
            if (videoElement) {
                videoElement.style.opacity = '0';
            }
            if (placeholder) {
                placeholder.style.opacity = '1';
            }
            this.showCameraOffOverlay(participantId);
        }
    }

    /**
     * Show camera off overlay for participant
     * @param {string} participantId - Participant ID
     */
    showCameraOffOverlay(participantId) {
        const participantElement = document.getElementById(`participant-${participantId}`);
        if (!participantElement) return;

        // Update camera status indicator
        const cameraStatus = document.getElementById(`camera-status-${participantId}`);
        if (cameraStatus) {
            cameraStatus.className = 'w-6 h-6 bg-red-600 rounded-full flex items-center justify-center';
            cameraStatus.innerHTML = '<i class="fas fa-video-slash text-white text-xs"></i>';
        }

        // Add camera off overlay if it doesn't exist
        let overlay = participantElement.querySelector('.camera-off-overlay');
        if (!overlay) {
            overlay = document.createElement('div');
            overlay.className = 'camera-off-overlay absolute inset-0 bg-gray-800 bg-opacity-90 flex items-center justify-center z-10';
            overlay.innerHTML = `
                <div class="text-center">
                    <div class="w-12 h-12 bg-red-600 rounded-full flex items-center justify-center mb-2 mx-auto">
                        <i class="fas fa-video-slash text-white text-lg"></i>
                    </div>
                    <p class="text-white text-sm">الكاميرا مغلقة</p>
                </div>
            `;
            participantElement.appendChild(overlay);
        }

        overlay.style.display = 'flex';
    }

    /**
     * Hide camera off overlay for participant
     * @param {string} participantId - Participant ID
     */
    hideCameraOffOverlay(participantId) {
        const participantElement = document.getElementById(`participant-${participantId}`);
        if (!participantElement) return;

        // Update camera status indicator
        const cameraStatus = document.getElementById(`camera-status-${participantId}`);
        if (cameraStatus) {
            cameraStatus.className = 'w-6 h-6 bg-green-600 rounded-full flex items-center justify-center';
            cameraStatus.innerHTML = '<i class="fas fa-video text-white text-xs"></i>';
        }

        // Hide camera off overlay
        const overlay = participantElement.querySelector('.camera-off-overlay');
        if (overlay) {
            overlay.style.display = 'none';
        }
    }

    /**
     * Update participant track state based on publication
     * @param {string} participantId - Participant ID
     * @param {LiveKit.TrackPublication} publication - The track publication
     */
    updateParticipantTrackState(participantId, publication) {
        if (!this.trackStates.has(participantId)) {
            this.trackStates.set(participantId, {
                hasVideo: false,
                hasAudio: false,
                videoMuted: true,
                audioMuted: true
            });
        }

        const state = this.trackStates.get(participantId);

        if (publication.kind === 'video') {
            state.hasVideo = publication.track !== null;
            state.videoMuted = publication.isMuted;
        } else if (publication.kind === 'audio') {
            state.hasAudio = publication.track !== null;
            state.audioMuted = publication.isMuted;
        }

        console.log(`📊 Updated track state for ${participantId}:`, state);
    }

    /**
     * Get participant track state
     * @param {string} participantId - Participant ID
     * @returns {Object} Track state object
     */
    getParticipantTrackState(participantId) {
        return this.trackStates.get(participantId) || {
            hasVideo: false,
            hasAudio: false,
            videoMuted: true,
            audioMuted: true
        };
    }

    /**
     * Check if participant has active video (not muted and has track)
     * @param {string} participantId - Participant ID
     * @returns {boolean} Whether participant has active video
     */
    participantHasActiveVideo(participantId) {
        const state = this.getParticipantTrackState(participantId);
        return state.hasVideo && !state.videoMuted;
    }

    /**
     * Check if participant has active audio (not muted and has track)
     * @param {string} participantId - Participant ID
     * @returns {boolean} Whether participant has active audio
     */
    participantHasActiveAudio(participantId) {
        const state = this.getParticipantTrackState(participantId);
        return state.hasAudio && !state.audioMuted;
    }

    /**
     * Remove all tracks for a participant
     * @param {string} participantId - Participant ID
     */
    removeParticipantTracks(participantId) {
        console.log(`🧹 Removing tracks for participant ${participantId}`);

        // Remove track references
        if (this.participantTracks.has(participantId)) {
            const tracks = this.participantTracks.get(participantId);

            // Remove video element
            if (tracks.video && tracks.video.parentNode) {
                tracks.video.remove();
            }

            // Remove audio element
            if (tracks.audio && tracks.audio.parentNode) {
                tracks.audio.remove();
            }

            this.participantTracks.delete(participantId);
        }

        // Remove track state
        this.trackStates.delete(participantId);

        console.log(`✅ Tracks removed for ${participantId}`);
    }

    /**
     * Get all participant IDs with active video
     * @returns {string[]} Array of participant IDs with active video
     */
    getParticipantsWithActiveVideo() {
        const participantsWithVideo = [];

        for (const [participantId, state] of this.trackStates.entries()) {
            if (state.hasVideo && !state.videoMuted) {
                participantsWithVideo.push(participantId);
            }
        }

        return participantsWithVideo;
    }

    /**
     * Destroy tracks manager and clean up
     */
    destroy() {
        console.log('🧹 Destroying tracks manager...');

        // Remove all participant tracks
        for (const participantId of this.participantTracks.keys()) {
            this.removeParticipantTracks(participantId);
        }

        this.participantTracks.clear();
        this.trackStates.clear();

        console.log('✅ Tracks manager destroyed');
    }
}
