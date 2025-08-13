@props([
    'sessions' => collect(),
    'title' => 'سجل الحضور والجلسات',
    'subtitle' => 'آخر 10 جلسات',
    'limit' => 10,
    'viewType' => 'student', // 'student', 'teacher'
    'showAllButton' => true,
    'emptyMessage' => 'لا توجد جلسات مسجلة بعد'
])

@php
    $limitedSessions = $limit ? $sessions->sortByDesc('scheduled_at')->take($limit) : $sessions->sortByDesc('scheduled_at');
@endphp

<!-- Enhanced Attendance & Session History -->
@if($sessions->count() > 0)
    <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-6">
        <div class="flex items-center justify-between mb-6">
            <h3 class="text-xl font-bold text-gray-900">{{ $title }}</h3>
            @if($subtitle)
                <div class="flex items-center space-x-2 space-x-reverse">
                    <span class="text-sm bg-green-100 text-green-700 px-3 py-1 rounded-full font-medium">
                        {{ $subtitle }}
                    </span>
                </div>
            @endif
        </div>
        
        <div class="space-y-4">
            @forelse($limitedSessions as $session)
                <div class="attendance-indicator rounded-xl p-6 border border-gray-200 hover:shadow-md transition-all duration-300 cursor-pointer"
                     onclick="openSessionDetail({{ $session->id }})">
                    <div class="flex items-center justify-between">
                        <!-- Session Info -->
                        <div class="flex items-center space-x-4 space-x-reverse">
                            <!-- Attendance Status Indicator -->
                            <div class="flex flex-col items-center">
                                @if($session->status === App\Enums\SessionStatus::COMPLETED)
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
                                @elseif($session->getStatusEnum()->value === 'scheduled')
                                    <div class="w-4 h-4 bg-blue-500 rounded-full mb-1 animate-bounce"></div>
                                    <span class="text-xs text-blue-600 font-bold">مجدولة</span>
                                @elseif($session->getStatusEnum()->value === 'cancelled')
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
                                    @if($session->status === App\Enums\SessionStatus::COMPLETED && ($session->papers_memorized_today || $session->verses_memorized_today))
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
                                @if($session->status === App\Enums\SessionStatus::COMPLETED && ($session->current_page || $session->current_surah))
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
                                    {{ $session->status === App\Enums\SessionStatus::COMPLETED ? 
                                        ($session->attendance_status === 'attended' ? 'bg-green-100 text-green-800' :
                                         ($session->attendance_status === 'late' ? 'bg-yellow-100 text-yellow-800' :
                                          ($session->attendance_status === 'absent' ? 'bg-red-100 text-red-800' : 'bg-green-100 text-green-800'))) :
                                       ($session->status === App\Enums\SessionStatus::SCHEDULED ? 'bg-blue-100 text-blue-800' : 
                                       ($session->status === App\Enums\SessionStatus::CANCELLED ? 'bg-gray-100 text-gray-800' : 'bg-gray-100 text-gray-800')) }}">
                                    @if($session->status === App\Enums\SessionStatus::COMPLETED)
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
                    <p class="text-gray-500">{{ $emptyMessage }}</p>
                </div>
            @endforelse
        </div>
        
        @if($showAllButton && $sessions->count() > $limit)
            <div class="mt-6 text-center">
                <button class="inline-flex items-center px-6 py-3 bg-gray-100 text-gray-700 rounded-xl hover:bg-gray-200 transition-colors"
                        onclick="showAllSessions()">
                    <i class="ri-more-line ml-2"></i>
                    عرض جميع الجلسات ({{ $sessions->count() }})
                </button>
            </div>
        @endif
    </div>
@else
    <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-6">
        <div class="text-center py-12">
            <i class="ri-calendar-line text-4xl text-gray-300 mb-3"></i>
            <p class="text-gray-500">{{ $emptyMessage }}</p>
        </div>
    </div>
@endif

<script>
function openSessionDetail(sessionId) {
    const userType = '{{ $viewType }}';
    
    if (userType === 'teacher') {
        @if(auth()->check())
            const subdomain = '{{ auth()->user()->academy->subdomain ?? "itqan-academy" }}';
            const domain = '{{ config("app.domain") }}';
            
            // Use subdomain-based routing: http://subdomain.domain/teacher/sessions/id
            const finalUrl = `http://${subdomain}.${domain}/teacher/sessions/${sessionId}`;
            
            console.log('Session URL:', finalUrl);
            window.location.href = finalUrl;
        @else
            console.error('User not authenticated');
        @endif
    } else {
        // For student sessions 
        @if(auth()->check())
            const subdomain = '{{ auth()->user()->academy->subdomain ?? "itqan-academy" }}';
            const domain = '{{ config("app.domain") }}';
            const finalUrl = `http://${subdomain}.${domain}/sessions/${sessionId}`;
            window.location.href = finalUrl;
        @endif
    }
}

function showAllSessions() {
    // This could expand the view or redirect to a full sessions page
    alert('سيتم تنفيذ عرض جميع الجلسات قريباً');
}
</script>
