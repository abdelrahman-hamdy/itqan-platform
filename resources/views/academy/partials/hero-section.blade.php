<!-- Hero Section -->
<section class="relative overflow-hidden min-h-screen flex items-center hero-gradient">
  <!-- Background Decorative Elements -->
  <div class="absolute top-20 right-20 w-96 h-96 bg-primary-500/5 rounded-full blur-3xl"></div>
  <div class="absolute bottom-20 left-20 w-96 h-96 bg-primary-600/5 rounded-full blur-3xl"></div>
  
  <!-- Content Container -->
  <div class="relative z-10 max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 w-full text-center py-20">
    <!-- Main Heading -->
    <h1 class="text-4xl sm:text-5xl lg:text-6xl xl:text-7xl font-bold mb-8 leading-tight text-gray-900 font-arabic">
      {{ $academy->name }}
      <span class="block text-2xl sm:text-3xl lg:text-4xl xl:text-5xl mt-4 text-primary-500/80">
        {{ $academy->tagline ?? 'للتعليم الشامل' }}
      </span>
    </h1>
    
    <!-- Description -->
    <p class="text-lg sm:text-xl lg:text-2xl mb-12 text-gray-600 leading-relaxed max-w-4xl mx-auto font-arabic">
      {{ $academy->description ?? 'منصة تعليمية متكاملة تجمع بين تحفيظ القرآن الكريم والمواد الدراسية لجميع المراحل الدراسية، مع معلمين متخصصين وكورسات تفاعلية تناسب احتياجاتك التعليمية' }}
    </p>
    
    <!-- Action Buttons -->
    <div class="flex flex-col sm:flex-row gap-6 justify-center">
      <a href="#quran" 
         class="inline-flex items-center px-8 py-4 bg-primary-500 hover:bg-primary-600 text-white text-lg font-semibold rounded-md transition-colors duration-200 focus:outline-none focus:ring-2 focus:ring-primary-500 focus:ring-offset-2 font-arabic">
        <svg class="w-6 h-6 ms-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6.253v13m0-13C10.832 5.477 9.246 5 7.5 5S4.168 5.477 3 6.253v13C4.168 18.477 5.754 18 7.5 18s3.332.477 4.5 1.253m0-13C13.168 5.477 14.754 5 16.5 5c1.746 0 3.332.477 4.5 1.253v13C20.832 18.477 19.246 18 17.5 18c-1.746 0-3.332.477-4.5 1.253"></path>
        </svg>
        ابدأ التعلم الآن
      </a>
      
      <a href="#about" 
         class="inline-flex items-center px-8 py-4 bg-white hover:bg-gray-50 text-gray-700 border border-gray-300 text-lg font-semibold rounded-md transition-colors duration-200 focus:outline-none focus:ring-2 focus:ring-primary-500 focus:ring-offset-2 font-arabic">
        <svg class="w-6 h-6 ms-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path>
        </svg>
        تعرف على خدماتنا
      </a>
    </div>

    <!-- Feature Highlights -->
    <div class="mt-16 grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-8 max-w-4xl mx-auto">
      @if($academy->quran_enabled ?? true)
        <div class="text-center">
          <div class="w-16 h-16 bg-success-100 rounded-full flex items-center justify-center mx-auto mb-4">
            <svg class="w-8 h-8 text-success-600" fill="currentColor" viewBox="0 0 24 24">
              <path d="M12 3L1 9l11 6 9-4.91V17h2V9M5 13.18v4L12 21l7-3.82v-4L12 17l-7-3.82Z"/>
            </svg>
          </div>
          <h3 class="text-lg font-semibold text-gray-900 mb-2 font-arabic">تحفيظ القرآن الكريم</h3>
          <p class="text-sm text-gray-600 font-arabic">حلقات تحفيظ مع أفضل المعلمين المتخصصين</p>
        </div>
      @endif

      @if($academy->academic_enabled ?? true)
        <div class="text-center">
          <div class="w-16 h-16 bg-primary-100 rounded-full flex items-center justify-center mx-auto mb-4">
            <svg class="w-8 h-8 text-primary-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 14l9-5-9-5-9 5 9 5z"></path>
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 14l6.16-3.422a12.083 12.083 0 01.665 6.479A11.952 11.952 0 0012 20.055a11.952 11.952 0 00-6.824-2.998 12.078 12.078 0 01.665-6.479L12 14z"></path>
            </svg>
          </div>
          <h3 class="text-lg font-semibold text-gray-900 mb-2 font-arabic">التعليم الدراسي</h3>
          <p class="text-sm text-gray-600 font-arabic">مناهج شاملة لجميع المراحل التعليمية</p>
        </div>
      @endif

      @if($academy->recorded_courses_enabled ?? true)
        <div class="text-center">
          <div class="w-16 h-16 bg-warning-100 rounded-full flex items-center justify-center mx-auto mb-4">
            <svg class="w-8 h-8 text-warning-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M14.828 14.828a4 4 0 01-5.656 0M9 10h1.5a2.5 2.5 0 110 5H9m4.5-1.206a11.955 11.955 0 01-2.5 2.829 11.955 11.955 0 01-2.5-2.829m0 0V11m0 3.207L6.707 21A2 2 0 014 19.293V7a2 2 0 012.293-1.707L9 7h6l2.293-1.707A2 2 0 0119 7v12.293A2 2 0 0116.707 21L14 18.207"></path>
            </svg>
          </div>
          <h3 class="text-lg font-semibold text-gray-900 mb-2 font-arabic">دورات مسجلة</h3>
          <p class="text-sm text-gray-600 font-arabic">تعلم في أي وقت وبالسرعة التي تناسبك</p>
        </div>
      @endif
    </div>
  </div>
</section>