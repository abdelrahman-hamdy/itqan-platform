@php
    // Get gradient palette
    $gradientPalette = $academy?->gradient_palette ?? \App\Enums\GradientPalette::OCEAN_BREEZE;
    $hexColors = $gradientPalette->getHexColors();
    $gradientFromHex = $hexColors['from'];
    $gradientToHex = $hexColors['to'];

    // Get brand color
    $brandColor = $academy?->brand_color ?? \App\Enums\TailwindColor::SKY;
    $brandColor300Hex = $brandColor->getHexValue(300);
    $brandColorHex = $brandColor->getHexValue(500);

    // Get reviews items from academy
    $reviews = $academy?->reviews_items ?? [];

    // Helper function to get avatar URL (returns null if no avatar)
    $getAvatarUrl = function($avatar) {
        if (empty($avatar)) {
            return null;
        }
        if (str_starts_with($avatar, 'http')) {
            return $avatar;
        }
        return asset('storage/' . $avatar);
    };
@endphp

@if(count($reviews) > 0)
<!-- Testimonials Section - Template 2: Clean Grid Layout -->
<section id="testimonials" class="py-16 sm:py-18 lg:py-20 relative overflow-hidden" role="region" aria-labelledby="testimonials-heading">
  <!-- Subtle Gradient Background -->
  <div class="absolute inset-0" style="background: linear-gradient(to bottom right, {{ $gradientFromHex }}15, #ffffff, {{ $gradientToHex }}15);"></div>

  <div class="relative z-10 max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
    <div class="text-center mb-10 sm:mb-12 lg:mb-16">
      <h2 id="testimonials-heading" class="text-2xl sm:text-3xl lg:text-4xl font-bold text-gray-900 mb-4">{{ $heading ?? 'آراء طلابنا' }}</h2>
      <p class="text-base sm:text-lg lg:text-xl text-gray-600 max-w-3xl mx-auto">
        {{ $subheading ?? 'اكتشف تجارب طلابنا الناجحة وكيف ساعدتهم في تحقيق أهدافهم التعليمية' }}
      </p>
    </div>

    <!-- Testimonials Grid -->
    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4 sm:gap-6">
      @foreach($reviews as $review)
      <div class="bg-white border-2 border-gray-200 rounded-xl p-6 transition-all duration-200"
           onmouseenter="this.style.borderColor='{{ $brandColor300Hex }}'"
           onmouseleave="this.style.borderColor=''">
        <div class="flex items-center gap-3 mb-4">
          @if($avatarUrl = $getAvatarUrl($review['avatar'] ?? null))
            <img src="{{ $avatarUrl }}" alt="{{ $review['name'] }}" class="w-12 h-12 rounded-full object-cover border-2 border-gray-200">
          @else
            <div class="w-12 h-12 rounded-full flex items-center justify-center border-2" style="background: linear-gradient(135deg, {{ $gradientFromHex }}, {{ $gradientToHex }}); border-color: {{ $brandColor300Hex }};">
              <i class="ri-user-fill text-white text-lg"></i>
            </div>
          @endif
          <div>
            <h4 class="font-semibold text-gray-900">{{ $review['name'] }}</h4>
            <p class="text-sm text-gray-500">{{ $review['role'] ?? '' }}</p>
          </div>
        </div>
        <div class="flex gap-1 mb-3">
          @for($i = 1; $i <= 5; $i++)
            @if($i <= ($review['rating'] ?? 5))
              <i class="ri-star-fill text-yellow-400"></i>
            @else
              <i class="ri-star-line text-yellow-400"></i>
            @endif
          @endfor
        </div>
        <p class="text-gray-600 text-sm leading-relaxed">
          "{{ $review['content'] }}"
        </p>
      </div>
      @endforeach
    </div>
  </div>
</section>
@endif
