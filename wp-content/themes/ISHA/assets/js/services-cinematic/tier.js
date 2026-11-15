/**
 * Chọn chế độ hiển thị. Thứ tự quyết định (đặc tả mục "CHẾ ĐỘ & PHÁT HIỆN THIẾT BỊ"):
 *   1) prefers-reduced-motion → STATIC (không tải thư viện 3D);
 *   2) không tạo được WebGL2 → 2D;
 *   3) điện thoại/máy tính bảng (pointer: coarse, UA mobile, hoặc màn hình < 900px) → LUÔN LITE;
 *   4) desktop: hiện dùng heuristics (hardwareConcurrency >= 8 và deviceMemory >= 8 → FULL, còn lại → LITE).
 * Giai đoạn 6 sẽ thêm @pmndrs/detect-gpu (benchmark tự host) cho FULL_HIGH / tier 0 và governor runtime hạ bậc khi tụt khung hình.
 * Quản trị viên có thể ép chế độ bằng ?isha-mode=FULL_HIGH|FULL|LITE|2D|STATIC (chỉ khi bảng debug bật).
 */
export const MODES = Object.freeze( [ 'FULL_HIGH', 'FULL', 'LITE', '2D', 'STATIC' ] );

export function hasWebGL2() {
	try {
		const c = document.createElement( 'canvas' );
		const gl = c.getContext( 'webgl2' );
		if ( ! gl ) {
			return false;
		}
		const lose = gl.getExtension( 'WEBGL_lose_context' );
		if ( lose ) {
			lose.loseContext();
		}
		return true;
	} catch ( err ) {
		return false;
	}
}

export function isMobileLike() {
	const coarse = window.matchMedia && window.matchMedia( '(pointer: coarse)' ).matches;
	const ua = /Mobi|Android|iPhone|iPad|iPod/i.test( navigator.userAgent || '' );
	return coarse || ua || window.innerWidth < 900;
}

export function detectMode( { reduced, debug } ) {
	const reasons = [];
	let forced = null;
	if ( debug ) {
		const q = new URLSearchParams( window.location.search ).get( 'isha-mode' );
		if ( q && MODES.includes( q ) ) {
			forced = q;
		}
	}
	if ( forced ) {
		return { mode: forced, mobile: isMobileLike(), reasons: [ 'ép bằng ?isha-mode' ] };
	}
	if ( reduced ) {
		return { mode: 'STATIC', mobile: isMobileLike(), reasons: [ 'prefers-reduced-motion' ] };
	}
	if ( ! hasWebGL2() ) {
		return { mode: '2D', mobile: isMobileLike(), reasons: [ 'không có WebGL2' ] };
	}
	if ( isMobileLike() ) {
		return { mode: 'LITE', mobile: true, reasons: [ 'thiết bị di động / màn hình hẹp' ] };
	}
	const cores = navigator.hardwareConcurrency || 4;
	const mem = navigator.deviceMemory || 8; // Safari/Firefox không có deviceMemory: coi như đủ, governor sẽ hạ nếu chậm.
	reasons.push( `heuristics: ${ cores } luồng, ${ mem } GB` );
	return { mode: cores >= 8 && mem >= 8 ? 'FULL' : 'LITE', mobile: false, reasons };
}
