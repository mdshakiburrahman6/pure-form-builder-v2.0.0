<?php
if (!defined('ABSPATH')) exit;

global $wpdb;

// 1. Form load logic
$form = $wpdb->get_row($wpdb->prepare("SELECT * FROM {$wpdb->prefix}pfb_forms WHERE id = %d", $id));
if (!$form) { echo '<p>Invalid form.</p>'; return; }

// 2. Access control (Legacy logic)
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

// 3. Meta and Errors (Legacy logic)
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

// 4. Fetch Fieldsets for V2 Grouping
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
        // 5. Section wise fields load
        $section_fields = $wpdb->get_results($wpdb->prepare(
            "SELECT * FROM {$wpdb->prefix}pfb_fields WHERE fieldset_id = %d AND is_fieldset = 0 ORDER BY sort_order ASC, id ASC",
            $section->id
        ));

        // 6. "Hide if Empty" Logic for V2
        if ($is_edit && $section->fieldset_display === 'hide_if_empty') {
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
                    // ==========================================
                    // APNAR ORIGINAL SWITCH CASE LOGIC (600+ line logic starts here)
                    // ==========================================
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
                            ?>
                            <input type="file" name="<?php echo esc_attr($f->name); ?>" <?php echo !empty($f->required) ? 'required' : ''; ?> class="<?php echo $has_error ? 'pfb-error-input' : ''; ?>">
                            <?php
                            break;

                        case 'image':
                            ?>
                            <div class="pfb-file-wrap">
                                <?php
                                $types = !empty($f->file_types) ? $f->file_types : '';
                                $max   = !empty($f->max_size) ? $f->max_size : 0;
                                $min   = !empty($f->min_size) ? $f->min_size : 0;
                                ?>
                                <input type="file" accept="image/*" name="<?php echo esc_attr($f->name); ?>" class="pfb-file-input <?php echo $has_error ? 'pfb-error-input' : ''; ?>" data-preview="pfb-preview-<?php echo esc_attr($f->name); ?>" data-types="<?php echo esc_attr($types); ?>" data-max="<?php echo esc_attr($max); ?>" data-min="<?php echo esc_attr($min); ?>">
                                <div class="pfb-preview" id="pfb-preview-<?php echo esc_attr($f->name); ?>">
                                    <?php if ($is_edit && !empty($value)): ?>
                                        <div class="pfb-image-preview existing"><img src="<?php echo esc_url($value); ?>" /></div>
                                    <?php endif; ?>
                                </div>
                            </div>
                            <?php if (!empty($f->file_types) || !empty($f->max_size)): ?>
                                <small class="pfb-file-hint" style="display:block; margin-top:6px; color:#666;">
                                    <?php if (!empty($f->file_types)) echo 'Allowed: ' . esc_html($f->file_types); ?>
                                    <?php if (!empty($f->max_size)) echo ( !empty($f->file_types) ? ' | ' : '' ) . 'Max: ' . esc_html($f->max_size) . 'MB'; ?>
                                </small>
                            <?php endif; ?>
                            <?php
                            break;
                    }
                    ?>
                </div>
            <?php endforeach; ?>
        </fieldset>
    <?php endforeach; ?>

    <button type="submit"><?php echo $is_edit ? 'Update Profile' : 'Submit'; ?></button>
</form>

<script>
// SUCCESS/ERROR ALERTS (SweetAlert)
<?php if (isset($_GET['pfb_success'])) : ?>
document.addEventListener('DOMContentLoaded', () => { Swal.fire({ icon: 'success', title: 'Form submitted successfully!' }); });
<?php endif; ?>

<?php if (!empty($pfb_errors)) : ?>
document.addEventListener('DOMContentLoaded', () => {
    const fields = <?php echo wp_json_encode(array_values($pfb_errors)); ?>;
    Swal.fire({ icon: 'error', title: fields.join(', ') });
});
<?php endif; ?>

// URL AND UI CLEANUP SCRIPTS (Apnar ager shob logic ekhane thakbe)
(function () {
    const url = new URL(window.location.href);
    ['pfb_success', 'pfb_errors'].forEach(p => { if(url.searchParams.has(p)) { url.searchParams.delete(p); window.history.replaceState({}, document.title, url.pathname); } });
})();

document.addEventListener('DOMContentLoaded', function () {
    const firstError = document.querySelector('.pfb-has-error');
    if (firstError) firstError.scrollIntoView({ behavior: 'smooth', block: 'center' });

    document.querySelectorAll('.pfb-error-input').forEach(input => {
        input.addEventListener('input', () => {
            input.classList.remove('pfb-error-input');
            const field = input.closest('.pfb-field');
            if (field) field.classList.remove('pfb-has-error');
        });
    });
});
</script>