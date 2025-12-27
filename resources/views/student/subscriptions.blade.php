@php
    use App\Enums\SubscriptionStatus;

    $academy = auth()->user()->academy;
    $subdomain = request()->route('subdomain') ?? $academy->subdomain ?? 'itqan-academy';
    $isParent = ($layout ?? 'student') === 'parent';
    $routePrefix = $isParent ? 'parent.subscriptions' : 'student.subscriptions';

    // Build unified subscriptions list
    $allSubscriptions = collect();

    // Add individual Quran subscriptions
    foreach ($individualQuranSubscriptions as $sub) {
        $statusEnum = $sub->status instanceof SubscriptionStatus
            ? $sub->status
            : SubscriptionStatus::tryFrom($sub->status) ?? SubscriptionStatus::PENDING;

        $allSubscriptions->push([
            'id' => $sub->id,
            'type' => 'quran_individual',
            'type_label' => 'قرآن فردي',
            'type_icon' => 'ri-user-line',
            'type_color' => 'yellow',
            'title' => $sub->quranTeacher?->full_name ?? $sub->quranTeacherUser?->name ?? 'معلم غير محدد',
            'subtitle' => $sub->package_name_ar ?? $sub->package?->name_ar ?? 'اشتراك فردي',
            'status' => $statusEnum,
            'status_label' => $statusEnum->label(),
            'status_classes' => $statusEnum->badgeClasses(),
            'is_active' => $statusEnum === SubscriptionStatus::ACTIVE,
            'progress' => $sub->total_sessions > 0 ? round(($sub->sessions_used / $sub->total_sessions) * 100) : 0,
            'sessions_used' => $sub->sessions_used ?? 0,
            'total_sessions' => $sub->total_sessions ?? 0,
            'sessions_remaining' => $sub->sessions_remaining ?? 0,
            'auto_renew' => $sub->auto_renew ?? false,
            'next_billing_date' => $sub->next_billing_date ?? $sub->ends_at,
            'billing_cycle' => $sub->billing_cycle,
            'href' => $sub->individualCircle?->id
                ? route('individual-circles.show', ['subdomain' => $subdomain, 'circle' => $sub->individualCircle->id])
                : null,
            'created_at' => $sub->created_at,
            'can_cancel' => $statusEnum === SubscriptionStatus::ACTIVE,
            'model' => $sub,
            'model_type' => 'quran',
        ]);
    }

    // Add group Quran subscriptions
    foreach ($groupQuranSubscriptions as $sub) {
        $statusEnum = $sub->status instanceof SubscriptionStatus
            ? $sub->status
            : SubscriptionStatus::tryFrom($sub->status) ?? SubscriptionStatus::PENDING;

        $circle = $sub->circle;

        $allSubscriptions->push([
            'id' => $sub->id,
            'type' => 'quran_group',
            'type_label' => 'حلقة جماعية',
            'type_icon' => 'ri-group-line',
            'type_color' => 'green',
            'title' => $circle?->name_ar ?? $circle?->name ?? $sub->quranTeacher?->full_name ?? 'حلقة قرآنية',
            'subtitle' => $sub->quranTeacher?->full_name ?? 'معلم غير محدد',
            'status' => $statusEnum,
            'status_label' => $statusEnum->label(),
            'status_classes' => $statusEnum->badgeClasses(),
            'is_active' => $statusEnum === SubscriptionStatus::ACTIVE,
            'progress' => $sub->total_sessions > 0 ? round(($sub->sessions_used / $sub->total_sessions) * 100) : 0,
            'sessions_used' => $sub->sessions_used ?? 0,
            'total_sessions' => $sub->total_sessions ?? 0,
            'sessions_remaining' => $sub->sessions_remaining ?? 0,
            'students_count' => $circle?->students?->count() ?? 0,
            'max_students' => $circle?->max_students ?? 10,
            'auto_renew' => $sub->auto_renew ?? false,
            'next_billing_date' => $sub->next_billing_date ?? $sub->ends_at,
            'billing_cycle' => $sub->billing_cycle,
            'href' => $circle?->id
                ? route('quran-circles.show', ['subdomain' => $subdomain, 'circleId' => $circle->id])
                : null,
            'created_at' => $sub->created_at,
            'can_cancel' => $statusEnum === SubscriptionStatus::ACTIVE,
            'model' => $sub,
            'model_type' => 'quran',
        ]);
    }

    // Add academic subscriptions
    foreach ($academicSubscriptions as $sub) {
        $statusEnum = $sub->status instanceof SubscriptionStatus
            ? $sub->status
            : SubscriptionStatus::tryFrom($sub->status) ?? SubscriptionStatus::PENDING;

        $totalSessions = $sub->total_sessions_scheduled ?? 0;
        $completedSessions = $sub->total_sessions_completed ?? 0;

        $allSubscriptions->push([
            'id' => $sub->id,
            'type' => 'academic',
            'type_label' => 'دروس أكاديمية',
            'type_icon' => 'ri-book-open-line',
            'type_color' => 'violet',
            'title' => $sub->teacher?->user?->name ?? 'معلم غير محدد',
            'subtitle' => ($sub->subject?->name ?? 'مادة غير محددة') . ' - ' . ($sub->gradeLevel?->name ?? ''),
            'status' => $statusEnum,
            'status_label' => $statusEnum->label(),
            'status_classes' => $statusEnum->badgeClasses(),
            'is_active' => $statusEnum === SubscriptionStatus::ACTIVE,
            'progress' => $totalSessions > 0 ? round(($completedSessions / $totalSessions) * 100) : 0,
            'sessions_used' => $completedSessions,
            'total_sessions' => $totalSessions,
            'sessions_remaining' => max(0, $totalSessions - $completedSessions),
            'auto_renew' => $sub->auto_renew ?? false,
            'next_billing_date' => $sub->next_billing_date ?? $sub->ends_at,
            'billing_cycle' => $sub->billing_cycle,
            'href' => route('student.academic-subscriptions.show', ['subdomain' => $subdomain, 'subscriptionId' => $sub->id]),
            'created_at' => $sub->created_at,
            'can_cancel' => $statusEnum === SubscriptionStatus::ACTIVE,
            'model' => $sub,
            'model_type' => 'academic',
        ]);
    }

    // Add interactive courses
    foreach ($courseEnrollments as $course) {
        $enrollment = $course->enrollments->first();
        $allSubscriptions->push([
            'id' => $course->id,
            'type' => 'course',
            'type_label' => 'دورة تفاعلية',
            'type_icon' => 'ri-slideshow-line',
            'type_color' => 'blue',
            'title' => $course->title,
            'subtitle' => $course->assignedTeacher?->user?->name ?? 'معلم غير محدد',
            'status' => null,
            'status_label' => $enrollment?->enrollment_status === \App\Enums\EnrollmentStatus::COMPLETED ? 'مكتمل' : 'مسجل',
            'status_classes' => $enrollment?->enrollment_status === \App\Enums\EnrollmentStatus::COMPLETED ? 'bg-green-100 text-green-800' : 'bg-blue-100 text-blue-800',
            'is_active' => $enrollment?->enrollment_status === \App\Enums\EnrollmentStatus::ENROLLED,
            'progress' => $enrollment?->progress_percentage ?? 0,
            'sessions_used' => null,
            'total_sessions' => null,
            'sessions_remaining' => null,
            'auto_renew' => null,
            'next_billing_date' => null,
            'billing_cycle' => null,
            'href' => route('interactive-courses.show', ['subdomain' => $subdomain, 'courseId' => $course->id]),
            'created_at' => $enrollment?->created_at ?? $course->created_at,
            'can_cancel' => false,
            'model' => $course,
            'model_type' => 'course',
        ]);
    }

    // Sort by created_at descending
    $allSubscriptions = $allSubscriptions->sortByDesc('created_at');

    // Apply filters
    $filterStatus = request('status');
    $filterType = request('type');

    if ($filterStatus === \App\Enums\SubscriptionStatus::ACTIVE->value) {
        $allSubscriptions = $allSubscriptions->filter(fn($s) => $s['is_active']);
    } elseif ($filterStatus === 'inactive') {
        $allSubscriptions = $allSubscriptions->filter(fn($s) => !$s['is_active']);
    }

    if ($filterType && $filterType !== 'all') {
        $allSubscriptions = $allSubscriptions->filter(fn($s) => $s['type'] === $filterType);
    }

    $totalCount = $individualQuranSubscriptions->count() + $groupQuranSubscriptions->count() + $academicSubscriptions->count() + $courseEnrollments->count();
    $activeCount = $allSubscriptions->filter(fn($s) => $s['is_active'])->count();
