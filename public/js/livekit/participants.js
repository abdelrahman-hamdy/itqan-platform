/**
 * LiveKit Participants Module
 * Handles participant management, DOM mapping, role badges, and participant list updates
 */

/**
 * Participant manager for LiveKit participants
 */
class LiveKitParticipants {
    /**
     * Create a new participants manager
     * @param {Object} config - Configuration object
     * @param {Function} config.onParticipantAdded - Callback when participant is added
     * @param {Function} config.onParticipantRemoved - Callback when participant is removed
     * @param {Function} config.onParticipantClick - Callback when participant is clicked
     * @param {Object} config.meetingConfig - Meeting configuration for role detection
     */
    constructor(config = {}) {
        this.config = config;
        this.participants = new Map(); // participantId -> participant object
        this.participantElements = new Map(); // participantId -> DOM element
        this.localParticipant = null;

    }

    /**
     * Set the local participant reference
     * @param {LiveKit.LocalParticipant} localParticipant - Local participant instance
     */
    setLocalParticipant(localParticipant) {
        this.localParticipant = localParticipant;
    }

    /**
     * Add a participant to the meeting
     * @param {LiveKit.Participant} participant - Participant to add
     */
    addParticipant(participant) {
        const participantId = participant.identity;


        if (this.participants.has(participantId)) {
            return;
        }

        // Store participant reference
        this.participants.set(participantId, participant);

        // Create DOM element for participant
        this.createParticipantElement(participant);

        // Notify callback
        if (this.config.onParticipantAdded) {
            this.config.onParticipantAdded(participant);
        }

    }

    /**
     * Remove a participant from the meeting
     * @param {string} participantId - Participant ID to remove
     */
    removeParticipant(participantId) {

        if (!this.participants.has(participantId)) {
            return;
        }

        // Get participant for callback
        const participant = this.participants.get(participantId);

        // Remove DOM element
        this.removeParticipantElement(participantId);

        // Remove from map
        this.participants.delete(participantId);

        // Notify callback
        if (this.config.onParticipantRemoved) {
            this.config.onParticipantRemoved(participant, participantId);
        }

    }

