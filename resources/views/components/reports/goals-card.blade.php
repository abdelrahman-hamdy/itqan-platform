@props([
    'goals', // Goals statistics array
    'title' => 'تتبع الأهداف'
])

@php
    // Weekly goals
    $weeklyGoalPages = $goals['weekly_goal_pages'] ?? null;
    $weeklyProgressPages = $goals['weekly_progress_pages'] ?? 0;
    $weeklyProgressPercentage = $weeklyGoalPages > 0 ? min(100, round(($weeklyProgressPages / $weeklyGoalPages) * 100, 1)) : 0;

    // Monthly goals
    $monthlyGoalPages = $goals['monthly_goal_pages'] ?? null;
    $monthlyProgressPages = $goals['monthly_progress_pages'] ?? 0;
    $monthlyProgressPercentage = $monthlyGoalPages > 0 ? min(100, round(($monthlyProgressPages / $monthlyGoalPages) * 100, 1)) : 0;

    // Consistency score
    $consistencyScore = $goals['consistency_score'] ?? null;

    $hasAnyGoals = $weeklyGoalPages !== null || $monthlyGoalPages !== null || $consistencyScore !== null;
@endphp

<div class="bg-white rounded-xl shadow-sm border border-gray-200 p-6">
    <h2 class="text-lg font-bold text-gray-900 mb-4">{{ $title }}</h2>

    @if($hasAnyGoals)
        <!-- Weekly Goal -->
        @if($weeklyGoalPages !== null && $weeklyGoalPages > 0)
            <div class="mb-6">
                <div class="flex justify-between items-center mb-2">
                    <div class="flex items-center">
                        <i class="ri-calendar-week-line text-gray-600 ml-2"></i>
                        <span class="text-sm font-medium text-gray-700">الهدف الأسبوعي</span>
                    </div>
                    <span class="text-sm font-bold {{ $weeklyProgressPercentage >= 100 ? 'text-green-600' : ($weeklyProgressPercentage >= 50 ? 'text-blue-600' : 'text-yellow-600') }}">
                        {{ $weeklyProgressPercentage }}%
                    </span>
                </div>
                <div class="w-full bg-gray-200 rounded-full h-3 mb-2">
                    <div
                        class="h-3 rounded-full transition-all duration-500 {{ $weeklyProgressPercentage >= 100 ? 'bg-green-500' : ($weeklyProgressPercentage >= 50 ? 'bg-blue-500' : 'bg-yellow-500') }}"
                        style="width: {{ $weeklyProgressPercentage }}%">
                    </div>
                </div>
                <div class="flex justify-between text-xs text-gray-600">
                    <span>{{ number_format($weeklyProgressPages, 1) }} صفحة</span>
                    <span>الهدف: {{ number_format($weeklyGoalPages, 1) }} صفحة</span>
                </div>

                @if($weeklyProgressPercentage >= 100)
                    <div class="mt-3 p-3 bg-green-50 border border-green-200 rounded-lg">
                        <div class="flex items-center">
                            <i class="ri-trophy-line text-green-600 ml-2"></i>
                            <span class="text-sm text-green-800 font-medium">أحسنت! لقد حققت هدفك الأسبوعي</span>
                        </div>
                    </div>
                @endif
            </div>
        @endif

        <!-- Monthly Goal -->
        @if($monthlyGoalPages !== null && $monthlyGoalPages > 0)
            <div class="mb-6">
                <div class="flex justify-between items-center mb-2">
                    <div class="flex items-center">
                        <i class="ri-calendar-line text-gray-600 ml-2"></i>
                        <span class="text-sm font-medium text-gray-700">الهدف الشهري</span>
                    </div>
                    <span class="text-sm font-bold {{ $monthlyProgressPercentage >= 100 ? 'text-green-600' : ($monthlyProgressPercentage >= 50 ? 'text-blue-600' : 'text-yellow-600') }}">
                        {{ $monthlyProgressPercentage }}%
                    </span>
                </div>
                <div class="w-full bg-gray-200 rounded-full h-3 mb-2">
                    <div
                        class="h-3 rounded-full transition-all duration-500 {{ $monthlyProgressPercentage >= 100 ? 'bg-green-500' : ($monthlyProgressPercentage >= 50 ? 'bg-blue-500' : 'bg-yellow-500') }}"
                        style="width: {{ $monthlyProgressPercentage }}%">
                    </div>
                </div>
                <div class="flex justify-between text-xs text-gray-600">
                    <span>{{ number_format($monthlyProgressPages, 1) }} صفحة</span>
                    <span>الهدف: {{ number_format($monthlyGoalPages, 1) }} صفحة</span>
                </div>

                @if($monthlyProgressPercentage >= 100)
                    <div class="mt-3 p-3 bg-green-50 border border-green-200 rounded-lg">
                        <div class="flex items-center">
                            <i class="ri-medal-line text-green-600 ml-2"></i>
                            <span class="text-sm text-green-800 font-medium">رائع! لقد حققت هدفك الشهري</span>
                        </div>
                    </div>
                @endif
            </div>
        @endif

        <!-- Consistency Score -->
        @if($consistencyScore !== null)
            <div class="p-4 bg-gradient-to-r from-purple-50 to-blue-50 rounded-lg border border-purple-200">
                <div class="flex items-center justify-between mb-2">
                    <div class="flex items-center">
                        <i class="ri-fire-line text-purple-600 ml-2 text-xl"></i>
                        <span class="text-sm font-medium text-purple-900">مؤشر الانتظام</span>
                    </div>
                    <span class="text-2xl font-bold {{ $consistencyScore >= 80 ? 'text-green-600' : ($consistencyScore >= 60 ? 'text-blue-600' : 'text-yellow-600') }}">
                        {{ $consistencyScore }}%
                    </span>
                </div>
                <p class="text-xs text-purple-700 mt-1">
                    @if($consistencyScore >= 80)
                        ممتاز! تحافظ على انتظام عالٍ في الحضور والتقدم
                    @elseif($consistencyScore >= 60)
                        جيد! استمر في المحافظة على الانتظام
                    @else
                        حاول تحسين انتظامك في الحضور والدراسة
                    @endif
                </p>
            </div>
        @endif

    @else
        <!-- No Goals Set -->
        <div class="text-center py-8">
            <div class="w-16 h-16 bg-gray-100 rounded-full flex items-center justify-center mx-auto mb-4">
                <i class="ri-target-line text-3xl text-gray-400"></i>
            </div>
            <h4 class="text-base font-medium text-gray-900 mb-2">لم يتم تحديد أهداف بعد</h4>
            <p class="text-sm text-gray-600">
                قم بتحديد أهداف أسبوعية أو شهرية لتتبع تقدمك
            </p>
        </div>
    @endif
</div>
