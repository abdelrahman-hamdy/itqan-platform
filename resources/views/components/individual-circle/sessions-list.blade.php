@props([
    'circle',
    'sessions',
    'viewType' => 'student' // 'student' or 'teacher'
])

<div class="bg-white rounded-xl shadow-sm mb-8">
    <div class="p-6 border-b border-gray-200">
        <div class="flex items-center justify-between">
            <h3 class="text-lg font-bold text-gray-900">
                <i class="ri-calendar-line text-primary-600 ml-2"></i>
                جلسات الحلقة
            </h3>
            <div class="flex items-center space-x-2 space-x-reverse">
                @if($viewType === 'teacher')
                    <button type="button" onclick="openScheduleModal()" class="px-4 py-2 text-sm font-medium text-white bg-primary-600 rounded-lg hover:bg-primary-700 transition-colors">
                        <i class="ri-add-line ml-1"></i>
                        جدولة جلسة
                    </button>
                    <div class="w-px h-6 bg-gray-300 mx-2"></div>
                @endif
                
                <button type="button" id="filterAllSessions" class="px-3 py-1 text-sm rounded-full border border-primary-200 text-primary-700 bg-primary-50">
                    الكل
                </button>
                <button type="button" id="filterScheduledSessions" class="px-3 py-1 text-sm rounded-full border border-gray-200 text-gray-700 hover:bg-gray-50">
                    المجدولة
                </button>
                <button type="button" id="filterCompletedSessions" class="px-3 py-1 text-sm rounded-full border border-gray-200 text-gray-700 hover:bg-gray-50">
                    المكتملة
                </button>
                <button type="button" id="filterUnscheduledSessions" class="px-3 py-1 text-sm rounded-full border border-gray-200 text-gray-700 hover:bg-gray-50">
                    غير مجدولة
                </button>
                <button type="button" id="filterTemplateSessions" class="px-3 py-1 text-sm rounded-full border border-gray-200 text-gray-700 hover:bg-gray-50">
                    القوالب
                </button>
            </div>
        </div>
    </div>
    
    <div class="p-6">
        @if($sessions && $sessions->count() > 0)
            <div class="space-y-4" id="sessionsList">
                @foreach($sessions->sortBy('scheduled_at') as $session)
                    @php
                        $sessionType = $session->status === 'completed' ? 'completed' : 
                                      ($session->status === 'scheduled' ? 'scheduled' : 
                                      ($session->status === 'unscheduled' ? 'unscheduled' : 'template'));
                        $isClickable = in_array($session->status, ['completed', 'scheduled']);
                    @endphp
                    <div class="session-item border border-gray-200 rounded-lg p-4 transition-colors {{ $isClickable ? 'hover:border-primary-300 cursor-pointer' : 'opacity-60 cursor-not-allowed' }}"
                         data-session-type="{{ $sessionType }}"
                         @if($isClickable) onclick="openSessionDetail({{ $session->id }})" @endif>
                        <div class="flex items-center justify-between">
                            <div class="flex items-center space-x-3 space-x-reverse">
                                <div class="flex-shrink-0">
                                    <span class="inline-flex items-center justify-center w-8 h-8 rounded-full text-xs font-medium
                                        {{ $session->status === 'completed' ? 'bg-green-100 text-green-800' : 
                                           ($session->status === 'scheduled' ? 'bg-blue-100 text-blue-800' : 
                                           ($session->status === 'unscheduled' ? 'bg-yellow-100 text-yellow-800' : 'bg-gray-100 text-gray-800')) }}">
                                        {{ $session->session_sequence }}
                                    </span>
                                </div>
                                <div>
                                    <h4 class="font-medium text-gray-900">{{ $session->title }}</h4>
                                    <div class="flex items-center space-x-4 space-x-reverse mt-1">
                                        @if($session->status === 'scheduled' && $session->scheduled_at)
                                            <span class="text-sm text-gray-600">
                                                <i class="ri-calendar-line ml-1"></i>
                                                {{ $session->scheduled_at->format('l، d F Y - H:i') }}
                                            </span>
                                        @elseif($session->status === 'completed' && $session->ended_at)
                                            <span class="text-sm text-gray-600">
                                                <i class="ri-check-line ml-1"></i>
                                                اكتملت {{ $session->ended_at->diffForHumans() }}
                                            </span>
                                        @elseif($session->status === 'unscheduled')
                                            <span class="text-sm text-yellow-600">
                                                <i class="ri-time-line ml-1"></i>
                                                في انتظار الجدولة
                                            </span>
                                        @else
                                            <span class="text-sm text-gray-500">
                                                <i class="ri-draft-line ml-1"></i>
                                                قالب جلسة
                                            </span>
                                        @endif
                                        
                                        <span class="text-sm text-gray-500">
                                            <i class="ri-time-line ml-1"></i>
                                            {{ $session->duration_minutes }} دقيقة
                                        </span>

                                        @if($session->lesson_objectives && count($session->lesson_objectives) > 0)
                                            <span class="text-sm text-gray-500">
                                                <i class="ri-target-line ml-1"></i>
                                                {{ count($session->lesson_objectives) }} هدف
                                            </span>
                                        @endif
                                    </div>
                                </div>
                            </div>
                            
                            <div class="flex items-center space-x-2 space-x-reverse">
                                <!-- Status Badge -->
                                <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium
                                    {{ $session->status === 'completed' ? 'bg-green-100 text-green-800' : 
                                       ($session->status === 'scheduled' ? 'bg-blue-100 text-blue-800' : 
                                       ($session->status === 'unscheduled' ? 'bg-yellow-100 text-yellow-800' : 'bg-gray-100 text-gray-800')) }}">
                                    @if($session->status === 'completed')
                                        مكتملة
                                    @elseif($session->status === 'scheduled')
                                        مجدولة
                                    @elseif($session->status === 'unscheduled')
                                        غير مجدولة
                                    @else
                                        قالب
                                    @endif
                                </span>
                                
                                <!-- Action Buttons -->
                                @if($session->is_scheduled && $session->scheduled_at && $session->scheduled_at->isFuture())
                                    @php
                                        $minutesUntilSession = now()->diffInMinutes($session->scheduled_at);
                                        $canJoin = $minutesUntilSession <= 30; // Can join 30 minutes before
                                    @endphp
                                    
                                    @if($canJoin)
                                        <a href="{{ route('meetings.join', ['subdomain' => auth()->user()->academy->subdomain ?? 'itqan-academy', 'session' => $session->id]) }}" 
                                           onclick="event.stopPropagation()"
                                           class="inline-flex items-center px-3 py-1 bg-primary-600 text-white text-xs font-medium rounded-md hover:bg-primary-700 transition-colors">
                                            <i class="ri-video-line ml-1"></i>
                                            انضمام للجلسة
                                        </a>
                                    @else
                                        <span class="text-xs text-gray-500">
                                            متاح خلال {{ $minutesUntilSession }} دقيقة
                                        </span>
                                    @endif
                                @elseif($session->status === 'completed')
                                    <!-- Show homework/progress/quiz indicators for completed sessions -->
                                    <div class="flex items-center space-x-1 space-x-reverse">
                                        @if($session->homework && $session->homework->count() > 0)
                                            <span class="inline-flex items-center px-2 py-1 bg-yellow-100 text-yellow-800 text-xs rounded">
                                                <i class="ri-book-2-line ml-1"></i>
                                                واجبات
                                            </span>
                                        @endif
                                        
                                        @if($session->progress && $session->progress->count() > 0)
                                            <span class="inline-flex items-center px-2 py-1 bg-green-100 text-green-800 text-xs rounded">
                                                <i class="ri-line-chart-line ml-1"></i>
                                                تقدم
                                            </span>
                                        @endif
                                        
                                        @if($session->quizzes && $session->quizzes->count() > 0)
                                            <span class="inline-flex items-center px-2 py-1 bg-purple-100 text-purple-800 text-xs rounded">
                                                <i class="ri-question-line ml-1"></i>
                                                اختبارات
                                            </span>
                                        @endif
                                    </div>
                                @endif
                                
                                <i class="ri-arrow-left-s-line text-gray-400"></i>
                            </div>
                        </div>

                        <!-- Session Description/Notes (if any) -->
                        @if($session->description && strlen($session->description) > 0)
                            <div class="mt-3 pt-3 border-t border-gray-100">
                                <p class="text-sm text-gray-600 line-clamp-2">{{ $session->description }}</p>
                            </div>
                        @endif

                        <!-- Lesson Objectives (for teacher view) -->
                        @if($viewType === 'teacher' && $session->lesson_objectives && count($session->lesson_objectives) > 0)
                            <div class="mt-3 pt-3 border-t border-gray-100">
                                <p class="text-xs text-gray-500 mb-1">أهداف الجلسة:</p>
                                <div class="flex flex-wrap gap-1">
                                    @foreach(array_slice($session->lesson_objectives, 0, 3) as $objective)
                                        <span class="inline-flex items-center px-2 py-1 bg-blue-50 text-blue-700 text-xs rounded">
                                            {{ $objective }}
                                        </span>
                                    @endforeach
                                    @if(count($session->lesson_objectives) > 3)
                                        <span class="text-xs text-gray-500">+{{ count($session->lesson_objectives) - 3 }} المزيد</span>
                                    @endif
                                </div>
                            </div>
                        @endif
                    </div>
                @endforeach
            </div>
        @else
            <div class="text-center py-8">
                <i class="ri-calendar-line text-4xl text-gray-300 mb-3"></i>
                <p class="text-gray-500">لا توجد جلسات متاحة</p>
                <p class="text-sm text-gray-400">ستظهر الجلسات هنا عند إنشائها</p>
            </div>
        @endif
    </div>
