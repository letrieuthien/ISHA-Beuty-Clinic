# docs/: mục lục và tra cứu nhanh

Bộ tài liệu kỹ thuật của site ISHA Beauty Clinic, lập ngày 27/11/2026 **từ code đang chạy** (không chép từ prompt cũ). Khi tài liệu và code lệch nhau thì **code đúng**. Số dòng `file:dòng` đúng tại thời điểm lập, mở file rồi tìm theo tên hàm nếu dòng đã trôi. Đường dẫn trong tài liệu tính từ `wp-content/` hoặc `wp-content/themes/ISHA/` (mỗi file ghi rõ ở đầu).

## 1. Năm file chính

| File | Nội dung | Mở khi |
|---|---|---|
| [01-source-structure.md](01-source-structure.md) | Cây thư mục theme `ISHA` + plugin `isha-core`; luồng nạp CSS/JS theo từng loại trang; template hierarchy (URL nào dùng file nào); plugin bên thứ ba; các điểm code tự mâu thuẫn | Không biết file nào lo trang nào; thêm/sửa việc nạp CSS/JS; trang không hiện đúng mẫu |
| [02-components-and-plugins.md](02-components-and-plugins.md) | 8 CPT, 3 taxonomy, toàn bộ post/term meta, option/transient, 23 shortcode, AJAX/admin-post, ~40 component UI/JS (trigger, khởi tạo), token và breakpoint, danh sách chủ đề còn sót | Cần biết dữ liệu nằm ở đâu và ai đọc/ghi; thêm trường mới; sửa form/AJAX; tìm component dùng lại |
| [03-ui-ux-design-system.md](03-ui-ux-design-system.md) | Màu (token, tông section, chữ vàng), typography, breakpoint chuẩn 900/901 và 560/561, quy chuẩn kính (Floating Glass), easing/thời lượng, `prefers-reduced-motion`, class dùng lại | Trước **mọi** việc UI/UX: chọn màu, cỡ chữ, mốc responsive, hiệu ứng, kính |
| [04-technical-decisions.md](04-technical-decisions.md) | 7 ADR: không build; meta native thay ACF (trừ Dịch vụ/Chính sách); xử lý `h2` bằng JS; sự kiện chỉ in địa điểm khi có dữ liệu; tìm kiếm chỉ theo tiêu đề; "mobile-first" kiểu tĩnh-trước; gỡ class intro sau `animationend` | Muốn đổi cách làm cũ: đọc lý do trước để không phá chủ ý |
| [05-pages-status-audit.md](05-pages-status-audit.md) | Bảng audit 12 trang/thành phần: file PHP/CSS/JS, trạng thái, nợ kỹ thuật; chuỗi tiếng Việt chưa qua hàm dịch; nợ dùng chung; việc đang chờ; phụ lục vận hành trang chủ, quy cách media, danh sách Bento, giọng văn | Nhận việc bảo trì một trang; lập kế hoạch dọn nợ; cần biết cái gì đang chờ khách |

Onboarding ngắn cho phiên mới nằm ở [CLAUDE.md](../CLAUDE.md) (thư mục gốc dự án).

## 2. Tra cứu nhanh

| Câu hỏi | Mở |
|---|---|
| Trang X dùng template, CSS, JS nào? | 05 mục 1 (bảng file); sâu hơn: 01 mục 3.3 và 4 |
| URL này ra file nào? | 01 mục 4 |
| Trang này nạp những CSS/JS nào? | 01 mục 3 |
| Meta key / option / transient tên gì, ai ghi, ai đọc? | 02 mục 3 và 4 |
| Shortcode / AJAX action nào, tham số gì? | 02 mục 5 và 6 |
| Component (lightbox, dock, tab, accordion, Card Ring...) khởi tạo ở đâu? | 02 mục 7 |
| Màu nào dùng cho việc gì, chữ vàng nhỏ dùng mã nào? | 03 mục 1 |
| Mốc responsive là gì, vì sao không phải 768/1024? | 03 mục 3; 04 ADR-06 |
| Công thức kính, easing, thời lượng? | 03 mục 4 và 5 |
| Phải làm gì khi giảm chuyển động / tắt JS? | 03 mục 6 |
| Vì sao ACF vẫn còn? Vì sao mục lục Tin tức dựng bằng JS? | 04 ADR-02, ADR-03 |
| Trang này đang ổn hay còn nợ? | 05 mục 2 |
| Chuỗi tiếng Việt nào chưa qua `__()`? | 05 mục 3 |
| Việc nào đang chờ khách / chưa commit? | 05 mục 5 |
| Bật/tắt landing, xem trước trang chủ, xử lý section biến mất? | 05 phụ lục A |
| Quy cách ảnh/video, file `isha-wordmark.svg`? | 05 phụ lục B |
| Thuật toán danh sách Tin tức/Sự kiện, thêm danh sách thứ ba? | 05 phụ lục C |

