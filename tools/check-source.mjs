#!/usr/bin/env node
/**
 * Kiểm tra ảnh nguồn của cảnh v3 (docs/isha-lookdev-prompt-v3.md mục 18, bước 1). Chạy từ gốc repo: node tools/check-source.mjs
 *
 * - plates/scene-source.*: dò .png → .webp → .jpg, dùng file đầu tiên tìm thấy. Kiểm tra: tồn tại, rộng ≥ 3840px, tỉ lệ gần 16:9, định dạng
 *   thật khớp đuôi file. Với PNG: giải mã (zlib của Node, không cần thư viện) và in histogram độ sáng (Rec. 709, như shader), các phân vị, hồ
 *   sơ độ sáng theo hàng (để thấy trời, các rặng xa sáng và rặng gần tối), cân bằng trái / phải. JPEG / WebP chỉ đọc được kích thước.
 * - plates/depth.png, plates/clouds.*: kích thước, tỉ lệ so với ảnh nguồn, định dạng thật.
 * - mood/, current/: đếm file, đối chiếu tên đặc tả yêu cầu.
 *
 * Mã thoát 1 khi thiếu ảnh nguồn hoặc ảnh nguồn không đạt.
 */
import { readFileSync, existsSync, readdirSync } from 'node:fs';
import { join, dirname, resolve } from 'node:path';
import { fileURLToPath } from 'node:url';
import { inflateSync } from 'node:zlib';

const ROOT = resolve( dirname( fileURLToPath( import.meta.url ) ), '..' );
const REFS = join( ROOT, 'tools', 'refs' );
const PLATES = join( REFS, 'plates' );
const fail = [];
const warn = [];

/** Định dạng thật theo chữ ký đầu file. */
function sniff( buf ) {
	if ( buf[ 0 ] === 0x89 && buf.toString( 'latin1', 1, 4 ) === 'PNG' ) {
		return 'png';
	}
	if ( buf[ 0 ] === 0xff && buf[ 1 ] === 0xd8 ) {
		return 'jpg';
	}
	if ( buf.toString( 'latin1', 0, 4 ) === 'RIFF' && buf.toString( 'latin1', 8, 12 ) === 'WEBP' ) {
		return 'webp';
	}
	return '?';
}

/** Kích thước từ header (PNG, JPEG, WebP VP8 / VP8L / VP8X). */
function dims( buf, kind ) {
	if ( kind === 'png' ) {
		return [ buf.readUInt32BE( 16 ), buf.readUInt32BE( 20 ) ];
	}
	if ( kind === 'jpg' ) {
		let i = 2;
		while ( i < buf.length ) {
			const m = buf[ i + 1 ];
			const len = buf.readUInt16BE( i + 2 );
			if ( m >= 0xc0 && m <= 0xcf && m !== 0xc4 && m !== 0xc8 && m !== 0xcc ) {
				return [ buf.readUInt16BE( i + 7 ), buf.readUInt16BE( i + 5 ) ];
			}
			i += 2 + len;
		}
	}
	if ( kind === 'webp' ) {
		const chunk = buf.toString( 'latin1', 12, 16 );
		if ( chunk === 'VP8X' ) {
			return [ 1 + buf.readUIntLE( 24, 3 ), 1 + buf.readUIntLE( 27, 3 ) ];
		}
		if ( chunk === 'VP8L' ) {
			const b = buf.readUInt32LE( 21 );
			return [ ( b & 0x3fff ) + 1, ( ( b >> 14 ) & 0x3fff ) + 1 ];
		}
		if ( chunk === 'VP8 ' ) {
			return [ buf.readUInt16LE( 26 ) & 0x3fff, buf.readUInt16LE( 28 ) & 0x3fff ];
		}
	}
	return [ 0, 0 ];
}

