#!/usr/bin/env node
/**
 * Chuyển ảnh nguồn của cảnh (tools/refs/plates/, khách quản lý, không commit) sang webp trong theme (wp-content/themes/ISHA/assets/img/scene/)
 * cho trang thật dùng (bước 8). Chạy từ gốc repo, cần Apache XAMPP đang chạy (trình duyệt tải ảnh qua HTTP):
 *
 *   node tools/bake-scene.mjs [--base=http://localhost/ISHA-Beuty-Clinic/]
 *
 * Máy không có cwebp / ImageMagick / sharp nên dùng chính bộ mã hoá WebP của Chrome / Edge headless (canvas.toDataURL 'image/webp', giữ kênh
 * alpha cho mây). Ra:
 *   - scene-source.webp    3840 px (desktop; đặc tả yêu cầu ảnh nguồn ≥ 3840), chất lượng 0.86
 *   - scene-source-m.webp  1920 px (điện thoại: ảnh fit theo chiều ngang khung dọc, 1920 đủ cho DPR 3 và dolly), 0.84
 *   - clouds-4.webp, clouds-5.webp  giữ kích thước gốc, có alpha, 0.88
 *   - poster.webp (1920 × 1080) và poster-m.webp (860 × 1864, khung dọc): chính cảnh v3 đã tô màu ở giữa đoạn cuộn (p = 0.5), chụp từ
 *     tools/lookdev.html (SwiftShader, không bụi, không grain: grain phủ bằng CSS). Nền của chế độ tĩnh (giảm chuyển động, không JS),
 *     chế độ 2D và màn che lúc WebGL đang dựng (đặc tả mục 16). Chạy lại sau khi đổi màu / ánh sáng của cảnh. Chỉ poster: --only=poster.
 *   - ../cards/brushed.webp (256 × 256, lặp liền): vân kim loại chải xước của mặt thẻ "Vòng cung ánh kim" (README-card-ring.md), nhiễu
 *     xám kéo ngang (mờ theo trục x, nối vòng nên lặp không thấy mối). Chỉ texture này: --only=brushed.
 * Không có bản đồ độ sâu: độ sâu tự suy từ độ sáng (README mục 9), trang thật không dò depth.webp.
 */
import { spawn } from 'node:child_process';
import { existsSync, mkdirSync, writeFileSync, statSync } from 'node:fs';
import { join, dirname, resolve } from 'node:path';
import { fileURLToPath } from 'node:url';
import { tmpdir } from 'node:os';

const ROOT = resolve( dirname( fileURLToPath( import.meta.url ) ), '..' );
const OUT = join( ROOT, 'wp-content/themes/ISHA/assets/img/scene' );
const arg = ( name, def ) => {
	const a = process.argv.find( ( x ) => x.startsWith( `--${ name }=` ) );
	return a ? a.slice( name.length + 3 ) : def;
};
const BASE = arg( 'base', 'http://localhost/ISHA-Beuty-Clinic/' );
const ONLY = arg( 'only', '' );
const POSTERS = [
	{ out: 'poster.webp', width: 1920, height: 1080, dpr: 1, q: 82 },
	{ out: 'poster-m.webp', width: 430, height: 932, dpr: 2, q: 80 },
];
const JOBS = [
	{ src: 'tools/refs/plates/scene-source.png', out: 'scene-source.webp', width: 3840, q: 0.86 },
	{ src: 'tools/refs/plates/scene-source.png', out: 'scene-source-m.webp', width: 1920, q: 0.84 },
	{ src: 'tools/refs/plates/clouds-4.png', out: 'clouds-4.webp', width: 0, q: 0.88 },
	{ src: 'tools/refs/plates/clouds-5.png', out: 'clouds-5.webp', width: 0, q: 0.88 },
];
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

for ( const j of JOBS ) {
	if ( ! existsSync( join( ROOT, j.src ) ) ) {
		console.error( `Thiếu ${ j.src } (ảnh nguồn của khách, đặt trong tools/refs/plates/).` );
		process.exit( 1 );
	}
}
const exe = BROWSERS.find( ( p ) => p && existsSync( p ) );
if ( ! exe ) {
	console.error( 'Không tìm thấy Chrome / Edge.' );
	process.exit( 1 );
}
mkdirSync( OUT, { recursive: true } );

