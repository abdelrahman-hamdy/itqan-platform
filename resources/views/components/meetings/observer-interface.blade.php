@props(['session', 'sessionType'])

@php
    $observerTokenUrl = route('api.meetings.observer-token', [
        'sessionType' => $sessionType,
        'sessionId' => $session->id,
    ]);
@endphp

{{-- Observer Mode Banner --}}
<div class="bg-blue-50 border border-blue-200 rounded-xl p-4 flex items-center gap-3 mb-4">
    <i class="ri-eye-line text-2xl text-blue-600 shrink-0"></i>
    <div>
        <h3 class="font-semibold text-blue-800">{{ __('supervisor.observation.observer_mode') }}</h3>
        <p class="text-sm text-blue-600">{{ __('supervisor.observation.observer_description') }}</p>
    </div>
</div>

{{-- Meeting Observer Container --}}
<div id="observer-meeting-container" class="bg-gray-900 rounded-xl overflow-hidden" style="min-height: 500px;">
    {{-- Connection State --}}
    <div id="observer-status" class="flex flex-col items-center justify-center h-full p-8 text-center" style="min-height: 500px;">
        <div id="observer-connecting" class="hidden">
            <div class="animate-spin rounded-full h-10 w-10 border-b-2 border-blue-400 mb-4 mx-auto"></div>
            <p class="text-gray-300 text-lg">{{ __('supervisor.observation.connecting') }}</p>
        </div>
        <div id="observer-idle" class="flex flex-col items-center">
            <i class="ri-video-camera-line text-6xl text-gray-500 mb-4"></i>
            <p class="text-gray-400 mb-6 text-lg">{{ __('supervisor.observation.ready_to_observe') }}</p>
            <button
                id="observer-join-btn"
                class="inline-flex items-center gap-2 px-6 py-3 bg-blue-600 hover:bg-blue-700 text-white font-medium rounded-lg transition-colors"
            >
                <i class="ri-eye-line text-lg"></i>
                {{ __('supervisor.observation.start_observation') }}
            </button>
        </div>
        <div id="observer-error" class="hidden">
            <i class="ri-error-warning-line text-6xl text-red-400 mb-4"></i>
            <p class="text-red-300 text-lg mb-4" id="observer-error-message"></p>
            <button
                id="observer-retry-btn"
                class="inline-flex items-center gap-2 px-4 py-2 bg-gray-700 hover:bg-gray-600 text-white rounded-lg transition-colors"
            >
                {{ __('supervisor.observation.retry') }}
            </button>
        </div>
    </div>

    {{-- Video Grid (hidden until connected) --}}
    <div id="observer-video-grid" class="hidden relative" style="min-height: 500px;">
        {{-- Header Bar --}}
        <div class="absolute top-0 inset-x-0 z-10 bg-gradient-to-b from-black/60 to-transparent p-4 flex items-center justify-between">
            <div class="flex items-center gap-3">
                <span class="inline-flex items-center gap-1.5 px-3 py-1 rounded-full text-xs font-medium bg-blue-600/80 text-white">
                    <span class="w-2 h-2 rounded-full bg-green-400 animate-pulse"></span>
                    {{ __('supervisor.observation.observing') }}
                </span>
                <span id="observer-participant-count" class="text-gray-300 text-sm"></span>
            </div>
            <button
                id="observer-leave-btn"
                class="inline-flex items-center gap-2 px-4 py-2 bg-red-600 hover:bg-red-700 text-white text-sm font-medium rounded-lg transition-colors"
            >
                <i class="ri-close-line"></i>
                {{ __('supervisor.observation.leave_observation') }}
            </button>
        </div>

        {{-- Video Tiles Container --}}
        <div id="observer-video-tiles" class="grid gap-2 p-2 pt-16" style="min-height: 500px;">
            {{-- Video tiles will be dynamically added here --}}
        </div>
    </div>
