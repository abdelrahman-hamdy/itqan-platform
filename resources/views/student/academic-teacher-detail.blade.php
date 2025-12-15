@auth
  @if(auth()->user()->isAdmin() || auth()->user()->isSuperAdmin())
    {{-- Show public layout for admin/superadmin --}}
    <x-layouts.public-layout
      title="المعلم الأكاديمي - {{ $teacher->user->name }}"
      description="تعلم مع الأستاذ {{ $teacher->user->name }} - معلم مؤهل ومعتمد في {{ $academy->name ?? 'أكاديمية إتقان' }}"
      :academy="$academy">
      @include('student.partials.academic-teacher-detail-content')
    </x-layouts.public-layout>
  @elseif(auth()->user()->isParent())
    {{-- Show parent layout for parents --}}
    <x-layouts.parent-layout title="المعلم الأكاديمي - {{ $teacher->user->name }}">
      @include('student.partials.academic-teacher-detail-content')
    </x-layouts.parent-layout>
  @elseif(auth()->user()->isQuranTeacher() || auth()->user()->isAcademicTeacher())
    {{-- Show teacher layout for teachers --}}
    <x-layouts.teacher
      title="المعلم الأكاديمي - {{ $teacher->user->name }}"
      description="تعلم مع الأستاذ {{ $teacher->user->name }} - معلم مؤهل ومعتمد في {{ $academy->name ?? 'أكاديمية إتقان' }}">
      @include('student.partials.academic-teacher-detail-content')
    </x-layouts.teacher>
  @else
    {{-- Show student layout for students --}}
    <x-layouts.student
      title="المعلم الأكاديمي - {{ $teacher->user->name }}"
      description="تعلم مع الأستاذ {{ $teacher->user->name }} - معلم مؤهل ومعتمد في {{ $academy->name ?? 'أكاديمية إتقان' }}">
      @include('student.partials.academic-teacher-detail-content')
    </x-layouts.student>
  @endif
@else
<x-layouts.public-layout
  title="المعلم الأكاديمي - {{ $teacher->user->name }}"
  description="تعلم مع الأستاذ {{ $teacher->user->name }} - معلم مؤهل ومعتمد في {{ $academy->name ?? 'أكاديمية إتقان' }}"
  :academy="$academy">
  @include('student.partials.academic-teacher-detail-content')
</x-layouts.public-layout>
@endauth
