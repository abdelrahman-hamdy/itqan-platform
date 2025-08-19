@props([
    'circle',
    'sessions',
    'viewType' => 'student' // 'student' or 'teacher'
])

<div class="bg-white rounded-xl shadow-sm mb-8">
    <div class="p-6 border-b border-gray-200">
        <div class="flex items-center justify-between mb-6">
            <h3 class="text-xl font-bold text-gray-900">
                <i class="ri-calendar-line text-primary-600 ml-2"></i>
                جلسات الحلقة
            </h3>
            @if($viewType === 'teacher')
                <a href="{{ url('') }}/teacher-panel/{{ auth()->user()->academy->id }}/calendar" target="_blank" class="px-4 py-2 text-sm font-medium text-white bg-primary-600 rounded-lg hover:bg-primary-700 transition-colors inline-flex items-center">
                    <i class="ri-calendar-line ml-1"></i>
                    جدولة جلسة
                </a>
            @endif
        </div>

        <!-- Integrated Stats Overview -->
        <div class="grid grid-cols-2 lg:grid-cols-4 gap-4 mb-6">
            <div class="flex items-center justify-between p-4 bg-gradient-to-r from-blue-50 to-blue-100 rounded-xl border border-blue-200">
                <div>
                    <p class="text-sm font-medium text-blue-700">إجمالي الجلسات</p>
                    <p class="text-2xl font-bold text-blue-900">{{ $circle->total_sessions }}</p>
                </div>
                <div class="p-2 bg-blue-200 rounded-lg">
                    <i class="ri-book-line text-xl text-blue-600"></i>
                </div>
            </div>
            
            <div class="flex items-center justify-between p-4 bg-gradient-to-r from-green-50 to-green-100 rounded-xl border border-green-200">
                <div>
                    <p class="text-sm font-medium text-green-700">المكتملة</p>
                    <p class="text-2xl font-bold text-green-900">{{ $circle->sessions_completed }}</p>
                </div>
                <div class="p-2 bg-green-200 rounded-lg">
                    <i class="ri-checkbox-circle-line text-xl text-green-600"></i>
                </div>
            </div>
            
            <div class="flex items-center justify-between p-4 bg-gradient-to-r from-orange-50 to-orange-100 rounded-xl border border-orange-200">
                <div>
                    <p class="text-sm font-medium text-orange-700">المجدولة</p>
                    <p class="text-2xl font-bold text-orange-900">{{ $circle->sessions_scheduled }}</p>
                </div>
                <div class="p-2 bg-orange-200 rounded-lg">
                    <i class="ri-calendar-check-line text-xl text-orange-600"></i>
                </div>
            </div>
            
            <div class="flex items-center justify-between p-4 bg-gradient-to-r from-purple-50 to-purple-100 rounded-xl border border-purple-200">
                <div>
                    <p class="text-sm font-medium text-purple-700">المتبقية</p>
                    <p class="text-2xl font-bold text-purple-900">{{ $circle->sessions_remaining }}</p>
                </div>
                <div class="p-2 bg-purple-200 rounded-lg">
                    <i class="ri-time-line text-xl text-purple-600"></i>
                </div>
            </div>
        </div>

        <!-- Progress Bar -->
        @if($circle->progress_percentage > 0)
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
                    <span>{{ $circle->sessions_completed }} مكتملة</span>
                    <span>{{ $circle->sessions_remaining }} متبقية</span>
                </div>
            </div>
        @endif

        <!-- Session Filters -->
        <div class="flex flex-wrap items-center gap-2">
            <span class="text-sm font-medium text-gray-700 ml-2">تصفية الجلسات:</span>
            <button type="button" id="filterAllSessions" class="px-4 py-2 text-sm font-medium rounded-lg border-2 border-primary-200 text-primary-700 bg-primary-50 transition-all duration-200">
                <i class="ri-list-check ml-1"></i>
                الكل
            </button>
            <button type="button" id="filterScheduledSessions" class="px-4 py-2 text-sm font-medium rounded-lg border-2 border-gray-200 text-gray-700 bg-white hover:bg-gray-50 hover:border-gray-300 transition-all duration-200">
                <i class="ri-calendar-check-line ml-1"></i>
                المجدولة
            </button>
            <button type="button" id="filterCompletedSessions" class="px-4 py-2 text-sm font-medium rounded-lg border-2 border-gray-200 text-gray-700 bg-white hover:bg-gray-50 hover:border-gray-300 transition-all duration-200">
                <i class="ri-checkbox-circle-line ml-1"></i>
                المكتملة
            </button>
            <button type="button" id="filterUnscheduledSessions" class="px-4 py-2 text-sm font-medium rounded-lg border-2 border-gray-200 text-gray-700 bg-white hover:bg-gray-50 hover:border-gray-300 transition-all duration-200">
                <i class="ri-time-line ml-1"></i>
                غير مجدولة
            </button>
            <button type="button" id="filterTemplateSessions" class="px-4 py-2 text-sm font-medium rounded-lg border-2 border-gray-200 text-gray-700 bg-white hover:bg-gray-50 hover:border-gray-300 transition-all duration-200">
                <i class="ri-draft-line ml-1"></i>
                القوالب
            </button>
        </div>
    </div>
    
    <div class="p-6">
        @if($sessions && $sessions->count() > 0)
            <div class="space-y-4" id="sessionsList">
                @foreach($sessions->sortByDesc(function($session) {
                    // Sort by date (recent to older)
                    return $session->scheduled_at ?? $session->created_at;
                }) as $session)
                    @php
                        $statusData = $session->getStatusDisplayData();
                        $isClickable = in_array($session->status->value, ['completed', 'scheduled', 'ongoing']);
                        $isUpcoming = $statusData['is_upcoming'];
                    @endphp
                    <div class="session-item bg-white border border-gray-200 rounded-xl p-5 transition-all duration-200 {{ $isClickable ? 'hover:border-primary-300 hover:shadow-md cursor-pointer transform hover:-translate-y-1' : 'opacity-60 cursor-not-allowed' }}
                        {{ $isUpcoming ? 'ring-2 ring-blue-200 border-blue-300' : '' }}"
                         data-session-type="{{ $session->status->value }}"
                         data-session-id="{{ $session->id }}"
                         @if($isClickable) onclick="openSessionDetail({{ $session->id }})" @endif>
                        <div class="flex items-center justify-between">
                            <div class="flex items-center space-x-3 space-x-reverse">
                                <div class="flex-shrink-0">
                                    <div class="relative">
                                        <span class="inline-flex items-center justify-center w-10 h-10 rounded-xl text-sm font-bold shadow-sm
                                            {{ $session->status === App\Enums\SessionStatus::COMPLETED ? 'bg-gradient-to-r from-green-400 to-green-500 text-white' : 
                                               ($session->status === App\Enums\SessionStatus::SCHEDULED ? 'bg-gradient-to-r from-blue-400 to-blue-500 text-white' : 
                                               ($session->status === App\Enums\SessionStatus::UNSCHEDULED ? 'bg-gradient-to-r from-yellow-400 to-yellow-500 text-white' : 'bg-gradient-to-r from-gray-300 to-gray-400 text-gray-700')) }}">
                                            {{ $session->session_sequence }}
                                        </span>
                                        @if($session->status === App\Enums\SessionStatus::COMPLETED)
                                            <div class="absolute -top-1 -right-1 w-4 h-4 bg-green-500 rounded-full flex items-center justify-center">
                                                <i class="ri-check-line text-white text-xs"></i>
                                            </div>
                                        @endif
                                    </div>
                                </div>
                                <div>
                                    <h4 class="text-lg font-semibold text-gray-900 mb-1">{{ $session->title }}</h4>
                                    <div class="flex items-center space-x-4 space-x-reverse text-sm">
                                        @if($session->status === App\Enums\SessionStatus::SCHEDULED && $session->scheduled_at)
                                            <span class="text-sm text-gray-600">
                                                <i class="ri-calendar-line ml-1"></i>
                                                {{ $session->scheduled_at->format('l، d F Y - H:i') }}
                                            </span>
                                        @elseif($session->status === App\Enums\SessionStatus::COMPLETED && $session->ended_at)
                                            <span class="text-sm text-gray-600">
                                                <i class="ri-check-line ml-1"></i>
                                                اكتملت {{ $session->ended_at->diffForHumans() }}
                                            </span>
                                        @elseif($session->status === App\Enums\SessionStatus::UNSCHEDULED)
                                            <span class="text-sm text-yellow-600">
                                                <i class="ri-time-line ml-1"></i>
                                                في انتظار الجدولة
                                            </span>
                                        @else
                                            <span class="text-{{ $statusData['color'] }}-600">
                                                <i class="{{ $statusData['icon'] }} ml-1"></i>
                                                {{ $statusData['label'] }}
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
                            
                            <div class="flex items-center space-x-3 space-x-reverse">
                                <!-- Status Badge -->
                                <span class="inline-flex items-center px-3 py-1.5 rounded-lg text-xs font-semibold shadow-sm
                                    bg-gradient-to-r from-{{ $statusData['color'] }}-100 to-{{ $statusData['color'] }}-200 text-{{ $statusData['color'] }}-800 border border-{{ $statusData['color'] }}-300">
                                    <i class="{{ $statusData['icon'] }} ml-1"></i>
                                    {{ $statusData['label'] }}
                                </span>
                                
                                <!-- Action Buttons -->
                                @if($session->status === App\Enums\SessionStatus::SCHEDULED && $session->scheduled_at && $session->scheduled_at->isFuture())
                                    @php
                                        $minutesUntilSession = now()->diffInMinutes($session->scheduled_at);
                                        $canJoin = $minutesUntilSession <= 30; // Can join 30 minutes before
                                    @endphp
                                    
                                    @if($canJoin)
                                        <a href="{{ route('meetings.join', ['subdomain' => auth()->user()->academy->subdomain ?? 'itqan-academy', 'session' => $session->id]) }}" 
                                           onclick="event.stopPropagation()"
                                           class="inline-flex items-center px-4 py-2 bg-gradient-to-r from-green-500 to-green-600 text-white text-sm font-semibold rounded-lg hover:from-green-600 hover:to-green-700 transition-all duration-200 shadow-sm hover:shadow-md transform hover:scale-105">
                                            <i class="ri-video-line ml-1"></i>
                                            انضمام للجلسة
                                        </a>
                                    @else
                                        <span class="text-xs text-gray-500">
                                            متاح خلال {{ $minutesUntilSession }} دقيقة
                                        </span>
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
            <div class="text-center py-12">
                <div class="mx-auto w-24 h-24 bg-gradient-to-br from-gray-100 to-gray-200 rounded-full flex items-center justify-center mb-6">
                    <i class="ri-calendar-line text-3xl text-gray-400"></i>
                </div>
                <h3 class="text-lg font-semibold text-gray-900 mb-2">لا توجد جلسات متاحة</h3>
                <p class="text-sm text-gray-500 mb-4">ستظهر الجلسات هنا عند إنشائها أو جدولتها</p>
                @if($viewType === 'teacher')
                    <a href="{{ url('') }}/teacher-panel/{{ auth()->user()->academy->id }}/calendar" target="_blank" 
                        class="inline-flex items-center px-6 py-3 bg-primary-600 text-white text-sm font-medium rounded-lg hover:bg-primary-700 transition-colors shadow-sm">
                        <i class="ri-calendar-line ml-2"></i>
                        إنشاء جلسة جديدة
                    </a>
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
