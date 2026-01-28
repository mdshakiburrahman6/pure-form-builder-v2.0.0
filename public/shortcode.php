<?php
/**
 * public/shortcode.php
 * Final Optimized Version: Fixed Profile Data Loop & Multi-Button Logic
 * Supporting 3-Tab Design Prefix (view_) and All Old Logics.
 */

if (!defined('ABSPATH')) exit;

// [pfb_form id="X"] - Standard form display
add_shortcode('pfb_form', function ($atts) {
    global $wpdb;
    $atts = shortcode_atts(['id' => 0, 'entry_id' => 0], $atts);
    $form_id = intval($atts['id']);
    if (!$form_id) return '<p>Form ID is missing.</p>';
    
    $entry_id = intval($atts['entry_id'] ?? 0);

    // Auto-fetch entry for logged-in users
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

// [pfb_my_entry form_id="X"] - Profile/Entry view handler
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

/**
 * Professional Render Entry View with Dynamic Designer Support
 */
if (!function_exists('pfb_render_entry_view')) {
    function pfb_render_entry_view($entry_id, $form_id = null) {
        global $wpdb;
        
        if (!$form_id) {
            $form_id = $wpdb->get_var($wpdb->prepare("SELECT form_id FROM {$wpdb->prefix}pfb_entries WHERE id=%d", $entry_id));
        }

        // Fetch advanced design data
        $form = $wpdb->get_row($wpdb->prepare("SELECT * FROM {$wpdb->prefix}pfb_forms WHERE id = %d", $form_id));
        if (!$form) return '<p>Form settings not found.</p>';

        $pre = 'view_'; // View Context Prefix
        
        // Dynamic Layout Calculations
        $cols = ($form->{$pre.'column_layout'} === '3-col') ? 3 : (($form->{$pre.'column_layout'} === '2-col') ? 2 : 1);
        $img_width = !empty($form->image_preview_width) ? $form->image_preview_width . '%' : '100%';
        $img_align = !empty($form->image_align) ? $form->image_align : 'center';

        // Fetch structure and meta
        $fieldsets = $wpdb->get_results($wpdb->prepare("SELECT * FROM {$wpdb->prefix}pfb_fields WHERE form_id = %d AND is_fieldset = 1 ORDER BY sort_order ASC", $form_id));
        $meta_rows = $wpdb->get_results($wpdb->prepare("SELECT field_name, field_value FROM {$wpdb->prefix}pfb_entry_meta WHERE entry_id = %d", $entry_id));
        
        $meta_map = [];
        foreach ($meta_rows as $m) { $meta_map[$m->field_name] = $m->field_value; }

        ob_start(); ?>
        
        <style>
            .pfb-profile-container-<?php echo $form_id; ?> {
                background-color: <?php echo esc_attr($form->{$pre.'input_bg_color'}); ?>;
                background-image: url('<?php echo esc_url($form->form_bg_image); ?>'); 
                background-size: cover; 
                padding: <?php echo intval($form->{$pre.'form_padding'}); ?>px;
                border-radius: <?php echo intval($form->border_radius); ?>px; 
            }

            .pfb-profile-grid {
                display: grid;
                grid-template-columns: repeat(<?php echo $cols; ?>, 1fr);
                gap: <?php echo intval($form->{$pre.'field_spacing'}); ?>px;
            }

            .pfb-section-title { 
                grid-column: 1 / -1;
                color: <?php echo esc_attr($form->{$pre.'heading_color'}); ?>; 
                font-size: <?php echo intval($form->{$pre.'heading_font_size'}); ?>px; 
                font-weight: <?php echo intval($form->{$pre.'heading_font_weight'}); ?>;
                margin-bottom: <?php echo intval($form->{$pre.'header_gap'}); ?>px;
                border-bottom: 2px solid <?php echo esc_attr($form->{$pre.'heading_color'}); ?>;
                padding-bottom: 5px;
            }

            .pfb-label { 
                color: <?php echo esc_attr($form->{$pre.'label_color'}); ?>; 
                font-size: <?php echo intval($form->{$pre.'label_font_size'}); ?>px; 
                font-weight: <?php echo intval($form->{$pre.'label_font_weight'}); ?>;
                display: block;
                margin-bottom: 5px;
            }

            .pfb-value {
                color: <?php echo esc_attr($form->{$pre.'text_color'}); ?>;
                font-size: <?php echo intval($form->{$pre.'text_font_size'}); ?>px; 
                font-weight: <?php echo intval($form->{$pre.'text_font_weight'}); ?>;
            }
            
            /* Profile Media Styling */
            .pfb-value img { 
                width: <?php echo $img_width; ?>; 
                margin: <?php echo ($img_align === 'center' ? '0 auto' : ($img_align === 'right' ? '0 0 0 auto' : '0 auto 0 0')); ?>;
                display: block;
                border-radius: 8px;
            }

            /* Buttons Designer */
            .pfb-btn-edit-custom { 
                background-color: <?php echo esc_attr($form->{$pre.'submit_btn_bg'}); ?> !important; 
                color: <?php echo esc_attr($form->{$pre.'submit_btn_clr'}); ?> !important; 
                border-radius: <?php echo intval($form->{$pre.'submit_btn_radius'}); ?>px !important;
                padding: 12px 30px;
                text-decoration: none;
                font-weight: 600;
                display: inline-block;
            }
            .pfb-btn-back-custom { 
                background-color: <?php echo esc_attr($form->{$pre.'cancel_btn_bg'}); ?> !important; 
                color: <?php echo esc_attr($form->{$pre.'cancel_btn_clr'}); ?> !important; 
                border-radius: <?php echo intval($form->{$pre.'cancel_btn_radius'}); ?>px !important;
                padding: 12px 30px;
                text-decoration: none;
                font-weight: 600;
                display: inline-block;
                border: 1px solid #ccc;
            }
        </style>

        <div class="pfb-profile-container-<?php echo $form_id; ?>">
            <div class="pfb-profile-card">
                <div class="pfb-profile-grid">
                    <?php foreach ($fieldsets as $section) : 
                        $section_fields = $wpdb->get_results($wpdb->prepare("SELECT * FROM {$wpdb->prefix}pfb_fields WHERE fieldset_id = %d AND is_fieldset = 0 ORDER BY sort_order ASC", $section->id));
                        
                        $has_data = false;
                        foreach($section_fields as $check) { if(!empty($meta_map[$check->name])) { $has_data = true; break; } }
                        if(!$has_data) continue;

                        echo '<h3 class="pfb-section-title">' . esc_html($section->label) . '</h3>';
                        
                        foreach ($section_fields as $f) : 
                            $val = $meta_map[$f->name] ?? '';
                            if (empty($val)) continue; ?>
                            <div class="pfb-info-item" style="margin-bottom: 15px;">
                                <span class="pfb-label"><?php echo esc_html($f->label); ?>:</span>
                                <div class="pfb-value">
                                    <?php 
                                    // OLD LOGIC: Handle Image URLs
                                    if(filter_var($val, FILTER_VALIDATE_URL) && preg_match('/\.(jpg|jpeg|png|gif|webp)$/i', $val)) {
                                        echo '<img src="'.esc_url($val).'">';
                                    } else {
                                        echo nl2br(esc_html($val));
                                    }
                                    ?>
                                </div>
                            </div>
                        <?php endforeach; 
                    endforeach; ?>
                </div>

                <div class="pfb-profile-actions" style="margin-top:30px; border-top:1px solid #ddd; padding-top:20px; display: flex; gap: 15px; justify-content: center;">
                    <a class="pfb-btn-edit-custom" href="<?php echo esc_url(add_query_arg('edit', 1)); ?>">
                        <?php echo esc_html($form->{$pre.'submit_btn_text'}); ?>
                    </a>
                    <a class="pfb-btn-back-custom" href="<?php echo esc_url(remove_query_arg(['entry_id', 'edit'])); ?>">
                        <?php echo esc_html($form->{$pre.'cancel_btn_text'}); ?>
                    </a>
                </div>
            </div>
        </div>
        <?php return ob_get_clean();
    }
}