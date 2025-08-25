/**
 * LiveKit Layout Module
 * Handles grid/focus layouts, resizing, and responsive design (DOM-only, no SDK calls)
 */

/**
 * Layout manager for video grid and focus modes
 */
export class LiveKitLayout {
    /**
     * Create a new layout manager
     * @param {Object} config - Configuration object
     * @param {Function} config.onLayoutChange - Callback when layout changes
     * @param {Function} config.onFocusEnter - Callback when entering focus mode
     * @param {Function} config.onFocusExit - Callback when exiting focus mode
     */
    constructor(config = {}) {
        this.config = config;
        this.isFocusModeActive = false;
        this.focusedParticipant = null;
        this.responsiveObserver = null;
        this.isSidebarOpen = false;
        this.currentSidebarType = null;

        // Cache DOM elements
        this.elements = {
            videoGrid: null,
            meetingInterface: null,
            sidebar: null,
            videoArea: null
        };

        this.initializeLayout();

        // Set global reference for screen share focus mode
        window.livekitLayout = this;

        console.log('🎨 LiveKitLayout initialized');
    }

    /**
     * Initialize layout system
     */
    initializeLayout() {
        this.cacheElements();
        this.setupResponsiveSystem();
        this.setupResizeObserver();
        this.setupKeyboardHandlers();

        // Initial layout calculation
        this.calculateMeetingHeight();
        this.updateVideoLayoutClasses();
    }

    /**
     * Cache DOM elements
     */
    cacheElements() {
        this.elements = {
            videoGrid: document.getElementById('videoGrid'),
            meetingInterface: document.getElementById('meetingInterface'),
            sidebar: document.getElementById('meetingSidebar'),
            videoArea: document.getElementById('videoArea')
        };

        console.log('🔍 Cached elements:', {
            videoGrid: !!this.elements.videoGrid,
            meetingInterface: !!this.elements.meetingInterface,
            sidebar: !!this.elements.sidebar,
            videoArea: !!this.elements.videoArea
        });
    }

    /**
     * Set up responsive system
     */
    setupResponsiveSystem() {
        console.log('📱 Setting up responsive layout system...');

        // Add window resize listener
        window.addEventListener('resize', () => {
            this.calculateMeetingHeight();
            this.updateVideoLayoutClasses();
        });

        // Detect sidebar state
        this.detectSidebarState();

        console.log('✅ Responsive system initialized');
    }

    /**
     * Set up resize observer for container changes
     */
    setupResizeObserver() {
        if (!window.ResizeObserver) {
            console.warn('⚠️ ResizeObserver not supported');
            return;
        }

        this.responsiveObserver = new ResizeObserver((entries) => {
            for (const entry of entries) {
                this.handleContainerResize(entry);
            }
        });

        if (this.elements.meetingInterface) {
            this.responsiveObserver.observe(this.elements.meetingInterface);
        } else {
            console.warn('⚠️ Meeting interface element not found for resize observer');
        }
    }

    /**
     * Set up keyboard handlers
     */
    setupKeyboardHandlers() {
        console.log('⌨️ Setting up keyboard handlers...');

        // Add keyboard event listener for focus mode
        document.addEventListener('keydown', (e) => {
            if (e.key === 'Escape' && this.isFocusModeActive) {
                console.log('⌨️ Escape key pressed, exiting focus mode');
                this.exitFocusMode();
            }
        });

        console.log('✅ Keyboard handlers set up');
    }

    /**
     * Handle container resize
     * @param {ResizeObserverEntry} entry - Resize observer entry
     */
    handleContainerResize(entry) {
        const { width, height } = entry.contentRect;
        console.log(`📐 Container resized: ${width}x${height}`);

        this.adjustVideoGridForContainer(width, height);

        // Focus mode uses CSS transforms, no manual layout updates needed
    }

    /**
     * Calculate meeting height based on viewport
     */
    calculateMeetingHeight() {
        if (!this.elements.meetingInterface) return;

        const viewportWidth = window.innerWidth;
        const viewportHeight = window.innerHeight;

        // Find control bar and header elements
        const controlBar = document.querySelector('.bg-gray-800.border-t.border-gray-700');
        const meetingHeader = document.querySelector('.bg-gradient-to-r.from-blue-500');

        const controlBarHeight = controlBar ? controlBar.offsetHeight : 80;
        const headerHeight = meetingHeader ? meetingHeader.offsetHeight : 60;

        // Calculate optimal height based on 16:9 aspect ratio
        const availableHeight = viewportHeight - 200;
        const optimalHeight = Math.min(availableHeight, (viewportWidth * 9) / 16 + controlBarHeight + headerHeight);
        const finalHeight = Math.max(400, optimalHeight);

        this.elements.meetingInterface.style.height = `${finalHeight}px`;

        console.log(`📱 Meeting height set to: ${finalHeight}px`);
    }

