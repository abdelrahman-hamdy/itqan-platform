{{--
    LiveKit Meeting Interface Component
    Unified meeting interface for both teachers and students
    Based on official LiveKit JavaScript SDK
--}}

@props([
'session',
'userType' => 'student'
])

<!-- INLINE STYLES AND SCRIPTS - GUARANTEED TO LOAD -->
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css" integrity="sha512-Avb2QiuDEEvB4bZJYdft2mNjVShBftLdPG8FJ0V7irTLQ8Uo0qcPxh4Plq7G5tGm0rU+1SPhVotteLpBERwTkw==" crossorigin="anonymous" referrerpolicy="no-referrer">

<style>
    /* Custom CSS for meeting interface */
    .meeting-focus-enter {
        opacity: 0;
        transform: scale(0.95);
    }

    .meeting-focus-enter-active {
        opacity: 1;
        transform: scale(1);
        transition: opacity 300ms ease-out, transform 300ms ease-out;
    }

    .meeting-focus-exit {
        opacity: 1;
        transform: scale(1);
    }

    .meeting-focus-exit-active {
        opacity: 0;
        transform: scale(0.95);
        transition: opacity 300ms ease-in, transform 300ms ease-in;
    }

    /* Smooth video transitions */
    .video-transition {
        transition: all 300ms cubic-bezier(0.4, 0, 0.2, 1);
    }

    /* CRITICAL FIX: Smooth loading overlay transitions */
    #loadingOverlay {
        transition: opacity 500ms ease-out, visibility 500ms ease-out;
        pointer-events: auto;
        backdrop-filter: blur(2px);
        -webkit-backdrop-filter: blur(2px);
    }

    #loadingOverlay.fade-out {
        opacity: 0;
        visibility: hidden;
        pointer-events: none;
    }

    /* Smooth meeting interface transitions */
    #meetingInterface {
        transition: opacity 400ms ease-in;
    }

    #meetingInterface.fade-in {
        opacity: 1 !important;
    }

    /* Ensure meeting interface is visible by default */
    #meetingInterface:not(.fade-in) {
        opacity: 1;
    }

    /* Loading spinner enhancement */
    #loadingOverlay .animate-spin {
        animation: spin 1s linear infinite;
    }

    @keyframes spin {
        from { transform: rotate(0deg); }
        to { transform: rotate(360deg); }
    }

    /* Focus area styling - removed (focusArea deprecated) */

    /* Horizontal participants layout */
    #horizontalParticipants {
        scrollbar-width: thin;
        scrollbar-color: rgba(156, 163, 175, 0.5) transparent;
    }

    #horizontalParticipants::-webkit-scrollbar {
        height: 8px;
    }

    #horizontalParticipants::-webkit-scrollbar-track {
        background: transparent;
    }

    #horizontalParticipants::-webkit-scrollbar-thumb {
        background-color: rgba(156, 163, 175, 0.5);
        border-radius: 4px;
    }

    #horizontalParticipants::-webkit-scrollbar-thumb:hover {
        background-color: rgba(156, 163, 175, 0.7);
    }

    /* Participant hover effects */
    .participant-hover {
        transition: all 200ms ease-in-out;
    }

    .participant-hover:hover {
        transform: translateY(-2px);
        box-shadow: 0 10px 25px -5px rgba(0, 0, 0, 0.1), 0 10px 10px -5px rgba(0, 0, 0, 0.04);
    }

    /* Focus indicator */
    .focus-indicator {
        position: relative;
    }

    /* Hand raise indicator overlay on participant tiles */
    .hand-raise-indicator {
        position: absolute;
        top: 8px;
        right: 8px;
        width: 32px;
        height: 32px;
        border-radius: 9999px;
        background: linear-gradient(135deg, #f59e0b, #d97706);
        display: flex;
        align-items: center;
        justify-content: center;
        z-index: 30;
        box-shadow: 0 4px 12px rgba(0, 0, 0, 0.3);
        border: 2px solid white;
        animation: handRaisePulse 2s ease-in-out infinite;
        transition: all 0.3s ease;
    }

    .hand-raise-indicator:hover {
        transform: scale(1.1);
        box-shadow: 0 6px 16px rgba(0, 0, 0, 0.4);
    }

    .hand-raise-indicator i {
        color: white;
        font-size: 14px;
        filter: drop-shadow(0 1px 2px rgba(0, 0, 0, 0.3));
    }

    @keyframes handRaisePulse {
        0%, 100% {
            transform: scale(1);
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.3);
        }
        50% {
            transform: scale(1.05);
            box-shadow: 0 6px 16px rgba(0, 0, 0, 0.4);
        }
    }

    .hand-raise-indicator svg {
        width: 18px;
        height: 18px;
        fill: #111827;
    }

    .hand-raise-indicator .fa-hand {
        font-size: 16px;
        color: #111827;
    }

    .focus-indicator::after {
        content: '';
        position: absolute;
        inset: -4px;
        border: 2px solid #60a5fa;
        border-radius: 8px;
        opacity: 0.75;
        animation: focusPulse 2s ease-in-out infinite;
    }

    @keyframes focusPulse {

        0%,
        100% {
            opacity: 0.75;
        }

        50% {
            opacity: 0.4;
        }
    }

    /* Focus Mode Overlay - Full Video Area Coverage */
    #focusOverlay {
        position: absolute !important;
        top: 0 !important;
        left: 0 !important;
        right: 0 !important;
        bottom: 0 !important;
        width: 100% !important;
        height: 100% !important;
        z-index: 50 !important;
        backdrop-filter: blur(4px);
        margin: 0 !important;
        padding: 0 !important;
    }

    #closeFocusBtn {
        position: absolute !important;
        top: 16px !important;
        right: 16px !important;
        z-index: 60 !important;
        pointer-events: auto !important;
        cursor: pointer !important;
    }

    /* Focus Mode Container - Updated for CSS-first approach */
    #focusedVideoContainer {
        /* Styles now handled by .focused-video-container class */
    }

    /* Participant video hover effects */
    .participant-video {
        cursor: pointer;
        transition: transform 0.3s ease;
    }

    .participant-video:hover {
        transform: scale(1.02);
    }

    /* Scale animations */
    .scale-0 {
        transform: scale(0);
    }

    .scale-100 {
        transform: scale(1);
    }

    /* Focus mode active state */
    .focus-mode-active {
        position: relative;
    }

    .focus-mode-active #videoGrid {
        pointer-events: none;
    }

    .focus-mode-active #videoGrid>* {
        pointer-events: auto;
    }

    /* CSS Classes for Focus Mode */
    .participant-video.focus-mode-active {
        position: absolute !important;
        z-index: 60 !important;
        transition: all 500ms cubic-bezier(0.4, 0, 0.2, 1) !important;
    }

    .participant-video.focus-mode-transitioning {
        transition: all 500ms cubic-bezier(0.4, 0, 0.2, 1) !important;
    }

    .participant-video.focused {
        position: absolute !important;
        top: 50% !important;
        left: 50% !important;
        transform: translate(-50%, -50%) !important;
        width: 90% !important;
        max-width: 900px !important;
        height: 90% !important;
        max-height: 80vh !important;
        z-index: 60 !important;
        transition: all 600ms cubic-bezier(0.25, 0.46, 0.45, 0.94) !important;
        margin: 0 !important;
        border-radius: 12px !important;
        box-shadow: 0 20px 40px rgba(0, 0, 0, 0.5) !important;
    }

    .participant-video.focused video {
        width: 100% !important;
        height: 100% !important;
        object-fit: cover !important;
        border-radius: 12px !important;
    }

    /* Video Area - Updated for CSS-first approach */
    #videoArea {
        /* Styles now handled by .video-area class */
    }

    /* Ensure the main content area takes full height */
    #meetingInterface {
        height: 100% !important;
        display: flex !important;
        flex-direction: column !important;
    }

    /* Ensure the grid container takes remaining space */
    #meetingInterface>.grid {
        flex: 1 !important;
        min-height: 0 !important;
        display: grid !important;
        grid-template-rows: 1fr !important;
    }

    /* Video Grid - Updated for CSS-first approach */
    #videoGrid {
        /* Styles now handled by .video-grid class */
    }

    /* Focus mode active state - Updated for CSS-first approach */
    #videoArea.focus-mode-active {
        /* Styles now handled by .video-area.focus-mode-active class */
    }

    /* Placeholder styling */
    .placeholder-overlay {
        backdrop-filter: blur(2px);
        background: linear-gradient(135deg, rgba(31, 41, 55, 0.8), rgba(55, 65, 81, 0.8));
    }

    /* Focused video - Updated for CSS-first approach */
    #focusedVideoContainer video {
        /* Styles now handled by .focused-video-container video class */
    }

    /* Enhanced participant interactions */
    .participant-clickable {
        cursor: pointer;
        user-select: none;
    }

    .participant-clickable:active {
        transform: scale(0.98);
    }

    /* Smooth focus transitions */
    .focus-transition {
        transition: all 300ms cubic-bezier(0.4, 0, 0.2, 1);
    }

    /* Focus area entrance animation */
    @keyframes focusAreaEnter {
        from {
            opacity: 0;
            transform: scale(0.9) translateY(-20px);
        }

        to {
            opacity: 1;
            transform: scale(1) translateY(0);
        }
    }

    .focus-area-enter {
        animation: focusAreaEnter 400ms cubic-bezier(0.4, 0, 0.2, 1) forwards;
    }

    /* Element movement transitions */
    .element-move-transition {
        transition: all 300ms cubic-bezier(0.4, 0, 0.2, 1);
    }

    /* Grid element styling */
    .grid-element {
        position: relative;
        border-radius: 8px;
        overflow: hidden;
        transition: all 300ms cubic-bezier(0.4, 0, 0.2, 1);
        aspect-ratio: 16/9;
    }

    /* Smooth element movement */
    .element-moving {
        transition: all 300ms cubic-bezier(0.4, 0, 0.2, 1);
    }

    /* Horizontal layout improvements */
    .horizontal-scroll-smooth {
        scroll-behavior: smooth;
        scrollbar-width: thin;
        scrollbar-color: rgba(156, 163, 175, 0.5) transparent;
    }

    .horizontal-scroll-smooth::-webkit-scrollbar {
        height: 6px;
    }

    .horizontal-scroll-smooth::-webkit-scrollbar-track {
        background: transparent;
    }

    .horizontal-scroll-smooth::-webkit-scrollbar-thumb {
        background-color: rgba(156, 163, 175, 0.5);
        border-radius: 3px;
    }

    .horizontal-scroll-smooth::-webkit-scrollbar-thumb:hover {
        background-color: rgba(156, 163, 175, 0.7);
    }

    /* Loading states */
    .focus-loading {
        position: relative;
        overflow: hidden;
    }

    .focus-loading::before {
        content: '';
        position: absolute;
        top: 0;
        left: -100%;
        width: 100%;
        height: 100%;
        background: linear-gradient(90deg, transparent, rgba(255, 255, 255, 0.1), transparent);
        animation: loadingShimmer 1.5s infinite;
    }

    @keyframes loadingShimmer {
        0% {
            left: -100%;
        }

        100% {
            left: 100%;
        }
    }

    /* ===== UNIFIED RESPONSIVE VIDEO GRID SYSTEM ===== */

    /* Meeting Interface - Dynamic height will be set by JavaScript */
    #livekitMeetingInterface {
        transition: all 300ms ease-in-out;
    }

    /* Fullscreen mode styling */
    #livekitMeetingInterface.fullscreen-mode {
        position: fixed !important;
        top: 0 !important;
        left: 0 !important;
        width: 100vw !important;
        height: 100vh !important;
        z-index: 9999 !important;
    }

    /* ===== MAIN VIDEO GRID LAYOUT ===== */

    /* Base Grid Configuration */
    #videoGrid {
        display: grid !important;
        gap: 1rem;
        padding: 1rem;
        width: 100%;
        height: 100%;
        place-items: center;
        align-content: center;
        justify-content: center;
        grid-auto-rows: minmax(180px, 1fr);
        max-width: 1600px;
        margin: 0 auto;
        overflow: hidden;
    }

    /* Grid Layout Rules Based on Participant Count */

    /* 1 Participant - Single centered video */
    #videoGrid[data-participants="1"] {
        grid-template-columns: 1fr;
        max-width: 800px;
    }

    #videoGrid[data-participants="1"] .participant-video {
        width: 100%;
        max-width: 700px;
        aspect-ratio: 16/9;
        min-height: 300px;
        max-height: 500px;
    }

    /* 2 Participants - Side by side */
    #videoGrid[data-participants="2"] {
        grid-template-columns: repeat(2, 1fr);
        max-width: 1200px;
    }

    #videoGrid[data-participants="2"] .participant-video {
        width: 100%;
        aspect-ratio: 16/9;
        min-height: 250px;
        max-height: 400px;
    }

    /* 3-4 Participants - 2x2 grid */
    #videoGrid[data-participants="3"],
    #videoGrid[data-participants="4"] {
        grid-template-columns: repeat(2, 1fr);
        grid-template-rows: repeat(2, 1fr);
        max-width: 1200px;
    }

    #videoGrid[data-participants="3"] .participant-video,
    #videoGrid[data-participants="4"] .participant-video {
        width: 100%;
        aspect-ratio: 16/9;
        min-height: 200px;
        max-height: 350px;
    }

    /* 5-6 Participants - 3x2 grid */
    #videoGrid[data-participants="5"],
    #videoGrid[data-participants="6"] {
        grid-template-columns: repeat(3, 1fr);
        grid-template-rows: repeat(2, 1fr);
        max-width: 1400px;
    }

    #videoGrid[data-participants="5"] .participant-video,
    #videoGrid[data-participants="6"] .participant-video {
        width: 100%;
        aspect-ratio: 16/9;
        min-height: 180px;
        max-height: 300px;
    }

    /* 7-9 Participants - 3x3 grid */
    #videoGrid[data-participants="7"],
    #videoGrid[data-participants="8"],
    #videoGrid[data-participants="9"] {
        grid-template-columns: repeat(3, 1fr);
        grid-template-rows: repeat(3, 1fr);
        max-width: 1400px;
    }

    #videoGrid[data-participants="7"] .participant-video,
    #videoGrid[data-participants="8"] .participant-video,
    #videoGrid[data-participants="9"] .participant-video {
        width: 100%;
        aspect-ratio: 16/9;
        min-height: 160px;
        max-height: 280px;
    }

    /* 10-12 Participants - 4x3 grid */
    #videoGrid[data-participants="10"],
    #videoGrid[data-participants="11"],
    #videoGrid[data-participants="12"] {
        grid-template-columns: repeat(4, 1fr);
        grid-template-rows: repeat(3, 1fr);
        max-width: 1600px;
    }

    #videoGrid[data-participants="10"] .participant-video,
    #videoGrid[data-participants="11"] .participant-video,
    #videoGrid[data-participants="12"] .participant-video {
        width: 100%;
        aspect-ratio: 16/9;
        min-height: 140px;
        max-height: 250px;
    }

    /* 13+ Participants - Auto-fit responsive grid */
    #videoGrid[data-participants^="1"]:not([data-participants="1"]):not([data-participants="10"]):not([data-participants="11"]):not([data-participants="12"]),
    #videoGrid[data-participants^="2"]:not([data-participants="2"]),
    #videoGrid[data-participants^="3"]:not([data-participants="3"]):not([data-participants="4"]):not([data-participants="5"]):not([data-participants="6"]):not([data-participants="7"]):not([data-participants="8"]):not([data-participants="9"]) {
        grid-template-columns: repeat(auto-fit, minmax(180px, 1fr));
        grid-auto-rows: minmax(140px, 180px);
        gap: 0.75rem;
    }

    /* Focus Layout State */
    .video-layout-focus #videoGrid {
        display: none;
    }

    .video-layout-focus #horizontalParticipants {
        display: flex !important;
    }

    /* Sidebar Open State Adjustments */
    .video-layout-sidebar-open #videoGrid {
        gap: 0.75rem;
        padding: 0.75rem;
        max-width: calc(100vw - 400px);
    }

    .video-layout-sidebar-open #videoGrid[data-participants="1"] {
        max-width: 600px;
    }

    .video-layout-sidebar-open #videoGrid[data-participants="2"] {
        max-width: 900px;
    }

    .video-layout-sidebar-open #videoGrid[data-participants="3"],
    .video-layout-sidebar-open #videoGrid[data-participants="4"] {
        max-width: 1000px;
    }

    /* ===== FOCUS AREA STYLING REMOVED (focusArea deprecated) ===== */

    /* ===== HORIZONTAL PARTICIPANTS (FOCUS MODE) ===== */

    #horizontalParticipants {
        height: 120px;
        background: rgb(31, 41, 55);
        border-radius: 0.5rem;
        overflow-x: auto;
        overflow-y: hidden;
        gap: 0.75rem;
        padding: 0.75rem;
        scroll-behavior: smooth;
    }

    .horizontal-participant {
        flex-shrink: 0;
        width: 200px;
        height: 90px;
        aspect-ratio: 16/9;
        border-radius: 0.5rem;
        overflow: hidden;
        cursor: pointer;
        transition: all 200ms ease-in-out;
    }

    .horizontal-participant:hover {
        transform: scale(1.05);
        box-shadow: 0 4px 12px rgba(0, 0, 0, 0.3);
    }



    /* ===== HORIZONTAL PARTICIPANTS RESPONSIVE ADJUSTMENTS ===== */

    @media (max-width: 768px) {
        .horizontal-participant {
            width: 160px;
            height: 90px;
        }
    }

    /* ===== ANIMATIONS & TRANSITIONS ===== */

    .video-layout-transition {
        transition: all 300ms cubic-bezier(0.4, 0, 0.2, 1);
    }

    .focus-enter-animation {
        animation: focusEnter 400ms cubic-bezier(0.4, 0, 0.2, 1);
    }

    @keyframes focusEnter {
        from {
            opacity: 0;
            transform: scale(0.9) translateY(-10px);
        }

        to {
            opacity: 1;
            transform: scale(1) translateY(0);
        }
    }

    /* ===== SCROLLBAR STYLING ===== */

    #horizontalParticipants::-webkit-scrollbar {
        height: 6px;
    }

    #horizontalParticipants::-webkit-scrollbar-track {
        background: transparent;
    }

    #horizontalParticipants::-webkit-scrollbar-thumb {
        background: rgba(156, 163, 175, 0.5);
        border-radius: 3px;
    }

    #horizontalParticipants::-webkit-scrollbar-thumb:hover {
        background: rgba(156, 163, 175, 0.7);
    }

    /* Focus area entrance animation */
    @keyframes focusAreaEnter {
        from {
            opacity: 0;
            transform: scale(0.9) translateY(-20px);
        }

        to {
            opacity: 1;
            transform: scale(1) translateY(0);
        }
    }

    .focus-area-enter {
        animation: focusAreaEnter 400ms cubic-bezier(0.4, 0, 0.2, 1) forwards;
    }

    /* Element movement transitions */
    .element-move-transition {
        transition: all 300ms cubic-bezier(0.4, 0, 0.2, 1);
    }

    /* Focus indicator with pulse animation */
    .focus-indicator {
        position: relative;
        box-shadow: 0 0 0 3px rgba(59, 130, 246, 0.5);
        animation: focusPulse 2s infinite;
    }

    @keyframes focusPulse {

        0%,
        100% {
            box-shadow: 0 0 0 3px rgba(59, 130, 246, 0.5);
        }

        50% {
            box-shadow: 0 0 0 6px rgba(59, 130, 246, 0.3);
        }
    }

    /* Clickable participant styling */
    .participant-clickable {
        cursor: pointer;
        user-select: none;
    }

    /* Focus transition effects */
    .focus-transition {
        transition: all 400ms cubic-bezier(0.4, 0, 0.2, 1);
    }





    /* Base Participant Video Styling */
    .participant-video {
        transition: all 0.3s ease;
        background: rgb(31, 41, 55);
        border: 1px solid rgb(55, 65, 81);
        border-radius: 0.5rem;
        overflow: hidden;
        cursor: pointer;
        position: relative;
        box-sizing: border-box;
    }

    .participant-video:hover {
        border-color: rgb(59, 130, 246);
        transform: translateY(-2px);
        box-shadow: 0 8px 20px rgba(0, 0, 0, 0.2);
    }

    .participant-video video {
        width: 100%;
        height: 100%;
        object-fit: cover;
    }

    /* ===== RESPONSIVE BREAKPOINTS ===== */

    /* Tablet Breakpoint (1024px and below) */
    @media (max-width: 1024px) {
        #videoGrid {
            gap: 0.75rem;
            padding: 0.75rem;
        }

        /* Adjust 5-6 participants to 2x3 on smaller screens */
        #videoGrid[data-participants="5"],
        #videoGrid[data-participants="6"] {
            grid-template-columns: repeat(2, 1fr);
            grid-template-rows: repeat(3, 1fr);
            max-width: 1000px;
        }

        /* Adjust 7-9 participants to 3x3 on smaller screens */
        #videoGrid[data-participants="7"],
        #videoGrid[data-participants="8"],
        #videoGrid[data-participants="9"] {
            grid-template-columns: repeat(3, 1fr);
            max-width: 1200px;
        }

        /* Adjust 10-12 participants to 3x4 on smaller screens */
        #videoGrid[data-participants="10"],
        #videoGrid[data-participants="11"],
        #videoGrid[data-participants="12"] {
            grid-template-columns: repeat(3, 1fr);
            grid-template-rows: repeat(4, 1fr);
            max-width: 1200px;
        }
    }

    /* Mobile Landscape and Small Tablet (768px and below) */
    @media (max-width: 768px) {
        #videoGrid {
            gap: 0.5rem;
            padding: 0.5rem;
        }

        /* 3-4 participants remain 2x2 */
        #videoGrid[data-participants="3"],
        #videoGrid[data-participants="4"] {
            grid-template-columns: repeat(2, 1fr);
            max-width: 100%;
        }

        /* 5-6 participants become 2x3 */
        #videoGrid[data-participants="5"],
        #videoGrid[data-participants="6"] {
            grid-template-columns: repeat(2, 1fr);
            grid-template-rows: repeat(3, 1fr);
            max-width: 100%;
        }

        /* 7+ participants use auto-fit grid */
        #videoGrid[data-participants="7"],
        #videoGrid[data-participants="8"],
        #videoGrid[data-participants="9"],
        #videoGrid[data-participants="10"],
        #videoGrid[data-participants="11"],
        #videoGrid[data-participants="12"] {
            grid-template-columns: repeat(auto-fit, minmax(150px, 1fr));
            grid-template-rows: auto;
            max-width: 100%;
        }

        /* Reduce min heights for mobile */
        .participant-video {
            min-height: 120px !important;
            max-height: 250px !important;
        }
    }

    /* Mobile Portrait (640px and below) */
    @media (max-width: 640px) {
        #videoGrid {
            gap: 0.25rem;
            padding: 0.25rem;
        }

        /* Single participant takes more space */
        #videoGrid[data-participants="1"] {
            max-width: 100%;
        }

        #videoGrid[data-participants="1"] .participant-video {
            max-width: 100%;
            min-height: 200px;
            max-height: 300px;
        }

        /* 2 participants become stacked on very small screens */
        #videoGrid[data-participants="2"] {
            grid-template-columns: 1fr;
            grid-template-rows: repeat(2, 1fr);
            max-width: 100%;
        }

        /* 3+ participants use 2 columns max */
        #videoGrid[data-participants="3"],
        #videoGrid[data-participants="4"],
        #videoGrid[data-participants="5"],
        #videoGrid[data-participants="6"],
        #videoGrid[data-participants="7"],
        #videoGrid[data-participants="8"],
        #videoGrid[data-participants="9"],
        #videoGrid[data-participants="10"],
        #videoGrid[data-participants="11"],
        #videoGrid[data-participants="12"] {
            grid-template-columns: repeat(2, 1fr);
            grid-template-rows: auto;
            max-width: 100%;
        }

        /* Further reduce heights for small screens */
        .participant-video {
            min-height: 100px !important;
            max-height: 180px !important;
        }
    }

    /* Fullscreen support */
    .meeting-fullscreen {
        position: fixed !important;
        top: 0 !important;
        left: 0 !important;
        right: 0 !important;
        bottom: 0 !important;
        z-index: 9999 !important;
        background: #111827 !important;
    }

    .meeting-fullscreen #videoGrid {
        grid-template-columns: repeat(auto-fit, minmax(250px, 1fr)) !important;
        padding: 2rem !important;
        gap: 1.5rem !important;
    }

    .meeting-fullscreen .participant-video {
        min-width: 250px !important;
        min-height: 180px !important;
    }

    /* Ensure meeting controls always appear above fullscreen content */
    .meeting-fullscreen #leaveMeeting,
    .meeting-fullscreen #fullscreenBtn,
    .meeting-fullscreen .meeting-control-button {
        z-index: 99999 !important;
        position: relative !important;
    }

    /* Ensure confirmation modals appear above fullscreen */
    #leaveConfirmModal {
        z-index: 99999 !important;
    }



    /* Focus loading state */
    .focus-loading {
        position: relative;
        overflow: hidden;
    }

    .focus-loading::before {
        content: '';
        position: absolute;
        top: 0;
        left: -100%;
        width: 100%;
        height: 100%;
        background: linear-gradient(90deg, transparent, rgba(255, 255, 255, 0.1), transparent);
        animation: loadingShimmer 1.5s infinite;
    }

    @keyframes loadingShimmer {
        0% {
            left: -100%;
        }

        100% {
            left: 100%;
        }
    }
