{{--
    Shared Scheduling Panel for Teacher & Supervisor calendars.

    Props:
    - $schedulableItemsRoute (string) — URL for fetching schedulable items
    - $recommendationsRoute  (string) — URL for fetching scheduling recommendations
    - $scheduleRoute         (string) — URL for submitting schedule
    - $teacherType           (string) — 'quran_teacher' | 'academic_teacher'
    - $tabs                  (array)  — tab key→label map
    - $teacherId             (int|null) — null for teacher view, set for supervisor
    - $calendarVarName       (string) — JS window var to refetch ('teacherCalendar' | 'supervisorCalendar')
--}}
@props([
    'schedulableItemsRoute',
    'recommendationsRoute',
    'scheduleRoute',
    'teacherType',
    'tabs',
    'teacherId' => null,
    'calendarVarName' => 'teacherCalendar',
])

<div class="mb-6" x-data="schedulingPanel()">
    <div class="bg-white rounded-xl shadow-sm border border-gray-200">

        <!-- Panel Header + Collapse Toggle — entire area is clickable -->
        <div class="flex items-center justify-between cursor-pointer select-none p-4 md:p-6"
             @click="panelOpen = !panelOpen"
             role="button" tabindex="0" @keydown.enter="panelOpen = !panelOpen">
            <div>
                <h2 class="text-lg font-bold text-gray-900 mb-0.5">
                    <template x-if="teacherType === 'quran_teacher'">
                        <span>{{ __('teacher.calendar.management_quran') }}</span>
                    </template>
                    <template x-if="teacherType === 'academic_teacher'">
                        <span>{{ __('teacher.calendar.management_academic') }}</span>
                    </template>
                    <template x-if="teacherType !== 'quran_teacher' && teacherType !== 'academic_teacher'">
                        <span>{{ __('teacher.calendar.management_generic') }}</span>
                    </template>
                </h2>
                <p class="text-sm text-gray-500">
                    <template x-if="teacherType === 'quran_teacher'">
                        <span>{{ __('teacher.calendar.description_quran') }}</span>
                    </template>
                    <template x-if="teacherType === 'academic_teacher'">
                        <span>{{ __('teacher.calendar.description_academic') }}</span>
                    </template>
                </p>
            </div>
            <button class="p-2 rounded-lg hover:bg-gray-100 transition-colors">
                <i class="ri-arrow-down-s-line text-xl text-gray-400 transition-transform duration-200"
                   :class="{ 'rotate-180': panelOpen }"></i>
            </button>
        </div>

        <!-- Collapsible Content -->
        <div x-show="panelOpen" x-collapse class="px-4 md:px-6 pb-4 md:pb-6">
            <!-- Alerts -->
            <template x-if="error">
                <div class="mt-4 bg-red-50 border border-red-200 rounded-lg p-3 text-sm text-red-800">
                    <i class="ri-error-warning-line me-1"></i> <span x-text="error"></span>
                </div>
            </template>
            <template x-if="success">
                <div class="mt-4 bg-green-50 border border-green-200 rounded-lg p-3 text-sm text-green-800">
                    <i class="ri-checkbox-circle-line me-1"></i> <span x-text="success"></span>
                </div>
            </template>

            <!-- Tabs -->
            <div class="mt-4 flex flex-wrap gap-2 border-b border-gray-200 pb-3">
                <template x-for="(label, key) in tabs" :key="key">
                    <button
                        @click="activeTab = key; fetchItems()"
                        :class="activeTab === key
                            ? 'bg-blue-600 text-white shadow-sm'
                            : 'bg-gray-100 text-gray-700 hover:bg-gray-200'"
                        class="min-h-[36px] px-4 py-1.5 text-sm font-medium rounded-lg transition-colors"
                        x-text="label">
                    </button>
                </template>
            </div>

            <!-- Empty State (full width when no items) -->
            <template x-if="!loading && items.length === 0">
                <div class="mt-4 flex flex-col items-center justify-center text-center py-12">
                    <i class="ri-inbox-line text-4xl text-gray-300 mb-3"></i>
                    <p class="text-sm font-semibold text-gray-600" x-text="getEmptyTitle()"></p>
                    <p class="text-xs text-gray-400 mt-1" x-text="getEmptyDescription()"></p>
                </div>
            </template>

            <!-- Content: Items Grid + Scheduling Form side by side -->
            <div class="mt-4 flex flex-col lg:flex-row gap-6" x-show="loading || items.length > 0">

                <!-- Items List (left side) -->
                <div class="w-full lg:w-1/2 min-h-[200px] flex flex-col">
                    <!-- Loading -->
                    <template x-if="loading">
                        <div class="flex items-center justify-center py-8">
                            <div class="animate-spin rounded-full h-8 w-8 border-b-2 border-blue-600"></div>
                        </div>
                    </template>

                    <!-- Items -->
                    <template x-if="!loading && items.length > 0">
                        <div class="grid grid-cols-1 sm:grid-cols-2 gap-3 max-h-[400px] overflow-y-auto pe-1">
                            <template x-for="item in items" :key="item.id">
                                <div @click="selectItem(item)"
                                     :class="selectedItem?.id === item.id
                                         ? 'border-blue-500 bg-blue-50 ring-1 ring-blue-200'
                                         : 'border-gray-200 hover:border-blue-300 hover:bg-gray-50'"
                                     class="border rounded-lg p-3 cursor-pointer transition-all">
                                    <!-- Header: Name + Status Badge -->
                                    <div class="flex items-start gap-2 mb-2">
                                        <div class="flex-1 min-w-0">
                                            <p class="text-sm font-semibold text-gray-900 truncate" x-text="item.name || item.title"></p>
                                        </div>
                                        <span :class="getItemStatusBadgeClass(item)"
                                              class="text-[10px] px-2 py-0.5 rounded-full whitespace-nowrap flex-shrink-0"
                                              x-text="getItemStatusLabel(item)">
                                        </span>
                                    </div>

                                    <!-- Type-specific info rows -->
                                    {{-- Group Circle --}}
                                    <template x-if="item.type === 'group'">
                                        <div class="space-y-1 text-xs text-gray-600">
                                            <p><i class="ri-group-line me-1 text-gray-400"></i> {{ __('teacher.calendar.students_count') }} <span class="font-medium text-gray-800" x-text="(item.students_count || 0) + '/' + (item.max_students || '∞')"></span></p>
                                            <p><i class="ri-calendar-2-line me-1 text-gray-400"></i> {{ __('teacher.calendar.monthly_sessions') }} <span class="font-medium text-gray-800" x-text="item.monthly_sessions || '-'"></span></p>
                                            <p><i class="ri-time-line me-1 text-gray-400"></i> <span class="font-medium text-gray-800" x-text="(item.session_duration_minutes || 60) + ' {{ __('teacher.calendar.minutes_short') }}'"></span></p>
                                        </div>
                                    </template>

                                    {{-- Individual Circle --}}
                                    <template x-if="item.type === 'individual'">
                                        <div class="space-y-1 text-xs text-gray-600">
                                            <p><i class="ri-user-line me-1 text-gray-400"></i> {{ __('teacher.calendar.student_label') }} <span class="font-medium text-gray-800" x-text="item.student_name || '-'"></span></p>
                                            <p><i class="ri-calendar-check-line me-1 text-gray-400"></i> {{ __('teacher.calendar.sessions_progress') }}: <span class="font-medium text-gray-800" x-text="(item.sessions_scheduled || 0) + '/' + (item.sessions_count || 0)"></span></p>
                                            <template x-if="item.subscription_start || item.subscription_end">
                                                <p><i class="ri-calendar-line me-1 text-gray-400"></i> <span class="font-medium text-gray-800" x-text="formatDate(item.subscription_start) + ' → ' + formatDate(item.subscription_end)"></span></p>
                                            </template>
                                        </div>
                                    </template>

                                    {{-- Trial Request --}}
                                    <template x-if="item.type === 'trial'">
                                        <div class="space-y-1 text-xs text-gray-600">
                                            <template x-if="item.level_label">
                                                <p><i class="ri-bar-chart-line me-1 text-gray-400"></i> {{ __('teacher.calendar.level_label') }} <span class="font-medium text-gray-800" x-text="item.level_label"></span></p>
                                            </template>
                                            <template x-if="item.preferred_time_label">
                                                <p><i class="ri-time-line me-1 text-gray-400"></i> {{ __('teacher.calendar.preferred_time') }} <span class="font-medium text-gray-800" x-text="item.preferred_time_label"></span></p>
                                            </template>
                                        </div>
                                    </template>

                                    {{-- Private Lesson --}}
                                    <template x-if="item.type === 'private_lesson'">
                                        <div class="space-y-1 text-xs text-gray-600">
                                            <p><i class="ri-user-line me-1 text-gray-400"></i> {{ __('teacher.calendar.student_label') }} <span class="font-medium text-gray-800" x-text="item.student_name || '-'"></span></p>
                                            <p><i class="ri-book-line me-1 text-gray-400"></i> {{ __('teacher.calendar.subject_label') }} <span class="font-medium text-gray-800" x-text="item.subject_name || '-'"></span></p>
                                            <p><i class="ri-calendar-check-line me-1 text-gray-400"></i> {{ __('teacher.calendar.sessions_progress') }}: <span class="font-medium text-gray-800" x-text="(item.sessions_scheduled || 0) + '/' + (item.total_sessions || 0)"></span></p>
                                        </div>
                                    </template>

                                    {{-- Interactive Course --}}
                                    <template x-if="item.type === 'interactive_course'">
                                        <div class="space-y-1 text-xs text-gray-600">
                                            <p><i class="ri-book-open-line me-1 text-gray-400"></i> {{ __('teacher.calendar.subject_label') }} <span class="font-medium text-gray-800" x-text="item.subject_name || '-'"></span></p>
                                            <p><i class="ri-group-line me-1 text-gray-400"></i> {{ __('teacher.calendar.students_enrolled') }} <span class="font-medium text-gray-800" x-text="(item.enrolled_students || 0) + '/' + (item.max_students || '∞')"></span></p>
                                            <p><i class="ri-calendar-check-line me-1 text-gray-400"></i> {{ __('teacher.calendar.sessions_progress') }}: <span class="font-medium text-gray-800" x-text="(item.sessions_scheduled || 0) + '/' + (item.total_sessions || 0)"></span></p>
                                            <template x-if="item.start_date || item.end_date">
                                                <p><i class="ri-calendar-line me-1 text-gray-400"></i> <span class="font-medium text-gray-800" x-text="(item.start_date || '?') + ' → ' + (item.end_date || '?')"></span></p>
                                            </template>
                                        </div>
                                    </template>

                                    <!-- Session progress bar (for types with remaining sessions) -->
                                    <template x-if="item.sessions_scheduled !== undefined && (item.sessions_count || item.total_sessions)">
                                        <div class="mt-2">
                                            <div class="w-full bg-gray-200 rounded-full h-1.5">
                                                <div class="bg-blue-500 h-1.5 rounded-full transition-all" :style="'width: ' + Math.min(100, Math.round(((item.sessions_scheduled || 0) / (item.sessions_count || item.total_sessions || 1)) * 100)) + '%'"></div>
                                            </div>
                                        </div>
                                    </template>

                                    <!-- Cannot schedule badge -->
                                    <template x-if="item.can_schedule === false">
                                        <div class="mt-2">
                                            <span class="inline-flex items-center gap-1 text-[10px] px-2 py-0.5 rounded-full bg-red-50 text-red-600 border border-red-200">
                                                <i class="ri-forbid-line"></i> {{ __('teacher.calendar.cannot_schedule') }}
                                            </span>
                                        </div>
                                    </template>

                                    <!-- Remaining sessions count -->
                                    <template x-if="item.can_schedule !== false && (item.sessions_remaining !== undefined || item.remaining_sessions !== undefined)">
                                        <p class="text-xs text-blue-600 mt-1.5 font-medium">
                                            <i class="ri-calendar-todo-line me-0.5"></i>
                                            {{ __('teacher.calendar.remaining') }}: <span x-text="item.sessions_remaining ?? item.remaining_sessions"></span>
                                        </p>
                                    </template>
                                </div>
                            </template>
                        </div>
                    </template>
                </div>

                <!-- Cannot-schedule info panel (shown when item is selected but not schedulable) -->
                <template x-if="selectedItem && selectedItem.can_schedule === false">
                    <div class="w-full lg:w-1/2 bg-gray-50 border border-gray-200 rounded-xl p-4 flex flex-col gap-3">
                        <h3 class="text-sm font-bold text-gray-900">
                            <i class="ri-information-line me-1 text-gray-500"></i>
                            <span x-text="selectedItem.name || selectedItem.title"></span>
                        </h3>

                        <div class="bg-amber-50 border border-amber-200 rounded-lg p-3 text-sm text-amber-800">
                            <p class="font-semibold mb-1">
                                <i class="ri-forbid-line me-1"></i>
                                {{ __('teacher.calendar.cannot_schedule') }}
                            </p>
                            <p x-text="selectedItem.status_arabic"></p>
                        </div>

                        <!-- Show scheduled time if already scheduled -->
                        <template x-if="selectedItem.scheduled_at_formatted">
                            <div class="bg-green-50 border border-green-200 rounded-lg p-3 text-sm text-green-800">
                                <p class="font-semibold mb-1">
                                    <i class="ri-calendar-check-line me-1"></i>
                                    {{ __('teacher.calendar.already_scheduled') }}
                                </p>
                                <p x-text="selectedItem.scheduled_at_formatted"></p>
                            </div>
                        </template>
                    </div>
                </template>

                <!-- Schedule Form (right side, shown when item selected and schedulable) -->
                <template x-if="selectedItem && selectedItem.can_schedule !== false">
                    <div class="w-full lg:w-1/2 bg-gray-50 border border-gray-200 rounded-xl p-4">
                        <h3 class="text-sm font-bold text-gray-900 mb-3">
                            <i class="ri-calendar-schedule-line me-1 text-blue-600"></i>
                            {{ __('teacher.calendar.schedule_for') }}:
                            <span class="text-blue-700" x-text="selectedItem.name || selectedItem.title"></span>
                        </h3>

                        <!-- Days Selection (hidden for trial sessions — day is auto-computed from date) -->
                        <div class="mb-3" x-show="selectedItem.type !== 'trial'">
                            <label class="block text-xs font-semibold text-gray-700 mb-2">{{ __('teacher.calendar.select_days') }}</label>
                            <div class="flex flex-wrap gap-2">
                                @php
                                    $days = [
                                        'Saturday' => __('teacher.calendar.day_sat'),
                                        'Sunday' => __('teacher.calendar.day_sun'),
                                        'Monday' => __('teacher.calendar.day_mon'),
                                        'Tuesday' => __('teacher.calendar.day_tue'),
                                        'Wednesday' => __('teacher.calendar.day_wed'),
                                        'Thursday' => __('teacher.calendar.day_thu'),
                                        'Friday' => __('teacher.calendar.day_fri'),
                                    ];
                                @endphp
                                @foreach($days as $dayEn => $dayAr)
                                    <button type="button"
                                            @click="toggleDay('{{ $dayEn }}')"
                                            :class="isDaySelected('{{ $dayEn }}')
                                                ? 'bg-blue-600 text-white border-blue-600'
                                                : 'bg-white text-gray-700 border-gray-300 hover:border-blue-400'"
                                            class="min-h-[36px] px-3 py-1.5 text-xs font-medium rounded-lg border transition-colors">
                                        {{ $dayAr }}
                                    </button>
                                @endforeach
                            </div>
                            <!-- Recommendations hint -->
                            <template x-if="recommendations">
                                <p class="mt-1.5 text-xs text-blue-600 bg-blue-50 rounded-lg px-3 py-1.5">
                                    <i class="ri-lightbulb-line me-1"></i>
                                    {{ __('teacher.calendar.recommended') }}: <span x-text="recommendations.recommended_days"></span> {{ __('teacher.calendar.days_per_week') }}
                                    <template x-if="recommendations.reason">
                                        <span class="text-gray-500"> — <span x-text="recommendations.reason"></span></span>
                                    </template>
                                </p>
                            </template>
                        </div>

                        <!-- Session Date (trial sessions only — single date picker) -->
                        <div class="mb-3" x-show="selectedItem.type === 'trial'">
                            <label class="block text-xs font-semibold text-gray-700 mb-1">{{ __('teacher.calendar.session_date') }}</label>
                            <input type="date" x-model="scheduleStartDate" @change="onTrialDateChange()"
                                   class="w-full min-h-[36px] px-2 py-1.5 border border-gray-300 rounded-lg text-sm focus:ring-2 focus:ring-blue-500 focus:border-blue-500">
                        </div>

                        <!-- Start Date + Session Count (regular items) -->
                        <div class="grid grid-cols-2 gap-3 mb-3" x-show="selectedItem.type !== 'trial'">
                            <div>
                                <label class="block text-xs font-semibold text-gray-700 mb-1">{{ __('teacher.calendar.start_date') }}</label>
                                <input type="date" x-model="scheduleStartDate"
                                       class="w-full min-h-[36px] px-2 py-1.5 border border-gray-300 rounded-lg text-sm focus:ring-2 focus:ring-blue-500 focus:border-blue-500">
                                <p class="text-[10px] text-gray-400 mt-0.5">{{ __('teacher.calendar.start_date_hint') }}</p>
                            </div>
                            <div>
                                <label class="block text-xs font-semibold text-gray-700 mb-1">{{ __('teacher.calendar.session_count') }}</label>
                                <input type="number" x-model.number="sessionCount" min="1" max="50"
                                       class="w-full min-h-[36px] px-2 py-1.5 border border-gray-300 rounded-lg text-sm focus:ring-2 focus:ring-blue-500 focus:border-blue-500">
                            </div>
                        </div>

                        <!-- Time Fields with Labels -->
                        <div class="mb-3">
                            <label class="block text-xs font-semibold text-gray-700 mb-1">{{ __('teacher.calendar.time') }}</label>
                            <div class="grid grid-cols-3 gap-2">
                                <div>
                                    <label class="block text-[10px] text-gray-500 mb-0.5">{{ __('teacher.calendar.hour_label') }}</label>
                                    <select x-model="scheduleHour" class="w-full min-h-[36px] px-2 py-1 border border-gray-300 rounded-lg text-sm">
                                        <template x-for="h in 12" :key="h">
                                            <option :value="h" x-text="h"></option>
                                        </template>
                                    </select>
                                </div>
                                <div>
                                    <label class="block text-[10px] text-gray-500 mb-0.5">{{ __('teacher.calendar.minute_label') }}</label>
                                    <select x-model="scheduleMinute" class="w-full min-h-[36px] px-2 py-1 border border-gray-300 rounded-lg text-sm">
                                        <option value="00">00</option>
                                        <option value="15">15</option>
                                        <option value="30">30</option>
                                        <option value="45">45</option>
                                    </select>
                                </div>
                                <div>
                                    <label class="block text-[10px] text-gray-500 mb-0.5">{{ __('teacher.calendar.period_label') }}</label>
                                    <select x-model="schedulePeriod" class="w-full min-h-[36px] px-2 py-1 border border-gray-300 rounded-lg text-sm">
                                        <option value="AM">{{ __('teacher.calendar.am') }}</option>
                                        <option value="PM">{{ __('teacher.calendar.pm') }}</option>
                                    </select>
                                </div>
                            </div>
                        </div>

                        <!-- Preview Box: trial session (single date) -->
                        <template x-if="selectedItem.type === 'trial' && scheduleStartDate">
                            <div class="mb-3 bg-white border border-gray-200 rounded-lg p-3">
                                <p class="text-[10px] font-semibold text-gray-500 uppercase mb-1.5">{{ __('teacher.calendar.schedule_preview') }}</p>
                                <div class="space-y-1 text-xs text-gray-700">
                                    <p><span class="font-medium">{{ __('teacher.calendar.preview_start') }}:</span> <span x-text="formatDate(scheduleStartDate)"></span></p>
                                    <p><span class="font-medium">{{ __('teacher.calendar.preview_time') }}:</span> <span x-text="scheduleHour + ':' + scheduleMinute + ' ' + (schedulePeriod === 'AM' ? @js(__('teacher.calendar.am')) : @js(__('teacher.calendar.pm'))) + ' (' + scheduleTime + ')'"></span></p>
                                </div>
                            </div>
                        </template>

                        <!-- Preview Box: regular items -->
                        <template x-if="selectedItem.type !== 'trial' && scheduleDays.length > 0">
                            <div class="mb-3 bg-white border border-gray-200 rounded-lg p-3">
                                <p class="text-[10px] font-semibold text-gray-500 uppercase mb-1.5">{{ __('teacher.calendar.schedule_preview') }}</p>
                                <div class="space-y-1 text-xs text-gray-700">
                                    <p><span class="font-medium">{{ __('teacher.calendar.preview_sessions') }}:</span> <span x-text="sessionCount"></span></p>
                                    <p><span class="font-medium">{{ __('teacher.calendar.preview_days') }}:</span> <span x-text="scheduleDays.map(d => getDayLabel(d)).join('، ')"></span></p>
                                    <p><span class="font-medium">{{ __('teacher.calendar.preview_start') }}:</span> <span x-text="scheduleStartDate ? formatDate(scheduleStartDate) : '{{ __('teacher.calendar.today') }}'"></span></p>
                                    <p><span class="font-medium">{{ __('teacher.calendar.preview_time') }}:</span> <span x-text="scheduleHour + ':' + scheduleMinute + ' ' + (schedulePeriod === 'AM' ? @js(__('teacher.calendar.am')) : @js(__('teacher.calendar.pm'))) + ' (' + scheduleTime + ')'"></span></p>
                                </div>
                            </div>
                        </template>

                        <!-- Submit Button (full width) -->
                        <button @click="submitSchedule()" :disabled="submitting || (selectedItem.type === 'trial' ? !scheduleStartDate : scheduleDays.length === 0) || sessionCount < 1"
                                class="w-full min-h-[40px] inline-flex items-center justify-center gap-1.5 px-4 py-2 bg-blue-600 text-white text-sm font-medium rounded-lg hover:bg-blue-700 disabled:opacity-50 disabled:cursor-not-allowed transition-colors">
                            <template x-if="submitting">
                                <div class="animate-spin rounded-full h-4 w-4 border-2 border-white border-t-transparent"></div>
                            </template>
                            <template x-if="!submitting">
                                <i class="ri-calendar-check-line"></i>
                            </template>
                            <span x-text="submitting ? '{{ __('teacher.calendar.scheduling') }}' : '{{ __('teacher.calendar.schedule_button') }}'"></span>
                        </button>
                    </div>
                </template>
            </div>
        </div>
    </div>