    /**
     * Detect sidebar state changes
     */
    detectSidebarState() {
        if (!this.elements.sidebar) return;

        const observer = new MutationObserver((mutations) => {
            mutations.forEach((mutation) => {
                if (mutation.type === 'attributes' && mutation.attributeName === 'class') {
                    this.updateSidebarState();
                }
            });
        });

        observer.observe(this.elements.sidebar, {
            attributes: true,
            attributeFilter: ['class']
        });

        // Initial state
        this.updateSidebarState();
    }

    /**
     * Update sidebar state
     */
    updateSidebarState() {
        if (!this.elements.sidebar) return;

        const wasOpen = this.isSidebarOpen;
        this.isSidebarOpen = this.elements.sidebar.classList.contains('-translate-x-0');

        if (wasOpen !== this.isSidebarOpen) {
            console.log(`📋 Sidebar state changed: ${this.isSidebarOpen ? 'open' : 'closed'}`);
            this.adjustVideoAreaForSidebar(this.isSidebarOpen);
        }
    }

    /**
     * Adjust video grid for container size
     * @param {number} width - Container width
     * @param {number} height - Container height
     */
    adjustVideoGridForContainer(width, height) {
        if (!this.elements.videoGrid) return;

        const participantCount = this.elements.videoGrid.children.length;
        if (participantCount === 0) return;

        // Update layout classes for responsive grid system
        this.updateVideoLayoutClasses();

        console.log(`📐 Video grid adjusted for container ${width}x${height} with ${participantCount} participants`);
    }



    /**
     * Apply grid layout configuration
     * @param {Object} gridConfig - Grid configuration
     */
    applyGridLayout(gridConfig) {
        if (!this.elements.videoGrid) return;

        const { participantCount } = gridConfig;

        // Update data attribute for CSS-driven responsive grid layout
        this.elements.videoGrid.setAttribute('data-participants', participantCount);

        console.log(`🎯 Applied CSS grid layout for ${participantCount} participants`);
    }

    /**
     * Apply grid layout based on participant count
     * @param {number} participantCount - Number of participants
     */
    applyGrid(participantCount) {
        console.log(`🎯 Applying CSS grid layout for ${participantCount} participants`);

        if (!this.elements.videoGrid) {
            console.error('❌ Video grid not found');
            return;
        }

        // Exit focus mode if active
        if (this.isFocusModeActive) {
            this.exitFocusMode();
        }

        // Update layout classes and data attributes
        this.updateVideoLayoutClasses();

        // Apply grid configuration using CSS data attribute
        this.applyGridLayout({ participantCount });

        console.log(`✅ CSS grid layout applied for ${participantCount} participants`);
    }

    /**
     * Update video layout based on participant count
     */
    updateVideoLayoutClasses() {
        if (!this.elements.videoGrid) return;

        const totalElements = this.elements.videoGrid.children.length;
        const screenShareElements = this.elements.videoGrid.querySelectorAll('[data-is-screen-share="true"]').length;
        const participantElements = totalElements - screenShareElements;

        // Set data attributes for CSS-driven responsive grid
        this.elements.videoGrid.setAttribute('data-participants', participantElements);
        this.elements.videoGrid.setAttribute('data-screen-shares', screenShareElements);
        this.elements.videoGrid.setAttribute('data-total-elements', totalElements);

        // Add screen share class if there are active screen shares
        if (screenShareElements > 0) {
            this.elements.videoGrid.classList.add('has-screen-share');
        } else {
            this.elements.videoGrid.classList.remove('has-screen-share');
        }

        console.log(`🎨 Updated layout for ${participantElements} participants and ${screenShareElements} screen shares (${totalElements} total)`);
    }

