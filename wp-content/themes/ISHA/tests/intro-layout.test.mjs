// Bố cục màn chờ (assets/js/services-cinematic/intro-layout.js). Chạy: node --test "tests/*.test.mjs" (thư mục theme).
import { test } from 'node:test';
import assert from 'node:assert/strict';
import { introLayout, applyIntroLayout, ridgeTop, CANVAS_IMAGE, RIDGE_ART } from '../assets/js/services-cinematic/intro-layout.js';
import { CONFIG } from '../assets/js/services-cinematic/config.js';

const OPT = { framing: CONFIG.framing, horizonUV: CONFIG.plate.horizonUV, path: CONFIG.stops.path, sunClear: CONFIG.intro.sunClear };
const near = ( a, b, eps = 0.01 ) => assert.ok( Math.abs( a - b ) <= eps, `${ a } ≉ ${ b }` );

test( 'ridgeTop: nội suy tuyến tính, kẹp ngoài [0, 1]', () => {
	const t = RIDGE_ART.top;
	near( ridgeTop( 0 ), t[ 0 ], 1e-9 );
	near( ridgeTop( 1 ), t[ t.length - 1 ], 1e-9 );
	near( ridgeTop( -3 ), t[ 0 ], 1e-9 );
	near( ridgeTop( 9 ), t[ t.length - 1 ], 1e-9 );
	near( ridgeTop( 1 / 64 ), ( t[ 0 ] + t[ 1 ] ) / 2, 1e-9 );
} );

test( '1440 × 900: đỉnh trái của canvas theo công thức đóng khung (chân trời 47%), đỉnh đầu tiên của hình núi trùng nó', () => {
	const L = introLayout( 1440, 900, OPT );
	// Canvas: ảnh vừa chiều rộng, cao 1440 / 1.7737; chân trời (horizonUV 0.2) ở 47% khung.
	const imgH = 1440 / CANVAS_IMAGE.aspect;
	near( L.peakX, CANVAS_IMAGE.peakU * 1440, 1e-6 );
	near( L.peakY, 0.47 * 900 - 0.2 * imgH + CANVAS_IMAGE.peakV * imgH, 1e-6 );
	// Đỉnh đầu tiên của hình núi nằm đúng điểm đó.
	near( L.x + RIDGE_ART.peakX * L.artW, L.peakX );
	near( L.y + RIDGE_ART.peakY * L.artH, L.peakY );
} );

test( 'khung ảnh núi luôn phủ kín stage (đáy, trái, phải) và không nhỏ hơn chiều rộng khung', () => {
	for ( const [ w, h ] of [ [ 1440, 900 ], [ 1920, 1080 ], [ 1366, 768 ], [ 1536, 730 ], [ 2560, 1100 ], [ 1024, 768 ], [ 820, 1180 ], [ 390, 844 ], [ 360, 640 ], [ 1280, 400 ] ] ) {
		const L = introLayout( w, h, OPT );
		assert.ok( L.artW >= w - 1e-6, `${ w }×${ h }: artW ${ L.artW } < W` );
		assert.ok( L.x <= 1e-6, `${ w }×${ h }: lộ mép trái (x = ${ L.x })` );
		assert.ok( L.x + L.artW >= w - 1e-6, `${ w }×${ h }: lộ mép phải` );
		assert.ok( L.y + L.artH >= h - 1e-6, `${ w }×${ h }: lộ đáy (đáy ảnh ${ L.y + L.artH } < ${ h })` );
		near( L.artW / L.artH, RIDGE_ART.aspect, 1e-9 );
		// Vẫn khớp đỉnh trái canvas.
		near( L.x + RIDGE_ART.peakX * L.artW, L.peakX );
		near( L.y + RIDGE_ART.peakY * L.artH, L.peakY );
	}
} );

