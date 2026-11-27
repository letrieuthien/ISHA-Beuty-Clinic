# PROMPT CHO CLAUDE CODE — v3
## Section "Bình minh lan tỏa": một ảnh thật + depth displacement + 2.5D camera

> **v3 thay thế v1 và v2.** Khác biệt cốt lõi: thay vì 5 lớp ảnh tách tay (v2) hay địa hình procedural (v1), cảnh dựng từ **một tấm ảnh 4K duy nhất**, với bản đồ độ sâu **suy từ độ sáng của chính ảnh đó**. Mặt trời, trời, mây, sương, bụi, cỏ lau đều do WebGL vẽ đè lên.
>
> Model: Opus 5.5 hoặc Fable 5.2. Effort **xhigh** cho bước 1–4, **high** cho bước 5–9.

---

# 0. ĐỌC TRƯỚC TIÊN

Trước khi viết dòng code nào, đọc hết `tools/refs/`:

```
tools/refs/
  plates/   scene-source.(png|jpg|webp) (bắt buộc), depth.png (tùy chọn), clouds.(jpg|png) (tùy chọn)
  mood/     mood-01.jpg … mood-08.jpg
  current/  before-desktop-p00/p50/p100.png, before-mobile-p50.png
  NOTES.md  nguồn ảnh, giấy phép, và thông số kỹ thuật của repo
  bang-ma-mau.md
```

- **`plates/scene-source.*`** — nguyên liệu duy nhất cho địa hình. Dò theo thứ tự `.png`, `.webp`, `.jpg` và dùng file đầu tiên tìm thấy; PNG là bản không mất chất nên được ưu tiên. **Không sinh địa hình procedural trong bất kỳ trường hợp nào.** Thiếu file này thì dừng lại và hỏi.
- **`mood/`** — tiêu chuẩn thẩm mỹ. Sau mỗi bước ở mục 18, tự so sánh ảnh render của mình với moodboard và **nói rõ còn lệch ở đâu**. Đây thay cho từ "chân thực", vốn vô nghĩa khi bạn không nhìn thấy output của chính mình.
- **`current/`** — hiện trạng cần sửa: núi là khối Gaussian trơn, không bóng đổ, bố cục đối xứng, mặt trời hình bánh răng, ba card hiện cùng lúc.
- **`NOTES.md`** — slug taxonomy, slug CPT, tên ACF field, phiên bản WP/PHP, `add_image_size` đã đăng ký, chuỗi font. **Không đoán những thứ này.**
- **`bang-ma-mau.md`** — nguồn chân lý duy nhất về màu.

---

# 1. PHẠM VI

## GIỮ NGUYÊN, KHÔNG SỬA

- Toàn bộ PHP: `inc/services-cinematic.php`, `template-parts/services-cinematic.php`, hợp đồng dữ liệu JSON, transient cache, enqueue có điều kiện, WordPress Script Modules API.
- Markup SSR và nội dung HTML thật (SEO).
- `store.js`, `scroll.js` (sticky + ScrollTrigger + Lenis + bàn phím), `a11y.js`, `tier.js`, `debug.js`.
- `card-machine.js` (reducer). Chỉ sửa nếu test chỉ ra lỗi.
- Nhánh `prefers-reduced-motion` và no-JS.
- Ràng buộc: PHP 7.4+, CSS và JS thuần, **không có bước build**, thư viện nạp bằng ES module qua Script Modules API.

## THAY THẾ HOÀN TOÀN

- Toàn bộ `scene/*`. Xóa `terrain.js`, `clouds.js`, `vegetation.js`, `rail.js`.
- `fallback/lineart2d.js`.
- Phần View của card (`card-view.js`) và CSS card.
- Tập trường dữ liệu của `stops.js` (giữ chữ ký hàm thuần và bộ test).

## DỌN DẸP TRƯỚC KHI BẮT ĐẦU

Theme không có bước build nên **không có tree-shaking**: mọi file còn sót lại vẫn được tải và vẫn gây nhiễu. Làm sạch ngay từ đầu, đừng để đến cuối.

Người dùng đã gắn tag `scene-v1-final` và tạo nhánh `feat/scene-v3` để đối chiếu. Việc của bạn:

**Xóa hẳn:**

```
assets/js/services-cinematic/scene/                       (toàn bộ thư mục)
assets/js/services-cinematic/fallback/lineart2d.js
```

**Bốn chỗ code thừa nằm ngoài thư mục `scene/`, rất dễ bỏ sót:**

1. **Đăng ký script module cho `Sky.js`** trong `inc/services-cinematic.php`. v3 bỏ Preetham, nên xóa dòng `wp_register_script_module('three/addons/objects/Sky.js', …)`, gỡ nó khỏi mảng dependency, và xóa file vendor tương ứng. Để lại thì nó vẫn được đưa vào importmap và vẫn tải về.
2. **Preset tier của cảnh cũ** trong `config.js`: mật độ cỏ instanced, độ phân giải terrain, số billboard mây, cờ bật raymarch, tham số god rays hậu kỳ.
3. **CSS mồ côi** trong `services-cinematic.css`: `.isha-svc__rays`, các class nền tối của card cũ, style của fallback line-art.
4. **Texture hoặc ảnh sinh ra cho cảnh cũ** trong `assets/img/`, nếu có.

**Viết `tools/deadcode.mjs`**, chạy hai phép quét và in báo cáo:

