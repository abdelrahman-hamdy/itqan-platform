@php
    // Get gradient palette
    $gradientPalette = $academy?->gradient_palette ?? \App\Enums\GradientPalette::OCEAN_BREEZE;
    $hexColors = $gradientPalette->getHexColors();
    $gradientFromHex = $hexColors['from'];
    $gradientToHex = $hexColors['to'];

    // Get brand color
    $brandColor = $academy?->brand_color ?? \App\Enums\TailwindColor::SKY;
    $brandColorHex = $brandColor->getHexValue(500);
    $brandColorLightHex = $brandColor->getHexValue(100);
@endphp

<!-- Features Section - Template 3: Classic Simple Design -->
<section id="features" class="py-16 sm:py-18 lg:py-20 bg-white">
  <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
    <!-- Section Header - Center on Mobile, Right on Desktop -->
    <div class="text-center md:text-right mb-8 sm:mb-10">
      <h2 class="text-2xl sm:text-3xl font-bold text-gray-900 mb-2">{{ $heading ?? __('academy.features.why_choose_academy') }}</h2>
      @if(isset($subheading))
        <p class="text-sm sm:text-base text-gray-600">{{ $subheading }}</p>
      @endif
    </div>

    <!-- Features Grid -->
    <div class="grid md:grid-cols-2 lg:grid-cols-3 gap-4 sm:gap-6">
      <!-- Feature 1 -->
      <div class="bg-gray-50 border border-gray-200 rounded-lg p-5 sm:p-6 text-center md:text-right">
        <div class="w-10 h-10 sm:w-12 sm:h-12 rounded-lg flex items-center justify-center mb-3 sm:mb-4 mx-auto md:mx-0"
             style="background-color: {{ $gradientFromHex }}1a;">
          <i class="ri-team-line text-xl sm:text-2xl" style="color: {{ $gradientFromHex }};"></i>
        </div>
        <h3 class="text-base sm:text-lg font-semibold text-gray-900 mb-2">{{ __('academy.features.specialized_teachers.title') }}</h3>
        <p class="text-xs sm:text-sm text-gray-600 leading-relaxed">{{ __('academy.features.specialized_teachers.description') }}</p>
      </div>

      <!-- Feature 2 -->
      <div class="bg-gray-50 border border-gray-200 rounded-lg p-5 sm:p-6 text-center md:text-right">
        <div class="w-10 h-10 sm:w-12 sm:h-12 rounded-lg flex items-center justify-center mb-3 sm:mb-4 mx-auto md:mx-0"
             style="background-color: {{ $gradientToHex }}1a;">
          <i class="ri-calendar-check-line text-xl sm:text-2xl" style="color: {{ $gradientToHex }};"></i>
        </div>
        <h3 class="text-base sm:text-lg font-semibold text-gray-900 mb-2">{{ __('academy.features.flexible_schedules.title') }}</h3>
        <p class="text-xs sm:text-sm text-gray-600 leading-relaxed">{{ __('academy.features.flexible_schedules.description') }}</p>
      </div>

      <!-- Feature 3 -->
      <div class="bg-gray-50 border border-gray-200 rounded-lg p-5 sm:p-6 text-center md:text-right">
        <div class="w-10 h-10 sm:w-12 sm:h-12 rounded-lg flex items-center justify-center mb-3 sm:mb-4 mx-auto md:mx-0"
             style="background-color: {{ $brandColorLightHex }};">
          <i class="ri-video-line text-xl sm:text-2xl" style="color: {{ $brandColorHex }};"></i>
        </div>
        <h3 class="text-base sm:text-lg font-semibold text-gray-900 mb-2">{{ __('academy.features.remote_learning.title') }}</h3>
        <p class="text-xs sm:text-sm text-gray-600 leading-relaxed">{{ __('academy.features.remote_learning.description') }}</p>
      </div>

      <!-- Feature 4 -->
      <div class="bg-gray-50 border border-gray-200 rounded-lg p-5 sm:p-6 text-center md:text-right">
        <div class="w-10 h-10 sm:w-12 sm:h-12 rounded-lg flex items-center justify-center mb-3 sm:mb-4 mx-auto md:mx-0"
             style="background-color: {{ $gradientFromHex }}1a;">
          <i class="ri-trophy-line text-xl sm:text-2xl" style="color: {{ $gradientFromHex }};"></i>
        </div>
        <h3 class="text-base sm:text-lg font-semibold text-gray-900 mb-2">{{ __('academy.features.continuous_followup.title') }}</h3>
        <p class="text-xs sm:text-sm text-gray-600 leading-relaxed">{{ __('academy.features.continuous_followup.description') }}</p>
      </div>

      <!-- Feature 5 -->
      <div class="bg-gray-50 border border-gray-200 rounded-lg p-5 sm:p-6 text-center md:text-right">
        <div class="w-10 h-10 sm:w-12 sm:h-12 rounded-lg flex items-center justify-center mb-3 sm:mb-4 mx-auto md:mx-0"
             style="background-color: {{ $gradientToHex }}1a;">
          <i class="ri-book-open-line text-xl sm:text-2xl" style="color: {{ $gradientToHex }};"></i>
        </div>
        <h3 class="text-base sm:text-lg font-semibold text-gray-900 mb-2">{{ __('academy.features.advanced_curriculum.title') }}</h3>
        <p class="text-xs sm:text-sm text-gray-600 leading-relaxed">{{ __('academy.features.advanced_curriculum.description') }}</p>
      </div>

      <!-- Feature 6 -->
      <div class="bg-gray-50 border border-gray-200 rounded-lg p-5 sm:p-6 text-center md:text-right">
        <div class="w-10 h-10 sm:w-12 sm:h-12 rounded-lg flex items-center justify-center mb-3 sm:mb-4 mx-auto md:mx-0"
             style="background-color: {{ $brandColorLightHex }};">
          <i class="ri-customer-service-line text-xl sm:text-2xl" style="color: {{ $brandColorHex }};"></i>
        </div>
        <h3 class="text-base sm:text-lg font-semibold text-gray-900 mb-2">{{ __('academy.features.technical_support.title') }}</h3>
        <p class="text-xs sm:text-sm text-gray-600 leading-relaxed">{{ __('academy.features.technical_support.description') }}</p>
      </div>
    </div>
  </div>
</section>
