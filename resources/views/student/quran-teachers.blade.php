@php
  $academy = auth()->user()->academy;
@endphp

<x-student title="{{ $academy->name ?? 'أكاديمية إتقان' }} - معلمو القرآن الكريم">
  <x-slot name="description">استكشف معلمي القرآن الكريم المتاحين - {{ $academy->name ?? 'أكاديمية إتقان' }}</x-slot>

  <!-- Header Section -->
  <div class="mb-8">
    <div class="flex items-center justify-between flex-wrap gap-4">
      <div>
        <h1 class="text-3xl font-bold text-gray-900 mb-2">
          معلمو القرآن الكريم
        </h1>
        <p class="text-gray-600">
          اختر من بين نخبة من معلمي القرآن الكريم المؤهلين للحصول على دروس خاصة
        </p>
      </div>
      <div class="bg-white rounded-lg px-6 py-3 border border-gray-200 shadow-sm">
        <span class="text-sm text-gray-600">معلميني الحاليين: </span>
        <span class="font-bold text-2xl text-yellow-600">{{ $activeSubscriptionsCount }}</span>
      </div>
    </div>
  </div>

  <!-- Filters Section -->
  <x-filters.quran-filters
    :route="route('student.quran-teachers', ['subdomain' => $academy->subdomain ?? 'itqan-academy'])"
    :showSearch="true"
    :showExperience="true"
    :showGender="true"
    :showDays="true"
    color="yellow"
  />

  <!-- Results Summary -->
  <div class="mb-6 flex items-center justify-between">
    <p class="text-gray-600">
      <span class="font-semibold text-gray-900">{{ $quranTeachers->total() }}</span>
      معلم متاح
    </p>
    @if($quranTeachers->total() > 0)
    <p class="text-sm text-gray-500">
      عرض {{ $quranTeachers->firstItem() }} - {{ $quranTeachers->lastItem() }} من {{ $quranTeachers->total() }}
    </p>
    @endif
  </div>

  <!-- Teachers Grid -->
  @if($quranTeachers->count() > 0)
  <div class="grid grid-cols-1 md:grid-cols-2 gap-6 mb-8">
    @foreach($quranTeachers as $teacher)
    @php
      $isSubscribed = $teacher->is_subscribed;
      $subscription = $teacher->my_subscription;
    @endphp

    <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-6 card-hover flex flex-col">
      <!-- Card Header -->
      <div class="flex items-center gap-3 mb-4">
        <!-- Teacher Avatar -->
        <x-avatar
          :user="$teacher"
          size="lg"
          userType="quran_teacher"
          :gender="$teacher->gender ?? $teacher->user?->gender ?? 'male'"
          class="flex-shrink-0" />

        <!-- Name and Info -->
        <div class="flex-1 min-w-0">
          <!-- Name Row -->
          <div class="flex items-center justify-between gap-2 mb-2">
            <h3 class="font-bold text-gray-900 text-lg leading-tight">
              {{ $teacher->user->full_name ?? $teacher->user->name ?? 'معلم قرآن' }}
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
                <i class="ri-graduation-cap-line text-yellow-600"></i>
                <span class="truncate">{{ \App\Enums\EducationalQualification::getLabel($teacher->educational_qualification) }}</span>
              </div>
              @endif
              @if($teacher->educational_qualification && $teacher->teaching_experience_years)
              <span class="text-gray-300">•</span>
              @endif
              @if($teacher->teaching_experience_years)
              <div class="flex items-center gap-1">
                <i class="ri-time-line text-yellow-600"></i>
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
              @if($teacher->active_students_count)
              <span class="w-1 h-1 rounded-full bg-gray-300"></span>
              <span class="text-xs text-gray-600">{{ $teacher->active_students_count }} طالب</span>
              @endif
            </div>
          </div>
        </div>
      </div>

      <!-- Bio -->
      @if($teacher->bio || $teacher->bio_arabic)
      <p class="text-sm text-gray-600 mb-4 line-clamp-2 leading-relaxed">
        {{ $teacher->bio ?? $teacher->bio_arabic }}
      </p>
      @endif

      <!-- Details Grid -->
      <div class="space-y-3 mb-6 bg-gray-50 rounded-lg p-4">
        @if($isSubscribed && $subscription)
          <!-- Sessions Info -->
          <div class="flex items-center text-sm">
            <div class="w-8 h-8 bg-white rounded-lg flex items-center justify-center ml-3 shadow-sm">
              <i class="ri-video-line text-green-600"></i>
            </div>
            <div class="flex-1">
              <p class="text-xs text-gray-500 mb-0.5">الجلسات</p>
              <p class="font-semibold text-gray-900">
                {{ $subscription->sessions_attended ?? 0 }} من {{ $subscription->total_sessions ?? 0 }} جلسة
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
        @else
          <!-- Teaching Times -->
          @if($teacher->available_days && is_array($teacher->available_days) && count($teacher->available_days) > 0)
          <div class="flex items-center text-sm">
            <div class="w-8 h-8 bg-white rounded-lg flex items-center justify-center ml-3 shadow-sm">
              <i class="ri-calendar-line text-yellow-600"></i>
            </div>
            <div class="flex-1">
              <p class="text-xs text-gray-500 mb-0.5">أوقات التدريس</p>
              <p class="font-semibold text-gray-900">
                @php
                  $displayDays = array_slice($teacher->available_days, 0, 3);
                  $dayNames = collect($displayDays)->map(function($day) {
                    try {
                      return \App\Enums\WeekDays::from($day)->label();
                    } catch (\ValueError $e) {
                      return $day;
                    }
                  })->join(' • ');
                @endphp
                {{ $dayNames }}
                @if(count($teacher->available_days) > 3)
                <span class="text-xs text-gray-500">+{{ count($teacher->available_days) - 3 }}</span>
                @endif
                @if($teacher->available_time_start && $teacher->available_time_end)
                <span class="text-xs text-gray-600 mr-1">• {{ formatTimeArabic($teacher->available_time_start) }} - {{ formatTimeArabic($teacher->available_time_end) }}</span>
                @endif
              </p>
            </div>
          </div>
          @endif

          <!-- Lowest Price -->
          @if($availablePackages->count() > 0)
          <div class="flex items-center text-sm">
            <div class="w-8 h-8 bg-white rounded-lg flex items-center justify-center ml-3 shadow-sm">
              <i class="ri-money-dollar-circle-line text-yellow-600"></i>
            </div>
            <div class="flex-1">
              <p class="text-xs text-gray-500 mb-0.5">الأسعار</p>
              <p class="font-bold text-yellow-600">من {{ number_format($availablePackages->min('monthly_price'), 0) }} ر.س/شهر</p>
            </div>
          </div>
          @endif
        @endif
      </div>

      <!-- Certifications -->
      @if($teacher->certifications && is_array($teacher->certifications) && count($teacher->certifications) > 0)
      <div class="mb-6">
        <p class="text-xs font-medium text-gray-500 mb-2">الشهادات والإجازات</p>
        <div class="flex flex-wrap gap-1.5">
          @foreach(array_slice($teacher->certifications, 0, 3) as $cert)
          <span class="inline-flex items-center px-2 py-1 rounded-md text-xs font-medium bg-emerald-100 text-emerald-800">
            <i class="ri-award-line ml-1"></i>
            {{ Str::limit($cert, 25) }}
          </span>
          @endforeach
          @if(count($teacher->certifications) > 3)
          <span class="inline-flex items-center px-2 py-1 rounded-md text-xs font-medium bg-gray-100 text-gray-700">
            +{{ count($teacher->certifications) - 3 }}
          </span>
          @endif
        </div>
      </div>
      @endif

      <!-- Spacer to push buttons to bottom -->
      <div class="flex-grow"></div>

      <!-- Action Buttons -->
      <div class="flex items-center gap-2 mt-auto">
        <!-- View Profile Button (Yellow - Always shown) -->
        <a href="{{ route('public.quran-teachers.show', ['subdomain' => $academy->subdomain ?? 'itqan-academy', 'teacher' => $teacher->id]) }}"
           class="inline-block bg-yellow-600 text-white px-5 py-3.5 rounded-lg text-sm font-semibold hover:bg-yellow-700 transition-colors">
          <i class="ri-eye-line ml-1"></i>
          عرض الملف الشخصي
        </a>

        <!-- Additional Buttons for Subscribed Teachers -->
        @if($isSubscribed && $subscription && $subscription->individualCircle)
          <!-- Open Circle Button (Subtle Yellow) -->
          <a href="{{ route('individual-circles.show', ['subdomain' => $academy->subdomain ?? 'itqan-academy', 'circle' => $subscription->individualCircle->id]) }}"
             class="inline-block px-5 py-3.5 bg-yellow-50 border-2 border-yellow-200 rounded-lg text-sm font-semibold text-yellow-700 hover:bg-yellow-100 transition-colors">
            <i class="ri-book-open-line ml-1"></i>
            فتح الحلقة
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
    @endforeach
  </div>

  <!-- Custom Pagination -->
  @if($quranTeachers->hasPages())
  <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-6">
    <div class="flex flex-col sm:flex-row items-center justify-between gap-4">
      <!-- Page Info -->
      <div class="text-sm text-gray-600">
        صفحة <span class="font-semibold text-gray-900">{{ $quranTeachers->currentPage() }}</span>
        من <span class="font-semibold text-gray-900">{{ $quranTeachers->lastPage() }}</span>
      </div>

      <!-- Pagination Links -->
      <div class="flex items-center gap-2">
        <!-- Previous Button -->
        @if($quranTeachers->onFirstPage())
        <span class="px-4 py-2 bg-gray-100 text-gray-400 rounded-lg text-sm font-medium cursor-not-allowed">
          <i class="ri-arrow-right-s-line"></i>
          السابق
        </span>
        @else
        <a href="{{ $quranTeachers->previousPageUrl() }}"
           class="px-4 py-2 bg-white border border-gray-300 text-gray-700 rounded-lg text-sm font-medium hover:bg-gray-50 hover:border-yellow-500 hover:text-yellow-600 transition-colors">
          <i class="ri-arrow-right-s-line"></i>
          السابق
        </a>
        @endif

        <!-- Page Numbers -->
        <div class="hidden sm:flex items-center gap-1">
          @php
            $start = max(1, $quranTeachers->currentPage() - 2);
            $end = min($quranTeachers->lastPage(), $quranTeachers->currentPage() + 2);
          @endphp

          @if($start > 1)
          <a href="{{ $quranTeachers->url(1) }}"
             class="w-10 h-10 flex items-center justify-center border border-gray-300 rounded-lg text-sm font-medium hover:bg-gray-50 hover:border-yellow-500 hover:text-yellow-600 transition-colors">
            1
          </a>
          @if($start > 2)
          <span class="px-2 text-gray-400">...</span>
          @endif
          @endif

          @for($i = $start; $i <= $end; $i++)
          @if($i == $quranTeachers->currentPage())
          <span class="w-10 h-10 flex items-center justify-center bg-yellow-600 text-white rounded-lg text-sm font-bold shadow-sm">
            {{ $i }}
          </span>
          @else
          <a href="{{ $quranTeachers->url($i) }}"
             class="w-10 h-10 flex items-center justify-center border border-gray-300 rounded-lg text-sm font-medium hover:bg-gray-50 hover:border-yellow-500 hover:text-yellow-600 transition-colors">
            {{ $i }}
          </a>
          @endif
          @endfor

          @if($end < $quranTeachers->lastPage())
          @if($end < $quranTeachers->lastPage() - 1)
          <span class="px-2 text-gray-400">...</span>
          @endif
          <a href="{{ $quranTeachers->url($quranTeachers->lastPage()) }}"
             class="w-10 h-10 flex items-center justify-center border border-gray-300 rounded-lg text-sm font-medium hover:bg-gray-50 hover:border-yellow-500 hover:text-yellow-600 transition-colors">
            {{ $quranTeachers->lastPage() }}
          </a>
          @endif
        </div>

        <!-- Next Button -->
        @if($quranTeachers->hasMorePages())
        <a href="{{ $quranTeachers->nextPageUrl() }}"
           class="px-4 py-2 bg-white border border-gray-300 text-gray-700 rounded-lg text-sm font-medium hover:bg-gray-50 hover:border-yellow-500 hover:text-yellow-600 transition-colors">
          التالي
          <i class="ri-arrow-left-s-line"></i>
        </a>
        @else
        <span class="px-4 py-2 bg-gray-100 text-gray-400 rounded-lg text-sm font-medium cursor-not-allowed">
          التالي
          <i class="ri-arrow-left-s-line"></i>
        </span>
        @endif
      </div>

      <!-- Per Page Info -->
      <div class="text-sm text-gray-500">
        {{ $quranTeachers->count() }} من أصل {{ $quranTeachers->total() }} معلم
      </div>
    </div>
  </div>
  @endif

  @else
  <!-- Empty State -->
  <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-12 text-center">
    <div class="w-24 h-24 bg-gradient-to-br from-gray-100 to-gray-200 rounded-full flex items-center justify-center mx-auto mb-6 shadow-inner">
      <i class="ri-user-star-line text-gray-400 text-4xl"></i>
    </div>
    <h3 class="text-xl font-bold text-gray-900 mb-3">لا يوجد معلمون متاحون</h3>
    <p class="text-gray-600 mb-6 max-w-md mx-auto">
      @if(request()->hasAny(['search', 'experience', 'gender', 'schedule_days']))
        لم نجد معلمين يطابقون معايير البحث. جرّب تعديل الفلاتر.
      @else
        لا يوجد معلمو قرآن كريم متاحون حالياً. ستتم إضافة معلمين جدد قريباً.
      @endif
    </p>
    <div class="flex items-center justify-center gap-3">
      @if(request()->hasAny(['search', 'experience', 'gender', 'schedule_days']))
      <a href="{{ route('student.quran-teachers', ['subdomain' => $academy->subdomain ?? 'itqan-academy']) }}"
         class="inline-flex items-center px-6 py-3 bg-yellow-600 text-white rounded-lg hover:bg-yellow-700 transition-colors shadow-sm font-medium">
        <i class="ri-refresh-line ml-2"></i>
        إعادة تعيين الفلاتر
      </a>
      @endif
      <a href="{{ route('student.profile', ['subdomain' => $academy->subdomain ?? 'itqan-academy']) }}"
         class="inline-flex items-center px-6 py-3 border-2 border-gray-300 text-gray-700 rounded-lg hover:bg-gray-50 transition-colors font-medium">
        <i class="ri-arrow-right-line ml-2"></i>
        العودة للملف الشخصي
      </a>
    </div>
  </div>
  @endif

</x-student>
