# Nhà trọ 441

Ứng dụng Laravel nội bộ, local-first cho Nhà trọ 441. Phase 5 bao gồm khung dự án, SQLite, một tài khoản quản trị, cấu trúc nhà/tầng/phòng, cấu hình giá, kỳ tháng, luồng nhập chỉ số, hóa đơn nháp có snapshot, mẫu in và xuất XLSX/PDF/DOCX. Chưa có chốt kỳ, thanh toán hay QR.

## Yêu cầu

- PHP 8.3 trở lên với các extension `curl`, `dom`, `fileinfo`, `gd`, `libxml`, `mbstring`, `openssl`, `pdo_sqlite`, `simplexml`, `sqlite3`, `xml`, `xmlreader`, `xmlwriter` và `zip`.
- Composer 2.
- Node.js không bắt buộc cho giao diện hiện tại; các file Vite được giữ lại cho các phase sau.

Nếu dùng Docker theo hướng dẫn ngay bên dưới thì máy chỉ cần Docker Desktop; không cần cài PHP, Composer hay Node.js.

## Chạy bằng Docker — khuyến nghị

### Lần đầu

Tạo file cấu hình riêng, không được commit:

```powershell
Copy-Item .env.docker.example .env.docker
```

Mở `.env.docker` và đặt `ADMIN_PASSWORD` dài ít nhất 12 ký tự. Sau đó build và chạy:

```powershell
docker compose --env-file .env.docker up -d --build
```

Mở `http://127.0.0.1:8000`. Container tự động:

- tạo và lưu `APP_KEY` trong Docker volume;
- tạo file SQLite;
- chạy migrations;
- tạo/cập nhật một admin từ `.env.docker`;
- seed dữ liệu demo mà không ghi đè cài đặt đã sửa.

### Những lần sau

Container có `restart: unless-stopped`, vì vậy thường sẽ tự chạy khi Docker Desktop khởi động. Nếu cần chạy thủ công:

```powershell
docker compose --env-file .env.docker up -d
```

Xem log hoặc dừng app:

```powershell
docker compose --env-file .env.docker logs -f app
docker compose --env-file .env.docker stop
```

Khi source code thay đổi, rebuild image:

```powershell
docker compose --env-file .env.docker up -d --build
```

SQLite và khóa ứng dụng nằm trong volume `nhatro-441_app-data`; storage nằm trong `nhatro-441_app-storage`. Lệnh `stop`, `down`, restart hoặc rebuild image không xóa các volume này.

> Không chạy `docker compose down -v` trừ khi muốn xóa toàn bộ database và khóa ứng dụng. Thao tác này không thể hoàn tác nếu không có backup.

### Truy cập Docker qua LAN

Trong `.env.docker`, đặt IP private của máy chạy Docker, ví dụ:

```dotenv
APP_URL=http://192.168.1.20:8000
```

Sau đó chạy lại `docker compose --env-file .env.docker up -d`. Điện thoại cùng Wi-Fi truy cập URL trên. Chỉ mở TCP 8000 cho Private network và không port-forward ra Internet.

## Cài đặt local

Phần này chỉ cần khi không dùng Docker.

```bash
composer install
cp .env.example .env
php artisan key:generate
```

Tạo file SQLite rỗng:

```bash
# macOS / Linux
touch database/database.sqlite

# Windows PowerShell
New-Item -ItemType File -Force database/database.sqlite
```

Mở `.env`, đặt một mật khẩu riêng dài ít nhất 12 ký tự tại `ADMIN_PASSWORD`, sau đó chạy:

```bash
php artisan migrate
php artisan db:seed --class=AdminUserSeeder
php artisan db:seed --class=DemoPropertySeeder
```

Seeder chỉ tạo hoặc cập nhật một tài khoản admin và không có mật khẩu mặc định trong source. Sau khi seed thành công, xóa giá trị `ADMIN_PASSWORD` khỏi `.env` rồi chạy:

```bash
php artisan config:clear
```

Khởi động trên máy hiện tại:

```bash
php artisan serve
```

Mở `http://127.0.0.1:8000`. Route `/` sẽ chuyển tới `/login` nếu chưa đăng nhập.

## Dữ liệu demo

`DemoPropertySeeder` tạo một property theo `PROPERTY_CODE` / `PROPERTY_NAME` trong `.env`, bốn tầng và 45 phòng demo:

- Tầng 1: 101–111.
- Tầng 2: 201–211.
- Tầng 3: 301–311.
- Tầng 4: 401–412.

