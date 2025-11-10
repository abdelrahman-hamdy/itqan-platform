<!DOCTYPE html>
<html lang="ar" dir="rtl">

<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>{{ $course->title }} - {{ $academy->name }}</title>
  <meta name="description" content="{{ $course->description ?? 'تفاصيل الكورس التفاعلي' }}">
  <script src="https://cdn.tailwindcss.com/3.4.16"></script>
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/remixicon/4.6.0/remixicon.min.css">
  <script>
    tailwind.config = {
      theme: {
        extend: {
          colors: {
            primary: "{{ $academy->brand_color ?? $academy->primary_color ?? '#4169E1' }}",
            secondary: "{{ $academy->secondary_color ?? '#6495ED' }}",
          }
        }
      }
    };
  </script>
  @include('components.global-styles')
</head>

<body class="bg-gray-50 font-sans">

  <!-- Public Navigation -->
  @include('components.public-navigation', ['academy' => $academy])

  <!-- Course Hero Section -->
  <section class="bg-gradient-to-br from-primary to-secondary text-white py-12">
    <div class="container mx-auto px-4">
      <div class="max-w-4xl mx-auto">
        <!-- Breadcrumb -->
        <nav class="flex items-center gap-2 text-sm mb-6 opacity-90">
          <a href="{{ route('academy.home') }}" class="hover:underline">الرئيسية</a>
          <i class="ri-arrow-left-s-line text-xs"></i>
          <a href="{{ route('interactive-courses.index') }}" class="hover:underline">الكورسات التفاعلية</a>
          <i class="ri-arrow-left-s-line text-xs"></i>
          <span>{{ $course->title }}</span>
        </nav>

        <div class="grid md:grid-cols-3 gap-8 items-center">
          <!-- Course Info -->
          <div class="md:col-span-2">
            <div class="inline-block bg-white/20 backdrop-blur-sm px-3 py-1 rounded-full text-sm mb-3">
              {{ $course->course_type_in_arabic }}
            </div>
            <h1 class="text-3xl md:text-4xl font-bold mb-3">{{ $course->title }}</h1>
            <p class="text-lg opacity-90 mb-4">{{ $course->description ?? 'كورس تفاعلي شامل مع أفضل المعلمين' }}</p>

            <!-- Teacher Info -->
            @if($course->assignedTeacher)
            <div class="flex items-center gap-3 bg-white/10 backdrop-blur-sm rounded-lg p-3 inline-flex">
              <div class="w-10 h-10 rounded-full bg-white/20 flex items-center justify-center">
                <i class="ri-user-line text-xl"></i>
              </div>
              <div>
                <p class="text-sm opacity-75">المعلم</p>
                <p class="font-semibold">{{ $course->assignedTeacher->user->name }}</p>
              </div>
            </div>
            @endif
          </div>

          <!-- Course Card -->
          <div class="bg-white rounded-xl shadow-lg p-6 text-gray-900">
            <div class="text-center mb-4">
              <div class="text-4xl font-bold text-primary mb-1">{{ number_format($course->student_price, 0) }}</div>
              <div class="text-sm text-gray-600">ريال سعودي</div>
              @if($course->is_enrollment_fee_required && $course->enrollment_fee > 0)
              <div class="text-xs text-gray-500 mt-1">+ {{ number_format($course->enrollment_fee, 0) }} ريال رسوم التسجيل</div>
              @endif
            </div>

            <div class="space-y-3 mb-6 text-sm">
              <div class="flex justify-between items-center">
                <span class="text-gray-600">المقاعد المتاحة</span>
                <span class="font-semibold">{{ $course->getAvailableSlots() }} / {{ $course->max_students }}</span>
              </div>
              <div class="flex justify-between items-center">
                <span class="text-gray-600">المدة</span>
                <span class="font-semibold">{{ $course->duration_weeks }} أسبوع</span>
              </div>
              <div class="flex justify-between items-center">
                <span class="text-gray-600">عدد الجلسات</span>
                <span class="font-semibold">{{ $course->total_sessions }} جلسة</span>
              </div>
            </div>

            @auth
              @if($course->isEnrollmentOpen())
                <a href="{{ route('interactive-courses.enroll', ['course' => $course->id]) }}"
                   class="block w-full bg-primary text-white py-3 px-4 rounded-lg text-center font-semibold hover:bg-secondary transition-colors">
                  <i class="ri-shopping-cart-line ml-2"></i>
                  سجل الآن
                </a>
              @else
                <button disabled class="block w-full bg-gray-300 text-gray-600 py-3 px-4 rounded-lg text-center font-semibold cursor-not-allowed">
                  التسجيل مغلق حالياً
                </button>
              @endif
            @else
              <a href="{{ route('login') }}"
                 class="block w-full bg-primary text-white py-3 px-4 rounded-lg text-center font-semibold hover:bg-secondary transition-colors">
                <i class="ri-login-box-line ml-2"></i>
                سجل دخول للتسجيل
              </a>
            @endauth
          </div>
        </div>
      </div>
    </div>
  </section>

  <!-- Course Details -->
  <section class="py-12">
    <div class="container mx-auto px-4">
      <div class="max-w-4xl mx-auto">
        <div class="grid md:grid-cols-3 gap-8">
          <!-- Main Content -->
          <div class="md:col-span-2 space-y-8">
            <!-- About Course -->
            <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-6">
              <h2 class="text-2xl font-bold mb-4 flex items-center gap-2">
                <i class="ri-information-line text-primary"></i>
                نبذة عن الكورس
              </h2>
              <div class="prose prose-sm max-w-none text-gray-700">
                <p>{{ $course->description ?? 'كورس تفاعلي شامل يغطي جميع جوانب المادة بطريقة احترافية مع أفضل المعلمين المتخصصين.' }}</p>
              </div>
            </div>

            <!-- What You'll Learn -->
            <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-6">
              <h2 class="text-2xl font-bold mb-4 flex items-center gap-2">
                <i class="ri-checkbox-circle-line text-primary"></i>
                ماذا ستتعلم
              </h2>
              <div class="grid md:grid-cols-2 gap-3">
                <div class="flex items-start gap-2">
                  <i class="ri-check-line text-green-500 mt-1"></i>
                  <span class="text-sm text-gray-700">فهم شامل للمادة</span>
                </div>
                <div class="flex items-start gap-2">
                  <i class="ri-check-line text-green-500 mt-1"></i>
                  <span class="text-sm text-gray-700">تطبيقات عملية</span>
                </div>
                <div class="flex items-start gap-2">
                  <i class="ri-check-line text-green-500 mt-1"></i>
                  <span class="text-sm text-gray-700">واجبات تفاعلية</span>
                </div>
                <div class="flex items-start gap-2">
                  <i class="ri-check-line text-green-500 mt-1"></i>
                  <span class="text-sm text-gray-700">تقييمات مستمرة</span>
                </div>
              </div>
            </div>

            <!-- Course Schedule -->
            @if($course->schedule)
            <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-6">
              <h2 class="text-2xl font-bold mb-4 flex items-center gap-2">
                <i class="ri-calendar-line text-primary"></i>
                جدول الكورس
              </h2>
              <div class="space-y-2 text-sm">
                @foreach($course->schedule as $day => $time)
                <div class="flex items-center justify-between p-3 bg-gray-50 rounded-lg">
                  <span class="font-medium">{{ $day }}</span>
                  <span class="text-gray-600">{{ $time }}</span>
                </div>
                @endforeach
              </div>
            </div>
            @endif
          </div>

          <!-- Sidebar -->
          <div class="space-y-6">
            <!-- Course Features -->
            <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-6">
              <h3 class="font-bold mb-4">مميزات الكورس</h3>
              <div class="space-y-3 text-sm">
                <div class="flex items-center gap-3">
                  <div class="w-10 h-10 rounded-lg bg-blue-50 flex items-center justify-center">
                    <i class="ri-vidicon-line text-primary"></i>
                  </div>
                  <div>
                    <div class="font-medium">جلسات مباشرة</div>
                    <div class="text-xs text-gray-600">تفاعل مباشر مع المعلم</div>
                  </div>
                </div>
                <div class="flex items-center gap-3">
                  <div class="w-10 h-10 rounded-lg bg-green-50 flex items-center justify-center">
                    <i class="ri-file-text-line text-green-600"></i>
                  </div>
                  <div>
                    <div class="font-medium">واجبات منزلية</div>
                    <div class="text-xs text-gray-600">تطبيق عملي مستمر</div>
                  </div>
                </div>
                <div class="flex items-center gap-3">
                  <div class="w-10 h-10 rounded-lg bg-purple-50 flex items-center justify-center">
                    <i class="ri-award-line text-purple-600"></i>
                  </div>
                  <div>
                    <div class="font-medium">شهادة إتمام</div>
                    <div class="text-xs text-gray-600">عند إنهاء الكورس</div>
                  </div>
                </div>
              </div>
            </div>

            <!-- Course Info -->
            <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-6">
              <h3 class="font-bold mb-4">معلومات الكورس</h3>
              <div class="space-y-3 text-sm">
                <div class="flex justify-between">
                  <span class="text-gray-600">المستوى</span>
                  <span class="font-medium">{{ $course->gradeLevel->name ?? 'جميع المستويات' }}</span>
                </div>
                <div class="flex justify-between">
                  <span class="text-gray-600">المادة</span>
                  <span class="font-medium">{{ $course->subject->name ?? 'عامة' }}</span>
                </div>
                <div class="flex justify-between">
                  <span class="text-gray-600">تاريخ البدء</span>
                  <span class="font-medium">{{ $course->start_date?->format('Y/m/d') ?? 'سيُعلن قريباً' }}</span>
                </div>
                <div class="flex justify-between">
                  <span class="text-gray-600">تاريخ الانتهاء</span>
                  <span class="font-medium">{{ $course->end_date?->format('Y/m/d') ?? 'حسب الجدول' }}</span>
                </div>
                <div class="flex justify-between">
                  <span class="text-gray-600">آخر موعد للتسجيل</span>
                  <span class="font-medium text-red-600">{{ $course->enrollment_deadline?->format('Y/m/d') }}</span>
                </div>
              </div>
            </div>
          </div>
        </div>
      </div>
    </div>
  </section>

  <!-- Footer -->
  @include('academy.components.footer', ['academy' => $academy])

</body>
</html>
