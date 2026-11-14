<?php
/**
 * Template dùng chung của trang danh sách bài viết (Tin tức, Sự kiện) theo phong cách Editorial Bento Grid.
 *
 * Cấu trúc: tiêu đề trang, các tab chuyên mục (liên kết thường, không JavaScript), lưới bento rồi phân trang. Dữ liệu từ
 * isha_editorial_view() (inc/editorial-list.php); giao diện ở assets/css/editorial-list.css.
 *
 * Lưới bento (desktop, 4 cột; hàng 1 và hàng 4 chỉ chứa nhãn nhóm):
 *   hàng 1  [nhãn Tiêu điểm      ][nhãn Cùng chuyên mục]
 *   hàng 2  [ HERO 2x2           ][ C1 1x2 ][ C2 1x1 ]
 *   hàng 3  [                    ][        ][ C3 1x1 ]
 *   hàng 4  [nhãn Cùng tác giả                        ]
 *   hàng 5  [ A1 2x1             ][ A2 1x1 ][ A3 1x1 ]
 * Nhóm ít hơn 3 bài thì CSS dàn lại cho đầy ô (class isha-bento--cN, isha-bento--aN); không có ô trống.
 * Thứ tự trong HTML cũng là thứ tự ưu tiên khi lưới thu thành 1 hoặc 2 cột: hero, nhóm chuyên mục, nhóm tác giả.
 *
 * Tham số ($args): post_type (isha_event hoặc isha_article).
 *
 * @package ISHA
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$isha_type = ( isset( $args['post_type'] ) && is_string( $args['post_type'] ) ) ? $args['post_type'] : '';
$isha_view = isha_editorial_view( $isha_type );

$isha_hero    = ! empty( $isha_view['hero'] ) ? $isha_view['hero'] : array();
$isha_cats    = ! empty( $isha_view['cat'] ) ? $isha_view['cat'] : array();
$isha_authors = ! empty( $isha_view['author'] ) ? $isha_view['author'] : array();
$isha_tabs    = ! empty( $isha_view['tabs'] ) ? $isha_view['tabs'] : array();
$isha_links   = ! empty( $isha_view['pagination'] ) ? $isha_view['pagination'] : array();
$isha_count_c = count( $isha_cats );
$isha_count_a = count( $isha_authors );
$isha_step    = 0; // Thứ tự xuất hiện: độ trễ hoạt ảnh của từng ô và nhãn.
?>
<section class="section isha-bento-page" data-source="<?php echo esc_attr( $isha_type ); ?>">
	<div class="container">

		<header class="section__header isha-bento-page__head">
			<h1 class="section__title"><?php the_title(); ?></h1>
			<?php if ( '' !== trim( get_the_content() ) ) : ?>
				<div class="entry-content container--narrow isha-bento-page__lead"><?php the_content(); ?></div>
			<?php endif; ?>
		</header>

		<?php if ( count( $isha_tabs ) > 2 ) : ?>
			<?php /* Tab chuyên mục: liên kết thường tới ?chuyen-muc=slug (tải lại trang, không JavaScript). Dùng lại class .btn của hệ thống. */ ?>
			<nav class="isha-bento-tabs" aria-label="<?php esc_attr_e( 'Lọc theo chuyên mục', 'isha' ); ?>">
				<?php foreach ( $isha_tabs as $isha_tab ) : ?>
					<a class="btn btn--sm<?php echo $isha_tab['current'] ? ' btn--primary' : ''; ?>" href="<?php echo esc_url( $isha_tab['url'] ); ?>"<?php echo $isha_tab['current'] ? ' aria-current="page"' : ''; // phpcs:ignore WordPress.Security.EscapeOutput -- chuỗi cố định. ?>><?php echo esc_html( $isha_tab['label'] ); ?></a>
				<?php endforeach; ?>
			</nav>
		<?php endif; ?>

		<?php if ( $isha_hero ) : ?>
			<div class="isha-bento isha-bento--c<?php echo (int) $isha_count_c; ?> isha-bento--a<?php echo (int) $isha_count_a; ?>">

				<?php /* 1. Tiêu điểm / Mới nhất (hero): ô lớn nhất, nền ảnh tràn hoặc mảng màu đậm. */ ?>
				<h2 class="isha-bento__label isha-bento__label--hero" style="--i:0">
					<span class="isha-bento__label-text"><?php echo esc_html( (int) $isha_view['page'] > 1 ? __( 'Tiêu điểm', 'isha' ) : __( 'Mới nhất', 'isha' ) ); ?></span>
				</h2>
				<?php
				get_template_part(
					'template-parts/editorial/tile',
					null,
					array(
						'card'    => $isha_hero,
						'role'    => 'hero',
						'variant' => 'hero',
						'index'   => $isha_step++,
					)
				);
				?>

				<?php /* 2. Cùng chuyên mục: tối đa 3 ô, mỗi ô một cấu trúc hiển thị khác nhau (stack, cover, strip). */ ?>
				<?php if ( $isha_count_c > 0 ) : ?>
					<h2 class="isha-bento__label isha-bento__label--cat" style="--i:<?php echo (int) $isha_step; ?>">
						<span class="isha-bento__label-text"><?php esc_html_e( 'Cùng chuyên mục', 'isha' ); ?></span>
						<?php if ( '' !== $isha_view['cat_label'] ) : ?>
							<span class="isha-bento__label-name"><?php echo esc_html( $isha_view['cat_label'] ); ?></span>
						<?php endif; ?>
					</h2>
					<?php foreach ( $isha_cats as $isha_slot => $isha_card ) : ?>
						<?php
						get_template_part(
							'template-parts/editorial/tile',
							null,
							array(
								'card'    => $isha_card,
								'role'    => 'c' . ( $isha_slot + 1 ),
								'variant' => isha_editorial_variant( 'cat', $isha_slot, $isha_count_c ),
								'index'   => $isha_step++,
							)
						);
						?>
					<?php endforeach; ?>
				<?php endif; ?>

				<?php /* 3. Cùng tác giả: tối đa 3 ô, không trùng bài với nhóm chuyên mục ở trên. */ ?>
				<?php if ( $isha_count_a > 0 ) : ?>
					<h2 class="isha-bento__label isha-bento__label--author" style="--i:<?php echo (int) $isha_step; ?>"<?php echo $isha_step >= 3 ? ' data-reveal="scroll"' : ''; // phpcs:ignore WordPress.Security.EscapeOutput -- chuỗi cố định. ?>>
						<span class="isha-bento__label-text"><?php esc_html_e( 'Cùng tác giả', 'isha' ); ?></span>
						<?php if ( '' !== $isha_view['author_label'] ) : ?>
							<span class="isha-bento__label-name"><?php echo esc_html( $isha_view['author_label'] ); ?></span>
						<?php endif; ?>
					</h2>
					<?php foreach ( $isha_authors as $isha_slot => $isha_card ) : ?>
						<?php
						get_template_part(
							'template-parts/editorial/tile',
							null,
							array(
								'card'    => $isha_card,
								'role'    => 'a' . ( $isha_slot + 1 ),
								'variant' => isha_editorial_variant( 'author', $isha_slot, $isha_count_a ),
								'index'   => $isha_step++,
							)
						);
						?>
					<?php endforeach; ?>
				<?php endif; ?>

			</div>

			<?php if ( $isha_links ) : ?>
				<?php /* Phân trang: dùng lại class .pagination/.nav-links/.page-numbers của hệ thống. */ ?>
				<nav class="navigation pagination" aria-label="<?php esc_attr_e( 'Phân trang', 'isha' ); ?>">
					<div class="nav-links">
						<?php foreach ( $isha_links as $isha_link ) : ?>
							<?php echo $isha_link; // phpcs:ignore WordPress.Security.EscapeOutput -- HTML do paginate_links() dựng, đã escape. ?>
						<?php endforeach; ?>
					</div>
				</nav>
			<?php endif; ?>

		<?php elseif ( ! $isha_view ) : ?>
			<?php if ( current_user_can( 'manage_options' ) ) : ?>
				<p class="isha-notice"><?php esc_html_e( 'Cần kích hoạt plugin ISHA Core để hiển thị danh sách này.', 'isha' ); ?></p>
			<?php endif; ?>
		<?php else : ?>
			<p class="isha-bento__empty"><?php echo esc_html( $isha_view['empty'] ); ?></p>
		<?php endif; ?>

	</div>
</section>
