@props([
    'performance', // Performance statistics array
    'title' => 'التقييم الأكاديمي'
])

@php
    $averageHomework = $performance['average_homework_degree'] ?? 0;
    $averageOverall = $performance['average_overall_performance'] ?? $averageHomework;
    $totalEvaluated = $performance['total_evaluated'] ?? 0;

    // Get color based on performance
    $getColorClass = function($degree) {
        if ($degree >= 8) return 'text-green-600';
        if ($degree >= 6) return 'text-blue-600';
        if ($degree >= 4) return 'text-yellow-600';
        return 'text-red-600';
    };

    $getStrokeColor = function($degree) {
        if ($degree >= 8) return '#10b981';
        if ($degree >= 6) return '#3b82f6';
        if ($degree >= 4) return '#f59e0b';
        return '#ef4444';
    };

    $getRatingLabel = function($degree) {
        if ($degree >= 8) return 'ممتاز';
        if ($degree >= 6) return 'جيد';
        if ($degree >= 4) return 'مقبول';
        return 'ضعيف';
    };

    // Calculate percentage for circular progress (0-100%)
    $progressPercentage = ($averageOverall / 10) * 100;
@endphp

<div class="bg-white rounded-xl shadow-sm border border-gray-200 p-6">
    <h2 class="text-lg font-bold text-gray-900 mb-4">{{ $title }}</h2>

    <!-- Overall Performance with SVG Circle -->
    <div class="flex items-center justify-center mb-6">
        <div class="relative inline-flex items-center justify-center">
            <!-- SVG Circle Progress -->
            <svg class="w-32 h-32" viewBox="0 0 120 120">
                <!-- Background circle -->
                <circle
                    cx="60"
                    cy="60"
                    r="54"
                    fill="none"
                    stroke="#e5e7eb"
                    stroke-width="8"
                ></circle>
                <!-- Progress circle -->
                <circle
                    cx="60"
                    cy="60"
                    r="54"
                    fill="none"
                    stroke="{{ $getStrokeColor($averageOverall) }}"
                    stroke-width="8"
                    stroke-dasharray="{{ 2 * 3.14159 * 54 }}"
                    stroke-dashoffset="{{ 2 * 3.14159 * 54 * (1 - $progressPercentage / 100) }}"
                    stroke-linecap="round"
                    transform="rotate(-90 60 60)"
                    class="transition-all duration-500"
                ></circle>
            </svg>
            <!-- Center text -->
            <div class="absolute inset-0 flex items-center justify-center flex-col">
                <span class="text-3xl font-bold {{ $getColorClass($averageOverall) }}">
                    {{ number_format($averageOverall, 1) }}
                </span>
                <span class="text-xs text-gray-600 mt-1">من 10</span>
            </div>
        </div>
    </div>

    <!-- Rating Label -->
    <div class="text-center mb-6">
        <span class="text-base font-bold {{ $getColorClass($averageOverall) }}">
            {{ $getRatingLabel($averageOverall) }}
        </span>
    </div>

    <!-- Performance Breakdown -->
    <div class="space-y-4">
        <!-- Homework Performance -->
        <div>
            <div class="flex justify-between items-center mb-2">
                <div class="flex items-center">
                    <i class="ri-file-edit-line text-gray-600 ms-2"></i>
                    <span class="text-sm text-gray-700">أداء الواجبات</span>
                </div>
                <span class="text-sm font-bold {{ $getColorClass($averageHomework) }}">
                    {{ number_format($averageHomework, 1) }}/10
                </span>
            </div>
            <div class="w-full bg-gray-200 rounded-full h-2">
                <div
                    class="h-2 rounded-full transition-all duration-500 {{ $averageHomework >= 8 ? 'bg-green-500' : ($averageHomework >= 6 ? 'bg-blue-500' : ($averageHomework >= 4 ? 'bg-yellow-500' : 'bg-red-500')) }}"
                    style="width: {{ ($averageHomework / 10) * 100 }}%">
                </div>
            </div>
        </div>

        <!-- Total Evaluated -->
        @if($totalEvaluated > 0)
        <div class="pt-2 border-t border-gray-200">
            <div class="flex items-center justify-between p-3 bg-gray-50 rounded-lg">
                <div class="flex items-center">
                    <i class="ri-checkbox-circle-line text-blue-600 ms-2"></i>
                    <span class="text-sm text-gray-700">إجمالي التقييمات</span>
                </div>
                <span class="text-sm font-bold text-blue-600">{{ $totalEvaluated }}</span>
            </div>
        </div>
        @endif
    </div>
</div>
