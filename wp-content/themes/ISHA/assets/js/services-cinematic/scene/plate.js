/**
 * Lưới ảnh nền của cảnh v3 (docs/isha-lookdev-prompt-v3.md mục 3.3 – 3.5, 5.2, 7). Đặc tả đặt tên terrainMesh.js; ở đây là plate.js
 * ("plate" = tấm ảnh nền 2.5D) để tiêu chí nghiệm thu `git grep terrain` sạch.
 *
 * DỰNG LƯỚI BẰNG CHIẾU NGƯỢC (3.3), trong vertex shader nên đổi kích thước khung chỉ đổi uniform, không dựng lại lưới:
 *   mỗi đỉnh có toạ độ ảnh g = (u, v) → vị trí trên khung tham chiếu theo luật đóng khung (5.2: fit theo chiều rộng, neo horizonUV vào
 *   framing.horizon*) → tia từ camera tham chiếu (gốc toạ độ, nhìn −Z, cùng FOV với camera thật) → đỉnh = tia · mix( near, far, độ sâu ).
 *   Nhìn từ vị trí tham chiếu (p = 0) hình trùng khít ảnh gốc; camera rời chỗ mới sinh parallax.
 *   Lưới tràn hai bên extendU (texture kẹp mép) và tràn xuống dưới đáy ảnh `skirt` chiều cao ảnh (khung dọc: ảnh fit ngang không phủ tới
 *   đáy khung). Phần tràn dưới đáy ở độ sâu 0, màu lấy từ ảnh đã làm mờ mạnh rồi tối dần (khung dọc: card nằm ở vùng này).
 *   Hàng lưới xếp từ trên xuống: tam giác xa vẽ trước.
 *
 * CẮT TRỜI THEO ĐỘ SÁNG (3.4): alpha = ( 1 − smoothstep( lumLow, lumHigh, lum ) ) · mép mềm theo hàng ảnh (bandY ± band). Trời của ảnh sương
 *   là thứ sáng nhất nên mép cắt tự bám đường viền rặng, không có vệt ngang.
 *
 * CHỐNG KÉO GIÃN (3.5), lệch đặc tả có chủ ý: đặc tả mờ theo độ dốc màn hình của độ sâu (dFdx / dFdy), nhưng mép rặng luôn dốc nên cách đó
 *   làm mờ cả ở p = 0, phá tiêu chí "trùng khít ảnh gốc". Ở đây đo độ giãn THẬT: vertex shader chiếu thêm hai đỉnh láng giềng (u + du,
 *   v + dv) qua camera hiện tại, so khoảng cách trên màn hình với lúc ở vị trí tham chiếu; giãn quá stretchLo lần thì mờ dần, tới stretchHi
 *   thì trong suốt (lộ trời / sương phía sau). p = 0: không giãn, không mờ. Giá trị nội suy giữa các đỉnh nên mờ mượt, không lộ mặt tam giác.
 *
 * VẼ HAI LƯỢT để không phải sắp xếp tam giác trong suốt: `core` (alpha ≈ 1, đục, ghi chiều sâu: mặt trời ở z −1050 tự bị rặng gần che),
 *   `fringe` (phần mép mờ: hoà trộn, không ghi chiều sâu).
 *
 * TÔ MÀU (7), trong không gian hiển thị (sRGB) như tô màu ảnh: base = mix( farTint, ridgeBase, mix( mixNear, mixFar, d ) ); màu = base ·
 *   gray · gain, gray = độ sáng ảnh (giữ AO và khe núi của ảnh thật). d của MÀU tính từ độ sáng từng pixel (gradient map), không từ bản
 *   đồ đã làm mượt của lưới (bản mượt tạo quầng nhạt dọc đường viền rặng). farTint = mix( fog, Golden, aerial ): rặng xa ngả màu trời sát
 *   chân trời để hoà với trời (thêm so với đặc tả). Viền sáng: pixel ở mép rặng (độ dốc độ sâu lớn), sát đường viền thật (ảnh phía xa sáng
 *   hơn), hướng về mặt trời trên màn hình; cộng mix( Golden, Metal, 0.4 ) · rimIntensity · tắt dần theo khoảng cách tới mặt trời.
 */
import { DEPTH_GLSL, SRGB_GLSL } from './depth.js';

