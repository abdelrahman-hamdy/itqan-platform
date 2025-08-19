@props([
    'sessions' => collect(),
    'circle' => null,
    'title' => 'سجل جلسات الحلقة الجماعية',
    'subtitle' => 'آخر 10 جلسات',
    'limit' => 10,
    'viewType' => 'student', // 'student', 'teacher'
    'showAllButton' => true,
    'emptyMessage' => 'لا توجد جلسات مسجلة بعد'
])

@php
    $limitedSessions = $limit ? $sessions->sortByDesc('scheduled_at')->take($limit) : $sessions->sortByDesc('scheduled_at');
    $studentCount = $circle ? ($circle->students ? $circle->students->count() : 0) : 0;
@endphp

<!-- Enhanced Group Sessions List -->
@if($sessions->count() > 0)
    <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-6">
        <div class="flex items-center justify-between mb-6">
            <h3 class="text-xl font-bold text-gray-900">{{ $title }}</h3>
            <div class="flex items-center space-x-2 space-x-reverse">
                @if($subtitle)
                    <span class="text-sm bg-green-100 text-green-700 px-3 py-1 rounded-full font-medium">
                        {{ $subtitle }}
                    </span>
                @endif
                @if($viewType === 'teacher')
                    <div class="flex space-x-1 space-x-reverse">
                        <button id="filterAllSessions" class="px-3 py-1 text-xs border rounded-full border-primary-200 text-primary-700 bg-primary-50">
                            الكل
                        </button>
                        <button id="filterScheduledSessions" class="px-3 py-1 text-xs border rounded-full border-gray-200 text-gray-700">
                            مجدولة
                        </button>
                        <button id="filterCompletedSessions" class="px-3 py-1 text-xs border rounded-full border-gray-200 text-gray-700">
                            مكتملة
                        </button>
                        <button id="filterCancelledSessions" class="px-3 py-1 text-xs border rounded-full border-gray-200 text-gray-700">
                            ملغاة
                        </button>
                    </div>
                @endif
            </div>
        </div>
        
        <div class="space-y-4">
            @forelse($limitedSessions as $session)
                <div class="session-item attendance-indicator rounded-xl p-6 border border-gray-200 hover:shadow-md transition-all duration-300 cursor-pointer"
                     data-session-type="{{ strtolower($session->status->value) }}"
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
                                            {{ $studentCount }} طالب
                                        </span>
                                    @elseif($session->status === App\Enums\SessionStatus::SCHEDULED)
                                        <span class="bg-blue-100 text-blue-800 text-xs px-2 py-1 rounded-full font-medium">
                                            <i class="ri-group-line ml-1"></i>
                                            {{ $studentCount }} مسجل
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
                                    @elseif($session->duration_minutes)
                                        <span class="flex items-center space-x-1 space-x-reverse">
                                            <i class="ri-timer-line"></i>
                                            <span>{{ $session->duration_minutes }} دقيقة</span>
                                        </span>
                                    @endif
                                </div>
                                
                                <!-- Session Description -->
                                @if($session->description)
                                    <div class="mt-2 text-sm text-gray-700">
                                        <p class="bg-gray-50 text-gray-700 px-3 py-2 rounded-lg">
                                            {{ Str::limit($session->description, 100) }}
                                        </p>
                                    </div>
                                @endif

                                <!-- Group Progress Info (for completed sessions) -->
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
                                
                                <!-- Group Performance Indicators -->
                                @if($session->status === App\Enums\SessionStatus::COMPLETED)
                                    <div class="flex flex-col items-end space-y-1 text-xs">
                                        @if($session->recitation_quality)
                                            <span class="bg-purple-100 text-purple-700 px-2 py-1 rounded">
                                                متوسط التلاوة: {{ $session->recitation_quality }}/10
                                            </span>
                                        @endif
                                        @if($session->tajweed_accuracy)
                                            <span class="bg-indigo-100 text-indigo-700 px-2 py-1 rounded">
                                                متوسط التجويد: {{ $session->tajweed_accuracy }}/10
                                            </span>
                                        @endif
                                        @if($session->attendance_count || $studentCount > 0)
                                            @php
                                                $attendanceCount = $session->attendance_count ?? $studentCount;
                                                $attendanceRate = $studentCount > 0 ? round(($attendanceCount / $studentCount) * 100) : 0;
                                            @endphp
                                            <span class="bg-emerald-100 text-emerald-700 px-2 py-1 rounded">
                                                الحضور: {{ $attendanceCount }}/{{ $studentCount }} ({{ $attendanceRate }}%)
                                            </span>
                                        @endif
                                    </div>
                                @elseif($session->status === App\Enums\SessionStatus::SCHEDULED)
                                    <div class="text-xs text-blue-600">
                                        <span class="bg-blue-50 text-blue-700 px-2 py-1 rounded">
                                            {{ $studentCount }} طالب مسجل
                                        </span>
                                    </div>
                                @endif
                            </div>
                        </div>
                    </div>

                    <!-- Quick Actions for Teachers -->
                    @if($viewType === 'teacher' && $session->status === App\Enums\SessionStatus::SCHEDULED)
                        <div class="mt-4 pt-4 border-t border-gray-100">
                            <div class="flex items-center justify-end space-x-2 space-x-reverse">
                                <button onclick="event.stopPropagation(); editSession({{ $session->id }})" 
                                        class="px-3 py-1 text-xs bg-blue-100 text-blue-700 rounded-full hover:bg-blue-200 transition-colors">
                                    <i class="ri-edit-line ml-1"></i>
                                    تعديل
                                </button>
                                <button onclick="event.stopPropagation(); cancelSession({{ $session->id }})" 
                                        class="px-3 py-1 text-xs bg-red-100 text-red-700 rounded-full hover:bg-red-200 transition-colors">
                                    <i class="ri-close-line ml-1"></i>
                                    إلغاء
                                </button>
                                <button onclick="event.stopPropagation(); startSession({{ $session->id }})" 
                                        class="px-3 py-1 text-xs bg-green-100 text-green-700 rounded-full hover:bg-green-200 transition-colors">
                                    <i class="ri-play-line ml-1"></i>
                                    بدء الجلسة
                                </button>
                            </div>
                        </div>
                    @endif
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
            @if($viewType === 'teacher')
                <button type="button" onclick="openScheduleModal()" 
                        class="mt-4 inline-flex items-center px-6 py-3 bg-primary-600 text-white text-sm font-medium rounded-lg hover:bg-primary-700 transition-colors">
                    <i class="ri-calendar-add-line ml-2"></i>
                    جدولة أول جلسة
                </button>
            @endif
        </div>
    </div>
@endif

<script>
function openSessionDetail(sessionId) {
    const userType = '{{ $viewType }}';
    
    @if(auth()->check())
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

function showAllSessions() {
    // This could expand the view or redirect to a full sessions page
    alert('سيتم تنفيذ عرض جميع الجلسات قريباً');
}

// Teacher-specific session actions
@if($viewType === 'teacher')
function editSession(sessionId) {
    alert('سيتم تنفيذ تعديل الجلسة قريباً - جلسة رقم: ' + sessionId);
}

function cancelSession(sessionId) {
    if (confirm('هل أنت متأكد من إلغاء هذه الجلسة؟')) {
        // Here would be the actual cancel logic
        alert('سيتم تنفيذ إلغاء الجلسة قريباً - جلسة رقم: ' + sessionId);
    }
}

function startSession(sessionId) {
    const sessionUrl = '{{ route("meetings.join", ["subdomain" => auth()->user()->academy->subdomain ?? "itqan-academy", "session" => "SESSION_ID_PLACEHOLDER"]) }}';
    const finalUrl = sessionUrl.replace('SESSION_ID_PLACEHOLDER', sessionId);
    window.location.href = finalUrl;
}
@endif
</script>