    /**
     * Create DOM element for participant
     * @param {LiveKit.Participant} participant - Participant object
     */
    createParticipantElement(participant) {
        const participantId = participant.identity;
        const isLocal = participant === this.localParticipant;


        const videoGrid = document.getElementById('videoGrid');
        if (!videoGrid) {
            return;
        }

        // Create participant container
        const participantDiv = document.createElement('div');
        participantDiv.id = `participant-${participantId}`;
        participantDiv.className = 'participant-video relative bg-gray-800 rounded-lg overflow-hidden aspect-video w-full h-full group';
        participantDiv.dataset.participantId = participantId;
        participantDiv.dataset.isLocal = isLocal;

        // Note: Focus mode is now only triggered by the close button, not by clicking on the video
        // This prevents accidental focus mode activation

        // Create video element for tracks (starts hidden)
        const videoElement = document.createElement('video');
        videoElement.id = `video-${participantId}`;
        videoElement.className = 'absolute inset-0 w-full h-full object-cover opacity-0 transition-opacity duration-300 z-10';
        videoElement.autoplay = true;
        videoElement.playsInline = true;
        videoElement.muted = isLocal; // Mute local video to avoid feedback
        videoElement.style.display = 'none'; // Hidden until track is attached and confirmed working

        // Create placeholder with avatar and name
        const placeholder = this.createParticipantPlaceholder(participant, isLocal);
        
        // Add video element first (behind placeholder)
        participantDiv.appendChild(videoElement);
        // Add placeholder on top
        participantDiv.appendChild(placeholder);

        // Create name overlay HTML (hidden by default, shown when video is on)
        const nameOverlay = document.createElement('div');
        nameOverlay.id = `name-overlay-${participantId}`;
        nameOverlay.className = 'absolute bottom-2 left-2 z-20 pointer-events-none opacity-0 transition-opacity duration-300';
        nameOverlay.style.display = 'none';
        nameOverlay.style.maxWidth = '200px'; // Ensure consistent max width
        
        // Create overlay content with Tailwind CSS - narrower width, more transparent, with better spacing
        nameOverlay.innerHTML = `
            <div class="flex items-center justify-between bg-black bg-opacity-60 rounded-lg px-4 py-1.5 text-white text-sm max-w-48 shadow-lg border border-gray-600">
                <div class="flex items-center flex-1 min-w-0">
                    <span class="font-semibold truncate" id="overlay-name-${participantId}">${this.getParticipantDisplayName(participant)}</span>
                    ${this.isParticipantTeacher(participant, isLocal) ?
                        `<span class="bg-green-600 text-white text-xs px-2 py-0.5 rounded-full font-bold whitespace-nowrap flex-shrink-0 shadow-sm mr-1">${t('participants.teacher')}</span>` :
                        ''
                    }
                </div>
                <div class="flex items-center gap-2 mr-1 flex-shrink-0">
                    <i id="overlay-mic-${participantId}" class="${isLocal ? 'ri-mic-line' : 'ri-mic-off-line'} text-sm ${isLocal ? 'text-green-500' : 'text-red-500'}"></i>
                </div>
            </div>
        `;
        
        // Add name overlay to participant
        participantDiv.appendChild(nameOverlay);

        // Create hand raise indicator (hidden by default) - positioned at top right
        const handRaiseIndicator = document.createElement('div');
        handRaiseIndicator.id = `hand-raise-${participantId}`;
        handRaiseIndicator.className = 'absolute top-2 right-2 z-30 bg-yellow-500 text-white rounded-full w-8 h-8 flex items-center justify-center shadow-lg border-2 border-white opacity-0 transition-all duration-300 transform scale-75';
        handRaiseIndicator.style.display = 'none';
        handRaiseIndicator.innerHTML = `
            <i class="fas fa-hand text-sm"></i>
        `;
        
        // Add hand raise indicator to participant
        participantDiv.appendChild(handRaiseIndicator);

        // Add click event listener to the entire participant div for focus mode
        participantDiv.addEventListener('click', (e) => {
            e.preventDefault();
            e.stopPropagation();


            if (this.config.onParticipantClick) {
                this.config.onParticipantClick(participantDiv, participant);
            }
        });

        // Add to video grid
        videoGrid.appendChild(participantDiv);

        // Store element reference
        this.participantElements.set(participantId, participantDiv);

        // Ensure participant starts with placeholder visible (prevents dark background issues)
        // Add a small delay to ensure the DOM element is fully rendered
        setTimeout(() => {
            this.ensureParticipantPlaceholderVisible(participantId);
            // Immediately sync icons to actual track state
            this.syncParticipantIcons(participant);
        }, 100);


        // Debug: Log the participant element
    }

