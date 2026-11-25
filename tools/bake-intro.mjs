#!/usr/bin/env node
/**
 * Dựng tài nguyên của màn chờ "Bình minh" (section #lieu-trinh, trang nhóm dịch vụ) vào wp-content/themes/ISHA/assets/img/intro/.
 * Chạy từ gốc repo, cần Apache XAMPP đang chạy (trình duyệt tải ảnh nguồn qua HTTP, tránh canvas bị "tainted"):
 *
 *   node tools/bake-intro.mjs [--base=http://localhost/ISHA-Beuty-Clinic/]
 *
 * Ảnh nguồn (assets/images/Visual_1-1.png, trang 1 của "Visual Element preview": ba lớp núi thanh dọc) có NỀN TRẮNG ĐẶC, nét nâu #996A35 mờ dần
 * về trắng, nên kênh alpha suy từ độ đậm của nét: alpha = (255 − R) / (255 − 153), màu cố định (153, 106, 53). Ra:
 *   - ridge.webp       2560 px, có alpha: ba lớp núi. Máy không có cwebp / sharp nên dùng bộ mã hoá WebP của Chrome headless (canvas.toDataURL).
 *   - ridge-mask.svg   silhouette đặc của đường đỉnh núi (hợp của ba lớp; đỉnh từng thanh nối bằng đường mượt, hạ xuống INSET px so với đầu
 *                      thanh mờ). CSS dùng làm mask cho lớp "nền che": mặt trời và tia nằm SAU lớp này nên bị núi che đúng như nhô lên sau núi,
 *                      còn bản thân lớp che cùng màu nền nên không thấy. Cùng khung 1600 × 996 với ridge.webp (cùng tỉ lệ).
 *   - sun.svg          đĩa mặt trời (vầng sáng + đĩa + viền mảnh), tạo hình riêng theo Visual_7.png (đĩa "trống" ôm bởi tia mảnh).
 *   - rays-near.svg    vòng tia dày quanh đĩa; rays-far.svg tia dài đứt đoạn và mảnh vụn xa (Visual_7: tia dài bị cắt thành đoạn).
 *     Cả hai vẽ trong hệ toạ độ đơn vị = bán kính đĩa (viewBox đối xứng quanh tâm) để CSS đặt theo --isha-intro-r, và mờ dần ở mép ngoài.
 *     Sinh có hạt giống cố định (cùng lệnh → cùng ảnh), không dùng Math.random.
 */
import { spawn } from 'node:child_process';
import { existsSync, mkdirSync, writeFileSync, statSync } from 'node:fs';
import { join, dirname, resolve } from 'node:path';
import { fileURLToPath } from 'node:url';
import { tmpdir } from 'node:os';

const ROOT = resolve( dirname( fileURLToPath( import.meta.url ) ), '..' );
const THEME = 'wp-content/themes/ISHA';
const OUT = join( ROOT, THEME, 'assets/img/intro' );
const arg = ( name, def ) => {
	const a = process.argv.find( ( x ) => x.startsWith( `--${ name }=` ) );
	return a ? a.slice( name.length + 3 ) : def;
};
const BASE = arg( 'base', 'http://localhost/ISHA-Beuty-Clinic/' );
const SRC = `${ THEME }/assets/images/Visual_1-1.png`;

const INK = '#996a35';       // màu nét của Visual_*.png (153, 106, 53), gần --isha-metal-deep #8e6837
const RIDGE_W = 2560;        // ridge.webp
const RIDGE_Q = 0.92;
const MASK_W = 1600;         // khung toạ độ của ridge-mask.svg (cao theo tỉ lệ ảnh)
const TIP_ALPHA = 6;         // alpha (0 – 255, trên bản 1600 px) của đầu thanh: ngưỡng thấp vì đầu thanh mờ dần về nền
const BAR_ALPHA = 90;        // alpha lớn nhất của cột để coi cột đó có thanh
const HALF_PITCH = 7;        // nửa bước thanh ở khung 1600 (bước ≈ 13.3 px): cửa sổ lấy cực tiểu đầu thanh
const INSET = 3;             // hạ đường đỉnh xuống bấy nhiêu px (khung 1600) so với đầu thanh mờ
const SMOOTH = 7;            // bán kính làm mượt đường đỉnh (px)
const STEP = 4;              // bước lấy mẫu của đường đỉnh (px)