const VS = /* glsl */ `
${ DEPTH_GLSL }
uniform vec2 uRefTan;
uniform float uFitScale;
uniform float uNdcHorizon;
uniform float uHorizonV;
uniform float uNear;
uniform float uFar;
uniform vec2 uStep;
uniform vec2 uViewport;
varying vec2 vUv;
varying float vStretch;
varying float vSkirt;
vec2 texUv( vec2 g ) {
	return clamp( g, vec2( 0.0005 ), vec2( 0.9995 ) );
}
vec3 place( vec2 g ) {
	float d = g.y < 0.0 ? 0.0 : sceneDepth( texUv( g ) );
	vec2 ndc = vec2( g.x * 2.0 - 1.0, uNdcHorizon + ( g.y - uHorizonV ) * uFitScale );
	return normalize( vec3( ndc * uRefTan, -1.0 ) ) * mix( uNear, uFar, d );
}
vec2 toPx( vec3 p ) {
	vec4 c = projectionMatrix * modelViewMatrix * vec4( p, 1.0 );
	return c.xy / c.w * 0.5 * uViewport;
}
void main() {
	vec2 g = position.xy;
	vec3 p = place( g );
	vec2 s0 = toPx( p );
	float lx = length( toPx( place( g + vec2( uStep.x, 0.0 ) ) ) - s0 ) / ( uStep.x * uViewport.x );
	float ly = length( toPx( place( g + vec2( 0.0, uStep.y ) ) ) - s0 ) / ( uStep.y * uFitScale * 0.5 * uViewport.y );
	vStretch = max( lx, ly );
	vUv = texUv( g );
	vSkirt = max( -g.y, 0.0 );
	gl_Position = projectionMatrix * modelViewMatrix * vec4( p, 1.0 );
}
`;

