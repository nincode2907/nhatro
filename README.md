# Nhà trọ 441

Ứng dụng Laravel nội bộ, local-first cho Nhà trọ 441. Ứng dụng dùng SQLite và có lệnh backup an toàn để vận hành trong mạng gia đình. Chưa có thanh toán hay QR.

## Yêu cầu

- PHP 8.3 trở lên với các extension `curl`, `dom`, `fileinfo`, `gd`, `libxml`, `mbstring`, `openssl`, `pdo_sqlite`, `simplexml`, `sqlite3`, `xml`, `xmlreader`, `xmlwriter` và `zip`.
- Composer 2.
- Node.js không bắt buộc cho giao diện hiện tại; các file Vite được giữ lại cho các phase sau.

Nếu dùng Docker theo hướng dẫn ngay bên dưới thì máy chỉ cần Docker Desktop; không cần cài PHP, Composer hay Node.js.

Hướng dẫn riêng về chạy Docker, seed admin/dữ liệu demo và bảo toàn database nằm tại [docs/run-guide.md](docs/run-guide.md).

## Chạy bằng Docker — khuyến nghị

### Lần đầu

Compose tự đọc `.env` ở thư mục gốc. Đặt `ADMIN_PASSWORD` trong file này rồi build và chạy:

```powershell
docker compose up -d --build
```

Mở `http://127.0.0.1:9000`. Container tự động:

- tạo và lưu `APP_KEY` trong Docker volume;
- tạo file SQLite;
- chạy migrations;
- tạo/cập nhật một admin từ `.env`;
- seed dữ liệu demo mà không ghi đè cài đặt đã sửa.

### Những lần sau

Container có `restart: unless-stopped`, vì vậy thường sẽ tự chạy khi Docker Desktop khởi động. Nếu cần chạy thủ công:

```powershell
docker compose up -d
```

Xem log hoặc dừng app:

```powershell
docker compose logs -f app
docker compose stop
```

Khi source code thay đổi, rebuild image:

```powershell
docker compose up -d --build
```

### Build giao diện trong Docker

Image ứng dụng tự chạy `npm run build` trong một Docker build stage. Node.js không có trong container `app`, nên không chạy `npm` bằng `docker compose exec app`.

Để chạy Vite ở chế độ phát triển hoàn toàn trong Docker, khởi động thêm service `frontend`:

```bash
docker compose --profile dev up -d --build
```

Theo dõi log Vite:

```bash
docker compose logs -f frontend
```

Service này mở Vite tại `http://127.0.0.1:5173`. Khi đang chạy, nếu cần gọi lệnh npm thủ công thì dùng:

```bash
docker compose run --rm frontend npm run build
```

Không cần và không nên chạy `npm` bên trong container `app`.

### Chế độ development — sửa code không cần rebuild

Khi đang sửa PHP, Blade, CSS hoặc route thường xuyên, chạy một lần ở chế độ development:

```bash
docker compose -f compose.yaml -f compose.dev.yaml --profile dev up -d --build
```

`compose.dev.yaml` mount trực tiếp các thư mục mã nguồn vào container PHP. Sau khi lệnh trên chạy xong:

- sửa PHP, Blade, route hoặc CSS trong `public/` → chỉ cần refresh trình duyệt;
- sửa CSS/JS trong `resources/` → Vite tự theo dõi và cập nhật;
- thay đổi `composer.json`, `package.json`, Dockerfile hoặc dependency → chạy lại lệnh có `--build`.

Khi muốn khởi động lại development mà không cần build image, dùng:

```bash
docker compose -f compose.yaml -f compose.dev.yaml --profile dev up -d
```

Chế độ mặc định không dùng `compose.dev.yaml` vẫn là production-like: source được copy vào image và phù hợp để chạy ổn định.

Khi chạy Docker, SQLite nằm tại `database/database.sqlite` trong thư mục dự án và được mount vào container. Khóa ứng dụng và backup nằm trong volume `nhatro-441_app-data`; storage nằm trong `nhatro-441_app-storage`. Lệnh `stop`, `down`, restart hoặc rebuild image không xóa database hoặc các volume này.