- File `.js` trong `assets/js/services-cinematic/` không được bất kỳ file `.js` hay `.php` nào import.
- Khóa viết hoa trong `config.js` không được đọc ở bất kỳ file nào khác ngoài chính nó.

Chạy script này ở cuối bước 0 và một lần nữa ở cuối bước 9. Cả hai lần phải sạch.

Commit riêng: `chore: xóa scene v1 trước khi dựng v3`.

---

# 2. HƯỚNG NGHỆ THUẬT

**Núi nhiều lớp chìm trong sương, tối giản Á Đông, quiet luxury.**

- Trời chiếm 55–65% khung hình. Quiet luxury là tiết chế, không phải mật độ chi tiết.
- Bảng màu chỉ ba tông: sương trắng ngà, núi xám ấm ngả xanh rất nhẹ, mặt trời vàng.
- Chuyển động chậm đến mức gần như thiền.
- Tiền cảnh là bóng cỏ lau tối, mờ nhòe, chỉ ở mép dưới.

Sương che giấu mọi giới hạn kỹ thuật. Đó là lý do hướng này đạt trần chất lượng cao hơn hẳn hướng photoreal cũ.

---

# 3. PHẦN CỐT LÕI — DỰNG ĐỘ SÂU VÀ LƯỚI TỪ MỘT ẢNH

Đây là phần quyết định cả phương án. Làm sai chỗ này thì mọi thứ phía sau vô nghĩa.

## 3.1 Suy bản đồ độ sâu từ độ sáng

Với ảnh núi chìm sương, **độ sáng tỉ lệ thuận với khoảng cách**: rặng càng xa thì càng nhiều sương chắn giữa nên càng sáng. Khai thác quan hệ này:

```glsl
float lum = dot(photo.rgb, vec3(0.2126, 0.7152, 0.0722));
float d   = smoothstep(uLumNear, uLumFar, lum);   // tối = gần (0), sáng = xa (1)
d = pow(d, uDepthGamma);                           // chỉnh phân bố
```

`uLumNear`, `uLumFar`, `uDepthGamma` nằm trong `config.js` và **bắt buộc có trong lil-gui** — đây là ba núm quan trọng nhất của cả section.

**Bắt buộc làm mượt bản đồ độ sâu.** Chi tiết nhỏ của ảnh (vân đá, nhiễu) sẽ biến thành mặt gồ ghề nếu không khử. Chạy một **pass tiền xử lý một lần lúc load**, không phải mỗi frame:

1. Downsample ảnh xuống 1/4.
2. Gaussian blur tách trục, bán kính khoảng **1.5% chiều rộng ảnh**.
3. Ghi kết quả vào một `WebGLRenderTarget` dùng làm `depthTexture`.

## 3.2 Ưu tiên `depth.png` nếu có

Nếu `assets/img/scene/depth.webp` tồn tại thì dùng nó thay cho bước 3.1, vẫn blur nhẹ. Code phải tự dò và log rõ nguồn độ sâu đang dùng.

## 3.3 Dựng lưới bằng unprojection — làm đúng chỗ này

Không dùng `PlaneGeometry` rồi đẩy thẳng theo trục Z. Làm vậy ảnh sẽ méo.

Cách đúng: **bắn tia từ một camera tham chiếu và đặt đỉnh dọc theo tia**, sao cho nhìn từ vị trí tham chiếu thì hình ảnh trùng khít ảnh gốc phẳng; chỉ khi camera rời chỗ mới sinh parallax.

```
cho mỗi đỉnh (u, v) trên lưới:
  ndc   = vec2(u, v) * 2.0 - 1.0
  ray   = unproject(ndc, refCamera)        // hướng tia từ camera tham chiếu
  depth = texture(depthTexture, vec2(u,v)).r
  dist  = mix(uNearDist, uFarDist, depth)
  pos   = refCamera.position + normalize(ray) * dist
```

- `refCamera`: `PerspectiveCamera`, `fov = 38`, đặt tại `z = 0`, nhìn về `-Z`.
- `uNearDist = 700`, `uFarDist = 1300` (đơn vị scene). Chỉnh qua GUI.
- Lưới: **320 × 180 segment** trên desktop, 160 × 90 trên mobile. Dựng một lần lúc load trên CPU, hoặc trong vertex shader nếu lấy depth bằng vertex texture fetch.
- Lưới chỉ phủ **phần dưới đường chân trời**, không phủ cả khung.

## 3.4 Cắt trời theo độ sáng, không theo đường thẳng

Toàn bộ phần trời của ảnh bị **loại bỏ** và thay bằng gradient shader, để trời chuyển sáng được theo hành trình mặt trời và để mây trôi được.

Đừng cắt bằng một đường ngang cố định — sẽ lộ vệt. Cắt theo **chính độ sáng**, vì trong ảnh sương thì trời là thứ sáng nhất, nên đường cắt sẽ tự bám theo đường chân núi:

```glsl
float skyMask = smoothstep(uSkyLumLow, uSkyLumHigh, lum);
float bandMask = smoothstep(uHorizonY + uBand, uHorizonY - uBand, vUv.y);
alpha = (1.0 - skyMask) * bandMask;
```

`uHorizonY` khoảng 0.26 (tính từ trên), `uBand` khoảng 0.06. Cả bốn tham số vào GUI.

