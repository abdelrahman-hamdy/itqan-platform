<x-filament-panels::page>
    @php
        $currentAcademy = AcademyHelper::getCurrentAcademy();
    @endphp

    @if($currentAcademy)
        <!-- Academy Context Dashboard -->
        <div class="space-y-6">
            <!-- Academy Info Card -->
            <div class="bg-white dark:bg-gray-800 rounded-lg shadow p-6 max-w-4xl mx-auto">
                <div class="flex flex-col lg:flex-row items-start lg:items-center gap-4">
                    @if($currentAcademy->logo)
                        <img src="{{ $currentAcademy->logo }}" alt="{{ $currentAcademy->name }}" class="w-16 h-16 rounded-lg flex-shrink-0">
                    @else
                        <div class="w-16 h-16 bg-blue-500 rounded-lg flex items-center justify-center flex-shrink-0">
                            <span class="text-2xl text-white font-bold">{{ substr($currentAcademy->name, 0, 1) }}</span>
                        </div>
                    @endif
                    
                    <div class="flex-1 min-w-0">
                        <h2 class="text-xl lg:text-2xl font-bold text-gray-900 dark:text-white mb-2">{{ $currentAcademy->name }}</h2>
                        @if($currentAcademy->description)
                            <p class="text-gray-600 dark:text-gray-400 mb-2 line-clamp-2">{{ $currentAcademy->description }}</p>
                        @endif
                        <div class="flex flex-col sm:flex-row items-start sm:items-center gap-2 sm:gap-4 text-sm text-gray-500 dark:text-gray-400">
                            <span class="flex items-center gap-1">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 12a9 9 0 01-9 9m9-9a9 9 0 00-9-9m9 9H3m9 9v-9m0-9v9"></path>
                                </svg>
                                {{ $currentAcademy->subdomain }}.{{ config('app.domain', 'itqan-platform.test') }}
                            </span>
                            @if($currentAcademy->email)
                                <span class="flex items-center gap-1">
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 8l7.89 4.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"></path>
                                    </svg>
                                    {{ $currentAcademy->email }}
                                </span>
                            @endif
                        </div>
                    </div>
                    
                    <div class="flex items-center gap-2 flex-shrink-0">
                        <a href="{{ route('filament.admin.resources.recorded-courses.index') }}" 
                           class="inline-flex items-center px-4 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700 transition-colors text-sm">
                            <svg class="w-4 h-4 ms-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6"></path>
                            </svg>
                            إضافة دورة جديدة
                        </a>
                    </div>
                </div>
            </div>

            <!-- Quick Actions -->
            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
                <!-- Academic Management -->
                <div class="bg-gradient-to-r from-blue-500 to-blue-600 rounded-lg shadow-lg p-6 text-white">
                    <div class="flex items-center justify-between mb-4">
                        <h3 class="text-lg font-semibold">القسم الأكاديمي</h3>
                        <svg class="w-8 h-8" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6.253v13m0-13C10.832 5.477 9.246 5 7.5 5S4.168 5.477 3 6.253v13C4.168 18.477 5.754 18 7.5 18s3.332.477 4.5 1.253m0-13C13.168 5.477 14.754 5 16.5 5c1.746 0 3.332.477 4.5 1.253v13C19.832 18.477 18.246 18 16.5 18c-1.746 0-3.332.477-4.5 1.253"></path>
                        </svg>
                    </div>
                    <div class="space-y-2">
                        <a href="{{ route('filament.admin.resources.academic-teacher-profiles.index') }}" 
                           class="block text-blue-100 hover:text-white transition-colors">
                            إدارة المدرسين
                        </a>
                        <a href="{{ route('filament.admin.resources.academic-packages.index') }}" 
                           class="block text-blue-100 hover:text-white transition-colors">
                            الباقات الأكاديمية
                        </a>
                        <a href="{{ route('filament.admin.resources.recorded-courses.index') }}" 
                           class="block text-blue-100 hover:text-white transition-colors">
                            الدورات المسجلة
                        </a>
                        <a href="{{ route('filament.admin.resources.interactive-courses.index') }}" 
                           class="block text-blue-100 hover:text-white transition-colors">
                            الدورات التفاعلية
                        </a>
                    </div>
                </div>

                <!-- Quran Management -->
                <div class="bg-gradient-to-r from-green-500 to-green-600 rounded-lg shadow-lg p-6 text-white">
                    <div class="flex items-center justify-between mb-4">
                        <h3 class="text-lg font-semibold">قسم القرآن</h3>
                        <svg class="w-8 h-8" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path>
                        </svg>
                    </div>
                    <div class="space-y-2">
                        <a href="{{ route('filament.admin.resources.quran-circles.index') }}" 
                           class="block text-green-100 hover:text-white transition-colors">
                            حلقات القرآن
                        </a>
                        <a href="{{ route('filament.admin.resources.quran-packages.index') }}" 
                           class="block text-green-100 hover:text-white transition-colors">
                            باقات القرآن
                        </a>
                    </div>
                </div>

                <!-- User Management -->
                <div class="bg-gradient-to-r from-purple-500 to-purple-600 rounded-lg shadow-lg p-6 text-white">
                    <div class="flex items-center justify-between mb-4">
                        <h3 class="text-lg font-semibold">إدارة المستخدمين</h3>
                        <svg class="w-8 h-8" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197m13.5-9a2.5 2.5 0 11-5 0 2.5 2.5 0 015 0z"></path>
                        </svg>
                    </div>
                    <div class="space-y-2">
                        <a href="{{ route('filament.admin.resources.student-profiles.index') }}" 
                           class="block text-purple-100 hover:text-white transition-colors">
                            الطلاب
                        </a>
                        <a href="{{ route('filament.admin.resources.parent-profiles.index') }}" 
                           class="block text-purple-100 hover:text-white transition-colors">
                            أولياء الأمور
                        </a>
                        <a href="{{ route('filament.admin.resources.supervisor-profiles.index') }}" 
                           class="block text-purple-100 hover:text-white transition-colors">
                            المشرفين
                        </a>
                    </div>
                </div>
            </div>

            <!-- Recent Activity -->
            <div class="bg-white dark:bg-gray-800 rounded-lg shadow p-6">
                <h3 class="text-lg font-semibold text-gray-900 dark:text-white mb-4">النشاطات الأخيرة</h3>
                <div class="space-y-4">
                    @php
                        $recentCourses = RecordedCourse::where('academy_id', $currentAcademy->id)
                            ->latest()
                            ->take(5)
                            ->get();
                    @endphp
                    
                    @forelse($recentCourses as $course)
                        <div class="flex items-center gap-4 p-3 bg-gray-50 dark:bg-gray-700 rounded-lg">
                            <div class="w-10 h-10 bg-blue-100 dark:bg-blue-900 rounded-lg flex items-center justify-center">
                                <svg class="w-5 h-5 text-blue-600 dark:text-blue-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 10l4.553-2.276A1 1 0 0121 8.618v6.764a1 1 0 01-1.447.894L15 14M5 18h8a2 2 0 002-2V8a2 2 0 00-2-2H5a2 2 0 00-2 2v8a2 2 0 002 2z"></path>
                                </svg>
                            </div>
                            <div class="flex-1">
                                <p class="font-medium text-gray-900 dark:text-white">{{ $course->title_ar }}</p>
                                <p class="text-sm text-gray-500 dark:text-gray-400">{{ $course->created_at->diffForHumans() }}</p>
                            </div>
                            <span class="px-2 py-1 text-xs rounded-full {{ $course->is_published ? 'bg-green-100 text-green-800' : 'bg-yellow-100 text-yellow-800' }}">
                                {{ $course->is_published ? 'منشور' : 'مسودة' }}
                            </span>
                        </div>
                    @empty
                        <p class="text-gray-500 dark:text-gray-400 text-center py-4">لا توجد دورات حديثة</p>
                    @endforelse
                </div>
            </div>
        </div>
    @else
        <!-- Global Dashboard - Super Admin View -->
        <div class="space-y-6">
            <!-- Welcome Card -->
            <div class="bg-white dark:bg-gray-800 rounded-lg shadow p-6">
                <h2 class="text-2xl font-bold text-gray-900 dark:text-white mb-4">مرحباً بك في منصة إتقان</h2>
                <p class="text-gray-600 dark:text-gray-400">
                    اختر أكاديمية من القائمة أعلاه لإدارة محتواها أو استعرض الإحصائيات العامة للنظام.
                </p>
            </div>

            <!-- Render Filament Widgets for Super Admin -->
            @if(\App\Filament\Widgets\SuperAdminStatsWidget::canView())
                <div x-data="{ open: window.innerWidth >= 768 }"
                     x-on:resize.window="if (window.innerWidth >= 768) open = true">
                    <button x-on:click="open = !open"
                            class="md:hidden flex items-center justify-between w-full px-4 py-3 bg-white dark:bg-gray-800 rounded-lg shadow mb-2">
                        <span class="text-base font-semibold text-gray-900 dark:text-white">الإحصائيات العامة</span>
                        <svg class="w-5 h-5 text-gray-500 transition-transform duration-200"
                             :class="open && 'rotate-180'"
                             fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/>
                        </svg>
                    </button>
                    <div x-show="open" x-collapse>
                        @livewire(\App\Filament\Widgets\SuperAdminStatsWidget::class)
                    </div>
                </div>
            @endif

            @if(\App\Filament\Widgets\SuperAdminMonthlyStatsWidget::canView())
                <div x-data="{ open: window.innerWidth >= 768 }"
                     x-on:resize.window="if (window.innerWidth >= 768) open = true">
                    <button x-on:click="open = !open"
                            class="md:hidden flex items-center justify-between w-full px-4 py-3 bg-white dark:bg-gray-800 rounded-lg shadow mb-2">
                        <span class="text-base font-semibold text-gray-900 dark:text-white">إحصائيات هذا الشهر</span>
                        <svg class="w-5 h-5 text-gray-500 transition-transform duration-200"
                             :class="open && 'rotate-180'"
                             fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/>
                        </svg>
                    </button>
                    <div x-show="open" x-collapse>
                        @livewire(\App\Filament\Widgets\SuperAdminMonthlyStatsWidget::class)
                    </div>
                </div>
            @endif

            <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
                @if(\App\Filament\Widgets\UserAnalyticsChartWidget::canView())
                    @livewire(\App\Filament\Widgets\UserAnalyticsChartWidget::class)
                @endif
                @if(\App\Filament\Widgets\SessionAnalyticsChartWidget::canView())
                    @livewire(\App\Filament\Widgets\SessionAnalyticsChartWidget::class)
                @endif
            </div>

            @if(\App\Filament\Widgets\RecentBusinessRequestsWidget::canView())
                @livewire(\App\Filament\Widgets\RecentBusinessRequestsWidget::class)
            @endif
        </div>
    @endif
</x-filament-panels::page> 