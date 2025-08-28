@props([
    'circle',
    'viewType' => 'student' // 'student', 'teacher', 'supervisor'
])

<div class="bg-white rounded-xl shadow-sm border border-gray-200 p-6 mb-6">
    <div class="flex items-center justify-between mb-6">
        <h3 class="text-lg font-bold text-gray-900">تفاصيل الحلقة</h3>
        <div class="w-10 h-10 bg-primary-50 rounded-lg flex items-center justify-center">
            <i class="ri-information-line text-primary-600"></i>
        </div>
    </div>
    
    <div class="space-y-4">
        <!-- Teacher Card (Clickable) -->
        <div class="bg-white rounded-lg border border-gray-200 overflow-hidden">
            @if($circle->quranTeacher)
                <a href="{{ route('public.quran-teachers.show', ['subdomain' => auth()->user()->academy->subdomain ?? 'itqan-academy', 'teacher' => $circle->quranTeacher->id]) }}" 
                   class="block p-4 hover:bg-gray-50 transition-colors">
                    <div class="flex items-center space-x-3 space-x-reverse">
                        <x-teacher-avatar 
                            :teacher="$circle->quranTeacher" 
                            size="sm" 
                            class="flex-shrink-0" />
                        <div class="flex-1">
                            <span class="text-xs font-medium text-blue-600 uppercase tracking-wide">المعلم</span>
                            <p class="font-bold text-blue-900 text-sm">{{ $circle->quranTeacher->user->name ?? 'غير محدد' }}</p>
                            @if($viewType === 'student' && $circle->quranTeacher->teaching_experience_years)
                                <p class="text-xs text-blue-700 mt-1">{{ $circle->quranTeacher->teaching_experience_years }} سنوات خبرة</p>
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
        @if($circle->schedule_days || $circle->schedule_time)
            <div class="bg-white rounded-lg p-4 border border-gray-200">
                <div class="flex items-start space-x-3 space-x-reverse">
                    <div class="w-8 h-8 bg-green-100 rounded-full flex items-center justify-center flex-shrink-0 mt-1">
                        <i class="ri-calendar-line text-green-600 text-sm"></i>
                    </div>
                    <div class="flex-1">
                        <span class="text-xs font-medium text-green-600 uppercase tracking-wide">الجدول</span>
                        <div class="space-y-1">
                            @if($circle->schedule_days)
                                <p class="font-bold text-green-900 text-sm">{{ $circle->schedule_days_text }}</p>
                            @endif
                            @if($circle->schedule_time)
                                <p class="text-xs text-green-700 flex items-center">
                                    <i class="ri-time-line ml-1"></i>
                                    {{ \Carbon\Carbon::parse($circle->schedule_time)->format('H:i') }}
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
        
        <!-- Capacity & Duration -->
        <div class="grid grid-cols-2 gap-3">
            <!-- Capacity -->
            <div class="bg-white rounded-lg p-3 border border-gray-200">
                <div class="flex items-center space-x-2 space-x-reverse">
                    <i class="ri-group-line text-teal-600 text-sm"></i>
                    <span class="text-xs font-medium text-teal-600">السعة</span>
                </div>
                <p class="text-sm font-bold text-teal-900 mt-1">{{ $circle->students ? $circle->students->count() : 0 }}/{{ $circle->max_students ?? '∞' }}</p>
            </div>

            <!-- Duration -->
            <div class="bg-white rounded-lg p-3 border border-gray-200">
                <div class="flex items-center space-x-2 space-x-reverse">
                    <i class="ri-timer-line text-pink-600 text-sm"></i>
                    <span class="text-xs font-medium text-pink-600">مدة الجلسة</span>
                </div>
                <p class="text-sm font-bold text-pink-900 mt-1">{{ $circle->default_duration_minutes ?? '60' }} دقيقة</p>
            </div>
        </div>

        @if($viewType === 'student')
            <!-- Student-specific info -->
            @if($circle->monthly_fee)
                <div class="bg-white rounded-lg p-3 border border-gray-200">
                    <div class="flex items-center space-x-2 space-x-reverse">
                        <i class="ri-money-dollar-circle-line text-yellow-600 text-sm"></i>
                        <span class="text-xs font-medium text-yellow-600">الرسوم الشهرية</span>
                    </div>
                    <p class="text-sm font-bold text-yellow-900 mt-1">{{ number_format($circle->monthly_fee) }} {{ $circle->currency ?? 'ريال' }}</p>
                </div>
            @endif
        @endif
    </div>
    
    <!-- Notes -->
    @if($circle->notes)
        <div class="mt-6 pt-4 border-t border-gray-200">
            <span class="text-sm text-gray-600 flex items-center">
                <i class="ri-sticky-note-line ml-1"></i>
                ملاحظات:
            </span>
            <p class="mt-1 text-sm text-gray-700">{{ $circle->notes }}</p>
        </div>
    @endif

    @if($viewType === 'teacher' && $circle->teacher_notes)
        <div class="mt-4 pt-4 border-t border-gray-200">
            <span class="text-sm text-gray-600 flex items-center">
                <i class="ri-user-star-line ml-1"></i>
                ملاحظات المعلم:
            </span>
            <p class="mt-1 text-sm text-gray-700">{{ $circle->teacher_notes }}</p>
        </div>
    @endif
</div>
