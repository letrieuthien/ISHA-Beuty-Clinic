/**
 * Bầu trời: Sky (Preetham) của three/addons, cập nhật uniform từ sampleStops mỗi khung hình. Hướng mặt trời do scene/index.js tính từ vị
 * trí mặt trời trên màn hình (mặt trời neo theo khung, xem stops.js).
 *
 * Chèn thêm vào fragment shader của Sky (chuỗi GLSL, trước gl_FragColor):
 * - uSkyGain: hệ số khuếch đại bức xạ trời;
 * - "sàn sáng" (token floor): trộn gradient --isha-skin-soft (chân trời) → --isha-daylight (đỉnh) theo uFloorMix (keyframe skyFloor),
 *   giữ một chế độ SÁNG cho cả rạng đông;
 * - dưới đường chân trời: thay bằng màu sương (Preetham vẽ nửa dưới rất tối);
 * - MẶT TRỜI là chủ thể: tắt đĩa Preetham (chỉ khoảng 0.5°, bị nén thành trắng nên trông như mặt trăng), vẽ đĩa riêng bán kính uSunRadius
 *   (mặc định 2.2°) mép mềm, lõi Daylight/Golden, viền Golden, cường độ vừa phải để giữ sắc vàng sau tone mapping; hào quang hai lớp
 *   (exp(−θ/6°) và exp(−θ/22°)) màu Golden → Sunset theo thời điểm, lớp ngoài có tia sáng mềm theo góc (24 tia, trôi rất chậm).
 *   Địa hình vẽ sau và có chiều sâu nên tự che đĩa lúc rạng đông (mặt trời ló sau đỉnh chính).
 * Mọi màu đến từ token qua uniform (tokens.js), không có mã màu trong shader.
 */

const INJECT_UNIFORMS = /* glsl */ `
uniform float uSkyGain;
uniform float uFloorMix;
uniform vec3 uFloorTop;
uniform vec3 uFloorHorizon;
uniform vec3 uBelowColor;
uniform float uSunRadius;
uniform float uDiscIntensity;
uniform vec3 uDiscCore;
uniform vec3 uDiscRim;
uniform vec3 uHaloColor;
uniform vec3 uHaloOuter;
uniform float uHalo;
`;

const INJECT_BODY = /* glsl */ `
			texColor *= uSkyGain;
			float upness = clamp( direction.y, 0.0, 1.0 );
			vec3 floorCol = mix( uFloorHorizon, uFloorTop, smoothstep( 0.0, 0.55, upness ) );
			texColor = mix( texColor, floorCol, uFloorMix );
			texColor = mix( texColor, uBelowColor, smoothstep( 0.01, -0.04, direction.y ) );

			// Mặt trời: góc tới tâm (theta) và góc quanh tâm (phi) để vẽ tia.
			float sTheta = acos( clamp( dot( direction, vSunDirection ), -1.0, 1.0 ) );
			vec3 sRight = normalize( cross( vSunDirection, vec3( 0.0, 1.0, 0.0 ) ) + vec3( 1e-5, 0.0, 0.0 ) );
			vec3 sUp = cross( sRight, vSunDirection );
			vec3 sTan = direction - vSunDirection * dot( direction, vSunDirection );
			float sPhi = atan( dot( sTan, sUp ), dot( sTan, sRight ) );
			float sRays = 0.72 + 0.28 * sin( sPhi * 24.0 + time * 0.1 );
			float haloIn = exp( -sTheta / radians( 6.0 ) );
			float haloOut = exp( -sTheta / radians( 22.0 ) ) * sRays;
			texColor += ( uHaloColor * haloIn * 0.85 + uHaloOuter * haloOut * 0.32 ) * uHalo;
			float disc = 1.0 - smoothstep( uSunRadius * 0.82, uSunRadius, sTheta );
			vec3 discCol = mix( uDiscRim, uDiscCore, 1.0 - smoothstep( 0.0, uSunRadius, sTheta ) );
			texColor = mix( texColor, discCol * uDiscIntensity, disc );

			gl_FragColor = vec4( texColor, 1.0 );`;

