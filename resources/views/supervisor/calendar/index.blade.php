<x-layouts.supervisor>

@php
    $subdomain = request()->route('subdomain') ?? auth()->user()->academy->subdomain ?? 'itqan-academy';
    $eventsRoute = route('manage.calendar.events', ['subdomain' => $subdomain]);
    $rescheduleRoute = route('manage.calendar.reschedule', ['subdomain' => $subdomain]);
    $recommendationsRoute = route('manage.calendar.recommendations', ['subdomain' => $subdomain]);
    $sessionDetailRoute = route('manage.calendar.session-detail', ['subdomain' => $subdomain]);
    $updateSessionRoute = route('manage.calendar.update-session', ['subdomain' => $subdomain]);
    $quranHomeworkRoute = route('manage.calendar.quran-homework', ['subdomain' => $subdomain]);
    $academicHomeworkRoute = route('manage.calendar.academic-homework', ['subdomain' => $subdomain]);
@endphp

<div>
    <x-ui.breadcrumb
        :items="[
            ['label' => __('supervisor.calendar.page_title')],
        ]"
        view-type="supervisor"
    />

    <div class="mb-6 md:mb-8">
        <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-2 mb-1 md:mb-2">
            <h1 class="text-xl sm:text-2xl md:text-3xl font-bold text-gray-900">{{ __('supervisor.calendar.page_title') }}</h1>
            @if($selectedTeacher)
            <x-ui.timezone-clock />
            @endif
        </div>
        <p class="mt-1 md:mt-2 text-sm md:text-base text-gray-600">{{ __('supervisor.calendar.page_subtitle') }}</p>
    </div>

    <!-- Teacher Selector -->
    <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-4 md:p-6 mb-6">
        <div class="flex items-center gap-4 flex-wrap">
            <label class="text-sm font-medium text-gray-700 flex items-center gap-1.5 flex-shrink-0">
                <i class="ri-user-line text-gray-400"></i>
                {{ __('supervisor.calendar.select_teacher') }}
            </label>
            <select
                id="calendar-teacher-select"
                onchange="
                    var base = window.location.pathname;
                    if (this.value) {
                        window.location.href = base + '?teacher_id=' + this.value;
                    } else {
                        window.location.href = base;
                    }
                "
                class="rounded-lg border-gray-300 text-sm focus:ring-indigo-500 focus:border-indigo-500 min-w-[250px]"
            >
                <option value="">-- {{ __('supervisor.calendar.select_teacher') }} --</option>
                @foreach($teachers as $t)
                    <option value="{{ $t['id'] }}" {{ $selectedTeacherId == $t['id'] ? 'selected' : '' }}>
                        {{ $t['name'] }} ({{ $t['type_label'] }})
                    </option>
                @endforeach
            </select>
        </div>
    </div>

    @if($selectedTeacher)
        <!-- Stats Cards -->
        <x-calendar.stats-cards />

        <!-- Scheduling Panel -->
        @if(!empty($tabs))
        <x-calendar.scheduling-panel
            :schedulableItemsRoute="route('manage.calendar.schedulable-items', ['subdomain' => $subdomain])"
            :recommendationsRoute="$recommendationsRoute"
            :scheduleRoute="route('manage.calendar.schedule', ['subdomain' => $subdomain])"
            :teacherType="$teacherType"
            :tabs="$tabs"
            :teacherId="$selectedTeacherId"
            calendarVarName="supervisorCalendar"
        />
        @endif

        <!-- FullCalendar -->
        <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-4 md:p-6 w-full overflow-x-auto">
            <div id="supervisor-calendar" class="w-full min-w-0"></div>
        </div>

        <!-- Session Detail + Homework Modals -->
        <x-calendar.session-detail-modal
            :sessionDetailRoute="$sessionDetailRoute"
            :updateSessionRoute="$updateSessionRoute"
            :quranHomeworkRoute="$quranHomeworkRoute"
            :academicHomeworkRoute="$academicHomeworkRoute"
            :teacherId="$selectedTeacherId"
            calendarVarName="supervisorCalendar"
        />
    @else
        <!-- No teacher selected -->
        <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-8 md:p-12 text-center">
            <div class="w-20 h-20 bg-indigo-100 rounded-full flex items-center justify-center mx-auto mb-4">
                <i class="ri-calendar-schedule-line text-3xl text-indigo-500"></i>
            </div>
            <h3 class="text-lg font-bold text-gray-900 mb-2">{{ __('supervisor.calendar.select_teacher') }}</h3>
            <p class="text-sm text-gray-600">{{ __('supervisor.calendar.select_teacher_description') }}</p>
        </div>
    @endif
</div>

<x-slot:head>
    @if($selectedTeacher)
    <x-calendar.fullcalendar-styles />
    @endif
</x-slot:head>

