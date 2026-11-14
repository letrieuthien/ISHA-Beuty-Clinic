/**
 * ISHA Core: meta box "Nội dung trang chủ", phần thẻ (tab).
 *
 * Markup do ISHA_Core_Home_Fields::render_meta_box() in ra (xem includes/class-home-fields.php):
 *   div.isha-home
 *     div[role=tablist] > button[role=tab][data-section][aria-controls][aria-selected]
 *     div[role=tabpanel][hidden khi không chọn]  (mọi panel đều chứa đủ trường nên một lần gửi form mang toàn bộ dữ liệu)
 *
 * Hành vi: bấm hoặc dùng phím Trái/Phải/Home/End (tabindex luân phiên, cuối vòng quay về đầu) để đổi thẻ; địa chỉ có
 * dạng #isha-home-<section> thì mở thẻ đó (khi tải trang và khi hash đổi). Không lưu gì vào bộ nhớ của trình duyệt:
 * thẻ đang mở chỉ được ghi vào hash của địa chỉ (history.replaceState, không thêm mục lịch sử, không cuộn trang).
 */
( function () {
	'use strict';

	var HASH_PREFIX = '#isha-home-';

	/**
	 * Gắn hành vi thẻ cho một meta box.
	 *
	 * @param {Element} root Phần tử .isha-home.
	 */
	function initTabs( root ) {
		var tabs = Array.prototype.slice.call( root.querySelectorAll( '[role="tab"]' ) );
		var rtl = window.getComputedStyle( root ).direction === 'rtl';

		if ( ! tabs.length ) {
			return;
		}

		/**
		 * Mở thẻ có chỉ số cho trước: đồng bộ aria-selected, tabindex và thuộc tính hidden của panel.
		 *
		 * @param {number}  index Chỉ số thẻ.
		 * @param {boolean} focus Có chuyển focus vào thẻ không.
		 */
		function select( index, focus ) {
			tabs.forEach( function ( tab, i ) {
				var active = i === index;
				var panel = document.getElementById( tab.getAttribute( 'aria-controls' ) );

				tab.setAttribute( 'aria-selected', active ? 'true' : 'false' );
				tab.tabIndex = active ? 0 : -1;
				if ( panel ) {
					panel.hidden = ! active;
				}
			} );

			if ( focus ) {
				tabs[ index ].focus();
			}
		}

		/**
		 * Chỉ số thẻ ứng với một hash dạng #isha-home-<section>, -1 nếu không khớp.
		 *
		 * @param {string} hash window.location.hash.
		 * @return {number}
		 */
		function indexFromHash( hash ) {
			var section;
			var i;

			if ( ! hash || hash.indexOf( HASH_PREFIX ) !== 0 ) {
				return -1;
			}

			section = hash.slice( HASH_PREFIX.length );
			for ( i = 0; i < tabs.length; i++ ) {
				if ( tabs[ i ].getAttribute( 'data-section' ) === section ) {
					return i;
				}
			}
			return -1;
		}

		/**
		 * Ghi thẻ đang mở vào hash để tải lại trang vẫn giữ thẻ (không tạo mục lịch sử, không cuộn).
		 *
		 * @param {number} index Chỉ số thẻ.
		 */
		function remember( index ) {
			var hash = HASH_PREFIX + tabs[ index ].getAttribute( 'data-section' );

			if ( window.location.hash === hash || ! window.history || typeof window.history.replaceState !== 'function' ) {
				return;
			}

			try {
				window.history.replaceState( window.history.state, '', hash );
			} catch ( error ) {
				// Một số khung nhúng chặn replaceState: bỏ qua, thẻ vẫn đổi bình thường.
			}
		}

		root.addEventListener( 'click', function ( event ) {
			var tab = event.target.closest ? event.target.closest( '[role="tab"]' ) : null;
			var index = tab ? tabs.indexOf( tab ) : -1;

			if ( index > -1 ) {
				select( index, false );
				remember( index );
			}
		} );

		root.addEventListener( 'keydown', function ( event ) {
			var index = tabs.indexOf( event.target );
			var next;

			if ( index < 0 || event.altKey || event.ctrlKey || event.metaKey ) {
				return;
			}

			switch ( event.key ) {
				case 'ArrowRight':
					next = rtl ? index - 1 : index + 1;
					break;
				case 'ArrowLeft':
					next = rtl ? index + 1 : index - 1;
					break;
				case 'Home':
					next = 0;
					break;
				case 'End':
					next = tabs.length - 1;
					break;
				default:
					return;
			}

			event.preventDefault();
			next = ( next + tabs.length ) % tabs.length;
			select( next, true );
			remember( next );
		} );

		window.addEventListener( 'hashchange', function () {
			var index = indexFromHash( window.location.hash );

			if ( index > -1 ) {
				select( index, false );
			}
		} );

		// Địa chỉ có hash của một section thì mở thẻ đó; không thì giữ thẻ đầu tiên do server đặt.
		var start = indexFromHash( window.location.hash );
		if ( start > -1 ) {
			select( start, false );
		}
	}

	function init() {
		Array.prototype.forEach.call( document.querySelectorAll( '.isha-home' ), initTabs );
	}

	if ( document.readyState === 'loading' ) {
		document.addEventListener( 'DOMContentLoaded', init );
	} else {
		init();
	}
}() );
