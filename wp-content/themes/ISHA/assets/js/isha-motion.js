/**
 * Lõi chuyển động ISHA: window.ISHA.motion (docs/home-architecture.md mục 5.8). ES5, không thư viện. Một vòng rAF cho mọi cảnh và tween.
 * Thiếu rAF, matchMedia hoặc IntersectionObserver thì không khởi động; cổng trong <head> thêm isha-motion-fail.
 * API: started, hashEntry; scene( root, { pin, length, smooth, compact: 'static' | 'same' | fn, update( p, state ), enter, leave, track, stage } )
 *   -> { root, progress(), refresh(), destroy() }; tween( { delay, duration, from, to, ease, update( v, e ), done } );
 *   inView( el, fn( visible, entry ), { once, threshold, rootMargin } ); reduced(), compact(), on( 'compact' | 'reduced' | 'resize', fn ),
 *   clamp, lerp, seg, ease, scrollTo( yOrEl, { offset, duration, done } ).
 * Gọn + 'static': không ghim, update( 1 ) một lần khi vào khung; 'same': như đầy đủ; hàm: gọi một lần. Giảm chuyển động: mọi cảnh như 'static'.
 * Cảnh ghim: rootEl > [data-isha-track] > [data-isha-stage] (sticky); p = độ dịch của stage trong track. Không ghim: p = tiến độ đi qua màn hình.
 */
