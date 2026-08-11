# Nhà trọ 441 — Codex Context & Implementation Plan

> Tài liệu này là **nguồn ngữ cảnh chính** để đưa cho Codex khi code dự án.
> Hãy coi các yêu cầu trong file này là source of truth cho V1.
> Không tự ý mở rộng thành một hệ thống quản lý nhà trọ tổng quát nếu chưa được yêu cầu.

---

## 0. Vai trò của Codex

Bạn là senior full-stack engineer phụ trách xây dựng một web app nội bộ cho **Nhà trọ 441**.

Mục tiêu V1 không phải là xây một SaaS quản lý nhà trọ hoàn chỉnh.

Mục tiêu duy nhất của V1:

1. Đi từng tầng/phòng và nhập chỉ số điện + nước trên điện thoại.
2. Không ghi ra giấy rồi nhập lại Excel.
3. Tự lấy chỉ số cuối kỳ trước làm chỉ số đầu kỳ sau.
4. Tự tính tiền điện, nước và các khoản phí cố định theo cấu hình riêng của từng phòng.
5. Tự sinh hóa đơn tháng.
6. In/xuất hóa đơn.
7. Xuất dữ liệu tháng ra Excel, PDF và Word.
8. Chạy local trong nhà trước.
9. Dữ liệu lưu bằng SQLite.
10. Hoàn thiện và dùng thật cho Nhà trọ 441 trước khi mở rộng.

Nếu có yêu cầu không thuộc scope V1, ghi nhận nhưng **không tự triển khai**.

---

# 1. Bối cảnh thực tế

## 1.1 Quy mô

Nhà trọ 441 hiện có khoảng:

- 5 tầng.
- Mỗi tầng khoảng 10–12 phòng.
- Tổng khoảng 45 phòng.

Số phòng có dạng:

- 101, 102, 103...
- 201, 202...
- 301...
- 401...

Danh sách thực tế sẽ được seed/config sau.

---

## 1.2 Quy trình hiện tại

Quy trình thủ công đang gần như:

```text
Đến kỳ ghi điện nước
    ↓
Đi từng phòng xem đồng hồ điện
    ↓
Ghi chỉ số ra giấy
    ↓
Có chỉ số nước
    ↓
Mở file Excel tháng
    ↓
Nhập chỉ số mới
    ↓
Lấy chỉ số tháng trước làm chỉ số cũ
    ↓
Excel tính điện/nước/phòng/xe/rác/cáp...
    ↓
Viết phiếu giấy từng phòng
    ↓
Đưa phiếu cho người thuê
    ↓
Lưu từng file theo tháng
    ↓
Tổng hợp báo cáo
```

Vấn đề lớn nhất là **cùng một dữ liệu bị ghi/nhập lại nhiều lần**.

V1 phải biến flow thành:

```text
Nhìn đồng hồ
    ↓
Nhập vào điện thoại đúng 1 lần
    ↓
Database
    ├── tự lấy chỉ số cũ
    ├── tính tiêu thụ
    ├── tính tiền
    ├── tạo hóa đơn
    ├── in phiếu
    └── export dữ liệu tháng
```

---

# 2. Product scope V1

## 2.1 Bắt buộc có

- Nhà trọ 441.
- Danh sách tầng.
- Danh sách phòng.
- Thứ tự đi thực tế của từng phòng.
- Trạng thái phòng.
- Setting giá riêng cho từng phòng.
- Billing period theo tháng.
- Nhập chỉ số điện.
- Nhập chỉ số nước.
- Số cũ tự động lấy từ kỳ trước.
- Số mới nhập một lần.
- Tính usage.
- Validate số mới.
- Skip phòng và lưu lý do.
- Danh sách phòng trong tầng có trạng thái đã ghi/chưa ghi/bỏ qua/trống.
- Nhảy trực tiếp đến một phòng.
- OK & Next.
- Tự cập nhật draft invoice.
- Hóa đơn tháng.
- Invoice snapshot.
- In hóa đơn đơn.
- In hàng loạt.
- Export Excel.
- Export PDF.
- Export DOCX.
- Backup SQLite cơ bản.
- Responsive trên điện thoại và desktop.
- Simple admin login trước khi dùng trên LAN.

---

## 2.2 Chưa làm trong V1

Không tự triển khai các phần sau:

- Multi-property SaaS hoàn chỉnh.
- Người thuê / tenant portal.
- Hợp đồng.
- Cọc.
- Tạm trú.
- CCCD.
- Quản lý xe chi tiết.
- Theo dõi thanh toán / công nợ.
- QR từng phòng.
- Camera chụp đồng hồ.
- OCR.
- Zalo API.
- SMS.
- Payment gateway.
- QR ngân hàng.
- IoT.
- Smart meter.
- Native Android/iOS app.
- Offline sync.
- Role/permission phức tạp.
- MongoDB.
- VPS/public deployment.
- Microservices.

Nếu cần, chỉ để TODO ở README, không code trước.

---

# 3. Tech stack

## 3.1 Backend

