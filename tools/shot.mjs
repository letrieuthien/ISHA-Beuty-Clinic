#!/usr/bin/env node
/**
 * Chụp tools/lookdev.html ra PNG trong tools/out/ (docs/isha-lookdev-prompt-v3.md mục 17.1). Chạy từ gốc repo:
 *
 *   node tools/shot.mjs [--views=depth] [--p=0,0.33,0.66,1] [--size=1920x1080] [--dpr=1] [--tag=v3] [--source=auto] [--url=...] [--gpu]
 *
 * Mỗi tổ hợp view × p ra một file <tag>-<view>-p<000>.png. Mặc định p = 0, 0.33, 0.66, 1 như đặc tả. Trang báo xong qua
 * window.__lookdev.ready; lỗi JS của trang được in ra.
 *
 * Trình duyệt: Playwright nếu có; không có thì tự điều khiển Chrome / Edge headless qua DevTools Protocol (Node 22+ có sẵn WebSocket). WebGL
 * mặc định chạy bằng SwiftShader (phần mềm, kết quả ổn định giữa các máy, chậm); --gpu thử dùng GPU thật. Không tìm thấy trình duyệt nào:
 * in hướng dẫn chụp thủ công.
 */
import { spawn } from 'node:child_process';
import { existsSync, mkdirSync, writeFileSync, rmSync } from 'node:fs';
import { join, dirname, resolve } from 'node:path';
import { fileURLToPath } from 'node:url';
import { tmpdir } from 'node:os';

const ROOT = resolve( dirname( fileURLToPath( import.meta.url ) ), '..' );
const OUT = join( ROOT, 'tools', 'out' );
const arg = ( name, def ) => {
	const a = process.argv.find( ( x ) => x.startsWith( `--${ name }=` ) );
	return a ? a.slice( name.length + 3 ) : def;
};
const views = arg( 'views', 'depth' ).split( ',' );
const ps = arg( 'p', '0,0.33,0.66,1' ).split( ',' ).map( Number );
const [ W, H ] = arg( 'size', '1920x1080' ).split( 'x' ).map( Number );
const dpr = Number( arg( 'dpr', '1' ) );
const tag = arg( 'tag', 'v3' );
const source = arg( 'source', 'auto' );
const base = arg( 'url', process.env.LOOKDEV_URL || 'http://localhost/ISHA-Beuty-Clinic/tools/lookdev.html' );
const gpu = process.argv.includes( '--gpu' );
const FLAGS = gpu
	? [ '--enable-gpu', '--ignore-gpu-blocklist' ]
	: [ '--use-angle=swiftshader', '--enable-unsafe-swiftshader', '--ignore-gpu-blocklist' ];
const jobs = [];
for ( const v of views ) {
	for ( const p of ps ) {
		const url = `${ base }?view=${ encodeURIComponent( v ) }&p=${ p }&source=${ encodeURIComponent( source ) }&gui=0&dpr=${ dpr }`;
		const file = join( OUT, `${ tag }-${ v }-p${ String( Math.round( p * 100 ) ).padStart( 3, '0' ) }.png` );
		jobs.push( { url, file } );
	}
}
mkdirSync( OUT, { recursive: true } );
const sleep = ( ms ) => new Promise( ( r ) => setTimeout( r, ms ) );

function manual() {
	console.log( `Không có Playwright và không tìm thấy Chrome / Edge. Chụp thủ công:
  1. Mở ${ base } (qua HTTP, không mở file trực tiếp).
  2. Với mỗi ảnh: thêm ?view=<${ views.join( '|' ) }>&p=<${ ps.join( '|' ) }>&gui=0 vào URL, cửa sổ ${ W } × ${ H }.
  3. Chờ hình hiện, chụp màn hình, lưu vào tools/out/ theo tên ${ tag }-<view>-p<000>.png (p × 100, 3 chữ số).
  Có thể chỉ định trình duyệt: biến môi trường BROWSER=<đường dẫn chrome/edge>.` );
}

async function withPlaywright() {
	let pw;
	try {
		pw = await import( 'playwright' );
	} catch ( err ) {
		return false;
	}
	const browser = await pw.chromium.launch( { args: FLAGS } );
	const page = await browser.newPage( { viewport: { width: W, height: H }, deviceScaleFactor: dpr } );
	const errors = [];
	page.on( 'pageerror', ( e ) => errors.push( String( e ) ) );
	page.on( 'console', ( m ) => m.type() === 'error' && errors.push( m.text() ) );
	for ( const j of jobs ) {
		await page.goto( j.url );
		await page.waitForFunction( () => window.__lookdev && window.__lookdev.ready, null, { timeout: 180000 } );
		await page.screenshot( { path: j.file } );
		console.log( 'đã chụp', j.file.slice( ROOT.length + 1 ) );
	}
	await browser.close();
	errors.forEach( ( e ) => console.log( 'lỗi trang:', e ) );
	return true;
}