/** Giải mã PNG 8 bit (xám, xám + alpha, RGB, RGBA; không interlace) → { w, h, ch, data } với data là từng hàng đã bỏ filter. */
function decodePng( buf ) {
	let pos = 8;
	let w = 0;
	let h = 0;
	let depth = 0;
	let type = 0;
	let interlace = 0;
	const idat = [];
	while ( pos < buf.length ) {
		const len = buf.readUInt32BE( pos );
		const name = buf.toString( 'latin1', pos + 4, pos + 8 );
		const body = buf.subarray( pos + 8, pos + 8 + len );
		if ( name === 'IHDR' ) {
			w = body.readUInt32BE( 0 );
			h = body.readUInt32BE( 4 );
			depth = body[ 8 ];
			type = body[ 9 ];
			interlace = body[ 12 ];
		} else if ( name === 'IDAT' ) {
			idat.push( body );
		} else if ( name === 'IEND' ) {
			break;
		}
		pos += 12 + len;
	}
	const ch = { 0: 1, 2: 3, 4: 2, 6: 4 }[ type ];
	if ( depth !== 8 || ! ch || interlace ) {
		throw new Error( `PNG chưa hỗ trợ (bit depth ${ depth }, color type ${ type }, interlace ${ interlace }); xuất lại PNG 8 bit RGB không interlace.` );
	}
	const raw = inflateSync( Buffer.concat( idat ) );
	const stride = w * ch;
	const out = Buffer.alloc( stride * h );
	for ( let y = 0; y < h; y++ ) {
		const f = raw[ y * ( stride + 1 ) ];
		const src = y * ( stride + 1 ) + 1;
		const dst = y * stride;
		for ( let x = 0; x < stride; x++ ) {
			const a = x >= ch ? out[ dst + x - ch ] : 0;
			const b = y > 0 ? out[ dst - stride + x ] : 0;
			const c = x >= ch && y > 0 ? out[ dst - stride + x - ch ] : 0;
			let v = raw[ src + x ];
			if ( f === 1 ) {
				v += a;
			} else if ( f === 2 ) {
				v += b;
			} else if ( f === 3 ) {
				v += ( a + b ) >> 1;
			} else if ( f === 4 ) {
				const p = a + b - c;
				const pa = Math.abs( p - a );
				const pb = Math.abs( p - b );
				const pc = Math.abs( p - c );
				v += pa <= pb && pa <= pc ? a : pb <= pc ? b : c;
			}
			out[ dst + x ] = v & 255;
		}
	}
	return { w, h, ch, data: out };
}

const bar = ( frac, width = 40 ) => '█'.repeat( Math.round( frac * width ) ).padEnd( width, '·' );
const pct = ( v ) => ( v * 100 ).toFixed( 1 ).padStart( 5 ) + '%';

// ---------------------------------------------------------------------------------------------------------------------------------------
// 1. Ảnh nguồn.
console.log( '== plates/scene-source ==' );
const srcPath = [ 'png', 'webp', 'jpg', 'jpeg' ].map( ( e ) => join( PLATES, `scene-source.${ e }` ) ).find( existsSync );
if ( ! srcPath ) {
	console.log( 'KHÔNG TÌM THẤY tools/refs/plates/scene-source.(png|webp|jpg). Dừng: không có ảnh nguồn thì không dựng địa hình (không sinh procedural).' );
	process.exit( 1 );
}
const srcBuf = readFileSync( srcPath );
const kind = sniff( srcBuf );
const ext = srcPath.split( '.' ).pop().replace( 'jpeg', 'jpg' );
const [ W, H ] = dims( srcBuf, kind );
const ratio = W / H;
console.log( `file: ${ srcPath.slice( ROOT.length + 1 ) } (${ ( srcBuf.length / 1048576 ).toFixed( 1 ) } MB, định dạng thật: ${ kind })` );
console.log( `kích thước: ${ W } × ${ H }, tỉ lệ ${ ratio.toFixed( 3 ) }:1 (16:9 = 1.778)` );
if ( kind !== ext ) {
	fail.push( `scene-source mang đuôi .${ ext } nhưng nội dung là ${ kind }` );
}
if ( W < 3840 ) {
	fail.push( `scene-source rộng ${ W }px < 3840px` );
}
if ( Math.abs( ratio - 16 / 9 ) > 0.05 ) {
	warn.push( `scene-source tỉ lệ ${ ratio.toFixed( 3 ) }:1, lệch 16:9 quá 0.05 (đặc tả mục 5.1 chấp nhận khoảng 1.9:1 sau khi cắt trời; code fit theo chiều rộng nên vẫn dùng được)` );
}
if ( W > 4096 ) {
	warn.push( `scene-source rộng ${ W }px > 4096px: vượt MAX_TEXTURE_SIZE của một số GPU di động, cần thu nhỏ khi xuất webp` );
}

