/**
 * Trợ năng: stage nhận focus (tabindex 0) để dùng phím, có nhãn hướng dẫn. Vùng aria-live báo khi người dùng ẩn dãy card (Esc / chạm khung
 * cảnh) và khi hiện lại, trễ CONFIG.announceDelayMs để ẩn rồi hiện liền nhau không đọc dồn. Không đọc gì theo cuộn: card là dãy trượt
 * ngang (mọi card hiện cùng lúc, không gắn với điểm dừng) đọc được như danh sách thường; kịch bản không còn nhãn thời điểm.
 */
import { CONFIG } from '../config.js';

const MSG = {
	dismissed: 'Đã ẩn dãy liệu trình. Nhấn Enter hoặc chạm vào khung cảnh để hiện lại.',
	shown: 'Đã hiện dãy liệu trình.',
};

export function createA11y( { stage, live, services } ) {
	const N = services.length;
	let timer = 0;

	stage.setAttribute( 'tabindex', '0' );
	stage.setAttribute( 'role', 'group' );
	stage.setAttribute( 'aria-roledescription', 'khung cảnh' );
	stage.setAttribute( 'aria-label', `Khung cảnh bình minh và ${ N } liệu trình. Phím mũi tên cuộn khung cảnh, Esc ẩn dãy thẻ, Enter hiện lại.` );

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
