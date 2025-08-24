/**
 * Professional LiveKit Meeting Interface
 * Based on the official LiveKit Meet example
 * Converted from React components to vanilla JavaScript
 */

class ProfessionalLiveKitMeeting {
    constructor(config) {
        this.config = config;
        this.meetingConfig = config; // Store for participant identification and UI logic
        this.room = null;
        this.localParticipant = null;
        this.participants = new Map();
        this.participantVideoStates = new Map(); // Enhanced video state tracking
        this.isAudioEnabled = true;
        this.isVideoEnabled = true;
        this.isLocalCameraEnabled = true; // Track local camera state separately
        this.isScreenSharing = false;
        this.isHandRaised = false;
        this.isRecording = false;
        this.isConnected = false;
        this.isStarting = false;

        // Video state synchronization intervals
        this.videoStateCheckInterval = null;

        // Focus mode state
        this.isFocusModeActive = false;
        this.focusedParticipant = null;
        this.isSidebarOpen = false;
        this.responsiveObserver = null;

        // UI Elements cache
        this.elements = {};

        // Event listeners
        this.eventListeners = new Map();

        // Initialize responsive system
        this.initializeResponsiveSystem();

        console.log('🎯 ProfessionalLiveKitMeeting initialized with config:', config);
    }

    // Initialize CSS-first video layout system
    initializeResponsiveSystem() {
        console.log('📱 Initializing CSS-first video layout system...');

        // Set up dynamic height calculation
        this.calculateMeetingHeight();

        // Set up sidebar state detection
        this.detectSidebarState();

        // Add window resize listener for height recalculation
        window.addEventListener('resize', () => {
            this.calculateMeetingHeight();
            this.updateVideoLayoutClasses();
            this.scheduleFocusedLayoutUpdate();
        });

        console.log('✅ CSS-first system initialized');
    }

    // Calculate meeting height based on viewport and aspect ratio
    calculateMeetingHeight() {
        const meetingInterface = document.getElementById('livekitMeetingInterface');
        const controlBar = document.querySelector('.bg-gray-800.border-t.border-gray-700'); // Control bar
        const meetingHeader = document.querySelector('.bg-gradient-to-r.from-blue-500'); // Meeting header

        if (!meetingInterface) return;

        const viewportWidth = window.innerWidth;
        const viewportHeight = window.innerHeight;
        const controlBarHeight = controlBar ? controlBar.offsetHeight : 80;
        const headerHeight = meetingHeader ? meetingHeader.offsetHeight : 60;

        // Calculate optimal height based on 16:9 aspect ratio with space for controls
        const availableHeight = viewportHeight - 200; // Leave space for page elements
        const optimalHeight = Math.min(availableHeight, (viewportWidth * 9) / 16 + controlBarHeight + headerHeight);
        const finalHeight = Math.max(400, optimalHeight); // Minimum 400px

        meetingInterface.style.height = `${finalHeight}px`;

        console.log(`📱 Meeting height set to: ${finalHeight}px (viewport: ${viewportWidth}x${viewportHeight})`);
    }

    // Detect and track sidebar open/close state
    detectSidebarState() {
        const sidebar = document.getElementById('meetingSidebar');
        if (!sidebar) return;

        // Create observer for sidebar state changes
        const observer = new MutationObserver((mutations) => {
            mutations.forEach((mutation) => {
                if (mutation.type === 'attributes' && mutation.attributeName === 'class') {
                    this.updateSidebarState();
                }
            });
        });

        observer.observe(sidebar, {
            attributes: true,
            attributeFilter: ['class']
        });

        // Initial state check
        this.updateSidebarState();
    }

    // Update sidebar state and adjust video layout accordingly
    updateSidebarState() {
        const sidebar = document.getElementById('meetingSidebar');
        const videoArea = document.getElementById('videoArea');

        if (!sidebar || !videoArea) return;

        const isOpen = !sidebar.classList.contains('-translate-x-full');
        this.isSidebarOpen = isOpen;

        if (isOpen) {
            videoArea.classList.add('sidebar-open');
            console.log('📱 Sidebar opened, adjusting video layout...');
        } else {
            videoArea.classList.remove('sidebar-open');
            console.log('📱 Sidebar closed, restoring video layout...');
        }

        // Update video grid layout to be responsive to sidebar state
        this.updateVideoGridLayout();
    }

    // Set up responsive observer for video grid
    setupResponsiveObserver() {
        const videoGrid = document.getElementById('videoGrid');
        if (!videoGrid) return;

        // Create ResizeObserver to watch for container size changes
        if (window.ResizeObserver) {
            this.responsiveObserver = new ResizeObserver((entries) => {
                entries.forEach((entry) => {
                    this.handleContainerResize(entry);
                });
            });

            this.responsiveObserver.observe(videoGrid);
            console.log('📱 Responsive observer set up for video grid');
        }
    }

    // Handle container resize events
    handleContainerResize(entry) {
        const { width, height } = entry.contentRect;
        console.log(`📱 Container resized: ${width}x${height}`);

        // Adjust video grid based on available space
        this.adjustVideoGridForContainer(width, height);
    }

    // Adjust video grid for container size
    adjustVideoGridForContainer(width, height) {
        const videoGrid = document.getElementById('videoGrid');
        if (!videoGrid) return;

        // Calculate optimal grid based on available space
        const participantCount = this.participants.size;
        const optimalGrid = this.calculateOptimalGrid(width, height, participantCount);

        // Apply optimal grid configuration
        this.applyOptimalGrid(videoGrid, optimalGrid);
    }

    // Enhanced optimal grid configuration for up to 100+ participants
    calculateOptimalGrid(width, height, participantCount) {
        console.log(`🔢 Calculating grid for ${participantCount} participants in ${width}x${height}`);

        if (participantCount === 0) return { cols: 1, rows: 1, gap: '1rem', minSize: 280 };
        if (participantCount === 1) return { cols: 1, rows: 1, gap: '1rem', minSize: 400 };
        if (participantCount === 2) return { cols: 2, rows: 1, gap: '1rem', minSize: 300 };
        if (participantCount <= 4) return { cols: 2, rows: 2, gap: '0.875rem', minSize: 280 };
        if (participantCount <= 6) return { cols: 3, rows: 2, gap: '0.75rem', minSize: 240 };
        if (participantCount <= 9) return { cols: 3, rows: 3, gap: '0.625rem', minSize: 220 };
        if (participantCount <= 12) return { cols: 4, rows: 3, gap: '0.5rem', minSize: 200 };
        if (participantCount <= 16) return { cols: 4, rows: 4, gap: '0.5rem', minSize: 180 };
        if (participantCount <= 25) return { cols: 5, rows: 5, gap: '0.375rem', minSize: 160 };
        if (participantCount <= 36) return { cols: 6, rows: 6, gap: '0.25rem', minSize: 140 };
        if (participantCount <= 49) return { cols: 7, rows: 7, gap: '0.25rem', minSize: 120 };
        if (participantCount <= 64) return { cols: 8, rows: 8, gap: '0.125rem', minSize: 100 };
        if (participantCount <= 81) return { cols: 9, rows: 9, gap: '0.125rem', minSize: 90 };
        if (participantCount <= 100) return { cols: 10, rows: 10, gap: '0.125rem', minSize: 80 };

        // For extremely large meetings (100+ participants)
        const availableWidth = width - 32; // Account for padding
        const availableHeight = height - 32; // Account for padding

        // Calculate optimal columns and rows to fit all participants
        const aspectRatio = availableWidth / availableHeight;
        const cols = Math.ceil(Math.sqrt(participantCount * aspectRatio));
        const rows = Math.ceil(participantCount / cols);

        // Calculate minimum size based on available space
        const maxCellWidth = availableWidth / cols;
        const maxCellHeight = availableHeight / rows;
        const minSize = Math.max(60, Math.min(maxCellWidth - 8, maxCellHeight - 8)); // Min 60px, max based on space

        console.log(`🔢 Large meeting grid: ${cols}x${rows}, minSize: ${minSize}px`);

        return {
            cols,
            rows,
            gap: participantCount > 100 ? '0.0625rem' : '0.125rem', // 1px gap for 100+
            minSize
        };
    }

    // Enhanced grid configuration application for scalable layouts
    applyOptimalGrid(videoGrid, config) {
        console.log(`📱 Applying enhanced grid config:`, config);

        // Remove all existing grid classes to start fresh
        videoGrid.className = videoGrid.className.replace(/grid-cols-\d+|grid-rows-\d+/g, '').trim();

        // Set CSS custom properties for the grid
        videoGrid.style.setProperty('--grid-cols', config.cols.toString());
        videoGrid.style.setProperty('--grid-rows', config.rows.toString());
        videoGrid.style.setProperty('--grid-gap', config.gap);
        videoGrid.style.setProperty('--min-cell-size', `${config.minSize}px`);
        videoGrid.style.setProperty('--max-cell-size', `${config.minSize * 1.5}px`);

        // Enhanced CSS grid properties for large participant counts
        videoGrid.style.display = 'grid';
        videoGrid.style.gridTemplateColumns = `repeat(${config.cols}, minmax(${config.minSize}px, 1fr))`;
        videoGrid.style.gridTemplateRows = `repeat(${config.rows}, minmax(${config.minSize}px, 1fr))`;
        videoGrid.style.gap = config.gap;
        videoGrid.style.width = '100%';
        videoGrid.style.height = '100%';
        videoGrid.style.overflow = 'auto'; // Enable scrolling for very large grids
        videoGrid.style.scrollBehavior = 'smooth';

        // Add responsive classes based on participant count
        const participantCount = config.cols * config.rows;
        if (participantCount > 100) {
            videoGrid.classList.add('mega-grid', 'participants-100plus');
        } else if (participantCount > 50) {
            videoGrid.classList.add('large-grid', 'participants-50plus');
        } else if (participantCount > 25) {
            videoGrid.classList.add('medium-grid', 'participants-25plus');
        } else {
            videoGrid.classList.add('standard-grid');
        }

        console.log(`✅ Grid applied: ${config.cols}x${config.rows}, ${config.minSize}px cells`);
    }

    // Enhanced window resize handling
    handleResize() {
        console.log('📱 Enhanced window resize handling...');

        // Debounce resize events
        clearTimeout(this.resizeTimeout);
        this.resizeTimeout = setTimeout(() => {
            // Recalculate meeting height
            this.calculateMeetingHeight();

            // Update video grid layout with new dimensions
            this.updateVideoGridLayout();

            // Force recalculation of grid if participants exist
            const videoGrid = document.getElementById('videoGrid');
            if (videoGrid && this.participants.size > 0) {
                this.applyParticipantGridLayout(videoGrid, this.participants.size);
            }

            // Update focused layout if active
            if (this.isFocusModeActive) {
                this.scheduleFocusedLayoutUpdate();
            }
        }, 150);
    }

    // Check if participant has active video track
    hasActiveVideoTrack(participant) {
        if (!participant) {
            console.log('🔍 hasActiveVideoTrack: No participant provided');
            return false;
        }

        const isLocal = participant === this.localParticipant;
        const identity = participant.identity;

        console.log(`🔍 Enhanced video track check for ${identity} (local: ${isLocal})`);

        try {
            // Strategy 1: Check via videoTracks Map (modern SDK)
            if (participant.videoTracks && participant.videoTracks instanceof Map) {
                console.log(`🔍 Map-based check (${participant.videoTracks.size} tracks)`);

                for (const [trackSid, publication] of participant.videoTracks) {
                    const isCamera = publication.source === window.LiveKit?.Track?.Source?.Camera ||
                        publication.source === 'camera' ||
                        publication.kind === 'video';

                    if (isCamera) {
                        const hasTrack = publication.track !== null && publication.track !== undefined;
                        const isUnmuted = !publication.isMuted;
                        const isEnabled = publication.isEnabled !== false;
                        const isSubscribed = isLocal || publication.isSubscribed;

                        console.log(`🔍 Camera track ${trackSid}:`, {
                            hasTrack, isUnmuted, isEnabled, isSubscribed
                        });

                        if (hasTrack && isUnmuted && isEnabled && isSubscribed) {
                            console.log(`✅ Active camera track found for ${identity}`);
                            return true;
                        }
                    }
                }
            }

            // Strategy 2: Check via trackPublications (compatibility fallback)
            if (participant.trackPublications instanceof Map) {
                console.log(`🔍 TrackPublications check (${participant.trackPublications.size} tracks)`);

                for (const [trackSid, publication] of participant.trackPublications) {
                    if (publication.kind === 'video') {
                        const hasTrack = publication.track !== null;
                        const isUnmuted = !publication.isMuted;
                        const isSubscribed = isLocal || publication.isSubscribed;

                        if (hasTrack && isUnmuted && isSubscribed) {
                            console.log(`✅ Active video via trackPublications for ${identity}`);
                            return true;
                        }
                    }
                }
            }

            // Strategy 3: Legacy object-based check
            if (participant.videoTracks && typeof participant.videoTracks === 'object') {
                console.log(`🔍 Legacy object check`);

                let tracks = [];
                if (typeof participant.videoTracks.values === 'function') {
                    tracks = Array.from(participant.videoTracks.values());
                } else if (typeof participant.videoTracks.entries === 'function') {
                    tracks = Array.from(participant.videoTracks.entries()).map(([k, v]) => v);
                } else {
                    tracks = Object.values(participant.videoTracks);
                }

                for (const publication of tracks) {
                    if (publication && publication.track && !publication.isMuted) {
                        const isSubscribed = isLocal || publication.isSubscribed;
                        if (isSubscribed) {
                            console.log(`✅ Active video via legacy method for ${identity}`);
                            return true;
                        }
                    }
                }
            }

            // Strategy 4: DOM verification (final fallback)
            const participantElement = document.getElementById(`participant-${identity}`);
            if (participantElement) {
                const videoElement = participantElement.querySelector('video');
                if (videoElement &&
                    videoElement.srcObject &&
                    videoElement.videoWidth > 0 &&
                    videoElement.videoHeight > 0 &&
                    !videoElement.paused) {
                    console.log(`✅ Active video confirmed via DOM for ${identity}`);
                    return true;
                }
            }

        } catch (error) {
            console.error(`❌ Error in video track check for ${identity}:`, error);
        }

        console.log(`❌ No active video track found for ${identity}`);
        return false;
    }

    // Enhanced video state synchronization method
    syncParticipantVideoState(participant, hasVideo) {
        if (!participant) return;

        const identity = participant.identity;
        const isLocal = participant === this.localParticipant;

        console.log(`🔄 Syncing video state for ${identity} (local: ${isLocal}): ${hasVideo ? 'ON' : 'OFF'}`);

        // Update our internal state tracking
        if (!this.participantVideoStates) {
            this.participantVideoStates = new Map();
        }
        this.participantVideoStates.set(identity, hasVideo);

        // Get participant element
        const participantElement = document.getElementById(`participant-${identity}`);
        if (!participantElement) {
            console.log(`⚠️ No element found for ${identity}, scheduling retry`);
            // Retry after a brief delay in case the element is being created
            setTimeout(() => {
                const retryElement = document.getElementById(`participant-${identity}`);
                if (retryElement) {
                    this.syncParticipantVideoState(participant, hasVideo);
                }
            }, 500);
            return;
        }

        // Update camera overlay immediately
        if (hasVideo) {
            console.log(`🟢 Hiding camera-off overlay for ${identity}`);
            this.hideCameraOffOverlay(participantElement);
        } else {
            console.log(`🔴 Showing camera-off overlay for ${identity}`);
            this.showCameraOffOverlay(participantElement, participant);
        }

        // Update camera status indicator
        const cameraStatus = participantElement.querySelector(`#camera-status-${identity}`);
        if (cameraStatus) {
            if (hasVideo) {
                cameraStatus.className = 'w-6 h-6 bg-green-600 rounded-full flex items-center justify-center';
                const icon = cameraStatus.querySelector('i');
                if (icon) {
                    icon.className = 'fas fa-video text-white text-xs';
                }
            } else {
                cameraStatus.className = 'w-6 h-6 bg-red-600 rounded-full flex items-center justify-center';
                const icon = cameraStatus.querySelector('i');
                if (icon) {
                    icon.className = 'fas fa-video-slash text-white text-xs';
                }
            }
        }

        // Broadcast state change to other participants (for consistency)
        if (isLocal) {
            this.broadcastCameraState(identity, hasVideo);
        }

        console.log(`✅ Video state synchronized for ${identity}`);
    }

    // Synchronize all participant video states
    synchronizeAllVideoStates() {
        if (!this.room || !this.participants) return;

        console.log('🔄 Synchronizing all participant video states...');

        // Sync local participant
        if (this.localParticipant) {
            const hasVideo = this.hasActiveVideoTrack(this.localParticipant);
            this.syncParticipantVideoState(this.localParticipant, hasVideo);
        }

        // Sync all remote participants
        this.participants.forEach((participant, identity) => {
            if (participant !== this.localParticipant) {
                const hasVideo = this.hasActiveVideoTrack(participant);
                this.syncParticipantVideoState(participant, hasVideo);
            }
        });

        console.log('✅ All video states synchronized');
    }

    // Update participant camera status based on actual track state
    updateParticipantCameraStatus(participant) {
        if (!participant) return;

        const participantElement = document.getElementById(`participant-${participant.identity}`);
        if (!participantElement) return;

        const hasVideo = this.hasActiveVideoTrack(participant);
        const isLocal = participant === this.localParticipant;

        console.log(`📹 Updating camera status for ${participant.identity} (local: ${isLocal}): ${hasVideo ? 'HAS VIDEO' : 'NO VIDEO'}`);

        // Update camera overlay based on video status
        if (hasVideo) {
            // Camera is on - hide overlay
            console.log(`🟢 Hiding camera-off overlay for ${participant.identity}`);
            this.hideCameraOffOverlay(participantElement);
        } else {
            // Camera is off - show overlay
            console.log(`🔴 Showing camera-off overlay for ${participant.identity}`);
            this.showCameraOffOverlay(participantElement, participant);
        }

        const cameraStatus = participantElement.querySelector(`#camera-status-${participant.identity}`);

        if (cameraStatus) {
            if (hasVideo) {
                cameraStatus.className = 'w-6 h-6 bg-green-600 rounded-full flex items-center justify-center';
                const icon = cameraStatus.querySelector('i');
                if (icon) {
                    icon.className = 'fas fa-video text-white text-xs';
                }
            } else {
                cameraStatus.className = 'w-6 h-6 bg-red-600 rounded-full flex items-center justify-center';
                const icon = cameraStatus.querySelector('i');
                if (icon) {
                    icon.className = 'fas fa-video-slash text-white text-xs';
                }
            }
        }

        console.log(`📹 Camera status updated for ${participant.identity}: ${hasVideo ? 'ON' : 'OFF'}`);
    }

    // Unified teacher detection logic
    isParticipantTeacher(participant, isLocal = null) {
        if (isLocal === null) {
            isLocal = participant === this.localParticipant;
        }

        if (isLocal) {
            // For local participant, check meeting config
            return this.meetingConfig && this.meetingConfig.userType === 'quran_teacher';
        } else {
            // For remote participants, check metadata first, then fallback to identity
            if (participant.metadata) {
                try {
                    const metadata = JSON.parse(participant.metadata);
                    return metadata.userType === 'quran_teacher';
                } catch (e) {
                    // If metadata parsing fails, fallback to identity check
                }
            }
            // Fallback: check if identity contains teacher indicator
            return participant.identity.includes('معلم') || participant.identity.includes('teacher');
        }
    }

    // Create or get camera-off overlay for participant
    getOrCreateCameraOffOverlay(participantElement, participant) {
        console.log(`🔍 getOrCreateCameraOffOverlay called for ${participant.identity}`);
        console.log(`🔍 Participant element:`, participantElement);
        console.log(`🔍 Participant element type:`, typeof participantElement);
        console.log(`🔍 Participant element nodeType:`, participantElement?.nodeType);

        if (!participantElement) {
            console.error(`❌ No participant element provided for ${participant.identity}`);
            return null;
        }

        if (!participantElement.appendChild) {
            console.error(`❌ Participant element has no appendChild method for ${participant.identity}`);
            console.error(`❌ Participant element methods:`, Object.getOwnPropertyNames(participantElement));
            return null;
        }

        // Check if overlay already exists
        let overlay = participantElement.querySelector('.camera-off-overlay');
        console.log(`🔍 Existing overlay found:`, !!overlay);

        if (!overlay) {
            console.log(`🔍 Creating new overlay for ${participant.identity}`);
            try {
                // Create new overlay
                const displayName = participant.identity || 'مشارك';
                const isLocal = participant === this.localParticipant;
                const isTeacher = this.isParticipantTeacher(participant, isLocal);

                overlay = document.createElement('div');
                console.log(`🔍 Overlay element created:`, overlay);

                overlay.className = 'absolute inset-0 flex flex-col items-center justify-center bg-gradient-to-br from-blue-900 to-gray-800 z-10 camera-off-overlay opacity-0 transition-opacity duration-300';
                overlay.innerHTML = `
                    <div class="flex flex-col items-center text-center">
                        <div class="relative w-20 h-20 sm:w-24 sm:h-24 bg-gray-700 bg-opacity-60 rounded-full flex items-center justify-center mb-3 shadow-lg backdrop-blur-sm">
                            <i class="fas fa-video-slash text-white text-3xl sm:text-4xl opacity-80"></i>
                        </div>
                        <p class="text-white text-sm sm:text-base font-medium px-2 text-center">${displayName}</p>
                        <p class="text-gray-300 text-xs mt-1">${isLocal ? '(أنت)' : isTeacher ? '<span class="text-green-400">معلم</span>' : 'مشارك'}</p>
                        <p class="text-gray-400 text-xs mt-1">الكاميرا مغلقة</p>
                    </div>
                `;

                console.log(`🔍 About to append overlay to participant element`);
                console.log(`🔍 Participant element before append:`, participantElement.outerHTML);

                participantElement.appendChild(overlay);

                console.log(`🔍 Overlay appended successfully`);
                console.log(`🔍 Participant element after append:`, participantElement.outerHTML);
                console.log(`🔍 New overlay created and appended for ${participant.identity}`);

            } catch (error) {
                console.error(`❌ Error creating overlay for ${participant.identity}:`, error);
                return null;
            }
        } else {
            console.log(`🔍 Using existing overlay for ${participant.identity}`);
        }

        return overlay;
    }

    // Enhanced camera-off overlay display with improved reliability
    showCameraOffOverlay(participantElement, participant) {
        if (!participantElement || !participant) return;

        const identity = participant.identity;
        const isLocal = participant === this.localParticipant;
        console.log(`🔴 Enhanced showCameraOffOverlay for ${identity} (local: ${isLocal})`);

        const overlay = this.getOrCreateCameraOffOverlay(participantElement, participant);
        if (!overlay) {
            console.error(`❌ Failed to create overlay for ${identity}`);
            return;
        }

        // Ensure overlay is properly styled and visible
        overlay.style.display = 'flex'; // Use flex for proper centering
        overlay.style.opacity = '1';
        overlay.style.visibility = 'visible';
        overlay.style.zIndex = '20'; // Higher z-index to ensure it's on top
        overlay.classList.remove('opacity-0', 'hidden');
        overlay.classList.add('opacity-100');

        // For local participants, properly stop and hide the video
        const videoElement = participantElement.querySelector('video');
        if (videoElement) {
            if (isLocal) {
                // For local participant, stop the video stream and hide it completely
                console.log(`📴 Stopping local video stream for ${identity}`);
                if (videoElement.srcObject) {
                    const stream = videoElement.srcObject;
                    if (stream && stream.getTracks) {
                        stream.getTracks().forEach(track => {
                            if (track.kind === 'video') {
                                track.enabled = false; // Disable the track
                            }
                        });
                    }
                }
                videoElement.style.opacity = '0';
                videoElement.style.visibility = 'hidden';
                videoElement.style.display = 'none';
            } else {
                // For remote participants, just dim the video
                videoElement.style.opacity = '0.1';
            }
        }

        console.log(`✅ Camera-off overlay shown for ${identity} (local: ${isLocal})`);
    }

    // Enhanced camera-off overlay hiding with improved reliability
    hideCameraOffOverlay(participantElement) {
        if (!participantElement) return;

        const identity = participantElement.id.replace('participant-', '');
        const isLocal = identity === this.localParticipant?.identity;
        console.log(`🟢 Enhanced hideCameraOffOverlay for ${identity} (local: ${isLocal})`);

        const overlay = participantElement.querySelector('.camera-off-overlay');
        if (overlay) {
            // Hide overlay immediately with multiple methods for reliability
            overlay.style.display = 'none';
            overlay.style.opacity = '0';
            overlay.style.visibility = 'hidden';
            overlay.classList.remove('opacity-100');
            overlay.classList.add('opacity-0', 'hidden');

            console.log(`✅ Camera-off overlay hidden for ${identity}`);
        }

        // Restore video element when overlay is hidden
        const videoElement = participantElement.querySelector('video');
        if (videoElement) {
            if (isLocal) {
                // For local participant, re-enable the video stream
                console.log(`🟢 Restoring local video stream for ${identity}`);
                if (videoElement.srcObject) {
                    const stream = videoElement.srcObject;
                    if (stream && stream.getTracks) {
                        stream.getTracks().forEach(track => {
                            if (track.kind === 'video') {
                                track.enabled = true; // Re-enable the track
                            }
                        });
                    }
                }
                videoElement.style.opacity = '1';
                videoElement.style.visibility = 'visible';
                videoElement.style.display = 'block';
            } else {
                // For remote participants, restore full opacity
                videoElement.style.opacity = '1';
            }
        }
    }

    async startMeeting() {
        if (this.isStarting) {
            console.log('⚠️ Meeting already starting, ignoring duplicate call');
            return;
        }

        if (this.isConnected) {
            console.log('⚠️ Meeting already connected');
            return;
        }

        this.isStarting = true;
        console.log('🚀 Starting meeting...');

        try {
            // Show loading overlay
            const loadingOverlay = document.getElementById('loadingOverlay');
            if (loadingOverlay) {
                loadingOverlay.style.display = 'flex';
            }

            // Update button text
            const btnText = document.getElementById('meetingBtnText');
            if (btnText) {
                btnText.textContent = 'جاري الاتصال...';
            }

            // Join the room
            await this.joinRoom();

            // Show meeting interface
            this.showMeetingInterface();

            // Set up event listeners for controls
            this.setupEventListeners();

            // Add test button for remote participant camera-off overlay
            this.addTestButton();

            // Update connection status
            this.updateConnectionStatus('connected');

            console.log('✅ Meeting started successfully');

        } catch (error) {
            console.error('❌ Failed to start meeting:', error);
            this.updateConnectionStatus('error');

            // Reset state on error
            this.isStarting = false;
            this.isConnected = false;

            // Show error message
            alert('فشل في الاتصال بالجلسة. يرجى المحاولة مرة أخرى.');
        } finally {
            this.isStarting = false;

            // Hide loading overlay
            const loadingOverlay = document.getElementById('loadingOverlay');
            if (loadingOverlay) {
                loadingOverlay.style.display = 'none';
            }
        }
    }

