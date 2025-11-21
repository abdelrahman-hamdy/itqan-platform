@php
  $academy = auth()->user()->academy;
@endphp

<x-student title="{{ $academy->name ?? 'أكاديمية إتقان' }} - المعلمون الأكاديميون">
  <x-slot name="description">استكشف المعلمين الأكاديميين المتاحين - {{ $academy->name ?? 'أكاديمية إتقان' }}</x-slot>

  <!-- Header Section -->
  <div class="mb-8">
    <div class="flex items-center justify-between flex-wrap gap-4">
      <div>
        <h1 class="text-3xl font-bold text-gray-900 mb-2">
          المعلمون الأكاديميون
        </h1>
        <p class="text-gray-600">
          اختر من بين نخبة من المعلمين المتخصصين في المواد الأكاديمية للحصول على دروس خاصة
        </p>
      </div>
      <div class="bg-white rounded-lg px-6 py-3 border border-gray-200 shadow-sm">
        <span class="text-sm text-gray-600">معلميني الحاليين: </span>
        <span class="font-bold text-2xl text-violet-600">{{ $activeSubscriptionsCount }}</span>
      </div>
    </div>
  </div>

  <!-- Filters Section -->
  <x-filters.academic-filters
    :route="route('student.academic-teachers', ['subdomain' => $academy->subdomain ?? 'itqan-academy'])"
    :subjects="$subjects"
    :gradeLevels="$gradeLevels"
    :showSearch="true"
    :showSubjects="true"
    :showGradeLevels="true"
    :showExperience="false"
    :showGender="true"
    color="violet"
  />

  <!-- Results Summary -->
  <div class="mb-6 flex items-center justify-between">
    <p class="text-gray-600">
      <span class="font-semibold text-gray-900">{{ $academicTeachers->total() }}</span>
      معلم متاح
    </p>
    @if($academicTeachers->total() > 0)
    <p class="text-sm text-gray-500">
      عرض {{ $academicTeachers->firstItem() }} - {{ $academicTeachers->lastItem() }} من {{ $academicTeachers->total() }}
    </p>
    @endif
  </div>

  <!-- Teachers Grid -->
  @if($academicTeachers->count() > 0)
  <div class="grid grid-cols-1 md:grid-cols-2 gap-6 mb-8">
    @foreach($academicTeachers as $teacher)
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
      </div>

      <!-- Languages -->
      @if($teacher->languages && is_array($teacher->languages) && count($teacher->languages) > 0)
      <div class="mb-6">
        <p class="text-xs font-medium text-gray-500 mb-2">اللغات</p>
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
      @endif

      <!-- Spacer to push buttons to bottom -->
      <div class="flex-grow"></div>

      <!-- Action Buttons -->
      <div class="flex items-center gap-2 mt-auto">
        <!-- View Profile Button (Violet - Always shown) -->
        <a href="{{ route('public.academic-teachers.show', ['subdomain' => $academy->subdomain ?? 'itqan-academy', 'teacher' => $teacher->id]) }}"
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
    @endforeach
  </div>

  <!-- Custom Pagination -->
  @if($academicTeachers->hasPages())
  <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-6">
    <div class="flex flex-col sm:flex-row items-center justify-between gap-4">
      <!-- Page Info -->
      <div class="text-sm text-gray-600">
        صفحة <span class="font-semibold text-gray-900">{{ $academicTeachers->currentPage() }}</span>
        من <span class="font-semibold text-gray-900">{{ $academicTeachers->lastPage() }}</span>
      </div>

      <!-- Pagination Links -->
      <div class="flex items-center gap-2">
        <!-- Previous Button -->
        @if($academicTeachers->onFirstPage())
        <span class="px-4 py-2 bg-gray-100 text-gray-400 rounded-lg text-sm font-medium cursor-not-allowed">
          <i class="ri-arrow-right-s-line"></i>
          السابق
        </span>
        @else
        <a href="{{ $academicTeachers->previousPageUrl() }}"
           class="px-4 py-2 bg-white border border-gray-300 text-gray-700 rounded-lg text-sm font-medium hover:bg-gray-50 hover:border-violet-500 hover:text-violet-600 transition-colors">
          <i class="ri-arrow-right-s-line"></i>
          السابق
        </a>
        @endif

        <!-- Page Numbers -->
        <div class="hidden sm:flex items-center gap-1">
          @php
            $start = max(1, $academicTeachers->currentPage() - 2);
            $end = min($academicTeachers->lastPage(), $academicTeachers->currentPage() + 2);
          @endphp

          @if($start > 1)
          <a href="{{ $academicTeachers->url(1) }}"
             class="w-10 h-10 flex items-center justify-center border border-gray-300 rounded-lg text-sm font-medium hover:bg-gray-50 hover:border-violet-500 hover:text-violet-600 transition-colors">
            1
          </a>
          @if($start > 2)
          <span class="px-2 text-gray-400">...</span>
          @endif
          @endif

          @for($i = $start; $i <= $end; $i++)
          @if($i == $academicTeachers->currentPage())
          <span class="w-10 h-10 flex items-center justify-center bg-violet-600 text-white rounded-lg text-sm font-bold shadow-sm">
            {{ $i }}
          </span>
          @else
          <a href="{{ $academicTeachers->url($i) }}"
             class="w-10 h-10 flex items-center justify-center border border-gray-300 rounded-lg text-sm font-medium hover:bg-gray-50 hover:border-violet-500 hover:text-violet-600 transition-colors">
            {{ $i }}
          </a>
          @endif
          @endfor

          @if($end < $academicTeachers->lastPage())
          @if($end < $academicTeachers->lastPage() - 1)
          <span class="px-2 text-gray-400">...</span>
          @endif
          <a href="{{ $academicTeachers->url($academicTeachers->lastPage()) }}"
             class="w-10 h-10 flex items-center justify-center border border-gray-300 rounded-lg text-sm font-medium hover:bg-gray-50 hover:border-violet-500 hover:text-violet-600 transition-colors">
            {{ $academicTeachers->lastPage() }}
          </a>
          @endif
        </div>

        <!-- Next Button -->
        @if($academicTeachers->hasMorePages())
        <a href="{{ $academicTeachers->nextPageUrl() }}"
           class="px-4 py-2 bg-white border border-gray-300 text-gray-700 rounded-lg text-sm font-medium hover:bg-gray-50 hover:border-violet-500 hover:text-violet-600 transition-colors">
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
        {{ $academicTeachers->count() }} من أصل {{ $academicTeachers->total() }} معلم
      </div>
    </div>
  </div>
  @endif

  @else
  <!-- Empty State -->
  <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-12 text-center">
    <div class="w-24 h-24 bg-gradient-to-br from-gray-100 to-gray-200 rounded-full flex items-center justify-center mx-auto mb-6 shadow-inner">
      <i class="ri-graduation-cap-line text-gray-400 text-4xl"></i>
    </div>
    <h3 class="text-xl font-bold text-gray-900 mb-3">لا يوجد معلمون متاحون</h3>
    <p class="text-gray-600 mb-6 max-w-md mx-auto">
      @if(request()->hasAny(['search', 'subject', 'grade_level', 'gender']))
        لم نجد معلمين يطابقون معايير البحث. جرّب تعديل الفلاتر.
      @else
        لا يوجد معلمون أكاديميون متاحون حالياً. ستتم إضافة معلمين جدد قريباً.
      @endif
    </p>
    <div class="flex items-center justify-center gap-3">
      @if(request()->hasAny(['search', 'subject', 'grade_level', 'gender']))
      <a href="{{ route('student.academic-teachers', ['subdomain' => $academy->subdomain ?? 'itqan-academy']) }}"
         class="inline-flex items-center px-6 py-3 bg-violet-600 text-white rounded-lg hover:bg-violet-700 transition-colors shadow-sm font-medium">
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
