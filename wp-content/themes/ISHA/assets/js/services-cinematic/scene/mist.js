/**
 * Sương của cảnh v3 (docs/isha-lookdev-prompt-v3.md mục 11). 4 dải desktop, 2 điện thoại (tầng chất lượng chọn theo CONFIG.mist.priority).
 *
 * Mỗi dải là một quad rộng trên mặt phẳng z = −khoảng cách, đặt theo HÀNG ẢNH (thung lũng nằm ở đâu trong ảnh, qua luật đóng khung của
 * plate.js) nên đúng chỗ trên mọi tỉ lệ khung và có parallax như núi ở cùng độ sâu. Che khuất mềm bởi lưới ảnh (OCCLUSION_GLSL của plate.js,
 * không dùng depth test cứng: nó cắt dải sương theo đường đồng độ sâu thành mảng mép sắc giữa sườn núi): rặng gần hơn tự che dải sương,
 * nên sương xen giữa các lớp núi mà không cần mặt nạ. Trôi 0.004 – 0.012 đơn vị / giây, chậm đến mức gần như không thấy, và trôi cả khi
 * người dùng không cuộn. Alpha tổng theo keyframe fogDensity; gần mặt trời sương sáng và ấm lên (tán xạ thuận, sunLit). Màu: fog → sunCore.
 *
 * KHÔNG ĐƯỢC CÓ CẠNH THẲNG (khách thấy "khối chữ nhật trôi ngang qua chân núi", sửa ba lần mới tận gốc):
 *   - Nhiễu là gradient noise, nội suy bậc 5, miền toạ độ xoay ở mỗi octave, tính trong toạ độ ĐẲNG HƯỚNG (đơn vị chiều cao khung). Bản cũ
 *     dùng value noise trên lưới ô vuông, kéo giãn 7 : 1.7: vân có nếp gấp chạy dọc đường lưới (ngang và dọc), tăng tương phản thành các
 *     khối chữ nhật trôi theo sương.
 *   - Quad cao gấp 3 lần dải lõi: mặt cắt dọc (Gauss) về ~0 rất lâu trước mép quad; cửa sổ mép chỉ là bảo hiểm. Đỉnh / đáy sương uốn lượn
 *     theo nhiễu tần số thấp (lệch tâm tới ±0.8 nửa dải, độ dày đổi theo vị trí) thay cho một vệt ngang có biên gần thẳng.
 */
import { SRGB_GLSL } from './depth.js';
import { OCCLUSION_GLSL } from './plate.js';

const VS = /* glsl */ `
uniform vec3 uCenter;
uniform vec2 uHalf;
varying vec2 vB;
varying vec2 vNdc;
varying vec3 vWorld;
void main() {
	vB = position.xy * 0.5 + 0.5;
	vWorld = uCenter + vec3( position.xy * uHalf, 0.0 );
	vec4 c = projectionMatrix * viewMatrix * vec4( vWorld, 1.0 );
	vNdc = c.xy / c.w;
	gl_Position = c;
}
`;

