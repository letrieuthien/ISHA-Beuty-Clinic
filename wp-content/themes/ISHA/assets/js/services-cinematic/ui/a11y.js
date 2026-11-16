/**
 * Trợ năng: nhãn thời điểm (trang trí, aria-hidden) theo điểm dừng; stage nhận focus (tabindex 0) để dùng phím, có nhãn hướng dẫn. Vùng
 * aria-live đọc thời điểm của khung cảnh khi index ổn định quá CONFIG.announceDelayMs (cuộn lướt qua không đọc dồn). Từ khi card thành dãy
 * trượt ngang (mọi card hiện cùng lúc, không gắn với điểm dừng), không còn đọc "Dịch vụ i/N" theo cuộn: danh sách card đọc được như một
 * danh sách thường.
 */
import { CONFIG } from '../config.js';

export function createA11y( { stage, live, dayLabel, services, stops } ) {
	const N = services.length;
	let timer = 0;
	let said = -1;

	stage.setAttribute( 'tabindex', '0' );
	stage.setAttribute( 'role', 'group' );
	stage.setAttribute( 'aria-roledescription', 'khung cảnh' );
	stage.setAttribute( 'aria-label', `Khung cảnh bình minh và ${ N } liệu trình. Phím mũi tên cuộn khung cảnh, Esc ẩn dãy thẻ, Enter hiện lại.` );

	function setLabel( i ) {
		if ( dayLabel && stops[ i ] ) {
			dayLabel.textContent = stops[ i ].label;
		}
	}

	function onIndex( i ) {
		setLabel( i );
		window.clearTimeout( timer );
		if ( i < 0 || i === said ) {
			return;
		}
		timer = window.setTimeout( () => {
			said = i;
			live.textContent = stops[ i ] ? `Khung cảnh: ${ stops[ i ].label }.` : '';
		}, CONFIG.announceDelayMs );
	}

	return {
		onIndex,
		destroy() {
			window.clearTimeout( timer );
			[ 'tabindex', 'role', 'aria-roledescription', 'aria-label' ].forEach( ( a ) => stage.removeAttribute( a ) );
			live.textContent = '';
		},
	};
}
