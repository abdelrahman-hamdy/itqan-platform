{{--
    LiveKit Meeting Interface Component
    Unified meeting interface for both teachers and students
    Based on official LiveKit JavaScript SDK
--}}

@props([
'session',
'userType' => 'student'
])

@php
    // Detect session type - check if it's an AcademicSession or QuranSession
    $isAcademicSession = $session instanceof \App\Models\AcademicSession;
    
    // Get configuration for meeting timing based on session type
    if ($isAcademicSession) {
        // Academic sessions have different configuration approach
        $preparationMinutes = 15; // Default for academic sessions
        $endingBufferMinutes = 5;
        $graceMinutes = 15;
        $circle = null; // Academic sessions don't have circles
    } else {
        // Quran sessions use circle configuration
        $circle = $session->session_type === 'individual' 
            ? $session->individualCircle 
            : $session->circle;
        
        $preparationMinutes = $circle?->preparation_minutes ?? 15;
        $endingBufferMinutes = $circle?->ending_buffer_minutes ?? 5;
        $graceMinutes = $circle?->late_join_grace_period_minutes ?? 15;
    }
    
    // CRITICAL FIX: Students should be able to join unless session is completed/cancelled
    $canJoinMeeting = in_array($session->status, [
        App\Enums\SessionStatus::READY,
        App\Enums\SessionStatus::ONGOING
    ]);
    
    // ADDITIONAL FIX: Allow students to join even if marked absent, as long as session is not completed
    if ($userType === 'student' && in_array($session->status, [
        App\Enums\SessionStatus::ABSENT,
        App\Enums\SessionStatus::SCHEDULED
    ])) {
        // Students can join during preparation time or if session hasn't ended
        $now = now();
        $preparationStart = $session->scheduled_at?->copy()->subMinutes($preparationMinutes);
        $sessionEnd = $session->scheduled_at?->copy()->addMinutes(($session->duration_minutes ?? 30) + $endingBufferMinutes);
        
        if ($now->gte($preparationStart) && $now->lt($sessionEnd)) {
            $canJoinMeeting = true;
        }
    }
    
    // Get status-specific messages
    $meetingMessage = '';
    $buttonText = '';
    $buttonClass = '';
    $buttonDisabled = false;
    
    switch($session->status) {
        case App\Enums\SessionStatus::READY:
            if ($session->meeting_room_name) {
                // Meeting room exists, both teachers and students can join
                $meetingMessage = $userType === 'quran_teacher' 
                    ? 'الجلسة جاهزة للبدء - يمكنك الآن بدء الاجتماع' 
                    : 'الجلسة جاهزة - يمكنك الانضمام الآن';
                $buttonText = $userType === 'quran_teacher' ? 'بدء الجلسة' : 'انضم للجلسة';
                $buttonClass = $userType === 'quran_teacher' ? 'bg-green-600 hover:bg-green-700' : 'bg-blue-600 hover:bg-blue-700';
                $buttonDisabled = false;
            } else {
                // No meeting room yet, only teachers can start
                $meetingMessage = $userType === 'quran_teacher' 
                    ? 'الجلسة جاهزة للبدء - يمكنك الآن بدء الاجتماع' 
                    : 'الجلسة جاهزة - في انتظار المعلم لبدء الاجتماع';
                $buttonText = $userType === 'quran_teacher' ? 'بدء الجلسة' : 'في انتظار المعلم';
                $buttonClass = $userType === 'quran_teacher' ? 'bg-green-600 hover:bg-green-700' : 'bg-gray-400 cursor-not-allowed';
                $buttonDisabled = $userType !== 'quran_teacher';
            }
            break;
            
        case App\Enums\SessionStatus::ONGOING:
            $meetingMessage = 'الجلسة جارية الآن - انضم للمشاركة';
            $buttonText = 'انضمام للجلسة الجارية';
            $buttonClass = 'bg-orange-600 hover:bg-orange-700 animate-pulse';
            break;
            
        case App\Enums\SessionStatus::SCHEDULED:
            if ($canJoinMeeting) {
                $meetingMessage = 'جاري تحضير الاجتماع - يمكنك الانضمام الآن';
                $buttonText = 'انضم للجلسة';
                $buttonClass = 'bg-blue-600 hover:bg-blue-700';
                $buttonDisabled = false;
            } else {
                if ($session->scheduled_at) {
                    $preparationTime = $session->scheduled_at->copy()->subMinutes($preparationMinutes);
                    $timeData = formatTimeRemaining($preparationTime);
                    if (!$timeData['is_past']) {
                        $meetingMessage = "سيتم تحضير الاجتماع خلال " . $timeData['formatted'] . " ({$preparationMinutes} دقيقة قبل الموعد)";
                    } else {
                        $meetingMessage = "جاري تحضير الاجتماع...";
                    }
                } else {
                    $meetingMessage = 'الجلسة مجدولة لكن لم يتم تحديد الوقت بعد';
                }
                $buttonText = 'في انتظار تحضير الاجتماع';
                $buttonClass = 'bg-gray-400 cursor-not-allowed';
                $buttonDisabled = true;
            }
            break;
            
        case App\Enums\SessionStatus::COMPLETED:
            $meetingMessage = 'تم إنهاء الجلسة بنجاح';
            $buttonText = 'الجلسة منتهية';
            $buttonClass = 'bg-gray-400 cursor-not-allowed';
            $buttonDisabled = true;
            break;
            
        case App\Enums\SessionStatus::CANCELLED:
            $meetingMessage = 'تم إلغاء الجلسة';
            $buttonText = 'الجلسة ملغية';
            $buttonClass = 'bg-red-400 cursor-not-allowed';
            $buttonDisabled = true;
            break;
            
        case App\Enums\SessionStatus::ABSENT:
            if ($canJoinMeeting) {
                $meetingMessage = 'تم تسجيل غيابك ولكن يمكنك الانضمام الآن';
                $buttonText = 'انضم للجلسة (غائب)';
                $buttonClass = 'bg-yellow-600 hover:bg-yellow-700';
                $buttonDisabled = false;
            } else {
                $meetingMessage = 'تم تسجيل غياب الطالب';
                $buttonText = 'غياب الطالب';
                $buttonClass = 'bg-red-400 cursor-not-allowed';
                $buttonDisabled = true;
            }
            break;
            
        default:
            // Handle case where status might be a string or enum
            $statusLabel = is_object($session->status) && method_exists($session->status, 'label')
                ? $session->status->label()
                : $session->status;
            $meetingMessage = 'حالة الجلسة: ' . $statusLabel;
            $buttonText = 'غير متاح';
            $buttonClass = 'bg-gray-400 cursor-not-allowed';
            $buttonDisabled = true;
    }
