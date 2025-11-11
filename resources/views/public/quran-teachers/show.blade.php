<x-layouts.student 
  title="معلم القرآن الكريم - {{ $teacher->full_name }}" 
  description="تعلم القرآن الكريم مع الأستاذ {{ $teacher->full_name }} - معلم مؤهل ومعتمد في {{ auth()->user()->academy->name ?? 'أكاديمية إتقان' }}">

  <!-- Breadcrumb -->
  <nav class="mb-8">
    <ol class="flex items-center space-x-2 space-x-reverse text-sm text-gray-600">
      <li><a href="{{ route('student.profile', ['subdomain' => auth()->user()->academy->subdomain ?? 'itqan-academy']) }}" class="hover:text-primary">ملفي الشخصي</a></li>
      <li>/</li>
      <li><a href="{{ route('student.quran-teachers', ['subdomain' => auth()->user()->academy->subdomain ?? 'itqan-academy']) }}" class="hover:text-primary">معلمو القرآن الكريم</a></li>
      <li>/</li>
      <li class="text-gray-900">{{ $teacher->full_name }}</li>
    </ol>
  </nav>

  <!-- Success Messages -->
  @if (session('success'))
    <div class="bg-green-50 border border-green-200 text-green-700 px-4 py-3 rounded-lg mb-6">
      <div class="flex">
        <i class="ri-check-line text-green-500 mt-0.5 ml-2"></i>
        <div>{{ session('success') }}</div>
      </div>
    </div>
  @endif

  <!-- Error Messages -->
  @if (session('error'))
    <div class="bg-red-50 border border-red-200 text-red-700 px-4 py-3 rounded-lg mb-6">
      <div class="flex">
        <i class="ri-error-warning-line text-red-500 mt-0.5 ml-2"></i>
        <div>{{ session('error') }}</div>
      </div>
    </div>
  @endif

  <!-- Teacher Profile Header Section -->
  <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-8 mb-8">
    <div class="flex flex-col lg:flex-row items-start gap-8">
      
      <!-- Teacher Avatar -->
      <x-teacher-avatar :teacher="$teacher" size="xl" />

      <!-- Teacher Info -->
      <div class="flex-1">
        <div class="grid grid-cols-1 lg:grid-cols-2 gap-8">
          
          <!-- Basic Info -->
          <div>
            <h1 class="text-3xl lg:text-4xl font-bold text-gray-900 mb-2">{{ $teacher->full_name }}</h1>
            <p class="text-xl text-primary font-medium mb-6">معلم القرآن الكريم المعتمد</p>

            <!-- Stats - Side by Side Layout -->
            <div class="flex items-center gap-8">
              <!-- Rating Section -->
              <div class="flex items-center">
                <i class="ri-star-line text-yellow-500 text-2xl ml-2"></i>
                <div>
                  @if($teacher->rating && $teacher->rating > 0)
                    <div class="text-2xl font-bold text-gray-900">{{ number_format($teacher->rating, 1) }}</div>
                    <div class="text-sm text-gray-600">تقييم المعلم</div>
                  @else
                    <div class="text-2xl font-bold text-gray-400">-</div>
                    <div class="text-sm text-gray-500">لا يوجد تقييم</div>
                  @endif
                </div>
              </div>
              
              <!-- Students Count -->
              <div class="flex items-center">
                <i class="ri-group-line text-green-600 text-2xl ml-2"></i>
                <div>
                  <div class="text-2xl font-bold text-gray-900 mb-1">{{ $stats['total_students'] }}</div>
                  <div class="text-sm text-gray-600">طالب</div>
                </div>
              </div>
            </div>
          </div>

          <!-- Qualifications -->
          <div>
            <h3 class="text-lg font-bold text-gray-900 mb-4">
              <i class="ri-award-line text-primary ml-2"></i>
              المؤهلات والشهادات
            </h3>
            <div class="space-y-3">
              <!-- Educational Qualification -->
              @if($teacher->educational_qualification)
                <div class="flex items-center">
                  <i class="ri-check-line text-green-500 ml-3"></i>
                  <span class="text-gray-700">{{ $teacher->educational_qualification }}</span>
                </div>
              @endif

              <!-- Certifications -->
              @if($teacher->certifications && count($teacher->certifications) > 0)
                @foreach($teacher->certifications as $certification)
                  <div class="flex items-center">
                    <i class="ri-check-line text-green-500 ml-3"></i>
                    <span class="text-gray-700">{{ $certification }}</span>
                  </div>
                @endforeach
              @endif

              <!-- Experience -->
              <div class="flex items-center">
                <i class="ri-check-line text-green-500 ml-3"></i>
                <span class="text-gray-700">{{ $teacher->teaching_experience_years ?? 0 }} سنوات من الخبرة في التدريس</span>
              </div>

              <!-- Languages -->
              @if($teacher->languages && count($teacher->languages) > 0)
                <div class="flex items-center">
                  <i class="ri-check-line text-green-500 ml-3"></i>
                  <span class="text-gray-700">يتحدث: {{ implode('، ', $teacher->languages) }}</span>
                </div>
              @endif
            </div>
          </div>

        </div>

        <!-- Description -->
        @if($teacher->bio_arabic)
          <div class="mt-6 pt-6 border-t border-gray-200">
            <h3 class="text-lg font-bold text-gray-900 mb-3">
              <i class="ri-user-line text-primary ml-2"></i>
              نبذة عن المعلم
            </h3>
            <p class="text-gray-700 leading-relaxed">
              {{ $teacher->bio_arabic }}
            </p>
          </div>
        @endif
      </div>
    </div>
  </div>

  <!-- Availability and Trial Session Row -->
  <div class="grid grid-cols-1 lg:grid-cols-3 gap-8 mb-8">
    
    <!-- Availability - Takes 2 columns -->
    <div class="lg:col-span-2">
      <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-6">
        <h3 class="text-xl font-bold text-gray-900 mb-6">
          <i class="ri-calendar-line text-primary ml-2"></i>
          أوقات التدريس المتاحة
        </h3>
        
        @if($teacher->available_days && count($teacher->available_days) > 0)
          <div class="flex flex-wrap gap-2 mb-6">
            @php
              $daysInArabic = [
                'monday' => 'الاثنين',
                'tuesday' => 'الثلاثاء', 
                'wednesday' => 'الأربعاء',
                'thursday' => 'الخميس',
                'friday' => 'الجمعة',
                'saturday' => 'السبت',
                'sunday' => 'الأحد'
              ];
            @endphp
            @foreach($teacher->available_days as $day)
              <span class="bg-primary/10 text-primary px-3 py-1 rounded-full text-sm font-medium">
                {{ $daysInArabic[$day] ?? $day }}
              </span>
            @endforeach
          </div>
        @endif

        @if($teacher->available_time_start && $teacher->available_time_end)
          <div class="text-right mb-4">
            @php
              $startHour = $teacher->available_time_start->format('H');
              $startMinute = $teacher->available_time_start->format('i');
              $endHour = $teacher->available_time_end->format('H');
              $endMinute = $teacher->available_time_end->format('i');
              
              // Format start time
              $startTime12 = $startHour > 12 ? ($startHour - 12) : ($startHour == 0 ? 12 : $startHour);
              $startPeriod = $startHour >= 12 ? 'مساءً' : 'صباحاً';
              $startTimeFormatted = $startTime12 . ':' . $startMinute . ' ' . $startPeriod;
              
              // Format end time
              $endTime12 = $endHour > 12 ? ($endHour - 12) : ($endHour == 0 ? 12 : $endHour);
              $endPeriod = $endHour >= 12 ? 'مساءً' : 'صباحاً';
              $endTimeFormatted = $endTime12 . ':' . $endMinute . ' ' . $endPeriod;
            @endphp
            <div class="text-lg font-medium text-gray-700 mb-2">
              من الساعة {{ $startTimeFormatted }} إلى الساعة {{ $endTimeFormatted }}
            </div>
          </div>
        @endif
        
        <!-- Flexibility Note -->
        <div class="bg-blue-50 border border-blue-200 rounded-lg p-4">
          <div class="flex items-start">
            <i class="ri-information-line text-blue-600 ml-3 mt-0.5"></i>
            <p class="text-sm text-blue-800">
              <strong>ملاحظة:</strong> أوقات الجلسات يتم تحديدها بمرونة لتناسب كل من الطالب والمعلم، ويمكن التنسيق المسبق لاختيار الأوقات المناسبة للطرفين.
            </p>
          </div>
        </div>
      </div>
    </div>

    <!-- Trial Session - Takes 1 column -->
    <div class="lg:col-span-1">
      @if($offersTrialSessions)
        <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-6 h-full">
          <div class="text-center">
            <div class="w-16 h-16 mx-auto mb-4 bg-green-100 rounded-full flex items-center justify-center">
              <i class="ri-gift-line text-2xl text-green-600"></i>
            </div>
            <h3 class="text-xl font-bold text-gray-900 mb-2">جلسة تجريبية مجانية</h3>
            <p class="text-gray-600 mb-6">تجربة مجانية لتقييم مستواك</p>
            
            @auth
              @if(auth()->user()->user_type === 'student')
                @if($existingTrialRequest)
                  <!-- Show existing request status -->
                  <div class="bg-yellow-50 border border-yellow-200 rounded-lg p-4">
                    <div class="flex items-center justify-center">
                      <i class="ri-time-line text-yellow-600 ml-2"></i>
                      <span class="text-sm font-medium text-yellow-800">طلب موجود - الحالة: {{ $existingTrialRequest->getStatusLabelAttribute() }}</span>
                      @if($existingTrialRequest->scheduled_at)
                        <span class="text-sm text-yellow-700 mr-2">({{ $existingTrialRequest->scheduled_at->format('Y/m/d') }})</span>
                      @endif
                    </div>
                  </div>
                @else
                  <a href="{{ route('public.quran-teachers.trial', ['subdomain' => auth()->user()->academy->subdomain, 'teacher' => $teacher->id]) }}" 
                     class="w-full bg-green-600 text-white py-3 px-6 rounded-lg font-medium hover:bg-green-700 transition-colors block">
                    احجز جلسة تجريبية
                  </a>
                @endif
              @else
                <div class="text-center text-gray-500">
                  متاح للطلاب فقط
                </div>
              @endif
            @else
              <a href="{{ route('login', ['subdomain' => auth()->user()->academy->subdomain ?? 'itqan-academy']) }}" 
                 class="w-full bg-green-600 text-white py-3 px-6 rounded-lg font-medium hover:bg-green-700 transition-colors block">
                سجل دخولك لحجز جلسة
              </a>
            @endauth
          </div>
        </div>
      @else
        <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-6 h-full">
          <div class="text-center">
            <div class="w-16 h-16 mx-auto mb-4 bg-gray-100 rounded-full flex items-center justify-center">
              <i class="ri-close-line text-2xl text-gray-400"></i>
            </div>
            <h3 class="text-xl font-bold text-gray-900 mb-2">الجلسات التجريبية</h3>
            <p class="text-gray-600 mb-6">غير متاحة حالياً</p>
            <div class="text-sm text-gray-500">
              هذا المعلم لا يقدم جلسات تجريبية في الوقت الحالي
            </div>
          </div>
        </div>
      @endif
    </div>
  </div>

  <!-- Quran Packages Section -->
  @if($packages->count() > 0)
    <div class="mb-8">
      <h2 class="text-2xl font-bold text-gray-900 mb-6">
        <i class="ri-package-line text-primary ml-2"></i>
        الباقات المتاحة
      </h2>
      
      <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
        @foreach($packages as $package)
          <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-6 hover:shadow-md transition-shadow card-hover flex flex-col h-full">
            
            <!-- Package Header -->
            <div class="text-center mb-6">
              <h3 class="text-xl font-bold text-gray-900 mb-2">{{ $package->getDisplayName() }}</h3>
              <div class="text-3xl font-bold text-primary mb-1">{{ $package->monthly_price }} {{ $package->getDisplayCurrency() }}</div>
              <div class="text-sm text-gray-500">شهرياً</div>
            </div>
            
            <!-- Package Description -->
            <p class="text-gray-600 text-center mb-6">{{ $package->getDescription() }}</p>
            
            <!-- Package Features - Flexible grow -->
            <div class="space-y-3 mb-6 flex-grow">
              <div class="flex items-center">
                <i class="ri-check-line text-green-500 ml-3"></i>
                <span class="text-gray-700">{{ $package->sessions_per_month }} جلسة شهرياً</span>
              </div>
              <div class="flex items-center">
                <i class="ri-check-line text-green-500 ml-3"></i>
                <span class="text-gray-700">{{ $package->session_duration_minutes }} دقيقة لكل جلسة</span>
              </div>
              @if($package->features && count($package->features) > 0)
                @foreach($package->features as $feature)
                  <div class="flex items-center">
                    <i class="ri-check-line text-green-500 ml-3"></i>
                    <span class="text-gray-700">{{ $feature }}</span>
                  </div>
                @endforeach
              @endif
            </div>
            
            <!-- Subscribe Button - Always at bottom -->
            <div class="mt-auto">
              @auth
                @if(auth()->user()->user_type === 'student')
                  <a href="{{ route('public.quran-teachers.subscribe', ['subdomain' => auth()->user()->academy->subdomain, 'teacher' => $teacher->id, 'packageId' => $package->id]) }}" 
                     class="w-full bg-primary text-white py-3 px-6 rounded-lg text-center font-medium hover:bg-secondary transition-colors block">
                    اشترك الآن
                  </a>
                @else
                  <div class="text-center text-gray-500 py-3">متاح للطلاب فقط</div>
                @endif
              @else
                <a href="{{ route('login', ['subdomain' => auth()->user()->academy->subdomain ?? 'itqan-academy']) }}" 
                   class="w-full bg-primary text-white py-3 px-6 rounded-lg text-center font-medium hover:bg-secondary transition-colors block">
                  سجل دخولك للاشتراك
                </a>
              @endauth
            </div>
          </div>
        @endforeach
      </div>
    </div>
  @endif

</x-layouts.student>