const FS = /* glsl */ `
${ SRGB_GLSL }
${ OCCLUSION_GLSL }
uniform float uAlpha;
uniform float uSoft;
uniform float uTime;
uniform float uSpeed;
uniform float uSeed;
uniform float uScale;
uniform float uSunLit;
uniform float uAspect;
uniform vec3 uFog;
uniform vec3 uLit;
uniform vec2 uSunNdc;
uniform vec2 uBandSize; // kích thước quad theo chiều cao khung (rộng, cao) để nhiễu đẳng hướng
varying vec2 vB;
varying vec2 vNdc;
varying vec3 vWorld;
vec3 toLinear( vec3 c ) {
	return mix( c / 12.92, pow( ( max( c, 0.0 ) + 0.055 ) / 1.055, vec3( 2.4 ) ), step( 0.04045, c ) );
}
// Băm không dùng sin (ổn định trên mọi GPU).
float hash( vec2 p ) {
	vec3 p3 = fract( vec3( p.xyx ) * 0.1031 );
	p3 += dot( p3, p3.yzx + 33.33 );
	return fract( ( p3.x + p3.y ) * p3.z );
}
// Gradient noise, nội suy bậc 5 (liên tục bậc 2: không có nếp gấp dọc đường lưới như value noise). Trả về khoảng 0 … 1.
float gnoise( vec2 p ) {
	vec2 i = floor( p );
	vec2 f = fract( p );
	vec2 u = f * f * f * ( f * ( f * 6.0 - 15.0 ) + 10.0 );
	float h00 = hash( i ) * 6.2831853;
	float h10 = hash( i + vec2( 1.0, 0.0 ) ) * 6.2831853;
	float h01 = hash( i + vec2( 0.0, 1.0 ) ) * 6.2831853;
	float h11 = hash( i + vec2( 1.0, 1.0 ) ) * 6.2831853;
	float a = dot( vec2( cos( h00 ), sin( h00 ) ), f );
	float b = dot( vec2( cos( h10 ), sin( h10 ) ), f - vec2( 1.0, 0.0 ) );
	float c = dot( vec2( cos( h01 ), sin( h01 ) ), f - vec2( 0.0, 1.0 ) );
	float d = dot( vec2( cos( h11 ), sin( h11 ) ), f - vec2( 1.0, 1.0 ) );
	return 0.5 + 0.7 * mix( mix( a, b, u.x ), mix( c, d, u.x ), u.y );
}
// Mỗi octave xoay miền khoảng 37° (không có hướng trục nào được ưu tiên) và lệch gốc.
const mat2 ROT = mat2( 0.8, 0.6, -0.6, 0.8 );
float fbm( vec2 p ) {
	float s = 0.0;
	float a = 0.5;
	for ( int i = 0; i < 5; i++ ) {
		s += a * gnoise( p );
		p = ROT * p * 2.03 + vec2( 1.7, 9.2 );
		a *= 0.5;
	}
	return s;
}
void main() {
	// Toạ độ đẳng hướng (đơn vị chiều cao khung); sương dẹt theo chiều ngang nhờ giãn trục x một lần ở đầu vào (không phải lưới bị kéo).
	vec2 pos = vB * uBandSize;
	float drift = uTime * uSpeed * uScale;
	vec2 q = vec2( pos.x * 0.45, pos.y ) * 5.0 * uScale;
	float n = fbm( q + vec2( drift * 5.0 + uSeed, uSeed * 0.37 ) );
	float n2 = fbm( q * 1.9 + vec2( -drift * 6.5, uSeed * 1.9 ) );
	float dens = smoothstep( 0.3, 0.75, n * 0.65 + n2 * 0.35 );
	// Dải lõi: by = ±1 là nửa dải gốc (quad cao gấp 3: by trong −3 … 3). Tâm uốn theo nhiễu tần số thấp, độ dày đổi theo vị trí.
	float by = ( vB.y - 0.5 ) * 6.0;
	float wav = fbm( vec2( pos.x * 1.1 + drift * 2.0, uSeed * 2.3 ) );
	float thick = 0.55 + 0.45 * fbm( vec2( pos.x * 0.8 - drift, uSeed * 3.1 + 4.0 ) );
	float yy = ( by - ( wav - 0.5 ) * 1.6 + ( n - 0.5 ) * 0.6 ) / thick;
	float vert = exp( -yy * yy * 0.5 );
	float horiz = smoothstep( 0.0, 0.06, vB.x ) * ( 1.0 - smoothstep( 0.94, 1.0, vB.x ) );
	// Bảo hiểm: về 0 tuyệt đối ở mép quad (ở đó Gauss đã ~0).
	float edgeY = smoothstep( 0.0, 0.12, vB.y ) * smoothstep( 0.0, 0.12, 1.0 - vB.y );
	float a = uAlpha * vert * horiz * edgeY * mix( 0.25, 1.0, dens ) * plateVisible( vWorld, uSoft );
	vec2 dv = ( vNdc - uSunNdc ) * vec2( uAspect, 1.0 ) * 0.5;
	float lit = exp( -length( dv ) / 0.32 ) * uSunLit;
	vec3 col = mix( uFog, uLit, clamp( lit, 0.0, 1.0 ) );
	gl_FragColor = vec4( toLinear( col ) * ( 1.0 + 0.35 * lit ), clamp( a, 0.0, 1.0 ) );
	#include <tonemapping_fragment>
	#include <colorspace_fragment>
}
`;

/**
 * @param {object} THREE
 * @param {{palette:object, cfg:object, occlusion:object, count:number, sunZ:number}} opts cfg: CONFIG.mist (đọc mỗi khung); occlusion:
 *   plate.occlusionUniforms; count: số dải (tầng chất lượng); sunZ: khoảng cách mặt trời, để dải xa hơn vẽ trước mặt trời, dải gần vẽ sau tia.
 */
