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
        <h2 class="text-3xl font-bold text-gray-900 mb-2">
            {{ $subject->name ?? $subscription->subject_name ?? 'درس خاص' }}
        </h2>
        <p class="text-sm text-blue-600 font-medium">
            {{ $gradeLevel ? $gradeLevel->getDisplayName() : ($subscription->grade_level_name ?? __('components.academic.level_unspecified')) }}
        </p>
    </div>

    <div class="space-y-4">
        <!-- Teacher Information -->
        @if($teacher)
            @if($viewType === 'student')
                <a href="{{ route('academic-teachers.show', ['subdomain' => auth()->user()->academy->subdomain ?? 'itqan-academy', 'teacherId' => $teacher->id]) }}"
                   class="block bg-gray-50 rounded-xl p-4 border border-gray-200 hover:bg-gray-100 hover:border-gray-300 transition-all duration-200 group">
                    <div class="flex items-center gap-3">
                        <x-avatar
                            :user="$teacher"
                            size="sm"
                            userType="academic_teacher"
                            :gender="$teacher->gender ?? 'male'"
                            class="flex-shrink-0" />
                        <div class="flex-1">
                            <span class="text-xs font-bold text-violet-700 uppercase tracking-wide">المعلم</span>
                            <p class="font-bold text-gray-900 text-base">
                                {{ $teacher->first_name }} {{ $teacher->last_name }}
                            </p>
                            @if($teacher->experience_years)
                                <p class="text-xs text-violet-600 mt-1">
                                    <i class="ri-medal-line ms-1"></i>
                                    {{ $teacher->experience_years }} سنوات خبرة
                                </p>
                            @endif
                        </div>
                        <i class="ri-external-link-line text-violet-500 text-md"></i>
                    </div>
                </a>
            @else
                <div class="bg-gray-50 rounded-xl p-4 border border-gray-200">
                    <div class="flex items-center gap-3">
                        <x-avatar
                            :user="$teacher"
                            size="sm"
                            userType="academic_teacher"
                            :gender="$teacher->gender ?? 'male'"
                            class="flex-shrink-0" />
                        <div class="flex-1">
                            <span class="text-xs font-bold text-violet-700 uppercase tracking-wide">المعلم</span>
                            <p class="font-bold text-gray-900 text-base">
                                {{ $teacher->first_name }} {{ $teacher->last_name }}
                            </p>
                            @if($teacher->experience_years)
                                <p class="text-xs text-violet-600 mt-1">
                                    <i class="ri-medal-line ms-1"></i>
                                    {{ $teacher->experience_years }} سنوات خبرة
                                </p>
                            @endif
                        </div>
                    </div>
                </div>
            @endif
        @endif

        <!-- Student Information (for teacher view) -->
        @if($viewType === 'teacher' && $student)
            <div class="bg-gray-50 rounded-xl p-4 border border-gray-200">
                <div class="flex items-center gap-3">
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

        <!-- Session Configuration -->
        <div class="grid grid-cols-2 gap-3">
            <!-- Sessions per Month -->
            <div class="bg-violet-50 rounded-xl p-3 border border-violet-200 text-center">
                <div class="flex items-center justify-center gap-2 mb-2">
                    <i class="ri-calendar-check-line text-violet-600 text-lg"></i>
                    <span class="text-xs text-violet-700 font-medium">الجلسات الشهرية</span>
                </div>
                <p class="text-2xl font-bold text-violet-900">{{ $subscription->sessions_per_month ?? 8 }}</p>
            </div>

            <!-- Session Duration -->
            <div class="bg-emerald-50 rounded-xl p-3 border border-emerald-200 text-center">
                <div class="flex items-center justify-center gap-2 mb-2">
                    <i class="ri-timer-line text-emerald-600 text-lg"></i>
                    <span class="text-xs text-emerald-700 font-medium">مدة الجلسة</span>
                </div>
                <p class="text-2xl font-bold text-emerald-900">{{ $subscription->session_duration_minutes ?? 60 }} <span class="text-sm">دقيقة</span></p>
            </div>
        </div>

        <!-- Weekly Schedule -->
        @if($subscription->weekly_schedule)
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
            <div class="bg-gray-50 rounded-xl p-4 border border-gray-200">
                @if($schedule && is_array($schedule))
                    <div class="space-y-3">
                        @foreach($schedule as $key => $value)
                            <div class="flex items-center justify-between">
                                <span class="text-sm text-gray-600 flex items-center">
                                    <i class="ri-calendar-line ms-2 text-purple-600"></i>
                                    {{ $arabicDays[$key] ?? $key }}
                                </span>
                                <span class="font-medium {{ ($key === 'preferred_days' || $key === 'preferred_time') ? 'text-violet-600' : 'text-gray-900' }}">
                                    @if($key === 'preferred_days' || $key === 'preferred_time')
                                        @if(is_array($value))
                                            {{ implode(', ', array_map(function($item) use ($arabicDays) {
                                                return $arabicDays[strtolower($item)] ?? $item;
                                            }, $value)) }}
                                        @else
                                            {{ $arabicDays[strtolower($value)] ?? $value }}
                                        @endif
                                    @else
                                        @if(is_array($value))
                                            {{ implode(', ', $value) }}
                                        @else
                                            {{ $value }}
                                        @endif
                                    @endif
                                </span>
                            </div>
                        @endforeach
                    </div>
                @endif
            </div>
        @endif

        <!-- Notes -->
        @if($subscription->notes)
            <div class="bg-gray-50 rounded-xl p-4 border border-gray-200">
                <div class="flex items-center mb-2">
                    <i class="ri-sticky-note-line ms-2 text-amber-600"></i>
                    <span class="text-sm font-bold text-amber-700 uppercase tracking-wide">ملاحظات</span>
                </div>
                <p class="text-sm text-gray-700 leading-relaxed">{{ $subscription->notes }}</p>
            </div>
        @endif
    </div>
</div>