@endphp

<x-layouts.authenticated
    :role="$layout ?? 'student'"
    title="{{ $academy->name ?? 'أكاديمية إتقان' }} - {{ $isParent ? 'اشتراكات الأبناء' : 'الاشتراكات' }}">
    <x-slot name="description">{{ $isParent ? 'متابعة اشتراكات الأبناء' : 'إدارة جميع اشتراكاتك والدورات المسجلة' }} - {{ $academy->name ?? 'أكاديمية إتقان' }}</x-slot>

    <!-- Header Section (Using reusable component - same as certificates page) -->
    <x-student-page.header
        title="{{ $isParent ? 'اشتراكات الأبناء' : 'الاشتراكات' }}"
        description="{{ $isParent ? 'متابعة اشتراكات الأبناء والدورات المسجلة' : 'إدارة جميع اشتراكاتك والدورات المسجلة' }}"
        :count="$totalCount"
        countLabel="إجمالي الاشتراكات"
        countColor="blue"
        :secondaryCount="$activeCount"
        secondaryCountLabel="نشط"
        secondaryCountColor="green"
    />

    <!-- Flash Messages -->
    @if(session('success'))
        <div class="mb-6 p-4 bg-green-50 border border-green-200 rounded-xl flex items-center gap-3">
            <i class="ri-checkbox-circle-fill text-green-600 text-xl"></i>
            <span class="text-green-800">{{ session('success') }}</span>
        </div>
    @endif
    @if(session('error'))
        <div class="mb-6 p-4 bg-red-50 border border-red-200 rounded-xl flex items-center gap-3">
            <i class="ri-error-warning-fill text-red-600 text-xl"></i>
            <span class="text-red-800">{{ session('error') }}</span>
        </div>
    @endif

    <!-- Filters Section (Same style as quran-circles) -->
    <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-4 md:p-6 mb-6 md:mb-8">
        <form method="GET" action="{{ route($isParent ? 'parent.subscriptions.index' : 'student.subscriptions', ['subdomain' => $subdomain]) }}" class="space-y-4">
            <div class="mb-4">
                <h3 class="text-base md:text-lg font-semibold text-gray-900">
                    <i class="ri-filter-3-line ml-2"></i>
                    تصفية النتائج
                </h3>
            </div>

            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
                <!-- Status Filter -->
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">
                        <i class="ri-checkbox-circle-line ml-1"></i>
                        الحالة
                    </label>
                    <div class="relative">
                        <select name="status"
                                style="appearance: none; -webkit-appearance: none; -moz-appearance: none; background-image: none;"
                                class="w-full border border-gray-300 rounded-lg px-4 py-2.5 pr-10 text-sm focus:ring-2 focus:ring-blue-500 focus:border-blue-500 transition-colors bg-white">
                            <option value="">الكل</option>
                            <option value="active" {{ request('status') === 'active' ? 'selected' : '' }}>نشط</option>
                            <option value="inactive" {{ request('status') === 'inactive' ? 'selected' : '' }}>غير نشط</option>
                        </select>
                        <div class="pointer-events-none absolute inset-y-0 right-0 flex items-center px-3 text-gray-500">
                            <i class="ri-arrow-down-s-line text-lg"></i>
                        </div>
                    </div>
                </div>

                <!-- Type Filter -->
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">
                        <i class="ri-stack-line ml-1"></i>
                        نوع الاشتراك
                    </label>
                    <div class="relative">
                        <select name="type"
                                style="appearance: none; -webkit-appearance: none; -moz-appearance: none; background-image: none;"
                                class="w-full border border-gray-300 rounded-lg px-4 py-2.5 pr-10 text-sm focus:ring-2 focus:ring-blue-500 focus:border-blue-500 transition-colors bg-white">
                            <option value="">جميع الأنواع</option>
                            <option value="quran_individual" {{ request('type') === 'quran_individual' ? 'selected' : '' }}>قرآن فردي</option>
                            <option value="quran_group" {{ request('type') === 'quran_group' ? 'selected' : '' }}>حلقة جماعية</option>
                            <option value="academic" {{ request('type') === 'academic' ? 'selected' : '' }}>دروس أكاديمية</option>
                            <option value="course" {{ request('type') === 'course' ? 'selected' : '' }}>دورات تفاعلية</option>
                        </select>
                        <div class="pointer-events-none absolute inset-y-0 right-0 flex items-center px-3 text-gray-500">
                            <i class="ri-arrow-down-s-line text-lg"></i>
                        </div>
                    </div>
                </div>

                <!-- Empty space for alignment -->
                <div class="hidden lg:block"></div>
            </div>

            <!-- Buttons Row (Same style as quran-circles - separate row) -->
            <div class="flex flex-col sm:flex-row items-stretch sm:items-center gap-3 pt-2">
                <button type="submit"
                        class="inline-flex items-center justify-center min-h-[44px] bg-blue-600 text-white px-6 py-2.5 rounded-xl md:rounded-lg text-sm font-medium hover:bg-blue-700 transition-colors">
                    <i class="ri-search-line ml-1"></i>
                    تطبيق الفلاتر
                </button>

                @if(request()->hasAny(['status', 'type']))
                <a href="{{ route($isParent ? 'parent.subscriptions.index' : 'student.subscriptions', ['subdomain' => $subdomain]) }}"
                   class="inline-flex items-center justify-center min-h-[44px] bg-gray-100 text-gray-700 px-6 py-2.5 rounded-xl md:rounded-lg text-sm font-medium hover:bg-gray-200 transition-colors">
                    <i class="ri-close-circle-line ml-1"></i>
                    إعادة تعيين
                </a>
                @endif
            </div>
        </form>
    </div>

    <!-- Subscriptions Cards (Full Width) -->
    @if($allSubscriptions->count() > 0)
        <div class="space-y-4 mb-8">
            @foreach($allSubscriptions as $subscription)
                @php
                    // Color classes matching resource listing pages
                    $colorClasses = [
                        'yellow' => ['bg' => 'bg-yellow-600', 'light' => 'bg-yellow-50', 'text' => 'text-yellow-600', 'border' => 'border-yellow-300', 'ring' => 'ring-yellow-100'],
                        'green' => ['bg' => 'bg-green-600', 'light' => 'bg-green-50', 'text' => 'text-green-600', 'border' => 'border-green-300', 'ring' => 'ring-green-100'],
                        'blue' => ['bg' => 'bg-blue-600', 'light' => 'bg-blue-50', 'text' => 'text-blue-600', 'border' => 'border-blue-300', 'ring' => 'ring-blue-100'],
                        'violet' => ['bg' => 'bg-violet-600', 'light' => 'bg-violet-50', 'text' => 'text-violet-600', 'border' => 'border-violet-300', 'ring' => 'ring-violet-100'],
                        'cyan' => ['bg' => 'bg-cyan-600', 'light' => 'bg-cyan-50', 'text' => 'text-cyan-600', 'border' => 'border-cyan-300', 'ring' => 'ring-cyan-100'],
                    ];
                    $colors = $colorClasses[$subscription['type_color']] ?? $colorClasses['blue'];

                    $billingLabel = null;
                    if ($subscription['billing_cycle']) {
                        $billingLabel = $subscription['billing_cycle'] instanceof \App\Enums\BillingCycle
                            ? $subscription['billing_cycle']->label()
                            : match($subscription['billing_cycle']) {
                                'monthly' => 'شهري',
                                'quarterly' => 'ربع سنوي',
                                'yearly' => 'سنوي',
                                default => $subscription['billing_cycle'],
                            };
                    }
                @endphp
                <div class="bg-white rounded-xl shadow-sm border border-gray-200 hover:shadow-md hover:border-gray-300 transition-all duration-200 overflow-hidden">
                    <div class="p-4 md:p-5 lg:p-6">
                        <!-- Row 1: Icon + Title on Right, Actions on Left -->
                        <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4 mb-4">
                            <!-- Right Side: Icon + Title -->
                            <div class="flex items-center gap-4">
                                <div class="w-12 h-12 rounded-xl {{ $colors['light'] }} flex items-center justify-center flex-shrink-0">
                                    <i class="{{ $subscription['type_icon'] }} {{ $colors['text'] }} text-xl"></i>
                                </div>
                                <div class="min-w-0">
                                    <div class="flex flex-wrap items-center gap-2">
                                        <h3 class="text-lg font-bold text-gray-900">{{ $subscription['title'] }}</h3>
                                        <span class="inline-flex items-center px-2 py-0.5 rounded-lg text-xs font-medium {{ $colors['light'] }} {{ $colors['text'] }}">
                                            {{ $subscription['type_label'] }}
                                        </span>
                                        <span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-medium {{ $subscription['status_classes'] }}">
                                            {{ $subscription['status_label'] }}
                                        </span>
                                    </div>
                                    <p class="text-sm text-gray-600 mt-0.5">{{ $subscription['subtitle'] }}</p>
                                </div>
                            </div>

                            <!-- Left Side: Actions -->
                            <div class="flex flex-wrap items-center gap-2 sm:flex-shrink-0">
                                @if($subscription['href'])
                                    <a href="{{ $subscription['href'] }}"
                                       class="inline-flex items-center justify-center min-h-[44px] px-4 py-2 bg-white border border-gray-300 rounded-xl md:rounded-lg text-sm font-medium text-gray-700 hover:bg-gray-50 hover:border-gray-400 transition-colors">
                                        <i class="ri-eye-line ml-2"></i>
                                        عرض التفاصيل
                                    </a>
                                @endif

                                @if(!$isParent && $subscription['auto_renew'] !== null && $subscription['is_active'])
                                    <button type="button"
                                            onclick="toggleAutoRenew('{{ $subscription['model_type'] }}', {{ $subscription['id'] }}, {{ $subscription['auto_renew'] ? 'true' : 'false' }})"
                                            class="inline-flex items-center justify-center min-h-[44px] px-4 py-2 rounded-xl md:rounded-lg text-sm font-medium transition-colors
                                                   {{ $subscription['auto_renew']
                                                       ? 'bg-amber-50 text-amber-700 border border-amber-200 hover:bg-amber-100'
                                                       : 'bg-green-50 text-green-700 border border-green-200 hover:bg-green-100' }}">
                                        <i class="{{ $subscription['auto_renew'] ? 'ri-toggle-fill' : 'ri-toggle-line' }} ml-2"></i>
                                        {{ $subscription['auto_renew'] ? 'إيقاف التجديد' : 'تفعيل التجديد' }}
                                    </button>
                                @endif

                                @if(!$isParent && $subscription['can_cancel'])
                                    <button type="button"
                                            onclick="cancelSubscription('{{ $subscription['model_type'] }}', {{ $subscription['id'] }})"
                                            class="inline-flex items-center justify-center min-h-[44px] px-4 py-2 bg-red-50 text-red-700 border border-red-200 rounded-xl md:rounded-lg text-sm font-medium hover:bg-red-100 transition-colors">
                                        <i class="ri-close-circle-line ml-2"></i>
                                        إلغاء
                                    </button>
                                @endif
                            </div>
                        </div>

                        <!-- Row 2: Info Items (Full Width) -->
                        <div class="flex flex-wrap items-center gap-x-6 gap-y-2 text-sm pt-4 border-t border-gray-100">
                            <!-- Progress or Stats -->
                            @if($subscription['progress'] !== null && $subscription['total_sessions'])
                                <div class="flex items-center gap-3">
                                    <div class="flex items-center gap-2">
                                        <i class="ri-calendar-check-line text-gray-400"></i>
                                        <span class="text-gray-600">{{ $subscription['sessions_used'] }}/{{ $subscription['total_sessions'] }} جلسة</span>
                                    </div>
                                    <div class="w-24 h-2 bg-gray-200 rounded-full overflow-hidden">
                                        <div class="{{ $colors['bg'] }} h-full rounded-full transition-all" style="width: {{ $subscription['progress'] }}%"></div>
                                    </div>
                                    @if($subscription['sessions_remaining'] > 0)
                                        <span class="text-gray-500">({{ $subscription['sessions_remaining'] }} متبقية)</span>
                                    @endif
                                </div>
                            @elseif($subscription['type'] === 'quran_group')
                                <div class="flex items-center gap-2 text-gray-600">
                                    <i class="ri-group-2-line {{ $colors['text'] }}"></i>
                                    <span>{{ $subscription['students_count'] ?? 0 }} / {{ $subscription['max_students'] ?? 10 }} طالب</span>
                                </div>
                            @elseif($subscription['type'] === 'course')
                                <div class="flex items-center gap-3">
                                    <div class="flex items-center gap-2">
                                        <i class="ri-pie-chart-line text-gray-400"></i>
                                        <span class="text-gray-600">{{ $subscription['progress'] }}% مكتمل</span>
                                    </div>
                                    <div class="w-24 h-2 bg-gray-200 rounded-full overflow-hidden">
                                        <div class="{{ $colors['bg'] }} h-full rounded-full transition-all" style="width: {{ $subscription['progress'] }}%"></div>
                                    </div>
                                </div>
                            @endif

                            <!-- Billing Info -->
                            @if($billingLabel)
                                <div class="flex items-center gap-1.5 text-gray-600">
                                    <i class="ri-repeat-line text-gray-400"></i>
                                    <span>{{ $billingLabel }}</span>
                                </div>
                            @endif

                            @if($subscription['next_billing_date'])
                                <div class="flex items-center gap-1.5 text-amber-600">
                                    <i class="ri-calendar-line"></i>
                                    <span>التجديد: {{ $subscription['next_billing_date']->format('d/m/Y') }}</span>
                                </div>
                            @endif

                            @if($subscription['auto_renew'] !== null)
                                <div class="flex items-center gap-1.5 {{ $subscription['auto_renew'] ? 'text-green-600' : 'text-gray-500' }}">
                                    <i class="{{ $subscription['auto_renew'] ? 'ri-checkbox-circle-fill' : 'ri-close-circle-line' }}"></i>
                                    <span>التجديد التلقائي: {{ $subscription['auto_renew'] ? 'مفعّل' : 'معطّل' }}</span>
                                </div>
                            @endif
                        </div>
                    </div>
                </div>
            @endforeach
        </div>
    @else
        <!-- Empty State (Using reusable component) -->
        <x-student-page.empty-state
            icon="ri-bookmark-line"
            title="{{ ($filterStatus || $filterType) ? 'لا توجد نتائج' : ($isParent ? 'لا توجد اشتراكات للأبناء' : 'لا توجد اشتراكات حالياً') }}"
            description="{{ ($filterStatus || $filterType) ? 'لم يتم العثور على اشتراكات تطابق معايير البحث' : ($isParent ? 'لم يتم تسجيل أي اشتراكات للأبناء بعد' : 'ابدأ رحلة التعلم معنا من خلال الاشتراك في أحد برامجنا التعليمية') }}"
            :actionUrl="($filterStatus || $filterType) ? route($isParent ? 'parent.subscriptions.index' : 'student.subscriptions', ['subdomain' => $subdomain]) : ($isParent ? null : route('quran-teachers.index', ['subdomain' => $subdomain]))"
            actionLabel="{{ ($filterStatus || $filterType) ? 'إعادة تعيين' : ($isParent ? '' : 'تصفح المعلمين') }}"
            actionIcon="{{ ($filterStatus || $filterType) ? 'ri-refresh-line' : 'ri-user-line' }}"
            iconBgColor="blue"
        />
    @endif

    <!-- Trial Requests Section (Students only) -->
    @if(!$isParent && isset($quranTrialRequests) && $quranTrialRequests->count() > 0)
    <div class="mt-8">
        <div class="mb-4">
            <h2 class="text-xl font-bold text-gray-900">
                <i class="ri-test-tube-line text-amber-500 ml-2"></i>
                الجلسات التجريبية
                <span class="text-sm font-normal text-gray-500 mr-2">({{ $quranTrialRequests->count() }})</span>
            </h2>
        </div>

        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
            @foreach($quranTrialRequests as $trial)
                <div class="bg-white rounded-xl shadow-sm border border-gray-200 overflow-hidden">
                    <!-- Header -->
                    <div class="bg-gradient-to-l from-amber-500 to-orange-500 p-4">
                        <div class="flex items-center justify-between">
                            <div class="flex items-center gap-3">
                                <div class="w-12 h-12 rounded-xl bg-white/20 backdrop-blur flex items-center justify-center">
                                    <i class="ri-user-star-line text-white text-xl"></i>
                                </div>
                                <span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-medium bg-white/20 text-white">
                                    جلسة تجريبية
                                </span>
                            </div>
                            <span class="inline-flex items-center px-2.5 py-1 rounded-full text-xs font-medium
                                {{ $trial->status === \App\Enums\TrialRequestStatus::APPROVED ? 'bg-green-100 text-green-800' :
                                   ($trial->status === \App\Enums\TrialRequestStatus::PENDING ? 'bg-yellow-100 text-yellow-800' :
                                   ($trial->status === \App\Enums\TrialRequestStatus::COMPLETED ? 'bg-blue-100 text-blue-800' : 'bg-red-100 text-red-800')) }}">
                                {{ $trial->status->label() }}
                            </span>
                        </div>
                    </div>

                    <!-- Body -->
                    <div class="p-4">
                        <h3 class="font-bold text-gray-900 mb-1">{{ $trial->teacher?->full_name ?? 'معلم غير محدد' }}</h3>
                        <p class="text-sm text-gray-600 mb-4">{{ $trial->request_code ?? '' }}</p>

                        <div class="space-y-2">
                            @if($trial->scheduled_at)
                                <div class="flex items-center text-sm text-green-600">
                                    <i class="ri-calendar-check-line ml-1"></i>
                                    {{ formatDateTimeArabic($trial->scheduled_at) }}
                                </div>
                            @endif
                            <div class="flex items-center text-xs text-gray-500">
                                <i class="ri-time-line ml-1"></i>
                                {{ formatDateArabic($trial->created_at, 'd/m/Y') }}
                            </div>
                        </div>
                    </div>

                    <!-- Footer -->
                    <div class="px-4 py-3 bg-gray-50 border-t border-gray-100">
                        @if($trial->status === \App\Enums\TrialRequestStatus::APPROVED && $trial->meeting_link)
                            <a href="{{ $trial->meeting_link }}" target="_blank"
                               class="w-full inline-flex items-center justify-center px-4 py-2 bg-green-600 text-white rounded-lg text-sm font-medium hover:bg-green-700 transition-colors">
                                <i class="ri-video-line ml-1"></i>
                                دخول الجلسة
                            </a>
                        @elseif($trial->status === \App\Enums\TrialRequestStatus::COMPLETED)
                            <a href="{{ route('quran-teachers.show', ['subdomain' => $subdomain, 'teacherId' => $trial->teacher?->id]) }}"
                               class="w-full inline-flex items-center justify-center px-4 py-2 bg-blue-600 text-white rounded-lg text-sm font-medium hover:bg-blue-700 transition-colors">
                                <i class="ri-arrow-left-line ml-1"></i>
                                اشترك الآن
                            </a>
                        @else
                            <span class="block text-center text-sm text-gray-500 py-2">
                                @if($trial->status === \App\Enums\TrialRequestStatus::PENDING)
                                    في انتظار الموافقة
                                @elseif($trial->status === \App\Enums\TrialRequestStatus::REJECTED)
                                    تم رفض الطلب
                                @endif
                            </span>
                        @endif
                    </div>
                </div>
            @endforeach
        </div>
    </div>
    @endif

    <!-- Confirmation Modal - Bottom sheet on mobile, centered on desktop -->
    <div id="confirmModal"
         x-data="{ open: false }"
         x-show="open"
         x-cloak
         @open-confirm-modal.window="open = true"
         @close-confirm-modal.window="open = false"
         @keydown.escape.window="if(open) { open = false; closeModal(); }"
         class="fixed inset-0 bg-black/50 backdrop-blur-sm z-50 overflow-y-auto"
         style="display: none;">

        <!-- Modal Container - Bottom sheet on mobile, centered on desktop -->
        <div class="fixed inset-0 flex items-end md:items-center justify-center p-0 md:p-4">
            <div x-show="open"
                 x-transition:enter="transition ease-out duration-200"
                 x-transition:enter-start="opacity-0 translate-y-full md:translate-y-0 md:scale-95"
                 x-transition:enter-end="opacity-100 translate-y-0 md:scale-100"
                 x-transition:leave="transition ease-in duration-150"
                 x-transition:leave-start="opacity-100 translate-y-0 md:scale-100"
                 x-transition:leave-end="opacity-0 translate-y-full md:translate-y-0 md:scale-95"
                 @click.stop
                 class="bg-white w-full md:max-w-md rounded-t-2xl md:rounded-2xl shadow-xl overflow-hidden">

                <!-- Mobile drag handle -->
                <div class="md:hidden absolute top-2 left-1/2 -translate-x-1/2 w-10 h-1 rounded-full bg-gray-300 z-10"></div>

                <div class="p-6 pt-8 md:pt-6 text-center">
                    <div id="modalIcon" class="w-16 h-16 mx-auto mb-4 rounded-full flex items-center justify-center"></div>
                    <h3 id="modalTitle" class="text-lg md:text-xl font-bold text-gray-900 mb-2"></h3>
                    <p id="modalMessage" class="text-gray-600 text-sm md:text-base"></p>
                </div>
                <div class="bg-gray-50 px-4 md:px-6 py-4 flex flex-col-reverse md:flex-row gap-3 md:justify-center">
                    <button onclick="closeModal()" class="min-h-[48px] md:min-h-[44px] flex-1 px-4 py-2.5 bg-gray-100 text-gray-700 rounded-xl md:rounded-lg font-medium hover:bg-gray-200 transition-colors">
                        إلغاء
                    </button>
                    <button id="modalConfirmBtn" class="min-h-[48px] md:min-h-[44px] flex-1 px-4 py-2.5 rounded-xl md:rounded-lg font-medium transition-colors">
                        تأكيد
                    </button>
                </div>
            </div>
        </div>
    </div>

    @push('scripts')
    <script>
        // Use route URLs generated by Laravel (includes subdomain automatically)
        const toggleAutoRenewUrl = "{{ route('student.subscriptions.toggle-auto-renew', ['subdomain' => $subdomain, 'type' => '__TYPE__', 'id' => '__ID__']) }}";
        const cancelSubscriptionUrl = "{{ route('student.subscriptions.cancel', ['subdomain' => $subdomain, 'type' => '__TYPE__', 'id' => '__ID__']) }}";

        function toggleAutoRenew(type, id, currentState) {
            const action = currentState ? 'إيقاف' : 'تفعيل';
            showModal(
                currentState ? 'bg-amber-100' : 'bg-green-100',
                currentState ? 'ri-toggle-fill text-amber-600 text-3xl' : 'ri-toggle-line text-green-600 text-3xl',
                `${action} التجديد التلقائي`,
                `هل أنت متأكد من ${action} التجديد التلقائي لهذا الاشتراك؟`,
                currentState ? 'bg-amber-600 text-white hover:bg-amber-700' : 'bg-green-600 text-white hover:bg-green-700',
                () => {
                    const form = document.createElement('form');
                    form.method = 'POST';
                    form.action = toggleAutoRenewUrl.replace('__TYPE__', type).replace('__ID__', id);
                    form.innerHTML = `
                        <input type="hidden" name="_token" value="{{ csrf_token() }}">
                        <input type="hidden" name="_method" value="PATCH">
                    `;
                    document.body.appendChild(form);
                    form.submit();
                }
            );
        }

        function cancelSubscription(type, id) {
            showModal(
                'bg-red-100',
                'ri-close-circle-line text-red-600 text-3xl',
                'إلغاء الاشتراك',
                'هل أنت متأكد من إلغاء هذا الاشتراك؟ لن تتمكن من التراجع عن هذا الإجراء.',
                'bg-red-600 text-white hover:bg-red-700',
                () => {
                    const form = document.createElement('form');
                    form.method = 'POST';
                    form.action = cancelSubscriptionUrl.replace('__TYPE__', type).replace('__ID__', id);
                    form.innerHTML = `
                        <input type="hidden" name="_token" value="{{ csrf_token() }}">
                        <input type="hidden" name="_method" value="PATCH">
                    `;
                    document.body.appendChild(form);
                    form.submit();
                }
            );
        }

        function showModal(iconBg, iconClass, title, message, confirmBtnClass, onConfirm) {
            const modal = document.getElementById('confirmModal');
            const iconEl = document.getElementById('modalIcon');
            const titleEl = document.getElementById('modalTitle');
            const messageEl = document.getElementById('modalMessage');
            const confirmBtn = document.getElementById('modalConfirmBtn');

            iconEl.className = `w-16 h-16 mx-auto mb-4 rounded-full flex items-center justify-center ${iconBg}`;
            iconEl.innerHTML = `<i class="${iconClass}"></i>`;
            titleEl.textContent = title;
            messageEl.textContent = message;
            confirmBtn.className = `min-h-[48px] md:min-h-[44px] flex-1 px-4 py-2.5 rounded-xl md:rounded-lg font-medium transition-colors ${confirmBtnClass}`;
            confirmBtn.onclick = () => {
                closeModal();
                onConfirm();
            };

            // Show modal using Alpine.js
            modal.style.display = 'block';
            window.dispatchEvent(new CustomEvent('open-confirm-modal'));
            document.body.style.overflow = 'hidden';
        }

        function closeModal() {
            const modal = document.getElementById('confirmModal');
            window.dispatchEvent(new CustomEvent('close-confirm-modal'));
            document.body.style.overflow = '';
            setTimeout(() => {
                modal.style.display = 'none';
            }, 200);
        }
    </script>
    @endpush

</x-layouts.authenticated>
