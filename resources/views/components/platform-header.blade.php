<nav id="platform-header" class="fixed top-0 left-0 right-0 z-50">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        <div class="flex justify-between items-center h-16 md:h-20 lg:h-24">
            <!-- Logo Section -->
            <div class="flex items-center">
                <a href="{{ route('platform.home') }}" class="block">
                    @if(isset($platformSettings) && $platformSettings->logo)
                        <img src="{{ asset('storage/' . $platformSettings->logo) }}" alt="منصة إتقان" class="h-10 md:h-12 lg:h-14 w-auto">
                    @else
                        <h1 class="text-xl md:text-2xl font-bold text-white">
                            منصة إتقان
                        </h1>
                    @endif
                </a>
            </div>

            <!-- Desktop Navigation Menu -->
            <div class="hidden lg:flex items-center justify-center flex-1">
                <div class="flex items-center gap-2">
                    <a href="{{ route('platform.home') }}"
                       class="nav-link px-4 py-2 rounded-lg text-base font-medium text-white hover:text-green-300 transition-all duration-300 {{ request()->routeIs('platform.home') ? 'nav-active' : '' }}">
                        الرئيسية
                    </a>

                    <a href="{{ route('platform.business-services') }}"
                       class="nav-link px-4 py-2 rounded-lg text-base font-medium text-white hover:text-green-300 transition-all duration-300 {{ request()->routeIs('platform.business-services') ? 'nav-active' : '' }}">
                        خدمات الأعمال
                    </a>

                    <a href="{{ route('platform.portfolio') }}"
                       class="nav-link px-4 py-2 rounded-lg text-base font-medium text-white hover:text-green-300 transition-all duration-300 {{ request()->routeIs('platform.portfolio') ? 'nav-active' : '' }}">
                        البورتفوليو
                    </a>

                    <a href="{{ route('platform.about') }}"
                       class="nav-link px-4 py-2 rounded-lg text-base font-medium text-white hover:text-green-300 transition-all duration-300 {{ request()->routeIs('platform.about') ? 'nav-active' : '' }}">
                        من نحن
                    </a>

                    <a href="{{ route('platform.contact') }}"
                       class="nav-link px-4 py-2 rounded-lg text-base font-medium text-white hover:text-green-300 transition-all duration-300 {{ request()->routeIs('platform.contact') ? 'nav-active' : '' }}">
                        اتصل بنا
                    </a>
                </div>
            </div>

            <!-- Right Side Actions -->
            <div class="flex items-center gap-4">
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
                <button id="mobile-menu-btn"
                        type="button"
                        class="lg:hidden p-2 min-h-[44px] min-w-[44px] flex items-center justify-center rounded-lg text-white hover:bg-white/10 transition-colors cursor-pointer"
                        aria-label="القائمة">
                    <i id="menu-icon" class="ri-menu-line text-2xl"></i>
                </button>
            </div>
        </div>
    </div>
</nav>

<!-- Mobile Menu Overlay -->
<div id="mobile-overlay" class="fixed inset-0 z-[100] bg-black/50 lg:hidden" style="display: none;"></div>

<!-- Mobile Menu Sidebar -->
<div id="mobile-sidebar" class="fixed top-0 bottom-0 w-[300px] max-w-[85vw] bg-white shadow-xl overflow-y-auto transition-all duration-300" style="right: -300px; z-index: 9999;">
    <!-- Mobile Menu Header -->
    <div class="flex items-center justify-between p-4 border-b">
        <h2 class="text-lg font-bold text-gray-900">القائمة</h2>
        <button id="close-menu-btn"
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

