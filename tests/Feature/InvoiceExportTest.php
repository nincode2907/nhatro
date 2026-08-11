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
use PhpOffice\PhpSpreadsheet\IOFactory as SpreadsheetIOFactory;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use Tests\TestCase;
use ZipArchive;

class InvoiceExportTest extends TestCase
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

    public function test_all_export_routes_require_authentication(): void
    {
        $invoice = $this->createInvoice('101', 1);

        foreach ([
            route('invoice-exports.xlsx', $this->period),
            route('invoice-exports.pdf-batch', $this->period),
            route('invoice-exports.docx-batch', $this->period),
            route('invoice-exports.pdf-single', [$this->period, $invoice]),
            route('invoice-exports.docx-single', [$this->period, $invoice]),
        ] as $url) {
            $this->get($url)->assertRedirect(route('login'));
        }
    }

    public function test_xlsx_contains_snapshot_values_monthly_columns_and_no_formulas(): void
    {
        $invoice = $this->createInvoice('101', 1);
        $invoice->room->settings->update([
            'rent_amount' => 9_000_000,
            'electricity_unit_price' => 9_999,
            'water_unit_price' => 8_888,
        ]);

        $response = $this->actingAs(User::factory()->create())
            ->get(route('invoice-exports.xlsx', $this->period))
            ->assertOk()
            ->assertHeader('content-type', 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet')
            ->assertHeader('content-disposition', 'attachment; filename="test-house_2026-08_hoa-don.xlsx"');

        $spreadsheet = $this->loadSpreadsheet($response->getContent());
        $sheet = $spreadsheet->getActiveSheet();

        $this->assertSame('A5', $sheet->getFreezePane());
        $this->assertSame([
            'Phòng', 'Giá', 'Tiền phòng', 'CSC điện', 'CSM điện', 'kWh',
            'Đơn giá điện', 'Tiền điện', 'CSC nước', 'CSM nước', 'm³',
            'Đơn giá nước', 'Tiền nước', 'Xe', 'Rác', 'Cáp', 'Khác',
            'Tổng', 'Trạng thái', 'Ghi chú',
        ], $sheet->rangeToArray('A4:T4', null, false, false)[0]);
        $this->assertSame('101', $sheet->getCell('A5')->getValue());
        $this->assertSame(3_200_000, $sheet->getCell('B5')->getValue());
        $this->assertSame(3_200_000, $sheet->getCell('C5')->getValue());
        $this->assertSame(14_000, $sheet->getCell('D5')->getValue());
        $this->assertSame(14_217, $sheet->getCell('E5')->getValue());
        $this->assertSame(217, $sheet->getCell('F5')->getValue());
        $this->assertSame(3_200, $sheet->getCell('G5')->getValue());
        $this->assertSame(694_400, $sheet->getCell('H5')->getValue());
        $this->assertSame(343, $sheet->getCell('I5')->getValue());
        $this->assertSame(347, $sheet->getCell('J5')->getValue());
        $this->assertSame(4, $sheet->getCell('K5')->getValue());
        $this->assertSame(17_000, $sheet->getCell('L5')->getValue());
        $this->assertSame(68_000, $sheet->getCell('M5')->getValue());
        $this->assertSame(120_000, $sheet->getCell('N5')->getValue());
        $this->assertSame(30_000, $sheet->getCell('O5')->getValue());
        $this->assertSame(80_000, $sheet->getCell('P5')->getValue());
        $this->assertSame(25_000, $sheet->getCell('Q5')->getValue());
        $this->assertSame(4_217_400, $sheet->getCell('R5')->getValue());
        $this->assertSame('Đã ghi', $sheet->getCell('S5')->getValue());
        $this->assertSame('Kiểm tra đồng hồ', $sheet->getCell('T5')->getValue());
        $this->assertSame('TỔNG THÁNG', $sheet->getCell('A7')->getValue());
        $this->assertSame(4_217_400, $sheet->getCell('R7')->getValue());

        foreach ($sheet->getCellCollection()->getCoordinates() as $coordinate) {
            $value = $sheet->getCell($coordinate)->getValue();
            $this->assertFalse(is_string($value) && str_starts_with($value, '='));
        }

        $spreadsheet->disconnectWorksheets();
    }

    public function test_pdf_supports_single_and_three_per_page_batch_exports(): void
    {
        $invoices = collect(['101', '102', '103', '104'])
            ->map(fn (string $room, int $index): Invoice => $this->createInvoice($room, $index + 1));
        $admin = User::factory()->create();

        $single = $this->actingAs($admin)
            ->get(route('invoice-exports.pdf-single', [$this->period, $invoices->first()]))
            ->assertOk()
            ->assertHeader('content-type', 'application/pdf')
            ->assertHeader('content-disposition', 'attachment; filename="test-house_2026-08_phong-101.pdf"')
            ->getContent();
        $batch = $this->actingAs($admin)
            ->get(route('invoice-exports.pdf-batch', $this->period))
            ->assertOk()
            ->assertHeader('content-type', 'application/pdf')
            ->assertHeader('content-disposition', 'attachment; filename="test-house_2026-08_hoa-don.pdf"')
            ->getContent();

        $this->assertStringStartsWith('%PDF-', $single);
        $this->assertStringStartsWith('%PDF-', $batch);
        $this->assertGreaterThan(10_000, strlen($single));
        $this->assertGreaterThan(20_000, strlen($batch));
        $this->assertSame(1, preg_match_all('/\/Type\s*\/Page\b/', $single));
        $this->assertSame(2, preg_match_all('/\/Type\s*\/Page\b/', $batch));
    }

    public function test_docx_supports_editable_single_and_batch_exports(): void
    {
        $invoices = collect(['101', '102'])
            ->map(fn (string $room, int $index): Invoice => $this->createInvoice($room, $index + 1));
        $admin = User::factory()->create();

        $single = $this->actingAs($admin)
            ->get(route('invoice-exports.docx-single', [$this->period, $invoices->first()]))
            ->assertOk()
            ->assertHeader('content-type', 'application/vnd.openxmlformats-officedocument.wordprocessingml.document')
            ->assertHeader('content-disposition', 'attachment; filename="test-house_2026-08_phong-101.docx"')
            ->getContent();
        $batch = $this->actingAs($admin)
            ->get(route('invoice-exports.docx-batch', $this->period))
            ->assertOk()
            ->assertHeader('content-type', 'application/vnd.openxmlformats-officedocument.wordprocessingml.document')
            ->assertHeader('content-disposition', 'attachment; filename="test-house_2026-08_hoa-don.docx"')
            ->getContent();

        $singleXml = $this->documentXml($single);
        $batchXml = $this->documentXml($batch);

        $this->assertStringStartsWith('PK', $single);
        $this->assertStringContainsString('NHÀ TRỌ KIỂM THỬ', $singleXml);
        $this->assertStringContainsString('PHÒNG 101', $singleXml);
        $this->assertStringContainsString('Cũ 14.000 → Mới 14.217', $singleXml);
        $this->assertStringContainsString('4.217.400 đ', $singleXml);
        $this->assertStringContainsString('Kiểm tra đồng hồ', $singleXml);
        $this->assertStringContainsString('PHÒNG 101', $batchXml);
        $this->assertStringContainsString('PHÒNG 102', $batchXml);
        $this->assertStringContainsString('w:type="page"', $batchXml);
    }

    public function test_exports_reject_an_invoice_from_another_period_and_other_property_periods(): void
    {
        $invoice = $this->createInvoice('101', 1);
        $otherPeriod = BillingPeriod::factory()->for($this->property)->create([
            'period_key' => '2026-09',
            'starts_on' => '2026-09-01',
            'ends_on' => '2026-09-30',
        ]);
        $otherPropertyPeriod = BillingPeriod::factory()->create([
            'period_key' => '2026-10',
            'starts_on' => '2026-10-01',
            'ends_on' => '2026-10-31',
        ]);
        $admin = User::factory()->create();

        $this->actingAs($admin)
            ->get(route('invoice-exports.pdf-single', [$otherPeriod, $invoice]))
            ->assertNotFound();
        $this->actingAs($admin)
            ->get(route('invoice-exports.xlsx', $otherPropertyPeriod))
            ->assertNotFound();
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
            'cable_amount' => 80_000,
            'other_amount' => 25_000,
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
            ->create(['note' => 'Kiểm tra đồng hồ']);

        return $this->app->make(InvoiceCalculator::class)
            ->recalculateDraft($this->period, $room, $reading);
    }

    private function loadSpreadsheet(string $content): Spreadsheet
    {
        $path = tempnam(sys_get_temp_dir(), 'invoice-xlsx-');
        file_put_contents($path, $content);

        try {
            return SpreadsheetIOFactory::load($path);
        } finally {
            unlink($path);
        }
    }

    private function documentXml(string $content): string
    {
        $path = tempnam(sys_get_temp_dir(), 'invoice-docx-');
        file_put_contents($path, $content);
        $zip = new ZipArchive;

        try {
            $this->assertTrue($zip->open($path));
            $xml = $zip->getFromName('word/document.xml');
            $this->assertIsString($xml);

            return html_entity_decode($xml, ENT_QUOTES | ENT_XML1, 'UTF-8');
        } finally {
            $zip->close();
            unlink($path);
        }
    }
}
