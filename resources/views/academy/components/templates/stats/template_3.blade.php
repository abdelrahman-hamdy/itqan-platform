@php
    // Get gradient palette
    $gradientPalette = $academy?->gradient_palette ?? \App\Enums\GradientPalette::OCEAN_BREEZE;
    $hexColors = $gradientPalette->getHexColors();
    $gradientFromHex = $hexColors['from'];
    $gradientToHex = $hexColors['to'];

    // Get brand color with all shades
    $brandColor = $academy?->brand_color ?? \App\Enums\TailwindColor::SKY;
    $brandColorHex = $brandColor->getHexValue(500);
    $brandColorLightHex = $brandColor->getHexValue(100);
    $brandColor50Hex = $brandColor->getHexValue(50);
    $brandColor600Hex = $brandColor->getHexValue(600);
@endphp

<!-- Statistics Section - Template 3: Classic Simple Design -->
<section id="stats" class="py-16 sm:py-18 lg:py-20 bg-white">
  <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
    <!-- Section Header - Center on Mobile, Right on Desktop -->
    <div class="text-center md:text-right mb-8 sm:mb-10">
      <h2 class="text-2xl sm:text-3xl font-bold text-gray-900 mb-2">{{ $heading ?? __('academy.stats.default_heading') }}</h2>
      @if(isset($subheading))
        <p class="text-sm sm:text-base text-gray-600">{{ $subheading }}</p>
      @endif
    </div>

    <!-- Stats Grid -->
    <div class="grid grid-cols-2 lg:grid-cols-4 gap-4 sm:gap-6">
      <!-- Stat 1 - Students -->
      <div class="bg-white border border-gray-200 rounded-lg p-4 sm:p-6 text-center">
        <div class="w-10 h-10 sm:w-12 sm:h-12 rounded-lg flex items-center justify-center mx-auto mb-2 sm:mb-3"
             style="background-color: {{ $gradientFromHex }}1a;">
          <i class="ri-user-line text-xl sm:text-2xl" style="color: {{ $gradientFromHex }};"></i>
        </div>
        <div class="text-2xl sm:text-3xl font-bold text-gray-900 mb-1 stats-counter" data-target="{{ $academy->stats_students ?? 5000 }}">0</div>
        <div class="text-xs sm:text-sm text-gray-600">طالب نشط</div>
      </div>

      <!-- Stat 2 - Teachers -->
      <div class="bg-white border border-gray-200 rounded-lg p-4 sm:p-6 text-center">
        <div class="w-10 h-10 sm:w-12 sm:h-12 rounded-lg flex items-center justify-center mx-auto mb-2 sm:mb-3"
             style="background-color: {{ $gradientToHex }}1a;">
          <i class="ri-team-line text-xl sm:text-2xl" style="color: {{ $gradientToHex }};"></i>
        </div>
        <div class="text-2xl sm:text-3xl font-bold text-gray-900 mb-1 stats-counter" data-target="{{ $academy->stats_teachers ?? 200 }}">0</div>
        <div class="text-xs sm:text-sm text-gray-600">معلم متخصص</div>
      </div>

      <!-- Stat 3 - Courses -->
      <div class="bg-white border border-gray-200 rounded-lg p-4 sm:p-6 text-center">
        <div class="w-10 h-10 sm:w-12 sm:h-12 rounded-lg flex items-center justify-center mx-auto mb-2 sm:mb-3"
             style="background-color: {{ $brandColorLightHex }};">
          <i class="ri-book-open-line text-xl sm:text-2xl" style="color: {{ $brandColorHex }};"></i>
        </div>
        <div class="text-2xl sm:text-3xl font-bold text-gray-900 mb-1 stats-counter" data-target="{{ $academy->stats_courses ?? 150 }}">0</div>
        <div class="text-xs sm:text-sm text-gray-600">كورس متاح</div>
      </div>

      <!-- Stat 4 - Success Rate -->
      <div class="bg-white border border-gray-200 rounded-lg p-4 sm:p-6 text-center">
        <div class="w-10 h-10 sm:w-12 sm:h-12 rounded-lg flex items-center justify-center mx-auto mb-2 sm:mb-3"
             style="background-color: {{ $brandColor50Hex }};">
          <i class="ri-trophy-line text-xl sm:text-2xl" style="color: {{ $brandColor600Hex }};"></i>
        </div>
        <div class="text-2xl sm:text-3xl font-bold text-gray-900 mb-1"><span class="stats-counter" data-target="{{ $academy->stats_success_rate ?? 98 }}">0</span>%</div>
        <div class="text-xs sm:text-sm text-gray-600">نسبة الرضا</div>
      </div>
    </div>
  </div>
</section>