const BROWSERS = [
	process.env.BROWSER,
	'C:/Program Files/Google/Chrome/Application/chrome.exe',
	'C:/Program Files (x86)/Google/Chrome/Application/chrome.exe',
	'C:/Program Files (x86)/Microsoft/Edge/Application/msedge.exe',
	'C:/Program Files/Microsoft/Edge/Application/msedge.exe',
	'/Applications/Google Chrome.app/Contents/MacOS/Google Chrome',
	'/usr/bin/google-chrome',
	'/usr/bin/chromium',
];
const sleep = ( ms ) => new Promise( ( r ) => setTimeout( r, ms ) );
const kb = ( f ) => ( statSync( f ).size / 1024 ).toFixed( 1 ) + ' KB';

// ---------------------------------------------------------------------------------------------------------------------------------
// Phần 1: núi (cần trình duyệt)
// ---------------------------------------------------------------------------------------------------------------------------------
const exe = BROWSERS.find( ( p ) => p && existsSync( p ) );
if ( ! exe ) {
	console.error( 'Không tìm thấy Chrome / Edge.' );
	process.exit( 1 );
}
if ( ! existsSync( join( ROOT, SRC ) ) ) {
	console.error( `Thiếu ${ SRC }.` );
	process.exit( 1 );
}
mkdirSync( OUT, { recursive: true } );

