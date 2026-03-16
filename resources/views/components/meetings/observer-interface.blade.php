@props(['session', 'sessionType'])

@php
    $observerTokenUrl = route('api.meetings.observer-token', [
        'sessionType' => $sessionType,
        'sessionId' => $session->id,
    ]);
    $academySettings = current_academy();
    $preparationMinutes = $academySettings?->default_preparation_minutes ?? 10;
    $endingBufferMinutes = $academySettings?->default_buffer_minutes ?? 5;
@endphp

{{-- Meeting Observer Container --}}
<div id="observer-meeting-container" class="rounded-xl overflow-hidden">

    {{-- Connection State (idle / connecting / error) — Light style matching normal meeting join view --}}
    <div id="observer-status" class="bg-white rounded-xl shadow-sm border border-gray-200 overflow-hidden">

        {{-- Header bar (matches livekit-interface session-status-header) --}}
        <div class="bg-gradient-to-r from-blue-50 to-indigo-50 px-6 py-4 border-b border-gray-100">
            <div class="flex items-center justify-between">
                <div class="flex items-center gap-3">
                    <h2 class="text-lg font-bold text-gray-900 flex items-center gap-2">
                        <i class="ri-eye-line text-blue-600"></i>
                        {{ __('supervisor.observation.observer_mode') }}
                    </h2>
                </div>
                {{-- Timer (same as livekit-interface, driven by SmartSessionTimer) --}}
                @if($session->scheduled_at)
                <div class="session-timer text-start" id="observer-session-timer" data-phase="not_started">
                    <div class="flex items-center gap-2 text-sm">
                        <span id="observer-timer-phase" class="phase-label font-medium">{{ __('meetings.timer.waiting_session') }}</span>
                        <span class="text-gray-400">|</span>
                        <span id="observer-time-display" class="time-display font-mono font-bold text-lg">--:--</span>
                    </div>
                    <div class="w-full bg-gray-200 rounded-full h-1.5 mt-2">
                        <div id="observer-timer-progress" class="h-1.5 rounded-full transition-all duration-1000" style="width: 0%"></div>
                    </div>
                </div>
                @endif
            </div>
        </div>

        {{-- Main Content Area --}}
        <div class="p-6">
            {{-- Connecting --}}
            <div id="observer-connecting" class="hidden flex flex-col items-center py-8">
                <div class="animate-spin rounded-full h-12 w-12 border-b-2 border-blue-500 mb-4 mx-auto"></div>
                <p class="text-gray-600 text-lg">{{ __('supervisor.observation.connecting') }}</p>
            </div>

            {{-- Idle / Ready --}}
            <div id="observer-idle" class="flex flex-col items-center py-6">
                <div class="join-action-area text-center">
                    <button
                        id="observer-join-btn"
                        class="cursor-pointer bg-blue-600 hover:bg-blue-700 text-white px-8 py-4 rounded-xl font-semibold transition-all duration-300 flex items-center gap-3 mx-auto min-w-[240px] justify-center shadow-lg transform hover:scale-105"
                    >
                        <i class="ri-eye-line text-xl"></i>
                        <span class="text-lg">{{ __('supervisor.observation.start_observation') }}</span>
                    </button>
                    <div class="status-message mt-4 bg-gray-50 rounded-lg p-3">
                        <p class="text-gray-700 text-sm font-medium">{{ __('supervisor.observation.observer_description') }}</p>
                    </div>
                </div>
            </div>

            {{-- Error --}}
            <div id="observer-error" class="hidden flex flex-col items-center py-8">
                <i class="ri-error-warning-line text-6xl text-red-400 mb-4"></i>
                <p class="text-red-600 text-lg mb-4" id="observer-error-message"></p>
                <button
                    id="observer-retry-btn"
                    class="cursor-pointer inline-flex items-center gap-2 px-4 py-2 bg-gray-100 hover:bg-gray-200 text-gray-700 rounded-lg transition-colors"
                >
                    <i class="ri-refresh-line"></i>
                    {{ __('supervisor.observation.retry') }}
                </button>
            </div>
        </div>

    </div>

    {{-- Video Grid (hidden until connected) --}}
    <div id="observer-video-grid" class="hidden flex flex-col bg-gray-900 rounded-xl overflow-hidden" style="min-height: 700px;">

        {{-- Top Bar (matches livekit-interface gradient bar) --}}
        <div class="bg-gradient-to-r from-blue-500 via-blue-600 to-blue-700 text-white px-4 py-3 flex items-center justify-between text-sm font-medium shadow-lg shrink-0">
            {{-- Left: Participant count + Timer --}}
            <div class="flex items-center gap-4 sm:gap-8">
                <div class="flex items-center gap-2">
                    <i class="ri-group-line text-lg text-white"></i>
                    <span id="observer-participant-count" class="text-white font-semibold">0</span>
                    <span class="text-white">{{ __('meetings.info.participant') }}</span>
                </div>
                <div class="flex items-center gap-2 font-mono">
                    <div id="observer-timer-dot" class="w-2 h-2 bg-orange-400 rounded-full animate-pulse"></div>
                    <span id="observer-timer" class="text-white font-bold">00:00</span>
                </div>
            </div>
            {{-- Right: Fullscreen button --}}
            <button id="observer-fullscreen-btn"
                aria-label="{{ __('meetings.info.fullscreen') }}"
                class="cursor-pointer bg-black bg-opacity-20 hover:bg-opacity-30 text-white px-3 py-2 rounded-lg transition-all duration-200 flex items-center gap-2 text-sm font-medium hover:scale-105 focus:outline-none focus:ring-2 focus:ring-white focus:ring-opacity-50">
                <i id="observer-fullscreen-icon" class="ri-fullscreen-line text-lg text-white"></i>
                <span id="observer-fullscreen-text" class="hidden sm:inline">{{ __('meetings.info.fullscreen') }}</span>
            </button>
        </div>

        {{-- Video Tiles Container --}}
        <div id="observer-video-tiles" class="flex-1 grid gap-2 p-2">
            {{-- Video tiles injected dynamically --}}
        </div>

        {{-- Bottom Bar (matches control-bar style) --}}
        <div id="observer-bottom-bar" class="bg-gray-800 border-t border-gray-700 shadow-lg shrink-0">
            <div class="px-4 py-4 flex items-center justify-center gap-4">
                {{-- Observer mode badge --}}
                <span class="inline-flex items-center gap-1.5 px-3 py-1 rounded-full text-xs font-medium bg-blue-600/80 text-white">
                    <span class="w-2 h-2 rounded-full bg-green-400 animate-pulse"></span>
                    {{ __('supervisor.observation.observing') }}
                </span>
                {{-- Leave button (same style as control-bar leave button) --}}
                <button id="observer-leave-btn"
                    class="cursor-pointer shrink-0 w-12 h-12 sm:w-14 sm:h-14 rounded-full bg-red-600 hover:bg-red-700 text-white flex items-center justify-center transition-all duration-200 hover:scale-105 focus:outline-none focus:ring-2 focus:ring-red-500 active:scale-95"
                    aria-label="{{ __('supervisor.observation.leave_observation') }}">
                    <i class="ri-logout-box-line text-xl"></i>
                </button>
            </div>
        </div>

    </div>
