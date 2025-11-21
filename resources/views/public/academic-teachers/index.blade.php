<!DOCTYPE html>
<html lang="ar" dir="rtl">

<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>المعلمون الأكاديميون - {{ $academy->name ?? 'أكاديمية إتقان' }}</title>
  <meta name="description" content="تصفح المعلمين الأكاديميين المؤهلين والمعتمدين في {{ $academy->name ?? 'أكاديمية إتقان' }}">
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
    title="المعلمون الأكاديميون"
    subtitle="معلمون مؤهلون ومعتمدون لتدريس المواد الأكاديمية"
    icon="ri-user-star-line"
    :stats="[
      ['value' => $teachers->total(), 'label' => 'معلم متاح'],
      ['value' => $teachers->sum('total_students'), 'label' => 'طالب مسجل'],
      ['value' => $teachers->sum('total_sessions'), 'label' => 'جلسة مكتملة']
    ]"
  />

  <!-- Teachers Grid -->
  <section class="py-16">
    <div class="container mx-auto px-4">
      
      @if($teachers->count() > 0)
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4 gap-6">
          @foreach($teachers as $teacher)
            <x-academic-teacher-card :teacher="$teacher" :academy="$academy" />
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
  @include('academy.components.footer', ['academy' => $academy])

</body>
</html>