## 3.5 Chống kéo giãn ở biên độ sâu

Khi camera dịch, vùng bị rặng gần che sẽ lộ ra mà không có dữ liệu ảnh, sinh ra tam giác kéo giãn. Khử bằng cách **làm mờ dần những pixel nằm trên sườn dốc của bản đồ độ sâu**:

```glsl
float grad = length(vec2(dFdx(vDepth), dFdy(vDepth)));
alpha *= 1.0 - smoothstep(uGradLo, uGradHi, grad);
```

Với ảnh sương mép mềm, cách này gần như xóa sạch artifact. Kèm theo: **giới hạn biên độ dolly** (mục 9).

---

# 4. KIẾN TRÚC

```
assets/js/services-cinematic/scene/
  renderer.js      // WebGLRenderer, NeutralToneMapping, DPR cap, sRGB
  depth.js         // tiền xử lý depth (3.1–3.2), render target, dò depth.webp
  terrainMesh.js   // unprojection mesh (3.3), cắt trời (3.4), chống giãn (3.5), tint + rim
  sky.js           // gradient shader 4 chặng + dither + lớp mây
  sun.js           // đĩa + quầng + vệt ngang + ánh sáng thể tích soft glow
  mist.js          // 3–5 dải sương fbm trôi ngang
  dust.js          // hệ hạt bụi sáng
  reeds.js         // cỏ lau instanced tiền cảnh
  birds.js         // đàn chim, xuất hiện một lần
  idle.js          // chuyển động độc lập với scroll
  post.js          // grain + vignette (mọi tier), bloom (desktop)
  camera.js        // dolly theo progress
  lifecycle.js     // IntersectionObserver, visibility, context loss
```

Thứ tự độ sâu trong scene, từ xa tới gần:

| | z | Ghi chú |
|---|---|---|
| sky | −1600 | quad phủ kín, luôn ở sau cùng |
| terrainMesh | −1300 … −700 | trải theo depth |
| sun + volumetric | −1050 | nằm giữa dải địa hình, nên **bị rặng gần che một phần** |
| mist (3–5 dải) | −1200, −1000, −850, −720 | xen giữa các bậc độ sâu |
| birds | −900 | |
| reeds | −80 | hoàn toàn do WebGL vẽ |
| dust | −40 … −200 | |

Việc đặt mặt trời ở `z = −1050` là chi tiết quan trọng: lưới địa hình có đỉnh ở gần hơn sẽ tự che nó, tạo khoảnh khắc "nhô lên từ sau núi" mà không cần mask thủ công.

---

# 5. BỐ CỤC VÀ ĐÓNG KHUNG ẢNH

## 5.1 Ảnh nguồn đã được chuẩn bị thế nào

Người dùng đã **lật ngang** ảnh gốc, để hướng sáng trong ảnh đến từ bên phải cho khớp với mặt trời do code vẽ. Đã **cắt bớt phần trời**, chỉ chừa lại một dải 8–12% phía trên rặng xa nhất để cơ chế cắt trời ở mục 3.4 có chỗ chuyển tiếp. **Phần đáy giữ nguyên.** Ảnh xuất ở 4096px ngang (nằm trong ngưỡng `MAX_TEXTURE_SIZE` của mọi GPU di động), tỉ lệ khoảng 1,9:1.

Ảnh **không được crop về đúng tỉ lệ khung hình**. Việc đóng khung là của code.

## 5.2 Đóng khung — fit theo chiều rộng, KHÔNG cover-crop

Quy tắc chung cho mọi tỉ lệ màn hình:

- **Ảnh luôn phủ hết chiều rộng viewport.** Không bao giờ cắt bớt hai bên.
- Neo theo **đường chân trời**, tức silhouette của rặng xa nhất. Đo vị trí của nó trong texture một lần, đặt vào config thành `uHorizonUV`.
- **Phần đáy ảnh được phép tràn xuống dưới viewport.** Đây là chủ ý: nó đẩy những chi tiết sắc nét ở mép dưới ảnh ra ngoài vùng nhìn thấy, đúng chỗ dễ bị xé nhất khi lưới đẩy theo độ sâu.

| | Đường chân trời đặt ở | Trời chiếm |
|---|---|---|
| Desktop 16:9 | 45–50% chiều cao viewport | ~47% |
| Màn hẹp / mobile dọc | 55–60% chiều cao viewport | ~57% |

Màn càng hẹp thì càng nhiều trời. Đây không phải nhân nhượng kỹ thuật mà là chủ ý: trời do shader vẽ nên vô hạn và sạch, còn quiet luxury thì càng nhiều khoảng trống càng tốt. Mobile cũng là nơi cần chừa chỗ cho card ở đáy.

`fitTextureToViewport()` làm đúng việc này: tính scale theo chiều rộng, rồi dịch dọc sao cho `uHorizonUV` rơi đúng tỉ lệ trên. Gọi lại khi resize và khi đổi hướng màn hình. **Không dùng logic kiểu `object-fit: cover`** — cover-crop trên màn dọc sẽ cắt mất tới 75% chiều rộng và làm mất toàn bộ phần phân tầng đẹp nhất của ảnh.

Lưới địa hình phải dựng rộng hơn viewport ở phía dưới để phần tràn vẫn có hình.

## 5.3 Khối núi neo

