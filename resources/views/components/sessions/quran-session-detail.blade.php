@props([
    'session',
    'viewType' => 'student' // student, teacher
])

<div class="min-h-screen bg-gray-50 py-8">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        <!-- Breadcrumb -->
        <nav class="mb-8">
            <ol class="flex items-center space-x-2 space-x-reverse text-sm text-gray-600">
                <li>
                    <a href="{{ route($viewType . '.profile', ['subdomain' => request()->route('subdomain')]) }}"
                       class="hover:text-primary">
                        {{ $viewType === 'student' ? 'ملفي الشخصي' : 'ملفي الشخصي' }}
                    </a>
                </li>
                <li>/</li>
                @if($session->circle_id && $session->circle)
                    <li>
                        @if($viewType === 'student')
                            <a href="{{ route('student.quran-circles', ['subdomain' => request()->route('subdomain')]) }}"
                               class="hover:text-primary">حلقات القرآن</a>
                        @else
                            <a href="{{ route('teacher.group-circles.index', ['subdomain' => request()->route('subdomain')]) }}"
                               class="hover:text-primary">الحلقات الجماعية</a>
                        @endif
                    </li>
                    <li>/</li>
                    <li>
                        @if($viewType === 'student')
                            <a href="{{ route('student.circles.show', ['subdomain' => request()->route('subdomain'), 'circleId' => $session->circle->id]) }}"
                               class="hover:text-primary">{{ $session->circle->name ?? 'الحلقة' }}</a>
                        @else
                            <a href="{{ route('teacher.group-circles.show', ['subdomain' => request()->route('subdomain'), 'circle' => $session->circle->id]) }}"
                               class="hover:text-primary">{{ $session->circle->name ?? 'الحلقة' }}</a>
                        @endif
                    </li>
                @elseif($session->individual_circle_id && $session->individualCircle)
                    <li>
                        @if($viewType === 'student')
                            <a href="{{ route('student.quran-teachers', ['subdomain' => request()->route('subdomain')]) }}"
                               class="hover:text-primary">معلمي القرآن</a>
                        @else
                            <a href="{{ route('teacher.individual-circles.index', ['subdomain' => request()->route('subdomain')]) }}"
                               class="hover:text-primary">الحلقات الفردية</a>
                        @endif
                    </li>
                    <li>/</li>
                    <li>
                        <a href="{{ route('individual-circles.show', ['subdomain' => request()->route('subdomain'), 'circle' => $session->individualCircle->id]) }}"
                           class="hover:text-primary">{{ $session->individualCircle->subscription->package->name ?? 'الحلقة الفردية' }}</a>
                    </li>
                @else
                    <li>
                        @if($viewType === 'student')
                            <a href="{{ route('student.dashboard', ['subdomain' => request()->route('subdomain')]) }}"
                               class="hover:text-primary">لوحة التحكم</a>
                        @else
                            <a href="{{ route('teacher.schedule.dashboard', ['subdomain' => request()->route('subdomain')]) }}"
                               class="hover:text-primary">
                                {{ $session->session_type === 'trial' ? 'الجلسات التجريبية' : 'جدول الجلسات' }}
                            </a>
                        @endif
                    </li>
                @endif
                <li>/</li>
                <li class="text-gray-900">{{ $session->title ?? 'تفاصيل الجلسة' }}</li>
            </ol>
        </nav>

        <div class="space-y-6">
            <!-- Session Header -->
            <x-sessions.session-header :session="$session" :view-type="$viewType" />

            <!-- Enhanced LiveKit Meeting Interface -->
            <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-6">
                <x-meetings.livekit-interface
                    :session="$session"
                    :user-type="$viewType === 'student' ? 'student' : 'quran_teacher'"
                />
            </div>

            <!-- Trial Session Information (Student Only) -->
            @if($viewType === 'student' && $session->session_type === 'trial' && $session->trialRequest)
                <div class="bg-gradient-to-br from-green-50 to-emerald-50 rounded-xl shadow-sm border border-green-200 p-6">
                    <div class="flex items-start gap-4">
                        <div class="flex-shrink-0">
                            <div class="w-12 h-12 bg-green-600 rounded-full flex items-center justify-center">
                                <i class="fas fa-gift text-white text-xl"></i>
                            </div>
                        </div>
                        <div class="flex-1">
                            <h3 class="text-lg font-bold text-green-900 mb-2">
                                <i class="fas fa-star text-yellow-500 ml-1"></i>
                                جلسة تجريبية مجانية
                            </h3>
                            <p class="text-green-800 mb-3">
                                هذه جلسة تجريبية مجانية مدتها 30 دقيقة للتعرف على المعلم وتقييم مستواك في القرآن الكريم.
                            </p>
                            <div class="grid grid-cols-2 gap-4 text-sm">
                                <div>
                                    <span class="font-medium text-green-900">المستوى المُدخل:</span>
                                    <span class="text-green-700">{{ $session->trialRequest->level_label }}</span>
                                </div>
                                @if($session->trialRequest->learning_goals && count($session->trialRequest->learning_goals) > 0)
                                <div>
                                    <span class="font-medium text-green-900">الأهداف:</span>
                                    <span class="text-green-700">
                                        @php
                                            $goals = [
                                                'reading' => 'القراءة',
                                                'tajweed' => 'التجويد',
                                                'memorization' => 'الحفظ',
                                                'improvement' => 'التحسين'
                                            ];
                                            $goalLabels = collect($session->trialRequest->learning_goals)->map(fn($g) => $goals[$g] ?? $g);
                                        @endphp
                                        {{ $goalLabels->join('، ') }}
                                    </span>
                                </div>
                                @endif
                            </div>
                            @if($session->trialRequest->notes)
                            <div class="mt-3 p-3 bg-white/50 rounded-lg">
                                <span class="font-medium text-green-900 block mb-1">ملاحظاتك:</span>
                                <p class="text-green-700 text-sm">{{ $session->trialRequest->notes }}</p>
                            </div>
                            @endif
                        </div>
                    </div>
                </div>
            @endif

            <!-- Homework Management (Teacher) or Homework Display (Student) -->
            @if($viewType === 'teacher')
                <x-sessions.homework-management
                    :session="$session"
                    view-type="teacher" />
            @elseif($session->homework && $session->homework->count() > 0)
                <x-sessions.homework-display
                    :session="$session"
                    :homework="$session->homework"
                    view-type="student" />
            @endif

            <!-- Students Section (Teacher Only) -->
            @if($viewType === 'teacher')
                @if($session->session_type === 'group' && $session->students && $session->students->count() > 0)
                <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-6 mt-6">
                    <h3 class="text-lg font-semibold text-gray-900 mb-4">
                        طلاب الجلسة ({{ $session->students->count() }})
                    </h3>

                    <div class="space-y-4">
                        @foreach($session->students as $student)
                            <x-sessions.student-item
                                :student="$student"
                                :session="$session"
                                :show-chat="true"
                                size="sm"
                            />
                        @endforeach
                    </div>
                </div>
                @elseif($session->session_type === 'individual' && $session->student)
                <!-- Individual Student Info -->
                <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-6 mt-6">
                    <h3 class="text-lg font-semibold text-gray-900 mb-4">الطالب</h3>

                    <x-sessions.student-item
                        :student="$session->student"
                        :session="$session"
                        :show-chat="true"
                        size="md"
                    />
                </div>
                @endif
            @endif

            <!-- Session Instructions (Student Only, for Scheduled Sessions) -->
            @if($viewType === 'student' && $session->status === 'scheduled')
                <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-6">
                    <h3 class="text-lg font-semibold text-gray-900 mb-4">تعليمات الجلسة</h3>
                    <div class="bg-blue-50 p-4 rounded-lg">
                        <div class="flex items-start gap-3">
                            <div class="flex-shrink-0 w-6 h-6 bg-blue-600 rounded-full flex items-center justify-center mt-1">
                                <i class="fas fa-info text-white text-xs"></i>
                            </div>
                            <div class="text-blue-800">
                                <p class="font-medium mb-2">نصائح للاستعداد للجلسة:</p>
                                <ul class="space-y-1 text-sm">
                                    <li>• تأكد من جودة اتصال الإنترنت</li>
                                    <li>• اختبر الكاميرا والميكروفون قبل بدء الجلسة</li>
                                    <li>• أحضر المصحف أو افتح تطبيق القرآن الكريم</li>
                                    <li>• اختر مكاناً هادئاً للجلسة</li>
                                    <li>• كن مستعداً قبل الموعد بـ 5 دقائق</li>
                                </ul>
                            </div>
                        </div>
                    </div>
                </div>
            @endif
        </div>
    </div>
</div>

<!-- Student Evaluation Modal Component (Teacher Only) -->
@if($viewType === 'teacher')
    <x-teacher.student-evaluation-modal :session="$session" />
@endif
