<nav id="platform-header"
     x-data="{ mobileMenuOpen: false, scrolled: false }"
     @scroll.window="scrolled = window.pageYOffset > 50"
     :class="{ 'header-scrolled': scrolled }"
     class="bg-white/5 backdrop-blur-md shadow-sm border-b border-white/20 transition-all duration-500 ease-in-out fixed top-0 left-0 right-0 z-50 text-white">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 h-full">
        <div class="flex justify-between items-center h-16 md:h-20 lg:h-28 transition-all duration-500 ease-in-out"
             :class="{ 'lg:h-20': scrolled }">
            <!-- Logo Section -->
            <div class="flex items-center h-full">
                <div class="flex-shrink-0">
                    <a href="{{ route('platform.home') }}" class="block">
                        <h1 class="text-xl md:text-2xl font-bold transition-all duration-300"
                            :class="scrolled ? 'text-gray-800' : 'text-white'">
                            منصة إتقان
                        </h1>
                    </a>
                </div>
            </div>

            <!-- Desktop Navigation Menu -->
            <div class="hidden lg:flex items-center justify-center flex-1">
                <div class="flex items-center gap-2">
                    <a href="{{ route('platform.home') }}"
                       class="nav-link px-4 py-2 rounded-lg text-base font-medium transition-all duration-300 {{ request()->routeIs('platform.home') ? 'nav-active' : '' }}"
                       :class="scrolled ? 'text-gray-700 hover:text-green-600 hover:bg-gray-100' : 'text-white hover:text-green-300'">
                        الرئيسية
                    </a>

                    <a href="{{ route('platform.business-services') }}"
                       class="nav-link px-4 py-2 rounded-lg text-base font-medium transition-all duration-300 {{ request()->routeIs('platform.business-services') ? 'nav-active' : '' }}"
                       :class="scrolled ? 'text-gray-700 hover:text-green-600 hover:bg-gray-100' : 'text-white hover:text-green-300'">
                        خدمات الأعمال
                    </a>

                    <a href="{{ route('platform.portfolio') }}"
                       class="nav-link px-4 py-2 rounded-lg text-base font-medium transition-all duration-300 {{ request()->routeIs('platform.portfolio') ? 'nav-active' : '' }}"
                       :class="scrolled ? 'text-gray-700 hover:text-green-600 hover:bg-gray-100' : 'text-white hover:text-green-300'">
                        البورتفوليو
                    </a>

                    <a href="{{ route('platform.about') }}"
                       class="nav-link px-4 py-2 rounded-lg text-base font-medium transition-all duration-300 {{ request()->routeIs('platform.about') ? 'nav-active' : '' }}"
                       :class="scrolled ? 'text-gray-700 hover:text-green-600 hover:bg-gray-100' : 'text-white hover:text-green-300'">
                        من نحن
                    </a>

                    <a href="{{ route('platform.contact') }}"
                       class="nav-link px-4 py-2 rounded-lg text-base font-medium transition-all duration-300 {{ request()->routeIs('platform.contact') ? 'nav-active' : '' }}"
                       :class="scrolled ? 'text-gray-700 hover:text-green-600 hover:bg-gray-100' : 'text-white hover:text-green-300'">
                        اتصل بنا
                    </a>
                </div>
            </div>

            <!-- Right Side Actions -->
            <div class="flex items-center gap-4 h-full">
                <!-- Academy Button (Desktop) -->
                <a href="http://itqan-academy.{{ config('app.domain') }}"
                   class="hidden md:flex gradient-button text-white px-4 lg:px-6 py-2 lg:py-3 rounded-lg text-sm lg:text-base font-medium transition-all duration-300 transform hover:scale-105 hover:shadow-lg items-center gap-2 relative overflow-hidden">
                    <div class="absolute inset-0 bg-gradient-to-r from-blue-500 via-purple-500 to-indigo-500 animate-gradient-x"></div>
                    <svg class="w-4 h-4 relative z-10" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6.253v13m0-13C10.832 5.477 9.246 5 7.5 5S4.168 5.477 3 6.253v13C4.168 18.477 5.754 18 7.5 18s3.332.477 4.5 1.253m0-13C13.168 5.477 14.754 5 16.5 5c1.747 0 3.332.477 4.5 1.253v13C19.832 18.477 18.246 18 16.5 18c-1.746 0-3.332.477-4.5 1.253"></path>
                    </svg>
                    <span class="relative z-10 hidden lg:inline">زيارة الأكاديمية</span>
                    <span class="relative z-10 lg:hidden">الأكاديمية</span>
                </a>

                <!-- Mobile Menu Button -->
                <button @click="mobileMenuOpen = !mobileMenuOpen"
                        class="lg:hidden p-2 min-h-[44px] min-w-[44px] flex items-center justify-center rounded-lg transition-colors"
                        :class="scrolled ? 'text-gray-700 hover:bg-gray-100' : 'text-white hover:bg-white/10'"
                        aria-label="القائمة">
                    <i x-show="!mobileMenuOpen" class="ri-menu-line text-2xl"></i>
                    <i x-show="mobileMenuOpen" x-cloak class="ri-close-line text-2xl"></i>
                </button>
            </div>
        </div>
    </div>

    <!-- Mobile Menu Drawer -->
    <div x-show="mobileMenuOpen"
         x-cloak
         x-transition:enter="transition ease-out duration-300"
         x-transition:enter-start="opacity-0"
         x-transition:enter-end="opacity-100"
         x-transition:leave="transition ease-in duration-200"
         x-transition:leave-start="opacity-100"
         x-transition:leave-end="opacity-0"
         class="lg:hidden fixed inset-0 z-40 bg-black/50"
         @click="mobileMenuOpen = false">
    </div>

    <div x-show="mobileMenuOpen"
         x-cloak
         x-transition:enter="transition ease-out duration-300"
         x-transition:enter-start="translate-x-full"
         x-transition:enter-end="translate-x-0"
         x-transition:leave="transition ease-in duration-200"
         x-transition:leave-start="translate-x-0"
         x-transition:leave-end="translate-x-full"
         class="lg:hidden fixed top-0 right-0 bottom-0 w-[300px] max-w-[85vw] bg-white shadow-xl z-50 overflow-y-auto"
         @click.stop>

        <!-- Mobile Menu Header -->
        <div class="flex items-center justify-between p-4 border-b">
            <h2 class="text-lg font-bold text-gray-900">القائمة</h2>
            <button @click="mobileMenuOpen = false"
                    class="p-2 min-h-[44px] min-w-[44px] flex items-center justify-center rounded-lg hover:bg-gray-100 text-gray-600">
                <i class="ri-close-line text-2xl"></i>
            </button>
        </div>

        <!-- Mobile Navigation Links -->
        <nav class="p-4">
            <div class="space-y-1">
                <a href="{{ route('platform.home') }}"
                   class="flex items-center gap-3 px-4 py-3 min-h-[48px] rounded-lg transition-colors {{ request()->routeIs('platform.home') ? 'bg-green-50 text-green-700' : 'text-gray-700 hover:bg-gray-100' }}">
                    <i class="ri-home-line text-xl"></i>
                    <span class="font-medium">الرئيسية</span>
                </a>

                <a href="{{ route('platform.business-services') }}"
                   class="flex items-center gap-3 px-4 py-3 min-h-[48px] rounded-lg transition-colors {{ request()->routeIs('platform.business-services') ? 'bg-green-50 text-green-700' : 'text-gray-700 hover:bg-gray-100' }}">
                    <i class="ri-briefcase-line text-xl"></i>
                    <span class="font-medium">خدمات الأعمال</span>
                </a>

                <a href="{{ route('platform.portfolio') }}"
                   class="flex items-center gap-3 px-4 py-3 min-h-[48px] rounded-lg transition-colors {{ request()->routeIs('platform.portfolio') ? 'bg-green-50 text-green-700' : 'text-gray-700 hover:bg-gray-100' }}">
                    <i class="ri-gallery-line text-xl"></i>
                    <span class="font-medium">البورتفوليو</span>
                </a>

                <a href="{{ route('platform.about') }}"
                   class="flex items-center gap-3 px-4 py-3 min-h-[48px] rounded-lg transition-colors {{ request()->routeIs('platform.about') ? 'bg-green-50 text-green-700' : 'text-gray-700 hover:bg-gray-100' }}">
                    <i class="ri-information-line text-xl"></i>
                    <span class="font-medium">من نحن</span>
                </a>

                <a href="{{ route('platform.contact') }}"
                   class="flex items-center gap-3 px-4 py-3 min-h-[48px] rounded-lg transition-colors {{ request()->routeIs('platform.contact') ? 'bg-green-50 text-green-700' : 'text-gray-700 hover:bg-gray-100' }}">
                    <i class="ri-mail-line text-xl"></i>
                    <span class="font-medium">اتصل بنا</span>
                </a>
            </div>
        </nav>

        <!-- Mobile Menu Footer -->
        <div class="p-4 border-t mt-auto">
            <a href="http://itqan-academy.{{ config('app.domain') }}"
               class="flex items-center justify-center gap-2 w-full px-4 py-3 min-h-[48px] bg-gradient-to-r from-blue-500 via-purple-500 to-indigo-500 text-white rounded-lg font-medium transition-transform hover:scale-[1.02]">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6.253v13m0-13C10.832 5.477 9.246 5 7.5 5S4.168 5.477 3 6.253v13C4.168 18.477 5.754 18 7.5 18s3.332.477 4.5 1.253m0-13C13.168 5.477 14.754 5 16.5 5c1.747 0 3.332.477 4.5 1.253v13C19.832 18.477 18.246 18 16.5 18c-1.746 0-3.332.477-4.5 1.253"></path>
                </svg>
                <span>زيارة الأكاديمية</span>
            </a>
        </div>
    </div>
</nav>

<style>
    /* Active page styling */
    .nav-active {
        color: #16A34A !important;
        font-weight: 600;
    }

    /* Header scrolled state */
    .header-scrolled {
        backdrop-filter: blur(20px);
        background-color: rgba(255, 255, 255, 0.95) !important;
        box-shadow: 0 4px 20px rgba(0, 0, 0, 0.08);
        border-bottom-color: rgba(229, 231, 235, 1) !important;
    }

    /* Gradient animation for button */
    @keyframes gradient-x {
        0%, 100% { background-position: 0% 50%; }
        50% { background-position: 100% 50%; }
    }

    .animate-gradient-x {
        background-size: 200% 200%;
        animation: gradient-x 3s ease infinite;
    }

    .gradient-button {
        position: relative;
        overflow: hidden;
    }

    /* Hide x-cloak elements until Alpine.js loads */
    [x-cloak] {
        display: none !important;
    }
</style>