const port = 9500 + Math.floor( Math.random() * 300 );
const proc = spawn( exe, [ '--headless=new', `--remote-debugging-port=${ port }`, '--no-first-run', '--hide-scrollbars', `--user-data-dir=${ join( tmpdir(), 'isha-bake-intro-' + port ) }`, 'about:blank' ], { stdio: 'ignore' } );
try {
	let page;
	for ( let i = 0; i < 150 && ! page; i++ ) {
		try {
			page = ( await ( await fetch( `http://127.0.0.1:${ port }/json` ) ).json() ).find( ( t ) => t.type === 'page' );
		} catch ( err ) {
			await sleep( 200 );
		}
	}
	if ( ! page ) {
		throw new Error( 'không kết nối được DevTools' );
	}
	const ws = new WebSocket( page.webSocketDebuggerUrl );
	await new Promise( ( r, f ) => {
		ws.addEventListener( 'open', r );
		ws.addEventListener( 'error', f );
	} );
	let id = 0;
	const pending = new Map();
	ws.addEventListener( 'message', ( ev ) => {
		const m = JSON.parse( ev.data );
		if ( m.id && pending.has( m.id ) ) {
			pending.get( m.id )( m );
			pending.delete( m.id );
		}
	} );
	const send = ( method, params = {} ) => new Promise( ( r ) => {
		const i = ++id;
		pending.set( i, r );
		ws.send( JSON.stringify( { id: i, method, params } ) );
	} );
	await send( 'Page.enable' );
	// Mở chính tệp ảnh làm trang: cùng origin nên canvas đọc được pixel.
	await send( 'Page.navigate', { url: BASE + SRC + '?v=' + Date.now() } );
	await sleep( 1200 );

	const expr = `(async () => {
		const img = new Image();
		img.src = location.href;
		await img.decode();
		const NW = img.naturalWidth, NH = img.naturalHeight;

		// Nền trắng đặc + nét nâu → RGBA (màu cố định, alpha theo độ đậm).
		const full = document.createElement( 'canvas' );
		full.width = NW;
		full.height = NH;
		const fg = full.getContext( '2d', { willReadFrequently: true } );
		fg.drawImage( img, 0, 0 );
		const im = fg.getImageData( 0, 0, NW, NH );
		const s = im.data;
		for ( let i = 0; i < s.length; i += 4 ) {
			const a = Math.min( 1, Math.max( 0, ( 255 - s[ i ] ) / 102 ) );
			s[ i ] = 153; s[ i + 1 ] = 106; s[ i + 2 ] = 53; s[ i + 3 ] = Math.round( a * 255 );
		}
		fg.putImageData( im, 0, 0 );

		const scaled = ( w ) => {
			const h = Math.round( NH * w / NW );
			const c = document.createElement( 'canvas' );
			c.width = w;
			c.height = h;
			const g = c.getContext( '2d', { willReadFrequently: true } );
			g.imageSmoothingQuality = 'high';
			g.drawImage( full, 0, 0, w, h );
			return { c, g, w, h };
		};

		// Đường đỉnh: đầu thanh cao nhất của từng cột (hợp ba lớp), điền khe giữa thanh bằng nội suy, làm mượt.
		// Cột "có thanh" = alpha lớn nhất của cột đủ đậm (thanh mảnh của lớp sau chỉ rộng khoảng 1 px ở khung 1600, đầu thanh mờ dần nên
		// đầu thanh dùng ngưỡng thấp).
		const sm = scaled( ${ MASK_W } );
		const W = sm.w, H = sm.h;
		const d = sm.g.getImageData( 0, 0, W, H ).data;
		const top = new Array( W ).fill( -1 );
		for ( let x = 0; x < W; x++ ) {
			let mx = 0, first = -1;
			for ( let y = 0; y < H; y++ ) {
				const a = d[ ( y * W + x ) * 4 + 3 ];
				if ( a > mx ) mx = a;
				if ( first < 0 && a >= ${ TIP_ALPHA } ) first = y;
			}
			if ( mx >= ${ BAR_ALPHA } ) top[ x ] = first;
		}
		// Bỏ điểm lạc: cao hơn trung vị lân cận (±45 px) quá 70 px (chấm nhiễu trong ảnh nguồn).
		const clean = top.slice();
		for ( let x = 0; x < W; x++ ) {
			if ( top[ x ] < 0 ) continue;
			const w = [];
			for ( let k = Math.max( 0, x - 45 ); k <= Math.min( W - 1, x + 45 ); k++ ) if ( top[ k ] >= 0 ) w.push( top[ k ] );
			w.sort( ( p, q ) => p - q );
			if ( top[ x ] < w[ w.length >> 1 ] - 70 ) clean[ x ] = -1;
		}
		// Ba lớp thanh KHÔNG trùng cột (thanh dày lớp trước rộng 4 – 5 px nằm ở cột thanh mảnh lớp sau không có): đầu thanh theo từng cột nhảy
		// giữa các lớp. Lấy cực tiểu trong cửa sổ ±HALF_PITCH (nửa bước thanh) để luôn bắt được thanh cao nhất, tức lớp sau cùng.
		const env = new Array( W ).fill( -1 );
		for ( let x = 0; x < W; x++ ) {
			let m = -1;
			for ( let k = Math.max( 0, x - ${ HALF_PITCH } ); k <= Math.min( W - 1, x + ${ HALF_PITCH } ); k++ ) {
				if ( clean[ k ] >= 0 && ( m < 0 || clean[ k ] < m ) ) m = clean[ k ];
			}
			env[ x ] = m;
		}
		clean.splice( 0, W, ...env );
		// Nội suy tuyến tính qua khe (và ngoại suy hằng ở hai đầu).
		const valid = [];
		for ( let x = 0; x < W; x++ ) if ( clean[ x ] >= 0 ) valid.push( x );
		const prof = new Array( W );
		let j = 0;
		for ( let x = 0; x < W; x++ ) {
			while ( j + 1 < valid.length && valid[ j + 1 ] <= x ) j++;
			const a = valid[ j ], b = valid[ Math.min( j + 1, valid.length - 1 ) ];
			if ( x <= valid[ 0 ] ) prof[ x ] = clean[ valid[ 0 ] ];
			else if ( x >= valid[ valid.length - 1 ] ) prof[ x ] = clean[ valid[ valid.length - 1 ] ];
			else prof[ x ] = a === b ? clean[ a ] : clean[ a ] + ( clean[ b ] - clean[ a ] ) * ( x - a ) / ( b - a );
		}
		// Làm mượt (hộp), hạ xuống INSET.
		const out = [];
		for ( let x = 0; x < W; x += ${ STEP } ) {
			let sum = 0, n = 0;
			for ( let k = Math.max( 0, x - ${ SMOOTH } ); k <= Math.min( W - 1, x + ${ SMOOTH } ); k++ ) { sum += prof[ k ]; n++; }
			out.push( [ x, Math.min( H, sum / n + ${ INSET } ) ] );
		}
		out.push( [ W, out[ out.length - 1 ][ 1 ] ] );

		// ridge.webp. Ảnh nguồn có chấm lạc phía trên núi: xoá mọi thứ cao hơn đường đỉnh quá 8 px (khung 1600), chừa đầu thanh mờ.
		const big = scaled( ${ RIDGE_W } );
		const k = big.w / W;
		for ( let x = 0; x < W; x++ ) {
			const cut = Math.max( 0, Math.floor( ( prof[ x ] - 8 ) * k ) );
			if ( cut > 0 ) big.g.clearRect( Math.floor( x * k ), 0, Math.ceil( k ), cut );
		}
		const webp = big.c.toDataURL( 'image/webp', ${ RIDGE_Q } );
		return { webp, w: big.w, h: big.h, W, H, pts: out, bars: valid.length };
	})()`;
	const r = await send( 'Runtime.evaluate', { expression: expr, awaitPromise: true, returnByValue: true } );
	const v = r.result?.result?.value;
	if ( ! v || ! v.webp.startsWith( 'data:image/webp' ) ) {
		throw new Error( 'không xử lý được ảnh núi: ' + JSON.stringify( r.result?.exceptionDetails || r.result ) );
	}
	const ridgeFile = join( OUT, 'ridge.webp' );
	writeFileSync( ridgeFile, Buffer.from( v.webp.split( ',' )[ 1 ], 'base64' ) );
	console.log( `ridge.webp: ${ v.w } × ${ v.h }, ${ kb( ridgeFile ) } (${ v.bars } cột có thanh)` );

	const f1 = ( n ) => n.toFixed( 1 ).replace( /\.0$/, '' );
	const d = 'M0,' + f1( v.H ) + ' L' + v.pts.map( ( p ) => f1( p[ 0 ] ) + ',' + f1( p[ 1 ] ) ).join( ' L' ) + ` L${ v.W },${ f1( v.H ) } Z`;
	const maskFile = join( OUT, 'ridge-mask.svg' );
	writeFileSync( maskFile, `<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 ${ v.W } ${ v.H }" preserveAspectRatio="none"><path d="${ d }"/></svg>\n` );
	console.log( `ridge-mask.svg: ${ v.W } × ${ v.H }, ${ v.pts.length } điểm, ${ kb( maskFile ) }` );
	// Số liệu cho intro-layout.js (RIDGE_ART): đỉnh đầu tiên bên trái (cực tiểu y trong 40% chiều rộng đầu, bỏ cột sát mép) và độ cao đường đỉnh
	// tại 33 điểm đều. Chạy lại script (đổi ảnh núi, SMOOTH, INSET…) thì chép hai dòng này vào RIDGE_ART.
	const ptsN = v.pts.length;
	let peak = null;
	for ( let i = 1; i < ptsN; i++ ) {
		const [ px, py ] = v.pts[ i ];
		if ( px < 0.04 * v.W || px > 0.4 * v.W ) continue;
		let ok = true;
		for ( let k = Math.max( 0, i - 15 ); k <= Math.min( ptsN - 1, i + 15 ); k++ ) if ( v.pts[ k ][ 1 ] < py ) ok = false;
		if ( ok ) { peak = [ px / v.W, py / v.H ]; break; }
	}
	const top33 = Array.from( { length: 33 }, ( _, i ) => +( v.pts[ Math.round( ( i / 32 ) * ( ptsN - 1 ) ) ][ 1 ] / v.H ).toFixed( 3 ) );
	console.log( `RIDGE_ART.peakX = ${ peak ? peak[ 0 ].toFixed( 3 ) : '?' }, peakY = ${ peak ? peak[ 1 ].toFixed( 3 ) : '?' }` );
	console.log( `RIDGE_ART.top = [ ${ top33.join( ', ' ) } ]` );
	// Đường đỉnh thô cho việc chỉnh (đặt vị trí mặt trời): tools/out/ không đưa vào git.
	mkdirSync( join( ROOT, 'tools/out' ), { recursive: true } );
	writeFileSync( join( ROOT, 'tools/out/intro-ridge.json' ), JSON.stringify( { w: v.W, h: v.H, pts: v.pts } ) );
	ws.close();
} finally {
	proc.kill();
}

