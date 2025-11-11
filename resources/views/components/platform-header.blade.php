<nav id="platform-header" class="bg-white/5 backdrop-blur-md shadow-sm border-b border-white/20 transition-all duration-500 ease-in-out fixed top-0 left-0 right-0 z-50 text-white">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 h-full">
        <div class="flex justify-between items-center h-28 transition-all duration-500 ease-in-out">
            <!-- Logo Section -->
            <div class="flex items-center h-full">
                <div class="flex-shrink-0">
                    <a href="{{ route('platform.home') }}" class="block">
                        <h1 class="text-2xl font-bold text-white hover:text-green-300 transition-colors duration-300">منصة إتقان</h1>
                    </a>
                </div>
            </div>
            
            <!-- Centered Navigation Menu -->
            <div class="hidden md:flex items-center justify-center flex-1">
                <div class="flex items-center space-x-4 space-x-reverse">
                    <a href="{{ route('platform.home') }}" 
                       class="nav-link {{ request()->routeIs('platform.home') ? 'active' : '' }} text-white px-4 py-2 rounded-md text-lg font-medium transition-all duration-300 relative group">
                        <span class="relative z-10 group-hover:text-white group-hover:drop-shadow-[0_0_8px_rgba(34,197,94,0.8)] transition-all duration-300">الرئيسية</span>
                    </a>
                    
                    <a href="{{ route('platform.business-services') }}" 
                       class="nav-link {{ request()->routeIs('platform.business-services') ? 'active' : '' }} text-white px-4 py-2 rounded-md text-lg font-medium transition-all duration-300 relative group">
                        <span class="relative z-10 group-hover:text-white group-hover:drop-shadow-[0_0_8px_rgba(34,197,94,0.8)] transition-all duration-300">خدمات الأعمال</span>
                    </a>
                    
                    <a href="{{ route('platform.portfolio') }}" 
                       class="nav-link {{ request()->routeIs('platform.portfolio') ? 'active' : '' }} text-white px-4 py-2 rounded-md text-lg font-medium transition-all duration-300 relative group">
                        <span class="relative z-10 group-hover:text-white group-hover:drop-shadow-[0_0_8px_rgba(34,197,94,0.8)] transition-all duration-300">البورتفوليو</span>
                    </a>
                    
                    <a href="{{ route('platform.about') }}" 
                       class="nav-link {{ request()->routeIs('platform.about') ? 'active' : '' }} text-white px-4 py-2 rounded-md text-lg font-medium transition-all duration-300 relative group">
                        <span class="relative z-10 group-hover:text-white group-hover:drop-shadow-[0_0_8px_rgba(34,197,94,0.8)] transition-all duration-300">من نحن</span>
                    </a>
                    
                    <a href="{{ route('platform.contact') }}" 
                       class="nav-link {{ request()->routeIs('platform.contact') ? 'active' : '' }} text-white px-4 py-2 rounded-md text-lg font-medium transition-all duration-300 relative group">
                        <span class="relative z-10 group-hover:text-white group-hover:drop-shadow-[0_0_8px_rgba(34,197,94,0.8)] transition-all duration-300">اتصل بنا</span>
                    </a>
                </div>
            </div>
            
            <!-- Right Side Actions -->
            <div class="flex items-center space-x-8 space-x-reverse h-full">
                <a href="http://itqan-academy.{{ config('app.domain') }}" 
                   class="gradient-button text-white px-6 py-3 rounded-lg text-base font-medium transition-all duration-300 transform hover:scale-105 hover:shadow-lg flex items-center space-x-3 space-x-reverse relative overflow-hidden">
                    <div class="absolute inset-0 bg-gradient-to-r from-blue-500 via-purple-500 to-indigo-500 animate-gradient-x"></div>
                    <svg class="w-4 h-4 relative z-10" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6.253v13m0-13C10.832 5.477 9.246 5 7.5 5S4.168 5.477 3 6.253v13C4.168 18.477 5.754 18 7.5 18s3.332.477 4.5 1.253m0-13C13.168 5.477 14.754 5 16.5 5c1.747 0 3.332.477 4.5 1.253v13C19.832 18.477 18.246 18 16.5 18c-1.746 0-3.332.477-4.5 1.253"></path>
                    </svg>
                    <span class="relative z-10">زيارة الأكاديمية</span>
                </a>
            </div>
        </div>
    </div>
</nav>

<style>
.nav-link {
    position: relative;
    overflow: hidden;
}

.nav-link:hover {
    transform: scale(1.05);
}

/* Active page styling */
.nav-link.active span {
    color: #16A34A !important; /* Primary green color for active page */
    font-weight: 600;
}

/* Sticky header styles with smooth transitions */
#platform-header.sticky-active {
    backdrop-filter: blur(20px);
    background-color: rgba(255, 255, 255, 0.8);
    box-shadow: 0 8px 32px rgba(0, 0, 0, 0.12);
    border-bottom: 1px solid rgba(255, 255, 255, 0.4);
    color: #1f2937;
}

#platform-header.sticky-active h1 {
    color: #1f2937 !important;
}

#platform-header.sticky-active .nav-link {
    color: #1f2937 !important;
}

#platform-header.sticky-active .nav-link span {
    color: #1f2937 !important;
}

#platform-header.sticky-active .nav-link:hover span {
    color: #16A34A !important;
    text-shadow: 0 0 4px rgba(34, 197, 94, 0.4);
}

#platform-header.sticky-active .nav-link.active span {
    color: #16A34A !important;
    font-weight: 600;
}

#platform-header.sticky-active .flex.justify-between {
    height: 5rem;
}

#platform-header.sticky-active h1 {
    transform: scale(0.9);
}

/* Gradient animation for button */
@keyframes gradient-x {
    0%, 100% {
        background-position: 0% 50%;
    }
    50% {
        background-position: 100% 50%;
    }
}

.animate-gradient-x {
    background-size: 200% 200%;
    animation: gradient-x 3s ease infinite;
}

.gradient-button {
    position: relative;
    overflow: hidden;
}

/* Mobile menu styles */
@media (max-width: 768px) {
    #platform-header .nav-link {
        display: none;
    }
}
</style>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const header = document.getElementById('platform-header');
    const headerContent = header.querySelector('.flex.justify-between');
    let lastScrollTop = 0;
    let ticking = false;
    
    function updateHeader() {
        const scrollTop = window.pageYOffset || document.documentElement.scrollTop;
        
        // Add threshold to prevent flickering
        if (scrollTop > 50) {
            if (!header.classList.contains('sticky-active')) {
                header.classList.add('sticky-active');
            }
        } else {
            if (header.classList.contains('sticky-active')) {
                header.classList.remove('sticky-active');
            }
        }
        
        lastScrollTop = scrollTop;
        ticking = false;
    }
    
    function requestTick() {
        if (!ticking) {
            requestAnimationFrame(updateHeader);
            ticking = true;
        }
    }
    
    window.addEventListener('scroll', requestTick, { passive: true });
});
</script>