Ảnh nguồn **không có một đỉnh chủ đạo** — nó là toàn cảnh một dãy núi trải rộng. Vì vậy phải **chỉ định** một khối làm điểm neo cho câu chuyện: khối núi lớn hình tam giác, có hình khối rõ nhất, sau khi lật nằm ở **x ≈ 0.65** (đã đo trên ảnh thật).

Đặt vào config thành `uAnchorX = 0.65`. Đo lại nếu người dùng thay ảnh. Từ đó:

- **Mặt trời mọc trong khe thung lũng bên trái khối neo**, rồi leo dọc sườn trái của nó và vượt lên trên.
- **Camera dolly lệch nhẹ về phía khối neo**, không đi thẳng vào tâm khung.

Lưu ý hình học: với đường chân trời đặt ở 47% viewport và ảnh fit theo chiều rộng, **đỉnh của khối neo rơi vào khoảng 64% chiều cao viewport**. Mặt trời ở stop đầu phải nằm **thấp hơn** mốc đó, nếu không sẽ mất câu chuyện "nhô lên từ chân núi".

## 5.4 Vị trí các thành phần

Giá trị khởi đầu, chỉnh qua lil-gui. Tính theo `uAnchorX = 0.65` và đỉnh khối neo ở 64% chiều cao viewport.

| Thành phần | Desktop 16:9 | Mobile dọc |
|---|---|---|
| Mặt trời, stop đầu | x = **52%**, y = **74%** | x = 50%, y = 68% |
| Mặt trời, stop cuối | x = **58%**, y = **18%** | x = 56%, y = 15% |
| Card | lệch trái, tâm x = **27%**, y = 52% | đáy giữa, `bottom: 9svh`, width 88vw |
| Tiêu đề danh mục | trên cùng **bên trái** | trên cùng giữa |

Ở stop đầu, mặt trời **phải bị một rặng gần che một phần**. Nếu không xảy ra, chỉnh `sunZ` và `sunY` qua GUI cho tới khi đạt — đây là khoảnh khắc kể chuyện quan trọng nhất của cả section.

**Nghiệm thu bố cục:** khối neo không nằm trong x ∈ [45%, 55%]; ảnh phủ kín chiều rộng viewport ở mọi tỉ lệ màn hình, không có dải trống hai bên.

---

# 6. ĐIỂM DỪNG MẶT TRỜI — `stops.js`

Giữ chữ ký hàm thuần và bộ test. Tập trường:

```js
computeStops(N, cfg) → Object.freeze([{
  sunX, sunY,        // % khung hình
  sunScale,          // 1.00 → 0.78
  sunIntensity,      // 0.75 → 1.00
  rimIntensity,      // 1.00 → 0.35
  volumetric,        // 0.90 → 0.32
  fogDensity,        // 1.00 → 0.62
  skyMix,            // vị trí trong gradient trời
  cloudOpacity,      // 0.18 → 0.30
  dustIntensity,     // 0.45 → 1.00
  exposure,          // 1.15 → 1.00
  grain,             // 0.030 → 0.022
  label
}])
```

`t_i = N === 1 ? 1 : i/(N-1)`, qua `easeOutSine`.
Nhãn: `["Rạng đông","Bình minh","Ban mai","Nắng sớm","Nắng vàng","Toàn quang"]`, chọn theo `round(t_i × (len-1))`.

`sampleStops(stops, p, N)` giữ plateau `hold = 0.35`, ghi vào out-param, không cấp phát object mỗi frame.
`activeIndex` giữ hysteresis 0.02.

**Test:** giữ toàn bộ test cũ, thêm — `sunY` giảm đơn điệu; `rimIntensity` và `volumetric` giảm đơn điệu; `dustIntensity` tăng đơn điệu; mọi trường hữu hạn với N = 1..6; nhãn đúng với N = 3,4,5,6.

---

# 7. MÀU

Giữ `tokens.js`: đọc bằng `getComputedStyle(...).getPropertyValue()` rồi `new THREE.Color().setStyle()`.

```
fogColor   = mix(--isha-daylight, --isha-skin-soft, 0.35)
greenHint  = hsl(hue 112°, sat = goldSat × 0.28, lightness tham chiếu --isha-dawn)
ridgeBase  = mix(--isha-dawn, greenHint, 0.40)
```

**Tô địa hình.** Khử màu ảnh gốc thành `gray`, rồi tô lại theo độ sâu — liên tục, không chia bậc:

```glsl
float depthMix = mix(0.70, 0.12, d);        // gần = 0.70 (đậm), xa = 0.12 (tan vào sương)
vec3 base = mix(fogColor, ridgeBase, depthMix);
vec3 col  = base * gray;
```

Giữ nguyên `gray` làm hệ số nhân là điều kiện để AO và khe núi của ảnh thật còn lại.

**Viền sáng (rim).** Ở pixel nằm trên sườn dốc của bản đồ độ sâu **và** hướng về phía mặt trời, cộng `mix(--isha-golden, #BF953F, 0.4) × rimIntensity × falloff`. Dùng lại `grad` đã tính ở mục 3.5, nhân với tích vô hướng giữa hướng gradient và hướng tới mặt trời trên màn hình. Đây là thứ làm rặng núi "sống".

