# 04. Quyết định kỹ thuật (ADR ngắn, theo code đang chạy)

Lập ngày 27/11/2026 bằng cách đọc code; mỗi quyết định kèm "Dấu vết" (`file:dòng`) để kiểm lại. Đường dẫn tính từ `wp-content/` (theme `themes/ISHA/`, plugin `plugins/isha-core/`). Mẫu: **Bối cảnh → Quyết định → Lý do**. Phần "Lý do" ghi rõ nguồn: *(code)* là chính code/comment nói ra; *(suy ra)* là suy từ cách code được viết, chưa có ghi chép của khách hay người dựng. Tài liệu đi kèm: `01-source-structure.md`, `02-components-and-plugins.md`, `03-ui-ux-design-system.md`.

**Đối chiếu đề bài với code** (đã ghi đúng theo code ở dưới):

| Đề bài nói | Code thực tế |
|---|---|
| "Post meta thuần thay ACF" | Đúng với Sự kiện, Tin tức, Lời kể, Không gian, trang Giới thiệu/trang chủ. **Dịch vụ và Chính sách vẫn dùng ACF** (ADR-02) |
| "Gỡ class intro sau `animationend`" | Chỉ có ở một chỗ: chùm thẻ chính sách `[isha_lp_card_circle]` của plugin (ADR-07) |
| "Sticky Bar" | Là **dock liên hệ** (`aside.isha-dock`), dùng lại cho Tin tức/Sự kiện; ẩn hẳn trên desktop có chuột thật (ADR-06) |
| "Mobile-first `<768px`, `<1024px`" | Chuẩn theme là 900/901 và 560/561, gần như toàn `max-width` ghi đè; xem `03` mục 3 (ADR-06) |
| "`h2` trong `the_content()` bằng JS" | Có hai chỗ: mục lục Tin tức (quét h2/h3) và chương liệu trình (tách theo h2) (ADR-03) |

## ADR-01. PHP, CSS và JS thuần, không bước build

**Bối cảnh.** Site WordPress chạy trên XAMPP, một nhóm nhỏ, nhiều trang có hiệu ứng nặng (WebGL, vòng thẻ 3D). Cần sửa, lưu, tải lại là thấy.

**Quyết định.** Không có `package.json`, `composer.json` hay bundler ở gốc repo, theme hay plugin. CSS viết tay, mỗi trang/section một file (23 file ở `assets/css/`). JS front-end là IIFE ES5 không thư viện (không jQuery, GSAP, Lenis, Swiper). Phần WebGL dùng ES module `three` 0.187.0, `lil-gui`, `detect-gpu` tự host ở `assets/vendor/` (không minify), đăng ký bằng WordPress Script Modules với `'import' => 'dynamic'` (importmap có sẵn, tải khi cần). Phiên bản tệp là `filemtime()`, riêng module con không có `?ver` nên `assets/js/.htaccess` đặt `Cache-Control: no-cache`. Thư mục `tools/` (`bake-*.mjs`, `deadcode.mjs`, `shot.mjs`, `lookdev*`) và `tests/*.test.mjs` là công cụ ngoại tuyến chạy bằng Node, **không** nằm trong đường dẫn phục vụ trang.

**Lý do.** Mã nguồn đang chạy là mã nguồn đã viết, không có bản biên dịch lệch với bản gốc *(suy ra)*. `filemtime()` thay cho bước đóng gói để phá cache *(code: `enqueue.php`, `home/assets.php`)*. ES5 + IIFE không cần biên dịch lại *(suy ra)*; code còn chừa nhánh dự phòng cho Safari dưới 16 (`@supports not (overflow: clip)`, `home.css:70`) *(code)*. Ghi chú theo dõi: không minify nghĩa là `three.core.js` 1,5 MB và `home.css` 202 KB tải nguyên; chấp nhận được vì chỉ trang cần mới nạp (`01` mục 3).

