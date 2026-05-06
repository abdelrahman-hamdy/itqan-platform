<?php

namespace App\Services;

use App\Exports\TeacherEarningsDetailsExport;
use App\Exports\TeacherEarningsSummaryExport;
use App\Models\AcademicSession;
use App\Models\InteractiveCourseSession;
use App\Models\QuranSession;
use Illuminate\Support\Collection;
use Maatwebsite\Excel\Facades\Excel;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
use TCPDF;

class TeacherEarningsExportService
{
    private const SUMMARY_COLUMN_LABEL_KEYS = [
        'teacher' => 'supervisor.teacher_earnings.summary_teacher_name',
        'quran_individual' => 'supervisor.teacher_earnings.summary_quran_individual',
        'quran_group' => 'supervisor.teacher_earnings.summary_quran_group',
        'academic' => 'supervisor.teacher_earnings.summary_academic',
        'interactive' => 'supervisor.teacher_earnings.summary_interactive',
        'sessions' => 'supervisor.teacher_earnings.summary_sessions_count',
        'hours' => 'supervisor.teacher_earnings.summary_total_hours',
        'total' => 'supervisor.teacher_earnings.summary_total',
    ];

    private const SUMMARY_COLUMN_BASELINE_WIDTHS = [
        'teacher' => 50,
        'quran_individual' => 34,
        'quran_group' => 34,
        'academic' => 34,
        'interactive' => 34,
        'sessions' => 22,
        'hours' => 24,
        'total' => 35,
    ];

    private const DETAILS_COLUMN_LABEL_KEYS = [
        'teacher' => 'supervisor.teacher_earnings.details_col_teacher',
        'source_type' => 'supervisor.teacher_earnings.details_col_source_type',
        'source_name' => 'supervisor.teacher_earnings.details_col_source_name',
        'session_date' => 'supervisor.teacher_earnings.details_col_session_date',
        'earning_month' => 'supervisor.teacher_earnings.details_col_earning_month',
        'duration' => 'supervisor.teacher_earnings.details_col_duration',
        'calculation_method' => 'supervisor.teacher_earnings.details_col_calculation_method',
        'amount' => 'supervisor.teacher_earnings.details_col_amount',
        'status' => 'supervisor.teacher_earnings.details_col_status',
        'dispute_notes' => 'supervisor.teacher_earnings.details_col_dispute_notes',
    ];

    private const DETAILS_COLUMN_BASELINE_WIDTHS = [
        'teacher' => 32,
        'source_type' => 22,
        'source_name' => 38,
        'session_date' => 22,
        'earning_month' => 22,
        'duration' => 18,
        'calculation_method' => 28,
        'amount' => 24,
        'status' => 20,
        'dispute_notes' => 50,
    ];

    private const PAGE_USABLE_WIDTH_MM = 277;

    /**
     * Generate a teacher earnings summary Excel file for download.
     */
    public function generateSummaryExcel(array $teacherSummaries, array $profileUserMap, array $meta, array $columns = []): BinaryFileResponse
    {
        $columns = $this->resolveColumns($columns, array_keys(self::SUMMARY_COLUMN_LABEL_KEYS));
        $export = new TeacherEarningsSummaryExport($teacherSummaries, $profileUserMap, $meta, $columns);
        $filename = 'teacher-earnings-'.nowInAcademyTimezone()->format('Y-m-d').'.xlsx';

        return Excel::download($export, $filename);
    }

    /**
     * Generate a teacher earnings summary PDF in memory.
     */
    public function generateSummaryPdf(array $teacherSummaries, array $profileUserMap, array $meta, array $columns = []): string
    {
        $columns = $this->resolveColumns($columns, array_keys(self::SUMMARY_COLUMN_LABEL_KEYS));

        $pdf = $this->createPdf($meta, __('supervisor.teacher_earnings.export_report_title'));
        $pdf->AddPage();
        $pdf->setRTL(true);

        $this->addHeader($pdf, $meta, __('supervisor.teacher_earnings.export_report_title'));
        $pdf->Ln(6);
        $this->addSummaryTable($pdf, $teacherSummaries, $profileUserMap, $meta, $columns);
        $pdf->Ln(8);
        $this->addFooter($pdf, $meta);

        return $pdf->Output('', 'S');
    }

