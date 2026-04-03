<?php

namespace App\Services;

use TCPDF;

class TeacherEarningsExportService
{
    /**
     * Generate a teacher earnings summary PDF in memory.
     *
     * @param  array  $teacherSummaries  Teacher summary data from the controller
     * @param  array  $profileUserMap  Maps "teacher_type_id" => User
     * @param  array  $meta  Contains: academy_name, currency_symbol, period_label, generated_at
     * @return string Raw PDF content
     */
    public function generateSummaryPdf(array $teacherSummaries, array $profileUserMap, array $meta): string
    {
        $pdf = $this->createPdf($meta);
        $pdf->AddPage();
        $pdf->setRTL(true);

        $this->addHeader($pdf, $meta);
        $pdf->Ln(6);
        $this->addSummaryTable($pdf, $teacherSummaries, $profileUserMap, $meta);
        $pdf->Ln(8);
        $this->addFooter($pdf, $meta);

        return $pdf->Output('', 'S');
    }

    protected function createPdf(array $meta): TCPDF
    {
        $pdf = new TCPDF('L', 'mm', 'A4', true, 'UTF-8', false);

        $pdf->SetCreator('Itqan Platform');
        $pdf->SetAuthor($meta['academy_name']);
        $pdf->SetTitle(__('supervisor.teacher_earnings.export_report_title'));
        $pdf->setPrintHeader(false);
        $pdf->setPrintFooter(false);
        $pdf->SetMargins(10, 10, 10);
        $pdf->SetAutoPageBreak(true, 15);

        return $pdf;
    }

    protected function addHeader(TCPDF $pdf, array $meta): void
    {
        $pdf->SetFont('dejavusans', 'B', 14);
        $pdf->Cell(0, 10, $meta['academy_name'], 0, 1, 'C');

        $pdf->SetFont('dejavusans', 'B', 12);
        $pdf->Cell(0, 8, __('supervisor.teacher_earnings.export_report_title'), 0, 1, 'C');

        $pdf->SetFont('dejavusans', '', 10);
        $pdf->Cell(0, 7, __('supervisor.teacher_earnings.export_period_label').': '.$meta['period_label'], 0, 1, 'C');

        $pdf->SetFont('dejavusans', '', 9);
        $pdf->SetTextColor(128, 128, 128);
        $pdf->Cell(0, 6, __('supervisor.teacher_earnings.export_generated_at').': '.$meta['generated_at'], 0, 1, 'C');
        $pdf->SetTextColor(0, 0, 0);
    }

