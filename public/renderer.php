<?php
/**
 * public/renderer.php
 * Absolute Final Version: Edit Mode Visibility, URL Cleanup & Perfect Success Toast
 */

if (!defined('ABSPATH')) exit;

global $wpdb;

// 1. Form load logic
$form = $wpdb->get_row($wpdb->prepare("SELECT * FROM {$wpdb->prefix}pfb_forms WHERE id = %d", $id));
if (!$form) { echo '<p>Invalid form.</p>'; return; }

// 2. Access control check
$access_type = $form->access_type ?? 'all';
$allowed_roles = !empty($form->allowed_roles) ? array_map('trim', explode(',', $form->allowed_roles)) : [];
$redirect_type = $form->redirect_type ?? 'message';
$redirect_page = intval($form->redirect_page ?? 0);
$current_user = wp_get_current_user();
$access_error = null;

if ($access_type === 'logged_in' && !is_user_logged_in()) {
    $access_error = pfb_handle_access_denied($redirect_type, $redirect_page);
}
if ($access_type === 'guest' && is_user_logged_in()) {
    $access_error = pfb_handle_access_denied($redirect_type, $redirect_page);
}
if (!$access_error && !empty($allowed_roles)) {
    if (!is_user_logged_in() || empty(array_intersect($allowed_roles, (array) $current_user->roles))) {
        $access_error = pfb_handle_access_denied($redirect_type, $redirect_page);
    }
}
if ($access_error === 'message') {
    echo '<div class="pfb-access-denied"><strong>Access Denied</strong><br>You do not have permission to view this form.</div>';
    return;
}

// 3. Meta and Errors setup
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

// 4. Fetch Fieldsets (Sections)
$fieldsets = $wpdb->get_results($wpdb->prepare(
    "SELECT * FROM {$wpdb->prefix}pfb_fields WHERE form_id = %d AND is_fieldset = 1 ORDER BY sort_order ASC, id ASC",
    $id
));
?>

