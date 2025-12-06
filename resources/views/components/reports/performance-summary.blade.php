@props([
    'data', // PerformanceDTO or array
    'title' => 'أداء الطالب',
    'type' => 'quran', // quran, academic, interactive
])

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
        $averageOverall >= 8 => 'ممتاز',
        $averageOverall >= 6 => 'جيد',
        $averageOverall >= 4 => 'مقبول',
        default => 'ضعيف'
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
        <i class="ri-star-line text-{{ $colorClass }}-600 ml-2"></i>
        {{ $title }}
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
                    label="درجة الحفظ الجديد"
                    :color="$colorClass"
                    :showPercentage="false"
                />
                <div class="flex justify-between items-center text-sm">
                    <span class="text-gray-600">{{ number_format($averageMemorization, 1) }}/10</span>
                </div>

                <x-ui.progress-bar
                    :percentage="$averageReservation * 10"
                    label="درجة التلاوة والمراجعة"
                    :color="$colorClass"
                    :showPercentage="false"
                />
                <div class="flex justify-between items-center text-sm">
                    <span class="text-gray-600">{{ number_format($averageReservation ?? 0, 1) }}/10</span>
                </div>
            @elseif(($type === 'academic' || $type === 'interactive') && $averageHomework !== null)
                <x-ui.progress-bar
                    :percentage="$averageHomework * 10"
                    label="درجة الواجبات"
                    :color="$colorClass"
                    :showPercentage="false"
                />
                <div class="flex justify-between items-center text-sm">
                    <span class="text-gray-600">{{ number_format($averageHomework, 1) }}/10</span>
                </div>
            @endif

            <div class="text-sm text-gray-600 pt-2 border-t border-gray-200">
                إجمالي التقييمات: {{ $totalEvaluated }}
            </div>
        </div>
    </div>
</div>