const FS = /* glsl */ `
${ SRGB_GLSL }
${ DEPTH_GLSL }
uniform sampler2D uPhoto;
uniform vec2 uDepthTexel;
uniform float uFitScale;
uniform vec2 uViewport;
uniform float uSkyLumLow;
uniform float uSkyLumHigh;
uniform float uCutY;
uniform float uCutBand;
uniform float uStretchLo;
uniform float uStretchHi;
uniform vec3 uFog;
uniform vec3 uRidge;
uniform vec3 uRimColor;
uniform vec3 uSkyLow;
uniform float uAerial;
uniform float uHaze;
uniform float uMixNear;
uniform float uMixFar;
uniform float uGain;
uniform float uRimLo;
uniform float uRimHi;
uniform float uRimFalloff;
uniform float uRimAmount;
uniform vec2 uSunNdc;
uniform int uDebug;
varying vec2 vUv;
varying float vStretch;
varying float vSkirt;
const vec3 Y709 = vec3( 0.2126, 0.7152, 0.0722 );
vec3 toLinear( vec3 c ) {
	return mix( c / 12.92, pow( ( max( c, 0.0 ) + 0.055 ) / 1.055, vec3( 2.4 ) ), step( 0.04045, c ) );
}
float lumAt( vec2 uv ) {
	return dot( toSrgb( texture( uPhoto, uv ).rgb ), Y709 );
}
void main() {
	vec3 photo = toSrgb( texture( uPhoto, vUv ).rgb );
	float lum = dot( photo, Y709 );
	float d = sceneDepth( vUv );

	float alpha = 1.0;
	if ( vSkirt <= 0.0 ) {
		float sky = smoothstep( uSkyLumLow, uSkyLumHigh, lum );
		float band = smoothstep( uCutY - uCutBand, uCutY + uCutBand, 1.0 - vUv.y );
		alpha = ( 1.0 - sky ) * band;
	}
	alpha *= 1.0 - smoothstep( uStretchLo, uStretchHi, vStretch );
#ifdef CORE
	if ( alpha < 0.996 ) {
		discard;
	}
	alpha = 1.0;
#else
	if ( alpha >= 0.996 || alpha < 0.004 ) {
		discard;
	}
#endif

	// Tô màu theo độ sâu (sRGB hiển thị). Độ sâu cho MÀU lấy từ độ sáng từng điểm ảnh (cùng ánh xạ smoothstep + gamma), không lấy bản
	// đã làm mượt của lưới: bản mượt làm mép trên của rặng gần nhận tông "xa" sáng hơn, thành quầng nhạt dọc đường viền. Đây là một
	// gradient map: màu bám đúng từng pixel, lưới vẫn mượt. Phía xa ngả về màu trời sát chân trời (aerial) để rặng xa hoà với trời.
	float dc = pow( smoothstep( uLumNear, uLumFar, lum ), uDepthGamma );
	vec3 farTint = mix( uFog, uSkyLow, uAerial );
	vec3 base = mix( farTint, uRidge, mix( uMixNear, uMixFar, dc ) );
	vec3 col = base * lum * uGain;
	// Không khí: lớp càng xa càng phủ màu sương (núi chìm trong sương), theo keyframe fogDensity.
	col = mix( col, farTint, uHaze * pow( dc, 1.4 ) );

	// Phần tràn dưới đáy ảnh (khung dọc): độ sáng đáy ảnh làm mờ rất mạnh theo chiều ngang (9 mẫu Gauss trên mip 6: một mip thấp đơn
	// lẻ nội suy tuyến tính thành các dải dốc, mắt thấy như sọc dọc), cùng gradient map, tối dần nhẹ; 8% đáy ảnh hoà dần vào màu đó để
	// không có đường nối. Khung ngang: đáy ảnh nằm ngoài khung nên không thấy.
	float lb = 0.0;
	for ( int i = -4; i <= 4; i++ ) {
		float fi = float( i );
		lb += exp( -fi * fi / 8.0 ) * dot( toSrgb( textureLod( uPhoto, vec2( vUv.x + fi / 64.0, 0.03 ), 6.0 ).rgb ), Y709 );
	}
	lb /= 4.898; // tổng trọng số exp( −i² / 8 ), i = −4..4
	// Xuống sâu trong phần tràn, độ sáng hoà về trung bình của cả đáy ảnh (mip 10): màu không đổi theo cột nên không kéo thành sọc dọc.
	float lbAvg = dot( toSrgb( textureLod( uPhoto, vec2( 0.5, 0.03 ), 10.0 ).rgb ), Y709 );
	lb = mix( lb, lbAvg, smoothstep( 0.0, 0.35, vSkirt ) );
	vec3 skirtCol = mix( farTint, uRidge, mix( uMixNear, uMixFar, pow( smoothstep( uLumNear, uLumFar, lb ), uDepthGamma ) ) ) * lb * uGain;
	if ( vSkirt > 0.0 ) {
		col = skirtCol * mix( 1.0, 0.78, smoothstep( 0.0, 0.8, vSkirt ) );
	} else {
		col = mix( skirtCol, col, smoothstep( 0.0, 0.08, vUv.y ) );
	}

	// Viền sáng.
	// Độ dốc độ sâu tính trên một chiều rộng ảnh, hai trục cùng thang: texel của bản đồ vuông (dw / dh = tỉ lệ ảnh) nên chênh lệch qua
	// 2 texel theo trục nào cũng chia cho 2 · e.x.
	vec2 e = uDepthTexel;
	vec2 grad = vec2(
		sceneDepth( vUv + vec2( e.x, 0.0 ) ) - sceneDepth( vUv - vec2( e.x, 0.0 ) ),
		sceneDepth( vUv + vec2( 0.0, e.y ) ) - sceneDepth( vUv - vec2( 0.0, e.y ) )
	) / ( 2.0 * e.x );
	float gm = length( grad );
	vec2 gdir = grad / max( gm, 1e-5 );
	vec2 pixNdc = gl_FragCoord.xy / uViewport * 2.0 - 1.0;
	vec2 toSun = ( uSunNdc - pixNdc ) * vec2( uViewport.x / uViewport.y, 1.0 );
	float facing = max( dot( gdir, normalize( toSun + 1e-5 ) ), 0.0 );
	float dist = length( toSun ) * 0.5;
	vec2 off = gdir * 2.0 * vec2( 1.0 / uViewport.x, 1.0 / ( uFitScale * 0.5 * uViewport.y ) );
	float edge = lumAt( vUv + off ) - lumAt( vUv - off ); // > 0: phía xa sáng hơn → pixel thuộc rặng gần, ngay sát mép
	float rim = smoothstep( uRimLo, uRimHi, gm ) * smoothstep( 0.01, 0.07, edge ) * facing * exp( -dist / uRimFalloff ) * uRimAmount;
	if ( vSkirt > 0.0 ) {
		rim = 0.0;
	}
	col += uRimColor * rim;

	if ( uDebug == 1 ) {
		col = vec3( alpha );
	} else if ( uDebug == 2 ) {
		col = vec3( clamp( ( vStretch - 1.0 ) / ( uStretchHi - 1.0 ), 0.0, 1.0 ) );
	} else if ( uDebug == 3 ) {
		col = vec3( rim );
	}
	gl_FragColor = vec4( toLinear( col ), alpha );
	#include <tonemapping_fragment>
	#include <colorspace_fragment>
}
`;

