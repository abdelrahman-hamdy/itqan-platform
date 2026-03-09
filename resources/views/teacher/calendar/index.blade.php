<x-layouts.teacher :title="__('teacher.calendar.page_title') . ' - ' . config('app.name')">

@php
    $subdomain = request()->route('subdomain') ?? auth()->user()->academy->subdomain ?? 'itqan-academy';
    $eventsRoute = route('teacher.calendar.events', ['subdomain' => $subdomain]);
@endphp

    <!-- Page Header -->
    <div class="mb-6 md:mb-8">
        <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-2 mb-1 md:mb-2">
            <h1 class="text-xl sm:text-2xl md:text-3xl font-bold text-gray-900">{{ __('teacher.calendar.page_title') }}</h1>
            <div class="inline-flex items-center gap-2 px-4 py-2 text-sm font-medium rounded-lg bg-white text-gray-700 border border-gray-200 shadow-sm">
                <i class="ri-global-line text-blue-500"></i>
                <span>{{ __('student.calendar.all_times_in_country', ['country' => auth()->user()->academy?->country?->label() ?? __('enums.country.SA')]) }}</span>
                <span class="text-gray-300">|</span>
                <i class="ri-time-line text-blue-500"></i>
                <span id="live-clock" class="font-semibold text-gray-900 tabular-nums"></span>
            </div>
        </div>
        <p class="text-sm md:text-base text-gray-600">{{ __('teacher.calendar.page_description') }}</p>
    </div>

    <!-- Stats Cards -->
    <x-calendar.stats-cards />

    <!-- Two-column layout: Scheduling Panel + Calendar Grid -->
    <div class="flex flex-col lg:flex-row gap-6">

        {{-- ================================================================ --}}
        {{-- LEFT: Scheduling Panel (1/3 width on desktop, full on mobile)    --}}
        {{-- ================================================================ --}}
        <div class="w-full lg:w-1/3" x-data="schedulingPanel()">
            <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-4 md:p-6 sticky top-24">

                <!-- Panel Header -->
                <div class="mb-4 md:mb-6">
                    <h2 class="text-lg font-bold text-gray-900 mb-1">
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
                        <template x-if="teacherType !== 'quran_teacher' && teacherType !== 'academic_teacher'">
                            <span>{{ __('teacher.calendar.description_generic') }}</span>
                        </template>
                    </p>
                </div>

                <!-- Tabs -->
                <div class="flex flex-wrap gap-2 mb-4 border-b border-gray-200 pb-3">
                    <template x-for="(label, key) in tabs" :key="key">
                        <button
                            @click="activeTab = key; fetchItems()"
                            :class="{
                                'bg-blue-600 text-white shadow-sm': activeTab === key,
                                'bg-gray-100 text-gray-600 hover:bg-gray-200': activeTab !== key
                            }"
                            class="px-3 py-1.5 text-xs font-medium rounded-lg transition-all duration-200"
                            x-text="label"
                        ></button>
                    </template>
                </div>

                <!-- Success Message -->
                <template x-if="success">
                    <div class="mb-4 p-3 bg-green-50 border border-green-200 rounded-lg flex items-center gap-2 text-sm text-green-700" role="alert">
                        <i class="ri-check-line text-lg flex-shrink-0"></i>
                        <span x-text="success"></span>
                        <button @click="success = null" class="ms-auto text-green-700 hover:text-green-900 flex-shrink-0">
                            <i class="ri-close-line text-lg"></i>
                        </button>
                    </div>
                </template>

                <!-- Error Message -->
                <template x-if="error">
                    <div class="mb-4 p-3 bg-red-50 border border-red-200 rounded-lg flex items-center gap-2 text-sm text-red-700" role="alert">
                        <i class="ri-error-warning-line text-lg flex-shrink-0"></i>
                        <span x-text="error"></span>
                        <button @click="error = null" class="ms-auto text-red-700 hover:text-red-900 flex-shrink-0">
                            <i class="ri-close-line text-lg"></i>
                        </button>
                    </div>
                </template>

                <!-- Loading State -->
                <template x-if="loading">
                    <div class="py-8 text-center">
                        <div class="inline-flex items-center gap-2 text-gray-500 text-sm">
                            <svg class="animate-spin h-5 w-5 text-blue-500" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                                <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                            </svg>
                            <span>{{ __('teacher.calendar.loading') }}</span>
                        </div>
                    </div>
                </template>

                <!-- Item List -->
                <template x-if="!loading && items.length === 0 && !selectedItem">
                    <div class="py-8 text-center">
                        <div class="w-16 h-16 bg-gray-100 rounded-full flex items-center justify-center mx-auto mb-3">
                            <i class="ri-inbox-line text-2xl text-gray-400"></i>
                        </div>
                        <h3 class="text-sm font-semibold text-gray-900 mb-1" x-text="getEmptyTitle()"></h3>
                        <p class="text-xs text-gray-500" x-text="getEmptyDescription()"></p>
                    </div>
                </template>

                <template x-if="!loading && items.length > 0 && !selectedItem">
                    <div class="space-y-3 max-h-[50vh] overflow-y-auto">
                        <template x-for="item in items" :key="item.id">
                            <button
                                @click="selectItem(item)"
                                class="w-full text-start p-3 rounded-lg border border-gray-200 hover:border-blue-300 hover:bg-blue-50/50 transition-all duration-200 group"
                            >
                                <div class="flex items-start justify-between gap-2 mb-2">
                                    <h4 class="text-sm font-semibold text-gray-900 group-hover:text-blue-700 line-clamp-2" x-text="item.name"></h4>
                                    <span
                                        :class="{
                                            'bg-green-100 text-green-700': item.scheduling_status === 'fully_scheduled',
                                            'bg-yellow-100 text-yellow-700': item.scheduling_status === 'partially_scheduled',
                                            'bg-gray-100 text-gray-600': item.scheduling_status === 'unscheduled'
                                        }"
                                        class="text-[10px] font-medium px-2 py-0.5 rounded-full whitespace-nowrap flex-shrink-0"
                                        x-text="getSchedulingStatusLabel(item.scheduling_status)"
                                    ></span>
                                </div>
                                <div class="flex flex-wrap gap-x-4 gap-y-1 text-xs text-gray-500">
                                    <template x-if="item.sessions_count !== undefined">
                                        <span class="inline-flex items-center gap-1">
                                            <i class="ri-calendar-line"></i>
                                            <span>{{ __('teacher.calendar.sessions_count') }}</span>
                                            <span x-text="item.sessions_count" class="font-medium text-gray-700"></span>
                                        </span>
                                    </template>
                                    <template x-if="item.students_count !== undefined">
                                        <span class="inline-flex items-center gap-1">
                                            <i class="ri-user-line"></i>
                                            <span>{{ __('teacher.calendar.students_count') }}</span>
                                            <span x-text="item.students_count" class="font-medium text-gray-700"></span>
                                        </span>
                                    </template>
                                    <template x-if="item.time">
                                        <span class="inline-flex items-center gap-1">
                                            <i class="ri-time-line"></i>
                                            <span>{{ __('teacher.calendar.time_label') }}</span>
                                            <span x-text="item.time" class="font-medium text-gray-700"></span>
                                        </span>
                                    </template>
                                </div>
                            </button>
                        </template>
                    </div>
                </template>

                <!-- Schedule Form (shown when item is selected) -->
                <template x-if="!loading && selectedItem">
                    <div class="space-y-4">
                        <!-- Back to list button -->
                        <button
                            @click="selectedItem = null; error = null; success = null;"
                            class="inline-flex items-center gap-1 text-sm text-gray-500 hover:text-gray-700 transition-colors"
                        >
                            <i class="ri-arrow-right-line"></i>
                            <span>{{ __('teacher.calendar.select_item') }}</span>
                        </button>

                        <!-- Selected Item Summary -->
                        <div class="p-3 bg-blue-50 border border-blue-200 rounded-lg">
                            <h4 class="text-sm font-bold text-blue-900 mb-1" x-text="selectedItem.name"></h4>
                            <div class="flex flex-wrap gap-x-3 gap-y-1 text-xs text-blue-700">
                                <template x-if="selectedItem.student_name">
                                    <span class="inline-flex items-center gap-1">
                                        <i class="ri-user-line"></i>
                                        <span>{{ __('teacher.calendar.student_label') }}</span>
                                        <span x-text="selectedItem.student_name" class="font-medium"></span>
                                    </span>
                                </template>
                                <template x-if="selectedItem.subscription_info">
                                    <span class="inline-flex items-center gap-1">
                                        <i class="ri-calendar-line"></i>
                                        <span>{{ __('teacher.calendar.total_sessions') }}</span>
                                        <span x-text="selectedItem.subscription_info.total_sessions" class="font-medium"></span>
                                    </span>
                                </template>
                                <template x-if="selectedItem.subscription_info">
                                    <span class="inline-flex items-center gap-1">
                                        <i class="ri-calendar-check-line"></i>
                                        <span>{{ __('teacher.calendar.scheduled_label') }}</span>
                                        <span x-text="selectedItem.subscription_info.scheduled_sessions" class="font-medium"></span>
                                    </span>
                                </template>
                                <template x-if="selectedItem.subscription_info">
                                    <span class="inline-flex items-center gap-1">
                                        <i class="ri-calendar-todo-line"></i>
                                        <span>{{ __('teacher.calendar.remaining_label') }}</span>
                                        <span x-text="selectedItem.subscription_info.remaining_sessions" class="font-medium"></span>
                                    </span>
                                </template>
                                <template x-if="selectedItem.subscription_info && selectedItem.subscription_info.start_date">
                                    <span class="inline-flex items-center gap-1">
                                        <i class="ri-time-line"></i>
                                        <span>{{ __('teacher.calendar.start_date') }}</span>
                                        <span x-text="selectedItem.subscription_info.start_date" class="font-medium"></span>
                                        <span>-</span>
                                        <span x-text="selectedItem.subscription_info.end_date" class="font-medium"></span>
                                    </span>
                                </template>
                            </div>
                        </div>

                        <!-- Day Checkboxes -->
                        <div>
                            <label class="block text-sm font-semibold text-gray-700 mb-2">{{ __('teacher.calendar.select_days') }}</label>
                            <div class="grid grid-cols-4 gap-2">
                                @php
                                    $days = [
                                        'Saturday' => __('teacher.calendar.day_saturday'),
                                        'Sunday' => __('teacher.calendar.day_sunday'),
                                        'Monday' => __('teacher.calendar.day_monday'),
                                        'Tuesday' => __('teacher.calendar.day_tuesday'),
                                        'Wednesday' => __('teacher.calendar.day_wednesday'),
                                        'Thursday' => __('teacher.calendar.day_thursday'),
                                        'Friday' => __('teacher.calendar.day_friday'),
                                    ];
                                @endphp
                                @foreach ($days as $dayValue => $dayLabel)
                                    <button
                                        type="button"
                                        @click="toggleDay('{{ $dayValue }}')"
                                        :class="{
                                            'bg-blue-600 text-white border-blue-600': isDaySelected('{{ $dayValue }}'),
                                            'bg-white text-gray-700 border-gray-300 hover:border-blue-400': !isDaySelected('{{ $dayValue }}')
                                        }"
                                        class="px-2 py-2 text-xs font-medium rounded-lg border transition-all duration-200 text-center"
                                    >
                                        {{ $dayLabel }}
                                    </button>
                                @endforeach
                            </div>
                        </div>

                        <!-- Start Date -->
                        <div>
                            <label for="schedule-start-date" class="block text-sm font-semibold text-gray-700 mb-1">{{ __('teacher.calendar.start_date_label') }}</label>
                            <input
                                type="date"
                                id="schedule-start-date"
                                x-model="scheduleStartDate"
                                min="{{ now()->format('Y-m-d') }}"
                                class="w-full px-3 py-2 text-sm border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500 transition"
                            />
                        </div>

                        <!-- Time Selection -->
                        <div>
                            <label class="block text-sm font-semibold text-gray-700 mb-1">{{ __('teacher.calendar.session_time') }}</label>
                            <div class="flex items-center gap-2">
                                <!-- Hour -->
                                <div class="flex-1">
                                    <label class="sr-only" for="schedule-hour">{{ __('teacher.calendar.hour') }}</label>
                                    <select
                                        id="schedule-hour"
                                        x-model="scheduleHour"
                                        class="w-full px-2 py-2 text-sm border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500 transition"
                                    >
                                        @for ($h = 1; $h <= 12; $h++)
                                            <option value="{{ $h }}">{{ $h }}</option>
                                        @endfor
                                    </select>
                                </div>
                                <span class="text-gray-400 font-bold">:</span>
                                <!-- Minutes -->
                                <div class="flex-1">
                                    <label class="sr-only" for="schedule-minute">{{ __('teacher.calendar.minute') }}</label>
                                    <select
                                        id="schedule-minute"
                                        x-model="scheduleMinute"
                                        class="w-full px-2 py-2 text-sm border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500 transition"
                                    >
                                        <option value="00">00</option>
                                        <option value="15">15</option>
                                        <option value="30">30</option>
                                        <option value="45">45</option>
                                    </select>
                                </div>
                                <!-- AM/PM -->
                                <div class="flex-1">
                                    <label class="sr-only" for="schedule-period">{{ __('teacher.calendar.am') }} / {{ __('teacher.calendar.pm') }}</label>
                                    <select
                                        id="schedule-period"
                                        x-model="schedulePeriod"
                                        class="w-full px-2 py-2 text-sm border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500 transition"
                                    >
                                        <option value="AM">{{ __('teacher.calendar.am') }}</option>
                                        <option value="PM">{{ __('teacher.calendar.pm') }}</option>
                                    </select>
                                </div>
                            </div>
                        </div>

                        <!-- Session Count -->
                        <div>
                            <label for="schedule-session-count" class="block text-sm font-semibold text-gray-700 mb-1">{{ __('teacher.calendar.session_count_label') }}</label>
                            <input
                                type="number"
                                id="schedule-session-count"
                                x-model.number="sessionCount"
                                min="1"
                                :max="selectedItem.subscription_info ? selectedItem.subscription_info.remaining_sessions : 100"
                                :disabled="selectedItem.type === 'trial'"
                                class="w-full px-3 py-2 text-sm border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500 transition disabled:bg-gray-100 disabled:cursor-not-allowed"
                            />
                        </div>

                        <!-- Submit Button -->
                        <button
                            @click="submitSchedule()"
                            :disabled="submitting || scheduleDays.length === 0 || !scheduleStartDate"
                            class="w-full px-4 py-3 text-sm font-bold text-white bg-blue-600 rounded-lg hover:bg-blue-700 transition-all duration-200 disabled:opacity-50 disabled:cursor-not-allowed flex items-center justify-center gap-2"
                        >
                            <template x-if="submitting">
                                <svg class="animate-spin h-4 w-4 text-white" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                                    <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                    <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                                </svg>
                            </template>
                            <span x-text="submitting ? '{{ __('teacher.calendar.scheduling') }}' : '{{ __('teacher.calendar.schedule_button') }}'"></span>
                        </button>
                    </div>
                </template>

            </div>
        </div>

        {{-- ================================================================ --}}
        {{-- RIGHT: Calendar Grid (2/3 width on desktop, full on mobile)      --}}
        {{-- ================================================================ --}}
        <div class="w-full lg:w-2/3">
            <!-- Calendar Navigation -->
            <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-4 md:p-6 mb-6" x-data>
                <div class="flex flex-col md:flex-row md:items-center md:justify-between gap-4 mb-4 md:mb-6">
                    <div class="flex items-center gap-2 md:gap-3 order-2 md:order-1">
                        <button @click="changeMonth(-1)" class="nav-button min-h-[44px]">
                            <i class="ri-arrow-right-s-line text-xl"></i>
                            <span class="hidden sm:inline">{{ __('student.calendar.previous_month') }}</span>
                        </button>
                        <button @click="goToToday()" class="nav-button min-h-[44px] bg-blue-50 text-blue-600 border-blue-200 hover:bg-blue-100">
                            <i class="ri-calendar-check-line"></i>
                            <span>{{ __('student.calendar.today') }}</span>
                        </button>
                        <button @click="changeMonth(1)" class="nav-button min-h-[44px]">
                            <span class="hidden sm:inline">{{ __('student.calendar.next_month') }}</span>
                            <i class="ri-arrow-left-s-line text-xl"></i>
                        </button>
                    </div>
                    <div class="text-center order-1 md:order-2">
                        <h2 id="current-month" class="text-xl md:text-2xl font-bold text-gray-900"></h2>
                    </div>
                    <div class="flex items-center gap-2">
                        <!-- Legend -->
                        <div class="hidden lg:flex items-center gap-4 text-xs">
                            <div class="flex items-center gap-1">
                                <div class="w-3 h-3 rounded bg-blue-500"></div>
                                <span class="text-gray-600">{{ __('student.calendar.legend_scheduled') }}</span>
                            </div>
                            <div class="flex items-center gap-1">
                                <div class="w-3 h-3 rounded bg-yellow-500"></div>
                                <span class="text-gray-600">{{ __('student.calendar.legend_ongoing') }}</span>
                            </div>
                            <div class="flex items-center gap-1">
                                <div class="w-3 h-3 rounded bg-green-500"></div>
                                <span class="text-gray-600">{{ __('student.calendar.legend_completed') }}</span>
                            </div>
                            <div class="flex items-center gap-1">
                                <div class="w-3 h-3 rounded bg-red-500"></div>
                                <span class="text-gray-600">{{ __('student.calendar.legend_cancelled') }}</span>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Calendar Grid -->
                <div id="calendar-grid" class="calendar-grid rounded-lg overflow-hidden" role="grid" aria-label="{{ __('teacher.calendar.page_title') }}">
                    <!-- Header Row -->
                    <div class="calendar-header-cell" role="columnheader">{{ __('student.calendar.saturday') }}</div>
                    <div class="calendar-header-cell" role="columnheader">{{ __('student.calendar.sunday') }}</div>
                    <div class="calendar-header-cell" role="columnheader">{{ __('student.calendar.monday') }}</div>
                    <div class="calendar-header-cell" role="columnheader">{{ __('student.calendar.tuesday') }}</div>
                    <div class="calendar-header-cell" role="columnheader">{{ __('student.calendar.wednesday') }}</div>
                    <div class="calendar-header-cell" role="columnheader">{{ __('student.calendar.thursday') }}</div>
                    <div class="calendar-header-cell" role="columnheader">{{ __('student.calendar.friday') }}</div>
                    <!-- Days will be inserted here by JavaScript -->
                </div>
            </div>
        </div>

    </div>

    <!-- Event Details Modal -->
    <x-calendar.event-modal />

