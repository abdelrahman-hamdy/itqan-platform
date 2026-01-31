/**
 * LiveKit Tracks Module - ENHANCED WITH SYNCHRONIZATION FIXES
 * Handles track subscription, attachment/detachment, and media element management
 * CRITICAL FIXES: Proper state synchronization, race condition elimination, robust track handling
 */

/**
 * Track manager for LiveKit media tracks
 */
class LiveKitTracks {
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
        this.trackStates = new Map(); // participantId -> comprehensive state object

        // Retry mechanism tracking
        this.lastTrack = null;
        this.lastPublication = null;
        this.lastParticipant = null;

        // CRITICAL FIX: Enhanced state synchronization
        this.processingQueue = new Set(); // Track which participants are being processed
        this.syncTimeouts = new Map(); // participantId -> timeout ID
        this.lastStateUpdate = new Map(); // participantId -> timestamp

    }

    /**
     * CRITICAL FIX: Initialize participant state with actual track publication data
     */
    initializeParticipantState(participantId, isLocal) {
        if (!this.trackStates.has(participantId)) {
            // CRITICAL FIX: Don't assume states - read from actual participant
            const defaultState = {
                hasVideo: false,
                hasAudio: false,
                videoMuted: true,
                audioMuted: true,
                hasScreenShare: false,
                screenShareMuted: true,
                isLocal: isLocal,
                lastUpdate: Date.now(),
                uiSynced: false
            };
            
            this.trackStates.set(participantId, defaultState);
        }
    }

    /**
     * CRITICAL FIX: Update track state based on actual publication data
     */
    updateTrackState(participantId, publication, track) {
        const state = this.trackStates.get(participantId);
        if (!state) return;

        const hasTrack = track !== null && track !== undefined;
        const isMuted = publication.isMuted;
        
        if (publication.source === window.LiveKit.Track.Source.ScreenShare) {
            state.hasScreenShare = hasTrack;
            state.screenShareMuted = isMuted;
        } else if (publication.kind === 'video') {
            state.hasVideo = hasTrack;
            state.videoMuted = isMuted;
        } else if (publication.kind === 'audio') {
            state.hasAudio = hasTrack;
            state.audioMuted = isMuted;
        }

        state.lastUpdate = Date.now();
        state.uiSynced = false; // Mark as needing UI sync
        
    }

    /**
     * CRITICAL FIX: Force UI synchronization
     */
    forceUISync(participantId) {
        // Cancel any pending sync
        if (this.syncTimeouts.has(participantId)) {
            clearTimeout(this.syncTimeouts.get(participantId));
        }

        // Schedule immediate sync
        const timeoutId = setTimeout(() => {
            this.performUISync(participantId);
            this.syncTimeouts.delete(participantId);
        }, 100);
        
        this.syncTimeouts.set(participantId, timeoutId);
    }

    /**
     * CRITICAL FIX: Perform UI synchronization based on actual state
     */
    performUISync(participantId) {
        
        const state = this.trackStates.get(participantId);
        if (!state) {
            return;
        }

        // Sync video display
        const shouldShowVideo = state.hasVideo && !state.videoMuted;
        this.updateVideoDisplay(participantId, shouldShowVideo);

        // Sync audio indicators
        const shouldShowAudio = state.hasAudio && !state.audioMuted;
        this.updateMicrophoneStatusIcon(participantId, shouldShowAudio);
        this.updateOverlayMicStatus(participantId, shouldShowAudio);

        state.uiSynced = true;
    }

    /**
     * CRITICAL FIX: Wait for video to be ready before showing
     */
    async waitForVideoReady(videoElement, participantId, timeout = 3000) {
        
        return new Promise((resolve) => {
            const checkReady = () => {
                if (videoElement.srcObject && 
                    videoElement.srcObject.getTracks().length > 0 &&
                    videoElement.readyState >= 2) {
                    resolve(true);
                    return;
                }
            };

            // Check immediately
            checkReady();

            // Set up event listeners for video ready states
            const onLoadedData = () => {
                checkReady();
            };

            const onCanPlay = () => {
                checkReady();
            };

            videoElement.addEventListener('loadeddata', onLoadedData, { once: true });
            videoElement.addEventListener('canplay', onCanPlay, { once: true });

            // Timeout fallback
            setTimeout(() => {
                videoElement.removeEventListener('loadeddata', onLoadedData);
                videoElement.removeEventListener('canplay', onCanPlay);
                resolve(false);
            }, timeout);
        });
    }

    /**
     * Handle track subscribed event - ENHANCED WITH SYNCHRONIZATION FIXES
     * @param {LiveKit.Track} track - The subscribed track
     * @param {LiveKit.TrackPublication} publication - The track publication
     * @param {LiveKit.Participant} participant - The participant
     */
    async handleTrackSubscribed(track, publication, participant) {
        const participantId = participant.identity;
        const isLocal = participant.isLocal;
        

        // CRITICAL FIX: Prevent duplicate processing
        const processingKey = `${participantId}-${track.kind}-${publication.source}`;
        if (this.processingQueue.has(processingKey)) {
            return;
        }
        
        this.processingQueue.add(processingKey);

        try {
            // Store for retry mechanism
            this.lastTrack = track;
            this.lastPublication = publication;
            this.lastParticipant = participant;

            // CRITICAL FIX: Initialize participant state with REAL track data
            this.initializeParticipantState(participantId, isLocal);
            
            // Update state based on actual publication
            this.updateTrackState(participantId, publication, track);

            // Handle screen share tracks differently
            if (publication.source === window.LiveKit.Track.Source.ScreenShare) {
                await this.handleScreenShareTrackSubscribed(track, publication, participant);
            } else if (publication.source === window.LiveKit.Track.Source.ScreenShareAudio) {
                await this.handleScreenShareAudioTrackSubscribed(track, publication, participant);
            } else if (track.kind === 'video') {
                await this.handleVideoTrackSubscribed(track, publication, participant);
            } else if (track.kind === 'audio') {
                await this.handleAudioTrackSubscribed(track, publication, participant);
            }

            // CRITICAL FIX: Force UI sync after track processing
            this.forceUISync(participantId);
            
        } finally {
            this.processingQueue.delete(processingKey);
        }
    }

    /**
     * Handle video track subscribed - ENHANCED WITH ROBUST ERROR HANDLING
     * @param {LiveKit.VideoTrack} track - The video track
     * @param {LiveKit.TrackPublication} publication - The track publication
     * @param {LiveKit.Participant} participant - The participant
     */
    async handleVideoTrackSubscribed(track, publication, participant) {
        const participantId = participant.identity;
        const isLocal = participant.isLocal;


        try {
            // CRITICAL FIX: Get video element with retry mechanism
            const videoElement = await this.getVideoElementWithRetry(participantId, isLocal);
            if (!videoElement) {
                return;
            }

            // Attach track with error handling
            try {
                track.attach(videoElement);
            } catch (attachError) {
                return;
            }

            // Store track reference
            this.storeTrackReference(participantId, 'video', videoElement);

            // CRITICAL FIX: Update video display based on ACTUAL track state
            const shouldShowVideo = !publication.isMuted && track !== null;
            
            // Wait for video to be ready before updating display
            await this.waitForVideoReady(videoElement, participantId);
            
            // Update display with actual state
            this.updateVideoDisplay(participantId, shouldShowVideo);
            
            // Callback notification
            if (this.config.onVideoTrackAttached) {
                this.config.onVideoTrackAttached(participantId, videoElement, track, publication);
            }


        } catch (error) {
        }
    }

    /**
     * CRITICAL FIX: Get video element with retry mechanism
     */
    async getVideoElementWithRetry(participantId, isLocal, maxRetries = 3) {
        for (let attempt = 1; attempt <= maxRetries; attempt++) {
            
            const videoElement = this.getOrCreateVideoElement(participantId, isLocal);
            if (videoElement) {
                return videoElement;
            }
            
            if (attempt < maxRetries) {
                await new Promise(resolve => setTimeout(resolve, 500 * attempt));
            }
        }
        
        return null;
    }

    /**
     * Store track reference safely
     */
    storeTrackReference(participantId, trackType, element) {
        if (!this.participantTracks.has(participantId)) {
            this.participantTracks.set(participantId, {});
        }
        this.participantTracks.get(participantId)[trackType] = element;
    }

    /**
     * Handle audio track subscribed - ENHANCED WITH IMMEDIATE UI UPDATE
     * @param {LiveKit.AudioTrack} track - The audio track
     * @param {LiveKit.TrackPublication} publication - The track publication
     * @param {LiveKit.Participant} participant - The participant
     */
    async handleAudioTrackSubscribed(track, publication, participant) {
        const participantId = participant.identity;
        const isLocal = participant.isLocal;


        try {
            // CRITICAL FIX: Update UI immediately with actual state
            const hasActiveAudio = !publication.isMuted && track !== null;
            this.updateMicrophoneStatusIcon(participantId, hasActiveAudio);
            this.updateOverlayMicStatus(participantId, hasActiveAudio);

            // For non-local participants, attach audio
            if (!isLocal && track) {
                const audioElement = this.getOrCreateAudioElement(participantId);
                
                try {
                    track.attach(audioElement);
                    this.storeTrackReference(participantId, 'audio', audioElement);
                } catch (attachError) {
                }
            }

            // Callback notification
            if (this.config.onAudioTrackAttached) {
                this.config.onAudioTrackAttached(participantId, null, track, publication);
            }


        } catch (error) {
        }
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


        // Create a screen share "participant" element
        this.createScreenShareElement(track, publication, participant);

        // Notify callback
        if (this.config.onVideoTrackAttached) {
            this.config.onVideoTrackAttached(`${participantId}_screen`, null, track, publication);
        }

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


        // For screen share audio, we'll attach it to a hidden audio element
        if (!isLocal) {
            const audioElement = this.getOrCreateAudioElement(`${participantId}_screen_audio`);
            track.attach(audioElement);
        }

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


        const videoGrid = document.getElementById('videoGrid');
        if (!videoGrid) {
            return;
        }

        // Check if screen share element already exists
        let screenShareDiv = document.getElementById(`participant-${screenShareId}`);
        if (screenShareDiv) {
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
        videoElement.className = 'absolute inset-0 w-full h-full object-contain opacity-100 transition-opacity duration-300 z-10 cursor-pointer';
        videoElement.autoplay = true;
        videoElement.playsInline = true;
        videoElement.muted = isLocal; // Mute local screen share to avoid audio feedback
        videoElement.style.pointerEvents = 'auto';

        // Attach track to video element
        track.attach(videoElement);


        // Create screen share overlay with info
        const overlay = document.createElement('div');
        overlay.className = 'absolute inset-0 bg-gradient-to-br from-blue-900/20 to-gray-800/20 flex flex-col justify-between p-4 pointer-events-none z-20';

        // Add focus indicator overlay (without background to not cover the video)
        const focusIndicator = document.createElement('div');
        focusIndicator.className = 'absolute inset-0 transition-all duration-200 pointer-events-none flex items-center justify-center z-30';
        focusIndicator.innerHTML = `
            <div class="bg-black bg-opacity-50 text-white px-4 py-2 rounded-lg opacity-0 hover:opacity-100 transition-opacity duration-200">
                <i class="ri-fullscreen-line mr-2"></i>
                ${t('screen_share.click_to_enlarge')}
            </div>
        `;

        // Screen share title
        const title = document.createElement('div');
        title.className = 'bg-blue-600 text-white px-3 py-1 rounded-full text-sm font-medium self-start shadow-lg screen-share-title';
        title.innerHTML = `
            <i class="ri-computer-line mr-2"></i>
            ${isLocal ? t('screen_share.your_screen') : `${t('screen_share.screen_of')} ${this.getParticipantDisplayName(participant)}`}
            <span class="ml-2 text-xs opacity-75">(${t('screen_share.click_to_enlarge')})</span>
        `;

        // Screen share controls (for local only)
        const controls = document.createElement('div');
        controls.className = 'flex justify-end gap-2';

        if (isLocal) {
            const stopButton = document.createElement('button');
            stopButton.className = 'bg-red-600 hover:bg-red-700 text-white p-2 rounded-full transition-colors pointer-events-auto';
            stopButton.innerHTML = '<i class="ri-stop-circle-line text-lg"></i>';
            stopButton.title = t('controls.stop_screen_share');
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


            // Trigger focus mode through layout system
            if (window.livekitLayout) {
                window.livekitLayout.applyFocusMode(screenShareId, screenShareDiv);
            } else {
            }
        });

        // Also add click listener to the video element itself
        videoElement.addEventListener('click', (e) => {
            e.preventDefault();
            e.stopPropagation();


            if (window.livekitLayout) {
                window.livekitLayout.applyFocusMode(screenShareId, screenShareDiv);
            }
        });

        // Insert screen share at the beginning of video grid for prominence
        videoGrid.insertBefore(screenShareDiv, videoGrid.firstChild);

        // Add a small delay and then test if the element is clickable
        setTimeout(() => {
            console.log('Screen share element test', {
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
        return participant.identity || t('participants.participant');
    }

    /**
     * Remove screen share element
     * @param {string} participantId - Participant ID
     */
    removeScreenShareElement(participantId) {
        const screenShareId = `${participantId}_screen`;

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

    }

    /**
     * Handle track unsubscribed event
     * @param {LiveKit.Track} track - The unsubscribed track
     * @param {LiveKit.TrackPublication} publication - The track publication
     * @param {LiveKit.Participant} participant - The participant
     */
    handleTrackUnsubscribed(track, publication, participant) {

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

        // CRITICAL FIX: Update track state immediately
        this.updateTrackState(participantId, publication, publication.track);
    }

    /**
     * Handle video track unsubscribed
     * @param {LiveKit.VideoTrack} track - The video track
     * @param {LiveKit.TrackPublication} publication - The track publication
     * @param {LiveKit.Participant} participant - The participant
     */
    handleVideoTrackUnsubscribed(track, publication, participant) {
        const participantId = participant.identity;


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

    }

    /**
     * Handle audio track unsubscribed
     * @param {LiveKit.AudioTrack} track - The audio track
     * @param {LiveKit.TrackPublication} publication - The track publication
     * @param {LiveKit.Participant} participant - The participant
     */
    handleAudioTrackUnsubscribed(track, publication, participant) {
        const participantId = participant.identity;


        // Update microphone status icon to show as muted
        this.updateMicrophoneStatusIcon(participantId, false);
        this.updateOverlayMicStatus(participantId, false);

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

    }

    /**
     * Handle screen share track unsubscribed
     * @param {LiveKit.VideoTrack} track - The screen share video track
     * @param {LiveKit.TrackPublication} publication - The track publication
     * @param {LiveKit.Participant} participant - The participant
     */
    handleScreenShareTrackUnsubscribed(track, publication, participant) {
        const participantId = participant.identity;


        // Detach track
        track.detach();

        // Remove screen share element
        this.removeScreenShareElement(participantId);

        // Notify callback
        if (this.config.onVideoTrackDetached) {
            this.config.onVideoTrackDetached(`${participantId}_screen`, track, publication);
        }

    }

    /**
     * Handle screen share audio track unsubscribed
     * @param {LiveKit.AudioTrack} track - The screen share audio track
     * @param {LiveKit.TrackPublication} publication - The track publication
     * @param {LiveKit.Participant} participant - The participant
     */
    handleScreenShareAudioTrackUnsubscribed(track, publication, participant) {
        const participantId = participant.identity;


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

    }

    /**
     * Handle track muted event - ENHANCED WITH IMMEDIATE UI UPDATE
     * @param {LiveKit.TrackPublication} publication - The track publication
     * @param {LiveKit.Participant} participant - The participant
     */
    handleTrackMuted(publication, participant) {
        const participantId = participant.identity;

        // CRITICAL FIX: Update state immediately
        this.updateTrackState(participantId, publication, publication.track);

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
            this.updateOverlayMicStatus(participantId, false);

            // Notify microphone state change
            if (this.config.onMicrophoneStateChanged) {
                this.config.onMicrophoneStateChanged(participantId, false);
            }
        }

        // CRITICAL FIX: Force UI sync
        this.forceUISync(participantId);
    }

    /**
     * Handle track unmuted event - ENHANCED WITH IMMEDIATE UI UPDATE
     * @param {LiveKit.TrackPublication} publication - The track publication
     * @param {LiveKit.Participant} participant - The participant
     */
    handleTrackUnmuted(publication, participant) {
        const participantId = participant.identity;

        // CRITICAL FIX: Update state immediately
        this.updateTrackState(participantId, publication, publication.track);

        if (publication.source === window.LiveKit.Track.Source.ScreenShare) {
            // Handle screen share unmuted (show screen share)
            this.updateScreenShareDisplay(participantId, true);
        } else if (publication.kind === 'video') {
            // CRITICAL FIX: Only show video if we actually have the track
            const hasTrack = publication.track !== null;
            this.updateVideoDisplay(participantId, hasTrack);

            // Notify camera state change
            if (this.config.onCameraStateChanged) {
                this.config.onCameraStateChanged(participantId, hasTrack);
            }
        } else if (publication.kind === 'audio') {
            // CRITICAL FIX: Only show audio status if we actually have the track
            const hasTrack = publication.track !== null;
            this.updateMicrophoneStatusIcon(participantId, hasTrack);
            this.updateOverlayMicStatus(participantId, hasTrack);

            // Notify microphone state change
            if (this.config.onMicrophoneStateChanged) {
                this.config.onMicrophoneStateChanged(participantId, hasTrack);
            }
        }

        // CRITICAL FIX: Force UI sync
        this.forceUISync(participantId);
    }

    /**
     * Get or create video element for participant
     * @param {string} participantId - Participant ID
     * @param {boolean} isLocal - Whether this is the local participant
     * @returns {HTMLVideoElement} Video element
     */
    getOrCreateVideoElement(participantId, isLocal = false) {

        // Try to find the video element directly first
        let videoElement = document.getElementById(`video-${participantId}`);
        
        if (videoElement) {
            return videoElement;
        }

        // Try to find participant element and look for video inside
        let participantElement = document.getElementById(`participant-${participantId}`);

        if (!participantElement) {
            // Alternative search methods
            participantElement = document.querySelector(`[data-participant-id="${participantId}"]`);

            if (!participantElement) {
                // Log available participant elements for debugging
                const allParticipants = document.querySelectorAll('[id^="participant-"], [data-participant-id]');

                // Wait a bit and try again (race condition fix)
                setTimeout(() => {
                    const retryElement = document.getElementById(`participant-${participantId}`);
                    if (retryElement && this.lastTrack && this.lastPublication && this.lastParticipant) {
                        this.handleTrackSubscribed(this.lastTrack, this.lastPublication, this.lastParticipant);
                    }
                }, 500);

                return null;
            }
        }


        // Look for existing video element inside participant
        videoElement = participantElement.querySelector('video');
        
        if (!videoElement) {

            videoElement = document.createElement('video');
            videoElement.id = `video-${participantId}`;
            videoElement.className = 'absolute inset-0 w-full h-full object-cover opacity-0 transition-opacity duration-300 z-10';
            videoElement.autoplay = true;
            videoElement.playsInline = true;
            videoElement.muted = isLocal; // Mute local video to avoid feedback
            videoElement.style.display = 'none';

            // Insert video element at the beginning of participant element
            participantElement.insertBefore(videoElement, participantElement.firstChild);

        } else {
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

        const participantElement = document.getElementById(`participant-${participantId}`);
        if (!participantElement) {
            return;
        }

        const videoElement = participantElement.querySelector('video');
        const placeholder = participantElement.querySelector('.absolute.inset-0.flex.flex-col');

                if (hasVideo) {
            // Show video, hide placeholder completely and show name overlay

            if (videoElement) {
                videoElement.style.opacity = '1';
                videoElement.style.display = 'block';
                videoElement.style.visibility = 'visible';
                videoElement.style.zIndex = '10';
            }

            // Hide placeholder completely when video is on
            if (placeholder) {
                placeholder.style.opacity = '0';
                placeholder.style.visibility = 'hidden';
                placeholder.style.zIndex = '5';
            }

            // Show the existing HTML name overlay
            const nameOverlay = document.getElementById(`name-overlay-${participantId}`);
            if (nameOverlay) {
                nameOverlay.style.display = 'block';
                nameOverlay.style.opacity = '1';
            } else {
            }

        } else {
            // Hide video, show full placeholder with all content

            if (videoElement) {
                videoElement.style.opacity = '0';
                videoElement.style.display = 'none';
                videoElement.style.visibility = 'hidden';
                videoElement.style.zIndex = '5';
            }

            // Show placeholder completely when video is off
            if (placeholder) {
                placeholder.style.opacity = '1';
                placeholder.style.visibility = 'visible';
                placeholder.style.zIndex = '15';
                placeholder.style.backgroundColor = '';

                // Ensure all content inside placeholder is fully visible
                const allElements = placeholder.querySelectorAll('*');
                allElements.forEach(el => {
                    el.style.opacity = '1';
                    el.style.visibility = 'visible';
                });

                // Reset any custom styles that may have been applied
                const statusContainer = placeholder.querySelector('.mt-2.flex.items-center.justify-center.gap-3');
                if (statusContainer) {
                    statusContainer.style.position = '';
                    statusContainer.style.bottom = '';
                    statusContainer.style.left = '';
                    statusContainer.style.backgroundColor = '';
                    statusContainer.style.padding = '';
                    statusContainer.style.borderRadius = '';
                    statusContainer.style.zIndex = '';
                }
            }

            // Hide the HTML name overlay when video is off (placeholder shows the name)
            const nameOverlay = document.getElementById(`name-overlay-${participantId}`);
            if (nameOverlay) {
                nameOverlay.style.display = 'none';
                nameOverlay.style.opacity = '0';
            }

        }

        // Update camera status icon (in status bar)
        this.updateCameraStatusIcon(participantId, hasVideo);
    }

    /**
     * Update name overlay content (for dynamic updates)
     * @param {string} participantId - Participant ID
     * @param {string} displayName - Display name to show
     * @param {boolean} isTeacher - Whether participant is a teacher
     */
    updateNameOverlayContent(participantId, displayName, isTeacher) {
        const nameElement = document.getElementById(`overlay-name-${participantId}`);
        if (nameElement) {
            nameElement.textContent = displayName;
        }
        
        // Update teacher badge visibility
        const teacherBadge = document.querySelector(`#name-overlay-${participantId} .bg-green-600`);
        if (teacherBadge) {
            teacherBadge.style.display = isTeacher ? 'inline-block' : 'none';
        }
    }

    /**
     * Update overlay mic status when video is on
     * @param {string} participantId - Participant ID
     * @param {boolean} hasAudio - Whether participant has active audio
     */
    updateOverlayMicStatus(participantId, hasAudio) {
        const overlayMicIcon = document.getElementById(`overlay-mic-${participantId}`);
        if (overlayMicIcon) {
            if (hasAudio) {
                overlayMicIcon.className = 'ri-mic-line text-sm text-green-500';
            } else {
                overlayMicIcon.className = 'ri-mic-off-line text-sm text-red-500';
            }
        }
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
                if (icon) icon.className = 'ri-video-line text-sm';
            } else {
                cameraStatus.className = 'text-red-500';
                if (icon) icon.className = 'ri-video-off-line text-sm';
            }
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
                if (icon) icon.className = 'ri-mic-line text-sm';
            } else {
                micStatus.className = 'text-red-500';
                if (icon) icon.className = 'ri-mic-off-line text-sm';
            }
        }
    }

    /**
     * Update screen share display visibility
     * @param {string} participantId - Participant ID
     * @param {boolean} isVisible - Whether screen share should be visible
     */
    updateScreenShareDisplay(participantId, isVisible) {

        const screenShareId = `${participantId}_screen`;
        const screenShareElement = document.getElementById(`participant-${screenShareId}`);

        if (!screenShareElement) {
            return;
        }

        const videoElement = screenShareElement.querySelector('video');
        const overlay = screenShareElement.querySelector('.absolute.inset-0.bg-gradient-to-br');

        if (isVisible) {
            // Show screen share

            if (videoElement) {
                videoElement.style.opacity = '1';
                videoElement.style.display = 'block';
                videoElement.style.visibility = 'visible';
            }

            if (overlay) {
                overlay.style.opacity = '0.2'; // Keep overlay slightly visible for title
            }

            screenShareElement.style.opacity = '1';

        } else {
            // Hide screen share or show paused state

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
                            <i class="ri-pause-line text-4xl mb-2"></i>
                            <p class="text-lg font-medium">${t('screen_share.screen_share_paused')}</p>
                        </div>
                    `;
                    overlay.appendChild(pausedIndicator);
                }
            }

            screenShareElement.style.opacity = '0.7';

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
     * Destroy tracks manager and clean up - ENHANCED WITH CLEANUP
     */
    destroy() {

        // CRITICAL FIX: Clear all timeouts
        for (const timeoutId of this.syncTimeouts.values()) {
            clearTimeout(timeoutId);
        }
        this.syncTimeouts.clear();

        // Clear processing queue
        this.processingQueue.clear();

        // Remove all participant tracks
        for (const participantId of this.participantTracks.keys()) {
            this.removeParticipantTracks(participantId);
        }

        this.participantTracks.clear();
        this.trackStates.clear();
        this.lastStateUpdate.clear();

    }
}

// Make class globally available
window.LiveKitTracks = LiveKitTracks;
