@props([
    'subscription',
    'viewType' => 'student' // 'student', 'teacher', 'supervisor'
])

@php
    $student = $subscription->student;
    $teacher = $subscription->teacher;
    $subject = $subscription->subject;
    $gradeLevel = $subscription->gradeLevel;
@endphp

<div class="bg-white rounded-xl shadow-sm border border-gray-200 p-6 mb-6">
    <!-- Header with Subject -->
    <div class="text-center mb-6">
        <h2 class="text-3xl font-bold text-gray-900 mb-1">
            {{ $subject->name ?? $subscription->subject_name ?? 'درس خاص' }}
        </h2>
        <p class="text-sm text-blue-600 font-medium">
            {{ $gradeLevel->name ?? $subscription->grade_level_name ?? 'مستوى غير محدد' }}
        </p>
    </div>

    <div class="space-y-4">
        <!-- Teacher Information -->
        @if($teacher)
            <div class="bg-gray-50 rounded-xl p-4 border border-gray-200">
                <div class="flex items-center space-x-3 space-x-reverse">
                    <x-avatar
                        :user="$teacher"
                        size="sm"
                        userType="academic_teacher"
                        :gender="$teacher->gender ?? 'male'"
                        class="flex-shrink-0" />
                    <div class="flex-1">
                        <span class="text-xs font-bold text-green-700 uppercase tracking-wide">المعلم</span>
                        <p class="font-bold text-gray-900 text-base">
                            {{ $teacher->first_name }} {{ $teacher->last_name }}
                        </p>
                        @if($teacher->experience_years)
                            <p class="text-xs text-green-600 mt-1">
                                <i class="ri-medal-line ml-1"></i>
                                {{ $teacher->experience_years }} سنوات خبرة
                            </p>
                        @endif
                    </div>
                    @if($viewType === 'student')
                        <a href="{{ route('public.academic-teachers.show', ['subdomain' => auth()->user()->academy->subdomain ?? 'itqan-academy', 'teacher' => $teacher->id]) }}" 
                           class="text-blue-600 hover:text-blue-800 transition-colors">
                            <i class="ri-external-link-line text-lg"></i>
                        </a>
                    @endif
                </div>
            </div>
        @endif

        <!-- Student Information (for teacher view) -->
        @if($viewType === 'teacher' && $student)
            <div class="bg-gray-50 rounded-xl p-4 border border-gray-200">
                <div class="flex items-center space-x-3 space-x-reverse">
                    <x-avatar
                        :user="$student"
                        size="sm"
                        userType="student"
                        :gender="$student->gender ?? $student->studentProfile?->gender ?? 'male'"
                        class="flex-shrink-0" />
                    <div class="flex-1">
                        <span class="text-xs font-bold text-purple-700 uppercase tracking-wide">الطالب</span>
                        <p class="font-bold text-gray-900 text-base">{{ $student->name }}</p>
                    </div>
                </div>
            </div>
        @endif

        <!-- Subscription Details -->
        <div class="bg-gray-50 rounded-xl p-4 border border-gray-200">
            <div class="grid grid-cols-2 gap-4">
                <!-- Sessions per Week -->
                <div class="text-center">
                    <div class="w-10 h-10 bg-orange-500 rounded-lg flex items-center justify-center mx-auto mb-2">
                        <i class="ri-calendar-check-line text-white text-sm"></i>
                    </div>
                    <p class="text-xl font-bold text-gray-900">{{ $subscription->sessions_per_week ?? 1 }}</p>
                    <p class="text-xs text-orange-600 font-medium">جلسة/أسبوع</p>
                </div>

                <!-- Session Duration -->
                <div class="text-center">
                    <div class="w-10 h-10 bg-teal-500 rounded-lg flex items-center justify-center mx-auto mb-2">
                        <i class="ri-timer-line text-white text-sm"></i>
                    </div>
                    <p class="text-xl font-bold text-gray-900">{{ $subscription->session_duration_minutes ?? 60 }}</p>
                    <p class="text-xs text-teal-600 font-medium">دقيقة</p>
                </div>
            </div>
        </div>


        <!-- Subscription Status -->
        <div class="bg-gray-50 rounded-xl p-4 border border-gray-200">
            <div class="flex items-center justify-between">
                <span class="text-sm font-bold text-gray-700 uppercase tracking-wide flex items-center">
                    <i class="ri-information-line ml-1"></i>
                    حالة الاشتراك
                </span>
                <span class="px-3 py-1 rounded-full text-xs font-bold
                    @if($subscription->status === 'active') bg-green-100 text-green-800
                    @elseif($subscription->status === 'paused') bg-yellow-100 text-yellow-800
                    @elseif($subscription->status === 'expired') bg-blue-100 text-blue-800
                    @elseif($subscription->status === 'cancelled') bg-red-100 text-red-800
                    @elseif($subscription->status === 'suspended') bg-orange-100 text-orange-800
                    @else bg-gray-100 text-gray-800 @endif">
                    @switch($subscription->status)
                        @case('active') نشط @break
                        @case('paused') متوقف @break
                        @case('expired') منتهي @break
                        @case('cancelled') ملغي @break
                        @case('suspended') موقف @break
                        @case('pending') في الانتظار @break
                        @default {{ $subscription->status }}
                    @endswitch
                </span>
            </div>
        </div>

        <!-- Dates Information -->
        <div class="bg-gray-50 rounded-xl p-4 border border-gray-200">
            <div class="space-y-3">
                @if($subscription->start_date)
                    <div class="flex justify-between items-center">
                        <span class="text-sm text-gray-600 flex items-center">
                            <i class="ri-calendar-2-line ml-1"></i>
                            تاريخ البداية
                        </span>
                        <span class="font-medium text-gray-900">{{ \Carbon\Carbon::parse($subscription->start_date)->format('Y/m/d') }}</span>
                    </div>
                @endif
                @if($subscription->end_date)
                    <div class="flex justify-between items-center">
                        <span class="text-sm text-gray-600 flex items-center">
                            <i class="ri-calendar-event-line ml-1"></i>
                            تاريخ الانتهاء
                        </span>
                        <span class="font-medium text-gray-900">{{ \Carbon\Carbon::parse($subscription->end_date)->format('Y/m/d') }}</span>
                    </div>
                @endif
                @if($subscription->next_billing_date)
                    <div class="flex justify-between items-center">
                        <span class="text-sm text-gray-600 flex items-center">
                            <i class="ri-refresh-line ml-1"></i>
                            الفوترة القادمة
                        </span>
                        <span class="font-medium text-gray-900">{{ \Carbon\Carbon::parse($subscription->next_billing_date)->format('Y/m/d') }}</span>
                    </div>
                @endif
            </div>
        </div>

        <!-- Weekly Schedule -->
        @if($subscription->weekly_schedule)
            <div class="bg-gray-50 rounded-xl p-4 border border-gray-200">
                <div class="flex items-center mb-3">
                    <i class="ri-calendar-line ml-2 text-indigo-600"></i>
                    <span class="text-sm font-bold text-indigo-700 uppercase tracking-wide">الجدول الأسبوعي</span>
                </div>
                <div class="space-y-2">
                    @php
                        $schedule = is_string($subscription->weekly_schedule) ? json_decode($subscription->weekly_schedule, true) : $subscription->weekly_schedule;
                        $arabicDays = [
                            'sunday' => 'الأحد',
                            'monday' => 'الاثنين', 
                            'tuesday' => 'الثلاثاء',
                            'wednesday' => 'الأربعاء',
                            'thursday' => 'الخميس',
                            'friday' => 'الجمعة',
                            'saturday' => 'السبت',
                            'preferred_days' => 'الأيام المفضلة',
                            'preferred_time' => 'الوقت المفضل',
                            'morning' => 'صباحاً',
                            'afternoon' => 'بعد الظهر',
                            'evening' => 'مساءً',
                            'night' => 'ليلاً'
                        ];
                    @endphp
                    @if($schedule && is_array($schedule))
                        @foreach($schedule as $key => $value)
                            @if($key === 'preferred_days' || $key === 'preferred_time')
                                <div class="flex justify-between items-center">
                                    <span class="text-sm text-gray-600">{{ $arabicDays[$key] ?? $key }}</span>
                                    <span class="font-medium text-gray-900">
                                        @if(is_array($value))
                                            {{ implode(', ', array_map(function($item) use ($arabicDays) {
                                                return $arabicDays[strtolower($item)] ?? $item;
                                            }, $value)) }}
                                        @else
                                            {{ $arabicDays[strtolower($value)] ?? $value }}
                                        @endif
                                    </span>
                                </div>
                            @else
                                <div class="flex justify-between items-center">
                                    <span class="text-sm text-gray-600">{{ $arabicDays[strtolower($key)] ?? $key }}</span>
                                    <span class="font-medium text-gray-900">
                                        @if(is_array($value))
                                            {{ implode(', ', $value) }}
                                        @else
                                            {{ $value }}
                                        @endif
                                    </span>
                                </div>
                            @endif
                        @endforeach
                    @endif
                </div>
            </div>
        @endif

        <!-- Notes -->
        @if($subscription->notes)
            <div class="bg-gray-50 rounded-xl p-4 border border-gray-200">
                <div class="flex items-center mb-2">
                    <i class="ri-sticky-note-line ml-2 text-amber-600"></i>
                    <span class="text-sm font-bold text-amber-700 uppercase tracking-wide">ملاحظات</span>
                </div>
                <p class="text-sm text-gray-700 leading-relaxed">{{ $subscription->notes }}</p>
            </div>
        @endif
    </div>
</div>
