@props([
    'lesson',
    'lessonType' => 'quran', // 'quran' or 'academic'
    'viewType' => 'student', // 'student', 'teacher', 'supervisor'
    'context' => 'individual' // 'group', 'individual'
])

@php
    $isAcademic = $lessonType === 'academic';
    
    if ($isAcademic) {
        $teacher = $lesson->academicTeacher ?? null;
        $teacherRoute = $teacher ? route('public.academic-teachers.show', ['subdomain' => auth()->user()->academy->subdomain ?? 'itqan-academy', 'teacher' => $teacher->id]) : '#';
        $package = $lesson->academicPackage ?? null;
    } else {
        $teacher = $lesson->quranTeacher ?? null;
        $teacherRoute = $teacher ? route('public.quran-teachers.show', ['subdomain' => auth()->user()->academy->subdomain ?? 'itqan-academy', 'teacher' => $teacher->id]) : '#';
        $package = $lesson->subscription->package ?? null;
    }
@endphp

<div class="bg-white rounded-xl shadow-sm border border-gray-200 p-6 mb-6">
    <div class="flex items-center justify-between mb-6">
        <h3 class="text-lg font-bold text-gray-900">
            @if($isAcademic)
                تفاصيل الدرس
            @else
                تفاصيل الحلقة
            @endif
        </h3>
        <div class="w-10 h-10 bg-primary-50 rounded-lg flex items-center justify-center">
            <i class="ri-information-line text-primary-600"></i>
        </div>
    </div>
    
    <div class="space-y-4">
        <!-- Teacher Card (Clickable) -->
        <div class="bg-white rounded-lg border border-gray-200 overflow-hidden">
            @if($teacher)
                <a href="{{ $teacherRoute }}" 
                   class="block p-4 hover:bg-gray-50 transition-colors">
                    <div class="flex items-center space-x-3 space-x-reverse">
                        <x-avatar
                            :user="$teacher"
                            size="sm"
                            :userType="$isAcademic ? 'academic_teacher' : 'quran_teacher'"
                            :gender="$teacher->gender ?? 'male'"
                            class="flex-shrink-0" />
                        <div class="flex-1">
                            <span class="text-xs font-medium text-blue-600 uppercase tracking-wide">المعلم</span>
                            <p class="font-bold text-blue-900 text-sm">{{ $teacher->full_name ?? $teacher->name ?? 'غير محدد' }}</p>
                            @if($viewType === 'student' && $teacher->teaching_experience_years)
                                <p class="text-xs text-blue-700 mt-1">{{ $teacher->teaching_experience_years }} سنوات خبرة</p>
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

        @if($isAcademic)
            <!-- Academic Lesson Details -->
            
            <!-- Subject & Grade -->
            <div class="grid grid-cols-2 gap-3">
                <div class="bg-white rounded-lg p-3 border border-gray-200">
                    <div class="flex items-center space-x-2 space-x-reverse">
                        <i class="ri-book-open-line text-purple-600 text-sm"></i>
                        <span class="text-xs font-medium text-purple-600">المادة</span>
                    </div>
                    <p class="text-sm font-bold text-purple-900 mt-1">{{ $lesson->subject_name ?? 'مادة دراسية' }}</p>
                </div>
                
                <div class="bg-white rounded-lg p-3 border border-gray-200">
                    <div class="flex items-center space-x-2 space-x-reverse">
                        <i class="ri-trophy-line text-orange-600 text-sm"></i>
                        <span class="text-xs font-medium text-orange-600">المرحلة</span>
                    </div>
                    <p class="text-sm font-bold text-orange-900 mt-1">{{ $lesson->grade_level_name ?? 'مرحلة دراسية' }}</p>
                </div>
            </div>

            <!-- Subscription Info (full width under teacher) -->
            <div class="bg-white rounded-lg p-4 border border-gray-200">
                <div class="flex items-start space-x-3 space-x-reverse">
                    <div class="w-8 h-8 bg-green-100 rounded-full flex items-center justify-center flex-shrink-0 mt-1">
                        <i class="ri-bookmark-line text-green-600 text-sm"></i>
                    </div>
                    <div class="flex-1">
                        <span class="text-xs font-medium text-green-600 uppercase tracking-wide">الاشتراك</span>
                        <div class="space-y-1">
                            <p class="font-bold text-green-900 text-sm">{{ $lesson->subscription_code ?? 'اشتراك أكاديمي' }}</p>
                            <p class="text-xs text-green-700 flex items-center">
                                <i class="ri-calendar-line ml-1"></i>
                                بدأ: {{ $lesson->start_date ? $lesson->start_date->format('Y-m-d') : 'غير محدد' }}
                            </p>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Session Duration -->
            <div class="bg-white rounded-lg p-3 border border-gray-200">
                <div class="flex items-center space-x-2 space-x-reverse">
                    <i class="ri-timer-line text-pink-600 text-sm"></i>
                    <span class="text-xs font-medium text-pink-600">مدة الجلسة</span>
                </div>
                <p class="text-sm font-bold text-pink-900 mt-1">{{ $package->session_duration_minutes ?? '60' }} دقيقة</p>
            </div>

        @else
            <!-- Quran Circle Details -->
            
            <!-- Schedule Card -->
            @if($context === 'group' && $lesson->schedule && $lesson->schedule->weekly_schedule)
                <div class="bg-white rounded-lg p-4 border border-gray-200">
                    <div class="flex items-start space-x-3 space-x-reverse">
                        <div class="w-8 h-8 bg-green-100 rounded-full flex items-center justify-center flex-shrink-0 mt-1">
                            <i class="ri-calendar-line text-green-600 text-sm"></i>
                        </div>
                        <div class="flex-1">
                            <span class="text-xs font-medium text-green-600 uppercase tracking-wide">الجدول</span>
                            <div class="space-y-1">
                                <p class="font-bold text-green-900 text-sm">{{ $lesson->schedule_days_text }}</p>
                                @if($lesson->schedule->weekly_schedule && count($lesson->schedule->weekly_schedule) > 0)
                                    @foreach($lesson->schedule->weekly_schedule as $scheduleItem)
                                        <p class="text-xs text-green-700 flex items-center">
                                            <i class="ri-time-line ml-1"></i>
                                            {{ $scheduleItem['time'] ?? 'غير محدد' }}
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
            @elseif($context === 'individual' && $lesson->subscription)
                <!-- Subscription Card for Individual Circles -->
                <div class="bg-white rounded-lg p-4 border border-gray-200">
                    <div class="flex items-start space-x-3 space-x-reverse">
                        <div class="w-8 h-8 bg-green-100 rounded-full flex items-center justify-center flex-shrink-0 mt-1">
                            <i class="ri-bookmark-line text-green-600 text-sm"></i>
                        </div>
                        <div class="flex-1">
                            <span class="text-xs font-medium text-green-600 uppercase tracking-wide">الاشتراك</span>
                            <div class="space-y-1">
                                <p class="font-bold text-green-900 text-sm">{{ $lesson->subscription->package->name ?? 'اشتراك مخصص' }}</p>
                                @if($lesson->subscription->expires_at)
                                    <p class="text-xs text-green-700 flex items-center">
                                        <i class="ri-time-line ml-1"></i>
                                        ينتهي: {{ $lesson->subscription->expires_at->format('Y-m-d') }}
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
                <div class="bg-white rounded-lg p-3 border border-gray-200">
                    <div class="flex items-center space-x-2 space-x-reverse">
                        <i class="ri-book-open-line text-purple-600 text-sm"></i>
                        <span class="text-xs font-medium text-purple-600">التخصص</span>
                    </div>
                    <p class="text-sm font-bold text-purple-900 mt-1">
                        {{ $lesson->specialization === 'memorization' ? 'حفظ القرآن' : 
                           ($lesson->specialization === 'recitation' ? 'التلاوة' : 
                           ($lesson->specialization === 'interpretation' ? 'التفسير' : 
                           ($lesson->specialization === 'arabic_language' ? 'اللغة العربية' : 
                           ($lesson->specialization === 'complete' ? 'متكامل' : 'حفظ القرآن')))) }}
                    </p>
                </div>
                
                <!-- Level -->
                <div class="bg-white rounded-lg p-3 border border-gray-200">
                    <div class="flex items-center space-x-2 space-x-reverse">
                        <i class="ri-trophy-line text-orange-600 text-sm"></i>
                        <span class="text-xs font-medium text-orange-600">المستوى</span>
                    </div>
                    <p class="text-sm font-bold text-orange-900 mt-1">
                        {{ $lesson->memorization_level === 'beginner' ? 'مبتدئ' : 
                           ($lesson->specialization === 'elementary' ? 'ابتدائي' : 
                           ($lesson->memorization_level === 'intermediate' ? 'متوسط' : 
                           ($lesson->memorization_level === 'advanced' ? 'متقدم' : 
                           ($lesson->memorization_level === 'expert' ? 'خبير' : 'مبتدئ')))) }}
                    </p>
                </div>
            </div>
        @endif
        

    </div>
    
    <!-- Notes -->
    @if($lesson->notes)
        <div class="mt-6 pt-4 border-t border-gray-200">
            <span class="text-sm text-gray-600 flex items-center">
                <i class="ri-sticky-note-line ml-1"></i>
                ملاحظات:
            </span>
            <p class="mt-1 text-sm text-gray-700">{{ $lesson->notes }}</p>
        </div>
    @endif
</div>
