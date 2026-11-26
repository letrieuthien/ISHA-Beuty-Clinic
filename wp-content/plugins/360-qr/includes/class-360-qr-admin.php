<?php
if (!defined('ABSPATH')) exit;

class NET360QR_ADMIN {
    public function __construct() {
        add_action('admin_menu', array($this,'menu'));
        add_action('admin_enqueue_scripts', array($this,'assets'));
        add_action('admin_enqueue_scripts', array($this,'logo_preload'));
        
        add_action('admin_post_net360qr_save', array($this,'save'));
        add_action('admin_post_net360qr_delete', array($this,'delete'));
        add_action('admin_post_net360qr_toggle', array($this,'toggle'));
        add_action('admin_post_net360qr_svg', array($this,'svg'));
        add_action('admin_post_net360qr_png', array($this,'png'));
        add_action('wp_ajax_net360qr_fetch_title', array($this,'fetch_title'));
    }

    public function menu() {
        add_menu_page('360 QR','360 QR','manage_options','360-qr',array($this,'page'),'dashicons-format-image',26);
    }

    public function assets($hook) {
        if ($hook === 'toplevel_page_360-qr') {
            wp_enqueue_style('360-qr-admin', NET360QR_URL.'assets/admin.css', array(), NET360QR_VERSION);
            wp_enqueue_script('360-qr-download', NET360QR_URL.'assets/qr-download.js', array(), NET360QR_VERSION, true);
            wp_enqueue_script('360-qr-shapes', NET360QR_URL.'assets/qr-shapes.js', array(), NET360QR_VERSION, true);
            wp_localize_script('360-qr-download', 'NET360QRAdmin', array(
                'ajaxUrl' => admin_url('admin-ajax.php'),
                'nonce' => wp_create_nonce('net360qr_title'),
                'i18n' => array(
                    'loading' => 'Detecting title…',
                    'found' => 'Title detected',
                    'notFound' => 'Page title not found.',
                    'error' => 'Could not fetch the page. Check the URL and try again.'
                )
            ));
        }
    }


    public function logo_preload($hook) {
        if ($hook !== 'toplevel_page_360-qr') return;
        echo '<link rel="preload" as="image" href="'.esc_url(NET360QR_URL.'assets/images/360-qr-logo.png').'">';
    }

    private function url($args=array()) {
        $url = add_query_arg($args, admin_url('admin.php?page=360-qr'));
        return wp_nonce_url($url, 'net360qr_admin_view');
    }

    private function code() {
        $chars='ABCDEFGHJKLMNPQRSTUVWXYZ23456789';
        do{$c='';for($i=0;$i<8;$i++)$c.=$chars[wp_rand(0,strlen($chars)-1)];}while(NET360QR_DB::code_exists($c));
        return $c;
    }