@endphp

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
    
    /* Meeting Timer Styles */
    .countdown-timer {
        min-height: 120px;
        transition: all 0.3s ease-in-out;
    }
    
    .countdown-timer.waiting {
        background: linear-gradient(135deg, #fff3cd, #fef3c7);
        border-color: #f59e0b;
        color: #92400e;
    }
    
    .countdown-timer.active {
        background: linear-gradient(135deg, #d1fae5, #a7f3d0);
        border-color: #059669;
        color: #065f46;
    }
    
    .countdown-timer.overtime {
        background: linear-gradient(135deg, #fee2e2, #fecaca);
        border-color: #dc2626;
        color: #991b1b;
    }
    
    .countdown-timer.offline {
        background: linear-gradient(135deg, #f3f4f6, #e5e7eb);
        border-color: #6b7280;
        color: #374151;
    }
    
    .timer-display {
        text-shadow: 0 1px 3px rgba(0, 0, 0, 0.1);
    }
    
    @keyframes pulse {
        0%, 100% { opacity: 1; }
        50% { opacity: 0.5; }
    }

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
        z-index: 22 !important;
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

    /* ===== CONTROL BUTTON HOVER FIXES ===== */
    /* Fix: Camera and mic buttons should keep red color when off, not turn grey on hover */
    #toggleMic.mic-off:hover,
    #toggleCamera.camera-off:hover {
        background-color: #dc2626 !important; /* Keep red color on hover when off */
        transform: scale(1.05);
    }

    #toggleMic.mic-off,
    #toggleCamera.camera-off {
        background-color: #dc2626; /* Red when off */
        color: white;
    }

    /* ===== TOOLTIP STYLES ===== */
    .control-tooltip {
        position: absolute;
        bottom: 120%;
        left: 50%;
        transform: translateX(-50%);
        background: rgba(0, 0, 0, 0.9);
        color: white;
        padding: 8px 12px;
        border-radius: 6px;
        font-size: 12px;
        white-space: nowrap;
        opacity: 0;
        visibility: hidden;
        transition: all 0.3s ease;
        z-index: 99999;
        pointer-events: none;
        animation: tooltipBounce 0.3s ease-out;
        box-shadow: 0 4px 12px rgba(0, 0, 0, 0.3);
    }

    .control-tooltip::after {
        content: '';
        position: absolute;
        top: 100%;
        left: 50%;
        transform: translateX(-50%);
        border: 5px solid transparent;
        border-top-color: rgba(0, 0, 0, 0.9);
    }

    .control-button:hover .control-tooltip {
        opacity: 1;
        visibility: visible;
        transform: translateX(-50%) translateY(-4px);
        animation: tooltipBounce 0.4s cubic-bezier(0.4, 0, 0.2, 1);
    }

    @keyframes tooltipBounce {
        0% {
            opacity: 0;
            transform: translateX(-50%) translateY(4px) scale(0.8);
        }
        60% {
            opacity: 1;
            transform: translateX(-50%) translateY(-6px) scale(1.05);
        }
        100% {
            opacity: 1;
            transform: translateX(-50%) translateY(-4px) scale(1);
        }
    }

    /* Control button base styles */
    .control-button {
        position: relative;
        z-index: 25;
        transition: all 0.2s cubic-bezier(0.4, 0, 0.2, 1);
    }

    .control-button:hover {
        transform: scale(1.05);
    }

    .control-button:active {
        transform: scale(0.95);
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
            window.sessionType = '{{ $isAcademicSession ? 'academic' : 'quran' }}';
            window.auth = {
                user: {
                    id: '{{ auth()->id() }}',
                    name: '{{ auth()->user()->first_name }} {{ auth()->user()->last_name }}'
                }
            };


            
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

    // CRITICAL FIX: Load session timer FIRST, then other scripts
    Promise.resolve()
        .then(() => loadScript('{{ asset("js/session-timer.js") }}?v={{ time() }}', 'sessionTimer'))
        .then(() => loadScript('{{ asset("js/livekit/data-channel.js") }}?v={{ time() }}', 'dataChannel'))
        .then(() => loadScript('{{ asset("js/livekit/connection.js") }}?v={{ time() }}', 'connection'))
        .then(() => loadScript('{{ asset("js/livekit/tracks.js") }}?v={{ time() }}', 'tracks'))
        .then(() => loadScript('{{ asset("js/livekit/participants.js") }}?v={{ time() }}', 'participants'))
        .then(() => loadScript('{{ asset("js/livekit/controls.js") }}?v={{ time() }}', 'controls'))
        .then(() => loadScript('{{ asset("js/livekit/layout.js") }}?v={{ time() }}', 'layout'))
        .then(() => loadScript('{{ asset("js/livekit/index.js") }}?v={{ time() }}', 'index'))
        .catch(error => {
            console.error('❌ Failed to load scripts:', error);
        });

    // CRITICAL FIX: Initialize Smart Session Timer with immediate loading and display
    @if($session->scheduled_at)
    function initializeSessionTimer() {
        const timerConfig = {
            sessionId: {{ $session->id }},
            scheduledAt: '{{ $session->scheduled_at->toISOString() }}',
            durationMinutes: {{ $session->duration_minutes ?? 30 }},
            preparationMinutes: {{ $preparationMinutes }},
            endingBufferMinutes: {{ $endingBufferMinutes }},
            timerElementId: 'session-timer',
            phaseElementId: 'timer-phase',
            displayElementId: 'time-display',
            
            onPhaseChange: function(newPhase, oldPhase) {
                console.log('⏰ Phase changed:', oldPhase, '→', newPhase);
                updateSessionPhaseUI(newPhase);
                
                // AUTO-TERMINATION: End meeting when time expires
                if (newPhase === 'ended' && oldPhase !== 'ended') {
                    console.log('🔴 Session time expired - auto-terminating meeting');
                    autoTerminateMeeting();
                }
            },
            
            onTick: function(timing) {
                updateSessionProgress(timing);
            }
        };

        if (typeof SmartSessionTimer !== 'undefined') {
            console.log('⏰ SmartSessionTimer available - initializing immediately');
            window.sessionTimer = new SmartSessionTimer(timerConfig);
        } else {
            console.warn('⏰ SmartSessionTimer not available - loading script first');
            loadScript('{{ asset("js/session-timer.js") }}', 'sessionTimer').then(() => {
                // Immediate initialization after script loads
                console.log('⏰ Timer script loaded - initializing SmartSessionTimer');
                window.sessionTimer = new SmartSessionTimer(timerConfig);
            }).catch(error => {
                console.error('❌ Failed to load session timer:', error);
            });
        }
    }

    // CRITICAL: Initialize timer immediately - don't wait for anything else
    console.log('⏰ Initializing session timer immediately...');
    initializeSessionTimer();
    @endif

    /**
     * Auto-terminate meeting when time expires
     */
    function autoTerminateMeeting() {
        console.log('🔴 Auto-terminating meeting - time expired');
        
        // Show notification to user
        if (typeof showNotification !== 'undefined') {
            showNotification('⏰ انتهى وقت الجلسة وتم إنهاؤها تلقائياً', 'info');
        }
        
        // Disconnect from LiveKit room if connected
        if (window.room && window.room.state === 'connected') {
            console.log('🔴 Disconnecting from LiveKit room');
            try {
                window.room.disconnect();
            } catch (error) {
                console.error('Error disconnecting from room:', error);
            }
        }
        
        // Record attendance leave if tracking
        if (window.attendanceTracker && window.attendanceTracker.isTracking) {
            console.log('🔴 Recording final attendance leave');
            window.attendanceTracker.recordLeave();
        }
        
        // Disable meeting controls
        const startMeetingBtn = document.getElementById('startMeeting');
        const joinMeetingBtn = document.getElementById('joinMeeting');
        const leaveMeetingBtn = document.getElementById('leaveMeeting');
        
        if (startMeetingBtn) {
            startMeetingBtn.disabled = true;
            startMeetingBtn.innerHTML = '<i class="ri-time-line text-xl"></i>';
            startMeetingBtn.title = 'انتهت الجلسة';
        }
        
        if (joinMeetingBtn) {
            joinMeetingBtn.disabled = true;
            joinMeetingBtn.innerHTML = '<i class="ri-time-line text-xl"></i>';
            joinMeetingBtn.title = 'انتهت الجلسة';
        }
        
        if (leaveMeetingBtn) {
            leaveMeetingBtn.style.display = 'none';
        }
        
        // Update UI to show session ended
        const connectionStatus = document.getElementById('connectionStatus');
        if (connectionStatus) {
            connectionStatus.innerHTML = '<div class="flex items-center justify-center space-x-2 rtl:space-x-reverse"><i class="ri-time-line text-gray-500"></i><span class="text-gray-500">انتهت الجلسة</span></div>';
        }
        
        // Hide video grid and show session ended message
        const videoGrid = document.getElementById('videoGrid');
        if (videoGrid) {
            videoGrid.innerHTML = `
                <div class="flex flex-col items-center justify-center h-64 text-center">
                    <i class="ri-time-line text-6xl text-gray-400 mb-4"></i>
                    <h3 class="text-xl font-semibold text-gray-600 mb-2">انتهت الجلسة</h3>
                    <p class="text-gray-500">تم إنهاء الجلسة تلقائياً بانتهاء الوقت المحدد</p>
                </div>
            `;
        }
        
        console.log('✅ Meeting auto-termination completed');
    }

    // Initialize Attendance Status Tracking (only for students)
    // CRITICAL FIX: Don't start attendance tracking on page load - only when meeting actually starts
    @if($userType === 'student')
    // Attendance tracking will be initialized by AutoAttendanceTracker when meeting starts
    @endif
    
    // Initialize Real-time Session Status Polling
    initializeSessionStatusPolling();
    
    // Initialize Network Reconnection Handling
    initializeNetworkReconnection();

    // CRITICAL FIX: Check initial session status to handle completed sessions
    checkInitialSessionStatus();

    // Update session phase UI based on timer phase
    function updateSessionPhaseUI(phase) {
        const headerElement = document.querySelector('.session-status-header');
        const timerElement = document.getElementById('session-timer');
        const statusMessage = document.querySelector('.status-message p');
        
        if (!headerElement || !timerElement) return;
        
        // Update header background based on phase
        headerElement.className = 'session-status-header px-6 py-4 border-b border-gray-100 transition-colors duration-500';
        timerElement.setAttribute('data-phase', phase);
        
        switch(phase) {
            case 'not_started':
                headerElement.classList.add('bg-gradient-to-r', 'from-gray-50', 'to-gray-100');
                break;
            case 'preparation':
                headerElement.classList.add('bg-gradient-to-r', 'from-yellow-50', 'to-amber-50');
                if (statusMessage) statusMessage.textContent = 'وقت التحضير - استعد للجلسة';
                break;
            case 'session':
                headerElement.classList.add('bg-gradient-to-r', 'from-green-50', 'to-emerald-50');
                if (statusMessage) statusMessage.textContent = 'الجلسة جارية الآن';
                break;
            case 'overtime':
                headerElement.classList.add('bg-gradient-to-r', 'from-red-50', 'to-rose-50');
                if (statusMessage) statusMessage.textContent = 'وقت إضافي - اختتم الجلسة قريباً';
                break;
            case 'ended':
                headerElement.classList.add('bg-gradient-to-r', 'from-gray-50', 'to-slate-50');
                if (statusMessage) statusMessage.textContent = 'انتهت الجلسة';
                
                // CRITICAL FIX: Stop timer when session ends
                if (window.sessionTimer) {
                    console.log('⏰ Stopping session timer - session ended');
                    window.sessionTimer.stop();
                    
                    // Set timer display to 00:00
                    const timeDisplay = document.getElementById('time-display');
                    if (timeDisplay) {
                        timeDisplay.textContent = '00:00';
                    }
                }
                break;
        }
    }

    // Update session progress
    function updateSessionProgress(timing) {
        // Update any additional UI based on timing
        // This can be expanded for more detailed progress tracking
    }

    // CRITICAL FIX: Disable old attendance tracking initialization
    function initializeAttendanceTracking() {
        console.log('📊 Old initializeAttendanceTracking() called - skipping (AutoAttendanceTracker handles this now)');
        // AutoAttendanceTracker handles all attendance tracking now
        // No automatic API calls on page load
    }

    // Initialize session status polling for real-time updates
    function initializeSessionStatusPolling() {
        // Check session status every 10 seconds for real-time button updates
        checkSessionStatus();
        setInterval(checkSessionStatus, 10000);
    }

    // Check initial session status (for when page loads on a completed session)
    function checkInitialSessionStatus() {
        // Get server-side session status from PHP
        const sessionStatus = '{{ is_object($session->status) && method_exists($session->status, 'value') ? $session->status->value : (is_object($session->status) ? $session->status->name : $session->status) }}';
        
        if (sessionStatus === 'completed') {
            console.log('⏰ Session is already completed - stopping timer immediately');
            
            // Stop timer if it exists
            if (window.sessionTimer) {
                window.sessionTimer.stop();
            }
            
            // Set timer display to 00:00
            const timeDisplay = document.getElementById('time-display');
            if (timeDisplay) {
                timeDisplay.textContent = '00:00';
            }
            
            // Update phase to ended
            updateSessionPhaseUI('ended');
        }
    }

    // Check session status and update UI accordingly
    function checkSessionStatus() {
        fetchWithAuth(`/api/sessions/{{ $session->id }}/status`)
            .then(response => response.json())
            .then(data => {
                updateSessionStatusUI(data);
                console.log('📊 Session status updated:', data);
            })
            .catch(error => {
                console.warn('⚠️ Failed to check session status:', error);
            });
    }

    // Update session status UI based on server response
    function updateSessionStatusUI(statusData) {
        const meetingBtn = document.getElementById('startMeetingBtn');
        const meetingBtnText = document.getElementById('meetingBtnText');
        const statusMessage = document.querySelector('.status-message p');
        
        if (!meetingBtn || !meetingBtnText || !statusMessage) return;

        const { status, can_join, message, button_text, button_class } = statusData;

        // Update button text and message
        meetingBtnText.textContent = button_text;
        statusMessage.textContent = message;

        // Update button classes and state
        meetingBtn.className = `join-button ${button_class} text-white px-8 py-4 rounded-xl font-semibold transition-all duration-300 flex items-center gap-3 mx-auto min-w-[240px] justify-center shadow-lg transform hover:scale-105`;
        
        // Enable/disable button based on can_join status
        if (can_join) {
            meetingBtn.disabled = false;
            meetingBtn.removeAttribute('disabled');
            meetingBtn.setAttribute('data-state', 'ready');
        } else {
            meetingBtn.disabled = true;
            meetingBtn.setAttribute('disabled', 'disabled');
            meetingBtn.setAttribute('data-state', 'waiting');
        }

        // Update icon based on status
        const iconElement = meetingBtn.querySelector('i');
        if (iconElement) {
            if (can_join) {
                iconElement.className = 'ri-video-on-line text-xl';
            } else {
                // Use status-specific icons
                iconElement.className = getStatusIcon(status) + ' text-xl';
            }
        }

        // CRITICAL FIX: Stop timer when session is completed
        if (status === 'completed' && window.sessionTimer) {
            console.log('⏰ Session completed - stopping timer');
            window.sessionTimer.stop();
            
            // Mark timer as permanently stopped to prevent restart
            window.sessionTimer.isSessionCompleted = true;
            
            // Set timer display to 00:00 and prevent further updates
            const timeDisplay = document.getElementById('time-display');
            if (timeDisplay) {
                timeDisplay.textContent = '00:00';
                // Lock the display to prevent timer updates
                timeDisplay.dataset.locked = 'true';
            }
            
            // Update phase to ended
            updateSessionPhaseUI('ended');
        }
    }

    // Get icon for session status
    function getStatusIcon(status) {
        const icons = {
            'scheduled': 'ri-calendar-line',
            'ready': 'ri-video-on-line', 
            'ongoing': 'ri-live-line',
            'completed': 'ri-check-circle-line',
            'cancelled': 'ri-close-circle-line',
            'absent': 'ri-user-unfollow-line'
        };
        return icons[status] || 'ri-question-line';
    }

    // Enhanced fetch with authentication and error handling
    async function fetchWithAuth(url, options = {}) {
        const defaultHeaders = {
            'X-Requested-With': 'XMLHttpRequest',
            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.getAttribute('content'),
            'Accept': 'application/json',
            'Content-Type': 'application/json'
        };

        const config = {
            ...options,
            headers: {
                ...defaultHeaders,
                ...options.headers
            }
        };

        try {
            const response = await fetch(url, config);
            
            // Handle authentication errors
            if (response.status === 401) {
                console.warn('🔑 Authentication failed, attempting to refresh...');
                
                // Try to refresh CSRF token
                await refreshCSRFToken();
                
                // Retry with new token
                const newToken = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content');
                config.headers['X-CSRF-TOKEN'] = newToken;
                
                return await fetch(url, config);
            }
            
            if (!response.ok) {
                throw new Error(`HTTP ${response.status}: ${response.statusText}`);
            }
            
            return response;
        } catch (error) {
            console.error('🔥 Fetch error:', error);
            throw error;
        }
    }

    // Refresh CSRF token
    async function refreshCSRFToken() {
        try {
            const response = await fetch('/csrf-token', {
                method: 'GET',
                headers: {
                    'X-Requested-With': 'XMLHttpRequest'
                }
            });
            
            if (response.ok) {
                const data = await response.json();
                document.querySelector('meta[name="csrf-token"]')?.setAttribute('content', data.token);
                console.log('🔑 CSRF token refreshed successfully');
            }
        } catch (error) {
            console.warn('⚠️ Failed to refresh CSRF token:', error);
            // Fallback: reload page if token refresh fails repeatedly
            if (window.tokenRefreshAttempts > 2) {
                console.log('🔄 Multiple token refresh failures, reloading page...');
                window.location.reload();
            }
            window.tokenRefreshAttempts = (window.tokenRefreshAttempts || 0) + 1;
        }
    }

    // CRITICAL FIX: Disable old attendance tracking function
    // This function was causing attendance tracking on page load
    function updateAttendanceStatus() {
        console.log('📊 Old updateAttendanceStatus() called - skipping (AutoAttendanceTracker handles this now)');
        return; // Do nothing - AutoAttendanceTracker handles all attendance tracking
        
        /* OLD CODE DISABLED - was causing page load attendance tracking
        fetchWithAuth(`/api/sessions/{{ $session->id }}/attendance-status`)
        .then(response => response.json())
        .then(data => {
            const statusElement = document.getElementById('attendance-status');
            const textElement = statusElement?.querySelector('.attendance-text');
            const timeElement = statusElement?.querySelector('.attendance-time');
            const dotElement = statusElement?.querySelector('.attendance-dot');
            
            if (!statusElement || !textElement || !timeElement) return;
            
            // Update status text
            const statusLabels = {
                'present': 'حاضر',
                'late': 'متأخر',
                'partial': 'حضور جزئي',
                'absent': 'غائب'
            };
            
            const isInMeeting = data.is_currently_in_meeting;
            
            // CRITICAL FIX: Better status detection for active users
            let statusLabel;
            if (isInMeeting) {
                statusLabel = 'حاضر'; // User is currently in meeting
            } else if (data.duration_minutes > 0) {
                statusLabel = statusLabels[data.attendance_status] || 'حضر سابقاً';
            } else {
                statusLabel = statusLabels[data.attendance_status] || 'لم تنضم بعد';
            }
            
            textElement.textContent = isInMeeting ? 
                `${statusLabel} (في الجلسة الآن)` : 
                statusLabel;
            
            // Update time info
            if (data.duration_minutes > 0) {
                timeElement.textContent = `مدة الحضور: ${data.duration_minutes} دقيقة`;
            } else {
                timeElement.textContent = '--';
            }
            
            // Update dot color
            if (dotElement) {
                dotElement.className = 'attendance-dot w-3 h-3 rounded-full transition-all duration-300';
                
                if (isInMeeting) {
                    dotElement.classList.add('bg-green-500', 'animate-pulse');
                } else if (data.attendance_status === 'present') {
                    dotElement.classList.add('bg-green-400');
                } else if (data.attendance_status === 'late') {
                    dotElement.classList.add('bg-yellow-400');
                } else if (data.attendance_status === 'partial') {
                    dotElement.classList.add('bg-orange-400');
                } else {
                    dotElement.classList.add('bg-gray-400');
                }
            }
            
            console.log('📊 Attendance status updated:', data);
        })
        .catch(error => {
            console.warn('⚠️ Failed to update attendance status:', error);
        });
        */ // END OF DISABLED CODE
    }

    // Initialize network reconnection handling
    function initializeNetworkReconnection() {
        let isOnline = navigator.onLine;
        let reconnectAttempts = 0;
        const maxReconnectAttempts = 5;

        // Listen for online/offline events
        window.addEventListener('online', handleNetworkOnline);
        window.addEventListener('offline', handleNetworkOffline);

        function handleNetworkOffline() {
            isOnline = false;
            console.log('🔌 Network disconnected');
            showNetworkStatus('غير متصل بالشبكة', 'offline');
        }

        function handleNetworkOnline() {
            console.log('🔌 Network reconnected');
            isOnline = true;
            showNetworkStatus('إعادة الاتصال...', 'reconnecting');
            
            // Reset token refresh attempts
            window.tokenRefreshAttempts = 0;
            
            // Attempt to reconnect LiveKit and refresh data
            setTimeout(attemptReconnection, 1000);
        }

        async function attemptReconnection() {
            if (!isOnline || reconnectAttempts >= maxReconnectAttempts) {
                if (reconnectAttempts >= maxReconnectAttempts) {
                    showNetworkStatus('فشل في إعادة الاتصال - يرجى إعادة تحميل الصفحة', 'error');
                }
                return;
            }

            reconnectAttempts++;
            console.log(`🔄 Reconnection attempt ${reconnectAttempts}/${maxReconnectAttempts}`);

            try {
                // Refresh CSRF token first
                await refreshCSRFToken();
                
                // Test API connectivity
                await fetchWithAuth('/api/server-time');
                
                // Update session status and attendance
                await Promise.all([
                    checkSessionStatus(),
                    updateAttendanceStatus()
                ]);

                // Try to reconnect LiveKit if room exists
                if (window.room && window.room.state === 'disconnected') {
                    console.log('🎥 Attempting to reconnect LiveKit room...');
                    
                    // Check if we have an active meeting and try to rejoin
                    const connectionStatus = document.getElementById('connectionStatus');
                    if (connectionStatus) {
                        connectionStatus.style.display = 'block';
                        const connectionText = document.getElementById('connectionText');
                        if (connectionText) {
                            connectionText.textContent = 'إعادة الاتصال بالجلسة...';
                        }
                    }

                    // Trigger rejoin process
                    const startMeetingBtn = document.getElementById('startMeetingBtn');
                    if (startMeetingBtn && !startMeetingBtn.disabled) {
                        // Auto-rejoin if the meeting is still active
                        setTimeout(() => {
                            if (window.room && window.room.state === 'disconnected') {
                                startMeetingBtn.click();
                            }
                        }, 2000);
                    }
                }

                // CRITICAL FIX: Hide loading overlay after successful reconnection
                const loadingOverlay = document.getElementById('loadingOverlay');
                if (loadingOverlay && loadingOverlay.style.display !== 'none') {
                    console.log('🔄 Hiding loading overlay after reconnection');
                    loadingOverlay.classList.add('fade-out');
                    setTimeout(() => {
                        loadingOverlay.style.display = 'none';
                        loadingOverlay.classList.remove('fade-out');
                    }, 500);
                }

                showNetworkStatus('متصل', 'online');
                reconnectAttempts = 0; // Reset on successful reconnection
                
                console.log('✅ Reconnection successful');

            } catch (error) {
                console.warn(`⚠️ Reconnection attempt ${reconnectAttempts} failed:`, error);
                
                if (reconnectAttempts < maxReconnectAttempts) {
                    // Exponential backoff
                    const delay = Math.min(1000 * Math.pow(2, reconnectAttempts), 10000);
                    setTimeout(attemptReconnection, delay);
                } else {
                    showNetworkStatus('فشل في إعادة الاتصال', 'error');
                }
            }
        }

        function showNetworkStatus(message, status) {
            // Create or update network status indicator
            let networkIndicator = document.getElementById('networkIndicator');
            
            if (!networkIndicator) {
                networkIndicator = document.createElement('div');
                networkIndicator.id = 'networkIndicator';
                networkIndicator.className = 'fixed top-4 right-4 z-50 px-4 py-2 rounded-lg text-sm font-medium transition-all duration-300';
                document.body.appendChild(networkIndicator);
            }

            networkIndicator.textContent = message;
            
            // Update styling based on status
            networkIndicator.className = 'fixed top-4 right-4 z-50 px-4 py-2 rounded-lg text-sm font-medium transition-all duration-300';
            
            switch(status) {
                case 'online':
                    networkIndicator.classList.add('bg-green-500', 'text-white');
                    setTimeout(() => {
                        networkIndicator.style.opacity = '0';
                        setTimeout(() => networkIndicator.remove(), 300);
                    }, 3000);
                    break;
                case 'offline':
                    networkIndicator.classList.add('bg-red-500', 'text-white');
                    break;
                case 'reconnecting':
                    networkIndicator.classList.add('bg-yellow-500', 'text-white');
                    break;
                case 'error':
                    networkIndicator.classList.add('bg-red-600', 'text-white');
                    break;
            }
            
            networkIndicator.style.opacity = '1';
        }
    }
</script>



<!-- Enhanced Smart Meeting Interface -->
<div class="session-join-container bg-white rounded-xl shadow-sm border border-gray-200 overflow-hidden">
    <!-- Session Status Header -->
    <div class="session-status-header bg-gradient-to-r from-blue-50 to-indigo-50 px-6 py-4 border-b border-gray-100" data-phase="waiting">
        <div class="flex items-center justify-between">
            <div class="flex items-center gap-3">
                <div class="status-indicator flex items-center gap-2">
                    <h2 class="text-lg font-bold text-gray-900 flex items-center gap-2">
                        <i class="ri-video-line text-blue-600"></i>
                        @if($userType === 'quran_teacher')
                        إدارة الاجتماع المباشر
                        @else
                        الانضمام للجلسة المباشرة
                        @endif
                    </h2>
                </div>
            </div>
            
            <!-- Session Timer -->
            @if($session->scheduled_at)
            <div class="session-timer text-left" id="session-timer" data-phase="waiting">
                <div class="flex items-center gap-2 text-sm text-gray-600">
                    <span id="timer-phase" class="phase-label font-medium">في انتظار الجلسة</span>
                    <span class="text-gray-400">|</span>
                    <span id="time-display" class="time-display font-mono font-bold text-lg">--:--</span>
                </div>
                <div class="w-full bg-gray-200 rounded-full h-1 mt-2">
                    <div id="timer-progress" class="bg-blue-500 h-1 rounded-full transition-all duration-1000" style="width: 0%"></div>
                </div>
            </div>
            @endif
        </div>
    </div>

    <!-- Main Content Area -->
    <div class="p-6">
        <div class="flex flex-col lg:flex-row gap-6">
            <!-- Left Column: Status & Info -->
            <div class="flex-1 space-y-4">
                <!-- Main Action Area -->
                <div class="join-action-area text-center py-6">
                    <!-- Join Button -->
                    <button
                        id="startMeetingBtn"
                        class="join-button {{ $buttonClass }} text-white px-8 py-4 rounded-xl font-semibold transition-all duration-300 flex items-center gap-3 mx-auto min-w-[240px] justify-center shadow-lg transform hover:scale-105"
                        data-state="{{ $canJoinMeeting ? 'ready' : 'waiting' }}"
                        {{ $buttonDisabled ? 'disabled' : '' }}>
                        
                        @if($canJoinMeeting)
                            <i class="ri-video-on-line text-xl"></i>
                        @else
                            <i class="{{ is_object($session->status) && method_exists($session->status, 'icon') ? $session->status->icon() : 'ri-question-line' }} text-xl"></i>
                        @endif
                        <span id="meetingBtnText" class="text-lg">{{ $buttonText }}</span>
                    </button>

                    <!-- Status Message -->
                    <div class="status-message mt-4 bg-gray-50 rounded-lg p-3">
                        <p class="text-gray-700 text-sm font-medium">{{ $meetingMessage }}</p>
                    </div>
                </div>

                <!-- Session Info Grid -->
                <div class="session-info bg-gray-50 rounded-lg p-4">
                    <h3 class="text-sm font-semibold text-gray-900 mb-3 flex items-center gap-2">
                        <i class="ri-information-line text-blue-600"></i>
                        معلومات الجلسة
                    </h3>
                    <div class="grid grid-cols-2 gap-4 text-sm">
                        <div class="info-item flex justify-between">
                            <span class="label text-gray-600">وقت الجلسة:</span>
                            <span class="value font-medium text-gray-900">{{ $session->scheduled_at ? $session->scheduled_at->format('h:i A') : 'غير محدد' }}</span>
                        </div>
                        <div class="info-item flex justify-between">
                            <span class="label text-gray-600">المدة:</span>
                            <span class="value font-medium text-gray-900">{{ $session->duration_minutes ?? 30 }} دقيقة</span>
                        </div>
                        @if($circle)
                        <div class="info-item flex justify-between">
                            <span class="label text-gray-600">فترة التحضير:</span>
                            <span class="value font-medium text-gray-900">{{ $preparationMinutes }} دقيقة</span>
                        </div>
                        <div class="info-item flex justify-between">
                            <span class="label text-gray-600">الوقت الإضافي:</span>
                            <span class="value font-medium text-gray-900">{{ $endingBufferMinutes }} دقيقة</span>
                        </div>
                        @endif
                    </div>
                    
                    @if($session->meeting_room_name)
                    <div class="mt-3 pt-3 border-t border-gray-200">
                        <div class="flex justify-between items-center text-sm">
                            <span class="text-gray-600">رقم الغرفة:</span>
                            <code class="bg-white px-2 py-1 rounded text-xs font-mono border">{{ $session->meeting_room_name }}</code>
                        </div>
                    </div>
                    @endif
                </div>
            </div>

            <!-- Right Column: Controls & Status -->
            <div class="lg:w-80 space-y-4">
                <!-- Enhanced Attendance Status (Only for students) -->
                @if($userType === 'student')
                <div class="attendance-status bg-gradient-to-r from-gray-50 to-gray-100 rounded-lg p-4 border border-gray-200 shadow-sm" id="attendance-status">
                    <div class="flex items-center gap-3 mb-3">
                        <div class="attendance-indicator flex items-center gap-2">
                            <span class="attendance-dot w-3 h-3 rounded-full bg-gray-400 transition-all duration-300"></span>
                            <i class="attendance-icon ri-user-line text-lg text-gray-600"></i>
                            <h3 class="text-sm font-semibold text-gray-900">حالة الحضور</h3>
                        </div>
                    </div>
                    <div class="attendance-details">
                        <div class="attendance-text text-sm text-gray-700 font-medium mb-1">جاري التحميل...</div>
                        <div class="attendance-time text-xs text-gray-500">--</div>
                    </div>
                    
                    <!-- Optional: Progress bar for attendance percentage -->
                    <div class="mt-3 hidden" id="attendance-progress">
                        <div class="flex justify-between items-center text-xs text-gray-600 mb-1">
                            <span>نسبة الحضور</span>
                            <span class="attendance-percentage">0%</span>
                        </div>
                        <div class="w-full bg-gray-200 rounded-full h-2">
                            <div class="bg-green-500 h-2 rounded-full transition-all duration-300" style="width: 0%" id="attendance-progress-bar"></div>
                        </div>
                    </div>
                </div>
                @endif

                <!-- System Status -->
                <div class="system-status bg-gray-50 rounded-lg p-4">
                    <h3 class="text-sm font-semibold text-gray-900 mb-3 flex items-center gap-2">
                        <i class="ri-shield-check-line text-gray-600"></i>
                        حالة النظام
                    </h3>
                    <div class="space-y-3">
                        <!-- Camera Permission -->
                        <div class="flex items-center justify-between p-3 bg-white rounded-lg border border-gray-200">
                            <div class="flex items-center gap-3">
                                <div class="w-8 h-8 rounded-full flex items-center justify-center" id="camera-status-icon">
                                    <i class="ri-camera-line text-gray-400"></i>
                                </div>
                                <div>
                                    <div class="text-sm font-medium text-gray-900">كاميرا المتصفح</div>
                                    <div class="text-xs text-gray-600" id="camera-status-text">جاري التحقق...</div>
                                </div>
                            </div>
                            <button id="camera-permission-btn" class="px-3 py-1 text-xs font-medium rounded-full bg-blue-100 text-blue-700 hover:bg-blue-200 transition-colors hidden">
                                منح الإذن
                            </button>
                        </div>

                        <!-- Microphone Permission -->
                        <div class="flex items-center justify-between p-3 bg-white rounded-lg border border-gray-200">
                            <div class="flex items-center gap-3">
                                <div class="w-8 h-8 rounded-full flex items-center justify-center" id="mic-status-icon">
                                    <i class="ri-mic-line text-gray-400"></i>
                                </div>
                                <div>
                                    <div class="text-sm font-medium text-gray-900">ميكروفون المتصفح</div>
                                    <div class="text-xs text-gray-600" id="mic-status-text">جاري التحقق...</div>
                                </div>
                            </div>
                            <button id="mic-permission-btn" class="px-3 py-1 text-xs font-medium rounded-full bg-blue-100 text-blue-700 hover:bg-blue-200 transition-colors hidden">
                                منح الإذن
                            </button>
                        </div>

                        <!-- Network Status -->
                        <div class="flex items-center justify-between p-3 bg-white rounded-lg border border-gray-200">
                            <div class="flex items-center gap-3">
                                <div class="w-8 h-8 rounded-full flex items-center justify-center" id="network-status-icon">
                                    <i class="ri-wifi-line text-gray-400"></i>
                                </div>
                                <div>
                                    <div class="text-sm font-medium text-gray-900">حالة الاتصال</div>
                                    <div class="text-xs text-gray-600" id="network-status-text">جاري التحقق...</div>
                                </div>
                            </div>
                            <div class="text-xs text-gray-500" id="network-speed"></div>
                        </div>

                        <!-- Browser Compatibility -->
                        <div class="flex items-center justify-between p-3 bg-white rounded-lg border border-gray-200">
                            <div class="flex items-center gap-3">
                                <div class="w-8 h-8 rounded-full flex items-center justify-center" id="browser-status-icon">
                                    <i class="ri-global-line text-gray-400"></i>
                                </div>
                                <div>
                                    <div class="text-sm font-medium text-gray-900">توافق المتصفح</div>
                                    <div class="text-xs text-gray-600" id="browser-status-text">جاري التحقق...</div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

            </div>
        </div>
    </div>
</div>

@if($userType === 'quran_teacher')
<!-- Session Status Management Section -->
<div class="mt-6 pt-6 border-t border-gray-200">
    <h3 class="text-lg font-semibold text-gray-900 mb-4">إدارة حالة الجلسة</h3>
    
    <div class="flex flex-wrap gap-3">
        @switch($session->status instanceof \BackedEnum ? $session->status->value : $session->status)
            @case('scheduled')
            @case('ready')
            @case('ongoing')
                @if($session->session_type === 'group')
                    <!-- Group Session: Mark as Canceled -->
                    <button id="cancelSessionBtn" 
                            class="bg-red-600 hover:bg-red-700 text-white px-4 py-2 rounded-lg font-medium transition-colors duration-200 flex items-center gap-2"
                            onclick="cancelSession('{{ $session->id }}')">
                        <i class="ri-close-circle-line"></i>
                        إلغاء الجلسة (عدم حضور المعلم)
                    </button>
                @elseif($session->session_type === 'individual')
                    <!-- Individual Session: Multiple options -->
                    <button id="cancelSessionBtn" 
                            class="bg-red-600 hover:bg-red-700 text-white px-4 py-2 rounded-lg font-medium transition-colors duration-200 flex items-center gap-2"
                            onclick="cancelSession('{{ $session->id }}')">
                        <i class="ri-close-circle-line"></i>
                        إلغاء الجلسة
                    </button>
                    
                    <button id="markStudentAbsentBtn" 
                            class="bg-yellow-600 hover:bg-yellow-700 text-white px-4 py-2 rounded-lg font-medium transition-colors duration-200 flex items-center gap-2"
                            onclick="markStudentAbsent('{{ $session->id }}')">
                        <i class="ri-user-unfollow-line"></i>
                        تسجيل غياب الطالب
                    </button>
                @endif
                
                <!-- Complete Session Button (for both types if session is ongoing) -->
                @if((is_object($session->status) && method_exists($session->status, 'value') ? $session->status->value : $session->status) === 'ongoing')
                <button id="completeSessionBtn" 
                        class="bg-green-600 hover:bg-green-700 text-white px-4 py-2 rounded-lg font-medium transition-colors duration-200 flex items-center gap-2"
                        onclick="completeSession('{{ $session->id }}')">
                    <i class="ri-check-circle-line"></i>
                    إنهاء الجلسة
                </button>
                @endif
                @break
                
            @case('completed')
                <!-- No actions needed for completed sessions -->
                <div class="text-green-600 flex items-center gap-2">
                    <i class="ri-check-circle-fill text-lg"></i>
                    <span class="font-medium">تم إنهاء الجلسة بنجاح</span>
                </div>
                @break
                
            @case('cancelled')
                <!-- No actions needed for cancelled sessions -->
                <div class="text-red-600 flex items-center gap-2">
                    <i class="ri-close-circle-fill text-lg"></i>
                    <span class="font-medium">تم إلغاء الجلسة</span>
                </div>
                @break
                
            @case('absent')
                <!-- No actions needed for absent sessions -->
                <div class="text-gray-600 flex items-center gap-2">
                    <i class="ri-user-unfollow-fill text-lg"></i>
                    <span class="font-medium">تم تسجيل غياب الطالب</span>
                </div>
                @break
                
            @default
                <!-- Unknown status -->
                <div class="text-gray-500 flex items-center gap-2">
                    <i class="ri-question-line text-lg"></i>
                    <span class="font-medium">حالة غير معروفة: {{ is_object($session->status) && method_exists($session->status, 'label') ? $session->status->label() : $session->status }}</span>
                </div>
        @endswitch
    </div>
</div>

<script>
// Session status management functions
function cancelSession(sessionId) {
    if (!confirm('هل أنت متأكد من إلغاء هذه الجلسة؟ لن يتم احتساب هذه الجلسة في الاشتراك.')) {
        return;
    }
    
    fetch(`/teacher/sessions/${sessionId}/cancel`, {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
            'X-CSRF-TOKEN': '{{ csrf_token() }}'
        }
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            showNotification('تم إلغاء الجلسة بنجاح', 'success');
            setTimeout(() => window.location.reload(), 2000);
        } else {
            showNotification('فشل في إلغاء الجلسة: ' + (data.message || 'خطأ غير معروف'), 'error');
        }
    })
    .catch(error => {
        console.error('Error:', error);
        showNotification('حدث خطأ أثناء إلغاء الجلسة', 'error');
    });
}

function markStudentAbsent(sessionId) {
    if (!confirm('هل أنت متأكد من تسجيل غياب الطالب؟')) {
        return;
    }
    
    fetch(`/teacher/sessions/${sessionId}/mark-student-absent`, {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
            'X-CSRF-TOKEN': '{{ csrf_token() }}'
        }
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            showNotification('تم تسجيل غياب الطالب بنجاح', 'success');
            setTimeout(() => window.location.reload(), 2000);
        } else {
            showNotification('فشل في تسجيل غياب الطالب: ' + (data.message || 'خطأ غير معروف'), 'error');
        }
    })
    .catch(error => {
        console.error('Error:', error);
        showNotification('حدث خطأ أثناء تسجيل غياب الطالب', 'error');
    });
}