<x-slot:scripts>
@if($selectedTeacher)
<script src="https://cdn.jsdelivr.net/npm/fullcalendar@6.1.15/index.global.min.js"></script>
<script>
    // ================================================================
    // FullCalendar Initialization
    // ================================================================
    document.addEventListener('DOMContentLoaded', function() {
        const calendarEl = document.getElementById('supervisor-calendar');
        if (!calendarEl) return;

        const academyTimezone = @js(\App\Services\AcademyContextService::getTimezone());
        const eventsRoute = @js($eventsRoute);
        const rescheduleRoute = @js($rescheduleRoute);
        const teacherId = @js($selectedTeacherId);

        // Stats update
        const stats = @js($stats);
        if (stats) {
            const el = (id) => document.getElementById(id);
            if (el('stat-total')) el('stat-total').textContent = stats.total || 0;
            if (el('stat-scheduled')) el('stat-scheduled').textContent = stats.scheduled || 0;
            if (el('stat-completed')) el('stat-completed').textContent = stats.completed || 0;
            if (el('stat-cancelled')) el('stat-cancelled').textContent = stats.cancelled || 0;
        }

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

        // HTML escape helper
        function htmlEscape(str) {
            const div = document.createElement('div');
            div.appendChild(document.createTextNode(String(str ?? '')));
            return div.innerHTML;
        }

        // Convert UTC ISO string to offset-less academy-local string for FullCalendar
        // FC CDN doesn't support named timezones, so we pre-convert and use timeZone:'UTC'
        function utcToAcademyLocal(isoStr) {
            if (!isoStr) return isoStr;
            const d = new Date(isoStr);
            const parts = new Intl.DateTimeFormat('en-CA', {
                timeZone: academyTimezone,
                year: 'numeric', month: '2-digit', day: '2-digit',
                hour: '2-digit', minute: '2-digit', second: '2-digit', hour12: false
            }).formatToParts(d);
            const get = type => parts.find(p => p.type === type)?.value || '';
            const hour = get('hour') === '24' ? '00' : get('hour');
            return `${get('year')}-${get('month')}-${get('day')}T${hour}:${get('minute')}:${get('second')}`;
        }

        // Initialize FullCalendar
        const calendar = new FullCalendar.Calendar(calendarEl, {
            direction: 'rtl',
            locale: 'ar',
            timeZone: 'UTC',
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
            firstDay: 6,
            height: 'auto',
            editable: true,
            eventStartEditable: true,
            eventDurationEditable: true,
            selectable: false,
            dayMaxEvents: 4,
            moreLinkText: function(n) { return '+' + n + ' {{ __("student.calendar.more_sessions") }}'; },
            nowIndicator: true,

            events: function(info, successCallback, failureCallback) {
                const startStr = info.startStr.split('T')[0];
                const endStr = info.endStr.split('T')[0];

                fetch(`${eventsRoute}?teacher_id=${teacherId}&start=${startStr}&end=${endStr}`, {
                    headers: { 'X-Requested-With': 'XMLHttpRequest', 'Accept': 'application/json' }
                })
                .then(r => r.json())
                .then(data => {
                    const events = (Array.isArray(data) ? data : []).map(event => ({
                        id: event.id,
                        title: event.title,
                        start: utcToAcademyLocal(event.start_time),
                        end: utcToAcademyLocal(event.end_time),
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

            eventClick: function(info) {
                info.jsEvent.preventDefault();
                const event = info.event;
                const props = event.extendedProps;
                const eventData = props.originalEvent || {
                    id: event.id,
                    source: props.source,
                };
                const modalEl = document.querySelector('[x-data="sessionDetailModal()"]');
                if (modalEl && modalEl.__x) {
                    modalEl.__x.$data.show(eventData);
                } else if (modalEl) {
                    const alpineData = Alpine.$data(modalEl);
                    if (alpineData) {
                        alpineData.show(eventData);
                    }
                }
            },

            eventDrop: function(info) {
                const event = info.event;
                const props = event.extendedProps;

                if (props.status !== 'scheduled' && props.status !== 'ready') {
                    info.revert();
                    if (window.toast) {
                        window.toast.warning(@js(__('teacher.calendar.cannot_reschedule_status')));
                    }
                    return;
                }

                const eventId = event.id || '';
                const idParts = eventId.split('_');
                const sessionId = parseInt(idParts[idParts.length - 1]);
                const source = props.source;

                if (!sessionId || !source) {
                    info.revert();
                    return;
                }

                // FC is in UTC mode — event.start UTC components = academy local time
                const d = event.start;
                const pad = n => String(n).padStart(2, '0');
                const newStartLocal = `${d.getUTCFullYear()}-${pad(d.getUTCMonth()+1)}-${pad(d.getUTCDate())}T${pad(d.getUTCHours())}:${pad(d.getUTCMinutes())}:00`;
                const newDateStr = d.toLocaleDateString('ar-SA', {
                    timeZone: 'UTC',
                    weekday: 'long', year: 'numeric', month: 'long', day: 'numeric'
                });
                const newTimeStr = d.toLocaleTimeString('ar-SA', {
                    timeZone: 'UTC',
                    hour: '2-digit', minute: '2-digit', hour12: true
                });

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
                                scheduled_at: newStartLocal,
                                teacher_id: teacherId,
                            })
                        })
                        .then(r => r.json().then(data => ({ ok: r.ok, data })))
                        .then(({ ok, data }) => {
                            if (ok) {
                                if (window.toast) window.toast.success(data.message || @js(__('teacher.calendar.reschedule_success')));
                                if (window.supervisorCalendar) window.supervisorCalendar.refetchEvents();
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

            eventResize: function(info) {
                info.revert();
            },

            loading: function(isLoading) {
                // Loading indicator if needed
            }
        });

        calendar.render();
        window.supervisorCalendar = calendar;

        const resizeObserver = new ResizeObserver(() => {
            calendar.updateSize();
        });
        resizeObserver.observe(calendarEl.parentElement);
    });
</script>
@endif
</x-slot:scripts>

</x-layouts.supervisor>
