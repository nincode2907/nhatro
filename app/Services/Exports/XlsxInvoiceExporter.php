<?php

namespace App\Services\Exports;

use App\Data\Exports\InvoiceExportDataset;
use App\Data\Exports\InvoiceExportRecord;
use App\Enums\InvoiceItemType;
use PhpOffice\PhpSpreadsheet\Cell\DataType;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Border;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Worksheet\PageSetup;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;

class XlsxInvoiceExporter
{
    /** @var list<string> */
    private const HEADERS = [
        'Phòng',
        'Giá',
        'Tiền phòng',
        'CSC điện',
        'CSM điện',
        'kWh',
        'Đơn giá điện',
        'Tiền điện',
        'CSC nước',
        'CSM nước',
        'm³',
        'Đơn giá nước',
        'Tiền nước',
        'Xe',
        'Rác',
        'Cáp',
        'Khác',
        'Tổng',
        'Trạng thái',
        'Ghi chú',
    ];

    public function export(InvoiceExportDataset $dataset): string
    {
        $spreadsheet = new Spreadsheet;

        try {
            $spreadsheet->getProperties()
                ->setCreator(config('app.name'))
                ->setTitle('Hóa đơn '.$dataset->period->label())
                ->setSubject('Bảng hóa đơn tháng từ invoice snapshots');

            $sheet = $spreadsheet->getActiveSheet();
            $sheet->setTitle('Thang_'.$dataset->period->starts_on->format('m_Y'));
            $sheet->mergeCells('A1:T1');
            $sheet->setCellValue('A1', $dataset->period->property->name.' — '.$dataset->period->label());
            $sheet->mergeCells('A2:T2');
            $sheet->setCellValue('A2', $dataset->period->property->address ?: 'Bảng hóa đơn theo phòng');
            $sheet->fromArray(self::HEADERS, null, 'A4');

            $row = 5;

            foreach ($dataset->records as $record) {
                $this->writeRecord($sheet, $row, $record);
                $row++;
            }

            $lastDataRow = $row - 1;
            $summaryHeaderRow = $row + 1;
            $summaryEndRow = $this->writeSummary($sheet, $summaryHeaderRow, $dataset);

            $this->styleSheet($sheet, $lastDataRow, $summaryHeaderRow, $summaryEndRow, $dataset);

            return ExportBuffer::capture(
                fn (string $path) => (new Xlsx($spreadsheet))->save($path),
            );
        } finally {
            $spreadsheet->disconnectWorksheets();
        }
    }

    private function writeSummary(
        Worksheet $sheet,
        int $headerRow,
        InvoiceExportDataset $dataset,
    ): int {
        $sheet->mergeCells("A{$headerRow}:B{$headerRow}");
        $sheet->setCellValue("A{$headerRow}", 'TỔNG HỢP');
        $sheet->setCellValue("C{$headerRow}", $dataset->period->label());
        $sheet->mergeCells("D{$headerRow}:H{$headerRow}");
        $sheet->setCellValue(
            "D{$headerRow}",
            $dataset->previousPeriod
                ? 'So với '.$dataset->previousPeriod->label()
                : 'So với tháng trước',
        );

        $summaryRows = [
            ['TỔNG TIỀN PHÒNG', InvoiceItemType::Rent],
            ['TỔNG TIỀN ĐIỆN', InvoiceItemType::Electricity],
            ['TỔNG TIỀN NƯỚC', InvoiceItemType::Water],
            ['TỔNG TIỀN XE', InvoiceItemType::Vehicle],
            ['TỔNG TIỀN RÁC', InvoiceItemType::Garbage],
            ['TỔNG TIỀN CÁP / INTERNET', InvoiceItemType::Cable],
            ['TỔNG KHOẢN KHÁC', InvoiceItemType::Other],
            ['TỔNG CỘNG', null],
        ];

        foreach ($summaryRows as $offset => [$label, $type]) {
            $row = $headerRow + $offset + 1;
            $current = $type ? $dataset->totalFor($type) : $dataset->grandTotal();
            $previous = $type ? $dataset->previousTotalFor($type) : $dataset->previousGrandTotal();

            $sheet->mergeCells("A{$row}:B{$row}");
            $sheet->setCellValue("A{$row}", $label);
            $sheet->setCellValueExplicit("C{$row}", $current, DataType::TYPE_NUMERIC);
            $sheet->mergeCells("D{$row}:H{$row}");

            if ($dataset->previousPeriod) {
                $difference = $current - $previous;
                if ($difference === 0) {
                    $sheet->setCellValue("D{$row}", '→ 0 đ so với tháng trước');
                } else {
                    $sheet->setCellValueExplicit("D{$row}", $difference, DataType::TYPE_NUMERIC);
                }
                $sheet->getStyle("D{$row}")
                    ->getFont()
                    ->getColor()
                    ->setRGB(match (true) {
                        $difference > 0 => '008000',
                        $difference < 0 => 'C00000',
                        default => '60706A',
                    });
            } else {
                $sheet->setCellValue("D{$row}", 'Chưa có dữ liệu tháng trước');
            }
        }

        return $headerRow + count($summaryRows);
    }

