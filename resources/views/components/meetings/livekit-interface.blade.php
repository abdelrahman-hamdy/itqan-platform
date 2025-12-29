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
    // Detect session type
    $isAcademicSession = $session instanceof \App\Models\AcademicSession;
    $isInteractiveCourseSession = $session instanceof \App\Models\InteractiveCourseSession;

    // Get academy for this session - all session types use academy settings
    if ($isInteractiveCourseSession) {
        $academy = $session->course?->academy;
    } else {
        $academy = $session->academy;
    }

    // Get meeting timing from academy settings (single source of truth)
    $academySettings = $academy?->settings;
    $preparationMinutes = $academySettings?->default_preparation_minutes ?? 10;
    $endingBufferMinutes = $academySettings?->default_buffer_minutes ?? 5;
    $graceMinutes = $academySettings?->default_late_tolerance_minutes ?? 15;

    // Check if session has a meeting room (based on meeting_room_name or meeting_link)
    $hasMeetingRoom = !empty($session->meeting_room_name) || !empty($session->meeting_link);

    // Anyone can join when session is READY or ONGOING (students and teachers)
    // Both can initiate the meeting if room doesn't exist
    $canJoinMeeting = in_array($session->status, [
        App\Enums\SessionStatus::READY,
        App\Enums\SessionStatus::ONGOING
    ]);

    // ADDITIONAL FIX: Allow students to join even if marked absent, as long as session is active
    if ($userType === 'student' && in_array($session->status, [
        App\Enums\SessionStatus::ABSENT,
        App\Enums\SessionStatus::SCHEDULED
    ]) && $hasMeetingRoom) {
        // Students can join during preparation time or if session hasn't ended
        // Use academy timezone for "now" to ensure accurate comparisons
        $now = nowInAcademyTimezone();
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
            // Anyone can join/start the session
            $meetingMessage = 'الجلسة جاهزة - يمكنك الانضمام الآن';
            $buttonText = 'انضم للجلسة';
            $buttonClass = 'bg-green-600 hover:bg-green-700';
            $buttonDisabled = false;
            break;

        case App\Enums\SessionStatus::ONGOING:
            // Anyone can join the ongoing session
            $meetingMessage = 'الجلسة جارية الآن - انضم للمشاركة';
            $buttonText = 'انضمام للجلسة الجارية';
            $buttonClass = 'bg-orange-600 hover:bg-orange-700 animate-pulse';
            $buttonDisabled = false;
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

<!-- External Resources -->
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css" integrity="sha512-Avb2QiuDEEvB4bZJYdft2mNjVShBftLdPG8FJ0V7irTLQ8Uo0qcPxh4Plq7G5tGm0rU+1SPhVotteLpBERwTkw==" crossorigin="anonymous" referrerpolicy="no-referrer">
<!-- Meeting interface CSS is loaded via resources/css/meeting-interface.css through Vite -->

<!-- LiveKit JavaScript SDK - SPECIFIC WORKING VERSION -->
<script>
    // Loading LiveKit SDK

    function loadLiveKitScript() {
        return new Promise((resolve, reject) => {
            // Use official latest version from CDN
            const script = document.createElement('script');
            script.src = 'https://cdn.jsdelivr.net/npm/livekit-client/dist/livekit-client.umd.min.js';
            script.crossOrigin = 'anonymous';

            script.onload = () => {
                // LiveKit script loaded
                // Check for various possible global names
                setTimeout(() => {
                    const possibleNames = ['LiveKit', 'LiveKitClient', 'LivekitClient', 'livekit'];
                    let livekitFound = null;

                    for (const name of possibleNames) {
                        if (typeof window[name] !== 'undefined') {
                            livekitFound = window[name];
                            window.LiveKit = livekitFound; // Normalize to LiveKit
                            // LiveKit found
                            break;
                        }
                    }

                    if (livekitFound) {
                        // LiveKit SDK available
                        resolve();
                    } else {
                        reject(new Error('LiveKit global not found'));
                    }
                }, 200);
            };

            script.onerror = (error) => {
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
    // Track loading states
    let scriptsLoaded = {
        api: false,
        dataChannel: false,
        connection: false,
        tracks: false,
        participants: false,
        controls: false,
        layout: false,
        index: false
    };

    // Store interval IDs for cleanup (prevents memory leaks)
    let sessionStatusPollingInterval = null;

    function checkAllScriptsLoaded() {
        const allLoaded = Object.values(scriptsLoaded).every(loaded => loaded);
        if (allLoaded) {
            // Store session configuration
            window.sessionId = '{{ $session->id }}';
            window.sessionType = '{{ $isAcademicSession ? 'academic' : ($isInteractiveCourseSession ? 'interactive' : 'quran') }}';
            window.auth = {
                user: {
                    id: '{{ auth()->id() }}',
                    name: '{{ auth()->user()->first_name }} {{ auth()->user()->last_name }}'
                }
            };
        }
    }

    function loadScript(src, name) {
        return new Promise((resolve, reject) => {
            const script = document.createElement('script');
            script.src = src;
            script.onload = () => {
                scriptsLoaded[name] = true;
                checkAllScriptsLoaded();
                resolve();
            };
            script.onerror = reject;
            document.head.appendChild(script);
        });
    }

    // Load LiveKit scripts in order: API helper first, then session timer, then modules
    Promise.resolve()
        .then(() => loadScript('{{ asset("js/livekit/api.js") }}?v={{ time() }}', 'api'))
        .then(() => loadScript('{{ asset("js/session-timer.js") }}?v={{ time() }}', 'sessionTimer'))
        .then(() => loadScript('{{ asset("js/livekit/data-channel.js") }}?v={{ time() }}', 'dataChannel'))
        .then(() => loadScript('{{ asset("js/livekit/connection.js") }}?v={{ time() }}', 'connection'))
        .then(() => loadScript('{{ asset("js/livekit/tracks.js") }}?v={{ time() }}', 'tracks'))
        .then(() => loadScript('{{ asset("js/livekit/participants.js") }}?v={{ time() }}', 'participants'))
        .then(() => loadScript('{{ asset("js/livekit/controls.js") }}?v={{ time() }}', 'controls'))
        .then(() => loadScript('{{ asset("js/livekit/layout.js") }}?v={{ time() }}', 'layout'))
        .then(() => loadScript('{{ asset("js/livekit/index.js") }}?v={{ time() }}', 'index'))
        .catch(() => {
            // Silent fail - LiveKit scripts loading error
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
                updateSessionPhaseUI(newPhase);

                // AUTO-TERMINATION: End meeting when time expires
                if (newPhase === 'ended' && oldPhase !== 'ended') {
                    autoTerminateMeeting();
                }
            },
            
            onTick: function(timing) {
                updateSessionProgress(timing);
            }
        };

        if (typeof SmartSessionTimer !== 'undefined') {
            window.sessionTimer = new SmartSessionTimer(timerConfig);
        } else {
            loadScript('{{ asset("js/session-timer.js") }}', 'sessionTimer').then(() => {
                window.sessionTimer = new SmartSessionTimer(timerConfig);
            }).catch(() => {
                // Silent fail - timer will not work
            });
        }
    }

    // Initialize timer immediately
    initializeSessionTimer();
    @endif

    /**
     * Auto-terminate meeting when time expires
     */
    function autoTerminateMeeting() {
        // Show notification to user
        if (typeof showNotification !== 'undefined') {
            showNotification('⏰ انتهى وقت الجلسة وتم إنهاؤها تلقائياً', 'info');
        }

        // Disconnect from LiveKit room if connected
        if (window.room && window.room.state === 'connected') {
            try {
                window.room.disconnect();
            } catch {
                // Silent fail - room disconnect error
            }
        }

        // Record attendance leave if tracking
        if (window.attendanceTracker && window.attendanceTracker.isTracking) {
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

                // Stop timer when session ends
                if (window.sessionTimer) {
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

    // Disabled - AutoAttendanceTracker handles all attendance tracking now
    function initializeAttendanceTracking() {
        // No automatic API calls on page load
    }

    // Initialize session status polling for real-time updates
    function initializeSessionStatusPolling() {
        // Check session status every 10 seconds for real-time button updates
        checkSessionStatus();
        // Store interval ID for cleanup on page unload (prevents memory leak)
        sessionStatusPollingInterval = setInterval(checkSessionStatus, 10000);
    }

    // Stop session status polling (for cleanup)
    function stopSessionStatusPolling() {
        if (sessionStatusPollingInterval) {
            clearInterval(sessionStatusPollingInterval);
            sessionStatusPollingInterval = null;
        }
    }

    // Check initial session status (for when page loads on a completed session)
    function checkInitialSessionStatus() {
        // Get server-side session status from PHP
        const sessionStatus = '{{ is_object($session->status) && method_exists($session->status, 'value') ? $session->status->value : (is_object($session->status) ? $session->status->name : $session->status) }}';
        
        if (sessionStatus === 'completed') {
            
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
            })
            .catch(error => {
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

        // Enum constants for JavaScript
        const SessionStatus = {
            SCHEDULED: 'scheduled',
            READY: 'ready',
            ONGOING: 'ongoing',
            COMPLETED: 'completed',
            CANCELLED: 'cancelled',
            IN_PROGRESS: 'in_progress',
            LIVE: 'live'
        };

        // CRITICAL FIX: Stop timer when session is completed
        if (status === SessionStatus.COMPLETED && window.sessionTimer) {
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
            }
        } catch (error) {
            // Fallback: reload page if token refresh fails repeatedly
            if (window.tokenRefreshAttempts > 2) {
                window.location.reload();
            }
            window.tokenRefreshAttempts = (window.tokenRefreshAttempts || 0) + 1;
        }
    }

    // CRITICAL FIX: Disable old attendance tracking function
    // This function was causing attendance tracking on page load
    function updateAttendanceStatus() {
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
                'attended': 'حاضر',
                'present': 'حاضر',  // Legacy support
                'late': 'متأخر',
                'left': 'غادر مبكراً',
                'partial': 'غادر مبكراً',  // Legacy support
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

                const AttendanceStatus = {
                    ATTENDED: 'attended',
                    PRESENT: 'present',
                    LATE: 'late',
                    LEFT: 'left',
                    PARTIAL: 'partial',
                    ABSENT: 'absent'
                };

                if (isInMeeting) {
                    dotElement.classList.add('bg-green-500', 'animate-pulse');
                } else if (data.attendance_status === AttendanceStatus.ATTENDED || data.attendance_status === AttendanceStatus.PRESENT) {
                    dotElement.classList.add('bg-green-400');
                } else if (data.attendance_status === AttendanceStatus.LATE) {
                    dotElement.classList.add('bg-yellow-400');
                } else if (data.attendance_status === AttendanceStatus.LEFT || data.attendance_status === AttendanceStatus.PARTIAL) {
                    dotElement.classList.add('bg-orange-400');
                } else {
                    dotElement.classList.add('bg-gray-400');
                }
            }

        })
        .catch(error => {
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
            showNetworkStatus('غير متصل بالشبكة', 'offline');
        }

        function handleNetworkOnline() {
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
                    loadingOverlay.classList.add('fade-out');
                    setTimeout(() => {
                        loadingOverlay.style.display = 'none';
                        loadingOverlay.classList.remove('fade-out');
                    }, 500);
                }

                showNetworkStatus('متصل', 'online');
                reconnectAttempts = 0; // Reset on successful reconnection
                

            } catch (error) {
                
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
                            <span class="value font-medium text-gray-900">{{ $session->scheduled_at ? formatTimeArabic($session->scheduled_at) : 'غير محدد' }}</span>
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
                <!-- Attendance Status (Only for students) -->
                @if($userType === 'student')
                <x-meetings.attendance-status :sessionId="$session->id" />
                @endif

                <!-- System Status -->
                <x-meetings.system-status :userType="$userType" />

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
        showNotification('حدث خطأ أثناء إنهاء الجلسة', 'error');
    });
}

function showNotification(message, type = 'info', duration = 5000) {
    // Use unified toast system if available, fallback to basic notification
    if (window.toast) {
        window.toast.show({ type: type, message: message, duration: duration });
    } else {
        // Fallback for when toast system isn't loaded yet
    }
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
                <button id="fullscreenBtn" aria-label="ملء الشاشة" class="bg-black bg-opacity-20 hover:bg-opacity-30 text-white px-3 py-2 rounded-lg transition-all duration-200 flex items-center gap-2 text-sm font-medium hover:scale-105 focus:outline-none focus:ring-2 focus:ring-white focus:ring-opacity-50 z-1 relative">
                    <i id="fullscreenIcon" class="ri-fullscreen-line text-lg text-white" aria-hidden="true"></i>
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

                <x-meetings.sidebar-panels :userType="$userType" />
            </div>

            @php
                // Only show recording for Interactive Course sessions (Academic teachers only)
                $isInteractiveCourse = ($session->session_type === 'interactive_course' ||
                                      (isset($session->interactiveCourseSession) && $session->interactiveCourseSession) ||
                                      (method_exists($session, 'session_type') && $session->session_type === 'interactive_course'));
                $showRecording = $userType === 'academic_teacher' && $isInteractiveCourse;
            @endphp
            <x-meetings.control-bar :userType="$userType" :showRecording="$showRecording" />
        </div>
    </div>
</div>

<!-- Meeting Initialization Script -->
<script>

    // Initialize modular meeting system
    async function initializeMeeting() {

        try {
            // Wait for LiveKit SDK to load
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


            // Set up start button handler
            const startBtn = document.getElementById('startMeetingBtn');
            if (startBtn) {

                // Add click handler for modular system
                startBtn.addEventListener('click', async () => {

                    // CRITICAL FIX: Check if user is already in the meeting
                    if (window.meeting || startBtn.disabled) {
                        return;
                    }

                    // CRITICAL FIX: Check if already tracking attendance (user is in meeting)
                    if (attendanceTracker && attendanceTracker.isTracking) {
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
                        } else {
                        }

                        // Initialize meeting with new modular system
                        window.meeting = await initializeLiveKitMeeting(meetingConfig);


                        // CRITICAL FIX: Immediately record join when meeting starts
                        if (attendanceTracker) {
                            setTimeout(() => {
                                attendanceTracker.recordJoin();
                            }, 1000);
                        }

                        // Update button text
                        if (btnText) btnText.textContent = 'متصل';

                    } catch (error) {

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
                        window.toast?.error(`فشل في الاتصال بالجلسة: ${errorMessage}`);
                    }
                });

            } else {
            }


        } catch (error) {
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
        initializeMeeting();
    });

    // Fallback initialization on DOM ready
    document.addEventListener('DOMContentLoaded', function() {

        // Ensure initializeLiveKitMeeting is available
        if (typeof window.initializeLiveKitMeeting !== 'function') {
            return;
        }

    });

    // Cleanup on page unload
    window.addEventListener('beforeunload', async () => {
        // Stop session status polling (prevents memory leak)
        stopSessionStatusPolling();

        if (window.meeting && typeof window.meeting.destroy === 'function') {
            try {
                await window.meeting.destroy();
            } catch (error) {
            }
        } else if (window.destroyCurrentMeeting) {
            // Fallback cleanup
            try {
                await window.destroyCurrentMeeting();
            } catch (error) {
            }
        }
    });





</script>

<!-- Auto-join functionality removed - meetings now require manual start -->

<!-- Meeting Timer System -->
<script>
document.addEventListener('DOMContentLoaded', function() {
    // =====================================================
    // Session Starting Soon Notification (Uses Unified Toast)
    // Shows toast notification when session is starting soon
    // =====================================================
    @if($session->scheduled_at && $session->scheduled_at->isFuture() && $session->scheduled_at->diffInMinutes(now()) <= 15)
        @php
            $timeData = formatTimeRemaining($session->scheduled_at);
        @endphp
        @if(!$timeData['is_past'])
            // Use unified toast system for session starting notification
            if (window.toast) {
                window.toast.info('الجلسة ستبدأ خلال {{ $timeData['formatted'] }}', { duration: 8000 });
            }
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
            return; // DISABLED - Livewire component handles this now
        }
        
        /**
         * Record user joining the meeting
         */
        async recordJoin() {
            if (this.isTracking) {
                return;
            }
            
            try {
                // DISABLED: Client-side attendance tracking - Now handled by LiveKit webhooks

                // Simulate successful response for UI update
                const data = {
                    success: true,
                    message: 'الحضور يتم تتبعه تلقائياً',
                    attendance_status: {}
                };
                
                if (data.success) {
                    this.isTracking = true;
                    
                    if (data.attendance_status) {
                        this.updateAttendanceUI(data.attendance_status);
                    }
                    
                    this.showNotification('✅ ' + data.message, 'success');
                    
                    // CRITICAL FIX: Start periodic updates only when meeting join is successful
                    if (!this.updateInterval) {
                        this.startPeriodicUpdates();
                    }
                    
                    // Immediately refresh attendance status
                    setTimeout(() => {
                        this.loadCurrentStatus();
                    }, 500);
                    
                } else {
                    this.showNotification('⚠️ ' + (data.message || 'فشل في تسجيل الحضور'), 'warning');
                }
                
            } catch (error) {
                this.showNotification('❌ فشل في تسجيل دخولك للجلسة', 'error');
            }
        }
        
        /**
         * Record user leaving the meeting
         */
        async recordLeave() {
            if (!this.isTracking) return; // Only record leave if we recorded join
            
            try {
                
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
                    
                } else {
                    this.showNotification('⚠️ ' + data.message, 'warning');
                }
                
            } catch (error) {
                this.showNotification('❌ فشل في تسجيل خروجك من الجلسة', 'error');
            }
        }
        
        /**
         * Update attendance UI based on status data
         * @param {Object} statusData - Attendance status data from API
         */
        updateAttendanceUI(statusData) {
            
            if (!this.statusElement || !this.textElement || !this.timeElement || !this.dotElement) {
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
            const SessionStatus = {
                SCHEDULED: 'scheduled',
                COMPLETED: 'completed'
            };

            const AttendanceStatus = {
                ATTENDED: 'attended',
                PRESENT: 'present',
                LATE: 'late',
                LEFT: 'left',
                PARTIAL: 'partial',
                PARTIAL_ATTENDANCE: 'partial_attendance',
                NOT_ATTENDED: 'not_attended',
                NOT_JOINED_YET: 'not_joined_yet'
            };

            let iconClass = 'ri-user-line';

            // Handle different session states and attendance statuses
            if (session_state === SessionStatus.SCHEDULED && attendance_status === 'not_started') {
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
                
            } else if (session_state === SessionStatus.COMPLETED) {
                // Session has ended - show final status
                if (attendance_status === AttendanceStatus.NOT_ATTENDED || (!has_ever_joined && duration_minutes === 0)) {
                    statusText = 'لم تحضر الجلسة';
                    timeText = 'الجلسة انتهت';
                    dotColor = 'bg-red-400';
                    containerColor = 'from-red-50 to-pink-50';
                    borderColor = 'border-red-200';
                    iconClass = 'ri-close-circle-line';

                } else if (attendance_status === AttendanceStatus.LEFT || attendance_status === AttendanceStatus.PARTIAL_ATTENDANCE || attendance_status === AttendanceStatus.PARTIAL) {
                    statusText = 'غادر مبكراً';
                    timeText = `حضرت ${duration_minutes} دقيقة (${attendance_percentage}%)`;
                    dotColor = 'bg-orange-400';
                    containerColor = 'from-orange-50 to-red-50';
                    borderColor = 'border-orange-200';
                    iconClass = 'ri-logout-box-line';

                } else if (attendance_status === AttendanceStatus.ATTENDED || attendance_status === AttendanceStatus.PRESENT) {
                    statusText = 'حضرت الجلسة';
                    timeText = `${duration_minutes} دقيقة (${attendance_percentage}%)`;
                    dotColor = 'bg-green-400';
                    containerColor = 'from-green-50 to-emerald-50';
                    borderColor = 'border-green-200';
                    iconClass = 'ri-check-circle-line';

                } else if (attendance_status === AttendanceStatus.LATE) {
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
                
            } else if (attendance_status === AttendanceStatus.NOT_JOINED_YET) {
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
                    'attended': 'حاضر',
                    'present': 'حاضر',  // Legacy support
                    'late': 'متأخر',
                    'left': 'غادر مبكراً',
                    'partial': 'غادر مبكراً',  // Legacy support
                    'absent': 'غائب'
                };

                statusText = statusLabels[attendance_status] || 'غير محدد';
                timeText = `${duration_minutes} دقيقة - انضم ${join_count} مرة`;

                if (attendance_status === 'attended' || attendance_status === 'present') {
                    dotColor = 'bg-green-400';
                    containerColor = 'from-green-50 to-emerald-50';
                    borderColor = 'border-green-200';
                    iconClass = 'ri-check-line';
                } else if (attendance_status === 'late') {
                    dotColor = 'bg-yellow-400';
                    containerColor = 'from-yellow-50 to-amber-50';
                    borderColor = 'border-yellow-200';
                    iconClass = 'ri-time-line';
                } else if (attendance_status === 'left' || attendance_status === 'partial') {
                    dotColor = 'bg-orange-400';
                    containerColor = 'from-orange-50 to-red-50';
                    borderColor = 'border-orange-200';
                    iconClass = 'ri-logout-box-line';
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
         * Show notification to user using unified toast system
         */
        showNotification(message, type = 'info') {
            if (window.toast) {
                window.toast.show({ type: type, message: message });
            } else {
            }
        }
        
        /**
         * Hook into meeting events
         */
        hookIntoMeetingEvents(meeting) {
            
            if (!meeting) {
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
                // Fallback: try to record join immediately since user clicked to join
                setTimeout(() => {
                    this.recordJoin();
                }, 2000);
                return;
            }
            
            
            // Check if already connected
            if (room.state === 'connected') {
                this.recordJoin();
            }
            
            // Listen for local participant connection
            room.on('connected', () => {
                this.recordJoin();
            });
            
            // Listen for local participant disconnection
            room.on('disconnected', () => {
                this.recordLeave();
            });
            
            // Listen for connection state changes
            room.on('connectionStateChanged', (state) => {
                
                if (state === 'connected') {
                    this.recordJoin();
                } else if (state === 'disconnected' || state === 'failed') {
                    this.recordLeave();
                }
            });
            
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
        
        const recordingBtn = document.getElementById('toggleRecording');
        const recordingIcon = document.getElementById('recordingIcon');
        const recordingIndicator = document.getElementById('recordingIndicator');
        
        if (recordingBtn) {
            recordingBtn.addEventListener('click', toggleRecording);
        }
    }
    
    async function toggleRecording() {
        const recordingBtn = document.getElementById('toggleRecording');
        const recordingIcon = document.getElementById('recordingIcon');
        const recordingIndicator = document.getElementById('recordingIndicator');
        
        try {
            if (recordingState.isRecording) {
                // Stop recording
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
        
    }
    
    function showRecordingNotification(message, type = 'info') {
        // Use unified toast system
        if (window.toast) {
            window.toast.show({ type: type, message: message, duration: 4000 });
        } else {
        }
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