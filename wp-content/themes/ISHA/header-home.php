<?php
/**
 * Header của trang chủ điện ảnh (gọi bằng get_header( 'home' )).
 *
 * Cùng thanh header với header.php (template-parts/header/masthead.php) để assets/js/main.js điều khiển menu như mọi trang; khác ở
 * class `site-header--home` (intro của trang chủ ẩn header trong lúc chạy, home.css), thẻ <meta theme-color> và thẻ <main> không có
 * class `page` (trang chủ không dùng page.css và không chừa dải header phía trên: hero nằm dưới header trong suốt).
 *
 * @package ISHA
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}
?>
<!doctype html>
<html <?php language_attributes(); ?>>
<head>
	<meta charset="<?php bloginfo( 'charset' ); ?>">
	<meta name="viewport" content="width=device-width, initial-scale=1">
	<meta name="theme-color" content="#141414">
	<?php wp_head(); ?>
</head>
<body <?php body_class(); ?>>
<?php wp_body_open(); ?>
<a class="skip-link screen-reader-text" href="#content"><?php esc_html_e( 'Bỏ qua, đến nội dung chính', 'isha' ); ?></a>

<?php get_template_part( 'template-parts/header/masthead', null, array( 'home' => true ) ); ?>

<main id="content" class="site-main">
