@php
  $pageTitle = ($academy->name ?? 'أكاديمية إتقان') . ' - معلمو القرآن الكريم';
  $pageDescription = 'استكشف معلمي القرآن الكريم المتاحين - ' . ($academy->name ?? 'أكاديمية إتقان');
@endphp

@auth
  @if(auth()->user()->isAdmin() || auth()->user()->isSuperAdmin())
    {{-- Show public layout for admin/superadmin --}}
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
