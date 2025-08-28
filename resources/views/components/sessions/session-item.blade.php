@props(['session', 'viewType' => 'student'])

@php
    use App\Enums\SessionStatus;
    
    // Get enhanced status data
    $statusData = $session->getStatusDisplayData();
    
    // Determine if session is clickable
    $isClickable = true; // All sessions should be viewable
    
    // Get status-specific styling and info
    $statusColor = $statusData['color'];
    $statusIcon = $statusData['icon'];
    $statusLabel = $statusData['label'];
@endphp

<div class="attendance-indicator rounded-xl p-6 border border-gray-200 hover:shadow-md transition-all duration-300 {{ $isClickable ? 'cursor-pointer' : 'cursor-default' }}"
     @if($isClickable) onclick="openSessionDetail({{ $session->id }})" @endif>
    <div class="flex items-center justify-between">
        <!-- Session Info -->
        <div class="flex items-center space-x-4 space-x-reverse">
            <!-- Session Number -->
            <div class="flex-shrink-0">
                <div class="relative">
                    <span class="inline-flex items-center justify-center w-10 h-10 rounded-xl text-sm font-bold shadow-sm
                        bg-gradient-to-r from-{{ $statusColor }}-400 to-{{ $statusColor }}-500 text-white">
                        {{ $session->session_sequence ?? '#' }}
                    </span>
                    
                    <!-- Status indicator overlay -->
                    @if($session->status === SessionStatus::COMPLETED)
                        <div class="absolute -top-1 -right-1 w-4 h-4 bg-green-500 rounded-full flex items-center justify-center">
                            <i class="ri-check-line text-white text-xs"></i>
                        </div>
                    @elseif($session->status === SessionStatus::ONGOING)
                        <div class="absolute -top-1 -right-1 w-4 h-4 bg-orange-500 rounded-full flex items-center justify-center animate-pulse">
                            <i class="ri-live-line text-white text-xs"></i>
                        </div>
                    @elseif($session->status === SessionStatus::READY)
                        <div class="absolute -top-1 -right-1 w-4 h-4 bg-green-400 rounded-full flex items-center justify-center">
                            <i class="ri-video-line text-white text-xs"></i>
                        </div>
                    @elseif($session->status === SessionStatus::ABSENT)
                        <div class="absolute -top-1 -right-1 w-4 h-4 bg-red-500 rounded-full flex items-center justify-center">
                            <i class="ri-user-x-line text-white text-xs"></i>
                        </div>
                    @elseif($session->status === SessionStatus::SCHEDULED)
                        <div class="absolute -top-1 -right-1 w-4 h-4 bg-blue-500 rounded-full flex items-center justify-center">
                            <i class="ri-calendar-line text-white text-xs"></i>
                        </div>
                    @endif
                </div>
            </div>
            
            <!-- Session Details -->
            <div class="flex-1">
                <div class="flex items-center space-x-3 space-x-reverse mb-2">
                    <h4 class="font-bold text-gray-900 text-lg">{{ $session->title ?? 'جلسة قرآنية' }}</h4>
                    
                    <!-- Progress badge for completed sessions -->
                    @if($session->status === SessionStatus::COMPLETED && ($session->papers_memorized_today || $session->verses_memorized_today))
                        <span class="bg-green-100 text-green-800 text-xs px-2 py-1 rounded-full font-medium">
                            @if($session->papers_memorized_today)
                                +{{ $session->papers_memorized_today }} وجه
                            @elseif($session->verses_memorized_today)
                                +{{ $session->verses_memorized_today }} آية
                            @endif
                        </span>
                    @endif
                    
                    <!-- Live indicator for ongoing sessions -->
                    @if($session->status === SessionStatus::ONGOING)
                        <span class="bg-red-100 text-red-800 text-xs px-2 py-1 rounded-full font-medium animate-pulse flex items-center gap-1">
                            <div class="w-2 h-2 bg-red-500 rounded-full"></div>
                            مباشرة الآن
                        </span>
                    @endif
                    
                    <!-- Ready indicator -->
                    @if($session->status === SessionStatus::READY)
                        <span class="bg-green-100 text-green-800 text-xs px-2 py-1 rounded-full font-medium flex items-center gap-1">
                            <i class="ri-video-line"></i>
                            جاهزة للانضمام
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
                            <i class="ri-timer-2-line"></i>
                            <span>{{ $session->duration_minutes }} دقيقة مخطط</span>
                        </span>
                    @endif
                </div>
                
                <!-- Progress in Session -->
                @if($session->status === SessionStatus::COMPLETED && ($session->current_page || $session->current_surah))
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

                <!-- Meeting timing info for active sessions -->
                @if(in_array($session->status, [SessionStatus::SCHEDULED, SessionStatus::READY, SessionStatus::ONGOING]))
                    @php
                        $circle = $session->session_type === 'individual' ? $session->individualCircle : $session->circle;
                        $preparationMinutes = $circle?->preparation_minutes ?? 15;
                    @endphp
                    
                    @if($session->status === SessionStatus::SCHEDULED && $session->scheduled_at)
                        @php
                            $minutesUntilReady = now()->diffInMinutes($session->scheduled_at->copy()->subMinutes($preparationMinutes), false);
                        @endphp
                        
                        @if($minutesUntilReady > 0)
                            <div class="mt-2 text-xs text-amber-600 bg-amber-50 px-2 py-1 rounded">
                                <i class="ri-timer-line"></i>
                                سيتم تحضير الاجتماع خلال {{ ceil($minutesUntilReady) }} دقيقة
                            </div>
                        @elseif($minutesUntilReady <= 0 && $minutesUntilReady > -60)
                            <div class="mt-2 text-xs text-blue-600 bg-blue-50 px-2 py-1 rounded">
                                <i class="ri-settings-3-line animate-spin"></i>
                                جاري تحضير الاجتماع...
                            </div>
                        @endif
                    @endif
                @endif
            </div>
        </div>
        
        <!-- Session Status and Actions -->
        <div class="text-left">
            <div class="flex flex-col items-end space-y-2">
                <!-- Status Badge -->
                <span class="inline-flex items-center px-3 py-1.5 rounded-lg text-xs font-semibold shadow-sm
                    bg-gradient-to-r from-{{ $statusColor }}-100 to-{{ $statusColor }}-200 text-{{ $statusColor }}-800 border border-{{ $statusColor }}-300">
                    <i class="{{ $statusIcon }} ml-1"></i>
                    {{ $statusLabel }}
                </span>
                
                <!-- Action Button for active sessions -->
                @if(in_array($session->status, [SessionStatus::READY, SessionStatus::ONGOING]) && $viewType === 'student')
                    <a href="{{ route('student.sessions.show', ['subdomain' => auth()->user()->academy->subdomain ?? 'itqan-academy', 'sessionId' => $session->id]) }}" 
                       onclick="event.stopPropagation()"
                       class="inline-flex items-center px-3 py-1.5 bg-gradient-to-r from-green-500 to-green-600 text-white text-xs font-semibold rounded-lg hover:from-green-600 hover:to-green-700 transition-all duration-200 shadow-sm hover:shadow-md">
                        <i class="ri-video-line ml-1"></i>
                        {{ $session->status === SessionStatus::ONGOING ? 'انضمام الآن' : 'انضم للجلسة' }}
                    </a>
                @endif
                
                <!-- Performance Indicators for completed sessions -->
                @if($session->status === SessionStatus::COMPLETED && ($session->recitation_quality || $session->tajweed_accuracy))
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

                <!-- Attendance info for completed individual sessions -->
                @if($session->status === SessionStatus::COMPLETED && $session->session_type === 'individual' && $session->attendance_status)
                    <div class="text-xs text-gray-600">
                        الحضور: 
                        @switch($session->attendance_status)
                            @case('attended')
                                <span class="text-green-600 font-medium">حضر</span>
                                @break
                            @case('late')
                                <span class="text-yellow-600 font-medium">متأخر</span>
                                @break
                            @case('left_early')
                                <span class="text-orange-600 font-medium">غادر مبكراً</span>
                                @break
                            @case('absent')
                                <span class="text-red-600 font-medium">غائب</span>
                                @break
                        @endswitch
                    </div>
                @endif

                <!-- Absent status info -->
                @if($session->status === SessionStatus::ABSENT)
                    <div class="text-xs text-red-600 bg-red-50 px-2 py-1 rounded">
                        <i class="ri-information-line"></i>
                        تم احتساب الجلسة من الاشتراك
                    </div>
                @endif
            </div>
        </div>
    </div>
</div>
