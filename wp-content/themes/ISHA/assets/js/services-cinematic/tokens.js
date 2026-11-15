/**
 * Màu cho cảnh 3D, truy được về token CSS (KHÔNG có mã hex trong JS).
 *
 * - readTokenRGB( name ): giá trị sRGB [0..1] của một biến CSS. Một số token khai báo bằng color-mix(), getPropertyValue chỉ trả chuỗi công
 *   thức, nên phân giải qua phần tử thăm dò (style.color = var(...)) rồi đọc màu đã tính (rgb() hoặc color(srgb ...)).
 * - Hàm màu thuần trên mảng sRGB: rgbToHsl, hslToRgb, mix.
 * - derivePalette( tokens, cfg ): màu phái sinh (hồng mây, xanh cỏ/cây, đá núi, sương) tính từ token theo hệ số của config (kPink, kGreen...).
 * - toLinearColors( palette, THREE ): đổi sang THREE.Color (Linear-sRGB) bằng setRGB( r, g, b, SRGBColorSpace ), không tự đổi lần nữa.
 */

export const TOKEN_NAMES = Object.freeze( [
	'--isha-daylight', '--isha-skin', '--isha-gold', '--isha-shadow', '--isha-white', '--isha-gold-ink', '--isha-skin-soft',
	'--isha-dawn', '--isha-golden', '--isha-evening', '--isha-sunset', '--isha-twilight', '--isha-dusk',
] );

/** Chuỗi màu đã tính (rgb(), rgba(), color(srgb ...)) → [r, g, b] trong [0, 1]. */
export function parseComputedColor( str ) {
	const s = String( str || '' ).trim();
	let m = s.match( /^rgba?\(\s*([\d.]+)[ ,]+([\d.]+)[ ,]+([\d.]+)/i );
	if ( m ) {
		return [ +m[ 1 ] / 255, +m[ 2 ] / 255, +m[ 3 ] / 255 ];
	}
	m = s.match( /^color\(\s*srgb\s+([\d.e-]+)\s+([\d.e-]+)\s+([\d.e-]+)/i );
	if ( m ) {
		return [ +m[ 1 ], +m[ 2 ], +m[ 3 ] ];
	}
	return null;
}

export function readTokens( names = TOKEN_NAMES ) {
	const probe = document.createElement( 'span' );
	probe.style.cssText = 'position:absolute;width:0;height:0;overflow:hidden;visibility:hidden;';
	document.body.appendChild( probe );
	const out = {};
	for ( const name of names ) {
		probe.style.color = '';
		probe.style.color = `var(${ name })`;
		const rgb = parseComputedColor( getComputedStyle( probe ).color );
		if ( rgb ) {
			out[ name ] = rgb;
		}
	}
	probe.remove();
	return out;
}

export function rgbToHsl( [ r, g, b ] ) {
	const max = Math.max( r, g, b );
	const min = Math.min( r, g, b );
	const l = ( max + min ) / 2;
	if ( max === min ) {
		return [ 0, 0, l ];
	}
	const d = max - min;
	const s = l > 0.5 ? d / ( 2 - max - min ) : d / ( max + min );
	let h;
	if ( max === r ) {
		h = ( g - b ) / d + ( g < b ? 6 : 0 );
	} else if ( max === g ) {
		h = ( b - r ) / d + 2;
	} else {
		h = ( r - g ) / d + 4;
	}
	return [ h * 60, s, l ];
}

export function hslToRgb( [ h, s, l ] ) {
	const hh = ( ( ( h % 360 ) + 360 ) % 360 ) / 360;
	if ( s === 0 ) {
		return [ l, l, l ];
	}
	const q = l < 0.5 ? l * ( 1 + s ) : l + s - l * s;
	const p = 2 * l - q;
	const f = ( t ) => {
		let x = t;
		if ( x < 0 ) x += 1;
		if ( x > 1 ) x -= 1;
		if ( x < 1 / 6 ) return p + ( q - p ) * 6 * x;
		if ( x < 1 / 2 ) return q;
		if ( x < 2 / 3 ) return p + ( q - p ) * ( 2 / 3 - x ) * 6;
		return p;
	};
	return [ f( hh + 1 / 3 ), f( hh ), f( hh - 1 / 3 ) ];
}

export function mix( a, b, t ) {
	return [ a[ 0 ] + ( b[ 0 ] - a[ 0 ] ) * t, a[ 1 ] + ( b[ 1 ] - a[ 1 ] ) * t, a[ 2 ] + ( b[ 2 ] - a[ 2 ] ) * t ];
}

/**
 * Màu phái sinh (sRGB), hàm thuần.
 *
 * @param {object} t   Kết quả readTokens.
 * @param {object} cfg CONFIG.colors.
 */
export function derivePalette( t, cfg ) {
	const gold = rgbToHsl( t[ '--isha-gold' ] );
	const golden = rgbToHsl( t[ '--isha-golden' ] );
	const skin = rgbToHsl( t[ '--isha-skin' ] );
	const goldSat = gold[ 1 ];

	// Hồng mây: hue của Skin dịch về vùng hồng, độ bão hoà = vàng x kPink, sáng cao.
	const pink = hslToRgb( [ cfg.pinkHue, goldSat * cfg.kPink, Math.min( 0.9, skin[ 2 ] + 0.05 ) ] );
	// Xanh "mát, trẻ trung" cho cỏ/cây: hue xanh, độ bão hoà = vàng x kGreen, sáng tham chiếu Golden.
	const green = hslToRgb( [ cfg.greenHue, goldSat * cfg.kGreen, golden[ 2 ] * 0.62 ] );
	const greenLight = hslToRgb( [ cfg.greenHue - 18, goldSat * cfg.kGreen * 0.9, golden[ 2 ] * 0.85 ] );
	// Đá núi: Dawn/Evening trộn chút xanh.
	const rock = mix( mix( t[ '--isha-dawn' ], t[ '--isha-evening' ], 0.5 ), green, 0.2 );

	return {
		daylight: t[ '--isha-daylight' ],
		skinSoft: t[ '--isha-skin-soft' ],
		skin: t[ '--isha-skin' ],
		golden: t[ '--isha-golden' ],
		sunset: t[ '--isha-sunset' ],
		gold: t[ '--isha-gold' ],
		dawn: t[ '--isha-dawn' ],
		evening: t[ '--isha-evening' ],
		shadow: t[ '--isha-shadow' ],
		white: t[ '--isha-white' ],
		pink,
		green,
		greenLight,
		rock,
		peakLight: mix( t[ '--isha-golden' ], t[ '--isha-daylight' ], 0.5 ),
	};
}

/** Màu mặt trời theo sunTone ∈ [0, 1] trên đường Golden → Sunset → Daylight (sRGB). */
export function sunToneRGB( p, tone ) {
	const t = Math.min( 1, Math.max( 0, tone ) );
	return t < 0.5 ? mix( p.golden, p.sunset, t * 2 ) : mix( p.sunset, p.daylight, ( t - 0.5 ) * 2 );
}

export function toLinearColors( palette, THREE ) {
	const out = {};
	for ( const [ k, v ] of Object.entries( palette ) ) {
		out[ k ] = new THREE.Color().setRGB( v[ 0 ], v[ 1 ], v[ 2 ], THREE.SRGBColorSpace );
	}
	return out;
}
