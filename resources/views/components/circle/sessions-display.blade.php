@props([
    'sessions' => collect(),
    'circle' => null,
    'viewType' => 'student', // 'student', 'teacher'
    'context' => 'individual', // 'individual', 'group', 'progress'
    'showStats' => true,
    'showFilters' => true,
    'showActions' => true,
    'showProgress' => true,
    'title' => 'جلسات الحلقة',
    'emptyMessage' => 'لا توجد جلسات متاحة',
    'emptyDescription' => 'ستظهر الجلسات هنا عند إنشائها أو جدولتها',
    'variant' => 'default' // 'default', 'compact', 'detailed'
])

@php
    // Prepare stats based on context
    $stats = [];
    if ($showStats && $circle) {
        switch ($context) {
            case 'individual':
                $stats = [
                    ['label' => 'إجمالي الجلسات', 'value' => $circle->total_sessions, 'icon' => 'ri-book-line', 'color' => 'blue'],
                    ['label' => 'المكتملة', 'value' => $circle->sessions_completed, 'icon' => 'ri-checkbox-circle-line', 'color' => 'green'],
                    ['label' => 'المجدولة', 'value' => $circle->sessions_scheduled, 'icon' => 'ri-calendar-check-line', 'color' => 'orange'],
                    ['label' => 'المتبقية', 'value' => $circle->sessions_remaining, 'icon' => 'ri-time-line', 'color' => 'purple']
                ];
                break;
            case 'group':
                $stats = [
                    ['label' => 'عدد الطلاب', 'value' => $circle->students->count(), 'icon' => 'ri-user-line', 'color' => 'blue'],
                    ['label' => 'الجلسات المكتملة', 'value' => $circle->sessions->where('status', 'completed')->count(), 'icon' => 'ri-calendar-check-line', 'color' => 'green'],
                    ['label' => 'متوسط الحضور', 'value' => '85%', 'icon' => 'ri-star-line', 'color' => 'yellow'],
                    ['label' => 'الأماكن المتاحة', 'value' => max(0, $circle->max_students - $circle->students->count()), 'icon' => 'ri-user-add-line', 'color' => 'purple']
                ];
                break;
        }
    }

    // Filter options
    $filters = [
        'all' => ['label' => 'الكل', 'icon' => 'ri-list-check'],
        'scheduled' => ['label' => 'المجدولة', 'icon' => 'ri-calendar-check-line'],
        'completed' => ['label' => 'المكتملة', 'icon' => 'ri-checkbox-circle-line'],
        'unscheduled' => ['label' => 'غير مجدولة', 'icon' => 'ri-time-line'],
        'template' => ['label' => 'القوالب', 'icon' => 'ri-draft-line']
    ];

    // Sort sessions by date (recent to later - ascending)
    $sortedSessions = $sessions->sortBy(function($session) {
        return $session->scheduled_at ?? $session->created_at;
    });
@endphp

