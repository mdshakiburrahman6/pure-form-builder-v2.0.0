<?php
/**
 * includes/admin-actions.php
 * Final Version: Fixed Fatal Error + Entry Delete + Gallery/Image Update Logic
 */

if (!defined('ABSPATH')) exit;

// Register Actions
add_action('admin_post_pfb_save_form', 'pfb_handle_save_form');
add_action('admin_post_pfb_add_field', 'pfb_handle_add_field');
add_action('admin_post_pfb_delete_field', 'pfb_handle_delete_field');
add_action('admin_post_pfb_delete_form', 'pfb_handle_delete_form');
add_action('admin_post_pfb_export_entries', 'pfb_export_entries_csv');
add_action('admin_post_pfb_update_entry', 'pfb_handle_update_entry');
add_action('admin_post_pfb_save_form_settings', 'pfb_save_form_settings');
add_action('admin_post_pfb_delete_entry', 'pfb_handle_delete_entry'); // Delete entry action register

// AJAX Handler for Drag and Drop Sorting
add_action('wp_ajax_pfb_update_field_order', 'pfb_handle_field_sorting');

/* =========================
   1. SAVE / UPDATE FORM
========================= */
function pfb_handle_save_form() {
    if (!current_user_can('manage_options')) wp_die('Unauthorized');
    check_admin_referer('pfb_save_form_action', 'pfb_nonce');

    global $wpdb;
    $table = $wpdb->prefix . 'pfb_forms';
    $form_id = isset($_POST['form_id']) ? intval($_POST['form_id']) : 0;
    $name    = sanitize_text_field($_POST['form_name']);

    if ($form_id) {
        $wpdb->update($table, ['name' => $name], ['id' => $form_id]);
    } else {    
        $wpdb->insert($table, ['name' => $name]);
        $form_id = $wpdb->insert_id;
    }

    wp_redirect(admin_url('admin.php?page=pfb-builder&form_id=' . $form_id . '&saved=1'));
    exit;
}

/* =========================
   2. ADD / EDIT FIELD
========================= */
function pfb_handle_add_field() {
    if (!current_user_can('manage_options')) wp_die('Unauthorized');
    check_admin_referer('pfb_add_field_action', 'pfb_field_nonce');

    global $wpdb;
    $field_table = $wpdb->prefix . 'pfb_fields';
    $field_id    = isset($_POST['field_id']) ? intval($_POST['field_id']) : 0;
    $form_id     = intval($_POST['form_id']);
    $fieldset_id = isset($_POST['fieldset_id']) ? intval($_POST['fieldset_id']) : 0;
    $field_type  = sanitize_text_field($_POST['field_type']);
    // $required    = isset($_POST['field_required']) ? 1 : 0;
    $required = isset($_POST['field_required']) ? 1 : 0;

    $rules = null;
    if (!empty($_POST['rules']) && is_array($_POST['rules'])) {
        $clean_groups = [];
        foreach ($_POST['rules'] as $group) {
            if (empty($group['rules']) || !is_array($group['rules'])) continue;
            $clean_rules = [];
            foreach ($group['rules'] as $rule) {
                if (empty($rule['field']) || empty($rule['operator']) || $rule['value'] === '') continue;
                $clean_rules[] = [
                    'field'    => sanitize_key($rule['field']),
                    'operator' => sanitize_text_field($rule['operator']),
                    'value'    => sanitize_text_field($rule['value']),
                ];
            }
            if (!empty($clean_rules)) $clean_groups[] = ['rules' => $clean_rules];
        }
        if (!empty($clean_groups)) $rules = wp_json_encode($clean_groups);
    }

    $field_name_raw = $_POST['field_name'] ?? '';
    if (!preg_match('/^[a-zA-Z][a-zA-Z0-9_]*$/', $field_name_raw)) wp_die('Invalid field name.');
    $field_name = strtolower($field_name_raw);
    
    $options = null;
    if (in_array($field_type, ['select','radio'])) {
        $options = !empty($_POST['field_options']) ? wp_json_encode(array_map('trim', explode(',', $_POST['field_options']))) : null;
    }

    $data = [
        'form_id'          => $form_id,
        'fieldset_id'      => $fieldset_id,
        'type'             => $field_type,
        'label'            => sanitize_text_field($_POST['field_label']),
        'placeholder' => sanitize_text_field($_POST['field_placeholder']), 
        'description' => sanitize_textarea_field($_POST['field_description']), 
        'name'             => $field_name,
        'options'          => $options,
        'required'         => $required,
        'rules'            => $rules,
        'is_fieldset'      => ($field_type === 'fieldset') ? 1 : 0,
        'fieldset_display' => sanitize_text_field($_POST['fieldset_display'] ?? 'show_always'),
        'file_types'       => sanitize_text_field($_POST['file_types'] ?? ''),
        'max_size'         => floatval($_POST['max_size'] ?? 0),
        'section_bg_image'   => esc_url_raw($_POST['section_bg_image'] ?? ''),
        'section_bg_opacity' => floatval($_POST['section_bg_opacity'] ?? 1.0),
    ];

    if ($field_id) {
        $wpdb->update($field_table, $data, ['id' => $field_id]);
    } else {
        $wpdb->insert($field_table, $data);
    }

    wp_redirect(admin_url('admin.php?page=pfb-builder&form_id=' . $form_id . '&field_added=1'));
    exit;
}

