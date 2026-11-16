#!/usr/bin/env node
/**
 * Tách mây từ ảnh trời xanh (tools/refs/plates/clouds.*) thành mặt nạ xám: mây trắng trên nền đen, cho lớp mây của scene/sky.js
 * (docs/isha-lookdev-prompt-v3.md mục 8). Chạy từ gốc repo: node tools/bake-clouds.mjs [đầu vào] [đầu ra]
 * Mặc định ra tools/out/clouds-mask.png (lookdev tự dùng nếu có). Bước tích hợp đổi sang webp trong assets/img/scene/.
 *
 * Cách tách: mây gần trung tính (R ≈ B), trời xanh có R / B thấp. Nền trời đổi dần theo vị trí nên trừ đi "nền" là phân vị 20% của R / B
 * trong từng ô (mây chiếm thiểu số), nội suy song tuyến; phần vượt nền qua smoothstep thành độ phủ mây. Xoá dấu watermark ✦ ở góc dưới
 * phải (vùng x > 0.93, y > 0.86 của ảnh, mép mềm). Ảnh vào: PNG 8 bit RGB / RGBA (giải mã bằng zlib, không thư viện). Ra: PNG xám 1/2 kích thước.
 */
import { readFileSync, writeFileSync, mkdirSync, existsSync } from 'node:fs';
import { join, dirname, resolve } from 'node:path';
import { fileURLToPath } from 'node:url';
import { inflateSync, deflateSync, crc32 } from 'node:zlib';

const ROOT = resolve( dirname( fileURLToPath( import.meta.url ) ), '..' );
const input = process.argv[ 2 ] || [ 'png', 'jpg', 'webp' ].map( ( e ) => join( ROOT, 'tools/refs/plates', `clouds.${ e }` ) ).find( existsSync );
const output = process.argv[ 3 ] || join( ROOT, 'tools/out/clouds-mask.png' );
if ( ! input || ! existsSync( input ) ) {
	console.log( 'Không có tools/refs/plates/clouds.*: sky.js sẽ dùng mây fbm.' );
	process.exit( 0 );
}
const buf = readFileSync( input );
if ( buf[ 0 ] !== 0x89 || buf.toString( 'latin1', 1, 4 ) !== 'PNG' ) {
	console.log( `${ input } không phải PNG (nội dung thật). Xuất lại PNG rồi chạy lại.` );
	process.exit( 1 );
}

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
		if ( body[ 8 ] !== 8 || body[ 12 ] ) {
			throw new Error( 'cần PNG 8 bit không interlace' );
		}
	} else if ( name === 'IDAT' ) {
		idat.push( body );
	} else if ( name === 'IEND' ) {
		break;
	}
	pos += 12 + len;
}
const ch = { 2: 3, 6: 4 }[ type ];
if ( ! ch ) {
	throw new Error( 'cần PNG màu (RGB / RGBA)' );
}
const raw = inflateSync( Buffer.concat( idat ) );
const stride = w * ch;
const px = Buffer.alloc( stride * h );
for ( let y = 0; y < h; y++ ) {
	const f = raw[ y * ( stride + 1 ) ];
	const s = y * ( stride + 1 ) + 1;
	const d = y * stride;
	for ( let x = 0; x < stride; x++ ) {
		const a = x >= ch ? px[ d + x - ch ] : 0;
		const b = y > 0 ? px[ d - stride + x ] : 0;
		const c = x >= ch && y > 0 ? px[ d - stride + x - ch ] : 0;
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
		px[ d + x ] = v & 255;
	}
}

