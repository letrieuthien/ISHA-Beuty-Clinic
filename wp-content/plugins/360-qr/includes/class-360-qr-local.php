<?php
if (!defined('ABSPATH')) exit;

/**
 * 360 QR - self-contained QR Model 2 encoder.
 *
 * This implementation creates a standards-compliant QR Code in byte mode,
 * Version 5, Error Correction Level L, with a quiet zone and SVG output.
 * It intentionally has no external runtime dependency.
 */
class NET360QR_LOCAL {
    const VERSION = 5;
    const SIZE = 37;
    const DATA_CODEWORDS = 108;
    const ECC_CODEWORDS = 26;
    const MAX_BYTES = 105;

    public static function svg($text, $size=600, $dark='#111827', $light='#FFFFFF', $logo_attachment_id=0, $shape='classic') {
        $matrix = self::matrix($text);
        $n = self::SIZE;
        $margin = 4;
        $view = $n + ($margin * 2);
        $size = max(64, min(2048, (int)$size));
        $dark = self::color($dark, '#111827');
        $light = self::color($light, '#FFFFFF');
        $shape = self::shape($shape);
        $has_logo = (bool) self::logo_file($logo_attachment_id);

        $out = '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 '.$view.' '.$view.'" width="'.$size.'" height="'.$size.'" shape-rendering="crispEdges" role="img" aria-label="QR Code">';
        $out .= '<rect width="100%" height="100%" fill="'.$light.'"/>';
        $out .= '<g fill="'.$dark.'">';
        for ($y=0; $y<$n; $y++) {
            for ($x=0; $x<$n; $x++) {
                $xx = $x + $margin; $yy = $y + $margin;
                // Leave a genuine hole in the rendered module field for the centered logo.
                if ($has_logo && self::inside_logo_hole($xx + 0.5, $yy + 0.5, $view)) continue;
                if (!$matrix[$y][$x]) continue;
                // Keep the three finder-pattern zones square for better scanner compatibility.
                $finder = (($x < 8 && $y < 8) || ($x >= $n - 8 && $y < 8) || ($x < 8 && $y >= $n - 8));
                if (!$finder && $shape === 'dots') $out .= '<circle cx="'.($xx+.5).'" cy="'.($yy+.5).'" r="0.47"/>';
                elseif (!$finder && $shape === 'diamonds') $out .= '<path d="M'.($xx+.5).' '.$yy.'L'.($xx+1).' '.($yy+.5).'L'.($xx+.5).' '.($yy+1).'L'.$xx.' '.($yy+.5).'Z"/>';
                elseif (!$finder && $shape === 'rounded') $out .= '<rect x="'.$xx.'" y="'.$yy.'" width="1" height="1" rx="0.42"/>';
                elseif (!$finder && $shape === 'rounded_square') $out .= '<rect x="'.$xx.'" y="'.$yy.'" width="1" height="1" rx="0.18"/>';
                elseif (!$finder && $shape === 'geometric') $out .= '<path d="M'.($xx+.25).' '.$yy.'L'.($xx+.75).' '.$yy.'L'.($xx+1).' '.($yy+.5).'L'.($xx+.75).' '.($yy+1).'L'.($xx+.25).' '.($yy+1).'L'.$xx.' '.($yy+.5).'Z"/>';
                else $out .= '<path d="M'.$xx.' '.$yy.'h1v1h-1z"/>';
            }
        }
        $out .= '</g>';
        $out .= self::svg_logo_overlay($logo_attachment_id, $view);
        $out .= '</svg>';
        return $out;
    }

    private static function logo_file($attachment_id) {
        $attachment_id = absint($attachment_id);
        if (!$attachment_id) return '';
        $mime = get_post_mime_type($attachment_id);
        if (!in_array($mime, array('image/png', 'image/jpeg', 'image/webp'), true)) return '';
        $file = get_attached_file($attachment_id);
        if (!$file || !is_file($file) || !is_readable($file)) return '';
        $size = @filesize($file);
        if (!$size || $size > 2 * 1024 * 1024) return '';
        return $file;
    }

