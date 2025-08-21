@extends('components.layouts.teacher')

@section('title', $session->title ?? 'تفاصيل الجلسة')

@push('head')
<script src="https://unpkg.com/livekit-client/dist/livekit-client.umd.js"></script>
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
<style>
    .video-container {
        position: relative;
        background: #1a1a1a;
        border-radius: 8px;
        overflow: hidden;
        aspect-ratio: 16/9;
    }
    
    .video-container video {
        width: 100%;
        height: 100%;
        object-fit: cover;
    }
    
    .participant-label {
        position: absolute;
        bottom: 8px;
        left: 8px;
        background: rgba(0, 0, 0, 0.7);
        color: white;
        padding: 4px 8px;
        border-radius: 4px;
        font-size: 12px;
        z-index: 10;
    }
    
    .teacher-border {
        border: 3px solid #10b981;
    }
    
    .student-border {
        border: 3px solid #3b82f6;
    }
    
    .speaking {
        box-shadow: 0 0 20px rgba(34, 197, 94, 0.8);
        border-color: #22c55e !important;
    }
    
    .control-btn {
        @apply w-12 h-12 rounded-full flex items-center justify-center transition-all duration-200;
    }
    
    .control-btn:hover {
        transform: scale(1.05);
    }
    
    .control-btn.active {
        @apply bg-green-600 text-white;
    }
    
    .control-btn.inactive {
        @apply bg-red-600 text-white;
    }
    
    .chat-message {
        @apply mb-3 p-3 rounded-lg;
    }
    
    .chat-message.teacher {
        @apply bg-green-50 border-r-4 border-green-500;
    }
    
    .chat-message.student {
        @apply bg-blue-50 border-r-4 border-blue-500;
    }
    
    .notification {
        position: fixed;
        top: 20px;
        right: 20px;
        z-index: 1000;
        max-width: 300px;
    }
</style>
@endpush

