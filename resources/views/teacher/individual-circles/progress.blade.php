<x-layouts.teacher 
    :title="'تقرير التقدم - ' . $circle->student->name . ' - ' . config('app.name', 'منصة إتقان')"
    :description="'تقرير التقدم للطالب: ' . $circle->student->name">

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
</style>

<div class="min-h-screen bg-gradient-to-br from-blue-50 via-white to-purple-50 p-6">
    <!-- Enhanced Header with Student Profile -->
    <div class="progress-gradient rounded-2xl shadow-lg text-white p-8 mb-8">
        <div class="flex items-center justify-between">
            <div class="flex items-center space-x-6 space-x-reverse">
                <a href="{{ route('teacher.individual-circles.show', ['subdomain' => auth()->user()->academy->subdomain ?? 'itqan-academy', 'circle' => $circle->id]) }}" 
                   class="text-white/80 hover:text-white transition-colors">
                    <i class="ri-arrow-right-line text-2xl"></i>
                </a>
                
                <!-- Student Avatar and Basic Info -->
                <div class="flex items-center space-x-4 space-x-reverse">
                    <div class="relative">
                        <x-student-avatar :student="$circle->student" size="xl" />
                        <div class="absolute -bottom-1 -right-1 w-6 h-6 bg-green-500 rounded-full border-3 border-white flex items-center justify-center">
                            <i class="ri-user-star-line text-xs text-white"></i>
                        </div>
                    </div>
                    <div>
                        <h1 class="text-3xl font-bold mb-1">{{ $circle->student->name }}</h1>
                        <div class="flex items-center space-x-4 space-x-reverse text-white/90">
                            <span class="flex items-center space-x-1 space-x-reverse">
                                <i class="ri-book-open-line"></i>
                                <span>{{ $circle->subscription->package->name ?? 'اشتراك مخصص' }}</span>
                            </span>
                            @if($circle->student->studentProfile?->student_code)
                                <span class="flex items-center space-x-1 space-x-reverse">
                                    <i class="ri-hashtag"></i>
                                    <span>{{ $circle->student->studentProfile->student_code }}</span>
                                </span>
                            @endif
                        </div>
                        
                        <!-- Quick Progress Indicator -->
                        <div class="mt-3 flex items-center space-x-4 space-x-reverse">
                            <div class="flex items-center space-x-2 space-x-reverse">
                                <div class="w-24 h-2 bg-white/20 rounded-full overflow-hidden">
                                    <div class="h-full bg-white rounded-full transition-all duration-500" 
                                         style="width: {{ $stats['progress_percentage'] }}%"></div>
                                </div>
                                <span class="text-sm font-medium">{{ number_format($stats['progress_percentage'], 1) }}%</span>
                            </div>
                            <span class="text-sm text-white/80">التقدم الإجمالي</span>
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
                <a href="{{ route('teacher.students.show', ['subdomain' => auth()->user()->academy->subdomain ?? 'itqan-academy', 'student' => $circle->student->id]) }}" 
                   class="inline-flex items-center px-6 py-3 bg-white text-purple-600 text-sm font-medium rounded-xl hover:bg-gray-50 transition-colors">
                    <i class="ri-user-line ml-2"></i>
                    ملف الطالب
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
                        <span>مع الحضور</span>
                    </div>
                </div>
                <div class="w-12 h-12 bg-green-100 rounded-xl flex items-center justify-center group-hover:scale-110 transition-transform">
                    <i class="ri-checkbox-circle-line text-2xl text-green-600"></i>
                </div>
            </div>
        </div>
        
        <!-- Attendance Rate -->
        <div class="bg-white rounded-2xl shadow-lg border border-emerald-100 p-6 hover:shadow-xl transition-all duration-300 group">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-sm font-medium text-emerald-600 mb-1">معدل الحضور</p>
                    <p class="text-3xl font-bold text-emerald-900 mb-2">{{ number_format($stats['attendance_rate'], 1) }}%</p>
                    <div class="w-full bg-emerald-100 rounded-full h-2">
                        <div class="bg-emerald-500 h-2 rounded-full transition-all duration-500" 
                             style="width: {{ $stats['attendance_rate'] }}%"></div>
                    </div>
                </div>
                <div class="w-12 h-12 bg-emerald-100 rounded-xl flex items-center justify-center group-hover:scale-110 transition-transform">
                    <i class="ri-user-check-line text-2xl text-emerald-600"></i>
                </div>
            </div>
        </div>
        
        <!-- Scheduled Sessions -->
        <div class="bg-white rounded-2xl shadow-lg border border-orange-100 p-6 hover:shadow-xl transition-all duration-300 group">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-sm font-medium text-orange-600 mb-1">الجلسات المجدولة</p>
                    <p class="text-3xl font-bold text-orange-900 mb-2">{{ $stats['scheduled_sessions'] }}</p>
                    <div class="flex items-center text-xs text-orange-600">
                        <i class="ri-time-line ml-1"></i>
                        <span>القادمة</span>
                    </div>
                </div>
                <div class="w-12 h-12 bg-orange-100 rounded-xl flex items-center justify-center group-hover:scale-110 transition-transform">
                    <i class="ri-calendar-check-line text-2xl text-orange-600"></i>
                </div>
            </div>
        </div>
        
        <!-- Remaining Sessions -->
        <div class="bg-white rounded-2xl shadow-lg border border-purple-100 p-6 hover:shadow-xl transition-all duration-300 group">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-sm font-medium text-purple-600 mb-1">الجلسات المتبقية</p>
                    <p class="text-3xl font-bold text-purple-900 mb-2">{{ $stats['remaining_sessions'] }}</p>
                    <div class="flex items-center text-xs text-purple-600">
                        <i class="ri-hourglass-line ml-1"></i>
                        <span>للجدولة</span>
                    </div>
                </div>
                <div class="w-12 h-12 bg-purple-100 rounded-xl flex items-center justify-center group-hover:scale-110 transition-transform">
                    <i class="ri-time-line text-2xl text-purple-600"></i>
                </div>
            </div>
        </div>
    </div>

    <!-- Enhanced Progress Overview -->
    <div class="bg-white rounded-2xl shadow-lg border border-gray-100 p-8 mb-8">
        <div class="flex items-center justify-between mb-6">
            <div>
                <h3 class="text-2xl font-bold text-gray-900">نسبة الإنجاز الإجمالية</h3>
                <p class="text-gray-600">التقدم في البرنامج التعليمي</p>
            </div>
            <div class="text-right">
                <span class="text-4xl font-bold bg-gradient-to-r from-primary-600 to-purple-600 bg-clip-text text-transparent">
                    {{ number_format($stats['progress_percentage'], 1) }}%
                </span>
                <p class="text-sm text-gray-500">من البرنامج</p>
            </div>
        </div>
        
        <div class="relative">
            <div class="w-full bg-gray-200 rounded-full h-6 overflow-hidden">
                <div class="bg-gradient-to-r from-primary-500 to-purple-500 h-6 rounded-full transition-all duration-1000 shadow-lg" 
                     style="width: {{ $stats['progress_percentage'] }}%"></div>
            </div>
            <div class="absolute inset-0 flex items-center justify-center">
                <span class="text-sm font-medium text-white mix-blend-difference">
                    {{ $stats['completed_sessions'] }}/{{ $stats['total_sessions'] }} جلسة
                </span>
            </div>
        </div>
        
        <!-- Progress Milestones -->
        <div class="flex justify-between mt-4 text-sm text-gray-500">
            <span>البداية</span>
            <span class="font-medium text-primary-600">{{ number_format($stats['progress_percentage'], 1) }}% مكتمل</span>
            <span>النهاية</span>
        </div>
    </div>

    <div class="grid grid-cols-1 xl:grid-cols-3 gap-8">
        <!-- Main Content - Learning Progress and Activity -->
        <div class="xl:col-span-2 space-y-8">
            
            <!-- Enhanced Learning Progress -->
            @if($circle->current_page || $circle->current_surah || $circle->papers_memorized || $circle->verses_memorized)
                <div class="bg-white rounded-2xl shadow-lg border border-gray-100 p-8">
                    <div class="flex items-center justify-between mb-6">
                        <h3 class="text-2xl font-bold text-gray-900">التقدم في الحفظ</h3>
                        <div class="flex items-center space-x-2 space-x-reverse">
                            <div class="w-3 h-3 bg-green-500 rounded-full animate-pulse"></div>
                            <span class="text-sm text-green-600 font-medium">نشط</span>
                        </div>
                    </div>
                    
                    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
                        @if($circle->current_page && $circle->current_face)
                            <div class="bg-gradient-to-br from-blue-50 to-blue-100 rounded-xl p-6 border border-blue-200">
                                <div class="flex items-center justify-between mb-3">
                                    <p class="text-sm font-semibold text-blue-700">الموضع الحالي</p>
                                    <i class="ri-map-pin-line text-blue-600 text-lg"></i>
                                </div>
                                <p class="text-2xl font-bold text-blue-900 mb-1">الصفحة {{ $circle->current_page }}</p>
                                <p class="text-sm text-blue-700 font-medium">{{ $circle->current_face == 1 ? 'الوجه الأول' : 'الوجه الثاني' }}</p>
                            </div>
                        @elseif($circle->current_surah)
                            <div class="bg-gradient-to-br from-blue-50 to-blue-100 rounded-xl p-6 border border-blue-200">
                                <div class="flex items-center justify-between mb-3">
                                    <p class="text-sm font-semibold text-blue-700">السورة الحالية</p>
                                    <i class="ri-book-open-line text-blue-600 text-lg"></i>
                                </div>
                                <p class="text-2xl font-bold text-blue-900 mb-1">سورة رقم {{ $circle->current_surah }}</p>
                                @if($circle->current_verse)
                                    <p class="text-sm text-blue-700 font-medium">آية {{ $circle->current_verse }}</p>
                                @endif
                            </div>
                        @endif
                        
                        @if($circle->papers_memorized_precise || $circle->papers_memorized)
                            <div class="bg-gradient-to-br from-green-50 to-emerald-100 rounded-xl p-6 border border-green-200">
                                <div class="flex items-center justify-between mb-3">
                                    <p class="text-sm font-semibold text-green-700">الأوجه المحفوظة</p>
                                    <i class="ri-trophy-line text-green-600 text-lg"></i>
                                </div>
                                <p class="text-2xl font-bold text-green-900 mb-1">
                                    {{ $circle->papers_memorized_precise ?? $circle->papers_memorized }} وجه
                                </p>
                                @if($circle->verses_memorized)
                                    <p class="text-sm text-green-700 font-medium">{{ $circle->verses_memorized }} آية</p>
                                @endif
                            </div>
                        @elseif($circle->verses_memorized)
                            <div class="bg-gradient-to-br from-green-50 to-emerald-100 rounded-xl p-6 border border-green-200">
                                <div class="flex items-center justify-between mb-3">
                                    <p class="text-sm font-semibold text-green-700">إجمالي الآيات المحفوظة</p>
                                    <i class="ri-trophy-line text-green-600 text-lg"></i>
                                </div>
                                <p class="text-2xl font-bold text-green-900 mb-1">{{ $circle->verses_memorized }} آية</p>
                                <p class="text-sm text-green-700 font-medium">≈ {{ number_format($circle->convertVersesToPapers($circle->verses_memorized), 1) }} وجه</p>
                            </div>
                        @endif
                        
                        @if($circle->papers_memorized_precise && $circle->papers_memorized_precise > 0)
                            <div class="bg-gradient-to-br from-purple-50 to-violet-100 rounded-xl p-6 border border-purple-200">
                                <div class="flex items-center justify-between mb-3">
                                    <p class="text-sm font-semibold text-purple-700">معدل التقدم</p>
                                    <i class="ri-speed-line text-purple-600 text-lg"></i>
                                </div>
                                <p class="text-2xl font-bold text-purple-900 mb-1">
                                    @if($circle->sessions_completed > 0)
                                        {{ number_format($circle->papers_memorized_precise / $circle->sessions_completed, 2) }} وجه/جلسة
                                    @else
                                        -
                                    @endif
                                </p>
                                <p class="text-sm text-purple-700 font-medium">{{ $circle->sessions_completed }} جلسة مكتملة</p>
                            </div>
                        @endif
                    </div>
                </div>
            @endif

            <!-- Enhanced Attendance & Session History -->
            @if($circle->sessions->count() > 0)
                <div class="bg-white rounded-2xl shadow-lg border border-gray-100 p-8">
                    <div class="flex items-center justify-between mb-6">
                        <h3 class="text-2xl font-bold text-gray-900">سجل الحضور والجلسات</h3>
                        <div class="flex items-center space-x-2 space-x-reverse">
                            <span class="text-sm bg-green-100 text-green-700 px-3 py-1 rounded-full font-medium">
                                آخر 10 جلسات
                            </span>
                        </div>
                    </div>
                    
                    <div class="space-y-4">
                        @forelse($circle->sessions->sortByDesc('scheduled_at')->take(10) as $session)
                            <div class="attendance-indicator bg-gradient-to-r from-gray-50 to-gray-100 rounded-xl p-6 border border-gray-200 hover:shadow-md transition-all duration-300">
                                <div class="flex items-center justify-between">
                                    <!-- Session Info -->
                                    <div class="flex items-center space-x-4 space-x-reverse">
                                        <!-- Attendance Status Indicator -->
                                        <div class="flex flex-col items-center">
                                            @if($session->status === 'completed')
                                                @if($session->attendance_status === 'attended')
                                                    <div class="w-4 h-4 bg-green-500 rounded-full mb-1 animate-pulse"></div>
                                                    <span class="text-xs text-green-600 font-bold">حضر</span>
                                                @elseif($session->attendance_status === 'late')
                                                    <div class="w-4 h-4 bg-yellow-500 rounded-full mb-1"></div>
                                                    <span class="text-xs text-yellow-600 font-bold">متأخر</span>
                                                @elseif($session->attendance_status === 'left_early')
                                                    <div class="w-4 h-4 bg-orange-500 rounded-full mb-1"></div>
                                                    <span class="text-xs text-orange-600 font-bold">غادر مبكراً</span>
                                                @elseif($session->attendance_status === 'absent')
                                                    <div class="w-4 h-4 bg-red-500 rounded-full mb-1"></div>
                                                    <span class="text-xs text-red-600 font-bold">غائب</span>
                                                @else
                                                    <div class="w-4 h-4 bg-green-500 rounded-full mb-1"></div>
                                                    <span class="text-xs text-green-600 font-bold">حضر</span>
                                                @endif
                                            @elseif($session->status === 'scheduled')
                                                <div class="w-4 h-4 bg-blue-500 rounded-full mb-1 animate-bounce"></div>
                                                <span class="text-xs text-blue-600 font-bold">مجدولة</span>
                                            @elseif($session->status === 'cancelled')
                                                <div class="w-4 h-4 bg-gray-400 rounded-full mb-1"></div>
                                                <span class="text-xs text-gray-500 font-bold">ملغاة</span>
                                            @else
                                                <div class="w-4 h-4 bg-gray-300 rounded-full mb-1"></div>
                                                <span class="text-xs text-gray-500 font-bold">{{ $session->getStatusEnum()->label() }}</span>
                                            @endif
                                        </div>
                                        
                                        <!-- Session Details -->
                                        <div class="flex-1">
                                            <div class="flex items-center space-x-3 space-x-reverse mb-2">
                                                <h4 class="font-bold text-gray-900 text-lg">{{ $session->title ?? 'جلسة قرآنية' }}</h4>
                                                @if($session->status === 'completed' && ($session->papers_memorized_today || $session->verses_memorized_today))
                                                    <span class="bg-green-100 text-green-800 text-xs px-2 py-1 rounded-full font-medium">
                                                        @if($session->papers_memorized_today)
                                                            +{{ $session->papers_memorized_today }} وجه
                                                        @elseif($session->verses_memorized_today)
                                                            +{{ $session->verses_memorized_today }} آية
                                                        @endif
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
                                            
                                            <!-- Progress in Session -->
                                            @if($session->status === 'completed' && ($session->current_page || $session->current_surah))
                                                <div class="mt-2 text-sm text-gray-700">
                                                    <span class="bg-blue-50 text-blue-700 px-2 py-1 rounded font-medium">
                                                        @if($session->current_page)
                                                            الصفحة {{ $session->current_page }} - {{ $session->current_face == 1 ? 'الوجه الأول' : 'الوجه الثاني' }}
                                                        @elseif($session->current_surah)
                                                            سورة رقم {{ $session->current_surah }}
                                                            @if($session->current_verse) - آية {{ $session->current_verse }} @endif
                                                        @endif
                                                    </span>
                                                </div>
                                            @endif
                                        </div>
                                    </div>
                                    
                                    <!-- Session Status and Actions -->
                                    <div class="text-left">
                                        <div class="flex flex-col items-end space-y-2">
                                            <!-- Status Badge -->
                                            <span class="inline-flex items-center px-3 py-1 rounded-full text-sm font-medium
                                                {{ $session->status === 'completed' ? 
                                                    ($session->attendance_status === 'attended' ? 'bg-green-100 text-green-800' :
                                                     ($session->attendance_status === 'late' ? 'bg-yellow-100 text-yellow-800' :
                                                      ($session->attendance_status === 'absent' ? 'bg-red-100 text-red-800' : 'bg-green-100 text-green-800'))) :
                                                   ($session->status === 'scheduled' ? 'bg-blue-100 text-blue-800' : 
                                                   ($session->status === 'cancelled' ? 'bg-gray-100 text-gray-800' : 'bg-gray-100 text-gray-800')) }}">
                                                @if($session->status === 'completed')
                                                    @if($session->attendance_status === 'attended')
                                                        <i class="ri-check-double-line ml-1"></i> حضر وأكمل
                                                    @elseif($session->attendance_status === 'late')
                                                        <i class="ri-time-line ml-1"></i> حضر متأخراً
                                                    @elseif($session->attendance_status === 'left_early')
                                                        <i class="ri-logout-box-line ml-1"></i> غادر مبكراً
                                                    @elseif($session->attendance_status === 'absent')
                                                        <i class="ri-close-line ml-1"></i> غائب
                                                    @else
                                                        <i class="ri-check-line ml-1"></i> مكتملة
                                                    @endif
                                                @elseif($session->status === 'scheduled')
                                                    <i class="ri-calendar-check-line ml-1"></i> مجدولة
                                                @elseif($session->status === 'cancelled')
                                                    <i class="ri-close-line ml-1"></i> ملغاة
                                                @else
                                                    <i class="ri-question-line ml-1"></i> {{ $session->getStatusEnum()->label() }}
                                                @endif
                                            </span>
                                            
                                            <!-- Performance Indicators -->
                                            @if($session->status === 'completed' && ($session->recitation_quality || $session->tajweed_accuracy))
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

        <!-- Enhanced Sidebar with Comprehensive Information -->
        <div class="space-y-8">
            
            <!-- Performance Summary -->
            <div class="bg-white rounded-2xl shadow-lg border border-gray-100 p-6">
                <div class="flex items-center justify-between mb-4">
                    <h3 class="text-lg font-bold text-gray-900">الأداء العام</h3>
                    <i class="ri-bar-chart-line text-2xl text-primary-600"></i>
                </div>
                
                @php
                    $completedSessions = $circle->sessions->where('status', 'completed');
                    $avgRecitation = $completedSessions->avg('recitation_quality') ?? 0;
                    $avgTajweed = $completedSessions->avg('tajweed_accuracy') ?? 0;
                    $totalPapers = $circle->papers_memorized_precise ?? $circle->convertVersesToPapers($circle->verses_memorized ?? 0);
                @endphp
                
                <div class="space-y-4">
                    <!-- Recitation Quality -->
                    <div>
                        <div class="flex justify-between text-sm mb-1">
                            <span class="text-gray-600">جودة التلاوة</span>
                            <span class="font-medium">{{ number_format($avgRecitation, 1) }}/10</span>
                        </div>
                        <div class="w-full bg-gray-200 rounded-full h-2">
                            <div class="bg-gradient-to-r from-purple-400 to-purple-600 h-2 rounded-full transition-all duration-500" 
                                 style="width: {{ $avgRecitation * 10 }}%"></div>
                        </div>
                    </div>
                    
                    <!-- Tajweed Accuracy -->
                    <div>
                        <div class="flex justify-between text-sm mb-1">
                            <span class="text-gray-600">دقة التجويد</span>
                            <span class="font-medium">{{ number_format($avgTajweed, 1) }}/10</span>
                        </div>
                        <div class="w-full bg-gray-200 rounded-full h-2">
                            <div class="bg-gradient-to-r from-indigo-400 to-indigo-600 h-2 rounded-full transition-all duration-500" 
                                 style="width: {{ $avgTajweed * 10 }}%"></div>
                        </div>
                    </div>
                    
                    <!-- Attendance Rate -->
                    <div>
                        <div class="flex justify-between text-sm mb-1">
                            <span class="text-gray-600">معدل الحضور</span>
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
                        <p class="text-sm text-gray-600 mb-1">التقييم الإجمالي</p>
                        @php
                            $overallGrade = ($avgRecitation + $avgTajweed + ($stats['attendance_rate']/10)) / 3;
                            $gradeText = $overallGrade >= 8.5 ? 'ممتاز' : ($overallGrade >= 7 ? 'جيد جداً' : ($overallGrade >= 6 ? 'جيد' : 'يحتاج تحسين'));
                            $gradeColor = $overallGrade >= 8.5 ? 'text-green-600' : ($overallGrade >= 7 ? 'text-blue-600' : ($overallGrade >= 6 ? 'text-yellow-600' : 'text-red-600'));
                        @endphp
                        <p class="text-2xl font-bold {{ $gradeColor }}">{{ $gradeText }}</p>
                        <p class="text-sm text-gray-500">{{ number_format($overallGrade, 1) }}/10</p>
                    </div>
                </div>
            </div>

            <!-- Student Details -->
            <div class="bg-white rounded-2xl shadow-lg border border-gray-100 p-6">
                <h3 class="text-lg font-bold text-gray-900 mb-4">تفاصيل الطالب</h3>
                
                <div class="space-y-4">
                    <!-- Basic Info -->
                    <div class="flex items-center space-x-3 space-x-reverse">
                        <x-student-avatar :student="$circle->student" size="lg" />
                        <div class="flex-1">
                            <p class="font-bold text-gray-900 text-lg">{{ $circle->student->name }}</p>
                            <p class="text-sm text-gray-500">{{ $circle->student->email }}</p>
                            @if($circle->student->studentProfile?->student_code)
                                <p class="text-xs text-primary-600 font-medium">#{{ $circle->student->studentProfile->student_code }}</p>
                            @endif
                        </div>
                    </div>
                    
                    @if($circle->student->studentProfile)
                        <div class="space-y-3 text-sm border-t border-gray-100 pt-4">
                            @if($circle->student->studentProfile->phone)
                                <div class="flex items-center justify-between">
                                    <span class="text-gray-600 flex items-center">
                                        <i class="ri-phone-line ml-1"></i>
                                        الهاتف
                                    </span>
                                    <span class="font-medium">{{ $circle->student->studentProfile->phone }}</span>
                                </div>
                            @endif
                            @if($circle->student->studentProfile->date_of_birth)
                                <div class="flex items-center justify-between">
                                    <span class="text-gray-600 flex items-center">
                                        <i class="ri-calendar-line ml-1"></i>
                                        العمر
                                    </span>
                                    <span class="font-medium">{{ \Carbon\Carbon::parse($circle->student->studentProfile->date_of_birth)->age }} سنة</span>
                                </div>
                            @endif
                        </div>
                    @endif
                </div>
            </div>

            <!-- Learning Goals & Milestones -->
            <div class="bg-white rounded-2xl shadow-lg border border-gray-100 p-6">
                <h3 class="text-lg font-bold text-gray-900 mb-4">الأهداف والإنجازات</h3>
                
                <div class="space-y-4">
                    <!-- Current Goal -->
                    <div class="bg-blue-50 rounded-xl p-4 border border-blue-100">
                        <p class="text-sm font-semibold text-blue-700 mb-2">الهدف الحالي</p>
                        <p class="text-blue-900 font-medium">
                            @if($totalPapers > 0)
                                إكمال {{ ceil($totalPapers) + 1 }} وجه القادم
                            @else
                                البدء في رحلة الحفظ
                            @endif
                        </p>
                    </div>
                    
                    <!-- Progress to Next Milestone -->
                    @if($totalPapers > 0)
                        @php
                            $nextMilestone = ceil($totalPapers / 5) * 5; // Next 5-paper milestone
                            $progressToMilestone = (($totalPapers % 5) / 5) * 100;
                        @endphp
                        <div>
                            <div class="flex justify-between text-sm mb-2">
                                <span class="text-gray-600">التقدم نحو {{ $nextMilestone }} وجه</span>
                                <span class="font-medium">{{ number_format($progressToMilestone, 1) }}%</span>
                            </div>
                            <div class="w-full bg-gray-200 rounded-full h-3">
                                <div class="bg-gradient-to-r from-yellow-400 to-orange-500 h-3 rounded-full transition-all duration-500" 
                                     style="width: {{ $progressToMilestone }}%"></div>
                            </div>
                        </div>
                    @endif
                    
                    <!-- Achievements -->
                    <div class="space-y-2">
                        <p class="text-sm font-semibold text-gray-700">الإنجازات</p>
                        <div class="grid grid-cols-2 gap-2">
                            @if($stats['completed_sessions'] >= 10)
                                <div class="bg-green-100 text-green-800 text-xs px-2 py-1 rounded-full text-center font-medium">
                                    <i class="ri-medal-line"></i> 10 جلسات
                                </div>
                            @endif
                            @if($stats['attendance_rate'] >= 90)
                                <div class="bg-blue-100 text-blue-800 text-xs px-2 py-1 rounded-full text-center font-medium">
                                    <i class="ri-star-line"></i> حضور ممتاز
                                </div>
                            @endif
                            @if($totalPapers >= 5)
                                <div class="bg-purple-100 text-purple-800 text-xs px-2 py-1 rounded-full text-center font-medium">
                                    <i class="ri-trophy-line"></i> 5 أوجه
                                </div>
                            @endif
                            @if($avgRecitation >= 8)
                                <div class="bg-yellow-100 text-yellow-800 text-xs px-2 py-1 rounded-full text-center font-medium">
                                    <i class="ri-mic-line"></i> تلاوة متقنة
                                </div>
                            @endif
                        </div>
                    </div>
                </div>
            </div>

            <!-- Circle Information -->
            <div class="bg-white rounded-2xl shadow-lg border border-gray-100 p-6">
                <h3 class="text-lg font-bold text-gray-900 mb-4">معلومات الحلقة</h3>
                
                <div class="space-y-4">
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
                    
                    @if($circle->started_at)
                        <div class="flex items-center justify-between">
                            <span class="text-gray-600 flex items-center">
                                <i class="ri-play-line ml-1"></i>
                                بدأت في
                            </span>
                            <span class="font-medium">{{ $circle->started_at->format('Y/m/d') }}</span>
                        </div>
                    @endif
                    
                    @if($circle->default_duration_minutes)
                        <div class="flex items-center justify-between">
                            <span class="text-gray-600 flex items-center">
                                <i class="ri-timer-line ml-1"></i>
                                مدة الجلسة
                            </span>
                            <span class="font-medium">{{ $circle->default_duration_minutes }} دقيقة</span>
                        </div>
                    @endif
                    
                    @if($circle->last_session_at)
                        <div class="flex items-center justify-between">
                            <span class="text-gray-600 flex items-center">
                                <i class="ri-history-line ml-1"></i>
                                آخر جلسة
                            </span>
                            <span class="font-medium">{{ $circle->last_session_at->diffForHumans() }}</span>
                        </div>
                    @endif
                </div>
            </div>

            <!-- Quick Actions -->
            <div class="bg-white rounded-2xl shadow-lg border border-gray-100 p-6">
                <h3 class="text-lg font-bold text-gray-900 mb-4">إجراءات سريعة</h3>
                
                <div class="space-y-3">
                    <a href="{{ route('teacher.individual-circles.show', ['subdomain' => auth()->user()->academy->subdomain ?? 'itqan-academy', 'circle' => $circle->id]) }}" 
                       class="block w-full text-center px-5 py-3 bg-gradient-to-r from-primary-600 to-purple-600 text-white text-sm font-medium rounded-xl hover:from-primary-700 hover:to-purple-700 transition-colors shadow-lg">
                        <i class="ri-eye-line ml-2"></i>
                        عرض الحلقة
                    </a>
                    
                    @if($circle->canScheduleSession())
                        <button class="w-full px-5 py-3 bg-gradient-to-r from-green-600 to-emerald-600 text-white text-sm font-medium rounded-xl hover:from-green-700 hover:to-emerald-700 transition-colors shadow-lg">
                            <i class="ri-calendar-line ml-2"></i>
                            جدولة جلسة جديدة
                        </button>
                    @endif
                    
                    <button class="w-full px-5 py-3 border-2 border-gray-200 text-gray-700 text-sm font-medium rounded-xl hover:bg-gray-50 hover:border-gray-300 transition-colors">
                        <i class="ri-download-line ml-2"></i>
                        تصدير التقرير
                    </button>
                    
                    <button class="w-full px-5 py-3 border-2 border-orange-200 text-orange-700 text-sm font-medium rounded-xl hover:bg-orange-50 hover:border-orange-300 transition-colors">
                        <i class="ri-message-line ml-2"></i>
                        إرسال تقرير لولي الأمر
                    </button>
                </div>
            </div>
        </div>
    </div>
</div>

</x-layouts.teacher>
