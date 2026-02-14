<!-- Stats Counter Section -->
<section class="py-16 bg-white dark:bg-gray-800" id="stats">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        <!-- Section Header -->
        <div class="text-center mb-12 animate-on-scroll">
            <h2 class="text-3xl md:text-4xl font-bold text-gray-900 dark:text-white mb-4">
                أرقامنا تتحدث
            </h2>
            <p class="text-lg text-gray-600 dark:text-gray-300 max-w-3xl mx-auto">
                نفخر بما حققناه من إنجازات في رحلتنا التعليمية، ونسعى دائماً لتقديم الأفضل لطلابنا
            </p>
        </div>

        <!-- Stats Grid -->
        <div class="grid grid-cols-2 md:grid-cols-5 gap-8">
            <!-- Total Students -->
            <div class="text-center group animate-on-scroll hover-lift">
                <div class="academy-bg-primary bg-opacity-10 w-20 h-20 mx-auto rounded-full flex items-center justify-center mb-4 group-hover:bg-opacity-20 transition-all duration-300">
                    <svg class="w-10 h-10 academy-primary" fill="currentColor" viewBox="0 0 24 24">
                        <path d="M12 12C14.21 12 16 10.21 16 8C16 5.79 14.21 4 12 4C9.79 4 8 5.79 8 8C8 10.21 9.79 12 12 12ZM12 14C9.33 14 4 15.34 4 18V20H20V18C20 15.34 14.67 14 12 14Z"/>
                    </svg>
                </div>
                <div class="counter text-3xl md:text-4xl font-bold academy-primary mb-2" data-target="{{ $stats['total_students'] }}">0</div>
                <div class="text-sm md:text-base text-gray-600 dark:text-gray-300 font-medium">طالب وطالبة</div>
                <div class="w-12 h-1 academy-bg-primary mx-auto mt-3 rounded-full opacity-0 group-hover:opacity-100 transition-opacity duration-300"></div>
            </div>

            <!-- Total Teachers -->
            <div class="text-center group animate-on-scroll hover-lift">
                <div class="academy-bg-secondary bg-opacity-10 w-20 h-20 mx-auto rounded-full flex items-center justify-center mb-4 group-hover:bg-opacity-20 transition-all duration-300">
                    <svg class="w-10 h-10 academy-secondary" fill="currentColor" viewBox="0 0 24 24">
                        <path d="M12 2L13.5 8.5L20 7L14.5 12.5L21 14L13.5 15.5L12 22L10.5 15.5L4 14L9.5 12.5L3 7L9.5 8.5L12 2Z"/>
                    </svg>
                </div>
                <div class="counter text-3xl md:text-4xl font-bold academy-secondary mb-2" data-target="{{ $stats['total_teachers'] }}">0</div>
                <div class="text-sm md:text-base text-gray-600 dark:text-gray-300 font-medium">معلم ومعلمة</div>
                <div class="w-12 h-1 academy-bg-secondary mx-auto mt-3 rounded-full opacity-0 group-hover:opacity-100 transition-opacity duration-300"></div>
            </div>

            <!-- Active Courses -->
            <div class="text-center group animate-on-scroll hover-lift">
                <div class="bg-green-100 w-20 h-20 mx-auto rounded-full flex items-center justify-center mb-4 group-hover:bg-green-200 transition-all duration-300">
                    <svg class="w-10 h-10 text-green-600" fill="currentColor" viewBox="0 0 24 24">
                        <path d="M19 3H5C3.9 3 3 3.9 3 5V19C3 20.1 3.9 21 5 21H19C20.1 21 21 20.1 21 19V5C21 3.9 20.1 3 19 3ZM9 17H7V10H9V17ZM13 17H11V7H13V17ZM17 17H15V13H17V17Z"/>
                    </svg>
                </div>
                <div class="counter text-3xl md:text-4xl font-bold text-green-600 mb-2" data-target="{{ $stats['active_courses'] }}">0</div>
                <div class="text-sm md:text-base text-gray-600 dark:text-gray-300 font-medium">دورة تعليمية</div>
                <div class="w-12 h-1 bg-green-600 mx-auto mt-3 rounded-full opacity-0 group-hover:opacity-100 transition-opacity duration-300"></div>
            </div>

            <!-- Quran Circles -->
            <div class="text-center group animate-on-scroll hover-lift">
                <div class="bg-purple-100 w-20 h-20 mx-auto rounded-full flex items-center justify-center mb-4 group-hover:bg-purple-200 transition-all duration-300">
                    <svg class="w-10 h-10 text-purple-600" fill="currentColor" viewBox="0 0 24 24">
                        <path d="M12 2C13.1 2 14 2.9 14 4C14 5.1 13.1 6 12 6C10.9 6 10 5.1 10 4C10 2.9 10.9 2 12 2ZM21 9V7L15 7.5V9L21 9ZM3 9L9 8.5V7L3 7.5V9ZM13.5 7C14.6 7 15.5 7.9 15.5 9S14.6 11 13.5 11S11.5 10.1 11.5 9S12.4 7 13.5 7ZM9.5 7C10.6 7 11.5 7.9 11.5 9S10.6 11 9.5 11S7.5 10.1 7.5 9S8.4 7 9.5 7ZM16.5 13.5C17.6 13.5 18.5 14.4 18.5 15.5S17.6 17.5 16.5 17.5S14.5 16.6 14.5 15.5S15.4 13.5 16.5 13.5ZM6.5 13.5C7.6 13.5 8.5 14.4 8.5 15.5S7.6 17.5 6.5 17.5S4.5 16.6 4.5 15.5S5.4 13.5 6.5 13.5Z"/>
                    </svg>
                </div>
                <div class="counter text-3xl md:text-4xl font-bold text-purple-600 mb-2" data-target="{{ $stats['quran_circles'] }}">0</div>
                <div class="text-sm md:text-base text-gray-600 dark:text-gray-300 font-medium">حلقة قرآنية</div>
                <div class="w-12 h-1 bg-purple-600 mx-auto mt-3 rounded-full opacity-0 group-hover:opacity-100 transition-opacity duration-300"></div>
            </div>

            <!-- Completion Rate -->
            <div class="text-center group animate-on-scroll hover-lift">
                <div class="bg-yellow-100 w-20 h-20 mx-auto rounded-full flex items-center justify-center mb-4 group-hover:bg-yellow-200 transition-all duration-300">
                    <svg class="w-10 h-10 text-yellow-600" fill="currentColor" viewBox="0 0 24 24">
                        <path d="M12 2L15.09 8.26L22 9L17 14L18.18 21L12 17.77L5.82 21L7 14L2 9L8.91 8.26L12 2Z"/>
                    </svg>
                </div>
                <div class="flex items-center justify-center">
                    <span class="counter text-3xl md:text-4xl font-bold text-yellow-600 mb-2" data-target="{{ $stats['completion_rate'] }}">0</span>
                    <span class="text-3xl md:text-4xl font-bold text-yellow-600 mb-2">%</span>
                </div>
                <div class="text-sm md:text-base text-gray-600 dark:text-gray-300 font-medium">معدل الإنجاز</div>
                <div class="w-12 h-1 bg-yellow-600 mx-auto mt-3 rounded-full opacity-0 group-hover:opacity-100 transition-opacity duration-300"></div>
            </div>
        </div>

        <!-- Achievement Badges -->
        <div class="mt-16 text-center animate-on-scroll">
            <h3 class="text-xl font-semibold text-gray-900 dark:text-white mb-8">إنجازاتنا وشراكاتنا</h3>
            <div class="flex flex-wrap justify-center items-center gap-8 opacity-60">
                <!-- Achievement Badge 1 -->
                <div class="flex items-center gap-2 bg-gray-50 dark:bg-gray-700 px-4 py-2 rounded-lg">
                    <svg class="w-6 h-6 text-green-500" fill="currentColor" viewBox="0 0 24 24">
                        <path d="M12 2L15.09 8.26L22 9L17 14L18.18 21L12 17.77L5.82 21L7 14L2 9L8.91 8.26L12 2Z"/>
                    </svg>
                    <span class="text-sm font-medium text-gray-700 dark:text-gray-300">تعليم متميز</span>
                </div>

                <!-- Achievement Badge 2 -->
                <div class="flex items-center gap-2 bg-gray-50 dark:bg-gray-700 px-4 py-2 rounded-lg">
                    <svg class="w-6 h-6 text-blue-500" fill="currentColor" viewBox="0 0 24 24">
                        <path d="M12 2C13.1 2 14 2.9 14 4C14 5.1 13.1 6 12 6C10.9 6 10 5.1 10 4C10 2.9 10.9 2 12 2Z"/>
                    </svg>
                    <span class="text-sm font-medium text-gray-700 dark:text-gray-300">جودة التعليم</span>
                </div>

                <!-- Achievement Badge 3 -->
                <div class="flex items-center gap-2 bg-gray-50 dark:bg-gray-700 px-4 py-2 rounded-lg">
                    <svg class="w-6 h-6 text-purple-500" fill="currentColor" viewBox="0 0 24 24">
                        <path d="M12 2L13.5 8.5L20 7L14.5 12.5L21 14L13.5 15.5L12 22L10.5 15.5L4 14L9.5 12.5L3 7L9.5 8.5L12 2Z"/>
                    </svg>
                    <span class="text-sm font-medium text-gray-700 dark:text-gray-300">تميز تعليمي</span>
                </div>
            </div>
        </div>
    </div>
</section>