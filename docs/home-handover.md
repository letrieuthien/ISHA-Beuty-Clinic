# Bàn giao: trang chủ điện ảnh ISHA

Ngày: 2026-11-10 (Giai đoạn 7). Đọc cùng: `home-report-phase7.md` (kết quả kiểm thử), `home-admin.md` (hướng dẫn nhập nội dung), `home-assets.md` (danh sách media và hiện trạng từng mục), `home-architecture.md` (hợp đồng kỹ thuật, mục 14 là phần Giai đoạn 7).

## 1. Bàn giao gồm gì

- **Trang chủ điện ảnh 13 section** (Hero, Câu chuyện, Triết lý, Dịch vụ, Giá trị, Quy trình, Không gian, Sự kiện, Bài viết, Lời kể, Tầm nhìn, Cảnh kết, Đối tác) cùng thanh liên hệ nhanh (dock), mỗi section là một shortcode chạy độc lập `[isha_home_<mã>]`.
- **Trang chi tiết liệu trình** dựng lại theo cùng hệ thiết kế (`single-isha_service.php`), nút **Lên đầu trang** riêng của theme (thay plugin WPFront đã gỡ).
- **Phía quản trị (plugin ISHA Core):** khung "Nội dung trang chủ" 13 thẻ, bốn kiểu bài mới (Sự kiện, Lời kể khách hàng, Kiến thức làn da, cùng các taxonomy), ô ảnh bìa/ảnh trước-sau/thứ tự của nhóm dịch vụ, bộ seed 6 nhóm và 17 liệu trình.
- **Không thêm thư viện nào, không có bước build.** Chỉ PHP, CSS, JS thuần (ES5) của theme và plugin. JS của trang chủ (`isha-motion.js` + `home.js`) tổng 95.522 byte chưa nén (ngân sách 110.000).
- **Giữ nguyên:** trang Chính sách (`policy-single.*`, `single-isha_policy.php`), chế độ landing, trang đặt lịch, `header.php`, `footer.php` và mọi trang khác (xem mục 2.4).

## 2. Tệp đã thêm và sửa

### 2.1 Theme `wp-content/themes/ISHA/` (thêm 32 tệp)

| Tệp | Lý do |
|---|---|
| `assets/css/home.css` | Toàn bộ kiểu của 13 section, header và dock của trang chủ (18 khối có dấu `/* >>> … */`); mọi selector nằm dưới `.isha-home` |
| `assets/js/isha-motion.js` | Lõi chuyển động dùng chung (`window.ISHA.motion`): cảnh ghim, tween, `inView`, `scrollTo`, chế độ gọn/giảm chuyển động |
| `assets/js/home.js` | Các cảnh của 13 section, hàng vuốt, băng chuyền, video nền, header, dock, bầu trời đổi màu theo section |
| `header-home.php` | Header của trang chủ điện ảnh (cùng cấu trúc `header.php`, thêm `theme-color`) |
| `inc/home/registry.php` | Danh sách 13 section theo thứ tự (shortcode, template, tông nền, class, cảnh) |
| `inc/home/data.php` | Lớp dữ liệu: nội dung mặc định Phụ lục A trộn với nội dung CMS, dựng sẵn từng section |
| `inc/home/helpers.php` | Hàm in markup dùng chung (ảnh, nút, placeholder, logo chữ, `isha_home_render()`) |
| `inc/home/shortcodes.php` | 13 shortcode `[isha_home_<mã>]` |
| `inc/home/assets.php` | Nạp CSS/JS chỉ ở nơi cần, cổng `isha-motion-on/fail` trong `<head>`, preload poster, `noindex` khi xem trước, gỡ CSS block editor |
| `template-parts/home/*.php` (15 tệp) | 13 section, `intro.php` (lớp phủ mở đầu), `dock.php` |
| `inc/service-single.php`, `template-parts/service-single.php`, `assets/css/service-single.css`, `assets/js/service-single.js` | Trang chi tiết liệu trình: dữ liệu dựng sẵn, markup, kiểu, hộp xem ảnh lớn |
| `inc/scroll-top.php`, `assets/css/scroll-top.css`, `assets/js/scroll-top.js` | Nút "Lên đầu trang" |
| `inc/admin.php` | (sau báo cáo, theo yêu cầu của bạn) Ẩn mục **Bình luận** khỏi menu wp-admin và biểu tượng bình luận trên thanh admin khi theme ISHA bật; bộ lọc `isha/admin/hide_comments_menu` trả `false` thì hiện lại |

### 2.2 Theme, tệp có sẵn đã sửa (7 tệp)

| Tệp | Sửa gì và vì sao |
|---|---|
| `assets/css/main.css` | Chỉ thêm khối token (màu, cỡ chữ, nhịp chuyển động, `--isha-vh`); phần còn lại giữ nguyên từng byte |
| `front-page.php` | Định tuyến: chế độ landing bật thì in landing như cũ; tắt (hoặc xem trước) thì dựng trang chủ điện ảnh |
| `functions.php` | Thêm các dòng nạp `inc/home/*`, `inc/service-single.php`, `inc/scroll-top.php`, `inc/admin.php` |
| `inc/helpers.php` | `isha_landing_only()` và `isha_is_inner_page()` tính thêm trạng thái xem trước; thêm `isha_home_is_preview()`, `isha_home_is_active()` |
| `inc/setup.php` | Ba cỡ ảnh mới (`isha-frame` 1200×1500, `isha-cinema` 2400×1004, `isha-strip` 1600×1100) và class `isha-home` trên `<body>` |
| `single-isha_service.php` | Viết lại thành lớp mỏng gọi template mới (dữ liệu và luồng đặt lịch giữ nguyên) |
| `inc/customizer.php` | (Giai đoạn 7) chỉ thêm dòng mô tả "Không còn tác dụng" cho 4 điều khiển của trang chủ cũ không còn nơi nào đọc |

