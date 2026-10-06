# Bootstrap AI — Nhà trọ 441

> Báo cáo lịch sử ngày 05/10/2026. Cấu hình hiện hành: [hướng dẫn local](../local-guide.md).

Ngày kiểm tra: 05/10/2026 (Asia/Ho_Chi_Minh). Markdown này là nguồn chuẩn;
[bản HTML](ai-bootstrap.html) dành cho người đọc. Kiểm tra Git ban đầu: sạch.

## Kết quả và phạm vi

- Trước: không có AGENTS.md ở root/nested trong repo, không có skill riêng.
  Sau: `AGENTS.md` 65 dòng, skill `nhatro-billing` 45 dòng.
- Hướng dẫn luôn nạp chứa stack, ranh giới kiến trúc, invariant dữ liệu,
  bảo toàn SQLite/key/storage, lệnh kiểm tra và routing theo task.
- Không di chuyển/xóa tài liệu gốc; không đổi DB, dữ liệu thật, credentials,
  port, registry, proxy hoặc PHP hệ thống. Không commit/push.
- Thêm favicon SVG và reference vào layout, trang lỗi DB bận, hai trang in.
  Các trang HTML này đã có `lang="vi"`; `public/favicon.ico` cũ là file rỗng.
- Sửa README native serve thành `--host=127.0.0.1 --port=9000`, khớp URL
  được hướng dẫn. Lệnh cũ không chỉ định port, mặc định Laravel là 8000.

## Nhà của kiến thức

| Loại | Nguồn chuẩn / nơi nạp | Khi cần |
|---|---|---|
| A — Core | [AGENTS.md](../../AGENTS.md) | Mọi task trong repo |
| B — Workflow | [nhatro-billing](../../.agents/skills/nhatro-billing/SKILL.md) | Reading, giá, hóa đơn, in/export |
| C — Reference | [run-guide.md](../local-guide.md), [local-operations.md](../local-guide.md) | Start/seed, backup/restore/LAN |
| C/D — Yêu cầu và kế hoạch | [nhatro-441-codex-context.md](../reference/nhatro-441-codex-context.md) | Tra yêu cầu V1 và phase dự kiến |
| C/D — Schema và rationale | [nhatro-441-erd.md](../reference/nhatro-441-erd.md) | Tra thiết kế, invariants; so với migrations |
| E — Nội dung cần đối chiếu | Thiết kế/phase không phải trạng thái implementation | Xác minh với routes, code, tests |

Không có AGENTS.md dài cần tách. Hai tài liệu gốc giữ nguyên toàn bộ nội dung;
không nạp chúng cho mọi task. Ví dụ: plan có phase chốt kỳ, nhưng routes hiện
chưa có endpoint chốt kỳ; có enum/guard finalized không có nghĩa UI đã hoàn tất.
Thông tin đã xác minh từ code đi vào skill; không sao chép toàn bộ plan vào skill.

Global skills có sẵn được route thay vì nhân bản: `feature-builder` khi thêm
feature, `ui-page-builder` khi sửa một trang, `task-qa-review` khi QA task,
`data-model-architecture-review` khi review model/transaction,
`security-review` khi review bảo mật. Bootstrap dùng `project-ai-bootstrap`
và `skill-creator`; không cài global skill mới.

## Phát hiện về instruction và tooling

| Phát hiện | Bằng chứng | Xử lý |
|---|---|---|
| Thiếu operating manual | Không có AGENTS.md trong repo | Tạo root, không có scope nested cần giữ |
| Kế hoạch lớn dễ bị hiểu là tính năng đã có | Phase 6 trong context; `routes/web.php` thiếu route finalize | Route on demand và yêu cầu đối chiếu code |
| Native serve và URL không khớp | README chỉ có `php artisan serve` nhưng URL 9000 | Chỉ định host/port trong README |
| RTK không khả dụng | PATH và vị trí binary phổ biến không có `rtk` | Giữ reference RTK, ghi fallback rõ |
| PHP mặc định không chạy | PHP 7.4 lỗi thiếu libffi.8.dylib | Dùng PHP 8.4 sẵn có, không đổi hệ thống |
| Favicon trống | `public/favicon.ico` 0 byte, không có reference icon trong layout | Thêm SVG và reference |

## Runtime local: cấu hình và quan sát

Registry tồn tại ở `/Users/buivannin/Desktop/workspace/personal/dev-hub/projects.yml`.
Không có entry khớp đường dẫn repo Nhà trọ. Cổng dưới đây **chưa được cấp block**.

