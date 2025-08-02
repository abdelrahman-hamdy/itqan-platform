<!-- Hero Section -->
<section class="relative bg-gradient-to-br from-blue-50 to-indigo-100 dark:from-gray-800 dark:to-gray-900 overflow-hidden">
    <!-- Background Pattern -->
    <div class="absolute inset-0 opacity-10">
        <svg class="w-full h-full" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 100 100">
            <defs>
                <pattern id="hero-pattern" x="0" y="0" width="20" height="20" patternUnits="userSpaceOnUse">
                    <circle cx="10" cy="10" r="1" fill="currentColor"/>
                </pattern>
            </defs>
            <rect width="100%" height="100%" fill="url(#hero-pattern)"/>
        </svg>
    </div>

    <div class="relative max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-20 lg:py-32">
        <div class="grid grid-cols-1 lg:grid-cols-2 gap-12 items-center">
            <!-- Content -->
            <div class="text-center lg:text-start animate-on-scroll">
                <!-- Academy Logo (if available) -->
                @if($academy->logo_url)
                    <div class="mb-8 flex justify-center lg:justify-start">
                        <img src="{{ $academy->logo_url }}" alt="{{ $academy->name }}" class="h-20 w-20 rounded-xl shadow-lg academy-border-primary border-2">
                    </div>
                @endif

                <!-- Main Heading -->
                <h1 class="text-4xl md:text-5xl lg:text-6xl font-bold text-gray-900 dark:text-white mb-6 leading-tight">
                    مرحباً بكم في
                    <span class="academy-primary block mt-2">{{ $academy->name }}</span>
                </h1>

                <!-- Academy Description -->
                @if($academy->description)
                    <p class="text-xl text-gray-600 dark:text-gray-300 mb-8 leading-relaxed max-w-2xl mx-auto lg:mx-0">
                        {{ $academy->description }}
                    </p>
                @endif

                <!-- Call-to-Action Buttons -->
                <div class="flex flex-col sm:flex-row gap-4 justify-center lg:justify-start">
                    <a href="#services" class="academy-bg-primary text-white px-8 py-3 rounded-lg font-medium hover:opacity-90 transition-all duration-300 hover:transform hover:scale-105 text-center">
                        استكشف خدماتنا
                    </a>
                    <a href="{{ route('courses.index', ['subdomain' => $academy->subdomain]) }}" class="border-2 academy-border-primary academy-primary px-8 py-3 rounded-lg font-medium hover:academy-bg-primary hover:text-white transition-all duration-300 text-center">
                        تصفح الدورات
                    </a>
                </div>

                <!-- Academy Features -->
                <div class="mt-12 grid grid-cols-3 gap-4">
                    <div class="text-center">
                        <div class="academy-primary text-2xl font-bold">{{ $stats['total_students'] }}+</div>
                        <div class="text-sm text-gray-600 dark:text-gray-300">طالب وطالبة</div>
                    </div>
                    <div class="text-center">
                        <div class="academy-primary text-2xl font-bold">{{ $stats['total_teachers'] }}+</div>
                        <div class="text-sm text-gray-600 dark:text-gray-300">معلم ومعلمة</div>
                    </div>
                    <div class="text-center">
                        <div class="academy-primary text-2xl font-bold">{{ $stats['active_courses'] }}+</div>
                        <div class="text-sm text-gray-600 dark:text-gray-300">دورة تعليمية</div>
                    </div>
                </div>
            </div>

            <!-- Hero Image/Visual -->
            <div class="relative animate-on-scroll order-first lg:order-last">
                <div class="relative">
                    <!-- Main Visual Container -->
                    <div class="relative h-96 lg:h-[500px] bg-gradient-to-br academy-bg-primary rounded-2xl overflow-hidden shadow-2xl">
                        <!-- Islamic Pattern Overlay -->
                        <div class="absolute inset-0 opacity-20">
                            <svg class="w-full h-full" viewBox="0 0 400 400" xmlns="http://www.w3.org/2000/svg">
                                <defs>
                                    <pattern id="islamic-pattern" x="0" y="0" width="40" height="40" patternUnits="userSpaceOnUse">
                                        <g fill="white">
                                            <circle cx="20" cy="20" r="2"/>
                                            <path d="M20 10 L30 20 L20 30 L10 20 Z" fill="none" stroke="white" stroke-width="1"/>
                                        </g>
                                    </pattern>
                                </defs>
                                <rect width="100%" height="100%" fill="url(#islamic-pattern)"/>
                            </svg>
                        </div>

                        <!-- Content Overlay -->
                        <div class="absolute inset-0 flex items-center justify-center p-8">
                            <div class="text-center text-white">
                                <!-- Quran Icon -->
                                <div class="mx-auto mb-6 w-24 h-24 bg-white bg-opacity-20 rounded-full flex items-center justify-center">
                                    <svg class="w-12 h-12" fill="currentColor" viewBox="0 0 24 24">
                                        <path d="M12 2L13.5 8.5L20 7L14.5 12.5L21 14L13.5 15.5L12 22L10.5 15.5L4 14L9.5 12.5L3 7L9.5 8.5L12 2Z"/>
                                    </svg>
                                </div>

                                <!-- Academy Vision -->
                                <h3 class="text-2xl font-bold mb-4">رؤيتنا</h3>
                                <p class="text-lg opacity-90">
                                    نسعى لتقديم تعليم إسلامي متميز يجمع بين الأصالة والمعاصرة
                                </p>
                            </div>
                        </div>
                    </div>

                    <!-- Floating Elements -->
                    <div class="absolute -top-4 -right-4 w-20 h-20 academy-bg-secondary rounded-full opacity-80 animate-pulse"></div>
                    <div class="absolute -bottom-4 -left-4 w-16 h-16 bg-yellow-400 rounded-full opacity-80 animate-pulse"></div>
                    <div class="absolute top-1/2 -left-8 w-12 h-12 bg-green-400 rounded-full opacity-80 animate-pulse"></div>
                </div>
            </div>
        </div>
    </div>

    <!-- Scroll Down Indicator -->
    <div class="absolute bottom-8 left-1/2 transform -translate-x-1/2 animate-bounce">
        <div class="w-8 h-8 border-2 academy-border-primary rounded-full flex items-center justify-center">
            <svg class="w-4 h-4 academy-primary" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 14l-7 7m0 0l-7-7m7 7V3"/>
            </svg>
        </div>
    </div>
</section>