    /**
     * Enter focus mode for a specific participant (Simple Fade-in Approach)
     * @param {string} participantId - Participant ID to focus on
     * @param {HTMLElement} participantElement - Participant DOM element
     */
    applyFocusMode(participantId, participantElement = null) {
        console.log(`🎯 applyFocusMode called with participantId: ${participantId}`);
        console.log(`🎯 participantElement provided: ${!!participantElement}`);

        if (!participantElement) {
            participantElement = document.getElementById(`participant-${participantId}`);
            console.log(`🎯 Found element by ID: ${!!participantElement}`);
        }

        if (!participantElement) {
            console.log(`⏳ Participant element not found for ${participantId}, will retry later`);
            return;
        }

        // Prevent multiple focus modes
        if (this.isFocusModeActive) {
            console.log('⚠️ Focus mode already active, exiting first');
            this.exitFocusMode();
            setTimeout(() => this.applyFocusMode(participantId, participantElement), 100);
            return;
        }

        console.log(`🎯 Entering focus mode for ${participantId} (simple fade-in approach)`);

        this.isFocusModeActive = true;
        this.focusedParticipant = participantId;

        // Get required elements
        const videoArea = document.getElementById('videoArea');
        const overlay = document.getElementById('focusOverlay');
        const focusedContainer = document.getElementById('focusedVideoContainer');

        if (!videoArea || !overlay || !focusedContainer) {
            console.error('❌ Required elements not found');
            return;
        }

        // Store references to original element
        this._focusOriginalElement = participantElement;

        // Get video area dimensions for proper sizing
        const videoAreaRect = videoArea.getBoundingClientRect();
        const padding = 30; // 30px padding as requested
        const availableWidth = videoAreaRect.width - (padding * 2);
        const availableHeight = videoAreaRect.height - (padding * 2);

        console.log(`📐 Video area rect: ${videoAreaRect.width}x${videoAreaRect.height}`);
        console.log(`📐 Available space after padding: ${availableWidth}x${availableHeight}`);

        // Get actual video aspect ratio from the original video element
        const originalVideo = participantElement.querySelector('video');
        let videoAspectRatio = 16 / 9; // Default fallback

        // Check if this is a screen share (different aspect ratio handling)
        const isScreenShare = participantElement.dataset.isScreenShare === 'true';

        if (originalVideo && originalVideo.videoWidth && originalVideo.videoHeight) {
            videoAspectRatio = originalVideo.videoWidth / originalVideo.videoHeight;
            console.log(`📹 Detected video aspect ratio: ${videoAspectRatio.toFixed(2)} (${originalVideo.videoWidth}x${originalVideo.videoHeight})`);
        } else {
            console.log(`📹 Using default aspect ratio: ${videoAspectRatio.toFixed(2)}`);
        }

        // For screen shares, use a more conservative aspect ratio to ensure content fits
        if (isScreenShare) {
            videoAspectRatio = Math.min(videoAspectRatio, 16 / 9); // Cap at 16:9 for screen shares
            console.log(`🖥️ Screen share detected, adjusted aspect ratio: ${videoAspectRatio.toFixed(2)}`);
        }

        // Use FULL available height as primary constraint
        let focusedHeight = availableHeight;
        let focusedWidth = focusedHeight * videoAspectRatio;

        // Only if calculated width exceeds available width, constrain by width
        if (focusedWidth > availableWidth) {
            focusedWidth = availableWidth;
            focusedHeight = focusedWidth / videoAspectRatio;
        }

        // Ensure we're using meaningful dimensions
        focusedWidth = Math.max(focusedWidth, 200);
        focusedHeight = Math.max(focusedHeight, 150);

        console.log(`📐 Video area rect: ${videoAreaRect.width}x${videoAreaRect.height}`);
        console.log(`📐 Available space: ${availableWidth}x${availableHeight}`);
        console.log(`📐 Calculated aspect ratio: ${videoAspectRatio.toFixed(2)}`);
        console.log(`📐 Final focused video size: ${focusedWidth}x${focusedHeight}`);
        console.log(`📐 Size utilization: ${((focusedWidth * focusedHeight) / (availableWidth * availableHeight) * 100).toFixed(1)}%`);

        // Create focused video element
        const focusedElement = participantElement.cloneNode(true);
        focusedElement.id = `focused-${participantId}`;

        // Style for centered, properly sized display - OVERRIDE ALL CSS CONSTRAINTS
        focusedElement.style.setProperty('position', 'absolute', 'important');
        focusedElement.style.setProperty('top', '50%', 'important');
        focusedElement.style.setProperty('left', '50%', 'important');
        focusedElement.style.setProperty('transform', 'translate(-50%, -50%)', 'important');
        focusedElement.style.setProperty('width', `${focusedWidth}px`, 'important');
        focusedElement.style.setProperty('height', `${focusedHeight}px`, 'important');
        focusedElement.style.setProperty('max-width', 'none', 'important');
        focusedElement.style.setProperty('max-height', 'none', 'important');
        focusedElement.style.setProperty('min-width', 'auto', 'important');
        focusedElement.style.setProperty('min-height', 'auto', 'important');
        focusedElement.style.setProperty('z-index', '60', 'important');
        focusedElement.style.setProperty('opacity', '0', 'important'); // Start invisible for fade-in
        focusedElement.style.setProperty('transition', 'opacity 400ms ease-in-out', 'important');
        focusedElement.style.setProperty('pointer-events', 'auto', 'important');
        focusedElement.style.setProperty('margin', '0', 'important');
        focusedElement.style.setProperty('border-radius', '12px', 'important');
        focusedElement.style.setProperty('box-shadow', '0 20px 40px rgba(0, 0, 0, 0.5)', 'important');

        // Remove any existing exit buttons
        const existingExitBtn = focusedElement.querySelector('#exitFocusBtn');
        if (existingExitBtn) {
            existingExitBtn.remove();
        }

        // Add exit button
        const exitBtn = document.createElement('button');
        exitBtn.id = 'exitFocusBtn';
        exitBtn.className = 'absolute top-4 right-4 w-12 h-12 bg-black bg-opacity-50 hover:bg-opacity-70 rounded-full text-white flex items-center justify-center transition-all duration-200 hover:scale-110 focus:outline-none focus:ring-2 focus:ring-blue-500 z-10';
        exitBtn.innerHTML = '<i class="fas fa-times text-xl"></i>';
        exitBtn.onclick = (e) => {
            e.stopPropagation();
            e.preventDefault();
            this.exitFocusMode();
        };

        focusedElement.appendChild(exitBtn);

        // Clone video tracks to the focused element
        this.cloneVideoTracks(participantElement, focusedElement, participantId);

        // Ensure video element fills the focused container properly - OVERRIDE CSS CONSTRAINTS
        const focusedVideo = focusedElement.querySelector('video');
        if (focusedVideo) {
            focusedVideo.style.setProperty('width', '100%', 'important');
            focusedVideo.style.setProperty('height', '100%', 'important');

            // Use different object-fit for screen shares vs regular videos
            const isScreenShare = focusedElement.dataset.isScreenShare === 'true';
            if (isScreenShare) {
                focusedVideo.style.setProperty('object-fit', 'contain', 'important'); // Show full screen share content
                focusedVideo.style.setProperty('background-color', '#1f2937', 'important'); // Dark background for screen shares
            } else {
                focusedVideo.style.setProperty('object-fit', 'cover', 'important'); // Fill the entire container for regular videos
            }

            focusedVideo.style.setProperty('border-radius', '12px', 'important'); // Match the container styling
            focusedVideo.style.setProperty('max-width', 'none', 'important');
            focusedVideo.style.setProperty('max-height', 'none', 'important');
        }

        // Remove CSS classes that might constrain sizing
        focusedElement.classList.remove('focused');
        focusedElement.classList.add('custom-focused-video');

        // Dim original element
        participantElement.style.opacity = '0.3';
        participantElement.style.pointerEvents = 'none';
        participantElement.style.transition = 'opacity 300ms ease';

        // Add focused element to the focused container
        focusedContainer.appendChild(focusedElement);

        // Store reference
        this._focusedElement = focusedElement;

        // Hide screen share title when in focus mode (target the cloned element)
        if (isScreenShare) {
            const focusedScreenShareTitle = focusedElement.querySelector('.screen-share-title');
            if (focusedScreenShareTitle) {
                focusedScreenShareTitle.style.setProperty('opacity', '0', 'important');
                focusedScreenShareTitle.style.setProperty('pointer-events', 'none', 'important');
                console.log('🏷️ Hidden screen share title in focus mode');
            }
        }

        // Set up fullscreen change listener for automatic repositioning
        this.setupFullscreenListener();

        // Set up window resize listener for focus mode
        this.setupFocusResizeListener();

        // Start monitoring video area size changes during focus mode
        this.startFocusModeMonitoring();

        // Show overlay
        overlay.classList.remove('hidden');
        overlay.style.opacity = '0';
        overlay.style.transition = 'opacity 300ms ease';

        // Add focus mode class to video area
        videoArea.classList.add('focus-mode-active');

        // Fade in both overlay and focused video
        requestAnimationFrame(() => {
            overlay.style.opacity = '1';
            focusedElement.style.setProperty('opacity', '1', 'important');

            // Debug: Check actual applied dimensions
            setTimeout(() => {
                const rect = focusedElement.getBoundingClientRect();
                console.log(`🔍 ACTUAL focused video dimensions: ${rect.width}x${rect.height}`);
                console.log(`🔍 Expected vs Actual: ${focusedWidth}x${focusedHeight} vs ${rect.width}x${rect.height}`);

                const computedStyle = window.getComputedStyle(focusedElement);
                console.log(`🔍 Computed width: ${computedStyle.width}, height: ${computedStyle.height}`);
                console.log(`🔍 Max-width: ${computedStyle.maxWidth}, max-height: ${computedStyle.maxHeight}`);
            }, 100);
        });

        console.log('✅ Focus mode activated with simple fade-in');
    }

