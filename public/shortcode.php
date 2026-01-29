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
    if (!is_user_logged_in()) {
        return '<p>Please login to view your profile.</p>';
    }

    global $wpdb;

    // ১. ফর্ম আইডি ডিটেকশন (প্লাগিনের সব ফাইলের সাথে সিঙ্ক করা)
    $atts    = shortcode_atts(['form_id' => 0], $atts);
    $form_id = intval($atts['form_id']);

    // যদি শর্টকোডে আইডি না থাকে, তবে পেজ মেটা থেকে আইডি নেওয়ার চেষ্টা করবে
    if (!$form_id) {
        $page_id = get_the_ID();
        if ($page_id) {
            $form_id = intval(get_post_meta($page_id, 'pfb_form_id', true));
        }
    }

    // এখনো আইডি না থাকলে এরর দেখাবে
    if (!$form_id) {
        return '<p>Error: Please provide a valid form_id in the shortcode.</p>';
    }

    $user_id  = get_current_user_id();

    // ২. ডাটাবেস থেকে ওই নির্দিষ্ট ফর্মের এন্ট্রি খোঁজা
    $entry_id = $wpdb->get_var(
        $wpdb->prepare(
            "SELECT id FROM {$wpdb->prefix}pfb_entries WHERE form_id = %d AND user_id = %d ORDER BY id DESC LIMIT 1",
            $form_id,
            $user_id
        )
    );

    // ৩. যদি ডাটা না থাকে, তবে নতুন সাবমিশন ফর্ম দেখাবে
    if (!$entry_id) {
        return do_shortcode('[pfb_form id="' . $form_id . '"]');
    }

    // ৪. এডিট মোড হ্যান্ডেল করা
    if (isset($_GET['edit']) && intval($_GET['edit']) === 1) {
        return do_shortcode('[pfb_form id="' . $form_id . '" entry_id="' . $entry_id . '"]');
    }

    // ৫. ভিউ মোড রেন্ডার করা
    return pfb_render_entry_view($entry_id, $form_id);
}

/**
 * Professional Render Entry View with Dynamic Designer Support
 */
