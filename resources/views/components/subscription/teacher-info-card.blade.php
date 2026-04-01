@props(['teacher', 'teacherType' => 'academic', 'compact' => false])

@if($compact)
{{-- Compact collapsible mode --}}
<div x-data="{ expanded: false }">
  <button type="button" @click="expanded = !expanded" class="w-full flex items-center justify-between group">
    <div class="flex items-center gap-3">
      <x-avatar
        :user="$teacher"
        size="sm"
        :userType="$teacherType === 'quran' ? 'quran_teacher' : 'academic_teacher'" />
      <div class="text-start">
        <div class="font-semibold text-gray-900 text-sm">{{ $teacher->full_name ?? $teacher->user->name }}</div>
        <div class="text-xs text-gray-500">
          @if($teacherType === 'quran')
            {{ __('public.booking.quran.teacher_info.certified') }}
          @else
            {{ __('public.academic_packages.teachers.certified') }}
          @endif
        </div>
      </div>
    </div>
    <i class="ri-arrow-down-s-line text-gray-400 transition-transform duration-200"
       :class="expanded && 'rotate-180'"></i>
  </button>

  <div x-show="expanded" x-collapse x-cloak class="mt-3 ps-[3.75rem] space-y-2">
    @if(($teacher->rating ?? 0) > 0)
      <div class="flex items-center gap-2">
        <div class="flex text-yellow-400">
          @for($i = 1; $i <= 5; $i++)
            <i class="ri-star-{{ $i <= $teacher->rating ? 'fill' : 'line' }} text-xs"></i>
          @endfor
        </div>
        <span class="text-xs text-gray-500">({{ $teacher->rating }})</span>
      </div>
    @endif

    @if($teacher->experience_years ?? $teacher->teaching_experience_years)
      <div class="flex items-center gap-2 text-xs text-gray-600">
        <i class="ri-award-line text-indigo-500"></i>
        <span>{{ $teacher->experience_years ?? $teacher->teaching_experience_years }} {{ __('public.academic_packages.teachers.experience') }}</span>
      </div>
    @endif

    @if($teacher->education_level)
      <div class="flex items-center gap-2 text-xs text-gray-600">
        <i class="ri-graduation-cap-line text-green-500"></i>
        <span>
          {{ $teacher->education_level instanceof \App\Enums\EducationalQualification ? $teacher->education_level->label() : \App\Enums\EducationalQualification::getLabel($teacher->education_level) }}
          @if($teacher->university)
            - {{ $teacher->university }}
          @endif
        </span>
      </div>
    @endif

    @if($teacherType === 'academic' && $teacher->subjects && $teacher->subjects->count() > 0)
      <div class="flex flex-wrap gap-1.5 mt-1">
        @foreach($teacher->subjects as $subject)
          <span class="bg-indigo-100 text-indigo-700 text-[10px] px-2 py-0.5 rounded-full">{{ $subject->name }}</span>
        @endforeach
      </div>
    @endif
  </div>
</div>

@else
{{-- Full card mode --}}
<div class="bg-white rounded-xl shadow-sm border border-gray-200 p-6">
  <h3 class="text-lg font-bold text-gray-900 mb-4 flex items-center gap-2">
    <i class="ri-user-star-line text-primary"></i>
    {{ __('public.booking.quran.teacher_info.title') }}
  </h3>
  <div class="flex items-center gap-4 mb-4">
    <x-avatar
      :user="$teacher"
      size="lg"
      :userType="$teacherType === 'quran' ? 'quran_teacher' : 'academic_teacher'"
    <div>
      <h4 class="font-bold text-gray-900">{{ $teacher->full_name ?? $teacher->user->name }}</h4>
      <p class="text-gray-600">
        @if($teacherType === 'quran')
          {{ __('public.booking.quran.teacher_info.certified') }}
        @else
          {{ __('public.academic_packages.teachers.certified') }}
        @endif
      </p>
      @if($teacher->teacher_code)
        <p class="text-sm text-gray-500">{{ $teacher->teacher_code }}</p>
      @endif
    </div>
  </div>

  @if(($teacher->rating ?? 0) > 0)
    <div class="flex items-center gap-2 mt-3">
      <div class="flex text-yellow-400">
        @for($i = 1; $i <= 5; $i++)
          <i class="ri-star-{{ $i <= $teacher->rating ? 'fill' : 'line' }} text-sm"></i>
        @endfor
      </div>
      <span class="text-sm text-gray-600">({{ $teacher->rating }})</span>
    </div>
  @endif

  @if($teacher->experience_years ?? $teacher->teaching_experience_years)
    <div class="flex items-center gap-2 text-sm text-gray-600 mb-2 mt-3">
      <i class="ri-award-line text-blue-500"></i>
      <span>{{ $teacher->experience_years ?? $teacher->teaching_experience_years }} {{ __('public.academic_packages.teachers.experience') }}</span>
    </div>
  @endif

  @if($teacher->education_level)
    <div class="flex items-center gap-2 text-sm text-gray-600 mb-2">
      <i class="ri-graduation-cap-line text-green-500"></i>
      <span>
        {{ $teacher->education_level instanceof \App\Enums\EducationalQualification ? $teacher->education_level->label() : \App\Enums\EducationalQualification::getLabel($teacher->education_level) }}
        @if($teacher->university)
          - {{ $teacher->university }}
        @endif
      </span>
    </div>
  @endif

  @if($teacherType === 'academic' && $teacher->subjects && $teacher->subjects->count() > 0)
    <div class="mb-3">
      <h5 class="text-sm font-medium text-gray-700 mb-2">{{ __('public.academic_packages.teachers.specializations') }}</h5>
      <div class="flex flex-wrap gap-2">
        @foreach($teacher->subjects as $subject)
          <span class="bg-blue-100 text-blue-800 text-xs px-2 py-1 rounded-full">{{ $subject->name }}</span>
        @endforeach
      </div>
    </div>
  @endif
</div>
@endif