    public function save() {
        if(!current_user_can('manage_options')) wp_die('Permission denied.');
        check_admin_referer('net360qr_save');
        $id = isset($_POST['id']) ? absint(wp_unslash($_POST['id'])) : 0;
        $name = isset($_POST['name']) ? sanitize_text_field(wp_unslash($_POST['name'])) : '';
        $dest = isset($_POST['destination_url']) ? esc_url_raw(wp_unslash($_POST['destination_url'])) : '';
        $code = isset($_POST['code']) ? strtoupper(sanitize_key(wp_unslash($_POST['code']))) : '';
        if(!$name||!$dest||!preg_match('#^https?://#i',$dest)) wp_safe_redirect($this->url(array('error'=>'Enter a name and a valid URL (http/https).'))) && exit;
        if(!$code) $code=$this->code();
        $public=home_url('/q/'.$code);
        $check=NET360QR_LOCAL::validate_text($public);
        if(is_wp_error($check)) wp_safe_redirect($this->url(array('error'=>$check->get_error_message()))) && exit;
        if(NET360QR_DB::code_exists($code,$id)) wp_safe_redirect($this->url(array('error'=>'Duplicate code.'))) && exit;
        $existing = $id ? NET360QR_DB::get($id) : null;
        $logo_id = $existing ? absint($existing->logo_attachment_id ?? 0) : 0;
        if (!empty($_POST['remove_logo'])) $logo_id = 0;
        if (!empty($_FILES['net360qr_logo']['name'])) {
            require_once ABSPATH . 'wp-admin/includes/file.php';
            require_once ABSPATH . 'wp-admin/includes/media.php';
            require_once ABSPATH . 'wp-admin/includes/image.php';
            $new_logo = media_handle_upload('net360qr_logo', 0);
            if (is_wp_error($new_logo)) wp_safe_redirect($this->url(array('error'=>'Could not upload the logo: '.$new_logo->get_error_message()))) && exit;
            $mime = get_post_mime_type($new_logo);
            if (!in_array($mime, array('image/png','image/jpeg','image/webp'), true)) { wp_delete_attachment($new_logo, true); wp_safe_redirect($this->url(array('error'=>'The logo must be a PNG, JPG, or WebP image.'))) && exit; }
            $logo_id = (int) $new_logo;
        }
        $shape_input = isset($_POST['qr_shape']) ? sanitize_key(wp_unslash($_POST['qr_shape'])) : ($existing->qr_shape ?? 'classic');
        $shape = NET360QR_LOCAL::shape($shape_input);
        $data = array('name'=>$name,'code'=>$code,'destination_url'=>$dest,'logo_attachment_id'=>$logo_id,'qr_shape'=>$shape);
        if($id) NET360QR_DB::update($id,$data);
        else NET360QR_DB::insert(array_merge($data,array('user_id'=>get_current_user_id(),'status'=>'active')));
        wp_safe_redirect($this->url(array('saved'=>1))); exit;
    }

    public function delete() {
        if(!current_user_can('manage_options')) wp_die('Permission denied.');
        $id = isset($_GET['id']) ? absint(wp_unslash($_GET['id'])) : 0; check_admin_referer('net360qr_delete_'.$id); if($id) NET360QR_DB::delete($id);
        wp_safe_redirect($this->url()); exit;
    }

    public function toggle() {
        if(!current_user_can('manage_options')) wp_die('Permission denied.');
        $id = isset($_GET['id']) ? absint(wp_unslash($_GET['id'])) : 0; check_admin_referer('net360qr_toggle_'.$id); $qr=NET360QR_DB::get($id);
        if($qr) NET360QR_DB::update($id,array('status'=>$qr->status==='active'?'inactive':'active'));
        wp_safe_redirect($this->url()); exit;
    }

    public function svg() {
        if(!current_user_can('manage_options')) wp_die('Permission denied.');
        $id = isset($_GET['id']) ? absint(wp_unslash($_GET['id'])) : 0; check_admin_referer('net360qr_svg_'.$id); $qr=NET360QR_DB::get($id); if(!$qr) wp_die('QR code not found.');
        $public=home_url('/q/'.rawurlencode($qr->code)); nocache_headers(); header('Content-Type: image/svg+xml; charset=utf-8');
        header('Content-Disposition: attachment; filename="net360-'.sanitize_file_name($qr->code).'.svg"');
        // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- SVG is generated internally by the plugin and must remain valid XML.
        echo NET360QR_LOCAL::svg($public, 1000, '#111827', '#FFFFFF', absint($qr->logo_attachment_id ?? 0), NET360QR_LOCAL::shape($qr->qr_shape ?? 'classic'));
        exit;
    }

    public function png() {
        if(!current_user_can('manage_options')) wp_die('Permission denied.');
        $id = isset($_GET['id']) ? absint(wp_unslash($_GET['id'])) : 0; check_admin_referer('net360qr_png_'.$id); $qr=NET360QR_DB::get($id); if(!$qr) wp_die('QR code not found.');
        $public=home_url('/q/'.rawurlencode($qr->code));
        $size = isset($_GET['size']) ? absint(wp_unslash($_GET['size'])) : 1200;
        if (!in_array($size, array(512, 1024, 2048), true)) { $size = 1200; }
        $png=NET360QR_LOCAL::png($public,$size,absint($qr->logo_attachment_id ?? 0), NET360QR_LOCAL::shape($qr->qr_shape ?? 'classic')); if(is_wp_error($png)) wp_die(esc_html($png->get_error_message()));
        nocache_headers();
        header('Content-Type: image/png');
        header('Content-Disposition: attachment; filename="net360-'.sanitize_file_name($qr->code).'-'.$size.'px.png"');
        // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- PNG is binary data and must not be escaped.
        echo $png;
        exit;
    }