( function () {
	'use strict';

	var win = window, doc = document, html = doc.documentElement;

	// Thiếu API nền: thoát êm (cổng <head> thêm isha-motion-fail sau 6 giây).
	if ( ! win.requestAnimationFrame || ! win.matchMedia || ! win.IntersectionObserver ) {
		return;
	}
	win.ISHA = win.ISHA || {};
	if ( win.ISHA.motion && win.ISHA.motion.started ) {
		return;
	}

	var EPS = 0.0004, TAU = 0.18, MAX_DT = 0.1;
	var NEAR = '100% 0px 100% 0px';   // vùng "gần": thêm một chiều cao màn hình mỗi phía
	var VIEW = '0px 0px -10% 0px';    // "vào khung" (chế độ tĩnh): mép trên lên quá 90% màn hình
	var KEYS = [ 'ArrowUp', 'ArrowDown', 'PageUp', 'PageDown', 'Home', 'End', ' ' ];
	var STOP = [ 'wheel', 'touchstart', 'mousedown', 'keydown' ];
	var mqCompact = win.matchMedia( '(max-width: 900px), (max-height: 560px)' );
	var mqReduced = win.matchMedia( '(prefers-reduced-motion: reduce)' );
	var modeCompact = mqCompact.matches, modeReduced = mqReduced.matches;
	var vw = 0, vh = 0, raf = 0, last = 0, needMeasure = false, resizeEvt = false;
	var scenes = [], tweens = [], scrolling = null;
	var listeners = { compact: [], reduced: [], resize: [] };
	var ioNear = new win.IntersectionObserver( onIO, { rootMargin: NEAR } );
	var ioView = new win.IntersectionObserver( onIO, { rootMargin: VIEW } );
	var ro = win.ResizeObserver ? new win.ResizeObserver( onSizes ) : null;

	// Hàm rỗng.
	function noop() {}
	// Trả 0.
	function zero() {
		return 0;
	}
	// Kẹp v vào [a, b].
	function clamp( v, a, b ) {
		return v < a ? a : ( v > b ? b : v );
	}
	// Nội suy tuyến tính a tới b.
	function lerp( a, b, t ) {
		return a + ( b - a ) * t;
	}
	// Đoạn của p trong [a, b], chuẩn hoá 0..1.
	function seg( p, a, b ) {
		return a === b ? ( p < a ? 0 : 1 ) : clamp( ( p - a ) / ( b - a ), 0, 1 );
	}
	// Số hữu hạn kẹp vào 0..1.
	function unit( v ) {
		return isFinite( v ) ? clamp( v, 0, 1 ) : 0;
	}
	// Easing ra theo luỹ thừa n.
	function powerOut( n ) {
		return function ( t ) {
			return 1 - Math.pow( 1 - t, n );
		};
	}
	// cubic-bezier( x1, y1, x2, y2 ) kiểu CSS: chia đôi để giải x( s ) = t.
	function bezier( x1, y1, x2, y2 ) {
		// Một toạ độ của đường cong tại s.
		function at( s, a, b ) {
			var u = 1 - s;
			return 3 * u * u * s * a + 3 * u * s * s * b + s * s * s;
		}
		return function ( t ) {
			var lo = 0, hi = 1, s = t, i;
			if ( t <= 0 || t >= 1 ) {
				return t <= 0 ? 0 : 1;
			}
			for ( i = 0; i < 30; i++ ) {
				s = ( lo + hi ) / 2;
				if ( at( s, x1, x2 ) < t ) {
					lo = s;
				} else {
					hi = s;
				}
			}
			return at( s, y1, y2 );
		};
	}
	var ease = {
		linear: function ( t ) {
			return t;
		},
		power1: powerOut( 2 ),
		power2: powerOut( 3 ),
		power3: powerOut( 4 ),
		inOut: function ( t ) {
			return t < 0.5 ? 2 * t * t : 1 - Math.pow( -2 * t + 2, 2 ) / 2;
		},
		cine: bezier( 0.22, 1, 0.36, 1 )
	};
	// Chọn hàm easing theo tên hoặc hàm.
	function easeOf( v ) {
		if ( 'function' === typeof v ) {
			return v;
		}
		return Object.prototype.hasOwnProperty.call( ease, v ) ? ease[ v ] : ease.linear;
	}

	// Tab đang ẩn?
	function hidden() {
		return !! doc.hidden || 'hidden' === doc.visibilityState;
	}
	// Vị trí cuộn dọc hiện tại.
	function pageY() {
		return win.pageYOffset || html.scrollTop || 0;
	}
	// Đọc kích thước khung nhìn.
	function readViewport() {
		vw = html.clientWidth || win.innerWidth;
		vh = win.innerHeight || html.clientHeight;
	}
	// Thông tin chế độ và kích thước gửi cho callback.
	function info() {
		return { width: vw, height: vh, compact: modeCompact, reduced: modeReduced };
	}

	// Gọi callback của chủ sở hữu, lỗi chỉ báo một lần.
	function run( owner, key, a, b ) {
		var fn = owner[ key ];
		if ( 'function' !== typeof fn ) {
			return;
		}
		try {
			fn( a, b );
		} catch ( err ) {
			owner.failed = owner.failed || {};
			if ( ! owner.failed[ key ] && win.console ) {
				owner.failed[ key ] = true;
				win.console.error( '[ISHA.motion] ' + owner.name + ' ' + key, err );
			}
		}
	}

	// Đăng ký sự kiện (compact, reduced, resize), trả hàm huỷ.
	function on( type, fn ) {
		var list = listeners[ type ];
		if ( ! Array.isArray( list ) || 'function' !== typeof fn ) {
			return noop;
		}
		list.push( fn );
		return function () {
			var i = list.indexOf( fn );
			if ( i > -1 ) {
				list.splice( i, 1 );
			}
		};
	}
	// Phát sự kiện tới người nghe.
	function emit( type, value ) {
		listeners[ type ].slice().forEach( function ( fn ) {
			run( { name: 'on(' + type + ')', fn: fn }, 'fn', value, info() );
		} );
	}

	// Cảnh đang gần hoặc đang hiện (chế độ tuyến tính)?
	function near( s ) {
		return 'linear' === s.mode && ! s.dead && ( s.vis || s.active );
	}

	// Dựng đối tượng trạng thái gửi cho update.
	function stateOf( s, p ) {
		var st = s.state;
		st.p = p;
		st.target = s.target;
		st.width = vw;
		st.height = vh;
		st.compact = modeCompact;
		st.reduced = modeReduced;
		st.active = s.active;
		return st;
	}

	// Vẽ cảnh ở tiến độ p (gọi update).
	function draw( s, p ) {
		s.drawn = p;
		s.force = false;
		run( s, 'update', p, stateOf( s, p ) );
	}

	// Đọc bố cục một lượt, trả target 0..1; ghi lại kích thước để onSizes không báo trùng.
	function readTarget( s ) {
		var r, g, t, range;
		if ( ! s.pin ) {
			r = s.root.getBoundingClientRect();
			s.sz[ 0 ] = r.width;
			s.sz[ 1 ] = r.height;
			return unit( ( vh - r.top ) / ( vh + r.height ) );
		}
		r = s.track.getBoundingClientRect();
		s.sz[ 0 ] = r.width;
		s.sz[ 1 ] = r.height;
		if ( s.stage ) {
			g = s.stage.getBoundingClientRect();
			s.sz[ 2 ] = g.width;
			s.sz[ 3 ] = g.height;
			range = r.height - g.height;
			t = g.top - r.top;
		} else {
			range = r.height - vh;
			t = -r.top;
		}
		if ( s.length > 0 ) {
			range = Math.min( range, s.length * vh );
		}
		return range >= 1 ? unit( t / range ) : unit( ( vh - r.top ) / ( vh + r.height ) );
	}

	// Một bước làm mượt p tới target; true nếu còn đuổi.
	function step( s, dt ) {
		var chasing = false, diff = s.target - s.p;
		if ( s.snap ) {
			s.snap = false;
			s.p = s.target;
		} else if ( Math.abs( diff ) > EPS ) {
			s.p += s.tau > 0 ? diff * ( 1 - Math.exp( -dt / s.tau ) ) : diff;
			chasing = Math.abs( s.target - s.p ) > EPS;
			if ( ! chasing ) {
				s.p = s.target;
			}
		} else {
			s.p = s.target;
		}
		if ( s.force || Math.abs( s.p - s.drawn ) >= EPS || ( s.p === s.target && s.p !== s.drawn ) ) {
			draw( s, s.p );
		}
		return chasing;
	}

	// Cảnh vào vùng gần: snap rồi gọi enter.
	function enterScene( s ) {
		s.active = s.snap = s.force = true;
		run( s, 'enter', stateOf( s, s.p ) );
	}

	// Cảnh rời vùng gần: chốt p rồi gọi leave.
	function leaveScene( s ) {
		s.active = false;
		s.p = s.target;
		if ( s.p !== s.drawn ) {
			draw( s, s.p );
		}
		run( s, 'leave', stateOf( s, s.p ) );
	}

	// Chế độ tĩnh: vào hoặc ra khung; update( 1 ) hoặc hàm riêng chỉ một lần.
	function viewChange( s, vis ) {
		if ( vis === s.active ) {
			return;
		}
		s.active = vis;
		run( s, vis ? 'enter' : 'leave', stateOf( s, s.p ) );
		if ( vis && ! s.fired ) {
			s.fired = true;
			s.p = s.target = 1;
			if ( s.custom && ! modeReduced ) {
				run( s, 'custom', stateOf( s, 1 ) );
			} else {
				draw( s, 1 );
			}
		}
	}

	// Chế độ của cảnh: static hoặc linear.
	function modeOf( s ) {
		return modeReduced || ( modeCompact && 'same' !== s.compact ) ? 'static' : 'linear';
	}

	// Callback IntersectionObserver: cập nhật cảnh gần và cảnh hiện.
	function onIO( entries, obs ) {
		var isNear = obs === ioNear;
		entries.forEach( function ( e ) {
			scenes.forEach( function ( s ) {
				if ( s.root !== e.target || s.dead ) {
					return;
				}
				if ( isNear && 'linear' === s.mode ) {
					s.vis = e.isIntersecting;
				} else if ( ! isNear && 'static' === s.mode ) {
					viewChange( s, e.isIntersecting );
				}
			} );
		} );
		if ( isNear ) {
			kick();
		}
	}

	// Kích thước phần tử đổi: buộc vẽ lại, bỏ qua nếu trùng lần vừa đọc.
	function onSizes( entries ) {
		var wake = false;
		entries.forEach( function ( e ) {
			var box = e.borderBoxSize && e.borderBoxSize[ 0 ];
			var w = box ? box.inlineSize : e.contentRect.width;
			var h = box ? box.blockSize : e.contentRect.height;
			scenes.forEach( function ( s ) {
				var k = s.els.indexOf( e.target );
				if ( k < 0 || 'linear' !== s.mode || undefined === s.sz[ 2 * k ] ) {
					return;
				}
				if ( Math.abs( w - s.sz[ 2 * k ] ) > 0.5 || Math.abs( h - s.sz[ 2 * k + 1 ] ) > 0.5 ) {
					s.sz[ 2 * k ] = w;
					s.sz[ 2 * k + 1 ] = h;
					if ( near( s ) ) {
						s.force = wake = true;
					}
				}
			} );
		} );
		if ( wake ) {
			kick();
		}
	}

	// Gắn hoặc gỡ quan sát một cảnh.
	function observe( s, add ) {
		var m = add ? 'observe' : 'unobserve';
		var io = 'linear' === s.mode ? ioNear : ioView;
		var shared = scenes.some( function ( x ) {
			return x !== s && ! x.dead && x.root === s.root && x.mode === s.mode;
		} );
		if ( add ) {
			io.unobserve( s.root );
		}
		if ( add || ! shared ) {
			io[ m ]( s.root );
		}
		if ( ro && 'linear' === s.mode ) {
			s.els.forEach( function ( el ) {
				ro[ m ]( el );
			} );
		}
		s.vis = false;
	}

	// Đặt lịch khung hình kế tiếp nếu chưa có.
	function kick() {
		if ( ! raf && ! modeReduced && ! hidden() ) {
			raf = win.requestAnimationFrame( frame );
		}
	}

	// Yêu cầu đo lại bố cục ở khung kế.
	function requestMeasure( isResize ) {
		needMeasure = true;
		resizeEvt = resizeEvt || isResize;
		kick();
	}

	// Chạy các tween trong một khung.
	function stepTweens( dt ) {
		var cur = tweens;
		tweens = [];
		cur.forEach( function ( tw ) {
			var k, e;
			if ( tw.dead ) {
				return;
			}
			if ( tw.started ) {
				tw.t += dt;
			} else {
				tw.started = true;
			}
			if ( tw.t < 0 ) {
				tweens.push( tw );
				return;
			}
			k = tw.dur > 0 ? Math.min( 1, tw.t / tw.dur ) : 1;
			e = k >= 1 ? 1 : tw.ease( k );
			run( tw, 'update', tw.from + ( tw.to - tw.from ) * e, e );
			if ( k >= 1 ) {
				tw.dead = true;
				run( tw, 'done' );
			} else if ( ! tw.dead ) {
				tweens.push( tw );
			}
		} );
		return tweens.length > 0;
	}

	// Vòng rAF chung của mọi cảnh và tween.
	function frame( ts ) {
		var list = scenes, measure = needMeasure, resized = resizeEvt, busy = false, dt, i, s;
		raf = 0;
		if ( hidden() || modeReduced ) {
			last = 0;
			return;
		}
		dt = last ? clamp( ( ts - last ) / 1000, 0, MAX_DT ) : 1 / 60;
		last = ts;
		if ( measure ) {
			needMeasure = resizeEvt = false;
			readViewport();
		}
		for ( i = 0; i < list.length; i++ ) {
			s = list[ i ];
			if ( near( s ) ) {
				s.target = readTarget( s );
				s.force = s.force || measure;
			}
		}
		for ( i = 0; i < list.length; i++ ) {
			s = list[ i ];
			if ( ! near( s ) ) {
				continue;
			}
			if ( s.vis && ! s.active ) {
				enterScene( s );
			} else if ( ! s.vis ) {
				leaveScene( s );
				continue;
			}
			if ( ! s.dead && step( s, dt ) ) {
				busy = true;
			}
		}
		if ( tweens.length && stepTweens( dt ) ) {
			busy = true;
		}
		if ( resized ) {
			emit( 'resize', info() );
		}
		if ( busy || needMeasure ) {
			kick();
		} else {
			last = 0;
		}
	}

	// Kết thúc tween: chốt giá trị cuối và gọi done.
	function finishTween( tw ) {
		tw.dead = true;
		run( tw, 'update', tw.to, 1 );
		run( tw, 'done' );
	}

	// Dựng lại trạng thái các cảnh khi đổi chế độ.
	function reinit() {
		var cur = tweens;
		if ( raf ) {
			win.cancelAnimationFrame( raf );
			raf = 0;
		}
		last = 0;
		readViewport();
		if ( modeReduced ) {
			tweens = [];
			cur.forEach( function ( tw ) {
				if ( ! tw.dead ) {
					finishTween( tw );
				}
			} );
		}
		scenes.forEach( function ( s ) {
			observe( s, false );
			if ( s.active ) {
				s.active = false;
				run( s, 'leave', stateOf( s, s.p ) );
			}
			s.p = s.target = 0;
			s.drawn = -1;
			s.snap = s.force = true;
			s.fired = false;
			s.mode = modeOf( s );
			observe( s, true );
		} );
		requestMeasure( false );
	}

	// Cập nhật chế độ compact và reduced, báo khi đổi.
	function checkModes() {
		var c = mqCompact.matches, r = mqReduced.matches;
		var dc = c !== modeCompact, dr = r !== modeReduced;
		if ( ! dc && ! dr ) {
			return;
		}
		modeCompact = c;
		modeReduced = r;
		reinit();
		if ( dc ) {
			emit( 'compact', c );
		}
		if ( dr ) {
			emit( 'reduced', r );
		}
	}

	// Lấy phần tử từ node hoặc selector.
	function pick( root, v, sel ) {
		return v && 1 === v.nodeType ? v : root.querySelector( 'string' === typeof v ? v : sel );
	}

	// Đăng ký một cảnh cuộn (API công khai).
	function scene( root, opts ) {
		var s;
		opts = opts || {};
		if ( ! root || 1 !== root.nodeType ) {
			return { root: null, progress: zero, refresh: noop, destroy: noop };
		}
		s = {
			root: root, name: root.getAttribute( 'data-isha-scene' ) || root.id || 'scene',
			pin: !! opts.pin, length: +opts.length > 0 ? +opts.length : 0,
			tau: 'number' === typeof opts.smooth ? Math.max( 0, opts.smooth ) : ( false === opts.smooth ? 0 : TAU ),
			compact: opts.compact, custom: 'function' === typeof opts.compact ? opts.compact : null,
			update: opts.update, enter: opts.enter, leave: opts.leave,
			track: null, stage: null, els: [ root ], sz: [],
			p: 0, target: 0, drawn: -1, vis: false, active: false, snap: true, force: true, fired: false, dead: false,
			state: { p: 0, target: 0, width: 0, height: 0, compact: false, reduced: false, active: false }
		};
		if ( s.pin ) {
			s.track = pick( root, opts.track, '[data-isha-track]' ) || root;
			s.stage = pick( root, opts.stage, '[data-isha-stage]' );
			s.els = s.stage ? [ s.track, s.stage ] : [ s.track ];
		}
		s.mode = modeOf( s );
		scenes.push( s );
		observe( s, true );
		return {
			root: root,
			progress: function () {
				return s.p;
			},
			refresh: function () {
				s.force = true;
				requestMeasure( false );
			},
			destroy: function () {
				if ( ! s.dead ) {
					s.dead = true;
					s.active = false;
					observe( s, false );
					scenes = scenes.filter( function ( x ) {
						return x !== s;
					} );
				}
			}
		};
	}

	// Tween theo thời gian (API công khai).
	function tween( o ) {
		var tw;
		o = o || {};
		tw = {
			name: 'tween', from: 'number' === typeof o.from ? o.from : 0, to: 'number' === typeof o.to ? o.to : 1,
			dur: 'number' === typeof o.duration && o.duration >= 0 ? o.duration : 1, t: o.delay > 0 ? -o.delay : 0,
			ease: easeOf( o.ease ), update: o.update, done: o.done, started: false, dead: false
		};
		if ( modeReduced ) {
			win.setTimeout( function () {
				if ( ! tw.dead ) {
					finishTween( tw );
				}
			}, 0 );
		} else {
			tweens.push( tw );
			kick();
		}
		return {
			cancel: function () {
				tw.dead = true;
			}
		};
	}

	// Báo khi phần tử vào hoặc ra khung nhìn (API công khai).
	function inView( el, fn, opts ) {
		var io, off = noop, shown = false, want, last, holder = { name: 'inView', fn: fn };
		opts = opts || {};
		if ( ! el || 1 !== el.nodeType || 'function' !== typeof fn ) {
			return { disconnect: noop };
		}
		// Mức giao nhau nhỏ nhất được yêu cầu (Infinity: chỉ cần chạm khung nhìn).
		want = Math.min.apply( null, [].concat( undefined === opts.threshold ? 0 : opts.threshold ).filter( function ( t ) {
			return t > 0;
		} ) );
		// Mức cần đạt lúc này: phần tử cao hơn màn hình thì hạ xuống mức đạt được; đọc lại kích thước mỗi lần (xoay máy hay đổi cỡ khung làm đổi cả hai).
		function need() {
			var h = el.offsetHeight;
			return isFinite( want ) && h > win.innerHeight ? Math.min( want, 0.9 * win.innerHeight / h ) : want;
		}
		// Ngắt quan sát và gỡ người nghe đổi cỡ khung.
		function stop() {
			off();
			off = noop;
			if ( io ) {
				io.disconnect();
				io = null;
			}
		}
		// Dựng bộ quan sát: ngưỡng dày (từng 0,01) để mức cần đạt đổi theo bố cục vẫn được báo, cộng đúng mức cần đạt lúc này vì phần tử rất cao (hơn 10 màn hình)
		// có thể không có ngưỡng nào của lưới nằm trong khoảng đạt được.
		function watch() {
			var steps = [], i;
			for ( i = 0; i <= 100; i++ ) {
				steps.push( i / 100 );
			}
			last = need();
			if ( isFinite( last ) && last > 0 && last < 1 ) {
				steps.push( last );
			}
			io = new win.IntersectionObserver( function ( entries ) {
				entries.forEach( function ( e ) {
					// Hiện thật sự: có giao nhau và đạt mức cần đạt.
					var n = need(), vis = e.isIntersecting && ( ! isFinite( n ) || e.intersectionRatio + 0.001 >= n );
					if ( ! io || vis === shown ) {
						return;
					}
					shown = vis;
					if ( vis || ! opts.once ) {
						run( holder, 'fn', vis, e );
					}
					if ( vis && opts.once ) {
						stop();
					}
				} );
			}, { threshold: steps, rootMargin: opts.rootMargin || '0px' } );
			io.observe( el );
		}
		watch();
		// Đổi cỡ khung (xoay máy) làm mức cần đạt của phần tử cao đổi: dựng lại bộ quan sát với ngưỡng mới.
		off = on( 'resize', function () {
			if ( io && need() !== last ) {
				io.disconnect();
				watch();
			}
		} );
		return { disconnect: stop };
	}

	// Nhảy tức thì tới y.
	function jump( y ) {
		try {
			win.scrollTo( { left: 0, top: y, behavior: 'instant' } );
		} catch ( err ) {
			win.scrollTo( 0, y );
		}
	}

	// Cuộn mượt tới target (API công khai).
	function scrollTo( target, opts ) {
		var holder, y, dist, prev, tw, handle;
		// Dừng cuộn khi người dùng chạm hoặc bấm phím.
		function stop( e ) {
			if ( 'keydown' !== e.type || KEYS.indexOf( e.key ) > -1 ) {
				handle.cancel();
			}
		}
		// Kết thúc lần cuộn, gỡ người nghe.
		function finish( completed ) {
			if ( scrolling !== handle ) {
				return;
			}
			scrolling = null;
			html.style.scrollBehavior = prev;
			STOP.forEach( function ( type ) {
				win.removeEventListener( type, stop );
			} );
			if ( completed ) {
				run( holder, 'done' );
			}
		}
		opts = opts || {};
		holder = { name: 'scrollTo', done: opts.done };
		y = 'number' === typeof target ? target : ( target && 1 === target.nodeType ? target.getBoundingClientRect().top + pageY() : NaN );
		if ( ! isFinite( y ) ) {
			return { cancel: noop };
		}
		y = clamp( y + ( +opts.offset || 0 ), 0, Math.max( 0, html.scrollHeight - html.clientHeight ) );
		if ( scrolling ) {
			scrolling.cancel();
		}
		dist = Math.abs( y - pageY() );
		prev = html.style.scrollBehavior;
		html.style.scrollBehavior = 'auto';
		if ( modeReduced || dist < 1 || 0 === opts.duration ) {
			jump( y );
			html.style.scrollBehavior = prev;
			run( holder, 'done' );
			return { cancel: noop };
		}
		tw = tween( {
			duration: opts.duration > 0 ? +opts.duration : clamp( dist / 1800, 0.5, 1.4 ),
			from: pageY(), to: y, ease: 'cine', update: jump,
			done: function () {
				finish( true );
			}
		} );
		handle = {
			cancel: function () {
				tw.cancel();
				finish( false );
			}
		};
		scrolling = handle;
		STOP.forEach( function ( type ) {
			win.addEventListener( type, stop, { passive: true } );
		} );
		return handle;
	}

	// Nghe thay đổi của một media query.
	function listen( mq ) {
		if ( mq.addEventListener ) {
			mq.addEventListener( 'change', checkModes );
		} else if ( mq.addListener ) {
			mq.addListener( checkModes );
		}
	}
	// Xử lý đổi kích thước cửa sổ.
	function onResize() {
		checkModes();
		if ( modeReduced ) {
			readViewport();
			emit( 'resize', info() );
		} else {
			requestMeasure( true );
		}
	}

	readViewport();
	listen( mqCompact );
	listen( mqReduced );
	win.addEventListener( 'scroll', function () {
		if ( scenes.some( near ) ) {
			kick();
		}
	}, { passive: true } );
	win.addEventListener( 'resize', onResize );
	win.addEventListener( 'orientationchange', onResize );
	win.addEventListener( 'pageshow', function ( e ) {
		if ( e && e.persisted ) {
			scenes.forEach( function ( s ) {
				s.snap = s.snap || s.active;
				s.force = s.force || s.active;
			} );
			onResize();
		}
	} );
	doc.addEventListener( 'visibilitychange', function () {
		if ( hidden() ) {
			if ( raf ) {
				win.cancelAnimationFrame( raf );
				raf = 0;
			}
			last = 0;
		} else {
			checkModes();
			requestMeasure( false );
		}
	} );

	win.ISHA.motion = {
		started: true,
		hashEntry: !! win.location.hash,
		scene: scene,
		tween: tween,
		inView: inView,
		reduced: function () {
			return mqReduced.matches;
		},
		compact: function () {
			return mqCompact.matches;
		},
		on: on,
		scrollTo: scrollTo,
		clamp: clamp,
		lerp: lerp,
		seg: seg,
		ease: ease
	};
}() );