</style>

<!-- LiveKit JavaScript SDK - SPECIFIC WORKING VERSION -->
<script>
    console.log('🔄 Loading LiveKit SDK...');

    function loadLiveKitScript() {
        return new Promise((resolve, reject) => {
            // Use official latest version from CDN
            const script = document.createElement('script');
            script.src = 'https://cdn.jsdelivr.net/npm/livekit-client/dist/livekit-client.umd.min.js';
            script.crossOrigin = 'anonymous';

            script.onload = () => {
                console.log('✅ LiveKit script loaded');
                // Check for various possible global names
                setTimeout(() => {
                    const possibleNames = ['LiveKit', 'LiveKitClient', 'LivekitClient', 'livekit'];
                    let livekitFound = null;

                    for (const name of possibleNames) {
                        if (typeof window[name] !== 'undefined') {
                            livekitFound = window[name];
                            window.LiveKit = livekitFound; // Normalize to LiveKit
                            console.log(`✅ LiveKit found as global: ${name}`);
                            break;
                        }
                    }

                    if (livekitFound) {
                        console.log('✅ LiveKit SDK available');
                        resolve();
                    } else {
                        console.error('❌ LiveKit global not found. Available globals:', Object.keys(window).filter(k => k.toLowerCase().includes('live')));
                        reject(new Error('LiveKit global not found'));
                    }
                }, 200);
            };

            script.onerror = (error) => {
                console.error('❌ Failed to load LiveKit script:', error);
                reject(new Error('Failed to load LiveKit script'));
            };

            document.head.appendChild(script);
        });
    }

    // Start loading LiveKit
    window.livekitLoadPromise = loadLiveKitScript();
