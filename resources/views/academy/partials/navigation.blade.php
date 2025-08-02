<nav class="bg-white dark:bg-gray-800 shadow-sm sticky top-0 z-50">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        <div class="flex justify-between h-16">
            <!-- Logo and Academy Name -->
            <div class="flex items-center">
                <a href="{{ route('academy.home', ['subdomain' => $academy->subdomain]) }}" class="flex items-center space-x-3 rtl:space-x-reverse">
                    @if($academy->logo_url)
                        <img src="{{ $academy->logo_url }}" alt="{{ $academy->name }}" class="h-8 w-8 rounded-lg">
                    @else
                        <div class="h-8 w-8 academy-bg-primary rounded-lg flex items-center justify-center">
                            <span class="text-white font-bold text-sm">{{ substr($academy->name, 0, 1) }}</span>
                        </div>
                    @endif
                    <span class="text-xl font-semibold academy-primary">{{ $academy->name }}</span>
                </a>
            </div>

            <!-- Desktop Navigation Links -->
            <div class="hidden md:flex items-center space-x-8 rtl:space-x-reverse">
                <a href="{{ route('academy.home', ['subdomain' => $academy->subdomain]) }}" class="text-gray-600 hover:text-gray-900 dark:text-gray-300 dark:hover:text-white px-3 py-2 text-sm font-medium transition-colors">
                    الرئيسية
                </a>
                <a href="{{ route('courses.index', ['subdomain' => $academy->subdomain]) }}" class="text-gray-600 hover:text-gray-900 dark:text-gray-300 dark:hover:text-white px-3 py-2 text-sm font-medium transition-colors">
                    الدورات المسجلة
                </a>
                <a href="#quran-services" class="text-gray-600 hover:text-gray-900 dark:text-gray-300 dark:hover:text-white px-3 py-2 text-sm font-medium transition-colors">
                    خدمات القرآن
                </a>
                <a href="#academic-services" class="text-gray-600 hover:text-gray-900 dark:text-gray-300 dark:hover:text-white px-3 py-2 text-sm font-medium transition-colors">
                    الخدمات الأكاديمية
                </a>
                <a href="#footer" class="text-gray-600 hover:text-gray-900 dark:text-gray-300 dark:hover:text-white px-3 py-2 text-sm font-medium transition-colors">
                    تواصل معنا
                </a>
                
                <!-- Authentication Links -->
                @auth
                    <div class="relative">
                        <button type="button" class="flex items-center text-sm rounded-full text-gray-600 hover:text-gray-900 dark:text-gray-300 dark:hover:text-white focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500" id="user-menu-button" aria-expanded="false" aria-haspopup="true">
                            <span class="sr-only">فتح قائمة المستخدم</span>
                            <div class="h-8 w-8 bg-gray-300 rounded-full flex items-center justify-center">
                                <span class="text-sm font-medium">{{ substr(auth()->user()->name, 0, 1) }}</span>
                            </div>
                        </button>
                        <!-- Dropdown menu would go here -->
                    </div>
                @else
                    <a href="{{ route('login', ['subdomain' => $academy->subdomain]) }}" class="academy-bg-primary text-white px-4 py-2 rounded-lg text-sm font-medium hover:opacity-90 transition-opacity">
                        تسجيل الدخول
                    </a>
                @endauth
            </div>

            <!-- Mobile menu button -->
            <div class="md:hidden flex items-center">
                <button type="button" class="text-gray-600 hover:text-gray-900 dark:text-gray-300 dark:hover:text-white focus:outline-none focus:ring-2 focus:ring-inset focus:ring-indigo-500" aria-controls="mobile-menu" aria-expanded="false" id="mobile-menu-button">
                    <span class="sr-only">فتح القائمة الرئيسية</span>
                    <!-- Menu icon -->
                    <svg class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16" />
                    </svg>
                </button>
            </div>
        </div>
    </div>

    <!-- Mobile menu -->
    <div class="md:hidden hidden" id="mobile-menu">
        <div class="px-2 pt-2 pb-3 space-y-1 sm:px-3 bg-white dark:bg-gray-800 border-t border-gray-200 dark:border-gray-700">
            <a href="{{ route('academy.home', ['subdomain' => $academy->subdomain]) }}" class="block px-3 py-2 text-base font-medium text-gray-600 hover:text-gray-900 dark:text-gray-300 dark:hover:text-white">
                الرئيسية
            </a>
            <a href="{{ route('courses.index', ['subdomain' => $academy->subdomain]) }}" class="block px-3 py-2 text-base font-medium text-gray-600 hover:text-gray-900 dark:text-gray-300 dark:hover:text-white">
                الدورات المسجلة
            </a>
            <a href="#quran-services" class="block px-3 py-2 text-base font-medium text-gray-600 hover:text-gray-900 dark:text-gray-300 dark:hover:text-white">
                خدمات القرآن
            </a>
            <a href="#academic-services" class="block px-3 py-2 text-base font-medium text-gray-600 hover:text-gray-900 dark:text-gray-300 dark:hover:text-white">
                الخدمات الأكاديمية
            </a>
            <a href="#footer" class="block px-3 py-2 text-base font-medium text-gray-600 hover:text-gray-900 dark:text-gray-300 dark:hover:text-white">
                تواصل معنا
            </a>
            
            @auth
                <div class="border-t border-gray-200 dark:border-gray-700 pt-3">
                    <div class="px-3 py-2">
                        <div class="text-base font-medium text-gray-800 dark:text-gray-200">{{ auth()->user()->name }}</div>
                        <div class="text-sm font-medium text-gray-500 dark:text-gray-400">{{ auth()->user()->email }}</div>
                    </div>
                </div>
            @else
                <div class="border-t border-gray-200 dark:border-gray-700 pt-3">
                    <a href="{{ route('login', ['subdomain' => $academy->subdomain]) }}" class="block px-3 py-2 text-base font-medium academy-primary">
                        تسجيل الدخول
                    </a>
                </div>
            @endauth
        </div>
    </div>
</nav>

<script>
    // Mobile menu toggle
    document.getElementById('mobile-menu-button').addEventListener('click', function() {
        const mobileMenu = document.getElementById('mobile-menu');
        mobileMenu.classList.toggle('hidden');
    });
</script>