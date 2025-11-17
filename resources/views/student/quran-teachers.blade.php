<x-student title="{{ auth()->user()->academy->name ?? 'أكاديمية إتقان' }} - معلمو القرآن الكريم">
  <x-slot name="description">استكشف معلمي القرآن الكريم المتاحين - {{ auth()->user()->academy->name ?? 'أكاديمية إتقان' }}</x-slot>
      
      <!-- Header Section -->
      <div class="mb-8">
        <div class="flex items-center justify-between">
          <div>
            <h1 class="text-3xl font-bold text-gray-900 mb-2">
              <i class="ri-user-star-line text-primary ml-2"></i>
              معلمو القرآن الكريم
            </h1>
            <p class="text-gray-600">
              اختر من بين نخبة من معلمي القرآن الكريم المؤهلين للحصول على دروس خاصة
            </p>
          </div>
          <div class="flex items-center space-x-4 space-x-reverse">
            <div class="bg-white rounded-lg px-4 py-2 border border-gray-200">
              <span class="text-sm text-gray-600">معلميني الحاليين: </span>
              <span class="font-semibold text-primary">{{ $activeSubscriptions->count() }}</span>
            </div>
          </div>
        </div>
      </div>

      @if($activeSubscriptions->count() > 0)
      <!-- My Current Teachers -->
      <div class="mb-12">
        <h2 class="text-2xl font-bold text-gray-900 mb-6">معلميني الحاليين</h2>
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
          @foreach($activeSubscriptions as $subscription)
          <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-6 card-hover">
            <!-- Header with Avatar beside Name and Status -->
            <div class="flex items-start gap-4 mb-4">
              <!-- Teacher Avatar -->
              <x-teacher-avatar :teacher="$subscription->quranTeacher" size="md" class="flex-shrink-0" />
              
              <!-- Name and Description -->
              <div class="flex-1 min-w-0">
                <h3 class="font-semibold text-gray-900 mb-1 truncate">
                  {{ $subscription->quranTeacher->user->full_name ?? $subscription->quranTeacher->user->name ?? 'معلم قرآن' }}
                </h3>
                <p class="text-sm text-gray-600 line-clamp-2">{{ $subscription->quranTeacher->bio ?? $subscription->quranTeacher->bio_arabic ?? 'معلم قرآن كريم' }}</p>
              </div>
              
              <!-- Status Badge -->
              <div class="flex-shrink-0">
                <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium 
                  {{ $subscription->subscription_status === 'active' ? 'bg-green-100 text-green-800' : 'bg-yellow-100 text-yellow-800' }}">
                  {{ $subscription->subscription_status === 'active' ? 'نشط' : 'في الانتظار' }}
                </span>
              </div>
            </div>
            
            <div class="space-y-2">
              <div class="flex items-center text-sm text-gray-600">
                <i class="ri-book-line ml-2"></i>
                <span>{{ $subscription->package->name ?? 'حزمة مخصصة' }}</span>
              </div>
              <div class="flex items-center text-sm text-gray-600">
                <i class="ri-calendar-line ml-2"></i>
                <span>التقدم: {{ $subscription->progress_percentage }}%</span>
              </div>
              <div class="flex items-center text-sm text-gray-600">
                <i class="ri-time-line ml-2"></i>
                <span>{{ $subscription->sessions_used ?? 0 }} جلسة مكتملة</span>
              </div>
            </div>

            <!-- Single Action Button -->
            <div class="mt-6">
              <a href="{{ route('individual-circles.show', ['subdomain' => auth()->user()->academy->subdomain ?? 'itqan-academy', 'circle' => $subscription->individualCircle->id]) }}" 
                 class="w-full bg-primary text-white px-4 py-3 rounded-lg text-sm font-medium hover:bg-secondary transition-colors text-center inline-block">
                <i class="ri-book-open-line ml-2"></i>
                عرض حلقة القرآن
              </a>
            </div>
          </div>
          @endforeach
        </div>
      </div>
      @endif

      <!-- Available Teachers -->
      <div>
        <div class="flex items-center justify-between mb-6">
          <h2 class="text-2xl font-bold text-gray-900">معلمون متاحون</h2>
          <div class="flex items-center space-x-4 space-x-reverse">
            <select class="border border-gray-300 rounded-lg px-3 py-2 text-sm">
              <option>جميع التخصصات</option>
              <option>حفظ القرآن</option>
              <option>التجويد</option>
              <option>التفسير</option>
            </select>
            <select class="border border-gray-300 rounded-lg px-3 py-2 text-sm">
              <option>ترتيب حسب: الأحدث</option>
              <option>الأعلى تقييماً</option>
              <option>الأقل سعراً</option>
            </select>
          </div>
        </div>

        @if($quranTeachers->count() > 0)
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
          @foreach($quranTeachers as $teacher)
          <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-6 card-hover">
            <div class="flex items-start justify-between mb-4">
              <x-teacher-avatar :teacher="$teacher" size="sm" class="flex-shrink-0" />
              <div class="flex items-center">
                <i class="ri-star-fill text-yellow-400 text-sm"></i>
                <span class="text-sm text-gray-600 mr-1">{{ number_format($teacher->average_rating ?? $teacher->rating ?? 4.8, 1) }}</span>
              </div>
            </div>
            
            <h3 class="font-semibold text-gray-900 mb-2">
              {{ $teacher->user->name ?? $teacher->full_name }}
            </h3>
            <p class="text-sm text-gray-600 mb-4">{{ $teacher->bio_arabic ?? 'معلم قرآن كريم مؤهل' }}</p>
            
            <div class="space-y-2">
              @if($teacher->teaching_experience_years)
              <div class="flex items-center text-sm text-gray-600">
                <i class="ri-time-line ml-2"></i>
                <span>{{ $teacher->teaching_experience_years }} سنوات خبرة</span>
              </div>
              @endif
              @if($teacher->active_students_count)
              <div class="flex items-center text-sm text-gray-600">
                <i class="ri-group-line ml-2"></i>
                <span>{{ $teacher->active_students_count }} طالب نشط</span>
              </div>
              @endif
              <div class="flex items-center text-sm text-gray-600">
                <i class="ri-video-line ml-2"></i>
                <span>{{ $teacher->total_sessions ?? 0 }} جلسة مكتملة</span>
              </div>
              @if($availablePackages->count() > 0)
              <div class="flex items-center text-sm text-gray-600">
                <i class="ri-money-dollar-circle-line ml-2"></i>
                <span>من {{ $availablePackages->min('monthly_price') }} ر.س/شهر</span>
              </div>
              @endif
            </div>

            <!-- Qualifications -->
            @if($teacher->educational_qualification)
            <div class="mt-4">
              <div class="flex flex-wrap gap-1">
                <span class="inline-flex items-center px-2 py-1 rounded-full text-xs font-medium bg-green-100 text-green-800">
                  <i class="ri-graduation-cap-line ml-1"></i>
                  {{ $teacher->educational_qualification }}
                </span>
                @if($teacher->certifications && is_array($teacher->certifications))
                  @foreach($teacher->certifications as $cert)
                    <span class="inline-flex items-center px-2 py-1 rounded-full text-xs font-medium bg-blue-100 text-blue-800">
                      {{ $cert }}
                    </span>
                  @endforeach
                @endif
              </div>
            </div>
            @endif

            <div class="mt-6 flex space-x-2 space-x-reverse">
              <a href="{{ route('public.quran-teachers.show', ['subdomain' => auth()->user()->academy->subdomain ?? 'itqan-academy', 'teacher' => $teacher->id]) }}" 
                 class="flex-1 bg-primary text-white px-4 py-3 rounded-lg text-sm font-medium hover:bg-secondary transition-colors text-center inline-block">
                <i class="ri-eye-line ml-2"></i>
                عرض الملف الشخصي
              </a>
              @php
                $isRegisteredWithTeacher = $activeSubscriptions->where('quran_teacher_id', $teacher->id)->where('subscription_status', 'active')->count() > 0;
              @endphp
              @if($teacher->user && $isRegisteredWithTeacher)
              <a href="{{ route('chat', ['subdomain' => auth()->user()->academy->subdomain ?? 'itqan-academy', 'user' => $teacher->user->id]) }}"
                 class="px-3 py-3 bg-green-50 border border-green-200 rounded-lg text-sm text-green-700 hover:bg-green-100 transition-colors">
                <i class="ri-message-3-line"></i>
              </a>
              @endif
            </div>
          </div>
          @endforeach
        </div>

        <!-- Pagination -->
        <div class="mt-8">
          {{ $quranTeachers->links() }}
        </div>
        @else
        <!-- Empty State -->
        <div class="text-center py-12">
          <div class="w-24 h-24 bg-gray-100 rounded-full flex items-center justify-center mx-auto mb-4">
            <i class="ri-user-star-line text-gray-400 text-3xl"></i>
          </div>
          <h3 class="text-lg font-semibold text-gray-900 mb-2">لا يوجد معلمون متاحون حالياً</h3>
          <p class="text-gray-600 mb-6">ستتم إضافة معلمين جدد قريباً. تابع معنا للحصول على التحديثات</p>
          <a href="{{ route('student.profile', ['subdomain' => auth()->user()->academy->subdomain ?? 'itqan-academy']) }}" 
             class="inline-flex items-center px-4 py-2 bg-primary text-white rounded-lg hover:bg-secondary transition-colors">
            <i class="ri-arrow-right-line ml-2"></i>
            العودة للملف الشخصي
          </a>
        </div>
        @endif
      </div>

</x-student>