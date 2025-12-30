@php
    // Get gradient palette
    $gradientPalette = $academy?->gradient_palette ?? \App\Enums\GradientPalette::OCEAN_BREEZE;
    $hexColors = $gradientPalette->getHexColors();
    $gradientFromHex = $hexColors['from'];
    $gradientToHex = $hexColors['to'];

    // Get brand color for dynamic styling
    $brandColor = $academy?->brand_color ?? \App\Enums\TailwindColor::SKY;
    $brandColorHex = $brandColor->getHexValue(500);
    $brandColorLightHex = $brandColor->getHexValue(200);
@endphp

<!-- Testimonials Section - Template 3: Classic Design with 2 Items Per Slide -->
<section id="testimonials" class="py-16 sm:py-18 lg:py-20 relative overflow-hidden" role="region" aria-labelledby="testimonials-heading">
  <!-- Subtle Gradient Background -->
  <div class="absolute inset-0" style="background: linear-gradient(to bottom right, {{ $gradientFromHex }}33, #f9fafb, {{ $gradientToHex }}33);"></div>

  <div class="relative z-10 max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
    <div class="text-center md:text-right mb-8 sm:mb-10 lg:mb-12">
      <h2 id="testimonials-heading" class="text-2xl sm:text-3xl font-bold text-gray-900 mb-2 sm:mb-3">{{ $heading ?? 'آراء طلابنا' }}</h2>
      <p class="text-sm sm:text-base text-gray-600 max-w-2xl mx-auto md:mx-0">
        {{ $subheading ?? 'اكتشف تجارب طلابنا الناجحة وكيف ساعدتهم في تحقيق أهدافهم التعليمية' }}
      </p>
    </div>

    <!-- Testimonials Carousel with 2 Items Per Slide -->
    <div class="testimonials-carousel relative" data-brand-color="{{ $brandColorHex }}" data-brand-color-light="{{ $brandColorLightHex }}" data-items-mobile="1" data-items-tablet="2" data-items-desktop="2">
      <!-- Carousel Container -->
      <div class="carousel-container overflow-hidden py-6 px-2">
        <div id="testimonials-track" class="flex transition-transform duration-300 ease-in-out">
        <!-- Testimonial 1 -->
        <div class="carousel-item flex-shrink-0 w-full md:w-1/2 px-4">
          <div class="testimonial-card">
            <div class="testimonial-header">
              <div class="testimonial-avatar">
                <img src="https://images.unsplash.com/photo-1507003211169-0a1dd7228f2d?w=80&h=80&fit=crop&crop=face" alt="أحمد محمد">
              </div>
              <div class="testimonial-info">
                <h4 class="testimonial-name">أحمد محمد</h4>
                <p class="testimonial-role">طالب في قسم القرآن الكريم</p>
              </div>
            </div>
            <div class="testimonial-rating">
              <i class="ri-star-fill"></i>
              <i class="ri-star-fill"></i>
              <i class="ri-star-fill"></i>
              <i class="ri-star-fill"></i>
              <i class="ri-star-fill"></i>
            </div>
            <p class="testimonial-content">
              "تجربة رائعة مع {{ $academy->name ?? 'أكاديمية إتقان' }}. المعلمون متخصصون والمنهج واضح ومنظم. تمكنت من حفظ 5 أجزاء في 6 أشهر فقط."
            </p>
          </div>
        </div>

        <!-- Testimonial 2 -->
        <div class="carousel-item flex-shrink-0 w-full md:w-1/2 px-4">
          <div class="testimonial-card">
            <div class="testimonial-header">
              <div class="testimonial-avatar">
                <img src="https://images.unsplash.com/photo-1494790108755-2616b612b786?w=80&h=80&fit=crop&crop=face" alt="فاطمة أحمد">
              </div>
              <div class="testimonial-info">
                <h4 class="testimonial-name">فاطمة أحمد</h4>
                <p class="testimonial-role">طالبة في القسم الأكاديمي</p>
              </div>
            </div>
            <div class="testimonial-rating">
              <i class="ri-star-fill"></i>
              <i class="ri-star-fill"></i>
              <i class="ri-star-fill"></i>
              <i class="ri-star-fill"></i>
              <i class="ri-star-fill"></i>
            </div>
            <p class="testimonial-content">
              "الدروس الأكاديمية ممتازة والشرح واضح. تحسنت درجاتي بشكل كبير في الرياضيات والفيزياء بفضل المعلمين المتميزين."
            </p>
          </div>
        </div>

        <!-- Testimonial 3 -->
        <div class="carousel-item flex-shrink-0 w-full md:w-1/2 px-4">
          <div class="testimonial-card">
            <div class="testimonial-header">
              <div class="testimonial-avatar">
                <img src="https://images.unsplash.com/photo-1472099645785-5658abf4ff4e?w=80&h=80&fit=crop&crop=face" alt="محمد علي">
              </div>
              <div class="testimonial-info">
                <h4 class="testimonial-name">محمد علي</h4>
                <p class="testimonial-role">ولي أمر</p>
              </div>
            </div>
            <div class="testimonial-rating">
              <i class="ri-star-fill"></i>
              <i class="ri-star-fill"></i>
              <i class="ri-star-fill"></i>
              <i class="ri-star-fill"></i>
              <i class="ri-star-fill"></i>
            </div>
            <p class="testimonial-content">
              "{{ $academy->name ?? 'أكاديمية إتقان' }} غيرت مستوى ابني التعليمي. الدعم المستمر والمتابعة الدقيقة جعلته يحب التعلم أكثر."
            </p>
          </div>
        </div>

        <!-- Testimonial 4 -->
        <div class="carousel-item flex-shrink-0 w-full md:w-1/2 px-4">
          <div class="testimonial-card">
            <div class="testimonial-header">
              <div class="testimonial-avatar">
                <img src="https://images.unsplash.com/photo-1438761681033-6461ffad8d80?w=80&h=80&fit=crop&crop=face" alt="سارة حسن">
              </div>
              <div class="testimonial-info">
                <h4 class="testimonial-name">سارة حسن</h4>
                <p class="testimonial-role">طالبة في الدروس الخاصة</p>
              </div>
            </div>
            <div class="testimonial-rating">
              <i class="ri-star-fill"></i>
              <i class="ri-star-fill"></i>
              <i class="ri-star-fill"></i>
              <i class="ri-star-fill"></i>
              <i class="ri-star-fill"></i>
            </div>
            <p class="testimonial-content">
              "الدروس الخاصة ساعدتني كثيراً في فهم المواد الصعبة. المعلمون صبورون ويشرحون بطريقة مبسطة ومفهومة."
            </p>
          </div>
        </div>

        <!-- Testimonial 5 -->
        <div class="carousel-item flex-shrink-0 w-full md:w-1/2 px-4">
          <div class="testimonial-card">
            <div class="testimonial-header">
              <div class="testimonial-avatar">
                <img src="https://images.unsplash.com/photo-1500648767791-00dcc994a43e?w=80&h=80&fit=crop&crop=face" alt="خالد عبدالله">
              </div>
              <div class="testimonial-info">
                <h4 class="testimonial-name">خالد عبدالله</h4>
                <p class="testimonial-role">طالب في الكورسات التفاعلية</p>
              </div>
            </div>
            <div class="testimonial-rating">
              <i class="ri-star-fill"></i>
              <i class="ri-star-fill"></i>
              <i class="ri-star-fill"></i>
              <i class="ri-star-fill"></i>
              <i class="ri-star-fill"></i>
            </div>
            <p class="testimonial-content">
              "الكورسات التفاعلية ممتازة! المحتوى غني والتفاعل مع المعلمين يجعل التعلم أكثر متعة وفعالية."
            </p>
          </div>
        </div>

        <!-- Testimonial 6 -->
        <div class="carousel-item flex-shrink-0 w-full md:w-1/2 px-4">
          <div class="testimonial-card">
            <div class="testimonial-header">
              <div class="testimonial-avatar">
                <img src="https://images.unsplash.com/photo-1544005313-94ddf0286df2?w=80&h=80&fit=crop&crop=face" alt="نورا سالم">
              </div>
              <div class="testimonial-info">
                <h4 class="testimonial-name">نورا سالم</h4>
                <p class="testimonial-role">طالبة في حلقات القرآن</p>
              </div>
            </div>
            <div class="testimonial-rating">
              <i class="ri-star-fill"></i>
              <i class="ri-star-fill"></i>
              <i class="ri-star-fill"></i>
              <i class="ri-star-fill"></i>
              <i class="ri-star-fill"></i>
            </div>
            <p class="testimonial-content">
              "حلقات القرآن جميلة جداً. الجو الروحاني والزملاء الطيبون يجعلون الحفظ أسهل وأكثر متعة."
            </p>
          </div>
        </div>
        </div>
      </div>

      <!-- Navigation Buttons - Simple Transition -->
      <button id="carousel-prev" class="carousel-nav-btn absolute start-2 md:start-4 lg:start-[-4rem] z-10 w-10 h-10 md:w-12 md:h-12 bg-white rounded-lg shadow-md hover:bg-gray-50 flex items-center justify-center transition-colors duration-200" style="top: 50%; transform: translateY(-50%); color: {{ $brandColorHex }};">
        <i class="ri-arrow-right-s-line text-xl ltr:rotate-180"></i>
      </button>
      <button id="carousel-next" class="carousel-nav-btn absolute end-2 md:end-4 lg:end-[-4rem] z-10 w-10 h-10 md:w-12 md:h-12 bg-white rounded-lg shadow-md hover:bg-gray-50 flex items-center justify-center transition-colors duration-200" style="top: 50%; transform: translateY(-50%); color: {{ $brandColorHex }};">
        <i class="ri-arrow-left-s-line text-xl ltr:rotate-180"></i>
      </button>

      <!-- Pagination Dots - Increased Gap -->
      <div id="carousel-dots" class="flex justify-center items-center gap-3 mt-6">
        <!-- Dots will be generated by JavaScript -->
      </div>
    </div>
  </div>
</section>
