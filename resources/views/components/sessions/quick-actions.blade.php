@props([
    'session',
    'viewType' => 'student' // 'student' or 'teacher'
])

@php
    $isTeacher = $viewType === 'teacher';
@endphp

<!-- Quick Actions Section -->
<div class="bg-white rounded-xl shadow-sm border border-gray-200 p-6">
    <h3 class="text-lg font-semibold text-gray-900 mb-4">الإجراءات السريعة</h3>
    
    <div class="space-y-3">
        @if($isTeacher)
            <!-- Teacher Actions -->
            
            <!-- Sessions Management -->
            <a href="{{ route('teacher.sessions.index', ['subdomain' => request()->route('subdomain')]) }}" 
               class="flex items-center gap-3 p-3 bg-blue-50 hover:bg-blue-100 rounded-lg transition-colors duration-200 group">
                <div class="w-10 h-10 bg-blue-600 rounded-lg flex items-center justify-center text-white group-hover:bg-blue-700">
                    <i class="ri-calendar-line"></i>
                </div>
                <div>
                    <p class="font-medium text-gray-900">جلساتي</p>
                    <p class="text-sm text-gray-600">إدارة جميع الجلسات</p>
                </div>
            </a>
            
            <!-- Homework Management -->
            @if($session->session_type === 'group' || $session->session_type === 'individual')
                <div class="flex items-center gap-3 p-3 bg-yellow-50 rounded-lg opacity-75">
                    <div class="w-10 h-10 bg-yellow-600 rounded-lg flex items-center justify-center text-white relative">
                        <i class="ri-file-text-line"></i>
                        @if($session->homework && $session->homework->count() > 0)
                            <span class="absolute -top-1 -right-1 bg-red-500 text-white text-xs rounded-full h-4 w-4 flex items-center justify-center">{{ $session->homework->count() }}</span>
                        @endif
                    </div>
                    <div>
                        <p class="font-medium text-gray-900">الواجبات</p>
                        <p class="text-sm text-gray-600">قيد التطوير</p>
                    </div>
                </div>
            @endif
            
            <!-- Students Management -->
            @if($session->session_type === 'group')
                <div class="flex items-center gap-3 p-3 bg-green-50 rounded-lg opacity-75">
                    <div class="w-10 h-10 bg-green-600 rounded-lg flex items-center justify-center text-white">
                        <i class="ri-group-line"></i>
                    </div>
                    <div>
                        <p class="font-medium text-gray-900">الطلاب</p>
                        <p class="text-sm text-gray-600">قيد التطوير</p>
                    </div>
                </div>
            @endif
            
            <!-- Individual Student Profile (for individual sessions) -->
            @if($session->session_type === 'individual' && $session->student)
                <div class="flex items-center gap-3 p-3 bg-purple-50 rounded-lg opacity-75">
                    <div class="w-10 h-10 bg-purple-600 rounded-lg flex items-center justify-center text-white">
                        <i class="ri-user-line"></i>
                    </div>
                    <div>
                        <p class="font-medium text-gray-900">ملف الطالب</p>
                        <p class="text-sm text-gray-600">{{ $session->student->name }}</p>
                    </div>
                </div>
            @endif
            
            <!-- Progress Reports -->
            <div class="flex items-center gap-3 p-3 bg-indigo-50 rounded-lg opacity-75">
                <div class="w-10 h-10 bg-indigo-600 rounded-lg flex items-center justify-center text-white">
                    <i class="ri-bar-chart-line"></i>
                </div>
                <div>
                    <p class="font-medium text-gray-900">التقارير</p>
                    <p class="text-sm text-gray-600">قيد التطوير</p>
                </div>
            </div>
            
            <!-- Calendar View -->
            <a href="{{ route('teacher.calendar', ['subdomain' => request()->route('subdomain')]) }}" 
               class="flex items-center gap-3 p-3 bg-orange-50 hover:bg-orange-100 rounded-lg transition-colors duration-200 group">
                <div class="w-10 h-10 bg-orange-600 rounded-lg flex items-center justify-center text-white group-hover:bg-orange-700">
                    <i class="ri-calendar-2-line"></i>
                </div>
                <div>
                    <p class="font-medium text-gray-900">التقويم</p>
                    <p class="text-sm text-gray-600">عرض التقويم</p>
                </div>
            </a>
            
        @else
            <!-- Student Actions -->
            
            <!-- My Circle -->
            @if($session->individualCircle)
                <a href="{{ route('individual-circles.show', ['subdomain' => request()->route('subdomain'), 'circle' => $session->individualCircle->id]) }}" 
                   class="flex items-center gap-3 p-3 bg-blue-50 hover:bg-blue-100 rounded-lg transition-colors duration-200 group">
                    <div class="w-10 h-10 bg-blue-600 rounded-lg flex items-center justify-center text-white group-hover:bg-blue-700">
                        <i class="ri-book-open-line"></i>
                    </div>
                    <div>
                        <p class="font-medium text-gray-900">حلقتي</p>
                        <p class="text-sm text-gray-600">عرض حلقة القرآن</p>
                    </div>
                </a>
            @else
                <a href="{{ route('student.dashboard', ['subdomain' => request()->route('subdomain')]) }}" 
                   class="flex items-center gap-3 p-3 bg-blue-50 hover:bg-blue-100 rounded-lg transition-colors duration-200 group">
                    <div class="w-10 h-10 bg-blue-600 rounded-lg flex items-center justify-center text-white group-hover:bg-blue-700">
                        <i class="ri-calendar-line"></i>
                    </div>
                    <div>
                        <p class="font-medium text-gray-900">لوحة التحكم</p>
                        <p class="text-sm text-gray-600">العودة للرئيسية</p>
                    </div>
                </a>
            @endif
            
            <!-- Homework (Note: Homework routes not yet implemented) -->
            @if($session->homework_assigned)
                <div class="flex items-center gap-3 p-3 bg-yellow-50 rounded-lg opacity-75">
                    <div class="w-10 h-10 bg-yellow-600 rounded-lg flex items-center justify-center text-white">
                        <i class="ri-file-text-line"></i>
                    </div>
                    <div>
                        <p class="font-medium text-gray-900">الواجبات</p>
                        <p class="text-sm text-gray-600">قيد التطوير</p>
                    </div>
                </div>
            @endif
            
            <!-- Progress -->
            <a href="{{ route('student.progress', ['subdomain' => request()->route('subdomain')]) }}" 
               class="flex items-center gap-3 p-3 bg-green-50 hover:bg-green-100 rounded-lg transition-colors duration-200 group">
                <div class="w-10 h-10 bg-green-600 rounded-lg flex items-center justify-center text-white group-hover:bg-green-700">
                    <i class="ri-line-chart-line"></i>
                </div>
                <div>
                    <p class="font-medium text-gray-900">التقدم</p>
                    <p class="text-sm text-gray-600">متابعة الإنجازات</p>
                </div>
            </a>
            
            <!-- Teacher Profile -->
            @if($session->teacher)
                <div class="flex items-center gap-3 p-3 bg-purple-50 rounded-lg opacity-75">
                    <div class="w-10 h-10 bg-purple-600 rounded-lg flex items-center justify-center text-white">
                        <i class="ri-user-star-line"></i>
                    </div>
                    <div>
                        <p class="font-medium text-gray-900">ملف المعلم</p>
                        <p class="text-sm text-gray-600">{{ $session->teacher->name }}</p>
                    </div>
                </div>
            @endif
            
            <!-- Circle Details -->
            @if($session->circle_id && $session->circle)
                <a href="{{ route('student.circles.show', ['subdomain' => request()->route('subdomain'), 'circleId' => $session->circle->id]) }}" 
                   class="flex items-center gap-3 p-3 bg-indigo-50 hover:bg-indigo-100 rounded-lg transition-colors duration-200 group">
                    <div class="w-10 h-10 bg-indigo-600 rounded-lg flex items-center justify-center text-white group-hover:bg-indigo-700">
                        <i class="ri-group-line"></i>
                    </div>
                    <div>
                        <p class="font-medium text-gray-900">تفاصيل الحلقة</p>
                        <p class="text-sm text-gray-600">{{ $session->circle->name ?? 'الحلقة الجماعية' }}</p>
                    </div>
                </a>
            @elseif($session->individual_circle_id && $session->individualCircle)
                <a href="{{ route('individual-circles.show', ['subdomain' => request()->route('subdomain'), 'circle' => $session->individualCircle->id]) }}" 
                   class="flex items-center gap-3 p-3 bg-indigo-50 hover:bg-indigo-100 rounded-lg transition-colors duration-200 group">
                    <div class="w-10 h-10 bg-indigo-600 rounded-lg flex items-center justify-center text-white group-hover:bg-indigo-700">
                        <i class="ri-user-line"></i>
                    </div>
                    <div>
                        <p class="font-medium text-gray-900">الحلقة الفردية</p>
                        <p class="text-sm text-gray-600">تفاصيل الحلقة</p>
                    </div>
                </a>
            @endif
            
            <!-- Calendar -->
            <a href="{{ route('student.calendar', ['subdomain' => request()->route('subdomain')]) }}" 
               class="flex items-center gap-3 p-3 bg-orange-50 hover:bg-orange-100 rounded-lg transition-colors duration-200 group">
                <div class="w-10 h-10 bg-orange-600 rounded-lg flex items-center justify-center text-white group-hover:bg-orange-700">
                    <i class="ri-calendar-2-line"></i>
                </div>
                <div>
                    <p class="font-medium text-gray-900">التقويم</p>
                    <p class="text-sm text-gray-600">جدول الجلسات</p>
                </div>
            </a>
        @endif
        
        <!-- Common Actions for Both Roles -->
        
        <!-- Help & Support -->
        <div class="flex items-center gap-3 p-3 bg-gray-50 rounded-lg opacity-75">
            <div class="w-10 h-10 bg-gray-600 rounded-lg flex items-center justify-center text-white">
                <i class="ri-question-line"></i>
            </div>
            <div>
                <p class="font-medium text-gray-900">المساعدة</p>
                <p class="text-sm text-gray-600">قيد التطوير</p>
            </div>
        </div>
    </div>
    
    <!-- Session-Specific Quick Info -->
    @if($session->status === 'scheduled' && $session->scheduled_at)
        <div class="mt-6 pt-4 border-t border-gray-200">
            <h4 class="text-sm font-medium text-gray-900 mb-2">معلومات سريعة</h4>
            
            @php
                $timeDiff = $session->scheduled_at->diffInMinutes(now());
                $timeText = '';
                $colorClass = '';
                
                if ($session->scheduled_at->isPast()) {
                    $timeText = 'انتهت منذ ' . $session->scheduled_at->diffForHumans();
                    $colorClass = 'text-red-600 bg-red-50';
                } elseif ($timeDiff <= 10) {
                    $timeText = 'تبدأ خلال ' . $timeDiff . ' دقيقة';
                    $colorClass = 'text-orange-600 bg-orange-50';
                } elseif ($timeDiff <= 60) {
                    $timeText = 'تبدأ خلال ' . $timeDiff . ' دقيقة';
                    $colorClass = 'text-yellow-600 bg-yellow-50';
                } else {
                    $timeText = 'تبدأ ' . $session->scheduled_at->diffForHumans();
                    $colorClass = 'text-blue-600 bg-blue-50';
                }
            @endphp
            
            <div class="p-3 rounded-lg {{ $colorClass }}">
                <div class="flex items-center">
                    <i class="ri-time-line ml-2"></i>
                    <span class="text-sm font-medium">{{ $timeText }}</span>
                </div>
                @if($session->duration_minutes)
                    <div class="flex items-center mt-1">
                        <i class="ri-timer-line ml-2"></i>
                        <span class="text-sm">المدة: {{ $session->duration_minutes }} دقيقة</span>
                    </div>
                @endif
            </div>
        </div>
    @endif
</div> 