**Dấu vết.** `themes/ISHA/inc/enqueue.php`, `inc/script-modules.php:26-76`, `inc/home/assets.php:23-77`, `assets/js/.htaccess`.

## ADR-02. Meta box native lưu post meta; ACF chỉ còn ở hai CPT cũ

**Bối cảnh.** Mỗi loại nội dung cần trường riêng (ngày giờ sự kiện, ảnh banner, ID ảnh, mảng gallery...). Một số cần kiểm tra chặt (ngày kết thúc phải sau ngày bắt đầu).

**Quyết định.** Plugin `isha-core` đăng ký **meta box native** cho Sự kiện, Tin tức, Lời kể, Không gian, Lịch hẹn/Liên hệ, term dịch vụ, và trang Giới thiệu/trang chủ/Hero. Khoá bắt đầu bằng `_` (không hiện ở Custom Fields), mỗi hộp có nonce và kiểm `current_user_can`, bỏ qua autosave/revision, ghi bằng `update_post_meta( …, wp_slash() )`, rỗng thì xoá. Giá trị được làm sạch **hai lần**: khi ghi và khi đọc qua hàm `ISHA_Core_*::get()`. Theme chỉ đọc bằng `get_post_meta`, không gọi `get_field()` ở đâu. **Ngoại lệ:** `isha_service` (4 trường) và `isha_policy` (2 ảnh + màu) dùng ACF local field group; Dịch vụ có hộp chọn nhiều ảnh dự phòng khi không có ACF PRO, cùng khoá meta nên nâng cấp không phải chuyển dữ liệu; thiếu ACF thì hiện thông báo ở admin.

**Lý do.** Dữ liệu và giao diện nhập nằm trong plugin của dự án nên đổi theme không mất, và nội dung mới không buộc phải cài ACF *(suy ra)*. Cần kiểm tra riêng không làm được bằng ACF: `parse_local()` (ngày thật, năm 2000-2100, kết thúc sau bắt đầu) kèm khoá timestamp `_isha_event_start_ts/_end_ts` để sắp xếp và so sánh *(code: `class-event-fields.php:1-12`)*; URL bản đồ chỉ nhận http(s). Hai CPT còn lại giữ ACF vì trường chỉ là ảnh, màu, chữ thuần có sẵn kiểu field *(suy ra)*; code không có đường chuyển sang native.

**Dấu vết.** `isha-core/includes/class-event-fields.php`, `class-article-fields.php`, `class-service-fields.php:60-70,128-140`, `class-policy-fields.php:99-106`.

## ADR-03. Xử lý `h2` trong nội dung bài bằng JS, đọc DOM đã dịch

**Bối cảnh.** Nội dung bài (`the_content()`) do biên tập viên soạn bằng trình soạn thảo mặc định **hoặc Elementor**, và site hiển thị VI/EN qua TranslatePress (dịch ở đầu ra HTML). Hai nhu cầu: mục lục cho bài Tin tức; chia liệu trình thành chương.

**Quyết định.** Không dùng filter `the_content` ở PHP để chèn id hay dựng mục lục. JS đọc vùng đã render:
- **Mục lục Tin tức** (`post-article.js:123-190`): quét `h2, h3` thấy được trong `[data-isha-article-body]`, bỏ tiêu đề nằm trong `blockquote`, `details`, tab/accordion/toggle Elementor, `[data-isha-no-toc]`; cần ≥ 2 tiêu đề, tối đa 30; tiêu đề chưa có `id` thì tạo slug từ **chữ đang hiển thị** (bỏ dấu, `đ`→`d`, hậu tố `-2`, `-3`). Desktop: cột phải dính; điện thoại: `<details>` ở đầu bài. Không chạy trong khung soạn Elementor.
- **Chương liệu trình** (`service-single.js:140-245`): tách theo **thẻ** `H2` (không đọc chữ), phần trước h2 đầu là `.service-lead`.

