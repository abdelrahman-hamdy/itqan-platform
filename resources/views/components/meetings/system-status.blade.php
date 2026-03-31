{{--
    System Status Component
    Shows camera, microphone, network, and browser compatibility status
--}}

@props([
    'userType' => 'student'
])

@php
    $componentId = 'system-status-' . uniqid();
    $modalId = 'reset-modal-' . uniqid();
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
            <button data-role="camera-btn" class="px-3 py-1 text-xs font-medium rounded-full bg-blue-100 text-blue-700 hover:bg-blue-200 transition-colors cursor-pointer hidden">
                {{ __('meetings.system.grant_permission') }}
            </button>
            <button data-role="camera-reset" class="px-3 py-1 text-xs font-medium rounded-full bg-orange-100 text-orange-700 hover:bg-orange-200 transition-colors cursor-pointer hidden">
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
            <button data-role="mic-reset" class="px-3 py-1 text-xs font-medium rounded-full bg-orange-100 text-orange-700 hover:bg-orange-200 transition-colors cursor-pointer hidden">
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

<!-- Permission Reset Modal -->
<div id="{{ $modalId }}" class="fixed inset-0 z-50 hidden">
    {{-- Backdrop --}}
    <div data-role="modal-backdrop" class="absolute inset-0 bg-black/40 backdrop-blur-sm transition-opacity"></div>
    {{-- Modal content --}}
    <div class="flex items-center justify-center min-h-full p-4">
        <div class="relative bg-white rounded-2xl shadow-2xl max-w-md w-full mx-auto overflow-hidden transform transition-all">
            {{-- Header --}}
            <div class="bg-gradient-to-l from-orange-500 to-amber-500 px-6 py-5 text-white">
                <div class="flex items-center justify-between">
                    <div class="flex items-center gap-3">
                        <div class="w-10 h-10 bg-white/20 rounded-xl flex items-center justify-center">
                            <i class="ri-lock-unlock-line text-xl"></i>
                        </div>
                        <h3 class="text-lg font-bold">{{ __('meetings.system.how_to_reset_title') }}</h3>
                    </div>
                    <button data-role="modal-close" class="w-8 h-8 rounded-full bg-white/20 hover:bg-white/30 flex items-center justify-center transition-colors cursor-pointer">
                        <i class="ri-close-line text-lg"></i>
                    </button>
                </div>
                <p class="text-sm text-white/80 mt-2">{{ __('meetings.system.modal_subtitle') }}</p>
            </div>
            {{-- Steps --}}
            <div class="px-6 py-5 space-y-4">
                {{-- Step 1 --}}
                <div class="flex gap-4">
                    <div class="flex-shrink-0 w-8 h-8 rounded-full bg-orange-100 text-orange-600 flex items-center justify-center font-bold text-sm">1</div>
                    <div>
                        <div class="text-sm font-semibold text-gray-900">{{ __('meetings.system.how_to_reset_step1_title') }}</div>
                        <p class="text-xs text-gray-500 mt-0.5">{{ __('meetings.system.how_to_reset_step1') }}</p>
                    </div>
                </div>
                {{-- Step 2 --}}
                <div class="flex gap-4">
                    <div class="flex-shrink-0 w-8 h-8 rounded-full bg-orange-100 text-orange-600 flex items-center justify-center font-bold text-sm">2</div>
                    <div>
                        <div class="text-sm font-semibold text-gray-900">{{ __('meetings.system.how_to_reset_step2_title') }}</div>
                        <p class="text-xs text-gray-500 mt-0.5">{{ __('meetings.system.how_to_reset_step2') }}</p>
                    </div>
                </div>
                {{-- Step 3 --}}
                <div class="flex gap-4">
                    <div class="flex-shrink-0 w-8 h-8 rounded-full bg-orange-100 text-orange-600 flex items-center justify-center font-bold text-sm">3</div>
                    <div>
                        <div class="text-sm font-semibold text-gray-900">{{ __('meetings.system.how_to_reset_step3_title') }}</div>
                        <p class="text-xs text-gray-500 mt-0.5">{{ __('meetings.system.how_to_reset_step3') }}</p>
                    </div>
                </div>
            </div>
            {{-- Footer --}}
            <div class="px-6 py-4 bg-gray-50 border-t border-gray-100">
                <button data-role="modal-close-btn" class="w-full py-2.5 bg-orange-500 hover:bg-orange-600 text-white text-sm font-semibold rounded-xl transition-colors cursor-pointer">
                    {{ __('meetings.system.modal_got_it') }}
                </button>
            </div>
        </div>
    </div>
</div>

<script>
(function() {
    var root = document.getElementById('{{ $componentId }}');
    var modal = document.getElementById('{{ $modalId }}');
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
            if (btn) btn.classList.add('hidden');
            if (resetBtn) resetBtn.classList.remove('hidden');
        } else if (state === 'prompt') {
            icon.classList.add('bg-yellow-100');
            icon.innerHTML = '<i class="ri-question-line text-yellow-600"></i>';
            text.classList.add('text-yellow-600');
            text.textContent = t.needs_permission;
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

    // Grant buttons
    var camBtn = el('camera-btn');
    var micBtn = el('mic-btn');
    if (camBtn) camBtn.addEventListener('click', function() { requestMedia('camera', { video: true }); });
    if (micBtn) micBtn.addEventListener('click', function() { requestMedia('mic', { audio: true }); });

    // Modal open/close
    function openModal() { if (modal) modal.classList.remove('hidden'); }
    function closeModal() { if (modal) modal.classList.add('hidden'); }

    var camReset = el('camera-reset');
    var micReset = el('mic-reset');
    if (camReset) camReset.addEventListener('click', openModal);
    if (micReset) micReset.addEventListener('click', openModal);

    if (modal) {
        modal.querySelector('[data-role="modal-backdrop"]').addEventListener('click', closeModal);
        modal.querySelector('[data-role="modal-close"]').addEventListener('click', closeModal);
        modal.querySelector('[data-role="modal-close-btn"]').addEventListener('click', closeModal);
    }
})();
</script>
