<?php
/**
 * Template Name: Giới thiệu
 * Template Post Type: page
 *
 * @package ISHA
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

get_header();

while ( have_posts() ) :
	the_post();
	isha_page_banner();
	?>
	<section class="section">
		<div class="container container--narrow">
			<div class="entry-content"><?php the_content(); ?></div>
		</div>
	</section>

	<section class="section section--alt">
		<div class="container">
			<header class="section__header">
				<h2 class="section__title"><?php esc_html_e( 'Giá trị cốt lõi', 'isha' ); ?></h2>
			</header>
			<div class="grid grid--3">
				<div class="card card--pad">
					<h3 class="card__title"><?php esc_html_e( 'Trung thực & cởi mở', 'isha' ); ?></h3>
					<p><?php esc_html_e( 'Tư vấn minh bạch, kỳ vọng được quản lý rõ ràng ngay từ buổi đầu.', 'isha' ); ?></p>
				</div>
				<div class="card card--pad">
					<h3 class="card__title"><?php esc_html_e( 'Chuyên nghiệp & tận tâm', 'isha' ); ?></h3>
					<p><?php esc_html_e( 'Phác đồ dựa trên khoa học da liễu, thực hiện bởi đội ngũ chuyên môn giàu kinh nghiệm.', 'isha' ); ?></p>
				</div>
				<div class="card card--pad">
					<h3 class="card__title"><?php esc_html_e( 'Tôn vinh vẻ đẹp nội tại', 'isha' ); ?></h3>
					<p><?php esc_html_e( 'Khơi gợi vẻ đẹp tự nhiên thay vì thay đổi con người bạn.', 'isha' ); ?></p>
				</div>
			</div>
		</div>
	</section>
	<?php
endwhile;

isha_cta_section();
get_footer();
