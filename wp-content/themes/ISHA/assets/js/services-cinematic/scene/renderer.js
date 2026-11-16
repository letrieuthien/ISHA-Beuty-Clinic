/**
 * Renderer WebGL của cảnh v3 (docs/isha-lookdev-prompt-v3.md mục 4 và phụ lục).
 *
 * - outputColorSpace sRGB; tone mapping Neutral, A/B với AgX qua debug. Không dùng ACESFilmic (làm lệch hue của vàng).
 * - DPR có trần (tầng chất lượng: desktop 1.75, điện thoại 1.5; governor hạ dần).
 * - resize() bỏ qua khi kích thước và DPR không đổi: gán canvas.width (renderer.setSize) luôn xoá hình kể cả khi giá trị như cũ, gây chớp
 *   đen trên điện thoại mỗi lần thanh địa chỉ ẩn / hiện (bài học bản 7b). Trả true khi đổi thật để nơi gọi vẽ lại ngay trong cùng lượt.
 * - rtType: kiểu render target cho dữ liệu cần độ chính xác (bản đồ độ sâu): HalfFloat khi GPU ghi được float, nếu không thì 8 bit.
 */
const TONE = Object.freeze( { Neutral: 'NeutralToneMapping', AgX: 'AgXToneMapping' } );

export function createRenderer( THREE, { canvas, dprCap = 1.75, toneMapping = 'Neutral' } ) {
	const renderer = new THREE.WebGLRenderer( {
		canvas,
		antialias: false,
		alpha: false,
		stencil: false,
		powerPreference: 'high-performance',
	} );
	renderer.outputColorSpace = THREE.SRGBColorSpace;
	const ext = renderer.extensions;
	const rtType = ext.has( 'EXT_color_buffer_float' ) || ext.has( 'EXT_color_buffer_half_float' ) ? THREE.HalfFloatType : THREE.UnsignedByteType;

	let cap = dprCap;
	let w = 0;
	let h = 0;
	let dpr = 0;

	const api = {
		renderer,
		rtType,
		get size() {
			return { width: w, height: h, dpr };
		},
		setToneMapping( name ) {
			renderer.toneMapping = THREE[ TONE[ name ] || TONE.Neutral ];
			renderer.toneMappingExposure = 1;
		},
		setDprCap( c ) {
			cap = c;
			return api.resize( w, h );
		},
		/**
		 * @param {number} width  CSS px.
		 * @param {number} height CSS px.
		 * @return {boolean} true khi kích thước hoặc DPR đổi thật (canvas đã bị xoá: vẽ lại ngay).
		 */
		resize( width, height ) {
			const d = Math.min( ( typeof window !== 'undefined' && window.devicePixelRatio ) || 1, cap );
			const nw = Math.max( 1, Math.round( width ) );
			const nh = Math.max( 1, Math.round( height ) );
			if ( nw === w && nh === h && d === dpr ) {
				return false;
			}
			w = nw;
			h = nh;
			dpr = d;
			renderer.setPixelRatio( d );
			renderer.setSize( w, h, false );
			return true;
		},
		dispose() {
			renderer.dispose();
		},
	};
	api.setToneMapping( toneMapping );
	return api;
}
