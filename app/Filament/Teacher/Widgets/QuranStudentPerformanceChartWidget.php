<?php

namespace App\Filament\Teacher\Widgets;

use App\Enums\AttendanceStatus;
use App\Models\QuranSession;
use App\Models\StudentSessionReport;
use Filament\Widgets\ChartWidget;
use Illuminate\Support\Facades\Auth;

class QuranStudentPerformanceChartWidget extends ChartWidget
{
    // Prevent auto-display on dashboard - Dashboard explicitly adds this widget
    protected static bool $isDiscoverable = false;

    protected ?string $heading = 'أداء الطلاب في الجلسات';

    protected static ?int $sort = 5;

    protected int|string|array $columnSpan = 'full';

    protected ?string $maxHeight = '300px';

    public ?string $filter = 'month';

    protected function getFilters(): ?array
    {
        return [
            'week' => 'أسبوع',
            'month' => 'شهر',
            'all' => 'الكل',
        ];
    }

    protected function getData(): array
    {
        $user = Auth::user();
        $teacherProfile = $user->quranTeacherProfile;

        if (! $teacherProfile) {
            return $this->getEmptyData();
        }

        // Get teacher's session IDs
        $teacherSessionIds = QuranSession::where('quran_teacher_id', $teacherProfile->id)
            ->pluck('id')
            ->toArray();

        if (empty($teacherSessionIds)) {
            return $this->getEmptyData();
        }

        $labels = [];
        $memorizationGrades = [];
        $revisionGrades = [];
        $attendanceRates = [];

        // Determine grouping interval
        $intervals = match ($this->filter) {
            'week' => 7, // 7 days
            'month' => 4, // 4 weeks
            'all' => 12, // 12 months
            default => 4,
        };

        for ($i = $intervals - 1; $i >= 0; $i--) {
            if ($this->filter === 'week') {
                // Daily data for week view
                $periodStart = now()->subDays($i + 1)->startOfDay();
                $periodEnd = now()->subDays($i)->endOfDay();
                $labels[] = $periodStart->translatedFormat('D');
            } elseif ($this->filter === 'month') {
                // Weekly data for month view
                $periodStart = now()->subWeeks($i + 1)->startOfWeek();
                $periodEnd = now()->subWeeks($i)->endOfWeek();
                $labels[] = 'أسبوع '.($intervals - $i);
            } else {
                // Monthly data for all time view
                $periodStart = now()->subMonths($i + 1)->startOfMonth();
                $periodEnd = now()->subMonths($i)->endOfMonth();
                $labels[] = $periodStart->translatedFormat('M');
            }

            // Get reports for this period
            $periodReports = StudentSessionReport::whereIn('session_id', $teacherSessionIds)
                ->whereBetween('created_at', [$periodStart, $periodEnd])
                ->get();

            // Calculate average memorization grade (0-10 scale, convert to percentage)
            $avgMemorization = $periodReports->whereNotNull('new_memorization_degree')->avg('new_memorization_degree');
            $memorizationGrades[] = $avgMemorization !== null ? round($avgMemorization * 10, 1) : 0;

            // Calculate average revision grade (0-10 scale, convert to percentage)
            $avgRevision = $periodReports->whereNotNull('reservation_degree')->avg('reservation_degree');
            $revisionGrades[] = $avgRevision !== null ? round($avgRevision * 10, 1) : 0;

            // Calculate attendance rate
            $totalReports = $periodReports->count();
            if ($totalReports > 0) {
                $attended = $periodReports->whereIn('attendance_status', [
                    AttendanceStatus::ATTENDED->value,
                    AttendanceStatus::LATE->value,
                    AttendanceStatus::LEFT->value,
                ])->count();
                $attendanceRates[] = round(($attended / $totalReports) * 100, 1);
            } else {
                $attendanceRates[] = 0;
            }
        }

        return [
            'datasets' => [
                [
                    'label' => 'معدل درجات الحفظ الجديد (%)',
                    'data' => $memorizationGrades,
                    'borderColor' => '#3B82F6',
                    'backgroundColor' => 'rgba(59, 130, 246, 0.2)',
                    'pointBackgroundColor' => '#3B82F6',
                    'pointBorderColor' => '#fff',
                    'pointBorderWidth' => 2,
                    'pointRadius' => 4,
                    'fill' => true,
                    'tension' => 0.3,
                ],
                [
                    'label' => 'معدل درجات المراجعة (%)',
                    'data' => $revisionGrades,
                    'borderColor' => '#8B5CF6',
                    'backgroundColor' => 'rgba(139, 92, 246, 0.2)',
                    'pointBackgroundColor' => '#8B5CF6',
                    'pointBorderColor' => '#fff',
                    'pointBorderWidth' => 2,
                    'pointRadius' => 4,
                    'fill' => true,
                    'tension' => 0.3,
                ],
                [
                    'label' => 'نسبة الحضور (%)',
                    'data' => $attendanceRates,
                    'borderColor' => '#10B981',
                    'backgroundColor' => 'rgba(16, 185, 129, 0.2)',
                    'pointBackgroundColor' => '#10B981',
                    'pointBorderColor' => '#fff',
                    'pointBorderWidth' => 2,
                    'pointRadius' => 4,
                    'fill' => true,
                    'tension' => 0.3,
                ],
            ],
            'labels' => $labels,
        ];
    }

