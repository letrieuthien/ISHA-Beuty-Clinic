/**
 * Trợ năng của section: stage nhận focus (tabindex 0) để dùng phím, có nhãn hướng dẫn. Vùng aria-live báo khi người dùng ẩn vòng thẻ (Esc /
 * chạm khung cảnh) và khi hiện lại, trễ CONFIG.announceDelayMs để ẩn rồi hiện liền nhau không đọc dồn. Không đọc gì theo cuộn: điểm dừng chỉ
 * điều khiển khung cảnh. Trợ năng của chính vòng thẻ (nút tạm dừng, aria-current, đọc tên thẻ được chọn): card-ring/a11y.js.
 */
import { CONFIG } from '../config.js';

const MSG = {
	dismissed: 'Đã ẩn vòng liệu trình. Nhấn Enter hoặc chạm vào khung cảnh để hiện lại.',
	shown: 'Đã hiện vòng liệu trình.',
};

export function createA11y( { stage, live, services } ) {
	const N = services.length;
	let timer = 0;

	stage.setAttribute( 'tabindex', '0' );
	stage.setAttribute( 'role', 'group' );
	stage.setAttribute( 'aria-roledescription', 'khung cảnh' );
	stage.setAttribute( 'aria-label', `Khung cảnh bình minh và ${ N } liệu trình. Trong vòng thẻ, phím mũi tên chọn thẻ trước / sau. Esc ẩn vòng thẻ, Enter hiện lại.` );

	/**
	 * Gọi khi state card đổi. Chỉ báo chuyển đổi do người dùng: shown → dismissed và dismissed → shown (vào / rời section thì không).
	 *
	 * @param {{phase: string}} prev State trước.
	 * @param {{phase: string}} next State sau.
	 */
	function onCard( prev, next ) {
		const user = ( prev.phase === 'shown' && next.phase === 'dismissed' ) || ( prev.phase === 'dismissed' && next.phase === 'shown' );
		if ( ! user ) {
			return;
		}
		window.clearTimeout( timer );
		timer = window.setTimeout( () => {
			live.textContent = MSG[ next.phase ];
		}, CONFIG.announceDelayMs );
	}

	return {
		onCard,
		destroy() {
			window.clearTimeout( timer );
			[ 'tabindex', 'role', 'aria-roledescription', 'aria-label' ].forEach( ( a ) => stage.removeAttribute( a ) );
			live.textContent = '';
		},
	};
}
