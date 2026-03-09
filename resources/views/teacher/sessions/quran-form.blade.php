<x-layouts.teacher
    :title="($isEdit ? __('teacher.session_form.edit_title') : __('teacher.session_form.create_title')) . ' - ' . config('app.name')">

@php
    $subdomain = request()->route('subdomain') ?? auth()->user()->academy->subdomain ?? 'itqan-academy';
@endphp

<div class="max-w-4xl mx-auto">
    {{-- Breadcrumbs --}}
    <x-ui.breadcrumb
        :items="[
            ['label' => __('teacher.session_form.sessions_breadcrumb'), 'route' => route('teacher.group-circles.index', ['subdomain' => $subdomain])],
            ['label' => $isEdit ? __('teacher.session_form.edit_title') : __('teacher.session_form.create_title')],
        ]"
        view-type="teacher"
    />

    {{-- Flash Messages --}}
    @if(session('error'))
        <div class="bg-red-50 border border-red-200 rounded-lg p-3 md:p-4 mb-4 md:mb-6">
            <div class="flex items-start gap-2">
                <i class="ri-error-warning-line text-red-600 text-lg flex-shrink-0"></i>
                <p class="font-medium text-red-900 text-sm">{{ session('error') }}</p>
            </div>
        </div>
    @endif

    {{-- Form --}}
    <form method="POST"
          action="{{ $isEdit
              ? route('teacher.sessions.update', ['subdomain' => $subdomain, 'sessionId' => $session->id])
              : route('teacher.sessions.store', ['subdomain' => $subdomain]) }}"
          x-data="quranSessionForm()">
        @csrf
        @if($isEdit)
            @method('PUT')
        @endif

        {{-- Circle Selection Card --}}
        @if(!$isEdit)
        <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-4 md:p-6 mb-4 md:mb-6">
            <h2 class="text-base md:text-lg font-bold text-gray-900 mb-4 flex items-center gap-2">
                <i class="ri-group-line text-blue-600"></i>
                {{ __('teacher.session_form.circle_selection') }}
            </h2>

            <div class="space-y-4">
                {{-- Circle Type Toggle --}}
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">{{ __('teacher.session_form.circle_type') }} <span class="text-red-500">*</span></label>
                    <div class="flex gap-4">
                        <label class="flex items-center gap-2 cursor-pointer">
                            <input type="radio" name="circle_type" value="individual"
                                   x-model="circleType"
                                   {{ old('circle_type', $selectedCircleType) === 'individual' ? 'checked' : '' }}
                                   class="text-blue-600 focus:ring-blue-500">
                            <span class="text-sm text-gray-700">{{ __('teacher.session_form.individual_circle') }}</span>
                        </label>
                        <label class="flex items-center gap-2 cursor-pointer">
                            <input type="radio" name="circle_type" value="group"
                                   x-model="circleType"
                                   {{ old('circle_type', $selectedCircleType) === 'group' ? 'checked' : '' }}
                                   class="text-blue-600 focus:ring-blue-500">
                            <span class="text-sm text-gray-700">{{ __('teacher.session_form.group_circle') }}</span>
                        </label>
                    </div>
                    @error('circle_type')
                        <p class="text-red-500 text-xs mt-1">{{ $message }}</p>
                    @enderror
                </div>

                {{-- Individual Circle Selector --}}
                <div x-show="circleType === 'individual'" x-cloak>
                    <label for="individual_circle_id" class="block text-sm font-medium text-gray-700 mb-1">
                        {{ __('teacher.session_form.select_individual_circle') }} <span class="text-red-500">*</span>
                    </label>
                    <select name="individual_circle_id" id="individual_circle_id"
                            class="w-full rounded-lg border-gray-300 focus:border-blue-500 focus:ring-blue-500 text-sm">
                        <option value="">{{ __('teacher.session_form.choose_circle') }}</option>
                        @foreach($individualCircles as $circle)
                            <option value="{{ $circle->id }}"
                                {{ old('individual_circle_id', $selectedCircleType === 'individual' ? $selectedCircleId : '') == $circle->id ? 'selected' : '' }}>
                                {{ $circle->student->name ?? __('teacher.session_form.unknown_student') }}
                                @if($circle->specialization)
                                    - {{ \App\Models\QuranIndividualCircle::SPECIALIZATIONS[$circle->specialization] ?? $circle->specialization }}
                                @endif
                            </option>
                        @endforeach
                    </select>
                    @error('individual_circle_id')
                        <p class="text-red-500 text-xs mt-1">{{ $message }}</p>
                    @enderror
                </div>

                {{-- Group Circle Selector --}}
                <div x-show="circleType === 'group'" x-cloak>
                    <label for="circle_id" class="block text-sm font-medium text-gray-700 mb-1">
                        {{ __('teacher.session_form.select_group_circle') }} <span class="text-red-500">*</span>
                    </label>
                    <select name="circle_id" id="circle_id"
                            class="w-full rounded-lg border-gray-300 focus:border-blue-500 focus:ring-blue-500 text-sm">
                        <option value="">{{ __('teacher.session_form.choose_circle') }}</option>
                        @foreach($groupCircles as $circle)
                            <option value="{{ $circle->id }}"
                                {{ old('circle_id', $selectedCircleType === 'group' ? $selectedCircleId : '') == $circle->id ? 'selected' : '' }}>
                                {{ $circle->name }}
                                ({{ $circle->enrolled_students }}/{{ $circle->max_students }} {{ __('teacher.session_form.students') }})
                            </option>
                        @endforeach
                    </select>
                    @error('circle_id')
                        <p class="text-red-500 text-xs mt-1">{{ $message }}</p>
                    @enderror
                </div>
            </div>
        </div>
        @endif

        {{-- Session Details Card --}}
        <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-4 md:p-6 mb-4 md:mb-6">
            <h2 class="text-base md:text-lg font-bold text-gray-900 mb-4 flex items-center gap-2">
                <i class="ri-calendar-check-line text-green-600"></i>
                {{ __('teacher.session_form.session_details') }}
            </h2>

            <div class="space-y-4">
                {{-- Scheduled At --}}
                <div>
                    <label for="scheduled_at" class="block text-sm font-medium text-gray-700 mb-1">
                        {{ __('teacher.session_form.scheduled_at') }} <span class="text-red-500">*</span>
                    </label>
                    <input type="datetime-local" name="scheduled_at" id="scheduled_at"
                           value="{{ old('scheduled_at', $isEdit && $session->scheduled_at ? \App\Services\AcademyContextService::toAcademyTimezone($session->scheduled_at)->format('Y-m-d\TH:i') : '') }}"
                           required
                           class="w-full rounded-lg border-gray-300 focus:border-blue-500 focus:ring-blue-500 text-sm">
                    <p class="text-xs text-gray-500 mt-1">{{ __('teacher.session_form.timezone_note', ['timezone' => \App\Services\AcademyContextService::getTimezone()]) }}</p>
                    @error('scheduled_at')
                        <p class="text-red-500 text-xs mt-1">{{ $message }}</p>
                    @enderror
                </div>

                {{-- Duration --}}
                <div>
                    <label for="duration_minutes" class="block text-sm font-medium text-gray-700 mb-1">
                        {{ __('teacher.session_form.duration_minutes') }}
                    </label>
                    <select name="duration_minutes" id="duration_minutes"
                            class="w-full rounded-lg border-gray-300 focus:border-blue-500 focus:ring-blue-500 text-sm">
                        @foreach([30, 45, 60, 90, 120] as $mins)
                            <option value="{{ $mins }}"
                                {{ old('duration_minutes', $isEdit ? $session->duration_minutes : 45) == $mins ? 'selected' : '' }}>
                                {{ $mins }} {{ __('teacher.session_form.minutes') }}
                            </option>
                        @endforeach
                    </select>
                    @error('duration_minutes')
                        <p class="text-red-500 text-xs mt-1">{{ $message }}</p>
                    @enderror
                </div>

                {{-- Title --}}
                <div>
                    <label for="title" class="block text-sm font-medium text-gray-700 mb-1">
                        {{ __('teacher.session_form.title') }}
                    </label>
                    <input type="text" name="title" id="title"
                           value="{{ old('title', $session->title ?? '') }}"
                           maxlength="255"
                           class="w-full rounded-lg border-gray-300 focus:border-blue-500 focus:ring-blue-500 text-sm"
                           placeholder="{{ __('teacher.session_form.title_placeholder') }}">
                    @error('title')
                        <p class="text-red-500 text-xs mt-1">{{ $message }}</p>
                    @enderror
                </div>

                {{-- Description --}}
                <div>
                    <label for="description" class="block text-sm font-medium text-gray-700 mb-1">
                        {{ __('teacher.session_form.description') }}
                    </label>
                    <textarea name="description" id="description" rows="3"
                              class="w-full rounded-lg border-gray-300 focus:border-blue-500 focus:ring-blue-500 text-sm"
                              placeholder="{{ __('teacher.session_form.description_placeholder') }}">{{ old('description', $session->description ?? '') }}</textarea>
                    @error('description')
                        <p class="text-red-500 text-xs mt-1">{{ $message }}</p>
                    @enderror
                </div>

                {{-- Lesson Content --}}
                <div>
                    <label for="lesson_content" class="block text-sm font-medium text-gray-700 mb-1">
                        {{ __('teacher.session_form.lesson_content') }}
                    </label>
                    <textarea name="lesson_content" id="lesson_content" rows="4"
                              class="w-full rounded-lg border-gray-300 focus:border-blue-500 focus:ring-blue-500 text-sm"
                              placeholder="{{ __('teacher.session_form.lesson_content_placeholder') }}">{{ old('lesson_content', $session->lesson_content ?? '') }}</textarea>
                    @error('lesson_content')
                        <p class="text-red-500 text-xs mt-1">{{ $message }}</p>
                    @enderror
                </div>
            </div>
        </div>

        {{-- Actions --}}
        <div class="flex justify-end gap-3">
            <a href="{{ url()->previous() }}"
               class="px-6 py-2.5 text-sm font-medium text-gray-700 bg-white border border-gray-300 rounded-lg hover:bg-gray-50 transition-colors">
                {{ __('common.actions.cancel') }}
            </a>
            <button type="submit"
                    class="px-6 py-2.5 text-sm font-medium text-white bg-blue-600 rounded-lg hover:bg-blue-700 transition-colors">
                <i class="ri-save-line ms-1"></i>
                {{ $isEdit ? __('common.actions.save_changes') : __('teacher.session_form.create_session') }}
            </button>
        </div>
    </form>
</div>

@push('scripts')
<script>
    function quranSessionForm() {
        return {
            circleType: '{{ old('circle_type', $selectedCircleType ?? 'individual') }}',
        };
    }
</script>
@endpush

</x-layouts.teacher>