Giá demo ban đầu: phòng 3.000.000 đ, điện 3.200 đ/kWh, nước 17.000 đ/m³, xe 120.000 đ, rác 30.000 đ. Đây không phải dữ liệu thực tế đã được xác nhận; hãy kiểm tra và sửa từ trang `/rooms`.

Seeder có thể chạy lại mà không tạo bản ghi trùng và không ghi đè cấu hình phòng đã sửa. `php artisan db:seed` cũng chạy demo seeder; admin vẫn được seed riêng để không giữ mật khẩu trong `.env`.

Trang hiện có:

- `/billing-periods`: chọn/tạo kỳ tháng và xem tiến độ từng tầng.
- `/billing-periods/{period}/floors/{floor}/rooms/{room}/reading`: ghi điện nước, nhảy phòng, bỏ qua có lý do và OK & Tiếp.
- `/billing-periods/{period}/invoices`: xem các hóa đơn nháp đã tự tính từ chỉ số và cấu hình giá.
- `/billing-periods/{period}/invoices/{invoice}`: xem hóa đơn rõ ràng và mở bản in A5.
- `/billing-periods/{period}/invoices/print`: xem trước/in hàng loạt 3 phiếu trên mỗi tờ A4.
- `/billing-periods/{period}/exports/invoices.xlsx`: tải bảng Excel tháng, gồm chỉ số cũ/mới, lượng dùng, đơn giá, phí, tổng, trạng thái và ghi chú.
- `/billing-periods/{period}/exports/invoices.pdf`: tải PDF hóa đơn hàng loạt; từng hóa đơn cũng có nút tải PDF riêng.
- `/billing-periods/{period}/exports/invoices.docx`: tải Word có thể chỉnh sửa; từng hóa đơn cũng có nút tải Word riêng.
- `/rooms`: danh sách tầng/phòng theo `sort_order`.
- `/rooms/{room}/settings`: sửa trạng thái, thứ tự đi, giá, phí và cờ đồng hồ.
- `/property-structure`: thêm/sửa tầng, thêm/sửa/chuyển phòng, xóa phòng chưa có lịch sử và ngừng sử dụng phòng đã có lịch sử.

Demo seeder chỉ tạo 4 tầng và 45 phòng khi property chưa có tầng nào. Sau khi cấu trúc được chỉnh trên giao diện, những lần khởi động Docker hoặc chạy seeder tiếp theo sẽ giữ nguyên dữ liệu đó.

## Truy cập trong mạng LAN

Chỉ dùng LAN trên Wi-Fi/mạng riêng tin cậy. Không port-forward cổng 8000 và không expose trực tiếp ra Internet.

1. Tìm IPv4 private của máy chạy app: dùng `ipconfig` trên Windows hoặc `ip addr` trên Linux. Ví dụ: `192.168.1.20`.
2. Trong `.env`, đặt `APP_URL=http://192.168.1.20:8000` và giữ `APP_DEBUG=false`.
3. Chạy server lắng nghe trong LAN:

   ```bash
   php artisan serve --host=0.0.0.0 --port=8000
   ```

4. Trên điện thoại cùng Wi-Fi, mở `http://192.168.1.20:8000`.
5. Nếu firewall hỏi, chỉ cho phép PHP/cổng TCP 8000 trên **Private network**, không phải Public network.

Nếu người thuê và gia đình dùng chung Wi-Fi, nên tách mạng quản trị/guest Wi-Fi. Laravel development server phù hợp cho quy mô local V1, không phải public production server.

## SQLite

- Database mặc định: `database/database.sqlite`, nằm ngoài `public/` và bị Git ignore.
- Foreign keys được bật.
- `busy_timeout=5000ms`, WAL journal mode và `synchronous=NORMAL` được cấu hình cho các thao tác ghi ngắn trên LAN.
- File `database.sqlite-wal` và `database.sqlite-shm` nếu xuất hiện cũng không được commit.
- Backup/restore an toàn thuộc Phase 7, chưa được triển khai trong Phase 0.

## Kiểm tra

```bash
php artisan test
./vendor/bin/pint --test
```

Test dùng SQLite `:memory:` và không ghi vào database local.

## Cấu hình an toàn

- `.env`, SQLite runtime files, logs, backup và dependencies không được commit.
- Không chia sẻ `APP_KEY` hoặc mật khẩu admin.
- Giữ `APP_DEBUG=false` khi dùng trong gia đình.
- Dùng mật khẩu admin riêng, không trùng mật khẩu Wi-Fi hay email.

## Phạm vi tiếp theo

Chốt kỳ, thanh toán, QR và backup thuộc các phase sau trong `nhatro-441-codex-context.md`; chưa được triển khai.