    /**
     * Generate a teacher earnings details Excel file for download.
     */
    public function generateDetailsExcel(Collection $earnings, array $profileUserMap, array $meta, array $columns = []): BinaryFileResponse
    {
        $columns = $this->resolveColumns($columns, array_keys(self::DETAILS_COLUMN_LABEL_KEYS));
        $rows = $earnings->map(fn ($earning) => $this->buildDetailsRow($earning, $profileUserMap, $meta))->all();

        $export = new TeacherEarningsDetailsExport($rows, $meta, $columns);
        $filename = 'teacher-earnings-details-'.nowInAcademyTimezone()->format('Y-m-d').'.xlsx';

        return Excel::download($export, $filename);
    }

    /**
     * Generate a teacher earnings details PDF in memory.
     */
    public function generateDetailsPdf(Collection $earnings, array $profileUserMap, array $meta, array $columns = []): string
    {
        $columns = $this->resolveColumns($columns, array_keys(self::DETAILS_COLUMN_LABEL_KEYS));
        $rows = $earnings->map(fn ($earning) => $this->buildDetailsRow($earning, $profileUserMap, $meta))->all();

        $pdf = $this->createPdf($meta, __('supervisor.teacher_earnings.export_details_report_title'));
        $pdf->AddPage();
        $pdf->setRTL(true);

        $this->addHeader($pdf, $meta, __('supervisor.teacher_earnings.export_details_report_title'));
        $pdf->Ln(6);
        $this->addDetailsTable($pdf, $rows, $columns);
        $pdf->Ln(8);
        $this->addFooter($pdf, $meta);

        return $pdf->Output('', 'S');
    }

    /**
     * Project a single earning to the labelled, currency-aware values used by
     * both the details Excel exporter and the details PDF table renderer.
     *
     * @return array<string, string|int|float>
     */
    public function buildDetailsRow($earning, array $profileUserMap, array $meta): array
    {
        $currency = $meta['currency_symbol'];
        $profileKey = $earning->teacher_type.'_'.$earning->teacher_id;
        $teacherUser = $profileUserMap[$profileKey] ?? null;
        $teacherName = $teacherUser?->name ?? $earning->teacher_name ?? __('common.unknown');

        $session = $earning->session;
        $sessionType = $this->resolveSessionType($earning);
        [$sourceLabel, $sourceName] = $this->resolveSource($sessionType, $session);

        $statusLabel = $earning->is_disputed
            ? __('supervisor.teacher_earnings.status_disputed')
            : ($earning->is_finalized
                ? __('supervisor.teacher_earnings.status_finalized')
                : __('supervisor.teacher_earnings.status_pending'));

        $durationMinutes = (int) ($earning->calculation_metadata['duration_minutes'] ?? 60);

        return [
            'teacher' => $teacherName,
            'source_type' => $sourceLabel,
            'source_name' => $sourceName ?? '-',
            'session_date' => $earning->session_completed_at?->format('Y-m-d') ?? '-',
            'earning_month' => $earning->earning_month?->format('Y-m') ?? '-',
            'duration' => $durationMinutes,
            'calculation_method' => $earning->calculation_method_label ?? (string) $earning->calculation_method,
            'amount' => number_format((float) $earning->amount, 2).' '.$currency,
            'status' => $statusLabel,
            'dispute_notes' => $earning->is_disputed ? (string) ($earning->dispute_notes ?? '') : '-',
        ];
    }

    private function resolveSessionType($earning): string
    {
        return match ($earning->session_type) {
            QuranSession::class, 'quran_session' => 'quran',
            AcademicSession::class, 'academic_session' => 'academic',
            InteractiveCourseSession::class, 'interactive_course_session' => 'interactive',
            default => 'other',
        };
    }

    /**
     * @return array{0: string, 1: ?string}
     */
    private function resolveSource(string $sessionType, $session): array
    {
        if ($sessionType === 'quran' && $session) {
            $isIndividual = $session->session_type === 'individual';
            $label = $isIndividual
                ? __('supervisor.teacher_earnings.source_quran_individual')
                : __('supervisor.teacher_earnings.source_quran_group');
            $name = $isIndividual ? $session->individualCircle?->name : $session->circle?->name;

            return [$label, $name];
        }

        if ($sessionType === 'academic') {
            return [__('supervisor.teacher_earnings.source_academic'), $session?->academicIndividualLesson?->name];
        }

        if ($sessionType === 'interactive') {
            return [__('supervisor.teacher_earnings.source_interactive'), $session?->course?->title];
        }

        return [__('supervisor.teacher_earnings.source_other'), null];
    }

    private function createPdf(array $meta, string $title): TCPDF
    {
        $pdf = new TCPDF('L', 'mm', 'A4', true, 'UTF-8', false);

        $pdf->SetCreator('Itqan Platform');
        $pdf->SetAuthor($meta['academy_name']);
        $pdf->SetTitle($title);
        $pdf->setPrintHeader(false);
        $pdf->setPrintFooter(false);
        $pdf->SetMargins(10, 10, 10);
        $pdf->SetAutoPageBreak(true, 15);

        return $pdf;
    }

