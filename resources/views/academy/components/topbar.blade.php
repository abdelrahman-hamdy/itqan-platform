<!-- Topbar Navigation -->
<nav id="navigation" class="bg-white shadow-lg sticky top-0 z-50" role="navigation" aria-label="التنقل الرئيسي">
  <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
    <div class="flex justify-between items-center h-20">
      <!-- Logo and Academy Name -->
      <div class="flex items-center h-20">
        <a href="{{ route('academy.home', ['subdomain' => $academy->subdomain ?? 'test-academy']) }}" class="flex items-center h-full px-2 focus:outline-none" aria-label="العودة إلى الصفحة الرئيسية">
          @if($academy->logo)
            <img src="{{ $academy->logo }}" alt="{{ $academy->name }}" class="w-8 h-8 rounded-full object-cover">
          @else
            <div class="w-8 h-8 flex items-center justify-center bg-primary rounded-full">
              <i class="ri-book-open-line text-white text-lg"></i>
            </div>
          @endif
          <span class="mr-3 text-xl font-bold text-primary">{{ $academy->name ?? 'أكاديمية إتقان' }}</span>
        </a>
      </div>

      <!-- Centered Navigation Menu -->
      <div class="hidden md:flex items-center justify-center h-20">
        <div class="flex items-center space-x-1 space-x-reverse">
          @if($academy->quran_enabled ?? true)
            <a href="#quran" class="flex items-center h-20 px-4 text-gray-700 hover:text-primary hover:bg-gray-50 transition-colors duration-200 focus:outline-none font-medium" aria-label="انتقل إلى قسم القرآن الكريم">القرآن الكريم</a>
          @endif
          @if($academy->academic_enabled ?? true)
            <a href="#academic" class="flex items-center h-20 px-4 text-gray-700 hover:text-primary hover:bg-gray-50 transition-colors duration-200 focus:outline-none font-medium" aria-label="انتقل إلى القسم الأكاديمي">الأكاديمي</a>
          @endif
          @if($academy->recorded_courses_enabled ?? true)
            <a href="#courses" class="flex items-center h-20 px-4 text-gray-700 hover:text-primary hover:bg-gray-50 transition-colors duration-200 focus:outline-none font-medium" aria-label="انتقل إلى الكورسات المسجلة">الكورسات المسجلة</a>
          @endif
          <a href="#about" class="flex items-center h-20 px-4 text-gray-700 hover:text-primary hover:bg-gray-50 transition-colors duration-200 focus:outline-none font-medium" aria-label="انتقل إلى قسم من نحن">من نحن</a>
          <a href="#contact" class="flex items-center h-20 px-4 text-gray-700 hover:text-primary hover:bg-gray-50 transition-colors duration-200 focus:outline-none font-medium" aria-label="انتقل إلى قسم اتصل بنا">اتصل بنا</a>
        </div>
      </div>

      <!-- Right Side Actions -->
      <div class="flex items-center space-x-4 space-x-reverse">
        @auth
          <!-- User Dropdown -->
          <div class="relative h-20 flex items-center" x-data="{ open: false }">
            <button @click="open = !open" class="flex items-center h-20 px-3 space-x-2 space-x-reverse text-gray-700 hover:text-primary hover:bg-gray-50 focus:outline-none transition-colors duration-200" aria-label="قائمة المستخدم" aria-expanded="false">
              @if(auth()->user()->avatar)
                <img src="{{ auth()->user()->avatar }}" alt="{{ auth()->user()->name }}" class="w-8 h-8 rounded-full object-cover">
              @else
                <div class="w-8 h-8 bg-primary rounded-full flex items-center justify-center">
                  <i class="ri-user-line text-white text-sm"></i>
                </div>
              @endif
              <span class="hidden sm:block text-sm font-medium">{{ auth()->user()->name }}</span>
              <i class="ri-arrow-down-s-line text-sm"></i>
            </button>
            
            <!-- Dropdown Menu -->
            <div x-show="open" @click.away="open = false" x-transition:enter="transition ease-out duration-100" x-transition:enter-start="transform opacity-0 scale-95" x-transition:enter-end="transform opacity-100 scale-100" x-transition:leave="transition ease-in duration-75" x-transition:leave-start="transform opacity-100 scale-100" x-transition:leave-end="transform opacity-0 scale-95" class="absolute right-0 top-full mt-2 w-48 bg-white border border-gray-200 rounded-lg shadow-lg z-50 overflow-hidden" role="menu">
              <div class="py-1">
                <div class="px-4 py-2 border-b border-gray-100">
                  <p class="text-sm font-medium text-gray-900">{{ auth()->user()->name }}</p>
                  <p class="text-xs text-gray-500">{{ auth()->user()->email }}</p>
                </div>
                <a href="{{ route('student.profile', ['subdomain' => $academy->subdomain ?? 'test-academy']) }}" class="flex items-center px-4 py-2 text-sm text-gray-700 hover:bg-gray-50 transition-colors duration-200" role="menuitem">
                  <i class="ri-user-line ml-2"></i>
                  الملف الشخصي
                </a>
                <div class="border-t border-gray-100"></div>
                <form method="POST" action="{{ route('logout', ['subdomain' => $academy->subdomain ?? 'test-academy']) }}" class="block">
                  @csrf
                  <button type="submit" class="flex items-center w-full px-4 py-2 text-sm text-red-600 hover:bg-red-50 transition-colors duration-200" role="menuitem">
                    <i class="ri-logout-box-line ml-2"></i>
                    تسجيل الخروج
                  </button>
                </form>
              </div>
            </div>
          </div>
        @else
          <!-- Login Button for Guests -->
          <a href="{{ route('login', ['subdomain' => $academy->subdomain ?? 'test-academy']) }}" class="bg-primary text-white px-6 py-2 !rounded-button hover:bg-secondary transition-colors duration-200 whitespace-nowrap focus:outline-none" aria-label="تسجيل الدخول إلى حسابك">
            تسجيل الدخول
          </a>
        @endauth

        <!-- Mobile Menu Button -->
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
          <a href="#quran" class="block px-3 py-2 text-gray-700 hover:text-primary hover:bg-gray-50 rounded-md focus:outline-none" aria-label="انتقل إلى قسم القرآن الكريم">القرآن الكريم</a>
        @endif
        @if($academy->academic_enabled ?? true)
          <a href="#academic" class="block px-3 py-2 text-gray-700 hover:text-primary hover:bg-gray-50 rounded-md focus:outline-none" aria-label="انتقل إلى القسم الأكاديمي">الأكاديمي</a>
        @endif
        @if($academy->recorded_courses_enabled ?? true)
          <a href="#courses" class="block px-3 py-2 text-gray-700 hover:text-primary hover:bg-gray-50 rounded-md focus:outline-none" aria-label="انتقل إلى الكورسات المسجلة">الكورسات المسجلة</a>
        @endif
        <a href="#about" class="block px-3 py-2 text-gray-700 hover:text-primary hover:bg-gray-50 rounded-md focus:outline-none" aria-label="انتقل إلى قسم من نحن">من نحن</a>
        <a href="#contact" class="block px-3 py-2 text-gray-700 hover:text-primary hover:bg-gray-50 rounded-md focus:outline-none" aria-label="انتقل إلى قسم اتصل بنا">اتصل بنا</a>
        
        @auth
          <div class="border-t border-gray-200 pt-2 mt-2">
            <a href="{{ route('student.profile', ['subdomain' => $academy->subdomain ?? 'test-academy']) }}" class="block px-3 py-2 text-gray-700 hover:text-primary hover:bg-gray-50 rounded-md focus:outline-none">الملف الشخصي</a>
            <form method="POST" action="{{ route('logout', ['subdomain' => $academy->subdomain ?? 'test-academy']) }}">
              @csrf
              <button type="submit" class="block w-full text-right px-3 py-2 text-red-600 hover:text-red-700 hover:bg-red-50 rounded-md focus:outline-none">تسجيل الخروج</button>
            </form>
          </div>
        @endauth
      </div>
    </div>
  </div>
</nav>

<!-- Alpine.js for dropdown functionality -->
<script src="https://unpkg.com/alpinejs@3.x.x/dist/cdn.min.js" defer></script>

<!-- Mobile menu toggle script -->
<script>
document.addEventListener('DOMContentLoaded', function() {
  const mobileMenuButton = document.getElementById('mobile-menu-button');
  const mobileMenu = document.getElementById('mobile-menu');
  
  if (mobileMenuButton && mobileMenu) {
    mobileMenuButton.addEventListener('click', function() {
      mobileMenu.classList.toggle('hidden');
      const isExpanded = !mobileMenu.classList.contains('hidden');
      mobileMenuButton.setAttribute('aria-expanded', isExpanded);
    });
  }
});
</script>