</div>

{{-- Fullscreen fix: ensure flex column fills entire screen --}}
<style>
    #observer-meeting-container:fullscreen {
        background: #111827;
        display: flex;
        flex-direction: column;
    }
    #observer-meeting-container:fullscreen #observer-video-grid {
        flex: 1;
        min-height: 0;
        height: 100%;
    }
    #observer-meeting-container:fullscreen #observer-video-tiles {
        flex: 1;
        min-height: 0;
    }
    #observer-meeting-container:fullscreen #observer-bottom-bar {
        position: relative;
        z-index: 30;
    }
</style>

{{-- Observer Meeting JavaScript --}}
<script>
(function() {
    'use strict';

    const CONFIG = {
        tokenUrl: @json($observerTokenUrl),
        csrfToken: @json(csrf_token()),
    };

    const STRINGS = {
        connecting: @json(__('supervisor.observation.connecting')),
        connected: @json(__('supervisor.observation.connected')),
        disconnected: @json(__('supervisor.observation.disconnected')),
        connection_failed: @json(__('supervisor.observation.connection_failed')),
        participants: @json(__('supervisor.observation.participants_count')),
    };

    // Role labels
    const ROLE_LABELS = {
        teacher: @json(__('meetings.participants.teacher')),
        student: @json(__('meetings.participants.student')),
        admin: @json(__('meetings.participants.admin')),
        supervisor: @json(__('supervisor.observation.role_supervisor')),
    };

    // Avatar color config (matches LiveKitParticipants.generateAvatarHtml)
    const AVATAR_TYPE_CONFIG = {
        quran_teacher:    { bg: 'bg-yellow-100', text: 'text-yellow-700' },
        academic_teacher: { bg: 'bg-violet-100', text: 'text-violet-700' },
        supervisor:       { bg: 'bg-orange-100', text: 'text-orange-700' },
        admin:            { bg: 'bg-red-100',    text: 'text-red-700'    },
        super_admin:      { bg: 'bg-red-100',    text: 'text-red-700'    },
        student:          { bg: 'bg-blue-100',   text: 'text-blue-700'   },
    };

    let room = null;
    let isConnected = false;

    // DOM Elements
    const statusEl = document.getElementById('observer-status');
    const connectingEl = document.getElementById('observer-connecting');
    const idleEl = document.getElementById('observer-idle');
    const errorEl = document.getElementById('observer-error');
    const errorMsgEl = document.getElementById('observer-error-message');
    const videoGridEl = document.getElementById('observer-video-grid');
    const videoTilesEl = document.getElementById('observer-video-tiles');
    const participantCountEl = document.getElementById('observer-participant-count');
    const joinBtn = document.getElementById('observer-join-btn');
    const leaveBtn = document.getElementById('observer-leave-btn');
    const retryBtn = document.getElementById('observer-retry-btn');

    function showState(state) {
        idleEl.classList.add('hidden');
        connectingEl.classList.add('hidden');
        errorEl.classList.add('hidden');
        videoGridEl.classList.add('hidden');
        statusEl.classList.remove('hidden');

        if (state === 'idle') {
            idleEl.classList.remove('hidden');
        } else if (state === 'connecting') {
            connectingEl.classList.remove('hidden');
        } else if (state === 'error') {
            errorEl.classList.remove('hidden');
        } else if (state === 'connected') {
            statusEl.classList.add('hidden');
            videoGridEl.classList.remove('hidden');
        }
    }

    function updateParticipantCount() {
        if (!room) return;
        participantCountEl.textContent = room.remoteParticipants.size;
    }

    function updateVideoLayout() {
        const tiles = videoTilesEl.children.length;
        const base = 'flex-1 grid gap-2 p-2';
        if (tiles <= 1) {
            videoTilesEl.className = base + ' grid-cols-1';
        } else if (tiles <= 2) {
            videoTilesEl.className = base + ' grid-cols-1 md:grid-cols-2';
        } else if (tiles <= 4) {
            videoTilesEl.className = base + ' grid-cols-2';
        } else {
            videoTilesEl.className = base + ' grid-cols-2 md:grid-cols-3';
        }
    }

    function getInitials(name) {
        return (name || '?').split(' ').map(function(n) { return n[0]; }).join('').toUpperCase().slice(0, 2);
    }

    // Shared avatar error handler (exposed on container for onerror access)
    window._observerAvatarError = function(imgEl, userType, name) {
        var cfg = AVATAR_TYPE_CONFIG[userType] || AVATAR_TYPE_CONFIG['student'];
        var initials = getInitials(name);
        imgEl.onerror = null;
        imgEl.parentElement.innerHTML = '<span class="font-semibold text-lg sm:text-xl ' + cfg.text + '">' + initials + '</span>';
    };

    function escapeAttr(str) {
        return (str || '').replace(/&/g, '&amp;').replace(/"/g, '&quot;').replace(/'/g, '&#39;').replace(/</g, '&lt;').replace(/>/g, '&gt;');
    }

    function generateAvatarHtml(avatarUrl, defaultAvatarUrl, userType, name) {
        var cfg = AVATAR_TYPE_CONFIG[userType] || AVATAR_TYPE_CONFIG['student'];
        var initials = getInitials(name);
        var safeName = escapeAttr(name);
        var safeUserType = escapeAttr(userType);
        var content = '';
        if (avatarUrl) {
            content = '<img src="' + escapeAttr(avatarUrl) + '" alt="' + safeName + '" class="w-full h-full object-cover"'
                + ' onerror="window._observerAvatarError(this,\'' + safeUserType + '\',\'' + safeName + '\')">';
        } else if (defaultAvatarUrl) {
            content = '<img src="' + escapeAttr(defaultAvatarUrl) + '" alt="' + safeName + '"'
                + ' class="absolute object-cover" style="width:120%;height:120%;top:0;left:50%;transform:translateX(-50%)"'
                + ' onerror="window._observerAvatarError(this,\'' + safeUserType + '\',\'' + safeName + '\')">';
        } else {
            content = '<span class="font-semibold text-lg sm:text-xl ' + cfg.text + '">' + initials + '</span>';
        }
        return '<div class="w-16 h-16 sm:w-20 sm:h-20 rounded-full overflow-hidden ' + cfg.bg + ' relative flex items-center justify-center">' + content + '</div>';
    }

    function getRoleLabel(metadata) {
        var userType = metadata.userType || '';
        if (userType === 'quran_teacher' || userType === 'academic_teacher' || metadata.role === 'teacher')
            return ROLE_LABELS.teacher;
        if (userType === 'supervisor' || userType === 'admin' || userType === 'super_admin')
            return ROLE_LABELS.supervisor;
        return ROLE_LABELS.student;
    }

    function createParticipantTile(participant) {
        var tileId = 'tile-' + participant.sid;
        if (document.getElementById(tileId)) return;

        var metadata = JSON.parse(participant.metadata || '{}');
        var name = metadata.name || participant.identity;
        var userType = metadata.userType || 'student';
        var avatarUrl = metadata.avatarUrl || null;
        var defaultAvatarUrl = metadata.defaultAvatarUrl || null;
        var isTeacher = metadata.role === 'teacher' || userType === 'quran_teacher' || userType === 'academic_teacher';
        var roleLabel = getRoleLabel(metadata);
        var avatarHtml = generateAvatarHtml(avatarUrl, defaultAvatarUrl, userType, name);

        var teacherBadge = isTeacher
            ? '<div class="absolute -top-1 -right-1 bg-green-600 text-white text-xs px-2 py-0.5 rounded-full font-bold shadow-lg z-10">' + ROLE_LABELS.teacher + '</div>'
            : '';

        var tile = document.createElement('div');
        tile.id = tileId;
        tile.className = 'relative bg-gray-800 rounded-lg overflow-hidden aspect-video flex items-center justify-center group';
        tile.innerHTML =
            '<div class="absolute inset-0 flex flex-col items-center justify-center bg-gradient-to-br from-blue-900 to-gray-800 z-10" data-placeholder>' +
                '<div class="flex flex-col items-center text-center">' +
                    '<div class="relative mb-3 shadow-lg transition-transform duration-200 group-hover:scale-110">' +
                        avatarHtml +
                        teacherBadge +
                    '</div>' +
                    '<p class="text-white text-sm sm:text-base font-medium px-2 text-center">' + escapeAttr(name) + '</p>' +
                    '<p class="text-gray-300 text-xs mt-1">' + roleLabel + '</p>' +
                '</div>' +
            '</div>' +
            '<div class="absolute bottom-0 inset-x-0 bg-gradient-to-t from-black/60 to-transparent p-2 flex items-center justify-between z-20">' +
                '<span class="text-white text-xs font-medium truncate max-w-[70%]">' + escapeAttr(name) + '</span>' +
            '</div>';

        videoTilesEl.appendChild(tile);
        updateVideoLayout();
    }

    function removeParticipantTile(participant) {
        var tile = document.getElementById('tile-' + participant.sid);
        if (tile) {
            tile.remove();
            updateVideoLayout();
        }
    }

    function attachTrack(track, participant) {
        var tile = document.getElementById('tile-' + participant.sid);
        if (!tile) return;

        if (track.kind === 'video') {
            var videoEl = track.attach();
            videoEl.className = 'absolute inset-0 w-full h-full object-cover z-0';
            tile.insertBefore(videoEl, tile.firstChild);
            var placeholder = tile.querySelector('[data-placeholder]');
            if (placeholder) placeholder.classList.add('hidden');
        } else if (track.kind === 'audio') {
            var audioEl = track.attach();
            audioEl.style.display = 'none';
            tile.appendChild(audioEl);
        }
    }

    function detachTrack(track, participant) {
        track.detach().forEach(function(el) { el.remove(); });
        var tile = document.getElementById('tile-' + participant.sid);
        if (tile && track.kind === 'video') {
            var placeholder = tile.querySelector('[data-placeholder]');
            if (placeholder) placeholder.classList.remove('hidden');
        }
    }

    async function loadLiveKitSDK() {
        if (window.LiveKit) return;
        return new Promise((resolve, reject) => {
            const script = document.createElement('script');
            script.src = 'https://cdn.jsdelivr.net/npm/livekit-client/dist/livekit-client.umd.min.js';
            script.crossOrigin = 'anonymous';
            script.onload = () => {
                setTimeout(() => {
                    const names = ['LiveKit', 'LiveKitClient', 'LivekitClient', 'livekit'];
                    for (const name of names) {
                        if (typeof window[name] !== 'undefined') {
                            window.LiveKit = window[name];
                            resolve();
                            return;
                        }
                    }
                    reject(new Error('LiveKit SDK not found'));
                }, 200);
            };
            script.onerror = () => reject(new Error('Failed to load LiveKit SDK'));
            document.head.appendChild(script);
        });
    }

    async function fetchObserverToken() {
        const response = await fetch(CONFIG.tokenUrl, {
            headers: {
                'Accept': 'application/json',
                'X-CSRF-TOKEN': CONFIG.csrfToken,
            },
            credentials: 'same-origin',
        });

        if (!response.ok) {
            const data = await response.json().catch(() => ({}));
            throw new Error(data.message || 'Failed to get observer token');
        }

        return response.json();
    }

    // Timer
    let timerInterval = null;
    let timerSeconds = 0;
    const timerEl = document.getElementById('observer-timer');

    function startTimer() {
        timerSeconds = 0;
        if (timerEl) timerEl.textContent = '00:00';
        timerInterval = setInterval(function() {
            timerSeconds++;
            var m = String(Math.floor(timerSeconds / 60)).padStart(2, '0');
            var s = String(timerSeconds % 60).padStart(2, '0');
            if (timerEl) timerEl.textContent = m + ':' + s;
        }, 1000);
    }

    function stopTimer() {
        if (timerInterval) { clearInterval(timerInterval); timerInterval = null; }
        timerSeconds = 0;
        if (timerEl) timerEl.textContent = '00:00';
    }

    // Fullscreen
    const fullscreenBtn = document.getElementById('observer-fullscreen-btn');
    const fullscreenIcon = document.getElementById('observer-fullscreen-icon');
    const fullscreenText = document.getElementById('observer-fullscreen-text');
    const containerEl = document.getElementById('observer-meeting-container');
    const fsEnterText = @json(__('meetings.info.fullscreen'));
    const fsExitText = @json(__('meetings.info.exit_fullscreen'));

    if (fullscreenBtn) {
        fullscreenBtn.addEventListener('click', function() {
            if (!document.fullscreenElement) {
                containerEl.requestFullscreen().catch(function() {});
            } else {
                document.exitFullscreen().catch(function() {});
            }
        });
    }

    document.addEventListener('fullscreenchange', function() {
        if (document.fullscreenElement) {
            if (fullscreenIcon) fullscreenIcon.className = 'ri-fullscreen-exit-line text-lg text-white';
            if (fullscreenText) fullscreenText.textContent = fsExitText;
        } else {
            if (fullscreenIcon) fullscreenIcon.className = 'ri-fullscreen-line text-lg text-white';
            if (fullscreenText) fullscreenText.textContent = fsEnterText;
        }
    });

    async function connect() {
        showState('connecting');

        try {
            await loadLiveKitSDK();
            const tokenData = await fetchObserverToken();

            room = new window.LiveKit.Room({
                adaptiveStream: true,
                dynacast: true,
            });

            room.on(window.LiveKit.RoomEvent.ParticipantConnected, (participant) => {
                createParticipantTile(participant);
                updateParticipantCount();
                subscribeToParticipantTracks(participant);
            });

            room.on(window.LiveKit.RoomEvent.ParticipantDisconnected, (participant) => {
                removeParticipantTile(participant);
                updateParticipantCount();
            });

            room.on(window.LiveKit.RoomEvent.TrackSubscribed, (track, publication, participant) => {
                attachTrack(track, participant);
            });

            room.on(window.LiveKit.RoomEvent.TrackUnsubscribed, (track, publication, participant) => {
                detachTrack(track, participant);
            });

            room.on(window.LiveKit.RoomEvent.Disconnected, () => {
                isConnected = false;
                videoTilesEl.innerHTML = '';
                stopTimer();
                showState('idle');
            });

            await room.connect(tokenData.data.server_url, tokenData.data.access_token);

            isConnected = true;
            showState('connected');
            startTimer();

            room.remoteParticipants.forEach((participant) => {
                createParticipantTile(participant);
                subscribeToParticipantTracks(participant);
            });

            updateParticipantCount();

        } catch (error) {
            console.error('Observer connection failed:', error);
            errorMsgEl.textContent = error.message || STRINGS.connection_failed;
            showState('error');
        }
    }

    function subscribeToParticipantTracks(participant) {
        participant.trackPublications.forEach((publication) => {
            if (publication.isSubscribed && publication.track) {
                attachTrack(publication.track, participant);
            }
        });
    }

    async function disconnect() {
        if (room) {
            await room.disconnect();
            room = null;
        }
        isConnected = false;
        videoTilesEl.innerHTML = '';
        stopTimer();
        showState('idle');
    }

    // Event Listeners
    joinBtn.addEventListener('click', connect);
    leaveBtn.addEventListener('click', disconnect);
    retryBtn.addEventListener('click', connect);

    // Cleanup on page leave
    window.addEventListener('beforeunload', () => {
        if (room) {
            room.disconnect();
        }
    });

    // Initial state
    showState('idle');

    // Initialize SmartSessionTimer for the idle header
    @if($session->scheduled_at)
    (function initObserverTimer() {
        function loadTimerScript() {
            return new Promise(function(resolve, reject) {
                if (typeof SmartSessionTimer !== 'undefined') { resolve(); return; }
                var s = document.createElement('script');
                s.src = '{{ asset("js/session-timer.js") }}?v={{ time() }}';
                s.onload = resolve;
                s.onerror = reject;
                document.head.appendChild(s);
            });
        }

        var timerConfig = {
            sessionId: {{ $session->id }},
            scheduledAt: '{{ $session->scheduled_at->toISOString() }}',
            durationMinutes: {{ $session->duration_minutes ?? 30 }},
            preparationMinutes: {{ $preparationMinutes }},
            endingBufferMinutes: {{ $endingBufferMinutes }},
            timerElementId: 'observer-session-timer',
            phaseElementId: 'observer-timer-phase',
            displayElementId: 'observer-time-display',
            onPhaseChange: function() {},
            onTick: function(timing) {
                var progressEl = document.getElementById('observer-timer-progress');
                if (progressEl && timing.progress !== undefined) {
                    progressEl.style.width = Math.min(timing.progress, 100) + '%';
                    // Color based on phase
                    var phase = timing.phase || '';
                    if (phase === 'session') {
                        progressEl.className = 'h-1.5 rounded-full transition-all duration-1000 bg-green-500';
                    } else if (phase === 'overtime') {
                        progressEl.className = 'h-1.5 rounded-full transition-all duration-1000 bg-red-500';
                    } else if (phase === 'preparation') {
                        progressEl.className = 'h-1.5 rounded-full transition-all duration-1000 bg-yellow-500';
                    } else {
                        progressEl.className = 'h-1.5 rounded-full transition-all duration-1000 bg-blue-400';
                    }
                }
            }
        };

        loadTimerScript().then(function() {
            if (typeof SmartSessionTimer !== 'undefined') {
                window.observerSessionTimer = new SmartSessionTimer(timerConfig);
            }
        }).catch(function() {});
    })();
    @endif
})();
</script>
