/**
 * ISHA Core: ô chọn media dùng chung (ảnh đơn, video, gallery) trong trang quản trị.
 *
 * Markup do ISHA_Core_Admin_Media::render() in ra (xem includes/class-admin-media.php):
 *   div.isha-media[data-type][data-multiple][data-max][data-title][data-button]
 *     input[type=hidden]        ID (image, video) hoặc "id,id,..." theo thứ tự (gallery)
 *     div.isha-media__preview   xem trước, được dựng lại bằng DOM API (tiêu đề tệp do người dùng nhập nên không dùng innerHTML)
 *     button.isha-media__choose, button.isha-media__clear, span.isha-media__status (vùng thông báo cho trình đọc màn hình)
 *
 * Mỗi lần giá trị đổi, div.isha-media phát CustomEvent 'isha-media:change' (bubbles) với detail = { type, value, ids }.
 * Dùng ủy quyền sự kiện trên document nên chạy với mọi số ô trên trang, kể cả ô được chèn thêm sau.
 * Không có wp.media thì chỉ báo lỗi khi bấm nút chọn; không ném ngoại lệ lúc nạp.
 */
( function () {
	'use strict';

	var DEFAULT_MAX = 24;

	// Chuỗi dự phòng, trùng khoá với ISHA_Core_Admin_Media::strings(). Chuỗi thật (đã dịch) do PHP truyền qua window.ishaAdminMedia.
	var FALLBACK = {
		chooseImage: 'Chọn ảnh',
		changeImage: 'Đổi ảnh',
		chooseVideo: 'Chọn video',
		changeVideo: 'Đổi video',
		addImages: 'Thêm ảnh',
		clearImage: 'Xoá ảnh',
		clearVideo: 'Xoá video',
		clearAll: 'Xoá tất cả',
		prev: 'Trước',
		next: 'Sau',
		remove: 'Xoá',
		moveUpLabel: 'Chuyển ảnh %d lên trước',
		moveDownLabel: 'Chuyển ảnh %d ra sau',
		removeLabel: 'Xoá ảnh %d',
		none: 'Chưa chọn',
		frameTitleImage: 'Chọn ảnh',
		frameTitleVideo: 'Chọn video',
		frameTitleGallery: 'Chọn các ảnh',
		frameButtonImage: 'Dùng ảnh này',
		frameButtonVideo: 'Dùng video này',
		frameButtonGallery: 'Dùng các ảnh đã chọn',
		moved: 'Ảnh đang ở vị trí %1$d trên %2$d.',
		removed: 'Đã xoá ảnh %d.',
		chosen: 'Đã chọn: %s',
		galleryCount: 'Danh sách có %d ảnh.',
		limit: 'Chỉ giữ tối đa %d ảnh, các ảnh còn lại đã bị bỏ qua.',
		unavailable: 'Không mở được thư viện media. Hãy tải lại trang rồi thử lại.'
	};

	var strings = window.ishaAdminMedia || {};
	var frames = typeof WeakMap === 'function' ? new WeakMap() : null;

	/* --- Tiện ích ---------------------------------------------------------- */

	function t( key ) {
		var value = strings[ key ];
		return typeof value === 'string' && value !== '' ? value : ( FALLBACK[ key ] || '' );
	}

	/**
	 * Thay %d, %s, %1$d, %2$d trong mẫu chuỗi bằng các đối số.
	 *
	 * @param {string} template Mẫu chuỗi.
	 * @return {string}
	 */
	function format( template ) {
		var args = Array.prototype.slice.call( arguments, 1 );
		var next = 0;

		return String( template ).replace( /%(?:(\d+)\$)?[ds]/g, function ( match, position ) {
			var index = position ? parseInt( position, 10 ) - 1 : next++;
			return index < args.length ? String( args[ index ] ) : match;
		} );
	}

	function toInt( value ) {
		var number = parseInt( value, 10 );
		return isNaN( number ) ? 0 : number;
	}

	function capitalize( text ) {
		return text.charAt( 0 ).toUpperCase() + text.slice( 1 );
	}

	function make( tag, className, text ) {
		var node = document.createElement( tag );

		if ( className ) {
			node.className = className;
		}
		if ( text ) {
			node.textContent = text;
		}
		return node;
	}

	function makeButton( className, text ) {
		var button = make( 'button', className, text );

		button.type = 'button';
		return button;
	}

	function makeImage( src ) {
		var image = document.createElement( 'img' );

		image.src = src;
		image.alt = '';
		return image;
	}

	/**
	 * Chuyển focus tới phần tử đầu tiên còn dùng được trong danh sách.
	 *
	 * @param {Array} nodes Các phần tử ứng viên (có thể là null).
	 */
	function focusFirst( nodes ) {
		var index;

		for ( index = 0; index < nodes.length; index++ ) {
			if ( nodes[ index ] && ! nodes[ index ].disabled && ! nodes[ index ].hidden ) {
				nodes[ index ].focus();
				return;
			}
		}
	}

	/* --- Truy cập cấu trúc của ô ------------------------------------------ */

	function typeOf( wrapper ) {
		var type = wrapper.getAttribute( 'data-type' );

		return type === 'video' || type === 'gallery' ? type : 'image';
	}

	function maxOf( wrapper ) {
		var max = toInt( wrapper.getAttribute( 'data-max' ) );

		return max > 0 ? Math.min( max, DEFAULT_MAX ) : DEFAULT_MAX;
	}

	function inputOf( wrapper ) {
		return wrapper.querySelector( 'input[type="hidden"]' );
	}

	function currentIds( wrapper ) {
		var input = inputOf( wrapper );

		if ( ! input || input.value === '' ) {
			return [];
		}
		return input.value.split( ',' ).map( toInt ).filter( function ( id ) {
			return id > 0;
		} );
	}

	function announce( wrapper, message ) {
		var status = wrapper.querySelector( '.isha-media__status' );

		if ( status ) {
			status.textContent = message || '';
		}
	}

	function notify( wrapper ) {
		var input = inputOf( wrapper );

		wrapper.dispatchEvent( new CustomEvent( 'isha-media:change', {
			bubbles: true,
			detail: {
				type: typeOf( wrapper ),
				value: input ? input.value : '',
				ids: currentIds( wrapper )
			}
		} ) );
	}

	function thumbUrl( data ) {
		return data.sizes && data.sizes.thumbnail && data.sizes.thumbnail.url ? data.sizes.thumbnail.url : data.url;
	}

	/* --- Ảnh đơn và video -------------------------------------------------- */

	/**
	 * Dựng lại phần xem trước của ảnh đơn hoặc video từ JSON của attachment (null = chưa chọn).
	 * Cấu trúc khớp với ISHA_Core_Admin_Media::render_single_preview().
	 *
	 * @param {Element}     wrapper div.isha-media.
	 * @param {Object|null} data    JSON của attachment.
	 */
	function renderSingle( wrapper, data ) {
		var preview = wrapper.querySelector( '.isha-media__preview' );
		var name, thumb, video, src, info;

		if ( ! preview ) {
			return;
		}
		preview.textContent = '';

		if ( ! data ) {
			preview.appendChild( make( 'p', 'isha-media__empty', t( 'none' ) ) );
			return;
		}

		name = data.title || data.filename || '';

		if ( typeOf( wrapper ) === 'video' ) {
			thumb = make( 'div', 'isha-media__thumb isha-media__thumb--video' );
			if ( data.url ) {
				video = document.createElement( 'video' );
				video.src = data.url;
				video.muted = true;
				video.controls = true;
				video.setAttribute( 'muted', '' );
				video.setAttribute( 'playsinline', '' );
				video.setAttribute( 'preload', 'metadata' );
				if ( name ) {
					video.setAttribute( 'aria-label', name );
				}
				thumb.appendChild( video );
			}
		} else {
			thumb = make( 'div', 'isha-media__thumb' );
			src = thumbUrl( data );
			if ( src ) {
				thumb.appendChild( makeImage( src ) );
			}
		}
		preview.appendChild( thumb );

		info = make( 'div', 'isha-media__info' );
		info.appendChild( make( 'span', 'isha-media__name', name ) );
		info.appendChild( make( 'span', 'isha-media__mime', data.mime || '' ) );
		preview.appendChild( info );
	}

	/**
	 * Cập nhật chữ nút chọn/đổi và nút xoá theo việc đã có giá trị hay chưa.
	 *
	 * @param {Element} wrapper div.isha-media (ảnh đơn hoặc video).
	 */
	function refreshSingle( wrapper ) {
		var input = inputOf( wrapper );
		var choose = wrapper.querySelector( '.isha-media__choose' );
		var clear = wrapper.querySelector( '.isha-media__clear' );
		var video = typeOf( wrapper ) === 'video';
		var has = !! input && input.value !== '';

		if ( choose ) {
			choose.textContent = t( has ? ( video ? 'changeVideo' : 'changeImage' ) : ( video ? 'chooseVideo' : 'chooseImage' ) );
		}
		if ( clear ) {
			clear.hidden = ! has;
		}
	}

	function setSingle( wrapper, data ) {
		var input = inputOf( wrapper );
		var before;

		if ( ! input ) {
			return;
		}
		before = input.value;
		input.value = data ? String( data.id ) : '';
		renderSingle( wrapper, data );
		refreshSingle( wrapper );

		if ( before !== input.value ) {
			notify( wrapper );
		}
	}

	/* --- Gallery ----------------------------------------------------------- */

	function galleryList( wrapper ) {
		var list = wrapper.querySelector( '.isha-media__list' );
		var preview = wrapper.querySelector( '.isha-media__preview' );

		if ( ! list && preview ) {
			list = make( 'ul', 'isha-media__list' );
			list.setAttribute( 'role', 'list' );
			preview.appendChild( list );
		}
		return list;
	}

	function itemId( item ) {
		return toInt( item.getAttribute( 'data-id' ) );
	}

	/**
	 * Dựng một thẻ ảnh của gallery từ JSON attachment. Cấu trúc khớp với ISHA_Core_Admin_Media::render_gallery_item();
	 * số thứ tự, nhãn aria và trạng thái disabled của nút do refreshGallery() điền.
	 *
	 * @param {Object} data JSON của attachment.
	 * @return {Element}
	 */
	function buildItem( data ) {
		var item = make( 'li', 'isha-media__item' );
		var name = data.title || data.filename || '';
		var src = thumbUrl( data );
		var position = make( 'span', 'isha-media__pos' );
		var thumb = make( 'div', 'isha-media__thumb' );
		var label = make( 'span', 'isha-media__name', name );
		var actions = make( 'div', 'isha-media__actions' );
		var prev = makeButton( 'button button-small isha-media__move', t( 'prev' ) );
		var next = makeButton( 'button button-small isha-media__move', t( 'next' ) );
		var remove = makeButton( 'button-link button-link-delete isha-media__remove', t( 'remove' ) );

		item.setAttribute( 'data-id', String( data.id ) );
		position.setAttribute( 'aria-hidden', 'true' );
		if ( src ) {
			thumb.appendChild( makeImage( src ) );
		}
		label.title = name;
		prev.setAttribute( 'data-dir', '-1' );
		next.setAttribute( 'data-dir', '1' );

		actions.appendChild( prev );
		actions.appendChild( next );
		actions.appendChild( remove );
		item.appendChild( position );
		item.appendChild( thumb );
		item.appendChild( label );
		item.appendChild( actions );
		return item;
	}

	/**
	 * Đồng bộ mọi thứ phụ thuộc vào danh sách ảnh: giá trị input, số thứ tự, nhãn và trạng thái của nút, dòng "Chưa chọn".
	 *
	 * @param {Element} wrapper div.isha-media kiểu gallery.
	 */
	function refreshGallery( wrapper ) {
		var input = inputOf( wrapper );
		var list = galleryList( wrapper );
		var empty = wrapper.querySelector( '.isha-media__empty' );
		var clear = wrapper.querySelector( '.isha-media__clear' );
		var items = list ? list.querySelectorAll( '.isha-media__item' ) : [];
		var total = items.length;
		var ids = [];
		var index, item, position, prev, next, remove, number;

		for ( index = 0; index < total; index++ ) {
			item = items[ index ];
			position = index + 1;
			ids.push( itemId( item ) );

			number = item.querySelector( '.isha-media__pos' );
			prev = item.querySelector( '.isha-media__move[data-dir="-1"]' );
			next = item.querySelector( '.isha-media__move[data-dir="1"]' );
			remove = item.querySelector( '.isha-media__remove' );

			if ( number ) {
				number.textContent = String( position );
			}
			if ( prev ) {
				prev.disabled = index === 0;
				prev.setAttribute( 'aria-label', format( t( 'moveUpLabel' ), position ) );
			}
			if ( next ) {
				next.disabled = index === total - 1;
				next.setAttribute( 'aria-label', format( t( 'moveDownLabel' ), position ) );
			}
			if ( remove ) {
				remove.setAttribute( 'aria-label', format( t( 'removeLabel' ), position ) );
			}
		}

		if ( input ) {
			input.value = ids.join( ',' );
		}
		if ( list ) {
			list.hidden = total === 0;
		}
		if ( empty ) {
			empty.hidden = total > 0;
		}
		if ( clear ) {
			clear.hidden = total === 0;
		}
	}

	/**
	 * Áp lựa chọn từ khung wp.media vào gallery: ảnh còn được chọn giữ nguyên chỗ, ảnh bị bỏ chọn thì mất,
	 * ảnh mới nối vào cuối theo thứ tự chọn, tối đa data-max.
	 *
	 * Ảnh đã có trong gallery được nhận diện theo ID, không theo dữ liệu của model: wp.media.attachment( id ) chỉ có
	 * { id } cho tới khi fetch() xong, nên model chọn sẵn có thể chưa có "type". Chỉ ảnh MỚI mới phải có type "image".
	 *
	 * @param {Element} wrapper div.isha-media kiểu gallery.
	 * @param {Array}   models  JSON của các attachment đang được chọn.
	 */
	function applyGallery( wrapper, models ) {
		var list = galleryList( wrapper );
		var input = inputOf( wrapper );
		var max = maxOf( wrapper );
		var wanted = {};
		var order = [];
		var present = {};
		var overflow = false;
		var before;

		if ( ! list || ! input ) {
			return;
		}
		before = input.value;

		models.forEach( function ( data ) {
			if ( data && data.id && ! wanted[ data.id ] ) {
				wanted[ data.id ] = data;
				order.push( data.id );
			}
		} );

		Array.prototype.slice.call( list.children ).forEach( function ( item ) {
			var id = itemId( item );

			if ( wanted[ id ] ) {
				present[ id ] = true;
			} else {
				list.removeChild( item );
			}
		} );

		order.forEach( function ( id ) {
			if ( present[ id ] || wanted[ id ].type !== 'image' ) {
				return;
			}
			if ( list.children.length >= max ) {
				overflow = true;
				return;
			}
			list.appendChild( buildItem( wanted[ id ] ) );
		} );

		refreshGallery( wrapper );
		announce( wrapper, overflow ? format( t( 'limit' ), max ) : format( t( 'galleryCount' ), list.children.length ) );

		if ( before !== input.value ) {
			notify( wrapper );
		}
	}

	function moveItem( wrapper, item, direction, button ) {
		var list = item ? item.parentNode : null;
		var other = list ? ( direction < 0 ? item.previousElementSibling : item.nextElementSibling ) : null;

		if ( ! other ) {
			return;
		}

		// Đổi chỗ bằng cách dời phần tử hàng xóm, nên phần tử đang giữ focus không bị gỡ khỏi DOM.
		list.insertBefore( other, direction < 0 ? item.nextSibling : item );
		refreshGallery( wrapper );

		// Nút vừa bấm có thể đã bị vô hiệu (ảnh tới đầu/cuối danh sách): chuyển focus sang nút còn dùng được.
		focusFirst( [ button, item.querySelector( '.isha-media__move:not(:disabled)' ), item.querySelector( '.isha-media__remove' ) ] );
		announce( wrapper, format( t( 'moved' ), Array.prototype.indexOf.call( list.children, item ) + 1, list.children.length ) );
		notify( wrapper );
	}

	function removeItem( wrapper, item ) {
		var list = item ? item.parentNode : null;
		var position, neighbour;

		if ( ! list ) {
			return;
		}
		position = Array.prototype.indexOf.call( list.children, item ) + 1;
		neighbour = item.nextElementSibling || item.previousElementSibling;
		list.removeChild( item );
		refreshGallery( wrapper );

		// Focus sang nút Xoá của ảnh vừa dồn vào chỗ cũ (hoặc ảnh liền trước), hết ảnh thì về nút chọn.
		focusFirst( [ neighbour ? neighbour.querySelector( '.isha-media__remove' ) : null, wrapper.querySelector( '.isha-media__choose' ) ] );
		announce( wrapper, format( t( 'removed' ), position ) );
		notify( wrapper );
	}

	/* --- Xoá giá trị ------------------------------------------------------- */

	/**
	 * Đưa ô về trạng thái chưa chọn.
	 *
	 * @param {Element} wrapper div.isha-media.
	 * @param {boolean} quiet   true: không ghi thông báo cho trình đọc màn hình.
	 */
	function clearPicker( wrapper, quiet ) {
		var input = inputOf( wrapper );
		var list, before;

		if ( ! input ) {
			return;
		}
		before = input.value;

		if ( typeOf( wrapper ) === 'gallery' ) {
			list = galleryList( wrapper );
			if ( list ) {
				list.textContent = '';
			}
			refreshGallery( wrapper );
		} else {
			input.value = '';
			renderSingle( wrapper, null );
			refreshSingle( wrapper );
		}
		announce( wrapper, quiet ? '' : t( 'none' ) );

		if ( before !== input.value ) {
			notify( wrapper );
		}
	}

	function clearWithin( root ) {
		Array.prototype.forEach.call( root.querySelectorAll( '.isha-media' ), function ( wrapper ) {
			clearPicker( wrapper, true );
		} );
	}

	/* --- Khung wp.media ---------------------------------------------------- */

	/**
	 * Đưa các attachment đang chọn của ô vào vùng chọn của khung mỗi khi mở khung.
	 *
	 * @param {Element} wrapper div.isha-media.
	 * @param {Object}  frame   Khung wp.media.
	 */
	function preselect( wrapper, frame ) {
		var state = frame.state();
		var selection = state ? state.get( 'selection' ) : null;

		if ( ! selection ) {
			return;
		}
		selection.reset();
		currentIds( wrapper ).forEach( function ( id ) {
			var attachment = window.wp.media.attachment( id );

			if ( ! attachment.get( 'url' ) ) {
				attachment.fetch();
			}
			selection.add( attachment );
		} );
	}

	function handleSelect( wrapper, frame ) {
		var state = frame.state();
		var selection = state ? state.get( 'selection' ) : null;
		var type = typeOf( wrapper );
		var models = selection ? selection.models.map( function ( model ) {
			return model.toJSON();
		} ) : [];
		var data = models[ 0 ];

		if ( type === 'gallery' ) {
			applyGallery( wrapper, models );
			return;
		}
		if ( ! data || ! data.id || data.type !== ( type === 'video' ? 'video' : 'image' ) ) {
			return;
		}
		setSingle( wrapper, data );
		announce( wrapper, format( t( 'chosen' ), data.title || data.filename || '' ) );
	}

	/**
	 * Lấy (tạo lười) khung wp.media của một ô. Mỗi ô một khung.
	 *
	 * @param {Element} wrapper div.isha-media.
	 * @return {Object|null} null nếu wp.media không dùng được.
	 */
	function getFrame( wrapper ) {
		var frame = frames ? frames.get( wrapper ) : wrapper.ishaMediaFrame;
		var type = typeOf( wrapper );

		if ( frame ) {
			return frame;
		}
		if ( ! window.wp || typeof window.wp.media !== 'function' ) {
			return null;
		}

		try {
			frame = window.wp.media( {
				title: wrapper.getAttribute( 'data-title' ) || t( 'frameTitle' + capitalize( type ) ),
				button: { text: wrapper.getAttribute( 'data-button' ) || t( 'frameButton' + capitalize( type ) ) },
				library: { type: type === 'video' ? 'video' : 'image' },
				multiple: type === 'gallery' ? 'add' : false
			} );
		} catch ( error ) {
			return null;
		}
		if ( ! frame ) {
			return null;
		}

		frame.on( 'open', function () {
			preselect( wrapper, frame );
		} );
		frame.on( 'select', function () {
			handleSelect( wrapper, frame );
		} );

		if ( frames ) {
			frames.set( wrapper, frame );
		} else {
			wrapper.ishaMediaFrame = frame;
		}
		return frame;
	}

	function openFrame( wrapper, trigger ) {
		var frame = getFrame( wrapper );

		if ( ! frame ) {
			announce( wrapper, t( 'unavailable' ) );
			return;
		}
		announce( wrapper, '' );

		// Khi đóng, khung media trả focus về phần tử đang focus lúc mở. Safari và Firefox trên macOS không focus nút khi bấm chuột.
		trigger.focus();
		frame.open();
	}

	/* --- Sự kiện ------------------------------------------------------------ */

	function onClick( event ) {
		var target = event.target;
		var button, wrapper;

		if ( ! target || typeof target.closest !== 'function' ) {
			return;
		}
		button = target.closest( 'button' );
		wrapper = button ? button.closest( '.isha-media' ) : null;
		if ( ! wrapper ) {
			return;
		}

		if ( button.classList.contains( 'isha-media__choose' ) ) {
			event.preventDefault();
			openFrame( wrapper, button );
		} else if ( button.classList.contains( 'isha-media__clear' ) ) {
			event.preventDefault();
			clearPicker( wrapper, false );
			focusFirst( [ wrapper.querySelector( '.isha-media__choose' ) ] );
		} else if ( button.classList.contains( 'isha-media__remove' ) ) {
			event.preventDefault();
			removeItem( wrapper, button.closest( '.isha-media__item' ) );
		} else if ( button.classList.contains( 'isha-media__move' ) ) {
			event.preventDefault();
			moveItem( wrapper, button.closest( '.isha-media__item' ), toInt( button.getAttribute( 'data-dir' ) ) < 0 ? -1 : 1, button );
		}
	}

	/**
	 * Màn "Thêm term": WordPress gửi form #addtag bằng AJAX và chỉ xoá các input đang hiện, nên input hidden của ô chọn
	 * phải được xoá ở đây. Khi thêm lỗi (HTTP 200 kèm <wp_error>) thì giữ nguyên lựa chọn để người dùng sửa và gửi lại.
	 */
	function bindAddTag() {
		var jq = window.jQuery;

		if ( typeof jq !== 'function' ) {
			return;
		}
		jq( document ).on( 'ajaxSuccess', function ( event, xhr, settings ) {
			var form;

			if ( ! settings || typeof settings.data !== 'string' || settings.data.indexOf( 'action=add-tag' ) === -1 ) {
				return;
			}
			if ( xhr && typeof xhr.responseText === 'string' && xhr.responseText.indexOf( '<wp_error' ) !== -1 ) {
				return;
			}
			form = document.getElementById( 'addtag' );
			if ( form ) {
				clearWithin( form );
			}
		} );
	}

	document.addEventListener( 'click', onClick );
	document.addEventListener( 'reset', function ( event ) {
		if ( ! event.defaultPrevented && event.target && typeof event.target.querySelectorAll === 'function' ) {
			clearWithin( event.target );
		}
	} );
	bindAddTag();
}() );