    /**
     * Create participant placeholder with avatar and details
     * @param {LiveKit.Participant} participant - Participant object
     * @param {boolean} isLocal - Whether this is the local participant
     * @returns {HTMLElement} Placeholder element
     */
    createParticipantPlaceholder(participant, isLocal) {
        const participantId = participant.identity;
        const placeholder = document.createElement('div');
        placeholder.className = 'absolute inset-0 flex flex-col items-center justify-center bg-gradient-to-br from-blue-900 to-gray-800 transition-opacity duration-300 z-10';

        // Generate avatar color based on participant identity
        const colors = ['bg-blue-600', 'bg-green-600', 'bg-purple-600', 'bg-red-600', 'bg-yellow-600', 'bg-indigo-600', 'bg-pink-600', 'bg-gray-600'];
        const avatarColor = colors[participantId.length % colors.length];

        // Get display name and initials
        const displayName = this.getParticipantDisplayName(participant);
        const initials = this.getParticipantInitials(displayName);

        // Check if participant is a teacher
        const isTeacher = this.isParticipantTeacher(participant, isLocal);

        // Create teacher badge
        const teacherBadge = isTeacher ?
            `<div class="absolute -top-1 -right-1 bg-green-600 text-white text-xs px-2 py-0.5 rounded-full font-bold shadow-lg">${t('participants.teacher')}</div>` :
            '';

        // Set data attributes
        placeholder.setAttribute('data-participant-id', participantId);
        placeholder.setAttribute('data-camera-off', 'true');

        // Initialize all icons as OFF (red) - they will be updated by track events to actual state
        // This ensures consistent behavior and avoids wrong assumptions
        const cameraStatusClass = 'text-red-500';
        const cameraStatusIcon = 'ri-video-off-line';
        const micStatusClass = 'text-red-500';
        const micStatusIcon = 'ri-mic-off-line';


        placeholder.innerHTML = `
            <div class="flex flex-col items-center text-center">
                <div class="relative w-16 h-16 sm:w-20 sm:h-20 ${avatarColor} rounded-full flex items-center justify-center mb-3 shadow-lg transition-transform duration-200 group-hover:scale-110">
                    <span class="text-white font-bold text-lg sm:text-xl">${initials}</span>
                    ${teacherBadge}
                </div>
                <p class="text-white text-sm sm:text-base font-medium px-2 text-center">${displayName}</p>
                <p class="text-gray-300 text-xs mt-1">${isLocal ? t('participants.you') : isTeacher ? t('participants.teacher') : t('participants.student')}</p>

                <!-- Camera and Mic status indicators -->
                <div class="mt-2 flex items-center justify-center gap-3">
                    <div id="camera-status-${participantId}" class="${cameraStatusClass}">
                        <i class="${cameraStatusIcon} text-sm"></i>
                    </div>
                    <div id="mic-status-${participantId}" class="${micStatusClass}">
                        <i class="${micStatusIcon} text-sm"></i>
                    </div>
                </div>
            </div>
        `;

        return placeholder;
    }

    /**
     * Remove DOM element for participant
     * @param {string} participantId - Participant ID
     */
    removeParticipantElement(participantId) {

        const element = this.participantElements.get(participantId);
        if (element && element.parentNode) {
            element.remove();
        }

        this.participantElements.delete(participantId);
    }

    /**
     * Get participant display name
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

        // Clean up the identity to extract readable name
        if (participant.identity) {
            return this.cleanParticipantIdentity(participant.identity);
        }

        return t('participants.participant');
    }

    /**
     * Clean participant identity to extract readable name
     * @param {string} identity - Raw participant identity
     * @returns {string} Clean, readable name
     */
    cleanParticipantIdentity(identity) {
        // Remove common prefixes and suffixes
        let cleanName = identity;
        
        // Remove numeric prefixes (like "17_")
        cleanName = cleanName.replace(/^\d+_/, '');
        
        // Remove "teacher" suffix if present
        cleanName = cleanName.replace(/_teacher$/, '');
        
        // Remove "student" suffix if present
        cleanName = cleanName.replace(/_student$/, '');
        
        // Replace remaining underscores with spaces for better readability
        cleanName = cleanName.replace(/_/g, ' ');
        
        // Trim any extra whitespace
        cleanName = cleanName.trim();
        
        // If we end up with an empty string, return a default
        if (!cleanName) {
            return t('participants.student');
        }

        return cleanName;
    }

    /**
     * Get participant initials for avatar
     * @param {string} displayName - Display name
     * @returns {string} Initials
     */
    getParticipantInitials(displayName) {
        // Use the clean display name for initials
        const cleanName = this.cleanParticipantIdentity(displayName);
        return cleanName.split(' ')
            .map(n => n[0])
            .join('')
            .toUpperCase()
            .slice(0, 2);
    }

    /**
     * Check if participant is a teacher
     * @param {LiveKit.Participant} participant - Participant object
     * @param {boolean} isLocal - Whether this is the local participant
     * @returns {boolean} Whether participant is a teacher
     */
    isParticipantTeacher(participant, isLocal = null) {
        if (isLocal === null) {
            isLocal = participant === this.localParticipant;
        }

        // Check metadata first
        if (participant.metadata) {
            try {
                const metadata = JSON.parse(participant.metadata);
                if (metadata.role === 'teacher') {
                    return true;
                }
            } catch (e) {
                // Ignore JSON parse errors
            }
        }

        // Check participant identity for teacher suffix
        if (participant.identity && participant.identity.includes('teacher')) {
            return true;
        }

        // For local participant, check meeting config
        if (isLocal && this.config.meetingConfig) {
            return this.config.meetingConfig.role === 'teacher';
        }

        return false;
    }

