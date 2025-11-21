<x-layouts.student
    :title="$course->title . ' - ' . config('app.name', 'منصة إتقان')"
    :description="$course->description">

<div class="max-w-7xl mx-auto">
    <!-- Breadcrumb -->
    <nav class="mb-6">
        <ol class="flex items-center space-x-2 space-x-reverse text-sm text-gray-500">
            <li><a href="{{ route('student.interactive-courses', ['subdomain' => auth()->user()->academy->subdomain ?? 'itqan-academy']) }}" class="hover:text-blue-600 transition-colors">الكورسات التفاعلية</a></li>
            <li><i class="ri-arrow-left-s-line"></i></li>
            <li class="text-gray-900 font-medium">{{ $course->title }}</li>
        </ol>
    </nav>

    @php
        $now = now();
        $isEnrollmentClosed = $course->enrollment_deadline < $now->toDateString() || $enrollmentStats['available_spots'] <= 0;
        $isOngoing = $course->start_date <= $now->toDateString() && $course->end_date >= $now->toDateString();
        $isFinished = $course->end_date < $now->toDateString();
        $isUpcoming = $course->start_date > $now->toDateString();

        if ($isFinished) {
            $statusLabel = 'انتهى';
            $statusBg = 'bg-gray-100';
            $statusText = 'text-gray-700';
            $statusIcon = 'ri-checkbox-circle-line';
        } elseif ($isOngoing) {
            $statusLabel = 'جاري الآن';
            $statusBg = 'bg-green-100';
            $statusText = 'text-green-700';
            $statusIcon = 'ri-play-circle-fill';
        } elseif ($isEnrollmentClosed) {
            $statusLabel = 'التسجيل مغلق';
            $statusBg = 'bg-red-100';
            $statusText = 'text-red-700';
            $statusIcon = 'ri-close-circle-line';
        } else {
            $statusLabel = 'متاح للتسجيل';
            $statusBg = 'bg-green-100';
            $statusText = 'text-green-700';
            $statusIcon = 'ri-check-circle-fill';
        }
    @endphp

    <!-- Hero Section -->
    <div class="bg-gradient-to-br from-blue-50 to-white rounded-2xl p-8 md:p-10 mb-8 border border-blue-100">
        <!-- Status Badge -->
        <div class="inline-flex items-center gap-2 px-3 py-1.5 rounded-full {{ $statusBg }} {{ $statusText }} text-sm font-medium mb-4">
            <i class="{{ $statusIcon }}"></i>
            <span>{{ $statusLabel }}</span>
        </div>

        <!-- Title -->
        <h1 class="text-3xl md:text-4xl font-bold text-gray-900 mb-4 leading-tight">{{ $course->title }}</h1>

        <!-- Description -->
        @if($course->description)
        <p class="text-lg text-gray-600 leading-relaxed mb-6">{{ $course->description }}</p>
        @endif

        <!-- Quick Info Pills -->
        <div class="flex flex-wrap gap-3">
            @if($course->subject)
            <div class="inline-flex items-center gap-2 px-4 py-2 bg-white rounded-full border border-gray-200 shadow-sm">
                <i class="ri-bookmark-line text-blue-500"></i>
                <span class="text-sm font-medium text-gray-700">{{ $course->subject->name }}</span>
            </div>
            @endif

            @if($course->gradeLevel)
            <div class="inline-flex items-center gap-2 px-4 py-2 bg-white rounded-full border border-gray-200 shadow-sm">
                <i class="ri-graduation-cap-line text-blue-500"></i>
                <span class="text-sm font-medium text-gray-700">{{ $course->gradeLevel->name }}</span>
            </div>
            @endif

            @if($course->difficulty_level)
            <div class="inline-flex items-center gap-2 px-4 py-2 bg-white rounded-full border border-gray-200 shadow-sm">
                <i class="ri-bar-chart-line text-blue-500"></i>
                <span class="text-sm font-medium text-gray-700">
                    @if($course->difficulty_level === 'beginner') مبتدئ
                    @elseif($course->difficulty_level === 'intermediate') متوسط
                    @elseif($course->difficulty_level === 'advanced') متقدم
                    @else {{ $course->difficulty_level }}
                    @endif
                </span>
            </div>
            @endif
        </div>
    </div>

    <!-- Main Content Grid -->
    <div class="grid grid-cols-1 lg:grid-cols-3 gap-8">
        <!-- Main Content (Left Column - 2/3) -->
        <div class="lg:col-span-2 space-y-8">

            <!-- About Course Section (Teacher, Learning Outcomes, Prerequisites) -->
            <div class="bg-white rounded-2xl p-8 shadow-sm border border-gray-200">
                <!-- Teacher Information -->
                @if($course->assignedTeacher)
                <div class="mb-10">
                    <h2 class="text-lg font-bold text-gray-900 mb-6 flex items-center gap-2">
                        <i class="ri-user-star-line text-blue-500"></i>
                        المدرس
                    </h2>
                    <div class="flex flex-col md:flex-row items-start gap-6">
                        <!-- Teacher Avatar -->
                        <img
                            src="{{ $course->assignedTeacher->user->profile_image ?? 'https://ui-avatars.com/api/?name=' . urlencode($course->assignedTeacher->full_name) . '&background=f97316&color=fff&size=200' }}"
                            alt="{{ $course->assignedTeacher->full_name }}"
                            class="w-28 h-28 rounded-full object-cover border-4 border-gray-100 shadow-sm">

                        <!-- Teacher Info -->
                        <div class="flex-1">
                            <!-- Name with Rating -->
                            <div class="flex items-center justify-between mb-2">
                                <h3 class="text-xl font-bold text-gray-900">{{ $course->assignedTeacher->full_name }}</h3>
                                @if($course->assignedTeacher->rating)
                                <div class="inline-flex items-center gap-1">
                                    <i class="ri-star-fill text-yellow-500"></i>
                                    <span class="text-sm font-bold text-gray-900">{{ number_format($course->assignedTeacher->rating, 1) }}</span>
                                </div>
                                @endif
                            </div>


                            <!-- Degree and Languages - Horizontal Layout -->
                            <div class="flex flex-wrap items-center gap-4 mb-4">
                                @if($course->assignedTeacher->educational_qualification)
                                <div class="flex items-center gap-2 text-sm">
                                    <i class="ri-graduation-cap-line text-blue-500"></i>
                                    <span class="text-gray-900 font-medium">{{ $course->assignedTeacher->educational_qualification }}</span>
                                </div>
                                @endif

                                @if($course->assignedTeacher->education_level)
                                @php
                                    $educationLevelArabic = match($course->assignedTeacher->education_level) {
                                        'diploma' => 'دبلوم',
                                        'bachelor' => 'بكالوريوس',
                                        'master' => 'ماجستير',
                                        'phd' => 'دكتوراه',
                                        default => $course->assignedTeacher->education_level
                                    };
                                @endphp
                                <div class="flex items-center gap-2 text-sm">
                                    <i class="ri-book-line text-blue-500"></i>
                                    <span class="text-gray-900 font-medium">{{ $educationLevelArabic }}</span>
                                </div>
                                @endif

                                @if($course->assignedTeacher->languages && count($course->assignedTeacher->languages) > 0)
                                @php
                                    $languageMap = [
                                        'Arabic' => 'العربية',
                                        'English' => 'الإنجليزية',
                                        'French' => 'الفرنسية',
                                        'Spanish' => 'الإسبانية',
                                        'German' => 'الألمانية',
                                        'Turkish' => 'التركية',
                                        'Urdu' => 'الأردية',
                                    ];
                                    $languagesInArabic = array_map(function($lang) use ($languageMap) {
                                        return $languageMap[$lang] ?? $lang;
                                    }, $course->assignedTeacher->languages);
                                @endphp
                                <div class="flex items-center gap-2 text-sm">
                                    <i class="ri-global-line text-blue-500"></i>
                                    <span class="text-gray-900 font-medium">{{ implode(' • ', $languagesInArabic) }}</span>
                                </div>
                                @endif

                                @if($course->assignedTeacher->years_of_experience)
                                <div class="flex items-center gap-2 text-sm">
                                    <i class="ri-medal-line text-blue-500"></i>
                                    <span class="text-gray-900 font-medium">{{ $course->assignedTeacher->years_of_experience }} سنة خبرة</span>
                                </div>
                                @endif

                                @if($course->assignedTeacher->total_students)
                                <div class="flex items-center gap-2 text-sm">
                                    <i class="ri-group-line text-blue-500"></i>
                                    <span class="text-gray-900 font-medium">{{ $course->assignedTeacher->total_students }} طالب</span>
                                </div>
                                @endif
                            </div>

                            <!-- Bio -->
                            @if($course->assignedTeacher->bio_arabic)
                            <p class="text-gray-600 leading-relaxed mb-4">{{ $course->assignedTeacher->bio_arabic }}</p>
                            @endif

                            <!-- Certifications -->
                            @if($course->assignedTeacher->certifications && count($course->assignedTeacher->certifications) > 0)
                            <div class="mb-4">
                                <p class="text-xs text-gray-500 mb-2">الشهادات والدورات</p>
                                <div class="flex flex-wrap gap-2">
                                    @foreach($course->assignedTeacher->certifications as $cert)
                                    <span class="inline-flex items-center px-3 py-1 bg-blue-50 text-blue-700 rounded-full text-xs font-medium">
                                        <i class="ri-award-line ml-1"></i>
                                        {{ is_array($cert) ? $cert['name'] : $cert }}
                                    </span>
                                    @endforeach
                                </div>
                            </div>
                            @endif

                            <!-- Action Buttons - Floated to Left -->
                            <div class="flex items-center justify-end gap-3">
                                <a href="{{ route('public.academic-teachers.show', ['subdomain' => auth()->user()->academy->subdomain ?? 'itqan-academy', 'teacher' => $course->assignedTeacher->id]) }}"
                                   class="inline-flex items-center gap-2 px-5 py-2.5 bg-gray-100 hover:bg-gray-200 text-gray-900 rounded-lg font-medium transition-colors">
                                    <i class="ri-user-line"></i>
                                    <span>عرض الملف الشخصي</span>
                                </a>

                                @if($isEnrolled)
                                <a href="#"
                                   class="inline-flex items-center gap-2 px-5 py-2.5 bg-green-500 hover:bg-green-600 text-white rounded-lg font-medium transition-colors">
                                    <i class="ri-chat-3-line"></i>
                                    <span>تواصل مع المعلم</span>
                                </a>
                                @endif
                            </div>
                        </div>
                    </div>
                </div>
                @endif

                <!-- Divider -->
                @if($course->assignedTeacher && (($course->learning_outcomes && count($course->learning_outcomes) > 0) || ($course->prerequisites && count($course->prerequisites) > 0)))
                <div class="border-t border-gray-200 my-8"></div>
                @endif

                <!-- Learning Outcomes -->
                @if($course->learning_outcomes && count($course->learning_outcomes) > 0)
                <div class="mb-10">
                    <h2 class="text-lg font-bold text-gray-900 mb-6 flex items-center gap-2">
                        <i class="ri-lightbulb-flash-line text-green-600"></i>
                        ما ستتعلمه
                    </h2>
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        @foreach($course->learning_outcomes as $outcome)
                        <div class="flex items-start gap-3 p-3 rounded-lg">
                            <div class="w-6 h-6 bg-green-100 rounded-full flex items-center justify-center flex-shrink-0 mt-0.5">
                                <i class="ri-check-line text-green-600 text-sm"></i>
                            </div>
                            <span class="text-gray-700 leading-relaxed">{{ is_array($outcome) ? $outcome['outcome'] : $outcome }}</span>
                        </div>
                        @endforeach
                    </div>
                </div>
                @endif

                <!-- Divider -->
                @if($course->learning_outcomes && count($course->learning_outcomes) > 0 && $course->prerequisites && count($course->prerequisites) > 0)
                <div class="border-t border-gray-200 my-8"></div>
                @endif

                <!-- Prerequisites -->
                @if($course->prerequisites && count($course->prerequisites) > 0)
                <div class="mb-10">
                    <h2 class="text-lg font-bold text-gray-900 mb-6 flex items-center gap-2">
                        <i class="ri-file-list-3-line text-blue-600"></i>
                        المتطلبات الأساسية
                    </h2>
                    <div class="space-y-3">
                        @foreach($course->prerequisites as $prerequisite)
                        <div class="flex items-start gap-3 p-3 rounded-lg">
                            <div class="w-6 h-6 bg-blue-100 rounded-full flex items-center justify-center flex-shrink-0 mt-0.5">
                                <i class="ri-arrow-left-line text-blue-600 text-sm"></i>
                            </div>
                            <span class="text-gray-700 leading-relaxed">{{ is_array($prerequisite) ? $prerequisite['prerequisite'] : $prerequisite }}</span>
                        </div>
                        @endforeach
                    </div>
                </div>
                @endif

                <!-- Divider -->
                @if(($course->learning_outcomes && count($course->learning_outcomes) > 0) || ($course->prerequisites && count($course->prerequisites) > 0))
                @if($course->schedule && is_array($course->schedule) && count($course->schedule) > 0)
                <div class="border-t border-gray-200 my-8"></div>
                @endif
                @endif

                <!-- Course Schedule -->
                @if($course->schedule && is_array($course->schedule) && count($course->schedule) > 0)
                <div>
                    <h2 class="text-lg font-bold text-gray-900 mb-6 flex items-center gap-2">
                        <i class="ri-calendar-2-line text-purple-600"></i>
                        الجدول الأسبوعي
                    </h2>
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-3">
                        @foreach($course->schedule as $day => $time)
                        <div class="flex items-center justify-between p-4 bg-gradient-to-l from-purple-50 to-white rounded-xl border border-purple-100">
                            <span class="font-semibold text-gray-900">{{ $day }}</span>
                            <span class="text-purple-600 font-bold">{{ $time }}</span>
                        </div>
                        @endforeach
                    </div>
                </div>
                @endif
            </div>

            <!-- Sessions List (for enrolled students) -->
            @if($isEnrolled)
            <div class="bg-white rounded-2xl p-8 shadow-sm border border-gray-200">
                @php
                    $allCourseSessions = collect($upcomingSessions)->merge($pastSessions);
                @endphp
                <x-sessions.sessions-list
                    :sessions="$allCourseSessions"
                    title="جلسات الكورس"
                    view-type="student"
                    :show-tabs="false"
                    empty-message="لا توجد جلسات مجدولة بعد" />
            </div>
            @endif

        </div>

        <!-- Sidebar (Right Column - 1/3) -->
        <div class="space-y-6">

            <!-- Subscription & Price Card (Sticky) -->
            <div class="sticky top-6">
                <div class="bg-white rounded-2xl shadow-sm border border-gray-200 overflow-hidden mb-6">
                    <!-- Header with decorative pattern - Only show price for non-enrolled students -->
                    @if(!$isEnrolled)
                    <div class="bg-gradient-to-br from-blue-50 via-white to-blue-50 p-6 border-b border-blue-100">
                        @if($course->student_price)
                        <div class="text-center">
                            <p class="text-gray-600 text-sm mb-2 font-medium">سعر الكورس</p>
                            <div class="flex items-baseline justify-center gap-2 mb-1">
                                <span class="text-5xl font-black text-blue-600">{{ number_format($course->student_price) }}</span>
                                <span class="text-xl font-bold text-blue-500">ر.س</span>
                            </div>
                            @if($course->enrollment_fee && $course->is_enrollment_fee_required)
                            <p class="text-xs text-gray-500 mt-2">+ رسوم تسجيل: {{ number_format($course->enrollment_fee) }} ر.س</p>
                            @endif
                        </div>
                        @endif
                    </div>
                    @endif

                    <!-- Content -->
                    <div class="p-6">
                        <!-- Enrollment Status & Button -->
                        @if($isEnrolled)
                        <div class="bg-green-50 border-2 border-green-200 rounded-xl p-5 text-center mb-4">
                            <div class="w-16 h-16 bg-green-100 rounded-full flex items-center justify-center mx-auto mb-3">
                                <i class="ri-check-fill text-green-600 text-3xl"></i>
                            </div>
                            <p class="font-bold text-green-900 text-lg mb-1">مسجل بنجاح</p>
                            <p class="text-green-700 text-sm">أنت مسجل في هذا الكورس</p>
                        </div>
                        <button class="w-full bg-gray-100 text-gray-500 px-6 py-4 rounded-xl font-bold text-base cursor-not-allowed">
                            <i class="ri-check-line ml-2"></i>
                            مسجل بالفعل
                        </button>
                        @else
                            @if($course->is_published && $course->enrollment_deadline >= now()->toDateString() && $enrollmentStats['available_spots'] > 0)
                            <!-- Available Spots Badge -->
                            <div class="flex items-center justify-center gap-2 mb-4 p-3 bg-blue-50 rounded-lg border border-blue-100">
                                <i class="ri-group-line text-blue-600"></i>
                                <span class="text-sm font-bold text-blue-900">
                                    باقي {{ $enrollmentStats['available_spots'] }} {{ $enrollmentStats['available_spots'] == 1 ? 'مقعد' : 'مقاعد' }} فقط
                                </span>
                            </div>

                            <!-- CTA Button -->
                            <a href="{{ route('interactive-courses.enroll', ['subdomain' => auth()->user()->academy->subdomain ?? 'itqan-academy', 'course' => $course->id]) }}"
                               class="group block w-full bg-gradient-to-r from-blue-500 to-blue-600 hover:from-blue-600 hover:to-blue-700 text-white px-6 py-4 rounded-xl font-bold text-lg transition-all shadow-lg hover:shadow-xl text-center transform hover:-translate-y-1 relative overflow-hidden">
                                <span class="relative z-10 flex items-center justify-center gap-2">
                                    <i class="ri-shopping-cart-line text-xl"></i>
                                    <span>سجل الآن</span>
                                </span>
                                <!-- Shimmer effect -->
                                <div class="absolute inset-0 bg-gradient-to-r from-transparent via-white/20 to-transparent translate-x-[-200%] group-hover:translate-x-[200%] transition-transform duration-1000"></div>
                            </a>

                            <!-- Additional Info -->
                            <div class="grid grid-cols-2 gap-3 mt-4">
                                <div class="text-center p-3 bg-gray-50 rounded-lg">
                                    <i class="ri-calendar-line text-blue-500 text-lg mb-1"></i>
                                    <p class="text-xs text-gray-600">المدة</p>
                                    <p class="text-sm font-bold text-gray-900">{{ $course->duration_weeks }} أسبوع</p>
                                </div>
                                <div class="text-center p-3 bg-gray-50 rounded-lg">
                                    <i class="ri-time-line text-blue-500 text-lg mb-1"></i>
                                    <p class="text-xs text-gray-600">الجلسات</p>
                                    <p class="text-sm font-bold text-gray-900">{{ $course->total_sessions }} جلسة</p>
                                </div>
                            </div>
                            @else
                            <!-- Closed Registration -->
                            <div class="bg-gray-50 border-2 border-gray-200 rounded-xl p-5 text-center mb-4">
                                <div class="w-16 h-16 bg-gray-100 rounded-full flex items-center justify-center mx-auto mb-3">
                                    <i class="ri-close-circle-fill text-gray-400 text-3xl"></i>
                                </div>
                                <p class="font-bold text-gray-700 text-lg mb-1">التسجيل مغلق</p>
                                <p class="text-gray-600 text-sm">لا يمكن التسجيل في هذا الكورس حالياً</p>
                            </div>
                            <button class="w-full bg-gray-100 text-gray-500 px-6 py-4 rounded-xl font-bold text-base cursor-not-allowed">
                                <i class="ri-close-circle-line ml-2"></i>
                                غير متاح للتسجيل
                            </button>
                            @endif
                        @endif

                        <!-- Security Badge - Only show for non-enrolled students -->
                        @if(!$isEnrolled)
                        <div class="flex items-center justify-center gap-2 mt-4 pt-4 border-t border-gray-100">
                            <i class="ri-shield-check-line text-green-600"></i>
                            <span class="text-xs text-gray-600">دفع آمن ومضمون</span>
                        </div>
                        @endif
                    </div>
                </div>

                <!-- Course Information Widget (معلومات الدورة) -->
                <div class="bg-white rounded-2xl p-6 shadow-sm border border-gray-200 mb-6">
                    <h3 class="text-lg font-bold text-gray-900 mb-4 flex items-center gap-2">
                        <i class="ri-information-line text-blue-500"></i>
                        معلومات الدورة
                    </h3>
                    <div class="space-y-3">
                        <!-- Start Date -->
                        <div class="flex items-center gap-3 p-3 bg-gray-50 rounded-xl">
                            <div class="w-10 h-10 bg-blue-100 rounded-lg flex items-center justify-center flex-shrink-0">
                                <i class="ri-calendar-check-line text-blue-600"></i>
                            </div>
                            <div class="flex-1 min-w-0">
                                <p class="text-xs text-gray-500 mb-0.5">تاريخ البدء</p>
                                <p class="font-bold text-gray-900">{{ $course->start_date->format('d/m/Y') }}</p>
                            </div>
                        </div>

                        <!-- End Date -->
                        @if($course->end_date)
                        <div class="flex items-center gap-3 p-3 bg-gray-50 rounded-xl">
                            <div class="w-10 h-10 bg-purple-100 rounded-lg flex items-center justify-center flex-shrink-0">
                                <i class="ri-calendar-close-line text-purple-600"></i>
                            </div>
                            <div class="flex-1 min-w-0">
                                <p class="text-xs text-gray-500 mb-0.5">تاريخ الانتهاء</p>
                                <p class="font-bold text-gray-900">{{ $course->end_date->format('d/m/Y') }}</p>
                            </div>
                        </div>
                        @endif

                        <!-- Enrollment Deadline with Countdown - Only show for non-enrolled students -->
                        @if(!$isEnrolled)
                        @php
                            $deadline = $enrollmentStats['enrollment_deadline'];
                            $now = now();
                            $daysLeft = $now->diffInDays($deadline, false);
                            $isDeadlinePassed = $daysLeft < 0;
                            $isDeadlineClose = $daysLeft >= 0 && $daysLeft <= 7;

                            if ($isDeadlinePassed) {
                                $bgColor = 'bg-red-50';
                                $iconBg = 'bg-red-100';
                                $iconColor = 'text-red-600';
                                $borderColor = 'border-red-200';
                                $countdownColor = 'text-red-700';
                            } elseif ($isDeadlineClose) {
                                $bgColor = 'bg-amber-50';
                                $iconBg = 'bg-amber-100';
                                $iconColor = 'text-amber-600';
                                $borderColor = 'border-amber-200';
                                $countdownColor = 'text-amber-700';
                            } else {
                                $bgColor = 'bg-green-50';
                                $iconBg = 'bg-green-100';
                                $iconColor = 'text-green-600';
                                $borderColor = 'border-green-200';
                                $countdownColor = 'text-green-700';
                            }
                        @endphp

                        <div class="p-4 {{ $bgColor }} rounded-xl border-2 {{ $borderColor }}" id="enrollment-deadline-card">
                            <div class="flex items-center gap-3 mb-4">
                                <div class="w-10 h-10 {{ $iconBg }} rounded-lg flex items-center justify-center flex-shrink-0">
                                    <i class="ri-time-line {{ $iconColor }}"></i>
                                </div>
                                <div class="flex-1">
                                    <p class="text-xs text-gray-600 mb-0.5">آخر موعد للتسجيل</p>
                                    <p class="font-bold text-gray-900">{{ $deadline->format('d/m/Y') }}</p>
                                </div>
                            </div>

                            <!-- Countdown Timer -->
                            @if($isDeadlinePassed)
                                <div class="flex items-center justify-center gap-2 {{ $countdownColor }} font-bold py-3">
                                    <i class="ri-close-circle-fill text-xl"></i>
                                    <span>انتهى موعد التسجيل</span>
                                </div>
                            @else
                                <div id="countdown-timer" class="text-center py-2"
                                     data-deadline="{{ $deadline->format('Y-m-d 23:59:59') }}">
                                    <div class="flex justify-center items-start gap-1">
                                        <div class="flex flex-col items-center">
                                            <span id="countdown-days" class="text-2xl font-bold {{ $countdownColor }} font-mono">00</span>
                                            <span class="text-xs text-gray-500 mt-1">يوم</span>
                                        </div>
                                        <span class="text-2xl font-bold {{ $countdownColor }}">:</span>
                                        <div class="flex flex-col items-center">
                                            <span id="countdown-hours" class="text-2xl font-bold {{ $countdownColor }} font-mono">00</span>
                                            <span class="text-xs text-gray-500 mt-1">ساعة</span>
                                        </div>
                                        <span class="text-2xl font-bold {{ $countdownColor }}">:</span>
                                        <div class="flex flex-col items-center">
                                            <span id="countdown-minutes" class="text-2xl font-bold {{ $countdownColor }} font-mono">00</span>
                                            <span class="text-xs text-gray-500 mt-1">دقيقة</span>
                                        </div>
                                        <span class="text-2xl font-bold {{ $countdownColor }}">:</span>
                                        <div class="flex flex-col items-center">
                                            <span id="countdown-seconds" class="text-2xl font-bold {{ $countdownColor }} font-mono">00</span>
                                            <span class="text-xs text-gray-500 mt-1">ثانية</span>
                                        </div>
                                    </div>
                                </div>
                            @endif
                        </div>
                        @endif
                    </div>
                </div>

                <!-- Progress Summary (only for enrolled students) -->
                @if($isEnrolled && $student)
                    <x-interactive.progress-summary
                        :courseId="$course->id"
                        :studentId="$student->id"
                    />
                @endif

                <!-- Certificate Section (only for enrolled students) -->
                @if($isEnrolled && isset($enrollment))
                    @php
                        $certificate = $enrollment->certificate;
                        $canRequestCertificate = $enrollment->completion_percentage >= 100 && !$certificate;
                    @endphp

                    <div class="bg-white rounded-2xl p-6 shadow-sm border border-gray-200">
                        <h3 class="text-lg font-bold text-gray-900 mb-4 flex items-center gap-2">
                            <i class="ri-award-line text-amber-500"></i>
                            شهادة الإتمام
                        </h3>

                        @if($certificate)
                            <!-- Certificate Already Issued -->
                            <div class="bg-gradient-to-br from-amber-50 to-yellow-50 rounded-xl p-6 border-2 border-amber-200">
                                <div class="text-center mb-4">
                                    <div class="w-20 h-20 bg-amber-100 rounded-full flex items-center justify-center mx-auto mb-3">
                                        <i class="ri-medal-fill text-amber-500 text-4xl"></i>
                                    </div>
                                    <p class="font-bold text-amber-900 text-lg mb-1">تم إصدار الشهادة</p>
                                    <p class="text-amber-700 text-sm">رقم الشهادة: {{ $certificate->certificate_number }}</p>
                                </div>

                                <div class="flex gap-3">
                                    <a href="{{ route('student.certificate.view', $certificate) }}"
                                       target="_blank"
                                       class="flex-1 inline-flex items-center justify-center px-4 py-3 bg-amber-500 hover:bg-amber-600 text-white rounded-lg transition-colors duration-200 font-semibold">
                                        <i class="ri-eye-line ml-2"></i>
                                        عرض
                                    </a>
                                    <a href="{{ route('student.certificate.download', $certificate) }}"
                                       class="flex-1 inline-flex items-center justify-center px-4 py-3 bg-amber-600 hover:bg-amber-700 text-white rounded-lg transition-colors duration-200 font-semibold">
                                        <i class="ri-download-line ml-2"></i>
                                        تحميل PDF
                                    </a>
                                </div>
                            </div>
                        @elseif($canRequestCertificate)
                            <!-- Can Request Certificate -->
                            <div class="bg-gradient-to-br from-green-50 to-emerald-50 rounded-xl p-6 border-2 border-green-200">
                                <div class="text-center mb-4">
                                    <div class="w-20 h-20 bg-green-100 rounded-full flex items-center justify-center mx-auto mb-3">
                                        <i class="ri-checkbox-circle-fill text-green-500 text-4xl"></i>
                                    </div>
                                    <p class="font-bold text-green-900 text-lg mb-1">مبروك!</p>
                                    <p class="text-green-700 text-sm mb-2">لقد أتممت الكورس بنجاح</p>
                                    <p class="text-green-600 text-xs">يمكنك الآن طلب شهادة الإتمام</p>
                                </div>

                                <form method="POST" action="{{ route('student.certificate.request-interactive') }}" class="w-full">
                                    @csrf
                                    <input type="hidden" name="enrollment_id" value="{{ $enrollment->id }}">
                                    <button type="submit"
                                            class="w-full inline-flex items-center justify-center px-6 py-3 bg-green-500 hover:bg-green-600 text-white rounded-lg transition-colors duration-200 font-bold text-lg shadow-lg hover:shadow-xl transform hover:-translate-y-0.5">
                                        <i class="ri-award-line ml-2 text-xl"></i>
                                        احصل على شهادتك الآن
                                    </button>
                                </form>
                            </div>
                        @else
                            <!-- Not Yet Eligible -->
                            <div class="bg-gradient-to-br from-blue-50 to-indigo-50 rounded-xl p-6 border-2 border-blue-200">
                                <div class="text-center mb-4">
                                    <div class="w-20 h-20 bg-blue-100 rounded-full flex items-center justify-center mx-auto mb-3">
                                        <i class="ri-hourglass-line text-blue-500 text-4xl"></i>
                                    </div>
                                    <p class="font-bold text-blue-900 text-lg mb-1">أكمل الكورس</p>
                                    <p class="text-blue-700 text-sm">للحصول على شهادة الإتمام</p>
                                </div>

                                <!-- Progress Bar -->
                                <div class="mb-3">
                                    <div class="flex items-center justify-between mb-2">
                                        <span class="text-sm font-medium text-blue-900">نسبة الإتمام</span>
                                        <span class="text-sm font-bold text-blue-600">{{ number_format($enrollment->completion_percentage, 0) }}%</span>
                                    </div>
                                    <div class="w-full bg-blue-100 rounded-full h-3 overflow-hidden">
                                        <div class="bg-gradient-to-r from-blue-500 to-indigo-500 h-3 rounded-full transition-all duration-300"
                                             style="width: {{ $enrollment->completion_percentage }}%"></div>
                                    </div>
                                </div>

                                <p class="text-xs text-blue-600 text-center">
                                    <i class="ri-information-line ml-1"></i>
                                    ستتوفر الشهادة عند إتمام 100% من الكورس
                                </p>
                            </div>
                        @endif
                    </div>
                @endif

                <!-- Quick Actions (only for enrolled students) -->
                @if($isEnrolled)
                    <div class="bg-white rounded-2xl p-6 shadow-sm border border-gray-200">
                        <x-circle.quick-actions
                            :circle="$course"
                            type="group"
                            view-type="student"
                            context="academic"
                            :is-enrolled="true"
                        />
                    </div>
                @endif
            </div>

        </div>
    </div>
