{{--
    Meeting Control Bar Component
    Control buttons for the LiveKit meeting interface
--}}

@props([
    'userType' => 'student',
    'showRecording' => false
])

<!-- Control Bar - Always at bottom -->
<div class="control-bar bottom-0 left-0 right-0 bg-gray-800 border-t border-gray-700 px-4 py-4 flex items-center justify-center gap-2 sm:gap-4 shadow-lg flex-wrap sm:flex-nowrap z-11">
    <!-- Microphone Button -->
    <button id="toggleMic" aria-label="إيقاف/تشغيل الميكروفون" class="control-button w-12 h-12 sm:w-14 sm:h-14 rounded-full bg-gray-600 hover:bg-gray-500 text-white flex items-center justify-center transition-all duration-200 hover:scale-105 focus:outline-none focus:ring-2 focus:ring-blue-500 active:scale-95">
        <i class="ri-mic-line text-xl" aria-hidden="true"></i>
        <div class="control-tooltip">إيقاف/تشغيل الميكروفون</div>
    </button>

    <!-- Camera Button -->
    <button id="toggleCamera" aria-label="إيقاف/تشغيل الكاميرا" class="control-button w-12 h-12 sm:w-14 sm:h-14 rounded-full bg-gray-600 hover:bg-gray-500 text-white flex items-center justify-center transition-all duration-200 hover:scale-105 focus:outline-none focus:ring-2 focus:ring-blue-500 active:scale-95">
        <i class="ri-vidicon-line text-xl" aria-hidden="true"></i>
        <div class="control-tooltip">إيقاف/تشغيل الكاميرا</div>
    </button>

    @if($userType === 'quran_teacher')
    <!-- Screen Share Button (Teachers Only) -->
    <button id="toggleScreenShare" aria-label="مشاركة الشاشة" class="control-button w-12 h-12 sm:w-14 sm:h-14 rounded-full bg-gray-600 hover:bg-gray-500 text-white flex items-center justify-center transition-all duration-200 hover:scale-105 focus:outline-none focus:ring-2 focus:ring-blue-500 active:scale-95">
        <i class="ri-share-box-line text-xl" aria-hidden="true"></i>
        <div class="control-tooltip">مشاركة الشاشة</div>
    </button>
    @endif

    @if($userType !== 'quran_teacher')
    <!-- Hand Raise Button -->
    <button id="toggleHandRaise" aria-label="رفع اليد" class="control-button w-12 h-12 sm:w-14 sm:h-14 rounded-full bg-gray-600 hover:bg-orange-500 text-white flex items-center justify-center transition-all duration-200 hover:scale-105 focus:outline-none focus:ring-2 focus:ring-orange-500 active:scale-95">
        <i class="ri-hand text-white text-xl" aria-hidden="true"></i>
        <div class="control-tooltip">رفع اليد</div>
    </button>
    @endif

    <!-- Chat Button -->
    <button id="toggleChat" aria-label="إظهار/إخفاء الدردشة" class="control-button w-12 h-12 sm:w-14 sm:h-14 rounded-full bg-gray-600 hover:bg-gray-500 text-white flex items-center justify-center transition-all duration-200 hover:scale-105 focus:outline-none focus:ring-2 focus:ring-blue-500 active:scale-95">
        <i class="ri-chat-3-line text-xl" aria-hidden="true"></i>
        <div class="control-tooltip">إظهار/إخفاء الدردشة</div>
    </button>

    <!-- Participants Button -->
    <button id="toggleParticipants" aria-label="إظهار/إخفاء المشاركين" class="control-button w-12 h-12 sm:w-14 sm:h-14 rounded-full bg-gray-600 hover:bg-gray-500 text-white flex items-center justify-center transition-all duration-200 hover:scale-105 focus:outline-none focus:ring-2 focus:ring-blue-500 active:scale-95">
        <i class="ri-group-line text-xl" aria-hidden="true"></i>
        <div class="control-tooltip">إظهار/إخفاء المشاركين</div>
    </button>

    @if($userType === 'quran_teacher')
    <!-- Raised Hands Button (Teachers Only) -->
    <button id="toggleRaisedHands" aria-label="إدارة الأيدي المرفوعة" class="control-button w-12 h-12 sm:w-14 sm:h-14 rounded-full bg-gray-600 hover:bg-orange-500 text-white flex items-center justify-center transition-all duration-200 hover:scale-105 focus:outline-none focus:ring-2 focus:ring-orange-500 active:scale-95 relative">
        <i class="ri-hand text-white text-xl" aria-hidden="true"></i>
        <!-- Notification Badge -->
        <div id="raisedHandsNotificationBadge" class="absolute -top-1 -right-1 w-5 h-5 bg-red-500 text-white text-xs rounded-full flex items-center justify-center font-bold hidden" aria-live="polite">
            <span id="raisedHandsBadgeCount">0</span>
        </div>
        <div class="control-tooltip">إدارة الأيدي المرفوعة</div>
    </button>
    @endif

    @if($showRecording)
    <!-- Recording Button (Interactive Courses Only) -->
    <button id="toggleRecording" aria-label="بدء/إيقاف تسجيل الدورة" class="control-button w-12 h-12 sm:w-14 sm:h-14 rounded-full bg-gray-600 hover:bg-red-500 text-white flex items-center justify-center transition-all duration-200 hover:scale-105 focus:outline-none focus:ring-2 focus:ring-red-500 active:scale-95 relative">
        <i class="ri-record-circle-line text-xl" id="recordingIcon" aria-hidden="true"></i>
        <div id="recordingIndicator" class="absolute -top-1 -right-1 w-3 h-3 bg-red-500 rounded-full animate-pulse hidden" aria-hidden="true"></div>
        <div class="control-tooltip">بدء/إيقاف تسجيل الدورة</div>
    </button>
    @endif

    @if($userType === 'quran_teacher')
    <!-- Settings Button (Teachers Only) -->
    <button id="toggleSettings" aria-label="الإعدادات" class="control-button w-12 h-12 sm:w-14 sm:h-14 rounded-full bg-gray-600 hover:bg-gray-500 text-white flex items-center justify-center transition-all duration-200 hover:scale-105 focus:outline-none focus:ring-2 focus:ring-blue-500 active:scale-95">
        <i class="ri-settings-3-line text-xl" aria-hidden="true"></i>
        <div class="control-tooltip">الإعدادات</div>
    </button>
    @endif

    <!-- Leave Button -->
    <button id="leaveMeeting" aria-label="مغادرة الجلسة" class="control-button w-12 h-12 sm:w-14 sm:h-14 rounded-full bg-red-600 hover:bg-red-700 text-white flex items-center justify-center transition-all duration-200 hover:scale-105 focus:outline-none focus:ring-2 focus:ring-red-500 active:scale-95 relative meeting-control-button">
        <i class="ri-logout-box-line text-xl" aria-hidden="true"></i>
        <div class="control-tooltip">مغادرة الجلسة</div>
    </button>
</div>