Ưu tiên:

- Laravel, bản đang được support và tương thích tốt với môi trường dự án.
- PHP version phù hợp với Laravel version được chọn.
- SQLite.
- Eloquent ORM.
- Laravel validation.
- Laravel migrations.
- Laravel seeders.
- Laravel feature/unit tests.

Không dùng MongoDB.

---

## 3.2 Frontend

Ưu tiên:

- Blade.
- Livewire hoặc giải pháp server-driven tương đương nếu giúp giảm JS.
- Alpine.js chỉ khi thực sự cần tương tác nhỏ.
- Responsive mobile-first.
- Không cần React/Vue SPA cho V1.

Mục tiêu:

- Mở bằng điện thoại, thao tác một tay được.
- Input số lớn.
- Nút OK & Next lớn.
- Không bắt người dùng đi qua nhiều màn hình.
- Desktop vẫn dùng được để xem/in/export.

---

## 3.3 Database

SQLite là database chính của V1.

Yêu cầu:

- Enable WAL mode nếu phù hợp.
- Dùng transaction ngắn.
- Có unique constraints cần thiết.
- Có foreign keys.
- Có backup file SQLite.
- Không commit file database production lên Git.
- Không commit file `.env`.

---

## 3.4 Export

Cần hỗ trợ:

- `.xlsx`
- `.pdf`
- `.docx`

Có thể dùng các package PHP/Laravel trưởng thành, miễn:

- tương thích với Laravel/PHP version;
- dễ maintain;
- không phụ thuộc dịch vụ cloud;
- dữ liệu export phải lấy từ cùng một nguồn invoice/monthly data.

Không được viết 3 bộ business logic tính tiền khác nhau cho Excel/PDF/DOCX.

---

# 4. Deployment V1

## 4.1 GitHub

GitHub chỉ dùng để:

- lưu source code;
- version control;
- issue/todo nếu cần.

**Không coi GitHub Pages là server cho Laravel.**

---

## 4.2 Local use

V1 chạy trên một laptop/PC trong nhà.

Ví dụ:

```bash
php artisan serve --host=0.0.0.0 --port=8000
```

Các điện thoại trong cùng private Wi-Fi truy cập qua LAN IP:

```text
http://192.168.x.x:8000
```

Không expose app trực tiếp ra public Internet ở V1.

Nếu mạng Wi-Fi người thuê và Wi-Fi gia đình dùng chung, phải đặt app sau authentication và nên cân nhắc network isolation.

---

# 5. Domain concepts

Các khái niệm chính:

- Property: Nhà trọ 441.
- Floor: tầng.
- Room: phòng.
- Room Setting: cấu hình giá của phòng.
- Billing Period: kỳ tháng, ví dụ 2026-08.
- Meter Reading: chỉ số điện/nước của một phòng trong một kỳ.
- Invoice: hóa đơn của phòng trong kỳ.
- Invoice Item: từng khoản tiền đã snapshot.

Xem chi tiết schema trong file:

`nhatro-441-erd.md`

---

# 6. Trạng thái

## 6.1 Room status

Dùng enum/string rõ ràng:

```text
OCCUPIED
VACANT
INACTIVE
```

V1 chủ yếu dùng:

- OCCUPIED
- VACANT

---

## 6.2 Billing period status

```text
OPEN
FINALIZED
```

`OPEN`:

- cho nhập/sửa readings;
- invoice ở trạng thái draft.

`FINALIZED`:

- khóa kỳ ở mức nghiệp vụ;
- không sửa dữ liệu bình thường;
- nếu cần sửa phải explicit reopen bằng action có cảnh báo.

---

## 6.3 Reading status

```text
PENDING
RECORDED
SKIPPED
```

`PENDING`:
- chưa xử lý.

`RECORDED`:
- đã có điện + nước hợp lệ.

`SKIPPED`:
- đã bỏ qua kỳ này và có lý do.

---

## 6.4 Skip reason

Có thể dùng enum + note:

```text
NOT_RECORDED_TODAY
VACANT_ROOM
METER_NOT_ACCESSIBLE
METER_PROBLEM
OTHER
```

Nếu `VACANT_ROOM`:

- có thể hỏi user có muốn cập nhật room.status = VACANT hay không;
- không tự silently thay đổi trạng thái phòng.

---

# 7. Main user flow

## 7.1 Trang chủ

Màn hình đầu:

```text
NHÀ TRỌ 441

Kỳ: Tháng 08/2026

[ GHI ĐIỆN NƯỚC ]

Tầng 1     8 / 11
Tầng 2    11 / 11 ✅
Tầng 3     4 / 11
Tầng 4     0 / 12

[ HÓA ĐƠN THÁNG ]
[ PHÒNG & CÀI ĐẶT ]
[ XUẤT DỮ LIỆU ]
```

Không cần dashboard tài chính phức tạp.

---

## 7.2 Chọn tháng

Có billing period selector.

Ví dụ:

```text
2026-08
```

Nếu kỳ chưa tồn tại:

