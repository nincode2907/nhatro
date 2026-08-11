<?php

namespace App\Services\Exports;

use App\Data\Exports\InvoiceExportDataset;
use App\Data\Exports\InvoiceExportRecord;
use PhpOffice\PhpWord\IOFactory;
use PhpOffice\PhpWord\PhpWord;
use PhpOffice\PhpWord\SimpleType\Jc;
use PhpOffice\PhpWord\SimpleType\JcTable;
use PhpOffice\PhpWord\SimpleType\VerticalJc;
use PhpOffice\PhpWord\Style\Language;

class DocxInvoiceExporter
{
    public function single(InvoiceExportRecord $record): string
    {
        return $this->build([$record]);
    }

    public function batch(InvoiceExportDataset $dataset): string
    {
        return $this->build($dataset->records->all());
    }

    /** @param list<InvoiceExportRecord> $records */
    private function build(array $records): string
    {
        $phpWord = new PhpWord;
        $phpWord->setDefaultFontName('Arial');
        $phpWord->setDefaultFontSize(10);
        $phpWord->getSettings()->setThemeFontLang(new Language('vi-VN'));
        $this->registerStyles($phpWord);
        $section = $phpWord->addSection([
            'orientation' => 'portrait',
            'marginTop' => 700,
            'marginRight' => 700,
            'marginBottom' => 700,
            'marginLeft' => 700,
        ]);

        foreach ($records as $index => $record) {
            if ($index > 0) {
                $section->addPageBreak();
            }

            $this->appendInvoice($section, $record);
        }

        return ExportBuffer::capture(
            fn (string $path) => IOFactory::createWriter($phpWord, 'Word2007')->save($path),
        );
    }

    private function registerStyles(PhpWord $phpWord): void
    {
        $phpWord->addTitleStyle(1, [
            'name' => 'Arial',
            'size' => 16,
            'bold' => true,
            'allCaps' => true,
        ], [
            'alignment' => Jc::CENTER,
            'spaceAfter' => 80,
        ]);
        $phpWord->addTableStyle('InvoiceLines', [
            'borderSize' => 4,
            'borderColor' => 'AEB8B3',
            'cellMargin' => 80,
            'alignment' => JcTable::CENTER,
        ], [
            'bgColor' => '263B34',
        ]);
        $phpWord->addTableStyle('InvoiceTotal', [
            'borderSize' => 0,
            'cellMargin' => 100,
            'alignment' => JcTable::CENTER,
        ]);
    }

    private function appendInvoice($section, InvoiceExportRecord $record): void
    {
        $invoice = $record->invoice;
        $section->addText(
            mb_strtoupper($invoice->billingPeriod->property->name),
            ['bold' => true, 'size' => 13],
            ['alignment' => Jc::CENTER, 'spaceAfter' => 20],
        );

        if ($invoice->billingPeriod->property->address) {
            $section->addText(
                $invoice->billingPeriod->property->address,
                ['size' => 9, 'color' => '555555'],
                ['alignment' => Jc::CENTER, 'spaceAfter' => 80],
            );
        }

        $section->addTitle('Hóa đơn tiền phòng', 1);
        $section->addText(
            $invoice->billingPeriod->label(),
            ['bold' => true, 'size' => 12],
            ['alignment' => Jc::CENTER, 'spaceAfter' => 80],
        );
        $section->addText(
            'PHÒNG '.$invoice->room->room_number,
            ['bold' => true, 'size' => 24, 'color' => '116149'],
            ['alignment' => Jc::CENTER, 'spaceAfter' => 120],
        );

        $table = $section->addTable('InvoiceLines');
        $table->addRow(420, ['tblHeader' => true]);

        foreach ([
            ['Khoản thu', 3400],
            ['Số lượng', 1500],
            ['Đơn giá', 1800],
            ['Thành tiền', 2000],
        ] as [$heading, $width]) {
            $table->addCell($width, ['bgColor' => '263B34', 'valign' => VerticalJc::CENTER])
                ->addText($heading, ['bold' => true, 'color' => 'FFFFFF'], ['alignment' => Jc::CENTER]);
        }

        foreach ($record->document->rows() as $row) {
            $item = $row['item'];
            $table->addRow();
            $description = $row['label'];

            if ($item?->metadata && isset($item->metadata['previous'], $item->metadata['current'])) {
                $description .= sprintf(
                    ' — Cũ %s → Mới %s',
                    number_format($item->metadata['previous'], 0, ',', '.'),
                    number_format($item->metadata['current'], 0, ',', '.'),
                );
            } elseif (! $item) {
                $description .= ' — Không phát sinh';
            }

            $table->addCell(3400)->addText($description, ['bold' => true]);
            $table->addCell(1500)->addText(
                $item ? number_format($item->quantity, 0, ',', '.').' '.$item->unit : '—',
                null,
                ['alignment' => Jc::RIGHT],
            );
            $table->addCell(1800)->addText(
                $item ? number_format($item->unit_price, 0, ',', '.').' đ' : '—',
                null,
                ['alignment' => Jc::RIGHT],
            );
            $table->addCell(2000)->addText(
                $item ? number_format($item->amount, 0, ',', '.').' đ' : '—',
                ['bold' => true],
                ['alignment' => Jc::RIGHT],
            );
        }

        $section->addTextBreak(1, ['size' => 3]);
        $totalTable = $section->addTable('InvoiceTotal');
        $totalTable->addRow(650);
        $totalTable->addCell(4350, ['bgColor' => '111111', 'valign' => VerticalJc::CENTER])
            ->addText('TỔNG CỘNG', ['bold' => true, 'color' => 'FFFFFF', 'size' => 12]);
        $totalTable->addCell(4350, ['bgColor' => '111111', 'valign' => VerticalJc::CENTER])
            ->addText(
                number_format($invoice->total, 0, ',', '.').' đ',
                ['bold' => true, 'color' => 'FFFFFF', 'size' => 18],
                ['alignment' => Jc::RIGHT],
            );

        $section->addText(
            'Ghi chú: '.($record->note() ?: 'Không có'),
            ['size' => 9],
            ['spaceBefore' => 100, 'spaceAfter' => 100],
        );
        $section->addText(
            'Ngày '.($invoice->generated_at?->format('d/m/Y') ?? '...../...../..........'),
            ['size' => 9],
            ['alignment' => Jc::RIGHT, 'spaceAfter' => 80],
        );

        $signatures = $section->addTable(['alignment' => JcTable::CENTER]);
        $signatures->addRow();
        $signatures->addCell(4350)->addText(
            "Người thu\n(Ký và ghi rõ họ tên)",
            ['bold' => true],
            ['alignment' => Jc::CENTER],
        );
        $signatures->addCell(4350)->addText(
            "Người thuê\n(Ký và ghi rõ họ tên)",
            ['bold' => true],
            ['alignment' => Jc::CENTER],
        );
        $section->addTextBreak(3);
    }
}
