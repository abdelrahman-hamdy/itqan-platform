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
  <!-- Classic Islamic Rosette Pattern Background -->
  <div class="absolute inset-0 opacity-[0.12]">
    <svg width="100%" height="100%" xmlns="http://www.w3.org/2000/svg">
      <defs>
        <pattern id="classic-islamic-rosette" x="0" y="0" width="200" height="200" patternUnits="userSpaceOnUse">
          <g stroke-linecap="round" stroke-linejoin="round" fill="none">
            <!-- Main geometric frame - Primary color -->
            <path d="M100 0 L135.36 35.36 L170.71 0 M200 29.29 L164.64 64.64 L200 100 M170.71 200 L135.36 164.64 L100 200 M0 170.71 L35.36 135.36 L0 100 M29.29 0 L64.64 35.36 L100 0 M200 170.71 L164.64 135.36 L200 100 M100 200 L64.64 164.64 L29.29 200 M0 29.29 L35.36 64.64 L0 100 M64.64 35.36 L135.36 35.36 L164.64 64.64 L164.64 135.36 L135.36 164.64 L64.64 164.64 L35.36 135.36 L35.36 64.64 Z M64.64 35.36 L35.36 64.64 M135.36 35.36 L164.64 64.64 M164.64 135.36 L135.36 164.64 M35.36 135.36 L64.64 164.64"
                  stroke="{{ $gradientFromHex }}"
                  stroke-width="3"/>
            <!-- Inner rosette - Secondary color -->
            <path d="M100 20 L128.28 48.28 M100 180 L128.28 151.72 M100 180 L71.72 151.72 M100 20 L71.72 48.28 M180 100 L151.72 128.28 M20 100 L48.28 128.28 M20 100 L48.28 71.72 M180 100 L151.72 71.72 M71.72 48.28 L128.28 48.28 L151.72 71.72 L151.72 128.28 L128.28 151.72 L71.72 151.72 L48.28 128.28 L48.28 71.72 Z"
                  stroke="{{ $gradientToHex }}"
                  stroke-width="1.5"
                  opacity="0.85"/>
          </g>
        </pattern>
      </defs>
      <rect x="0" y="0" width="100%" height="100%" fill="url(#classic-islamic-rosette)"/>
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