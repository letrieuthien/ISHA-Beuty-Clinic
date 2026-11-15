/**
 * Sun glare / sun flash: lóa kiểu ống kính quanh mặt trời (thay vòng tia line-art SVG). Vẽ SAU cảnh và hậu kỳ, cộng sáng (additive), mọi chế
 * độ WebGL (LITE cũng có: chỉ vài quad, không đọc texture).
 *
 * Thành phần (theo ảnh mẫu khách gửi):
 * - lõi trắng nóng nhỏ, quầng cam hai lớp;
 * - các vệt sao (starburst) mảnh, dài ngắn khác nhau, lệch góc ngẫu nhiên, xoay và "thở" rất chậm; thêm vài chùm tia rộng mềm màu cam;
 * - đốm lens ghost (đĩa mờ viền sáng) nằm trên trục từ mặt trời qua tâm khung hình, sang phía đối diện.
 * Cường độ nhân với độ lộ của mặt trời (horizon.js: núi che thì tắt dần) và "flash" (lóe khi mặt trời vừa ló qua đỉnh, lóe nhẹ khi đổi liệu
 * trình; scene/index.js tính). Màu phái sinh từ token (Daylight, Golden, Sunset, Skin, xanh cỏ), không có mã màu trong shader.
 *
 * Toạ độ: quad đặt thẳng trong NDC (không cần camera); kích thước tính theo đơn vị chiều cao khung (NDC y) để mọi thứ tròn trên mọi tỉ lệ.
 */

// [vị trí trên trục (0 = mặt trời, 1 = tâm khung, 2 = điểm đối xứng), nửa cạnh (NDC y), màu (0 Golden, 1 Sunset, 2 xanh nhạt, 3 Skin), độ sáng]
const GHOSTS = [
	[ 0.55, 0.035, 0, 0.55 ],
	[ 1.25, 0.1, 2, 0.32 ],
	[ 1.5, 0.05, 1, 0.5 ],
	[ 1.85, 0.15, 3, 0.22 ],
	[ 2.2, 0.03, 0, 0.5 ],
];
const MAIN_SIZE = 0.9;

