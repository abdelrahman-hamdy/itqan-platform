<!-- Testimonials Section -->
<section class="bg-gray-50 py-20" role="region" aria-labelledby="testimonials-heading">
  <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
    <div class="text-center mb-16">
      <h2 id="testimonials-heading" class="text-4xl font-bold text-gray-900 mb-4">آراء طلابنا</h2>
      <p class="text-xl text-gray-600 max-w-3xl mx-auto">
        اكتشف تجارب طلابنا الناجحة وكيف ساعدتهم {{ $academy->name ?? 'أكاديمية إتقان' }} في تحقيق أهدافهم التعليمية
      </p>
    </div>
    
    <div class="grid md:grid-cols-2 lg:grid-cols-3 gap-8">
      @forelse($academy->testimonials ?? [] as $testimonial)
        <div class="bg-white p-6 rounded-xl shadow-lg">
          <div class="flex items-center mb-4">
            <img src="{{ $testimonial->avatar ?? 'https://images.unsplash.com/photo-1507003211169-0a1dd7228f2d?w=60&h=60&fit=crop&crop=face' }}" 
                 alt="صورة {{ $testimonial->name }}" class="w-12 h-12 rounded-full ml-4">
            <div>
              <h4 class="font-semibold text-gray-900">{{ $testimonial->name }}</h4>
              <p class="text-sm text-gray-600">{{ $testimonial->role }}</p>
            </div>
          </div>
          <div class="flex mb-4" aria-label="تقييم {{ $testimonial->rating }} نجوم">
            @for($i = 1; $i <= 5; $i++)
              <i class="ri-star-{{ $i <= $testimonial->rating ? 'fill' : 'line' }} text-yellow-400"></i>
            @endfor
          </div>
          <p class="text-gray-700 leading-relaxed">
            "{{ $testimonial->content }}"
          </p>
        </div>
      @empty
        <!-- Default testimonials -->
        <div class="bg-white p-6 rounded-xl shadow-lg">
          <div class="flex items-center mb-4">
            <img src="https://images.unsplash.com/photo-1507003211169-0a1dd7228f2d?w=60&h=60&fit=crop&crop=face" alt="صورة أحمد محمد" class="w-12 h-12 rounded-full ml-4">
            <div>
              <h4 class="font-semibold text-gray-900">أحمد محمد</h4>
              <p class="text-sm text-gray-600">طالب في قسم القرآن الكريم</p>
            </div>
          </div>
          <div class="flex mb-4" aria-label="تقييم 5 نجوم">
            <i class="ri-star-fill text-yellow-400"></i>
            <i class="ri-star-fill text-yellow-400"></i>
            <i class="ri-star-fill text-yellow-400"></i>
            <i class="ri-star-fill text-yellow-400"></i>
            <i class="ri-star-fill text-yellow-400"></i>
          </div>
          <p class="text-gray-700 leading-relaxed">
            "تجربة رائعة مع {{ $academy->name ?? 'أكاديمية إتقان' }}. المعلمون متخصصون والمنهج واضح ومنظم. تمكنت من حفظ 5 أجزاء في 6 أشهر فقط."
          </p>
        </div>
        
        <div class="bg-white p-6 rounded-xl shadow-lg">
          <div class="flex items-center mb-4">
            <img src="https://images.unsplash.com/photo-1494790108755-2616b612b786?w=60&h=60&fit=crop&crop=face" alt="صورة فاطمة أحمد" class="w-12 h-12 rounded-full ml-4">
            <div>
              <h4 class="font-semibold text-gray-900">فاطمة أحمد</h4>
              <p class="text-sm text-gray-600">طالبة في القسم الأكاديمي</p>
            </div>
          </div>
          <div class="flex mb-4" aria-label="تقييم 5 نجوم">
            <i class="ri-star-fill text-yellow-400"></i>
            <i class="ri-star-fill text-yellow-400"></i>
            <i class="ri-star-fill text-yellow-400"></i>
            <i class="ri-star-fill text-yellow-400"></i>
            <i class="ri-star-fill text-yellow-400"></i>
          </div>
          <p class="text-gray-700 leading-relaxed">
            "الدروس الأكاديمية ممتازة والشرح واضح. تحسنت درجاتي بشكل كبير في الرياضيات والفيزياء بفضل المعلمين المتميزين."
          </p>
        </div>
        
        <div class="bg-white p-6 rounded-xl shadow-lg">
          <div class="flex items-center mb-4">
            <img src="https://images.unsplash.com/photo-1472099645785-5658abf4ff4e?w=60&h=60&fit=crop&crop=face" alt="صورة محمد علي" class="w-12 h-12 rounded-full ml-4">
            <div>
              <h4 class="font-semibold text-gray-900">محمد علي</h4>
              <p class="text-sm text-gray-600">ولي أمر</p>
            </div>
          </div>
          <div class="flex mb-4" aria-label="تقييم 5 نجوم">
            <i class="ri-star-fill text-yellow-400"></i>
            <i class="ri-star-fill text-yellow-400"></i>
            <i class="ri-star-fill text-yellow-400"></i>
            <i class="ri-star-fill text-yellow-400"></i>
            <i class="ri-star-fill text-yellow-400"></i>
          </div>
          <p class="text-gray-700 leading-relaxed">
            "{{ $academy->name ?? 'أكاديمية إتقان' }} غيرت مستوى ابني التعليمي. الدعم المستمر والمتابعة الدقيقة جعلته يحب التعلم أكثر."
          </p>
        </div>
      @endforelse
    </div>
  </div>
</section> 