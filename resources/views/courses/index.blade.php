@php
  $academy = auth()->user()->academy ?? null;
@endphp

<x-layouts.student title="{{ $academy->name ?? __('student.common.academy_default') }} - {{ __('student.recorded_courses.title') }}">
  <x-slot name="description">{{ __('student.recorded_courses.description') }} - {{ $academy->name ?? __('student.common.academy_default') }}</x-slot>

  <!-- Header Section -->
  <div class="mb-8">
    <div class="flex items-center justify-between flex-wrap gap-4">
      <div>
        <h1 class="text-3xl font-bold text-gray-900 mb-2">
          {{ __('student.recorded_courses.title') }}
        </h1>
        <p class="text-gray-600">
          {{ __('student.recorded_courses.description') }}
        </p>
      </div>
      <div class="bg-white rounded-lg px-6 py-3 border border-gray-200 shadow-sm">
        <span class="text-sm text-gray-600">{{ __('student.recorded_courses.total_courses') }}: </span>
        <span class="font-bold text-2xl text-cyan-500">{{ $courses->total() }}</span>
      </div>
    </div>
  </div>

  <!-- Filters Section -->
  <x-filters.course-filters
    :route="route('courses.index', ['subdomain' => $academy->subdomain ?? 'itqan-academy'])"
    :subjects="$subjects"
    :gradeLevels="$gradeLevels"
    :levels="$levels"
    :showSearch="true"
    :showSubject="true"
    :showGradeLevel="true"
    :showDifficulty="true"
    color="cyan"
  />

  <!-- Results Summary -->
  <div class="mb-6 flex items-center justify-between">
    <p class="text-gray-600">
      <span class="font-semibold text-gray-900">{{ $courses->total() }}</span>
      {{ __('student.recorded_courses.courses_available') }}
    </p>
    @if($courses->total() > 0)
    <p class="text-sm text-gray-500">
      {{ __('student.recorded_courses.showing_results') }} {{ $courses->firstItem() }} - {{ $courses->lastItem() }} {{ __('student.recorded_courses.of_total') }} {{ $courses->total() }}
    </p>
    @endif
  </div>

  <!-- Courses Grid -->
  @if($courses->count() > 0)
  <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6 mb-8">
    @foreach($courses as $course)
    <x-course-card :course="$course" :academy="$academy" />
    @endforeach
  </div>

  <!-- Pagination -->
  <div class="flex justify-center mt-8">
    {{ $courses->appends(request()->query())->links() }}
  </div>
  @else
  <!-- Empty State -->
  <div class="text-center py-12">
    <div class="w-24 h-24 bg-cyan-50 rounded-full flex items-center justify-center mx-auto mb-4">
      <i class="ri-play-circle-line text-cyan-400 text-4xl"></i>
    </div>
    <h3 class="text-lg font-semibold text-gray-900 mb-2">{{ __('student.recorded_courses.no_courses_title') }}</h3>
    <p class="text-gray-600 mb-6">{{ __('student.recorded_courses.no_courses_description') }}</p>
    <a href="{{ route('courses.index', ['subdomain' => $academy->subdomain ?? 'itqan-academy']) }}"
       class="inline-flex items-center px-4 py-2 bg-cyan-500 text-white rounded-lg hover:bg-cyan-600 transition-colors">
      <i class="ri-refresh-line ms-2"></i>
      {{ __('student.recorded_courses.show_all_courses') }}
    </a>
  </div>
  @endif

</x-layouts.student>
