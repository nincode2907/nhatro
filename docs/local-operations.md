# Vận hành local, backup và khôi phục SQLite

Tài liệu này dành cho máy đang chạy Nhà trọ 441 bằng Docker hoặc Laravel local. Database phải nằm trên ổ đĩa local của máy chạy app. Không đặt file database đang hoạt động trong OneDrive, Google Drive, Dropbox, ổ mạng SMB/NFS hoặc thư mục được đồng bộ trực tiếp.

## Tạo backup

Lệnh backup dùng `VACUUM INTO` của SQLite, vì vậy tạo được một ảnh chụp nhất quán cả khi database đang dùng WAL. Lệnh kiểm tra `integrity_check` và khóa ngoại trước khi công nhận bản backup, sau đó mới xóa các bản cũ vượt retention.

### Docker

```bash
docker compose --env-file .env.docker exec app php artisan app:backup-database
```

Backup được lưu trong `/data/backups` thuộc Docker volume `app-data`. Kết quả lệnh cho biết chính xác tên file.

Retention mặc định là 30 bản. Đổi mặc định trong `.env.docker`:

```dotenv
DB_BACKUP_RETENTION=30
```

Hoặc chỉ ghi đè cho một lần chạy:

```bash
docker compose --env-file .env.docker exec app php artisan app:backup-database --retention=60
```

### Chạy Laravel không qua Docker

```bash
php artisan app:backup-database
```

Mặc định file nằm trong `storage/app/backups`. Có thể đổi trong `.env`:

```dotenv
DB_BACKUP_PATH=storage/app/backups
DB_BACKUP_RETENTION=30
```

Thư mục backup không được nằm trong `public/`. Retention chỉ xóa các file đúng mẫu do lệnh này tạo; không xóa file lạ trong cùng thư mục.

## Chép backup ra OneDrive hoặc Google Drive

Đây là thao tác thủ công, không cần cấp quyền cloud cho ứng dụng.

Với Docker, tạo một thư mục `backups` trên máy rồi chép đúng file được lệnh backup báo:

```bash
docker compose --env-file .env.docker cp app:/data/backups/2026-08-12_230000_123456.sqlite ./backups/2026-08-12_230000_123456.sqlite
```

Sau đó dùng Finder/File Explorer chép file `.sqlite` này vào thư mục OneDrive hoặc Google Drive và chờ ứng dụng cloud báo đồng bộ hoàn tất.

Với Laravel local, chép file từ `storage/app/backups` vào thư mục cloud. Nên giữ ít nhất một bản ngoài máy chạy app và thỉnh thoảng thử khôi phục trên một bản sao. Không đồng bộ trực tiếp `/data/database.sqlite` hoặc `database/database.sqlite` đang chạy, vì file WAL có thể chưa được gộp và cloud sync không cung cấp snapshot nhất quán.

## Khôi phục backup

Khôi phục sẽ thay toàn bộ dữ liệu hiện tại. Chọn đúng file và giữ một bản backup của dữ liệu hiện tại trước khi làm.

### Docker

1. Khi app còn chạy, tạo thêm một backup hiện trạng bằng lệnh ở trên và chép nó ra máy host.
2. Dừng container app, không dùng `down -v`:

   ```bash
   docker compose --env-file .env.docker stop app
   ```

3. Chép file cần khôi phục vào volume của container đã dừng:

   ```bash
   docker compose --env-file .env.docker cp ./backups/BAN-CAN-KHOI-PHUC.sqlite app:/data/restore.sqlite
   ```

4. Thay database khi app vẫn đang dừng và xóa các file WAL/SHM cũ:

   ```bash
   docker compose --env-file .env.docker run --rm --no-deps --entrypoint sh app -c 'cp /data/restore.sqlite /data/database.sqlite && rm -f /data/database.sqlite-wal /data/database.sqlite-shm /data/restore.sqlite && chown 1000:1000 /data/database.sqlite && chmod 600 /data/database.sqlite'
   ```

5. Khởi động lại và kiểm tra đăng nhập, kỳ gần nhất và vài chỉ số phòng:

   ```bash
   docker compose --env-file .env.docker up -d app
   docker compose --env-file .env.docker logs --tail=50 app
   ```

### Laravel local

