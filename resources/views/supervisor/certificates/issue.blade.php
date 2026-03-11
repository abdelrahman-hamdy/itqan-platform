<x-layouts.supervisor>

@php
    $subdomain = request()->route('subdomain') ?? auth()->user()->academy->subdomain ?? 'itqan-academy';
@endphp

<div>
    <x-ui.breadcrumb
        :items="[
            ['label' => __('supervisor.certificates.page_title'), 'route' => route('manage.certificates.index', ['subdomain' => $subdomain])],
            ['label' => __('supervisor.certificates.issue_certificate')],
        ]"
        view-type="supervisor"
    />

    <div class="mb-6 md:mb-8">
        <h1 class="text-xl sm:text-2xl md:text-3xl font-bold text-gray-900">{{ __('supervisor.certificates.issue_certificate') }}</h1>
        <p class="mt-1 md:mt-2 text-sm md:text-base text-gray-600">{{ __('supervisor.certificates.issue_subtitle') }}</p>
    </div>

    <div class="max-w-2xl">
        <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-4 md:p-6">
            <form method="POST" action="{{ route('manage.certificates.store', ['subdomain' => $subdomain]) }}">
                @csrf

                <div class="space-y-5">
                    <!-- Student -->
                    <div>
                        <label for="student_id" class="block text-sm font-medium text-gray-700 mb-1">{{ __('supervisor.certificates.student') }} <span class="text-red-500">*</span></label>
                        <select name="student_id" id="student_id" required
                                class="w-full rounded-lg border-gray-300 text-sm focus:border-blue-500 focus:ring-blue-500 @error('student_id') border-red-500 @enderror">
                            <option value="">{{ __('supervisor.certificates.select_student') }}</option>
                            @foreach($students as $student)
                                <option value="{{ $student['id'] }}" {{ old('student_id') == $student['id'] ? 'selected' : '' }}>{{ $student['name'] }}</option>
                            @endforeach
                        </select>
                        @error('student_id')
                            <p class="mt-1 text-xs text-red-600">{{ $message }}</p>
                        @enderror
                    </div>

                    <!-- Teacher -->
                    <div>
                        <label for="teacher_id" class="block text-sm font-medium text-gray-700 mb-1">{{ __('supervisor.certificates.teacher') }} <span class="text-red-500">*</span></label>
                        <select name="teacher_id" id="teacher_id" required
                                class="w-full rounded-lg border-gray-300 text-sm focus:border-blue-500 focus:ring-blue-500 @error('teacher_id') border-red-500 @enderror">
                            <option value="">{{ __('supervisor.certificates.select_teacher') }}</option>
                            @foreach($teachers as $teacher)
                                <option value="{{ $teacher['id'] }}" {{ old('teacher_id') == $teacher['id'] ? 'selected' : '' }}>{{ $teacher['name'] }}</option>
                            @endforeach
                        </select>
                        @error('teacher_id')
                            <p class="mt-1 text-xs text-red-600">{{ $message }}</p>
                        @enderror
                    </div>

                    <!-- Certificate Type -->
                    <div>
                        <label for="certificate_type" class="block text-sm font-medium text-gray-700 mb-1">{{ __('supervisor.certificates.type') }} <span class="text-red-500">*</span></label>
                        <select name="certificate_type" id="certificate_type" required
                                class="w-full rounded-lg border-gray-300 text-sm focus:border-blue-500 focus:ring-blue-500 @error('certificate_type') border-red-500 @enderror">
                            <option value="">{{ __('supervisor.certificates.select_type') }}</option>
                            @foreach($certificateTypes as $value => $label)
                                <option value="{{ $value }}" {{ old('certificate_type') == $value ? 'selected' : '' }}>{{ $label }}</option>
                            @endforeach
                        </select>
                        @error('certificate_type')
                            <p class="mt-1 text-xs text-red-600">{{ $message }}</p>
                        @enderror
                    </div>

                    <!-- Notes / Certificate Text -->
                    <div>
                        <label for="certificate_text" class="block text-sm font-medium text-gray-700 mb-1">{{ __('supervisor.certificates.certificate_text') }}</label>
                        <textarea name="certificate_text" id="certificate_text" rows="4"
                                  class="w-full rounded-lg border-gray-300 text-sm focus:border-blue-500 focus:ring-blue-500 @error('certificate_text') border-red-500 @enderror"
                                  placeholder="{{ __('supervisor.certificates.certificate_text_placeholder') }}">{{ old('certificate_text') }}</textarea>
                        @error('certificate_text')
                            <p class="mt-1 text-xs text-red-600">{{ $message }}</p>
                        @enderror
                    </div>

                    <!-- Actions -->
                    <div class="flex items-center gap-3 pt-2">
                        <button type="submit" class="px-6 py-2.5 bg-blue-600 text-white text-sm font-medium rounded-lg hover:bg-blue-700 transition-colors">
                            <i class="ri-award-line ml-1"></i> {{ __('supervisor.certificates.issue_certificate') }}
                        </button>
                        <a href="{{ route('manage.certificates.index', ['subdomain' => $subdomain]) }}"
                           class="px-6 py-2.5 bg-gray-100 text-gray-700 text-sm font-medium rounded-lg hover:bg-gray-200 transition-colors">
                            {{ __('supervisor.common.cancel') }}
                        </a>
                    </div>
                </div>
            </form>
        </div>
    </div>
</div>

</x-layouts.supervisor>
