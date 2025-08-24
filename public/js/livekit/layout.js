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

        console.log('🎨 LiveKitLayout initialized');
    }

    /**
     * Initialize layout system
     */
    initializeLayout() {
        this.cacheElements();
        this.setupResponsiveSystem();
        this.setupResizeObserver();

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
            meetingInterface: document.getElementById('livekitMeetingInterface'),
            sidebar: document.getElementById('meetingSidebar'),
            videoArea: document.querySelector('#livekitMeetingInterface .relative')
        };
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
            this.scheduleFocusedLayoutUpdate();
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
        }
    }

    /**
     * Handle container resize
     * @param {ResizeObserverEntry} entry - Resize observer entry
     */
    handleContainerResize(entry) {
        const { width, height } = entry.contentRect;
        console.log(`📐 Container resized: ${width}x${height}`);

        this.adjustVideoGridForContainer(width, height);

        if (this.isFocusModeActive) {
            this.scheduleFocusedLayoutUpdate();
        }
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
        this.isSidebarOpen = !this.elements.sidebar.classList.contains('translate-x-full');

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

        const gridConfig = this.calculateOptimalGrid(width, height, participantCount);
        this.applyOptimalGrid(gridConfig);
    }

    /**
     * Calculate optimal grid configuration
     * @param {number} width - Available width
     * @param {number} height - Available height
     * @param {number} participantCount - Number of participants
     * @returns {Object} Grid configuration
     */
    calculateOptimalGrid(width, height, participantCount) {
        if (participantCount === 0) {
            return { cols: 1, rows: 1, itemWidth: width, itemHeight: height };
        }

        // Handle single participant
        if (participantCount === 1) {
            return {
                cols: 1,
                rows: 1,
                itemWidth: Math.min(width, height * 16 / 9),
                itemHeight: Math.min(height, width * 9 / 16)
            };
        }

        let bestConfig = { cols: 1, rows: participantCount, itemWidth: 0, itemHeight: 0, score: 0 };

        // Try different grid configurations
        for (let cols = 1; cols <= Math.ceil(Math.sqrt(participantCount * 2)); cols++) {
            const rows = Math.ceil(participantCount / cols);

            const itemWidth = width / cols;
            const itemHeight = height / rows;

            // Calculate aspect ratio score (prefer 16:9)
            const aspectRatio = itemWidth / itemHeight;
            const targetAspectRatio = 16 / 9;
            const aspectScore = 1 - Math.abs(aspectRatio - targetAspectRatio) / targetAspectRatio;

            // Calculate area utilization score
            const totalArea = itemWidth * itemHeight * participantCount;
            const availableArea = width * height;
            const utilizationScore = totalArea / availableArea;

            // Combined score
            const score = aspectScore * 0.7 + utilizationScore * 0.3;

            if (score > bestConfig.score) {
                bestConfig = { cols, rows, itemWidth, itemHeight, score };
            }
        }

        return bestConfig;
    }

    /**
     * Apply optimal grid configuration
     * @param {Object} gridConfig - Grid configuration
     */
    applyOptimalGrid(gridConfig) {
        if (!this.elements.videoGrid) return;

        const { cols, rows, itemWidth, itemHeight } = gridConfig;

        // Update CSS custom properties for grid
        this.elements.videoGrid.style.setProperty('--grid-cols', cols);
        this.elements.videoGrid.style.setProperty('--grid-rows', rows);
        this.elements.videoGrid.style.setProperty('--item-width', `${itemWidth}px`);
        this.elements.videoGrid.style.setProperty('--item-height', `${itemHeight}px`);

        // Update grid CSS classes
        this.elements.videoGrid.className = `grid gap-2 h-full w-full place-items-center`;
        this.elements.videoGrid.style.gridTemplateColumns = `repeat(${cols}, 1fr)`;
        this.elements.videoGrid.style.gridTemplateRows = `repeat(${rows}, 1fr)`;

        console.log(`🎯 Applied grid: ${cols}x${rows} (${itemWidth.toFixed(0)}x${itemHeight.toFixed(0)})`);
    }

    /**
     * Apply grid layout based on participant count
     * @param {number} participantCount - Number of participants
     */
    applyGrid(participantCount) {
        console.log(`🎯 Applying grid layout for ${participantCount} participants`);

        if (!this.elements.videoGrid) {
            console.error('❌ Video grid not found');
            return;
        }

        // Exit focus mode if active
        if (this.isFocusModeActive) {
            this.exitFocusMode();
        }

        // Update layout classes
        this.updateVideoLayoutClasses();

        // Calculate container dimensions
        const containerRect = this.elements.videoGrid.getBoundingClientRect();
        this.adjustVideoGridForContainer(containerRect.width, containerRect.height);

        console.log(`✅ Grid layout applied for ${participantCount} participants`);
    }

    /**
     * Update video layout classes based on participant count
     */
    updateVideoLayoutClasses() {
        if (!this.elements.videoGrid) return;

        const participantCount = this.elements.videoGrid.children.length;

        // Remove all existing layout classes
        const layoutClasses = [
            'grid-cols-1', 'grid-cols-2', 'grid-cols-3', 'grid-cols-4',
            'grid-rows-1', 'grid-rows-2', 'grid-rows-3', 'grid-rows-4'
        ];

        this.elements.videoGrid.classList.remove(...layoutClasses);

        // Apply responsive grid classes
        if (participantCount <= 1) {
            this.elements.videoGrid.classList.add('grid-cols-1', 'place-items-center');
        } else if (participantCount <= 4) {
            this.elements.videoGrid.classList.add('grid-cols-1', 'sm:grid-cols-2');
        } else if (participantCount <= 9) {
            this.elements.videoGrid.classList.add('grid-cols-2', 'sm:grid-cols-3');
        } else {
            this.elements.videoGrid.classList.add('grid-cols-2', 'sm:grid-cols-3', 'lg:grid-cols-4');
        }

        console.log(`🎨 Updated layout classes for ${participantCount} participants`);
    }

    /**
     * Enter focus mode for a specific participant
     * @param {string} participantId - Participant ID to focus on
     * @param {HTMLElement} participantElement - Participant DOM element
     */
    applyFocusMode(participantId, participantElement = null) {
        if (!participantElement) {
            participantElement = document.getElementById(`participant-${participantId}`);
        }

        if (!participantElement) {
            console.error(`❌ Participant element not found for ${participantId}`);
            return;
        }

        console.log(`🎯 Entering focus mode for ${participantId}`);

        this.isFocusModeActive = true;
        this.focusedParticipant = participantId;

        // Switch to horizontal layout
        this.switchToHorizontalLayout();

        // Create or update focused element
        this.createFocusedElement(participantElement);

        // Update focused layout
        this.updateFocusedLayout();

        // Add focus mode classes
        document.body.classList.add('focus-mode-active');

        // Notify callback
        if (this.config.onFocusEnter) {
            this.config.onFocusEnter(participantId, participantElement);
        }

        console.log(`✅ Focus mode activated for ${participantId}`);
    }

    /**
     * Exit focus mode
     */
    exitFocusMode() {
        console.log('🔙 Exiting focus mode');

        if (!this.isFocusModeActive) {
            console.log('⚠️ Focus mode not active');
            return;
        }

        this.isFocusModeActive = false;
        const previousFocusedParticipant = this.focusedParticipant;
        this.focusedParticipant = null;

        // Clean up focus elements
        this.cleanupFocusElements();

        // Switch back to grid layout
        this.switchToGridLayout();

        // Remove focus mode classes
        document.body.classList.remove('focus-mode-active');

        // Notify callback
        if (this.config.onFocusExit) {
            this.config.onFocusExit(previousFocusedParticipant);
        }

        console.log('✅ Focus mode exited');
    }

    /**
     * Switch to horizontal layout for focus mode
     */
    switchToHorizontalLayout() {
        if (!this.elements.videoArea) return;

        console.log('🔄 Switching to horizontal layout');

        // Add horizontal layout classes
        this.elements.videoArea.classList.add('flex', 'flex-col', 'lg:flex-row');
        this.elements.videoArea.classList.remove('relative');

        // Update video grid for horizontal layout
        if (this.elements.videoGrid) {
            this.elements.videoGrid.classList.remove('h-full');
            this.elements.videoGrid.classList.add('lg:w-1/4', 'lg:max-w-xs', 'flex', 'lg:flex-col', 'gap-2', 'p-2');
            this.elements.videoGrid.style.gridTemplateColumns = '';
            this.elements.videoGrid.style.gridTemplateRows = '';
        }
    }

    /**
     * Switch back to grid layout
     */
    switchToGridLayout() {
        if (!this.elements.videoArea) return;

        console.log('🔄 Switching to grid layout');

        // Remove horizontal layout classes
        this.elements.videoArea.classList.remove('flex', 'flex-col', 'lg:flex-row');
        this.elements.videoArea.classList.add('relative');

        // Reset video grid
        if (this.elements.videoGrid) {
            this.elements.videoGrid.classList.add('h-full');
            this.elements.videoGrid.classList.remove('lg:w-1/4', 'lg:max-w-xs', 'flex', 'lg:flex-col', 'gap-2', 'p-2');
        }

        // Reapply grid layout
        this.updateVideoLayoutClasses();
    }

    /**
     * Create focused element for focus mode
     * @param {HTMLElement} participantElement - Participant element to focus
     */
    createFocusedElement(participantElement) {
        // Remove existing focused element
        this.cleanupFocusElements();

        // Clone the participant element
        const focusedElement = participantElement.cloneNode(true);
        focusedElement.id = `focused-${participantElement.id}`;
        focusedElement.classList.add('focused-participant');
        focusedElement.classList.remove('participant-video');

        // Create focused container
        const focusedContainer = document.createElement('div');
        focusedContainer.id = 'focusedContainer';
        focusedContainer.className = 'flex-1 relative bg-gray-900 flex items-center justify-center p-4';

        focusedContainer.appendChild(focusedElement);

        // Add to video area
        if (this.elements.videoArea) {
            this.elements.videoArea.insertBefore(focusedContainer, this.elements.videoGrid);
        }

        // Setup exit button
        this.setupExitFocusButton();
    }

    /**
     * Clean up focus elements
     */
    cleanupFocusElements() {
        const focusedContainer = document.getElementById('focusedContainer');
        if (focusedContainer) {
            focusedContainer.remove();
        }

        const exitButton = document.getElementById('exitFocusButton');
        if (exitButton) {
            exitButton.remove();
        }
    }

    /**
     * Setup exit focus button
     */
    setupExitFocusButton() {
        const existingButton = document.getElementById('exitFocusButton');
        if (existingButton) return;

        const exitButton = document.createElement('button');
        exitButton.id = 'exitFocusButton';
        exitButton.className = 'absolute top-4 right-4 z-20 bg-gray-800 hover:bg-gray-700 text-white p-2 rounded-full transition-colors';
        exitButton.innerHTML = '<i class="fas fa-times text-lg"></i>';
        exitButton.title = 'إغلاق وضع التركيز';

        exitButton.addEventListener('click', () => {
            this.exitFocusMode();
        });

        const focusedContainer = document.getElementById('focusedContainer');
        if (focusedContainer) {
            focusedContainer.appendChild(exitButton);
        }
    }

    /**
     * Schedule focused layout update
     */
    scheduleFocusedLayoutUpdate() {
        if (!this.isFocusModeActive) return;

        // Debounce layout updates
        clearTimeout(this.focusedLayoutTimeout);
        this.focusedLayoutTimeout = setTimeout(() => {
            this.updateFocusedLayout();
        }, 100);
    }

    /**
     * Update focused layout positioning
     */
    updateFocusedLayout() {
        if (!this.isFocusModeActive) return;

        const focusedContainer = document.getElementById('focusedContainer');
        const focusedElement = focusedContainer?.querySelector('.focused-participant');

        if (!focusedContainer || !focusedElement) return;

        const containerRect = focusedContainer.getBoundingClientRect();
        const aspectRatio = 16 / 9;

        // Calculate optimal size while maintaining aspect ratio
        let targetWidth, targetHeight;

        if (containerRect.width / containerRect.height > aspectRatio) {
            // Container is wider than target aspect ratio
            targetHeight = Math.min(containerRect.height * 0.9, 600);
            targetWidth = targetHeight * aspectRatio;
        } else {
            // Container is taller than target aspect ratio
            targetWidth = Math.min(containerRect.width * 0.9, 800);
            targetHeight = targetWidth / aspectRatio;
        }

        // Apply styles
        focusedElement.style.width = `${targetWidth}px`;
        focusedElement.style.height = `${targetHeight}px`;
        focusedElement.style.maxWidth = '90%';
        focusedElement.style.maxHeight = '90%';

        console.log(`🎯 Updated focused layout: ${targetWidth.toFixed(0)}x${targetHeight.toFixed(0)}`);
    }

    /**
     * Adjust video area for sidebar state
     * @param {boolean} sidebarOpen - Whether sidebar is open
     */
    adjustVideoAreaForSidebar(sidebarOpen) {
        if (!this.elements.videoArea) return;

        console.log(`📋 Adjusting video area for sidebar: ${sidebarOpen ? 'open' : 'closed'}`);

        if (sidebarOpen) {
            this.elements.videoArea.classList.add('lg:mr-80');
        } else {
            this.elements.videoArea.classList.remove('lg:mr-80');
        }

        // Trigger layout recalculation
        this.scheduleFocusedLayoutUpdate();
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
        this.scheduleFocusedLayoutUpdate();
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

        // Clear timeouts
        clearTimeout(this.focusedLayoutTimeout);

        // Clear references
        this.elements = {};
        this.focusedParticipant = null;

        console.log('✅ Layout manager destroyed');
    }
}