| Thành phần | Cấu hình hiện tại | Quan sát khi kiểm tra |
|---|---|---|
| App Docker | `${APP_BIND_IP:-127.0.0.1}:${APP_PORT:-9000}:8000` | Không có app Nhà trọ đang nghe trong `docker ps`/lsof |
| Vite Docker (profile dev) | `${VITE_PORT:-5173}:5173` | Không thấy listener 5173; publication thiếu host IP, có thể mở wildcard/LAN khi start |
| `.env` | APP_PORT 9000, APP_URL loopback 9000 | Chỉ đọc biến runtime không nhạy cảm |
| `.env.docker` | APP_PORT/URL 8000 | Khác `.env`; Compose chỉ dùng nếu chọn `--env-file .env.docker` |
| `.env.example` | APP_URL loopback 8000 | Template native, khác hướng dẫn Docker 9000 |
| Shared Caddy | Docker `0.0.0.0:80->80`, Caddy bind `0.0.0.0` trong container | Host lsof hiển thị IPv6 wildcard; không phải loopback-only |
| `nhatro.localhost` | Chưa có route trong Caddyfile | Hostname đề xuất, chưa xác minh HTTP/browser |

Dev Hub `npm run discover` hoàn tất, không báo conflict trong các project đã
đăng ký. `npm run allocate -- --name 'Nhà trọ 441' --path <repo>` trả exit 2:
đường dẫn đã được discovery nhận diện là project chưa đăng ký, cần review
identity/service trước khi cấp block. Không có allocation hợp lệ để migrate.
Không coi gợi ý `next` của discovery là block đã reserve hoặc đã kiểm tra đầy đủ.

URL theo cấu hình hiện tại là `http://127.0.0.1:9000` **khi app được start**;
không tuyên bố app đang chạy. Không start Docker vì entrypoint migrate/seed
trên SQLite bind mount thật. Không khởi động dịch vụ hoặc thay bind của proxy.

## Đề xuất runtime cần quyết định riêng

1. Review identity Nhà trọ 441 và services app + frontend; chạy lại allocator
   sau khi xử lý tình trạng discovery, kiểm tra toàn block IPv4/IPv6 và Docker.
2. Reserve registry theo quy ước x000 app, x030 frontend, SQLite không expose
   port. Không ghi một block giả định vào cấu hình project.
3. Đồng bộ `compose.yaml`, env templates, env local chỉ các biến runtime,
   Vite host/HMR nếu cần và tài liệu với allocation đã xác nhận. Giữ container
   port app 8000; giữ credentials, volumes, DB bind mount, bind loopback.
4. Thêm route `nhatro.localhost` qua proxy hiện có sau khi được phép;
   kiểm tra CORS/cookie/origin và HTTP mong đợi qua IPv4, IPv6 và browser.
   Không mở rộng shared proxy bind. Rollback port/env/proxy bằng snapshot
   cấu hình trước migration; không rollback bằng xóa DB/volumes.

Quy trình onboarding Dev Hub yêu cầu đăng ký/migration được yêu cầu rõ trước
khi ghi registry/project configuration. Phần bootstrap này để lại đề xuất,
không thay cấu hình trung tâm hay claim `.localhost` đã dùng được.

## Validation

- PHP 8.4.18 chạy được Laravel 13.24.0 từ dependencies đã có.
- `php artisan test` bằng PHP 8.4: 91 tests, 88 đạt, 3 lỗi ở
  `DemoPropertySeederTest` kỳ vọng 4 tầng nhưng seeder hiện tạo 5 tầng.
  Test, seeder không nằm trong diff; README cũng mô tả 5 tầng/14 phòng.
- Pint `--test`: lỗi có sẵn ở `PropertyStructureController.php` và
  `UpdateRoomSettings.php`; hai file không nằm trong diff. Không sửa ngoài scope.
- Compose mặc định và compose dev/profile dev đều qua `config --quiet`;
  không in environment secrets. Chưa build/start Docker hoặc npm build.
- Skill được kiểm tra bằng validator của `skill-creator`; môi trường Python
  mặc định thiếu PyYAML, dùng venv tạm riêng thay vì cài global.
- Kiểm tra whitespace diff, đường dẫn routing, favicon và HTML docs.
  Kiểm tra HTML tĩnh đạt; chưa xem render vì browser chặn URL file://.
  HTTP runtime app/proxy vẫn chưa được kiểm chứng.

## Phần còn lại

Review/cấp block Dev Hub và migrate runtime; sửa lệch test demo seeder và
formatting PHP trong task riêng. Không có quyết định nghiệp vụ mới được thêm.
