@php
    // Get brand color for dynamic styling
    $brandColor = $academy?->brand_color ?? \App\Enums\TailwindColor::SKY;
    $brandColorHex = $brandColor->getHexValue(500);
    $brandColorLightHex = $brandColor->getHexValue(200);

    // Get reviews items from academy or use defaults
    $reviewsItems = $academy?->reviews_items ?? [];

    // Default reviews if none are set
    $defaultReviews = [
        [
            'name' => 'أحمد محمد',
            'role' => 'طالب في قسم القرآن الكريم',
            'content' => 'تجربة رائعة مع ' . ($academy->name ?? 'أكاديمية إتقان') . '. المعلمون متخصصون والمنهج واضح ومنظم. تمكنت من حفظ 5 أجزاء في 6 أشهر فقط.',
            'rating' => 5,
            'avatar' => 'https://images.unsplash.com/photo-1507003211169-0a1dd7228f2d?w=80&h=80&fit=crop&crop=face',
        ],
        [
            'name' => 'فاطمة أحمد',
            'role' => 'طالبة في القسم الأكاديمي',
            'content' => 'الدروس الأكاديمية ممتازة والشرح واضح. تحسنت درجاتي بشكل كبير في الرياضيات والفيزياء بفضل المعلمين المتميزين.',
            'rating' => 5,
            'avatar' => 'https://images.unsplash.com/photo-1494790108755-2616b612b786?w=80&h=80&fit=crop&crop=face',
        ],
        [
            'name' => 'محمد علي',
            'role' => 'ولي أمر',
            'content' => ($academy->name ?? 'أكاديمية إتقان') . ' غيرت مستوى ابني التعليمي. الدعم المستمر والمتابعة الدقيقة جعلته يحب التعلم أكثر.',
            'rating' => 5,
            'avatar' => 'https://images.unsplash.com/photo-1472099645785-5658abf4ff4e?w=80&h=80&fit=crop&crop=face',
        ],
        [
            'name' => 'سارة حسن',
            'role' => 'طالبة في الدروس الخاصة',
            'content' => 'الدروس الخاصة ساعدتني كثيراً في فهم المواد الصعبة. المعلمون صبورون ويشرحون بطريقة مبسطة ومفهومة.',
            'rating' => 5,
            'avatar' => 'https://images.unsplash.com/photo-1438761681033-6461ffad8d80?w=80&h=80&fit=crop&crop=face',
        ],
        [
            'name' => 'خالد عبدالله',
            'role' => 'طالب في الكورسات التفاعلية',
            'content' => 'الكورسات التفاعلية ممتازة! المحتوى غني والتفاعل مع المعلمين يجعل التعلم أكثر متعة وفعالية.',
            'rating' => 5,
            'avatar' => 'https://images.unsplash.com/photo-1500648767791-00dcc994a43e?w=80&h=80&fit=crop&crop=face',
        ],
        [
            'name' => 'نورا سالم',
            'role' => 'طالبة في حلقات القرآن',
            'content' => 'حلقات القرآن جميلة جداً. الجو الروحاني والزملاء الطيبون يجعلون الحفظ أسهل وأكثر متعة.',
            'rating' => 5,
            'avatar' => 'https://images.unsplash.com/photo-1544005313-94ddf0286df2?w=80&h=80&fit=crop&crop=face',
        ],
    ];

    // Use custom reviews if available, otherwise use defaults
    $reviews = !empty($reviewsItems) ? $reviewsItems : $defaultReviews;

    // Helper function to get avatar URL
    $getAvatarUrl = function($avatar) {
        if (empty($avatar)) {
            return 'https://ui-avatars.com/api/?name=User&background=random&size=80';
        }
        if (str_starts_with($avatar, 'http')) {
            return $avatar;
        }
        return asset('storage/' . $avatar);
    };
@endphp

<!-- Testimonials Section -->
<section id="testimonials" class="bg-gray-50 py-16 sm:py-18 lg:py-20" role="region" aria-labelledby="testimonials-heading">
  <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
    <div class="text-center mb-10 sm:mb-12 lg:mb-16">
      <h2 id="testimonials-heading" class="text-2xl sm:text-3xl lg:text-4xl font-bold text-gray-900 mb-4">{{ $heading ?? 'آراء طلابنا' }}</h2>
      <p class="text-base sm:text-lg lg:text-xl text-gray-600 max-w-3xl mx-auto">
        {{ $subheading ?? 'اكتشف تجارب طلابنا الناجحة وكيف ساعدتهم في تحقيق أهدافهم التعليمية' }}
      </p>
    </div>

    <!-- Testimonials Carousel -->
    <div class="testimonials-carousel relative" data-brand-color="{{ $brandColorHex }}" data-brand-color-light="{{ $brandColorLightHex }}" data-items-mobile="1" data-items-tablet="2" data-items-desktop="3">
      <!-- Carousel Container -->
      <div class="carousel-container overflow-hidden py-8 mx-12 lg:mx-16">
        <div id="testimonials-track" class="flex transition-transform duration-300 ease-in-out">
        @foreach($reviews as $review)
        <div class="carousel-item flex-shrink-0 w-full md:w-1/2 lg:w-1/3 px-3">
          <div class="testimonial-card">
            <div class="testimonial-header">
              <div class="testimonial-avatar">
                <img src="{{ $getAvatarUrl($review['avatar'] ?? null) }}" alt="{{ $review['name'] }}">
              </div>
              <div class="testimonial-info">
                <h4 class="testimonial-name">{{ $review['name'] }}</h4>
                <p class="testimonial-role">{{ $review['role'] ?? '' }}</p>
              </div>
            </div>
            <div class="testimonial-rating">
              @for($i = 1; $i <= 5; $i++)
                @if($i <= ($review['rating'] ?? 5))
                  <i class="ri-star-fill"></i>
                @else
                  <i class="ri-star-line"></i>
                @endif
              @endfor
            </div>
            <p class="testimonial-content">
              "{{ $review['content'] }}"
            </p>
          </div>
        </div>
        @endforeach
        </div>
      </div>

      <!-- Navigation Buttons -->
      <button id="carousel-prev" class="absolute start-0 top-1/2 -translate-y-1/2 z-10 w-10 h-10 md:w-12 md:h-12 bg-white rounded-full shadow-lg hover:shadow-xl transition-all duration-300 flex items-center justify-center hover:scale-110" style="color: {{ $brandColorHex }};">
        <i class="ri-arrow-right-s-line text-xl md:text-2xl ltr:rotate-180"></i>
      </button>
      <button id="carousel-next" class="absolute end-0 top-1/2 -translate-y-1/2 z-10 w-10 h-10 md:w-12 md:h-12 bg-white rounded-full shadow-lg hover:shadow-xl transition-all duration-300 flex items-center justify-center hover:scale-110" style="color: {{ $brandColorHex }};">
        <i class="ri-arrow-left-s-line text-xl md:text-2xl ltr:rotate-180"></i>
      </button>

      <!-- Pagination Dots -->
      <div id="carousel-dots" class="flex justify-center items-center gap-3 mt-8">
        <!-- Dots will be generated by JavaScript -->
      </div>
    </div>
  </div>
</section>