    /**
     * Get participant element by ID
     * @param {string} participantId - Participant ID
     * @returns {HTMLElement|null} Participant DOM element
     */
    getParticipantElement(participantId) {
        return this.participantElements.get(participantId) || null;
    }

    /**
     * Get participant object by ID
     * @param {string} participantId - Participant ID
     * @returns {LiveKit.Participant|null} Participant object
     */
    getParticipant(participantId) {
        return this.participants.get(participantId) || null;
    }

    /**
     * Get all participant IDs
     * @returns {string[]} Array of participant IDs
     */
    getAllParticipantIds() {
        return Array.from(this.participants.keys());
    }

    /**
     * Get all participants
     * @returns {LiveKit.Participant[]} Array of participants
     */
    getAllParticipants() {
        return Array.from(this.participants.values());
    }

    /**
     * Get participant count
     * @returns {number} Number of participants
     */
    getParticipantCount() {
        return this.participants.size;
    }

    /**
     * Update participant list in sidebar
     */
    updateParticipantsList() {

        const participantsList = document.getElementById('participantsList');
        if (!participantsList) {
            return;
        }

        // Clear existing list
        participantsList.innerHTML = '';

        // Add participants directly without header
        const participantsContainer = document.createElement('div');
        participantsContainer.className = 'flex-1 overflow-y-auto';

        for (const participant of this.participants.values()) {
            const participantItem = this.createParticipantListItem(participant);
            participantsContainer.appendChild(participantItem);
        }

        participantsList.appendChild(participantsContainer);

    }

    /**
     * Create participant list item for sidebar
     * @param {LiveKit.Participant} participant - Participant object
     * @returns {HTMLElement} Participant list item
     */
    createParticipantListItem(participant) {
        const participantId = participant.identity;
        const isLocal = participant === this.localParticipant;
        const isTeacher = this.isParticipantTeacher(participant, isLocal);
        const displayName = this.getParticipantDisplayName(participant);

        const listItem = document.createElement('div');
        listItem.className = 'flex items-center justify-between px-2 py-2 hover:bg-gray-700 transition-colors';

        // Participant info
        const participantInfo = document.createElement('div');
        participantInfo.className = 'flex items-center space-x-3 space-x-reverse';

        // Avatar
        const colors = ['bg-blue-600', 'bg-green-600', 'bg-purple-600', 'bg-red-600', 'bg-yellow-600', 'bg-indigo-600', 'bg-pink-600', 'bg-gray-600'];
        const avatarColor = colors[participantId.length % colors.length];
        const initials = this.getParticipantInitials(displayName);

        const avatar = document.createElement('div');
        avatar.className = `relative w-8 h-8 ${avatarColor} rounded-full flex items-center justify-center`;
        avatar.innerHTML = `
            <span class="text-white font-medium text-sm">${initials}</span>
            ${isTeacher ? '<div class="absolute -top-1 -right-1 w-3 h-3 bg-green-500 rounded-full border border-gray-800"></div>' : ''}
        `;

        // Name and status
        const nameContainer = document.createElement('div');
        nameContainer.innerHTML = `
            <p class="text-white font-medium text-sm">${displayName}</p>
            <p class="text-gray-400 text-xs">${isLocal ? t('participants.you') : isTeacher ? t('participants.teacher') : t('participants.student')}</p>
        `;

        participantInfo.appendChild(avatar);
        participantInfo.appendChild(nameContainer);

        // Status indicators
        const statusContainer = document.createElement('div');
        statusContainer.className = 'flex items-center space-x-2 space-x-reverse';

        // Microphone status
        const micStatus = document.createElement('div');
        micStatus.id = `mic-status-list-${participantId}`;
        micStatus.className = 'w-4 h-4 bg-gray-600 rounded-full flex items-center justify-center';
        micStatus.innerHTML = '<i class="fas fa-microphone-slash text-white text-xs"></i>';

        // Camera status
        const camStatus = document.createElement('div');
        camStatus.id = `cam-status-list-${participantId}`;
        camStatus.className = 'w-4 h-4 bg-gray-600 rounded-full flex items-center justify-center';
        camStatus.innerHTML = '<i class="fas fa-video-slash text-white text-xs"></i>';

        statusContainer.appendChild(micStatus);
        statusContainer.appendChild(camStatus);

        listItem.appendChild(participantInfo);
        listItem.appendChild(statusContainer);

        return listItem;
    }

