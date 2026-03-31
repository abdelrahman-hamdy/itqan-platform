{{--
    System Status Component
    Shows camera, microphone, network, and browser compatibility status
--}}

@props([
    'userType' => 'student'
])

<!-- System Status -->
<div class="system-status bg-gray-50 rounded-lg p-4"
     x-data="{
        cameraState: 'checking',
        micState: 'checking',
        networkOnline: navigator.onLine,
        browserCompatible: !!(navigator.mediaDevices && navigator.mediaDevices.getUserMedia && (window.RTCPeerConnection || window.webkitRTCPeerConnection)),
        _onOnline: null,
        _onOffline: null,

        async init() {
            await Promise.all([
                this.checkCameraPermission(),
                this.checkMicPermission(),
            ]);
            this._onOnline = () => { this.networkOnline = true; };
            this._onOffline = () => { this.networkOnline = false; };
            window.addEventListener('online', this._onOnline);
            window.addEventListener('offline', this._onOffline);
        },

        destroy() {
            window.removeEventListener('online', this._onOnline);
            window.removeEventListener('offline', this._onOffline);
        },

        async checkCameraPermission() {
            try {
                const result = await navigator.permissions.query({ name: 'camera' });
                this.cameraState = result.state;
                result.addEventListener('change', () => { this.cameraState = result.state; });
            } catch (e) {
                this.cameraState = 'prompt';
            }
        },

        async checkMicPermission() {
            try {
                const result = await navigator.permissions.query({ name: 'microphone' });
                this.micState = result.state;
                result.addEventListener('change', () => { this.micState = result.state; });
            } catch (e) {
                this.micState = 'prompt';
            }
        },

        async requestCamera() {
            try {
                const stream = await navigator.mediaDevices.getUserMedia({ video: true });
                this.cameraState = 'granted';
                stream.getTracks().forEach(track => track.stop());
            } catch (e) {
                this.cameraState = 'denied';
            }
        },

        async requestMic() {
            try {
                const stream = await navigator.mediaDevices.getUserMedia({ audio: true });
                this.micState = 'granted';
                stream.getTracks().forEach(track => track.stop());
            } catch (e) {
                this.micState = 'denied';
            }
        },

        stateIcon(state) {
            if (state === 'granted') return 'ri-check-line text-green-600';
            if (state === 'denied') return 'ri-close-line text-red-600';
            if (state === 'prompt') return 'ri-question-line text-yellow-600';
            return 'ri-loader-4-line text-gray-400 animate-spin';
        },

        stateBg(state) {
            if (state === 'granted') return 'bg-green-100';
            if (state === 'denied') return 'bg-red-100';
            if (state === 'prompt') return 'bg-yellow-100';
            return 'bg-gray-100';
        },

        stateText(state) {
            const t = window.meetingTranslations?.system || {};
            if (state === 'granted') return t.allowed || '{{ __('meetings.system.allowed') }}';
            if (state === 'denied') return t.denied || '{{ __('meetings.system.denied') }}';
            if (state === 'prompt') return t.needs_permission || '{{ __('meetings.system.needs_permission') }}';
            return '{{ __('meetings.system.unknown') }}';
        },

        stateTextClass(state) {
            if (state === 'granted') return 'text-xs text-green-600';
            if (state === 'denied') return 'text-xs text-red-600';
            if (state === 'prompt') return 'text-xs text-yellow-600';
            return 'text-xs text-gray-600';
        },

        showBtn(state) {
            return state === 'prompt' || state === 'denied';
        }
     }"
>
    <h3 class="text-sm font-semibold text-gray-900 mb-3 flex items-center gap-2">
        <i class="ri-shield-check-line text-gray-600"></i>
        {{ __('meetings.system.title') }}
    </h3>
    <div class="space-y-3">
        <!-- Camera Permission -->
        <div class="flex items-center justify-between p-3 bg-white rounded-lg border border-gray-200">
            <div class="flex items-center gap-3">
                <div class="w-8 h-8 rounded-full flex items-center justify-center" :class="stateBg(cameraState)">
                    <i :class="stateIcon(cameraState)"></i>
                </div>
                <div>
                    <div class="text-sm font-medium text-gray-900">{{ __('meetings.system.camera') }}</div>
                    <div :class="stateTextClass(cameraState)" x-text="stateText(cameraState)"></div>
                </div>
            </div>
            <button
                x-show="showBtn(cameraState)"
                x-cloak
                @click="requestCamera()"
                class="px-3 py-1 text-xs font-medium rounded-full bg-blue-100 text-blue-700 hover:bg-blue-200 transition-colors cursor-pointer"
            >
                {{ __('meetings.system.grant_permission') }}
            </button>
        </div>

        <!-- Microphone Permission -->
        <div class="flex items-center justify-between p-3 bg-white rounded-lg border border-gray-200">
            <div class="flex items-center gap-3">
                <div class="w-8 h-8 rounded-full flex items-center justify-center" :class="stateBg(micState)">
                    <i :class="stateIcon(micState)"></i>
                </div>
                <div>
                    <div class="text-sm font-medium text-gray-900">{{ __('meetings.system.microphone') }}</div>
                    <div :class="stateTextClass(micState)" x-text="stateText(micState)"></div>
                </div>
            </div>
            <button
                x-show="showBtn(micState)"
                x-cloak
                @click="requestMic()"
                class="px-3 py-1 text-xs font-medium rounded-full bg-blue-100 text-blue-700 hover:bg-blue-200 transition-colors cursor-pointer"
            >
                {{ __('meetings.system.grant_permission') }}
            </button>
        </div>

        <!-- Network Status -->
        <div class="flex items-center justify-between p-3 bg-white rounded-lg border border-gray-200">
            <div class="flex items-center gap-3">
                <div class="w-8 h-8 rounded-full flex items-center justify-center"
                     :class="networkOnline ? 'bg-green-100' : 'bg-red-100'">
                    <i :class="networkOnline ? 'ri-wifi-line text-green-600' : 'ri-wifi-off-line text-red-600'"></i>
                </div>
                <div>
                    <div class="text-sm font-medium text-gray-900">{{ __('meetings.system.connection_status') }}</div>
                    <div :class="networkOnline ? 'text-xs text-green-600' : 'text-xs text-red-600'"
                         x-text="networkOnline
                            ? (window.meetingTranslations?.system?.connected || '{{ __('meetings.system.connected') }}')
                            : (window.meetingTranslations?.system?.not_connected || '{{ __('meetings.system.not_connected') }}')">
                    </div>
                </div>
            </div>
        </div>

        <!-- Browser Compatibility -->
        <div class="flex items-center justify-between p-3 bg-white rounded-lg border border-gray-200">
            <div class="flex items-center gap-3">
                <div class="w-8 h-8 rounded-full flex items-center justify-center"
                     :class="browserCompatible ? 'bg-green-100' : 'bg-red-100'">
                    <i :class="browserCompatible ? 'ri-check-line text-green-600' : 'ri-error-warning-line text-red-600'"></i>
                </div>
                <div>
                    <div class="text-sm font-medium text-gray-900">{{ __('meetings.system.browser_compatibility') }}</div>
                    <div :class="browserCompatible ? 'text-xs text-green-600' : 'text-xs text-red-600'"
                         x-text="browserCompatible
                            ? (window.meetingTranslations?.system?.compatible || '{{ __('meetings.system.compatible') }}')
                            : (window.meetingTranslations?.system?.not_compatible || '{{ __('meetings.system.not_compatible') }}')">
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
