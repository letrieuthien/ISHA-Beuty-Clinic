<?php
if (!defined('ABSPATH')) exit;

class NET360QR_FRONTEND {
    public function __construct() {
        add_shortcode('net360qr_dashboard', array($this,'dashboard'));
        add_action('init', array($this,'actions'));
        add_filter('query_vars', array($this, 'query_vars'));
        add_action('wp_enqueue_scripts', array($this,'assets'));
    }

    public function query_vars($vars) {
        $vars[] = 'net360qr_edit';
        $vars[] = 'net360qr_ok';
        $vars[] = 'net360qr_error';
        return $vars;
    }

    public function assets() {
        if (!is_singular()) return;
        global $post;
        if ($post && (has_shortcode($post->post_content, 'net360qr_dashboard') || has_shortcode($post->post_content, 'net360qr_dashboard'))) {
            wp_enqueue_style('360-qr-frontend', NET360QR_URL.'assets/frontend.css', array(), NET360QR_VERSION);
            wp_enqueue_script('360-qr-download', NET360QR_URL.'assets/qr-download.js', array(), NET360QR_VERSION, true);
            wp_enqueue_script('360-qr-shapes', NET360QR_URL.'assets/qr-shapes.js', array(), NET360QR_VERSION, true);
        }
    }

    private function back($args=array()) {
        $u = wp_get_referer() ?: home_url('/');
        wp_safe_redirect(add_query_arg($args, $u)); exit;
    }

    private function generated_code() {
        $chars = 'ABCDEFGHJKLMNPQRSTUVWXYZ23456789';
        do {
            $code = '';
            for ($i=0; $i<8; $i++) $code .= $chars[wp_rand(0, strlen($chars)-1)];
        } while (NET360QR_DB::code_exists($code));
        return $code;
    }

    public function actions() {
        if (!is_user_logged_in() || !isset($_POST['net360qr_action'])) return;
        $action = sanitize_key(wp_unslash($_POST['net360qr_action']));
        if (!in_array($action, array('save','toggle','delete'), true)) return;

        $nonce = isset($_POST['_wpnonce']) ? sanitize_text_field(wp_unslash($_POST['_wpnonce'])) : '';
        if (!$nonce || !wp_verify_nonce($nonce, 'net360qr_front'))
            $this->back(array('net360qr_error'=>'Invalid request.'));

        $uid = get_current_user_id();
        $id = isset($_POST['id']) ? absint(wp_unslash($_POST['id'])) : 0;

        if ($action === 'save') {
            $name = isset($_POST['name']) ? sanitize_text_field(wp_unslash($_POST['name'])) : '';
            $dest = isset($_POST['destination_url']) ? esc_url_raw(wp_unslash($_POST['destination_url'])) : '';
            $code = isset($_POST['code']) ? strtoupper(sanitize_key(wp_unslash($_POST['code']))) : '';

            if (!$name || !$dest || !preg_match('#^https?://#i', $dest))
                $this->back(array('net360qr_error'=>'Enter a name and a valid URL (http/https).'));

            $public = home_url('/q/'.($code ?: 'XXXXXXXX'));
            $qrCheck = NET360QR_LOCAL::validate_text($public);
            if (is_wp_error($qrCheck)) $this->back(array('net360qr_error'=>$qrCheck->get_error_message()));

            if (!$code) $code = $this->generated_code();
            if (NET360QR_DB::code_exists($code, $id)) $this->back(array('net360qr_error'=>'Duplicate code. Please choose another.'));

            $existing = $id ? NET360QR_DB::get_for_user($id, $uid) : null;
            if ($id && !$existing) $this->back(array('net360qr_error'=>'QR code not found.'));
            $logo_id = $existing ? absint($existing->logo_attachment_id ?? 0) : 0;
            if (!empty($_POST['remove_logo'])) $logo_id = 0;
            if (!empty($_FILES['net360qr_logo']['name'])) {
                if (!current_user_can('upload_files')) $this->back(array('net360qr_error'=>'Your account does not have permission to upload logos.'));
                require_once ABSPATH . 'wp-admin/includes/file.php';
                require_once ABSPATH . 'wp-admin/includes/media.php';
                require_once ABSPATH . 'wp-admin/includes/image.php';
                $new_logo = media_handle_upload('net360qr_logo', 0);
                if (is_wp_error($new_logo)) $this->back(array('net360qr_error'=>'Could not upload the logo.'));
                $mime = get_post_mime_type($new_logo);
                if (!in_array($mime, array('image/png','image/jpeg','image/webp'), true)) { wp_delete_attachment($new_logo, true); $this->back(array('net360qr_error'=>'The logo must be a PNG, JPG, or WebP image.')); }
                $logo_id = (int) $new_logo;
            }
            $shape_input = isset($_POST['qr_shape']) ? sanitize_key(wp_unslash($_POST['qr_shape'])) : ($existing->qr_shape ?? 'classic');
            $shape = NET360QR_LOCAL::shape($shape_input);
            $data = array('name'=>$name,'code'=>$code,'destination_url'=>$dest,'logo_attachment_id'=>$logo_id,'qr_shape'=>$shape);
            if ($id) NET360QR_DB::update($id, $data);
            else NET360QR_DB::insert(array_merge($data,array('user_id'=>$uid,'status'=>'active')));
            $this->back(array('net360qr_ok'=>'1'));
        }

        $qr = NET360QR_DB::get_for_user($id, $uid);
        if (!$qr) $this->back(array('net360qr_error'=>'QR code not found.'));
        if ($action === 'delete') NET360QR_DB::delete($id);
        else NET360QR_DB::update($id, array('status'=>$qr->status === 'active' ? 'inactive' : 'active'));
        $this->back(array('net360qr_ok'=>'1'));
    }