function completeSession(sessionId) {
    if (!confirm('هل أنت متأكد من إنهاء هذه الجلسة؟')) {
        return;
    }
    
    fetch(`/teacher/sessions/${sessionId}/complete`, {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
            'X-CSRF-TOKEN': '{{ csrf_token() }}'
        }
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            showNotification('تم إنهاء الجلسة بنجاح', 'success');
            setTimeout(() => window.location.reload(), 2000);
        } else {
            showNotification('فشل في إنهاء الجلسة: ' + (data.message || 'خطأ غير معروف'), 'error');
        }
    })
    .catch(error => {
        console.error('Error:', error);
        showNotification('حدث خطأ أثناء إنهاء الجلسة', 'error');
    });
}

function showNotification(message, type = 'info', duration = 5000) {
    const notification = document.createElement('div');
    notification.className = `fixed top-4 right-4 p-4 rounded-lg shadow-lg max-w-sm z-50 transform translate-x-full transition-transform duration-300`;
    
    const colors = {
        success: 'bg-green-500 text-white',
        error: 'bg-red-500 text-white',
        warning: 'bg-yellow-500 text-white',
        info: 'bg-blue-500 text-white'
    };
    
    notification.className += ` ${colors[type] || colors.info}`;
    
    notification.innerHTML = `
        <div class="flex items-center justify-between">
            <span>${message}</span>
            <button onclick="this.parentElement.parentElement.remove()" class="ml-2 hover:opacity-70">
                <i class="ri-close-line"></i>
            </button>
        </div>
    `;
    
    document.body.appendChild(notification);
    
    setTimeout(() => notification.classList.remove('translate-x-full'), 100);
    setTimeout(() => {
        notification.classList.add('translate-x-full');
        setTimeout(() => notification.remove(), 300);
    }, duration);
}
</script>
@endif

