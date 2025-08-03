<!-- Hero Section -->
<section id="main-content" class="relative overflow-hidden min-h-screen flex items-center bg-gradient-to-br from-gray-50 via-white to-blue-50" role="banner">
  <div class="absolute top-20 right-20 w-96 h-96 bg-primary/5 rounded-full blur-3xl"></div>
  <div class="absolute bottom-20 left-20 w-96 h-96 bg-secondary/5 rounded-full blur-3xl"></div>
  <div class="relative z-10 max-w-5xl mx-auto px-4 sm:px-6 lg:px-8 w-full text-center">
    <h1 class="text-6xl lg:text-7xl font-bold mb-8 leading-tight text-gray-900">
      {{ $academy->name ?? 'أكاديمية إتقان' }}
      <span class="block text-4xl lg:text-5xl mt-4 text-primary/80">{{ $academy->tagline ?? 'للتعليم الشامل' }}</span>
    </h1>
    <p class="text-2xl mb-12 text-gray-600 leading-relaxed max-w-3xl mx-auto">
      {{ $academy->description ?? 'منصة تعليمية متكاملة تجمع بين تحفيظ القرآن الكريم والمواد الأكاديمية لجميع المراحل الدراسية، مع معلمين متخصصين وكورسات تفاعلية تناسب احتياجاتك التعليمية' }}
    </p>
    <div class="flex flex-col sm:flex-row gap-6 justify-center mb-12">
      <a href="{{ route('student.register', ['subdomain' => $academy->subdomain ?? 'test-academy']) }}" 
         class="bg-primary text-white px-10 py-5 !rounded-button text-xl font-semibold hover:bg-secondary transition-colors duration-200 whitespace-nowrap focus:ring-custom" 
         aria-label="ابدأ رحلتك التعليمية الآن">
        ابدأ التعلم الآن
      </a>
      <a href="#services" 
         class="bg-gray-50 text-gray-700 px-10 py-5 !rounded-button text-xl font-semibold hover:bg-gray-100 transition-colors duration-200 whitespace-nowrap focus:ring-custom" 
         aria-label="اكتشف خدماتنا التعليمية">
        تعرف على خدماتنا
      </a>
    </div>
    
    <!-- Trust Indicators -->
    <div class="flex flex-wrap justify-center gap-4 mb-8">
      <div class="trust-badge">
        <i class="ri-shield-check-line"></i>
        <span>معتمد رسمياً</span>
      </div>
      <div class="trust-badge">
        <i class="ri-award-line"></i>
        <span>جودة مضمونة</span>
      </div>
      <div class="trust-badge">
        <i class="ri-customer-service-2-line"></i>
        <span>دعم 24/7</span>
      </div>
      <div class="trust-badge">
        <i class="ri-money-dollar-circle-line"></i>
        <span>ضمان استرداد المال</span>
      </div>
    </div>
  </div>
</section> 