# Nhà trọ 441

@/Users/buivannin/.codex/RTK.md

Ứng dụng quản trị nội bộ, local-first cho một nhà trọ: ghi điện nước trên
điện thoại, tính hóa đơn và xuất XLSX/PDF/DOCX. Laravel 13, PHP >=8.3,
SQLite, Blade với CSS/JS trong `public/`; Vite là tooling bổ sung.

## Ranh giới và quy ước

- Giữ phạm vi một property; không tự mở rộng SaaS, thanh toán/QR hoặc đổi DB.
- Controller mỏng; validation trong Form Request, nghiệp vụ trong
  `app/Actions/` và `app/Services/`. Không tính tiền trong Blade.
- Tiền là integer VND; không dùng float cho tính hóa đơn. Dùng enums hiện có.
- Luôn kiểm tra property → period/floor → room thuộc cùng phạm vi;
  giữ middleware `auth` cho các thao tác quản trị.
- Một reading và một invoice cho mỗi room/period. Lưu reading và tính nháp
  trong cùng transaction; bảo toàn invoice items snapshot đã finalized.
- Giữ lịch sử phòng: phòng có reading/invoice chỉ ngừng sử dụng, không xóa.
- UI tiếng Việt, `lang="vi"`, favicon hợp lệ; kiểm tra giao diện điện thoại
  và bản in khi thay đổi các màn hình tương ứng.

## Dữ liệu và thao tác nguy hiểm

- Không commit/in nội dung `.env`, APP_KEY, mật khẩu, SQLite/WAL/SHM, backup.
- Không chạy migration reset/fresh, seed, restore hoặc xóa dữ liệu thật nếu
  task chưa cho phép. Docker entrypoint tự migrate và seed admin/demo khi start.
- Không dùng `docker compose down -v`; volume giữ key, backup và storage.
  SQLite là bind mount `database/`, không nằm trong volume app-data.
- Không đặt DB đang hoạt động vào cloud sync/ổ mạng; backup bằng
  `app:backup-database`. Không expose app ra Internet hay tự mở bind LAN.

## Lệnh từ thư mục gốc

Theo RTK khi có sẵn; nếu không tìm thấy binary, báo và dùng lệnh trực tiếp.
PHP cần >=8.3 với extensions trong README; tại máy này PHP mặc định bị lỗi,
PHP 8.4 đã kiểm tra: `/opt/homebrew/opt/php@8.4/bin/php`.

- Kiểm tra: `php artisan test`; `php vendor/bin/pint --test`.
  Test dùng SQLite `:memory:` trong `phpunit.xml`; không chạy nếu config cache
  hoặc env override khiến DB trỏ vào dữ liệu thật.
- Native: `php artisan serve --host=127.0.0.1 --port=9000`.
- Docker: đọc `docs/local-guide.md` trước khi start/rebuild; `.env` phải có
  ADMIN_PASSWORD. Compose mặc định mount code; `compose.dev.yaml` chỉ bật debug.
- Frontend: `npm run build` cần Node/dependencies; app container không có npm.
- Kiểm tra Compose không in secrets: `docker compose config --quiet`.

Khi start server, giữ terminal tương tác hiển thị logs; chỉ in URL thực tế
sau khi server nghe. Cổng 9000/5173 hiện chưa được Dev Hub cấp block;
đọc registry trước mọi quyết định port/proxy, không coi `.localhost` đã hoạt động.

## Nạp kiến thức theo task

- Điện nước, hóa đơn, export: `.agents/skills/nhatro-billing/SKILL.md`.
- Docker/env/start/seed/backup/restore/LAN: `docs/local-guide.md`.
- Yêu cầu V1: `docs/reference/nhatro-441-codex-context.md`; thiết kế schema:
  `docs/reference/nhatro-441-erd.md`. Đây là yêu cầu/thiết kế, không phải bằng chứng đã triển khai;
  đối chiếu code, migrations và tests trước khi kết luận trạng thái tính năng.
- Lịch sử audit AI: `docs/history/ai-bootstrap.md`; runtime hiện hành đọc local-guide.
- Dùng global `feature-builder` khi thêm feature, `ui-page-builder` khi sửa
  một trang, `task-qa-review` khi QA; `data-model-architecture-review` hoặc
  `security-review` khi task cần review chuyên sâu. Không sao chép các skill này.

Hoàn tất: kiểm tra đúng phạm vi thay đổi, chạy test phù hợp và Pint khi sửa PHP,
đồng bộ tài liệu liên quan; báo rõ kiểm tra chưa chạy và lỗi có sẵn.