test( 'đỉnh núi không trôi theo chiều cao cửa sổ như neo đáy: độ cao đỉnh bám công thức của canvas', () => {
	// Cửa sổ thấp (1536 × 730) và cao (1536 × 960): đỉnh dịch theo chân trời 47% của canvas, không theo đáy.
	const a = introLayout( 1536, 730, OPT );
	const b = introLayout( 1536, 960, OPT );
	near( b.peakY - a.peakY, 0.47 * ( 960 - 730 ), 1e-6 );
	near( b.y + RIDGE_ART.peakY * b.artH - ( a.y + RIDGE_ART.peakY * a.artH ), 0.47 * ( 960 - 730 ), 1e-6 );
} );

test( 'mặt trời ở bên phải: x = điểm đầu mặt trời của canvas (desktop 57.5%, khung dọc 56%)', () => {
	near( introLayout( 1440, 900, OPT ).sunX, 0.575 * 1440, 1e-6 );
	near( introLayout( 1920, 1080, OPT ).sunX, 0.575 * 1920, 1e-6 );
	near( introLayout( 390, 844, OPT ).sunX, 0.56 * 390, 1e-6 );
	assert.ok( introLayout( 1440, 900, OPT ).sunX > 1440 / 2, 'bên phải tâm khung' );
} );

test( 'mặt trời mọc xong thì nằm trên đường núi (đáy đĩa cách sunClear), xuất phát thì chìm hẳn sau núi', () => {
	for ( const [ w, h ] of [ [ 1440, 900 ], [ 1920, 1080 ], [ 1366, 768 ], [ 1536, 730 ], [ 1024, 768 ], [ 390, 844 ] ] ) {
		const L = introLayout( w, h, OPT );
		const top = ( px ) => L.y + ridgeTop( ( px - L.x ) / L.artW ) * L.artH;
		const clear = OPT.sunClear * L.artH;
		near( top( L.sunX ) - ( L.sunY + L.r ), clear, 1e-6 );
		// Xuất phát: đỉnh đĩa dưới mọi điểm đường núi trong bề ngang đĩa.
		const startTop = L.sunY + L.rise - L.r;
		for ( let k = -8; k <= 8; k++ ) {
			assert.ok( startTop >= top( L.sunX + ( k / 8 ) * L.r ) + 5, `${ w }×${ h }: đĩa còn lộ ở ${ k }/8` );
		}
		assert.ok( L.rise > 0 );
		// Mặt trời mọc xong nằm trong khung nhìn.
		assert.ok( L.sunY - L.r > 0, `${ w }×${ h }: đĩa tràn mép trên` );
	}
} );

test( 'tỉ lệ trung gian (1024 × 768): chân trời nội suy smoothstep giữa dọc và ngang như canvas', () => {
	const va = 1024 / 768;
	const t = ( va - 0.7 ) / 0.7;
	const s = t * t * ( 3 - 2 * t );
	const hY = 0.57 + ( 0.47 - 0.57 ) * s;
	const imgH = 1024 / CANVAS_IMAGE.aspect;
	near( introLayout( 1024, 768, OPT ).peakY, hY * 768 - 0.2 * imgH + CANVAS_IMAGE.peakV * imgH, 1e-6 );
} );

test( 'khung dọc: dùng chân trời 57% như canvas', () => {
	const imgH = 390 / CANVAS_IMAGE.aspect;
	near( introLayout( 390, 844, OPT ).peakY, 0.57 * 844 - 0.2 * imgH + CANVAS_IMAGE.peakV * imgH, 1e-6 );
} );

test( 'applyIntroLayout: ghi đủ biến CSS px', () => {
	const props = {};
	const el = { style: { setProperty: ( k, v ) => ( props[ k ] = v ) } };
	applyIntroLayout( el, introLayout( 1440, 900, OPT ) );
	for ( const k of [ '--ia-w', '--ia-h', '--ia-x', '--ia-y', '--is-x', '--is-y', '--isha-intro-r', '--ia-rise' ] ) {
		assert.match( props[ k ], /^-?\d+(\.\d+)?px$/, k );
	}
} );
