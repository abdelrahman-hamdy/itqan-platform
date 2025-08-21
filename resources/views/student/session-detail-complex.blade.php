@extends('components.layouts.student')

@section('title', $session->title ?? 'تفاصيل الجلسة')

@push('head')
<script src="https://unpkg.com/livekit-client/dist/livekit-client.umd.js"></script>
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
<link rel="preconnect" href="https://fonts.googleapis.com">
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
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
                <h2 class="text-xl font-semibold text-gray-900 mb-2">انضم للاجتماع</h2>
                <p class="text-gray-600">انضم لجلسة القرآن الكريم المباشرة</p>
            </div>
            <div>
                <button 
                    id="joinMeetingBtn"
                    class="bg-green-600 hover:bg-green-700 text-white px-6 py-3 rounded-lg font-semibold transition-colors duration-200 flex items-center gap-2"
                    onclick="joinMeeting()"
                >
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 10l4.553-2.276A1 1 0 0121 8.618v6.764a1 1 0 01-1.447.894L15 14M5 18h8a2 2 0 002-2V8a2 2 0 00-2-2H5a2 2 0 00-2 2v8a2 2 0 002 2z"></path>
                    </svg>
                    انضم للاجتماع
                </button>
            </div>
        </div>
    </div>
    
    <!-- Meeting Container -->
    <div id="meetingContainer" class="bg-white rounded-lg shadow-md overflow-hidden" style="display: none;">
        <!-- Meeting Header -->
        <div class="bg-gray-900 p-4 flex items-center justify-between">
            <div class="flex items-center space-x-4 space-x-reverse">
                <h3 class="text-white text-lg font-semibold">{{ $session->title ?? 'جلسة القرآن الكريم' }}</h3>
                <span class="text-gray-300 text-sm">{{ auth()->user()->first_name }} {{ auth()->user()->last_name }} - طالب</span>
            </div>
            
            <div class="flex items-center space-x-3 space-x-reverse">
                <!-- Connection Status -->
                <div id="connectionStatus" class="flex items-center space-x-2 space-x-reverse">
                    <div class="w-2 h-2 bg-yellow-500 rounded-full animate-pulse"></div>
                    <span class="text-sm text-gray-300">جاري الاتصال...</span>
                </div>
                
                <!-- Session Timer -->
                <div id="sessionTimer" class="text-sm bg-gray-800 text-white px-3 py-1 rounded-lg">
                    00:00:00
                </div>
                
                <!-- Participant Count -->
                <div id="participantCount" class="text-sm bg-blue-600 text-white px-3 py-1 rounded-lg">
                    <i class="fas fa-users"></i>
                    <span>0</span>
                </div>
            </div>
        </div>
        
        <!-- LiveKit Meeting Interface -->
        <div id="livekitMeetingInterface" class="min-h-[600px] bg-gray-900 flex flex-col">
            <!-- Loading State -->
            <div id="loadingState" class="flex-1 flex items-center justify-center">
                <div class="text-center text-white">
                    <div class="animate-spin rounded-full h-16 w-16 border-b-2 border-blue-500 mx-auto mb-4"></div>
                    <h3 class="text-lg font-semibold mb-2">جاري تحضير الجلسة...</h3>
                    <p class="text-gray-300">يرجى الانتظار قليلاً</p>
                </div>
            </div>
            
            <!-- Meeting Container -->
            <div id="meetingInterface" class="flex-1 hidden">
                <!-- Meeting Main Container -->
                <div class="bg-gray-900 rounded-lg overflow-hidden" style="height: 75vh;">
                    <div class="relative w-full h-full">
                        <!-- Main Video Area (Speaker View) -->
                        <div id="mainVideoArea" class="relative w-full h-full bg-gradient-to-br from-gray-800 to-gray-900">
                            <!-- Main Speaker Video -->
                            <div id="speakerVideoContainer" class="w-full h-full flex items-center justify-center">
                                <div id="waitingArea" class="text-center text-white">
                                    <div class="mb-6">
                                        <i class="fas fa-video text-6xl text-gray-500 mb-4"></i>
                                        <h3 class="text-2xl font-semibold mb-2">في انتظار المشاركين</h3>
                                        <p class="text-gray-400">ستظهر كاميرا المشاركين هنا عند انضمامهم</p>
                                    </div>
                                </div>
                                <!-- Speaker video will be inserted here -->
                </div>
                
                            <!-- Participants Thumbnail Strip -->
                            <div id="participantsStrip" class="absolute bottom-20 left-4 right-4 flex justify-center">
                                <div id="participantThumbnails" class="flex gap-3 p-3 bg-black bg-opacity-50 rounded-lg max-w-full overflow-x-auto">
                                    <!-- Participant thumbnails will be added here -->
                                </div>
                            </div>
                            
                            <!-- Local Video (Picture-in-Picture) -->
                            <div id="localVideoContainer" class="absolute top-4 right-4 group cursor-move" 
                                 style="width: 240px; height: 180px; resize: both; overflow: hidden; min-width: 160px; min-height: 120px; max-width: 400px; max-height: 300px;">
                                <div class="relative w-full h-full bg-gray-800 rounded-lg overflow-hidden border-2 border-white shadow-lg">
                                    <video id="localVideo" autoplay muted playsinline class="w-full h-full object-cover"></video>
                                    <div class="absolute bottom-2 left-2 bg-black bg-opacity-80 text-white text-xs px-2 py-1 rounded">
                                        أنت
                                    </div>
                                    <!-- Local Video Controls -->
                                    <div class="absolute top-2 right-2 opacity-0 group-hover:opacity-100 transition-opacity flex gap-1">
                                        <button id="pinLocalVideo" class="bg-black bg-opacity-70 text-white w-6 h-6 rounded flex items-center justify-center hover:bg-opacity-90" title="تثبيت">
                                            <i class="fas fa-thumbtack text-xs"></i>
                    </button>
                                        <button id="hideLocalVideo" class="bg-black bg-opacity-70 text-white w-6 h-6 rounded flex items-center justify-center hover:bg-opacity-90" title="إخفاء">
                                            <i class="fas fa-eye-slash text-xs"></i>
                                        </button>
                                    </div>
                                </div>
                            </div>
                            
                            <!-- Meeting Info Bar -->
                            <div class="absolute top-4 left-4 flex gap-3">
                                <!-- Connection Status -->
                                <div id="connectionStatus" class="bg-black bg-opacity-70 text-white px-3 py-2 rounded-lg flex items-center gap-2">
                                    <div id="connectionDot" class="w-2 h-2 bg-green-400 rounded-full"></div>
                                    <span id="connectionText" class="text-sm">متصل</span>
                                </div>
                                
                                <!-- Session Timer -->
                                <div id="sessionTimer" class="bg-black bg-opacity-70 text-white px-3 py-2 rounded-lg flex items-center gap-2">
                                    <i class="fas fa-clock text-sm"></i>
                                    <span id="timerText" class="text-sm">00:00</span>
                                </div>
                            </div>
                            
                            <!-- Participant Count -->
                            <div id="participantCount" class="absolute top-4 left-1/2 transform -translate-x-1/2">
                                <div class="bg-black bg-opacity-70 text-white px-3 py-2 rounded-lg flex items-center gap-2">
                                    <i class="fas fa-users text-sm"></i>
                                    <span id="participantCountText" class="text-sm">1 مشارك</span>
                                </div>
                            </div>
                            
                            <!-- Recording Indicator -->
                            <div id="recordingIndicator" class="absolute top-4 right-4 bg-red-600 text-white px-3 py-2 rounded-lg items-center gap-2 hidden">
                                <div class="w-2 h-2 bg-white rounded-full animate-pulse"></div>
                                <span class="text-sm">جاري التسجيل</span>
                            </div>
                        </div>
                        
                        <!-- Meeting Controls Overlay -->
                        <div class="absolute bottom-0 left-0 right-0 bg-gradient-to-t from-black to-transparent p-6">
                            <div class="flex justify-center items-center gap-4">
                                <!-- Microphone Control -->
                                <div class="group relative">
                                    <button id="micBtn" class="control-btn w-14 h-14 rounded-full flex items-center justify-center transition-all duration-300 bg-gray-700 hover:bg-gray-600 text-white">
                                        <i class="fas fa-microphone text-lg"></i>
                    </button>
                                    <div class="absolute -top-10 left-1/2 transform -translate-x-1/2 bg-black text-white text-xs px-2 py-1 rounded opacity-0 group-hover:opacity-100 transition-opacity whitespace-nowrap">
                                        الميكروفون
                                    </div>
                                </div>
                                
                                <!-- Video Control -->
                                <div class="group relative">
                                    <button id="cameraBtn" class="control-btn w-14 h-14 rounded-full flex items-center justify-center transition-all duration-300 bg-gray-700 hover:bg-gray-600 text-white">
                                        <i class="fas fa-video text-lg"></i>
                    </button>
                                    <div class="absolute -top-10 left-1/2 transform -translate-x-1/2 bg-black text-white text-xs px-2 py-1 rounded opacity-0 group-hover:opacity-100 transition-opacity whitespace-nowrap">
                                        الكاميرا
                                    </div>
                                </div>
                                
                                <!-- Screen Share Control -->
                                <div class="group relative">
                                    <button id="screenShareBtn" class="control-btn w-14 h-14 rounded-full flex items-center justify-center transition-all duration-300 bg-gray-700 hover:bg-gray-600 text-white">
                                        <i class="fas fa-desktop text-lg"></i>
                    </button>
                                    <div class="absolute -top-10 left-1/2 transform -translate-x-1/2 bg-black text-white text-xs px-2 py-1 rounded opacity-0 group-hover:opacity-100 transition-opacity whitespace-nowrap">
                                        مشاركة الشاشة
                                    </div>
                                </div>
                                
                                <!-- Raise Hand -->
                                <div class="group relative">
                                    <button id="raiseHandBtn" class="control-btn w-14 h-14 rounded-full flex items-center justify-center transition-all duration-300 bg-gray-700 hover:bg-gray-600 text-white">
                                        <i class="fas fa-hand-paper text-lg"></i>
                    </button>
                                    <div class="absolute -top-10 left-1/2 transform -translate-x-1/2 bg-black text-white text-xs px-2 py-1 rounded opacity-0 group-hover:opacity-100 transition-opacity whitespace-nowrap">
                                        رفع اليد
                </div>
            </div>
            
                                <!-- Chat Toggle -->
                                <div class="group relative">
                                    <button id="chatToggleBtn" class="control-btn w-14 h-14 rounded-full flex items-center justify-center transition-all duration-300 bg-gray-700 hover:bg-gray-600 text-white relative">
                                        <i class="fas fa-comment text-lg"></i>
                                        <span id="chatBadge" class="absolute -top-1 -right-1 bg-red-500 text-xs rounded-full w-5 h-5 flex items-center justify-center hidden text-white">0</span>
                                    </button>
                                    <div class="absolute -top-10 left-1/2 transform -translate-x-1/2 bg-black text-white text-xs px-2 py-1 rounded opacity-0 group-hover:opacity-100 transition-opacity whitespace-nowrap">
                                        المحادثة
                                    </div>
                </div>
                
                                <!-- Participants List -->
                                <div class="group relative">
                                    <button id="participantsBtn" class="control-btn w-14 h-14 rounded-full flex items-center justify-center transition-all duration-300 bg-gray-700 hover:bg-gray-600 text-white">
                                        <i class="fas fa-users text-lg"></i>
                                    </button>
                                    <div class="absolute -top-10 left-1/2 transform -translate-x-1/2 bg-black text-white text-xs px-2 py-1 rounded opacity-0 group-hover:opacity-100 transition-opacity whitespace-nowrap">
                                        المشاركون
                                    </div>
                </div>
                
                                <!-- Settings -->
                                <div class="group relative">
                                    <button id="settingsBtn" class="control-btn w-14 h-14 rounded-full flex items-center justify-center transition-all duration-300 bg-gray-700 hover:bg-gray-600 text-white">
                                        <i class="fas fa-cog text-lg"></i>
                                    </button>
                                    <div class="absolute -top-10 left-1/2 transform -translate-x-1/2 bg-black text-white text-xs px-2 py-1 rounded opacity-0 group-hover:opacity-100 transition-opacity whitespace-nowrap">
                                        الإعدادات
                                    </div>
                    </div>
                    
                                <!-- Leave Meeting -->
                                <div class="group relative">
                                    <button id="leaveBtn" class="control-btn w-14 h-14 rounded-full flex items-center justify-center transition-all duration-300 bg-red-600 hover:bg-red-500 text-white">
                                        <i class="fas fa-phone-slash text-lg"></i>
                                    </button>
                                    <div class="absolute -top-10 left-1/2 transform -translate-x-1/2 bg-black text-white text-xs px-2 py-1 rounded opacity-0 group-hover:opacity-100 transition-opacity whitespace-nowrap">
                                        مغادرة الجلسة
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    </div>
                    
                <!-- Side Panels Container -->
                <div class="flex mt-4 gap-4">
                    <!-- Chat Panel -->
                    <div id="chatPanel" class="hidden w-96 bg-white rounded-lg shadow-xl border border-gray-200 transition-all duration-300">
                        <div class="h-96 flex flex-col">
                            <!-- Chat Header -->
                            <div class="p-4 border-b border-gray-200 bg-gray-50 rounded-t-lg">
                                <div class="flex items-center justify-between">
                                    <h3 class="font-semibold text-gray-800 flex items-center gap-2">
                                        <i class="fas fa-comment text-blue-600"></i>
                                        المحادثة
                                    </h3>
                                    <button id="closeChatBtn" class="text-gray-400 hover:text-gray-600 p-1">
                                        <i class="fas fa-times"></i>
                                    </button>
                    </div>
                </div>
                            
                            <!-- Chat Messages -->
                            <div id="chatMessages" class="flex-1 p-4 overflow-y-auto bg-gray-50 space-y-3">
                                <div class="text-center text-gray-500 py-8">
                                    <i class="fas fa-comment-dots text-3xl mb-2 text-gray-300"></i>
                                    <p>لا توجد رسائل بعد</p>
                                    <p class="text-sm">ابدأ المحادثة مع المشاركين</p>
                                </div>
                            </div>
                            
                            <!-- Chat Input -->
                            <div class="p-4 border-t border-gray-200 bg-white rounded-b-lg">
                                <div class="flex gap-2">
                                    <input type="text" id="chatInput" placeholder="اكتب رسالة..." 
                                           class="flex-1 border border-gray-300 rounded-lg px-3 py-2 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent">
                                    <button id="sendChatBtn" class="bg-blue-600 text-white px-4 py-2 rounded-lg hover:bg-blue-700 transition-colors flex items-center gap-2">
                                        <i class="fas fa-paper-plane"></i>
                                        إرسال
                                    </button>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Participants Panel -->
                    <div id="participantsPanel" class="hidden w-80 bg-white rounded-lg shadow-xl border border-gray-200 transition-all duration-300">
                        <div class="h-96 flex flex-col">
                            <!-- Participants Header -->
                            <div class="p-4 border-b border-gray-200 bg-gray-50 rounded-t-lg">
                                <div class="flex items-center justify-between">
                                    <h3 class="font-semibold text-gray-800 flex items-center gap-2">
                                        <i class="fas fa-users text-blue-600"></i>
                                        المشاركون (<span id="totalParticipants">1</span>)
                                    </h3>
                                    <button id="closeParticipantsBtn" class="text-gray-400 hover:text-gray-600 p-1">
                                        <i class="fas fa-times"></i>
                                    </button>
                                </div>
                            </div>
                            
                            <!-- Participants List -->
                            <div id="participantsList" class="flex-1 overflow-y-auto">
                                <!-- Participants will be listed here -->
                            </div>
                        </div>
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

        <!-- Teacher Information -->
        <div class="bg-white rounded-lg shadow-md p-6">
            <h3 class="text-lg font-semibold text-gray-900 mb-4">معلومات المعلم</h3>
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
            @else
            <p class="text-gray-500 text-center">لم يتم تحديد المعلم</p>
            @endif
        </div>
    </div>

    <!-- Meeting Features Preview -->
    <div class="bg-white rounded-lg shadow-md p-6 mt-6">
        <h3 class="text-lg font-semibold text-gray-900 mb-4">مميزات الاجتماع</h3>
        <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
            <div class="text-center p-4 bg-gray-50 rounded-lg">
                <div class="w-12 h-12 bg-blue-100 rounded-full flex items-center justify-center mx-auto mb-3">
                    <svg class="w-6 h-6 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 10l4.553-2.276A1 1 0 0121 8.618v6.764a1 1 0 01-1.447.894L15 14M5 18h8a2 2 0 002-2V8a2 2 0 00-2-2H5a2 2 0 00-2 2v8a2 2 0 002 2z"></path>
                    </svg>
                </div>
                <h4 class="font-medium text-gray-900 mb-2">فيديو عالي الجودة</h4>
                <p class="text-sm text-gray-600">رؤية واضحة للمعلم والمشاركين</p>
            </div>
            
            <div class="text-center p-4 bg-gray-50 rounded-lg">
                <div class="w-12 h-12 bg-green-100 rounded-full flex items-center justify-center mx-auto mb-3">
                    <svg class="w-6 h-6 text-green-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 12h.01M12 12h.01M16 12h.01M21 12c0 4.418-4.03 8-9 8a9.863 9.863 0 01-4.255-.949L3 20l1.395-3.72C3.512 15.042 3 13.574 3 12c0-4.418 4.03-8 9-8s9 3.582 9 8z"></path>
                    </svg>
                </div>
                <h4 class="font-medium text-gray-900 mb-2">دردشة مباشرة</h4>
                <p class="text-sm text-gray-600">تواصل مع المعلم والطلاب</p>
            </div>
            
            <div class="text-center p-4 bg-gray-50 rounded-lg">
                <div class="w-12 h-12 bg-purple-100 rounded-full flex items-center justify-center mx-auto mb-3">
                    <svg class="w-6 h-6 text-purple-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                    </svg>
                </div>
                <h4 class="font-medium text-gray-900 mb-2">رفع اليد</h4>
                <p class="text-sm text-gray-600">اسأل أسئلة أو اطلب المساعدة</p>
            </div>
        </div>
    </div>