    /**
     * Clone video tracks from original element to cloned element
     * @param {HTMLElement} originalElement - Original participant element
     * @param {HTMLElement} clonedElement - Cloned participant element
     * @param {string} participantId - Participant ID
     */
    cloneVideoTracks(originalElement, clonedElement, participantId) {
        console.log(`🎬 Cloning video tracks for ${participantId}...`);

        try {
            // Get video elements from both original and clone
            const originalVideo = originalElement.querySelector('video');
            const clonedVideo = clonedElement.querySelector('video');

            if (!originalVideo || !clonedVideo) {
                console.log(`⚠️ Video elements not found for cloning - original: ${!!originalVideo}, clone: ${!!clonedVideo}`);
                return;
            }

            // If original video has a srcObject (MediaStream), clone it to the cloned video
            if (originalVideo.srcObject) {
                clonedVideo.srcObject = originalVideo.srcObject;
                clonedVideo.play().catch(e => {
                    console.log(`⚠️ Could not auto-play cloned video for ${participantId}:`, e);
                });
                console.log(`✅ Video track cloned for ${participantId}`);
            } else {
                console.log(`⚠️ No srcObject found on original video for ${participantId}`);
            }

            // Sync other video properties
            clonedVideo.muted = originalVideo.muted;
            clonedVideo.autoplay = originalVideo.autoplay;
            clonedVideo.playsInline = originalVideo.playsInline;

        } catch (error) {
            console.error(`❌ Error cloning video tracks for ${participantId}:`, error);
        }
    }