    private static function svg_logo_overlay($attachment_id, $view) {
        $file = self::logo_file($attachment_id);
        if (!$file) return '';
        $mime = get_post_mime_type($attachment_id);
        $bytes = file_get_contents($file);
        if ($bytes === false || strlen($bytes) > 2 * 1024 * 1024) return '';
        $data = 'data:' . $mime . ';base64,' . base64_encode($bytes);
        // Small central logo and white backing preserve most QR modules.
        $box = $view * 0.20;
        $x = ($view - $box) / 2;
        // The module field is omitted in this rounded area; white is not merely painted over modules.
        $pad = $box * 0.15;
        $image = $box - ($pad * 2);
        $radius = $box * 0.16;
        return '<rect x="'.round($x,3).'" y="'.round($x,3).'" width="'.round($box,3).'" height="'.round($box,3).'" rx="'.round($radius,3).'" fill="#FFFFFF"/><image href="'.$data.'" x="'.round($x+$pad,3).'" y="'.round($x+$pad,3).'" width="'.round($image,3).'" height="'.round($image,3).'" preserveAspectRatio="xMidYMid meet"/>';
    }

    /** Render visual examples for every supported QR module style. */
    public static function shape_picker($selected = 'classic') {
        $selected = self::shape($selected);
        $options = array(
            'classic' => array('1. Classic', 'Traditional square modules'),
            'rounded' => array('2. Rounded', 'Modules with rounded corners'),
            'dots' => array('3. Dots', 'Circular modules'),
            'diamonds' => array('4. Diamonds', 'Diamond-shaped modules'),
            'rounded_square' => array('5. Rounded Squares', 'Squares with rounded corners'),
            'geometric' => array('6. Geometric', 'Geometric modules'),
        );
        ob_start();
        echo '<div class="net360qr-shape-picker" role="radiogroup" aria-label="QR style">';
        foreach ($options as $value => $info) {
            echo '<label class="net360qr-shape-card'.($selected === $value ? ' is-selected' : '').'">';
            echo '<input type="radio" name="qr_shape" value="'.esc_attr($value).'" '.checked($selected, $value, false).' />';
            // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- SVG is generated internally from a fixed demo URL, validated colors, and a whitelisted shape; escaping would corrupt the SVG markup.
            echo '<span class="net360qr-shape-preview">'.self::svg('https://net360.pe/qr-demo', 180, '#111827', '#FFFFFF', 0, $value).'</span>';
            echo '<span class="net360qr-shape-name">'.esc_html($info[0]).'</span>';
            echo '<span class="net360qr-shape-description">'.esc_html($info[1]).'</span>';
            echo '</label>';
        }
        echo '</div><p class="net360qr-shape-note">Select a card to choose the appearance. The dynamic destination and scan statistics will not change.</p>';
        return ob_get_clean();
    }

    public static function data_uri($text, $size=600) {
        return 'data:image/svg+xml;base64,'.base64_encode(self::svg($text, $size));
    }