</script>

<!-- Load LiveKit Classes in Correct Order -->
<script>
    console.log('🔄 Loading Modular LiveKit system...');

    // Track loading states
    let scriptsLoaded = {
        dataChannel: false,
        connection: false,
        tracks: false,
        participants: false,
        controls: false,
        layout: false,
        index: false
    };

    function checkAllScriptsLoaded() {
        const allLoaded = Object.values(scriptsLoaded).every(loaded => loaded);
        if (allLoaded) {
            console.log('✅ All LiveKit classes loaded, initializing system...');
            
            // Store session configuration
            window.sessionId = '{{ $session->id }}';
            window.auth = {
                user: {
                    id: '{{ auth()->id() }}',
                    name: '{{ auth()->user()->first_name }} {{ auth()->user()->last_name }}'
                }
            };

            console.log('✅ Session configuration set:', {
                sessionId: window.sessionId,
                user: window.auth.user
            });
            
            console.log('✅ Modular LiveKit system ready!');
        }
    }

    function loadScript(src, name) {
        return new Promise((resolve, reject) => {
            const script = document.createElement('script');
            script.src = src;
            script.onload = () => {
                console.log(`✅ ${name} loaded`);
                scriptsLoaded[name] = true;
                checkAllScriptsLoaded();
                resolve();
            };
            script.onerror = (error) => {
                console.error(`❌ Failed to load ${name}:`, error);
                reject(error);
            };
            document.head.appendChild(script);
        });
    }

    // Load scripts in order
    Promise.resolve()
        .then(() => loadScript('{{ asset("js/livekit/data-channel.js") }}?v={{ time() }}', 'dataChannel'))
        .then(() => loadScript('{{ asset("js/livekit/connection.js") }}?v={{ time() }}', 'connection'))
        .then(() => loadScript('{{ asset("js/livekit/tracks.js") }}?v={{ time() }}', 'tracks'))
        .then(() => loadScript('{{ asset("js/livekit/participants.js") }}?v={{ time() }}', 'participants'))
        .then(() => loadScript('{{ asset("js/livekit/controls.js") }}?v={{ time() }}', 'controls'))
        .then(() => loadScript('{{ asset("js/livekit/layout.js") }}?v={{ time() }}', 'layout'))
        .then(() => loadScript('{{ asset("js/livekit/index.js") }}?v={{ time() }}', 'index'))
        .catch(error => {
            console.error('❌ Failed to load LiveKit system:', error);
        });
