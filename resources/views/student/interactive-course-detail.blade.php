@php
    $pageTitle = $course->title . ' - ' . ($academy->name ?? __('student.common.platform_default'));
@endphp

@auth
  @if(auth()->user()->isAdmin() || auth()->user()->isSuperAdmin())
    {{-- Show public layout for admin/superadmin --}}
    <x-layouts.public-layout
        :title="$pageTitle"
        :description="$course->description"
        :academy="$academy">
        @include('student.partials.interactive-course-detail-content')
    </x-layouts.public-layout>
  @elseif(auth()->user()->isParent())
    {{-- Show parent layout for parents --}}
    <x-layouts.parent-layout :title="$pageTitle">
        @include('student.partials.interactive-course-detail-content')
    </x-layouts.parent-layout>
  @elseif(auth()->user()->isQuranTeacher() || auth()->user()->isAcademicTeacher())
    {{-- Show teacher layout for teachers --}}
    <x-layouts.teacher
        :title="$pageTitle"
        :description="$course->description">
        @include('student.partials.interactive-course-detail-content')
    </x-layouts.teacher>
  @else
    {{-- Show student layout for students --}}
    <x-layouts.student
        :title="$pageTitle"
        :description="$course->description">
        @include('student.partials.interactive-course-detail-content')
    </x-layouts.student>
  @endif
@else
<x-layouts.public-layout
    :title="$pageTitle"
    :description="$course->description"
    :academy="$academy">
    @include('student.partials.interactive-course-detail-content')
</x-layouts.public-layout>
@endauth