    public static function png($text, $size=1200, $logo_attachment_id=0, $shape='classic') {
        if (!function_exists('imagecreatetruecolor')) {
            return new WP_Error('net360_no_gd', 'The PHP GD extension is not available.');
        }
        $matrix=self::matrix($text); $n=self::SIZE; $margin=4; $modules=$n+($margin*2); $shape=self::shape($shape); $has_logo=(bool)self::logo_file($logo_attachment_id);
        $size=max(128,min(3000,(int)$size));
        $img=imagecreatetruecolor($size,$size); $white=imagecolorallocate($img,255,255,255); $black=imagecolorallocate($img,17,24,39);
        imagefilledrectangle($img,0,0,$size-1,$size-1,$white); $scale=$size/$modules;
        for($y=0;$y<$n;$y++) for($x=0;$x<$n;$x++) {
            if ($has_logo && self::inside_logo_hole($x + $margin + 0.5, $y + $margin + 0.5, $modules)) continue;
            if (!$matrix[$y][$x]) continue;
            $x1=(int)floor(($x+$margin)*$scale); $y1=(int)floor(($y+$margin)*$scale); $x2=(int)ceil(($x+$margin+1)*$scale)-1; $y2=(int)ceil(($y+$margin+1)*$scale)-1;
            // Keep finder-pattern zones square; stylize only the data modules.
            $finder = (($x < 8 && $y < 8) || ($x >= $n - 8 && $y < 8) || ($x < 8 && $y >= $n - 8));
            if (!$finder && $shape === 'dots') {
                $cx=(int)round(($x+$margin+.5)*$scale); $cy=(int)round(($y+$margin+.5)*$scale); $r=max(1,(int)floor($scale*.43)); imagefilledellipse($img,$cx,$cy,$r*2,$r*2,$black);
            } elseif (!$finder && ($shape === 'diamonds' || $shape === 'geometric')) {
                $cx=($x+$margin+.5)*$scale; $cy=($y+$margin+.5)*$scale;
                if ($shape === 'diamonds') $pts=array((int)$cx,(int)($y1),(int)$x2,(int)$cy,(int)$cx,(int)$y2,(int)$x1,(int)$cy);
                else { $pts=array((int)($x1+$scale*.25),(int)$y1,(int)($x1+$scale*.75),(int)$y1,(int)$x2,(int)$cy,(int)($x1+$scale*.75),(int)$y2,(int)($x1+$scale*.25),(int)$y2,(int)$x1,(int)$cy); }
                if (function_exists('imagefilledpolygon')) {
                    imagefilledpolygon($img, $pts, (int) (count($pts) / 2), $black);
                } else {
                    imagefilledrectangle($img, $x1, $y1, $x2, $y2, $black);
                }
            } elseif (!$finder && ($shape === 'rounded' || $shape === 'rounded_square')) {
                $inset = $shape === 'rounded' ? (int)floor($scale*.12) : (int)floor($scale*.04);
                imagefilledrectangle($img,$x1+$inset,$y1+$inset,$x2-$inset,$y2-$inset,$black);
            } else imagefilledrectangle($img,$x1,$y1,$x2,$y2,$black);
        }
        // Overlay an optional small logo in the center, with a white backing.
        $logo_file = self::logo_file($logo_attachment_id);
        if ($logo_file && function_exists('imagecreatefromstring')) {
            $logo_bytes = file_get_contents($logo_file);
            $logo_src = ($logo_bytes !== false && strlen($logo_bytes) <= 2 * 1024 * 1024) ? @imagecreatefromstring($logo_bytes) : false;
            if ($logo_src) {
                $box = (int) round($size * 0.20);
                // Match the SVG export: rounded true-clearance area and inner spacing.
                $pad = max(3, (int) round($box * 0.15));
                $inner = max(1, $box - 2 * $pad);
                $lx = (int) floor(($size - $box) / 2); $ly = $lx;
                $white = imagecolorallocate($img, 255, 255, 255);
                self::filled_rounded_rect($img, $lx, $ly, $lx + $box - 1, $ly + $box - 1, (int) round($box * 0.16), $white);
                $sw = imagesx($logo_src); $sh = imagesy($logo_src);
                $ratio = min($inner / max(1, $sw), $inner / max(1, $sh));
                $dw = max(1, (int) round($sw * $ratio)); $dh = max(1, (int) round($sh * $ratio));
                imagealphablending($img, true);
                imagecopyresampled($img, $logo_src, $lx + (int)(($box-$dw)/2), $ly + (int)(($box-$dh)/2), 0, 0, $dw, $dh, $sw, $sh);
                imagedestroy($logo_src);
            }
        }
        ob_start();
        try { imagepng($img); $data = ob_get_clean(); }
        catch (Throwable $exception) { if (ob_get_level() > 0) ob_end_clean(); imagedestroy($img); return new WP_Error('net360qr_png_generation_failed', 'Could not generate the PNG.'); }
        imagedestroy($img);
        return $data;
    }

    /** Determine whether a module center falls inside the reserved rounded logo cutout. */
    private static function inside_logo_hole($cx, $cy, $view) {
        $box = $view * 0.20;
        $left = ($view - $box) / 2;
        $right = $left + $box;
        $top = $left;
        $bottom = $right;
        $radius = $box * 0.16;
        if ($cx < $left || $cx > $right || $cy < $top || $cy > $bottom) return false;
        $qx = max($left + $radius, min($cx, $right - $radius));
        $qy = max($top + $radius, min($cy, $bottom - $radius));
        $dx = $cx - $qx;
        $dy = $cy - $qy;
        return (($dx * $dx) + ($dy * $dy)) <= ($radius * $radius);
    }