### 2.3 Plugin `wp-content/plugins/isha-core/`

Thêm 12 tệp: `includes/home-schema.php` (lược đồ khung "Nội dung trang chủ", nguồn duy nhất cho admin và kiểm tra), `class-home-fields.php` (khung 13 thẻ), `class-event-fields.php`, `class-testimonial-fields.php`, `class-article-fields.php` (trường của ba kiểu bài mới), `class-term-fields.php` (ảnh bìa, video bìa, ảnh trước/sau, thứ tự của nhóm dịch vụ), `class-admin-media.php` (ô chọn ảnh/video/gallery dùng chung), `class-seed.php` (6 nhóm, 17 liệu trình, 3 loại sự kiện), cùng `assets/css/admin-home.css`, `admin-media.css`, `assets/js/admin-home.js`, `admin-media.js`.

Sửa 3 tệp: `includes/class-post-types.php` (thêm Sự kiện, Lời kể khách hàng, Kiến thức làn da và các taxonomy; kiểu `isha_service` thêm `editor` và `thumbnail`), `isha-core.php` (nạp các class mới, phiên bản 1.1.0), `uninstall.php` (dọn các tuỳ chọn mới).

### 2.4 Không đụng tới (đối chiếu từng byte với bản trước dự án)

`header.php`, `footer.php`, `single-isha_policy.php`, `assets/css/policy-single.css`, `assets/js/policy-single.js`, `inc/policy-single.php`, `template-parts/policy-screens.php`, `template-parts/landing-policy.php`; plugin: `class-service-fields.php`, `class-policy-fields.php`, `class-shortcodes.php`, `class-booking.php`, `class-settings.php`. Trang Chính sách vẫn dựng bằng mã riêng của nó, chưa dùng lõi chuyển động chung (xem mục 6, đề xuất 6).

## 3. Trước khi ra mắt (danh sách kiểm)

Hiện trạng đo trên DB thật (`isha`) ngày 10/11/2026 ghi ngay trong từng mục.

