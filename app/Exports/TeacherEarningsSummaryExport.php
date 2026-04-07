<?php

namespace App\Exports;

use Maatwebsite\Excel\Concerns\FromArray;
use Maatwebsite\Excel\Concerns\WithColumnWidths;
use Maatwebsite\Excel\Concerns\WithEvents;
use Maatwebsite\Excel\Concerns\WithStyles;
use Maatwebsite\Excel\Events\AfterSheet;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Border;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

class TeacherEarningsSummaryExport implements FromArray, WithColumnWidths, WithEvents, WithStyles
{
    public function __construct(
        private readonly array $teacherSummaries,
        private readonly array $profileUserMap,
        private readonly array $meta,
    ) {}

    public function array(): array
    {
        $currency = $this->meta['currency_symbol'];
        $rows = [];

        // Title rows
        $rows[] = [$this->meta['academy_name']];
        $rows[] = [__('supervisor.teacher_earnings.export_report_title')];
        $rows[] = [__('supervisor.teacher_earnings.export_period_label').': '.$this->meta['period_label'].'  |  '.__('supervisor.teacher_earnings.export_generated_at').': '.$this->meta['generated_at']];
        $rows[] = []; // Empty spacer row

        // Header row
        $rows[] = [
            __('supervisor.teacher_earnings.summary_teacher_name'),
            __('supervisor.teacher_earnings.summary_quran_individual'),
            __('supervisor.teacher_earnings.summary_quran_group'),
            __('supervisor.teacher_earnings.summary_academic'),
            __('supervisor.teacher_earnings.summary_interactive'),
            __('supervisor.teacher_earnings.summary_sessions_count'),
            __('supervisor.teacher_earnings.summary_total_hours'),
            __('supervisor.teacher_earnings.summary_total'),
        ];

        // Data rows
        $totals = [
            'quran_individual' => 0,
            'quran_group' => 0,
            'academic' => 0,
            'interactive' => 0,
            'sessions_count' => 0,
            'total_duration_minutes' => 0,
            'total' => 0,
        ];

        foreach ($this->teacherSummaries as $summary) {
            $profileKey = $summary['teacher_type'].'_'.$summary['teacher_id'];
            $teacherUser = $this->profileUserMap[$profileKey] ?? null;
            $teacherName = $teacherUser?->name ?? __('common.unknown');
            $hours = round($summary['total_duration_minutes'] / 60, 1);

            $rows[] = [
                $teacherName,
                $this->formatAmount($summary['quran_individual']['amount'], $currency),
                $this->formatAmount($summary['quran_group']['amount'], $currency),
                $this->formatAmount($summary['academic']['amount'], $currency),
                $this->formatAmount($summary['interactive']['amount'], $currency),
                $summary['sessions_count'],
                $hours.' '.__('supervisor.teacher_earnings.hours_unit'),
                number_format($summary['total'], 2).' '.$currency,
            ];

            $totals['quran_individual'] += $summary['quran_individual']['amount'];
            $totals['quran_group'] += $summary['quran_group']['amount'];
            $totals['academic'] += $summary['academic']['amount'];
            $totals['interactive'] += $summary['interactive']['amount'];
            $totals['sessions_count'] += $summary['sessions_count'];
            $totals['total_duration_minutes'] += $summary['total_duration_minutes'];
            $totals['total'] += $summary['total'];
        }

        $totalHours = round($totals['total_duration_minutes'] / 60, 1);

        // Totals row
        $rows[] = [
            __('supervisor.teacher_earnings.summary_total'),
            $this->formatAmount($totals['quran_individual'], $currency),
            $this->formatAmount($totals['quran_group'], $currency),
            $this->formatAmount($totals['academic'], $currency),
            $this->formatAmount($totals['interactive'], $currency),
            $totals['sessions_count'],
            $totalHours.' '.__('supervisor.teacher_earnings.hours_unit'),
            number_format($totals['total'], 2).' '.$currency,
        ];

        return $rows;
    }

    public function columnWidths(): array
    {
        return [
            'A' => 30,
            'B' => 20,
            'C' => 20,
            'D' => 20,
            'E' => 20,
            'F' => 15,
            'G' => 15,
            'H' => 20,
        ];
    }

    public function styles(Worksheet $sheet): array
    {
        $dataCount = count($this->teacherSummaries);
        $headerRow = 5;
        $lastDataRow = $headerRow + $dataCount;
        $totalsRow = $lastDataRow + 1;

        return [
            // Title rows
            1 => ['font' => ['bold' => true, 'size' => 14], 'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER]],
            2 => ['font' => ['bold' => true, 'size' => 12], 'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER]],
            3 => ['font' => ['size' => 10, 'color' => ['rgb' => '808080']], 'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER]],
            // Header row
            $headerRow => [
                'font' => ['bold' => true, 'size' => 10],
                'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER, 'vertical' => Alignment::VERTICAL_CENTER],
                'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => 'F0F0F0']],
                'borders' => ['allBorders' => ['borderStyle' => Border::BORDER_THIN]],
            ],
            // Totals row
            $totalsRow => [
                'font' => ['bold' => true, 'size' => 10],
                'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER],
                'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => 'E6E6E6']],
                'borders' => ['allBorders' => ['borderStyle' => Border::BORDER_THIN]],
            ],
        ];
    }

    public function registerEvents(): array
    {
        $dataCount = count($this->teacherSummaries);

        return [
            AfterSheet::class => function (AfterSheet $event) use ($dataCount) {
                $sheet = $event->sheet->getDelegate();

                // RTL for Arabic
                $sheet->setRightToLeft(true);

                // Merge title rows across all columns
                $sheet->mergeCells('A1:H1');
                $sheet->mergeCells('A2:H2');
                $sheet->mergeCells('A3:H3');

                // Style data rows
                $headerRow = 5;
                $lastDataRow = $headerRow + $dataCount;

                for ($row = $headerRow + 1; $row <= $lastDataRow; $row++) {
                    $sheet->getStyle("A{$row}:H{$row}")->applyFromArray([
                        'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER],
                        'borders' => ['allBorders' => ['borderStyle' => Border::BORDER_THIN, 'color' => ['rgb' => 'E0E0E0']]],
                    ]);

                    // Alternating row colors
                    if (($row - $headerRow) % 2 === 0) {
                        $sheet->getStyle("A{$row}:H{$row}")->applyFromArray([
                            'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => 'FAFAFA']],
                        ]);
                    }
                }
            },
        ];
    }

    private function formatAmount(float $amount, string $currency): string
    {
        if ($amount <= 0) {
            return '-';
        }

        return number_format($amount, 2).' '.$currency;
    }
}
