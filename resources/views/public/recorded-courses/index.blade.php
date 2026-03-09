@php
  $pageTitle = __('courses.index.page_title_suffix') . ($academy->name ?? __('components.layouts.academy_default'));
  $pageDescription = __('courses.index.page_description_suffix') . ($academy->name ?? __('components.layouts.academy_default'));
@endphp

@auth
  @if(auth()->user()->isAdmin() || auth()->user()->isSuperAdmin() || auth()->user()->isSupervisor())
    {{-- Show supervisor layout for admin/superadmin/supervisor (with sidebar) --}}
    <x-layouts.supervisor :title="$pageTitle" :description="$pageDescription">
      @include('public.recorded-courses.partials.index-content')
    </x-layouts.supervisor>
  @elseif(auth()->user()->isParent())
    {{-- Show parent layout for parents --}}
    <x-layouts.parent-layout :title="$pageTitle">
      @include('public.recorded-courses.partials.index-content')
    </x-layouts.parent-layout>
  @elseif(auth()->user()->isQuranTeacher() || auth()->user()->isAcademicTeacher())
    {{-- Show teacher layout for teachers --}}
    <x-layouts.teacher :title="$pageTitle" :description="$pageDescription">
      @include('public.recorded-courses.partials.index-content')
    </x-layouts.teacher>
  @else
    {{-- Show student layout for regular users --}}
    <x-layouts.student :title="$pageTitle" :description="$pageDescription">
      @include('public.recorded-courses.partials.index-content')
    </x-layouts.student>
  @endif
@else
<x-layouts.public-layout :title="$pageTitle" :academy="$academy" :description="$pageDescription">
  @include('public.recorded-courses.partials.index-content')
</x-layouts.public-layout>
@endauth
