<?php

namespace App\Http\Controllers;

use App\Http\Requests\Properties\UpdateDefaultPricesRequest;
use App\Models\Property;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;

class DefaultPricesController extends Controller
{
    public function edit(): View
    {
        $property = $this->configuredProperty();

        return view('properties.default-prices', ['prices' => $property->defaultRoomPrices()]);
    }

    public function update(UpdateDefaultPricesRequest $request): RedirectResponse
    {
        $this->configuredProperty()->update([
            'default_room_prices' => array_map(fn ($value) => (int) $value, $request->validated()),
        ]);

        return redirect()->route('default-prices.edit')->with('status', 'Đã lưu giá mặc định cho phòng mới.');
    }

    private function configuredProperty(): Property
    {
        return Property::query()->where('code', config('property.code'))->where('is_active', true)->firstOrFail();
    }
}
