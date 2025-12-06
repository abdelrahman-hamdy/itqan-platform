@php
  $pageTitle = ($academy->name ?? 'أكاديمية إتقان') . ' - المعلمون الأكاديميون';
  $pageDescription = 'استكشف المعلمين الأكاديميين المتاحين - ' . ($academy->name ?? 'أكاديمية إتقان');
@endphp

@auth
  @if(auth()->user()->isAdmin() || auth()->user()->isSuperAdmin())
    {{-- Show public layout for admin/superadmin --}}
    <x-layouts.public-layout :title="$pageTitle" :academy="$academy" :description="$pageDescription">
      @include('student.partials.academic-teachers-content')
    </x-layouts.public-layout>
  @elseif(auth()->user()->isParent())
    {{-- Show parent layout for parents --}}
    <x-layouts.parent-layout :title="$pageTitle">
      @include('student.partials.academic-teachers-content')
    </x-layouts.parent-layout>
  @else
    {{-- Show student layout for students --}}
    <x-layouts.student :title="$pageTitle" :description="$pageDescription">
      @include('student.partials.academic-teachers-content')
    </x-layouts.student>
  @endif
@else
<x-layouts.public-layout :title="$pageTitle" :academy="$academy" :description="$pageDescription">
  @include('student.partials.academic-teachers-content')
</x-layouts.public-layout>
@endauth