<!-- Meeting Container -->
<div id="meetingContainer" class="bg-white rounded-lg shadow-md overflow-hidden mt-8" style="display: none;">
    <!-- LiveKit Meeting Interface - Dynamic Height -->
    <div id="livekitMeetingInterface" class="bg-gray-900 relative overflow-hidden" style="min-height: 400px;">
        <!-- Loading Overlay - ENHANCED WITH SMOOTH TRANSITIONS -->
        <div id="loadingOverlay" class="absolute inset-0 bg-black bg-opacity-75 flex items-center justify-center z-22">
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
                        <i class="ri-group-line text-lg text-white"></i>
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
                    <i id="fullscreenIcon" class="ri-fullscreen-line text-lg text-white"></i>
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
                            <i class="ri-close-line text-xl"></i>
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
                                        <i class="ri-send-plane-line text-lg"></i>
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
                                        <i class="ri-hand-heart-line text-5xl mx-auto mb-4 text-gray-500 block"></i>
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
                                @if($userType === 'quran_teacher')
                                <!-- Teacher Controls - Simplified Design -->
                                <div class="bg-gray-700 rounded-lg p-4">
                                    <h4 class="text-white font-medium mb-4">التحكم في الطلاب</h4>
                                    <div class="space-y-4">
                                        <!-- Microphone Control -->
                                        <div class="flex items-center justify-between py-3 border-b border-gray-600">
                                            <div class="flex items-center gap-3">
                                                <div class="w-10 h-10 bg-blue-600 rounded-lg flex items-center justify-center">
                                                    <i class="ri-mic-line text-white text-xl"></i>
                                                </div>
                                                <div>
                                                    <p class="text-white font-medium text-sm">السماح بالميكروفون</p>
                                                    <p class="text-gray-400 text-xs">السماح للطلاب بإستخدام الميكروفون</p>
                                                </div>
                                            </div>
                                            <label class="relative inline-flex items-center cursor-pointer">
                                                <input type="checkbox" id="toggleAllStudentsMicSwitch" class="sr-only peer">
                                                <div class="w-11 h-6 bg-gray-500 peer-focus:outline-none peer-focus:ring-4 peer-focus:ring-blue-300 rounded-full peer peer-checked:after:translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-[2px] after:left-[2px] after:bg-white after:rounded-full after:h-5 after:w-5 after:transition-all peer-checked:bg-green-600"></div>
                                            </label>
                                        </div>

                                        <!-- Camera Control -->
                                        <div class="flex items-center justify-between py-3">
                                            <div class="flex items-center gap-3">
                                                <div class="w-10 h-10 bg-purple-600 rounded-lg flex items-center justify-center">
                                                    <i class="ri-vidicon-line text-white text-xl"></i>
                                                </div>
                                                <div>
                                                    <p class="text-white font-medium text-sm">السماح بالكاميرا</p>
                                                    <p class="text-gray-400 text-xs">السماح للطلاب بإستخدام الكاميرا</p>
                                                </div>
                                            </div>
                                            <label class="relative inline-flex items-center cursor-pointer">
                                                <input type="checkbox" id="toggleAllStudentsCameraSwitch" class="sr-only peer">
                                                <div class="w-11 h-6 bg-gray-500 peer-focus:outline-none peer-focus:ring-4 peer-focus:ring-purple-300 rounded-full peer peer-checked:after:translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-[2px] after:left-[2px] after:bg-white after:rounded-full after:h-5 after:w-5 after:transition-all peer-checked:bg-green-600"></div>
                                            </label>
                                        </div>
                                    </div>
                                </div>
                                @else
                                <!-- Student Settings - Device Selection -->
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
                                @endif
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Control Bar - Always at bottom -->
            <div class="control-bar bottom-0 left-0 right-0 bg-gray-800 border-t border-gray-700 px-4 py-4 flex items-center justify-center gap-2 sm:gap-4 shadow-lg flex-wrap sm:flex-nowrap z-11">
                <!-- Microphone Button -->
                <button id="toggleMic" class="control-button w-12 h-12 sm:w-14 sm:h-14 rounded-full bg-gray-600 hover:bg-gray-500 text-white flex items-center justify-center transition-all duration-200 hover:scale-105 focus:outline-none focus:ring-2 focus:ring-blue-500 active:scale-95">
                    <i class="ri-mic-line text-xl"></i>
                    <div class="control-tooltip">إيقاف/تشغيل الميكروفون</div>
                </button>

                <!-- Camera Button -->
                <button id="toggleCamera" class="control-button w-12 h-12 sm:w-14 sm:h-14 rounded-full bg-gray-600 hover:bg-gray-500 text-white flex items-center justify-center transition-all duration-200 hover:scale-105 focus:outline-none focus:ring-2 focus:ring-blue-500 active:scale-95">
                    <i class="ri-vidicon-line text-xl"></i>
                    <div class="control-tooltip">إيقاف/تشغيل الكاميرا</div>
                </button>

                @if($userType === 'quran_teacher')
                <!-- Screen Share Button (Teachers Only) -->
                <button id="toggleScreenShare" class="control-button w-12 h-12 sm:w-14 sm:h-14 rounded-full bg-gray-600 hover:bg-gray-500 text-white flex items-center justify-center transition-all duration-200 hover:scale-105 focus:outline-none focus:ring-2 focus:ring-blue-500 active:scale-95">
                    <i class="ri-share-box-line text-xl"></i>
                    <div class="control-tooltip">مشاركة الشاشة</div>
                </button>
                @endif

                @if($userType !== 'quran_teacher')
                <!-- Hand Raise Button -->
                <button id="toggleHandRaise" class="control-button w-12 h-12 sm:w-14 sm:h-14 rounded-full bg-gray-600 hover:bg-orange-500 text-white flex items-center justify-center transition-all duration-200 hover:scale-105 focus:outline-none focus:ring-2 focus:ring-orange-500 active:scale-95">
                    <i class="ri-hand text-white text-xl"></i>
                    <div class="control-tooltip">رفع اليد</div>
                </button>
                @endif

                <!-- Chat Button -->
                <button id="toggleChat" class="control-button w-12 h-12 sm:w-14 sm:h-14 rounded-full bg-gray-600 hover:bg-gray-500 text-white flex items-center justify-center transition-all duration-200 hover:scale-105 focus:outline-none focus:ring-2 focus:ring-blue-500 active:scale-95">
                    <i class="ri-chat-3-line text-xl"></i>
                    <div class="control-tooltip">إظهار/إخفاء الدردشة</div>
                </button>

                <!-- Participants Button -->
                <button id="toggleParticipants" class="control-button w-12 h-12 sm:w-14 sm:h-14 rounded-full bg-gray-600 hover:bg-gray-500 text-white flex items-center justify-center transition-all duration-200 hover:scale-105 focus:outline-none focus:ring-2 focus:ring-blue-500 active:scale-95">
                    <i class="ri-group-line text-xl"></i>
                    <div class="control-tooltip">إظهار/إخفاء المشاركين</div>
                </button>

                @if($userType === 'quran_teacher')
                <!-- Raised Hands Button (Teachers Only) -->
                <button id="toggleRaisedHands" class="control-button w-12 h-12 sm:w-14 sm:h-14 rounded-full bg-gray-600 hover:bg-orange-500 text-white flex items-center justify-center transition-all duration-200 hover:scale-105 focus:outline-none focus:ring-2 focus:ring-orange-500 active:scale-95 relative">
                    <i class="ri-hand text-white text-xl"></i>
                    <!-- Notification Badge -->
                    <div id="raisedHandsNotificationBadge" class="absolute -top-1 -right-1 w-5 h-5 bg-red-500 text-white text-xs rounded-full flex items-center justify-center font-bold hidden">
                        <span id="raisedHandsBadgeCount">0</span>
                    </div>
                    <div class="control-tooltip">إدارة الأيدي المرفوعة</div>
                </button>
                @endif

                @php
                    // Only show recording for Interactive Course sessions (Academic teachers only)
                    $isInteractiveCourse = ($session->session_type === 'interactive_course' || 
                                          (isset($session->interactiveCourseSession) && $session->interactiveCourseSession) ||
                                          (method_exists($session, 'session_type') && $session->session_type === 'interactive_course'));
                    $showRecording = $userType === 'academic_teacher' && $isInteractiveCourse;
                @endphp
                
                @if($showRecording)
                <!-- Recording Button (Interactive Courses Only) -->
                <button id="toggleRecording" class="control-button w-12 h-12 sm:w-14 sm:h-14 rounded-full bg-gray-600 hover:bg-red-500 text-white flex items-center justify-center transition-all duration-200 hover:scale-105 focus:outline-none focus:ring-2 focus:ring-red-500 active:scale-95 relative">
                    <i class="ri-record-circle-line text-xl" id="recordingIcon"></i>
                    <div id="recordingIndicator" class="absolute -top-1 -right-1 w-3 h-3 bg-red-500 rounded-full animate-pulse hidden"></div>
                    <div class="control-tooltip">بدء/إيقاف تسجيل الدورة</div>
                </button>
                @endif

                @if($userType === 'quran_teacher')
                <!-- Settings Button (Teachers Only) -->
                <button id="toggleSettings" class="control-button w-12 h-12 sm:w-14 sm:h-14 rounded-full bg-gray-600 hover:bg-gray-500 text-white flex items-center justify-center transition-all duration-200 hover:scale-105 focus:outline-none focus:ring-2 focus:ring-blue-500 active:scale-95">
                    <i class="ri-settings-3-line text-xl"></i>
                    <div class="control-tooltip">الإعدادات</div>
                </button>
                @endif

                <!-- Leave Button -->
                <button id="leaveMeeting" class="control-button w-12 h-12 sm:w-14 sm:h-14 rounded-full bg-red-600 hover:bg-red-700 text-white flex items-center justify-center transition-all duration-200 hover:scale-105 focus:outline-none focus:ring-2 focus:ring-red-500 active:scale-95 relative meeting-control-button">
                    <i class="ri-logout-box-line text-xl"></i>
                    <div class="control-tooltip">مغادرة الجلسة</div>
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

                    // CRITICAL FIX: Check if user is already in the meeting
                    if (window.meeting || startBtn.disabled) {
                        console.log('⚠️ Meeting already initialized or initializing, ignoring click');
                        return;
                    }

                    // CRITICAL FIX: Check if already tracking attendance (user is in meeting)
                    if (attendanceTracker && attendanceTracker.isTracking) {
                        console.log('⚠️ User already in meeting and attendance is being tracked, ignoring click');
                        return;
                    }

                    try {
                        // Show loading state
                        startBtn.disabled = true;
                        const btnText = document.getElementById('meetingBtnText');
                        const originalText = btnText?.textContent;
                        
                        if (btnText) {
                            btnText.textContent = 'جاري الاتصال...';
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

                        // CRITICAL FIX: Immediately record join when meeting starts
                        if (attendanceTracker) {
                            console.log('🎯 Recording join immediately after meeting start');
                            setTimeout(() => {
                                attendanceTracker.recordJoin();
                            }, 1000);
                        }

                        // Update button text
                        if (btnText) btnText.textContent = 'متصل';

                    } catch (error) {
                        console.error('❌ Failed to start meeting:', error);

                        // Reset button state
                        startBtn.disabled = false;
                        const btnText = document.getElementById('meetingBtnText');
                        if (btnText) {
                            btnText.textContent = 'إعادة المحاولة';
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





</script>

<!-- Auto-join functionality removed - meetings now require manual start -->

<!-- Meeting Timer System -->
<script>
document.addEventListener('DOMContentLoaded', function() {
    // =====================================================
    // Session Starting Soon Notification (Centralized)
    // Shows toast notification when session is starting soon
    // =====================================================
    @if($session->scheduled_at && $session->scheduled_at->isFuture() && $session->scheduled_at->diffInMinutes(now()) <= 15)
        @php
            $timeData = formatTimeRemaining($session->scheduled_at);
        @endphp
        @if(!$timeData['is_past'])
            (function() {
                const notification = document.createElement('div');
                notification.className = 'fixed top-4 right-4 p-4 rounded-lg shadow-lg max-w-sm z-50 transform translate-x-full transition-transform duration-300 bg-blue-500 text-white';
                notification.innerHTML = `
                    <div class="flex items-center justify-between gap-3">
                        <div class="flex items-center gap-2">
                            <i class="ri-time-line text-lg"></i>
                            <span>الجلسة ستبدأ خلال {{ $timeData['formatted'] }}</span>
                        </div>
                        <button onclick="this.parentElement.parentElement.remove()" class="hover:opacity-70 flex-shrink-0">
                            <i class="ri-close-line"></i>
                        </button>
                    </div>
                `;
                document.body.appendChild(notification);

                // Animate in
                setTimeout(() => notification.classList.remove('translate-x-full'), 100);

                // Auto-dismiss after 8 seconds
                setTimeout(() => {
                    notification.classList.add('translate-x-full');
                    setTimeout(() => notification.remove(), 300);
                }, 8000);
            })();
        @endif
    @endif

    // Meeting Timer Class
    class MeetingTimer {
        constructor() {
            this.timerElement = document.getElementById('meetingTimer');
            this.displayElement = document.getElementById('timerDisplay');
            this.labelElement = document.getElementById('timerLabel');
            this.statusElement = document.getElementById('timerStatus');

            @if($session->scheduled_at)
            this.scheduledAt = new Date('{{ $session->scheduled_at->toISOString() }}');
            this.duration = {{ $session->duration_minutes ?? 60 }} * 60 * 1000; // milliseconds
            this.endingBuffer = {{ $endingBufferMinutes ?? 5 }} * 60 * 1000; // milliseconds

            if (this.timerElement && this.displayElement) {
                console.log('🕐 Timer initialized for session at:', this.scheduledAt);
                this.start();
            }
            @endif
        }
        
        start() {
            this.update();
            this.interval = setInterval(() => this.update(), 1000);
        }
        
        update() {
            const now = new Date();
            const scheduledTime = this.scheduledAt;
            const sessionEndTime = new Date(scheduledTime.getTime() + this.duration);
            const finalEndTime = new Date(sessionEndTime.getTime() + this.endingBuffer);
            
            let timeLeft, status, phase;
            
            if (now < scheduledTime) {
                // Before meeting starts (orange phase)
                timeLeft = scheduledTime - now;
                phase = 'waiting';
                this.labelElement.textContent = 'بداية الجلسة خلال';
                this.statusElement.textContent = 'في انتظار بداية الجلسة';
                this.updateColors('bg-orange-50', 'border-orange-200', 'text-orange-900', 'text-orange-700', 'text-orange-600');
            } else if (now >= scheduledTime && now < sessionEndTime) {
                // During meeting (green phase)
                timeLeft = now - scheduledTime;
                phase = 'active';
                this.labelElement.textContent = 'الجلسة جارية منذ';
                this.statusElement.textContent = 'الجلسة نشطة حالياً';
                this.updateColors('bg-green-50', 'border-green-200', 'text-green-900', 'text-green-700', 'text-green-600');
            } else if (now >= sessionEndTime && now < finalEndTime) {
                // Overtime (red phase)
                timeLeft = now - sessionEndTime;
                phase = 'overtime';
                this.labelElement.textContent = 'وقت إضافي منذ';
                this.statusElement.textContent = 'الجلسة في الوقت الإضافي';
                this.updateColors('bg-red-50', 'border-red-200', 'text-red-900', 'text-red-700', 'text-red-600');
            } else {
                // Session ended
                timeLeft = 0;
                phase = 'ended';
                this.labelElement.textContent = 'انتهت الجلسة';
                this.displayElement.textContent = '00:00:00';
                this.statusElement.textContent = 'انتهت الجلسة';
                this.updateColors('bg-gray-50', 'border-gray-200', 'text-gray-900', 'text-gray-700', 'text-gray-600');
                return;
            }
            
            // Format and display time
            const hours = Math.floor(timeLeft / (1000 * 60 * 60));
            const minutes = Math.floor((timeLeft % (1000 * 60 * 60)) / (1000 * 60));
            const seconds = Math.floor((timeLeft % (1000 * 60)) / 1000);
            
            this.displayElement.textContent = 
                hours.toString().padStart(2, '0') + ':' +
                minutes.toString().padStart(2, '0') + ':' +
                seconds.toString().padStart(2, '0');
        }
        
        updateColors(bgClass, borderClass, titleClass, labelClass, statusClass) {
            const container = this.timerElement.closest('.bg-blue-50, .bg-orange-50, .bg-green-50, .bg-red-50, .bg-gray-50');
            if (container) {
                // Remove old color classes
                container.className = container.className.replace(/bg-(blue|orange|green|red|gray)-50/g, '');
                container.className = container.className.replace(/border-(blue|orange|green|red|gray)-200/g, '');
                
                // Add new color classes
                container.classList.add(bgClass, borderClass);
            }
            
            // Update text colors
            if (this.displayElement) {
                this.displayElement.className = this.displayElement.className.replace(/text-(blue|orange|green|red|gray)-900/g, '');
                this.displayElement.classList.add(titleClass);
            }
            if (this.labelElement) {
                this.labelElement.className = this.labelElement.className.replace(/text-(blue|orange|green|red|gray)-700/g, '');
                this.labelElement.classList.add(labelClass);
            }
            if (this.statusElement) {
                this.statusElement.className = this.statusElement.className.replace(/text-(blue|orange|green|red|gray)-600/g, '');
                this.statusElement.classList.add(statusClass);
            }
        }
        
        destroy() {
            if (this.interval) {
                clearInterval(this.interval);
            }
        }
    }
    
    // Initialize timer
    if (document.getElementById('meetingTimer')) {
        window.meetingTimer = new MeetingTimer();
        console.log('✅ Meeting timer started');
    }
    
    // Cleanup on page unload
    window.addEventListener('beforeunload', function() {
        if (window.meetingTimer) {
            window.meetingTimer.destroy();
        }
    });
});
</script>

<!-- Auto-Attendance Tracking System -->
<script>
    // Auto-Attendance Tracking System
    class AutoAttendanceTracker {
        constructor() {
            this.sessionId = {{ $session->id }};
            this.roomName = '{{ $session->meeting_room_name ?? "session-" . $session->id }}';
            this.csrfToken = '{{ csrf_token() }}';
            this.isTracking = false;
            this.attendanceStatus = null;
            
            // UI elements - FIX: Use correct selectors matching actual DOM
            this.statusElement = document.getElementById('attendance-status');
            this.iconElement = null; // Will be found dynamically
            this.textElement = this.statusElement?.querySelector('.attendance-text');
            this.detailsElement = this.statusElement?.querySelector('.attendance-details');
            this.timeElement = this.statusElement?.querySelector('.attendance-time');
            this.dotElement = this.statusElement?.querySelector('.attendance-dot');
            
            // CRITICAL FIX: Initialize DOM elements, show loading state initially
            if (this.statusElement) {
                console.log('📊 Attendance tracker initialized - will load status shortly');
                // Show loading state initially (status will be loaded by DOMContentLoaded)
                this.updateAttendanceUI({
                    is_currently_in_meeting: false,
                    attendance_status: 'loading',
                    attendance_percentage: '...',
                    duration_minutes: '...'
                });
            }
        }
        
        /**
         * Load current attendance status
         * DISABLED: Attendance now handled by Livewire component via webhooks
         */
        async loadCurrentStatus() {
            console.log('ℹ️ Attendance status via Livewire - skipping API call');
            return; // DISABLED - Livewire component handles this now
        }
        
        /**
         * Record user joining the meeting
         */
        async recordJoin() {
            if (this.isTracking) {
                console.log('⚠️ Already tracking attendance, skipping duplicate join');
                return;
            }
            
            try {
                // DISABLED: Client-side attendance tracking - Now handled by LiveKit webhooks
                console.log('ℹ️ Attendance tracking via webhooks - No client-side join needed');

                // Simulate successful response for UI update
                const data = {
                    success: true,
                    message: 'الحضور يتم تتبعه تلقائياً',
                    attendance_status: {}
                };
                
                if (data.success) {
                    this.isTracking = true;
                    console.log('✅ Meeting join recorded successfully, updating UI...');
                    
                    if (data.attendance_status) {
                        this.updateAttendanceUI(data.attendance_status);
                    }
                    
                    this.showNotification('✅ ' + data.message, 'success');
                    
                    // CRITICAL FIX: Start periodic updates only when meeting join is successful
                    if (!this.updateInterval) {
                        console.log('🔄 Starting attendance tracking periodic updates...');
                        this.startPeriodicUpdates();
                    }
                    
                    // Immediately refresh attendance status
                    setTimeout(() => {
                        console.log('🔄 Refreshing attendance status after join...');
                        this.loadCurrentStatus();
                    }, 500);
                    
                } else {
                    this.showNotification('⚠️ ' + (data.message || 'فشل في تسجيل الحضور'), 'warning');
                    console.warn('Failed to record meeting join:', data);
                }
                
            } catch (error) {
                console.error('Error recording meeting join:', error);
                this.showNotification('❌ فشل في تسجيل دخولك للجلسة', 'error');
            }
        }
        
        /**
         * Record user leaving the meeting
         */
        async recordLeave() {
            if (!this.isTracking) return; // Only record leave if we recorded join
            
            try {
                console.log('🎯 Recording meeting leave...');
                
                const response = await fetch('/api/meetings/attendance/leave', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': this.csrfToken,
                        'X-Requested-With': 'XMLHttpRequest',
                    },
                    credentials: 'same-origin',
                    body: JSON.stringify({
                        session_id: this.sessionId,
                        session_type: window.sessionType || 'quran',
                        room_name: this.roomName,
                    }),
                });
                
                const data = await response.json();
                
                if (data.success) {
                    this.isTracking = false;
                    this.updateAttendanceUI(data.attendance_status);
                    this.showNotification('✅ ' + data.message, 'success');
                    
                    // CRITICAL FIX: Stop periodic updates when user leaves
                    this.stopPeriodicUpdates();
                    
                    console.log('✅ Meeting leave recorded successfully');
                } else {
                    this.showNotification('⚠️ ' + data.message, 'warning');
                    console.warn('Failed to record meeting leave:', data.message);
                }
                
            } catch (error) {
                console.error('Error recording meeting leave:', error);
                this.showNotification('❌ فشل في تسجيل خروجك من الجلسة', 'error');
            }
        }
        
        /**
         * Update attendance UI based on status data
         * @param {Object} statusData - Attendance status data from API
         */
        updateAttendanceUI(statusData) {
            console.log('📊 Updating attendance UI with data:', statusData);
            
            if (!this.statusElement || !this.textElement || !this.timeElement || !this.dotElement) {
                console.warn('⚠️ Attendance UI elements not found');
                return;
            }
            
            const {
                is_currently_in_meeting,
                attendance_status,
                attendance_percentage,
                duration_minutes,
                join_count,
                session_state,
                has_ever_joined,
                minutes_until_start
            } = statusData;
            
            let statusText = '';
            let timeText = '';
            let dotColor = 'bg-gray-400';
            let containerColor = 'from-gray-50 to-gray-100';
            let borderColor = 'border-gray-200';
            let iconClass = 'ri-user-line';
            
            // Handle different session states and attendance statuses
            if (session_state === 'scheduled' && attendance_status === 'not_started') {
                // Session hasn't started yet
                statusText = 'الجلسة لم تبدأ بعد';
                if (minutes_until_start && minutes_until_start > 0) {
                    timeText = `ستبدأ خلال ${minutes_until_start} دقيقة`;
                } else {
                    timeText = 'في انتظار البدء';
                }
                dotColor = 'bg-blue-400';
                containerColor = 'from-blue-50 to-indigo-50';
                borderColor = 'border-blue-200';
                iconClass = 'ri-time-line';
                
            } else if (session_state === 'completed') {
                // Session has ended - show final status
                if (attendance_status === 'not_attended' || (!has_ever_joined && duration_minutes === 0)) {
                    statusText = 'لم تحضر الجلسة';
                    timeText = 'الجلسة انتهت';
                    dotColor = 'bg-red-400';
                    containerColor = 'from-red-50 to-pink-50';
                    borderColor = 'border-red-200';
                    iconClass = 'ri-close-circle-line';
                    
                } else if (attendance_status === 'partial_attendance' || attendance_status === 'partial') {
                    statusText = 'حضور جزئي';
                    timeText = `حضرت ${duration_minutes} دقيقة (${attendance_percentage}%)`;
                    dotColor = 'bg-orange-400';
                    containerColor = 'from-orange-50 to-red-50';
                    borderColor = 'border-orange-200';
                    iconClass = 'ri-time-line';
                    
                } else if (attendance_status === 'present') {
                    statusText = 'حضرت الجلسة';
                    timeText = `${duration_minutes} دقيقة (${attendance_percentage}%)`;
                    dotColor = 'bg-green-400';
                    containerColor = 'from-green-50 to-emerald-50';
                    borderColor = 'border-green-200';
                    iconClass = 'ri-check-circle-line';
                    
                } else if (attendance_status === 'late') {
                    statusText = 'حضرت متأخراً';
                    timeText = `${duration_minutes} دقيقة (${attendance_percentage}%)`;
                    dotColor = 'bg-yellow-400';
                    containerColor = 'from-yellow-50 to-amber-50';
                    borderColor = 'border-yellow-200';
                    iconClass = 'ri-time-line';
                    
                } else {
                    statusText = 'الجلسة انتهت';
                    timeText = duration_minutes > 0 ? `حضرت ${duration_minutes} دقيقة` : 'لم تحضر';
                    dotColor = 'bg-gray-400';
                    containerColor = 'from-gray-50 to-gray-100';
                    borderColor = 'border-gray-200';
                    iconClass = 'ri-calendar-check-line';
                }
                
            } else if (is_currently_in_meeting) {
                // Currently in the meeting
                statusText = 'في الجلسة الآن';
                timeText = `${duration_minutes} دقيقة`;
                dotColor = 'bg-green-500 animate-pulse';
                containerColor = 'from-green-50 to-emerald-50';
                borderColor = 'border-green-200';
                iconClass = 'ri-live-line';
                
            } else if (attendance_status === 'not_joined_yet') {
                // Session is ongoing but user hasn't joined
                statusText = 'لم تنضم بعد';
                timeText = 'الجلسة جارية الآن';
                dotColor = 'bg-orange-400 animate-pulse';
                containerColor = 'from-orange-50 to-yellow-50';
                borderColor = 'border-orange-200';
                iconClass = 'ri-notification-line';
                
            } else if (duration_minutes > 0) {
                // User has attended but is not currently in meeting
                const statusLabels = {
                    'present': 'حاضر',
                    'late': 'متأخر',
                    'partial': 'حضور جزئي',
                    'absent': 'غائب'
                };
                
                statusText = statusLabels[attendance_status] || 'غير محدد';
                timeText = `${duration_minutes} دقيقة - انضم ${join_count} مرة`;
                
                if (attendance_status === 'present') {
                    dotColor = 'bg-green-400';
                    containerColor = 'from-green-50 to-emerald-50';
                    borderColor = 'border-green-200';
                    iconClass = 'ri-check-line';
                } else if (attendance_status === 'late') {
                    dotColor = 'bg-yellow-400';
                    containerColor = 'from-yellow-50 to-amber-50';
                    borderColor = 'border-yellow-200';
                    iconClass = 'ri-time-line';
                } else if (attendance_status === 'partial') {
                    dotColor = 'bg-orange-400';
                    containerColor = 'from-orange-50 to-red-50';
                    borderColor = 'border-orange-200';
                    iconClass = 'ri-time-line';
                }
                
            } else {
                // Default state
                statusText = 'لم تنضم بعد';
                timeText = '--';
                dotColor = 'bg-gray-400';
                containerColor = 'from-gray-50 to-gray-100';
                borderColor = 'border-gray-200';
                iconClass = 'ri-user-line';
            }
            
            // Update UI elements
            this.textElement.textContent = statusText;
            this.timeElement.textContent = timeText;
            
            // Update dot color
            this.dotElement.className = 'attendance-dot w-3 h-3 rounded-full transition-all duration-300 ' + dotColor;
            
            // Update container colors
            this.statusElement.className = `attendance-status bg-gradient-to-r ${containerColor} rounded-lg p-4 border ${borderColor} shadow-sm`;
            
            // Update icon if there's an icon element
            const iconElement = this.statusElement.querySelector('.attendance-icon');
            if (iconElement) {
                iconElement.className = `attendance-icon ${iconClass} text-lg`;
            }
            
            console.log('✅ Attendance UI updated successfully');
        }
        
        /**
         * Start periodic updates for real-time attendance tracking
         */
        startPeriodicUpdates() {
            // Update every 30 seconds for real-time tracking
            this.updateInterval = setInterval(() => {
                this.loadCurrentStatus();
            }, 30000);
        }
        
        /**
         * Stop periodic updates
         */
        stopPeriodicUpdates() {
            if (this.updateInterval) {
                clearInterval(this.updateInterval);
                this.updateInterval = null;
            }
        }
        
        /**
         * Show notification to user
         */
        showNotification(message, type = 'info') {
            // Create notification element
            const notification = document.createElement('div');
            notification.className = `fixed top-4 right-4 z-50 px-4 py-3 rounded-lg shadow-lg text-white text-sm max-w-sm transition-all duration-300 ${
                type === 'success' ? 'bg-green-600' : 
                type === 'warning' ? 'bg-yellow-600' : 
                type === 'error' ? 'bg-red-600' : 'bg-blue-600'
            }`;
            notification.textContent = message;
            notification.style.transform = 'translateX(100%)';
            notification.style.opacity = '0';
            
            // Add to page
            document.body.appendChild(notification);
            
            // Animate in
            setTimeout(() => {
                notification.style.transform = 'translateX(0)';
                notification.style.opacity = '1';
            }, 10);
            
            // Auto-remove after 5 seconds
            setTimeout(() => {
                notification.style.transform = 'translateX(100%)';
                notification.style.opacity = '0';
                setTimeout(() => {
                    if (notification.parentNode) {
                        notification.parentNode.removeChild(notification);
                    }
                }, 300);
            }, 5000);
        }
        
        /**
         * Hook into meeting events
         */
        hookIntoMeetingEvents(meeting) {
            console.log('🔗 Hooking into meeting events for attendance tracking...', meeting);
            
            if (!meeting) {
                console.warn('⚠️ No meeting object provided');
                return;
            }
            
            // Try to get room from different possible paths
            let room = null;
            if (meeting.connection && typeof meeting.connection.getRoom === 'function') {
                room = meeting.connection.getRoom();
            } else if (meeting.room) {
                room = meeting.room;
            } else if (meeting.connection && meeting.connection.room) {
                room = meeting.connection.room;
            }
            
            if (!room) {
                console.warn('⚠️ Room not available, trying to connect anyway...');
                // Fallback: try to record join immediately since user clicked to join
                setTimeout(() => {
                    console.log('🔄 Fallback: Recording join after timeout');
                    this.recordJoin();
                }, 2000);
                return;
            }
            
            console.log('✅ Room found:', room);
            
            // Check if already connected
            if (room.state === 'connected') {
                console.log('📡 Room already connected - recording join immediately');
                this.recordJoin();
            }
            
            // Listen for local participant connection
            room.on('connected', () => {
                console.log('📡 Connected to room - recording join');
                this.recordJoin();
            });
            
            // Listen for local participant disconnection
            room.on('disconnected', () => {
                console.log('📡 Disconnected from room - recording leave');
                this.recordLeave();
            });
            
            // Listen for connection state changes
            room.on('connectionStateChanged', (state) => {
                console.log('📡 Connection state changed:', state);
                
                if (state === 'connected') {
                    this.recordJoin();
                } else if (state === 'disconnected' || state === 'failed') {
                    this.recordLeave();
                }
            });
            
            console.log('✅ Attendance tracking hooked into meeting events');
        }
    }
    
    // Recording functionality for Interactive Courses only
    let recordingState = {
        isRecording: false,
        recordingId: null,
        startTime: null,
        sessionId: {{ $session->id ?? 'null' }}
    };
    
    function initializeRecordingControls() {
        console.log('🎥 Initializing recording controls for Interactive Course...');
        
        const recordingBtn = document.getElementById('toggleRecording');
        const recordingIcon = document.getElementById('recordingIcon');
        const recordingIndicator = document.getElementById('recordingIndicator');
        
        if (recordingBtn) {
            recordingBtn.addEventListener('click', toggleRecording);
            console.log('✅ Recording controls initialized');
        }
    }
    
    async function toggleRecording() {
        const recordingBtn = document.getElementById('toggleRecording');
        const recordingIcon = document.getElementById('recordingIcon');
        const recordingIndicator = document.getElementById('recordingIndicator');
        
        try {
            if (recordingState.isRecording) {
                // Stop recording
                console.log('🛑 Stopping recording...');
                await stopRecording();
                
                // Update UI
                recordingIcon.className = 'ri-record-circle-line text-xl';
                recordingIndicator.classList.add('hidden');
                recordingBtn.classList.remove('bg-red-600');
                recordingBtn.classList.add('bg-gray-600');
                recordingBtn.title = 'بدء تسجيل الدورة';
                
                showRecordingNotification('✅ تم إيقاف التسجيل وحفظه بنجاح', 'success');
                
            } else {
                // Start recording
                console.log('▶️ Starting recording...');
                await startRecording();
                
                // Update UI
                recordingIcon.className = 'ri-stop-circle-line text-xl';
                recordingIndicator.classList.remove('hidden');
                recordingBtn.classList.remove('bg-gray-600');
                recordingBtn.classList.add('bg-red-600');
                recordingBtn.title = 'إيقاف تسجيل الدورة';
                
                showRecordingNotification('🎥 بدأ تسجيل الدورة التفاعلية', 'success');
            }
        } catch (error) {
            console.error('❌ Recording error:', error);
            showRecordingNotification('❌ خطأ في التسجيل: ' + error.message, 'error');
        }
    }
    
    async function startRecording() {
        const response = await fetch('/api/interactive-courses/recording/start', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.getAttribute('content')
            },
            body: JSON.stringify({
                session_id: recordingState.sessionId,
                meeting_room: window.meeting?.roomName || 'unknown_room'
            })
        });
        
        if (!response.ok) {
            throw new Error('فشل في بدء التسجيل');
        }
        
        const data = await response.json();
        recordingState.isRecording = true;
        recordingState.recordingId = data.recording_id;
        recordingState.startTime = new Date();
        
        console.log('✅ Recording started:', data);
    }
    
    async function stopRecording() {
        if (!recordingState.recordingId) {
            throw new Error('لا يوجد تسجيل نشط');
        }
        
        const response = await fetch('/api/interactive-courses/recording/stop', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.getAttribute('content')
            },
            body: JSON.stringify({
                recording_id: recordingState.recordingId,
                session_id: recordingState.sessionId
            })
        });
        
        if (!response.ok) {
            throw new Error('فشل في إيقاف التسجيل');
        }
        
        const data = await response.json();
        recordingState.isRecording = false;
        recordingState.recordingId = null;
        recordingState.startTime = null;
        
        console.log('✅ Recording stopped:', data);
    }
    
    function showRecordingNotification(message, type = 'info') {
        // Create notification element
        const notification = document.createElement('div');
        notification.className = `fixed top-4 left-4 z-50 px-4 py-3 rounded-lg shadow-lg text-white text-sm max-w-sm transition-all duration-300 ${
            type === 'success' ? 'bg-green-600' : 
            type === 'error' ? 'bg-red-600' : 
            'bg-blue-600'
        }`;
        notification.textContent = message;
        
        // Add to DOM
        document.body.appendChild(notification);
        
        // Remove after 4 seconds
        setTimeout(() => {
            notification.style.opacity = '0';
            setTimeout(() => {
                document.body.removeChild(notification);
            }, 300);
        }, 4000);
    }
    
    // Initialize attendance tracker
    let attendanceTracker = null;
    document.addEventListener('DOMContentLoaded', () => {
        attendanceTracker = new AutoAttendanceTracker();
        // Make globally accessible for debugging
        window.attendanceTracker = attendanceTracker;
        
        // Initialize recording functionality (Interactive Courses only)
        @if($showRecording ?? false)
        initializeRecordingControls();
        @endif
        
        // CRITICAL FIX: Load initial status for students (especially for completed sessions)
        @if($userType === 'student')
            console.log('📊 Student detected - loading initial attendance status...');
            // Wait a moment for DOM to be fully ready, then load status
            setTimeout(() => {
                if (attendanceTracker) {
                    attendanceTracker.loadCurrentStatus();
                }
            }, 500);
        @endif
        
        // Hook into meeting events when meeting starts
        const originalButton = document.getElementById('startMeetingBtn');
        if (originalButton) {
            const originalOnClick = originalButton.onclick;
            originalButton.addEventListener('click', async function(e) {
                // Wait a bit for the meeting to initialize
                setTimeout(() => {
                    if (window.meeting && attendanceTracker) {
                        attendanceTracker.hookIntoMeetingEvents(window.meeting);
                    }
                }, 3000);
            });
        }
    });
    
    // Cleanup attendance tracking on page unload
    window.addEventListener('beforeunload', () => {
        if (attendanceTracker) {
            // Stop periodic updates
            attendanceTracker.stopPeriodicUpdates();
            
            if (attendanceTracker.isTracking) {
                // Send leave event synchronously (best effort)
                navigator.sendBeacon('/api/meetings/attendance/leave', JSON.stringify({
                    session_id: attendanceTracker.sessionId,
                    room_name: attendanceTracker.roomName,
                }));
            }
        }
    });