function findBrowser() {
	const c = [
		process.env.BROWSER,
		'C:/Program Files/Google/Chrome/Application/chrome.exe',
		'C:/Program Files (x86)/Google/Chrome/Application/chrome.exe',
		'C:/Program Files (x86)/Microsoft/Edge/Application/msedge.exe',
		'C:/Program Files/Microsoft/Edge/Application/msedge.exe',
		'/Applications/Google Chrome.app/Contents/MacOS/Google Chrome',
		'/usr/bin/google-chrome',
		'/usr/bin/chromium',
		'/usr/bin/chromium-browser',
	];
	return c.find( ( p ) => p && existsSync( p ) );
}

async function withCdp() {
	const exe = findBrowser();
	if ( ! exe ) {
		return false;
	}
	const port = 9400 + Math.floor( Math.random() * 400 );
	const profile = join( tmpdir(), `isha-shot-${ port }` );
	const proc = spawn( exe, [ '--headless=new', `--remote-debugging-port=${ port }`, `--window-size=${ W },${ H }`, '--hide-scrollbars', '--no-first-run', `--user-data-dir=${ profile }`, ...FLAGS, 'about:blank' ], { stdio: 'ignore' } );
	try {
		let page;
		for ( let i = 0; i < 150 && ! page; i++ ) {
			try {
				const list = await ( await fetch( `http://127.0.0.1:${ port }/json` ) ).json();
				page = list.find( ( t ) => t.type === 'page' );
			} catch ( err ) {
				await sleep( 200 );
			}
		}
		if ( ! page ) {
			throw new Error( 'không kết nối được DevTools của ' + exe );
		}
		const ws = new WebSocket( page.webSocketDebuggerUrl );
		await new Promise( ( r, j ) => {
			ws.addEventListener( 'open', r );
			ws.addEventListener( 'error', j );
		} );
		let id = 0;
		const pending = new Map();
		const errors = [];
		ws.addEventListener( 'message', ( ev ) => {
			const m = JSON.parse( ev.data );
			if ( m.id && pending.has( m.id ) ) {
				pending.get( m.id )( m );
				pending.delete( m.id );
			} else if ( m.method === 'Runtime.exceptionThrown' ) {
				errors.push( m.params.exceptionDetails.exception?.description || m.params.exceptionDetails.text );
			} else if ( m.method === 'Runtime.consoleAPICalled' && m.params.type === 'error' ) {
				errors.push( m.params.args.map( ( a ) => a.value ?? a.description ).join( ' ' ) );
			}
		} );
		const send = ( method, params = {} ) => new Promise( ( r ) => {
			const i = ++id;
			pending.set( i, r );
			ws.send( JSON.stringify( { id: i, method, params } ) );
		} );
		await send( 'Runtime.enable' );
		await send( 'Page.enable' );
		await send( 'Emulation.setDeviceMetricsOverride', { width: W, height: H, deviceScaleFactor: dpr, mobile: false } );
		for ( const j of jobs ) {
			const errBefore = errors.length;
			await send( 'Page.navigate', { url: j.url } );
			const t0 = Date.now();
			let ready = false;
			// Trang ném exception (lỗi module, lỗi shader): dừng chờ ngay, in lỗi và bỏ cả lượt (các ảnh sau cũng sẽ lỗi như vậy).
			while ( ! ready && errors.length === errBefore && Date.now() - t0 < 180000 ) {
				await sleep( 300 );
				const r = await send( 'Runtime.evaluate', { expression: '!!(window.__lookdev && window.__lookdev.ready)', returnByValue: true } );
				ready = r.result?.result?.value === true;
			}
			if ( ! ready ) {
				console.log( errors.length > errBefore ? 'trang lỗi, dừng:' : 'hết giờ chờ', j.url );
				break;
			}
			const pageErr = await send( 'Runtime.evaluate', { expression: 'JSON.stringify(window.__lookdev.errors)', returnByValue: true } );
			JSON.parse( pageErr.result?.result?.value || '[]' ).forEach( ( e ) => errors.push( e ) );
			const shot = await send( 'Page.captureScreenshot', { format: 'png' } );
			writeFileSync( j.file, Buffer.from( shot.result.data, 'base64' ) );
			console.log( `đã chụp ${ j.file.slice( ROOT.length + 1 ) } (${ ( ( Date.now() - t0 ) / 1000 ).toFixed( 1 ) } s)` );
		}
		[ ...new Set( errors ) ].forEach( ( e ) => console.log( 'lỗi trang:', e ) );
		ws.close();
	} finally {
		proc.kill();
		await sleep( 500 );
		try {
			rmSync( profile, { recursive: true, force: true } );
		} catch ( err ) {
			// hồ sơ tạm có thể còn bị khoá vài giây sau khi đóng trình duyệt
		}
	}
	return true;
}

if ( ! ( await withPlaywright() ) && ! ( await withCdp() ) ) {
	manual();
}
