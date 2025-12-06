@php
  // Get gradient palette for this academy
  $gradientPalette = $academy?->gradient_palette ?? \App\Enums\GradientPalette::OCEAN_BREEZE;
  $colors = $gradientPalette->getColors();
  $gradientFrom = $colors['from'];
  $gradientTo = $colors['to'];
@endphp

<!-- Features Section - Template 2: Clean Icon-Based Cards with Gradient Background -->
<section id="features" class="relative py-24 overflow-hidden" role="region" aria-labelledby="features-heading">
  <!-- Gradient Background -->
  <div class="absolute inset-0 bg-gradient-to-br from-{{ $gradientFrom }}/10 via-white to-{{ $gradientTo }}/10"></div>
  <div class="absolute inset-0 bg-[radial-gradient(circle_at_top_right,rgba(59,130,246,0.08),transparent_50%)]"></div>

  <div class="relative max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
    <div class="text-center mb-16">
      <h2 id="features-heading" class="text-3xl font-bold text-gray-900 mb-4">{{ $heading ?? 'لماذا نحن؟' }}</h2>
      @if(isset($subheading))
        <p class="text-lg text-gray-600 max-w-3xl mx-auto">{{ $subheading }}</p>
      @endif
    </div>

    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
      <!-- Feature 1 -->
      <div class="bg-white/80 backdrop-blur-sm border-2 border-gray-200 rounded-xl p-8 hover:border-{{ $gradientFrom }}/40 hover:shadow-lg transition-all duration-200">
        <div class="w-14 h-14 bg-gradient-to-br from-{{ $gradientFrom }}/20 to-{{ $gradientTo }}/20 rounded-xl flex items-center justify-center mb-5 border border-{{ $gradientFrom }}/30">
          <i class="ri-user-star-line text-3xl text-{{ $gradientFrom }}"></i>
        </div>
        <h3 class="text-xl font-bold text-gray-900 mb-3">معلمون متخصصون</h3>
        <p class="text-gray-600 leading-relaxed">
          نخبة من أفضل المعلمين الحاصلين على شهادات معتمدة وخبرة واسعة في التدريس
        </p>
      </div>

      <!-- Feature 2 -->
      <div class="bg-white/80 backdrop-blur-sm border-2 border-gray-200 rounded-xl p-8 hover:border-{{ $gradientFrom }}/40 hover:shadow-lg transition-all duration-200">
        <div class="w-14 h-14 bg-gradient-to-br from-{{ $gradientFrom }}/20 to-{{ $gradientTo }}/20 rounded-xl flex items-center justify-center mb-5 border border-{{ $gradientFrom }}/30">
          <i class="ri-calendar-check-line text-3xl text-{{ $gradientFrom }}"></i>
        </div>
        <h3 class="text-xl font-bold text-gray-900 mb-3">جداول مرنة</h3>
        <p class="text-gray-600 leading-relaxed">
          اختر الوقت المناسب لك من بين مجموعة واسعة من المواعيد المتاحة
        </p>
      </div>

      <!-- Feature 3 -->
      <div class="bg-white/80 backdrop-blur-sm border-2 border-gray-200 rounded-xl p-8 hover:border-{{ $gradientFrom }}/40 hover:shadow-lg transition-all duration-200">
        <div class="w-14 h-14 bg-gradient-to-br from-{{ $gradientFrom }}/20 to-{{ $gradientTo }}/20 rounded-xl flex items-center justify-center mb-5 border border-{{ $gradientFrom }}/30">
          <i class="ri-video-line text-3xl text-{{ $gradientFrom }}"></i>
        </div>
        <h3 class="text-xl font-bold text-gray-900 mb-3">تعليم تفاعلي</h3>
        <p class="text-gray-600 leading-relaxed">
          منصة تعليمية تفاعلية بتقنية عالية تضمن أفضل تجربة تعليمية
        </p>
      </div>

      <!-- Feature 4 -->
      <div class="bg-white/80 backdrop-blur-sm border-2 border-gray-200 rounded-xl p-8 hover:border-{{ $gradientFrom }}/40 hover:shadow-lg transition-all duration-200">
        <div class="w-14 h-14 bg-gradient-to-br from-{{ $gradientFrom }}/20 to-{{ $gradientTo }}/20 rounded-xl flex items-center justify-center mb-5 border border-{{ $gradientFrom }}/30">
          <i class="ri-file-text-line text-3xl text-{{ $gradientFrom }}"></i>
        </div>
        <h3 class="text-xl font-bold text-gray-900 mb-3">متابعة دورية</h3>
        <p class="text-gray-600 leading-relaxed">
          تقارير مفصلة عن تقدم الطالب وتقييم مستمر لضمان تحقيق الأهداف التعليمية
        </p>
      </div>

      <!-- Feature 5 -->
      <div class="bg-white/80 backdrop-blur-sm border-2 border-gray-200 rounded-xl p-8 hover:border-{{ $gradientFrom }}/40 hover:shadow-lg transition-all duration-200">
        <div class="w-14 h-14 bg-gradient-to-br from-{{ $gradientFrom }}/20 to-{{ $gradientTo }}/20 rounded-xl flex items-center justify-center mb-5 border border-{{ $gradientFrom }}/30">
          <i class="ri-shield-check-line text-3xl text-{{ $gradientFrom }}"></i>
        </div>
        <h3 class="text-xl font-bold text-gray-900 mb-3">بيئة آمنة</h3>
        <p class="text-gray-600 leading-relaxed">
          بيئة تعليمية آمنة ومراقبة تضمن حماية وخصوصية جميع المستخدمين
        </p>
      </div>

      <!-- Feature 6 -->
      <div class="bg-white/80 backdrop-blur-sm border-2 border-gray-200 rounded-xl p-8 hover:border-{{ $gradientFrom }}/40 hover:shadow-lg transition-all duration-200">
        <div class="w-14 h-14 bg-gradient-to-br from-{{ $gradientFrom }}/20 to-{{ $gradientTo }}/20 rounded-xl flex items-center justify-center mb-5 border border-{{ $gradientFrom }}/30">
          <i class="ri-customer-service-2-line text-3xl text-{{ $gradientFrom }}"></i>
        </div>
        <h3 class="text-xl font-bold text-gray-900 mb-3">دعم فني متواصل</h3>
        <p class="text-gray-600 leading-relaxed">
          فريق دعم فني متاح على مدار الساعة للإجابة على استفساراتك ومساعدتك
        </p>
      </div>
    </div>
  </div>
</section>