    /**
     * Setup fullscreen change listener for automatic repositioning
     */
    setupFullscreenListener() {
        if (this.fullscreenListener) {
            return; // Already set up
        }

        this.fullscreenListener = () => {
            const isFullscreen = !!(document.fullscreenElement || document.webkitFullscreenElement ||
                document.mozFullScreenElement || document.msFullscreenElement);
            console.log(`📺 Fullscreen state changed to: ${isFullscreen ? 'FULLSCREEN' : 'NORMAL'}, recalculating focused video...`);

            // Force immediate recalculation for both entering and exiting fullscreen
            setTimeout(() => {
                if (this.isFocusModeActive && this._focusedElement) {
                    console.log('🔧 Force recalculating focus mode size due to fullscreen change');
                    this.recalculateFocusedVideoSize();
                }
            }, 50); // Shorter delay for immediate response

            // Also add a secondary check after a longer delay to ensure it worked
            setTimeout(() => {
                if (this.isFocusModeActive && this._focusedElement) {
                    console.log('🔧 Secondary focus mode size check after fullscreen change');
                    this.recalculateFocusedVideoSize();
                }
            }, 300);

            // Add a third check after an even longer delay for stubborn cases
            setTimeout(() => {
                if (this.isFocusModeActive && this._focusedElement) {
                    console.log('🔧 Final focus mode size check after fullscreen change');
                    this.recalculateFocusedVideoSize();
                }
            }, 1000);
        };

        // Add all fullscreen event listeners
        document.addEventListener('fullscreenchange', this.fullscreenListener);
        document.addEventListener('webkitfullscreenchange', this.fullscreenListener);
        document.addEventListener('mozfullscreenchange', this.fullscreenListener);
        document.addEventListener('MSFullscreenChange', this.fullscreenListener);

        // Also listen for window resize as a fallback
        this.fullscreenResizeListener = () => {
            if (this.isFocusModeActive && this._focusedElement) {
                console.log('🔧 Window resize detected during focus mode, recalculating...');
                clearTimeout(this.fullscreenResizeTimeout);
                this.fullscreenResizeTimeout = setTimeout(() => {
                    this.recalculateFocusedVideoSize();
                }, 100);
            }
        };
        window.addEventListener('resize', this.fullscreenResizeListener);

        // Add a global method for manual testing
        window.testFocusResize = () => {
            console.log('🧪 Manual focus resize test triggered');
            if (this.isFocusModeActive && this._focusedElement) {
                this.recalculateFocusedVideoSize();
            } else {
                console.log('❌ No active focus mode to resize');
            }
        };

        console.log('📺 Enhanced fullscreen listener set up with immediate recalculation and manual test function');
        console.log('🧪 You can test manual resize by running: window.testFocusResize() in console');
    }

