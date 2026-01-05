<nav class="bg-white shadow-sm border-b border-gray-200 sticky top-0 z-50">
  <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
    <div class="flex justify-between items-center h-20">
      <!-- Logo and Brand -->
      <div class="flex items-center gap-8">
        <div class="flex items-center">
          @if($academy->logo ?? null)
            <img src="{{ $academy->logo_url }}" alt="{{ $academy->name }}" class="w-8 h-8 ms-2">
          @else
            <div class="w-8 h-8 flex items-center justify-center">
              <svg class="w-8 h-8 text-primary-500" fill="currentColor" viewBox="0 0 24 24">
                <path d="M12 3L1 9l11 6 9-4.91V17h2V9M5 13.18v4L12 21l7-3.82v-4L12 17l-7-3.82Z"/>
              </svg>
            </div>
          @endif
          <span class="mr-2 text-xl font-bold text-primary-500 font-arabic">{{ $academy->name }}</span>
        </div>
        
        <!-- Desktop Navigation Links -->
        <div class="hidden md:flex items-center gap-6">
          @if($academy->quran_enabled ?? true)
            <a href="#quran" class="text-gray-700 hover:text-primary-600 transition-colors duration-200 font-arabic">
              {{ __('academy.nav.sections.quran') }}
            </a>
          @endif
          @if($academy->academic_enabled ?? true)
            <a href="#academic" class="text-gray-700 hover:text-primary-600 transition-colors duration-200 font-arabic">
              {{ __('academy.nav.sections.academic') }}
            </a>
          @endif
          @if($academy->recorded_courses_enabled ?? true)
            <a href="#courses" class="text-gray-700 hover:text-primary-600 transition-colors duration-200 font-arabic">
              {{ __('academy.nav.sections.courses') }}
            </a>
          @endif
          <a href="#about" class="text-gray-700 hover:text-primary-600 transition-colors duration-200 font-arabic">
            {{ __('academy.footer.about') }}
          </a>
          <a href="#contact" class="text-gray-700 hover:text-primary-600 transition-colors duration-200 font-arabic">
            {{ __('academy.footer.contact') }}
          </a>
        </div>
      </div>

      <!-- Right Side Actions -->
      <div class="flex items-center gap-4">
        <!-- Language Selector -->
        <div class="relative">
          <button class="flex items-center gap-2 text-gray-700 hover:text-primary-600 transition-colors duration-200">
            <span class="font-arabic">{{ app()->getLocale() === 'ar' ? 'العربية' : 'English' }}</span>
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"></path>
            </svg>
          </button>
        </div>

        <!-- Login Button -->
        <a href="{{ route('login', ['subdomain' => $academy->subdomain]) }}"
           class="inline-flex items-center px-4 py-2 bg-primary-500 hover:bg-primary-600 text-white font-medium rounded-md transition-colors duration-200 focus:outline-none focus:ring-2 focus:ring-primary-500 focus:ring-offset-2 font-arabic">
          {{ __('academy.user.login') }}
        </a>

        <!-- Mobile Menu Button -->
        <button class="md:hidden p-2 rounded-md text-gray-400 hover:text-gray-500 hover:bg-gray-100 focus:outline-none focus:ring-2 focus:ring-primary-500 focus:ring-offset-2"
                id="mobile-menu-button"
                aria-expanded="false"
                aria-controls="mobile-menu">
          <span class="sr-only">{{ __('academy.nav.open_menu') }}</span>
          <svg class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor" aria-hidden="true">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16" />
          </svg>
        </button>
      </div>
    </div>

    <!-- Mobile Menu -->
    <div class="md:hidden hidden" id="mobile-menu">
      <div class="px-2 pt-2 pb-3 space-y-1 sm:px-3 bg-white border-t border-gray-200">
        @if($academy->quran_enabled ?? true)
          <a href="#quran" class="block px-3 py-2 text-gray-700 hover:text-primary-600 hover:bg-gray-50 rounded-md font-arabic">
            {{ __('academy.nav.sections.quran') }}
          </a>
        @endif
        @if($academy->academic_enabled ?? true)
          <a href="#academic" class="block px-3 py-2 text-gray-700 hover:text-primary-600 hover:bg-gray-50 rounded-md font-arabic">
            {{ __('academy.nav.sections.academic') }}
          </a>
        @endif
        @if($academy->recorded_courses_enabled ?? true)
          <a href="#courses" class="block px-3 py-2 text-gray-700 hover:text-primary-600 hover:bg-gray-50 rounded-md font-arabic">
            {{ __('academy.nav.sections.courses') }}
          </a>
        @endif
        <a href="#about" class="block px-3 py-2 text-gray-700 hover:text-primary-600 hover:bg-gray-50 rounded-md font-arabic">
          {{ __('academy.footer.about') }}
        </a>
        <a href="#contact" class="block px-3 py-2 text-gray-700 hover:text-primary-600 hover:bg-gray-50 rounded-md font-arabic">
          {{ __('academy.footer.contact') }}
        </a>
      </div>
    </div>
  </div>
</nav>

<script>
document.addEventListener("DOMContentLoaded", function () {
  const mobileMenuButton = document.getElementById('mobile-menu-button');
  const mobileMenu = document.getElementById('mobile-menu');
  
  if (mobileMenuButton && mobileMenu) {
    mobileMenuButton.addEventListener('click', function() {
      const expanded = this.getAttribute('aria-expanded') === 'true';
      this.setAttribute('aria-expanded', !expanded);
      mobileMenu.classList.toggle('hidden');
    });

    // Close mobile menu when clicking on links
    const mobileLinks = mobileMenu.querySelectorAll('a');
    mobileLinks.forEach(link => {
      link.addEventListener('click', () => {
        mobileMenu.classList.add('hidden');
        mobileMenuButton.setAttribute('aria-expanded', 'false');
      });
    });
  }
});
</script>