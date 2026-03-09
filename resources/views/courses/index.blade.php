@php
  $academy = auth()->user()->academy ?? null;
  $pageTitle = ($academy->name ?? __('student.common.academy_default')) . ' - ' . __('student.recorded_courses.title');
  $pageDescription = __('student.recorded_courses.description') . ' - ' . ($academy->name ?? __('student.common.academy_default'));
@endphp

@auth
  @if(auth()->user()->isAdmin() || auth()->user()->isSuperAdmin() || auth()->user()->isSupervisor())
    {{-- Show supervisor layout for admin/superadmin/supervisor (with sidebar) --}}
    <x-layouts.supervisor :title="$pageTitle" :description="$pageDescription">
      @include('student.partials.recorded-courses-content')
    </x-layouts.supervisor>
  @elseif(auth()->user()->isParent())
    {{-- Show parent layout for parents --}}
    <x-layouts.parent-layout :title="$pageTitle">
      @include('student.partials.recorded-courses-content')
    </x-layouts.parent-layout>
  @elseif(auth()->user()->isQuranTeacher() || auth()->user()->isAcademicTeacher())
    {{-- Show teacher layout for teachers --}}
    <x-layouts.teacher :title="$pageTitle" :description="$pageDescription">
      @include('student.partials.recorded-courses-content')
    </x-layouts.teacher>
  @else
    {{-- Show student layout for students --}}
    <x-layouts.student :title="$pageTitle" :description="$pageDescription">
      @include('student.partials.recorded-courses-content')
    </x-layouts.student>
  @endif
@else
<x-layouts.public-layout :title="$pageTitle" :academy="$academy" :description="$pageDescription">
  @include('student.partials.recorded-courses-content')
</x-layouts.public-layout>
@endauth