@section('content')
<div class="container mx-auto px-4 py-8">
    <!-- Session Header -->
    <div class="bg-white rounded-lg shadow-md p-6 mb-6">
        <div class="flex items-center justify-between">
            <div>
                <h1 class="text-2xl font-bold text-gray-900 mb-2">{{ $session->title ?? 'جلسة القرآن الكريم' }}</h1>
                <p class="text-gray-600">{{ $session->description ?? 'جلسة تعليم القرآن الكريم' }}</p>
            </div>
            <div class="text-right">
                <p class="text-sm text-gray-500">تاريخ الجلسة</p>
                <p class="text-lg font-semibold text-gray-900">{{ $session->scheduled_at ? $session->scheduled_at->format('Y-m-d H:i') : 'غير محدد' }}</p>
            </div>
        </div>
    </div>

    <!-- Meeting Controls -->
    <div class="bg-white rounded-lg shadow-md p-6 mb-6">
        <div class="flex items-center justify-between">
            <div>
                <h2 class="text-xl font-semibold text-gray-900 mb-2">التحكم في الاجتماع</h2>
                <p class="text-gray-600">ابدأ الاجتماع أو انضم إليه</p>
            </div>
            <div>
                <button 
                    id="startMeetingBtn"
                    class="bg-blue-600 hover:bg-blue-700 text-white px-6 py-3 rounded-lg font-semibold transition-colors duration-200 flex items-center gap-2"
                    onclick="joinMeeting()"
                >
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 10l4.553-2.276A1 1 0 0121 8.618v6.764a1 1 0 01-1.447.894L15 14M5 18h8a2 2 0 002-2V8a2 2 0 00-2-2H5a2 2 0 00-2 2v8a2 2 0 002 2z"></path>
                    </svg>
                    <span id="meetingBtnText">بدء/انضم للاجتماع</span>
                </button>
            </div>
        </div>
    </div>

    <!-- Meeting Container -->
    <div id="meetingContainer" class="bg-white rounded-lg shadow-md overflow-hidden" style="display: none;">
        <div class="bg-gray-900 p-4">
            <h3 class="text-white text-lg font-semibold">اجتماع مباشر - {{ $session->title ?? 'جلسة القرآن الكريم' }}</h3>
        </div>
        
        <!-- LiveKit Meeting Interface -->
        <div id="livekitMeetingInterface" class="min-h-[600px] bg-gray-900">
            <!-- Loading Overlay -->
            <div id="loadingOverlay" class="fixed inset-0 bg-black bg-opacity-75 flex items-center justify-center z-50">
                <div class="text-center text-white">
                    <div class="animate-spin rounded-full h-16 w-16 border-b-2 border-blue-500 mx-auto mb-4"></div>
                    <p class="text-xl">جاري الاتصال بالاجتماع...</p>
                </div>
            </div>
            
            <!-- Meeting Interface -->
            <div id="meetingInterface" class="h-screen flex flex-col" style="display: none;">
                <!-- Video Grid -->
                <div id="videoGrid" class="flex-1 grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4 gap-4 p-4">
                    <!-- Participants will be added here dynamically -->
                </div>
                
                <!-- Control Bar -->
                <div class="bg-gray-800 p-4 flex items-center justify-center gap-4">
                    <button id="micBtn" class="control-button active" title="إيقاف/تشغيل الميكروفون">
                        <svg fill="currentColor" viewBox="0 0 20 20">
                            <path fill-rule="evenodd" d="M7 4a3 3 0 016 0v4a3 3 0 11-6 0V4zm4 10.93A7.001 7.001 0 0017 8a1 1 0 10-2 0A5 5 0 015 8a1 1 0 00-2 0 7.001 7.001 0 006 6.93V17H6a1 1 0 100 2h8a1 1 0 100-2h-3v-2.07z" clip-rule="evenodd"/>
                        </svg>
                    </button>
                    
                    <button id="cameraBtn" class="control-button active" title="إيقاف/تشغيل الكاميرا">
                        <svg fill="currentColor" viewBox="0 0 20 20">
                            <path fill-rule="evenodd" d="M4 3a2 2 0 00-2 2v10a2 2 0 002 2h12a2 2 0 002-2V5a2 2 0 00-2-2H4zm12 12H4l4-8v3l2 2 2-2v-3l4 8z" clip-rule="evenodd"/>
                        </svg>
                    </button>
                    
                    <button id="screenShareBtn" class="control-button" title="مشاركة الشاشة">
                        <svg fill="currentColor" viewBox="0 0 20 20">
                            <path d="M3 4a1 1 0 011-1h12a1 1 0 011 1v2a1 1 0 01-1 1H4a1 1 0 01-1-1V4zM3 10a1 1 0 011-1h6a1 1 0 011 1v6a1 1 0 01-1 1H4a1 1 0 01-1-1v-6zM14 9a1 1 0 00-1 1v6a1 1 0 001 1h2a1 1 0 001-1v-6a1 1 0 00-1-1h-2z"/>
                        </svg>
                    </button>
                    
                    <button id="handRaiseBtn" class="control-button" title="رفع اليد">
                        <svg fill="currentColor" viewBox="0 0 20 20">
                            <path d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/>
                        </svg>
                    </button>
                    
                    <button id="chatToggleBtn" class="control-button" title="إظهار/إخفاء الدردشة">
                        <svg fill="currentColor" viewBox="0 0 20 20">
                            <path fill-rule="evenodd" d="M18 10c0 3.866-3.582 7-8 7a8.841 8.841 0 01-4.083-.98L2 17l1.338-3.123C2.493 12.767 2 11.434 2 10c0-3.866 3.582-7 8-7s8 3.134 8 7zM7 9H5v2h2V9zm8 0h-2v2h2V9zM9 9h2v2H9V9z" clip-rule="evenodd"/>
                        </svg>
                    </button>
                    
                    <button id="leaveBtn" class="control-button danger large" title="مغادرة الاجتماع">
                        <svg fill="currentColor" viewBox="0 0 20 20">
                            <path fill-rule="evenodd" d="M3 3a1 1 0 00-1 1v12a1 1 0 001 1h12a1 1 0 001-1V4a1 1 0 00-1-1H3zm7 7.586l3.293-3.293a1 1 0 111.414 1.414L11.414 12l3.293 3.293a1 1 0 01-1.414 1.414L10 13.414l-3.293 3.293a1 1 0 01-1.414-1.414L8.586 12 5.293 8.707a1 1 0 011.414-1.414L10 10.586l3.293-3.293z" clip-rule="evenodd"/>
                        </svg>
                    </button>
                </div>
            </div>
            
            <!-- Sidebar -->
            <div id="sidebar" class="fixed right-0 top-0 h-full w-80 bg-white shadow-lg transform translate-x-full transition-transform duration-300 z-40">
                <!-- Participants Tab -->
                <div class="sidebar-header" id="participantsTab">
                    المشاركون (<span id="participantCount">0</span>)
                </div>
                
                <div class="participants-list" id="participantsList">
                    <!-- Participants will be dynamically added here -->
                </div>
                
                <!-- Chat Section -->
                <div class="chat-section" id="chatSection" style="display: none;">
                    <div class="chat-header">
                        الدردشة
                    </div>
                    
                    <div class="chat-messages" id="chatMessages">
                        <!-- Chat messages will be dynamically added here -->
                    </div>
                    
                    <div class="chat-input-container">
                        <input type="text" class="chat-input" id="chatInput" placeholder="اكتب رسالة..." maxlength="500">
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Session Details -->
    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
        <!-- Session Information -->
        <div class="bg-white rounded-lg shadow-md p-6">
            <h3 class="text-lg font-semibold text-gray-900 mb-4">معلومات الجلسة</h3>
            <div class="space-y-3">
                <div class="flex justify-between">
                    <span class="text-gray-600">الحالة:</span>
                    <span class="font-medium text-gray-900">
                        @if($session->status === 'scheduled')
                            <span class="text-blue-600">مجدولة</span>
                        @elseif($session->status === 'in_progress')
                            <span class="text-green-600">جارية</span>
                        @elseif($session->status === 'completed')
                            <span class="text-gray-600">مكتملة</span>
                        @elseif($session->status === 'cancelled')
                            <span class="text-red-600">ملغية</span>
                        @else
                            {{ $session->status }}
                        @endif
                    </span>
                </div>
                
                <div class="flex justify-between">
                    <span class="text-gray-600">المدة:</span>
                    <span class="font-medium text-gray-900">{{ $session->duration_minutes ?? 60 }} دقيقة</span>
                </div>
                
                <div class="flex justify-between">
                    <span class="text-gray-600">نوع الجلسة:</span>
                    <span class="font-medium text-gray-900">
                        @if($session->quran_circle_id)
                            مجموعة
                        @elseif($session->individual_circle_id)
                            فردية
                        @elseif($session->quran_subscription_id)
                            اشتراك
                        @else
                            غير محدد
                        @endif
                    </span>
                </div>
                
                @if($session->meeting_room_name)
                <div class="flex justify-between">
                    <span class="text-gray-600">اسم الغرفة:</span>
                    <span class="font-medium text-gray-900 font-mono text-sm">{{ $session->meeting_room_name }}</span>
                </div>
                @endif
            </div>
        </div>

        <!-- Participants -->
        <div class="bg-white rounded-lg shadow-md p-6">
            <h3 class="text-lg font-semibold text-gray-900 mb-4">المشاركون</h3>
            <div class="space-y-3">
                @if($session->teacher)
                <div class="flex items-center gap-3 p-3 bg-blue-50 rounded-lg">
                    <div class="w-10 h-10 bg-blue-600 rounded-full flex items-center justify-center text-white font-semibold">
                        {{ substr($session->teacher->name, 0, 1) }}
                    </div>
                    <div>
                        <p class="font-medium text-gray-900">{{ $session->teacher->name }}</p>
                        <p class="text-sm text-blue-600">المعلم</p>
                    </div>
                </div>
                @endif
                
                @if($session->students && $session->students->count() > 0)
                    @foreach($session->students->take(5) as $student)
                    <div class="flex items-center gap-3 p-3 bg-gray-50 rounded-lg">
                        <div class="w-10 h-10 bg-gray-600 rounded-full flex items-center justify-center text-white font-semibold">
                            {{ substr($student->name, 0, 1) }}
                        </div>
                        <div>
                            <p class="font-medium text-gray-900">{{ $student->name }}</p>
                            <p class="text-sm text-gray-600">طالب</p>
                        </div>
                    </div>
                    @endforeach
                    
                    @if($session->students->count() > 5)
                    <p class="text-sm text-gray-500 text-center">و {{ $session->students->count() - 5 }} طالب آخر</p>
                    @endif
                @else
                    <p class="text-gray-500 text-center">لا يوجد طلاب مسجلين</p>
                @endif
            </div>
        </div>
    </div>