- cho tạo kỳ mới;
- tự chuẩn bị reading records hoặc lazy-create khi truy cập;
- chỉ số cũ tự tìm từ kỳ trước gần nhất có dữ liệu hợp lệ.

---

## 7.3 Chọn tầng

Mỗi tầng hiển thị:

```text
Tầng 1
8 / 11 đã xử lý
```

"Đã xử lý" = `RECORDED + SKIPPED`.

---

# 8. Meter Reading Screen — màn hình quan trọng nhất

Đây là màn hình phải tối ưu UX nhiều nhất.

Ví dụ:

```text
← TẦNG 1                      Tháng 08/2026

Đã xử lý 3 / 11        ███░░░░░░░

              PHÒNG 101
                 1 / 11

⚡ ĐIỆN

Số cũ                         14.000
Số mới                      [ 14217 ]

Tiêu thụ                       217 kWh
Đơn giá                       3.200 đ
Thành tiền                  694.400 đ

💧 NƯỚC

Số cũ                            343
Số mới                         [ 347 ]

Tiêu thụ                           4 m³
Đơn giá                      17.000 đ
Thành tiền                   68.000 đ

[ DS PHÒNG ]       [ BỎ QUA ]

          [ ✓ OK & TIẾP ]
```

---

## 8.1 Luôn hiển thị số phòng

Không chỉ hiển thị:

```text
1 / 11
```

mà phải hiển thị:

```text
PHÒNG 101
1 / 11
```

`1 / 11` = vị trí của phòng trong `sort_order` tầng hiện tại.

Ngoài ra có progress riêng:

```text
Đã xử lý 3 / 11
```

Hai thông tin này không được trộn làm một.

---

## 8.2 Số cũ

Người dùng không nhập số cũ bình thường.

App tự lấy:

```text
current reading của kỳ trước
    ↓
previous reading của kỳ hiện tại
```

Ví dụ:

```text
2026-07 current electricity = 14217

2026-08:
previous electricity = 14217
current electricity = user input
```

Tương tự nước.

---

## 8.3 Số mới

Input:

- `type=number` hoặc `inputmode=numeric`;
- font lớn;
- keypad numeric trên mobile;
- không dùng spinner gây khó thao tác nếu browser hỗ trợ;
- auto-select/focus hợp lý;
- Enter/Next chuyển thuận tiện nếu có thể.

---

## 8.4 Live calculation

Khi nhập số mới:

```text
usage = current - previous
amount = usage * unit_price
```

UI cập nhật ngay.

Lưu ý:

- số tiền lưu integer VND;
- không dùng float cho tiền;
- meter reading có thể dùng integer nếu đồng hồ thực tế chỉ ghi số nguyên;
- nếu sau này cần decimal thì schema phải dễ nâng cấp.

---

# 9. Validation

## 9.1 Số mới nhỏ hơn số cũ

Ví dụ:

```text
Số cũ: 14217
Số mới: 14127
```

Hiển thị:

```text
⚠ Chỉ số mới nhỏ hơn chỉ số cũ.
Vui lòng kiểm tra lại.
```

Không cho `OK & Next` bình thường.

Có thể cung cấp action ngoại lệ:

```text
[ Đồng hồ đã thay/reset ]
```

Nhưng V1 chỉ cần lưu một flag/note rõ ràng nếu triển khai action này.

Không silently chấp nhận usage âm.

---

## 9.2 Thiếu một trong hai loại

Nếu phòng OCCUPIED:

- mặc định cần cả điện và nước để `RECORDED`.

Nếu nghiệp vụ thực tế có phòng không dùng một loại meter:

- thêm setting `electric_enabled` / `water_enabled`;
- loại disabled không cần nhập.

---

# 10. OK & Next

Khi user bấm:

```text
✓ OK & TIẾP
```

Thực hiện trong transaction ngắn:

1. Validate.
2. Lock/ensure unique room + billing period record.
3. Save previous/current electricity.
4. Save electricity usage.
5. Save previous/current water.
6. Save water usage.
7. Set status = RECORDED.
8. Set recorded_at.
9. Recalculate/update draft invoice.
10. Tìm phòng tiếp theo chưa xử lý theo `sort_order`.
11. Redirect/navigate trực tiếp tới phòng tiếp theo.

Không bắt user:

```text
Save → Back → chọn phòng → Open
```

---

# 11. Room list drawer/modal

Nếu app đang ở:

```text
PHÒNG 102 — 2/11
```

nhưng ngoài thực tế người dùng đang đứng trước phòng 105:

bấm:

```text
DS PHÒNG
```

Hiển thị:

```text
TẦNG 1 — 7/11 đã xử lý

✅ 101    Đã ghi
✅ 102    Đã ghi
🟡 103    Chưa ghi
⏭ 104    Bỏ qua
🟡 105    Chưa ghi
✅ 106    Đã ghi
⚪ 107    Phòng trống
...
```

Status display:

- ✅ RECORDED
- 🟡 PENDING
- ⏭ SKIPPED
- ⚪ VACANT

