@php
    // Get gradient palette
    $gradientPalette = $academy?->gradient_palette ?? \App\Enums\GradientPalette::OCEAN_BREEZE;
    $hexColors = $gradientPalette->getHexColors();
    $gradientFromHex = $hexColors['from'];
    $gradientToHex = $hexColors['to'];

    $brandColor = $academy?->brand_color ?? \App\Enums\TailwindColor::SKY;
    $brandHex500 = $brandColor->getHexValue(500);
    $brandHex600 = $brandColor->getHexValue(600);

    $showCourses = $academy->academic_show_courses ?? true;
    $showTeachers = $academy->academic_show_teachers ?? true;

    $courseItems = $interactiveCourses->take(6);
    $teacherItems = $academicTeachers->take(6);
@endphp

<!-- Academic Section - Template 2: Dual Sub-sections with Horizontal Scroll -->
<section id="academic" class="py-16 sm:py-20 lg:py-24 scroll-mt-20">
  <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
    <!-- Section Header -->
    <div class="text-center mb-10 sm:mb-14">
      <h2 class="text-2xl sm:text-3xl font-bold text-gray-900 mb-3">{{ $heading ?? __('academy.academic_section.default_heading') }}</h2>
      @if(isset($subheading))
        <p class="text-base sm:text-lg text-gray-600">{{ $subheading }}</p>
      @endif
    </div>

    {{-- Sub-section A: Interactive Courses (Horizontal Scroll) --}}
    @if($showCourses)
    <div class="rounded-2xl p-6 sm:p-8 lg:p-10 mb-8 lg:mb-12" style="background: linear-gradient(135deg, {{ $gradientFromHex }}0d, {{ $gradientFromHex }}06, white);">
      <div class="mb-6 sm:mb-8">
        <div class="flex items-center gap-3 mb-2">
          <div class="w-8 h-8 rounded-lg flex items-center justify-center" style="background-color: {{ $gradientFromHex }}20;">
            <i class="ri-book-open-line text-base" style="color: {{ $gradientFromHex }};"></i>
          </div>
          <h3 class="text-lg sm:text-xl font-bold text-gray-900">{{ __('academy.academic_section.interactive_courses_title') }}</h3>
        </div>
        <p class="text-sm text-gray-500 ms-11">{{ __('academy.academic_section.interactive_courses_subtitle') }}</p>
      </div>

      @if($courseItems->count() > 0)
      <div id="interactive-courses-scroll" class="relative">
        {{-- Scroll Container --}}
        <div class="overflow-x-auto pb-4 -mx-2 px-2 scroll-smooth" style="scrollbar-width: thin; scrollbar-color: {{ $gradientFromHex }} #f3f4f6;">
          <div class="flex gap-4 sm:gap-5" style="scroll-snap-type: x mandatory;">
            @foreach($courseItems as $course)
            @php
              $isEnrolled = $enrollment ?? false;
            @endphp
            <div class="flex-shrink-0 w-[85vw] sm:w-[480px] md:w-[620px] scroll-snap-align-start">
              <div class="bg-white rounded-xl border border-gray-100 shadow-sm overflow-hidden transition-all duration-200 hover:shadow-md hover:border-gray-200 h-full flex flex-col md:flex-row">
                {{-- Course Visual (Gradient Placeholder) --}}
                <div class="md:w-[40%] flex-shrink-0 relative overflow-hidden" style="background: linear-gradient(135deg, {{ $gradientFromHex }}30, {{ $brandHex500 }}20, {{ $gradientFromHex }}10);">
                  <div class="flex items-center justify-center h-40 md:h-full min-h-[160px]">
                    <div class="text-center">
                      <i class="ri-book-open-line text-4xl opacity-40" style="color: {{ $gradientFromHex }};"></i>
                      @if($course->subject)
                      <p class="text-xs font-medium mt-2 opacity-60" style="color: {{ $gradientFromHex }};">{{ $course->subject->name }}</p>
                      @endif
                    </div>
                  </div>
                  {{-- Status Badge --}}
                  <div class="absolute top-3 start-3">
                    <span class="inline-flex items-center px-2 py-0.5 rounded-full text-[10px] font-semibold
                      {{ $course->is_published ? 'bg-blue-100 text-blue-700' : 'bg-gray-100 text-gray-600' }}">
                      {{ $course->is_published ? __('components.cards.interactive_course.status_available') : __('components.cards.interactive_course.status_unavailable') }}
                    </span>
                  </div>
                </div>

                {{-- Course Content --}}
                <div class="md:w-[60%] p-4 sm:p-5 flex flex-col">
                  {{-- Title + Rating --}}
                  <div class="flex items-start justify-between gap-2 mb-2">
                    <h4 class="text-sm sm:text-base font-bold text-gray-900 line-clamp-2">{{ $course->title }}</h4>
                    @if(($course->avg_rating ?? 0) > 0)
                    <div class="flex items-center gap-0.5 flex-shrink-0">
                      <i class="ri-star-fill text-xs text-amber-400"></i>
                      <span class="text-xs font-medium text-gray-600">{{ number_format($course->avg_rating, 1) }}</span>
                    </div>
                    @endif
                  </div>

                  @if($course->description)
                  <p class="text-xs text-gray-500 line-clamp-2 mb-3">{{ $course->description }}</p>
                  @endif

                  {{-- Details --}}
                  <div class="space-y-2 text-xs text-gray-600 mb-3">
                    @if($course->assignedTeacher)
                    <div class="flex items-center gap-2">
                      <i class="ri-user-star-line" style="color: {{ $gradientFromHex }};"></i>
                      <span class="truncate">{{ $course->assignedTeacher->full_name }}</span>
                    </div>
                    @endif

                    @if($course->gradeLevel)
                    <div class="flex items-center gap-2">
                      <i class="ri-graduation-cap-line" style="color: {{ $gradientFromHex }};"></i>
                      <span>{{ $course->gradeLevel->getDisplayName() }}</span>
                    </div>
                    @endif

                    <div class="flex items-center gap-2">
                      <i class="ri-calendar-line" style="color: {{ $gradientFromHex }};"></i>
                      <span>{{ __('academy.cards.sessions', ['count' => $course->total_sessions ?? 0]) }} &bull; {{ __('academy.cards.weeks', ['count' => $course->duration_weeks ?? 0]) }}</span>
                    </div>

                    @if($course->schedule && is_array($course->schedule) && count($course->schedule) > 0)
                    <div class="flex items-center gap-1.5 flex-wrap">
                      @foreach(array_slice($course->schedule, 0, 3) as $item)
                      <span class="inline-flex px-1.5 py-0.5 rounded text-[10px] font-medium bg-gray-50 text-gray-600">
                        {{ is_array($item) ? ($item['day'] ?? '') : ($item->day ?? '') }}
                        @if(is_array($item) ? ($item['time'] ?? null) : ($item->time ?? null))
                          : {{ is_array($item) ? $item['time'] : $item->time }}
                        @endif
                      </span>
                      @endforeach
                    </div>
                    @endif
                  </div>

                  {{-- Spacer --}}
                  <div class="flex-grow"></div>

                  {{-- Price + CTA --}}
                  <div class="flex items-center justify-between gap-3 pt-3 border-t border-gray-50">
                    @if($course->student_price > 0)
                    <div>
                      @if($course->hasDiscount())
                        <span class="text-sm font-bold" style="color: {{ $gradientFromHex }};">{{ number_format($course->sale_price) }} {{ getCurrencySymbol() }}</span>
                        <span class="text-xs text-gray-400 line-through ms-1">{{ number_format($course->student_price) }}</span>
                      @else
                        <span class="text-sm font-bold" style="color: {{ $gradientFromHex }};">{{ number_format($course->student_price) }} {{ getCurrencySymbol() }}</span>
                      @endif
                    </div>
                    @else
                    <span class="text-sm font-bold text-green-600">{{ __('academy.cards.free') }}</span>
                    @endif

                    <a href="{{ route('interactive-courses.show', ['subdomain' => $academy->subdomain, 'courseId' => $course->id]) }}"
                       class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-lg text-xs font-semibold text-white transition-all duration-200 hover:opacity-90"
                       style="background-color: {{ $gradientFromHex }};">
                      {{ __('academy.cards.view_details') }}
                      <i class="ri-arrow-left-s-line text-sm ltr:rotate-180"></i>
                    </a>
                  </div>
                </div>
              </div>
            </div>
            @endforeach
          </div>
        </div>

        {{-- Navigation Arrows --}}
        @if($courseItems->count() > 1)
        <button class="scroll-prev hidden sm:flex absolute top-1/2 -translate-y-1/2 -start-3 lg:-start-5 w-10 h-10 bg-white rounded-full shadow-lg hover:shadow-xl transition-all duration-200 items-center justify-center z-10"
                style="color: {{ $gradientFromHex }};" aria-label="{{ __('academy.actions.view_more') }}">
          <i class="ri-arrow-right-s-line text-xl ltr:rotate-180"></i>
        </button>
        <button class="scroll-next hidden sm:flex absolute top-1/2 -translate-y-1/2 -end-3 lg:-end-5 w-10 h-10 bg-white rounded-full shadow-lg hover:shadow-xl transition-all duration-200 items-center justify-center z-10"
                style="color: {{ $gradientFromHex }};" aria-label="{{ __('academy.actions.view_more') }}">
          <i class="ri-arrow-left-s-line text-xl ltr:rotate-180"></i>
        </button>
        @endif
      </div>

      @if($interactiveCourses->count() > 0)
      <div class="text-center mt-6">
        <a href="{{ route('interactive-courses.index', ['subdomain' => $academy->subdomain]) }}"
           class="inline-flex items-center gap-2 text-sm font-semibold transition-colors hover:gap-3"
           style="color: {{ $gradientFromHex }};">
          {{ __('academy.actions.view_more') }}
          <i class="ri-arrow-left-line ltr:rotate-180"></i>
        </a>
      </div>
      @endif
      @else
      <div class="text-center py-8">
        <div class="w-14 h-14 rounded-full flex items-center justify-center mx-auto mb-3"
             style="background-color: {{ $gradientFromHex }}1a;">
          <i class="ri-book-open-line text-xl" style="color: {{ $gradientFromHex }};"></i>
        </div>
        <p class="text-sm font-medium text-gray-700">{{ __('academy.academic_section.no_courses_title') }}</p>
        <p class="text-xs text-gray-500 mt-1">{{ __('academy.academic_section.no_courses_message') }}</p>
      </div>
      @endif
    </div>
    @endif

    {{-- Sub-section B: Academic Teachers (Compact Grid) --}}
    @if($showTeachers)
    <div class="rounded-2xl p-6 sm:p-8 lg:p-10" style="background: linear-gradient(135deg, {{ $gradientToHex }}0d, {{ $gradientToHex }}08, white);">
      <div class="mb-6 sm:mb-8">
        <div class="flex items-center gap-3 mb-2">
          <div class="w-8 h-8 rounded-lg flex items-center justify-center" style="background-color: {{ $gradientToHex }}20;">
            <i class="ri-user-star-line text-base" style="color: {{ $gradientToHex }};"></i>
          </div>
          <h3 class="text-lg sm:text-xl font-bold text-gray-900">{{ __('academy.academic_section.academic_teachers_title') }}</h3>
        </div>
        <p class="text-sm text-gray-500 ms-11">{{ __('academy.academic_section.academic_teachers_subtitle') }}</p>
      </div>

      @if($teacherItems->count() > 0)
      <div class="grid sm:grid-cols-2 lg:grid-cols-3 gap-3 sm:gap-4">
        @foreach($teacherItems as $teacher)
        @php
          $rating = $teacher->average_rating ?? $teacher->rating ?? 0;
          $teacherName = $teacher->full_name ?? '';
          $qualification = $teacher->educational_qualification;
          $qualificationLabel = $qualification instanceof \App\Enums\EducationalQualification
              ? $qualification->label()
              : ($qualification ? \App\Enums\EducationalQualification::getLabel($qualification) : null);
          $teacherSubjects = $teacher->relationLoaded('subjects')
              ? $teacher->subjects
              : (($teacher->subject_ids && is_array($teacher->subject_ids))
                  ? \App\Models\AcademicSubject::whereIn('id', $teacher->subject_ids)->limit(3)->get()
                  : collect());
        @endphp
        <a href="{{ route('academic-teachers.show', ['subdomain' => $academy->subdomain, 'teacherId' => $teacher->id]) }}"
           class="group flex items-start gap-3 bg-white rounded-xl p-3.5 border border-gray-100 shadow-sm transition-all duration-200 hover:shadow-md hover:border-gray-200">
          {{-- Avatar --}}
          <div class="flex-shrink-0">
            <x-avatar :user="$teacher" size="sm" userType="academic_teacher"
                       :gender="$teacher->gender ?? $teacher->user?->gender ?? 'male'" />
          </div>

          {{-- Info --}}
          <div class="min-w-0 flex-1">
            <div class="flex items-center justify-between gap-2 mb-1">
              <h4 class="text-sm font-bold text-gray-900 truncate">{{ $teacherName }}</h4>
              @if($rating > 0)
              <div class="flex items-center gap-0.5 flex-shrink-0">
                <i class="ri-star-fill text-xs text-amber-400"></i>
                <span class="text-xs font-medium text-gray-600">{{ number_format($rating, 1) }}</span>
              </div>
              @endif
            </div>

            @if($qualificationLabel)
            <span class="inline-block text-[10px] font-medium px-1.5 py-0.5 rounded-md mb-1.5"
                  style="background-color: {{ $gradientToHex }}15; color: {{ $gradientToHex }};">{{ $qualificationLabel }}</span>
            @endif

            <div class="flex items-center flex-wrap gap-x-3 gap-y-1 text-[11px] text-gray-500">
              @if($teacher->teaching_experience_years)
              <span class="flex items-center gap-1">
                <i class="ri-briefcase-line"></i>
                {{ __('academy.cards.experience_years', ['years' => $teacher->teaching_experience_years]) }}
              </span>
              @endif
              @if($teacherSubjects->isNotEmpty())
              <span class="flex items-center gap-1">
                <i class="ri-book-line"></i>
                {{ $teacherSubjects->first()->name }}
                @if($teacherSubjects->count() > 1)
                  <span class="font-medium" style="color: {{ $gradientToHex }};">+{{ $teacherSubjects->count() - 1 }}</span>
                @endif
              </span>
              @endif
            </div>
          </div>
        </a>
        @endforeach
      </div>

      @if($academicTeachers->count() > 0)
      <div class="text-center mt-6">
        <a href="{{ route('academic-teachers.index', ['subdomain' => $academy->subdomain]) }}"
           class="inline-flex items-center gap-2 text-sm font-semibold transition-colors hover:gap-3"
           style="color: {{ $gradientToHex }};">
          {{ __('academy.actions.view_more') }}
          <i class="ri-arrow-left-line ltr:rotate-180"></i>
        </a>
      </div>
      @endif
      @else
      <div class="text-center py-8">
        <div class="w-14 h-14 rounded-full flex items-center justify-center mx-auto mb-3"
             style="background-color: {{ $gradientToHex }}1a;">
          <i class="ri-user-star-line text-xl" style="color: {{ $gradientToHex }};"></i>
        </div>
        <p class="text-sm font-medium text-gray-700">{{ __('academy.academic_section.no_teachers_title') }}</p>
        <p class="text-xs text-gray-500 mt-1">{{ __('academy.academic_section.no_teachers_message') }}</p>
      </div>
      @endif
    </div>
    @endif
  </div>
