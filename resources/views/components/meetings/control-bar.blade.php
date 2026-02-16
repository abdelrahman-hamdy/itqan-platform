{{--
    Meeting Control Bar Component
    Control buttons for the LiveKit meeting interface
--}}

@props([
    'userType' => 'student',
    'showRecording' => false
])

<!-- Control Bar - Always at bottom -->
<div class="control-bar bottom-0 left-0 right-0 bg-gray-800 border-t border-gray-700 shadow-lg z-11">
    <!-- Buttons container: scrolls horizontally, buttons don't shrink -->
    <div class="control-bar-buttons px-4 py-4 flex items-center justify-center gap-2 sm:gap-4 flex-nowrap overflow-x-auto">
    <!-- Microphone Button -->
    <button id="toggleMic" aria-label="{{ __('meetings.controls.toggle_mic') }}" class="control-button shrink-0 w-12 h-12 sm:w-14 sm:h-14 rounded-full bg-gray-600 hover:bg-gray-500 text-white flex items-center justify-center transition-all duration-200 hover:scale-105 focus:outline-none focus:ring-2 focus:ring-blue-500 active:scale-95">
        <i class="ri-mic-line text-xl" aria-hidden="true"></i>
        <span class="control-tooltip">{{ __('meetings.controls.toggle_mic') }}</span>
    </button>

    <!-- Camera Button -->
    <button id="toggleCamera" aria-label="{{ __('meetings.controls.toggle_camera') }}" class="control-button shrink-0 w-12 h-12 sm:w-14 sm:h-14 rounded-full bg-gray-600 hover:bg-gray-500 text-white flex items-center justify-center transition-all duration-200 hover:scale-105 focus:outline-none focus:ring-2 focus:ring-blue-500 active:scale-95">
        <i class="ri-vidicon-line text-xl" aria-hidden="true"></i>
        <span class="control-tooltip">{{ __('meetings.controls.toggle_camera') }}</span>
    </button>

    @if($userType === 'quran_teacher')
    <!-- Screen Share Button (Teachers Only) -->
    <button id="toggleScreenShare" aria-label="{{ __('meetings.controls.share_screen') }}" class="control-button shrink-0 w-12 h-12 sm:w-14 sm:h-14 rounded-full bg-gray-600 hover:bg-gray-500 text-white flex items-center justify-center transition-all duration-200 hover:scale-105 focus:outline-none focus:ring-2 focus:ring-blue-500 active:scale-95">
        <i class="ri-share-box-line text-xl" aria-hidden="true"></i>
        <span class="control-tooltip">{{ __('meetings.controls.share_screen') }}</span>
    </button>
    @endif

    @if($userType !== 'quran_teacher')
    <!-- Hand Raise Button -->
    <button id="toggleHandRaise" aria-label="{{ __('meetings.controls.raise_hand') }}" class="control-button shrink-0 w-12 h-12 sm:w-14 sm:h-14 rounded-full bg-gray-600 hover:bg-orange-500 text-white flex items-center justify-center transition-all duration-200 hover:scale-105 focus:outline-none focus:ring-2 focus:ring-orange-500 active:scale-95">
        <i class="ri-hand text-white text-xl" aria-hidden="true"></i>
        <span class="control-tooltip">{{ __('meetings.controls.raise_hand') }}</span>
    </button>
    @endif

    <!-- Chat Button -->
    <button id="toggleChat" aria-label="{{ __('meetings.controls.toggle_chat') }}" class="control-button shrink-0 w-12 h-12 sm:w-14 sm:h-14 rounded-full bg-gray-600 hover:bg-gray-500 text-white flex items-center justify-center transition-all duration-200 hover:scale-105 focus:outline-none focus:ring-2 focus:ring-blue-500 active:scale-95">
        <i class="ri-chat-3-line text-xl" aria-hidden="true"></i>
        <span class="control-tooltip">{{ __('meetings.controls.toggle_chat') }}</span>
    </button>

    <!-- Participants Button -->
    <button id="toggleParticipants" aria-label="{{ __('meetings.controls.toggle_participants') }}" class="control-button shrink-0 w-12 h-12 sm:w-14 sm:h-14 rounded-full bg-gray-600 hover:bg-gray-500 text-white flex items-center justify-center transition-all duration-200 hover:scale-105 focus:outline-none focus:ring-2 focus:ring-blue-500 active:scale-95">
        <i class="ri-group-line text-xl" aria-hidden="true"></i>
        <span class="control-tooltip">{{ __('meetings.controls.toggle_participants') }}</span>
    </button>

    @if($userType === 'quran_teacher')
    <!-- Raised Hands Button (Teachers Only) -->
    <button id="toggleRaisedHands" aria-label="{{ __('meetings.controls.manage_raised_hands') }}" class="control-button shrink-0 w-12 h-12 sm:w-14 sm:h-14 rounded-full bg-gray-600 hover:bg-orange-500 text-white flex items-center justify-center transition-all duration-200 hover:scale-105 focus:outline-none focus:ring-2 focus:ring-orange-500 active:scale-95 relative">
        <i class="ri-hand text-white text-xl" aria-hidden="true"></i>
        <span class="control-tooltip">{{ __('meetings.controls.manage_raised_hands') }}</span>
        <!-- Notification Badge -->
        <div id="raisedHandsNotificationBadge" class="absolute -top-1 -end-1 w-5 h-5 bg-red-500 text-white text-xs rounded-full flex items-center justify-center font-bold hidden" aria-live="polite">
            <span id="raisedHandsBadgeCount">0</span>
        </div>
    </button>
    @endif

    @if($showRecording)
    <!-- Recording Button (Interactive Courses Only) -->
    <button id="toggleRecording" aria-label="{{ __('meetings.controls.toggle_recording') }}" class="control-button shrink-0 w-12 h-12 sm:w-14 sm:h-14 rounded-full bg-gray-600 hover:bg-red-500 text-white flex items-center justify-center transition-all duration-200 hover:scale-105 focus:outline-none focus:ring-2 focus:ring-red-500 active:scale-95 relative">
        <i class="ri-record-circle-line text-xl" id="recordingIcon" aria-hidden="true"></i>
        <span class="control-tooltip">{{ __('meetings.controls.toggle_recording') }}</span>
        <div id="recordingIndicator" class="absolute -top-1 -end-1 w-3 h-3 bg-red-500 rounded-full animate-pulse hidden" aria-hidden="true"></div>
    </button>
    @endif

    @if($userType === 'quran_teacher')
    <!-- Settings Button (Teachers Only) -->
    <button id="toggleSettings" aria-label="{{ __('meetings.controls.settings') }}" class="control-button shrink-0 w-12 h-12 sm:w-14 sm:h-14 rounded-full bg-gray-600 hover:bg-gray-500 text-white flex items-center justify-center transition-all duration-200 hover:scale-105 focus:outline-none focus:ring-2 focus:ring-blue-500 active:scale-95">
        <i class="ri-settings-3-line text-xl" aria-hidden="true"></i>
        <span class="control-tooltip">{{ __('meetings.controls.settings') }}</span>
    </button>
    @endif

    <!-- Leave Button -->
    <button id="leaveMeeting" aria-label="{{ __('meetings.controls.leave_meeting') }}" class="control-button shrink-0 w-12 h-12 sm:w-14 sm:h-14 rounded-full bg-red-600 hover:bg-red-700 text-white flex items-center justify-center transition-all duration-200 hover:scale-105 focus:outline-none focus:ring-2 focus:ring-red-500 active:scale-95 relative meeting-control-button">
        <i class="ri-logout-box-line text-xl" aria-hidden="true"></i>
        <span class="control-tooltip">{{ __('meetings.controls.leave_meeting') }}</span>
    </button>
    </div>
