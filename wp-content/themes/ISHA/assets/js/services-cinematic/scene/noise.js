/**
 * Perlin noise 2D có seed (cùng seed thì cùng địa hình ở mọi lần tải) và fbm / ridged multifractal. Thuần, không phụ thuộc three.
 */

export function mulberry32( seed ) {
	let a = seed >>> 0;
	return function () {
		a = ( a + 0x6d2b79f5 ) >>> 0;
		let t = a;
		t = Math.imul( t ^ ( t >>> 15 ), t | 1 );
		t ^= t + Math.imul( t ^ ( t >>> 7 ), t | 61 );
		return ( ( t ^ ( t >>> 14 ) ) >>> 0 ) / 4294967296;
	};
}

export function createPerlin( seed ) {
	const rand = mulberry32( seed );
	const p = new Uint8Array( 256 );
	for ( let i = 0; i < 256; i++ ) p[ i ] = i;
	for ( let i = 255; i > 0; i-- ) {
		const j = Math.floor( rand() * ( i + 1 ) );
		const t = p[ i ];
		p[ i ] = p[ j ];
		p[ j ] = t;
	}
	const perm = new Uint8Array( 512 );
	for ( let i = 0; i < 512; i++ ) perm[ i ] = p[ i & 255 ];

	const fade = ( t ) => t * t * t * ( t * ( t * 6 - 15 ) + 10 );
	const grad = ( h, x, y ) => {
		switch ( h & 7 ) {
			case 0: return x + y;
			case 1: return -x + y;
			case 2: return x - y;
			case 3: return -x - y;
			case 4: return x;
			case 5: return -x;
			case 6: return y;
			default: return -y;
		}
	};

	// Giá trị khoảng [-1, 1].
	function noise( x, y ) {
		const xi = Math.floor( x );
		const yi = Math.floor( y );
		const X = xi & 255;
		const Y = yi & 255;
		const xf = x - xi;
		const yf = y - yi;
		const u = fade( xf );
		const v = fade( yf );
		const aa = perm[ perm[ X ] + Y ];
		const ab = perm[ perm[ X ] + Y + 1 ];
		const ba = perm[ perm[ X + 1 ] + Y ];
		const bb = perm[ perm[ X + 1 ] + Y + 1 ];
		const x1 = grad( aa, xf, yf ) + u * ( grad( ba, xf - 1, yf ) - grad( aa, xf, yf ) );
		const x2 = grad( ab, xf, yf - 1 ) + u * ( grad( bb, xf - 1, yf - 1 ) - grad( ab, xf, yf - 1 ) );
		return ( x1 + v * ( x2 - x1 ) ) * 0.7071;
	}

	function fbm( x, y, octaves, freq ) {
		let sum = 0;
		let amp = 0.5;
		let f = freq;
		let norm = 0;
		for ( let i = 0; i < octaves; i++ ) {
			sum += noise( x * f, y * f ) * amp;
			norm += amp;
			amp *= 0.5;
			f *= 2.03;
		}
		return sum / norm; // [-1, 1]
	}

	// Ridged multifractal: ( 1 - |n| )^2, mỗi octave nhân với octave trước (sống núi sắc). Giá trị [0, ~1].
	function ridged( x, y, octaves, freq ) {
		let sum = 0;
		let amp = 0.5;
		let f = freq;
		let prev = 1;
		let norm = 0;
		for ( let i = 0; i < octaves; i++ ) {
			let n = 1 - Math.abs( noise( x * f, y * f ) );
			n *= n;
			n *= prev;
			prev = n;
			sum += n * amp;
			norm += amp;
			amp *= 0.5;
			f *= 2.07;
		}
		return sum / norm;
	}

	return { noise, fbm, ridged };
}