    public function fetch_title() {
        if (!current_user_can('manage_options')) wp_send_json_error(array('message'=>'Permission denied.'), 403);
        check_ajax_referer('net360qr_title', 'nonce');
        $url = isset($_POST['url']) ? esc_url_raw(wp_unslash($_POST['url'])) : '';
        if (!$url || !preg_match('#^https?://#i', $url)) wp_send_json_error(array('message'=>'Invalid URL.'), 400);
        if (function_exists('wp_http_validate_url') && !wp_http_validate_url($url)) wp_send_json_error(array('message'=>'URL is not allowed.'), 400);
        $response = wp_safe_remote_get($url, array(
            'timeout' => 12,
            'redirection' => 5,
            'limit_response_size' => 2000000,
            'user-agent' => 'Mozilla/5.0 (compatible; 360 QR/'.NET360QR_VERSION.'; +'.home_url('/').')',
            'headers' => array('Accept' => 'text/html,application/xhtml+xml;q=0.9,*/*;q=0.8')
        ));
        if (is_wp_error($response)) {
            wp_send_json_error(array('message'=>'Could not fetch the URL: '.$response->get_error_message()), 502);
        }
        $code = (int) wp_remote_retrieve_response_code($response);
        if ($code < 200 || $code >= 400) wp_send_json_error(array('message'=>'The website returned HTTP '.$code.'.'), 502);
        $body = wp_remote_retrieve_body($response);
        if (!$body) wp_send_json_error(array('message'=>'The page returned no content.'), 502);
        $title = '';
        // First, try the <title> element, which is the most reliable source for this field.
        if (preg_match('/<title\b[^>]*>(.*?)<\/title\s*>/is', $body, $m)) {
            $title = trim(wp_strip_all_tags(html_entity_decode($m[1], ENT_QUOTES | ENT_HTML5, 'UTF-8')));
        }
        // Fallback for sites without a <title> element but with Open Graph metadata.
        if (!$title && preg_match('/<meta[^>]+property=[\"\']og:title[\"\'][^>]+content=[\"\']([^\"\']+)[\"\']/i', $body, $m)) {
            $title = trim(wp_strip_all_tags(html_entity_decode($m[1], ENT_QUOTES | ENT_HTML5, 'UTF-8')));
        }
        // Last fallback: first visible H1 in the HTML.
        if (!$title && preg_match('/<h1\b[^>]*>(.*?)<\/h1\s*>/is', $body, $m)) {
            $title = trim(wp_strip_all_tags(html_entity_decode($m[1], ENT_QUOTES | ENT_HTML5, 'UTF-8')));
        }
        $title = preg_replace('/\s+/u', ' ', $title);
        $title = trim($title);
        if (!$title) wp_send_json_error(array('message'=>'No page title was found.'), 404);
        wp_send_json_success(array('title'=>sanitize_text_field($title)));
    }

