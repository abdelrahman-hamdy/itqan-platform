<x-layouts.teacher :title="__('teacher.calendar.page_title') . ' - ' . config('app.name')">

@php
    $subdomain = request()->route('subdomain') ?? auth()->user()->academy->subdomain ?? 'itqan-academy';
    $eventsRoute = route('teacher.calendar.events', ['subdomain' => $subdomain]);
    $rescheduleRoute = route('teacher.calendar.reschedule', ['subdomain' => $subdomain]);
    $recommendationsRoute = route('teacher.calendar.recommendations', ['subdomain' => $subdomain]);
    $sessionDetailRoute = route('teacher.calendar.session-detail', ['subdomain' => $subdomain]);
    $updateSessionRoute = route('teacher.calendar.update-session', ['subdomain' => $subdomain]);
    $quranHomeworkRoute = route('teacher.calendar.quran-homework', ['subdomain' => $subdomain]);
    $academicHomeworkRoute = route('teacher.calendar.academic-homework', ['subdomain' => $subdomain]);
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
                                            <span :class="{
                                                'bg-green-100 text-green-700': item.status === 'fully_scheduled' || item.status === 'scheduled',
                                                'bg-amber-100 text-amber-700': item.status === 'partially_scheduled',
                                                'bg-gray-100 text-gray-600': item.status === 'not_scheduled' || item.status === 'unscheduled'
                                            }" class="text-[10px] px-2 py-0.5 rounded-full whitespace-nowrap flex-shrink-0"
                                               x-text="getSchedulingStatusLabel(item.status)">
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
                                                <p><i class="ri-user-line me-1 text-gray-400"></i> {{ __('teacher.calendar.student_label') }} <span class="font-medium text-gray-800" x-text="item.student_name || '-'"></span></p>
                                                <template x-if="item.level_label">
                                                    <p><i class="ri-bar-chart-line me-1 text-gray-400"></i> {{ __('teacher.calendar.level_label') }} <span class="font-medium text-gray-800" x-text="item.level_label"></span></p>
                                                </template>
                                                <template x-if="item.preferred_time_label">
                                                    <p><i class="ri-time-line me-1 text-gray-400"></i> {{ __('teacher.calendar.preferred_time') }} <span class="font-medium text-gray-800" x-text="item.preferred_time_label"></span></p>
                                                </template>
                                                <template x-if="item.status_arabic">
                                                    <p><i class="ri-information-line me-1 text-gray-400"></i> <span class="font-medium text-gray-800" x-text="item.status_arabic"></span></p>
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

                            <!-- Start Date + Time Fields -->
                            <div class="grid grid-cols-2 gap-3 mb-3">
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

                            <!-- Preview Box -->
                            <template x-if="scheduleDays.length > 0">
                                <div class="mb-3 bg-white border border-gray-200 rounded-lg p-3">
                                    <p class="text-[10px] font-semibold text-gray-500 uppercase mb-1.5">{{ __('teacher.calendar.schedule_preview') }}</p>
                                    <div class="space-y-1 text-xs text-gray-700">
                                        <p><span class="font-medium">{{ __('teacher.calendar.preview_sessions') }}:</span> <span x-text="sessionCount"></span></p>
                                        <p><span class="font-medium">{{ __('teacher.calendar.preview_days') }}:</span> <span x-text="scheduleDays.map(d => getDayLabel(d)).join('، ')"></span></p>
                                        <p><span class="font-medium">{{ __('teacher.calendar.preview_start') }}:</span> <span x-text="scheduleStartDate ? formatDate(scheduleStartDate) : '{{ __('teacher.calendar.today') }}'"></span></p>
                                        <p><span class="font-medium">{{ __('teacher.calendar.preview_time') }}:</span> <span x-text="scheduleHour + ':' + scheduleMinute + ' ' + schedulePeriod"></span></p>
                                    </div>
                                </div>
                            </template>

                            <!-- Submit Button (full width) -->
                            <button @click="submitSchedule()" :disabled="submitting || scheduleDays.length === 0 || sessionCount < 1"
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

    {{-- ================================================================ --}}
    {{-- FullCalendar (full width)                                        --}}
    {{-- ================================================================ --}}
    <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-4 md:p-6 w-full overflow-x-auto">
        <div id="fullcalendar" class="w-full min-w-0"></div>
    </div>

    <!-- Session Detail Modal -->
    <div x-data="sessionDetailModal()" x-cloak>
        <!-- Backdrop -->
        <div x-show="open" class="fixed inset-0 bg-black/50 z-40" @click="close()"></div>

        <!-- Modal -->
        <div x-show="open" x-transition class="fixed inset-0 z-50 flex items-center justify-center p-4" @click.self="close()">
            <div class="bg-white rounded-xl shadow-2xl w-full max-w-lg max-h-[90vh] overflow-y-auto" @click.stop>
                <!-- Header -->
                <div class="relative p-5 rounded-t-xl" :class="{
                    'bg-gradient-to-br from-blue-500 to-blue-600': session?.status === 'scheduled',
                    'bg-gradient-to-br from-indigo-500 to-indigo-600': session?.status === 'ready',
                    'bg-gradient-to-br from-amber-500 to-amber-600': session?.status === 'ongoing' || session?.status === 'live',
                    'bg-gradient-to-br from-green-500 to-green-600': session?.status === 'completed',
                    'bg-gradient-to-br from-red-500 to-red-600': session?.status === 'cancelled',
                    'bg-gradient-to-br from-gray-500 to-gray-600': !session?.status
                }">
                    <button @click="close()" class="absolute top-3 rtl:left-3 ltr:right-3 text-white/80 hover:text-white p-1 rounded-lg hover:bg-white/10 cursor-pointer">
                        <i class="ri-close-line text-xl"></i>
                    </button>
                    <div class="flex items-center gap-2 mb-2">
                        <span class="text-xs font-bold px-2.5 py-1 rounded-full bg-white/20 text-white" x-text="session?.status_label || ''"></span>
                        <span class="text-xs px-2.5 py-1 rounded-full bg-white/10 text-white/90" x-text="getSourceLabel(session?.source)"></span>
                    </div>
                    <h3 class="text-lg font-bold text-white leading-tight" x-text="session?.title || ''"></h3>
                </div>

                <!-- Loading -->
                <template x-if="loading">
                    <div class="flex items-center justify-center py-12">
                        <div class="animate-spin rounded-full h-8 w-8 border-b-2 border-blue-600"></div>
                    </div>
                </template>

                <!-- Content (view mode) -->
                <template x-if="!loading && session && !editMode">
                    <div class="p-5 space-y-4">
                        <!-- Date/Time -->
                        <div class="grid grid-cols-2 gap-3">
                            <div class="bg-blue-50 border border-blue-100 rounded-lg p-3">
                                <p class="text-[10px] font-semibold text-blue-600 uppercase mb-1"><i class="ri-calendar-event-line me-1"></i>{{ __('teacher.calendar.modal_date') }}</p>
                                <p class="text-sm font-bold text-gray-900" x-text="formatModalDate(session.scheduled_at)"></p>
                            </div>
                            <div class="bg-green-50 border border-green-100 rounded-lg p-3">
                                <p class="text-[10px] font-semibold text-green-600 uppercase mb-1"><i class="ri-time-line me-1"></i>{{ __('teacher.calendar.modal_time') }}</p>
                                <p class="text-sm font-bold text-gray-900" x-text="formatModalTime(session.scheduled_at)"></p>
                            </div>
                        </div>

                        <!-- Duration -->
                        <div class="bg-purple-50 border border-purple-100 rounded-lg p-3 flex items-center justify-between">
                            <span class="text-xs font-semibold text-purple-600"><i class="ri-hourglass-line me-1"></i>{{ __('teacher.calendar.modal_duration') }}</span>
                            <span class="text-sm font-bold text-gray-900" x-text="(session.duration_minutes || 60) + ' {{ __('teacher.calendar.minutes_short') }}'"></span>
                        </div>

                        <!-- Info rows -->
                        <div class="space-y-2">
                            <template x-if="session.student_name">
                                <div class="flex items-center gap-2 text-sm">
                                    <i class="ri-user-line text-gray-400 w-5"></i>
                                    <span class="text-gray-500">{{ __('teacher.calendar.student_label') }}</span>
                                    <span class="font-medium text-gray-900" x-text="session.student_name"></span>
                                </div>
                            </template>
                            <template x-if="session.circle_name">
                                <div class="flex items-center gap-2 text-sm">
                                    <i class="ri-group-line text-gray-400 w-5"></i>
                                    <span class="text-gray-500">{{ __('teacher.calendar.circle_label') }}</span>
                                    <span class="font-medium text-gray-900" x-text="session.circle_name"></span>
                                </div>
                            </template>
                            <template x-if="session.subject_name">
                                <div class="flex items-center gap-2 text-sm">
                                    <i class="ri-book-line text-gray-400 w-5"></i>
                                    <span class="text-gray-500">{{ __('teacher.calendar.subject_label') }}</span>
                                    <span class="font-medium text-gray-900" x-text="session.subject_name"></span>
                                </div>
                            </template>
                            <template x-if="session.course_title">
                                <div class="flex items-center gap-2 text-sm">
                                    <i class="ri-presentation-line text-gray-400 w-5"></i>
                                    <span class="text-gray-500">{{ __('teacher.calendar.course_label') }}</span>
                                    <span class="font-medium text-gray-900" x-text="session.course_title"></span>
                                </div>
                            </template>
                            <template x-if="session.meeting_link">
                                <div class="flex items-center gap-2 text-sm">
                                    <i class="ri-video-line text-gray-400 w-5"></i>
                                    <a :href="session.meeting_link" target="_blank" class="text-blue-600 hover:underline text-sm">{{ __('teacher.calendar.join_meeting') }}</a>
                                </div>
                            </template>
                            <template x-if="session.teacher_notes">
                                <div class="bg-gray-50 border border-gray-200 rounded-lg p-3 mt-2">
                                    <p class="text-[10px] font-semibold text-gray-500 uppercase mb-1">{{ __('teacher.calendar.notes_label') }}</p>
                                    <p class="text-sm text-gray-700" x-text="session.teacher_notes"></p>
                                </div>
                            </template>
                            <template x-if="session.has_homework">
                                <div class="flex items-center gap-2 text-sm">
                                    <i class="ri-task-line text-green-500 w-5"></i>
                                    <span class="text-green-600 font-medium">{{ __('teacher.calendar.has_homework') }}</span>
                                </div>
                            </template>
                        </div>

                        <!-- Action buttons -->
                        <div class="flex flex-col gap-2 pt-2 border-t border-gray-100">
                            <a :href="session.detail_url || '#'" class="w-full text-center px-4 py-2.5 bg-blue-600 text-white text-sm font-medium rounded-lg hover:bg-blue-700 transition-colors cursor-pointer">
                                <i class="ri-external-link-line me-1"></i> {{ __('teacher.calendar.view_full_session') }}
                            </a>
                            <div class="grid grid-cols-2 gap-2">
                                <template x-if="session.can_edit">
                                    <button @click="editData = { scheduled_at: session.scheduled_at ? session.scheduled_at.substring(0,16) : '', duration_minutes: session.duration_minutes || 60, teacher_notes: session.teacher_notes || '' }; editMode = true" class="px-4 py-2 bg-gray-100 text-gray-700 text-sm font-medium rounded-lg hover:bg-gray-200 transition-colors cursor-pointer">
                                        <i class="ri-edit-line me-1"></i> {{ __('teacher.calendar.edit_session') }}
                                    </button>
                                </template>
                                <button @click="openHomeworkModal()" class="px-4 py-2 bg-amber-50 text-amber-700 text-sm font-medium rounded-lg hover:bg-amber-100 border border-amber-200 transition-colors cursor-pointer">
                                    <i class="ri-task-line me-1"></i> {{ __('teacher.calendar.manage_homework') }}
                                </button>
                            </div>
                        </div>
                    </div>
                </template>

                <!-- Content (edit mode) -->
                <template x-if="!loading && session && editMode">
                    <div class="p-5 space-y-4">
                        <h4 class="text-sm font-bold text-gray-900 mb-3"><i class="ri-edit-line me-1 text-blue-600"></i>{{ __('teacher.calendar.edit_session') }}</h4>

                        <!-- Date/Time -->
                        <div>
                            <label class="block text-xs font-semibold text-gray-700 mb-1">{{ __('teacher.calendar.modal_date') }} + {{ __('teacher.calendar.modal_time') }}</label>
                            <input type="datetime-local" x-model="editData.scheduled_at"
                                   class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm focus:ring-2 focus:ring-blue-500 focus:border-blue-500">
                        </div>

                        <!-- Duration -->
                        <div>
                            <label class="block text-xs font-semibold text-gray-700 mb-1">{{ __('teacher.calendar.modal_duration') }}</label>
                            <select x-model="editData.duration_minutes" class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm">
                                <option value="30">30 {{ __('teacher.calendar.minutes_short') }}</option>
                                <option value="45">45 {{ __('teacher.calendar.minutes_short') }}</option>
                                <option value="60">60 {{ __('teacher.calendar.minutes_short') }}</option>
                                <option value="90">90 {{ __('teacher.calendar.minutes_short') }}</option>
                                <option value="120">120 {{ __('teacher.calendar.minutes_short') }}</option>
                            </select>
                        </div>

                        <!-- Notes -->
                        <div>
                            <label class="block text-xs font-semibold text-gray-700 mb-1">{{ __('teacher.calendar.notes_label') }}</label>
                            <textarea x-model="editData.teacher_notes" rows="3" maxlength="1000"
                                      class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm focus:ring-2 focus:ring-blue-500 focus:border-blue-500"
                                      placeholder="{{ __('teacher.calendar.notes_placeholder') }}"></textarea>
                        </div>

                        <!-- Save / Cancel -->
                        <div class="flex gap-2 pt-2">
                            <button @click="saveEdit()" :disabled="saving"
                                    class="flex-1 px-4 py-2.5 bg-blue-600 text-white text-sm font-medium rounded-lg hover:bg-blue-700 disabled:opacity-50 transition-colors cursor-pointer">
                                <template x-if="saving"><span class="animate-spin inline-block h-4 w-4 border-2 border-white border-t-transparent rounded-full me-1"></span></template>
                                {{ __('teacher.calendar.save_changes') }}
                            </button>
                            <button @click="editMode = false" class="px-4 py-2.5 bg-gray-100 text-gray-700 text-sm font-medium rounded-lg hover:bg-gray-200 transition-colors cursor-pointer">
                                {{ __('teacher.calendar.cancel_edit') }}
                            </button>
                        </div>
                    </div>
                </template>
            </div>
        </div>

        <!-- Homework Modal -->
        <div x-show="homeworkOpen" class="fixed inset-0 bg-black/50 z-[60]" @click="homeworkOpen = false"></div>
        <div x-show="homeworkOpen" x-transition class="fixed inset-0 z-[70] flex items-center justify-center p-4" @click.self="homeworkOpen = false">
            <div class="bg-white rounded-xl shadow-2xl w-full max-w-lg max-h-[90vh] overflow-y-auto" @click.stop>
                <div class="p-5 border-b border-gray-200 flex items-center justify-between">
                    <h3 class="text-sm font-bold text-gray-900"><i class="ri-task-line me-1 text-amber-600"></i>{{ __('teacher.calendar.manage_homework') }}</h3>
                    <button @click="homeworkOpen = false" class="text-gray-400 hover:text-gray-600 cursor-pointer"><i class="ri-close-line text-xl"></i></button>
                </div>

                <!-- Quran Homework Form -->
                <template x-if="session && (session.source === 'quran_session' || session.source === 'circle_session')">
                    <div class="p-5 space-y-4" @click="surahDropdownOpen = false">
                        <!-- New Memorization -->
                        <label class="flex items-center gap-2 cursor-pointer">
                            <input type="checkbox" x-model="hwData.has_new_memorization" class="rounded border-gray-300 text-blue-600 focus:ring-blue-500">
                            <span class="text-sm font-medium text-gray-700">{{ __('teacher.calendar.hw_new_memorization') }}</span>
                        </label>
                        <template x-if="hwData.has_new_memorization">
                            <div class="ps-6 space-y-2">
                                <select x-model="hwData.new_memorization_surah" class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm">
                                    <option value="">{{ __('teacher.calendar.hw_select_surah') }}</option>
                                    <template x-for="s in surahList" :key="s.value">
                                        <option :value="s.value" x-text="s.label"></option>
                                    </template>
                                </select>
                                <input type="number" x-model.number="hwData.new_memorization_pages" min="1" placeholder="{{ __('teacher.calendar.hw_pages') }}"
                                       class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm">
                            </div>
                        </template>

                        <!-- Review -->
                        <label class="flex items-center gap-2 cursor-pointer">
                            <input type="checkbox" x-model="hwData.has_review" class="rounded border-gray-300 text-blue-600 focus:ring-blue-500">
                            <span class="text-sm font-medium text-gray-700">{{ __('teacher.calendar.hw_review') }}</span>
                        </label>
                        <template x-if="hwData.has_review">
                            <div class="ps-6 space-y-2">
                                <select x-model="hwData.review_surah" class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm">
                                    <option value="">{{ __('teacher.calendar.hw_select_surah') }}</option>
                                    <template x-for="s in surahList" :key="s.value">
                                        <option :value="s.value" x-text="s.label"></option>
                                    </template>
                                </select>
                                <input type="number" x-model.number="hwData.review_pages" min="1" placeholder="{{ __('teacher.calendar.hw_pages') }}"
                                       class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm">
                            </div>
                        </template>

                        <!-- Comprehensive Review -->
                        <label class="flex items-center gap-2 cursor-pointer">
                            <input type="checkbox" x-model="hwData.has_comprehensive_review" class="rounded border-gray-300 text-blue-600 focus:ring-blue-500">
                            <span class="text-sm font-medium text-gray-700">{{ __('teacher.calendar.hw_comprehensive_review') }}</span>
                        </label>
                        <template x-if="hwData.has_comprehensive_review">
                            <div class="ps-6">
                                {{-- Selected tags --}}
                                <div class="flex flex-wrap gap-1 mb-2" x-show="hwData.comprehensive_review_surahs && hwData.comprehensive_review_surahs.length > 0">
                                    <template x-for="sv in (hwData.comprehensive_review_surahs || [])" :key="sv">
                                        <span class="inline-flex items-center gap-1 px-2 py-0.5 bg-blue-100 text-blue-800 text-xs rounded-full">
                                            <span x-text="surahList.find(s => s.value == sv)?.label || sv"></span>
                                            <button type="button" @click.stop="hwData.comprehensive_review_surahs = hwData.comprehensive_review_surahs.filter(v => v !== sv)"
                                                    class="text-blue-600 hover:text-blue-800 cursor-pointer">&times;</button>
                                        </span>
                                    </template>
                                </div>
                                {{-- Search input --}}
                                <div class="relative" @click.stop>
                                    <input type="text" x-model="surahSearch"
                                           @focus="surahDropdownOpen = true"
                                           @click.stop
                                           placeholder="{{ __('teacher.calendar.hw_search_surah') }}"
                                           class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm focus:ring-blue-500 focus:border-blue-500">
                                    {{-- Dropdown list --}}
                                    <div x-show="surahDropdownOpen" x-transition
                                         @click.stop
                                         class="absolute z-[60] mt-1 w-full max-h-48 overflow-y-auto bg-white border border-gray-200 rounded-lg shadow-lg">
                                        <template x-for="s in surahList.filter(s => !surahSearch || s.label.includes(surahSearch) || s.value.toString().includes(surahSearch))" :key="s.value">
                                            <button type="button"
                                                    @click.stop="if (hwData.comprehensive_review_surahs.includes(s.value)) { hwData.comprehensive_review_surahs = hwData.comprehensive_review_surahs.filter(v => v !== s.value); } else { hwData.comprehensive_review_surahs.push(s.value); }"
                                                    class="w-full text-start px-3 py-1.5 text-sm hover:bg-blue-50 cursor-pointer flex items-center justify-between"
                                                    :class="hwData.comprehensive_review_surahs.includes(s.value) ? 'bg-blue-50 text-blue-700 font-medium' : 'text-gray-700'">
                                                <span x-text="s.label"></span>
                                                <svg x-show="hwData.comprehensive_review_surahs.includes(s.value)" class="w-4 h-4 text-blue-600 flex-shrink-0" fill="currentColor" viewBox="0 0 20 20">
                                                    <path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd"/>
                                                </svg>
                                            </button>
                                        </template>
                                        <div x-show="surahList.filter(s => !surahSearch || s.label.includes(surahSearch) || s.value.toString().includes(surahSearch)).length === 0"
                                             class="px-3 py-2 text-sm text-gray-400">{{ __('teacher.calendar.hw_no_results') }}</div>
                                    </div>
                                </div>
                                <p class="text-[10px] text-gray-400 mt-1">
                                    <span x-text="(hwData.comprehensive_review_surahs || []).length"></span> {{ __('teacher.calendar.hw_selected_count') }}
                                </p>
                            </div>
                        </template>

                        <!-- Additional instructions -->
                        <div>
                            <label class="block text-xs font-semibold text-gray-700 mb-1">{{ __('teacher.calendar.hw_instructions') }}</label>
                            <textarea x-model="hwData.additional_instructions" rows="2" maxlength="2000"
                                      class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm"></textarea>
                        </div>

                        <!-- Save -->
                        <button @click="saveQuranHomework()" :disabled="hwSaving"
                                class="w-full px-4 py-2.5 bg-amber-600 text-white text-sm font-medium rounded-lg hover:bg-amber-700 disabled:opacity-50 transition-colors cursor-pointer">
                            <template x-if="hwSaving"><span class="animate-spin inline-block h-4 w-4 border-2 border-white border-t-transparent rounded-full me-1"></span></template>
                            {{ __('teacher.calendar.hw_save') }}
                        </button>
                    </div>
                </template>

                <!-- Academic Homework Form -->
                <template x-if="session && (session.source === 'academic_session' || session.source === 'course_session')">
                    <div class="p-5 space-y-4">
                        <div>
                            <label class="block text-xs font-semibold text-gray-700 mb-1">{{ __('teacher.calendar.hw_description') }}</label>
                            <textarea x-model="hwData.homework_description" rows="4" maxlength="5000"
                                      class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm"
                                      placeholder="{{ __('teacher.calendar.hw_description_placeholder') }}"></textarea>
                        </div>
                        <button @click="saveAcademicHomework()" :disabled="hwSaving || !hwData.homework_description"
                                class="w-full px-4 py-2.5 bg-amber-600 text-white text-sm font-medium rounded-lg hover:bg-amber-700 disabled:opacity-50 transition-colors cursor-pointer">
                            <template x-if="hwSaving"><span class="animate-spin inline-block h-4 w-4 border-2 border-white border-t-transparent rounded-full me-1"></span></template>
                            {{ __('teacher.calendar.hw_save') }}
                        </button>
                    </div>
                </template>
            </div>
        </div>
    </div>

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
        /* Responsive — ensure calendar fills all available width */
        .fc {
            width: 100% !important;
            max-width: 100% !important;
        }
        .fc .fc-view-harness {
            width: 100% !important;
        }
        .fc table {
            width: 100% !important;
            table-layout: fixed !important;
        }
        .fc .fc-scrollgrid {
            width: 100% !important;
        }
        .fc .fc-daygrid-body {
            width: 100% !important;
        }
        .fc .fc-daygrid-body table {
            width: 100% !important;
        }
        .fc td, .fc th {
            max-width: none !important;
        }
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
            recommendations: null,

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
                this.recommendations = null;
                this.fetchRecommendations(item);
            },

            async fetchRecommendations(item) {
                try {
                    const response = await fetch(
                        `{{ $recommendationsRoute }}?item_id=${item.id}&item_type=${item.type}`,
                        { headers: { 'X-Requested-With': 'XMLHttpRequest', 'Accept': 'application/json' } }
                    );
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
                            schedule_start_date: this.scheduleStartDate || new Date().toISOString().split('T')[0],
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
                    const d = new Date(dateStr);
                    return d.toLocaleDateString('ar-SA', { year: 'numeric', month: 'short', day: 'numeric' });
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

    // ================================================================
    // Session Detail Modal Alpine.js Component
    // ================================================================
    function sessionDetailModal() {
        return {
            open: false,
            loading: false,
            session: null,
            editMode: false,
            saving: false,
            editData: {},
            homeworkOpen: false,
            hwSaving: false,
            hwData: {},
            surahSearch: '',
            surahDropdownOpen: false,
            surahList: @js(collect(\App\Enums\QuranSurah::cases())->map(fn($s) => ['value' => $s->value, 'label' => $s->getNumber() . '. ' . $s->value])->values()),

            async show(eventData) {
                this.open = true;
                this.loading = true;
                this.editMode = false;
                this.session = null;

                const eventId = eventData.id || '';
                const idParts = eventId.split('_');
                const sessionId = parseInt(idParts[idParts.length - 1]);
                const source = eventData.source;

                if (!sessionId || !source) {
                    this.loading = false;
                    return;
                }

                try {
                    const response = await fetch(
                        `{{ $sessionDetailRoute }}?source=${source}&session_id=${sessionId}`,
                        { headers: { 'X-Requested-With': 'XMLHttpRequest', 'Accept': 'application/json' } }
                    );
                    const data = await response.json();
                    if (data.success) {
                        this.session = data.session;
                    }
                } catch (e) {
                    // Failed to load
                } finally {
                    this.loading = false;
                }
            },

            close() {
                this.open = false;
                this.editMode = false;
                this.homeworkOpen = false;
            },

            async refreshSession() {
                if (!this.session) return;
                try {
                    const response = await fetch(
                        `{{ $sessionDetailRoute }}?source=${this.session.source}&session_id=${this.session.id}`,
                        { headers: { 'X-Requested-With': 'XMLHttpRequest', 'Accept': 'application/json' } }
                    );
                    const data = await response.json();
                    if (data.success) {
                        this.session = data.session;
                    }
                } catch (e) {
                    // Silently fail
                }
            },

            getSourceLabel(source) {
                const labels = {
                    'quran_session': @js(__('student.calendar.quran_individual_session')),
                    'circle_session': @js(__('student.calendar.quran_circle_session')),
                    'course_session': @js(__('student.calendar.course_session')),
                    'academic_session': @js(__('student.calendar.academic_session'))
                };
                return labels[source] || source;
            },

            formatModalDate(isoStr) {
                if (!isoStr) return '-';
                return new Date(isoStr).toLocaleDateString('ar-SA', {
                    timeZone: @js(\App\Services\AcademyContextService::getTimezone()),
                    weekday: 'long', year: 'numeric', month: 'long', day: 'numeric'
                });
            },

            formatModalTime(isoStr) {
                if (!isoStr) return '-';
                return new Date(isoStr).toLocaleTimeString('ar-SA', {
                    timeZone: @js(\App\Services\AcademyContextService::getTimezone()),
                    hour: '2-digit', minute: '2-digit', hour12: true
                });
            },

            openHomeworkModal() {
                this.homeworkOpen = true;
                this.surahSearch = '';
                this.surahDropdownOpen = false;
                // Pre-fill homework data from session
                if (this.session?.homework_data) {
                    this.hwData = { ...this.session.homework_data };
                } else {
                    this.hwData = {
                        has_new_memorization: false,
                        has_review: false,
                        has_comprehensive_review: false,
                        new_memorization_surah: '',
                        new_memorization_pages: null,
                        review_surah: '',
                        review_pages: null,
                        comprehensive_review_surahs: [],
                        additional_instructions: '',
                        homework_description: '',
                    };
                }
            },

            async saveEdit() {
                this.saving = true;
                try {
                    const body = {
                        source: this.session.source,
                        session_id: this.session.id,
                    };
                    if (this.editData.scheduled_at) body.scheduled_at = this.editData.scheduled_at;
                    if (this.editData.duration_minutes) body.duration_minutes = parseInt(this.editData.duration_minutes);
                    if (this.editData.teacher_notes !== undefined) body.teacher_notes = this.editData.teacher_notes;

                    const response = await fetch(@js(route('teacher.calendar.update-session', ['subdomain' => $subdomain])), {
                        method: 'PUT',
                        headers: {
                            'Content-Type': 'application/json',
                            'X-CSRF-TOKEN': @js(csrf_token()),
                            'Accept': 'application/json'
                        },
                        body: JSON.stringify(body)
                    });
                    const data = await response.json();
                    if (data.success) {
                        if (window.toast) window.toast.success(data.message);
                        this.editMode = false;
                        if (window.teacherCalendar) window.teacherCalendar.refetchEvents();
                        await this.refreshSession();
                    } else {
                        if (window.toast) window.toast.error(data.message);
                    }
                } catch (e) {
                    if (window.toast) window.toast.error(@js(__('teacher.calendar.schedule_error')));
                } finally {
                    this.saving = false;
                }
            },

            async saveQuranHomework() {
                this.hwSaving = true;
                try {
                    const body = {
                        session_id: this.session.id,
                        ...this.hwData
                    };
                    const response = await fetch(@js(route('teacher.calendar.quran-homework', ['subdomain' => $subdomain])), {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json',
                            'X-CSRF-TOKEN': @js(csrf_token()),
                            'Accept': 'application/json'
                        },
                        body: JSON.stringify(body)
                    });
                    const data = await response.json();
                    if (data.success) {
                        if (window.toast) window.toast.success(data.message);
                        this.homeworkOpen = false;
                        await this.refreshSession();
                    } else {
                        if (window.toast) window.toast.error(data.message);
                    }
                } catch (e) {
                    if (window.toast) window.toast.error(@js(__('teacher.calendar.schedule_error')));
                } finally {
                    this.hwSaving = false;
                }
            },

            async saveAcademicHomework() {
                this.hwSaving = true;
                try {
                    const body = {
                        session_id: this.session.id,
                        source: this.session.source,
                        homework_description: this.hwData.homework_description,
                    };
                    const response = await fetch(@js(route('teacher.calendar.academic-homework', ['subdomain' => $subdomain])), {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json',
                            'X-CSRF-TOKEN': @js(csrf_token()),
                            'Accept': 'application/json'
                        },
                        body: JSON.stringify(body)
                    });
                    const data = await response.json();
                    if (data.success) {
                        if (window.toast) window.toast.success(data.message);
                        this.homeworkOpen = false;
                        await this.refreshSession();
                    } else {
                        if (window.toast) window.toast.error(data.message);
                    }
                } catch (e) {
                    if (window.toast) window.toast.error(@js(__('teacher.calendar.schedule_error')));
                } finally {
                    this.hwSaving = false;
                }
            }
        };
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

            // Click event → show session detail modal
            eventClick: function(info) {
                info.jsEvent.preventDefault();
                const event = info.event;
                const props = event.extendedProps;
                const eventData = props.originalEvent || {
                    id: event.id,
                    source: props.source,
                };
                // Find the sessionDetailModal Alpine component and call show()
                const modalEl = document.querySelector('[x-data="sessionDetailModal()"]');
                if (modalEl && modalEl.__x) {
                    modalEl.__x.$data.show(eventData);
                } else if (modalEl) {
                    // Alpine 3 - use $data on the Alpine instance
                    const alpineData = Alpine.$data(modalEl);
                    if (alpineData) {
                        alpineData.show(eventData);
                    }
                }
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

        // Re-render calendar when container resizes (e.g. sidebar collapse)
        const resizeObserver = new ResizeObserver(() => {
            calendar.updateSize();
        });
        resizeObserver.observe(calendarEl.parentElement);
    });
</script>
</x-slot:scripts>

</x-layouts.teacher>
