@props([
    'circle',
    'viewType' => 'student', // 'student', 'teacher', 'supervisor'
    'context' => 'group', // 'group', 'individual'
    'type' => 'quran' // 'quran', 'academic'
])

<div class="bg-white rounded-xl shadow-sm border border-gray-200 p-6 mb-6">
    <h3 class="font-bold text-gray-900 mb-6 flex items-center gap-2">
        <i class="ri-information-line text-green-500 text-lg" style="font-weight: 100;"></i>
        {{ $type === 'academic' ? 'تفاصيل الدرس' : 'تفاصيل الحلقة' }}
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
                    <div class="flex items-center space-x-3 space-x-reverse">
                        <x-avatar
                            :user="$teacher"
                            size="sm"
                            :userType="$type === 'academic' ? 'academic_teacher' : 'quran_teacher'"
                            :gender="$teacher->gender ?? 'male'"
                            class="flex-shrink-0" />
                        <div class="flex-1">
                            <span class="text-xs font-medium text-blue-600 uppercase tracking-wide">المعلم</span>
                            <p class="font-bold text-blue-900 text-sm">
                                @if($type === 'academic')
                                    {{ $teacher->first_name }} {{ $teacher->last_name }}
                                @else
                                    {{ $teacher->name ?? 'غير محدد' }}
                                @endif
                            </p>
                            @if($viewType === 'student')
                                @if($type === 'academic' && $teacher->experience_years)
                                    <p class="text-xs text-blue-700 mt-1">{{ $teacher->experience_years }} سنوات خبرة</p>
                                @elseif($type === 'quran' && $teacher->teaching_experience_years)
                                    <p class="text-xs text-blue-700 mt-1">{{ $teacher->teaching_experience_years }} سنوات خبرة</p>
                                @endif
                            @endif
                        </div>
                        <i class="ri-external-link-line text-blue-600 text-sm"></i>
                    </div>
                </a>
            @else
                <div class="p-4">
                    <div class="flex items-center space-x-3 space-x-reverse">
                        <div class="w-8 h-8 bg-gray-100 rounded-full flex items-center justify-center flex-shrink-0">
                            <i class="ri-user-line text-gray-400 text-sm"></i>
                        </div>
                        <div class="flex-1">
                            <span class="text-xs font-medium text-blue-600 uppercase tracking-wide">المعلم</span>
                            <p class="font-bold text-blue-900 text-sm">غير محدد</p>
                        </div>
                    </div>
                </div>
            @endif
        </div>

        <!-- Student Card (For Individual Circles Only, Non-clickable) -->
        @if($context === 'individual' && $circle->student)
            <div class="bg-gray-50 rounded-lg border border-gray-200 overflow-hidden">
                <div class="p-4">
                    <div class="flex items-center space-x-3 space-x-reverse">
                        <x-avatar
                            :user="$circle->student"
                            size="sm"
                            userType="student"
                            :gender="$circle->student->gender ?? 'male'"
                            class="flex-shrink-0" />
                        <div class="flex-1">
                            <span class="text-xs font-medium text-blue-600 uppercase tracking-wide">الطالب</span>
                            <p class="font-bold text-blue-900 text-sm">
                                {{ $circle->student->name ?? 'غير محدد' }}
                            </p>
                        </div>
                    </div>
                </div>
            </div>
        @endif

        <!-- Schedule Card -->
        @if($context === 'group' && $circle->schedule && $circle->schedule->weekly_schedule)
            <div class="bg-gray-50 rounded-lg p-4 border border-gray-200">
                <div class="flex items-start space-x-3 space-x-reverse">
                    <div class="w-8 h-8 bg-green-100 rounded-full flex items-center justify-center flex-shrink-0 mt-1">
                        <i class="ri-calendar-line text-green-600 text-sm"></i>
                    </div>
                    <div class="flex-1">
                        <span class="text-xs font-medium text-green-600 uppercase tracking-wide">الجدول</span>
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
                                        <i class="ri-time-line ml-1"></i>
                                        @php
                                            $time = $scheduleItem['time'] ?? 'غير محدد';
                                            if ($time !== 'غير محدد') {
                                                try {
                                                    // Parse the time and convert to 12-hour format
                                                    $carbonTime = \Carbon\Carbon::parse($time);
                                                    $hour = $carbonTime->format('g'); // Hour without leading zeros
                                                    $period = $carbonTime->format('a') === 'am' ? 'صباحاً' : 'مساءً';
                                                    $time = $hour . ' ' . $period;
                                                } catch (\Exception $e) {
                                                    // Keep original time if parsing fails
                                                }
                                            }
                                        @endphp
                                        {{ $time }}
                                        @if(isset($scheduleItem['duration']))
                                            ({{ $scheduleItem['duration'] }} دقيقة)
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
                <div class="flex items-start space-x-3 space-x-reverse">
                    <div class="w-8 h-8 bg-blue-100 rounded-full flex items-center justify-center flex-shrink-0 mt-1">
                        <i class="ri-calendar-check-line text-blue-600 text-sm"></i>
                    </div>
                    <div class="flex-1">
                        <span class="text-xs font-medium text-blue-600 uppercase tracking-wide">تاريخ بدء الحلقة</span>
                        <div class="space-y-1">
                            <p class="font-bold text-blue-900 text-sm">
                                @if($circle->subscription->starts_at)
                                    {{ $circle->subscription->starts_at->locale('ar')->translatedFormat('d F Y') }}
                                @else
                                    غير محدد
                                @endif
                            </p>
                            @if($circle->subscription->expires_at)
                                <p class="text-xs text-blue-700 flex items-center">
                                    <i class="ri-time-line ml-1"></i>
                                    ينتهي: {{ $circle->subscription->expires_at->locale('ar')->translatedFormat('d F Y') }}
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
                <div class="flex items-center space-x-2 space-x-reverse">
                    <i class="ri-book-open-line text-purple-600 text-sm"></i>
                    <span class="text-xs font-medium text-purple-600">التخصص</span>
                </div>
                <p class="text-sm font-bold text-purple-900 mt-1">
                    {{ $circle->specialization === 'memorization' ? 'حفظ القرآن' : 
                       ($circle->specialization === 'recitation' ? 'التلاوة' : 
                       ($circle->specialization === 'interpretation' ? 'التفسير' : 
                       ($circle->specialization === 'arabic_language' ? 'اللغة العربية' : 
                       ($circle->specialization === 'complete' ? 'متكامل' : 'حفظ القرآن')))) }}
                </p>
            </div>

            <!-- Level -->
            <div class="bg-gray-50 rounded-lg p-3 border border-gray-200">
                <div class="flex items-center space-x-2 space-x-reverse">
                    <i class="ri-trophy-line text-orange-600 text-sm"></i>
                    <span class="text-xs font-medium text-orange-600">المستوى</span>
                </div>
                <p class="text-sm font-bold text-orange-900 mt-1">
                    {{ $circle->memorization_level === 'beginner' ? 'مبتدئ' : 
                       ($circle->specialization === 'elementary' ? 'ابتدائي' : 
                       ($circle->memorization_level === 'intermediate' ? 'متوسط' : 
                       ($circle->memorization_level === 'advanced' ? 'متقدم' : 
                       ($circle->memorization_level === 'expert' ? 'خبير' : 'مبتدئ')))) }}
                </p>
            </div>
        </div>
        
        <!-- Age Group & Gender Type (50% width each) -->
        @if($circle->age_group || $circle->gender_type)
            <div class="grid grid-cols-2 gap-3">
                <!-- Age Group -->
                @if($circle->age_group)
                    <div class="bg-gray-50 rounded-lg p-3 border border-gray-200">
                        <div class="flex items-center space-x-2 space-x-reverse">
                            <i class="ri-user-3-line text-indigo-600 text-sm"></i>
                            <span class="text-xs font-medium text-indigo-600">الفئة العمرية</span>
                        </div>
                        <p class="text-sm font-bold text-indigo-900 mt-1">
                            @switch($circle->age_group)
                                @case('children') أطفال @break
                                @case('youth') شباب @break
                                @case('adults') كبار @break
                                @case('all_ages') كل الفئات @break
                                @default {{ $circle->age_group }}
                            @endswitch
                        </p>
                    </div>
                @endif

                <!-- Gender Type -->
                @if($circle->gender_type)
                    <div class="bg-gray-50 rounded-lg p-3 border border-gray-200">
                        <div class="flex items-center space-x-2 space-x-reverse">
                            <i class="ri-group-2-line text-cyan-600 text-sm"></i>
                            <span class="text-xs font-medium text-cyan-600">النوع</span>
                        </div>
                        <p class="text-sm font-bold text-cyan-900 mt-1">
                            {{ $circle->gender_type === 'male' ? 'رجال' : ($circle->gender_type === 'female' ? 'نساء' : 'مختلط') }}
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
                    <div class="flex items-center space-x-2 space-x-reverse">
                        <i class="ri-group-line text-teal-600 text-sm"></i>
                        <span class="text-xs font-medium text-teal-600">السعة</span>
                    </div>
                    <p class="text-sm font-bold text-teal-900 mt-1">{{ $circle->students ? $circle->students->count() : 0 }}/{{ $circle->max_students ?? '∞' }}</p>
                </div>
            @else
                <!-- Sessions Number (Individual only) -->
                <div class="bg-gray-50 rounded-lg p-3 border border-gray-200">
                    <div class="flex items-center space-x-2 space-x-reverse">
                        <i class="ri-calendar-check-line text-teal-600 text-sm"></i>
                        <span class="text-xs font-medium text-teal-600">عدد الجلسات</span>
                    </div>
                    <p class="text-sm font-bold text-teal-900 mt-1">
                        {{ $circle->subscription?->package?->total_sessions ?? ($circle->subscription?->total_sessions ?? 'غير محدد') }}
                        @if($circle->subscription?->package?->total_sessions || $circle->subscription?->total_sessions)
                            جلسة
                        @endif
                    </p>
                </div>
            @endif

            <!-- Duration -->
            <div class="bg-gray-50 rounded-lg p-3 border border-gray-200">
                <div class="flex items-center space-x-2 space-x-reverse">
                    <i class="ri-timer-line text-pink-600 text-sm"></i>
                    <span class="text-xs font-medium text-pink-600">مدة الجلسة</span>
                </div>
                <p class="text-sm font-bold text-pink-900 mt-1">
                    @if($context === 'group')
                        {{ $circle->session_duration_minutes ?? 60 }} دقيقة
                    @else
                        {{ $circle->default_duration_minutes ?? 60 }} دقيقة
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
                <span class="text-sm text-gray-600 flex items-center">
                    <i class="ri-sticky-note-line ml-1"></i>
                    ملاحظات{{ $context === 'individual' ? ' الطالب' : '' }}:
                </span>
                <p class="mt-1 text-sm text-gray-700">{{ $notesToShow }}</p>
            </div>
        @endif
    @endif


</div>
