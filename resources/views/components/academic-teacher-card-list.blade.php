@props(['teacher', 'academy', 'subjects' => collect(), 'gradeLevels' => collect()])

@php
  $isSubscribed = $teacher->is_subscribed ?? false;
  $subscription = $teacher->my_subscription ?? null;
  $teacherSubjects = collect($teacher->subject_ids ?? [])->map(function($id) use ($subjects) {
    return $subjects->firstWhere('id', $id);
  })->filter();
  $teacherGradeLevels = collect($teacher->grade_level_ids ?? [])->map(function($id) use ($gradeLevels) {
    return $gradeLevels->firstWhere('id', $id);
  })->filter();
@endphp

<div class="bg-white rounded-xl shadow-sm border border-gray-200 p-6 card-hover flex flex-col">
  <!-- Card Header -->
  <div class="flex items-center gap-3 mb-4">
    <!-- Teacher Avatar -->
    <x-avatar
      :user="$teacher"
      size="lg"
      userType="academic_teacher"
      :gender="$teacher->gender ?? 'male'"
      class="flex-shrink-0" />

    <!-- Name and Info -->
    <div class="flex-1 min-w-0">
      <!-- Name Row -->
      <div class="flex items-center justify-between gap-2 mb-2">
        <h3 class="font-bold text-gray-900 text-lg leading-tight">
          {{ $teacher->full_name ?? 'معلم أكاديمي' }}
        </h3>
        <!-- Status Badge -->
        @if($isSubscribed)
        <span class="inline-flex items-center px-2.5 py-1 rounded-full text-xs font-semibold bg-green-100 text-green-800 flex-shrink-0">
          <i class="ri-check-line ml-1"></i>
          معلمي
        </span>
        @endif
      </div>

      <!-- Info Row -->
      <div class="flex items-center justify-between gap-2">
        <div class="flex items-center gap-2 text-sm text-gray-600">
          @if($teacher->educational_qualification)
          <div class="flex items-center gap-1">
            <i class="ri-graduation-cap-line text-violet-600"></i>
            <span class="truncate">{{ \App\Enums\EducationalQualification::getLabel($teacher->educational_qualification) }}</span>
          </div>
          @endif
          @if($teacher->educational_qualification && $teacher->teaching_experience_years)
          <span class="text-gray-300">•</span>
          @endif
          @if($teacher->teaching_experience_years)
          <div class="flex items-center gap-1">
            <i class="ri-time-line text-violet-600"></i>
            <span>{{ $teacher->teaching_experience_years }} سنوات خبرة</span>
          </div>
          @endif
        </div>
        <!-- Rating and Students -->
        <div class="flex items-center gap-2 flex-shrink-0">
          <div class="flex items-center">
            <i class="ri-star-fill text-yellow-400 text-base"></i>
            <span class="text-sm font-semibold text-gray-700 mr-1">
              {{ number_format($teacher->average_rating ?? $teacher->rating ?? 4.8, 1) }}
            </span>
          </div>
          @if($teacher->total_students ?? 0)
          <span class="w-1 h-1 rounded-full bg-gray-300"></span>
          <span class="text-xs text-gray-600">{{ $teacher->total_students }} طالب</span>
          @endif
        </div>
      </div>
    </div>
  </div>

  <!-- Bio -->
  @if($teacher->bio_arabic || $teacher->bio_english)
  <p class="text-sm text-gray-600 mb-4 line-clamp-2 leading-relaxed">
    {{ $teacher->bio_arabic ?? $teacher->bio_english }}
  </p>
  @endif

  <!-- Details Grid -->
  <div class="mb-6 bg-gray-50 rounded-lg p-4">
    @if($isSubscribed && $subscription)
      <!-- Subscription Info for Subscribed Students -->
      <div class="space-y-3">
        <!-- Sessions Info -->
        <div class="flex items-center text-sm">
          <div class="w-8 h-8 bg-white rounded-lg flex items-center justify-center ml-3 shadow-sm">
            <i class="ri-video-line text-green-600"></i>
          </div>
          <div class="flex-1">
            <p class="text-xs text-gray-500 mb-0.5">الحصص</p>
            <p class="font-semibold text-gray-900">
              {{ $subscription->total_sessions_completed ?? 0 }} من {{ $subscription->total_sessions ?? 0 }} حصة
            </p>
          </div>
        </div>

        <!-- Progress -->
        <div class="flex items-center text-sm">
          <div class="w-8 h-8 bg-white rounded-lg flex items-center justify-center ml-3 shadow-sm">
            <i class="ri-calendar-check-line text-green-600"></i>
          </div>
          <div class="flex-1">
            <p class="text-xs text-gray-500 mb-0.5">التقدم</p>
            <div class="flex items-center gap-2">
              <div class="flex-1 bg-gray-200 rounded-full h-1.5">
                <div class="bg-green-600 h-1.5 rounded-full transition-all"
                     style="width: {{ $subscription->progress_percentage ?? 0 }}%"></div>
              </div>
              <span class="text-xs font-semibold text-gray-900">{{ $subscription->progress_percentage ?? 0 }}%</span>
            </div>
          </div>
        </div>

        <!-- Subject Info -->
        @if($subscription->subject_name)
        <div class="flex items-center text-sm">
          <div class="w-8 h-8 bg-white rounded-lg flex items-center justify-center ml-3 shadow-sm">
            <i class="ri-book-line text-green-600"></i>
          </div>
          <div class="flex-1">
            <p class="text-xs text-gray-500 mb-0.5">المادة</p>
            <p class="font-semibold text-gray-900">{{ $subscription->subject_name }}</p>
          </div>
        </div>
        @endif
      </div>
    @else
      <!-- Default Info for Non-subscribed -->
      <!-- Row 1: Subjects & Grade Levels (2 columns) -->
      <div class="grid grid-cols-2 gap-3 mb-3">
        <!-- Subjects -->
        @if($teacherSubjects->count() > 0)
      <div class="flex items-center text-sm">
        <div class="w-8 h-8 bg-white rounded-lg flex items-center justify-center ml-2 shadow-sm flex-shrink-0">
          <i class="ri-book-line text-violet-600"></i>
        </div>
        <div class="flex-1 min-w-0">
          <p class="text-xs text-gray-500 mb-0.5">المواد</p>
          <div class="flex flex-wrap gap-1">
            @if($teacherSubjects->count() > 1)
              <span class="inline-flex items-center px-1.5 py-0.5 rounded text-xs font-medium bg-violet-100 text-violet-800 truncate">
                {{ $teacherSubjects->first()->name }}
              </span>
              <span class="inline-flex items-center px-1.5 py-0.5 rounded text-xs font-medium bg-gray-100 text-gray-700">
                +{{ $teacherSubjects->count() - 1 }}
              </span>
            @else
              <span class="text-xs font-medium text-gray-900 truncate">{{ $teacherSubjects->first()->name }}</span>
            @endif
          </div>
        </div>
      </div>
      @endif

      <!-- Grade Levels -->
      @if($teacherGradeLevels->count() > 0)
      <div class="flex items-center text-sm">
        <div class="w-8 h-8 bg-white rounded-lg flex items-center justify-center ml-2 shadow-sm flex-shrink-0">
          <i class="ri-file-list-3-line text-violet-600"></i>
        </div>
        <div class="flex-1 min-w-0">
          <p class="text-xs text-gray-500 mb-0.5">المراحل</p>
          <div class="flex flex-wrap gap-1">
            @if($teacherGradeLevels->count() > 1)
              <span class="inline-flex items-center px-1.5 py-0.5 rounded text-xs font-medium bg-violet-100 text-violet-800 truncate">
                {{ $teacherGradeLevels->first()->name }}
              </span>
              <span class="inline-flex items-center px-1.5 py-0.5 rounded text-xs font-medium bg-gray-100 text-gray-700">
                +{{ $teacherGradeLevels->count() - 1 }}
              </span>
            @else
              <span class="text-xs font-medium text-gray-900 truncate">{{ $teacherGradeLevels->first()->name }}</span>
            @endif
          </div>
        </div>
      </div>
      @endif
      </div>

      <!-- Minimum Price (Full width with better styling) -->
      @if(!empty($teacher->minimum_price))
      <div class="pt-3 border-t border-gray-200">
        <div class="flex items-center justify-between">
          <div class="flex items-center gap-2 text-sm text-gray-600">
            <i class="ri-price-tag-3-line text-violet-600"></i>
            <span>يبدأ من</span>
          </div>
          <div class="flex items-baseline gap-1">
            <span class="text-2xl font-bold text-violet-600">{{ number_format($teacher->minimum_price) }}</span>
            <span class="text-sm text-violet-500">ر.س/شهر</span>
          </div>
        </div>
      </div>
      @endif
    @endif
  </div>

  <!-- Languages -->
  @if($teacher->languages && is_array($teacher->languages) && count($teacher->languages) > 0)
  <div class="mb-6">
    <div class="flex items-center gap-2">
      <span class="text-xs font-medium text-gray-500">اللغات:</span>
      <div class="flex flex-wrap gap-1.5">
        @foreach(array_slice($teacher->languages, 0, 3) as $lang)
        <span class="inline-flex items-center px-2 py-1 rounded-md text-xs font-medium bg-emerald-100 text-emerald-800">
          <i class="ri-global-line ml-1"></i>
          {{ $lang }}
        </span>
        @endforeach
        @if(count($teacher->languages) > 3)
        <span class="inline-flex items-center px-2 py-1 rounded-md text-xs font-medium bg-gray-100 text-gray-700">
          +{{ count($teacher->languages) - 3 }}
        </span>
        @endif
      </div>
    </div>
  </div>
  @endif

  <!-- Spacer to push buttons to bottom -->
  <div class="flex-grow"></div>

  <!-- Action Buttons -->
  <div class="flex items-center gap-2 mt-auto">
    <!-- View Profile Button (Violet - Always shown) -->
    <a href="{{ route('academic-teachers.show', ['subdomain' => $academy->subdomain ?? 'itqan-academy', 'teacherId' => $teacher->id]) }}"
       class="inline-block bg-violet-600 text-white px-5 py-3.5 rounded-lg text-sm font-semibold hover:bg-violet-700 transition-colors">
      <i class="ri-eye-line ml-1"></i>
      عرض الملف الشخصي
    </a>

    <!-- Additional Buttons for Subscribed Teachers -->
    @if($isSubscribed && $subscription)
      <!-- Open Subscription Button (Subtle Violet) -->
      <a href="{{ route('student.academic-subscriptions.show', ['subdomain' => $academy->subdomain ?? 'itqan-academy', 'subscriptionId' => $subscription->id]) }}"
         class="inline-block px-5 py-3.5 bg-violet-50 border-2 border-violet-200 rounded-lg text-sm font-semibold text-violet-700 hover:bg-violet-100 transition-colors">
        <i class="ri-book-open-line ml-1"></i>
        فتح الدرس
      </a>

      <!-- Chat Button -->
      @if($teacher->user)
        @php $conv = auth()->user()->getOrCreatePrivateConversation($teacher->user); @endphp
        @if($conv)
        <a href="{{ route('chat', ['conversation' => $conv->id]) }}"
           class="inline-flex items-center justify-center px-5 bg-green-50 border-2 border-green-200 rounded-lg text-green-700 hover:bg-green-100 transition-colors"
           style="height: 52px;">
          <i class="ri-message-3-line text-xl"></i>
        </a>
        @endif
      @endif
    @endif
  </div>
</div>