Click phòng nào thì mở thẳng phòng đó.

Danh sách phải đủ lớn để dùng bằng điện thoại.

---

# 12. Skip flow

Bấm:

```text
BỎ QUA
```

Mở modal:

```text
LÝ DO BỎ QUA PHÒNG 105

○ Chưa ghi được hôm nay
○ Phòng đang trống
○ Không tiếp cận được đồng hồ
○ Đồng hồ có vấn đề
○ Khác

Ghi chú
[........................]

[ HỦY ]       [ XÁC NHẬN ]
```

Khi xác nhận:

- reading.status = SKIPPED;
- save skip_reason;
- save note;
- navigate tới phòng chưa xử lý tiếp theo.

Nếu lý do = `VACANT_ROOM`:

- hỏi riêng có cập nhật room.status thành VACANT không;
- không implicit update.

---

# 13. Room sort order

Không giả định thứ tự đi bằng số phòng.

Mỗi room có:

```text
sort_order
```

Ví dụ có thể là:

```text
101 → 102 → 105 → 103 → 104
```

nếu layout thực tế thuận tiện như vậy.

Trang Room Settings phải cho sửa sort order.

---

# 14. Room Settings

Mỗi phòng có cấu hình riêng.

Ví dụ:

```text
PHÒNG 101 — CÀI ĐẶT

Trạng thái
[ Đang thuê ]

Thứ tự đi
[ 1 ]

Tiền phòng
[ 3.200.000 ] đ

Điện
[ 3.200 ] đ / kWh
[✓] Có đồng hồ điện

Nước
[ 17.000 ] đ / m³
[✓] Có đồng hồ nước

Xe
[ 120.000 ] đ

Rác
[ 30.000 ] đ

Cáp / Internet
[ 0 ] đ

Khoản cố định khác
[ 0 ] đ

Ghi chú
[......................]

[ LƯU ]
```

Tiền lưu integer VND.

---

# 15. Billing calculation

V1 tính:

```text
rent
+ electricity
+ water
+ vehicle
+ garbage
+ cable/internet
+ other fixed fee
= total
```

Không tự thêm deposit/cọc.

---

## 15.1 Electricity

```text
usage = current - previous
amount = usage * electricity_unit_price
```

---

## 15.2 Water

```text
usage = current - previous
amount = usage * water_unit_price
```

---

# 16. Invoice snapshot rule

Đây là rule bắt buộc.

Giả sử tháng 08:

```text
electricity unit price = 3.200đ
```

Tháng 09 đổi thành:

```text
3.500đ
```

Hóa đơn tháng 08 **không được thay đổi**.

Khi generate/recalculate invoice, tạo `invoice_items` lưu snapshot:

```text
description = "Điện tháng 08/2026"
quantity = 217
unit = "kWh"
unit_price = 3200
amount = 694400
```

Tương tự:

- tiền phòng;
- nước;
- xe;
- rác;
- cáp/internet;
- khoản khác.

Nếu invoice đã finalized/locked:

- không recalculate tự động từ room settings mới.

---

# 17. Draft invoice

Trong kỳ OPEN:

- invoice có thể ở `DRAFT`;
- khi reading thay đổi thì update draft invoice.

Không tạo business logic riêng trong UI.

Nên có service:

```text
InvoiceCalculator
```

hoặc tên tương đương.

Nó là source of truth cho tính hóa đơn.

---

# 18. Invoice template

Template phải:

- tiếng Việt;
- rõ;
- dễ đọc;
- phù hợp người lớn tuổi;
- số phòng nổi bật;
- tổng tiền font lớn;
- thể hiện rõ chỉ số cũ → mới;
- có chỗ ký nếu in giấy.

Ví dụ:

```text
┌──────────────────────────────────────────────┐
│                 NHÀ TRỌ 441                 │
│                                              │
│             PHIẾU TIỀN PHÒNG                │
│                THÁNG 08/2026                │
├──────────────────────────────────────────────┤
│ PHÒNG: 101                                  │
│                                              │
│ Nội dung        SL      Đơn giá   Thành tiền │
│ ──────────────────────────────────────────── │
│ Tiền phòng                         3.200.000 │
│                                              │
│ Điện                                         │
│ 14.000 → 14.217                              │
│ 217 kWh          3.200             694.400   │
│                                              │
│ Nước                                         │
│ 343 → 347                                    │
│ 4 m³            17.000              68.000   │
│                                              │
│ Xe                                   120.000 │
│ Rác                                   30.000 │
│ Cáp / Internet                              0 │
│ Khoản khác                                  0 │
│ ──────────────────────────────────────────── │
│                                              │
│ TỔNG CỘNG                        4.112.400 đ │
│                                              │
│ Bằng chữ: .................................. │
│                                              │
│ Ghi chú: ................................... │
│                                              │
│ Ngày .... tháng .... năm ....                │
│                                              │
│ Người thu                  Người thuê        │
│                                              │
│ __________                 __________        │
└──────────────────────────────────────────────┘
```

---

# 19. Print layouts

## 19.1 Single invoice