const port = 9500 + Math.floor( Math.random() * 300 );
const proc = spawn( exe, [ '--headless=new', `--remote-debugging-port=${ port }`, '--no-first-run', '--hide-scrollbars', '--use-angle=swiftshader', '--enable-unsafe-swiftshader', '--ignore-gpu-blocklist', `--user-data-dir=${ join( tmpdir(), 'isha-bake-' + port ) }`, 'about:blank' ], { stdio: 'ignore' } );
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
	// Trang cùng origin với ảnh (tránh canvas bị "tainted" khi xuất).
	await send( 'Page.navigate', { url: BASE + 'tools/lookdev.html?gui=0&cards=0&view=photo' } );
	await sleep( 1500 );
	if ( ! ONLY || ONLY === 'brushed' ) {
		const expr = `(() => {
			const N = 256, c = document.createElement( 'canvas' );
			c.width = c.height = N;
			const g = c.getContext( '2d' ), im = g.createImageData( N, N );
			let seed = 7;
			const rnd = () => ( ( seed = ( seed * 16807 ) % 2147483647 ) / 2147483647 );
			const row = new Float32Array( N );
			for ( let y = 0; y < N; y++ ) {
				const base = ( rnd() - 0.5 ) * 0.5; // mỗi hàng một độ sáng: vân chạy ngang
				for ( let x = 0; x < N; x++ ) row[ x ] = rnd() - 0.5;
				for ( let x = 0; x < N; x++ ) {
					let sum = 0;
					for ( let k = -24; k <= 24; k++ ) sum += row[ ( x + k + N ) % N ];
					const v = 128 + ( sum / 49 * 2.2 + base ) * 110;
					const i = ( y * N + x ) * 4;
					im.data[ i ] = im.data[ i + 1 ] = im.data[ i + 2 ] = Math.max( 0, Math.min( 255, v ) );
					im.data[ i + 3 ] = 255;
				}
			}
			g.putImageData( im, 0, 0 );
			return c.toDataURL( 'image/webp', 0.8 );
		})()`;
		const r = await send( 'Runtime.evaluate', { expression: expr, returnByValue: true } );
		const dir = join( OUT, '..', 'cards' );
		mkdirSync( dir, { recursive: true } );
		const file = join( dir, 'brushed.webp' );
		writeFileSync( file, Buffer.from( r.result.result.value.split( ',' )[ 1 ], 'base64' ) );
		console.log( `cards/brushed.webp: 256 × 256, ${ ( statSync( file ).size / 1024 ).toFixed( 1 ) } KB` );
	}
	for ( const j of ONLY ? [] : JOBS ) {
		const expr = `(async () => {
			const img = new Image();
			img.src = ${ JSON.stringify( BASE + j.src ) } + '?v=' + Date.now();
			await img.decode();
			const w = ${ j.width } || img.naturalWidth;
			const h = Math.round( img.naturalHeight * w / img.naturalWidth );
			const c = document.createElement( 'canvas' );
			c.width = w;
			c.height = h;
			const g = c.getContext( '2d' );
			g.imageSmoothingQuality = 'high';
			g.drawImage( img, 0, 0, w, h );
			return { w, h, data: c.toDataURL( 'image/webp', ${ j.q } ) };
		})()`;
		const r = await send( 'Runtime.evaluate', { expression: expr, awaitPromise: true, returnByValue: true } );
		const v = r.result?.result?.value;
		if ( ! v || ! v.data.startsWith( 'data:image/webp' ) ) {
			throw new Error( `không xuất được ${ j.out }: ${ JSON.stringify( r.result?.exceptionDetails || r.result ) }` );
		}
		const file = join( OUT, j.out );
		writeFileSync( file, Buffer.from( v.data.split( ',' )[ 1 ], 'base64' ) );
		console.log( `${ j.out }: ${ v.w } × ${ v.h }, ${ ( statSync( file ).size / 1024 ).toFixed( 0 ) } KB` );
	}
	await send( 'Runtime.enable' );
	for ( const p of ! ONLY || ONLY === 'poster' ? POSTERS : [] ) {
		await send( 'Emulation.setDeviceMetricsOverride', { width: p.width, height: p.height, deviceScaleFactor: p.dpr, mobile: false } );
		await send( 'Page.navigate', { url: BASE + 'tools/lookdev.html?view=scene&p=0.5&gui=0&cards=0&reduced=1&off=grain,dust' } );
		const t0 = Date.now();
		let ready = false;
		while ( ! ready && Date.now() - t0 < 180000 ) {
			await sleep( 400 );
			const r = await send( 'Runtime.evaluate', { expression: '!!(window.__lookdev && window.__lookdev.ready) && JSON.stringify(window.__lookdev.errors)', returnByValue: true } );
			const v = r.result?.result?.value;
			if ( v && v !== '[]' ) {
				throw new Error( 'lookdev lỗi: ' + v );
			}
			ready = v === '[]';
		}
		if ( ! ready ) {
			throw new Error( 'lookdev không sẵn sàng' );
		}
		await send( 'Runtime.evaluate', { expression: "document.getElementById('label').style.display='none'" } );
		await sleep( 1500 );
		const shot = await send( 'Page.captureScreenshot', { format: 'webp', quality: p.q } );
		const file = join( OUT, p.out );
		writeFileSync( file, Buffer.from( shot.result.data, 'base64' ) );
		console.log( `${ p.out }: ${ p.width * p.dpr } × ${ p.height * p.dpr }, ${ ( statSync( file ).size / 1024 ).toFixed( 0 ) } KB` );
	}
	ws.close();
} finally {
	proc.kill();
}
