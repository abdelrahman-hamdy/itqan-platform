<!DOCTYPE html>
<html lang="ar" dir="rtl">

<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>معلمو القرآن الكريم - {{ $academy->name ?? 'أكاديمية إتقان' }}</title>
  <meta name="description" content="تصفح أفضل معلمي القرآن الكريم المؤهلين في {{ $academy->name ?? 'أكاديمية إتقان' }}">
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
            <p class="text-sm text-gray-600">معلمو القرآن الكريم</p>
          </div>
        </div>

        <!-- Navigation -->
        <nav class="hidden md:flex items-center space-x-6 space-x-reverse">
          <a href="{{ route('academy.home', ['subdomain' => $academy->subdomain]) }}" 
             class="text-gray-600 hover:text-primary transition-colors">الرئيسية</a>
          <a href="{{ route('public.quran-teachers.index', ['subdomain' => $academy->subdomain]) }}" 
             class="text-primary font-medium">معلمو القرآن</a>
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

  <!-- Page Header -->
  <section class="bg-gradient-to-l from-primary to-secondary text-white py-16">
    <div class="container mx-auto px-4 text-center">
      <h2 class="text-4xl font-bold mb-4">معلمو القرآن الكريم</h2>
      <p class="text-xl opacity-90 max-w-2xl mx-auto">
        اختر من بين أفضل المعلمين المؤهلين والمعتمدين لتعلم القرآن الكريم وتجويده
      </p>
      <div class="mt-8 bg-white/10 backdrop-blur-sm rounded-lg p-6 max-w-lg mx-auto">
        <div class="grid grid-cols-3 gap-4 text-center">
          <div>
            <div class="text-2xl font-bold">{{ $teachers->total() }}</div>
            <div class="text-sm opacity-75">معلم متاح</div>
          </div>
          <div>
            <div class="text-2xl font-bold">{{ $teachers->sum('total_students') }}</div>
            <div class="text-sm opacity-75">طالب مسجل</div>
          </div>
          <div>
            <div class="text-2xl font-bold">{{ $teachers->sum('total_sessions') }}</div>
            <div class="text-sm opacity-75">جلسة مكتملة</div>
          </div>
        </div>
      </div>
    </div>
  </section>

  <!-- Teachers Grid -->
  <section class="py-16">
    <div class="container mx-auto px-4">
      
      @if($teachers->count() > 0)
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4 gap-6">
          @foreach($teachers as $teacher)
            <div class="bg-white rounded-xl shadow-sm border border-gray-200 overflow-hidden card-hover">
              <!-- Teacher Avatar -->
              <div class="p-6 text-center">
                <div class="w-20 h-20 mx-auto mb-4 rounded-full bg-gradient-to-br from-primary to-secondary flex items-center justify-center text-white text-2xl font-bold">
                  @if($teacher->avatar)
                    <img src="{{ asset('storage/' . $teacher->avatar) }}" alt="{{ $teacher->full_name }}" class="w-full h-full rounded-full object-cover">
                  @else
                    {{ substr($teacher->first_name, 0, 1) }}{{ substr($teacher->last_name, 0, 1) }}
                  @endif
                </div>
                
                <!-- Teacher Name -->
                <h3 class="text-lg font-bold text-gray-900 mb-1">{{ $teacher->full_name }}</h3>
                <p class="text-sm text-gray-600 mb-2">{{ $teacher->teacher_code }}</p>
                
                <!-- Rating -->
                @if($teacher->rating > 0)
                  <div class="flex items-center justify-center mb-3">
                    <div class="flex text-yellow-400">
                      @for($i = 1; $i <= 5; $i++)
                        <i class="ri-star-{{ $i <= $teacher->rating ? 'fill' : 'line' }} text-sm"></i>
                      @endfor
                    </div>
                    <span class="text-sm text-gray-600 mr-2">({{ $teacher->rating }})</span>
                  </div>
                @endif
              </div>

              <!-- Teacher Info -->
              <div class="px-6 pb-4 space-y-3">
                <!-- Experience -->
                <div class="flex items-center text-sm text-gray-600">
                  <i class="ri-time-line ml-2 text-primary"></i>
                  <span>{{ $teacher->teaching_experience_years ?? 0 }} سنوات خبرة</span>
                </div>

                <!-- Students Count -->
                <div class="flex items-center text-sm text-gray-600">
                  <i class="ri-group-line ml-2 text-primary"></i>
                  <span>{{ $teacher->total_students ?? 0 }} طالب</span>
                </div>

                <!-- Languages -->
                @if($teacher->languages)
                  <div class="flex items-center text-sm text-gray-600">
                    <i class="ri-global-line ml-2 text-primary"></i>
                    <span>{{ implode('، ', $teacher->languages) }}</span>
                  </div>
                @endif

                <!-- Certifications -->
                @if($teacher->certifications && count($teacher->certifications) > 0)
                  <div class="flex items-center text-sm text-gray-600">
                    <i class="ri-award-line ml-2 text-primary"></i>
                    <span>{{ count($teacher->certifications) }} شهادات</span>
                  </div>
                @endif
              </div>

              <!-- Action Button -->
              <div class="px-6 pb-6">
                <a href="{{ route('public.quran-teachers.show', ['subdomain' => $academy->subdomain, 'teacherCode' => $teacher->teacher_code]) }}" 
                   class="w-full bg-primary text-white py-3 px-4 rounded-lg text-center font-medium hover:bg-secondary transition-colors block">
                  عرض الملف الشخصي
                </a>
              </div>
            </div>
          @endforeach
        </div>

        <!-- Pagination -->
        @if($teachers->hasPages())
          <div class="mt-12">
            {{ $teachers->links() }}
          </div>
        @endif

      @else
        <!-- No Teachers -->
        <div class="text-center py-16">
          <div class="w-24 h-24 mx-auto mb-6 rounded-full bg-gray-100 flex items-center justify-center">
            <i class="ri-user-line text-4xl text-gray-400"></i>
          </div>
          <h3 class="text-xl font-bold text-gray-900 mb-2">لا توجد معلمين متاحين حالياً</h3>
          <p class="text-gray-600 mb-6">نعمل على إضافة معلمين جدد قريباً</p>
          <a href="{{ route('academy.home', ['subdomain' => $academy->subdomain]) }}" 
             class="bg-primary text-white px-6 py-3 rounded-lg hover:bg-secondary transition-colors">
            العودة للرئيسية
          </a>
        </div>
      @endif
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