<form class="pfb-form" method="post" action="<?php echo esc_url(admin_url('admin-post.php')); ?>" enctype="multipart/form-data" novalidate>
    <?php if ($is_edit): ?><input type="hidden" name="entry_id" value="<?php echo esc_attr($entry_id); ?>"><?php endif; ?>
    <input type="hidden" name="action" value="pfb_submit_form">
    <input type="hidden" name="pfb_form_id" value="<?php echo esc_attr($id); ?>">
    <?php wp_nonce_field('pfb_frontend_submit', 'pfb_nonce'); ?>

    <?php 
    foreach ($fieldsets as $section) : 
        $section_fields = $wpdb->get_results($wpdb->prepare(
            "SELECT * FROM {$wpdb->prefix}pfb_fields WHERE fieldset_id = %d AND is_fieldset = 0 ORDER BY sort_order ASC, id ASC",
            $section->id
        ));

        // Logic: Edit mode-e shob dekhabe, View mode-e faka thakle hide hobe
        if ($is_edit === false && $section->fieldset_display === 'hide_if_empty') {
            $has_data = false;
            foreach ($section_fields as $sf) {
                if (!empty($existing_meta[$sf->name])) { $has_data = true; break; }
            }
            if (!$has_data) continue; 
        }
    ?>
        <fieldset class="pfb-section-wrapper" style="margin-bottom:30px; border:1px solid #ddd; padding:20px; border-radius:8px;">
            <legend style="padding:0 10px; font-weight:bold; font-size:1.2em;"><?php echo esc_html($section->label); ?></legend>

            <?php foreach ($section_fields as $f) : 
                $has_error = isset($pfb_errors[$f->name]);
                $value = ($is_edit && isset($existing_meta[$f->name])) ? $existing_meta[$f->name] : '';
            ?>
                <div class="pfb-field <?php echo $has_error ? 'pfb-has-error' : ''; ?>" <?php if (!empty($f->rules)) echo 'data-rules="' . esc_attr($f->rules) . '"'; ?>>
                    <label><?php echo esc_html($f->label); ?></label>

                    <?php
                    switch ($f->type) {
                        case 'text':
                        case 'email':
                        case 'number':
                        case 'url':
                            ?>
                            <input type="<?php echo esc_attr($f->type); ?>" name="<?php echo esc_attr($f->name); ?>" value="<?php echo esc_attr($value); ?>" <?php echo !empty($f->required) ? 'required' : ''; ?> class="<?php echo $has_error ? 'pfb-error-input' : ''; ?>">
                            <?php
                            break;

                        case 'textarea':
                            ?>
                            <textarea name="<?php echo esc_attr($f->name); ?>" <?php echo !empty($f->required) ? 'required' : ''; ?> class="<?php echo $has_error ? 'pfb-error-input' : ''; ?>"><?php echo esc_textarea($value); ?></textarea>
                            <?php
                            break;

                        case 'select':
                            $options = json_decode($f->options, true) ?: [];
                            ?>
                            <select name="<?php echo esc_attr($f->name); ?>" <?php echo !empty($f->required) ? 'required' : ''; ?> class="<?php echo $has_error ? 'pfb-error-input' : ''; ?>">
                                <option value="" <?php selected(empty($value)); ?>>Select</option>
                                <?php foreach ($options as $opt): ?>
                                    <option value="<?php echo esc_attr($opt); ?>" <?php selected($value === $opt); ?>><?php echo esc_html($opt); ?></option>
                                <?php endforeach; ?>
                            </select>
                            <?php
                            break;

                        case 'radio':
                            $options = json_decode($f->options, true) ?: [];
                            foreach ($options as $opt):
                            ?>
                                <label style="display:block;">
                                    <input type="radio" name="<?php echo esc_attr($f->name); ?>" value="<?php echo esc_attr($opt); ?>" <?php checked($value === $opt); ?>>
                                    <?php echo esc_html($opt); ?>
                                </label>
                            <?php
                            endforeach;
                            break;

                        case 'file':
                        case 'image':
                            ?>
                            <div class="pfb-file-wrap">
                                <input type="file" name="<?php echo esc_attr($f->name); ?>" class="pfb-file-input <?php echo $has_error ? 'pfb-error-input' : ''; ?>" onchange="pfb_preview_image(this)">
                                <div class="pfb-preview-container">
                                    <?php if ($is_edit && !empty($value)): ?>
                                        <div class="pfb-image-preview existing"><img src="<?php echo esc_url($value); ?>" style="max-width:150px; margin-top:10px; border-radius:5px; border:1px solid #ddd;" /></div>
                                    <?php endif; ?>
                                </div>
                            </div>
                            <?php break;
                    }
                    ?>
                </div>
            <?php endforeach; ?>
        </fieldset>
    <?php endforeach; ?>

    <button type="submit"><?php echo $is_edit ? 'Update Profile' : 'Submit'; ?></button>
</form>

<script>
    document.addEventListener('DOMContentLoaded', function () {
        const urlParams = new URLSearchParams(window.location.search);
        
        // 1. Success Alert Logic
        if (urlParams.has('pfb_success')) {
            // Form-er bhetore entry_id achhe kina check kore message thik kora
            const isEditMode = document.querySelector('input[name="entry_id"]') !== null;
            
            Swal.fire({
                toast: true,
                position: 'top-end',
                icon: 'success',
                title: isEditMode ? 'Profile updated successfully!' : 'Form submitted successfully!',
                showConfirmButton: false,
                timer: 3000
            });

            // Fix: Alert fire hobar por ektu deri kore URL cleanup kora
            setTimeout(() => {
                const url = new URL(window.location.href);
                url.searchParams.delete('pfb_success');
                url.searchParams.delete('pfb_errors');
                window.history.replaceState({}, document.title, url.pathname + url.search);
            }, 1000); // 1 second deri korle safe
        }

        // 2. Error Alert Logic
        <?php if (!empty($pfb_errors)) : ?>
            const uniqueErrors = [...new Set(<?php echo wp_json_encode(array_values($pfb_errors)); ?>)];
            Swal.fire({
                toast: true,
                position: 'top-end',
                icon: 'error',
                title: uniqueErrors.join(', ') + ' are required',
                showConfirmButton: false,
                timer: 4000
            });
        <?php endif; ?>
    });

    // Image Preview Function (No change needed here)
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
</script>