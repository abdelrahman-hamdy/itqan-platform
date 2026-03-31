{{--
    System Status Component
    Shows camera, microphone, network, and browser compatibility status
--}}

@props([
    'userType' => 'student'
])

@php
    $componentId = 'system-status-' . uniqid();
@endphp

<!-- System Status -->
<div class="system-status bg-gray-50 rounded-lg p-4" id="{{ $componentId }}">
    <h3 class="text-sm font-semibold text-gray-900 mb-3 flex items-center gap-2">
        <i class="ri-shield-check-line text-gray-600"></i>
        {{ __('meetings.system.title') }}
    </h3>
    <div class="space-y-3">
        <!-- Camera Permission -->
        <div class="flex items-center justify-between p-3 bg-white rounded-lg border border-gray-200">
            <div class="flex items-center gap-3">
                <div class="w-8 h-8 rounded-full flex items-center justify-center bg-gray-100" data-role="camera-icon">
                    <i class="ri-loader-4-line text-gray-400 animate-spin"></i>
                </div>
                <div>
                    <div class="text-sm font-medium text-gray-900">{{ __('meetings.system.camera') }}</div>
                    <div class="text-xs text-gray-600" data-role="camera-text">{{ __('meetings.system.unknown') }}</div>
                </div>
            </div>
            {{-- Button for 'prompt' state --}}
            <button data-role="camera-btn" class="px-3 py-1 text-xs font-medium rounded-full bg-blue-100 text-blue-700 hover:bg-blue-200 transition-colors cursor-pointer hidden">
                {{ __('meetings.system.grant_permission') }}
            </button>
            {{-- Hint for 'denied' state: guide user to browser settings --}}
            <button data-role="camera-reset" onclick="window._sysStatusOpenSiteSettings()" class="px-3 py-1 text-xs font-medium rounded-full bg-orange-100 text-orange-700 hover:bg-orange-200 transition-colors cursor-pointer hidden">
                {{ __('meetings.system.reset_in_browser') }}
            </button>
        </div>

        <!-- Microphone Permission -->
        <div class="flex items-center justify-between p-3 bg-white rounded-lg border border-gray-200">
            <div class="flex items-center gap-3">
                <div class="w-8 h-8 rounded-full flex items-center justify-center bg-gray-100" data-role="mic-icon">
                    <i class="ri-loader-4-line text-gray-400 animate-spin"></i>
                </div>
                <div>
                    <div class="text-sm font-medium text-gray-900">{{ __('meetings.system.microphone') }}</div>
                    <div class="text-xs text-gray-600" data-role="mic-text">{{ __('meetings.system.unknown') }}</div>
                </div>
            </div>
            <button data-role="mic-btn" class="px-3 py-1 text-xs font-medium rounded-full bg-blue-100 text-blue-700 hover:bg-blue-200 transition-colors cursor-pointer hidden">
                {{ __('meetings.system.grant_permission') }}
            </button>
            <button data-role="mic-reset" onclick="window._sysStatusOpenSiteSettings()" class="px-3 py-1 text-xs font-medium rounded-full bg-orange-100 text-orange-700 hover:bg-orange-200 transition-colors cursor-pointer hidden">
                {{ __('meetings.system.reset_in_browser') }}
            </button>
        </div>

        <!-- Network Status -->
        <div class="flex items-center justify-between p-3 bg-white rounded-lg border border-gray-200">
            <div class="flex items-center gap-3">
                <div class="w-8 h-8 rounded-full flex items-center justify-center bg-gray-100" data-role="network-icon">
                    <i class="ri-wifi-line text-gray-400"></i>
                </div>
                <div>
                    <div class="text-sm font-medium text-gray-900">{{ __('meetings.system.connection_status') }}</div>
                    <div class="text-xs text-gray-600" data-role="network-text">{{ __('meetings.system.unknown') }}</div>
                </div>
            </div>
        </div>

        <!-- Browser Compatibility -->
        <div class="flex items-center justify-between p-3 bg-white rounded-lg border border-gray-200">
            <div class="flex items-center gap-3">
                <div class="w-8 h-8 rounded-full flex items-center justify-center bg-gray-100" data-role="browser-icon">
                    <i class="ri-global-line text-gray-400"></i>
                </div>
                <div>
                    <div class="text-sm font-medium text-gray-900">{{ __('meetings.system.browser_compatibility') }}</div>
                    <div class="text-xs text-gray-600" data-role="browser-text">{{ __('meetings.system.unknown') }}</div>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