if ( kind === 'png' ) {
	const t0 = Date.now();
	const img = decodePng( srcBuf );
	const { w, h, ch, data } = img;
	const lum = new Float32Array( w * h );
	const hist = new Uint32Array( 256 );
	let alphaLow = 0;
	for ( let i = 0, p = 0; i < w * h; i++, p += ch ) {
		let l;
		if ( ch >= 3 ) {
			l = ( 0.2126 * data[ p ] + 0.7152 * data[ p + 1 ] + 0.0722 * data[ p + 2 ] ) / 255;
		} else {
			l = data[ p ] / 255;
		}
		if ( ( ch === 4 && data[ p + 3 ] < 250 ) || ( ch === 2 && data[ p + 1 ] < 250 ) ) {
			alphaLow++;
		}
		lum[ i ] = l;
		hist[ Math.min( 255, Math.round( l * 255 ) ) ]++;
	}
	console.log( `giải mã PNG: ${ w } × ${ h }, ${ ch } kênh, ${ Date.now() - t0 } ms` );
	if ( alphaLow ) {
		warn.push( `${ alphaLow } pixel có alpha < 250: ảnh nguồn nên đục hoàn toàn` );
	}

	// Phân vị độ sáng.
	const total = w * h;
	const q = ( f ) => {
		let acc = 0;
		for ( let i = 0; i < 256; i++ ) {
			acc += hist[ i ];
			if ( acc >= f * total ) {
				return i / 255;
			}
		}
		return 1;
	};
	const P = Object.fromEntries( [ 0.01, 0.05, 0.25, 0.5, 0.75, 0.95, 0.99 ].map( ( f ) => [ f, q( f ) ] ) );
	console.log( '\nphân vị độ sáng (0–1, Rec. 709 trên giá trị sRGB như shader):' );
	console.log( '  ' + Object.entries( P ).map( ( [ f, v ] ) => `p${ Math.round( f * 100 ) } = ${ v.toFixed( 3 ) }` ).join( '   ' ) );

	// Histogram 16 bậc.
	const B = 16;
	const bins = new Array( B ).fill( 0 );
	hist.forEach( ( n, i ) => {
		bins[ Math.min( B - 1, Math.floor( ( i / 256 ) * B ) ) ] += n;
	} );
	const maxBin = Math.max( ...bins );
	console.log( '\nhistogram độ sáng (16 bậc):' );
	bins.forEach( ( n, i ) => {
		console.log( `  ${ ( i / B ).toFixed( 3 ) }–${ ( ( i + 1 ) / B ).toFixed( 3 ) } ${ bar( n / maxBin ) } ${ pct( n / total ) }` );
	} );
	const used = bins.filter( ( n ) => n / total >= 0.01 ).length;
	console.log( `  bậc chiếm ≥ 1% ảnh: ${ used }/${ B }; dải p5–p95: ${ ( P[ 0.95 ] - P[ 0.05 ] ).toFixed( 3 ) }` );
	if ( used < 6 || P[ 0.95 ] - P[ 0.05 ] < 0.25 ) {
		fail.push( 'độ sáng quá dồn (ít bậc / dải p5–p95 hẹp): suy độ sâu từ độ sáng sẽ phẳng' );
	}

	// Hồ sơ theo hàng: trung bình và p10 / p90 mỗi dải 5% chiều cao.
	console.log( '\nđộ sáng theo hàng (dải 5% chiều cao, từ trên xuống): trung bình [p10 – p90]' );
	const rowsMean = [];
	for ( let k = 0; k < 20; k++ ) {
		const y0 = Math.floor( ( k / 20 ) * h );
		const y1 = Math.floor( ( ( k + 1 ) / 20 ) * h );
		const hh = new Uint32Array( 64 );
		let sum = 0;
		let n = 0;
		for ( let y = y0; y < y1; y += 2 ) {
			for ( let x = 0; x < w; x += 2 ) {
				const l = lum[ y * w + x ];
				sum += l;
				n++;
				hh[ Math.min( 63, Math.floor( l * 64 ) ) ]++;
			}
		}
		const qq = ( f ) => {
			let acc = 0;
			for ( let i = 0; i < 64; i++ ) {
				acc += hh[ i ];
				if ( acc >= f * n ) {
					return ( i + 0.5 ) / 64;
				}
			}
			return 1;
		};
		const mean = sum / n;
		rowsMean.push( mean );
		console.log( `  ${ ( k * 5 ).toString().padStart( 2 ) }–${ ( ( k + 1 ) * 5 ).toString().padStart( 3 ) }% ${ bar( mean, 30 ) } ${ mean.toFixed( 3 ) } [${ qq( 0.1 ).toFixed( 2 ) } – ${ qq( 0.9 ).toFixed( 2 ) }]` );
	}
	// Xu hướng xa sáng / gần tối: nửa trên sáng hơn nửa dưới.
	const top = rowsMean.slice( 0, 10 ).reduce( ( a, b ) => a + b, 0 ) / 10;
	const bottom = rowsMean.slice( 10 ).reduce( ( a, b ) => a + b, 0 ) / 10;
	console.log( `  nửa trên ${ top.toFixed( 3 ) }, nửa dưới ${ bottom.toFixed( 3 ) } → ${ top > bottom + 0.05 ? 'đúng quan hệ "xa sáng, gần tối"' : 'KHÔNG rõ quan hệ xa sáng / gần tối' }` );
	if ( top <= bottom + 0.05 ) {
		warn.push( 'nửa trên không sáng hơn rõ nửa dưới: độ sâu suy từ độ sáng có thể sai, cân nhắc vẽ depth.png' );
	}

	// Trái / phải (ảnh đã lật để nắng đến từ bên phải).
	let left = 0;
	let right = 0;
	for ( let y = 0; y < h; y += 4 ) {
		for ( let x = 0; x < w; x += 4 ) {
			if ( x < w / 2 ) {
				left += lum[ y * w + x ];
			} else {
				right += lum[ y * w + x ];
			}
		}
	}
	console.log( `\ntrái / phải: ${ ( left / ( left + right ) ).toFixed( 3 ) } / ${ ( right / ( left + right ) ).toFixed( 3 ) } (chỉ để tham khảo hướng sáng)` );

	// Gợi ý khởi đầu cho các núm của depth.js (đặc tả 3.1, 3.4): ngưỡng gần / xa theo phân vị của phần dưới 15% đầu ảnh.
	const hist2 = new Uint32Array( 256 );
	let n2 = 0;
	for ( let y = Math.floor( h * 0.15 ); y < h; y += 2 ) {
		for ( let x = 0; x < w; x += 2 ) {
			hist2[ Math.min( 255, Math.round( lum[ y * w + x ] * 255 ) ) ]++;
			n2++;
		}
	}
	const q2 = ( f ) => {
		let acc = 0;
		for ( let i = 0; i < 256; i++ ) {
			acc += hist2[ i ];
			if ( acc >= f * n2 ) {
				return i / 255;
			}
		}
		return 1;
	};
	console.log( `gợi ý khởi đầu (phần dưới 85% ảnh): uLumNear ≈ ${ q2( 0.05 ).toFixed( 2 ) } (p5), uLumFar ≈ ${ q2( 0.97 ).toFixed( 2 ) } (p97); trời ≈ ${ P[ 0.99 ].toFixed( 2 ) } (p99 toàn ảnh)` );

	// Khe histogram giữa trời và núi: bậc 1/64 thưa nhất trong vùng sáng 0.5–0.75 (trời ảnh sương là thứ sáng nhất, đặc tả 3.4).
	const fine = new Uint32Array( 64 );
	hist.forEach( ( n, i ) => {
		fine[ Math.floor( i / 4 ) ] += n;
	} );
	let gapBin = 32;
	for ( let i = 32; i < 48; i++ ) {
		if ( fine[ i ] < fine[ gapBin ] ) {
			gapBin = i;
		}
	}
	const skyCut = ( gapBin + 0.5 ) / 64;
	console.log( `khe trời / núi trong histogram: quanh ${ skyCut.toFixed( 3 ) } (${ pct( fine[ gapBin ] / total ) } ảnh) → gợi ý uSkyLumLow ≈ ${ ( skyCut - 0.04 ).toFixed( 2 ) }, uSkyLumHigh ≈ ${ ( skyCut + 0.05 ).toFixed( 2 ) }` );

	// Đường chân trời (silhouette rặng xa nhất): mỗi cột, hàng đầu tiên từ trên xuống mà độ sáng (trung bình khối 9 × 9) dưới ngưỡng khe.
	const box = ( cx, cy ) => {
		let s = 0;
		let n = 0;
		for ( let y = Math.max( 0, cy - 4 ); y <= Math.min( h - 1, cy + 4 ); y++ ) {
			for ( let x = Math.max( 0, cx - 4 ); x <= Math.min( w - 1, cx + 4 ); x++ ) {
				s += lum[ y * w + x ];
				n++;
			}
		}
		return s / n;
	};
	const COLS = 40;
	const sil = [];
	for ( let k = 0; k <= COLS; k++ ) {
		const cx = Math.min( w - 1, Math.round( ( k / COLS ) * ( w - 1 ) ) );
		let yy = h - 1;
		for ( let y = 0; y < h; y += 2 ) {
			if ( box( cx, y ) < skyCut ) {
				yy = y;
				break;
			}
		}
		sil.push( yy / h );
	}
	const sorted = [ ...sil ].sort( ( a, b ) => a - b );
	const hi = sil.indexOf( sorted[ 0 ] );
	console.log( `\nđường chân trời (silhouette rặng xa nhất, y 0–1 từ trên; x mỗi ${ 100 / COLS }%):` );
	for ( let r = 0; r <= COLS; r += 10 ) {
		console.log( '  ' + sil.slice( r, r + 10 ).map( ( v, i ) => `x${ ( ( r + i ) / COLS ).toFixed( 3 ) }:${ v.toFixed( 3 ) }` ).join( ' ' ) );
	}
	console.log( `  cao nhất y = ${ sorted[ 0 ].toFixed( 3 ) } tại x ≈ ${ ( hi / COLS ).toFixed( 3 ) }; trung vị ${ sorted[ Math.floor( sorted.length / 2 ) ].toFixed( 3 ) }; thấp nhất ${ sorted[ sorted.length - 1 ].toFixed( 3 ) }` );
} else {
	warn.push( `scene-source là ${ kind }: script chỉ giải mã PNG nên không in được histogram (xuất thêm bản PNG để kiểm tra)` );
}

