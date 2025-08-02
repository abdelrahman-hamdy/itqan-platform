<!-- Recorded Courses Section -->
<section class="py-16 bg-gradient-to-br from-indigo-50 to-purple-100 dark:from-gray-800 dark:to-gray-900" id="recorded-courses">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        <!-- Section Header -->
        <div class="text-center mb-16 animate-on-scroll">
            <div class="inline-flex items-center justify-center w-16 h-16 bg-indigo-100 dark:bg-indigo-900 rounded-full mb-6">
                <svg class="w-8 h-8 text-indigo-600 dark:text-indigo-400" fill="currentColor" viewBox="0 0 24 24">
                    <path d="M8 5V19L19 12L8 5Z"/>
                </svg>
            </div>
            <h2 class="text-3xl md:text-4xl font-bold text-gray-900 dark:text-white mb-4">
                الدورات المسجلة
            </h2>
            <p class="text-lg text-gray-600 dark:text-gray-300 max-w-3xl mx-auto">
                تعلم في أي وقت ومن أي مكان مع مجموعتنا الواسعة من الدورات المسجلة عالية الجودة
            </p>
        </div>

        <!-- Featured Courses Grid -->
        @if($services['recorded_courses'] && $services['recorded_courses']->count() > 0)
            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-8 mb-12">
                @foreach($services['recorded_courses'] as $course)
                    <div class="bg-white dark:bg-gray-800 rounded-2xl shadow-lg overflow-hidden hover-lift animate-on-scroll group">
                        <!-- Course Thumbnail -->
                        <div class="relative h-48 bg-gradient-to-br from-indigo-400 to-purple-500 overflow-hidden">
                            @if($course->thumbnail_url)
                                <img src="{{ $course->thumbnail_url }}" alt="{{ $course->title }}" class="w-full h-full object-cover group-hover:scale-105 transition-transform duration-300">
                            @else
                                <div class="w-full h-full flex items-center justify-center">
                                    <svg class="w-16 h-16 text-white opacity-80" fill="currentColor" viewBox="0 0 24 24">
                                        <path d="M8 5V19L19 12L8 5Z"/>
                                    </svg>
                                </div>
                            @endif
                            
                            <!-- Course Overlay -->
                            <div class="absolute inset-0 bg-black bg-opacity-20 group-hover:bg-opacity-30 transition-all duration-300"></div>
                            
                            <!-- Duration Badge -->
                            @if($course->total_duration_minutes)
                                <div class="absolute top-4 right-4 bg-black bg-opacity-60 text-white px-2 py-1 rounded text-sm">
                                    {{ floor($course->total_duration_minutes / 60) }}:{{ str_pad($course->total_duration_minutes % 60, 2, '0', STR_PAD_LEFT) }}
                                </div>
                            @endif
                            
                            <!-- Featured Badge -->
                            @if($course->is_featured)
                                <div class="absolute top-4 left-4 bg-yellow-500 text-white px-2 py-1 rounded text-sm font-semibold">
                                    مميز
                                </div>
                            @endif
                        </div>

                        <!-- Course Content -->
                        <div class="p-6">
                            <!-- Course Category & Level -->
                            <div class="flex items-center justify-between mb-3">
                                @if($course->subject)
                                    <span class="inline-block bg-indigo-100 dark:bg-indigo-900 text-indigo-800 dark:text-indigo-200 px-2 py-1 rounded text-sm">
                                        {{ $course->subject->name }}
                                    </span>
                                @endif
                                @if($course->difficulty_level)
                                    <span class="text-sm text-gray-500 dark:text-gray-400">{{ $course->difficulty_level }}</span>
                                @endif
                            </div>

                            <!-- Course Title -->
                            <h3 class="text-xl font-bold text-gray-900 dark:text-white mb-2 line-clamp-2">
                                {{ $course->title }}
                            </h3>

                            <!-- Course Description -->
                            <p class="text-gray-600 dark:text-gray-300 text-sm mb-4 line-clamp-3">
                                {{ $course->description }}
                            </p>

                            <!-- Course Stats -->
                            <div class="flex items-center justify-between text-sm text-gray-500 dark:text-gray-400 mb-4">
                                <div class="flex items-center gap-4">
                                    @if($course->total_lessons)
                                        <span class="flex items-center gap-1">
                                            <svg class="w-4 h-4" fill="currentColor" viewBox="0 0 24 24">
                                                <path d="M19 3H5C3.9 3 3 3.9 3 5V19C3 20.1 3.9 21 5 21H19C20.1 21 21 20.1 21 19V5C21 3.9 20.1 3 19 3ZM14 17H12V15H14V17ZM14 11H12V5H14V11ZM10 17H8V9H10V17Z"/>
                                            </svg>
                                            {{ $course->total_lessons }} درس
                                        </span>
                                    @endif
                                    @if($course->total_enrollments)
                                        <span class="flex items-center gap-1">
                                            <svg class="w-4 h-4" fill="currentColor" viewBox="0 0 24 24">
                                                <path d="M12 12C14.21 12 16 10.21 16 8C16 5.79 14.21 4 12 4C9.79 4 8 5.79 8 8C8 10.21 9.79 12 12 12ZM12 14C9.33 14 4 15.34 4 18V20H20V18C20 15.34 14.67 14 12 14Z"/>
                                            </svg>
                                            {{ $course->total_enrollments }} طالب
                                        </span>
                                    @endif
                                </div>
                                
                                @if($course->avg_rating)
                                    <div class="flex items-center gap-1">
                                        <svg class="w-4 h-4 text-yellow-400" fill="currentColor" viewBox="0 0 24 24">
                                            <path d="M12 2L15.09 8.26L22 9L17 14L18.18 21L12 17.77L5.82 21L7 14L2 9L8.91 8.26L12 2Z"/>
                                        </svg>
                                        <span>{{ number_format($course->avg_rating, 1) }}</span>
                                    </div>
                                @endif
                            </div>

                            <!-- Instructor -->
                            @if($course->instructor && $course->instructor->user)
                                <div class="flex items-center gap-3 mb-4 pb-4 border-b border-gray-200 dark:border-gray-700">
                                    <div class="w-8 h-8 bg-gray-300 dark:bg-gray-600 rounded-full flex items-center justify-center">
                                        <span class="text-sm font-medium text-gray-700 dark:text-gray-300">
                                            {{ substr($course->instructor->user->name, 0, 1) }}
                                        </span>
                                    </div>
                                    <div>
                                        <p class="text-sm font-medium text-gray-900 dark:text-white">{{ $course->instructor->user->name }}</p>
                                        <p class="text-xs text-gray-500 dark:text-gray-400">مدرب معتمد</p>
                                    </div>
                                </div>
                            @endif

                            <!-- Price & Action -->
                            <div class="flex items-center justify-between">
                                <div>
                                    @if($course->is_free)
                                        <span class="text-2xl font-bold text-green-600">مجاني</span>
                                    @else
                                        <div class="flex items-center gap-2">
                                            @if($course->discount_price && $course->discount_price < $course->price)
                                                <span class="text-2xl font-bold academy-primary">{{ number_format($course->discount_price) }}</span>
                                                <span class="text-lg text-gray-500 line-through">{{ number_format($course->price) }}</span>
                                                <span class="text-sm text-white bg-red-500 px-2 py-1 rounded">ريال</span>
                                            @else
                                                <span class="text-2xl font-bold academy-primary">{{ number_format($course->price) }}</span>
                                                <span class="text-sm text-gray-500">ريال</span>
                                            @endif
                                        </div>
                                    @endif
                                </div>

                                <a href="{{ route('courses.show', $course) }}" class="academy-bg-primary text-white px-4 py-2 rounded-lg text-sm font-medium hover:opacity-90 transition-opacity">
                                    عرض التفاصيل
                                </a>
                            </div>
                        </div>
                    </div>
                @endforeach
            </div>
        @else
            <!-- No Courses Available -->
            <div class="text-center py-16 animate-on-scroll">
                <svg class="w-24 h-24 text-gray-400 mx-auto mb-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 4V2C7 1.45 7.45 1 8 1H16C16.55 1 17 1.45 17 2V4H20C20.55 4 21 4.45 21 5S20.55 6 20 6H19V19C19 20.1 18.1 21 17 21H7C5.9 21 5 20.1 5 19V6H4C3.45 6 3 5.55 3 5S3.45 4 4 4H7Z"/>
                </svg>
                <h3 class="text-2xl font-bold text-gray-900 dark:text-white mb-4">لا توجد دورات مسجلة متاحة حالياً</h3>
                <p class="text-gray-600 dark:text-gray-300 mb-8">نعمل على إضافة محتوى تعليمي جديد قريباً</p>
                <button class="academy-bg-primary text-white px-6 py-3 rounded-lg font-medium hover:opacity-90 transition-opacity">
                    اشترك في التحديثات
                </button>
            </div>
        @endif

        <!-- View All Courses Button -->
        @if($services['recorded_courses'] && $services['recorded_courses']->count() > 0)
            <div class="text-center animate-on-scroll">
                <a href="{{ route('courses.index', ['subdomain' => $academy->subdomain]) }}" class="inline-flex items-center academy-bg-primary text-white px-8 py-3 rounded-lg font-medium hover:opacity-90 transition-opacity">
                    عرض جميع الدورات
                    <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"/>
                    </svg>
                </a>
            </div>
        @endif

        <!-- Course Categories -->
        <div class="mt-16 animate-on-scroll">
            <h3 class="text-2xl font-bold text-center text-gray-900 dark:text-white mb-8">فئات الدورات المتاحة</h3>
            <div class="grid grid-cols-2 md:grid-cols-4 lg:grid-cols-6 gap-4">
                <div class="bg-white dark:bg-gray-800 rounded-lg p-4 text-center hover-lift group">
                    <div class="w-12 h-12 bg-blue-100 dark:bg-blue-900 rounded-lg flex items-center justify-center mx-auto mb-3 group-hover:bg-blue-200 dark:group-hover:bg-blue-800 transition-colors">
                        <svg class="w-6 h-6 text-blue-600 dark:text-blue-400" fill="currentColor" viewBox="0 0 24 24">
                            <path d="M12 2L13.5 8.5L20 7L14.5 12.5L21 14L13.5 15.5L12 22L10.5 15.5L4 14L9.5 12.5L3 7L9.5 8.5L12 2Z"/>
                        </svg>
                    </div>
                    <h4 class="font-semibold text-gray-900 dark:text-white text-sm">علوم الشريعة</h4>
                </div>

                <div class="bg-white dark:bg-gray-800 rounded-lg p-4 text-center hover-lift group">
                    <div class="w-12 h-12 bg-green-100 dark:bg-green-900 rounded-lg flex items-center justify-center mx-auto mb-3 group-hover:bg-green-200 dark:group-hover:bg-green-800 transition-colors">
                        <svg class="w-6 h-6 text-green-600 dark:text-green-400" fill="currentColor" viewBox="0 0 24 24">
                            <path d="M19 3H5C3.9 3 3 3.9 3 5V19C3 20.1 3.9 21 5 21H19C20.1 21 21 20.1 21 19V5C21 3.9 20.1 3 19 3ZM14 17H12V15H14V17ZM14 11H12V5H14V11ZM10 17H8V9H10V17Z"/>
                        </svg>
                    </div>
                    <h4 class="font-semibold text-gray-900 dark:text-white text-sm">اللغة العربية</h4>
                </div>

                <div class="bg-white dark:bg-gray-800 rounded-lg p-4 text-center hover-lift group">
                    <div class="w-12 h-12 bg-yellow-100 dark:bg-yellow-900 rounded-lg flex items-center justify-center mx-auto mb-3 group-hover:bg-yellow-200 dark:group-hover:bg-yellow-800 transition-colors">
                        <svg class="w-6 h-6 text-yellow-600 dark:text-yellow-400" fill="currentColor" viewBox="0 0 24 24">
                            <path d="M12 2C6.48 2 2 6.48 2 12S6.48 22 12 22 22 17.52 22 12 17.52 2 12 2ZM13 17H11V15H13V17ZM13 13H11V7H13V13Z"/>
                        </svg>
                    </div>
                    <h4 class="font-semibold text-gray-900 dark:text-white text-sm">الرياضيات</h4>
                </div>

                <div class="bg-white dark:bg-gray-800 rounded-lg p-4 text-center hover-lift group">
                    <div class="w-12 h-12 bg-purple-100 dark:bg-purple-900 rounded-lg flex items-center justify-center mx-auto mb-3 group-hover:bg-purple-200 dark:group-hover:bg-purple-800 transition-colors">
                        <svg class="w-6 h-6 text-purple-600 dark:text-purple-400" fill="currentColor" viewBox="0 0 24 24">
                            <path d="M12 2L15.09 8.26L22 9L17 14L18.18 21L12 17.77L5.82 21L7 14L2 9L8.91 8.26L12 2Z"/>
                        </svg>
                    </div>
                    <h4 class="font-semibold text-gray-900 dark:text-white text-sm">العلوم</h4>
                </div>

                <div class="bg-white dark:bg-gray-800 rounded-lg p-4 text-center hover-lift group">
                    <div class="w-12 h-12 bg-red-100 dark:bg-red-900 rounded-lg flex items-center justify-center mx-auto mb-3 group-hover:bg-red-200 dark:group-hover:bg-red-800 transition-colors">
                        <svg class="w-6 h-6 text-red-600 dark:text-red-400" fill="currentColor" viewBox="0 0 24 24">
                            <path d="M12 2C13.1 2 14 2.9 14 4C14 5.1 13.1 6 12 6C10.9 6 10 5.1 10 4C10 2.9 10.9 2 12 2Z"/>
                        </svg>
                    </div>
                    <h4 class="font-semibold text-gray-900 dark:text-white text-sm">التاريخ</h4>
                </div>

                <div class="bg-white dark:bg-gray-800 rounded-lg p-4 text-center hover-lift group">
                    <div class="w-12 h-12 bg-indigo-100 dark:bg-indigo-900 rounded-lg flex items-center justify-center mx-auto mb-3 group-hover:bg-indigo-200 dark:group-hover:bg-indigo-800 transition-colors">
                        <svg class="w-6 h-6 text-indigo-600 dark:text-indigo-400" fill="currentColor" viewBox="0 0 24 24">
                            <path d="M12 2L13.5 8.5L20 7L14.5 12.5L21 14L13.5 15.5L12 22L10.5 15.5L4 14L9.5 12.5L3 7L9.5 8.5L12 2Z"/>
                        </svg>
                    </div>
                    <h4 class="font-semibold text-gray-900 dark:text-white text-sm">مهارات عامة</h4>
                </div>
            </div>
        </div>
    </div>
</section>