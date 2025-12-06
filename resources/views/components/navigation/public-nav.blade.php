@php
  // Determine current route for active states
  $currentRoute = request()->route()?->getName() ?? '';

  // Navigation items with their routes, colors, and section visibility mapping
  $allNavItems = [
    [
      'route' => 'quran-circles.index',
      'label' => 'حلقات القرآن',
      'activeRoutes' => ['quran-circles.index', 'quran-circles.show'],
      'color' => 'green-600',
      'hoverBg' => 'green-50',
      'visible' => $academy->quran_visible ?? true,
    ],
    [
      'route' => 'quran-teachers.index',
      'label' => 'معلمو القرآن',
      'activeRoutes' => ['quran-teachers.index', 'quran-teachers.show'],
      'color' => 'yellow-600',
      'hoverBg' => 'yellow-50',
      'visible' => $academy->quran_visible ?? true,
    ],
    [
      'route' => 'academic-teachers.index',
      'label' => 'المعلمون الأكاديميون',
      'activeRoutes' => ['academic-teachers.index', 'academic-teachers.show'],
      'color' => 'violet-600',
      'hoverBg' => 'violet-50',
      'visible' => $academy->academic_visible ?? true,
    ],
    [
      'route' => 'interactive-courses.index',
      'label' => 'الكورسات التفاعلية',
      'activeRoutes' => ['interactive-courses.index', 'interactive-courses.show'],
      'color' => 'blue-600',
      'hoverBg' => 'blue-50',
      'visible' => $academy->academic_visible ?? true,
    ],
    [
      'route' => 'courses.index',
      'label' => 'الدورات المسجلة',
      'activeRoutes' => ['courses.index', 'courses.show', 'courses.learn', 'lessons.show'],
      'color' => 'cyan-600',
      'hoverBg' => 'cyan-50',
      'visible' => $academy->courses_visible ?? true,
    ],
  ];

  // Filter navigation items based on section visibility
  $navItems = array_filter($allNavItems, fn($item) => $item['visible']);
@endphp

