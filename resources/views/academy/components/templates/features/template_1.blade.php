@php
    // Get gradient palette
    $gradientPalette = $academy?->gradient_palette ?? \App\Enums\GradientPalette::OCEAN_BREEZE;
    $hexColors = $gradientPalette->getHexColors();
    $gradientFromHex = $hexColors['from'];
    $gradientToHex = $hexColors['to'];
    $bgGradientStyle = "background: linear-gradient(to right, {$gradientFromHex}, {$gradientToHex});";
@endphp

<!-- Features Section -->
<section id="features" class="py-16 sm:py-18 lg:py-20 text-white" style="{{ $bgGradientStyle }}">
  <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
    <div class="text-center mb-10 sm:mb-12 lg:mb-16">
      <h2 class="text-2xl sm:text-3xl lg:text-4xl font-bold mb-4">{{ $heading ?? __('academy.features.default_heading') }}</h2>
      <p class="text-base sm:text-lg lg:text-xl max-w-3xl mx-auto" style="color: rgba(255,255,255,0.85);">
        {{ $subheading ?? __('academy.features.default_subheading') }}
      </p>
    </div>
    <div class="grid grid-cols-2 lg:grid-cols-4 gap-4 sm:gap-6 lg:gap-8">
      <div class="text-center">
        <div class="w-12 h-12 sm:w-16 sm:h-16 flex items-center justify-center bg-white/20 rounded-full mx-auto mb-3 sm:mb-4">
          <i class="ri-shield-check-line text-xl sm:text-2xl text-white"></i>
        </div>
        <h3 class="text-base sm:text-lg lg:text-xl font-bold mb-2 sm:mb-3">{{ __('academy.features.quality.title') }}</h3>
        <p class="text-xs sm:text-sm lg:text-base" style="color: rgba(255,255,255,0.85);">{{ __('academy.features.quality.description') }}</p>
      </div>
      <div class="text-center">
        <div class="w-12 h-12 sm:w-16 sm:h-16 flex items-center justify-center bg-white/20 rounded-full mx-auto mb-3 sm:mb-4">
          <i class="ri-time-line text-xl sm:text-2xl text-white"></i>
        </div>
        <h3 class="text-base sm:text-lg lg:text-xl font-bold mb-2 sm:mb-3">{{ __('academy.features.flexibility.title') }}</h3>
        <p class="text-xs sm:text-sm lg:text-base" style="color: rgba(255,255,255,0.85);">{{ __('academy.features.flexibility.description') }}</p>
      </div>
      <div class="text-center">
        <div class="w-12 h-12 sm:w-16 sm:h-16 flex items-center justify-center bg-white/20 rounded-full mx-auto mb-3 sm:mb-4">
          <i class="ri-customer-service-2-line text-xl sm:text-2xl text-white"></i>
        </div>
        <h3 class="text-base sm:text-lg lg:text-xl font-bold mb-2 sm:mb-3">{{ __('academy.features.support.title') }}</h3>
        <p class="text-xs sm:text-sm lg:text-base" style="color: rgba(255,255,255,0.85);">{{ __('academy.features.support.description') }}</p>
      </div>
      <div class="text-center">
        <div class="w-12 h-12 sm:w-16 sm:h-16 flex items-center justify-center bg-white/20 rounded-full mx-auto mb-3 sm:mb-4">
          <i class="ri-bar-chart-line text-xl sm:text-2xl text-white"></i>
        </div>
        <h3 class="text-base sm:text-lg lg:text-xl font-bold mb-2 sm:mb-3">{{ __('academy.features.regular_followup.title') }}</h3>
        <p class="text-xs sm:text-sm lg:text-base" style="color: rgba(255,255,255,0.85);">{{ __('academy.features.regular_followup.description') }}</p>
      </div>
    </div>
  </div>
</section>