/* =========================
   3. DELETE ENTRY Logic (Fixed Blank Page)
========================= */
function pfb_handle_delete_entry() {
    if (!current_user_can('manage_options')) wp_die('Unauthorized');
    
    $entry_id = intval($_GET['entry_id'] ?? 0);
    $form_id  = intval($_GET['form_id'] ?? 0);

    if (!$entry_id || !check_admin_referer('pfb_delete_entry_' . $entry_id)) {
        wp_die('Security check failed.');
    }

    global $wpdb;
    $wpdb->delete("{$wpdb->prefix}pfb_entry_meta", ['entry_id' => $entry_id]);
    $wpdb->delete("{$wpdb->prefix}pfb_entries", ['id' => $entry_id]);

    wp_redirect(admin_url('admin.php?page=pfb-entries&form_id=' . $form_id . '&deleted=1'));
    exit;
}

/* =========================
   4. UPDATE ENTRY (Admin Edit Fix)
========================= */
function pfb_handle_update_entry() {
    if (!current_user_can('manage_options')) wp_die('Unauthorized');
    check_admin_referer('pfb_update_entry', 'pfb_nonce');
    global $wpdb;

    $entry_id = intval($_POST['entry_id'] ?? 0);
    $form_id = $wpdb->get_var($wpdb->prepare("SELECT form_id FROM {$wpdb->prefix}pfb_entries WHERE id=%d", $entry_id));
    if (!$form_id) wp_die('Invalid form');

    $fields = $wpdb->get_results($wpdb->prepare("SELECT * FROM {$wpdb->prefix}pfb_fields WHERE form_id=%d AND is_fieldset=0", $form_id));
    $old_meta_rows = $wpdb->get_results($wpdb->prepare("SELECT * FROM {$wpdb->prefix}pfb_entry_meta WHERE entry_id=%d", $entry_id));
    $old_map = [];
    foreach ($old_meta_rows as $m) { $old_map[$m->field_name] = $m->field_value; }

    require_once ABSPATH . 'wp-admin/includes/file.php';
    require_once ABSPATH . 'wp-admin/includes/media.php';
    require_once ABSPATH . 'wp-admin/includes/image.php';

    foreach ($fields as $field) {
        $value = '';
        if ($field->type === 'image' || $field->type === 'gallery') {
            // Remove Logic (Advanced UI Style)
            if (!empty($_POST['delete_image']) && in_array($field->name, $_POST['delete_image'])) {
                $value = ''; 
            } 
            // Upload Logic
            elseif (!empty($_FILES[$field->name]['name']) && (is_array($_FILES[$field->name]['name']) ? !empty($_FILES[$field->name]['name'][0]) : !empty($_FILES[$field->name]['name']))) {
                if ($field->type === 'gallery') {
                    $urls = [];
                    foreach ($_FILES[$field->name]['name'] as $k => $v) {
                        if (!$v) continue;
                        $_FILES['pfb_tmp'] = [
                            'name' => $_FILES[$field->name]['name'][$k],
                            'type' => $_FILES[$field->name]['type'][$k],
                            'tmp_name' => $_FILES[$field->name]['tmp_name'][$k],
                            'error' => $_FILES[$field->name]['error'][$k],
                            'size' => $_FILES[$field->name]['size'][$k]
                        ];
                        $up = media_handle_upload('pfb_tmp', 0);
                        if (!is_wp_error($up)) $urls[] = wp_get_attachment_url($up);
                    }
                    $value = wp_json_encode($urls);
                } else {
                    $up = media_handle_upload($field->name, 0);
                    $value = (!is_wp_error($up)) ? wp_get_attachment_url($up) : ($old_map[$field->name] ?? '');
                }
            } else {
                $value = $old_map[$field->name] ?? '';
            }
        } else {
            $value = sanitize_text_field($_POST['fields'][$field->name] ?? '');
        }

        $wpdb->replace("{$wpdb->prefix}pfb_entry_meta", [
            'entry_id'    => $entry_id,
            'field_name'  => $field->name,
            'field_value' => $value
        ]);
    }
    wp_redirect(admin_url('admin.php?page=pfb-entry-edit&entry_id=' . $entry_id . '&updated=1'));
    exit;
}

