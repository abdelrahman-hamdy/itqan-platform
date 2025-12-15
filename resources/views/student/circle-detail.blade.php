@auth
  @if(auth()->user()->isAdmin() || auth()->user()->isSuperAdmin())
    {{-- Show public layout for admin/superadmin --}}
    <x-layouts.public-layout
        :title="$circle->name . ' - ' . ($academy->name ?? 'منصة إتقان')"
        :description="'تفاصيل حلقة القرآن: ' . $circle->name"
        :academy="$academy">
        @include('student.partials.circle-detail-content')
    </x-layouts.public-layout>
  @elseif(auth()->user()->isParent())
    {{-- Show parent layout for parents --}}
    <x-layouts.parent-layout :title="$circle->name . ' - ' . ($academy->name ?? 'منصة إتقان')">
        @include('student.partials.circle-detail-content')
    </x-layouts.parent-layout>
  @elseif(auth()->user()->isQuranTeacher() || auth()->user()->isAcademicTeacher())
    {{-- Show teacher layout for teachers --}}
    <x-layouts.teacher
        :title="$circle->name . ' - ' . ($academy->name ?? 'منصة إتقان')"
        :description="'تفاصيل حلقة القرآن: ' . $circle->name">
        @include('student.partials.circle-detail-content')
    </x-layouts.teacher>
  @else
    {{-- Show student layout for students --}}
    <x-layouts.student
        :title="$circle->name . ' - ' . ($academy->name ?? 'منصة إتقان')"
        :description="'تفاصيل حلقة القرآن: ' . $circle->name">
        @include('student.partials.circle-detail-content')
    </x-layouts.student>
  @endif
@else
<x-layouts.public-layout
    :title="$circle->name . ' - ' . ($academy->name ?? 'منصة إتقان')"
    :description="'تفاصيل حلقة القرآن: ' . $circle->name"
    :academy="$academy">
    @include('student.partials.circle-detail-content')
</x-layouts.public-layout>
@endauth
