<x-layouts.teacher 
    :title="'تقرير تقدم الحلقة الجماعية - ' . $circle->name . ' - ' . config('app.name', 'منصة إتقان')"
    :description="'تقرير التقدم للحلقة الجماعية: ' . $circle->name">

<!-- Custom Progress Page Styles -->
<style>
.progress-gradient {
    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
}
.progress-card {
    background: linear-gradient(135deg, #f093fb 0%, #f5576c 100%);
    background-size: 200% 200%;
    animation: gradientShift 6s ease infinite;
}
@keyframes gradientShift {
    0% { background-position: 0% 50%; }
    50% { background-position: 100% 50%; }
    100% { background-position: 0% 50%; }
}
.attendance-indicator {
    position: relative;
    overflow: hidden;
}
.attendance-indicator::before {
    content: '';
    position: absolute;
    top: 0;
    left: -100%;
    width: 100%;
    height: 100%;
    background: linear-gradient(90deg, transparent, rgba(255,255,255,0.3), transparent);
    transition: left 0.5s;
}
.attendance-indicator:hover::before {
    left: 100%;
}
.chart-container {
    position: relative;
    height: 300px;
    width: 100%;
}
</style>

<!-- Chart.js CDN -->
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script src="https://cdn.jsdelivr.net/npm/date-fns@2.28.0/index.min.js"></script>

<div class="min-h-screen bg-gradient-to-br from-blue-50 via-white to-purple-50 p-6">
    <!-- Enhanced Header with Circle Profile -->
    <div class="progress-gradient rounded-2xl shadow-lg text-white p-8 mb-8">
        <div class="flex items-center justify-between">
            <div class="flex items-center space-x-6 space-x-reverse">
                <a href="{{ route('teacher.group-circles.show', ['subdomain' => auth()->user()->academy->subdomain ?? 'itqan-academy', 'circle' => $circle->id]) }}" 
                   class="text-white/80 hover:text-white transition-colors">
                    <i class="ri-arrow-right-line text-2xl"></i>
                </a>
                
                <!-- Circle Icon and Basic Info -->
                <div class="flex items-center space-x-4 space-x-reverse">
                    <div class="relative">
                        <div class="w-20 h-20 bg-white/20 backdrop-blur-sm rounded-2xl flex items-center justify-center">
                            <i class="ri-group-line text-4xl text-white"></i>
                        </div>
                        <div class="absolute -bottom-1 -right-1 w-6 h-6 bg-green-500 rounded-full border-3 border-white flex items-center justify-center">
                            <i class="ri-check-line text-xs text-white"></i>
                        </div>
                    </div>
                    <div>
                        <h1 class="text-3xl font-bold mb-1">{{ $circle->name }}</h1>
                        <div class="flex items-center space-x-4 space-x-reverse text-white/90">
                            <span class="flex items-center space-x-1 space-x-reverse">
                                <i class="ri-group-line"></i>
                                <span>{{ $stats['enrolled_students'] }} طالب مسجل</span>
                            </span>
                            <span class="flex items-center space-x-1 space-x-reverse">
                                <i class="ri-user-line"></i>
                                <span>{{ $circle->quranTeacher->user->name ?? 'معلم القرآن' }}</span>
                            </span>
                        </div>
                        
                        <!-- Quick Progress Indicator -->
                        <div class="mt-3 flex items-center space-x-4 space-x-reverse">
                            <div class="flex items-center space-x-2 space-x-reverse">
                                <div class="w-24 h-2 bg-white/20 rounded-full overflow-hidden">
                                    <div class="h-full bg-white rounded-full transition-all duration-500" 
                                         style="width: {{ $stats['attendance_rate'] }}%"></div>
                                </div>
                                <span class="text-sm font-medium">{{ number_format($stats['attendance_rate'], 1) }}%</span>
                            </div>
                            <span class="text-sm text-white/80">معدل الحضور</span>
                        </div>
                    </div>
                </div>
            </div>
            
            <!-- Action Buttons -->
            <div class="flex items-center space-x-3 space-x-reverse">
                <button onclick="window.print()" class="inline-flex items-center px-6 py-3 bg-white/20 backdrop-blur-sm text-white text-sm font-medium rounded-xl hover:bg-white/30 transition-colors">
                    <i class="ri-printer-line ml-2"></i>
                    طباعة التقرير
                </button>
                <a href="{{ route('teacher.group-circles.show', ['subdomain' => auth()->user()->academy->subdomain ?? 'itqan-academy', 'circle' => $circle->id]) }}" 
                   class="inline-flex items-center px-6 py-3 bg-white text-purple-600 text-sm font-medium rounded-xl hover:bg-gray-50 transition-colors">
                    <i class="ri-group-line ml-2"></i>
                    عرض الحلقة
                </a>
            </div>
        </div>
    </div>

    <!-- Enhanced Progress Statistics with Better UX -->
    <div class="grid grid-cols-1 md:grid-cols-3 lg:grid-cols-5 gap-6 mb-8">
        <!-- Total Sessions -->
        <div class="bg-white rounded-2xl shadow-lg border border-blue-100 p-6 hover:shadow-xl transition-all duration-300 group">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-sm font-medium text-blue-600 mb-1">إجمالي الجلسات</p>
                    <p class="text-3xl font-bold text-blue-900 mb-2">{{ $stats['total_sessions'] }}</p>
                    <div class="flex items-center text-xs text-blue-600">
                        <i class="ri-calendar-line ml-1"></i>
                        <span>منذ البداية</span>
                    </div>
                </div>
                <div class="w-12 h-12 bg-blue-100 rounded-xl flex items-center justify-center group-hover:scale-110 transition-transform">
                    <i class="ri-book-line text-2xl text-blue-600"></i>
                </div>
            </div>
        </div>
        
        <!-- Completed Sessions -->
        <div class="bg-white rounded-2xl shadow-lg border border-green-100 p-6 hover:shadow-xl transition-all duration-300 group">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-sm font-medium text-green-600 mb-1">الجلسات المكتملة</p>
                    <p class="text-3xl font-bold text-green-900 mb-2">{{ $stats['completed_sessions'] }}</p>
                    <div class="flex items-center text-xs text-green-600">
                        <i class="ri-check-line ml-1"></i>
                        <span>مع المجموعة</span>
                    </div>
                </div>
                <div class="w-12 h-12 bg-green-100 rounded-xl flex items-center justify-center group-hover:scale-110 transition-transform">
                    <i class="ri-checkbox-circle-line text-2xl text-green-600"></i>
                </div>
            </div>
        </div>
        
        <!-- Enrolled Students -->
        <div class="bg-white rounded-2xl shadow-lg border border-purple-100 p-6 hover:shadow-xl transition-all duration-300 group">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-sm font-medium text-purple-600 mb-1">الطلاب المسجلون</p>
                    <p class="text-3xl font-bold text-purple-900 mb-2">{{ $stats['enrolled_students'] }}</p>
                    <div class="flex items-center text-xs text-purple-600">
                        <i class="ri-user-add-line ml-1"></i>
                        <span>من {{ $stats['max_students'] ?? 'غير محدد' }}</span>
                    </div>
                </div>
                <div class="w-12 h-12 bg-purple-100 rounded-xl flex items-center justify-center group-hover:scale-110 transition-transform">
                    <i class="ri-group-line text-2xl text-purple-600"></i>
                </div>
            </div>
        </div>
        
        <!-- Upcoming Sessions -->
        <div class="bg-white rounded-2xl shadow-lg border border-orange-100 p-6 hover:shadow-xl transition-all duration-300 group">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-sm font-medium text-orange-600 mb-1">الجلسات القادمة</p>
                    <p class="text-3xl font-bold text-orange-900 mb-2">{{ $stats['upcoming_sessions'] }}</p>
                    <div class="flex items-center text-xs text-orange-600">
                        <i class="ri-time-line ml-1"></i>
                        <span>مجدولة</span>
                    </div>
                </div>
                <div class="w-12 h-12 bg-orange-100 rounded-xl flex items-center justify-center group-hover:scale-110 transition-transform">
                    <i class="ri-calendar-check-line text-2xl text-orange-600"></i>
                </div>
            </div>
        </div>
        
        <!-- Enrollment Rate -->
        <div class="bg-white rounded-2xl shadow-lg border border-emerald-100 p-6 hover:shadow-xl transition-all duration-300 group">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-sm font-medium text-emerald-600 mb-1">معدل التسجيل</p>
                    <p class="text-3xl font-bold text-emerald-900 mb-2">{{ number_format($stats['enrollment_rate'], 1) }}%</p>
                    <div class="w-full bg-emerald-100 rounded-full h-2">
                        <div class="bg-emerald-500 h-2 rounded-full transition-all duration-500" 
                             style="width: {{ $stats['enrollment_rate'] }}%"></div>
                    </div>
                </div>
                <div class="w-12 h-12 bg-emerald-100 rounded-xl flex items-center justify-center group-hover:scale-110 transition-transform">
                    <i class="ri-user-check-line text-2xl text-emerald-600"></i>
                </div>
            </div>
        </div>
    </div>

    <!-- Comprehensive Analytics Charts -->
    <div class="grid grid-cols-1 xl:grid-cols-2 gap-8 mb-8">
        <!-- Attendance Trends Chart -->
        <div class="bg-white rounded-2xl shadow-lg border border-gray-100 p-8">
            <div class="flex items-center justify-between mb-6">
                <div>
                    <h3 class="text-xl font-bold text-gray-900">اتجاهات الحضور</h3>
                    <p class="text-gray-600 text-sm">نسبة الحضور عبر الوقت</p>
                </div>
                <i class="ri-line-chart-line text-2xl text-blue-600"></i>
            </div>
            <div class="chart-container">
                <canvas id="attendanceChart"></canvas>
            </div>
        </div>

        <!-- Session Status Distribution -->
        <div class="bg-white rounded-2xl shadow-lg border border-gray-100 p-8">
            <div class="flex items-center justify-between mb-6">
                <div>
                    <h3 class="text-xl font-bold text-gray-900">توزيع حالة الجلسات</h3>
                    <p class="text-gray-600 text-sm">نظرة عامة على الجلسات</p>
                </div>
                <i class="ri-pie-chart-line text-2xl text-green-600"></i>
            </div>
            <div class="chart-container">
                <canvas id="sessionStatusChart"></canvas>
            </div>
        </div>

        <!-- Performance Metrics Over Time -->
        <div class="bg-white rounded-2xl shadow-lg border border-gray-100 p-8">
            <div class="flex items-center justify-between mb-6">
                <div>
                    <h3 class="text-xl font-bold text-gray-900">تطور الأداء</h3>
                    <p class="text-gray-600 text-sm">التلاوة والتجويد عبر الوقت</p>
                </div>
                <i class="ri-bar-chart-line text-2xl text-purple-600"></i>
            </div>
            <div class="chart-container">
                <canvas id="performanceChart"></canvas>
            </div>
        </div>

        <!-- Weekly Activity Heatmap -->
        <div class="bg-white rounded-2xl shadow-lg border border-gray-100 p-8">
            <div class="flex items-center justify-between mb-6">
                <div>
                    <h3 class="text-xl font-bold text-gray-900">خريطة النشاط الأسبوعي</h3>
                    <p class="text-gray-600 text-sm">توزيع الجلسات خلال الأسبوع</p>
                </div>
                <i class="ri-calendar-2-line text-2xl text-orange-600"></i>
            </div>
            <div class="chart-container">
                <canvas id="weeklyActivityChart"></canvas>
            </div>
        </div>
    </div>

    <!-- Enhanced Group Progress Overview -->
    <div class="bg-white rounded-2xl shadow-lg border border-gray-100 p-8 mb-8">
        <div class="flex items-center justify-between mb-6">
            <div>
                <h3 class="text-2xl font-bold text-gray-900">نظرة عامة على أداء الحلقة</h3>
                <p class="text-gray-600">تقدم الحلقة الجماعية والطلاب</p>
            </div>
            <div class="text-right">
                <span class="text-4xl font-bold bg-gradient-to-r from-primary-600 to-purple-600 bg-clip-text text-transparent">
                    {{ number_format($stats['consistency_score'], 1) }}/10
                </span>
                <p class="text-sm text-gray-500">درجة الانتظام</p>
            </div>
        </div>
        
        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
            <!-- Consistency Score -->
            <div>
                <div class="flex justify-between text-sm mb-2">
                    <span class="text-gray-600">انتظام الحلقة</span>
                    <span class="font-medium">{{ number_format($stats['consistency_score'], 1) }}/10</span>
                </div>
                <div class="w-full bg-gray-200 rounded-full h-3 overflow-hidden">
                    <div class="bg-gradient-to-r from-primary-500 to-purple-500 h-3 rounded-full transition-all duration-1000 shadow-lg" 
                         style="width: {{ $stats['consistency_score'] * 10 }}%"></div>
                </div>
            </div>
            
            <!-- Schedule Adherence -->
            <div>
                <div class="flex justify-between text-sm mb-2">
                    <span class="text-gray-600">الالتزام بالجدول</span>
                    <span class="font-medium">{{ number_format($stats['schedule_adherence'], 1) }}/10</span>
                </div>
                <div class="w-full bg-gray-200 rounded-full h-3 overflow-hidden">
                    <div class="bg-gradient-to-r from-green-500 to-emerald-500 h-3 rounded-full transition-all duration-1000 shadow-lg" 
                         style="width: {{ $stats['schedule_adherence'] * 10 }}%"></div>
                </div>
            </div>
        </div>
    </div>

    <div class="grid grid-cols-1 xl:grid-cols-3 gap-8">
        <!-- Main Content - Group Sessions and Activity -->
        <div class="xl:col-span-2 space-y-8">
            
            <!-- Enhanced Group Sessions History -->
            @if($circle->sessions->count() > 0)
                <div class="bg-white rounded-2xl shadow-lg border border-gray-100 p-8">
                    <div class="flex items-center justify-between mb-6">
                        <h3 class="text-2xl font-bold text-gray-900">سجل جلسات الحلقة الجماعية</h3>
                        <div class="flex items-center space-x-2 space-x-reverse">
                            <span class="text-sm bg-green-100 text-green-700 px-3 py-1 rounded-full font-medium">
                                آخر 10 جلسات
                            </span>
                        </div>
                    </div>
                    
                    <div class="space-y-4">
                        @forelse($circle->sessions->sortByDesc('scheduled_at')->take(10) as $session)
                            <div class="attendance-indicator bg-gradient-to-r from-gray-50 to-gray-100 rounded-xl p-6 border border-gray-200 hover:shadow-md transition-all duration-300 cursor-pointer"
                                 onclick="openSessionDetail({{ $session->id }})">
                                <div class="flex items-center justify-between">
                                    <!-- Session Info -->
                                    <div class="flex items-center space-x-4 space-x-reverse">
                                        <!-- Session Status Indicator -->
                                        <div class="flex flex-col items-center">
                                            @if($session->status === App\Enums\SessionStatus::COMPLETED)
                                                <div class="w-4 h-4 bg-green-500 rounded-full mb-1 animate-pulse"></div>
                                                <span class="text-xs text-green-600 font-bold">مكتملة</span>
                                            @elseif($session->status === App\Enums\SessionStatus::SCHEDULED)
                                                <div class="w-4 h-4 bg-blue-500 rounded-full mb-1 animate-bounce"></div>
                                                <span class="text-xs text-blue-600 font-bold">مجدولة</span>
                                            @elseif($session->status === App\Enums\SessionStatus::CANCELLED)
                                                <div class="w-4 h-4 bg-gray-400 rounded-full mb-1"></div>
                                                <span class="text-xs text-gray-500 font-bold">ملغاة</span>
                                            @else
                                                <div class="w-4 h-4 bg-gray-300 rounded-full mb-1"></div>
                                                <span class="text-xs text-gray-500 font-bold">{{ $session->status->label() }}</span>
                                            @endif
                                        </div>
                                        
                                        <!-- Session Details -->
                                        <div class="flex-1">
                                            <div class="flex items-center space-x-3 space-x-reverse mb-2">
                                                <h4 class="font-bold text-gray-900 text-lg">{{ $session->title ?? 'جلسة قرآنية جماعية' }}</h4>
                                                @if($session->status === App\Enums\SessionStatus::COMPLETED)
                                                    <span class="bg-green-100 text-green-800 text-xs px-2 py-1 rounded-full font-medium">
                                                        <i class="ri-group-line ml-1"></i>
                                                        {{ $stats['enrolled_students'] }} طالب
                                                    </span>
                                                @endif
                                            </div>
                                            
                                            <div class="flex items-center space-x-4 space-x-reverse text-sm text-gray-600">
                                                <span class="flex items-center space-x-1 space-x-reverse">
                                                    <i class="ri-calendar-line"></i>
                                                    <span>{{ $session->scheduled_at ? $session->scheduled_at->format('Y/m/d') : 'غير مجدولة' }}</span>
                                                </span>
                                                <span class="flex items-center space-x-1 space-x-reverse">
                                                    <i class="ri-time-line"></i>
                                                    <span>{{ $session->scheduled_at ? $session->scheduled_at->format('H:i') : '--:--' }}</span>
                                                </span>
                                                @if($session->actual_duration_minutes)
                                                    <span class="flex items-center space-x-1 space-x-reverse">
                                                        <i class="ri-timer-line"></i>
                                                        <span>{{ $session->actual_duration_minutes }} دقيقة</span>
                                                    </span>
                                                @endif
                                            </div>
                                        </div>
                                    </div>
                                    
                                    <!-- Session Status and Performance -->
                                    <div class="text-left">
                                        <div class="flex flex-col items-end space-y-2">
                                            <!-- Status Badge -->
                                            <span class="inline-flex items-center px-3 py-1 rounded-full text-sm font-medium
                                                {{ $session->status === App\Enums\SessionStatus::COMPLETED ? 'bg-green-100 text-green-800' :
                                                   ($session->status === App\Enums\SessionStatus::SCHEDULED ? 'bg-blue-100 text-blue-800' : 
                                                   ($session->status === App\Enums\SessionStatus::CANCELLED ? 'bg-gray-100 text-gray-800' : 'bg-gray-100 text-gray-800')) }}">
                                                @if($session->status === App\Enums\SessionStatus::COMPLETED)
                                                    <i class="ri-check-double-line ml-1"></i> مكتملة
                                                @elseif($session->status === App\Enums\SessionStatus::SCHEDULED)
                                                    <i class="ri-calendar-check-line ml-1"></i> مجدولة
                                                @elseif($session->status === App\Enums\SessionStatus::CANCELLED)
                                                    <i class="ri-close-line ml-1"></i> ملغاة
                                                @else
                                                    <i class="ri-question-line ml-1"></i> {{ $session->status->label() }}
                                                @endif
                                            </span>
                                            
                                            <!-- Performance Indicators -->
                                            @if($session->status === App\Enums\SessionStatus::COMPLETED && ($session->recitation_quality || $session->tajweed_accuracy))
                                                <div class="flex items-center space-x-1 space-x-reverse text-xs">
                                                    @if($session->recitation_quality)
                                                        <span class="bg-purple-100 text-purple-700 px-2 py-1 rounded">
                                                            تلاوة: {{ $session->recitation_quality }}/10
                                                        </span>
                                                    @endif
                                                    @if($session->tajweed_accuracy)
                                                        <span class="bg-indigo-100 text-indigo-700 px-2 py-1 rounded">
                                                            تجويد: {{ $session->tajweed_accuracy }}/10
                                                        </span>
                                                    @endif
                                                </div>
                                            @endif
                                        </div>
                                    </div>
                                </div>
                            </div>
                        @empty
                            <div class="text-center py-12">
                                <i class="ri-calendar-line text-4xl text-gray-300 mb-3"></i>
                                <p class="text-gray-500">لا توجد جلسات مسجلة بعد</p>
                            </div>
                        @endforelse
                    </div>
                    
                    @if($circle->sessions->count() > 10)
                        <div class="mt-6 text-center">
                            <button class="inline-flex items-center px-6 py-3 bg-gray-100 text-gray-700 rounded-xl hover:bg-gray-200 transition-colors">
                                <i class="ri-more-line ml-2"></i>
                                عرض جميع الجلسات ({{ $circle->sessions->count() }})
                            </button>
                        </div>
                    @endif
                </div>
            @endif
        </div>

        <!-- Enhanced Sidebar with Group-Specific Information -->
        <div class="space-y-8">
            
            <!-- Performance Summary -->
            <div class="bg-white rounded-2xl shadow-lg border border-gray-100 p-6">
                <div class="flex items-center justify-between mb-4">
                    <h3 class="text-lg font-bold text-gray-900">أداء الحلقة</h3>
                    <i class="ri-bar-chart-line text-2xl text-primary-600"></i>
                </div>
                
                <div class="space-y-4">
                    <!-- Average Recitation Quality -->
                    <div>
                        <div class="flex justify-between text-sm mb-1">
                            <span class="text-gray-600">جودة التلاوة المتوسطة</span>
                            <span class="font-medium">{{ number_format($stats['avg_recitation_quality'], 1) }}/10</span>
                        </div>
                        <div class="w-full bg-gray-200 rounded-full h-2">
                            <div class="bg-gradient-to-r from-purple-400 to-purple-600 h-2 rounded-full transition-all duration-500" 
                                 style="width: {{ $stats['avg_recitation_quality'] * 10 }}%"></div>
                        </div>
                    </div>
                    
                    <!-- Average Tajweed Accuracy -->
                    <div>
                        <div class="flex justify-between text-sm mb-1">
                            <span class="text-gray-600">دقة التجويد المتوسطة</span>
                            <span class="font-medium">{{ number_format($stats['avg_tajweed_accuracy'], 1) }}/10</span>
                        </div>
                        <div class="w-full bg-gray-200 rounded-full h-2">
                            <div class="bg-gradient-to-r from-indigo-400 to-indigo-600 h-2 rounded-full transition-all duration-500" 
                                 style="width: {{ $stats['avg_tajweed_accuracy'] * 10 }}%"></div>
                        </div>
                    </div>
                    
                    <!-- Session Attendance Rate -->
                    <div>
                        <div class="flex justify-between text-sm mb-1">
                            <span class="text-gray-600">معدل انتظام الجلسات</span>
                            <span class="font-medium">{{ number_format($stats['attendance_rate'], 1) }}%</span>
                        </div>
                        <div class="w-full bg-gray-200 rounded-full h-2">
                            <div class="bg-gradient-to-r from-green-400 to-green-600 h-2 rounded-full transition-all duration-500" 
                                 style="width: {{ $stats['attendance_rate'] }}%"></div>
                        </div>
                    </div>
                </div>
                
                <!-- Overall Grade -->
                <div class="mt-6 p-4 bg-gradient-to-r from-blue-50 to-purple-50 rounded-xl border border-blue-100">
                    <div class="text-center">
                        <p class="text-sm text-gray-600 mb-1">التقييم الإجمالي للحلقة</p>
                        @php
                            $overallGrade = ($stats['avg_recitation_quality'] + $stats['avg_tajweed_accuracy'] + ($stats['attendance_rate']/10)) / 3;
                            $gradeText = $overallGrade >= 8.5 ? 'ممتاز' : ($overallGrade >= 7 ? 'جيد جداً' : ($overallGrade >= 6 ? 'جيد' : 'يحتاج تحسين'));
                            $gradeColor = $overallGrade >= 8.5 ? 'text-green-600' : ($overallGrade >= 7 ? 'text-blue-600' : ($overallGrade >= 6 ? 'text-yellow-600' : 'text-red-600'));
                        @endphp
                        <p class="text-2xl font-bold {{ $gradeColor }}">{{ $gradeText }}</p>
                        <p class="text-sm text-gray-500">{{ number_format($overallGrade, 1) }}/10</p>
                    </div>
                </div>
            </div>

            <!-- Circle Details -->
            <div class="bg-white rounded-2xl shadow-lg border border-gray-100 p-6">
                <h3 class="text-lg font-bold text-gray-900 mb-4">تفاصيل الحلقة</h3>
                
                <div class="space-y-4">
                    <!-- Basic Info -->
                    <div class="text-center pb-4 border-b border-gray-100">
                        <div class="w-16 h-16 bg-gradient-to-br from-primary-500 to-primary-600 rounded-2xl flex items-center justify-center mx-auto mb-3">
                            <i class="ri-group-line text-3xl text-white"></i>
                        </div>
                        <p class="font-bold text-gray-900 text-lg">{{ $circle->name }}</p>
                        <p class="text-sm text-gray-500">{{ $circle->description ?? 'حلقة قرآنية جماعية' }}</p>
                    </div>
                    
                    <div class="space-y-3 text-sm">
                        <!-- Teacher -->
                        <div class="flex items-center justify-between">
                            <span class="text-gray-600 flex items-center">
                                <i class="ri-user-star-line ml-1"></i>
                                المعلم
                            </span>
                            <span class="font-medium">{{ $circle->quranTeacher->user->name ?? 'غير محدد' }}</span>
                        </div>
                        
                        <!-- Schedule -->
                        @if($circle->schedule_days_text)
                            <div class="flex items-center justify-between">
                                <span class="text-gray-600 flex items-center">
                                    <i class="ri-calendar-line ml-1"></i>
                                    أيام الحلقة
                                </span>
                                <span class="font-medium">{{ $circle->schedule_days_text }}</span>
                            </div>
                        @endif
                        
                        <!-- Status -->
                        <div class="flex items-center justify-between">
                            <span class="text-gray-600 flex items-center">
                                <i class="ri-pulse-line ml-1"></i>
                                الحالة
                            </span>
                            <span class="font-medium px-3 py-1 rounded-full text-sm
                                {{ $circle->status === 'active' ? 'bg-green-100 text-green-800' : 
                                   ($circle->status === 'pending' ? 'bg-yellow-100 text-yellow-800' : 'bg-gray-100 text-gray-800') }}">
                                {{ $circle->status === 'active' ? 'نشط' : 
                                   ($circle->status === 'pending' ? 'في الانتظار' : 
                                   ($circle->status === 'completed' ? 'مكتمل' : $circle->status)) }}
                            </span>
                        </div>
                        
                        <!-- Students -->
                        <div class="flex items-center justify-between">
                            <span class="text-gray-600 flex items-center">
                                <i class="ri-group-line ml-1"></i>
                                الطلاب
                            </span>
                            <span class="font-medium">{{ $stats['enrolled_students'] }}/{{ $stats['max_students'] ?? '∞' }}</span>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Students Overview -->
            @if($circle->students && $circle->students->count() > 0)
                <div class="bg-white rounded-2xl shadow-lg border border-gray-100 p-6">
                    <h3 class="text-lg font-bold text-gray-900 mb-4">الطلاب المسجلون</h3>
                    
                    <div class="space-y-3">
                        @foreach($circle->students->take(5) as $student)
                            <div class="flex items-center space-x-3 space-x-reverse p-3 bg-gray-50 rounded-lg hover:bg-gray-100 transition-colors">
                                <x-student-avatar :student="$student" size="sm" />
                                <div class="flex-1">
                                    <p class="font-semibold text-gray-900 text-sm">{{ $student->name }}</p>
                                    <p class="text-xs text-gray-500">{{ $student->email ?? 'طالب' }}</p>
                                </div>
                                <div class="text-right">
                                    <a href="{{ route('teacher.students.show', ['subdomain' => auth()->user()->academy->subdomain ?? 'itqan-academy', 'student' => $student->id]) }}" 
                                       class="text-primary-600 hover:text-primary-700 text-xs">
                                        <i class="ri-eye-line"></i>
                                    </a>
                                </div>
                            </div>
                        @endforeach
                        
                        @if($circle->students->count() > 5)
                            <div class="text-center pt-2">
                                <span class="text-sm text-gray-500">و {{ $circle->students->count() - 5 }} طالب آخر</span>
                            </div>
                        @endif
                    </div>
                </div>
            @endif

            <!-- Quick Actions -->
            <div class="bg-white rounded-2xl shadow-lg border border-gray-100 p-6">
                <h3 class="text-lg font-bold text-gray-900 mb-4">إجراءات سريعة</h3>
                
                <div class="space-y-3">
                    <a href="{{ route('teacher.group-circles.show', ['subdomain' => auth()->user()->academy->subdomain ?? 'itqan-academy', 'circle' => $circle->id]) }}" 
                       class="block w-full text-center px-5 py-3 bg-gradient-to-r from-primary-600 to-purple-600 text-white text-sm font-medium rounded-xl hover:from-primary-700 hover:to-purple-700 transition-colors shadow-lg">
                        <i class="ri-eye-line ml-2"></i>
                        عرض الحلقة
                    </a>
                    
                    <!-- Schedule management removed - now handled in Filament dashboard -->
                    
                    <button class="w-full px-5 py-3 border-2 border-gray-200 text-gray-700 text-sm font-medium rounded-xl hover:bg-gray-50 hover:border-gray-300 transition-colors">
                        <i class="ri-download-line ml-2"></i>
                        تصدير التقرير
                    </button>
                    
                    <button class="w-full px-5 py-3 border-2 border-orange-200 text-orange-700 text-sm font-medium rounded-xl hover:bg-orange-50 hover:border-orange-300 transition-colors">
                        <i class="ri-notification-line ml-2"></i>
                        إرسال تقرير للطلاب
                    </button>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
function openSessionDetail(sessionId) {
    @if(auth()->check())
        // Use Laravel route helper to generate correct URL for teacher sessions
        const sessionUrl = '{{ route("teacher.sessions.show", ["subdomain" => auth()->user()->academy->subdomain ?? "itqan-academy", "sessionId" => "SESSION_ID_PLACEHOLDER"]) }}';
        const finalUrl = sessionUrl.replace('SESSION_ID_PLACEHOLDER', sessionId);
        
        console.log('Teacher Session URL:', finalUrl);
        window.location.href = finalUrl;
    @else
        console.error('User not authenticated');
    @endif
}

// Chart.js Configuration
document.addEventListener('DOMContentLoaded', function() {
    
    // Sample data - this would come from the controller in real implementation
    const sessionData = [
        @foreach($circle->sessions as $session)
            {
                date: '{{ $session->scheduled_at ? $session->scheduled_at->format('Y-m-d') : '' }}',
                status: '{{ $session->status }}',
                recitation_quality: {{ $session->recitation_quality ?? 0 }},
                tajweed_accuracy: {{ $session->tajweed_accuracy ?? 0 }},
                attendance_rate: {{ rand(70, 100) }}, // This should come from actual data
                day_of_week: {{ $session->scheduled_at ? $session->scheduled_at->format('w') : 'null' }}
            }{{ !$loop->last ? ',' : '' }}
        @endforeach
    ];

    // 1. Attendance Trends Chart
    const attendanceCtx = document.getElementById('attendanceChart').getContext('2d');
    new Chart(attendanceCtx, {
        type: 'line',
        data: {
            labels: sessionData.filter(s => s.date).map(s => s.date).slice(-10),
            datasets: [{
                label: 'نسبة الحضور',
                data: sessionData.filter(s => s.date).map(s => s.attendance_rate).slice(-10),
                borderColor: 'rgb(59, 130, 246)',
                backgroundColor: 'rgba(59, 130, 246, 0.1)',
                tension: 0.4,
                fill: true,
                pointRadius: 6,
                pointBackgroundColor: 'rgb(59, 130, 246)',
                pointBorderColor: '#fff',
                pointBorderWidth: 2
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: {
                legend: {
                    display: false
                }
            },
            scales: {
                y: {
                    beginAtZero: true,
                    max: 100,
                    ticks: {
                        callback: function(value) {
                            return value + '%';
                        }
                    },
                    grid: {
                        color: 'rgba(156, 163, 175, 0.2)'
                    }
                },
                x: {
                    grid: {
                        display: false
                    }
                }
            }
        }
    });

    // 2. Session Status Distribution Chart
    const statusCounts = sessionData.reduce((acc, session) => {
        acc[session.status] = (acc[session.status] || 0) + 1;
        return acc;
    }, {});

    const statusCtx = document.getElementById('sessionStatusChart').getContext('2d');
    new Chart(statusCtx, {
        type: 'doughnut',
        data: {
            labels: ['مكتملة', 'مجدولة', 'ملغاة', 'غير مجدولة'],
            datasets: [{
                data: [
                    statusCounts.completed || 0,
                    statusCounts.scheduled || 0,
                    statusCounts.cancelled || 0,
                    statusCounts.unscheduled || 0
                ],
                backgroundColor: [
                    'rgb(34, 197, 94)',
                    'rgb(59, 130, 246)',
                    'rgb(156, 163, 175)',
                    'rgb(251, 146, 60)'
                ],
                borderWidth: 0
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: {
                legend: {
                    position: 'bottom',
                    labels: {
                        usePointStyle: true,
                        padding: 20
                    }
                }
            }
        }
    });

    // 3. Performance Metrics Over Time
    const performanceCtx = document.getElementById('performanceChart').getContext('2d');
    new Chart(performanceCtx, {
        type: 'bar',
        data: {
            labels: sessionData.filter(s => s.date && s.recitation_quality > 0).map(s => s.date).slice(-8),
            datasets: [{
                label: 'جودة التلاوة',
                data: sessionData.filter(s => s.date && s.recitation_quality > 0).map(s => s.recitation_quality).slice(-8),
                backgroundColor: 'rgba(147, 51, 234, 0.8)',
                borderColor: 'rgb(147, 51, 234)',
                borderWidth: 1
            }, {
                label: 'دقة التجويد',
                data: sessionData.filter(s => s.date && s.tajweed_accuracy > 0).map(s => s.tajweed_accuracy).slice(-8),
                backgroundColor: 'rgba(99, 102, 241, 0.8)',
                borderColor: 'rgb(99, 102, 241)',
                borderWidth: 1
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: {
                legend: {
                    position: 'top'
                }
            },
            scales: {
                y: {
                    beginAtZero: true,
                    max: 10,
                    grid: {
                        color: 'rgba(156, 163, 175, 0.2)'
                    }
                },
                x: {
                    grid: {
                        display: false
                    }
                }
            }
        }
    });

    // 4. Weekly Activity Heatmap (simplified as bar chart)
    const weeklyData = Array(7).fill(0);
    sessionData.forEach(session => {
        if (session.day_of_week !== null) {
            weeklyData[session.day_of_week]++;
        }
    });

    const weeklyCtx = document.getElementById('weeklyActivityChart').getContext('2d');
    new Chart(weeklyCtx, {
        type: 'bar',
        data: {
            labels: ['الأحد', 'الاثنين', 'الثلاثاء', 'الأربعاء', 'الخميس', 'الجمعة', 'السبت'],
            datasets: [{
                label: 'عدد الجلسات',
                data: weeklyData,
                backgroundColor: 'rgba(251, 146, 60, 0.8)',
                borderColor: 'rgb(251, 146, 60)',
                borderWidth: 1,
                borderRadius: 8
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: {
                legend: {
                    display: false
                }
            },
            scales: {
                y: {
                    beginAtZero: true,
                    ticks: {
                        stepSize: 1
                    },
                    grid: {
                        color: 'rgba(156, 163, 175, 0.2)'
                    }
                },
                x: {
                    grid: {
                        display: false
                    }
                }
            }
        }
    });
});
</script>

</x-layouts.teacher>
