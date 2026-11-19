/**
 * Bụi sáng vàng (Golden Dust) quanh ảnh chân dung: thay hiệu ứng "bầu trời sao" chấm trắng sắc nét (19/11/2026). Một THREE.Points cộng
 * sáng (additive); chuyển động tính hết trong vertex shader theo thời gian nên CPU không cập nhật từng hạt:
 *   - ít hạt, mỗi hạt là một đốm bokeh mép mềm (không có lõi sắc), cỡ đa dạng, opacity riêng 0,15–0,45;
 *   - màu ánh kim #fcf6ba pha vàng đồng #8e6837 theo từng hạt;
 *   - trôi cực chậm (Zen): quay rất chậm quanh khung chân dung, nhích lên theo nhịp dài và hiện / tắt dần trong mỗi nhịp, "thở" nhẹ;
 *   - vùng chức danh và tên dưới chân dung: hạt mờ hẳn đi (không làm rối chữ). Hạt nằm trong canvas, dưới ảnh chân dung (HTML), nên phần
 *     sau khung ảnh bị che; chỉ thấy bụi QUANH khung.
 * Độ hiện theo mức hiện của ảnh chân dung (uAmount 0..1); bằng 0 thì ẩn hẳn, không vẽ. Toạ độ tính bằng px CSS so với tâm khung gallery
 * (y hướng lên), đổi sang đơn vị thế giới bằng uWpp. Số liệu ở config.js (DUST_*).
 */
import * as THREE from 'three';
import { CONFIG as C } from './config.js';

const VERTEX = /* glsl */ `
attribute vec4 aSeed;      // x: góc đầu (rad), y: vị trí trong vòng (0..1), z: pha (0..1), w: ngẫu nhiên 0..1
uniform float uTime;
uniform vec2 uCenter;      // tâm khung chân dung (px)
uniform vec2 uHalf;        // nửa rộng, nửa cao khung chân dung (px)
uniform vec2 uCapC;        // tâm vùng chức danh + tên (px)
uniform vec2 uCapH;        // nửa kích thước vùng đó (px)
uniform float uInner;
uniform float uSpread;
uniform float uWpp;
uniform float uPx;
uniform float uAmount;
uniform float uOrbit;
uniform float uRise;
uniform float uCycle;      // độ dài một nhịp trôi lên (px)
uniform vec2 uSize;        // cỡ hạt nhỏ nhất, lớn nhất (px CSS)
uniform vec2 uAlpha;       // opacity nhỏ nhất, lớn nhất của từng hạt
varying float vA;
varying float vW;

void main() {
	float w = aSeed.w;
	float r2 = fract( w * 7.31 + aSeed.z * 3.7 );
	float dirn = w > 0.5 ? 1.0 : -1.0;
	float ang = aSeed.x + uTime * uOrbit * ( 0.4 + w ) * dirn;
	float rho = ( uInner + aSeed.y * uSpread ) * ( 1.0 + 0.04 * sin( uTime * 0.25 + aSeed.z * 6.2832 ) );
	vec2 p = uCenter + vec2( cos( ang ) * uHalf.x, sin( ang ) * uHalf.y ) * rho;
	// Nhích lên theo nhịp dài: hiện dần rồi tắt dần ở hai đầu nhịp, vòng lại không bật hình.
	float rise = mod( uTime * uRise * ( 0.5 + w ) + aSeed.z * uCycle, uCycle );
	p.y += rise - 0.5 * uCycle;
	float env = sin( 3.14159265 * rise / uCycle );
	float breathe = 0.8 + 0.2 * sin( uTime * ( 0.35 + w * 0.5 ) + aSeed.z * 6.2832 );
	// Vùng chữ dưới chân dung: khoảng cách tới hình chữ nhật của vùng đó; trong vùng còn 10%, cách 48px trở ra thì đủ.
	vec2 q = max( abs( p - uCapC ) - uCapH, 0.0 );
	float capFade = mix( 0.1, 1.0, smoothstep( 0.0, 48.0, length( q ) ) );
	vA = uAmount * mix( uAlpha.x, uAlpha.y, r2 ) * env * breathe * capFade;
	vW = w;
	gl_PointSize = mix( uSize.x, uSize.y, r2 * r2 ) * uPx;
	gl_Position = projectionMatrix * modelViewMatrix * vec4( p * uWpp, 0.0, 1.0 );
}
`;