// ---------------------------------------------------------------------------------------------------------------------------------------
// 2. Ảnh phụ.
console.log( '\n== plates/ phụ ==' );
for ( const base of [ 'depth', 'clouds' ] ) {
	const p = [ 'png', 'webp', 'jpg', 'jpeg' ].map( ( e ) => join( PLATES, `${ base }.${ e }` ) ).find( existsSync );
	if ( ! p ) {
		console.log( `${ base }: không có (tùy chọn)` );
		continue;
	}
	const b = readFileSync( p );
	const k = sniff( b );
	const [ w, h ] = dims( b, k );
	const e = p.split( '.' ).pop().replace( 'jpeg', 'jpg' );
	console.log( `${ base }: ${ p.slice( PLATES.length + 1 ) } ${ w } × ${ h } (tỉ lệ ${ ( w / h ).toFixed( 3 ) }), định dạng thật ${ k }` );
	if ( k !== e ) {
		warn.push( `${ base }.${ e } thật ra là ${ k }` );
	}
	if ( base === 'depth' && Math.abs( w / h - ratio ) > 0.01 ) {
		warn.push( `depth tỉ lệ ${ ( w / h ).toFixed( 3 ) } khác ảnh nguồn ${ ratio.toFixed( 3 ) }: sẽ lệch khi phủ lên ảnh` );
	}
}

