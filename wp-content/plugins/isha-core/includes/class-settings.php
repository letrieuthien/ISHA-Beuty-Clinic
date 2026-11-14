<?php
/**
 * Trang cài đặt: thông tin liên hệ, giờ làm việc, mạng xã hội.
 *
 * @package ISHA_Core
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class ISHA_Core_Settings {

	const OPTION = 'isha_core_settings';

	public static function init() {
		add_action( 'admin_menu', array( __CLASS__, 'menu' ) );
		add_action( 'admin_init', array( __CLASS__, 'register' ) );
	}

	/**
	 * key => [label, type]. type: text | email | url | textarea.
	 *
	 * @return array
	 */
	public static function fields() {
		return array(
			'phone'        => array( __( 'Hotline', 'isha-core' ), 'text' ),
			'email'        => array( __( 'Email hiển thị', 'isha-core' ), 'email' ),
			'notify_email' => array( __( 'Email nhận thông báo đặt lịch (để trống = email admin)', 'isha-core' ), 'email' ),
			'address'      => array( __( 'Địa chỉ', 'isha-core' ), 'textarea' ),
			'hours'        => array( __( 'Giờ làm việc (mỗi dòng: "Thứ 2 – Thứ 6: 08:00 – 18:00")', 'isha-core' ), 'textarea' ),
			'map_url'      => array( __( 'Link nhúng Google Maps (Chia sẻ → Nhúng bản đồ → copy giá trị src)', 'isha-core' ), 'url' ),
			'facebook'     => array( __( 'Facebook URL', 'isha-core' ), 'url' ),
			'instagram'    => array( __( 'Instagram URL', 'isha-core' ), 'url' ),
			'tiktok'       => array( __( 'TikTok URL', 'isha-core' ), 'url' ),
			'youtube'      => array( __( 'YouTube URL', 'isha-core' ), 'url' ),
			'zalo'         => array( __( 'Zalo URL', 'isha-core' ), 'url' ),
		);
	}

	public static function menu() {
		add_menu_page(
			__( 'ISHA Clinic', 'isha-core' ),
			__( 'ISHA Clinic', 'isha-core' ),
			'manage_options',
			'isha-core',
			array( __CLASS__, 'render' ),
			'dashicons-admin-generic',
			24
		);
	}

	public static function register() {
		register_setting(
			'isha_core_group',
			self::OPTION,
			array(
				'type'              => 'array',
				'sanitize_callback' => array( __CLASS__, 'sanitize' ),
				'default'           => array(),
			)
		);
	}

	public static function sanitize( $input ) {
		$clean = array();
		if ( ! is_array( $input ) ) {
			return $clean;
		}
		foreach ( self::fields() as $key => $field ) {
			if ( ! isset( $input[ $key ] ) ) {
				continue;
			}
			$value = wp_unslash( $input[ $key ] );
			switch ( $field[1] ) {
				case 'email':
					$clean[ $key ] = sanitize_email( $value );
					break;
				case 'url':
					$clean[ $key ] = esc_url_raw( $value, array( 'https', 'http' ) );
					break;
				case 'textarea':
					$clean[ $key ] = sanitize_textarea_field( $value );
					break;
				default:
					$clean[ $key ] = sanitize_text_field( $value );
			}
		}
		return $clean;
	}

	public static function render() {
		if ( ! current_user_can( 'manage_options' ) ) {
			return;
		}
		$values = get_option( self::OPTION, array() );
		?>
		<div class="wrap">
			<h1><?php esc_html_e( 'Cài đặt ISHA Clinic', 'isha-core' ); ?></h1>
			<form method="post" action="options.php">
				<?php settings_fields( 'isha_core_group' ); ?>
				<table class="form-table" role="presentation">
					<?php foreach ( self::fields() as $key => $field ) : ?>
						<?php $val = isset( $values[ $key ] ) ? $values[ $key ] : ''; ?>
						<tr>
							<th scope="row"><label for="isha-<?php echo esc_attr( $key ); ?>"><?php echo esc_html( $field[0] ); ?></label></th>
							<td>
								<?php if ( 'textarea' === $field[1] ) : ?>
									<textarea class="large-text" rows="4" id="isha-<?php echo esc_attr( $key ); ?>" name="<?php echo esc_attr( self::OPTION . '[' . $key . ']' ); ?>"><?php echo esc_textarea( $val ); ?></textarea>
								<?php else : ?>
									<input class="regular-text" type="<?php echo esc_attr( 'text' === $field[1] ? 'text' : $field[1] ); ?>" id="isha-<?php echo esc_attr( $key ); ?>" name="<?php echo esc_attr( self::OPTION . '[' . $key . ']' ); ?>" value="<?php echo esc_attr( $val ); ?>">
								<?php endif; ?>
							</td>
						</tr>
					<?php endforeach; ?>
				</table>
				<?php submit_button(); ?>
			</form>
		</div>
		<?php
	}
}
