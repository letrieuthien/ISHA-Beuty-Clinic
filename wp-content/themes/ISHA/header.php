<?php
/**
 * Header.
 *
 * Thanh header (logo, menu, ngôn ngữ, tìm kiếm, Đặt lịch) nằm ở template-parts/header/masthead.php, dùng chung với header-home.php.
 * Trang con: <main> có class `page` khi là trang nội dung; CSS chừa chỗ cho header cố định ở .site-main (main.css, khối Header).
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
	<?php wp_head(); ?>
</head>
<body <?php body_class(); ?>>
<?php wp_body_open(); ?>
<a class="skip-link screen-reader-text" href="#content"><?php esc_html_e( 'Bỏ qua, đến nội dung chính', 'isha' ); ?></a>

<?php get_template_part( 'template-parts/header/masthead' ); ?>

<main id="content" class="site-main<?php echo isha_is_inner_page() ? ' page' : ''; ?>">