- A5 hoặc half-A4.
- Dùng khi cần in riêng.

## 19.2 Batch compact invoices

Ưu tiên layout:

```text
3 phiếu / A4
```

Ví dụ:

```text
45 phòng → khoảng 15 tờ A4
```

Mục tiêu:

- in;
- cắt;
- đưa phiếu.

Phải có print CSS.

---

# 20. Excel export

Excel nên gần với file cũ để người nhà dễ thích nghi.

Workbook theo billing period.

Sheet `HoaDon` hoặc `Thang_08_2026`:

| Phòng | Giá | Tiền phòng | CSC điện | CSM điện | kWh | Đơn giá điện | Tiền điện | CSC nước | CSM nước | m3 | Đơn giá nước | Tiền nước | Xe | Rác | Cáp | Khác | Tổng | Trạng thái | Ghi chú |
|---|---:|---:|---:|---:|---:|---:|---:|---:|---:|---:|---:|---:|---:|---:|---:|---:|---:|---|---|

Yêu cầu:

- số tiền format VND/numeric;
- không cần formula phụ thuộc Excel để tính tiền;
- giá trị export phải là kết quả từ business logic server;
- có tổng cuối sheet;
- frozen header;
- column width hợp lý.

---

# 21. PDF export

Có:

1. PDF một hóa đơn.
2. PDF batch toàn bộ phòng.
3. PDF monthly summary đơn giản nếu dễ triển khai.

Nguồn dữ liệu là invoice đã tính.

---

# 22. DOCX export

Có:

1. DOCX hóa đơn một phòng.
2. DOCX batch theo tháng hoặc report tháng.

Không cần cố làm DOCX giống PDF pixel-perfect.

Mục tiêu:

- mở bằng Word;
- nội dung rõ ràng;
- chỉnh sửa/in lại được.

---

# 23. Month completion screen

Ví dụ:

```text
THÁNG 08/2026

Tầng 1    11/11 ✅
Tầng 2    11/11 ✅
Tầng 3    10/11 ⚠
Tầng 4    12/12 ✅

Còn:
P307 — chưa ghi nước

[ XỬ LÝ P307 ]
```

Không final kỳ nếu còn `PENDING`, trừ khi user explicit override.

Nếu có `SKIPPED`:

- cho final;
- nhưng hiển thị cảnh báo danh sách skipped.

---

# 24. Finalize period

Khi user bấm:

```text
HOÀN TẤT KỲ
```

Hỏi confirm.

Sau đó:

- billing_period.status = FINALIZED;
- invoices chuyển sang FINALIZED/LOCKED;
- không tự update invoice từ setting nữa;
- vẫn cho export/in.

Nếu cần sửa:

```text
REOPEN KỲ
```

phải có confirmation.

---

# 25. Authentication V1

Chỉ cần:

- 1 admin user;
- login session;
- logout;
- password hash;
- CSRF;
- route protection.

Không cần:

- role;
- permission;
- social login;
- password reset email.

Seeder có thể tạo admin theo env hoặc command/setup flow.

Không hardcode password vào source.

---

# 26. Backup SQLite

Có command ví dụ:

```text
php artisan app:backup-database
```

Kết quả:

```text
storage/app/backups/
├── 2026-08-10_230000.sqlite
├── 2026-08-09_230000.sqlite
...
```

Yêu cầu:

- backup an toàn khi SQLite đang chạy;
- không chỉ `copy()` một cách mù quáng nếu có nguy cơ WAL chưa checkpoint;
- dùng SQLite backup strategy hợp lý;
- giữ N bản cấu hình được;
- README hướng dẫn copy backup sang OneDrive/Google Drive ngoài app.

Không cần tích hợp OAuth cloud V1.

---

# 27. Error handling

Các lỗi cần UX rõ:

- kỳ chưa có;
- kỳ đã finalized;
- phòng không tồn tại;
- duplicate reading;
- current < previous;
- missing meter;
- invoice calculation failed;
- export failed;
- database locked;
- backup failed.

Không hiển thị stack trace ở production/local household mode.

---

# 28. Testing

Bắt buộc có automated tests cho business rules chính.

Ít nhất:

## Reading tests

- lấy số cũ từ kỳ trước;
- lưu số mới;
- usage điện đúng;
- usage nước đúng;
- current < previous bị reject;
- disabled meter không cần nhập;
- skip room lưu reason;
- unique room + period.

## Invoice tests

- rent được snapshot;
- electricity price snapshot;
- water price snapshot;
- fixed fee snapshot;
- total đúng;
- đổi setting tháng sau không làm invoice cũ đổi;
- finalized invoice không auto-recalculate.

## Flow tests

- OK & Next tìm đúng next pending room theo sort_order;
- room list hiển thị trạng thái đúng;
- skipped được tính là processed;
- pending không được tính processed;
- finalization chặn khi còn pending.

---

# 29. Seed data

Tạo seed demo cho:

```text
Nhà trọ 441
```

4 tầng.

Có thể seed room mẫu:

```text
101–111
201–211
301–311
401–412
```

Nhưng phải ghi rõ đây chỉ là **demo seed**.

Không khẳng định danh sách thực tế cho đến khi user cung cấp.

Seed giá demo:

```text
rent = 3_000_000
electric = 3_200
water = 17_000
vehicle = 120_000
garbage = 30_000
cable = 0
other = 0
```

Các giá này chỉ là demo, user có thể sửa per-room.

---

# 30. Coding conventions

- Thin controllers.
- Business logic nằm trong service/domain action.
- Không tính invoice trực tiếp trong Blade/Livewire component.
- Form Request / validation rules rõ ràng.
- PHP types nếu có thể.
- Enum/value object hợp lý nhưng không over-engineer.
- Money = integer VND.
- Date/billing month dùng type rõ.
- Không dùng magic number.
- Không hardcode Nhà 441 khắp code; có 1 property record/config.
- Không tạo repository pattern vô ích nếu Eloquent đã đủ.
- Không microservice.
- Không CQRS/event sourcing.
- Không over-engineer.

---

# 31. Suggested route/page structure

Có thể điều chỉnh nhưng intent phải giữ.

```text
/login

/
  dashboard/home

/billing-periods
/billing-periods/{period}

/billing-periods/{period}/floors/{floor}/readings
/billing-periods/{period}/floors/{floor}/rooms/{room}/reading

/rooms
/rooms/{room}
/rooms/{room}/settings

/billing-periods/{period}/invoices
/billing-periods/{period}/invoices/{invoice}

/billing-periods/{period}/exports/excel
/billing-periods/{period}/exports/pdf
/billing-periods/{period}/exports/docx
```

---

# 32. UI principles

- Mobile-first.
- Vietnamese labels.
- Font dễ đọc.
- Số phòng cực rõ.
- Nút chính lớn.
- Nút nguy hiểm tách màu/confirm.
- Không dùng table rộng ở màn hình điện thoại nếu card/list tốt hơn.
- Mỗi lần chỉ tập trung một phòng khi ghi meter.
- Modal/drawer DS phòng phải mở nhanh.
- Không reload cả app quá nhiều nếu Livewire giúp tránh.
- Không animation thừa.
- Không dark mode V1 nếu chưa cần.

---

# 33. Accessibility / usability

- Input label rõ.
- Không chỉ dùng màu để thể hiện status; có icon/text.
- Tap target đủ lớn.
- Error text ngay gần input.
- Decimal/thousand separator display phải thống nhất.
- Database lưu raw integer, UI format theo `vi-VN`.

---

# 34. Security baseline

Dù local:

- auth required;
- password hash;
- CSRF;
- no debug exposed;
- validate all input;
- escape output;
- no raw SQL interpolation;
- no public directory listing;
- `.env` ignored;
- SQLite file không nằm public web root;
- exports không tạo path traversal;
- route authorization tối thiểu.

Không cần security enterprise.

---

# 35. Data integrity rules

1. Mỗi room + billing period chỉ có một meter reading record.
2. Mỗi room + billing period chỉ có một invoice.
3. Invoice items snapshot, không phụ thuộc setting hiện tại sau khi finalize.
4. Reading RECORDED phải đủ các meter được enable.
5. PENDING không tạo finalized invoice.
6. FINALIZED period không được sửa bình thường.
7. Room VACANT có thể skip billing hoặc tạo invoice 0 tùy explicit rule; V1 mặc định không bill rent nếu room VACANT ở kỳ đó.
8. Không xóa lịch sử kỳ finalized bằng UI thường.
9. Không cascade delete history nếu xóa room; ưu tiên deactivate room.

---

# 36. Definition of Done V1

V1 chỉ được coi là hoàn thành khi có thể demo flow sau trên điện thoại:

```text
1. Login
2. Chọn tháng 08/2026
3. Chọn Tầng 1
4. Thấy PHÒNG 101 — 1/11
5. Thấy số điện/nước cũ tự động
6. Nhập số mới
7. Thấy usage + tiền cập nhật
8. OK & Next
9. Sang PHÒNG 102 — 2/11
10. Bấm DS PHÒNG
11. Chọn phòng 105
12. Nhập dữ liệu
13. Bỏ qua một phòng với lý do
14. Hoàn tất tầng
15. Hoàn tất các tầng
16. Xem invoice
17. In invoice
18. Export XLSX
19. Export PDF
20. Export DOCX
21. Sang tháng 09
22. Số mới tháng 08 tự thành số cũ tháng 09
```

Nếu flow này chưa chạy trơn tru thì chưa làm feature ngoài scope.

---

# 37. Implementation phases

Quan trọng:

- Làm từng phase.
- Sau mỗi phase:
  - chạy tests;
  - chạy formatter/linter nếu có;
  - tóm tắt file đã thay đổi;
  - tóm tắt command cần chạy;
  - ghi TODO còn lại;
  - **dừng lại chờ phase tiếp theo**.
- Không tự chạy sang tất cả phase nếu user chỉ yêu cầu một phase.