</div>

<script>
// Session filtering
document.addEventListener('DOMContentLoaded', function() {
    const filterButtons = ['filterAllSessions', 'filterScheduledSessions', 'filterCompletedSessions', 'filterUnscheduledSessions', 'filterTemplateSessions'];
    const sessionItems = document.querySelectorAll('.session-item');
    
    filterButtons.forEach(buttonId => {
        const button = document.getElementById(buttonId);
        if (button) {
            button.addEventListener('click', function() {
                // Update button styles
                filterButtons.forEach(id => {
                    const btn = document.getElementById(id);
                    if (btn) {
                        btn.classList.remove('border-primary-200', 'text-primary-700', 'bg-primary-50');
                        btn.classList.add('border-gray-200', 'text-gray-700');
                    }
                });
                
                this.classList.add('border-primary-200', 'text-primary-700', 'bg-primary-50');
                this.classList.remove('border-gray-200', 'text-gray-700');
                
                // Filter sessions
                const filterType = buttonId.replace('filter', '').replace('Sessions', '').toLowerCase();
                
                sessionItems.forEach(item => {
                    if (filterType === 'all') {
                        item.style.display = 'block';
                    } else {
                        const itemType = item.dataset.sessionType;
                        item.style.display = itemType === filterType ? 'block' : 'none';
                    }
                });
            });
        }
    });
});

// Session detail function
function openSessionDetail(sessionId) {
    const userType = '{{ $viewType }}';
    const subdomain = '{{ auth()->user()->academy->subdomain ?? "itqan-academy" }}';
    
    if (userType === 'teacher') {
        window.location.href = `/${subdomain}/teacher/sessions/${sessionId}`;
    } else {
        window.location.href = `/${subdomain}/sessions/${sessionId}`;
    }
}
</script>
