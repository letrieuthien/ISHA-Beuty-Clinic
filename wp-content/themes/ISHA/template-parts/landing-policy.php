<?php
/**
 * Landing page giai đoạn 1: trang "Chính sách khách hàng".
 *
 * Phần thân lấy từ nội dung trang (the_content) để chỉnh sửa trực tiếp bằng Elementor.
 *
 * @package ISHA
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

while ( have_posts() ) :
	the_post();
	?>
	<section class="section landing-policy">
		<?php $isha_eyebrow = trim( (string) isha_mod( 'hero_eyebrow' ) ); ?>
		<?php if ( '' !== $isha_eyebrow ) : // Chỉ in khi có chữ, tránh gạch vàng (::after) lẻ loi phía trên nội dung. ?>
			<div class="container">
				<header class="section__header">
					<p class="hero__eyebrow landing-policy__eyebrow"><?php echo esc_html( $isha_eyebrow ); ?></p>
				</header>
			</div>
		<?php endif; ?>		
		<div class="container">
			<div class="entry-content landing-policy__content"><?php the_content(); ?></div>
		</div>	
	</section>
	<?php
endwhile;
