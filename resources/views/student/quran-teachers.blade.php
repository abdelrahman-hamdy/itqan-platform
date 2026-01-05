@php
  $pageTitle = ($academy->name ?? __('student.common.academy_default')) . ' - ' . __('student.search_results.quran_teachers');
  $pageDescription = __('student.circles.explore_description') . ' - ' . ($academy->name ?? __('student.common.academy_default'));
@endphp

@auth
  @if(auth()->user()->isAdmin() || auth()->user()->isSuperAdmin() || auth()->user()->isSupervisor())
    {{-- Show public layout for admin/superadmin/supervisor --}}
    <x-layouts.public-layout :title="$pageTitle" :academy="$academy" :description="$pageDescription">
      @include('student.partials.quran-teachers-content')
    </x-layouts.public-layout>
  @elseif(auth()->user()->isParent())
    {{-- Show parent layout for parents --}}
    <x-layouts.parent-layout :title="$pageTitle">
      @include('student.partials.quran-teachers-content')
    </x-layouts.parent-layout>
  @elseif(auth()->user()->isQuranTeacher() || auth()->user()->isAcademicTeacher())
    {{-- Show teacher layout for teachers --}}
    <x-layouts.teacher :title="$pageTitle" :description="$pageDescription">
      @include('student.partials.quran-teachers-content')
    </x-layouts.teacher>
  @else
    {{-- Show student layout for students --}}
    <x-layouts.student :title="$pageTitle" :description="$pageDescription">
      @include('student.partials.quran-teachers-content')
    </x-layouts.student>
  @endif
@else
<x-layouts.public-layout :title="$pageTitle" :academy="$academy" :description="$pageDescription">
  @include('student.partials.quran-teachers-content')
</x-layouts.public-layout>
@endauth
