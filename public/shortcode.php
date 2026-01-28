<?php
/**
 * public/shortcode.php
 * Final Version: Fixed Section Logic + Image/Gallery Preview + Download Buttons
 */

if (!defined('ABSPATH')) exit;

// [pfb_form id="X"]
add_shortcode('pfb_form', function ($atts) {
    global $wpdb;
    $atts = shortcode_atts(['id' => 0, 'entry_id' => 0], $atts);
    $form_id = intval($atts['id']);
    if (!$form_id) return '<p>Form ID is missing.</p>';
    $entry_id = intval($atts['entry_id'] ?? 0);

    if (is_user_logged_in() && !$entry_id) {
        $user_id = get_current_user_id();
        $existing_id = $wpdb->get_var($wpdb->prepare("SELECT id FROM {$wpdb->prefix}pfb_entries WHERE form_id = %d AND user_id = %d", $form_id, $user_id));
        if ($existing_id) $entry_id = $existing_id;
    }

    $form = $wpdb->get_row($wpdb->prepare("SELECT * FROM {$wpdb->prefix}pfb_forms WHERE id = %d", $form_id));
    if (!$form) return '<p>Invalid form.</p>';

    $id = $form_id;
    ob_start();
    include PFB_PATH . 'public/renderer.php';
    return ob_get_clean();
});

// [pfb_my_entry form_id="X"]
add_shortcode('pfb_my_entry', 'pfb_render_my_entry');

function pfb_render_my_entry($atts) {
    if (!is_user_logged_in()) return '<p>Please login to view your profile.</p>';
    global $wpdb;
    $form_id = intval($atts['form_id'] ?? 0);
    if (!$form_id) {
        $page_id = get_queried_object_id();
        if ($page_id) $form_id = intval(get_post_meta($page_id, 'pfb_form_id', true));
    }
    if (!$form_id) return '<p>Form not assigned to this page.</p>';

    $user_id = get_current_user_id();
    $entry_id = $wpdb->get_var($wpdb->prepare("SELECT id FROM {$wpdb->prefix}pfb_entries WHERE form_id = %d AND user_id = %d", $form_id, $user_id));
    
    if (!$entry_id) return do_shortcode('[pfb_form id="'.$form_id.'"]');
    
    if (isset($_GET['edit']) && $_GET['edit'] == 1) {
        return do_shortcode('[pfb_form id="'.$form_id.'" entry_id="'.$entry_id.'"]');
    }
    return pfb_render_entry_view($entry_id, $form_id);
}