    /**
     * Update participant status indicators in the list
     * @param {string} participantId - Participant ID
     * @param {string} type - Status type ('mic' or 'cam')
     * @param {boolean} isActive - Whether the device is active
     */
    updateParticipantListStatus(participantId, type, isActive) {
        const statusElement = document.getElementById(`${type}-status-list-${participantId}`);
        if (!statusElement) return;

        if (type === 'mic') {
            if (isActive) {
                statusElement.className = 'w-4 h-4 bg-green-600 rounded-full flex items-center justify-center';
                statusElement.innerHTML = '<i class="fas fa-microphone text-white text-xs"></i>';
            } else {
                statusElement.className = 'w-4 h-4 bg-gray-600 rounded-full flex items-center justify-center';
                statusElement.innerHTML = '<i class="fas fa-microphone-slash text-white text-xs"></i>';
            }
        } else if (type === 'cam') {
            if (isActive) {
                statusElement.className = 'w-4 h-4 bg-green-600 rounded-full flex items-center justify-center';
                statusElement.innerHTML = '<i class="fas fa-video text-white text-xs"></i>';
            } else {
                statusElement.className = 'w-4 h-4 bg-gray-600 rounded-full flex items-center justify-center';
                statusElement.innerHTML = '<i class="fas fa-video-slash text-white text-xs"></i>';
            }
        }
    }

    /**
     * Add teacher badge to participant
     * @param {string} participantId - Participant ID
     */
    addTeacherBadge(participantId) {
        const element = this.getParticipantElement(participantId);
        if (!element) return;

        const avatar = element.querySelector('.rounded-full');
        if (!avatar) return;

        // Check if badge already exists
        if (avatar.querySelector('.bg-green-600')) return;

        const badge = document.createElement('div');
        badge.className = 'absolute -top-1 -right-1 bg-green-600 text-white text-xs px-2 py-0.5 rounded-full font-bold shadow-lg';
        badge.textContent = t('participants.teacher');
        avatar.appendChild(badge);
    }

    /**
     * Remove teacher badge from participant
     * @param {string} participantId - Participant ID
     */
    removeTeacherBadge(participantId) {
        const element = this.getParticipantElement(participantId);
        if (!element) return;

        const badge = element.querySelector('.bg-green-600');
        if (badge) {
            badge.remove();
        }
    }

    /**
     * Highlight active speaker
     * @param {string[]} activeSpeakerIds - Array of active speaker participant IDs
     */
    highlightActiveSpeakers(activeSpeakerIds) {
        // Remove existing highlights
        for (const element of this.participantElements.values()) {
            element.classList.remove('ring-4', 'ring-blue-500', 'ring-opacity-75');
        }

        // Add highlights to active speakers
        for (const participantId of activeSpeakerIds) {
            const element = this.getParticipantElement(participantId);
            if (element) {
                element.classList.add('ring-4', 'ring-blue-500', 'ring-opacity-75');
            }
        }
    }