// ---------------------------------------------------------------------------------------------------------------------------------
// Phần 2: mặt trời và tia (thuần Node)
// ---------------------------------------------------------------------------------------------------------------------------------
// PRNG có hạt giống (mulberry32): cùng lệnh, cùng ảnh.
function rng( seed ) {
	let a = seed >>> 0;
	return () => {
		a = ( a + 0x6d2b79f5 ) >>> 0;
		let t = a;
		t = Math.imul( t ^ ( t >>> 15 ), t | 1 );
		t ^= t + Math.imul( t ^ ( t >>> 7 ), t | 61 );
		return ( ( t ^ ( t >>> 14 ) ) >>> 0 ) / 4294967296;
	};
}
const n2 = ( v ) => ( +v.toFixed( 2 ) ).toString();
const seg = ( ang, r0, r1, w, op ) => {
	const c = Math.cos( ang ), s = Math.sin( ang );
	return `<path d="M${ n2( c * r0 ) },${ n2( s * r0 ) }L${ n2( c * r1 ) },${ n2( s * r1 ) }" stroke-width="${ w }"${ op < 0.995 ? ` stroke-opacity="${ n2( op ) }"` : '' }/>`;
};
const fadeMask = ( R, from ) => `<defs><radialGradient id="g" cx="0" cy="0" r="${ R }" gradientUnits="userSpaceOnUse"><stop offset="${ from }" stop-color="#fff"/><stop offset="1" stop-color="#fff" stop-opacity="0"/></radialGradient><mask id="m" maskUnits="userSpaceOnUse" x="${ -R }" y="${ -R }" width="${ 2 * R }" height="${ 2 * R }"><circle r="${ R }" fill="url(#g)"/></mask></defs>`;
const svgOpen = ( R ) => `<svg xmlns="http://www.w3.org/2000/svg" viewBox="${ -R } ${ -R } ${ 2 * R } ${ 2 * R }">`;

