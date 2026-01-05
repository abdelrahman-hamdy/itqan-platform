@php
    $pageTitle = $circle->name . ' - ' . ($academy->name ?? __('student.common.platform_default'));
    $pageDescription = __('student.group_circle.meta_description') . ' ' . $circle->name;
@endphp

@auth
  @if(auth()->user()->isAdmin() || auth()->user()->isSuperAdmin() || auth()->user()->isSupervisor())
    {{-- Show public layout for admin/superadmin/supervisor --}}
    <x-layouts.public-layout
        :title="$pageTitle"
        :description="$pageDescription"
        :academy="$academy">
        @include('student.partials.circle-detail-content')
    </x-layouts.public-layout>
  @elseif(auth()->user()->isParent())
    {{-- Show parent layout for parents --}}
    <x-layouts.parent-layout :title="$pageTitle">
        @include('student.partials.circle-detail-content')
    </x-layouts.parent-layout>
  @elseif(auth()->user()->isQuranTeacher() || auth()->user()->isAcademicTeacher())
    {{-- Show teacher layout for teachers --}}
    <x-layouts.teacher
        :title="$pageTitle"
        :description="$pageDescription">
        @include('student.partials.circle-detail-content')
    </x-layouts.teacher>
  @else
    {{-- Show student layout for students --}}
    <x-layouts.student
        :title="$pageTitle"
        :description="$pageDescription">
        @include('student.partials.circle-detail-content')
    </x-layouts.student>
  @endif
@else
<x-layouts.public-layout
    :title="$pageTitle"
    :description="$pageDescription"
    :academy="$academy">
    @include('student.partials.circle-detail-content')
</x-layouts.public-layout>
@endauth
