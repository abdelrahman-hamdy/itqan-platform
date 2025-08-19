<x-layouts.student 
    :title="$circle->name . ' - ' . config('app.name', 'منصة إتقان')"
    :description="'تفاصيل حلقة القرآن: ' . $circle->name">

<div class="max-w-5xl mx-auto">
        <!-- Breadcrumb -->
        <nav class="mb-8">
            <ol class="flex items-center space-x-2 space-x-reverse text-sm text-gray-600">
                <li><a href="{{ route('student.quran-circles', ['subdomain' => auth()->user()->academy->subdomain ?? 'itqan-academy']) }}" class="hover:text-primary">حلقات القرآن</a></li>
                <li>/</li>
                <li class="text-gray-900">{{ $circle->name }}</li>
            </ol>
        </nav>

        <div class="grid grid-cols-1 lg:grid-cols-3 gap-8">
            <!-- Circle Content -->
            <div class="lg:col-span-2">
                <!-- Circle Header -->
                <div class="bg-white rounded-xl shadow-sm p-6 mb-8">
                    <div class="mb-6">
                        <h1 class="text-3xl font-bold text-gray-900 mb-2">{{ $circle->name_ar ?? $circle->name_en ?? $circle->name }}</h1>
                    </div>
                    
                    <!-- Teacher Info -->
                    @if($circle->quranTeacher)
                    <div class="relative p-6 bg-gradient-to-r from-gray-50 to-gray-100 rounded-lg border border-gray-200 mb-6">
                        <h3 class="text-lg font-bold text-gray-900 mb-4">معلم الحلقة</h3>
                        <div class="flex items-center gap-4">
                            @if($circle->quranTeacher->avatar)
                            <div class="w-16 h-16 rounded-full overflow-hidden border-2 border-gray-200">
                                <img src="{{ asset('storage/' . $circle->quranTeacher->avatar) }}" 
                                     alt="{{ $circle->quranTeacher->full_name }}" 
                                     class="w-full h-full object-cover">
                            </div>
                            @else
                            <div class="w-16 h-16 bg-primary-100 rounded-full flex items-center justify-center border-2 border-gray-200">
                                <svg class="w-8 h-8 text-primary" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"></path>
                                </svg>
                            </div>
                            @endif
                            <div class="flex-1">
                                <h4 class="font-semibold text-gray-900 text-lg">{{ $circle->quranTeacher->full_name }}</h4>
                                @if($circle->quranTeacher->teaching_experience_years)
                                <p class="text-sm text-gray-500">{{ $circle->quranTeacher->teaching_experience_years }} سنوات خبرة</p>
                                @endif
                                @if($circle->quranTeacher->specializations)
                                <p class="text-sm text-gray-500 mt-1">{{ is_array($circle->quranTeacher->specializations) ? implode('، ', $circle->quranTeacher->specializations) : $circle->quranTeacher->specializations }}</p>
                                @endif
                                
                                <!-- Teacher Profile Link -->
                                @if($circle->quranTeacher->teacher_code)
                                <div class="mt-3">
                                    <a href="{{ route('public.quran-teachers.show', ['subdomain' => auth()->user()->academy->subdomain ?? 'itqan-academy', 'teacher' => $circle->quranTeacher->id]) }}" 
                                       class="inline-flex items-center gap-2 text-primary hover:text-secondary text-sm font-medium transition-colors">
                                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"></path>
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"></path>
                                        </svg>
                                        عرض الملف الشخصي
                                    </a>
                                </div>
                                @endif
                            </div>
                        </div>
                    </div>
                    @endif
                    
                    <!-- Circle Stats -->
                    <div class="space-y-4 mb-6">
                        <!-- Enrolled Students -->
                        <div class="flex items-center gap-3">
                            <div class="w-8 h-8 bg-blue-100 rounded-full flex items-center justify-center">
                                <svg class="w-5 h-5 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197m13.5-8.5a3 3 0 11-6 0 3 3 0 016 0z"></path>
                                </svg>
                            </div>
                            <div>
                                <span class="text-lg font-semibold text-gray-900">{{ $circle->enrolled_students ?? 0 }}</span>
                                <span class="text-gray-600 mr-2">طالب منضم</span>
                            </div>
                        </div>
                        
                        <!-- Place Type -->
                        <div class="flex items-center gap-3">
                            @if($circle->room_link)
                                <div class="w-8 h-8 bg-purple-100 rounded-full flex items-center justify-center">
                                    <svg class="w-5 h-5 text-purple-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9.75 17L9 20l-1 1h8l-1-1-.75-3M3 13h18M5 17h14a2 2 0 002-2V5a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"></path>
                                    </svg>
                                </div>
                                <span class="text-lg font-semibold text-purple-600">عبر الإنترنت</span>
                            @else
                                <div class="w-8 h-8 bg-green-100 rounded-full flex items-center justify-center">
                                    <svg class="w-5 h-5 text-green-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4"></path>
                                    </svg>
                                </div>
                                <span class="text-lg font-semibold text-green-600">حضوري</span>
                            @endif
                        </div>
                        
                        <!-- Price (only if not enrolled) -->
                        @if($circle->monthly_fee && !$isEnrolled)
                        <div class="flex items-center gap-3">
                            <div class="w-8 h-8 bg-yellow-100 rounded-full flex items-center justify-center">
                                <svg class="w-5 h-5 text-yellow-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1"></path>
                                </svg>
                            </div>
                            <div>
                                <span class="text-lg font-semibold text-gray-900">{{ number_format($circle->monthly_fee) }}</span>
                                <span class="text-gray-600 mr-2">{{ $circle->currency ?? 'ريال' }}/شهرياً</span>
                            </div>
                        </div>
                        @endif
                    </div>
                    
                    <!-- Circle Information Labels -->
                    <div class="flex flex-wrap gap-3 mb-6">
                        @if($circle->gender_type)
                        <div class="inline-flex items-center gap-2 px-4 py-2 bg-purple-100 text-purple-800 rounded-lg">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z"></path>
                            </svg>
                            <span class="text-sm font-medium">
                                الجنس: {{ $circle->gender_type === 'male' ? 'رجال' : ($circle->gender_type === 'female' ? 'نساء' : 'مختلط') }}
                            </span>
                        </div>
                        @endif
                        
                        @if($circle->age_group)
                        <div class="inline-flex items-center gap-2 px-4 py-2 bg-blue-100 text-blue-800 rounded-lg">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                            </svg>
                            <span class="text-sm font-medium">
                                الفئة العمرية: 
                                @switch($circle->age_group)
                                    @case('children') أطفال @break
                                    @case('youth') شباب @break
                                    @case('adults') كبار @break
                                    @case('all_ages') كل الفئات @break
                                    @default {{ $circle->age_group }}
                                @endswitch
                            </span>
                        </div>
                        @endif
                        
                        @if($circle->circle_type)
                        <div class="inline-flex items-center gap-2 px-4 py-2 bg-green-100 text-green-800 rounded-lg">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6.253v13m0-13C10.832 5.477 9.246 5 7.5 5S4.168 5.477 3 6.253v13C4.168 18.477 5.754 18 7.5 18s3.332.477 4.5 1.253m0-13C13.168 5.477 14.754 5 16.5 5c1.746 0 3.332.477 4.5 1.253v13C19.832 18.477 18.246 18 16.5 18c-1.746 0-3.332.477-4.5 1.253"></path>
                            </svg>
                            <span class="text-sm font-medium">نوع الحلقة: {{ $circle->circle_type_text }}</span>
                        </div>
                        @endif
                    </div>
                </div>

                <!-- Circle Details -->
                @if($circle->description_ar || $circle->description_en || $circle->description || $circle->learning_objectives || $circle->requirements)
                <div class="bg-white rounded-xl shadow-sm p-6 mb-8">
                    <h2 class="text-xl font-bold text-gray-900 mb-6">وصف الحلقة</h2>
                    
                    <div class="space-y-8">
                        <!-- Description -->
                        @if($circle->description_ar || $circle->description_en)
                        <div>
                            <h3 class="text-lg font-semibold text-gray-800 mb-3 flex items-center gap-2">
                                <div class="w-2 h-2 bg-blue-500 rounded-full"></div>
                                نبذة عن الحلقة
                            </h3>
                            <div class="p-2">
                                <p class="text-gray-700 leading-relaxed">{{ $circle->description_ar ?? $circle->description_en ?? $circle->description }}</p>
                            </div>
                        </div>
                        @endif

                        <!-- Learning Objectives -->
                        @if($circle->learning_objectives)
                        <div>
                            <h3 class="text-lg font-semibold text-gray-800 mb-3 flex items-center gap-2">
                                <div class="w-2 h-2 bg-green-500 rounded-full"></div>
                                أهداف التعلم
                            </h3>
                            <div class="p-2">
                                @if(is_array($circle->learning_objectives))
                                <div class="space-y-3">
                                    @foreach($circle->learning_objectives as $objective)
                                    <div class="flex items-start gap-3">
                                        <div class="w-6 h-6 bg-green-500 rounded-full flex items-center justify-center mt-0.5 flex-shrink-0">
                                            <svg class="w-4 h-4 text-white" fill="currentColor" viewBox="0 0 20 20">
                                                <path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd"></path>
                                            </svg>
                                        </div>
                                        <span class="text-gray-700 leading-relaxed">{{ $objective }}</span>
                                    </div>
                                    @endforeach
                                </div>
                                @else
                                <p class="text-gray-700 leading-relaxed">{{ $circle->learning_objectives }}</p>
                                @endif
                            </div>
                        </div>
                        @endif

                        <!-- Requirements -->
                        @if($circle->requirements)
                        <div>
                            <h3 class="text-lg font-semibold text-gray-800 mb-3 flex items-center gap-2">
                                <div class="w-2 h-2 bg-orange-500 rounded-full"></div>
                                متطلبات الانضمام
                            </h3>
                            <div class="bg-orange-50 border-l-4 border-orange-400 p-4 rounded-r-lg">
                                @if(is_array($circle->requirements))
                                <div class="space-y-3">
                                    @foreach($circle->requirements as $requirement)
                                    <div class="flex items-start gap-3">
                                        <div class="w-6 h-6 bg-orange-500 rounded-full flex items-center justify-center mt-0.5 flex-shrink-0">
                                            <svg class="w-4 h-4 text-white" fill="currentColor" viewBox="0 0 20 20">
                                                <path fill-rule="evenodd" d="M8.257 3.099c.765-1.36 2.722-1.36 3.486 0l5.58 9.92c.75 1.334-.213 2.98-1.742 2.98H4.42c-1.53 0-2.493-1.646-1.743-2.98l5.58-9.92zM11 13a1 1 0 11-2 0 1 1 0 012 0zm-1-8a1 1 0 00-1 1v3a1 1 0 002 0V6a1 1 0 00-1-1z" clip-rule="evenodd"></path>
                                            </svg>
                                        </div>
                                        <span class="text-gray-700 leading-relaxed">{{ $requirement }}</span>
                                    </div>
                                    @endforeach
                                </div>
                                @else
                                <p class="text-gray-700 leading-relaxed">{{ $circle->requirements }}</p>
                                @endif
                            </div>
                        </div>
                        @endif
                    </div>
                </div>
                @endif

                <!-- Next Session & Schedule -->
                <div class="space-y-6">
                    <!-- Next Session -->
                    @if($isEnrolled)
                    <div class="bg-white rounded-xl shadow-sm p-6">
                        <h2 class="text-xl font-bold text-gray-900 mb-4 flex items-center gap-2">
                            <svg class="w-6 h-6 text-green-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                            </svg>
                            الجلسة القادمة
                        </h2>
                        
                        <!-- Next Session Date & Time -->
                        @if($circle->next_session_at)
                        <div class="mb-4 p-3 bg-blue-50 border border-blue-200 rounded-lg">
                            <div class="flex items-center gap-3">
                                <div class="w-10 h-10 bg-blue-100 rounded-full flex items-center justify-center">
                                    <svg class="w-5 h-5 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"></path>
                                    </svg>
                                </div>
                                <div>
                                    @php
                                        $nextSession = \Carbon\Carbon::parse($circle->next_session_at);
                                        $arabicDays = [
                                            'Sunday' => 'الأحد',
                                            'Monday' => 'الاثنين',
                                            'Tuesday' => 'الثلاثاء',
                                            'Wednesday' => 'الأربعاء',
                                            'Thursday' => 'الخميس',
                                            'Friday' => 'الجمعة',
                                            'Saturday' => 'السبت'
                                        ];
                                        $arabicMonths = [
                                            1 => 'يناير', 2 => 'فبراير', 3 => 'مارس', 4 => 'أبريل',
                                            5 => 'مايو', 6 => 'يونيو', 7 => 'يوليو', 8 => 'أغسطس',
                                            9 => 'سبتمبر', 10 => 'أكتوبر', 11 => 'نوفمبر', 12 => 'ديسمبر'
                                        ];
                                        
                                        $dayName = $arabicDays[$nextSession->format('l')];
                                        $monthName = $arabicMonths[$nextSession->month];
                                        $day = $nextSession->day;
                                        $year = $nextSession->year;
                                        
                                        // Format time in Arabic
                                        $hour = $nextSession->hour;
                                        $minute = $nextSession->minute;
                                        $arabicHours = [
                                            1 => 'الواحدة', 2 => 'الثانية', 3 => 'الثالثة', 4 => 'الرابعة', 
                                            5 => 'الخامسة', 6 => 'السادسة', 7 => 'السابعة', 8 => 'الثامنة',
                                            9 => 'التاسعة', 10 => 'العاشرة', 11 => 'الحادية عشرة', 12 => 'الثانية عشرة',
                                            13 => 'الواحدة', 14 => 'الثانية', 15 => 'الثالثة', 16 => 'الرابعة',
                                            17 => 'الخامسة', 18 => 'السادسة', 19 => 'السابعة', 20 => 'الثامنة',
                                            21 => 'التاسعة', 22 => 'العاشرة', 23 => 'الحادية عشرة', 0 => 'الثانية عشرة'
                                        ];
                                        
                                        $hourDisplay = $hour == 0 ? 12 : ($hour > 12 ? $hour - 12 : $hour);
                                        $hourName = $arabicHours[$hour] ?? $hourDisplay;
                                        $periodName = $hour >= 12 ? 'مساءً' : 'صباحاً';
                                        $timeDisplay = "الساعة {$hourName} {$periodName}";
                                        
                                        if ($minute > 0) {
                                            $timeDisplay .= " و{$minute} دقيقة";
                                        }
                                    @endphp
                                    <h3 class="font-semibold text-blue-800">{{ $dayName }}، {{ $day }} {{ $monthName }} {{ $year }}</h3>
                                    <p class="text-sm text-blue-600">{{ $timeDisplay }}</p>
                                </div>
                            </div>
                        </div>
                        @else
                        <div class="mb-4 p-3 bg-gray-50 border border-gray-200 rounded-lg">
                            <div class="flex items-center gap-3">
                                <div class="w-10 h-10 bg-gray-100 rounded-full flex items-center justify-center">
                                    <svg class="w-5 h-5 text-gray-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"></path>
                                    </svg>
                                </div>
                                <div>
                                    <h3 class="font-medium text-gray-600">لم يتم تحديد موعد الجلسة القادمة</h3>
                                    <p class="text-sm text-gray-500">سيتم الإعلان عن موعد الجلسة قريباً</p>
                                </div>
                            </div>
                        </div>
                        @endif
                        
                        @if($circle->room_link)
                        <div class="bg-green-50 border border-green-200 rounded-lg p-4">
                            <div class="flex items-center justify-between">
                                <div>
                                    <h3 class="font-medium text-green-800 mb-1">رابط الجلسة متاح</h3>
                                    <p class="text-sm text-green-600">يمكنك الدخول للجلسة من خلال الرابط أدناه</p>
                                </div>
                                <svg class="w-8 h-8 text-green-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5.636 18.364a9 9 0 010-12.728m12.728 0a9 9 0 010 12.728m-9.9-2.829a5 5 0 010-7.07m7.072 0a5 5 0 010 7.07M13 12a1 1 0 11-2 0 1 1 0 012 0z"></path>
                                </svg>
                            </div>
                            <a href="{{ $circle->room_link }}" target="_blank"
                               class="mt-3 w-full bg-green-600 text-white py-3 px-4 rounded-lg font-medium hover:bg-green-700 transition-colors text-center block">
                                دخول الجلسة الآن
                            </a>
                        </div>
                        @else
                        <div class="bg-yellow-50 border border-yellow-200 rounded-lg p-4">
                            <div class="flex items-center gap-3">
                                <svg class="w-8 h-8 text-yellow-600 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-2.5L13.732 4c-.77-.833-1.96-.833-2.73 0L5.084 15.5c-.77.833.192 2.5 1.732 2.5z"></path>
                                </svg>
                                <div>
                                    <h3 class="font-medium text-yellow-800 mb-1">رابط الجلسة غير متاح حالياً</h3>
                                    <p class="text-sm text-yellow-600">سيتم إرسال رابط الجلسة قريباً</p>
                                </div>
                            </div>
                        </div>
                        @endif
                    </div>
                    @endif

                    <!-- Upcoming Sessions (for enrolled students) -->
                    @if($isEnrolled && $upcomingSessions->count() > 0)
                    <div class="bg-white rounded-xl shadow-sm p-6 mb-6">
                        <h2 class="text-xl font-bold text-gray-900 mb-6 flex items-center gap-2">
                            <svg class="w-6 h-6 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"></path>
                            </svg>
                            الجلسات القادمة
                        </h2>
                        
                        <div class="space-y-4">
                            @foreach($upcomingSessions as $session)
                            @php
                                $sessionDate = \Carbon\Carbon::parse($session->scheduled_at);
                                $arabicDays = [
                                    'Sunday' => 'الأحد', 'Monday' => 'الاثنين', 'Tuesday' => 'الثلاثاء',
                                    'Wednesday' => 'الأربعاء', 'Thursday' => 'الخميس', 'Friday' => 'الجمعة', 'Saturday' => 'السبت'
                                ];
                                $arabicMonths = [
                                    1 => 'يناير', 2 => 'فبراير', 3 => 'مارس', 4 => 'أبريل', 5 => 'مايو', 6 => 'يونيو',
                                    7 => 'يوليو', 8 => 'أغسطس', 9 => 'سبتمبر', 10 => 'أكتوبر', 11 => 'نوفمبر', 12 => 'ديسمبر'
                                ];
                                $dayName = $arabicDays[$sessionDate->format('l')];
                                $monthName = $arabicMonths[$sessionDate->month];
                                
                                $isToday = $sessionDate->isToday();
                                $isTomorrow = $sessionDate->isTomorrow();
                                $timeUntil = now()->diffInMinutes($sessionDate);
                                $isStartingSoon = $timeUntil <= 30 && $timeUntil > 0;
                                $hasMeetingRoom = !empty($session->meeting_room_name);
                            @endphp
                            
                            <div class="border rounded-lg p-4 {{ $isToday ? 'border-green-300 bg-green-50' : 'border-gray-200 bg-gray-50' }} cursor-pointer hover:shadow-md transition-shadow" 
                                 onclick="openSessionDetail({{ $session->id }})">
                                <div class="flex items-center justify-between mb-3">
                                    <div class="flex items-center gap-3">
                                        <div class="w-12 h-12 {{ $isToday ? 'bg-green-100' : 'bg-blue-100' }} rounded-full flex items-center justify-center">
                                            <svg class="w-6 h-6 {{ $isToday ? 'text-green-600' : 'text-blue-600' }}" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                                            </svg>
                                        </div>
                                        <div>
                                            <h3 class="font-semibold {{ $isToday ? 'text-green-800' : 'text-gray-900' }}">
                                                @if($isToday) اليوم
                                                @elseif($isTomorrow) غداً
                                                @else {{ $dayName }}
                                                @endif
                                                - {{ $sessionDate->format('d') }} {{ $monthName }}
                                            </h3>
                                            <p class="text-sm {{ $isToday ? 'text-green-600' : 'text-gray-600' }}">
                                                {{ $sessionDate->format('h:i A') }}
                                                @if($session->duration_minutes)
                                                    - {{ $session->duration_minutes }} دقيقة
                                                @endif
                                            </p>
                                        </div>
                                    </div>
                                    
                                    <!-- Session Status -->
                                    <div class="flex items-center gap-2">
                                        @if($hasMeetingRoom)
                                            @if($isStartingSoon)
                                                <span class="px-3 py-1 bg-yellow-100 text-yellow-800 rounded-full text-xs font-medium animate-pulse">
                                                    تبدأ خلال {{ $timeUntil }} دقيقة
                                                </span>
                                            @else
                                                <span class="px-3 py-1 bg-green-100 text-green-800 rounded-full text-xs font-medium">
                                                    جاهزة للانضمام
                                                </span>
                                            @endif
                                        @else
                                            <span class="px-3 py-1 bg-gray-100 text-gray-600 rounded-full text-xs font-medium">
                                                في الانتظار
                                            </span>
                                        @endif
                                    </div>
                                </div>
                                
                                <!-- Session Details -->
                                @if($session->title || $session->description)
                                <div class="mb-3">
                                    @if($session->title)
                                        <h4 class="font-medium text-gray-900 mb-1">{{ $session->title }}</h4>
                                    @endif
                                    @if($session->description)
                                        <p class="text-sm text-gray-600">{{ $session->description }}</p>
                                    @endif
                                </div>
                                @endif
                                
                                <!-- Join Meeting Button -->
                                @if($hasMeetingRoom)
                                    <div class="flex justify-end">
                                        <a href="{{ route('meetings.join', ['session' => $session->id]) }}" 
                                           target="_blank"
                                           onclick="event.stopPropagation()"
                                           class="inline-flex items-center gap-2 bg-green-600 text-white px-4 py-2 rounded-lg text-sm font-medium hover:bg-green-700 transition-colors">
                                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 10l4.553-2.276A1 1 0 0121 8.618v6.764a1 1 0 01-1.447.894L15 14M5 18h8a2 2 0 002-2V8a2 2 0 00-2-2H5a2 2 0 00-2 2v8a2 2 0 002 2z"></path>
                                            </svg>
                                            دخول الجلسة
                                        </a>
                                    </div>
                                @else
                                    <div class="flex justify-end">
                                        <span class="inline-flex items-center gap-2 bg-gray-100 text-gray-500 px-4 py-2 rounded-lg text-sm">
                                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                                            </svg>
                                            سيتوفر الرابط قريباً
                                        </span>
                                    </div>
                                @endif
                            </div>
                            @endforeach
                            
                            @if($upcomingSessions->count() >= 10)
                            <div class="text-center pt-4">
                                <span class="text-sm text-gray-500">عرض أول 10 جلسات قادمة</span>
                            </div>
                            @endif
                        </div>
                    </div>
                    @endif

                    <!-- Recent Sessions (for enrolled students) -->
                    @if($isEnrolled && $pastSessions->count() > 0)
                    <div class="bg-white rounded-xl shadow-sm p-6 mb-6">
                        <h2 class="text-xl font-bold text-gray-900 mb-6 flex items-center gap-2">
                            <svg class="w-6 h-6 text-gray-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                            </svg>
                            الجلسات السابقة
                        </h2>
                        
                        <div class="space-y-3">
                            @foreach($pastSessions as $session)
                            @php
                                $sessionDate = \Carbon\Carbon::parse($session->scheduled_at);
                                $arabicDays = [
                                    'Sunday' => 'الأحد', 'Monday' => 'الاثنين', 'Tuesday' => 'الثلاثاء',
                                    'Wednesday' => 'الأربعاء', 'Thursday' => 'الخميس', 'Friday' => 'الجمعة', 'Saturday' => 'السبت'
                                ];
                                $arabicMonths = [
                                    1 => 'يناير', 2 => 'فبراير', 3 => 'مارس', 4 => 'أبريل', 5 => 'مايو', 6 => 'يونيو',
                                    7 => 'يوليو', 8 => 'أغسطس', 9 => 'سبتمبر', 10 => 'أكتوبر', 11 => 'نوفمبر', 12 => 'ديسمبر'
                                ];
                                $dayName = $arabicDays[$sessionDate->format('l')];
                                $monthName = $arabicMonths[$sessionDate->month];
                            @endphp
                            
                            <div class="border border-gray-200 rounded-lg p-3 bg-gray-50 cursor-pointer hover:shadow-md transition-shadow" 
                                 onclick="openSessionDetail({{ $session->id }})">
                                <div class="flex items-center justify-between">
                                    <div class="flex items-center gap-3">
                                        <div class="w-8 h-8 bg-gray-100 rounded-full flex items-center justify-center">
                                            <svg class="w-4 h-4 text-gray-600" fill="currentColor" viewBox="0 0 20 20">
                                                <path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd"></path>
                                            </svg>
                                        </div>
                                        <div>
                                            <h4 class="font-medium text-gray-900 text-sm">
                                                {{ $dayName }} - {{ $sessionDate->format('d') }} {{ $monthName }}
                                            </h4>
                                            <p class="text-xs text-gray-500">{{ $sessionDate->format('h:i A') }}</p>
                                        </div>
                                    </div>
                                    
                                    <div class="flex items-center gap-2">
                                        <span class="px-2 py-1 bg-green-100 text-green-700 rounded text-xs font-medium">
                                            مكتملة
                                        </span>
                                        
                                        @if($session->recording_url)
                                        <a href="{{ $session->recording_url }}" target="_blank"
                                           onclick="event.stopPropagation()"
                                           class="text-blue-600 hover:text-blue-800 text-xs">
                                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M14.828 14.828a4 4 0 01-5.656 0M9 10h1m4 0h1m-6 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                                            </svg>
                                        </a>
                                        @endif
                                    </div>
                                </div>
                            </div>
                            @endforeach
                        </div>
                    </div>
                    @endif

                    <!-- Schedule Information -->
                    @if($circle->session_duration_minutes || $circle->schedule_days || $circle->schedule_time)
                    <div class="bg-white rounded-xl shadow-sm p-6">
                        <h2 class="text-xl font-bold text-gray-900 mb-4">مواعيد وتفاصيل الجلسات</h2>
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                            <!-- Session Duration -->
                            @if($circle->session_duration_minutes)
                            <div>
                                <h3 class="font-medium text-gray-900 mb-2">مدة الجلسة</h3>
                                <div class="flex items-center gap-2">
                                    <svg class="w-5 h-5 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                                    </svg>
                                    <span class="px-3 py-1 bg-blue-100 text-blue-800 rounded-lg text-sm font-medium">
                                        {{ $circle->session_duration_minutes }} دقيقة
                                    </span>
                                </div>
                            </div>
                            @endif

                            <!-- Schedule Days -->
                            @if($circle->schedule_days)
                            <div>
                                <h3 class="font-medium text-gray-900 mb-2">أيام الجلسات</h3>
                                <div class="flex flex-wrap gap-2">
                                    @if(is_array($circle->schedule_days))
                                    @foreach($circle->schedule_days as $day)
                                    @php
                                        $arabicDays = [
                                            'sunday' => 'الأحد',
                                            'monday' => 'الاثنين', 
                                            'tuesday' => 'الثلاثاء',
                                            'wednesday' => 'الأربعاء',
                                            'thursday' => 'الخميس',
                                            'friday' => 'الجمعة',
                                            'saturday' => 'السبت'
                                        ];
                                        $arabicDay = $arabicDays[strtolower($day)] ?? $day;
                                    @endphp
                                    <span class="px-3 py-1 bg-purple-100 text-purple-800 rounded-full text-sm font-medium">
                                        {{ $arabicDay }}
                                    </span>
                                    @endforeach
                                    @else
                                    @php
                                        $arabicDays = [
                                            'sunday' => 'الأحد',
                                            'monday' => 'الاثنين', 
                                            'tuesday' => 'الثلاثاء',
                                            'wednesday' => 'الأربعاء',
                                            'thursday' => 'الخميس',
                                            'friday' => 'الجمعة',
                                            'saturday' => 'السبت'
                                        ];
                                        $arabicDay = $arabicDays[strtolower($circle->schedule_days)] ?? $circle->schedule_days;
                                    @endphp
                                    <span class="px-3 py-1 bg-purple-100 text-purple-800 rounded-full text-sm font-medium">
                                        {{ $arabicDay }}
                                    </span>
                                    @endif
                                </div>
                            </div>
                            @endif
                            
                            <!-- Schedule Time -->
                            @if($circle->schedule_time)
                            <div class="md:col-span-2">
                                <h3 class="font-medium text-gray-900 mb-2">وقت الجلسة</h3>
                                <div class="flex items-center gap-2">
                                    <svg class="w-5 h-5 text-green-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                                    </svg>
                                    @php
                                        // Convert time to readable Arabic format
                                        $time = $circle->schedule_time;
                                        $timeReadable = $time;
                                        
                                        // Try to parse common time formats
                                        if (preg_match('/(\d{1,2}):(\d{2})\s*(AM|PM|am|pm)?/', $time, $matches)) {
                                            $hour = (int)$matches[1];
                                            $minute = $matches[2];
                                            $period = strtoupper($matches[3] ?? '');
                                            
                                            if ($period === 'PM' && $hour !== 12) {
                                                $hour += 12;
                                            } elseif ($period === 'AM' && $hour === 12) {
                                                $hour = 0;
                                            }
                                            
                                            $arabicHours = [
                                                1 => 'الواحدة', 2 => 'الثانية', 3 => 'الثالثة', 4 => 'الرابعة', 
                                                5 => 'الخامسة', 6 => 'السادسة', 7 => 'السابعة', 8 => 'الثامنة',
                                                9 => 'التاسعة', 10 => 'العاشرة', 11 => 'الحادية عشرة', 12 => 'الثانية عشرة',
                                                13 => 'الواحدة', 14 => 'الثانية', 15 => 'الثالثة', 16 => 'الرابعة',
                                                17 => 'الخامسة', 18 => 'السادسة', 19 => 'السابعة', 20 => 'الثامنة',
                                                21 => 'التاسعة', 22 => 'العاشرة', 23 => 'الحادية عشرة', 0 => 'الثانية عشرة'
                                            ];
                                            
                                            $hourName = $arabicHours[$hour] ?? $hour;
                                            $periodName = $hour >= 12 ? 'مساءً' : 'صباحاً';
                                            $timeReadable = "الساعة {$hourName} {$periodName}";
                                            
                                            if ($minute !== '00') {
                                                $timeReadable .= " و{$minute} دقيقة";
                                            }
                                        }
                                    @endphp
                                    <span class="px-3 py-1 bg-green-100 text-green-800 rounded-lg text-sm font-medium">
                                        {{ $timeReadable }}
                                    </span>
                                </div>
                            </div>
                            @endif
                        </div>
                    </div>
                    @endif
                </div>
            </div>

            <!-- Sidebar -->
            <div class="lg:col-span-1">
                <!-- Circle Info Sidebar -->
                <x-circle.info-sidebar :circle="$circle" view-type="student" />

                <!-- Enrollment Card -->
                <div class="bg-white rounded-xl shadow-sm p-6 mb-6 sticky top-4">
                    @if($isEnrolled)
                    <!-- Already Enrolled -->
                    <div class="text-center mb-6">
                        <div class="w-16 h-16 bg-green-100 rounded-full flex items-center justify-center mx-auto mb-4">
                            <svg class="w-8 h-8 text-green-600" fill="currentColor" viewBox="0 0 20 20">
                                <path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd"></path>
                            </svg>
                        </div>
                        <h3 class="text-lg font-bold text-green-600 mb-2">مسجل في الحلقة</h3>
                        <p class="text-sm text-gray-600">أنت مسجل في هذه الحلقة ويمكنك حضور الجلسات</p>
                    </div>
                    
                    @if($circle->room_link)
                    <a href="{{ $circle->room_link }}" target="_blank"
                       class="w-full bg-green-500 text-white py-3 px-4 rounded-lg font-medium hover:bg-green-600 transition-colors text-center block mb-4">
                        دخول الجلسة
                    </a>
                    @endif
                    
                    @elseif($canEnroll)
                    <!-- Can Enroll -->
                    <div class="text-center mb-6">
                        @if($circle->monthly_fee && $circle->monthly_fee > 0)
                        <div class="text-3xl font-bold text-gray-900 mb-2">
                            {{ number_format($circle->monthly_fee) }} {{ $circle->currency ?? 'ريال' }}
                        </div>
                        <div class="text-sm text-gray-600 mb-4">رسوم شهرية</div>
                        @else
                        <div class="text-3xl font-bold text-green-600 mb-2">مجاني</div>
                        <div class="text-sm text-gray-600 mb-4">بدون رسوم</div>
                        @endif
                    </div>
                    
                    <div class="text-center">
                        <p class="text-sm text-gray-600">{{ $circle->max_students - $circle->enrolled_students }} مقعد متبقي</p>
                    </div>
                    
                    @else
                    <!-- Cannot Enroll -->
                    <div class="text-center mb-6">
                        <div class="w-16 h-16 bg-gray-100 rounded-full flex items-center justify-center mx-auto mb-4">
                            <svg class="w-8 h-8 text-gray-400" fill="currentColor" viewBox="0 0 20 20">
                                <path fill-rule="evenodd" d="M5 9V7a5 5 0 0110 0v2a2 2 0 012 2v5a2 2 0 01-2 2H5a2 2 0 01-2-2v-5a2 2 0 012-2zm8-2v2H7V7a3 3 0 016 0z" clip-rule="evenodd"></path>
                            </svg>
                        </div>
                        <h3 class="text-lg font-bold text-gray-600 mb-2">غير متاح للتسجيل</h3>
                        <p class="text-sm text-gray-600">
                            @if($circle->enrollment_status === 'full')
                                هذه الحلقة مكتملة العدد
                            @elseif($circle->enrollment_status === 'closed')
                                التسجيل مغلق حالياً
                            @else
                                الحلقة غير نشطة
                            @endif
                        </p>
                    </div>
                    @endif
                </div>

                <!-- Quick Actions -->
                <x-circle.quick-actions :circle="$circle" view-type="student" :isEnrolled="$isEnrolled" :canEnroll="$canEnroll" />

                <!-- Circle Features -->
                <div class="bg-white rounded-xl shadow-sm p-6">
                    <h3 class="font-bold text-gray-900 mb-4">مميزات الحلقة</h3>
                    <div class="space-y-3">
                        <div class="flex items-center gap-3">
                            <svg class="w-5 h-5 text-green-500" fill="currentColor" viewBox="0 0 20 20">
                                <path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd"></path>
                            </svg>
                            <span class="text-sm text-gray-700">تعلم جماعي تفاعلي</span>
                        </div>
                        <div class="flex items-center gap-3">
                            <svg class="w-5 h-5 text-green-500" fill="currentColor" viewBox="0 0 20 20">
                                <path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd"></path>
                            </svg>
                            <span class="text-sm text-gray-700">معلم مؤهل ومعتمد</span>
                        </div>
                        <div class="flex items-center gap-3">
                            <svg class="w-5 h-5 text-green-500" fill="currentColor" viewBox="0 0 20 20">
                                <path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd"></path>
                            </svg>
                            <span class="text-sm text-gray-700">متابعة مستمرة للتقدم</span>
                        </div>
                        @if($circle->recording_enabled)
                        <div class="flex items-center gap-3">
                            <svg class="w-5 h-5 text-green-500" fill="currentColor" viewBox="0 0 20 20">
                                <path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd"></path>
                            </svg>
                            <span class="text-sm text-gray-700">تسجيل الجلسات للمراجعة</span>
                        </div>
                        @endif
                        @if($circle->certificates_enabled)
                        <div class="flex items-center gap-3">
                            <svg class="w-5 h-5 text-green-500" fill="currentColor" viewBox="0 0 20 20">
                                <path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd"></path>
                            </svg>
                            <span class="text-sm text-gray-700">شهادة إتمام</span>
                        </div>
                        @endif
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<x-slot name="scripts">
<script>
function openSessionDetail(sessionId) {
    @if(auth()->check())
        // Use Laravel route helper to generate correct URL for student sessions
        const sessionUrl = '{{ route("student.sessions.show", ["subdomain" => auth()->user()->academy->subdomain ?? "itqan-academy", "sessionId" => "SESSION_ID_PLACEHOLDER"]) }}';
        const finalUrl = sessionUrl.replace('SESSION_ID_PLACEHOLDER', sessionId);
        
        console.log('Student Session URL:', finalUrl);
        window.location.href = finalUrl;
    @else
        console.error('User not authenticated');
    @endif
}