/**
 * Professional Render Entry View with Alignment, Size Control, and Download Support
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

        $pre = 'view_'; // Context Prefix for Profile View
        
        // Dynamic Layout Calculations
        $cols = ($form->{$pre.'column_layout'} === '3-col') ? 3 : (($form->{$pre.'column_layout'} === '2-col') ? 2 : 1);
        
        // Fetch structure and meta
        $fieldsets = $wpdb->get_results($wpdb->prepare("SELECT * FROM {$wpdb->prefix}pfb_fields WHERE form_id = %d AND is_fieldset = 1 ORDER BY sort_order ASC", $form_id));
        $meta_rows = $wpdb->get_results($wpdb->prepare("SELECT field_name, field_value FROM {$wpdb->prefix}pfb_entry_meta WHERE entry_id = %d", $entry_id));
        
        $meta_map = [];
        foreach ($meta_rows as $m) { $meta_map[$m->field_name] = $m->field_value; }

        ob_start(); ?>
        
        <style>
            .pfb-profile-container-<?php echo $form_id; ?> {
                background-color: <?php echo ($form->{$pre.'input_bg_transparent'} == 1) ? 'transparent' : esc_attr($form->{$pre.'input_bg_color'}); ?>;
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

            .pfb-info-item {
                margin-bottom: 25px;
                /* Container alignment for images and text */
                text-align: <?php echo esc_attr($form->{$pre.'text_align'}); ?>;
            }

            .pfb-section-title { 
                grid-column: 1 / -1;
                color: <?php echo esc_attr($form->{$pre.'heading_color'}); ?>; 
                font-size: <?php echo intval($form->{$pre.'heading_font_size'}); ?>px; 
                font-weight: <?php echo intval($form->{$pre.'heading_font_weight'}); ?>;
                text-align: <?php echo esc_attr($form->{$pre.'heading_align'}); ?>;
                margin-bottom: <?php echo intval($form->{$pre.'header_gap'}); ?>px;
                /* border-bottom: 2px solid <?php echo esc_attr($form->{$pre.'heading_color'}); ?>; */
                padding-bottom: 5px;
            }

            .pfb-label { 
                display: block; 
                text-align: <?php echo esc_attr($form->{$pre.'label_align'}); ?>;
                color: <?php echo esc_attr($form->{$pre.'label_color'}); ?>;
                font-size: <?php echo intval($form->{$pre.'label_font_size'}); ?>px;
                font-weight: <?php echo intval($form->{$pre.'label_font_weight'}); ?>;
                margin-bottom: 8px;
            }

            .pfb-value {
                color: <?php echo esc_attr($form->{$pre.'text_color'}); ?>;
                font-size: <?php echo intval($form->{$pre.'text_font_size'}); ?>px; 
                font-weight: <?php echo intval($form->{$pre.'text_font_weight'}); ?>;
            }
            
            /* Professional Image Styling with Size Control */
            .pfb-view-image { 
                display: inline-block; /* Essential to allow parent text-align to work */
                width: <?php echo !empty($form->{$pre.'image_preview_width'}) ? intval($form->{$pre.'image_preview_width'}) . '%' : '100%'; ?> !important;
                max-width: 100%;
                height: auto !important;
                border-radius: 8px;
                border: 1px solid #ddd;
                margin-top: 10px;
                
            }
            .pfb-view-image-div{
                text-align: <?php echo esc_attr($form->{$pre.'text_align'}); ?>;
            }
            .pfb-download-wrapper {
                margin-top: 10px;
                display: block;
                text-align: <?php echo esc_attr($form->{$pre.'text_align'}); ?>;
            }

            .pfb-btn-download-profile {
                display: inline-flex;
                align-items: center;
                gap: 6px;
                background: #f1f1f1;
                padding: 4px 12px !important;
                border-radius: 4px;
                text-decoration: none;
                font-size: 14px !important;
                color: #2271b1;
                border: 1px solid #ccc;
                font-weight: 600;
            }

            .pfb-btn-download-view {
                display: inline-flex;
                align-items: center;
                gap: 6px;
                background: #f1f1f1;
                padding: 6px 14px;
                border-radius: 4px;
                text-decoration: none;
                font-size: 12px;
                color: #2271b1;
                border: 1px solid #ccc;
                font-weight: 600;
            }

            /* Buttons Designer */
            .pfb-view-footer { 
                display: flex;
                margin-top: 30px;
                gap: 15px;
                justify-content: <?php echo esc_attr($form->{$pre.'submit_btn_align'}); ?>; 
            }
            .pfb-btn-edit-custom { 
                background-color: <?php echo esc_attr($form->{$pre.'submit_btn_bg'}); ?> !important; 
                color: <?php echo esc_attr($form->{$pre.'submit_btn_clr'}); ?> !important; 
                border-radius: <?php echo intval($form->{$pre.'submit_btn_radius'}); ?>px !important;
                font-size: <?php echo intval($form->{$pre.'submit_btn_size'}); ?>px !important; /* Dynamically added */
                font-weight: <?php echo intval($form->{$pre.'submit_btn_weight'}); ?> !important; /* Dynamically added */
                padding: 12px 30px;
                text-decoration: none;
            }
            .pfb-btn-back-custom { 
                background-color: <?php echo esc_attr($form->{$pre.'cancel_btn_bg'}); ?> !important; 
                color: <?php echo esc_attr($form->{$pre.'cancel_btn_clr'}); ?> !important; 
                border-radius: <?php echo intval($form->{$pre.'cancel_btn_radius'}); ?>px !important;
                padding: 12px 30px;
                text-decoration: none;
                font-weight: 600;
                border: 1px solid #ccc;
            }
            
        </style>

        <div class="pfb-profile-container-<?php echo $form_id; ?>">
            <div class="pfb-profile-card">
                <div class="pfb-profile-grid">
                    <?php
                        /**
                         * Loop through fieldsets to render sections in View Profile
                         */
                        foreach ($fieldsets as $section) : 
                            // Fetch non-fieldset fields for the current section
                            $section_fields = $wpdb->get_results($wpdb->prepare("SELECT * FROM {$wpdb->prefix}pfb_fields WHERE fieldset_id = %d AND is_fieldset = 0 ORDER BY sort_order ASC", $section->id));
                            
                            // Check if current section has any non-empty meta data
                            $has_data = false;
                            foreach($section_fields as $check) { if(!empty($meta_map[$check->name])) { $has_data = true; break; } }
                            if(!$has_data) continue;

                            // Calculate background style and opacity for section headers
                            $sec_bg_style = "";
                            if (!empty($section->section_bg_image)) {
                                $opacity = floatval($section->section_bg_opacity);
                                // Apply white linear gradient mask based on opacity settings
                                $sec_bg_style = "background: linear-gradient(rgba(255,255,255," . (1 - $opacity) . "), rgba(255,255,255," . (1 - $opacity) . ")), url('" . esc_url($section->section_bg_image) . "'); background-size: cover; background-position: center; padding: 25px; border-radius: 10px; margin-bottom: 30px; border: 1px solid #eee;";
                            } else {
                                $sec_bg_style = "margin-bottom: 30px;";
                            }
                        ?>
                            <div class="pfb-section-view-wrapper" style="<?php echo $sec_bg_style; ?>">
                                
                                <h3 class="pfb-section-title"><?php echo esc_html($section->label); ?></h3>
                                
                                <div class="pfb-section-grid-inner" style="display: grid; grid-template-columns: repeat(<?php echo $cols; ?>, 1fr); gap: <?php echo intval($form->{$pre.'field_spacing'}); ?>px;">
                                    <?php foreach ($section_fields as $f) : 
                                        $val = $meta_map[$f->name] ?? '';
                                        if (empty($val)) continue; ?>
                                        
                                        <div class="pfb-info-item">
                                            <span class="pfb-label"><?php echo esc_html($f->label); ?>:</span>
                                            <div class="pfb-view-value">
                                                <?php 
                                                $decoded_val = json_decode($val, true);

                                                if (is_array($decoded_val)) {
                                                    // Render Gallery component with multiple images
                                                    echo '<div style="display:flex; gap:10px; flex-wrap:wrap; margin-top:10px; justify-content:inherit;">';
                                                    foreach ($decoded_val as $img_url) {
                                                        echo '<div class="pfb-view-image-div" style="display:inline-block;">';
                                                        echo '<img src="'.esc_url($img_url).'" class="pfb-view-image" style="width:120px; height:120px; object-fit:cover;">';
                                                        echo '<br><a href="'.esc_url($img_url).'" download class="pfb-btn-download-profile" style="padding:3px 8px; font-size:10px; margin-top:5px;">📥 Download</a>';
                                                        echo '</div>';
                                                    }
                                                    echo '</div>';
                                                } elseif (filter_var($val, FILTER_VALIDATE_URL) && preg_match('/\.(jpg|jpeg|png|gif|webp)$/i', $val)) {
                                                    // Render Single Image component
                                                    echo '<img src="'.esc_url($val).'" class="pfb-view-image">';
                                                    echo '<div class="pfb-download-wrapper"><a href="'.esc_url($val).'" download class="pfb-btn-download-profile">📥 Download Image</a></div>';
                                                } else {
                                                    // Fallback for standard text data
                                                    echo nl2br(esc_html($val));
                                                }
                                                ?>
                                            </div>
                                        </div>
                                    <?php endforeach; ?>
                                </div>
                            </div>
                    <?php endforeach; ?>
                </div>

                <div class="pfb-view-footer">
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
