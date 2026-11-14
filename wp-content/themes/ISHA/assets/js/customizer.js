/* Live preview cho Customizer. */
( function ( api ) {
	'use strict';

	var map = {
		isha_hero_eyebrow: '.hero__eyebrow',
		isha_hero_title: '.hero__title',
		isha_hero_subtitle: '.hero__subtitle',
		isha_hero_btn_text: '.hero .btn--primary',
		isha_hero_btn2_text: '.hero .btn--outline',
		blogname: '.site-title'
	};

	Object.keys( map ).forEach( function ( setting ) {
		api( setting, function ( value ) {
			value.bind( function ( to ) {
				var el = document.querySelector( map[ setting ] );
				if ( el ) {
					el.textContent = to;
				}
			} );
		} );
	} );
}( wp.customize ) );