<nav class="bg-white shadow-lg border-b border-gray-200 fixed top-0 left-0 right-0 z-50" role="navigation" aria-label="التنقل الرئيسي">
  <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
    <div class="flex justify-between items-center h-20">
      <!-- Logo and Brand -->
      <div class="flex items-center">
        <x-academy-logo
          :academy="$academy"
          size="md"
          :showName="true"
          :href="route('academy.home', ['subdomain' => $academy->subdomain])" />
      </div>

      <!-- Desktop Navigation Links -->
      <div class="hidden md:flex items-center space-x-2 space-x-reverse">
        @foreach($navItems as $item)
          @php
            // Check if current route matches any active routes
            $isActive = false;
            foreach($item['activeRoutes'] as $activeRoute) {
              if (str_contains($activeRoute, '*')) {
                $pattern = str_replace('*', '', $activeRoute);
                if (str_starts_with($currentRoute, $pattern)) {
                  $isActive = true;
                  break;
                }
              } elseif ($currentRoute === $activeRoute) {
                $isActive = true;
                break;
              }
            }
            $itemRoute = Route::has($item['route']) ? route($item['route'], ['subdomain' => $academy->subdomain]) : '#';
          @endphp
          <a href="{{ $itemRoute }}"
             class="px-3 py-2 font-medium rounded-lg transition-all duration-200 {{ $isActive ? 'text-' . $item['color'] : 'text-gray-700' }} hover:text-{{ $item['color'] }} hover:bg-gray-100">
            {{ $item['label'] }}
          </a>
        @endforeach
      </div>

      <!-- Auth Buttons -->
      <div class="flex items-center space-x-4 space-x-reverse">
        @auth
          @php
            $dashboardRoute = match(auth()->user()->role ?? auth()->user()->user_type) {
              'student' => route('student.dashboard', ['subdomain' => $academy->subdomain]),
              'teacher', 'quran_teacher' => route('teacher.dashboard', ['subdomain' => $academy->subdomain]),
              'academic_teacher' => route('teacher.dashboard', ['subdomain' => $academy->subdomain]),
              default => route('filament.admin.pages.dashboard')
            };
          @endphp
          <a href="{{ $dashboardRoute }}"
             class="bg-primary text-white px-6 py-2 !rounded-button hover:bg-secondary transition-colors duration-200 whitespace-nowrap focus:outline-none">
            <i class="ri-dashboard-line ml-1"></i>
            لوحة التحكم
          </a>
        @else
          <a href="{{ route('login', ['subdomain' => $academy->subdomain]) }}"
             class="bg-primary text-white px-6 py-2 !rounded-button hover:bg-secondary transition-colors duration-200 whitespace-nowrap focus:outline-none"
             aria-label="تسجيل الدخول إلى حسابك">
            تسجيل الدخول
          </a>
        @endauth
      </div>

      <!-- Mobile menu button -->
      <div class="md:hidden">
        <button type="button" class="text-gray-700 hover:text-primary focus:outline-none focus:text-primary"
                onclick="toggleMobileMenu()" aria-label="فتح قائمة التنقل">
          <i class="ri-menu-line text-xl"></i>
        </button>
      </div>
    </div>
  </div>

  <!-- Mobile menu -->
  <div id="mobile-menu" class="md:hidden hidden bg-white border-t border-gray-200">
    <div class="px-2 pt-2 pb-3 space-y-1">
      @foreach($navItems as $item)
        @php
          // Check if current route matches any active routes
          $isActive = false;
          foreach($item['activeRoutes'] as $activeRoute) {
            if (str_contains($activeRoute, '*')) {
              $pattern = str_replace('*', '', $activeRoute);
              if (str_starts_with($currentRoute, $pattern)) {
                $isActive = true;
                break;
              }
            } elseif ($currentRoute === $activeRoute) {
              $isActive = true;
              break;
            }
          }
          $itemRoute = Route::has($item['route']) ? route($item['route'], ['subdomain' => $academy->subdomain]) : '#';
        @endphp
        <a href="{{ $itemRoute }}"
           class="block px-3 py-2 font-medium rounded-lg transition-all duration-200 {{ $isActive ? 'text-' . $item['color'] . ' bg-' . $item['hoverBg'] : 'text-gray-700' }} hover:text-{{ $item['color'] }} hover:bg-{{ $item['hoverBg'] }}">
          {{ $item['label'] }}
        </a>
      @endforeach

      <div class="border-t border-gray-200 pt-4 mt-4">
        @auth
          @php
            $dashboardRoute = match(auth()->user()->role ?? auth()->user()->user_type) {
              'student' => route('student.dashboard', ['subdomain' => $academy->subdomain]),
              'teacher', 'quran_teacher' => route('teacher.dashboard', ['subdomain' => $academy->subdomain]),
              'academic_teacher' => route('teacher.dashboard', ['subdomain' => $academy->subdomain]),
              default => route('filament.admin.pages.dashboard')
            };
          @endphp
          <a href="{{ $dashboardRoute }}"
             class="block mx-3 px-6 py-2 bg-primary text-white !rounded-button text-center hover:bg-secondary transition-colors duration-200 focus:outline-none">
            <i class="ri-dashboard-line ml-1"></i>
            لوحة التحكم
          </a>
        @else
          <a href="{{ route('login', ['subdomain' => $academy->subdomain]) }}"
             class="block mx-3 px-6 py-2 bg-primary text-white !rounded-button text-center hover:bg-secondary transition-colors duration-200 focus:outline-none"
             aria-label="تسجيل الدخول إلى حسابك">
            تسجيل الدخول
          </a>
        @endauth
      </div>
    </div>
  </div>
</nav>

<script>
function toggleMobileMenu() {
  const mobileMenu = document.getElementById('mobile-menu');
  mobileMenu.classList.toggle('hidden');
}
</script>