</script>

<!-- Meeting Controls Card -->
<div class="bg-white rounded-lg shadow-md p-6 mb-6">
    <div class="flex items-center justify-between">
        <div>
            <h2 class="text-xl font-semibold text-gray-900 mb-2">
                @if($userType === 'quran_teacher')
                إدارة الاجتماع المباشر
                @else
                الانضمام للجلسة المباشرة
                @endif
            </h2>
            <p class="text-gray-600">
                @if($userType === 'quran_teacher')
                يمكنك بدء الاجتماع والتحكم في الجلسة
                @else
                انضم للجلسة المباشرة مع معلمك
                @endif
            </p>
        </div>
        <div class="flex flex-col items-end gap-2">
            <!-- Connection Status -->
            <div id="connectionStatus" class="connection-status hidden">
                <i class="fas fa-circle mr-1"></i>
                <span id="connectionText">غير متصل</span>
            </div>

            <!-- Join Meeting Button -->
            <button
                id="startMeetingBtn"
                class="bg-blue-600 hover:bg-blue-700 text-white px-6 py-3 rounded-lg font-semibold transition-colors duration-200 flex items-center gap-2 min-w-[200px] justify-center">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                        d="M15 10l4.553-2.276A1 1 0 0121 8.618v6.764a1 1 0 01-1.447.894L15 14M5 18h8a2 2 0 002-2V8a2 2 0 00-2-2H5a2 2 0 00-2 2v8a2 2 0 002 2z">
                    </path>
                </svg>
                <span id="meetingBtnText">
                    @if($userType === 'quran_teacher')
                    بدء الجلسة
                    @else
                    انضم للجلسة
                    @endif
                </span>
            </button>

            <!-- Meeting Info -->
            @if($session->meeting_room_name)
            <div class="text-sm text-gray-500 text-right">
                <div>رقم الغرفة: <code class="bg-gray-100 px-2 py-1 rounded text-xs">{{ $session->meeting_room_name }}</code></div>
                @if($session->scheduled_at)
                <div class="mt-1">الموعد: {{ $session->scheduled_at->format('H:i') }}</div>
                @endif
            </div>
            @endif
        </div>
    </div>