1. **Chế độ landing.** Đang **TẮT**: trang chủ mới đã hiển thị cho mọi người. Nếu đưa DB này lên host mà chưa muốn mở, tick lại Giao diện › Tùy biến › ISHA – Giao diện › Chế độ landing **và** đặt Cài đặt › Đọc › Trang chủ về trang "Chính sách khách hàng thân thiết ISHA" (ID 7), vì landing in nội dung của trang chủ tĩnh mà trang "Trang chủ" (ID 10) rỗng.
2. **17 liệu trình: ĐÃ XUẤT BẢN và đã có NỘI DUNG MẪU** (10/11/2026, theo yêu cầu của bạn). Bạn tự xuất bản 11 bài trong wp-admin; tôi xuất bản nốt 6 bài còn Nháp (ID 148 đến 153) và điền, chỉ vào ô còn trống, bốn mục cho cả 17 bài: Thời gian liệu trình (bài 147 giữ "6 buổi / 3 tháng" bạn đã nhập), Mô tả ngắn, Các bước liệu trình (6 bước) và bài mô tả trong trình soạn thảo (đoạn giới thiệu, "Phù hợp với", "Lưu ý"). **Đây là nội dung mẫu do trợ lý soạn, cần bác sĩ duyệt trước khi coi là chính thức**: không có giá, liều lượng hay cam kết kết quả; riêng **BAP** (chưa rõ kỹ thuật cụ thể), **ISHA Signature Harmony**, **Nâng cơ chuyên sâu** và **Mesopeel** được viết khá chung, cần bác sĩ bổ sung. **Chưa có ảnh đại diện và ảnh liệu trình** (trang dùng ảnh bìa nhóm hoặc khối giữ chỗ). Công cụ và nội dung nằm ở `C:\xampp\isha-backups\2026-11-10_before-service-content\`; khôi phục (gỡ nội dung mẫu, trả 6 bài về Nháp) bằng `rollback-service-content.sql` cùng thư mục (mục 9). Thùng rác còn 8 bài `isha_service` cũ.
3. **Thứ tự liệu trình: ĐÃ CẬP NHẬT** (10/11/2026, theo cho phép của bạn). 17 bài seed (ID 147 đến 163) trước đó có `menu_order` 10, 20, 30 lặp theo từng nhóm nên khi xuất bản, trang Dịch vụ cũ và ô chọn dịch vụ ở form đặt lịch sẽ trộn xen kẽ các nhóm. Nay là số toàn cục theo nhóm: 110, 120, 130 | 210, 220 | 310, 320, 330 | 410, 420, 430 | 510, 520, 530, 540 | 610, 620 (cũng là số seed dùng cho lần seed sau). Chỉ đổi cột `menu_order`; trạng thái vẫn **Nháp**, ngày sửa không đổi. Thứ tự trong từng nhóm giữ nguyên nên mục Liệu trình khác và section Dịch vụ không đổi. Khôi phục nếu cần: mục 4.1.1 và mục 9.
4. **Media và nội dung thật.** Thư viện hiện chỉ có logo và ảnh thẻ chính sách; trang "Trang chủ" chưa lưu nội dung nào (mọi chữ là mặc định của Phụ lục A); 17 liệu trình, 5 Lời kể, **5 Sự kiện** (ID 194 đến 198: 4 sắp tới từ 22/11/2026 đến 10/01/2027 và 1 đã diễn ra 18/10/2026; đủ ba loại Workshop, Design Talk, Charity; địa điểm lấy theo địa chỉ phòng khám; chưa có ảnh) và **5 Bài viết kiến thức** (ID 199 đến 203, 1 bài nổi bật "Nền da khỏe bắt đầu từ đâu?"; ba chủ đề mới "Chăm sóc da hằng ngày", "Điều trị và công nghệ", "Chuẩn bị trước thẩm mỹ"; chưa có ảnh) đều là nội dung mẫu, thêm ngày 10/11/2026 theo yêu cầu của bạn (khôi phục: `rollback-events-articles.sql` trong `C:\xampp\isha-backups\2026-11-10_before-events-articles\`, mục 9); có 5 Lời kể khách hàng mẫu (ID 183 đến 187: Chị Minh Anh, Chị Thu Hà, Anh Quốc Bảo, Chị Ngọc Lan, Chị Phương Thảo, gắn với 5 liệu trình, đã xuất bản, thêm ngày 10/11/2026 theo yêu cầu của bạn; gỡ bằng `rollback-testimonials.sql` trong `C:\xampp\isha-backups\2026-11-10_before-testimonials\`, hoặc xoá từng bài trong wp-admin). Danh sách từng mục và placeholder tương ứng ở `home-assets.md`.
5. **Menu chính.** Vị trí Primary đang gán cho menu rỗng ("Chính sách khách hàng thân thiết", 0 mục), nên header ở mọi trang chỉ có logo và nút Đặt lịch. Menu đầy đủ "ISHA – Menu chính" (6 mục, trong đó một mục trỏ trang "Đội ngũ bác sĩ" đang ở thùng rác) có sẵn: gán tại Giao diện › Trình đơn khi sẵn sàng.
6. **Liên hệ.** ISHA Clinic (menu bên trái): hotline, email, địa chỉ, giờ làm việc, bản đồ, Facebook, Instagram đã có; **Zalo URL, TikTok, YouTube đang trống**. Thanh liên hệ nhanh lấy hotline và Zalo từ đây: chưa có Zalo URL thì chỉ hiện nút Đặt lịch và Gọi hotline (nút Nhắn Zalo hiện khi nhập địa chỉ dạng `https://zalo.me/…`).
7. **Múi giờ.** Cài đặt › Chung hiện là UTC+7 dạng độ lệch (`gmt_offset` 7); nên chọn "Asia/Ho_Chi_Minh" và **đặt trước khi nhập sự kiện**: sự kiện lưu cả giờ địa phương lẫn mốc UTC, đổi múi giờ sau khi nhập sẽ làm lệch giờ sự kiện cũ.
8. **Đường dẫn tĩnh.** Sau khi đưa plugin lên host: Cài đặt › Đường dẫn tĩnh › Lưu thay đổi (một lần), để `/su-kien/`, `/bai-viet/`, `/chu-de/` hoạt động.
9. **Plugin cần bật:** ISHA Core, **Advanced Custom Fields** (các ô Thời gian, Mô tả ngắn, Các bước của liệu trình), Elementor (trang landing chính sách). Không còn plugin WPFront Scroll Top (theme có nút riêng).
10. **Máy chủ.** Bật **nén (brotli hoặc gzip) cho HTML, CSS, JS và font** và **cache dài cho tài nguyên có `?ver=`** (không nén thì trang chủ nặng gần gấp đôi, xem `home-report-phase7.md` mục 5); HTTPS; PHP 7.4 trở lên.
11. **SEO.** Trang chủ chưa có thẻ meta description (Lighthouse SEO 92/100). Dùng plugin SEO hoặc bổ sung khi có nội dung chốt. Trang xem trước (`?isha_preview=home`) tự `noindex`.
12. **Không đưa lên web root:** thư mục `docs/` và `isha-backups` (đã có `docs/.htaccess` chặn truy cập từ web, nhưng đừng triển khai các thư mục này). Thư mục đo tạm `_isha_qa` đã xoá.
13. **Đo lại và thử máy thật** khi đã có media thật: Lighthouse trên host thật (mục tiêu LCP dưới 2,5 giây trên 4G) và danh sách thiết bị thật ở mục 7.
14. **Giao diện wp-admin chưa từng được mở trong trình duyệt.** Mọi kiểm thử phía quản trị chạy bằng PHP trên DB thử (bộ công cụ trình duyệt bị cấm đăng nhập và vùng admin theo thiết kế an toàn). Cần một lần thử tay: (a) Trang › "Trang chủ" › Sửa: lần lượt 13 thẻ, nhập và lưu một ô ở mỗi thẻ; (b) nút **Chọn** ảnh và video mở Thư viện phương tiện; (c) bộ ảnh: thêm, **Trước/Sau** (đổi thứ tự), **Xoá**; (d) Dịch vụ › Nhóm dịch vụ: ảnh bìa, ảnh trước/sau (ghép cặp, cảnh báo cặp ảnh đã bị xoá khỏi thư viện), thứ tự; (e) nút tạo dữ liệu mẫu (seed) ở trang cài đặt ISHA Core nếu còn dùng; (f) các ô ACF của liệu trình; (g) xem trang chủ khi đang đăng nhập (thanh admin 32 px đã được mô phỏng, xem báo cáo mục 4).
15. **Đưa lên host (dễ sai):** (a) **không** tải `wp-config.php` và `.htaccess` của máy XAMPP (có `WP_HOME`/`WP_SITEURL` và `RewriteBase` riêng của máy thử); (b) DB chứa 169 chỗ ghi `localhost/ISHA_clinic` (có cả trong dữ liệu serialize): đổi tên miền bằng công cụ tìm-thay **hiểu dữ liệu serialize** (WP-CLI `search-replace`, Better Search Replace…), không dùng tìm-thay chữ thường; (c) sau khi lên host chạy "Regenerate Thumbnails" để ảnh đã có sinh ba cỡ mới `isha-frame`, `isha-cinema`, `isha-strip`; (d) plugin cache/tối ưu có tuỳ chọn **Delay JS**, **Remove Unused CSS**, **Combine/Minify JS** có thể phá cảnh chuyển động (script thêm các class `.is-live`, `.is-pre`, `.is-open`, `isha-motion-on` lúc chạy): hiệu ứng lỗi thì tắt các tuỳ chọn đó cho trang chủ; (e) video chỉ nên là MP4 H.264 không tiếng trong mức khuyến nghị (ngang ≤ 8 MB, dọc ≤ 3 MB); quay từ iPhone (MOV/HEVC) hay tệp lớn hơn sẽ làm trang chậm hoặc không phát ở một số trình duyệt.
16. **Logo chữ SVG khi có tệp thật:** tệp không nên có thuộc tính `fill` hay thẻ `<style>`, để màu lấy từ theme qua `currentColor` (xem `home-assets.md`).
17. **Bản quyền và pháp lý:** font Garamond Premier Pro (Adobe) cần giấy phép dùng làm web font cho tên miền thật (kiểm tra gói đang có); ảnh trước/sau (sự đồng ý bằng văn bản của khách) và nội dung y khoa cần pháp chế duyệt (quảng cáo dịch vụ y tế); các lời kể là nội dung của doanh nghiệp nên đã bỏ ô "đồng ý công khai" theo yêu cầu của bạn, pháp chế nên xem cách trình bày chúng có phù hợp quy định quảng cáo hay không.
18. **Phiên bản tối thiểu chưa chạy thật:** mã được kiểm cú pháp cho PHP 7.4 và chạy thật trên bản của XAMPP (PHP 8.5.12, WordPress 7.1.4, MariaDB 10.11). PHP 7.4 đến 8.4 (chỉ kiểm cú pháp 7.4) và WordPress 6.0 đến 6.2 chưa chạy thật. Theme nạp script bằng tham số mảng (`strategy: defer`, cần WordPress 6.3 trở lên; bản cũ hơn coi mảng là "nạp ở chân trang", trang vẫn chạy nhưng không có defer).

