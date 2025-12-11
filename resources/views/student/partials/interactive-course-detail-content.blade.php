<div class="max-w-7xl mx-auto px-4 sm:px-0">
    <!-- Breadcrumb -->
    <nav class="mb-4 md:mb-6 overflow-x-auto">
        <ol class="flex items-center gap-2 text-xs md:text-sm text-gray-500 whitespace-nowrap">
            <li><a href="{{ route('interactive-courses.index', ['subdomain' => $academy->subdomain ?? 'itqan-academy']) }}" class="hover:text-blue-600 transition-colors min-h-[44px] inline-flex items-center">الكورسات التفاعلية</a></li>
            <li><i class="ri-arrow-left-s-line"></i></li>
            <li class="text-gray-900 font-medium truncate max-w-[200px]">{{ $course->title }}</li>
        </ol>
    </nav>

    @php
        $now = now();
        $isEnrollmentClosed = ($course->enrollment_deadline && $course->enrollment_deadline < $now->toDateString()) || $enrollmentStats['available_spots'] <= 0;
        $isOngoing = $course->start_date && $course->start_date <= $now->toDateString() && $course->end_date && $course->end_date >= $now->toDateString();
        $isFinished = $course->end_date && $course->end_date < $now->toDateString();
        $isUpcoming = $course->start_date && $course->start_date > $now->toDateString();

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
    <div class="bg-gradient-to-br from-blue-50 to-white rounded-2xl p-4 sm:p-6 md:p-8 lg:p-10 mb-6 md:mb-8 border border-blue-100">
        <!-- Status Badge with Rating -->
        <div class="flex items-center justify-between gap-4 mb-4 flex-wrap">
            <div class="inline-flex items-center gap-2 px-3 py-1.5 rounded-full {{ $statusBg }} {{ $statusText }} text-sm font-medium">
                <i class="{{ $statusIcon }}"></i>
                <span>{{ $statusLabel }}</span>
            </div>

            <!-- Rating Stars -->
            @if($course->total_reviews > 0)
            <div class="flex items-center gap-2">
                <div class="flex items-center gap-1">
                    @for($i = 1; $i <= 5; $i++)
                        @if($i <= floor($course->avg_rating))
                            <i class="ri-star-fill text-yellow-400 text-lg"></i>
                        @elseif($i - 0.5 <= $course->avg_rating)
                            <i class="ri-star-half-fill text-yellow-400 text-lg"></i>
                        @else
                            <i class="ri-star-line text-gray-300 text-lg"></i>
                        @endif
                    @endfor
                </div>
                <span class="text-sm font-medium text-gray-700">{{ number_format($course->avg_rating, 1) }}</span>
                <span class="text-sm text-gray-500">({{ $course->total_reviews }})</span>
            </div>
            @endif
        </div>

        <!-- Title -->
        <h1 class="text-xl sm:text-2xl md:text-3xl lg:text-4xl font-bold text-gray-900 mb-3 md:mb-4 leading-tight">{{ $course->title }}</h1>

        <!-- Description -->
        @if($course->description)
        <p class="text-sm md:text-base lg:text-lg text-gray-600 leading-relaxed mb-4 md:mb-6">{{ $course->description }}</p>
        @endif

        <!-- Quick Info Pills -->
        <div class="flex flex-wrap gap-2 md:gap-3">
            @if($course->subject)
            <div class="inline-flex items-center gap-1.5 md:gap-2 px-3 md:px-4 py-1.5 md:py-2 bg-white rounded-full border border-gray-200 shadow-sm">
                <i class="ri-bookmark-line text-blue-500 text-sm md:text-base"></i>
                <span class="text-xs md:text-sm font-medium text-gray-700">{{ $course->subject->name }}</span>
            </div>
            @endif

            @if($course->gradeLevel)
            <div class="inline-flex items-center gap-1.5 md:gap-2 px-3 md:px-4 py-1.5 md:py-2 bg-white rounded-full border border-gray-200 shadow-sm">
                <i class="ri-graduation-cap-line text-blue-500 text-sm md:text-base"></i>
                <span class="text-xs md:text-sm font-medium text-gray-700">{{ $course->gradeLevel->name }}</span>
            </div>
            @endif

            @if($course->difficulty_level)
            <div class="inline-flex items-center gap-1.5 md:gap-2 px-3 md:px-4 py-1.5 md:py-2 bg-white rounded-full border border-gray-200 shadow-sm">
                <i class="ri-bar-chart-line text-blue-500 text-sm md:text-base"></i>
                <span class="text-xs md:text-sm font-medium text-gray-700">
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
    <div class="grid grid-cols-1 lg:grid-cols-3 gap-4 md:gap-6 lg:gap-8" data-sticky-container>
        <!-- Main Content (Left Column - 2/3) -->
        <div class="lg:col-span-2 space-y-4 md:space-y-6 lg:space-y-8">

            <!-- About Course Section (Teacher, Learning Outcomes, Prerequisites) -->
            <div class="bg-white rounded-2xl p-4 md:p-6 lg:p-8 shadow-sm border border-gray-200">
                <!-- Teacher Information -->
                @if($course->assignedTeacher)
                <div class="mb-6 md:mb-10">
                    <h2 class="text-base md:text-lg font-bold text-gray-900 mb-4 md:mb-6 flex items-center gap-2">
                        <i class="ri-user-star-line text-blue-500"></i>
                        المدرس
                    </h2>
                    <div class="flex flex-col md:flex-row items-start gap-4 md:gap-6">
                        <!-- Teacher Avatar -->
                        <x-avatar
                            :user="$course->assignedTeacher->user"
                            size="2xl"
                            userType="academic_teacher"
                            :gender="$course->assignedTeacher->gender ?? 'male'" />

                        <!-- Teacher Info -->
                        <div class="flex-1">
                            <!-- Name with Rating -->
                            <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-2 mb-2">
                                <h3 class="text-lg md:text-xl font-bold text-gray-900">{{ $course->assignedTeacher->full_name }}</h3>
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

                            <!-- Action Buttons -->
                            <div class="flex flex-col sm:flex-row items-stretch sm:items-center gap-2 md:gap-3 mt-4">
                                <a href="{{ route('academic-teachers.show', ['subdomain' => auth()->user()->academy->subdomain ?? 'itqan-academy', 'teacherId' => $course->assignedTeacher->id]) }}"
                                   class="inline-flex items-center justify-center gap-2 min-h-[44px] px-4 md:px-5 py-2.5 bg-gray-100 hover:bg-gray-200 text-gray-900 rounded-xl font-medium transition-colors text-sm md:text-base">
                                    <i class="ri-user-line"></i>
                                    <span>عرض الملف الشخصي</span>
                                </a>

                                @if($isEnrolled)
                                <a href="#"
                                   class="inline-flex items-center justify-center gap-2 min-h-[44px] px-4 md:px-5 py-2.5 bg-green-500 hover:bg-green-600 text-white rounded-xl font-medium transition-colors text-sm md:text-base">
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
                <div class="mb-6 md:mb-10">
                    <h2 class="text-base md:text-lg font-bold text-gray-900 mb-4 md:mb-6 flex items-center gap-2">
                        <i class="ri-lightbulb-flash-line text-green-600"></i>
                        ما ستتعلمه
                    </h2>
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-3 md:gap-4">
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
                <div class="mb-6 md:mb-10">
                    <h2 class="text-base md:text-lg font-bold text-gray-900 mb-4 md:mb-6 flex items-center gap-2">
                        <i class="ri-file-list-3-line text-blue-600"></i>
                        المتطلبات الأساسية
                    </h2>
                    <div class="space-y-2 md:space-y-3">
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
                    <h2 class="text-base md:text-lg font-bold text-gray-900 mb-4 md:mb-6 flex items-center gap-2">
                        <i class="ri-calendar-2-line text-purple-600"></i>
                        الجدول الأسبوعي
                    </h2>
                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-2 md:gap-3">
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

            <!-- Content Tabs (Sessions, Quizzes, Reviews) -->
            @if($isEnrolled)
            <x-tabs id="course-content-tabs" default-tab="sessions" lazy url-sync>
                <x-slot name="tabs">
                    @php
                        $allCourseSessions = collect($upcomingSessions ?? [])->merge($pastSessions ?? []);
                        $courseReviews = $course->approvedReviews()->with('user')->latest()->get();
                    @endphp
                    <x-tabs.tab
                        id="sessions"
                        label="الجلسات"
                        icon="ri-calendar-line"
                        :badge="$allCourseSessions->count()"
                    />
                    <x-tabs.tab
                        id="quizzes"
                        label="الاختبارات"
                        icon="ri-file-list-3-line"
                    />
                    <x-tabs.tab
                        id="reviews"
                        label="التقييمات"
                        icon="ri-star-line"
                        :badge="$courseReviews->count()"
                    />
                </x-slot>

                <x-slot name="panels">
                    <x-tabs.panel id="sessions">
                        @php
                            $allCourseSessions = collect($upcomingSessions ?? [])->merge($pastSessions ?? []);
                        @endphp
                        <x-sessions.sessions-list
                            :sessions="$allCourseSessions"
                            view-type="student"
                            :show-tabs="false"
                            empty-message="لا توجد جلسات مجدولة بعد" />
                    </x-tabs.panel>

                    <x-tabs.panel id="quizzes">
                        <livewire:quizzes-widget :assignable="$course" />
                    </x-tabs.panel>

                    <x-tabs.panel id="reviews" lazy>
                        @php
                            $courseReviews = $course->approvedReviews()->with('user')->latest()->get();
                        @endphp
                        <x-reviews.section
                            :reviewable-type="\App\Models\InteractiveCourse::class"
                            :reviewable-id="$course->id"
                            review-type="course"
                            :reviews="$courseReviews"
                            :rating="$course->avg_rating ?? 0"
                            :total-reviews="$course->total_reviews ?? 0"
                            :show-summary="$courseReviews->count() > 0"
                            :show-breakdown="true"
                            :show-review-form="$isEnrolled"
                        />
                    </x-tabs.panel>
                </x-slot>
            </x-tabs>
            @else
            <!-- Course Reviews Section (for non-enrolled students) -->
            @php
                $courseReviews = $course->approvedReviews()->with('user')->latest()->get();
            @endphp
            <div class="bg-white rounded-2xl p-4 md:p-6 lg:p-8 shadow-sm border border-gray-200">
                <x-reviews.section
                    :reviewable-type="\App\Models\InteractiveCourse::class"
                    :reviewable-id="$course->id"
                    review-type="course"
                    :reviews="$courseReviews"
                    :rating="$course->avg_rating ?? 0"
                    :total-reviews="$course->total_reviews ?? 0"
                    :show-summary="$courseReviews->count() > 0"
                    :show-breakdown="true"
                    :show-review-form="false"
                />
            </div>
            @endif

        </div>

        <!-- Sidebar (Right Column - 1/3) -->
        <div data-sticky-sidebar>
            <!-- Inner wrapper for proper spacing -->
            <div class="space-y-4 md:space-y-6">
                @if($isEnrolled && isset($enrollment))
                    <!-- Enrollment Status - Show for enrolled students -->
                    <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-4 md:p-6 mb-4 md:mb-6">
                        <h3 class="font-bold text-gray-900 mb-3 md:mb-4 flex items-center gap-2">
                            <i class="ri-file-list-3-line text-purple-500 text-lg" style="font-weight: 100;"></i>
                            حالة التسجيل
                        </h3>

                        <!-- Enrolled Badge -->
                        <div class="bg-gradient-to-r from-green-50 to-emerald-50 rounded-lg p-4 border-2 border-green-200 mb-4">
                            <div class="flex items-center justify-center gap-2">
                                <i class="ri-check-line text-2xl text-green-600"></i>
                                <span class="text-lg font-bold text-green-800">مسجل في الدورة</span>
                            </div>
                            @if($enrollment->enrollment_date)
                                <p class="text-xs text-green-700 text-center mt-2">
                                    تاريخ التسجيل: {{ \Carbon\Carbon::parse($enrollment->enrollment_date)->locale('ar')->translatedFormat('d F Y') }}
                                </p>
                            @endif
                        </div>

                        <!-- Progress -->
                        @if(isset($enrollment->completion_percentage))
                            <div class="mb-4">
                                <div class="flex justify-between items-center mb-2">
                                    <span class="text-sm font-medium text-gray-700">التقدم في الدورة</span>
                                    <span class="text-sm font-bold text-primary">{{ round($enrollment->completion_percentage) }}%</span>
                                </div>
                                <div class="w-full bg-gray-200 rounded-full h-3">
                                    <div class="bg-primary h-3 rounded-full transition-all duration-500"
                                         style="width: {{ round($enrollment->completion_percentage) }}%"></div>
                                </div>
                            </div>
                        @endif

                        <!-- Payment Status -->
                        @if($enrollment->payment_status)
                            <div class="flex items-center justify-between p-3 bg-gray-50 rounded-lg">
                                <div class="flex items-center">
                                    <i class="ri-money-dollar-circle-line text-gray-600 ml-2"></i>
                                    <span class="text-sm text-gray-700">حالة الدفع</span>
                                </div>
                                <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium
                                    {{ $enrollment->payment_status === 'paid' ? 'bg-green-100 text-green-800' :
                                       ($enrollment->payment_status === 'pending' ? 'bg-yellow-100 text-yellow-800' : 'bg-gray-100 text-gray-800') }}">
                                    {{ $enrollment->payment_status === 'paid' ? 'مدفوع' :
                                       ($enrollment->payment_status === 'pending' ? 'قيد الانتظار' : $enrollment->payment_status) }}
                                </span>
                            </div>
                        @endif
                    </div>
                @elseif(!$isEnrolled && $course->is_published && (!$course->enrollment_deadline || $course->enrollment_deadline >= now()->toDateString()) && ($enrollmentStats['available_spots'] ?? 0) > 0)
                    <!-- Enrollment Card - Show for non-enrolled students who can enroll -->
                    <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-4 md:p-6 mb-4 md:mb-6">
                        <h3 class="font-bold text-gray-900 mb-3 md:mb-4 flex items-center gap-2">
                            <i class="ri-shopping-cart-line text-purple-500 text-lg" style="font-weight: 100;"></i>
                            التسجيل في الدورة
                        </h3>
                        <form id="enrollForm" method="POST" action="{{ route('interactive-courses.enroll', ['subdomain' => auth()->user()->academy->subdomain ?? 'itqan-academy', 'courseId' => $course->id]) }}">
                            @csrf
                            <button type="button"
                                onclick="showConfirmModal({
                                    title: 'تأكيد التسجيل في الكورس',
                                    message: 'هل أنت متأكد من التسجيل في هذا الكورس؟@if($course->enrollment_fee && $course->is_enrollment_fee_required) سيتم تحصيل رسوم تسجيل بقيمة {{ number_format($course->enrollment_fee) }} ر.س.@endif',
                                    confirmText: 'نعم، سجل الآن',
                                    cancelText: 'إلغاء',
                                    type: 'success',
                                    onConfirm: function() {
                                        document.getElementById('enrollForm').submit();
                                    }
                                })"
                                class="group w-full min-h-[48px] bg-gradient-to-r from-blue-500 to-blue-600 hover:from-blue-600 hover:to-blue-700 text-white px-4 md:px-6 py-3 md:py-4 rounded-xl font-bold text-base md:text-lg transition-all shadow-lg hover:shadow-xl transform hover:-translate-y-1 relative overflow-hidden">
                                <span class="relative z-10 flex items-center justify-center gap-2">
                                    <i class="ri-shopping-cart-line text-xl"></i>
                                    <span>سجل الآن</span>
                                </span>
                                <div class="absolute inset-0 bg-gradient-to-r from-transparent via-white/20 to-transparent translate-x-[-200%] group-hover:translate-x-[200%] transition-transform duration-1000"></div>
                            </button>
                        </form>
                    </div>
                @endif

                <!-- Course Information Widget (معلومات الدورة) -->
                <div class="bg-white rounded-2xl p-4 md:p-6 shadow-sm border border-gray-200 mb-4 md:mb-6">
                    <h3 class="text-base md:text-lg font-bold text-gray-900 mb-3 md:mb-4 flex items-center gap-2">
                        <i class="ri-information-line text-blue-500" style="font-weight: 100;"></i>
                        معلومات الدورة
                    </h3>
                    <div class="space-y-2 md:space-y-3">
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
                        @if(!$isEnrolled && $enrollmentStats['enrollment_deadline'])
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
                @if($isEnrolled && isset($student))
                    <x-interactive.progress-summary
                        :courseId="$course->id"
                        :studentId="$student->id"
                    />
                @endif

                <!-- Quick Actions (only for enrolled students) -->
                @if($isEnrolled)
                    <div class="mb-4 md:mb-6">
                        <x-circle.quick-actions
                            :circle="$course"
                            type="group"
                            view-type="student"
                            context="interactive"
                            :is-enrolled="true"
                        />
                    </div>
                @endif

                <!-- Certificate Section (only for enrolled students) -->
                @if($isEnrolled && isset($enrollment))
                    <x-certificate.student-certificate-section
                        :subscription="$enrollment"
                        type="interactive"
                    />
                @endif
            </div>
            <!-- End inner wrapper -->
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
