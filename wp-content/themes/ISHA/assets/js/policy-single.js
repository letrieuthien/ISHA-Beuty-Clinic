/**
 * Trang chi tiết Chính sách – hai màn hình đầu (preloader → cổng thẻ kính → màn thẻ nổi).
 *
 * Không dùng thư viện. Trình tự:
 *   1. Mở đầu: chờ ảnh + font, bộ đếm chạy tới 100, cảnh phóng ra từ giữa màn hình, đèn thẻ chớp sáng, chữ hiện dần.
 *   2. Cuộn: màn hình ghim (CSS position: sticky). Tiến độ p (0 → 1) điều khiển máy quay tiến vào, thẻ quay thẳng,
 *      viền sáng lên, thẻ đầy ánh sáng và phóng kín màn hình; p ≥ 0.8 hiện màn 2. Cuộn ngược thì mọi thứ chạy lại ngược.
 *   3. Màn 2: thẻ nổi, nghiêng theo chuột, nút "Xem mặt sau" lật thẻ. Con trỏ kính chỉ hoạt động trong hai màn hình.
 * Mọi vị trí / kích thước hình thức do assets/css/policy-single.css quyết định; file này chỉ viết số liệu chuyển động.
 */
( function () {
	'use strict';

	var root = document.querySelector( '[data-isha-ps]' );
	if ( ! root ) {
		return;
	}

	var html = document.documentElement;

	/* ── Hằng số chuyển động (đổi cảm giác ở đây) ───────────────────────────── */
	var DIST = 1.5;                 // quãng cuộn chuyển cảnh, tính theo chiều cao màn hình
	var HOLD = 0.5;                 // cuộn thêm khi màn 2 đứng yên trước lúc nhả ghim (khớp CSS: track = màn hình + 2 × vh)
	var SMOOTH = 0.18;              // giây – độ "mượt" khi cuộn
	var DOLLY = 0.18;               // máy quay tiến vào 18%
	var DOLLY_SEG = [ 0, 0.7 ];     // đoạn p của máy quay
	var TURN_SEG = [ 0, 0.45 ];     // đoạn p thẻ quay thẳng mặt
	var GROW_SEG = [ 0.5, 0.82 ];   // đoạn p thẻ phóng ra phủ kín màn hình (lũy thừa 3)
	var LIGHT_OFF = [ 0.15, 0.4 ];  // đèn thẻ tắt dần
	var RIM_ON = [ 0.1, 0.4 ];      // viền sáng lên
	var FILL_ON = [ 0.35, 0.62 ];   // thẻ đầy ánh sáng
	var RISE_SEG = [ 0.2, 0.8 ];    // chữ màn 1 trồi lên 120u
	var READY_FADE = [ 0.79, 0.81 ];
	var READY_IN = 0.8;             // p ≥ 0.8: phát phần hiện màn 2
	var READY_OUT = 0.74;           // p < 0.74: lui lại
	var TILT_X = 6;                 // độ nghiêng tối đa của thẻ nổi theo chuột (độ)
	var TILT_Y = 8;
	var PLANE_W = 652;              // mặt phẳng thẻ dựng đứng, "px thẻ"
	var PLANE_H = 1024;
	// Bốn góc thẻ so với tâm thẻ (thẻ nhìn hơi chéo, cạnh phải xa hơn) và chiều cao trung bình của hai cạnh bên.
	var CARD = [ [ -310.5, -549.5 ], [ 310.5, -499.5 ], [ 310.5, 490.5 ], [ -310.5, 558.5 ] ];
	var CARD_H = 1049;

	var reduce = window.matchMedia( '(prefers-reduced-motion: reduce)' );
	var mobileMQ = window.matchMedia( '(orientation: portrait)' );   // khớp @media trong policy-single.css

	/* ── Toán học ─────────────────────────────────────────────────────────── */
	function clamp( v, a, b ) {
		return v < a ? a : ( v > b ? b : v );
	}
	function seg( t, a, b ) {
		return clamp( ( t - a ) / ( b - a ), 0, 1 );
	}
	function inOut( t ) {
		return t < 0.5 ? 2 * t * t : 1 - Math.pow( -2 * t + 2, 2 ) / 2;
	}
	function easeOut1( t ) { // power1.out
		return 1 - ( 1 - t ) * ( 1 - t );
	}
	function easeIn1( t ) { // power1.in
		return t * t;
	}
	function easeOut2( t ) { // power2.out
		return 1 - Math.pow( 1 - t, 3 );
	}
	function lerpQuad( a, b, t ) {
		return a.map( function ( p, i ) {
			return { x: p.x + ( b[ i ].x - p.x ) * t, y: p.y + ( b[ i ].y - p.y ) * t };
		} );
	}
	function scaleQuad( q, c, s ) {
		return q.map( function ( p ) {
			return { x: c.x + ( p.x - c.x ) * s, y: c.y + ( p.y - c.y ) * s };
		} );
	}
	function rectQuad( cx, cy, w, h ) {
		return [
			{ x: cx - w / 2, y: cy - h / 2 }, { x: cx + w / 2, y: cy - h / 2 },
			{ x: cx + w / 2, y: cy + h / 2 }, { x: cx - w / 2, y: cy + h / 2 }
		];
	}
	// matrix3d ánh xạ hình chữ nhật (0, 0, w, h) lên bốn góc q (dùng với transform-origin: 0 0).
	function quadToMatrix3d( w, h, q ) {
		var p0 = q[ 0 ], p1 = q[ 1 ], p2 = q[ 2 ], p3 = q[ 3 ];
		var dx1 = p1.x - p2.x, dy1 = p1.y - p2.y, dx2 = p3.x - p2.x, dy2 = p3.y - p2.y;
		var sx = p0.x - p1.x + p2.x - p3.x, sy = p0.y - p1.y + p2.y - p3.y;
		var det = dx1 * dy2 - dx2 * dy1;
		if ( ! det ) {
			return 'none';
		}
		var g = ( sx * dy2 - dx2 * sy ) / det, k = ( dx1 * sy - sx * dy1 ) / det;
		var a = p1.x - p0.x + g * p1.x, b = p3.x - p0.x + k * p3.x;
		var d = p1.y - p0.y + g * p1.y, e = p3.y - p0.y + k * p3.y;
		var m = [ a / w, d / w, 0, g / w, b / h, e / h, 0, k / h, 0, 0, 1, 0, p0.x, p0.y, 0, 1 ];
		return 'matrix3d(' + m.map( function ( v ) { return +v.toFixed( 8 ); } ).join( ',' ) + ')';
	}
	function pad2( v ) {
		return v < 10 ? '0' + v : String( v );
	}
	function noop() {}

	/* ── Phần tử ─────────────────────────────────────────────────────────── */
	function $( sel ) {
		return root.querySelector( sel );
	}
	var track = $( '.isha-ps__track' );
	var stage = $( '.isha-ps__stage' );
	var probe = $( '.isha-ps__probe' );
	var pre = $( '.isha-ps__pre' );
	var preWord = $( '.isha-ps__pre .isha-ps__word' );
	var countEl = $( '[data-ps-count]' );
	var media = $( '[data-ps-media]' );
	var over = $( '[data-ps-over]' );
	var scene = $( '[data-ps-scene]' );
	var glass = $( '[data-ps-plane="glass"]' );
	var light = $( '[data-ps-plane="light"]' );
	var portal = $( '[data-ps-plane="portal"]' );
	var rim = $( '.isha-ps__rim' );
	var fill = $( '.isha-ps__fill' );
	var hero = $( '[data-ps-hero]' );
	var ready = $( '[data-ps-ready]' );
	var tiltEl = $( '[data-ps-tilt]' );
	var flipper = $( '[data-ps-flipper]' );
	var cardEl = $( '[data-ps-card]' );

	if ( ! track || ! stage || ! media || ! scene || ! glass || ! light || ! portal || ! hero || ! ready ) {
		return;
	}

	/* ── Trạng thái ──────────────────────────────────────────────────────── */
	var S = { scale: 0, face: 0, flash: 0, p: 0 };   // 0 → 1; GSAP/cuộn ghi, render() đọc
	var W = 0, H = 0, U = 1, topPx = 0, distPx = 1, mobile = false;
	var introDone = false;
	var isIn = false;
	var lastSig = '';
	var raf = 0, last = 0;
	var tweens = [];
	var introStart = 0;
	var preloading = true;
	var shown = 0, goal = 0;
	var tilt = { tx: 0, ty: 0, x: 0, y: 0 };

	/* ── Đo kích thước ─────────────────────────────────────────────────────── */
	function measure() {
		// Header dính (nếu có) chiếm phần đầu màn hình; khung ghim nằm ngay dưới nó.
		var header = document.querySelector( '.site-header' );
		var top = 0;
		if ( header ) {
			var cs = window.getComputedStyle( header );
			if ( 'sticky' === cs.position || 'fixed' === cs.position ) {
				top = ( parseFloat( cs.top ) || 0 ) + header.offsetHeight;
			}
		}
		topPx = top;
		html.style.setProperty( '--ps-top', top + 'px' );

		W = stage.clientWidth;      // đọc sau khi đã đặt --ps-top
		H = stage.clientHeight;
		U = ( probe.getBoundingClientRect().width / 1000 ) || 1;
		mobile = mobileMQ.matches;
		distPx = Math.max( 1, ( track.offsetHeight - H ) * DIST / ( DIST + HOLD ) );
		lastSig = '';
	}

	function readTarget() {
		return clamp( ( topPx - track.getBoundingClientRect().top ) / distPx, 0, 1 );
	}

	/* ── Vẽ một khung hình ─────────────────────────────────────────────────── */
	var setIn = function ( on ) {
		if ( on === isIn ) {
			return;
		}
		isIn = on;
		ready.classList.toggle( 'is-in', on );
	};

	function render() {
		var p = S.p;
		var rm = reduce.matches;
		var sig = S.scale + '|' + S.face + '|' + S.flash + '|' + p + '|' + W + 'x' + H + '|' + rm;
		if ( sig === lastSig ) {
			return;
		}
		lastSig = sig;

		var sc = 'scale(' + S.scale + ')';
		media.style.transform = sc;
		over.style.transform = sc;

		// Thẻ đứng ở giữa cảnh, hơi chếch lên; cao theo thước u nhưng không vượt tỉ lệ của khung.
		var cardH = Math.min( ( mobile ? 330 : 403 ) * U, H * ( mobile ? 0.45 : 0.58 ) );
		var k = cardH / CARD_H;
		var c = { x: W / 2, y: H * ( mobile ? 0.5 : 0.46 ) };
		var base = CARD.map( function ( o ) {
			return { x: c.x + o[ 0 ] * k, y: c.y + o[ 1 ] * k };
		} );

		// Máy quay tiến vào: phóng cả cảnh quanh tâm thẻ, bốn góc thẻ đi theo.
		var d = rm ? 1 : 1 + DOLLY * inOut( seg( p, DOLLY_SEG[ 0 ], DOLLY_SEG[ 1 ] ) );
		scene.style.transformOrigin = c.x + 'px ' + c.y + 'px';
		scene.style.transform = 'scale(' + d + ')';
		var inScene = scaleQuad( base, c, d );

		// Thẻ quay thẳng mặt: các góc dần về hình chữ nhật dựng đứng quanh tâm thẻ.
		var h = ( ( inScene[ 3 ].y - inScene[ 0 ].y + inScene[ 2 ].y - inScene[ 1 ].y ) / 2 ) * 1.06;
		var turned = lerpQuad( inScene, rectQuad( c.x, c.y, h * PLANE_W / PLANE_H, h ), rm ? 0 : inOut( seg( p, TURN_SEG[ 0 ], TURN_SEG[ 1 ] ) ) );

		// Phóng kín: dần về hình chữ nhật tâm màn hình, lớn hơn màn hình 20%.
		var coverH = Math.max( H, W * PLANE_H / PLANE_W ) * 1.2;
		var cover = rectQuad( W / 2, H / 2, coverH * PLANE_W / PLANE_H, coverH );
		var quad = lerpQuad( turned, cover, rm ? 0 : Math.pow( seg( p, GROW_SEG[ 0 ], GROW_SEG[ 1 ] ), 3 ) );

		var m = quadToMatrix3d( PLANE_W, PLANE_H, quad );
		glass.style.transform = m;
		light.style.transform = m;
		portal.style.transform = m;

		light.style.opacity = String( S.face * ( rm ? 1 : 1 - seg( p, LIGHT_OFF[ 0 ], LIGHT_OFF[ 1 ] ) ) );
		rim.style.opacity = String( rm ? 0 : Math.max( S.flash, seg( p, RIM_ON[ 0 ], RIM_ON[ 1 ] ) ) );
		fill.style.opacity = String( rm ? 0 : seg( p, FILL_ON[ 0 ], FILL_ON[ 1 ] ) );

		hero.style.transform = rm ? '' : 'translateY(' + ( -120 * U * seg( p, RISE_SEG[ 0 ], RISE_SEG[ 1 ] ) ).toFixed( 2 ) + 'px)';

		// Màn 2: hiện ra khi cổng đã phủ kín (giảm chuyển động: hòa vào bằng cuộn, không có cổng).
		var op = rm ? seg( p, 0.3, 0.6 ) : seg( p, READY_FADE[ 0 ], READY_FADE[ 1 ] );
		ready.style.opacity = String( op );
		ready.style.visibility = op > 0 ? 'visible' : 'hidden';
		if ( 'inert' in hero ) {
			hero.inert = op > 0.5;   // chữ màn 1 nằm dưới cổng thì không nhận bàn phím / chuột
		}
		if ( rm ) {
			if ( ! isIn && op >= 1 ) {
				setIn( true );
			} else if ( isIn && p < 0.3 ) {
				setIn( false );
			}
		} else if ( ! isIn && p >= READY_IN ) {
			setIn( true );
		} else if ( isIn && p < READY_OUT ) {
			setIn( false );
		}
	}

	/* ── Vòng lặp: chỉ chạy khi còn việc (cuộn, mở đầu, nghiêng thẻ) ─────────────── */
	function tween( delay, dur, from, to, ease, set ) {
		tweens.push( { at: delay * 1000, dur: dur * 1000, from: from, to: to, ease: ease, set: set, done: false } );
	}
	// Các giá trị chia đều thời lượng (như keyframes dạng mảng của GSAP); ease áp lên toàn bộ tiến độ.
	function keyframes( delay, dur, values, ease, set ) {
		var n = values.length - 1;
		tween( delay, dur, 0, 1, function ( t ) {
			return t;
		}, function ( t ) {
			var e = ease( t );
			var i = Math.min( n - 1, Math.floor( e * n ) );
			set( values[ i ] + ( values[ i + 1 ] - values[ i ] ) * ( e * n - i ) );
		} );
	}
	function linear( t ) {
		return t;
	}

	function kick() {
		if ( ! raf ) {
			last = performance.now();
			raf = window.requestAnimationFrame( frame );
		}
	}

	function frame( now ) {
		raf = 0;
		var dt = Math.min( 0.1, ( now - last ) / 1000 );
		last = now;
		var busy = false;

		if ( preloading ) {
			shown += ( goal - shown ) * ( 1 - Math.exp( -dt / 0.4 ) );
			if ( countEl ) {
				countEl.textContent = pad2( Math.round( shown ) );
			}
			busy = true;
		}

		if ( tweens.length ) {
			var el = now - introStart;
			var alive = false;
			tweens.forEach( function ( tw ) {
				if ( tw.done ) {
					return;
				}
				var t = ( el - tw.at ) / tw.dur;
				if ( t < 0 ) {
					alive = true;
					return;
				}
				if ( t >= 1 ) {
					tw.set( tw.to );
					tw.done = true;
					return;
				}
				tw.set( tw.from + ( tw.to - tw.from ) * tw.ease( t ) );
				alive = true;
			} );
			if ( alive ) {
				busy = true;
			} else {
				tweens = [];
			}
		}

		if ( introDone ) {
			var target = readTarget();
			var diff = target - S.p;
			if ( Math.abs( diff ) > 0.0004 ) {
				S.p += diff * ( 1 - Math.exp( -dt / SMOOTH ) );
				busy = true;
			} else {
				S.p = target;
			}
			if ( isIn && ! reduce.matches ) {
				var f = 1 - Math.exp( -dt / 0.25 );
				tilt.x += ( tilt.tx - tilt.x ) * f;
				tilt.y += ( tilt.ty - tilt.y ) * f;
				if ( tiltEl ) {
					tiltEl.style.transform = 'rotateX(' + tilt.x.toFixed( 2 ) + 'deg) rotateY(' + tilt.y.toFixed( 2 ) + 'deg)';
				}
				if ( Math.abs( tilt.tx - tilt.x ) > 0.01 || Math.abs( tilt.ty - tilt.y ) > 0.01 ) {
					busy = true;
				}
			}
		}

		render();
		if ( busy ) {
			kick();
		}
	}

	/* ── Mở đầu ──────────────────────────────────────────────────────────── */
	function unlock() {
		html.classList.remove( 'isha-ps-lock' );
		root.classList.add( 'is-done' );
		if ( pre ) {
			pre.style.visibility = 'hidden';
		}
		introDone = true;
		measure();
		kick();
	}

	function startIntro() {
		window.clearTimeout( window.ishaPsGuard );
		preloading = false;
		measure();

		if ( reduce.matches ) {
			// Giảm chuyển động: không đếm số, không phóng; cảnh + chữ cùng hiện mờ dần trong 0.4 giây.
			if ( pre ) {
				pre.style.display = 'none';
			}
			S.scale = 1;
			S.face = 1;
			root.classList.add( 'is-live' );
			render();
			window.requestAnimationFrame( function () {
				root.classList.add( 'is-go' );
			} );
			window.setTimeout( unlock, 450 );
			return;
		}

		introStart = performance.now();
		root.classList.add( 'is-live', 'is-go' );
		tween( 0, 0.5, shown, 100, easeOut1, function ( v ) {
			if ( countEl ) {
				countEl.textContent = pad2( Math.round( v ) );
			}
		} );
		tween( 0.5, 0.15, 1, 0, easeIn1, function ( v ) {
			if ( pre ) {
				var n = pre.querySelector( '.isha-ps__count' );
				if ( n ) {
					n.style.opacity = String( v );
				}
			}
		} );
		tween( 0.6, 0.85, 0, 1, easeOut2, function ( v ) {
			S.scale = v;
		} );
		tween( 0.7, 0.4, 1, 0, easeOut1, function ( v ) {
			if ( preWord ) {
				preWord.style.opacity = String( 0.15 * v );
			}
		} );
		keyframes( 1.05, 0.45, [ 0, 0.8, 0.15, 1, 0.55, 1 ], linear, function ( v ) {
			S.face = v;
		} );
		keyframes( 1.05, 0.7, [ 0, 1, 0 ], easeOut1, function ( v ) {
			S.flash = v;
		} );
		window.setTimeout( unlock, 2300 );
		kick();
	}

	// Vào thẳng một vị trí có neo (#isha-chi-tiet): bỏ mở đầu, hiện luôn ở trạng thái cuối.
	function startInstant() {
		window.clearTimeout( window.ishaPsGuard );
		preloading = false;
		root.classList.add( 'is-instant', 'is-live', 'is-go' );
		S.scale = 1;
		S.face = 1;
		if ( pre ) {
			pre.style.display = 'none';
		}
		introDone = true;
		html.classList.remove( 'isha-ps-lock' );
		root.classList.add( 'is-done' );
		measure();
		S.p = readTarget();
		render();
		window.requestAnimationFrame( function () {
			window.requestAnimationFrame( function () {
				root.classList.remove( 'is-instant' );
			} );
		} );
		kick();
	}

	// Chữ khổng lồ mờ dùng logo làm mask CSS; mask đòi ảnh phải tải được theo CORS. Không tải được thì ẩn chữ đó (CSS .is-nomask).
	function checkLogoMask() {
		var raw = window.getComputedStyle( root ).getPropertyValue( '--isha-ps-logo' );
		var m = /url\(\s*["']?([^"')]+)["']?\s*\)/.exec( raw );
		if ( ! m ) {
			return;
		}
		var img = new window.Image();
		img.crossOrigin = 'anonymous';
		img.onerror = function () {
			root.classList.add( 'is-nomask' );
		};
		img.src = m[ 1 ];
	}

	// Chờ ảnh (giải mã xong) và font; hết 6,5 giây vẫn chưa xong thì chạy luôn.
	function whenLoaded() {
		var imgs = [].slice.call( root.querySelectorAll( 'img' ) );
		var fonts = [ '500 1em "Montserrat"', '600 1em "Montserrat"', '400 1em "Garamond Premier Pro"', 'italic 400 1em "Garamond Premier Pro"' ];
		var total = imgs.length + fonts.length;
		var done = 0;
		var tick = function () {
			done++;
			goal = 90 * done / total;
			kick();
		};
		var jobs = imgs.map( function ( img ) {
			var job;
			if ( img.complete && img.naturalWidth ) {
				job = Promise.resolve();
			} else if ( img.decode ) {
				job = img.decode();
			} else {
				job = new Promise( function ( resolve, reject ) {
					img.addEventListener( 'load', resolve, { once: true } );
					img.addEventListener( 'error', reject, { once: true } );
				} );
			}
			return job.catch( function ( err ) {
				window.console && window.console.warn && window.console.warn( 'isha-ps: ảnh lỗi', err );
			} ).then( tick );
		} );
		var fontJobs = fonts.map( function ( f ) {
			return ( document.fonts && document.fonts.load ? document.fonts.load( f ) : Promise.resolve() ).catch( noop ).then( tick );
		} );
		var all = Promise.all( jobs.concat( fontJobs ) );
		var timeout = new Promise( function ( resolve ) {
			window.setTimeout( resolve, 6500 );
		} );
		return Promise.race( [ all, timeout ] );
	}

	/* ── Nghiêng thẻ theo chuột, lật thẻ ────────────────────────────────────── */
	window.addEventListener( 'pointermove', function ( e ) {
		tilt.ty = ( ( e.clientX / window.innerWidth ) - 0.5 ) * 2 * TILT_Y;
		tilt.tx = -( ( e.clientY / window.innerHeight ) - 0.5 ) * 2 * TILT_X;
		if ( isIn ) {
			kick();
		}
	}, { passive: true } );

	// Bấm vào thẻ (hoặc Enter / Space khi thẻ đang được focus) để lật mặt trước / mặt sau.
	if ( cardEl && flipper && cardEl.classList.contains( 'is-flippable' ) ) {
		var toggleFlip = function () {
			var on = flipper.classList.toggle( 'is-flipped' );
			cardEl.setAttribute( 'aria-pressed', on ? 'true' : 'false' );
			cardEl.setAttribute( 'aria-label', cardEl.getAttribute( on ? 'data-label-front' : 'data-label-back' ) );
		};
		cardEl.addEventListener( 'click', toggleFlip );
		cardEl.addEventListener( 'keydown', function ( e ) {
			if ( 'Enter' === e.key || ' ' === e.key ) {
				e.preventDefault();
				toggleFlip();
			}
		} );
	}

	/* ── Con trỏ kính (chỉ có chuột; chỉ trong hai màn hình) ─────────────────── */
	function initCursor() {
		var disc = document.createElement( 'div' );
		disc.className = 'isha-ps-cursor';
		disc.setAttribute( 'aria-hidden', 'true' );
		document.body.appendChild( disc );

		var x = 0, y = 0, tx = 0, ty = 0, seen = false, on = false, running = false, prev = 0;

		function setOn( v ) {
			if ( v === on ) {
				return;
			}
			on = v;
			disc.classList.toggle( 'is-on', v );
			html.classList.toggle( 'isha-ps-cursor-on', v );
		}
		function loop( now ) {
			var k = 1 - Math.pow( 0.7, ( now - prev ) / 16.67 );   // mỗi khung 60 fps đi 30% quãng còn lại
			prev = now;
			x += ( tx - x ) * k;
			y += ( ty - y ) * k;
			disc.style.transform = 'translate3d(' + x.toFixed( 1 ) + 'px,' + y.toFixed( 1 ) + 'px,0)';
			if ( Math.abs( tx - x ) > 0.1 || Math.abs( ty - y ) > 0.1 ) {
				window.requestAnimationFrame( loop );
			} else {
				running = false;
			}
		}
		document.addEventListener( 'pointermove', function ( e ) {
			if ( e.pointerType && 'mouse' !== e.pointerType ) {
				return;
			}
			tx = e.clientX;
			ty = e.clientY;
			if ( ! seen ) {
				seen = true;
				x = tx;
				y = ty;
			}
			setOn( stage.contains( e.target ) );
			if ( ! running ) {
				running = true;
				prev = performance.now();
				window.requestAnimationFrame( loop );
			}
		}, { passive: true } );
		document.addEventListener( 'mouseout', function ( e ) {
			if ( ! e.relatedTarget ) {
				setOn( false );
			}
		} );
		// Cuộn không phát sự kiện di chuột: nội dung trượt qua dưới con trỏ đứng yên thì kiểm tra lại xem còn nằm trong hai màn hình không.
		var checking = false;
		window.addEventListener( 'scroll', function () {
			if ( ! seen || checking ) {
				return;
			}
			checking = true;
			window.requestAnimationFrame( function () {
				checking = false;
				var el = document.elementFromPoint( tx, ty );
				setOn( !! el && stage.contains( el ) );
			} );
		}, { passive: true } );
	}
	if ( window.matchMedia( '(hover: hover) and (pointer: fine)' ).matches ) {
		initCursor();
	}

	/* ── Khởi động ───────────────────────────────────────────────────────── */
	window.addEventListener( 'scroll', kick, { passive: true } );
	window.addEventListener( 'resize', function () {
		measure();
		kick();
	} );
	if ( 'ResizeObserver' in window ) {
		new window.ResizeObserver( function () {
			measure();
			kick();
		} ).observe( stage );
	}

	// Kích hoạt bằng hash (vd. #isha-chi-tiet) thì không phát lại mở đầu; còn lại luôn bắt đầu từ đầu trang.
	if ( window.location.hash ) {
		checkLogoMask();
		startInstant();
		return;
	}
	if ( 'scrollRestoration' in window.history ) {
		window.history.scrollRestoration = 'manual';
	}
	window.scrollTo( 0, 0 );
	checkLogoMask();
	measure();
	render();
	if ( ! reduce.matches ) {
		kick();   // chạy bộ đếm trong lúc chờ tải
	}
	whenLoaded().then( startIntro, startIntro );
}() );
