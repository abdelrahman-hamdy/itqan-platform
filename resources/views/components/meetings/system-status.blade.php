{{--
    System Status Component
    Shows camera, microphone, network, and browser compatibility status
--}}

@props([
    'userType' => 'student'
])

<!-- System Status -->
<div class="system-status bg-gray-50 rounded-lg p-4">
    <h3 class="text-sm font-semibold text-gray-900 mb-3 flex items-center gap-2">
        <i class="ri-shield-check-line text-gray-600"></i>
        حالة النظام
    </h3>
    <div class="space-y-3">
        <!-- Camera Permission -->
        <div class="flex items-center justify-between p-3 bg-white rounded-lg border border-gray-200">
            <div class="flex items-center gap-3">
                <div class="w-8 h-8 rounded-full flex items-center justify-center" id="camera-status-icon">
                    <i class="ri-camera-line text-gray-400"></i>
                </div>
                <div>
                    <div class="text-sm font-medium text-gray-900">كاميرا المتصفح</div>
                    <div class="text-xs text-gray-600" id="camera-status-text">جاري التحقق...</div>
                </div>
            </div>
            <button id="camera-permission-btn" class="px-3 py-1 text-xs font-medium rounded-full bg-blue-100 text-blue-700 hover:bg-blue-200 transition-colors hidden">
                منح الإذن
            </button>
        </div>

        <!-- Microphone Permission -->
        <div class="flex items-center justify-between p-3 bg-white rounded-lg border border-gray-200">
            <div class="flex items-center gap-3">
                <div class="w-8 h-8 rounded-full flex items-center justify-center" id="mic-status-icon">
                    <i class="ri-mic-line text-gray-400"></i>
                </div>
                <div>
                    <div class="text-sm font-medium text-gray-900">ميكروفون المتصفح</div>
                    <div class="text-xs text-gray-600" id="mic-status-text">جاري التحقق...</div>
                </div>
            </div>
            <button id="mic-permission-btn" class="px-3 py-1 text-xs font-medium rounded-full bg-blue-100 text-blue-700 hover:bg-blue-200 transition-colors hidden">
                منح الإذن
            </button>
        </div>

        <!-- Network Status -->
        <div class="flex items-center justify-between p-3 bg-white rounded-lg border border-gray-200">
            <div class="flex items-center gap-3">
                <div class="w-8 h-8 rounded-full flex items-center justify-center" id="network-status-icon">
                    <i class="ri-wifi-line text-gray-400"></i>
                </div>
                <div>
                    <div class="text-sm font-medium text-gray-900">حالة الاتصال</div>
                    <div class="text-xs text-gray-600" id="network-status-text">جاري التحقق...</div>
                </div>
            </div>
            <div class="text-xs text-gray-500" id="network-speed"></div>
        </div>

        <!-- Browser Compatibility -->
        <div class="flex items-center justify-between p-3 bg-white rounded-lg border border-gray-200">
            <div class="flex items-center gap-3">
                <div class="w-8 h-8 rounded-full flex items-center justify-center" id="browser-status-icon">
                    <i class="ri-global-line text-gray-400"></i>
                </div>
                <div>
                    <div class="text-sm font-medium text-gray-900">توافق المتصفح</div>
                    <div class="text-xs text-gray-600" id="browser-status-text">جاري التحقق...</div>
                </div>
            </div>
        </div>
    </div>
</div>
