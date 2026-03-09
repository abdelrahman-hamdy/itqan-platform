@props([
    'data', // TrendDataDTO or array
    'title' => null,
])

@php
    $displayTitle = $title ?? __('components.reports.trend_chart.title');
@endphp

@php
// Support both DTO and array
$labels = is_object($data) ? $data->labels : ($data['labels'] ?? []);
$attendance = is_object($data) ? $data->attendance : ($data['attendance'] ?? []);
$memorization = is_object($data) ? $data->memorization : ($data['memorization'] ?? []);
$reservation = is_object($data) ? $data->reservation : ($data['reservation'] ?? []);
$hasData = is_object($data) && method_exists($data, 'hasData') ? $data->hasData() : !empty($labels);
@endphp

@if($hasData)
<div class="bg-white rounded-xl shadow-sm border border-gray-200 p-6 mb-6">
    <h2 class="text-xl font-bold text-gray-900 mb-4 flex items-center">
        <i class="ri-line-chart-line text-blue-600 me-2"></i>
        {{ $displayTitle }}
    </h2>
    <div class="h-80">
        <canvas id="performanceChart"></canvas>
    </div>
</div>

<!-- Chart.js is bundled via Vite (resources/js/chart-init.js) -->
<script>
    document.addEventListener('DOMContentLoaded', function() {
        const ctx = document.getElementById('performanceChart').getContext('2d');

        const labels = @json($labels);
        const attendanceRaw = @json($attendance);
        const memorizationRaw = @json($memorization);
        const reservationRaw = @json($reservation);
        const dataPointCount = labels.length;

        // For few data points, use larger points and no curve tension
        const pointRadius = dataPointCount <= 3 ? 6 : 4;
        const pointHoverRadius = dataPointCount <= 3 ? 9 : 6;
        const lineTension = dataPointCount <= 2 ? 0 : 0.4;
        const borderWidth = dataPointCount <= 3 ? 3 : 2;

        // Replace leading nulls with 0 so lines/fill areas render from the start
        function fillLeadingNulls(arr) {
            const result = [...arr];
            for (let i = 0; i < result.length; i++) {
                if (result[i] !== null) break;
                result[i] = 0;
            }
            return result;
        }
        const memorization = fillLeadingNulls(memorizationRaw);
        const reservation = fillLeadingNulls(reservationRaw);

        // Only include datasets that have at least one non-null value
        const hasMemorization = memorizationRaw.some(v => v !== null && v !== 0);
        const hasReservation = reservationRaw.some(v => v !== null && v !== 0);

        const datasets = [
            {
                label: @json(__('components.reports.trend_chart.attendance')),
                data: attendanceRaw,
                borderColor: 'rgb(34, 197, 94)',
                backgroundColor: 'rgba(34, 197, 94, 0.1)',
                tension: lineTension,
                fill: true,
                pointRadius: pointRadius,
                pointHoverRadius: pointHoverRadius,
                pointBackgroundColor: 'rgb(34, 197, 94)',
                borderWidth: borderWidth
            }
        ];

        if (hasMemorization) {
            datasets.push({
                label: @json(__('components.reports.trend_chart.memorization_scores')),
                data: memorization,
                borderColor: 'rgb(168, 85, 247)',
                backgroundColor: 'rgba(168, 85, 247, 0.1)',
                tension: lineTension,
                fill: true,
                spanGaps: true,
                pointRadius: pointRadius,
                pointHoverRadius: pointHoverRadius,
                pointBackgroundColor: 'rgb(168, 85, 247)',
                borderWidth: borderWidth
            });
        }

        if (hasReservation) {
            datasets.push({
                label: @json(__('components.reports.trend_chart.review_scores')),
                data: reservation,
                borderColor: 'rgb(59, 130, 246)',
                backgroundColor: 'rgba(59, 130, 246, 0.1)',
                tension: lineTension,
                fill: true,
                spanGaps: true,
                pointRadius: pointRadius,
                pointHoverRadius: pointHoverRadius,
                pointBackgroundColor: 'rgb(59, 130, 246)',
                borderWidth: borderWidth
            });
        }

        const chartData = { labels, datasets };

        const config = {
            type: 'line',
            data: chartData,
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: {
                        position: 'top',
                        rtl: true,
                        labels: {
                            font: {
                                family: 'Tajawal, sans-serif',
                                size: 14
                            },
                            usePointStyle: true,
                            pointStyle: 'circle'
                        }
                    },
                    tooltip: {
                        rtl: true,
                        titleFont: {
                            family: 'Tajawal, sans-serif'
                        },
                        bodyFont: {
                            family: 'Tajawal, sans-serif'
                        },
                        callbacks: {
                            label: function(context) {
                                if (context.raw === null) return null;
                                return context.dataset.label + ': ' + context.raw + '/10';
                            }
                        },
                        filter: function(tooltipItem) {
                            return tooltipItem.raw !== null;
                        }
                    }
                },
                scales: {
                    y: {
                        beginAtZero: true,
                        max: 10,
                        ticks: {
                            stepSize: 2,
                            font: {
                                family: 'Tajawal, sans-serif'
                            }
                        },
                        grid: {
                            color: 'rgba(0, 0, 0, 0.06)'
                        }
                    },
                    x: {
                        ticks: {
                            font: {
                                family: 'Tajawal, sans-serif'
                            }
                        },
                        grid: {
                            display: false
                        }
                    }
                }
            }
        };

        new Chart(ctx, config);
    });
</script>
@endif
