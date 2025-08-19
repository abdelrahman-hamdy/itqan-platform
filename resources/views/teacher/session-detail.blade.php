<x-layouts.teacher 
    :title="'تفاصيل الجلسة - ' . config('app.name', 'منصة إتقان')"
    :description="'إدارة تفاصيل جلسة القرآن الكريم'">

<div class="max-w-7xl mx-auto">
    <!-- Breadcrumb -->
    <nav class="mb-8">
        <ol class="flex items-center space-x-2 space-x-reverse text-sm text-gray-600">
            <li>
                @if($session->individual_circle_id && $session->individualCircle)
                    <a href="{{ route('teacher.individual-circles.show', ['subdomain' => $academy->subdomain ?? 'itqan-academy', 'circle' => $session->individualCircle->id]) }}" 
                       class="hover:text-primary">الحلقة الفردية</a>
                @elseif($session->circle_id && $session->circle)
                    <a href="{{ route('teacher.group-circles.show', ['subdomain' => $academy->subdomain, 'circle' => $session->circle->id]) }}" 
                       class="hover:text-primary">الحلقة الجماعية</a>
                @else
                    <span class="text-gray-500">الجلسة</span>
                @endif
            </li>
            <li>/</li>
            <li class="text-gray-900">تفاصيل الجلسة</li>
        </ol>
    </nav>

    <div class="grid grid-cols-1 lg:grid-cols-3 gap-8">
        <!-- Main Content -->
        <div class="lg:col-span-2">
            <!-- Session Header -->
            <div class="bg-white rounded-xl shadow-sm p-6 mb-8">
                <div class="flex items-start justify-between mb-6">
                    <div>
                        <h1 class="text-2xl font-bold text-gray-900">{{ $session->title }}</h1>
                        <p class="text-gray-600 mt-1">
                            @if(in_array($session->session_type, ['circle', 'group']) && $session->circle)
                                جلسة جماعية - {{ $session->circle->name }}
                                @if($session->circle->students)
                                    <span class="text-sm">({{ $session->circle->students->count() }} طالب)</span>
                                @endif
                            @elseif($session->student)
                                الجلسة رقم {{ $session->session_sequence ?? 1 }} مع {{ $session->student->name }}
                            @else
                                الجلسة رقم {{ $session->session_sequence ?? 1 }} - الطالب غير محدد
                            @endif
                        </p>
                        @if($session->scheduled_at)
                            <p class="text-sm text-gray-500 mt-2">
                                <i class="ri-calendar-line ml-1"></i>
                                {{ $session->scheduled_at->format('l، d F Y - H:i A') }}
                            </p>
                        @endif
                    </div>
                    
                    <!-- Status Badge -->
                    <span class="inline-flex items-center px-3 py-1 rounded-full text-sm font-medium
                        {{ $session->status === App\Enums\SessionStatus::COMPLETED ? 'bg-green-100 text-green-800' : 
                           ($session->status === App\Enums\SessionStatus::SCHEDULED ? 'bg-blue-100 text-blue-800' : 'bg-gray-100 text-gray-800') }}">
                        @if($session->status === App\Enums\SessionStatus::COMPLETED)
                            <i class="ri-check-line ml-1"></i>
                            مكتملة
                        @elseif($session->status === App\Enums\SessionStatus::SCHEDULED)
                            <i class="ri-calendar-line ml-1"></i>
                            مجدولة
                        @else
                            @php $statusData = $session->getStatusDisplayData(); @endphp
                            <i class="{{ $statusData['icon'] }} ml-1"></i>
                            {{ $statusData['label'] }}
                        @endif
                    </span>
                </div>

                <!-- Session Info -->
                @if($session->description || $session->lesson_objectives)
                    <div class="border-t border-gray-200 pt-6">
                        @if($session->description)
                            <div class="mb-4">
                                <h3 class="font-medium text-gray-900 mb-2">وصف الجلسة</h3>
                                <p class="text-gray-700">{{ $session->description }}</p>
                            </div>
                        @endif
                        
                        @if($session->lesson_objectives && count($session->lesson_objectives) > 0)
                            <div>
                                <h3 class="font-medium text-gray-900 mb-2">أهداف الجلسة</h3>
                                <ul class="list-disc list-inside space-y-1 text-gray-700">
                                    @foreach($session->lesson_objectives as $objective)
                                        <li>{{ $objective }}</li>
                                    @endforeach
                                </ul>
                            </div>
                        @endif
                    </div>
                @endif
            </div>

            <!-- Meeting Room Integration -->
            @if($session->meeting_room_name && $session->meeting_link)
                <div class="bg-white rounded-xl shadow-sm p-6 mb-8">
                    <div class="flex items-center justify-between mb-4">
                        <h3 class="text-lg font-bold text-gray-900">
                            <i class="ri-video-line text-green-600 ml-2"></i>
                            غرفة الاجتماع - {{ $session->meeting_room_name }}
                        </h3>
                        <div class="flex items-center space-x-2 space-x-reverse">
                            <span class="px-3 py-1 bg-green-100 text-green-800 rounded-full text-sm font-medium">
                                <i class="ri-live-line ml-1"></i>
                                جاهز
                            </span>
                        </div>
                    </div>
                    
                    <div class="bg-gray-50 rounded-lg p-4 mb-4">
                        <div class="flex items-center justify-between">
                            <div class="flex items-center space-x-3 space-x-reverse">
                                <div class="w-10 h-10 bg-green-100 rounded-full flex items-center justify-center">
                                    <i class="ri-video-line text-green-600"></i>
                                </div>
                                <div>
                                    <p class="font-medium text-gray-900">غرفة الاجتماع جاهزة</p>
                                    <p class="text-sm text-gray-600">{{ $session->meeting_platform ?? 'LiveKit' }} - تم الإنشاء</p>
                                </div>
                            </div>
                            <div class="flex items-center space-x-2 space-x-reverse">
                                @if($session->scheduled_at)
                                    @php
                                        $timeRemaining = humanize_time_remaining_arabic($session->scheduled_at);
                                        $canTestMeeting = can_test_meetings();
                                    @endphp
                                    @if($timeRemaining['can_join'] || $session->scheduled_at->isPast() || $canTestMeeting)
                                        <div class="flex items-center gap-3">
                                            <button type="button" onclick="startMeeting()" id="startMeetingBtn"
                                                   class="inline-flex items-center px-4 py-2 bg-green-600 text-white font-medium rounded-lg hover:bg-green-700 transition-colors shadow-sm">
                                                <i class="ri-video-line ml-2"></i>
                                                <span id="meetingBtnText">بدء الجلسة</span>
                                            </button>
                                            <button type="button" onclick="endMeeting()" id="endMeetingBtn" style="display: none;"
                                                   class="inline-flex items-center px-4 py-2 bg-red-600 text-white font-medium rounded-lg hover:bg-red-700 transition-colors shadow-sm">
                                                <i class="ri-stop-line ml-2"></i>
                                                إنهاء الاجتماع
                                            </button>
                                            <button type="button" onclick="copyMeetingLink()" 
                                                   class="inline-flex items-center px-4 py-2 bg-gray-100 text-gray-700 font-medium rounded-lg hover:bg-gray-200 transition-colors">
                                                <i class="ri-link ml-2"></i>
                                                نسخ الرابط
                                            </button>
                                            
                                            @if(!$timeRemaining['can_join'] && !$session->scheduled_at->isPast() && $canTestMeeting)
                                                <span class="inline-flex items-center px-2 py-1 bg-yellow-100 text-yellow-800 text-xs rounded">
                                                    <i class="ri-flask-line ml-1"></i>
                                                    وضع الاختبار
                                                </span>
                                            @endif
                                        </div>
                                    @else
                                        <span class="text-sm text-orange-600 font-medium">
                                            <i class="ri-time-line ml-1"></i>
                                            {{ $timeRemaining['text'] }}
                                        </span>
                                    @endif
                                @endif
                            </div>
                        </div>
                    </div>

                    <!-- Meeting Info -->
                    <div class="grid grid-cols-1 md:grid-cols-3 gap-4 text-sm">
                        <div class="flex items-center text-gray-600">
                            <i class="ri-time-line ml-2"></i>
                            <span>المدة: {{ $session->duration_minutes ?? 60 }} دقيقة</span>
                        </div>
                        <div class="flex items-center text-gray-600">
                            <i class="ri-group-line ml-2"></i>
                            <span>أقصى مشاركين: {{ $session->meeting_data['settings']['max_participants'] ?? 50 }}</span>
                        </div>
                        <div class="flex items-center text-gray-600">
                            <i class="ri-shield-check-line ml-2"></i>
                            <span>آمن ومشفر</span>
                        </div>
                    </div>
                </div>
            @endif

            <!-- Embedded Meeting Room -->
            <div id="meetingContainer" class="bg-white rounded-xl shadow-sm p-6 mb-8" style="display: none;">
                <div class="flex items-center justify-between mb-4">
                    <h3 class="text-lg font-bold text-gray-900">
                        <i class="ri-video-on-line text-green-600 ml-2"></i>
                        جلسة فيديو مباشرة
                    </h3>
                    <div class="flex items-center space-x-2 space-x-reverse">
                        <span class="px-3 py-1 bg-green-100 text-green-800 rounded-full text-sm font-medium">
                            <i class="ri-record-circle-line ml-1 animate-pulse"></i>
                            جاري الآن
                        </span>
                        <div id="sessionTimer" class="px-3 py-1 bg-blue-100 text-blue-800 rounded-full text-sm font-medium">
                            <i class="ri-time-line ml-1"></i>
                            <span id="timerDisplay">00:00</span>
                        </div>
                    </div>
                </div>
                
                <!-- Meeting Video Container -->
                <div class="relative bg-gray-900 rounded-lg overflow-hidden" style="height: 500px;">
                    <div id="meetingRoom" class="w-full h-full"></div>
                    
                    <!-- Loading State -->
                    <div id="meetingLoading" class="absolute inset-0 flex items-center justify-center bg-gray-900">
                        <div class="text-center text-white">
                            <div class="animate-spin rounded-full h-12 w-12 border-b-2 border-white mx-auto mb-4"></div>
                            <p class="text-lg">جاري تحضير الغرفة...</p>
                            <p class="text-sm text-gray-300">يرجى الانتظار</p>
                        </div>
                    </div>
                    
                    <!-- Meeting Controls -->
                    <div id="meetingControls" class="absolute bottom-4 left-1/2 transform -translate-x-1/2 flex items-center space-x-2 space-x-reverse bg-black/50 backdrop-blur-sm rounded-lg p-2" style="display: none;">
                        <button onclick="toggleMute()" id="muteBtn" class="p-2 bg-gray-800 hover:bg-gray-700 text-white rounded-lg transition-colors">
                            <i class="ri-mic-line" id="micIcon"></i>
                        </button>
                        <button onclick="toggleVideo()" id="videoBtn" class="p-2 bg-gray-800 hover:bg-gray-700 text-white rounded-lg transition-colors">
                            <i class="ri-camera-line" id="cameraIcon"></i>
                        </button>
                        <button onclick="toggleScreenShare()" id="screenBtn" class="p-2 bg-gray-800 hover:bg-gray-700 text-white rounded-lg transition-colors">
                            <i class="ri-computer-line"></i>
                        </button>
                    </div>
                </div>
                
                <!-- Session Info During Meeting -->
                <div class="mt-4 p-4 bg-gray-50 rounded-lg">
                    <div class="grid grid-cols-1 md:grid-cols-3 gap-4 text-sm">
                        <div class="flex items-center text-gray-600">
                            <i class="ri-user-line ml-2"></i>
                            <span id="participantCount">0 مشارك</span>
                        </div>
                        <div class="flex items-center text-gray-600">
                            <i class="ri-signal-wifi-line ml-2"></i>
                            <span id="connectionStatus">متصل</span>
                        </div>
                        <div class="flex items-center text-gray-600">
                            <i class="ri-record-circle-line ml-2"></i>
                            <span>تسجيل: {{ $session->meeting_data['settings']['recording_enabled'] ?? false ? 'مفعل' : 'معطل' }}</span>
                        </div>
                    </div>
                </div>
            </div>

            <div class="bg-white rounded-xl shadow-sm p-6 mb-8">
                <!-- Action Buttons Section -->
                @if($session->status === App\Enums\SessionStatus::SCHEDULED)
                    <div class="border-t border-gray-200 pt-6 mt-6">
                        <div class="flex flex-wrap items-center gap-4">
                            @if($session->scheduled_at && $session->scheduled_at->isFuture())
                                @php
                                    $minutesUntilSession = now()->diffInMinutes($session->scheduled_at);
                                    $canJoin = $minutesUntilSession <= 30;
                                @endphp
                                
                                @if($canJoin)
                                    <a href="{{ route('meetings.join', ['subdomain' => $academy->subdomain ?? 'itqan-academy', 'session' => $session->id]) }}" 
                                       class="inline-flex items-center px-6 py-3 bg-green-600 text-white font-semibold rounded-lg hover:bg-green-700 transition-colors shadow-sm">
                                        <i class="ri-video-line ml-2"></i>
                                        بدء الجلسة
                                    </a>
                                @endif
                            @endif
                            
                            @if($session->scheduled_at && ($session->scheduled_at->isPast() || $session->scheduled_at->isToday()))
                                <button type="button" id="markCompleteBtn" 
                                    class="inline-flex items-center px-6 py-3 bg-green-600 text-white font-semibold rounded-lg hover:bg-green-700 transition-colors shadow-sm">
                                    <i class="ri-check-line ml-2"></i>
                                    إنهاء الجلسة
                                </button>
                            @endif
                            
                            <button type="button" id="markCancelBtn" 
                                class="inline-flex items-center px-6 py-3 bg-red-600 text-white font-semibold rounded-lg hover:bg-red-700 transition-colors shadow-sm">
                                <i class="ri-close-line ml-2"></i>
                                إلغاء الجلسة
                            </button>
                            
                            @if($session->session_type === 'individual' && $session->scheduled_at && $session->scheduled_at->isPast())
                                <button type="button" id="markAbsentBtn" 
                                    class="inline-flex items-center px-6 py-3 bg-orange-600 text-white font-semibold rounded-lg hover:bg-orange-700 transition-colors shadow-sm">
                                    <i class="ri-user-x-line ml-2"></i>
                                    غياب الطالب
                                </button>
                            @endif
                        </div>
                    </div>
                @endif
            </div>

            <!-- Session Notes Form -->
            <div class="bg-white rounded-xl shadow-sm p-6 mb-8">
                <h3 class="text-lg font-bold text-gray-900 mb-6">
                    <i class="ri-edit-line text-blue-600 ml-2"></i>
                    ملاحظات الجلسة
                </h3>
                
                <form id="notesForm" class="space-y-6">
                    <div>
                        <label for="teacher_notes" class="block text-sm font-medium text-gray-700 mb-2">ملاحظات المعلم</label>
                        <textarea name="teacher_notes" id="teacher_notes" rows="4"
                            class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-primary-500"
                            placeholder="اكتب ملاحظاتك على الجلسة والطالب...">{{ $session->teacher_notes }}</textarea>
                    </div>
                    
                    <div>
                        <label for="student_progress" class="block text-sm font-medium text-gray-700 mb-2">تقدم الطالب</label>
                        <textarea name="student_progress" id="student_progress" rows="3"
                            class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-primary-500"
                            placeholder="اذكر التقدم المحرز في هذه الجلسة...">{{ $session->student_progress }}</textarea>
                    </div>
                    
                    <div>
                        <label for="homework_assigned" class="block text-sm font-medium text-gray-700 mb-2">الواجب المنزلي</label>
                        <textarea name="homework_assigned" id="homework_assigned" rows="3"
                            class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-primary-500"
                            placeholder="اكتب الواجب المنزلي المطلوب للجلسة القادمة...">{{ $session->homework_assigned }}</textarea>
                    </div>
                    
                    <button type="submit" class="inline-flex items-center px-4 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700 transition-colors">
                        <i class="ri-save-line ml-2"></i>
                        حفظ الملاحظات
                    </button>
                </form>
            </div>

            <!-- Student Feedback (if available) -->
            @if($session->status === App\Enums\SessionStatus::COMPLETED && $session->student_feedback)
                <div class="bg-white rounded-xl shadow-sm p-6 mb-8">
                    <h3 class="text-lg font-bold text-gray-900 mb-4">
                        <i class="ri-feedback-line text-yellow-600 ml-2"></i>
                        تقييم الطالب
                    </h3>
                    
                    <div class="flex items-center space-x-2 space-x-reverse mb-3">
                        @for($i = 1; $i <= 5; $i++)
                            <i class="ri-star-{{ $i <= $session->student_rating ? 'fill' : 'line' }} text-yellow-400"></i>
                        @endfor
                        <span class="text-sm text-gray-600 mr-2">{{ $session->student_rating }}/5</span>
                    </div>
                    
                    <p class="text-gray-700">{{ $session->student_feedback }}</p>
                    <p class="text-xs text-gray-500 mt-2">تم الإرسال {{ $session->feedback_at->diffForHumans() }}</p>
                </div>
            @endif

            <!-- Recording (if available) -->
            @if($session->status === App\Enums\SessionStatus::COMPLETED && $session->recording_url)
                <div class="bg-white rounded-xl shadow-sm p-6 mb-8">
                    <h3 class="text-lg font-bold text-gray-900 mb-4">
                        <i class="ri-video-line text-red-600 ml-2"></i>
                        تسجيل الجلسة
                    </h3>
                    
                    <a href="{{ $session->recording_url }}" target="_blank"
                       class="inline-flex items-center px-4 py-2 bg-red-600 text-white rounded-lg hover:bg-red-700 transition-colors">
                        <i class="ri-play-circle-line ml-2"></i>
                        مشاهدة التسجيل
                    </a>
                </div>
            @endif
        </div>

        <!-- Sidebar -->
        <div class="lg:col-span-1">
            <!-- Session Participants Info -->
            <div class="bg-white rounded-xl shadow-sm p-6 mb-6">
                @if(in_array($session->session_type, ['circle', 'group']) && $session->circle)
                    <!-- Group Session Info -->
                    <h3 class="font-bold text-gray-900 mb-4">معلومات الحلقة الجماعية</h3>
                    
                    <div class="flex items-center space-x-3 space-x-reverse mb-4">
                        <div class="w-12 h-12 rounded-full bg-primary-100 flex items-center justify-center">
                            <i class="ri-group-line text-primary-600 text-xl"></i>
                        </div>
                        <div>
                            <h4 class="font-medium text-gray-900">{{ $session->circle->name }}</h4>
                            <p class="text-sm text-gray-600">{{ $session->circle->students ? $session->circle->students->count() : 0 }} طالب مسجل</p>
                        </div>
                    </div>

                    <!-- Students List -->
                    @if($session->circle->students && $session->circle->students->count() > 0)
                        <div class="space-y-2 max-h-40 overflow-y-auto">
                            @foreach($session->circle->students->take(5) as $student)
                                <div class="flex items-center space-x-2 space-x-reverse p-2 bg-gray-50 rounded-lg">
                                    <x-student-avatar :student="$student" size="sm" />
                                    <div class="flex-1 min-w-0">
                                        <p class="text-sm font-medium text-gray-900 truncate">{{ $student->name }}</p>
                                        <p class="text-xs text-gray-500 truncate">{{ $student->email ?? 'طالب' }}</p>
                                    </div>
                                </div>
                            @endforeach
                            @if($session->circle->students->count() > 5)
                                <p class="text-center text-sm text-gray-500 pt-2">و {{ $session->circle->students->count() - 5 }} طالب آخر</p>
                            @endif
                        </div>
                    @else
                        <div class="text-center py-4">
                            <div class="w-12 h-12 rounded-full bg-gray-100 flex items-center justify-center mx-auto mb-2">
                                <i class="ri-group-line text-gray-400"></i>
                            </div>
                            <p class="text-gray-500">لا يوجد طلاب مسجلون</p>
                        </div>
                    @endif
                @elseif($session->student)
                    <!-- Individual Session Info -->
                    <h3 class="font-bold text-gray-900 mb-4">معلومات الطالب</h3>
                    
                    <div class="flex items-center space-x-3 space-x-reverse mb-4">
                        <x-student-avatar :student="$session->student" size="md" />
                        <div>
                            <h4 class="font-medium text-gray-900">{{ $session->student->name }}</h4>
                            @if($session->student->email)
                                <p class="text-sm text-gray-600">{{ $session->student->email }}</p>
                            @endif
                        </div>
                    </div>
                @else
                    <!-- No Participants Info -->
                    <h3 class="font-bold text-gray-900 mb-4">معلومات الجلسة</h3>
                    
                    <div class="text-center py-4">
                        <div class="w-12 h-12 rounded-full bg-gray-100 flex items-center justify-center mx-auto mb-2">
                            <i class="ri-user-line text-gray-400"></i>
                        </div>
                        <p class="text-gray-500">لم يتم تحديد الطالب</p>
                    </div>
                @endif
                
                <div class="space-y-3">
                    <div>
                        <span class="text-sm text-gray-600">نوع الجلسة:</span>
                        <p class="font-medium text-gray-900">
                            @if(in_array($session->session_type, ['circle', 'group']) && $session->circle)
                                حلقة جماعية
                            @elseif($session->individual_circle_id && $session->individualCircle)
                                حلقة فردية
                            @else
                                غير محدد
                            @endif
                        </p>
                    </div>
                    
                    @if($session->individual_circle_id && $session->individualCircle?->subscription?->package)
                        <div>
                            <span class="text-sm text-gray-600">نوع الاشتراك:</span>
                            <p class="font-medium text-gray-900">{{ $session->individualCircle->subscription->package->name }}</p>
                        </div>
                    @endif
                    
                    <div>
                        <span class="text-sm text-gray-600">المدة:</span>
                        <p class="font-medium text-gray-900">{{ $session->duration_minutes }} دقيقة</p>
                    </div>
                    
                    @if($session->scheduled_at)
                        <div>
                            <span class="text-sm text-gray-600">الموعد:</span>
                            <p class="font-medium text-gray-900">{{ $session->scheduled_at->format('d/m/Y') }}</p>
                            <p class="text-sm text-gray-600">{{ $session->scheduled_at->format('H:i A') }}</p>
                        </div>
                    @endif
                    
                    @if($session->ended_at)
                        <div>
                            <span class="text-sm text-gray-600">انتهت في:</span>
                            <p class="font-medium text-gray-900">{{ $session->ended_at->format('d/m/Y H:i A') }}</p>
                        </div>
                    @endif
                </div>

                <div class="mt-6 pt-4 border-t border-gray-200">
                    @if($session->individual_circle_id && $session->individualCircle)
                        <a href="{{ route('teacher.individual-circles.show', ['subdomain' => $academy->subdomain ?? 'itqan-academy', 'circle' => $session->individualCircle->id]) }}" 
                           class="text-primary-600 hover:text-primary-700 text-sm font-medium flex items-center">
                            <i class="ri-arrow-right-line ml-1"></i>
                            العودة للحلقة الفردية
                        </a>
                    @elseif($session->circle_id && $session->circle)
                        <a href="{{ route('teacher.group-circles.show', ['subdomain' => $academy->subdomain, 'circle' => $session->circle->id]) }}" 
                           class="text-primary-600 hover:text-primary-700 text-sm font-medium flex items-center">
                            <i class="ri-arrow-right-line ml-1"></i>
                            العودة للحلقة الجماعية
                        </a>
                    @endif
                </div>
            </div>

            <!-- Quick Actions -->
            <div class="bg-white rounded-xl shadow-sm p-6">
                <h3 class="font-bold text-gray-900 mb-4">إجراءات سريعة</h3>
                
                <div class="space-y-3">
                    <!-- Test Meeting Creation Button -->
                    @if($session->status === App\Enums\SessionStatus::SCHEDULED)
                        <button id="testMeetingBtn" 
                               class="w-full flex items-center justify-center px-4 py-2 bg-purple-100 text-purple-700 text-sm font-medium rounded-lg hover:bg-purple-200 transition-colors">
                            <i class="ri-test-tube-line ml-2"></i>
                            اختبار إنشاء اجتماع
                        </button>
                    @endif
                    
                    @if($session->student && $session->student->email)
                        <a href="mailto:{{ $session->student->email }}" 
                           class="w-full flex items-center justify-center px-4 py-2 bg-gray-100 text-gray-700 text-sm font-medium rounded-lg hover:bg-gray-200 transition-colors">
                            <i class="ri-mail-line ml-2"></i>
                            إرسال رسالة للطالب
                        </a>
                    @endif
                    
                    @if($session->individual_circle_id && $session->individualCircle)
                        <a href="{{ route('teacher.individual-circles.progress', ['subdomain' => $academy->subdomain ?? 'itqan-academy', 'circle' => $session->individualCircle->id]) }}" 
                           class="w-full flex items-center justify-center px-4 py-2 bg-gray-100 text-gray-700 text-sm font-medium rounded-lg hover:bg-gray-200 transition-colors">
                            <i class="ri-line-chart-line ml-2"></i>
                            تقرير التقدم (فردي)
                        </a>
                    @elseif($session->circle_id && $session->circle)
                        <a href="{{ route('teacher.group-circles.progress', ['subdomain' => $academy->subdomain, 'circle' => $session->circle->id]) }}" 
                           class="w-full flex items-center justify-center px-4 py-2 bg-gray-100 text-gray-700 text-sm font-medium rounded-lg hover:bg-gray-200 transition-colors">
                            <i class="ri-line-chart-line ml-2"></i>
                            تقرير التقدم (جماعي)
                        </a>
                    @endif
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Modals -->
<x-modals.session-action-modal 
    id="complete-session-modal"
    title="تأكيد إنهاء الجلسة"
    message="هل أنت متأكد من إنهاء هذه الجلسة؟ سيتم تسجيل الجلسة كمكتملة ولن تتمكن من التراجع عن هذا الإجراء."
    confirm-text="إنهاء الجلسة"
    cancel-text="إلغاء"
    confirm-color="green"
    icon="ri-check-circle-line" />