**Trời.** Gradient 4 chặng từ trên xuống: `--isha-daylight` → `--isha-skin-soft` → `mix(--isha-skin, --isha-golden, 0.5)` → `--isha-golden` đậm hơn quanh mặt trời. Chặng dịch theo `skyMix`.
**Bắt buộc dither**: cộng nhiễu biên độ `1/255` vào màu cuối của sky shader. Ba dòng GLSL, hết banding — không có lý do gì phải dùng ảnh bầu trời.

**Mặt trời.** Lõi `mix(--isha-golden, #FFF, 0.75)`, quầng `--isha-golden`, tia `#FCF6BA` alpha rất thấp.
**Sương và bụi.** `fogColor`.

Mọi hệ số vào `config.js` và lil-gui. **Không thêm hex mới vào CSS.** Bốn mã kim loại `#FCF6BA #BF953F #AA771C #B38728` chỉ dùng cho tia, viền mảnh và viền card.

---

# 8. TRỜI

Hai lớp:

1. **Gradient shader** như mục 7, có dither. Đây là nền, điều khiển hoàn toàn được.
2. **`clouds.webp` phủ lên** nếu file tồn tại: `blending: AdditiveBlending` nhẹ hoặc chế độ screen, opacity theo `cloudOpacity`, **tô màu theo stop** giống địa hình, trôi ngang 0.003 đơn vị/giây. Nếu không có file, sinh mây bằng fbm 2D domain-warp trong shader — chấp nhận được, chỉ kém tự nhiên hơn chút.

---

# 9. CAMERA VÀ PARALLAX — `camera.js`

Camera dolly từ vị trí tham chiếu về phía trước, đồng thời lệch rất nhẹ về phía khối neo (mục 5.3):

```
const e = easeInOutSine(smoothed);
camera.position.z = lerp(0, -uDollyMax, e);
camera.position.x = lerp(0, uAnchorDrift, e);   // uAnchorDrift ≈ 6, dấu hướng về uAnchorX
```

`uDollyMax = 95` làm giá trị khởi đầu. **Ràng buộc cứng: không vượt quá mức khiến phần gần nhất của ảnh phóng to hơn 18%.** Viết một assert tính tỉ lệ phóng tại `d = 0` và cảnh báo trong console nếu vượt. Đây là ranh giới giữa "parallax đẹp" và "xé hình".

Thêm parallax chuột cực nhẹ: dịch camera ±0.35 đơn vị theo X/Y, damp riêng, chỉ desktop.

Damping: `smoothed += (progress − smoothed) × (1 − exp(−k·dt))`, k = 5 desktop, 8 mobile.

Resize: cập nhật `camera.aspect`, gọi lại `fitTextureToViewport()`, khung dọc mở FOV thêm 8–12°. **Không dựng lại lưới** trừ khi đổi tier.

---

# 10. MẶT TRỜI VÀ ÁNH SÁNG THỂ TÍCH

Bỏ hoàn toàn sprite tia hình bánh răng và hai vòng lens-flare tròn của bản cũ.

- **Đĩa:** tròn mềm, bán kính 2.2% chiều cao khung, lõi gần trắng, viền tan vào quầng.
- **Quầng:** radial gradient rộng gấp 6 lần đĩa, alpha thấp.
- **Vệt ngang kiểu anamorphic:** mảnh, dài 18% chiều rộng, alpha 0.12. Chi tiết nhỏ này tạo cảm giác điện ảnh nhiều nhất.
- **Ánh sáng thể tích:** 3–5 quad hình nêm rất mờ tỏa ra từ mặt trời, `AdditiveBlending`, `depthWrite: false`. Texture là gradient mềm sinh bằng `OffscreenCanvas` lúc load, không cần file. Opacity theo `volumetric`, góc tỏa lệch nhau, mỗi quad xoay rất chậm với tốc độ khác nhau. Đặt tại `z = −1020`, tức hơi trước mặt trời nhưng vẫn sau các đỉnh gần.

Cách này nhẹ hơn god rays hậu kỳ, chạy được trên mobile, và cho ra màn lan tỏa dịu nhẹ đúng tinh thần quiet luxury hơn tia sắc nét.

**Khoảnh khắc nhô lên:** ở điểm dừng đầu, mặt trời phải bị đỉnh gần che một phần, chỉ lộ quầng. Việc này xảy ra tự nhiên nhờ depth buffer, không cần mask — chỉ cần `sunZ` nằm giữa dải độ sâu của địa hình. Kiểm tra bằng mắt và chỉnh `sunZ` qua GUI.

---

# 11. SƯƠNG

4 dải desktop, 2 mobile. Mỗi dải là một quad rộng, fragment shader fbm 2D cuộn ngang, alpha mềm hai đầu, đặt xen giữa các bậc độ sâu (mục 4).

Tốc độ trôi 0.004–0.012 đơn vị/giây — **chậm đến mức gần như không thấy chuyển**. Alpha tổng theo `fogDensity`, mỗi dải 0.22–0.48.

Sương phải tiếp tục trôi **cả khi người dùng không cuộn**.

---

# 12. BỤI SÁNG VÀ TẦNG CHUYỂN ĐỘNG ĐỘC LẬP VỚI SCROLL

Bản cũ **đóng băng hoàn toàn khi người dùng dừng cuộn để đọc card**. Đây là phần sửa lỗi đó.

## `dust.js`

