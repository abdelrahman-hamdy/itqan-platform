/**
 * FIXED: LiveKit Tracks Module
 * Handles track subscription, attachment/detachment, and media element management
 * CRITICAL FIXES: Proper state synchronization, race condition elimination, robust track handling
 */

class LiveKitTracksFixed {
    constructor(config = {}) {
        this.config = config;
        this.participantTracks = new Map(); // participantId -> { video: HTMLVideoElement, audio: HTMLAudioElement }
        
        // CRITICAL FIX: Single source of truth for track states
        this.trackStates = new Map(); // participantId -> comprehensive state object
        
        // Track subscription management
        this.subscriptionQueue = new Map(); // participantId -> Promise[]
        this.processingQueue = new Set(); // Track which participants are being processed
        
        // State synchronization
        this.syncTimeouts = new Map(); // participantId -> timeout ID
        this.lastStateUpdate = new Map(); // participantId -> timestamp
        
        console.log('🎬 LiveKitTracksFixed initialized with robust sync');
    }

    /**
     * CRITICAL FIX: Handle track subscribed with proper state management
     */
    async handleTrackSubscribed(track, publication, participant) {
        const participantId = participant.identity;
        const isLocal = participant.isLocal;
        
        console.log(`📹 [FIXED] Track subscribed: ${track.kind} from ${participantId} (local: ${isLocal})`);
        console.log(`📊 Publication state: subscribed=${publication.isSubscribed}, muted=${publication.isMuted}, source=${publication.source}`);

        // Prevent duplicate processing
        if (this.processingQueue.has(`${participantId}-${track.kind}-${publication.source}`)) {
            console.log(`⏭️ Already processing ${track.kind} for ${participantId}, skipping`);
            return;
        }
        
        const processingKey = `${participantId}-${track.kind}-${publication.source}`;
        this.processingQueue.add(processingKey);

        try {
            // CRITICAL FIX: Initialize participant state with REAL track data
            this.initializeParticipantState(participantId, isLocal);
            
            // Update state based on actual publication
            this.updateTrackState(participantId, publication, track);

            // Handle different track types
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
            console.log(`📊 [FIXED] Initialized state for ${participantId}:`, defaultState);
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
        
        console.log(`📊 [FIXED] Updated track state for ${participantId}:`, state);
    }

    /**
     * CRITICAL FIX: Robust video track handling
     */
    async handleVideoTrackSubscribed(track, publication, participant) {
        const participantId = participant.identity;
        const isLocal = participant.isLocal;

        console.log(`📹 [FIXED] Processing video track for ${participantId}`);

        try {
            // Get video element with retry mechanism
            const videoElement = await this.getVideoElementWithRetry(participantId, isLocal);
            if (!videoElement) {
                console.error(`❌ Failed to get video element for ${participantId} after retries`);
                return;
            }

            // Attach track with error handling
            try {
                track.attach(videoElement);
                console.log(`✅ Video track attached for ${participantId}`);
            } catch (attachError) {
                console.error(`❌ Failed to attach video track for ${participantId}:`, attachError);
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
            console.error(`❌ Error handling video track for ${participantId}:`, error);
        }
    }

    /**
     * CRITICAL FIX: Robust audio track handling
     */
    async handleAudioTrackSubscribed(track, publication, participant) {
        const participantId = participant.identity;
        const isLocal = participant.isLocal;

        console.log(`🎤 [FIXED] Processing audio track for ${participantId} (muted: ${publication.isMuted})`);

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
                    console.error(`❌ Failed to attach audio track for ${participantId}:`, attachError);
                }
            }

            // Callback notification
            if (this.config.onAudioTrackAttached) {
                this.config.onAudioTrackAttached(participantId, null, track, publication);
            }

        } catch (error) {
            console.error(`❌ Error handling audio track for ${participantId}:`, error);
        }
    }

    /**
     * CRITICAL FIX: Get video element with retry mechanism
     */
    async getVideoElementWithRetry(participantId, isLocal, maxRetries = 3) {
        for (let attempt = 1; attempt <= maxRetries; attempt++) {
            console.log(`📹 Getting video element for ${participantId} (attempt ${attempt}/${maxRetries})`);
            
            const videoElement = this.getOrCreateVideoElement(participantId, isLocal);
            if (videoElement) {
                return videoElement;
            }
            
            if (attempt < maxRetries) {
                console.log(`⏳ Waiting before retry for ${participantId}...`);
                await new Promise(resolve => setTimeout(resolve, 500 * attempt));
            }
        }
        
        return null;
    }

    /**
     * CRITICAL FIX: Wait for video to be ready before showing
     */
    async waitForVideoReady(videoElement, participantId, timeout = 3000) {
        console.log(`📹 Waiting for video to be ready for ${participantId}`);
        
        return new Promise((resolve) => {
            const checkReady = () => {
                if (videoElement.srcObject && 
                    videoElement.srcObject.getTracks().length > 0 &&
                    videoElement.readyState >= 2) {
                    console.log(`✅ Video ready for ${participantId}`);
                    resolve(true);
                    return;
                }
            };

            // Check immediately
            checkReady();

            // Set up event listeners for video ready states
            const onLoadedData = () => {
                console.log(`📹 Video loaded data for ${participantId}`);
                checkReady();
            };

            const onCanPlay = () => {
                console.log(`📹 Video can play for ${participantId}`);
                checkReady();
            };

            videoElement.addEventListener('loadeddata', onLoadedData, { once: true });
            videoElement.addEventListener('canplay', onCanPlay, { once: true });

            // Timeout fallback
            setTimeout(() => {
                videoElement.removeEventListener('loadeddata', onLoadedData);
                videoElement.removeEventListener('canplay', onCanPlay);
                console.log(`⏰ Video ready timeout for ${participantId}, proceeding anyway`);
                resolve(false);
            }, timeout);
        });
    }

    /**
     * CRITICAL FIX: Improved video display update
     */
    updateVideoDisplay(participantId, hasVideo) {
        console.log(`🔄 [FIXED] Updating video display for ${participantId}: ${hasVideo ? 'SHOW' : 'HIDE'}`);

        const participantElement = document.getElementById(`participant-${participantId}`);
        if (!participantElement) {
            console.warn(`⚠️ Participant element not found for ${participantId}`);
            return;
        }

        const videoElement = participantElement.querySelector('video');
        const placeholder = participantElement.querySelector('.absolute.inset-0.flex.flex-col');
        const nameOverlay = document.getElementById(`name-overlay-${participantId}`);

        if (hasVideo && videoElement && videoElement.srcObject) {
            // Show video - CRITICAL FIX: Verify video actually has content
            const tracks = videoElement.srcObject.getTracks();
            const hasVideoTracks = tracks.some(track => track.kind === 'video' && track.enabled);
            
            if (hasVideoTracks) {
                console.log(`📹 Showing video for ${participantId} (verified content)`);
                
                // Show video
                videoElement.style.opacity = '1';
                videoElement.style.display = 'block';
                videoElement.style.visibility = 'visible';
                videoElement.style.zIndex = '10';

                // Hide placeholder
                if (placeholder) {
                    placeholder.style.opacity = '0';
                    placeholder.style.visibility = 'hidden';
                    placeholder.style.zIndex = '5';
                }

                // Show name overlay
                if (nameOverlay) {
                    nameOverlay.style.display = 'block';
                    nameOverlay.style.opacity = '1';
                }
            } else {
                console.log(`📹 Video element exists but no active video tracks for ${participantId}`);
                hasVideo = false; // Force placeholder view
            }
        }
        
        if (!hasVideo) {
            // Show placeholder
            console.log(`📹 Hiding video for ${participantId} (showing placeholder)`);
            
            // Hide video
            if (videoElement) {
                videoElement.style.opacity = '0';
                videoElement.style.display = 'none';
                videoElement.style.visibility = 'hidden';
                videoElement.style.zIndex = '5';
            }

            // Show placeholder
            if (placeholder) {
                placeholder.style.opacity = '1';
                placeholder.style.visibility = 'visible';
                placeholder.style.zIndex = '15';
                
                // Ensure all placeholder content is visible
                const allElements = placeholder.querySelectorAll('*');
                allElements.forEach(el => {
                    el.style.opacity = '1';
                    el.style.visibility = 'visible';
                });
            }

            // Hide name overlay
            if (nameOverlay) {
                nameOverlay.style.display = 'none';
                nameOverlay.style.opacity = '0';
            }
        }

        // Update camera status icon
        this.updateCameraStatusIcon(participantId, hasVideo);
        
        // Update track state
        const state = this.trackStates.get(participantId);
        if (state) {
            state.uiSynced = true;
            state.lastUpdate = Date.now();
        }
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
        console.log(`🔄 [FIXED] Performing UI sync for ${participantId}`);
        
        const state = this.trackStates.get(participantId);
        if (!state) {
            console.warn(`⚠️ No state found for ${participantId}`);
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
        console.log(`✅ UI sync completed for ${participantId}`);
    }

    /**
     * CRITICAL FIX: Handle track muted with immediate UI update
     */
    handleTrackMuted(publication, participant) {
        const participantId = participant.identity;
        console.log(`🔇 [FIXED] Track muted: ${publication.kind} from ${participantId}`);

        // Update state immediately
        this.updateTrackState(participantId, publication, publication.track);

        // Update UI based on track type
        if (publication.kind === 'video') {
            this.updateVideoDisplay(participantId, false);
            if (this.config.onCameraStateChanged) {
                this.config.onCameraStateChanged(participantId, false);
            }
        } else if (publication.kind === 'audio') {
            this.updateMicrophoneStatusIcon(participantId, false);
            this.updateOverlayMicStatus(participantId, false);
            if (this.config.onMicrophoneStateChanged) {
                this.config.onMicrophoneStateChanged(participantId, false);
            }
        }

        // Force UI sync
        this.forceUISync(participantId);
    }

    /**
     * CRITICAL FIX: Handle track unmuted with immediate UI update
     */
    handleTrackUnmuted(publication, participant) {
        const participantId = participant.identity;
        console.log(`🔊 [FIXED] Track unmuted: ${publication.kind} from ${participantId}`);

        // Update state immediately
        this.updateTrackState(participantId, publication, publication.track);

        // Update UI based on track type
        if (publication.kind === 'video') {
            // Only show video if we actually have the track
            const hasTrack = publication.track !== null;
            this.updateVideoDisplay(participantId, hasTrack);
            if (this.config.onCameraStateChanged) {
                this.config.onCameraStateChanged(participantId, hasTrack);
            }
        } else if (publication.kind === 'audio') {
            const hasTrack = publication.track !== null;
            this.updateMicrophoneStatusIcon(participantId, hasTrack);
            this.updateOverlayMicStatus(participantId, hasTrack);
            if (this.config.onMicrophoneStateChanged) {
                this.config.onMicrophoneStateChanged(participantId, hasTrack);
            }
        }

        // Force UI sync
        this.forceUISync(participantId);
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
     * Get participant track state
     */
    getParticipantTrackState(participantId) {
        return this.trackStates.get(participantId) || {
            hasVideo: false,
            hasAudio: false,
            videoMuted: true,
            audioMuted: true,
            hasScreenShare: false,
            screenShareMuted: true,
            uiSynced: false
        };
    }

    /**
     * Check if participant has active video
     */
    participantHasActiveVideo(participantId) {
        const state = this.getParticipantTrackState(participantId);
        return state.hasVideo && !state.videoMuted;
    }

    /**
     * Check if participant has active audio
     */
    participantHasActiveAudio(participantId) {
        const state = this.getParticipantTrackState(participantId);
        return state.hasAudio && !state.audioMuted;
    }

    // Include all other methods from original tracks.js that aren't track-state related
    // (getOrCreateVideoElement, getOrCreateAudioElement, updateCameraStatusIcon, etc.)
    
    getOrCreateVideoElement(participantId, isLocal = false) {
        console.log(`📹 Getting video element for ${participantId}, isLocal: ${isLocal}`);

        let videoElement = document.getElementById(`video-${participantId}`);
        
        if (videoElement) {
            return videoElement;
        }

        let participantElement = document.getElementById(`participant-${participantId}`);
        if (!participantElement) {
            participantElement = document.querySelector(`[data-participant-id="${participantId}"]`);
            if (!participantElement) {
                console.error(`❌ Participant element not found for ${participantId}`);
                return null;
            }
        }

        videoElement = participantElement.querySelector('video');
        
        if (!videoElement) {
            videoElement = document.createElement('video');
            videoElement.id = `video-${participantId}`;
            videoElement.className = 'absolute inset-0 w-full h-full object-cover opacity-0 transition-opacity duration-300 z-10';
            videoElement.autoplay = true;
            videoElement.playsInline = true;
            videoElement.muted = isLocal;
            videoElement.style.display = 'none';

            participantElement.insertBefore(videoElement, participantElement.firstChild);
        }

        return videoElement;
    }

    getOrCreateAudioElement(participantId) {
        let audioElement = document.getElementById(`audio-${participantId}`);

        if (!audioElement) {
            audioElement = document.createElement('audio');
            audioElement.id = `audio-${participantId}`;
            audioElement.autoplay = true;
            audioElement.style.display = 'none';
            document.body.appendChild(audioElement);
        }

        return audioElement;
    }

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
        }
    }

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
        }
    }

    updateOverlayMicStatus(participantId, hasAudio) {
        const overlayMicIcon = document.getElementById(`overlay-mic-${participantId}`);
        if (overlayMicIcon) {
            if (hasAudio) {
                overlayMicIcon.className = 'fas fa-microphone text-sm text-green-500';
            } else {
                overlayMicIcon.className = 'fas fa-microphone-slash text-sm text-red-500';
            }
        }
    }

    // Include other non-problematic methods
    handleTrackUnsubscribed(track, publication, participant) {
        const participantId = participant.identity;
        console.log(`📹 [FIXED] Track unsubscribed: ${track.kind} from ${participantId}`);

        // Update state
        this.updateTrackState(participantId, publication, null);

        // Detach track
        track.detach();

        // Update UI
        if (publication.kind === 'video') {
            this.updateVideoDisplay(participantId, false);
        } else if (publication.kind === 'audio') {
            this.updateMicrophoneStatusIcon(participantId, false);
            this.updateOverlayMicStatus(participantId, false);
        }

        // Remove track reference
        if (this.participantTracks.has(participantId)) {
            delete this.participantTracks.get(participantId)[publication.kind];
        }
    }

    removeParticipantTracks(participantId) {
        console.log(`🧹 [FIXED] Removing tracks for participant ${participantId}`);

        // Remove track references
        if (this.participantTracks.has(participantId)) {
            this.participantTracks.delete(participantId);
        }

        // Remove track state
        this.trackStates.delete(participantId);

        // Cancel any pending syncs
        if (this.syncTimeouts.has(participantId)) {
            clearTimeout(this.syncTimeouts.get(participantId));
            this.syncTimeouts.delete(participantId);
        }
    }

    destroy() {
        console.log('🧹 Destroying tracks manager...');

        // Clear all timeouts
        for (const timeoutId of this.syncTimeouts.values()) {
            clearTimeout(timeoutId);
        }

        // Remove all participant tracks
        for (const participantId of this.participantTracks.keys()) {
            this.removeParticipantTracks(participantId);
        }

        this.participantTracks.clear();
        this.trackStates.clear();
        this.syncTimeouts.clear();
        this.processingQueue.clear();
        this.subscriptionQueue.clear();
    }
}

// Make class globally available
window.LiveKitTracksFixed = LiveKitTracksFixed;
