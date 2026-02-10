<!-- Topbar Navigation Wrapper -->
<div x-data="{ mobileMenuOpen: false }" class="sticky top-0 z-50">
<nav id="navigation" class="bg-white shadow-lg" role="navigation" aria-label="{{ __('academy.nav.main_navigation') }}">
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
        <div class="flex items-center gap-1">
          @php
            // Section names mapping (using translations)
            $sectionNames = [
              'hero' => __('academy.nav.sections.hero'),
              'stats' => __('academy.nav.sections.stats'),
              'reviews' => __('academy.nav.sections.reviews'),
              'quran' => __('academy.nav.sections.quran'),
              'academic' => __('academy.nav.sections.academic'),
              'courses' => __('academy.nav.sections.courses'),
              'features' => __('academy.nav.sections.features'),
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
              <a href="#{{ $anchorId }}" class="flex items-center h-20 px-4 text-gray-700 hover:text-primary hover:bg-gray-50 transition-colors duration-200 focus:outline-none font-medium" aria-label="{{ __('academy.nav.go_to', ['section' => $sectionName]) }}">{{ $sectionName }}</a>
            @endif
          @endforeach
        </div>
      </div>

      <!-- Right Side Actions -->
      <div class="flex items-center gap-4">
        <!-- Language Switcher (Desktop) -->
        <div class="hidden md:block">
          <x-ui.language-switcher :dropdown="false" :showLabel="false" size="sm" />
        </div>

        <!-- User Widget (Desktop) - Shared component for consistency -->
        <x-navigation.user-widget :academy="$academy" height="h-20" />

        <!-- Mobile Menu Button (Mobile Only) -->
        <button @click="mobileMenuOpen = !mobileMenuOpen" class="md:hidden focus:ring-custom p-2" aria-label="{{ __('academy.nav.open_menu') }}" :aria-expanded="mobileMenuOpen">
          <div class="w-6 h-6 flex items-center justify-center">
            <i :class="mobileMenuOpen ? 'ri-close-line' : 'ri-menu-line'" class="text-xl"></i>
          </div>
        </button>
      </div>
    </div>

  </div>
</nav>

<!-- Mobile Navigation Menu - Fixed overlay -->
<div x-show="mobileMenuOpen"
     x-transition:enter="transition ease-out duration-200"
     x-transition:enter-start="opacity-0"
     x-transition:enter-end="opacity-100"
     x-transition:leave="transition ease-in duration-150"
     x-transition:leave-start="opacity-100"
     x-transition:leave-end="opacity-0"
     @click="mobileMenuOpen = false"
     x-cloak
     class="md:hidden fixed inset-0 top-20 z-40 bg-black/20" id="mobile-menu-overlay">
</div>
<div x-show="mobileMenuOpen"
     x-transition:enter="transition ease-out duration-200"
     x-transition:enter-start="opacity-0 -translate-y-2"
     x-transition:enter-end="opacity-100 translate-y-0"
     x-transition:leave="transition ease-in duration-150"
     x-transition:leave-start="opacity-100 translate-y-0"
     x-transition:leave-end="opacity-0 -translate-y-2"
     x-cloak
     class="md:hidden fixed left-0 right-0 top-20 z-50 bg-white shadow-lg border-t border-gray-200" id="mobile-menu">
  <div class="px-4 py-3 space-y-1 max-h-[70vh] overflow-y-auto">
    @php
      // Section names mapping (using translations, same as desktop menu)
      $mobileSectionNames = [
        'hero' => __('academy.nav.sections.hero'),
        'stats' => __('academy.nav.sections.stats'),
        'reviews' => __('academy.nav.sections.reviews'),
        'quran' => __('academy.nav.sections.quran'),
        'academic' => __('academy.nav.sections.academic'),
        'courses' => __('academy.nav.sections.courses'),
        'features' => __('academy.nav.sections.features'),
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
        <a href="#{{ $anchorId }}" @click="mobileMenuOpen = false" class="block px-3 py-2.5 text-gray-700 hover:text-primary hover:bg-gray-50 rounded-md focus:outline-none font-medium" aria-label="{{ __('academy.nav.go_to', ['section' => $sectionName]) }}">{{ $sectionName }}</a>
      @endif
    @endforeach

    <!-- Language Switcher (Mobile) -->
    <div class="border-t border-gray-200 pt-2 mt-2">
      <x-ui.language-switcher :dropdown="false" :showLabel="true" size="md" class="w-full justify-center" />
    </div>

    <!-- User Actions -->
    <div class="border-t border-gray-200 pt-2 mt-2">
      @auth
        @php
          $mobileProfileRouteName = auth()->user()->isTeacher() ? 'teacher.profile' : 'student.profile';
          $mobileIsAdminOrSuperAdminOrSupervisor = auth()->user()->isAdmin() || auth()->user()->isSuperAdmin() || auth()->user()->isSupervisor();
        @endphp
        {{-- Sessions Monitoring Link for Supervisors & SuperAdmins (Mobile) --}}
        @if(auth()->user()->isSupervisor() || auth()->user()->isSuperAdmin())
        @php
          $mobileSessionsUrl = auth()->user()->isSuperAdmin()
              ? url('/admin/live-sessions')
              : url('/supervisor-panel/monitored-all-sessions');
        @endphp
        <a href="{{ $mobileSessionsUrl }}" target="_blank" @click="mobileMenuOpen = false" class="flex items-center px-3 py-2.5 text-gray-700 hover:bg-gray-50 rounded-md focus:outline-none font-medium" aria-label="{{ __('supervisor.observation.observe_session') }}">
          <i class="ri-eye-line ms-2"></i>
          {{ __('supervisor.observation.observe_session') }}
          <i class="ri-external-link-line text-gray-400 ms-auto text-xs"></i>
        </a>
        @endif

        {{-- Chat Link for Supervisors (Mobile) --}}
        @if(auth()->user()->user_type === 'supervisor')
        <a href="{{ route('chats', ['subdomain' => $academy->subdomain]) }}" @click="mobileMenuOpen = false" class="flex items-center px-3 py-2.5 text-gray-700 hover:bg-gray-50 rounded-md focus:outline-none font-medium" aria-label="{{ __('chat.messages') }}">
          <i class="ri-message-3-line ms-2"></i>
          {{ __('chat.messages') }}
          @php $mobileUnreadCount = auth()->user()->unreadMessagesCount(); @endphp
          @if($mobileUnreadCount > 0)
          <span class="ms-auto inline-flex items-center justify-center min-w-[20px] h-5 text-xs font-bold text-white bg-red-500 rounded-full px-1">
            {{ $mobileUnreadCount > 99 ? '99+' : $mobileUnreadCount }}
          </span>
          @endif
        </a>
        @endif
        @if(!$mobileIsAdminOrSuperAdminOrSupervisor)
        <a href="{{ route($mobileProfileRouteName, ['subdomain' => $academy->subdomain ?? 'test-academy']) }}" @click="mobileMenuOpen = false" class="flex items-center px-3 py-2.5 text-gray-700 hover:bg-gray-50 rounded-md focus:outline-none font-medium" aria-label="{{ __('academy.user.profile') }}">
          <i class="ri-user-line ms-2"></i>
          {{ __('academy.user.profile') }}
        </a>
        @endif
        <form method="POST" action="{{ route('logout', ['subdomain' => $academy->subdomain ?? 'test-academy']) }}" class="block">
          @csrf
          <button type="submit" @click="mobileMenuOpen = false" class="flex items-center w-full px-3 py-2.5 text-red-600 hover:bg-red-50 rounded-md focus:outline-none font-medium" aria-label="{{ __('academy.user.logout') }}">
            <i class="ri-logout-box-line ms-2"></i>
            {{ __('academy.user.logout') }}
          </button>
        </form>
      @else
        <a href="{{ route('login', ['subdomain' => $academy->subdomain ?? 'test-academy']) }}" @click="mobileMenuOpen = false" class="flex items-center px-3 py-2.5 text-primary hover:bg-primary/10 rounded-md focus:outline-none font-medium" aria-label="{{ __('academy.user.login') }}">
          <i class="ri-login-box-line ms-2"></i>
          {{ __('academy.user.login') }}
        </a>
      @endauth
    </div>
  </div>
</div>
</div>

<!-- Alpine.js is bundled with Livewire 3 (inject_assets: true in config/livewire.php) -->
<style>
  [x-cloak] { display: none !important; }
</style>

<!-- Smooth scroll script -->
<script>
document.addEventListener('DOMContentLoaded', function() {
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
