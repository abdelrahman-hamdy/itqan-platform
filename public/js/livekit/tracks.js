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

        // Retry mechanism tracking
        this.lastTrack = null;
        this.lastPublication = null;
        this.lastParticipant = null;

        console.log('🎬 LiveKitTracks initialized');
    }

    /**
     * Handle track subscribed event
     * @param {LiveKit.Track} track - The subscribed track
     * @param {LiveKit.TrackPublication} publication - The track publication
     * @param {LiveKit.Participant} participant - The participant
     */
    handleTrackSubscribed(track, publication, participant) {
        console.log(`📹 Track subscribed: ${track.kind} from ${participant.identity}, source: ${publication.source}`);

        const participantId = participant.identity;
        const isLocal = participant.isLocal;

        // Store for retry mechanism
        this.lastTrack = track;
        this.lastPublication = publication;
        this.lastParticipant = participant;

        // Initialize participant state if not exists
        if (!this.trackStates.has(participantId)) {
            this.trackStates.set(participantId, {
                hasVideo: false,
                hasAudio: false,
                videoMuted: true,
                audioMuted: true,
                hasScreenShare: false,
                screenShareMuted: true
            });
        }

        // Handle screen share tracks differently
        if (publication.source === window.LiveKit.Track.Source.ScreenShare) {
            this.handleScreenShareTrackSubscribed(track, publication, participant);
        } else if (publication.source === window.LiveKit.Track.Source.ScreenShareAudio) {
            this.handleScreenShareAudioTrackSubscribed(track, publication, participant);
        } else if (track.kind === 'video') {
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

        console.log(`📹 Video track subscribed for ${participantId} (local: ${isLocal}, muted: ${publication.isMuted})`);

        // Get or create video element
        const videoElement = this.getOrCreateVideoElement(participantId, isLocal);

        if (!videoElement) {
            console.error(`❌ Could not create video element for ${participantId}`);
            return;
        }

        // Attach track to video element
        track.attach(videoElement);
        console.log(`📹 Track attached to video element for ${participantId}`);

        // For debugging: log video element properties
        console.log(`📹 Video element state: width=${videoElement.videoWidth}, height=${videoElement.videoHeight}, ready=${videoElement.readyState}`);

        // Ensure video element is properly configured
        videoElement.onloadedmetadata = () => {
            console.log(`📹 Video metadata loaded for ${participantId}: ${videoElement.videoWidth}x${videoElement.videoHeight}`);
            // Force update display once metadata is loaded
            this.updateVideoDisplay(participantId, !publication.isMuted);
        };

        // Add error handling for video track
        videoElement.onerror = (error) => {
            console.error(`❌ Video error for ${participantId}:`, error);
        };

        // Add event when video is ready to play
        videoElement.oncanplay = () => {
            console.log(`🎬 Video can play for ${participantId}`);
            this.updateVideoDisplay(participantId, !publication.isMuted);
        };

        // Update video visibility and overlay state immediately
        const shouldShowVideo = !publication.isMuted;
        console.log(`📹 Should show video for ${participantId}: ${shouldShowVideo}`);
        this.updateVideoDisplay(participantId, shouldShowVideo);

        // Extra verification for track attachment
        setTimeout(() => {
            if (videoElement.srcObject) {
                console.log(`✅ Video srcObject confirmed for ${participantId}`);
            } else {
                console.warn(`⚠️ Video srcObject missing for ${participantId}, re-attaching track...`);
                track.attach(videoElement);
            }
        }, 1000);

        // Store track reference
        if (!this.participantTracks.has(participantId)) {
            this.participantTracks.set(participantId, {});
        }
        this.participantTracks.get(participantId).video = videoElement;

        // Notify callback
        if (this.config.onVideoTrackAttached) {
            this.config.onVideoTrackAttached(participantId, videoElement, track, publication);
        }

        console.log(`✅ Video track attached for ${participantId} - should show: ${shouldShowVideo}`);
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

        console.log(`🎤 Audio track subscribed for ${participantId} (local: ${isLocal}, muted: ${publication.isMuted})`);

        // Update microphone status icon
        this.updateMicrophoneStatusIcon(participantId, !publication.isMuted);

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
     * Handle screen share track subscribed
     * @param {LiveKit.VideoTrack} track - The screen share video track
     * @param {LiveKit.TrackPublication} publication - The track publication
     * @param {LiveKit.Participant} participant - The participant
     */
    handleScreenShareTrackSubscribed(track, publication, participant) {
        const participantId = participant.identity;
        const isLocal = participant.isLocal;

        console.log(`🖥️ Screen share track subscribed for ${participantId} (local: ${isLocal})`);

        // Create a screen share "participant" element
        this.createScreenShareElement(track, publication, participant);

        // Notify callback
        if (this.config.onVideoTrackAttached) {
            this.config.onVideoTrackAttached(`${participantId}_screen`, null, track, publication);
        }

        console.log(`✅ Screen share track handled for ${participantId}`);
    }

    /**
     * Handle screen share audio track subscribed
     * @param {LiveKit.AudioTrack} track - The screen share audio track
     * @param {LiveKit.TrackPublication} publication - The track publication
     * @param {LiveKit.Participant} participant - The participant
     */
    handleScreenShareAudioTrackSubscribed(track, publication, participant) {
        const participantId = participant.identity;
        const isLocal = participant.isLocal;

        console.log(`🔊 Screen share audio track subscribed for ${participantId} (local: ${isLocal})`);

        // For screen share audio, we'll attach it to a hidden audio element
        if (!isLocal) {
            const audioElement = this.getOrCreateAudioElement(`${participantId}_screen_audio`);
            track.attach(audioElement);
        }

        console.log(`✅ Screen share audio track handled for ${participantId}`);
    }

    /**
     * Create screen share display element
     * @param {LiveKit.VideoTrack} track - The screen share video track
     * @param {LiveKit.TrackPublication} publication - The track publication
     * @param {LiveKit.Participant} participant - The participant
     */
    createScreenShareElement(track, publication, participant) {
        const participantId = participant.identity;
        const screenShareId = `${participantId}_screen`;
        const isLocal = participant.isLocal;

        console.log(`🖥️ Creating screen share element for ${participantId}`);

        const videoGrid = document.getElementById('videoGrid');
        if (!videoGrid) {
            console.error('❌ Video grid not found');
            console.error('❌ Available elements with "video" in ID:', Array.from(document.querySelectorAll('[id*="video"]')).map(el => el.id));
            return;
        }

        // Check if screen share element already exists
        let screenShareDiv = document.getElementById(`participant-${screenShareId}`);
        if (screenShareDiv) {
            console.log(`🖥️ Screen share element already exists for ${participantId}`);
            return;
        }

        // Create screen share container
        screenShareDiv = document.createElement('div');
        screenShareDiv.id = `participant-${screenShareId}`;
        screenShareDiv.className = 'participant-video screen-share relative bg-gray-900 rounded-lg overflow-hidden aspect-video w-full h-full group border-2 border-blue-500 cursor-pointer hover:border-blue-400 transition-all duration-200 select-none';
        screenShareDiv.style.pointerEvents = 'auto';
        screenShareDiv.dataset.participantId = screenShareId;
        screenShareDiv.dataset.isScreenShare = 'true';
        screenShareDiv.dataset.isLocal = isLocal;
        screenShareDiv.dataset.originalParticipantId = participantId; // Store original participant ID for focus mode

        // Create video element for screen share
        const videoElement = document.createElement('video');
        videoElement.id = `video-${screenShareId}`;
        videoElement.className = 'absolute inset-0 w-full h-full object-contain opacity-1 transition-opacity duration-300 z-10 cursor-pointer';
        videoElement.autoplay = true;
        videoElement.playsInline = true;
        videoElement.muted = isLocal; // Mute local screen share to avoid audio feedback
        videoElement.style.pointerEvents = 'auto';

        // Attach track to video element
        track.attach(videoElement);

        // Create screen share overlay with info
        const overlay = document.createElement('div');
        overlay.className = 'absolute inset-0 bg-gradient-to-br from-blue-900/20 to-gray-800/20 flex flex-col justify-between p-4 pointer-events-none z-20';

        // Add focus indicator overlay
        const focusIndicator = document.createElement('div');
        focusIndicator.className = 'absolute inset-0 bg-blue-500 bg-opacity-0 hover:bg-opacity-10 transition-all duration-200 pointer-events-none flex items-center justify-center z-30';
        focusIndicator.innerHTML = `
            <div class="bg-black bg-opacity-50 text-white px-4 py-2 rounded-lg opacity-0 hover:opacity-100 transition-opacity duration-200">
                <i class="fas fa-expand-arrows-alt mr-2"></i>
                انقر للتكبير
            </div>
        `;

        // Screen share title
        const title = document.createElement('div');
        title.className = 'bg-blue-600 text-white px-3 py-1 rounded-full text-sm font-medium self-start shadow-lg screen-share-title';
        title.innerHTML = `
            <i class="fas fa-desktop mr-2"></i>
            ${isLocal ? 'شاشتك المشتركة' : `شاشة ${this.getParticipantDisplayName(participant)}`}
            <span class="ml-2 text-xs opacity-75">(انقر للتكبير)</span>
        `;

        // Screen share controls (for local only)
        const controls = document.createElement('div');
        controls.className = 'flex justify-end space-x-2 space-x-reverse';

        if (isLocal) {
            const stopButton = document.createElement('button');
            stopButton.className = 'bg-red-600 hover:bg-red-700 text-white p-2 rounded-full transition-colors pointer-events-auto';
            stopButton.innerHTML = '<i class="fas fa-stop"></i>';
            stopButton.title = 'إيقاف مشاركة الشاشة';
            stopButton.onclick = () => {
                // Trigger screen share toggle through controls
                if (window.livekitControls) {
                    window.livekitControls.toggleScreenShare();
                }
            };
            controls.appendChild(stopButton);
        }

        // Focus button removed - users can click anywhere on the screen share to focus

        overlay.appendChild(title);
        overlay.appendChild(controls);

        // Add elements to container
        screenShareDiv.appendChild(videoElement);
        screenShareDiv.appendChild(overlay);
        screenShareDiv.appendChild(focusIndicator);

        // Add click event listener for focus mode
        screenShareDiv.addEventListener('click', (e) => {
            e.preventDefault();
            e.stopPropagation();

            console.log(`🎯 Screen share clicked for focus mode: ${participantId}`);
            console.log(`🎯 Screen share ID: ${screenShareId}`);
            console.log(`🎯 Window livekitLayout exists: ${!!window.livekitLayout}`);
            console.log(`🎯 Click target:`, e.target);
            console.log(`🎯 Current target:`, e.currentTarget);

            // Trigger focus mode through layout system
            if (window.livekitLayout) {
                console.log(`🎯 Calling applyFocusMode with: ${screenShareId}`);
                window.livekitLayout.applyFocusMode(screenShareId, screenShareDiv);
            } else {
                console.error('❌ window.livekitLayout not found!');
            }
        });

        // Also add click listener to the video element itself
        videoElement.addEventListener('click', (e) => {
            e.preventDefault();
            e.stopPropagation();

            console.log(`🎯 Screen share video clicked for focus mode: ${participantId}`);

            if (window.livekitLayout) {
                window.livekitLayout.applyFocusMode(screenShareId, screenShareDiv);
            }
        });

        // Insert screen share at the beginning of video grid for prominence
        videoGrid.insertBefore(screenShareDiv, videoGrid.firstChild);

        // Add a small delay and then test if the element is clickable
        setTimeout(() => {
            console.log(`🖥️ Screen share element created and inserted:`, {
                id: screenShareDiv.id,
                exists: !!document.getElementById(screenShareDiv.id),
                clickable: screenShareDiv.style.pointerEvents === 'auto',
                hasClickListeners: screenShareDiv.onclick !== null
            });
        }, 100);

        // Store element reference with screen share suffix
        if (!this.participantTracks.has(screenShareId)) {
            this.participantTracks.set(screenShareId, {});
        }
        this.participantTracks.get(screenShareId).video = videoElement;

        console.log(`✅ Screen share element created for ${participantId}`);
        console.log(`📊 Video grid now has ${videoGrid.children.length} participants (including screen share)`);
    }

    /**
     * Get participant display name helper
     * @param {LiveKit.Participant} participant - Participant object
     * @returns {string} Display name
     */
    getParticipantDisplayName(participant) {
        // Try to get name from metadata first
        if (participant.metadata) {
            try {
                const metadata = JSON.parse(participant.metadata);
                if (metadata.name) {
                    return metadata.name;
                }
            } catch (e) {
                // Ignore JSON parse errors
            }
        }

        // Fallback to identity
        return participant.identity || 'مشارك';
    }

    /**
     * Remove screen share element
     * @param {string} participantId - Participant ID
     */
    removeScreenShareElement(participantId) {
        const screenShareId = `${participantId}_screen`;
        console.log(`🗑️ Removing screen share element for ${participantId}`);

        const element = document.getElementById(`participant-${screenShareId}`);
        if (element && element.parentNode) {
            element.remove();
        }

        // Remove track references
        this.participantTracks.delete(screenShareId);

        // Remove audio element if exists
        const audioElement = document.getElementById(`audio-${participantId}_screen_audio`);
        if (audioElement && audioElement.parentNode) {
            audioElement.remove();
        }

        console.log(`✅ Screen share element removed for ${participantId}`);
    }

    /**
     * Handle track unsubscribed event
     * @param {LiveKit.Track} track - The unsubscribed track
     * @param {LiveKit.TrackPublication} publication - The track publication
     * @param {LiveKit.Participant} participant - The participant
     */
    handleTrackUnsubscribed(track, publication, participant) {
        console.log(`📹 Track unsubscribed: ${track.kind} from ${participant.identity}, source: ${publication.source}`);

        const participantId = participant.identity;

        // Handle screen share tracks differently
        if (publication.source === window.LiveKit.Track.Source.ScreenShare) {
            this.handleScreenShareTrackUnsubscribed(track, publication, participant);
        } else if (publication.source === window.LiveKit.Track.Source.ScreenShareAudio) {
            this.handleScreenShareAudioTrackUnsubscribed(track, publication, participant);
        } else if (track.kind === 'video') {
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

        // Update microphone status icon to show as muted
        this.updateMicrophoneStatusIcon(participantId, false);

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
     * Handle screen share track unsubscribed
     * @param {LiveKit.VideoTrack} track - The screen share video track
     * @param {LiveKit.TrackPublication} publication - The track publication
     * @param {LiveKit.Participant} participant - The participant
     */
    handleScreenShareTrackUnsubscribed(track, publication, participant) {
        const participantId = participant.identity;

        console.log(`🖥️ Screen share track unsubscribed for ${participantId}`);

        // Detach track
        track.detach();

        // Remove screen share element
        this.removeScreenShareElement(participantId);

        // Notify callback
        if (this.config.onVideoTrackDetached) {
            this.config.onVideoTrackDetached(`${participantId}_screen`, track, publication);
        }

        console.log(`✅ Screen share track detached for ${participantId}`);
    }

    /**
     * Handle screen share audio track unsubscribed
     * @param {LiveKit.AudioTrack} track - The screen share audio track
     * @param {LiveKit.TrackPublication} publication - The track publication
     * @param {LiveKit.Participant} participant - The participant
     */
    handleScreenShareAudioTrackUnsubscribed(track, publication, participant) {
        const participantId = participant.identity;

        console.log(`🔊 Screen share audio track unsubscribed for ${participantId}`);

        // Detach track
        track.detach();

        // Remove audio element
        const audioElement = document.getElementById(`audio-${participantId}_screen_audio`);
        if (audioElement && audioElement.parentNode) {
            audioElement.remove();
        }

        // Notify callback
        if (this.config.onAudioTrackDetached) {
            this.config.onAudioTrackDetached(`${participantId}_screen_audio`, track, publication);
        }

        console.log(`✅ Screen share audio track detached for ${participantId}`);
    }

    /**
     * Handle track muted event
     * @param {LiveKit.TrackPublication} publication - The track publication
     * @param {LiveKit.Participant} participant - The participant
     */
    handleTrackMuted(publication, participant) {
        const participantId = participant.identity;

        console.log(`🔇 Track muted: ${publication.kind} from ${participantId}, source: ${publication.source}`);

        if (publication.source === window.LiveKit.Track.Source.ScreenShare) {
            // Handle screen share muted (hide screen share)
            this.updateScreenShareDisplay(participantId, false);
        } else if (publication.kind === 'video') {
            // Hide video and update camera status icon
            this.updateVideoDisplay(participantId, false);

            // Notify camera state change
            if (this.config.onCameraStateChanged) {
                this.config.onCameraStateChanged(participantId, false);
            }
        } else if (publication.kind === 'audio') {
            // Update microphone status icon
            this.updateMicrophoneStatusIcon(participantId, false);

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

        console.log(`🔊 Track unmuted: ${publication.kind} from ${participantId}, source: ${publication.source}`);

        if (publication.source === window.LiveKit.Track.Source.ScreenShare) {
            // Handle screen share unmuted (show screen share)
            this.updateScreenShareDisplay(participantId, true);
        } else if (publication.kind === 'video') {
            // Show video and update camera status icon
            this.updateVideoDisplay(participantId, true);

            // Notify camera state change
            if (this.config.onCameraStateChanged) {
                this.config.onCameraStateChanged(participantId, true);
            }
        } else if (publication.kind === 'audio') {
            // Update microphone status icon
            this.updateMicrophoneStatusIcon(participantId, true);

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
        console.log(`📹 Getting/Creating video element for ${participantId}, isLocal: ${isLocal}`);

        // Try to find participant element
        let participantElement = document.getElementById(`participant-${participantId}`);

        if (!participantElement) {
            // Alternative search methods
            console.log(`🔍 Primary search failed for participant-${participantId}, trying alternatives...`);
            participantElement = document.querySelector(`[data-participant-id="${participantId}"]`);

            if (!participantElement) {
                console.error(`❌ Participant element not found for ${participantId}`);
                // Log available participant elements for debugging
                const allParticipants = document.querySelectorAll('[id^="participant-"], [data-participant-id]');
                console.log('📋 Available participant elements:', Array.from(allParticipants).map(el => el.id || el.getAttribute('data-participant-id')));

                // Wait a bit and try again (race condition fix)
                setTimeout(() => {
                    console.log(`🔄 Retrying video element creation for ${participantId}...`);
                    const retryElement = document.getElementById(`participant-${participantId}`);
                    if (retryElement && this.lastTrack && this.lastPublication && this.lastParticipant) {
                        console.log(`✅ Participant element found on retry for ${participantId}`);
                        this.handleTrackSubscribed(this.lastTrack, this.lastPublication, this.lastParticipant);
                    }
                }, 500);

                return null;
            }
        }

        console.log(`✅ Found participant element for ${participantId}`);

        // Look for existing video element
        let videoElement = participantElement.querySelector('video');
        let existingVideoId = videoElement ? videoElement.id : 'none';
        console.log(`📹 Existing video element check: ${existingVideoId}`);

        if (!videoElement) {
            console.log(`📹 Creating new video element for ${participantId}`);

            videoElement = document.createElement('video');
            videoElement.id = `video-${participantId}`;
            videoElement.className = 'absolute inset-0 w-full h-full object-cover opacity-1 transition-opacity duration-300 z-10';
            videoElement.autoplay = true;
            videoElement.playsInline = true;
            videoElement.muted = isLocal; // Mute local video to avoid feedback
            videoElement.style.aspectRatio = '16/9';
            videoElement.style.display = 'block';

            // Insert video element at the beginning of participant element
            participantElement.insertBefore(videoElement, participantElement.firstChild);

            console.log(`📹 ✅ Video element created and inserted for ${participantId}`);
            console.log(`📹 📊 Participant element children:`, participantElement.children.length);
        } else {
            console.log(`📹 Found existing video element for ${participantId}: ${videoElement.id}`);
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
 * Update video display and camera status icon
 * @param {string} participantId - Participant ID
 * @param {boolean} hasVideo - Whether participant has active video
 */
    updateVideoDisplay(participantId, hasVideo) {
        console.log(`📹 Updating video display for ${participantId}: ${hasVideo ? 'ON' : 'OFF'}`);

        const participantElement = document.getElementById(`participant-${participantId}`);
        if (!participantElement) {
            console.log(`⏳ Participant element not found for ${participantId}, will retry later`);
            return;
        }

        const videoElement = participantElement.querySelector('video');
        const placeholder = participantElement.querySelector('.absolute.inset-0.flex.flex-col');

        if (hasVideo) {
            // Show video
            console.log(`📹 Showing video for ${participantId}`);

            if (videoElement) {
                videoElement.style.opacity = '1';
                videoElement.style.display = 'block';
                videoElement.style.visibility = 'visible';
            }

            if (placeholder) {
                placeholder.style.opacity = '0.1'; // Keep slightly visible for labels
            }

            console.log(`✅ Video shown for ${participantId}`);
        } else {
            // Hide video
            console.log(`📹 Hiding video for ${participantId}`);

            if (videoElement) {
                videoElement.style.opacity = '0';
                videoElement.style.display = 'none';
                videoElement.style.visibility = 'hidden';
            }

            if (placeholder) {
                placeholder.style.opacity = '1';
            }

            console.log(`✅ Video hidden for ${participantId}`);
        }

        // Update camera status icon
        this.updateCameraStatusIcon(participantId, hasVideo);
    }

    /**
     * Update camera status icon
     * @param {string} participantId - Participant ID
     * @param {boolean} hasVideo - Whether participant has active video
     */
    updateCameraStatusIcon(participantId, hasVideo) {
        const cameraStatus = document.getElementById(`camera-status-${participantId}`);
        if (cameraStatus) {
            const icon = cameraStatus.querySelector('i');
            if (hasVideo) {
                cameraStatus.className = 'text-green-500';
                if (icon) icon.className = 'fas fa-video text-sm';
            } else {
                cameraStatus.className = 'text-red-500';
                if (icon) icon.className = 'fas fa-video-slash text-sm';
            }
            console.log(`📹 Updated camera status icon for ${participantId}: ${hasVideo ? 'green' : 'red'}`);
        }
    }

    /**
     * Update microphone status icon
     * @param {string} participantId - Participant ID
     * @param {boolean} hasAudio - Whether participant has active audio
     */
    updateMicrophoneStatusIcon(participantId, hasAudio) {
        const micStatus = document.getElementById(`mic-status-${participantId}`);
        if (micStatus) {
            const icon = micStatus.querySelector('i');
            if (hasAudio) {
                micStatus.className = 'text-green-500';
                if (icon) icon.className = 'fas fa-microphone text-sm';
            } else {
                micStatus.className = 'text-red-500';
                if (icon) icon.className = 'fas fa-microphone-slash text-sm';
            }
            console.log(`🎤 Updated microphone status icon for ${participantId}: ${hasAudio ? 'green' : 'red'}`);
        }
    }

    /**
     * Update screen share display visibility
     * @param {string} participantId - Participant ID
     * @param {boolean} isVisible - Whether screen share should be visible
     */
    updateScreenShareDisplay(participantId, isVisible) {
        console.log(`🖥️ Updating screen share display for ${participantId}: ${isVisible ? 'VISIBLE' : 'HIDDEN'}`);

        const screenShareId = `${participantId}_screen`;
        const screenShareElement = document.getElementById(`participant-${screenShareId}`);

        if (!screenShareElement) {
            console.log(`⏳ Screen share element not found for ${participantId}`);
            return;
        }

        const videoElement = screenShareElement.querySelector('video');
        const overlay = screenShareElement.querySelector('.absolute.inset-0.bg-gradient-to-br');

        if (isVisible) {
            // Show screen share
            console.log(`🖥️ Showing screen share for ${participantId}`);

            if (videoElement) {
                videoElement.style.opacity = '1';
                videoElement.style.display = 'block';
                videoElement.style.visibility = 'visible';
            }

            if (overlay) {
                overlay.style.opacity = '0.2'; // Keep overlay slightly visible for title
            }

            screenShareElement.style.opacity = '1';

            console.log(`✅ Screen share shown for ${participantId}`);
        } else {
            // Hide screen share or show paused state
            console.log(`🖥️ Hiding screen share for ${participantId}`);

            if (videoElement) {
                videoElement.style.opacity = '0.3';
            }

            if (overlay) {
                overlay.style.opacity = '0.8'; // Show overlay more prominently when paused

                // Add paused indicator if not already present
                let pausedIndicator = overlay.querySelector('.paused-indicator');
                if (!pausedIndicator) {
                    pausedIndicator = document.createElement('div');
                    pausedIndicator.className = 'paused-indicator absolute inset-0 flex items-center justify-center bg-black bg-opacity-50';
                    pausedIndicator.innerHTML = `
                        <div class="text-center text-white">
                            <i class="fas fa-pause text-4xl mb-2"></i>
                            <p class="text-lg font-medium">تم إيقاف مشاركة الشاشة مؤقتاً</p>
                        </div>
                    `;
                    overlay.appendChild(pausedIndicator);
                }
            }

            screenShareElement.style.opacity = '0.7';

            console.log(`✅ Screen share hidden for ${participantId}`);
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
                audioMuted: true,
                hasScreenShare: false,
                screenShareMuted: true
            });
        }

        const state = this.trackStates.get(participantId);

        if (publication.source === window.LiveKit.Track.Source.ScreenShare) {
            state.hasScreenShare = publication.track !== null;
            state.screenShareMuted = publication.isMuted;
        } else if (publication.kind === 'video') {
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
            audioMuted: true,
            hasScreenShare: false,
            screenShareMuted: true
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
     * Check if participant has active screen share (not muted and has track)
     * @param {string} participantId - Participant ID
     * @returns {boolean} Whether participant has active screen share
     */
    participantHasActiveScreenShare(participantId) {
        const state = this.getParticipantTrackState(participantId);
        return state.hasScreenShare && !state.screenShareMuted;
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

        // Remove screen share element if it exists
        this.removeScreenShareElement(participantId);

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
