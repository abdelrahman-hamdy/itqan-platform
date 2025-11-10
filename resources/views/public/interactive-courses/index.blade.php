<!DOCTYPE html>
<html lang="ar" dir="rtl">

<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>الكورسات التفاعلية - {{ $academy->name ?? 'أكاديمية إتقان' }}</title>
  <meta name="description" content="تصفح أفضل الكورسات التفاعلية المؤهلة في {{ $academy->name ?? 'أكاديمية إتقان' }}">
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
  <!-- Global Styles -->
  @include('components.global-styles')
</head>

<body class="bg-gray-50 font-sans">

  <!-- Public Navigation -->
  @include('components.public-navigation', ['academy' => $academy])

  <!-- Hero Section -->
  <x-public-hero-section 
    :academy="$academy"
    title="الكورسات التفاعلية"
    subtitle="تعلم مع أفضل المعلمين في جلسات تفاعلية مباشرة عبر الإنترنت"
    icon="ri-video-line"
    :stats="[
      ['value' => $courses->total(), 'label' => 'كورس متاح'],
      ['value' => $courses->where('status', 'active')->count(), 'label' => 'كورس نشط'],
      ['value' => $courses->sum('enrolled_students'), 'label' => 'طالب مسجل']
    ]"
  />

  <!-- Courses Grid -->
  <section class="py-16">
    <div class="container mx-auto px-4">
      
      @if($courses->count() > 0)
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4 gap-6">
          @foreach($courses as $course)
            <div class="bg-white rounded-xl shadow-sm border border-gray-200 overflow-hidden card-hover">
              <!-- Course Image/Icon -->
              <div class="p-6 text-center">
                <div class="w-20 h-20 mx-auto mb-4 rounded-full bg-gradient-to-br from-primary to-secondary flex items-center justify-center text-white text-2xl font-bold">
                  @if($course->thumbnail)
                    <img src="{{ asset('storage/' . $course->thumbnail) }}" alt="{{ $course->title }}" class="w-full h-full rounded-full object-cover">
                  @else
                    <i class="ri-video-line"></i>
                  @endif
                </div>
                
                <!-- Course Title -->
                <h3 class="text-lg font-bold text-gray-900 mb-1">{{ $course->title }}</h3>
                <p class="text-sm text-gray-600 mb-2">{{ $course->instructor_name ?? 'مدرس متخصص' }}</p>
                
                <!-- Rating -->
                @if($course->rating > 0)
                  <div class="flex items-center justify-center mb-3">
                    <div class="flex text-yellow-400">
                      @for($i = 1; $i <= 5; $i++)
                        <i class="ri-star-{{ $i <= $course->rating ? 'fill' : 'line' }} text-sm"></i>
                      @endfor
                    </div>
                    <span class="text-sm text-gray-600 mr-2">({{ $course->rating }})</span>
                  </div>
                @endif
              </div>

              <!-- Course Info -->
              <div class="px-6 pb-4 space-y-3">
                <!-- Duration -->
                <div class="flex items-center text-sm text-gray-600">
                  <i class="ri-time-line ml-2 text-primary"></i>
                  <span>{{ $course->duration ?? 'غير محدد' }}</span>
                </div>

                <!-- Students Count -->
                <div class="flex items-center text-sm text-gray-600">
                  <i class="ri-group-line ml-2 text-primary"></i>
                  <span>{{ $course->enrolled_students ?? 0 }} طالب</span>
                </div>

                <!-- Level -->
                @if($course->level)
                  <div class="flex items-center text-sm text-gray-600">
                    <i class="ri-book-open-line ml-2 text-primary"></i>
                    <span>{{ $course->level }}</span>
                  </div>
                @endif

                <!-- Price -->
                <div class="flex items-center text-sm text-gray-600">
                  <i class="ri-money-dollar-circle-line ml-2 text-primary"></i>
                  <span>{{ $course->price ? $course->price . ' ريال' : 'مجاني' }}</span>
                </div>
              </div>

              <!-- Action Button -->
              <div class="px-6 pb-6">
                <a href="{{ route('interactive-courses.show', ['subdomain' => $academy->subdomain, 'course' => $course->id]) }}"
                   class="w-full bg-primary text-white py-3 px-4 rounded-lg text-center font-medium hover:bg-secondary transition-colors block">
                  عرض التفاصيل
                </a>
              </div>
            </div>
          @endforeach
        </div>

        <!-- Pagination -->
        @if($courses->hasPages())
          <div class="mt-12">
            {{ $courses->links() }}
          </div>
        @endif

      @else
        <!-- No Courses -->
        <div class="text-center py-16">
          <div class="w-24 h-24 mx-auto mb-6 rounded-full bg-gray-100 flex items-center justify-center">
            <i class="ri-video-line text-4xl text-gray-400"></i>
          </div>
          <h3 class="text-xl font-bold text-gray-900 mb-2">لا توجد كورسات تفاعلية متاحة حالياً</h3>
          <p class="text-gray-600 mb-6">نعمل على إضافة كورسات تفاعلية جديدة قريباً</p>
          <a href="{{ route('academy.home', ['subdomain' => $academy->subdomain]) }}"
             class="bg-primary text-white px-6 py-3 rounded-lg hover:bg-secondary transition-colors">
            العودة للرئيسية
          </a>
        </div>
      @endif
    </div>
  </section>

  <!-- Footer -->
  @include('academy.components.footer', ['academy' => $academy])

</body>
</html>
