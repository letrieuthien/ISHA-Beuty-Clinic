/**
 * Mây hồng 2.5D: các billboard lớn (desktop 8 đến 12, lite 4) quay mặt về camera theo trục đứng, fragment shader fbm 2D + domain warp, mép
 * mềm, đáy hơi tối, viền sáng (silver lining) khi gần hướng mặt trời. uGain (CONFIG.cloudGain) đưa mây lên ngang độ sáng của trời
 * Preetham (bức xạ trời lớn hơn 1 nhiều; không có hệ số này mây sẽ xám đục trên nền trời). Màu = trộn trắng ấm (Daylight/White) với hồng phái sinh từ Skin
 * (tokens.js, độ bão hoà = vàng x kPink) theo keyframe cloudPinkMix (rạng đông hồng hơn). Trôi rất chậm theo phương ngang.
 *
 * Bố cục: mặt trời là chủ thể nên KHÔNG đặt mây trên đường đi của mặt trời (cột giữa, độ cao thấp hơn khoảng 19°). Mây nằm hai bên và cao hơn;
 * bản lite (điện thoại, khung dọc hẹp) dùng các ô gần giữa hơn để vẫn thấy. Mây ở xa hơn núi nên núi che chân mây; mờ dần vào sương ở gần
 * chân trời. Một draw call (InstancedBufferGeometry).
 */
import { SHARED_GLSL } from './shading.js';

// [lệch phương vị (°) so với hướng nhìn, độ cao (°), độ rộng góc (°), tỉ lệ cao/rộng]. Thứ tự: ô ưu tiên trước (lite lấy n ô đầu).
const SLOTS = [
	[ -13, 15, 14, 0.36 ], [ 14, 17, 15, 0.34 ], [ -6, 23, 10, 0.32 ], [ 8, 25, 11, 0.3 ],
	[ -27, 11, 18, 0.38 ], [ 29, 12, 18, 0.36 ], [ -40, 8, 20, 0.4 ], [ 42, 9, 20, 0.38 ],
	[ -20, 27, 12, 0.3 ], [ 22, 29, 13, 0.3 ], [ -52, 14, 16, 0.36 ], [ 54, 16, 16, 0.34 ],
];
const DIST = 4800;

