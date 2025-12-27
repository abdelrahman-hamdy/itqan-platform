@props([
    'progress', // Progress statistics array
    'title' => 'التقدم في الحفظ',
    'showLifetime' => false
])

@php
    // Current subscription progress
    $pagesMemorized = $progress['pages_memorized'] ?? 0;
    $papersMemorized = $progress['papers_memorized'] ?? 0;
    $progressPercentage = $progress['progress_percentage'] ?? 0;

    // Lifetime progress (if applicable)
    $lifetimePagesMemorized = $progress['lifetime_pages_memorized'] ?? null;
    $lifetimeSessionsCompleted = $progress['lifetime_sessions_completed'] ?? null;

    // Calculate Quran completion percentage (use config for total pages)
    $totalQuranPages = config('quran.total_pages', 604);
    $quranCompletionPercentage = $pagesMemorized > 0 ? min(100, round(($pagesMemorized / $totalQuranPages) * 100, 1)) : 0;
@endphp

<div class="bg-white rounded-xl shadow-sm border border-gray-200 p-6">
    <h2 class="text-lg font-bold text-gray-900 mb-4">{{ $title }}</h2>

    <!-- Pages Progress -->
    <div class="mb-6">
        <div class="flex items-center justify-center mb-4">
            <div class="text-center">
                <div class="text-5xl font-bold text-primary mb-2">{{ number_format($pagesMemorized, 1) }}</div>
                <div class="text-sm text-gray-600">صفحة محفوظة</div>
                <div class="text-xs text-gray-500 mt-1">({{ $papersMemorized }} وجه)</div>
            </div>
        </div>

        <!-- Quran Completion Progress Bar -->
        <div class="mb-2">
            <div class="flex justify-between items-center mb-1">
                <span class="text-xs font-medium text-gray-700">نسبة إتمام القرآن</span>
                <span class="text-xs font-bold text-primary">{{ $quranCompletionPercentage }}%</span>
            </div>
            <div class="w-full bg-gray-200 rounded-full h-2">
                <div class="bg-gradient-to-r from-primary to-green-500 h-2 rounded-full transition-all duration-500"
                     style="width: {{ $quranCompletionPercentage }}%"></div>
            </div>
            <div class="flex justify-between mt-1 text-xs text-gray-500">
                <span>{{ number_format($pagesMemorized, 1) }} من {{ $totalQuranPages }} صفحة</span>
            </div>
        </div>
    </div>

    <!-- Subscription Progress (if available) -->
    @if($progressPercentage > 0)
        <div class="mb-4">
            <div class="flex justify-between items-center mb-2">
                <span class="text-sm font-medium text-gray-700">تقدم الاشتراك الحالي</span>
                <span class="text-sm font-bold text-blue-600">{{ $progressPercentage }}%</span>
            </div>
            <div class="w-full bg-gray-200 rounded-full h-2">
                <div class="bg-blue-500 h-2 rounded-full transition-all duration-500"
                     style="width: {{ $progressPercentage }}%"></div>
            </div>
        </div>
    @endif

    <!-- Lifetime Stats (if available and enabled) -->
    @if($showLifetime && $lifetimePagesMemorized !== null)
        <div class="border-t border-gray-200 pt-4 mt-4">
            <h3 class="text-sm font-bold text-gray-800 mb-3">الإحصائيات الإجمالية</h3>
            <div class="grid grid-cols-2 gap-3">
                <div class="text-center p-3 bg-purple-50 rounded-lg border border-purple-200">
                    <div class="text-xl font-bold text-purple-600">{{ number_format($lifetimePagesMemorized, 1) }}</div>
                    <div class="text-xs text-purple-700 font-medium">إجمالي الصفحات</div>
                </div>
                @if($lifetimeSessionsCompleted !== null)
                    <div class="text-center p-3 bg-blue-50 rounded-lg border border-blue-200">
                        <div class="text-xl font-bold text-blue-600">{{ $lifetimeSessionsCompleted }}</div>
                        <div class="text-xs text-blue-700 font-medium">إجمالي الجلسات</div>
                    </div>
                @endif
            </div>
        </div>
    @endif

    <!-- Additional Progress Details -->
    @if(isset($progress['average_pages_per_session']) && $progress['average_pages_per_session'] > 0)
        <div class="mt-4 p-3 bg-green-50 rounded-lg border border-green-200">
            <div class="flex items-center justify-between">
                <div class="flex items-center">
                    <i class="ri-line-chart-line text-green-600 ml-2"></i>
                    <span class="text-sm text-green-800">متوسط الحفظ بالجلسة</span>
                </div>
                <span class="text-sm font-bold text-green-700">
                    {{ number_format($progress['average_pages_per_session'], 2) }} صفحة
                </span>
            </div>
        </div>
    @endif
</div>
