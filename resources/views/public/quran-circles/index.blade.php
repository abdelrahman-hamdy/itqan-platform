<!DOCTYPE html>
<html lang="ar" dir="rtl">

<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>حلقات القرآن الكريم - {{ $academy->name ?? 'أكاديمية إتقان' }}</title>
  <meta name="description" content="انضم إلى حلقات تحفيظ القرآن الكريم مع معلمين متخصصين في {{ $academy->name ?? 'أكاديمية إتقان' }}">
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
    title="حلقات القرآن الكريم"
    subtitle="انضم إلى حلقات تحفيظ القرآن الكريم مع معلمين متخصصين"
    icon="ri-group-line"
    :stats="[
      ['value' => $circles->total(), 'label' => 'حلقة متاحة'],
      ['value' => $circles->where('enrollment_status', 'open')->count(), 'label' => 'مفتوحة للتسجيل'],
      ['value' => 'مجاني', 'label' => 'تجربة أسبوع']
    ]"
  />

  <!-- Circles Grid -->
  <section class="py-16">
    <div class="container mx-auto px-4">
      
      @if($circles->count() > 0)
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4 gap-6">
          @foreach($circles as $circle)
            <x-quran-circle-card :circle="$circle" :academy="$academy" />
          @endforeach
        </div>

        <!-- Pagination -->
        @if($circles->hasPages())
          <div class="mt-12">
            {{ $circles->links() }}
          </div>
        @endif

      @else
        <!-- No Circles -->
        <div class="text-center py-16">
          <div class="w-24 h-24 mx-auto mb-6 rounded-full bg-gray-100 flex items-center justify-center">
            <i class="ri-group-line text-4xl text-gray-400"></i>
          </div>
          <h3 class="text-xl font-bold text-gray-900 mb-2">لا توجد حلقات متاحة حالياً</h3>
          <p class="text-gray-600 mb-6">نعمل على إضافة حلقات جديدة قريباً</p>
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