// 1/2 kích thước, R / B trung bình khối 2 × 2.
const W = w >> 1;
const H = h >> 1;
const wr = new Float32Array( W * H );
for ( let y = 0; y < H; y++ ) {
	for ( let x = 0; x < W; x++ ) {
		let r = 0;
		let b = 0;
		for ( let k = 0; k < 4; k++ ) {
			const p = ( ( y * 2 + ( k >> 1 ) ) * w + x * 2 + ( k & 1 ) ) * ch;
			r += px[ p ];
			b += px[ p + 2 ];
		}
		wr[ y * W + x ] = r / Math.max( 1, b );
	}
}
const T = 48;
const GX = Math.ceil( W / T );
const GY = Math.ceil( H / T );
const base = new Float32Array( GX * GY );
for ( let gy = 0; gy < GY; gy++ ) {
	for ( let gx = 0; gx < GX; gx++ ) {
		const v = [];
		for ( let y = gy * T; y < Math.min( H, gy * T + T ); y++ ) {
			for ( let x = gx * T; x < Math.min( W, gx * T + T ); x++ ) {
				v.push( wr[ y * W + x ] );
			}
		}
		v.sort( ( a, b ) => a - b );
		base[ gy * GX + gx ] = v[ Math.floor( v.length * 0.2 ) ];
	}
}
const sm = ( a, b, x ) => {
	const t = Math.min( 1, Math.max( 0, ( x - a ) / ( b - a ) ) );
	return t * t * ( 3 - 2 * t );
};
const out = Buffer.alloc( W * H );
let cover = 0;
for ( let y = 0; y < H; y++ ) {
	for ( let x = 0; x < W; x++ ) {
		const fx = Math.min( GX - 1.001, Math.max( 0, x / T - 0.5 ) );
		const fy = Math.min( GY - 1.001, Math.max( 0, y / T - 0.5 ) );
		const x0 = Math.floor( fx );
		const y0 = Math.floor( fy );
		const tx = fx - x0;
		const ty = fy - y0;
		const bs = ( base[ y0 * GX + x0 ] * ( 1 - tx ) + base[ y0 * GX + x0 + 1 ] * tx ) * ( 1 - ty ) + ( base[ ( y0 + 1 ) * GX + x0 ] * ( 1 - tx ) + base[ ( y0 + 1 ) * GX + x0 + 1 ] * tx ) * ty;
		let m = sm( 0.02, 0.32, wr[ y * W + x ] - bs );
		// watermark ✦ góc dưới phải
		m *= 1 - sm( 0.92, 0.94, x / W ) * sm( 0.85, 0.87, y / H );
		if ( m > 0.1 ) {
			cover++;
		}
		out[ y * W + x ] = Math.round( m * 255 );
	}
}

// PNG xám 8 bit.
const rows = Buffer.alloc( ( W + 1 ) * H );
for ( let y = 0; y < H; y++ ) {
	out.copy( rows, y * ( W + 1 ) + 1, y * W, ( y + 1 ) * W );
}
const chunk = ( n, b ) => {
	const l = Buffer.alloc( 4 );
	l.writeUInt32BE( b.length );
	const nb = Buffer.concat( [ Buffer.from( n, 'latin1' ), b ] );
	const c = Buffer.alloc( 4 );
	c.writeUInt32BE( crc32( nb ) >>> 0 );
	return Buffer.concat( [ l, nb, c ] );
};
const ih = Buffer.alloc( 13 );
ih.writeUInt32BE( W, 0 );
ih.writeUInt32BE( H, 4 );
ih[ 8 ] = 8;
ih[ 9 ] = 0;
mkdirSync( dirname( output ), { recursive: true } );
writeFileSync( output, Buffer.concat( [ Buffer.from( [ 137, 80, 78, 71, 13, 10, 26, 10 ] ), chunk( 'IHDR', ih ), chunk( 'IDAT', deflateSync( rows, { level: 9 } ) ), chunk( 'IEND', Buffer.alloc( 0 ) ) ] ) );
console.log( `${ output.slice( ROOT.length + 1 ) }: ${ W } × ${ H }, mây phủ ${ ( ( cover / W / H ) * 100 ).toFixed( 1 ) }%` );