function showEnrollModal(circleId) {
    showConfirmModal({
        title: 'انضمام للحلقة',
        message: 'هل أنت متأكد من الانضمام لهذه الحلقة؟ سيتم تفعيل اشتراكك فوراً.',
        type: 'success',
        confirmText: 'انضم الآن',
        cancelText: 'إلغاء',
        onConfirm: () => enrollInCircle(circleId)
    });
}

function showLeaveModal(circleId) {
    showConfirmModal({
        title: 'إلغاء التسجيل',
        message: 'هل أنت متأكد من إلغاء التسجيل من هذه الحلقة؟ ستفقد إمكانية الوصول لجميع المواد.',
        type: 'danger',
        confirmText: 'إلغاء التسجيل',
        cancelText: 'البقاء في الحلقة',
        onConfirm: () => leaveCircle(circleId)
    });
}

function enrollInCircle(circleId) {
    fetch(`{{ route('student.circles.enroll', ['subdomain' => auth()->user()->academy->subdomain ?? 'itqan-academy', 'circleId' => '__CIRCLE_ID__']) }}`.replace('__CIRCLE_ID__', circleId), {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
        }
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            showConfirmModal({
                title: 'تم بنجاح!',
                message: data.message || 'تم تسجيلك في الحلقة بنجاح',
                type: 'success',
                confirmText: 'موافق',
                onConfirm: () => {
                    if (data.redirect_url) {
                        window.location.href = data.redirect_url;
                    } else {
                        location.reload();
                    }
                }
            });
        } else {
            showConfirmModal({
                title: 'خطأ في التسجيل',
                message: data.error || 'حدث خطأ أثناء التسجيل',
                type: 'danger',
                confirmText: 'موافق'
            });
        }
    })
    .catch(error => {
        console.error('Error:', error);
        showConfirmModal({
            title: 'خطأ في الاتصال',
            message: 'حدث خطأ أثناء التسجيل. يرجى المحاولة مرة أخرى',
            type: 'danger',
            confirmText: 'موافق'
        });
    });
}

function leaveCircle(circleId) {
    fetch(`{{ route('student.circles.leave', ['subdomain' => auth()->user()->academy->subdomain ?? 'itqan-academy', 'circleId' => '__CIRCLE_ID__']) }}`.replace('__CIRCLE_ID__', circleId), {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
        }
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            showConfirmModal({
                title: 'تم بنجاح!',
                message: data.message || 'تم إلغاء تسجيلك من الحلقة بنجاح',
                type: 'success',
                confirmText: 'موافق',
                onConfirm: () => {
                    if (data.redirect_url) {
                        window.location.href = data.redirect_url;
                    } else {
                        location.reload();
                    }
                }
            });
        } else {
            showConfirmModal({
                title: 'خطأ',
                message: data.error || 'حدث خطأ أثناء إلغاء التسجيل',
                type: 'danger',
                confirmText: 'موافق'
            });
        }
    })
    .catch(error => {
        console.error('Error:', error);
        showConfirmModal({
            title: 'خطأ في الاتصال',
            message: 'حدث خطأ أثناء إلغاء التسجيل. يرجى المحاولة مرة أخرى',
            type: 'danger',
            confirmText: 'موافق'
        });
    });
}
</script>
</x-slot>

</x-layouts.student>