    private function svg_data($public, $size=300) {
        return NET360QR_LOCAL::data_uri($public, $size);
    }

    public function dashboard() {
        if (!is_user_logged_in()) {
            return '<div class="360-qr-app"><div class="net360-alert error">Please log in to manage your QR codes.</div></div>';
        }

        $uid = get_current_user_id();
        $qrs = NET360QR_DB::all($uid);
        $edit_id = absint(get_query_var('net360qr_edit', 0));
        $edit = $edit_id ? NET360QR_DB::get_for_user($edit_id, $uid) : null;
        $total_scans = 0; $active = 0;
        foreach ($qrs as $q) { $total_scans += (int)$q->scans; if ($q->status === 'active') $active++; }

        ob_start(); ?>
        <div class="360-qr-app">
            <div class="360-qr-hero">
                <div>
                    <span class="net360-kicker">360 QR MANAGER</span>
                    <h2>My QR Codes</h2>
                    <p>Create dynamic QR codes and change their destination without reprinting them.</p>
                </div>
                <div class="360-qr-hero-badge"><strong><?php echo esc_html(number_format_i18n(count($qrs))); ?></strong><span>QR Codes Created</span></div>
            </div>

            <?php if (get_query_var('net360qr_ok', '')): ?><div class="net360-alert ok">✓ Changes saved successfully.</div><?php endif; ?>
            <?php $front_error = sanitize_text_field((string) get_query_var('net360qr_error', '')); if ($front_error): ?><div class="net360-alert error"><?php echo esc_html($front_error); ?></div><?php endif; ?>

            <div class="net360-stats">
                <div class="net360-stat"><span>Active QR Codes</span><strong><?php echo esc_html(number_format_i18n($active)); ?></strong></div>
                <div class="net360-stat"><span>Total Scans</span><strong><?php echo esc_html(number_format_i18n($total_scans)); ?></strong></div>
                <div class="net360-stat"><span>System Status</span><strong class="live">● Operational</strong></div>
            </div>

            <div class="360-qr-card net360-create-card">
                <div class="net360-card-title"><div><span class="net360-step">01</span><div><h3><?php echo $edit?'Edit Dynamic QR Code':'Create Dynamic QR Code'; ?></h3><p>The QR URL stays the same even when you change its destination.</p></div></div></div>
                <form method="post" enctype="multipart/form-data">
                    <?php wp_nonce_field('net360qr_front'); ?>
                    <input type="hidden" name="net360qr_action" value="save"><input type="hidden" name="id" value="<?php echo esc_attr($edit->id??0); ?>">
                    <div class="net360-form-grid">
                        <label>QR Name<input type="text" name="name" required value="<?php echo esc_attr($edit->name??''); ?>" placeholder="Restaurant menu"></label>
                        <label>Custom Code <small>(optional)</small><input type="text" name="code" maxlength="32" value="<?php echo esc_attr($edit->code??''); ?>" placeholder="Automatic"></label>
                        <label class="full">Destination URL<input type="url" name="destination_url" required value="<?php echo esc_attr($edit->destination_url??''); ?>" placeholder="https://yourwebsite.com/menu"></label>
                        <div class="full net360qr-shape-field"><strong>QR style</strong><?php
                        // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- shape_picker returns internally generated HTML; all dynamic labels and attributes are escaped and SVG is generated from validated values.
                        echo NET360QR_LOCAL::shape_picker($edit->qr_shape ?? 'classic'); ?></div>
                        <div class="full net360qr-logo-option"><strong>Center Logo (Optional)</strong><div class="net360qr-logo-example"><div class="net360qr-example-code"><span>360</span></div><div><strong>QR Logo Example</strong><small>Added only if you upload an image. You can leave this blank.</small></div></div><input type="file" name="net360qr_logo" accept="image/png,image/jpeg,image/webp"><small>PNG, JPG, or WebP. A small logo with a transparent background is recommended.</small><?php if(!empty($edit->logo_attachment_id) && wp_get_attachment_url($edit->logo_attachment_id)): ?><br><img src="<?php echo esc_url(wp_get_attachment_url($edit->logo_attachment_id)); ?>" alt="Current logo" style="max-width:80px;max-height:80px;margin-top:8px"><br><label><input type="checkbox" name="remove_logo" value="1"> Remove logo</label><?php endif; ?></div>
                    </div>
                    <div class="net360-form-actions"><button class="net360-btn primary"><?php echo $edit?'Save Changes':'＋ Create QR'; ?></button><?php if($edit): ?><a class="net360-btn" href="<?php echo esc_url(remove_query_arg('net360qr_edit')); ?>">Cancel</a><?php endif; ?></div>
                </form>
            </div>

            <div class="net360-list-head"><div><span class="net360-kicker">02 · MANAGEMENT</span><h3>Your QR Codes</h3></div><span><?php echo esc_html(number_format_i18n(count($qrs))); ?> registered</span></div>

            <?php if (!$qrs): ?>
                <div class="net360-empty"><div class="net360-empty-icon">▦</div><h3>You don’t have any QR codes yet</h3><p>Create your first dynamic QR code above. You can edit its destination whenever you need.</p></div>
            <?php endif; ?>

            <div class="360-qr-grid">
            <?php foreach ($qrs as $qr):
                $public = home_url('/q/'.rawurlencode($qr->code));
                $image = home_url('/q-image/'.rawurlencode($qr->code));
            ?>
                <article class="360-qr-item <?php echo $qr->status==='active'?'':'is-inactive'; ?>">
                    <div class="360-qr-visual"><button type="button" class="net360-image-trigger" data-image="<?php echo esc_url($image); ?>" data-title="<?php echo esc_attr($qr->name); ?>" aria-label="Enlarge QR code <?php echo esc_attr($qr->name); ?>"><img src="<?php echo esc_url($image); ?>" alt="QR <?php echo esc_attr($qr->name); ?>"></button></div>
                    <div class="360-qr-info">
                        <div class="net360-item-top"><div><h3><?php echo esc_html($qr->name); ?></h3><code><?php echo esc_html($qr->code); ?></code></div><span class="net360-status <?php echo $qr->status==='active'?'active':'inactive'; ?>"><?php echo $qr->status==='active'?'Active':'Inactive'; ?></span></div>
                        <div class="net360-url-box"><span>QR URL</span><a href="<?php echo esc_url($public); ?>" target="_blank" rel="noopener"><?php echo esc_html($public); ?></a><button type="button" class="net360-copy" data-copy="<?php echo esc_attr($public); ?>">Copy</button></div>
                        <p class="net360-destination"><span>Destination</span><?php echo esc_html($qr->destination_url); ?></p>
                        <div class="net360-metrics"><span><strong><?php echo esc_html(number_format_i18n((int)$qr->scans)); ?></strong> scans</span><span><strong><?php echo $qr->last_scan_at ? esc_html(mysql2date('d/m/Y H:i', $qr->last_scan_at)) : '—'; ?></strong> last scan</span></div>
                        <div class="net360-actions">
                            <a class="net360-btn" href="<?php echo esc_url(add_query_arg('net360qr_edit',$qr->id)); ?>">✎ Edit</a>
                            <button type="button" class="net360-btn net360-image-trigger" data-image="<?php echo esc_url($image); ?>" data-title="<?php echo esc_attr($qr->name); ?>">View QR</button>
                            <details class="net360-download-menu net360-download-menu-front"><summary class="net360-btn">↓ Download</summary><div class="net360-download-options"><strong>Download PNG</strong>
                            <?php foreach (array(512=>'Standard · 512 px',1024=>'High Quality · 1024 px',2048=>'Maximum Quality · 2048 px') as $download_size=>$download_label): ?><a href="<?php echo esc_url(add_query_arg('size',$download_size,$image . '/png')); ?>" download="<?php echo esc_attr(sanitize_file_name($qr->code).'-'.$download_size.'px.png'); ?>"><?php echo esc_html($download_label); ?></a><?php endforeach; ?>
                            <a class="net360-download-svg" href="<?php echo esc_url($image); ?>" download="<?php echo esc_attr(sanitize_file_name($qr->code)); ?>.svg">Vector SVG · Scalable</a></div></details>
                            <form method="post"><input type="hidden" name="net360qr_action" value="toggle"><input type="hidden" name="id" value="<?php echo esc_attr($qr->id); ?>"><?php wp_nonce_field('net360qr_front'); ?><button class="net360-btn"><?php echo $qr->status==='active'?'⏸ Pause':'▶ Activate'; ?></button></form>
                            <form method="post" onsubmit="return confirm('Delete this QR code? This action cannot be undone.')"><input type="hidden" name="net360qr_action" value="delete"><input type="hidden" name="id" value="<?php echo esc_attr($qr->id); ?>"><?php wp_nonce_field('net360qr_front'); ?><button class="net360-btn danger">Delete</button></form>
                        </div>
                    </div>
                </article>
            <?php endforeach; ?>
            </div>
            <div class="net360-image-modal" id="net360-image-modal" hidden aria-hidden="true">
                <div class="net360-image-modal-backdrop" data-modal-close></div>
                <div class="net360-image-modal-dialog" role="dialog" aria-modal="true" aria-labelledby="net360-image-modal-title">
                    <button type="button" class="net360-image-modal-close" data-modal-close aria-label="Close">&times;</button>
                    <div class="net360-image-modal-head"><span class="net360-kicker">PREVIEW</span><h2 id="net360-image-modal-title">QR Code</h2></div>
                    <div class="net360-image-modal-body"><img id="net360-image-modal-img" src="" alt="QR Code ampliado"></div>
                </div>
            </div>
        </div>
        <?php
        return ob_get_clean();
    }
}