export const PLATE_DEBUG = Object.freeze( { none: 0, alpha: 1, stretch: 2, rim: 3 } );

/**
 * Che khuất mềm bởi lưới ảnh, cho các lớp hiệu ứng nằm trên một mặt phẳng z (sương, mặt trời, tia). Depth test cứng cắt mặt phẳng hiệu ứng
 * theo đường đồng độ sâu của lưới, chạy ngang giữa sườn núi thành mép sắc vô lý; ở đây tự tính khoảng cách z của lưới trên đường nhìn từ
 * camera tham chiếu qua điểm p (gần đúng: bỏ qua parallax vài px giữa hai độ sâu), rồi mờ dần trong khoảng `soft`. Ở đường viền rặng độ sâu
 * nhảy rất dốc nên chỗ che vẫn sắc; trên sườn thoải thì chuyển mượt. Ngoài ảnh phía trên và vùng trời của ảnh (độ sâu ≈ 1): không che.
 * Uniform dùng chung đối tượng với lưới (plate.occlusionUniforms) nên luôn khớp đóng khung hiện tại.
 */
export const OCCLUSION_GLSL = /* glsl */ `
${ DEPTH_GLSL }
uniform vec2 uRefTan;
uniform float uFitScale;
uniform float uNdcHorizon;
uniform float uHorizonV;
uniform float uNear;
uniform float uFar;
float plateZAt( vec3 p ) {
	vec2 ndc = p.xy / ( -p.z * uRefTan );
	float v = uHorizonV + ( ndc.y - uNdcHorizon ) / uFitScale;
	if ( v > 1.0 ) {
		return 1e5;
	}
	vec2 uv = clamp( vec2( ndc.x * 0.5 + 0.5, v ), vec2( 0.0005 ), vec2( 0.9995 ) );
	float d = v < 0.0 ? 0.0 : sceneDepth( uv );
	float dist = mix( uNear, uFar, d ) / length( vec3( ndc * uRefTan, 1.0 ) );
	return mix( dist, 1e5, smoothstep( 0.86, 0.96, d ) );
}
float plateVisible( vec3 p, float soft ) {
	return smoothstep( 0.0, soft, plateZAt( p ) + p.z );
}
`;

/** Lưới (u, v) phủ ảnh + phần tràn. Hàng từ trên (v = 1) xuống dưới; vị trí đỉnh = (u, v, 0), shader tự đặt. */
function buildGrid( THREE, cols, ext, skirt ) {
	const rows = Math.max( 2, Math.round( ( cols * 9 ) / 16 ) );
	const skirtRows = Math.max( 4, Math.round( rows * 0.15 * skirt ) );
	const vs = [];
	for ( let j = rows; j >= 0; j-- ) {
		vs.push( j / rows );
	}
	for ( let k = 1; k <= skirtRows; k++ ) {
		vs.push( ( -skirt * k ) / skirtRows );
	}
	const nu = cols + 1;
	const pos = new Float32Array( nu * vs.length * 3 );
	let o = 0;
	for ( const v of vs ) {
		for ( let i = 0; i <= cols; i++ ) {
			pos[ o++ ] = -ext + ( ( 1 + 2 * ext ) * i ) / cols;
			pos[ o++ ] = v;
			pos[ o++ ] = 0;
		}
	}
	const idx = new Uint32Array( cols * ( vs.length - 1 ) * 6 );
	o = 0;
	for ( let j = 0; j < vs.length - 1; j++ ) {
		for ( let i = 0; i < cols; i++ ) {
			const a = j * nu + i;
			const b = a + 1;
			const c = a + nu;
			const d = c + 1;
			idx[ o++ ] = a;
			idx[ o++ ] = c;
			idx[ o++ ] = b;
			idx[ o++ ] = b;
			idx[ o++ ] = c;
			idx[ o++ ] = d;
		}
	}
	const geo = new THREE.BufferGeometry();
	geo.setAttribute( 'position', new THREE.BufferAttribute( pos, 3 ) );
	geo.setIndex( new THREE.BufferAttribute( idx, 1 ) );
	return { geo, rows, step: [ ( 1 + 2 * ext ) / cols, 1 / rows ] };
}

const smooth01 = ( a, b, x ) => {
	const t = Math.min( 1, Math.max( 0, ( x - a ) / ( b - a ) ) );
	return t * t * ( 3 - 2 * t );
};

