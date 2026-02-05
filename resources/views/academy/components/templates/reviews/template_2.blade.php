@php
    // Get brand color
    $brandColor = $academy?->brand_color ?? \App\Enums\TailwindColor::SKY;
    $brandColor300Hex = $brandColor->getHexValue(300);

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

<!-- Testimonials Section - Template 2: Clean Grid Layout -->
<section id="testimonials" class="bg-white py-16 sm:py-18 lg:py-20" role="region" aria-labelledby="testimonials-heading">
  <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
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
          <img src="{{ $getAvatarUrl($review['avatar'] ?? null) }}" alt="{{ $review['name'] }}" class="w-12 h-12 rounded-full object-cover border-2 border-gray-200">
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