</section>

<style>
  .scroll-snap-align-start {
    scroll-snap-align: start;
  }
  #interactive-courses-scroll .overflow-x-auto::-webkit-scrollbar {
    height: 6px;
  }
  #interactive-courses-scroll .overflow-x-auto::-webkit-scrollbar-track {
    background: #f3f4f6;
    border-radius: 3px;
  }
  #interactive-courses-scroll .overflow-x-auto::-webkit-scrollbar-thumb {
    background: {{ $gradientFromHex }};
    border-radius: 3px;
  }
</style>

<script>
document.addEventListener('DOMContentLoaded', function() {
    var container = document.getElementById('interactive-courses-scroll');
    if (!container) return;

    var scrollEl = container.querySelector('.overflow-x-auto');
    var prevBtn = container.querySelector('.scroll-prev');
    var nextBtn = container.querySelector('.scroll-next');

    if (!scrollEl) return;

    var scrollAmount = 400;
    var isRTL = document.documentElement.dir === 'rtl';

    if (prevBtn) {
        prevBtn.addEventListener('click', function() {
            scrollEl.scrollBy({ left: isRTL ? scrollAmount : -scrollAmount, behavior: 'smooth' });
        });
    }
    if (nextBtn) {
        nextBtn.addEventListener('click', function() {
            scrollEl.scrollBy({ left: isRTL ? -scrollAmount : scrollAmount, behavior: 'smooth' });
        });
    }

    // Touch swipe support
    var touchStartX = 0;
    scrollEl.addEventListener('touchstart', function(e) {
        touchStartX = e.changedTouches[0].screenX;
    }, { passive: true });

    scrollEl.addEventListener('touchend', function(e) {
        var diff = touchStartX - e.changedTouches[0].screenX;
        if (Math.abs(diff) > 50) {
            if ((diff > 0 && !isRTL) || (diff < 0 && isRTL)) {
                scrollEl.scrollBy({ left: scrollAmount, behavior: 'smooth' });
            } else {
                scrollEl.scrollBy({ left: -scrollAmount, behavior: 'smooth' });
            }
        }
    }, { passive: true });
});
</script>
