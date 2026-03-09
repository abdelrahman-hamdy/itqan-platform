@props([
    'teachers',
    'selectedTeacherId' => null,
    'filterParam' => 'teacher_id',
])

<div x-data="{ selectedTeacher: '{{ $selectedTeacherId ?? '' }}' }" class="mb-4 md:mb-6">
    <div class="flex items-center gap-3 flex-wrap">
        <label class="text-sm font-medium text-gray-700 flex items-center gap-1.5 flex-shrink-0">
            <i class="ri-filter-3-line text-gray-400"></i>
            {{ __('supervisor.common.filter_by_teacher') }}
        </label>
        <select
            x-model="selectedTeacher"
            x-on:change="
                const url = new URL(window.location.href);
                if (selectedTeacher) {
                    url.searchParams.set('{{ $filterParam }}', selectedTeacher);
                } else {
                    url.searchParams.delete('{{ $filterParam }}');
                }
                window.location.href = url.toString();
            "
            class="rounded-lg border-gray-300 text-sm focus:ring-indigo-500 focus:border-indigo-500 min-w-[200px]"
        >
            <option value="">{{ __('supervisor.common.all_teachers') }}</option>
            @foreach($teachers as $teacher)
                <option value="{{ $teacher['id'] }}" {{ $selectedTeacherId == $teacher['id'] ? 'selected' : '' }}>
                    {{ $teacher['name'] }}
                    @if(isset($teacher['type_label']))
                        ({{ $teacher['type_label'] }})
                    @endif
                </option>
            @endforeach
        </select>

        @if($selectedTeacherId)
            <span class="inline-flex items-center gap-1 text-xs px-2.5 py-1 rounded-full bg-indigo-100 text-indigo-700">
                <i class="ri-user-line"></i>
                {{ __('supervisor.common.showing_for_teacher', ['name' => collect($teachers)->firstWhere('id', $selectedTeacherId)['name'] ?? '']) }}
            </span>
        @endif
    </div>
</div>
