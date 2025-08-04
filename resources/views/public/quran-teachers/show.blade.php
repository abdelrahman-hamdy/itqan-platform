<!DOCTYPE html>
<html lang="ar" dir="rtl">

<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>{{ $teacher->full_name }} - معلم القرآن الكريم - {{ $academy->name ?? 'أكاديمية إتقان' }}</title>
  <meta name="description" content="تعلم القرآن الكريم مع الأستاذ {{ $teacher->full_name }} - معلم مؤهل ومعتمد في {{ $academy->name ?? 'أكاديمية إتقان' }}">
  <script src="https://cdn.tailwindcss.com/3.4.16"></script>
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/remixicon/4.6.0/remixicon.min.css">
  <script>
    tailwind.config = {
      theme: {
        extend: {
          colors: {
            primary: "{{ $academy->primary_color ?? '#4169E1' }}",
            secondary: "{{ $academy->secondary_color ?? '#6495ED' }}",
          }
        }
      }
    };
  </script>
  <style>
    .card-hover {
      transition: all 0.3s ease;
    }
    .card-hover:hover {
      transform: translateY(-4px);
      box-shadow: 0 20px 40px rgba(65, 105, 225, 0.15);
    }
  </style>
</head>

<body class="bg-gray-50 font-sans">

  <!-- Header -->
  <header class="bg-white shadow-sm sticky top-0 z-50">
    <div class="container mx-auto px-4 py-4">
      <div class="flex items-center justify-between">
        <!-- Logo and Academy Name -->
        <div class="flex items-center space-x-3 space-x-reverse">
          @if($academy->logo)
            <img src="{{ asset('storage/' . $academy->logo) }}" alt="{{ $academy->name }}" class="h-10 w-10 rounded-lg">
          @endif
          <div>
            <h1 class="text-xl font-bold text-gray-900">{{ $academy->name ?? 'أكاديمية إتقان' }}</h1>
          </div>
        </div>

        <!-- Navigation -->
        <nav class="hidden md:flex items-center space-x-6 space-x-reverse">
          <a href="{{ route('academy.home', ['subdomain' => $academy->subdomain]) }}" 
             class="text-gray-600 hover:text-primary transition-colors">الرئيسية</a>
          <a href="{{ route('public.quran-teachers.index', ['subdomain' => $academy->subdomain]) }}" 
             class="text-gray-600 hover:text-primary transition-colors">معلمو القرآن</a>
          @auth
            <a href="{{ route('student.profile', ['subdomain' => $academy->subdomain]) }}" 
               class="bg-primary text-white px-4 py-2 rounded-lg hover:bg-secondary transition-colors">
              الملف الشخصي
            </a>
          @else
            <a href="{{ route('login', ['subdomain' => $academy->subdomain]) }}" 
               class="bg-primary text-white px-4 py-2 rounded-lg hover:bg-secondary transition-colors">
              تسجيل الدخول
            </a>
          @endauth
        </nav>
      </div>
    </div>
  </header>

  <!-- Teacher Profile Header -->
  <section class="bg-gradient-to-l from-primary to-secondary text-white py-16">
    <div class="container mx-auto px-4">
      <div class="flex flex-col lg:flex-row items-center gap-8">
        <!-- Teacher Avatar -->
        <div class="relative">
          <div class="w-32 h-32 rounded-full border-4 border-white/20 overflow-hidden bg-white/10">
            @if($teacher->avatar)
              <img src="{{ asset('storage/' . $teacher->avatar) }}" alt="{{ $teacher->full_name }}" 
                   class="w-full h-full object-cover">
            @else
              <div class="w-full h-full flex items-center justify-center text-4xl font-bold">
                {{ substr($teacher->first_name, 0, 1) }}{{ substr($teacher->last_name, 0, 1) }}
              </div>
            @endif
          </div>
          <!-- Verified Badge -->
          <div class="absolute -bottom-2 -right-2 bg-green-500 text-white rounded-full w-8 h-8 flex items-center justify-center">
            <i class="ri-shield-check-line text-sm"></i>
          </div>
        </div>

        <!-- Teacher Info -->
        <div class="text-center lg:text-right flex-1">
          <h2 class="text-4xl font-bold mb-2">{{ $teacher->full_name }}</h2>
          <p class="text-xl opacity-90 mb-4">معلم القرآن الكريم المعتمد</p>
          <p class="text-lg opacity-75 mb-6">{{ $teacher->teacher_code }}</p>

          <!-- Rating -->
          @if($teacher->rating > 0)
            <div class="flex items-center justify-center lg:justify-start mb-4">
              <div class="flex text-yellow-400">
                @for($i = 1; $i <= 5; $i++)
                  <i class="ri-star-{{ $i <= $teacher->rating ? 'fill' : 'line' }} text-lg"></i>
                @endfor
              </div>
              <span class="text-lg mr-3">({{ $teacher->rating }})</span>
            </div>
          @endif

          <!-- Quick Stats -->
          <div class="grid grid-cols-3 gap-6 bg-white/10 backdrop-blur-sm rounded-lg p-6">
            <div class="text-center">
              <div class="text-2xl font-bold">{{ $stats['experience_years'] }}</div>
              <div class="text-sm opacity-75">سنوات خبرة</div>
            </div>
            <div class="text-center">
              <div class="text-2xl font-bold">{{ $stats['total_students'] }}</div>
              <div class="text-sm opacity-75">طالب</div>
            </div>
            <div class="text-center">
              <div class="text-2xl font-bold">{{ $stats['total_sessions'] }}</div>
              <div class="text-sm opacity-75">جلسة</div>
            </div>
          </div>
        </div>
      </div>
    </div>
  </section>

  <!-- Main Content -->
  <section class="py-16">
    <div class="container mx-auto px-4">
      <div class="grid grid-cols-1 lg:grid-cols-3 gap-8">
        
        <!-- Teacher Details -->
        <div class="lg:col-span-2 space-y-8">
          
          <!-- About -->
          <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-6">
            <h3 class="text-xl font-bold text-gray-900 mb-4">
              <i class="ri-user-line text-primary ml-2"></i>
              نبذة عن المعلم
            </h3>
            <div class="prose prose-gray max-w-none">
              <p class="text-gray-700 leading-relaxed">
                {{ $teacher->bio_arabic ?? 'معلم قرآن كريم متخصص ومؤهل بخبرة واسعة في تعليم القرآن الكريم وأحكام التجويد.' }}
              </p>
            </div>
          </div>

          <!-- Qualifications -->
          <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-6">
            <h3 class="text-xl font-bold text-gray-900 mb-4">
              <i class="ri-award-line text-primary ml-2"></i>
              المؤهلات والشهادات
            </h3>
            <div class="space-y-4">
              <!-- Educational Qualification -->
              @if($teacher->educational_qualification)
                <div class="flex items-center p-3 bg-gray-50 rounded-lg">
                  <i class="ri-graduation-cap-line text-primary ml-3"></i>
                  <span class="font-medium">{{ $teacher->educational_qualification }}</span>
                </div>
              @endif

              <!-- Certifications -->
              @if($teacher->certifications && count($teacher->certifications) > 0)
                @foreach($teacher->certifications as $certification)
                  <div class="flex items-center p-3 bg-gray-50 rounded-lg">
                    <i class="ri-medal-line text-primary ml-3"></i>
                    <span>{{ $certification }}</span>
                  </div>
                @endforeach
              @endif

              <!-- Experience -->
              <div class="flex items-center p-3 bg-gray-50 rounded-lg">
                <i class="ri-time-line text-primary ml-3"></i>
                <span>{{ $teacher->teaching_experience_years ?? 0 }} سنوات من الخبرة في التدريس</span>
              </div>

              <!-- Languages -->
              @if($teacher->languages && count($teacher->languages) > 0)
                <div class="flex items-center p-3 bg-gray-50 rounded-lg">
                  <i class="ri-global-line text-primary ml-3"></i>
                  <span>يتحدث: {{ implode('، ', $teacher->languages) }}</span>
                </div>
              @endif
            </div>
          </div>

          <!-- Availability -->
          <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-6">
            <h3 class="text-xl font-bold text-gray-900 mb-4">
              <i class="ri-calendar-line text-primary ml-2"></i>
              أوقات التدريس المتاحة
            </h3>
            
            @if($teacher->available_days && count($teacher->available_days) > 0)
              <div class="grid grid-cols-2 md:grid-cols-4 gap-3 mb-4">
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
                  <div class="bg-primary/10 text-primary px-3 py-2 rounded-lg text-center text-sm font-medium">
                    {{ $daysInArabic[$day] ?? $day }}
                  </div>
                @endforeach
              </div>
            @endif

            @if($teacher->available_time_start && $teacher->available_time_end)
              <div class="flex items-center text-gray-600">
                <i class="ri-time-line ml-2"></i>
                <span>من {{ $teacher->available_time_start->format('H:i') }} إلى {{ $teacher->available_time_end->format('H:i') }}</span>
              </div>
            @endif
          </div>

        </div>

        <!-- Booking Sidebar -->
        <div class="space-y-6">
          
          <!-- Trial Session -->
          @if($offersTrialSessions)
            <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-6">
              <div class="text-center mb-4">
                <div class="w-16 h-16 mx-auto mb-3 bg-green-100 rounded-full flex items-center justify-center">
                  <i class="ri-gift-line text-2xl text-green-600"></i>
                </div>
                <h3 class="text-lg font-bold text-gray-900">جلسة تجريبية مجانية</h3>
                <p class="text-sm text-gray-600 mt-1">تجربة مجانية لتقييم مستواك</p>
              </div>
              
              @auth
                @if(auth()->user()->user_type === 'student')
                  <a href="{{ route('public.quran-teachers.trial', ['subdomain' => $academy->subdomain, 'teacherCode' => $teacher->teacher_code]) }}" 
                     class="w-full bg-green-600 text-white py-3 px-4 rounded-lg text-center font-medium hover:bg-green-700 transition-colors block">
                    احجز جلسة تجريبية
                  </a>
                @else
                  <div class="text-center text-sm text-gray-500">
                    متاح للطلاب فقط
                  </div>
                @endif
              @else
                <a href="{{ route('login', ['subdomain' => $academy->subdomain]) }}" 
                   class="w-full bg-green-600 text-white py-3 px-4 rounded-lg text-center font-medium hover:bg-green-700 transition-colors block">
                  سجل دخولك لحجز جلسة
                </a>
              @endauth
            </div>
          @endif

          <!-- Packages -->
          @if($packages->count() > 0)
            <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-6">
              <h3 class="text-lg font-bold text-gray-900 mb-4">
                <i class="ri-package-line text-primary ml-2"></i>
                الباقات المتاحة
              </h3>
              
              <div class="space-y-4">
                @foreach($packages as $package)
                  <div class="border border-gray-200 rounded-lg p-4 hover:border-primary transition-colors">
                    <div class="flex justify-between items-start mb-2">
                      <h4 class="font-bold text-gray-900">{{ $package->getDisplayName() }}</h4>
                      <div class="text-left">
                        <div class="text-lg font-bold text-primary">{{ $package->monthly_price }} {{ $package->currency }}</div>
                        <div class="text-xs text-gray-500">شهرياً</div>
                      </div>
                    </div>
                    
                    <p class="text-sm text-gray-600 mb-3">{{ $package->getDescription() }}</p>
                    
                    <!-- Package Features -->
                    <div class="space-y-1 mb-4">
                      <div class="flex items-center text-sm text-gray-600">
                        <i class="ri-check-line text-green-500 ml-2"></i>
                        <span>{{ $package->sessions_per_month }} جلسة شهرياً</span>
                      </div>
                      <div class="flex items-center text-sm text-gray-600">
                        <i class="ri-check-line text-green-500 ml-2"></i>
                        <span>{{ $package->session_duration_minutes }} دقيقة لكل جلسة</span>
                      </div>
                      @if($package->features && count($package->features) > 0)
                        @foreach($package->features as $feature)
                          <div class="flex items-center text-sm text-gray-600">
                            <i class="ri-check-line text-green-500 ml-2"></i>
                            <span>{{ $feature }}</span>
                          </div>
                        @endforeach
                      @endif
                    </div>
                    
                    @auth
                      @if(auth()->user()->user_type === 'student')
                        <a href="{{ route('public.quran-teachers.subscribe', ['subdomain' => $academy->subdomain, 'teacherCode' => $teacher->teacher_code, 'packageId' => $package->id]) }}" 
                           class="w-full bg-primary text-white py-2 px-4 rounded-lg text-center text-sm font-medium hover:bg-secondary transition-colors block">
                          اشترك الآن
                        </a>
                      @else
                        <div class="text-center text-xs text-gray-500">متاح للطلاب فقط</div>
                      @endif
                    @else
                      <a href="{{ route('login', ['subdomain' => $academy->subdomain]) }}" 
                         class="w-full bg-primary text-white py-2 px-4 rounded-lg text-center text-sm font-medium hover:bg-secondary transition-colors block">
                        سجل دخولك للاشتراك
                      </a>
                    @endauth
                  </div>
                @endforeach
              </div>
            </div>
          @endif

          <!-- Contact Info -->
          <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-6">
            <h3 class="text-lg font-bold text-gray-900 mb-4">
              <i class="ri-customer-service-line text-primary ml-2"></i>
              هل تحتاج مساعدة؟
            </h3>
            <p class="text-sm text-gray-600 mb-4">
              تواصل معنا للمساعدة في اختيار الباقة المناسبة أو الإجابة على استفساراتك
            </p>
            @if($academy->contact_phone)
              <a href="tel:{{ $academy->contact_phone }}" 
                 class="w-full bg-gray-100 text-gray-700 py-2 px-4 rounded-lg text-center text-sm font-medium hover:bg-gray-200 transition-colors block mb-2">
                <i class="ri-phone-line ml-2"></i>
                {{ $academy->contact_phone }}
              </a>
            @endif
            @if($academy->contact_email)
              <a href="mailto:{{ $academy->contact_email }}" 
                 class="w-full bg-gray-100 text-gray-700 py-2 px-4 rounded-lg text-center text-sm font-medium hover:bg-gray-200 transition-colors block">
                <i class="ri-mail-line ml-2"></i>
                راسلنا عبر البريد
              </a>
            @endif
          </div>

        </div>
      </div>
    </div>
  </section>

  <!-- Footer -->
  <footer class="bg-gray-900 text-white py-12">
    <div class="container mx-auto px-4">
      <div class="grid grid-cols-1 md:grid-cols-3 gap-8">
        <!-- Academy Info -->
        <div>
          <h4 class="text-lg font-bold mb-4">{{ $academy->name ?? 'أكاديمية إتقان' }}</h4>
          <p class="text-gray-400 mb-4">منصة تعليمية متميزة لتعلم القرآن الكريم والعلوم الأكاديمية</p>
        </div>

        <!-- Quick Links -->
        <div>
          <h4 class="text-lg font-bold mb-4">روابط سريعة</h4>
          <ul class="space-y-2 text-gray-400">
            <li><a href="{{ route('academy.home', ['subdomain' => $academy->subdomain]) }}" class="hover:text-white transition-colors">الرئيسية</a></li>
            <li><a href="{{ route('public.quran-teachers.index', ['subdomain' => $academy->subdomain]) }}" class="hover:text-white transition-colors">معلمو القرآن</a></li>
          </ul>
        </div>

        <!-- Contact -->
        <div>
          <h4 class="text-lg font-bold mb-4">تواصل معنا</h4>
          @if($academy->contact_email)
            <p class="text-gray-400 mb-2">
              <i class="ri-mail-line ml-2"></i>
              {{ $academy->contact_email }}
            </p>
          @endif
          @if($academy->contact_phone)
            <p class="text-gray-400">
              <i class="ri-phone-line ml-2"></i>
              {{ $academy->contact_phone }}
            </p>
          @endif
        </div>
      </div>

      <div class="border-t border-gray-800 mt-8 pt-8 text-center text-gray-400">
        <p>&copy; {{ date('Y') }} {{ $academy->name ?? 'أكاديمية إتقان' }}. جميع الحقوق محفوظة.</p>
      </div>
    </div>
  </footer>

</body>
</html>