**Lý do.** Vì TranslatePress dịch sau khi PHP đã chạy, id/nhãn mục lục dựng ở PHP sẽ là tiếng Việt kể cả khi trang đang hiển thị tiếng Anh; JS đọc DOM cuối cùng nên nhãn luôn đúng ngôn ngữ đang xem *(đề bài; code: comment `service-single.js:140` "không phụ thuộc ngôn ngữ hay câu chữ của biên tập viên")*. Cùng một đoạn JS chạy được cho cả hai nhánh nội dung (mặc định và `--builder`) *(code)*. Không có JS thì bài vẫn đọc trọn vẹn (mục lục giữ `hidden`, chương không được tạo) *(code)*.

**Đánh đổi.** `id` neo sinh theo chữ hiển thị nên `#slug` của bản Việt không trỏ tới đúng mục ở bản Anh; mục lục chỉ có sau khi JS chạy, không nằm trong HTML gốc *(suy ra)*.

**Dấu vết.** `assets/js/post-article.js:123-190`, `assets/js/service-single.js:140-245`, `template-parts/post-article.php:113-127,190`.

## ADR-04. Sự kiện: chỉ in địa điểm/bản đồ khi có dữ liệu

**Bối cảnh.** Sự kiện rất khác nhau: workshop tại phòng khám, ưu đãi hay ra mắt dịch vụ có thể không địa điểm; sự kiện trực tuyến không có bản đồ; trang chỉ để xem thông tin (không đăng ký).

**Quyết định.** Không in ô rỗng. `isha_post_single_event_map( $location, $map_url )` trả `''` (không có nút "Chỉ đường") nếu: không có địa điểm; hoặc địa điểm là trực tuyến (regex `trực tuyến|online|zoom|google meet|teams|livestream|webinar|fanpage|facebook|youtube|tiktok`, đổi được bằng filter `isha/event/online_pattern`) mà biên tập viên chưa nhập link. Có link biên tập viên nhập (`_isha_event_map_url`, chỉ http/https) thì dùng link đó; còn lại dựng link tìm Google Maps từ đúng chuỗi địa điểm. Mẫu `post-event.php` chia nhánh theo `$isha_has_place` (ô địa điểm, dòng trong thẻ) và `'' !== map_url` (nút). Cùng nguyên tắc cho thời gian (`state` = `upcoming|ongoing|past|''`; không có ngày thì không in); không có giờ kết thúc thì **hết ngày bắt đầu** (23:59:59) mới tính là "đã kết thúc".

**Lý do.** Code nêu rõ sự kiện ưu đãi, ra mắt dịch vụ có thể không cần bản đồ (`post-single.php:164-166`); một thẻ thông tin trống hay nút "Chỉ đường" dẫn tới "Zoom" làm khách hiểu sai *(suy ra)*. Có ô link bản đồ riêng cho biên tập viên vì tìm theo chữ địa điểm có thể trúng sai chỗ *(suy ra; code chỉ nêu thứ tự ưu tiên ở `post-single.php:162-170`)*. Trang không có nút đăng ký: `_isha_event_register_url` còn ở admin nhưng chỉ banner trang chủ đọc, trang chi tiết không dùng *(code: `class-event-fields.php:10`)*.

**Dấu vết.** `inc/post-single.php:141,157,171` (`isha_post_single_day_end/_is_online/_event_map`), `template-parts/post-event.php:59-62,123-126,207-213`, `isha-core/includes/class-event-fields.php`.

## ADR-05. Tìm kiếm nhanh: AJAX chỉ lọc theo `post_title`

**Bối cảnh.** Popup tìm kiếm (Ctrl/Cmd+K) cần kết quả tức thì trên ba nhóm: Dịch vụ, Tin tức, Sự kiện, ở trang có thể được phục vụ từ cache.

