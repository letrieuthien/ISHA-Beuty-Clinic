#!/usr/bin/env node
/**
 * Quét code thừa của section "Bình minh lan tỏa" (docs/isha-lookdev-prompt-v3.md mục 1). Theme không có bước build nên không có
 * tree-shaking: file hay khoá còn sót vẫn nằm trong repo và gây nhiễu.
 *
 * Ba phép quét:
 *   1. File .js trong assets/js/services-cinematic/ không tới được từ entry. Entry = file .js mà PHP của theme trỏ tới
 *      (wp_enqueue_script_module ... 'services-cinematic/main.js'). Đi theo import tĩnh, import() động và export ... from. Chỉ code chạy thật
 *      mới tính: file chỉ được test hay tools/ import vẫn bị báo.
 *   2. Khoá VIẾT HOA trong config.js không được đọc ở file nào khác ngoài chính nó.
 *   3. (Thêm) class trong assets/css/services-cinematic.css không xuất hiện trong PHP / JS nào của theme (CSS mồ côi).
 *
 * Chạy từ gốc repo: node tools/deadcode.mjs. Mã thoát 1 khi có phát hiện.
 */
import { readFileSync, readdirSync, statSync, existsSync } from 'node:fs';
import { join, dirname, resolve, relative, sep } from 'node:path';
import { fileURLToPath } from 'node:url';

const ROOT = resolve( dirname( fileURLToPath( import.meta.url ) ), '..' );
const THEME = join( ROOT, 'wp-content', 'themes', 'ISHA' );
const SVC = join( THEME, 'assets', 'js', 'services-cinematic' );
const CSS = join( THEME, 'assets', 'css', 'services-cinematic.css' );
const rel = ( p ) => relative( ROOT, p ).split( sep ).join( '/' );

function walk( dir, exts, skip = [] ) {
	const out = [];
	for ( const name of readdirSync( dir ) ) {
		const p = join( dir, name );
		if ( skip.some( ( s ) => p.includes( s ) ) ) {
			continue;
		}
		const st = statSync( p );
		if ( st.isDirectory() ) {
			out.push( ...walk( p, exts, skip ) );
		} else if ( exts.some( ( e ) => name.endsWith( e ) ) ) {
			out.push( p );
		}
	}
	return out;
}

// Bỏ chú thích để import nằm trong comment không được tính.
function stripComments( src ) {
	return src.replace( /\/\*[\s\S]*?\*\//g, '' ).replace( /(^|[^:'"`\\])\/\/[^\n]*/g, '$1' );
}

function importsOf( file ) {
	const src = stripComments( readFileSync( file, 'utf8' ) );
	const specs = new Set();
	const re = /(?:import|export)\s*(?:[^'"`;]*?\sfrom\s*)?['"]([^'"]+)['"]|import\(\s*['"]([^'"]+)['"]\s*\)/g;
	let m;
	while ( ( m = re.exec( src ) ) ) {
		specs.add( m[ 1 ] || m[ 2 ] );
	}
	return [ ...specs ].filter( ( s ) => s.startsWith( '.' ) ).map( ( s ) => resolve( dirname( file ), s ) );
}

const problems = [];
const SKIP = [ `${ sep }vendor${ sep }`, `${ sep }node_modules${ sep }` ];
const phpFiles = walk( THEME, [ '.php' ], SKIP );
const svcFiles = walk( SVC, [ '.js' ] );

// 1. File mồ côi.
const entries = svcFiles.filter( ( f ) => {
	const needle = 'services-cinematic/' + relative( SVC, f ).split( sep ).join( '/' );
	return phpFiles.some( ( p ) => readFileSync( p, 'utf8' ).includes( needle ) );
} );
const reached = new Set();
const queue = [ ...entries ];
while ( queue.length ) {
	const f = queue.pop();
	if ( reached.has( f ) ) {
		continue;
	}
	reached.add( f );
	for ( const dep of importsOf( f ) ) {
		if ( ! existsSync( dep ) ) {
			problems.push( `[import hỏng] ${ rel( f ) } import ${ rel( dep ) } (không tồn tại)` );
			continue;
		}
		queue.push( dep );
	}
}
console.log( `1. File JS tới được từ entry (${ entries.map( rel ).join( ', ' ) || 'KHÔNG CÓ ENTRY' }): ${ reached.size }/${ svcFiles.length }` );
if ( ! entries.length ) {
	problems.push( '[entry] Không tìm thấy PHP nào nạp services-cinematic/*.js' );
}
for ( const f of svcFiles ) {
	if ( ! reached.has( f ) ) {
		problems.push( `[file mồ côi] ${ rel( f ) }` );
	}
}

// 2. Khoá VIẾT HOA của config.js.
const configFile = join( SVC, 'config.js' );
const configSrc = stripComments( readFileSync( configFile, 'utf8' ) );
const upperKeys = [ ...new Set( [ ...configSrc.matchAll( /^\s*([A-Z][A-Z0-9_]*)\s*:/gm ) ].map( ( m ) => m[ 1 ] ) ) ];
const others = [ ...svcFiles.filter( ( f ) => f !== configFile ), ...phpFiles ].map( ( f ) => stripComments( readFileSync( f, 'utf8' ) ) );
const unreadKeys = upperKeys.filter( ( k ) => ! others.some( ( src ) => new RegExp( `\\b${ k }\\b` ).test( src ) ) );
console.log( `2. Khoá viết hoa trong config.js: ${ upperKeys.join( ', ' ) || '(không có)' }` );
for ( const k of unreadKeys ) {
	problems.push( `[khoá config thừa] ${ k }` );
}

// 3. CSS mồ côi.
const cssSrc = readFileSync( CSS, 'utf8' ).replace( /\/\*[\s\S]*?\*\//g, '' );
const classes = [ ...new Set( [ ...cssSrc.matchAll( /\.(-?[a-z_][a-z0-9_-]*)/gi ) ].map( ( m ) => m[ 1 ] ) ) ].filter( ( c ) => ! /^\d/.test( c ) );
const markup = [ ...phpFiles, ...svcFiles ].map( ( f ) => readFileSync( f, 'utf8' ) ).join( '\n' );
const esc = ( s ) => s.replace( /[-.*+?^${}()|[\]\\]/g, '\\$&' );
const used = ( c ) => {
	if ( new RegExp( `(^|[^a-z0-9_-])${ esc( c ) }([^a-z0-9_-]|$)`, 'i' ).test( markup ) ) {
		return true;
	}
	// Modifier ghép động: isha-svc__ridge--<?php echo ... ?> trong PHP, `isha-x--${ i }` trong JS.
	const cut = c.lastIndexOf( '--' );
	return cut > 0 && new RegExp( `${ esc( c.slice( 0, cut + 2 ) ) }(<\\?php|\\$\\{)` ).test( markup );
};
const orphanCss = classes.filter( ( c ) => ! used( c ) );
console.log( `3. Class trong services-cinematic.css: ${ classes.length }` );
for ( const c of orphanCss ) {
	problems.push( `[CSS mồ côi] .${ c }` );
}

if ( problems.length ) {
	console.log( `\nCÓ ${ problems.length } PHÁT HIỆN:` );
	problems.forEach( ( p ) => console.log( '  - ' + p ) );
	process.exitCode = 1;
} else {
	console.log( '\nSạch: không file mồ côi, không khoá config thừa, không CSS mồ côi.' );
}
