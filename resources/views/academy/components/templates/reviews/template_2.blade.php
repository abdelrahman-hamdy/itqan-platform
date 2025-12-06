@php
  $brandColor = $academy?->brand_color?->value ?? 'sky';
@endphp

<!-- Testimonials Section - Template 2: Clean Grid Layout -->
<section id="testimonials" class="bg-white py-20" role="region" aria-labelledby="testimonials-heading">
  <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
    <div class="text-center mb-16">
      <h2 id="testimonials-heading" class="text-4xl font-bold text-gray-900 mb-4">{{ $heading ?? 'آراء طلابنا' }}</h2>
      <p class="text-xl text-gray-600 max-w-3xl mx-auto">
        {{ $subheading ?? 'اكتشف تجارب طلابنا الناجحة وكيف ساعدتهم في تحقيق أهدافهم التعليمية' }}
      </p>
    </div>

    <!-- Testimonials Grid -->
    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
      <!-- Testimonial 1 -->
      <div class="bg-white border-2 border-gray-200 rounded-xl p-6 hover:border-{{ $brandColor }}-300 transition-all duration-200">
        <div class="flex items-center gap-3 mb-4">
          <img src="https://images.unsplash.com/photo-1507003211169-0a1dd7228f2d?w=80&h=80&fit=crop&crop=face" alt="أحمد محمد" class="w-12 h-12 rounded-full object-cover border-2 border-gray-200">
          <div>
            <h4 class="font-semibold text-gray-900">أحمد محمد</h4>
            <p class="text-sm text-gray-500">طالب في قسم القرآن الكريم</p>
          </div>
        </div>
        <div class="flex gap-1 mb-3">
          <i class="ri-star-fill text-yellow-400"></i>
          <i class="ri-star-fill text-yellow-400"></i>
          <i class="ri-star-fill text-yellow-400"></i>
          <i class="ri-star-fill text-yellow-400"></i>
          <i class="ri-star-fill text-yellow-400"></i>
        </div>
        <p class="text-gray-600 text-sm leading-relaxed">
          "تجربة رائعة مع {{ $academy->name ?? 'أكاديمية إتقان' }}. المعلمون متخصصون والمنهج واضح ومنظم. تمكنت من حفظ 5 أجزاء في 6 أشهر فقط."
        </p>
      </div>

      <!-- Testimonial 2 -->
      <div class="bg-white border-2 border-gray-200 rounded-xl p-6 hover:border-{{ $brandColor }}-300 transition-all duration-200">
        <div class="flex items-center gap-3 mb-4">
          <img src="https://images.unsplash.com/photo-1494790108755-2616b612b786?w=80&h=80&fit=crop&crop=face" alt="فاطمة أحمد" class="w-12 h-12 rounded-full object-cover border-2 border-gray-200">
          <div>
            <h4 class="font-semibold text-gray-900">فاطمة أحمد</h4>
            <p class="text-sm text-gray-500">طالبة في القسم الأكاديمي</p>
          </div>
        </div>
        <div class="flex gap-1 mb-3">
          <i class="ri-star-fill text-yellow-400"></i>
          <i class="ri-star-fill text-yellow-400"></i>
          <i class="ri-star-fill text-yellow-400"></i>
          <i class="ri-star-fill text-yellow-400"></i>
          <i class="ri-star-fill text-yellow-400"></i>
        </div>
        <p class="text-gray-600 text-sm leading-relaxed">
          "الدروس الأكاديمية ممتازة والشرح واضح. تحسنت درجاتي بشكل كبير في الرياضيات والفيزياء بفضل المعلمين المتميزين."
        </p>
      </div>

      <!-- Testimonial 3 -->
      <div class="bg-white border-2 border-gray-200 rounded-xl p-6 hover:border-{{ $brandColor }}-300 transition-all duration-200">
        <div class="flex items-center gap-3 mb-4">
          <img src="https://images.unsplash.com/photo-1472099645785-5658abf4ff4e?w=80&h=80&fit=crop&crop=face" alt="محمد علي" class="w-12 h-12 rounded-full object-cover border-2 border-gray-200">
          <div>
            <h4 class="font-semibold text-gray-900">محمد علي</h4>
            <p class="text-sm text-gray-500">ولي أمر</p>
          </div>
        </div>
        <div class="flex gap-1 mb-3">
          <i class="ri-star-fill text-yellow-400"></i>
          <i class="ri-star-fill text-yellow-400"></i>
          <i class="ri-star-fill text-yellow-400"></i>
          <i class="ri-star-fill text-yellow-400"></i>
          <i class="ri-star-fill text-yellow-400"></i>
        </div>
        <p class="text-gray-600 text-sm leading-relaxed">
          "{{ $academy->name ?? 'أكاديمية إتقان' }} غيرت مستوى ابني التعليمي. الدعم المستمر والمتابعة الدقيقة جعلته يحب التعلم أكثر."
        </p>
      </div>

      <!-- Testimonial 4 -->
      <div class="bg-white border-2 border-gray-200 rounded-xl p-6 hover:border-{{ $brandColor }}-300 transition-all duration-200">
        <div class="flex items-center gap-3 mb-4">
          <img src="https://images.unsplash.com/photo-1438761681033-6461ffad8d80?w=80&h=80&fit=crop&crop=face" alt="سارة حسن" class="w-12 h-12 rounded-full object-cover border-2 border-gray-200">
          <div>
            <h4 class="font-semibold text-gray-900">سارة حسن</h4>
            <p class="text-sm text-gray-500">طالبة في الدروس الخاصة</p>
          </div>
        </div>
        <div class="flex gap-1 mb-3">
          <i class="ri-star-fill text-yellow-400"></i>
          <i class="ri-star-fill text-yellow-400"></i>
          <i class="ri-star-fill text-yellow-400"></i>
          <i class="ri-star-fill text-yellow-400"></i>
          <i class="ri-star-fill text-yellow-400"></i>
        </div>
        <p class="text-gray-600 text-sm leading-relaxed">
          "الدروس الخاصة ساعدتني كثيراً في فهم المواد الصعبة. المعلمون صبورون ويشرحون بطريقة مبسطة ومفهومة."
        </p>
      </div>

      <!-- Testimonial 5 -->
      <div class="bg-white border-2 border-gray-200 rounded-xl p-6 hover:border-{{ $brandColor }}-300 transition-all duration-200">
        <div class="flex items-center gap-3 mb-4">
          <img src="https://images.unsplash.com/photo-1500648767791-00dcc994a43e?w=80&h=80&fit=crop&crop=face" alt="خالد عبدالله" class="w-12 h-12 rounded-full object-cover border-2 border-gray-200">
          <div>
            <h4 class="font-semibold text-gray-900">خالد عبدالله</h4>
            <p class="text-sm text-gray-500">طالب في الكورسات التفاعلية</p>
          </div>
        </div>
        <div class="flex gap-1 mb-3">
          <i class="ri-star-fill text-yellow-400"></i>
          <i class="ri-star-fill text-yellow-400"></i>
          <i class="ri-star-fill text-yellow-400"></i>
          <i class="ri-star-fill text-yellow-400"></i>
          <i class="ri-star-fill text-yellow-400"></i>
        </div>
        <p class="text-gray-600 text-sm leading-relaxed">
          "الكورسات التفاعلية ممتازة! المحتوى غني والتفاعل مع المعلمين يجعل التعلم أكثر متعة وفعالية."
        </p>
      </div>

      <!-- Testimonial 6 -->
      <div class="bg-white border-2 border-gray-200 rounded-xl p-6 hover:border-{{ $brandColor }}-300 transition-all duration-200">
        <div class="flex items-center gap-3 mb-4">
          <img src="https://images.unsplash.com/photo-1544005313-94ddf0286df2?w=80&h=80&fit=crop&crop=face" alt="نورا سالم" class="w-12 h-12 rounded-full object-cover border-2 border-gray-200">
          <div>
            <h4 class="font-semibold text-gray-900">نورا سالم</h4>
            <p class="text-sm text-gray-500">طالبة في حلقات القرآن</p>
          </div>
        </div>
        <div class="flex gap-1 mb-3">
          <i class="ri-star-fill text-yellow-400"></i>
          <i class="ri-star-fill text-yellow-400"></i>
          <i class="ri-star-fill text-yellow-400"></i>
          <i class="ri-star-fill text-yellow-400"></i>
          <i class="ri-star-fill text-yellow-400"></i>
        </div>
        <p class="text-gray-600 text-sm leading-relaxed">
          "حلقات القرآن جميلة جداً. الجو الروحاني والزملاء الطيبون يجعلون الحفظ أسهل وأكثر متعة."
        </p>
      </div>
    </div>
  </div>
</section>
