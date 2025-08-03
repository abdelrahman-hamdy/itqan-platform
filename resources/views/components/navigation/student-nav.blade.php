<!-- Student Navigation Component -->
<nav id="navigation" class="bg-white shadow-lg sticky top-0 z-50" role="navigation" aria-label="التنقل الرئيسي للطالب">
  <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
    <div class="flex justify-between items-center h-20">
      <!-- Logo and Main Navigation -->
      <div class="flex items-center space-x-8 space-x-reverse">
        <div class="flex items-center">
          <div class="w-8 h-8 flex items-center justify-center">
            <i class="ri-book-open-line text-2xl text-primary"></i>
          </div>
          <span class="mr-2 text-xl font-bold text-primary">{{ auth()->user()->academy->name ?? 'أكاديمية إتقان' }}</span>
        </div>
        <div class="hidden md:flex items-center space-x-6 space-x-reverse">
          <a href="#quran-circles" class="text-gray-700 hover:text-primary transition-colors duration-200 focus:ring-custom" aria-label="انتقل إلى دوائر القرآن">دوائر القرآن</a>
          <a href="#quran-private" class="text-gray-700 hover:text-primary transition-colors duration-200 focus:ring-custom" aria-label="انتقل إلى الدروس الخاصة">الدروس الخاصة</a>
          <a href="#interactive-courses" class="text-gray-700 hover:text-primary transition-colors duration-200 focus:ring-custom" aria-label="انتقل إلى الكورسات التفاعلية">الكورسات التفاعلية</a>
          <a href="#recorded-courses" class="text-gray-700 hover:text-primary transition-colors duration-200 focus:ring-custom" aria-label="انتقل إلى الكورسات المسجلة">الكورسات المسجلة</a>
        </div>
      </div>

      <!-- Search, Notifications, and User Profile -->
      <div class="flex items-center space-x-4 space-x-reverse">
        <!-- Search Bar -->
        <div class="relative hidden md:block">
          <div class="absolute inset-y-0 right-0 pr-3 flex items-center pointer-events-none">
            <i class="ri-search-line text-gray-400"></i>
          </div>
          <input type="text" 
                 placeholder="البحث في الكورسات والدروس..." 
                 class="w-64 pl-10 pr-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary focus:border-transparent"
                 aria-label="البحث في المحتوى">
        </div>

        <!-- Notifications -->
        <div class="relative">
          <button class="relative p-2 text-gray-700 hover:text-primary focus:ring-custom" 
                  aria-label="الإشعارات" 
                  aria-expanded="false"
                  id="notifications-button">
            <i class="ri-notification-3-line text-xl"></i>
            <span class="absolute -top-1 -right-1 bg-red-500 text-white text-xs rounded-full h-5 w-5 flex items-center justify-center">3</span>
          </button>
          <!-- Notifications Dropdown -->
          <div class="absolute left-0 mt-2 w-80 bg-white border border-gray-200 rounded-lg shadow-lg hidden" 
               role="menu" 
               id="notifications-dropdown">
            <div class="p-4">
              <h3 class="text-lg font-semibold mb-3">الإشعارات</h3>
              <div class="space-y-3">
                <div class="flex items-start space-x-3 space-x-reverse">
                  <div class="w-2 h-2 bg-blue-500 rounded-full mt-2"></div>
                  <div class="flex-1">
                    <p class="text-sm font-medium">درس جديد متاح</p>
                    <p class="text-xs text-gray-500">تم إضافة درس جديد في دورة القرآن الكريم</p>
                    <p class="text-xs text-gray-400 mt-1">منذ 5 دقائق</p>
                  </div>
                </div>
                <div class="flex items-start space-x-3 space-x-reverse">
                  <div class="w-2 h-2 bg-green-500 rounded-full mt-2"></div>
                  <div class="flex-1">
                    <p class="text-sm font-medium">تم إكمال الواجب</p>
                    <p class="text-xs text-gray-500">تم تقييم واجبك في الرياضيات</p>
                    <p class="text-xs text-gray-400 mt-1">منذ ساعة</p>
                  </div>
                </div>
                <div class="flex items-start space-x-3 space-x-reverse">
                  <div class="w-2 h-2 bg-yellow-500 rounded-full mt-2"></div>
                  <div class="flex-1">
                    <p class="text-sm font-medium">تذكير بالدرس</p>
                    <p class="text-xs text-gray-500">درس القرآن غداً الساعة 4 مساءً</p>
                    <p class="text-xs text-gray-400 mt-1">منذ 3 ساعات</p>
                  </div>
                </div>
              </div>
              <a href="#" class="block text-center text-primary text-sm mt-3 hover:underline">عرض جميع الإشعارات</a>
            </div>
          </div>
        </div>

        <!-- User Profile Dropdown -->
        <div class="relative">
          <button class="flex items-center space-x-2 space-x-reverse text-gray-700 hover:text-primary focus:ring-custom" 
                  aria-label="قائمة المستخدم" 
                  aria-expanded="false"
                  id="user-menu-button">
            <div class="w-8 h-8 bg-primary rounded-full flex items-center justify-center">
              <span class="text-white text-sm font-medium">
                {{ substr(auth()->user()->studentProfile->first_name ?? auth()->user()->name, 0, 1) }}
              </span>
            </div>
            <span class="hidden md:block">{{ auth()->user()->studentProfile->first_name ?? auth()->user()->name }}</span>
            <i class="ri-arrow-down-s-line"></i>
          </button>
          <!-- User Menu Dropdown -->
          <div class="absolute left-0 mt-2 w-48 bg-white border border-gray-200 rounded-lg shadow-lg hidden" 
               role="menu" 
               id="user-menu-dropdown">
            <div class="py-1">
                             <a href="{{ route('student.profile', ['subdomain' => auth()->user()->academy->subdomain ?? 'itqan-academy']) }}" class="block px-4 py-2 text-gray-700 hover:bg-gray-50" role="menuitem">
                <i class="ri-user-line ml-2"></i>
                الملف الشخصي
              </a>
              <a href="#" class="block px-4 py-2 text-gray-700 hover:bg-gray-50" role="menuitem">
                <i class="ri-settings-3-line ml-2"></i>
                الإعدادات
              </a>
              <a href="#" class="block px-4 py-2 text-gray-700 hover:bg-gray-50" role="menuitem">
                <i class="ri-wallet-3-line ml-2"></i>
                الاشتراكات
              </a>
              <hr class="my-1">
              <form method="POST" action="{{ route('logout', ['subdomain' => auth()->user()->academy->subdomain ?? 'itqan-academy']) }}">
                @csrf
                <button type="submit" class="block w-full text-right px-4 py-2 text-gray-700 hover:bg-gray-50" role="menuitem">
                  <i class="ri-logout-box-r-line ml-2"></i>
                  تسجيل الخروج
                </button>
              </form>
            </div>
          </div>
        </div>

        <!-- Mobile Menu Button -->
        <button class="md:hidden focus:ring-custom" 
                aria-label="فتح قائمة التنقل" 
                aria-expanded="false" 
                id="mobile-menu-button">
          <div class="w-6 h-6 flex items-center justify-center">
            <i class="ri-menu-line text-xl"></i>
          </div>
        </button>
      </div>
    </div>

    <!-- Mobile Navigation Menu -->
    <div class="md:hidden hidden" id="mobile-menu">
      <div class="px-2 pt-2 pb-3 space-y-1 bg-white border-t border-gray-200">
        <a href="#quran-circles" class="block px-3 py-2 text-gray-700 hover:text-primary hover:bg-gray-50 rounded-md focus:ring-custom">دوائر القرآن</a>
        <a href="#quran-private" class="block px-3 py-2 text-gray-700 hover:text-primary hover:bg-gray-50 rounded-md focus:ring-custom">الدروس الخاصة</a>
        <a href="#interactive-courses" class="block px-3 py-2 text-gray-700 hover:text-primary hover:bg-gray-50 rounded-md focus:ring-custom">الكورسات التفاعلية</a>
        <a href="#recorded-courses" class="block px-3 py-2 text-gray-700 hover:text-primary hover:bg-gray-50 rounded-md focus:ring-custom">الكورسات المسجلة</a>
      </div>
    </div>
  </div>
