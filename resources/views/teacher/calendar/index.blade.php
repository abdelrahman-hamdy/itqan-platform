<x-layouts.teacher :title="__('teacher.calendar.page_title') . ' - ' . config('app.name')">

@php
    $subdomain = request()->route('subdomain') ?? auth()->user()->academy->subdomain ?? 'itqan-academy';
    $eventsRoute = route('teacher.calendar.events', ['subdomain' => $subdomain]);
    $rescheduleRoute = route('teacher.calendar.reschedule', ['subdomain' => $subdomain]);
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

    {{-- ================================================================ --}}
    {{-- Scheduling Panel (full width, above calendar)                    --}}
    {{-- ================================================================ --}}
    <div class="mb-6" x-data="schedulingPanel()">
        <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-4 md:p-6">

            <!-- Panel Header + Collapse Toggle -->
            <div class="flex items-center justify-between cursor-pointer" @click="panelOpen = !panelOpen">
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
            <div x-show="panelOpen" x-collapse>
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

                <!-- Content: Items Grid + Scheduling Form side by side -->
                <div class="mt-4 flex flex-col lg:flex-row gap-6">

                    <!-- Items List (left side) -->
                    <div class="w-full lg:w-1/2">
                        <!-- Loading -->
                        <template x-if="loading">
                            <div class="flex items-center justify-center py-8">
                                <div class="animate-spin rounded-full h-8 w-8 border-b-2 border-blue-600"></div>
                            </div>
                        </template>

                        <!-- Empty State -->
                        <template x-if="!loading && items.length === 0">
                            <div class="text-center py-6">
                                <i class="ri-inbox-line text-3xl text-gray-300 mb-2"></i>
                                <p class="text-sm font-semibold text-gray-600" x-text="getEmptyTitle()"></p>
                                <p class="text-xs text-gray-400 mt-1" x-text="getEmptyDescription()"></p>
                            </div>
                        </template>

                        <!-- Items -->
                        <template x-if="!loading && items.length > 0">
                            <div class="grid grid-cols-1 sm:grid-cols-2 gap-3 max-h-[300px] overflow-y-auto pe-1">
                                <template x-for="item in items" :key="item.id">
                                    <div @click="selectItem(item)"
                                         :class="selectedItem?.id === item.id
                                             ? 'border-blue-500 bg-blue-50 ring-1 ring-blue-200'
                                             : 'border-gray-200 hover:border-blue-300 hover:bg-gray-50'"
                                         class="border rounded-lg p-3 cursor-pointer transition-all">
                                        <div class="flex items-start gap-2">
                                            <div class="flex-1 min-w-0">
                                                <p class="text-sm font-semibold text-gray-900 truncate" x-text="item.name || item.title"></p>
                                                <p class="text-xs text-gray-500 mt-0.5" x-text="item.subtitle || ''"></p>
                                            </div>
                                            <template x-if="item.scheduling_status">
                                                <span :class="{
                                                    'bg-green-100 text-green-700': item.scheduling_status === 'fully_scheduled',
                                                    'bg-amber-100 text-amber-700': item.scheduling_status === 'partially_scheduled',
                                                    'bg-gray-100 text-gray-600': item.scheduling_status === 'unscheduled'
                                                }" class="text-xs px-2 py-0.5 rounded-full whitespace-nowrap flex-shrink-0"
                                                   x-text="getSchedulingStatusLabel(item.scheduling_status)">
                                                </span>
                                            </template>
                                        </div>
                                        <template x-if="item.remaining_sessions !== undefined">
                                            <p class="text-xs text-blue-600 mt-1.5">
                                                <i class="ri-calendar-todo-line me-0.5"></i>
                                                {{ __('teacher.calendar.remaining') }}: <span x-text="item.remaining_sessions"></span>
                                            </p>
                                        </template>
                                    </div>
                                </template>
                            </div>
                        </template>
                    </div>

                    <!-- Schedule Form (right side, shown when item selected) -->
                    <template x-if="selectedItem">
                        <div class="w-full lg:w-1/2 bg-gray-50 border border-gray-200 rounded-xl p-4">
                            <h3 class="text-sm font-bold text-gray-900 mb-3">
                                <i class="ri-calendar-schedule-line me-1 text-blue-600"></i>
                                {{ __('teacher.calendar.schedule_for') }}:
                                <span class="text-blue-700" x-text="selectedItem.name || selectedItem.title"></span>
                            </h3>

                            <!-- Days Selection -->
                            <div class="mb-3">
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
                            </div>

                            <!-- Start Date + Time + Session Count -->
                            <div class="grid grid-cols-2 sm:grid-cols-4 gap-3 mb-3">
                                <div>
                                    <label class="block text-xs font-semibold text-gray-700 mb-1">{{ __('teacher.calendar.start_date') }}</label>
                                    <input type="date" x-model="scheduleStartDate"
                                           class="w-full min-h-[36px] px-2 py-1.5 border border-gray-300 rounded-lg text-sm focus:ring-2 focus:ring-blue-500 focus:border-blue-500">
                                </div>
                                <div>
                                    <label class="block text-xs font-semibold text-gray-700 mb-1">{{ __('teacher.calendar.time') }}</label>
                                    <div class="flex gap-1">
                                        <select x-model="scheduleHour" class="flex-1 min-h-[36px] px-1 py-1 border border-gray-300 rounded-lg text-sm">
                                            <template x-for="h in 12" :key="h">
                                                <option :value="h" x-text="h"></option>
                                            </template>
                                        </select>
                                        <select x-model="scheduleMinute" class="w-14 min-h-[36px] px-1 py-1 border border-gray-300 rounded-lg text-sm">
                                            <option value="00">00</option>
                                            <option value="15">15</option>
                                            <option value="30">30</option>
                                            <option value="45">45</option>
                                        </select>
                                        <select x-model="schedulePeriod" class="w-14 min-h-[36px] px-1 py-1 border border-gray-300 rounded-lg text-sm">
                                            <option value="AM">{{ __('teacher.calendar.am') }}</option>
                                            <option value="PM">{{ __('teacher.calendar.pm') }}</option>
                                        </select>
                                    </div>
                                </div>
                                <div>
                                    <label class="block text-xs font-semibold text-gray-700 mb-1">{{ __('teacher.calendar.session_count') }}</label>
                                    <input type="number" x-model.number="sessionCount" min="1" max="50"
                                           class="w-full min-h-[36px] px-2 py-1.5 border border-gray-300 rounded-lg text-sm focus:ring-2 focus:ring-blue-500 focus:border-blue-500">
                                </div>
                                <div class="flex items-end">
                                    <button @click="submitSchedule()" :disabled="submitting || scheduleDays.length === 0 || !scheduleStartDate"
                                            class="w-full min-h-[36px] inline-flex items-center justify-center gap-1.5 px-4 py-1.5 bg-blue-600 text-white text-sm font-medium rounded-lg hover:bg-blue-700 disabled:opacity-50 disabled:cursor-not-allowed transition-colors">
                                        <template x-if="submitting">
                                            <div class="animate-spin rounded-full h-4 w-4 border-2 border-white border-t-transparent"></div>
                                        </template>
                                        <template x-if="!submitting">
                                            <i class="ri-calendar-check-line"></i>
                                        </template>
                                        <span x-text="submitting ? '{{ __('teacher.calendar.scheduling') }}' : '{{ __('teacher.calendar.schedule_button') }}'"></span>
                                    </button>
                                </div>
                            </div>
                        </div>
                    </template>
                </div>
            </div>
        </div>
    </div>

    {{-- ================================================================ --}}
    {{-- FullCalendar (full width)                                        --}}
    {{-- ================================================================ --}}
    <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-4 md:p-6">
        <div id="fullcalendar"></div>
    </div>

    <!-- Event Details Modal -->
    <x-calendar.event-modal />

