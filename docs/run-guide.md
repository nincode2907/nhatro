# Hướng dẫn chạy Nhà trọ 441

## Chạy bằng Docker

Docker Compose tự đọc file `.env` ở thư mục gốc để thay các biến `${...}` trong `compose.yaml`. Nếu file `.env` của bạn đã có `ADMIN_PASSWORD`, có thể chạy trực tiếp:

```bash
docker compose up -d --build
```

`ADMIN_PASSWORD` phải có giá trị để container khởi động. Đặt mật khẩu dài ít nhất 12 ký tự. Compose truyền biến này vào container; nó không tự đưa toàn bộ nội dung `.env` vào container.

Xem log, dừng và khởi động lại:

```bash
docker compose logs -f app
docker compose stop
docker compose up -d
```

Khi source thay đổi, build lại:

```bash
docker compose up -d --build
```

Mỗi lần container khởi động, entrypoint tự chạy migration, `AdminUserSeeder` và `DemoPropertySeeder`. Admin seeder cập nhật tài khoản admin theo `ADMIN_NAME`, `ADMIN_USERNAME`, `ADMIN_PASSWORD` trong env đã chọn. Hãy giữ mật khẩu đúng trong file env đó; nếu đổi mật khẩu đăng nhập trực tiếp trên ứng dụng, lần khởi động container tiếp theo có thể đưa mật khẩu về giá trị trong env.

## Seed dữ liệu thủ công

Khi chạy Laravel trực tiếp trên máy, sau khi cấu hình `.env` và tạo database SQLite, chạy:

```bash
php artisan migrate
php artisan db:seed --class=AdminUserSeeder
php artisan db:seed --class=DemoPropertySeeder
```

`AdminUserSeeder` tạo hoặc cập nhật tài khoản admin theo các biến `ADMIN_NAME`, `ADMIN_USERNAME`, `ADMIN_PASSWORD`. `DemoPropertySeeder` tạo property, tầng, phòng và giá mặc định khi property chưa có tầng. Nếu cấu trúc tầng/phòng đã tồn tại, seeder giữ nguyên cấu trúc đó.

`php artisan db:seed` chỉ chạy `DemoPropertySeeder`; nó không seed admin. Không chạy seed demo trên database muốn giữ sạch hoặc trên dữ liệu production chưa được backup.

## Cấu hình và dữ liệu

- `.env` chứa cấu hình riêng, không commit.
- Với Docker, SQLite nằm ở `database/database.sqlite` trên máy và được mount vào container. Khóa ứng dụng và backup vẫn nằm trong volume `app-data`.
- `stop`, `down` hoặc rebuild không xóa các volume; file SQLite vẫn nằm trong thư mục dự án.
- Không dùng `docker compose down -v` nếu muốn giữ khóa ứng dụng, backup và storage. Database bind-mounted vẫn nằm ở `database/database.sqlite`.
- Hướng dẫn backup/khôi phục và truy cập LAN nằm trong [local-operations.md](local-operations.md).