> Không chạy `docker compose down -v` trừ khi muốn xóa các volume chứa khóa ứng dụng, backup và storage. Database bind-mounted ở `database/database.sqlite` vẫn nằm trong thư mục dự án.

### Truy cập Docker qua LAN

Trong `.env`, đặt IP private của máy chạy Docker, ví dụ:

```dotenv
APP_BIND_IP=192.168.1.20
APP_URL=http://192.168.1.20:9000
```

Sau đó chạy lại `docker compose up -d`. Điện thoại cùng Wi-Fi truy cập URL trên. Chỉ mở TCP 9000 cho Private network và không port-forward ra Internet.

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

Mở `http://127.0.0.1:9000`. Route `/` sẽ chuyển tới `/login` nếu chưa đăng nhập.

## Dữ liệu demo

`DemoPropertySeeder` tạo một property theo `PROPERTY_CODE` / `PROPERTY_NAME` trong env, năm tầng và 14 phòng demo:

- Tầng 1: 101–104.
- Tầng 2: 201–203.
- Tầng 3: 301–302.
- Tầng 4: 401–404.
- Tầng 5: 501.

Giá demo ban đầu: phòng 3.000.000 đ, điện 3.200 đ/kWh, nước 17.000 đ/m³, xe 120.000 đ, rác 30.000 đ. Đây không phải dữ liệu thực tế đã được xác nhận; hãy kiểm tra và sửa từ trang `/rooms`.

Demo seeder có thể chạy lại mà không tạo bản ghi trùng và không ghi đè cấu hình phòng đã sửa. `php artisan db:seed` chạy demo seeder; admin được seed riêng bằng `php artisan db:seed --class=AdminUserSeeder`. Docker tự chạy cả hai seeder mỗi lần container khởi động.

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

Demo seeder chỉ tạo 5 tầng và 14 phòng khi property chưa có tầng nào. Sau khi cấu trúc được chỉnh trên giao diện, những lần khởi động Docker hoặc chạy seeder tiếp theo sẽ giữ nguyên dữ liệu đó.

## Truy cập trong mạng LAN

Chỉ dùng LAN trên Wi-Fi/mạng riêng tin cậy. Không port-forward cổng 9000 và không expose trực tiếp ra Internet.

1. Tìm IPv4 private của máy chạy app: dùng `ipconfig` trên Windows hoặc `ip addr` trên Linux. Ví dụ: `192.168.1.20`.
2. Trong `.env`, đặt `APP_URL=http://192.168.1.20:9000` và giữ `APP_DEBUG=false`.
3. Chạy server lắng nghe trong LAN:

   ```bash
   php artisan serve --host=0.0.0.0 --port=9000
   ```

4. Trên điện thoại cùng Wi-Fi, mở `http://192.168.1.20:9000`.
5. Nếu firewall hỏi, chỉ cho phép PHP/cổng TCP 9000 trên **Private network**, không phải Public network.

Nếu người thuê và gia đình dùng chung Wi-Fi, nên tách mạng quản trị/guest Wi-Fi. Laravel development server phù hợp cho quy mô local V1, không phải public production server.

## SQLite

- Database: `database/database.sqlite`, nằm ngoài `public/` và bị Git ignore. Khi chạy Docker, đây cũng là file được container sử dụng qua bind mount.
- Foreign keys được bật.
- `busy_timeout=5000ms`, WAL, transaction `IMMEDIATE` và `synchronous=NORMAL` được cấu hình cho hai thiết bị gia đình thực hiện các thao tác ghi ngắn.
- File `database.sqlite-wal` và `database.sqlite-shm` nếu xuất hiện cũng không được commit.
- Tạo backup bằng `php artisan app:backup-database`; retention mặc định là 30 bản và có thể đổi bằng `DB_BACKUP_RETENTION` hoặc `--retention`.
- Hướng dẫn đầy đủ về backup, restore, chép sang OneDrive/Google Drive và chạy LAN nằm tại [docs/local-operations.md](docs/local-operations.md).

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

Thanh toán, QR và các thay đổi sau khi dùng thực tế thuộc các phase sau; Phase 7 không thay đổi hệ quản trị SQLite.
