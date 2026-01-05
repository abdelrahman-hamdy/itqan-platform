@php
  // Determine current route for active states
  $currentRoute = request()->route()?->getName() ?? '';

  // Navigation items with their routes, colors, and section visibility mapping
  $allNavItems = [
    [
      'route' => 'quran-circles.index',
      'label' => __('components.navigation.public.quran_circles'),
      'activeRoutes' => ['quran-circles.index', 'quran-circles.show'],
      'color' => 'green-600',
      'hoverBg' => 'green-50',
      'visible' => $academy->quran_visible ?? true,
    ],
    [
      'route' => 'quran-teachers.index',
      'label' => __('components.navigation.public.quran_teachers'),
      'activeRoutes' => ['quran-teachers.index', 'quran-teachers.show'],
      'color' => 'yellow-600',
      'hoverBg' => 'yellow-50',
      'visible' => $academy->quran_visible ?? true,
    ],
    [
      'route' => 'academic-teachers.index',
      'label' => __('components.navigation.public.academic_teachers'),
      'activeRoutes' => ['academic-teachers.index', 'academic-teachers.show'],
      'color' => 'violet-600',
      'hoverBg' => 'violet-50',
      'visible' => $academy->academic_visible ?? true,
    ],
    [
      'route' => 'interactive-courses.index',
      'label' => __('components.navigation.public.interactive_courses'),
      'activeRoutes' => ['interactive-courses.index', 'interactive-courses.show'],
      'color' => 'blue-600',
      'hoverBg' => 'blue-50',
      'visible' => $academy->academic_visible ?? true,
    ],
    [
      'route' => 'courses.index',
      'label' => __('components.navigation.public.recorded_courses'),
      'activeRoutes' => ['courses.index', 'courses.show', 'courses.learn', 'lessons.show'],
      'color' => 'cyan-600',
      'hoverBg' => 'cyan-50',
      'visible' => $academy->courses_visible ?? true,
    ],
  ];

  // Filter navigation items based on section visibility
  $navItems = array_filter($allNavItems, fn($item) => $item['visible']);
@endphp

<nav class="bg-white shadow-lg border-b border-gray-200 fixed top-0 left-0 right-0 z-50" role="navigation" aria-label="{{ __('components.navigation.public.main_navigation') }}">
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
      <div class="hidden md:flex items-center gap-2">
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

      <!-- Auth Buttons and Language Switcher -->
      <div class="flex items-center gap-4">
        <!-- Language Switcher (Desktop only - mobile version in dropdown menu) -->
        <div class="hidden md:block">
          <x-ui.language-switcher :dropdown="false" :showLabel="false" size="sm" />
        </div>

        <!-- User Widget (Desktop) - Shared component for consistency with main landing page -->
        <x-navigation.user-widget :academy="$academy" height="h-20" />
      </div>

      <!-- Mobile menu button -->
      <div class="md:hidden">
        <button type="button" class="text-gray-700 hover:text-primary focus:outline-none focus:text-primary"
                onclick="toggleMobileMenu()" aria-label="{{ __('components.navigation.public.toggle_mobile_menu') }}">
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

      <!-- Mobile Language Switcher -->
      <div class="px-3 py-2">
        <x-ui.language-switcher :dropdown="false" :showLabel="true" size="md" class="w-full justify-center" />
      </div>

      <!-- User Actions (Mobile) -->
      <div class="border-t border-gray-200 pt-2 mt-2">
        @auth
          @php
            $mobileUser = auth()->user();
            $mobileProfileRouteName = $mobileUser->isTeacher() ? 'teacher.profile' : 'student.profile';
            $mobileIsAdminOrSuperAdminOrSupervisor = $mobileUser->isAdmin() || $mobileUser->isSuperAdmin() || $mobileUser->isSupervisor();

            // Determine dashboard route for admin roles
            $mobileDashboardRoute = match($mobileUser->user_type) {
              'supervisor' => route('filament.supervisor.pages.dashboard'),
              'admin' => route('filament.admin.pages.dashboard'),
              'super_admin' => route('filament.admin.pages.dashboard'),
              default => route('filament.admin.pages.dashboard')
            };
          @endphp

          {{-- Chat Link for Supervisors (Mobile) --}}
          @if($mobileUser->user_type === 'supervisor')
          <a href="{{ route('chats', ['subdomain' => $academy->subdomain]) }}" onclick="toggleMobileMenu()" class="flex items-center px-3 py-2.5 text-gray-700 hover:bg-gray-50 rounded-md focus:outline-none font-medium" aria-label="{{ __('chat.messages') }}">
            <i class="ri-message-3-line ms-2"></i>
            {{ __('chat.messages') }}
            @php $mobileUnreadCount = $mobileUser->unreadMessagesCount(); @endphp
            @if($mobileUnreadCount > 0)
            <span class="ms-auto inline-flex items-center justify-center min-w-[20px] h-5 text-xs font-bold text-white bg-red-500 rounded-full px-1">
              {{ $mobileUnreadCount > 99 ? '99+' : $mobileUnreadCount }}
            </span>
            @endif
          </a>
          @endif

          @if($mobileIsAdminOrSuperAdminOrSupervisor)
            {{-- Dashboard Link (opens in new tab) for Admin/SuperAdmin/Supervisor --}}
            <a href="{{ $mobileDashboardRoute }}" target="_blank" onclick="toggleMobileMenu()" class="flex items-center px-3 py-2.5 text-gray-700 hover:bg-gray-50 rounded-md focus:outline-none font-medium">
              <i class="ri-dashboard-line ms-2"></i>
              {{ __('components.navigation.public.dashboard') }}
              <i class="ri-external-link-line text-gray-400 ms-auto text-xs"></i>
            </a>
          @else
            {{-- Profile Link for Students/Teachers --}}
            <a href="{{ route($mobileProfileRouteName, ['subdomain' => $academy->subdomain ?? 'test-academy']) }}" onclick="toggleMobileMenu()" class="flex items-center px-3 py-2.5 text-gray-700 hover:bg-gray-50 rounded-md focus:outline-none font-medium" aria-label="{{ __('academy.user.profile') }}">
              <i class="ri-user-line ms-2"></i>
              {{ __('academy.user.profile') }}
            </a>
          @endif

          {{-- Logout --}}
          <form method="POST" action="{{ route('logout', ['subdomain' => $academy->subdomain]) }}" class="block">
            @csrf
            <button type="submit" onclick="toggleMobileMenu()" class="flex items-center w-full px-3 py-2.5 text-red-600 hover:bg-red-50 rounded-md focus:outline-none font-medium" aria-label="{{ __('academy.user.logout') }}">
              <i class="ri-logout-box-line ms-2"></i>
              {{ __('academy.user.logout') }}
            </button>
          </form>
        @else
          <a href="{{ route('login', ['subdomain' => $academy->subdomain]) }}" onclick="toggleMobileMenu()" class="flex items-center px-3 py-2.5 text-primary hover:bg-primary/10 rounded-md focus:outline-none font-medium" aria-label="{{ __('academy.user.login') }}">
            <i class="ri-login-box-line ms-2"></i>
            {{ __('academy.user.login') }}
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