// ---------------------------------------------------------------------------------------------------------------------------------------
// 3. Moodboard và hiện trạng.
console.log( '\n== mood/, current/ ==' );
const list = ( d ) => ( existsSync( d ) ? readdirSync( d ).filter( ( f ) => ! f.startsWith( '.' ) ) : [] );
const mood = list( join( REFS, 'mood' ) );
console.log( `mood: ${ mood.length } ảnh (đặc tả: mood-01 … mood-08) → ${ mood.join( ', ' ) || '(trống)' }` );
for ( const f of mood ) {
	const k = sniff( readFileSync( join( REFS, 'mood', f ) ) );
	const e = f.split( '.' ).pop().toLowerCase().replace( 'jpeg', 'jpg' );
	if ( k !== e ) {
		warn.push( `mood/${ f } thật ra là ${ k }` );
	}
}
if ( mood.length < 8 ) {
	warn.push( `mood có ${ mood.length }/8 ảnh` );
}
const cur = list( join( REFS, 'current' ) );
const want = [ 'before-desktop-p00.png', 'before-desktop-p50.png', 'before-desktop-p100.png', 'before-mobile-p50.png' ];
const missing = want.filter( ( f ) => ! cur.includes( f ) );
console.log( `current: ${ cur.length } ảnh → ${ cur.join( ', ' ) || '(trống)' }` );
if ( missing.length ) {
	warn.push( `current thiếu tên đặc tả: ${ missing.join( ', ' ) }` );
}

// ---------------------------------------------------------------------------------------------------------------------------------------
console.log( '\n== kết luận ==' );
warn.forEach( ( m ) => console.log( '  cảnh báo: ' + m ) );
fail.forEach( ( m ) => console.log( '  KHÔNG ĐẠT: ' + m ) );
console.log( fail.length ? 'Ảnh nguồn KHÔNG ĐẠT.' : 'Ảnh nguồn đạt yêu cầu tối thiểu.' );
process.exitCode = fail.length ? 1 : 0;