## 4. Quyết định còn mở và việc chờ khách hàng

### 4.1 Mới ở Giai đoạn 7 (cần bạn quyết)

1. **Cập nhật `menu_order` của 17 liệu trình trên DB thật: ĐÃ LÀM** (mục 3.3). Lần đầu lệnh ghi bị hệ thống chặn nên tôi dừng và hỏi; sau khi bạn cho phép rõ ràng, tôi đã chạy `fix-menu-order.php --apply` (sau khi sao lưu DB thật và chạy thử). Đã kiểm chứng: số bài đổi đúng 17 và chỉ đổi cột `menu_order` (so từng bảng với bản sao lưu: mọi cột khác của 17 bài, mọi bài khác và mọi bảng khác giống hệt; chỉ có thêm một dòng nhật ký `elementor_log` do chính Elementor ghi mỗi lần WordPress chạy); chạy thử lần hai báo 17 bài "đã đúng", 0 việc; các trang công khai vẫn 200, không lỗi PHP. **Khôi phục (nếu cần):** `C:\xampp\mysql\bin\mysql.exe -u root isha < C:\xampp\isha-backups\2026-11-10_before-menu-order\rollback-menu-order.sql` trả đúng 17 giá trị về 10, 20, 30, 40 (chỉ đổi dòng chưa ai sửa tay; đã thử trên bản sao DB: bảng bài viết trở lại giống hệt trước khi cập nhật); hoặc nạp `db.sql` cùng thư mục để trả cả DB về lúc trước.
2. **Video bìa nhóm dịch vụ:** ô nhập có trong admin và lưu được, nhưng trang chủ **chưa phát** video bìa (chỉ dùng ảnh bìa). Làm tiếp (in `<video>` trong khung 4:5 dùng chung hàm video nền, khoảng 1 đến 2 KB JS) hay gỡ ô khỏi admin?
3. **Lớp phủ tối của hero trên điện thoại** đã nâng từ 0,6 lên 0,74 để chữ nhỏ trên poster/video sáng vẫn đạt tương phản 4,5:1; ảnh hero hiện tối hơn bản đã duyệt. Xem lại khi có poster thật; chỉnh một dòng CSS (`.isha-hero__veil` trong khối chế độ gọn của `home.css`; dưới 0,66 thì chữ nhỏ trên ảnh trắng không còn đạt).
4. **Header và footer cũ chưa đạt vùng chạm 48px** (nút Menu 48×26 trên điện thoại, nút Đặt lịch 44px, liên kết mạng xã hội chân trang cao 19px; panel menu di động không cuộn được khi dài hơn màn hình). Trang chủ mới và trang liệu trình đã được sửa riêng; các trang cũ dùng chung `header.php`, `footer.php`, `main.css` nên không đổi ở đợt này (yêu cầu giữ nguyên). Có nâng chung toàn site không?
5. **Tốc độ tải trên điện thoại:** đo và đề xuất ở `home-report-phase7.md` mục 5. Với font OTF/TTF giữ nguyên theo chỉ dẫn và máy chủ có nén, Lighthouse (mô phỏng thận trọng) cho LCP khoảng 2,7 giây trên 4G thường (mục tiêu 2,5 giây) và khoảng 8 giây trên 4G chậm; đo thực trên đường truyền giả lập (lượt xem lại, không intro) cho 1,7 giây và 5,4 giây. Chuyển font sang WOFF2 cắt Latin + tiếng Việt giảm từ khoảng 1,2 MB (đã nén) xuống khoảng 0,42 MB.

