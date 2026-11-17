/**
 * Trang Giới thiệu ISHA: about.js (docs/home-architecture.md mục 16). IIFE ES5; ISHA.motion là phụ thuộc mềm.
 * Việc: video nền hero (khi có; không phát khi giảm chuyển động hoặc tiết kiệm dữ liệu), section hiện dần khi vào khung nhìn, ảnh phóng nhẹ theo cuộn.
 */
( function () {
	'use strict';

	var win = window, doc = document;
	var ISHA = win.ISHA = win.ISHA || {};

	// NodeList thành mảng.
	function all( root, sel ) {
		return Array.prototype.slice.call( root.querySelectorAll( sel ) );
	}

	// Báo lỗi của một khối lên console.
	function report( name, err ) {
		if ( win.console && win.console.error ) {
			win.console.error( '[ISHA.about] ' + name, err );
		}
	}

	// Giảm chuyển động đang bật?
	function reduced() {
		return !! ( win.matchMedia && win.matchMedia( '(prefers-reduced-motion: reduce)' ).matches );
	}

	// Gọi fn một lần khi el vào khung nhìn (lõi chuyển động nếu có, không thì IntersectionObserver; không có cả hai thì gọi ngay).
	function once( el, fn, motion ) {
		var io;

		if ( motion ) {
			motion.inView( el, fn, { once: true, threshold: 0.18 } );
			return;
		}
		if ( ! win.IntersectionObserver ) {
			fn();
			return;
		}
		io = new win.IntersectionObserver( function ( entries ) {
			entries.forEach( function ( en ) {
				if ( en.isIntersecting ) {
					io.disconnect();
					fn();
				}
			} );
		}, { threshold: 0.18 } );
		io.observe( el );
	}

	// Video nền hero: bản dọc cho điện thoại dọc, mờ dần vào khi phát; tab ẩn thì dừng. Giảm chuyển động hoặc tiết kiệm dữ liệu: giữ ảnh.
	function heroVideo( root ) {
		var video = root.querySelector( '.isha-about-hero__video' ), conn = win.navigator.connection, portrait, src;

		// Phát video, bỏ qua lỗi khi trình duyệt chặn tự phát (ảnh vẫn hiện).
		function play() {
			var pr;
			try {
				pr = video.play();
			} catch ( err ) {
				pr = null;
			}
			if ( pr && pr.catch ) {
				pr.catch( function () {} );
			}
		}

		if ( ! video || reduced() || ( conn && conn.saveData ) ) {
			return;
		}
		portrait = !! ( win.matchMedia && win.matchMedia( '(max-width: 900px) and (orientation: portrait)' ).matches );
		src = video.getAttribute( portrait ? 'data-src-mobile' : 'data-src' ) || video.getAttribute( 'data-src' );
		if ( ! src ) {
			return;
		}
		video.muted = true;
		video.hidden = false;
		video.addEventListener( 'playing', function () {
			video.classList.add( 'is-playing' );
		} );
		video.setAttribute( 'src', src );
		play();
		doc.addEventListener( 'visibilitychange', function () {
			if ( doc.hidden ) {
				video.pause();
			} else {
				play();
			}
		} );
	}

	// Section chưa vào khung nhìn lúc tải thì ẩn (.is-pre) rồi hiện dần một lần khi vào; section đã thấy sẵn giữ nguyên.
	function reveal( root, motion ) {
		var vh = win.innerHeight || doc.documentElement.clientHeight || 0;

		if ( reduced() ) {
			return;
		}
		all( root, '.isha-about-sec' ).forEach( function ( sec ) {
			if ( sec.getBoundingClientRect().top < vh * 0.85 ) {
				return;
			}
			sec.classList.add( 'is-pre' );
			once( sec, function () {
				sec.classList.remove( 'is-pre' );
			}, motion );
		} );
	}

	// Ảnh của section phóng 1,08 về 1 theo cuộn (như ảnh chân dung của section Founder); chế độ gọn hoặc giảm chuyển động thì đứng yên.
	function dolly( root, motion ) {
		var inOut = motion.ease && 'function' === typeof motion.ease.inOut ? motion.ease.inOut : function ( t ) {
			return t;
		};

		all( root, '.isha-about-sec' ).forEach( function ( sec ) {
			var img = sec.querySelector( '.isha-about-sec__frame .isha-img' );

			if ( ! img ) {
				return;
			}
			motion.scene( sec, {
				update: function ( p, state ) {
					var s = state.compact || state.reduced ? 1 : 1 + 0.08 * ( 1 - inOut( motion.seg( p, 0.05, 0.55 ) ) );

					img.style.transform = s > 1.0005 ? 'scale(' + s.toFixed( 4 ) + ')' : '';
				}
			} );
		} );
	}

	// Khởi chạy khi DOM sẵn sàng; lõi chuyển động không có thì vẫn có video và hiện dần (IntersectionObserver).
	function boot() {
		var root = doc.querySelector( '.isha-about' ), motion = ISHA.motion && ISHA.motion.started ? ISHA.motion : null;

		if ( ! root ) {
			return;
		}
		try {
			heroVideo( root );
		} catch ( err ) {
			report( 'video', err );
		}
		try {
			reveal( root, motion );
			if ( motion ) {
				dolly( root, motion );
			}
		} catch ( err2 ) {
			report( 'motion', err2 );
		}
	}

	if ( 'loading' === doc.readyState ) {
		doc.addEventListener( 'DOMContentLoaded', boot );
	} else {
		boot();
	}
}() );
