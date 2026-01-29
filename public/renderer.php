<?php
/**
 * public/renderer.php
 * Final Version: Supporting 3-Tab Design Prefix (submit_ / edit_)
 * Includes: Tel, URL, File, Gallery Types, Image Previews, Header Gap, and Font Weights.
 */

if (!defined('ABSPATH')) exit;

global $wpdb;

// 1. Form load logic
$form = $wpdb->get_row($wpdb->prepare("SELECT * FROM {$wpdb->prefix}pfb_forms WHERE id = %d", $id));
if (!$form) { echo '<p>Invalid form.</p>'; return; }

// 2. Meta and Errors setup
$is_edit = !empty($entry_id);
$existing_meta = [];
if ($is_edit) {
    $rows = $wpdb->get_results($wpdb->prepare("SELECT field_name, field_value FROM {$wpdb->prefix}pfb_entry_meta WHERE entry_id = %d", $entry_id));
    foreach ($rows as $row) { $existing_meta[$row->field_name] = $row->field_value; }
}

$pfb_errors = [];
if (!empty($_GET['pfb_errors'])) {
    $pfb_errors = json_decode(stripslashes(urldecode($_GET['pfb_errors'])), true);
}

// 3. Setup Context Prefix (Submit uses 'submit_', Edit uses 'edit_')
$pre = $is_edit ? 'edit_' : 'submit_';

// 4. Dynamic Layout Calculations
$cols = ($form->{$pre.'column_layout'} === '3-col') ? 3 : (($form->{$pre.'column_layout'} === '2-col') ? 2 : 1);
$img_width = !empty($form->image_preview_width) ? $form->image_preview_width . '%' : '100%';
$img_align = !empty($form->image_align) ? $form->image_align : 'center';

// 5. Advanced Dynamic Styling
echo "<style>
    .pfb-form-{$id} {
        padding: " . intval($form->{$pre.'form_padding'}) . "px;
        background-color: " . esc_attr($form->{$pre.'input_bg_color'}) . ";
        border-radius: " . intval($form->border_radius) . "px;
        background-image: url('" . esc_url($form->form_bg_image) . "');
        background-size: cover;
        text-align: " . esc_attr($form->text_align) . ";
    }

    /* Column Grid System with Fields Gap */
    .pfb-form-{$id} .pfb-section-wrapper {
        display: grid;
        grid-template-columns: repeat({$cols}, 1fr);
        gap: " . intval($form->{$pre.'field_spacing'}) . "px;
        margin-bottom: 30px;
        border: none !important;
        padding: 20px;
        border-radius: 8px;
    }

    /* Header Gap & Heading Typography */
    .pfb-form-{$id} legend {
        grid-column: 1 / -1;
        padding: 0px;
        margin-bottom: " . intval($form->{$pre.'header_gap'}) . "px;
        color: " . esc_attr($form->{$pre.'heading_color'}) . ";
        font-size: " . intval($form->{$pre.'heading_font_size'}) . "px;
        font-weight: " . intval($form->{$pre.'heading_font_weight'}) . ";
    }

    /* Label Typography */
    .pfb-form-{$id} .pfb-field label {
        color: " . esc_attr($form->{$pre.'label_color'}) . ";
        font-size: " . intval($form->{$pre.'label_font_size'}) . "px;
        font-weight: " . intval($form->{$pre.'label_font_weight'}) . ";
        display: block;
        margin-bottom: 8px;
    }

    /* Input Text Typography */
    .pfb-form-{$id} input, .pfb-form-{$id} select, .pfb-form-{$id} textarea {
        color: " . esc_attr($form->{$pre.'text_color'}) . " !important;
        font-size: " . intval($form->{$pre.'text_font_size'}) . "px !important;
        font-weight: " . intval($form->{$pre.'text_font_weight'}) . " !important;
    }

    /* Submit/Update Button Designer */
    .pfb-form-{$id} .pfb-submit-btn {
        background-color: " . esc_attr($form->{$pre.'submit_btn_bg'}) . " !important;
        color: " . esc_attr($form->{$pre.'submit_btn_clr'}) . " !important;
        font-size: " . intval($form->{$pre.'submit_btn_size'}) . "px !important;
        font-weight: " . intval($form->{$pre.'submit_btn_weight'}) . " !important;
        border-radius: " . intval($form->{$pre.'submit_btn_radius'}) . "px !important;
        padding: 12px 28px;
        border: none;
        cursor: pointer;
    }

    /* Cancel/Back Button Style */
    .pfb-form-{$id} .pfb-btn-cancel {
        background-color: " . esc_attr($form->{$pre.'cancel_btn_bg'}) . " !important;
        color: " . esc_attr($form->{$pre.'cancel_btn_clr'}) . " !important;
        border-radius: " . intval($form->{$pre.'cancel_btn_radius'}) . "px !important;
        text-decoration: none;
        padding: 11px 25px;
        display: inline-block;
    }

    /* Media Preview Control */
    .pfb-preview-container img, .pfb-gallery-preview-container img { 
        width: {$img_width}; 
        display: block; 
        margin: " . ($img_align === 'center' ? '0 auto' : ($img_align === 'right' ? '0 0 0 auto' : '0 auto 0 0')) . "; 
    }