### 4.2 Việc chờ thông tin từ khách hàng (các quyết định chuyển từ Giai đoạn 2 đến 6, theo chỉ dẫn "không quan trọng, sẽ lấy sau")

- Media thật cho mọi chỗ (`home-assets.md`), nội dung thật cho 13 thẻ, sự kiện, bài viết, lời kể (do doanh nghiệp soạn, không cần đồng ý công khai), ảnh trước/sau (có đồng ý bằng văn bản và pháp chế xác nhận).
- Logo chữ ISHA dạng vector (đang là placeholder chữ Garamond), biểu tượng Light Gloom và Liner pattern (đang tự vẽ).
- Nhịp intro và iris của hero (hiện intro khoảng 2,0 đến 2,2 giây cộng 0,6 giây mờ dần; iris 1,2 giây trên điện thoại).
- Độ đọc của kiểu chữ: cỡ chữ thân và độ rộng dòng (đoạn phụ Câu chuyện và đoạn Tầm nhìn chỉ khoảng 37 đến 44 ký tự mỗi dòng ở desktop sau khi đổi chữ thân sang Montserrat).
- Menu chính (gán menu đầy đủ), nhịp từng cảnh, Dịch vụ có "bám khung" khi dừng cuộn không, giá trị (đã đổi sang khung tối chữ sáng ngày 11/11/2026, xem `home-architecture.md` 14.6), điều khiển Sự kiện (viên nang ở góc thẻ hay hàng riêng), cách đọc chữ trợ năng tiếng Việt ("băng chuyền", "trang chiếu"…), thẻ thông tin liệu trình trên điện thoại nằm trước hay sau "Liệu trình khác".
- Có dựng lại trang nhóm dịch vụ, trang bài viết và trang sự kiện theo hệ thiết kế mới không (ngoài phạm vi ban đầu).

## 5. Giới hạn hiện biết