**Quyết định.** Không dùng tham số `s` của WordPress (quét cả nội dung, trích dẫn). `isha_live_search_query()` gắn **tạm** một filter `posts_clauses` (gỡ trong `finally`) thêm `AND post_title LIKE %s` (chuỗi đã `esc_like` + `prepare`) và sắp xếp: tiêu đề **bắt đầu** bằng từ khoá lên trước, rồi theo tiêu đề. Chỉ bài `publish`, không mật khẩu; từ khoá ≤ 80 ký tự; tối đa 6 bài mỗi nhóm (filter `isha/live_search/per_group`, kẹp 1-12), `no_found_rows`. So khớp không phân biệt hoa thường và dấu nhờ collation `utf8mb4_unicode_520_ci` ("mun" ra "mụn"). Client: debounce 250ms, `AbortController`; không có `<dialog>` thì rơi về liên kết `/?s=`. Nonce lấy mới qua `isha_live_search_nonce` khi gặp 403.

**Lý do.** Khớp tiêu đề cho kết quả dự đoán được với từ khoá ngắn, rẻ (không quét nội dung dài của bài) và tránh kết quả "trúng chữ trong thân bài" *(suy ra từ code)*. Bộ lọc tạm đảm bảo không ảnh hưởng truy vấn khác *(code: `live-search.php:204`)*.

**Giới hạn đã biết.** (1) Nhánh lọc ngôn ngữ chỉ có Polylang/WPML, **TranslatePress chưa được lọc** (`live-search.php:142-155`): tìm trên bản Anh vẫn so khớp tiêu đề gốc tiếng Việt. (2) Nonce cấp cho khách là công khai, không có rate-limit hay honeypot như form (`02` mục 6). (3) Không tìm theo nội dung, tóm tắt hay thẻ.

**Dấu vết.** `inc/live-search.php:130-160,204-250,263-320`, `assets/js/live-search.js`.

## ADR-06. Mobile-first theo nghĩa "tĩnh trước": scroll-snap, accordion thích ứng, dock

**Bối cảnh.** Phần lớn khách vào bằng điện thoại; thiết bị có GPU yếu; không phải màn nào cũng có chuột. Một DOM duy nhất phải dùng được ở mọi chế độ.

**Quyết định.** Một điều kiện "chế độ gọn" dùng chung cho CSS và JS: `(max-width: 900px), (max-height: 560px)` (`03` mục 3). Trạng thái mặc định là tĩnh, đọc được, không cần JS hay hover; chuyển động và tương tác thêm sau (`html.isha-motion-on`). Ba mẫu giao diện:
- **Hàng vuốt có điểm dừng.** `scroll-snap-type: x mandatory` + `scroll-snap-align: start`, thanh cuộn ẩn: hàng `.isha-row` trang chủ, thẻ bài liên quan của Tin tức (`post-article.css:1307`), dải liệu trình nhóm dịch vụ, hàng dự phòng của Card Ring (`card-ring.css:607`). Không JS vẫn vuốt được (`tabindex="0"`); JS chỉ thêm nút Trước/Sau và chấm. Tắt `scroll-snap-type` khi hàng trở thành bố cục khác (`home.css:2589,3719,4167`).
- **Accordion thích ứng.** Cùng một nội dung, hai hình thức theo chế độ: chương liệu trình là accordion ở chế độ gọn (nút trong `h2`, mở sẵn chương đầu) và mở hết, `h2` chữ thường ở desktop; `service-single.js` lắng nghe `matchMedia` `change` để đổi trực tiếp khi xoay màn hình. Mục lục Tin tức: `<details>` ở di động, cột dính ở desktop; menu con trong drawer cũng là accordion.
- **Thanh dính = dock liên hệ.** `aside.isha-dock` (Đặt lịch tư vấn / Nhắn Zalo / Gọi hotline). Chế độ gọn: thanh đáy chia đều, cao 48px, chừa `safe-area`. JS bật `.is-visible` sau 80% hero, ẩn khi `#cta` hiện ≥ 40%. **Desktop có chuột thật: ẩn hẳn** (header đã có nút Đặt lịch); luật nằm một khối, bỏ khối đó là bật lại (`home.css:862-866`). Tin tức, Sự kiện, liệu trình dùng lại dock này thay vì thanh dính mới; nút Lên đầu trang tự né dock (`MutationObserver`).