const R0 = 1.12;     // tia bắt đầu cách tâm 1.12 bán kính đĩa (khoảng trống như Visual_7)
const N_NEAR = 144;  // 2.5° một tia
const angOf = ( i ) => ( i / N_NEAR ) * Math.PI * 2;

// Vòng tia dày: độ dài đổi mượt theo góc (hai sóng) cộng nhiễu, mỗi tia thứ sáu dài hơn.
{
	const r = rng( 7 );
	const R = 9;
	const parts = [];
	for ( let i = 0; i < N_NEAR; i++ ) {
		const a = angOf( i );
		let len = 2.6 + 1.5 * ( 0.5 + 0.5 * Math.sin( a * 3 + 0.7 ) ) + 1.1 * ( 0.5 + 0.5 * Math.sin( a * 7 + 2.1 ) ) + 1.2 * r();
		if ( i % 6 === 0 ) len *= 1.55;
		parts.push( seg( a, R0, Math.min( 8.6, R0 + len ), 0.034, 0.5 + 0.45 * r() ) );
	}
	writeFileSync( join( OUT, 'rays-near.svg' ), `${ svgOpen( R ) }${ fadeMask( R, 0.62 ) }<g mask="url(#m)" fill="none" stroke="${ INK }" stroke-linecap="butt">${ parts.join( '' ) }</g></svg>\n` );
}

