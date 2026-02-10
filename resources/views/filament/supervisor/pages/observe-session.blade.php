<x-filament-panels::page>
    {{-- Observer Mode Banner --}}
    <div class="bg-blue-50 dark:bg-blue-900/20 border border-blue-200 dark:border-blue-700 rounded-xl p-4 flex items-center gap-3">
        <x-heroicon-o-eye class="w-6 h-6 text-blue-600 dark:text-blue-400 shrink-0" />
        <div>
            <h3 class="font-semibold text-blue-800 dark:text-blue-200">
                {{ __('supervisor.observation.observer_mode') }} - {{ $observerRoleLabel }}
            </h3>
            <p class="text-sm text-blue-600 dark:text-blue-400">
                {{ __('supervisor.observation.observer_description') }}
            </p>
        </div>
    </div>

    {{-- Session Info Card --}}
    <div class="bg-white dark:bg-gray-800 rounded-xl shadow-sm border border-gray-200 dark:border-gray-700 p-4">
        <div class="grid grid-cols-1 md:grid-cols-4 gap-4">
            <div>
                <span class="text-sm text-gray-500 dark:text-gray-400">{{ __('supervisor.observation.session_title') }}</span>
                <p class="font-medium text-gray-900 dark:text-gray-100">{{ $sessionTitle }}</p>
            </div>
            <div>
                <span class="text-sm text-gray-500 dark:text-gray-400">{{ __('supervisor.observation.teacher') }}</span>
                <p class="font-medium text-gray-900 dark:text-gray-100">{{ $teacherName }}</p>
            </div>
            <div>
                <span class="text-sm text-gray-500 dark:text-gray-400">{{ __('supervisor.observation.student_info') }}</span>
                <p class="font-medium text-gray-900 dark:text-gray-100">{{ $studentInfo }}</p>
            </div>
            <div>
                <span class="text-sm text-gray-500 dark:text-gray-400">{{ __('supervisor.observation.status') }}</span>
                <p class="font-medium text-gray-900 dark:text-gray-100">
                    <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-green-100 text-green-800 dark:bg-green-800 dark:text-green-100">
                        {{ $sessionStatus }}
                    </span>
                </p>
            </div>
        </div>
    </div>

    {{-- Meeting Observer Container --}}
    <div id="observer-meeting-container" class="bg-gray-900 rounded-xl overflow-hidden" style="min-height: 500px;">
        {{-- Connection State --}}
        <div id="observer-status" class="flex flex-col items-center justify-center h-full p-8 text-center" style="min-height: 500px;">
            <div id="observer-connecting" class="hidden">
                <x-filament::loading-indicator class="h-10 w-10 text-blue-400 mb-4" />
                <p class="text-gray-300 text-lg">{{ __('supervisor.observation.connecting') }}</p>
            </div>
            <div id="observer-idle" class="flex flex-col items-center">
                <x-heroicon-o-video-camera class="w-16 h-16 text-gray-500 mb-4" />
                <p class="text-gray-400 mb-6 text-lg">{{ __('supervisor.observation.ready_to_observe') }}</p>
                <button
                    id="observer-join-btn"
                    class="inline-flex items-center gap-2 px-6 py-3 bg-blue-600 hover:bg-blue-700 text-white font-medium rounded-lg transition-colors"
                >
                    <x-heroicon-o-eye class="w-5 h-5" />
                    {{ __('supervisor.observation.start_observation') }}
                </button>
            </div>
            <div id="observer-error" class="hidden">
                <x-heroicon-o-exclamation-triangle class="w-16 h-16 text-red-400 mb-4 mx-auto" />
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
                    <x-heroicon-o-x-mark class="w-4 h-4" />
                    {{ __('supervisor.observation.leave_observation') }}
                </button>
            </div>

            {{-- Video Tiles Container --}}
            <div id="observer-video-tiles" class="grid gap-2 p-2 pt-16" style="min-height: 500px;">
                {{-- Video tiles will be dynamically added here --}}
            </div>
        </div>
    </div>

    {{-- Supervisor Notes --}}
    <div class="bg-white dark:bg-gray-800 rounded-xl shadow-sm border border-gray-200 dark:border-gray-700 p-4">
        <h3 class="font-semibold text-gray-900 dark:text-gray-100 mb-3">{{ __('supervisor.observation.supervisor_notes') }}</h3>
        <form wire:submit="saveNotes" class="space-y-3">
            <textarea
                wire:model="supervisorNotes"
                rows="3"
                class="w-full rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-gray-100 focus:border-blue-500 focus:ring-blue-500"
                placeholder="{{ __('supervisor.observation.notes_placeholder') }}"
            ></textarea>
            <div class="flex justify-end">
                <button
                    type="submit"
                    class="inline-flex items-center gap-2 px-4 py-2 bg-primary-600 hover:bg-primary-700 text-white text-sm font-medium rounded-lg transition-colors"
                >
                    <span wire:loading.remove wire:target="saveNotes">{{ __('supervisor.observation.save_notes') }}</span>
                    <span wire:loading wire:target="saveNotes">{{ __('supervisor.observation.saving') }}</span>
                </button>
            </div>
        </form>
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
            no_audio_video: @json(__('supervisor.observation.no_audio_video')),
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
            if (role === 'teacher') {
                roleBadge = '<span class="px-2 py-0.5 rounded text-xs bg-green-600 text-white">{{ __("supervisor.observation.role_teacher") }}</span>';
            } else if (role === 'student') {
                roleBadge = '<span class="px-2 py-0.5 rounded text-xs bg-blue-600 text-white">{{ __("supervisor.observation.role_student") }}</span>';
            } else if (role === 'admin' || role === 'observer') {
                roleBadge = '<span class="px-2 py-0.5 rounded text-xs bg-purple-600 text-white">{{ __("supervisor.observation.role_admin") }}</span>';
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

                // Handle remote participants
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

                room.on(window.LiveKit.RoomEvent.Reconnecting, () => {
                    // Briefly show connecting state
                });

                room.on(window.LiveKit.RoomEvent.Reconnected, () => {
                    // Back to normal
                });

                // Connect to the room
                await room.connect(tokenData.data.server_url, tokenData.data.access_token);

                isConnected = true;
                showState('connected');

                // Add existing participants
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
</x-filament-panels::page>
