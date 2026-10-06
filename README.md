# Nhà trọ 441

Ứng dụng chạy local bằng Docker để ghi điện nước, tính tiền phòng và in/xuất hóa đơn. Dữ liệu lưu bằng SQLite trên máy, không cần dịch vụ production, Redis hay cloud.

## Chạy ứng dụng

Máy chỉ cần Docker Desktop đang mở. Lần đầu tạo `.env`:

```bash
cp .env.example .env
```

Đặt `ADMIN_PASSWORD` trong `.env` (giữ `.env` hiện có nếu đã dùng app), rồi chạy:

```bash
docker compose up -d --build
```

Mở [http://127.0.0.1:9000](http://127.0.0.1:9000) và đăng nhập với `ADMIN_USERNAME` / `ADMIN_PASSWORD` trong `.env`.

Các lần sau:

```bash
docker compose up -d
docker compose logs -f app
```

Sửa PHP, Blade, CSS/JS trong `public/` rồi refresh là thấy thay đổi. Khi đổi dependency hoặc Dockerfile, chạy lại lệnh có `--build`.

Dừng app bằng `docker compose stop`. Không dùng `docker compose down -v`: volume giữ khóa ứng dụng, backup và storage. SQLite nằm trong `database/database.sqlite` của repo.

## Tài liệu

- [Hướng dẫn local đầy đủ](docs/local-guide.md) · [bản trực quan](docs/local-guide.html): env, Docker, backup/khôi phục và LAN.
- [Yêu cầu V1](docs/reference/nhatro-441-codex-context.md) và [thiết kế ERD](docs/reference/nhatro-441-erd.md): đọc theo phần cần làm, đối chiếu với code để biết trạng thái triển khai.
- [AGENTS.md](AGENTS.md) và [.agents/skills/nhatro-billing/SKILL.md](.agents/skills/nhatro-billing/SKILL.md): hướng dẫn cho agent.
- [Báo cáo bootstrap ngày 05/10/2026](docs/history/ai-bootstrap.md) · [HTML](docs/history/ai-bootstrap.html): lịch sử kiểm tra, không phải cấu hình runtime hiện tại.

Bản in/PDF không gắn nhãn “Bản nháp”; dữ liệu hóa đơn vẫn giữ trạng thái nội bộ và snapshot để bảo toàn lịch sử.