- `THREE.Points`, 300 hạt desktop, 120 mobile, phân bố z = −40 … −200.
- Sprite chấm tròn mềm sinh bằng `OffscreenCanvas`, `AdditiveBlending`, `depthWrite: false`.
- Kích thước 1–4px, hạt gần to hơn.
- Trôi lên rất chậm cộng dao động sin ngang, mỗi hạt lệch pha, chu kỳ 8–16 giây.
- **Alpha điều biến theo khoảng cách màn hình tới mặt trời** — hạt gần mặt trời sáng rõ, hạt xa gần như vô hình. Không có điều này thì nó trông như tuyết rơi chứ không như bụi bắt nắng.
- Cường độ tổng theo `dustIntensity`.

## `idle.js`

Chạy liên tục, không phụ thuộc progress:

- **Thở của khung hình:** dao động sin biên độ ±0.15%, chu kỳ 12 giây, áp lên offset camera.
- **Tia sáng thở:** `volumetric × (1 + 0.08·sin(t/8))`.
- **Quầng mặt trời:** co giãn ±3%, chu kỳ 10 giây, lệch pha so với tia.

## `birds.js`

4–5 chấm nhỏ màu `ridgeBase` đậm, bay chậm ngang khung theo đường cong thoải, vỗ cánh bằng cách co giãn chiều cao sprite. **Xuất hiện một lần duy nhất** khi tới điểm dừng thứ ba, mất khoảng 14 giây bay hết khung, không lặp lại.

Tắt toàn bộ mục 12 khi `prefers-reduced-motion`.

---

# 13. CỎ LAU TIỀN CẢNH — `reeds.js`

Hoàn toàn do WebGL vẽ, không lấy từ ảnh. Đây là lý do ảnh gốc không được có vật thể sắc nét ở tiền cảnh.

`InstancedMesh`, 180 blade desktop, 70 mobile, đặt tại `z = −80`, chỉ ở dải 12% dưới cùng của khung. Màu `mix(fogColor, ridgeBase, 0.86)`, tối dần về chân. Đung đưa bằng vertex shader theo world X cộng time, biên độ nhỏ, chu kỳ 6–9 giây. Mép blade mềm bằng alpha để giả mất nét.

Vì nằm ở `z = −80`, lớp này **được tự do dịch nhiều** khi camera dolly, bù lại cho việc dải địa hình bị giới hạn 18%.

---

# 14. HẬU KỲ — `post.js`

**Mọi tier, kể cả mobile:**
- **Grain** 2.2–3.0% theo stop. Gần như miễn phí, và là thứ duy nhất khiến render trông như phim.
- **Vignette** 0.25, mềm.

**Chỉ desktop:**
- **Bloom** threshold cao, chỉ chạm đĩa mặt trời và viền rim.

Không cần DOF runtime — độ mờ theo xa gần đã có sẵn trong ảnh. Không cần god rays hậu kỳ — đã làm bằng soft glow ở mục 10.

Governor runtime **không bao giờ được tắt grain và vignette**.

---

# 15. CARD

## Lỗi phải sửa ngay

Hiện có **ba card hiển thị cùng lúc** (xem `tools/refs/current/`). Sau khi animation "ra" kết thúc, card không active phải `display: none`, không chỉ `opacity: 0`.

Thêm test: ở mọi progress, **đúng một card** có `display` khác `none`.

## Chất liệu — kính sáng, viền ánh kim

```css
.isha-card {
  background: color-mix(in srgb, var(--isha-daylight) 74%, transparent);
  backdrop-filter: blur(16px) saturate(118%);
  -webkit-backdrop-filter: blur(16px) saturate(118%);
  border-radius: 10px;
  box-shadow: 0 24px 60px -24px rgba(28,27,25,0.28);
}
.isha-card::before {
  content: ""; position: absolute; inset: 0;
  border-radius: inherit; padding: 1px;
  background: linear-gradient(135deg, #FCF6BA, #BF953F 28%, #AA771C 52%, #B38728 74%, #FCF6BA);
  -webkit-mask: linear-gradient(#000 0 0) content-box, linear-gradient(#000 0 0);
  -webkit-mask-composite: xor; mask-composite: exclude;
  pointer-events: none;
}
```

Bốn mã kim loại này đúng là phần `bang-ma-mau.md` dành cho "viền thẻ thành viên, khung ảnh cao cấp". **Không dùng chúng cho chữ hay nền lớn.**

Thêm hiệu ứng quét sáng chạy dọc viền **một lần** khi card lật vào: dịch `background-position` trong 0.9s. Không lặp.

## Nội dung

- Tiêu đề Montserrat SemiBold `--isha-shadow`; mô tả Garamond `--isha-shadow`; nhãn nhỏ màu vàng dùng `--isha-gold-ink` (không dùng `--isha-gold`, không đạt AA).
- **Ảnh:** bỏ crop tròn nhỏ. Tỉ lệ **3:2 tràn hết chiều rộng card**, bo góc trên, `object-fit: cover`. Chỉ nạp ảnh của index hiện tại ± 1.
- **Nút CTA** đang xuống hai dòng: `white-space: nowrap`, card rộng 420px desktop, nút `font-size: 13px`, `letter-spacing: 0.06em`. Mobile xếp dọc.
- Kiểm tra AA cho chữ nhỏ trên nền nút `--isha-gold`; không đạt thì nền đổi sang `--isha-gold-ink`.

## Mặt sau card