/* =========================
   5. OTHER ACTIONS (Export, Settings, Field Sorting, etc.)
========================= */
function pfb_handle_delete_field() {
    if (!current_user_can('manage_options')) wp_die('Unauthorized');
    $field_id = intval($_GET['field_id']);
    $form_id  = intval($_GET['form_id']);
    check_admin_referer('pfb_delete_field_' . $field_id);
    global $wpdb;
    $wpdb->delete($wpdb->prefix . 'pfb_fields', ['id' => $field_id]);
    wp_redirect(admin_url('admin.php?page=pfb-builder&form_id=' . $form_id . '&field_deleted=1'));
    exit;
}

function pfb_handle_field_sorting() {
    if (!current_user_can('manage_options')) wp_send_json_error('Unauthorized');
    global $wpdb;
    $order = isset($_POST['order']) ? $_POST['order'] : []; 
    if (!empty($order) && is_array($order)) {
        foreach ($order as $index => $field_id) {
            $wpdb->update("{$wpdb->prefix}pfb_fields", ['sort_order' => $index], ['id' => intval($field_id)]);
        }
        wp_send_json_success('Saved!');
    } else { wp_send_json_error('No data'); }
}

function pfb_handle_delete_form() {
    if (!current_user_can('manage_options')) wp_die('Unauthorized');
    $form_id = isset($_GET['form_id']) ? intval($_GET['form_id']) : 0;
    check_admin_referer('pfb_delete_form_' . $form_id);
    global $wpdb;
    $entry_ids = $wpdb->get_col($wpdb->prepare("SELECT id FROM {$wpdb->prefix}pfb_entries WHERE form_id = %d", $form_id));
    if (!empty($entry_ids)) {
        $in = implode(',', array_map('intval', $entry_ids));
        $wpdb->query("DELETE FROM {$wpdb->prefix}pfb_entry_meta WHERE entry_id IN ($in)");
    }
    $wpdb->delete("{$wpdb->prefix}pfb_entries", ['form_id' => $form_id]);
    $wpdb->delete("{$wpdb->prefix}pfb_fields", ['form_id' => $form_id]);
    $wpdb->delete("{$wpdb->prefix}pfb_forms", ['id' => $form_id]);
    wp_redirect(admin_url('admin.php?page=pfb-forms&deleted=1'));
    exit;
}

function pfb_export_entries_csv() {
    if (!current_user_can('manage_options')) wp_die('Unauthorized');
    global $wpdb;
    $form_id = intval($_GET['form_id']);
    $entries = $wpdb->get_results($wpdb->prepare("SELECT e.id, e.created_at, u.user_login FROM {$wpdb->prefix}pfb_entries e LEFT JOIN {$wpdb->users} u ON e.user_id = u.ID WHERE e.form_id = %d ORDER BY e.id DESC", $form_id));
    $all_fields = $wpdb->get_col($wpdb->prepare("SELECT DISTINCT em.field_name FROM {$wpdb->prefix}pfb_entry_meta em INNER JOIN {$wpdb->prefix}pfb_entries e ON em.entry_id = e.id WHERE e.form_id = %d ORDER BY em.field_name ASC", $form_id));
    if (!$entries) wp_die('No entries found.');
    header('Content-Type: text/csv');
    header('Content-Disposition: attachment; filename="export.csv"');
    $output = fopen('php://output', 'w');
    fputcsv($output, array_merge(['ID', 'User', 'Date'], $all_fields));
    foreach ($entries as $entry) {
        $meta = $wpdb->get_results($wpdb->prepare("SELECT field_name, field_value FROM {$wpdb->prefix}pfb_entry_meta WHERE entry_id = %d", $entry->id));
        $values = array_fill_keys($all_fields, '');
        foreach ($meta as $m) $values[$m->field_name] = $m->field_value;
        fputcsv($output, array_merge([$entry->id, $entry->user_login ?: 'Guest', $entry->created_at], array_values($values)));
    }
    fclose($output);
    exit;
}

