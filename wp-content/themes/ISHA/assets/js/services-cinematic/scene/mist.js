/**
 * Sương của cảnh v3 (docs/isha-lookdev-prompt-v3.md mục 11). 4 dải desktop, 2 điện thoại (tầng chất lượng chọn theo CONFIG.mist.priority).
 *
 * Mỗi dải là một quad rộng trên mặt phẳng z = −khoảng cách, đặt theo HÀNG ẢNH (thung lũng nằm ở đâu trong ảnh, qua luật đóng khung của
 * plate.js) nên đúng chỗ trên mọi tỉ lệ khung và có parallax như núi ở cùng độ sâu. Che khuất mềm bởi lưới ảnh (OCCLUSION_GLSL của plate.js,
 * không dùng depth test cứng: nó cắt dải sương theo đường đồng độ sâu thành mảng mép sắc giữa sườn núi): rặng gần hơn tự che dải sương,
 * nên sương xen giữa các lớp núi mà không cần mặt nạ. Fragment: fbm 2D cuộn ngang (hai lớp ngược chiều), mép trên / dưới mềm và gợn theo
 * vân, hai đầu mờ dần. Trôi 0.004 – 0.012 đơn vị / giây, chậm đến mức gần như không thấy, và trôi cả khi người dùng không cuộn. Alpha
 * tổng theo keyframe fogDensity; gần mặt trời sương sáng và ấm lên (tán xạ thuận, sunLit). Màu: fog (token) → sunCore.
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
varying vec2 vB;
varying vec2 vNdc;
varying vec3 vWorld;
vec3 toLinear( vec3 c ) {
	return mix( c / 12.92, pow( ( max( c, 0.0 ) + 0.055 ) / 1.055, vec3( 2.4 ) ), step( 0.04045, c ) );
}
float hash( vec2 p ) {
	return fract( sin( dot( p, vec2( 127.1, 311.7 ) ) ) * 43758.5453 );
}
float vnoise( vec2 p ) {
	vec2 i = floor( p );
	vec2 f = fract( p );
	vec2 u = f * f * ( 3.0 - 2.0 * f );
	return mix( mix( hash( i ), hash( i + vec2( 1.0, 0.0 ) ), u.x ), mix( hash( i + vec2( 0.0, 1.0 ) ), hash( i + vec2( 1.0, 1.0 ) ), u.x ), u.y );
}
float fbm( vec2 p ) {
	float s = 0.0;
	float a = 0.5;
	for ( int i = 0; i < 5; i++ ) {
		s += a * vnoise( p );
		p = p * 2.02 + vec2( 11.3, 7.9 );
		a *= 0.5;
	}
	return s;
}
void main() {
	vec2 q = vec2( vB.x * 7.0, vB.y * 1.7 ) * uScale;
	float drift = uTime * uSpeed * 7.0 * uScale;
	float n = fbm( q + vec2( drift + uSeed, uSeed * 0.37 ) );
	float n2 = fbm( q * 2.1 + vec2( -drift * 1.3, uSeed * 1.9 ) );
	float dens = smoothstep( 0.28, 0.72, n * 0.7 + n2 * 0.3 );
	float yy = ( vB.y - 0.5 ) + ( n - 0.5 ) * 0.28;
	float vert = exp( -yy * yy / 0.04 );
	float horiz = smoothstep( 0.0, 0.06, vB.x ) * ( 1.0 - smoothstep( 0.94, 1.0, vB.x ) );
	float a = uAlpha * vert * horiz * mix( 0.35, 1.0, dens ) * plateVisible( vWorld, uSoft );
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
				const halfNdcY = ( h * info.fitScale ) / 2 * 1.5;
				b.u.uCenter.value.set( 0, yC * info.refTanY * z, -z );
				b.u.uHalf.value.set( 1.18 * info.refTanX * z, halfNdcY * info.refTanY * z );
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