</nav>

<script>
document.addEventListener('DOMContentLoaded', function() {
  // Mobile menu toggle
  const mobileMenuButton = document.getElementById('mobile-menu-button');
  const mobileMenu = document.getElementById('mobile-menu');
  
  mobileMenuButton?.addEventListener('click', function() {
    const isExpanded = this.getAttribute('aria-expanded') === 'true';
    this.setAttribute('aria-expanded', !isExpanded);
    mobileMenu.classList.toggle('hidden');
  });

  // Notifications dropdown
  const notificationsButton = document.getElementById('notifications-button');
  const notificationsDropdown = document.getElementById('notifications-dropdown');
  
  notificationsButton?.addEventListener('click', function() {
    const isExpanded = this.getAttribute('aria-expanded') === 'true';
    this.setAttribute('aria-expanded', !isExpanded);
    notificationsDropdown.classList.toggle('hidden');
  });

  // User menu dropdown
  const userMenuButton = document.getElementById('user-menu-button');
  const userMenuDropdown = document.getElementById('user-menu-dropdown');
  
  userMenuButton?.addEventListener('click', function() {
    const isExpanded = this.getAttribute('aria-expanded') === 'true';
    this.setAttribute('aria-expanded', !isExpanded);
    userMenuDropdown.classList.toggle('hidden');
  });

  // Close dropdowns when clicking outside
  document.addEventListener('click', function(event) {
    if (!notificationsButton?.contains(event.target)) {
      notificationsDropdown?.classList.add('hidden');
      notificationsButton?.setAttribute('aria-expanded', 'false');
    }
    
    if (!userMenuButton?.contains(event.target)) {
      userMenuDropdown?.classList.add('hidden');
      userMenuButton?.setAttribute('aria-expanded', 'false');
    }
  });
});
</script> 