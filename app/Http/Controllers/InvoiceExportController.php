<?php

namespace App\Http\Controllers;

use App\Models\BillingPeriod;
use App\Models\Invoice;
use App\Services\Exports\DocxInvoiceExporter;
use App\Services\Exports\ExportFilename;
use App\Services\Exports\InvoiceExportDataFactory;
use App\Services\Exports\PdfInvoiceExporter;
use App\Services\Exports\XlsxInvoiceExporter;
use Illuminate\Http\Response;

class InvoiceExportController extends Controller
{
    public function xlsx(
        BillingPeriod $period,
        InvoiceExportDataFactory $dataFactory,
        XlsxInvoiceExporter $exporter,
        ExportFilename $filename,
    ): Response {
        $this->ensureConfiguredPeriod($period);
        $dataset = $dataFactory->forPeriod($period);

        return $this->download(
            $exporter->export($dataset),
            $filename->monthly($period, 'xlsx'),
            'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
        );
    }

    public function pdfBatch(
        BillingPeriod $period,
        InvoiceExportDataFactory $dataFactory,
        PdfInvoiceExporter $exporter,
        ExportFilename $filename,
    ): Response {
        $this->ensureConfiguredPeriod($period);
        $dataset = $dataFactory->forPeriod($period);

        return $this->download(
            $exporter->batch($dataset),
            $filename->monthly($period, 'pdf'),
            'application/pdf',
        );
    }

    public function docxBatch(
        BillingPeriod $period,
        InvoiceExportDataFactory $dataFactory,
        DocxInvoiceExporter $exporter,
        ExportFilename $filename,
    ): Response {
        $this->ensureConfiguredPeriod($period);
        $dataset = $dataFactory->forPeriod($period);

        return $this->download(
            $exporter->batch($dataset),
            $filename->monthly($period, 'docx'),
            'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
        );
    }

    public function pdfSingle(
        BillingPeriod $period,
        Invoice $invoice,
        InvoiceExportDataFactory $dataFactory,
        PdfInvoiceExporter $exporter,
        ExportFilename $filename,
    ): Response {
        $invoice = $this->invoiceForPeriod($period, $invoice);

        return $this->download(
            $exporter->single($dataFactory->forInvoice($invoice)),
            $filename->single($invoice, 'pdf'),
            'application/pdf',
        );
    }

    public function docxSingle(
        BillingPeriod $period,
        Invoice $invoice,
        InvoiceExportDataFactory $dataFactory,
        DocxInvoiceExporter $exporter,
        ExportFilename $filename,
    ): Response {
        $invoice = $this->invoiceForPeriod($period, $invoice);

        return $this->download(
            $exporter->single($dataFactory->forInvoice($invoice)),
            $filename->single($invoice, 'docx'),
            'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
        );
    }

    private function ensureConfiguredPeriod(BillingPeriod $period): void
    {
        $belongsToProperty = $period->property()
            ->where('code', config('property.code'))
            ->where('is_active', true)
            ->exists();

        abort_unless($belongsToProperty, 404);
    }

    private function invoiceForPeriod(BillingPeriod $period, Invoice $invoice): Invoice
    {
        $this->ensureConfiguredPeriod($period);
        abort_unless($invoice->billing_period_id === $period->id, 404);

        return $invoice;
    }

    private function download(string $content, string $filename, string $contentType): Response
    {
        return response($content, 200, [
            'Content-Type' => $contentType,
            'Content-Disposition' => 'attachment; filename="'.$filename.'"',
            'Content-Length' => (string) strlen($content),
            'X-Content-Type-Options' => 'nosniff',
        ]);
    }
}
