/**
 * Màn chờ "Cánh cửa phòng trị liệu" (inc/preloader.php, preloader.css). JS thuần ES5, không phụ thuộc.
 *
 * Cổng trong <head> đã quyết định có hiện không (class `isha-preloader-on` trên <html>). Ở đây chỉ lo nhịp 2 và 3:
 *   - Mở cửa (thêm `.is-opening`) khi trang THẬT SỰ sẵn sàng (window.load: CSS, JS, ảnh; cộng font) VÀ đã qua sàn MIN ms kể từ lúc
 *     màn chờ hiện (mốc `ishaPreT` do cổng <head> ghi). Không dùng một hẹn giờ cố định: mạng nhanh thì không bắt khách chờ thừa, mạng chậm thì không mở cửa khi trang chưa xong.
 *   - Chốt chống kẹt: quá MAX ms tính từ lúc điều hướng thì mở cửa dù chưa xong.
 *   - Sau transitionend của cánh trái (có dự phòng bằng hẹn giờ): gỡ khoá cuộn, xoá lớp phủ khỏi DOM.
 * Thời lượng cửa (DELAY, SLIDE) phải khớp preloader.css.
 */
( function ( win, doc ) {
	'use strict';

	var html = doc.documentElement,
		root = doc.getElementById( 'isha-preloader' ),
		SEEN = 'isha_preloader_seen',
		MIN = 1500,       // ms từ lúc lớp phủ hiện tới lúc mở cửa sớm nhất: sàn, không phải thời gian chờ cố định.
		MAX = 4000,       // ms từ lúc điều hướng: buộc mở cửa dù load chậm (không sớm hơn sàn MIN).
		DELAY = 180,      // ms: cửa bắt đầu trượt sau khi emblem tan (preloader.css).
		SLIDE = 780,      // ms: thời lượng cửa trượt (preloader.css).
		startedAt = win.ishaPreT > 0 ? win.ishaPreT : now(),   // mốc lớp phủ bắt đầu hiện (cổng <head> ghi); thiếu thì lấy lúc script chạy.
		pending = 2,      // số tín hiệu sẵn sàng còn chờ: load + font.
		opened = false,
		finished = false,
		minTimer = 0,
		maxTimer = 0,
		fallbackTimer = 0,
		door = null;

	function now() {
		return win.performance && win.performance.now ? win.performance.now() : 0;
	}

	function removeOverlay() {
		if ( root && root.parentNode ) {
			root.parentNode.removeChild( root );
		}
	}

	if ( ! root ) {
		return;
	}

	// Đã tiếp quản: gỡ chốt 5 giây của cổng (từ đây chốt MAX bên dưới lo).
	win.clearTimeout( win.ishaPreGuard );

	// Cổng không bật (phiên đã xem, giảm chuyển động, #neo...): xoá lớp phủ, không làm gì thêm.
	if ( ! html.classList.contains( 'isha-preloader-on' ) ) {
		removeOverlay();
		return;
	}

	try {
		win.sessionStorage.setItem( SEEN, '1' );
	} catch ( err ) {
		// sessionStorage bị chặn: cổng đã không bật màn chờ, tới được đây là hiếm; cứ chạy hết lượt này.
	}

	// Nhịp 3: gỡ khoá cuộn, xoá lớp phủ.
	function finish() {
		if ( finished ) {
			return;
		}
		finished = true;
		win.clearTimeout( fallbackTimer );
		html.classList.remove( 'isha-preloader-on' );
		removeOverlay();
	}

	// Nhịp 2: mở cửa.
	function open() {
		if ( opened ) {
			return;
		}
		opened = true;
		win.clearTimeout( minTimer );
		win.clearTimeout( maxTimer );

		door = root.querySelector( '.isha-preloader__door--left' );
		if ( door ) {
			door.addEventListener( 'transitionend', function ( e ) {
				if ( e.target === door && 'transform' === e.propertyName ) {
					finish();
				}
			} );
		}
		root.classList.add( 'is-opening' );
		// transitionend không bắn nếu tab ẩn hoặc CSS không áp: dự phòng bằng hẹn giờ.
		fallbackTimer = win.setTimeout( finish, DELAY + SLIDE + 250 );
	}

	// Trang sẵn sàng: mở cửa, nhưng không sớm hơn MIN kể từ lúc lớp phủ hiện.
	function onReady() {
		if ( opened || minTimer ) {
			return;
		}
		minTimer = win.setTimeout( open, Math.max( 0, startedAt + MIN - now() ) );
	}

	// Hai tín hiệu thật: window.load (CSS, JS, ảnh trong trang) và font đã sẵn sàng (chữ không đổi font sau khi cửa mở).
	function oneReady() {
		if ( --pending <= 0 ) {
			onReady();
		}
	}

	// Chốt chống kẹt: tối đa MAX ms từ lúc điều hướng, nhưng không sớm hơn sàn MIN.
	maxTimer = win.setTimeout( open, Math.max( 0, Math.max( startedAt + MIN, MAX ) - now() ) );

	if ( 'complete' === doc.readyState ) {
		oneReady();
	} else {
		win.addEventListener( 'load', oneReady );
	}
	if ( doc.fonts && doc.fonts.ready && doc.fonts.ready.then ) {
		doc.fonts.ready.then( oneReady, oneReady );
	} else {
		oneReady();
	}
}( window, document ) );