Vân kẻ dọc vàng bằng `repeating-linear-gradient`, **đường kẻ sắc 1px không blur**, opacity 0.14, dày ở đáy mờ dần lên trên, phủ thêm gradient vàng rất nhạt ở `mix-blend-mode: overlay`. Sắc nét là về cạnh, không phải độ đậm.

## Animation lật

`rotateY` với `perspective: 1200px`, `backface-visibility: hidden`, **biên độ nhỏ**: xoay từ −62° thay vì −90°, cộng `translateY(18px)` và `scale(0.97)`. Vào 0.7s `power3.out`, ra 0.42s `power2.in`, lệch pha 0.14s. Giữ latest-wins, kill timeline đang chạy, đảo chiều khi cuộn ngược, bỏ qua index trung gian khi cuộn nhanh.

**Không animate blur**, chỉ transform và opacity. Trên `(hover: none)` giảm blur còn 10px và tăng độ đục. Có `@supports not (backdrop-filter: blur(1px))` dự phòng nền đục `--isha-skin-soft`.

---

# 16. TIER VÀ FALLBACK

Cảnh mới rất nhẹ. Mọi thiết bị chạy cùng một scene, khác tham số:

| | DPR | Lưới | Dải sương | Hạt bụi | Cỏ lau | Tia thể tích | Bloom | Grain + Vignette |
|---|---|---|---|---|---|---|---|---|
| Desktop | ≤ 1.75 | 320×180 | 4 | 300 | 180 | 5 | Có | **Có** |
| Mobile | ≤ 1.5 | 160×90 | 2 | 120 | 70 | 3 | Không | **Có** |

Giữ `tier.js` và governor. Thứ tự hạ cấp: DPR → mật độ lưới → số dải sương → số hạt bụi → số quad tia → số cỏ lau. **Không bao giờ tắt grain và vignette.**

**MODE_2D:** dùng chính `scene-source.webp` làm ảnh nền tĩnh với `transform: scale` nhẹ theo scroll, gradient trời bằng `<div>`, grain bằng PNG noise lặp ở `mix-blend-mode: overlay`. Không có parallax theo độ sâu nhưng vẫn đúng tông. Xóa `lineart2d.js` cũ.

**Reduced motion:** danh sách tĩnh, nền là `scene-source.webp` đã tô màu sẵn ở p = 0.5 (xuất một lần thành `poster.webp`). Không nạp three/gsap/lenis.

Giữ `lifecycle.js`: `IntersectionObserver` dừng render loop khi ra khỏi viewport, `visibilitychange`, xử lý `webglcontextlost` / `webglcontextrestored`, mất context lần hai thì chuyển MODE_2D.

---

# 17. CÁCH LÀM VIỆC — bắt buộc

Bạn **không nhìn thấy kết quả bạn tạo ra**.

1. Viết `tools/shot.mjs`: dùng Playwright nếu có, render `tools/lookdev.html` ở `p = 0, 0.33, 0.66, 1` ra 4 PNG trong `tools/out/`. Không có Playwright thì in hướng dẫn chụp thủ công.
2. **Làm trước trên `tools/lookdev.html`** — một trang độc lập, không WordPress, nạp cùng các module scene, lil-gui đầy đủ. Chỉ tích hợp vào theme sau khi hình ảnh được duyệt.
3. **Sau mỗi bước ở mục 18, DỪNG LẠI.** Chạy `shot.mjs`, tự so sánh với `tools/refs/mood/`, nói rõ còn lệch ở đâu, rồi báo cáo kèm đường dẫn ảnh. Không làm liền hai bước.
4. Thêm **chế độ xem bản đồ độ sâu** trong GUI (hiển thị `depthTexture` thang xám toàn màn hình). Đây là công cụ gỡ lỗi quan trọng nhất của v3 — mọi lỗi hình đều truy về được bản đồ độ sâu.
5. Mọi hằng số thị giác vào lil-gui, kèm nút "Copy config JSON".
6. Không in code dài vào chat. Viết thẳng vào file trong repo.

---

# 18. THỨ TỰ THỰC HIỆN

0. **Dọn dẹp** theo mục 1: xóa `scene/` và `lineart2d.js`, dọn bốn chỗ code thừa nằm ngoài, viết `tools/deadcode.mjs`, chạy và báo cáo sạch → **commit riêng**.
1. Đọc `tools/refs/`. Viết `tools/check-source.mjs` kiểm tra ảnh gốc: tồn tại, ≥ 3840px, tỉ lệ 16:9, in histogram thô để xác nhận có đủ bậc độ sáng → **dừng, báo cáo**.
2. `renderer.js` + `depth.js` + chế độ xem bản đồ độ sâu trong GUI → **dừng, duyệt bản đồ độ sâu trước khi làm gì khác**. Đây là bước quan trọng nhất.
3. `terrainMesh.js` (unprojection, cắt trời theo độ sáng, chống giãn, tint, rim) + `sky.js` + `camera.js` → **dừng, duyệt 4 ảnh**. Kiểm tra kỹ chỗ nối trời và núi, và artifact khi dolly tới cực đại.
4. `sun.js` + `mist.js` + grain/vignette → **dừng, duyệt**. Bước quyết định cảnh có "đắt" hay không, dự kiến nhiều vòng chỉnh.
5. `dust.js` + `idle.js` + `birds.js` → **dừng, duyệt**. Kiểm tra bằng cách **không cuộn trong 30 giây**: khung hình phải vẫn sống.
6. `reeds.js` + bloom desktop → **dừng, duyệt**.
7. `card-view.js` + CSS card + viền ánh kim + test "đúng một card" → **dừng, duyệt**.
8. `stops.js` + test, nối vào `store.js`, tích hợp ngược vào theme WordPress.
9. `tier.js` + MODE_2D + `poster.webp` + `lifecycle.js`. Chạy checklist, đo trên thiết bị thật, xóa file scene cũ.

