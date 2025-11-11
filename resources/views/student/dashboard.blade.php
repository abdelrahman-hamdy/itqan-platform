<x-layouts.student-layout title="لوحة التحكم">
    <div class="container mx-auto px-4 py-8">
        <!-- Welcome Header -->
        <div class="mb-8">
            <h1 class="text-3xl font-bold text-gray-900 mb-2">مرحباً، {{ Auth::user()->name }}</h1>
            <p class="text-gray-600">نظرة عامة على تقدمك الدراسي</p>
        </div>

        <!-- Quick Stats Grid -->
        <div class="grid grid-cols-1 md:grid-cols-4 gap-6 mb-8">
            <!-- Active Courses -->
            <div class="bg-gradient-to-br from-blue-50 to-blue-100 rounded-xl shadow-sm border border-blue-200 p-6">
                <div class="flex items-center justify-between">
                    <div>
                        <p class="text-sm text-blue-700 mb-1">الدورات النشطة</p>
                        <p class="text-3xl font-bold text-blue-900">{{ $activeCourses->count() }}</p>
                    </div>
                    <div class="bg-blue-200 rounded-full p-3">
                        <i class="ri-book-open-line text-blue-700 text-2xl"></i>
                    </div>
                </div>
            </div>

            <!-- Completed Courses -->
            <div class="bg-gradient-to-br from-green-50 to-green-100 rounded-xl shadow-sm border border-green-200 p-6">
                <div class="flex items-center justify-between">
                    <div>
                        <p class="text-sm text-green-700 mb-1">الدورات المكتملة</p>
                        <p class="text-3xl font-bold text-green-900">{{ $completedCourses->count() }}</p>
                    </div>
                    <div class="bg-green-200 rounded-full p-3">
                        <i class="ri-checkbox-circle-line text-green-700 text-2xl"></i>
                    </div>
                </div>
            </div>

            <!-- Pending Homework -->
            @if(isset($homeworkStats))
            <div class="bg-gradient-to-br from-yellow-50 to-yellow-100 rounded-xl shadow-sm border border-yellow-200 p-6">
                <div class="flex items-center justify-between">
                    <div>
                        <p class="text-sm text-yellow-700 mb-1">واجبات معلقة</p>
                        <p class="text-3xl font-bold text-yellow-900">{{ $homeworkStats['pending'] }}</p>
                    </div>
                    <div class="bg-yellow-200 rounded-full p-3">
                        <i class="ri-file-list-line text-yellow-700 text-2xl"></i>
                    </div>
                </div>
            </div>

            <!-- Average Score -->
            <div class="bg-gradient-to-br from-purple-50 to-purple-100 rounded-xl shadow-sm border border-purple-200 p-6">
                <div class="flex items-center justify-between">
                    <div>
                        <p class="text-sm text-purple-700 mb-1">معدل الواجبات</p>
                        <p class="text-3xl font-bold text-purple-900">{{ number_format($homeworkStats['average_score'], 1) }}%</p>
                    </div>
                    <div class="bg-purple-200 rounded-full p-3">
                        <i class="ri-star-line text-purple-700 text-2xl"></i>
                    </div>
                </div>
            </div>
            @endif
        </div>

        <!-- Main Content Grid -->
        <div class="grid grid-cols-1 lg:grid-cols-3 gap-8">
            <!-- Left Column (2/3 width) -->
            <div class="lg:col-span-2 space-y-8">
                <!-- Pending Homework Section -->
                @if(isset($pendingHomework) && $pendingHomework->count() > 0)
                <div class="bg-white rounded-xl shadow-sm border border-gray-200">
                    <div class="border-b border-gray-200 p-6 flex items-center justify-between">
                        <h2 class="text-xl font-bold text-gray-900 flex items-center">
                            <i class="ri-alert-line text-yellow-600 ml-2"></i>
                            واجبات تحتاج إلى تسليم
                        </h2>
                        <a href="{{ route('student.homework.index') }}" class="text-sm text-blue-600 hover:text-blue-800">
                            عرض الكل
                            <i class="ri-arrow-left-line mr-1"></i>
                        </a>
                    </div>
                    <div class="divide-y divide-gray-200">
                        @foreach($pendingHomework as $homework)
                        <div class="p-6 hover:bg-gray-50 transition-colors">
                            <div class="flex items-start justify-between">
                                <div class="flex-1">
                                    <h3 class="text-lg font-semibold text-gray-900 mb-2">{{ $homework['title'] }}</h3>
                                    <div class="flex flex-wrap items-center gap-3 text-sm text-gray-600">
                                        <span class="inline-flex items-center px-2 py-1 rounded text-xs font-medium
                                            {{ $homework['type'] === 'academic' ? 'bg-purple-100 text-purple-800' :
                                               ($homework['type'] === 'quran' ? 'bg-green-100 text-green-800' :
                                               'bg-blue-100 text-blue-800') }}">
                                            {{ $homework['type'] === 'academic' ? 'أكاديمي' :
                                               ($homework['type'] === 'quran' ? 'قرآن' :
                                               'دورة تفاعلية') }}
                                        </span>
                                        @if($homework['due_date'])
                                        <span class="flex items-center">
                                            <i class="ri-calendar-line ml-1"></i>
                                            {{ $homework['due_date']->format('Y-m-d') }}
                                        </span>
                                        @endif
                                    </div>
                                </div>
                                <a href="{{ route('student.homework.submit', ['id' => $homework['homework_id'], 'type' => $homework['type']]) }}"
                                   class="mr-4 inline-flex items-center px-4 py-2 bg-blue-600 hover:bg-blue-700 text-white rounded-lg transition-colors text-sm">
                                    <i class="ri-upload-line ml-2"></i>
                                    تسليم
                                </a>
                            </div>
                        </div>
                        @endforeach
                    </div>
                </div>
                @endif

                <!-- In Progress Courses -->
                @if($inProgressCourses->count() > 0)
                <div class="bg-white rounded-xl shadow-sm border border-gray-200">
                    <div class="border-b border-gray-200 p-6">
                        <h2 class="text-xl font-bold text-gray-900 flex items-center">
                            <i class="ri-play-line text-blue-600 ml-2"></i>
                            دورات قيد التقدم
                        </h2>
                    </div>
                    <div class="p-6 space-y-4">
                        @foreach($inProgressCourses as $enrollment)
                        <div class="border border-gray-200 rounded-lg p-4 hover:border-blue-300 transition-colors">
                            <div class="flex items-start justify-between mb-3">
                                <div class="flex-1">
                                    <h3 class="font-semibold text-gray-900 mb-1">
                                        {{ $enrollment->recordedCourse->title ?? 'دورة' }}
                                    </h3>
                                    <p class="text-sm text-gray-600">
                                        {{ $enrollment->recordedCourse->category->name ?? '' }}
                                    </p>
                                </div>
                                <span class="text-sm font-semibold text-blue-600">
                                    {{ number_format($enrollment->progress_percentage, 1) }}%
                                </span>
                            </div>
                            <!-- Progress Bar -->
                            <div class="w-full bg-gray-200 rounded-full h-2 mb-3">
                                <div class="bg-blue-600 h-2 rounded-full transition-all"
                                     style="width: {{ $enrollment->progress_percentage }}%"></div>
                            </div>
                            <a href="{{ route('courses.show', $enrollment->recordedCourse->id) }}"
                               class="text-sm text-blue-600 hover:text-blue-800">
                                متابعة التعلم
                                <i class="ri-arrow-left-line mr-1"></i>
                            </a>
                        </div>
                        @endforeach
                    </div>
                </div>
                @endif

                <!-- Recent Activity -->
                @if($recentProgress->count() > 0)
                <div class="bg-white rounded-xl shadow-sm border border-gray-200">
                    <div class="border-b border-gray-200 p-6">
                        <h2 class="text-xl font-bold text-gray-900 flex items-center">
                            <i class="ri-history-line text-gray-600 ml-2"></i>
                            النشاط الأخير
                        </h2>
                    </div>
                    <div class="divide-y divide-gray-200">
                        @foreach($recentProgress->take(5) as $progress)
                        <div class="p-4 hover:bg-gray-50 transition-colors">
                            <div class="flex items-center gap-3">
                                <div class="bg-blue-100 rounded-full p-2">
                                    <i class="ri-play-circle-line text-blue-600"></i>
                                </div>
                                <div class="flex-1 min-w-0">
                                    <p class="text-sm font-medium text-gray-900 truncate">
                                        {{ $progress->lesson->title ?? '' }}
                                    </p>
                                    <p class="text-xs text-gray-600">
                                        {{ $progress->recordedCourse->title ?? '' }} •
                                        {{ $progress->last_accessed_at->diffForHumans() }}
                                    </p>
                                </div>
                            </div>
                        </div>
                        @endforeach
                    </div>
                </div>
                @endif
            </div>

            <!-- Right Column (1/3 width) -->
            <div class="space-y-8">
                <!-- Homework Statistics -->
                @if(isset($homeworkStats))
                <div class="bg-white rounded-xl shadow-sm border border-gray-200">
                    <div class="border-b border-gray-200 p-6">
                        <h2 class="text-lg font-bold text-gray-900 flex items-center">
                            <i class="ri-file-chart-line text-purple-600 ml-2"></i>
                            إحصائيات الواجبات
                        </h2>
                    </div>
                    <div class="p-6 space-y-4">
                        <div class="flex items-center justify-between">
                            <span class="text-sm text-gray-600">إجمالي الواجبات</span>
                            <span class="font-semibold text-gray-900">{{ $homeworkStats['total'] }}</span>
                        </div>
                        <div class="flex items-center justify-between">
                            <span class="text-sm text-gray-600">تم التسليم</span>
                            <span class="font-semibold text-green-600">{{ $homeworkStats['submitted'] }}</span>
                        </div>
                        <div class="flex items-center justify-between">
                            <span class="text-sm text-gray-600">تم التصحيح</span>
                            <span class="font-semibold text-blue-600">{{ $homeworkStats['graded'] }}</span>
                        </div>
                        <div class="flex items-center justify-between">
                            <span class="text-sm text-gray-600">معلق</span>
                            <span class="font-semibold text-yellow-600">{{ $homeworkStats['pending'] }}</span>
                        </div>
                        <div class="pt-4 border-t border-gray-200">
                            <div class="flex items-center justify-between mb-2">
                                <span class="text-sm text-gray-600">نسبة التسليم</span>
                                <span class="font-semibold text-gray-900">{{ number_format($homeworkStats['submission_rate'], 1) }}%</span>
                            </div>
                            <div class="w-full bg-gray-200 rounded-full h-2">
                                <div class="bg-green-600 h-2 rounded-full"
                                     style="width: {{ $homeworkStats['submission_rate'] }}%"></div>
                            </div>
                        </div>
                        <a href="{{ route('student.homework.index') }}"
                           class="block w-full text-center px-4 py-2 bg-purple-600 hover:bg-purple-700 text-white rounded-lg transition-colors text-sm mt-4">
                            عرض جميع الواجبات
                        </a>
                    </div>
                </div>
                @endif

                <!-- Achievements -->
                @if(isset($achievements) && count($achievements) > 0)
                <div class="bg-white rounded-xl shadow-sm border border-gray-200">
                    <div class="border-b border-gray-200 p-6">
                        <h2 class="text-lg font-bold text-gray-900 flex items-center">
                            <i class="ri-trophy-line text-yellow-600 ml-2"></i>
                            الإنجازات
                        </h2>
                    </div>
                    <div class="p-6 space-y-3">
                        @foreach($achievements as $achievement)
                        <div class="flex items-center gap-3 p-3 bg-yellow-50 rounded-lg">
                            <i class="ri-medal-line text-yellow-600 text-2xl"></i>
                            <div>
                                <p class="text-sm font-medium text-gray-900">{{ $achievement['title'] }}</p>
                                <p class="text-xs text-gray-600">{{ $achievement['description'] }}</p>
                            </div>
                        </div>
                        @endforeach
                    </div>
                </div>
                @endif

                <!-- Recommended Courses -->
                @if(isset($recommendedCourses) && $recommendedCourses->count() > 0)
                <div class="bg-white rounded-xl shadow-sm border border-gray-200">
                    <div class="border-b border-gray-200 p-6">
                        <h2 class="text-lg font-bold text-gray-900 flex items-center">
                            <i class="ri-lightbulb-line text-green-600 ml-2"></i>
                            دورات موصى بها
                        </h2>
                    </div>
                    <div class="p-6 space-y-3">
                        @foreach($recommendedCourses->take(3) as $course)
                        <a href="{{ route('courses.show', $course->id) }}"
                           class="block p-3 border border-gray-200 rounded-lg hover:border-blue-300 hover:bg-blue-50 transition-colors">
                            <p class="text-sm font-medium text-gray-900 mb-1">{{ $course->title }}</p>
                            <p class="text-xs text-gray-600">{{ $course->category->name ?? '' }}</p>
                        </a>
                        @endforeach
                    </div>
                </div>
                @endif
            </div>
        </div>
    </div>
</x-layouts.student-layout>