    private function writeRecord(Worksheet $sheet, int $row, InvoiceExportRecord $record): void
    {
        $electricity = InvoiceItemType::Electricity;
        $water = InvoiceItemType::Water;
        $values = [
            'A' => $record->invoice->room->room_number,
            'B' => $record->unitPrice(InvoiceItemType::Rent),
            'C' => $record->amount(InvoiceItemType::Rent),
            'D' => $record->previous($electricity),
            'E' => $record->current($electricity),
            'F' => $record->quantity($electricity),
            'G' => $record->unitPrice($electricity),
            'H' => $record->amount($electricity),
            'I' => $record->previous($water),
            'J' => $record->current($water),
            'K' => $record->quantity($water),
            'L' => $record->unitPrice($water),
            'M' => $record->amount($water),
            'N' => $record->amount(InvoiceItemType::Vehicle),
            'O' => $record->amount(InvoiceItemType::Garbage),
            'P' => $record->amount(InvoiceItemType::Cable),
            'Q' => $record->amount(InvoiceItemType::Other),
            'R' => $record->invoice->total,
            'S' => $record->statusLabel(),
            'T' => $record->note() ?? '',
        ];

        foreach ($values as $column => $value) {
            $type = $column === 'A' || $column === 'S' || $column === 'T'
                ? DataType::TYPE_STRING
                : DataType::TYPE_NUMERIC;
            $sheet->setCellValueExplicit("{$column}{$row}", $value, $type);
        }
    }

