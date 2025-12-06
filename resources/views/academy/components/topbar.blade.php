<!-- Topbar Navigation -->
<nav id="navigation" class="bg-white shadow-lg sticky top-0 z-50" role="navigation" aria-label="التنقل الرئيسي">
  <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
    <div class="flex justify-between items-center h-20">
      <!-- Logo and Academy Name -->
      <div class="flex items-center h-20">
        <x-academy-logo
          :academy="$academy"
          size="md"
          :showName="true"
          :href="route('academy.home', ['subdomain' => $academy->subdomain ?? 'test-academy'])" />
      </div>

      <!-- Centered Navigation Menu -->
      <div class="hidden md:flex items-center justify-center h-20">
        <div class="flex items-center space-x-1 space-x-reverse">
          @php
            // Section names mapping
            $sectionNames = [
              'hero' => 'الرئيسية',
              'stats' => 'الإحصائيات',
              'reviews' => 'آراء طلابنا',
              'quran' => 'القرآن الكريم',
              'academic' => 'الأكاديمي',
              'courses' => 'الكورسات المسجلة',
              'features' => 'المميزات',
            ];

            // Anchor IDs mapping
            $sectionAnchors = [
              'hero' => 'main-content',
              'stats' => 'stats',
              'reviews' => 'testimonials',
              'quran' => 'quran',
              'academic' => 'academic',
              'courses' => 'courses',
              'features' => 'features',
            ];

            // Get sections order
            $sectionsOrder = $academy->sections_order ?? ['hero', 'stats', 'reviews', 'quran', 'academic', 'courses', 'features'];
          @endphp

          @foreach($sectionsOrder as $section)
            @php
              $sectionVisible = $academy->{$section . '_visible'} ?? true;
              $showInNav = $academy->{$section . '_show_in_nav'} ?? false;
              $sectionName = $sectionNames[$section] ?? ucfirst($section);
              $anchorId = $sectionAnchors[$section] ?? $section;
            @endphp
            @if($sectionVisible && $showInNav)
              <a href="#{{ $anchorId }}" class="flex items-center h-20 px-4 text-gray-700 hover:text-primary hover:bg-gray-50 transition-colors duration-200 focus:outline-none font-medium" aria-label="انتقل إلى {{ $sectionName }}">{{ $sectionName }}</a>
            @endif
          @endforeach
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
                @php
                  $profileRouteName = auth()->user()->isTeacher() ? 'teacher.profile' : 'student.profile';
                  $isAdminOrSuperAdmin = auth()->user()->isAdmin() || auth()->user()->isSuperAdmin();
                @endphp
                @if(!$isAdminOrSuperAdmin)
                <a href="{{ route($profileRouteName, ['subdomain' => $academy->subdomain ?? 'test-academy']) }}" class="flex items-center px-4 py-2 text-sm text-gray-700 hover:bg-gray-50 transition-colors duration-200" role="menuitem">
                  <i class="ri-user-line ml-2"></i>
                  الملف الشخصي
                </a>
                <div class="border-t border-gray-100"></div>
                @endif
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
        @php
          // Section names mapping (reuse from desktop menu)
          $mobileSectionNames = [
            'hero' => 'الرئيسية',
            'stats' => 'الإحصائيات',
            'reviews' => 'آراء طلابنا',
            'quran' => 'القرآن الكريم',
            'academic' => 'الأكاديمي',
            'courses' => 'الكورسات المسجلة',
            'features' => 'المميزات',
          ];

          // Anchor IDs mapping
          $mobileSectionAnchors = [
            'hero' => 'main-content',
            'stats' => 'stats',
            'reviews' => 'testimonials',
            'quran' => 'quran',
            'academic' => 'academic',
            'courses' => 'courses',
            'features' => 'features',
          ];

          // Get sections order
          $mobileSectionsOrder = $academy->sections_order ?? ['hero', 'stats', 'reviews', 'quran', 'academic', 'courses', 'features'];
        @endphp

        @foreach($mobileSectionsOrder as $section)
          @php
            $sectionVisible = $academy->{$section . '_visible'} ?? true;
            $showInNav = $academy->{$section . '_show_in_nav'} ?? false;
            $sectionName = $mobileSectionNames[$section] ?? ucfirst($section);
            $anchorId = $mobileSectionAnchors[$section] ?? $section;
          @endphp
          @if($sectionVisible && $showInNav)
            <a href="#{{ $anchorId }}" class="block px-3 py-2 text-gray-700 hover:text-primary hover:bg-gray-50 rounded-md focus:outline-none" aria-label="انتقل إلى {{ $sectionName }}">{{ $sectionName }}</a>
          @endif
        @endforeach

        @auth
          <div class="border-t border-gray-200 pt-2 mt-2">
            @php
              $profileRouteName = auth()->user()->isTeacher() ? 'teacher.profile' : 'student.profile';
              $isAdminOrSuperAdmin = auth()->user()->isAdmin() || auth()->user()->isSuperAdmin();
            @endphp
            @if(!$isAdminOrSuperAdmin)
            <a href="{{ route($profileRouteName, ['subdomain' => $academy->subdomain ?? 'test-academy']) }}" class="block px-3 py-2 text-gray-700 hover:text-primary hover:bg-gray-50 rounded-md focus:outline-none">الملف الشخصي</a>
            @endif
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

  // Smooth scroll for anchor links
  document.querySelectorAll('a[href^="#"]').forEach(anchor => {
    anchor.addEventListener('click', function(e) {
      const href = this.getAttribute('href');

      // Skip empty anchors or non-hash links
      if (!href || href === '#') return;

      // Get target element
      const targetId = href.substring(1);
      const targetElement = document.getElementById(targetId);

      // Only prevent default if target exists
      if (!targetElement) return;

      e.preventDefault();
      e.stopPropagation();

      // Close mobile menu if open
      if (mobileMenu && !mobileMenu.classList.contains('hidden')) {
        mobileMenu.classList.add('hidden');
        mobileMenuButton.setAttribute('aria-expanded', 'false');
      }

      // Get navbar height
      const navbar = document.getElementById('navigation');
      const navbarHeight = navbar ? navbar.offsetHeight : 80;

      // Calculate the target position
      const targetPosition = targetElement.getBoundingClientRect().top + window.scrollY - navbarHeight;

      // Scroll to position
      window.scrollTo({
        top: targetPosition,
        behavior: 'smooth'
      });
    }, true); // Use capture phase
  });
});
</script>