// elOffsetDeg: cộng vào độ cao của mọi ô (camera ngẩng hơn mốc 2.5° bao nhiêu thì nâng bấy nhiêu) để mây giữ chỗ trên khung hình.
export function buildClouds( THREE, { count, origin, colors, shared, seed, elOffsetDeg = 0 } ) {
	const n = Math.min( count, SLOTS.length );
	const quad = new THREE.PlaneGeometry( 1, 1 );
	const geo = new THREE.InstancedBufferGeometry();
	geo.index = quad.index;
	geo.attributes.position = quad.attributes.position;
	geo.attributes.uv = quad.attributes.uv;
	geo.instanceCount = n;

	const centers = new Float32Array( n * 3 );
	const sizes = new Float32Array( n * 2 );
	const seeds = new Float32Array( n );
	for ( let i = 0; i < n; i++ ) {
		const [ az, el, wDeg, ratio ] = SLOTS[ i ];
		const a = THREE.MathUtils.degToRad( az );
		const e = THREE.MathUtils.degToRad( el + elOffsetDeg );
		centers[ i * 3 ] = origin.x + Math.sin( a ) * Math.cos( e ) * DIST;
		centers[ i * 3 + 1 ] = origin.y + Math.sin( e ) * DIST;
		centers[ i * 3 + 2 ] = origin.z - Math.cos( a ) * Math.cos( e ) * DIST;
		const w = 2 * DIST * Math.tan( THREE.MathUtils.degToRad( wDeg ) / 2 );
		sizes[ i * 2 ] = w;
		sizes[ i * 2 + 1 ] = w * ratio;
		seeds[ i ] = ( ( seed * 0.0001 + i * 0.6180339 ) % 1 ) * 100;
	}
	geo.setAttribute( 'aCenter', new THREE.InstancedBufferAttribute( centers, 3 ) );
	geo.setAttribute( 'aSize', new THREE.InstancedBufferAttribute( sizes, 2 ) );
	geo.setAttribute( 'aSeed', new THREE.InstancedBufferAttribute( seeds, 1 ) );

	const uniforms = {
		...shared,
		uWarm: { value: colors.daylight.clone().lerp( colors.white, 0.4 ) },
		uPink: { value: colors.pink.clone() },
		uPinkMix: { value: 0.6 },
		uOpacity: { value: 0.9 },
		uGain: { value: 1.8 },
	};

	const material = new THREE.ShaderMaterial( {
		name: 'IshaClouds',
		uniforms,
		transparent: true,
		depthWrite: false,
		vertexShader: /* glsl */ `
			attribute vec3 aCenter;
			attribute vec2 aSize;
			attribute float aSeed;
			varying vec2 vUv;
			varying float vSeed;
			varying vec3 vWorld;
			uniform float uTime;
			void main() {
				// Quay mặt về camera theo trục đứng (giữ mây nằm ngang).
				vec3 right = normalize( vec3( viewMatrix[ 0 ][ 0 ], 0.0, viewMatrix[ 2 ][ 0 ] ) );
				vec3 c = aCenter + vec3( uTime * 1.2, 0.0, 0.0 );
				vec3 world = c + right * position.x * aSize.x + vec3( 0.0, 1.0, 0.0 ) * position.y * aSize.y;
				vUv = uv;
				vSeed = aSeed;
				vWorld = world;
				gl_Position = projectionMatrix * viewMatrix * vec4( world, 1.0 );
			}
		`,
		fragmentShader: SHARED_GLSL + /* glsl */ `
			uniform vec3 uWarm;
			uniform vec3 uPink;
			uniform float uPinkMix;
			uniform float uOpacity;
			uniform float uGain;
			varying vec2 vUv;
			varying float vSeed;
			varying vec3 vWorld;

			void main() {
				vec2 q = vUv * vec2( 2.6, 1.0 ) + vec2( vSeed * 3.17, vSeed * 1.31 ) + vec2( uTime * 0.0025, 0.0 );
				float warp = ishaFbm( q * 1.4 );
				float nn = ishaFbm( q * 2.2 + vec2( warp * 1.6, warp * 0.8 ) );
				vec2 c = ( vUv - 0.5 ) * 2.0;
				float r = length( c * vec2( 1.0, 1.55 ) );
				float shape = smoothstep( 1.0, 0.2, r );
				float density = smoothstep( 0.4, 0.78, nn * 0.85 + shape * 0.55 - 0.18 ) * shape;
				if ( density < 0.01 ) discard;

				vec3 col = mix( uWarm, uPink, uPinkMix ) * uGain;
				col *= mix( 0.86, 1.04, vUv.y ) * mix( 0.94, 1.04, density ); // đáy mây và mép hơi tối, lõi sáng
				vec3 V = normalize( vWorld - cameraPosition );
				float toward = pow( max( dot( V, uSunDir ), 0.0 ), 8.0 );
				float rim = ( 1.0 - density ) * ( 1.0 - density );
				col += uSunColor * rim * toward * 1.1;                  // viền bạc phía mặt trời
				col = mix( col, uFogColor * uGain, 0.08 + 0.35 * ( 1.0 - smoothstep( 0.0, 0.12, V.y ) ) ); // tan vào sương gần chân trời

				gl_FragColor = vec4( col, density * uOpacity );
				#include <tonemapping_fragment>
				#include <colorspace_fragment>
			}
		`,
	} );

	const mesh = new THREE.Mesh( geo, material );
	mesh.name = 'clouds';
	mesh.frustumCulled = false;
	mesh.renderOrder = 1;

	return {
		mesh,
		// Governor: bớt mây bằng instanceCount (ô ưu tiên đứng trước nên giữ được bố cục), không dựng lại.
		setCount( c ) {
			geo.instanceCount = Math.max( 0, Math.min( n, c ) );
			mesh.visible = geo.instanceCount > 0;
		},
		uniforms,
		setColors( c ) {
			uniforms.uWarm.value.copy( c.daylight ).lerp( c.white, 0.4 );
			uniforms.uPink.value.copy( c.pink );
		},
		dispose() {
			quad.dispose();
			geo.dispose();
			material.dispose();
		},
	};
}
