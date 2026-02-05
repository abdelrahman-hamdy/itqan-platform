@php
    // Get gradient palette for this academy
    $gradientPalette = $academy?->gradient_palette ?? \App\Enums\GradientPalette::OCEAN_BREEZE;
    $hexColors = $gradientPalette->getHexColors();
    $gradientFromHex = $hexColors['from'];
    $gradientToHex = $hexColors['to'];

    // Get brand color for the heading (use hex value)
    $brandColor = $academy?->brand_color ?? \App\Enums\TailwindColor::SKY;
    $brandColorHex = $brandColor->getHexValue(900); // Dark shade for heading

    // Get hero heading and subheading with defaults
    $heroHeading = $heading ?? __('academy.hero.default_heading');
    $heroSubheading = $subheading ?? __('academy.hero.default_subheading');
@endphp

<!-- Modern Hero Section -->
<section id="hero-section" class="relative min-h-screen flex items-center justify-center overflow-hidden py-24 sm:py-16 lg:py-0" role="banner">
  <!-- Islamic Geometric Pattern Background - Elegant Arabesque -->
  <div class="absolute inset-0 opacity-[0.06]">
    <svg class="w-full h-full" xmlns="http://www.w3.org/2000/svg" preserveAspectRatio="xMidYMid slice">
      <defs>
        <pattern id="islamicPattern" x="0" y="0" width="80" height="80" patternUnits="userSpaceOnUse">
          <!-- Central arabesque flower -->
          <circle cx="40" cy="40" r="12" fill="none" stroke="{{ $gradientFromHex }}" stroke-width="1"/>
          <circle cx="40" cy="40" r="6" fill="none" stroke="{{ $gradientToHex }}" stroke-width="0.8"/>
          <!-- Four petals -->
          <ellipse cx="40" cy="20" rx="4" ry="8" fill="none" stroke="{{ $gradientFromHex }}" stroke-width="0.8"/>
          <ellipse cx="40" cy="60" rx="4" ry="8" fill="none" stroke="{{ $gradientFromHex }}" stroke-width="0.8"/>
          <ellipse cx="20" cy="40" rx="8" ry="4" fill="none" stroke="{{ $gradientFromHex }}" stroke-width="0.8"/>
          <ellipse cx="60" cy="40" rx="8" ry="4" fill="none" stroke="{{ $gradientFromHex }}" stroke-width="0.8"/>
          <!-- Corner connecting arcs -->
          <path d="M0,0 Q20,20 0,40" fill="none" stroke="{{ $gradientToHex }}" stroke-width="0.6"/>
          <path d="M80,0 Q60,20 80,40" fill="none" stroke="{{ $gradientToHex }}" stroke-width="0.6"/>
          <path d="M0,80 Q20,60 0,40" fill="none" stroke="{{ $gradientToHex }}" stroke-width="0.6"/>
          <path d="M80,80 Q60,60 80,40" fill="none" stroke="{{ $gradientToHex }}" stroke-width="0.6"/>
          <!-- Small dots at intersections -->
          <circle cx="0" cy="0" r="2" fill="{{ $gradientFromHex }}" opacity="0.4"/>
          <circle cx="80" cy="0" r="2" fill="{{ $gradientFromHex }}" opacity="0.4"/>
          <circle cx="0" cy="80" r="2" fill="{{ $gradientFromHex }}" opacity="0.4"/>
          <circle cx="80" cy="80" r="2" fill="{{ $gradientFromHex }}" opacity="0.4"/>
        </pattern>
      </defs>
      <rect width="100%" height="100%" fill="url(#islamicPattern)"/>
    </svg>
  </div>

  <!-- Enhanced Gradient Background -->
  <div class="absolute inset-0" style="background: linear-gradient(to bottom right, {{ $gradientFromHex }}26, white, {{ $gradientToHex }}26);"></div>
  <div class="absolute inset-0" style="background: radial-gradient(circle at center, {{ $gradientFromHex }}14, transparent 60%);"></div>

  <div class="relative z-10 w-full max-w-4xl mx-auto px-4 sm:px-6 lg:px-8 text-center">
    <div class="space-y-8">
        <!-- Badge -->
        <div class="inline-flex items-center gap-2 px-4 py-2 rounded-full border animate-bounce" style="background: linear-gradient(to right, {{ $gradientFromHex }}1a, {{ $gradientToHex }}1a); border-color: {{ $gradientFromHex }}33;">
          <div class="w-2 h-2 rounded-full animate-pulse" style="background-color: {{ $gradientFromHex }};"></div>
          <span class="text-sm font-medium" style="color: {{ $gradientFromHex }};">{{ __('academy.hero.badge') }}</span>
        </div>

        <!-- Main Heading -->
        <h1 class="text-4xl sm:text-5xl lg:text-6xl xl:text-7xl font-bold leading-tight" style="color: {{ $brandColorHex }};">
          {{ $heroHeading }}
        </h1>

        <!-- Subheading -->
        <p class="text-xl lg:text-2xl text-gray-600 leading-loose max-w-3xl mx-auto">
          {{ $heroSubheading }}
        </p>

        <!-- CTA Buttons -->
        <div class="flex justify-center">
          <a href="{{ route('student.register', ['subdomain' => $academy->subdomain ?? 'test-academy']) }}"
             class="group relative px-10 py-5 text-white rounded-2xl font-bold text-lg transition-all duration-300 transform hover:scale-105 hover:shadow-2xl overflow-hidden"
             style="background: linear-gradient(to right, {{ $gradientFromHex }}, {{ $gradientToHex }});">
            <div class="absolute inset-0 opacity-0 group-hover:opacity-100 transition-opacity duration-300" style="background: linear-gradient(to right, {{ $gradientToHex }}, {{ $gradientFromHex }});"></div>
            <div class="absolute -inset-1 rounded-2xl blur opacity-30 group-hover:opacity-60 transition-opacity duration-300" style="background: linear-gradient(to right, {{ $gradientFromHex }}, {{ $gradientToHex }});"></div>
            <div class="absolute inset-0 bg-gradient-to-r from-transparent via-white to-transparent opacity-0 group-hover:opacity-20 transform -skew-x-12 -translate-x-full group-hover:translate-x-full transition-transform duration-700"></div>
            <span class="relative z-10 flex items-center justify-center gap-3">
              <i class="ri-rocket-line text-xl"></i>
              {{ __('academy.hero.cta_button') }}
            </span>
          </a>
        </div>

        <!-- Academy Sections -->
        <div class="grid grid-cols-2 lg:grid-cols-4 gap-4 sm:gap-6 lg:gap-8 pt-8 border-t border-gray-100">
          <!-- Quran Circles -->
          <a href="#quran" class="group bg-white/80 backdrop-blur-sm border border-gray-100 rounded-2xl sm:rounded-3xl p-4 sm:p-6 text-center transition-all duration-300 cursor-pointer hover:-translate-y-1.5 hover:shadow-xl hover:border-green-200 block" onclick="event.preventDefault(); document.getElementById('quran')?.scrollIntoView({ behavior: 'smooth', block: 'start' });">
            <div class="w-12 h-12 sm:w-14 sm:h-14 rounded-xl sm:rounded-2xl flex items-center justify-center mx-auto mb-3 sm:mb-4 bg-green-500/10 text-green-600 group-hover:bg-green-500 group-hover:text-white transition-all duration-300">
              <i class="ri-group-line text-2xl sm:text-3xl"></i>
            </div>
            <div class="flex flex-col gap-1">
              <h3 class="text-sm sm:text-base font-semibold text-gray-800 leading-snug group-hover:text-green-600 transition-colors duration-300">{{ __('academy.services.quran_circles.title') }}</h3>
              <p class="text-xs sm:text-sm text-gray-600 leading-relaxed">{{ __('academy.services.quran_circles.description') }}</p>
            </div>
            <div class="mt-3 opacity-0 group-hover:opacity-100 transition-opacity duration-300">
              <i class="ri-arrow-down-line text-green-500 animate-bounce"></i>
            </div>
          </a>

          <!-- Individual Quran Learning - Orange -->
          <a href="#quran-teachers" class="group bg-white/80 backdrop-blur-sm border border-gray-100 rounded-2xl sm:rounded-3xl p-4 sm:p-6 text-center transition-all duration-300 cursor-pointer hover:-translate-y-1.5 hover:shadow-xl hover:border-orange-200 block" onclick="event.preventDefault(); const section = document.getElementById('quran'); const teachersDiv = document.getElementById('quran-teachers'); if (section) { const alpineData = Alpine.$data(section); if (alpineData && alpineData.activeTab !== undefined) { alpineData.activeTab = 'teachers'; } } section?.scrollIntoView({ behavior: 'smooth', block: 'start' }); setTimeout(() => teachersDiv?.scrollIntoView({ behavior: 'smooth', block: 'start' }), 100);">
            <div class="w-12 h-12 sm:w-14 sm:h-14 rounded-xl sm:rounded-2xl flex items-center justify-center mx-auto mb-3 sm:mb-4 bg-orange-500/10 text-orange-600 group-hover:bg-orange-500 group-hover:text-white transition-all duration-300">
              <i class="ri-user-line text-2xl sm:text-3xl"></i>
            </div>
            <div class="flex flex-col gap-1">
              <h3 class="text-sm sm:text-base font-semibold text-gray-800 leading-snug group-hover:text-orange-600 transition-colors duration-300">{{ __('academy.services.individual_learning.title') }}</h3>
              <p class="text-xs sm:text-sm text-gray-600 leading-relaxed">{{ __('academy.services.individual_learning.description') }}</p>
            </div>
            <div class="mt-3 opacity-0 group-hover:opacity-100 transition-opacity duration-300">
              <i class="ri-arrow-down-line text-orange-500 animate-bounce"></i>
            </div>
          </a>

          <!-- Private Classes - Violet -->
          <a href="#academic" class="group bg-white/80 backdrop-blur-sm border border-gray-100 rounded-2xl sm:rounded-3xl p-4 sm:p-6 text-center transition-all duration-300 cursor-pointer hover:-translate-y-1.5 hover:shadow-xl hover:border-violet-200 block" onclick="event.preventDefault(); document.getElementById('academic')?.scrollIntoView({ behavior: 'smooth', block: 'start' });">
            <div class="w-12 h-12 sm:w-14 sm:h-14 rounded-xl sm:rounded-2xl flex items-center justify-center mx-auto mb-3 sm:mb-4 bg-violet-500/10 text-violet-600 group-hover:bg-violet-500 group-hover:text-white transition-all duration-300">
              <i class="ri-video-line text-2xl sm:text-3xl"></i>
            </div>
            <div class="flex flex-col gap-1">
              <h3 class="text-sm sm:text-base font-semibold text-gray-800 leading-snug group-hover:text-violet-600 transition-colors duration-300">{{ __('academy.services.private_lessons.title') }}</h3>
              <p class="text-xs sm:text-sm text-gray-600 leading-relaxed">{{ __('academy.services.private_lessons.description') }}</p>
            </div>
            <div class="mt-3 opacity-0 group-hover:opacity-100 transition-opacity duration-300">
              <i class="ri-arrow-down-line text-violet-500 animate-bounce"></i>
            </div>
          </a>

          <!-- Interactive Courses - Blue -->
          <a href="#courses" class="group bg-white/80 backdrop-blur-sm border border-gray-100 rounded-2xl sm:rounded-3xl p-4 sm:p-6 text-center transition-all duration-300 cursor-pointer hover:-translate-y-1.5 hover:shadow-xl hover:border-blue-200 block" onclick="event.preventDefault(); document.getElementById('courses')?.scrollIntoView({ behavior: 'smooth', block: 'start' });">
            <div class="w-12 h-12 sm:w-14 sm:h-14 rounded-xl sm:rounded-2xl flex items-center justify-center mx-auto mb-3 sm:mb-4 bg-blue-500/10 text-blue-600 group-hover:bg-blue-500 group-hover:text-white transition-all duration-300">
              <i class="ri-computer-line text-2xl sm:text-3xl"></i>
            </div>
            <div class="flex flex-col gap-1">
              <h3 class="text-sm sm:text-base font-semibold text-gray-800 leading-snug group-hover:text-blue-600 transition-colors duration-300">{{ __('academy.services.interactive_courses.title') }}</h3>
              <p class="text-xs sm:text-sm text-gray-600 leading-relaxed">{{ __('academy.services.interactive_courses.description') }}</p>
            </div>
            <div class="mt-3 opacity-0 group-hover:opacity-100 transition-opacity duration-300">
              <i class="ri-arrow-down-line text-blue-500 animate-bounce"></i>
            </div>
          </a>
        </div>
    </div>
  </div>
</section>