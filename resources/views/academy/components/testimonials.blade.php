<!-- Testimonials Section -->
<section id="testimonials" class="bg-gray-50 py-20" role="region" aria-labelledby="testimonials-heading">
  <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
    <div class="text-center mb-16">
      <h2 id="testimonials-heading" class="text-4xl font-bold text-gray-900 mb-4">آراء طلابنا</h2>
      <p class="text-xl text-gray-600 max-w-3xl mx-auto">
        اكتشف تجارب طلابنا الناجحة وكيف ساعدتهم {{ $academy->name ?? 'أكاديمية إتقان' }} في تحقيق أهدافهم التعليمية
      </p>
    </div>
    
    <!-- Testimonials Carousel -->
    <div class="testimonials-carousel relative">
      <!-- Carousel Container -->
      <div class="carousel-container overflow-hidden py-8 px-4">
        <div id="testimonials-track" class="flex transition-transform duration-300 ease-in-out">
        <!-- Testimonial 1 -->
        <div class="carousel-item flex-shrink-0 w-full md:w-1/2 lg:w-1/3 px-6">
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
        <div class="carousel-item flex-shrink-0 w-full md:w-1/2 lg:w-1/3 px-6">
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
        <div class="carousel-item flex-shrink-0 w-full md:w-1/2 lg:w-1/3 px-6">
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
        <div class="carousel-item flex-shrink-0 w-full md:w-1/2 lg:w-1/3 px-6">
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
        <div class="carousel-item flex-shrink-0 w-full md:w-1/2 lg:w-1/3 px-6">
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
        <div class="carousel-item flex-shrink-0 w-full md:w-1/2 lg:w-1/3 px-6">
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
        
        <!-- Testimonial 7 -->
        <div class="carousel-item flex-shrink-0 w-full md:w-1/2 lg:w-1/3 px-6">
          <div class="testimonial-card">
            <div class="testimonial-header">
              <div class="testimonial-avatar">
                <img src="https://images.unsplash.com/photo-1507003211169-0a1dd7228f2d?w=80&h=80&fit=crop&crop=face" alt="يوسف أحمد">
              </div>
              <div class="testimonial-info">
                <h4 class="testimonial-name">يوسف أحمد</h4>
                <p class="testimonial-role">طالب في التعليم الفردي</p>
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
              "التعليم الفردي مثالي لمن يريد التركيز على نقاط ضعفه. المعلم يخصص وقتاً كاملاً لي."
            </p>
          </div>
        </div>
        
        <!-- Testimonial 8 -->
        <div class="carousel-item flex-shrink-0 w-full md:w-1/2 lg:w-1/3 px-6">
          <div class="testimonial-card">
            <div class="testimonial-header">
              <div class="testimonial-avatar">
                <img src="https://images.unsplash.com/photo-1494790108755-2616b612b786?w=80&h=80&fit=crop&crop=face" alt="مريم خالد">
              </div>
              <div class="testimonial-info">
                <h4 class="testimonial-name">مريم خالد</h4>
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
              "المنهج الأكاديمي شامل ومنظم. المتابعة المستمرة والاختبارات الدورية تساعدني على التقدم."
            </p>
          </div>
        </div>
        
        <!-- Testimonial 9 -->
        <div class="carousel-item flex-shrink-0 w-full md:w-1/2 lg:w-1/3 px-6">
          <div class="testimonial-card">
            <div class="testimonial-header">
              <div class="testimonial-avatar">
                <img src="https://images.unsplash.com/photo-1472099645785-5658abf4ff4e?w=80&h=80&fit=crop&crop=face" alt="عبدالرحمن محمد">
              </div>
              <div class="testimonial-info">
                <h4 class="testimonial-name">عبدالرحمن محمد</h4>
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
              "أشكر {{ $academy->name ?? 'أكاديمية إتقان' }} على الجودة العالية في التعليم. ابنتي تحب الذهاب للحلقات."
            </p>
          </div>
        </div>
        </div>
      </div>
      
      <!-- Navigation Buttons -->
      <button id="carousel-prev" class="carousel-nav-btn absolute left-2 md:left-4 lg:-left-16 top-1/2 -translate-y-1/2 z-10 w-12 h-12 md:w-14 md:h-14 bg-white rounded-full shadow-lg hover:shadow-xl transition-all duration-300 flex items-center justify-center text-primary hover:scale-110">
        <i class="ri-arrow-left-s-line text-xl md:text-2xl"></i>
      </button>
      <button id="carousel-next" class="carousel-nav-btn absolute right-2 md:right-4 lg:-right-16 top-1/2 -translate-y-1/2 z-10 w-12 h-12 md:w-14 md:h-14 bg-white rounded-full shadow-lg hover:shadow-xl transition-all duration-300 flex items-center justify-center text-primary hover:scale-110">
        <i class="ri-arrow-right-s-line text-xl md:text-2xl"></i>
      </button>
      
      <!-- Pagination Dots -->
      <div id="carousel-dots" class="flex justify-center items-center gap-3 mt-8">
        <!-- Dots will be generated by JavaScript -->
      </div>
    </div>
  </div>
</section> 