export function buildGlare( THREE, { colors } ) {
	const quad = new THREE.PlaneGeometry( 2, 2 );
	const geo = new THREE.InstancedBufferGeometry();
	geo.index = quad.index;
	geo.attributes.position = quad.attributes.position;
	const n = 1 + GHOSTS.length;
	const inst = new Float32Array( n * 4 ); // t, size, tint, gain
	const kind = new Float32Array( n );
	inst.set( [ 0, MAIN_SIZE, 0, 1 ], 0 );
	GHOSTS.forEach( ( g, i ) => {
		inst.set( g, ( i + 1 ) * 4 );
		kind[ i + 1 ] = 1;
	} );
	geo.setAttribute( 'aInst', new THREE.InstancedBufferAttribute( inst, 4 ) );
	geo.setAttribute( 'aKind', new THREE.InstancedBufferAttribute( kind, 1 ) );
	geo.instanceCount = n;

	const uniforms = {
		uSun: { value: new THREE.Vector2() },
		uAspect: { value: 1 },
		uTime: { value: 0 },
		uRot: { value: 0 },
		uVis: { value: 1 },
		uFlash: { value: 0 },
		uIntensity: { value: 1 },
		uStreakGain: { value: 1 },
		uGhostGain: { value: 1 },
		uCore: { value: new THREE.Color() },
		uGlow: { value: new THREE.Color() },
		uGlowOuter: { value: new THREE.Color() },
		uTint0: { value: new THREE.Color() },
		uTint1: { value: new THREE.Color() },
		uTint2: { value: new THREE.Color() },
		uTint3: { value: new THREE.Color() },
	};

	const material = new THREE.ShaderMaterial( {
		uniforms,
		transparent: true,
		blending: THREE.AdditiveBlending,
		depthTest: false,
		depthWrite: false,
		vertexShader: /* glsl */ `
			attribute vec4 aInst;
			attribute float aKind;
			uniform vec2 uSun;
			uniform float uAspect;
			uniform vec3 uTint0;
			uniform vec3 uTint1;
			uniform vec3 uTint2;
			uniform vec3 uTint3;
			varying vec2 vP;
			varying float vKind;
			varying float vSize;
			varying float vGain;
			varying vec3 vTint;
			void main() {
				vec2 c = uSun * ( 1.0 - aInst.x );
				vec2 off = position.xy * aInst.y;
				vP = position.xy;
				vKind = aKind;
				vSize = aInst.y;
				vGain = aInst.w;
				vTint = aInst.z < 0.5 ? uTint0 : aInst.z < 1.5 ? uTint1 : aInst.z < 2.5 ? uTint2 : uTint3;
				gl_Position = vec4( c.x + off.x / uAspect, c.y + off.y, 0.0, 1.0 );
			}
		`,
		fragmentShader: /* glsl */ `
			uniform float uTime;
			uniform float uRot;
			uniform float uVis;
			uniform float uFlash;
			uniform float uIntensity;
			uniform float uStreakGain;
			uniform float uGhostGain;
			uniform vec3 uCore;
			uniform vec3 uGlow;
			uniform vec3 uGlowOuter;
			varying vec2 vP;
			varying float vKind;
			varying float vSize;
			varying float vGain;
			varying vec3 vTint;
			const float TAU = 6.28318530718;
			float hash1( float n ) {
				return fract( sin( n * 12.9898 ) * 43758.5453 );
			}
			float wrap( float a ) {
				return atan( sin( a ), cos( a ) );
			}
			void main() {
				vec3 col;
				if ( vKind < 0.5 ) {
					vec2 p = vP * vSize;
					float r = length( p );
					float phi = atan( p.y, p.x );
					float boost = 1.0 + uFlash * 0.9;

					// Lõi trắng nóng + quầng cam hai lớp.
					float core = exp( -r / 0.012 ) + 0.35 * exp( -r / 0.03 );
					// Quầng nhẹ: trời quanh mặt trời vốn đã rất sáng (hào quang của sky.js), cộng nhiều sẽ cháy trắng cả vùng.
					float glow = exp( -r / 0.05 ) * 0.2 + exp( -r / 0.16 ) * 0.04;

					// Starburst: 14 vệt mảnh, mỗi vệt một góc lệch, độ dài, độ rộng riêng; xoay và "thở" rất chậm.
					float M = 14.0;
					float id = floor( ( phi - uRot ) / TAU * M + 0.5 );
					float hid = mod( id, M ); // id ở hai phía của góc ±180° là cùng một vệt: băm theo hid để không có đường nối
					float h1 = hash1( hid + 3.1 );
					float h2 = hash1( hid * 1.71 + 7.7 );
					float h3 = hash1( hid * 2.93 + 1.3 );
					float ang = uRot + ( id + ( h1 - 0.5 ) * 0.55 ) * TAU / M;
					float d = wrap( phi - ang );
					float perp = abs( sin( d ) ) * r;
					float along = step( 0.0, cos( d ) );
					float len = mix( 0.1, 0.48, h2 * h2 ) * ( 0.88 + 0.12 * sin( uTime * 0.7 + hid * 2.3 ) ) * ( 1.0 + uFlash * 0.5 );
					float w = mix( 0.0011, 0.0028, h3 ) * ( 1.0 + r * 1.5 );
					float streak = along * exp( -( perp * perp ) / ( w * w ) ) * exp( -r / len );

					// Chùm tia rộng mềm (cam), 7 chùm.
					float M2 = 7.0;
					float id2 = floor( ( phi + uRot * 0.6 ) / TAU * M2 + 0.5 );
					float hid2 = mod( id2, M2 );
					float ang2 = -uRot * 0.6 + ( id2 + ( hash1( hid2 + 11.0 ) - 0.5 ) * 0.4 ) * TAU / M2;
					float soft = pow( max( cos( wrap( phi - ang2 ) * M2 * 0.5 ), 0.0 ), 18.0 ) * exp( -r / mix( 0.18, 0.34, hash1( hid2 + 5.0 ) ) );

					vec3 streakCol = mix( uCore, uGlow, smoothstep( 0.0, 0.3, r ) );
					col = uCore * core * boost
						+ uGlow * glow * ( 1.0 + uFlash * 0.3 )
						+ streakCol * streak * 0.55 * uStreakGain * boost
						+ uGlowOuter * soft * 0.07 * uStreakGain;
					// Mép quad: tắt hẳn để không lộ cạnh.
					col *= 1.0 - smoothstep( 0.8, 1.0, max( abs( vP.x ), abs( vP.y ) ) );
				} else {
					// Lens ghost: đĩa mờ viền sáng.
					float d = length( vP );
					float disc = smoothstep( 1.0, 0.5, d ) * 0.45 + exp( -pow( ( d - 0.84 ) / 0.07, 2.0 ) ) * 0.55;
					disc *= 1.0 - smoothstep( 0.94, 1.0, d );
					col = vTint * disc * vGain * 0.45 * uGhostGain * ( 1.0 + uFlash * 0.8 );
				}
				gl_FragColor = vec4( col * uIntensity * uVis, 1.0 ); // không gian hiển thị: màu uniform đã là sRGB
			}
		`,
	} );

	const mesh = new THREE.Mesh( geo, material );
	mesh.name = 'sun-glare';
	mesh.frustumCulled = false;
	const scene = new THREE.Scene();
	scene.add( mesh );
	const cam = new THREE.Camera();
	let palette = colors;

	return {
		uniforms,
		/**
		 * @param {{sunNdc:{x:number,y:number}, aspect:number, vis:number, flash:number, time:number, rot:number, tone:number,
		 *   intensity:number, streaks:number, ghosts:number}} o
		 */
		update( o ) {
			const c = palette;
			uniforms.uSun.value.set( o.sunNdc.x, o.sunNdc.y );
			uniforms.uAspect.value = o.aspect;
			uniforms.uTime.value = o.time;
			uniforms.uRot.value = o.rot;
			uniforms.uVis.value = o.vis;
			uniforms.uFlash.value = o.flash;
			uniforms.uIntensity.value = o.intensity;
			uniforms.uStreakGain.value = o.streaks;
			uniforms.uGhostGain.value = o.ghosts;
			// Rạng đông cam (Sunset), về sau vàng (Golden) sáng hơn; lõi luôn gần trắng (Daylight pha Golden).
			const t = o.tone;
			uniforms.uCore.value.copy( c.daylight ).lerp( c.golden, 0.18 );
			uniforms.uGlow.value.copy( c.sunset ).lerp( c.golden, 0.25 + 0.55 * t );
			uniforms.uGlowOuter.value.copy( c.sunset ).lerp( c.golden, 0.15 + 0.4 * t );
			uniforms.uTint0.value.copy( c.golden );
			uniforms.uTint1.value.copy( c.sunset );
			uniforms.uTint2.value.copy( c.greenLight ).lerp( c.daylight, 0.35 );
			uniforms.uTint3.value.copy( c.skin );
			// Cộng sáng trực tiếp trong không gian hiển thị (sau tone mapping): đổi màu token tuyến tính về sRGB, để đuôi mờ của vệt sao giảm
			// đúng như nhìn thấy (nếu cộng giá trị tuyến tính rồi mã hoá sRGB, phần mờ bị khuếch đại thành vệt dày).
			for ( const k of [ 'uCore', 'uGlow', 'uGlowOuter', 'uTint0', 'uTint1', 'uTint2', 'uTint3' ] ) {
				uniforms[ k ].value.convertLinearToSRGB();
			}
			mesh.visible = o.vis * o.intensity > 1e-3;
		},
		setColors( c ) {
			palette = c;
		},
		render( renderer ) {
			if ( mesh.visible ) {
				renderer.render( scene, cam );
			}
		},
		dispose() {
			quad.dispose();
			geo.dispose();
			material.dispose();
		},
	};
}