**Lý do.** Một DOM, hai cách trình bày giúp không nhân đôi nội dung và không phải tải thư viện carousel/accordion *(code)*. Hover chỉ nằm trong `(hover: hover) and (pointer: fine)` để cảm ứng không bị kẹt trạng thái hover *(code)*. Mốc 900/560 theo quy tắc UI/UX chung của dự án (`CLAUDE.md`, chiến lược responsive dựa trên GPU yếu, ưu tiên giữ độ nét); màn thấp (≤ 560px cao) cũng vào chế độ gọn *(code)*.

**Lưu ý lệch.** CSS gần như toàn `max-width` ghi đè, không viết từ nhỏ lên bằng `min-width`; "mobile-first" ở đây là về *trạng thái mặc định*, không về cách viết media query. Chỉ `editorial-list.css` (640/641/1100) là mobile-first theo nghĩa đen. Các mốc 767/768 và 1024/1025 còn sót ở vài file (`03` mục 3).

**Dấu vết.** `home.css:416,2306,3891,4006`, `post-article.css:1307`, `service-single.js:140-245`, `home.js:857-904`, `home.css:753-915`, `isha-motion.js:30`.

## ADR-07. Gỡ class intro sau `animationend` để hover hoạt động

**Bối cảnh.** `[isha_lp_card_circle]` (chùm thẻ chính sách) có hai chuyển động trên cùng thuộc tính `rotate` của mỗi thẻ: màn **vào trang** (mỗi thẻ quay 360° rồi hạ về góc nghỉ, chạy một lần) và **hover** (con trỏ lại gần thì quạt xòe rộng, đổi `--spread` qua `.is-active`; cả chùm nghiêng theo chuột).

**Quyết định.** Màn vào trang là class tạm: JS thêm `.is-pre` (giấu chùm), khi chùm vào khung ≥ 25% (`IntersectionObserver`) đổi sang `.is-spinning` (CSS gắn `animation: isha-lp-spin 1.8s … backwards`, trễ `i × 75ms`). Mỗi lần `animationend` của `isha-lp-spin` đếm một thẻ; đủ thẻ thì **gỡ `.is-spinning`**. Có bộ hẹn giờ dự phòng `INTRO_MS + (n−1) × 75 + 400` ms nếu `animationend` không tới (tab bị ẩn). Trong lúc `.is-pre` hoặc `.is-spinning`, hàm cập nhật hover/nghiêng bỏ qua. Màn vào trang chỉ bật khi > 767px và không giảm chuyển động (cùng điều kiện ở CSS và JS).

**Lý do.** Trong cascade, thuộc tính đang bị `@keyframes` điều khiển thắng khai báo thường và `transition`; nếu giữ class animation thì `rotate` bị khoá và hover không đổi được quạt *(quy tắc cascade của CSS)*. Code cũng ghi rõ để tilt không giành `transform` với animation (`isha-lp-stack.js:74`). Keyframe đích trùng đúng giá trị nghỉ và dùng `backwards` (không `forwards`), nên khi gỡ class thẻ không nhảy mà trả quyền cho `transition: rotate .9s` và hover *(code: comment `isha-core.css:84-86`)*.

**Mẫu liên quan, khác mục đích.** `initCoin` (`main.js:326`) chờ `animationend` của `isha-lang-settle` rồi mới chuyển trang; băng chuyền trang chủ dùng `animationend` của chấm tiến trình làm đồng hồ (`home.js:201`). Chỉ `isha-lp-stack.js` gỡ class intro. Hiệu ứng vào section `[data-reveal]` dùng `forwards` và **không** gỡ class (xem `03` mục 8 về rủi ro của nó).

**Dấu vết.** `plugins/isha-core/assets/js/isha-lp-stack.js:13-75`, `assets/css/isha-core.css:84-107`.