    protected function addSummaryTable(TCPDF $pdf, array $summaries, array $profileUserMap, array $meta): void
    {
        $currency = $meta['currency_symbol'];

        // Column widths for landscape A4 (usable width ~277mm with 10mm margins)
        $colWidths = [
            'teacher' => 50,
            'quran_individual' => 34,
            'quran_group' => 34,
            'academic' => 34,
            'interactive' => 34,
            'sessions' => 22,
            'hours' => 24,
            'total' => 35,
        ];

        // Table header
        $pdf->SetFont('dejavusans', 'B', 8);
        $pdf->SetFillColor(240, 240, 240);

        $pdf->Cell($colWidths['total'], 8, __('supervisor.teacher_earnings.summary_total'), 1, 0, 'C', true);
        $pdf->Cell($colWidths['hours'], 8, __('supervisor.teacher_earnings.summary_total_hours'), 1, 0, 'C', true);
        $pdf->Cell($colWidths['sessions'], 8, __('supervisor.teacher_earnings.summary_sessions_count'), 1, 0, 'C', true);
        $pdf->Cell($colWidths['interactive'], 8, __('supervisor.teacher_earnings.summary_interactive'), 1, 0, 'C', true);
        $pdf->Cell($colWidths['academic'], 8, __('supervisor.teacher_earnings.summary_academic'), 1, 0, 'C', true);
        $pdf->Cell($colWidths['quran_group'], 8, __('supervisor.teacher_earnings.summary_quran_group'), 1, 0, 'C', true);
        $pdf->Cell($colWidths['quran_individual'], 8, __('supervisor.teacher_earnings.summary_quran_individual'), 1, 0, 'C', true);
        $pdf->Cell($colWidths['teacher'], 8, __('supervisor.teacher_earnings.summary_teacher_name'), 1, 1, 'C', true);

        // Table body
        $pdf->SetFont('dejavusans', '', 8);
        $rowIndex = 0;
        $totals = [
            'quran_individual' => 0,
            'quran_group' => 0,
            'academic' => 0,
            'interactive' => 0,
            'sessions_count' => 0,
            'total_duration_minutes' => 0,
            'total' => 0,
        ];

        foreach ($summaries as $summary) {
            $profileKey = $summary['teacher_type'].'_'.$summary['teacher_id'];
            $teacherUser = $profileUserMap[$profileKey] ?? null;
            $teacherName = $teacherUser?->name ?? __('common.unknown');

            $fillColor = $rowIndex % 2 === 1;
            if ($fillColor) {
                $pdf->SetFillColor(250, 250, 250);
            }

            $hours = round($summary['total_duration_minutes'] / 60, 1);

            $pdf->Cell($colWidths['total'], 7, number_format($summary['total'], 2).' '.$currency, 1, 0, 'C', $fillColor);
            $pdf->Cell($colWidths['hours'], 7, $hours.' '.__('supervisor.teacher_earnings.hours_unit'), 1, 0, 'C', $fillColor);
            $pdf->Cell($colWidths['sessions'], 7, $summary['sessions_count'], 1, 0, 'C', $fillColor);
            $pdf->Cell($colWidths['interactive'], 7, $this->formatAmount($summary['interactive']['amount'], $currency), 1, 0, 'C', $fillColor);
            $pdf->Cell($colWidths['academic'], 7, $this->formatAmount($summary['academic']['amount'], $currency), 1, 0, 'C', $fillColor);
            $pdf->Cell($colWidths['quran_group'], 7, $this->formatAmount($summary['quran_group']['amount'], $currency), 1, 0, 'C', $fillColor);
            $pdf->Cell($colWidths['quran_individual'], 7, $this->formatAmount($summary['quran_individual']['amount'], $currency), 1, 0, 'C', $fillColor);
            $pdf->Cell($colWidths['teacher'], 7, $teacherName, 1, 1, 'R', $fillColor);

            // Accumulate totals
            $totals['quran_individual'] += $summary['quran_individual']['amount'];
            $totals['quran_group'] += $summary['quran_group']['amount'];
            $totals['academic'] += $summary['academic']['amount'];
            $totals['interactive'] += $summary['interactive']['amount'];
            $totals['sessions_count'] += $summary['sessions_count'];
            $totals['total_duration_minutes'] += $summary['total_duration_minutes'];
            $totals['total'] += $summary['total'];

            $rowIndex++;
        }

        // Footer row
        $pdf->SetFont('dejavusans', 'B', 8);
        $pdf->SetFillColor(230, 230, 230);

        $totalHours = round($totals['total_duration_minutes'] / 60, 1);

        $pdf->Cell($colWidths['total'], 8, number_format($totals['total'], 2).' '.$currency, 1, 0, 'C', true);
        $pdf->Cell($colWidths['hours'], 8, $totalHours.' '.__('supervisor.teacher_earnings.hours_unit'), 1, 0, 'C', true);
        $pdf->Cell($colWidths['sessions'], 8, $totals['sessions_count'], 1, 0, 'C', true);
        $pdf->Cell($colWidths['interactive'], 8, $this->formatAmount($totals['interactive'], $currency), 1, 0, 'C', true);
        $pdf->Cell($colWidths['academic'], 8, $this->formatAmount($totals['academic'], $currency), 1, 0, 'C', true);
        $pdf->Cell($colWidths['quran_group'], 8, $this->formatAmount($totals['quran_group'], $currency), 1, 0, 'C', true);
        $pdf->Cell($colWidths['quran_individual'], 8, $this->formatAmount($totals['quran_individual'], $currency), 1, 0, 'C', true);
        $pdf->Cell($colWidths['teacher'], 8, __('supervisor.teacher_earnings.summary_total'), 1, 1, 'R', true);
    }

    protected function addFooter(TCPDF $pdf, array $meta): void
    {
        $pdf->SetFont('dejavusans', '', 8);
        $pdf->SetTextColor(128, 128, 128);
        $pdf->Cell(0, 5, 'Itqan Platform - '.$meta['academy_name'], 0, 1, 'C');
        $pdf->SetTextColor(0, 0, 0);
    }

    private function formatAmount(float $amount, string $currency): string
    {
        if ($amount <= 0) {
            return '-';
        }

        return number_format($amount, 2).' '.$currency;
    }
}