    /** Draw a solid rounded rectangle with GD using matching rectangles and circular corners. */
    private static function filled_rounded_rect($image, $x1, $y1, $x2, $y2, $radius, $color) {
        $radius = max(1, min((int)$radius, (int)(($x2 - $x1 + 1) / 2), (int)(($y2 - $y1 + 1) / 2)));
        imagefilledrectangle($image, $x1 + $radius, $y1, $x2 - $radius, $y2, $color);
        imagefilledrectangle($image, $x1, $y1 + $radius, $x2, $y2 - $radius, $color);
        imagefilledellipse($image, $x1 + $radius, $y1 + $radius, $radius * 2, $radius * 2, $color);
        imagefilledellipse($image, $x2 - $radius, $y1 + $radius, $radius * 2, $radius * 2, $color);
        imagefilledellipse($image, $x1 + $radius, $y2 - $radius, $radius * 2, $radius * 2, $color);
        imagefilledellipse($image, $x2 - $radius, $y2 - $radius, $radius * 2, $radius * 2, $color);
    }

    public static function validate_text($text) {
        $bytes = strlen((string)$text);
        if ($bytes > self::MAX_BYTES) {
            return new WP_Error('net360qr_too_long', sprintf('The QR code content exceeds the maximum of %d bytes.', self::MAX_BYTES));
        }
        return true;
    }

    public static function shape($value) {
        $allowed = array('classic','rounded','dots','diamonds','rounded_square','geometric');
        $value = sanitize_key((string)$value);
        return in_array($value, $allowed, true) ? $value : 'classic';
    }

    private static function color($value, $fallback) {
        $value = strtoupper(trim((string)$value));
        return preg_match('/^#[0-9A-F]{6}$/', $value) ? $value : $fallback;
    }

    private static function matrix($text) {
        $text = (string)$text;
        $valid = self::validate_text($text);
        if (is_wp_error($valid)) {
            throw new InvalidArgumentException(esc_html($valid->get_error_message()));
        }

        $bytes = array_values(unpack('C*', $text));
        $bits = array(0,1,0,0); // Byte mode
        $len = count($bytes);
        self::append_bits($bits, $len, 8);
        foreach ($bytes as $byte) self::append_bits($bits, $byte, 8);

        $capacity = self::DATA_CODEWORDS * 8;
        $terminator = min(4, $capacity - count($bits));
        for ($i=0; $i<$terminator; $i++) $bits[] = 0;
        while (count($bits) % 8) $bits[] = 0;

        $pads = array(0xEC, 0x11);
        $p = 0;
        while (count($bits) < $capacity) {
            self::append_bits($bits, $pads[$p % 2], 8);
            $p++;
        }

        $data = array();
        for ($i=0; $i<count($bits); $i+=8) {
            $v = 0;
            for ($j=0; $j<8; $j++) $v = ($v << 1) | $bits[$i+$j];
            $data[] = $v;
        }
        $ecc = self::reed_solomon($data, self::ECC_CODEWORDS);
        $codewords = array_merge($data, $ecc);

        $size = self::SIZE;
        $m = array_fill(0, $size, array_fill(0, $size, null));

        self::finder($m, 0, 0);
        self::finder($m, $size - 7, 0);
        self::finder($m, 0, $size - 7);

        for ($i=8; $i<$size-8; $i++) {
            $m[6][$i] = (($i % 2) === 0);
            $m[$i][6] = (($i % 2) === 0);
        }

        // Version 5 alignment pattern: center (30,30).
        self::alignment($m, 30, 30);

        // Fixed dark module.
        $m[$size-8][8] = true;

        // Reserve/write format information: L + mask 0.
        $format = self::bch_format(8);
        for ($i=0; $i<15; $i++) {
            $bit = (($format >> $i) & 1) === 1;
            if ($i < 6) $m[$i][8] = $bit;
            elseif ($i < 8) $m[$i+1][8] = $bit;
            else $m[$size-15+$i][8] = $bit;

            if ($i < 8) $m[8][$size-$i-1] = $bit;
            elseif ($i < 9) $m[8][15-$i] = $bit;
            else $m[8][14-$i] = $bit;
        }

        // Place data in the standard zig-zag pattern. Mask 0 is (x+y)%2==0.
        $bitIndex = 0;
        $x = $size - 1;
        $up = true;
        $totalBits = count($codewords) * 8;

        while ($x > 0) {
            if ($x === 6) $x--;
            $ys = $up ? range($size-1, 0) : range(0, $size-1);
            foreach ($ys as $y) {
                for ($dx=0; $dx<2; $dx++) {
                    $xx = $x - $dx;
                    if ($m[$y][$xx] !== null) continue;
                    $dataBit = ($bitIndex < $totalBits) ? (($codewords[intdiv($bitIndex, 8)] >> (7 - ($bitIndex % 8))) & 1) : 0;
                    $maskBit = ((($xx + $y) % 2) === 0) ? 1 : 0;
                    $m[$y][$xx] = (($dataBit ^ $maskBit) === 1);
                    $bitIndex++;
                }
            }
            $up = !$up;
            $x -= 2;
        }

        // Any remainder modules are zero data before masking.
        for ($y=0; $y<$size; $y++) for ($x=0; $x<$size; $x++) if ($m[$y][$x] === null) $m[$y][$x] = false;
        return $m;
    }

