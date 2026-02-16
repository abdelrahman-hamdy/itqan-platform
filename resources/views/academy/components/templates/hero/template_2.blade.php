@php
    // Get brand color with hex values
    $brandColor = $academy?->brand_color ?? \App\Enums\TailwindColor::SKY;
    $brandHex50 = $brandColor->getHexValue(50);
    $brandHex200 = $brandColor->getHexValue(200);
    $brandHex500 = $brandColor->getHexValue(500);
    $brandHex600 = $brandColor->getHexValue(600);
    $brandHex700 = $brandColor->getHexValue(700);

    $heroHeading = $heading ?? __('academy.hero.default_heading');
    $heroSubheading = $subheading ?? __('academy.hero.default_subheading');
@endphp

<!-- Hero Section - Template 2: Modern Abstract Design -->
<section id="hero-section" class="relative flex items-center overflow-hidden bg-white py-24 sm:py-16 lg:py-0" role="main" style="min-height: min(100vh, 56rem);">
  <!-- Abstract Background with Subtle Orbs -->
  <div class="absolute inset-0 overflow-hidden">
    <!-- Subtle Gradient Overlay -->
    <div class="absolute inset-0 opacity-40" style="background: linear-gradient(to bottom right, {{ $brandHex50 }}, white, #faf5ff);"></div>

    <!-- Large Animated Gradient Orbs with Reduced Opacity -->
    <div class="absolute top-0 -left-60 w-[600px] h-[600px] rounded-full mix-blend-multiply filter blur-3xl opacity-20 animate-blob" style="background-color: {{ $brandHex200 }};"></div>
    <div class="absolute top-0 -right-60 w-[600px] h-[600px] bg-purple-200 rounded-full mix-blend-multiply filter blur-3xl opacity-20 animate-blob animation-delay-2000"></div>
    <div class="absolute bottom-0 left-1/2 -translate-x-1/2 w-[600px] h-[600px] bg-pink-200 rounded-full mix-blend-multiply filter blur-3xl opacity-20 animate-blob animation-delay-4000"></div>

    <!-- Subtle Dot Pattern -->
    <div class="absolute inset-0 opacity-10" style="background-image: radial-gradient(circle, rgba(0,0,0,0.1) 1px, transparent 1px); background-size: 40px 40px;"></div>
  </div>

  <div class="relative z-10 max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 w-full">
    <div class="grid lg:grid-cols-2 gap-12 lg:gap-16 items-center">
      <!-- Left Content -->
      <div class="space-y-8 text-center lg:text-right">
        <!-- Badge -->
        <div class="inline-flex items-center gap-2 px-5 py-2.5 rounded-full shadow-sm animate-bounce" style="background-color: {{ $brandHex50 }}; border: 1px solid {{ $brandHex200 }};">
          <div class="w-2 h-2 rounded-full animate-pulse" style="background-color: {{ $brandHex500 }};"></div>
          <span class="text-sm font-semibold" style="color: {{ $brandHex700 }};">{{ __('academy.hero.badge') }}</span>
        </div>

        <!-- Main Heading -->
        <h1 class="text-4xl sm:text-5xl lg:text-6xl font-bold leading-tight text-gray-900">
          {{ $heroHeading }}
        </h1>

        <!-- Subheading -->
        <p class="text-lg lg:text-xl text-gray-600 leading-relaxed">
          {{ $heroSubheading }}
        </p>

        <!-- CTA Button -->
        <div class="flex justify-center lg:justify-start">
          <a href="{{ route('student.register', ['subdomain' => $academy->subdomain ?? 'test-academy']) }}"
             class="group relative inline-flex items-center gap-3 px-8 py-4 text-white rounded-2xl font-semibold text-lg transition-all duration-300 hover:shadow-xl hover:-translate-y-1"
             style="background-color: {{ $brandHex600 }};"
             onmouseover="this.style.backgroundColor='{{ $brandHex700 }}'"
             onmouseout="this.style.backgroundColor='{{ $brandHex600 }}'">
            <span>{{ __('academy.hero.cta_button') }}</span>
            <i class="ri-arrow-left-line text-xl transition-transform duration-300 group-hover:-translate-x-1 ltr:rotate-180 ltr:group-hover:translate-x-1"></i>
          </a>
        </div>
      </div>

      <!-- Right Content - Items Grid -->
      @if($academy->hero_show_boxes ?? true)
      @php
          $visibleBoxes = collect([
              $academy->quran_show_circles ?? true,
              $academy->quran_show_teachers ?? true,
              $academy->academic_show_teachers ?? true,
              $academy->academic_show_courses ?? true,
          ])->filter()->count();
      @endphp
      @if($visibleBoxes > 0)
      <div class="grid {{ $visibleBoxes === 1 ? 'grid-cols-1 max-w-sm mx-auto' : 'grid-cols-2' }} gap-4 lg:gap-6">
        @if($academy->quran_show_circles ?? true)
        <!-- Quran Circles -->
        <div class="group relative bg-white rounded-xl sm:rounded-2xl p-4 sm:p-6 shadow-lg border border-gray-100 transition-all duration-300 hover:shadow-2xl hover:-translate-y-2 cursor-pointer overflow-hidden">
          <div class="absolute inset-0 bg-gradient-to-br from-green-50 to-emerald-50 opacity-0 group-hover:opacity-100 transition-opacity duration-300"></div>
          <div class="relative z-10 space-y-3 sm:space-y-4">
            <div class="w-12 h-12 sm:w-14 sm:h-14 bg-gradient-to-br from-green-400 to-emerald-500 rounded-xl sm:rounded-2xl flex items-center justify-center shadow-lg transform group-hover:scale-110 transition-transform duration-300">
              <i class="ri-group-line text-2xl sm:text-3xl text-white"></i>
            </div>
            <div>
              <h3 class="text-sm sm:text-base font-bold text-gray-900 mb-1">{{ __('academy.services.quran_circles.title') }}</h3>
              <p class="text-xs sm:text-sm text-gray-600">{{ __('academy.services.quran_circles.description') }}</p>
            </div>
          </div>
          <div class="absolute -bottom-2 -right-2 w-16 h-16 sm:w-20 sm:h-20 bg-green-100 rounded-full opacity-20 group-hover:scale-150 transition-transform duration-500"></div>
        </div>
        @endif

        @if($academy->quran_show_teachers ?? true)
        <!-- Individual Quran Learning -->
        <div class="group relative bg-white rounded-xl sm:rounded-2xl p-4 sm:p-6 shadow-lg border border-gray-100 transition-all duration-300 hover:shadow-2xl hover:-translate-y-2 cursor-pointer overflow-hidden">
          <div class="absolute inset-0 bg-gradient-to-br from-blue-50 to-cyan-50 opacity-0 group-hover:opacity-100 transition-opacity duration-300"></div>
          <div class="relative z-10 space-y-3 sm:space-y-4">
            <div class="w-12 h-12 sm:w-14 sm:h-14 bg-gradient-to-br from-blue-400 to-cyan-500 rounded-xl sm:rounded-2xl flex items-center justify-center shadow-lg transform group-hover:scale-110 transition-transform duration-300">
              <i class="ri-user-line text-2xl sm:text-3xl text-white"></i>
            </div>
            <div>
              <h3 class="text-sm sm:text-base font-bold text-gray-900 mb-1">{{ __('academy.services.individual_learning.title') }}</h3>
              <p class="text-xs sm:text-sm text-gray-600">{{ __('academy.services.individual_learning.description') }}</p>
            </div>
          </div>
          <div class="absolute -bottom-2 -right-2 w-16 h-16 sm:w-20 sm:h-20 bg-blue-100 rounded-full opacity-20 group-hover:scale-150 transition-transform duration-500"></div>
        </div>
        @endif

        @if($academy->academic_show_teachers ?? true)
        <!-- Private Classes -->
        <div class="group relative bg-white rounded-xl sm:rounded-2xl p-4 sm:p-6 shadow-lg border border-gray-100 transition-all duration-300 hover:shadow-2xl hover:-translate-y-2 cursor-pointer overflow-hidden">
          <div class="absolute inset-0 bg-gradient-to-br from-amber-50 to-orange-50 opacity-0 group-hover:opacity-100 transition-opacity duration-300"></div>
          <div class="relative z-10 space-y-3 sm:space-y-4">
            <div class="w-12 h-12 sm:w-14 sm:h-14 bg-gradient-to-br from-amber-400 to-orange-500 rounded-xl sm:rounded-2xl flex items-center justify-center shadow-lg transform group-hover:scale-110 transition-transform duration-300">
              <i class="ri-video-line text-2xl sm:text-3xl text-white"></i>
            </div>
            <div>
              <h3 class="text-sm sm:text-base font-bold text-gray-900 mb-1">{{ __('academy.services.private_lessons.title') }}</h3>
              <p class="text-xs sm:text-sm text-gray-600">{{ __('academy.services.private_lessons.description') }}</p>
            </div>
          </div>
          <div class="absolute -bottom-2 -right-2 w-16 h-16 sm:w-20 sm:h-20 bg-amber-100 rounded-full opacity-20 group-hover:scale-150 transition-transform duration-500"></div>
        </div>
        @endif

        @if($academy->academic_show_courses ?? true)
        <!-- Interactive Courses -->
        <div class="group relative bg-white rounded-xl sm:rounded-2xl p-4 sm:p-6 shadow-lg border border-gray-100 transition-all duration-300 hover:shadow-2xl hover:-translate-y-2 cursor-pointer overflow-hidden">
          <div class="absolute inset-0 bg-gradient-to-br from-violet-50 to-purple-50 opacity-0 group-hover:opacity-100 transition-opacity duration-300"></div>
          <div class="relative z-10 space-y-3 sm:space-y-4">
            <div class="w-12 h-12 sm:w-14 sm:h-14 bg-gradient-to-br from-violet-400 to-purple-500 rounded-xl sm:rounded-2xl flex items-center justify-center shadow-lg transform group-hover:scale-110 transition-transform duration-300">
              <i class="ri-computer-line text-2xl sm:text-3xl text-white"></i>
            </div>
            <div>
              <h3 class="text-sm sm:text-base font-bold text-gray-900 mb-1">{{ __('academy.services.interactive_courses.title') }}</h3>
              <p class="text-xs sm:text-sm text-gray-600">{{ __('academy.services.interactive_courses.description') }}</p>
            </div>
          </div>
          <div class="absolute -bottom-2 -right-2 w-16 h-16 sm:w-20 sm:h-20 bg-violet-100 rounded-full opacity-20 group-hover:scale-150 transition-transform duration-500"></div>
        </div>
        @endif
      </div>
      @endif
      @endif
    </div>
  </div>
</section>

<style>
  @keyframes blob {
    0%, 100% {
      transform: translate(0, 0) scale(1);
    }
    33% {
      transform: translate(30px, -50px) scale(1.1);
    }
    66% {
      transform: translate(-20px, 20px) scale(0.9);
    }
  }

  .animate-blob {
    animation: blob 7s infinite;
  }

  .animation-delay-2000 {
    animation-delay: 2s;
  }

  .animation-delay-4000 {
    animation-delay: 4s;
  }
</style>
