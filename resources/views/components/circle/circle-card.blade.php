@props([
    'circle',
    'type' => 'group', // 'group', 'individual', or 'trial'
    'context' => 'quran', // 'quran' or 'academic'
    'isEnrolled' => false,
    'showActions' => true
])

@php
    $isGroup = $type === 'group';
    $isIndividual = $type === 'individual';
    $isTrial = $type === 'trial';
    $isAcademic = $context === 'academic';

    // Calculate availability for group circles
    if ($isGroup) {
        $isAvailable = !$isEnrolled && $circle->enrollment_status === 'open' && $circle->enrolled_students < $circle->max_students;
        $isFull = $circle->enrollment_status === 'full' || $circle->enrolled_students >= $circle->max_students;
    }

    // Get teacher based on context
    $teacher = null;
    if ($isGroup) {
        $teacher = $circle->quranTeacher;
    } elseif ($isIndividual || $isTrial) {
        $teacher = $isAcademic ? ($circle->teacher ?? null) : ($circle->quranTeacher ?? null);
    }

    // Determine status text and color
    $statusText = '';
    $statusClass = '';

    if ($isGroup) {
        if ($isEnrolled) {
            $statusText = 'نشط';
            $statusClass = 'bg-secondary-100 text-secondary-600';
        } elseif ($isAvailable) {
            $statusText = 'متاح للتسجيل';
            $statusClass = 'bg-primary-100 text-primary-600';
        } elseif ($isFull) {
            $statusText = 'مكتمل';
            $statusClass = 'bg-red-100 text-red-800';
        } else {
            $statusText = 'مغلق';
            $statusClass = 'bg-gray-100 text-gray-800';
        }
    } else {
        // Individual/Trial circles
        $statusText = $circle->status ? 'نشط' : 'غير نشط';
        $statusClass = $circle->status ? 'bg-green-100 text-green-800' : 'bg-gray-100 text-gray-800';
    }

    // Get circle title
    $circleTitle = '';
    if ($isGroup) {
        $circleTitle = $circle->name ?? $circle->name_ar ?? $circle->name_en;
    } elseif ($isIndividual) {
        if ($isAcademic) {
            $circleTitle = $circle->subject->name ?? $circle->subject_name ?? 'درس خاص';
        } else {
            $circleTitle = $circle->subscription->package->name ?? 'حلقة فردية';
        }
    } elseif ($isTrial) {
        $circleTitle = 'جلسة تجريبية';
    }

    // Get description
    $circleDescription = '';
    if ($isGroup) {
        $circleDescription = $circle->description ?? $circle->description_ar ?? $circle->description_en ?? '';
    } elseif ($isIndividual) {
        $circleDescription = $isAcademic
            ? 'درس خاص في ' . ($circle->subject->name ?? 'المادة الأكاديمية')
            : 'حلقة فردية لتعليم القرآن الكريم';
    } elseif ($isTrial) {
        $circleDescription = 'جلسة تجريبية لتقييم مستوى الطالب';
    }

    // Subdomain helper
    $subdomain = auth()->user()->academy->subdomain ?? 'itqan-academy';
@endphp

