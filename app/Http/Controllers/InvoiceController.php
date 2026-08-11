<?php

namespace App\Http\Controllers;

use App\Models\BillingPeriod;
use App\Models\Invoice;
use Illuminate\View\View;

class InvoiceController extends Controller
{
    public function index(BillingPeriod $period): View
    {
        $this->ensureConfiguredPeriod($period);
        $period->loadMissing('property');

        $invoices = $period->invoices()
            ->with(['room.floor', 'items'])
            ->get()
            ->sortBy([
                ['room.floor.sort_order', 'asc'],
                ['room.sort_order', 'asc'],
                ['room.room_number', 'asc'],
            ])
            ->values();

        return view('invoices.index', compact('period', 'invoices'));
    }

    public function show(BillingPeriod $period, Invoice $invoice): View
    {
        $this->ensureConfiguredPeriod($period);
        abort_unless($invoice->billing_period_id === $period->id, 404);

        $invoice->loadMissing(['billingPeriod.property', 'room.floor', 'items']);

        return view('invoices.show', compact('period', 'invoice'));
    }

    private function ensureConfiguredPeriod(BillingPeriod $period): void
    {
        $belongsToProperty = $period->property()
            ->where('code', config('property.code'))
            ->where('is_active', true)
            ->exists();

        abort_unless($belongsToProperty, 404);
    }
}
