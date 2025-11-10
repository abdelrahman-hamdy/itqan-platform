@props(['progressSummary'])

<div class="bg-white rounded-xl shadow-sm border border-gray-200 p-6">
    <h3 class="text-xl font-bold text-gray-900 mb-6">
        <i class="ri-line-chart-line text-primary ml-2"></i>
        ملخص التقدم الأكاديمي
    </h3>

    <!-- Progress Status Alert -->
    @if($progressSummary['needs_support'] ?? false)
        <div class="bg-orange-50 border border-orange-200 text-orange-800 px-4 py-3 rounded-lg mb-6">
            <div class="flex items-start">
                <i class="ri-alert-line text-orange-600 mt-0.5 ml-2"></i>
                <div>
                    <div class="font-medium">تحتاج إلى دعم إضافي</div>
                    <div class="text-sm mt-1">يرجى التواصل مع المعلم لمناقشة التحسينات المطلوبة</div>
                </div>
            </div>
        </div>
    @endif

    <!-- Progress Metrics Grid -->
    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">

        <!-- Attendance Rate -->
        <div class="space-y-2">
            <div class="flex items-center justify-between">
                <span class="text-sm font-medium text-gray-700">معدل الحضور</span>
                <span class="text-lg font-bold {{ $progressSummary['attendance_rate'] >= 80 ? 'text-green-600' : ($progressSummary['attendance_rate'] >= 60 ? 'text-yellow-600' : 'text-red-600') }}">
                    {{ $progressSummary['attendance_rate'] }}%
                </span>
            </div>
            <div class="w-full bg-gray-200 rounded-full h-2">
                <div class="h-2 rounded-full {{ $progressSummary['attendance_rate'] >= 80 ? 'bg-green-500' : ($progressSummary['attendance_rate'] >= 60 ? 'bg-yellow-500' : 'bg-red-500') }}"
                     style="width: {{ $progressSummary['attendance_rate'] }}%"></div>
            </div>
            <div class="text-xs text-gray-600">
                {{ $progressSummary['sessions_completed'] }} من {{ $progressSummary['sessions_planned'] }} جلسة حضرتها
            </div>
        </div>

        <!-- Homework Completion -->
        @if($progressSummary['total_assignments'] > 0)
        <div class="space-y-2">
            <div class="flex items-center justify-between">
                <span class="text-sm font-medium text-gray-700">إتمام الواجبات</span>
                <span class="text-lg font-bold {{ $progressSummary['homework_completion_rate'] >= 80 ? 'text-green-600' : ($progressSummary['homework_completion_rate'] >= 60 ? 'text-yellow-600' : 'text-red-600') }}">
                    {{ $progressSummary['homework_completion_rate'] }}%
                </span>
            </div>
            <div class="w-full bg-gray-200 rounded-full h-2">
                <div class="h-2 rounded-full {{ $progressSummary['homework_completion_rate'] >= 80 ? 'bg-green-500' : ($progressSummary['homework_completion_rate'] >= 60 ? 'bg-yellow-500' : 'bg-red-500') }}"
                     style="width: {{ $progressSummary['homework_completion_rate'] }}%"></div>
            </div>
            <div class="text-xs text-gray-600">
                {{ $progressSummary['completed_assignments'] }} من {{ $progressSummary['total_assignments'] }} واجب مكتمل
            </div>
        </div>
        @else
        <div class="space-y-2">
            <div class="flex items-center justify-between">
                <span class="text-sm font-medium text-gray-700">الواجبات</span>
                <span class="text-sm text-gray-500">لا توجد واجبات بعد</span>
            </div>
        </div>
        @endif

        <!-- Overall Grade -->
        @if($progressSummary['overall_grade'])
        <div class="space-y-2">
            <div class="flex items-center justify-between">
                <span class="text-sm font-medium text-gray-700">الدرجة الإجمالية</span>
                <span class="text-2xl font-bold {{ $progressSummary['overall_grade'] >= 80 ? 'text-green-600' : ($progressSummary['overall_grade'] >= 60 ? 'text-yellow-600' : 'text-red-600') }}">
                    {{ $progressSummary['overall_grade'] }}
                </span>
            </div>
            <div class="w-full bg-gray-200 rounded-full h-2">
                <div class="h-2 rounded-full {{ $progressSummary['overall_grade'] >= 80 ? 'bg-green-500' : ($progressSummary['overall_grade'] >= 60 ? 'bg-yellow-500' : 'bg-red-500') }}"
                     style="width: {{ $progressSummary['overall_grade'] }}%"></div>
            </div>
        </div>
        @endif

        <!-- Progress Status -->
        <div class="space-y-2">
            <div class="flex items-center justify-between">
                <span class="text-sm font-medium text-gray-700">حالة التقدم</span>
                <span class="inline-flex items-center px-3 py-1 rounded-full text-sm font-medium {{ $progressSummary['needs_support'] ? 'bg-red-100 text-red-800' : 'bg-green-100 text-green-800' }}">
                    {{ $progressSummary['progress_status'] }}
                </span>
            </div>
            <div class="text-xs text-gray-600">
                مستوى التفاعل: {{ $progressSummary['engagement_level'] }}
            </div>
        </div>

    </div>

    <!-- Session Information -->
    <div class="mt-6 pt-6 border-t border-gray-200 grid grid-cols-2 gap-4">
        <div class="flex items-center">
            <i class="ri-calendar-check-line text-gray-400 ml-2"></i>
            <div>
                <div class="text-xs text-gray-500">آخر جلسة</div>
                <div class="text-sm font-medium text-gray-900">
                    {{ $progressSummary['last_session'] ? \Carbon\Carbon::parse($progressSummary['last_session'])->format('Y/m/d') : 'لا توجد' }}
                </div>
            </div>
        </div>
        <div class="flex items-center">
            <i class="ri-calendar-line text-gray-400 ml-2"></i>
            <div>
                <div class="text-xs text-gray-500">الجلسة القادمة</div>
                <div class="text-sm font-medium text-gray-900">
                    {{ $progressSummary['next_session'] ? \Carbon\Carbon::parse($progressSummary['next_session'])->format('Y/m/d') : 'لم تحدد بعد' }}
                </div>
            </div>
        </div>
    </div>

    <!-- Warning Messages -->
    @if($progressSummary['consecutive_missed'] >= 2)
        <div class="mt-4 bg-yellow-50 border border-yellow-200 text-yellow-800 px-4 py-3 rounded-lg">
            <div class="flex items-start">
                <i class="ri-error-warning-line text-yellow-600 mt-0.5 ml-2"></i>
                <div class="text-sm">
                    <strong>تنبيه:</strong> لديك {{ $progressSummary['consecutive_missed'] }} جلسات متتالية غائب عنها. حاول الحضور بانتظام لتحسين أدائك.
                </div>
            </div>
        </div>
    @endif

    @if($progressSummary['sessions_missed'] > $progressSummary['sessions_completed'] && $progressSummary['sessions_planned'] > 5)
        <div class="mt-4 bg-red-50 border border-red-200 text-red-800 px-4 py-3 rounded-lg">
            <div class="flex items-start">
                <i class="ri-alert-line text-red-600 mt-0.5 ml-2"></i>
                <div class="text-sm">
                    <strong>تحذير:</strong> عدد الجلسات المتغيب عنها أكبر من الجلسات المكتملة. يرجى التواصل مع المعلم.
                </div>
            </div>
        </div>
    @endif
</div>