</div>

<!-- Meeting Container -->
<div id="meetingContainer" class="bg-white rounded-lg shadow-md overflow-hidden mb-8" style="display: none;">
    <!-- LiveKit Meeting Interface - Dynamic Height -->
    <div id="livekitMeetingInterface" class="bg-gray-900 relative overflow-hidden" style="min-height: 400px;">
        <!-- Loading Overlay - ENHANCED WITH SMOOTH TRANSITIONS -->
        <div id="loadingOverlay" class="absolute inset-0 bg-black bg-opacity-75 flex items-center justify-center z-50">
            <div class="text-center text-white">
                <div class="animate-spin rounded-full h-16 w-16 border-b-2 border-blue-500 mx-auto mb-4"></div>
                <p class="text-xl font-medium">جاري الاتصال بالاجتماع...</p>
                <p class="text-sm text-gray-300 mt-2">يرجى الانتظار قليلاً...</p>
            </div>
        </div>

        <!-- Meeting Interface - ENHANCED WITH SMOOTH FADE-IN -->
        <div id="meetingInterface" class="h-full flex flex-col bg-gray-900 text-white" style="min-height: 700px;">
            <!-- Meeting Header - With fullscreen button -->
            <div class="bg-gradient-to-r from-blue-500 via-blue-600 to-blue-700 text-white px-4 py-3 flex items-center justify-between text-sm font-medium shadow-lg">
                <!-- Left side - Meeting info -->
                <div class="flex items-center gap-4 sm:gap-8">
                    <!-- Participant Count -->
                    <div class="flex items-center gap-2 text-white">
                        <svg class="w-5 h-5 text-white" fill="currentColor" viewBox="0 0 20 20">
                            <path d="M13 6a3 3 0 11-6 0 3 3 0 016 0zM18 8a2 2 0 11-4 0 2 2 0 014 0zM14 15a4 4 0 00-8 0v3h8v-3z" />
                        </svg>
                        <span id="participantCount" class="text-white font-semibold">0</span>
                        <span class="text-white">مشارك</span>
                    </div>

                    <!-- Meeting Timer -->
                    <div class="flex items-center gap-2 text-white font-mono">
                        <div class="w-2 h-2 bg-red-500 rounded-full animate-pulse"></div>
                        <span id="meetingTimer" class="text-white font-bold">00:00</span>
                    </div>
                </div>

                <!-- Right side - Fullscreen button -->
                <button id="fullscreenBtn" class="bg-black bg-opacity-20 hover:bg-opacity-30 text-white px-3 py-2 rounded-lg transition-all duration-200 flex items-center gap-2 text-sm font-medium hover:scale-105 focus:outline-none focus:ring-2 focus:ring-white focus:ring-opacity-50 z-1 relative">
                    <svg id="fullscreenIcon" class="w-4 h-4 text-white" fill="currentColor" viewBox="0 0 20 20">
                        <path fill-rule="evenodd" d="M3 4a1 1 0 011-1h4a1 1 0 010 2H6.414l2.293 2.293a1 1 0 01-1.414 1.414L5 6.414V8a1 1 0 01-2 0V4zm9 1a1 1 0 010-2h4a1 1 0 011 1v4a1 1 0 01-1 1H4a1 1 0 01-1-1V4zM3 10a1 1 0 012 0v1.586l2.293-2.293a1 1 0 111.414 1.414L6.414 15H8a1 1 0 010 2H4a1 1 0 01-1-1v-4zm13-1a1 1 0 011 1v4a1 1 0 01-1 1h-4a1 1 0 010-2h1.586l-2.293-2.293a1 1 0 11-1.414-1.414L15 13.586V12a1 1 0 011-1z" clip-rule="evenodd" />
                    </svg>
                    <span id="fullscreenText" class="hidden sm:inline">ملء الشاشة</span>
                </button>
            </div>

            <!-- Main Content Area with Sidebar -->
            <div class="flex-1 grid grid-cols-1 min-h-0 overflow-hidden relative" style="overflow: hidden;">
                <!-- Video Area -->
                <div id="videoArea" class="video-area bg-gray-900 relative">

                    <!-- Video Grid -->
                    <div id="videoGrid" class="video-grid grid-1">
                        <!-- Participants will be added here dynamically -->
                    </div>

                    <!-- Focus Mode Overlay -->
                    <div id="focusOverlay" class="focus-overlay hidden">
                        <!-- Focused Video Container -->
                        <div id="focusedVideoContainer" class="focused-video-container">
                            <!-- Focused video will be moved here -->
                        </div>
                    </div>
                </div>

                <!-- Sidebar -->
                <div id="meetingSidebar" class="absolute top-0 right-0 bottom-0 w-96 bg-gray-800 border-l border-gray-700 flex flex-col transform translate-x-full transition-transform duration-300 ease-in-out z-40">
                    <!-- Sidebar Header -->
                    <div class="bg-gray-700 px-4 py-3 flex items-center justify-between border-b border-gray-600">
                        <h3 id="sidebarTitle" class="text-white font-semibold">الدردشة</h3>
                        <button id="closeSidebarBtn" class="text-gray-300 hover:text-white transition-colors">
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                            </svg>
                        </button>
                    </div>

                    <!-- Sidebar Content -->
                    <div class="flex-1 overflow-hidden">
                        <!-- Chat Panel -->
                        <div id="chatContent" class="h-full flex flex-col">
                            <!-- Chat Messages -->
                            <div id="chatMessages" class="flex-1 overflow-y-auto p-4 space-y-3">
                                <!-- Messages will be added here dynamically -->
                            </div>

                            <!-- Chat Input -->
                            <div class="p-4 border-t border-gray-600">
                                <div class="flex gap-2">
                                    <input
                                        type="text"
                                        id="chatMessageInput"
                                        placeholder="اكتب رسالة..."
                                        class="flex-1 bg-gray-700 text-white rounded-lg px-3 py-2 focus:outline-none focus:ring-2 focus:ring-blue-500"
                                        onkeypress="if(event.key==='Enter') window.meeting?.controls?.sendChatMessage()">
                                    <button
                                        id="sendChatBtn"
                                        class="bg-blue-600 hover:bg-blue-700 text-white px-4 py-2 rounded-lg transition-colors"
                                        onclick="window.meeting?.controls?.sendChatMessage()">
                                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 19l9 2-9-18-9 18 9-2zm0 0v-8"></path>
                                        </svg>
                                    </button>
                                </div>
                            </div>
                        </div>

                        <!-- Participants Panel -->
                        <div id="participantsContent" class="h-full flex-col hidden">
                            <div class="flex-1 overflow-y-auto p-4 space-y-2">
                                <div id="participantsList">
                                    <!-- Participants will be added here dynamically -->
                                </div>
                            </div>
                        </div>

                        <!-- Raised Hands Panel (Teachers Only) -->
                        @if($userType === 'quran_teacher')
                        <div id="raisedHandsContent" class="h-full flex-col hidden">
                            <!-- Global Audio Controls -->
                            <div class="p-4 border-b border-gray-600">
                                <h4 class="text-white font-medium mb-3">إدارة الصوت</h4>
                                <div class="flex items-center justify-between py-2">
                                    <span class="text-white text-sm font-medium">السماح للطلاب بإستخدام الميكروفون</span>
                                    <label class="relative inline-flex items-center cursor-pointer">
                                        <input type="checkbox" id="toggleAllStudentsMicSwitch" class="sr-only peer" checked>
                                        <div class="w-11 h-6 bg-gray-500 peer-focus:outline-none peer-focus:ring-4 peer-focus:ring-blue-300 rounded-full peer peer-checked:after:translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-[2px] after:left-[2px] after:bg-white after:rounded-full after:h-5 after:w-5 after:transition-all peer-checked:bg-green-600"></div>
                                    </label>
                                </div>
                            </div>

                            <!-- Raised Hands Queue -->
                            <div class="flex-1 overflow-y-auto p-4">
                                <div class="flex items-center justify-between mb-4">
                                    <h4 class="text-white font-medium">الأيدي المرفوعة</h4>
                                    <div class="flex items-center gap-2">
                                        <span id="raisedHandsCount" class="bg-orange-500 text-white text-xs px-2 py-1 rounded-full">0</span>
                                        <button id="clearAllRaisedHandsBtn" 
                                                onclick="window.meeting?.controls?.clearAllRaisedHands()" 
                                                class="bg-red-600 hover:bg-red-700 text-white text-xs px-3 py-1 rounded transition-colors hidden"
                                                title="إخفاء جميع الأيدي المرفوعة">
                                            ✋ إخفاء الكل
                                        </button>
                                    </div>
                                </div>

                                <div id="raisedHandsList" class="space-y-3">
                                    <!-- Empty state -->
                                    <div id="noRaisedHandsMessage" class="text-center text-gray-400 py-8">
                                        <svg class="w-12 h-12 mx-auto mb-4 text-gray-500" fill="currentColor" viewBox="0 0 20 20">
                                            <path d="M10 12a2 2 0 100-4 2 2 0 000 4z" />
                                            <path fill-rule="evenodd" d="M.458 10C1.732 5.943 5.522 3 10 3s8.268 2.943 9.542 7c-1.274 4.057-5.064 7-9.542 7S1.732 14.057.458 10zM14 10a4 4 0 11-8 0 4 4 0 018 0z" clip-rule="evenodd" />
                                        </svg>
                                        <p>لا يوجد طلاب رفعوا أيديهم</p>
                                    </div>
                                    <!-- Raised hands will be added here dynamically -->
                                </div>
                            </div>
                        </div>
                        @endif

                        <!-- Settings Panel -->
                        <div id="settingsContent" class="h-full flex-col hidden">
                            <div class="flex-1 overflow-y-auto p-4 space-y-4">
                                <!-- Camera Settings -->
                                <div class="bg-gray-700 rounded-lg p-4">
                                    <h4 class="text-white font-medium mb-3">إعدادات الكاميرا</h4>
                                    <div class="space-y-2">
                                        <div>
                                            <label class="text-gray-300 text-sm">الكاميرا</label>
                                            <select id="cameraSelect" class="w-full mt-1 bg-gray-600 text-white rounded px-3 py-2 focus:outline-none focus:ring-2 focus:ring-blue-500">
                                                <option>جاري التحميل...</option>
                                            </select>
                                        </div>
                                        <div>
                                            <label class="text-gray-300 text-sm">الجودة</label>
                                            <select id="videoQualitySelect" class="w-full mt-1 bg-gray-600 text-white rounded px-3 py-2 focus:outline-none focus:ring-2 focus:ring-blue-500">
                                                <option value="low">منخفضة (480p)</option>
                                                <option value="medium" selected>متوسطة (720p)</option>
                                                <option value="high">عالية (1080p)</option>
                                            </select>
                                        </div>
                                    </div>
                                </div>

                                <!-- Microphone Settings -->
                                <div class="bg-gray-700 rounded-lg p-4">
                                    <h4 class="text-white font-medium mb-3">إعدادات الميكروفون</h4>
                                    <div class="space-y-2">
                                        <div>
                                            <label class="text-gray-300 text-sm">الميكروفون</label>
                                            <select id="microphoneSelect" class="w-full mt-1 bg-gray-600 text-white rounded px-3 py-2 focus:outline-none focus:ring-2 focus:ring-blue-500">
                                                <option>جاري التحميل...</option>
                                            </select>
                                        </div>
                                        <div class="flex items-center justify-between">
                                            <span class="text-gray-300 text-sm">كتم الصوت عند الدخول</span>
                                            <input type="checkbox" id="muteonJoinCheckbox" class="rounded">
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Control Bar - Always at bottom -->
            <div class="control-bar bottom-0 left-0 right-0 bg-gray-800 border-t border-gray-700 px-4 py-4 flex items-center justify-center gap-2 sm:gap-4 shadow-lg flex-wrap sm:flex-nowrap z-50">
                <!-- Microphone Button -->
                <button id="toggleMic" class="w-12 h-12 sm:w-14 sm:h-14 rounded-full bg-gray-600 hover:bg-gray-500 text-white flex items-center justify-center transition-all duration-200 hover:scale-105 focus:outline-none focus:ring-2 focus:ring-blue-500 active:scale-95" title="إيقاف/تشغيل الميكروفون">
                    <svg class="w-6 h-6 text-white" fill="currentColor" viewBox="0 0 20 20">
                        <path fill-rule="evenodd" d="M7 4a3 3 0 016 0v4a3 3 0 11-6 0V4zm4 10.93A7.001 7.001 0 0017 8a1 1 0 10-2 0A5 5 0 015 8a1 1 0 00-1 0 7.001 7.001 0 006 6.93V17H6a1 1 0 100 2h8a1 1 0 100-2h-3v-2.07z" clip-rule="evenodd" />
                    </svg>
                </button>

                <!-- Camera Button -->
                <button id="toggleCamera" class="w-12 h-12 sm:w-14 sm:h-14 rounded-full bg-gray-600 hover:bg-gray-500 text-white flex items-center justify-center transition-all duration-200 hover:scale-105 focus:outline-none focus:ring-2 focus:ring-blue-500 active:scale-95" title="إيقاف/تشغيل الكاميرا">
                    <svg class="w-6 h-6 text-white" fill="currentColor" viewBox="0 0 20 20">
                        <path fill-rule="evenodd" d="M4 3a2 2 0 00-2 2v10a2 2 0 002 2h12a2 2 0 002-2V5a2 2 0 00-2-2H4zm12 12H4l4-8v3l2 2 2-2v-3l4 8z" clip-rule="evenodd" />
                    </svg>
                </button>

                <!-- Screen Share Button -->
                <button id="toggleScreenShare" class="w-12 h-12 sm:w-14 sm:h-14 rounded-full bg-gray-600 hover:bg-gray-500 text-white flex items-center justify-center transition-all duration-200 hover:scale-105 focus:outline-none focus:ring-2 focus:ring-blue-500 active:scale-95" title="مشاركة الشاشة">
                    <svg class="w-6 h-6 text-white" fill="currentColor" viewBox="0 0 20 20">
                        <path d="M3 4a1 1 0 011-1h12a1 1 0 011 1v2a1 1 0 01-1 1H4a1 1 0 00-1-1V4zM3 10a1 1 0 011-1h6a1 1 0 011 1v6a1 1 0 01-1 1H4a1 1 0 01-1-1v-6zM14 9a1 1 0 00-1 1v6a1 1 0 001 1h2a1 1 0 001-1v-6a1 1 0 00-1-1h-2z" />
                    </svg>
                </button>

                @if($userType !== 'quran_teacher')
                <!-- Hand Raise Button -->
                <button id="toggleHandRaise" class="w-12 h-12 sm:w-14 sm:h-14 rounded-full bg-gray-600 hover:bg-orange-500 text-white flex items-center justify-center transition-all duration-200 hover:scale-105 focus:outline-none focus:ring-2 focus:ring-orange-500 active:scale-95" title="رفع اليد">
                    <i class="fa-solid fa-hand text-white text-xl"></i>
                </button>
                @endif

                <!-- Chat Button -->
                <button id="toggleChat" class="w-12 h-12 sm:w-14 sm:h-14 rounded-full bg-gray-600 hover:bg-gray-500 text-white flex items-center justify-center transition-all duration-200 hover:scale-105 focus:outline-none focus:ring-2 focus:ring-blue-500 active:scale-95" title="إظهار/إخفاء الدردشة">
                    <svg class="w-6 h-6 text-white" fill="currentColor" viewBox="0 0 20 20">
                        <path fill-rule="evenodd" d="M18 10c0 3.866-3.582 7-8 7a8.841 8.841 0 01-4.083-.98L2 17l1.338-3.123C2.493 12.767 2 11.434 2 10c0-3.866 3.582-7 8-7s8 3.134 8 7zM7 9H5v2h2V9zm8 0h-2v2h2V9zM9 9h2v2H9V9z" clip-rule="evenodd" />
                    </svg>
                </button>

                <!-- Participants Button -->
                <button id="toggleParticipants" class="w-12 h-12 sm:w-14 sm:h-14 rounded-full bg-gray-600 hover:bg-gray-500 text-white flex items-center justify-center transition-all duration-200 hover:scale-105 focus:outline-none focus:ring-2 focus:ring-blue-500 active:scale-95" title="إظهار/إخفاء المشاركين">
                    <svg class="w-6 h-6 text-white" fill="currentColor" viewBox="0 0 20 20">
                        <path d="M13 6a3 3 0 11-6 0 3 3 0 016 0zM18 8a2 2 0 11-4 0 2 2 0 014 0zM14 15a4 4 0 00-8 0v3h8v-3z" />
                    </svg>
                </button>

                @if($userType === 'quran_teacher')
                <!-- Raised Hands Button (Teachers Only) -->
                <button id="toggleRaisedHands" class="w-12 h-12 sm:w-14 sm:h-14 rounded-full bg-gray-600 hover:bg-orange-500 text-white flex items-center justify-center transition-all duration-200 hover:scale-105 focus:outline-none focus:ring-2 focus:ring-orange-500 active:scale-95 relative" title="إدارة الأيدي المرفوعة">
                    <i class="fa-solid fa-hand text-white text-xl"></i>
                    <!-- Notification Badge -->
                    <div id="raisedHandsNotificationBadge" class="absolute -top-1 -right-1 w-5 h-5 bg-red-500 text-white text-xs rounded-full flex items-center justify-center font-bold hidden">
                        <span id="raisedHandsBadgeCount">0</span>
                    </div>
                </button>
                @endif

                @if($userType === 'quran_teacher')
                <!-- Recording Button -->
                <button id="toggleRecording" class="w-12 h-12 sm:w-14 sm:h-14 rounded-full bg-gray-600 hover:bg-red-500 text-white flex items-center justify-center transition-all duration-200 hover:scale-105 focus:outline-none focus:ring-2 focus:ring-red-500 active:scale-95" title="بدء/إيقاف التسجيل">
                    <svg class="w-6 h-6 text-white" fill="currentColor" viewBox="0 0 20 20">
                        <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zM9.555 7.168A1 1 0 008 8v4a1 1 0 001.555.832l3-2a1 1 0 000-1.664l-3-2z" clip-rule="evenodd" />
                    </svg>
                </button>
                @endif

                <!-- Settings Button -->
                <button id="toggleSettings" class="w-12 h-12 sm:w-14 sm:h-14 rounded-full bg-gray-600 hover:bg-gray-500 text-white flex items-center justify-center transition-all duration-200 hover:scale-105 focus:outline-none focus:ring-2 focus:ring-blue-500 active:scale-95" title="الإعدادات">
                    <svg class="w-6 h-6 text-white" fill="currentColor" viewBox="0 0 20 20">
                        <path fill-rule="evenodd" d="M11.49 3.17c-.38-1.56-2.6-1.56-2.98 0a1.532 1.532 0 01-2.286.948c-1.372-.836-2.942.734-2.106 2.106.54.886.061 2.042-.947 2.287-1.372-.836-2.942.734-2.106 2.106a1.532 1.532 0 010 2.287c-.836 1.372.734 2.942 2.106 2.106a1.532 1.532 0 012.287.947c.379 1.561 2.6 1.561 2.978 0a1.533 1.533 0 012.287-.947c-.836 1.372.734 2.942 2.106 2.106a1.533 1.533 0 01.947-2.287c1.561-.379 1.561-2.6 0-2.978a1.532 1.532 0 01-.947-2.287c.836-1.372-.734-2.942-2.106-2.106a1.532 1.532 0 01-2.287-.947zM10 13a3 3 0 100-6 3 3 0 000 6z" clip-rule="evenodd" />
                    </svg>
                </button>

                <!-- Leave Button -->
                <button id="leaveMeeting" class="w-12 h-12 sm:w-14 sm:h-14 rounded-full bg-red-600 hover:bg-red-700 text-white flex items-center justify-center transition-all duration-200 hover:scale-105 focus:outline-none focus:ring-2 focus:ring-red-500 active:scale-95 z-[99999] relative meeting-control-button" title="مغادرة الجلسة">
                    <svg class="w-6 h-6 text-white" fill="currentColor" viewBox="0 0 20 20">
                        <path fill-rule="evenodd" d="M3 3a1 1 0 00-1 1v12a1 1 0 102 0V4a1 1 0 00-1-1zm10.293 9.293a1 1 0 001.414 1.414l3-3a1 1 0 000-1.414l-3-3a1 1 0 10-1.414 1.414L14.586 9H7a1 1 0 100 2h7.586l-1.293 1.293z" clip-rule="evenodd" />
                    </svg>
                </button>
            </div>
        </div>
    </div>
