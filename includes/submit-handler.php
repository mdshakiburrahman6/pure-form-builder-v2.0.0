<?php
/**
 * includes/submit-handler.php
 * Final Version: V2 Nested Support + Multi-File Gallery + Tel/URL Sanitization
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

    // 1. SINGLE ENTRY ENFORCEMENT
    if ($user_id && !$entry_id) {
        $existing_entry_id = $wpdb->get_var($wpdb->prepare(
            "SELECT id FROM {$wpdb->prefix}pfb_entries WHERE form_id = %d AND user_id = %d",
            $form_id, $user_id
        ));
        if ($existing_entry_id) $entry_id = $existing_entry_id;
    }

    // 2. FETCH ACTIVE INPUT FIELDS
    $fields = $wpdb->get_results($wpdb->prepare(
        "SELECT * FROM {$wpdb->prefix}pfb_fields WHERE form_id = %d AND is_fieldset = 0",
        $form_id
    ));

    $errors = [];
    $data   = [];

    foreach ($fields as $f) {
        $name = $f->name;
        $value = '';

        // --- TYPE: GALLERY (Multiple Image Upload) ---
        if ($f->type === 'gallery') {
            if (!empty($_FILES[$name]['name'][0])) {
                require_once ABSPATH . 'wp-admin/includes/file.php';
                require_once ABSPATH . 'wp-admin/includes/media.php';
                require_once ABSPATH . 'wp-admin/includes/image.php';

                $gallery_urls = [];
                $files = $_FILES[$name];
                
                // WP does not support multi-upload natively in one go, so we loop
                foreach ($files['name'] as $key => $val) {
                    if ($files['name'][$key]) {
                        $file = [
                            'name'     => $files['name'][$key],
                            'type'     => $files['type'][$key],
                            'tmp_name' => $files['tmp_name'][$key],
                            'error'    => $files['error'][$key],
                            'size'     => $files['size'][$key]
                        ];
                        
                        // Fake a single file for media_handle_upload
                        $_FILES['pfb_temp_file'] = $file;
                        $upload = media_handle_upload('pfb_temp_file', 0);
                        
                        if (!is_wp_error($upload)) {
                            $gallery_urls[] = wp_get_attachment_url($upload);
                        }
                    }
                }
                $value = !empty($gallery_urls) ? wp_json_encode($gallery_urls) : '';
            } elseif ($entry_id) {
                // Keep existing gallery if no new files uploaded
                $value = $wpdb->get_var($wpdb->prepare("SELECT field_value FROM {$wpdb->prefix}pfb_entry_meta WHERE entry_id=%d AND field_name=%s", $entry_id, $name));
            }
        } 
        
        // --- TYPE: FILE / IMAGE (Single Upload) ---
        elseif (in_array($f->type, ['file', 'image'])) {
            if (!empty($_FILES[$name]['name'])) {
                require_once ABSPATH . 'wp-admin/includes/file.php';
                require_once ABSPATH . 'wp-admin/includes/media.php';
                require_once ABSPATH . 'wp-admin/includes/image.php';

                $upload = media_handle_upload($name, 0);
                if (is_wp_error($upload)) {
                    if (!empty($f->required)) $errors[$name] = $f->label;
                } else {
                    $value = wp_get_attachment_url($upload);
                }
            } elseif ($entry_id) {
                $value = $wpdb->get_var($wpdb->prepare("SELECT field_value FROM {$wpdb->prefix}pfb_entry_meta WHERE entry_id=%d AND field_name=%s", $entry_id, $name));
            }
        } 
        
        // --- TYPE: STANDARD TEXT / URL / TEL ---
        else {
            $raw_val = isset($_POST[$name]) ? $_POST[$name] : '';
            
            if ($f->type === 'url') {
                $value = esc_url_raw($raw_val);
            } elseif ($f->type === 'tel') {
                $value = sanitize_text_field($raw_val); // Basic sanitization for phone
            } else {
                $value = sanitize_text_field($raw_val);
            }

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
    if ($entry_id) {
        // Mode Update: Prothome ager shob meta delete kore nite hobe jate clean update hoy
        // Athoba shudhu shei field gulo update korte hobe jeta submit hoyeche
        foreach ($data as $field => $val) {
            $wpdb->update(
                "{$wpdb->prefix}pfb_entry_meta",
                ['field_value' => $val],
                ['entry_id' => $entry_id, 'field_name' => $field]
            );
            
            // Jodi update na hoy (orthat age row chhilo na), tobe insert korbe
            if (!$wpdb->rows_affected) {
                $wpdb->insert("{$wpdb->prefix}pfb_entry_meta", [
                    'entry_id'    => $entry_id,
                    'field_name'  => $field,
                    'field_value' => $val
                ]);
            }
        }
    } else {
        // Mode Insert (Thik ache)
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
    $redirect_url = remove_query_arg(['edit', 'entry_id', 'pfb_errors'], wp_get_referer());
    wp_safe_redirect(add_query_arg('pfb_success', 1, $redirect_url));
    exit;
}