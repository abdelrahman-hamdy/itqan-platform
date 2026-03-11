<x-layouts.supervisor>

@php
    $subdomain = request()->route('subdomain') ?? auth()->user()->academy->subdomain ?? 'itqan-academy';
@endphp

<div>
    <x-ui.breadcrumb
        :items="[['label' => __('supervisor.recorded_courses.page_title')]]"
        view-type="supervisor"
    />

    <div class="mb-6 md:mb-8">
        <h1 class="text-xl sm:text-2xl md:text-3xl font-bold text-gray-900">{{ __('supervisor.recorded_courses.page_title') }}</h1>
        <p class="mt-1 md:mt-2 text-sm md:text-base text-gray-600">{{ __('supervisor.recorded_courses.page_subtitle') }}</p>
    </div>

    <!-- Stats Cards -->
    <div class="grid grid-cols-2 md:grid-cols-4 gap-3 md:gap-4 mb-6">
        <div class="bg-white rounded-xl shadow-sm border border-gray-100 p-4 md:p-6">
            <div class="flex items-center gap-3 mb-3">
                <div class="p-2 bg-blue-50 rounded-lg">
                    <i class="ri-video-line text-xl text-blue-600"></i>
                </div>
                <span class="text-sm text-gray-500">{{ __('supervisor.recorded_courses.total_courses') }}</span>
            </div>
            <div class="text-3xl font-bold text-gray-900">{{ $totalCourses }}</div>
        </div>

        <div class="bg-white rounded-xl shadow-sm border border-gray-100 p-4 md:p-6">
            <div class="flex items-center gap-3 mb-3">
                <div class="p-2 bg-green-50 rounded-lg">
                    <i class="ri-checkbox-circle-line text-xl text-green-600"></i>
                </div>
                <span class="text-sm text-gray-500">{{ __('supervisor.recorded_courses.published') }}</span>
            </div>
            <div class="text-3xl font-bold text-gray-900">{{ $publishedCount }}</div>
        </div>

        <div class="bg-white rounded-xl shadow-sm border border-gray-100 p-4 md:p-6">
            <div class="flex items-center gap-3 mb-3">
                <div class="p-2 bg-yellow-50 rounded-lg">
                    <i class="ri-draft-line text-xl text-yellow-600"></i>
                </div>
                <span class="text-sm text-gray-500">{{ __('supervisor.recorded_courses.draft') }}</span>
            </div>
            <div class="text-3xl font-bold text-gray-900">{{ $draftCount }}</div>
        </div>

        <div class="bg-white rounded-xl shadow-sm border border-gray-100 p-4 md:p-6">
            <div class="flex items-center gap-3 mb-3">
                <div class="p-2 bg-purple-50 rounded-lg">
                    <i class="ri-user-star-line text-xl text-purple-600"></i>
                </div>
                <span class="text-sm text-gray-500">{{ __('supervisor.recorded_courses.total_enrollments') }}</span>
            </div>
            <div class="text-3xl font-bold text-gray-900">{{ $totalEnrollments }}</div>
        </div>
    </div>

    <!-- Filters -->
    <div class="bg-white rounded-xl shadow-sm border border-gray-100 p-4 md:p-6 mb-6">
        <form method="GET" action="{{ route('manage.recorded-courses.index', ['subdomain' => $subdomain]) }}" class="flex flex-col sm:flex-row gap-3">
            <div class="flex-1">
                <input type="text" name="search" value="{{ request('search') }}"
                       placeholder="{{ __('supervisor.recorded_courses.search_placeholder') }}"
                       class="w-full rounded-lg border-gray-300 text-sm focus:border-blue-500 focus:ring-blue-500">
            </div>
            <div>
                <select name="status" class="w-full sm:w-auto rounded-lg border-gray-300 text-sm focus:border-blue-500 focus:ring-blue-500">
                    <option value="">{{ __('supervisor.recorded_courses.all_statuses') }}</option>
                    <option value="published" {{ request('status') === 'published' ? 'selected' : '' }}>{{ __('supervisor.recorded_courses.published') }}</option>
                    <option value="draft" {{ request('status') === 'draft' ? 'selected' : '' }}>{{ __('supervisor.recorded_courses.draft') }}</option>
                </select>
            </div>
            @if(count($instructors) > 0)
            <div>
                <select name="instructor_id" class="w-full sm:w-auto rounded-lg border-gray-300 text-sm focus:border-blue-500 focus:ring-blue-500">
                    <option value="">{{ __('supervisor.recorded_courses.all_instructors') }}</option>
                    @foreach($instructors as $instructor)
                        <option value="{{ $instructor['id'] }}" {{ request('instructor_id') == $instructor['id'] ? 'selected' : '' }}>{{ $instructor['name'] }}</option>
                    @endforeach
                </select>
            </div>
            @endif
            <div class="flex gap-2">
                <button type="submit" class="px-4 py-2 bg-blue-600 text-white text-sm rounded-lg hover:bg-blue-700 transition-colors">
                    <i class="ri-search-line ml-1"></i> {{ __('supervisor.common.search') }}
                </button>
                @if(request()->hasAny(['search', 'status', 'instructor_id']))
                    <a href="{{ route('manage.recorded-courses.index', ['subdomain' => $subdomain]) }}" class="px-4 py-2 bg-gray-100 text-gray-700 text-sm rounded-lg hover:bg-gray-200 transition-colors">
                        {{ __('supervisor.common.clear_filters') }}
                    </a>
                @endif
            </div>
        </form>
    </div>

    <!-- Courses Table -->
    <div class="bg-white rounded-xl shadow-sm border border-gray-100 overflow-hidden">
        <div class="overflow-x-auto">
            <table class="w-full">
                <thead class="bg-gray-50">
                    <tr>
                        <th class="px-4 md:px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase">{{ __('supervisor.recorded_courses.course_title') }}</th>
                        <th class="px-4 md:px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase hidden md:table-cell">{{ __('supervisor.recorded_courses.instructor') }}</th>
                        <th class="px-4 md:px-6 py-3 text-center text-xs font-medium text-gray-500 uppercase hidden lg:table-cell">{{ __('supervisor.recorded_courses.sections_count') }}</th>
                        <th class="px-4 md:px-6 py-3 text-center text-xs font-medium text-gray-500 uppercase hidden lg:table-cell">{{ __('supervisor.recorded_courses.lessons_count') }}</th>
                        <th class="px-4 md:px-6 py-3 text-center text-xs font-medium text-gray-500 uppercase">{{ __('supervisor.recorded_courses.enrollments_count') }}</th>
                        <th class="px-4 md:px-6 py-3 text-center text-xs font-medium text-gray-500 uppercase">{{ __('supervisor.recorded_courses.status') }}</th>
                        <th class="px-4 md:px-6 py-3 text-center text-xs font-medium text-gray-500 uppercase hidden md:table-cell">{{ __('supervisor.recorded_courses.price') }}</th>
                        <th class="px-4 md:px-6 py-3 text-center text-xs font-medium text-gray-500 uppercase">{{ __('supervisor.common.actions') }}</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100">
                    @forelse($courses as $course)
                        <tr class="hover:bg-gray-50 transition-colors">
                            <td class="px-4 md:px-6 py-4">
                                <div class="flex items-center gap-3">
                                    <div class="w-10 h-10 bg-blue-100 rounded-lg flex items-center justify-center flex-shrink-0">
                                        <i class="ri-video-line text-blue-600"></i>
                                    </div>
                                    <div class="min-w-0">
                                        <p class="text-sm font-medium text-gray-900 truncate">{{ $course->title }}</p>
                                        @if($course->difficulty_level)
                                            <p class="text-xs text-gray-500">{{ $course->difficulty_level }}</p>
                                        @endif
                                    </div>
                                </div>
                            </td>
                            <td class="px-4 md:px-6 py-4 hidden md:table-cell">
                                <span class="text-sm text-gray-600">{{ '-' }}</span>
                            </td>
                            <td class="px-4 md:px-6 py-4 text-center hidden lg:table-cell">
                                <span class="text-sm text-gray-600">{{ $course->sections_count }}</span>
                            </td>
                            <td class="px-4 md:px-6 py-4 text-center hidden lg:table-cell">
                                <span class="text-sm text-gray-600">{{ $course->lessons_count }}</span>
                            </td>
                            <td class="px-4 md:px-6 py-4 text-center">
                                <span class="text-sm text-gray-600">{{ $course->enrollments_count }}</span>
                            </td>
                            <td class="px-4 md:px-6 py-4 text-center">
                                @if($course->is_published)
                                    <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-green-100 text-green-800">{{ __('supervisor.recorded_courses.published') }}</span>
                                @else
                                    <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-yellow-100 text-yellow-800">{{ __('supervisor.recorded_courses.draft') }}</span>
                                @endif
                            </td>
                            <td class="px-4 md:px-6 py-4 text-center hidden md:table-cell">
                                <span class="text-sm text-gray-600">{{ $course->formatted_price }}</span>
                            </td>
                            <td class="px-4 md:px-6 py-4 text-center">
                                <div class="flex items-center justify-center gap-2">
                                    <a href="{{ route('manage.recorded-courses.show', ['subdomain' => $subdomain, 'course' => $course->id]) }}"
                                       class="inline-flex items-center gap-1 px-3 py-1.5 text-xs font-medium rounded-lg bg-blue-600 hover:bg-blue-700 text-white transition-colors">
                                        <i class="ri-eye-line"></i> {{ __('supervisor.common.view') }}
                                    </a>
                                    <form method="POST" action="{{ route('manage.recorded-courses.toggle-publish', ['subdomain' => $subdomain, 'course' => $course->id]) }}" class="inline">
                                        @csrf
                                        <button type="submit" class="inline-flex items-center gap-1 px-3 py-1.5 text-xs font-medium rounded-lg {{ $course->is_published ? 'bg-yellow-100 hover:bg-yellow-200 text-yellow-800' : 'bg-green-100 hover:bg-green-200 text-green-800' }} transition-colors">
                                            <i class="{{ $course->is_published ? 'ri-eye-off-line' : 'ri-eye-line' }}"></i>
                                            {{ $course->is_published ? __('supervisor.recorded_courses.unpublish') : __('supervisor.recorded_courses.publish') }}
                                        </button>
                                    </form>
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="8" class="px-6 py-12 text-center">
                                <div class="flex flex-col items-center">
                                    <div class="w-14 h-14 bg-gray-100 rounded-full flex items-center justify-center mb-3">
                                        <i class="ri-video-line text-2xl text-gray-400"></i>
                                    </div>
                                    <p class="text-sm text-gray-500">{{ __('supervisor.common.no_data') }}</p>
                                </div>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        @if($courses->hasPages())
            <div class="p-4 border-t border-gray-100">
                {{ $courses->links() }}
            </div>
        @endif
    </div>
</div>

</x-layouts.supervisor>
