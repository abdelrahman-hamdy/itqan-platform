<x-layouts.supervisor>

@php
    $subdomain = request()->route('subdomain') ?? auth()->user()->academy->subdomain ?? 'itqan-academy';
@endphp

<div class="max-w-3xl mx-auto">
    <x-ui.breadcrumb
        :items="[
            ['label' => __('supervisor.subscriptions.page_title'), 'url' => route('manage.subscriptions.index', ['subdomain' => $subdomain])],
            ['label' => __('supervisor.subscriptions.create_title')],
        ]"
        view-type="supervisor"
    />

    <div class="mb-6">
        <h1 class="text-xl sm:text-2xl font-bold text-gray-900">{{ __('supervisor.subscriptions.create_title') }}</h1>
        <p class="mt-1 text-sm text-gray-600">{{ __('supervisor.subscriptions.create_subtitle') }}</p>
    </div>

    <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-6">
        <form method="POST" action="{{ route('manage.subscriptions.store', ['subdomain' => $subdomain]) }}">
            @csrf

            {{-- Type --}}
            <div class="mb-5">
                <label class="block text-sm font-medium text-gray-700 mb-1">{{ __('supervisor.subscriptions.field_type') }}</label>
                <select name="type" id="subscription_type" required
                        class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm focus:ring-2 focus:ring-blue-500 focus:border-blue-500"
                        x-data x-on:change="$dispatch('type-changed', { type: $el.value })">
                    <option value="quran" @selected(old('type') === 'quran')>{{ __('supervisor.subscriptions.type_quran') }}</option>
                    <option value="academic" @selected(old('type') === 'academic')>{{ __('supervisor.subscriptions.type_academic') }}</option>
                </select>
                @error('type') <p class="text-red-500 text-xs mt-1">{{ $message }}</p> @enderror
            </div>

            {{-- Student --}}
            <div class="mb-5">
                <label class="block text-sm font-medium text-gray-700 mb-1">{{ __('supervisor.subscriptions.field_student') }}</label>
                <select name="student_id" required
                        class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm focus:ring-2 focus:ring-blue-500 focus:border-blue-500">
                    <option value="">{{ __('supervisor.subscriptions.select_student') }}</option>
                    @foreach($students as $student)
                        <option value="{{ $student->id }}" @selected(old('student_id') == $student->id)>{{ $student->name }} ({{ $student->email }})</option>
                    @endforeach
                </select>
                @error('student_id') <p class="text-red-500 text-xs mt-1">{{ $message }}</p> @enderror
            </div>

            {{-- Teacher (Quran) --}}
            <div class="mb-5" x-data="{ type: '{{ old('type', 'quran') }}' }"
                 @type-changed.window="type = $event.detail.type">
                <div x-show="type === 'quran'">
                    <label class="block text-sm font-medium text-gray-700 mb-1">{{ __('supervisor.subscriptions.field_teacher') }}</label>
                    <select name="teacher_id" x-bind:disabled="type !== 'quran'"
                            class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm focus:ring-2 focus:ring-blue-500 focus:border-blue-500">
                        <option value="">{{ __('supervisor.subscriptions.select_teacher') }}</option>
                        @foreach($quranTeachers as $teacher)
                            <option value="{{ $teacher->id }}" @selected(old('teacher_id') == $teacher->id)>{{ $teacher->name }}</option>
                        @endforeach
                    </select>
                </div>
                <div x-show="type === 'academic'">
                    <label class="block text-sm font-medium text-gray-700 mb-1">{{ __('supervisor.subscriptions.field_teacher') }}</label>
                    <select name="teacher_id" x-bind:disabled="type !== 'academic'"
                            class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm focus:ring-2 focus:ring-blue-500 focus:border-blue-500">
                        <option value="">{{ __('supervisor.subscriptions.select_teacher') }}</option>
                        @foreach($academicTeachers as $teacher)
                            <option value="{{ $teacher->academicTeacherProfile?->id }}" @selected(old('teacher_id') == $teacher->academicTeacherProfile?->id)>{{ $teacher->name }}</option>
                        @endforeach
                    </select>
                </div>
                @error('teacher_id') <p class="text-red-500 text-xs mt-1">{{ $message }}</p> @enderror
            </div>

            {{-- Total Sessions --}}
            <div class="mb-5">
                <label class="block text-sm font-medium text-gray-700 mb-1">{{ __('supervisor.subscriptions.field_total_sessions') }}</label>
                <input type="number" name="total_sessions" value="{{ old('total_sessions', 12) }}" min="1" required
                       class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm focus:ring-2 focus:ring-blue-500 focus:border-blue-500">
                @error('total_sessions') <p class="text-red-500 text-xs mt-1">{{ $message }}</p> @enderror
            </div>

            {{-- Dates --}}
            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4 mb-5">
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">{{ __('supervisor.subscriptions.field_start_date') }}</label>
                    <input type="date" name="starts_at" value="{{ old('starts_at', now()->format('Y-m-d')) }}" required
                           class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm focus:ring-2 focus:ring-blue-500 focus:border-blue-500">
                    @error('starts_at') <p class="text-red-500 text-xs mt-1">{{ $message }}</p> @enderror
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">{{ __('supervisor.subscriptions.field_end_date') }}</label>
                    <input type="date" name="ends_at" value="{{ old('ends_at', now()->addMonth()->format('Y-m-d')) }}" required
                           class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm focus:ring-2 focus:ring-blue-500 focus:border-blue-500">
                    @error('ends_at') <p class="text-red-500 text-xs mt-1">{{ $message }}</p> @enderror
                </div>
            </div>

            {{-- Actions --}}
            <div class="flex items-center gap-3 pt-4 border-t border-gray-200">
                <button type="submit"
                        class="px-6 py-2 bg-blue-600 text-white rounded-lg text-sm font-medium hover:bg-blue-700 transition-colors">
                    {{ __('supervisor.subscriptions.btn_create') }}
                </button>
                <a href="{{ route('manage.subscriptions.index', ['subdomain' => $subdomain]) }}"
                   class="px-6 py-2 bg-gray-100 text-gray-700 rounded-lg text-sm font-medium hover:bg-gray-200 transition-colors">
                    {{ __('supervisor.subscriptions.btn_cancel') }}
                </a>
            </div>
        </form>
    </div>
</div>

</x-layouts.supervisor>