---

# PHASE 0 — Project bootstrap

## Goal

Khởi tạo skeleton chạy được local.

## Tasks

1. Tạo Laravel project.
2. Cấu hình SQLite.
3. `.env.example`.
4. `.gitignore`.
5. Enable foreign keys.
6. Xem xét WAL/busy timeout cho SQLite.
7. Tạo README local setup.
8. Thiết lập basic test environment.
9. Tạo initial layout responsive.
10. Tạo simple admin auth.
11. Không tạo business tables ngoài skeleton nếu chưa cần.

## Acceptance criteria

```text
composer install
php artisan migrate
php artisan serve --host=0.0.0.0 --port=8000
```

chạy được.

Có:

```text
/login
/
```

Route `/` yêu cầu đăng nhập.

SQLite không nằm public directory.

---

# PHASE 1 — Property, floors, rooms, room settings

## Goal

Có cấu trúc Nhà 441 và cấu hình từng phòng.

## Tasks

1. Migration/property.
2. Floors.
3. Rooms.
4. Room settings.
5. Seed demo Nhà 441.
6. Room status.
7. sort_order.
8. Room list page.
9. Room settings edit page.
10. Validation giá.
11. Tests.

## UI

```text
PHÒNG

Tầng 1
101  Đang thuê
102  Đang thuê
103  Trống
...
```

Room settings:

```text
rent
electric unit price
water unit price
vehicle
garbage
cable/internet
other
meter enabled flags
sort_order
status
```

## Acceptance criteria

- CRUD/update cần thiết chạy được.
- Không cần hard delete room.
- Có thể cấu hình giá từng phòng.
- Có thể thay đổi sort order.
- Có thể mark VACANT/OCCUPIED.


---

# PHASE 2 — Billing period + meter reading flow

## Goal

Hoàn thành core UX nhập điện nước.

Đây là phase quan trọng nhất.

## Tasks

1. Billing periods.
2. Meter readings.
3. Chọn tháng.
4. Chọn tầng.
5. Progress processed/total.
6. Screen `PHÒNG 101 — 1/11`.
7. Auto previous reading.
8. Current electricity input.
9. Current water input.
10. Live usage calculation.
11. Validation current < previous.
12. `OK & Next`.
13. `DS PHÒNG`.
14. Jump to room.
15. Status icon/text.
16. Skip modal.
17. Skip reason/note.
18. VACANT handling.
19. Tests.

## Critical UX

`OK & Next` phải:

- save;
- không quay ra list;
- tự mở next pending room theo sort_order.

`DS PHÒNG` phải cho user chọn bất kỳ phòng nào trong tầng.

## Acceptance criteria

Demo được:

```text
Tầng 1
→ 101 1/11
→ nhập
→ OK
→ 102 2/11
→ DS phòng
→ chọn 105
→ skip 106
→ xem progress đúng
```

---

# PHASE 3 — Billing engine + draft invoices

## Goal

Từ reading tạo hóa đơn draft chính xác.

## Tasks

1. Invoice model.
2. Invoice item model.
3. Invoice calculator service.
4. Snapshot all unit prices and fixed fees.
5. Auto update draft invoice after reading save.
6. Invoice totals.
7. Invoice page.
8. Tests.

## Invoice items

Ít nhất:

```text
RENT
ELECTRICITY
WATER
VEHICLE
GARBAGE
CABLE
OTHER
```

## Acceptance criteria

Ví dụ:

```text
previous electricity = 14000
current electricity  = 14217
unit price           = 3200
usage                = 217
amount               = 694400
```

Invoice total phải đúng.

Đổi `room_settings.electric_unit_price` sau đó không được làm invoice đã finalized thay đổi.

---

# PHASE 4 — Invoice UI + print templates

## Goal

Hóa đơn nhìn rõ, dùng được trên màn hình và giấy.

## Tasks

1. Invoice detail.
2. Vietnamese formatting.
3. Single invoice print page.
4. A5/half-A4 layout.
5. Compact 3-invoices-per-A4 layout.
6. Batch print page.
7. Print CSS.
8. Hide UI controls when printing.
9. Test rendering routes/data.

## Must display

- Nhà trọ 441.
- Tháng.
- Phòng.
- Tiền phòng.
- Điện old → new.
- Điện usage.
- Điện unit price.
- Tiền điện.
- Nước old → new.
- Nước usage.
- Nước unit price.
- Tiền nước.
- Xe.
- Rác.
- Cáp/Internet.
- Khác.
- Tổng.
- Ghi chú.
- Ngày.
- Người thu.
- Người thuê/signature area.

## Codex prompt — Phase 4

```text
Read the project context.

Implement PHASE 4 only: invoice UI and printable templates.

Create:
1. a readable single invoice,
2. an A5/half-A4 print view,
3. a compact batch layout targeting 3 invoices per A4 sheet.

Prioritize readability for older users:
- large room number,
- large total amount,
- clear old/new meter readings,
- clear line items,
- no unnecessary UI when printing.

Do not add payment tracking or tenant portal.

Run tests/check rendering and stop.
```