    async getLiveKitToken() {
        console.log('🔑 Getting LiveKit token...');

        try {
            const response = await fetch('/api/meetings/livekit/token', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': this.config.csrfToken
                },
                credentials: 'same-origin', // Include session cookies
                body: JSON.stringify({
                    room_name: this.config.roomName,
                    participant_name: this.config.participantName,
                    user_type: this.config.userType
                })
            });

            if (!response.ok) {
                throw new Error(`HTTP error! status: ${response.status}`);
            }

            const data = await response.json();

            if (!data.token) {
                throw new Error('No token received from server');
            }

            console.log('✅ LiveKit token received');
            return data.token;

        } catch (error) {
            console.error('❌ Failed to get LiveKit token:', error);
            throw error;
        }
    }

    async getTokenForExistingMeeting() {
        console.log('🔧 Getting token for existing meeting...');

        try {
            const response = await fetch(`/api/meetings/${this.config.sessionId}/token`, {
                method: 'GET',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': this.config.csrfToken
                },
                credentials: 'same-origin' // Include session cookies
            });

            if (!response.ok) {
                throw new Error(`HTTP ${response.status}: ${response.statusText}`);
            }

            const data = await response.json();
            console.log('✅ Existing meeting token response:', data);

            if (!data.success || !data.data || !data.data.access_token) {
                throw new Error('Invalid existing meeting token response');
            }

            console.log('✅ Existing meeting token validation passed');
            return data.data.access_token;

        } catch (error) {
            console.error('❌ Error getting existing meeting token:', error);
            throw error;
        }
    }

    async joinRoom() {
        console.log('🚪 Joining room...');

        // Log LiveKit version for debugging
        if (window.LiveKit) {
            console.log('🔍 LiveKit version:', window.LiveKit.version || 'Unknown');
            console.log('🔍 LiveKit object keys:', Object.keys(window.LiveKit));

            // Check if we have the correct API
            if (window.LiveKit.Room) {
                console.log('✅ LiveKit Room class available');
            } else {
                console.warn('⚠️ LiveKit Room class not found');
            }
        } else {
            console.log('⚠️ LiveKit not found in window object');
        }

        try {
            // Get LiveKit token
            const token = await this.getLiveKitToken();
            if (!token) {
                throw new Error('Failed to get LiveKit token');
            }

            // Create room instance with enhanced settings for latest SDK
            this.room = new window.LiveKit.Room({
                // Enhanced adaptive streaming for better quality management
                adaptiveStream: {
                    pixelDensity: 'screen'
                },
                // Enable dynacast for optimal bandwidth usage
                dynacast: true,
                // WebRTC configuration for better connectivity
                webRtcConfig: {
                    iceTransportPolicy: 'all'
                },
                // Publishing defaults optimized for large meetings
                publishDefaults: {
                    // Disable simulcast for better compatibility with 100+ participants
                    simulcast: false,
                    videoEncoding: {
                        maxBitrate: 1_500_000, // Reduced from 2M for better performance
                        maxFramerate: 30
                    },
                    audioEncoding: {
                        maxBitrate: 64_000 // Add audio bitrate limit
                    }
                },
                // Auto-subscribe to all tracks
                autoSubscribe: true
            });

            // Set up room event listeners
            this.setupRoomEventListeners();

            // Connect to room
            console.log('🔗 Connecting to server URL:', this.config.serverUrl);
            console.log('🎫 Using token:', token ? 'Token received' : 'No token');

            await this.room.connect(this.config.serverUrl, token, {
                autoSubscribe: true
            });

            this.localParticipant = this.room.localParticipant;
            this.isConnected = true;

            // Add local participant to UI
            this.addParticipant(this.localParticipant);

            // Add existing participants in the room
            console.log('👥 Adding existing participants...');
            console.log('🔍 Room object:', this.room);
            console.log('🔍 Room.remoteParticipants:', this.room.remoteParticipants);
            console.log('🔍 Room.localParticipant:', this.room.localParticipant);

            // Get existing participants using the latest LiveKit SDK API
            let existingParticipants = [];

            try {
                // Latest LiveKit SDK uses room.remoteParticipants as a Map
                if (this.room.remoteParticipants && this.room.remoteParticipants instanceof Map) {
                    existingParticipants = Array.from(this.room.remoteParticipants.values());
                    console.log('✅ Got existing participants via room.remoteParticipants Map:', existingParticipants.length);
                } else if (this.room.remoteParticipants && typeof this.room.remoteParticipants.values === 'function') {
                    // Fallback for older versions
                    existingParticipants = Array.from(this.room.remoteParticipants.values());
                    console.log('✅ Got existing participants via room.remoteParticipants.values():', existingParticipants.length);
                } else {
                    console.log('⚠️ room.remoteParticipants not available, trying alternative methods...');

                    // Try alternative methods if remoteParticipants is not available
                    if (this.room.participants && typeof this.room.participants.values === 'function') {
                        const allParticipants = Array.from(this.room.participants.values());
                        existingParticipants = allParticipants.filter(p => p !== this.room.localParticipant);
                        console.log('✅ Got existing participants via room.participants:', existingParticipants.length);
                    }
                }
            } catch (error) {
                console.error('❌ Error getting existing participants:', error);
            }

            // Add the found participants
            if (existingParticipants.length > 0) {
                existingParticipants.forEach(participant => {
                    console.log('👤 Adding existing participant:', participant.identity);
                    this.addParticipant(participant);

                    // CRITICAL: Attach existing video tracks for late joiners
                    setTimeout(() => {
                        this.attachExistingVideoTracks(participant);
                    }, 1000); // Increased delay to ensure element is fully created
                });
                console.log('✅ Added existing participants:', existingParticipants.length);
            } else {
                console.log('ℹ️ No existing participants found with any method');
            }

            // Remove loading states from buttons now that we're connected
            this.removeButtonLoadingStates();

            // Initialize buttons with proper states
            this.initializeButtons();

            console.log('✅ Successfully joined room');

            // Set up local media
            await this.setupLocalMedia();

            // Ensure local video track is published and subscribed
            await this.ensureLocalVideoPublished();

            // Force local video display after a delay
            setTimeout(() => {
                this.forceLocalVideoDisplay();
            }, 3000);

            // Set up periodic local video checks
            this.setupLocalVideoChecks();

            // Update participants list after joining
            setTimeout(() => {
                console.log('🔄 Initial participants list update after joining');
                this.updateParticipantsList();
                console.log('✅ Initial participants list update completed');
            }, 1000);

        } catch (error) {
            console.error('❌ Failed to join room:', error);
            throw error;
        }
    }

    setupLocalVideoChecks() {
        console.log('🔄 Setting up periodic local video checks...');

        // Check for local video every 3 seconds for the first 30 seconds
        let checkCount = 0;
        const maxChecks = 10;

        const checkInterval = setInterval(() => {
            checkCount++;

            // Skip checks if camera is disabled
            if (this.isLocalCameraEnabled === false) {
                console.log('📴 Skipping periodic local video check - camera disabled');
                checkCount++;
                return;
            }

            // Check if local video is displayed
            const localParticipantElement = document.getElementById(`participant-${this.localParticipant.identity}`);
            if (localParticipantElement) {
                const video = localParticipantElement.querySelector('video');
                const placeholder = localParticipantElement.querySelector('.absolute.inset-0');

                // Only try to attach video if camera should be on and track is active
                if (placeholder && placeholder.style.display !== 'none' && video && video.style.display === 'none') {
                    console.log('🔄 Local video not displayed, attempting to attach...');
                    this.checkAndAttachLocalVideo(); // This now respects camera state
                } else if (video && video.style.display !== 'none' && this.hasActiveVideoTrack(this.localParticipant)) {
                    console.log('✅ Local video is displayed and track is active, stopping checks');
                    clearInterval(checkInterval);
                }
            }

            // Stop checking after max attempts
            if (checkCount >= maxChecks) {
                console.log('⏰ Stopping local video checks after max attempts');
                clearInterval(checkInterval);
            }
        }, 3000);
    }

    setupRoomEventListeners() {
        console.log('🎧 Setting up room event listeners...');

        if (!this.room) {
            console.error('❌ Room not available for event listeners');
            return;
        }

        // Room connection state changes
        this.room.on(window.LiveKit.RoomEvent.ConnectionStateChanged, (state) => {
            console.log('🔗 Connection state changed:', state);
            this.updateConnectionStatus(state);

            // Update participant count and timer when connection state changes
            if (state === 'connected') {
                setTimeout(() => {
                    this.updateParticipantCount();

                    // Ensure timer is started for teachers if not already started
                    if (!this.timerInterval) {
                        console.log('🔧 Starting timer from connection state change');
                        this.startMeetingTimer();
                    }
                }, 1000); // Small delay to ensure everything is ready
            }
        });

        // Participant connected
        this.room.on(window.LiveKit.RoomEvent.ParticipantConnected, (participant) => {
            console.log('👤 Participant connected:', participant.identity);
            // Don't add local participant here - it's added in joinRoom
            if (participant !== this.localParticipant) {
                this.addParticipant(participant);

                // Set up event listeners for ALL remote participants immediately
                console.log(`🔍 Setting up event listeners for remote participant ${participant.identity} on connect`);
                this.setupRemoteParticipantEventListeners(participant);

                // Update participants list if sidebar is showing participants
                if (this.currentSidebarType === 'participants') {
                    this.updateParticipantsList();
                }

                // Always update participants list when someone joins
                setTimeout(() => {
                    this.updateParticipantsList();
                }, 500);
            }
        });

        // Participant disconnected
        this.room.on(window.LiveKit.RoomEvent.ParticipantDisconnected, (participant) => {
            console.log('👤 Participant disconnected:', participant.identity);
            this.removeParticipant(participant.identity); // Pass participant ID, not object
            // Update participants list if sidebar is showing participants
            if (this.currentSidebarType === 'participants') {
                this.updateParticipantsList();
            }

            // Always update participants list when someone leaves
            setTimeout(() => {
                this.updateParticipantsList();
            }, 500);
        });

        // Track subscribed (both audio and video)
        this.room.on(window.LiveKit.RoomEvent.TrackSubscribed, (track, publication, participant) => {
            console.log('📹 Track subscribed:', track.kind, 'from', participant.identity);
            console.log('🔍 Track details:', {
                trackSid: track.sid,
                trackKind: track.kind,
                trackSource: track.source,
                participantIdentity: participant.identity,
                isLocal: participant === this.localParticipant
            });
            this.handleTrackSubscribed(track, publication, participant);
        });

        // Track unsubscribed
        this.room.on(window.LiveKit.RoomEvent.TrackUnsubscribed, (track, publication, participant) => {
            console.log('📹 Track unsubscribed:', track.kind, 'from', participant.identity);
            console.log('🔍 Track details:', {
                trackSid: track.sid,
                trackKind: track.kind,
                trackSource: track.source,
                participantIdentity: participant.identity,
                isLocal: participant === this.localParticipant
            });
            this.handleTrackUnsubscribed(track, publication, participant);
        });

        // Enhanced track muted handling with improved synchronization
        this.room.on(window.LiveKit.RoomEvent.TrackMuted, (publication, participant) => {
            if (!participant || !publication) {
                console.log('🔇 Track muted but missing participant or publication');
                return;
            }
            console.log('🔇 Enhanced track muted:', publication.kind, 'from', participant.identity);

            if (publication.kind === 'video') {
                console.log(`📴 Video track muted for ${participant.identity} - immediate camera off`);
                // Immediate state update
                this.syncParticipantVideoState(participant, false);
                // Backup state update
                setTimeout(() => {
                    this.updateParticipantCameraStatus(participant);
                }, 100);
            }
        });

        // Enhanced track unmuted handling with improved synchronization
        this.room.on(window.LiveKit.RoomEvent.TrackUnmuted, (publication, participant) => {
            if (!participant || !publication) {
                console.log('🔊 Track unmuted but missing participant or publication');
                return;
            }
            console.log('🔊 Enhanced track unmuted:', publication.kind, 'from', participant.identity);

            if (publication.kind === 'video') {
                console.log(`🟢 Video track unmuted for ${participant.identity} - immediate camera on`);
                // Immediate state update
                this.syncParticipantVideoState(participant, true);
                // Backup state update after brief delay for track availability
                setTimeout(() => {
                    this.updateParticipantCameraStatus(participant);
                }, 200);
            }
        });

        // Data received
        this.room.on(window.LiveKit.RoomEvent.DataReceived, (payload, participant) => {
            if (!participant) {
                console.log('📨 Data received but no participant provided');
                return;
            }
            console.log('📨 Data received from:', participant.identity);

            // Check if payload is empty or invalid
            if (!payload || payload.length === 0) {
                console.log('📨 Empty payload received, skipping');
                return;
            }

            try {
                const data = JSON.parse(payload);
                if (data.type === 'camera_state') {
                    this.handleCameraStateData(data);
                } else if (data.type === 'request_camera_states') {
                    // Respond to camera state request by broadcasting our current state
                    if (this.localParticipant) {
                        const hasVideo = this.hasActiveVideoTrack(this.localParticipant);
                        this.broadcastCameraState(this.localParticipant.identity, hasVideo);
                        console.log(`📡 Responded to camera state request: ${hasVideo ? 'ON' : 'OFF'}`);
                    }
                } else {
                    this.handleDataReceived(data, participant);
                }
            } catch (error) {
                console.error('❌ Error parsing data payload:', error);
                // Don't call handleDataReceived again if parsing failed
            }
        });

        console.log('✅ Room event listeners set up');

        // Set up comprehensive remote participant monitoring
        this.setupRemoteParticipantMonitoring();

        // Request camera states from all participants after 5 seconds
        setTimeout(() => {
            console.log('📡 Requesting camera states from all participants');
            this.requestCameraStatesFromAllParticipants();
        }, 5000);

        // Force refresh all remote participants' camera status after 3 seconds
        setTimeout(() => {
            console.log('🔄 Force refreshing all remote participants camera status');
            this.forceRefreshAllRemoteCameraStatus();
        }, 3000);

        // Set up periodic overlay cleanup to fix any stuck overlays
        setInterval(() => {
            this.cleanupStuckOverlays();
        }, 5000); // Check every 5 seconds
    }



    async setupLocalMedia() {
        console.log('🎤 Setting up local media...');

        try {
            // Request microphone and camera permissions
            const stream = await navigator.mediaDevices.getUserMedia({
                audio: true,
                video: true
            });

            console.log('✅ Media permissions granted');

            // Enable microphone and camera by default
            await this.localParticipant.setMicrophoneEnabled(true);
            await this.localParticipant.setCameraEnabled(true);

            // Update local state
            this.isAudioEnabled = true;
            this.isVideoEnabled = true;

            // Update UI
            this.updateControlButtons();

            // Set up local track publishing listeners
            this.setupLocalTrackListeners();

            // Manually check and attach local video after a delay
            setTimeout(() => {
                this.checkAndAttachLocalVideo();
            }, 2000);

            // Enhanced periodic check that respects camera state
            let localVideoCheckCount = 0;
            const localVideoInterval = setInterval(() => {
                if (localVideoCheckCount >= 10) {
                    clearInterval(localVideoInterval);
                    return;
                }

                // Skip checks if camera is disabled
                if (this.isLocalCameraEnabled === false) {
                    console.log('📴 Skipping local video check - camera disabled');
                    localVideoCheckCount++;
                    return;
                }

                const localElement = document.getElementById(`participant-${this.localParticipant.identity}`);
                if (localElement) {
                    const video = localElement.querySelector('video');
                    if (video && video.srcObject && video.videoWidth > 0 && this.hasActiveVideoTrack(this.localParticipant)) {
                        console.log('✅ Local video confirmed displaying and camera is active');
                        clearInterval(localVideoInterval);
                        return;
                    }
                }

                console.log(`🔄 Enhanced local video check (attempt ${localVideoCheckCount + 1})`);
                this.checkAndAttachLocalVideo(); // This now respects camera state
                this.updateParticipantCameraStatus(this.localParticipant);
                localVideoCheckCount++;
            }, 1000);

            console.log('✅ Local media setup complete');

        } catch (error) {
            console.error('❌ Failed to setup local media:', error);

            if (error.name === 'NotAllowedError') {
                this.showNotification('يرجى السماح بالوصول للميكروفون والكاميرا', 'error');
            } else {
                this.showNotification('خطأ في إعداد الوسائط المحلية', 'error');
            }
        }
    }

    setupLocalTrackListeners() {
        console.log('🎥 Setting up local track listeners...');

        // Listen for local track publishing
        this.localParticipant.on(window.LiveKit.ParticipantEvent.TrackPublished, (track, publication) => {
            console.log('📤 Local track published:', track.kind, track.source);

            // Subscribe to our own track to display it
            publication.setSubscribed(true);

            // If it's a video track, try to attach it immediately
            if (track.kind === 'video') {
                setTimeout(() => {
                    this.attachLocalVideoTrack(track);
                }, 500);
            }
        });

        // Enhanced local track muted/unmuted handling for proper overlay management
        this.localParticipant.on(window.LiveKit.ParticipantEvent.TrackMuted, (publication) => {
            try {
                if (publication.kind === 'video') {
                    console.log('📴 LOCAL video muted - showing camera-off overlay');
                    // Immediately sync video state to OFF
                    this.syncParticipantVideoState(this.localParticipant, false);
                    // Mark local camera as disabled to prevent forced video display
                    this.isLocalCameraEnabled = false;
                    // Additional update after brief delay
                    setTimeout(() => {
                        this.updateParticipantCameraStatus(this.localParticipant);
                    }, 100);
                }
            } catch (e) { console.warn('⚠️ Error handling local TrackMuted', e); }
        });

        this.localParticipant.on(window.LiveKit.ParticipantEvent.TrackUnmuted, (publication) => {
            try {
                if (publication.kind === 'video') {
                    console.log('🟢 LOCAL video unmuted - hiding camera-off overlay');
                    // Mark local camera as enabled
                    this.isLocalCameraEnabled = true;
                    // Immediately sync video state to ON
                    this.syncParticipantVideoState(this.localParticipant, true);
                    // Additional update after brief delay
                    setTimeout(() => {
                        this.updateParticipantCameraStatus(this.localParticipant);
                        // Try to attach video track if it's not showing
                        this.checkAndAttachLocalVideo();
                    }, 200);
                }
            } catch (e) { console.warn('⚠️ Error handling local TrackUnmuted', e); }
        });

        // Listen for local track subscription
        this.localParticipant.on(window.LiveKit.ParticipantEvent.TrackSubscribed, (track, publication) => {
            console.log('📹 Local track subscribed:', track.kind, track.source);

            if (track.kind === 'video') {
                // Handle local video track
                this.attachLocalVideoTrack(track);
            }
        });

        console.log('✅ Local track listeners set up');
    }

    // Ensure local video track is published and subscribed
    async ensureLocalVideoPublished() {
        console.log('🎥 Ensuring local video track is published...');

        if (!this.localParticipant) {
            console.error('❌ No local participant available');
            return;
        }

        try {
            // Check if camera is enabled
            if (!this.isVideoEnabled) {
                console.log('📹 Enabling camera...');
                await this.localParticipant.setCameraEnabled(true);
                this.isVideoEnabled = true;
            }

            // Wait a bit for the track to be published
            await new Promise(resolve => setTimeout(resolve, 1000));

            // Check if we have video tracks
            const videoTracks = Array.from(this.localParticipant.videoTracks.values());
            console.log('📹 Local video tracks found:', videoTracks.length);

            if (videoTracks.length > 0) {
                // Find camera track and ensure it's subscribed
                const cameraTrack = videoTracks.find(pub =>
                    pub.source === (window.LiveKit?.Track?.Source?.Camera || 'camera')
                );

                if (cameraTrack && cameraTrack.setSubscribed) {
                    console.log('✅ Camera track found, ensuring subscription...');
                    cameraTrack.setSubscribed(true);

                    // Try to attach to local participant element
                    setTimeout(() => {
                        this.checkAndAttachLocalVideo();
                    }, 500);
                } else if (cameraTrack) {
                    console.log('⚠️ Camera track found but setSubscribed not available, trying to attach directly...');
                    setTimeout(() => {
                        this.checkAndAttachLocalVideo();
                    }, 500);
                } else {
                    console.log('⚠️ No camera track found, trying any video track...');
                    const anyVideoTrack = videoTracks.find(pub => pub.track);
                    if (anyVideoTrack && anyVideoTrack.setSubscribed) {
                        anyVideoTrack.setSubscribed(true);
                        setTimeout(() => {
                            this.checkAndAttachLocalVideo();
                        }, 500);
                    } else if (anyVideoTrack) {
                        console.log('⚠️ Video track found but setSubscribed not available, trying to attach directly...');
                        setTimeout(() => {
                            this.checkAndAttachLocalVideo();
                        }, 500);
                    }
                }
            } else {
                console.log('⚠️ No video tracks found, camera might not be enabled');
            }

        } catch (error) {
            console.error('❌ Error ensuring local video published:', error);
        }
    }

    attachLocalVideoTrack(track) {
        console.log('🎥 Attaching local video track...');

        const participantElement = document.getElementById(`participant-${this.localParticipant.identity}`);
        if (participantElement) {
            const video = participantElement.querySelector('video');
            if (video) {
                // Detach any existing track
                if (video.srcObject) {
                    video.srcObject = null;
                }

                // Attach the track
                track.attach(video);

                // Show video with fade-in effect (using updated structure)
                video.classList.remove('opacity-0');
                video.classList.add('opacity-100');

                // Hide camera-off overlay when video is enabled
                this.hideCameraOffOverlay(participantElement);

                // Hide placeholder with fade-out effect (target the right placeholder)
                const placeholder = participantElement.querySelector('.bg-gradient-to-br');
                if (placeholder) {
                    placeholder.classList.remove('opacity-100');
                    placeholder.classList.add('opacity-0');
                    setTimeout(() => {
                        placeholder.style.display = 'none';
                    }, 300);
                }

                // Show status overlay with participant name
                const statusOverlay = participantElement.querySelector('.absolute.bottom-2');
                if (statusOverlay) {
                    statusOverlay.classList.remove('opacity-0');
                    statusOverlay.classList.add('opacity-100');
                }

                // Update camera status for local participant
                setTimeout(() => {
                    if (this.localParticipant) {
                        this.updateParticipantCameraStatus(this.localParticipant);
                    }
                }, 200);

                console.log('✅ Local video track attached successfully');
            } else {
                console.error('❌ Local video element not found');
            }
        } else {
            console.error('❌ Local participant element not found');
        }
    }

    showMeetingInterface() {
        console.log('🖥️ Showing meeting interface...');

        // Show meeting container
        const meetingContainer = document.getElementById('meetingContainer');
        if (meetingContainer) {
            meetingContainer.style.display = 'block';
        }

        // Show meeting interface
        const meetingInterface = document.getElementById('meetingInterface');
        if (meetingInterface) {
            meetingInterface.style.display = 'flex';
        }

        // Initialize buttons immediately when interface is shown
        this.initializeButtons();

        // Ensure sidebar is hidden by default
        setTimeout(() => {
            this.initializeSidebar();
        }, 100);

        // Ensure video is centered initially
        setTimeout(() => {
            this.centerVideoContent();
        }, 500);

        // Ensure DOM elements are ready before starting timer/count
        setTimeout(() => {
            // Start meeting timer
            this.startMeetingTimer();

            // Update participant count
            this.updateParticipantCount();

            // Update participants list if it exists
            if (this.elements.participantsList) {
                this.updateParticipantsList();
            }
        }, 100);

        // Update button text
        const btnText = document.getElementById('meetingBtnText');
        if (btnText) {
            btnText.textContent = 'مغادرة الجلسة';
        }

        // Update start button to leave button
        const startBtn = document.getElementById('startMeetingBtn');
        if (startBtn) {
            startBtn.id = 'leaveMeetingBtn';
            startBtn.className = 'bg-red-600 hover:bg-red-700 text-white px-6 py-3 rounded-lg font-semibold transition-colors duration-200 flex items-center gap-2 min-w-[200px] justify-center';
            startBtn.innerHTML = `
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" 
                          d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1">
                    </path>
                </svg>
                <span>مغادرة الجلسة</span>
            `;

            // Update click handler for leave button
            startBtn.onclick = () => {
                console.log('🚪 Leave button clicked from main button');
                this.leaveMeeting();
            };
        }

        console.log('✅ Meeting interface shown');
    }

    initializeSidebar() {
        console.log('📋 Initializing sidebar...');

        // Ensure sidebar starts hidden (off-screen to the left)
        if (this.elements.meetingSidebar) {
            console.log('📋 Hiding sidebar on initialization...');
            this.elements.meetingSidebar.classList.add('-translate-x-full');
            this.currentSidebarType = null;

            // Ensure sidebar doesn't take space when hidden
            this.elements.meetingSidebar.style.position = 'absolute';
            this.elements.meetingSidebar.style.left = '0';
            this.elements.meetingSidebar.style.top = '0';
        } else {
            console.warn('⚠️ Sidebar element not found during initialization');
        }

        // Ensure video area starts with full flex and centered
        const videoArea = document.getElementById('videoArea');
        if (videoArea) {
            videoArea.classList.add('flex-1');
            videoArea.classList.remove('w-[calc(100%-24rem)]');
        }

        // Ensure the video grid centers its content
        const videoGrid = document.getElementById('videoGrid');
        if (videoGrid) {
            videoGrid.style.alignContent = 'center';
            videoGrid.style.justifyContent = 'center';
        }

        // Reset all button states to inactive
        this.updateSidebarButtonStates(null);

        console.log('✅ Sidebar initialized');
    }

    centerVideoContent() {
        console.log('🎯 Centering video content...');

        const videoGrid = document.getElementById('videoGrid');
        if (videoGrid) {
            // Ensure the video grid centers its content
            videoGrid.style.alignContent = 'center';
            videoGrid.style.justifyContent = 'center';

            // If there's only one participant, center it
            const participants = videoGrid.children;
            if (participants.length === 1) {
                participants[0].style.margin = 'auto';
            }
        }

        console.log('✅ Video content centered');
    }

    cacheElements() {
        // This method is not needed as we cache elements in setupEventListeners
        console.log('🔍 Elements already cached in setupEventListeners');
    }

    setupEventListeners() {
        console.log('🎧 Setting up event listeners...');

        // Cache control elements
        this.elements = {
            micBtn: document.getElementById('micBtn'),
            cameraBtn: document.getElementById('cameraBtn'),
            screenShareBtn: document.getElementById('screenShareBtn'),
            handRaiseBtn: document.getElementById('handRaiseBtn'),
            chatToggleBtn: document.getElementById('chatToggleBtn'),
            participantsToggleBtn: document.getElementById('participantsToggleBtn'),
            recordBtn: document.getElementById('recordBtn'),
            settingsBtn: document.getElementById('settingsBtn'),
            leaveBtn: document.getElementById('leaveBtn'),
            // Sidebar elements
            meetingSidebar: document.getElementById('meetingSidebar'),
            closeSidebarBtn: document.getElementById('closeSidebarBtn'),
            sidebarTitle: document.getElementById('sidebarTitle'),
            chatPanel: document.getElementById('chatPanel'),
            participantsPanel: document.getElementById('participantsPanel'),
            settingsPanel: document.getElementById('settingsPanel'),
            chatMessages: document.getElementById('chatMessages'),
            chatInput: document.getElementById('chatInput'),
            sendChatBtn: document.getElementById('sendChatBtn'),
            participantsList: document.getElementById('participantsList'),
            // Settings elements
            cameraSelect: document.getElementById('cameraSelect'),
            microphoneSelect: document.getElementById('microphoneSelect'),
            videoQualitySelect: document.getElementById('videoQualitySelect'),
            muteonJoinCheckbox: document.getElementById('muteonJoinCheckbox')
        };

        // Log which elements were found
        console.log('🔍 Found elements:', Object.keys(this.elements).filter(key => this.elements[key]));

        // Microphone toggle
        if (this.elements.micBtn) {
            this.elements.micBtn.addEventListener('click', (e) => {
                e.preventDefault();
                e.stopPropagation();
                console.log('🎤 Microphone button clicked');
                this.toggleMicrophone();
            });
            console.log('✅ Microphone button listener added');
        } else {
            console.warn('⚠️ Microphone button not found');
        }

        // Camera toggle
        if (this.elements.cameraBtn) {
            this.elements.cameraBtn.addEventListener('click', (e) => {
                e.preventDefault();
                e.stopPropagation();
                console.log('📹 Camera button clicked');
                this.toggleCamera();
            });
            console.log('✅ Camera button listener added');
        } else {
            console.warn('⚠️ Camera button not found');
        }

        // Screen share toggle
        if (this.elements.screenShareBtn) {
            this.elements.screenShareBtn.addEventListener('click', (e) => {
                e.preventDefault();
                e.stopPropagation();
                console.log('🖥️ Screen share button clicked');
                this.toggleScreenShare();
            });
            console.log('✅ Screen share button listener added');
        } else {
            console.warn('⚠️ Screen share button not found');
        }

        // Hand raise toggle
        if (this.elements.handRaiseBtn) {
            this.elements.handRaiseBtn.addEventListener('click', (e) => {
                e.preventDefault();
                e.stopPropagation();
                console.log('✋ Hand raise button clicked');
                this.toggleHandRaise();
            });
            console.log('✅ Hand raise button listener added');
        } else {
            console.warn('⚠️ Hand raise button not found');
        }



        // Recording toggle (teachers only)
        if (this.elements.recordBtn) {
            this.elements.recordBtn.addEventListener('click', (e) => {
                e.preventDefault();
                e.stopPropagation();
                console.log('🔴 Record button clicked');
                this.toggleRecording();
            });
            console.log('✅ Record button listener added');
        } else {
            console.warn('⚠️ Record button not found (may be student view)');
        }

        // Settings
        if (this.elements.settingsBtn) {
            this.elements.settingsBtn.addEventListener('click', (e) => {
                e.preventDefault();
                e.stopPropagation();
                console.log('⚙️ Settings button clicked');
                this.openSidebar('settings');
            });
            console.log('✅ Settings button listener added');
        } else {
            console.warn('⚠️ Settings button not found');
        }

        // Chat toggle
        if (this.elements.chatToggleBtn) {
            this.elements.chatToggleBtn.addEventListener('click', (e) => {
                e.preventDefault();
                e.stopPropagation();
                console.log('💬 Chat button clicked');
                this.toggleSidebar('chat');
            });
            console.log('✅ Chat button listener added');
        } else {
            console.warn('⚠️ Chat button not found');
        }

        // Participants toggle
        if (this.elements.participantsToggleBtn) {
            this.elements.participantsToggleBtn.addEventListener('click', (e) => {
                e.preventDefault();
                e.stopPropagation();
                console.log('👥 Participants button clicked');
                this.toggleSidebar('participants');
            });
            console.log('✅ Participants button listener added');
        } else {
            console.warn('⚠️ Participants button not found');
        }

        // Sidebar close button
        if (this.elements.closeSidebarBtn) {
            this.elements.closeSidebarBtn.addEventListener('click', (e) => {
                e.preventDefault();
                e.stopPropagation();
                console.log('❌ Close sidebar button clicked');
                this.closeSidebar();
            });
            console.log('✅ Close sidebar button listener added');
        } else {
            console.warn('⚠️ Close sidebar button not found');
        }

        // Chat input and send
        if (this.elements.chatInput) {
            this.elements.chatInput.addEventListener('keypress', (e) => {
                if (e.key === 'Enter') {
                    e.preventDefault();
                    this.sendChatMessage();
                }
            });
        }

        if (this.elements.sendChatBtn) {
            this.elements.sendChatBtn.addEventListener('click', (e) => {
                e.preventDefault();
                e.stopPropagation();
                this.sendChatMessage();
            });
        }

        // Leave meeting - Only bind to control bar button, not the main button
        if (this.elements.leaveBtn) {
            this.elements.leaveBtn.addEventListener('click', (e) => {
                e.preventDefault();
                e.stopPropagation();
                console.log('🚪 Leave button clicked from control bar');
                this.leaveMeeting();
            });
            console.log('✅ Leave button listener added');
        } else {
            console.warn('⚠️ Leave button not found');
        }

        // Window resize handler for responsive sidebar
        window.addEventListener('resize', () => {
            // Readjust video area if sidebar is open
            if (this.currentSidebarType) {
                this.adjustVideoAreaForSidebar(true);
            }
        });

        // Exit focus button listener (set up after DOM is ready)
        this.setupExitFocusButton();

        // Set up keyboard shortcuts
        this.setupKeyboardShortcuts();

        // Set up fullscreen button
        this.setupFullscreenButton();

        // Add test button for debugging participants list (only in development)
        if (window.location.hostname === 'localhost' || window.location.hostname.includes('test')) {
            this.addTestButton();
        }

        console.log('✅ Event listeners set up');
    }

    addTestButton() {
        // Add a test button to manually trigger participants list update
        const testButton = document.createElement('button');
        testButton.id = 'testParticipantsBtn';
        testButton.className = 'fixed top-4 right-4 z-50 bg-red-600 text-white px-4 py-2 rounded-lg shadow-lg';
        testButton.textContent = 'Test Participants List';
        testButton.onclick = () => {
            console.log('🧪 Test button clicked - manually updating participants list');
            this.updateParticipantsList();
        };
        document.body.appendChild(testButton);

        // Add a second button to force refresh from participants Map
        const refreshButton = document.createElement('button');
        refreshButton.id = 'refreshParticipantsBtn';
        refreshButton.className = 'fixed top-16 right-4 z-50 bg-blue-600 text-white px-4 py-2 rounded-lg shadow-lg';
        refreshButton.textContent = 'Refresh from Map';
        refreshButton.onclick = () => {
            console.log('🔄 Refresh button clicked - forcing participants list from Map');
            this.forceUpdateParticipantsListFromMap();
        };
        document.body.appendChild(refreshButton);

        console.log('🧪 Test buttons added for debugging participants list');

        // Add a third button to add a test participant to the Map
        const addTestButton = document.createElement('button');
        addTestButton.id = 'addTestParticipantBtn';
        addTestButton.className = 'fixed top-28 right-4 z-50 bg-green-600 text-white px-4 py-2 rounded-lg shadow-lg';
        addTestButton.textContent = 'Add Test Participant';
        addTestButton.onclick = () => {
            console.log('➕ Add test participant button clicked');
            this.addTestParticipantToMap();
        };
        document.body.appendChild(addTestButton);

        // Add a fourth button to refresh camera status for all participants
        const refreshCameraButton = document.createElement('button');
        refreshCameraButton.id = 'refreshCameraStatusBtn';
        refreshCameraButton.className = 'fixed top-40 right-4 z-50 bg-purple-600 text-white px-4 py-2 rounded-lg shadow-lg';
        refreshCameraButton.textContent = 'Refresh Camera Status';
        refreshCameraButton.onclick = () => {
            console.log('🔄 Refresh camera status button clicked');
            this.refreshAllCameraStatus();
        };
        document.body.appendChild(refreshCameraButton);

        // Add a fifth button to toggle camera off overlay for testing
        const toggleOverlayButton = document.createElement('button');
        toggleOverlayButton.id = 'toggleOverlayBtn';
        toggleOverlayButton.className = 'fixed top-52 right-4 z-50 bg-orange-600 text-white px-4 py-2 rounded-lg shadow-lg';
        toggleOverlayButton.textContent = 'Toggle Overlay';
        toggleOverlayButton.onclick = () => {
            console.log('🔄 Toggle overlay button clicked');
            this.toggleCameraOffOverlayForTesting();
        };
        document.body.appendChild(toggleOverlayButton);

        // Add a sixth button to force refresh all camera statuses
        const forceRefreshButton = document.createElement('button');
        forceRefreshButton.id = 'forceRefreshBtn';
        forceRefreshButton.className = 'fixed top-64 right-4 z-50 bg-pink-600 text-white px-4 py-2 rounded-lg shadow-lg';
        forceRefreshButton.textContent = 'Force Refresh All';
        forceRefreshButton.onclick = () => {
            console.log('🔄 Force refresh all button clicked');
            this.forceRefreshAllCameraStatuses();
        };
        document.body.appendChild(forceRefreshButton);

        // Add a seventh button to check video elements
        const checkVideoButton = document.createElement('button');
        checkVideoButton.id = 'checkVideoBtn';
        checkVideoButton.className = 'fixed top-76 right-4 z-50 bg-indigo-600 text-white px-4 py-2 rounded-lg shadow-lg';
        checkVideoButton.textContent = 'Check Videos';
        checkVideoButton.onclick = () => {
            console.log('🔍 Check videos button clicked');
            this.checkAllVideoElements();
        };
        document.body.appendChild(checkVideoButton);

        // Add an eighth button to refresh remote camera status
        const refreshRemoteButton = document.createElement('button');
        refreshRemoteButton.id = 'refreshRemoteBtn';
        refreshRemoteButton.className = 'fixed top-88 right-4 z-50 bg-teal-600 text-white px-4 py-2 rounded-lg shadow-lg';
        refreshRemoteButton.textContent = 'Refresh Remote';
        refreshRemoteButton.onclick = () => {
            console.log('🔄 Refresh remote button clicked');
            this.forceRefreshAllRemoteCameraStatus();
        };
        document.body.appendChild(refreshRemoteButton);

        // Add a ninth button to force hide all overlays
        const hideAllOverlaysButton = document.createElement('button');
        hideAllOverlaysButton.id = 'hideAllOverlaysBtn';
        hideAllOverlaysButton.className = 'fixed top-100 right-4 z-50 bg-yellow-600 text-white px-4 py-2 rounded-lg shadow-lg';
        hideAllOverlaysButton.textContent = 'Hide All Overlays';
        hideAllOverlaysButton.onclick = () => {
            console.log('🔄 Hide all overlays button clicked');
            this.forceHideAllCameraOffOverlays();
        };
        document.body.appendChild(hideAllOverlaysButton);

        // Add a tenth button to force attach video tracks for late joiners
        const attachTracksButton = document.createElement('button');
        attachTracksButton.id = 'attachTracksBtn';
        attachTracksButton.className = 'fixed top-112 right-4 z-50 bg-lime-600 text-white px-4 py-2 rounded-lg shadow-lg';
        attachTracksButton.textContent = 'Attach Tracks';
        attachTracksButton.onclick = () => {
            console.log('🔄 Attach tracks button clicked');
            this.forceAttachAllVideoTracks();
        };
        document.body.appendChild(attachTracksButton);
    }

    addTestParticipantToMap() {
        // Create a mock participant for testing
        const testParticipant = {
            identity: 'Test User ' + Date.now(),
            sid: 'test-sid-' + Date.now(),
            name: 'Test User',
            metadata: JSON.stringify({ userType: 'student' }),
            audioTracks: new Map(),
            videoTracks: new Map(),
            isLocal: false
        };

        // Add to participants Map
        this.participants.set(testParticipant.identity, testParticipant);
        console.log('✅ Added test participant to Map:', testParticipant.identity);
        console.log('📊 Total participants in Map now:', this.participants.size);

        // Update the participants list
        this.updateParticipantsList();
    }

    refreshAllCameraStatus() {
        console.log('🔄 Refreshing camera status for all participants');

        // Refresh local participant
        if (this.localParticipant) {
            console.log('🔄 Refreshing camera status for local participant');
            this.updateParticipantCameraStatus(this.localParticipant);
        }

        // Refresh remote participants
        if (this.room && this.room.remoteParticipants && this.room.remoteParticipants instanceof Map) {
            const remoteParticipants = Array.from(this.room.remoteParticipants.values());
            console.log(`🔄 Refreshing camera status for ${remoteParticipants.length} remote participants`);
            remoteParticipants.forEach(participant => {
                this.updateParticipantCameraStatus(participant);
            });
        }

        // Also refresh from our internal participants Map
        if (this.participants.size > 0) {
            console.log(`🔄 Refreshing camera status for ${this.participants.size} participants from internal Map`);
            this.participants.forEach(participant => {
                this.updateParticipantCameraStatus(participant);
            });
        }

        console.log('✅ Camera status refresh completed');
    }

    forceRefreshAllCameraStatuses() {
        console.log('🔄 Force refreshing all camera statuses...');

        // Get all participant elements
        const participantElements = document.querySelectorAll('[id^="participant-"]');
        console.log(`🔄 Found ${participantElements.length} participant elements`);

        participantElements.forEach(element => {
            const participantId = element.dataset.participantId;
            console.log(`🔄 Force refreshing camera status for participant: ${participantId}`);

            // Find the participant object
            let participant = null;

            // Check if it's the local participant
            if (this.localParticipant && this.localParticipant.identity === participantId) {
                participant = this.localParticipant;
            }
            // Check remote participants
            else if (this.room && this.room.remoteParticipants && this.room.remoteParticipants instanceof Map) {
                participant = this.room.remoteParticipants.get(participantId);
            }

            if (participant) {
                console.log(`🔄 Found participant object for ${participantId}, updating camera status`);
                this.updateParticipantCameraStatus(participant);
            } else {
                console.log(`⚠️ Could not find participant object for ${participantId}`);
            }
        });

        console.log('✅ Force refresh completed');
    }

    checkAllVideoElements() {
        console.log('🔍 Checking all video elements...');

        const participantElements = document.querySelectorAll('[id^="participant-"]');
        console.log(`🔍 Found ${participantElements.length} participant elements`);

        participantElements.forEach(element => {
            const participantId = element.dataset.participantId;
            const video = element.querySelector('video');
            const overlay = element.querySelector('.camera-off-overlay');

            console.log(`🔍 Participant ${participantId}:`);
            console.log(`  - Video element: ${!!video}`);
            if (video) {
                console.log(`  - Video srcObject: ${!!video.srcObject}`);
                console.log(`  - Video videoWidth: ${video.videoWidth}`);
                console.log(`  - Video videoHeight: ${video.videoHeight}`);
                console.log(`  - Video opacity: ${video.classList.contains('opacity-100') ? 'visible' : 'hidden'}`);
            }
            console.log(`  - Camera off overlay: ${!!overlay}`);
            if (overlay) {
                console.log(`  - Overlay opacity: ${overlay.classList.contains('opacity-100') ? 'visible' : 'hidden'}`);
            }
        });

        console.log('✅ Video elements check completed');
    }

    // Handle remote participant camera state changes in real-time
    handleRemoteCameraStateChange(participant, hasVideo) {
        console.log(`🔄 Remote camera state change for ${participant.identity}: ${hasVideo ? 'ON' : 'OFF'}`);

        const participantElement = document.getElementById(`participant-${participant.identity}`);
        if (!participantElement) {
            console.log(`⚠️ No participant element found for ${participant.identity}`);
            return;
        }

        if (hasVideo) {
            // Camera is on - hide overlay and show video
            console.log(`🟢 Hiding camera-off overlay for ${participant.identity}`);
            this.hideCameraOffOverlay(participantElement);

            // Ensure video is visible
            const video = participantElement.querySelector('video');
            if (video) {
                video.style.display = 'block';
                video.classList.remove('opacity-0');
                video.classList.add('opacity-100');
                console.log(`🟢 Video element made visible for ${participant.identity}`);
            }

            // Force a re-check after a short delay to ensure overlay is hidden
            setTimeout(() => {
                const overlay = participantElement.querySelector('.camera-off-overlay');
                if (overlay && overlay.style.display !== 'none') {
                    console.log(`🔄 Force hiding overlay for ${participant.identity}`);
                    overlay.style.display = 'none';
                    overlay.classList.remove('opacity-100');
                    overlay.classList.add('opacity-0');
                }
            }, 100);
        } else {
            // Camera is off - show overlay
            console.log(`🔴 Showing camera-off overlay for ${participant.identity}`);
            this.showCameraOffOverlay(participantElement, participant);
        }

        // Update camera status indicator
        const cameraStatus = participantElement.querySelector(`#camera-status-${participant.identity}`);
        if (cameraStatus) {
            if (hasVideo) {
                cameraStatus.className = 'w-6 h-6 bg-green-600 rounded-full flex items-center justify-center';
                const icon = cameraStatus.querySelector('i');
                if (icon) {
                    icon.className = 'fas fa-video text-white text-xs';
                }
            } else {
                cameraStatus.className = 'w-6 h-6 bg-red-600 rounded-full flex items-center justify-center';
                const icon = cameraStatus.querySelector('i');
                if (icon) {
                    icon.className = 'fas fa-video-slash text-white text-xs';
                }
            }
        }

        console.log(`✅ Remote camera state updated for ${participant.identity}: ${hasVideo ? 'ON' : 'OFF'}`);
    }

    // Force refresh all remote participants' camera status
    forceRefreshAllRemoteCameraStatus() {
        console.log('🔄 Force refreshing all remote participants camera status...');

        if (this.room && this.room.remoteParticipants && this.room.remoteParticipants instanceof Map) {
            const remoteParticipants = Array.from(this.room.remoteParticipants.values());
            console.log(`🔄 Found ${remoteParticipants.length} remote participants`);

            remoteParticipants.forEach(participant => {
                console.log(`🔄 Checking camera status for ${participant.identity}`);

                // Check if participant has active video track
                const hasVideo = this.hasActiveVideoTrack(participant);
                console.log(`🔄 ${participant.identity} has video: ${hasVideo}`);

                // Update camera state
                this.handleRemoteCameraStateChange(participant, hasVideo);
            });
        } else {
            console.log('⚠️ No remote participants found or room not available');
        }

        console.log('✅ Remote camera status refresh completed');
    }

    // Attach existing video tracks for late joiners
    attachExistingVideoTracks(participant) {
        console.log(`🔍 Attaching existing video tracks for ${participant.identity}`);

        const participantElement = document.getElementById(`participant-${participant.identity}`);
        if (!participantElement) {
            console.log(`⚠️ No participant element found for ${participant.identity}`);
            return;
        }

        // Get video tracks from participant
        if (participant.videoTracks) {
            let videoTracks = [];

            // Try different methods to get video tracks
            if (participant.videoTracks instanceof Map) {
                videoTracks = Array.from(participant.videoTracks.entries());
            } else if (typeof participant.videoTracks.values === 'function') {
                videoTracks = Array.from(participant.videoTracks.values());
            } else if (typeof participant.videoTracks === 'object') {
                videoTracks = Object.entries(participant.videoTracks);
            }

            console.log(`🔍 Found ${videoTracks.length} video tracks for ${participant.identity}`);

            // Attach each video track
            videoTracks.forEach(([trackSid, publication]) => {
                if (publication.track && publication.isSubscribed && !publication.isMuted) {
                    console.log(`🔍 Attaching video track ${trackSid} for ${participant.identity}`);
                    this.attachVideoTrack(publication.track, participantElement);
                }
            });
        }

        // Also try to get tracks from the publication directly
        if (participant.getTracks) {
            try {
                const tracks = participant.getTracks();
                console.log(`🔍 Found ${tracks.length} tracks via getTracks() for ${participant.identity}`);

                tracks.forEach(track => {
                    if (track.kind === 'video' && track.isSubscribed && !track.isMuted) {
                        console.log(`🔍 Attaching video track via getTracks() for ${participant.identity}`);
                        this.attachVideoTrack(track, participantElement);
                    }
                });
            } catch (error) {
                console.log(`⚠️ getTracks() not available for ${participant.identity}:`, error);
            }
        }

        // Update camera status after attaching tracks
        setTimeout(() => {
            this.updateParticipantCameraStatus(participant);
        }, 200);

        // Force refresh video display after a longer delay
        setTimeout(() => {
            this.forceRefreshVideoDisplay(participant);
        }, 1000);
    }

    // Force refresh video display for a participant
    forceRefreshVideoDisplay(participant) {
        console.log(`🔄 Force refreshing video display for ${participant.identity}`);

        const participantElement = document.getElementById(`participant-${participant.identity}`);
        if (!participantElement) {
            console.log(`⚠️ No participant element found for ${participant.identity}`);
            return;
        }

        const video = participantElement.querySelector('video');
        if (!video) {
            console.log(`⚠️ No video element found for ${participant.identity}`);
            return;
        }

        // Check if video has content
        if (video.videoWidth > 0 && video.videoHeight > 0) {
            console.log(`✅ Video has content for ${participant.identity}: ${video.videoWidth}x${video.videoHeight}`);
            // Hide overlay if video is working
            this.hideCameraOffOverlay(participantElement);
        } else {
            console.log(`⚠️ Video has no content for ${participant.identity}`);
            // Try to re-attach video tracks
            this.attachExistingVideoTracks(participant);
        }
    }

    // Clean up stuck overlays that should be hidden
    cleanupStuckOverlays() {
        console.log('🧹 Cleaning up stuck overlays...');

        const participantElements = document.querySelectorAll('[id^="participant-"]');
        participantElements.forEach(element => {
            const participantId = element.dataset.participantId;
            const overlay = element.querySelector('.camera-off-overlay');
            const video = element.querySelector('video');

            if (overlay && video) {
                // Check if video is actually working
                if (video.videoWidth > 0 && video.videoHeight > 0) {
                    // Video is working, overlay should be hidden
                    if (overlay.style.display !== 'none') {
                        console.log(`🧹 Fixing stuck overlay for ${participantId} - video is working`);
                        overlay.style.display = 'none';
                        overlay.classList.remove('opacity-100');
                        overlay.classList.add('opacity-0');
                    }
                }
            }
        });
    }

    toggleCameraOffOverlayForTesting() {
        console.log('🔄 Toggling camera off overlay for testing');

        // Find the first participant element
        const firstParticipant = document.querySelector('[id^="participant-"]');
        if (firstParticipant) {
            const participantId = firstParticipant.dataset.participantId;
            console.log(`🔄 Toggling overlay for participant: ${participantId}`);

            const overlay = firstParticipant.querySelector('.camera-off-overlay');
            if (overlay) {
                if (overlay.classList.contains('opacity-100')) {
                    // Hide overlay
                    overlay.classList.remove('opacity-100');
                    overlay.classList.add('opacity-0');
                    console.log('✅ Hidden camera off overlay');
                } else {
                    // Show overlay
                    overlay.classList.remove('opacity-0');
                    overlay.classList.add('opacity-100');
                    console.log('✅ Shown camera off overlay');
                }
            } else {
                console.log('⚠️ No camera off overlay found, creating one');
                // Create a test overlay
                const testOverlay = document.createElement('div');
                testOverlay.className = 'camera-off-overlay absolute inset-0 bg-gradient-to-br from-blue-900 to-gray-800 flex flex-col items-center justify-center opacity-100 transition-opacity duration-300 z-10';
                testOverlay.innerHTML = `
                    <div class="flex flex-col items-center text-center">
                        <div class="relative w-20 h-20 sm:w-24 sm:h-24 bg-gray-700 bg-opacity-60 rounded-full flex items-center justify-center mb-3 shadow-lg backdrop-blur-sm">
                            <i class="fas fa-video-slash text-white text-3xl sm:text-4xl opacity-80"></i>
                        </div>
                        <p class="text-white text-sm sm:text-base font-medium px-2 text-center">Test Participant</p>
                        <p class="text-gray-300 text-xs mt-1">مشارك</p>
                        <p class="text-gray-400 text-xs mt-1">الكاميرا مغلقة</p>
                    </div>
                `;
                firstParticipant.appendChild(testOverlay);
                console.log('✅ Created and shown test camera off overlay');
            }
        } else {
            console.log('⚠️ No participant elements found');
        }
    }

    forceUpdateParticipantsListFromMap() {
        console.log('🔄 Force updating participants list from Map');
        console.log('📊 Participants Map size:', this.participants.size);
        console.log('📊 Participants Map keys:', Array.from(this.participants.keys()));

        if (!this.elements || !this.elements.participantsList) {
            console.log('⚠️ Cannot update participants list: missing elements');
            return;
        }

        // Clear the list
        this.elements.participantsList.innerHTML = '';

        // Get all participants from the Map
        const participants = Array.from(this.participants.values());
        console.log('📊 Participants from Map:', participants.length);

        // Add local participant if not in Map
        let localParticipant = null;
        if (this.room && this.room.localParticipant) {
            localParticipant = this.room.localParticipant;
        } else if (this.localParticipant) {
            localParticipant = this.localParticipant;
        }

        if (localParticipant && !this.participants.has(localParticipant.identity)) {
            participants.push(localParticipant);
            console.log('✅ Added local participant to list');
        }

        // Process each participant
        participants.forEach((participant, index) => {
            try {
                console.log(`👤 Processing participant ${index + 1}:`, participant.identity || 'Unknown');

                const participantDiv = document.createElement('div');
                participantDiv.className = 'flex items-center justify-between bg-gray-700 rounded-lg p-3';

                // Check audio and video tracks
                let audioEnabled = false;
                let videoEnabled = false;

                try {
                    if (participant.audioTracks && typeof participant.audioTracks.values === 'function') {
                        const audioTracks = Array.from(participant.audioTracks.values());
                        audioEnabled = audioTracks.length > 0 && audioTracks.some(track => track && !track.isMuted);
                    }
                } catch (error) {
                    console.warn('⚠️ Error checking audio tracks for', participant.identity, error);
                }

                try {
                    if (participant.videoTracks && typeof participant.videoTracks.values === 'function') {
                        const videoTracks = Array.from(participant.videoTracks.values());
                        videoEnabled = videoTracks.length > 0 && videoTracks.some(track => track && !track.isMuted);
                    }
                } catch (error) {
                    console.warn('⚠️ Error checking video tracks for', participant.identity, error);
                }

                participantDiv.innerHTML = `
                <div class="flex items-center gap-3">
                    <div class="w-8 h-8 bg-blue-600 rounded-full flex items-center justify-center text-white font-semibold text-sm">
                        ${participant.identity.charAt(0).toUpperCase()}
                    </div>
                    <div>
                        <div class="text-white font-medium">${participant.identity}${participant === this.room.localParticipant ? ' (أنت)' : ''}</div>
                        <div class="text-xs">
                            ${this.isParticipantTeacher(participant) ? '<span class="text-green-400">معلم</span>' : '<span class="text-gray-400">طالب</span>'}
                        </div>
                    </div>
                </div>
                <div class="flex items-center gap-2">
                    <div class="w-6 h-6 rounded-full flex items-center justify-center ${audioEnabled ? 'bg-green-600' : 'bg-red-600'}">
                        <svg class="w-3 h-3 text-white" fill="currentColor" viewBox="0 0 20 20">
                            <path d="M7 4a3 3 0 016 0v4a3 3 0 11-6 0V4zm4 10.93A7.001 7.001 0 0017 8a1 1 0 10-2 0A5 5 0 015 8a1 1 0 00-2 0 7.001 7.001 0 006 6.93V17H6a1 1 0 100 2h8a1 1 0 100-2h-3v-2.07z"/>
                        </svg>
                    </div>
                    <div class="w-6 h-6 rounded-full flex items-center justify-center ${videoEnabled ? 'bg-green-600' : 'bg-red-600'}">
                        <svg class="w-3 h-3 text-white" fill="currentColor" viewBox="0 0 20 20">
                            <path d="M2 6a2 2 0 012-2h6a2 2 0 012 2v8a2 2 0 01-2 2H4a2 2 0 01-2-2V6zM14.553 7.106A1 1 0 0014 8v4a1 1 0 00.553.894l2 1A1 1 0 0018 13V7a1 1 0 00-1.447-.894l-2 1z"/>
                        </svg>
                    </div>
                </div>
            `;

                this.elements.participantsList.appendChild(participantDiv);
                console.log('✅ Added participant to list:', participant.identity || 'Unknown');

            } catch (participantError) {
                console.error('❌ Error processing individual participant:', participantError);
            }
        });

        console.log('✅ Force update completed');
    }

    // Set up keyboard shortcuts for focus area
    setupKeyboardShortcuts() {
        document.addEventListener('keydown', (e) => {
            // Only handle shortcuts when meeting is active
            if (!this.isConnected) return;

            // Escape key to exit focus mode
            if (e.key === 'Escape' && this.isFocusModeActive) {
                e.preventDefault();
                console.log('⌨️ Escape key pressed, exiting focus mode');
                this.exitFocusMode();
            }

            // Tab key to cycle through participants in focus mode
            if (e.key === 'Tab' && this.isFocusModeActive && !e.shiftKey) {
                e.preventDefault();
                this.cycleFocusToNextParticipant();
            }

            // Shift+Tab to cycle backwards
            if (e.key === 'Tab' && this.isFocusModeActive && e.shiftKey) {
                e.preventDefault();
                this.cycleFocusToPreviousParticipant();
            }
        });
    }

    // Cycle focus to next participant
    cycleFocusToNextParticipant() {
        const participants = Array.from(document.querySelectorAll('[id^="participant-"]'));
        if (participants.length <= 1) return;

        const currentIndex = participants.findIndex(p => p.dataset.participantId === this.focusedParticipant);
        const nextIndex = (currentIndex + 1) % participants.length;
        const nextParticipant = participants[nextIndex];

        this.switchFocus(nextParticipant);
    }

    // Cycle focus to previous participant
    cycleFocusToPreviousParticipant() {
        const participants = Array.from(document.querySelectorAll('[id^="participant-"]'));
        if (participants.length <= 1) return;

        const currentIndex = participants.findIndex(p => p.dataset.participantId === this.focusedParticipant);
        const prevIndex = currentIndex === 0 ? participants.length - 1 : currentIndex - 1;
        const prevParticipant = participants[prevIndex];

        this.switchFocus(prevParticipant);
    }

    // Set up exit focus button listener
    setupExitFocusButton() {
        // Wait for DOM to be ready
        const checkExitButton = () => {
            const exitFocusBtn = document.getElementById('exitFocusBtn');
            if (exitFocusBtn) {
                exitFocusBtn.addEventListener('click', (e) => {
                    e.preventDefault();
                    e.stopPropagation();
                    console.log('🚪 Exit focus button clicked');
                    this.exitFocusMode();
                });
                console.log('✅ Exit focus button listener added');
            } else {
                // Retry after a short delay
                setTimeout(checkExitButton, 100);
            }
        };

        checkExitButton();
    }

    // Set up fullscreen button functionality
    setupFullscreenButton() {
        const fullscreenBtn = document.getElementById('fullscreenBtn');
        if (fullscreenBtn) {
            fullscreenBtn.addEventListener('click', () => this.toggleFullscreen());

            // Listen for fullscreen change events
            document.addEventListener('fullscreenchange', () => this.handleFullscreenChange());
            document.addEventListener('webkitfullscreenchange', () => this.handleFullscreenChange());
            document.addEventListener('msfullscreenchange', () => this.handleFullscreenChange());

            console.log('✅ Fullscreen button set up');
        }
    }

    // Toggle fullscreen mode
    toggleFullscreen() {
        const meetingInterface = document.getElementById('livekitMeetingInterface');

        if (!meetingInterface) return;

        if (!document.fullscreenElement) {
            // Enter fullscreen
            if (meetingInterface.requestFullscreen) {
                meetingInterface.requestFullscreen();
            } else if (meetingInterface.webkitRequestFullscreen) {
                meetingInterface.webkitRequestFullscreen();
            } else if (meetingInterface.msRequestFullscreen) {
                meetingInterface.msRequestFullscreen();
            }
        } else {
            // Exit fullscreen
            if (document.exitFullscreen) {
                document.exitFullscreen();
            } else if (document.webkitExitFullscreen) {
                document.webkitExitFullscreen();
            } else if (document.msExitFullscreen) {
                document.msExitFullscreen();
            }
        }
    }

    // Handle fullscreen change events
    handleFullscreenChange() {
        const meetingInterface = document.getElementById('livekitMeetingInterface');
        const fullscreenIcon = document.getElementById('fullscreenIcon');
        const fullscreenText = document.getElementById('fullscreenText');

        const isFullscreen = !!(document.fullscreenElement || document.webkitFullscreenElement || document.msFullscreenElement);

        if (isFullscreen) {
            meetingInterface.classList.add('fullscreen-mode');
            if (fullscreenIcon) {
                fullscreenIcon.innerHTML = '<path fill-rule="evenodd" d="M4 3a1 1 0 000 2h1.586L2.293 8.293a1 1 0 101.414 1.414L7 6.414V8a1 1 0 002 0V4a1 1 0 00-1-1H4zm10 0a1 1 0 00-1 1v4a1 1 0 002 0V6.414l3.293 3.293a1 1 0 001.414-1.414L16.414 5H18a1 1 0 000-2h-4zm-4 10a1 1 0 00-1 1v4a1 1 0 001 1h4a1 1 0 000-2h-1.586l3.293-3.293a1 1 0 00-1.414-1.414L12 14.586V13a1 1 0 00-1-1zm-6 1a1 1 0 011-1h4a1 1 0 000 2H6.414l3.293 3.293a1 1 0 01-1.414 1.414L5 16.414V18a1 1 0 01-2 0v-4z" clip-rule="evenodd"/>';
            }
            if (fullscreenText) {
                fullscreenText.textContent = 'خروج من ملء الشاشة';
            }
        } else {
            meetingInterface.classList.remove('fullscreen-mode');
            if (fullscreenIcon) {
                fullscreenIcon.innerHTML = '<path fill-rule="evenodd" d="M3 4a1 1 0 011-1h4a1 1 0 010 2H6.414l2.293 2.293a1 1 0 01-1.414 1.414L5 6.414V8a1 1 0 01-2 0V4zm9 1a1 1 0 010-2h4a1 1 0 011 1v4a1 1 0 01-2 0V6.414l-2.293 2.293a1 1 0 11-1.414-1.414L13.586 5H12zm-9 7a1 1 0 012 0v1.586l2.293-2.293a1 1 0 111.414 1.414L6.414 15H8a1 1 0 010 2H4a1 1 0 01-1-1v-4zm13-1a1 1 0 011 1v4a1 1 0 01-1 1h-4a1 1 0 010-2h1.586l-2.293-2.293a1 1 0 111.414-1.414L15 13.586V12a1 1 0 011-1z" clip-rule="evenodd"/>';
            }
            if (fullscreenText) {
                fullscreenText.textContent = 'ملء الشاشة';
            }
        }

        // Recalculate height after fullscreen change and re-fit focused element
        setTimeout(() => {
            this.calculateMeetingHeight();
            this.scheduleFocusedLayoutUpdate();
        }, 50);

        console.log(`📺 Fullscreen mode: ${isFullscreen ? 'ON' : 'OFF'}`);
    }

    // Debounced multi-pass update to avoid transient small sizes during transitions
    scheduleFocusedLayoutUpdate() {
        this.updateFocusedLayout();
        requestAnimationFrame(() => this.updateFocusedLayout());
        setTimeout(() => this.updateFocusedLayout(), 150);
        setTimeout(() => this.updateFocusedLayout(), 350);
    }

    // Update focused element layout when container size changes (fullscreen/resizes)
    updateFocusedLayout() {
        if (!this.isFocusModeActive) return;
        const videoArea = document.getElementById('videoArea');
        const focusedElement = document.querySelector('#focusedVideoContainer [id^="focused-"]');
        if (!videoArea || !focusedElement) return;

        const videoAreaRect = videoArea.getBoundingClientRect();
        // Adaptive padding based on container size to avoid overshrinking
        const maxSide = Math.max(videoAreaRect.width, videoAreaRect.height);
        const padding = Math.min(40, Math.max(12, maxSide * 0.05));
        const availableWidth = Math.max(videoAreaRect.width - (padding * 2), 0);
        const availableHeight = Math.max(videoAreaRect.height - (padding * 2), 0);
        const aspectRatio = 16 / 9;
        let targetWidth, targetHeight;
        if (availableWidth / availableHeight > aspectRatio) {
            targetHeight = availableHeight;
            targetWidth = targetHeight * aspectRatio;
        } else {
            targetWidth = availableWidth;
            targetHeight = targetWidth / aspectRatio;
        }
        // Guard against transient zero/very small values during layout transitions
        const minWidth = Math.min(Math.max(videoAreaRect.width * 0.5, 280), videoAreaRect.width);
        const minHeight = minWidth / aspectRatio;
        if (targetWidth < 1 || targetHeight < 1) {
            targetWidth = minWidth;
            targetHeight = minHeight;
        }
        const targetX = (videoAreaRect.width - targetWidth) / 2;
        const targetY = (videoAreaRect.height - targetHeight) / 2;

        focusedElement.style.left = `${targetX}px`;
        focusedElement.style.top = `${targetY}px`;
        focusedElement.style.width = `${targetWidth}px`;
        focusedElement.style.height = `${targetHeight}px`;
    }

    addParticipant(participant) {
        console.log('👤 Adding participant:', participant.identity);

        if (this.participants.has(participant.identity)) {
            console.log('⚠️ Participant already exists:', participant.identity);
            return;
        }

        const videoGrid = document.getElementById('videoGrid');
        if (!videoGrid) {
            console.error('❌ Video grid not found');
            return;
        }

        const isLocal = participant === this.localParticipant;

        // Create ROBUST participant element with CSS classes
        const participantDiv = document.createElement('div');
        participantDiv.id = `participant-${participant.identity}`;
        participantDiv.className = 'participant-video';

        // Add data attributes for future focus feature
        participantDiv.dataset.participantId = participant.identity;
        participantDiv.dataset.isLocal = isLocal;

        // Create video element with proper containment
        const video = document.createElement('video');
        video.className = 'absolute inset-0 w-full h-full object-cover opacity-0 transition-opacity duration-300';
        video.autoplay = true;
        video.playsInline = true;
        video.muted = isLocal; // Mute local video to avoid feedback
        video.style.aspectRatio = '16/9';

        // Create enhanced placeholder with avatar and name (always visible until video)
        const placeholder = document.createElement('div');
        placeholder.className = 'absolute inset-0 flex flex-col items-center justify-center bg-gradient-to-br from-blue-900 to-gray-800 transition-opacity duration-300';

        // Generate a nice color for the avatar based on participant identity
        const colors = ['bg-blue-600', 'bg-green-600', 'bg-purple-600', 'bg-red-600', 'bg-yellow-600', 'bg-indigo-600', 'bg-pink-600', 'bg-gray-600'];
        const avatarColor = colors[participant.identity.length % colors.length];

        const displayName = participant.identity || 'مشارك';
        const initials = displayName.split(' ').map(n => n[0]).join('').toUpperCase().slice(0, 2);

        // Use unified teacher detection logic
        const isTeacher = this.isParticipantTeacher(participant, isLocal);

        const teacherBadge = isTeacher ? '<div class="absolute -top-1 -right-1 bg-green-600 text-white text-xs px-2 py-0.5 rounded-full font-bold shadow-lg">معلم</div>' : '';

        // Add a data attribute to track camera state
        placeholder.setAttribute('data-participant-id', participant.identity);
        placeholder.setAttribute('data-camera-off', 'false');

        placeholder.innerHTML = `
            <div class="flex flex-col items-center text-center">
                <div class="relative w-16 h-16 sm:w-20 sm:h-20 ${avatarColor} rounded-full flex items-center justify-center mb-3 shadow-lg transition-transform duration-200 group-hover:scale-110">
                    <span class="text-white font-bold text-lg sm:text-xl">${initials}</span>
                    ${teacherBadge}
                </div>
                <p class="text-white text-sm sm:text-base font-medium px-2 text-center">${displayName}</p>
                <p class="text-gray-300 text-xs mt-1">${isLocal ? '(أنت)' : isTeacher ? 'معلم' : 'مشارك'}</p>
                
                <!-- Camera status indicator -->
                <div class="mt-2 flex items-center justify-center">
                    <div id="camera-status-${participant.identity}" class="w-6 h-6 bg-red-600 rounded-full flex items-center justify-center">
                        <i class="fas fa-video-slash text-white text-xs"></i>
                    </div>
                </div>
            </div>
        `;

        // Create video status overlay (for when video is active)
        const statusOverlay = document.createElement('div');
        statusOverlay.className = 'absolute bottom-2 left-2 bg-black bg-opacity-75 text-white text-xs px-2 py-1 rounded opacity-0 transition-opacity duration-300';
        statusOverlay.innerHTML = `
            <span class="font-medium">${displayName}</span>
            ${isLocal ? '<span class="text-green-400 ml-1">(أنت)</span>' : isTeacher ? '<span class="text-green-400 ml-1">(معلم)</span>' : ''}
        `;

        // Add click handler for focus feature (prepared for future)
        participantDiv.onclick = () => this.handleParticipantClick(participantDiv);

        // Assemble the participant element
        participantDiv.appendChild(video);
        participantDiv.appendChild(placeholder);
        participantDiv.appendChild(statusOverlay);

        // Add to video grid
        videoGrid.appendChild(participantDiv);

        // Store participant reference
        this.participants.set(participant.identity, participant);

        // Update grid layout
        this.updateVideoGridLayout();

        // Update participant count
        this.updateParticipantCount();

        // Check and update camera status after a brief delay to ensure tracks are processed
        setTimeout(() => {
            this.updateParticipantCameraStatus(participant);
        }, 500);

        // For remote participants, force show camera-off overlay immediately
        if (!isLocal) {
            console.log(`🔍 Setting up remote participant ${participant.identity} for camera status monitoring`);

            // Force show camera-off overlay for remote participants immediately
            setTimeout(() => {
                console.log(`🔍 FORCE SHOWING camera-off overlay for remote participant ${participant.identity}`);
                const participantElement = document.getElementById(`participant-${participant.identity}`);
                if (participantElement) {
                    this.showCameraOffOverlay(participantElement, participant);
                }
            }, 2000);
        }

        console.log('✅ ROBUST participant added:', participant.identity);
        console.log('📊 Total participants in UI:', this.participants.size);
        console.log('📊 Total participants in room:', this.room.participants.size + 1); // +1 for local participant
    }

    updateVideoGridLayout() {
        this.updateVideoLayoutClasses();
    }

    // Enhanced video layout classes with proper grid configuration
    updateVideoLayoutClasses() {
        const videoArea = document.getElementById('videoArea');
        const videoGrid = document.getElementById('videoGrid');
        const participants = this.participants.size;

        if (!videoArea || !videoGrid) return;

        console.log(`📱 Enhanced video layout update for ${participants} participants`);

        // Remove all existing layout classes
        videoArea.className = videoArea.className.replace(/video-layout-\w+/g, '').replace(/participants-count-\d+/g, '').replace(/participants-many/g, '').trim();
        videoGrid.className = videoGrid.className.replace(/grid-cols-\d+|grid-rows-\d+|gap-\d+/g, '').trim();

        // Add base classes back
        videoArea.className += ' flex-1 bg-gray-900 p-4 flex flex-col min-h-0 overflow-hidden transition-all duration-500 ease-in-out';

        // Add layout state classes
        if (this.isFocusModeActive) {
            videoArea.classList.add('video-layout-focus');
        } else {
            videoArea.classList.add('video-layout-normal');
        }

        if (this.isSidebarOpen) {
            videoArea.classList.add('video-layout-sidebar-open');
        }

        // Enhanced participant count handling with proper grid
        if (participants === 0) {
            // Show empty state
            videoGrid.innerHTML = '<div class="flex items-center justify-center text-gray-500 text-lg h-full">في انتظار المشاركين...</div>';
            videoGrid.style.display = 'flex';
        } else {
            // Clear any empty state message
            const emptyMessage = videoGrid.querySelector('.flex.items-center.justify-center.text-gray-500');
            if (emptyMessage) {
                emptyMessage.remove();
            }

            // Apply proper grid configuration based on participant count
            this.applyParticipantGridLayout(videoGrid, participants);

            // Add participant count classes for CSS customization
            if (participants <= 10) {
                videoArea.classList.add(`participants-count-${participants}`);
            } else {
                videoArea.classList.add('participants-many');
            }
        }

        // Ensure all participant elements have proper classes
        this.updateParticipantElements();

        console.log(`✅ Enhanced video layout applied for ${participants} participants`);
    }

    // New method to apply proper grid layout based on participant count
    applyParticipantGridLayout(videoGrid, participantCount) {
        console.log(`🔧 Applying grid layout for ${participantCount} participants`);

        // Get container dimensions
        const containerRect = videoGrid.getBoundingClientRect();
        const containerWidth = containerRect.width || 800; // Fallback width
        const containerHeight = containerRect.height || 600; // Fallback height

        // Calculate optimal grid using our enhanced method
        const gridConfig = this.calculateOptimalGrid(containerWidth, containerHeight, participantCount);

        // Apply the grid configuration
        videoGrid.style.display = 'grid';
        videoGrid.style.gridTemplateColumns = `repeat(${gridConfig.cols}, 1fr)`;
        videoGrid.style.gridTemplateRows = `repeat(${gridConfig.rows}, 1fr)`;
        videoGrid.style.gap = gridConfig.gap;
        videoGrid.style.width = '100%';
        videoGrid.style.height = '100%';
        videoGrid.style.padding = '0.5rem';
        videoGrid.style.boxSizing = 'border-box';

        // Add responsive classes
        if (participantCount > 100) {
            videoGrid.classList.add('mega-grid', 'participants-100plus');
            videoGrid.style.overflow = 'auto';
        } else if (participantCount > 50) {
            videoGrid.classList.add('large-grid', 'participants-50plus');
        } else if (participantCount > 25) {
            videoGrid.classList.add('medium-grid', 'participants-25plus');
        } else {
            videoGrid.classList.add('standard-grid');
        }

        // Ensure all participant elements fit properly
        const participantElements = videoGrid.querySelectorAll('[id^="participant-"]');
        participantElements.forEach((element, index) => {
            // Calculate grid position
            const row = Math.floor(index / gridConfig.cols) + 1;
            const col = (index % gridConfig.cols) + 1;

            // Apply grid positioning
            element.style.gridRow = row.toString();
            element.style.gridColumn = col.toString();
            element.style.minWidth = `${gridConfig.minSize}px`;
            element.style.minHeight = `${gridConfig.minSize}px`;
            element.style.maxWidth = '100%';
            element.style.maxHeight = '100%';
            element.style.aspectRatio = '16/9'; // Maintain aspect ratio
            element.style.overflow = 'hidden';
        });

        console.log(`✅ Grid applied: ${gridConfig.cols}x${gridConfig.rows}, ${participantCount} participants`);
    }

    // Update participant elements with consistent classes
    updateParticipantElements() {
        const participantElements = document.querySelectorAll('[id^="participant-"]');

        participantElements.forEach(element => {
            // Set consistent participant video class with proper styling
            element.className = 'participant-video relative bg-gray-800 rounded-lg overflow-hidden transition-all duration-300 border border-gray-700 hover:border-blue-500 cursor-pointer group';

            // Add click handler for focus feature
            element.onclick = () => this.handleParticipantClick(element);

            // Ensure video element has proper styling
            const video = element.querySelector('video');
            if (video) {
                video.className = 'w-full h-full object-cover transition-opacity duration-300';
            }
        });
    }

    addParticipant(participant) {
        console.log('👤 Adding participant:', participant.identity);

        if (this.participants.has(participant.identity)) {
            console.log('⚠️ Participant already exists:', participant.identity);
            return;
        }

        const videoGrid = document.getElementById('videoGrid');
        if (!videoGrid) {
            console.error('❌ Video grid not found');
            return;
        }

        const isLocal = participant === this.localParticipant;

        // Create participant element with proper CSS classes and structure
        const participantDiv = document.createElement('div');
        participantDiv.id = `participant-${participant.identity}`;
        participantDiv.className = 'participant-video relative bg-gray-800 rounded-lg overflow-hidden transition-all duration-300 border border-gray-700 hover:border-blue-500 cursor-pointer group';

        // Add data attributes
        participantDiv.dataset.participantId = participant.identity;
        participantDiv.dataset.isLocal = isLocal;

        // Create video element with proper styling
        const video = document.createElement('video');
        video.autoplay = true;
        video.playsInline = true;
        video.muted = isLocal; // Mute local video to avoid feedback
        video.className = 'w-full h-full object-cover opacity-0 transition-opacity duration-300';

        // Create placeholder with avatar
        const placeholder = document.createElement('div');
        placeholder.className = 'absolute inset-0 flex flex-col items-center justify-center bg-gradient-to-br from-blue-900 to-gray-800 transition-opacity duration-300';

        // Create participant name label (bottom left corner)
        const nameLabel = document.createElement('div');
        nameLabel.className = 'absolute bottom-2 left-2 bg-black bg-opacity-70 text-white px-2 py-1 rounded text-sm font-medium z-10';

        // Set name with proper labels using unified teacher detection
        const isTeacher = this.isParticipantTeacher(participant, isLocal);
        const displayName = isLocal ?
            `${participant.identity} (أنت)${isTeacher ? ' - معلم' : ''}` :
            `${participant.identity}${isTeacher ? ' - معلم' : ''}`;

        nameLabel.textContent = displayName;

        // Create placeholder content
        const placeholderName = participant.identity || 'مشارك';
        const initials = placeholderName.split(' ').map(n => n[0]).join('').toUpperCase().slice(0, 2);

        placeholder.innerHTML = `
            <div class="w-16 h-16 rounded-full bg-blue-600 flex items-center justify-center text-white font-bold text-lg mb-3">
                ${initials}
            </div>
            <div class="text-white text-center">
                <div class="font-medium text-sm">${placeholderName}</div>
                <div class="text-xs text-gray-300 mt-1">${isLocal ? 'أنت' : 'مشارك'}</div>
            </div>
        `;

        // Assemble participant element
        participantDiv.appendChild(video);
        participantDiv.appendChild(placeholder);
        participantDiv.appendChild(nameLabel); // Add name label

        // Add to participants map and video grid
        this.participants.set(participant.identity, participant);
        videoGrid.appendChild(participantDiv);

        // Set up video track handling
        this.handleParticipantVideoTracks(participant, video, placeholder);

        // Set up track subscription handling for remote participants
        if (!isLocal) {
            this.setupTrackSubscriptionHandling(participant, video, placeholder);
        }

        // Update layout classes
        this.updateVideoLayoutClasses();

        console.log('✅ Participant added:', participant.identity);
    }

    // Set up track subscription handling for remote participants
    setupTrackSubscriptionHandling(participant, video, placeholder) {
        // Listen for video track subscriptions
        participant.on('trackSubscribed', (track, publication) => {
            console.log(`📹 Track subscribed for ${participant.identity}:`, track.kind);

            if (track.kind === 'video') {
                console.log('✅ Attaching subscribed video track');
                track.attach(video);

                // Show video and hide placeholder
                video.style.display = 'block';
                video.classList.remove('opacity-0');
                video.classList.add('opacity-100');

                if (placeholder) {
                    placeholder.style.display = 'none';
                }
            }
        });

        // Listen for track unsubscriptions
        participant.on('trackUnsubscribed', (track) => {
            console.log(`📹 Track unsubscribed for ${participant.identity}:`, track.kind);

            if (track.kind === 'video') {
                // Show placeholder when video track is lost
                if (placeholder) {
                    placeholder.style.display = 'flex';
                }
                video.style.display = 'none';
            }
        });
    }

    // Handle participant video tracks - attach existing tracks to video element
    handleParticipantVideoTracks(participant, video, placeholder) {
        // Safely get video tracks size
        const videoTracks = participant.videoTracks || new Map();
        console.log(`🎥 Handling video tracks for ${participant.identity}:`, videoTracks.size);

        const isLocal = participant === this.localParticipant;
        let trackAttached = false;

        // For local participants, we need to handle differently
        if (isLocal) {
            console.log('🎥 Local participant detected, using special handling');

            // Safely check if local participant has video tracks
            const videoTracks = participant.videoTracks || new Map();
            if (videoTracks.size > 0) {
                // Find camera track
                const cameraTrack = Array.from(videoTracks.values())
                    .find(pub => pub.source === (window.LiveKit?.Track?.Source?.Camera || 'camera'));

                if (cameraTrack && cameraTrack.track) {
                    console.log('✅ Attaching local camera track');
                    cameraTrack.track.attach(video);

                    // Show video and hide placeholder
                    video.style.display = 'block';
                    video.classList.remove('opacity-0');
                    video.classList.add('opacity-100');

                    if (placeholder) {
                        placeholder.style.display = 'none';
                    }

                    trackAttached = true;
                } else {
                    console.log('⚠️ No camera track found for local participant');
                }
            } else {
                console.log('⚠️ Local participant has no video tracks');
            }
        } else {
            // Handle remote participants
            const videoTracks = participant.videoTracks || new Map();
            for (const [trackSid, publication] of videoTracks) {
                console.log(`📹 Processing remote track ${trackSid}:`, {
                    hasTrack: !!publication.track,
                    isMuted: publication.isMuted,
                    isSubscribed: publication.isSubscribed,
                    source: publication.source
                });

                if (publication.track && publication.isSubscribed) {
                    console.log('✅ Attaching remote video track');
                    publication.track.attach(video);

                    // Show video and hide placeholder
                    video.style.display = 'block';
                    video.classList.remove('opacity-0');
                    video.classList.add('opacity-100');

                    if (placeholder) {
                        placeholder.style.display = 'none';
                    }

                    trackAttached = true;
                    break;
                }
            }
        }

        // If no tracks were attached, show placeholder
        if (!trackAttached) {
            console.log('📺 No active video tracks, showing placeholder');
            if (placeholder) {
                placeholder.style.display = 'flex';
            }
            video.style.display = 'none';
        }
    }

    // Handle participant click for focus feature (now fully implemented)
    handleParticipantClick(participantElement) {
        const participantId = participantElement.dataset.participantId;
        const isLocal = participantElement.dataset.isLocal === 'true';

        console.log('🖱️ Participant clicked:', participantId, '(Local:', isLocal, ')');

        // Add immediate visual feedback
        participantElement.classList.add('ring-2', 'ring-blue-400', 'ring-opacity-75');

        // If already in focus mode, switch focus to this participant
        if (this.isFocusModeActive) {
            this.switchFocus(participantElement);
        } else {
            // Enter focus mode with this participant
            this.enterFocusMode(participantElement);
        }

        // Remove temporary visual feedback after animation
        setTimeout(() => {
            participantElement.classList.remove('ring-2', 'ring-blue-400', 'ring-opacity-75');
        }, 300);
    }

    // Enter focus mode with a specific participant - TRUE ELEMENT MOVEMENT
    enterFocusMode(participantElement) {
        console.log('🎯 Entering focus mode for:', participantElement.dataset.participantId);

        // Prevent multiple focus modes
        if (this.isFocusModeActive) {
            console.log('⚠️ Focus mode already active, exiting first');
            this.exitFocusMode();
            setTimeout(() => this.enterFocusMode(participantElement), 100);
            return;
        }

        this.isFocusModeActive = true;
        this.focusedParticipant = participantElement.dataset.participantId;

        // Store original position and size for smooth exit animation
        this.originalParticipantRect = participantElement.getBoundingClientRect();

        // Get the video area container
        const videoArea = document.getElementById('videoArea');
        if (!videoArea) {
            console.error('❌ Video area not found');
            return;
        }

        // Clean up any existing focus elements
        this.cleanupFocusElements();

        // Store original position and size for smooth animation
        const originalRect = participantElement.getBoundingClientRect();
        const videoAreaRect = videoArea.getBoundingClientRect();

        // Preserve original DOM position to prevent grid reflow
        const originalParent = participantElement.parentNode;
        const originalNextSibling = participantElement.nextElementSibling;
        this._focusOriginalParent = originalParent;
        this._focusOriginalNextSibling = originalNextSibling;

        // Create overlay for the entire video area
        const overlay = document.createElement('div');
        overlay.id = 'focusOverlay';
        overlay.className = 'absolute inset-0 bg-black bg-opacity-75 z-40 transition-opacity duration-300 opacity-0';

        // Create the focused video container
        const focusedContainer = document.createElement('div');
        focusedContainer.id = 'focusedVideoContainer';
        focusedContainer.className = 'absolute inset-0 z-50';

        // MOVE the actual participant element to focused container
        const focusedElement = participantElement;
        focusedElement.id = `focused-${participantElement.dataset.participantId}`;
        focusedElement.className = 'relative bg-gray-800 rounded-lg overflow-hidden transition-all duration-500 ease-out';
        focusedElement.onclick = null; // Remove click handler

        // Calculate target size with proper boundaries and aspect ratio (16:9)
        const padding = 40; // Padding from video area edges
        const availableWidth = videoAreaRect.width - (padding * 2);
        const availableHeight = videoAreaRect.height - (padding * 2);

        // Calculate size based on 16:9 aspect ratio
        const aspectRatio = 16 / 9;
        let targetWidth, targetHeight;

        if (availableWidth / availableHeight > aspectRatio) {
            // Height is the limiting factor
            targetHeight = availableHeight;
            targetWidth = targetHeight * aspectRatio;
        } else {
            // Width is the limiting factor
            targetWidth = availableWidth;
            targetHeight = targetWidth / aspectRatio;
        }

        // Center the focused video within the video area
        const targetX = (videoAreaRect.width - targetWidth) / 2;
        const targetY = (videoAreaRect.height - targetHeight) / 2;

        console.log(`📐 Target size: ${targetWidth}x${targetHeight}, Position: ${targetX},${targetY}`);

        // Set initial position to maintain original position
        focusedElement.style.position = 'absolute';
        focusedElement.style.left = `${originalRect.left - videoAreaRect.left}px`;
        focusedElement.style.top = `${originalRect.top - videoAreaRect.top}px`;
        focusedElement.style.width = `${originalRect.width}px`;
        focusedElement.style.height = `${originalRect.height}px`;
        focusedElement.style.zIndex = '60';

        // Ensure video maintains proper sizing
        const video = focusedElement.querySelector('video');
        if (video) {
            video.style.width = '100%';
            video.style.height = '100%';
            video.style.objectFit = 'cover';
            console.log('✅ Video track preserved in focus mode');
        }

        // Add exit button
        const exitBtn = document.createElement('button');
        exitBtn.id = 'exitFocusBtn';
        exitBtn.className = 'absolute top-4 right-4 w-10 h-10 bg-black bg-opacity-50 hover:bg-opacity-70 rounded-full text-white flex items-center justify-center transition-all duration-200 hover:scale-110 focus:outline-none focus:ring-2 focus:ring-blue-500 z-10';
        exitBtn.innerHTML = '<i class="fas fa-times text-lg"></i>';
        exitBtn.onclick = (e) => {
            e.stopPropagation();
            this.exitFocusMode();
        };

        focusedElement.appendChild(exitBtn);
        focusedContainer.appendChild(focusedElement);

        // Add overlay and focused container to video area
        videoArea.appendChild(overlay);
        videoArea.appendChild(focusedContainer);

        // Create a hidden placeholder occupying the original grid slot
        const placeholder = document.createElement('div');
        placeholder.id = `placeholder-${participantElement.dataset.participantId}`;
        // mirror sizing behavior but keep it invisible so the slot is reserved
        placeholder.className = participantElement.className;
        placeholder.style.visibility = 'hidden';
        placeholder.style.pointerEvents = 'none';
        placeholder.style.height = `${originalRect.height}px`;
        placeholder.style.width = `${originalRect.width}px`;

        // Insert placeholder at the exact original position to prevent reflow
        if (originalParent) {
            if (originalNextSibling) {
                originalParent.insertBefore(placeholder, originalNextSibling);
            } else {
                originalParent.appendChild(placeholder);
            }
        }

        // Trigger animations
        requestAnimationFrame(() => {
            // Show overlay
            overlay.classList.remove('opacity-0');
            overlay.classList.add('opacity-100');

            // Show placeholder
            placeholder.classList.remove('opacity-0');
            placeholder.classList.add('opacity-100');

            // Animate to focused position
            focusedElement.style.transition = 'all 500ms cubic-bezier(0.4, 0, 0.2, 1)';
            focusedElement.style.left = `${targetX}px`;
            focusedElement.style.top = `${targetY}px`;
            focusedElement.style.width = `${targetWidth}px`;
            focusedElement.style.height = `${targetHeight}px`;
        });

        // Add focus mode class to video area
        videoArea.classList.add('focus-mode-active');

        console.log('✅ Focus mode activated with true element movement');
    }

    // Clean up any existing focus elements
    cleanupFocusElements() {
        // Remove existing overlay
        const existingOverlay = document.getElementById('focusOverlay');
        if (existingOverlay) {
            existingOverlay.remove();
        }

        // Remove existing focused container
        const existingContainer = document.getElementById('focusedVideoContainer');
        if (existingContainer) {
            existingContainer.remove();
        }

        // Remove existing placeholders
        const existingPlaceholders = document.querySelectorAll('[id^="placeholder-"]');
        existingPlaceholders.forEach(placeholder => placeholder.remove());

        // Remove focus mode class
        const videoArea = document.getElementById('videoArea');
        if (videoArea) {
            videoArea.classList.remove('focus-mode-active');
        }
    }

    // Switch focus to a different participant - SMOOTH SCALING APPROACH
    switchFocus(newParticipantElement) {
        const oldParticipantId = this.focusedParticipant;
        const newParticipantId = newParticipantElement.dataset.participantId;

        console.log('🔄 Switching focus from', oldParticipantId, 'to', newParticipantId);

        // Exit current focus mode first
        this.exitFocusMode();

        // Wait for exit animation to complete, then enter new focus mode
        setTimeout(() => {
            this.enterFocusMode(newParticipantElement);
        }, 500); // Match the transition duration

        console.log('✅ Focus switched successfully');
    }

    // Exit focus mode and return to grid layout - TRUE ELEMENT MOVEMENT
    exitFocusMode() {
        console.log('🚪 Exiting focus mode');

        // Get the focused video container and overlay
        const focusedContainer = document.getElementById('focusedVideoContainer');
        const overlay = document.getElementById('focusOverlay');
        const focusedElement = focusedContainer?.querySelector('[id^="focused-"]');

        if (focusedElement && this.originalParticipantRect) {
            // Use the stored original position for smooth animation
            const videoArea = document.getElementById('videoArea');
            const videoAreaRect = videoArea.getBoundingClientRect();

            // Calculate the original position relative to current video area
            const originalX = this.originalParticipantRect.left - videoAreaRect.left;
            const originalY = this.originalParticipantRect.top - videoAreaRect.top;

            // Animate back to original position
            focusedElement.style.transition = 'all 500ms cubic-bezier(0.4, 0, 0.2, 1)';
            focusedElement.style.left = `${originalX}px`;
            focusedElement.style.top = `${originalY}px`;
            focusedElement.style.width = `${this.originalParticipantRect.width}px`;
            focusedElement.style.height = `${this.originalParticipantRect.height}px`;

            console.log('✅ Animating back to stored original position');
        }

        // Hide overlay with animation
        if (overlay) {
            overlay.classList.remove('opacity-100');
            overlay.classList.add('opacity-0');
        }

        // Hide placeholder on original element
        const originalParticipantId = this.focusedParticipant;
        const originalElement = document.getElementById(`participant-${originalParticipantId}`);
        if (originalElement) {
            const placeholder = originalElement.querySelector(`#placeholder-${originalParticipantId}`);
            if (placeholder) {
                placeholder.classList.remove('opacity-100');
                placeholder.classList.add('opacity-0');
            }
        }

        // Wait for animations to complete, then clean up
        setTimeout(() => {
            // Remove exit button from focused element before moving it back
            if (focusedElement) {
                const exitBtn = focusedElement.querySelector('#exitFocusBtn');
                if (exitBtn) {
                    exitBtn.remove();
                }

                const videoGrid = document.getElementById('videoGrid');
                if (videoGrid) {
                    // Restore original properties
                    const participantId = focusedElement.dataset.participantId;
                    focusedElement.id = `participant-${participantId}`;
                    focusedElement.className = 'participant-video relative bg-gray-800 rounded-lg overflow-hidden transition-all duration-300 border border-gray-700 hover:border-blue-500 cursor-pointer group';

                    // Clear all inline styles
                    focusedElement.style.position = '';
                    focusedElement.style.left = '';
                    focusedElement.style.top = '';
                    focusedElement.style.width = '';
                    focusedElement.style.height = '';
                    focusedElement.style.transition = '';
                    focusedElement.style.zIndex = '';

                    // Restore click handler
                    focusedElement.onclick = () => this.handleParticipantClick(focusedElement);

                    // Replace placeholder at original position to avoid grid shift
                    const placeholder = document.getElementById(`placeholder-${participantId}`);
                    if (placeholder && this._focusOriginalParent) {
                        this._focusOriginalParent.replaceChild(focusedElement, placeholder);
                    } else {
                        // Fallback: append to grid
                        videoGrid.appendChild(focusedElement);
                    }

                    console.log('✅ Focused element moved back to grid');
                }
            }

            // Remove overlay and focused container
            if (overlay) overlay.remove();
            if (focusedContainer) focusedContainer.remove();

            // Ensure placeholder cleanup
            const ph = document.getElementById(`placeholder-${originalParticipantId}`);
            if (ph && ph.parentNode) ph.parentNode.removeChild(ph);

            // Remove focus mode class from video area
            const videoArea = document.getElementById('videoArea');
            if (videoArea) {
                videoArea.classList.remove('focus-mode-active');
            }

            console.log('✅ Focus mode exited with true element movement');
        }, 500); // Match the transition duration

        // Reset focus state
        this.isFocusModeActive = false;
        this.focusedParticipant = null;
        this.originalParticipantRect = null;
        this._focusOriginalParent = null;
        this._focusOriginalNextSibling = null;

        // Return to grid layout
        this.switchToGridLayout();

        // Remove visual feedback from all participants
        const allParticipants = document.querySelectorAll('[id^="participant-"]');
        allParticipants.forEach(element => {
            element.classList.remove('focus-indicator');
        });

        // Remove focus mode class from video area
        const videoArea = document.getElementById('videoArea');
        if (videoArea) {
            videoArea.classList.remove('focus-mode-active');
        }

        console.log('✅ Focus mode exited');
    }

    // Move element to focus area (deprecated)
    // Method removed as focusArea is no longer used

    // Move element back to grid
    moveElementBackToGrid(element) {
        // Restore original ID and classes
        const participantId = element.dataset.participantId;
        element.id = `participant-${participantId}`;
        element.className = 'relative bg-gray-800 rounded-lg overflow-hidden aspect-video min-h-[150px] max-h-[300px] transition-all duration-300 border border-gray-700 hover:border-blue-500 cursor-pointer group participant-hover video-transition participant-clickable grid-element element-move-transition';

        // Reset all inline styles to ensure proper sizing
        element.style.minHeight = '';
        element.style.maxHeight = '';
        element.style.height = '';
        element.style.width = '';
        element.style.aspectRatio = '';

        // Restore video element styling
        const video = element.querySelector('video');
        if (video) {
            // Reset all video styles to defaults
            video.style.width = '';
            video.style.height = '';
            video.style.objectFit = '';
            video.style.aspectRatio = '';
            video.style.transform = '';
            video.style.transformOrigin = '';

            // Apply proper video styling
            video.style.width = '100%';
            video.style.height = '100%';
            video.style.objectFit = 'cover';

            console.log('✅ Video element restored to grid with proper styling');
        }

        // Restore click handler
        element.onclick = () => this.handleParticipantClick(element);

        // Move back to video grid
        const videoGrid = document.getElementById('videoGrid');
        if (videoGrid) {
            videoGrid.appendChild(element);
        }

        // Force a layout update to ensure proper sizing
        setTimeout(() => {
            this.updateVideoGridLayout();
        }, 100);
    }

    // Switch to horizontal layout for remaining participants
    switchToHorizontalLayout() {
        const videoGrid = document.getElementById('videoGrid');
        const horizontalParticipants = document.getElementById('horizontalParticipants');

        if (!videoGrid || !horizontalParticipants) return;

        // Hide video grid
        videoGrid.style.display = 'none';

        // Show horizontal participants
        horizontalParticipants.classList.remove('hidden');
        horizontalParticipants.style.display = 'flex';

        // Get remaining participants (excluding the focused one)
        const participants = Array.from(videoGrid.children).filter(child =>
            child.id.startsWith('participant-') &&
            child.dataset.participantId !== this.focusedParticipant
        );

        horizontalParticipants.innerHTML = '';
        participants.forEach(participant => {
            // Create thumbnail version for horizontal layout
            const thumbnail = participant.cloneNode(true);
            thumbnail.id = `thumb-${participant.dataset.participantId}`;
            thumbnail.className = 'relative bg-gray-800 rounded-lg overflow-hidden flex-shrink-0 transition-all duration-300 border border-gray-700 hover:border-blue-500 cursor-pointer group participant-hover video-transition element-move-transition';
            thumbnail.style.width = '200px';
            thumbnail.style.height = '120px';
            thumbnail.style.minWidth = '200px';
            thumbnail.style.minHeight = '120px';

            // Add click handler for focus switching
            thumbnail.onclick = () => {
                const originalParticipant = document.querySelector(`[data-participant-id="${participant.dataset.participantId}"]`);
                if (originalParticipant) {
                    this.switchFocus(originalParticipant);
                }
            };

            // Scale down video if present
            const video = thumbnail.querySelector('video');
            if (video) {
                video.style.transform = 'scale(0.8)';
                video.style.transformOrigin = 'center';
            }

            horizontalParticipants.appendChild(thumbnail);
        });

        console.log('✅ Switched to horizontal layout');
    }

    // Switch back to grid layout
    switchToGridLayout() {
        const videoGrid = document.getElementById('videoGrid');
        const horizontalParticipants = document.getElementById('horizontalParticipants');

        if (!videoGrid || !horizontalParticipants) return;

        // Show video grid
        videoGrid.style.display = 'grid';

        // Hide horizontal participants
        horizontalParticipants.classList.add('hidden');
        horizontalParticipants.style.display = 'none';

        // Clear horizontal participants
        horizontalParticipants.innerHTML = '';

        // Update grid layout
        this.updateVideoGridLayout();

        console.log('✅ Switched back to grid layout');
    }

    setupGridResizeObserver() {
        const videoGrid = document.getElementById('videoGrid');
        if (!videoGrid || this.gridResizeObserver) return;

        this.gridResizeObserver = new ResizeObserver((entries) => {
            for (let entry of entries) {
                const { width, height } = entry.contentRect;
                console.log(`📐 Grid container resized: ${width}x${height}`);

                // Update participant sizing based on container dimensions
                const participantElements = videoGrid.querySelectorAll('[id^="participant-"]');
                const participantCount = participantElements.length;

                if (participantCount > 0) {
                    // Calculate optimal sizing for current container
                    let optimalMinHeight, optimalMaxHeight;

                    if (participantCount === 1) {
                        // Single participant - use much larger dimensions
                        optimalMinHeight = Math.min(height * 0.7, 500);
                        optimalMaxHeight = height * 0.85; // Use most of available height
                    } else if (participantCount <= 4) {
                        optimalMinHeight = Math.min(height * 0.35, 250);
                        optimalMaxHeight = height * 0.45;
                    } else if (participantCount <= 9) {
                        optimalMinHeight = Math.min(height * 0.25, 200);
                        optimalMaxHeight = height * 0.3;
                    } else {
                        optimalMinHeight = Math.min(height * 0.2, 150);
                        optimalMaxHeight = height * 0.25;
                    }

                    participantElements.forEach(element => {
                        element.style.minHeight = `${optimalMinHeight}px`;
                        element.style.maxHeight = `${optimalMaxHeight}px`;
                    });
                }
            }
        });

        this.gridResizeObserver.observe(videoGrid);
    }

    removeParticipant(participantId) {
        console.log('👋 Removing participant:', participantId);

        // Remove from participants map
        this.participants.delete(participantId);

        // Remove from UI
        const participantElement = document.getElementById(`participant-${participantId}`);
        if (participantElement) {
            participantElement.remove();
        }

        // Remove thumbnail if in horizontal layout
        const thumbnailElement = document.getElementById(`thumb-${participantId}`);
        if (thumbnailElement) {
            thumbnailElement.remove();
        }

        // Handle focus area updates
        if (this.isFocusModeActive && this.focusedParticipant === participantId) {
            console.log('🎯 Focused participant left, exiting focus mode');
            this.exitFocusMode();
        }

        // Update grid layout
        this.updateVideoGridLayout();

        // Update participant count
        this.updateParticipantCount();

        // Handle edge cases
        this.handleParticipantCountChanges();

        console.log('✅ Participant removed:', participantId);
    }

    handleTrackSubscribed(track, publication, participant) {
        console.log('📹 Handling track subscription:', track.kind, 'from', participant.identity);

        if (track.kind === 'video') {
            // Handle video track
            let participantElement = document.getElementById(`participant-${participant.identity}`);

            // If participant element doesn't exist, add the participant first
            if (!participantElement) {
                console.log('👤 Adding new participant for video track:', participant.identity);
                this.addParticipant(participant);
                // Wait a bit for the element to be created
                setTimeout(() => {
                    participantElement = document.getElementById(`participant-${participant.identity}`);
                    if (participantElement) {
                        this.attachVideoTrack(track, participantElement);
                    }
                }, 100);
            } else {
                this.attachVideoTrack(track, participantElement);

                // For remote participants, ensure camera status is updated when video track is attached
                if (participant !== this.localParticipant) {
                    console.log(`🔍 Video track attached for remote participant ${participant.identity} - updating camera status`);
                    setTimeout(() => {
                        this.updateParticipantCameraStatus(participant);
                        // Also trigger remote camera state change to ensure overlay is updated
                        this.handleRemoteCameraStateChange(participant, true);
                    }, 500);
                }
            }
        } else if (track.kind === 'audio') {
            // Handle audio track
            console.log('🎵 Attaching audio track from:', participant.identity);

            // Create audio element for this participant
            const audioElement = document.createElement('audio');
            audioElement.id = `audio-${participant.identity}`;
            audioElement.autoplay = true;
            audioElement.playsInline = true;
            audioElement.muted = false; // Don't mute other participants' audio

            // Attach the audio track
            track.attach(audioElement);

            // Add to document body (hidden)
            document.body.appendChild(audioElement);

            console.log('✅ Audio track attached for:', participant.identity);
        }
    }

    attachVideoTrack(track, participantElement) {
        console.log(`🔍 attachVideoTrack called for participant: ${participantElement.dataset.participantId}`);
        console.log(`🔍 Track info:`, { kind: track.kind, id: track.sid, enabled: track.enabled });

        const video = participantElement.querySelector('video');
        if (video) {
            console.log(`🔍 Found video element, attaching track...`);
            track.attach(video);

            // Show video with fade-in effect
            video.classList.remove('opacity-0');
            video.classList.add('opacity-100');

            // Hide camera-off overlay when video is enabled
            console.log(`🔍 Hiding camera-off overlay for video track attachment`);
            this.hideCameraOffOverlay(participantElement);

            // Hide placeholder with fade-out effect
            const placeholder = participantElement.querySelector('.absolute.inset-0:not(video):not(.absolute.bottom-2)');
            if (placeholder && placeholder.classList.contains('bg-gradient-to-br')) {
                placeholder.classList.remove('opacity-100');
                placeholder.classList.add('opacity-0');
                // Hide completely after transition
                setTimeout(() => {
                    placeholder.style.display = 'none';
                }, 300);
            }

            // Show status overlay with participant name
            const statusOverlay = participantElement.querySelector('.absolute.bottom-2');
            if (statusOverlay) {
                statusOverlay.classList.remove('opacity-0');
                statusOverlay.classList.add('opacity-100');
            }

            // Update camera status indicator when video track is attached
            const cameraStatus = participantElement.querySelector(`#camera-status-${participantElement.dataset.participantId}`);
            if (cameraStatus) {
                cameraStatus.className = 'w-6 h-6 bg-green-600 rounded-full flex items-center justify-center';
                const icon = cameraStatus.querySelector('i');
                if (icon) {
                    icon.className = 'fas fa-video text-white text-xs';
                }
            }

            // Update camera status for this participant
            const participantId = participantElement.dataset.participantId;
            let participant = null;

            // Try to find the participant using different methods
            if (this.room) {
                // Method 1: Try remoteParticipants Map
                if (this.room.remoteParticipants && this.room.remoteParticipants instanceof Map) {
                    participant = this.room.remoteParticipants.get(participantId);
                }
                // Method 2: Try participants Map
                if (!participant && this.room.participants && this.room.participants instanceof Map) {
                    participant = this.room.participants.get(participantId);
                }
                // Method 3: Check if it's the local participant
                if (!participant && this.localParticipant && this.localParticipant.identity === participantId) {
                    participant = this.localParticipant;
                }
            }

            if (participant) {
                console.log(`🔄 Updating camera status for ${participant.identity} after video track attachment`);
                setTimeout(() => this.updateParticipantCameraStatus(participant), 100);
            } else {
                console.log(`⚠️ Could not find participant ${participantId} for camera status update`);
            }

            console.log('✅ ROBUST video track attached for:', participantElement.id);
        } else {
            console.error('❌ Video element not found for participant:', participantElement.id);
        }
    }

    handleTrackUnsubscribed(track, publication, participant) {
        console.log('📹 Handling track unsubscription:', track.kind, 'from', participant.identity);

        if (track.kind === 'video') {
            const participantElement = document.getElementById(`participant-${participant.identity}`);
            if (participantElement) {
                const videoElement = participantElement.querySelector('video');
                if (videoElement) {
                    track.detach(videoElement);
                    // Hide video with fade-out effect
                    videoElement.classList.remove('opacity-100');
                    videoElement.classList.add('opacity-0');
                }

                // Check if participant is still in the room (not left)
                let isStillInRoom = false;

                if (this.room) {
                    // Method 1: Check remoteParticipants Map
                    if (this.room.remoteParticipants && this.room.remoteParticipants instanceof Map) {
                        isStillInRoom = this.room.remoteParticipants.has(participant.identity);
                    }
                    // Method 2: Check participants Map
                    if (!isStillInRoom && this.room.participants && this.room.participants instanceof Map) {
                        isStillInRoom = this.room.participants.has(participant.identity);
                    }
                    // Method 3: Check if it's the local participant
                    if (!isStillInRoom && participant === this.localParticipant) {
                        isStillInRoom = true;
                    }
                }

                if (isStillInRoom) {
                    // Participant is still in room but camera is off - show camera-off overlay
                    console.log(`📹 Showing camera-off overlay for ${participant.identity} (still in room)`);
                    console.log(`🔍 Participant element:`, participantElement);
                    console.log(`🔍 Participant element ID:`, participantElement.id);
                    this.showCameraOffOverlay(participantElement, participant);

                    // Debug: Check if overlay was created
                    setTimeout(() => {
                        const overlay = participantElement.querySelector('.camera-off-overlay');
                        console.log(`🔍 Camera-off overlay found:`, !!overlay);
                        if (overlay) {
                            console.log(`🔍 Overlay classes:`, overlay.className);
                            console.log(`🔍 Overlay opacity:`, overlay.classList.contains('opacity-100'));
                        }
                    }, 100);
                } else {
                    // Participant left the room - show placeholder with avatar
                    console.log(`📹 Participant ${participant.identity} left the room`);
                    // The placeholder should already be visible from when participant was added
                }

                // Hide status overlay
                const statusOverlay = participantElement.querySelector('.absolute.bottom-2');
                if (statusOverlay) {
                    statusOverlay.classList.remove('opacity-100');
                    statusOverlay.classList.add('opacity-0');
                }

                // Update camera status indicator back to off when video track is removed
                const cameraStatus = participantElement.querySelector(`#camera-status-${participantElement.dataset.participantId}`);
                if (cameraStatus) {
                    cameraStatus.className = 'w-6 h-6 bg-red-600 rounded-full flex items-center justify-center';
                    const icon = cameraStatus.querySelector('i');
                    if (icon) {
                        icon.className = 'fas fa-video-slash text-white text-xs';
                    }
                }

                // Update camera status for this participant
                setTimeout(() => this.updateParticipantCameraStatus(participant), 100);

                console.log('✅ ROBUST video track removed for:', participant.identity);
            }
        } else if (track.kind === 'audio') {
            // Remove audio element
            const audioElement = document.getElementById(`audio-${participant.identity}`);
            if (audioElement) {
                track.detach(audioElement);
                audioElement.remove();
                console.log('✅ Audio track removed for:', participant.identity);
            }
        }
    }

    checkAndAttachLocalVideo() {
        console.log('🔍 Enhanced checking for local video tracks...');

        if (!this.localParticipant) {
            console.error('❌ No local participant available');
            return;
        }

        // Check if local camera is disabled - if so, don't force video display
        if (this.isLocalCameraEnabled === false) {
            console.log('📴 Local camera is disabled, skipping video attachment');
            return;
        }

        const participantElement = document.getElementById(`participant-${this.localParticipant.identity}`);
        if (!participantElement) {
            console.error('❌ Local participant element not found');
            return;
        }

        // Check if camera is actually active using our enhanced method
        const hasActiveVideo = this.hasActiveVideoTrack(this.localParticipant);
        if (!hasActiveVideo) {
            console.log('📴 No active video track found for local participant, respecting camera-off state');
            return;
        }

        // Get all video tracks from local participant
        const videoTracks = Array.from(this.localParticipant.videoTracks.values());
        console.log('📹 Found video tracks:', videoTracks.length);

        // Debug current state
        const video = participantElement.querySelector('video');
        const placeholder = participantElement.querySelector('.bg-gradient-to-br');
        console.log('🎥 Local video element state:', {
            videoExists: !!video,
            videoSrcObject: video?.srcObject,
            videoDisplay: video?.style.display,
            videoOpacity: video?.classList.contains('opacity-100'),
            placeholderVisible: placeholder && placeholder.style.display !== 'none'
        });

        videoTracks.forEach((publication, index) => {
            console.log(`📹 Video track ${index}:`, {
                source: publication.source,
                track: !!publication.track,
                isMuted: publication.isMuted,
                isSubscribed: publication.isSubscribed
            });
        });

        // Find camera track
        const cameraTrack = videoTracks.find(pub => pub.source === (window.LiveKit?.Track?.Source?.Camera || 'camera'));

        if (cameraTrack && cameraTrack.track) {
            console.log('🎥 Found camera track, manually attaching...');

            if (video) {
                // Detach any existing track
                if (video.srcObject) {
                    video.srcObject = null;
                }

                // Attach the camera track directly
                cameraTrack.track.attach(video);

                // Show video with fade-in effect (using updated structure)
                video.classList.remove('opacity-0');
                video.classList.add('opacity-100');

                // Hide camera-off overlay when video is enabled
                this.hideCameraOffOverlay(participantElement);

                // Hide placeholder with fade-out effect (target the right placeholder)
                if (placeholder) {
                    placeholder.classList.remove('opacity-100');
                    placeholder.classList.add('opacity-0');
                    setTimeout(() => {
                        placeholder.style.display = 'none';
                    }, 300);
                }

                // Show status overlay with participant name
                const statusOverlay = participantElement.querySelector('.absolute.bottom-2');
                if (statusOverlay) {
                    statusOverlay.classList.remove('opacity-0');
                    statusOverlay.classList.add('opacity-100');
                }

                // Update camera status
                setTimeout(() => {
                    this.updateParticipantCameraStatus(this.localParticipant);
                }, 100);

                console.log('✅ Camera track manually attached successfully with new structure');
            } else {
                console.error('❌ Video element not found');
            }
        } else {
            console.warn('⚠️ No camera track available');

            // Try to get any video track
            const anyVideoTrack = videoTracks.find(pub => pub.track);
            if (anyVideoTrack && anyVideoTrack.track) {
                console.log('🎥 Using any available video track...');

                if (video) {
                    anyVideoTrack.track.attach(video);

                    // Show video with fade-in effect (using updated structure)
                    video.classList.remove('opacity-0');
                    video.classList.add('opacity-100');

                    // Hide camera-off overlay when video is enabled
                    this.hideCameraOffOverlay(participantElement);

                    // Hide placeholder with fade-out effect (target the right placeholder)
                    if (placeholder) {
                        placeholder.classList.remove('opacity-100');
                        placeholder.classList.add('opacity-0');
                        setTimeout(() => {
                            placeholder.style.display = 'none';
                        }, 300);
                    }

                    // Show status overlay with participant name
                    const statusOverlay = participantElement.querySelector('.absolute.bottom-2');
                    if (statusOverlay) {
                        statusOverlay.classList.remove('opacity-0');
                        statusOverlay.classList.add('opacity-100');
                    }

                    // Update camera status
                    setTimeout(() => {
                        this.updateParticipantCameraStatus(this.localParticipant);
                    }, 100);

                    console.log('✅ Alternative video track attached with new structure');
                }
            }
        }
    }

    handleDataReceived(payload, participant) {
        console.log('📨 Handling data received:', payload);

        try {
            const data = JSON.parse(new TextDecoder().decode(payload));

            if (data.type === 'hand_raise') {
                console.log(`✋ Hand raise data from ${data.participant}: ${data.raised}`);

                // Update hand raise indicator for the participant
                this.updateHandRaiseIndicator(data.participant, data.raised);

                // Show notification for teachers when students raise hands
                if (this.config.userType === 'quran_teacher' && data.raised) {
                    const studentName = data.participantName || data.participant;
                    this.showNotification(`${studentName} رفع يده`, 'warning');
                }
            } else if (data.type === 'chat') {
                console.log(`💬 Chat message from ${data.sender}: ${data.message}`);

                // Add chat message to UI (from other participants)
                this.addChatMessage(data.message, data.sender, false);
            }
        } catch (error) {
            console.error('❌ Error parsing data payload:', error);
        }
    }

    async toggleMicrophone() {
        console.log('🎤 Toggling microphone...');

        if (!this.localParticipant) {
            console.warn('⚠️ No local participant available');
            this.showNotification('خطأ: لم يتم الاتصال بالجلسة بعد', 'error');
            return;
        }

        try {
            this.isAudioEnabled = !this.isAudioEnabled;

            // Enable/disable microphone
            await this.localParticipant.setMicrophoneEnabled(this.isAudioEnabled);

            // Update UI
            this.updateControlButtons();

            const status = this.isAudioEnabled ? 'مفعل' : 'معطل';
            this.showNotification(`الميكروفون: ${status}`, 'success');

            // Debug audio state and test microphone if enabled
            if (this.isAudioEnabled) {
                this.debugAudioState();
                // Test microphone after a short delay
                setTimeout(() => {
                    this.testMicrophone();
                }, 1000);
            }

            console.log('✅ Microphone toggled:', this.isAudioEnabled);
        } catch (error) {
            console.error('❌ Failed to toggle microphone:', error);
            this.showNotification('خطأ في التحكم بالميكروفون', 'error');
        }
    }

    async debugAudioState() {
        console.log('🔍 Debugging audio state...');

        try {
            // Check if we have microphone permissions
            const permissions = await navigator.permissions.query({ name: 'microphone' });
            console.log('🎤 Microphone permission state:', permissions.state);

            // Check available audio devices
            const devices = await navigator.mediaDevices.enumerateDevices();
            const audioDevices = devices.filter(device => device.kind === 'audioinput');
            console.log('🎤 Available audio devices:', audioDevices);

            // Check if local participant has audio tracks
            const audioTracks = Array.from(this.localParticipant.audioTracks.values());
            console.log('🎤 Local audio tracks:', audioTracks.length);

            audioTracks.forEach((publication, index) => {
                console.log(`🎤 Audio track ${index}:`, {
                    source: publication.source,
                    track: !!publication.track,
                    isMuted: publication.isMuted,
                    isSubscribed: publication.isSubscribed
                });
            });

        } catch (error) {
            console.error('❌ Error debugging audio state:', error);
        }
    }

    toggleCamera() {
        console.log('📹 Toggling camera...');

        if (!this.localParticipant) {
            console.warn('⚠️ No local participant available');
            this.showNotification('خطأ: لم يتم الاتصال بالجلسة بعد', 'error');
            return;
        }

        try {
            this.isVideoEnabled = !this.isVideoEnabled;
            this.isLocalCameraEnabled = this.isVideoEnabled; // Sync the local flag

            this.localParticipant.setCameraEnabled(this.isVideoEnabled);
            this.updateControlButtons();

            const status = this.isVideoEnabled ? 'مفعل' : 'معطل';
            this.showNotification(`الكاميرا: ${status}`, 'success');

            console.log(`📹 Camera toggled: ${this.isVideoEnabled ? 'ON' : 'OFF'}, isLocalCameraEnabled: ${this.isLocalCameraEnabled}`);

            // Immediate state synchronization
            this.syncParticipantVideoState(this.localParticipant, this.isVideoEnabled);

            // Additional updates with delays to ensure LiveKit processes the change
            setTimeout(() => {
                console.log('🔄 First camera status update after toggle...');
                this.updateParticipantCameraStatus(this.localParticipant);
            }, 200);

            setTimeout(() => {
                console.log('🔄 Final camera status update after toggle...');
                this.updateParticipantCameraStatus(this.localParticipant);

                // Only try to attach video if camera is enabled
                if (this.isVideoEnabled && this.isLocalCameraEnabled) {
                    this.checkAndAttachLocalVideo();
                }
            }, 1000);

            console.log('✅ Enhanced camera toggled:', this.isVideoEnabled);
        } catch (error) {
            console.error('❌ Failed to toggle camera:', error);
            this.showNotification('خطأ في التحكم بالكاميرا', 'error');
        }
    }

    forceLocalVideoDisplay() {
        console.log('🔧 Enhanced force displaying local video...');

        if (!this.localParticipant) {
            console.error('❌ No local participant available');
            return;
        }

        // Check if local camera is disabled - if so, don't force video display
        if (this.isLocalCameraEnabled === false) {
            console.log('📴 Local camera is disabled, not forcing video display');
            return;
        }

        // Check if camera is actually active using our enhanced method
        const hasActiveVideo = this.hasActiveVideoTrack(this.localParticipant);
        if (!hasActiveVideo) {
            console.log('📴 No active video track found for local participant, respecting camera-off state');
            return;
        }

        // Try to get the camera stream directly
        navigator.mediaDevices.getUserMedia({ video: true })
            .then(stream => {
                console.log('✅ Got camera stream, attaching to local video...');

                const participantElement = document.getElementById(`participant-${this.localParticipant.identity}`);
                if (participantElement) {
                    const video = participantElement.querySelector('video');
                    if (video) {
                        // Set the stream directly
                        video.srcObject = stream;

                        // Hide placeholder
                        const placeholder = participantElement.querySelector('.absolute.inset-0');
                        if (placeholder) {
                            placeholder.style.display = 'none';
                        }

                        // Show video
                        video.style.display = 'block';

                        console.log('✅ Local video force attached successfully');
                        this.showNotification('تم عرض الفيديو المحلي', 'success');
                    }
                }
            })
            .catch(error => {
                console.error('❌ Failed to get camera stream:', error);
                this.showNotification('خطأ في الوصول للكاميرا', 'error');
            });
    }

    updateControlButtons() {
        console.log('🎨 Updating control buttons...');

        // Update microphone button
        if (this.elements.micBtn) {
            this.elements.micBtn.className = 'w-12 h-12 sm:w-14 sm:h-14 rounded-full text-white flex items-center justify-center transition-all duration-200 hover:scale-105 focus:outline-none focus:ring-2 active:scale-95';
            if (this.isAudioEnabled) {
                this.elements.micBtn.classList.add('bg-green-600', 'hover:bg-green-500', 'focus:ring-green-500');
            } else {
                this.elements.micBtn.classList.add('bg-red-600', 'hover:bg-red-700', 'focus:ring-red-500');
            }
        }

        // Update camera button
        if (this.elements.cameraBtn) {
            this.elements.cameraBtn.className = 'w-12 h-12 sm:w-14 sm:h-14 rounded-full text-white flex items-center justify-center transition-all duration-200 hover:scale-105 focus:outline-none focus:ring-2 active:scale-95';
            if (this.isVideoEnabled) {
                this.elements.cameraBtn.classList.add('bg-green-600', 'hover:bg-green-500', 'focus:ring-green-500');
            } else {
                this.elements.cameraBtn.classList.add('bg-red-600', 'hover:bg-red-700', 'focus:ring-red-500');
            }
        }

        // Update screen share button
        if (this.elements.screenShareBtn) {
            this.elements.screenShareBtn.className = 'w-12 h-12 sm:w-14 sm:h-14 rounded-full text-white flex items-center justify-center transition-all duration-200 hover:scale-105 focus:outline-none focus:ring-2 active:scale-95';
            if (this.isScreenSharing) {
                this.elements.screenShareBtn.classList.add('bg-blue-600', 'hover:bg-blue-500', 'focus:ring-blue-500');
            } else {
                this.elements.screenShareBtn.classList.add('bg-gray-600', 'hover:bg-gray-500', 'focus:ring-blue-500');
            }
        }

        // Update hand raise button
        if (this.elements.handRaiseBtn) {
            this.elements.handRaiseBtn.className = 'w-12 h-12 sm:w-14 sm:h-14 rounded-full text-white flex items-center justify-center transition-all duration-200 hover:scale-105 focus:outline-none focus:ring-2 active:scale-95';
            if (this.isHandRaised) {
                this.elements.handRaiseBtn.classList.add('bg-orange-600', 'hover:bg-orange-500', 'focus:ring-orange-500');
            } else {
                this.elements.handRaiseBtn.classList.add('bg-gray-600', 'hover:bg-orange-500', 'focus:ring-orange-500');
            }
        }

        // Update recording button
        if (this.elements.recordBtn) {
            this.elements.recordBtn.className = 'w-12 h-12 sm:w-14 sm:h-14 rounded-full text-white flex items-center justify-center transition-all duration-200 hover:scale-105 focus:outline-none focus:ring-2 active:scale-95';
            if (this.isRecording) {
                this.elements.recordBtn.classList.add('bg-red-600', 'hover:bg-red-700', 'focus:ring-red-500');
            } else {
                this.elements.recordBtn.classList.add('bg-gray-600', 'hover:bg-red-500', 'focus:ring-red-500');
            }
        }

        console.log('✅ Control buttons updated');
    }

    toggleScreenShare() {
        console.log('🖥️ Toggle screen share');

        if (!this.localParticipant) {
            console.warn('⚠️ No local participant available');
            this.showNotification('خطأ: لم يتم الاتصال بالجلسة بعد', 'error');
            return;
        }

        try {
            if (this.isScreenSharing) {
                // Stop screen sharing
                this.localParticipant.setScreenShareEnabled(false);
                this.isScreenSharing = false;
                this.showNotification('تم إيقاف مشاركة الشاشة', 'success');
            } else {
                // Start screen sharing
                this.localParticipant.setScreenShareEnabled(true);
                this.isScreenSharing = true;
                this.showNotification('تم بدء مشاركة الشاشة', 'success');
            }

            this.updateControlButtons();
            console.log('✅ Screen share toggled:', this.isScreenSharing);
        } catch (error) {
            console.error('❌ Failed to toggle screen share:', error);
            this.showNotification('خطأ في مشاركة الشاشة', 'error');
        }
    }

    toggleHandRaise() {
        console.log('✋ Toggle hand raise');

        // Only students can raise hands
        if (this.config.userType === 'quran_teacher') {
            this.showNotification('المعلمون لا يمكنهم رفع أيديهم', 'info');
            return;
        }

        try {
            this.isHandRaised = !this.isHandRaised;
            this.updateControlButtons();

            const status = this.isHandRaised ? 'مرفوعة' : 'منخفضة';
            this.showNotification(`اليد: ${status}`, 'success');

            // Update hand raise indicator on local participant video
            this.updateHandRaiseIndicator(this.localParticipant.identity, this.isHandRaised);

            // Send hand raise data to other participants
            if (this.room) {
                this.room.localParticipant.publishData(
                    new TextEncoder().encode(JSON.stringify({
                        type: 'hand_raise',
                        raised: this.isHandRaised,
                        participant: this.localParticipant.identity,
                        participantName: this.config.participantName
                    })),
                    LiveKit.DataPacket_Kind.RELIABLE
                );
            }

            console.log('✅ Hand raise toggled:', this.isHandRaised);
        } catch (error) {
            console.error('❌ Failed to toggle hand raise:', error);
            this.showNotification('خطأ في رفع اليد', 'error');
        }
    }

    updateHandRaiseIndicator(participantId, isRaised) {
        console.log(`🎯 Updating hand raise indicator for ${participantId}: ${isRaised}`);

        const participantElement = document.getElementById(`participant-${participantId}`);
        if (!participantElement) {
            console.warn(`⚠️ Participant element not found for ${participantId}`);
            return;
        }

        // Remove existing indicator
        const existingIndicator = participantElement.querySelector('.hand-raise-indicator');
        if (existingIndicator) {
            existingIndicator.remove();
        }

        // Add indicator if hand is raised
        if (isRaised) {
            const indicator = document.createElement('div');
            indicator.className = 'hand-raise-indicator';
            indicator.innerHTML = `
                <i class="fa-solid fa-hand"></i>
            `;
            participantElement.appendChild(indicator);
            console.log(`✅ Hand raise indicator added for ${participantId}`);
        }
    }

    toggleChat() {
        console.log('💬 Toggle chat');

        try {
            const chatSidebar = document.getElementById('chatSidebar');
            if (chatSidebar) {
                chatSidebar.classList.toggle('hidden');
                const isVisible = !chatSidebar.classList.contains('hidden');
                this.showNotification(`الدردشة: ${isVisible ? 'مفتوحة' : 'مغلقة'}`, 'success');
            } else {
                this.showNotification('ميزة الدردشة قيد التطوير', 'info');
            }
        } catch (error) {
            console.error('❌ Failed to toggle chat:', error);
            this.showNotification('خطأ في فتح الدردشة', 'error');
        }
    }

    toggleParticipantsList() {
        console.log('👥 Toggle participants list');

        try {
            const participantsSidebar = document.getElementById('participantsSidebar');
            if (participantsSidebar) {
                participantsSidebar.classList.toggle('hidden');
                const isVisible = !participantsSidebar.classList.contains('hidden');
                this.showNotification(`قائمة المشاركين: ${isVisible ? 'مفتوحة' : 'مغلقة'}`, 'success');
            } else {
                this.showNotification('قائمة المشاركين قيد التطوير', 'info');
            }
        } catch (error) {
            console.error('❌ Failed to toggle participants list:', error);
            this.showNotification('خطأ في فتح قائمة المشاركين', 'error');
        }
    }

    toggleRecording() {
        console.log('🔴 Toggle recording');

        if (!this.localParticipant) {
            console.warn('⚠️ No local participant available');
            this.showNotification('خطأ: لم يتم الاتصال بالجلسة بعد', 'error');
            return;
        }

        try {
            this.isRecording = !this.isRecording;
            this.updateControlButtons();

            const status = this.isRecording ? 'مفعل' : 'معطل';
            this.showNotification(`التسجيل: ${status}`, 'success');

            // Here you would typically call your recording API
            if (this.isRecording) {
                // Start recording
                this.startRecording();
            } else {
                // Stop recording
                this.stopRecording();
            }

            console.log('✅ Recording toggled:', this.isRecording);
        } catch (error) {
            console.error('❌ Failed to toggle recording:', error);
            this.showNotification('خطأ في التحكم بالتسجيل', 'error');
        }
    }

    startRecording() {
        console.log('🔴 Starting recording...');
        // Implement recording start logic
        this.showNotification('تم بدء التسجيل', 'success');
    }

    stopRecording() {
        console.log('⏹️ Stopping recording...');
        // Implement recording stop logic
        this.showNotification('تم إيقاف التسجيل', 'success');
    }

    // Sidebar Management
    toggleSidebar(type) {
        if (!this.elements.meetingSidebar) return;

        const isCurrentlyVisible = !this.elements.meetingSidebar.classList.contains('translate-x-full');

        if (isCurrentlyVisible && this.currentSidebarType === type) {
            // If clicking the same button and sidebar is visible, close it
            this.closeSidebar();
        } else {
            // Open with the new type
            this.openSidebar(type);
        }
    }

    openSidebar(type) {
        console.log(`📋 Opening sidebar: ${type}`);

        if (!this.elements.meetingSidebar) return;

        try {
            // Store current type
            this.currentSidebarType = type;

            // Hide all panels first
            if (this.elements.chatPanel) this.elements.chatPanel.classList.add('hidden');
            if (this.elements.participantsPanel) this.elements.participantsPanel.classList.add('hidden');
            if (this.elements.settingsPanel) this.elements.settingsPanel.classList.add('hidden');

            // Show the appropriate panel and update title
            switch (type) {
                case 'chat':
                    console.log('📋 Showing chat panel');
                    if (this.elements.chatPanel) {
                        this.elements.chatPanel.classList.remove('hidden');
                        console.log('✅ Chat panel shown');
                    } else {
                        console.warn('⚠️ Chat panel element not found');
                    }
                    if (this.elements.sidebarTitle) this.elements.sidebarTitle.textContent = 'الدردشة';
                    this.markChatAsRead();
                    break;
                case 'participants':
                    console.log('📋 Showing participants panel');
                    if (this.elements.participantsPanel) {
                        this.elements.participantsPanel.classList.remove('hidden');
                        console.log('✅ Participants panel shown');
                    } else {
                        console.warn('⚠️ Participants panel element not found');
                    }
                    if (this.elements.sidebarTitle) this.elements.sidebarTitle.textContent = 'المشاركون';
                    console.log('🔄 Calling updateParticipantsList from openSidebar');
                    this.updateParticipantsList();
                    console.log('✅ updateParticipantsList called from openSidebar');
                    break;
                case 'settings':
                    console.log('📋 Showing settings panel');
                    if (this.elements.settingsPanel) {
                        this.elements.settingsPanel.classList.remove('hidden');
                        console.log('✅ Settings panel shown');
                    } else {
                        console.warn('⚠️ Settings panel element not found');
                    }
                    if (this.elements.sidebarTitle) this.elements.sidebarTitle.textContent = 'الإعدادات';
                    this.updateSettingsPanel();
                    break;
            }

            // Show sidebar by sliding it in from the left
            this.elements.meetingSidebar.classList.remove('-translate-x-full');

            // Ensure sidebar is positioned properly when visible
            this.elements.meetingSidebar.style.position = 'relative';
            this.elements.meetingSidebar.style.removeProperty('left');
            this.elements.meetingSidebar.style.removeProperty('top');

            // Adjust video area on larger screens
            this.adjustVideoAreaForSidebar(true);

            // Update responsive layout for sidebar open state
            setTimeout(() => {
                this.updateSidebarState();
            }, 300);

            // Update button states
            this.updateSidebarButtonStates(type);

        } catch (error) {
            console.error('❌ Failed to open sidebar:', error);
            this.showNotification('خطأ في فتح اللوحة الجانبية', 'error');
        }
    }

    closeSidebar() {
        console.log('📋 Closing sidebar');

        if (!this.elements.meetingSidebar) return;

        try {
            console.log('📋 Hiding sidebar, current type:', this.currentSidebarType);
            // Hide sidebar by sliding it back to the left
            this.elements.meetingSidebar.classList.add('-translate-x-full');

            // Reposition sidebar to absolute when hidden
            this.elements.meetingSidebar.style.position = 'absolute';
            this.elements.meetingSidebar.style.left = '0';
            this.elements.meetingSidebar.style.top = '0';

            this.currentSidebarType = null;

            // Adjust video area back to full flex
            this.adjustVideoAreaForSidebar(false);

            // Update responsive layout for sidebar closed state
            setTimeout(() => {
                this.updateSidebarState();
            }, 300);

            // Reset button states
            this.updateSidebarButtonStates(null);

        } catch (error) {
            console.error('❌ Failed to close sidebar:', error);
        }
    }

    updateSidebarButtonStates(activeType) {
        // Reset all button states
        const buttons = [
            { element: this.elements.chatToggleBtn, type: 'chat' },
            { element: this.elements.participantsToggleBtn, type: 'participants' },
            { element: this.elements.settingsBtn, type: 'settings' }
        ];

        buttons.forEach(({ element, type }) => {
            if (element) {
                if (type === activeType) {
                    // Active state
                    element.classList.remove('bg-gray-600');
                    element.classList.add('bg-blue-600', 'hover:bg-blue-700');
                } else {
                    // Inactive state
                    element.classList.remove('bg-blue-600', 'hover:bg-blue-700');
                    element.classList.add('bg-gray-600', 'hover:bg-gray-500');
                }
            }
        });
    }

    adjustVideoAreaForSidebar(sidebarOpen) {
        const videoArea = document.getElementById('videoArea');
        if (!videoArea) return;

        if (sidebarOpen) {
            // Reduce video area flex to make room for sidebar
            videoArea.classList.remove('flex-1');
            videoArea.classList.add('w-[calc(100%-24rem)]');
            console.log('📏 Video area width reduced for sidebar');
        } else {
            // Reset to full flex and center the video
            videoArea.classList.remove('w-[calc(100%-24rem)]');
            videoArea.classList.add('flex-1');

            // Remove inline styles to let flexbox handle the width
            videoArea.style.removeProperty('width');
            videoArea.style.removeProperty('maxWidth');
            videoArea.style.removeProperty('flex');

            // Center the video content
            const videoGrid = document.getElementById('videoGrid');
            if (videoGrid) {
                videoGrid.style.alignContent = 'center';
                videoGrid.style.justifyContent = 'center';
            }
            console.log('📏 Video area width restored to full flex');
        }
    }

    // Chat Functionality
    sendChatMessage() {
        if (!this.elements.chatInput) return;

        const message = this.elements.chatInput.value.trim();
        if (!message) return;

        try {
            console.log('💬 Sending chat message:', message);

            // Send via LiveKit data channel
            if (this.room && this.room.localParticipant) {
                const senderName = this.config.userName || this.config.participantName || this.room.localParticipant.identity;
                const data = JSON.stringify({
                    type: 'chat',
                    message: message,
                    sender: senderName,
                    timestamp: new Date().toISOString()
                });

                // Send to all participants
                this.room.localParticipant.publishData(
                    new TextEncoder().encode(data),
                    LiveKit.DataPacket_Kind.RELIABLE
                );
            }

            // Add to local chat
            const senderName = this.config.userName || this.config.participantName || this.room.localParticipant.identity;
            this.addChatMessage(message, senderName, true);

            // Clear input
            this.elements.chatInput.value = '';

        } catch (error) {
            console.error('❌ Failed to send chat message:', error);
            this.showNotification('خطأ في إرسال الرسالة', 'error');
        }
    }

    addChatMessage(message, sender, isOwn = false) {
        if (!this.elements.chatMessages) return;

        try {
            const messageDiv = document.createElement('div');
            messageDiv.className = `flex ${isOwn ? 'justify-end' : 'justify-start'}`;

            const timestamp = new Date().toLocaleTimeString('ar-EG', {
                hour: '2-digit',
                minute: '2-digit'
            });

            messageDiv.innerHTML = `
                <div class="${isOwn ? 'bg-blue-600' : 'bg-gray-700'} text-white rounded-lg px-3 py-2 max-w-xs break-words">
                    <div class="font-semibold text-xs mb-1">${sender}</div>
                    <div class="text-sm">${message}</div>
                    <div class="text-xs opacity-75 mt-1">${timestamp}</div>
                </div>
            `;

            this.elements.chatMessages.appendChild(messageDiv);

            // Scroll to bottom
            this.elements.chatMessages.scrollTop = this.elements.chatMessages.scrollHeight;

            // Show notification if sidebar is closed or showing different panel
            if (this.currentSidebarType !== 'chat') {
                this.showChatNotification();
            }

        } catch (error) {
            console.error('❌ Failed to add chat message:', error);
        }
    }

    showChatNotification() {
        if (this.elements.chatToggleBtn) {
            // Add notification badge
            let badge = this.elements.chatToggleBtn.querySelector('.chat-badge');
            if (!badge) {
                badge = document.createElement('div');
                badge.className = 'chat-badge absolute -top-1 -right-1 w-3 h-3 bg-red-500 rounded-full';
                this.elements.chatToggleBtn.appendChild(badge);
            }
        }
    }

    markChatAsRead() {
        if (this.elements.chatToggleBtn) {
            const badge = this.elements.chatToggleBtn.querySelector('.chat-badge');
            if (badge) {
                badge.remove();
            }
        }
    }

    // Participants Management - COMPLETELY REWRITTEN FOR SAFETY
    updateParticipantsList() {
        // BULLETPROOF: Check all prerequisites
        if (!this.elements || !this.elements.participantsList) {
            console.log('⚠️ Cannot update participants list: missing participantsList element');
            return;
        }

        if (!this.room) {
            console.log('⚠️ Cannot update participants list: room not available');
            return;
        }

        try {
            console.log('👥 SAFE: Updating participants list');

            // BULLETPROOF: Get participants with multiple safety checks
            const participants = [];

            // Add remote participants safely
            console.log('🔍 Checking remote participants...');
            console.log('🔍 this.room.remoteParticipants:', this.room.remoteParticipants);

            // Get remote participants using the latest LiveKit SDK API
            let remoteParticipants = [];

            try {
                // Latest LiveKit SDK uses room.remoteParticipants as a Map
                if (this.room.remoteParticipants && this.room.remoteParticipants instanceof Map) {
                    remoteParticipants = Array.from(this.room.remoteParticipants.values());
                    console.log('✅ Got remote participants via room.remoteParticipants Map:', remoteParticipants.length);
                } else if (this.room.remoteParticipants && typeof this.room.remoteParticipants.values === 'function') {
                    // Fallback for older versions
                    remoteParticipants = Array.from(this.room.remoteParticipants.values());
                    console.log('✅ Got remote participants via room.remoteParticipants.values():', remoteParticipants.length);
                } else {
                    console.log('⚠️ room.remoteParticipants not available, trying alternative methods...');

                    // Try alternative methods if remoteParticipants is not available
                    if (this.room.participants && typeof this.room.participants.values === 'function') {
                        const allParticipants = Array.from(this.room.participants.values());
                        remoteParticipants = allParticipants.filter(p => p !== this.room.localParticipant);
                        console.log('✅ Got remote participants via room.participants:', remoteParticipants.length);
                    }
                }
            } catch (error) {
                console.error('❌ Error getting remote participants:', error);
            }

            if (remoteParticipants.length > 0) {
                participants.push(...remoteParticipants);
                console.log('✅ Added remote participants:', remoteParticipants.length);
            } else {
                console.log('ℹ️ No remote participants found with any method');

                // Fallback: Use participants from our internal Map
                if (this.participants.size > 0) {
                    console.log('🔄 Using fallback: participants from internal Map');
                    const mapParticipants = Array.from(this.participants.values());
                    // Filter out local participant
                    const remoteFromMap = mapParticipants.filter(p => p !== this.localParticipant);
                    participants.push(...remoteFromMap);
                    console.log('✅ Added remote participants from Map fallback:', remoteFromMap.length);
                }
            }

            // Add local participant safely
            console.log('🔍 Checking local participant...');
            console.log('🔍 this.room.localParticipant:', this.room.localParticipant);
            console.log('🔍 this.localParticipant:', this.localParticipant);

            // Try different ways to get local participant
            let localParticipant = null;

            if (this.room.localParticipant) {
                localParticipant = this.room.localParticipant;
                console.log('✅ Method 1: Got local participant from room.localParticipant');
            } else if (this.localParticipant) {
                localParticipant = this.localParticipant;
                console.log('✅ Method 2: Got local participant from this.localParticipant');
            } else if (this.room.state && this.room.state.localParticipant) {
                localParticipant = this.room.state.localParticipant;
                console.log('✅ Method 3: Got local participant from room.state.localParticipant');
            }

            if (localParticipant) {
                participants.push(localParticipant);
                console.log('✅ Added local participant:', localParticipant.identity);
            } else {
                console.log('ℹ️ No local participant found with any method');
            }

            console.log('📊 Total participants to display:', participants.length);
            console.log('🔍 this.participants Map size:', this.participants.size);
            console.log('🔍 this.participants Map contents:', Array.from(this.participants.keys()));

            // Clear the list
            this.elements.participantsList.innerHTML = '';

            // Add a test entry to verify the list is working
            if (participants.length === 0) {
                // Try to get participants from the participants Map as fallback
                if (this.participants.size > 0) {
                    console.log('🔄 Using participants from this.participants Map as fallback');
                    const mapParticipants = Array.from(this.participants.values());
                    participants.push(...mapParticipants);
                    console.log('✅ Added participants from Map:', mapParticipants.length);
                } else {
                    const testDiv = document.createElement('div');
                    testDiv.className = 'flex items-center justify-between bg-gray-700 rounded-lg p-3';
                    testDiv.innerHTML = `
                        <div class="flex items-center gap-3">
                            <div class="w-8 h-8 bg-blue-600 rounded-full flex items-center justify-center text-white font-semibold text-sm">
                                T
                            </div>
                            <div>
                                <div class="text-white font-medium">Test Participant</div>
                                <div class="text-xs">
                                    <span class="text-gray-400">طالب</span>
                                </div>
                            </div>
                        </div>
                        <div class="flex items-center gap-2">
                            <div class="w-6 h-6 rounded-full flex items-center justify-center bg-red-600">
                                <svg class="w-3 h-3 text-white" fill="currentColor" viewBox="0 0 20 20">
                                    <path d="M7 4a3 3 0 016 0v4a3 3 0 11-6 0V4zm4 10.93A7.001 7.001 0 0017 8a1 1 0 10-2 0A5 5 0 015 8a1 1 0 00-2 0 7.001 7.001 0 006 6.93V17H6a1 1 0 100 2h8a1 1 0 100-2h-3v-2.07z"/>
                                </svg>
                            </div>
                            <div class="w-6 h-6 rounded-full flex items-center justify-center bg-red-600">
                                <svg class="w-3 h-3 text-white" fill="currentColor" viewBox="0 0 20 20">
                                    <path d="M2 6a2 2 0 012-2h6a2 2 0 012 2v8a2 2 0 01-2 2H4a2 2 0 01-2-2V6zM14.553 7.106A1 1 0 0014 8v4a1 1 0 00.553.894l2 1A1 1 0 0018 13V7a1 1 0 00-1.447-.894l-2 1z"/>
                                </svg>
                            </div>
                        </div>
                    `;
                    this.elements.participantsList.appendChild(testDiv);
                    console.log('🧪 Added test participant to verify list functionality');
                }
            }

            // BULLETPROOF: Process each participant with maximum safety
            participants.forEach((participant, index) => {
                try {
                    console.log(`👤 Processing participant ${index + 1}:`, participant.identity || 'Unknown');

                    const participantDiv = document.createElement('div');
                    participantDiv.className = 'flex items-center justify-between bg-gray-700 rounded-lg p-3';

                    // ULTRA SAFE: Check audio and video tracks with multiple fallbacks
                    let audioEnabled = false;
                    let videoEnabled = false;

                    try {
                        // Check audio tracks with maximum safety
                        if (participant.audioTracks && typeof participant.audioTracks.values === 'function') {
                            const audioTracks = Array.from(participant.audioTracks.values());
                            audioEnabled = audioTracks.length > 0 && audioTracks.some(track => track && !track.isMuted);
                        }
                    } catch (error) {
                        console.warn('⚠️ Error checking audio tracks for', participant.identity, error);
                    }

                    try {
                        // Check video tracks with maximum safety
                        if (participant.videoTracks && typeof participant.videoTracks.values === 'function') {
                            const videoTracks = Array.from(participant.videoTracks.values());
                            videoEnabled = videoTracks.length > 0 && videoTracks.some(track => track && !track.isMuted);
                        }
                    } catch (error) {
                        console.warn('⚠️ Error checking video tracks for', participant.identity, error);
                    }

                    participantDiv.innerHTML = `
                    <div class="flex items-center gap-3">
                        <div class="w-8 h-8 bg-blue-600 rounded-full flex items-center justify-center text-white font-semibold text-sm">
                            ${participant.identity.charAt(0).toUpperCase()}
                        </div>
                        <div>
                            <div class="text-white font-medium">${participant.identity}${participant === this.room.localParticipant ? ' (أنت)' : ''}</div>
                            <div class="text-xs">
                                ${this.isParticipantTeacher(participant) ? '<span class="text-green-400">معلم</span>' : '<span class="text-gray-400">طالب</span>'}
                            </div>
                        </div>
                    </div>
                    <div class="flex items-center gap-2">
                        <div class="w-6 h-6 rounded-full flex items-center justify-center ${audioEnabled ? 'bg-green-600' : 'bg-red-600'}">
                            <svg class="w-3 h-3 text-white" fill="currentColor" viewBox="0 0 20 20">
                                <path d="M7 4a3 3 0 016 0v4a3 3 0 11-6 0V4zm4 10.93A7.001 7.001 0 0017 8a1 1 0 10-2 0A5 5 0 015 8a1 1 0 00-2 0 7.001 7.001 0 006 6.93V17H6a1 1 0 100 2h8a1 1 0 100-2h-3v-2.07z"/>
                            </svg>
                        </div>
                        <div class="w-6 h-6 rounded-full flex items-center justify-center ${videoEnabled ? 'bg-green-600' : 'bg-red-600'}">
                            <svg class="w-3 h-3 text-white" fill="currentColor" viewBox="0 0 20 20">
                                <path d="M2 6a2 2 0 012-2h6a2 2 0 012 2v8a2 2 0 01-2 2H4a2 2 0 01-2-2V6zM14.553 7.106A1 1 0 0014 8v4a1 1 0 00.553.894l2 1A1 1 0 0018 13V7a1 1 0 00-1.447-.894l-2 1z"/>
                            </svg>
                        </div>
                    </div>
                `;

                    this.elements.participantsList.appendChild(participantDiv);
                    console.log('✅ Added participant to list:', participant.identity || 'Unknown');

                } catch (participantError) {
                    console.error('❌ Error processing individual participant:', participantError);
                }
            });

            console.log('✅ SAFE: Participants list updated successfully');

        } catch (error) {
            console.error('❌ CRITICAL: Failed to update participants list:', error);
            console.error('Error details:', error.stack);
        }
    }

    // Settings Management
    async updateSettingsPanel() {
        if (!this.elements.cameraSelect || !this.elements.microphoneSelect) return;

        try {
            console.log('⚙️ Updating settings panel');

            // Get available devices
            const devices = await navigator.mediaDevices.enumerateDevices();

            // Update camera select
            const cameras = devices.filter(device => device.kind === 'videoinput');
            this.elements.cameraSelect.innerHTML = '<option value="">اختر الكاميرا</option>';
            cameras.forEach(camera => {
                const option = document.createElement('option');
                option.value = camera.deviceId;
                option.textContent = camera.label || `كاميرا ${camera.deviceId.substr(0, 5)}`;
                this.elements.cameraSelect.appendChild(option);
            });

            // Update microphone select
            const microphones = devices.filter(device => device.kind === 'audioinput');
            this.elements.microphoneSelect.innerHTML = '<option value="">اختر الميكروفون</option>';
            microphones.forEach(microphone => {
                const option = document.createElement('option');
                option.value = microphone.deviceId;
                option.textContent = microphone.label || `ميكروفون ${microphone.deviceId.substr(0, 5)}`;
                this.elements.microphoneSelect.appendChild(option);
            });

            // Add change handlers
            this.elements.cameraSelect.addEventListener('change', (e) => {
                this.switchCamera(e.target.value);
            });

            this.elements.microphoneSelect.addEventListener('change', (e) => {
                this.switchMicrophone(e.target.value);
            });

            this.elements.videoQualitySelect.addEventListener('change', (e) => {
                this.changeVideoQuality(e.target.value);
            });

        } catch (error) {
            console.error('❌ Failed to update settings panel:', error);
            this.showNotification('خطأ في تحميل الإعدادات', 'error');
        }
    }

    async switchCamera(deviceId) {
        if (!this.room || !deviceId) return;

        try {
            console.log('📹 Switching camera:', deviceId);
            await this.room.switchActiveDevice('videoinput', deviceId);
            this.showNotification('تم تغيير الكاميرا', 'success');
        } catch (error) {
            console.error('❌ Failed to switch camera:', error);
            this.showNotification('خطأ في تغيير الكاميرا', 'error');
        }
    }

    async switchMicrophone(deviceId) {
        if (!this.room || !deviceId) return;

        try {
            console.log('🎤 Switching microphone:', deviceId);
            await this.room.switchActiveDevice('audioinput', deviceId);
            this.showNotification('تم تغيير الميكروفون', 'success');
        } catch (error) {
            console.error('❌ Failed to switch microphone:', error);
            this.showNotification('خطأ في تغيير الميكروفون', 'error');
        }
    }

    changeVideoQuality(quality) {
        // This would be implemented based on LiveKit's video quality settings
        console.log('🎥 Changing video quality:', quality);
        this.showNotification('تم تغيير جودة الفيديو', 'info');
    }

    leaveMeeting() {
        console.log('🚪 Leaving meeting');

        try {
            // Show elegant modal instead of browser confirm
            this.showLeaveConfirmModal();
        } catch (error) {
            console.error('❌ Failed to leave meeting:', error);
            this.showNotification('خطأ في مغادرة الجلسة', 'error');
        }
    }

    showLeaveConfirmModal() {
        // Check if modal already exists to prevent duplicates
        const existingModal = document.getElementById('leaveConfirmModal');
        if (existingModal) {
            console.log('⚠️ Leave modal already exists, removing it first');
            existingModal.remove();
        }

        // Create elegant modal
        const modal = document.createElement('div');
        modal.id = 'leaveConfirmModal';
        modal.className = 'fixed inset-0 bg-black bg-opacity-75 flex items-center justify-center z-50 animate-fadeIn';
        modal.style.zIndex = '9999';

        modal.innerHTML = `
            <div class="bg-white rounded-2xl shadow-2xl max-w-md w-full mx-4 transform animate-scaleIn">
                <div class="p-6">
                    <!-- Icon -->
                    <div class="flex justify-center mb-4">
                        <div class="w-16 h-16 bg-red-100 rounded-full flex items-center justify-center">
                            <svg class="w-8 h-8 text-red-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" 
                                      d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1">
                                </path>
                            </svg>
                        </div>
                    </div>
                    
                    <!-- Title -->
                    <h3 class="text-xl font-bold text-gray-900 text-center mb-2">
                        مغادرة الجلسة
                    </h3>
                    
                    <!-- Message -->
                    <p class="text-gray-600 text-center mb-6">
                        هل أنت متأكد من رغبتك في مغادرة الجلسة؟ سيتم قطع الاتصال مع جميع المشاركين.
                    </p>
                    
                    <!-- Buttons -->
                    <div class="flex gap-3">
                        <button id="cancelLeaveBtn" class="flex-1 px-4 py-3 bg-gray-100 hover:bg-gray-200 text-gray-800 rounded-lg font-semibold transition-colors duration-200">
                            إلغاء
                        </button>
                        <button id="confirmLeaveBtn" class="flex-1 px-4 py-3 bg-red-600 hover:bg-red-700 text-white rounded-lg font-semibold transition-colors duration-200">
                            مغادرة
                        </button>
                    </div>
                </div>
            </div>
        `;

        // Add CSS animations
        const style = document.createElement('style');
        style.textContent = `
            @keyframes fadeIn {
                from { opacity: 0; }
                to { opacity: 1; }
            }
            @keyframes scaleIn {
                from { transform: scale(0.9); opacity: 0; }
                to { transform: scale(1); opacity: 1; }
            }
            .animate-fadeIn {
                animation: fadeIn 0.3s ease-out;
            }
            .animate-scaleIn {
                animation: scaleIn 0.3s ease-out;
            }
        `;
        document.head.appendChild(style);

        // Add to body
        document.body.appendChild(modal);

        // Add event listeners
        const cancelBtn = modal.querySelector('#cancelLeaveBtn');
        const confirmBtn = modal.querySelector('#confirmLeaveBtn');

        const closeModal = () => {
            modal.style.opacity = '0';
            setTimeout(() => {
                document.body.removeChild(modal);
                document.head.removeChild(style);
            }, 300);
        };

        cancelBtn.addEventListener('click', closeModal);

        confirmBtn.addEventListener('click', () => {
            closeModal();

            try {
                // Clean up focus mode before disconnecting
                this.cleanupFocusMode();

                if (this.room) {
                    this.room.disconnect();
                }
                this.showNotification('تم مغادرة الجلسة', 'success');
                setTimeout(() => {
                    window.location.reload();
                }, 1000);
            } catch (error) {
                console.error('❌ Failed to disconnect:', error);
                this.showNotification('خطأ في مغادرة الجلسة', 'error');
            }
        });

        // Close on background click
        modal.addEventListener('click', (e) => {
            if (e.target === modal) {
                closeModal();
            }
        });

        console.log('✅ Leave confirmation modal shown');
    }

    showNotification(message, type = 'info') {
        console.log(`📢 Notification [${type}]:`, message);

        // Create notification element
        const notification = document.createElement('div');
        notification.className = `fixed top-4 right-4 z-50 p-4 rounded-lg shadow-lg transition-all duration-300 transform translate-x-full`;

        // Set background color based on type
        switch (type) {
            case 'success':
                notification.className += ' bg-green-500 text-white';
                break;
            case 'error':
                notification.className += ' bg-red-500 text-white';
                break;
            case 'warning':
                notification.className += ' bg-yellow-500 text-white';
                break;
            default:
                notification.className += ' bg-blue-500 text-white';
        }

        notification.textContent = message;

        // Add to page
        document.body.appendChild(notification);

        // Animate in
        setTimeout(() => {
            notification.classList.remove('translate-x-full');
        }, 100);

        // Remove after 3 seconds
        setTimeout(() => {
            notification.classList.add('translate-x-full');
            setTimeout(() => {
                if (notification.parentNode) {
                    notification.parentNode.removeChild(notification);
                }
            }, 300);
        }, 3000);
    }

    // Helper methods for checking participant media status
    isParticipantAudioEnabled(participant) {
        const audioTracks = participant.audioTracks || new Map();
        return audioTracks.size > 0 &&
            Array.from(audioTracks.values()).some(pub => !pub.isMuted);
    }

    isParticipantVideoEnabled(participant) {
        const videoTracks = participant.videoTracks || new Map();
        return videoTracks.size > 0 &&
            Array.from(videoTracks.values()).some(pub => !pub.isMuted);
    }

    updateConnectionStatus(status) {
        const statusElement = document.getElementById('connectionStatus');
        const statusText = document.getElementById('connectionText');

        if (statusElement && statusText) {
            statusElement.style.display = 'flex';

            switch (status) {
                case 'connected':
                    statusText.textContent = 'متصل';
                    statusElement.style.color = '#10b981';
                    break;
                case 'connecting':
                    statusText.textContent = 'جاري الاتصال...';
                    statusElement.style.color = '#f59e0b';
                    break;
                case 'disconnected':
                    statusText.textContent = 'غير متصل';
                    statusElement.style.color = '#ef4444';
                    break;
                case 'error':
                    statusText.textContent = 'خطأ في الاتصال';
                    statusElement.style.color = '#ef4444';
                    break;
                default:
                    statusText.textContent = 'غير متصل';
                    statusElement.style.color = '#6b7280';
            }
        }
    }

    destroy() {
        console.log('🧹 Destroying enhanced ProfessionalLiveKitMeeting...');

        // Stop meeting timer
        this.stopMeetingTimer();

        // Clear video state synchronization interval
        if (this.videoStateCheckInterval) {
            clearInterval(this.videoStateCheckInterval);
            this.videoStateCheckInterval = null;
        }

        // Clean up resize observer
        if (this.gridResizeObserver) {
            this.gridResizeObserver.disconnect();
            this.gridResizeObserver = null;
        }

        // Clean up responsive observer
        if (this.responsiveObserver) {
            this.responsiveObserver.disconnect();
            this.responsiveObserver = null;
        }

        // Disconnect from room
        if (this.room) {
            this.room.disconnect();
            this.room = null;
        }

        // Clear participant maps
        if (this.participants) {
            this.participants.clear();
        }
        if (this.participantVideoStates) {
            this.participantVideoStates.clear();
        }

        // Remove event listeners
        this.eventListeners.forEach((listener, element) => {
            element.removeEventListener('click', listener);
        });
        this.eventListeners.clear();

        console.log('✅ Enhanced ProfessionalLiveKitMeeting destroyed');
    }

    // Add this method after the constructor
    initializeButtons() {
        console.log('🔧 Initializing buttons...');

        // Set up event listeners immediately
        this.setupEventListeners();

        // Initialize button states
        this.updateControlButtons();

        // Add loading states for buttons that require connection
        this.addButtonLoadingStates();

        console.log('✅ Buttons initialized');
    }

    addButtonLoadingStates() {
        // Add loading state to buttons that require room connection
        const buttonsRequiringConnection = ['micBtn', 'cameraBtn', 'screenShareBtn', 'recordBtn'];

        buttonsRequiringConnection.forEach(buttonId => {
            const button = this.elements[buttonId];
            if (button && !this.isConnected) {
                button.classList.add('opacity-50', 'pointer-events-none');
                button.title = 'في انتظار الاتصال...';
            }
        });
    }

    removeButtonLoadingStates() {
        // Remove loading state from all buttons
        Object.values(this.elements).forEach(button => {
            if (button) {
                button.classList.remove('opacity-50', 'pointer-events-none');
            }
        });
    }

    async testMicrophone() {
        console.log('🎤 Testing microphone...');

        try {
            // Get microphone stream
            const stream = await navigator.mediaDevices.getUserMedia({ audio: true });

            // Create audio context for level monitoring
            const audioContext = new (window.AudioContext || window.webkitAudioContext)();
            const source = audioContext.createMediaStreamSource(stream);
            const analyser = audioContext.createAnalyser();

            source.connect(analyser);
            analyser.fftSize = 256;

            const bufferLength = analyser.frequencyBinCount;
            const dataArray = new Uint8Array(bufferLength);

            // Monitor audio levels
            const checkLevel = () => {
                analyser.getByteFrequencyData(dataArray);
                const average = dataArray.reduce((a, b) => a + b) / bufferLength;

                console.log('🎤 Audio level:', average);

                if (average > 10) {
                    console.log('✅ Microphone is working - audio detected');
                    this.showNotification('الميكروفون يعمل بشكل صحيح', 'success');
                    return;
                }

                // Continue monitoring for a few seconds
                setTimeout(checkLevel, 100);
            };

            checkLevel();

            // Stop monitoring after 5 seconds
            setTimeout(() => {
                stream.getTracks().forEach(track => track.stop());
                audioContext.close();
            }, 5000);

        } catch (error) {
            console.error('❌ Error testing microphone:', error);
            this.showNotification('خطأ في اختبار الميكروفون', 'error');
        }
    }

    // Update participant count in header
    updateParticipantCount() {
        const participantCountElement = document.getElementById('participantCount');
        console.log('🔍 Looking for participantCount element:', participantCountElement);

        if (participantCountElement) {
            // Get actual count from room participants + local participant
            const roomParticipants = this.room ? this.room.participants.size : 0;
            const localParticipantExists = this.localParticipant ? 1 : 0;

            // Always count at least 1 if we're in a meeting (current user)
            let totalParticipants = roomParticipants + localParticipantExists;

            // If we're connected to a room but showing 0, force to 1 (current user)
            if (totalParticipants === 0 && this.room && this.room.state === 'connected') {
                totalParticipants = 1;
                console.log('🔧 Forced participant count to 1 (current user in connected room)');
            }

            participantCountElement.textContent = totalParticipants;
            console.log('📊 Participant count updated:', totalParticipants, '(Room:', roomParticipants, '+ Local:', localParticipantExists, ', Room state:', this.room?.state || 'no room');
        } else {
            console.error('❌ participantCount element not found in DOM');
        }
    }

    // Start meeting timer
    startMeetingTimer() {
        // Check if timer element exists before starting
        const timerElement = document.getElementById('meetingTimer');
        console.log('🔍 Looking for meetingTimer element:', timerElement);

        if (!timerElement) {
            console.error('❌ meetingTimer element not found in DOM, cannot start timer');
            return;
        }

        // Stop any existing timer
        if (this.timerInterval) {
            clearInterval(this.timerInterval);
        }

        this.meetingStartTime = Date.now();
        this.timerInterval = setInterval(() => {
            this.updateMeetingTimer();
        }, 1000);

        // Initial update
        this.updateMeetingTimer();

        console.log('⏱️ Meeting timer started successfully');
    }

    // Stop meeting timer
    stopMeetingTimer() {
        if (this.timerInterval) {
            clearInterval(this.timerInterval);
            this.timerInterval = null;
            console.log('⏱️ Meeting timer stopped');
        }
    }

    // Update meeting timer display
    updateMeetingTimer() {
        if (!this.meetingStartTime) {
            console.warn('⚠️ Meeting start time not set, cannot update timer');
            return;
        }

        const timerElement = document.getElementById('meetingTimer');
        if (timerElement) {
            const elapsed = Date.now() - this.meetingStartTime;
            const minutes = Math.floor(elapsed / 60000);
            const seconds = Math.floor((elapsed % 60000) / 1000);

            const formattedTime = `${minutes.toString().padStart(2, '0')}:${seconds.toString().padStart(2, '0')}`;
            timerElement.textContent = formattedTime;

            // Debug log every 10 seconds
            if (seconds % 10 === 0) {
                console.log('⏱️ Meeting timer updated:', formattedTime);
            }
        } else {
            console.error('❌ meetingTimer element not found during update');
        }
    }

    // Handle edge cases and improve user experience
    handleParticipantCountChanges() {
        const participantCount = this.participants.size;

        // If no participants left, exit focus mode
        if (participantCount === 0 && this.isFocusModeActive) {
            console.log('🎯 No participants left, exiting focus mode');
            this.exitFocusMode();
        }

        // If only one participant and in focus mode, consider exiting
        if (participantCount === 1 && this.isFocusModeActive) {
            console.log('🎯 Only one participant left, suggesting to exit focus mode');
            // Show a subtle hint to the user
            this.showFocusModeHint('يمكنك الآن الخروج من وضع التركيز للعودة إلى العرض العادي');
        }

        // Update layout based on new participant count
        this.updateVideoGridLayout();
    }

    // Show focus mode hint
    showFocusModeHint(message) {
        // Create a temporary hint element
        const hint = document.createElement('div');
        hint.className = 'absolute bottom-4 left-1/2 transform -translate-x-1/2 bg-blue-600 text-white px-4 py-2 rounded-lg shadow-lg z-50 transition-all duration-300';
        hint.textContent = message;

        const focusArea = document.getElementById('focusArea');
        if (focusArea) {
            focusArea.appendChild(hint);

            // Auto-remove after 3 seconds
            setTimeout(() => {
                hint.style.opacity = '0';
                hint.style.transform = 'translateY(10px)';
                setTimeout(() => hint.remove(), 300);
            }, 3000);
        }
    }

    // Clean up focus mode and reset UI
    cleanupFocusMode() {
        console.log('🧹 Cleaning up focus mode...');

        // Exit focus mode if active
        if (this.isFocusModeActive) {
            this.exitFocusMode();
        }

        // Reset focus state
        this.isFocusModeActive = false;
        this.focusedParticipant = null;

        // Reset UI elements
        const videoGrid = document.getElementById('videoGrid');
        const horizontalParticipants = document.getElementById('horizontalParticipants');
        const focusArea = document.getElementById('focusArea');

        if (videoGrid) {
            videoGrid.style.display = 'grid';
        }

        if (horizontalParticipants) {
            horizontalParticipants.classList.add('hidden');
            horizontalParticipants.style.display = 'none';
            horizontalParticipants.innerHTML = '';
        }

        if (focusArea) {
            focusArea.classList.add('hidden');
            focusArea.innerHTML = '';
        }

        // Clean up responsive system
        if (this.responsiveObserver) {
            this.responsiveObserver.disconnect();
            this.responsiveObserver = null;
        }

        // Remove responsive classes
        const videoArea = document.getElementById('videoArea');
        if (videoArea) {
            videoArea.classList.remove('sidebar-open', 'focus-mode-active');
        }

        console.log('✅ Focus mode cleanup completed');
    }

    // Add test button for remote participant camera-off overlay
    addTestButton() {
        console.log('🔍 Adding test button for remote participant overlay...');

        // Try multiple selectors to find the controls container
        const selectors = [
            '.meeting-controls',
            '#meetingControls',
            '.controls-container',
            '.meeting-controls-container',
            '.flex.items-center.justify-center.gap-4',
            '.flex.justify-center.items-center.gap-4'
        ];

        let controlsContainer = null;
        for (const selector of selectors) {
            controlsContainer = document.querySelector(selector);
            if (controlsContainer) {
                console.log(`✅ Found controls container with selector: ${selector}`);
                break;
            }
        }

        if (controlsContainer) {
            // Test button
            const testButton = document.createElement('button');
            testButton.id = 'testRemoteOverlayBtn';
            testButton.className = 'bg-red-600 hover:bg-red-700 text-white px-4 py-2 rounded-lg transition-colors duration-200 flex items-center gap-2';
            testButton.innerHTML = '<i class="fas fa-video-slash"></i> Test Remote Overlay';
            testButton.onclick = () => this.testRemoteParticipantOverlay();

            // Force update button
            const forceUpdateButton = document.createElement('button');
            forceUpdateButton.id = 'forceUpdateBtn';
            forceUpdateButton.className = 'bg-blue-600 hover:bg-blue-700 text-white px-4 py-2 rounded-lg transition-colors duration-200 flex items-center gap-2 ml-2';
            forceUpdateButton.innerHTML = '<i class="fas fa-sync"></i> Force Update';
            forceUpdateButton.onclick = () => this.forceUpdateAllRemoteParticipants();

            // BRUTE FORCE button
            const bruteForceButton = document.createElement('button');
            bruteForceButton.id = 'bruteForceBtn';
            bruteForceButton.className = 'bg-purple-600 hover:bg-purple-700 text-white px-4 py-2 rounded-lg transition-colors duration-200 flex items-center gap-2 ml-2';
            bruteForceButton.innerHTML = '<i class="fas fa-bolt"></i> BRUTE FORCE';
            bruteForceButton.onclick = () => this.bruteForceCameraOffOverlay();

            controlsContainer.appendChild(testButton);
            controlsContainer.appendChild(forceUpdateButton);
            controlsContainer.appendChild(bruteForceButton);
            console.log('✅ Test, Force Update, and BRUTE FORCE buttons added for remote participant overlay');
        } else {
            console.error('❌ Could not find controls container for test button');
            console.log('🔍 Available elements with "control" in class or id:');
            document.querySelectorAll('[class*="control"], [id*="control"]').forEach(el => {
                console.log(`  - ${el.tagName}: ${el.className} ${el.id}`);
            });
        }
    }

    // Set up event listeners for a specific remote participant
    setupRemoteParticipantEventListeners(participant) {
        console.log(`🔍 Setting up event listeners for remote participant: ${participant.identity}`);

        // Log participant track information using the correct API
        if (participant.videoTracks) {
            console.log(`🔍 Participant video tracks:`, participant.videoTracks);
        }
        if (participant.audioTracks) {
            console.log(`🔍 Participant audio tracks:`, participant.audioTracks);
        }

        // Set up track event listeners for remote participant
        participant.on(window.LiveKit.ParticipantEvent.TrackMuted, (publication) => {
            console.log(`📴 Track muted event for ${participant.identity}:`, publication.kind, publication.muted);
            if (publication.kind === 'video') {
                console.log(`📴 Remote video muted for ${participant.identity}`);
                setTimeout(() => {
                    this.updateParticipantCameraStatus(participant);
                }, 200);
            }
        });

        participant.on(window.LiveKit.ParticipantEvent.TrackUnmuted, (publication) => {
            console.log(`🟢 Track unmuted event for ${participant.identity}:`, publication.kind, publication.muted);
            if (publication.kind === 'video') {
                console.log(`🟢 Remote video unmuted for ${participant.identity}`);
                setTimeout(() => {
                    this.updateParticipantCameraStatus(participant);
                }, 200);
            }
        });

        // Check initial state after a delay
        setTimeout(() => {
            console.log(`🔍 Checking initial video status for remote participant ${participant.identity}`);
            this.updateParticipantCameraStatus(participant);
        }, 1000);

        console.log(`✅ Event listeners set up for remote participant: ${participant.identity}`);
    }

    // Set up comprehensive remote participant monitoring
    setupRemoteParticipantMonitoring() {
        console.log('🔍 Setting up SIMPLE remote participant monitoring...');

        // SIMPLE: Only monitor REMOTE participants every 2 seconds
        setInterval(() => {
            console.log('🔧 SIMPLE: Checking REMOTE participants only for camera-off overlay...');

            if (!this.room || !this.room.remoteParticipants) return;

            // Get only remote participants
            const remoteParticipants = Array.from(this.room.remoteParticipants.values());
            console.log(`🔧 Found ${remoteParticipants.length} remote participants`);

            remoteParticipants.forEach(participant => {
                const participantId = participant.identity;
                const participantElement = document.getElementById(`participant-${participantId}`);

                if (!participantElement) return;

                // Check if this remote participant has video
                const hasVideo = this.hasActiveVideoTrack(participant);

                console.log(`🔧 Remote participant ${participantId}: hasVideo=${hasVideo}`);

                if (!hasVideo) {
                    // No video - SHOW camera-off overlay
                    console.log(`🔧 SHOWING camera-off overlay for remote ${participantId} (no video)`);

                    // Create or get overlay
                    let overlay = participantElement.querySelector('.camera-off-overlay');
                    if (!overlay) {
                        overlay = document.createElement('div');
                        overlay.className = 'camera-off-overlay absolute inset-0 bg-gradient-to-br from-blue-900 to-gray-800 flex flex-col items-center justify-center opacity-100 transition-opacity duration-300 z-10';

                        // Add teacher label
                        const isTeacher = this.isParticipantTeacher(participant, false);
                        let statusLabel = '';
                        if (isTeacher) {
                            statusLabel = '<div class="text-green-400 text-sm font-medium">معلم</div>';
                        }

                        overlay.innerHTML = `
                            <div class="w-20 h-20 sm:w-24 sm:h-24 bg-gray-700 rounded-full flex items-center justify-center mb-4">
                                <i class="fas fa-video-slash text-white text-2xl sm:text-3xl"></i>
                            </div>
                            <div class="text-white text-center">
                                <div class="font-semibold text-lg">${participantId}</div>
                                <div class="text-sm opacity-75">الكاميرا مغلقة</div>
                                ${statusLabel}
                            </div>
                        `;
                        participantElement.appendChild(overlay);
                        console.log(`🔧 Created new camera-off overlay for remote ${participantId}`);
                    } else {
                        overlay.classList.remove('opacity-0');
                        overlay.classList.add('opacity-100');
                        console.log(`🔧 Made existing camera-off overlay visible for remote ${participantId}`);
                    }
                } else {
                    // Has video - HIDE camera-off overlay
                    console.log(`🔧 HIDING camera-off overlay for remote ${participantId} (has video)`);
                    const overlay = participantElement.querySelector('.camera-off-overlay');
                    if (overlay) {
                        overlay.classList.remove('opacity-100');
                        overlay.classList.add('opacity-0');
                        console.log(`🔧 Hidden camera-off overlay for remote ${participantId}`);
                    }
                }
            });
        }, 2000);

        console.log('✅ SIMPLE remote participant monitoring set up - will check every 2 seconds');
    }

    // Set up camera state broadcasting via data channels
    setupCameraStateBroadcasting() {
        console.log('📡 Setting up camera state broadcasting...');

        // Store camera states for all participants
        this.cameraStates = new Map();

        // Broadcast local camera state every 2 seconds
        setInterval(() => {
            if (this.localParticipant) {
                const hasVideo = this.hasActiveVideoTrack(this.localParticipant);
                const currentState = this.cameraStates.get(this.localParticipant.identity);

                if (currentState !== hasVideo) {
                    this.cameraStates.set(this.localParticipant.identity, hasVideo);
                    this.broadcastCameraState(this.localParticipant.identity, hasVideo);
                    console.log(`📡 Broadcasting local camera state: ${hasVideo ? 'ON' : 'OFF'}`);
                }
            }
        }, 2000);

        console.log('✅ Camera state broadcasting set up');
    }

    // Broadcast camera state to all participants
    broadcastCameraState(participantId, hasVideo) {
        if (!this.room) return;

        try {
            const data = {
                type: 'camera_state',
                participantId: participantId,
                hasVideo: hasVideo,
                timestamp: Date.now()
            };

            this.room.localParticipant.publishData(data, {
                topic: 'camera_state'
            });

            console.log(`📡 Broadcasted camera state for ${participantId}: ${hasVideo ? 'ON' : 'OFF'}`);
        } catch (error) {
            console.error('❌ Error broadcasting camera state:', error);
        }
    }

    // Request camera states from all participants
    requestCameraStatesFromAllParticipants() {
        if (!this.room || !this.localParticipant) {
            console.log('📡 Skipping camera state request - room or local participant not ready');
            return;
        }

        try {
            const data = {
                type: 'request_camera_states',
                requesterId: this.localParticipant.identity,
                timestamp: Date.now()
            };

            this.room.localParticipant.publishData(data, {
                topic: 'camera_state_request'
            });

            console.log('📡 Requested camera states from all participants');
        } catch (error) {
            console.error('❌ Error requesting camera states:', error);
        }
    }

    // Handle received camera state data
    handleCameraStateData(data) {
        try {
            const { participantId, hasVideo } = data;
            console.log(`📡 Received camera state for ${participantId}: ${hasVideo ? 'ON' : 'OFF'}`);

            // Update stored state
            this.cameraStates.set(participantId, hasVideo);

            // Update UI immediately
            const participantElement = document.getElementById(`participant-${participantId}`);
            if (participantElement) {
                if (hasVideo) {
                    console.log(`📡 Hiding camera-off overlay for ${participantId} (received state: ON)`);
                    this.hideCameraOffOverlay(participantElement);
                } else {
                    console.log(`📡 Showing camera-off overlay for ${participantId} (received state: OFF)`);
                    // Find participant object for overlay creation
                    let participant = null;
                    if (participantId === this.localParticipant?.identity) {
                        participant = this.localParticipant;
                    } else if (this.room && this.room.remoteParticipants) {
                        participant = this.room.remoteParticipants.get(participantId);
                    }
                    if (participant) {
                        this.showCameraOffOverlay(participantElement, participant);
                    }
                }
            }
        } catch (error) {
            console.error('❌ Error handling camera state data:', error);
        }
    }

    // BRUTE FORCE: Immediately force camera-off overlay for ALL participants
    bruteForceCameraOffOverlay() {
        console.log('⚡ BRUTE FORCE: Immediately forcing camera-off overlay for ALL participants...');

        // Get all participant elements from DOM
        const allParticipantElements = document.querySelectorAll('[id^="participant-"]');
        console.log(`⚡ Found ${allParticipantElements.length} participant elements`);

        allParticipantElements.forEach(participantElement => {
            const participantId = participantElement.id.replace('participant-', '');

            // Find participant object safely
            let participant = null;
            const isLocal = participantId === this.localParticipant?.identity;
            if (isLocal) {
                participant = this.localParticipant;
            } else if (this.room && this.room.remoteParticipants) {
                participant = this.room.remoteParticipants.get(participantId);
            }

            // Check if this participant has video using LiveKit method
            const hasVideo = participant ? this.hasActiveVideoTrack(participant) : false;

            console.log(`⚡ Participant ${participantId}: hasVideo=${hasVideo}, isLocal=${isLocal}`);

            if (!hasVideo) {
                // No video - FORCE SHOW camera-off overlay
                console.log(`⚡ FORCE SHOWING camera-off overlay for ${participantId} (no video)`);

                // Create or get overlay
                let overlay = participantElement.querySelector('.camera-off-overlay');
                if (!overlay) {
                    overlay = document.createElement('div');
                    overlay.className = 'camera-off-overlay absolute inset-0 bg-gradient-to-br from-blue-900 to-gray-800 flex flex-col items-center justify-center opacity-100 transition-opacity duration-300 z-10';

                    // Add teacher label and current user label
                    const isLocal = participantId === this.localParticipant?.identity;
                    const isTeacher = this.isParticipantTeacher({ identity: participantId }, isLocal);

                    let displayName = participantId;
                    let statusLabel = '';

                    if (isLocal) {
                        statusLabel = '<div class="text-green-400 text-sm font-medium">أنت</div>';
                    } else if (isTeacher) {
                        statusLabel = '<div class="text-green-400 text-sm font-medium">معلم</div>';
                    }

                    overlay.innerHTML = `
                            <div class="w-20 h-20 sm:w-24 sm:h-24 bg-gray-700 rounded-full flex items-center justify-center mb-4">
                                <i class="fas fa-video-slash text-white text-2xl sm:text-3xl"></i>
                            </div>
                            <div class="text-white text-center">
                                <div class="font-semibold text-lg">${displayName}</div>
                                <div class="text-sm opacity-75">الكاميرا مغلقة</div>
                                ${statusLabel}
                            </div>
                        `;
                    participantElement.appendChild(overlay);
                    console.log(`⚡ Created new camera-off overlay for ${participantId}`);
                } else {
                    overlay.classList.remove('opacity-0');
                    overlay.classList.add('opacity-100');
                    console.log(`⚡ Made existing camera-off overlay visible for ${participantId}`);
                }
            } else {
                // Has video - FORCE HIDE camera-off overlay
                console.log(`⚡ FORCE HIDING camera-off overlay for ${participantId} (has video)`);
                const overlay = participantElement.querySelector('.camera-off-overlay');
                if (overlay) {
                    if (isLocal) {
                        // FOR LOCAL PARTICIPANTS: Remove inline styles and hide
                        overlay.removeAttribute('style');
                        console.log(`⚡ Removed inline styles for local participant ${participantId}`);
                    }
                    overlay.classList.remove('opacity-100');
                    overlay.classList.add('opacity-0');
                    console.log(`⚡ Hidden camera-off overlay for ${participantId}`);
                }
            }
        });

        console.log('✅ BRUTE FORCE completed - all participants should show correct camera-off state');
    }

    // Simple test: Force show camera-off overlay for ALL participants
    forceShowCameraOffForAllRemoteParticipants() {
        console.log('🧪 SIMPLE TEST: Force showing camera-off overlay for ALL participants...');

        if (!this.room) {
            console.error('❌ No room available');
            return;
        }

        // SAFE WAY: Get all participant elements from DOM
        const allParticipantElements = document.querySelectorAll('[id^="participant-"]');
        console.log(`🧪 Found ${allParticipantElements.length} participant elements in DOM`);

        allParticipantElements.forEach(participantElement => {
            const participantId = participantElement.id.replace('participant-', '');
            const isLocal = participantId === this.localParticipant?.identity;

            // Find participant object safely
            let participant = null;
            if (isLocal) {
                participant = this.localParticipant;
            } else if (this.room && this.room.remoteParticipants) {
                participant = this.room.remoteParticipants.get(participantId);
            }

            if (participant) {
                console.log(`🧪 FORCE SHOWING camera-off overlay for ${isLocal ? 'LOCAL' : 'REMOTE'} ${participantId}`);
                this.showCameraOffOverlay(participantElement, participant);
            }
        });

        console.log('✅ Simple test completed - ALL participants should show camera-off overlay');
    }

    // Force update all remote participants camera status
    forceUpdateAllRemoteParticipants() {
        console.log('🔄 Force updating all participants camera status...');

        if (!this.room) {
            console.error('❌ No room available');
            return;
        }

        // SAFE WAY: Get all participant elements from DOM
        const allParticipantElements = document.querySelectorAll('[id^="participant-"]');
        console.log(`🔄 Found ${allParticipantElements.length} participant elements to update`);

        allParticipantElements.forEach(participantElement => {
            const participantId = participantElement.id.replace('participant-', '');
            const isLocal = participantId === this.localParticipant?.identity;

            // Find participant object safely
            let participant = null;
            if (isLocal) {
                participant = this.localParticipant;
            } else if (this.room && this.room.remoteParticipants) {
                participant = this.room.remoteParticipants.get(participantId);
            }

            if (participant) {
                console.log(`🔄 Force updating camera status for: ${isLocal ? 'LOCAL' : 'REMOTE'} ${participantId}`);
                console.log(`🔄 Participant has video: ${this.hasActiveVideoTrack(participant)}`);
                console.log(`🔄 Participant tracks:`, participant.getTracks().map(t => ({ kind: t.kind, muted: t.muted })));

                // FORCE SHOW CAMERA OFF OVERLAY FOR ALL PARTICIPANTS
                console.log(`🔄 FORCE SHOWING camera-off overlay for ${participantId}`);
                this.showCameraOffOverlay(participantElement, participant);

                this.updateParticipantCameraStatus(participant);
            }
        });

        console.log('✅ Force update completed for all participants');
    }

    // Test method to manually trigger camera-off overlay for remote participants
    testRemoteParticipantOverlay() {
        console.log('🧪 Testing remote participant camera-off overlay...');

        if (!this.room) {
            console.error('❌ No room available');
            return;
        }

        const remoteParticipants = Array.from(this.room.remoteParticipants.values());
        console.log(`🧪 Found ${remoteParticipants.length} remote participants`);

        remoteParticipants.forEach(participant => {
            console.log(`🧪 Testing overlay for remote participant: ${participant.identity}`);
            const participantElement = document.getElementById(`participant-${participant.identity}`);

            if (participantElement) {
                console.log(`🧪 Participant element found:`, participantElement);
                console.log(`🧪 Participant element HTML:`, participantElement.outerHTML);

                // Test the actual camera-off overlay creation
                console.log(`🧪 Testing getOrCreateCameraOffOverlay for ${participant.identity}`);
                const overlay = this.getOrCreateCameraOffOverlay(participantElement, participant);
                console.log(`🧪 Overlay created:`, overlay);

                if (overlay) {
                    console.log(`🧪 Overlay HTML:`, overlay.outerHTML);
                    console.log(`🧪 Overlay parent:`, overlay.parentNode);

                    // Force show the overlay
                    overlay.style.opacity = '1';
                    overlay.style.display = 'flex';
                    overlay.style.background = 'red !important';
                    overlay.style.zIndex = '9999';

                    console.log(`🧪 Overlay styles applied:`, overlay.style.cssText);
                    console.log(`🧪 Participant element after overlay:`, participantElement.outerHTML);
                } else {
                    console.error(`❌ Failed to create overlay for ${participant.identity}`);
                }

            } else {
                console.error(`❌ No element found for participant: ${participant.identity}`);
            }
        });
    }

    // Force hide all camera off overlays
    forceHideAllCameraOffOverlays() {
        console.log('🔄 Force hiding all camera off overlays...');

        const overlays = document.querySelectorAll('.camera-off-overlay');
        console.log(`🔄 Found ${overlays.length} camera off overlays`);

        overlays.forEach(overlay => {
            console.log(`🔄 Hiding overlay:`, overlay);
            overlay.style.display = 'none';
            overlay.classList.remove('opacity-100');
            overlay.classList.add('opacity-0');
        });

        // Also ensure all videos are visible
        const videos = document.querySelectorAll('[id^="participant-"] video');
        videos.forEach(video => {
            video.style.display = 'block';
            video.classList.remove('opacity-0');
            video.classList.add('opacity-100');
        });

        console.log('✅ All camera off overlays hidden');
    }

    // Force attach all video tracks for late joiners
    forceAttachAllVideoTracks() {
        console.log('🔄 Force attaching all video tracks...');

        if (this.room && this.room.remoteParticipants && this.room.remoteParticipants instanceof Map) {
            const remoteParticipants = Array.from(this.room.remoteParticipants.values());
            console.log(`🔄 Found ${remoteParticipants.length} remote participants`);

            remoteParticipants.forEach(participant => {
                console.log(`🔄 Force attaching video tracks for ${participant.identity}`);
                this.attachExistingVideoTracks(participant);
            });
        } else {
            console.log('⚠️ No remote participants found or room not available');
        }

        console.log('✅ Force attach video tracks completed');
    }
}

