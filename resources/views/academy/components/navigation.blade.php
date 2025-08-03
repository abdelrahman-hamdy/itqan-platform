<!-- Navigation -->
<nav id="navigation" class="bg-white shadow-lg sticky top-0 z-50" role="navigation" aria-label="التنقل الرئيسي">
  <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
    <div class="flex justify-between items-center h-20">
      <div class="flex items-center space-x-8 space-x-reverse">
        <div class="flex items-center">
          <div class="w-8 h-8 flex items-center justify-center">
            <i class="ri-book-open-line text-2xl text-primary"></i>
          </div>
          <span class="mr-2 text-xl font-bold text-primary">{{ $academy->name ?? 'أكاديمية إتقان' }}</span>
        </div>
        <div class="hidden md:flex items-center space-x-6 space-x-reverse">
          @if($academy->quran_enabled ?? true)
            <a href="#quran" class="text-gray-700 hover:text-primary transition-colors duration-200 focus:ring-custom" aria-label="انتقل إلى قسم القرآن الكريم">القرآن الكريم</a>
          @endif
          @if($academy->academic_enabled ?? true)
            <a href="#academic" class="text-gray-700 hover:text-primary transition-colors duration-200 focus:ring-custom" aria-label="انتقل إلى القسم الأكاديمي">الأكاديمي</a>
          @endif
          @if($academy->recorded_courses_enabled ?? true)
            <a href="#courses" class="text-gray-700 hover:text-primary transition-colors duration-200 focus:ring-custom" aria-label="انتقل إلى الكورسات المسجلة">الكورسات المسجلة</a>
          @endif
          <a href="#about" class="text-gray-700 hover:text-primary transition-colors duration-200 focus:ring-custom" aria-label="انتقل إلى قسم من نحن">من نحن</a>
          <a href="#contact" class="text-gray-700 hover:text-primary transition-colors duration-200 focus:ring-custom" aria-label="انتقل إلى قسم اتصل بنا">اتصل بنا</a>
        </div>
      </div>
      <div class="flex items-center space-x-4 space-x-reverse">
        <div class="relative">
          <button class="flex items-center space-x-2 space-x-reverse text-gray-700 hover:text-primary focus:ring-custom" aria-label="اختيار اللغة" aria-expanded="false">
            <span>العربية</span>
            <div class="w-4 h-4 flex items-center justify-center">
              <i class="ri-arrow-down-s-line"></i>
            </div>
          </button>
          <div class="absolute top-full left-0 mt-2 bg-white border border-gray-200 rounded-lg shadow-lg hidden" role="menu">
            <a href="#" class="block px-4 py-2 text-gray-700 hover:bg-gray-50" role="menuitem">العربية</a>
            <a href="#" class="block px-4 py-2 text-gray-700 hover:bg-gray-50" role="menuitem">English</a>
          </div>
        </div>
        <a href="{{ route('login', ['subdomain' => $academy->subdomain ?? 'test-academy']) }}" class="bg-primary text-white px-6 py-2 !rounded-button hover:bg-secondary transition-colors duration-200 whitespace-nowrap focus:ring-custom" aria-label="تسجيل الدخول إلى حسابك">
          تسجيل الدخول
        </a>
        <button class="md:hidden focus:ring-custom" aria-label="فتح قائمة التنقل" aria-expanded="false" id="mobile-menu-button">
          <div class="w-6 h-6 flex items-center justify-center">
            <i class="ri-menu-line text-xl"></i>
          </div>
        </button>
      </div>
    </div>
    <!-- Mobile Navigation Menu -->
    <div class="md:hidden hidden" id="mobile-menu">
      <div class="px-2 pt-2 pb-3 space-y-1 bg-white border-t border-gray-200">
        @if($academy->quran_enabled ?? true)
          <a href="#quran" class="block px-3 py-2 text-gray-700 hover:text-primary hover:bg-gray-50 rounded-md focus:ring-custom" aria-label="انتقل إلى قسم القرآن الكريم">القرآن الكريم</a>
        @endif
        @if($academy->academic_enabled ?? true)
          <a href="#academic" class="block px-3 py-2 text-gray-700 hover:text-primary hover:bg-gray-50 rounded-md focus:ring-custom" aria-label="انتقل إلى القسم الأكاديمي">الأكاديمي</a>
        @endif
        @if($academy->recorded_courses_enabled ?? true)
          <a href="#courses" class="block px-3 py-2 text-gray-700 hover:text-primary hover:bg-gray-50 rounded-md focus:ring-custom" aria-label="انتقل إلى الكورسات المسجلة">الكورسات المسجلة</a>
        @endif
        <a href="#about" class="block px-3 py-2 text-gray-700 hover:text-primary hover:bg-gray-50 rounded-md focus:ring-custom" aria-label="انتقل إلى قسم من نحن">من نحن</a>
        <a href="#contact" class="block px-3 py-2 text-gray-700 hover:text-primary hover:bg-gray-50 rounded-md focus:ring-custom" aria-label="انتقل إلى قسم اتصل بنا">اتصل بنا</a>
      </div>
    </div>
  </div>
</nav> 