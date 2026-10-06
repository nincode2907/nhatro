---
name: nhatro-billing
description: Sửa luồng ghi điện nước, hóa đơn hoặc export Nhà trọ 441 với transaction, snapshot và phạm vi property. Dùng khi thay đổi reading, giá phòng, tính tiền, in hoặc XLSX/PDF/DOCX.
---

# Điện nước và hóa đơn Nhà trọ 441

## Định vị trước khi sửa

- Request/controller kiểm tra đầu vào và quan hệ property/period/floor/room.
  Xem `routes/web.php`, controller tương ứng và `app/Http/Requests/`.
- Luồng đọc/ghi: `app/Actions/MeterReadings/`; giá/thứ tự:
  `app/Actions/Rooms/`; tính tiền: `app/Services/Billing/InvoiceCalculator.php`.
- Hiển thị/in: `app/ViewModels/InvoiceDocument.php`, `resources/views/invoices/`;
  dữ liệu export chung: `app/Services/Exports/InvoiceExportDataFactory.php`,
  `app/Data/Exports/`, các exporter và views PDF.
- Chỉ đọc phần liên quan của [yêu cầu V1](../../../docs/reference/nhatro-441-codex-context.md)
  hoặc [thiết kế ERD](../../../docs/reference/nhatro-441-erd.md). Phân biệt yêu cầu tương lai
  với routes/actions đã tồn tại; hiện chưa có route chốt kỳ trong `routes/web.php`.

## Các quyết định cần giữ

1. `EnsureMeterReading` chụp chỉ số cũ từ reading RECORDED gần nhất trước kỳ
   hiện tại, cùng property; bỏ qua dữ liệu null. Không có thì nhập đầu kỳ.
   Không cho sửa chỉ số cũ đã lưu; chỉ số mới >= cũ; meter tắt để null.
2. `SaveMeterReading`/`SkipMeterReading` khóa và kiểm tra kỳ OPEN trong transaction,
   ghi reading rồi tính lại invoice nháp. Skip xóa current/usage; phòng OCCUPIED
   vẫn tính tiền phòng và phí cố định, không tính điện nước chưa ghi.
3. `ReadingFlow` chỉ lấy room active, OCCUPIED trước, rồi sort_order/room_number;
   chọn phòng PENDING tiếp theo và quay vòng. Giữ hành vi OK & Tiếp trên điện thoại.
4. `InvoiceCalculator` dùng integer với kiểm tra overflow. Room không OCCUPIED
   tạo tổng 0 và không có items. Không viết lại invoice FINALIZED; bản in/export
   phải dùng snapshot, không tự tính lại từ settings hiện tại.
5. Reset xóa cả readings/invoices/items trong transaction; controller chỉ cho
   kỳ OPEN của tháng hiện tại (`BillingPeriod::canResetReadings`). Không mở rộng
   sang kỳ cũ/finalized. Đổi giá phòng không được làm biến đổi snapshot lịch sử.

## Kiểm chứng theo phạm vi

Chọn tests liên quan trong `tests/Feature/`: `MeterReadingFlowTest`,
`InvoiceBillingTest`, `BillingPeriodManagementTest`, `RoomManagementTest`,
`PropertyStructureManagementTest`, `InvoicePrintRenderingTest`, `InvoiceExportTest`.
Với thay đổi liên kết nhiều bước, chạy toàn bộ suite. Dùng DB test `:memory:`;
không seed hoặc reset SQLite thật để thử. Kiểm tra tổng số, auth/phạm vi property,
snapshot lịch sử và trạng thái bỏ qua; in một phiếu A5, batch 3 phiếu/A4 nếu sửa layout.