</div>

<script src="https://unpkg.com/livekit-client@1.15.13/dist/livekit-client.umd.js"></script>
<script>
    // Fallback if CDN fails
    if (typeof LiveKit === 'undefined') {
        console.log('CDN failed, trying alternative source...');
        const script = document.createElement('script');
        script.src = 'https://cdn.jsdelivr.net/npm/livekit-client@1.15.13/dist/livekit-client.umd.js';
        script.onload = () => console.log('LiveKit loaded from alternative source');
        script.onerror = () => console.error('Failed to load LiveKit from alternative source');
        document.head.appendChild(script);
    }
</script>
<script>
    // Meeting configuration
    const meetingConfig = {
        serverUrl: '{{ config("livekit.server_url") }}',
        roomName: '{{ $session->meeting_room_name ?? "test-room" }}',
        participantName: '{{ auth()->user()->first_name }} {{ auth()->user()->last_name }}',
        sessionId: {{ $session->id ?? 'null' }},
        userType: '{{ auth()->user()->user_type }}',
        csrfToken: '{{ csrf_token() }}'
    };
    
    // Global variables
    let room = null;
    let participants = new Map();
    let isHandRaised = false;
    let isChatVisible = false;
    
    // Meeting state
    let isMeetingActive = false;
    
    // Join meeting INLINE in the same page - NO REDIRECTS (TEACHER VERSION)
    async function joinMeeting() {
        try {
            console.log('Starting robust LiveKit meeting inline (TEACHER)...');
            
            const startBtn = document.getElementById('startMeetingBtn');
            const btnText = document.getElementById('meetingBtnText');
            const meetingContainer = document.getElementById('meetingContainer');
            const loadingOverlay = document.getElementById('loadingOverlay');
            const meetingInterface = document.getElementById('meetingInterface');
            
            // Update button state
            startBtn.disabled = true;
            btnText.innerHTML = '<i class="fas fa-spinner fa-spin mr-2"></i>جاري بدء الجلسة...';
            
            // Show meeting container with loading state
            if (meetingContainer) {
                meetingContainer.style.display = 'block';
            }
            if (loadingOverlay) {
                loadingOverlay.style.display = 'flex';
            }
            if (meetingInterface) {
                meetingInterface.style.display = 'none';
            }
            
            // Get or create meeting room (teacher can force create)
            await ensureMeetingRoom();
            
            // Initialize robust LiveKit meeting inline
            await initializeMeeting();
            
        } catch (error) {
            console.error('Error starting meeting:', error);
            alert('فشل في بدء الاجتماع: ' + error.message);
            
            // Reset button state
            const startBtn = document.getElementById('startMeetingBtn');
            const btnText = document.getElementById('meetingBtnText');
            
            startBtn.disabled = false;
            btnText.textContent = 'بدء/انضم للاجتماع';
            
            // Hide meeting container
            const meetingContainer = document.getElementById('meetingContainer');
            if (meetingContainer) {
                meetingContainer.style.display = 'none';
            }
        }
    }
    
        // Initialize LiveKit meeting
    async function initializeMeeting() {
        try {
            console.log('Initializing LiveKit meeting...');
            console.log('LiveKit config:', {
                serverUrl: meetingConfig.serverUrl,
                roomName: meetingConfig.roomName,
                participantName: meetingConfig.participantName
            });
            
            // Check if LiveKit is loaded
            if (typeof LiveKit === 'undefined') {
                throw new Error('LiveKit library not loaded. Please refresh the page and try again.');
            }
            
            updateConnectionStatus('connecting');
            
            // Get participant token with enhanced error handling
            const token = await getParticipantToken();
            if (!token) {
                throw new Error('Failed to get participant token');
            }
            
            console.log('Got token, length:', token.length);
            console.log('Token prefix:', token.substring(0, 50) + '...');
            
            // Create room
            room = new LiveKit.Room({
                adaptiveStream: true,
                dynacast: true,
            });
            
            // Set up event listeners
            setupRoomEventListeners();
            
            // Connect to room with enhanced error handling
            console.log('Attempting to connect to LiveKit room...');
            console.log('Connection params:', {
                serverUrl: meetingConfig.serverUrl,
                hasToken: !!token,
                autoSubscribe: true
            });
            
            try {
                await room.connect(meetingConfig.serverUrl, token, {
                    autoSubscribe: true,
                });
                console.log('Successfully connected to LiveKit room');
            } catch (connectError) {
                console.error('LiveKit connection failed:', connectError);
                console.error('Connection error details:', {
                    message: connectError.message,
                    name: connectError.name,
                    stack: connectError.stack
                });
                throw new Error(`LiveKit connection failed: ${connectError.message}`);
            }
            
            // Wait for the room to be fully ready
            await new Promise((resolve) => {
                if (room.state === LiveKit.ConnectionState.Connected) {
                    resolve();
                } else {
                    room.once(LiveKit.RoomEvent.Connected, resolve);
                }
            });
            
            console.log('Room is fully ready, state:', room.state);
            
            // Hide loading and show meeting
            document.getElementById('loadingOverlay').style.display = 'none';
            document.getElementById('meetingInterface').style.display = 'flex';
            
            console.log('Meeting interface displayed, initializing UI...');
            
            // Initialize UI
            initializeUI();
            
            // Add local participant video
            await addLocalParticipant();
            
            console.log('Meeting initialization complete');
            
            // Debug room state
            console.log('Room state after initialization:');
            console.log('- Room connected:', room.state);
            console.log('- Local participant:', room.localParticipant);
            console.log('- Local participant tracks:', {
                audio: room.localParticipant?.audioTrack,
                video: room.localParticipant?.videoTrack
            });
            console.log('- Participants count:', room.participants?.size || 0);
            
        } catch (error) {
            console.error('Error initializing meeting:', error);
            console.error('Error details:', {
                message: error.message,
                name: error.name,
                stack: error.stack,
                roomState: room?.state,
                roomExists: !!room
            });
            alert('فشل في الاتصال بالاجتماع: ' + error.message);
            
            // Cleanup on error
            if (room) {
                try {
                    room.disconnect();
                } catch (disconnectError) {
                    console.error('Error during cleanup disconnect:', disconnectError);
                }
            }
        }
    }
    
    // Ensure meeting room exists
    async function ensureMeetingRoom() {
        try {
            const response = await fetch(`/meetings/${meetingConfig.sessionId}/create-or-get`, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': meetingConfig.csrfToken
                }
            });

            const data = await response.json();
            
            if (!data.success) {
                // Handle different types of errors
                if (response.status === 423) {
                    // Meeting not ready yet - unusual for teachers but handle gracefully
                    throw new Error('الاجتماع غير متاح حالياً. يرجى المحاولة مرة أخرى.');
                } else if (response.status === 403) {
                    // Forbidden - teacher doesn't have access
                    throw new Error(data.message || 'غير مصرح لك بإدارة هذه الجلسة');
                } else {
                    throw new Error(data.message || 'Failed to access meeting room');
                }
            }

            // Update room name if it was created/retrieved
            if (data.data.room_name) {
                meetingConfig.roomName = data.data.room_name;
            }

            // Show appropriate message based on whether room existed or was created
            if (data.data.exists) {
                showNotification('تم العثور على الاجتماع، جاري الانضمام...', 'info');
            } else if (data.data.created) {
                showNotification('تم إنشاء غرفة الاجتماع بنجاح', 'success');
            }

            return data.data;
        } catch (error) {
            console.error('Failed to ensure meeting room:', error);
            throw error;
        }
    }

    // Get participant token from backend
    async function getParticipantToken() {
        try {
            const response = await fetch(`/api/meetings/${meetingConfig.sessionId}/token`, {
                method: 'GET',
                headers: {
                    'X-CSRF-TOKEN': meetingConfig.csrfToken,
                    'Content-Type': 'application/json',
                }
            });
            
            if (!response.ok) {
                throw new Error('Failed to get token');
            }
            
            const data = await response.json();
            if (!data.success) {
                throw new Error(data.message || 'Failed to get token');
            }
            
            return data.data.access_token;
        } catch (error) {
            console.error('Error getting token:', error);
            throw error;
        }
    }
    
    // Setup robust room event listeners - FIXED FOR PARTICIPANT CONNECTION (TEACHER VERSION)
    function setupRoomEventListeners() {
        room.on('connected', () => {
            console.log('✅ Teacher room connected successfully');
            updateConnectionStatus('connected');
            
            // Add ALL existing participants when we connect - with null safety
            if (room.remoteParticipants && typeof room.remoteParticipants.size !== 'undefined') {
                console.log('Adding existing participants:', room.remoteParticipants.size);
                room.remoteParticipants.forEach(participant => {
                    console.log('Adding existing participant:', participant.identity);
                    addParticipant(participant);
                });
            } else {
                console.log('No remote participants available yet or remoteParticipants is undefined');
            }
        });

        room.on('disconnected', (reason) => {
            console.log('❌ Teacher room disconnected');
            console.log('Disconnect reason:', reason);
            console.log('Room state before disconnect:', room.state);
            console.log('Connection quality:', room.engine?.connectionQuality);
            updateConnectionStatus('disconnected');
        });

        // ✅ CRITICAL FIX: This ensures teacher sees all students when they join
        room.on('participantConnected', (participant) => {
            console.log('✅ NEW STUDENT JOINED (TEACHER VIEW):', participant.identity);
            addParticipant(participant);
            updateParticipantCount();
            
            const name = participant.name || participant.identity;
            console.log(`Student ${name} joined the class`);
        });

        room.on('participantDisconnected', (participant) => {
            console.log('❌ STUDENT LEFT (TEACHER VIEW):', participant.identity);
            removeParticipant(participant);
            updateParticipantCount();
            
            const name = participant.name || participant.identity;
            console.log(`Student ${name} left the class`);
        });

        // ✅ CRITICAL FIX: This ensures teacher sees student video/audio streams
        room.on('trackSubscribed', (track, publication, participant) => {
            console.log('✅ STUDENT TRACK SUBSCRIBED (TEACHER VIEW):', track.kind, 'from', participant.identity);
            handleTrackSubscribed(track, publication, participant);
        });

        room.on('trackUnsubscribed', (track, publication, participant) => {
            console.log('❌ STUDENT TRACK UNSUBSCRIBED (TEACHER VIEW):', track.kind, 'from', participant.identity);
            handleTrackUnsubscribed(track, publication, participant);
        });

        // ✅ CRITICAL: Track published events for immediate connection
        room.on('trackPublished', (publication, participant) => {
            console.log('✅ STUDENT TRACK PUBLISHED (TEACHER VIEW):', publication.kind, 'from', participant.identity);
            updateParticipantTracks(participant);
        });

        room.on('trackUnpublished', (publication, participant) => {
            console.log('❌ STUDENT TRACK UNPUBLISHED (TEACHER VIEW):', publication.kind, 'from', participant.identity);
            updateParticipantTracks(participant);
        });

        room.on('activeSpeakersChanged', (speakers) => {
            console.log('🎤 Active speakers changed (TEACHER VIEW):', speakers.map(s => s.identity));
        });

        // Data received (for chat)
        room.on('dataReceived', (payload, participant) => {
            if (payload.topic === 'chat') {
                handleChatMessage(payload, participant);
            } else if (payload.topic === 'hand-raise') {
                handleHandRaise(payload, participant);
            }
        });
    }
    
    // Handle track subscribed - CRITICAL for showing student video/audio
    function handleTrackSubscribed(track, publication, participant) {
        console.log('🔄 Handling track subscription:', track.kind, 'from', participant.identity);
        
        const participantId = participant.identity;
        
        if (track.kind === 'video') {
            console.log('📹 Attaching video track for student:', participantId);
            
            // Find video element for this participant
            const videoElement = document.getElementById(`video-${participantId}`);
            if (videoElement) {
                track.attach(videoElement);
                console.log('✅ Video attached successfully for:', participantId);
            } else {
                console.warn('❌ Video element not found for participant:', participantId);
            }
            
            // Update video status indicator
            const videoIndicator = document.getElementById(`video-${participantId}-status`);
            if (videoIndicator) {
                videoIndicator.className = 'status-indicator bg-green-500 w-3 h-3 rounded-full';
            }
        }
        
        if (track.kind === 'audio') {
            console.log('🎤 Audio track subscribed for student:', participantId);
            
            // Audio is handled automatically by LiveKit, but update status
            const micIndicator = document.getElementById(`mic-${participantId}`);
            if (micIndicator) {
                micIndicator.className = 'status-indicator bg-green-500 w-3 h-3 rounded-full';
            }
        }
    }
    
    // Handle track unsubscribed
    function handleTrackUnsubscribed(track, publication, participant) {
        console.log('🔄 Handling track unsubscription:', track.kind, 'from', participant.identity);
        
        const participantId = participant.identity;
        
        if (track.kind === 'video') {
            console.log('📹 Detaching video track for student:', participantId);
            track.detach();
            
            // Update video status indicator
            const videoIndicator = document.getElementById(`video-${participantId}-status`);
            if (videoIndicator) {
                videoIndicator.className = 'status-indicator bg-red-500 w-3 h-3 rounded-full';
            }
        }
        
        if (track.kind === 'audio') {
            console.log('🎤 Audio track unsubscribed for student:', participantId);
            
            // Update mic status indicator
            const micIndicator = document.getElementById(`mic-${participantId}`);
            if (micIndicator) {
                micIndicator.className = 'status-indicator bg-red-500 w-3 h-3 rounded-full';
            }
        }
    }
    
    // Initialize UI
    function initializeUI() {
        // Set up control button event listeners
        document.getElementById('micBtn').addEventListener('click', toggleMicrophone);
        document.getElementById('cameraBtn').addEventListener('click', toggleCamera);
        document.getElementById('screenShareBtn').addEventListener('click', toggleScreenShare);
        document.getElementById('handRaiseBtn').addEventListener('click', toggleHandRaise);
        document.getElementById('chatToggleBtn').addEventListener('click', toggleChat);
        document.getElementById('leaveBtn').addEventListener('click', leaveMeeting);
        
        // Set up chat input
        document.getElementById('chatInput').addEventListener('keypress', (e) => {
            if (e.key === 'Enter') {
                sendChatMessage();
            }
        });
        
        // Set up sidebar toggle
        document.getElementById('participantsTab').addEventListener('click', toggleSidebar);
        
        // Initialize control button states
        updateControlButtonStates();
    }
    
    // Update control button states based on current track status
    function updateControlButtonStates() {
        if (!room || !room.localParticipant) return;
        
        const localParticipant = room.localParticipant;
        const micBtn = document.getElementById('micBtn');
        const cameraBtn = document.getElementById('cameraBtn');
        
        // Check if tracks exist and are published
        const hasAudioTrack = localParticipant.audioTrack !== null;
        const hasVideoTrack = localParticipant.videoTrack !== null;
        
        // Update microphone button
        if (hasAudioTrack) {
            micBtn.classList.add('active');
            micBtn.classList.remove('inactive');
        } else {
            micBtn.classList.remove('active');
            micBtn.classList.add('inactive');
        }
        
        // Update camera button
        if (hasVideoTrack) {
            cameraBtn.classList.add('active');
            cameraBtn.classList.remove('inactive');
        } else {
            cameraBtn.classList.remove('active');
            cameraBtn.classList.add('inactive');
        }
    }
    
    // Add local participant video
    async function addLocalParticipant() {
        try {
            console.log('Adding local participant...');
            console.log('Room:', room);
            console.log('Local participant:', room?.localParticipant);
            
            if (!room || !room.localParticipant) {
                console.log('No room or local participant, returning');
                return;
            }
            
            // Check if room is properly connected
            if (room.state !== LiveKit.ConnectionState.Connected) {
                console.log('Room not fully connected, waiting...');
                await new Promise((resolve) => {
                    if (room.state === LiveKit.ConnectionState.Connected) {
                        resolve();
                    } else {
                        room.once(LiveKit.RoomEvent.Connected, resolve);
                    }
                });
                console.log('Room now connected, proceeding...');
            }
            
            const localParticipant = room.localParticipant;
            const videoGrid = document.getElementById('videoGrid');
            
            console.log('Video grid element:', videoGrid);
            
            // Create video element for local participant
            const videoElement = document.createElement('div');
            videoElement.className = 'relative bg-gray-800 rounded-lg overflow-hidden min-h-[200px]';
            videoElement.id = `video-${localParticipant.identity}`;
            
            videoElement.innerHTML = `
                <video autoplay muted playsinline class="w-full h-full object-cover"></video>
                <div class="absolute bottom-2 left-2 bg-black bg-opacity-50 text-white px-2 py-1 rounded text-sm">
                    ${localParticipant.identity} (أنت)
                </div>
                <div id="hand-${localParticipant.identity}" class="absolute top-2 right-2 bg-yellow-500 text-black px-2 py-1 rounded text-xs" style="display: none;">
                    ✋
                </div>
            `;
            
            videoGrid.appendChild(videoElement);
            
            // Get the video element for track attachment
            const videoTag = videoElement.querySelector('video');
            
            // Enable camera and microphone by default
            try {
                console.log('Enabling camera and microphone with LiveKit...');
                
                // Create and publish video track
                try {
                    const videoTrack = await LiveKit.createLocalVideoTrack({
                        resolution: {
                            width: 1280,
                            height: 720,
                        },
                        facingMode: 'user'
                    });
                    await localParticipant.publishTrack(videoTrack, {
                        name: 'camera',
                        simulcast: true,
                    });
                    console.log('Video track published successfully');
                    
                    // Attach to video element
                    videoTrack.attach(videoTag);
                } catch (videoError) {
                    console.error('Error publishing video track:', videoError);
                    console.error('Error details:', videoError);
                    throw new Error('Video track publishing failed: ' + (videoError.message || videoError.toString()));
                }
                
                // Create and publish audio track
                try {
                    const audioTrack = await LiveKit.createLocalAudioTrack({
                        echoCancellation: true,
                        noiseSuppression: true,
                        autoGainControl: true,
                    });
                    await localParticipant.publishTrack(audioTrack, {
                        name: 'microphone',
                    });
                    console.log('Audio track published successfully');
                } catch (audioError) {
                    console.error('Error publishing audio track:', audioError);
                    throw new Error('Audio track publishing failed: ' + (audioError.message || audioError.toString()));
                }
                
                console.log('Local media tracks published successfully');
                
                // Update control button states
                updateControlButtonStates();
                
            } catch (error) {
                console.error('Error enabling camera/microphone:', error);
                console.error('Error details:', {
                    name: error.name,
                    message: error.message,
                    stack: error.stack,
                    roomState: room?.state,
                    localParticipant: !!room?.localParticipant
                });
                
                // Try audio-only as fallback
                try {
                    console.log('Attempting audio-only mode...');
                    const audioTrack = await LiveKit.createLocalAudioTrack({
                        echoCancellation: true,
                        noiseSuppression: true,
                        autoGainControl: true,
                    });
                    await localParticipant.publishTrack(audioTrack, {
                        name: 'microphone',
                    });
                    console.log('Audio-only mode enabled successfully');
                    showNotification('تم تفعيل الصوت فقط - فشل في تفعيل الكاميرا', 'warning');
                    updateControlButtonStates();
                } catch (audioError) {
                    console.error('Failed to enable audio as well:', audioError);
                    showNotification('فشل في تفعيل الكاميرا والميكروفون - تحقق من الأذونات', 'error');
                }
            }
            
            // Add to participants map
            participants.set(localParticipant.identity, localParticipant);
            
            // Update participant count
            updateParticipantCount();
            
            console.log('Local participant added to UI successfully');
            console.log('Video grid now contains:', videoGrid.children.length, 'elements');
        } catch (error) {
            console.error('Error adding local participant:', error);
        }
    }
    
    // Add participant to video grid
    function addParticipant(participant) {
        if (participants.has(participant.identity)) {
            return;
        }
        
        participants.set(participant.identity, participant);
        
        const videoGrid = document.getElementById('videoGrid');
        const participantTile = document.createElement('div');
        participantTile.className = 'relative bg-gray-800 rounded-lg overflow-hidden';
        participantTile.id = `participant-${participant.identity}`;
        
        participantTile.innerHTML = `
            <video id="video-${participant.identity}" autoplay muted playsinline class="w-full h-full object-cover"></video>
            <div class="absolute bottom-2 left-2 bg-black bg-opacity-50 text-white px-2 py-1 rounded text-sm">
                ${participant.identity}
            </div>
            <div class="absolute top-2 right-2 flex gap-2">
                <div id="mic-${participant.identity}" class="status-indicator ${participant.isMicrophoneEnabled() ? 'bg-green-500' : 'bg-red-500'} w-3 h-3 rounded-full"></div>
                <div id="video-${participant.identity}-status" class="status-indicator ${participant.isCameraEnabled() ? 'bg-green-500' : 'bg-red-500'} w-3 h-3 rounded-full"></div>
                <div id="hand-${participant.identity}" class="bg-yellow-500 text-black px-2 py-1 rounded text-xs" style="display: none;">✋</div>
            </div>
        `;
        
        videoGrid.appendChild(participantTile);
        
        // Add to participants list
        addParticipantToList(participant);
        
        // Subscribe to tracks
        participant.on(LiveKit.ParticipantEvent.TrackSubscribed, (track, publication) => {
            const videoElement = document.getElementById(`video-${participant.identity}`);
            if (videoElement) {
                track.attach(videoElement);
            }
        });
        
        // Update track status
        updateParticipantTracks(participant);
    }
    
    // Remove participant from video grid
    function removeParticipant(participant) {
        const participantTile = document.getElementById(`participant-${participant.identity}`);
        if (participantTile) {
            participantTile.remove();
        }
        
        removeParticipantFromList(participant);
        participants.delete(participant.identity);
    }
    
    // Add participant to list
    function addParticipantToList(participant) {
        const participantsList = document.getElementById('participantsList');
        
        const participantItem = document.createElement('div');
        participantItem.className = 'flex items-center gap-3 p-3 hover:bg-gray-50 border-b border-gray-200';
        participantItem.id = `list-${participant.identity}`;
        
        const isLocal = participant === room.localParticipant;
        const role = isLocal ? meetingConfig.participantRole : 'مشارك';
        
        participantItem.innerHTML = `
            <div class="w-8 h-8 bg-blue-600 rounded-full flex items-center justify-center text-white font-semibold text-sm">
                ${participant.identity.charAt(0).toUpperCase()}
            </div>
            <div class="flex-1">
                <div class="font-medium text-gray-900">${participant.identity}${isLocal ? ' (أنت)' : ''}</div>
                <div class="text-sm text-gray-600">${role}</div>
            </div>
        `;
        
        participantsList.appendChild(participantItem);
    }
    
    // Remove participant from list
    function removeParticipantFromList(participant) {
        const participantItem = document.getElementById(`list-${participant.identity}`);
        if (participantItem) {
            participantItem.remove();
        }
    }
    
    // Update participant tracks
    function updateParticipantTracks(participant) {
        const micIndicator = document.getElementById(`mic-${participant.identity}`);
        const videoIndicator = document.getElementById(`video-${participant.identity}-status`);
        
        if (micIndicator) {
            micIndicator.className = `status-indicator ${participant.isMicrophoneEnabled() ? '' : 'muted'}`;
        }
        
        if (videoIndicator) {
            videoIndicator.className = `status-indicator ${participant.isCameraEnabled() ? '' : 'video-off'}`;
        }
    }
    
    // Update participant count
    function updateParticipantCount() {
        const count = participants.size;
        document.getElementById('participantCount').textContent = count;
    }
    
    // Control functions
    async function toggleCamera() {
        try {
            if (room.localParticipant.videoTrack) {
                // Disable camera by unpublishing the track
                await room.localParticipant.unpublishTrack(room.localParticipant.videoTrack);
                console.log('Camera disabled');
                
                // Hide the video element
                const videoElement = document.getElementById(`video-${room.localParticipant.identity}`);
                if (videoElement) {
                    const videoTag = videoElement.querySelector('video');
                    if (videoTag) {
                        videoTag.srcObject = null;
                    }
                }
            } else {
                // Enable camera by getting user media and publishing
                const stream = await navigator.mediaDevices.getUserMedia({ video: true });
                await room.localParticipant.publishTrack(stream.getVideoTracks()[0]);
                console.log('Camera enabled');
                
                // Show the video element
                const videoElement = document.getElementById(`video-${room.localParticipant.identity}`);
                if (videoElement) {
                    const videoTag = videoElement.querySelector('video');
                    if (videoTag) {
                        videoTag.srcObject = stream;
                    }
                }
            }
            
            // Update button states
            updateControlButtonStates();
            
        } catch (error) {
            console.error('Error toggling camera:', error);
            alert('فشل في تبديل حالة الكاميرا: ' + error.message);
        }
    }
    
    async function toggleMicrophone() {
        try {
            if (room.localParticipant.audioTrack) {
                // Disable microphone by unpublishing the track
                await room.localParticipant.unpublishTrack(room.localParticipant.audioTrack);
                console.log('Microphone disabled');
            } else {
                // Enable microphone by getting user media and publishing
                const stream = await navigator.mediaDevices.getUserMedia({ audio: true });
                await room.localParticipant.publishTrack(stream.getAudioTracks()[0]);
                console.log('Microphone enabled');
            }
            
            // Update button states
            updateControlButtonStates();
            
        } catch (error) {
            console.error('Error toggling microphone:', error);
            alert('فشل في تبديل حالة الميكروفون: ' + error.message);
        }
    }
    
    async function toggleScreenShare() {
        try {
            if (room.localParticipant.videoTrack && room.localParticipant.videoTrack.source === LiveKit.Track.Source.ScreenShare) {
                // Stop screen sharing
                await room.localParticipant.unpublishTrack(room.localParticipant.videoTrack);
                console.log('Screen share stopped');
                document.getElementById('screenShareBtn').classList.remove('active');
            } else {
                // Start screen sharing
                const stream = await navigator.mediaDevices.getDisplayMedia({ video: true });
                await room.localParticipant.publishTrack(stream.getVideoTracks()[0]);
                console.log('Screen share started');
                document.getElementById('screenShareBtn').classList.add('active');
            }
        } catch (error) {
            console.error('Error toggling screen share:', error);
        }
    }
    
    function toggleHandRaise() {
        isHandRaised = !isHandRaised;
        const handRaiseBtn = document.getElementById('handRaiseBtn');
        
        if (isHandRaised) {
            handRaiseBtn.classList.add('active');
            // Send hand raise signal
            if (room) {
                room.localParticipant.publishData(
                    new TextEncoder().encode(JSON.stringify({ raised: true })),
                    { topic: 'hand-raise' }
                );
            }
        } else {
            handRaiseBtn.classList.remove('active');
            // Send hand lower signal
            if (room) {
                room.localParticipant.publishData(
                    new TextEncoder().encode(JSON.stringify({ raised: false })),
                    { topic: 'hand-raise' }
                );
            }
        }
    }
    
    function toggleChat() {
        isChatVisible = !isChatVisible;
        const chatSection = document.getElementById('chatSection');
        chatSection.style.display = isChatVisible ? 'block' : 'none';
    }
    
    function toggleSidebar() {
        const sidebar = document.getElementById('sidebar');
        sidebar.classList.toggle('translate-x-full');
    }
    
    function sendChatMessage() {
        const chatInput = document.getElementById('chatInput');
        const message = chatInput.value.trim();
        
        if (message && room) {
            const chatData = {
                message: message,
                timestamp: new Date().toISOString()
            };
            
            room.localParticipant.publishData(
                new TextEncoder().encode(JSON.stringify(chatData)),
                { topic: 'chat' }
            );
            
            // Add message to local chat
            addChatMessage(room.localParticipant.identity, message, new Date());
            
            chatInput.value = '';
        }
    }
    
    function handleChatMessage(payload, participant) {
        try {
            const data = JSON.parse(new TextDecoder().decode(payload.data));
            addChatMessage(participant.identity, data.message, new Date(data.timestamp));
        } catch (error) {
            console.error('Error handling chat message:', error);
        }
    }
    
    function addChatMessage(sender, message, timestamp) {
        const chatMessages = document.getElementById('chatMessages');
        const messageElement = document.createElement('div');
        messageElement.className = 'chat-message';
        
        messageElement.innerHTML = `
            <div class="sender">${sender}</div>
            <div class="message">${message}</div>
            <div class="time">${timestamp.toLocaleTimeString()}</div>
        `;
        
        chatMessages.appendChild(messageElement);
        chatMessages.scrollTop = chatMessages.scrollHeight;
    }
    
    function handleHandRaise(payload, participant) {
        try {
            const data = JSON.parse(new TextDecoder().decode(payload.data));
            const handIndicator = document.getElementById(`hand-${participant.identity}`);
            
            if (handIndicator) {
                handIndicator.style.display = data.raised ? 'block' : 'none';
            }
        } catch (error) {
            console.error('Error handling hand raise:', error);
        }
    }
    
    function leaveMeeting() {
        if (confirm('هل أنت متأكد من مغادرة الاجتماع؟')) {
            if (room) {
                room.disconnect();
            }
            endMeeting();
        }
    }
    
    // End meeting function
    function endMeeting() {
        const startMeetingBtn = document.getElementById('startMeetingBtn');
        const meetingBtnText = document.getElementById('meetingBtnText');
        const meetingContainer = document.getElementById('meetingContainer');
        
        // Update button state
        startMeetingBtn.classList.remove('bg-red-600', 'hover:bg-red-700');
        startMeetingBtn.classList.add('bg-blue-600', 'hover:bg-blue-700');
        meetingBtnText.textContent = 'بدء الاجتماع';
        
        // Hide meeting container
        meetingContainer.style.display = 'none';
        
        // Mark meeting as inactive
        isMeetingActive = false;
        
        // Disconnect from room
        if (room) {
            room.disconnect();
            room = null;
        }
        
        // Clear participants
        participants.clear();
        
        // Reset UI
        document.getElementById('videoGrid').innerHTML = '';
        document.getElementById('participantsList').innerHTML = '';
        document.getElementById('chatMessages').innerHTML = '';
    }
    
    // Check if meeting is already active
    document.addEventListener('DOMContentLoaded', function() {
        // You can add logic here to check if a meeting is already active
        // For now, we'll assume it's not active
        isMeetingActive = false;
        
        // Check if LiveKit script is loaded
        if (typeof LiveKit === 'undefined') {
            console.log('LiveKit script not loaded yet, waiting...');
            // Wait a bit more for the script to load
            setTimeout(() => {
                if (typeof LiveKit === 'undefined') {
                    console.error('LiveKit script failed to load');
                    document.getElementById('startMeetingBtn').disabled = true;
                    document.getElementById('startMeetingBtn').textContent = 'LiveKit غير متوفر';
                } else {
                    console.log('LiveKit script loaded successfully');
                }
            }, 2000);
        } else {
            console.log('LiveKit script already loaded');
        }
    });

    // Show notification
    function showNotification(message, type = 'info', duration = 5000) {
        console.log(`[${type.toUpperCase()}] ${message}`);
        
        const notification = document.createElement('div');
        notification.className = `fixed top-4 right-4 p-4 rounded-lg shadow-lg max-w-sm z-50 transform translate-x-full transition-transform duration-300`;
        
        switch(type) {
            case 'success':
                notification.classList.add('bg-green-500', 'text-white');
                break;
            case 'error':
                notification.classList.add('bg-red-500', 'text-white');
                break;
            case 'warning':
                notification.classList.add('bg-yellow-500', 'text-white');
                break;
            default:
                notification.classList.add('bg-blue-500', 'text-white');
        }
        
        notification.innerHTML = `
            <div class="flex items-center justify-between">
                <span>${message}</span>
                <button onclick="this.parentElement.parentElement.remove()" class="ml-2 text-white hover:text-gray-200">
                    ×
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

    // Update connection status - simplified for teacher view
    function updateConnectionStatus(status) {
        console.log(`[Connection Status] ${status}`);
        
        // Since teacher view doesn't have a connection status UI, we'll just log and show notifications
        switch(status) {
            case 'connecting':
                console.log('🔄 جاري الاتصال بالجلسة...');
                break;
            case 'connected':
                console.log('✅ تم الاتصال بالجلسة بنجاح');
                // Don't show notification for connected - too noisy
                break;
            case 'disconnected':
                console.log('❌ تم قطع الاتصال عن الجلسة');
                showNotification('تم قطع الاتصال عن الجلسة', 'warning');
                break;
            default:
                console.log(`🔄 حالة الاتصال: ${status}`);
        }
    }
</script>
<style>
    /* LiveKit Meeting Styles */
    .control-button {
        @apply w-12 h-12 rounded-full bg-gray-700 text-white flex items-center justify-center transition-all duration-200 hover:bg-gray-600 focus:outline-none focus:ring-2 focus:ring-blue-500;
    }
    
    .control-button.active {
        @apply bg-green-600 hover:bg-green-700;
    }
    
    .control-button.inactive {
        @apply bg-red-600 hover:bg-red-700;
    }
    
    .control-button.danger {
        @apply bg-red-600 hover:bg-red-700;
    }
    
    .control-button.large {
        @apply w-16 h-16;
    }
    
    .sidebar-header {
        @apply bg-gray-800 text-white p-4 font-semibold text-lg border-b border-gray-700 cursor-pointer;
    }
    
    .participants-list {
        @apply p-4 space-y-2 max-h-96 overflow-y-auto;
    }
    
    .participant-item {
        @apply flex items-center gap-3 p-3 bg-gray-100 rounded-lg;
    }
    
    .participant-avatar {
        @apply w-8 h-8 bg-blue-600 text-white rounded-full flex items-center justify-center text-sm font-semibold;
    }
    
    .participant-name-sidebar {
        @apply font-medium text-gray-900;
    }
    
    .participant-role {
        @apply text-sm text-gray-600;
    }
    
    .chat-section {
        @apply border-t border-gray-200;
    }
    
    .chat-header {
        @apply bg-gray-800 text-white p-4 font-semibold text-lg;
    }
    
    .chat-messages {
        @apply p-4 space-y-3 max-h-64 overflow-y-auto;
    }
    
    .chat-message {
        @apply p-3 bg-gray-100 rounded-lg;
    }
    
    .chat-message .sender {
        @apply font-semibold text-blue-600 text-sm;
    }
    
    .chat-message .time {
        @apply text-xs text-gray-500;
    }
    
    .chat-input-container {
        @apply p-4 border-t border-gray-200;
    }
    
    .chat-input {
        @apply w-full px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500;
    }
    
    .participant-tile {
        @apply relative bg-gray-800 rounded-lg overflow-hidden aspect-video;
    }
    
    .participant-video {
        @apply w-full h-full object-cover;
    }
    
    .participant-info {
        @apply absolute bottom-0 left-0 right-0 bg-gradient-to-t from-black/80 to-transparent p-3;
    }
    
    .participant-name {
        @apply text-white font-semibold text-sm;
    }
    
    .participant-status {
        @apply flex gap-2 mt-1;
    }
    
    .status-indicator {
        @apply w-3 h-3 rounded-full;
    }
    
    .status-indicator.muted {
        @apply bg-red-500;
    }
    
    .status-indicator.video-off {
        @apply bg-yellow-500;
    }
    
    .hand-raised {
        @apply bg-yellow-400 text-gray-900 px-2 py-1 rounded text-xs font-medium;
    }
</style>

@endsection