    private function addHeader(TCPDF $pdf, array $meta, string $title): void
    {
        $pdf->SetFont('dejavusans', 'B', 14);
        $pdf->Cell(0, 10, $meta['academy_name'], 0, 1, 'C');

        $pdf->SetFont('dejavusans', 'B', 12);
        $pdf->Cell(0, 8, $title, 0, 1, 'C');

        $pdf->SetFont('dejavusans', '', 10);
        $pdf->Cell(0, 7, __('supervisor.teacher_earnings.export_period_label').': '.$meta['period_label'], 0, 1, 'C');

        $pdf->SetFont('dejavusans', '', 9);
        $pdf->SetTextColor(128, 128, 128);
        $pdf->Cell(0, 6, __('supervisor.teacher_earnings.export_generated_at').': '.$meta['generated_at'], 0, 1, 'C');
        $pdf->SetTextColor(0, 0, 0);
    }

    private function addSummaryTable(TCPDF $pdf, array $summaries, array $profileUserMap, array $meta, array $columns): void
    {
        $currency = $meta['currency_symbol'];
        $widths = $this->scaleColumnWidths($columns, self::SUMMARY_COLUMN_BASELINE_WIDTHS);

        $pdf->SetFont('dejavusans', 'B', 8);
        $pdf->SetFillColor(240, 240, 240);

        foreach ($columns as $i => $col) {
            $isLast = $i === count($columns) - 1;
            $pdf->Cell($widths[$col], 8, __(self::SUMMARY_COLUMN_LABEL_KEYS[$col]), 1, $isLast ? 1 : 0, 'C', true);
        }

        $pdf->SetFont('dejavusans', '', 8);
        $rowIndex = 0;
        $totals = [
            'quran_individual' => 0.0,
            'quran_group' => 0.0,
            'academic' => 0.0,
            'interactive' => 0.0,
            'sessions_count' => 0,
            'total_duration_minutes' => 0,
            'total' => 0.0,
        ];

        foreach ($summaries as $summary) {
            $profileKey = $summary['teacher_type'].'_'.$summary['teacher_id'];
            $teacherUser = $profileUserMap[$profileKey] ?? null;
            $teacherName = $teacherUser?->name ?? __('common.unknown');
            $hours = round($summary['total_duration_minutes'] / 60, 2);
            $fillColor = $rowIndex % 2 === 1;
            if ($fillColor) {
                $pdf->SetFillColor(250, 250, 250);
            }

            $cellValues = [
                'teacher' => $teacherName,
                'quran_individual' => $this->formatAmount($summary['quran_individual']['amount'], $currency),
                'quran_group' => $this->formatAmount($summary['quran_group']['amount'], $currency),
                'academic' => $this->formatAmount($summary['academic']['amount'], $currency),
                'interactive' => $this->formatAmount($summary['interactive']['amount'], $currency),
                'sessions' => $this->num($summary['sessions_count']),
                'hours' => $this->num($hours).' '.__('supervisor.teacher_earnings.hours_unit'),
                'total' => $this->num(number_format($summary['total'], 2)).' '.$currency,
            ];

            foreach ($columns as $i => $col) {
                $isLast = $i === count($columns) - 1;
                $pdf->Cell($widths[$col], 7, $cellValues[$col], 1, $isLast ? 1 : 0, 'C', $fillColor);
            }

            $totals['quran_individual'] += $summary['quran_individual']['amount'];
            $totals['quran_group'] += $summary['quran_group']['amount'];
            $totals['academic'] += $summary['academic']['amount'];
            $totals['interactive'] += $summary['interactive']['amount'];
            $totals['sessions_count'] += $summary['sessions_count'];
            $totals['total_duration_minutes'] += $summary['total_duration_minutes'];
            $totals['total'] += $summary['total'];
            $rowIndex++;
        }

        $pdf->SetFont('dejavusans', 'B', 8);
        $pdf->SetFillColor(230, 230, 230);
        $totalHours = round($totals['total_duration_minutes'] / 60, 2);
        $totalsRow = [
            'teacher' => __('supervisor.teacher_earnings.summary_total'),
            'quran_individual' => $this->formatAmount($totals['quran_individual'], $currency),
            'quran_group' => $this->formatAmount($totals['quran_group'], $currency),
            'academic' => $this->formatAmount($totals['academic'], $currency),
            'interactive' => $this->formatAmount($totals['interactive'], $currency),
            'sessions' => $this->num($totals['sessions_count']),
            'hours' => $this->num($totalHours).' '.__('supervisor.teacher_earnings.hours_unit'),
            'total' => $this->num(number_format($totals['total'], 2)).' '.$currency,
        ];

        foreach ($columns as $i => $col) {
            $isLast = $i === count($columns) - 1;
            $pdf->Cell($widths[$col], 8, $totalsRow[$col], 1, $isLast ? 1 : 0, 'C', true);
        }
    }

