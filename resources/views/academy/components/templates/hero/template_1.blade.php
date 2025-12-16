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
@endphp

<!-- Modern Hero Section -->
<section id="main-content" class="relative min-h-screen flex items-center overflow-hidden py-24 sm:py-16 lg:py-0" role="banner">
  <!-- Grid Pattern Background -->
  <div class="absolute inset-0 opacity-60" style="background-image: linear-gradient(rgba(0,0,0,0.2) 1px, transparent 1px), linear-gradient(90deg, rgba(0,0,0,0.2) 1px, transparent 1px); background-size: 100px 100px;"></div>

  <!-- Enhanced Gradient Background -->
  <div class="absolute inset-0 bg-gradient-to-br from-{{ $gradientFrom }}/15 via-white to-{{ $gradientTo }}/15"></div>
  <div class="absolute inset-0 bg-[radial-gradient(circle_at_center,rgba(59,130,246,0.08),transparent_60%)]"></div>

  <div class="relative z-10 max-w-4xl mx-auto px-4 sm:px-6 lg:px-8 w-full text-center">
    <div class="space-y-8">
        <!-- Badge -->
        <div class="inline-flex items-center gap-2 bg-gradient-to-r from-{{ $gradientFrom }}/10 to-{{ $gradientTo }}/10 px-4 py-2 rounded-full border border-primary/20 animate-bounce">
          <div class="w-2 h-2 bg-primary rounded-full animate-pulse"></div>
          <span class="text-sm font-medium text-primary">منصة تعليمية متطورة</span>
        </div>

        <!-- Main Heading -->
        <h1 class="text-4xl sm:text-5xl lg:text-6xl xl:text-7xl font-bold leading-tight text-{{ $brandColor }}-900">
          {{ $heroHeading }}
        </h1>

        <!-- Subheading -->
        <p class="text-xl lg:text-2xl text-gray-600 leading-loose max-w-3xl mx-auto">
          {{ $heroSubheading }}
        </p>

        <!-- CTA Buttons -->
        <div class="flex justify-center">
          <a href="{{ route('student.register', ['subdomain' => $academy->subdomain ?? 'test-academy']) }}"
             class="group relative px-10 py-5 bg-gradient-to-r from-{{ $gradientFrom }} to-{{ $gradientTo }} text-white rounded-2xl font-bold text-lg transition-all duration-300 transform hover:scale-105 hover:shadow-2xl overflow-hidden">
            <div class="absolute inset-0 bg-gradient-to-r from-{{ $gradientTo }} to-{{ $gradientFrom }} opacity-0 group-hover:opacity-100 transition-opacity duration-300"></div>
            <div class="absolute -inset-1 bg-gradient-to-r from-{{ $gradientFrom }} to-{{ $gradientTo }} rounded-2xl blur opacity-30 group-hover:opacity-60 transition-opacity duration-300"></div>
            <div class="absolute inset-0 bg-gradient-to-r from-transparent via-white to-transparent opacity-0 group-hover:opacity-20 transform -skew-x-12 -translate-x-full group-hover:translate-x-full transition-transform duration-700"></div>
            <span class="relative z-10 flex items-center justify-center gap-3">
              <i class="ri-rocket-line text-xl"></i>
              ابدأ رحلتك الآن
            </span>
          </a>
        </div>

        <!-- Academy Sections -->
        <div class="grid grid-cols-2 lg:grid-cols-4 gap-4 sm:gap-6 lg:gap-8 pt-8 border-t border-gray-100">
          <!-- Quran Circles -->
          <div class="bg-white/80 backdrop-blur-sm border border-gray-100 rounded-2xl sm:rounded-3xl p-4 sm:p-6 text-center transition-all duration-300 cursor-pointer hover:-translate-y-1.5 hover:shadow-xl">
            <div class="w-12 h-12 sm:w-14 sm:h-14 rounded-xl sm:rounded-2xl flex items-center justify-center mx-auto mb-3 sm:mb-4 bg-green-500/10 text-green-600">
              <i class="ri-group-line text-2xl sm:text-3xl"></i>
            </div>
            <div class="flex flex-col gap-1">
              <h3 class="text-sm sm:text-base font-semibold text-gray-800 leading-snug">حلقات القرآن</h3>
              <p class="text-xs sm:text-sm text-gray-600 leading-relaxed">تعلم جماعي مع معلمين متخصصين</p>
            </div>
          </div>

          <!-- Individual Quran Learning -->
          <div class="bg-white/80 backdrop-blur-sm border border-gray-100 rounded-2xl sm:rounded-3xl p-4 sm:p-6 text-center transition-all duration-300 cursor-pointer hover:-translate-y-1.5 hover:shadow-xl">
            <div class="w-12 h-12 sm:w-14 sm:h-14 rounded-xl sm:rounded-2xl flex items-center justify-center mx-auto mb-3 sm:mb-4 bg-blue-500/10 text-blue-600">
              <i class="ri-user-line text-2xl sm:text-3xl"></i>
            </div>
            <div class="flex flex-col gap-1">
              <h3 class="text-sm sm:text-base font-semibold text-gray-800 leading-snug">تعليم فردي</h3>
              <p class="text-xs sm:text-sm text-gray-600 leading-relaxed">حفظ شخصي مع متابعة مباشرة</p>
            </div>
          </div>

          <!-- Private Classes -->
          <div class="bg-white/80 backdrop-blur-sm border border-gray-100 rounded-2xl sm:rounded-3xl p-4 sm:p-6 text-center transition-all duration-300 cursor-pointer hover:-translate-y-1.5 hover:shadow-xl">
            <div class="w-12 h-12 sm:w-14 sm:h-14 rounded-xl sm:rounded-2xl flex items-center justify-center mx-auto mb-3 sm:mb-4 bg-amber-500/10 text-amber-600">
              <i class="ri-video-line text-2xl sm:text-3xl"></i>
            </div>
            <div class="flex flex-col gap-1">
              <h3 class="text-sm sm:text-base font-semibold text-gray-800 leading-snug">دروس خاصة</h3>
              <p class="text-xs sm:text-sm text-gray-600 leading-relaxed">تعليم أكاديمي مع معلمين خبراء</p>
            </div>
          </div>

          <!-- Interactive Courses -->
          <div class="bg-white/80 backdrop-blur-sm border border-gray-100 rounded-2xl sm:rounded-3xl p-4 sm:p-6 text-center transition-all duration-300 cursor-pointer hover:-translate-y-1.5 hover:shadow-xl">
            <div class="w-12 h-12 sm:w-14 sm:h-14 rounded-xl sm:rounded-2xl flex items-center justify-center mx-auto mb-3 sm:mb-4 bg-violet-500/10 text-violet-600">
              <i class="ri-computer-line text-2xl sm:text-3xl"></i>
            </div>
            <div class="flex flex-col gap-1">
              <h3 class="text-sm sm:text-base font-semibold text-gray-800 leading-snug">كورسات تفاعلية</h3>
              <p class="text-xs sm:text-sm text-gray-600 leading-relaxed">تعلم متقدم مع تقنيات حديثة</p>
            </div>
          </div>
        </div>
    </div>
  </div>
</section>