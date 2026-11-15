/**
 * Trợ năng: vùng aria-live đọc "Dịch vụ i/N: tên. Thời gian …" khi index ổn định quá CONFIG.announceDelayMs (cuộn lướt qua không đọc dồn);
 * nhãn thời điểm (trang trí, aria-hidden) theo điểm dừng; stage nhận focus (tabindex 0) để dùng phím, có nhãn hướng dẫn.
 */
import { CONFIG } from '../config.js';

export function createA11y( { stage, live, dayLabel, services, stops } ) {
	const N = services.length;
	let timer = 0;
	let said = -1;

	stage.setAttribute( 'tabindex', '0' );
	stage.setAttribute( 'role', 'group' );
	stage.setAttribute( 'aria-roledescription', 'khung cảnh' );
	stage.setAttribute( 'aria-label', `Các liệu trình, ${ N } điểm dừng. Dùng phím mũi tên để chuyển liệu trình, Esc để ẩn thẻ, Enter để hiện lại.` );

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
			const s = services[ i ];
			said = i;
			live.textContent = `Dịch vụ ${ i + 1 }/${ N }: ${ s.title }.${ s.duration ? ` Thời gian ${ s.duration }.` : '' }`;
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
