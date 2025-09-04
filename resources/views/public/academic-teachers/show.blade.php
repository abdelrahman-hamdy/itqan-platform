<x-layouts.student 
  title="المعلم الأكاديمي - {{ $teacher->full_name }}" 
  description="تعلم مع الأستاذ {{ $teacher->full_name }} - معلم مؤهل ومعتمد في {{ auth()->user()->academy->name ?? 'أكاديمية إتقان' }}">

  <!-- Breadcrumb -->
  <nav class="mb-8">
    <ol class="flex items-center space-x-2 space-x-reverse text-sm text-gray-600">
      <li><a href="{{ route('student.profile', ['subdomain' => auth()->user()->academy->subdomain ?? 'itqan-academy']) }}" class="hover:text-primary">ملفي الشخصي</a></li>
      <li>/</li>
      <li><a href="{{ route('student.academic-teachers', ['subdomain' => auth()->user()->academy->subdomain ?? 'itqan-academy']) }}" class="hover:text-primary">المعلمون الأكاديميون</a></li>
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
            <p class="text-xl text-primary font-medium mb-6">معلم أكاديمي معتمد</p>

            <!-- Stats - Side by Side Layout -->
            <div class="flex items-center gap-8">
              <!-- Rating Section -->
              <div class="flex items-center">
                <i class="ri-star-line text-yellow-500 text-2xl ml-2"></i>
                <div>
                  @if($teacher->average_rating && $teacher->average_rating > 0)
                    <div class="text-2xl font-bold text-gray-900">{{ number_format($teacher->average_rating, 1) }}</div>
                    <div class="text-sm text-gray-600">تقييم المعلم</div>
                  @else
                    <div class="text-2xl font-bold text-gray-900">جديد</div>
                    <div class="text-sm text-gray-600">معلم جديد</div>
                  @endif
                </div>
              </div>

              <!-- Students Count Section -->
              <div class="flex items-center">
                <i class="ri-group-line text-blue-500 text-2xl ml-2"></i>
                <div>
                  <div class="text-2xl font-bold text-gray-900">{{ $teacher->students_count ?? 0 }}</div>
                  <div class="text-sm text-gray-600">طالب نشط</div>
                </div>
              </div>

              <!-- Experience Section -->
              @if($teacher->experience_years)
              <div class="flex items-center">
                <i class="ri-time-line text-green-500 text-2xl ml-2"></i>
                <div>
                  <div class="text-2xl font-bold text-gray-900">{{ $teacher->experience_years }}</div>
                  <div class="text-sm text-gray-600">سنوات خبرة</div>
                </div>
              </div>
              @endif
            </div>

            <!-- Bio/Description -->
            @if($teacher->bio)
            <div class="mt-6">
              <p class="text-gray-700 leading-relaxed">{{ $teacher->bio }}</p>
            </div>
            @endif
          </div>

          <!-- Additional Details -->
          <div class="space-y-4">
            
            <!-- Qualification -->
            @if($teacher->qualification)
            <div class="flex items-center">
              <i class="ri-medal-line text-orange-500 text-lg ml-3"></i>
              <div>
                <div class="text-sm text-gray-600">المؤهل العلمي</div>
                <div class="font-medium text-gray-900">{{ $teacher->qualification }}</div>
              </div>
            </div>
            @endif

            <!-- University -->
            @if($teacher->university)
            <div class="flex items-center">
              <i class="ri-school-line text-purple-500 text-lg ml-3"></i>
              <div>
                <div class="text-sm text-gray-600">الجامعة</div>
                <div class="font-medium text-gray-900">{{ $teacher->university }}</div>
              </div>
            </div>
            @endif

            <!-- Teaching Experience -->
            @if($teacher->teaching_experience_years)
            <div class="flex items-center">
              <i class="ri-book-line text-indigo-500 text-lg ml-3"></i>
              <div>
                <div class="text-sm text-gray-600">سنوات التدريس</div>
                <div class="font-medium text-gray-900">{{ $teacher->teaching_experience_years }} سنوات</div>
              </div>
            </div>
            @endif



            <!-- Languages -->
            @if($teacher->languages && is_array($teacher->languages) && count($teacher->languages) > 0)
            <div class="flex items-start">
              <i class="ri-translate-line text-teal-500 text-lg ml-3 mt-1"></i>
              <div>
                <div class="text-sm text-gray-600">اللغات</div>
                <div class="font-medium text-gray-900">
                  {{ implode(' • ', $teacher->languages) }}
                </div>
              </div>
            </div>
            @endif
          </div>
        </div>
      </div>
    </div>
  </div>

  <!-- Teaching Times & Educational Stages Section -->
  <div class="grid grid-cols-1 lg:grid-cols-2 gap-8 mb-8">
    
    <!-- Teaching Times (50% width) -->
    <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-6">
      <h3 class="text-xl font-bold text-gray-900 mb-4">
        <i class="ri-calendar-line text-primary ml-2"></i>
        أوقات التدريس
      </h3>
      
      <!-- Available Days -->
      @if($teacher->available_days && is_array($teacher->available_days) && count($teacher->available_days) > 0)
        <div class="mb-4">
          <h4 class="text-sm font-medium text-gray-700 mb-2">الأيام المتاحة</h4>
          <div class="flex flex-wrap gap-2">
            @foreach($teacher->available_days as $day)
              @php
                $dayNames = [
                  'sunday' => 'الأحد',
                  'monday' => 'الاثنين', 
                  'tuesday' => 'الثلاثاء',
                  'wednesday' => 'الأربعاء',
                  'thursday' => 'الخميس',
                  'friday' => 'الجمعة',
                  'saturday' => 'السبت'
                ];
              @endphp
              <span class="inline-flex items-center px-3 py-1 rounded-full text-sm font-medium bg-green-100 text-green-800">
                {{ $dayNames[$day] ?? $day }}
              </span>
            @endforeach
          </div>
        </div>
      @endif

      <!-- Available Times -->
      @if($teacher->available_time_start && $teacher->available_time_end)
        <div class="mb-4">
          <h4 class="text-sm font-medium text-gray-700 mb-2">الأوقات المتاحة</h4>
          <div class="flex items-center bg-blue-50 rounded-lg p-3">
            <i class="ri-time-line text-blue-600 ml-2"></i>
            <span class="text-blue-800 font-medium">
              من {{ \Carbon\Carbon::parse($teacher->available_time_start)->format('H:i') }} 
              إلى {{ \Carbon\Carbon::parse($teacher->available_time_end)->format('H:i') }}
            </span>
          </div>
        </div>
      @endif

      <!-- Languages -->
      @if($teacher->languages && is_array($teacher->languages) && count($teacher->languages) > 0)
        <div>
          <h4 class="text-sm font-medium text-gray-700 mb-2">لغات التدريس</h4>
          <div class="flex flex-wrap gap-2">
            @foreach($teacher->languages as $language)
              @php
                $languageNames = [
                  'arabic' => 'العربية',
                  'english' => 'الإنجليزية',
                  'french' => 'الفرنسية',
                  'german' => 'الألمانية',
                  'turkish' => 'التركية',
                  'spanish' => 'الإسبانية',
                  'urdu' => 'الأردية',
                  'persian' => 'الفارسية'
                ];
              @endphp
              <span class="inline-flex items-center px-3 py-1 rounded-full text-sm font-medium bg-teal-100 text-teal-800">
                {{ $languageNames[$language] ?? $language }}
              </span>
            @endforeach
          </div>
        </div>
      @endif
    </div>

    <!-- Educational Stages (50% width) -->
    <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-6">
      <h3 class="text-xl font-bold text-gray-900 mb-4">
        <i class="ri-graduation-cap-line text-primary ml-2"></i>
        المراحل التعليمية
      </h3>
      
      <!-- Grade Levels -->
      @if($teacher->gradeLevels && $teacher->gradeLevels->count() > 0)
        <div class="mb-4">
          <h4 class="text-sm font-medium text-gray-700 mb-2">المراحل الدراسية</h4>
          <div class="flex flex-wrap gap-2">
            @foreach($teacher->gradeLevels as $gradeLevel)
              <span class="inline-flex items-center px-3 py-1 rounded-full text-sm font-medium bg-purple-100 text-purple-800">
                {{ $gradeLevel->name }}
              </span>
            @endforeach
          </div>
        </div>
      @endif

      <!-- Subjects -->
      @if($teacher->subjects && $teacher->subjects->count() > 0)
        <div>
          <h4 class="text-sm font-medium text-gray-700 mb-2">المواد التدريسية</h4>
          <div class="flex flex-wrap gap-2">
            @foreach($teacher->subjects as $subject)
              <span class="inline-flex items-center px-3 py-1 rounded-full text-sm font-medium bg-blue-100 text-blue-800">
                {{ $subject->name }}
              </span>
            @endforeach
          </div>
        </div>
      @endif
    </div>
  </div>

  <!-- Certifications -->
  @if($teacher->certifications && is_array($teacher->certifications) && count($teacher->certifications) > 0)
  <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-6 mb-8">
    <h3 class="text-xl font-bold text-gray-900 mb-4">
      <i class="ri-award-line text-primary ml-2"></i>
      الشهادات والتخصصات
    </h3>
    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
      @foreach($teacher->certifications as $certification)
        <div class="flex items-center p-3 bg-green-50 rounded-lg">
          <i class="ri-check-line text-green-600 ml-2"></i>
          <span class="text-green-800 font-medium">{{ $certification }}</span>
        </div>
      @endforeach
    </div>
  </div>
  @endif

  <!-- Academic Packages Section -->
  @if(isset($packages) && $packages->count() > 0)
    <div class="mb-8">
      <h2 class="text-2xl font-bold text-gray-900 mb-6">
        <i class="ri-package-line text-primary ml-2"></i>
        الباقات الأكاديمية المتاحة
      </h2>
      
      <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
        @foreach($packages as $package)
          <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-6 hover:shadow-md transition-shadow card-hover flex flex-col h-full">
            
            <!-- Package Header -->
            <div class="text-center mb-6">
              <h3 class="text-xl font-bold text-gray-900 mb-2">{{ $package->name_ar }}</h3>
              <div class="text-3xl font-bold text-primary mb-1">{{ $package->monthly_price }} {{ $package->currency }}</div>
              <div class="text-sm text-gray-500">شهرياً</div>
            </div>
            
            <!-- Package Description -->
            @if($package->description_ar)
              <p class="text-gray-600 text-center mb-6">{{ $package->description_ar }}</p>
            @endif
            
            <!-- Package Type Badge -->
            <div class="flex justify-center mb-4">
              <span class="inline-flex items-center px-3 py-1 rounded-full text-sm font-medium 
                {{ $package->package_type === 'individual' ? 'bg-blue-100 text-blue-800' : 'bg-green-100 text-green-800' }}">
                {{ $package->package_type === 'individual' ? 'دروس فردية' : 'دروس جماعية' }}
              </span>
            </div>
            
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
              @if($package->package_type === 'group' && $package->max_students_per_session > 1)
                <div class="flex items-center">
                  <i class="ri-check-line text-green-500 ml-3"></i>
                  <span class="text-gray-700">حد أقصى {{ $package->max_students_per_session }} طلاب</span>
                </div>
              @endif

              @if($package->features && is_array($package->features) && count($package->features) > 0)
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
                  <a href="{{ route('public.academic-packages.subscribe', ['subdomain' => $academy->subdomain ?? auth()->user()->academy->subdomain, 'teacher' => $teacher->id, 'packageId' => $package->id]) }}" 
                     class="w-full bg-primary text-white py-3 px-6 rounded-lg text-center font-medium hover:bg-secondary transition-colors block">
                    اشترك الآن
                  </a>
                @else
                  <div class="text-center text-gray-500 py-3">متاح للطلاب فقط</div>
                @endif
              @else
                <a href="{{ route('login', ['subdomain' => $academy->subdomain ?? 'itqan-academy']) }}" 
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