- **Safari/iOS thật chưa kiểm** (máy này là Windows). Đã chạy WebKit (Playwright, bản Windows) và rà tương thích tĩnh, nhưng bản WebKit Windows không phải Safari thật (thiếu thanh địa chỉ co giãn, cuộn quán tính, một số thuộc tính). Danh sách kiểm thiết bị thật ở mục 7. Không kiểm Firefox và Edge theo chỉ dẫn.
- **Chưa có media thật nên chưa đo được hiệu năng thật:** số đo dùng ảnh mẫu (poster 186 KB) và không có video.
- **Tốc độ:** 74% dung lượng tải lần đầu là 6 font OTF/TTF; trang chủ chưa có CSS tới hạn tách riêng (một `home.css` 151 KB, khoảng 33 KB khi nén brotli). Không có bước build nên chưa rút gọn (minify).
- **Sự kiện:** giờ lưu hai nơi (chuỗi địa phương và mốc UTC), xem mục 3.7. Sự kiện kéo dài nhiều ngày hiện hiển thị đủ ngày kết thúc.
- **Video bìa nhóm dịch vụ** chưa phát (mục 4.1.2).
- **Các giới hạn số lượng khai báo ở nhiều nơi:** ảnh trước/sau tối đa 6 cặp (`BA_MAX` trong plugin và số cứng trong theme), Không gian 12 ảnh, Đối tác 16 logo. Đổi một chỗ phải đổi cả theme lẫn plugin.
- **Truy vấn ảnh:** mỗi media tốn khoảng 2 truy vấn lần đầu trong request; trang chủ đầy đủ khoảng 60 tệp. Nên bật cache trang hoặc object cache ở host.
- **`will-change`** đặt cố định trên một số phần tử khi cảnh ở chế độ phim (spec đề nghị chỉ đặt trong lúc chuyển động); chỉ ảnh hưởng bộ nhớ GPU trên máy yếu.
- **Dock** nằm trong `<main>` dưới dạng `<aside>`, và nút Lên đầu trang nằm ngoài mọi landmark: công cụ kiểm trợ năng có thể gợi ý (không phải lỗi WCAG).
- **Màu viết trực tiếp** (rgba bản sao của Shadow ở khối trước/sau, vàng kim loại): đổi token Shadow thì các chỗ này không đổi theo.
- **Băng chuyền Lời kể tự chạy liên tục, không nút Tạm dừng** (theo yêu cầu của bạn, 10/11/2026; chi tiết ở `home-architecture.md` mục 14.5). Không dừng khi rê chuột hay bấm chuột; chỉ dừng khi focus bàn phím, khi ra khỏi khung nhìn, khi tab bị ẩn hoặc khi người xem bật giảm chuyển động của hệ điều hành (lúc đó không tự chạy, nút Trước/Sau vẫn dùng được). Đánh đổi: trái tinh thần WCAG 2.2.2 (nội dung tự chuyển động quá 5 giây nên có cách dừng), giống quyết định với video. Băng chuyền Sự kiện vẫn có nút Tạm dừng và dừng khi rê chuột; muốn đổi giống Lời kể thì truyền `always: true` và bỏ nút.
- **Mục Bình luận chỉ bị ẩn khỏi menu, chưa tắt tính năng:** bài viết vẫn có thể nhận bình luận và trang `wp-admin/edit-comments.php` vẫn mở được bằng đường dẫn. Muốn tắt hẳn thì đóng bình luận ở Cài đặt › Thảo luận (và bỏ tick "Cho phép bình luận" trên bài).
- **Intro làm LCP muộn hơn một chút:** phần tử lớn nhất lúc tải là câu intro (hiện sau khoảng 1,0 giây kể từ khi JS chạy); tắt intro hoặc rút nhịp thì LCP sớm hơn.
- **Hero là cảnh ghim đúng một màn hình nên độ dài chữ có giới hạn.** Đã đo ở 18 cỡ laptop (cao 561 đến 1000 px): với tiêu đề tối đa hai dòng, nút Đặt lịch luôn nằm trong khung (Giai đoạn 7 đã sửa lỗi nút bị cắt ở 1536×660 và 1536×740, nay cỡ chữ tiêu đề tự nhỏ lại khi màn hình thấp hơn khoảng 820 px). Tiêu đề từ ba dòng (hơn khoảng 56 ký tự) hoặc mô tả dài (từ ba dòng) ở màn hình thấp sẽ chồng lên dòng nhỏ hoặc đẩy nút xuống dưới mép màn hình. Ô nhập vẫn cho tới 120 và 240 ký tự; khuyến nghị 30 và 120 (`home-admin.md`). Cách làm triệt để (chưa làm): hero tự chuyển sang bố cục không ghim khi nội dung không vừa một màn hình.
- **Dịch vụ: nhóm có nhiều liệu trình** (bộ thử có một nhóm 8 liệu trình) thì ở màn hình thấp hơn khoảng 700 px cột chữ tự cuộn dọc bên trong khung (thiết kế có chủ ý, dữ liệu thật hiện 3 liệu trình mỗi nhóm không cuộn).
- **Người đã đăng nhập (có thanh admin):** thanh 32 px (46 px trên điện thoại) đẩy trang xuống nên ở màn hình rất thấp (1280×561) nút hero nằm dưới mép khung 19 px cho tới khi cuộn nhẹ; không có chữ nào bị header hoặc thanh che ở cảnh ghim (mô phỏng 8 cỡ khung, xem báo cáo mục 4).
- **Vạch cuộn của hero** (nét trang trí 1 px) chạm đáy nút Đặt lịch từ 3 đến 13 px ở màn hình cao 740 đến 1000 px (nằm sau nút, không che chữ).

## 6. Đề xuất tiếp theo (theo thứ tự nên làm)

1. Chuyển 7 font sang **WOFF2 cắt Latin + tiếng Việt** (ước lượng đo thật: 2.114 KB → 420 KB; có thể đặt preload lại hai font chính sau đó). Yêu cầu chốt "giữ OTF/TTF" nên chưa làm.
2. Dựng lại **trang nhóm dịch vụ** (`/nhom-dich-vu/…`, nơi liên kết "Xem tất cả liệu trình" dẫn tới), **trang bài viết** và **trang sự kiện** theo hệ thiết kế của trang chủ.
3. Nâng **vùng chạm 48px cho header và footer cũ** (kèm panel menu di động cuộn được) cho toàn site.
4. Quyết định về **video bìa nhóm** (làm tiếp hoặc gỡ ô).
5. Thêm **meta description và dữ liệu có cấu trúc** (plugin SEO), kiểm trang xem trước trước khi mở.
6. Chuyển **trang Chính sách** sang dùng lõi chuyển động chung (`isha-motion.js`) để một bộ mã chuyển động duy nhất.
7. Gộp các giới hạn số lượng về **một nguồn** (theme đọc từ lược đồ plugin) và nạp trước ảnh theo lô để giảm truy vấn.

## 7. Danh sách kiểm Safari/iOS bằng thiết bị thật (việc còn lại của Giai đoạn 7)

Làm trên iPhone (Safari) và, nếu có, iPad; ghi lại nơi nào lỗi (ảnh chụp màn hình):