1. Tạo backup hiện trạng.
2. Dừng hoàn toàn `php artisan serve` và mọi tiến trình queue đang dùng app.
3. Chép file backup đã chọn thành một file tạm cạnh `database/database.sqlite`.
4. Thay `database/database.sqlite` bằng file tạm, đồng thời xóa `database.sqlite-wal` và `database.sqlite-shm` cũ nếu có.
5. Chạy `php artisan migrate --force`, khởi động app và kiểm tra dữ liệu.

Không khôi phục bằng cách ghi đè database trong lúc app còn chạy.

## Chạy app trong mạng LAN

### Docker

Mặc định Docker chỉ bind vào `127.0.0.1`, nên thiết bị khác không truy cập được. Để dùng trong LAN, tìm IPv4 private của máy chạy Docker, ví dụ `192.168.1.20`, rồi đặt trong `.env.docker`:

```dotenv
APP_BIND_IP=192.168.1.20
APP_URL=http://192.168.1.20:8000
APP_DEBUG=false
```

Khởi động lại:

```bash
docker compose --env-file .env.docker up -d
```

Điện thoại cùng Wi-Fi mở `http://192.168.1.20:8000`. Nên đặt DHCP reservation trên router để IP máy chạy app không đổi.

### Laravel local

```bash
php artisan serve --host=192.168.1.20 --port=8000
```

Chỉ cho phép TCP 8000 trong firewall trên mạng Private/Home. Nếu hệ điều hành hỗ trợ giới hạn subnet, chỉ cho phép dải LAN gia đình, ví dụ `192.168.1.0/24`.

## Không đưa app ra Internet công cộng

- Không cấu hình port forwarding/NAT cho cổng 8000 trên router.
- Không bật DMZ host, UPnP mapping, public reverse proxy hoặc tunnel như ngrok/Cloudflare Tunnel cho app này.
- Không bind Docker vào `0.0.0.0` nếu có thể bind thẳng private LAN IP.
- Giữ `APP_DEBUG=false`, dùng mật khẩu admin riêng và không chia sẻ `APP_KEY`/`.env`.
- Nếu Wi-Fi người thuê dùng chung hạ tầng, tách thiết bị quản trị sang private SSID/VLAN; guest Wi-Fi không được truy cập máy chạy app.
- Không dùng Laravel development server như một public production server.

Có thể kiểm tra từ mạng di động 4G/5G rằng `http://IP-CONG-CONG:8000` không truy cập được. Không đăng địa chỉ LAN, file backup hoặc thông tin đăng nhập lên nơi công cộng.

## WAL và hai thiết bị trong gia đình

Cấu hình hiện tại dùng `journal_mode=WAL`, `busy_timeout=5000ms`, `synchronous=NORMAL`, khóa ngoại và transaction `IMMEDIATE`:

- WAL cho phép thiết bị đang đọc tiếp tục đọc trong lúc một ghi ngắn diễn ra.
- SQLite vẫn chỉ có một writer tại một thời điểm. `IMMEDIATE` nhận quyền ghi ngay từ đầu transaction; writer thứ hai chờ tối đa 5 giây thay vì dễ lỗi khi nâng cấp từ transaction đọc sang ghi.
- Các thao tác ghi chỉ số, bỏ qua phòng và cập nhật hóa đơn đang nằm trong transaction ngắn. Với hai điện thoại gia đình thỉnh thoảng bấm lưu gần nhau, chúng sẽ được tuần tự hóa và cấu hình này là phù hợp.
- `synchronous=NORMAL` trong WAL ưu tiên hiệu năng và vẫn giữ database nhất quán; sự cố mất điện có thể làm mất giao dịch vừa ghi cuối cùng, vì vậy cần backup định kỳ và UPS nếu nguồn điện không ổn định.
- Nếu gặp thông báo database đang bận, đợi thao tác trên thiết bị kia xong rồi thử lại. Nếu lỗi lặp lại, kiểm tra có tiến trình import/backup bất thường hoặc database bị đặt trên ổ mạng/cloud sync hay không.

SQLite vẫn phù hợp cho quy mô này. Cần đánh giá lại chỉ khi xuất hiện nhiều writer đồng thời hoặc workload ghi kéo dài; Phase 7 không thay đổi sang MongoDB, MySQL hay PostgreSQL.