## 3. File khác còn trong `docs/`

| File | Tình trạng |
|---|---|
| `home-architecture.md` (~288 KB) | **Chưa rà, không tin.** Hợp đồng kỹ thuật cũ của trang chủ, được ~49 comment trong code trỏ tới theo mục; chắc chắn có phần cũ (còn mô tả header cũ, 13 section). Chờ phiên rà riêng, phiên này không đụng vào. Dùng `01`-`05` thay thế khi có thể |
| `isha-lookdev-prompt-v3.md` | **Giữ.** 16 file trong `assets/js/services-cinematic/` trích dẫn theo mục |
| `Visual Element preview.pdf` | **Giữ.** Họa tiết thương hiệu (mặt trời, núi, đường kẻ) dùng cho màn chờ và cảnh Bình minh |
| `.htaccess` | Cấu hình truy cập thư mục |

## 4. Tài liệu đã xóa (27/11/2026)

Các prompt dựng UI cũ đã xóa ở phiên 1. Phiên 3 xóa tiếp năm file dưới đây sau khi rút phần còn dùng được:

| Đã xóa | Phần còn dùng được chuyển tới |
|---|---|
| home-admin (hướng dẫn nhập nội dung trang chủ) | 05 phụ lục A (landing, xem trước, xử lý nhanh); bản đồ "thêm section" ở `CLAUDE.md`; cấu trúc trường: 02 mục 3.2 |
| home-assets (danh sách media cần cung cấp) | 05 phụ lục B (quy cách media, spec `isha-wordmark.svg`) |
| home-brand-script (ý tưởng từ ISHA Brand Story) | 05 phụ lục D |
| editorial-list (danh sách Bento) | 05 phụ lục C (đã đối chiếu code; bỏ các mục lỗi thời: đường dẫn `/bai-viet/`, "sắp theo ngày đăng" của Sự kiện) |
| bang-ma-mau (bảng mã màu) | `03` mục 1 (màu thật, Shadow là `#141414`); cách đổi màu: `CLAUDE.md` |

Comment trong CSS/JS/PHP vẫn có thể trỏ tới các file đã xóa (ví dụ `main.css:31` → bang-ma-mau); đó là lịch sử, để nguyên theo quyết định 27/11, đừng tin nội dung.

## 5. Lưu ý

- **`docs/` nằm trong `.gitignore`**: các file ở đây không có trong `git status`, không có lịch sử và không có trên bản clone mới. Sao lưu riêng nếu cần giữ.
- `CLAUDE.md` ở gốc dự án **không** bị `.gitignore` chặn (sẽ vào git khi commit) và trỏ link vào `docs/`; trên bản clone mới các link đó sẽ hỏng. Nếu muốn `docs/` đi cùng repo, bỏ dòng `docs/` khỏi `.gitignore` (việc này chưa làm, cần người dùng quyết định).
- Khi thêm trang, CPT, meta, component hoặc đổi quy ước: cập nhật đúng file trong bảng mục 1 (và dòng tương ứng ở `CLAUDE.md` nếu đổi bản đồ "sửa X → mở Y").
- Bộ QA hồi quy ở ngoài repo: `C:\xampp\isha-backups\2026-11-18_after-phase3-restructure\qa-regression\` (`node run.mjs`, chỉ đọc DB).