    /**
     * Setup window resize listener for focus mode
     */
    setupFocusResizeListener() {
        if (this.focusResizeListener) {
            return; // Already set up
        }

        this.focusResizeListener = () => {
            console.log('📏 Window resized, recalculating focused video...');

            // Debounce resize events
            clearTimeout(this.resizeTimeout);
            this.resizeTimeout = setTimeout(() => {
                this.recalculateFocusedVideoSize();
            }, 150);
        };

        window.addEventListener('resize', this.focusResizeListener);
        console.log('📏 Focus resize listener set up');
    }

    /**
     * Start monitoring video area size changes during focus mode
     */
    startFocusModeMonitoring() {
        if (this.focusMonitoringInterval) {
            return; // Already monitoring
        }

        let lastVideoAreaSize = null;

        this.focusMonitoringInterval = setInterval(() => {
            if (!this.isFocusModeActive || !this._focusedElement) {
                return;
            }

            const videoArea = document.getElementById('videoArea');
            if (!videoArea) return;

            const currentSize = `${videoArea.offsetWidth}x${videoArea.offsetHeight}`;

            if (lastVideoAreaSize && lastVideoAreaSize !== currentSize) {
                console.log(`📏 Video area size changed from ${lastVideoAreaSize} to ${currentSize} - recalculating focus mode`);
                this.recalculateFocusedVideoSize();
            }

            lastVideoAreaSize = currentSize;
        }, 250); // Check every 250ms

        console.log('👁️ Started focus mode monitoring (checks every 250ms)');
    }

    /**
     * Stop monitoring video area size changes
     */
    stopFocusModeMonitoring() {
        if (this.focusMonitoringInterval) {
            clearInterval(this.focusMonitoringInterval);
            this.focusMonitoringInterval = null;
            console.log('👁️ Stopped focus mode monitoring');
        }
    }

    /**
     * Recalculate focused video size and position (for fullscreen changes)
     */
    recalculateFocusedVideoSize() {
        if (!this.isFocusModeActive || !this._focusedElement) {
            console.log('⚠️ No active focus mode or focused element found');
            return;
        }

        console.log('🔄 Recalculating focused video size...');

        const videoArea = document.getElementById('videoArea');
        if (!videoArea) {
            console.log('⚠️ Video area not found');
            return;
        }

        const participantElement = this._focusOriginalElement;
        if (!participantElement) {
            console.log('⚠️ Original participant element not found');
            return;
        }

        // Force a reflow to get fresh measurements
        videoArea.offsetHeight; // Force reflow

        // Recalculate dimensions with fresh measurements
        const videoAreaRect = videoArea.getBoundingClientRect();
        const padding = 30;
        const availableWidth = Math.max(200, videoAreaRect.width - (padding * 2));
        const availableHeight = Math.max(150, videoAreaRect.height - (padding * 2));

        console.log(`📊 Video area fresh bounds: ${videoAreaRect.width}x${videoAreaRect.height}`);
        console.log(`📊 Available space after padding: ${availableWidth}x${availableHeight}`);

        // Get video aspect ratio
        const originalVideo = participantElement.querySelector('video');
        let videoAspectRatio = 16 / 9;

        // Check if this is a screen share (different aspect ratio handling)
        const isScreenShare = participantElement.dataset.isScreenShare === 'true';

        if (originalVideo && originalVideo.videoWidth && originalVideo.videoHeight) {
            videoAspectRatio = originalVideo.videoWidth / originalVideo.videoHeight;
        }

        // For screen shares, use a more conservative aspect ratio to ensure content fits
        if (isScreenShare) {
            videoAspectRatio = Math.min(videoAspectRatio, 16 / 9); // Cap at 16:9 for screen shares
            console.log(`🖥️ Screen share detected during resize, adjusted aspect ratio: ${videoAspectRatio.toFixed(2)}`);
        }

        // Calculate new size - prioritize fitting within available space
        let focusedHeight = availableHeight;
        let focusedWidth = focusedHeight * videoAspectRatio;

        if (focusedWidth > availableWidth) {
            focusedWidth = availableWidth;
            focusedHeight = focusedWidth / videoAspectRatio;
        }

        // Ensure minimum size
        focusedWidth = Math.max(focusedWidth, 200);
        focusedHeight = Math.max(focusedHeight, 150);

        // AGGRESSIVE: Ensure we NEVER exceed video area bounds
        focusedWidth = Math.min(focusedWidth, availableWidth);
        focusedHeight = Math.min(focusedHeight, availableHeight);

        // Double check - if still too big, scale down proportionally
        if (focusedWidth > availableWidth || focusedHeight > availableHeight) {
            const scaleX = availableWidth / focusedWidth;
            const scaleY = availableHeight / focusedHeight;
            const scale = Math.min(scaleX, scaleY);

            focusedWidth = Math.floor(focusedWidth * scale);
            focusedHeight = Math.floor(focusedHeight * scale);

            console.log(`📊 Applied scale factor ${scale.toFixed(2)} to fit within bounds`);
        }

        console.log(`🔄 Final focused video size: ${focusedWidth}x${focusedHeight}`);
        console.log(`📊 Constraint check: width ${focusedWidth} <= ${availableWidth}, height ${focusedHeight} <= ${availableHeight}`);

        // Apply new size with smooth transition - OVERRIDE ALL CSS CONSTRAINTS
        const focusedElement = this._focusedElement;
        focusedElement.style.setProperty('transition', 'width 300ms ease, height 300ms ease', 'important');
        focusedElement.style.setProperty('width', `${focusedWidth}px`, 'important');
        focusedElement.style.setProperty('height', `${focusedHeight}px`, 'important');
        focusedElement.style.setProperty('max-width', `${focusedWidth}px`, 'important');
        focusedElement.style.setProperty('max-height', `${focusedHeight}px`, 'important');
        focusedElement.style.setProperty('min-width', 'auto', 'important');
        focusedElement.style.setProperty('min-height', 'auto', 'important');

        // Reset transition after animation
        setTimeout(() => {
            focusedElement.style.transition = 'opacity 400ms ease-in-out';
        }, 300);

        console.log('✅ Focused video size recalculated and aggressively constrained to video area');
    }