</div>

<!-- Meeting Initialization Script -->
<script>
    console.log('✅ LiveKit Meeting Component Loading...');

    // Initialize modular meeting system
    async function initializeMeeting() {
        console.log('🚀 Initializing modular meeting...');

        try {
            // Wait for LiveKit SDK to load
            console.log('⏳ Waiting for LiveKit SDK...');
            if (window.livekitLoadPromise) {
                await window.livekitLoadPromise;
            }

            // Double-check LiveKit is available
            if (typeof LiveKit === 'undefined' && typeof window.LiveKit === 'undefined') {
                throw new Error('LiveKit SDK not available after loading');
            }

            // Meeting configuration for modular system
            const meetingConfig = {
                serverUrl: '{{ config("livekit.server_url") }}',
                csrfToken: '{{ csrf_token() }}',
                roomName: '{{ $session->meeting_room_name ?? "session-" . $session->id }}',
                participantName: '{{ auth()->user()->first_name }} {{ auth()->user()->last_name }}',
                role: '{{ $userType === "quran_teacher" ? "teacher" : "student" }}'
            };

            console.log('✅ Modular meeting configuration:', meetingConfig);

            // Set up start button handler
            const startBtn = document.getElementById('startMeetingBtn');
            if (startBtn) {
                console.log('✅ Meeting button found and ready');

                // Add click handler for modular system
                startBtn.addEventListener('click', async () => {
                    console.log('🎯 Start button clicked!');

                    // Check if already initializing or initialized
                    if (window.meeting || startBtn.disabled) {
                        console.log('⚠️ Meeting already initialized or initializing, ignoring click');
                        return;
                    }

                    try {
                        // Show loading state
                        startBtn.disabled = true;
                        const btnText = document.getElementById('meetingBtnText');
                        const originalText = btnText?.textContent;
                        
                        if (btnText) {
                            btnText.textContent = window.isAutoJoining ? 'انضمام تلقائي...' : 'جاري الاتصال...';
                        }

                        // Show meeting container
                        const meetingContainer = document.getElementById('meetingContainer');
                        if (meetingContainer) {
                            meetingContainer.style.display = 'block';
                            console.log('✅ Meeting container shown');
                        } else {
                            console.error('❌ Meeting container not found');
                        }

                        // Initialize meeting with new modular system
                        console.log('🚀 Starting modular meeting...');
                        window.meeting = await initializeLiveKitMeeting(meetingConfig);

                        console.log('✅ Modular meeting initialized successfully');

                        // Update button text
                        if (btnText) btnText.textContent = 'متصل';

                    } catch (error) {
                        console.error('❌ Failed to start meeting:', error);

                        // Reset button state
                        startBtn.disabled = false;
                        const btnText = document.getElementById('meetingBtnText');
                        if (btnText) {
                            if (window.isAutoJoining) {
                                btnText.textContent = 'فشل الانضمام التلقائي - اضغط للمحاولة';
                            } else {
                                btnText.textContent = 'إعادة المحاولة';
                            }
                        }

                        // Hide meeting container on error
                        const meetingContainer = document.getElementById('meetingContainer');
                        if (meetingContainer) {
                            meetingContainer.style.display = 'none';
                        }

                        // Show user-friendly error
                        const errorMessage = error?.message || 'حدث خطأ غير متوقع';
                        alert(`فشل في الاتصال بالجلسة: ${errorMessage}`);
                    }
                });

                console.log('✅ Modular click handler added to start button');
            } else {
                console.error('❌ Meeting button not found');
            }

            console.log('🎉 Modular meeting system ready!');

        } catch (error) {
            console.error('❌ Meeting initialization failed:', error);
            const btn = document.getElementById('startMeetingBtn');
            const btnText = document.getElementById('meetingBtnText');
            if (btn) btn.disabled = true;

            const errorMessage = error?.message || error?.toString() || 'Unknown error';
            if (btnText) {
                btnText.textContent = errorMessage.toLowerCase().includes('livekit') ? 'LiveKit غير متوفر' : 'خطأ في التهيئة';
            }
        }
    }

    // Wait for window load, then initialize
    window.addEventListener('load', function() {
        console.log('🚀 All resources loaded, starting initialization...');
        initializeMeeting();
    });

    // Fallback initialization on DOM ready
    document.addEventListener('DOMContentLoaded', function() {
        console.log('🎯 DOM ready - checking modular system...');

        // Ensure initializeLiveKitMeeting is available
        if (typeof window.initializeLiveKitMeeting !== 'function') {
            console.warn('⚠️ Modular system not yet loaded, will rely on window.load event');
            return;
        }

        console.log('✅ Modular system available on DOM ready');
    });

    // Cleanup on page unload
    window.addEventListener('beforeunload', async () => {
        if (window.meeting && typeof window.meeting.destroy === 'function') {
            console.log('🧹 Cleaning up meeting on page unload...');
            try {
                await window.meeting.destroy();
            } catch (error) {
                console.error('❌ Error during cleanup:', error);
            }
        } else if (window.destroyCurrentMeeting) {
            // Fallback cleanup
            try {
                await window.destroyCurrentMeeting();
            } catch (error) {
                console.error('❌ Error during fallback cleanup:', error);
            }
        }
    });

    // Add debug panel toggle
    document.addEventListener('DOMContentLoaded', function() {
        // Create debug panel
        const debugPanel = document.createElement('div');
        debugPanel.id = 'debugPanel';
        debugPanel.className = 'fixed top-4 right-4 bg-gray-800 bg-opacity-90 text-white p-4 rounded-lg shadow-lg z-50 hidden';
        debugPanel.innerHTML = `
            <h3 class="text-lg font-bold mb-3">Debug Panel</h3>
            <div class="space-y-2">
                <button onclick="window.debugMeeting()" class="w-full bg-blue-600 hover:bg-blue-700 px-3 py-2 rounded text-sm">
                    Debug Meeting
                </button>
                <button onclick="window.debugVideos()" class="w-full bg-green-600 hover:bg-green-700 px-3 py-2 rounded text-sm">
                    Debug Videos
                </button>
                <button onclick="window.debugPlaceholders()" class="w-full bg-yellow-600 hover:bg-yellow-700 px-3 py-2 rounded text-sm">
                    Debug Placeholders
                </button>
                <button onclick="window.testOverlay()" class="w-full bg-purple-600 hover:bg-purple-700 px-3 py-2 rounded text-sm">
                    Test Overlay
                </button>
                <button onclick="window.forceShowOverlays()" class="w-full bg-indigo-600 hover:bg-indigo-700 px-3 py-2 rounded text-sm">
                    Force Show Overlays
                </button>
                <button onclick="window.forceUpdateVideoDisplay()" class="w-full bg-pink-600 hover:bg-pink-700 px-3 py-2 rounded text-sm">
                    Force Update Video
                </button>
                <button onclick="window.checkOverlays()" class="w-full bg-red-600 hover:bg-red-700 px-3 py-2 rounded text-sm">
                    Check Overlays
                </button>
                <button onclick="window.testNameCleaning()" class="w-full bg-teal-600 hover:bg-teal-700 px-3 py-2 rounded text-sm">
                    Test Name Cleaning
                </button>
                <button onclick="window.testHandRaiseIndicators()" class="w-full bg-orange-600 hover:bg-orange-700 px-3 py-2 rounded text-sm">
                    Test Hand Raise
                </button>
                <button onclick="window.testHandRaiseDirectly('local')" class="w-full bg-orange-500 hover:bg-orange-600 px-3 py-2 rounded text-sm">
                    Test Direct Hand Raise
                </button>
                <button onclick="window.forceCreateHandRaiseIndicator('local')" class="w-full bg-orange-400 hover:bg-orange-500 px-3 py-2 rounded text-sm">
                    Force Create Indicator
                </button>
                <button onclick="window.testControlsHandRaise('local', true)" class="w-full bg-orange-300 hover:bg-orange-400 px-3 py-2 rounded text-sm">
                    Test Controls Hand Raise
                </button>
            </div>
        `;

        // Create debug toggle button
        const debugToggle = document.createElement('button');
        debugToggle.id = 'debugToggle';
        debugToggle.className = 'fixed top-4 right-4 bg-gray-600 hover:bg-gray-700 text-white w-10 h-10 rounded-full flex items-center justify-center z-40 transition-all duration-200';
        debugToggle.innerHTML = '<i class="fas fa-bug text-sm"></i>';
        debugToggle.title = 'Toggle Debug Panel';
        debugToggle.onclick = function() {
            const panel = document.getElementById('debugPanel');
            if (panel) {
                panel.classList.toggle('hidden');
            }
        };

        // Add to page
        document.body.appendChild(debugToggle);
        document.body.appendChild(debugPanel);

        console.log('🔍 Debug panel added to page');
    });
