@php
    // Get gradient palette for this academy
    $gradientPalette = $academy?->gradient_palette ?? \App\Enums\GradientPalette::OCEAN_BREEZE;
    $hexColors = $gradientPalette->getHexColors();
    $gradientFromHex = $hexColors['from'];
    $gradientToHex = $hexColors['to'];

    // Get brand color with hex values
    $brandColor = $academy?->brand_color ?? \App\Enums\TailwindColor::SKY;
    $brandHex600 = $brandColor->getHexValue(600);
    $brandHex700 = $brandColor->getHexValue(700);

    // Get hero heading and subheading with defaults
    $heroHeading = $heading ?? __('academy.hero.default_heading');
    $heroSubheading = $subheading ?? __('academy.hero.default_subheading');

    // Get hero image
    $heroImage = $academy?->hero_image ? asset('storage/' . $academy->hero_image) : null;
@endphp

<!-- Hero Section - Template 3: Classic Professional Design -->
<section id="hero-section" class="relative py-20 sm:py-16 lg:py-24 overflow-hidden bg-white" role="banner">
  <div class="relative z-10 max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
    <div class="grid lg:grid-cols-2 gap-8 lg:gap-12 items-center">
      <!-- Right Content -->
      <div class="order-2 lg:order-1 space-y-6 sm:space-y-8 text-center lg:text-right">
        <!-- Badge -->
        <div class="inline-flex items-center gap-2 bg-gray-100 px-3 py-1.5 rounded-md">
          <div class="w-1.5 h-1.5 rounded-full" style="background-color: {{ $brandHex600 }};"></div>
          <span class="text-sm font-medium text-gray-700">{{ __('academy.hero.badge') }}</span>
        </div>

        <!-- Main Heading - Increased Size -->
        <h1 class="text-3xl sm:text-4xl lg:text-5xl xl:text-6xl font-bold leading-tight text-gray-900">
          {{ $heroHeading }}
        </h1>

        <!-- Subheading - Increased Size -->
        <p class="text-base sm:text-lg lg:text-xl text-gray-600 leading-relaxed">
          {{ $heroSubheading }}
        </p>

        <!-- CTA Button -->
        <div class="flex justify-center lg:justify-start">
          <a href="{{ route('student.register', ['subdomain' => $academy->subdomain ?? 'test-academy']) }}"
             class="inline-flex items-center gap-2 px-6 py-3 text-white rounded-md font-semibold transition-colors"
             style="background-color: {{ $brandHex600 }};"
             onmouseover="this.style.backgroundColor='{{ $brandHex700 }}'"
             onmouseout="this.style.backgroundColor='{{ $brandHex600 }}'">
            <i class="ri-arrow-left-line ltr:rotate-180"></i>
            {{ __('academy.hero.cta_button') }}
          </a>
        </div>

        <!-- Service Items - Compact Grid -->
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
        <div class="grid {{ $visibleBoxes === 1 ? 'grid-cols-1' : 'grid-cols-2' }} gap-3 pt-4">
          @if($academy->quran_show_circles ?? true)
          <!-- Quran Circles -->
          <div class="flex items-start gap-3 p-3 bg-gray-50 rounded-lg border border-gray-200">
            <div class="w-10 h-10 rounded-md flex items-center justify-center bg-green-500/10 text-green-600 flex-shrink-0">
              <i class="ri-group-line text-xl"></i>
            </div>
            <div class="flex-1 min-w-0">
              <h3 class="text-sm font-semibold text-gray-800 mb-0.5">{{ __('academy.services.quran_circles.title') }}</h3>
              <p class="text-xs text-gray-600 leading-snug">{{ __('academy.services.quran_circles.description') }}</p>
            </div>
          </div>
          @endif

          @if($academy->quran_show_teachers ?? true)
          <!-- Individual Quran Learning -->
          <div class="flex items-start gap-3 p-3 bg-gray-50 rounded-lg border border-gray-200">
            <div class="w-10 h-10 rounded-md flex items-center justify-center bg-blue-500/10 text-blue-600 flex-shrink-0">
              <i class="ri-user-line text-xl"></i>
            </div>
            <div class="flex-1 min-w-0">
              <h3 class="text-sm font-semibold text-gray-800 mb-0.5">{{ __('academy.services.individual_learning.title') }}</h3>
              <p class="text-xs text-gray-600 leading-snug">{{ __('academy.services.individual_learning.description') }}</p>
            </div>
          </div>
          @endif

          @if($academy->academic_show_teachers ?? true)
          <!-- Private Classes -->
          <div class="flex items-start gap-3 p-3 bg-gray-50 rounded-lg border border-gray-200">
            <div class="w-10 h-10 rounded-md flex items-center justify-center bg-amber-500/10 text-amber-600 flex-shrink-0">
              <i class="ri-video-line text-xl"></i>
            </div>
            <div class="flex-1 min-w-0">
              <h3 class="text-sm font-semibold text-gray-800 mb-0.5">{{ __('academy.services.private_lessons.title') }}</h3>
              <p class="text-xs text-gray-600 leading-snug">{{ __('academy.services.private_lessons.description') }}</p>
            </div>
          </div>
          @endif

          @if($academy->academic_show_courses ?? true)
          <!-- Interactive Courses -->
          <div class="flex items-start gap-3 p-3 bg-gray-50 rounded-lg border border-gray-200">
            <div class="w-10 h-10 rounded-md flex items-center justify-center bg-violet-500/10 text-violet-600 flex-shrink-0">
              <i class="ri-computer-line text-xl"></i>
            </div>
            <div class="flex-1 min-w-0">
              <h3 class="text-sm font-semibold text-gray-800 mb-0.5">{{ __('academy.services.interactive_courses.title') }}</h3>
              <p class="text-xs text-gray-600 leading-snug">{{ __('academy.services.interactive_courses.description') }}</p>
            </div>
          </div>
          @endif
        </div>
        @endif
        @endif
      </div>

      <!-- Left Image -->
      <div class="order-1 lg:order-2">
        <div class="aspect-[4/3] rounded-lg overflow-hidden border border-gray-200" style="background: linear-gradient(to bottom right, {{ $gradientFromHex }}33, {{ $gradientToHex }}33);">
          @if($heroImage)
            <img src="{{ $heroImage }}" alt="{{ $heroHeading }}" class="w-full h-full object-cover">
          @else
            <!-- Placeholder for image - can be replaced with actual image -->
            <div class="w-full h-full flex items-center justify-center">
              <div class="text-center text-gray-400">
                <i class="ri-image-line text-6xl mb-2"></i>
                <p class="text-sm">{{ __('academy.hero.image_placeholder') }}</p>
              </div>
            </div>
          @endif
        </div>
      </div>
    </div>
  </div>
</section>