    /**
     * Clean up focus mode event listeners
     */
    cleanupFocusListeners() {
        console.log('🧹 Cleaning up focus listeners...');

        // Clean up fullscreen listeners
        if (this.fullscreenListener) {
            document.removeEventListener('fullscreenchange', this.fullscreenListener);
            document.removeEventListener('webkitfullscreenchange', this.fullscreenListener);
            document.removeEventListener('mozfullscreenchange', this.fullscreenListener);
            document.removeEventListener('MSFullscreenChange', this.fullscreenListener);
            this.fullscreenListener = null;
        }

        // Clean up fullscreen resize listener
        if (this.fullscreenResizeListener) {
            window.removeEventListener('resize', this.fullscreenResizeListener);
            this.fullscreenResizeListener = null;
        }

        // Clear fullscreen resize timeout
        if (this.fullscreenResizeTimeout) {
            clearTimeout(this.fullscreenResizeTimeout);
            this.fullscreenResizeTimeout = null;
        }

        // Clean up regular resize listener
        if (this.focusResizeListener) {
            window.removeEventListener('resize', this.focusResizeListener);
            this.focusResizeListener = null;
        }

        // Clear any pending resize timeout
        if (this.resizeTimeout) {
            clearTimeout(this.resizeTimeout);
            this.resizeTimeout = null;
        }

        // Clean up global test function
        if (window.testFocusResize) {
            delete window.testFocusResize;
        }

        // Stop any monitoring
        this.stopFocusModeMonitoring();

        console.log('✅ Focus listeners cleaned up');
    }

    /**
     * Clean up cloned video tracks to prevent memory leaks
     * @param {HTMLElement} clonedElement - Cloned participant element
     * @param {string} participantId - Participant ID
     */
    cleanupClonedVideoTracks(clonedElement, participantId) {
        console.log(`🧹 Cleaning up cloned video tracks for ${participantId}...`);

        try {
            const clonedVideo = clonedElement?.querySelector('video');
            if (clonedVideo && clonedVideo.srcObject) {
                // Set srcObject to null to release the MediaStream reference
                clonedVideo.srcObject = null;
                console.log(`✅ Cloned video tracks cleaned up for ${participantId}`);
            }
        } catch (error) {
            console.error(`❌ Error cleaning up cloned video tracks for ${participantId}:`, error);
        }
    }

    /**
     * Setup close focus button
     */
    setupCloseFocusButton() {
        const closeFocusBtn = document.getElementById('closeFocusBtn');
        if (!closeFocusBtn) return;

        // Remove any existing event listeners
        const newButton = closeFocusBtn.cloneNode(true);
        closeFocusBtn.parentNode.replaceChild(newButton, closeFocusBtn);

        // Add new event listener
        newButton.addEventListener('click', () => {
            this.exitFocusMode();
        });

        console.log('🔘 Close focus button setup complete');
    }