</div>

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
        const count = room.remoteParticipants.size;
        participantCountEl.textContent = STRINGS.participants + ': ' + count;
    }

    function updateVideoLayout() {
        const tiles = videoTilesEl.children.length;
        if (tiles <= 1) {
            videoTilesEl.className = 'grid grid-cols-1 gap-2 p-2 pt-16';
        } else if (tiles <= 2) {
            videoTilesEl.className = 'grid grid-cols-1 md:grid-cols-2 gap-2 p-2 pt-16';
        } else if (tiles <= 4) {
            videoTilesEl.className = 'grid grid-cols-2 gap-2 p-2 pt-16';
        } else {
            videoTilesEl.className = 'grid grid-cols-2 md:grid-cols-3 gap-2 p-2 pt-16';
        }
    }

    function createParticipantTile(participant) {
        const tileId = 'tile-' + participant.sid;
        if (document.getElementById(tileId)) return;

        const metadata = JSON.parse(participant.metadata || '{}');
        const name = metadata.name || participant.identity;
        const role = metadata.role || '';

        let roleBadge = '';
        const roleTeacher = @json(__('supervisor.observation.role_teacher'));
        const roleStudent = @json(__('supervisor.observation.role_student'));
        const roleAdmin = @json(__('supervisor.observation.role_admin'));

        if (role === 'teacher') {
            roleBadge = '<span class="px-2 py-0.5 rounded text-xs bg-green-600 text-white">' + roleTeacher + '</span>';
        } else if (role === 'student') {
            roleBadge = '<span class="px-2 py-0.5 rounded text-xs bg-blue-600 text-white">' + roleStudent + '</span>';
        } else if (role === 'admin' || role === 'observer') {
            roleBadge = '<span class="px-2 py-0.5 rounded text-xs bg-purple-600 text-white">' + roleAdmin + '</span>';
        }

        const tile = document.createElement('div');
        tile.id = tileId;
        tile.className = 'relative bg-gray-800 rounded-lg overflow-hidden aspect-video flex items-center justify-center';
        tile.innerHTML = `
            <div class="absolute inset-0 flex items-center justify-center" data-placeholder>
                <div class="text-center">
                    <div class="w-16 h-16 mx-auto mb-2 rounded-full bg-gray-700 flex items-center justify-center text-2xl font-bold text-gray-400">
                        ${(name || '?').charAt(0)}
                    </div>
                    <p class="text-gray-400 text-sm">${name}</p>
                </div>
            </div>
            <div class="absolute bottom-0 inset-x-0 bg-gradient-to-t from-black/60 to-transparent p-2 flex items-center justify-between">
                <span class="text-white text-xs font-medium truncate max-w-[70%]">${name}</span>
                ${roleBadge}
            </div>
        `;

        videoTilesEl.appendChild(tile);
        updateVideoLayout();
    }

    function removeParticipantTile(participant) {
        const tile = document.getElementById('tile-' + participant.sid);
        if (tile) {
            tile.remove();
            updateVideoLayout();
        }
    }

    function attachTrack(track, participant) {
        const tile = document.getElementById('tile-' + participant.sid);
        if (!tile) return;

        if (track.kind === 'video') {
            const videoEl = track.attach();
            videoEl.className = 'absolute inset-0 w-full h-full object-cover';
            tile.insertBefore(videoEl, tile.firstChild);
            const placeholder = tile.querySelector('[data-placeholder]');
            if (placeholder) placeholder.classList.add('hidden');
        } else if (track.kind === 'audio') {
            const audioEl = track.attach();
            audioEl.style.display = 'none';
            tile.appendChild(audioEl);
        }
    }

    function detachTrack(track, participant) {
        track.detach().forEach(el => el.remove());
        const tile = document.getElementById('tile-' + participant.sid);
        if (tile && track.kind === 'video') {
            const placeholder = tile.querySelector('[data-placeholder]');
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
                showState('idle');
            });

            await room.connect(tokenData.data.server_url, tokenData.data.access_token);

            isConnected = true;
            showState('connected');

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
})();
</script>
