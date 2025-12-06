@php
  $pageTitle = 'الكورسات المسجلة - ' . ($academy->name ?? 'أكاديمية إتقان');
  $pageDescription = 'استكشف الكورسات المسجلة المتاحة - ' . ($academy->name ?? 'أكاديمية إتقان');
@endphp

@auth
  @if(auth()->user()->isAdmin() || auth()->user()->isSuperAdmin())
    {{-- Show public layout for admin/superadmin --}}
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