    private static function append_bits(&$bits, $value, $count) {
        for ($i=$count-1; $i>=0; $i--) $bits[] = (($value >> $i) & 1);
    }

    private static function finder(&$m, $sx, $sy) {
        $size = count($m);
        for ($dy=-1; $dy<=7; $dy++) {
            for ($dx=-1; $dx<=7; $dx++) {
                $x = $sx + $dx; $y = $sy + $dy;
                if ($x<0 || $y<0 || $x>=$size || $y>=$size) continue;
                $on = ($dx>=0 && $dx<=6 && $dy>=0 && $dy<=6 && ($dx===0 || $dx===6 || $dy===0 || $dy===6 || ($dx>=2 && $dx<=4 && $dy>=2 && $dy<=4)));
                $m[$y][$x] = $on;
            }
        }
    }

    private static function alignment(&$m, $cx, $cy) {
        for ($dy=-2; $dy<=2; $dy++) for ($dx=-2; $dx<=2; $dx++) {
            $m[$cy+$dy][$cx+$dx] = (max(abs($dx), abs($dy)) !== 1);
        }
    }

    private static function bch_format($data) {
        $v = $data << 10;
        $g = 0x537;
        while (self::bit_length($v) >= self::bit_length($g)) $v ^= $g << (self::bit_length($v) - self::bit_length($g));
        return (($data << 10) | $v) ^ 0x5412;
    }

    private static function bit_length($v) {
        $n = 0;
        while ($v > 0) { $n++; $v >>= 1; }
        return $n;
    }

    private static function gf_mul($x, $y) {
        $z = 0;
        while ($y > 0) {
            if ($y & 1) $z ^= $x;
            $y >>= 1;
            $x <<= 1;
            if ($x & 0x100) $x ^= 0x11D;
        }
        return $z;
    }

    private static function gf_pow2($power) {
        $x = 1;
        for ($i=0; $i<($power % 255); $i++) $x = self::gf_mul($x, 2);
        return $x;
    }

    private static function reed_solomon($data, $degree) {
        $gen = array(1);
        for ($i=0; $i<$degree; $i++) {
            $next = array_fill(0, count($gen)+1, 0);
            $a = self::gf_pow2($i);
            foreach ($gen as $j=>$coef) {
                $next[$j] ^= $coef;
                $next[$j+1] ^= self::gf_mul($coef, $a);
            }
            $gen = $next;
        }

        $rem = array_fill(0, $degree, 0);
        foreach ($data as $byte) {
            $factor = $byte ^ $rem[0];
            array_shift($rem);
            $rem[] = 0;
            for ($j=0; $j<$degree; $j++) $rem[$j] ^= self::gf_mul($gen[$j+1], $factor);
        }
        return $rem;
    }
}
