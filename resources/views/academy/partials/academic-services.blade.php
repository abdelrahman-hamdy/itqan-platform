<!-- Academic Services Section -->
<section class="py-16 bg-white dark:bg-gray-800" id="academic-services">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        <!-- Section Header -->
        <div class="text-center mb-16 animate-on-scroll">
            <div class="inline-flex items-center justify-center w-16 h-16 bg-blue-100 dark:bg-blue-900 rounded-full mb-6">
                <svg class="w-8 h-8 text-blue-600 dark:text-blue-400" fill="currentColor" viewBox="0 0 24 24">
                    <path d="M19 3H5C3.9 3 3 3.9 3 5V19C3 20.1 3.9 21 5 21H19C20.1 21 21 20.1 21 19V5C21 3.9 20.1 3 19 3ZM9 17H7V10H9V17ZM13 17H11V7H13V17ZM17 17H15V13H17V17Z"/>
                </svg>
            </div>
            <h2 class="text-3xl md:text-4xl font-bold text-gray-900 dark:text-white mb-4">
                الخدمات التعليمية
            </h2>
            <p class="text-lg text-gray-600 dark:text-gray-300 max-w-3xl mx-auto">
                نوفر برامج تعليمية شاملة تغطي جميع المواد الدراسية مع معلمين متخصصين ودورات تفاعلية حديثة
            </p>
        </div>

        <div class="grid grid-cols-1 lg:grid-cols-2 gap-12">
            <!-- Interactive Courses Section -->
            <div class="animate-on-scroll">
                <div class="bg-gradient-to-br from-blue-50 to-indigo-100 dark:from-gray-700 dark:to-gray-800 rounded-2xl shadow-lg p-8 h-full">
                    <div class="flex items-center gap-4 mb-6">
                        <div class="w-12 h-12 bg-blue-100 dark:bg-blue-900 rounded-lg flex items-center justify-center">
                            <svg class="w-6 h-6 text-blue-600 dark:text-blue-400" fill="currentColor" viewBox="0 0 24 24">
                                <path d="M19 3H5C3.9 3 3 3.9 3 5V19C3 20.1 3.9 21 5 21H19C20.1 21 21 20.1 21 19V5C21 3.9 20.1 3 19 3ZM14 17H12V15H14V17ZM14 11H12V5H14V11ZM10 17H8V9H10V17Z"/>
                            </svg>
                        </div>
                        <h3 class="text-2xl font-bold text-gray-900 dark:text-white">الدورات التفاعلية</h3>
                    </div>

                    <p class="text-gray-600 dark:text-gray-300 mb-8">
                        انضم إلى دوراتنا التفاعلية المباشرة واستفد من التعلم الجماعي مع معلمين خبراء
                    </p>

                    <!-- Interactive Courses List -->
                    <div class="space-y-4 mb-8">
                        @forelse($services['interactive_courses'] as $course)
                            <div class="bg-white dark:bg-gray-800 border border-gray-200 dark:border-gray-700 rounded-lg p-4 hover-lift">
                                <div class="flex justify-between items-start mb-3">
                                    <h4 class="font-semibold text-gray-900 dark:text-white text-lg">{{ $course->title }}</h4>
                                    <span class="px-2 py-1 bg-blue-100 dark:bg-blue-900 text-blue-800 dark:text-blue-200 text-xs rounded-full">
                                        {{ $course->max_students }} طالب
                                    </span>
                                </div>
                                
                                <p class="text-sm text-gray-600 dark:text-gray-300 mb-3">{{ $course->description }}</p>
                                
                                <div class="grid grid-cols-2 gap-4 mb-3 text-sm">
                                    <div class="flex items-center gap-2">
                                        <svg class="w-4 h-4 text-gray-400" fill="currentColor" viewBox="0 0 24 24">
                                            <path d="M12 2L13.5 8.5L20 7L14.5 12.5L21 14L13.5 15.5L12 22L10.5 15.5L4 14L9.5 12.5L3 7L9.5 8.5L12 2Z"/>
                                        </svg>
                                        @if($course->subject)
                                            <span class="text-gray-600 dark:text-gray-300">{{ $course->subject->name }}</span>
                                        @endif
                                    </div>
                                    <div class="flex items-center gap-2">
                                        <svg class="w-4 h-4 text-gray-400" fill="currentColor" viewBox="0 0 24 24">
                                            <path d="M12 2C6.48 2 2 6.48 2 12S6.48 22 12 22 22 17.52 22 12 17.52 2 12 2ZM13 17H11V15H13V17ZM13 13H11V7H13V13Z"/>
                                        </svg>
                                        <span class="text-gray-600 dark:text-gray-300">{{ $course->duration_weeks }} أسبوع</span>
                                    </div>
                                </div>

                                <div class="flex flex-wrap gap-2 mb-3">
                                    @if($course->gradeLevel)
                                        <span class="bg-purple-100 dark:bg-purple-900 text-purple-800 dark:text-purple-200 px-2 py-1 rounded text-xs">
                                            {{ $course->gradeLevel->getDisplayName() }}
                                        </span>
                                    @endif
                                    <span class="bg-green-100 dark:bg-green-900 text-green-800 dark:text-green-200 px-2 py-1 rounded text-xs">
                                        {{ $course->sessions_per_week }} جلسة/أسبوع
                                    </span>
                                    <span class="bg-yellow-100 dark:bg-yellow-900 text-yellow-800 dark:text-yellow-200 px-2 py-1 rounded text-xs">
                                        {{ $course->session_duration_minutes }} دقيقة
                                    </span>
                                </div>

                                @if($course->assignedTeacher && $course->assignedTeacher->user)
                                    <div class="pt-3 border-t border-gray-200 dark:border-gray-600">
                                        <p class="text-sm text-gray-600 dark:text-gray-300">
                                            المعلم: {{ $course->assignedTeacher->user->name }}
                                        </p>
                                    </div>
                                @endif

                                <div class="flex justify-between items-center mt-4">
                                    @if($course->student_price > 0)
                                        <span class="text-lg font-bold academy-primary">{{ number_format($course->student_price) }} ريال</span>
                                    @else
                                        <span class="text-lg font-bold text-green-600">مجاني</span>
                                    @endif
                                    <button class="academy-bg-primary text-white px-4 py-2 rounded-lg text-sm hover:opacity-90 transition-opacity">
                                        سجل الآن
                                    </button>
                                </div>
                            </div>
                        @empty
                            <div class="text-center py-8">
                                <svg class="w-16 h-16 text-gray-400 mx-auto mb-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6.253v13m0-13C10.832 5.477 9.246 5 7.5 5S4.168 5.477 3 6.253v13C4.168 18.477 5.754 18 7.5 18s3.332.477 4.5 1.253m0-13C13.168 5.477 14.754 5 16.5 5c1.746 0 3.332.477 4.5 1.253v13C20.832 18.477 19.246 18 17.5 18c-1.746 0-3.332.477-4.5 1.253"/>
                                </svg>
                                <p class="text-gray-500 dark:text-gray-400">لا توجد دورات تفاعلية متاحة حالياً</p>
                            </div>
                        @endforelse
                    </div>

                    <a href="#" class="inline-flex items-center text-blue-600 dark:text-blue-400 hover:text-blue-700 dark:hover:text-blue-300 font-medium">
                        عرض جميع الدورات
                        <svg class="w-4 h-4 me-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"/>
                        </svg>
                    </a>
                </div>
            </div>

            <!-- Academic Teachers Section -->
            <div class="animate-on-scroll">
                <div class="bg-gradient-to-br from-purple-50 to-pink-100 dark:from-gray-700 dark:to-gray-800 rounded-2xl shadow-lg p-8 h-full">
                    <div class="flex items-center gap-4 mb-6">
                        <div class="w-12 h-12 bg-purple-100 dark:bg-purple-900 rounded-lg flex items-center justify-center">
                            <svg class="w-6 h-6 text-purple-600 dark:text-purple-400" fill="currentColor" viewBox="0 0 24 24">
                                <path d="M12 12C14.21 12 16 10.21 16 8C16 5.79 14.21 4 12 4C9.79 4 8 5.79 8 8C8 10.21 9.79 12 12 12ZM12 14C9.33 14 4 15.34 4 18V20H20V18C20 15.34 14.67 14 12 14Z"/>
                            </svg>
                        </div>
                        <h3 class="text-2xl font-bold text-gray-900 dark:text-white">المعلمون المتخصصون</h3>
                    </div>

                    <p class="text-gray-600 dark:text-gray-300 mb-8">
                        احصل على تعليم فردي متخصص في جميع المواد الدراسية مع معلمين خبراء
                    </p>

                    <!-- Academic Teachers List -->
                    <div class="space-y-4 mb-8">
                        @forelse($services['academic_teachers'] as $teacher)
                            <div class="bg-white dark:bg-gray-800 border border-gray-200 dark:border-gray-700 rounded-lg p-4 hover-lift">
                                <div class="flex items-start gap-4">
                                    <div class="w-12 h-12 bg-purple-100 dark:bg-purple-900 rounded-full flex items-center justify-center flex-shrink-0">
                                        <span class="text-purple-600 dark:text-purple-400 font-semibold">
                                            {{ substr($teacher->name, 0, 1) }}
                                        </span>
                                    </div>
                                    <div class="flex-1">
                                        <h4 class="font-semibold text-gray-900 dark:text-white mb-1">{{ $teacher->name }}</h4>
                                        @if($teacher->academicTeacherProfile)
                                            <p class="text-sm text-gray-600 dark:text-gray-300 mb-2">
                                                {{ $teacher->academicTeacherProfile->specialization ?? 'معلم متخصص' }}
                                            </p>
                                            @if($teacher->academicTeacherProfile->experience_years)
                                                <div class="flex items-center gap-2 text-xs text-gray-500 dark:text-gray-400 mb-2">
                                                    <svg class="w-4 h-4" fill="currentColor" viewBox="0 0 24 24">
                                                        <path d="M12 2L15.09 8.26L22 9L17 14L18.18 21L12 17.77L5.82 21L7 14L2 9L8.91 8.26L12 2Z"/>
                                                    </svg>
                                                    {{ $teacher->academicTeacherProfile->experience_years }} سنوات خبرة
                                                </div>
                                            @endif
                                        @endif
                                        
                                        <!-- Teacher Subjects -->
                                        @if($teacher->subjects && $teacher->subjects->count() > 0)
                                            <div class="flex flex-wrap gap-1 mb-2">
                                                @foreach($teacher->subjects->take(3) as $subject)
                                                    <span class="bg-blue-100 dark:bg-blue-900 text-blue-800 dark:text-blue-200 px-2 py-1 rounded text-xs">
                                                        {{ $subject->name }}
                                                    </span>
                                                @endforeach
                                                @if($teacher->subjects->count() > 3)
                                                    <span class="text-xs text-gray-500 dark:text-gray-400">
                                                        +{{ $teacher->subjects->count() - 3 }} أخرى
                                                    </span>
                                                @endif
                                            </div>
                                        @endif
                                    </div>
                                    <div class="flex flex-col items-end">
                                        <span class="px-2 py-1 bg-purple-100 dark:bg-purple-900 text-purple-800 dark:text-purple-200 text-xs rounded-full mb-2">
                                            متاح
                                        </span>
                                        <button class="text-purple-600 dark:text-purple-400 text-sm hover:text-purple-700 dark:hover:text-purple-300">
                                            حجز موعد
                                        </button>
                                    </div>
                                </div>
                            </div>
                        @empty
                            <div class="text-center py-8">
                                <svg class="w-16 h-16 text-gray-400 mx-auto mb-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197m13.5-9a2.5 2.5 0 11-5 0 2.5 2.5 0 015 0z"/>
                                </svg>
                                <p class="text-gray-500 dark:text-gray-400">لا يوجد معلمون متاحون حالياً</p>
                            </div>
                        @endforelse
                    </div>

                    <a href="#" class="inline-flex items-center text-purple-600 dark:text-purple-400 hover:text-purple-700 dark:hover:text-purple-300 font-medium">
                        عرض جميع المعلمين
                        <svg class="w-4 h-4 me-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"/>
                        </svg>
                    </a>
                </div>
            </div>
        </div>

        <!-- Academic Services Benefits -->
        <div class="mt-16 animate-on-scroll">
            <div class="bg-gradient-to-r from-blue-600 to-purple-600 dark:from-blue-800 dark:to-purple-800 rounded-2xl shadow-lg p-8 text-white">
                <div class="text-center mb-8">
                    <h3 class="text-2xl md:text-3xl font-bold mb-4">لماذا تختار خدماتنا التعليمية؟</h3>
                    <p class="text-blue-100">نقدم تعليماً متميزاً يضمن النجاح والتفوق الدراسي</p>
                </div>
                
                <div class="grid grid-cols-1 md:grid-cols-4 gap-6">
                    <div class="text-center">
                        <div class="w-16 h-16 bg-white bg-opacity-20 rounded-full flex items-center justify-center mx-auto mb-4">
                            <svg class="w-8 h-8" fill="currentColor" viewBox="0 0 24 24">
                                <path d="M12 2L15.09 8.26L22 9L17 14L18.18 21L12 17.77L5.82 21L7 14L2 9L8.91 8.26L12 2Z"/>
                            </svg>
                        </div>
                        <h4 class="font-semibold mb-2">معلمون مؤهلون</h4>
                        <p class="text-sm text-blue-100">جميع معلمينا حاصلون على مؤهلات عليا في تخصصاتهم</p>
                    </div>
                    
                    <div class="text-center">
                        <div class="w-16 h-16 bg-white bg-opacity-20 rounded-full flex items-center justify-center mx-auto mb-4">
                            <svg class="w-8 h-8" fill="currentColor" viewBox="0 0 24 24">
                                <path d="M12 2C6.48 2 2 6.48 2 12S6.48 22 12 22 22 17.52 22 12 17.52 2 12 2ZM13 17H11V15H13V17ZM13 13H11V7H13V13Z"/>
                            </svg>
                        </div>
                        <h4 class="font-semibold mb-2">مناهج حديثة</h4>
                        <p class="text-sm text-blue-100">نستخدم أحدث الطرق التعليمية والتقنيات المبتكرة</p>
                    </div>
                    
                    <div class="text-center">
                        <div class="w-16 h-16 bg-white bg-opacity-20 rounded-full flex items-center justify-center mx-auto mb-4">
                            <svg class="w-8 h-8" fill="currentColor" viewBox="0 0 24 24">
                                <path d="M19 3H5C3.9 3 3 3.9 3 5V19C3 20.1 3.9 21 5 21H19C20.1 21 21 20.1 21 19V5C21 3.9 20.1 3 19 3ZM9 17H7V10H9V17ZM13 17H11V7H13V17ZM17 17H15V13H17V17Z"/>
                            </svg>
                        </div>
                        <h4 class="font-semibold mb-2">تقييم مستمر</h4>
                        <p class="text-sm text-blue-100">متابعة دورية لمستوى الطلاب وتقارير تقدم مفصلة</p>
                    </div>
                    
                    <div class="text-center">
                        <div class="w-16 h-16 bg-white bg-opacity-20 rounded-full flex items-center justify-center mx-auto mb-4">
                            <svg class="w-8 h-8" fill="currentColor" viewBox="0 0 24 24">
                                <path d="M12 2L13.5 8.5L20 7L14.5 12.5L21 14L13.5 15.5L12 22L10.5 15.5L4 14L9.5 12.5L3 7L9.5 8.5L12 2Z"/>
                            </svg>
                        </div>
                        <h4 class="font-semibold mb-2">مرونة في التعلم</h4>
                        <p class="text-sm text-blue-100">أوقات مرنة تناسب جدول الطالب وظروفه</p>
                    </div>
                </div>
            </div>
        </div>
    </div>
</section>