export function createMist( THREE, { palette, cfg, occlusion, count = 4, sunZ = 1050 } ) {
	const geo = new THREE.PlaneGeometry( 2, 2 );
	const group = new THREE.Group();
	group.name = 'mist';
	const bands = [];
	const sunNdc = new THREE.Vector2();
	const shared = {
		uTime: { value: 0 },
		uScale: { value: cfg.scale },
		uSunLit: { value: cfg.sunLit },
		uAspect: { value: 1 },
		uFog: { value: palette.fog.clone() },
		uLit: { value: palette.sunCore.clone() },
		uSunNdc: { value: sunNdc },
		uSoft: { value: cfg.soft },
		...occlusion,
	};
	const QUAD_SCALE = 3; // quad cao gấp 3 dải lõi

	function build( n ) {
		for ( const b of bands ) {
			group.remove( b.mesh );
			b.mesh.material.dispose();
		}
		bands.length = 0;
		cfg.priority.slice( 0, Math.max( 0, n ) ).forEach( ( idx ) => {
			const u = {
				...shared,
				uCenter: { value: new THREE.Vector3() },
				uHalf: { value: new THREE.Vector2( 1, 1 ) },
				uAlpha: { value: 0 },
				uSpeed: { value: 0.005 },
				uSeed: { value: idx * 13.7 + 3.1 },
				uBandSize: { value: new THREE.Vector2( 1, 1 ) },
			};
			const mat = new THREE.ShaderMaterial( { uniforms: u, vertexShader: VS, fragmentShader: FS, transparent: true, depthWrite: false, depthTest: false } );
			mat.name = `mist-${ idx }`;
			const mesh = new THREE.Mesh( geo, mat );
			mesh.frustumCulled = false;
			const z = cfg.bands[ idx ][ 0 ];
			// Xa hơn mặt trời: vẽ trước đĩa (renderOrder 4); gần hơn: sau tia (6). Trong mỗi nhóm, xa vẽ trước.
			mesh.renderOrder = ( z > sunZ ? 3 : 7 ) + ( 2000 - z ) / 10000;
			group.add( mesh );
			bands.push( { idx, mesh, u } );
		} );
	}
	build( count );

	let lastLayout = null;
	const api = {
		group,
		/**
		 * Đặt lại các dải theo luật đóng khung hiện tại của lưới ảnh (plate.layout()). Gọi khi đổi kích thước.
		 * @param {object} info plate.layout
		 */
		layout( info ) {
			lastLayout = info;
			for ( const b of bands ) {
				const [ z, row, h ] = cfg.bands[ b.idx ];
				const yC = info.ndcYOfRow( row );
				const halfNdcY = ( ( h * info.fitScale ) / 2 ) * QUAD_SCALE;
				const aspect = info.refTanX / info.refTanY;
				b.u.uCenter.value.set( 0, yC * info.refTanY * z, -z );
				b.u.uHalf.value.set( 1.18 * info.refTanX * z, halfNdcY * info.refTanY * z );
				// Quad theo chiều cao khung: rộng 2.36 NDC = 1.18 · tỉ lệ khung; cao 2 · halfNdcY NDC = halfNdcY.
				b.u.uBandSize.value.set( 1.18 * aspect, halfNdcY );
			}
		},
		setCount( n ) {
			build( n );
			if ( lastLayout ) {
				api.layout( lastLayout );
			}
		},
		/** @param {{time:number, sunNdc:{x:number,y:number}, aspect:number, fogDensity:number}} f */
		update( f ) {
			shared.uTime.value = f.time;
			shared.uScale.value = cfg.scale;
			shared.uSunLit.value = cfg.sunLit;
			shared.uSoft.value = cfg.soft;
			shared.uAspect.value = f.aspect;
			sunNdc.set( f.sunNdc.x, f.sunNdc.y );
			for ( const b of bands ) {
				const band = cfg.bands[ b.idx ];
				b.u.uAlpha.value = band[ 3 ] * f.fogDensity;
				b.u.uSpeed.value = band[ 4 ];
			}
		},
		setColors( pal ) {
			shared.uFog.value.copy( pal.fog );
			shared.uLit.value.copy( pal.sunCore );
		},
		dispose() {
			build( 0 );
			geo.dispose();
		},
	};
	return api;
}