// Export for global use
window.ProfessionalLiveKitMeeting = ProfessionalLiveKitMeeting;

// Debug log to confirm script loaded
console.log('🎯 ProfessionalLiveKitMeeting class loaded and available globally');

// Add a simple test function
window.testProfessionalMeeting = function () {
    console.log('🧪 Testing ProfessionalLiveKitMeeting...');
    console.log('Class available:', typeof ProfessionalLiveKitMeeting);
    console.log('Window object:', window.ProfessionalLiveKitMeeting);
    console.log('Constructor available:', typeof ProfessionalLiveKitMeeting === 'function');
    return typeof ProfessionalLiveKitMeeting === 'function';
};

// Test function for debugging
window.testProfessionalMeeting = function () {
    console.log('🧪 Testing Professional LiveKit Meeting...');
    console.log('LiveKit available:', typeof window.LiveKit !== 'undefined');
    console.log('ProfessionalLiveKitMeeting available:', typeof ProfessionalLiveKitMeeting !== 'undefined');

    if (typeof ProfessionalLiveKitMeeting !== 'undefined') {
        console.log('✅ ProfessionalLiveKitMeeting class is available');
    } else {
        console.error('❌ ProfessionalLiveKitMeeting class not found');
    }
};

// Test the class immediately
setTimeout(() => {
    if (window.isProfessionalLiveKitMeetingAvailable()) {
        console.log('✅ ProfessionalLiveKitMeeting is ready for use');
    } else {
        console.error('❌ ProfessionalLiveKitMeeting failed to load');
    }
}, 100);

// Check if the class is available
window.isProfessionalLiveKitMeetingAvailable = function () {
    return typeof ProfessionalLiveKitMeeting === 'function';
};
