{{--
    Meeting Control Bar Component
    Control buttons for the LiveKit meeting interface
--}}

@props([
    'userType' => 'student',
    'showRecording' => false
])

<!-- Control Bar - Always at bottom -->
<div class="control-bar bottom-0 left-0 right-0 bg-gray-800 border-t border-gray-700 px-4 py-4 flex items-center justify-center gap-2 sm:gap-4 shadow-lg flex-nowrap overflow-x-auto z-11">
    <!-- Microphone Button -->
    <button id="toggleMic" aria-label="{{ __('meetings.controls.toggle_mic') }}" class="control-button shrink-0 w-12 h-12 sm:w-14 sm:h-14 rounded-full bg-gray-600 hover:bg-gray-500 text-white flex items-center justify-center transition-all duration-200 hover:scale-105 focus:outline-none focus:ring-2 focus:ring-blue-500 active:scale-95">
        <i class="ri-mic-line text-xl" aria-hidden="true"></i>
        <div class="control-tooltip">{{ __('meetings.controls.toggle_mic') }}</div>
    </button>

    <!-- Camera Button -->
    <button id="toggleCamera" aria-label="{{ __('meetings.controls.toggle_camera') }}" class="control-button shrink-0 w-12 h-12 sm:w-14 sm:h-14 rounded-full bg-gray-600 hover:bg-gray-500 text-white flex items-center justify-center transition-all duration-200 hover:scale-105 focus:outline-none focus:ring-2 focus:ring-blue-500 active:scale-95">
        <i class="ri-vidicon-line text-xl" aria-hidden="true"></i>
        <div class="control-tooltip">{{ __('meetings.controls.toggle_camera') }}</div>
    </button>

    @if($userType === 'quran_teacher')
    <!-- Screen Share Button (Teachers Only) -->
    <button id="toggleScreenShare" aria-label="{{ __('meetings.controls.share_screen') }}" class="control-button shrink-0 w-12 h-12 sm:w-14 sm:h-14 rounded-full bg-gray-600 hover:bg-gray-500 text-white flex items-center justify-center transition-all duration-200 hover:scale-105 focus:outline-none focus:ring-2 focus:ring-blue-500 active:scale-95">
        <i class="ri-share-box-line text-xl" aria-hidden="true"></i>
        <div class="control-tooltip">{{ __('meetings.controls.share_screen') }}</div>
    </button>
    @endif

    @if($userType !== 'quran_teacher')
    <!-- Hand Raise Button -->
    <button id="toggleHandRaise" aria-label="{{ __('meetings.controls.raise_hand') }}" class="control-button shrink-0 w-12 h-12 sm:w-14 sm:h-14 rounded-full bg-gray-600 hover:bg-orange-500 text-white flex items-center justify-center transition-all duration-200 hover:scale-105 focus:outline-none focus:ring-2 focus:ring-orange-500 active:scale-95">
        <i class="ri-hand text-white text-xl" aria-hidden="true"></i>
        <div class="control-tooltip">{{ __('meetings.controls.raise_hand') }}</div>
    </button>
    @endif

    <!-- Chat Button -->
    <button id="toggleChat" aria-label="{{ __('meetings.controls.toggle_chat') }}" class="control-button shrink-0 w-12 h-12 sm:w-14 sm:h-14 rounded-full bg-gray-600 hover:bg-gray-500 text-white flex items-center justify-center transition-all duration-200 hover:scale-105 focus:outline-none focus:ring-2 focus:ring-blue-500 active:scale-95">
        <i class="ri-chat-3-line text-xl" aria-hidden="true"></i>
        <div class="control-tooltip">{{ __('meetings.controls.toggle_chat') }}</div>
    </button>

    <!-- Participants Button -->
    <button id="toggleParticipants" aria-label="{{ __('meetings.controls.toggle_participants') }}" class="control-button shrink-0 w-12 h-12 sm:w-14 sm:h-14 rounded-full bg-gray-600 hover:bg-gray-500 text-white flex items-center justify-center transition-all duration-200 hover:scale-105 focus:outline-none focus:ring-2 focus:ring-blue-500 active:scale-95">
        <i class="ri-group-line text-xl" aria-hidden="true"></i>
        <div class="control-tooltip">{{ __('meetings.controls.toggle_participants') }}</div>
    </button>

    @if($userType === 'quran_teacher')
    <!-- Raised Hands Button (Teachers Only) -->
    <button id="toggleRaisedHands" aria-label="{{ __('meetings.controls.manage_raised_hands') }}" class="control-button shrink-0 w-12 h-12 sm:w-14 sm:h-14 rounded-full bg-gray-600 hover:bg-orange-500 text-white flex items-center justify-center transition-all duration-200 hover:scale-105 focus:outline-none focus:ring-2 focus:ring-orange-500 active:scale-95 relative">
        <i class="ri-hand text-white text-xl" aria-hidden="true"></i>
        <!-- Notification Badge -->
        <div id="raisedHandsNotificationBadge" class="absolute -top-1 -end-1 w-5 h-5 bg-red-500 text-white text-xs rounded-full flex items-center justify-center font-bold hidden" aria-live="polite">
            <span id="raisedHandsBadgeCount">0</span>
        </div>
        <div class="control-tooltip">{{ __('meetings.controls.manage_raised_hands') }}</div>
    </button>
    @endif

    @if($showRecording)
    <!-- Recording Button (Interactive Courses Only) -->
    <button id="toggleRecording" aria-label="{{ __('meetings.controls.toggle_recording') }}" class="control-button shrink-0 w-12 h-12 sm:w-14 sm:h-14 rounded-full bg-gray-600 hover:bg-red-500 text-white flex items-center justify-center transition-all duration-200 hover:scale-105 focus:outline-none focus:ring-2 focus:ring-red-500 active:scale-95 relative">
        <i class="ri-record-circle-line text-xl" id="recordingIcon" aria-hidden="true"></i>
        <div id="recordingIndicator" class="absolute -top-1 -end-1 w-3 h-3 bg-red-500 rounded-full animate-pulse hidden" aria-hidden="true"></div>
        <div class="control-tooltip">{{ __('meetings.controls.toggle_recording') }}</div>
    </button>
    @endif

    @if($userType === 'quran_teacher')
    <!-- Settings Button (Teachers Only) -->
    <button id="toggleSettings" aria-label="{{ __('meetings.controls.settings') }}" class="control-button shrink-0 w-12 h-12 sm:w-14 sm:h-14 rounded-full bg-gray-600 hover:bg-gray-500 text-white flex items-center justify-center transition-all duration-200 hover:scale-105 focus:outline-none focus:ring-2 focus:ring-blue-500 active:scale-95">
        <i class="ri-settings-3-line text-xl" aria-hidden="true"></i>
        <div class="control-tooltip">{{ __('meetings.controls.settings') }}</div>
    </button>
    @endif

    <!-- Leave Button -->
    <button id="leaveMeeting" aria-label="{{ __('meetings.controls.leave_meeting') }}" class="control-button shrink-0 w-12 h-12 sm:w-14 sm:h-14 rounded-full bg-red-600 hover:bg-red-700 text-white flex items-center justify-center transition-all duration-200 hover:scale-105 focus:outline-none focus:ring-2 focus:ring-red-500 active:scale-95 relative meeting-control-button">
        <i class="ri-logout-box-line text-xl" aria-hidden="true"></i>
        <div class="control-tooltip">{{ __('meetings.controls.leave_meeting') }}</div>
    </button>
</div>
