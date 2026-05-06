<?php

namespace App\Exports;

use Maatwebsite\Excel\Concerns\FromArray;
use Maatwebsite\Excel\Concerns\WithColumnWidths;
use Maatwebsite\Excel\Concerns\WithEvents;
use Maatwebsite\Excel\Concerns\WithStyles;
use Maatwebsite\Excel\Events\AfterSheet;
use PhpOffice\PhpSpreadsheet\Cell\Coordinate;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Border;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

class TeacherEarningsSummaryExport implements FromArray, WithColumnWidths, WithEvents, WithStyles
{
    private const COLUMN_LABEL_KEYS = [
        'teacher' => 'supervisor.teacher_earnings.summary_teacher_name',
        'quran_individual' => 'supervisor.teacher_earnings.summary_quran_individual',
        'quran_group' => 'supervisor.teacher_earnings.summary_quran_group',
        'academic' => 'supervisor.teacher_earnings.summary_academic',
        'interactive' => 'supervisor.teacher_earnings.summary_interactive',
        'sessions' => 'supervisor.teacher_earnings.summary_sessions_count',
        'hours' => 'supervisor.teacher_earnings.summary_total_hours',
        'total' => 'supervisor.teacher_earnings.summary_total',
    ];

    private const COLUMN_WIDTHS = [
        'teacher' => 30,
        'quran_individual' => 20,
        'quran_group' => 20,
        'academic' => 20,
        'interactive' => 20,
        'sessions' => 15,
        'hours' => 15,
        'total' => 20,
    ];

    private readonly array $columns;

    public function __construct(
        private readonly array $teacherSummaries,
        private readonly array $profileUserMap,
        private readonly array $meta,
        array $columns = [],
    ) {
        $allowed = array_keys(self::COLUMN_LABEL_KEYS);
        $intersect = array_values(array_intersect($allowed, $columns));
        $this->columns = empty($intersect) ? $allowed : $intersect;
    }

    public function array(): array
    {
        $currency = $this->meta['currency_symbol'];
        $colCount = count($this->columns);
        $rows = [];

        $titleRow = array_fill(0, $colCount, null);
        $titleRow[0] = $this->meta['academy_name'];
        $rows[] = $titleRow;

        $reportTitleRow = array_fill(0, $colCount, null);
        $reportTitleRow[0] = __('supervisor.teacher_earnings.export_report_title');
        $rows[] = $reportTitleRow;

        $periodRow = array_fill(0, $colCount, null);
        $periodRow[0] = __('supervisor.teacher_earnings.export_period_label').': '.$this->meta['period_label']
            .'  |  '.__('supervisor.teacher_earnings.export_generated_at').': '.$this->meta['generated_at'];
        $rows[] = $periodRow;

        $rows[] = array_fill(0, $colCount, null);

        $rows[] = array_map(fn ($col) => __(self::COLUMN_LABEL_KEYS[$col]), $this->columns);

        $totals = [
            'quran_individual' => 0.0,
            'quran_group' => 0.0,
            'academic' => 0.0,
            'interactive' => 0.0,
            'sessions_count' => 0,
            'total_duration_minutes' => 0,
            'total' => 0.0,
        ];

        foreach ($this->teacherSummaries as $summary) {
            $profileKey = $summary['teacher_type'].'_'.$summary['teacher_id'];
            $teacherUser = $this->profileUserMap[$profileKey] ?? null;
            $teacherName = $teacherUser?->name ?? __('common.unknown');
            $hours = round($summary['total_duration_minutes'] / 60, 2);

            $cellValues = [
                'teacher' => $teacherName,
                'quran_individual' => $this->formatAmount($summary['quran_individual']['amount'], $currency),
                'quran_group' => $this->formatAmount($summary['quran_group']['amount'], $currency),
                'academic' => $this->formatAmount($summary['academic']['amount'], $currency),
                'interactive' => $this->formatAmount($summary['interactive']['amount'], $currency),
                'sessions' => $summary['sessions_count'],
                'hours' => $hours.' '.__('supervisor.teacher_earnings.hours_unit'),
                'total' => number_format($summary['total'], 2).' '.$currency,
            ];

            $rows[] = array_map(fn ($col) => $cellValues[$col], $this->columns);

            $totals['quran_individual'] += $summary['quran_individual']['amount'];
            $totals['quran_group'] += $summary['quran_group']['amount'];
            $totals['academic'] += $summary['academic']['amount'];
            $totals['interactive'] += $summary['interactive']['amount'];
            $totals['sessions_count'] += $summary['sessions_count'];
            $totals['total_duration_minutes'] += $summary['total_duration_minutes'];
            $totals['total'] += $summary['total'];
        }

        $totalHours = round($totals['total_duration_minutes'] / 60, 2);
        $totalsCells = [
            'teacher' => __('supervisor.teacher_earnings.summary_total'),
            'quran_individual' => $this->formatAmount($totals['quran_individual'], $currency),
            'quran_group' => $this->formatAmount($totals['quran_group'], $currency),
            'academic' => $this->formatAmount($totals['academic'], $currency),
            'interactive' => $this->formatAmount($totals['interactive'], $currency),
            'sessions' => $totals['sessions_count'],
            'hours' => $totalHours.' '.__('supervisor.teacher_earnings.hours_unit'),
            'total' => number_format($totals['total'], 2).' '.$currency,
        ];
        $rows[] = array_map(fn ($col) => $totalsCells[$col], $this->columns);

        return $rows;
    }

    public function columnWidths(): array
    {
        $widths = [];
        foreach ($this->columns as $i => $col) {
            $letter = Coordinate::stringFromColumnIndex($i + 1);
            $widths[$letter] = self::COLUMN_WIDTHS[$col];
        }

        return $widths;
    }

    public function styles(Worksheet $sheet): array
    {
        $dataCount = count($this->teacherSummaries);
        $headerRow = 5;
        $totalsRow = $headerRow + $dataCount + 1;

        return [
            1 => ['font' => ['bold' => true, 'size' => 14], 'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER]],
            2 => ['font' => ['bold' => true, 'size' => 12], 'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER]],
            3 => ['font' => ['size' => 10, 'color' => ['rgb' => '808080']], 'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER]],
            $headerRow => [
                'font' => ['bold' => true, 'size' => 10],
                'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER, 'vertical' => Alignment::VERTICAL_CENTER],
                'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => 'F0F0F0']],
                'borders' => ['allBorders' => ['borderStyle' => Border::BORDER_THIN]],
            ],
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
        $colCount = count($this->columns);

        return [
            AfterSheet::class => function (AfterSheet $event) use ($dataCount, $colCount) {
                $sheet = $event->sheet->getDelegate();
                $sheet->setRightToLeft(true);

                $lastCol = Coordinate::stringFromColumnIndex($colCount);
                $sheet->mergeCells("A1:{$lastCol}1");
                $sheet->mergeCells("A2:{$lastCol}2");
                $sheet->mergeCells("A3:{$lastCol}3");

                $headerRow = 5;
                $lastDataRow = $headerRow + $dataCount;

                for ($row = $headerRow + 1; $row <= $lastDataRow; $row++) {
                    $sheet->getStyle("A{$row}:{$lastCol}{$row}")->applyFromArray([
                        'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER],
                        'borders' => ['allBorders' => ['borderStyle' => Border::BORDER_THIN, 'color' => ['rgb' => 'E0E0E0']]],
                    ]);

                    if (($row - $headerRow) % 2 === 0) {
                        $sheet->getStyle("A{$row}:{$lastCol}{$row}")->applyFromArray([
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
