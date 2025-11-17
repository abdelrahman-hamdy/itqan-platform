@props([
    'performance', // Performance statistics array
    'title' => 'الأداء الأكاديمي'
])

@php
    $averageMemorization = $performance['average_memorization_degree'] ?? 0;
    $averageReservation = $performance['average_reservation_degree'] ?? 0;
    $averageOverall = $performance['average_overall_performance'] ?? 0;

    // Get color based on performance
    $getColorClass = function($degree) {
        if ($degree >= 8) return 'text-green-600';
        if ($degree >= 6) return 'text-blue-600';
        if ($degree >= 4) return 'text-yellow-600';
        return 'text-red-600';
    };

    $getBgColorClass = function($degree) {
        if ($degree >= 8) return 'bg-green-50 border-green-200';
        if ($degree >= 6) return 'bg-blue-50 border-blue-200';
        if ($degree >= 4) return 'bg-yellow-50 border-yellow-200';
        return 'bg-red-50 border-red-200';
    };
@endphp

<div class="bg-white rounded-xl shadow-sm border border-gray-200 p-6">
    <h2 class="text-lg font-bold text-gray-900 mb-4">{{ $title }}</h2>

    <!-- Overall Performance -->
    <div class="mb-6 text-center">
        <div class="inline-flex items-center justify-center w-24 h-24 rounded-full {{ $getBgColorClass($averageOverall) }} border-4 mb-3">
            <div class="text-center">
                <div class="text-3xl font-bold {{ $getColorClass($averageOverall) }}">
                    {{ number_format($averageOverall, 1) }}
                </div>
                <div class="text-xs text-gray-600">من 10</div>
            </div>
        </div>
        <div class="text-sm font-medium text-gray-700">متوسط الأداء العام</div>
    </div>

    <!-- Performance Breakdown -->
    <div class="space-y-4">
        <!-- New Memorization -->
        <div>
            <div class="flex justify-between items-center mb-2">
                <div class="flex items-center">
                    <i class="ri-book-mark-line text-gray-600 ml-2"></i>
                    <span class="text-sm text-gray-700">الحفظ الجديد</span>
                </div>
                <span class="text-sm font-bold {{ $getColorClass($averageMemorization) }}">
                    {{ number_format($averageMemorization, 1) }}/10
                </span>
            </div>
            <div class="w-full bg-gray-200 rounded-full h-2">
                <div
                    class="h-2 rounded-full transition-all duration-500 {{ $averageMemorization >= 8 ? 'bg-green-500' : ($averageMemorization >= 6 ? 'bg-blue-500' : ($averageMemorization >= 4 ? 'bg-yellow-500' : 'bg-red-500')) }}"
                    style="width: {{ ($averageMemorization / 10) * 100 }}%">
                </div>
            </div>
        </div>

        <!-- Recitation/Reservation -->
        <div>
            <div class="flex justify-between items-center mb-2">
                <div class="flex items-center">
                    <i class="ri-refresh-line text-gray-600 ml-2"></i>
                    <span class="text-sm text-gray-700">المراجعة</span>
                </div>
                <span class="text-sm font-bold {{ $getColorClass($averageReservation) }}">
                    {{ number_format($averageReservation, 1) }}/10
                </span>
            </div>
            <div class="w-full bg-gray-200 rounded-full h-2">
                <div
                    class="h-2 rounded-full transition-all duration-500 {{ $averageReservation >= 8 ? 'bg-green-500' : ($averageReservation >= 6 ? 'bg-blue-500' : ($averageReservation >= 4 ? 'bg-yellow-500' : 'bg-red-500')) }}"
                    style="width: {{ ($averageReservation / 10) * 100 }}%">
                </div>
            </div>
        </div>
    </div>

    <!-- Performance Rating Legend -->
    <div class="mt-6 p-4 bg-gray-50 rounded-lg">
        <h4 class="text-xs font-bold text-gray-700 mb-3">تقييم الأداء</h4>
        <div class="grid grid-cols-2 gap-2 text-xs">
            <div class="flex items-center gap-2">
                <div class="w-3 h-3 bg-green-500 rounded-full"></div>
                <span class="text-gray-700">ممتاز (8-10)</span>
            </div>
            <div class="flex items-center gap-2">
                <div class="w-3 h-3 bg-blue-500 rounded-full"></div>
                <span class="text-gray-700">جيد (6-7.9)</span>
            </div>
            <div class="flex items-center gap-2">
                <div class="w-3 h-3 bg-yellow-500 rounded-full"></div>
                <span class="text-gray-700">مقبول (4-5.9)</span>
            </div>
            <div class="flex items-center gap-2">
                <div class="w-3 h-3 bg-red-500 rounded-full"></div>
                <span class="text-gray-700">ضعيف (أقل من 4)</span>
            </div>
        </div>
    </div>
</div>