/**
 * @param {object} THREE
 * @param {{depth:object, palette:object, cfg:object, cut:object, rim:object, framing:object, grid:number}} opts
 *   depth: kết quả createDepth; palette: toVectors( derivePalette ) (sRGB); cfg / cut / rim / framing: CONFIG.plate / cut / rim / framing
 *   (đọc trực tiếp mỗi khung); grid: số segment ngang (tầng chất lượng).
 */
export function createPlate( THREE, { depth, palette, cfg, cut, rim, framing, grid } ) {
	const uniforms = {
		...depth.uniforms,
		uPhoto: { value: depth.photo },
		uDepthTexel: { value: new THREE.Vector2( 1 / depth.depthSize.width, 1 / depth.depthSize.height ) },
		uRefTan: { value: new THREE.Vector2( 1, 1 ) },
		uFitScale: { value: 1 },
		uNdcHorizon: { value: 0 },
		uHorizonV: { value: 1 - cfg.horizonUV },
		uNear: { value: cfg.near },
		uFar: { value: cfg.far },
		uStep: { value: new THREE.Vector2() },
		uViewport: { value: new THREE.Vector2( 1, 1 ) },
		uSkyLumLow: { value: cut.lumLow },
		uSkyLumHigh: { value: cut.lumHigh },
		uCutY: { value: cut.bandY },
		uCutBand: { value: 0.025 },
		uStretchLo: { value: cfg.stretchLo },
		uStretchHi: { value: cfg.stretchHi },
		uFog: { value: palette.fog.clone() },
		uRidge: { value: palette.ridgeBase.clone() },
		uRimColor: { value: palette.rim.clone() },
		uSkyLow: { value: palette.golden.clone() },
		uAerial: { value: cfg.aerial },
		uHaze: { value: cfg.haze },
		uMixNear: { value: cfg.mixNear },
		uMixFar: { value: cfg.mixFar },
		uGain: { value: cfg.gain },
		uRimLo: { value: rim.lo },
		uRimHi: { value: rim.hi },
		uRimFalloff: { value: rim.falloff },
		uRimAmount: { value: 0 },
		uSunNdc: { value: new THREE.Vector2() },
		uDebug: { value: 0 },
	};
	const core = new THREE.ShaderMaterial( { uniforms, vertexShader: VS, fragmentShader: FS, defines: { CORE: 1 } } );
	const fringe = new THREE.ShaderMaterial( { uniforms, vertexShader: VS, fragmentShader: FS, transparent: true, depthWrite: false } );
	core.name = 'plate-core';
	fringe.name = 'plate-fringe';

	const group = new THREE.Group();
	group.name = 'plate';
	let built = null;
	let coreMesh = null;
	let fringeMesh = null;

	function setGrid( cols ) {
		if ( built && built.cols === cols && built.ext === cfg.extendU && built.skirt === cfg.skirt ) {
			return;
		}
		if ( built ) {
			built.geo.dispose();
			group.remove( coreMesh, fringeMesh );
		}
		const g = buildGrid( THREE, cols, cfg.extendU, cfg.skirt );
		built = { ...g, cols, ext: cfg.extendU, skirt: cfg.skirt };
		uniforms.uStep.value.set( g.step[ 0 ], g.step[ 1 ] );
		coreMesh = new THREE.Mesh( g.geo, core );
		fringeMesh = new THREE.Mesh( g.geo, fringe );
		for ( const m of [ coreMesh, fringeMesh ] ) {
			m.frustumCulled = false;
		}
		coreMesh.renderOrder = 1;
		fringeMesh.renderOrder = 2;
		group.add( coreMesh, fringeMesh );
	}
	setGrid( grid );

	const layoutInfo = {
		horizonY: 0.5,
		imageTop: 0,
		imageBottom: 1,
		fitScale: 1,
		ndcHorizon: 0,
		refTanX: 1,
		refTanY: 1,
		/** NDC y (khung tham chiếu) của một hàng ảnh tính từ mép trên (0) tới mép dưới (1). */
		ndcYOfRow( row ) {
			return this.ndcHorizon + ( 1 - row - ( 1 - cfg.horizonUV ) ) * this.fitScale;
		},
	};

	const occlusionUniforms = {};
	for ( const k of [ 'uDepthTex', 'uLumNear', 'uLumFar', 'uDepthGamma', 'uDepthIsMap', 'uRefTan', 'uFitScale', 'uNdcHorizon', 'uHorizonV', 'uNear', 'uFar' ] ) {
		occlusionUniforms[ k ] = uniforms[ k ];
	}

	return {
		group,
		uniforms,
		/** Uniform cho OCCLUSION_GLSL (cùng đối tượng với lưới). */
		occlusionUniforms,
		setGrid,
		get layout() {
			return layoutInfo;
		},
		/**
		 * Đóng khung (5.2, fitTextureToViewport): ảnh phủ đúng chiều rộng khung, đường chân trời (horizonUV) ở framing.horizon* theo tỉ lệ khung;
		 * phần dưới tràn khỏi khung. Gọi khi đổi kích thước / hướng màn hình (chỉ đổi uniform).
		 * @param {number} width  CSS px.
		 * @param {number} height CSS px.
		 * @param {number} fovDeg FOV dọc của camera (camera tham chiếu dùng cùng FOV).
		 * @param {{x:number, y:number}} buffer Kích thước drawing buffer (px thật).
		 */
		layout( width, height, fovDeg, buffer ) {
			const va = width / Math.max( 1, height );
			const fit = ( 2 * va ) / depth.aspect;
			const hY = framing.horizonPortrait + ( framing.horizonLandscape - framing.horizonPortrait ) * smooth01( 0.7, 1.4, va );
			const ndcH = 1 - 2 * hY;
			const tanY = Math.tan( ( fovDeg * Math.PI ) / 360 );
			uniforms.uRefTan.value.set( va * tanY, tanY );
			uniforms.uFitScale.value = fit;
			uniforms.uNdcHorizon.value = ndcH;
			uniforms.uViewport.value.set( buffer.x, buffer.y );
			const hv = 1 - cfg.horizonUV;
			layoutInfo.horizonY = hY;
			layoutInfo.imageTop = ( 1 - ( ndcH + ( 1 - hv ) * fit ) ) / 2;
			layoutInfo.imageBottom = ( 1 - ( ndcH - hv * fit ) ) / 2;
			layoutInfo.fitScale = fit;
			layoutInfo.ndcHorizon = ndcH;
			layoutInfo.refTanX = va * tanY;
			layoutInfo.refTanY = tanY;
			return layoutInfo;
		},
		/**
		 * Mỗi khung: đồng bộ núm (config) và giá trị của keyframe.
		 * @param {{sunNdc:{x:number,y:number}, rimIntensity:number, skyMix:number, fogDensity:number, cutBand:number}} f cutBand: uBand
		 *   theo độ cao mặt trời (CONFIG.rise.band).
		 */
		update( f ) {
			// Rạng đông: rặng xa ngả màu trời ấm nhiều hơn; về toàn quang giảm còn 60%.
			uniforms.uAerial.value = cfg.aerial * ( 1 - 0.4 * ( f.skyMix || 0 ) );
			uniforms.uHaze.value = cfg.haze * ( f.fogDensity ?? 1 );
			uniforms.uHorizonV.value = 1 - cfg.horizonUV;
			uniforms.uNear.value = cfg.near;
			uniforms.uFar.value = cfg.far;
			uniforms.uSkyLumLow.value = cut.lumLow;
			uniforms.uSkyLumHigh.value = Math.max( cut.lumHigh, cut.lumLow + 1e-3 );
			uniforms.uCutY.value = cut.bandY;
			uniforms.uCutBand.value = Math.max( f.cutBand ?? 0.025, 1e-3 );
			uniforms.uStretchLo.value = cfg.stretchLo;
			uniforms.uStretchHi.value = Math.max( cfg.stretchHi, cfg.stretchLo + 1e-3 );
			uniforms.uMixNear.value = cfg.mixNear;
			uniforms.uMixFar.value = cfg.mixFar;
			uniforms.uGain.value = cfg.gain;
			uniforms.uRimLo.value = rim.lo;
			uniforms.uRimHi.value = Math.max( rim.hi, rim.lo + 1e-3 );
			uniforms.uRimFalloff.value = rim.falloff;
			uniforms.uRimAmount.value = rim.strength * f.rimIntensity;
			uniforms.uSunNdc.value.set( f.sunNdc.x, f.sunNdc.y );
		},
		setColors( pal ) {
			uniforms.uFog.value.copy( pal.fog );
			uniforms.uRidge.value.copy( pal.ridgeBase );
			uniforms.uRimColor.value.copy( pal.rim );
			uniforms.uSkyLow.value.copy( pal.golden );
		},
		setDebug( name ) {
			uniforms.uDebug.value = PLATE_DEBUG[ name ] || 0;
		},
		dispose() {
			if ( built ) {
				built.geo.dispose();
			}
			core.dispose();
			fringe.dispose();
		},
	};
}
