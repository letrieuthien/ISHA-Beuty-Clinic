#!/usr/bin/env node
/**
 * Đo chuyển động giữa các ảnh chụp cùng một khung theo thời gian (tiêu chí nghiệm thu "không cuộn trong 30 giây, khung hình vẫn có chuyển
 * động", docs/isha-lookdev-prompt-v3.md mục 12, 19). Ảnh lấy từ tools/shot.mjs --times. Chạy từ gốc repo:
 *
 *   node tools/motion-check.mjs tools/out/a-t000.png tools/out/a-t010.png ...
 *
 * Với từng cặp liền nhau: chênh lệch trung bình (0 – 255), tỉ lệ pixel đổi quá ngưỡng 2 và 8 (bỏ qua 2 bậc của grain / dither), và phần
 * khung (lưới 4 × 4) có chuyển động. Bỏ qua dải nhãn ở góc trên trái (100 px đầu). PNG 8 bit RGB / RGBA, giải mã bằng zlib.
 */
import { readFileSync } from 'node:fs';
import { inflateSync } from 'node:zlib';

function decode( file ) {
	const buf = readFileSync( file );
	let pos = 8;
	let w = 0;
	let h = 0;
	let type = 0;
	const idat = [];
	while ( pos < buf.length ) {
		const len = buf.readUInt32BE( pos );
		const name = buf.toString( 'latin1', pos + 4, pos + 8 );
		const body = buf.subarray( pos + 8, pos + 8 + len );
		if ( name === 'IHDR' ) {
			w = body.readUInt32BE( 0 );
			h = body.readUInt32BE( 4 );
			type = body[ 9 ];
		} else if ( name === 'IDAT' ) {
			idat.push( body );
		} else if ( name === 'IEND' ) {
			break;
		}
		pos += 12 + len;
	}
	const ch = { 2: 3, 6: 4 }[ type ];
	const raw = inflateSync( Buffer.concat( idat ) );
	const stride = w * ch;
	const o = Buffer.alloc( stride * h );
	for ( let y = 0; y < h; y++ ) {
		const f = raw[ y * ( stride + 1 ) ];
		const s = y * ( stride + 1 ) + 1;
		const d = y * stride;
		for ( let x = 0; x < stride; x++ ) {
			const a = x >= ch ? o[ d + x - ch ] : 0;
			const b = y > 0 ? o[ d - stride + x ] : 0;
			const c = x >= ch && y > 0 ? o[ d - stride + x - ch ] : 0;
			let v = raw[ s + x ];
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
			o[ d + x ] = v & 255;
		}
	}
	return { w, h, ch, px: o };
}

const files = process.argv.slice( 2 );
if ( files.length < 2 ) {
	console.log( 'Cần ít nhất hai ảnh.' );
	process.exit( 1 );
}
const imgs = files.map( decode );
for ( let k = 1; k < imgs.length; k++ ) {
	const a = imgs[ k - 1 ];
	const b = imgs[ k ];
	if ( a.w !== b.w || a.h !== b.h ) {
		console.log( 'Khác kích thước:', files[ k - 1 ], files[ k ] );
		continue;
	}
	let sum = 0;
	let n = 0;
	let over2 = 0;
	let over8 = 0;
	const cells = new Float64Array( 16 );
	const cellN = new Float64Array( 16 );
	for ( let y = 100; y < a.h; y += 2 ) {
		for ( let x = 0; x < a.w; x += 2 ) {
			const pa = ( y * a.w + x ) * a.ch;
			const pb = ( y * b.w + x ) * b.ch;
			const dlt = ( Math.abs( a.px[ pa ] - b.px[ pb ] ) + Math.abs( a.px[ pa + 1 ] - b.px[ pb + 1 ] ) + Math.abs( a.px[ pa + 2 ] - b.px[ pb + 2 ] ) ) / 3;
			sum += dlt;
			n++;
			if ( dlt > 2 ) {
				over2++;
			}
			if ( dlt > 8 ) {
				over8++;
			}
			const c = Math.min( 3, Math.floor( ( y / a.h ) * 4 ) ) * 4 + Math.min( 3, Math.floor( ( x / a.w ) * 4 ) );
			cells[ c ] += dlt > 2 ? 1 : 0;
			cellN[ c ]++;
		}
	}
	const moving = [ ...cells ].filter( ( v, i ) => v / cellN[ i ] > 0.02 ).length;
	console.log( `${ files[ k - 1 ].split( /[\\/]/ ).pop() } → ${ files[ k ].split( /[\\/]/ ).pop() }: chênh TB ${ ( sum / n ).toFixed( 2 ) }, đổi > 2: ${ ( ( over2 / n ) * 100 ).toFixed( 1 ) }%, > 8: ${ ( ( over8 / n ) * 100 ).toFixed( 2 ) }%, ô 4×4 có chuyển động: ${ moving }/16` );
}
