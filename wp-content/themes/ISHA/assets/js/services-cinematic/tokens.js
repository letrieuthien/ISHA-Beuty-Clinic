/**
 * Màu của cảnh v3, truy được về token CSS (KHÔNG có mã hex trong JS / GLSL). Đặc tả: docs/isha-lookdev-prompt-v3.md mục 7.
 *
 * - readTokens(): giá trị sRGB [0..1] của các biến CSS. Token khai báo bằng color-mix() thì getPropertyValue chỉ trả chuỗi công thức, nên
 *   phân giải qua phần tử thăm dò (style.color = var(...)) rồi đọc màu đã tính (rgb() hoặc color(srgb ...)).
 * - derivePalette( tokens, cfg ): bảng màu phái sinh (hàm thuần, sRGB hiển thị):
 *     fog       = mix( Daylight, Skin soft, 0.35 )                      sương, màu trời xa, bụi
 *     greenHint = hsl( 112°, độ bão hoà Gold × 0.28, độ sáng của Dawn )
 *     ridgeBase = mix( Dawn, greenHint, 0.40 )                         núi: xám ấm ngả xanh rất nhẹ
 *     sky       = Daylight → Skin soft → mix( Skin, Golden, 0.5 ) → Golden  bốn chặng trời từ trên xuống
 *     rim       = mix( Golden, Metal, 0.4 )                            viền sáng rặng núi
 *     sunCore   = mix( Golden, White, 0.75 ), rays = Metal light       (mặt trời, bước 4)
 * - Shader nhận màu sRGB hiển thị (Vector3, không đổi không gian) và tự tuyến tính hoá sau khi trộn: phép nhân "tô màu ảnh" (base × gray)
 *   làm trong không gian hiển thị cho kết quả giống tô màu ảnh trong Photoshop.
 */

export const TOKEN_NAMES = Object.freeze( [
	'--isha-daylight', '--isha-skin', '--isha-skin-soft', '--isha-gold', '--isha-golden', '--isha-dawn', '--isha-white',
	'--isha-metal', '--isha-metal-light',
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
	const missing = [];
	for ( const name of names ) {
		probe.style.color = '';
		probe.style.color = `var(${ name })`;
		const raw = getComputedStyle( document.documentElement ).getPropertyValue( name ).trim();
		const rgb = raw ? parseComputedColor( getComputedStyle( probe ).color ) : null;
		if ( rgb ) {
			out[ name ] = rgb;
		} else {
			missing.push( name );
		}
	}
	probe.remove();
	if ( missing.length ) {
		throw new Error( `Thiếu token màu: ${ missing.join( ', ' ) } (main.css chưa nạp?)` );
	}
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
		if ( x < 0 ) {
			x += 1;
		}
		if ( x > 1 ) {
			x -= 1;
		}
		if ( x < 1 / 6 ) {
			return p + ( q - p ) * 6 * x;
		}
		if ( x < 1 / 2 ) {
			return q;
		}
		if ( x < 2 / 3 ) {
			return p + ( q - p ) * ( 2 / 3 - x ) * 6;
		}
		return p;
	};
	return [ f( hh + 1 / 3 ), f( hh ), f( hh - 1 / 3 ) ];
}

export function mix( a, b, t ) {
	return [ a[ 0 ] + ( b[ 0 ] - a[ 0 ] ) * t, a[ 1 ] + ( b[ 1 ] - a[ 1 ] ) * t, a[ 2 ] + ( b[ 2 ] - a[ 2 ] ) * t ];
}

/**
 * @param {object} t   Kết quả readTokens.
 * @param {{fogSkinMix:number, greenHue:number, greenSat:number, ridgeGreenMix:number, rimMetalMix:number, sunWhiteMix:number}} cfg CONFIG.colors.
 */
export function derivePalette( t, cfg ) {
	const goldSat = rgbToHsl( t[ '--isha-gold' ] )[ 1 ];
	const dawnL = rgbToHsl( t[ '--isha-dawn' ] )[ 2 ];
	const greenHint = hslToRgb( [ cfg.greenHue, goldSat * cfg.greenSat, dawnL ] );
	return {
		daylight: t[ '--isha-daylight' ],
		skinSoft: t[ '--isha-skin-soft' ],
		skin: t[ '--isha-skin' ],
		golden: t[ '--isha-golden' ],
		dawn: t[ '--isha-dawn' ],
		white: t[ '--isha-white' ],
		metal: t[ '--isha-metal' ],
		fog: mix( t[ '--isha-daylight' ], t[ '--isha-skin-soft' ], cfg.fogSkinMix ),
		greenHint,
		ridgeBase: mix( t[ '--isha-dawn' ], greenHint, cfg.ridgeGreenMix ),
		skyMid: mix( t[ '--isha-skin' ], t[ '--isha-golden' ], 0.5 ),
		rim: mix( t[ '--isha-golden' ], t[ '--isha-metal' ], cfg.rimMetalMix ),
		sunCore: mix( t[ '--isha-golden' ], t[ '--isha-white' ], cfg.sunWhiteMix ),
		rays: t[ '--isha-metal-light' ],
	};
}

/** Bảng màu sRGB → THREE.Vector3 (giữ nguyên giá trị sRGB hiển thị cho shader). */
export function toVectors( palette, THREE ) {
	const out = {};
	for ( const [ k, v ] of Object.entries( palette ) ) {
		out[ k ] = new THREE.Vector3( v[ 0 ], v[ 1 ], v[ 2 ] );
	}
	return out;
}