</script>

@if($userType === 'quran_teacher' && $session->scheduled_at && $session->scheduled_at->isToday())
<!-- Auto-join Script for Teachers -->
<script>
    // Auto-join for teachers if meeting is scheduled now
    document.addEventListener('DOMContentLoaded', function() {
        const now = new Date();
        const scheduledTime = new Date('{{ $session->scheduled_at->toISOString() }}');
        const timeDiff = scheduledTime - now;

        // Auto-join if within 5 minutes of scheduled time
        if (Math.abs(timeDiff) <= 5 * 60 * 1000) {
            console.log('🕐 Auto-joining meeting as it\'s scheduled time');
            
            // Set auto-join flag to prevent conflicts
            window.isAutoJoining = true;
            
            // Simulate button click after ensuring initialization is complete
            setTimeout(() => {
                const startBtn = document.getElementById('startMeetingBtn');
                if (startBtn && !window.meeting) {
                    console.log('🕐 Triggering auto-join via button click');
                    startBtn.click();
                    
                    // Update button text to indicate auto-join
                    const btnText = document.getElementById('meetingBtnText');
                    if (btnText) {
                        btnText.textContent = 'انضمام تلقائي...';
                    }
                } else if (window.meeting) {
                    console.log('🕐 Meeting already initialized, skipping auto-join');
                } else {
                    console.warn('⚠️ Start button not found for auto-join');
                }
                
                window.isAutoJoining = false;
            }, 3000); // Increased delay to ensure proper initialization
        }
    });
</script>
@endif