</style>";

// 6. Fetch ALL fields
$all_fields = $wpdb->get_results($wpdb->prepare("SELECT * FROM {$wpdb->prefix}pfb_fields WHERE form_id = %d ORDER BY sort_order ASC, id ASC", $id));
?>

<div class="pfb-form pfb-form-<?php echo $id; ?>">
    <form method="post" action="<?php echo esc_url(admin_url('admin-post.php')); ?>" enctype="multipart/form-data" novalidate>
        <?php if ($is_edit): ?><input type="hidden" name="entry_id" value="<?php echo esc_attr($entry_id); ?>"><?php endif; ?>
        <input type="hidden" name="action" value="pfb_submit_form">
        <input type="hidden" name="pfb_form_id" value="<?php echo esc_attr($id); ?>">
        <?php wp_nonce_field('pfb_frontend_submit', 'pfb_nonce'); ?>

        <?php 
        $opened_fieldset = false;
        foreach ($all_fields as $f) : 
            /* - Update the Fieldset Loop section */

            if ($f->is_fieldset) {
                if ($opened_fieldset) echo '</fieldset>';
                
                $bg_style = "";
                
                /** * Logic: Disable background image for Submit and Edit modes.
                 * Background is only rendered if it's NOT an edit session and NOT a standard form submission view.
                 * For this specific requirement, we set $bg_style to empty to ensure clean Submit/Edit pages.
                 */
                
                // Check if the current context is strictly for the 'View Profile' (handled by shortcode.php)
                // In renderer.php (used for Submit/Edit), we keep the background empty.
                $bg_style = ""; 

                echo '<fieldset class="pfb-section-wrapper" style="' . $bg_style . '">';
                echo '<legend>' . esc_html($f->label) . '</legend>';
                $opened_fieldset = true;
                continue;
            }

            $has_error = isset($pfb_errors[$f->name]);
            $value = ($is_edit && isset($existing_meta[$f->name])) ? $existing_meta[$f->name] : '';
            ?>
            <div class="pfb-field <?php echo $has_error ? 'pfb-has-error' : ''; ?>" <?php if (!empty($f->rules)) echo 'data-rules="' . esc_attr($f->rules) . '"'; ?>>
                <label><?php echo esc_html($f->label); ?></label>

                <?php
                switch ($f->type) {
                    case 'text': case 'email': case 'number': case 'url': case 'tel':
                        echo '<input type="'.esc_attr($f->type).'" name="'.esc_attr($f->name).'" value="'.esc_attr($value).'" '.(!empty($f->required) ? 'required' : '').' class="'.($has_error ? 'pfb-error-input' : '').'">';
                        break;

                    case 'textarea':
                        echo '<textarea name="'.esc_attr($f->name).'" '.(!empty($f->required) ? 'required' : '').'>'.esc_textarea($value).'</textarea>';
                        break;

                    case 'select':
                        $options = json_decode($f->options, true) ?: [];
                        echo '<select name="'.esc_attr($f->name).'" '.(!empty($f->required) ? 'required' : '').'>';
                        echo '<option value="">Select</option>';
                        foreach ($options as $opt) { echo '<option value="'.esc_attr($opt).'" '.selected($value === $opt, true, false).'>'.esc_html($opt).'</option>'; }
                        echo '</select>';
                        break;

                    case 'radio':
                        $options = json_decode($f->options, true) ?: [];
                        foreach ($options as $opt) {
                            // Wrapped in a styled label for better UI
                            echo '<label class="pfb-radio-label">';
                            echo '<input type="radio" name="'.esc_attr($f->name).'" value="'.esc_attr($opt).'" '.checked($value === $opt, true, false).'>';
                            echo '<span>' . esc_html($opt) . '</span>';
                            echo '</label>';
                        }
                        break;

                    case 'image': case 'file':
                        ?>
                        <div class="pfb-file-wrap">
                            <input type="file" <?php echo ($f->type === 'image') ? 'accept="image/*"' : ''; ?> name="<?php echo esc_attr($f->name); ?>" onchange="<?php echo ($f->type === 'image') ? 'pfb_preview_image(this)' : ''; ?>">
                            <div class="pfb-preview-container">
                                <?php if ($is_edit && !empty($value)): ?>
                                    <div class="existing" style="margin-top:10px;">
                                        <?php if($f->type === 'image'): ?>
                                            <img src="<?php echo esc_url($value); ?>" style="max-width:150px; border-radius:5px; border:1px solid #ddd;" />
                                        <?php else: ?>
                                            <a href="<?php echo esc_url($value); ?>" target="_blank">📄 View Current File</a>
                                        <?php endif; ?>
                                        <label style="color:red; font-size:12px; display:block; margin-top:5px;">
                                            <input type="checkbox" name="pfb_remove_file[]" value="<?php echo esc_attr($f->name); ?>"> Remove Current
                                        </label>
                                    </div>
                                <?php endif; ?>
                            </div>
                        </div>
                        <?php break;

                    case 'gallery':
                        $gallery_images = !empty($value) ? json_decode($value, true) : [];
                        ?>
                        <div class="pfb-gallery-wrap">
                            <input type="file" accept="image/*" name="<?php echo esc_attr($f->name); ?>[]" multiple onchange="pfb_preview_gallery(this)">
                            <div class="pfb-gallery-preview-container" style="display:flex; gap:10px; flex-wrap:wrap; margin-top:10px;">
                                <?php if ($is_edit && !empty($gallery_images)): 
                                    foreach($gallery_images as $img_url): ?>
                                        <img src="<?php echo esc_url($img_url); ?>" style="width:80px; height:80px; object-fit:cover; border-radius:5px; border:1px solid #ddd;" />
                                    <?php endforeach; 
                                endif; ?>
                            </div>
                        </div>
                        <?php break;
                }
                ?>
            </div>
        <?php endforeach; 
        if ($opened_fieldset) echo '</fieldset>';
        ?>

        <div class="pfb-form-footer" style="display:flex; gap:15px; margin-top:25px; align-items:center;">
            <button type="submit" class="pfb-submit-btn">
                <?php echo esc_html($form->{$pre.'submit_btn_text'}); ?>
            </button>
            
            <?php if ($is_edit) : ?>
                <a href="<?php echo esc_url(remove_query_arg('edit')); ?>" class="pfb-btn-cancel">
                    <?php echo esc_html($form->{$pre.'cancel_btn_text'} ?? 'Cancel'); ?>
                </a>
            <?php endif; ?>
        </div>
    </form>
</div>

<script>
    function pfb_preview_image(input) {
        const container = input.closest('.pfb-file-wrap').querySelector('.pfb-preview-container');
        container.innerHTML = '';
        if (input.files && input.files[0]) {
            const reader = new FileReader();
            reader.onload = e => {
                const img = document.createElement('img');
                img.src = e.target.result;
                img.style.maxWidth = '150px'; img.style.marginTop = '10px'; img.style.borderRadius = '5px';
                container.appendChild(img);
            }
            reader.readAsDataURL(input.files[0]);
        }
    }

    function pfb_preview_gallery(input) {
        const container = input.closest('.pfb-gallery-wrap').querySelector('.pfb-gallery-preview-container');
        container.innerHTML = '';
        if (input.files) {
            Array.from(input.files).forEach(file => {
                const reader = new FileReader();
                reader.onload = e => {
                    const img = document.createElement('img');
                    img.src = e.target.result;
                    img.style.width = '80px'; img.style.height = '80px'; img.style.objectFit = 'cover';
                    img.style.borderRadius = '5px'; img.style.border = '1px solid #ddd';
                    container.appendChild(img);
                }
                reader.readAsDataURL(file);
            });
        }
    }
</script>