// PROFILE VIEW WITH IMAGE PREVIEW & DOWNLOAD
if (!function_exists('pfb_render_entry_view')) {
    function pfb_render_entry_view($entry_id, $form_id = null) {
        global $wpdb;
        if (!$form_id) {
            $form_id = $wpdb->get_var($wpdb->prepare("SELECT form_id FROM {$wpdb->prefix}pfb_entries WHERE id=%d", $entry_id));
        }

        // 1. Fetch Section Headers (Fieldsets)
        $fieldsets = $wpdb->get_results($wpdb->prepare(
            "SELECT * FROM {$wpdb->prefix}pfb_fields WHERE form_id = %d AND is_fieldset = 1 ORDER BY sort_order ASC, id ASC",
            $form_id
        ));

        // 2. Fetch User Meta Data
        $meta_rows = $wpdb->get_results($wpdb->prepare("SELECT field_name, field_value FROM {$wpdb->prefix}pfb_entry_meta WHERE entry_id = %d", $entry_id));
        $meta_map = [];
        foreach ($meta_rows as $m) { $meta_map[$m->field_name] = $m->field_value; }

        ob_start(); ?>
        <div class="pfb-profile-container">
            <div class="pfb-profile-card">
                <h2 class="pfb-profile-title" style="text-align:center; margin-bottom:30px;">User Profile</h2>
                
                <?php foreach ($fieldsets as $section) : 
                    // 3. Fetch fields for this section
                    $section_fields = $wpdb->get_results($wpdb->prepare(
                        "SELECT * FROM {$wpdb->prefix}pfb_fields WHERE fieldset_id = %d AND is_fieldset = 0 ORDER BY sort_order ASC, id ASC",
                        $section->id
                    ));

                    // Check if section has data
                    $has_section_data = false;
                    foreach ($section_fields as $f) { if (!empty($meta_map[$f->name])) { $has_section_data = true; break; } }

                    // Hide if empty logic
                    if ($section->fieldset_display === 'hide_if_empty' && !$has_section_data) continue; 
                ?>
                    <h3 class="pfb-section-title" style="border-bottom: 2px solid #eee; padding-bottom: 8px; margin-top: 30px; color: #2271b1; font-weight:700;">
                        <?php echo esc_html($section->label); ?>
                    </h3>

                    <div class="pfb-profile-grid">
                        <?php foreach ($section_fields as $f): 
                            $val = $meta_map[$f->name] ?? '';
                            $is_media = in_array($f->type, ['image', 'file', 'gallery']);
                        ?>
                            <div class="pfb-info-item <?php echo $is_media ? 'full-width' : ''; ?>" <?php if (empty($val) && $section->fieldset_display !== 'show_always') echo 'style="display:none;"'; ?>>
                                <span class="pfb-label"><?php echo esc_html($f->label); ?></span>
                                
                                <div class="pfb-value">
                                <?php if (!empty($val)) : ?>
                                    <?php if ($f->type === 'image') : ?>
                                        <div class="pfb-profile-image">
                                            <img src="<?php echo esc_url($val); ?>" style="max-width:220px; border-radius:12px; border:1px solid #ddd; display:block; margin-bottom:10px;">
                                            <a href="<?php echo esc_url($val); ?>" download class="pfb-btn-download" style="padding:5px 12px; background:#2271b1; color:#fff; text-decoration:none; border-radius:4px; font-size:12px; display:inline-block;">📥 Download Image</a>
                                        </div>

                                    <?php elseif ($f->type === 'file') : ?>
                                        <div class="pfb-profile-file">
                                            <a href="<?php echo esc_url($val); ?>" download class="pfb-btn-download" style="padding:8px 15px; background:#2271b1; color:#fff; text-decoration:none; border-radius:4px; font-size:13px; display:inline-block;">📥 Download Attached File</a>
                                        </div>

                                    <?php elseif ($f->type === 'gallery') : 
                                        $imgs = json_decode($val, true);
                                        if (is_array($imgs)) : ?>
                                            <div class="pfb-gallery-preview" style="display:flex; gap:10px; flex-wrap:wrap; margin-top:10px;">
                                                <?php foreach ($imgs as $img_url) : ?>
                                                    <div style="text-align:center;">
                                                        <img src="<?php echo esc_url($img_url); ?>" style="width:100px; height:100px; object-fit:cover; border-radius:8px; border:1px solid #eee; display:block; margin-bottom:5px;">
                                                        <a href="<?php echo esc_url($img_url); ?>" download style="font-size:10px; color:#2271b1; text-decoration:none;">Download</a>
                                                    </div>
                                                <?php endforeach; ?>
                                            </div>
                                        <?php endif; ?>

                                    <?php else : ?>
                                        <span class="pfb-text-value"><?php echo esc_html($val); ?></span>
                                    <?php endif; ?>
                                <?php else : ?>
                                    <span style="color:#ccc; font-style:italic;">N/A</span>
                                <?php endif; ?>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                <?php endforeach; ?>

                <div class="pfb-profile-actions" style="margin-top:40px; text-align:center;">
                    <a class="pfb-btn-edit" href="<?php echo esc_url(add_query_arg('edit', 1)); ?>" style="background:#111827; color:#fff; padding:12px 35px; border-radius:10px; text-decoration:none; font-weight:600;">
                        ✏️ Edit My Profile
                    </a>
                </div>
            </div>
        </div>
        <?php return ob_get_clean();
    }
}