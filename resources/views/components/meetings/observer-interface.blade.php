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

            {{-- Waiting for participants --}}
            <div id="observer-waiting" class="hidden flex flex-col items-center py-8">
                <i class="ri-user-search-line text-6xl text-amber-400 mb-4"></i>
                <p class="text-gray-700 text-lg font-semibold mb-2">{{ __('supervisor.observation.waiting_for_participants') }}</p>
                <p class="text-gray-500 text-sm mb-4">{{ __('supervisor.observation.waiting_auto_retry') }}</p>
                <div class="flex items-center gap-2 text-amber-600">
                    <i class="ri-loader-4-line animate-spin"></i>
                    <span class="text-sm">{{ __('supervisor.observation.checking_room') }}</span>
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
                    <div id="meetingTimerDot" class="w-2 h-2 bg-orange-400 rounded-full animate-pulse"></div>
                    <span id="meetingTimer" class="text-white font-bold">00:00</span>
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

    window.ITQAN_ROLE_CONFIG = @json(\App\Enums\UserType::meetingDisplayConfigMap());
    window.getRoleConfig = window.getRoleConfig || function (userType) {
        var cfg = window.ITQAN_ROLE_CONFIG || {};
        return cfg[userType] || cfg.student || { bg: 'bg-blue-100', text: 'text-blue-700' };
    };
    // `t()` is the translation helper LiveKitParticipants reads for role labels
    // and badges. Outside the regular meeting JS bundle it isn't defined, so we
    // resolve via window.meetingTranslations or fall back to the key.
    window.t = window.t || function (key) {
        var parts = String(key).split('.');
        var node = window.meetingTranslations || {};
        for (var i = 0; i < parts.length; i++) {
            if (node && parts[i] in node) node = node[parts[i]];
            else return key;
        }
        return typeof node === 'string' ? node : key;
    };
    window.meetingTranslations = window.meetingTranslations || {
        participants: {
            you: @json(__('meetings.participants.you')),
            teacher: @json(__('meetings.participants.teacher')),
            student: @json(__('meetings.participants.student')),
            admin: @json(__('meetings.participants.admin')),
            supervisor: @json(__('meetings.participants.supervisor')),
            participant: @json(__('meetings.participants.participant')),
        }
    };

    let room = null;
    let isConnected = false;
    let participantsManager = null;

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
    const waitingEl = document.getElementById('observer-waiting');
    let waitingRetryTimer = null;

    function showState(state) {
        idleEl.classList.add('hidden');
        connectingEl.classList.add('hidden');
        errorEl.classList.add('hidden');
        if (waitingEl) waitingEl.classList.add('hidden');
        videoGridEl.classList.add('hidden');
        statusEl.classList.remove('hidden');

        if (state === 'idle') {
            idleEl.classList.remove('hidden');
        } else if (state === 'connecting') {
            connectingEl.classList.remove('hidden');
        } else if (state === 'waiting') {
            if (waitingEl) waitingEl.classList.remove('hidden');
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

    function attachTrack(track, participant) {
        var participantId = participant.identity;
        if (track.kind === 'video') {
            var videoEl = document.getElementById('video-' + participantId);
            if (!videoEl) return;
            track.attach(videoEl);
            videoEl.style.display = 'block';
            videoEl.style.opacity = '1';
            videoEl.style.visibility = 'visible';
            var participantEl = document.getElementById('participant-' + participantId);
            var placeholder = participantEl && participantEl.querySelector('.absolute.inset-0.flex.flex-col');
            if (placeholder) {
                placeholder.style.opacity = '0';
                placeholder.style.visibility = 'hidden';
            }
        } else if (track.kind === 'audio') {
            var audioEl = track.attach();
            audioEl.style.display = 'none';
            var hostEl = document.getElementById('participant-' + participantId);
            (hostEl || document.body).appendChild(audioEl);
        }
    }

    function detachTrack(track, participant) {
        var participantId = participant.identity;
        if (track.kind === 'video') {
            track.detach();
            var videoEl = document.getElementById('video-' + participantId);
            if (videoEl) {
                videoEl.style.display = 'none';
                videoEl.style.opacity = '0';
                videoEl.style.visibility = 'hidden';
            }
            var participantEl = document.getElementById('participant-' + participantId);
            var placeholder = participantEl && participantEl.querySelector('.absolute.inset-0.flex.flex-col');
            if (placeholder) {
                placeholder.style.opacity = '1';
                placeholder.style.visibility = 'visible';
            }
        } else {
            track.detach().forEach(function(el) { el.remove(); });
        }
    }

    async function loadScript(src) {
        return new Promise((resolve, reject) => {
            const s = document.createElement('script');
            s.src = src;
            s.onload = resolve;
            s.onerror = () => reject(new Error('Failed to load ' + src));
            document.head.appendChild(s);
        });
    }

    async function loadParticipantsModule() {
        if (typeof LiveKitParticipants !== 'undefined') return;
        return loadScript('{{ asset("js/livekit/participants.js") }}?v={{ filemtime(public_path("js/livekit/participants.js")) }}');
    }

    async function loadLiveKitSDK() {
        if (window.LiveKit) return;
        @php
            // Must be same-origin: third-party CDNs (jsDelivr) are blocked by some regional ISPs.
            $livekitSdkPath = 'js/livekit/livekit-client.umd.min.js';
        @endphp
        return new Promise((resolve, reject) => {
            const script = document.createElement('script');
            script.src = '{{ asset($livekitSdkPath) }}?v={{ filemtime(public_path($livekitSdkPath)) }}';
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
        if (waitingRetryTimer) { clearTimeout(waitingRetryTimer); waitingRetryTimer = null; }

        try {
            await Promise.all([loadLiveKitSDK(), loadParticipantsModule()]);
            const tokenData = await fetchObserverToken();

            // Room has no participants yet — wait and auto-retry
            if (tokenData.data && tokenData.data.waiting) {
                showState('waiting');
                waitingRetryTimer = setTimeout(() => connect(), 5000);
                return;
            }

            participantsManager = new LiveKitParticipants({
                videoGridId: 'observer-video-tiles',
                meetingConfig: { role: 'observer', userType: 'supervisor' },
                onParticipantAdded: () => updateVideoLayout(),
                onParticipantRemoved: () => updateVideoLayout(),
                onParticipantClick: () => {},
            });

            room = new window.LiveKit.Room({
                adaptiveStream: true,
                dynacast: true,
            });

            room.on(window.LiveKit.RoomEvent.ParticipantConnected, (participant) => {
                participantsManager.addParticipant(participant);
                updateParticipantCount();
                subscribeToParticipantTracks(participant);
            });

            room.on(window.LiveKit.RoomEvent.ParticipantDisconnected, (participant) => {
                participantsManager.removeParticipant(participant.identity);
                updateParticipantCount();
            });

            room.on(window.LiveKit.RoomEvent.TrackSubscribed, (track, publication, participant) => {
                attachTrack(track, participant);
            });

            room.on(window.LiveKit.RoomEvent.TrackUnsubscribed, (track, publication, participant) => {
                detachTrack(track, participant);
            });

            // Active speaker glow — same `ring-4 ring-blue-500 ring-opacity-75`
            // treatment regular meetings use, applied via the shared participants
            // module so observer tiles match the rest of the UI.
            room.on(window.LiveKit.RoomEvent.ActiveSpeakersChanged, (speakers) => {
                if (!participantsManager) return;
                participantsManager.highlightActiveSpeakers(speakers.map(s => s.identity));
            });

            room.on(window.LiveKit.RoomEvent.Disconnected, () => {
                isConnected = false;
                if (participantsManager) {
                    participantsManager.destroy();
                    participantsManager = null;
                }
                showState('idle');
            });

            await room.connect(tokenData.data.server_url, tokenData.data.access_token);

            isConnected = true;
            showState('connected');

            room.remoteParticipants.forEach((participant) => {
                participantsManager.addParticipant(participant);
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
        if (participantsManager) {
            participantsManager.destroy();
            participantsManager = null;
        }
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

    // Phase-driven progress bar colour (kept out of `onTick` so phase changes
    // re-paint immediately, not on the next tick).
    function applyObserverPhaseColor(phase) {
        var el = document.getElementById('observer-timer-progress');
        if (!el) return;
        var base = 'h-1.5 rounded-full transition-all duration-1000 ';
        if (phase === 'session') el.className = base + 'bg-green-500';
        else if (phase === 'overtime') el.className = base + 'bg-red-500';
        else if (phase === 'preparation') el.className = base + 'bg-yellow-500';
        else el.className = base + 'bg-blue-400';
    }

    var _lastObserverProgress = -1;
    function updateObserverProgress(timing) {
        if (timing.percentage === undefined) return;
        var pct = Math.min(timing.percentage, 100);
        if (pct === _lastObserverProgress) return;
        var el = document.getElementById('observer-timer-progress');
        if (!el) return;
        el.style.width = pct + '%';
        _lastObserverProgress = pct;
    }

    // SmartSessionTimer drives both the idle header and the in-meeting
    // counter (via `meetingTimerElementId: 'meetingTimer'`). It also feeds
    // `#meetingTimerDot` whose colour is hardcoded by the timer module.
    @if($session->scheduled_at)
    function initializeObserverSessionTimer() {
        var timerConfig = {
            sessionId: {{ $session->id }},
            scheduledAt: '{{ $session->scheduled_at->toISOString() }}',
            durationMinutes: {{ $session->duration_minutes ?? 30 }},
            preparationMinutes: {{ $preparationMinutes }},
            endingBufferMinutes: {{ $endingBufferMinutes }},
            timerElementId: 'observer-session-timer',
            phaseElementId: 'observer-timer-phase',
            displayElementId: 'observer-time-display',
            meetingTimerElementId: 'meetingTimer',
            onPhaseChange: function(newPhase) {
                applyObserverPhaseColor(newPhase);
            },
            onTick: function(timing) {
                updateObserverProgress(timing);
            }
        };

        if (typeof SmartSessionTimer !== 'undefined') {
            window.observerSessionTimer = new SmartSessionTimer(timerConfig);
        } else {
            loadScript('{{ asset("js/session-timer.js") }}?v={{ filemtime(public_path("js/session-timer.js")) }}')
                .then(function() {
                    if (typeof SmartSessionTimer !== 'undefined') {
                        window.observerSessionTimer = new SmartSessionTimer(timerConfig);
                    }
                })
                .catch(function() {});
        }
    }

    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', initializeObserverSessionTimer);
    } else {
        initializeObserverSessionTimer();
    }
    @endif
})();
</script>
