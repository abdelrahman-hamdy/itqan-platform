{{--
    Meeting Sidebar Panels Component
    Chat, Participants, Raised Hands, and Settings panels
--}}

@props([
    'userType' => 'student'
])

<!-- Sidebar -->
<div id="meetingSidebar" class="absolute top-0 end-0 bottom-0 w-96 bg-gray-800 border-s border-gray-700 flex flex-col transform translate-x-full rtl:-translate-x-full transition-transform duration-300 ease-in-out z-40">
    <!-- Sidebar Header -->
    <div class="bg-gray-700 px-4 py-3 flex items-center justify-between border-b border-gray-600">
        <h3 id="sidebarTitle" class="text-white font-semibold">{{ __('meetings.sidebar.chat') }}</h3>
        <button id="closeSidebarBtn" aria-label="{{ __('meetings.sidebar.close_sidebar') }}" class="text-gray-300 hover:text-white transition-colors">
            <i class="ri-close-line text-xl" aria-hidden="true"></i>
        </button>
    </div>

    <!-- Sidebar Content -->
    <div class="flex-1 overflow-hidden">
        <!-- Chat Panel -->
        <div id="chatContent" class="h-full flex flex-col">
            <!-- Chat Messages -->
            <div id="chatMessages" class="flex-1 overflow-y-auto p-4 space-y-3">
                <!-- Messages will be added here dynamically -->
            </div>

            <!-- Chat Input -->
            <div class="p-4 border-t border-gray-600">
                <div class="flex gap-2">
                    <input
                        type="text"
                        id="chatMessageInput"
                        placeholder="{{ __('meetings.sidebar.type_message') }}"
                        class="flex-1 bg-gray-700 text-white rounded-lg px-3 py-2 focus:outline-none focus:ring-2 focus:ring-blue-500"
                        onkeypress="if(event.key==='Enter') window.meeting?.controls?.sendChatMessage()">
                    <button
                        id="sendChatBtn"
                        class="bg-blue-600 hover:bg-blue-700 text-white px-4 py-2 rounded-lg transition-colors"
                        onclick="window.meeting?.controls?.sendChatMessage()">
                        <i class="ri-send-plane-line text-lg"></i>
                    </button>
                </div>
            </div>
        </div>

        <!-- Participants Panel -->
        <div id="participantsContent" class="h-full flex-col hidden">
            <div class="flex-1 overflow-y-auto p-4 space-y-2">
                <div id="participantsList">
                    <!-- Participants will be added here dynamically -->
                </div>
            </div>
        </div>

        <!-- Raised Hands Panel (Teachers Only) -->
        @if($userType === 'quran_teacher')
        <div id="raisedHandsContent" class="h-full flex-col hidden">
            <!-- Raised Hands Queue -->
            <div class="flex-1 overflow-y-auto p-4">
                <div class="flex items-center justify-between mb-4">
                    <h4 class="text-white font-medium">{{ __('meetings.sidebar.raised_hands') }}</h4>
                    <div class="flex items-center gap-2">
                        <span id="raisedHandsCount" class="bg-orange-500 text-white text-xs px-2 py-1 rounded-full">0</span>
                        <button id="clearAllRaisedHandsBtn"
                                onclick="window.meeting?.controls?.clearAllRaisedHands()"
                                class="bg-red-600 hover:bg-red-700 text-white text-xs px-3 py-1 rounded transition-colors hidden"
                                aria-label="{{ __('meetings.sidebar.hide_all') }}">
                            ✋ {{ __('meetings.sidebar.hide_all') }}
                        </button>
                    </div>
                </div>

                <div id="raisedHandsList" class="space-y-3">
                    <!-- Empty state -->
                    <div id="noRaisedHandsMessage" class="text-center text-gray-400 py-8">
                        <i class="ri-hand-heart-line text-5xl mx-auto mb-4 text-gray-500 block"></i>
                        <p>{{ __('meetings.sidebar.no_raised_hands') }}</p>
                    </div>
                    <!-- Raised hands will be added here dynamically -->
                </div>
            </div>
        </div>
        @endif

        <!-- Settings Panel -->
        <div id="settingsContent" class="h-full flex-col hidden">
            <div class="flex-1 overflow-y-auto p-4 space-y-4">
                @if($userType === 'quran_teacher')
                <!-- Teacher Controls - Simplified Design -->
                <div class="bg-gray-700 rounded-lg p-4">
                    <h4 class="text-white font-medium mb-4">{{ __('meetings.sidebar.student_controls') }}</h4>
                    <div class="space-y-4">
                        <!-- Microphone Control -->
                        <div class="flex items-center justify-between py-3 border-b border-gray-600">
                            <div class="flex items-center gap-3">
                                <div class="w-10 h-10 bg-blue-600 rounded-lg flex items-center justify-center">
                                    <i class="ri-mic-line text-white text-xl"></i>
                                </div>
                                <div>
                                    <p class="text-white font-medium text-sm">{{ __('meetings.sidebar.allow_microphone') }}</p>
                                    <p class="text-gray-400 text-xs">{{ __('meetings.sidebar.allow_mic_description') }}</p>
                                </div>
                            </div>
                            <label class="relative inline-flex items-center cursor-pointer">
                                <input type="checkbox" id="toggleAllStudentsMicSwitch" class="sr-only peer">
                                <div class="w-11 h-6 bg-gray-500 peer-focus:outline-none peer-focus:ring-4 peer-focus:ring-blue-300 rounded-full peer peer-checked:after:translate-x-full rtl:peer-checked:after:-translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-[2px] after:start-[2px] after:bg-white after:rounded-full after:h-5 after:w-5 after:transition-all peer-checked:bg-green-600"></div>
                            </label>
                        </div>

                        <!-- Camera Control -->
                        <div class="flex items-center justify-between py-3">
                            <div class="flex items-center gap-3">
                                <div class="w-10 h-10 bg-purple-600 rounded-lg flex items-center justify-center">
                                    <i class="ri-vidicon-line text-white text-xl"></i>
                                </div>
                                <div>
                                    <p class="text-white font-medium text-sm">{{ __('meetings.sidebar.allow_camera') }}</p>
                                    <p class="text-gray-400 text-xs">{{ __('meetings.sidebar.allow_camera_description') }}</p>
                                </div>
                            </div>
                            <label class="relative inline-flex items-center cursor-pointer">
                                <input type="checkbox" id="toggleAllStudentsCameraSwitch" class="sr-only peer">
                                <div class="w-11 h-6 bg-gray-500 peer-focus:outline-none peer-focus:ring-4 peer-focus:ring-purple-300 rounded-full peer peer-checked:after:translate-x-full rtl:peer-checked:after:-translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-[2px] after:start-[2px] after:bg-white after:rounded-full after:h-5 after:w-5 after:transition-all peer-checked:bg-green-600"></div>
                            </label>
                        </div>
                    </div>
                </div>
                @else
                <!-- Student Settings - Device Selection -->
                <div class="bg-gray-700 rounded-lg p-4">
                    <h4 class="text-white font-medium mb-3">{{ __('meetings.sidebar.camera_settings') }}</h4>
                    <div class="space-y-2">
                        <div>
                            <label class="text-gray-300 text-sm">{{ __('meetings.sidebar.camera_label') }}</label>
                            <select id="cameraSelect" class="w-full mt-1 bg-gray-600 text-white rounded px-3 py-2 focus:outline-none focus:ring-2 focus:ring-blue-500">
                                <option>{{ __('meetings.loading.loading_devices') }}</option>
                            </select>
                        </div>
                        <div>
                            <label class="text-gray-300 text-sm">{{ __('meetings.sidebar.quality_label') }}</label>
                            <select id="videoQualitySelect" class="w-full mt-1 bg-gray-600 text-white rounded px-3 py-2 focus:outline-none focus:ring-2 focus:ring-blue-500">
                                <option value="low">{{ __('meetings.sidebar.quality_low') }}</option>
                                <option value="medium" selected>{{ __('meetings.sidebar.quality_medium') }}</option>
                                <option value="high">{{ __('meetings.sidebar.quality_high') }}</option>
                            </select>
                        </div>
                    </div>
                </div>

                <div class="bg-gray-700 rounded-lg p-4">
                    <h4 class="text-white font-medium mb-3">{{ __('meetings.sidebar.mic_settings') }}</h4>
                    <div class="space-y-2">
                        <div>
                            <label class="text-gray-300 text-sm">{{ __('meetings.sidebar.microphone_label') }}</label>
                            <select id="microphoneSelect" class="w-full mt-1 bg-gray-600 text-white rounded px-3 py-2 focus:outline-none focus:ring-2 focus:ring-blue-500">
                                <option>{{ __('meetings.loading.loading_devices') }}</option>
                            </select>
                        </div>
                        <div class="flex items-center justify-between">
                            <span class="text-gray-300 text-sm">{{ __('meetings.sidebar.mute_on_join') }}</span>
                            <input type="checkbox" id="muteonJoinCheckbox" class="rounded">
                        </div>
                    </div>
                </div>
                @endif
            </div>
        </div>
    </div>
</div>
