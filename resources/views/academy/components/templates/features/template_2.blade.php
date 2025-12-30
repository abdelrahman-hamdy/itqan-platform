@php
    // Get gradient palette
    $gradientPalette = $academy?->gradient_palette ?? \App\Enums\GradientPalette::OCEAN_BREEZE;
    $hexColors = $gradientPalette->getHexColors();
    $gradientFromHex = $hexColors['from'];
    $gradientToHex = $hexColors['to'];
@endphp

<!-- Features Section - Template 2: Clean Icon-Based Cards with Gradient Background -->
<section id="features" class="relative py-16 sm:py-20 lg:py-24 overflow-hidden" role="region" aria-labelledby="features-heading">
  <!-- Gradient Background -->
  <div class="absolute inset-0" style="background: linear-gradient(to bottom right, {{ $gradientFromHex }}1a, white, {{ $gradientToHex }}1a);"></div>
  <div class="absolute inset-0" style="background: radial-gradient(circle at top right, {{ $gradientFromHex }}14, transparent 50%);"></div>

  <div class="relative max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
    <div class="text-center mb-10 sm:mb-12 lg:mb-16">
      <h2 id="features-heading" class="text-2xl sm:text-3xl font-bold text-gray-900 mb-4">{{ $heading ?? __('academy.features.why_choose_us') }}</h2>
      @if(isset($subheading))
        <p class="text-base sm:text-lg text-gray-600 max-w-3xl mx-auto">{{ $subheading }}</p>
      @endif
    </div>

    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4 sm:gap-6">
      <!-- Feature 1 -->
      <div class="bg-white/80 backdrop-blur-sm border-2 border-gray-200 rounded-xl p-5 sm:p-6 lg:p-8 transition-all duration-200"
           style="--hover-border: {{ $gradientFromHex }}66;"
           onmouseenter="this.style.borderColor='{{ $gradientFromHex }}66'; this.style.boxShadow='0 10px 15px -3px rgba(0,0,0,0.1)';"
           onmouseleave="this.style.borderColor=''; this.style.boxShadow='';">
        <div class="w-12 h-12 sm:w-14 sm:h-14 rounded-xl flex items-center justify-center mb-4 sm:mb-5"
             style="background: linear-gradient(to bottom right, {{ $gradientFromHex }}33, {{ $gradientToHex }}33); border: 1px solid {{ $gradientFromHex }}4d;">
          <i class="ri-user-star-line text-2xl sm:text-3xl" style="color: {{ $gradientFromHex }};"></i>
        </div>
        <h3 class="text-lg sm:text-xl font-bold text-gray-900 mb-2 sm:mb-3">{{ __('academy.features.specialized_teachers.title') }}</h3>
        <p class="text-sm sm:text-base text-gray-600 leading-relaxed">
          {{ __('academy.features.specialized_teachers.description') }}
        </p>
      </div>

      <!-- Feature 2 -->
      <div class="bg-white/80 backdrop-blur-sm border-2 border-gray-200 rounded-xl p-5 sm:p-6 lg:p-8 transition-all duration-200"
           onmouseenter="this.style.borderColor='{{ $gradientFromHex }}66'; this.style.boxShadow='0 10px 15px -3px rgba(0,0,0,0.1)';"
           onmouseleave="this.style.borderColor=''; this.style.boxShadow='';">
        <div class="w-12 h-12 sm:w-14 sm:h-14 rounded-xl flex items-center justify-center mb-4 sm:mb-5"
             style="background: linear-gradient(to bottom right, {{ $gradientFromHex }}33, {{ $gradientToHex }}33); border: 1px solid {{ $gradientFromHex }}4d;">
          <i class="ri-calendar-check-line text-2xl sm:text-3xl" style="color: {{ $gradientFromHex }};"></i>
        </div>
        <h3 class="text-lg sm:text-xl font-bold text-gray-900 mb-2 sm:mb-3">{{ __('academy.features.flexible_schedules.title') }}</h3>
        <p class="text-sm sm:text-base text-gray-600 leading-relaxed">
          {{ __('academy.features.flexible_schedules.description') }}
        </p>
      </div>

      <!-- Feature 3 -->
      <div class="bg-white/80 backdrop-blur-sm border-2 border-gray-200 rounded-xl p-5 sm:p-6 lg:p-8 transition-all duration-200"
           onmouseenter="this.style.borderColor='{{ $gradientFromHex }}66'; this.style.boxShadow='0 10px 15px -3px rgba(0,0,0,0.1)';"
           onmouseleave="this.style.borderColor=''; this.style.boxShadow='';">
        <div class="w-12 h-12 sm:w-14 sm:h-14 rounded-xl flex items-center justify-center mb-4 sm:mb-5"
             style="background: linear-gradient(to bottom right, {{ $gradientFromHex }}33, {{ $gradientToHex }}33); border: 1px solid {{ $gradientFromHex }}4d;">
          <i class="ri-video-line text-2xl sm:text-3xl" style="color: {{ $gradientFromHex }};"></i>
        </div>
        <h3 class="text-lg sm:text-xl font-bold text-gray-900 mb-2 sm:mb-3">{{ __('academy.features.interactive_learning.title') }}</h3>
        <p class="text-sm sm:text-base text-gray-600 leading-relaxed">
          {{ __('academy.features.interactive_learning.description') }}
        </p>
      </div>

      <!-- Feature 4 -->
      <div class="bg-white/80 backdrop-blur-sm border-2 border-gray-200 rounded-xl p-5 sm:p-6 lg:p-8 transition-all duration-200"
           onmouseenter="this.style.borderColor='{{ $gradientFromHex }}66'; this.style.boxShadow='0 10px 15px -3px rgba(0,0,0,0.1)';"
           onmouseleave="this.style.borderColor=''; this.style.boxShadow='';">
        <div class="w-12 h-12 sm:w-14 sm:h-14 rounded-xl flex items-center justify-center mb-4 sm:mb-5"
             style="background: linear-gradient(to bottom right, {{ $gradientFromHex }}33, {{ $gradientToHex }}33); border: 1px solid {{ $gradientFromHex }}4d;">
          <i class="ri-file-text-line text-2xl sm:text-3xl" style="color: {{ $gradientFromHex }};"></i>
        </div>
        <h3 class="text-lg sm:text-xl font-bold text-gray-900 mb-2 sm:mb-3">{{ __('academy.features.regular_followup.title') }}</h3>
        <p class="text-sm sm:text-base text-gray-600 leading-relaxed">
          {{ __('academy.features.regular_followup.description') }}
        </p>
      </div>

      <!-- Feature 5 -->
      <div class="bg-white/80 backdrop-blur-sm border-2 border-gray-200 rounded-xl p-5 sm:p-6 lg:p-8 transition-all duration-200"
           onmouseenter="this.style.borderColor='{{ $gradientFromHex }}66'; this.style.boxShadow='0 10px 15px -3px rgba(0,0,0,0.1)';"
           onmouseleave="this.style.borderColor=''; this.style.boxShadow='';">
        <div class="w-12 h-12 sm:w-14 sm:h-14 rounded-xl flex items-center justify-center mb-4 sm:mb-5"
             style="background: linear-gradient(to bottom right, {{ $gradientFromHex }}33, {{ $gradientToHex }}33); border: 1px solid {{ $gradientFromHex }}4d;">
          <i class="ri-shield-check-line text-2xl sm:text-3xl" style="color: {{ $gradientFromHex }};"></i>
        </div>
        <h3 class="text-lg sm:text-xl font-bold text-gray-900 mb-2 sm:mb-3">{{ __('academy.features.safe_environment.title') }}</h3>
        <p class="text-sm sm:text-base text-gray-600 leading-relaxed">
          {{ __('academy.features.safe_environment.description') }}
        </p>
      </div>

      <!-- Feature 6 -->
      <div class="bg-white/80 backdrop-blur-sm border-2 border-gray-200 rounded-xl p-5 sm:p-6 lg:p-8 transition-all duration-200"
           onmouseenter="this.style.borderColor='{{ $gradientFromHex }}66'; this.style.boxShadow='0 10px 15px -3px rgba(0,0,0,0.1)';"
           onmouseleave="this.style.borderColor=''; this.style.boxShadow='';">
        <div class="w-12 h-12 sm:w-14 sm:h-14 rounded-xl flex items-center justify-center mb-4 sm:mb-5"
             style="background: linear-gradient(to bottom right, {{ $gradientFromHex }}33, {{ $gradientToHex }}33); border: 1px solid {{ $gradientFromHex }}4d;">
          <i class="ri-customer-service-2-line text-2xl sm:text-3xl" style="color: {{ $gradientFromHex }};"></i>
        </div>
        <h3 class="text-lg sm:text-xl font-bold text-gray-900 mb-2 sm:mb-3">{{ __('academy.features.technical_support.title') }}</h3>
        <p class="text-sm sm:text-base text-gray-600 leading-relaxed">
          {{ __('academy.features.technical_support.description') }}
        </p>
      </div>
    </div>
  </div>
</section>