</div>

<script>
function openSessionDetail(sessionId) {
    @if(auth()->check())
        const sessionUrl = '{{ route("student.interactive-sessions.show", ["subdomain" => auth()->user()->academy->subdomain ?? "itqan-academy", "session" => "SESSION_ID_PLACEHOLDER"]) }}';
        const finalUrl = sessionUrl.replace('SESSION_ID_PLACEHOLDER', sessionId);
        window.location.href = finalUrl;
    @else
        console.error('User not authenticated');
    @endif
}

// Real-time countdown timer
document.addEventListener('DOMContentLoaded', function() {
    const countdownTimer = document.getElementById('countdown-timer');
    if (!countdownTimer) return;

    const deadlineStr = countdownTimer.dataset.deadline;
    const deadlineDate = new Date(deadlineStr);

    const daysElement = document.getElementById('countdown-days');
    const hoursElement = document.getElementById('countdown-hours');
    const minutesElement = document.getElementById('countdown-minutes');
    const secondsElement = document.getElementById('countdown-seconds');

    function updateCountdown() {
        const now = new Date();
        const diff = deadlineDate - now;

        if (diff <= 0) {
            window.location.reload();
            return;
        }

        const days = Math.floor(diff / (1000 * 60 * 60 * 24));
        const hours = Math.floor((diff % (1000 * 60 * 60 * 24)) / (1000 * 60 * 60));
        const minutes = Math.floor((diff % (1000 * 60 * 60)) / (1000 * 60));
        const seconds = Math.floor((diff % (1000 * 60)) / 1000);

        daysElement.textContent = String(days).padStart(2, '0');
        hoursElement.textContent = String(hours).padStart(2, '0');
        minutesElement.textContent = String(minutes).padStart(2, '0');
        secondsElement.textContent = String(seconds).padStart(2, '0');
    }

    updateCountdown();
    setInterval(updateCountdown, 1000);
});
</script>

</x-layouts.student>