</div>
<style>
    /* LiveKit Meeting Styles */
    .control-button {
        @apply w-12 h-12 rounded-full bg-gray-700 text-white flex items-center justify-center transition-all duration-200 hover:bg-gray-600 focus:outline-none focus:ring-2 focus:ring-blue-500;
    }
    
    /* Modern Meeting Interface */
    .control-btn {
        border: none;
        cursor: pointer;
        transition: all 0.3s ease;
    }
    
    .control-btn:hover {
        transform: scale(1.05);
    }
    
    .control-btn.active {
        background-color: #059669 !important;
    }
    
    .control-btn.inactive {
        background-color: #dc2626 !important;
    }
    
    /* Participant Thumbnails */
    .participant-thumbnail {
        flex-shrink: 0;
        transition: all 0.3s ease;
    }
    
    .participant-thumbnail:hover {
        transform: scale(1.05);
    }
    
    .participant-thumbnail.ring-4 {
        box-shadow: 0 0 0 4px #10b981;
    }
    
    /* Local Video Container */
    #localVideoContainer {
        position: absolute;
        z-index: 10;
        transition: all 0.3s ease;
    }
    
    #localVideoContainer.pinned {
        z-index: 20;
        box-shadow: 0 0 0 3px #fbbf24;
    }
    
    /* Draggable cursor */
    .cursor-move {
        cursor: move;
    }
    
    /* Notification Styles */
    .notification {
        position: fixed;
        top: 1rem;
        right: 1rem;
        background: #1f2937;
        color: white;
        padding: 1rem 1.5rem;
        border-radius: 0.5rem;
        box-shadow: 0 10px 25px rgba(0, 0, 0, 0.3);
        z-index: 9999;
        transform: translateX(100%);
        transition: transform 0.3s ease;
        max-width: 300px;
    }
    
    .notification.show {
        transform: translateX(0);
    }
    
    .notification.success {
        background: #059669;
        border-left: 4px solid #10b981;
    }
    
    .notification.error {
        background: #dc2626;
        border-left: 4px solid #ef4444;
    }
    
    .notification.warning {
        background: #d97706;
        border-left: 4px solid #f59e0b;
    }
    
    .notification.info {
        background: #2563eb;
        border-left: 4px solid #3b82f6;
    }
    
    /* Animations */
    @keyframes pulse {
        0%, 100% { opacity: 1; }
        50% { opacity: 0.7; }
    }
    
    /* Responsive Design */
    @media (max-width: 768px) {
        #localVideoContainer {
            width: 120px !important;
            height: 90px !important;
            bottom: 80px;
            right: 1rem;
        }
        
        .participant-thumbnail {
            width: 80px !important;
            height: 60px !important;
        }
        
        .control-btn {
            width: 48px;
            height: 48px;
        }
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
    
    // Join meeting INLINE in the same page - NO REDIRECTS
    async function joinMeeting() {
        try {
            console.log('Starting robust LiveKit meeting inline...');
            
            const joinBtn = document.getElementById('joinMeetingBtn');
            const meetingContainer = document.getElementById('meetingContainer');
            const loadingState = document.getElementById('loadingState');
            const meetingInterface = document.getElementById('meetingInterface');
            
            // Update button state
            joinBtn.disabled = true;
            joinBtn.innerHTML = '<i class="fas fa-spinner fa-spin mr-2"></i>جاري الانضمام...';
            
            // Show meeting container with loading state
            meetingContainer.style.display = 'block';
            loadingState.classList.remove('hidden');
            meetingInterface.classList.add('hidden');
            
            // Get or create meeting room
            await ensureMeetingRoom();
            
            // Initialize robust LiveKit meeting inline
            await initializeMeeting();
            
        } catch (error) {
            console.error('Error starting meeting:', error);
            showNotification('فشل في بدء الاجتماع: ' + error.message, 'error');
            
            // Reset button state
            const joinBtn = document.getElementById('joinMeetingBtn');
            joinBtn.disabled = false;
            joinBtn.innerHTML = '<svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 10l4.553-2.276A1 1 0 0121 8.618v6.764a1 1 0 01-1.447.894L15 14M5 18h8a2 2 0 002-2V8a2 2 0 00-2-2H5a2 2 0 00-2 2v8a2 2 0 002 2z"></path></svg>انضم للاجتماع';
            
            // Hide meeting container
            document.getElementById('meetingContainer').style.display = 'none';
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
                    // Meeting not ready yet - show helpful message for students
                    throw new Error('الاجتماع لم يبدأ بعد. يرجى انتظار المعلم لبدء الجلسة.');
                } else if (response.status === 403) {
                    // Forbidden - user can't access this session
                    throw new Error(data.message || 'غير مصرح لك بالانضمام إلى هذه الجلسة');
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
    
        // Initialize LiveKit meeting
    async function initializeMeeting() {
        try {
            console.log('Initializing LiveKit meeting...');
            
            // Check if LiveKit is loaded
            if (typeof LiveKit === 'undefined') {
                throw new Error('LiveKit library not loaded. Please refresh the page and try again.');
            }
            
            updateConnectionStatus('connecting');
            
            // Get participant token
            const token = await getParticipantToken();
            if (!token) {
                throw new Error('Failed to get participant token');
            }
            
            // Create room
            room = new LiveKit.Room({
                adaptiveStream: true,
                dynacast: true,
                publishDefaults: {
                    simulcast: true,
                    videoEncoding: {
                        maxBitrate: 1500000,
                        maxFramerate: 30,
                    },
                },
            });
            
            // Set up event listeners
            setupRoomEventListeners();
            
            // Connect to room
            await room.connect(meetingConfig.serverUrl, token);
            
            console.log('Successfully connected to LiveKit room');
            
            // Enable local media
            await enableLocalMedia();
            
            // Hide loading and show meeting
            document.getElementById('loadingState').classList.add('hidden');
            document.getElementById('meetingInterface').classList.remove('hidden');
            
            updateConnectionStatus('connected');
            updateParticipantCount();
            startSessionTimer();
            
            showNotification('تم الاتصال بالجلسة بنجاح', 'success');
            
            console.log('Meeting interface displayed and ready!');
            
            // Local participant video setup is handled in enableLocalMedia()
            
            console.log('Meeting initialization complete');
            
            // Debug room state
            console.log('Room state after initialization:');
            console.log('- Room connected:', room.state);
            console.log('- Local participant:', room.localParticipant);
            console.log('- Local participant tracks:', {
                audio: room.localParticipant.audioTrack,
                video: room.localParticipant.videoTrack
            });
            console.log('- Participants count:', room.participants.size);
            
        } catch (error) {
            console.error('Error initializing meeting:', error);
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
                const errorData = await response.json();
                throw new Error(errorData.message || `HTTP error! status: ${response.status}`);
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

    // Global variables for session management
    let sessionStartTime = null;
    let timerInterval = null;
    let localTracks = {};
    let unreadChatCount = 0;
    let isChatOpen = false;

    // Setup robust room event listeners - FIXED FOR PARTICIPANT CONNECTION
    function setupRoomEventListeners() {
        room.on('connected', () => {
            console.log('✅ Room connected successfully');
            updateConnectionStatus('connected');
            
            // Add ALL existing participants when we connect
            console.log('Adding existing participants:', room.remoteParticipants.size);
            room.remoteParticipants.forEach(participant => {
                console.log('Adding existing participant:', participant.identity);
                addParticipant(participant, false);
            });
        });

        room.on('disconnected', () => {
            console.log('❌ Room disconnected');
            updateConnectionStatus('disconnected');
            showNotification('تم قطع الاتصال بالجلسة', 'warning');
        });

        // ✅ CRITICAL FIX: This ensures everyone sees each other when someone joins
        room.on('participantConnected', (participant) => {
            console.log('✅ NEW PARTICIPANT JOINED:', participant.identity);
            addParticipant(participant, false);
            updateParticipantCount();
            
            const name = participant.name || participant.identity;
            showNotification(`انضم ${name} للجلسة`, 'success');
        });

        room.on('participantDisconnected', (participant) => {
            console.log('❌ PARTICIPANT LEFT:', participant.identity);
            removeParticipant(participant);
            updateParticipantCount();
            
            const name = participant.name || participant.identity;
            showNotification(`غادر ${name} الجلسة`, 'info');
        });

        // ✅ CRITICAL FIX: This ensures video/audio streams are properly displayed
        room.on('trackSubscribed', (track, publication, participant) => {
            console.log('✅ TRACK SUBSCRIBED:', track.kind, 'from', participant.identity);
            handleTrackSubscribed(track, publication, participant);
        });

        room.on('trackUnsubscribed', (track, publication, participant) => {
            console.log('❌ TRACK UNSUBSCRIBED:', track.kind, 'from', participant.identity);
            handleTrackUnsubscribed(track, publication, participant);
        });

        // ✅ CRITICAL: Track published events for immediate connection
        room.on('trackPublished', (publication, participant) => {
            console.log('✅ TRACK PUBLISHED:', publication.kind, 'from', participant.identity);
        });

        room.on('activeSpeakersChanged', (speakers) => {
            console.log('🎤 Active speakers changed:', speakers.map(s => s.identity));
            handleActiveSpeakersChanged(speakers);
        });

        room.on('dataReceived', (payload, participant) => {
            handleDataReceived(payload, participant);
        });

        room.on('connectionQualityChanged', (quality, participant) => {
            updateConnectionQuality(quality, participant);
        });
    }

    // Enable local media
    async function enableLocalMedia() {
        try {
            console.log('Enabling local media...');
            
            // Enable camera with proper configuration
            const videoTrack = await LiveKit.createLocalVideoTrack({
                resolution: {
                    width: 1280,
                    height: 720,
                },
                facingMode: 'user'
            });
            await room.localParticipant.publishTrack(videoTrack, {
                name: 'camera',
                simulcast: true,
            });
            localTracks.video = videoTrack;
            
            console.log('Video track published successfully');
            
            // Enable microphone
            const audioTrack = await LiveKit.createLocalAudioTrack({
                echoCancellation: true,
                noiseSuppression: true,
                autoGainControl: true,
            });
            await room.localParticipant.publishTrack(audioTrack, {
                name: 'microphone',
            });
            localTracks.audio = audioTrack;
            
            console.log('Audio track published successfully');
            
            // Setup local video display
            setupLocalVideoDisplay();
            
            // Add local participant to participants map
            addParticipant(room.localParticipant, true);
            
            // Update control button states
            updateControlButtonStates();
            
        } catch (error) {
            console.error('Failed to enable local media:', error);
            console.error('Error details:', error);
            
            // Try to enable audio only if video fails
            try {
                if (!localTracks.audio) {
                    console.log('Attempting audio-only mode...');
                    const audioTrack = await LiveKit.createLocalAudioTrack();
                    await room.localParticipant.publishTrack(audioTrack);
                    localTracks.audio = audioTrack;
                    
                    // Add local participant without video
                    addParticipant(room.localParticipant, true);
                    updateControlButtonStates();
                    
                    showNotification('تم تفعيل الصوت فقط - فشل في تفعيل الكاميرا', 'warning');
                }
            } catch (audioError) {
                console.error('Failed to enable audio as well:', audioError);
                showNotification('فشل في تفعيل الكاميرا والميكروفون - تحقق من الأذونات', 'error');
                
                // Still add participant without media
                addParticipant(room.localParticipant, true);
                updateControlButtonStates();
            }
        }
    }
    
    // Setup local video display
    function setupLocalVideoDisplay() {
        const localVideo = document.getElementById('localVideo');
        if (localVideo && localTracks.video) {
            localTracks.video.attach(localVideo);
            console.log('Local video attached to video element');
        } else {
            console.error('Local video element not found or no video track');
        }
    }

    // Add participant to video grid
    function addParticipant(participant, isLocal = false) {
        const participantId = participant.sid || 'local';
        
        if (participants.has(participantId)) {
            console.log('Participant already exists:', participantId);
            return;
        }
        
        console.log('Adding participant:', participantId, 'isLocal:', isLocal);
        
        const name = participant.name || participant.identity || meetingConfig.participantName;
        const role = isLocal ? (meetingConfig.userRole === 'teacher' ? 'المعلم' : 'الطالب') : 'مشارك';
        const isTeacher = role.includes('معلم') || role.includes('المدير');
        
        // Add to participants map first
        const participantData = { participant, isLocal, name, role, isTeacher };
        participants.set(participantId, participantData);
        
        if (isLocal) {
            // For local participant, just ensure video element exists
            setupLocalVideo(participant);
        } else {
            // For remote participants, create thumbnail and handle main video
            createParticipantThumbnail(participantData, participantId);
            
            // Add to participants list panel
            addToParticipantsList(participantData, participantId);
            
            // Hide waiting area if visible
            const waitingArea = document.getElementById('waitingArea');
            if (waitingArea) {
                waitingArea.style.display = 'none';
            }
            
            // Check if this should be the main speaker
            checkForMainSpeaker();
        }
        
        // Update participant count
        updateParticipantCount();
        
        console.log('Participant added successfully. Total participants:', participants.size);
    }
    
    // Setup local video
    function setupLocalVideo(participant) {
        const localVideo = document.getElementById('localVideo');
        if (localVideo && localTracks.video) {
            localTracks.video.attach(localVideo);
        }
    }
    
    // Create participant thumbnail in the strip
    function createParticipantThumbnail(participantData, participantId) {
        const { participant, name, isTeacher } = participantData;
        const thumbnailsContainer = document.getElementById('participantThumbnails');
        
        const thumbnail = document.createElement('div');
        thumbnail.className = `participant-thumbnail relative bg-gray-800 rounded-lg overflow-hidden border-2 ${isTeacher ? 'border-yellow-400' : 'border-blue-400'} cursor-pointer transition-all hover:scale-105`;
        thumbnail.style.width = '120px';
        thumbnail.style.height = '90px';
        thumbnail.id = `thumbnail-${participantId}`;
        
        thumbnail.innerHTML = `
            <video autoplay playsinline class="w-full h-full object-cover"></video>
            <div class="absolute bottom-1 left-1 bg-black bg-opacity-80 text-white text-xs px-1 py-0.5 rounded">
                ${name}
            </div>
            <div class="absolute top-1 right-1 hidden" id="speaking-${participantId}">
                <div class="w-2 h-2 bg-green-400 rounded-full animate-pulse"></div>
            </div>
            <div class="absolute top-1 left-1 hidden" id="muted-${participantId}">
                <i class="fas fa-microphone-slash text-red-400 text-xs"></i>
            </div>
        `;
        
        // Add click handler to make this the main speaker
        thumbnail.addEventListener('click', () => {
            setMainSpeaker(participantId);
        });
        
        thumbnailsContainer.appendChild(thumbnail);
        
        // Update thumbnails container visibility
        const participantsStrip = document.getElementById('participantsStrip');
        if (participants.size > 1) {
            participantsStrip.classList.remove('hidden');
        }
    }
    
    // Add participant to the participants list panel
    function addToParticipantsList(participantData, participantId) {
        const { participant, name, isTeacher } = participantData;
        const participantsList = document.getElementById('participantsList');
        
        const listItem = document.createElement('div');
        listItem.className = 'participant-list-item p-3 border-b border-gray-100 flex items-center justify-between hover:bg-gray-50';
        listItem.id = `list-${participantId}`;
        
        listItem.innerHTML = `
            <div class="flex items-center gap-3">
                <div class="w-8 h-8 rounded-full bg-gradient-to-br ${isTeacher ? 'from-yellow-400 to-orange-500' : 'from-blue-400 to-blue-600'} flex items-center justify-center text-white text-sm font-semibold">
                    ${name.charAt(0)}
                </div>
                <div>
                    <div class="font-medium text-gray-900">${name}</div>
                    <div class="text-sm text-gray-500">${isTeacher ? 'المعلم' : 'الطالب'}</div>
                </div>
            </div>
            <div class="flex items-center gap-2">
                <div id="mic-status-${participantId}" class="w-2 h-2 rounded-full bg-gray-400"></div>
                <div id="camera-status-${participantId}" class="w-2 h-2 rounded-full bg-gray-400"></div>
            </div>
        `;
        
        participantsList.appendChild(listItem);
    }
    
    // Set main speaker in the large video area
    function setMainSpeaker(participantId) {
        const participantData = participants.get(participantId);
        if (!participantData) return;
        
        const speakerContainer = document.getElementById('speakerVideoContainer');
        
        // Clear current main video
        speakerContainer.innerHTML = '';
        
        // Create main video element
        const mainVideo = document.createElement('div');
        mainVideo.className = 'relative w-full h-full bg-gray-800 flex items-center justify-center';
        mainVideo.id = `main-video-${participantId}`;
        
        mainVideo.innerHTML = `
            <video autoplay playsinline class="w-full h-full object-cover"></video>
            <div class="absolute bottom-4 left-4 bg-black bg-opacity-70 text-white px-3 py-2 rounded-lg">
                <i class="fas ${participantData.isTeacher ? 'fa-chalkboard-teacher' : 'fa-user-graduate'} mr-2"></i>
                ${participantData.name}
            </div>
            <div class="absolute top-4 right-4 hidden" id="main-speaking-${participantId}">
                <div class="bg-green-500 text-white px-2 py-1 rounded-lg flex items-center gap-2">
                    <div class="w-2 h-2 bg-white rounded-full animate-pulse"></div>
                    يتحدث
                </div>
            </div>
        `;
        
        speakerContainer.appendChild(mainVideo);
        
        // Update active thumbnail styling
        document.querySelectorAll('.participant-thumbnail').forEach(thumb => {
            thumb.classList.remove('ring-4', 'ring-green-400');
        });
        
        const activeThumbnail = document.getElementById(`thumbnail-${participantId}`);
        if (activeThumbnail) {
            activeThumbnail.classList.add('ring-4', 'ring-green-400');
        }
    }
    
    // Check for main speaker (auto-select first non-local participant)
    function checkForMainSpeaker() {
        const speakerContainer = document.getElementById('speakerVideoContainer');
        const hasMainVideo = speakerContainer.querySelector('video');
        
        if (!hasMainVideo) {
            // Find first non-local participant to be main speaker
            for (const [participantId, data] of participants) {
                if (!data.isLocal) {
                    setMainSpeaker(participantId);
                    break;
                }
            }
        }
    }

    // Update connection status
    function updateConnectionStatus(status) {
        const statusEl = document.querySelector('#connectionStatus span');
        const indicatorEl = document.querySelector('#connectionStatus div');
        
        switch(status) {
            case 'connecting':
                statusEl.textContent = 'جاري الاتصال...';
                indicatorEl.className = 'w-2 h-2 bg-yellow-500 rounded-full animate-pulse';
                break;
            case 'connected':
                statusEl.textContent = 'متصل';
                indicatorEl.className = 'w-2 h-2 bg-green-500 rounded-full';
                break;
            case 'disconnected':
                statusEl.textContent = 'منقطع';
                indicatorEl.className = 'w-2 h-2 bg-red-500 rounded-full animate-pulse';
                break;
        }
    }

    // Show notification
    function showNotification(message, type = 'info', duration = 5000) {
        const notification = document.createElement('div');
        notification.className = `notification bg-white border-l-4 p-4 rounded-lg shadow-lg transform translate-x-full transition-transform duration-300`;
        
        switch(type) {
            case 'success':
                notification.classList.add('border-green-500');
                break;
            case 'error':
                notification.classList.add('border-red-500');
                break;
            case 'warning':
                notification.classList.add('border-yellow-500');
                break;
            default:
                notification.classList.add('border-blue-500');
        }
        
        const iconClass = {
            success: 'fa-check-circle text-green-500',
            error: 'fa-exclamation-circle text-red-500',
            warning: 'fa-exclamation-triangle text-yellow-500',
            info: 'fa-info-circle text-blue-500'
        }[type];
        
        notification.innerHTML = `
            <div class="flex items-center">
                <i class="fas ${iconClass} ml-3"></i>
                <span class="text-gray-800">${message}</span>
                <button onclick="this.parentElement.parentElement.remove()" class="mr-3 text-gray-400 hover:text-gray-600">
                    <i class="fas fa-times"></i>
                </button>
            </div>
        `;
        
        document.body.appendChild(notification);
        
        // Animate in
        setTimeout(() => {
            notification.classList.remove('translate-x-full');
        }, 100);
        
        // Auto remove
        setTimeout(() => {
            notification.classList.add('translate-x-full');
            setTimeout(() => notification.remove(), 300);
        }, duration);
    }

    // Additional helper functions
    function updateParticipantCount() {
        const count = room ? room.participants.size + 1 : participants.size;
        document.querySelector('#participantCount span').textContent = count;
    }

    function startSessionTimer() {
        sessionStartTime = Date.now();
        timerInterval = setInterval(updateSessionTimer, 1000);
    }

    function updateSessionTimer() {
        if (!sessionStartTime) return;
        
        const elapsed = Date.now() - sessionStartTime;
        const hours = Math.floor(elapsed / (1000 * 60 * 60));
        const minutes = Math.floor((elapsed % (1000 * 60 * 60)) / (1000 * 60));
        const seconds = Math.floor((elapsed % (1000 * 60)) / 1000);
        
        document.getElementById('sessionTimer').textContent = 
            `${hours.toString().padStart(2, '0')}:${minutes.toString().padStart(2, '0')}:${seconds.toString().padStart(2, '0')}`;
    }

    function updateControlButtonState(button, isActive) {
        if (isActive) {
            button.classList.remove('inactive');
            button.classList.add('active');
        } else {
            button.classList.remove('active');
            button.classList.add('inactive');
        }
    }

    function removeParticipant(participant) {
        const participantId = participant.sid;
        const participantData = participants.get(participantId);
        
        if (participantData) {
            participantData.container.remove();
            participants.delete(participantId);
            updateVideoGridLayout();
        }
    }

    function updateVideoGridLayout() {
        const count = participants.size;
        let gridClass = '';
        
        if (count === 1) {
            gridClass = 'grid-cols-1';
        } else if (count === 2) {
            gridClass = 'grid-cols-2';
        } else if (count <= 4) {
            gridClass = 'grid-cols-2 grid-rows-2';
        } else if (count <= 6) {
            gridClass = 'grid-cols-3 grid-rows-2';
        } else {
            gridClass = 'grid-cols-3 grid-rows-3';
        }
        
        document.getElementById('videoGrid').className = `flex-1 p-4 grid gap-4 auto-rows-fr ${gridClass}`;
    }

    function handleTrackSubscribed(track, publication, participant) {
        const participantId = participant.sid;
        const participantData = participants.get(participantId);
        
        if (!participantData) {
            console.log('Participant not found for track subscription:', participantId);
            return;
        }
        
        console.log('Track subscribed:', track.kind, 'for participant:', participantId);
        
        if (track.kind === 'video') {
            // Attach to thumbnail video
            const thumbnail = document.getElementById(`thumbnail-${participantId}`);
            if (thumbnail) {
                const thumbnailVideo = thumbnail.querySelector('video');
                if (thumbnailVideo) {
                    track.attach(thumbnailVideo);
                    console.log('Video track attached to thumbnail');
                }
            }
            
            // Attach to main video if this is the current speaker
            const mainVideo = document.getElementById(`main-video-${participantId}`);
            if (mainVideo) {
                const mainVideoElement = mainVideo.querySelector('video');
                if (mainVideoElement) {
                    track.attach(mainVideoElement);
                    console.log('Video track attached to main video');
                }
            }
            
            // Update camera status indicator
            const cameraStatus = document.getElementById(`camera-status-${participantId}`);
            if (cameraStatus) {
                cameraStatus.className = 'w-2 h-2 rounded-full bg-green-400';
            }
            
        } else if (track.kind === 'audio') {
            // Audio tracks are automatically attached for playback
            track.attach();
            console.log('Audio track attached');
            
            // Update mic status indicator
            const micStatus = document.getElementById(`mic-status-${participantId}`);
            if (micStatus) {
                micStatus.className = 'w-2 h-2 rounded-full bg-green-400';
            }
        }
    }

    function handleTrackUnsubscribed(track, publication, participant) {
        const participantId = participant.sid;
        
        console.log('Track unsubscribed:', track.kind, 'for participant:', participantId);
        
        if (track.kind === 'video') {
            // Detach from thumbnail video
            const thumbnail = document.getElementById(`thumbnail-${participantId}`);
            if (thumbnail) {
                const thumbnailVideo = thumbnail.querySelector('video');
                if (thumbnailVideo) {
                    track.detach(thumbnailVideo);
                }
            }
            
            // Detach from main video
            const mainVideo = document.getElementById(`main-video-${participantId}`);
            if (mainVideo) {
                const mainVideoElement = mainVideo.querySelector('video');
                if (mainVideoElement) {
                    track.detach(mainVideoElement);
                }
            }
            
            // Update camera status indicator
            const cameraStatus = document.getElementById(`camera-status-${participantId}`);
            if (cameraStatus) {
                cameraStatus.className = 'w-2 h-2 rounded-full bg-red-400';
            }
            
        } else if (track.kind === 'audio') {
            track.detach();
            
            // Update mic status indicator
            const micStatus = document.getElementById(`mic-status-${participantId}`);
            if (micStatus) {
                micStatus.className = 'w-2 h-2 rounded-full bg-red-400';
            }
        }
    }

    function getParticipantContainer(participant) {
        const participantId = participant.sid || 'local';
        const participantData = participants.get(participantId);
        return participantData ? participantData.container : null;
    }

    function handleActiveSpeakersChanged(speakers) {
        // Remove speaking indicator from all participants
        document.querySelectorAll('.video-container').forEach(container => {
            container.classList.remove('speaking');
        });
        
        // Add speaking indicator to active speakers
        speakers.forEach(speaker => {
            const container = getParticipantContainer(speaker);
            if (container) {
                container.classList.add('speaking');
            }
        });
    }

    function handleDataReceived(payload, participant) {
        try {
            const data = JSON.parse(new TextDecoder().decode(payload));
            
            if (data.type === 'chat') {
                addChatMessage(data.message, participant.name || participant.identity, false);
                
                if (!isChatOpen) {
                    unreadChatCount++;
                    updateChatBadge();
                }
            }
        } catch (error) {
            console.error('Failed to parse data:', error);
        }
    }

    function updateConnectionQuality(quality, participant) {
        if (participant === room.localParticipant) {
            const qualityEl = document.getElementById('connectionQuality');
            
            switch(quality) {
                case LiveKit.ConnectionQuality.EXCELLENT:
                    qualityEl.textContent = 'ممتازة';
                    qualityEl.className = 'text-green-400';
                    break;
                case LiveKit.ConnectionQuality.GOOD:
                    qualityEl.textContent = 'جيدة';
                    qualityEl.className = 'text-green-400';
                    break;
                case LiveKit.ConnectionQuality.POOR:
                    qualityEl.textContent = 'ضعيفة';
                    qualityEl.className = 'text-yellow-400';
                    break;
                default:
                    qualityEl.textContent = 'غير معروف';
                    qualityEl.className = 'text-gray-400';
            }
        }
    }

    function addChatMessage(message, senderName, isLocal) {
        // Placeholder for chat functionality
        console.log('Chat message:', message, 'from:', senderName);
    }

    function updateChatBadge() {
        const badge = document.getElementById('chatBadge');
        if (unreadChatCount > 0) {
            badge.textContent = unreadChatCount;
            badge.classList.remove('hidden');
        } else {
            badge.classList.add('hidden');
        }
    }
    
    // Set up room event listeners
    function setupRoomEventListeners() {
        // Participant connected
        room.on(LiveKit.RoomEvent.ParticipantConnected, (participant) => {
            console.log('Participant connected:', participant.identity);
            addParticipant(participant);
            updateParticipantCount();
        });
        
        // Participant disconnected
        room.on(LiveKit.RoomEvent.ParticipantDisconnected, (participant) => {
            console.log('Participant disconnected:', participant.identity);
            removeParticipant(participant);
            updateParticipantCount();
        });
        
        // Track published
        room.on(LiveKit.RoomEvent.TrackPublished, (publication, participant) => {
            console.log('Track published by:', participant.identity);
            updateParticipantTracks(participant);
        });
        
        // Track unpublished
        room.on(LiveKit.RoomEvent.TrackUnpublished, (publication, participant) => {
            console.log('Track unpublished by:', participant.identity);
            updateParticipantTracks(participant);
        });
        
        // Data received (for chat)
        room.on(LiveKit.RoomEvent.DataReceived, (payload, participant) => {
            if (payload.topic === 'chat') {
                handleChatMessage(payload, participant);
            } else if (payload.topic === 'hand-raise') {
                handleHandRaise(payload, participant);
            }
        });
    }
    
    // Initialize UI
    function initializeUI() {
        // Meeting controls
        setupMeetingControls();
        
        // Chat functionality
        setupChatFunctionality();
        
        // Participants panel functionality
        setupParticipantsPanelFunctionality();
        
        // Local video controls
        setupLocalVideoControls();
        
        // Speaking detection
        setupSpeakingDetection();
        
        console.log('UI initialized successfully');
    }
    
    // Setup meeting controls
    function setupMeetingControls() {
        // Microphone control
        document.getElementById('micBtn')?.addEventListener('click', toggleMicrophone);
        
        // Camera control
        document.getElementById('cameraBtn')?.addEventListener('click', toggleCamera);
        
        // Chat toggle
        document.getElementById('chatToggleBtn')?.addEventListener('click', () => {
            toggleChatPanel();
        });
        
        // Participants toggle
        document.getElementById('participantsBtn')?.addEventListener('click', () => {
            toggleParticipantsPanel();
        });
        
        // Raise hand
        document.getElementById('raiseHandBtn')?.addEventListener('click', () => {
            toggleRaiseHand();
        });
        
        // Screen share
        document.getElementById('screenShareBtn')?.addEventListener('click', () => {
            toggleScreenShare();
        });
        
        // Settings
        document.getElementById('settingsBtn')?.addEventListener('click', () => {
            openSettings();
        });
        
        // Leave meeting
        document.getElementById('leaveBtn')?.addEventListener('click', () => {
            confirmLeaveMeeting();
        });
    }
    
    // Toggle chat panel
    function toggleChatPanel() {
        const chatPanel = document.getElementById('chatPanel');
        const participantsPanel = document.getElementById('participantsPanel');
        
        if (chatPanel.classList.contains('hidden')) {
            chatPanel.classList.remove('hidden');
            participantsPanel.classList.add('hidden'); // Close participants if open
            
            // Mark button as active
            document.getElementById('chatToggleBtn').classList.add('bg-blue-600');
            document.getElementById('participantsBtn').classList.remove('bg-blue-600');
        } else {
            chatPanel.classList.add('hidden');
            document.getElementById('chatToggleBtn').classList.remove('bg-blue-600');
        }
    }
    
    // Toggle participants panel
    function toggleParticipantsPanel() {
        const chatPanel = document.getElementById('chatPanel');
        const participantsPanel = document.getElementById('participantsPanel');
        
        if (participantsPanel.classList.contains('hidden')) {
            participantsPanel.classList.remove('hidden');
            chatPanel.classList.add('hidden'); // Close chat if open
            
            // Mark button as active
            document.getElementById('participantsBtn').classList.add('bg-blue-600');
            document.getElementById('chatToggleBtn').classList.remove('bg-blue-600');
        } else {
            participantsPanel.classList.add('hidden');
            document.getElementById('participantsBtn').classList.remove('bg-blue-600');
        }
    }
    
    // Setup participants panel functionality
    function setupParticipantsPanelFunctionality() {
        // Close participants panel
        document.getElementById('closeParticipantsBtn')?.addEventListener('click', () => {
            document.getElementById('participantsPanel').classList.add('hidden');
            document.getElementById('participantsBtn').classList.remove('bg-blue-600');
        });
        
        // Close chat panel
        document.getElementById('closeChatBtn')?.addEventListener('click', () => {
            document.getElementById('chatPanel').classList.add('hidden');
            document.getElementById('chatToggleBtn').classList.remove('bg-blue-600');
        });
    }
    
    // Setup local video controls
    function setupLocalVideoControls() {
        const localVideoContainer = document.getElementById('localVideoContainer');
        
        // Make draggable
        makeElementDraggable(localVideoContainer);
        
        // Pin/unpin local video
        document.getElementById('pinLocalVideo')?.addEventListener('click', () => {
            toggleLocalVideoPin();
        });
        
        // Hide/show local video
        document.getElementById('hideLocalVideo')?.addEventListener('click', () => {
            toggleLocalVideoVisibility();
        });
    }
    
    // Make element draggable
    function makeElementDraggable(element) {
        if (!element) return;
        
        let isDragging = false;
        let startX, startY, startLeft, startTop;
        
        element.addEventListener('mousedown', (e) => {
            isDragging = true;
            startX = e.clientX;
            startY = e.clientY;
            startLeft = parseInt(window.getComputedStyle(element).left, 10);
            startTop = parseInt(window.getComputedStyle(element).top, 10);
            
            element.style.cursor = 'grabbing';
            e.preventDefault();
        });
        
        document.addEventListener('mousemove', (e) => {
            if (!isDragging) return;
            
            const dx = e.clientX - startX;
            const dy = e.clientY - startY;
            
            element.style.left = (startLeft + dx) + 'px';
            element.style.top = (startTop + dy) + 'px';
        });
        
        document.addEventListener('mouseup', () => {
            isDragging = false;
            element.style.cursor = 'move';
        });
    }
    
    // Toggle local video pin
    function toggleLocalVideoPin() {
        const container = document.getElementById('localVideoContainer');
        const pinBtn = document.getElementById('pinLocalVideo');
        
        if (container.classList.contains('pinned')) {
            container.classList.remove('pinned');
            pinBtn.innerHTML = '<i class="fas fa-thumbtack text-xs"></i>';
            pinBtn.title = 'تثبيت';
        } else {
            container.classList.add('pinned');
            pinBtn.innerHTML = '<i class="fas fa-thumbtack text-xs rotate-45"></i>';
            pinBtn.title = 'إلغاء التثبيت';
        }
    }
    
    // Toggle local video visibility
    function toggleLocalVideoVisibility() {
        const container = document.getElementById('localVideoContainer');
        const hideBtn = document.getElementById('hideLocalVideo');
        
        if (container.style.display === 'none') {
            container.style.display = 'block';
            hideBtn.innerHTML = '<i class="fas fa-eye-slash text-xs"></i>';
            hideBtn.title = 'إخفاء';
        } else {
            container.style.display = 'none';
            hideBtn.innerHTML = '<i class="fas fa-eye text-xs"></i>';
            hideBtn.title = 'إظهار';
        }
    }
    
    // Toggle raise hand
    function toggleRaiseHand() {
        const raiseHandBtn = document.getElementById('raiseHandBtn');
        
        if (raiseHandBtn.classList.contains('bg-yellow-500')) {
            // Lower hand
            raiseHandBtn.classList.remove('bg-yellow-500');
            raiseHandBtn.classList.add('bg-gray-700');
            showNotification('تم خفض اليد', 'info');
        } else {
            // Raise hand
            raiseHandBtn.classList.remove('bg-gray-700');
            raiseHandBtn.classList.add('bg-yellow-500');
            showNotification('تم رفع اليد - في انتظار المعلم', 'info');
        }
    }
    
    // Toggle screen share
    function toggleScreenShare() {
        const screenShareBtn = document.getElementById('screenShareBtn');
        
        if (screenShareBtn.classList.contains('bg-green-600')) {
            // Stop screen share
            stopScreenShare();
        } else {
            // Start screen share
            startScreenShare();
        }
    }
    
    // Start screen share
    async function startScreenShare() {
        try {
            const screenTrack = await LiveKit.createLocalScreenShareTrack({
                audio: true,
            });
            
            await room.localParticipant.publishTrack(screenTrack);
            
            const screenShareBtn = document.getElementById('screenShareBtn');
            screenShareBtn.classList.remove('bg-gray-700');
            screenShareBtn.classList.add('bg-green-600');
            
            showNotification('تم بدء مشاركة الشاشة', 'success');
            
        } catch (error) {
            console.error('Error starting screen share:', error);
            showNotification('فشل في مشاركة الشاشة', 'error');
        }
    }
    
    // Stop screen share
    async function stopScreenShare() {
        try {
            const screenTrack = room.localParticipant.getTrackPublication('screen');
            if (screenTrack) {
                await room.localParticipant.unpublishTrack(screenTrack.track);
            }
            
            const screenShareBtn = document.getElementById('screenShareBtn');
            screenShareBtn.classList.remove('bg-green-600');
            screenShareBtn.classList.add('bg-gray-700');
            
            showNotification('تم إيقاف مشاركة الشاشة', 'info');
            
        } catch (error) {
            console.error('Error stopping screen share:', error);
        }
    }
    
    // Open settings
    function openSettings() {
        showNotification('الإعدادات قريباً', 'info');
    }
    
    // Confirm leave meeting
    function confirmLeaveMeeting() {
        if (confirm('هل تريد مغادرة الجلسة؟')) {
            leaveMeeting();
        }
    }
    
    // Update control button states based on current track status
    function updateControlButtonStates() {
        if (!room || !room.localParticipant) return;
        
        const micBtn = document.getElementById('micBtn');
        const cameraBtn = document.getElementById('cameraBtn');
        
        // Update microphone button
        if (micBtn) {
            if (localTracks.audio && !localTracks.audio.isMuted) {
                micBtn.classList.remove('bg-red-600');
                micBtn.classList.add('bg-gray-700');
        } else {
                micBtn.classList.remove('bg-gray-700');
                micBtn.classList.add('bg-red-600');
            }
        }
        
        // Update camera button
        if (cameraBtn) {
            if (localTracks.video && !localTracks.video.isMuted) {
                cameraBtn.classList.remove('bg-red-600');
                cameraBtn.classList.add('bg-gray-700');
        } else {
                cameraBtn.classList.remove('bg-gray-700');
                cameraBtn.classList.add('bg-red-600');
            }
        }
    }
    
    // Toggle microphone
    async function toggleMicrophone() {
        if (!localTracks.audio) {
            console.log('No audio track to toggle');
            return;
        }
        
        try {
            if (localTracks.audio.isMuted) {
                await localTracks.audio.unmute();
                showNotification('تم تفعيل الميكروفون', 'success');
            } else {
                await localTracks.audio.mute();
                showNotification('تم كتم الميكروفون', 'info');
            }
            updateControlButtonStates();
        } catch (error) {
            console.error('Error toggling microphone:', error);
            showNotification('خطأ في تشغيل الميكروفون', 'error');
        }
    }
    
    // Toggle camera
    async function toggleCamera() {
        if (!localTracks.video) {
            console.log('No video track to toggle');
            return;
        }
        
        try {
            if (localTracks.video.isMuted) {
                await localTracks.video.unmute();
                showNotification('تم تفعيل الكاميرا', 'success');
            } else {
                await localTracks.video.mute();
                showNotification('تم إيقاف الكاميرا', 'info');
            }
            updateControlButtonStates();
        } catch (error) {
            console.error('Error toggling camera:', error);
            showNotification('خطأ في تشغيل الكاميرا', 'error');
        }
    }
    
    // REMOVED: Old addLocalParticipant function - replaced by enableLocalMedia() and setupLocalVideoDisplay()
    /*async function addLocalParticipant() {
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
                console.log('Enabling camera and microphone...');
                
                // Get user media (camera and microphone)
                const stream = await navigator.mediaDevices.getUserMedia({
                    video: true,
                    audio: true
                });
                console.log('Got user media stream:', stream);
                
                // Wait a bit more to ensure room is stable
                await new Promise(resolve => setTimeout(resolve, 500));
                
                console.log('About to publish video track...');
                console.log('Video track:', stream.getVideoTracks()[0]);
                console.log('Local participant state:', localParticipant);
                console.log('Room state:', room.state);
                
                // Publish the tracks
                try {
                    await localParticipant.publishTrack(stream.getVideoTracks()[0]);
                    console.log('Video track published');
                } catch (videoError) {
                    console.error('Error publishing video track:', videoError);
                    throw new Error('Video track publishing failed: ' + (videoError.message || videoError.toString()));
                }
                
                console.log('About to publish audio track...');
                console.log('Audio track:', stream.getAudioTracks()[0]);
                
                try {
                    await localParticipant.publishTrack(stream.getAudioTracks()[0]);
                    console.log('Audio track published');
                } catch (audioError) {
                    console.error('Error publishing audio track:', audioError);
                    throw new Error('Audio track publishing failed: ' + (audioError.message || audioError.toString()));
                }
                
                // Attach local video to the video element
                videoTag.srcObject = stream;
                console.log('Local video stream attached');
                
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
                
                let errorMessage = 'فشل في تفعيل الكاميرا أو الميكروفون';
                if (error.message) {
                    errorMessage += ': ' + error.message;
                } else if (error.name) {
                    errorMessage += ': ' + error.name;
                } else {
                    errorMessage += ': ' + error.toString();
                }
                
                alert(errorMessage);
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
    }*/
    
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
                <div id="mic-${participant.identity}" class="status-indicator bg-gray-400 w-3 h-3 rounded-full"></div>
                <div id="video-${participant.identity}-status" class="status-indicator bg-gray-400 w-3 h-3 rounded-full"></div>
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
            // Use track publication status instead of non-existent methods
            const audioPublication = participant.getTrackPublication(LiveKit.Track.Source.Microphone);
            micIndicator.className = `status-indicator ${audioPublication && !audioPublication.isMuted ? 'bg-green-500' : 'bg-red-500'} w-3 h-3 rounded-full`;
        }
        
        if (videoIndicator) {
            const videoPublication = participant.getTrackPublication(LiveKit.Track.Source.Camera);
            videoIndicator.className = `status-indicator ${videoPublication && !videoPublication.isMuted ? 'bg-green-500' : 'bg-red-500'} w-3 h-3 rounded-full`;
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
            
            // Hide meeting container
            document.getElementById('meetingContainer').style.display = 'none';
            
            // Reset button state
            const joinMeetingBtn = document.getElementById('joinMeetingBtn');
            joinMeetingBtn.disabled = false;
            joinMeetingBtn.textContent = 'انضم للاجتماع';
            
            // Clear participants
            participants.clear();
            
            // Reset UI
            document.getElementById('videoGrid').innerHTML = '';
            document.getElementById('participantsList').innerHTML = '';
            document.getElementById('chatMessages').innerHTML = '';
        }
    }
    
    // Check if LiveKit script is loaded
    document.addEventListener('DOMContentLoaded', function() {
        // Check if LiveKit script is loaded
        if (typeof LiveKit === 'undefined') {
            console.log('LiveKit script not loaded yet, waiting...');
            // Wait a bit more for the script to load
            setTimeout(() => {
                if (typeof LiveKit === 'undefined') {
                    console.error('LiveKit script failed to load');
                    document.getElementById('joinMeetingBtn').disabled = true;
                    document.getElementById('joinMeetingBtn').textContent = 'LiveKit غير متوفر';
                } else {
                    console.log('LiveKit script loaded successfully');
                }
            }, 2000);
        } else {
            console.log('LiveKit script loaded successfully');
        }
    });
</script>

@endsection