const FRAGMENT = /* glsl */ `
uniform vec3 uColor;
uniform vec3 uWarm;
varying float vA;
varying float vW;

void main() {
	// Đốm bokeh mép mềm: sáng đều ở giữa, tắt dần ra mép, không có lõi sắc.
	float d = length( gl_PointCoord - 0.5 ) * 2.0;
	float a = 1.0 - smoothstep( 0.35, 1.0, d );
	a *= a;
	vec3 col = mix( uColor, uWarm, smoothstep( 0.35, 0.95, vW ) );
	gl_FragColor = vec4( col, a * vA );
	#include <colorspace_fragment>
}
`;

// Số giả ngẫu nhiên cố định (LCG): cùng một bố cục mỗi lần tải, dễ kiểm thử.
function rng( seed ) {
	let s = seed >>> 0;
	return () => {
		s = ( Math.imul( s, 1664525 ) + 1013904223 ) >>> 0;
		return s / 4294967296;
	};
}

/**
 * @param {THREE.Scene} scene
 * @param {{ lite: boolean }} opts
 */
export function createDust( scene, { lite } ) {
	const count = lite ? C.DUST_COUNT_LITE : C.DUST_COUNT;
	const rand = rng( 20251119 );
	const seeds = new Float32Array( count * 4 );
	for ( let i = 0; i < count; i++ ) {
		seeds[ i * 4 ] = rand() * Math.PI * 2;
		seeds[ i * 4 + 1 ] = rand();
		seeds[ i * 4 + 2 ] = rand();
		seeds[ i * 4 + 3 ] = rand();
	}
	const geometry = new THREE.BufferGeometry();
	geometry.setAttribute( 'position', new THREE.BufferAttribute( new Float32Array( count * 3 ), 3 ) ); // không dùng (shader tự tính), three cần có
	geometry.setAttribute( 'aSeed', new THREE.BufferAttribute( seeds, 4 ) );

	const material = new THREE.ShaderMaterial( {
		uniforms: {
			uTime: { value: 0 },
			uCenter: { value: new THREE.Vector2( 0, 0 ) },
			uHalf: { value: new THREE.Vector2( 100, 120 ) },
			uCapC: { value: new THREE.Vector2( 0, -1e4 ) },
			uCapH: { value: new THREE.Vector2( 0, 0 ) },
			uInner: { value: C.DUST_INNER },
			uSpread: { value: C.DUST_SPREAD },
			uWpp: { value: 0.01 },
			uPx: { value: 1 },
			uAmount: { value: 0 },
			uOrbit: { value: C.DUST_ORBIT },
			uRise: { value: C.DUST_RISE },
			uCycle: { value: C.DUST_CYCLE },
			uSize: { value: new THREE.Vector2( C.DUST_SIZE_MIN, C.DUST_SIZE_MAX ) },
			uAlpha: { value: new THREE.Vector2( C.DUST_ALPHA_MIN, C.DUST_ALPHA_MAX ) },
			uColor: { value: new THREE.Color( C.DUST_COLOR ) },
			uWarm: { value: new THREE.Color( C.DUST_COLOR_WARM ) },
		},
		vertexShader: VERTEX,
		fragmentShader: FRAGMENT,
		transparent: true,
		blending: THREE.AdditiveBlending,
		depthTest: false,
		depthWrite: false,
	} );
	const points = new THREE.Points( geometry, material );
	points.frustumCulled = false; // vị trí tính trong shader, hộp bao của hình học vô nghĩa
	points.visible = false;
	points.position.z = -C.FOCUS_DIST;
	points.renderOrder = -1;
	scene.add( points );

	const u = material.uniforms;
	return {
		/**
		 * Đặt kích thước (px, tâm khung gallery làm gốc, y hướng lên): khung chân dung, vùng chức danh + tên, wpp, dpr.
		 */
		resize( { cx, cy, halfW, halfH, capX, capY, capHalfW, capHalfH, wpp, dpr } ) {
			u.uCenter.value.set( cx, cy );
			u.uHalf.value.set( halfW, halfH );
			if ( capHalfW > 0 && capHalfH > 0 ) {
				u.uCapC.value.set( capX, capY );
				u.uCapH.value.set( capHalfW, capHalfH );
			}
			u.uWpp.value = wpp;
			u.uPx.value = dpr;
		},
		/**
		 * @param {number} amount Mức hiện 0..1 (theo ảnh chân dung).
		 * @param {number} sec    Thời gian (giây).
		 * @return {boolean} true nếu đang hiện (cần vẽ liên tục để hạt chuyển động).
		 */
		update( amount, sec ) {
			points.visible = amount > 0.003;
			if ( points.visible ) {
				u.uAmount.value = amount;
				u.uTime.value = sec;
			}
			return points.visible;
		},
		dispose() {
			scene.remove( points );
			geometry.dispose();
			material.dispose();
		},
	};
}