</div>

<!-- Tooltip script: moves tooltip to body on hover to escape overflow clipping -->
<script>
(function() {
    var overrides = ['position','animation','transform','left','top','bottom','opacity','visibility','zIndex','pointerEvents','transition'];

    document.querySelectorAll('.control-bar .control-button').forEach(function(btn) {
        var tooltip = btn.querySelector('.control-tooltip');
        if (!tooltip) return;

        btn.addEventListener('mouseenter', function() {
            // Move tooltip to body so it escapes overflow-x:auto clipping
            document.body.appendChild(tooltip);

            // Only override properties that conflict; keep all stylesheet styles (bg, padding, ::after caret)
            tooltip.style.position = 'fixed';
            tooltip.style.animation = 'none';
            tooltip.style.bottom = 'auto';
            tooltip.style.zIndex = '99999';
            tooltip.style.pointerEvents = 'none';
            tooltip.style.transition = 'opacity 0.2s ease, transform 0.2s ease';
            tooltip.style.opacity = '0';
            tooltip.style.visibility = 'visible';
            tooltip.style.transform = 'none';

            // Measure size, then position centered above button
            var rect = btn.getBoundingClientRect();
            var tipW = tooltip.offsetWidth;
            var tipH = tooltip.offsetHeight;
            tooltip.style.left = (rect.left + rect.width / 2 - tipW / 2) + 'px';
            tooltip.style.top = (rect.top - tipH - 10) + 'px';

            // Trigger reflow then fade in
            tooltip.offsetHeight;
            tooltip.style.opacity = '1';
            tooltip.style.transform = 'translateY(-4px)';
        });

        btn.addEventListener('mouseleave', function() {
            tooltip.style.opacity = '0';
            tooltip.style.transform = 'translateY(0)';
            // After transition, clear overrides and move back into button
            setTimeout(function() {
                overrides.forEach(function(p) { tooltip.style[p] = ''; });
                tooltip.style.left = '';
                tooltip.style.top = '';
                btn.appendChild(tooltip);
            }, 250);
        });
    });
})();
</script>
