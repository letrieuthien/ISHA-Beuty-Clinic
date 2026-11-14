/* Chọn nhiều ảnh cho "Ảnh liệu trình" (fallback khi không có ACF PRO). */
( function ( $ ) {
	'use strict';

	$( '.isha-gallery' ).each( function () {
		var $box = $( this );
		var $list = $box.find( '.isha-gallery__list' );
		var $input = $box.find( 'input[name="isha_gallery_ids"]' );
		var frame;

		function sync() {
			var ids = $list.children( 'li' ).map( function () {
				return $( this ).data( 'id' );
			} ).get();
			$input.val( ids.join( ',' ) );
		}

		$list.sortable( { update: sync } );

		$list.on( 'click', '.isha-gallery__remove', function () {
			$( this ).closest( 'li' ).remove();
			sync();
		} );

		$box.find( '.isha-gallery__add' ).on( 'click', function ( e ) {
			e.preventDefault();
			if ( ! frame ) {
				frame = wp.media( {
					title: window.ishaGallery.title,
					button: { text: window.ishaGallery.button },
					library: { type: 'image' },
					multiple: 'add'
				} );
				frame.on( 'select', function () {
					frame.state().get( 'selection' ).each( function ( att ) {
						var a = att.toJSON();
						if ( $list.children( '[data-id="' + a.id + '"]' ).length ) {
							return;
						}
						var url = a.sizes && a.sizes.thumbnail ? a.sizes.thumbnail.url : a.url;
						var $li = $( '<li>' ).attr( 'data-id', a.id ).data( 'id', a.id );
						$li.append( $( '<img>' ).attr( 'src', url ).attr( 'alt', '' ) );
						$li.append( $( '<button type="button" class="isha-gallery__remove">&times;</button>' ) );
						$list.append( $li );
					} );
					sync();
				} );
			}
			frame.open();
		} );
	} );
}( jQuery ) );
