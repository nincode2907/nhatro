<?php

namespace Database\Seeders;

use App\Enums\RoomStatus;
use App\Models\Floor;
use App\Models\Property;
use App\Models\Room;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use RuntimeException;

class DemoPropertySeeder extends Seeder
{
    /** @var array<string, int> */
    private const FLOOR_ROOM_COUNTS = [
        '1' => 11,
        '2' => 11,
        '3' => 11,
        '4' => 12,
    ];

    /** @var array<string, int|bool> */
    private const DEFAULT_SETTINGS = [
        'rent_amount' => 3_000_000,
        'electricity_unit_price' => 3_200,
        'water_unit_price' => 17_000,
        'vehicle_amount' => 120_000,
        'garbage_amount' => 30_000,
        'cable_amount' => 0,
        'other_amount' => 0,
        'electricity_enabled' => true,
        'water_enabled' => true,
    ];

    public function run(): void
    {
        $code = trim((string) config('property.code'));
        $name = trim((string) config('property.name'));

        if ($code === '' || $name === '') {
            throw new RuntimeException('PROPERTY_CODE and PROPERTY_NAME must be configured before seeding demo data.');
        }

        $seeded = DB::transaction(function () use ($code, $name): bool {
            $property = Property::query()->firstOrCreate(
                ['code' => $code],
                [
                    'name' => $name,
                    'address' => config('property.address') ?: null,
                    'is_active' => true,
                ],
            );

            if ($property->floors()->exists()) {
                return false;
            }

            foreach (self::FLOOR_ROOM_COUNTS as $floorCode => $roomCount) {
                $floor = Floor::query()->firstOrCreate(
                    [
                        'property_id' => $property->id,
                        'code' => $floorCode,
                    ],
                    [
                        'name' => "Tầng {$floorCode}",
                        'sort_order' => (int) $floorCode,
                        'is_active' => true,
                    ],
                );

                for ($position = 1; $position <= $roomCount; $position++) {
                    $roomNumber = $floorCode.str_pad((string) $position, 2, '0', STR_PAD_LEFT);

                    $room = Room::query()->firstOrCreate(
                        [
                            'floor_id' => $floor->id,
                            'room_number' => $roomNumber,
                        ],
                        [
                            'sort_order' => $position,
                            'status' => RoomStatus::Occupied,
                            'is_active' => true,
                        ],
                    );

                    $room->settings()->firstOrCreate([], self::DEFAULT_SETTINGS);
                }
            }

            return true;
        });

        $this->command?->info($seeded
            ? 'Demo property created: 4 floors and 45 rooms. Verify all room numbers and prices before real use.'
            : 'Existing property structure kept unchanged; demo floors and rooms were not seeded.');
    }
}
