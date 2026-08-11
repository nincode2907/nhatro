<?php

namespace App\Http\Controllers;

use App\Actions\MeterReadings\ReadingFlow;
use App\Enums\BillingPeriodStatus;
use App\Http\Requests\BillingPeriods\StoreBillingPeriodRequest;
use App\Models\BillingPeriod;
use App\Models\Property;
use Carbon\CarbonImmutable;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

class BillingPeriodController extends Controller
{
    public function index(): View
    {
        $property = $this->configuredProperty();
        $periods = $property?->billingPeriods()
            ->orderByDesc('starts_on')
            ->get() ?? collect();

        return view('billing-periods.index', [
            'property' => $property,
            'periods' => $periods,
            'suggestedPeriodKey' => now()->format('Y-m'),
        ]);
    }

    public function store(StoreBillingPeriodRequest $request): RedirectResponse
    {
        $property = $this->configuredProperty();
        abort_unless($property, 404);

        $periodKey = $request->validated('period_key');
        $startsOn = CarbonImmutable::createFromFormat('Y-m-d', $periodKey.'-01')->startOfDay();

        $period = DB::transaction(fn () => BillingPeriod::query()->firstOrCreate(
            [
                'property_id' => $property->id,
                'period_key' => $periodKey,
            ],
            [
                'starts_on' => $startsOn->toDateString(),
                'ends_on' => $startsOn->endOfMonth()->toDateString(),
                'status' => BillingPeriodStatus::Open,
            ],
        ));

        return redirect()
            ->route('billing-periods.show', $period)
            ->with('status', $period->wasRecentlyCreated
                ? "Đã tạo {$period->label()}."
                : "Đã mở {$period->label()}.");
    }

    public function show(BillingPeriod $period, ReadingFlow $readingFlow): View
    {
        $this->ensureConfiguredPeriod($period);
        $period->loadMissing('property');

        $floors = $period->property->floors()
            ->where('is_active', true)
            ->orderBy('sort_order')
            ->get();

        $floorSummaries = $floors->map(function ($floor) use ($period, $readingFlow): array {
            $rooms = $readingFlow->roomsFor($floor);
            $statuses = $readingFlow->statusesFor($period, $rooms);

            return [
                'floor' => $floor,
                'total' => $rooms->count(),
                'processed' => $readingFlow->processedCount($statuses),
                'next_room' => $readingFlow->nextUnprocessed($period, $floor),
            ];
        });

        $periods = $period->property->billingPeriods()
            ->orderByDesc('starts_on')
            ->get();

        return view('billing-periods.show', compact('period', 'periods', 'floorSummaries'));
    }

    private function configuredProperty(): ?Property
    {
        return Property::query()
            ->where('code', config('property.code'))
            ->where('is_active', true)
            ->first();
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
