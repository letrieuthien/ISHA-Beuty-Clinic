/**
 * Nút "Lên đầu trang" ISHA: scroll-top.js (docs/home-architecture.md mục 13). IIFE ES5; ISHA.motion là phụ thuộc mềm.
 * Hiện nút khi đã cuộn xuống quá một đoạn; bấm thì cuộn mượt về đầu trang rồi đưa focus về liên kết đầu của header. Có dock (trang chủ) thì đứng trên dock.
 */
( function () {
	'use strict';

	var win = window, doc = document, html = doc.documentElement;
	var btn = doc.querySelector( '[data-isha-top]' ), on = false, limit = 600, queued = false;

	if ( ! btn ) {
		return;
	}

	// Giảm chuyển động đang bật?
	function reduced() {
		return !! ( win.matchMedia && win.matchMedia( '(prefers-reduced-motion: reduce)' ).matches );
	}

	// Đo lại ngưỡng hiện nút: 600px hoặc 3/4 chiều cao màn hình, lấy số lớn hơn.
	function measure() {
		limit = Math.max( 600, 0.75 * ( win.innerHeight || html.clientHeight || 0 ) );
	}

	// Hiện hoặc ẩn nút theo vị trí cuộn.
	function update() {
		var show = ( win.pageYOffset || html.scrollTop || 0 ) > limit;

		queued = false;
		if ( show !== on ) {
			on = show;
			btn.classList.toggle( 'is-visible', on );
		}
	}

	// Xếp việc cập nhật vào khung hình kế (sự kiện cuộn bắn dày).
	function schedule() {
		if ( ! queued ) {
			queued = true;
			win.requestAnimationFrame( update );
		}
	}

	// Có dock liên hệ đang hiện (trang chủ): nâng nút lên ngay trên dock, cách 8px (bằng khoảng cách giữa các nút dock); dock ẩn thì về mép như thường.
	function lift() {
		var dock = doc.querySelector( '[data-isha-dock]' ), cs = dock && win.getComputedStyle( dock ), v = '';
		// Dock do JS bật tắt bằng .is-visible khi html có isha-motion-on (và chưa isha-motion-fail); ngoài ra luôn hiện.
		var gated = html.classList.contains( 'isha-motion-on' ) && ! html.classList.contains( 'isha-motion-fail' );

		if ( cs && 'none' !== cs.display && ( ! gated || dock.classList.contains( 'is-visible' ) ) ) {
			v = ( ( isNaN( parseFloat( cs.bottom ) ) ? 16 : parseFloat( cs.bottom ) ) + dock.offsetHeight + 8 ) + 'px';
		}
		btn.style.setProperty( '--isha-top-bottom', v );
	}

	// Đưa focus về liên kết đầu của header, không cuộn thêm.
	function focusTop() {
		var a = doc.querySelector( '#masthead a' );

		if ( a && a.focus ) {
			try {
				a.focus( { preventScroll: true } );
			} catch ( err ) {
				a.focus();
			}
		}
	}

	// Cuộn về đầu trang: lõi chuyển động nếu có (mượt, huỷ được khi người dùng cuộn tay), không thì cuộn mượt của trình duyệt.
	function toTop() {
		var m = win.ISHA && win.ISHA.motion;

		if ( m && m.started && 'function' === typeof m.scrollTo && ! reduced() ) {
			m.scrollTo( 0, { done: focusTop } );
			return;
		}
		try {
			win.scrollTo( { top: 0, behavior: reduced() ? 'auto' : 'smooth' } );
		} catch ( err ) {
			win.scrollTo( 0, 0 );
		}
		focusTop();
	}

	btn.addEventListener( 'click', toTop );
	win.addEventListener( 'scroll', schedule, { passive: true } );
	win.addEventListener( 'resize', function () {
		measure();
		lift();
		schedule();
	} );
	win.addEventListener( 'pageshow', schedule );
	measure();
	update();
	lift();
	if ( win.MutationObserver && doc.querySelector( '[data-isha-dock]' ) ) {
		new win.MutationObserver( lift ).observe( doc.querySelector( '[data-isha-dock]' ), { attributes: true, attributeFilter: [ 'class' ] } );
		new win.MutationObserver( lift ).observe( html, { attributes: true, attributeFilter: [ 'class' ] } );
	}
}() );
