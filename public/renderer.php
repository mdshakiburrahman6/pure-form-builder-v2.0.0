<?php
/**
 * public/renderer.php
 * Final Version: Fixed Section logic for Form Submission.
 * Includes: Tel, URL, File, Gallery Types, and Image Previews.
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

// 3. Fetch ALL fields for this form (both Sections and Inputs)
$all_fields = $wpdb->get_results($wpdb->prepare(
    "SELECT * FROM {$wpdb->prefix}pfb_fields WHERE form_id = %d ORDER BY sort_order ASC, id ASC",
    $id
));

if (!$all_fields) {
    echo '<p>No fields added to this form yet.</p>';
    return;
}
?>

<form class="pfb-form" method="post" action="<?php echo esc_url(admin_url('admin-post.php')); ?>" enctype="multipart/form-data" novalidate>
    <?php if ($is_edit): ?><input type="hidden" name="entry_id" value="<?php echo esc_attr($entry_id); ?>"><?php endif; ?>
    <input type="hidden" name="action" value="pfb_submit_form">
    <input type="hidden" name="pfb_form_id" value="<?php echo esc_attr($id); ?>">
    <?php wp_nonce_field('pfb_frontend_submit', 'pfb_nonce'); ?>

    <?php 
    $opened_fieldset = false;

    foreach ($all_fields as $f) : 
        // Logic: Section Header (Fieldset) Detect
        if ($f->is_fieldset) {
            if ($opened_fieldset) { echo '</fieldset>'; }
            
            // NOTE: 'Hide if Empty' logic should NOT apply here in the form renderer.
            // It only applies to the Profile View.
            echo '<fieldset class="pfb-section-wrapper" style="margin-bottom:30px; border:1px solid #ddd; padding:20px; border-radius:8px;">';
            echo '<legend style="padding:0 10px; font-weight:bold; font-size:1.2em;">' . esc_html($f->label) . '</legend>';
            $opened_fieldset = true;
            continue;
        }

        // Standard Fields Rendering
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
                    foreach ($options as $opt) { 
                        echo '<option value="'.esc_attr($opt).'" '.selected($value === $opt, true, false).'>'.esc_html($opt).'</option>'; 
                    }
                    echo '</select>';
                    break;

                case 'radio':
                    $options = json_decode($f->options, true) ?: [];
                    foreach ($options as $opt) {
                        echo '<label style="display:block;"><input type="radio" name="'.esc_attr($f->name).'" value="'.esc_attr($opt).'" '.checked($value === $opt, true, false).'> '.esc_html($opt).'</label>';
                    }
                    break;

                case 'image':
                    ?>
                    <div class="pfb-file-wrap">
                        <input type="file" accept="image/*" name="<?php echo esc_attr($f->name); ?>" class="pfb-file-input" onchange="pfb_preview_image(this)">
                        <div class="pfb-preview-container">
                            <?php if ($is_edit && !empty($value)): ?>
                                <div class="pfb-image-preview existing" style="margin-top:10px;">
                                    <img src="<?php echo esc_url($value); ?>" style="max-width:150px; border-radius:5px; border:1px solid #ddd;" />
                                    <div style="margin-top:5px;">
                                        <label style="color:red; font-size:12px; cursor:pointer;">
                                            <input type="checkbox" name="pfb_remove_file[]" value="<?php echo esc_attr($f->name); ?>"> Remove Current Image
                                        </label>
                                    </div>
                                </div>
                            <?php endif; ?>
                        </div>
                    </div>
                    <?php 
                    break;

                case 'file':
                    ?>
                    <div class="pfb-file-wrap">
                        <input type="file" name="<?php echo esc_attr($f->name); ?>" class="pfb-file-input">
                        <div class="pfb-file-status">
                            <?php if ($is_edit && !empty($value)): ?>
                                <div class="existing" style="margin-top:10px; padding:10px; background:#f9f9f9; border:1px solid #eee; border-radius:5px;">
                                    <a href="<?php echo esc_url($value); ?>" target="_blank" style="font-size:13px; text-decoration:none;">📄 View Current File</a>
                                    <div style="margin-top:5px;">
                                        <label style="color:red; font-size:12px; cursor:pointer;">
                                            <input type="checkbox" name="pfb_remove_file[]" value="<?php echo esc_attr($f->name); ?>"> Remove Current File
                                        </label>
                                    </div>
                                </div>
                            <?php endif; ?>
                        </div>
                    </div>
                    <?php 
                    break;

                case 'gallery':
                    $gallery_images = !empty($value) ? json_decode($value, true) : [];
                    ?>
                    <div class="pfb-gallery-wrap">
                        <input type="file" accept="image/*" name="<?php echo esc_attr($f->name); ?>[]" multiple class="pfb-file-input" onchange="pfb_preview_gallery(this)">
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
    if ($opened_fieldset) { echo '</fieldset>'; }
    ?>

    <div class="pfb-form-footer" style="display:flex; gap:10px; margin-top:20px;">
        <button type="submit" class="pfb-submit-btn"><?php echo $is_edit ? 'Update Profile' : 'Submit'; ?></button>
        
        <?php if ($is_edit) : ?>
            <a href="<?php echo esc_url(remove_query_arg('edit')); ?>" class="pfb-btn-cancel" style="padding:10px 20px; background:#eee; color:#333; text-decoration:none; border-radius:5px;">
                Cancel & Back
            </a>
        <?php endif; ?>
    </div>
</form>

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