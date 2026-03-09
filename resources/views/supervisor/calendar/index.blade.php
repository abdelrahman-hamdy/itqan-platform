<x-layouts.supervisor>

@php
    $subdomain = request()->route('subdomain') ?? auth()->user()->academy->subdomain ?? 'itqan-academy';
@endphp

<div>
    <x-ui.breadcrumb
        :items="[
            ['label' => __('supervisor.sidebar.dashboard'), 'route' => route('manage.dashboard', ['subdomain' => $subdomain])],
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
                    const url = new URL(window.location.href);
                    if (this.value) {
                        url.searchParams.set('teacher_id', this.value);
                    } else {
                        url.searchParams.delete('teacher_id');
                    }
                    window.location.href = url.toString();
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

        <!-- Calendar embed (iframe to teacher calendar with supervisor context) -->
        <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-4 md:p-6">
            <div class="text-center py-8">
                <div class="w-16 h-16 bg-indigo-100 rounded-full flex items-center justify-center mx-auto mb-4">
                    <i class="ri-calendar-schedule-line text-2xl text-indigo-600"></i>
                </div>
                <h3 class="text-lg font-bold text-gray-900 mb-2">{{ __('supervisor.calendar.page_title') }}</h3>
                <p class="text-sm text-gray-600 mb-4">
                    {{ __('supervisor.calendar.select_teacher_description') }}
                </p>
                <div id="supervisor-calendar-container" class="min-h-[500px]"
                     data-events-url="{{ route('manage.calendar.events', ['subdomain' => $subdomain]) }}"
                     data-teacher-id="{{ $selectedTeacherId }}">
                    <!-- FullCalendar will be initialized here via JS if available -->
                    <p class="text-sm text-gray-500 italic">
                        {{ __('supervisor.calendar.page_subtitle') }}
                    </p>
                </div>
            </div>
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

</x-layouts.supervisor>
