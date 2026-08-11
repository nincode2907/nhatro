<?php

namespace App\Services\Exports;

use App\Data\Exports\InvoiceExportDataset;
use App\Data\Exports\InvoiceExportRecord;
use Dompdf\Dompdf;
use Dompdf\Options;

class PdfInvoiceExporter
{
    public function single(InvoiceExportRecord $record): string
    {
        return $this->render(
            view('exports.pdf.single', compact('record'))->render(),
            'A5',
        );
    }

    public function batch(InvoiceExportDataset $dataset): string
    {
        $sheets = $dataset->records->chunk(3);

        return $this->render(
            view('exports.pdf.batch', compact('dataset', 'sheets'))->render(),
            'A4',
        );
    }

    private function render(string $html, string $paper): string
    {
        $options = new Options;
        $options->set('defaultFont', 'DejaVu Sans');
        $options->set('isRemoteEnabled', false);
        $options->set('isPhpEnabled', false);
        $options->set('isFontSubsettingEnabled', true);
        $options->set('tempDir', sys_get_temp_dir());

        $dompdf = new Dompdf($options);
        $dompdf->loadHtml($html, 'UTF-8');
        $dompdf->setPaper($paper, 'portrait');
        $dompdf->render();

        return $dompdf->output();
    }
}