    /**
     * Exit focus mode and return to grid layout (Simple Fade-out)
     */
    exitFocusMode() {
        console.log('🚪 Exiting focus mode');

        if (!this.isFocusModeActive || !this._focusOriginalElement || !this._focusedElement) {
            console.log('⚠️ No active focus mode to exit');
            return;
        }

        const participantElement = this._focusOriginalElement;
        const focusedElement = this._focusedElement;
        const videoArea = document.getElementById('videoArea');
        const overlay = document.getElementById('focusOverlay');

        console.log('🎬 Starting fade-out animation...');

        // Fade out focused element and overlay
        focusedElement.style.opacity = '0';
        if (overlay) {
            overlay.style.opacity = '0';
        }

        // Remove focus mode class from video area
        if (videoArea) {
            videoArea.classList.remove('focus-mode-active');
        }

        // After fade-out completes, clean up
        setTimeout(() => {
            // Clean up video tracks to prevent memory leaks
            this.cleanupClonedVideoTracks(focusedElement, this.focusedParticipant);

            // Remove the focused element from DOM
            if (focusedElement && focusedElement.parentNode) {
                focusedElement.remove();
                console.log('🗑️ Focused element removed from DOM');
            }

            // Hide overlay completely
            if (overlay) {
                overlay.classList.add('hidden');
                overlay.style.opacity = '';
                overlay.style.transition = '';
            }

            // Restore original element opacity and interaction
            participantElement.style.opacity = '';
            participantElement.style.pointerEvents = '';
            participantElement.style.transition = '';

            // Clean up listeners
            this.cleanupFocusListeners();

            // Stop monitoring
            this.stopFocusModeMonitoring();

            // Reset focus state
            this.isFocusModeActive = false;
            this.focusedParticipant = null;
            this._focusOriginalElement = null;
            this._focusedElement = null;

            console.log('✅ Focus mode exited - original element restored');
        }, 400);

        console.log('✅ Focus mode exit animation started');
    }









    /**
     * Adjust video area for sidebar state
     * @param {boolean} sidebarOpen - Whether sidebar is open
     */
    adjustVideoAreaForSidebar(sidebarOpen) {
        if (!this.elements.videoArea) return;

        console.log(`📋 Adjusting video area for sidebar: ${sidebarOpen ? 'open' : 'closed'}`);

        if (sidebarOpen) {
            this.elements.videoArea.classList.add('mr-96');
        } else {
            this.elements.videoArea.classList.remove('mr-96');
        }

        // Trigger layout recalculation if needed
        this.updateVideoLayoutClasses();
    }

    /**
     * Get current layout state
     * @returns {Object} Layout state
     */
    getLayoutState() {
        return {
            isFocusModeActive: this.isFocusModeActive,
            focusedParticipant: this.focusedParticipant,
            isSidebarOpen: this.isSidebarOpen,
            participantCount: this.elements.videoGrid?.children.length || 0
        };
    }

    /**
     * Handle window resize
     */
    handleResize() {
        this.calculateMeetingHeight();
        this.updateVideoLayoutClasses();
    }

    /**
     * Destroy layout manager and clean up
     */
    destroy() {
        console.log('🧹 Destroying layout manager...');

        // Remove event listeners
        window.removeEventListener('resize', this.handleResize);

        // Clean up observers
        if (this.responsiveObserver) {
            this.responsiveObserver.disconnect();
            this.responsiveObserver = null;
        }

        // Clean up focus mode
        if (this.isFocusModeActive) {
            this.exitFocusMode();
        }

        // Clean up focus listeners
        this.cleanupFocusListeners();

        // Clean up any remaining focused elements
        const focusedElements = document.querySelectorAll('[id^="focused-"]');
        focusedElements.forEach(element => {
            console.log(`🗑️ Cleaning up focused element: ${element.id}`);
            const participantId = element.id.replace('focused-', '');
            this.cleanupClonedVideoTracks(element, participantId);
            element.remove();
        });

        // Clean up global reference
        window.livekitLayout = null;

        // Clean up any remaining placeholders (legacy cleanup)
        const placeholders = document.querySelectorAll('[id^="placeholder-"]');
        placeholders.forEach(placeholder => placeholder.remove());

        // Note: Focus listeners are cleaned up by cleanupFocusListeners() above

        // Clear references
        this.elements = {};
        this.focusedParticipant = null;

        console.log('✅ Layout manager destroyed');
    }
}