1. Cuộn từ đầu trang: thanh địa chỉ co giãn có làm cảnh ghim (hero, Triết lý, Dịch vụ) giật hay xê dịch không.
2. Cuộn đàn hồi ở đầu và cuối trang: header ẩn/hiện có đúng không; vùng lộ ra phía trên hero và dưới chân trang có là màu tối (đã đặt `theme-color` và nền `html`).
3. Video hero và cảnh kết: tự phát ở chế độ thường; **bật Chế độ tiết kiệm pin** thì còn poster, chạm một lần (touchend/click) thì video phát.
4. Thanh kéo trước/sau: kéo ngón tay có cuộn trang theo không (đã đặt `touch-action: pan-y`).
5. Dock dưới cùng có đè lên thanh home của iPhone không (viewport chưa khai báo `viewport-fit=cover` nên `env(safe-area-inset-*)` bằng 0; nếu bị đè thì thêm `viewport-fit=cover` và bù lề trái/phải cho dock và header khi xoay ngang).
6. Font Garamond và Montserrat OTF/TTF hiện đúng, dấu tiếng Việt không bị cắt ở tiêu đề lớn.
7. iOS dưới 16.2 dùng giá trị dự phòng của `color-mix`; iOS dưới 16: tia hero được ẩn (không kéo ngang trang).
8. Xoay ngang rồi xoay dọc: ảnh bìa các nhóm dịch vụ vẫn mở khi cuộn tới (đã sửa ngưỡng quan sát), menu di động cuộn được.
9. Hộp xem ảnh lớn của trang liệu trình: mở, đổi ảnh, đóng bằng nút (Esc không có trên iPhone).
10. Nút Lên đầu trang không đè nội dung ở các trang thường.

## 8. Bộ công cụ kiểm thử (QA toolkit)

