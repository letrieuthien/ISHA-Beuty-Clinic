# Role & Objective
Bạn là một Senior Frontend Developer kết hợp UI/UX Designer chuyên nghiệp. Hãy xây dựng một giao diện Template dùng chung cho 2 trang **Danh sách Tin tức (Articles)** và **Sự kiện (Events)** theo phong cách **Editorial Bento Grid**, tối ưu hóa tính thẩm mỹ báo chí cao cấp, sử dụng thuần CSS (Grid & Transition) và hạn chế tối đa JavaScript.

# Cấu trúc Bố cục Bento Grid (Layout Structure)
Trong một màn hình hiển thị, lưới bento phải sắp xếp chính xác các thành phần sau mà không bị trùng lặp bài viết:
1. **Bài viết Tiêu điểm / Mới nhất (Hero / Latest Item):** Chiếm vị trí trọng tâm, kích thước lớn.
2. **Bài viết liên quan cùng chuyên mục (Category-related Items):** Gồm chính xác 3 mục có cấu trúc hiển thị khác nhau.
3. **Bài viết cùng tác giả (Author-related Items):** Gồm chính xác 3 mục, tuyệt đối KHÔNG TRÙNG LẶP với 3 bài liên quan chuyên mục ở trên.

# Yêu cầu Thiết kế & Giao diện (UI/UX Guidelines)
- **Tỷ lệ khung hình (Aspect Ratios):** Áp dụng linh hoạt và kết hợp đa dạng giữa các ô vuông (1x1), ô ngang (2x1) và ô dọc (1x2) để tạo nhịp điệu thị giác, tránh cảm giác nhàm chán hoặc mỏi mắt.
- **Khoảng nghỉ thị giác (White Space):** Đặt khoảng cách giữa các ô (grid gap) từ 16px đến 24px để tổng thể trang báo thoáng đãng, sang trọng và không bị ngộp thông tin.
- **Phân cấp màu sắc & Độ tương phản:** 
  - Dành các mảng màu đậm hoặc nền tràn ảnh cho ô Tiêu điểm (Hero).
  - Các ô tin phụ / tiện ích sử dụng nền trắng hoặc xám nhạt (Light Gray) sẵn có trong hệ thống để làm nổi bật nội dung chữ.
  - Sử dụng các biến màu (Design Tokens / CSS Variables) đã định nghĩa sẵn trong dự án, **tuyệt đối không tự sinh mã màu mới**.
  - Ưu tiên sử dụng các class đã được định nghĩa common trong hệ thống, tránh phát sinh các class trùng nhau về chức năng nhưng khác tên.

# Quy chuẩn Kỹ thuật (Technical Constraints)
- **CSS-First Architecture:** Sử dụng tối đa sức mạnh của `display: grid` (chẳng hạn như `grid-template-columns` kết hợp `grid-row/column span`) và CSS Transitions cho các hiệu ứng tương tác.
- **Giới hạn JavaScript (JS Constraint):** Chỉ dùng JS trong trường hợp bắt buộc phải xử lý state tối thiểu (như chuyển đổi tab danh mục hoặc phân trang). Toàn bộ hiệu ứng hover, layout shift, và animation xuất hiện đều phải viết bằng CSS.
- **Hoạt ảnh (CSS Animations):** Thiết lập hiệu ứng fade-in / slide-up mượt mà bằng `@keyframes` hoặc `transition` khi các mục bài viết xuất hiện trên màn hình hoặc khi hover chuột.
- **Responsive Design:** Tối ưu hóa hoàn hảo trên thiết bị di động (Mobile First). Trên màn hình nhỏ, bento grid phải chuyển đổi linh hoạt thành dạng cột đơn hoặc lưới 2 cột gọn gàng, giữ nguyên thứ tự ưu tiên thị giác mà không bị tràn màn hình.

# Output Mong muốn
Hãy cung cấp mã nguồn hoàn chỉnh (HTML kết hợp Tailwind CSS / CSS Modules tuỳ theo cấu trúc dự án, kèm theo chú thích rõ ràng cho từng phần Bento Grid).