<div class="bg-white rounded-xl shadow-sm border border-gray-200 mb-8">
    <div class="p-6 border-b border-gray-200">
        <!-- Header -->
        <div class="flex items-center justify-between mb-6">
            <h3 class="text-xl font-bold text-gray-900">
                <i class="ri-calendar-line text-primary-600 ml-2"></i>
                {{ $title }}
            </h3>
            @if($showActions && $viewType === 'teacher' && in_array($context, ['individual', 'group']))
                <button type="button" onclick="openScheduleModal()" 
                    class="px-4 py-2 text-sm font-medium text-white bg-primary-600 rounded-lg hover:bg-primary-700 transition-colors">
                    <i class="ri-add-line ml-1"></i>
                    جدولة جلسة
                </button>
            @endif
        </div>

        <!-- Stats Grid -->
        @if($showStats && !empty($stats))
            <x-circle.stats-grid :stats="$stats" class="mb-6" />
        @endif

        <!-- Progress Bar -->
        @if($showProgress && $circle && $circle->progress_percentage > 0)
            <div class="bg-gradient-to-r from-gray-50 to-gray-100 rounded-xl p-4 mb-6 border border-gray-200">
                <div class="flex items-center justify-between mb-3">
                    <span class="text-sm font-semibold text-gray-700 flex items-center">
                        <i class="ri-line-chart-line text-primary-600 ml-2"></i>
                        نسبة الإنجاز
                    </span>
                    <span class="text-lg font-bold text-primary-600">{{ number_format($circle->progress_percentage, 1) }}%</span>
                </div>
                <div class="w-full bg-gray-200 rounded-full h-3 overflow-hidden">
                    <div class="bg-gradient-to-r from-primary-500 to-primary-600 h-3 rounded-full transition-all duration-500 ease-out shadow-sm" 
                         style="width: {{ $circle->progress_percentage }}%"></div>
                </div>
                <div class="flex justify-between mt-2 text-xs text-gray-600">
                    <span>{{ $circle->sessions_completed ?? 0 }} مكتملة</span>
                    <span>{{ $circle->sessions_remaining ?? 0 }} متبقية</span>
                </div>
            </div>
        @endif

        <!-- Session Filters -->
        @if($showFilters)
            <div class="flex flex-wrap items-center gap-2">
                <span class="text-sm font-medium text-gray-700 ml-2">تصفية الجلسات:</span>
                @foreach($filters as $key => $filter)
                    <button type="button" 
                        id="filter{{ ucfirst($key) }}Sessions" 
                        class="px-4 py-2 text-sm font-medium rounded-lg border-2 transition-all duration-200 
                            {{ $key === 'all' ? 'border-primary-200 text-primary-700 bg-primary-50' : 'border-gray-200 text-gray-700 bg-white hover:bg-gray-50 hover:border-gray-300' }}">
                        <i class="{{ $filter['icon'] }} ml-1"></i>
                        {{ $filter['label'] }}
                    </button>
                @endforeach
            </div>
        @endif
    </div>
    
    <!-- Sessions List -->
    <div class="p-6">
        @if($sortedSessions && $sortedSessions->count() > 0)
            <div class="space-y-4" id="sessionsList">
                @foreach($sortedSessions as $session)
                    @php
                        $statusData = $session->getStatusDisplayData();
                        $isClickable = in_array($session->status, ['completed', 'scheduled', 'ongoing']);
                        $isUpcoming = $statusData['is_upcoming'];
                    @endphp
                    
                    <div class="session-item bg-white border border-gray-200 rounded-lg p-5 transition-all duration-200 
                        {{ $isClickable ? 'hover:border-primary-300 hover:shadow-sm cursor-pointer' : 'opacity-60 cursor-not-allowed' }}
                        {{ $isUpcoming ? 'ring-2 ring-blue-200 border-blue-300' : '' }}"
                         data-session-type="{{ $session->status }}"
                         data-session-id="{{ $session->id }}"
                         @if($isClickable) onclick="openSessionDetail({{ $session->id }})" @endif>
                        
                        <div class="flex items-center justify-between">
                            <div class="flex items-center space-x-3 space-x-reverse">
                                <!-- Session Number -->
                                <div class="flex-shrink-0">
                                    <div class="relative">
                                        <span class="inline-flex items-center justify-center w-10 h-10 rounded-xl text-sm font-bold shadow-sm
                                            bg-gradient-to-r from-{{ $statusData['color'] }}-400 to-{{ $statusData['color'] }}-500 text-white">
                                            {{ $session->session_sequence ?? '#' }}
                                        </span>
                                                                @if($isUpcoming)
                            <div class="absolute -top-1 -right-1 w-4 h-4 bg-blue-500 rounded-full flex items-center justify-center">
                                <i class="ri-calendar-line text-white text-xs"></i>
                            </div>
                        @elseif($session->status === App\Enums\SessionStatus::COMPLETED)
                            <div class="absolute -top-1 -right-1 w-4 h-4 bg-green-500 rounded-full flex items-center justify-center">
                                <i class="ri-check-line text-white text-xs"></i>
                            </div>
                        @elseif($session->status === App\Enums\SessionStatus::READY)
                            <div class="absolute -top-1 -right-1 w-4 h-4 bg-green-400 rounded-full flex items-center justify-center">
                                <i class="ri-video-line text-white text-xs"></i>
                            </div>
                        @elseif($session->status === App\Enums\SessionStatus::ONGOING)
                            <div class="absolute -top-1 -right-1 w-4 h-4 bg-orange-500 rounded-full flex items-center justify-center animate-pulse">
                                <i class="ri-live-line text-white text-xs"></i>
                            </div>
                        @elseif($session->status === App\Enums\SessionStatus::ABSENT)
                            <div class="absolute -top-1 -right-1 w-4 h-4 bg-red-500 rounded-full flex items-center justify-center">
                                <i class="ri-user-x-line text-white text-xs"></i>
                            </div>
                        @endif
                                    </div>
                                </div>
                                
                                <!-- Session Info -->
                                <div>
                                    <h4 class="text-lg font-semibold text-gray-900 mb-1">{{ $session->title ?? 'جلسة ' . ($session->session_sequence ?? '#') }}</h4>
                                    <div class="flex items-center space-x-4 space-x-reverse text-sm">
                                        @if($isUpcoming)
                                            <span class="inline-flex items-center px-2 py-1 bg-blue-100 text-blue-700 rounded-md font-medium">
                                                <i class="ri-calendar-line ml-1"></i>
                                                جلسة قادمة
                                            </span>
                                        @endif
                                        
                                        @if($session->scheduled_at)
                                            <span class="text-gray-600">
                                                <i class="{{ $statusData['icon'] }} ml-1"></i>
                                                @if($session->status === App\Enums\SessionStatus::COMPLETED && $session->ended_at)
                                                    اكتملت {{ $session->ended_at->diffForHumans() }}
                                                @elseif($session->scheduled_at->isFuture())
                                                    {{ $session->scheduled_at->format('l، d F Y - H:i') }}
                                                @else
                                                    {{ $session->scheduled_at->format('l، d F Y - H:i') }}
                                                @endif
                                            </span>
                                        @else
                                            <span class="text-{{ $statusData['color'] }}-600">
                                                <i class="{{ $statusData['icon'] }} ml-1"></i>
                                                {{ $statusData['label'] }}
                                            </span>
                                        @endif
                                        
                                        <span class="text-gray-500">
                                            <i class="ri-time-line ml-1"></i>
                                            {{ $session->duration_minutes ?? 60 }} دقيقة
                                        </span>

                                        @if($session->lesson_objectives && count($session->lesson_objectives) > 0)
                                            <span class="text-gray-500">
                                                <i class="ri-target-line ml-1"></i>
                                                {{ count($session->lesson_objectives) }} هدف
                                            </span>
                                        @endif
                                    </div>
                                </div>
                            </div>
                            
                            <div class="flex items-center space-x-3 space-x-reverse">
                                <!-- Status Badge -->
                                <span class="inline-flex items-center px-3 py-1.5 rounded-lg text-xs font-semibold shadow-sm
                                    bg-gradient-to-r from-{{ $statusData['color'] }}-100 to-{{ $statusData['color'] }}-200 text-{{ $statusData['color'] }}-800 border border-{{ $statusData['color'] }}-300">
                                    <i class="{{ $statusData['icon'] }} ml-1"></i>
                                    {{ $statusData['label'] }}
                                </span>
                                
                                <!-- Action Buttons -->
                                @if($session->status === App\Enums\SessionStatus::READY || $session->status === App\Enums\SessionStatus::ONGOING)
                                    <a href="{{ route('student.sessions.show', ['subdomain' => auth()->user()->academy->subdomain ?? 'itqan-academy', 'sessionId' => $session->id]) }}" 
                                       onclick="event.stopPropagation()"
                                       class="inline-flex items-center px-4 py-2 bg-gradient-to-r from-green-500 to-green-600 text-white text-sm font-semibold rounded-lg hover:from-green-600 hover:to-green-700 transition-all duration-200 shadow-sm hover:shadow-md transform hover:scale-105">
                                        <i class="ri-video-line ml-1"></i>
                                        {{ $session->status === App\Enums\SessionStatus::ONGOING ? 'انضمام للجلسة الجارية' : 'انضمام للجلسة' }}
                                    </a>
                                @elseif($session->status === App\Enums\SessionStatus::SCHEDULED && $session->scheduled_at && $session->scheduled_at->isFuture())
                                    @php
                                        $timeRemaining = humanize_time_remaining_arabic($session->scheduled_at);
                                        $canTestMeeting = can_test_meetings();
                                    @endphp
                                    @if($canTestMeeting)
                                        <div class="flex items-center gap-2">
                                            <span class="text-xs text-gray-500">{{ $timeRemaining['text'] }}</span>
                                            <a href="{{ route('student.sessions.show', ['subdomain' => auth()->user()->academy->subdomain ?? 'itqan-academy', 'sessionId' => $session->id]) }}" 
                                               onclick="event.stopPropagation()"
                                               class="inline-flex items-center px-2 py-1 bg-yellow-100 text-yellow-800 text-xs rounded hover:bg-yellow-200 transition-colors">
                                                <i class="ri-flask-line ml-1"></i>
                                                اختبار
                                            </a>
                                        </div>
                                    @else
                                        <span class="text-xs text-gray-500">{{ $timeRemaining['text'] }}</span>
                                    @endif
                                @elseif($session->status === App\Enums\SessionStatus::COMPLETED)
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

                        <!-- Session Description/Notes -->
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

                        <!-- Progress Context: Attendance Status -->
                        @if($context === 'progress' && $session->status === App\Enums\SessionStatus::COMPLETED)
                            <div class="mt-3 pt-3 border-t border-gray-100">
                                <div class="flex items-center space-x-4 space-x-reverse">
                                    <div class="flex items-center">
                                        @if($session->attendance_status === 'attended')
                                            <div class="w-3 h-3 bg-green-500 rounded-full ml-2"></div>
                                            <span class="text-sm text-green-600 font-medium">حضر</span>
                                        @elseif($session->attendance_status === 'late')
                                            <div class="w-3 h-3 bg-yellow-500 rounded-full ml-2"></div>
                                            <span class="text-sm text-yellow-600 font-medium">متأخر</span>
                                        @elseif($session->attendance_status === 'absent')
                                            <div class="w-3 h-3 bg-red-500 rounded-full ml-2"></div>
                                            <span class="text-sm text-red-600 font-medium">غائب</span>
                                        @endif
                                    </div>
                                    @if($session->actual_duration_minutes)
                                        <span class="text-sm text-gray-600">المدة الفعلية: {{ $session->actual_duration_minutes }} دقيقة</span>
                                    @endif
                                </div>
                            </div>
                        @endif
                    </div>
                @endforeach
            </div>
        @else
            <div class="text-center py-12">
                <div class="mx-auto w-24 h-24 bg-gradient-to-br from-gray-100 to-gray-200 rounded-full flex items-center justify-center mb-6">
                    <i class="ri-calendar-line text-3xl text-gray-400"></i>
                </div>
                <h3 class="text-lg font-semibold text-gray-900 mb-2">{{ $emptyMessage }}</h3>
                <p class="text-sm text-gray-500 mb-4">{{ $emptyDescription }}</p>
                @if($showActions && $viewType === 'teacher' && in_array($context, ['individual', 'group']))
                    <button type="button" onclick="openScheduleModal()" 
                        class="inline-flex items-center px-6 py-3 bg-primary-600 text-white text-sm font-medium rounded-lg hover:bg-primary-700 transition-colors shadow-sm">
                        <i class="ri-add-line ml-2"></i>
                        إنشاء جلسة جديدة
                    </button>
                @endif
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
                        btn.classList.add('border-gray-200', 'text-gray-700', 'bg-white');
                    }
                });
                
                this.classList.add('border-primary-200', 'text-primary-700', 'bg-primary-50');
                this.classList.remove('border-gray-200', 'text-gray-700', 'bg-white');
                
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
    @if(auth()->check())
        const userType = '{{ $viewType }}';
        
        if (userType === 'teacher') {
            // Use Laravel route helper to generate correct URL for teachers
            const sessionUrl = '{{ route("teacher.sessions.show", ["subdomain" => auth()->user()->academy->subdomain ?? "itqan-academy", "sessionId" => "SESSION_ID_PLACEHOLDER"]) }}';
            const finalUrl = sessionUrl.replace('SESSION_ID_PLACEHOLDER', sessionId);
            
            console.log('Teacher Session URL:', finalUrl);
            window.location.href = finalUrl;
        } else {
            // Use Laravel route helper to generate correct URL for students
            const sessionUrl = '{{ route("student.sessions.show", ["subdomain" => auth()->user()->academy->subdomain ?? "itqan-academy", "sessionId" => "SESSION_ID_PLACEHOLDER"]) }}';
            const finalUrl = sessionUrl.replace('SESSION_ID_PLACEHOLDER', sessionId);
            
            console.log('Student Session URL:', finalUrl);
            window.location.href = finalUrl;
        }
    @else
        console.error('User not authenticated');
    @endif
}
</script>
