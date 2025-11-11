<!-- Modern Hero Section -->
<section id="main-content" class="relative min-h-screen flex items-center overflow-hidden" role="banner">
  <!-- Grid Pattern Background -->
  <div class="absolute inset-0 opacity-60" style="background-image: linear-gradient(rgba(0,0,0,0.2) 1px, transparent 1px), linear-gradient(90deg, rgba(0,0,0,0.2) 1px, transparent 1px); background-size: 100px 100px;"></div>
  
  <!-- Enhanced Gradient Background -->
  <div class="absolute inset-0 bg-gradient-to-br from-primary/15 via-white to-secondary/15"></div>
  <div class="absolute inset-0 bg-[radial-gradient(circle_at_center,rgba(59,130,246,0.08),transparent_60%)]"></div>
  
  <!-- Enhanced Floating Elements -->
  <div class="absolute top-20 right-20 w-24 h-24 bg-gradient-to-br from-primary/10 to-transparent rounded-2xl rotate-12 animate-pulse"></div>
  <div class="absolute bottom-32 left-16 w-20 h-20 bg-gradient-to-br from-secondary/10 to-transparent rounded-2xl -rotate-12 animate-pulse" style="animation-delay: 1s;"></div>

  <div class="relative z-10 max-w-4xl mx-auto px-4 sm:px-6 lg:px-8 w-full text-center">
    <div class="space-y-8">
        <!-- Badge -->
        <div class="inline-flex items-center gap-2 bg-gradient-to-r from-primary/10 to-secondary/10 px-4 py-2 rounded-full border border-primary/20 animate-bounce">
          <div class="w-2 h-2 bg-primary rounded-full animate-pulse"></div>
          <span class="text-sm font-medium text-primary">منصة تعليمية متطورة</span>
        </div>

        <!-- Main Heading -->
        <h1 class="text-4xl sm:text-5xl lg:text-6xl xl:text-7xl font-bold leading-tight">
          <span class="text-gray-900">تعليم</span>
          <span class="bg-gradient-to-r from-primary to-secondary bg-clip-text text-transparent">متميز</span>
          <span class="text-gray-700">للمستقبل</span>
        </h1>

        <!-- Subheading -->
        <p class="text-xl lg:text-2xl text-gray-600 leading-loose max-w-3xl mx-auto">
          انضم إلى آلاف الطلاب الذين يطورون مهاراتهم في القرآن الكريم والتعليم الأكاديمي مع أفضل المعلمين المتخصصين
        </p>

        <!-- CTA Buttons -->
        <div class="flex flex-col sm:flex-row gap-6 justify-center">
          <a href="{{ route('student.register', ['subdomain' => $academy->subdomain ?? 'test-academy']) }}" 
             class="group relative px-10 py-5 bg-gradient-to-r from-primary to-secondary text-white rounded-2xl font-bold text-lg transition-all duration-300 transform hover:scale-105 hover:shadow-2xl overflow-hidden">
            <div class="absolute inset-0 bg-gradient-to-r from-secondary to-primary opacity-0 group-hover:opacity-100 transition-opacity duration-300"></div>
            <div class="absolute -inset-1 bg-gradient-to-r from-primary to-secondary rounded-2xl blur opacity-30 group-hover:opacity-60 transition-opacity duration-300"></div>
            <div class="absolute inset-0 bg-gradient-to-r from-transparent via-white to-transparent opacity-0 group-hover:opacity-20 transform -skew-x-12 -translate-x-full group-hover:translate-x-full transition-transform duration-700"></div>
            <span class="relative z-10 flex items-center justify-center gap-3">
              <i class="ri-rocket-line text-xl"></i>
              ابدأ رحلتك الآن
            </span>
          </a>
          
          <a href="#features" 
             class="group relative px-10 py-5 bg-white text-gray-700 rounded-2xl font-bold text-lg border-2 border-gray-200 transition-all duration-300 transform hover:scale-105 hover:shadow-2xl overflow-hidden">
            <div class="absolute inset-0 bg-gradient-to-r from-gray-50 to-gray-100 opacity-0 group-hover:opacity-100 transition-opacity duration-300"></div>
            <div class="absolute -inset-1 bg-gradient-to-r from-gray-300 to-gray-400 rounded-2xl blur opacity-0 group-hover:opacity-30 transition-opacity duration-300"></div>
            <div class="absolute inset-0 bg-gradient-to-r from-transparent via-white to-transparent opacity-0 group-hover:opacity-20 transform -skew-x-12 -translate-x-full group-hover:translate-x-full transition-transform duration-700"></div>
            <span class="relative z-10 flex items-center justify-center gap-3">
              <i class="ri-arrow-down-line text-xl"></i>
              اعرف المزيد
            </span>
          </a>
        </div>

        <!-- Academy Sections -->
        <div class="grid grid-cols-2 lg:grid-cols-4 gap-8 pt-8 border-t border-gray-100">
          <!-- Quran Circles -->
          <div class="feature-card">
            <div class="feature-icon quran-icon">
              <i class="ri-group-line"></i>
            </div>
            <div class="feature-content">
              <h3 class="feature-title">حلقات القرآن</h3>
              <p class="feature-subtitle">تعلم جماعي مع معلمين متخصصين</p>
            </div>
          </div>

          <!-- Individual Quran Learning -->
          <div class="feature-card">
            <div class="feature-icon individual-icon">
              <i class="ri-user-line"></i>
            </div>
            <div class="feature-content">
              <h3 class="feature-title">تعليم فردي</h3>
              <p class="feature-subtitle">حفظ شخصي مع متابعة مباشرة</p>
            </div>
          </div>

          <!-- Private Classes -->
          <div class="feature-card">
            <div class="feature-icon private-icon">
              <i class="ri-video-line"></i>
            </div>
            <div class="feature-content">
              <h3 class="feature-title">دروس خاصة</h3>
              <p class="feature-subtitle">تعليم أكاديمي مع معلمين خبراء</p>
            </div>
          </div>

          <!-- Interactive Courses -->
          <div class="feature-card">
            <div class="feature-icon interactive-icon">
              <i class="ri-computer-line"></i>
            </div>
            <div class="feature-content">
              <h3 class="feature-title">كورسات تفاعلية</h3>
              <p class="feature-subtitle">تعلم متقدم مع تقنيات حديثة</p>
            </div>
          </div>
        </div>
    </div>
  </div>

  <!-- Scroll Indicator -->
  <div class="absolute bottom-8 left-1/2 transform -translate-x-1/2">
    <div class="flex flex-col items-center gap-2 text-gray-400">
      <span class="text-sm font-medium">اكتشف المزيد</span>
      <div class="w-6 h-10 border-2 border-gray-300 rounded-full flex justify-center">
        <div class="w-1 h-3 bg-gray-400 rounded-full mt-2 animate-bounce"></div>
      </div>
    </div>
  </div>
</section>