// Save Form Settings
function pfb_save_form_settings() {
    if (!current_user_can('manage_options')) wp_die('Permission denied');
    check_admin_referer('pfb_save_form_settings', 'pfb_settings_nonce');

    global $wpdb;
    $form_id = intval($_POST['form_id']);
    
    $data = [
        'access_type'     => sanitize_text_field($_POST['access_type'] ?? 'all'),
        'allow_user_edit' => isset($_POST['allow_user_edit']) ? 1 : 0,
        'form_bg_image'   => esc_url_raw($_POST['form_bg_image'] ?? ''),
    ];

    $tabs = ['view_', 'edit_', 'submit_']; /* */

    foreach ($tabs as $pre) {
        $data[$pre . 'column_layout']        = sanitize_text_field($_POST[$pre . 'column_layout'] ?? '1-col');
        $data[$pre . 'form_padding']         = intval($_POST[$pre . 'form_padding'] ?? 25);
        $data[$pre . 'header_gap']           = intval($_POST[$pre . 'header_gap'] ?? 15);
        $data[$pre . 'field_spacing']        = intval($_POST[$pre . 'field_spacing'] ?? 20);
        $data[$pre . 'input_bg_color']       = sanitize_hex_color($_POST[$pre . 'input_bg_color'] ?? '#ffffff');
        $data[$pre . 'image_preview_width'] = intval($_POST[$pre . 'image_preview_width'] ?? 100);
        
        foreach (['heading', 'label', 'text'] as $type) {
            $data[$pre . $type . '_font_size']   = intval($_POST[$pre . $type . '_font_size'] ?? 16);
            $data[$pre . $type . '_font_weight'] = intval($_POST[$pre . $type . '_font_weight'] ?? 400);
            $data[$pre . $type . '_color']       = sanitize_hex_color($_POST[$pre . $type . '_color'] ?? '#333333');
            $data[$pre . $type . '_align']       = sanitize_text_field($_POST[$pre . $type . '_align'] ?? 'left');
        }

        foreach (['submit', 'cancel'] as $btn) {
            $data[$pre . $btn . '_btn_text']   = sanitize_text_field($_POST[$pre . $btn . '_btn_text'] ?? '');
            $data[$pre . $btn . '_btn_bg']     = sanitize_hex_color($_POST[$pre . $btn . '_btn_bg'] ?? '#2271b1');
            $data[$pre . $btn . '_btn_clr']    = sanitize_hex_color($_POST[$pre . $btn . '_btn_clr'] ?? '#ffffff');
            $data[$pre . $btn . '_btn_radius'] = intval($_POST[$pre . $btn . '_btn_radius'] ?? 6);
            $data[$pre . $btn . '_btn_align']  = sanitize_text_field($_POST[$pre . $btn . '_btn_align'] ?? 'flex-start');
            $data[$pre . $btn . '_btn_size']   = intval($_POST[$pre . $btn . '_btn_size'] ?? 16);
            $data[$pre . $btn . '_btn_weight'] = intval($_POST[$pre . $btn . '_btn_weight'] ?? 600);
        }
    }

    $wpdb->update("{$wpdb->prefix}pfb_forms", $data, ['id' => $form_id]);
    $last_tab = isset($_POST['last_tab']) ? sanitize_text_field($_POST['last_tab']) : '#pfb-access-tab';
    wp_redirect(admin_url("admin.php?page=pfb-form-settings&form_id={$form_id}&updated=1&tab=" . urlencode($last_tab)));
    exit;
}

// AJAX Handler for Frontend Image Removal
add_action('wp_ajax_pfb_remove_frontend_image', 'pfb_handle_frontend_image_removal');

function pfb_handle_frontend_image_removal() {
    global $wpdb;
    $entry_id = intval($_POST['entry_id']);
    $field_name = sanitize_text_field($_POST['field_name']);

    $wpdb->update(
        "{$wpdb->prefix}pfb_entry_meta",
        ['field_value' => ''],
        ['entry_id' => $entry_id, 'field_name' => $field_name]
    );
    wp_send_json_success();
}





add_action('wp_footer', function () {
?>
<script>
document.addEventListener("DOMContentLoaded", function () {

    if (typeof Swal === 'undefined') return;

    const params = new URLSearchParams(window.location.search);

    // SUCCESS
    if (params.has('pfb_success')) {
        const mode = params.get('pfb_mode');

        Swal.fire({
            toast: true,
            position: 'top-end',
            icon: 'success',
            title: mode === 'edit'
                ? 'Profile updated successfully'
                : 'Profile created successfully',
            showConfirmButton: false,
            timer: 4000,
            timerProgressBar: true
        });

        const url = new URL(window.location);
        url.searchParams.delete('pfb_success');
        url.searchParams.delete('pfb_mode');
        window.history.replaceState({}, document.title, url.pathname);
    }

    // ERRORS
    if (params.has('pfb_errors')) {
        try {
            const errors = JSON.parse(decodeURIComponent(params.get('pfb_errors')));
            let html = '<ul>';
            Object.values(errors).forEach(e => html += `<li>${e} is required</li>`);
            html += '</ul>';

            Swal.fire({
                toast: true,
                position: 'top-end',
                icon: 'error',
                title: 'Please fix the following fields',
                html,
                showConfirmButton: false,
                timer: 6000
            });
        } catch(e){}
    }

});
</script>
<?php
});
