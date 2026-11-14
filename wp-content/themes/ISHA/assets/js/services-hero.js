/**
 * Hero trang danh sách dịch vụ (template-parts/services-hero.php, assets/css/services-hero.css). ES5, không thư viện.
 *
 * Video nền: chọn bản điện thoại hoặc màn hình rộng theo (max-width: 767px), chỉ gán src sau sự kiện load (không tranh băng
 * thông với ảnh LCP), hiện dần lên trên ảnh khi đã phát; tạm dừng khi hero ra khỏi màn hình hoặc tab bị ẩn. Giảm chuyển động
 * hoặc Save-Data: không phát video (thiếu ảnh nền thì vẫn tải khung đầu của video làm hình tĩnh).
 *
 * Bánh xe nan quạt: JS tiếp quản vòng quay CSS (.is-driven). Vào cảnh bánh xe xoè tới vị trí rồi tăng tốc êm lên tốc độ nền;
 * rê chuột vào một thẻ thì chậm dần và dừng, rời ra thì quay tiếp; thẻ nhận focus bàn phím thì bánh xe quay để thẻ lên đỉnh.
 * Không còn gì chuyển động (dừng hẳn, ra khỏi màn hình, tab ẩn) thì ngừng vòng requestAnimationFrame.
 */
( function () {
	'use strict';

	var win = window, doc = document;
	var root = doc.querySelector( '[data-isha-sh]' );
	if ( ! root || ! win.requestAnimationFrame || ! win.matchMedia ) {
		return;
	}

	var BASE = -360 / 120;  // tốc độ nền (độ/giây): một vòng trong 120 giây, âm = ngược chiều kim đồng hồ
	var TAU = 0.5;          // hằng số thời gian làm mềm tốc độ (giây)
	var INTRO = 32;         // góc xuất phát của màn xoè (độ); trái dấu với BASE để màn xoè đi cùng chiều quay
	var INTRO_MS = 1900;
	var SEEK_MS = 900;      // thời gian quay thẻ đang focus lên đỉnh
	var TOP = 90;           // góc của đỉnh trong hệ toạ độ thẻ (0 = nằm ngang bên trái tâm, xoay chiều kim đồng hồ)
	var FIT_MIN = 0.8;      // tỉ lệ nhỏ nhất khi thu bánh xe cho vừa khoảng trống dưới phần chữ
	var FIT_GAP = 28;       // khoảng hở tối thiểu (px) giữa đầu thẻ ở đỉnh và mép dưới phần chữ

	var mqReduced = win.matchMedia( '(prefers-reduced-motion: reduce)' );
	var mqMobile = win.matchMedia( '(max-width: 767px)' );
	var frame = root.querySelector( '.isha-sh__frame' );
	var head = root.querySelector( '.isha-sh__head' );
	var video = root.querySelector( '[data-isha-sh-video]' );
	var hasPicture = !! root.querySelector( '.isha-sh__img' );
	var wheel = root.querySelector( '[data-isha-sh-wheel]' );
	var cards = wheel ? wheel.querySelectorAll( '[data-isha-sh-card]' ) : [];
	var n = cards.length;

	var visible = true, loaded = false;
	var angle = 0, speed = 0, hovering = false, focused = false, seek = null;
	var raf = 0, last = 0, videoSrc = '', pointerAt = 0, fit = 1;

	// Hàm rỗng (nuốt lỗi play() bị trình duyệt chặn).
	function noop() {}

	// Người dùng có bật giảm chuyển động không.
	function reduced() {
		return !! mqReduced.matches;
	}

	// Gắn hàm nghe thay đổi của một media query (hỗ trợ cả API cũ của Safari < 14).
	function listen( mq, fn ) {
		if ( mq.addEventListener ) {
			mq.addEventListener( 'change', fn );
		} else if ( mq.addListener ) {
			mq.addListener( fn );
		}
	}

	// Đường cong ease-out bậc ba cho các lần quay có đích.
	function easeOut( t ) {
		t = 1 - t;
		return 1 - t * t * t;
	}

	// Đưa góc về khoảng (-180, 180].
	function wrap( a ) {
		a = a % 360;
		if ( a > 180 ) {
			a -= 360;
		} else if ( a <= -180 ) {
			a += 360;
		}
		return a;
	}

	/* --- Video nền ------------------------------------------------------- */

	// Người dùng bật tiết kiệm dữ liệu không.
	function saveData() {
		var c = navigator.connection;
		return !! ( c && c.saveData );
	}

	// Có được phát video không (đủ điều kiện chuyển động và dữ liệu).
	function canPlayVideo() {
		return ! reduced() && ! saveData();
	}

	// Nguồn video hợp với khổ màn hình hiện tại.
	function videoSource() {
		var mobile = mqMobile.matches ? video.getAttribute( 'data-src-mobile' ) : '';
		return mobile || video.getAttribute( 'data-src-desktop' ) || '';
	}

	// Chọn nguồn, tải và phát (hoặc chỉ tải khung đầu khi không được phát mà hero không có ảnh nền).
	function updateVideo() {
		if ( ! video || ! loaded ) {
			return;
		}
		var play = canPlayVideo();
		if ( ! play && hasPicture ) {
			video.pause();
			root.classList.remove( 'is-video' );
			return;
		}
		var src = videoSource();
		if ( src && src !== videoSrc ) {
			videoSrc = src;
			root.classList.remove( 'is-video' );
			video.preload = play ? 'auto' : 'metadata';
			video.src = src;
			video.load();
		}
		if ( play ) {
			playVideo();
		} else {
			video.pause();
		}
	}

	// Phát video nếu hero đang trong khung nhìn và tab đang hiện.
	function playVideo() {
		if ( ! video || ! videoSrc || ! visible || doc.hidden || ! canPlayVideo() ) {
			return;
		}
		var p = video.play();
		if ( p && p.catch ) {
			p.catch( noop );
		}
	}

	/* --- Bánh xe nan quạt ------------------------------------------------ */

	// Ghi góc và tỉ lệ hiện tại lên bánh xe.
	function render() {
		wheel.style.transform = 'rotate(' + angle.toFixed( 3 ) + 'deg)' + ( fit < 1 ? ' scale(' + fit.toFixed( 4 ) + ')' : '' );
	}

	// Đo lại tỉ lệ thu nhỏ để thẻ dài nhất khi đứng ở đỉnh không chạm phần chữ (tiêu đề dài, mô tả nhiều dòng, màn hình thấp).
	function measure() {
		if ( ! wheel || ! frame || ! head ) {
			return;
		}
		var reach = 0, i, card, r;
		for ( i = 0; i < n; i++ ) {
			card = cards[ i ];
			r = parseFloat( win.getComputedStyle( card ).right ) || 0;
			reach = Math.max( reach, card.offsetWidth + r );
		}
		var room = frame.clientHeight - ( head.offsetTop + head.offsetHeight ) - FIT_GAP;
		var next = reach > 0 ? Math.max( FIT_MIN, Math.min( 1, room / reach ) ) : 1;
		if ( Math.abs( next - fit ) > 0.002 ) {
			fit = next;
			render();
		}
	}

	// Tốc độ đích lúc này: dừng khi rê chuột, khi có thẻ đang focus hoặc khi giảm chuyển động.
	function targetSpeed() {
		return ( hovering || focused || reduced() ) ? 0 : BASE;
	}

	// Một khung hình: chạy lần quay có đích nếu có, nếu không thì làm mềm tốc độ về đích rồi cộng dồn góc.
	function tick( now ) {
		raf = 0;
		var dt = Math.min( ( now - last ) / 1000, 0.1 );
		last = now;

		if ( seek ) {
			var p = seek.dur > 0 ? Math.min( ( now - seek.t0 ) / seek.dur, 1 ) : 1;
			angle = seek.from + ( seek.to - seek.from ) * easeOut( p );
			if ( p >= 1 ) {
				seek = null;
				speed = 0;
				angle = angle % 360;
			}
		} else {
			var target = targetSpeed();
			speed += ( target - speed ) * ( 1 - Math.exp( -dt / TAU ) );
			if ( 0 === target && Math.abs( speed ) < 0.01 ) {
				speed = 0;
			}
			angle = ( angle + speed * dt ) % 360;
		}
		render();

		if ( seek || speed !== 0 || targetSpeed() !== 0 ) {
			start();
		}
	}

	// Chạy vòng khung hình nếu hero đang hiện và chưa chạy.
	function start() {
		if ( raf || ! wheel || ! visible || doc.hidden ) {
			return;
		}
		raf = win.requestAnimationFrame( function ( now ) {
			if ( ! last || now - last > 100 ) {
				last = now; // vừa tiếp tục sau khi nghỉ: không nhảy góc
			}
			tick( now );
		} );
	}

	// Dừng vòng khung hình.
	function stop() {
		if ( raf ) {
			win.cancelAnimationFrame( raf );
			raf = 0;
		}
		last = 0;
	}

	// Quay bánh xe tới góc đích (độ) theo đường ngắn nhất; giảm chuyển động thì nhảy thẳng.
	function seekTo( to, dur ) {
		var from = angle;
		to = from + wrap( to - from );
		if ( reduced() || ! dur ) {
			seek = null;
			angle = to % 360;
			speed = 0;
			render();
			return;
		}
		seek = { from: from, to: to, t0: win.performance && win.performance.now ? win.performance.now() : Date.now(), dur: dur };
		start();
	}

	// Góc gốc của thẻ thứ i (trùng công thức --a trong CSS).
	function cardAngle( i ) {
		return 180 / n + i * 360 / n;
	}

	// Gắn sự kiện rê chuột và focus cho từng thẻ.
	function bindCards() {
		var i;
		var mark = function () {
			pointerAt = Date.now();
		};
		wheel.addEventListener( 'mousedown', mark );
		wheel.addEventListener( 'touchstart', mark, { passive: true } );
		for ( i = 0; i < n; i++ ) {
			( function ( card, index ) {
				card.addEventListener( 'mouseenter', function () {
					hovering = true;
					start();
				} );
				card.addEventListener( 'mouseleave', function () {
					hovering = false;
					start();
				} );
				card.addEventListener( 'focus', function () {
					focused = true;
					// Focus do bấm chuột/chạm: không quay (thẻ sẽ trượt khỏi con trỏ giữa mousedown và mouseup, mất cú bấm).
					if ( Date.now() - pointerAt < 800 ) {
						start();
						return;
					}
					// Khung là overflow:hidden; trình duyệt có thể cuộn khung để lộ thẻ nằm dưới mép: trả về 0.
					if ( frame ) {
						frame.scrollTop = 0;
						frame.scrollLeft = 0;
					}
					seekTo( TOP - cardAngle( index ), SEEK_MS );
				} );
				card.addEventListener( 'blur', function () {
					focused = false;
					start();
				} );
			}( cards[ i ], i ) );
		}
	}

	/* --- Khởi động -------------------------------------------------------- */

	// Theo dõi hero vào/ra khung nhìn: ra thì dừng bánh xe và video, vào thì chạy lại.
	function observe() {
		if ( ! win.IntersectionObserver ) {
			return;
		}
		new win.IntersectionObserver( function ( entries ) {
			visible = entries[ entries.length - 1 ].isIntersecting;
			if ( visible ) {
				start();
				playVideo();
			} else {
				stop();
				if ( video ) {
					video.pause();
				}
			}
		} ).observe( root );
	}

	// Tab ẩn/hiện: dừng hoặc chạy lại.
	function onVisibility() {
		if ( doc.hidden ) {
			stop();
			if ( video ) {
				video.pause();
			}
		} else {
			start();
			playVideo();
		}
	}

	// Đổi chế độ giảm chuyển động: bỏ lần quay đang chạy, cập nhật video, để bánh xe tự dừng hoặc quay tiếp.
	function onReducedChange() {
		if ( reduced() ) {
			seek = null;
			speed = 0;
		}
		updateVideo();
		start();
	}

	// Khởi động bánh xe: tiếp quản vòng quay CSS, chạy màn xoè (trừ khi giảm chuyển động).
	function initWheel() {
		if ( ! wheel || ! n ) {
			return;
		}
		wheel.classList.add( 'is-driven' );
		bindCards();
		measure();
		// Đo lại khi khung đổi cỡ và khi font tự host tải xong (độ dài thẻ và chiều cao tiêu đề đổi theo font).
		if ( win.ResizeObserver ) {
			new win.ResizeObserver( measure ).observe( frame );
		} else {
			win.addEventListener( 'resize', measure );
		}
		if ( doc.fonts && doc.fonts.ready ) {
			doc.fonts.ready.then( measure );
		}
		// Trang nhóm dịch vụ (data-start = vị trí thẻ của nhóm đang xem): màn xoè dừng khi thẻ đó ở đỉnh.
		var first = parseInt( wheel.getAttribute( 'data-start' ), 10 );
		var home = ( first >= 0 && first < n ) ? wrap( TOP - cardAngle( first ) ) : 0;
		if ( reduced() ) {
			angle = home;
			render();
			return;
		}
		angle = home + INTRO;
		render();
		seekTo( home, INTRO_MS );
	}

	// Video: tải sau sự kiện load của trang.
	function initVideo() {
		if ( ! video ) {
			return;
		}
		video.muted = true;
		video.addEventListener( 'playing', function () {
			root.classList.add( 'is-video' );
		} );
		video.addEventListener( 'loadeddata', function () {
			if ( ! canPlayVideo() && ! hasPicture ) {
				root.classList.add( 'is-video' ); // khung đầu làm hình tĩnh
			}
		} );
		listen( mqMobile, updateVideo );

		var go = function () {
			loaded = true;
			updateVideo();
		};
		if ( 'complete' === doc.readyState ) {
			go();
		} else {
			win.addEventListener( 'load', go );
		}
	}

	initWheel();
	initVideo();
	observe();
	listen( mqReduced, onReducedChange );
	doc.addEventListener( 'visibilitychange', onVisibility );
}() );
