<?php
/**
 * includes/submit-handler.php
 * Fixed: Single Entry Enforcement & Dynamic Redirect for Profile View
 */

if (!defined('ABSPATH')) exit;

add_action('admin_post_pfb_submit_form', 'pfb_handle_form_submit');
add_action('admin_post_nopriv_pfb_submit_form', 'pfb_handle_form_submit');

function pfb_handle_form_submit() {
    if (!isset($_POST['pfb_nonce']) || !wp_verify_nonce($_POST['pfb_nonce'], 'pfb_frontend_submit')) {
        wp_die('Security check failed');
    }

    global $wpdb;
    $form_id  = intval($_POST['pfb_form_id'] ?? 0);
    $entry_id = intval($_POST['entry_id'] ?? 0);
    $user_id  = get_current_user_id();

    if (!$form_id) wp_die('Invalid form');

    // 1. SINGLE ENTRY ENFORCEMENT logic
    if ($user_id && !$entry_id) {
        $existing_entry_id = $wpdb->get_var($wpdb->prepare(
            "SELECT id FROM {$wpdb->prefix}pfb_entries WHERE form_id = %d AND user_id = %d",
            $form_id, $user_id
        ));

        if ($existing_entry_id) {
            $entry_id = $existing_entry_id;
        }
    }

    // 2. FETCH ACTIVE INPUT FIELDS (Skip Fieldsets)
    $fields = $wpdb->get_results($wpdb->prepare(
        "SELECT * FROM {$wpdb->prefix}pfb_fields WHERE form_id = %d AND is_fieldset = 0",
        $form_id
    ));

    $errors = [];
    $data   = [];

    foreach ($fields as $f) {
        if (isset($f->is_fieldset) && (int)$f->is_fieldset === 1) {
            continue;
        }

        $name = $f->name;
        $value = '';

        if (in_array($f->type, ['file', 'image'])) {
            if (!empty($_FILES[$name]['name'])) {
                require_once ABSPATH . 'wp-admin/includes/file.php';
                require_once ABSPATH . 'wp-admin/includes/media.php';
                require_once ABSPATH . 'wp-admin/includes/image.php';

                $upload = media_handle_upload($name, 0);
                if (is_wp_error($upload)) {
                    if (!empty($f->required)) {
                        $errors[$name] = $f->label;
                    }
                } else {
                    $value = wp_get_attachment_url($upload);
                }
            } elseif ($entry_id) {
                $value = $wpdb->get_var($wpdb->prepare(
                    "SELECT field_value FROM {$wpdb->prefix}pfb_entry_meta WHERE entry_id=%d AND field_name=%s",
                    $entry_id, $name
                ));
            }
        } else {
            $value = isset($_POST[$name]) ? sanitize_text_field($_POST[$name]) : '';
            if (!empty($f->required) && trim($value) === '') {
                $errors[$name] = $f->label;
            }
        }
        $data[$name] = $value;
    }

    // 3. ERROR REDIRECT
    if (!empty($errors)) {
        $url = add_query_arg(['pfb_errors' => urlencode(wp_json_encode($errors))], wp_get_referer());
        wp_safe_redirect($url);
        exit;
    }

    // 4. SAVE (UPDATE OR INSERT)
    $is_update = false;
    if ($entry_id) {
        $is_update = true;
        foreach ($data as $field => $val) {
            $wpdb->replace("{$wpdb->prefix}pfb_entry_meta", [
                'entry_id'    => $entry_id,
                'field_name'  => $field,
                'field_value' => $val
            ], ['%d', '%s', '%s']);
        }
    } else {
        $wpdb->insert("{$wpdb->prefix}pfb_entries", [
            'form_id'    => $form_id,
            'user_id'    => $user_id ? $user_id : null,
            'created_at' => current_time('mysql')
        ]);
        $entry_id = $wpdb->insert_id;

        foreach ($data as $field => $val) {
            $wpdb->insert("{$wpdb->prefix}pfb_entry_meta", [
                'entry_id'    => $entry_id,
                'field_name'  => $field,
                'field_value' => $val
            ]);
        }
    }

    // 5. SUCCESS REDIRECT & CLEANUP
    // remove_query_arg use kore puraton parameter clear korun
    $redirect_url = remove_query_arg(['edit', 'entry_id', 'pfb_errors'], wp_get_referer());
    
    // Safe Redirection
    wp_safe_redirect(add_query_arg('pfb_success', 1, $redirect_url));
    exit;
}