<style>
    /* Header styling with constant blur - only transition background */
    #platform-header {
        background: linear-gradient(135deg, rgba(99, 102, 241, 0.2) 0%, rgba(59, 130, 246, 0.2) 100%);
        backdrop-filter: blur(12px);
        -webkit-backdrop-filter: blur(12px);
        border-bottom: 1px solid rgba(255, 255, 255, 0.15);
        transition: background 0.3s ease, box-shadow 0.3s ease, border-color 0.3s ease;
    }

    /* Header past hero section - more opaque background */
    #platform-header.header-gradient {
        background: linear-gradient(135deg, rgba(99, 102, 241, 0.85) 0%, rgba(59, 130, 246, 0.85) 100%);
        box-shadow: 0 4px 20px rgba(0, 0, 0, 0.15);
        border-bottom: 1px solid rgba(99, 102, 241, 0.3);
    }

    /* Active page styling */
    .nav-active {
        color: #4ade80 !important;
        font-weight: 600;
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
</style>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const header = document.getElementById('platform-header');
    const mobileMenuBtn = document.getElementById('mobile-menu-btn');
    const closeMenuBtn = document.getElementById('close-menu-btn');
    const mobileOverlay = document.getElementById('mobile-overlay');
    const mobileSidebar = document.getElementById('mobile-sidebar');

    // Cache hero height for performance
    let cachedHeroHeight = null;
    let hasHeroSection = false;
    let isGradientActive = false;
    let ticking = false;

    // Hysteresis values to prevent rapid toggling
    const HYSTERESIS = 30; // pixels of dead zone

    // Get hero section
    function getHeroSection() {
        return document.querySelector('[data-hero]') ||
               document.querySelector('#hero') ||
               document.querySelector('.hero-section');
    }

    // Calculate and cache hero section height
    function calculateHeroHeight() {
        const heroSection = getHeroSection();
        hasHeroSection = !!heroSection;

        if (heroSection) {
            cachedHeroHeight = heroSection.offsetHeight;
        } else {
            cachedHeroHeight = 50;
        }
        return cachedHeroHeight;
    }

    // Update header state - called via requestAnimationFrame
    function updateHeader() {
        ticking = false;
        if (!header || !hasHeroSection) return;

        const heroHeight = cachedHeroHeight || calculateHeroHeight();
        const scrollY = window.pageYOffset || window.scrollY;
        const threshold = heroHeight - 80;

        // Apply hysteresis to prevent flickering
        if (!isGradientActive && scrollY > threshold) {
            header.classList.add('header-gradient');
            isGradientActive = true;
        } else if (isGradientActive && scrollY < (threshold - HYSTERESIS)) {
            header.classList.remove('header-gradient');
            isGradientActive = false;
        }
    }

    // Throttled scroll handler using requestAnimationFrame
    function handleScroll() {
        if (!ticking) {
            requestAnimationFrame(updateHeader);
            ticking = true;
        }
    }

    // Recalculate hero height on resize (debounced)
    let resizeTimeout;
    function handleResize() {
        clearTimeout(resizeTimeout);
        resizeTimeout = setTimeout(function() {
            calculateHeroHeight();
            updateHeader();
        }, 150);
    }

    // Initial setup
    calculateHeroHeight();

    // For pages without hero section, show gradient immediately and don't listen to scroll
    if (!hasHeroSection) {
        header.classList.add('header-gradient');
        isGradientActive = true;
    } else {
        // Listen for scroll only on pages with hero
        window.addEventListener('scroll', handleScroll, { passive: true });

        // Initial check
        updateHeader();
    }

    // Listen for resize
    window.addEventListener('resize', handleResize, { passive: true });

    // Recalculate when images load
    window.addEventListener('load', function() {
        calculateHeroHeight();
        if (hasHeroSection) {
            updateHeader();
        }
    });

    // Mobile menu functions
    function openMobileMenu() {
        mobileSidebar.style.right = '0';
        mobileOverlay.style.display = 'block';
        document.body.style.overflow = 'hidden';
    }

    function closeMobileMenu() {
        mobileSidebar.style.right = '-300px';
        mobileOverlay.style.display = 'none';
        document.body.style.overflow = '';
    }

    if (mobileMenuBtn) {
        mobileMenuBtn.addEventListener('click', function(e) {
            e.preventDefault();
            e.stopPropagation();
            openMobileMenu();
        });
    }

    if (closeMenuBtn) {
        closeMenuBtn.addEventListener('click', function(e) {
            e.preventDefault();
            closeMobileMenu();
        });
    }

    if (mobileOverlay) {
        mobileOverlay.addEventListener('click', closeMobileMenu);
    }

    document.addEventListener('keydown', function(e) {
        if (e.key === 'Escape') {
            closeMobileMenu();
        }
    });
});
</script>