(function() {
    var root = document.getElementById('{{ $componentId }}');
    if (!root) return;
    var el = function(sel) { return root.querySelector('[data-role="' + sel + '"]'); };

    var t = {
        allowed: @json(__('meetings.system.allowed')),
        denied: @json(__('meetings.system.denied')),
        needs_permission: @json(__('meetings.system.needs_permission')),
        unknown: @json(__('meetings.system.unknown')),
        connected: @json(__('meetings.system.connected')),
        not_connected: @json(__('meetings.system.not_connected')),
        compatible: @json(__('meetings.system.compatible')),
        not_compatible: @json(__('meetings.system.not_compatible')),
    };

    function updateStatus(type, state) {
        var icon = el(type + '-icon');
        var text = el(type + '-text');
        var btn = el(type + '-btn');
        var resetBtn = el(type + '-reset');
        if (!icon || !text) return;

        icon.className = 'w-8 h-8 rounded-full flex items-center justify-center';
        text.className = 'text-xs';

        if (state === 'granted') {
            icon.classList.add('bg-green-100');
            icon.innerHTML = '<i class="ri-check-line text-green-600"></i>';
            text.classList.add('text-green-600');
            text.textContent = t.allowed;
            if (btn) btn.classList.add('hidden');
            if (resetBtn) resetBtn.classList.add('hidden');
        } else if (state === 'denied') {
            icon.classList.add('bg-red-100');
            icon.innerHTML = '<i class="ri-close-line text-red-600"></i>';
            text.classList.add('text-red-600');
            text.textContent = t.denied;
            // Show reset button, hide grant button (getUserMedia can't override browser denial)
            if (btn) btn.classList.add('hidden');
            if (resetBtn) resetBtn.classList.remove('hidden');
        } else if (state === 'prompt') {
            icon.classList.add('bg-yellow-100');
            icon.innerHTML = '<i class="ri-question-line text-yellow-600"></i>';
            text.classList.add('text-yellow-600');
            text.textContent = t.needs_permission;
            // Show grant button (getUserMedia will trigger the browser prompt)
            if (btn) btn.classList.remove('hidden');
            if (resetBtn) resetBtn.classList.add('hidden');
        } else {
            icon.classList.add('bg-gray-100');
            icon.innerHTML = '<i class="ri-loader-4-line text-gray-400 animate-spin"></i>';
            text.classList.add('text-gray-600');
            text.textContent = t.unknown;
            if (btn) btn.classList.add('hidden');
            if (resetBtn) resetBtn.classList.add('hidden');
        }
    }

    async function checkPermission(type, name) {
        try {
            var result = await navigator.permissions.query({ name: name });
            updateStatus(type, result.state);
            result.addEventListener('change', function() { updateStatus(type, result.state); });
        } catch (e) {
            updateStatus(type, 'prompt');
        }
    }

    async function requestMedia(type, constraints) {
        try {
            var stream = await navigator.mediaDevices.getUserMedia(constraints);
            updateStatus(type, 'granted');
            stream.getTracks().forEach(function(track) { track.stop(); });
        } catch (e) {
            updateStatus(type, 'denied');
        }
    }

    function updateNetwork() {
        var icon = el('network-icon');
        var text = el('network-text');
        if (!icon || !text) return;
        icon.className = 'w-8 h-8 rounded-full flex items-center justify-center';
        text.className = 'text-xs';
        if (navigator.onLine) {
            icon.classList.add('bg-green-100');
            icon.innerHTML = '<i class="ri-wifi-line text-green-600"></i>';
            text.classList.add('text-green-600');
            text.textContent = t.connected;
        } else {
            icon.classList.add('bg-red-100');
            icon.innerHTML = '<i class="ri-wifi-off-line text-red-600"></i>';
            text.classList.add('text-red-600');
            text.textContent = t.not_connected;
        }
    }

    // Browser compatibility
    var compatible = !!(navigator.mediaDevices && navigator.mediaDevices.getUserMedia && (window.RTCPeerConnection || window.webkitRTCPeerConnection));
    var bIcon = el('browser-icon');
    var bText = el('browser-text');
    if (bIcon && bText) {
        bIcon.className = 'w-8 h-8 rounded-full flex items-center justify-center';
        bText.className = 'text-xs';
        if (compatible) {
            bIcon.classList.add('bg-green-100');
            bIcon.innerHTML = '<i class="ri-check-line text-green-600"></i>';
            bText.classList.add('text-green-600');
            bText.textContent = t.compatible;
        } else {
            bIcon.classList.add('bg-red-100');
            bIcon.innerHTML = '<i class="ri-error-warning-line text-red-600"></i>';
            bText.classList.add('text-red-600');
            bText.textContent = t.not_compatible;
        }
    }

    // Initialize
    checkPermission('camera', 'camera');
    checkPermission('mic', 'microphone');
    updateNetwork();
    window.addEventListener('online', updateNetwork);
    window.addEventListener('offline', updateNetwork);

    // Grant button: triggers browser permission prompt (only works when state is 'prompt')
    var camBtn = el('camera-btn');
    var micBtn = el('mic-btn');
    if (camBtn) camBtn.addEventListener('click', function() { requestMedia('camera', { video: true }); });
    if (micBtn) micBtn.addEventListener('click', function() { requestMedia('mic', { audio: true }); });

    // Reset button: opens Chrome site settings page (only way to un-deny permissions)
    window._sysStatusOpenSiteSettings = function() {
        // Chrome: open site settings for current origin
        window.open('chrome://settings/content/siteDetails?site=' + encodeURIComponent(window.location.origin), '_blank');
        // Chrome blocks chrome:// URLs from JS, so also show a toast/alert as fallback
        setTimeout(function() {
            if (window.toast) {
                window.toast.show({ type: 'info', message: @json(__('meetings.system.click_lock_icon')), duration: 8000 });
            } else {
                alert(@json(__('meetings.system.click_lock_icon')));
            }
        }, 300);
    };
})();
</script>