<div class="bg-white rounded-xl shadow-sm border border-gray-200 p-6 card-hover">
    <!-- Card Header -->
    <div class="flex items-start justify-between mb-4">
        <div class="w-14 h-14 {{ $isEnrolled ? 'bg-gradient-to-br from-secondary-100 to-secondary-200' : 'bg-gradient-to-br from-primary-100 to-primary-200' }} rounded-xl flex items-center justify-center shadow-sm">
            <i class="{{ $isEnrolled ? 'ri-bookmark-fill text-secondary-600' : 'ri-book-mark-line text-primary-600' }} text-2xl"></i>
        </div>
        <span class="inline-flex items-center px-3 py-1 rounded-full text-xs font-semibold shadow-sm {{ $statusClass }}">
            {{ $statusText }}
        </span>
    </div>

    <!-- Circle Info -->
    <div class="mb-4">
        <h3 class="font-bold text-gray-900 mb-2 text-lg leading-tight">{{ $circleTitle }}</h3>
        <p class="text-sm text-gray-600 line-clamp-2 leading-relaxed">{{ $circleDescription }}</p>
    </div>

    <!-- Details Grid -->
    <div class="space-y-3 mb-6 bg-gray-50 rounded-lg p-4">
        <!-- Teacher -->
        @if($teacher)
        <div class="flex items-center text-sm">
            <div class="w-8 h-8 bg-white rounded-lg flex items-center justify-center ml-3 shadow-sm">
                <i class="ri-user-star-line text-primary"></i>
            </div>
            <div class="flex-1 min-w-0">
                <p class="text-xs text-gray-500 mb-0.5">المعلم</p>
                <p class="font-semibold text-gray-900 truncate">{{ $teacher->full_name ?? $teacher->name ?? 'معلم' }}</p>
            </div>
        </div>
        @endif

        <!-- Students Count (Group circles only) -->
        @if($isGroup && isset($circle->students_count))
        <div class="flex items-center text-sm">
            <div class="w-8 h-8 bg-white rounded-lg flex items-center justify-center ml-3 shadow-sm">
                <i class="ri-group-line text-primary"></i>
            </div>
            <div class="flex-1">
                <p class="text-xs text-gray-500 mb-0.5">عدد الطلاب</p>
                <div class="flex items-center">
                    <p class="font-semibold text-gray-900">{{ $circle->students_count }}/{{ $circle->max_students }}</p>
                    <div class="flex-1 bg-gray-200 rounded-full h-1.5 mr-3">
                        <div class="bg-primary h-1.5 rounded-full transition-all"
                             style="width: {{ $circle->max_students > 0 ? ($circle->students_count / $circle->max_students * 100) : 0 }}%"></div>
                    </div>
                </div>
            </div>
        </div>
        @endif

        <!-- Session Progress (Individual/Trial) -->
        @if(($isIndividual || $isTrial) && isset($circle->subscription))
        <div class="flex items-center text-sm">
            <div class="w-8 h-8 bg-white rounded-lg flex items-center justify-center ml-3 shadow-sm">
                <i class="ri-calendar-check-line text-primary"></i>
            </div>
            <div class="flex-1">
                <p class="text-xs text-gray-500 mb-0.5">التقدم</p>
                <p class="font-semibold text-gray-900">{{ $circle->subscription->sessions_used ?? 0 }}/{{ $circle->subscription->total_sessions ?? 0 }} جلسة</p>
            </div>
        </div>
        @endif

        <!-- Schedule (Group circles) -->
        @if($isGroup && !empty($circle->schedule_days_text))
        <div class="flex items-center text-sm">
            <div class="w-8 h-8 bg-white rounded-lg flex items-center justify-center ml-3 shadow-sm">
                <i class="ri-calendar-line text-primary"></i>
            </div>
            <div class="flex-1 min-w-0">
                <p class="text-xs text-gray-500 mb-0.5">مواعيد الحلقة</p>
                <p class="font-semibold text-gray-900 truncate">{{ $circle->schedule_days_text }}</p>
            </div>
        </div>
        @endif

        <!-- Memorization Level (Group Quran circles) -->
        @if($isGroup && !$isAcademic && !empty($circle->memorization_level))
        <div class="flex items-center text-sm">
            <div class="w-8 h-8 bg-white rounded-lg flex items-center justify-center ml-3 shadow-sm">
                <i class="ri-bar-chart-line text-primary"></i>
            </div>
            <div class="flex-1">
                <p class="text-xs text-gray-500 mb-0.5">مستوى الحفظ</p>
                <p class="font-semibold text-gray-900">{{ $circle->memorization_level_text ?? $circle->memorization_level }}</p>
            </div>
        </div>
        @endif

        <!-- Monthly Fee (Group circles) -->
        @if($isGroup && !empty($circle->monthly_fee))
        <div class="flex items-center text-sm">
            <div class="w-8 h-8 bg-white rounded-lg flex items-center justify-center ml-3 shadow-sm">
                <i class="ri-money-dollar-circle-line text-primary"></i>
            </div>
            <div class="flex-1">
                <p class="text-xs text-gray-500 mb-0.5">الرسوم الشهرية</p>
                <p class="font-bold text-primary">{{ number_format($circle->monthly_fee, 2) }} ر.س</p>
            </div>
        </div>
        @endif
    </div>

    <!-- Action Button -->
    @if($showActions)
        @if($isGroup)
            @if($isEnrolled)
                <a href="{{ route('student.circles.show', ['subdomain' => $subdomain, 'circleId' => $circle->id]) }}"
                   class="block w-full bg-secondary text-white px-4 py-2.5 rounded-lg text-sm font-semibold hover:bg-secondary-600 transition-colors text-center">
                    <i class="ri-eye-line ml-1"></i>
                    عرض الحلقة
                </a>
            @else
                <a href="{{ route('student.circles.show', ['subdomain' => $subdomain, 'circleId' => $circle->id]) }}"
                   class="block w-full bg-primary text-white px-4 py-2.5 rounded-lg text-sm font-semibold hover:bg-primary-600 transition-colors text-center">
                    <i class="ri-information-line ml-1"></i>
                    عرض التفاصيل
                </a>
            @endif
        @elseif($isIndividual)
            <a href="{{ route('student.individual-circles.show', ['subdomain' => $subdomain, 'circle' => $circle->id]) }}"
               class="block w-full bg-primary text-white px-4 py-2.5 rounded-lg text-sm font-semibold hover:bg-primary-600 transition-colors text-center">
                <i class="ri-eye-line ml-1"></i>
                عرض الحلقة
            </a>
        @elseif($isTrial)
            <a href="{{ route('student.sessions.show', ['subdomain' => $subdomain, 'sessionId' => $circle->id]) }}"
               class="block w-full bg-primary text-white px-4 py-2.5 rounded-lg text-sm font-semibold hover:bg-primary-600 transition-colors text-center">
                <i class="ri-eye-line ml-1"></i>
                عرض الجلسة
            </a>
        @endif
    @endif
</div>