    private function styleSheet(
        Worksheet $sheet,
        int $lastDataRow,
        int $summaryHeaderRow,
        int $summaryEndRow,
        InvoiceExportDataset $dataset,
    ): void {
        $sheet->freezePane('A5');
        $sheet->getPageSetup()
            ->setOrientation(PageSetup::ORIENTATION_LANDSCAPE)
            ->setPaperSize(PageSetup::PAPERSIZE_A4)
            ->setFitToWidth(1)
            ->setFitToHeight(0);
        $sheet->getPageSetup()->setRowsToRepeatAtTopByStartAndEnd(1, 4);
        $sheet->getPageMargins()
            ->setTop(0.3)
            ->setBottom(0.3)
            ->setLeft(0.3)
            ->setRight(0.3);

        $sheet->getStyle('A1:T1')->applyFromArray([
            'font' => ['bold' => true, 'size' => 16, 'color' => ['rgb' => 'FFFFFF']],
            'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => '116149']],
            'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER],
        ]);
        $sheet->getRowDimension(1)->setRowHeight(28);
        $sheet->getStyle('A2:T2')->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
        $sheet->getStyle('A4:T4')->applyFromArray([
            'font' => ['bold' => true, 'color' => ['rgb' => 'FFFFFF']],
            'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => '263B34']],
            'alignment' => [
                'horizontal' => Alignment::HORIZONTAL_CENTER,
                'vertical' => Alignment::VERTICAL_CENTER,
                'wrapText' => true,
            ],
        ]);
        $sheet->getRowDimension(4)->setRowHeight(34);

        if ($lastDataRow >= 5) {
            $sheet->setAutoFilter("A4:T{$lastDataRow}");
            $sheet->getStyle("A5:T{$lastDataRow}")->applyFromArray([
                'borders' => [
                    'allBorders' => ['borderStyle' => Border::BORDER_THIN, 'color' => ['rgb' => 'D5DDD9']],
                ],
                'alignment' => ['vertical' => Alignment::VERTICAL_CENTER],
            ]);
            $sheet->getStyle("A5:A{$lastDataRow}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
            $sheet->getStyle("B5:R{$lastDataRow}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_RIGHT);
            $sheet->getStyle("S5:S{$lastDataRow}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
            $sheet->getStyle("T5:T{$lastDataRow}")->getAlignment()->setWrapText(true);
            $sheet->getStyle("D5:F{$lastDataRow}")->getNumberFormat()->setFormatCode('#,##0');
            $sheet->getStyle("I5:K{$lastDataRow}")->getNumberFormat()->setFormatCode('#,##0');

            foreach (['B:C', 'G:H', 'L:R'] as $columns) {
                [$start, $end] = explode(':', $columns);
                $sheet->getStyle("{$start}5:{$end}{$lastDataRow}")
                    ->getNumberFormat()
                    ->setFormatCode('#,##0 "đ"');
            }
        }

        $sheet->getStyle("A{$summaryHeaderRow}:H{$summaryHeaderRow}")->applyFromArray([
            'font' => ['bold' => true, 'color' => ['rgb' => 'FFFFFF']],
            'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => '263B34']],
            'alignment' => [
                'horizontal' => Alignment::HORIZONTAL_CENTER,
                'vertical' => Alignment::VERTICAL_CENTER,
            ],
        ]);
        $sheet->getRowDimension($summaryHeaderRow)->setRowHeight(24);

        $summaryStartRow = $summaryHeaderRow + 1;
        $sheet->getStyle("A{$summaryStartRow}:H{$summaryEndRow}")->applyFromArray([
            'borders' => [
                'bottom' => ['borderStyle' => Border::BORDER_THIN, 'color' => ['rgb' => 'D5DDD9']],
            ],
        ]);
        $sheet->getStyle("A{$summaryStartRow}:A{$summaryEndRow}")->getFont()->setBold(true);
        $sheet->getStyle("C{$summaryStartRow}:C{$summaryEndRow}")
            ->getNumberFormat()
            ->setFormatCode('#,##0 "đ"');
        $sheet->getStyle("C{$summaryStartRow}:C{$summaryEndRow}")
            ->getAlignment()
            ->setHorizontal(Alignment::HORIZONTAL_RIGHT);
        $sheet->getStyle("D{$summaryStartRow}:D{$summaryEndRow}")
            ->getAlignment()
            ->setHorizontal(Alignment::HORIZONTAL_CENTER);

        if ($dataset->previousPeriod) {
            $sheet->getStyle("D{$summaryStartRow}:D{$summaryEndRow}")
                ->getNumberFormat()
                ->setFormatCode(
                    '"↑ "#,##0" đ so với tháng trước";"↓ "#,##0" đ so với tháng trước"',
                );
        } else {
            $sheet->getStyle("D{$summaryStartRow}:D{$summaryEndRow}")
                ->getFont()
                ->getColor()
                ->setRGB('60706A');
        }

        $sheet->getStyle("A{$summaryEndRow}:H{$summaryEndRow}")->applyFromArray([
            'font' => ['bold' => true, 'size' => 12],
            'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => 'DFF3EB']],
            'borders' => [
                'top' => ['borderStyle' => Border::BORDER_MEDIUM, 'color' => ['rgb' => '116149']],
            ],
        ]);

        foreach ([
            'A' => 10,
            'B' => 14,
            'C' => 14,
            'D' => 12,
            'E' => 12,
            'F' => 9,
            'G' => 14,
            'H' => 14,
            'I' => 12,
            'J' => 12,
            'K' => 9,
            'L' => 14,
            'M' => 14,
            'N' => 12,
            'O' => 12,
            'P' => 12,
            'Q' => 12,
            'R' => 16,
            'S' => 14,
            'T' => 28,
        ] as $column => $width) {
            $sheet->getColumnDimension($column)->setWidth($width);
        }
    }
}
