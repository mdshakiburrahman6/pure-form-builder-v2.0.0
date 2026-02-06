<?php
if (!defined('ABSPATH')) exit;

if (!function_exists('pfb_handle_access_denied')) {
    function pfb_handle_access_denied($type, $page_id = 0) {

        // MESSAGE → renderer handle
        if ($type === 'message') {
            return 'message';
        }

        // REDIRECT → delay & hook based
        return [
            'type' => $type,
            'page' => $page_id,
        ];
    }
}

function pfb_get_entry_value($entry_id, $field_name, $user_id = 0) {
    global $wpdb;

    $value = $wpdb->get_var(
        $wpdb->prepare(
            "SELECT field_value 
             FROM {$wpdb->prefix}pfb_entry_meta 
             WHERE entry_id = %d AND field_name = %s",
            $entry_id,
            $field_name
        )
    );

    return apply_filters(
        'pfb_get_field_value',
        $value,
        $field_name,
        $entry_id,
        $user_id
    );
}
