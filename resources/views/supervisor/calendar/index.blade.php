<x-layouts.supervisor>

@php
    $subdomain = request()->route('subdomain') ?? auth()->user()->academy->subdomain ?? 'itqan-academy';
    $eventsRoute = route('manage.calendar.events', ['subdomain' => $subdomain]);
@endphp

<div>
    <x-ui.breadcrumb
        :items="[
            ['label' => __('supervisor.calendar.page_title')],
        ]"
        view-type="supervisor"
    />

    <div class="mb-6 md:mb-8">
        <h1 class="text-xl sm:text-2xl md:text-3xl font-bold text-gray-900">{{ __('supervisor.calendar.page_title') }}</h1>
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
        <!-- Teacher Info Banner -->
        @php
            $teacherType = in_array($selectedTeacher->id, $teachers->where('type', 'quran')->pluck('id')->toArray() ?? []) ? 'quran' : 'academic';
        @endphp
        <x-supervisor.teacher-info-banner :teacher="$selectedTeacher" :type="$teacherType" />

        <!-- Calendar -->
        <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-4 md:p-6">
            <div id="supervisor-calendar" class="w-full min-w-0"></div>
        </div>
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
    <link href="https://cdn.jsdelivr.net/npm/fullcalendar@6.1.15/index.global.min.css" rel="stylesheet">
    <style>
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
        .fc-event-scheduled { background-color: #3b82f6 !important; color: #fff !important; }
        .fc-event-ready { background-color: #6366f1 !important; color: #fff !important; }
        .fc-event-ongoing { background-color: #f59e0b !important; color: #fff !important; }
        .fc-event-live { background-color: #f59e0b !important; color: #fff !important; }
        .fc-event-completed { background-color: #10b981 !important; color: #fff !important; }
        .fc-event-cancelled { background-color: #ef4444 !important; color: #fff !important; }
        .fc-event-absent { background-color: #f97316 !important; color: #fff !important; }
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
    @endif
</x-slot:head>

<x-slot:scripts>
@if($selectedTeacher)
<script src="https://cdn.jsdelivr.net/npm/fullcalendar@6.1.15/index.global.min.js"></script>
<script>
document.addEventListener('DOMContentLoaded', function() {
    const calendarEl = document.getElementById('supervisor-calendar');
    if (!calendarEl) return;

    const academyTimezone = @js(\App\Services\AcademyContextService::getTimezone());
    const eventsRoute = @js($eventsRoute);
    const teacherId = @js($selectedTeacherId);
    const sessionShowBase = @js(route('manage.sessions.show', ['subdomain' => $subdomain, 'sessionType' => '__TYPE__', 'sessionId' => '__ID__']));

    const statusColors = {
        'scheduled': '#3b82f6',
        'ready': '#6366f1',
        'ongoing': '#f59e0b',
        'live': '#f59e0b',
        'completed': '#10b981',
        'cancelled': '#ef4444',
        'absent': '#f97316'
    };

    const sourceLabels = {
        'quran_session': @js(__('student.calendar.quran_individual_session')),
        'circle_session': @js(__('student.calendar.quran_circle_session')),
        'course_session': @js(__('student.calendar.course_session')),
        'academic_session': @js(__('student.calendar.academic_session'))
    };

    const statusLabels = {
        'scheduled': @js(__('student.calendar.status_scheduled')),
        'ready': @js(__('student.calendar.status_scheduled')),
        'ongoing': @js(__('student.calendar.status_ongoing')),
        'live': @js(__('student.calendar.status_ongoing')),
        'completed': @js(__('student.calendar.status_completed')),
        'cancelled': @js(__('student.calendar.status_cancelled'))
    };

    function htmlEscape(str) {
        const div = document.createElement('div');
        div.appendChild(document.createTextNode(String(str ?? '')));
        return div.innerHTML;
    }

    function getSessionManageUrl(event) {
        const source = event.source || '';
        let type = 'quran';
        if (source === 'academic_session') type = 'academic';
        else if (source === 'course_session') type = 'interactive';

        const numericId = String(event.id || '').replace(/^[a-z_]+_/, '');
        return sessionShowBase.replace('__TYPE__', type).replace('__ID__', numericId);
    }

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
        firstDay: 6,
        height: 'auto',
        editable: false,
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
                        originalEvent: event
                    }
                }));
                successCallback(events);
            })
            .catch(() => failureCallback());
        },

        eventClick: function(info) {
            info.jsEvent.preventDefault();
            const props = info.event.extendedProps;
            const event = props.originalEvent || { id: info.event.id, source: props.source };
            const url = getSessionManageUrl(event);
            if (url) window.location.href = url;
        },

        eventDidMount: function(info) {
            const props = info.event.extendedProps;
            const source = sourceLabels[props.source] || '';
            const status = statusLabels[props.status] || '';
            const duration = props.duration_minutes ? props.duration_minutes + ' {{ __("supervisor.sessions.duration_minutes", ["count" => ""]) }}' : '';
            const lines = [source, status, duration].filter(Boolean);
            if (lines.length) {
                info.el.title = lines.join(' | ');
            }
        }
    });

    calendar.render();
});
</script>
@endif
</x-slot:scripts>

</x-layouts.supervisor>