</script>

<!-- System Status Checker -->
<script>
document.addEventListener('DOMContentLoaded', function() {
    // System Status Checker Class
    class SystemStatusChecker {
        constructor() {
            this.init();
        }

        init() {
            this.checkCameraPermission();
            this.checkMicrophonePermission();
            this.checkNetworkStatus();
            this.checkBrowserCompatibility();
            this.setupEventListeners();
        }

        async checkCameraPermission() {
            try {
                const result = await navigator.permissions.query({ name: 'camera' });
                this.updatePermissionStatus('camera', result.state);
                
                result.addEventListener('change', () => {
                    this.updatePermissionStatus('camera', result.state);
                });
            } catch (error) {
                // Fallback: try to access camera directly
                try {
                    const stream = await navigator.mediaDevices.getUserMedia({ video: true });
                    this.updatePermissionStatus('camera', 'granted');
                    stream.getTracks().forEach(track => track.stop());
                } catch (err) {
                    this.updatePermissionStatus('camera', 'denied');
                }
            }
        }

        async checkMicrophonePermission() {
            try {
                const result = await navigator.permissions.query({ name: 'microphone' });
                this.updatePermissionStatus('mic', result.state);
                
                result.addEventListener('change', () => {
                    this.updatePermissionStatus('mic', result.state);
                });
            } catch (error) {
                // Fallback: try to access microphone directly
                try {
                    const stream = await navigator.mediaDevices.getUserMedia({ audio: true });
                    this.updatePermissionStatus('mic', 'granted');
                    stream.getTracks().forEach(track => track.stop());
                } catch (err) {
                    this.updatePermissionStatus('mic', 'denied');
                }
            }
        }

        updatePermissionStatus(type, state) {
            const icon = document.getElementById(`${type}-status-icon`);
            const text = document.getElementById(`${type}-status-text`);
            const button = document.getElementById(`${type}-permission-btn`);

            if (!icon || !text) return;

            // Remove existing classes
            icon.className = 'w-8 h-8 rounded-full flex items-center justify-center';
            text.className = 'text-xs';

            switch (state) {
                case 'granted':
                    icon.classList.add('bg-green-100');
                    icon.innerHTML = '<i class="ri-check-line text-green-600"></i>';
                    text.classList.add('text-green-600');
                    text.textContent = 'مسموح';
                    if (button) button.classList.add('hidden');
                    break;
                case 'denied':
                    icon.classList.add('bg-red-100');
                    icon.innerHTML = '<i class="ri-close-line text-red-600"></i>';
                    text.classList.add('text-red-600');
                    text.textContent = 'مرفوض';
                    if (button) button.classList.remove('hidden');
                    break;
                case 'prompt':
                    icon.classList.add('bg-yellow-100');
                    icon.innerHTML = '<i class="ri-question-line text-yellow-600"></i>';
                    text.classList.add('text-yellow-600');
                    text.textContent = 'يحتاج إذن';
                    if (button) button.classList.remove('hidden');
                    break;
                default:
                    icon.classList.add('bg-gray-100');
                    icon.innerHTML = `<i class="ri-${type === 'camera' ? 'camera' : 'mic'}-line text-gray-400"></i>`;
                    text.classList.add('text-gray-600');
                    text.textContent = 'غير معروف';
                    if (button) button.classList.add('hidden');
            }
        }

        checkNetworkStatus() {
            const icon = document.getElementById('network-status-icon');
            const text = document.getElementById('network-status-text');
            const speed = document.getElementById('network-speed');

            if (!icon || !text) return;

            const updateNetworkStatus = () => {
                if (navigator.onLine) {
                    icon.className = 'w-8 h-8 rounded-full flex items-center justify-center bg-green-100';
                    icon.innerHTML = '<i class="ri-wifi-line text-green-600"></i>';
                    text.className = 'text-xs text-green-600';
                    text.textContent = 'متصل';
                    
                    // Check connection speed if available
                    if (navigator.connection) {
                        const connection = navigator.connection;
                        const speedText = connection.effectiveType || connection.type || 'غير معروف';
                        if (speed) speed.textContent = speedText;
                    }
                } else {
                    icon.className = 'w-8 h-8 rounded-full flex items-center justify-center bg-red-100';
                    icon.innerHTML = '<i class="ri-wifi-off-line text-red-600"></i>';
                    text.className = 'text-xs text-red-600';
                    text.textContent = 'غير متصل';
                    if (speed) speed.textContent = '';
                }
            };

            // Initial check
            updateNetworkStatus();

            // Listen for network changes
            window.addEventListener('online', updateNetworkStatus);
            window.addEventListener('offline', updateNetworkStatus);

            // Check connection speed changes
            if (navigator.connection) {
                navigator.connection.addEventListener('change', updateNetworkStatus);
            }
        }

        checkBrowserCompatibility() {
            const icon = document.getElementById('browser-status-icon');
            const text = document.getElementById('browser-status-text');

            if (!icon || !text) return;

            // Check for required APIs
            const hasMediaDevices = !!navigator.mediaDevices;
            const hasGetUserMedia = hasMediaDevices && !!navigator.mediaDevices.getUserMedia;
            const hasWebRTC = !!(window.RTCPeerConnection || window.webkitRTCPeerConnection);
            const hasPermissions = !!navigator.permissions;

            const isCompatible = hasMediaDevices && hasGetUserMedia && hasWebRTC;

            if (isCompatible) {
                icon.className = 'w-8 h-8 rounded-full flex items-center justify-center bg-green-100';
                icon.innerHTML = '<i class="ri-check-line text-green-600"></i>';
                text.className = 'text-xs text-green-600';
                text.textContent = 'متوافق';
            } else {
                icon.className = 'w-8 h-8 rounded-full flex items-center justify-center bg-red-100';
                icon.innerHTML = '<i class="ri-error-warning-line text-red-600"></i>';
                text.className = 'text-xs text-red-600';
                text.textContent = 'غير متوافق';
            }
        }

        setupEventListeners() {
            // Camera permission button
            const cameraBtn = document.getElementById('camera-permission-btn');
            if (cameraBtn) {
                cameraBtn.addEventListener('click', async () => {
                    try {
                        const stream = await navigator.mediaDevices.getUserMedia({ video: true });
                        this.updatePermissionStatus('camera', 'granted');
                        stream.getTracks().forEach(track => track.stop());
                    } catch (error) {
                        this.updatePermissionStatus('camera', 'denied');
                    }
                });
            }

            // Microphone permission button
            const micBtn = document.getElementById('mic-permission-btn');
            if (micBtn) {
                micBtn.addEventListener('click', async () => {
                    try {
                        const stream = await navigator.mediaDevices.getUserMedia({ audio: true });
                        this.updatePermissionStatus('mic', 'granted');
                        stream.getTracks().forEach(track => track.stop());
                    } catch (error) {
                        this.updatePermissionStatus('mic', 'denied');
                    }
                });
            }
        }
    }

    // Initialize system status checker
    const systemStatusChecker = new SystemStatusChecker();
    window.systemStatusChecker = systemStatusChecker; // Make globally accessible
});
</script>