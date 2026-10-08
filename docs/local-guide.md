# Chạy local — Nhà trọ 441

Cập nhật: 08/10/2026. Đây là hướng dẫn vận hành hiện tại; [bản trực quan](local-guide.html) đọc cùng nội dung. App dùng Laravel + SQLite trong Docker, cho một nhà trọ; không cần thiết lập production hoặc dịch vụ cloud.

## 1. Một env, một lệnh chạy

Máy cần Docker Desktop. Dùng duy nhất `.env` ở root; `.env.example` là mẫu. `.env.docker` / `.env.docker.example` đã được bỏ để tránh chọn nhầm port hoặc tài khoản. Bản env riêng cũ được giữ ở `tmp/local-config-backups/` (Git ignore, không đưa vào Docker image).

Nếu đã có `.env`, giữ file đó. Chỉ máy mới chạy:

```bash
cp .env.example .env
```

Điền `ADMIN_PASSWORD` riêng; nên dùng ít nhất 12 ký tự. `ADMIN_USERNAME` mặc định là `admin`. Không commit/chia sẻ `.env`, mật khẩu hay key.

```bash
docker compose up -d --build
```

Mở [http://127.0.0.1:15400](http://127.0.0.1:15400). Đây là URL cấu hình; app dùng được sau khi container khởi động thành công. Xem health/log:

```bash
docker compose ps
docker compose logs -f app
```

Khi Docker Desktop khởi động lại, container có `restart: unless-stopped`. Có thể chạy thủ công `docker compose up -d`; dừng bằng `docker compose stop`.

## 2. Env và dịch vụ

| Biến / thành phần | Giá trị local | Vai trò |
|---|---|---|
| APP_ENV | local | Môi trường app, Compose cố định local |
| APP_PORT / APP_BIND_IP | 15400 / 127.0.0.1 | Host chỉ nghe loopback; app bên trong container dùng 8000 |
| APP_URL | http://127.0.0.1:15400 | URL bên ngoài; cập nhật khi chủ động dùng LAN |
| APP_DEBUG | false trong Compose mặc định | Giữ lỗi chi tiết khỏi màn hình sử dụng; xem logs để debug |
| DOCKER_APP_KEY | Trống mặc định | Docker tạo và giữ key trong volume app-data; không đổi key đã dùng |
| ADMIN_* | Tài khoản trong .env | Entrypoint seed/cập nhật admin mỗi lần start |
| PROPERTY_* | Mã, tên, địa chỉ nhà trọ | Cấu hình property; tránh đổi mã sau khi có dữ liệu |
| DB_BACKUP_RETENTION | 30 | Số bản backup giữ lại |
| SQLite | database/database.sqlite | Bind mount thật, không có port DB, không cần MySQL/Postgres |
| Session/cache | database | Không cần Redis |
| Queue | sync | Không cần worker riêng |
| VITE_PORT | 15430 | Profile dev tùy chọn, bind loopback |

Compose đọc `.env` để nội suy, chỉ truyền các biến khai báo trong `environment` vào container. Các biến DB/session/log trong `.env.example` hỗ trợ tooling PHP tại host; Docker tự cấu hình SQLite, locale vi, timezone Asia/Ho_Chi_Minh, log stderr, session encrypt và queue sync. `APP_KEY` native và `DOCKER_APP_KEY` không thay thế nhau tự động; key Docker đang lưu phải được bảo toàn.

Dev Hub đã đăng ký project `nhatro-441` trong block 15400–15499: app host 15400 → container 8000; Vite host 15430 → container 5173. SQLite không expose port. Proxy `nhatro.localhost` đang tắt; không cần proxy để dùng app. Đọc registry trung tâm trước khi có task thay port/hostname.

## 3. Sửa code và công cụ tùy chọn

Compose mặc định mount `app`, `bootstrap/app.php`, `config`, `database`, `lang`, `public`, `resources`, `routes`, `artisan` vào container. Sửa PHP, Blade, bản dịch hoặc CSS/JS trong `public/` rồi refresh; vendor giữ trong image. Đây là cấu hình dùng local, không có nhánh chạy production riêng.

Đổi `composer.json`, `package.json`, dependency hoặc Dockerfile thì rebuild:

```bash
docker compose up -d --build
```

Giao diện hiện tại dùng CSS/JS trong `public/`, không cần Vite để sử dụng. Nếu làm việc với assets trong `resources/`, profile dev có Node riêng:

```bash
docker compose --profile dev up -d frontend
docker compose logs -f frontend
```

Vite tại `http://127.0.0.1:15430`. App container không có npm; dùng `docker compose run --rm frontend npm run build` khi cần build assets vào thư mục `public/build` trên host. Image cũng có build stage cho assets, nhưng mount `public/` local ưu tiên nội dung host. Không dựa vào image build artifacts nếu host chưa có chúng.

Muốn xem lỗi chi tiết trong khi sửa code, dùng override tùy chọn (chỉ bật APP_DEBUG):

```bash
docker compose -f compose.yaml -f compose.dev.yaml up -d
```

Trở lại mặc định bằng `docker compose up -d`. Không dùng debug khi chia sẻ qua LAN.

## 4. Khởi động và seed

Entrypoint tạo file SQLite nếu thiếu, giữ APP_KEY trong volume, chạy migration rồi `AdminUserSeeder` và `DemoPropertySeeder`. Không reset database. Admin seeder cập nhật theo ADMIN_* mỗi lần start; nếu đổi mật khẩu trong ứng dụng, lần start sau có thể đưa về giá trị env. Vì vậy giữ ADMIN_PASSWORD trong `.env` khi chạy Docker.

Demo seeder tạo property, 5 tầng và 14 phòng khi property chưa có tầng: 101–104, 201–203, 301–302, 401–404, 501. Giá demo: phòng 3.000.000 đ, điện 3.200 đ/kWh, nước 17.000 đ/m³, xe 120.000 đ, rác 30.000 đ. Đây là dữ liệu mẫu cần kiểm tra trên `/rooms`. Khi đã có cấu trúc, seeder giữ nguyên cấu trúc đã sửa. `db:seed` chỉ chạy demo; admin seed riêng.

Không seed thử trên DB muốn giữ sạch. Thay cấu trúc bằng `/property-structure`; phòng có lịch sử chỉ ngừng sử dụng. Luồng dùng chính: `/billing-periods` → ghi điện nước từng phòng → hóa đơn → in A5/3 phiếu A4 hoặc xuất XLSX/PDF/DOCX. Bản in và PDF bỏ nhãn nháp; trạng thái nội bộ và snapshot vẫn giữ để bảo toàn tính tiền/lịch sử. Giao diện quản lý hiển thị “Đã tạo” cho hóa đơn chưa chốt; Excel dùng trạng thái reading nếu có, hoặc nhãn invoice “Đã tạo” khi không có reading. Word hiện không gắn nhãn nháp.

## 5. Dữ liệu và backup

SQLite phải ở ổ local, ngoài `public/`; không đặt DB đang chạy trong OneDrive/Google Drive/Dropbox, SMB/NFS hoặc thư mục sync. Không commit SQLite/WAL/SHM, logs, backup, key.

| Nơi lưu | Nội dung | Giữ khi stop/down/rebuild |
|---|---|---|
| database/ trên host | database.sqlite và WAL/SHM | Có; không tự xóa file |
| app-data volume | Key ứng dụng và /data/backups | Có nếu không dùng down -v |
| app-storage volume | Storage của Laravel | Có nếu không dùng down -v |

Tạo backup nhất quán khi DB dùng WAL:

```bash
docker compose exec app php artisan app:backup-database
```

Lệnh dùng SQLite `VACUUM INTO`, kiểm tra integrity/khóa ngoại trước khi công nhận backup và dọn retention. File ở `/data/backups`; lệnh in tên chính xác. Có thể dùng `--retention=60`; thư mục backup không ở `public/`, chỉ dọn file đúng mẫu của lệnh.

Chép đúng file đã tạo ra host (tạo thư mục backups trước), rồi mới chép bản snapshot đó lên cloud:

```bash
docker compose cp app:/data/backups/TEN-BACKUP.sqlite ./backups/TEN-BACKUP.sqlite
```

Giữ ít nhất một bản ngoài máy; thỉnh thoảng thử restore trên bản sao. Không sync DB đang hoạt động vì WAL có thể chưa được gộp. Backup có thể chứa dữ liệu riêng, không commit hoặc chia sẻ công khai.

## 6. Khôi phục: dừng app trước

Restore thay toàn bộ dữ liệu. Chọn đúng file và backup hiện trạng trước khi làm.

1. Tạo backup hiện trạng, chép ra host như trên.
2. Dừng app: `docker compose stop app` (không down -v).
3. Chép bản cần restore vào container đã dừng:

   ```bash
   docker compose cp ./backups/BAN-CAN-KHOI-PHUC.sqlite app:/data/restore.sqlite
   ```

4. Khi app vẫn dừng, thay database và xóa WAL/SHM cũ:

   ```bash
   docker compose run --rm --no-deps --entrypoint sh app -c 'cp /data/restore.sqlite /var/www/html/database/database.sqlite && rm -f /var/www/html/database/database.sqlite-wal /var/www/html/database/database.sqlite-shm /data/restore.sqlite && chown 1000:1000 /var/www/html/database/database.sqlite && chmod 600 /var/www/html/database/database.sqlite'
   ```

5. Start lại `docker compose up -d app`; kiểm tra logs, đăng nhập, kỳ gần nhất và vài chỉ số phòng.

Không ghi đè database lúc app đang chạy. Start lại cũng chạy migrations/admin/demo theo mục 4.

## 7. LAN là tùy chọn

Mặc định chỉ dùng trên máy qua loopback. Nếu muốn điện thoại cùng Wi-Fi quản trị, đặt private IPv4 cụ thể của máy trong `.env`, ví dụ:

```dotenv
APP_BIND_IP=192.168.1.20
APP_URL=http://192.168.1.20:15400
```

Chạy lại `docker compose up -d`; điện thoại mở URL đó. Giữ debug false. Đặt DHCP reservation để IP ổn định; chỉ cho TCP 15400 trong firewall Private/Home, giới hạn subnet nếu hỗ trợ. Không tự bind 0.0.0.0.

Không port-forward/NAT, DMZ, UPnP mapping, public proxy hoặc tunnel Internet cho app nếu chưa được người dùng yêu cầu. Nếu Wi-Fi người thuê chung hạ tầng, tách private SSID/VLAN; guest không được vào máy quản trị. Không dùng Laravel development server cho Internet công cộng.

## 8. SQLite và kiểm tra mã

Foreign keys, WAL, busy_timeout 5000ms, transaction IMMEDIATE và synchronous NORMAL phục vụ các thao tác ghi ngắn từ hai thiết bị. SQLite có một writer; thiết bị thứ hai chờ tối đa 5 giây. NORMAL giữ nhất quán nhưng mất điện có thể mất giao dịch vừa ghi cuối: cần backup định kỳ, UPS nếu nguồn điện không ổn định. Khi DB bận, đợi rồi thử; nếu lặp lại kiểm tra writer/import bất thường và nơi lưu DB. Không đổi hệ DB chỉ vì dùng hai thiết bị.

Kiểm tra code dùng PHP >=8.3 và vendor dev trên host; không dùng SQLite thật để thử:

```bash
php artisan test
php vendor/bin/pint --test
```

Test cấu hình SQLite `:memory:`. PHP mặc định của máy hiện không chạy được; dùng `/opt/homebrew/opt/php@8.4/bin/php` đã có nếu cần. Image app chỉ có dependencies chạy ứng dụng, không chứa test tooling. Cấu hình Compose kiểm tra không in secrets: `docker compose config --quiet`.

Tài liệu chuyên sâu nằm trong [reference/](reference/nhatro-441-codex-context.md); báo cáo bootstrap cũ trong [history/](history/ai-bootstrap.md) là lịch sử, không ghi đè hướng dẫn này.


## 9. Ngrok do người dùng chủ động bật

Khi dùng ngrok HTTPS trỏ vào app loopback 15400, thêm vào `.env`:

```dotenv
TRUSTED_PROXIES=*
```

Chạy `docker compose up -d app` để truyền biến mới vào container. App chỉ tin
`X-Forwarded-Proto` cho scheme HTTPS; không tin `X-Forwarded-For` hoặc
`X-Forwarded-Host`. Cấu hình này dành cho ngrok chạy trên cùng máy với app
bind `127.0.0.1`, không dùng wildcard trust khi mở app trực tiếp ra LAN/Internet.
Mẫu env để trống mặc định; muốn tắt hỗ trợ proxy, để `TRUSTED_PROXIES=` rồi
chạy lại Compose. Truy cập local không có forwarded header vẫn dùng HTTP.

Laravel sinh CSS/JS, form và redirect theo HTTPS của request ngrok, tránh
mixed content. Không force HTTPS toàn app và không hardcode domain tunnel;
đổi URL ngrok không cần sửa assets. APP_URL giữ URL local cho tooling CLI;
chỉ cần đổi nếu muốn URL sinh ngoài HTTP request dùng domain tunnel.

Không mở tunnel tự động. Không tắt CSRF/auth hoặc rate limit để sửa lỗi tunnel.
Secure cookie vẫn false để dùng được cả HTTP local; khi chuyển sang chỉ HTTPS,
cần cấu hình cookie phù hợp. Giữ APP_DEBUG=false và không expose port Vite.

## 10. Giá mặc định và nhập tiền

Trong **Phòng**, chọn **Giá mặc định** cạnh **Quản lý tầng & phòng** để sửa giá phòng, điện, nước, xe, rác và Internet. Phòng mới sao chép các mức giá này ngay khi tạo; có thể sửa giá riêng tại cài đặt phòng. Đổi giá mặc định chỉ áp dụng cho các phòng thêm sau đó. Các ô nhập tiền và hóa đơn dùng dấu phẩy ngăn cách hàng nghìn, ví dụ `1,000,000`; dữ liệu vẫn lưu số nguyên VND.

## 11. Đóng kỳ

Kỳ chỉ đóng được khi tất cả phòng đang hoạt động đã ghi chỉ số hoặc được bỏ qua. Đóng kỳ sẽ chốt hóa đơn, khóa chỉ số và lưu người cùng thời điểm đóng; không chỉnh sửa thông thường sau đó.
