<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>{{ $session->title ?? 'جلسة قرآنية' }} - منصة إتقان</title>
    
    <!-- LiveKit Client -->
    <script src="https://unpkg.com/livekit-client/dist/livekit-client.umd.js"></script>
    
    <!-- Tailwind CSS -->
    <script src="https://cdn.tailwindcss.com"></script>
    
    <style>
        .video-container {
            position: relative;
            width: 100%;
            height: 400px;
            background: #1f2937;
            border-radius: 0.5rem;
            overflow: hidden;
        }
        
        .video-element {
            width: 100%;
            height: 100%;
            object-fit: cover;
        }
        
        .participant-grid {
            display: grid;
            gap: 1rem;
            grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
        }
        
        .controls {
            position: fixed;
            bottom: 20px;
            left: 50%;
            transform: translateX(-50%);
            background: rgba(0, 0, 0, 0.8);
            padding: 1rem;
            border-radius: 1rem;
            display: flex;
            gap: 1rem;
            z-index: 1000;
        }
        
        .control-btn {
            width: 48px;
            height: 48px;
            border-radius: 50%;
            border: none;
            cursor: pointer;
            display: flex;
            align-items: center;
            justify-content: center;
            transition: all 0.2s;
        }
        
        .control-btn:hover {
            transform: scale(1.1);
        }
        
        .mute-btn { background: #374151; color: white; }
        .mute-btn.muted { background: #dc2626; }
        .video-btn { background: #374151; color: white; }
        .video-btn.disabled { background: #dc2626; }
        .leave-btn { background: #dc2626; color: white; }
        
        .status-indicator {
            position: absolute;
            top: 10px;
            right: 10px;
            background: rgba(0, 0, 0, 0.7);
            color: white;
            padding: 0.5rem 1rem;
            border-radius: 0.5rem;
            font-size: 0.875rem;
        }
        
        .participant-name {
            position: absolute;
            bottom: 10px;
            left: 10px;
            background: rgba(0, 0, 0, 0.7);
            color: white;
            padding: 0.25rem 0.75rem;
            border-radius: 0.25rem;
            font-size: 0.875rem;
        }
    </style>
</head>
<body class="bg-gray-100">
    <!-- Loading Screen -->
    <div id="loading" class="fixed inset-0 bg-gray-900 flex items-center justify-center z-50">
        <div class="text-center text-white">
            <div class="animate-spin rounded-full h-16 w-16 border-b-2 border-white mx-auto mb-4"></div>
            <h2 class="text-xl font-semibold mb-2">جاري تحضير الجلسة...</h2>
            <p class="text-gray-300">يرجى الانتظار قليلاً</p>
        </div>
    </div>

    <!-- Main Meeting Interface -->
    <div id="meeting-container" class="hidden min-h-screen">
        <!-- Header -->
        <header class="bg-white shadow-sm p-4">
            <div class="max-w-7xl mx-auto flex items-center justify-between">
                <div>
                    <h1 class="text-xl font-bold text-gray-900">{{ $session->title ?? 'جلسة قرآنية' }}</h1>
                    <p class="text-sm text-gray-600">
                        {{ $session->scheduled_at ? $session->scheduled_at->format('d/m/Y - H:i') : '' }}
                        @if($session->duration_minutes)
                            - {{ $session->duration_minutes }} دقيقة
                        @endif
                    </p>
                </div>
                
                <div class="flex items-center gap-4">
                    <div id="connection-status" class="px-3 py-1 rounded-full text-sm font-medium bg-gray-100 text-gray-600">
                        جاري الاتصال...
                    </div>
                    <div id="participant-count" class="px-3 py-1 rounded-full text-sm font-medium bg-blue-100 text-blue-800">
                        0 مشارك
                    </div>
                </div>
            </div>
        </header>

        <!-- Main Content -->
        <main class="p-4">
            <div class="max-w-7xl mx-auto">
                <!-- Video Grid -->
                <div id="video-grid" class="participant-grid mb-20">
                    <!-- Local video -->
                    <div class="video-container">
                        <video id="local-video" class="video-element" autoplay muted playsinline></video>
                        <div class="participant-name">{{ $participantName }} (أنت)</div>
                        <div id="local-status" class="status-indicator">جاري التحضير...</div>
                    </div>
                </div>
                
                <!-- No participants message -->
                <div id="no-participants" class="text-center py-12 hidden">
                    <div class="text-gray-500">
                        <svg class="w-16 h-16 mx-auto mb-4 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z"></path>
                        </svg>
                        <h3 class="text-lg font-medium mb-2">في انتظار المشاركين</h3>
                        <p class="text-sm">ستظهر كاميرات المشاركين هنا عند انضمامهم</p>
                    </div>
                </div>
            </div>
        </main>

        <!-- Controls -->
        <div class="controls">
            <button id="mute-btn" class="control-btn mute-btn" title="كتم/إلغاء كتم الميكروفون">
                <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 11a7 7 0 01-7 7m0 0a7 7 0 01-7-7m7 7v4m0 0H8m4 0h4m-4-8a3 3 0 01-3-3V5a3 3 0 116 0v6a3 3 0 01-3 3z"></path>
                </svg>
            </button>
            
            <button id="video-btn" class="control-btn video-btn" title="تشغيل/إيقاف الكاميرا">
                <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 10l4.553-2.276A1 1 0 0121 8.618v6.764a1 1 0 01-1.447.894L15 14M5 18h8a2 2 0 002-2V8a2 2 0 00-2-2H5a2 2 0 00-2 2v8a2 2 0 002 2z"></path>
                </svg>
            </button>
            
            <button id="leave-btn" class="control-btn leave-btn" title="مغادرة الجلسة">
                <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1"></path>
                </svg>
            </button>
        </div>
    </div>

    <!-- Error Message -->
    <div id="error-container" class="hidden fixed inset-0 bg-gray-900 flex items-center justify-center z-50">
        <div class="bg-white rounded-lg p-8 max-w-md mx-4 text-center">
            <div class="text-red-500 mb-4">
                <svg class="w-16 h-16 mx-auto" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-2.5L13.732 4c-.77-.833-1.96-.833-2.73 0L5.084 15.5c-.77.833.192 2.5 1.732 2.5z"></path>
                </svg>
            </div>
            <h3 class="text-lg font-semibold text-gray-900 mb-2">خطأ في الاتصال</h3>
            <p id="error-message" class="text-gray-600 mb-4">حدث خطأ أثناء محاولة الاتصال بالجلسة</p>
            <button onclick="window.close()" class="bg-gray-600 text-white px-4 py-2 rounded-lg hover:bg-gray-700">
                إغلاق
            </button>
        </div>
    </div>

    <script>
        // LiveKit Configuration
        const LIVEKIT_URL = @json($livekitServerUrl);
        const TOKEN = @json($token);
        const ROOM_NAME = @json($roomName);
        const PARTICIPANT_NAME = @json($participantName);
        const USER_ROLE = @json($userRole);

        let room = null;
        let localVideoTrack = null;
        let localAudioTrack = null;
        let isAudioMuted = false;
        let isVideoDisabled = false;

        // DOM Elements
        const loadingEl = document.getElementById('loading');
        const meetingContainer = document.getElementById('meeting-container');
        const errorContainer = document.getElementById('error-container');
        const errorMessage = document.getElementById('error-message');
        const videoGrid = document.getElementById('video-grid');
        const connectionStatus = document.getElementById('connection-status');
        const participantCount = document.getElementById('participant-count');
        const localVideo = document.getElementById('local-video');
        const localStatus = document.getElementById('local-status');
        const muteBtn = document.getElementById('mute-btn');
        const videoBtn = document.getElementById('video-btn');
        const leaveBtn = document.getElementById('leave-btn');
        const noParticipants = document.getElementById('no-participants');

        // Initialize meeting
        async function initializeMeeting() {
            try {
                // Create room instance
                room = new LiveKit.Room({
                    adaptiveStream: true,
                    dynacast: true,
                });

                // Set up event listeners
                setupEventListeners();

                // Connect to room
                await room.connect(LIVEKIT_URL, TOKEN);
                
                console.log('Connected to room:', room.name);
                
                // Enable local camera and microphone
                await enableLocalMedia();
                
                // Hide loading and show meeting
                loadingEl.classList.add('hidden');
                meetingContainer.classList.remove('hidden');
                
                updateConnectionStatus('connected');
                updateParticipantCount();
                
            } catch (error) {
                console.error('Failed to initialize meeting:', error);
                showError('فشل في الاتصال بالجلسة: ' + error.message);
            }
        }

        // Set up event listeners
        function setupEventListeners() {
            room.on(LiveKit.RoomEvent.TrackSubscribed, handleTrackSubscribed);
            room.on(LiveKit.RoomEvent.TrackUnsubscribed, handleTrackUnsubscribed);
            room.on(LiveKit.RoomEvent.ParticipantConnected, handleParticipantConnected);
            room.on(LiveKit.RoomEvent.ParticipantDisconnected, handleParticipantDisconnected);
            room.on(LiveKit.RoomEvent.ConnectionStateChanged, handleConnectionStateChanged);
            room.on(LiveKit.RoomEvent.Disconnected, handleDisconnected);

            // Control buttons
            muteBtn.addEventListener('click', toggleAudio);
            videoBtn.addEventListener('click', toggleVideo);
            leaveBtn.addEventListener('click', leaveRoom);
        }

        // Enable local media
        async function enableLocalMedia() {
            try {
                // Enable camera
                localVideoTrack = await LiveKit.createLocalVideoTrack({
                    resolution: LiveKit.VideoPresets.h720.resolution,
                });
                await room.localParticipant.publishTrack(localVideoTrack);
                localVideoTrack.attach(localVideo);

                // Enable microphone
                localAudioTrack = await LiveKit.createLocalAudioTrack();
                await room.localParticipant.publishTrack(localAudioTrack);

                localStatus.textContent = 'متصل';
                
            } catch (error) {
                console.error('Failed to enable local media:', error);
                localStatus.textContent = 'خطأ في الوسائط';
            }
        }

        // Handle track subscription
        function handleTrackSubscribed(track, publication, participant) {
            if (track.kind === LiveKit.Track.Kind.Video || track.kind === LiveKit.Track.Kind.Audio) {
                const element = track.attach();
                addParticipantVideo(participant, element, track.kind);
            }
        }

        // Handle track unsubscription
        function handleTrackUnsubscribed(track, publication, participant) {
            track.detach();
            removeParticipantVideo(participant.identity);
        }

        // Handle participant connected
        function handleParticipantConnected(participant) {
            console.log('Participant connected:', participant.identity);
            updateParticipantCount();
        }

        // Handle participant disconnected
        function handleParticipantDisconnected(participant) {
            console.log('Participant disconnected:', participant.identity);
            removeParticipantVideo(participant.identity);
            updateParticipantCount();
        }

        // Handle connection state changes
        function handleConnectionStateChanged(state) {
            console.log('Connection state:', state);
            updateConnectionStatus(state);
        }

        // Handle disconnection
        function handleDisconnected() {
            console.log('Disconnected from room');
            updateConnectionStatus('disconnected');
        }

        // Add participant video
        function addParticipantVideo(participant, element, trackKind) {
            const participantId = participant.identity;
            let container = document.getElementById(`participant-${participantId}`);
            
            if (!container) {
                container = document.createElement('div');
                container.id = `participant-${participantId}`;
                container.className = 'video-container';
                container.innerHTML = `
                    <div class="participant-name">${participant.name || participant.identity}</div>
                    <div class="status-indicator">متصل</div>
                `;
                videoGrid.appendChild(container);
            }

            if (trackKind === LiveKit.Track.Kind.Video) {
                element.className = 'video-element';
                container.appendChild(element);
            }

            updateNoParticipantsMessage();
        }

        // Remove participant video
        function removeParticipantVideo(participantId) {
            const container = document.getElementById(`participant-${participantId}`);
            if (container) {
                container.remove();
            }
            updateNoParticipantsMessage();
        }

        // Update connection status
        function updateConnectionStatus(state) {
            const statusMap = {
                'connected': { text: 'متصل', class: 'bg-green-100 text-green-800' },
                'connecting': { text: 'جاري الاتصال...', class: 'bg-yellow-100 text-yellow-800' },
                'disconnected': { text: 'منقطع', class: 'bg-red-100 text-red-800' },
                'reconnecting': { text: 'جاري إعادة الاتصال...', class: 'bg-yellow-100 text-yellow-800' }
            };
            
            const status = statusMap[state] || statusMap['connecting'];
            connectionStatus.textContent = status.text;
            connectionStatus.className = `px-3 py-1 rounded-full text-sm font-medium ${status.class}`;
        }

        // Update participant count
        function updateParticipantCount() {
            const count = room ? room.participants.size + 1 : 0; // +1 for local participant
            participantCount.textContent = `${count} مشارك`;
        }

        // Update no participants message
        function updateNoParticipantsMessage() {
            const remoteParticipants = videoGrid.querySelectorAll('[id^="participant-"]');
            if (remoteParticipants.length === 0) {
                noParticipants.classList.remove('hidden');
            } else {
                noParticipants.classList.add('hidden');
            }
        }

        // Toggle audio
        async function toggleAudio() {
            if (localAudioTrack) {
                isAudioMuted = !isAudioMuted;
                localAudioTrack.mute(isAudioMuted);
                muteBtn.classList.toggle('muted', isAudioMuted);
            }
        }

        // Toggle video
        async function toggleVideo() {
            if (localVideoTrack) {
                isVideoDisabled = !isVideoDisabled;
                localVideoTrack.mute(isVideoDisabled);
                videoBtn.classList.toggle('disabled', isVideoDisabled);
            }
        }

        // Leave room
        async function leaveRoom() {
            if (room) {
                await room.disconnect();
            }
            window.close();
        }

        // Show error
        function showError(message) {
            errorMessage.textContent = message;
            loadingEl.classList.add('hidden');
            errorContainer.classList.remove('hidden');
        }

        // Initialize on page load
        document.addEventListener('DOMContentLoaded', initializeMeeting);
        
        // Handle page unload
        window.addEventListener('beforeunload', async () => {
            if (room) {
                await room.disconnect();
            }
        });
    </script>
</body>
</html>
