@props([
    'circle',
    'viewType' => 'student', // 'student', 'teacher', 'supervisor'
    'context' => 'group', // 'group', 'individual'
    'type' => 'quran' // 'quran', 'academic'
])

<div class="bg-white rounded-xl shadow-sm border border-gray-200 p-6 mb-6">
    <h3 class="font-bold text-gray-900 mb-6 flex items-center gap-2">
        <i class="ri-information-line text-green-500 text-lg" style="font-weight: 100;"></i>
        {{ $type === 'academic' ? __('components.circle.info_sidebar.title_academic') : __('components.circle.info_sidebar.title') }}
    </h3>
    
    <div class="space-y-4">
        <!-- Teacher Card (Clickable) -->
        <div class="bg-gray-50 rounded-lg border border-gray-200 overflow-hidden">
            @php
                // Get teacher profile (QuranTeacherProfile or AcademicTeacherProfile)
                $teacherProfile = $type === 'academic' ? ($circle->teacher ?? null) : ($circle->quranTeacher ?? null);

                // For teacher view, link to their profile page. For student view, link to public teacher page
                if ($viewType === 'teacher') {
                    $teacherRoute = 'teacher.profile';
                    $teacherRouteParams = ['subdomain' => auth()->user()->academy->subdomain ?? 'itqan-academy'];
                } else {
                    $teacherRoute = $type === 'academic' ? 'academic-teachers.show' : 'quran-teachers.show';
                    // Use the profile ID for the route
                    // For Quran teachers, check if we have a User with quranTeacherProfile, or direct profile
                    if ($type === 'quran' && $teacherProfile) {
                        $profileId = $teacherProfile->quranTeacherProfile->id ?? $teacherProfile->id ?? 0;
                    } else {
                        $profileId = $teacherProfile->id ?? 0;
                    }
                    $teacherRouteParams = ['subdomain' => auth()->user()->academy->subdomain ?? 'itqan-academy', 'teacherId' => $profileId];
                }

                // Get the actual User model for display purposes
                $teacher = $teacherProfile?->user ?? $teacherProfile;
            @endphp
            @if($teacherProfile)
                <a href="{{ route($teacherRoute, $teacherRouteParams) }}"
                   class="block p-4 hover:bg-gray-100 transition-colors">
                    <div class="flex items-center gap-3">
                        <x-avatar
                            :user="$teacher"
                            size="sm"
                            :userType="$type === 'academic' ? 'academic_teacher' : 'quran_teacher'"
                            :gender="$teacher->gender ?? 'male'"
                            class="flex-shrink-0" />
                        <div class="flex-1">
                            <span class="text-xs font-medium text-blue-600 uppercase tracking-wide">{{ __('components.circle.info_sidebar.teacher') }}</span>
                            <p class="font-bold text-blue-900 text-sm">
                                @if($type === 'academic')
                                    {{ $teacher->first_name }} {{ $teacher->last_name }}
                                @else
                                    {{ $teacher->name ?? __('components.circle.info_sidebar.unspecified') }}
                                @endif
                            </p>
                            @if($viewType === 'student')
                                @if($type === 'academic' && $teacher->experience_years)
                                    <p class="text-xs text-blue-700 mt-1">{{ trans_choice('components.circle.info_sidebar.experience_years', $teacher->experience_years, ['count' => $teacher->experience_years]) }}</p>
                                @elseif($type === 'quran' && $teacher->teaching_experience_years)
                                    <p class="text-xs text-blue-700 mt-1">{{ trans_choice('components.circle.info_sidebar.experience_years', $teacher->teaching_experience_years, ['count' => $teacher->teaching_experience_years]) }}</p>
                                @endif
                            @endif
                        </div>
                        <i class="ri-external-link-line text-blue-600 text-sm"></i>
                    </div>
                </a>
            @else
                <div class="p-4">
                    <div class="flex items-center gap-3">
                        <div class="w-8 h-8 bg-gray-100 rounded-full flex items-center justify-center flex-shrink-0">
                            <i class="ri-user-line text-gray-400 text-sm"></i>
                        </div>
                        <div class="flex-1">
                            <span class="text-xs font-medium text-blue-600 uppercase tracking-wide">{{ __('components.circle.info_sidebar.teacher') }}</span>
                            <p class="font-bold text-blue-900 text-sm">{{ __('components.circle.info_sidebar.unspecified') }}</p>
                        </div>
                    </div>
                </div>
            @endif
        </div>

        <!-- Student Card (For Individual Circles Only, Non-clickable) -->
        @if($context === 'individual' && $circle->student)
            <div class="bg-gray-50 rounded-lg border border-gray-200 overflow-hidden">
                <div class="p-4">
                    <div class="flex items-center gap-3">
                        <x-avatar
                            :user="$circle->student"
                            size="sm"
                            userType="student"
                            :gender="$circle->student->gender ?? 'male'"
                            class="flex-shrink-0" />
                        <div class="flex-1">
                            <span class="text-xs font-medium text-blue-600 uppercase tracking-wide">{{ __('components.circle.info_sidebar.student_label') }}</span>
                            <p class="font-bold text-blue-900 text-sm">
                                {{ $circle->student->name ?? __('components.circle.info_sidebar.unspecified') }}
                            </p>
                        </div>
                    </div>
                </div>
            </div>
        @endif

        <!-- Schedule Card -->
        @if($context === 'group' && $circle->schedule && $circle->schedule->weekly_schedule)
            <div class="bg-gray-50 rounded-lg p-4 border border-gray-200">
                <div class="flex items-start gap-3">
                    <div class="w-8 h-8 bg-green-100 rounded-full flex items-center justify-center flex-shrink-0 mt-1">
                        <i class="ri-calendar-line text-green-600 text-sm"></i>
                    </div>
                    <div class="flex-1">
                        <span class="text-xs font-medium text-green-600 uppercase tracking-wide">{{ __('components.circle.info_sidebar.schedule') }}</span>
                        <div class="space-y-1">
                            <p class="font-bold text-green-900 text-sm">{{ $circle->schedule_days_text }}</p>
                            @if($circle->schedule->weekly_schedule && count($circle->schedule->weekly_schedule) > 0)
                                @php
                                    // Get unique times to avoid duplication
                                    $uniqueTimes = collect($circle->schedule->weekly_schedule)
                                        ->unique('time')
                                        ->values();
                                @endphp
                                @foreach($uniqueTimes as $scheduleItem)
                                    <p class="text-xs text-green-700 flex items-center">
                                        <i class="ri-time-line ms-1 rtl:ms-1 ltr:me-1"></i>
                                        @php
                                            $time = $scheduleItem['time'] ?? __('components.circle.info_sidebar.unspecified');
                                            if ($time !== __('components.circle.info_sidebar.unspecified')) {
                                                try {
                                                    // Parse the time and convert to 12-hour format
                                                    $carbonTime = \Carbon\Carbon::parse($time);
                                                    $hour = $carbonTime->format('g'); // Hour without leading zeros
                                                    $period = $carbonTime->format('a') === 'am' ? __('components.circle.info_sidebar.morning') : __('components.circle.info_sidebar.afternoon');
                                                    $time = $hour . ' ' . $period;
                                                } catch (\Exception $e) {
                                                    // Keep original time if parsing fails
                                                }
                                            }
                                        @endphp
                                        {{ $time }}
                                        @if(isset($scheduleItem['duration']))
                                            ({{ $scheduleItem['duration'] }} {{ __('components.circle.info_sidebar.minutes') }})
                                        @endif
                                    </p>
                                @endforeach
                            @endif
                        </div>
                    </div>
                </div>
            </div>
        @elseif($context === 'individual' && $circle->subscription)
            <!-- Circle Start Date for Individual Circles -->
            <div class="bg-gray-50 rounded-lg p-4 border border-gray-200">
                <div class="flex items-start gap-3">
                    <div class="w-8 h-8 bg-blue-100 rounded-full flex items-center justify-center flex-shrink-0 mt-1">
                        <i class="ri-calendar-check-line text-blue-600 text-sm"></i>
                    </div>
                    <div class="flex-1">
                        <span class="text-xs font-medium text-blue-600 uppercase tracking-wide">{{ __('components.circle.info_sidebar.circle_start_date') }}</span>
                        <div class="space-y-1">
                            <p class="font-bold text-blue-900 text-sm">
                                @if($circle->subscription->starts_at)
                                    {{ $circle->subscription->starts_at->locale(app()->getLocale())->translatedFormat('d F Y') }}
                                @else
                                    {{ __('components.circle.info_sidebar.unspecified') }}
                                @endif
                            </p>
                            @if($circle->subscription->expires_at)
                                <p class="text-xs text-blue-700 flex items-center">
                                    <i class="ri-time-line ms-1 rtl:ms-1 ltr:me-1"></i>
                                    {{ __('components.circle.info_sidebar.ends_on') }} {{ $circle->subscription->expires_at->locale(app()->getLocale())->translatedFormat('d F Y') }}
                                </p>
                            @endif
                        </div>
                    </div>
                </div>
            </div>
        @endif
        
        <!-- Learning Details Grid -->
        <div class="grid grid-cols-2 gap-3">
            <!-- Specialization -->
            <div class="bg-gray-50 rounded-lg p-3 border border-gray-200">
                <div class="flex items-center gap-2">
                    <i class="ri-book-open-line text-purple-600 text-sm"></i>
                    <span class="text-xs font-medium text-purple-600">{{ __('components.circle.info_sidebar.specialization') }}</span>
                </div>
                <p class="text-sm font-bold text-purple-900 mt-1">
                    @switch($circle->specialization)
                        @case('memorization') {{ __('components.circle.info_sidebar.memorization') }} @break
                        @case('recitation') {{ __('components.circle.info_sidebar.recitation') }} @break
                        @case('interpretation') {{ __('components.circle.info_sidebar.interpretation') }} @break
                        @case('arabic_language') {{ __('components.circle.info_sidebar.arabic_language') }} @break
                        @case('complete') {{ __('components.circle.info_sidebar.complete') }} @break
                        @default {{ __('components.circle.info_sidebar.memorization') }}
                    @endswitch
                </p>
            </div>

            <!-- Level -->
            <div class="bg-gray-50 rounded-lg p-3 border border-gray-200">
                <div class="flex items-center gap-2">
                    <i class="ri-trophy-line text-orange-600 text-sm"></i>
                    <span class="text-xs font-medium text-orange-600">{{ __('components.circle.info_sidebar.level') }}</span>
                </div>
                <p class="text-sm font-bold text-orange-900 mt-1">
                    @switch($circle->memorization_level)
                        @case('beginner') {{ __('components.circle.info_sidebar.beginner') }} @break
                        @case('elementary') {{ __('components.circle.info_sidebar.elementary') }} @break
                        @case('intermediate') {{ __('components.circle.info_sidebar.intermediate') }} @break
                        @case('advanced') {{ __('components.circle.info_sidebar.advanced') }} @break
                        @case('expert') {{ __('components.circle.info_sidebar.expert') }} @break
                        @default {{ __('components.circle.info_sidebar.beginner') }}
                    @endswitch
                </p>
            </div>
        </div>
        
        <!-- Age Group & Gender Type (50% width each) -->
        @if($circle->age_group || $circle->gender_type)
            <div class="grid grid-cols-2 gap-3">
                <!-- Age Group -->
                @if($circle->age_group)
                    <div class="bg-gray-50 rounded-lg p-3 border border-gray-200">
                        <div class="flex items-center gap-2">
                            <i class="ri-user-3-line text-indigo-600 text-sm"></i>
                            <span class="text-xs font-medium text-indigo-600">{{ __('components.circle.info_sidebar.age_group') }}</span>
                        </div>
                        <p class="text-sm font-bold text-indigo-900 mt-1">
                            @switch($circle->age_group)
                                @case('children') {{ __('components.circle.info_sidebar.children') }} @break
                                @case('youth') {{ __('components.circle.info_sidebar.youth') }} @break
                                @case('adults') {{ __('components.circle.info_sidebar.adults') }} @break
                                @case('all_ages') {{ __('components.circle.info_sidebar.all_ages') }} @break
                                @default {{ $circle->age_group }}
                            @endswitch
                        </p>
                    </div>
                @endif

                <!-- Gender Type -->
                @if($circle->gender_type)
                    <div class="bg-gray-50 rounded-lg p-3 border border-gray-200">
                        <div class="flex items-center gap-2">
                            <i class="ri-group-2-line text-cyan-600 text-sm"></i>
                            <span class="text-xs font-medium text-cyan-600">{{ __('components.circle.info_sidebar.gender_type') }}</span>
                        </div>
                        <p class="text-sm font-bold text-cyan-900 mt-1">
                            @switch($circle->gender_type)
                                @case('male') {{ __('components.circle.info_sidebar.male') }} @break
                                @case('female') {{ __('components.circle.info_sidebar.female') }} @break
                                @default {{ __('components.circle.info_sidebar.mixed') }}
                            @endswitch
                        </p>
                    </div>
                @endif
            </div>
        @endif
        
        <!-- Capacity & Duration -->
        <div class="grid grid-cols-2 gap-3">
            @if($context === 'group')
                <!-- Capacity -->
                <div class="bg-gray-50 rounded-lg p-3 border border-gray-200">
                    <div class="flex items-center gap-2">
                        <i class="ri-group-line text-teal-600 text-sm"></i>
                        <span class="text-xs font-medium text-teal-600">{{ __('components.circle.info_sidebar.capacity') }}</span>
                    </div>
                    <p class="text-sm font-bold text-teal-900 mt-1">{{ $circle->students ? $circle->students->count() : 0 }}/{{ $circle->max_students ?? '∞' }}</p>
                </div>
            @else
                <!-- Sessions Number (Individual only) -->
                <div class="bg-gray-50 rounded-lg p-3 border border-gray-200">
                    <div class="flex items-center gap-2">
                        <i class="ri-calendar-check-line text-teal-600 text-sm"></i>
                        <span class="text-xs font-medium text-teal-600">{{ __('components.circle.info_sidebar.sessions_number') }}</span>
                    </div>
                    <p class="text-sm font-bold text-teal-900 mt-1">
                        {{ $circle->subscription?->package?->total_sessions ?? ($circle->subscription?->total_sessions ?? __('components.circle.info_sidebar.unspecified')) }}
                        @if($circle->subscription?->package?->total_sessions || $circle->subscription?->total_sessions)
                            {{ __('components.circle.info_sidebar.session_count') }}
                        @endif
                    </p>
                </div>
            @endif

            <!-- Duration -->
            <div class="bg-gray-50 rounded-lg p-3 border border-gray-200">
                <div class="flex items-center gap-2">
                    <i class="ri-timer-line text-pink-600 text-sm"></i>
                    <span class="text-xs font-medium text-pink-600">{{ __('components.circle.info_sidebar.session_duration') }}</span>
                </div>
                <p class="text-sm font-bold text-pink-900 mt-1">
                    @if($context === 'group')
                        {{ $circle->session_duration_minutes ?? 60 }} {{ __('components.circle.info_sidebar.minutes') }}
                    @else
                        {{ $circle->default_duration_minutes ?? 60 }} {{ __('components.circle.info_sidebar.minutes') }}
                    @endif
                </p>
            </div>
        </div>
    </div>
    
    <!-- Notes - Only visible for teachers -->
    @if($viewType === 'teacher')
        @php
            // For individual circles, show subscription notes (user's notes during subscription)
            // For group circles, show circle notes (admin/teacher notes)
            $notesToShow = null;
            if ($context === 'individual' && $circle->subscription && $circle->subscription->notes) {
                $notesToShow = $circle->subscription->notes;
            } elseif ($context === 'group' && $circle->notes) {
                $notesToShow = $circle->notes;
            }
        @endphp

        @if($notesToShow)
            <div class="mt-6 pt-4 border-t border-gray-200">
                <span class="text-sm text-gray-600 flex items-center gap-1">
                    <i class="ri-sticky-note-line"></i>
                    {{ $context === 'individual' ? __('components.circle.info_sidebar.student_notes') : __('components.circle.info_sidebar.notes') }}
                </span>
                <p class="mt-1 text-sm text-gray-700">{{ $notesToShow }}</p>
            </div>
        @endif
    @endif


</div>