// Tia xa: vài tia liền toàn chiều dài, tia dài đứt đoạn (tiếp nối tia gần), mảnh vụn rải ở xa.
{
	const r = rng( 23 );
	const R = 19;
	const parts = [];
	for ( let i = 0; i < N_NEAR; i += 18 ) {
		parts.push( seg( angOf( i ), R0, 18.4, 0.026, 0.4 ) );
	}
	for ( let i = 0; i < N_NEAR; i += 3 ) {
		if ( i % 18 === 0 ) continue;
		const a = angOf( i );
		let at = 8.9 + 2.2 * r();
		const parts2 = 1 + Math.floor( r() * 3 );
		for ( let k = 0; k < parts2 && at < 18; k++ ) {
			const len = 2.2 + 5.5 * r();
			parts.push( seg( a, at, Math.min( 18.4, at + len ), 0.03, 0.4 + 0.5 * r() ) );
			at += len + 1.1 + 2.2 * r();
		}
	}
	for ( let k = 0; k < 90; k++ ) {
		const a = angOf( Math.floor( r() * N_NEAR ) );
		const at = 10 + 8 * r();
		parts.push( seg( a, at, Math.min( 18.6, at + 0.7 + 1.9 * r() ), 0.026, 0.3 + 0.4 * r() ) );
	}
	writeFileSync( join( OUT, 'rays-far.svg' ), `${ svgOpen( R ) }${ fadeMask( R, 0.55 ) }<g mask="url(#m)" fill="none" stroke="${ INK }" stroke-linecap="butt">${ parts.join( '' ) }</g></svg>\n` );
}

// Đĩa: vầng sáng ấm, đĩa ngà đặc (che đầu trong của tia khi lớp tia thu nhỏ), viền mảnh.
writeFileSync( join( OUT, 'sun.svg' ), `<svg xmlns="http://www.w3.org/2000/svg" viewBox="-3 -3 6 6"><defs><radialGradient id="h" cx="0" cy="0" r="3" gradientUnits="userSpaceOnUse"><stop offset="0.2" stop-color="#f7e2b0" stop-opacity="0.75"/><stop offset="0.45" stop-color="#f1d79c" stop-opacity="0.28"/><stop offset="1" stop-color="#f1d79c" stop-opacity="0"/></radialGradient><radialGradient id="d" cx="-0.18" cy="-0.22" r="1.25" gradientUnits="userSpaceOnUse"><stop offset="0" stop-color="#fffdf6"/><stop offset="0.7" stop-color="#fbf0d6"/><stop offset="1" stop-color="#f1dbaa"/></radialGradient></defs><circle r="3" fill="url(#h)"/><circle r="1" fill="url(#d)"/><circle r="1" fill="none" stroke="${ INK }" stroke-width="0.024" stroke-opacity="0.7"/></svg>\n` );

for ( const f of [ 'rays-near.svg', 'rays-far.svg', 'sun.svg' ] ) {
	console.log( `${ f }: ${ kb( join( OUT, f ) ) }` );
}
