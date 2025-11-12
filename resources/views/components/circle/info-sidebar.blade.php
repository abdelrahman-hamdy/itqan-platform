@props([
    'circle',
    'viewType' => 'student', // 'student', 'teacher', 'supervisor'
    'context' => 'group', // 'group', 'individual'
    'type' => 'quran' // 'quran', 'academic'
])

<div class="bg-white rounded-xl shadow-sm border border-gray-200 p-6 mb-6">
    <div class="flex items-center justify-between mb-6">
        <h3 class="text-lg font-bold text-gray-900">{{ $type === 'academic' ? 'تفاصيل الدرس' : 'تفاصيل الحلقة' }}</h3>
        <div class="w-10 h-10 bg-primary-50 rounded-lg flex items-center justify-center">
            <i class="ri-information-line text-primary-600"></i>
        </div>
    </div>
    
    <div class="space-y-4">
        <!-- Teacher Card (Clickable) -->
        <div class="bg-white rounded-lg border border-gray-200 overflow-hidden">
            @php
                $teacher = $type === 'academic' ? ($circle->teacher ?? null) : ($circle->quranTeacher ?? null);
                $teacherRoute = $type === 'academic' ? 'public.academic-teachers.show' : 'public.quran-teachers.show';
            @endphp
            @if($teacher)
                <a href="{{ route($teacherRoute, ['subdomain' => auth()->user()->academy->subdomain ?? 'itqan-academy', 'teacher' => $teacher->id]) }}" 
                   class="block p-4 hover:bg-gray-50 transition-colors">
                    <div class="flex items-center space-x-3 space-x-reverse">
                        <x-teacher-avatar 
                            :teacher="$teacher" 
                            size="sm" 
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

        <!-- Schedule Card -->
        @if($context === 'group' && $circle->schedule && $circle->schedule->weekly_schedule)
            <div class="bg-white rounded-lg p-4 border border-gray-200">
                <div class="flex items-start space-x-3 space-x-reverse">
                    <div class="w-8 h-8 bg-green-100 rounded-full flex items-center justify-center flex-shrink-0 mt-1">
                        <i class="ri-calendar-line text-green-600 text-sm"></i>
                    </div>
                    <div class="flex-1">
                        <span class="text-xs font-medium text-green-600 uppercase tracking-wide">الجدول</span>
                        <div class="space-y-1">
                            <p class="font-bold text-green-900 text-sm">{{ $circle->schedule_days_text }}</p>
                            @if($circle->schedule->weekly_schedule && count($circle->schedule->weekly_schedule) > 0)
                                @foreach($circle->schedule->weekly_schedule as $scheduleItem)
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
        @elseif($context === 'individual' && $circle->subscription)
            <!-- Subscription Card for Individual Circles -->
            <div class="bg-white rounded-lg p-4 border border-gray-200">
                <div class="flex items-start space-x-3 space-x-reverse">
                    <div class="w-8 h-8 bg-green-100 rounded-full flex items-center justify-center flex-shrink-0 mt-1">
                        <i class="ri-bookmark-line text-green-600 text-sm"></i>
                    </div>
                    <div class="flex-1">
                        <span class="text-xs font-medium text-green-600 uppercase tracking-wide">الاشتراك</span>
                        <div class="space-y-1">
                            <p class="font-bold text-green-900 text-sm">{{ $circle->subscription->package->name ?? 'اشتراك مخصص' }}</p>
                            @if($circle->subscription->expires_at)
                                <p class="text-xs text-green-700 flex items-center">
                                    <i class="ri-time-line ml-1"></i>
                                    ينتهي: {{ $circle->subscription->expires_at->format('Y-m-d') }}
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
                    {{ $circle->specialization === 'memorization' ? 'حفظ القرآن' : 
                       ($circle->specialization === 'recitation' ? 'التلاوة' : 
                       ($circle->specialization === 'interpretation' ? 'التفسير' : 
                       ($circle->specialization === 'arabic_language' ? 'اللغة العربية' : 
                       ($circle->specialization === 'complete' ? 'متكامل' : 'حفظ القرآن')))) }}
                </p>
            </div>
            
            <!-- Level -->
            <div class="bg-white rounded-lg p-3 border border-gray-200">
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
                    <div class="bg-white rounded-lg p-3 border border-gray-200">
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
                    <div class="bg-white rounded-lg p-3 border border-gray-200">
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
                <div class="bg-white rounded-lg p-3 border border-gray-200">
                    <div class="flex items-center space-x-2 space-x-reverse">
                        <i class="ri-group-line text-teal-600 text-sm"></i>
                        <span class="text-xs font-medium text-teal-600">السعة</span>
                    </div>
                    <p class="text-sm font-bold text-teal-900 mt-1">{{ $circle->students ? $circle->students->count() : 0 }}/{{ $circle->max_students ?? '∞' }}</p>
                </div>
            @else
                <!-- Student Info for Individual -->
                @if($circle->student)
                    <div class="bg-white rounded-lg p-3 border border-gray-200">
                        <div class="flex items-center space-x-2 space-x-reverse">
                            <i class="ri-user-line text-teal-600 text-sm"></i>
                            <span class="text-xs font-medium text-teal-600">الطالب</span>
                        </div>
                        <p class="text-sm font-bold text-teal-900 mt-1">{{ $circle->student->name ?? 'طالب' }}</p>
                    </div>
                @endif
            @endif

            <!-- Duration -->
            <div class="bg-white rounded-lg p-3 border border-gray-200">
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

        @if($viewType === 'student')
            <!-- Student-specific info -->
            @if($context === 'group' && $circle->monthly_fee)
                <div class="bg-white rounded-lg p-3 border border-gray-200">
                    <div class="flex items-center space-x-2 space-x-reverse">
                        <i class="ri-money-dollar-circle-line text-yellow-600 text-sm"></i>
                        <span class="text-xs font-medium text-yellow-600">الرسوم الشهرية</span>
                    </div>
                    <p class="text-sm font-bold text-yellow-900 mt-1">{{ number_format($circle->monthly_fee) }} {{ $circle->currency ?? 'ريال' }}</p>
                </div>
            @elseif($context === 'individual' && $circle->subscription && $circle->subscription->package)
                <div class="bg-white rounded-lg p-3 border border-gray-200">
                    <div class="flex items-center space-x-2 space-x-reverse">
                        <i class="ri-money-dollar-circle-line text-yellow-600 text-sm"></i>
                        <span class="text-xs font-medium text-yellow-600">قيمة الباقة</span>
                    </div>
                    <p class="text-sm font-bold text-yellow-900 mt-1">{{ number_format($circle->subscription->package->price ?? 0) }} {{ $circle->subscription->package->currency ?? 'ريال' }}</p>
                </div>
            @endif
        @endif
    </div>
    
    <!-- Notes -->
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


</div>
