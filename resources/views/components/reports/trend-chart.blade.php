@props([
    'data', // TrendDataDTO or array
    'title' => 'تطور أدائي',
])

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
        <i class="ri-line-chart-line text-blue-600 ms-2"></i>
        {{ $title }}
    </h2>
    <div class="h-80">
        <canvas id="performanceChart"></canvas>
    </div>
</div>

<!-- Chart.js -->
<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.0/dist/chart.umd.min.js"></script>
<script>
    document.addEventListener('DOMContentLoaded', function() {
        const ctx = document.getElementById('performanceChart').getContext('2d');

        const chartData = {
            labels: @json($labels),
            datasets: [
                {
                    label: 'الحضور',
                    data: @json($attendance),
                    borderColor: 'rgb(34, 197, 94)',
                    backgroundColor: 'rgba(34, 197, 94, 0.1)',
                    tension: 0.4,
                    fill: true
                },
                {
                    label: 'درجات الحفظ',
                    data: @json($memorization),
                    borderColor: 'rgb(168, 85, 247)',
                    backgroundColor: 'rgba(168, 85, 247, 0.1)',
                    tension: 0.4,
                    fill: true,
                    spanGaps: true
                },
                {
                    label: 'درجات المراجعة',
                    data: @json($reservation),
                    borderColor: 'rgb(59, 130, 246)',
                    backgroundColor: 'rgba(59, 130, 246, 0.1)',
                    tension: 0.4,
                    fill: true,
                    spanGaps: true
                }
            ]
        };

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
                            }
                        }
                    },
                    tooltip: {
                        rtl: true,
                        titleFont: {
                            family: 'Tajawal, sans-serif'
                        },
                        bodyFont: {
                            family: 'Tajawal, sans-serif'
                        }
                    }
                },
                scales: {
                    y: {
                        beginAtZero: true,
                        max: 10,
                        ticks: {
                            font: {
                                family: 'Tajawal, sans-serif'
                            }
                        }
                    },
                    x: {
                        ticks: {
                            font: {
                                family: 'Tajawal, sans-serif'
                            }
                        }
                    }
                }
            }
        };

        new Chart(ctx, config);
    });
</script>
@endif
