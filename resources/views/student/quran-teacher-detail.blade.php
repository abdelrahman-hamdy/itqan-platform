@auth
  @if(auth()->user()->isAdmin() || auth()->user()->isSuperAdmin())
    {{-- Show public layout for admin/superadmin --}}
    <x-layouts.public-layout
      title="{{ __('student.teacher_detail.quran_teacher_title') }} - {{ $teacher->user->name }}"
      description="{{ __('student.teacher_detail.quran_teacher_description') }} {{ $teacher->user->name }} - {{ __('student.teacher_detail.quran_teacher_certified') }} {{ $academy->name ?? __('student.common.academy_default') }}"
      :academy="$academy">
      @include('student.partials.quran-teacher-detail-content')
    </x-layouts.public-layout>
  @elseif(auth()->user()->isParent())
    {{-- Show parent layout for parents --}}
    <x-layouts.parent-layout title="{{ __('student.teacher_detail.quran_teacher_title') }} - {{ $teacher->user->name }}">
      @include('student.partials.quran-teacher-detail-content')
    </x-layouts.parent-layout>
  @elseif(auth()->user()->isQuranTeacher() || auth()->user()->isAcademicTeacher())
    {{-- Show teacher layout for teachers --}}
    <x-layouts.teacher
      title="{{ __('student.teacher_detail.quran_teacher_title') }} - {{ $teacher->user->name }}"
      description="{{ __('student.teacher_detail.quran_teacher_description') }} {{ $teacher->user->name }} - {{ __('student.teacher_detail.quran_teacher_certified') }} {{ $academy->name ?? __('student.common.academy_default') }}">
      @include('student.partials.quran-teacher-detail-content')
    </x-layouts.teacher>
  @else
    {{-- Show student layout for students --}}
    <x-layouts.student
      title="{{ __('student.teacher_detail.quran_teacher_title') }} - {{ $teacher->user->name }}"
      description="{{ __('student.teacher_detail.quran_teacher_description') }} {{ $teacher->user->name }} - {{ __('student.teacher_detail.quran_teacher_certified') }} {{ $academy->name ?? __('student.common.academy_default') }}">
      @include('student.partials.quran-teacher-detail-content')
    </x-layouts.student>
  @endif
@else
<x-layouts.public-layout
  title="{{ __('student.teacher_detail.quran_teacher_title') }} - {{ $teacher->user->name }}"
  description="{{ __('student.teacher_detail.quran_teacher_description') }} {{ $teacher->user->name }} - {{ __('student.teacher_detail.quran_teacher_certified') }} {{ $academy->name ?? __('student.common.academy_default') }}"
  :academy="$academy">
  @include('student.partials.quran-teacher-detail-content')
</x-layouts.public-layout>
@endauth