**Từ 18/11/2026 dùng bộ kiểm hồi quy mới** ở `C:\xampp\isha-backups\2026-11-18_after-phase3-restructure\qa-regression\` (hướng dẫn trong `README-QA.md` cùng thư mục): một lệnh `node run.mjs`, khoảng 5 phút, chỉ đọc DB thật `isha`. Gồm kiểm tĩnh (php -l, tương thích PHP 7.4, node --check, LF trong git, ngân sách JS 160 KB, 76 bài kiểm thử đơn vị của theme), PHP (cấu trúc trang chủ, CMS, Không gian, đường dẫn, nội dung, menu, trang quản trị), HTTP (mọi URL trong sitemap, 301, 404) và trình duyệt (17 loại trang × 3 cỡ màn hình cùng giảm chuyển động, popup Đặt lịch, form Liên hệ, dải ảnh Không gian). Bộ cũ dưới đây dựng cho trang chủ 13 section và DB thử nên phần lớn không còn chạy được; chỉ còn dùng để tham khảo axe, Lighthouse, WebKit.

Bộ công cụ và bài kiểm **không nằm trong thư mục dự án**; bản đóng gói ở `C:\xampp\isha-backups\2026-11-10_after-phase7\qa-toolkit.zip` (kèm `README-QA.md`). Gồm: `tools/` (`lint74`, `wpstyle`, `boot.php`, `tools/browser/` với Chrome headless qua CDP và công cụ audit tương phản, vùng chạm, tiêu đề, console), `tests/` (PHP trên DB thử `isha_sandbox` và `isha_fixture`, Chrome), `p4` đến `p8` (bài kiểm trình duyệt theo giai đoạn, ma trận Giai đoạn 7, đo hiệu năng, axe theo section, WebKit), `p8/t/fix-menu-order.php`; cổng kiểm cuối một lệnh `bash p8/t/final-all.sh` (khoảng 40 đến 60 phút: kiểm tĩnh, PHP, ma trận Chrome, các bộ trình duyệt, WebKit; kèm các bài của đợt kiểm toán lần hai: `laptop-heights.js`, `stage-fit.js`, `admin-bar.js`, `inview-tall.js`). Cần Node 24 và PHP CLI (có trong XAMPP); Lighthouse, Playwright và công cụ font cài riêng trong thư mục thử, không nằm trong zip (lệnh cài ở `README-QA.md`).

## 9. Sao lưu và khôi phục

Thư mục sao lưu: `C:\xampp\isha-backups\`. **Bản mới nhất `2026-11-18_after-phase3-restructure\`:** `db.sql` (DB thật `isha` sau giai đoạn 3 của đợt tái cấu trúc: Không gian ISHA, nội dung 17 liệu trình, menu mới; mã nguồn tương ứng là commit `19d8e8e` trên nhánh `develop/feature`) và bộ kiểm hồi quy `qa-regression\`. Ngoài ra DB `isha_backup` trong MySQL là bản người dùng tự sao lưu trước đợt tái cấu trúc (17/11/2026). Bản trước đó `2026-11-10_after-phase7\`: `theme-ISHA.zip`, `plugin-isha-core.zip`, `docs.zip`, `db.sql` (DB thật `isha` mới nhất: **sau** khi cập nhật `menu_order` và điền nội dung mẫu cho 17 liệu trình, lấy lúc 23:01), `qa-toolkit.zip`, `fix-menu-order.php` (tệp đã dùng ở mục 4.1.1; chạy lại là vô hại vì bài đã đúng thì bỏ qua). Thư mục `2026-11-10_before-events-articles\` giữ DB thật ngay trước khi thêm 5 sự kiện và 5 bài viết mẫu (`db.sql`, lúc 23:39), `rollback-events-articles.sql` (xoá đúng 10 bài, 3 chủ đề bài viết, tuỳ chọn `isha_article_cat_children` và trả bộ đếm loại sự kiện về 0; đã thử trên bản sao DB: mọi bảng trở lại giống hệt) và `add-samples.php` (nội dung mẫu và cách thêm). Thư mục `2026-11-10_before-service-content\` giữ DB thật ngay trước khi điền nội dung mẫu (`db.sql`, lúc 22:57), `rollback-service-content.sql` (khôi phục chọn lọc, đã thử trên bản sao DB: bảng bài viết, meta và bộ đếm nhóm trở lại giống hệt), cùng `content.json` (nội dung mẫu) và `fill-services.php` (chạy thử mặc định, `--apply` mới ghi, chỉ điền ô còn trống). Thư mục `2026-11-10_before-menu-order\` giữ DB thật **ngay trước** khi cập nhật (`db.sql`, lấy lúc 22:43) và `rollback-menu-order.sql` (khôi phục chọn lọc 17 giá trị). Khôi phục: mỗi zip đã chứa sẵn thư mục cấp một; giải nén `theme-ISHA.zip` vào `wp-content\themes\`, `plugin-isha-core.zip` vào `wp-content\plugins\`, `docs.zip` vào `C:\xampp\htdocs\ISHA_Clinic\`. DB: chạy trong `cmd.exe` `C:\xampp\mysql\bin\mysql.exe -u root isha < db.sql`. Bản `2026-11-09_phase0` là trạng thái trước khi bắt đầu dự án (theme, plugin và DB).

## 10. Tham chiếu kỹ thuật (hằng số, khoá, handle, bộ lọc)

- **Plugin ISHA Core 1.1.0** (theme 1.0.0). `ISHA_Core_Term_Fields::BA_MAX` 6; `ISHA_Core_Admin_Media::MAX_GALLERY` 24; Không gian tối đa 12 ảnh, Đối tác 16; `ISHA_Core_Home_Fields::MAX_ITEMS` 50; `ISHA_SERVICE_OTHERS_MAX` 5.
- **Kiểu bài:** `isha_service` (`/dich-vu/`), `isha_policy` (`/chinh-sach/`), `isha_appointment` và `isha_contact` (hộp thư, không công khai), `isha_event` (`/su-kien/`), `isha_testimonial` (không công khai), `isha_article` (`/tin-tuc/`; trước 18/11/2026 là `/bai-viet/`, đường dẫn cũ chuyển 301), `isha_space` (`/khong-gian-isha/`, từ 18/11/2026). **Taxonomy:** `isha_service_cat` (`/nhom-dich-vu/`), `isha_event_type`, `isha_article_cat` (liên kết trỏ tới danh sách đã lọc `?chuyen-muc=`; trang lưu trữ `/loai-su-kien/`, `/chu-de/` chuyển 301 về đó).
- **Khoá meta:** trang chủ `_isha_home_<mã section>` (11 section đang dùng, mảng có trường `enabled`; khoá cũ `_isha_home_story`, `_philosophy`, `_values`, `_vision` còn giữ dữ liệu, không còn đọc); trang Giới thiệu `_isha_about_story`, `_philosophy`, `_values`; hero của trang `_isha_hero_*`; bài Không gian `_isha_space_home`, `_subtitle`, `_summary`, `_gallery`; nhóm dịch vụ thêm `_isha_featured` (Dịch vụ tiêu biểu); nhóm dịch vụ `_isha_cover_id`, `_isha_cover_video_id`, `_isha_order`, `_isha_ba_before`, `_isha_ba_after`; seed `_isha_seed_key`, `_isha_seed_legacy`; sự kiện `_isha_event_start`, `_end`, `_start_ts`, `_end_ts`, `_location`, `_register_url`, `_featured`, `_portrait_id`; lời kể `_isha_tm_quote`, `_isha_tm_service_id` (khoá `_isha_tm_consent` đã gỡ ngày 10/11/2026); bài viết `_isha_article_subtitle`, `_isha_article_read_min`, `_isha_article_featured`; liệu trình (ACF) `isha_duration`, `isha_short_desc`, `isha_gallery`, `isha_steps`.
- **Tuỳ chọn:** `isha_core_settings` (hotline, Zalo, địa chỉ…), `isha_core_rewrite_ver` (= 3 từ 18/11/2026), theme mod `isha_landing_only`.
- **Handle CSS/JS:** style `isha-main`, `isha-home` (phụ thuộc `isha-main`), `isha-service-single`, `isha-scroll-top`; script `isha-main`, `isha-motion`, `isha-home-js` (kèm `window.ISHA.homeConfig`), `isha-service-single`, `isha-scroll-top`. Biến toàn cục duy nhất: `window.ISHA` (`motion`, `home`). Class `<html>`: `isha-motion-on`, `isha-motion-fail`, `isha-intro-on`, `isha-intro-end`; `<body>`: `isha-home`.
- **Bộ lọc công khai:** `isha/home/sections`, `isha/home/data/{section}`, `isha/scroll_top/enabled` (bool; mặc định tắt ở trang chi tiết Chính sách), `isha/admin/hide_comments_menu` (bool; mặc định true: ẩn menu Bình luận), `isha_core/home_schema`, `isha_core/home_page_id`, `isha_core/home_defaults`, `isha_core/news_url` (trang đích của đường dẫn Tin tức cũ), `isha_core/contact_topics`, `isha_core/booking_slots`.
- **Nonce:** `isha_save_home`/`isha_home_nonce`, `isha_save_event`/`isha_event_nonce`, `isha_save_testimonial`/`isha_testimonial_nonce`, `isha_save_article`/`isha_article_nonce`, `isha_save_term`/`isha_term_nonce`, `isha_seed_services`/`isha_seed_nonce`.
- **Mốc thời gian:** cổng `<head>` 6 giây (thêm `isha-motion-fail` nếu JS không khởi động), chốt intro 3,5 giây, chế độ gọn `(max-width: 900px), (max-height: 560px)`, băng chuyền Sự kiện 7 giây và Lời kể 8 giây.
