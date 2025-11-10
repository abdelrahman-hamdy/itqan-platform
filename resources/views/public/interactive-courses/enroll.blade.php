<!DOCTYPE html>
<html lang="ar" dir="rtl">

<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>التسجيل في {{ $course->title }} - {{ $academy->name }}</title>
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

  <!-- Enrollment Section -->
  <section class="py-12">
    <div class="container mx-auto px-4">
      <div class="max-w-4xl mx-auto">
        <!-- Breadcrumb -->
        <nav class="flex items-center gap-2 text-sm mb-6 text-gray-600">
          <a href="{{ route('academy.home') }}" class="hover:text-primary">الرئيسية</a>
          <i class="ri-arrow-left-s-line text-xs"></i>
          <a href="{{ route('interactive-courses.index') }}" class="hover:text-primary">الكورسات التفاعلية</a>
          <i class="ri-arrow-left-s-line text-xs"></i>
          <a href="{{ route('interactive-courses.show', ['course' => $course->id]) }}" class="hover:text-primary">{{ $course->title }}</a>
          <i class="ri-arrow-left-s-line text-xs"></i>
          <span class="text-gray-900">التسجيل</span>
        </nav>

        <div class="grid md:grid-cols-3 gap-8">
          <!-- Enrollment Form -->
          <div class="md:col-span-2">
            <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-8">
              <h1 class="text-2xl font-bold mb-6 flex items-center gap-2">
                <i class="ri-user-add-line text-primary"></i>
                التسجيل في الكورس
              </h1>

              @if(session('success'))
              <div class="bg-green-50 border border-green-200 text-green-800 p-4 rounded-lg mb-6 flex items-start gap-3">
                <i class="ri-check-circle-fill text-xl mt-0.5"></i>
                <div>
                  <div class="font-semibold mb-1">تم التسجيل بنجاح!</div>
                  <div class="text-sm">{{ session('success') }}</div>
                </div>
              </div>
              @endif

              @if(session('error'))
              <div class="bg-red-50 border border-red-200 text-red-800 p-4 rounded-lg mb-6 flex items-start gap-3">
                <i class="ri-error-warning-fill text-xl mt-0.5"></i>
                <div>
                  <div class="font-semibold mb-1">خطأ</div>
                  <div class="text-sm">{{ session('error') }}</div>
                </div>
              </div>
              @endif

              <form action="{{ route('interactive-courses.store-enrollment', ['course' => $course->id]) }}" method="POST">
                @csrf

                <!-- Student Info (readonly - from auth) -->
                <div class="mb-6">
                  <label class="block text-sm font-medium text-gray-700 mb-2">معلومات الطالب</label>
                  <div class="bg-gray-50 p-4 rounded-lg">
                    <div class="flex items-center gap-3">
                      <div class="w-12 h-12 rounded-full bg-primary/10 flex items-center justify-center">
                        <i class="ri-user-line text-primary text-xl"></i>
                      </div>
                      <div>
                        <div class="font-semibold">{{ auth()->user()->name }}</div>
                        <div class="text-sm text-gray-600">{{ auth()->user()->email }}</div>
                      </div>
                    </div>
                  </div>
                </div>

                <!-- Why joining -->
                <div class="mb-6">
                  <label for="goals" class="block text-sm font-medium text-gray-700 mb-2">
                    لماذا تريد الانضمام لهذا الكورس؟ (اختياري)
                  </label>
                  <textarea id="goals" name="goals" rows="4"
                            class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary focus:border-transparent"
                            placeholder="أخبرنا عن أهدافك من هذا الكورس..."></textarea>
                </div>

                <!-- Terms & Conditions -->
                <div class="mb-6">
                  <label class="flex items-start gap-3 cursor-pointer">
                    <input type="checkbox" name="terms" required
                           class="mt-1 w-4 h-4 text-primary border-gray-300 rounded focus:ring-primary">
                    <span class="text-sm text-gray-700">
                      أوافق على <a href="#" class="text-primary hover:underline">الشروط والأحكام</a>
                      و <a href="#" class="text-primary hover:underline">سياسة الخصوصية</a>
                    </span>
                  </label>
                </div>

                <!-- Submit Button -->
                <div class="flex gap-4">
                  <button type="submit"
                          class="flex-1 bg-primary text-white py-3 px-6 rounded-lg font-semibold hover:bg-secondary transition-colors flex items-center justify-center gap-2">
                    <i class="ri-check-line"></i>
                    تأكيد التسجيل
                  </button>
                  <a href="{{ route('interactive-courses.show', ['subdomain' => $academy->subdomain, 'course' => $course->id]) }}"
                     class="px-6 py-3 border border-gray-300 text-gray-700 rounded-lg font-semibold hover:bg-gray-50 transition-colors">
                    إلغاء
                  </a>
                </div>

                <!-- Payment Notice -->
                <div class="mt-6 p-4 bg-blue-50 border border-blue-200 rounded-lg">
                  <div class="flex items-start gap-3">
                    <i class="ri-information-line text-blue-600 text-xl mt-0.5"></i>
                    <div class="text-sm text-blue-800">
                      <div class="font-semibold mb-1">معلومة</div>
                      <div>سيتم تفعيل التسجيل فور إتمام عملية الدفع. سيتم التواصل معك قريباً لإتمام الدفع.</div>
                    </div>
                  </div>
                </div>
              </form>
            </div>
          </div>

          <!-- Order Summary -->
          <div>
            <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-6 sticky top-6">
              <h3 class="font-bold mb-4">ملخص الطلب</h3>

              <!-- Course Info -->
              <div class="mb-6">
                <div class="text-sm text-gray-600 mb-1">الكورس</div>
                <div class="font-semibold">{{ $course->title }}</div>
              </div>

              <!-- Price Breakdown -->
              <div class="space-y-3 mb-6 pb-6 border-b border-gray-200">
                <div class="flex justify-between text-sm">
                  <span class="text-gray-600">رسوم الكورس</span>
                  <span class="font-medium">{{ number_format($course->student_price, 2) }} ريال</span>
                </div>

                @if($course->is_enrollment_fee_required && $course->enrollment_fee > 0)
                <div class="flex justify-between text-sm">
                  <span class="text-gray-600">رسوم التسجيل</span>
                  <span class="font-medium">{{ number_format($course->enrollment_fee, 2) }} ريال</span>
                </div>
                @endif
              </div>

              <!-- Total -->
              <div class="flex justify-between items-center mb-6">
                <span class="font-bold">الإجمالي</span>
                <span class="text-2xl font-bold text-primary">
                  {{ number_format($course->student_price + ($course->is_enrollment_fee_required ? $course->enrollment_fee : 0), 2) }} ريال
                </span>
              </div>

              <!-- Course Details -->
              <div class="space-y-2 text-sm text-gray-600">
                <div class="flex items-center gap-2">
                  <i class="ri-calendar-line text-primary"></i>
                  <span>{{ $course->duration_weeks }} أسبوع</span>
                </div>
                <div class="flex items-center gap-2">
                  <i class="ri-vidicon-line text-primary"></i>
                  <span>{{ $course->total_sessions }} جلسة</span>
                </div>
                <div class="flex items-center gap-2">
                  <i class="ri-user-line text-primary"></i>
                  <span>{{ $course->getCurrentEnrollmentCount() }} طالب مسجل</span>
                </div>
                <div class="flex items-center gap-2">
                  <i class="ri-time-line text-primary"></i>
                  <span>{{ $course->session_duration_minutes }} دقيقة للجلسة</span>
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
