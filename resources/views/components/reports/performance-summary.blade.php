@props([
    'data', // PerformanceDTO or array
    'title' => null,
    'type' => 'quran', // quran, academic, interactive
])

@php
    $displayTitle = $title ?? __('components.reports.performance_summary.title');
@endphp

@php
// Support both DTO and array
$averageOverall = is_object($data) ? $data->averageOverall : ($data['average_overall'] ?? $data['average_overall_performance'] ?? 0);
$averageMemorization = is_object($data) ? $data->averageMemorization : ($data['average_memorization'] ?? $data['average_memorization_degree'] ?? null);
$averageReservation = is_object($data) ? $data->averageReservation : ($data['average_reservation'] ?? $data['average_reservation_degree'] ?? null);
$averageHomework = is_object($data) ? $data->averageHomework : ($data['average_homework'] ?? $data['average_homework_degree'] ?? null);
$totalEvaluated = is_object($data) ? $data->totalEvaluated : ($data['total_evaluated'] ?? $data['sessions_evaluated'] ?? 0);

$ratingLabel = is_object($data) && method_exists($data, 'getRatingLabel')
    ? $data->getRatingLabel()
    : (match(true) {
        $averageOverall >= 8 => __('components.reports.performance_summary.excellent'),
        $averageOverall >= 6 => __('components.reports.performance_summary.good'),
        $averageOverall >= 4 => __('components.reports.performance_summary.acceptable'),
        default => __('components.reports.performance_summary.weak')
    });

$colorClass = is_object($data) && method_exists($data, 'getColorClass')
    ? $data->getColorClass()
    : (match(true) {
        $averageOverall >= 8 => 'green',
        $averageOverall >= 6 => 'blue',
        $averageOverall >= 4 => 'yellow',
        default => 'red'
    });
@endphp

<div class="bg-white rounded-xl shadow-sm border border-gray-200 p-6">
    <h2 class="text-xl font-bold text-gray-900 mb-6 flex items-center">
        <i class="ri-star-line text-{{ $colorClass }}-600 ms-2 rtl:ms-2 ltr:me-2"></i>
        {{ $displayTitle }}
    </h2>

    <div class="flex flex-col lg:flex-row items-center gap-8">
        <!-- Circular Progress -->
        <div class="flex-shrink-0">
            <x-ui.circular-progress
                :value="$averageOverall * 10"
                :color="$colorClass"
                size="lg"
                :label="$ratingLabel"
                :sublabel="number_format($averageOverall, 1) . '/10'"
            />
        </div>

        <!-- Breakdown Metrics -->
        <div class="flex-1 space-y-4 w-full">
            @if($type === 'quran' && $averageMemorization !== null)
                <x-ui.progress-bar
                    :percentage="$averageMemorization * 10"
                    :label="__('components.reports.performance_summary.memorization_score')"
                    :color="$colorClass"
                    :showPercentage="false"
                />
                <div class="flex justify-between items-center text-sm">
                    <span class="text-gray-600">{{ number_format($averageMemorization, 1) }}/10</span>
                </div>

                <x-ui.progress-bar
                    :percentage="$averageReservation * 10"
                    :label="__('components.reports.performance_summary.recitation_review_score')"
                    :color="$colorClass"
                    :showPercentage="false"
                />
                <div class="flex justify-between items-center text-sm">
                    <span class="text-gray-600">{{ number_format($averageReservation ?? 0, 1) }}/10</span>
                </div>
            @elseif(($type === 'academic' || $type === 'interactive') && $averageHomework !== null)
                <x-ui.progress-bar
                    :percentage="$averageHomework * 10"
                    :label="__('components.reports.performance_summary.homework_score')"
                    :color="$colorClass"
                    :showPercentage="false"
                />
                <div class="flex justify-between items-center text-sm">
                    <span class="text-gray-600">{{ number_format($averageHomework, 1) }}/10</span>
                </div>
            @endif

            <div class="text-sm text-gray-600 pt-2 border-t border-gray-200">
                {{ __('components.reports.performance_summary.total_evaluations') }}: {{ $totalEvaluated }}
            </div>
        </div>
    </div>
</div>