    public function page() {
        $has_view_args = isset($_GET['edit']) || isset($_GET['error']) || isset($_GET['saved']) || isset($_GET['paged']);
        if ($has_view_args) {
            check_admin_referer('net360qr_admin_view');
        }
        $edit_id = isset($_GET['edit']) ? absint(wp_unslash($_GET['edit'])) : 0;
        $edit = $edit_id ? NET360QR_DB::get($edit_id) : null;
        $total_qrs=NET360QR_DB::count();
        $per_page=10;
        $current_page = isset($_GET['paged']) ? max(1, absint(wp_unslash($_GET['paged']))) : 1;
        $total_pages=max(1,(int)ceil($total_qrs/$per_page));
        if($current_page>$total_pages) $current_page=$total_pages;
        $qrs=NET360QR_DB::paginate($per_page,$current_page);
        $all_qrs=NET360QR_DB::all();
        $scans=0;$active=0;foreach($all_qrs as $q){$scans+=(int)$q->scans;if($q->status==='active')$active++;}
        ?>
        <div class="wrap net360-admin net360-app-page">
            <div class="net360-plugin-version">v<?php echo esc_html(NET360QR_VERSION); ?></div>
            <div class="net360-admin-hero net360-dashboard-hero-v2">
                <div class="net360-dashboard-brand">
                    <img src="<?php echo esc_url(NET360QR_URL.'assets/images/360-qr-logo.png'); ?>" alt="360 QR">
                    <div><div class="net360-eyebrow">NET360 PLATFORM</div><h1>360 QR</h1><p>Manage your dynamic QR codes directly in WordPress.</p></div>
                </div>
                <div class="net360-hero-actions"><a class="button button-primary net360-primary-action" href="#net360-create-qr"><span class="net360qr-plus-icon">+</span><span>Create New QR</span></a></div>
            </div>
            <?php
            $saved = !empty($_GET['saved']); if($saved): ?><div class="notice notice-success is-dismissible"><p>QR code saved successfully.</p></div><?php endif; ?>
            <?php
            $error = isset($_GET['error']) ? sanitize_text_field(wp_unslash($_GET['error'])) : ''; if($error): ?><div class="notice notice-error is-dismissible"><p><?php echo esc_html($error); ?></p></div><?php endif; ?>
            <div class="net360-admin-stats"><div class="net360-admin-stat"><span>QR Codes</span><strong><?php echo esc_html(number_format_i18n($total_qrs));?></strong></div><div class="net360-admin-stat"><span>Active QR Codes</span><strong><?php echo esc_html(number_format_i18n($active));?></strong></div><div class="net360-admin-stat"><span>Total Scans</span><strong><?php echo esc_html(number_format_i18n($scans));?></strong></div></div>
            <div class="net360-admin-card net360-create-card" id="net360-create-qr">
                <div class="net360-section-head"><div><div class="net360-section-kicker">SETTINGS</div><h2><?php echo $edit?'Edit Dynamic QR Code':'Create Dynamic QR Code'; ?></h2></div><span class="net360-section-icon dashicons dashicons-admin-links"></span></div>
                <p class="net360-admin-muted">The QR code uses your system URL, so you can change its destination later without reprinting it.</p>
                <form method="post" enctype="multipart/form-data" action="<?php echo esc_url(admin_url('admin-post.php')); ?>"><input type="hidden" name="action" value="net360qr_save"><input type="hidden" name="id" value="<?php echo esc_attr($edit->id??0);?>"><?php wp_nonce_field('net360qr_save'); ?>
                    <div class="net360-admin-form-grid">
                        <p><label><strong>Name</strong><br><input class="regular-text" name="name" id="net360qr_name" required value="<?php echo esc_attr($edit->name??''); ?>" placeholder="Menu 2026"></label></p>
                        <p><label><strong>Code</strong> <span class="net360-admin-muted">(optional)</span><br><input class="regular-text" name="code" maxlength="32" value="<?php echo esc_attr($edit->code??''); ?>" placeholder="Automatic"></label></p>
                        <p class="full"><label><strong>Destination URL</strong><br><div class="net360qr-url-field"><input class="large-text" type="url" name="destination_url" id="net360qr_destination" required value="<?php echo esc_attr($edit->destination_url??''); ?>" placeholder="https://yourwebsite.com/menu"><button type="button" class="button three60-detect-title" id="net360qr_detect_title">Detect Title</button></div><span id="net360qr_title_status" class="three60-title-status" aria-live="polite"></span></label></p>
                        <div class="full net360qr-shape-field"><strong>QR style</strong><?php
                        // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- shape_picker returns internally generated HTML; all dynamic labels and attributes are escaped and SVG is generated from validated values.
                        echo NET360QR_LOCAL::shape_picker($edit->qr_shape ?? 'classic'); ?></div>
                        <p class="full"><label><strong>Center Logo (Optional)</strong><br><input type="file" name="net360qr_logo" accept="image/png,image/jpeg,image/webp"><span class="net360-admin-muted">Leave this blank to create a QR code without a logo. Example: a small centered logo on a white background; transparent PNG recommended.</span></label><div class="net360qr-logo-example" aria-label="Optional centered logo example"><div class="net360qr-example-code"><span>360</span></div><div><strong>Centered Logo Example</strong><br><small>Illustration only. The logo is added only if you upload an image.</small></div></div><?php if(!empty($edit->logo_attachment_id) && wp_get_attachment_url($edit->logo_attachment_id)): ?><br><img src="<?php echo esc_url(wp_get_attachment_url($edit->logo_attachment_id)); ?>" alt="Current logo" style="max-width:90px;max-height:90px;margin-top:8px"><br><label><input type="checkbox" name="remove_logo" value="1"> Remove logo</label><?php endif; ?></p>
                    </div>
                    <p><button class="button button-primary button-large"><?php echo $edit?'Save Changes':'Create QR';?></button> <?php if($edit):?><a class="button button-large" href="<?php echo esc_url($this->url());?>">Cancel</a><?php endif;?></p>
                </form>
            </div>
            <div class="net360-admin-card net360-list-card"><div class="net360-section-head"><div><div class="net360-section-kicker">MY QR CODES</div><h2>QR Codes</h2><p class="net360-admin-muted">Manage, pause, and download your dynamic QR codes.</p></div><span class="net360-section-icon dashicons dashicons-format-image"></span></div><div class="net360-admin-table"><table class="widefat striped"><thead><tr><th>QR</th><th>Name</th><th>Code</th><th>Destination</th><th>Status</th><th>Scans</th><th>Last Scan</th><th>Actions</th></tr></thead><tbody>
            <?php if(!$qrs):?><tr><td colspan="8">No QR codes yet.</td></tr><?php endif;?>
            <?php foreach($qrs as $qr):$public=home_url('/q/'.rawurlencode($qr->code));$image=home_url('/q-image/'.rawurlencode($qr->code));?>
                <tr><td><button type="button" class="net360-image-trigger" data-image="<?php echo esc_url($image); ?>" data-title="<?php echo esc_attr($qr->name); ?>" aria-label="View QR <?php echo esc_attr($qr->name); ?>"><img class="net360-admin-qr" src="<?php echo esc_url($image);?>" alt="QR <?php echo esc_attr($qr->name);?>"></button></td><td><strong><?php echo esc_html($qr->name);?></strong><div class="net360-admin-muted">Created <?php echo esc_html(mysql2date('d/m/Y H:i',$qr->created_at));?></div></td><td><code><?php echo esc_html($qr->code);?></code><br><button type="button" class="button-link net360-admin-copy" data-copy="<?php echo esc_attr($public);?>">Copy QR URL</button></td><td style="max-width:280px;word-break:break-word"><?php echo esc_html($qr->destination_url);?></td><td><span class="net360-admin-status <?php echo $qr->status==='active'?'active':'inactive';?>"><?php echo $qr->status==='active'?'● Active':'● Inactive';?></span></td><td><?php echo esc_html(number_format_i18n((int)$qr->scans));?></td><td><?php echo $qr->last_scan_at?esc_html(mysql2date('d/m/Y H:i',$qr->last_scan_at)):'—';?></td><td><div class="net360-admin-actions" role="group" aria-label="Actions for <?php echo esc_attr($qr->name); ?>">
                    <a class="button button-small net360-action-btn" title="Edit" aria-label="Edit" href="<?php echo esc_url($this->url(array('edit'=>$qr->id)).'#net360-create-qr');?>"><span class="dashicons dashicons-edit" aria-hidden="true"></span></a>
                    <button type="button" class="button button-small net360-action-btn net360-image-trigger" title="View QR" aria-label="View QR" data-image="<?php echo esc_url($image); ?>" data-title="<?php echo esc_attr($qr->name); ?>"><span class="dashicons dashicons-visibility" aria-hidden="true"></span></button>
                    <details class="net360-download-menu"><summary class="button button-small net360-action-btn" title="Download Options" aria-label="Download Options"><span class="dashicons dashicons-download" aria-hidden="true"></span></summary><div class="net360-download-options"><strong>Download PNG</strong>
                    <?php foreach (array(512=>'Standard · 512 px',1024=>'High Quality · 1024 px',2048=>'Maximum Quality · 2048 px') as $download_size=>$download_label): ?><a href="<?php echo esc_url(wp_nonce_url(admin_url('admin-post.php?action=net360qr_png&id='.$qr->id.'&size='.$download_size),'net360qr_png_'.$qr->id));?>"><?php echo esc_html($download_label); ?></a><?php endforeach; ?>
                    <a class="net360-download-svg" href="<?php echo esc_url(wp_nonce_url(admin_url('admin-post.php?action=net360qr_svg&id='.$qr->id),'net360qr_svg_'.$qr->id));?>">Vector SVG · Scalable</a></div></details>
                    <a class="button button-small net360-action-btn" title="<?php echo $qr->status==='active'?'Pause':'Activate'; ?>" aria-label="<?php echo $qr->status==='active'?'Pause':'Activate'; ?>" href="<?php echo esc_url(wp_nonce_url(admin_url('admin-post.php?action=net360qr_toggle&id='.$qr->id),'net360qr_toggle_'.$qr->id));?>"><span class="dashicons <?php echo $qr->status==='active'?'dashicons-controls-pause':'dashicons-controls-play'; ?>" aria-hidden="true"></span></a>
                    <a class="button button-small net360-action-btn net360-action-delete" title="Delete" aria-label="Delete" onclick="return confirm('Delete this QR code?')" href="<?php echo esc_url(wp_nonce_url(admin_url('admin-post.php?action=net360qr_delete&id='.$qr->id),'net360qr_delete_'.$qr->id));?>"><span class="dashicons dashicons-trash" aria-hidden="true"></span></a>
                </div></td></tr>
            <?php endforeach;?></tbody></table></div>
                <?php if($total_pages>1): ?>
                    <nav class="net360-pagination" aria-label="QR code pagination">
                        <?php
                        $pagination_base=$this->url();
                        $window=2;
                        $pages_to_show=array(1,$total_pages);
                        for($p=$current_page-$window;$p<=$current_page+$window;$p++){if($p>1&&$p<$total_pages)$pages_to_show[]=$p;}
                        $pages_to_show=array_values(array_unique($pages_to_show)); sort($pages_to_show);
                        $previous=max(1,$current_page-1); $next=min($total_pages,$current_page+1);
                        ?>
                        <a class="net360-page-btn <?php echo $current_page===1?'disabled':''; ?>" href="<?php echo esc_url(add_query_arg('paged',$previous,$pagination_base)); ?>" aria-label="Previous page"><span class="dashicons dashicons-arrow-left-alt2" aria-hidden="true"></span></a>
                        <?php $last=0; foreach($pages_to_show as $p): if($last && $p>$last+1): ?><span class="net360-page-gap">…</span><?php endif; ?><a class="net360-page-btn <?php echo $p===$current_page?'current':''; ?>" href="<?php echo esc_url(add_query_arg('paged',$p,$pagination_base)); ?>" aria-current="<?php echo $p===$current_page?'page':'false'; ?>"><?php echo esc_html(number_format_i18n($p)); ?></a><?php $last=$p; endforeach; ?>
                        <a class="net360-page-btn <?php echo $current_page===$total_pages?'disabled':''; ?>" href="<?php echo esc_url(add_query_arg('paged',$next,$pagination_base)); ?>" aria-label="Next page"><span class="dashicons dashicons-arrow-right-alt2" aria-hidden="true"></span></a>
                    </nav>
                    <div class="net360-pagination-meta">Showing <?php echo esc_html(number_format_i18n(min($per_page,$total_qrs-($current_page-1)*$per_page))); ?> of <?php echo esc_html(number_format_i18n($total_qrs)); ?> QR codes</div>
                <?php endif; ?>
            </div></div>
            <div class="net360-image-modal" id="net360-image-modal" hidden aria-hidden="true">
                <div class="net360-image-modal-backdrop" data-modal-close></div>
                <div class="net360-image-modal-dialog" role="dialog" aria-modal="true" aria-labelledby="net360-image-modal-title">
                    <button type="button" class="net360-image-modal-close" data-modal-close aria-label="Close">&times;</button>
                    <div class="net360-image-modal-head"><span class="net360-section-kicker">PREVIEW</span><h2 id="net360-image-modal-title">QR Code</h2></div>
                    <div class="net360-image-modal-body"><img id="net360-image-modal-img" src="" alt="QR Code ampliado"></div>
                </div>
            </div>
        </div>
        <?php
    }
}
