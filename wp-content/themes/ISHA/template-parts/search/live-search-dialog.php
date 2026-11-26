<?php
/**
 * Popup Tìm kiếm nhanh (in ở chân trang, xem inc/live-search.php). `<dialog>` mở bằng showModal() trong live-search.js.
 *
 * Cấu trúc: thanh nhập (kính lúp, ô nhập combobox, nút xoá, nút Esc/Đóng) · vùng thân có 4 trạng thái (data-state trên .isha-search:
 * idle = chưa gõ, loading = skeleton, results = danh sách gom nhóm, empty = không có kết quả; error dùng chung khung empty) ·
 * thanh phím tắt (ẩn trên màn hình cảm ứng và chế độ gọn).
 *
 * @package ISHA
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$isha_suggestions = isha_live_search_suggestions();

/**
 * In hàng từ khoá gợi ý.
 *
 * @param string[] $list Từ khoá.
 */
$isha_print_chips = static function ( array $list ) {
	if ( ! $list ) {
		return;
	}
	echo '<ul class="isha-search__chips" role="list">';
	foreach ( $list as $word ) {
		printf(
			'<li><button type="button" class="isha-search__chip" data-isha-search-chip="%1$s">%2$s</button></li>',
			esc_attr( $word ),
			esc_html( $word )
		);
	}
	echo '</ul>';
};
?>
<dialog class="isha-search" id="isha-search" aria-label="<?php esc_attr_e( 'Tìm kiếm', 'isha' ); ?>" data-state="idle">
	<div class="isha-search__panel">
		<div class="isha-search__bar">
			<?php echo isha_live_search_icon( 'isha-search__lens' ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- SVG dựng sẵn. ?>
			<label class="screen-reader-text" for="isha-search-input"><?php esc_html_e( 'Từ khoá tìm kiếm', 'isha' ); ?></label>
			<input
				type="text"
				inputmode="search"
				id="isha-search-input"
				class="isha-search__input"
				placeholder="<?php esc_attr_e( 'Tìm kiếm dịch vụ, tin tức, sự kiện...', 'isha' ); ?>"
				autocomplete="off"
				autocorrect="off"
				autocapitalize="off"
				spellcheck="false"
				enterkeyhint="search"
				maxlength="80"
				role="combobox"
				aria-expanded="false"
				aria-controls="isha-search-list"
				aria-autocomplete="list"
			>
			<button type="button" class="isha-search__clear" data-isha-search-clear hidden>
				<span aria-hidden="true">&times;</span>
				<span class="screen-reader-text"><?php esc_html_e( 'Xoá từ khoá', 'isha' ); ?></span>
			</button>
			<button type="button" class="isha-search__esc" data-isha-search-close>
				<span class="isha-search__esc-key" aria-hidden="true">Esc</span>
				<span class="isha-search__esc-touch" aria-hidden="true"><?php esc_html_e( 'Đóng', 'isha' ); ?></span>
				<span class="screen-reader-text"><?php esc_html_e( 'Đóng tìm kiếm', 'isha' ); ?></span>
			</button>
		</div>

		<div class="isha-search__body">
			<div class="isha-search__idle">
				<span class="isha-search__orb" aria-hidden="true"><?php echo isha_live_search_icon(); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- SVG dựng sẵn. ?></span>
				<p class="isha-search__hint"><?php esc_html_e( 'Nhập từ khóa bạn cần tìm.', 'isha' ); ?></p>
				<p class="isha-search__sub"><?php esc_html_e( 'Tìm nhanh theo tên liệu trình, bài viết kiến thức hoặc sự kiện tại ISHA', 'isha' ); ?></p>
				<?php $isha_print_chips( $isha_suggestions ); ?>
			</div>

			<div class="isha-search__skeleton" aria-hidden="true">
				<?php for ( $isha_i = 0; $isha_i < 4; $isha_i++ ) : ?>
					<div class="isha-search__sk-row"><span class="isha-search__sk-thumb"></span><span class="isha-search__sk-lines"><span></span><span></span></span></div>
				<?php endfor; ?>
			</div>

			<div class="isha-search__list" id="isha-search-list" role="listbox" aria-label="<?php esc_attr_e( 'Kết quả tìm kiếm', 'isha' ); ?>"></div>

			<div class="isha-search__empty">
				<p class="isha-search__hint" data-isha-search-empty></p>
				<?php $isha_print_chips( $isha_suggestions ); ?>
			</div>
		</div>

		<div class="isha-search__foot" aria-hidden="true">
			<span><kbd>&uarr;</kbd><kbd>&darr;</kbd> <?php esc_html_e( 'Di chuyển', 'isha' ); ?></span>
			<span><kbd>&crarr;</kbd> <?php esc_html_e( 'Chọn', 'isha' ); ?></span>
			<span><kbd>esc</kbd> <?php esc_html_e( 'Đóng cửa sổ', 'isha' ); ?></span>
		</div>

		<p class="screen-reader-text" role="status" aria-live="polite" data-isha-search-status></p>
	</div>
</dialog>
