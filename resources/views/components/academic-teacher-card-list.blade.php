@props(['teacher', 'academy', 'subjects' => collect(), 'gradeLevels' => collect()])

@php
  $isSubscribed = $teacher->is_subscribed ?? false;
  $subscription = $teacher->my_subscription ?? null;
  $rating = $teacher->average_rating ?? $teacher->rating ?? 0;
  $studentsCount = $teacher->total_students ?? 0;
  $teacherSubjects = collect($teacher->subject_ids ?? [])->map(function($id) use ($subjects) {
    return $subjects->firstWhere('id', $id);
  })->filter();
  $teacherGradeLevels = collect($teacher->grade_level_ids ?? [])->map(function($id) use ($gradeLevels) {
    return $gradeLevels->firstWhere('id', $id);
  })->filter();
@endphp

<div class="bg-white rounded-xl shadow-sm border border-gray-200 card-hover flex flex-col overflow-hidden">
  {{-- Section 1: Colored Header — My Teacher + Name/Rating + Avatar --}}
  <div class="relative bg-violet-50 pe-4 sm:pe-6 ps-36 sm:ps-40 min-h-[100px] flex flex-col justify-end pb-3">
    {{-- Avatar: vertically centered on bottom border, positioned at start side --}}
    <div class="absolute start-4 sm:start-6 -bottom-8 z-10">
      <div class="rounded-full border-4 border-white">
        <x-avatar
          :user="$teacher"
          size="lg"
          userType="academic_teacher"
          :gender="$teacher->gender ?? $teacher->user?->gender ?? 'male'"
          class="flex-shrink-0" />
      </div>
    </div>

    {{-- My Teacher Badge (floated to end/left in RTL) --}}
    @if($isSubscribed)
    <div class="mb-2 flex justify-end">
      <span class="inline-flex items-center px-2.5 py-1 rounded-full text-xs font-semibold bg-green-500 text-white shadow-sm">
        <i class="ri-check-line me-1"></i>
        {{ __('components.cards.academic_teacher.my_teacher') }}
      </span>
    </div>
    @endif

    {{-- Name + Rating (stuck to bottom, full width beside avatar) --}}
    <div class="flex items-end justify-between gap-3">
      <h3 class="font-bold text-gray-900 text-lg leading-tight truncate">
        {{ $teacher->full_name ?? __('components.cards.academic_teacher.default_name') }}
      </h3>
      <div class="flex items-center gap-1 flex-shrink-0">
        <x-reviews.star-rating
          :rating="$rating"
          :total-reviews="$teacher->total_reviews ?? null"
          size="sm"
        />
      </div>
    </div>
  </div>

  {{-- Section 2: Main Info Area (ps + pt clears avatar overflow from header) --}}
  <div class="px-4 sm:px-6 pt-10 flex-1 flex flex-col">
    {{-- Key Details --}}
    <div class="space-y-2.5 mb-3">
      {{-- Qualification + Experience Row --}}
      <div class="flex flex-wrap items-center gap-2">
        @if($teacher->educational_qualification)
        <span class="inline-flex items-center px-2.5 py-1 rounded-full text-xs font-medium bg-violet-100 text-violet-800">
          <i class="ri-graduation-cap-line me-1"></i>
          {{ $teacher->educational_qualification instanceof \App\Enums\EducationalQualification ? $teacher->educational_qualification->label() : \App\Enums\EducationalQualification::getLabel($teacher->educational_qualification) }}
        </span>
        @endif
        @if($teacher->teaching_experience_years)
        <span class="inline-flex items-center px-2.5 py-1 rounded-full text-xs font-medium bg-gray-100 text-gray-700">
          <i class="ri-briefcase-line me-1 text-violet-600"></i>
          {{ $teacher->teaching_experience_years }}
          {{ $teacher->teaching_experience_years == 1 ? __('components.cards.academic_teacher.year_experience') : __('components.cards.academic_teacher.years_experience') }}
        </span>
        @endif
      </div>

      {{-- Subjects --}}
      @if($teacherSubjects->count() > 0)
      <div class="flex items-center text-sm text-gray-600">
        <i class="ri-book-line me-1.5 text-violet-600"></i>
        <div class="flex flex-wrap gap-1">
          <span class="inline-flex items-center px-1.5 py-0.5 rounded text-xs font-medium bg-violet-100 text-violet-800 truncate">
            {{ $teacherSubjects->first()->name }}
          </span>
          @if($teacherSubjects->count() > 1)
          <span class="inline-flex items-center px-1.5 py-0.5 rounded text-xs font-medium bg-gray-100 text-gray-700">
            +{{ $teacherSubjects->count() - 1 }}
          </span>
          @endif
        </div>
      </div>
      @endif

      {{-- Grade Levels --}}
      @if($teacherGradeLevels->count() > 0)
      <div class="flex items-center text-sm text-gray-600">
        <i class="ri-file-list-3-line me-1.5 text-violet-600"></i>
        <div class="flex flex-wrap gap-1">
          <span class="inline-flex items-center px-1.5 py-0.5 rounded text-xs font-medium bg-violet-100 text-violet-800 truncate">
            {{ $teacherGradeLevels->first()->name }}
          </span>
          @if($teacherGradeLevels->count() > 1)
          <span class="inline-flex items-center px-1.5 py-0.5 rounded text-xs font-medium bg-gray-100 text-gray-700">
            +{{ $teacherGradeLevels->count() - 1 }}
          </span>
          @endif
        </div>
      </div>
      @endif

      {{-- Students Count --}}
      @if($studentsCount > 0)
      <div class="flex items-center text-sm text-gray-600">
        <i class="ri-group-line me-1.5 text-violet-600"></i>
        <span>{{ $studentsCount }} {{ __('components.cards.academic_teacher.students_count') }}</span>
      </div>
      @endif
    </div>

    {{-- Bio --}}
    @if($teacher->bio_arabic || $teacher->bio_english)
    <p class="text-sm text-gray-500 mb-3 line-clamp-2 leading-relaxed">
      {{ $teacher->bio_arabic ?? $teacher->bio_english }}
    </p>
    @endif

    {{-- Languages --}}
    @if($teacher->languages && is_array($teacher->languages) && count($teacher->languages) > 0)
    <div class="mb-3">
      <div class="flex flex-wrap gap-1.5">
        @foreach(array_slice($teacher->languages, 0, 3) as $lang)
        <span class="inline-flex items-center px-2 py-1 rounded-md text-xs font-medium bg-blue-50 text-blue-700">
          <i class="ri-global-line me-1"></i>
          @php
            try { $langLabel = \App\Enums\TeachingLanguage::from($lang)->label(); }
            catch (\ValueError $e) { $langLabel = $lang; }
          @endphp
          {{ $langLabel }}
        </span>
        @endforeach
        @if(count($teacher->languages) > 3)
        <span class="inline-flex items-center px-2 py-1 rounded-md text-xs font-medium bg-gray-100 text-gray-700">
          +{{ count($teacher->languages) - 3 }}
        </span>
        @endif
      </div>
    </div>
    @endif

    {{-- Subscribed State Panel --}}
    @if($isSubscribed && $subscription)
    <div class="bg-violet-50 rounded-lg p-3 mb-3 border border-violet-100">
      <div class="flex items-center justify-between text-sm mb-2">
        <span class="text-violet-700 font-medium">
          <i class="ri-play-circle-line me-1"></i>
          {{ $subscription->total_sessions_completed ?? 0 }} {{ __('components.cards.academic_teacher.sessions_progress') }} {{ $subscription->total_sessions ?? 0 }} {{ __('components.cards.academic_teacher.session_unit') }}
        </span>
        <span class="text-xs font-bold text-violet-800">{{ $subscription->progress_percentage ?? 0 }}%</span>
      </div>
      <div class="bg-violet-200 rounded-full h-1.5">
        <div class="bg-violet-600 h-1.5 rounded-full transition-all"
             style="width: {{ $subscription->progress_percentage ?? 0 }}%"></div>
      </div>
    </div>
    @endif

    {{-- Spacer --}}
    <div class="flex-grow"></div>

    {{-- Section 3: Action Buttons --}}
    <div class="pb-4 sm:pb-6 pt-3 mt-auto">
      @if($isSubscribed && $subscription)
        {{-- Subscribed: Open Lesson + Chat icon + Profile icon --}}
        <div class="flex items-center gap-2">
          <a href="{{ route('student.academic-subscriptions.show', ['subdomain' => $academy->subdomain ?? 'itqan-academy', 'subscriptionId' => $subscription->id]) }}"
             class="flex-1 inline-flex items-center justify-center gap-1.5 bg-violet-600 text-white h-11 px-4 rounded-lg text-sm font-semibold hover:bg-violet-700 transition-colors">
            <i class="ri-book-open-line"></i>
            {{ __('components.cards.academic_teacher.open_lesson') }}
          </a>

          @if($teacher->user && $teacher->user->hasSupervisor())
            <x-chat.supervised-chat-button
                :teacher="$teacher->user"
                :student="auth()->user()"
                entityType="academic_lesson"
                :entityId="$subscription->id"
                variant="icon-only"
                size="md"
                class="!w-11 !h-11 !p-0 !rounded-lg !bg-blue-50 !border !border-blue-200 !text-blue-600 hover:!bg-blue-100"
            />
          @endif

          <a href="{{ route('academic-teachers.show', ['subdomain' => $academy->subdomain ?? 'itqan-academy', 'teacherId' => $teacher->id]) }}"
             class="w-11 h-11 inline-flex items-center justify-center rounded-lg border border-gray-200 text-gray-500 hover:bg-gray-50 hover:text-violet-600 transition-colors flex-shrink-0"
             title="{{ __('components.cards.academic_teacher.view_profile') }}">
            <i class="ri-user-line text-lg"></i>
          </a>
        </div>
      @else
        {{-- Not subscribed: View Profile full-width --}}
        <a href="{{ route('academic-teachers.show', ['subdomain' => $academy->subdomain ?? 'itqan-academy', 'teacherId' => $teacher->id]) }}"
           class="w-full inline-flex items-center justify-center gap-1.5 bg-violet-600 text-white h-11 rounded-lg text-sm font-semibold hover:bg-violet-700 transition-colors">
          <i class="ri-eye-line"></i>
          {{ __('components.cards.academic_teacher.view_profile') }}
        </a>
      @endif
    </div>
  </div>
</div>
