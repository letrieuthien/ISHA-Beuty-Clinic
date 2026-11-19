/**
 * Trang chủ điện ảnh ISHA: home.js (docs/home-architecture.md mục 5.9, 8 đến 11). IIFE ES5; ISHA.motion là phụ thuộc mềm.
 * Vỏ: rows, header, dock, sky. Cảnh: register( id, function ( root, ctx ) {} ). Mỗi khối nằm giữa marker >>> tên và <<< tên.
 */
( function () {
	'use strict';

	var win = window, doc = document, html = doc.documentElement;
	var ISHA = win.ISHA = win.ISHA || {};
	var home = ISHA.home = ISHA.home || {};

	if ( 'function' === typeof home.register ) {   // nạp hai lần: giữ bản đầu
		return;
	}

	var cfg = ISHA.homeConfig && 'object' === typeof ISHA.homeConfig ? ISHA.homeConfig : {};
	var strings = cfg.strings && 'object' === typeof cfg.strings ? cfg.strings : {};
	var FALLBACK = {
		prev: 'Trước',
		next: 'Sau',
		item: 'Khung %d',
		rowLabel: 'Danh sách cuộn ngang',
		carousel: 'băng chuyền',
		slide: 'trang chiếu',
		slideOf: '%1$d trên %2$d'
	};
	var mq = {
		reduced: win.matchMedia ? win.matchMedia( '(prefers-reduced-motion: reduce)' ) : null,
		compact: win.matchMedia ? win.matchMedia( '(max-width: 900px), (max-height: 560px)' ) : null
	};
	var view = { y: 0, vh: 0, vw: 0, layout: 0 };
	var jobs = [], queue = [], layoutFns = [], done = [];
	var queued = false, wantAll = false, wired = false, scenesOn = false, polling = false;

	home.modules = home.modules || {};

	// Hàm rỗng.
	function noop() {}

	// Báo lỗi của một module lên console.
	function report( name, err ) {
		if ( win.console && win.console.error ) {
			win.console.error( '[ISHA.home] ' + name, err );
		}
	}

	// Chuỗi giao diện từ cấu hình PHP, có dự phòng.
	function str( key ) {
		var v = strings[ key ];
		return 'string' === typeof v && '' !== v ? v : ( FALLBACK[ key ] || '' );
	}

	// Thay %d trong chuỗi bằng số n.
	function fmt( s, n ) {
		return String( s ).replace( '%d', n );
	}

	// Cổng 6 giây đã chuyển sang chế độ tĩnh?
	function failed() {
		return html.classList.contains( 'isha-motion-fail' );
	}

	// Chế độ phim đang bật (JS chạy, chưa lỗi)?
	function enhanced() {
		return html.classList.contains( 'isha-motion-on' ) && ! failed();
	}

	// Bọc tiện ích của lõi chuyển động, có hàm dự phòng.
	function util( key, fallback ) {
		return function () {
			var m = ISHA.motion;
			return ( m && 'function' === typeof m[ key ] ? m[ key ] : fallback ).apply( null, arguments );
		};
	}
	var clamp = util( 'clamp', function ( v, a, b ) {
		return v < a ? a : ( v > b ? b : v );
	} );
	var lerp = util( 'lerp', function ( a, b, t ) {
		return a + ( b - a ) * t;
	} );
	var reduced = util( 'reduced', function () {
		return !! ( mq.reduced && mq.reduced.matches );
	} );
	var compact = util( 'compact', function () {
		return !! ( mq.compact && mq.compact.matches );
	} );

	// Đoạn của v trong [a, b], chuẩn hoá 0..1.
	function seg( v, a, b ) {
		return clamp( ( v - a ) / ( b - a ), 0, 1 );
	}

	// NodeList thành mảng.
	function all( root, sel ) {
		return Array.prototype.slice.call( root.querySelectorAll( sel ) );
	}

	// Easing của lõi theo tên, dự phòng tuyến tính.
	function ease( name ) {
		var m = ISHA.motion;
		return m && m.ease && 'function' === typeof m.ease[ name ] ? m.ease[ name ] : function ( t ) {
			return t;
		};
	}

	// Tự chạy: gọi step() mỗi 5 giây khi root đang trong khung nhìn; dừng khi tab ẩn, giảm chuyển động hoặc focus bàn phím; thao tác của người dùng (bấm, thả chuột, vuốt)
	// thì đếm lại từ đầu. cb (tuỳ chọn): ms chu kỳ (mặc định 5000), threshold của inView, seen( v ) khi vào/ra khung nhìn, state( on ) khi bật/tắt chạy. Trả về { restart }.
	function auto( root, step, cb ) {
		var MS = ( cb && cb.ms > 0 ) ? cb.ms : 5000, timer = 0, seen = false, kb = false, on = false;

		cb = cb || {};
		// Đặt lại đồng hồ nếu đang chạy.
		function restart() {
			if ( timer ) {
				win.clearInterval( timer );
				timer = win.setInterval( step, MS );
			}
		}

		// Bật hoặc tắt đồng hồ theo trạng thái.
		function sync() {
			var want = seen && ! kb && ! doc.hidden && ! reduced();

			if ( want !== on ) {
				on = want;
				if ( cb.state ) {
					cb.state( on );
				}
			}
			if ( on && ! timer ) {
				timer = win.setInterval( step, MS );
			} else if ( ! on && timer ) {
				win.clearInterval( timer );
				timer = 0;
			}
		}

		ISHA.motion.inView( root, function ( v ) {
			seen = v;
			if ( cb.seen ) {
				cb.seen( v );
			}
			sync();
		}, { threshold: cb.threshold || 0.3 } );
		root.addEventListener( 'focusin', function ( e ) {
			try {
				kb = e.target.matches( ':focus-visible' );
			} catch ( err ) {
				kb = true;
			}
			sync();
		} );
		root.addEventListener( 'focusout', function () {
			kb = false;
			sync();
		} );
		[ 'click', 'pointerup' ].forEach( function ( t ) {
			root.addEventListener( t, restart );
		} );
		doc.addEventListener( 'visibilitychange', sync );
		onMode( 'reduced', sync );
		return { restart: restart };
	}

	// Kéo bằng chuột hoặc vuốt: gọi go( +1 | -1 ) khi kéo ngang quá 40px (nhiều hơn 1,5 lần kéo dọc); kéo xong thì nuốt cú bấm kế để không mở liên kết.
	function drag( el, go ) {
		var x0 = null, y0 = 0, swallow = false;

		el.addEventListener( 'pointerdown', function ( e ) {
			if ( 'mouse' !== e.pointerType || 0 === e.button ) {
				x0 = e.clientX;
				y0 = e.clientY;
				swallow = false;
			}
		} );
		el.addEventListener( 'pointerup', function ( e ) {
			var dx = e.clientX - x0, dy = e.clientY - y0;

			if ( null !== x0 && Math.abs( dx ) > 40 && Math.abs( dx ) > 1.5 * Math.abs( dy ) ) {
				swallow = true;
				go( dx < 0 ? 1 : -1 );
			}
			x0 = null;
		} );
		el.addEventListener( 'pointercancel', function () {
			x0 = null;
		} );
		el.addEventListener( 'click', function ( e ) {
			if ( swallow ) {
				swallow = false;
				e.preventDefault();
				e.stopPropagation();
			}
		}, true );
		el.addEventListener( 'dragstart', function ( e ) {
			e.preventDefault();
		} );
	}

	// Băng chuyền dùng chung (APG): animationend của chấm tiến trình CSS là đồng hồ. Chạy liên tục và lặp, không nút tạm dừng, không dừng khi rê chuột
	// (chỉ dừng khi focus bàn phím, tab bị ẩn hoặc giảm chuyển động).
	function carousel( o ) {
		var root = o.root, n = o.slides.length, cur = 0, seen = false;

		// Chuyển tới trang i: đặt hướng (data-dir) rồi tính lại kiểu trước khi đổi class để chuyển cảnh chạy đúng chiều; slide vừa rời đi giữ .is-leave 1,2 giây.
		function go( i ) {
			var from = cur, out = o.slides[ from ], d;

			cur = ( i + n ) % n;
			d = ( cur - from + n ) % n;
			if ( d ) {
				root.setAttribute( 'data-dir', d <= n / 2 ? 'next' : 'prev' );
				void root.offsetWidth;
				win.clearTimeout( out._leave );
				out.classList.add( 'is-leave' );
				out._leave = win.setTimeout( function () {
					out.classList.remove( 'is-leave' );
				}, 1200 );
				win.clearTimeout( o.slides[ cur ]._leave );
				o.slides[ cur ].classList.remove( 'is-leave' );
			}
			o.slides.forEach( function ( s, k ) {
				s.classList.toggle( 'is-active', k === cur );
				s.classList.toggle( 'is-in', k === cur && seen );
			} );
			warmNear( o.slides, cur );
			o.ticks.forEach( function ( t, k ) {
				if ( k === cur ) {
					t.setAttribute( 'aria-current', 'true' );
				} else {
					t.removeAttribute( 'aria-current' );
				}
			} );
		}

		root.setAttribute( 'aria-roledescription', str( 'carousel' ) );
		o.slides.forEach( function ( s, k ) {
			var g = s.firstElementChild || s, lb = g.getAttribute( 'aria-labelledby' ), nm = lb ? doc.getElementById( lb ) : null;
			var pos = str( 'slideOf' ).replace( '%1$d', k + 1 ).replace( '%2$d', n );

			// article và figure đã có vai trò riêng (role="group" không hợp lệ trên chúng): chỉ phần tử trung tính mới nhận role.
			if ( /^(DIV|SPAN)$/.test( g.tagName ) ) {
				g.setAttribute( 'role', 'group' );
			}
			g.setAttribute( 'aria-roledescription', str( 'slide' ) );
			// Thẻ có tên riêng (aria-labelledby, ví dụ tên sự kiện) thì gộp vị trí vào nhãn; để nguyên thì "n trên N" bị aria-labelledby ghi đè.
			if ( nm && nm.textContent ) {
				g.removeAttribute( 'aria-labelledby' );
				g.setAttribute( 'aria-label', nm.textContent.replace( /\s+/g, ' ' ).replace( /^ | $/g, '' ) + ', ' + pos );
			} else {
				g.setAttribute( 'aria-label', pos );
			}
		} );
		go( 0 );
		[ [ o.prev, -1 ], [ o.next, 1 ] ].forEach( function ( b ) {
			b[ 0 ].addEventListener( 'click', function () {
				go( cur + b[ 1 ] );
			} );
		} );
		o.ticks.forEach( function ( t, k ) {
			t.addEventListener( 'click', function () {
				go( k );
			} );
		} );
		o.list.setAttribute( 'aria-live', 'polite' );
		auto( root, function () {
			go( cur + 1 );
		}, {
			threshold: 0.2,
			seen: function ( v ) {
				seen = v;
				if ( v ) {
					o.slides[ cur ].classList.add( 'is-in' );
				}
			},
			state: function ( on ) {
				root.classList.toggle( 'is-playing', on );
				o.list.setAttribute( 'aria-live', on ? 'off' : 'polite' );
			}
		} );
		drag( o.list, function ( dir ) {
			go( cur + dir );
		} );
		return { go: go };
	}

	// Băng chuyền của section (Hiệu quả liệu trình): khối [data-isha-<name>] gồm .isha-<name>__list, các .isha-<name>__item và cụm phân trang
	// [data-isha-<name>-controls]; chỉ ở chế độ phim và từ 2 slide, lỗi thì để nguyên danh sách tĩnh.
	function deck( root, name ) {
		var box = root.querySelector( '[data-isha-' + name + ']' ), bar = box && box.querySelector( '[data-isha-' + name + '-controls]' );
		var list = box && box.querySelector( '.isha-' + name + '__list' ), title = root.querySelector( '.isha-sec__title' );
		var slides = list ? all( list, '.isha-' + name + '__item' ) : [];

		if ( ! enhanced() || slides.length < 2 || ! bar ) {
			return;
		}
		try {
			box.setAttribute( 'role', 'group' );
			if ( title ) {
				box.setAttribute( 'aria-labelledby', title.id );
			}
			box.classList.add( 'is-carousel' );
			bar.hidden = false;
			carousel( {
				root: box,
				list: list,
				slides: slides,
				ticks: all( bar, '.isha-pager__dots button' ),
				prev: bar.querySelector( '.isha-pager__btn--prev' ),
				next: bar.querySelector( '.isha-pager__btn--next' )
			} );
		} catch ( err ) {
			box.classList.remove( 'is-carousel' );
			bar.hidden = true;
			report( name, err );
		}
	}

	// Video nền (hero, cảnh kết): lặp, không tiếng, không nút; src() chọn nguồn, want() báo cần phát.
	// sync(): phát khi cảnh cần; trình duyệt chặn tự phát (Safari
	// tiết kiệm pin) thì giữ poster, thử lại ở lần chạm đầu.
	function bgVideo( video, src, want ) {
		var conn = win.navigator.connection, lite = !! ( conn && conn.saveData ), RETRY = [ 'pointerdown', 'pointerup', 'touchend', 'click' ];

		// Gán nguồn rồi nạp video.
		function load() {
			var s = src();

			video.hidden = false;
			if ( s && video.getAttribute( 'src' ) !== s ) {
				video.muted = true;
				video.preload = 'auto';
				video.setAttribute( 'src', s );
				try {
					video.load();
				} catch ( err ) {
					report( 'video', err );
				}
			}
		}

		// Phát hoặc dừng video theo cảnh và chế độ.
		function sync() {
			var pr;

			if ( want() && ! lite && ! reduced() && ! doc.hidden ) {
				load();
				try {
					pr = video.play();
				} catch ( err ) {
					pr = null;
				}
				if ( pr && pr.catch ) {
					pr.catch( noop );
				}
			} else if ( ! video.paused ) {
				video.pause();
			}
		}

		// Thử phát lại khi video đang dừng (autoplay bị chặn).
		function retry() {
			if ( video.paused ) {
				sync();
			}
		}

		video.muted = true;
		video.addEventListener( 'playing', function () {
			video.classList.add( 'is-playing' );
		} );
		doc.addEventListener( 'visibilitychange', sync );
		// Lần chạm đầu: pointerdown không phải cử chỉ kích hoạt trên cảm ứng (iOS Chế độ tiết kiệm pin chặn autoplay), nên thêm pointerup, touchend và click.
		RETRY.forEach( function ( t ) {
			doc.addEventListener( t, retry, { passive: true } );
		} );
		return { sync: sync, load: load, lite: lite };
	}

	// Nghe đổi chế độ (compact, reduced).
	function onMode( type, fn ) {
		var m = ISHA.motion, q = mq[ type ];
		if ( m && 'function' === typeof m.on ) {
			return m.on( type, fn );
		}
		if ( q && q.addEventListener ) {
			q.addEventListener( 'change', function () {
				fn( q.matches );
			} );
		}
		return noop;
	}

	// Chạy khi DOM đã sẵn sàng.
	function ready( fn ) {
		if ( 'loading' === doc.readyState ) {
			doc.addEventListener( 'DOMContentLoaded', fn );
		} else {
			fn();
		}
	}

	// Tải trước ảnh lazy trong el (đổi sang eager để trình duyệt tải ngay); dùng cho ảnh của slide sắp hiện mà lazy gốc chưa tính là gần khung nhìn.
	function warm( el ) {
		if ( el ) {
			all( el, 'img[loading="lazy"]' ).forEach( function ( img ) {
				img.loading = 'eager';
			} );
		}
	}

	// Tải trước các phần tử quanh chỉ số cur của danh sách (cur - 1 đến cur + 2, theo vòng); step: số phần tử mỗi nhóm (bản sao ở dải không gian).
	function warmNear( list, cur, step ) {
		var n = step || list.length, d;

		for ( d = -1; d <= 2; d++ ) {
			list.forEach( function ( el, i ) {
				if ( i % n === ( ( cur + d ) % n + n ) % n ) {
					warm( el );
				}
			} );
		}
	}

	// Ảnh đang tải thì ẩn (is-wait), tải xong mờ dần vào (is-pop) thay vì bật ra đột ngột; ảnh đã có sẵn không bị đụng tới.
	function fadeImages() {
		all( doc, '.isha-home img.isha-img' ).forEach( function ( img ) {
			function ok() {
				img.removeEventListener( 'load', ok );
				img.removeEventListener( 'error', ok );
				img.classList.remove( 'is-wait' );
				img.classList.add( 'is-pop' );
			}

			if ( ! img.complete ) {
				img.classList.add( 'is-wait' );
				img.addEventListener( 'load', ok );
				img.addEventListener( 'error', ok );
			}
		} );
	}

	// Khởi chạy một module, lỗi chỉ báo console.
	function boot( name, fn ) {
		ready( function () {
			try {
				fn();
			} catch ( err ) {
				report( name, err );
			}
		} );
	}

	// Gọi hàm của một job, bắt lỗi.
	function call( job, key, arg ) {
		if ( job.dead || 'function' !== typeof job[ key ] ) {
			return undefined;
		}
		try {
			return job[ key ]( arg );
		} catch ( err ) {
			job.dead = true;                       // job lỗi chỉ tự dừng, không làm hỏng job khác
			report( ( job.name || 'job' ) + ' ' + key, err );
			return undefined;
		}
	}

	// Chạy các job đang chờ trong một khung.
	function run() {
		var list = wantAll ? jobs.slice() : [], i;
		queued = wantAll = false;
		for ( i = 0; i < queue.length; i++ ) {
			if ( list.indexOf( queue[ i ] ) < 0 ) {
				list.push( queue[ i ] );
			}
		}
		queue = [];
		view.y = win.pageYOffset || html.scrollTop || 0;
		view.vh = win.innerHeight || html.clientHeight || 0;
		view.vw = html.clientWidth || win.innerWidth || 0;
		for ( i = 0; i < list.length; i++ ) {
			list[ i ].data = call( list[ i ], 'read', view );
		}
		for ( i = 0; i < list.length; i++ ) {
			call( list[ i ], 'write', list[ i ].data );
		}
	}

	// Đặt lịch chạy job ở khung kế.
	function schedule( all ) {
		wantAll = wantAll || all;
		if ( ! queued ) {
			queued = true;
			if ( win.requestAnimationFrame ) {
				win.requestAnimationFrame( run );
			} else {
				win.setTimeout( run, 16 );
			}
		}
	}

	// Yêu cầu chạy lại mọi job.
	function request() {
		schedule( true );
	}

	// Bố cục đổi: tăng bộ đếm đo và báo người nghe.
	function invalidate() {
		var i;
		view.layout++;
		for ( i = 0; i < layoutFns.length; i++ ) {
			try {
				layoutFns[ i ]();
			} catch ( err ) {
				report( 'layout', err );
			}
		}
		request();
	}

	// Theo dõi đổi thuộc tính của một phần tử.
	function watchAttrs( el, list ) {
		if ( el && win.MutationObserver ) {
			new win.MutationObserver( request ).observe( el, { attributes: true, attributeFilter: list } );
		}
	}

	// Nối cuộn, đổi kích thước, font vào vòng job.
	function wire() {
		var ro, film, head;
		if ( wired ) {
			return;
		}
		wired = true;
		win.addEventListener( 'scroll', request, { passive: true } );
		win.addEventListener( 'resize', invalidate );
		win.addEventListener( 'orientationchange', invalidate );
		win.addEventListener( 'load', invalidate );
		win.addEventListener( 'pageshow', function ( e ) {
			if ( e && e.persisted ) {                  // quay lại từ bfcache
				invalidate();
			}
		} );
		if ( doc.fonts && doc.fonts.ready ) {
			doc.fonts.ready.then( invalidate, noop );
		}
		onMode( 'compact', invalidate );
		onMode( 'reduced', request );
		watchAttrs( html, [ 'class' ] );
		if ( win.ResizeObserver ) {
			ro = new win.ResizeObserver( invalidate );
			film = doc.querySelector( '[data-isha-film]' );
			head = doc.querySelector( '.site-header--home' );
			if ( film ) {
				ro.observe( film );
			}
			if ( head ) {
				ro.observe( head );
			}
		}
	}

	var frame = {
		watch: function ( job ) {
			wire();
			jobs.push( job );
			schedule( true );
		},
		once: function ( job ) {
			if ( queue.indexOf( job ) < 0 ) {
				queue.push( job );
			}
			schedule( false );
		},
		request: request,
		onLayout: function ( fn ) {
			wire();
			layoutFns.push( fn );
		}
	};

	// Khởi chạy module cảnh cho các section có trong DOM.
	function startScenes( only ) {
		var motion = ISHA.motion, nodes, ctx, i, id, fn;
		if ( ! motion || ! motion.started || failed() ) {
			return false;
		}
		ctx = { motion: motion, config: cfg, str: str, reduced: reduced, compact: compact };
		nodes = doc.querySelectorAll( '[data-isha-scene]' );
		for ( i = 0; i < nodes.length; i++ ) {
			id = nodes[ i ].getAttribute( 'data-isha-scene' );
			fn = id && Object.prototype.hasOwnProperty.call( home.modules, id ) ? home.modules[ id ] : null;
			if ( 'function' !== typeof fn || ( only && only !== id ) || done.indexOf( nodes[ i ] ) > -1 ) {
				continue;
			}
			done.push( nodes[ i ] );
			try {
				fn( nodes[ i ], ctx );
			} catch ( err ) {
				report( 'scene ' + id, err );
			}
		}
		return true;
	}

	// Lõi tới chậm: thử lại mỗi 150ms tối đa 6 giây (khớp cổng isha-motion-fail).
	function pollScenes() {
		var tries = 0;
		if ( polling ) {
			return;
		}
		polling = true;
		( function attempt() {
			if ( startScenes() ) {
				polling = false;
				scenesOn = true;
			} else if ( failed() || ++tries > 40 ) {
				polling = false;
			} else {
				win.setTimeout( attempt, 150 );
			}
		}() );
	}

	home.register = function ( id, fn ) {
		if ( 'string' !== typeof id || '' === id || 'function' !== typeof fn ) {
			return;
		}
		home.modules[ id ] = fn;
		if ( scenesOn ) {
			startScenes( id );
		} else {
			pollScenes();
		}
	};
	var register = home.register;

/* >>> rows */
	// Hàng vuốt ngang: điều khiển hiện khi track tràn; Trước/Sau tới điểm bám kế.
	( function () {
		var TOL = 2;                                    // dung sai làm tròn khi so vị trí cuộn (px)

		// NodeList thành mảng.
		function list( nodes ) {
			return Array.prototype.slice.call( nodes );
		}

		// Đặt aria-disabled cho nút.
		function setDisabled( btn, off ) {
			if ( btn && off !== ( 'true' === btn.getAttribute( 'aria-disabled' ) ) ) {
				if ( off ) {
					btn.setAttribute( 'aria-disabled', 'true' );
				} else {
					btn.removeAttribute( 'aria-disabled' );
				}
			}
		}

		// Dựng điều khiển cho một hàng vuốt ngang.
		function initRow( root ) {
			var track = root.querySelector( '.isha-row__track' );
			var controls = root.querySelector( '[data-isha-pager]' );
			var prev = controls && controls.querySelector( '.isha-pager__btn--prev' );
			var next = controls && controls.querySelector( '.isha-pager__btn--next' );
			var ol = controls && controls.querySelector( '.isha-pager__dots' );
			var items = [], dots = ol ? list( ol.querySelectorAll( 'button' ) ) : [], dirty = true, shown = -1, goal = null, timer = 0, geom, job;

			if ( ! track ) {
				return;
			}
			if ( ! track.getAttribute( 'aria-label' ) && ! track.getAttribute( 'aria-labelledby' ) ) {
				track.setAttribute( 'aria-label', str( 'rowLabel' ) );
			}

			// Đo phạm vi cuộn và vị trí các khung.
			function measure() {
				var max = Math.max( 0, track.scrollWidth - track.clientWidth ), base = 0;
				return {
					max: max,
					over: max > 1,
					pos: items.map( function ( item, k ) {
						var left = item.getBoundingClientRect().left;
						base = 0 === k ? left : base;
						return clamp( left - base, 0, max );
					} )
				};
			}

			// Khung gần vị trí cuộn nhất.
			function nearest( left, g ) {
				var n = g.pos.length, best = 0, gap = Infinity, k, d;
				if ( n < 2 || left <= TOL ) {
					return 0;
				}
				if ( left >= g.max - TOL ) {
					return n - 1;
				}
				for ( k = 0; k < n; k++ ) {
					d = Math.abs( g.pos[ k ] - left );
					if ( d <= gap ) {
						gap = d;
						best = k;
					}
				}
				return best;
			}

			// Cuộn hàng tới x.
			function moveTo( x ) {
				var quick = reduced();
				goal = quick ? null : x;
				win.clearTimeout( timer );
				timer = win.setTimeout( function () {
					goal = null;
				}, 1200 );
				try {
					track.scrollTo( { left: x, behavior: quick ? 'instant' : 'smooth' } );
				} catch ( err ) {
					track.scrollLeft = x;
				}
			}

			// Cuộn sang khung trước hoặc sau.
			function go( dir ) {
				var g = measure(), base = null === goal ? track.scrollLeft : goal, x = null, k;
				for ( k = dir > 0 ? 0 : g.pos.length - 1; k >= 0 && k < g.pos.length; k += dir ) {
					if ( dir > 0 ? g.pos[ k ] > base + TOL : g.pos[ k ] < base - TOL ) {
						x = g.pos[ k ];
						break;
					}
				}
				if ( null !== x ) {
					moveTo( x );
				}
			}

			// Đánh dấu cần đo lại.
			function remeasure() {
				dirty = true;
				frame.once( job );
			}

			job = {
				name: 'row',
				read: function () {
					var left = track.scrollLeft;
					if ( dirty ) {
						dirty = false;
						items = list( track.children ).filter( function ( el ) {
							return el.classList.contains( 'isha-row__item' );
						} );
						geom = measure();
					}
					goal = null !== goal && Math.abs( left - goal ) <= TOL ? null : goal;
					return { left: left, g: geom };
				},
				write: function ( s ) {
					var cur, k, li, btn;
					if ( controls && controls.hidden === s.g.over ) {
						controls.hidden = ! s.g.over;
						if ( s.g.over ) {
							remeasure();
						}
					}
					// hàng không tràn: bỏ tabindex cho đỡ thừa một điểm dừng Tab.
					if ( s.g.over !== ( null !== track.getAttribute( 'tabindex' ) ) ) {
						if ( s.g.over ) {
							track.setAttribute( 'tabindex', '0' );
						} else {
							track.removeAttribute( 'tabindex' );
						}
					}
					setDisabled( prev, s.left <= TOL );
					setDisabled( next, s.left >= s.g.max - TOL );
					if ( ol && dots.length !== items.length ) {
						ol.textContent = '';
						for ( k = 0; k < items.length; k++ ) {
							li = ol.appendChild( doc.createElement( 'li' ) );
							btn = li.appendChild( doc.createElement( 'button' ) );
							btn.type = 'button';
							btn.setAttribute( 'aria-label', fmt( str( 'item' ), k + 1 ) );
						}
						dots = list( ol.querySelectorAll( 'button' ) );
						shown = -1;
					}
					cur = nearest( s.left, s.g );
					if ( cur !== shown ) {
						shown = cur;
						dots.forEach( function ( dot, i ) {
							if ( i === cur ) {
								dot.setAttribute( 'aria-current', 'true' );
							} else {
								dot.removeAttribute( 'aria-current' );
							}
						} );
					}
				}
			};

			[ [ prev, -1 ], [ next, 1 ] ].forEach( function ( pair ) {
				if ( pair[ 0 ] ) {
					pair[ 0 ].addEventListener( 'click', function () {
						if ( 'true' !== pair[ 0 ].getAttribute( 'aria-disabled' ) ) {
							go( pair[ 1 ] );
						}
					} );
				}
			} );
			if ( ol ) {
				ol.addEventListener( 'click', function ( e ) {
					var k = dots.indexOf( e.target.closest ? e.target.closest( 'button' ) : e.target );
					if ( k > -1 && k < items.length ) {
						moveTo( measure().pos[ k ] );
					}
				} );
			}
			track.addEventListener( 'keydown', function ( e ) {
				if ( e.target === track && ! ( e.altKey || e.ctrlKey || e.metaKey || e.shiftKey ) && ( 'ArrowLeft' === e.key || 'ArrowRight' === e.key ) ) {
					e.preventDefault();
					go( 'ArrowRight' === e.key ? 1 : -1 );
				}
			} );
			[ 'wheel', 'touchstart', 'mousedown' ].forEach( function ( type ) {
				track.addEventListener( type, function () {
					goal = null;
				}, { passive: true } );
			} );
			track.addEventListener( 'scroll', function () {
				frame.once( job );
			}, { passive: true } );
			root.addEventListener( 'load', function ( e ) {
				if ( e.target && 'IMG' === e.target.tagName ) {
					remeasure();
				}
			}, true );
			frame.onLayout( remeasure );
			if ( win.ResizeObserver ) {
				new win.ResizeObserver( remeasure ).observe( track );
			}
			remeasure();
		}

		boot( 'img-fade', fadeImages );

		boot( 'rows', function () {
			list( doc.querySelectorAll( '[data-isha-row]' ) ).forEach( function ( root ) {
				try {
					initRow( root );
				} catch ( err ) {
					report( 'row', err );
				}
			} );
		} );
	}() );
/* <<< rows */
/* >>> header */
	// Header: is-over-hero, is-solid, is-hidden (ẩn khi cuộn xuống 12px, hiện khi lên 8px).
	( function () {
		var HIDE_AFTER = 12, SHOW_AFTER = 8, BAND = 4;
		var header, hero, inset = 0, height = 0, seen = -1, lastY = null, dir = 0, run = 0, over = null, hidden = false;

		// Job đọc: trạng thái cần có của header.
		function read( v ) {
			if ( ! enhanced() ) {
				return null;
			}
			if ( seen !== v.layout ) {
				seen = v.layout;
				height = header.offsetHeight;
				inset = parseFloat( win.getComputedStyle( header ).top ) || 0;   // thanh admin: 32px hoặc 46px
			}
			return {
				y: clamp( v.y, 0, Math.max( 0, html.scrollHeight - v.vh ) ),       // bỏ phần nảy quá mép của iOS
				heroBottom: hero ? hero.getBoundingClientRect().bottom : null,
				pinned: header.contains( doc.activeElement ) || !! header.querySelector( '.menu-toggle[aria-expanded="true"]' ),
				calm: reduced()
			};
		}

		// Job ghi: đặt lớp trạng thái cho header.
		function write( s ) {
			var cl = header.classList, dy;
			if ( ! s ) {
				cl.remove( 'is-over-hero', 'is-solid', 'is-hidden' );
				over = null;
				hidden = false;
				dir = run = 0;
				lastY = null;
				return;
			}
			over = null !== s.heroBottom && s.heroBottom > inset + height + ( false === over ? BAND : 0 );
			dy = null === lastY ? 0 : s.y - lastY;               // khung đầu chỉ lấy mốc (tải lại giữa trang không tính là cuộn xuống)
			lastY = s.y;
			if ( dy ) {
				if ( ( dy > 0 ? 1 : -1 ) !== dir ) {
					dir = dy > 0 ? 1 : -1;
					run = 0;
				}
				run += Math.abs( dy );
			}
			if ( s.calm || s.pinned || s.y <= height ) {
				hidden = false;
			} else if ( hidden ) {
				hidden = ! ( dir < 0 && run >= SHOW_AFTER );
			} else {
				hidden = dir > 0 && run >= HIDE_AFTER;
			}
			cl.toggle( 'is-over-hero', over );
			cl.toggle( 'is-solid', ! over );
			cl.toggle( 'is-hidden', hidden );
		}

		boot( 'header', function () {
			header = doc.querySelector( '.site-header--home' );
			if ( header ) {
				hero = doc.querySelector( '[data-isha-scene="hero"]' ) || doc.getElementById( 'hero' );
				header.addEventListener( 'focusin', frame.request );
				frame.watch( { name: 'header', read: read, write: write } );
			}
		} );
	}() );
/* <<< header */
/* >>> dock */
	// Dock: is-visible khi qua 80% hero; ẩn khi #cta hiện trên 40% hoặc còn intro.
	( function () {
		var PASSED = 0.8, HIDE_AT = 0.4;
		var dock, hero, cta, intro, shown = null;

		// Tỉ lệ phần tử đang hiện trong màn hình.
		function visibleRatio( el, vh ) {
			var r = el ? el.getBoundingClientRect() : null, seen;
			if ( ! r || r.height < 1 ) {
				return 0;
			}
			seen = Math.min( r.bottom, vh ) - Math.max( r.top, 0 );
			return seen > 0 ? seen / Math.min( r.height, vh ) : 0;
		}

		// Job đọc: dock có nên hiện.
		function read( v ) {
			var r = hero ? hero.getBoundingClientRect() : null;
			if ( failed() ) {
				return null;
			}
			return ( ! r || r.height < 1 || -r.top >= PASSED * r.height ) && visibleRatio( cta, v.vh ) < HIDE_AT &&
				! ( intro && intro.getClientRects().length > 0 && 'hidden' !== win.getComputedStyle( intro ).visibility );
		}

		// Job ghi: bật hoặc tắt dock.
		function write( want ) {
			if ( null !== want && want !== shown ) {
				shown = want;
				dock.classList.toggle( 'is-visible', want );
			}
		}

		boot( 'dock', function () {
			dock = doc.querySelector( '[data-isha-dock]' );
			if ( ! dock ) {
				return;
			}
			hero = doc.querySelector( '[data-isha-scene="hero"]' ) || doc.getElementById( 'hero' );
			cta = doc.getElementById( 'cta' ) || doc.querySelector( '[data-isha-scene="cta"]' );
			intro = doc.querySelector( '[data-isha-intro]' );
			watchAttrs( intro, [ 'class', 'style', 'hidden' ] );
			watchAttrs( doc.body, [ 'class' ] );
			frame.watch( { name: 'dock', read: read, write: write } );
		} );
	}() );
/* <<< dock */
/* >>> sky */
	// Bầu trời: --sky-bg pha OKLab giữa tông hai section theo cuộn; mối nối cực sáng/tối nhận data-sky-solid.
	( function () {
		var REF = 0.5, BUF = 0.6, MIN_SPAN = 24, DARK = 0.18;
		var sky, film, secs = [], jn = [], solid = [], seen = -1, applied = '', on = false, cache = {};

		// Chuỗi màu CSS thành RGB.
		function parseColor( s ) {
			var m = /^rgba?\(\s*([\d.]+)[\s,]+([\d.]+)[\s,]+([\d.]+)\s*(?:[,/]\s*([\d.]+)\s*)?\)$/i.exec( s ), k = 1;
			if ( ! m ) {
				m = /^color\(\s*srgb\s+([\d.]+)\s+([\d.]+)\s+([\d.]+)\s*(?:\/\s*([\d.]+)\s*)?\)$/i.exec( s );
				k = 255;
			}
			return m && ( undefined === m[ 4 ] || parseFloat( m[ 4 ] ) >= 1 ) ? [ k * m[ 1 ], k * m[ 2 ], k * m[ 3 ] ] : null;
		}

		// Giá trị màu CSS thành RGB (có đệm).
		function toRgb( raw ) {
			var c = cache[ raw ], probe, host, bg;
			if ( ! c ) {
				host = sky || doc.body;
				probe = host.appendChild( doc.createElement( 'i' ) );
				probe.style.display = 'none';
				probe.style.backgroundColor = raw;
				bg = win.getComputedStyle( probe ).backgroundColor;
				host.removeChild( probe );
				c = cache[ raw ] = parseColor( bg );
			}
			return c;
		}

		// Một kênh sRGB sang tuyến tính.
		function lin( c ) {
			c /= 255;
			return c <= 0.04045 ? c / 12.92 : Math.pow( ( c + 0.055 ) / 1.055, 2.4 );
		}

		// Một kênh tuyến tính về sRGB 0..255.
		function enc( c ) {
			c = c <= 0.0031308 ? 12.92 * c : 1.055 * Math.pow( c, 1 / 2.4 ) - 0.055;
			return Math.round( 255 * clamp( c, 0, 1 ) );
		}

		// độ chói WCAG; DARK (0,18) cân bằng chữ Shadow và Daylight.
		function lum( c ) {
			return 0.2126 * lin( c[ 0 ] ) + 0.7152 * lin( c[ 1 ] ) + 0.0722 * lin( c[ 2 ] );
		}

		// RGB sang Oklab.
		function toLab( c ) {
			var r = lin( c[ 0 ] ), g = lin( c[ 1 ] ), b = lin( c[ 2 ] );
			var l = Math.pow( 0.4122214708 * r + 0.5363325363 * g + 0.0514459929 * b, 1 / 3 );
			var m = Math.pow( 0.2119034982 * r + 0.6806995451 * g + 0.1073969566 * b, 1 / 3 );
			var s = Math.pow( 0.0883024619 * r + 0.2817188376 * g + 0.6299787005 * b, 1 / 3 );
			return [
				0.2104542553 * l + 0.7936177850 * m - 0.0040720468 * s,
				1.9779984951 * l - 2.4285922050 * m + 0.4505937099 * s,
				0.0259040371 * l + 0.7827717662 * m - 0.8086757660 * s
			];
		}

		// Mảng RGB thành chuỗi rgb().
		function css( c ) {
			return 'rgb(' + Math.round( c[ 0 ] ) + ', ' + Math.round( c[ 1 ] ) + ', ' + Math.round( c[ 2 ] ) + ')';
		}

		// Trộn hai màu trong Oklab.
		function mix( a, b, t ) {
			var L = lerp( a.lab[ 0 ], b.lab[ 0 ], t ), A = lerp( a.lab[ 1 ], b.lab[ 1 ], t ), B = lerp( a.lab[ 2 ], b.lab[ 2 ], t );
			var l = L + 0.3963377774 * A + 0.2158037573 * B;
			var m = L - 0.1055613458 * A - 0.0638541728 * B;
			var s = L - 0.0894841775 * A - 1.2914855480 * B;
			l = l * l * l;
			m = m * m * m;
			s = s * s * s;
			return 'rgb(' + enc( 4.0767416621 * l - 3.3077115913 * m + 0.2309699292 * s ) + ', ' +
				enc( -1.2684380046 * l + 2.6097574011 * m - 0.3413193965 * s ) + ', ' +
				enc( -0.0041960863 * l - 0.7034186147 * m + 1.7076147010 * s ) + ')';
		}

		// Đo màu nền và đoạn chuyển của các section.
		function measure( v ) {
			var nodes = ( film || doc ).querySelectorAll( '[data-isha-scene]' ), k, el, r, cs, raw, rgb, mid, lo, hi;
			secs = [];
			jn = [];
			for ( k = 0; k < nodes.length; k++ ) {
				el = nodes[ k ];
				r = el.getBoundingClientRect();
				cs = win.getComputedStyle( el );
				raw = cs.getPropertyValue( '--tone-bg' ).replace( /^\s+|\s+$/g, '' );
				rgb = r.height >= 1 && raw ? toRgb( raw ) : null;
				if ( rgb ) {
					secs.push( {
						top: r.top + v.y,
						bottom: r.bottom + v.y,
						pt: parseFloat( cs.paddingTop ) || 0,
						pb: parseFloat( cs.paddingBottom ) || 0,
						lab: toLab( rgb ),
						css: css( rgb ),
						dark: lum( rgb ) < DARK,
						el: el
					} );
				}
			}
			secs.sort( function ( a, b ) {
				return a.top - b.top;
			} );
			for ( k = 0; k < secs.length - 1; k++ ) {
				mid = ( secs[ k ].bottom + secs[ k + 1 ].top ) / 2;
				lo = mid - BUF * secs[ k ].pb;
				hi = mid + BUF * secs[ k + 1 ].pt;
				if ( hi - lo < MIN_SPAN ) {
					lo = mid - MIN_SPAN / 2;
					hi = mid + MIN_SPAN / 2;
				}
				jn.push( { lo: lo, hi: hi } );
			}
			solidify();
		}

		// Đánh dấu section có nền đặc ở cạnh side (top | bottom: cạnh giáp mối nối đổi cực); đặc cả hai cạnh thì thành both.
		function mark( el, side ) {
			var was = el.getAttribute( 'data-sky-solid' );

			el.setAttribute( 'data-sky-solid', was && was !== side ? 'both' : side );
			if ( solid.indexOf( el ) < 0 ) {
				solid.push( el );
			}
		}

		// Gỡ mọi dấu nền đặc.
		function solidify() {
			var k;
			for ( k = 0; k < solid.length; k++ ) {
				solid[ k ].removeAttribute( 'data-sky-solid' );
				solid[ k ].style.removeProperty( '--sky-mid-b' );
				solid[ k ].style.removeProperty( '--sky-mid-t' );
			}
			solid = [];
			for ( k = 0; k < secs.length - 1; k++ ) {
				if ( secs[ k ].dark !== secs[ k + 1 ].dark ) {
					mark( secs[ k ].el, 'bottom' );
					mark( secs[ k + 1 ].el, 'top' );
					// Màu giữa cho dải chuyển ở mối nối (hero tự vẽ nền nên mối nối giáp hero giữ cắt gọn).
					if ( ! secs[ k ].el.classList.contains( 'isha-hero' ) && ! secs[ k + 1 ].el.classList.contains( 'isha-hero' ) ) {
						secs[ k ].el.style.setProperty( '--sky-mid-b', mix( secs[ k ], secs[ k + 1 ], 0.5 ) );
						secs[ k + 1 ].el.style.setProperty( '--sky-mid-t', mix( secs[ k ], secs[ k + 1 ], 0.5 ) );
					}
				}
			}
		}

		// Màu bầu trời tại vị trí ref.
		function colorAt( ref ) {
			var k, t;
			for ( k = 0; k < jn.length; k++ ) {
				if ( ref < jn[ k ].lo || ( ref <= jn[ k ].hi && secs[ k ].css === secs[ k + 1 ].css ) ) {
					return secs[ k ].css;
				}
				if ( ref <= jn[ k ].hi ) {
					t = ( ref - jn[ k ].lo ) / ( jn[ k ].hi - jn[ k ].lo );
					if ( t <= 0 || t >= 1 ) {
						return secs[ t <= 0 ? k : k + 1 ].css;
					}
					return mix( secs[ k ], secs[ k + 1 ], t * t * ( 3 - 2 * t ) );
				}
			}
			return secs.length ? secs[ secs.length - 1 ].css : '';
		}

		// Job đọc: màu bầu trời cần hiện.
		function read( v ) {
			if ( failed() || reduced() ) {
				return null;
			}
			if ( seen !== v.layout ) {
				seen = v.layout;
				measure( v );
			}
			return colorAt( v.y + v.vh * REF );
		}

		// Job ghi: đặt màu bầu trời.
		function write( color ) {
			if ( ! color ) {
				if ( on ) {
					html.classList.remove( 'isha-sky-on' );
					on = false;
				}
				return;
			}
			if ( color !== applied ) {
				sky.style.setProperty( '--sky-bg', color );
				applied = color;
			}
			if ( ! on ) {
				html.classList.add( 'isha-sky-on' );
				on = true;
			}
		}

		boot( 'sky', function () {
			sky = doc.querySelector( '[data-isha-sky]' );
			if ( sky ) {
				film = sky.closest ? sky.closest( '[data-isha-film]' ) : null;
				frame.watch( { name: 'sky', read: read, write: write } );
			}
		} );
	}() );
/* <<< sky */
/* >>> hero */
	// Hero và intro: intro CSS tối đa 2,8 giây (bỏ qua được); hero ghim, iris theo cuộn hoặc 1,2 giây (gọn).
	( function () {
		var SEEN = 'ishaIntro', MIN = 2000, MAX = 2200, CLEAN = 900, SVG = 'http://www.w3.org/2000/svg';

		// Đoạn của v trong [a, b], chuẩn hoá 0..1.
		function seg( v, a, b ) {
			return clamp( ( v - a ) / ( b - a ), 0, 1 );
		}

		register( 'hero', function ( root, ctx ) {
			var motion = ctx.motion, ease = motion.ease && motion.ease.inOut ? motion.ease.inOut : function ( t ) {
				return t;
			};
			var stage = root.querySelector( '[data-isha-stage]' ), film = root.querySelector( '[data-isha-hero-film]' );
			var media = root.querySelector( '[data-isha-hero-media]' ), veil = root.querySelector( '[data-isha-hero-veil]' );
			var sun = root.querySelector( '[data-isha-hero-sun]' ), rays = root.querySelector( '[data-isha-hero-rays]' );
			var video = root.querySelector( '[data-isha-hero-video]' ), bv = null;
			var intro = root.querySelector( '[data-isha-intro]' ), hint = root.querySelector( '.isha-hero__scroll' );
			var fades = Array.prototype.slice.call( root.querySelectorAll( '.isha-hero__top, .isha-hero__title, .isha-hero__bottom' ) );
			var cta = Array.prototype.slice.call( root.querySelectorAll( '.isha-hero__bottom a, .isha-hero__bottom button' ) );
			var geo = { r0: 0, r1: 0 }, cur = { e: 0, t: 0 }, queue = [], ended = ! html.classList.contains( 'isha-intro-on' );
			var opened = false, inView = true, calm = false;

			if ( ! stage || ! film || ! sun ) {
				return;
			}

			// Đo tâm và cỡ mặt trời trong stage.
			function measure() {
				var s = sun.getBoundingClientRect(), f = stage.getBoundingClientRect(), cx = s.left - f.left + s.width / 2, cy = s.top - f.top + s.height / 2;
				var dx = Math.max( cx, f.width - cx ), dy = Math.max( cy, f.height - cy );
				geo.r0 = s.width / 2;
				geo.r1 = Math.sqrt( dx * dx + dy * dy ) + 2;
				film.style.setProperty( '--iris-x', cx.toFixed( 1 ) + 'px' );
				film.style.setProperty( '--iris-y', cy.toFixed( 1 ) + 'px' );
				show( cur.e, cur.t );
			}

			// e: độ mở iris 0..1 (đã qua easing); t: mức chữ mờ 0..1.
			function show( e, t ) {
				var i, v;
				cur.e = e = clamp( e, 0, 1 );
				cur.t = t = clamp( t, 0, 1 );
				if ( calm ) {
					return;
				}
				film.style.setProperty( '--iris-r', ( geo.r0 + ( geo.r1 - geo.r0 ) * e ).toFixed( 1 ) + 'px' );
				if ( media ) {
					media.style.transform = 'scale(' + ( 1.12 - 0.12 * e ).toFixed( 4 ) + ')';
				}
				if ( rays ) {
					rays.style.opacity = e > 0 ? ( 1 - e ).toFixed( 3 ) : '';
					rays.style.transform = e > 0 ? 'scale(' + ( 1 + 1.6 * e ).toFixed( 3 ) + ')' : '';
				}
				if ( veil ) {
					veil.style.opacity = t > 0 ? ( 0.6 - 0.46 * t ).toFixed( 3 ) : '';
				}
				if ( hint ) {
					hint.style.opacity = e > 0 ? ( 0.7 * ( 1 - seg( e, 0, 0.15 ) ) ).toFixed( 3 ) : '';
				}
				for ( i = 0; i < fades.length; i++ ) {
					v = t > 0;
					fades[ i ].style.opacity = v ? ( 1 - t ).toFixed( 3 ) : '';
					fades[ i ].style.transform = v ? 'translate3d(0,' + ( -8 * t ).toFixed( 2 ) + 'vh,0)' : '';
				}
				// Nút gần như tàng hình thì ẩn hẳn: không còn nhận Tab, focus hay bấm khi người xem không thấy nó (chữ vẫn nằm trong cây trợ năng).
				for ( i = 0; i < cta.length; i++ ) {
					cta[ i ].style.visibility = t >= 0.92 ? 'hidden' : '';
				}
			}

			// Gỡ mọi giá trị tạm của iris.
			function clear() {
				var i, list = [ media, rays, veil, hint ].concat( fades );
				film.style.removeProperty( '--iris-r' );
				for ( i = 0; i < list.length; i++ ) {
					if ( list[ i ] ) {
						list[ i ].style.opacity = '';
						list[ i ].style.transform = '';
					}
				}
				for ( i = 0; i < cta.length; i++ ) {
					cta[ i ].style.visibility = '';
				}
				cur.e = cur.t = 0;
			}

			// Iris chạy theo thời gian khi intro vừa xong.
			function timed() {
				if ( reduced() ) {
					return;
				}
				whenEnded( function () {
					if ( opened ) {
						show( 1, 0 );
						return;
					}
					motion.tween( { duration: 1.2, ease: 'cine', update: function ( v, e ) {
						show( e, 0 );
					}, done: function () {
						opened = true;
					} } );
				} );
			}

			// Chạy fn khi intro đã kết thúc (hoặc xếp hàng chờ).
			function whenEnded( fn ) {
				if ( ended ) {
					fn();
				} else {
					queue.push( fn );
				}
			}

			// Nguồn video theo hướng màn hình.
			function source() {
				var portrait = win.matchMedia && win.matchMedia( '(orientation: portrait)' ).matches;
				return video.getAttribute( portrait ? 'data-src-portrait' : 'data-src-landscape' ) || video.getAttribute( 'data-src-landscape' ) || '';
			}

			// Đồng bộ phát video.
			function sync() {
				if ( bv ) {
					bv.sync();
				}
			}

			if ( video ) {
				var orient;
				bv = bgVideo( video, source, function () {
					return ended && inView;
				} );
				orient = win.matchMedia ? win.matchMedia( '(orientation: portrait)' ) : null;
				if ( orient && orient.addEventListener ) {
					orient.addEventListener( 'change', function () {
						if ( video.getAttribute( 'src' ) && source() !== video.getAttribute( 'src' ) ) {
							video.removeAttribute( 'src' );
							video.classList.remove( 'is-playing' );
							sync();
						}
					} );
				}
			}

			// Kết thúc intro ngay và giải phóng hàng chờ.
			function endNow() {
				var i;
				html.classList.add( 'isha-intro-end' );
				win.setTimeout( function () {
					html.classList.remove( 'isha-intro-on', 'isha-intro-end' );
					if ( intro ) {
						intro.classList.remove( 'is-run' );
					}
				}, CLEAN );
				ended = true;
				for ( i = 0; i < queue.length; i++ ) {
					queue[ i ]();
				}
				queue = [];
				sync();
			}

			// Dựng tia và vòng của lớp intro từ đường vẽ của hero.
			function build() {
				var r = sun.getBoundingClientRect(), svg = intro.querySelector( '.isha-intro__rays' ), beams = rays && rays.querySelector( '.isha-hero__beams' );
				var parts = beams ? ( beams.getAttribute( 'd' ) || '' ).split( 'M' ) : [], i, el;
				intro.style.setProperty( '--sx', ( r.left + r.width / 2 ).toFixed( 1 ) + 'px' );
				intro.style.setProperty( '--sy', ( r.top + r.height / 2 ).toFixed( 1 ) + 'px' );
				intro.style.setProperty( '--sd', r.width.toFixed( 1 ) + 'px' );
				if ( ! svg ) {
					return;
				}
				svg.style.setProperty( '--rw', ( 50 / Math.max( r.width, 1 ) ).toFixed( 4 ) );   // 1px đổi sang đơn vị viewBox
				for ( i = 0; i < parts.length; i++ ) {
					el = doc.createElementNS( SVG, i ? 'path' : 'circle' );
					if ( i ) {
						el.setAttribute( 'd', 'M' + parts[ i ] );
					} else {
						el.setAttribute( 'r', '30' );
					}
					el.setAttribute( 'pathLength', '1' );
					el.style.setProperty( '--i', String( i ) );
					svg.appendChild( el );
				}
			}

			// Chạy intro: chờ font và video, cho phép bỏ qua.
			function runIntro() {
				var events = [ 'pointerdown', 'keydown', 'wheel', 'touchstart', 'scroll' ], i, minTimer, maxTimer, finished = false, minDone = false, need = 0;

				// Kết thúc intro một lần.
				function finish() {
					if ( finished ) {
						return;
					}
					finished = true;
					win.clearTimeout( minTimer );
					win.clearTimeout( maxTimer );
					for ( i = 0; i < events.length; i++ ) {
						win.removeEventListener( events[ i ], finish, true );
					}
					endNow();
				}

				// Một điều kiện (font, video) đã sẵn sàng.
				function onReady() {
					if ( --need <= 0 && minDone ) {
						finish();
					}
				}

				try {
					win.sessionStorage.setItem( SEEN, '1' );
				} catch ( err ) {
					report( 'hero intro storage', err );
				}
				build();
				if ( bv && ! bv.lite ) {
					bv.load();
					if ( video.readyState < 3 ) {
						need++;
						video.addEventListener( 'canplay', onReady, { once: true } );
					}
				}
				// Chờ font của màn đầu để chữ không đổi font khi hero hiện; tối đa MAX.
				if ( doc.fonts && doc.fonts.load && win.Promise ) {
					need++;
					win.Promise.all( [ '500 24px "Garamond Premier Pro"', 'italic 400 24px "Garamond Premier Pro"', '400 17px Montserrat' ].map( function ( f ) {
						return doc.fonts.load( f );
					} ) ).then( onReady, onReady );
				}
				for ( i = 0; i < events.length; i++ ) {
					win.addEventListener( events[ i ], finish, { capture: true, passive: true } );
				}
				win.requestAnimationFrame( function () {
					win.requestAnimationFrame( function () {
						intro.classList.add( 'is-run' );
					} );
				} );
				minTimer = win.setTimeout( function () {
					minDone = true;
					if ( need <= 0 ) {
						finish();
					} else {
						maxTimer = win.setTimeout( finish, MAX - MIN );
					}
				}, MIN );
			}

			calm = reduced();
			measure();
			if ( ! calm ) {
				show( 0, 0 );
			}
			frame.onLayout( measure );
			motion.scene( root, {
				pin: true,
				length: 1.4,
				smooth: 0.18,
				compact: timed,
				update: function ( p, state ) {
					calm = state.reduced;
					if ( calm ) {
						clear();
						return;
					}
					show( ease( seg( p, 0, 0.6 ) ), seg( p, 0.35, 0.6 ) );
				},
				enter: function () {
					inView = true;
					sync();
				},
				leave: function () {
					inView = false;
					sync();
				}
			} );
			onMode( 'reduced', function ( on ) {
				calm = on;
				if ( on ) {
					clear();
				}
				sync();
			} );

			if ( ! ended && intro && ! calm && ! motion.hashEntry && ( win.pageYOffset || html.scrollTop || 0 ) < 8 ) {
				runIntro();
			} else if ( ! ended ) {
				ended = true;                                  // cổng đã thêm isha-intro-on nhưng điều kiện không còn: bỏ intro ngay
				html.classList.remove( 'isha-intro-on', 'isha-intro-end' );
				sync();
			} else {
				sync();
			}
		} );
	}() );
/* <<< hero */
/* >>> services */
	// Dịch vụ tiêu biểu ("Bánh xe tĩnh lặng"): không còn chạy trong home.js. Vòng xoay 3D và ánh sáng là script module riêng
	// (assets/js/home-wheel/main.js, nạp khi section sắp vào khung nhìn), dùng vòng cung ánh kim chung (assets/js/card-ring/).
/* <<< services */
/* >>> process */
	// Quy trình: đường kẻ tự vẽ (.is-live), --f mỗi bước, is-lit khi tới; desktop theo p, gọn theo mép phải.
	( function () {
		register( 'process', function ( root, ctx ) {
			var track = root.querySelector( '.isha-process__steps' ), steps = all( root, '.isha-process__step' ), n = steps.length;
			var pos = [], wid = [], live = false, job;

			if ( ! track || ! n ) {
				return;
			}

			// Đo vị trí và bề rộng các bước.
			function measure() {
				var x0 = steps[ 0 ].getBoundingClientRect().left;

				pos = steps.map( function ( s ) {
					return s.getBoundingClientRect().left - x0;
				} );
				wid = steps.map( function ( s ) {
					return s.getBoundingClientRect().width;
				} );
			}

			// Vẽ đường kẻ tới x và đặt bước sáng.
			function paint( x ) {                            // x: đầu đường kẻ, đo từ mép trái của bước đầu
				steps.forEach( function ( s, k ) {
					s.style.setProperty( '--f', clamp( ( x - pos[ k ] ) / wid[ k ], 0, 1 ).toFixed( 3 ) );
					s.classList.toggle( 'is-lit', x >= pos[ k ] + 2 );
				} );
			}

			// Bật hoặc tắt chế độ phim theo chế độ hiện tại.
			function sync() {
				var want = enhanced() && ! reduced();

				if ( want === live ) {
					return;
				}
				live = want;
				root.classList.toggle( 'is-live', live );
				if ( live ) {
					measure();
					frame.once( job );
				} else {
					steps.forEach( function ( s ) {
						s.style.removeProperty( '--f' );
						s.classList.remove( 'is-lit' );
					} );
				}
			}

			job = {
				name: 'process',
				read: function () {
					return track.getBoundingClientRect().right - steps[ 0 ].getBoundingClientRect().left;
				},
				write: function ( x ) {
					if ( live && compact() ) {
						paint( x );
					}
				}
			};
			track.addEventListener( 'scroll', function () {
				frame.once( job );
			}, { passive: true } );
			frame.onLayout( function () {
				measure();
				frame.once( job );
			} );
			ctx.motion.scene( root, {
				update: function ( p, state ) {
					if ( live && ! state.compact && ! state.reduced ) {
						paint( ( pos[ n - 1 ] + wid[ n - 1 ] ) * seg( p, 0.12, 0.5 ) );
					}
				}
			} );
			onMode( 'compact', function () {
				frame.once( job );
			} );
			onMode( 'reduced', sync );
			sync();
		} );
	}() );
/* <<< process */
/* >>> space */
	// Không gian: băng chuyền kéo lướt cùng kiểu với Dịch vụ tiêu biểu (không sao chép phần giao diện khác). Dải khung trượt ngang (translateX theo vị trí liên tục p, vòng kín),
	// ảnh trong khung dịch lệch ngược chiều (--space-sx), 11 lá rèm phủ ảnh mới tới rồi co dần từng lá (so le) để lộ ảnh. Khác Dịch vụ: chạy CHẬM (2,25 giây, rèm mở trải trên cả quãng trượt)
	// để mắt kịp thấy các lá rời nhau rồi khớp lại. Chuyển bằng: kéo hoặc vuốt (drag(), cả desktop lẫn điện thoại), mũi tên Trước/Sau, vạch chỉ mục, phím mũi tên, tự chạy 8 giây.
	// Parallax theo cuộn trang: --space-par (-1 đến 1); giảm chuyển động thì 0 và chuyển cảnh tức thì (lõi tween).
	( function () {
		var SLATS = 11, STEP = 0.05, DUR = 1.25, LEAVE = 2500;

		// Số có hai chữ số: 1 thành 01.
		function pad2( n ) {
			return ( n < 10 ? '0' : '' ) + n;
		}

		register( 'space', function ( root, ctx ) {
			var motion = ctx.motion, stage = root.querySelector( '[data-isha-space]' ), view = stage && stage.querySelector( '.isha-space__view' );
			var list = stage && stage.querySelector( '.isha-space__list' ), pager = stage && stage.querySelector( '[data-isha-pager]' );
			var items = list ? all( list, '.isha-space__item' ) : [], n = items.length, frames = [], now = stage && stage.querySelector( '[data-isha-space-now]' );
			var ticks = pager ? all( pager, '.isha-pager__dots button' ) : [], prev = pager && pager.querySelector( '.isha-pager__btn--prev' ), next = pager && pager.querySelector( '.isha-pager__btn--next' );
			var pos = 0, shown = -1, tw = null, par = NaN, title = root.querySelector( '.isha-sec__title' );

			if ( ! stage || ! view || ! list || ! n ) {
				return;
			}

			// Khoảng cách có dấu từ vị trí p tới khung k theo vòng, trong [-n/2, n/2].
			function wrapD( k, p ) {
				var d = ( ( k - p ) % n + n ) % n;

				return d > n / 2 ? d - n : d;
			}

			// Đặt vị trí p: mỗi khung tự dịch theo khoảng cách vòng d tới p (khung cuối nối liền khung đầu), ảnh dịch lệch ngược chiều, rèm mở khi d từ 1 về 0.
			function show( p ) {
				var cur = ( ( Math.round( p ) % n ) + n ) % n, k, d, f, t, j, was;

				for ( k = 0; k < n; k++ ) {
					d = wrapD( k, p );
					items[ k ].style.transform = 'translate3d(' + ( 100 * d ).toFixed( 3 ) + '%,0,0)';
					f = frames[ k ];
					if ( ! f ) {
						continue;
					}
					f.img.style.setProperty( '--space-sx', clamp( d, -1.3, 1.3 ).toFixed( 3 ) );
					t = 1 - seg( d, 0, 1 );
					if ( t !== f.t ) {
						f.t = t;
						for ( j = 0; j < SLATS; j++ ) {
							f.slats[ j ].style.transform = 'scaleX(' + ( 1 - seg( t, j * STEP, j * STEP + 1 - ( SLATS - 1 ) * STEP ) ).toFixed( 3 ) + ')';
						}
					}
				}
				if ( cur !== shown ) {
					was = shown;
					shown = cur;
					items.forEach( function ( item, i ) {
						item.classList.toggle( 'is-active', i === cur );
						if ( i === cur ) {
							item.classList.remove( 'is-leave' );
							win.clearTimeout( item._leave );
							item.removeAttribute( 'aria-hidden' );
						} else {
							item.setAttribute( 'aria-hidden', 'true' );
						}
					} );
					if ( was > -1 ) {
						items[ was ].classList.add( 'is-leave' );
						win.clearTimeout( items[ was ]._leave );
						items[ was ]._leave = win.setTimeout( function () {
							items[ was ].classList.remove( 'is-leave' );
						}, LEAVE );
					}
					warmNear( items, cur );
					if ( now ) {
						now.textContent = pad2( cur + 1 );
					}
					ticks.forEach( function ( b, i ) {
						if ( i === cur ) {
							b.setAttribute( 'aria-current', 'true' );
						} else {
							b.removeAttribute( 'aria-current' );
						}
					} );
				}
			}

			// Trượt tới vị trí t (có thể vượt n - 1 hoặc nhỏ hơn 0: cuộn tiếp cùng chiều qua chỗ nối vòng).
			function goTo( t ) {
				if ( tw ) {
					tw.cancel();
					tw = null;
				}
				if ( n < 2 || pos === t ) {
					return;
				}
				tw = motion.tween( {
					from: pos,
					to: t,
					duration: Math.min( 3.6, DUR + 0.25 * ( Math.abs( t - pos ) - 1 ) ),
					ease: 'inOut',
					update: function ( v ) {
						pos = v;
						show( v );
					},
					done: function () {
						pos = ( ( pos % n ) + n ) % n;
						tw = null;
					}
				} );
			}

			// Khối là nhóm băng chuyền cho trình đọc màn hình, mỗi khung có nhãn "k trên n".
			function label() {
				stage.setAttribute( 'role', 'group' );
				stage.setAttribute( 'aria-roledescription', str( 'carousel' ) );
				if ( title ) {
					stage.setAttribute( 'aria-labelledby', title.id );
				}
				items.forEach( function ( item, k ) {
					item.setAttribute( 'aria-roledescription', str( 'slide' ) );
					item.setAttribute( 'aria-label', str( 'slideOf' ).replace( '%1$d', k + 1 ).replace( '%2$d', n ) );
				} );
			}

			// Parallax theo cuộn trang; chạy cả khi không có băng chuyền. compact: 'same' để chạy cả ở chế độ gọn (mặc định lõi chuyển cảnh sang tĩnh, p = 1).
			motion.scene( root, {
				compact: 'same',
				update: function ( p, state ) {
					var v = state.reduced ? 0 : clamp( 2 * p - 1, -1, 1 );

					if ( Math.abs( v - par ) > 0.0005 || isNaN( par ) ) {
						par = v;
						stage.style.setProperty( '--space-par', v.toFixed( 4 ) );
					}
				}
			} );
			if ( ! enhanced() || n < 2 ) {
				return;
			}

			try {
				items.forEach( function ( item ) {
					var frame = item.querySelector( '.isha-space__frame' ), img = frame && frame.querySelector( '.isha-img, .isha-ph' ), wrap, slats = [], i;

					if ( ! img ) {
						frames.push( null );
						return;
					}
					wrap = doc.createElement( 'span' );
					wrap.className = 'isha-space__blinds';
					wrap.setAttribute( 'aria-hidden', 'true' );
					for ( i = 0; i < SLATS; i++ ) {
						slats.push( wrap.appendChild( doc.createElement( 'i' ) ) );
					}
					frame.appendChild( wrap );
					frames.push( { img: img, slats: slats, t: -1 } );
				} );
				label();
				stage.classList.add( 'is-carousel' );
				if ( pager ) {
					pager.hidden = false;
				}
				show( 0 );
			} catch ( err ) {
				stage.classList.remove( 'is-carousel' );
				report( 'space', err );
				return;
			}

			[ [ prev, -1 ], [ next, 1 ] ].forEach( function ( b ) {
				if ( b[ 0 ] ) {
					b[ 0 ].addEventListener( 'click', function () {
						goTo( Math.round( pos ) + b[ 1 ] );
					} );
				}
			} );
			ticks.forEach( function ( b, k ) {
				b.addEventListener( 'click', function () {
					var base = Math.round( pos );

					goTo( base + wrapD( k, base ) );
				} );
			} );
			list.addEventListener( 'keydown', function ( e ) {
				if ( 'ArrowRight' === e.key || 'ArrowLeft' === e.key ) {
					e.preventDefault();
					goTo( Math.round( pos ) + ( 'ArrowRight' === e.key ? 1 : -1 ) );
				}
			} );
			// Gắn vào cả sân khấu (không chỉ khung ảnh): ở desktop khối chữ đè lên ảnh nhưng không nằm trong khung ảnh, thả chuột trên đó vẫn phải tính.
			drag( stage, function ( dir ) {
				goTo( Math.round( pos ) + dir );
			} );
			// Ken Burns chỉ bắt đầu khi khung vào tầm nhìn (không tiêu hết 10 giây khi chưa ai thấy).
			motion.inView( stage, function ( v ) {
				if ( v ) {
					stage.classList.add( 'is-seen' );
				}
			}, { once: true, threshold: 0.25 } );
			auto( root, function () {
				goTo( Math.round( pos ) + 1 );
			}, { ms: 8000, threshold: 0.25 } );
		} );
	}() );
/* <<< space */
/* >>> results */
	// Hiệu quả liệu trình: băng chuyền ảnh trước/sau đã ghép, cùng kiểu với Sự kiện (deck()).
	register( 'results', function ( root ) {
		deck( root, 'results' );
	} );
/* <<< results */
/* >>> events */
	// Sự kiện: không còn băng chuyền (từ 19/11/2026 là danh sách tạp chí); hành vi nằm ở script module assets/js/events-list/main.js.
/* <<< events */
/* >>> articles */
/* <<< articles */
/* >>> testimonials */
	// Lời kể: một hàng thẻ trôi liên tục (marquee CSS), dừng khi rê chuột; nhân bản danh sách (aria-hidden, inert) cho đủ phủ khung nhìn
	// để vòng lặp liền mạch. Không bật khi lõi lỗi hoặc giảm chuyển động (giữ hàng vuốt tĩnh).
	( function () {
		var SPEED = 40;   // px mỗi giây

		register( 'testimonials', function ( root ) {
			var box = root.querySelector( '[data-isha-marquee]' ), track = box && box.querySelector( '.isha-testimonials__track' );
			var list = track && track.querySelector( '.isha-testimonials__list' ), clones = [], width = -1;

			if ( ! list ) {
				return;
			}

			// Gỡ bản sao và tắt trôi.
			function off() {
				clones.forEach( function ( c ) {
					track.removeChild( c );
				} );
				clones = [];
				root.classList.remove( 'is-marquee' );
			}

			// Dựng trôi theo bề rộng hiện tại: số bản sao đủ phủ khung nhìn cộng một nhóm, quãng dịch bằng một nhóm kể cả khoảng cách.
			function build() {
				var gap, step, need, c, k;

				off();
				if ( ! enhanced() || reduced() ) {
					return;
				}
				gap = parseFloat( win.getComputedStyle( track ).columnGap ) || 0;
				step = list.offsetWidth + gap;
				if ( step < 1 ) {
					return;
				}
				need = Math.ceil( box.clientWidth / step ) + 1;
				for ( k = 0; k < need; k++ ) {
					c = list.cloneNode( true );
					c.setAttribute( 'aria-hidden', 'true' );
					c.setAttribute( 'inert', '' );
					clones.push( track.appendChild( c ) );
				}
				root.style.setProperty( '--tm-shift', -step + 'px' );
				root.style.setProperty( '--tm-dur', ( step / SPEED ).toFixed( 1 ) + 's' );
				root.classList.add( 'is-marquee' );
			}

			// Chỉ dựng lại khi bề rộng khung đổi (không làm giật hàng thẻ mỗi lần đo lại bố cục).
			function size() {
				if ( box.clientWidth !== width ) {
					width = box.clientWidth;
					build();
				}
			}

			try {
				size();
				frame.onLayout( size );
				onMode( 'reduced', build );
			} catch ( err ) {
				off();
				report( 'testimonials', err );
			}
		} );
	}() );
/* <<< testimonials */
/* >>> cta */
	// Cảnh kết: iris khép (clip-path) kèm vòng Gold; video chỉ phát khi cảnh hiện, không có nút.
	( function () {
		register( 'cta', function ( root, ctx ) {
			var motion = ctx.motion, media = root.querySelector( '.isha-home-cta__media' ), inOut = ease( 'inOut' ), ring = doc.createElement( 'span' );
			var video = root.querySelector( '.isha-home-cta__video' ), bv = null;
			var r0 = 0, r1 = 0, e0 = 0, iris = false, visible = false;

			// Đo bán kính đầu và cuối của iris.
			function measure() {
				var w = root.clientWidth, h = root.clientHeight;

				r0 = Math.sqrt( w * w + h * h ) / 2 + 2;
				r1 = compact() ? 0.46 * Math.min( w, h ) : 0.36 * Math.min( win.innerWidth, win.innerHeight );
				ring.style.width = ring.style.height = ( 2 * r1 ).toFixed( 1 ) + 'px';
			}

			// Vẽ iris và vòng ở độ mở e.
			function show( e ) {
				var r = lerp( r0, r1, clamp( e, 0, 1 ) );

				e0 = e;
				if ( media ) {
					media.style.setProperty( '--iris-r', r.toFixed( 1 ) + 'px' );
				}
				ring.style.transform = 'translate(-50%,-50%) scale(' + ( r / Math.max( r1, 1 ) ).toFixed( 4 ) + ')';
				ring.style.opacity = ( 0.9 * seg( e, 0.1, 0.7 ) ).toFixed( 3 );
			}

			// Bật hoặc huỷ iris theo chế độ hiện tại.
			function arm() {
				var want = enhanced() && ! reduced();

				if ( want !== iris ) {
					iris = want;
					root.classList.toggle( 'is-iris', iris );
					if ( iris ) {
						measure();
						show( 0 );
					} else {
						if ( media ) {
							media.style.removeProperty( '--iris-r' );
						}
						ring.style.transform = ring.style.opacity = '';
					}
				}
			}

			// Đồng bộ phát video.
			function sync() {
				if ( bv ) {
					bv.sync();
				}
			}

			ring.className = 'isha-home-cta__ring';
			ring.setAttribute( 'aria-hidden', 'true' );
			root.insertBefore( ring, media ? media.nextSibling : root.firstChild );
			if ( video ) {
				bv = bgVideo( video, function () {
					return video.getAttribute( 'data-src' ) || '';
				}, function () {
					return visible;
				} );
			}

			frame.onLayout( function () {
				if ( iris ) {
					measure();
					show( e0 );
				}
			} );
			onMode( 'compact', function () {
				if ( iris ) {
					measure();
					show( 0 );
				}
			} );
			onMode( 'reduced', function () {
				arm();
				sync();
			} );
			arm();
			motion.scene( root, {
				compact: function () {
					motion.inView( root, function () {
						motion.tween( { duration: 1.2, ease: 'cine', update: function ( v, e ) {
							show( e );
						} } );
					}, { once: true, threshold: 0.5 } );
				},
				update: function ( p, state ) {
					if ( iris && ! state.compact && ! state.reduced ) {
						show( inOut( seg( p, 0.06, 0.52 ) ) );
					}
				},
				enter: function () {
					visible = true;
					sync();
				},
				leave: function () {
					visible = false;
					sync();
				}
			} );
		} );
	}() );
/* <<< cta */
/* >>> partners */
	// Đối tác: logo hiện lần lượt bằng opacity khi lưới vào khung (so le 40ms, --i); không marquee.
	( function () {
		register( 'partners', function ( root, ctx ) {
			var grid = root.querySelector( '.isha-partners__grid' ), cells = grid ? all( grid, '.isha-partners__cell' ) : [];
			var groups = Math.min( cells.length, 6 );

			if ( ! cells.length || ! enhanced() || reduced() ) {
				return;
			}
			cells.forEach( function ( c, k ) {
				c.style.setProperty( '--i', Math.floor( k * groups / cells.length ) );
			} );
			grid.classList.add( 'is-pre' );
			ctx.motion.inView( grid, function () {
				grid.classList.remove( 'is-pre' );
			}, { once: true, threshold: 0.2 } );
			onMode( 'reduced', function ( on ) {
				if ( on ) {
					grid.classList.remove( 'is-pre' );
				}
			} );
		} );
	}() );
/* <<< partners */

	ready( function () {
		if ( scenesOn ) {
			startScenes();
		} else {
			pollScenes();
		}
	} );
}() );