<x-modals.session-action-modal 
    id="cancel-session-modal"
    title="تأكيد إلغاء الجلسة"
    message="هل أنت متأكد من إلغاء هذه الجلسة؟ لن يتم احتساب هذه الجلسة من اشتراك الطالب."
    confirm-text="إلغاء الجلسة"
    cancel-text="تراجع"
    confirm-color="red"
    icon="ri-close-circle-line"
    :has-input="true"
    input-label="سبب الإلغاء (اختياري)"
    input-placeholder="اذكر سبب إلغاء الجلسة..." />

@if($session->session_type === 'individual' && $session->scheduled_at && $session->scheduled_at->isPast())
    <x-modals.session-action-modal 
        id="absent-session-modal"
        title="تسجيل غياب الطالب"
        message="هل أنت متأكد من تسجيل غياب الطالب؟ سيتم احتساب هذه الجلسة من اشتراك الطالب."
        confirm-text="تسجيل الغياب"
        cancel-text="إلغاء"
        confirm-color="orange"
        icon="ri-user-x-line"
        :has-input="true"
        input-label="سبب الغياب (اختياري)"
        input-placeholder="اذكر ملاحظات حول غياب الطالب..." />
@endif

<script>
document.addEventListener('DOMContentLoaded', function() {
    // Notes form submission
    document.getElementById('notesForm').addEventListener('submit', function(e) {
        e.preventDefault();
        
        const formData = new FormData(this);
        const submitBtn = this.querySelector('button[type="submit"]');
        const originalText = submitBtn.textContent;
        
        submitBtn.disabled = true;
        submitBtn.textContent = 'جاري الحفظ...';

        fetch(`{{ route('teacher.sessions.update-notes', ['subdomain' => $academy->subdomain ?? 'itqan-academy', 'sessionId' => $session->id]) }}`, {
            method: 'PUT',
            body: formData,
            headers: {
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
            }
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                showToast(data.message, 'success');
            } else {
                showToast('خطأ: ' + data.message, 'error');
            }
        })
        .catch(error => {
            console.error('Error:', error);
            showToast('حدث خطأ في حفظ الملاحظات', 'error');
        })
        .finally(() => {
            submitBtn.disabled = false;
            submitBtn.textContent = originalText;
        });
    });

    // Session action handlers
    @if($session->status === App\Enums\SessionStatus::SCHEDULED)
    
    @if($session->scheduled_at && ($session->scheduled_at->isPast() || $session->scheduled_at->isToday()))
    // Complete session
    document.getElementById('markCompleteBtn').addEventListener('click', function() {
        openModal('complete-session-modal');
    });
    
    document.getElementById('complete-session-modal-confirm').addEventListener('click', function() {
        setModalLoading('complete-session-modal', true);
        
        fetch(`{{ route('teacher.sessions.complete', ['subdomain' => $academy->subdomain ?? 'itqan-academy', 'sessionId' => $session->id]) }}`, {
            method: 'PUT',
            headers: {
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content'),
                'Content-Type': 'application/json'
            }
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                showToast(data.message, 'success');
                setTimeout(() => {
                    location.reload();
                }, 1500);
            } else {
                showToast('خطأ: ' + data.message, 'error');
                setModalLoading('complete-session-modal', false);
            }
        })
        .catch(error => {
            console.error('Error:', error);
            showToast('حدث خطأ في إنهاء الجلسة', 'error');
            setModalLoading('complete-session-modal', false);
        });
    });
    @endif
    
    // Cancel session
    document.getElementById('markCancelBtn').addEventListener('click', function() {
        openModal('cancel-session-modal');
    });
    
    document.getElementById('cancel-session-modal-confirm').addEventListener('click', function() {
        setModalLoading('cancel-session-modal', true);
        
        const reason = document.getElementById('cancel-session-modal-input').value;
        
        fetch(`{{ route('teacher.sessions.cancel', ['subdomain' => $academy->subdomain ?? 'itqan-academy', 'sessionId' => $session->id]) }}`, {
            method: 'PUT',
            headers: {
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content'),
                'Content-Type': 'application/json'
            },
            body: JSON.stringify({ reason: reason })
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                showToast(data.message, 'success');
                setTimeout(() => {
                    location.reload();
                }, 1500);
            } else {
                showToast('خطأ: ' + data.message, 'error');
                setModalLoading('cancel-session-modal', false);
            }
        })
        .catch(error => {
            console.error('Error:', error);
            showToast('حدث خطأ في إلغاء الجلسة', 'error');
            setModalLoading('cancel-session-modal', false);
        });
    });
    
    @if($session->session_type === 'individual' && $session->scheduled_at && $session->scheduled_at->isPast())
    // Mark absent
    document.getElementById('markAbsentBtn').addEventListener('click', function() {
        openModal('absent-session-modal');
    });
    
    document.getElementById('absent-session-modal-confirm').addEventListener('click', function() {
        setModalLoading('absent-session-modal', true);
        
        const reason = document.getElementById('absent-session-modal-input').value;
        
        fetch(`{{ route('teacher.sessions.absent', ['subdomain' => $academy->subdomain ?? 'itqan-academy', 'sessionId' => $session->id]) }}`, {
            method: 'PUT',
            headers: {
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content'),
                'Content-Type': 'application/json'
            },
            body: JSON.stringify({ reason: reason })
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                showToast(data.message, 'success');
                setTimeout(() => {
                    location.reload();
                }, 1500);
            } else {
                showToast('خطأ: ' + data.message, 'error');
                setModalLoading('absent-session-modal', false);
            }
        })
        .catch(error => {
            console.error('Error:', error);
            showToast('حدث خطأ في تسجيل الغياب', 'error');
            setModalLoading('absent-session-modal', false);
        });
    });
    @endif
    
    @endif

    // Toast notification function
    function showToast(message, type = 'info') {
        const toast = document.createElement('div');
        const bgColor = type === 'success' ? 'bg-green-500' : 
                        type === 'error' ? 'bg-red-500' : 
                        type === 'warning' ? 'bg-yellow-500' : 'bg-blue-500';
        
        toast.className = `fixed top-4 right-4 ${bgColor} text-white px-6 py-3 rounded-lg shadow-lg z-50 transition-all duration-300`;
        toast.textContent = message;
        
        document.body.appendChild(toast);
        
        // Auto remove after 3 seconds
        setTimeout(() => {
            if (toast.parentNode) {
                toast.style.opacity = '0';
                setTimeout(() => {
                    document.body.removeChild(toast);
                }, 300);
            }
        }, 3000);
    }

    // Copy Meeting Link Function
    function copyMeetingLink() {
        const meetingUrl = "{{ $session->meeting_link ?? '' }}";
        
        if (!meetingUrl || meetingUrl.trim() === '') {
            showToast('لم يتم إنشاء رابط الاجتماع بعد. اضغط على "بدء الجلسة" أولاً.', 'warning');
            return;
        }
        
        if (navigator.clipboard) {
            navigator.clipboard.writeText(meetingUrl).then(function() {
                showToast('تم نسخ رابط الاجتماع بنجاح!', 'success');
            }, function(err) {
                console.error('فشل في نسخ الرابط: ', err);
                showToast('فشل في نسخ الرابط', 'error');
            });
        } else {
            // Fallback for older browsers
            const textArea = document.createElement("textarea");
            textArea.value = meetingUrl;
            document.body.appendChild(textArea);
            textArea.select();
            try {
                document.execCommand('copy');
                showToast('تم نسخ رابط الاجتماع بنجاح!', 'success');
            } catch (err) {
                showToast('فشل في نسخ الرابط', 'error');
            }
            document.body.removeChild(textArea);
        }
    }

    // Debug function - call debugMeeting() in browser console for troubleshooting
    window.debugMeeting = function() {
        console.log('=== Meeting Debug Info ===');
        console.log('Session ID:', {{ $session->id }});
        console.log('Meeting Link:', "{{ $session->meeting_link ?? '' }}");
        console.log('Meeting Room Name:', "{{ $session->meeting_room_name ?? '' }}");
        console.log('Scheduled At:', "{{ $session->scheduled_at ?? '' }}");
        console.log('Can Test Meetings:', {{ can_test_meetings() ? 'true' : 'false' }});
        console.log('Academy Subdomain:', '{{ $academy->subdomain ?? "itqan-academy" }}');
        console.log('User Type:', '{{ auth()->user()->user_type ?? '' }}');
        console.log('CSRF Token:', document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') ?? 'NOT FOUND');
        console.log('========================');
    };

    // Test Meeting Creation
    const testMeetingBtn = document.getElementById('testMeetingBtn');
    if (testMeetingBtn) {
        testMeetingBtn.addEventListener('click', function() {
            const originalText = this.textContent;
            this.disabled = true;
            this.textContent = 'جاري إنشاء الاجتماع...';
            this.classList.remove('hover:bg-purple-200');
            this.classList.add('opacity-50', 'cursor-not-allowed');

            fetch(`{{ route('teacher.sessions.create-meeting', ['subdomain' => $academy->subdomain ?? 'itqan-academy', 'sessionId' => $session->id]) }}`, {
                method: 'POST',
                headers: {
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content'),
                    'Content-Type': 'application/json'
                }
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    showToast('تم إنشاء الاجتماع بنجاح! سيتم تحديث الصفحة لعرض غرفة الاجتماع.', 'success');
                    if (data.meeting_url) {
                        console.log('Meeting URL:', data.meeting_url);
                    }
                    // Refresh page after 1.5 seconds to show the meeting interface
                    setTimeout(() => {
                        location.reload();
                    }, 1500);
                } else {
                    showToast('خطأ في إنشاء الاجتماع: ' + (data.message || 'حدث خطأ غير متوقع'), 'error');
                }
            })
            .catch(error => {
                console.error('Error:', error);
                showToast('حدث خطأ في إنشاء الاجتماع', 'error');
            })
            .finally(() => {
                this.disabled = false;
                this.textContent = originalText;
                this.classList.add('hover:bg-purple-200');
                this.classList.remove('opacity-50', 'cursor-not-allowed');
            });
        });
    }

    // Embedded Meeting Functionality
    let room = null;
    let localParticipant = null;
    let sessionStartTime = null;
    let timerInterval = null;
    let isMuted = false;
    let isVideoOff = false;

    async function startMeeting() {
        const meetingContainer = document.getElementById('meetingContainer');
        const meetingLoading = document.getElementById('meetingLoading');
        const meetingControls = document.getElementById('meetingControls');
        const meetingBtnText = document.getElementById('meetingBtnText');
        const endMeetingBtn = document.getElementById('endMeetingBtn');
        const startMeetingBtn = document.getElementById('startMeetingBtn');
        
        try {
            // Update button state
            meetingBtnText.textContent = 'جاري إنشاء الاجتماع...';
            startMeetingBtn.disabled = true;
            
            // First, create the meeting if it doesn't exist
            const sessionId = {{ $session->id }};
            const subdomain = '{{ $academy->subdomain ?? "itqan-academy" }}';
            
            // Check if meeting already exists
            let meetingUrl = '{{ $session->meeting_link ?? "" }}';
            
            if (!meetingUrl) {
                // Create the meeting using the correct API route
                const response = await fetch('/api/meetings/create', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
                    },
                    body: JSON.stringify({
                        session_id: sessionId,
                        max_participants: 50,
                        recording_enabled: false,
                        max_duration: 120
                    })
                });

                if (!response.ok) {
                    const errorText = await response.text();
                    console.error('Server response:', response.status, errorText);
                    throw new Error(`HTTP ${response.status}: ${errorText}`);
                }

                const result = await response.json();
                console.log('Meeting creation result:', result);
                
                if (!result.success) {
                    throw new Error(result.message || 'فشل في إنشاء الاجتماع');
                }
                
                meetingUrl = result.meeting_info.meeting_url;
                showToast('تم إنشاء الاجتماع بنجاح!', 'success');
                
                // Reload the page to update the session data
                setTimeout(() => {
                    window.location.reload();
                }, 1000);
                return;
            }
            
            // Show meeting container
            meetingContainer.style.display = 'block';
            meetingLoading.style.display = 'flex';
            meetingBtnText.textContent = 'جاري الاتصال...';
            
            // Redirect to the meeting join page
            setTimeout(() => {
                window.location.href = meetingUrl;
            }, 1000);
            
        } catch (error) {
            console.error('Error starting meeting:', error);
            showToast('خطأ في بدء الجلسة: ' + error.message, 'error');
            
            // Reset button state
            meetingBtnText.textContent = 'بدء الجلسة';
            startMeetingBtn.disabled = false;
            meetingContainer.style.display = 'none';
        }
    }
    
    function endMeeting() {
        endMeetingSession();
        showToast('تم إنهاء الجلسة', 'info');
    }
    
    function endMeetingSession() {
        const meetingContainer = document.getElementById('meetingContainer');
        const meetingBtnText = document.getElementById('meetingBtnText');
        const endMeetingBtn = document.getElementById('endMeetingBtn');
        
        // Hide meeting container
        meetingContainer.style.display = 'none';
        
        // Reset button states
        meetingBtnText.textContent = 'بدء الجلسة';
        endMeetingBtn.style.display = 'none';
        
        // Stop timer
        if (timerInterval) {
            clearInterval(timerInterval);
            timerInterval = null;
        }
        
        // Reset variables
        sessionStartTime = null;
    }
    
    function startSessionTimer() {
        sessionStartTime = Date.now();
        timerInterval = setInterval(() => {
            const elapsed = Date.now() - sessionStartTime;
            const minutes = Math.floor(elapsed / 60000);
            const seconds = Math.floor((elapsed % 60000) / 1000);
            document.getElementById('timerDisplay').textContent = 
                `${minutes.toString().padStart(2, '0')}:${seconds.toString().padStart(2, '0')}`;
        }, 1000);
    }
    
    function updateParticipantCount() {
        // Simulate participant count (in real implementation, this would be from LiveKit)
        document.getElementById('participantCount').textContent = '1 مشارك';
    }
    
    async function toggleMute() {
        isMuted = !isMuted;
        document.getElementById('micIcon').className = isMuted ? 'ri-mic-off-line' : 'ri-mic-line';
        document.getElementById('muteBtn').className = isMuted ? 
            'p-2 bg-red-600 hover:bg-red-700 text-white rounded-lg transition-colors' :
            'p-2 bg-gray-800 hover:bg-gray-700 text-white rounded-lg transition-colors';
        showToast(isMuted ? 'تم كتم الصوت' : 'تم إلغاء كتم الصوت', 'info');
    }
    
    async function toggleVideo() {
        isVideoOff = !isVideoOff;
        document.getElementById('cameraIcon').className = isVideoOff ? 'ri-camera-off-line' : 'ri-camera-line';
        document.getElementById('videoBtn').className = isVideoOff ? 
            'p-2 bg-red-600 hover:bg-red-700 text-white rounded-lg transition-colors' :
            'p-2 bg-gray-800 hover:bg-gray-700 text-white rounded-colors';
        showToast(isVideoOff ? 'تم إيقاف الكاميرا' : 'تم تشغيل الكاميرا', 'info');
    }
    
    async function toggleScreenShare() {
        showToast('مشاركة الشاشة (قريباً)', 'info');
    }
});
</script>

</x-layouts.teacher>
