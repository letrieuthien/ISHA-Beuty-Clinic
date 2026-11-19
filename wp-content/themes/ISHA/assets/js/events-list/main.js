/**
 * Section Workshop & Sự kiện của trang chủ (template-parts/home/events.php). Script module, nạp ở mọi lượt xem trang chủ; nhỏ, không phụ
 * thuộc thư viện, không import gì, không nằm trong ngân sách JS của home.js.
 *
 *   - Rê chuột (mouseenter) hoặc focus bàn phím (focusin) vào một dòng thì dòng đó thành .is-active và ảnh ở khung trái chuyển mờ dần sang
 *     ảnh của sự kiện đó (CSS lo hiệu ứng; ở thẻ cảm ứng khung trái bị ẩn nên việc đổi class vô hại).
 *   - Bộ lọc loại sự kiện: bỏ `hidden` của khối lọc (HTML tĩnh giấu nó vì không có JS thì không dùng được). Bấm một tab thì chỉ hiện các dòng
 *     có tab đó trong data-tabs (PHP đã chọn sẵn đủ tối đa 3 dòng cho mỗi tab), ảnh khung trái về dòng đầu tiên của danh sách mới. Ảnh của
 *     dòng chưa hiện nằm trong `hidden` (display: none) nên không tải; hiện ra mới tải. Báo số dòng cho trình đọc màn hình qua vùng status.
 *   - Giảm chuyển động: đổi tức thì, không chờ hiệu ứng mờ.
 *
 * Không JS, lỗi JS: danh sách tĩnh với ba sự kiện của "Tất cả", dòng đầu đang chọn, mọi liên kết vẫn dùng được.
 */
( function () {
	'use strict';

	var box = document.querySelector( '[data-isha-events]' );
	if ( ! box ) {
		return;
	}

	var items = Array.prototype.slice.call( box.querySelectorAll( '.isha-events__item' ) );
	var shots = Array.prototype.slice.call( box.querySelectorAll( '.isha-events__shot' ) );
	var filter = document.querySelector( '[data-isha-events-filter]' );
	var tabs = filter ? Array.prototype.slice.call( filter.querySelectorAll( '.isha-events__tab' ) ) : [];
	var status = box.querySelector( '[data-isha-events-status]' );
	var statusText = box.getAttribute( 'data-status' ) || '';
	var reduce = window.matchMedia ? window.matchMedia( '(prefers-reduced-motion: reduce)' ) : { matches: false };

	var FADE_OUT = 180;   // ms: danh sách mờ đi trước khi đổi tab
	var SHOT_FADE = 650;  // ms: lớn hơn thời gian mờ của ảnh (CSS 520ms) để ẩn ảnh cũ sau khi nó mờ xong

	var current = '';
	var tab = 'all';
	var timer = 0;
	var sweep = 0;

	function shotOf( id ) {
		var i;
		for ( i = 0; i < shots.length; i++ ) {
			if ( shots[ i ].getAttribute( 'data-event' ) === id ) {
				return shots[ i ];
			}
		}
		return null;
	}

	function inTab( li, slug ) {
		return ( ' ' + ( li.getAttribute( 'data-tabs' ) || '' ) + ' ' ).indexOf( ' ' + slug + ' ' ) > -1;
	}

	// Đặt dòng có id làm dòng đang chọn và chuyển ảnh khung trái sang ảnh của nó.
	function activate( id ) {
		var next = shotOf( id );

		if ( ! id || id === current ) {
			return;
		}
		current = id;
		items.forEach( function ( li ) {
			li.classList.toggle( 'is-active', li.getAttribute( 'data-event' ) === id );
		} );
		if ( next ) {
			if ( next.hidden ) {
				next.hidden = false;
				void next.offsetWidth; // Để trình duyệt tính kiểu mờ trước khi thêm .is-active, ảnh mới có hiệu ứng mờ dần.
			}
			shots.forEach( function ( s ) {
				s.classList.toggle( 'is-active', s === next );
			} );
		}
	}

	// Ẩn ảnh của các dòng không thuộc tab hiện tại (sau khi ảnh cũ mờ xong), để ảnh đó không tải và không nằm trong luồng.
	function sweepShots() {
		window.clearTimeout( sweep );
		sweep = window.setTimeout( function () {
			shots.forEach( function ( s ) {
				var li = box.querySelector( '.isha-events__item[data-event="' + s.getAttribute( 'data-event' ) + '"]' );
				if ( li && li.hidden && ! s.classList.contains( 'is-active' ) ) {
					s.hidden = true;
				}
			} );
		}, reduce.matches ? 0 : SHOT_FADE );
	}

	function announce( n ) {
		if ( status && statusText ) {
			status.textContent = statusText.replace( '%d', String( n ) );
		}
	}

	function setTab( slug ) {
		var first = null;
		var shown = 0;

		if ( slug === tab ) {
			return;
		}
		tab = slug;
		tabs.forEach( function ( t ) {
			var on = t.getAttribute( 'data-tab' ) === slug;
			t.classList.toggle( 'is-active', on );
			t.setAttribute( 'aria-pressed', on ? 'true' : 'false' );
		} );

		window.clearTimeout( timer );
		box.classList.add( 'is-filtering' );
		timer = window.setTimeout( function () {
			items.forEach( function ( li ) {
				var on = inTab( li, slug );
				var shot = shotOf( li.getAttribute( 'data-event' ) );

				li.hidden = ! on;
				if ( on ) {
					shown++;
					first = first || li;
					// Ảnh của dòng đầu để activate() hiện (kèm ép tính kiểu cho hiệu ứng mờ); ảnh các dòng khác hiện sẵn (đang mờ 0).
					if ( shot && shot.hidden && li !== first ) {
						shot.hidden = false;
					}
				}
			} );
			if ( first ) {
				current = ''; // Ép activate() chạy kể cả khi dòng đầu trùng dòng đang chọn trước đó.
				activate( first.getAttribute( 'data-event' ) );
			}
			sweepShots();
			announce( shown );
			box.classList.remove( 'is-filtering' );
		}, reduce.matches ? 0 : FADE_OUT );
	}

	var firstActive = box.querySelector( '.isha-events__item.is-active' );
	current = firstActive ? firstActive.getAttribute( 'data-event' ) : '';

	items.forEach( function ( li ) {
		var id = li.getAttribute( 'data-event' );

		li.addEventListener( 'mouseenter', function () {
			activate( id );
		} );
		li.addEventListener( 'focusin', function () {
			activate( id );
		} );
	} );

	if ( filter && tabs.length ) {
		tabs.forEach( function ( t ) {
			t.addEventListener( 'click', function () {
				setTab( t.getAttribute( 'data-tab' ) || 'all' );
			} );
		} );
		filter.hidden = false;
	}
}() );
