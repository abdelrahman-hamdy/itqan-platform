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
  <!-- Islamic Geometric Pattern Background -->
  <div class="absolute inset-0 opacity-[0.08]">
    <svg class="w-full h-full" xmlns="http://www.w3.org/2000/svg" preserveAspectRatio="xMidYMid slice">
      <defs>
        <pattern id="islamicPattern" x="0" y="0" width="120" height="120" patternUnits="userSpaceOnUse">
          <!-- Central 8-pointed star -->
          <polygon points="60,10 70,40 100,40 76,58 84,88 60,72 36,88 44,58 20,40 50,40" fill="none" stroke="{{ $gradientFromHex }}" stroke-width="1.5"/>
          <!-- Inner octagon -->
          <polygon points="60,25 75,35 85,50 85,70 75,85 60,95 45,85 35,70 35,50 45,35" fill="none" stroke="{{ $gradientToHex }}" stroke-width="1"/>
          <!-- Connecting lines forming geometric lattice -->
          <line x1="0" y1="60" x2="35" y2="60" stroke="{{ $gradientFromHex }}" stroke-width="1"/>
          <line x1="85" y1="60" x2="120" y2="60" stroke="{{ $gradientFromHex }}" stroke-width="1"/>
          <line x1="60" y1="0" x2="60" y2="25" stroke="{{ $gradientFromHex }}" stroke-width="1"/>
          <line x1="60" y1="95" x2="60" y2="120" stroke="{{ $gradientFromHex }}" stroke-width="1"/>
          <!-- Corner decorations -->
          <path d="M0,0 L30,0 L15,15 L0,30 Z" fill="none" stroke="{{ $gradientToHex }}" stroke-width="0.8"/>
          <path d="M120,0 L90,0 L105,15 L120,30 Z" fill="none" stroke="{{ $gradientToHex }}" stroke-width="0.8"/>
          <path d="M0,120 L30,120 L15,105 L0,90 Z" fill="none" stroke="{{ $gradientToHex }}" stroke-width="0.8"/>
          <path d="M120,120 L90,120 L105,105 L120,90 Z" fill="none" stroke="{{ $gradientToHex }}" stroke-width="0.8"/>
          <!-- Small connecting stars at corners -->
          <circle cx="0" cy="0" r="3" fill="none" stroke="{{ $gradientFromHex }}" stroke-width="0.8"/>
          <circle cx="120" cy="0" r="3" fill="none" stroke="{{ $gradientFromHex }}" stroke-width="0.8"/>
          <circle cx="0" cy="120" r="3" fill="none" stroke="{{ $gradientFromHex }}" stroke-width="0.8"/>
          <circle cx="120" cy="120" r="3" fill="none" stroke="{{ $gradientFromHex }}" stroke-width="0.8"/>
          <!-- Diagonal accents -->
          <line x1="20" y1="20" x2="35" y2="35" stroke="{{ $gradientToHex }}" stroke-width="0.6"/>
          <line x1="100" y1="20" x2="85" y2="35" stroke="{{ $gradientToHex }}" stroke-width="0.6"/>
          <line x1="20" y1="100" x2="35" y2="85" stroke="{{ $gradientToHex }}" stroke-width="0.6"/>
          <line x1="100" y1="100" x2="85" y2="85" stroke="{{ $gradientToHex }}" stroke-width="0.6"/>
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

          <!-- Individual Quran Learning -->
          <a href="#quran" class="group bg-white/80 backdrop-blur-sm border border-gray-100 rounded-2xl sm:rounded-3xl p-4 sm:p-6 text-center transition-all duration-300 cursor-pointer hover:-translate-y-1.5 hover:shadow-xl hover:border-blue-200 block" onclick="event.preventDefault(); document.getElementById('quran')?.scrollIntoView({ behavior: 'smooth', block: 'start' });">
            <div class="w-12 h-12 sm:w-14 sm:h-14 rounded-xl sm:rounded-2xl flex items-center justify-center mx-auto mb-3 sm:mb-4 bg-blue-500/10 text-blue-600 group-hover:bg-blue-500 group-hover:text-white transition-all duration-300">
              <i class="ri-user-line text-2xl sm:text-3xl"></i>
            </div>
            <div class="flex flex-col gap-1">
              <h3 class="text-sm sm:text-base font-semibold text-gray-800 leading-snug group-hover:text-blue-600 transition-colors duration-300">{{ __('academy.services.individual_learning.title') }}</h3>
              <p class="text-xs sm:text-sm text-gray-600 leading-relaxed">{{ __('academy.services.individual_learning.description') }}</p>
            </div>
            <div class="mt-3 opacity-0 group-hover:opacity-100 transition-opacity duration-300">
              <i class="ri-arrow-down-line text-blue-500 animate-bounce"></i>
            </div>
          </a>

          <!-- Private Classes -->
          <a href="#academic" class="group bg-white/80 backdrop-blur-sm border border-gray-100 rounded-2xl sm:rounded-3xl p-4 sm:p-6 text-center transition-all duration-300 cursor-pointer hover:-translate-y-1.5 hover:shadow-xl hover:border-amber-200 block" onclick="event.preventDefault(); document.getElementById('academic')?.scrollIntoView({ behavior: 'smooth', block: 'start' });">
            <div class="w-12 h-12 sm:w-14 sm:h-14 rounded-xl sm:rounded-2xl flex items-center justify-center mx-auto mb-3 sm:mb-4 bg-amber-500/10 text-amber-600 group-hover:bg-amber-500 group-hover:text-white transition-all duration-300">
              <i class="ri-video-line text-2xl sm:text-3xl"></i>
            </div>
            <div class="flex flex-col gap-1">
              <h3 class="text-sm sm:text-base font-semibold text-gray-800 leading-snug group-hover:text-amber-600 transition-colors duration-300">{{ __('academy.services.private_lessons.title') }}</h3>
              <p class="text-xs sm:text-sm text-gray-600 leading-relaxed">{{ __('academy.services.private_lessons.description') }}</p>
            </div>
            <div class="mt-3 opacity-0 group-hover:opacity-100 transition-opacity duration-300">
              <i class="ri-arrow-down-line text-amber-500 animate-bounce"></i>
            </div>
          </a>

          <!-- Interactive Courses -->
          <a href="#courses" class="group bg-white/80 backdrop-blur-sm border border-gray-100 rounded-2xl sm:rounded-3xl p-4 sm:p-6 text-center transition-all duration-300 cursor-pointer hover:-translate-y-1.5 hover:shadow-xl hover:border-violet-200 block" onclick="event.preventDefault(); document.getElementById('courses')?.scrollIntoView({ behavior: 'smooth', block: 'start' });">
            <div class="w-12 h-12 sm:w-14 sm:h-14 rounded-xl sm:rounded-2xl flex items-center justify-center mx-auto mb-3 sm:mb-4 bg-violet-500/10 text-violet-600 group-hover:bg-violet-500 group-hover:text-white transition-all duration-300">
              <i class="ri-computer-line text-2xl sm:text-3xl"></i>
            </div>
            <div class="flex flex-col gap-1">
              <h3 class="text-sm sm:text-base font-semibold text-gray-800 leading-snug group-hover:text-violet-600 transition-colors duration-300">{{ __('academy.services.interactive_courses.title') }}</h3>
              <p class="text-xs sm:text-sm text-gray-600 leading-relaxed">{{ __('academy.services.interactive_courses.description') }}</p>
            </div>
            <div class="mt-3 opacity-0 group-hover:opacity-100 transition-opacity duration-300">
              <i class="ri-arrow-down-line text-violet-500 animate-bounce"></i>
            </div>
          </a>
        </div>
    </div>
  </div>
</section>