    /**
     * Ensure participant placeholder is visible (prevents dark background issues)
     * @param {string} participantId - Participant ID
     */
    ensureParticipantPlaceholderVisible(participantId) {

        const participantElement = this.getParticipantElement(participantId);
        if (!participantElement) {
            return;
        }

        const placeholder = participantElement.querySelector('.absolute.inset-0.flex.flex-col');
        const videoElement = participantElement.querySelector('video');

        if (placeholder) {
            // Ensure placeholder is fully visible by default
            placeholder.style.opacity = '1';
            placeholder.style.zIndex = '15';
            placeholder.style.backgroundColor = '';

            // Ensure all placeholder content is visible
            const mainContent = placeholder.querySelector('.flex.flex-col.items-center');
            if (mainContent) {
                const avatar = mainContent.querySelector('.rounded-full');
                const nameElements = mainContent.querySelectorAll('p');
                
                if (avatar) avatar.style.opacity = '1';
                nameElements.forEach(p => p.style.opacity = '1');
            }

            // Reset status indicators to normal position
            const statusContainer = placeholder.querySelector('.mt-2.flex.items-center.justify-center.gap-3');
            if (statusContainer) {
                statusContainer.style.opacity = '1';
                statusContainer.style.position = '';
                statusContainer.style.bottom = '';
                statusContainer.style.left = '';
                statusContainer.style.backgroundColor = '';
                statusContainer.style.padding = '';
                statusContainer.style.borderRadius = '';
                statusContainer.style.zIndex = '';
            }

        } else {
        }

        // Ensure video is hidden initially
        if (videoElement) {
            videoElement.style.opacity = '0';
            videoElement.style.display = 'none';
            videoElement.style.visibility = 'hidden';
            videoElement.style.zIndex = '5';
        }
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
            // Clean the display name before updating
            const cleanName = this.cleanParticipantIdentity(displayName);
            nameElement.textContent = cleanName;
        }
        