Mỗi bước một commit riêng. Ghi quyết định vào `README-services-cinematic.md`.

---

# 19. TIÊU CHÍ NGHIỆM THU

**Độ sâu và hình**
- [ ] Bản đồ độ sâu mượt, không gồ ghề theo vân đá; rặng xa sáng, rặng gần tối.
- [ ] Nhìn từ vị trí camera tham chiếu (p = 0), hình trùng khít ảnh gốc, không méo.
- [ ] Ở dolly cực đại, không thấy tam giác kéo giãn ở biên độ sâu.
- [ ] Phần gần nhất phóng không quá 18% — có assert trong code.
- [ ] Chỗ nối giữa trời shader và núi từ ảnh không lộ vệt ngang.

**Hình ảnh**
- [ ] Khối núi neo không nằm trong x ∈ [45%, 55%].
- [ ] Ảnh phủ kín chiều rộng viewport ở mọi tỉ lệ màn hình, không dải trống hai bên, không cover-crop.
- [ ] Đường chân trời ở 45–50% trên desktop và 55–60% trên màn hẹp.
- [ ] Đáy ảnh tràn xuống dưới viewport; chi tiết sắc nét ở mép dưới ảnh không nhìn thấy.
- [ ] Mặt trời bị một rặng gần che một phần ở điểm dừng đầu, lộ dần lên.
- [ ] Không còn sprite tia hình bánh răng, không còn vòng lens-flare tròn.
- [ ] Không banding trên gradient trời.
- [ ] Grain và vignette hoạt động trên **mọi** tier kể cả mobile.
- [ ] **Không cuộn trong 30 giây, khung hình vẫn có chuyển động.**

**Card**
- [ ] Đúng một card hiển thị ở mọi progress — có test tự động.
- [ ] Viền gradient ánh kim hiển thị đúng trên Chrome, Safari, Firefox.
- [ ] Ảnh trong card tỉ lệ 3:2, không crop tròn.
- [ ] Nút CTA không xuống dòng ở mọi breakpoint.

**Kỹ thuật**
- [ ] `node --test tests/` pass (stops, card-machine).
- [ ] N = 1, 3, 4, 5, 6 đều chạy đúng, nhãn đúng.
- [ ] Không có hex mới trong CSS; mọi màu truy được về token.
- [ ] Cuộn 1 dịch vụ = 100svh; cuộn ngược đảo chiều đúng; cuộn nhanh không kẹt card.
- [ ] Mobile ≥ 50fps khi cuộn.
- [ ] iOS Safari: không giật khi thanh địa chỉ ẩn/hiện, không nhảy layout.
- [ ] `prefers-reduced-motion` → danh sách tĩnh, không nạp three.
- [ ] Tắt JS → danh sách đầy đủ, SEO đọc được.
- [ ] Mất context WebGL → khôi phục được; lần hai → MODE_2D.
- [ ] Lighthouse: không lỗi console, CLS ≈ 0, contrast AA cho chữ nhỏ.

**Dọn dẹp**
- [ ] `tools/deadcode.mjs` chạy sạch: không file mồ côi, không khóa config thừa.
- [ ] Không còn đăng ký `three/addons/objects/Sky.js` trong PHP, trong importmap, và file vendor đã xóa.
- [ ] Không còn CSS mồ côi của cảnh và card cũ.
- [ ] `git grep -nE "terrain|vegetation|lineart2d|raymarch|godray"` không ra kết quả nào ngoài file tài liệu.
- [ ] So với tag `scene-v1-final`, tổng số file JS của section giảm, không tăng.

---

# PHỤ LỤC — STACK

Pin phiên bản, tự host trong `assets/vendor/`:

- `three@0.187.0` — `build/three.module.js`. **Không** dùng addon `Sky.js`.
- `gsap@3.15.1` — `index.js`, `ScrollTrigger.js`. Không dùng bản 4.x beta.
- `lenis@1.3.27` — chỉ trên thiết bị không-touch.
- `@pmndrs/detect-gpu` — `benchmarksURL` trỏ về bản tự host.
- `lil-gui` + `stats` — chỉ khi `?isha-debug=1`.

Renderer: `outputColorSpace = SRGBColorSpace`, `toneMapping = NeutralToneMapping` (A/B với AgX qua GUI). **Không dùng ACESFilmic** vì nó làm lệch hue của vàng. Dùng `THREE.Timer` thay `Clock`. Gọi `await renderer.compileAsync(scene, camera)` trước khi fade poster sang canvas.

---

# NẾU V3 CHƯA ĐỦ

Nếu sau khi chỉnh kỹ mà biên độ camera vẫn thấy quá nhỏ, hoặc bố cục bị khóa theo ảnh gây khó chịu, thì nâng cấp lên **v2** (tách 5 lớp thủ công trong Photoshop). Kiến trúc runtime gần như trùng nhau — chỉ thay nguồn texture từ một ảnh có depth sang năm quad có alpha. Không phải viết lại.
