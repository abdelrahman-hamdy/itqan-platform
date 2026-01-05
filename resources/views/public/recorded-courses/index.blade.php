@php
  $pageTitle = __('courses.index.page_title_suffix') . ($academy->name ?? __('components.layouts.academy_default'));
  $pageDescription = __('courses.index.page_description_suffix') . ($academy->name ?? __('components.layouts.academy_default'));
@endphp

@auth
  @if(auth()->user()->isAdmin() || auth()->user()->isSuperAdmin() || auth()->user()->isSupervisor())
    {{-- Show public layout for admin/superadmin/supervisor --}}
    <x-layouts.public-layout :title="$pageTitle" :academy="$academy" :description="$pageDescription">
      @include('public.recorded-courses.partials.index-content')
    </x-layouts.public-layout>
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