<x-slot:scripts>
<script>
    // ================================================================
    // Scheduling Panel Alpine.js Component
    // ================================================================
    function schedulingPanel() {
        return {
            teacherType: @js($teacherType),
            activeTab: '',
            tabs: @js($tabs),
            items: [],
            selectedItem: null,
            loading: false,
            submitting: false,
            // Schedule form fields
            scheduleDays: [],
            scheduleStartDate: '',
            scheduleHour: 10,
            scheduleMinute: '00',
            schedulePeriod: 'AM',
            sessionCount: 4,
            error: null,
            success: null,

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
                    const response = await fetch(
                        `{{ route('teacher.calendar.schedulable-items', ['subdomain' => $subdomain]) }}?tab=${this.activeTab}`,
                        {
                            headers: {
                                'X-Requested-With': 'XMLHttpRequest',
                                'Accept': 'application/json'
                            }
                        }
                    );
                    if (!response.ok) {
                        throw new Error('Network response was not ok');
                    }
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
                    const response = await fetch(@js(route('teacher.calendar.schedule', ['subdomain' => $subdomain])), {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json',
                            'X-CSRF-TOKEN': @js(csrf_token()),
                            'Accept': 'application/json'
                        },
                        body: JSON.stringify({
                            item_id: this.selectedItem.id,
                            item_type: this.selectedItem.type,
                            schedule_days: this.scheduleDays,
                            schedule_time: this.scheduleTime,
                            schedule_start_date: this.scheduleStartDate,
                            session_count: this.sessionCount,
                        })
                    });

                    const data = await response.json();
                    if (response.ok) {
                        this.success = data.message || @js(__('teacher.calendar.schedule_success'));
                        this.selectedItem = null;
                        this.fetchItems();
                        // Refresh calendar after short delay
                        setTimeout(() => fetchEventsForMonth(), 1000);
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
                if (idx > -1) {
                    this.scheduleDays.splice(idx, 1);
                } else {
                    this.scheduleDays.push(day);
                }
            },

            isDaySelected(day) {
                return this.scheduleDays.includes(day);
            },

            getSchedulingStatusLabel(status) {
                const labels = {
                    'fully_scheduled': @js(__('teacher.calendar.status_fully_scheduled')),
                    'partially_scheduled': @js(__('teacher.calendar.status_partially_scheduled')),
                    'unscheduled': @js(__('teacher.calendar.status_unscheduled')),
                    'scheduled': @js(__('teacher.calendar.status_scheduled'))
                };
                return labels[status] || status;
            },

            getEmptyTitle() {
                const titles = {
                    'group_circles': @js(__('teacher.calendar.no_group_circles')),
                    'individual_circles': @js(__('teacher.calendar.no_individual_circles')),
                    'trial_sessions': @js(__('teacher.calendar.no_trial_sessions')),
                    'private_lessons': @js(__('teacher.calendar.no_private_lessons')),
                    'interactive_courses': @js(__('teacher.calendar.no_interactive_courses'))
                };
                return titles[this.activeTab] || @js(__('teacher.calendar.no_group_circles'));
            },

            getEmptyDescription() {
                const descriptions = {
                    'group_circles': @js(__('teacher.calendar.no_group_circles_desc')),
                    'individual_circles': @js(__('teacher.calendar.no_individual_circles_desc')),
                    'trial_sessions': @js(__('teacher.calendar.no_trial_sessions_desc')),
                    'private_lessons': @js(__('teacher.calendar.no_private_lessons_desc')),
                    'interactive_courses': @js(__('teacher.calendar.no_interactive_courses_desc'))
                };
                return descriptions[this.activeTab] || @js(__('teacher.calendar.no_group_circles_desc'));
            }
        }
    }

    // ================================================================
    // Calendar Engine (shared with student calendar)
    // ================================================================

    // Live clock in academy timezone
    const academyTimezone = @js(\App\Services\AcademyContextService::getTimezone());
    function updateLiveClock() {
        const el = document.getElementById('live-clock');
        if (!el) return;
        const now = new Date().toLocaleTimeString('ar-SA', {
            timeZone: academyTimezone,
            hour: '2-digit',
            minute: '2-digit',
            second: '2-digit',
            hour12: true
        });
        el.textContent = now;
    }
    updateLiveClock();
    setInterval(updateLiveClock, 1000);

    // Calendar data
    let currentDate = new Date();
    let eventsData = @json($events);

    // Arabic month names
    const arabicMonths = [
        @js(__('student.calendar.months.january')), @js(__('student.calendar.months.february')), @js(__('student.calendar.months.march')),
        @js(__('student.calendar.months.april')), @js(__('student.calendar.months.may')), @js(__('student.calendar.months.june')),
        @js(__('student.calendar.months.july')), @js(__('student.calendar.months.august')), @js(__('student.calendar.months.september')),
        @js(__('student.calendar.months.october')), @js(__('student.calendar.months.november')), @js(__('student.calendar.months.december'))
    ];

    // Initialize calendar
    function initCalendar() {
        updateStats();
        renderCalendar();
    }

    // Render calendar
    function renderCalendar() {
        const year = currentDate.getFullYear();
        const month = currentDate.getMonth();

        // Update header
        document.getElementById('current-month').textContent = `${arabicMonths[month]} ${year}`;

        // Get first day of month and total days
        const firstDay = new Date(year, month, 1);
        const lastDay = new Date(year, month + 1, 0);
        const totalDays = lastDay.getDate();

        // Get day of week (0 = Sunday, 6 = Saturday)
        // Adjust for Saturday as first day
        let startDay = firstDay.getDay();
        startDay = (startDay + 1) % 7;

        // Get previous month's last days
        const prevMonthLastDay = new Date(year, month, 0).getDate();
        const prevMonthDays = startDay;

        // Calculate total cells needed
        const totalCells = Math.ceil((startDay + totalDays) / 7) * 7;

        // Build calendar days HTML
        let html = '';

        // Previous month days
        for (let i = prevMonthDays - 1; i >= 0; i--) {
            const day = prevMonthLastDay - i;
            html += `<div class="calendar-day other-month" role="gridcell">
                <div class="day-number">${day}</div>
            </div>`;
        }

        // Current month days
        const today = new Date();
        for (let day = 1; day <= totalDays; day++) {
            const isToday = (
                day === today.getDate() &&
                month === today.getMonth() &&
                year === today.getFullYear()
            );

            const dateStr = `${year}-${String(month + 1).padStart(2, '0')}-${String(day).padStart(2, '0')}`;
            const dayEvents = getEventsForDate(dateStr);

            html += `<div class="calendar-day ${isToday ? 'today' : ''}" role="gridcell" aria-label="${day} ${arabicMonths[month]}">
                <div class="day-number">${day}</div>
                ${renderDayEvents(dayEvents)}
            </div>`;
        }

        // Next month days
        const remainingCells = totalCells - (prevMonthDays + totalDays);
        for (let day = 1; day <= remainingCells; day++) {
            html += `<div class="calendar-day other-month" role="gridcell">
                <div class="day-number">${day}</div>
            </div>`;
        }

        // Get the calendar grid and remove all day cells (keep headers)
        const calendarGrid = document.getElementById('calendar-grid');
        const children = Array.from(calendarGrid.children);
        children.slice(7).forEach(child => child.remove());

        // Append new days HTML
        calendarGrid.insertAdjacentHTML('beforeend', html);

        // Update stats
        updateStats();
    }

    // Update stats based on current month's events
    function updateStats() {
        const SessionStatus = {
            SCHEDULED: 'scheduled',
            COMPLETED: 'completed',
            CANCELLED: 'cancelled'
        };

        const stats = {
            total: eventsData.length,
            scheduled: eventsData.filter(e => e.status === SessionStatus.SCHEDULED).length,
            completed: eventsData.filter(e => e.status === SessionStatus.COMPLETED).length,
            cancelled: eventsData.filter(e => e.status === SessionStatus.CANCELLED).length
        };

        const totalEl = document.getElementById('stat-total');
        const scheduledEl = document.getElementById('stat-scheduled');
        const completedEl = document.getElementById('stat-completed');
        const cancelledEl = document.getElementById('stat-cancelled');

        if (totalEl) totalEl.textContent = stats.total;
        if (scheduledEl) scheduledEl.textContent = stats.scheduled;
        if (completedEl) completedEl.textContent = stats.completed;
        if (cancelledEl) cancelledEl.textContent = stats.cancelled;
    }

    // Get events for specific date
    function getEventsForDate(dateStr) {
        return eventsData.filter(event => {
            const eventDate = new Date(event.start_time);
            const eventDateStr = `${eventDate.getFullYear()}-${String(eventDate.getMonth() + 1).padStart(2, '0')}-${String(eventDate.getDate()).padStart(2, '0')}`;
            return eventDateStr === dateStr;
        });
    }

    // Render events for a day
    function renderDayEvents(events) {
        if (events.length === 0) return '';

        let html = '<div class="space-y-1">';

        // Show maximum 3 events
        const displayEvents = events.slice(0, 3);

        displayEvents.forEach(event => {
            const time = new Date(event.start_time).toLocaleTimeString('ar-SA', {
                hour: '2-digit',
                minute: '2-digit',
                hour12: true
            });

            const statusClass = `status-${event.status}`;
            const eventTitle = truncate(event.title, 30);

            html += `<div class="calendar-event ${statusClass}" onclick='showEventModal(${JSON.stringify(event)})'>
                <i class="ri-time-line text-xs"></i>
                <span class="flex-1 truncate">${htmlEscape(eventTitle)}</span>
            </div>`;
        });

        // Show "more" indicator if there are additional events
        if (events.length > 3) {
            html += `<div class="text-xs text-gray-500 pe-2">+${events.length - 3} ${@js(__('student.calendar.more_sessions'))}</div>`;
        }

        html += '</div>';
        return html;
    }

    // Show event modal
    function showEventModal(event) {
        const modal = document.getElementById('event-modal');
        const modalHeader = document.getElementById('modal-header');

        // Set title
        document.getElementById('modal-title').textContent = event.title;

        // Set status-based gradient colors for modal header
        const statusGradients = {
            'scheduled': 'bg-gradient-to-br from-blue-500 to-blue-600',
            'ongoing': 'bg-gradient-to-br from-yellow-500 to-yellow-600',
            'completed': 'bg-gradient-to-br from-green-500 to-green-600',
            'cancelled': 'bg-gradient-to-br from-red-500 to-red-600'
        };

        modalHeader.className = `relative ${statusGradients[event.status] || 'bg-gradient-to-br from-blue-500 to-blue-600'} p-6 rounded-t-xl`;

        // Set status badge and session type badge
        const statusBadge = document.getElementById('modal-status');
        const statusInfo = getStatusInfo(event.status);
        const typeLabel = getEventTypeLabel(event.source);
        statusBadge.className = `inline-flex items-center gap-3 mb-3`;
        statusBadge.innerHTML = `
            <span class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-full text-xs font-bold bg-white/20 backdrop-blur-sm border border-white/30 text-white">
                <i class="${statusInfo.icon}"></i> ${statusInfo.label}
            </span>
            <span class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-full text-xs font-bold bg-white/10 backdrop-blur-sm border border-white/20 text-white/90">
                ${typeLabel}
            </span>
        `;

        // Set date and time
        const startDate = new Date(event.start_time);
        const endDate = new Date(event.end_time);

        const dateStr = startDate.toLocaleDateString('ar-SA', {
            weekday: 'long',
            year: 'numeric',
            month: 'long',
            day: 'numeric'
        });

        const timeStr = `${startDate.toLocaleTimeString('ar-SA', { hour: '2-digit', minute: '2-digit', hour12: true })} - ${endDate.toLocaleTimeString('ar-SA', { hour: '2-digit', minute: '2-digit', hour12: true })}`;

        document.getElementById('modal-date').textContent = dateStr;
        document.getElementById('modal-time').textContent = timeStr;

        // Set duration
        document.getElementById('modal-duration').textContent = `${event.duration_minutes} ${@js(__('student.calendar.duration'))}`;

        // Set teacher info container — for teacher view, show student info instead
        const teacherContainer = document.getElementById('modal-teacher-container');
        const teacherEl = document.getElementById('modal-teacher');

        if (event.participants && event.participants.length > 0) {
            const students = event.participants.filter(p => p.role !== 'teacher');
            if (students.length > 0) {
                teacherContainer.classList.remove('hidden');
                // Show the first student as the main person
                const mainStudent = students[0];
                const studentName = encodeURIComponent(mainStudent.name);
                const avatarBgColor = mainStudent.gender === 'female' ? 'ec4899' : '3b82f6';
                const avatarUrl = `https://ui-avatars.com/api/?name=${studentName}&background=${avatarBgColor}&color=fff&size=128&bold=true&format=svg`;

                let label = @js(__('student.calendar.student_label'));
                if (students.length > 1) {
                    label += ` (+${students.length - 1})`;
                }

                teacherEl.innerHTML = `
                    <div class="w-12 h-12 rounded-full overflow-hidden flex-shrink-0 shadow-md ring-2 ring-white">
                        <img src="${avatarUrl}" alt="${htmlEscape(mainStudent.name)}" class="w-full h-full object-cover">
                    </div>
                    <div class="flex-1">
                        <p class="text-base font-bold text-gray-900">${htmlEscape(mainStudent.name)}</p>
                        <p class="text-xs text-gray-500">${label}</p>
                    </div>
                `;
            } else {
                teacherContainer.classList.add('hidden');
            }
        } else {
            teacherContainer.classList.add('hidden');
        }

        // Set description
        if (event.description) {
            document.getElementById('modal-description-container').classList.remove('hidden');
            document.getElementById('modal-description').textContent = event.description;
        } else {
            document.getElementById('modal-description-container').classList.add('hidden');
        }

        // Set all participants list
        if (event.participants && event.participants.length > 0) {
            const students = event.participants.filter(p => p.role !== 'teacher');
            if (students.length > 1) {
                document.getElementById('modal-participants-container').classList.remove('hidden');
                const participantsHtml = students.map(p =>
                    `<div class="flex items-center gap-3 p-3 bg-white border border-gray-200 rounded-lg hover:border-blue-300 transition-colors">
                        <div class="w-10 h-10 bg-gradient-to-br from-blue-400 to-blue-600 rounded-full flex items-center justify-center flex-shrink-0 shadow-sm">
                            <span class="text-white font-bold text-sm">${htmlEscape(p.name.charAt(0))}</span>
                        </div>
                        <div class="flex-1 min-w-0">
                            <p class="text-sm font-semibold text-gray-900">${htmlEscape(p.name)}</p>
                            <p class="text-xs text-gray-500">${@js(__('student.calendar.student_label'))}</p>
                        </div>
                    </div>`
                ).join('');
                document.getElementById('modal-participants').innerHTML = participantsHtml;
            } else {
                document.getElementById('modal-participants-container').classList.add('hidden');
            }
        } else {
            document.getElementById('modal-participants-container').classList.add('hidden');
        }

        // Set view button URL
        document.getElementById('modal-view-button').href = event.url || '#';

        // Show modal
        modal.classList.add('active');
    }

    // Close modal
    function closeModal(event) {
        if (!event || event.target.id === 'event-modal') {
            document.getElementById('event-modal').classList.remove('active');
        }
    }

    // Change month
    function changeMonth(delta) {
        currentDate.setMonth(currentDate.getMonth() + delta);
        fetchEventsForMonth();
    }

    // Go to today
    function goToToday() {
        currentDate = new Date();
        fetchEventsForMonth();
    }

    // Fetch events for current month
    function fetchEventsForMonth() {
        const year = currentDate.getFullYear();
        const month = currentDate.getMonth();

        // Get first and last day of month
        const firstDayOfMonth = new Date(year, month, 1);
        const lastDayOfMonth = new Date(year, month + 1, 0);

        // Extend to include full weeks (match PHP logic)
        const startDate = new Date(firstDayOfMonth);
        startDate.setDate(startDate.getDate() - ((startDate.getDay() + 1) % 7));

        const endDate = new Date(lastDayOfMonth);
        const daysToAdd = (6 - ((endDate.getDay() + 1) % 7)) % 7;
        endDate.setDate(endDate.getDate() + daysToAdd);

        // Format dates for API
        const startStr = startDate.toISOString().split('T')[0];
        const endStr = endDate.toISOString().split('T')[0];

        // Fetch events via AJAX
        fetch(`{{ $eventsRoute }}?start=${startStr}&end=${endStr}`, {
            headers: {
                'X-Requested-With': 'XMLHttpRequest',
                'Accept': 'application/json'
            }
        })
        .then(response => response.json())
        .then(data => {
            eventsData = data;
            renderCalendar();
        })
        .catch(error => {
            renderCalendar(); // Render anyway with existing data
        });
    }

    // Helper functions
    function htmlEscape(str) {
        const div = document.createElement('div');
        div.appendChild(document.createTextNode(String(str ?? '')));
        return div.innerHTML;
    }

    function truncate(str, length) {
        return str.length > length ? str.substring(0, length) + '...' : str;
    }

    function getEventTypeLabel(source) {
        const labels = {
            'quran_session': @js(__('student.calendar.quran_individual_session')),
            'circle_session': @js(__('student.calendar.quran_circle_session')),
            'course_session': @js(__('student.calendar.course_session')),
            'academic_session': @js(__('student.calendar.academic_session'))
        };
        return labels[source] || @js(__('student.calendar.session_default'));
    }

    function getStatusInfo(status) {
        const SessionStatus = {
            SCHEDULED: 'scheduled',
            ONGOING: 'ongoing',
            COMPLETED: 'completed',
            CANCELLED: 'cancelled'
        };

        const statusMap = {
            [SessionStatus.SCHEDULED]: { label: @js(__('student.calendar.status_scheduled')), icon: 'ri-calendar-check-line', class: 'bg-blue-100 text-blue-700' },
            [SessionStatus.ONGOING]: { label: @js(__('student.calendar.status_ongoing')), icon: 'ri-live-line', class: 'bg-yellow-100 text-yellow-700' },
            [SessionStatus.COMPLETED]: { label: @js(__('student.calendar.status_completed')), icon: 'ri-checkbox-circle-line', class: 'bg-green-100 text-green-700' },
            [SessionStatus.CANCELLED]: { label: @js(__('student.calendar.status_cancelled')), icon: 'ri-close-circle-line', class: 'bg-red-100 text-red-700' }
        };
        return statusMap[status] || statusMap[SessionStatus.SCHEDULED];
    }

    // Initialize on page load
    document.addEventListener('DOMContentLoaded', initCalendar);

    // Close modal on Escape key
    document.addEventListener('keydown', function(event) {
        if (event.key === 'Escape') {
            closeModal();
        }
    });
</script>
</x-slot:scripts>

</x-layouts.teacher>
