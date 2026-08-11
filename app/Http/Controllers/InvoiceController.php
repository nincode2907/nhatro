<?php

namespace App\Http\Controllers;

use App\Models\BillingPeriod;
use App\Models\Invoice;
use App\ViewModels\InvoiceDocument;
use Illuminate\Support\Collection;
use Illuminate\View\View;

class InvoiceController extends Controller
{
    public function index(BillingPeriod $period): View
    {
        $this->ensureConfiguredPeriod($period);
        $period->loadMissing('property');

        $invoices = $this->invoicesForPeriod($period);

        return view('invoices.index', compact('period', 'invoices'));
    }

    public function show(BillingPeriod $period, Invoice $invoice): View
    {
        $invoice = $this->invoiceForPeriod($period, $invoice);
        $document = new InvoiceDocument($invoice);

        return view('invoices.show', compact('period', 'invoice', 'document'));
    }

    public function printSingle(BillingPeriod $period, Invoice $invoice): View
    {
        $invoice = $this->invoiceForPeriod($period, $invoice);
        $document = new InvoiceDocument($invoice);

        return view('invoices.print-single', compact('period', 'invoice', 'document'));
    }

    public function printBatch(BillingPeriod $period): View
    {
        $this->ensureConfiguredPeriod($period);
        $period->loadMissing('property');
        $documents = $this->invoicesForPeriod($period)
            ->map(fn (Invoice $invoice): InvoiceDocument => new InvoiceDocument($invoice));
        $sheets = $documents->chunk(3);

        return view('invoices.print-batch', compact('period', 'documents', 'sheets'));
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

        return $invoice->loadMissing(['billingPeriod.property', 'room.floor', 'items']);
    }

    /** @return Collection<int, Invoice> */
    private function invoicesForPeriod(BillingPeriod $period): Collection
    {
        return $period->invoices()
            ->with(['billingPeriod.property', 'room.floor', 'items'])
            ->get()
            ->sortBy([
                ['room.floor.sort_order', 'asc'],
                ['room.sort_order', 'asc'],
                ['room.room_number', 'asc'],
            ])
            ->values();
    }
}
