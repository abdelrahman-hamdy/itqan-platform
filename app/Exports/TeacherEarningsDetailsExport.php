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

class TeacherEarningsDetailsExport implements FromArray, WithColumnWidths, WithEvents, WithStyles
{
    private const COLUMN_LABEL_KEYS = [
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

    private const COLUMN_WIDTHS = [
        'teacher' => 25,
        'source_type' => 18,
        'source_name' => 28,
        'session_date' => 14,
        'earning_month' => 14,
        'duration' => 12,
        'calculation_method' => 22,
        'amount' => 16,
        'status' => 14,
        'dispute_notes' => 35,
    ];

    private readonly array $columns;

    /**
     * @param  array<int, array<string, string|int|float>>  $rows  Pre-projected detail rows from TeacherEarningsExportService::buildDetailsRow.
     */
    public function __construct(
        private readonly array $rows,
        private readonly array $meta,
        array $columns = [],
    ) {
        $allowed = array_keys(self::COLUMN_LABEL_KEYS);
        $intersect = array_values(array_intersect($allowed, $columns));
        $this->columns = empty($intersect) ? $allowed : $intersect;
    }

    public function array(): array
    {
        $colCount = count($this->columns);
        $sheet = [];

        $titleRow = array_fill(0, $colCount, null);
        $titleRow[0] = $this->meta['academy_name'];
        $sheet[] = $titleRow;

        $reportTitleRow = array_fill(0, $colCount, null);
        $reportTitleRow[0] = __('supervisor.teacher_earnings.export_details_report_title');
        $sheet[] = $reportTitleRow;

        $periodRow = array_fill(0, $colCount, null);
        $periodRow[0] = __('supervisor.teacher_earnings.export_period_label').': '.$this->meta['period_label']
            .'  |  '.__('supervisor.teacher_earnings.export_generated_at').': '.$this->meta['generated_at'];
        $sheet[] = $periodRow;

        $sheet[] = array_fill(0, $colCount, null);

        $sheet[] = array_map(fn ($col) => __(self::COLUMN_LABEL_KEYS[$col]), $this->columns);

        foreach ($this->rows as $row) {
            $sheet[] = array_map(fn ($col) => $row[$col] ?? '', $this->columns);
        }

        return $sheet;
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
        $headerRow = 5;

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
        ];
    }

    public function registerEvents(): array
    {
        $dataCount = count($this->rows);
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
                        'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER, 'wrapText' => true],
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
}