export function buildSky( THREE, Sky ) {
	const sky = new Sky();
	sky.name = 'sky';
	sky.scale.setScalar( 15000 );
	const mat = sky.material;
	const u = mat.uniforms;
	u.uSkyGain = { value: 1 };
	u.uFloorMix = { value: 0.3 };
	u.uFloorTop = { value: new THREE.Color() };
	u.uFloorHorizon = { value: new THREE.Color() };
	u.uBelowColor = { value: new THREE.Color() };
	u.uSunRadius = { value: THREE.MathUtils.degToRad( 2.2 ) };
	u.uDiscIntensity = { value: 1.6 };
	u.uDiscCore = { value: new THREE.Color() };
	u.uDiscRim = { value: new THREE.Color() };
	u.uHaloColor = { value: new THREE.Color() };
	u.uHaloOuter = { value: new THREE.Color() };
	u.uHalo = { value: 1 };
	u.showSunDisc.value = 0; // đĩa Preetham quá nhỏ; dùng đĩa riêng ở trên

	const marker = 'gl_FragColor = vec4( texColor, 1.0 );';
	if ( mat.fragmentShader.indexOf( marker ) < 0 ) {
		console.warn( '[ISHA svc] Không tìm thấy điểm chèn trong shader Sky (đổi phiên bản three?). Bỏ lớp sàn sáng và đĩa mặt trời.' );
		u.showSunDisc.value = 1;
	} else {
		mat.fragmentShader = INJECT_UNIFORMS + mat.fragmentShader.replace( marker, INJECT_BODY.trim() );
		mat.needsUpdate = true;
	}

	// Mây mỏng có sẵn của Sky.
	u.cloudCoverage.value = 0.18;
	u.cloudDensity.value = 0.3;
	u.cloudScale.value = 0.00022;
	u.cloudSpeed.value = 0.000012;

	return {
		mesh: sky,
		/**
		 * @param {object} s   Kết quả sampleStops (mặt trời theo sunSmoothed).
		 * @param {object} c   Màu tuyến tính (tokens.js).
		 * @param {object} opt { sunDir (Vector3, đã chuẩn hoá), sunColor (Color), floorMix, belowColor, time, gain, radiusDeg, discIntensity, halo }
		 */
		update( s, c, opt ) {
			u.sunPosition.value.copy( opt.sunDir );
			u.turbidity.value = s.turbidity;
			u.rayleigh.value = s.rayleigh;
			u.mieCoefficient.value = s.mieCoefficient;
			u.mieDirectionalG.value = s.mieG;
			u.uSkyGain.value = opt.gain;
			u.uFloorMix.value = opt.floorMix;
			u.uFloorTop.value.copy( c.daylight );
			u.uFloorHorizon.value.copy( c.skinSoft );
			u.uBelowColor.value.copy( opt.belowColor );
			u.uSunRadius.value = THREE.MathUtils.degToRad( opt.radiusDeg );
			u.uDiscIntensity.value = opt.discIntensity;
			// Mặt trời LUÔN vàng (không ngả trắng xám như mặt trăng): lõi Golden pha Daylight sáng dần theo thời điểm (tone 0 → 1), viền
			// Golden; hào quang từ Sunset (rạng đông) về Golden (toàn quang). opt.tone = sunTone của keyframe.
			const tone = opt.tone;
			u.uDiscCore.value.copy( c.golden ).lerp( c.daylight, 0.25 + 0.3 * tone );
			u.uDiscRim.value.copy( c.golden ).lerp( c.sunset, 0.35 * ( 1 - tone ) );
			u.uHaloColor.value.copy( c.sunset ).lerp( c.golden, 0.4 + 0.6 * tone );
			u.uHaloOuter.value.copy( c.sunset ).lerp( c.golden, 0.3 + 0.4 * tone );
			u.uHalo.value = opt.halo;
			u.time.value = opt.time;
		},
		dispose() {
			sky.geometry.dispose();
			mat.dispose();
		},
	};
}