    protected function getEmptyData(): array
    {
        $labels = match ($this->filter) {
            'week' => ['الأحد', 'الاثنين', 'الثلاثاء', 'الأربعاء', 'الخميس', 'الجمعة', 'السبت'],
            'month' => ['أسبوع 1', 'أسبوع 2', 'أسبوع 3', 'أسبوع 4'],
            'all' => ['يناير', 'فبراير', 'مارس', 'أبريل', 'مايو', 'يونيو', 'يوليو', 'أغسطس', 'سبتمبر', 'أكتوبر', 'نوفمبر', 'ديسمبر'],
            default => ['أسبوع 1', 'أسبوع 2', 'أسبوع 3', 'أسبوع 4'],
        };

        $zeroData = array_fill(0, count($labels), 0);

        return [
            'datasets' => [
                [
                    'label' => 'معدل درجات الحفظ الجديد (%)',
                    'data' => $zeroData,
                    'borderColor' => '#3B82F6',
                    'backgroundColor' => 'rgba(59, 130, 246, 0.2)',
                    'pointBackgroundColor' => '#3B82F6',
                    'pointBorderColor' => '#fff',
                    'pointBorderWidth' => 2,
                    'pointRadius' => 4,
                    'fill' => true,
                    'tension' => 0.3,
                ],
                [
                    'label' => 'معدل درجات المراجعة (%)',
                    'data' => $zeroData,
                    'borderColor' => '#8B5CF6',
                    'backgroundColor' => 'rgba(139, 92, 246, 0.2)',
                    'pointBackgroundColor' => '#8B5CF6',
                    'pointBorderColor' => '#fff',
                    'pointBorderWidth' => 2,
                    'pointRadius' => 4,
                    'fill' => true,
                    'tension' => 0.3,
                ],
                [
                    'label' => 'نسبة الحضور (%)',
                    'data' => $zeroData,
                    'borderColor' => '#10B981',
                    'backgroundColor' => 'rgba(16, 185, 129, 0.2)',
                    'pointBackgroundColor' => '#10B981',
                    'pointBorderColor' => '#fff',
                    'pointBorderWidth' => 2,
                    'pointRadius' => 4,
                    'fill' => true,
                    'tension' => 0.3,
                ],
            ],
            'labels' => $labels,
        ];
    }

    protected function getType(): string
    {
        return 'line';
    }

    protected function getOptions(): array
    {
        return [
            'responsive' => true,
            'maintainAspectRatio' => false,
            'plugins' => [
                'legend' => [
                    'position' => 'bottom',
                    'labels' => [
                        'usePointStyle' => true,
                        'padding' => 20,
                        'font' => [
                            'family' => 'Tajawal',
                            'size' => 12,
                        ],
                    ],
                ],
                'tooltip' => [
                    'mode' => 'index',
                    'intersect' => false,
                    'bodyFont' => [
                        'family' => 'Tajawal',
                    ],
                    'titleFont' => [
                        'family' => 'Tajawal',
                    ],
                ],
            ],
            'scales' => [
                'x' => [
                    'grid' => [
                        'display' => false,
                    ],
                    'ticks' => [
                        'font' => [
                            'family' => 'Tajawal',
                        ],
                    ],
                ],
                'y' => [
                    'beginAtZero' => true,
                    'max' => 100,
                    'grid' => [
                        'color' => 'rgba(0, 0, 0, 0.05)',
                    ],
                    'ticks' => [
                        'stepSize' => 20,
                        'font' => [
                            'family' => 'Tajawal',
                        ],
                    ],
                ],
            ],
            'interaction' => [
                'mode' => 'nearest',
                'axis' => 'x',
                'intersect' => false,
            ],
        ];
    }
}