---

# PHASE 5 — XLSX / PDF / DOCX exports

## Goal

Xuất được dữ liệu tháng.

## Tasks

1. Excel export.
2. PDF single invoice.
3. PDF batch.
4. DOCX single/batch.
5. Export buttons.
6. Filenames deterministic.
7. Download response headers.
8. Tests.

## Filename examples

```text
441_2026-08_hoa-don.xlsx
441_2026-08_hoa-don.pdf
441_2026-08_hoa-don.docx
441_2026-08_phong-101.pdf
```

## Excel

Gần format cũ.

Không phụ thuộc formula Excel để tính tiền.

## Acceptance criteria

Một billing period có thể export cả 3 format.

Giá trị total giữa:

```text
UI = XLSX = PDF = DOCX
```

phải cùng nguồn dữ liệu.

## Codex prompt — Phase 5

```text
Read the context.

Implement PHASE 5 only: XLSX, PDF, and DOCX exports.

All export values must come from the invoice/domain data already calculated by the server. Do not duplicate billing formulas inside exporters.

Excel should resemble the family's current monthly table and include old/new electricity and water readings, usage, unit prices, fees, totals, statuses, and notes.

PDF must support both single and batch invoices.

DOCX must be readable/editable in Word; pixel-perfect parity with PDF is not required.

Use stable PHP packages compatible with the project's Laravel/PHP version.

Add export tests and stop.
```

---

# PHASE 6 — Period completion/finalization

## Goal

Kết thúc tháng an toàn.

## Tasks

1. Floor completion summary.
2. Period summary.
3. List pending rooms.
4. List skipped rooms.
5. Prevent finalization with pending rooms.
6. Allow explicit override only if specified.
7. Finalize period.
8. Lock invoices.
9. Reopen with confirm.
10. Tests.

## Codex prompt — Phase 6

```text
Implement PHASE 6 only: period completion and finalization.

Before finalization:
- show completion by floor,
- show pending rooms,
- show skipped rooms,
- block normal finalization while PENDING readings remain.

When finalized:
- lock the billing period,
- lock/finalize invoices,
- prevent normal edits and automatic recalculation.

Provide an explicit REOPEN action with confirmation for corrections.

Add tests and stop.
```

---

# PHASE 7 — Backup + operational hardening

## Goal

Dùng local ổn định.

## Tasks

1. SQLite backup artisan command.
2. Retention config.
3. Backup folder.
4. README restore instructions.
5. SQLite WAL/busy handling review.
6. Graceful DB locked error.
7. Production-ish local env settings.
8. Security checklist.
9. Tests where practical.

## Codex prompt — Phase 7

```text
Implement PHASE 7 only: local operational hardening and SQLite backup.

Create a safe database backup command with configurable retention.

Document:
- how to back up,
- how to restore,
- how to copy backups to OneDrive/Google Drive manually,
- how to run the app on a LAN,
- how to avoid exposing it publicly.

Review SQLite WAL/locking behavior for two household devices making short writes.

Do not migrate to MongoDB/MySQL/PostgreSQL.

Run tests and stop.
```

---

# PHASE 8 — Real-use polish

## Goal

Dùng thật 1 tháng ở Nhà 441.

Không tự triển khai phase này trước khi nhận feedback thực tế.

Có thể gồm:

- sửa flow;
- sort order thực tế;
- điều chỉnh labels;
- thêm shortcut;
- sửa invoice layout;
- thêm import dữ liệu tháng trước nếu cần;
- thêm bulk room settings;
- thêm small monthly summary.

Không thêm major modules.

## Codex prompt — Phase 8

```text
Do not invent features.

Use the real feedback supplied after Nhà trọ 441 has used the app for at least one billing cycle.

Implement only the concrete UX/data issues observed in practice.

Keep the project focused on meter reading → billing → invoice → export.
```

---

# 38. Optional future roadmap — DO NOT IMPLEMENT NOW

Sau khi V1 chạy ổn mới cân nhắc:

```text
V2
- payment tracking
- paid/unpaid
- revenue summary
- tenant records
- contracts
- deposit

V3
- temporary residence/person list
- QR room access
- meter photo evidence
- PWA
- Tailscale/HTTPS
- hosted deployment

V4
- multiple properties
- SaaS
```

Đây chỉ là roadmap, không phải scope hiện tại.

---

# 39. Final instruction to Codex

Mỗi lần bắt đầu làm:

1. Đọc file này.
2. Đọc `nhatro-441-erd.md`.
3. Xác định phase được yêu cầu.
4. Chỉ code phase đó.
5. Không tự mở rộng scope.
6. Giữ backward compatibility với các phase trước.
7. Chạy test.
8. Nêu assumption.
9. Dừng.

Ưu tiên số 1 của sản phẩm:

> Người đi ghi điện nước chỉ cần nhìn đồng hồ, nhập vào điện thoại một lần, bấm OK & Next, và không phải ghi lại vào giấy/Excel lần thứ hai.