</div>

<script>
function schedulingPanel() {
    return {
        teacherType: @js($teacherType),
        teacherId: @js($teacherId),
        calendarVarName: @js($calendarVarName),
        panelOpen: false,
        activeTab: '',
        tabs: @js($tabs),
        items: [],
        selectedItem: null,
        loading: false,
        submitting: false,
        scheduleDays: [],
        scheduleStartDate: '',
        scheduleHour: 4,
        scheduleMinute: '00',
        schedulePeriod: 'PM',
        sessionCount: 4,
        error: null,
        success: null,
        recommendations: null,

        appendTeacherId(url, sep = '&') {
            return this.teacherId ? url + sep + 'teacher_id=' + this.teacherId : url;
        },

        init() {
            const tabKeys = Object.keys(this.tabs);
            if (tabKeys.length > 0) {
                this.activeTab = tabKeys[0];
                this.fetchItems();
            }
        },

        async fetchItems() {
            this.loading = true;
            this.selectedItem = null;
            this.error = null;
            try {
                const url = this.appendTeacherId(
                    @js($schedulableItemsRoute) + '?tab=' + this.activeTab,
                    '&'
                );
                const response = await fetch(url, {
                    headers: { 'X-Requested-With': 'XMLHttpRequest', 'Accept': 'application/json' }
                });
                if (!response.ok) throw new Error('Network error');
                const data = await response.json();
                this.items = data.items || [];
            } catch (e) {
                this.error = @js(__('teacher.calendar.load_error'));
            } finally {
                this.loading = false;
            }
        },

        selectItem(item) {
            this.selectedItem = item;
            this.scheduleDays = item.schedule_days || [];
            this.sessionCount = item.type === 'trial' ? 1 : 4;
            this.error = null;
            this.success = null;
            this.recommendations = null;
            this.fetchRecommendations(item);
        },

        async fetchRecommendations(item) {
            try {
                const url = this.appendTeacherId(
                    @js($recommendationsRoute) + '?item_id=' + item.id + '&item_type=' + item.type,
                    '&'
                );
                const response = await fetch(url, {
                    headers: { 'X-Requested-With': 'XMLHttpRequest', 'Accept': 'application/json' }
                });
                if (response.ok) {
                    const data = await response.json();
                    if (data.success) {
                        this.recommendations = data.recommendations;
                    }
                }
            } catch (e) {
                // Silently fail - recommendations are optional
            }
        },

        get scheduleTime() {
            let hour = parseInt(this.scheduleHour);
            if (this.schedulePeriod === 'PM' && hour < 12) hour += 12;
            if (this.schedulePeriod === 'AM' && hour === 12) hour = 0;
            return `${String(hour).padStart(2, '0')}:${this.scheduleMinute}`;
        },

        async submitSchedule() {
            if (this.submitting) return;
            this.submitting = true;
            this.error = null;
            this.success = null;

            try {
                const bodyData = {
                    item_id: this.selectedItem.id,
                    item_type: this.selectedItem.type,
                    schedule_days: this.scheduleDays,
                    schedule_time: this.scheduleTime,
                    schedule_start_date: this.scheduleStartDate || new Date().toISOString().split('T')[0],
                    session_count: this.sessionCount,
                };
                if (this.teacherId) {
                    bodyData.teacher_id = this.teacherId;
                }

                const response = await fetch(@js($scheduleRoute), {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': @js(csrf_token()),
                        'Accept': 'application/json'
                    },
                    body: JSON.stringify(bodyData)
                });

                const data = await response.json();
                if (response.ok) {
                    this.success = data.message || @js(__('teacher.calendar.schedule_success'));
                    this.selectedItem = null;
                    this.fetchItems();
                    // Refresh FullCalendar
                    if (window[this.calendarVarName]) {
                        window[this.calendarVarName].refetchEvents();
                    }
                } else {
                    this.error = data.message || data.error || @js(__('teacher.calendar.schedule_error'));
                }
            } catch (e) {
                this.error = @js(__('teacher.calendar.schedule_error'));
            } finally {
                this.submitting = false;
            }
        },

        toggleDay(day) {
            const idx = this.scheduleDays.indexOf(day);
            if (idx > -1) this.scheduleDays.splice(idx, 1);
            else this.scheduleDays.push(day);
        },

        isDaySelected(day) {
            return this.scheduleDays.includes(day);
        },

        getSchedulingStatusLabel(status) {
            const labels = {
                'fully_scheduled': @js(__('teacher.calendar.status_fully_scheduled')),
                'partially_scheduled': @js(__('teacher.calendar.status_partially_scheduled')),
                'unscheduled': @js(__('teacher.calendar.status_unscheduled')),
                'not_scheduled': @js(__('teacher.calendar.status_unscheduled')),
                'scheduled': @js(__('teacher.calendar.status_scheduled'))
            };
            return labels[status] || status;
        },

        getItemStatusBadgeClass(item) {
            if (item.type === 'trial') {
                const classes = {
                    'pending':   'bg-amber-100 text-amber-700',
                    'approved':  'bg-blue-100 text-blue-700',
                    'scheduled': 'bg-green-100 text-green-700',
                    'completed': 'bg-emerald-100 text-emerald-700',
                    'cancelled': 'bg-gray-100 text-gray-500',
                    'rejected':  'bg-red-100 text-red-700',
                    'no_show':   'bg-orange-100 text-orange-700',
                };
                return classes[item.status] || 'bg-gray-100 text-gray-600';
            }
            if (item.status === 'fully_scheduled' || item.status === 'scheduled') return 'bg-green-100 text-green-700';
            if (item.status === 'partially_scheduled') return 'bg-amber-100 text-amber-700';
            return 'bg-gray-100 text-gray-600';
        },

        getItemStatusLabel(item) {
            if (item.type === 'trial') {
                return item.status_arabic || item.status;
            }
            return this.getSchedulingStatusLabel(item.status);
        },

        onTrialDateChange() {
            if (!this.scheduleStartDate) return;
            const dayNames = ['Sunday', 'Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday', 'Saturday'];
            const d = new Date(this.scheduleStartDate + 'T00:00:00');
            this.scheduleDays = [dayNames[d.getDay()]];
        },

        getEmptyTitle() {
            const titles = {
                'group_circles': @js(__('teacher.calendar.no_group_circles')),
                'individual_circles': @js(__('teacher.calendar.no_individual_circles')),
                'trial_sessions': @js(__('teacher.calendar.no_trial_sessions')),
                'private_lessons': @js(__('teacher.calendar.no_private_lessons')),
                'interactive_courses': @js(__('teacher.calendar.no_interactive_courses'))
            };
            return titles[this.activeTab] || @js(__('teacher.calendar.no_items'));
        },

        getDayLabel(day) {
            const labels = {
                'Saturday': @js(__('teacher.calendar.day_sat')),
                'Sunday': @js(__('teacher.calendar.day_sun')),
                'Monday': @js(__('teacher.calendar.day_mon')),
                'Tuesday': @js(__('teacher.calendar.day_tue')),
                'Wednesday': @js(__('teacher.calendar.day_wed')),
                'Thursday': @js(__('teacher.calendar.day_thu')),
                'Friday': @js(__('teacher.calendar.day_fri'))
            };
            return labels[day] || day;
        },

        formatDate(dateStr) {
            if (!dateStr) return '?';
            try {
                // Use T00:00:00 (local time) to avoid UTC-offset date-shift bugs
                const d = new Date(dateStr + 'T00:00:00');
                const greg = d.toLocaleDateString('ar-SA-u-ca-gregory', { year: 'numeric', month: 'short', day: 'numeric' });
                const hijri = d.toLocaleDateString('ar-SA-u-ca-islamic-umalqura', { year: 'numeric', month: 'short', day: 'numeric' });
                return greg + ' — ' + hijri;
            } catch (e) {
                return String(dateStr).substring(0, 10);
            }
        },

        getEmptyDescription() {
            const descriptions = {
                'group_circles': @js(__('teacher.calendar.no_group_circles_desc')),
                'individual_circles': @js(__('teacher.calendar.no_individual_circles_desc')),
                'trial_sessions': @js(__('teacher.calendar.no_trial_sessions_desc')),
                'private_lessons': @js(__('teacher.calendar.no_private_lessons_desc')),
                'interactive_courses': @js(__('teacher.calendar.no_interactive_courses_desc'))
            };
            return descriptions[this.activeTab] || @js(__('teacher.calendar.no_items_desc'));
        }
    }
}
</script>
