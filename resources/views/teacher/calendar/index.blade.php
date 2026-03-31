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

    <!-- Scheduling Panel -->
    <x-calendar.scheduling-panel
        :schedulableItemsRoute="route('teacher.calendar.schedulable-items', ['subdomain' => $subdomain])"
        :recommendationsRoute="$recommendationsRoute"
        :scheduleRoute="route('teacher.calendar.schedule', ['subdomain' => $subdomain])"
        :teacherType="$teacherType"
        :tabs="$tabs"
        calendarVarName="teacherCalendar"
    />

    <!-- FullCalendar -->
    <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-4 md:p-6 w-full overflow-x-auto">
        <div id="fullcalendar" class="w-full min-w-0"></div>
    </div>

    <!-- Session Detail + Homework Modals -->
    <x-calendar.session-detail-modal
        :sessionDetailRoute="$sessionDetailRoute"
        :updateSessionRoute="$updateSessionRoute"
        :quranHomeworkRoute="$quranHomeworkRoute"
        :academicHomeworkRoute="$academicHomeworkRoute"
        calendarVarName="teacherCalendar"
    />

<x-slot:head>
    <x-calendar.fullcalendar-styles />
</x-slot:head>

<x-slot:scripts>
{{-- FullCalendar JS --}}
<script src="https://cdn.jsdelivr.net/npm/fullcalendar@6.1.15/index.global.min.js"></script>
<script>
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
            now: utcToAcademyLocal(new Date().toISOString()),
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

            // Click event → show session detail modal
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

            // Drag & drop → reschedule session
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
