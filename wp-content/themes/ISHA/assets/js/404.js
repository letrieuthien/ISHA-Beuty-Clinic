/**
 * Trang 404: ảnh số 404 (.isha-404__code, 404.php) nghiêng nhẹ theo hướng con trỏ.
 * Chỉ khi có chuột thật (hover + pointer fine) và không giảm chuyển động. Cạnh ảnh gần con trỏ hơi tiến về người xem,
 * tối đa 7° quanh trục Y và 4° quanh trục X; hướng tính theo vị trí con trỏ trên khung nhìn. Rời khỏi trang thì ảnh về phẳng.
 * Chỉ đổi transform trên một phần tử; vòng rAF tự dừng khi đã lặng nên không tốn khung hình khi không di chuột.
 */
( function () {
	'use strict';

	var win = window, doc = document;
	var section = doc.querySelector( '.isha-404' );
	var tilt = section ? section.querySelector( '.isha-404__code' ) : null;

	if ( ! tilt || ! win.requestAnimationFrame || ! win.matchMedia ) {
		return;
	}

	var MAX_Y = 7;      // độ, quanh trục Y (trái - phải)
	var MAX_X = 4;      // độ, quanh trục X (trên - dưới)
	var TAU = 0.3;      // giây, hằng số làm mượt
	var fine = win.matchMedia( '(hover: hover) and (pointer: fine)' );
	var calm = win.matchMedia( '(prefers-reduced-motion: reduce)' );
	var tx = 0, ty = 0; // góc đích
	var x = 0, y = 0;   // góc hiện tại
	var raf = 0, prev = 0;

	// Kẹp v vào [-1, 1].
	function clamp( v ) {
		return v < -1 ? -1 : ( v > 1 ? 1 : v );
	}

	// Đặt transform theo góc hiện tại.
	function paint() {
		tilt.style.transform = 'perspective(1400px) rotateX(' + y.toFixed( 2 ) + 'deg) rotateY(' + x.toFixed( 2 ) + 'deg)';
	}

	// Một khung: tiến góc hiện tại về góc đích; dừng khi đã tới nơi.
	function frame( now ) {
		var dt = prev ? Math.min( ( now - prev ) / 1000, 0.1 ) : 1 / 60;
		var f = 1 - Math.exp( -dt / TAU );

		prev = now;
		x += ( tx - x ) * f;
		y += ( ty - y ) * f;

		if ( Math.abs( tx - x ) < 0.01 && Math.abs( ty - y ) < 0.01 ) {
			x = tx;
			y = ty;
			paint();
			raf = 0;
			prev = 0;
			return;
		}
		paint();
		raf = win.requestAnimationFrame( frame );
	}

	// Đánh thức vòng rAF nếu đang nghỉ.
	function kick() {
		if ( ! raf ) {
			raf = win.requestAnimationFrame( frame );
		}
	}

	// Đặt góc đích theo con trỏ. Cạnh gần con trỏ tiến về người xem: rotateY âm khi con trỏ bên phải, rotateX dương khi con trỏ bên dưới.
	function aim( e ) {
		if ( ! fine.matches || calm.matches || ( e.pointerType && 'mouse' !== e.pointerType ) ) {
			return;
		}
		var nx = clamp( ( e.clientX / win.innerWidth ) * 2 - 1 );
		var ny = clamp( ( e.clientY / win.innerHeight ) * 2 - 1 );

		tx = -nx * MAX_Y;
		ty = ny * MAX_X;
		kick();
	}

	// Rời khỏi trang 404: trả ảnh về phẳng.
	function relax() {
		tx = 0;
		ty = 0;
		kick();
	}

	section.addEventListener( 'pointermove', aim, { passive: true } );
	section.addEventListener( 'pointerleave', relax );
}() );