<x-slot:head>
    {{-- FullCalendar CSS --}}
    <link href="https://cdn.jsdelivr.net/npm/fullcalendar@6.1.15/index.global.min.css" rel="stylesheet">
    <style>
        /* FullCalendar RTL & theme overrides */
        .fc {
            direction: rtl;
            font-family: 'Tajawal', sans-serif;
        }
        .fc .fc-toolbar-title {
            font-size: 1.25rem;
            font-weight: 700;
        }
        .fc .fc-button {
            background-color: #f3f4f6;
            border: 1px solid #e5e7eb;
            color: #374151;
            font-size: 0.875rem;
            font-weight: 500;
            padding: 0.375rem 0.75rem;
            border-radius: 0.5rem;
            transition: all 0.15s;
        }
        .fc .fc-button:hover {
            background-color: #e5e7eb;
            border-color: #d1d5db;
        }
        .fc .fc-button-primary:not(:disabled).fc-button-active,
        .fc .fc-button-primary:not(:disabled):active {
            background-color: #2563eb;
            border-color: #2563eb;
            color: #fff;
        }
        .fc .fc-today-button {
            background-color: #eff6ff;
            border-color: #bfdbfe;
            color: #2563eb;
        }
        .fc .fc-today-button:hover {
            background-color: #dbeafe;
        }
        .fc .fc-day-today {
            background-color: #eff6ff !important;
        }
        .fc .fc-daygrid-day-number {
            padding: 6px 8px;
            font-weight: 600;
            font-size: 0.875rem;
        }
        .fc .fc-col-header-cell-cushion {
            font-weight: 700;
            font-size: 0.8rem;
            color: #6b7280;
            padding: 8px 4px;
        }
        .fc .fc-event {
            border-radius: 0.375rem;
            padding: 2px 6px;
            font-size: 0.75rem;
            font-weight: 500;
            cursor: pointer;
            border: none;
            margin-bottom: 1px;
        }
        .fc .fc-event:hover {
            filter: brightness(0.9);
        }
        .fc .fc-daygrid-event-dot {
            display: none;
        }
        .fc .fc-event-time {
            font-weight: 600;
            font-size: 0.7rem;
        }
        .fc .fc-event-title {
            font-weight: 500;
        }
        /* Status colors */
        .fc-event-scheduled { background-color: #3b82f6 !important; color: #fff !important; }
        .fc-event-ready { background-color: #6366f1 !important; color: #fff !important; }
        .fc-event-ongoing { background-color: #f59e0b !important; color: #fff !important; }
        .fc-event-live { background-color: #f59e0b !important; color: #fff !important; }
        .fc-event-completed { background-color: #10b981 !important; color: #fff !important; }
        .fc-event-cancelled { background-color: #ef4444 !important; color: #fff !important; }
        .fc-event-absent { background-color: #f97316 !important; color: #fff !important; }
        /* Drag & drop visual feedback */
        .fc-event-dragging {
            opacity: 0.8;
            box-shadow: 0 4px 12px rgba(0,0,0,0.15);
        }
        .fc .fc-highlight {
            background-color: rgba(59, 130, 246, 0.1) !important;
        }
        /* Responsive */
        @media (max-width: 640px) {
            .fc .fc-toolbar {
                flex-direction: column;
                gap: 0.5rem;
            }
            .fc .fc-toolbar-chunk {
                display: flex;
                justify-content: center;
            }
        }
    </style>
</x-slot:head>

<x-slot:scripts>
{{-- FullCalendar JS --}}
<script src="https://cdn.jsdelivr.net/npm/fullcalendar@6.1.15/index.global.min.js"></script>
<script>
    // ================================================================
    // Scheduling Panel Alpine.js Component
    // ================================================================
    function schedulingPanel() {
        return {
            teacherType: @js($teacherType),
            panelOpen: false,
            activeTab: '',
            tabs: @js($tabs),
            items: [],
            selectedItem: null,
            loading: false,
            submitting: false,
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
                        { headers: { 'X-Requested-With': 'XMLHttpRequest', 'Accept': 'application/json' } }
                    );
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
                        // Refresh FullCalendar
                        if (window.teacherCalendar) {
                            window.teacherCalendar.refetchEvents();
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
    // FullCalendar Initialization
    // ================================================================
    document.addEventListener('DOMContentLoaded', function() {
        const calendarEl = document.getElementById('fullcalendar');
        const academyTimezone = @js(\App\Services\AcademyContextService::getTimezone());
        const eventsRoute = @js($eventsRoute);
        const rescheduleRoute = @js($rescheduleRoute);

        // Live clock
        function updateLiveClock() {
            const el = document.getElementById('live-clock');
            if (!el) return;
            el.textContent = new Date().toLocaleTimeString('ar-SA', {
                timeZone: academyTimezone,
                hour: '2-digit', minute: '2-digit', second: '2-digit', hour12: true
            });
        }
        updateLiveClock();
        setInterval(updateLiveClock, 1000);

        // Status color map
        const statusColors = {
            'scheduled': '#3b82f6',
            'ready': '#6366f1',
            'ongoing': '#f59e0b',
            'live': '#f59e0b',
            'completed': '#10b981',
            'cancelled': '#ef4444',
            'absent': '#f97316'
        };

        // Source labels
        const sourceLabels = {
            'quran_session': @js(__('student.calendar.quran_individual_session')),
            'circle_session': @js(__('student.calendar.quran_circle_session')),
            'course_session': @js(__('student.calendar.course_session')),
            'academic_session': @js(__('student.calendar.academic_session'))
        };

        // Status labels
        const statusLabels = {
            'scheduled': @js(__('student.calendar.status_scheduled')),
            'ongoing': @js(__('student.calendar.status_ongoing')),
            'completed': @js(__('student.calendar.status_completed')),
            'cancelled': @js(__('student.calendar.status_cancelled'))
        };

        // HTML escape helper
        function htmlEscape(str) {
            const div = document.createElement('div');
            div.appendChild(document.createTextNode(String(str ?? '')));
            return div.innerHTML;
        }

        // Initialize FullCalendar
        const calendar = new FullCalendar.Calendar(calendarEl, {
            direction: 'rtl',
            locale: 'ar',
            timeZone: academyTimezone,
            initialView: 'dayGridMonth',
            headerToolbar: {
                start: 'prev,next today',
                center: 'title',
                end: 'dayGridMonth,timeGridWeek,timeGridDay,listWeek'
            },
            buttonText: {
                today: @js(__('student.calendar.today')),
                month: @js(__('teacher.calendar.view_month')),
                week: @js(__('teacher.calendar.view_week')),
                day: @js(__('teacher.calendar.view_day')),
                list: @js(__('teacher.calendar.view_list'))
            },
            firstDay: 6, // Saturday
            height: 'auto',
            editable: true,
            eventStartEditable: true,
            eventDurationEditable: true,
            selectable: false,
            dayMaxEvents: 4,
            moreLinkText: function(n) { return '+' + n + ' {{ __("student.calendar.more_sessions") }}'; },
            nowIndicator: true,

            // Event source — fetch from API
            events: function(info, successCallback, failureCallback) {
                const startStr = info.startStr.split('T')[0];
                const endStr = info.endStr.split('T')[0];

                fetch(`${eventsRoute}?start=${startStr}&end=${endStr}`, {
                    headers: { 'X-Requested-With': 'XMLHttpRequest', 'Accept': 'application/json' }
                })
                .then(r => r.json())
                .then(data => {
                    const events = (Array.isArray(data) ? data : []).map(event => ({
                        id: event.id,
                        title: event.title,
                        start: event.start_time,
                        end: event.end_time,
                        backgroundColor: statusColors[event.status] || event.color || '#3b82f6',
                        borderColor: 'transparent',
                        classNames: ['fc-event-' + (event.status || 'scheduled')],
                        extendedProps: {
                            status: event.status,
                            source: event.source,
                            description: event.description,
                            duration_minutes: event.duration_minutes,
                            participants: event.participants,
                            url: event.url,
                            session_type: event.session_type,
                            originalEvent: event
                        }
                    }));
                    successCallback(events);
                })
                .catch(() => failureCallback());
            },

            // Click event → show modal
            eventClick: function(info) {
                info.jsEvent.preventDefault();
                const event = info.event;
                const props = event.extendedProps;
                showEventModal(props.originalEvent || {
                    title: event.title,
                    start_time: event.startStr,
                    end_time: event.endStr,
                    status: props.status,
                    source: props.source,
                    description: props.description,
                    duration_minutes: props.duration_minutes,
                    participants: props.participants,
                    url: props.url
                });
            },

            // Drag & drop → reschedule session
            eventDrop: function(info) {
                const event = info.event;
                const props = event.extendedProps;

                // Only allow rescheduling of scheduled sessions
                if (props.status !== 'scheduled' && props.status !== 'ready') {
                    info.revert();
                    if (window.toast) {
                        window.toast.warning(@js(__('teacher.calendar.cannot_reschedule_status')));
                    }
                    return;
                }

                // Extract numeric session ID from prefixed ID (e.g., "quran_session_433" → 433)
                const eventId = event.id || '';
                const idParts = eventId.split('_');
                const sessionId = parseInt(idParts[idParts.length - 1]);
                const source = props.source;

                if (!sessionId || !source) {
                    info.revert();
                    return;
                }

                // Capture values BEFORE revert — info.revert() mutates event.start
                const newStartISO = event.start.toISOString();
                const newDateStr = event.start.toLocaleDateString('ar-SA', {
                    timeZone: academyTimezone,
                    weekday: 'long', year: 'numeric', month: 'long', day: 'numeric'
                });
                const newTimeStr = event.start.toLocaleTimeString('ar-SA', {
                    timeZone: academyTimezone,
                    hour: '2-digit', minute: '2-digit', hour12: true
                });

                // Revert drag immediately — we'll refetch on success
                info.revert();

                const confirmMsg = event.title + '\n' + newDateStr + ' ' + newTimeStr;

                window.confirmAction({
                    title: @js(__('teacher.calendar.confirm_reschedule')),
                    message: confirmMsg,
                    confirmText: @js(__('teacher.calendar.reschedule_confirm_btn')),
                    cancelText: @js(__('common.cancel')),
                    icon: 'ri-calendar-schedule-line',
                    isDangerous: false,
                    onConfirm: () => {
                        fetch(rescheduleRoute, {
                            method: 'PUT',
                            headers: {
                                'Content-Type': 'application/json',
                                'X-CSRF-TOKEN': @js(csrf_token()),
                                'Accept': 'application/json'
                            },
                            body: JSON.stringify({
                                source: source,
                                session_id: sessionId,
                                scheduled_at: newStartISO,
                            })
                        })
                        .then(r => r.json().then(data => ({ ok: r.ok, data })))
                        .then(({ ok, data }) => {
                            if (ok) {
                                if (window.toast) window.toast.success(data.message || @js(__('teacher.calendar.reschedule_success')));
                                if (window.teacherCalendar) window.teacherCalendar.refetchEvents();
                            } else {
                                if (window.toast) window.toast.error(data.message || @js(__('teacher.calendar.reschedule_error')));
                            }
                        })
                        .catch(() => {
                            if (window.toast) window.toast.error(@js(__('teacher.calendar.reschedule_error')));
                        });
                    }
                });
            },

            // Resize → revert (duration API not implemented)
            eventResize: function(info) {
                info.revert();
            },

            // Loading state
            loading: function(isLoading) {
                // Could add a loading indicator if needed
            }
        });

        calendar.render();
        window.teacherCalendar = calendar;
    });
</script>
</x-slot:scripts>

</x-layouts.teacher>
