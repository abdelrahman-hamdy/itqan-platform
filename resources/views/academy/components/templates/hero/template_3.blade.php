@php
    // Get gradient palette for this academy
    $gradientPalette = $academy?->gradient_palette ?? \App\Enums\GradientPalette::OCEAN_BREEZE;
    $colors = $gradientPalette->getColors();
    $gradientFrom = $colors['from'];
    $gradientTo = $colors['to'];

    // Get brand color for the heading
    $brandColor = $academy?->brand_color?->value ?? 'sky';

    // Get hero heading and subheading with defaults
    $heroHeading = $heading ?? 'تعليم متميز للمستقبل';
    $heroSubheading = $subheading ?? 'انضم إلى آلاف الطلاب الذين يطورون مهاراتهم في القرآن الكريم والتعليم الأكاديمي مع أفضل المعلمين المتخصصين';

    // Get hero image
    $heroImage = $academy?->hero_image ? asset('storage/' . $academy->hero_image) : null;
@endphp

<!-- Hero Section - Template 3: Classic Professional Design -->
<section id="main-content" class="relative py-20 lg:py-24 overflow-hidden bg-white" role="banner">
  <div class="relative z-10 max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
    <div class="grid lg:grid-cols-2 gap-12 items-center">
      <!-- Right Content -->
      <div class="order-2 lg:order-1 space-y-8 text-right">
        <!-- Badge -->
        <div class="inline-flex items-center gap-2 bg-gray-100 px-3 py-1.5 rounded-md">
          <div class="w-1.5 h-1.5 bg-{{ $brandColor }}-600 rounded-full"></div>
          <span class="text-sm font-medium text-gray-700">منصة تعليمية متميزة</span>
        </div>

        <!-- Main Heading - Increased Size -->
        <h1 class="text-4xl sm:text-5xl lg:text-6xl font-bold leading-tight text-gray-900">
          {{ $heroHeading }}
        </h1>

        <!-- Subheading - Increased Size -->
        <p class="text-lg lg:text-xl text-gray-600 leading-relaxed">
          {{ $heroSubheading }}
        </p>

        <!-- CTA Button -->
        <div>
          <a href="{{ route('student.register', ['subdomain' => $academy->subdomain ?? 'test-academy']) }}"
             class="inline-flex items-center gap-2 px-6 py-3 bg-{{ $brandColor }}-600 text-white rounded-md font-semibold hover:bg-{{ $brandColor }}-700 transition-colors">
            <i class="ri-arrow-left-line"></i>
            ابدأ رحلتك الآن
          </a>
        </div>

        <!-- 4 Service Items - Compact Grid -->
        <div class="grid grid-cols-2 gap-3 pt-4">
          <!-- Quran Circles -->
          <div class="flex items-start gap-3 p-3 bg-gray-50 rounded-lg border border-gray-200">
            <div class="w-10 h-10 rounded-md flex items-center justify-center bg-green-500/10 text-green-600 flex-shrink-0">
              <i class="ri-group-line text-xl"></i>
            </div>
            <div class="flex-1 min-w-0">
              <h3 class="text-sm font-semibold text-gray-800 mb-0.5">حلقات القرآن</h3>
              <p class="text-xs text-gray-600 leading-snug">تعلم جماعي مع معلمين متخصصين</p>
            </div>
          </div>

          <!-- Individual Quran Learning -->
          <div class="flex items-start gap-3 p-3 bg-gray-50 rounded-lg border border-gray-200">
            <div class="w-10 h-10 rounded-md flex items-center justify-center bg-blue-500/10 text-blue-600 flex-shrink-0">
              <i class="ri-user-line text-xl"></i>
            </div>
            <div class="flex-1 min-w-0">
              <h3 class="text-sm font-semibold text-gray-800 mb-0.5">تعليم فردي</h3>
              <p class="text-xs text-gray-600 leading-snug">حفظ شخصي مع متابعة مباشرة</p>
            </div>
          </div>

          <!-- Private Classes -->
          <div class="flex items-start gap-3 p-3 bg-gray-50 rounded-lg border border-gray-200">
            <div class="w-10 h-10 rounded-md flex items-center justify-center bg-amber-500/10 text-amber-600 flex-shrink-0">
              <i class="ri-video-line text-xl"></i>
            </div>
            <div class="flex-1 min-w-0">
              <h3 class="text-sm font-semibold text-gray-800 mb-0.5">دروس خاصة</h3>
              <p class="text-xs text-gray-600 leading-snug">تعليم أكاديمي مع معلمين خبراء</p>
            </div>
          </div>

          <!-- Interactive Courses -->
          <div class="flex items-start gap-3 p-3 bg-gray-50 rounded-lg border border-gray-200">
            <div class="w-10 h-10 rounded-md flex items-center justify-center bg-violet-500/10 text-violet-600 flex-shrink-0">
              <i class="ri-computer-line text-xl"></i>
            </div>
            <div class="flex-1 min-w-0">
              <h3 class="text-sm font-semibold text-gray-800 mb-0.5">كورسات تفاعلية</h3>
              <p class="text-xs text-gray-600 leading-snug">تعلم متقدم مع تقنيات حديثة</p>
            </div>
          </div>
        </div>
      </div>

      <!-- Left Image -->
      <div class="order-1 lg:order-2">
        <div class="aspect-[4/3] rounded-lg overflow-hidden bg-gradient-to-br from-{{ $gradientFrom }}/20 to-{{ $gradientTo }}/20 border border-gray-200">
          @if($heroImage)
            <img src="{{ $heroImage }}" alt="{{ $heroHeading }}" class="w-full h-full object-cover">
          @else
            <!-- Placeholder for image - can be replaced with actual image -->
            <div class="w-full h-full flex items-center justify-center">
              <div class="text-center text-gray-400">
                <i class="ri-image-line text-6xl mb-2"></i>
                <p class="text-sm">صورة البطل</p>
              </div>
            </div>
          @endif
        </div>
      </div>
    </div>
  </div>
</section>