        // Update teacher badge visibility
        const teacherBadge = document.querySelector(`#name-overlay-${participantId} .bg-green-600`);
        if (teacherBadge) {
            teacherBadge.style.display = isTeacher ? 'inline-block' : 'none';
        }
    }

    /**
     * Update participant display name in both placeholder and overlay
     * @param {string} participantId - Participant ID
     * @param {string} newName - New name to display
     */
    updateParticipantName(participantId, newName) {
        const cleanName = this.cleanParticipantIdentity(newName);
        
        // Update placeholder name
        const placeholder = document.querySelector(`#participant-${participantId} .absolute.inset-0.flex.flex-col p`);
        if (placeholder) {
            placeholder.textContent = cleanName;
        }
        
        // Update overlay name
        const overlayName = document.getElementById(`overlay-name-${participantId}`);
        if (overlayName) {
            overlayName.textContent = cleanName;
        }
        
        // Update initials in avatar
        const initials = this.getParticipantInitials(cleanName);
        const avatar = document.querySelector(`#participant-${participantId} .rounded-full span`);
        if (avatar) {
            avatar.textContent = initials;
        }
        
    }

    /**
     * Test hand raise functionality directly (for debugging)
     * @param {string} participantId - Participant ID to test
     */
    testHandRaiseDirectly(participantId) {
        
        // Check if element exists
        const participantElement = document.getElementById(`participant-${participantId}`);
        if (!participantElement) {
            return;
        }
        
        // Check if hand raise indicator exists
        const handRaiseIndicator = document.getElementById(`hand-raise-${participantId}`);
        if (!handRaiseIndicator) {
            
            // Try to create one manually for testing
            const newIndicator = document.createElement('div');
            newIndicator.id = `hand-raise-${participantId}`;
            newIndicator.className = 'absolute top-2 right-2 z-30 bg-yellow-500 text-white rounded-full w-8 h-8 flex items-center justify-center shadow-lg border-2 border-white';
            newIndicator.innerHTML = '<i class="fas fa-hand text-sm"></i>';
            participantElement.appendChild(newIndicator);
            
            return;
        }
        
        
        // Test show
        this.showHandRaise(participantId);
        
        // Test hide after 2 seconds
        setTimeout(() => {
            this.hideHandRaise(participantId);
        }, 2000);
    }

    /**
     * Show hand raise indicator for participant
     * @param {string} participantId - Participant ID
     */
    showHandRaise(participantId) {
        const handRaiseIndicator = document.getElementById(`hand-raise-${participantId}`);
        if (handRaiseIndicator) {
            handRaiseIndicator.style.display = 'flex';
            handRaiseIndicator.style.opacity = '1';
            handRaiseIndicator.style.transform = 'scale(1)';
            handRaiseIndicator.style.visibility = 'visible';
            
            // Debug: Check computed styles
            const computedStyle = window.getComputedStyle(handRaiseIndicator);
            console.log('Hand raise indicator computed styles', {
                display: computedStyle.display,
                opacity: computedStyle.opacity,
                visibility: computedStyle.visibility,
                position: computedStyle.position,
                top: computedStyle.top,
                right: computedStyle.right,
                zIndex: computedStyle.zIndex
            });
        } else {
            // Debug: Check what elements exist
            const allElements = document.querySelectorAll('[id^="hand-raise-"]');
        }
    }

    /**
     * Hide hand raise indicator for participant
     * @param {string} participantId - Participant ID
     */
    hideHandRaise(participantId) {
        const handRaiseIndicator = document.getElementById(`hand-raise-${participantId}`);
        if (handRaiseIndicator) {
            handRaiseIndicator.style.opacity = '0';
            handRaiseIndicator.style.transform = 'scale(0.75)';
            handRaiseIndicator.style.visibility = 'hidden';
            setTimeout(() => {
                handRaiseIndicator.style.display = 'none';
            }, 300); // Wait for transition to complete
        } else {
        }
    }

    /**
     * Update hand raise status for participant
     * @param {string} participantId - Participant ID
     * @param {boolean} isRaised - Whether hand is raised
     */
    updateHandRaiseStatus(participantId, isRaised) {
        if (isRaised) {
            this.showHandRaise(participantId);
        } else {
            this.hideHandRaise(participantId);
        }
    }

    /**
     * Sync participant icons to actual track state immediately
     * @param {LiveKit.Participant} participant - Participant to sync
     */
    syncParticipantIcons(participant) {
        const participantId = participant.identity;


        // Check actual track publications
        const videoPublication = participant.getTrackPublication?.(window.LiveKit.Track.Source.Camera);
        const audioPublication = participant.getTrackPublication?.(window.LiveKit.Track.Source.Microphone);

        const hasActiveVideo = videoPublication && !videoPublication.isMuted && videoPublication.track;
        const hasActiveAudio = audioPublication && !audioPublication.isMuted && audioPublication.track;

        console.log('Participant media status update', {
            camera: hasActiveVideo ? 'ON' : 'OFF',
            mic: hasActiveAudio ? 'ON' : 'OFF',
            hasVideoPublication: !!videoPublication,
            hasAudioPublication: !!audioPublication,
            videoMuted: videoPublication?.isMuted,
            audioMuted: audioPublication?.isMuted
        });

        // Update camera status icon
        const cameraStatus = document.getElementById(`camera-status-${participantId}`);
        if (cameraStatus) {
            const icon = cameraStatus.querySelector('i');
            if (hasActiveVideo) {
                cameraStatus.className = 'text-green-500';
                if (icon) icon.className = 'ri-video-line text-sm';
            } else {
                cameraStatus.className = 'text-red-500';
                if (icon) icon.className = 'ri-video-off-line text-sm';
            }
        }

        // Update mic status icon
        const micStatus = document.getElementById(`mic-status-${participantId}`);
        if (micStatus) {
            const icon = micStatus.querySelector('i');
            if (hasActiveAudio) {
                micStatus.className = 'text-green-500';
                if (icon) icon.className = 'ri-mic-line text-sm';
            } else {
                micStatus.className = 'text-red-500';
                if (icon) icon.className = 'ri-mic-off-line text-sm';
            }
        }

        // Update overlay mic status
        const overlayMicIcon = document.getElementById(`overlay-mic-${participantId}`);
        if (overlayMicIcon) {
            if (hasActiveAudio) {
                overlayMicIcon.className = 'ri-mic-line text-sm text-green-500';
            } else {
                overlayMicIcon.className = 'ri-mic-off-line text-sm text-red-500';
            }
        }

    }

    /**
     * Destroy participants manager and clean up
     */
    destroy() {

        // Remove all participant elements
        for (const participantId of this.participants.keys()) {
            this.removeParticipantElement(participantId);
        }

        this.participants.clear();
        this.participantElements.clear();
        this.localParticipant = null;

    }
}

// Make class globally available
window.LiveKitParticipants = LiveKitParticipants;