    private function addDetailsTable(TCPDF $pdf, array $rows, array $columns): void
    {
        $widths = $this->scaleColumnWidths($columns, self::DETAILS_COLUMN_BASELINE_WIDTHS);
        $hasDisputeNotes = in_array('dispute_notes', $columns, true);
        $bodyFontSize = count($columns) >= 8 ? 7 : 8;

        $pdf->SetFont('dejavusans', 'B', $bodyFontSize);
        $pdf->SetFillColor(240, 240, 240);

        foreach ($columns as $i => $col) {
            $isLast = $i === count($columns) - 1;
            $pdf->Cell($widths[$col], 8, __(self::DETAILS_COLUMN_LABEL_KEYS[$col]), 1, $isLast ? 1 : 0, 'C', true);
        }

        $pdf->SetFont('dejavusans', '', $bodyFontSize);
        $rowIndex = 0;

        foreach ($rows as $row) {
            $fillColor = $rowIndex % 2 === 1;
            if ($fillColor) {
                $pdf->SetFillColor(250, 250, 250);
            }

            if ($hasDisputeNotes && mb_strlen((string) $row['dispute_notes']) > 50) {
                $rowHeight = 12;
                foreach ($columns as $i => $col) {
                    $isLast = $i === count($columns) - 1;
                    if ($col === 'dispute_notes') {
                        $x = $pdf->GetX();
                        $y = $pdf->GetY();
                        $pdf->MultiCell($widths[$col], $rowHeight, (string) $row[$col], 1, 'C', $fillColor, $isLast ? 1 : 0, $x, $y, true, 0, false, true, $rowHeight, 'M');
                    } else {
                        $pdf->Cell($widths[$col], $rowHeight, $this->stringifyCell($col, $row[$col]), 1, $isLast ? 1 : 0, 'C', $fillColor);
                    }
                }
            } else {
                foreach ($columns as $i => $col) {
                    $isLast = $i === count($columns) - 1;
                    $pdf->Cell($widths[$col], 7, $this->stringifyCell($col, $row[$col]), 1, $isLast ? 1 : 0, 'C', $fillColor);
                }
            }
            $rowIndex++;
        }
    }

    private function stringifyCell(string $col, mixed $value): string
    {
        if ($col === 'duration') {
            return $this->num($value);
        }
        if (is_numeric($value)) {
            return $this->num($value);
        }

        return (string) $value;
    }

    private function addFooter(TCPDF $pdf, array $meta): void
    {
        $pdf->SetFont('dejavusans', '', 8);
        $pdf->SetTextColor(128, 128, 128);
        $pdf->Cell(0, 5, $meta['academy_name'].' - '.$this->num('Itqan Platform'), 0, 1, 'C');
        $pdf->SetTextColor(0, 0, 0);
    }

    /**
     * Wrap a number with Unicode LTR embedding marks to prevent RTL digit reversal.
     */
    private function num(string|int|float $number): string
    {
        return "\u{202A}".$number."\u{202C}";
    }

    private function formatAmount(float $amount, string $currency): string
    {
        if ($amount <= 0) {
            return '-';
        }

        return $this->num(number_format($amount, 2)).' '.$currency;
    }

    private function resolveColumns(array $requested, array $allowed): array
    {
        $intersect = array_values(array_intersect($allowed, $requested));

        return empty($intersect) ? $allowed : $intersect;
    }

    /**
     * Distribute the available landscape A4 body width (~277mm) across the
     * requested columns proportionally to their baseline widths so the table
     * always fills the page regardless of how many columns the user picked.
     *
     * @param  array<int, string>  $columns
     * @param  array<string, int>  $baselines
     * @return array<string, float>
     */
    private function scaleColumnWidths(array $columns, array $baselines): array
    {
        $sum = 0;
        foreach ($columns as $col) {
            $sum += $baselines[$col] ?? 0;
        }
        if ($sum <= 0) {
            return [];
        }
        $factor = self::PAGE_USABLE_WIDTH_MM / $sum;
        $widths = [];
        foreach ($columns as $col) {
            $widths[$col] = ($baselines[$col] ?? 0) * $factor;
        }

        return $widths;
    }
}
