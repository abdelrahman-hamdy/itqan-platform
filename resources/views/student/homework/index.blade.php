@php
    $layoutComponent = ($layout ?? 'student') === 'parent' ? 'layouts.parent-layout' : 'layouts.student-layout';
    $isParentView = ($layout ?? 'student') === 'parent';
    $pageTitle = $isParentView ? 'واجبات الأبناء' : 'واجباتي';
    $pageDescription = $isParentView
        ? 'عرض جميع واجبات أبنائك الدراسية'
        : 'عرض وإدارة جميع الواجبات الدراسية (أكاديمي + قرآن + دورات تفاعلية)';
@endphp

<x-dynamic-component :component="$layoutComponent" :title="$pageTitle">
    <div class="space-y-6">
        <!-- Page Header -->
        <div>
            <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
                <div>
                    <h1 class="text-xl sm:text-2xl md:text-3xl font-bold text-gray-900 flex items-center">
                        <i class="ri-book-2-line text-blue-600 ml-2 md:ml-3"></i>
                        {{ $pageTitle }}
                    </h1>
                    <p class="text-sm md:text-base text-gray-600 mt-1 md:mt-2">{{ $pageDescription }}</p>
                </div>
            </div>
        </div>

        <!-- Urgent Homework Alert -->
        @if(isset($homework))
        @php
            $urgentHomework = collect($homework)->filter(function($hw) {
                return isset($hw['hours_until_due'])
                    && $hw['hours_until_due'] !== null
                    && $hw['hours_until_due'] > 0
                    && $hw['hours_until_due'] <= 24
                    && !in_array($hw['submission_status'] ?? $hw['status'], ['submitted', 'late', 'graded', 'returned']);
            });
        @endphp
        @if($urgentHomework->isNotEmpty())
        <div class="bg-gradient-to-r from-orange-500 to-red-600 rounded-xl shadow-md p-6 text-white mb-6">
            <div class="flex items-center justify-between flex-wrap gap-4">
                <div class="flex-1">
                    <p class="text-orange-100 text-sm font-medium">⚠️ واجبات عاجلة (تنتهي خلال 24 ساعة)</p>
                    <p class="text-4xl font-bold mt-2">{{ $urgentHomework->count() }}</p>
                    <div class="mt-3 space-y-1">
                        @foreach($urgentHomework->take(3) as $urgent)
                        <p class="text-sm text-orange-100">
                            • {{ $urgent['title'] }}
                            <span class="font-medium">({{ round($urgent['hours_until_due']) }} ساعة)</span>
                        </p>
                        @endforeach
                    </div>
                </div>
                <div class="bg-white bg-opacity-20 rounded-full p-4">
                    <i class="ri-alarm-warning-line text-5xl"></i>
                </div>
            </div>
        </div>
        @endif
        @endif

        <!-- Statistics Cards -->
        @if(isset($statistics))
        <div class="grid grid-cols-2 lg:grid-cols-4 gap-3 md:gap-6 mb-6 md:mb-8">
            <div class="bg-gradient-to-r from-blue-500 to-blue-600 rounded-xl shadow-md p-4 md:p-6 text-white">
                <div class="flex items-center justify-between">
                    <div class="min-w-0 flex-1">
                        <p class="text-blue-100 text-xs md:text-sm font-medium">إجمالي الواجبات</p>
                        <p class="text-2xl md:text-4xl font-bold mt-1 md:mt-2">{{ $statistics['total'] }}</p>
                        @if(isset($statistics['type_breakdown']))
                        <p class="text-xs text-blue-100 mt-1 hidden md:block">
                            {{ $statistics['type_breakdown']['academic'] }} أكاديمي •
                            {{ $statistics['type_breakdown']['quran'] }} قرآن •
                            {{ $statistics['type_breakdown']['interactive'] }} تفاعلي
                        </p>
                        @endif
                    </div>
                    <div class="bg-white bg-opacity-20 rounded-full p-2 md:p-4 flex-shrink-0">
                        <i class="ri-file-list-line text-xl md:text-3xl"></i>
                    </div>
                </div>
            </div>

            <div class="bg-gradient-to-r from-yellow-500 to-yellow-600 rounded-xl shadow-md p-4 md:p-6 text-white">
                <div class="flex items-center justify-between">
                    <div class="min-w-0 flex-1">
                        <p class="text-yellow-100 text-xs md:text-sm font-medium">قيد الانتظار</p>
                        <p class="text-2xl md:text-4xl font-bold mt-1 md:mt-2">{{ $statistics['pending'] }}</p>
                        @if(isset($statistics['overdue']) && $statistics['overdue'] > 0)
                        <p class="text-xs md:text-sm text-yellow-100 mt-1">
                            <i class="ri-error-warning-line"></i>
                            {{ $statistics['overdue'] }} متأخر
                        </p>
                        @endif
                    </div>
                    <div class="bg-white bg-opacity-20 rounded-full p-2 md:p-4 flex-shrink-0">
                        <i class="ri-time-line text-xl md:text-3xl"></i>
                    </div>
                </div>
            </div>

            <div class="bg-gradient-to-r from-green-500 to-green-600 rounded-xl shadow-md p-4 md:p-6 text-white">
                <div class="flex items-center justify-between">
                    <div class="min-w-0 flex-1">
                        <p class="text-green-100 text-xs md:text-sm font-medium">تم التسليم</p>
                        <p class="text-2xl md:text-4xl font-bold mt-1 md:mt-2">{{ $statistics['submitted'] + $statistics['graded'] }}</p>
                        @if(isset($statistics['completion_rate']))
                        <p class="text-xs md:text-sm text-green-100 mt-1 hidden sm:block">{{ $statistics['completion_rate'] }}% نسبة الإنجاز</p>
                        @endif
                    </div>
                    <div class="bg-white bg-opacity-20 rounded-full p-2 md:p-4 flex-shrink-0">
                        <i class="ri-check-double-line text-xl md:text-3xl"></i>
                    </div>
                </div>
            </div>

            <div class="bg-gradient-to-r from-purple-500 to-purple-600 rounded-xl shadow-md p-4 md:p-6 text-white">
                <div class="flex items-center justify-between">
                    <div class="min-w-0 flex-1">
                        <p class="text-purple-100 text-xs md:text-sm font-medium">المعدل</p>
                        <p class="text-2xl md:text-4xl font-bold mt-1 md:mt-2">{{ isset($statistics['average_score']) ? number_format($statistics['average_score'], 1) : '0' }}%</p>
                        <p class="text-xs md:text-sm text-purple-100 mt-1 hidden sm:block">{{ $statistics['graded'] }} مقيّم</p>
                    </div>
                    <div class="bg-white bg-opacity-20 rounded-full p-2 md:p-4 flex-shrink-0">
                        <i class="ri-star-line text-xl md:text-3xl"></i>
                    </div>
                </div>
            </div>
        </div>
        @endif

        <!-- Filters -->
        <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-4 md:p-6 mb-6 md:mb-8">
            @php
                $filterRoute = $isParentView
                    ? route('parent.homework.index', ['subdomain' => auth()->user()->academy->subdomain ?? 'itqan-academy'])
                    : route('student.homework.index', ['subdomain' => auth()->user()->academy->subdomain ?? 'itqan-academy']);
            @endphp
            <form method="GET" action="{{ $filterRoute }}" class="space-y-4">
                <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                    <div>
                        <label for="status" class="block text-sm font-medium text-gray-700 mb-2">الحالة</label>
                        <select name="status" id="status" class="w-full border border-gray-300 rounded-lg px-4 py-2.5 min-h-[44px] focus:ring-2 focus:ring-blue-500">
                            <option value="">جميع الحالات</option>
                            <option value="pending" {{ request('status') === 'pending' ? 'selected' : '' }}>قيد الانتظار</option>
                            <option value="submitted" {{ request('status') === 'submitted' ? 'selected' : '' }}>تم التسليم</option>
                            <option value="graded" {{ request('status') === 'graded' ? 'selected' : '' }}>تم التصحيح</option>
                            <option value="overdue" {{ request('status') === 'overdue' ? 'selected' : '' }}>متأخر</option>
                            <option value="late" {{ request('status') === 'late' ? 'selected' : '' }}>تسليم متأخر</option>
                        </select>
                    </div>

                    <div>
                        <label for="type" class="block text-sm font-medium text-gray-700 mb-2">نوع الواجب</label>
                        <select name="type" id="type" class="w-full border border-gray-300 rounded-lg px-4 py-2.5 min-h-[44px] focus:ring-2 focus:ring-blue-500">
                            <option value="">جميع الأنواع</option>
                            <option value="academic" {{ request('type') === 'academic' ? 'selected' : '' }}>أكاديمي</option>
                            <option value="quran" {{ request('type') === 'quran' ? 'selected' : '' }}>قرآن</option>
                            <option value="interactive" {{ request('type') === 'interactive' ? 'selected' : '' }}>دورة تفاعلية</option>
                        </select>
                    </div>
                </div>

                <div class="flex flex-col sm:flex-row items-stretch sm:items-center gap-3 pt-2">
                    <button type="submit" class="inline-flex items-center justify-center min-h-[44px] bg-blue-600 hover:bg-blue-700 text-white px-6 py-2 rounded-xl md:rounded-lg transition-colors">
                        <i class="ri-filter-line ml-1"></i>
                        تصفية
                    </button>
                    @if(request()->hasAny(['status', 'type']))
                    <a href="{{ $filterRoute }}"
                       class="inline-flex items-center justify-center min-h-[44px] bg-gray-100 hover:bg-gray-200 text-gray-700 px-6 py-2 rounded-xl md:rounded-lg transition-colors">
                        <i class="ri-close-line ml-1"></i>
                        إلغاء الفلاتر
                    </a>
                    @endif
                </div>
            </form>
        </div>

        <!-- Homework List -->
        @if(isset($homework) && count($homework) > 0)
        <div class="space-y-4">
            @foreach($homework as $hw)
            <div class="bg-white rounded-xl shadow-sm border border-gray-200 hover:shadow-md transition-shadow">
                <div class="p-4 md:p-6">
                    <div class="flex flex-col sm:flex-row sm:items-start sm:justify-between gap-4">
                        <div class="flex-1">
                            <!-- Type & Status Badges -->
                            <div class="flex items-center gap-3 mb-3 flex-wrap">
                                @if($hw['type'] === 'academic')
                                <span class="inline-flex items-center px-3 py-1 rounded-full text-xs font-medium bg-blue-100 text-blue-800">
                                    <i class="ri-book-line ml-1"></i>
                                    أكاديمي
                                </span>
                                @elseif($hw['type'] === 'quran')
                                <span class="inline-flex items-center px-3 py-1 rounded-full text-xs font-medium bg-green-100 text-green-800">
                                    <i class="ri-book-read-line ml-1"></i>
                                    قرآن كريم
                                </span>
                                @else
                                <span class="inline-flex items-center px-3 py-1 rounded-full text-xs font-medium bg-purple-100 text-purple-800">
                                    <i class="ri-live-line ml-1"></i>
                                    دورة تفاعلية
                                </span>
                                @endif

                                <!-- Status Badge -->
                                @php
                                $status = $hw['submission_status'] ?? $hw['status'];
                                $statusColors = [
                                    'not_started' => 'bg-gray-100 text-gray-800',
                                    'not_submitted' => 'bg-gray-100 text-gray-800',
                                    'draft' => 'bg-yellow-100 text-yellow-800',
                                    'submitted' => 'bg-blue-100 text-blue-800',
                                    'late' => 'bg-red-100 text-red-800',
                                    'graded' => 'bg-green-100 text-green-800',
                                    'returned' => 'bg-purple-100 text-purple-800',
                                    'resubmitted' => 'bg-indigo-100 text-indigo-800',
                                    'pending' => 'bg-gray-100 text-gray-800',
                                ];
                                $statusColor = $statusColors[$status] ?? 'bg-gray-100 text-gray-800';
                                @endphp

                                <span class="inline-flex items-center px-3 py-1 rounded-full text-xs font-medium {{ $statusColor }}">
                                    {{ $hw['submission_status_text'] ?? $hw['status_text'] }}
                                </span>

                                <!-- View-Only Badge (for Quran homework) -->
                                @if(isset($hw['is_view_only']) && $hw['is_view_only'])
                                <span class="inline-flex items-center px-2 py-1 rounded text-xs font-medium bg-green-50 text-green-700 border border-green-200">
                                    <i class="ri-eye-line ml-1"></i>
                                    عرض فقط
                                </span>
                                @endif

                                <!-- Late Badge -->
                                @if($hw['is_late'])
                                <span class="inline-flex items-center px-2 py-1 rounded text-xs font-medium bg-red-100 text-red-800">
                                    <i class="ri-error-warning-line ml-1"></i>
                                    متأخر {{ $hw['days_late'] }} {{ $hw['days_late'] == 1 ? 'يوم' : 'أيام' }}
                                </span>
                                @endif

                                <!-- Deadline Warning -->
                                @if(isset($hw['hours_until_due']) && $hw['hours_until_due'] !== null && !in_array($status, ['submitted', 'late', 'graded', 'returned']))
                                    @if($hw['hours_until_due'] < 0 && !$hw['is_late'])
                                    <span class="inline-flex items-center px-2 py-1 rounded text-xs font-medium bg-red-100 text-red-800">
                                        <i class="ri-alert-line ml-1"></i>
                                        متأخر
                                    </span>
                                    @elseif($hw['hours_until_due'] > 0 && $hw['hours_until_due'] <= 24)
                                    <span class="inline-flex items-center px-2 py-1 rounded text-xs font-medium bg-orange-100 text-orange-800">
                                        <i class="ri-alarm-warning-line ml-1"></i>
                                        ينتهي خلال {{ round($hw['hours_until_due']) }} ساعة
                                    </span>
                                    @endif
                                @endif
                            </div>

                            <!-- Title -->
                            <h3 class="text-lg font-bold text-gray-900 mb-2">{{ $hw['title'] }}</h3>

                            <!-- Description -->
                            @if($hw['description'])
                            <p class="text-sm text-gray-600 mb-3 line-clamp-2">{{ $hw['description'] }}</p>
                            @endif

                            <!-- Quran Homework Details -->
                            @if(isset($hw['homework_details']) && $hw['homework_details'])
                            <div class="mb-3 p-3 bg-green-50 rounded-lg border border-green-200">
                                <p class="text-xs font-medium text-green-900 mb-1">تفاصيل الواجب القرآني:</p>
                                @if($hw['homework_details']['has_new_memorization'])
                                <p class="text-xs text-green-800">
                                    • حفظ جديد: {{ $hw['homework_details']['new_memorization_pages'] }} {{ $hw['homework_details']['new_memorization_pages'] == 1 ? 'صفحة' : 'صفحات' }}
                                </p>
                                @endif
                                @if($hw['homework_details']['has_review'])
                                <p class="text-xs text-green-800">
                                    • مراجعة: {{ $hw['homework_details']['review_pages'] }} {{ $hw['homework_details']['review_pages'] == 1 ? 'صفحة' : 'صفحات' }}
                                </p>
                                @endif
                            </div>
                            @endif

                            <!-- Meta Info -->
                            <div class="flex flex-wrap items-center gap-4 text-sm text-gray-600">
                                @if($hw['due_date'])
                                <div class="flex items-center">
                                    <i class="ri-calendar-line ml-2"></i>
                                    <span>{{ \Carbon\Carbon::parse($hw['due_date'])->format('d/m/Y - h:i A') }}</span>
                                </div>
                                @endif

                                @if(isset($hw['teacher_name']) && $hw['teacher_name'])
                                <div class="flex items-center gap-2">
                                    <x-avatar
                                        :user="(object)[
                                            'name' => $hw['teacher_name'],
                                            'avatar' => $hw['teacher_avatar'] ?? null,
                                            'gender' => $hw['teacher_gender'] ?? 'male',
                                        ]"
                                        :userType="$hw['teacher_type'] ?? 'quran_teacher'"
                                        size="xs"
                                    />
                                    <span>{{ $hw['teacher_name'] }}</span>
                                </div>
                                @endif

                                @if(isset($hw['estimated_duration']) && $hw['estimated_duration'])
                                <div class="flex items-center">
                                    <i class="ri-time-line ml-2"></i>
                                    <span>{{ $hw['estimated_duration'] }} دقيقة</span>
                                </div>
                                @endif

                                @if(isset($hw['score_percentage']) && $hw['score_percentage'] !== null)
                                <div class="flex items-center font-medium text-green-600">
                                    <i class="ri-star-fill ml-2"></i>
                                    <span>{{ number_format($hw['score_percentage'], 1) }}%</span>
                                    @if(isset($hw['grade_letter']) && $hw['grade_letter'])
                                    <span class="mr-1">({{ $hw['grade_letter'] }})</span>
                                    @endif
                                </div>
                                @endif
                            </div>

                            <!-- Progress Bar (for drafts) -->
                            @if(isset($hw['progress_percentage']) && $hw['progress_percentage'] > 0 && $hw['progress_percentage'] < 100)
                            <div class="mt-3">
                                <div class="flex items-center justify-between text-xs text-gray-600 mb-1">
                                    <span>التقدم</span>
                                    <span>{{ $hw['progress_percentage'] }}%</span>
                                </div>
                                <div class="w-full bg-gray-200 rounded-full h-2">
                                    <div class="bg-blue-600 h-2 rounded-full" style="width: {{ $hw['progress_percentage'] }}%"></div>
                                </div>
                            </div>
                            @endif

                            <!-- Teacher Feedback -->
                            @if($hw['teacher_feedback'])
                            <div class="mt-4 p-3 bg-blue-50 rounded-lg border border-blue-200">
                                <p class="text-sm font-medium text-blue-900 mb-1 flex items-center">
                                    <i class="ri-feedback-line ml-1"></i>
                                    ملاحظات المعلم:
                                </p>
                                <p class="text-sm text-blue-800">{{ $hw['teacher_feedback'] }}</p>
                            </div>
                            @endif
                        </div>

                        <!-- Actions -->
                        <div class="flex flex-row sm:flex-col gap-2 sm:mr-4 flex-shrink-0">
                            @if(isset($hw['is_view_only']) && $hw['is_view_only'])
                                <!-- Quran homework: View session only -->
                                <a href="{{ $hw['view_url'] ?? '#' }}"
                                   class="inline-flex items-center justify-center min-h-[44px] px-4 py-2 bg-green-600 hover:bg-green-700 text-white text-sm font-medium rounded-xl md:rounded-lg transition-colors whitespace-nowrap flex-1 sm:flex-initial">
                                    <i class="ri-eye-line ml-1"></i>
                                    عرض الجلسة
                                </a>
                            @elseif(isset($hw['can_submit']) && $hw['can_submit'] && !$isParentView)
                                <!-- Can submit (students only, not parents) -->
                                <a href="{{ $hw['submit_url'] ?? route('student.homework.submit', ['subdomain' => auth()->user()->academy->subdomain ?? 'itqan-academy', 'id' => $hw['id'], 'type' => $hw['type']]) }}"
                                   class="inline-flex items-center justify-center min-h-[44px] px-4 py-2 bg-blue-600 hover:bg-blue-700 text-white text-sm font-medium rounded-xl md:rounded-lg transition-colors whitespace-nowrap flex-1 sm:flex-initial">
                                    <i class="ri-send-plane-line ml-1"></i>
                                    @if($status === 'draft')
                                        استكمال التسليم
                                    @else
                                        تسليم الواجب
                                    @endif
                                </a>
                            @else
                                <!-- View only -->
                                @php
                                    $viewRoute = $isParentView
                                        ? route('parent.homework.view', ['subdomain' => auth()->user()->academy->subdomain ?? 'itqan-academy', 'id' => $hw['id'], 'type' => $hw['type']])
                                        : route('student.homework.view', ['subdomain' => auth()->user()->academy->subdomain ?? 'itqan-academy', 'id' => $hw['id'], 'type' => $hw['type']]);
                                @endphp
                                <a href="{{ $hw['view_url'] ?? $viewRoute }}"
                                   class="inline-flex items-center justify-center min-h-[44px] px-4 py-2 bg-gray-200 hover:bg-gray-300 text-gray-700 text-sm font-medium rounded-xl md:rounded-lg transition-colors whitespace-nowrap flex-1 sm:flex-initial">
                                    <i class="ri-eye-line ml-1"></i>
                                    عرض التفاصيل
                                </a>
                            @endif

                            {{-- Show child name for parent view --}}
                            @if($isParentView && isset($hw['child_name']))
                            <span class="text-xs text-center text-purple-600 font-medium">
                                <i class="ri-user-line ml-1"></i>
                                {{ $hw['child_name'] }}
                            </span>
                            @endif
                        </div>
                    </div>
                </div>
            </div>
            @endforeach
        </div>
        @else
        <!-- Empty State -->
        <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-8 md:p-12 text-center">
            <div class="w-16 h-16 md:w-20 md:h-20 bg-gray-100 rounded-full flex items-center justify-center mx-auto mb-4">
                <i class="ri-inbox-line text-gray-400 text-3xl md:text-4xl"></i>
            </div>
            <h3 class="text-lg md:text-xl font-semibold text-gray-900 mb-2">لا توجد واجبات</h3>
            <p class="text-sm md:text-base text-gray-600">
                @if(request('status') || request('type'))
                    لم يتم العثور على واجبات تطابق المعايير المحددة.
                @elseif($isParentView)
                    لم يتم تعيين أي واجبات لأبنائك بعد.
                @else
                    لم يتم تعيين أي واجبات لك بعد.
                @endif
            </p>
        </div>
        @endif
    </div>
</x-dynamic-component>
