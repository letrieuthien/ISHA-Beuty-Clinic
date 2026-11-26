<?php
if (!defined('ABSPATH')) exit;

class NET360QR_REDIRECT {
    public function __construct() {
        add_action('init', array($this, 'rewrite'));
        add_filter('query_vars', array($this, 'vars'));
        add_action('template_redirect', array($this, 'handle'), 1);
    }

    public static function add_rules() {
        add_rewrite_rule('^q-image/([A-Za-z0-9_-]+)/png/?$', 'index.php?net360qrimage=$matches[1]&net360qrformat=png', 'top');
        add_rewrite_rule('^q-image/([A-Za-z0-9_-]+)/?$', 'index.php?net360qrimage=$matches[1]&net360qrformat=svg', 'top');
        add_rewrite_rule('^q/([A-Za-z0-9_-]+)/?$', 'index.php?net360qr=$matches[1]', 'top');
    }

    public function rewrite() { self::add_rules(); }
    public function vars($vars) { $vars[]='net360qr'; $vars[]='net360qrimage'; $vars[]='net360qrformat'; return $vars; }

    public function handle() {
        $image_code = get_query_var('net360qrimage');
        if ($image_code) {
            $qr = NET360QR_DB::get_by_code(strtoupper(preg_replace('/[^A-Z0-9_-]/', '', (string)$image_code)));
            if (!$qr) { status_header(404); exit; }
            $public = home_url('/q/'.rawurlencode($qr->code));
            $format = sanitize_key((string) get_query_var('net360qrformat', 'svg'));
            if (!in_array($format, array('svg', 'png'), true)) {
                $format = 'svg';
            }
            nocache_headers();
            if ($format === 'png') {
                try {
                    // phpcs:ignore WordPress.Security.NonceVerification.Recommended -- Public, read-only QR image endpoint; size is allowlisted below and no state is changed.
                    $size = isset($_GET['size']) ? absint(wp_unslash($_GET['size'])) : 1200;
                    if (!in_array($size, array(512, 1024, 2048), true)) { $size = 1200; }
                    $png = NET360QR_LOCAL::png($public, $size, absint($qr->logo_attachment_id ?? 0), NET360QR_LOCAL::shape($qr->qr_shape ?? 'classic'));
                } catch (Throwable $e) {
                    $png = NET360QR_LOCAL::png($public, isset($size) ? $size : 1200, 0, 'classic');
                }
                if (is_wp_error($png)) { status_header(500); echo esc_html($png->get_error_message()); exit; }
                header('Content-Type: image/png');
                header('Content-Disposition: attachment; filename="net360-'.sanitize_file_name($qr->code).'-'.(isset($size) ? $size : 1200).'px.png"');
                // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- PNG is binary data and must not be escaped.
                echo $png;
                exit;
            }
            header('Content-Type: image/svg+xml; charset=utf-8');
            header('Content-Disposition: inline; filename="net360-'.sanitize_file_name($qr->code).'.svg"');
            try {
                $svg = NET360QR_LOCAL::svg($public, 1000, '#111827', '#FFFFFF', absint($qr->logo_attachment_id ?? 0), NET360QR_LOCAL::shape($qr->qr_shape ?? 'classic'));
            } catch (Throwable $e) {
                $svg = NET360QR_LOCAL::svg($public, 1000);
            }
            // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- $svg is generated internally by NET360QR_LOCAL::svg() using validated colors/shapes and an encoded local logo; escaping would corrupt the SVG document.
            echo $svg;
            exit;
        }

        $code = get_query_var('net360qr');
        if (!$code) return;

        $code = strtoupper(preg_replace('/[^A-Z0-9_-]/', '', (string)$code));
        $qr = NET360QR_DB::get_by_code($code);
        if (!$qr) {
            status_header(404);
            wp_die('QR code not found.', '360 QR', array('response'=>404));
        }
        if ($qr->status !== 'active') {
            status_header(410);
            wp_die('This QR code is inactive.', '360 QR', array('response'=>410));
        }

        $url = esc_url_raw($qr->destination_url);
        $parts = wp_parse_url($url);
        if (!$parts || empty($parts['scheme']) || !in_array(strtolower($parts['scheme']), array('http','https'), true)) {
            status_header(400);
            wp_die('The destination URL is invalid.', '360 QR', array('response'=>400));
        }

        NET360QR_DB::register_scan($qr->id);
        $destination_host = wp_parse_url($url, PHP_URL_HOST);
        if (!$destination_host) {
            status_header(400);
            wp_die('The destination URL is invalid.', '360 QR', array('response' => 400));
        }

        $allow_destination = static function ($hosts) use ($destination_host) {
            $hosts[] = $destination_host;
            return array_values(array_unique($hosts));
        };
        add_filter('allowed_redirect_hosts', $allow_destination);
        wp_safe_redirect($url, 302, '360 QR');
        remove_filter('allowed_redirect_hosts', $allow_destination);
        exit;
    }
}
