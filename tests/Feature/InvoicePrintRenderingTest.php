<?php

namespace Tests\Feature;

use App\Models\BillingPeriod;
use App\Models\Floor;
use App\Models\Invoice;
use App\Models\MeterReading;
use App\Models\Property;
use App\Models\Room;
use App\Models\RoomSetting;
use App\Models\User;
use App\Services\Billing\InvoiceCalculator;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class InvoicePrintRenderingTest extends TestCase
{
    use RefreshDatabase;

    private Property $property;

    private Floor $floor;

    private BillingPeriod $period;

    protected function setUp(): void
    {
        parent::setUp();

        config()->set('property.code', 'TEST-HOUSE');
        $this->property = Property::factory()->create([
            'code' => 'TEST-HOUSE',
            'name' => 'Nhà trọ kiểm thử',
            'address' => '123 Đường Mẫu',
        ]);
        $this->floor = Floor::factory()->for($this->property)->create([
            'code' => '1',
            'name' => 'Tầng 1',
            'sort_order' => 1,
        ]);
        $this->period = BillingPeriod::factory()->for($this->property)->create([
            'period_key' => '2026-08',
            'starts_on' => '2026-08-01',
            'ends_on' => '2026-08-31',
        ]);
    }

    public function test_print_routes_require_authentication(): void
    {
        $invoice = $this->createInvoice('101', 1);

        $this->get(route('invoices.print-single', [$this->period, $invoice]))
            ->assertRedirect(route('login'));
        $this->get(route('invoices.print-batch', $this->period))
            ->assertRedirect(route('login'));
    }

    public function test_readable_invoice_and_a5_print_render_every_required_snapshot_field(): void
    {
        $invoice = $this->createInvoice('101', 1);
        $invoice->update(['note' => 'Thu tiền trong tháng']);
        $admin = User::factory()->create();

        $this->actingAs($admin)
            ->get(route('invoices.show', [$this->period, $invoice]))
            ->assertOk()
            ->assertSee(route('invoices.print-single', [$this->period, $invoice]), false)
            ->assertSeeText('Phòng 101')
            ->assertSeeText('4,112,400 đ');

        $response = $this->actingAs($admin)
            ->get(route('invoices.print-single', [$this->period, $invoice]))
            ->assertOk()
            ->assertSee('css/invoice-print-a5.css', false)
            ->assertSee('data-print-page', false)
            ->assertSeeText('Nhà trọ kiểm thử')
            ->assertSeeText('123 Đường Mẫu')
            ->assertSeeText('Hóa đơn tiền phòng')
            ->assertSeeText('Tháng 08/2026')
            ->assertSeeText('Phòng')
            ->assertSeeText('101')
            ->assertSeeText('Số cũ')
            ->assertSeeText('Số mới')
            ->assertSeeText('Đơn giá')
            ->assertSeeText('14.000')
            ->assertSeeText('14.217')
            ->assertSeeText('3,200 đ')
            ->assertSeeText('17,000 đ')
            ->assertSeeText('694,400 đ')
            ->assertSeeText('343')
            ->assertSeeText('347')
            ->assertSeeText('68,000 đ')
            ->assertSeeText('Tiền phòng')
            ->assertSeeText('Xe')
            ->assertSeeText('Rác')
            ->assertSeeText('Cáp / Internet')
            ->assertSeeText('Khoản khác')
            ->assertSeeText('Không phát sinh')
            ->assertSeeText('4,112,400 đ')
            ->assertSeeText('Số tiền bằng chữ: Bốn triệu một trăm mười hai nghìn bốn trăm đồng.')
            ->assertSeeText('Thu tiền trong tháng')
            ->assertSeeText('Người thu')
            ->assertSeeText('Người thuê')
            ->assertDontSeeText('Bản nháp')
            ->assertDontSeeText('Số lượng')
            ->assertDontSee('site-header', false);

        $this->assertSame(1, substr_count($response->getContent(), 'invoice-paper--single'));
        $this->assertSame(
            5,
            preg_match_all('/<td class="invoice-unit-price">\s*<\/td>/', $response->getContent()),
        );
    }

    public function test_batch_print_groups_exactly_three_invoices_per_a4_sheet_in_walk_order(): void
    {
        foreach (['101', '102', '103', '104'] as $index => $roomNumber) {
            $this->createInvoice($roomNumber, $index + 1);
        }

        $response = $this->actingAs(User::factory()->create())
            ->get(route('invoices.print-batch', $this->period))
            ->assertOk()
            ->assertSee('css/invoice-print-batch.css', false)
            ->assertSeeText('4 hóa đơn · 3 phiếu mỗi tờ A4')
            ->assertSeeInOrder(['101', '102', '103', '104'])
            ->assertDontSee('site-header', false);

        $html = $response->getContent();
        preg_match_all('/<section class="batch-sheet".*?<\/section>/s', $html, $sheets);

        $this->assertCount(2, $sheets[0]);
        $this->assertSame(3, substr_count($sheets[0][0], 'invoice-paper--compact'));
        $this->assertSame(1, substr_count($sheets[0][1], 'invoice-paper--compact'));
    }

    public function test_print_routes_reject_an_invoice_from_another_period(): void
    {
        $invoice = $this->createInvoice('101', 1);
        $otherPeriod = BillingPeriod::factory()->for($this->property)->create([
            'period_key' => '2026-09',
            'starts_on' => '2026-09-01',
            'ends_on' => '2026-09-30',
        ]);

        $this->actingAs(User::factory()->create())
            ->get(route('invoices.print-single', [$otherPeriod, $invoice]))
            ->assertNotFound();
    }

    public function test_print_styles_declare_a5_and_three_row_a4_page_layouts(): void
    {
        $commonCss = file_get_contents(public_path('css/invoice-print.css'));
        $a5Css = file_get_contents(public_path('css/invoice-print-a5.css'));
        $batchCss = file_get_contents(public_path('css/invoice-print-batch.css'));

        $this->assertStringContainsString('.invoice-room-number', $commonCss);
        $this->assertStringContainsString('.invoice-paper-total', $commonCss);
        $this->assertStringContainsString('.invoice-total-words', $commonCss);
        $this->assertStringContainsString('display: none !important', $commonCss);
        $this->assertStringContainsString('size: A5 portrait', $a5Css);
        $this->assertStringContainsString('size: A4 portrait', $batchCss);
        $this->assertStringContainsString('align-items: center', $batchCss);
        $this->assertStringContainsString('grid-template-rows: repeat(3', $batchCss);
        $this->assertStringContainsString('page-break-after: always', $batchCss);
    }

    private function createInvoice(string $roomNumber, int $sortOrder): Invoice
    {
        $room = Room::factory()->for($this->floor)->create([
            'room_number' => $roomNumber,
            'sort_order' => $sortOrder,
        ]);
        RoomSetting::factory()->for($room)->create([
            'rent_amount' => 3_200_000,
            'electricity_unit_price' => 3_200,
            'water_unit_price' => 17_000,
            'vehicle_amount' => 120_000,
            'garbage_amount' => 30_000,
            'cable_amount' => 0,
            'other_amount' => 0,
        ]);
        $reading = MeterReading::factory()
            ->for($this->period)
            ->for($room)
            ->recorded(
                electricityPrevious: 14_000,
                electricityCurrent: 14_217,
                waterPrevious: 343,
                waterCurrent: 347,
            )
            ->create();

        return $this->app->make(InvoiceCalculator::class)
            ->recalculateDraft($this->period, $room, $reading);
    }
}
